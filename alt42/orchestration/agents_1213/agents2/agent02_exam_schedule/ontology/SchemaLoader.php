<?php
/**
 * SchemaLoader - 온톨로지 스키마 로더 및 검증기
 * File: agent02_exam_schedule/ontology/SchemaLoader.php
 * 
 * Agent02 전용 스키마 로더
 * - 온톨로지.jsonld 파일 로드 및 파싱
 * - 클래스/프로퍼티 정의 추출
 * - 인스턴스 생성 시 스키마 검증
 * - 타입 검증 (xsd:string, xsd:integer 등)
 */

class SchemaLoader {
    
    /** @var array JSON-LD 스키마 전체 */
    private $schema = null;
    
    /** @var array @context 섹션 */
    private $context = [];
    
    /** @var array @graph 섹션 (클래스/프로퍼티 정의) */
    private $graph = [];
    
    /** @var array 클래스별 정의 캐시 */
    private $classDefinitions = [];
    
    /** @var array 프로퍼티별 정의 캐시 */
    private $propertyDefinitions = [];
    
    /** @var array 인스턴스별 레이블 캐시 (동적 로드용) */
    private $instanceLabels = [];
    
    /** @var array 검증 오류 목록 */
    private $validationErrors = [];
    
    /** @var string 스키마 파일 경로 */
    private $schemaPath;
    
    /** @var string 네임스페이스 프리픽스 */
    private $prefix = 'mk:';
    
    /**
     * Constructor
     * 
     * @param string|null $schemaPath 온톨로지.jsonld 파일 경로 (null이면 기본 경로 사용)
     */
    public function __construct(?string $schemaPath = null) {
        $this->schemaPath = $schemaPath ?? __DIR__ . '/../온톨로지.jsonld';
        $this->loadSchema();
    }
    
    /**
     * 스키마 파일 로드 및 파싱
     * 
     * @throws Exception 파일 로드 실패 시
     */
    private function loadSchema(): void {
        if (!file_exists($this->schemaPath)) {
            throw new Exception("[SchemaLoader] 스키마 파일을 찾을 수 없습니다: {$this->schemaPath}");
        }
        
        $jsonContent = file_get_contents($this->schemaPath);
        if ($jsonContent === false) {
            throw new Exception("[SchemaLoader] 스키마 파일 읽기 실패: {$this->schemaPath}");
        }
        
        $this->schema = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("[SchemaLoader] JSON 파싱 오류: " . json_last_error_msg());
        }
        
        // @context와 @graph 추출
        $this->context = $this->schema['@context'] ?? [];
        $this->graph = $this->schema['@graph'] ?? [];
        
        // 클래스 및 프로퍼티 정의 캐싱
        $this->buildDefinitionCache();
        
