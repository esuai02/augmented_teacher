<?php
/**
 * 온톨로지 서비스 (Ontology Service)
 * 
 * Phase 2: 문항별 온톨로지 활용
 * - 개념 관계 조회
 * - 오류 패턴 분석
 * - 선행 개념 갭 분석
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 * @see        RULE_ONTOLOGY_BALANCE_DESIGN.md
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class OntologyService {
    
    private $db;
    private $problemOntology;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        
        // 온톨로지 로드
        $this->problemOntology = require(__DIR__ . '/../ontology/problem_ontology.php');
    }
    
    /**
     * 문항 정보 조회
     * 
     * @param int $itemNumber 문항 번호
     * @return array|null 문항 정보
     */
    public function getProblemItem($itemNumber) {
        $key = 'item_' . $itemNumber;
        return $this->problemOntology['problem_items'][$key] ?? null;
    }
    
    /**
     * 문항의 요구 개념 조회
     * 
     * @param int $itemNumber 문항 번호
     * @return array 요구 개념 목록
     */
    public function getRequiredConcepts($itemNumber) {
        $item = $this->getProblemItem($itemNumber);
        if (!$item) return [];
        
        $conceptIds = $item['requires_concepts'] ?? [];
        $concepts = [];
        
        foreach ($conceptIds as $conceptId) {
            $conceptKey = $this->extractConceptKey($conceptId);
            if (isset($this->problemOntology['concepts'][$conceptKey])) {
                $concepts[] = $this->problemOntology['concepts'][$conceptKey];
            }
        }
        
        return $concepts;
    }
    
    /**
     * 문항의 선행 개념 조회
     * 
     * @param int $itemNumber 문항 번호
     * @return array 선행 개념 목록
     */
    public function getPrerequisiteConcepts($itemNumber) {
        $item = $this->getProblemItem($itemNumber);
        if (!$item) return [];
        
        $prereqIds = $item['prerequisite_concepts'] ?? [];
        $prerequisites = [];
        
        foreach ($prereqIds as $prereqId) {
            $conceptKey = $this->extractConceptKey($prereqId);
            // 온톨로지에서 찾거나 기본 정보 반환
            $prerequisites[] = [
                'concept_id' => $prereqId,
                'concept_key' => $conceptKey
            ];
        }
        
        return $prerequisites;
    }
    
    /**
     * 문항의 풀이 단계 조회
     * 
     * @param int $itemNumber 문항 번호
     * @return array 풀이 단계
     */
    public function getSolvingSteps($itemNumber) {
        $item = $this->getProblemItem($itemNumber);
        return $item['solving_steps'] ?? [];
    }
    
    /**
     * 문항의 흔한 오류 조회
     * 
     * @param int $itemNumber 문항 번호
     * @return array 흔한 오류 목록
     */
    public function getCommonMistakes($itemNumber) {
        $item = $this->getProblemItem($itemNumber);
        $mistakes = $item['common_mistakes'] ?? [];
        
        // 오류 패턴 상세 정보 추가
        foreach ($mistakes as &$mistake) {
            $errorType = $mistake['type'] ?? null;
            if ($errorType && isset($this->problemOntology['error_patterns'][$errorType])) {
                $mistake['pattern_detail'] = $this->problemOntology['error_patterns'][$errorType];
            }
        }
        
        return $mistakes;
    }
    
    /**
     * 오류 패턴 분석
     * 
     * @param string $errorType 오류 유형
     * @return array|null 오류 패턴 정보
     */
    public function analyzeErrorPattern($errorType) {
        $pattern = $this->problemOntology['error_patterns'][$errorType] ?? null;
        
        if ($pattern) {
            return [
                'error_type' => $errorType,
                'label' => $pattern['rdfs:label'],
                'description' => $pattern['description'],
                'severity' => $pattern['severity'],
                'affected_concepts' => $pattern['affected_concepts'],
                'intervention_recommendations' => $pattern['intervention_recommendations'],
                'persona_prone' => $pattern['persona_prone']
            ];
        }
        
        return null;
    }
    
    /**
     * 학생 컨텍스트와 문항 요구 사항 갭 분석
     * 
     * @param int $itemNumber 문항 번호
     * @param array $studentContext 학생 컨텍스트
     * @return array 갭 분석 결과
     */
    public function analyzeConceptGap($itemNumber, $studentContext) {
        $requiredConcepts = $this->getRequiredConcepts($itemNumber);
        $prerequisiteConcepts = $this->getPrerequisiteConcepts($itemNumber);
        
        $masteredConcepts = $studentContext['mastered_concepts'] ?? [];
        $weakConcepts = $studentContext['weak_concepts'] ?? [];
        
        $gaps = [];
        $strengths = [];
        
        // 요구 개념 분석
        foreach ($requiredConcepts as $concept) {
            $conceptId = $concept['@id'] ?? '';
            $label = $concept['rdfs:label'] ?? '';
            
            if (in_array($conceptId, $masteredConcepts)) {
                $strengths[] = ['concept_id' => $conceptId, 'label' => $label, 'status' => 'mastered'];
            } elseif (in_array($conceptId, $weakConcepts)) {
                $gaps[] = [
                    'concept_id' => $conceptId,
                    'label' => $label,
                    'status' => 'weak',
                    'common_mistakes' => $concept['common_mistakes'] ?? [],
                    'teaching_methods' => $concept['teaching_methods'] ?? []
                ];
            } else {
                $gaps[] = ['concept_id' => $conceptId, 'label' => $label, 'status' => 'unknown'];
            }
        }
        
        return [
            'item_number' => $itemNumber,
            'gaps' => $gaps,
            'strengths' => $strengths,
            'gap_count' => count($gaps),
            'readiness_score' => count($strengths) / max(count($requiredConcepts), 1),
            'recommended_approach' => $this->getRecommendedApproach($itemNumber, count($gaps))
        ];
    }
    
    /**
     * 문항 난이도에 따른 접근 방식 추천
     * 
     * @param int $itemNumber 문항 번호
     * @param int $gapCount 갭 개수
     * @return array 추천 접근 방식
     */
    private function getRecommendedApproach($itemNumber, $gapCount) {
        $item = $this->getProblemItem($itemNumber);
        $difficulty = $item['difficulty'] ?? 'medium';
        
        $mapping = $this->problemOntology['difficulty_persona_mapping'][$difficulty] ?? [];
        
        return [
            'approach' => $mapping['recommended_approach'] ?? 'step_by_step',
            'primary_personas' => $mapping['primary_personas'] ?? [],
            'intervention_focus' => $mapping['intervention_focus'] ?? [],
            'needs_prerequisite_review' => $gapCount > 1
        ];
    }
    
    /**
     * 힌트 조회
     * 
     * @param int $itemNumber 문항 번호
     * @param int $level 힌트 레벨 (1-3)
     * @return string|null 힌트 내용
     */
    public function getHint($itemNumber, $level = 1) {
        $item = $this->getProblemItem($itemNumber);
        $hints = $item['hints'] ?? [];
        
        foreach ($hints as $hint) {
            if ($hint['level'] === $level) {
                return $hint['content'];
            }
        }
        
        // 기본 힌트 생성
        $steps = $item['solving_steps'] ?? [];
        if (!empty($steps) && isset($steps[$level - 1])) {
            $step = $steps[$level - 1];
            return $step['action'] . ': ' . $step['detail'];
        }
        
        return null;
    }
    
    /**
     * 개념 관계 조회
     * 
     * @param string $conceptId 개념 ID
     * @param string $relationType 관계 유형 (is_prerequisite_of, related_to)
     * @return array 관련 개념 목록
     */
    public function getRelatedConcepts($conceptId, $relationType = null) {
        $relations = $this->problemOntology['concept_relations'] ?? [];
        $related = [];
        
        foreach ($relations as $relation) {
            $matchSource = $relation['source'] === $conceptId;
            $matchTarget = $relation['target'] === $conceptId;
            $matchType = !$relationType || $relation['relation'] === $relationType;
            
            if ($matchType) {
                if ($matchSource) {
                    $related[] = [
                        'concept_id' => $relation['target'],
                        'relation' => $relation['relation'],
                        'direction' => 'outgoing',
                        'weight' => $relation['weight']
                    ];
                }
                if ($matchTarget) {
                    $related[] = [
                        'concept_id' => $relation['source'],
                        'relation' => $relation['relation'],
                        'direction' => 'incoming',
                        'weight' => $relation['weight']
                    ];
                }
            }
        }
        
        return $related;
    }
    
    /**
     * 페르소나에 맞는 교수법 추천
     * 
     * @param int $itemNumber 문항 번호
     * @param string $personaId 페르소나 ID
     * @return array 추천 교수법
     */
    public function getTeachingRecommendations($itemNumber, $personaId) {
        $item = $this->getProblemItem($itemNumber);
        $difficulty = $item['difficulty'] ?? 'medium';
        
        $recommendations = [
            'item_number' => $itemNumber,
            'persona_id' => $personaId,
            'difficulty' => $difficulty,
            'teaching_methods' => [],
            'intervention_priority' => []
        ];
        
        // 난이도-페르소나 매핑에서 개입 우선순위 가져오기
        $mapping = $this->problemOntology['difficulty_persona_mapping'][$difficulty] ?? [];
        $recommendations['intervention_priority'] = $mapping['intervention_focus'] ?? [];
        
        // 요구 개념의 교수법 수집
        $requiredConcepts = $this->getRequiredConcepts($itemNumber);
        foreach ($requiredConcepts as $concept) {
            if (isset($concept['teaching_methods'])) {
                $recommendations['teaching_methods'] = array_merge(
                    $recommendations['teaching_methods'],
                    $concept['teaching_methods']
                );
            }
        }
        
        // 페르소나별 추가 고려사항
        $recommendations['persona_considerations'] = $this->getPersonaConsiderations($personaId, $difficulty);
        
        return $recommendations;
    }
    
    /**
     * 페르소나별 고려사항
     */
    private function getPersonaConsiderations($personaId, $difficulty) {
        $considerations = [
            'P001' => ['avoid' => ['급한 진행'], 'prefer' => ['작은 단계', '격려']],
            'P002' => ['avoid' => ['자기 수정 대기'], 'prefer' => ['확인 제공', '부분 인정']],
            'P003' => ['avoid' => ['즉시 교정'], 'prefer' => ['정서 조절 우선', '작은 성공']],
            'P004' => ['avoid' => ['요약 압축'], 'prefer' => ['검증 유도', '실수 지적']],
            'P005' => ['avoid' => ['극단적 예시'], 'prefer' => ['시각화', '집중 유도']],
            'P006' => ['avoid' => ['단계 분해'], 'prefer' => ['전체 구조', '연결성']],
            'P007' => ['avoid' => ['동일 반복'], 'prefer' => ['효율적 방법', '핵심 요약']],
            'P008' => ['avoid' => ['대비 강조'], 'prefer' => ['완벽주의 완화', '이완']],
            'P009' => ['avoid' => ['역순 재구성'], 'prefer' => ['구체적 예시', '일상 비유']],
            'P010' => ['avoid' => ['긴 사고 여백'], 'prefer' => ['함께 완성', '외부 자극']],
            'P011' => ['avoid' => ['긴 대기'], 'prefer' => ['작은 목표', '즉시 인정']],
            'P012' => ['avoid' => ['단순 반복'], 'prefer' => ['도전', '자기 수정 기회']]
        ];
        
        return $considerations[$personaId] ?? ['avoid' => [], 'prefer' => []];
    }
    
    /**
     * 개념 키 추출 (mk:Concept_XXX → xxx)
     */
    private function extractConceptKey($conceptId) {
        if (strpos($conceptId, 'mk:Concept_') === 0) {
            $key = substr($conceptId, strlen('mk:Concept_'));
            return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($key)));
        }
        return strtolower($conceptId);
    }
    
    /**
     * 온톨로지 노드 DB 저장
     * 
     * @param array $nodeData 노드 데이터
     * @param string $sessionId 세션 ID
     * @return bool 성공 여부
     */
    public function saveOntologyNode($nodeData, $sessionId = null) {
        try {
            $record = [
                'node_id' => $nodeData['node_id'] ?? 'NODE_' . uniqid(),
                'session_id' => $sessionId,
                'student_id' => $nodeData['student_id'] ?? null,
                'contents_id' => $nodeData['contents_id'] ?? null,
                'contents_type' => $nodeData['contents_type'] ?? null,
                'node_type' => $nodeData['node_type'] ?? 'concept',
                'node_label' => $nodeData['node_label'] ?? '',
                'parent_node_id' => $nodeData['parent_node_id'] ?? null,
                'namespace' => $nodeData['namespace'] ?? 'math',
                'layer' => $nodeData['layer'] ?? 'session',
                'properties' => json_encode($nodeData['properties'] ?? []),
                'relations' => json_encode($nodeData['relations'] ?? []),
                'source' => $nodeData['source'] ?? 'system',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42i_ontology_nodes', (object)$record);
            return true;
        } catch (Exception $e) {
            error_log("[OntologyService] saveOntologyNode 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return false;
        }
    }
    
    /**
     * 온톨로지 관계 DB 저장
     * 
     * @param string $sourceNodeId 소스 노드 ID
     * @param string $targetNodeId 타겟 노드 ID
     * @param string $relationType 관계 유형
     * @param float $weight 관계 강도
     * @return bool 성공 여부
     */
    public function saveOntologyRelation($sourceNodeId, $targetNodeId, $relationType, $weight = 1.0) {
        try {
            $record = [
                'source_node_id' => $sourceNodeId,
                'target_node_id' => $targetNodeId,
                'relation_type' => $relationType,
                'weight' => $weight,
                'direction' => 'unidirectional',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $this->db->insert_record('alt42i_ontology_relations', (object)$record);
            return true;
        } catch (Exception $e) {
            error_log("[OntologyService] saveOntologyRelation 오류: " . $e->getMessage() . " (파일: " . __FILE__ . ", 라인: " . __LINE__ . ")");
            return false;
        }
    }
}

