<?php
/**
 * SchemaLoader - 온톨로지 스키마 로더 및 검증기
 * File: agent01_onboarding/ontology/SchemaLoader.php
 * 
 * 체크포인트 1: 스키마 기반 검증 레이어
 * - 온톨로지.jsonld 파일 로드 및 파싱
 * - 클래스/프로퍼티 정의 추출
 * - 인스턴스 생성 시 스키마 검증
 * - 타입 검증 (xsd:string, xsd:integer 등)
 * 
 * 설계 원칙:
 * - 스키마(검증) 레이어와 도메인 추론 레이어 분리
 * - 스키마 로더는 검증만 담당, 추론 로직은 Engine에서 처리
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
            // [동적 로드] 인스턴스 정의 (mk:로 시작하고 rdfs:label이 있는 것)
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
     * 
     * @param string $classUri 클래스 URI (예: 'mk:OnboardingContext')
     * @return bool
     */
    public function classExists(string $classUri): bool {
        return isset($this->classDefinitions[$classUri]);
    }
    
    /**
     * 클래스 정의 조회
     * 
     * @param string $classUri 클래스 URI
     * @return array|null 클래스 정의 또는 null
     */
    public function getClassDefinition(string $classUri): ?array {
        return $this->classDefinitions[$classUri] ?? null;
    }
    
    /**
     * 프로퍼티 정의 조회
     * 
     * @param string $propertyName 프로퍼티 이름 (예: 'gradeLevel' 또는 'mk:gradeLevel')
     * @return array|null 프로퍼티 정의 또는 null
     */
    public function getPropertyDefinition(string $propertyName): ?array {
        // mk: 프리픽스 제거
        $shortName = str_replace($this->prefix, '', $propertyName);
        return $this->propertyDefinitions[$shortName] ?? null;
    }
    
    /**
     * 프로퍼티 타입 조회
     * 
     * @param string $propertyName 프로퍼티 이름
     * @return string|null XSD 타입 (예: 'xsd:string', 'xsd:integer')
     */
    public function getPropertyType(string $propertyName): ?string {
        $def = $this->getPropertyDefinition($propertyName);
        return $def['type'] ?? null;
    }
    
    /**
     * 인스턴스 데이터 검증
     * 
     * @param string $classUri 클래스 URI
     * @param array $data 인스턴스 데이터
     * @return array ['valid' => bool, 'errors' => array]
     */
    public function validateInstance(string $classUri, array $data): array {
        $this->validationErrors = [];
        
        // 1. 클래스 존재 확인
        if (!$this->classExists($classUri)) {
            $this->validationErrors[] = [
                'type' => 'class_not_found',
                'message' => "클래스가 스키마에 정의되어 있지 않습니다: {$classUri}",
                'class' => $classUri
            ];
            return ['valid' => false, 'errors' => $this->validationErrors];
        }
        
        // 2. 프로퍼티별 타입 검증
        foreach ($data as $property => $value) {
            // 메타 프로퍼티는 스킵 (@id, @type 등)
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
     * 
     * @param string $property 프로퍼티 이름
     * @param mixed $value 값
     */
    private function validatePropertyValue(string $property, $value): void {
        $propDef = $this->getPropertyDefinition($property);
        
        // 프로퍼티가 스키마에 없으면 경고 (오류는 아님)
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
        if (!$expectedType) return; // 타입 정의 없으면 스킵
        
        // 타입 검증
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
     * 
     * @param mixed $value 값
     * @param string $xsdType XSD 타입 (예: 'xsd:string', 'xsd:integer', '@id')
     * @return bool
     */
    private function checkType($value, string $xsdType): bool {
        // null 값은 허용 (optional 필드)
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
                // @id 타입은 URI 참조 (문자열이면 OK)
                return is_string($value);
                
            default:
                // 알 수 없는 타입은 통과
                return true;
        }
    }
    
    /**
     * 모든 클래스 목록 반환
     * 
     * @return array 클래스 정의 배열
     */
    public function getAllClasses(): array {
        return $this->classDefinitions;
    }
    
    /**
     * 모든 프로퍼티 목록 반환
     * 
     * @return array 프로퍼티 정의 배열
     */
    public function getAllProperties(): array {
        return $this->propertyDefinitions;
    }
    
    /**
     * [동적 로드] 모든 인스턴스 레이블 반환
     * 
     * 온톨로지.jsonld의 @graph에서 로드된 인스턴스들의 레이블
     * 
     * @return array 인스턴스 레이블 배열 ['mk:SupportiveIntroduction' => ['label' => '격려형 도입', ...], ...]
     */
    public function getAllInstanceLabels(): array {
        return $this->instanceLabels;
    }
    
    /**
     * [동적 로드] 특정 인스턴스의 레이블 조회
     * 
     * @param string $instanceUri 인스턴스 URI (예: 'mk:SupportiveIntroduction')
     * @return string 레이블 또는 인스턴스 URI
     */
    public function getInstanceLabel(string $instanceUri): string {
        // mk: 프리픽스 없으면 추가
        if (strpos($instanceUri, 'mk:') !== 0 && strpos($instanceUri, ':') === false) {
            $instanceUri = 'mk:' . $instanceUri;
        }
        
        if (isset($this->instanceLabels[$instanceUri])) {
            return $this->instanceLabels[$instanceUri]['label'];
        }
        
        // 매칭 안되면 CamelCase를 공백으로 분리하여 반환
        $shortName = str_replace('mk:', '', $instanceUri);
        return trim(preg_replace('/([A-Z])/', ' $1', $shortName));
    }
    
    /**
     * 클래스의 상위 클래스 조회
     * 
     * @param string $classUri 클래스 URI
     * @return string|null 상위 클래스 URI
     */
    public function getSuperClass(string $classUri): ?string {
        $def = $this->getClassDefinition($classUri);
        return $def['subClassOf'] ?? null;
    }
    
    /**
     * 클래스 계층 구조 조회 (상위 클래스 체인)
     * 
     * @param string $classUri 클래스 URI
     * @return array 상위 클래스 목록 (가까운 순)
     */
    public function getClassHierarchy(string $classUri): array {
        $hierarchy = [];
        $current = $classUri;
        $visited = []; // 순환 참조 방지
        
        while ($current && !isset($visited[$current])) {
            $visited[$current] = true;
            $superClass = $this->getSuperClass($current);
            if ($superClass) {
                $hierarchy[] = $superClass;
                $current = $superClass;
            } else {
                break;
            }
        }
        
        return $hierarchy;
    }
    
    /**
     * 진단 정보 반환 (Q1 진단 뷰용)
     * 
     * @return array 스키마 진단 정보
     */
    public function getDiagnostics(): array {
        return [
            'schema_loaded' => $this->schema !== null,
            'schema_path' => $this->schemaPath,
            'class_count' => count($this->classDefinitions),
            'property_count' => count($this->propertyDefinitions),
            'classes' => array_keys($this->classDefinitions),
            'properties' => array_keys($this->propertyDefinitions),
            'context_keys' => array_keys($this->context),
            'validation_errors' => $this->validationErrors
        ];
    }
    
    /**
     * rules.yaml 액션과 스키마 매핑 검증
     * 
     * @param array $actions rules.yaml의 action 배열
     * @return array ['valid' => bool, 'mappings' => array, 'errors' => array]
     */
    public function validateRuleActions(array $actions): array {
        $mappings = [];
        $errors = [];
        
        foreach ($actions as $action) {
            // create_instance 액션 파싱
            if (preg_match("/create_instance:\s*'([^']+)'/", $action, $matches)) {
                $classUri = $matches[1];
                if (!$this->classExists($classUri)) {
                    $errors[] = [
                        'action' => $action,
                        'type' => 'class_not_found',
                        'class' => $classUri,
                        'message' => "create_instance에서 참조한 클래스가 스키마에 없습니다: {$classUri}"
                    ];
                } else {
                    $mappings[] = ['action' => 'create_instance', 'class' => $classUri, 'valid' => true];
                }
            }
            
            // set_property 액션 파싱
            if (preg_match("/set_property:\s*\('([^']+)',\s*'\{([^}]+)\}'\)/", $action, $matches)) {
                $property = $matches[1];
                $variable = $matches[2];
                
                $propDef = $this->getPropertyDefinition($property);
                if (!$propDef) {
                    $errors[] = [
                        'action' => $action,
                        'type' => 'property_not_found',
                        'property' => $property,
                        'variable' => $variable,
                        'message' => "set_property에서 참조한 프로퍼티가 스키마에 없습니다: {$property}"
                    ];
                } else {
                    $mappings[] = [
                        'action' => 'set_property',
                        'property' => $property,
                        'variable' => $variable,
                        'expectedType' => $propDef['type'] ?? 'unknown',
                        'valid' => true
                    ];
                }
            }
            
            // reason_over 액션 파싱
            if (preg_match("/reason_over:\s*'([^']+)'/", $action, $matches)) {
                $classUri = $matches[1];
                if (!$this->classExists($classUri)) {
                    $errors[] = [
                        'action' => $action,
                        'type' => 'class_not_found',
                        'class' => $classUri,
                        'message' => "reason_over에서 참조한 클래스가 스키마에 없습니다: {$classUri}"
                    ];
                } else {
                    $mappings[] = ['action' => 'reason_over', 'class' => $classUri, 'valid' => true];
                }
            }
            
            // generate_strategy 액션 파싱
            if (preg_match("/generate_strategy:\s*'([^']+)'/", $action, $matches)) {
                $classUri = $matches[1];
                if (!$this->classExists($classUri)) {
                    $errors[] = [
                        'action' => $action,
                        'type' => 'class_not_found',
                        'class' => $classUri,
                        'message' => "generate_strategy에서 참조한 클래스가 스키마에 없습니다: {$classUri}"
                    ];
                } else {
                    $mappings[] = ['action' => 'generate_strategy', 'class' => $classUri, 'valid' => true];
                }
            }
            
            // generate_procedure 액션 파싱
            if (preg_match("/generate_procedure:\s*'([^']+)'/", $action, $matches)) {
                $classUri = $matches[1];
                if (!$this->classExists($classUri)) {
                    $errors[] = [
                        'action' => $action,
                        'type' => 'class_not_found',
                        'class' => $classUri,
                        'message' => "generate_procedure에서 참조한 클래스가 스키마에 없습니다: {$classUri}"
                    ];
                } else {
                    $mappings[] = ['action' => 'generate_procedure', 'class' => $classUri, 'valid' => true];
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'mappings' => $mappings,
            'errors' => $errors
        ];
    }
    
    /**
     * YAML 변수와 온톨로지 프로퍼티 매핑 검증
     * 
     * @param array $variableMappings 변수 매핑 배열 (예: ['gradeLevel' => ['student_grade', 'grade_level']])
     * @return array ['valid' => bool, 'matched' => array, 'unmatched' => array]
     */
    public function validateVariableMappings(array $variableMappings): array {
        $matched = [];
        $unmatched = [];
        
        // 공식 변수 → 온톨로지 프로퍼티 매핑 테이블
        $officialMapping = self::getOfficialVariableMapping();
        
        foreach ($variableMappings as $varName => $contextKeys) {
            // 1. 공식 매핑 테이블에서 먼저 찾기
            if (isset($officialMapping[$varName])) {
                $ontologyProp = $officialMapping[$varName];
                $propDef = $this->getPropertyDefinition($ontologyProp);
                
                if ($propDef) {
                    $matched[$varName] = [
                        'property' => $propDef,
                        'contextKeys' => $contextKeys,
                        'ontologyProperty' => $ontologyProp,
                        'note' => "공식 매핑: {$varName} → {$ontologyProp}"
                    ];
                    continue;
                }
            }
            
            // 2. 변수명이 온톨로지 프로퍼티와 직접 매칭되는지 확인
            $propDef = $this->getPropertyDefinition($varName);
            
            if ($propDef) {
                $matched[$varName] = [
                    'property' => $propDef,
                    'contextKeys' => $contextKeys
                ];
            } else {
                // 3. camelCase → snake_case 변환 시도
                $snakeCase = strtolower(preg_replace('/([A-Z])/', '_$1', $varName));
                $propDef = $this->getPropertyDefinition($snakeCase);
                
                if ($propDef) {
                    $matched[$varName] = [
                        'property' => $propDef,
                        'contextKeys' => $contextKeys,
                        'note' => "snake_case 변환으로 매칭됨: {$snakeCase}"
                    ];
                } else {
                    // 4. has 프리픽스 추가 시도 (concept_progress → hasConceptProgress)
                    $camelCase = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $varName))));
                    $hasProperty = 'has' . ucfirst($camelCase);
                    $propDef = $this->getPropertyDefinition($hasProperty);
                    
                    if ($propDef) {
                        $matched[$varName] = [
                            'property' => $propDef,
                            'contextKeys' => $contextKeys,
                            'note' => "has 프리픽스로 매칭됨: {$hasProperty}"
                        ];
                    } else {
                        $unmatched[$varName] = [
                            'contextKeys' => $contextKeys,
                            'message' => "온톨로지 프로퍼티와 매칭되지 않음"
                        ];
                    }
                }
            }
        }
        
        return [
            'valid' => empty($unmatched),
            'matched' => $matched,
            'unmatched' => $unmatched
        ];
    }
    
    /**
     * [인스턴스 메서드] 공식 변수 → 온톨로지 프로퍼티 매핑 테이블
     * 
     * 정적 메서드의 인스턴스 버전 (안전한 호출용)
     */
    public function getOfficialVariableMappingInstance(): array {
        return self::getOfficialVariableMapping();
    }
    
    /**
     * 공식 변수 → 온톨로지 프로퍼티 매핑 테이블
     * 
     * 컨텍스트 변수명과 온톨로지 프로퍼티 간의 공식 매핑
     * 이 테이블이 "단일 진실 소스(Single Source of Truth)"
     */
    public static function getOfficialVariableMapping(): array {
        return [
            // ===== OnboardingContext 프로퍼티 =====
            'gradeLevel'        => 'gradeLevel',
            'student_grade'     => 'gradeLevel',
            'schoolName'        => 'schoolName',
            'school_name'       => 'schoolName',
            'academyName'       => 'academyName',
            'academy_name'      => 'academyName',
            'academyGrade'      => 'academyGrade',
            'academy_grade'     => 'academyGrade',
            
            // ===== LearningContextIntegration 프로퍼티 =====
            'concept_progress'          => 'conceptProgressLevel',      // 온톨로지: conceptProgressLevel
            'conceptProgress'           => 'conceptProgressLevel',
            'advanced_progress'         => 'advancedProgressLevel',     // 온톨로지: advancedProgressLevel
            'advancedProgress'          => 'advancedProgressLevel',
            'math_unit_mastery'         => 'hasUnitMastery',            // 온톨로지: hasUnitMastery (관계 프로퍼티)
            'unitMastery'               => 'hasUnitMastery',
            'current_progress_position' => 'hasCurrentPosition',        // 온톨로지: hasCurrentPosition (관계 프로퍼티)
            'currentPosition'           => 'hasCurrentPosition',
            
            // ===== FirstClassStrategy 프로퍼티 =====
            'math_learning_style'   => 'mathLearningStyle',             // 온톨로지: mathLearningStyle
            'mathLearningStyle'     => 'mathLearningStyle',
            'study_style'           => 'studyStyle',                    // 온톨로지: studyStyle
            'studyStyle'            => 'studyStyle',
            'exam_style'            => 'examPreparationStyle',          // 온톨로지: examPreparationStyle
            'examStyle'             => 'examPreparationStyle',
            'math_confidence'       => 'mathSelfConfidence',            // 온톨로지: mathSelfConfidence
            'mathConfidence'        => 'mathSelfConfidence',
            'math_level'            => 'mathLevel',                     // 온톨로지: mathLevel
            'mathLevel'             => 'mathLevel',
            'math_stress_level'     => 'mathStressLevel',               // 온톨로지: mathStressLevel
            'mathStressLevel'       => 'mathStressLevel',
            
            // ===== 교재 관련 =====
            'textbooks'             => 'textbooks',
            'academy_textbook'      => 'academyTextbook',
            'academyTextbook'       => 'academyTextbook',
            
            // ===== 관계 프로퍼티 (has 프리픽스) =====
            'hasStudentGrade'       => 'hasStudentGrade',
            'hasSchool'             => 'hasSchool',
            'hasAcademy'            => 'hasAcademy',
            'hasAcademyGrade'       => 'hasAcademyGrade',
            'hasConceptProgress'    => 'hasConceptProgress',
            'hasAdvancedProgress'   => 'hasAdvancedProgress',
            'hasUnitMastery'        => 'hasUnitMastery',
            'hasCurrentPosition'    => 'hasCurrentPosition',
            'hasMathLearningStyle'  => 'hasMathLearningStyle',
            'hasStudyStyle'         => 'hasStudyStyle',
            'hasExamStyle'          => 'hasExamStyle',
            'hasMathConfidence'     => 'hasMathConfidence',
            'hasMathLevel'          => 'hasMathLevel',
            'hasMathStressLevel'    => 'hasMathStressLevel',
            'hasTextbooks'          => 'hasTextbooks',
            'hasAcademyTextbook'    => 'hasAcademyTextbook',
            
            // ===== 추천 프로퍼티 (recommends 프리픽스) =====
            'recommendsTextbook'        => 'recommendsTextbook',
            'recommendsUnit'            => 'recommendsUnit',
            'recommendsUnits'           => 'recommendsUnits',
            'recommendsProblemType'     => 'recommendsProblemType',
            'recommendsDifficulty'      => 'recommendsDifficulty',
            'recommendsProgress'        => 'recommendsProgress',
            'recommendsContentRange'    => 'recommendsContentRange',
            'recommendsIntroductionRoutine' => 'recommendsIntroductionRoutine',
            'recommendsExplanationStrategy' => 'recommendsExplanationStrategy',
            'recommendsMaterialType'    => 'recommendsMaterialType',
            'recommendsInteractionStyle' => 'recommendsInteractionStyle',
            'recommendsFeedbackTone'    => 'recommendsFeedbackTone',
            'recommendsAlignmentStrategy' => 'recommendsAlignmentStrategy',
            
            // ===== 절차 관련 프로퍼티 =====
            'hasProcedureSteps'     => 'hasProcedureSteps',
            'stepOrder'             => 'stepOrder',
            'stepType'              => 'stepType',
            'stepDescription'       => 'stepDescription',
            'stepDuration'          => 'stepDuration'
        ];
    }
    
    /**
     * 컨텍스트 변수명을 온톨로지 프로퍼티명으로 변환
     * 
     * @param string $contextKey 컨텍스트 키 (예: 'concept_progress')
     * @return string|null 온톨로지 프로퍼티명 (예: 'conceptProgressLevel') 또는 null
     */
    public function mapContextToOntology(string $contextKey): ?string {
        $mapping = self::getOfficialVariableMapping();
        return $mapping[$contextKey] ?? null;
    }
    
    /**
     * 온톨로지 프로퍼티명을 컨텍스트 변수명으로 변환 (역방향)
     * 
     * @param string $ontologyProp 온톨로지 프로퍼티명 (예: 'conceptProgressLevel')
     * @return string|null 컨텍스트 키 (예: 'concept_progress') 또는 null
     */
    public function mapOntologyToContext(string $ontologyProp): ?string {
        $mapping = self::getOfficialVariableMapping();
        $reversed = array_flip($mapping);
        
        // 역방향 매핑에서 snake_case 우선 반환
        if (isset($reversed[$ontologyProp])) {
            $key = $reversed[$ontologyProp];
            // snake_case 형태 우선
            if (strpos($key, '_') !== false) {
                return $key;
            }
        }
        
        // 전체 매핑에서 찾기
        foreach ($mapping as $contextKey => $prop) {
            if ($prop === $ontologyProp && strpos($contextKey, '_') !== false) {
                return $contextKey;
            }
        }
        
        return $reversed[$ontologyProp] ?? null;
    } 
     
    /**
     * 스키마 파일 경로 반환
     * 
     * @return string 스키마 파일 경로
     */
    public function getSchemaPath(): string {
        return $this->schemaPath;
    }
    
    /**
     * 스키마가 로드되었는지 확인
     * 
     * @return bool 스키마 로드 여부
     */
    public function isLoaded(): bool {
        return $this->schema !== null;
    }
    
    /**
     * 클래스 수 반환
     * 
     * @return int 클래스 수
     */
    public function getClassCount(): int {
        return count($this->classDefinitions);
    }
    
    /**
     * 프로퍼티 수 반환
     * 
     * @return int 프로퍼티 수
     */
    public function getPropertyCount(): int {
        return count($this->propertyDefinitions);
    }
}

