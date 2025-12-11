<?php
/**
 * 상호작용 서비스 (Interaction Service)
 * 
 * Phase 1: 즉각 반응 시스템의 핵심 서비스
 * - 룰 평가 → 개입 활동 실행
 * - 컨텍스트 관리
 * - 상호작용 로깅
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

require_once(__DIR__ . '/../includes/rule_evaluator.php');

class InteractionService {
    
    private $db;
    private $userId;
    private $immediateRules;
    private $personaRules;
    private $interventionMapping;
    private $ruleEvaluator;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $DB, $USER;
        $this->db = $DB;
        $this->userId = $USER->id ?? 0;
        
        // 룰과 매핑 로드
        $this->loadRulesAndMappings();
    }
    
    /**
     * 룰과 매핑 로드
     */
    private function loadRulesAndMappings() {
        $rulesDir = __DIR__ . '/../rules/';
        
        $this->immediateRules = require($rulesDir . 'immediate_rules.php');
        $this->personaRules = require($rulesDir . 'persona_rules.php');
        $this->interventionMapping = require($rulesDir . 'intervention_mapping.php');
        
        // 룰 평가기 초기화
        $this->ruleEvaluator = new RuleEvaluator($this->immediateRules);
    }
    
    /**
     * 세션 시작
     * 
     * @param int $studentId 학생 ID
     * @param string $contentsId 컨텐츠 ID
     * @param string $contentsType 컨텐츠 유형
     * @param string $whiteboardId 화이트보드 ID
     * @return array 세션 정보
     */
    public function startSession($studentId, $contentsId, $contentsType, $whiteboardId = null) {
        $sessionId = 'SESSION_' . time() . '_' . rand(1000, 9999);
        
        // 학생 페르소나 조회
        $studentPersona = $this->getStudentPersona($studentId);
        
        $session = [
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'contents_id' => $contentsId,
            'contents_type' => $contentsType,
            'whiteboard_id' => $whiteboardId,
            'persona_id' => $studentPersona['persona_id'] ?? 'P002', // 기본: 확인요구형
            'current_step' => 1,
            'emotion_type' => 'neutral',
            'session_status' => 'active',
            'started_at' => date('Y-m-d H:i:s')
        ];
        
        // DB 저장 (mdl_alt42i_sessions)
        try {
            $this->db->insert_record('alt42i_sessions', (object)$session);
        } catch (Exception $e) {
            error_log("[InteractionService] startSession 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
        }
        
        // 세션 시작 이벤트 처리
        $initContext = [
            'event_type' => 'session_start',
            'session_id' => $sessionId,
            'student_id' => $studentId,
            'persona_id' => $session['persona_id']
        ];
        $this->processEvent($initContext);
        
        return [
            'success' => true,
            'session_id' => $sessionId,
            'persona' => $studentPersona,
            'message' => '학습 세션이 시작되었습니다.'
        ];
    }
    
    /**
     * 이벤트 처리 (핵심 메서드)
     * 
     * @param array $eventContext 이벤트 컨텍스트
     * @return array 처리 결과 (개입 활동 포함)
     */
    public function processEvent($eventContext) {
        $result = [
            'matched_rules' => [],
            'interventions' => [],
            'feedback' => null,
            'next_action' => null
        ];
        
        // 1. 페르소나 컨텍스트 추가
        $personaId = $eventContext['persona_id'] ?? null;
        if ($personaId && isset($this->personaRules[$personaId])) {
            $eventContext['persona_rules'] = $this->personaRules[$personaId];
        }
        
        // 2. 즉각 반응 룰 평가
        $matchedRules = $this->ruleEvaluator->evaluate($eventContext);
        $result['matched_rules'] = $matchedRules;
        
        // 3. 페르소나 룰 평가 (추가)
        if ($personaId && isset($this->personaRules[$personaId]['rules'])) {
            $personaRuleEvaluator = new RuleEvaluator($this->personaRules[$personaId]['rules']);
            $personaMatchedRules = $personaRuleEvaluator->evaluate($eventContext);
            $result['matched_rules'] = array_merge($result['matched_rules'], $personaMatchedRules);
        }
        
        // 4. 매칭된 룰의 개입 활동 수집
        foreach ($result['matched_rules'] as $rule) {
            $actionId = $rule['action'] ?? null;
            if ($actionId && isset($this->interventionMapping[$actionId])) {
                $intervention = $this->interventionMapping[$actionId];
                $intervention['triggered_by'] = $rule['rule_id'];
                $intervention['confidence'] = $rule['confidence'] ?? 0.8;
                $intervention['message'] = $rule['message'] ?? $intervention['ui_action']['message'] ?? null;
                $result['interventions'][] = $intervention;
            }
        }
        
        // 5. 우선순위 정렬 및 최우선 개입 선택
        if (!empty($result['interventions'])) {
            usort($result['interventions'], function($a, $b) {
                return ($a['priority'] ?? 2) - ($b['priority'] ?? 2);
            });
            
            $primaryIntervention = $result['interventions'][0];
            $result['feedback'] = $this->generateFeedback($primaryIntervention, $eventContext);
            $result['next_action'] = $primaryIntervention['ui_action'] ?? null;
        }
        
        // 6. 상호작용 로그 기록
        $this->logInteraction($eventContext, $result);
        
        return $result;
    }
    
    /**
     * 제스처 입력 처리
     * 
     * @param string $sessionId 세션 ID
     * @param string $gestureType 제스처 유형 (check, cross, question, circle, arrow)
     * @param array $additionalContext 추가 컨텍스트
     * @return array 처리 결과
     */
    public function processGesture($sessionId, $gestureType, $additionalContext = []) {
        // 세션 정보 조회
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['success' => false, 'error' => '세션을 찾을 수 없습니다.'];
        }
        
        $context = array_merge([
            'event_type' => 'gesture_input',
            'gesture_type' => $gestureType,
            'session_id' => $sessionId,
            'student_id' => $session->student_id,
            'persona_id' => $session->persona_id,
            'current_step' => $session->current_step,
            'max_step' => 5 // 기본 5단계
        ], $additionalContext);
        
        $result = $this->processEvent($context);
        
        // 제스처 기록
        $this->logGesture($sessionId, $gestureType, $result);
        
        return [
            'success' => true,
            'gesture_type' => $gestureType,
            'result' => $result
        ];
    }
    
    /**
     * 감정 변경 처리
     * 
     * @param string $sessionId 세션 ID
     * @param string $emotionType 감정 유형
     * @return array 처리 결과
     */
    public function processEmotionChange($sessionId, $emotionType) {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['success' => false, 'error' => '세션을 찾을 수 없습니다.'];
        }
        
        $previousEmotion = $session->emotion_type;
        $emotionChange = $this->determineEmotionChange($previousEmotion, $emotionType);
        
        // 세션 업데이트
        $this->updateSession($sessionId, ['emotion_type' => $emotionType]);
        
        $context = [
            'event_type' => 'emotion_change',
            'emotion_type' => $emotionType,
            'previous_emotion' => $previousEmotion,
            'emotion_change' => $emotionChange,
            'session_id' => $sessionId,
            'student_id' => $session->student_id,
            'persona_id' => $session->persona_id
        ];
        
        $result = $this->processEvent($context);
        
        // 감정 히스토리 기록
        $this->logEmotionHistory($sessionId, $previousEmotion, $emotionType);
        
        return [
            'success' => true,
            'emotion_type' => $emotionType,
            'emotion_change' => $emotionChange,
            'result' => $result
        ];
    }
    
    /**
     * 필기 패턴 처리
     * 
     * @param string $sessionId 세션 ID
     * @param array $patternData 패턴 데이터
     * @return array 처리 결과
     */
    public function processWritingPattern($sessionId, $patternData) {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['success' => false, 'error' => '세션을 찾을 수 없습니다.'];
        }
        
        $context = array_merge([
            'event_type' => 'writing_pattern',
            'session_id' => $sessionId,
            'student_id' => $session->student_id,
            'persona_id' => $session->persona_id,
            'current_step' => $session->current_step,
            'session_status' => $session->session_status
        ], $patternData);
        
        $result = $this->processEvent($context);
        
        // 패턴 기록
        $this->logWritingPattern($sessionId, $patternData, $result);
        
        return [
            'success' => true,
            'pattern' => $patternData,
            'result' => $result
        ];
    }
    
    /**
     * 단계 진행
     * 
     * @param string $sessionId 세션 ID
     * @param int $newStep 새 단계
     * @return array 처리 결과
     */
    public function advanceStep($sessionId, $newStep = null) {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['success' => false, 'error' => '세션을 찾을 수 없습니다.'];
        }
        
        $currentStep = $session->current_step;
        $newStep = $newStep ?? ($currentStep + 1);
        
        // 세션 업데이트
        $this->updateSession($sessionId, ['current_step' => $newStep]);
        
        // 단계 완료 이벤트
        $context = [
            'event_type' => 'step_change',
            'step_status' => 'completed',
            'previous_step' => $currentStep,
            'current_step' => $newStep,
            'session_id' => $sessionId,
            'student_id' => $session->student_id,
            'persona_id' => $session->persona_id
        ];
        
        $result = $this->processEvent($context);
        
        // 진행률 계산
        $maxStep = 5;
        $progress = round(($newStep / $maxStep) * 100);
        
        return [
            'success' => true,
            'current_step' => $newStep,
            'progress' => $progress,
            'result' => $result
        ];
    }
    
    /**
     * 세션 종료
     * 
     * @param string $sessionId 세션 ID
     * @return array 처리 결과
     */
    public function endSession($sessionId) {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return ['success' => false, 'error' => '세션을 찾을 수 없습니다.'];
        }
        
        $endedAt = date('Y-m-d H:i:s');
        $startedAt = strtotime($session->started_at);
        $duration = time() - $startedAt;
        
        $this->updateSession($sessionId, [
            'session_status' => 'completed',
            'ended_at' => $endedAt,
            'duration_seconds' => $duration
        ]);
        
        // 학습 성과 집계
        $outcomes = $this->calculateLearningOutcomes($sessionId);
        
        return [
            'success' => true,
            'session_id' => $sessionId,
            'duration_seconds' => $duration,
            'outcomes' => $outcomes
        ];
    }
    
    // ========================================
    // 헬퍼 메서드
    // ========================================
    
    /**
     * 학생 페르소나 조회
     */
    private function getStudentPersona($studentId) {
        try {
            $record = $this->db->get_record('alt42_student_personas', [
                'student_id' => $studentId,
                'is_current' => 1
            ]);
            
            if ($record) {
                $personaId = $record->persona_id;
                if (isset($this->personaRules[$personaId])) {
                    return $this->personaRules[$personaId];
                }
            }
        } catch (Exception $e) {
            error_log("[InteractionService] getStudentPersona 오류: " . $e->getMessage());
        }
        
        // 기본 페르소나 반환
        return $this->personaRules['P002'] ?? ['persona_id' => 'P002', 'name' => '확인요구형'];
    }
    
    /**
     * 세션 조회
     */
    private function getSession($sessionId) {
        try {
            return $this->db->get_record('alt42i_sessions', ['session_id' => $sessionId]);
        } catch (Exception $e) {
            error_log("[InteractionService] getSession 오류: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 세션 업데이트
     */
    private function updateSession($sessionId, $updates) {
        try {
            $record = $this->db->get_record('alt42i_sessions', ['session_id' => $sessionId]);
            if ($record) {
                foreach ($updates as $key => $value) {
                    $record->$key = $value;
                }
                $this->db->update_record('alt42i_sessions', $record);
            }
        } catch (Exception $e) {
            error_log("[InteractionService] updateSession 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 피드백 생성
     */
    private function generateFeedback($intervention, $context) {
        $message = $intervention['message'] ?? null;
        $uiAction = $intervention['ui_action'] ?? [];
        
        // 템플릿 변수 치환
        if (isset($uiAction['message_template'])) {
            $message = $uiAction['message_template'];
            foreach ($context as $key => $value) {
                if (is_string($value)) {
                    $message = str_replace('{' . $key . '}', $value, $message);
                }
            }
        }
        
        return [
            'activity_id' => $intervention['activity_id'],
            'category' => $intervention['category'],
            'name' => $intervention['name'],
            'message' => $message,
            'style' => $uiAction['style'] ?? 'default',
            'show_breathing_bar' => $uiAction['show_breathing_bar'] ?? false,
            'duration_ms' => $uiAction['duration_ms'] ?? 3000
        ];
    }
    
    /**
     * 감정 변화 판단
     */
    private function determineEmotionChange($previous, $current) {
        $positiveEmotions = ['confident'];
        $neutralEmotions = ['neutral'];
        $negativeEmotions = ['confused', 'stuck', 'anxious'];
        
        $prevScore = in_array($previous, $positiveEmotions) ? 1 : (in_array($previous, $neutralEmotions) ? 0 : -1);
        $currScore = in_array($current, $positiveEmotions) ? 1 : (in_array($current, $neutralEmotions) ? 0 : -1);
        
        $diff = $currScore - $prevScore;
        if ($diff > 0) return 'positive';
        if ($diff < 0) return 'negative';
        return 'stable';
    }
    
    /**
     * 상호작용 로그 기록
     */
    private function logInteraction($context, $result) {
        try {
            $log = [
                'session_id' => $context['session_id'] ?? null,
                'student_id' => $context['student_id'] ?? $this->userId,
                'contents_id' => $context['contents_id'] ?? null,
                'contents_type' => $context['contents_type'] ?? null,
                'event_type' => $context['event_type'] ?? 'unknown',
                'event_data' => json_encode($context),
                'triggered_rules' => json_encode($result['matched_rules'] ?? []),
                'triggered_interventions' => json_encode($result['interventions'] ?? []),
                'timestamp_ms' => round(microtime(true) * 1000),
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42i_interaction_logs', (object)$log);
        } catch (Exception $e) {
            error_log("[InteractionService] logInteraction 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 제스처 로그 기록
     */
    private function logGesture($sessionId, $gestureType, $result) {
        try {
            $session = $this->getSession($sessionId);
            $gesture = [
                'session_id' => $sessionId,
                'student_id' => $session->student_id ?? $this->userId,
                'contents_id' => $session->contents_id ?? null,
                'contents_type' => $session->contents_type ?? null,
                'gesture_type' => $gestureType,
                'gesture_symbol' => $this->getGestureSymbol($gestureType),
                'action_taken' => json_encode($result['next_action'] ?? null),
                'recognized_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42i_gestures', (object)$gesture);
        } catch (Exception $e) {
            error_log("[InteractionService] logGesture 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 감정 히스토리 기록
     */
    private function logEmotionHistory($sessionId, $previousEmotion, $newEmotion) {
        try {
            $session = $this->getSession($sessionId);
            $history = [
                'session_id' => $sessionId,
                'student_id' => $session->student_id ?? $this->userId,
                'contents_id' => $session->contents_id ?? null,
                'contents_type' => $session->contents_type ?? null,
                'previous_emotion' => $previousEmotion,
                'new_emotion' => $newEmotion,
                'change_source' => 'student', // student 또는 ai
                'changed_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42i_emotion_history', (object)$history);
        } catch (Exception $e) {
            error_log("[InteractionService] logEmotionHistory 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 필기 패턴 로그 기록
     */
    private function logWritingPattern($sessionId, $patternData, $result) {
        try {
            $session = $this->getSession($sessionId);
            $pattern = [
                'pattern_id' => 'PATTERN_' . time() . '_' . rand(1000, 9999),
                'session_id' => $sessionId,
                'student_id' => $session->student_id ?? $this->userId,
                'pattern_type' => $patternData['pattern_type'] ?? 'unknown',
                'duration' => $patternData['pause_duration'] ?? null,
                'count' => $patternData['erase_count'] ?? 1,
                'inferred_state' => $this->inferCognitiveState($patternData),
                'intervention_triggered' => $result['interventions'][0]['activity_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42_writing_patterns', (object)$pattern);
        } catch (Exception $e) {
            error_log("[InteractionService] logWritingPattern 오류: " . $e->getMessage());
        }
    }
    
    /**
     * 인지 상태 추론
     */
    private function inferCognitiveState($patternData) {
        if (isset($patternData['pause_duration']) && $patternData['pause_duration'] >= 10) {
            return 'stuck';
        }
        if (isset($patternData['pause_duration']) && $patternData['pause_duration'] >= 3) {
            return 'cognitive_load';
        }
        if (isset($patternData['erase_count']) && $patternData['erase_count'] >= 3) {
            return 'confusion';
        }
        return 'processing';
    }
    
    /**
     * 제스처 심볼 반환
     */
    private function getGestureSymbol($gestureType) {
        $symbols = [
            'check' => '✓',
            'cross' => '✗',
            'question' => '?',
            'circle' => '○',
            'arrow' => '→'
        ];
        return $symbols[$gestureType] ?? '?';
    }
    
    /**
     * 학습 성과 계산
     */
    private function calculateLearningOutcomes($sessionId) {
        // TODO: 상세 구현
        return [
            'session_id' => $sessionId,
            'total_interactions' => 0,
            'gesture_count' => 0,
            'intervention_count' => 0,
            'avg_understanding' => 0.5
        ];
    }
}

