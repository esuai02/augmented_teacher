<?php
/**
 * 학생 컨텍스트 서비스 (Context Service)
 * 
 * Phase 3: 학생 컨텍스트 누적 시스템
 * - 세션별 상호작용 누적
 * - 페르소나 신뢰도 업데이트
 * - 취약 개념 히스토리
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class ContextService {
    
    private $db;
    private $userId;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $DB, $USER;
        $this->db = $DB;
        $this->userId = $USER->id ?? 0;
    }
    
    /**
     * 학생 컨텍스트 조회 또는 생성
     * 
     * @param int $studentId 학생 ID
     * @return object 학생 컨텍스트
     */
    public function getOrCreateContext($studentId) {
        $context = $this->getContext($studentId);
        
        if (!$context) {
            $context = $this->createContext($studentId);
        }
        
        return $context;
    }
    
    /**
     * 학생 컨텍스트 조회
     * 
     * @param int $studentId 학생 ID
     * @return object|null 학생 컨텍스트
     */
    public function getContext($studentId) {
        try {
            return $this->db->get_record('alt42_student_contexts', ['student_id' => $studentId]);
        } catch (Exception $e) {
            error_log("[ContextService] getContext 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return null;
        }
    }
    
    /**
     * 학생 컨텍스트 생성
     * 
     * @param int $studentId 학생 ID
     * @return object 생성된 컨텍스트
     */
    public function createContext($studentId) {
        $context = [
            'student_id' => $studentId,
            'current_unit' => null,
            'current_concept' => null,
            'understanding_level' => 'medium',
            'concepts_learned' => json_encode([]),
            'concepts_struggling' => json_encode([]),
            'learning_style' => null,
            'preferred_explanation' => null,
            'context_data' => json_encode([
                'total_sessions' => 0,
                'avg_session_duration' => 0,
                'preferred_personas' => [],
                'concept_mastery' => []
            ]),
            'session_count' => 0,
            'total_interactions' => 0,
            'last_activity_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        try {
            $this->db->insert_record('alt42_student_contexts', (object)$context);
            return $this->getContext($studentId);
        } catch (Exception $e) {
            error_log("[ContextService] createContext 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return (object)$context;
        }
    }
    
    /**
     * 학생 컨텍스트 업데이트
     * 
     * @param int $studentId 학생 ID
     * @param array $updates 업데이트할 필드
     * @return bool 성공 여부
     */
    public function updateContext($studentId, $updates) {
        try {
            $context = $this->getContext($studentId);
            if (!$context) {
                $context = $this->createContext($studentId);
            }
            
            foreach ($updates as $key => $value) {
                if (property_exists($context, $key)) {
                    if (is_array($value)) {
                        $context->$key = json_encode($value);
                    } else {
                        $context->$key = $value;
                    }
                }
            }
            
            $context->updated_at = date('Y-m-d H:i:s');
            $context->last_activity_at = date('Y-m-d H:i:s');
            
            $this->db->update_record('alt42_student_contexts', $context);
            return true;
        } catch (Exception $e) {
            error_log("[ContextService] updateContext 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return false;
        }
    }
    
    /**
     * 세션 완료 시 컨텍스트 업데이트
     * 
     * @param int $studentId 학생 ID
     * @param array $sessionSummary 세션 요약 데이터
     */
    public function updateFromSession($studentId, $sessionSummary) {
        $context = $this->getOrCreateContext($studentId);
        $contextData = json_decode($context->context_data ?? '{}', true);
        
        // 세션 카운트 증가
        $sessionCount = ($context->session_count ?? 0) + 1;
        
        // 총 상호작용 수 증가
        $totalInteractions = ($context->total_interactions ?? 0) + ($sessionSummary['interaction_count'] ?? 0);
        
        // 평균 세션 시간 업데이트
        $prevAvg = $contextData['avg_session_duration'] ?? 0;
        $newDuration = $sessionSummary['duration_seconds'] ?? 0;
        $newAvg = (($prevAvg * ($sessionCount - 1)) + $newDuration) / $sessionCount;
        $contextData['avg_session_duration'] = round($newAvg);
        $contextData['total_sessions'] = $sessionCount;
        
        // 이해도 레벨 업데이트
        $understandingLevel = $this->calculateUnderstandingLevel($sessionSummary);
        
        // 학습한 개념 업데이트
        $conceptsLearned = json_decode($context->concepts_learned ?? '[]', true);
        if (isset($sessionSummary['concepts_covered'])) {
            $conceptsLearned = array_unique(array_merge($conceptsLearned, $sessionSummary['concepts_covered']));
        }
        
        // 취약 개념 업데이트
        $conceptsStruggling = json_decode($context->concepts_struggling ?? '[]', true);
        if (isset($sessionSummary['concepts_struggled'])) {
            $conceptsStruggling = array_unique(array_merge($conceptsStruggling, $sessionSummary['concepts_struggled']));
        }
        // 마스터한 개념은 취약 목록에서 제거
        $conceptsStruggling = array_diff($conceptsStruggling, $sessionSummary['concepts_mastered'] ?? []);
        
        $this->updateContext($studentId, [
            'session_count' => $sessionCount,
            'total_interactions' => $totalInteractions,
            'understanding_level' => $understandingLevel,
            'concepts_learned' => array_values($conceptsLearned),
            'concepts_struggling' => array_values($conceptsStruggling),
            'context_data' => $contextData
        ]);
    }
    
    /**
     * 이해도 레벨 계산
     * 
     * @param array $sessionSummary 세션 요약
     * @return string 이해도 레벨
     */
    private function calculateUnderstandingLevel($sessionSummary) {
        $correctRate = $sessionSummary['correct_rate'] ?? 0.5;
        
        if ($correctRate >= 0.9) return 'very_high';
        if ($correctRate >= 0.75) return 'high';
        if ($correctRate >= 0.5) return 'medium';
        if ($correctRate >= 0.25) return 'low';
        return 'very_low';
    }
    
    // ========================================
    // 페르소나 관련 메서드
    // ========================================
    
    /**
     * 현재 페르소나 조회
     * 
     * @param int $studentId 학생 ID
     * @return object|null 페르소나 매칭 정보
     */
    public function getCurrentPersona($studentId) {
        try {
            return $this->db->get_record('alt42_student_personas', [
                'student_id' => $studentId,
                'is_current' => 1
            ]);
        } catch (Exception $e) {
            error_log("[ContextService] getCurrentPersona 오류: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 페르소나 할당 또는 업데이트
     * 
     * @param int $studentId 학생 ID
     * @param string $personaId 페르소나 ID
     * @param float $matchScore 매칭 점수
     * @param float $confidence 신뢰도
     * @return bool 성공 여부
     */
    public function assignPersona($studentId, $personaId, $matchScore = 0.5, $confidence = 0.5) {
        try {
            // 기존 현재 페르소나 해제
            $this->db->execute(
                "UPDATE {alt42_student_personas} SET is_current = 0 WHERE student_id = ?",
                [$studentId]
            );
            
            // 새 페르소나 할당
            $record = [
                'student_id' => $studentId,
                'persona_id' => $personaId,
                'match_score' => $matchScore,
                'confidence' => $confidence,
                'interaction_patterns' => json_encode([]),
                'is_current' => 1,
                'matched_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42_student_personas', (object)$record);
            return true;
        } catch (Exception $e) {
            error_log("[ContextService] assignPersona 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return false;
        }
    }
    
    /**
     * 페르소나 신뢰도 업데이트
     * 
     * @param int $studentId 학생 ID
     * @param float $confidenceChange 신뢰도 변화량 (-1.0 ~ 1.0)
     * @param array $interactionData 상호작용 데이터
     * @return bool 성공 여부
     */
    public function updatePersonaConfidence($studentId, $confidenceChange, $interactionData = []) {
        try {
            $current = $this->getCurrentPersona($studentId);
            if (!$current) return false;
            
            // 신뢰도 업데이트 (0.0 ~ 1.0 범위 유지)
            $newConfidence = max(0, min(1, $current->confidence + $confidenceChange));
            
            // 상호작용 패턴 업데이트
            $patterns = json_decode($current->interaction_patterns ?? '[]', true);
            $patterns[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'confidence_change' => $confidenceChange,
                'data' => $interactionData
            ];
            // 최근 50개만 유지
            $patterns = array_slice($patterns, -50);
            
            $current->confidence = $newConfidence;
            $current->interaction_patterns = json_encode($patterns);
            
            $this->db->update_record('alt42_student_personas', $current);
            
            // 신뢰도가 낮아지면 페르소나 스위칭 검토
            if ($newConfidence < 0.3) {
                $this->suggestPersonaSwitch($studentId, $current->persona_id, $patterns);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("[ContextService] updatePersonaConfidence 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 페르소나 스위칭 제안
     * 
     * @param int $studentId 학생 ID
     * @param string $currentPersonaId 현재 페르소나 ID
     * @param array $patterns 상호작용 패턴
     */
    private function suggestPersonaSwitch($studentId, $currentPersonaId, $patterns) {
        // 패턴 분석하여 새 페르소나 추천 (간단한 휴리스틱)
        // TODO: 더 정교한 알고리즘 구현
        
        $emotionPatterns = [];
        foreach ($patterns as $p) {
            if (isset($p['data']['emotion_type'])) {
                $emotionPatterns[] = $p['data']['emotion_type'];
            }
        }
        
        // 감정 패턴 기반 페르소나 추천
        $emotionCounts = array_count_values($emotionPatterns);
        $dominantEmotion = array_key_first($emotionCounts) ?? 'neutral';
        
        $emotionToPersona = [
            'anxious' => 'P008', // 불안과몰입형
            'stuck' => 'P001',   // 막힘-회피형
            'confused' => 'P009', // 추상약함형
            'confident' => 'P012' // 메타인지고수형
        ];
        
        $suggestedPersona = $emotionToPersona[$dominantEmotion] ?? $currentPersonaId;
        
        if ($suggestedPersona !== $currentPersonaId) {
            // 스위칭 기록
            $this->recordPersonaSwitch($studentId, $currentPersonaId, $suggestedPersona, 'low_confidence');
        }
    }
    
    /**
     * 페르소나 스위칭 기록
     * 
     * @param int $studentId 학생 ID
     * @param string $fromPersonaId 이전 페르소나 ID
     * @param string $toPersonaId 새 페르소나 ID
     * @param string $reason 스위칭 이유
     * @return bool 성공 여부
     */
    public function recordPersonaSwitch($studentId, $fromPersonaId, $toPersonaId, $reason = null) {
        try {
            $record = [
                'student_id' => $studentId,
                'from_persona_id' => $fromPersonaId,
                'to_persona_id' => $toPersonaId,
                'switch_reason' => $reason,
                'context_snapshot' => json_encode([
                    'timestamp' => date('Y-m-d H:i:s')
                ]),
                'switched_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42_persona_switches', (object)$record);
            
            // 새 페르소나 할당
            $this->assignPersona($studentId, $toPersonaId, 0.5, 0.5);
            
            return true;
        } catch (Exception $e) {
            error_log("[ContextService] recordPersonaSwitch 오류: " . $e->getMessage());
            return false;
        }
    }
    
    // ========================================
    // 취약 개념 관리
    // ========================================
    
    /**
     * 취약 개념 추가
     * 
     * @param int $studentId 학생 ID
     * @param string $conceptId 개념 ID
     * @param string $errorType 오류 유형
     */
    public function addWeakConcept($studentId, $conceptId, $errorType = null) {
        $context = $this->getOrCreateContext($studentId);
        $conceptsStruggling = json_decode($context->concepts_struggling ?? '[]', true);
        
        if (!in_array($conceptId, $conceptsStruggling)) {
            $conceptsStruggling[] = $conceptId;
            $this->updateContext($studentId, [
                'concepts_struggling' => $conceptsStruggling
            ]);
        }
        
        // 취약 개념 히스토리 기록
        $this->logWeakConceptHistory($studentId, $conceptId, $errorType);
    }
    
    /**
     * 개념 마스터리 기록
     * 
     * @param int $studentId 학생 ID
     * @param string $conceptId 개념 ID
     */
    public function markConceptMastered($studentId, $conceptId) {
        $context = $this->getOrCreateContext($studentId);
        
        // 학습 완료 목록에 추가
        $conceptsLearned = json_decode($context->concepts_learned ?? '[]', true);
        if (!in_array($conceptId, $conceptsLearned)) {
            $conceptsLearned[] = $conceptId;
        }
        
        // 취약 목록에서 제거
        $conceptsStruggling = json_decode($context->concepts_struggling ?? '[]', true);
        $conceptsStruggling = array_values(array_diff($conceptsStruggling, [$conceptId]));
        
        // 마스터리 데이터 업데이트
        $contextData = json_decode($context->context_data ?? '{}', true);
        $contextData['concept_mastery'][$conceptId] = [
            'mastered_at' => date('Y-m-d H:i:s'),
            'mastery_level' => 1.0
        ];
        
        $this->updateContext($studentId, [
            'concepts_learned' => $conceptsLearned,
            'concepts_struggling' => $conceptsStruggling,
            'context_data' => $contextData
        ]);
    }
    
    /**
     * 취약 개념 히스토리 기록
     */
    private function logWeakConceptHistory($studentId, $conceptId, $errorType) {
        // context_data에 히스토리 추가
        $context = $this->getContext($studentId);
        if (!$context) return;
        
        $contextData = json_decode($context->context_data ?? '{}', true);
        
        if (!isset($contextData['weak_concept_history'])) {
            $contextData['weak_concept_history'] = [];
        }
        
        $contextData['weak_concept_history'][] = [
            'concept_id' => $conceptId,
            'error_type' => $errorType,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // 최근 100개만 유지
        $contextData['weak_concept_history'] = array_slice($contextData['weak_concept_history'], -100);
        
        $this->updateContext($studentId, ['context_data' => $contextData]);
    }
    
    // ========================================
    // 컨텍스트 스냅샷
    // ========================================
    
    /**
     * 현재 컨텍스트 스냅샷 생성
     * 
     * @param int $studentId 학생 ID
     * @param string $sessionId 세션 ID
     * @return array 컨텍스트 스냅샷
     */
    public function createContextSnapshot($studentId, $sessionId = null) {
        $context = $this->getOrCreateContext($studentId);
        $persona = $this->getCurrentPersona($studentId);
        
        return [
            'student_id' => $studentId,
            'session_id' => $sessionId,
            'snapshot_at' => date('Y-m-d H:i:s'),
            'understanding_level' => $context->understanding_level ?? 'medium',
            'current_persona' => $persona->persona_id ?? null,
            'persona_confidence' => $persona->confidence ?? 0.5,
            'concepts_learned_count' => count(json_decode($context->concepts_learned ?? '[]', true)),
            'concepts_struggling_count' => count(json_decode($context->concepts_struggling ?? '[]', true)),
            'session_count' => $context->session_count ?? 0,
            'total_interactions' => $context->total_interactions ?? 0
        ];
    }
    
    /**
     * 컨텍스트 스냅샷 저장
     * 
     * @param array $snapshot 스냅샷 데이터
     * @return bool 성공 여부
     */
    public function saveContextSnapshot($snapshot) {
        try {
            $record = [
                'session_id' => $snapshot['session_id'],
                'student_id' => $snapshot['student_id'],
                'contents_id' => $snapshot['contents_id'] ?? null,
                'contents_type' => $snapshot['contents_type'] ?? null,
                'context_summary' => json_encode($snapshot),
                'cognitive_load' => $snapshot['cognitive_load'] ?? 'medium',
                'engagement_level' => $snapshot['engagement_level'] ?? 'medium',
                'understanding_level' => $snapshot['understanding_level'] ?? 'medium',
                'recorded_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42i_context_states', (object)$record);
            return true;
        } catch (Exception $e) {
            error_log("[ContextService] saveContextSnapshot 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return false;
        }
    }
    
    /**
     * 학습 스타일 추론
     * 
     * @param int $studentId 학생 ID
     * @return string 추론된 학습 스타일
     */
    public function inferLearningStyle($studentId) {
        $context = $this->getContext($studentId);
        if (!$context) return 'unknown';
        
        $contextData = json_decode($context->context_data ?? '{}', true);
        
        // 상호작용 패턴 분석
        // TODO: 더 정교한 분석 구현
        
        $preferredExplanation = $context->preferred_explanation;
        
        if ($preferredExplanation === 'visual') return 'visual';
        if ($preferredExplanation === 'step_by_step') return 'sequential';
        if ($preferredExplanation === 'example') return 'concrete';
        
        return 'balanced';
    }
}

