<?php
/**
 * Agent Garden Service
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/agent_garden.service.php
 * 
 * 에이전트 가든 비즈니스 로직
 */

class AgentGardenService {
    
    /**
     * 범용 온톨로지 액션 처리
     * 
     * @param string $agentId 에이전트 ID
     * @param array $decision 룰 엔진 결정 결과
     * @param array $context 컨텍스트
     * @param int $studentId 학생 ID
     * @return array 수정된 decision (ontology_results 추가)
     */
    private function processOntologyActions(string $agentId, array $decision, array $context, int $studentId): array {
        $ontologyResults = [];
        
        error_log("[AgentGardenService] processOntologyActions called for agent: {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[AgentGardenService] Decision keys: " . implode(', ', array_keys($decision)) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        if (!isset($decision['actions']) || !is_array($decision['actions'])) {
            error_log("[AgentGardenService] No actions found in decision. Has actions key: " . (isset($decision['actions']) ? 'YES' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return $decision;
        }
        
        error_log("[AgentGardenService] Found " . count($decision['actions']) . " actions [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[AgentGardenService] Actions preview: " . json_encode(array_slice($decision['actions'], 0, 5), JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // 에이전트별 전용 핸들러 사용
        if ($agentId === 'agent01' || $agentId === 'agent01_onboarding') {
            $ontologyHandlerPath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyActionHandler.php';
        } elseif ($agentId === 'agent04' || $agentId === 'agent04_inspect_weakpoints') {
            $ontologyHandlerPath = __DIR__ . '/../../agent04_inspect_weakpoints/ontology/OntologyActionHandler.php';
        } else {
            $ontologyHandlerPath = __DIR__ . '/../ontology/OntologyActionHandler.php';
        }
        
        error_log("[AgentGardenService] Looking for OntologyActionHandler at: {$ontologyHandlerPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        if (!file_exists($ontologyHandlerPath)) {
            error_log("[AgentGardenService] OntologyActionHandler not found at: {$ontologyHandlerPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return $decision;
        }
        
        error_log("[AgentGardenService] OntologyActionHandler found, loading... [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        require_once($ontologyHandlerPath);
        error_log("[AgentGardenService] OntologyActionHandler loaded successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // Agent04의 경우 OntologyConfig 체크 스킵 (Agent01 전용)
        if ($agentId !== 'agent04' && $agentId !== 'agent04_inspect_weakpoints') {
            // OntologyConfig 로드 (에이전트 ID 정규화 및 파일 존재 확인용)
            $ontologyConfigPath = __DIR__ . '/../ontology/OntologyConfig.php';
            if (file_exists($ontologyConfigPath)) {
                require_once($ontologyConfigPath);
                
                // 에이전트 ID 정규화
                $agentId = OntologyConfig::normalizeAgentId($agentId);
                
                // 온톨로지 파일 존재 여부 확인
                $ontologyFileLoaderPath = __DIR__ . '/../ontology/OntologyFileLoader.php';
                if (file_exists($ontologyFileLoaderPath)) {
                    require_once($ontologyFileLoaderPath);
                    
                    if (!OntologyFileLoader::exists($agentId)) {
                        error_log("[AgentGardenService] Ontology file not found for agent: {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        return $decision; // 온톨로지 파일이 없으면 기본 동작 유지
                    }
                }
            }
        }
        
        try {
            // OntologyActionHandler 생성 (에러 발생 시 기본 동작 유지)
            try {
                // Agent04의 경우 생성자 시그니처가 다름
                if ($agentId === 'agent04' || $agentId === 'agent04_inspect_weakpoints') {
                    $ontologyHandler = new OntologyActionHandler($context, $studentId);
                } else {
                    $ontologyHandler = new OntologyActionHandler($agentId, $context, $studentId);
                }
            } catch (Exception $e) {
                error_log("[AgentGardenService] Failed to create OntologyActionHandler for {$agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return $decision; // 기본 동작 유지
            }
            
            foreach ($decision['actions'] as $idx => $action) {
                // Python 룰 엔진이 반환하는 액션 형식 처리
                // 형식 1: 문자열 "create_instance: 'mk-a04:WeakpointDetectionContext'"
                // 형식 2: 배열 {"create_instance": "mk-a04:WeakpointDetectionContext"}
                // 형식 3: 배열 {"key": "value"} (parse_action 결과)
                
                $actionStr = '';
                $isOntologyAction = false;
                
                if (is_array($action)) {
                    // 배열 형식: {"create_instance": "mk-a04:..."} 또는 {"key": "value"}
                    $actionKeys = array_keys($action);
                    
                    // 온톨로지 액션 키 확인
                    $ontologyKeys = ['create_instance', 'set_property', 'reason_over', 'generate_reinforcement_plan', 'generate_strategy', 'generate_procedure'];
                    foreach ($ontologyKeys as $key) {
                        if (isset($action[$key])) {
                            $isOntologyAction = true;
                            break;
                        }
                    }
                    
                    // set_property의 경우 값에 mk: 또는 mk-a04:가 있는지 확인
                    if (!$isOntologyAction && isset($action['set_property'])) {
                        $propertyValue = is_string($action['set_property']) ? $action['set_property'] : json_encode($action['set_property']);
                        if (preg_match('/(mk:|mk-a04:|at:)/i', $propertyValue)) {
                            $isOntologyAction = true;
                        }
                    }
                    
                    $actionStr = json_encode($action, JSON_UNESCAPED_UNICODE);
                } else {
                    // 문자열 형식
                    $actionStr = (string)$action;
                    
                    // create_instance, reason_over, generate_reinforcement_plan 등은 무조건 온톨로지 액션
                    if (preg_match('/create_instance|reason_over|generate_reinforcement_plan|generate_strategy|generate_procedure/i', $actionStr)) {
                        $isOntologyAction = true;
                    }
                    // set_property는 mk: 또는 mk-a04:를 포함하는 경우만
                    elseif (preg_match('/set_property.*(mk:|mk-a04:|at:)/i', $actionStr)) {
                        $isOntologyAction = true;
                    }
                }
                
                if ($isOntologyAction) {
                    error_log("[AgentGardenService] Processing ontology action {$idx} for {$agentId}: {$actionStr} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    
                    try {
                        $result = $ontologyHandler->executeAction($action);
                        $ontologyResults[] = $result;
                        
                        if ($result['success']) {
                            error_log("[AgentGardenService] Ontology action executed successfully for {$agentId}: " . json_encode($result, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        } else {
                            error_log("[AgentGardenService] Ontology action failed for {$agentId}: " . ($result['error'] ?? 'Unknown error') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        }
                    } catch (Exception $e) {
                        error_log("[AgentGardenService] Ontology action exception for {$agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        $ontologyResults[] = [
                            'success' => false,
                            'error' => $e->getMessage(),
                            'action' => $actionStr
                        ];
                    }
                }
            }
            
            if (!empty($ontologyResults)) {
                error_log("[AgentGardenService] Processed " . count($ontologyResults) . " ontology actions for {$agentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $decision['ontology_results'] = $ontologyResults;
            } else {
                error_log("[AgentGardenService] No ontology actions were processed for {$agentId}. Total actions: " . count($decision['actions']) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                // 디버깅: 모든 액션 출력
                foreach ($decision['actions'] as $idx => $action) {
                    $actionStr = is_array($action) ? json_encode($action, JSON_UNESCAPED_UNICODE) : (string)$action;
                    error_log("[AgentGardenService] Action {$idx}: {$actionStr} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
        } catch (Exception $e) {
            error_log("[AgentGardenService] Error processing ontology actions for {$agentId}: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        return $decision;
    }
    
    /**
     * 에이전트 실행
     */
    public function executeAgent($agentId, $request, $studentId) {
        // 에이전트별 실행 경로 매핑
        $agentPaths = $this->getAgentPaths();
        
        if (!isset($agentPaths[$agentId])) {
            throw new Exception("에이전트 {$agentId}를 찾을 수 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }

        $agentPath = $agentPaths[$agentId];
        
        // agent01 (온보딩)의 경우 rules.yaml을 사용하여 추론
        if ($agentId === 'agent01' || $agentId === 'agent01_onboarding') {
            return $this->executeAgent01WithRules($request, $studentId);
        }
        
        // agent04 (취약점 분석)의 경우 rules.yaml을 사용하여 추론
        if ($agentId === 'agent04' || $agentId === 'agent04_inspect_weakpoints') {
            return $this->executeAgent04WithRules($request, $studentId);
        }
        
        // 다른 에이전트는 기존 로직 사용 (향후 온톨로지 통합 가능)
        $result = [
            'agent_id' => $agentId,
            'agent_name' => $this->getAgentName($agentId),
            'request' => $request,
            'response' => $this->simulateAgentExecution($agentId, $request),
            'timestamp' => time(),
            'execution_time' => rand(100, 500) . 'ms'
        ];

        return $result;
    }
    
    /**
     * Agent01 (온보딩) 실행 - rules.yaml 사용
     */
    private function executeAgent01WithRules($request, $studentId) {
        try {
            // student_id 검증
            if (empty($studentId)) {
                global $USER;
                $studentId = $USER->id ?? null;
                if (empty($studentId)) {
                    throw new Exception("student_id가 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // agent01의 rules 폴더 경로
            $agent01RulesPath = __DIR__ . '/../../agent01_onboarding/rules';
            $rulesFilePath = $agent01RulesPath . '/rules.yaml';
            $ruleEvaluatorPath = $agent01RulesPath . '/rule_evaluator.php';
            $dataAccessPath = $agent01RulesPath . '/data_access.php';
            
            // 디버깅: 경로 확인
            error_log("[Agent01 Debug] agent01RulesPath: {$agent01RulesPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent01 Debug] rulesFilePath: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent01 Debug] studentId: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 파일 존재 확인
            if (!file_exists($rulesFilePath)) {
                $errorMsg = "Rules file not found: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent01 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($ruleEvaluatorPath)) {
                $errorMsg = "Rule evaluator not found: {$ruleEvaluatorPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent01 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($dataAccessPath)) {
                $errorMsg = "Data access file not found: {$dataAccessPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent01 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            error_log("[Agent01 Debug] All files exist. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // report_generator.php가 직접 실행되지 않도록 가드 설정
            if (!defined('ALT42_DISABLE_DIRECT_ACTION')) {
                define('ALT42_DISABLE_DIRECT_ACTION', true);
            }
            
            // 학생 컨텍스트 가져오기
            error_log("[Agent01 Debug] Loading data_access.php [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            require_once($dataAccessPath);
            
            error_log("[Agent01 Debug] Calling prepareRuleContext({$studentId}) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $context = prepareRuleContext($studentId);
            
            // prepareRuleContext가 null을 반환한 경우 처리
            if ($context === null) {
                error_log("[Agent01 Warning] prepareRuleContext returned null, using default context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $context = ['student_id' => $studentId];
            }
            
            // student_id가 컨텍스트에 포함되어 있는지 확인 및 보장
            if (!isset($context['student_id']) || empty($context['student_id'])) {
                error_log("[Agent01 Warning] student_id missing in context, setting to {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $context['student_id'] = $studentId;
            }
            
            // 최종 검증: student_id가 반드시 있어야 함
            if (empty($context['student_id'])) {
                $errorMsg = "student_id를 컨텍스트에서 가져올 수 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent01 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            error_log("[Agent01 Debug] Context prepared. student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 사용자 메시지를 컨텍스트에 추가
            $context['user_message'] = $request;
            $context['conversation_timestamp'] = time();
            
            // ========================================
            // PersonaRuleEngine 통합 (2025-12-03)
            // 페르소나 식별 후 컨텍스트에 주입하여 페르소나별 맞춤 룰 적용
            // ========================================
            $personaEnginePath = __DIR__ . '/../../agent01_onboarding/persona_system/engine/PersonaRuleEngine.php';
            $personaResult = null;

            if (file_exists($personaEnginePath)) {
                try {
                    require_once($personaEnginePath);
                    error_log("[Agent01 Debug] PersonaRuleEngine loaded [File: " . __FILE__ . ", Line: " . __LINE__ . "]");

                    // PersonaRuleEngine 인스턴스 생성 및 페르소나 식별
                    $personaEngine = new PersonaRuleEngine();

                    // 페르소나 식별을 위한 컨텍스트 준비
                    $personaContext = [
                        'student_id' => $context['student_id'],
                        'user_message' => $context['user_message'],
                        'situation_code' => 'S0',  // 온보딩 상황 코드
                        'grade_level' => $context['grade_level'] ?? null,
                        'math_confidence' => $context['math_confidence'] ?? null,
                        'emotion_level' => $context['emotion_level'] ?? 0,
                        'timestamp' => time()
                    ];

                    // 페르소나 식별 실행
                    $personaResult = $personaEngine->identifyPersona($personaContext);
                    error_log("[Agent01 Debug] Persona identified: " . json_encode($personaResult, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");

                    // 컨텍스트에 페르소나 정보 주입
                    $context['persona_id'] = $personaResult['persona_id'] ?? 'unknown';
                    $context['persona_name'] = $personaResult['persona_name'] ?? null;
                    $context['persona_behavior'] = $personaResult['behavior'] ?? 'neutral';
                    $context['persona_tone'] = $personaResult['tone'] ?? 'Professional';
                    $context['persona_pace'] = $personaResult['pace'] ?? 'moderate';
                    $context['persona_confidence'] = $personaResult['confidence'] ?? 0;

                    error_log("[Agent01 Debug] Persona context injected - ID: {$context['persona_id']}, Behavior: {$context['persona_behavior']}, Tone: {$context['persona_tone']} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");

                } catch (Exception $personaError) {
                    error_log("[Agent01 Warning] PersonaRuleEngine error: " . $personaError->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                    // 페르소나 에러 시 기본값으로 진행
                    $context['persona_id'] = 'default';
                    $context['persona_behavior'] = 'neutral';
                    $context['persona_tone'] = 'Professional';
                    $context['persona_pace'] = 'moderate';
                    $context['persona_confidence'] = 0;
                }
            } else {
                error_log("[Agent01 Warning] PersonaRuleEngine not found: {$personaEnginePath} - proceeding without persona [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                // 페르소나 엔진 없이 기본값으로 진행
                $context['persona_id'] = 'default';
                $context['persona_behavior'] = 'neutral';
                $context['persona_tone'] = 'Professional';
                $context['persona_pace'] = 'moderate';
                $context['persona_confidence'] = 0;
            }
            // ========================================
            // PersonaRuleEngine 통합 끝
            // ========================================

            // 룰 평가기 로드 및 실행
            error_log("[Agent01 Debug] Loading rule_evaluator.php [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            require_once($ruleEvaluatorPath);
            
            // rules.yaml 파일 경로를 올바르게 지정
            error_log("[Agent01 Debug] Creating OnboardingRuleEvaluator with rules file: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $evaluator = new OnboardingRuleEvaluator($rulesFilePath);
            
            error_log("[Agent01 Debug] Evaluating rules with context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 온톨로지 파일 경로 확인 및 로깅
            $ontologyFilePath = __DIR__ . '/../../ontology_engineering/modules/agent01.owl';
            if (file_exists($ontologyFilePath)) {
                error_log("[Agent01 Debug] Ontology file found: {$ontologyFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            } else {
                error_log("[Agent01 Warning] Ontology file not found: {$ontologyFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            $decision = $evaluator->evaluate($context);
            error_log("[Agent01 Debug] Rules evaluated successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent01 Debug] Decision: " . json_encode($decision, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent01 Debug] Decision has actions: " . (isset($decision['actions']) ? 'YES (' . count($decision['actions']) . ')' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 범용 온톨로지 액션 처리
            $decision = $this->processOntologyActions('agent01', $decision, $context, $studentId);
            error_log("[Agent01 Debug] After processOntologyActions - has ontology_results: " . (isset($decision['ontology_results']) ? 'YES (' . count($decision['ontology_results']) . ')' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 액션에서 답변 생성 (컨텍스트 정보 포함)
            $response = $this->generateResponseFromActions($decision, $request, $context);
            error_log("[Agent01 Debug] Generated response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            $result = [
                'agent_id' => 'agent01',
                'agent_name' => '온보딩',
                'request' => $request,
                'response' => $response,
                'timestamp' => time(),
                'execution_time' => isset($decision['execution_time']) ? $decision['execution_time'] : 'N/A',
                'matched_rule' => $decision['rule_id'] ?? null, // Python 스크립트는 rule_id를 반환함
                'confidence' => $decision['confidence'] ?? null
            ];
            
            // 온톨로지 결과 포함
            if (isset($decision['ontology_results'])) {
                $result['ontology_results'] = $decision['ontology_results'];
                error_log("[Agent01 Debug] Added ontology_results to result: " . count($decision['ontology_results']) . " items [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }

            // 페르소나 정보 포함 (2025-12-03 추가)
            if ($personaResult !== null) {
                $result['persona'] = [
                    'id' => $personaResult['persona_id'] ?? 'unknown',
                    'name' => $personaResult['persona_name'] ?? null,
                    'behavior' => $personaResult['behavior'] ?? 'neutral',
                    'tone' => $personaResult['tone'] ?? 'Professional',
                    'pace' => $personaResult['pace'] ?? 'moderate',
                    'confidence' => $personaResult['confidence'] ?? 0,
                    'matched_rule' => $personaResult['matched_rule'] ?? null
                ];
                error_log("[Agent01 Debug] Added persona to result: " . json_encode($result['persona'], JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }

            // 디버그: decision 구조 포함 (테스트용)
            $result['_debug'] = [
                'decision_keys' => array_keys($decision),
                'has_actions' => isset($decision['actions']),
                'actions_count' => isset($decision['actions']) ? count($decision['actions']) : 0,
                'has_ontology_results' => isset($decision['ontology_results']),
                'ontology_results_count' => isset($decision['ontology_results']) ? count($decision['ontology_results']) : 0,
                'decision_actions_preview' => isset($decision['actions']) ? array_slice($decision['actions'], 0, 5) : null, // 처음 5개만 미리보기
                'decision_full' => $decision // 전체 decision 구조 (디버그용)
            ];
            
            return $result;
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            
            error_log("Error executing agent01 with rules: " . $errorMessage . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("Stack trace: " . $errorTrace);
            
            // 에러 발생 시 기본 응답 반환
            $responseMessage = '온보딩 에이전트 처리 중 오류가 발생했습니다: ' . $errorMessage;
            $fallbackMessage = '안녕하세요! 온보딩 에이전트입니다. 학습 스타일이나 목표에 대해 알려주시면 맞춤형 학습 계획을 도와드리겠습니다.';
            
            // PyYAML 관련 오류인 경우 특별 처리
            if (strpos($errorMessage, 'yaml 모듈') !== false || strpos($errorMessage, 'PyYAML') !== false) {
                $installUrl = dirname($_SERVER['PHP_SELF']) . '/install_pyyaml.php';
                $responseMessage = 'Python yaml 모듈이 필요합니다. ';
                $responseMessage .= '자동 설치를 시도했지만 실패했습니다. ';
                $responseMessage .= '웹 인터페이스를 통해 설치를 시도해주세요: ' . $installUrl;
                $fallbackMessage = 'Python yaml 모듈 설치가 필요합니다. 관리자에게 문의하거나 설치 페이지를 방문해주세요.';
            }
            
            return [
                'agent_id' => 'agent01',
                'agent_name' => '온보딩',
                'request' => $request,
                'response' => [
                    'status' => 'error',
                    'message' => $responseMessage,
                    'fallback_message' => $fallbackMessage,
                    'error_details' => $errorMessage,
                    'install_url' => (strpos($errorMessage, 'yaml 모듈') !== false || strpos($errorMessage, 'PyYAML') !== false) 
                        ? dirname($_SERVER['PHP_SELF']) . '/install_pyyaml.php' 
                        : null
                ],
                'timestamp' => time(),
                'execution_time' => 'N/A'
            ];
        }
    }
    
    /**
     * Agent04 (취약점 분석) 실행 - rules.yaml 사용
     */
    private function executeAgent04WithRules($request, $studentId) {
        try {
            // student_id 검증
            if (empty($studentId)) {
                global $USER;
                $studentId = $USER->id ?? null;
                if (empty($studentId)) {
                    throw new Exception("student_id가 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // agent04의 rules 폴더 경로
            $agent04RulesPath = __DIR__ . '/../../agent04_inspect_weakpoints/rules';
            $rulesFilePath = $agent04RulesPath . '/rules.yaml';
            $ruleEvaluatorPath = $agent04RulesPath . '/rule_evaluator.php';
            $dataAccessPath = $agent04RulesPath . '/data_access.php';
            
            // 디버깅: 경로 확인
            error_log("[Agent04 Debug] agent04RulesPath: {$agent04RulesPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent04 Debug] rulesFilePath: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent04 Debug] studentId: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 파일 존재 확인
            if (!file_exists($rulesFilePath)) {
                $errorMsg = "Rules file not found: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent04 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($ruleEvaluatorPath)) {
                $errorMsg = "Rule evaluator not found: {$ruleEvaluatorPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent04 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($dataAccessPath)) {
                $errorMsg = "Data access file not found: {$dataAccessPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent04 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            error_log("[Agent04 Debug] All files exist. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 학생 컨텍스트 가져오기
            error_log("[Agent04 Debug] Loading data_access.php [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            require_once($dataAccessPath);
            
            error_log("[Agent04 Debug] Calling prepareRuleContext({$studentId}) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $context = prepareRuleContext($studentId);
            
            // prepareRuleContext가 null을 반환한 경우 처리
            if ($context === null) {
                error_log("[Agent04 Warning] prepareRuleContext returned null, using default context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $context = ['student_id' => $studentId];
            }
            
            // student_id가 컨텍스트에 포함되어 있는지 확인 및 보장
            if (!isset($context['student_id']) || empty($context['student_id'])) {
                error_log("[Agent04 Warning] student_id missing in context, setting to {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $context['student_id'] = $studentId;
            }
            
            // 최종 검증: student_id가 반드시 있어야 함
            if (empty($context['student_id'])) {
                $errorMsg = "student_id를 컨텍스트에서 가져올 수 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent04 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            error_log("[Agent04 Debug] Context prepared. student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 사용자 메시지 또는 활동 데이터를 컨텍스트에 추가
            if (is_array($request)) {
                // 활동 데이터인 경우
                $context = array_merge($context, $request);
            } else {
                // 텍스트 메시지인 경우
                $context['user_message'] = $request;
            }
            $context['conversation_timestamp'] = time();
            
            // 룰 평가기 로드 및 실행
            error_log("[Agent04 Debug] Loading rule_evaluator.php [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            require_once($ruleEvaluatorPath);
            
            // rules.yaml 파일 경로를 올바르게 지정
            error_log("[Agent04 Debug] Creating InspectWeakpointsRuleEvaluator with rules file: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $evaluator = new InspectWeakpointsRuleEvaluator($rulesFilePath);
            
            error_log("[Agent04 Debug] Evaluating rules with context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            $decision = $evaluator->evaluate($context);
            error_log("[Agent04 Debug] Rules evaluated successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent04 Debug] Decision: " . json_encode($decision, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent04 Debug] Decision has actions: " . (isset($decision['actions']) ? 'YES (' . count($decision['actions']) . ')' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 디버깅: actions 상세 출력
            if (isset($decision['actions']) && is_array($decision['actions'])) {
                error_log("[Agent04 Debug] Actions preview (first 10): " . json_encode(array_slice($decision['actions'], 0, 10), JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                foreach (array_slice($decision['actions'], 0, 10) as $idx => $action) {
                    $actionStr = is_array($action) ? json_encode($action, JSON_UNESCAPED_UNICODE) : (string)$action;
                    error_log("[Agent04 Debug] Action {$idx}: {$actionStr} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // 범용 온톨로지 액션 처리
            $decision = $this->processOntologyActions('agent04', $decision, $context, $studentId);
            error_log("[Agent04 Debug] After processOntologyActions - has ontology_results: " . (isset($decision['ontology_results']) ? 'YES (' . count($decision['ontology_results']) . ')' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            if (isset($decision['ontology_results'])) {
                error_log("[Agent04 Debug] Ontology results: " . json_encode($decision['ontology_results'], JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // 액션에서 답변 생성 (컨텍스트 정보 포함)
            $response = $this->generateAgent04Response($decision, $request, $context);
            error_log("[Agent04 Debug] Generated response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            $result = [
                'agent_id' => 'agent04',
                'agent_name' => '취약점 분석',
                'request' => $request,
                'response' => $response,
                'timestamp' => time(),
                'execution_time' => isset($decision['execution_time']) ? $decision['execution_time'] : 'N/A',
                'matched_rule' => $decision['rule_id'] ?? null,
                'confidence' => $decision['confidence'] ?? null
            ];
            
            return $result;
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            
            error_log("Error executing agent04 with rules: " . $errorMessage . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("Stack trace: " . $errorTrace);
            
            // 에러 발생 시 기본 응답 반환
            $responseMessage = '취약점 분석 에이전트 처리 중 오류가 발생했습니다: ' . $errorMessage;
            $fallbackMessage = '안녕하세요! 취약점 분석 에이전트입니다. 학습 활동 데이터를 분석하여 취약점을 찾아드리겠습니다.';
            
            // PyYAML 관련 오류인 경우 특별 처리
            if (strpos($errorMessage, 'yaml 모듈') !== false || strpos($errorMessage, 'PyYAML') !== false) {
                $installUrl = dirname($_SERVER['PHP_SELF']) . '/install_pyyaml.php';
                $responseMessage = 'Python yaml 모듈이 필요합니다. ';
                $responseMessage .= '자동 설치를 시도했지만 실패했습니다. ';
                $responseMessage .= '웹 인터페이스를 통해 설치를 시도해주세요: ' . $installUrl;
                $fallbackMessage = 'Python yaml 모듈 설치가 필요합니다. 관리자에게 문의하거나 설치 페이지를 방문해주세요.';
            }
            
            return [
                'agent_id' => 'agent04',
                'agent_name' => '취약점 분석',
                'request' => $request,
                'response' => [
                    'status' => 'error',
                    'message' => $responseMessage,
                    'fallback_message' => $fallbackMessage,
                    'error_details' => $errorMessage,
                    'install_url' => (strpos($errorMessage, 'yaml 모듈') !== false || strpos($errorMessage, 'PyYAML') !== false) 
                        ? dirname($_SERVER['PHP_SELF']) . '/install_pyyaml.php' 
                        : null
                ],
                'timestamp' => time(),
                'execution_time' => 'N/A'
            ];
        }
    }
    
    /**
     * Agent04 응답 생성 (온톨로지 결과 포함)
     */
    private function generateAgent04Response($decision, $userMessage, $context = null) {
        $actions = $decision['actions'] ?? [];
        $response = [
            'status' => 'success',
            'message' => '',
            'reinforcement_plan' => null,
            'reasoning_results' => null,
            'execution_plan' => null,
            'weakpoint_analysis' => []
        ];
        
        // 온톨로지 결과 추출
        $ontologyResults = $decision['ontology_results'] ?? [];
        $reinforcementPlans = [];
        $reasoningResults = [];
        $executionPlans = [];
        $weakpointContexts = [];
        
        if (!empty($ontologyResults)) {
            foreach ($ontologyResults as $result) {
                if ($result['success'] ?? false) {
                    // 보강 방안 추출 (generate_reinforcement_plan 결과)
                    if (isset($result['reinforcement_plan'])) {
                        $reinforcementPlans[] = $result['reinforcement_plan'];
                    }
                    
                    // 인스턴스 ID가 있으면 인스턴스 데이터 조회
                    if (isset($result['instance_id'])) {
                        $instanceId = $result['instance_id'];
                        $instanceData = $this->getOntologyInstance($instanceId, 'agent04');
                        
                        if ($instanceData) {
                            $instanceType = $instanceData['@type'] ?? '';
                            
                            // WeakpointAnalysisDecisionModel 추출
                            if (strpos($instanceType, 'WeakpointAnalysisDecisionModel') !== false) {
                                $reinforcementPlans[] = $instanceData;
                            }
                            
                            // ReinforcementPlanExecutionPlan 추출
                            if (strpos($instanceType, 'ReinforcementPlanExecutionPlan') !== false) {
                                $executionPlans[] = $instanceData;
                            }
                            
                            // WeakpointDetectionContext 추출
                            if (strpos($instanceType, 'WeakpointDetectionContext') !== false) {
                                $weakpointContexts[] = $instanceData;
                            }
                        }
                    }
                    
                    // 추론 결과 추출 (reason_over 결과)
                    if (isset($result['results']) && is_array($result['results'])) {
                        foreach ($result['results'] as $reasoningResult) {
                            if (isset($reasoningResult['reasoning'])) {
                                $reasoningResults[] = $reasoningResult['reasoning'];
                            }
                        }
                    }
                }
            }
        }
        
        // 보강 방안 통합
        if (!empty($reinforcementPlans)) {
            $plan = $reinforcementPlans[0]; // 첫 번째 보강 방안 사용
            $response['reinforcement_plan'] = [
                'weakpoint_description' => $plan['mk-a04:hasWeakpointDescription'] ?? null,
                'root_cause' => $plan['mk-a04:hasRootCause'] ?? null,
                'reinforcement_strategy' => $plan['mk-a04:hasReinforcementStrategy'] ?? null,
                'recommended_method' => $plan['mk-a04:hasRecommendedMethod'] ?? null,
                'recommended_content' => $plan['mk-a04:hasRecommendedContent'] ?? null,
                'intervention_type' => $plan['mk-a04:hasInterventionType'] ?? null,
                'feedback_message' => $plan['mk-a04:hasFeedbackMessage'] ?? null,
                'severity_level' => $plan['mk-a04:hasSeverityLevel'] ?? null,
                'priority' => $plan['mk-a04:hasPriority'] ?? null
            ];
        }
        
        // 추론 결과 통합
        if (!empty($reasoningResults)) {
            $reasoning = $reasoningResults[0]; // 첫 번째 추론 결과 사용
            $response['reasoning_results'] = [
                'inferred_severity' => $reasoning['inferredSeverity'] ?? null,
                'inferred_strategy' => $reasoning['inferredStrategy'] ?? null,
                'inferred_priority' => $reasoning['inferredPriority'] ?? null,
                'inferred_intervention_type' => $reasoning['inferredInterventionType'] ?? null,
                'inferred_content_range' => $reasoning['inferredContentRange'] ?? null
            ];
        }
        
        // 실행 계획 통합
        if (!empty($executionPlans)) {
            $execPlan = $executionPlans[0]; // 첫 번째 실행 계획 사용
            $response['execution_plan'] = [
                'action_steps' => $execPlan['mk-a04:hasActionSteps'] ?? [],
                'expected_impact' => $execPlan['mk-a04:hasExpectedImpact'] ?? null,
                'estimated_duration' => $execPlan['mk-a04:hasEstimatedDuration'] ?? null
            ];
        }
        
        // 취약점 컨텍스트 통합
        if (!empty($weakpointContexts)) {
            foreach ($weakpointContexts as $ctx) {
                $response['weakpoint_analysis'][] = [
                    'activity_type' => $ctx['mk-a04:hasActivityType'] ?? null,
                    'activity_category' => $ctx['mk-a04:hasActivityCategory'] ?? null,
                    'weakpoint_severity' => $ctx['mk-a04:hasWeakpointSeverity'] ?? null,
                    'weakpoint_pattern' => $ctx['mk-a04:hasWeakpointPattern'] ?? null,
                    'detection_timestamp' => $ctx['mk-a04:hasDetectionTimestamp'] ?? null
                ];
            }
        }
        
        // 메시지 생성
        $messages = [];
        
        // 1. 기존 액션에서 메시지 추출
        foreach ($actions as $action) {
            if (is_string($action)) {
                // provide_feedback 액션 파싱
                if (preg_match('/provide_feedback:\s*[\'"](.+?)[\'"]/', $action, $matches)) {
                    $feedbackMsg = $matches[1];
                    // 변수 치환 ({{variable}} 형식)
                    $feedbackMsg = $this->substituteVariables($feedbackMsg, $context);
                    $messages[] = $feedbackMsg;
                } elseif (preg_match('/generate_intervention:\s*[\'"](.+?)[\'"]/', $action, $matches)) {
                    $messages[] = '개입 방안: ' . $matches[1];
                }
            } elseif (is_array($action)) {
                if (isset($action['provide_feedback'])) {
                    $feedbackMsg = $action['provide_feedback'];
                    $feedbackMsg = $this->substituteVariables($feedbackMsg, $context);
                    $messages[] = $feedbackMsg;
                }
            }
        }
        
        // 2. 온톨로지 기반 메시지 생성
        $ontologyMessages = $this->formatOntologyResultsAsMessage($response, $context);
        if (!empty($ontologyMessages)) {
            $messages = array_merge($messages, $ontologyMessages);
        }
        
        // 3. 중복 제거 및 메시지 결합
        $messages = array_unique($messages);
        $response['message'] = implode("\n\n", $messages);
        
        // 메시지가 비어있으면 기본 메시지 제공
        if (empty(trim($response['message']))) {
            $response['message'] = '취약점 분석이 완료되었습니다. 보강 방안을 확인해주세요.';
        }
        
        return $response;
    }
    
    /**
     * 온톨로지 결과를 메시지 형식으로 변환
     */
    private function formatOntologyResultsAsMessage($response, $context = null): array {
        $messages = [];
        
        // 보강 방안 메시지 생성
        if ($response['reinforcement_plan']) {
            $plan = $response['reinforcement_plan'];
            $planMsg = "📋 **보강 방안**\n\n";
            
            if ($plan['weakpoint_description']) {
                $planMsg .= "**취약점**: " . $plan['weakpoint_description'] . "\n";
            }
            
            if ($plan['root_cause']) {
                $planMsg .= "**근본 원인**: " . $plan['root_cause'] . "\n";
            }
            
            if ($plan['reinforcement_strategy']) {
                $planMsg .= "**보강 전략**: " . $plan['reinforcement_strategy'] . "\n";
            }
            
            if ($plan['recommended_method']) {
                $planMsg .= "**권장 방법**: " . $plan['recommended_method'] . "\n";
            }
            
            if ($plan['recommended_content']) {
                $planMsg .= "**권장 콘텐츠**: " . $plan['recommended_content'] . "\n";
            }
            
            if ($plan['severity_level']) {
                $severityText = $this->formatSeverityLevel($plan['severity_level']);
                $planMsg .= "**심각도**: " . $severityText . "\n";
            }
            
            if ($plan['priority']) {
                $priorityText = $this->formatPriority($plan['priority']);
                $planMsg .= "**우선순위**: " . $priorityText . "\n";
            }
            
            $messages[] = trim($planMsg);
        }
        
        // 추론 결과 메시지 생성
        if ($response['reasoning_results']) {
            $reasoning = $response['reasoning_results'];
            $reasoningMsg = "🔍 **분석 결과**\n\n";
            
            if ($reasoning['inferred_severity']) {
                $reasoningMsg .= "**추론된 심각도**: " . $this->formatSeverityLevel($reasoning['inferred_severity']) . "\n";
            }
            
            if ($reasoning['inferred_strategy']) {
                $reasoningMsg .= "**추론된 전략**: " . $reasoning['inferred_strategy'] . "\n";
            }
            
            if ($reasoning['inferred_priority']) {
                $reasoningMsg .= "**추론된 우선순위**: " . $this->formatPriority($reasoning['inferred_priority']) . "\n";
            }
            
            if (strlen($reasoningMsg) > 20) { // 실제 내용이 있는 경우만 추가
                $messages[] = trim($reasoningMsg);
            }
        }
        
        // 실행 계획 메시지 생성
        if ($response['execution_plan'] && !empty($response['execution_plan']['action_steps'])) {
            $execPlan = $response['execution_plan'];
            $execMsg = "📝 **실행 계획**\n\n";
            
            $actionSteps = $execPlan['action_steps'];
            if (is_array($actionSteps)) {
                foreach ($actionSteps as $index => $step) {
                    $stepNum = $index + 1;
                    if (is_string($step)) {
                        $execMsg .= "{$stepNum}. {$step}\n";
                    } elseif (is_array($step) && isset($step['description'])) {
                        $execMsg .= "{$stepNum}. {$step['description']}\n";
                    }
                }
            }
            
            if ($execPlan['expected_impact']) {
                $execMsg .= "\n**예상 효과**: " . $execPlan['expected_impact'] . "\n";
            }
            
            $messages[] = trim($execMsg);
        }
        
        return $messages;
    }
    
    /**
     * 심각도 레벨 포맷팅
     */
    private function formatSeverityLevel($severity): string {
        $severityMap = [
            'mk-a04:High' => '높음',
            'mk-a04:Medium' => '보통',
            'mk-a04:Low' => '낮음',
            'High' => '높음',
            'Medium' => '보통',
            'Low' => '낮음'
        ];
        
        return $severityMap[$severity] ?? $severity;
    }
    
    /**
     * 우선순위 포맷팅
     */
    private function formatPriority($priority): string {
        $priorityMap = [
            'mk-a04:High' => '높음',
            'mk-a04:Medium' => '보통',
            'mk-a04:Low' => '낮음',
            'High' => '높음',
            'Medium' => '보통',
            'Low' => '낮음'
        ];
        
        return $priorityMap[$priority] ?? $priority;
    }
    
    /**
     * 변수 치환 ({{variable}} 형식)
     */
    private function substituteVariables($text, $context = null): string {
        if (!$context || !is_array($context)) {
            return $text;
        }
        
        return preg_replace_callback('/\{\{(\w+)\}\}/', function($matches) use ($context) {
            $varName = $matches[1];
            return $context[$varName] ?? $matches[0];
        }, $text);
    }
    
    /**
     * 온톨로지 인스턴스 조회
     */
    private function getOntologyInstance($instanceId, $agentId): ?array {
        try {
            // Agent04의 OntologyEngine 사용
            $ontologyEnginePath = __DIR__ . '/../../agent04_inspect_weakpoints/ontology/OntologyEngine.php';
            if (!file_exists($ontologyEnginePath)) {
                error_log("[AgentGardenService] OntologyEngine not found: {$ontologyEnginePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            require_once($ontologyEnginePath);
            $engine = new OntologyEngine();
            return $engine->getInstance($instanceId);
            
        } catch (Exception $e) {
            error_log("[AgentGardenService] Error getting ontology instance: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 액션에서 답변 생성 (온보딩 정보 분석 포함)
     */
    private function generateResponseFromActions($decision, $userMessage, $context = null) {
        $actions = $decision['actions'] ?? [];
        $response = [
            'status' => 'success',
            'message' => '',
            'questions' => [],
            'suggestions' => [],
            'onboarding_info' => [] // 온보딩 정보 요약
        ];
        
        // 질문 유형 감지 및 상세 분석 리포트 생성
        $matchedRuleId = $decision['rule_id'] ?? '';
        $userMessageLower = mb_strtolower($userMessage, 'UTF-8');
        
        // 첫 수업 관련 질문 감지
        $isFirstClassQuestion = (
            $matchedRuleId === 'Q1_comprehensive_first_class_strategy' ||
            mb_strpos($matchedRuleId, 'Q1_') === 0 ||
            (mb_strpos($userMessageLower, '첫') !== false && 
             (mb_strpos($userMessageLower, '수업') !== false || mb_strpos($userMessageLower, '어떻게') !== false ||
              mb_strpos($userMessageLower, '시작') !== false || mb_strpos($userMessageLower, '도입') !== false ||
              mb_strpos($userMessageLower, '설명 전략') !== false || mb_strpos($userMessageLower, '자료 유형') !== false))
        );
        
        // 커리큘럼/루틴 최적화 관련 질문 감지
        $isCurriculumQuestion = (
            mb_strpos($matchedRuleId, 'Q2_') === 0 ||
            (mb_strpos($userMessageLower, '커리큘럼') !== false || mb_strpos($userMessageLower, '루틴') !== false ||
             mb_strpos($userMessageLower, '최적화') !== false || mb_strpos($userMessageLower, '우선순위') !== false ||
             mb_strpos($userMessageLower, '학습 흐름') !== false || mb_strpos($userMessageLower, '문제 유형 비중') !== false)
        );
        
        // 중장기 성장 전략 관련 질문 감지
        $isGrowthStrategyQuestion = (
            mb_strpos($matchedRuleId, 'Q3_') === 0 ||
            (mb_strpos($userMessageLower, '중장기') !== false || mb_strpos($userMessageLower, '성장') !== false ||
             mb_strpos($userMessageLower, '경시') !== false || mb_strpos($userMessageLower, '진학 목표') !== false ||
             mb_strpos($userMessageLower, '자존감') !== false || mb_strpos($userMessageLower, '피로') !== false ||
             mb_strpos($userMessageLower, '리스크') !== false || mb_strpos($userMessageLower, '트래킹') !== false)
        );
        
        // 첫 수업 관련 질문에 대한 상세 분석 리포트 생성 (예외 처리 포함)
        if ($isFirstClassQuestion && $context !== null && isset($context['student_id'])) {
            try {
                error_log("[Agent01] Generating first class strategy report for student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $detailedReport = $this->generateFirstClassStrategyReport($context['student_id'], $context);
                if ($detailedReport && !empty($detailedReport['report'])) {
                    $response['detailed_report'] = $detailedReport['report'];
                    $response['has_detailed_report'] = true;
                    error_log("[Agent01] First class strategy report generated successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[Agent01] First class strategy report generation returned empty result [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            } catch (Exception $e) {
                // 리포트 생성 실패해도 기본 응답은 반환
                error_log("[Agent01] Error generating first class strategy report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $response['report_generation_error'] = '상세 리포트 생성 중 오류가 발생했습니다. 기본 응답을 제공합니다.';
            }
        }
        
        // 커리큘럼/루틴 최적화 질문에 대한 온톨로지 기반 답변 강화
        if ($isCurriculumQuestion && $context !== null) {
            // 온보딩 컨텍스트 분석 강화
            if (empty($response['onboarding_info']) || empty($response['onboarding_info']['summary'])) {
                $onboardingSummary = $this->analyzeOnboardingContext($context);
                if (!empty($onboardingSummary)) {
                    $response['onboarding_info'] = $onboardingSummary;
                }
            }
        }
        
        // 중장기 성장 전략 질문에 대한 온톨로지 기반 답변 강화
        if ($isGrowthStrategyQuestion && $context !== null) {
            // 온보딩 컨텍스트 분석 강화
            if (empty($response['onboarding_info']) || empty($response['onboarding_info']['summary'])) {
                $onboardingSummary = $this->analyzeOnboardingContext($context);
                if (!empty($onboardingSummary)) {
                    $response['onboarding_info'] = $onboardingSummary;
                }
            }
        }
        
        // 컨텍스트 정보 분석 및 제공 (메시지 생성 후 추가)
        $onboardingSummary = null;
        if ($context !== null && is_array($context)) {
            $onboardingSummary = $this->analyzeOnboardingContext($context);
            if (!empty($onboardingSummary)) {
                $response['onboarding_info'] = $onboardingSummary;
            }
        }
        
        // 온톨로지 결과 활용
        $ontologyResults = $decision['ontology_results'] ?? [];
        $strategyData = null;
        $procedureData = null;
        $reasoningResults = [];
        
        foreach ($ontologyResults as $ontologyResult) {
            if ($ontologyResult['success'] ?? false) {
                // 전략 데이터 추출
                if (isset($ontologyResult['strategy'])) {
                    $strategyData = $ontologyResult['strategy'];
                }
                
                // 절차 데이터 추출
                if (isset($ontologyResult['procedure'])) {
                    $procedureData = $ontologyResult['procedure'];
                }
                
                // 추론 결과 추출
                if (isset($ontologyResult['results'])) {
                    $reasoningResults = array_merge($reasoningResults, $ontologyResult['results']);
                }
            }
        }
        
        // 온톨로지 기반 메시지 강화
        if ($strategyData && $isFirstClassQuestion) {
            $strategy = $strategyData['strategy'] ?? [];
            $strategyMsg = "\n\n📋 **첫 수업 전략 (온톨로지 기반)**\n";
            
            if (isset($strategy['mk:hasMathLearningStyle'])) {
                $strategyMsg .= "- 학습 스타일: " . $strategy['mk:hasMathLearningStyle'] . "\n";
            }
            if (isset($strategy['mk:hasStudyStyle'])) {
                $strategyMsg .= "- 공부 스타일: " . $strategy['mk:hasStudyStyle'] . "\n";
            }
            if (isset($strategy['mk:hasMathConfidence'])) {
                $strategyMsg .= "- 수학 자신감: " . $strategy['mk:hasMathConfidence'] . "/10\n";
            }
            if (isset($strategy['mk:recommendsUnits'])) {
                $strategyMsg .= "- 추천 단원: " . implode(', ', $strategy['mk:recommendsUnits']) . "\n";
            }
            
            $response['message'] .= $strategyMsg;
            $response['ontology_strategy'] = $strategy;
        }
        
        // 절차 기반 메시지 강화
        if ($procedureData && $isFirstClassQuestion) {
            $procedureSteps = $procedureData['procedure_steps'] ?? [];
            if (!empty($procedureSteps)) {
                $procedureMsg = "\n\n📝 **수업 절차**\n";
                foreach ($procedureSteps as $step) {
                    $order = $step['mk:stepOrder'] ?? 0;
                    $type = $step['mk:stepType'] ?? '';
                    $desc = $step['mk:stepDescription'] ?? '';
                    $procedureMsg .= "{$order}. [{$type}] {$desc}\n";
                }
                $response['message'] .= $procedureMsg;
                $response['ontology_procedure'] = $procedureSteps;
            }
        }
        
        // 추론 결과 활용
        if (!empty($reasoningResults)) {
            foreach ($reasoningResults as $reasoning) {
                $reasoningData = $reasoning['reasoning'] ?? [];
                if (!empty($reasoningData)) {
                    if (isset($reasoningData['recommendsUnits'])) {
                        $response['suggestions'][] = '추천 단원: ' . implode(', ', $reasoningData['recommendsUnits']);
                    }
                    if (isset($reasoningData['recommendsDifficulty'])) {
                        $response['suggestions'][] = '추천 난이도: ' . $reasoningData['recommendsDifficulty'];
                    }
                }
            }
        }
        
        // 디버깅: actions 로그
        error_log("[Agent01] Actions count: " . count($actions) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[Agent01] Actions: " . json_encode($actions, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        foreach ($actions as $action) {
            if (is_string($action)) {
                // 문자열 형식: "key: value"
                if (preg_match('/^display_message:\s*(.+)$/i', $action, $matches)) {
                    $msg = trim($matches[1], "'\"");
                    // 여러 줄바꿈을 하나로, 앞뒤 공백 제거
                    $msg = preg_replace('/\n{2,}/', "\n", trim($msg));
                    $response['message'] .= $msg . "\n";
                } elseif (preg_match('/^question:\s*(.+)$/i', $action, $matches)) {
                    $response['questions'][] = trim($matches[1], "'\"");
                } elseif (preg_match('/^collect_info:\s*(.+)$/i', $action, $matches)) {
                    $infoType = trim($matches[1], "'\"");
                    if (empty($response['message'])) {
                        $response['message'] = "{$infoType} 정보를 수집하겠습니다.\n";
                    }
                }
            } elseif (is_array($action)) {
                // Python parse_action이 반환하는 형식: {"display_message": "메시지"}
                if (isset($action['display_message'])) {
                    $msg = $action['display_message'];
                    // 여러 줄바꿈을 하나로, 앞뒤 공백 제거
                    $msg = preg_replace('/\n{2,}/', "\n", trim($msg));
                    $response['message'] .= $msg . "\n";
                } elseif (isset($action['question'])) {
                    $response['questions'][] = $action['question'];
                } elseif (isset($action['collect_info'])) {
                    $infoType = $action['collect_info'];
                    if (empty($response['message'])) {
                        $response['message'] = "{$infoType} 정보를 수집하겠습니다.\n";
                    }
                } elseif (isset($action['validate'])) {
                    // validate 액션은 무시하거나 메시지에 추가
                    if (!empty($action['validate'])) {
                        // 필요시 메시지에 추가
                    }
                } elseif (isset($action['communication_mode'])) {
                    // communication_mode 액션 처리 (간결하게)
                    if (!empty($action['communication_mode'])) {
                        $msg = trim($action['communication_mode']);
                        $msg = preg_replace('/\n{2,}/', "\n", $msg);
                        $response['message'] .= $msg . "\n";
                    }
                } elseif (isset($action['recommend_path'])) {
                    // recommend_path 액션 처리
                    if (!empty($action['recommend_path'])) {
                        $response['suggestions'][] = $action['recommend_path'];
                    }
                } elseif (isset($action['analyze'])) {
                    // analyze 액션은 내부 처리용이므로 무시
                } elseif (isset($action['generate_description'])) {
                    // generate_description 액션은 내부 처리용이므로 무시
                } elseif (isset($action['load_db'])) {
                    // load_db 액션은 내부 처리용이므로 무시
                } elseif (isset($action['validate_all'])) {
                    // validate_all 액션은 내부 처리용이므로 무시
                } elseif (isset($action['type'])) {
                    // 기존 형식: {"type": "display_message", "message": "메시지"}
                    if ($action['type'] === 'display_message' && isset($action['message'])) {
                        $msg = $action['message'];
                        $msg = preg_replace('/\n{2,}/', "\n", trim($msg));
                        $response['message'] .= $msg . "\n";
                    } elseif ($action['type'] === 'question' && isset($action['question'])) {
                        $response['questions'][] = $action['question'];
                    }
                }
            }
        }
        
        // 메시지가 비어있거나 default_rule이 사용된 경우 사용자 메시지 분석
        if (empty($response['message']) || (isset($decision['rule_id']) && $decision['rule_id'] === 'default')) {
            $analyzedResponse = $this->analyzeUserMessage($userMessage, $decision);
            if (!empty($analyzedResponse['message'])) {
                $response['message'] = $analyzedResponse['message'];
            }
            if (!empty($analyzedResponse['questions'])) {
                $response['questions'] = array_merge($response['questions'], $analyzedResponse['questions']);
            }
        }
        
        // 메시지가 여전히 비어있으면 기본 메시지 사용
        if (empty($response['message']) && !empty($response['questions'])) {
            $response['message'] = "다음 질문에 답변해주시면 맞춤형 학습 계획을 수립하는데 도움이 됩니다.\n";
        }
        
        // 질문이 있으면 메시지에 추가 (간결하게)
        if (!empty($response['questions'])) {
            $response['message'] .= "\n" . implode("\n", array_map(function($q) {
                return "• " . $q;
            }, $response['questions']));
        }
        
        // 최종 메시지 정리 (여러 줄바꿈을 하나로, 앞뒤 공백 제거)
        $response['message'] = preg_replace('/\n{3,}/', "\n\n", trim($response['message']));
        
        // 메시지가 여전히 비어있으면 기본 안내 메시지
        if (empty($response['message'])) {
            $response['message'] = "안녕하세요! 온보딩 에이전트입니다. 학습 스타일, 목표, 진도 등에 대해 알려주시면 맞춤형 학습 계획을 도와드리겠습니다.";
        }
        
        // 상세 리포트가 있으면 메시지에 간결하게 추가
        if (isset($response['has_detailed_report']) && $response['has_detailed_report']) {
            $response['message'] .= "\n\n📊 상세 분석 리포트가 생성되었습니다.";
        }
        
        // 온보딩 정보를 메시지 뒤에 추가 (정보가 있는 경우에만, 간결하게)
        if ($onboardingSummary !== null && !empty($onboardingSummary['summary'])) {
            $response['message'] .= "\n\n📋 " . trim($onboardingSummary['summary']);
            
            // 완성도 정보 추가
            if (isset($onboardingSummary['completion_rate'])) {
                $completionRate = round($onboardingSummary['completion_rate'], 1);
                $response['message'] .= " (완성도: " . $completionRate . "%)";
            }
        }
        
        // 적용된 룰 정보 추가 (간결하게)
        if (isset($decision['rule_id']) && !empty($decision['rule_id']) && $decision['rule_id'] !== 'default') {
            $ruleId = $decision['rule_id'];
            // 룰 ID를 읽기 쉽게 변환 (예: Q1_comprehensive_first_class_strategy -> Q1)
            $ruleDisplay = preg_replace('/^([A-Z]\d+).*$/', '$1', $ruleId);
            if ($ruleDisplay === $ruleId) {
                // 패턴이 맞지 않으면 간단히 표시
                $ruleDisplay = str_replace(['_', '-'], ' ', $ruleId);
                $ruleDisplay = ucwords(strtolower($ruleDisplay));
            }
            $response['applied_rule'] = $ruleDisplay;
            $response['rule_id'] = $ruleId;
            
            // 룰 설명이 있으면 추가
            if (isset($decision['rule_description'])) {
                $response['rule_description'] = $decision['rule_description'];
            }
        }
        
        // 사용된 모든 룰 목록 (confidence가 있는 룰들)
        $usedRules = [];
        
        // matched_rule이 있으면 추가
        if (isset($decision['rule_id']) && !empty($decision['rule_id']) && $decision['rule_id'] !== 'default') {
            $ruleId = $decision['rule_id'];
            $ruleInfo = $this->getRuleInfoFromYaml($ruleId);
            $ruleDisplay = preg_replace('/^([A-Z]\d+).*$/', '$1', $ruleId);
            if ($ruleDisplay === $ruleId) {
                $ruleDisplay = str_replace(['_', '-'], ' ', $ruleId);
                $ruleDisplay = ucwords(strtolower($ruleDisplay));
            }
            $usedRules[] = [
                'id' => $ruleId,
                'display' => $ruleDisplay,
                'description' => $ruleInfo['description'] ?? '',
                'rationale' => $ruleInfo['rationale'] ?? '',
                'confidence' => $decision['confidence'] ?? null,
                'source' => 'rules.yaml'
            ];
        }
        
        // evaluated_rules가 있으면 추가
        if (isset($decision['evaluated_rules']) && is_array($decision['evaluated_rules'])) {
            foreach ($decision['evaluated_rules'] as $rule) {
                if (isset($rule['rule_id']) && isset($rule['matched']) && $rule['matched']) {
                    $ruleId = $rule['rule_id'];
                    
                    // 이미 추가된 룰이면 스킵
                    $alreadyAdded = false;
                    foreach ($usedRules as $ur) {
                        if ($ur['id'] === $ruleId) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if ($alreadyAdded) {
                        continue;
                    }
                    
                    $ruleInfo = $this->getRuleInfoFromYaml($ruleId);
                    $ruleDisplay = preg_replace('/^([A-Z]\d+).*$/', '$1', $ruleId);
                    if ($ruleDisplay === $ruleId) {
                        $ruleDisplay = str_replace(['_', '-'], ' ', $ruleId);
                        $ruleDisplay = ucwords(strtolower($ruleDisplay));
                    }
                    $usedRules[] = [
                        'id' => $ruleId,
                        'display' => $ruleDisplay,
                        'description' => $ruleInfo['description'] ?? '',
                        'rationale' => $ruleInfo['rationale'] ?? '',
                        'confidence' => $rule['confidence'] ?? null,
                        'source' => 'rules.yaml'
                    ];
                }
            }
        }
        
        if (!empty($usedRules)) {
            $response['used_rules'] = $usedRules;
        }
        
        // ========================================
        // 페르소나 톤/페이스 적용 (2025-12-03 추가)
        // 메시지를 페르소나 특성에 맞게 스타일 변환
        // ========================================
        if ($context !== null && isset($context['persona_tone'])) {
            $response['message'] = $this->applyPersonaToneAndPace(
                $response['message'],
                $context['persona_tone'] ?? 'Professional',
                $context['persona_pace'] ?? 'moderate',
                $context['persona_id'] ?? 'unknown'
            );

            // 페르소나 적용 정보 추가
            $response['persona_applied'] = [
                'tone' => $context['persona_tone'] ?? 'Professional',
                'pace' => $context['persona_pace'] ?? 'moderate',
                'persona_id' => $context['persona_id'] ?? 'unknown'
            ];

            error_log("[Agent01] Persona tone/pace applied: tone=" . ($context['persona_tone'] ?? 'Professional') .
                     ", pace=" . ($context['persona_pace'] ?? 'moderate') .
                     " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }

        return $response;
    }

    /**
     * 페르소나 톤/페이스 적용 함수 (2025-12-03 추가)
     *
     * 학생 페르소나 특성에 맞게 메시지 스타일을 조정합니다.
     *
     * @param string $message 원본 메시지
     * @param string $tone 톤 (Gentle, Professional, Encouraging, Warm, Collaborative)
     * @param string $pace 페이스 (slow, moderate, normal, very_slow)
     * @param string $personaId 페르소나 ID (S0_P1 ~ S0_P5)
     * @return string 스타일이 적용된 메시지
     */
    private function applyPersonaToneAndPace($message, $tone, $pace, $personaId) {
        if (empty($message)) {
            return $message;
        }

        $styledMessage = $message;

        // ========================================
        // 톤(Tone) 적용
        // ========================================
        switch ($tone) {
            case 'Gentle':
                // 부드러운 톤: 안심시키는 표현 추가, 압박감 제거
                // S0_P2 (방어적 최소 응답자) 대상
                $styledMessage = $this->applyGentleTone($styledMessage);
                break;

            case 'Warm':
                // 따뜻한 톤: 정서적 지지 강화, 공감 표현
                // S0_P4 (불안한 완벽주의자) 대상
                $styledMessage = $this->applyWarmTone($styledMessage);
                break;

            case 'Encouraging':
                // 격려하는 톤: 긍정적 강화, 성취 인정
                // S0_P3 (과대 포장형) 대상
                $styledMessage = $this->applyEncouragingTone($styledMessage);
                break;

            case 'Collaborative':
                // 협력적 톤: "우리" 언어 사용, 파트너십 강조
                // S0_P5 (무관심한 수동적) 대상
                $styledMessage = $this->applyCollaborativeTone($styledMessage);
                break;

            case 'Professional':
            default:
                // 전문적 톤: 명확하고 객관적인 표현 유지
                // S0_P1 (솔직한 자기 분석가) 대상
                // 기본 스타일 유지
                break;
        }

        // ========================================
        // 페이스(Pace) 적용
        // ========================================
        switch ($pace) {
            case 'very_slow':
                // 매우 천천히: 짧은 문장, 단계별 설명, 충분한 여백
                $styledMessage = $this->applyVerySlowPace($styledMessage);
                break;

            case 'slow':
                // 천천히: 문장 분리, 핵심 강조
                $styledMessage = $this->applySlowPace($styledMessage);
                break;

            case 'moderate':
            case 'normal':
            default:
                // 보통: 표준 문장 구조 유지
                break;
        }

        return $styledMessage;
    }

    /**
     * Gentle 톤 적용 (방어적 학생용)
     */
    private function applyGentleTone($message) {
        // 시작 부분에 안심 표현 추가
        $gentleOpeners = [
            '괜찮아요, 천천히 해봐요. ',
            '부담 갖지 않아도 돼요. ',
            '편하게 시작해볼까요? '
        ];

        // 첫 문장 앞에 opener 추가 (이미 있으면 스킵)
        $hasGentleStart = false;
        foreach ($gentleOpeners as $opener) {
            if (mb_strpos($message, mb_substr($opener, 0, 5)) !== false) {
                $hasGentleStart = true;
                break;
            }
        }

        if (!$hasGentleStart) {
            $randomOpener = $gentleOpeners[array_rand($gentleOpeners)];
            $message = $randomOpener . "\n\n" . $message;
        }

        // 마지막에 안심 메시지 추가
        if (mb_strpos($message, '언제든') === false && mb_strpos($message, '괜찮') === false) {
            $message .= "\n\n언제든 편하게 질문해 주세요. 🙂";
        }

        return $message;
    }

    /**
     * Warm 톤 적용 (불안한 학생용)
     */
    private function applyWarmTone($message) {
        // 시작 부분에 공감 표현 추가
        $warmOpeners = [
            '걱정하지 마세요, 함께 차근차근 해볼게요. ',
            '잘하고 계세요! 조금씩 나아가면 돼요. ',
            '완벽하지 않아도 괜찮아요. '
        ];

        $hasWarmStart = false;
        foreach ($warmOpeners as $opener) {
            if (mb_strpos($message, mb_substr($opener, 0, 5)) !== false) {
                $hasWarmStart = true;
                break;
            }
        }

        if (!$hasWarmStart) {
            $randomOpener = $warmOpeners[array_rand($warmOpeners)];
            $message = $randomOpener . "\n\n" . $message;
        }

        // 마지막에 지지 메시지 추가
        if (mb_strpos($message, '응원') === false && mb_strpos($message, '함께') === false) {
            $message .= "\n\n항상 응원하고 있어요. 💪";
        }

        return $message;
    }

    /**
     * Encouraging 톤 적용 (과대포장형 학생용)
     */
    private function applyEncouragingTone($message) {
        // 시작 부분에 인정 표현 추가
        $encouragingOpeners = [
            '좋은 의욕이에요! ',
            '그 자신감이 좋아요! ',
            '멋진 목표네요! '
        ];

        $hasEncouragingStart = false;
        foreach ($encouragingOpeners as $opener) {
            if (mb_strpos($message, mb_substr($opener, 0, 5)) !== false) {
                $hasEncouragingStart = true;
                break;
            }
        }

        if (!$hasEncouragingStart) {
            $randomOpener = $encouragingOpeners[array_rand($encouragingOpeners)];
            $message = $randomOpener . "\n\n" . $message;
        }

        // 현실적 조언 부드럽게 추가
        if (mb_strpos($message, '한 단계씩') === false && mb_strpos($message, '차근차근') === false) {
            $message .= "\n\n한 단계씩 함께 나아가면 더 멋진 결과가 있을 거예요! 🎯";
        }

        return $message;
    }

    /**
     * Collaborative 톤 적용 (수동적 학생용)
     */
    private function applyCollaborativeTone($message) {
        // "우리" 언어로 변환
        $message = str_replace('해보세요', '같이 해봐요', $message);
        $message = str_replace('하시면', '하면 우리가', $message);
        $message = str_replace('알려주세요', '함께 찾아봐요', $message);

        // 시작 부분에 협력 표현 추가
        $collaborativeOpeners = [
            '함께 알아볼까요? ',
            '같이 해보면 재미있을 거예요! ',
            '우리 팀으로 시작해봐요. '
        ];

        $hasCollaborativeStart = false;
        foreach ($collaborativeOpeners as $opener) {
            if (mb_strpos($message, mb_substr($opener, 0, 3)) !== false) {
                $hasCollaborativeStart = true;
                break;
            }
        }

        if (!$hasCollaborativeStart) {
            $randomOpener = $collaborativeOpeners[array_rand($collaborativeOpeners)];
            $message = $randomOpener . "\n\n" . $message;
        }

        // 마지막에 참여 유도 메시지 추가
        if (mb_strpos($message, '함께') === false || mb_strpos($message, '같이') === false) {
            $message .= "\n\n어떻게 생각해요? 함께 정해봐요! 🤝";
        }

        return $message;
    }

    /**
     * Very Slow 페이스 적용 (매우 천천히)
     */
    private function applyVerySlowPace($message) {
        // 긴 문장을 짧게 분리
        // 마침표 뒤에 줄바꿈 추가
        $message = preg_replace('/\. ([가-힣A-Za-z])/u', ".\n\n$1", $message);

        // 핵심 포인트에 번호 매기기 (이미 번호가 없는 경우)
        if (preg_match('/^[^\d\n]/m', $message) && mb_strpos($message, '1.') === false) {
            // 첫 번째 문장 후에 "하나씩 살펴볼게요" 추가
            $firstPeriod = mb_strpos($message, '.');
            if ($firstPeriod !== false && $firstPeriod < 100) {
                $before = mb_substr($message, 0, $firstPeriod + 1);
                $after = mb_substr($message, $firstPeriod + 1);
                $message = $before . "\n\n🔹 하나씩 천천히 살펴볼게요.\n" . trim($after);
            }
        }

        return $message;
    }

    /**
     * Slow 페이스 적용 (천천히)
     */
    private function applySlowPace($message) {
        // 문장 사이에 약간의 여백 추가
        $message = preg_replace('/\.(\s*)([가-힣A-Za-z])/u', ".\n\n$2", $message);

        return $message;
    }

    /**
     * rules.yaml에서 룰 정보 가져오기
     */
    private function getRuleInfoFromYaml($ruleId) {
        static $rulesCache = null;
        static $rulesFilePath = null;
        
        // rules.yaml 파일 경로 찾기
        if ($rulesFilePath === null) {
            $ruleEvaluatorPath = __DIR__ . '/../../agent01_onboarding/rules/rule_evaluator.php';
            if (file_exists($ruleEvaluatorPath)) {
                $rulesFilePath = __DIR__ . '/../../agent01_onboarding/rules/rules.yaml';
            } else {
                return ['description' => '', 'rationale' => ''];
            }
        }
        
        // 캐시가 없으면 rules.yaml 파일 읽기
        if ($rulesCache === null) {
            if (!file_exists($rulesFilePath)) {
                return ['description' => '', 'rationale' => ''];
            }
            
            $yamlContent = file_get_contents($rulesFilePath);
            if (empty($yamlContent)) {
                return ['description' => '', 'rationale' => ''];
            }
            
            // YAML 파싱 (간단한 정규식 기반 파싱)
            $rulesCache = [];
            
            // rule_id로 룰 찾기
            preg_match_all('/rule_id:\s*["\']?(' . preg_quote($ruleId, '/') . ')["\']?/i', $yamlContent, $matches, PREG_OFFSET_CAPTURE);
            
            foreach ($matches[1] as $match) {
                $offset = $match[1];
                
                // 해당 룰의 description과 rationale 찾기
                $ruleSection = substr($yamlContent, $offset - 100, 2000); // 충분한 범위
                
                $description = '';
                $rationale = '';
                
                // description 찾기
                if (preg_match('/description:\s*["\']([^"\']+)["\']/i', $ruleSection, $descMatch)) {
                    $description = $descMatch[1];
                } elseif (preg_match('/description:\s*([^\n]+)/i', $ruleSection, $descMatch)) {
                    $description = trim($descMatch[1]);
                }
                
                // rationale 찾기
                if (preg_match('/rationale:\s*["\']([^"\']+)["\']/i', $ruleSection, $ratMatch)) {
                    $rationale = $ratMatch[1];
                } elseif (preg_match('/rationale:\s*([^\n]+)/i', $ruleSection, $ratMatch)) {
                    $rationale = trim($ratMatch[1]);
                }
                
                $rulesCache[$ruleId] = [
                    'description' => $description,
                    'rationale' => $rationale
                ];
                
                break; // 첫 번째 매치만 사용
            }
        }
        
        return $rulesCache[$ruleId] ?? ['description' => '', 'rationale' => ''];
    }
    
    /**
     * 첫 수업 전략 상세 분석 리포트 생성
     */
    private function generateFirstClassStrategyReport($studentId, $context = null) {
        try {
            // report_generator.php의 함수들 사용
            $reportGeneratorPath = __DIR__ . '/../../agent01_onboarding/report_generator.php';
            $reportServicePath = __DIR__ . '/../../agent01_onboarding/report_service.php';
            
            if (!file_exists($reportGeneratorPath) || !file_exists($reportServicePath)) {
                error_log("[Agent01] Report generator files not found [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            require_once($reportServicePath);
            require_once($reportGeneratorPath);
            
            // 온보딩 데이터 가져오기
            $data = getOnboardingData($studentId);
            if (!$data['success']) {
                error_log("[Agent01] Failed to get onboarding data [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            $info = $data['info'] ?? [];
            $assessment = $data['assessment'] ?? [];
            
            // OpenAI 설정 로드
            load_openai_config();
            
            // 첫 수업 전략만 생성하는 프롬프트 구성
            $system = [
                'role' => 'system',
                'content' => '너는 학습코칭 온보딩 분석 어시스턴트다. 온보딩/평가 데이터를 바탕으로 한국어 리포트를 작성한다. 모든 분석과 제안은 주어진 데이터에 근거해 구체적으로 연결하고, 누락된 값은 언급하지 말고 자연스럽게 생략한다. 개인정보는 노출하지 말고, 실행지향적인 권고를 제시하라.'
            ];
            
            $userPayload = [
                'student' => [
                    'name' => $info['studentName'] ?? '',
                    'mbti' => $info['mbti_type'] ?? '',
                    'learning_style' => $info['learning_style'] ?? '',
                ],
                'onboarding' => [
                    'math_level' => $info['math_level'] ?? '',
                    'concept_progress' => $info['concept_progress'] ?? '',
                    'advanced_progress' => $info['advanced_progress'] ?? '',
                    'problem_preference' => $info['problem_preference'] ?? '',
                    'exam_style' => $info['exam_style'] ?? '',
                    'math_confidence' => $info['math_confidence'] ?? null,
                    'parent_style' => $info['parent_style'] ?? '',
                    'stress_level' => $info['stress_level'] ?? '',
                    'short_term_goal' => $info['short_term_goal'] ?? '',
                    'mid_term_goal' => $info['mid_term_goal'] ?? '',
                    'long_term_goal' => $info['long_term_goal'] ?? '',
                    'weekly_hours' => $info['weekly_hours'] ?? null,
                    'academy_experience' => $info['academy_experience'] ?? '',
                ],
                'assessment' => $assessment,
                'instructions' => '**중요: 순수 마크다운 텍스트만 작성하라. 절대로 다음을 사용하지 마라:**
- 리포트 제목이나 헤더 (예: "학습코칭 온보딩 리포트", "온보딩 리포트" 등) - 바로 본문부터 시작하라
- 생성 시각이나 날짜 정보
- HTML 태그 (예: <div>, <p>, <br>, <style>, <body> 등 모든 HTML 태그)
- CSS 스타일 코드 (예: .class{...}, body{...}, <style> 태그 등)
- 코드블록 (예: ```html, ```markdown, ``` 등)
- HTML 엔티티 (예: &nbsp; 등)

**바로 ## 첫 수업 시작 전략부터 시작하라.** 제목이나 헤더 없이 본문 내용만 작성하라.

아래 구조의 **순수 마크다운 형식**으로만 한국어 리포트를 작성하라. 각 항목은 제공된 데이터(onboarding, assessment, student)에 근거하여 작성하고, 누락된 값은 언급하지 말고 해당 항목을 생략한다. 표와 리스트를 적절히 활용하되, **오직 마크다운 문법만 사용**한다 (## 헤더, **볼드**, - 목록, 1. 번호목록 등). 빈 줄은 최소화하고, 섹션 간에는 한 줄만 띄어라. **모든 항목을 완전히 작성하라. 중간에 끊기지 않도록 충분히 상세하게 작성하라.**

## 첫 수업 시작 전략

이 학생의 현재 수학 학습 맥락을 종합해서, 첫 수업에서 무엇을 어떻게 시작해야 할지 상세히 알려줘.

온보딩 정보(수학 수준: ' . ($info['math_level'] ?? '') . ', 개념 진도: ' . ($info['concept_progress'] ?? '') . ', 심화 진도: ' . ($info['advanced_progress'] ?? '') . ', 문제풀이 성향: ' . ($info['problem_preference'] ?? '') . ', 시험 대비 성향: ' . ($info['exam_style'] ?? '') . ', 자신감: ' . ($info['math_confidence'] ?? '') . '/10)를 근거로, 다음을 포함하여 작성:

1. **수업 도입 루틴** (자신감 수준에 맞춘 부드러운 도입 또는 도전적 도입)
2. **설명 전략** (학습 스타일별 맞춤형 설명 방식)
3. **자료 유형 추천** (진도와 교재에 맞춘 자료 선정)
4. **첫 수업 30분 진행 안** (분 단위 상세 계획)
5. **1주/2주 루틴 샘플** (시간·콘텐츠 수치 포함)
6. **부모 커뮤니케이션 문장** 2줄
7. **마이크로 습관** 3개
8. **리스크 신호와 대응** (체크리스트)

문단 간 연결 문장을 추가해 전체 흐름이 자연스럽게 이어지도록 작성한다. **마크다운 형식만 사용하고, HTML 태그나 코드블록(```)은 절대 사용하지 마라.**'
            ];
            
            $messages = [
                $system,
                ['role' => 'user', 'content' => json_encode($userPayload, JSON_UNESCAPED_UNICODE)]
            ];
            
            // 리포트 생성용 max_tokens 증가 (기본값 1000 → 8000, 중간 잘림 방지)
            // 상수가 이미 정의되어 있을 수 있으므로 GLOBALS를 통해 오버라이드
            $GLOBALS['OPENAI_MAX_TOKENS_OVERRIDE'] = 8000;
            
            // 상수가 정의되지 않았으면 정의
            if (!defined('OPENAI_MAX_TOKENS')) {
                define('OPENAI_MAX_TOKENS', 8000);
            }
            
            // OpenAI API 호출 (타임아웃 60초로 설정, max_tokens 8000)
            error_log("[Agent01] Calling OpenAI API for first class strategy report (max_tokens: 8000) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            $startTime = microtime(true);
            $ai = call_openai_chat($messages);
            
            // 오버라이드 제거 (다른 호출에 영향 주지 않도록)
            unset($GLOBALS['OPENAI_MAX_TOKENS_OVERRIDE']);
            
            $elapsedTime = round((microtime(true) - $startTime) * 1000);
            error_log("[Agent01] OpenAI API call completed in {$elapsedTime}ms [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            if (!$ai['success']) {
                error_log("[Agent01] OpenAI API call failed: " . ($ai['error'] ?? 'Unknown error') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            $markdown = $ai['content'];
            
            // 리포트 헤더/제목 제거 (예: "학습코칭 온보딩 리포트", "온보딩 리포트 (GPT)" 등)
            $markdown = preg_replace('/^.*?(학습코칭|온보딩).*?리포트.*?\n/i', '', $markdown);
            $markdown = preg_replace('/^.*?생성\s*시각.*?\n/i', '', $markdown);
            $markdown = preg_replace('/^.*?생성시각.*?\n/i', '', $markdown);
            $markdown = preg_replace('/^.*?생성\s*시각.*?[0-9]{4}.*?\n/i', '', $markdown);
            
            // 중복된 "학습코칭 온보딩 리포트" 제거
            $markdown = preg_replace('/^.*?학습코칭\s*온보딩\s*리포트.*?\n/i', '', $markdown);
            
            // 앞부분의 불필요한 텍스트 제거
            $markdown = preg_replace('/^[^\#]*?##/s', '##', $markdown);
            
            // CSS 스타일 블록 제거 (예: .class{...}, body{...} 등)
            $markdown = preg_replace('/\.[a-zA-Z0-9_-]+\s*\{[^}]*\}/s', '', $markdown);
            $markdown = preg_replace('/[a-zA-Z0-9_-]+\s*\{[^}]*\}/s', '', $markdown);
            $markdown = preg_replace('/\{[^}]*\}/s', '', $markdown);
            
            // <style> 태그와 내용 제거
            $markdown = preg_replace('/<style[^>]*>.*?<\/style>/is', '', $markdown);
            
            // 마크다운 코드블록 제거 (```html, ```markdown, ``` 등 모든 코드블록)
            $markdown = preg_replace('/```[a-z]*\s*\n?/i', '', $markdown);
            $markdown = preg_replace('/```\s*\n?/', '', $markdown);
            $markdown = preg_replace('/```/', '', $markdown);
            
            // HTML 태그 완전 제거 (혹시 포함된 경우)
            $markdown = strip_tags($markdown);
            
            // HTML 엔티티 디코딩
            $markdown = html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            
            // CSS 관련 키워드가 포함된 줄 제거
            $lines = explode("\n", $markdown);
            $cleanedLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                // CSS 스타일 관련 키워드가 포함된 줄 제거
                if (preg_match('/^(\.|@media|body|html|font-family|background|color|margin|padding|border|display|grid|flex)/i', $trimmed)) {
                    continue;
                }
                // CSS 속성이 포함된 줄 제거
                if (preg_match('/\{[^}]*\}/', $trimmed) && preg_match('/[:;]/', $trimmed)) {
                    continue;
                }
                $cleanedLines[] = $line;
            }
            $markdown = implode("\n", $cleanedLines);
            
            // 연속된 빈 줄 제거 (3개 이상 -> 2개로)
            $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
            
            // 앞뒤 공백 정리
            $markdown = trim($markdown);
            
            // 각 줄의 앞뒤 공백 제거
            $lines = explode("\n", $markdown);
            $cleanedLines = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                // 빈 줄은 하나만 유지
                if ($trimmed === '' && (!empty($cleanedLines) && end($cleanedLines) === '')) {
                    continue;
                }
                $cleanedLines[] = $trimmed;
            }
            $markdown = implode("\n", $cleanedLines);
            
            // 최종 정리
            $markdown = trim($markdown);
            
            // 마크다운을 그대로 반환 (클라이언트에서 렌더링)
            return [
                'success' => true,
                'report' => $markdown,
                'report_type' => 'markdown',
                'generated_at' => time()
            ];
            
        } catch (Exception $e) {
            error_log("[Agent01] Error generating first class strategy report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }

    /**
     * 온보딩 컨텍스트 분석 및 요약 생성
     */
    private function analyzeOnboardingContext($context) {
        $summary = [];
        $infoItems = [];
        $missingItems = [];
        
        // 학생 기본 정보
        if (!empty($context['student_name'])) {
            $infoItems[] = "학생명: " . $context['student_name'];
        }
        
        // 수학 수준 정보
        if (!empty($context['math_level'])) {
            $infoItems[] = "수학 수준: " . $context['math_level'];
        } else {
            $missingItems[] = "수학 수준";
        }
        
        // 수학 자신감
        if (!empty($context['math_confidence'])) {
            $infoItems[] = "수학 자신감: " . $context['math_confidence'];
        } else {
            $missingItems[] = "수학 자신감";
        }
        
        // 학습 스타일
        if (!empty($context['study_style'])) {
            $infoItems[] = "학습 스타일: " . $context['study_style'];
        } elseif (!empty($context['math_learning_style'])) {
            $infoItems[] = "수학 학습 스타일: " . $context['math_learning_style'];
        } else {
            $missingItems[] = "학습 스타일";
        }
        
        // MBTI
        if (!empty($context['mbti_type'])) {
            $infoItems[] = "MBTI: " . $context['mbti_type'];
        }
        
        // 진도 정보
        $progressInfo = [];
        if (!empty($context['concept_progress'])) {
            $progressInfo[] = "개념 진도: " . $context['concept_progress'];
        } else {
            $missingItems[] = "개념 진도";
        }
        if (!empty($context['advanced_progress'])) {
            $progressInfo[] = "심화 진도: " . $context['advanced_progress'];
        } else {
            $missingItems[] = "심화 진도";
        }
        if (!empty($progressInfo)) {
            $infoItems[] = implode(", ", $progressInfo);
        }
        
        // 학원 정보
        if (!empty($context['academy_name'])) {
            $academyInfo = "학원: " . $context['academy_name'];
            if (!empty($context['academy_grade'])) {
                $academyInfo .= " (" . $context['academy_grade'] . ")";
            }
            $infoItems[] = $academyInfo;
        } else {
            $missingItems[] = "학원 정보";
        }
        
        // 수학 성적 정보
        if (!empty($context['math_recent_score'])) {
            $scoreInfo = "최근 수학 성적: " . $context['math_recent_score'];
            if (!empty($context['math_recent_ranking'])) {
                $scoreInfo .= " (등수: " . $context['math_recent_ranking'] . ")";
            }
            $infoItems[] = $scoreInfo;
        } else {
            $missingItems[] = "수학 성적 정보";
        }
        
        // 취약 단원
        if (!empty($context['math_weak_units']) && is_array($context['math_weak_units']) && count($context['math_weak_units']) > 0) {
            $infoItems[] = "취약 단원: " . implode(", ", $context['math_weak_units']);
        }
        
        // 학습 목표
        if (!empty($context['goals']['long_term'])) {
            $infoItems[] = "장기 목표: " . $context['goals']['long_term'];
        } else {
            $missingItems[] = "학습 목표";
        }
        
        // 주당 학습 시간
        if (!empty($context['study_hours_per_week'])) {
            $infoItems[] = "주당 학습 시간: " . $context['study_hours_per_week'] . "시간";
        }
        
        // 요약 메시지 생성
        $summaryText = "";
        if (!empty($infoItems)) {
            $summaryText .= "현재 수집된 온보딩 정보:\n";
            foreach ($infoItems as $item) {
                $summaryText .= "• " . $item . "\n";
            }
        }
        
        if (!empty($missingItems)) {
            if (!empty($summaryText)) {
                $summaryText .= "\n";
            }
            $summaryText .= "추가로 필요한 정보:\n";
            foreach ($missingItems as $item) {
                $summaryText .= "• " . $item . "\n";
            }
        }
        
        $summary['summary'] = trim($summaryText);
        $summary['collected_info'] = $infoItems;
        $summary['missing_info'] = $missingItems;
        $summary['completion_rate'] = empty($infoItems) && empty($missingItems) ? 0 : 
            (count($infoItems) / (count($infoItems) + count($missingItems))) * 100;
        
        return $summary;
    }

    /**
     * 사용자 메시지 분석 및 답변 생성
     */
    private function analyzeUserMessage($userMessage, $decision) {
        $response = [
            'message' => '',
            'questions' => []
        ];
        
        $userMessageLower = mb_strtolower($userMessage, 'UTF-8');
        
        // 첫 수업 관련 키워드
        if (mb_strpos($userMessageLower, '첫') !== false && 
            (mb_strpos($userMessageLower, '수업') !== false || 
             mb_strpos($userMessageLower, '시작') !== false || 
             mb_strpos($userMessageLower, '준비') !== false)) {
            $response['message'] = "첫 수업을 시작하기 위해 학생의 현재 수학 학습 상태를 파악하겠습니다.\n\n";
            $response['questions'][] = "현재 수학 진도는 어디까지 진행하셨나요? (예: 중등수학 2-1, 고등수학 상 등)";
            $response['questions'][] = "수학 학습 스타일은 어떤가요? (A) 계산 연습 중심 (B) 개념 이해 중심 (C) 문제 풀이 중심";
            return $response;
        }
        
        // 학습 계획/목표 관련
        if (mb_strpos($userMessageLower, '계획') !== false || 
            mb_strpos($userMessageLower, '목표') !== false ||
            mb_strpos($userMessageLower, '루틴') !== false) {
            $response['message'] = "학습 계획을 수립하기 위해 다음 정보가 필요합니다:\n\n";
            $response['questions'][] = "수학 학습 목표는 무엇인가요? (예: 내신 대비, 수능 대비, 경시대회 등)";
            $response['questions'][] = "주당 학습 시간은 얼마나 되나요?";
            return $response;
        }
        
        // 진도/수준 관련
        if (mb_strpos($userMessageLower, '진도') !== false || 
            mb_strpos($userMessageLower, '수준') !== false ||
            mb_strpos($userMessageLower, '레벨') !== false) {
            $response['message'] = "학생의 수학 학습 진도를 파악하겠습니다.\n\n";
            $response['questions'][] = "현재 개념 진도는 어디까지 진행하셨나요?";
            $response['questions'][] = "심화 진도는 어디까지 진행하셨나요?";
            return $response;
        }
        
        // 문제/어려움 관련
        if (mb_strpos($userMessageLower, '문제') !== false || 
            mb_strpos($userMessageLower, '어려') !== false ||
            mb_strpos($userMessageLower, '힘들') !== false) {
            $response['message'] = "학습 중 어려움을 파악하여 맞춤형 해결 방안을 제시하겠습니다.\n\n";
            $response['questions'][] = "어떤 부분이 가장 어려우신가요? (예: 특정 단원, 문제 유형 등)";
            $response['questions'][] = "어려움을 느끼는 이유는 무엇인가요? (예: 개념 이해 부족, 계산 실수 등)";
            return $response;
        }
        
        // 기본 응답: 컨텍스트 기반 안내
        $context = $decision['trace_data']['context_snapshot'] ?? [];
        $hasProgress = !empty($context['concept_progress']) || !empty($context['advanced_progress']);
        $hasStyle = !empty($context['study_style']) || !empty($context['math_learning_style']);
        
        if ($hasProgress && $hasStyle) {
            $response['message'] = "학생의 학습 정보를 분석했습니다. 추가로 필요한 정보를 수집하겠습니다.\n\n";
            $response['questions'][] = "학습 목표는 무엇인가요?";
        } elseif ($hasProgress) {
            $response['message'] = "학습 진도 정보를 확인했습니다. 학습 스타일을 파악하겠습니다.\n\n";
            $response['questions'][] = "수학 학습 시 어떤 방식을 선호하시나요? (개념 중심, 문제 풀이 중심 등)";
        } else {
            $response['message'] = "학생의 수학 학습 상태를 파악하기 위해 다음 정보가 필요합니다:\n\n";
            $response['questions'][] = "현재 수학 진도는 어디까지 진행하셨나요?";
            $response['questions'][] = "수학 학습 스타일은 어떤가요?";
        }
        
        return $response;
    }
    
    /**
     * 에이전트 경로 매핑
     */
    private function getAgentPaths() {
        return [
            'agent01' => 'agent01_onboarding',
            'agent02' => 'agent02_exam_schedule',
            'agent03' => 'agent03_goals_analysis',
            'agent04' => 'agent04_inspect_weakpoints',
            'agent05' => 'agent05_learning_emotion',
            'agent06' => 'agent06_teacher_feedback',
            'agent07' => 'agent07_interaction_targeting',
            'agent08' => 'agent08_calmness',
            'agent09' => 'agent09_learning_management',
            'agent10' => 'agent10_concept_notes',
            'agent11' => 'agent11_problem_notes',
            'agent12' => 'agent12_rest_routine',
            'agent13' => 'agent13_learning_dropout',
            'agent14' => 'agent14_current_position',
            'agent15' => 'agent15_problem_redefinition',
            'agent16' => 'agent16_interaction_preparation',
            'agent17' => 'agent17_remaining_activities',
            'agent18' => 'agent18_signature_routine',
            'agent19' => 'agent19_interaction_content',
            'agent20' => 'agent20_intervention_preparation',
            'agent21' => 'agent21_intervention_execution',
            'agent22' => 'agent22_module_improvement'
        ];
    }

    /**
     * 에이전트 이름 조회
     */
    private function getAgentName($agentId) {
        $agentNames = [
            'agent01' => '온보딩',
            'agent02' => '시험 일정',
            'agent03' => '목표 분석',
            'agent04' => '약점 분석',
            'agent05' => '학습 감정',
            'agent06' => '교사 피드백',
            'agent07' => '상호작용 타겟팅',
            'agent08' => '침착함',
            'agent09' => '학습 관리',
            'agent10' => '개념 노트',
            'agent11' => '문제 노트',
            'agent12' => '휴식 루틴',
            'agent13' => '학습 이탈',
            'agent14' => '현재 위치',
            'agent15' => '문제 재정의',
            'agent16' => '상호작용 준비',
            'agent17' => '남은 활동',
            'agent18' => '시그니처 루틴',
            'agent19' => '상호작용 컨텐츠',
            'agent20' => '개입 준비',
            'agent21' => '개입 실행',
            'agent22' => '모듈 개선'
        ];
        
        return $agentNames[$agentId] ?? $agentId;
    }

    /**
     * 에이전트 실행 시뮬레이션 (실제 구현 시 각 에이전트 API 호출로 대체)
     */
    private function simulateAgentExecution($agentId, $request) {
        // 실제 구현 시 각 에이전트의 API를 호출
        // 현재는 시뮬레이션 응답 반환
        return [
            'status' => 'success',
            'message' => "에이전트 {$agentId}가 요청을 처리했습니다: {$request}",
            'data' => [
                'result' => '처리 완료',
                'details' => '실제 에이전트 실행 결과가 여기에 표시됩니다.'
            ]
        ];
    }
}


