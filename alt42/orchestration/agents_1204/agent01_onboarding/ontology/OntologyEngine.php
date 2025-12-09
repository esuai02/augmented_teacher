<?php
/**
 * Agent01 온톨로지 엔진
 * File: agent01_onboarding/ontology/OntologyEngine.php
 * 
 * 온톨로지 인스턴스 생성, 추론, 전략 생성 기능 제공
 */

// Moodle config는 이미 로드되어 있다고 가정 (호출하는 쪽에서 로드)
// include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

class OntologyEngine {
    
    private $db;
    private $namespace = 'https://mathking.kr/ontology/mathking/';
    private $prefix = 'mk:';
    
    /**
     * Constructor
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
        $this->ensureTableExists();
    }
    
    /**
     * 온톨로지 인스턴스 저장 테이블 생성
     */
    private function ensureTableExists() {
        $dbman = $this->db->get_manager();
        
        $table = new xmldb_table('alt42_ontology_instances');
        
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('instance_id', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('class_type', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
            $table->add_field('jsonld_data', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
            $table->add_field('stage', XMLDB_TYPE_CHAR, '50', null, null, null, null);
            $table->add_field('parent_instance_id', XMLDB_TYPE_CHAR, '255', null, null, null, null);
            $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            $table->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
            
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_index('instance_id_idx', XMLDB_INDEX_UNIQUE, ['instance_id']);
            $table->add_index('student_id_idx', XMLDB_INDEX_NOTUNIQUE, ['student_id']);
            $table->add_index('class_type_idx', XMLDB_INDEX_NOTUNIQUE, ['class_type']);
            
            $dbman->create_table($table);
        }
    }
    
    /**
     * 온톨로지 인스턴스 생성
     * 
     * @param string $class 클래스 URI (예: 'mk:OnboardingContext')
     * @param array $properties 프로퍼티 배열
     * @param int|null $studentId 학생 ID
     * @param array|null $context 컨텍스트 (변수 치환용)
     * @return string 생성된 인스턴스 ID
     * @throws Exception
     */
    public function createInstance(string $class, array $properties = [], ?int $studentId = null, ?array $context = null): string {
        try {
            // 학생 ID 확인
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
                if ($studentId === null) {
                    throw new Exception("student_id가 필요합니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // 클래스명 추출
            $className = str_replace($this->prefix, '', $class);
            if (strpos($className, ':') !== false) {
                $className = substr($className, strpos($className, ':') + 1);
            }
            
            // 인스턴스 ID 생성
            $instanceId = $this->prefix . $className . '/instance_' . uniqid();
            
            // Stage 확인
            $stage = $this->getStageForClass($class);
            
            // JSON-LD 데이터 구성
            $jsonld = [
                '@id' => $instanceId,
                '@type' => $class,
                'mk:hasStage' => $stage
            ];
            
            // 프로퍼티 추가 (변수 치환 포함)
            foreach ($properties as $key => $value) {
                // 변수 치환 (예: {gradeLevel} → 실제 값)
                if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                    $varName = $matches[1];
                    $resolvedValue = $this->resolveVariable($varName, $context);
                    if ($resolvedValue !== '') {
                        $value = $resolvedValue;
                        error_log("[OntologyEngine] Resolved {$varName} = {$value} during createInstance [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    } else {
                        // 값이 없으면 빈 문자열로 설정 (나중에 setProperty로 채울 수 있도록)
                        $value = '';
                    }
                }
                // 빈 값이 아닌 경우에만 추가
                if ($value !== null && $value !== '') {
                    $jsonld[$key] = $value;
                }
            }
            
            // 데이터베이스에 저장
            $record = new stdClass();
            $record->instance_id = $instanceId;
            $record->student_id = $studentId;
            $record->class_type = $class;
            $record->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $record->stage = $stage;
            $record->parent_instance_id = null;
            $record->created_at = time();
            $record->updated_at = time();
            
            $this->db->insert_record('alt42_ontology_instances', $record);
            
            error_log("[OntologyEngine] Created instance: {$instanceId} for student: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $instanceId;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error creating instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 프로퍼티 설정
     * 
     * @param string $instanceId 인스턴스 ID
     * @param string $property 프로퍼티 URI
     * @param mixed $value 값
     * @param array|null $context 변수 치환을 위한 컨텍스트
     * @throws Exception
     */
    public function setProperty(string $instanceId, string $property, $value, ?array $context = null): void {
        try {
            // 인스턴스 조회
            $instance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $instanceId]);
            if (!$instance) {
                throw new Exception("Instance not found: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // JSON-LD 파싱
            $jsonld = json_decode($instance->jsonld_data, true);
            if (!$jsonld) {
                throw new Exception("Invalid JSON-LD data for instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // 값 치환 (변수 처리)
            if (is_string($value) && preg_match('/\{(\w+)\}/', $value, $matches)) {
                $varName = $matches[1];
                // resolveVariable을 통해 컨텍스트에서 값 추출
                $resolvedValue = $this->resolveVariable($varName, $context);
                if ($resolvedValue !== '') {
                    $value = $resolvedValue;
                    error_log("[OntologyEngine] Resolved {$varName} = {$value} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[OntologyEngine] Warning: Could not resolve variable {$varName}, keeping placeholder [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    // 값이 없으면 빈 문자열로 설정 (나중에 채울 수 있도록)
                    $value = '';
                }
            }
            
            // 프로퍼티 설정
            $jsonld[$property] = $value;
            
            // 업데이트
            $instance->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $instance->updated_at = time();
            $this->db->update_record('alt42_ontology_instances', $instance);
            
            error_log("[OntologyEngine] Set property {$property} = " . (is_array($value) ? json_encode($value) : $value) . " for instance: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error setting property: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 의미 기반 추론
     * 
     * @param string $class 추론할 클래스 URI
     * @param string|null $instanceId 특정 인스턴스 ID (null이면 전체)
     * @param int|null $studentId 학생 ID
     * @return array 추론 결과
     */
    public function reasonOver(string $class, ?string $instanceId = null, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            $conditions = ['class_type' => $class];
            if ($instanceId !== null) {
                $conditions['instance_id'] = $instanceId;
            }
            if ($studentId !== null) {
                $conditions['student_id'] = $studentId;
            }
            
            $instances = $this->db->get_records('alt42_ontology_instances', $conditions);
            
            $results = [];
            foreach ($instances as $instance) {
                $jsonld = json_decode($instance->jsonld_data, true);
                if (!$jsonld) {
                    continue;
                }
                
                // 추론 로직 적용
                $reasoningResult = $this->applyReasoningRules($class, $jsonld, $studentId);
                $results[] = [
                    'instance_id' => $instance->instance_id,
                    'data' => $jsonld,
                    'reasoning' => $reasoningResult
                ];
            }
            
            error_log("[OntologyEngine] Reasoned over {$class}, found " . count($results) . " instances [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return $results;
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error reasoning: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return [];
        }
    }
    
    /**
     * 전략 생성
     * 
     * @param string $strategyClass 전략 클래스 URI
     * @param array $context 컨텍스트 정보
     * @param int|null $studentId 학생 ID
     * @return array 생성된 전략 객체
     */
    public function generateStrategy(string $strategyClass, array $context, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            // 관련 Context 인스턴스 조회
            $onboardingContexts = $this->reasonOver('mk:OnboardingContext', null, $studentId);
            $learningContexts = $this->reasonOver('mk:LearningContextIntegration', null, $studentId);
            
            // 전략 생성 로직
            $strategy = $this->buildStrategy($strategyClass, $onboardingContexts, $learningContexts, $context);
            
            // 인스턴스로 저장 (컨텍스트 전달하여 변수 치환 가능하도록)
            $instanceId = $this->createInstance($strategyClass, $strategy, $studentId, $context);
            
            // 부모 관계 설정
            if (!empty($onboardingContexts)) {
                $parentId = $onboardingContexts[0]['instance_id'];
                $this->setParentRelation($instanceId, $parentId);
            }
            
            error_log("[OntologyEngine] Generated strategy: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'strategy' => $strategy
            ];
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error generating strategy: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 절차 생성
     * 
     * @param string $procedureClass 절차 클래스 URI
     * @param string $strategyId 전략 인스턴스 ID
     * @param int|null $studentId 학생 ID
     * @return array 생성된 절차 객체
     */
    public function generateProcedure(string $procedureClass, string $strategyId, ?int $studentId = null): array {
        try {
            if ($studentId === null) {
                global $USER;
                $studentId = $USER->id ?? null;
            }
            
            // 전략 인스턴스 조회
            $strategyInstance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $strategyId]);
            if (!$strategyInstance) {
                throw new Exception("Strategy instance not found: {$strategyId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            $strategyJsonld = json_decode($strategyInstance->jsonld_data, true);
            
            // 절차 단계 생성
            $procedureSteps = $this->buildProcedureSteps($strategyJsonld);
            
            // 절차 인스턴스 생성
            $procedureData = [
                'mk:hasProcedureSteps' => $procedureSteps
            ];
            
            $instanceId = $this->createInstance($procedureClass, $procedureData, $studentId, null);
            
            // 부모 관계 설정
            $this->setParentRelation($instanceId, $strategyId);
            
            error_log("[OntologyEngine] Generated procedure: {$instanceId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            return [
                'instance_id' => $instanceId,
                'procedure_steps' => $procedureSteps
            ];
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error generating procedure: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw $e;
        }
    }
    
    /**
     * 인스턴스 조회
     * 
     * @param string $instanceId 인스턴스 ID
     * @return array|null JSON-LD 데이터
     */
    public function getInstance(string $instanceId): ?array {
        try {
            $instance = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $instanceId]);
            if (!$instance) {
                return null;
            }
            
            return json_decode($instance->jsonld_data, true);
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error getting instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    // === Private Helper Methods ===
    
    /**
     * 클래스에 대한 Stage 반환
     */
    private function getStageForClass(string $class): string {
        $stageMap = [
            'mk:OnboardingContext' => 'Context',
            'mk:LearningContextIntegration' => 'Context',
            'mk:FirstClassDecisionModel' => 'Decision',
            'mk:FirstClassStrategy' => 'Decision',
            'mk:FirstClassExecutionPlan' => 'Execution',
            'mk:LessonProcedure' => 'Execution'
        ];
        
        return $stageMap[$class] ?? 'Context';
    }
    
    /**
     * 변수 해석 (강화된 버전)
     * 
     * 변수명을 컨텍스트 키로 매핑하여 실제 값 추출
     */
    private function resolveVariable(string $varName, ?array $context): string {
        if (!$context) {
            return '';
        }
        
        // 직접 매칭 시도
        if (isset($context[$varName])) {
            $value = $context[$varName];
            return $value !== null ? (string)$value : '';
        }
        
        // 변수명 → 컨텍스트 키 매핑 테이블
        $variableMapping = [
            // OnboardingContext 프로퍼티 매핑
            'gradeLevel' => ['student_grade', 'grade_level', 'grade'],
            'schoolName' => ['school_name', 'school'],
            'academyName' => ['academy_name', 'academy'],
            'academyGrade' => ['academy_grade', 'academy_grade_level'],
            
            // LearningContextIntegration 프로퍼티 매핑
            'concept_progress' => ['concept_progress', 'conceptProgress'],
            'advanced_progress' => ['advanced_progress', 'advancedProgress'],
            'math_unit_mastery' => ['math_unit_mastery', 'unit_mastery', 'unitMastery'],
            'current_progress_position' => ['current_progress_position', 'currentPosition', 'current_position'],
            
            // FirstClassStrategy 프로퍼티 매핑
            'math_learning_style' => ['math_learning_style', 'mathLearningStyle', 'learning_style'],
            'study_style' => ['study_style', 'studyStyle'],
            'exam_style' => ['exam_style', 'examStyle'],
            'math_confidence' => ['math_confidence', 'mathConfidence', 'confidence'],
            'math_level' => ['math_level', 'mathLevel', 'level'],
            'math_stress_level' => ['math_stress_level', 'mathStressLevel', 'stress_level']
        ];
        
        // 매핑 테이블에서 찾기
        if (isset($variableMapping[$varName])) {
            foreach ($variableMapping[$varName] as $contextKey) {
                if (isset($context[$contextKey])) {
                    $value = $context[$contextKey];
                    if ($value !== null && $value !== '') {
                        error_log("[OntologyEngine] Resolved variable {$varName} → {$contextKey} = " . (is_array($value) ? json_encode($value) : $value) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
                    }
                }
            }
        }
        
        // snake_case 변환 시도 (예: gradeLevel → grade_level)
        $snakeCase = strtolower(preg_replace('/([A-Z])/', '_$1', $varName));
        if (isset($context[$snakeCase])) {
            $value = $context[$snakeCase];
            if ($value !== null && $value !== '') {
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            }
        }
        
        // camelCase 변환 시도 (예: grade_level → gradeLevel)
        $camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $varName))));
        if ($camelCase !== $varName && isset($context[$camelCase])) {
            $value = $context[$camelCase];
            if ($value !== null && $value !== '') {
                return is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;
            }
        }
        
        error_log("[OntologyEngine] Could not resolve variable: {$varName} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return '';
    }
    
    /**
     * 추론 규칙 적용
     */
    private function applyReasoningRules(string $class, array $jsonld, ?int $studentId): array {
        $result = [];
        
        if ($class === 'mk:LearningContextIntegration') {
            // 진도 기반 단원 추천 추론
            $conceptProgress = $jsonld['mk:hasConceptProgress'] ?? null;
            $advancedProgress = $jsonld['mk:hasAdvancedProgress'] ?? null;
            $unitMastery = $jsonld['mk:hasUnitMastery'] ?? null;
            $currentPosition = $jsonld['mk:hasCurrentPosition'] ?? null;
            
            if ($conceptProgress || $advancedProgress) {
                // 단원 추천 추론
                $recommendedUnits = $this->inferRecommendedUnits($conceptProgress, $advancedProgress, $unitMastery, $currentPosition);
                if (!empty($recommendedUnits)) {
                    $result['recommendsUnits'] = $recommendedUnits;
                }
                
                // 내용 범위 추론
                $contentRange = $this->inferContentRange($conceptProgress, $advancedProgress);
                if ($contentRange) {
                    $result['recommendsContentRange'] = $contentRange;
                }
                
                // 정렬 전략 추론
                $academyProgress = $jsonld['mk:hasAcademyProgress'] ?? null;
                $curriculumAlignment = $jsonld['mk:hasCurriculumAlignment'] ?? null;
                if ($academyProgress || $curriculumAlignment) {
                    $alignmentStrategy = $this->inferAlignmentStrategy($conceptProgress, $academyProgress, $curriculumAlignment);
                    if ($alignmentStrategy) {
                        $result['recommendsAlignmentStrategy'] = $alignmentStrategy;
                    }
                }
            }
        }
        
        if ($class === 'mk:OnboardingContext') {
            // 온보딩 컨텍스트 기반 추론
            $mathConfidence = $jsonld['mk:hasMathConfidence'] ?? null;
            $mathLevel = $jsonld['mk:hasMathLevel'] ?? null;
            $mathLearningStyle = $jsonld['mk:hasMathLearningStyle'] ?? null;
            
            // 난이도 추천 추론
            if ($mathConfidence !== null && $mathLevel) {
                $difficulty = $this->inferDifficultyLevel($mathConfidence, $mathLevel);
                if ($difficulty) {
                    $result['recommendsDifficulty'] = $difficulty;
                }
            }
            
            // 진도 추천 추론
            if ($mathLevel && $mathLearningStyle) {
                $progress = $this->inferProgressRecommendation($mathLevel, $mathLearningStyle);
                if ($progress) {
                    $result['recommendsProgress'] = $progress;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * 추천 단원 추론 (강화된 로직)
     */
    private function inferRecommendedUnits(?string $conceptProgress, ?string $advancedProgress, ?string $unitMastery, ?string $currentPosition): array {
        $recommended = [];
        
        // 개념 진도 파싱 (예: "중2-1 일차방정식까지")
        if ($conceptProgress) {
            // 학년-학기 추출
            if (preg_match('/(중|고)\s*(\d)-(\d)/', $conceptProgress, $matches)) {
                $grade = $matches[1] . $matches[2];
                $semester = $matches[3];
                
                // 현재 단원 추출
                $currentUnit = null;
                if (preg_match('/(일차방정식|이차방정식|일차함수|이차함수|삼각형|사각형|원|통계|확률)/', $conceptProgress, $unitMatches)) {
                    $currentUnit = $unitMatches[1];
                }
                
                // 다음 단원 추천
                $unitSequence = [
                    '중1' => ['정수와 유리수', '문자와 식', '일차방정식', '좌표평면과 그래프', '일차함수'],
                    '중2' => ['유리수와 순환소수', '식의 계산', '일차부등식', '연립일차방정식', '일차함수', '이차함수'],
                    '중3' => ['제곱근과 실수', '인수분해', '이차방정식', '이차함수', '삼각비', '원의 성질']
                ];
                
                if (isset($unitSequence[$grade])) {
                    $units = $unitSequence[$grade];
                    $currentIndex = $currentUnit ? array_search($currentUnit, $units) : -1;
                    
                    // 다음 2-3개 단원 추천
                    for ($i = $currentIndex + 1; $i < min($currentIndex + 4, count($units)); $i++) {
                        if (isset($units[$i])) {
                            $recommended[] = $units[$i];
                        }
                    }
                }
            }
        }
        
        // 심화 진도가 있으면 심화 단원도 추가
        if ($advancedProgress && !empty($recommended)) {
            $recommended[] = $recommended[0] . ' (심화)';
        }
        
        // 단원 마스터리 기반 보완 추천
        if ($unitMastery) {
            if (preg_match('/(\w+)\s*(미이수|부족|보통)/', $unitMastery, $masteryMatches)) {
                $weakUnit = $masteryMatches[1];
                if (!in_array($weakUnit, $recommended)) {
                    array_unshift($recommended, $weakUnit . ' (보완 필요)');
                }
            }
        }
        
        return !empty($recommended) ? $recommended : ['일차함수', '이차함수']; // 기본값
    }
    
    /**
     * 내용 범위 추론
     */
    private function inferContentRange(?string $conceptProgress, ?string $advancedProgress): ?string {
        if (!$conceptProgress) {
            return null;
        }
        
        // 기본 범위: 개념 진도 기준
        $range = $conceptProgress;
        
        // 심화 진도가 있으면 확장
        if ($advancedProgress) {
            $range .= ' + ' . $advancedProgress . ' (심화)';
        }
        
        return $range;
    }
    
    /**
     * 정렬 전략 추론
     */
    private function inferAlignmentStrategy(?string $conceptProgress, ?string $academyProgress, ?string $curriculumAlignment): ?string {
        if (!$conceptProgress || !$academyProgress) {
            return null;
        }
        
        // 학원 진도가 학교보다 빠른지 확인
        if (strpos($curriculumAlignment, '빠름') !== false || strpos($curriculumAlignment, '앞서') !== false) {
            return '학원 진도에 맞춰 학교 진도 보완';
        } elseif (strpos($curriculumAlignment, '느림') !== false || strpos($curriculumAlignment, '뒤처') !== false) {
            return '학교 진도에 맞춰 학원 진도 보완';
        } else {
            return '학교-학원 진도 정렬 유지';
        }
    }
    
    /**
     * 난이도 수준 추론
     */
    private function inferDifficultyLevel(int $mathConfidence, string $mathLevel): ?string {
        // 자신감 기반 난이도 추천
        if ($mathConfidence <= 3) {
            return 'Easy';
        } elseif ($mathConfidence <= 6) {
            return 'EasyToMedium';
        } elseif ($mathConfidence <= 8) {
            return 'Medium';
        } else {
            return 'MediumToHard';
        }
    }
    
    /**
     * 진도 추천 추론
     */
    private function inferProgressRecommendation(string $mathLevel, string $mathLearningStyle): ?string {
        // 수준과 학습 스타일 기반 진도 추천
        $baseProgress = $mathLevel;
        
        if ($mathLearningStyle === '계산형') {
            return $baseProgress . ' (계산 중심)';
        } elseif ($mathLearningStyle === '개념형') {
            return $baseProgress . ' (개념 중심)';
        } else {
            return $baseProgress . ' (균형)';
        }
    }
    
    /**
     * 전략 구축
     */
    private function buildStrategy(string $strategyClass, array $onboardingContexts, array $learningContexts, array $context): array {
        $strategy = [];
        
        // OnboardingContext에서 데이터 추출
        if (!empty($onboardingContexts)) {
            $onboardingData = $onboardingContexts[0]['data'];
            $strategy['mk:hasMathLearningStyle'] = $onboardingData['mk:hasMathLearningStyle'] ?? null;
            $strategy['mk:hasStudyStyle'] = $onboardingData['mk:hasStudyStyle'] ?? null;
            $strategy['mk:hasMathConfidence'] = $onboardingData['mk:hasMathConfidence'] ?? null;
        }
        
        // LearningContextIntegration에서 데이터 추출
        if (!empty($learningContexts)) {
            $learningData = $learningContexts[0]['data'];
            $reasoning = $learningContexts[0]['reasoning'] ?? [];
            if (isset($reasoning['recommendsUnits'])) {
                $strategy['mk:recommendsUnits'] = $reasoning['recommendsUnits'];
            }
        }
        
        // 컨텍스트에서 추가 데이터
        foreach ($context as $key => $value) {
            if (strpos($key, 'mk:') === 0) {
                $strategy[$key] = $value;
            }
        }
        
        return $strategy;
    }
    
    /**
     * 절차 단계 구축
     */
    private function buildProcedureSteps(array $strategyJsonld): array {
        $steps = [];
        
        // 전략에서 절차 단계 생성
        $stepOrder = 1;
        
        // 도입 단계
        if (isset($strategyJsonld['mk:recommendsIntroductionRoutine'])) {
            $steps[] = [
                '@type' => 'mk:ProcedureStep',
                'mk:stepOrder' => $stepOrder++,
                'mk:stepType' => 'introduction',
                'mk:stepDescription' => '도입: ' . $strategyJsonld['mk:recommendsIntroductionRoutine']
            ];
        }
        
        // 설명 단계
        if (isset($strategyJsonld['mk:recommendsExplanationStrategy'])) {
            $steps[] = [
                '@type' => 'mk:ProcedureStep',
                'mk:stepOrder' => $stepOrder++,
                'mk:stepType' => 'explanation',
                'mk:stepDescription' => '설명: ' . $strategyJsonld['mk:recommendsExplanationStrategy']
            ];
        }
        
        // 연습 단계
        $steps[] = [
            '@type' => 'mk:ProcedureStep',
            'mk:stepOrder' => $stepOrder++,
            'mk:stepType' => 'practice',
            'mk:stepDescription' => '연습: 기본 문제 풀이'
        ];
        
        // 피드백 단계
        if (isset($strategyJsonld['mk:recommendsFeedbackTone'])) {
            $steps[] = [
                '@type' => 'mk:ProcedureStep',
                'mk:stepOrder' => $stepOrder++,
                'mk:stepType' => 'feedback',
                'mk:stepDescription' => '피드백: ' . $strategyJsonld['mk:recommendsFeedbackTone']
            ];
        }
        
        return $steps;
    }
    
    /**
     * 부모 관계 설정
     */
    public function setParentRelation(string $childId, string $parentId): void {
        try {
            $child = $this->db->get_record('alt42_ontology_instances', ['instance_id' => $childId]);
            if (!$child) {
                return;
            }
            
            $jsonld = json_decode($child->jsonld_data, true);
            $jsonld['mk:hasParent'] = $parentId;
            
            $child->jsonld_data = json_encode($jsonld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $child->parent_instance_id = $parentId;
            $child->updated_at = time();
            
            $this->db->update_record('alt42_ontology_instances', $child);
            
        } catch (Exception $e) {
            error_log("[OntologyEngine] Error setting parent relation: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
    }
}

