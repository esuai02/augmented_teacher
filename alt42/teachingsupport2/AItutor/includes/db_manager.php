<?php
/**
 * DB 매니저
 * Moodle DB API를 사용하여 시스템 데이터 관리
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    2.0 (MySQL 버전)
 */

class DBManager {
    /** @var string 테이블 접두사 (mdl_ 제외) */
    private $tablePrefix = 'alt42_';
    
    /** @var moodle_database Moodle DB 인스턴스 */
    private $db;
    
    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }
    
    /**
     * 테이블명 반환 (접두사 적용)
     * @param string $tableName 테이블명
     * @return string 전체 테이블명
     */
    private function table($tableName) {
        return $this->tablePrefix . $tableName;
    }
    
    // =========================================
    // 분석 결과 (analysis_results)
    // =========================================
    
    /**
     * 분석 결과 저장
     * @param array $analysisData 분석 데이터
     * @return string|bool analysis_id 또는 실패시 false
     */
    public function saveAnalysisResult($analysisData) {
        global $USER;
        
        try {
            // analysis_id 생성
            if (!isset($analysisData['analysis_id'])) {
                $analysisData['analysis_id'] = 'ANALYSIS_' . time() . '_' . mt_rand(1000, 9999);
            }
            $analysisId = $analysisData['analysis_id'];
            
            // 기존 레코드 확인
            $existing = $this->db->get_record($this->table('analysis_results'), [
                'analysis_id' => $analysisId
            ]);
            
            // 레코드 객체 생성
            $record = new stdClass();
            $record->analysis_id = $analysisId;
            $record->student_id = $analysisData['student_id'] ?? $USER->id;
            $record->created_by = $analysisData['created_by'] ?? $USER->id;
            $record->text_content = $analysisData['text_content'] ?? '';
            $record->image_data = $analysisData['image_data'] ?? '';
            $record->dialogue_analysis = $this->jsonEncode($analysisData['dialogue_analysis'] ?? null);
            $record->comprehensive_questions = $this->jsonEncode($analysisData['comprehensive_questions'] ?? []);
            $record->detailed_questions = $this->jsonEncode($analysisData['detailed_questions'] ?? []);
            $record->teaching_rules = $this->jsonEncode($analysisData['teaching_rules'] ?? []);
            $record->ontology = $this->jsonEncode($analysisData['ontology'] ?? null);
            $record->rule_contents = $this->jsonEncode($analysisData['rule_contents'] ?? null);
            $record->metadata = $this->jsonEncode($analysisData['metadata'] ?? null);
            
            if ($existing) {
                // 업데이트
                $record->id = $existing->id;
                $record->updated_at = date('Y-m-d H:i:s');
                $this->db->update_record($this->table('analysis_results'), $record);
                error_log("[AItutor] 분석 결과 업데이트: {$analysisId}");
            } else {
                // 신규 저장
                $record->created_at = date('Y-m-d H:i:s');
                $record->updated_at = date('Y-m-d H:i:s');
                $this->db->insert_record($this->table('analysis_results'), $record);
                error_log("[AItutor] 분석 결과 저장: {$analysisId}");
            }
            
            return $analysisId;
        } catch (Exception $e) {
            error_log("[AItutor] 분석 결과 저장 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 분석 결과 조회
     * @param string $analysisId 분석 ID
     * @return array|null 분석 결과
     */
    public function getAnalysisResult($analysisId) {
        try {
            $record = $this->db->get_record($this->table('analysis_results'), [
                'analysis_id' => $analysisId
            ]);
            
            if ($record) {
                return $this->parseAnalysisRecord($record);
            }
            return null;
        } catch (Exception $e) {
            error_log("[AItutor] getAnalysisResult 오류: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * 학생의 분석 결과 목록 조회
     * @param int $studentId 학생 ID
     * @param int $limit 제한
     * @return array 분석 결과 목록
     */
    public function getStudentAnalysisResults($studentId, $limit = 20) {
        try {
            $records = $this->db->get_records(
                $this->table('analysis_results'),
                ['student_id' => $studentId],
                'created_at DESC',
                '*',
                0,
                $limit
            );
            
            $results = [];
            foreach ($records as $record) {
                $results[] = $this->parseAnalysisRecord($record);
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getStudentAnalysisResults 오류: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 분석 레코드 파싱
     */
    private function parseAnalysisRecord($record) {
        return [
            'id' => $record->id,
            'analysis_id' => $record->analysis_id,
            'student_id' => $record->student_id,
            'created_by' => $record->created_by,
            'text_content' => $record->text_content,
            'image_data' => $record->image_data,
            'dialogue_analysis' => $this->jsonDecode($record->dialogue_analysis),
            'comprehensive_questions' => $this->jsonDecode($record->comprehensive_questions),
            'detailed_questions' => $this->jsonDecode($record->detailed_questions),
            'teaching_rules' => $this->jsonDecode($record->teaching_rules),
            'ontology' => $this->jsonDecode($record->ontology),
            'rule_contents' => $this->jsonDecode($record->rule_contents),
            'metadata' => $this->jsonDecode($record->metadata),
            'created_at' => $record->created_at,
            'updated_at' => $record->updated_at
        ];
    }
    
    // =========================================
    // 상호작용 (interactions)
    // =========================================
    
    /**
     * 상호작용 저장
     */
    public function saveInteraction($interactionData) {
        try {
            $record = new stdClass();
            $record->interaction_id = $interactionData['interaction_id'] ?? 
                'INT_' . time() . '_' . mt_rand(1000, 9999);
            $record->analysis_id = $interactionData['analysis_id'] ?? null;
            $record->student_id = $interactionData['student_id'];
            $record->session_id = $interactionData['session_id'] ?? null;
            $record->user_input = $interactionData['user_input'];
            $record->response_text = $interactionData['response_text'] ?? '';
            $record->response_data = $this->jsonEncode($interactionData['response_data'] ?? null);
            $record->matched_rules = $this->jsonEncode($interactionData['matched_rules'] ?? []);
            $record->persona_id = $interactionData['persona_id'] ?? null;
            $record->intervention_id = $interactionData['intervention_id'] ?? null;
            $record->context_data = $this->jsonEncode($interactionData['context_data'] ?? null);
            $record->understanding_level = $interactionData['understanding_level'] ?? 'medium';
            $record->confidence = $interactionData['confidence'] ?? 0.50;
            $record->created_at = date('Y-m-d H:i:s');
            
            $id = $this->db->insert_record($this->table('interactions'), $record);
            return $record->interaction_id;
        } catch (Exception $e) {
            error_log("[AItutor] saveInteraction 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 상호작용 조회
     */
    public function getInteractions($studentId = null, $limit = 50) {
        try {
            $conditions = [];
            if ($studentId !== null) {
                $conditions['student_id'] = $studentId;
            }
            
            $records = $this->db->get_records(
                $this->table('interactions'),
                $conditions,
                'created_at DESC',
                '*',
                0,
                $limit
            );
            
            $results = [];
            foreach ($records as $record) {
                $results[] = [
                    'id' => $record->id,
                    'interaction_id' => $record->interaction_id,
                    'analysis_id' => $record->analysis_id,
                    'student_id' => $record->student_id,
                    'session_id' => $record->session_id,
                    'user_input' => $record->user_input,
                    'response_text' => $record->response_text,
                    'response_data' => $this->jsonDecode($record->response_data),
                    'matched_rules' => $this->jsonDecode($record->matched_rules),
                    'persona_id' => $record->persona_id,
                    'intervention_id' => $record->intervention_id,
                    'context_data' => $this->jsonDecode($record->context_data),
                    'understanding_level' => $record->understanding_level,
                    'confidence' => $record->confidence,
                    'created_at' => $record->created_at
                ];
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getInteractions 오류: " . $e->getMessage());
            return [];
        }
    }
    
    // =========================================
    // 생성된 룰 (generated_rules)
    // =========================================
    
    /**
     * 생성된 룰 저장
     */
    public function saveGeneratedRule($rule) {
        try {
            $record = new stdClass();
            $record->rule_id = $rule['rule_id'] ?? 'RULE_' . time() . '_' . mt_rand(1000, 9999);
            $record->analysis_id = $rule['analysis_id'] ?? null;
            $record->priority = $rule['priority'] ?? 50;
            $record->description = $rule['description'] ?? '';
            $record->conditions = $this->jsonEncode($rule['conditions'] ?? []);
            $record->actions = $this->jsonEncode($rule['actions'] ?? $rule['action'] ?? []);
            $record->confidence = $rule['confidence'] ?? 0.80;
            $record->rationale = $rule['rationale'] ?? '';
            $record->category = $rule['category'] ?? null;
            $record->is_active = $rule['is_active'] ?? 1;
            $record->metadata = $this->jsonEncode($rule['metadata'] ?? null);
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');
            
            $id = $this->db->insert_record($this->table('generated_rules'), $record);
            return $record->rule_id;
        } catch (Exception $e) {
            error_log("[AItutor] saveGeneratedRule 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 생성된 룰 조회
     */
    public function getGeneratedRules($ruleId = null, $priority = null) {
        try {
            if ($ruleId) {
                $record = $this->db->get_record($this->table('generated_rules'), [
                    'rule_id' => $ruleId
                ]);
                return $record ? [$this->parseRuleRecord($record)] : [];
            }
            
            $sql = "SELECT * FROM {" . $this->table('generated_rules') . "} WHERE is_active = 1";
            $params = [];
            
            if ($priority !== null) {
                $sql .= " AND priority >= ?";
                $params[] = $priority;
            }
            
            $sql .= " ORDER BY priority DESC";
            
            $records = $this->db->get_records_sql($sql, $params);
            
            $results = [];
            foreach ($records as $record) {
                $results[] = $this->parseRuleRecord($record);
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getGeneratedRules 오류: " . $e->getMessage());
            return [];
        }
    }
    
    private function parseRuleRecord($record) {
        return [
            'id' => $record->id,
            'rule_id' => $record->rule_id,
            'analysis_id' => $record->analysis_id,
            'priority' => $record->priority,
            'description' => $record->description,
            'conditions' => $this->jsonDecode($record->conditions),
            'actions' => $this->jsonDecode($record->actions),
            'confidence' => $record->confidence,
            'rationale' => $record->rationale,
            'category' => $record->category,
            'is_active' => $record->is_active,
            'metadata' => $this->jsonDecode($record->metadata),
            'created_at' => $record->created_at
        ];
    }
    
    // =========================================
    // 룰 컨텐츠 (rule_contents)
    // =========================================
    
    /**
     * 룰 컨텐츠 저장
     */
    public function saveRuleContent($content) {
        try {
            $record = new stdClass();
            $record->rule_id = $content['rule_id'];
            $record->content_type = $content['type'] ?? $content['content_type'] ?? 'verification';
            $record->title = $content['title'] ?? '';
            $record->content = $this->jsonEncode($content['content']);
            $record->metadata = $this->jsonEncode($content['metadata'] ?? null);
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');
            
            return $this->db->insert_record($this->table('rule_contents'), $record);
        } catch (Exception $e) {
            error_log("[AItutor] saveRuleContent 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 룰 컨텐츠 조회
     */
    public function getRuleContents($ruleId = null, $type = null) {
        try {
            $conditions = [];
            if ($ruleId) {
                $conditions['rule_id'] = $ruleId;
            }
            if ($type) {
                $conditions['content_type'] = $type;
            }
            
            $records = $this->db->get_records(
                $this->table('rule_contents'),
                $conditions,
                'created_at DESC'
            );
            
            $results = [];
            foreach ($records as $record) {
                $results[] = [
                    'id' => $record->id,
                    'rule_id' => $record->rule_id,
                    'type' => $record->content_type,
                    'title' => $record->title,
                    'content' => $this->jsonDecode($record->content),
                    'metadata' => $this->jsonDecode($record->metadata),
                    'created_at' => $record->created_at
                ];
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getRuleContents 오류: " . $e->getMessage());
            return [];
        }
    }
    
    // =========================================
    // 온톨로지 데이터 (ontology_data)
    // =========================================
    
    /**
     * 온톨로지 데이터 저장
     */
    public function saveOntologyData($nodeData) {
        try {
            $record = new stdClass();
            $record->node_id = $nodeData['node_id'] ?? 'NODE_' . time() . '_' . mt_rand(1000, 9999);
            $record->analysis_id = $nodeData['analysis_id'] ?? null;
            $record->node_class = $nodeData['class'] ?? $nodeData['node_class'] ?? '';
            $record->stage = $nodeData['stage'] ?? 'Context';
            $record->parent_id = $nodeData['parent_id'] ?? null;
            $record->properties = $this->jsonEncode($nodeData['properties'] ?? null);
            $record->metadata = $this->jsonEncode($nodeData['metadata'] ?? null);
            $record->created_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');
            
            return $this->db->insert_record($this->table('ontology_data'), $record);
        } catch (Exception $e) {
            error_log("[AItutor] saveOntologyData 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 온톨로지 데이터 조회
     */
    public function getOntologyData($nodeId = null, $nodeClass = null) {
        try {
            $conditions = [];
            if ($nodeId) {
                $conditions['node_id'] = $nodeId;
            }
            if ($nodeClass) {
                $conditions['node_class'] = $nodeClass;
            }
            
            $records = $this->db->get_records($this->table('ontology_data'), $conditions);
            
            $results = [];
            foreach ($records as $record) {
                $results[] = [
                    'id' => $record->id,
                    'node_id' => $record->node_id,
                    'analysis_id' => $record->analysis_id,
                    'class' => $record->node_class,
                    'stage' => $record->stage,
                    'parent_id' => $record->parent_id,
                    'properties' => $this->jsonDecode($record->properties),
                    'metadata' => $this->jsonDecode($record->metadata),
                    'created_at' => $record->created_at
                ];
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getOntologyData 오류: " . $e->getMessage());
            return [];
        }
    }
    
    // =========================================
    // 학생 컨텍스트 (student_contexts)
    // =========================================
    
    /**
     * 학생 컨텍스트 저장/업데이트
     */
    public function saveStudentContext($studentId, $contextData) {
        try {
            $existing = $this->db->get_record($this->table('student_contexts'), [
                'student_id' => $studentId
            ]);
            
            $record = new stdClass();
            $record->student_id = $studentId;
            $record->current_unit = $contextData['current_unit'] ?? null;
            $record->current_concept = $contextData['current_concept'] ?? null;
            $record->understanding_level = $contextData['understanding_level'] ?? 'medium';
            $record->concepts_learned = $this->jsonEncode($contextData['concepts_learned'] ?? $contextData['concepts'] ?? []);
            $record->concepts_struggling = $this->jsonEncode($contextData['concepts_struggling'] ?? []);
            $record->learning_style = $contextData['learning_style'] ?? null;
            $record->preferred_explanation = $contextData['preferred_explanation'] ?? null;
            $record->context_data = $this->jsonEncode($contextData['context_data'] ?? null);
            $record->last_activity_at = date('Y-m-d H:i:s');
            $record->updated_at = date('Y-m-d H:i:s');
            
            if ($existing) {
                $record->id = $existing->id;
                $record->session_count = $existing->session_count;
                $record->total_interactions = ($existing->total_interactions ?? 0) + 1;
                $this->db->update_record($this->table('student_contexts'), $record);
            } else {
                $record->session_count = 1;
                $record->total_interactions = 1;
                $record->created_at = date('Y-m-d H:i:s');
                $this->db->insert_record($this->table('student_contexts'), $record);
            }
            
            return true;
        } catch (Exception $e) {
            error_log("[AItutor] saveStudentContext 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 학생 컨텍스트 조회
     */
    public function getStudentContext($studentId) {
        try {
            $record = $this->db->get_record($this->table('student_contexts'), [
                'student_id' => $studentId
            ]);
            
            if ($record) {
                return [
                    'id' => $record->id,
                    'student_id' => $record->student_id,
                    'current_unit' => $record->current_unit,
                    'current_concept' => $record->current_concept,
                    'understanding_level' => $record->understanding_level,
                    'concepts' => $this->jsonDecode($record->concepts_learned),
                    'concepts_struggling' => $this->jsonDecode($record->concepts_struggling),
                    'learning_style' => $record->learning_style,
                    'preferred_explanation' => $record->preferred_explanation,
                    'context_data' => $this->jsonDecode($record->context_data),
                    'session_count' => $record->session_count,
                    'total_interactions' => $record->total_interactions,
                    'last_activity_at' => $record->last_activity_at
                ];
            }
            return null;
        } catch (Exception $e) {
            error_log("[AItutor] getStudentContext 오류: " . $e->getMessage());
            return null;
        }
    }
    
    // =========================================
    // 페르소나 (personas)
    // =========================================
    
    /**
     * 모든 페르소나 조회
     */
    public function getAllPersonas() {
        try {
            $records = $this->db->get_records(
                $this->table('personas'),
                ['is_active' => 1],
                'display_order ASC'
            );
            
            $results = [];
            foreach ($records as $record) {
                $results[] = $this->parsePersonaRecord($record);
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getAllPersonas 오류: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 페르소나 조회
     */
    public function getPersona($personaId) {
        try {
            $record = $this->db->get_record($this->table('personas'), [
                'persona_id' => $personaId
            ]);
            
            return $record ? $this->parsePersonaRecord($record) : null;
        } catch (Exception $e) {
            error_log("[AItutor] getPersona 오류: " . $e->getMessage());
            return null;
        }
    }
    
    private function parsePersonaRecord($record) {
        return [
            'id' => $record->id,
            'persona_id' => $record->persona_id,
            'name' => $record->name,
            'name_en' => $record->name_en,
            'description' => $record->description,
            'situation' => $record->situation,
            'behavior' => $record->behavior,
            'hidden_cause' => $record->hidden_cause,
            'intervention_strategy' => $this->jsonDecode($record->intervention_strategy),
            'trigger_patterns' => $this->jsonDecode($record->trigger_patterns),
            'recommended_interventions' => $this->jsonDecode($record->recommended_interventions),
            'is_active' => $record->is_active
        ];
    }
    
    // =========================================
    // 학생-페르소나 (student_personas)
    // =========================================
    
    /**
     * 학생 페르소나 매칭 저장
     */
    public function saveStudentPersonaMatch($studentId, $personaId, $matchScore, $patterns = null) {
        try {
            // 기존 current 비활성화
            $this->db->execute(
                "UPDATE {" . $this->table('student_personas') . "} SET is_current = 0 WHERE student_id = ?",
                [$studentId]
            );
            
            $record = new stdClass();
            $record->student_id = $studentId;
            $record->persona_id = $personaId;
            $record->match_score = $matchScore;
            $record->confidence = 0.50;
            $record->interaction_patterns = $this->jsonEncode($patterns);
            $record->is_current = 1;
            $record->matched_at = date('Y-m-d H:i:s');
            
            return $this->db->insert_record($this->table('student_personas'), $record);
        } catch (Exception $e) {
            error_log("[AItutor] saveStudentPersonaMatch 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 학생의 현재 페르소나 조회
     */
    public function getCurrentStudentPersona($studentId) {
        try {
            $record = $this->db->get_record($this->table('student_personas'), [
                'student_id' => $studentId,
                'is_current' => 1
            ]);
            
            if ($record) {
                return [
                    'persona_id' => $record->persona_id,
                    'match_score' => $record->match_score,
                    'confidence' => $record->confidence,
                    'matched_at' => $record->matched_at
                ];
            }
            return null;
        } catch (Exception $e) {
            error_log("[AItutor] getCurrentStudentPersona 오류: " . $e->getMessage());
            return null;
        }
    }
    
    // =========================================
    // 페르소나 스위칭 (persona_switches)
    // =========================================
    
    /**
     * 페르소나 스위칭 기록
     */
    public function savePersonaSwitch($switchData) {
        try {
            $record = new stdClass();
            $record->student_id = $switchData['student_id'];
            $record->from_persona_id = $switchData['from_persona_id'] ?? null;
            $record->to_persona_id = $switchData['to_persona_id'];
            $record->switch_reason = $switchData['switch_reason'] ?? '';
            $record->trigger_interaction_id = $switchData['trigger_interaction_id'] ?? null;
            $record->confidence_change = $switchData['confidence_change'] ?? null;
            $record->context_snapshot = $this->jsonEncode($switchData['context_snapshot'] ?? null);
            $record->switched_at = date('Y-m-d H:i:s');
            
            return $this->db->insert_record($this->table('persona_switches'), $record);
        } catch (Exception $e) {
            error_log("[AItutor] savePersonaSwitch 오류: " . $e->getMessage());
            return false;
        }
    }
    
    // =========================================
    // 개입 활동 (intervention_activities)
    // =========================================
    
    /**
     * 모든 개입 활동 조회
     */
    public function getAllInterventions() {
        try {
            $records = $this->db->get_records(
                $this->table('intervention_activities'),
                ['is_active' => 1],
                'category_order ASC, activity_order ASC'
            );
            
            $results = [];
            foreach ($records as $record) {
                $results[] = $this->parseInterventionRecord($record);
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getAllInterventions 오류: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 카테고리별 개입 활동 조회
     */
    public function getInterventionsByCategory($category) {
        try {
            $records = $this->db->get_records(
                $this->table('intervention_activities'),
                ['category' => $category, 'is_active' => 1],
                'activity_order ASC'
            );
            
            $results = [];
            foreach ($records as $record) {
                $results[] = $this->parseInterventionRecord($record);
            }
            return $results;
        } catch (Exception $e) {
            error_log("[AItutor] getInterventionsByCategory 오류: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * 개입 활동 조회
     */
    public function getIntervention($activityId) {
        try {
            $record = $this->db->get_record($this->table('intervention_activities'), [
                'activity_id' => $activityId
            ]);
            
            return $record ? $this->parseInterventionRecord($record) : null;
        } catch (Exception $e) {
            error_log("[AItutor] getIntervention 오류: " . $e->getMessage());
            return null;
        }
    }
    
    private function parseInterventionRecord($record) {
        return [
            'id' => $record->id,
            'activity_id' => $record->activity_id,
            'category' => $record->category,
            'category_order' => $record->category_order,
            'activity_order' => $record->activity_order,
            'name' => $record->name,
            'description' => $record->description,
            'trigger_signals' => $this->jsonDecode($record->trigger_signals),
            'persona_mapping' => $this->jsonDecode($record->persona_mapping),
            'priority' => $record->priority,
            'duration' => $record->duration,
            'method' => $record->method,
            'execution_count' => $record->execution_count,
            'success_rate' => $record->success_rate
        ];
    }
    
    // =========================================
    // 개입 활동 실행 기록 (intervention_executions)
    // =========================================
    
    /**
     * 개입 활동 실행 기록
     */
    public function saveInterventionExecution($executionData) {
        try {
            $record = new stdClass();
            $record->activity_id = $executionData['activity_id'];
            $record->student_id = $executionData['student_id'];
            $record->interaction_id = $executionData['interaction_id'] ?? null;
            $record->persona_id = $executionData['persona_id'] ?? null;
            $record->trigger_signal = $executionData['trigger_signal'] ?? '';
            $record->context_snapshot = $this->jsonEncode($executionData['context_snapshot'] ?? null);
            $record->response_type = $executionData['response_type'] ?? 'neutral';
            $record->effectiveness = $executionData['effectiveness'] ?? null;
            $record->notes = $executionData['notes'] ?? '';
            $record->executed_at = date('Y-m-d H:i:s');
            
            $id = $this->db->insert_record($this->table('intervention_executions'), $record);
            
            // 실행 횟수 증가
            $this->db->execute(
                "UPDATE {" . $this->table('intervention_activities') . "} SET execution_count = execution_count + 1 WHERE activity_id = ?",
                [$executionData['activity_id']]
            );
            
            return $id;
        } catch (Exception $e) {
            error_log("[AItutor] saveInterventionExecution 오류: " . $e->getMessage());
            return false;
        }
    }
    
    // =========================================
    // 세션 (sessions)
    // =========================================
    
    /**
     * 세션 시작
     */
    public function startSession($studentId, $analysisId = null) {
        try {
            $sessionId = 'SESSION_' . time() . '_' . mt_rand(1000, 9999);
            
            $record = new stdClass();
            $record->session_id = $sessionId;
            $record->student_id = $studentId;
            $record->analysis_id = $analysisId;
            $record->status = 'active';
            $record->started_at = date('Y-m-d H:i:s');
            
            // 현재 페르소나 가져오기
            $currentPersona = $this->getCurrentStudentPersona($studentId);
            if ($currentPersona) {
                $record->start_persona_id = $currentPersona['persona_id'];
            }
            
            $this->db->insert_record($this->table('sessions'), $record);
            
            return $sessionId;
        } catch (Exception $e) {
            error_log("[AItutor] startSession 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 세션 종료
     */
    public function endSession($sessionId) {
        try {
            $session = $this->db->get_record($this->table('sessions'), [
                'session_id' => $sessionId
            ]);
            
            if (!$session) {
                return false;
            }
            
            $started = strtotime($session->started_at);
            $duration = time() - $started;
            
            // 현재 페르소나 가져오기
            $currentPersona = $this->getCurrentStudentPersona($session->student_id);
            
            $record = new stdClass();
            $record->id = $session->id;
            $record->status = 'completed';
            $record->duration_seconds = $duration;
            $record->ended_at = date('Y-m-d H:i:s');
            
            if ($currentPersona) {
                $record->end_persona_id = $currentPersona['persona_id'];
            }
            
            $this->db->update_record($this->table('sessions'), $record);
            
            return true;
        } catch (Exception $e) {
            error_log("[AItutor] endSession 오류: " . $e->getMessage());
            return false;
        }
    }
    
    // =========================================
    // 필기 패턴 (writing_patterns)
    // =========================================
    
    /**
     * 필기 패턴 저장
     */
    public function saveWritingPattern($patternData) {
        try {
            $record = new stdClass();
            $record->pattern_id = $patternData['pattern_id'] ?? 'WP_' . time() . '_' . mt_rand(1000, 9999);
            $record->student_id = $patternData['student_id'];
            $record->session_id = $patternData['session_id'] ?? null;
            $record->pattern_type = $patternData['pattern_type'];
            $record->duration = $patternData['duration'] ?? null;
            $record->count = $patternData['count'] ?? 1;
            $record->confidence = $patternData['confidence'] ?? 0.50;
            $record->inferred_state = $patternData['inferred_state'] ?? null;
            $record->stroke_data = $this->jsonEncode($patternData['stroke_data'] ?? null);
            $record->position_data = $this->jsonEncode($patternData['position_data'] ?? null);
            $record->intervention_triggered = $patternData['intervention_triggered'] ?? null;
            $record->created_at = date('Y-m-d H:i:s');
            
            return $this->db->insert_record($this->table('writing_patterns'), $record);
        } catch (Exception $e) {
            error_log("[AItutor] saveWritingPattern 오류: " . $e->getMessage());
            return false;
        }
    }
    
    // =========================================
    // 비침습적 질문 (non_intrusive_questions)
    // =========================================
    
    /**
     * 비침습적 질문 저장
     */
    public function saveNonIntrusiveQuestion($questionData) {
        try {
            $record = new stdClass();
            $record->question_id = $questionData['question_id'] ?? 'NIQ_' . time() . '_' . mt_rand(1000, 9999);
            $record->student_id = $questionData['student_id'];
            $record->session_id = $questionData['session_id'] ?? null;
            $record->question_type = $questionData['question_type'];
            $record->content = $questionData['content'];
            $record->inferred_state = $questionData['inferred_state'] ?? null;
            $record->response_type = $questionData['response_type'] ?? 'no_response';
            $record->response_value = $questionData['response_value'] ?? null;
            $record->response_time = $questionData['response_time'] ?? null;
            $record->was_escalated = $questionData['was_escalated'] ?? 0;
            $record->escalation_level = $questionData['escalation_level'] ?? 0;
            $record->displayed_at = date('Y-m-d H:i:s');
            
            return $this->db->insert_record($this->table('non_intrusive_questions'), $record);
        } catch (Exception $e) {
            error_log("[AItutor] saveNonIntrusiveQuestion 오류: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 비침습적 질문 응답 업데이트
     */
    public function updateNonIntrusiveQuestionResponse($questionId, $responseData) {
        try {
            $question = $this->db->get_record($this->table('non_intrusive_questions'), [
                'question_id' => $questionId
            ]);
            
            if (!$question) {
                return false;
            }
            
            $record = new stdClass();
            $record->id = $question->id;
            $record->response_type = $responseData['response_type'];
            $record->response_value = $responseData['response_value'] ?? null;
            $record->response_time = $responseData['response_time'] ?? null;
            $record->responded_at = date('Y-m-d H:i:s');
            
            if (isset($responseData['was_escalated'])) {
                $record->was_escalated = $responseData['was_escalated'];
                $record->escalation_level = $responseData['escalation_level'] ?? 0;
            }
            
            $this->db->update_record($this->table('non_intrusive_questions'), $record);
            return true;
        } catch (Exception $e) {
            error_log("[AItutor] updateNonIntrusiveQuestionResponse 오류: " . $e->getMessage());
            return false;
        }
    }
    
    // =========================================
    // 유틸리티
    // =========================================
    
    /**
     * JSON 인코딩
     */
    private function jsonEncode($data) {
        if ($data === null) {
            return null;
        }
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * JSON 디코딩
     */
    private function jsonDecode($json) {
        if ($json === null || $json === '') {
            return null;
        }
        return json_decode($json, true);
    }
    
    /**
     * 통계 정보 조회
     */
    public function getStats() {
        $tables = [
            'analysis_results',
            'interactions',
            'generated_rules',
            'rule_contents',
            'ontology_data',
            'student_contexts',
            'personas',
            'student_personas',
            'intervention_activities',
            'intervention_executions',
            'sessions'
        ];
        
        $stats = [];
        foreach ($tables as $table) {
            try {
                $count = $this->db->count_records($this->table($table));
                $stats[$table] = ['count' => $count];
            } catch (Exception $e) {
                $stats[$table] = ['count' => 0, 'error' => $e->getMessage()];
            }
        }
        
        return $stats;
    }
    
    /**
     * Moodle DB 인스턴스 반환
     */
    public function getDB() {
        return $this->db;
    }
}