        error_log("[SchemaLoader] 스키마 로드 완료: " . count($this->classDefinitions) . " 클래스, " . count($this->propertyDefinitions) . " 프로퍼티");
    }
    
    /**
     * 클래스/프로퍼티/인스턴스 정의 캐시 구축
     */
    private function buildDefinitionCache(): void {
        foreach ($this->graph as $node) {
            $id = $node['@id'] ?? null;
            $type = $node['@type'] ?? null;
            
            if (!$id) continue;
            
            // 클래스 정의 (owl:Class 또는 rdfs:Class)
            if ($type === 'owl:Class' || $type === 'rdfs:Class') {
                $this->classDefinitions[$id] = [
                    'id' => $id,
                    'label' => $node['rdfs:label'] ?? $id,
                    'comment' => $node['rdfs:comment'] ?? '',
                    'subClassOf' => $node['rdfs:subClassOf'] ?? null,
                    'type' => $type
                ];
            }
            // 인스턴스 정의
            elseif ($type !== null && strpos($type, 'mk:') === 0 && isset($node['rdfs:label'])) {
                $this->instanceLabels[$id] = [
                    'id' => $id,
                    'label' => $node['rdfs:label'],
                    'comment' => $node['rdfs:comment'] ?? '',
                    'type' => $type
                ];
            }
        }
        
        // @context에서 프로퍼티 정의 추출
        foreach ($this->context as $key => $value) {
            // 프로퍼티 정의는 객체 형태: { "@id": "...", "@type": "..." }
            if (is_array($value) && isset($value['@id'])) {
                $this->propertyDefinitions[$key] = [
                    'id' => $value['@id'],
                    'type' => $value['@type'] ?? null,
                    'shortName' => $key
                ];
            }
        }
        
        error_log("[SchemaLoader] 인스턴스 레이블 로드 완료: " . count($this->instanceLabels) . "개");
    }
    
    /**
     * 클래스 존재 여부 확인
     */
    public function classExists(string $classUri): bool {
        return isset($this->classDefinitions[$classUri]);
    }
    
    /**
     * 클래스 정의 조회
     */
    public function getClassDefinition(string $classUri): ?array {
        return $this->classDefinitions[$classUri] ?? null;
    }
    
    /**
     * 프로퍼티 정의 조회
     */
    public function getPropertyDefinition(string $propertyName): ?array {
        $shortName = str_replace($this->prefix, '', $propertyName);
        return $this->propertyDefinitions[$shortName] ?? null;
    }
    
    /**
     * 프로퍼티 타입 조회
     */
    public function getPropertyType(string $propertyName): ?string {
        $def = $this->getPropertyDefinition($propertyName);
        return $def['type'] ?? null;
    }
    
    /**
     * 인스턴스 데이터 검증
     */
    public function validateInstance(string $classUri, array $data): array {
        $this->validationErrors = [];
        
        if (!$this->classExists($classUri)) {
            $this->validationErrors[] = [
                'type' => 'class_not_found',
                'message' => "클래스가 스키마에 정의되어 있지 않습니다: {$classUri}",
                'class' => $classUri
            ];
            return ['valid' => false, 'errors' => $this->validationErrors];
        }
        
        foreach ($data as $property => $value) {
            if (strpos($property, '@') === 0) continue;
            $this->validatePropertyValue($property, $value);
        }
        
        return [
            'valid' => empty($this->validationErrors),
            'errors' => $this->validationErrors
        ];
    }
    
    /**
     * 프로퍼티 값 타입 검증
     */
    private function validatePropertyValue(string $property, $value): void {
        $propDef = $this->getPropertyDefinition($property);
        
        if (!$propDef) {
            $this->validationErrors[] = [
                'type' => 'property_not_in_schema',
                'level' => 'warning',
                'message' => "프로퍼티가 스키마에 정의되어 있지 않습니다: {$property}",
                'property' => $property
            ];
            return;
        }
        
        $expectedType = $propDef['type'] ?? null;
        if (!$expectedType) return;
        
        $isValid = $this->checkType($value, $expectedType);
        if (!$isValid) {
            $this->validationErrors[] = [
                'type' => 'type_mismatch',
                'message' => "타입 불일치: {$property}는 {$expectedType} 타입이어야 합니다",
                'property' => $property,
                'expectedType' => $expectedType,
                'actualValue' => $value
            ];
        }
    }
    
    /**
     * XSD 타입 검증
     */
    private function checkType($value, string $xsdType): bool {
        if ($value === null || $value === '') return true;
        
        switch ($xsdType) {
            case 'xsd:string':
                return is_string($value);
            case 'xsd:integer':
                return is_int($value) || (is_string($value) && ctype_digit($value));
            case 'xsd:boolean':
                return is_bool($value) || in_array($value, ['true', 'false', '1', '0'], true);
            case 'xsd:decimal':
            case 'xsd:float':
            case 'xsd:double':
                return is_numeric($value);
            case '@id':
                return is_string($value);
            default:
                return true;
        }
    }
    
    /**
     * 모든 클래스 목록 반환
     */
    public function getAllClasses(): array {
        return $this->classDefinitions;
    }
    
    /**
     * 모든 프로퍼티 목록 반환
     */
    public function getAllProperties(): array {
        return $this->propertyDefinitions;
    }
    
    /**
     * 모든 인스턴스 레이블 반환
     */
    public function getAllInstanceLabels(): array {
        return $this->instanceLabels;
    }
    
    /**
     * 특정 인스턴스의 레이블 조회
     */
    public function getInstanceLabel(string $instanceUri): string {
        if (strpos($instanceUri, 'mk:') !== 0 && strpos($instanceUri, ':') === false) {
            $instanceUri = 'mk:' . $instanceUri;
        }
        
        if (isset($this->instanceLabels[$instanceUri])) {
            return $this->instanceLabels[$instanceUri]['label'];
        }
        
        $shortName = str_replace('mk:', '', $instanceUri);
        return trim(preg_replace('/([A-Z])/', ' $1', $shortName));
    }
    
    /**
     * 클래스의 상위 클래스 조회
     */
    public function getSuperClass(string $classUri): ?string {
        $def = $this->getClassDefinition($classUri);
        return $def['subClassOf'] ?? null;
    }
    
    /**
     * 공식 변수 → 온톨로지 프로퍼티 매핑 테이블 (Agent02 전용)
     */
    public static function getOfficialVariableMapping(): array {
        return [
            // 시험 일정 관련
            'examName' => 'examName',
            'exam_name' => 'examName',
            'examStartDate' => 'examStartDate',
            'exam_start_date' => 'examStartDate',
            'examEndDate' => 'examEndDate',
            'exam_end_date' => 'examEndDate',
            'dDay' => 'dDay',
            'd_day' => 'dDay',
            'examSubject' => 'examSubject',
            'exam_subject' => 'examSubject',
            'examScope' => 'examScope',
            'exam_scope' => 'examScope',
            
            // 점수 관련
            'targetScore' => 'targetScore',
            'target_score' => 'targetScore',
            'currentExpectedScore' => 'currentExpectedScore',
            'current_expected_score' => 'currentExpectedScore',
            'academyRank' => 'academyRank',
            'academy_rank' => 'academyRank',
            'schoolScore' => 'schoolScore',
            'school_score' => 'schoolScore',
            
            // 진도 관련
            'academyProgress' => 'academyProgress',
            'academy_progress' => 'academyProgress',
            'schoolExamScope' => 'schoolExamScope',
            'school_exam_scope' => 'schoolExamScope',
            'progressGap' => 'progressGap',
            'progress_gap' => 'progressGap',
            
            // 시간 관련
            'academyAssignmentTime' => 'academyAssignmentTime',
            'academy_assignment_time' => 'academyAssignmentTime',
            'academyAssignmentFatigue' => 'academyAssignmentFatigue',
            'academy_assignment_fatigue' => 'academyAssignmentFatigue',
            'homeStudyRoutine' => 'homeStudyRoutine',
            'home_study_routine' => 'homeStudyRoutine',
            'availableTimeByDDay' => 'availableTimeByDDay',
            'available_time_by_d_day' => 'availableTimeByDDay',
            'academyTime' => 'academyTime',
            'academy_time' => 'academyTime',
            'schoolTime' => 'schoolTime',
            'school_time' => 'schoolTime',
            'homeTime' => 'homeTime',
            'home_time' => 'homeTime',
            
            // 단원/교재 관련
            'unitAccuracyRate' => 'unitAccuracyRate',
            'unit_accuracy_rate' => 'unitAccuracyRate',
            'textbookProgressRate' => 'textbookProgressRate',
            'textbook_progress_rate' => 'textbookProgressRate',
            'academyFeedbackData' => 'academyFeedbackData',
            'academy_feedback_data' => 'academyFeedbackData',
            
            // 전략 비율 관련
            'conceptRatio' => 'conceptRatio',
            'concept_ratio' => 'conceptRatio',
            'typeRatio' => 'typeRatio',
            'type_ratio' => 'typeRatio',
            'advancedRatio' => 'advancedRatio',
            'advanced_ratio' => 'advancedRatio',
            'pastExamRatio' => 'pastExamRatio',
            'past_exam_ratio' => 'pastExamRatio',
            
            // 루틴 관련
            'shortTermRoutine' => 'shortTermRoutine',
            'short_term_routine' => 'shortTermRoutine',
            'midTermRoutine' => 'midTermRoutine',
            'mid_term_routine' => 'midTermRoutine',
            'routineTransitionPoint' => 'routineTransitionPoint',
            'routine_transition_point' => 'routineTransitionPoint',
            
            // 효과 분석 관련
            'questionTypeSuccessRate' => 'questionTypeSuccessRate',
            'question_type_success_rate' => 'questionTypeSuccessRate',
            'textbookCoverage' => 'textbookCoverage',
            'textbook_coverage' => 'textbookCoverage',
            'examHitRate' => 'examHitRate',
            'exam_hit_rate' => 'examHitRate',
            'calculationErrorRatio' => 'calculationErrorRatio',
            'calculation_error_ratio' => 'calculationErrorRatio',
            'conceptErrorRatio' => 'conceptErrorRatio',
            'concept_error_ratio' => 'conceptErrorRatio',
            'routineMaintenanceRate' => 'routineMaintenanceRate',
            'routine_maintenance_rate' => 'routineMaintenanceRate',
            
            // 관계 프로퍼티
            'hasExamSchedule' => 'hasExamSchedule',
            'hasExamScope' => 'hasExamScope',
            'hasAcademyProgress' => 'hasAcademyProgress',
            'hasSchoolExamScope' => 'hasSchoolExamScope',
            'hasProgressGap' => 'hasProgressGap',
            'hasTimeResourceAllocation' => 'hasTimeResourceAllocation',
            'hasAlignmentPlan' => 'hasAlignmentPlan',
            'hasScoreImprovementPotential' => 'hasScoreImprovementPotential',
            'hasStrategyRatio' => 'hasStrategyRatio',
            'hasUnitPriority' => 'hasUnitPriority',
            'hasExamCycleImprovement' => 'hasExamCycleImprovement',
            
            // 추천 프로퍼티
            'recommendsAlignmentStrategy' => 'recommendsAlignmentStrategy',
            'recommendsTimeAllocation' => 'recommendsTimeAllocation',
            'recommendsStrategyRatio' => 'recommendsStrategyRatio',
            'recommendsUnitPriority' => 'recommendsUnitPriority',
            'recommendsRoutineSchedule' => 'recommendsRoutineSchedule',
            'recommendsImprovementStrategy' => 'recommendsImprovementStrategy'
        ];
    }
    
    /**
     * 컨텍스트 변수명을 온톨로지 프로퍼티명으로 변환
     */
    public function mapContextToOntology(string $contextKey): ?string {
        $mapping = self::getOfficialVariableMapping();
        return $mapping[$contextKey] ?? null;
    }
    
    /**
     * 스키마 파일 경로 반환
     */
    public function getSchemaPath(): string {
        return $this->schemaPath;
    }
    
    /**
     * 스키마가 로드되었는지 확인
     */
    public function isLoaded(): bool {
        return $this->schema !== null;
    }
}

