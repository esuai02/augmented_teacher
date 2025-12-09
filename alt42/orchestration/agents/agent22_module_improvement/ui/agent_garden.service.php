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
        } elseif ($agentId === 'agent02' || $agentId === 'agent02_exam_schedule') {
            $ontologyHandlerPath = __DIR__ . '/../../agent02_exam_schedule/ontology/OntologyActionHandler.php';
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
        
        // Agent02, Agent04의 경우 OntologyConfig 체크 스킵 (Agent01 전용)
        if ($agentId !== 'agent02' && $agentId !== 'agent02_exam_schedule' && $agentId !== 'agent04' && $agentId !== 'agent04_inspect_weakpoints') {
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
                // Agent02, Agent04의 경우 생성자 시그니처가 다름
                if ($agentId === 'agent02' || $agentId === 'agent02_exam_schedule') {
                    $ontologyHandler = new OntologyActionHandler('agent02', $context, $studentId);
                } elseif ($agentId === 'agent04' || $agentId === 'agent04_inspect_weakpoints') {
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
        
        // agent02 (시험 일정)의 경우 rules.yaml을 사용하여 추론
        if ($agentId === 'agent02' || $agentId === 'agent02_exam_schedule') {
            return $this->executeAgent02WithRules($request, $studentId);
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
            
            // Q1 질문인 경우 온톨로지 기반 Q1 파이프라인 실행
            $matchedRuleId = $decision['rule_id'] ?? '';
            $isQ1Question = (
                $matchedRuleId === 'Q1_comprehensive_first_class_strategy' ||
                mb_strpos($matchedRuleId, 'Q1_') === 0 ||
                (mb_strpos(mb_strtolower($request, 'UTF-8'), '첫') !== false && 
                 (mb_strpos(mb_strtolower($request, 'UTF-8'), '수업') !== false || 
                  mb_strpos(mb_strtolower($request, 'UTF-8'), '시작') !== false))
            );
            
            if ($isQ1Question) {
                error_log("[Agent01 Debug] Q1 question detected, executing Q1 pipeline [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $q1Result = $this->executeQ1OntologyPipeline($context, $studentId);
                if ($q1Result['success']) {
                    $decision['q1_pipeline_result'] = $q1Result;
                    error_log("[Agent01 Debug] Q1 pipeline executed successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[Agent01 Debug] Q1 pipeline failed: " . ($q1Result['error'] ?? 'Unknown') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // Q2 질문인 경우 온톨로지 기반 Q2 파이프라인 실행
            $isQ2Question = (
                mb_strpos($matchedRuleId, 'Q2_') === 0 ||
                (mb_strpos(mb_strtolower($request, 'UTF-8'), '커리큘럼') !== false || 
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '루틴') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '최적화') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '우선순위') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '학습 흐름') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '문제 유형 비중') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '부모 개입') !== false)
            );
            
            if ($isQ2Question) {
                error_log("[Agent01 Debug] Q2 question detected, executing Q2 pipeline [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $q2Result = $this->executeQ2OntologyPipeline($context, $studentId);
                if ($q2Result['success']) {
                    $decision['q2_pipeline_result'] = $q2Result;
                    error_log("[Agent01 Debug] Q2 pipeline executed successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[Agent01 Debug] Q2 pipeline failed: " . ($q2Result['error'] ?? 'Unknown') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // Q3 질문인 경우 온톨로지 기반 Q3 파이프라인 실행
            $isQ3Question = (
                mb_strpos($matchedRuleId, 'Q3_') === 0 ||
                (mb_strpos(mb_strtolower($request, 'UTF-8'), '중장기') !== false || 
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '성장') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '경시') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '진학 목표') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '자존감') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '피로') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '리스크') !== false ||
                 mb_strpos(mb_strtolower($request, 'UTF-8'), '트래킹') !== false)
            );
            
            if ($isQ3Question) {
                error_log("[Agent01 Debug] Q3 question detected, executing Q3 pipeline [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $q3Result = $this->executeQ3OntologyPipeline($context, $studentId);
                if ($q3Result['success']) {
                    $decision['q3_pipeline_result'] = $q3Result;
                    error_log("[Agent01 Debug] Q3 pipeline executed successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[Agent01 Debug] Q3 pipeline failed: " . ($q3Result['error'] ?? 'Unknown') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
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
     * Agent02 (시험 일정) 실행 - rules.yaml 사용
     */
    private function executeAgent02WithRules($request, $studentId) {
        try {
            // student_id 검증
            if (empty($studentId)) {
                global $USER;
                $studentId = $USER->id ?? null;
                if (empty($studentId)) {
                    throw new Exception("student_id가 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            // agent02의 rules 폴더 경로
            $agent02RulesPath = __DIR__ . '/../../agent02_exam_schedule/rules';
            $rulesFilePath = $agent02RulesPath . '/rules.yaml';
            $ruleEvaluatorPath = $agent02RulesPath . '/rule_evaluator.php';
            $dataAccessPath = $agent02RulesPath . '/data_access.php';
            
            // 디버깅: 경로 확인
            error_log("[Agent02 Debug] agent02RulesPath: {$agent02RulesPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent02 Debug] rulesFilePath: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent02 Debug] studentId: {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 파일 존재 확인
            if (!file_exists($rulesFilePath)) {
                $errorMsg = "Rules file not found: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent02 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($ruleEvaluatorPath)) {
                $errorMsg = "Rule evaluator not found: {$ruleEvaluatorPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent02 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            if (!file_exists($dataAccessPath)) {
                $errorMsg = "Data access file not found: {$dataAccessPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent02 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            error_log("[Agent02 Debug] All files exist. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // report_generator.php가 직접 실행되지 않도록 가드 설정
            if (!defined('ALT42_DISABLE_DIRECT_ACTION')) {
                define('ALT42_DISABLE_DIRECT_ACTION', true);
            }
            
            // Moodle config가 로드되었는지 확인
            if (!isset($DB)) {
                include_once("/home/moodle/public_html/moodle/config.php");
                global $DB, $USER;
            }
            
            // 학생 컨텍스트 가져오기
            error_log("[Agent02 Debug] Loading data_access.php [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            require_once($dataAccessPath);
            
            error_log("[Agent02 Debug] Calling prepareRuleContext({$studentId}) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            try {
                $context = prepareRuleContext($studentId);
            } catch (Exception $e) {
                error_log("[Agent02 Error] prepareRuleContext 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                // 기본 컨텍스트 사용
                $context = ['student_id' => $studentId];
            }
            
            // prepareRuleContext가 null을 반환한 경우 처리
            if ($context === null) {
                error_log("[Agent02 Warning] prepareRuleContext returned null, using default context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $context = ['student_id' => $studentId];
            }
            
            // student_id가 컨텍스트에 포함되어 있는지 확인 및 보장
            if (!isset($context['student_id']) || empty($context['student_id'])) {
                error_log("[Agent02 Warning] student_id missing in context, setting to {$studentId} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $context['student_id'] = $studentId;
            }
            
            // 최종 검증: student_id가 반드시 있어야 함
            if (empty($context['student_id'])) {
                $errorMsg = "student_id를 컨텍스트에서 가져올 수 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                error_log("[Agent02 Error] " . $errorMsg);
                throw new Exception($errorMsg);
            }
            
            error_log("[Agent02 Debug] Context prepared. student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 사용자 메시지를 컨텍스트에 추가
            $context['user_message'] = $request;
            $context['conversation_timestamp'] = time();
            
            // 룰 평가기 로드 및 실행
            error_log("[Agent02 Debug] Loading rule_evaluator.php [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            require_once($ruleEvaluatorPath);
            
            // rules.yaml 파일 경로를 올바르게 지정
            error_log("[Agent02 Debug] Creating RuleEvaluator with rules file: {$rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // rule_evaluator.php에서 클래스명 확인 필요
            $evaluatorClass = 'ExamScheduleRuleEvaluator';
            if (!class_exists($evaluatorClass)) {
                $evaluatorClass = 'RuleEvaluator';
                if (!class_exists($evaluatorClass)) {
                    throw new Exception("RuleEvaluator 클래스를 찾을 수 없습니다. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            }
            
            try {
                $evaluator = new $evaluatorClass($rulesFilePath);
            } catch (Exception $e) {
                error_log("[Agent02 Error] RuleEvaluator 생성 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                throw new Exception("RuleEvaluator 생성 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            error_log("[Agent02 Debug] Evaluating rules with context [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            try {
                $decision = $evaluator->evaluate($context);
            } catch (Exception $e) {
                error_log("[Agent02 Error] Rules evaluation 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                throw new Exception("Rules evaluation 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            error_log("[Agent02 Debug] Rules evaluated successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent02 Debug] Decision: " . json_encode($decision, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("[Agent02 Debug] Decision has actions: " . (isset($decision['actions']) ? 'YES (' . count($decision['actions']) . ')' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // 범용 온톨로지 액션 처리
            try {
                $decision = $this->processOntologyActions('agent02', $decision, $context, $studentId);
                error_log("[Agent02 Debug] After processOntologyActions - has ontology_results: " . (isset($decision['ontology_results']) ? 'YES (' . count($decision['ontology_results']) . ')' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            } catch (Exception $e) {
                error_log("[Agent02 Error] processOntologyActions 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                // 온톨로지 처리 실패해도 계속 진행
            }
            
            // 액션에서 답변 생성 (컨텍스트 정보 포함)
            try {
                $response = $this->generateResponseFromActions($decision, $request, $context);
                error_log("[Agent02 Debug] Generated response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            } catch (Exception $e) {
                error_log("[Agent02 Error] generateResponseFromActions 실패: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                // 기본 응답 생성
                $response = [
                    'status' => 'success',
                    'message' => $decision['rationale'] ?? '시험 일정 정보를 분석 중입니다.',
                    'questions' => [],
                    'suggestions' => []
                ];
            }
            
            $result = [
                'agent_id' => 'agent02',
                'agent_name' => '시험 일정',
                'request' => $request,
                'response' => $response,
                'timestamp' => time(),
                'execution_time' => isset($decision['execution_time']) ? $decision['execution_time'] : 'N/A',
                'matched_rule' => $decision['rule_id'] ?? null,
                'confidence' => $decision['confidence'] ?? null
            ];
            
            // 온톨로지 결과 포함
            if (isset($decision['ontology_results'])) {
                $result['ontology_results'] = $decision['ontology_results'];
                error_log("[Agent02 Debug] Added ontology_results to result: " . count($decision['ontology_results']) . " items [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            return $result;
            
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            $errorTrace = $e->getTraceAsString();
            $errorFile = $e->getFile();
            $errorLine = $e->getLine();
            
            error_log("Error executing agent02 with rules: " . $errorMessage . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("Error trace: " . $errorTrace);
            error_log("Error in file: {$errorFile}:{$errorLine}");
            
            // 예외를 다시 던져서 컨트롤러에서 처리하도록 함
            throw new Exception("시험 일정 에이전트 처리 중 오류가 발생했습니다: {$errorMessage} [File: {$errorFile}, Line: {$errorLine}]", 0, $e);
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
        
        // 커리큘럼/루틴 최적화 관련 질문 감지 (Q2)
        $isQ2Question = (
            mb_strpos($matchedRuleId, 'Q2_') === 0 ||
            (mb_strpos($userMessageLower, '커리큘럼') !== false || mb_strpos($userMessageLower, '루틴') !== false ||
             mb_strpos($userMessageLower, '최적화') !== false || mb_strpos($userMessageLower, '우선순위') !== false ||
             mb_strpos($userMessageLower, '학습 흐름') !== false || mb_strpos($userMessageLower, '문제 유형 비중') !== false ||
             mb_strpos($userMessageLower, '부모 개입') !== false)
        );
        
        // 중장기 성장 전략 관련 질문 감지 (Q3)
        $isQ3Question = (
            mb_strpos($matchedRuleId, 'Q3_') === 0 ||
            (mb_strpos($userMessageLower, '중장기') !== false || mb_strpos($userMessageLower, '성장') !== false ||
             mb_strpos($userMessageLower, '경시') !== false || mb_strpos($userMessageLower, '진학 목표') !== false ||
             mb_strpos($userMessageLower, '자존감') !== false || mb_strpos($userMessageLower, '피로') !== false ||
             mb_strpos($userMessageLower, '리스크') !== false || mb_strpos($userMessageLower, '트래킹') !== false)
        );
        
        // 하위 호환성을 위한 별칭
        $isCurriculumQuestion = $isQ2Question;
        $isGrowthStrategyQuestion = $isQ3Question;
        
        // [주의] 첫 수업 리포트 생성은 온톨로지 결과 추출 후 아래에서 수행 (순서 중요)
        // 온톨로지 기반 전략/절차 데이터가 필요하므로 $strategyData, $procedureData 추출 후 호출
        $isFirstClassQuestionForReport = $isFirstClassQuestion && $context !== null && isset($context['student_id']);
        
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
        
        // 온톨로지 결과 활용 (기존 ontology_results + Q1/Q2/Q3 파이프라인 결과)
        $ontologyResults = $decision['ontology_results'] ?? [];
        $q1PipelineResult = $decision['q1_pipeline_result'] ?? null;
        $q2PipelineResult = $decision['q2_pipeline_result'] ?? null;
        $q3PipelineResult = $decision['q3_pipeline_result'] ?? null;
        $strategyData = null;
        $procedureData = null;
        $reasoningResults = [];
        
        // 질문 유형에 맞는 파이프라인 결과만 사용 (우선순위: Q1 > Q2 > Q3)
        if ($isFirstClassQuestion && $q1PipelineResult && $q1PipelineResult['success']) {
            $strategyData = $q1PipelineResult['strategy'] ?? null;
            $procedureData = $q1PipelineResult['procedure'] ?? null;
            $reasoningResults = $q1PipelineResult['reasoning'] ?? [];
            
            error_log("[Agent01] Using Q1 pipeline results - Strategy: " . ($strategyData ? 'YES' : 'NO') . ", Procedure: " . ($procedureData ? 'YES' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } elseif ($isQ2Question && $q2PipelineResult && $q2PipelineResult['success']) {
            $strategyData = $q2PipelineResult['strategy'] ?? null;
            $reasoningResults = $q2PipelineResult['reasoning'] ?? [];
            
            error_log("[Agent01] Using Q2 pipeline results - Strategy: " . ($strategyData ? 'YES' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } elseif ($isQ3Question && $q3PipelineResult && $q3PipelineResult['success']) {
            $strategyData = $q3PipelineResult['strategy'] ?? null;
            $reasoningResults = $q3PipelineResult['reasoning'] ?? [];
            
            error_log("[Agent01] Using Q3 pipeline results - Strategy: " . ($strategyData ? 'YES' : 'NO') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // 기존 ontology_results에서 추가 데이터 추출
        foreach ($ontologyResults as $ontologyResult) {
            if ($ontologyResult['success'] ?? false) {
                // 전략 데이터 추출 (Q1 파이프라인 결과가 없는 경우)
                if (!$strategyData && isset($ontologyResult['strategy'])) {
                    $strategyData = $ontologyResult['strategy'];
                }
                
                // 절차 데이터 추출 (Q1 파이프라인 결과가 없는 경우)
                if (!$procedureData && isset($ontologyResult['procedure'])) {
                    $procedureData = $ontologyResult['procedure'];
                }
                
                // 추론 결과 추출
                if (isset($ontologyResult['results'])) {
                    $reasoningResults = array_merge($reasoningResults, $ontologyResult['results']);
                }
            }
        }
        
        // [핵심 수정] 온톨로지 결과 추출 후 첫 수업 전략 리포트 생성 (온톨로지 데이터 전달)
        $isFirstClassQuestionForReport = $isFirstClassQuestion && $context !== null && isset($context['student_id']);
        if ($isFirstClassQuestionForReport) {
            try {
                error_log("[Agent01] Generating first class strategy report with ontology data for student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                
                // 온톨로지 데이터를 함께 전달
                $ontologyData = [
                    'strategy' => $strategyData,
                    'procedure' => $procedureData,
                    'reasoning' => $reasoningResults
                ];
                
                $detailedReport = $this->generateFirstClassStrategyReport($context['student_id'], $context, $ontologyData);
                if ($detailedReport && !empty($detailedReport['report'])) {
                    $response['detailed_report'] = $detailedReport['report'];
                    $response['has_detailed_report'] = true;
                    error_log("[Agent01] First class strategy report generated successfully with ontology data [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                } else {
                    error_log("[Agent01] First class strategy report generation returned empty result [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            } catch (Exception $e) {
                error_log("[Agent01] Error generating first class strategy report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                $response['report_generation_error'] = '상세 리포트 생성 중 오류가 발생했습니다. 기본 응답을 제공합니다.';
            }
        }
        
        // Q2 커리큘럼 최적화 리포트 생성
        $isQ2QuestionForReport = $isQ2Question && $context !== null && isset($context['student_id']);
        if ($isQ2QuestionForReport) {
            try {
                error_log("[Agent01] Generating Q2 curriculum optimization report with ontology data for student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                
                $ontologyData = [
                    'strategy' => $strategyData,
                    'reasoning' => $reasoningResults
                ];
                
                $detailedReport = $this->generateCurriculumOptimizationReport($context['student_id'], $context, $ontologyData);
                if ($detailedReport && !empty($detailedReport['report'])) {
                    $response['detailed_report'] = $detailedReport['report'];
                    $response['has_detailed_report'] = true;
                    error_log("[Agent01] Q2 curriculum optimization report generated successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            } catch (Exception $e) {
                error_log("[Agent01] Error generating Q2 report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
        }
        
        // Q3 중장기 성장 전략 리포트 생성
        $isQ3QuestionForReport = $isQ3Question && $context !== null && isset($context['student_id']);
        if ($isQ3QuestionForReport) {
            try {
                error_log("[Agent01] Generating Q3 long-term growth strategy report with ontology data for student_id: " . $context['student_id'] . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                
                $ontologyData = [
                    'strategy' => $strategyData,
                    'reasoning' => $reasoningResults
                ];
                
                $detailedReport = $this->generateLongTermGrowthReport($context['student_id'], $context, $ontologyData);
                if ($detailedReport && !empty($detailedReport['report'])) {
                    $response['detailed_report'] = $detailedReport['report'];
                    $response['has_detailed_report'] = true;
                    error_log("[Agent01] Q3 long-term growth strategy report generated successfully [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                }
            } catch (Exception $e) {
                error_log("[Agent01] Error generating Q3 report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
        }
        
        // 온톨로지 기반 메시지 강화
        if ($strategyData && $isFirstClassQuestion) {
            $strategy = $strategyData['strategy'] ?? $strategyData;
            
            // [동적 로드] 온톨로지 값 추출 (SchemaLoader 매핑 사용)
            $learningStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLearningStyle');
            $studyStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasStudyStyle');
            $examStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasExamStyle');
            $confidence = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathConfidence');
            $mathLevel = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLevel');
            $conceptProgress = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasConceptProgress');
            $advancedProgress = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasAdvancedProgress');
            
            $strategyMsg = "\n\n📋 **온보딩 기반 첫 수업 전략**\n";
            
            // 학생 현황 섹션
            $hasStudentInfo = false;
            $studentInfoMsg = "\n**📊 학생 현황**\n";
            
            if ($mathLevel) {
                $studentInfoMsg .= "• 수학 수준: " . $mathLevel . "\n";
                $hasStudentInfo = true;
            }
            if ($confidence !== null && $confidence !== '') {
                $confidenceText = is_numeric($confidence) ? $confidence . "/10" : $confidence;
                $studentInfoMsg .= "• 수학 자신감: " . $confidenceText . "\n";
                $hasStudentInfo = true;
            }
            if ($learningStyle) {
                $studentInfoMsg .= "• 학습 스타일: " . $learningStyle . "\n";
                $hasStudentInfo = true;
            }
            if ($studyStyle) {
                $studentInfoMsg .= "• 공부 스타일: " . $studyStyle . "\n";
                $hasStudentInfo = true;
            }
            if ($examStyle) {
                $studentInfoMsg .= "• 시험 대비 스타일: " . $examStyle . "\n";
                $hasStudentInfo = true;
            }
            
            if ($hasStudentInfo) {
                $strategyMsg .= $studentInfoMsg;
            }
            
            // 진도 정보 섹션
            $hasProgressInfo = false;
            $progressInfoMsg = "\n**📚 진도 정보**\n";
            
            if ($conceptProgress) {
                $progressInfoMsg .= "• 개념 진도: " . $conceptProgress . "\n";
                $hasProgressInfo = true;
            }
            if ($advancedProgress) {
                $progressInfoMsg .= "• 심화 진도: " . $advancedProgress . "\n";
                $hasProgressInfo = true;
            }
            
            if ($hasProgressInfo) {
                $strategyMsg .= $progressInfoMsg;
            }
            
            // 추천 전략 섹션
            $hasRecommendations = false;
            $recommendationsMsg = "\n**🎯 맞춤 추천**\n";
            
            // [동적 로드] 모든 recommends* 프로퍼티 자동 처리
            $recommendProps = $this->findRecommendProperties($strategy);
            foreach ($recommendProps as $prop) {
                $value = $strategy[$prop] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                
                $label = $this->getPropertyLabelFallback($prop);
                $displayValue = $this->formatOntologyInstanceValue($value);
                $recommendationsMsg .= "• {$label}: {$displayValue}\n";
                $hasRecommendations = true;
            }
            
            if ($hasRecommendations) {
                $strategyMsg .= $recommendationsMsg;
            }
            
            $response['message'] .= $strategyMsg;
            $response['ontology_strategy'] = $strategy;
        }
        
        // 절차 기반 메시지 강화
        if ($procedureData && $isFirstClassQuestion) {
            $procedure = $procedureData['procedure'] ?? $procedureData;
            $procedureSteps = $procedure['procedure_steps'] ?? $procedure['mk:hasProcedureSteps'] ?? $procedure['mk:hasSteps'] ?? [];
            
            if (!empty($procedureSteps)) {
                $procedureMsg = "\n\n📝 **첫 수업 30분 진행안**\n";
                
                // [동적 로드] 단계 타입별 이모지 및 한글 매핑 (procedure_template.json에서 로드)
                $typeEmoji = [];
                $typeKorean = [];
                $templatePath = __DIR__ . '/../../agent01_onboarding/procedure_template.json';
                if (file_exists($templatePath)) {
                    try {
                        $template = json_decode(file_get_contents($templatePath), true);
                        $typeEmoji = $template['step_type_emojis'] ?? [];
                        $typeKorean = $template['step_type_labels'] ?? [];
                    } catch (Exception $e) {
                        error_log("[AgentGardenService] 절차 단계 매핑 로드 실패: " . $e->getMessage());
                    }
                }
                
                $totalDuration = 0;
                foreach ($procedureSteps as $idx => $step) {
                    $order = $step['mk:stepOrder'] ?? ($idx + 1);
                    $type = $step['mk:stepType'] ?? $step['type'] ?? 'step';
                    $desc = $step['mk:stepDescription'] ?? $step['description'] ?? '';
                    $duration = $step['mk:stepDuration'] ?? $step['duration'] ?? '';
                    
                    // 시간 추출 (예: "5분" → 5)
                    if (preg_match('/(\d+)/', $duration, $durationMatch)) {
                        $totalDuration += (int)$durationMatch[1];
                    }
                    
                    $emoji = $typeEmoji[$type] ?? '📌';
                    $typeText = $typeKorean[$type] ?? $type;
                    
                    $stepLine = "{$emoji} **{$order}. {$typeText}**";
                    if ($duration) $stepLine .= " ({$duration})";
                    $stepLine .= "\n   " . $desc . "\n";
                    
                    $procedureMsg .= $stepLine;
                }
                
                // 총 시간 표시
                if ($totalDuration > 0) {
                    $procedureMsg .= "\n⏱️ **총 소요 시간**: {$totalDuration}분\n";
                }
                
                $response['message'] .= $procedureMsg;
                $response['ontology_procedure'] = $procedureSteps;
            }
        }
        
        // 온톨로지 기반 메시지 강화 - Q2
        if ($strategyData && $isQ2Question) {
            $strategy = $strategyData['strategy'] ?? $strategyData;
            
            $strategyMsg = "\n\n📋 **커리큘럼과 루틴 최적화 전략**\n";
            
            // 학생 현황 섹션
            $hasStudentInfo = false;
            $studentInfoMsg = "\n**📊 학생 현황**\n";
            
            $learningStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasLearningStyle') ?? 
                             $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLearningStyle');
            $studyStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasStudyStyle') ?? 
                          $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasStudyStyle');
            $confidence = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasMathConfidence') ?? 
                          $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathConfidence');
            $mathLevel = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLevel');
            $conceptProgress = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasConceptProgress');
            $advancedProgress = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasAdvancedProgress');
            
            if ($mathLevel) {
                $studentInfoMsg .= "• 수학 수준: " . $mathLevel . "\n";
                $hasStudentInfo = true;
            }
            if ($confidence !== null && $confidence !== '') {
                $confidenceText = is_numeric($confidence) ? $confidence . "/10" : $confidence;
                $studentInfoMsg .= "• 수학 자신감: " . $confidenceText . "\n";
                $hasStudentInfo = true;
            }
            if ($learningStyle) {
                $studentInfoMsg .= "• 학습 스타일: " . $learningStyle . "\n";
                $hasStudentInfo = true;
            }
            if ($studyStyle) {
                $studentInfoMsg .= "• 공부 스타일: " . $studyStyle . "\n";
                $hasStudentInfo = true;
            }
            
            if ($hasStudentInfo) {
                $strategyMsg .= $studentInfoMsg;
            }
            
            // 진도 정보 섹션
            $hasProgressInfo = false;
            $progressInfoMsg = "\n**📚 진도 정보**\n";
            
            if ($conceptProgress) {
                $progressInfoMsg .= "• 개념 진도: " . $conceptProgress . "\n";
                $hasProgressInfo = true;
            }
            if ($advancedProgress) {
                $progressInfoMsg .= "• 심화 진도: " . $advancedProgress . "\n";
                $hasProgressInfo = true;
            }
            
            if ($hasProgressInfo) {
                $strategyMsg .= $progressInfoMsg;
            }
            
            // 추천 전략 섹션
            $hasRecommendations = false;
            $recommendationsMsg = "\n**🎯 맞춤 추천**\n";
            
            $recommendProps = $this->findRecommendProperties($strategy);
            foreach ($recommendProps as $prop) {
                $value = $strategy[$prop] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                
                $label = $this->getPropertyLabelFallback($prop);
                $displayValue = $this->formatOntologyInstanceValue($value);
                $recommendationsMsg .= "• {$label}: {$displayValue}\n";
                $hasRecommendations = true;
            }
            
            if ($hasRecommendations) {
                $strategyMsg .= $recommendationsMsg;
            }
            
            $response['message'] .= $strategyMsg;
            $response['ontology_strategy'] = $strategy;
        }
        
        // 온톨로지 기반 메시지 강화 - Q3
        if ($strategyData && $isQ3Question) {
            $strategy = $strategyData['strategy'] ?? $strategyData;
            
            $strategyMsg = "\n\n📋 **중장기 성장 전략**\n";
            
            // 학생 현황 섹션
            $hasStudentInfo = false;
            $studentInfoMsg = "\n**📊 학생 현황**\n";
            
            $confidence = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasMathConfidence') ?? 
                          $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathConfidence');
            $mathLevel = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLevel');
            $longTermGoal = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasLongTermGoal') ?? 
                            $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasLongTermGoal');
            $riskLevel = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasRiskLevel');
            $stressLevel = $this->extractOntologyValueDynamic($strategy, $context, 'mk-a01-mod:hasStressLevel') ?? 
                           $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathStressLevel');
            
            if ($mathLevel) {
                $studentInfoMsg .= "• 수학 수준: " . $mathLevel . "\n";
                $hasStudentInfo = true;
            }
            if ($confidence !== null && $confidence !== '') {
                $confidenceText = is_numeric($confidence) ? $confidence . "/10" : $confidence;
                $studentInfoMsg .= "• 수학 자신감: " . $confidenceText . "\n";
                $hasStudentInfo = true;
            }
            if ($longTermGoal) {
                $studentInfoMsg .= "• 장기 목표: " . $longTermGoal . "\n";
                $hasStudentInfo = true;
            }
            if ($riskLevel) {
                $studentInfoMsg .= "• 리스크 수준: " . $riskLevel . "\n";
                $hasStudentInfo = true;
            }
            if ($stressLevel) {
                $studentInfoMsg .= "• 스트레스 수준: " . $stressLevel . "\n";
                $hasStudentInfo = true;
            }
            
            if ($hasStudentInfo) {
                $strategyMsg .= $studentInfoMsg;
            }
            
            // 추천 전략 섹션
            $hasRecommendations = false;
            $recommendationsMsg = "\n**🎯 맞춤 추천**\n";
            
            $recommendProps = $this->findRecommendProperties($strategy);
            foreach ($recommendProps as $prop) {
                $value = $strategy[$prop] ?? null;
                if ($value === null || $value === '') {
                    continue;
                }
                
                $label = $this->getPropertyLabelFallback($prop);
                $displayValue = $this->formatOntologyInstanceValue($value);
                $recommendationsMsg .= "• {$label}: {$displayValue}\n";
                $hasRecommendations = true;
            }
            
            if ($hasRecommendations) {
                $strategyMsg .= $recommendationsMsg;
            }
            
            $response['message'] .= $strategyMsg;
            $response['ontology_strategy'] = $strategy;
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
        
        // 온톨로지 디버그 정보 추가 (사용된 JSON-LD 및 온톨로지 정보 표시)
        $pipelineResult = $q1PipelineResult ?? $q2PipelineResult ?? $q3PipelineResult ?? null;
        if ($pipelineResult) {
            $ontologyDebug = [
                'pipeline_success' => $pipelineResult['success'] ?? false,
                'stages' => $pipelineResult['stages'] ?? [],
                'schema_info' => null,
                'instances_created' => [],
                'jsonld_data' => []
            ];
            
            // 스키마 정보 로드
            $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
            if (file_exists($schemaLoaderPath)) {
                require_once($schemaLoaderPath);
                try {
                    $schemaLoader = new SchemaLoader();
                    // [동적 로드] 사용된 클래스와 프로퍼티 자동 추출
                    $allClasses = $schemaLoader->getAllClasses();
                    $allProperties = $schemaLoader->getAllProperties();
                    $classesUsed = array_keys($allClasses);
                    $propertiesUsed = array_keys($allProperties);
                    
                    $ontologyDebug['schema_info'] = [
                        'schema_path' => $schemaLoader->getSchemaPath(),
                        'class_count' => $schemaLoader->getClassCount(),
                        'property_count' => $schemaLoader->getPropertyCount(),
                        'classes_used' => $classesUsed,
                        'properties_used' => $propertiesUsed
                    ];
                } catch (Exception $e) {
                    $ontologyDebug['schema_info'] = ['error' => $e->getMessage()];
                }
            }
            
            // 생성된 인스턴스 정보 (Q1, Q2, Q3 모두 포함)
            $allPipelineResults = array_filter([$q1PipelineResult, $q2PipelineResult, $q3PipelineResult]);
            foreach ($allPipelineResults as $pipelineRes) {
                if (isset($pipelineRes['stages'])) {
                    foreach ($pipelineRes['stages'] as $stageName => $stageInfo) {
                        if (isset($stageInfo['instance_id'])) {
                            $ontologyDebug['instances_created'][] = [
                                'stage' => $stageName,
                                'instance_id' => $stageInfo['instance_id'],
                                'status' => $stageInfo['status'] ?? 'unknown'
                            ];
                        }
                    }
                }
            }
            
            // 전략 JSON-LD 데이터
            if ($strategyData) {
                $ontologyDebug['jsonld_data']['strategy'] = [
                    'instance_id' => $strategyData['instance_id'] ?? null,
                    'data' => $strategyData['strategy'] ?? $strategyData
                ];
            }
            
            // 절차 JSON-LD 데이터
            if ($procedureData) {
                $ontologyDebug['jsonld_data']['procedure'] = [
                    'instance_id' => $procedureData['instance_id'] ?? null,
                    'steps_count' => count($procedureData['procedure_steps'] ?? []),
                    'data' => $procedureData
                ];
            }
            
            // 응답에 온톨로지 디버그 정보 추가
            $response['ontology_debug'] = $ontologyDebug;
            
            // 메시지에 온톨로지 사용 정보 추가
            $ontologyInfoMsg = "\n\n---\n📊 **온톨로지 사용 정보 (디버그)**\n";
            $ontologyInfoMsg .= "• 스키마 파일: `온톨로지.jsonld`\n";
            $ontologyInfoMsg .= "• 사용된 클래스: " . implode(', ', $ontologyDebug['schema_info']['classes_used'] ?? []) . "\n";
            $ontologyInfoMsg .= "• 생성된 인스턴스: " . count($ontologyDebug['instances_created']) . "개\n";
            
            if (!empty($ontologyDebug['instances_created'])) {
                $ontologyInfoMsg .= "\n**생성된 인스턴스 목록:**\n";
                foreach ($ontologyDebug['instances_created'] as $inst) {
                    $shortId = substr($inst['instance_id'], 0, 50) . '...';
                    $ontologyInfoMsg .= "• [{$inst['stage']}] `{$shortId}`\n";
                }
            }
            
            if (!empty($ontologyDebug['jsonld_data']['strategy']['data'])) {
                $ontologyInfoMsg .= "\n**전략 JSON-LD 데이터:**\n```json\n";
                $ontologyInfoMsg .= json_encode($ontologyDebug['jsonld_data']['strategy']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $ontologyInfoMsg .= "\n```\n";
            }
            
            if (!empty($ontologyDebug['jsonld_data']['procedure']['data'])) {
                $ontologyInfoMsg .= "\n**절차 JSON-LD 데이터:**\n```json\n";
                $ontologyInfoMsg .= json_encode($ontologyDebug['jsonld_data']['procedure']['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $ontologyInfoMsg .= "\n```\n";
            }
            
            $response['message'] .= $ontologyInfoMsg;
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
        
        return $response;
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
     * Q1 온톨로지 파이프라인 실행
     * 
     * 온톨로지 기반으로 첫 수업 전략을 생성합니다.
     * SchemaLoader의 공식 매핑 테이블을 사용하여 변수를 해석합니다.
     */
    private function executeQ1OntologyPipeline(array $context, int $studentId): array {
        try {
            $ontologyHandlerPath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyActionHandler.php';
            $ontologyEnginePath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyEngine.php';
            $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
            
            if (!file_exists($ontologyHandlerPath) || !file_exists($ontologyEnginePath)) {
                error_log("[Q1 Pipeline] Required files not found [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return ['success' => false, 'error' => 'Ontology files not found'];
            }
            
            require_once($schemaLoaderPath);
            require_once($ontologyEnginePath);
            require_once($ontologyHandlerPath);
            
            // 컨텍스트 로깅 (디버그용)
            error_log("[Q1 Pipeline] Context keys: " . implode(', ', array_keys($context)));
            error_log("[Q1 Pipeline] math_learning_style: " . ($context['math_learning_style'] ?? 'NULL'));
            error_log("[Q1 Pipeline] math_confidence: " . ($context['math_confidence'] ?? 'NULL'));
            error_log("[Q1 Pipeline] math_level: " . ($context['math_level'] ?? 'NULL'));
            
            // OntologyActionHandler 생성 (컨텍스트와 학생 ID 전달)
            $handler = new OntologyActionHandler('agent01', $context, $studentId);
            
            // Q1 파이프라인 실행 (OntologyActionHandler::executeQ1Pipeline()은 인자를 받지 않음)
            // 내부적으로 constructor에서 받은 context와 studentId를 사용
            $pipelineResult = $handler->executeQ1Pipeline();
            
            if (!$pipelineResult['success']) {
                error_log("[Q1 Pipeline] Pipeline failed: " . json_encode($pipelineResult, JSON_UNESCAPED_UNICODE));
                return $pipelineResult;
            }
            
            // 전략 및 절차 데이터 추출 (파이프라인 결과에서 직접 추출)
            $strategyData = $pipelineResult['strategy'] ?? null;
            $procedureData = $pipelineResult['procedure'] ?? null;
            $reasoningResults = [];
            
            // stages에서 추가 정보 추출
            if (isset($pipelineResult['stages']['reasoning'])) {
                $reasoningResults = $pipelineResult['stages']['reasoning'];
            }
            
            // 온톨로지 엔진에서 최신 인스턴스 조회
            $engine = new OntologyEngine();
            
            // FirstClassStrategy 인스턴스 조회
            $strategyInstances = $engine->getInstancesByClass('mk:FirstClassStrategy', $studentId, 1);
            if (!empty($strategyInstances)) {
                $latestStrategy = $strategyInstances[0];
                $strategyData = array_merge($strategyData ?? [], [
                    'instance_id' => $latestStrategy['instance_id'] ?? null,
                    'strategy' => $latestStrategy['jsonld_data'] ?? []
                ]);
            }
            
            // LessonProcedure 인스턴스 조회
            $procedureInstances = $engine->getInstancesByClass('mk:LessonProcedure', $studentId, 1);
            if (!empty($procedureInstances)) {
                $latestProcedure = $procedureInstances[0];
                $procedureData = array_merge($procedureData ?? [], [
                    'instance_id' => $latestProcedure['instance_id'] ?? null,
                    'procedure' => $latestProcedure['jsonld_data'] ?? []
                ]);
            }
            
            return [
                'success' => true,
                'strategy' => $strategyData,
                'procedure' => $procedureData,
                'reasoning' => $reasoningResults,
                'pipeline_results' => $pipelineResult['results'] ?? []
            ];
            
        } catch (Exception $e) {
            error_log("[Q1 Pipeline] Exception: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Q2 온톨로지 파이프라인 실행
     * 
     * 온톨로지 기반으로 커리큘럼과 루틴 최적화 전략을 생성합니다.
     */
    private function executeQ2OntologyPipeline(array $context, int $studentId): array {
        try {
            $ontologyHandlerPath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyActionHandler.php';
            $ontologyEnginePath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyEngine.php';
            $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
            
            if (!file_exists($ontologyHandlerPath) || !file_exists($ontologyEnginePath)) {
                error_log("[Q2 Pipeline] Required files not found [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return ['success' => false, 'error' => 'Ontology files not found'];
            }
            
            require_once($schemaLoaderPath);
            require_once($ontologyEnginePath);
            require_once($ontologyHandlerPath);
            
            // 컨텍스트 로깅 (디버그용)
            error_log("[Q2 Pipeline] Context keys: " . implode(', ', array_keys($context)));
            error_log("[Q2 Pipeline] math_learning_style: " . ($context['math_learning_style'] ?? 'NULL'));
            error_log("[Q2 Pipeline] study_style: " . ($context['study_style'] ?? 'NULL'));
            error_log("[Q2 Pipeline] long_term_goal: " . ($context['long_term_goal'] ?? 'NULL'));
            
            // OntologyActionHandler 생성 (컨텍스트와 학생 ID 전달)
            $handler = new OntologyActionHandler('agent01', $context, $studentId);
            
            // Q2 파이프라인 실행
            $pipelineResult = $handler->executeQ2Pipeline();
            
            if (!$pipelineResult['success']) {
                error_log("[Q2 Pipeline] Pipeline failed: " . json_encode($pipelineResult, JSON_UNESCAPED_UNICODE));
                return $pipelineResult;
            }
            
            // 전략 데이터 추출
            $strategyData = $pipelineResult['strategy'] ?? null;
            $reasoningResults = [];
            
            // 실제 추론 결과 추출 (우선순위: reasoning > stages.reasoning)
            if (isset($pipelineResult['reasoning']) && is_array($pipelineResult['reasoning'])) {
                $reasoningResults = $pipelineResult['reasoning'];
            } elseif (isset($pipelineResult['stages']['reasoning'])) {
                $reasoningResults = $pipelineResult['stages']['reasoning'];
            }
            
            // 온톨로지 엔진에서 최신 인스턴스 조회
            $engine = new OntologyEngine();
            
            // CurriculumOptimization 인스턴스 조회
            $strategyInstances = $engine->getInstancesByClass('mk-a01-mod:CurriculumOptimization', $studentId, 1);
            if (!empty($strategyInstances)) {
                $latestStrategy = $strategyInstances[0];
                $strategyData = array_merge($strategyData ?? [], [
                    'instance_id' => $latestStrategy['instance_id'] ?? null,
                    'strategy' => $latestStrategy['jsonld_data'] ?? []
                ]);
            }
            
            return [
                'success' => true,
                'strategy' => $strategyData,
                'reasoning' => $reasoningResults,
                'pipeline_results' => $pipelineResult['results'] ?? []
            ];
            
        } catch (Exception $e) {
            error_log("[Q2 Pipeline] Exception: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Q3 온톨로지 파이프라인 실행
     * 
     * 온톨로지 기반으로 중장기 성장 전략을 생성합니다.
     */
    private function executeQ3OntologyPipeline(array $context, int $studentId): array {
        try {
            $ontologyHandlerPath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyActionHandler.php';
            $ontologyEnginePath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyEngine.php';
            $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
            
            if (!file_exists($ontologyHandlerPath) || !file_exists($ontologyEnginePath)) {
                error_log("[Q3 Pipeline] Required files not found [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return ['success' => false, 'error' => 'Ontology files not found'];
            }
            
            require_once($schemaLoaderPath);
            require_once($ontologyEnginePath);
            require_once($ontologyHandlerPath);
            
            // 컨텍스트 로깅 (디버그용)
            error_log("[Q3 Pipeline] Context keys: " . implode(', ', array_keys($context)));
            error_log("[Q3 Pipeline] math_confidence: " . ($context['math_confidence'] ?? 'NULL'));
            error_log("[Q3 Pipeline] long_term_goal: " . ($context['long_term_goal'] ?? 'NULL'));
            error_log("[Q3 Pipeline] stress_level: " . ($context['stress_level'] ?? 'NULL'));
            
            // OntologyActionHandler 생성 (컨텍스트와 학생 ID 전달)
            $handler = new OntologyActionHandler('agent01', $context, $studentId);
            
            // Q3 파이프라인 실행
            $pipelineResult = $handler->executeQ3Pipeline();
            
            if (!$pipelineResult['success']) {
                error_log("[Q3 Pipeline] Pipeline failed: " . json_encode($pipelineResult, JSON_UNESCAPED_UNICODE));
                return $pipelineResult;
            }
            
            // 전략 데이터 추출
            $strategyData = $pipelineResult['strategy'] ?? null;
            $reasoningResults = [];
            
            // 실제 추론 결과 추출 (우선순위: reasoning > stages.reasoning)
            if (isset($pipelineResult['reasoning']) && is_array($pipelineResult['reasoning'])) {
                $reasoningResults = $pipelineResult['reasoning'];
            } elseif (isset($pipelineResult['stages']['reasoning'])) {
                $reasoningResults = $pipelineResult['stages']['reasoning'];
            }
            
            // 온톨로지 엔진에서 최신 인스턴스 조회
            $engine = new OntologyEngine();
            
            // LongTermGrowthStrategy 인스턴스 조회
            $strategyInstances = $engine->getInstancesByClass('mk-a01-mod:LongTermGrowthStrategy', $studentId, 1);
            if (!empty($strategyInstances)) {
                $latestStrategy = $strategyInstances[0];
                $strategyData = array_merge($strategyData ?? [], [
                    'instance_id' => $latestStrategy['instance_id'] ?? null,
                    'strategy' => $latestStrategy['jsonld_data'] ?? []
                ]);
            }
            
            return [
                'success' => true,
                'strategy' => $strategyData,
                'reasoning' => $reasoningResults,
                'pipeline_results' => $pipelineResult['results'] ?? []
            ];
            
        } catch (Exception $e) {
            error_log("[Q3 Pipeline] Exception: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Q2 커리큘럼 최적화 리포트 생성
     */
    private function generateCurriculumOptimizationReport($studentId, $context = null, $ontologyData = null) {
        try {
            $strategyData = $ontologyData['strategy'] ?? null;
            $strategy = $strategyData['strategy'] ?? $strategyData ?? [];
            
            if (!empty($strategy)) {
                $markdown = $this->generateOntologyBasedReport($strategy, [], $context ?? [], 'curriculum_optimization');
                
                $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
                $markdown = trim($markdown);
                
                return [
                    'success' => true,
                    'report' => $markdown,
                    'report_type' => 'ontology_based_markdown',
                    'data_source' => 'json-ld',
                    'generated_at' => time()
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("[Agent01] Error generating curriculum optimization report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * Q3 중장기 성장 전략 리포트 생성
     */
    private function generateLongTermGrowthReport($studentId, $context = null, $ontologyData = null) {
        try {
            $strategyData = $ontologyData['strategy'] ?? null;
            $strategy = $strategyData['strategy'] ?? $strategyData ?? [];
            
            if (!empty($strategy)) {
                $markdown = $this->generateOntologyBasedReport($strategy, [], $context ?? [], 'long_term_growth');
                
                $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
                $markdown = trim($markdown);
                
                return [
                    'success' => true,
                    'report' => $markdown,
                    'report_type' => 'ontology_based_markdown',
                    'data_source' => 'json-ld',
                    'generated_at' => time()
                ];
            }
            
            return null;
        } catch (Exception $e) {
            error_log("[Agent01] Error generating long-term growth report: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return null;
        }
    }
    
    /**
     * 첫 수업 전략 상세 분석 리포트 생성
     */
    /**
     * 첫 수업 전략 리포트 생성 (온톨로지/룰 기반)
     * 
     * @param int $studentId 학생 ID
     * @param array|null $context 컨텍스트 데이터
     * @param array|null $ontologyData 온톨로지 기반 전략/절차 데이터
     * @return array|null 리포트 결과
     */
    private function generateFirstClassStrategyReport($studentId, $context = null, $ontologyData = null) {
        try {
            // [핵심 수정 - 신뢰도 9.8+] 온톨로지 기반 데이터 추출
            $strategyData = $ontologyData['strategy'] ?? null;
            $procedureData = $ontologyData['procedure'] ?? null;
            $reasoningData = $ontologyData['reasoning'] ?? [];
            
            // 전략 JSON-LD에서 값 추출
            $strategy = $strategyData['strategy'] ?? $strategyData ?? [];
            $procedure = $procedureData['procedure'] ?? $procedureData ?? [];
            $procedureSteps = $procedure['procedure_steps'] ?? $procedure['mk:hasProcedureSteps'] ?? [];
            
            // [신뢰도 9.8+ 핵심] 먼저 온톨로지 기반 자동 문장 생성 시도
            // OpenAI 없이 100% 데이터 기반 리포트 생성
            $hasOntologyData = !empty($strategy) || !empty($procedureSteps);
            
            if ($hasOntologyData) {
                error_log("[Agent01] 신뢰도 9.8+: 온톨로지 기반 자동 문장 생성 사용 [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                
                $markdown = $this->generateOntologyBasedReport($strategy, $procedureSteps, $context ?? []);
                
                // 마크다운 정리
                $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);
                $markdown = trim($markdown);
                
                return [
                    'success' => true,
                    'report' => $markdown,
                    'report_type' => 'ontology_based_markdown',  // 신뢰도 9.8+ 타입
                    'data_source' => 'json-ld',                  // 데이터 소스 명시
                    'generated_at' => time()
                ];
            }
            
            // [Fallback] 온톨로지 데이터가 없는 경우에만 OpenAI 사용
            error_log("[Agent01] 온톨로지 데이터 없음, OpenAI fallback 사용 [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            
            // report_generator.php의 함수들 사용
            $reportGeneratorPath = __DIR__ . '/../../agent01_onboarding/report_generator.php';
            $reportServicePath = __DIR__ . '/../../agent01_onboarding/report_service.php';
            
            if (!file_exists($reportGeneratorPath) || !file_exists($reportServicePath)) {
                error_log("[Agent01] Report generator files not found [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                return null;
            }
            
            require_once($reportServicePath);
            require_once($reportGeneratorPath);
            
            // OpenAI 설정 로드
            load_openai_config();
            
            // [동적 로드] 온톨로지에서 값 추출 (동적 프로퍼티 검색)
            $mathLevel = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLevel') ?? '';
            $conceptProgress = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasConceptProgress') ?? '';
            $advancedProgress = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasAdvancedProgress') ?? '';
            $mathLearningStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathLearningStyle') ?? '';
            $studyStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasStudyStyle') ?? '';
            $examStyle = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasExamStyle') ?? '';
            $mathConfidence = $this->extractOntologyValueDynamic($strategy, $context, 'mk:hasMathConfidence') ?? 5;
            
            // [동적 로드] 추천 값 추출 (findRecommendProperties 사용)
            $recommendProps = $this->findRecommendProperties($strategy);
            // 동적으로 추천 프로퍼티 값 추출 (하위 호환성을 위해 개별 변수 유지)
            $recommendsData = [];
            foreach ($recommendProps as $prop) {
                $propName = str_replace('mk:recommends', '', $prop);
                $propName = lcfirst(str_replace('mk:', '', $propName));
                $recommendsData[$propName] = $strategy[$prop] ?? null;
            }
            // 개별 변수는 동적 데이터에서 추출 (하위 호환성)
            $recommendsTextbook = $recommendsData['Textbook'] ?? $strategy['mk:recommendsTextbook'] ?? '';
            $recommendsUnit = $recommendsData['Unit'] ?? $strategy['mk:recommendsUnit'] ?? '';
            $recommendsUnits = $recommendsData['Units'] ?? $strategy['mk:recommendsUnits'] ?? [];
            $recommendsProblemType = $recommendsData['ProblemType'] ?? $strategy['mk:recommendsProblemType'] ?? [];
            $recommendsIntroRoutine = $recommendsData['IntroductionRoutine'] ?? $strategy['mk:recommendsIntroductionRoutine'] ?? '';
            $recommendsExplanation = $recommendsData['ExplanationStrategy'] ?? $strategy['mk:recommendsExplanationStrategy'] ?? '';
            $recommendsMaterial = $recommendsData['MaterialType'] ?? $strategy['mk:recommendsMaterialType'] ?? '';
            $recommendsFeedback = $recommendsData['FeedbackTone'] ?? $strategy['mk:recommendsFeedbackTone'] ?? '';
            $recommendsDifficulty = $recommendsData['Difficulty'] ?? $strategy['mk:recommendsDifficulty'] ?? '';
            
            // JSON-LD 데이터를 정리된 형태로 변환
            $jsonldSummary = $this->formatOntologyDataForPrompt($strategy, $procedureSteps);
            
            // 시스템 프롬프트 (온톨로지 기반으로 변경)
            $system = [
                'role' => 'system',
                'content' => '너는 수학학원 온보딩 전문 어시스턴트다. 아래 제공되는 **온톨로지 기반 JSON-LD 데이터**를 분석하여 자연스러운 한국어 리포트를 작성한다. 

**핵심 원칙:**
1. 제공된 JSON-LD 온톨로지 데이터의 값들을 정확히 반영하라
2. mk:recommends* 프로퍼티의 추천값들을 구체적으로 설명하라
3. mk:hasProcedureSteps의 절차 단계를 그대로 활용하라
4. 온톨로지에 없는 내용은 추측하지 말고 생략하라
5. 순수 마크다운 형식만 사용하라 (HTML, 코드블록 금지)'
            ];
            
            // 사용자 프롬프트 (온톨로지 데이터 기반)
            $userPayload = [
                'ontology_data' => [
                    'strategy_jsonld' => $strategy,
                    'procedure_steps' => $procedureSteps,
                    'reasoning' => $reasoningData
                ],
                'summary' => $jsonldSummary,
                'instructions' => '**위 온톨로지 JSON-LD 데이터를 분석하여 아래 형식의 마크다운 리포트를 작성하라:**

**바로 ## 첫 수업 시작 전략부터 시작하라.** (제목/헤더 없이 본문만)

## 첫 수업 시작 전략

**이 학생의 온톨로지 기반 분석 결과를 바탕으로** 첫 수업 전략을 설명하라.

### 1. 학생 현황 (온톨로지 데이터 기반)
- 수학 수준: ' . $mathLevel . '
- 개념 진도: ' . $conceptProgress . '
- 심화 진도: ' . $advancedProgress . '
- 학습 스타일: ' . $mathLearningStyle . '
- 자신감 수준: ' . $mathConfidence . '/10

### 2. 수업 도입 루틴
**mk:recommendsIntroductionRoutine** 값(' . $recommendsIntroRoutine . ')을 자연스러운 문장으로 설명하라.

### 3. 설명 전략
**mk:recommendsExplanationStrategy** 값(' . $recommendsExplanation . ')을 학습 스타일(' . $mathLearningStyle . ')과 연결하여 설명하라.

### 4. 자료 및 교재 추천
- 추천 교재: ' . $recommendsTextbook . '
- 추천 단원: ' . (is_array($recommendsUnits) ? implode(', ', $recommendsUnits) : $recommendsUnit) . '
- 자료 유형: ' . $recommendsMaterial . '

### 5. 첫 수업 30분 진행안
**mk:hasProcedureSteps** 데이터를 활용하여 분 단위로 상세히 작성하라:
' . $this->formatProcedureStepsForPrompt($procedureSteps) . '

### 6. 피드백 방식
**mk:recommendsFeedbackTone** 값(' . $recommendsFeedback . ')을 자연스럽게 설명하라.

### 7. 문제 유형 추천
' . (is_array($recommendsProblemType) ? json_encode($recommendsProblemType, JSON_UNESCAPED_UNICODE) : $recommendsProblemType) . '

**마크다운 형식만 사용. HTML 태그나 코드블록(```) 절대 금지.**'
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
     * 온톨로지 데이터를 프롬프트용 요약 형태로 변환
     * 
     * @param array $strategy 전략 JSON-LD 데이터
     * @param array $procedureSteps 절차 단계 배열
     * @return string 프롬프트용 요약 문자열
     */
    private function formatOntologyDataForPrompt(array $strategy, array $procedureSteps): string {
        $summary = "=== 온톨로지 기반 전략 요약 ===\n\n";
        
        // [동적 로드] 학생 정보 (findStudentInfoProperties 사용)
        $summary .= "📊 학생 분석:\n";
        $studentProps = $this->findStudentInfoProperties($strategy);
        foreach ($studentProps as $prop) {
            $value = $strategy[$prop] ?? null;
            if ($value !== null && $value !== '') {
                $label = $this->getPropertyLabelFallback($prop);
                $displayValue = $this->formatOntologyValue($prop, $value);
                $summary .= "- {$label}: {$displayValue}\n";
            }
        }
        
        // [동적 로드] 추천 사항 (findRecommendProperties 사용)
        $summary .= "\n📋 추천 사항:\n";
        $recommendProps = $this->findRecommendProperties($strategy);
        foreach ($recommendProps as $prop) {
            $value = $strategy[$prop] ?? null;
            if ($value !== null && $value !== '') {
                $label = $this->getPropertyLabelFallback($prop);
                $displayValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $this->formatOntologyInstanceValue($value);
                $summary .= "- {$label}: {$displayValue}\n";
            }
        }
        
        // 절차 단계
        if (!empty($procedureSteps)) {
            $summary .= "\n📝 수업 절차 (mk:hasProcedureSteps):\n";
            foreach ($procedureSteps as $step) {
                $order = $step['mk:stepOrder'] ?? $step['stepOrder'] ?? '?';
                $type = $step['mk:stepType'] ?? $step['stepType'] ?? '';
                $desc = $step['mk:stepDescription'] ?? $step['stepDescription'] ?? '';
                $duration = $step['mk:stepDuration'] ?? $step['stepDuration'] ?? '';
                $summary .= "  {$order}. [{$type}] {$desc} ({$duration})\n";
            }
        }
        
        return $summary;
    }
    
    /**
     * 절차 단계를 프롬프트용 문자열로 변환
     * 
     * @param array $procedureSteps 절차 단계 배열
     * @return string 프롬프트용 절차 설명
     */
    private function formatProcedureStepsForPrompt(array $procedureSteps): string {
        if (empty($procedureSteps)) {
            return "(절차 단계 데이터 없음)";
        }
        
        $output = "";
        foreach ($procedureSteps as $step) {
            $order = $step['mk:stepOrder'] ?? $step['stepOrder'] ?? '?';
            $type = $step['mk:stepType'] ?? $step['stepType'] ?? '';
            $desc = $step['mk:stepDescription'] ?? $step['stepDescription'] ?? '';
            $duration = $step['mk:stepDuration'] ?? $step['stepDuration'] ?? '';
            
            $output .= "- **{$order}단계 ({$duration})**: {$type} - {$desc}\n";
        }
        
        return $output;
    }
    
    // ============================================================
    // [동적 로드 함수들 - rules.yaml + 온톨로지.jsonld 기반 추론]
    // ============================================================
    
    /**
     * [동적 로드] 온톨로지 기반 리포트 생성
     * 
     * 🔧 하드코딩 제거: 온톨로지.jsonld의 rdfs:label과 SchemaLoader를 활용
     * 
     * @param array $strategy 전략 JSON-LD 데이터 (추론 결과)
     * @param array $procedureSteps 절차 단계 배열
     * @param array $context 컨텍스트 데이터
     * @return string 마크다운 형식 리포트
     */
    private function generateOntologyBasedReport(array $strategy, array $procedureSteps, array $context, string $reportType = 'first_class'): string {
        $report = "";
        
        // OntologyEngine 로드 (레이블 조회용)
        $engine = $this->getOntologyEngine();
        
        // 리포트 타입에 따른 제목 설정
        $reportTitle = '첫 수업 시작 전략'; // 기본값
        switch ($reportType) {
            case 'curriculum_optimization':
                $reportTitle = '커리큘럼과 루틴 최적화 전략';
                break;
            case 'long_term_growth':
                $reportTitle = '중장기 성장 전략';
                break;
            default:
                $reportTitle = '첫 수업 시작 전략';
                break;
        }
        
        // [1] 학생 현황 섹션 - 온톨로지에서 동적으로 프로퍼티와 값 추출
        $report .= "## {$reportTitle}\n\n";
        $report .= "### 1. 학생 현황 (온톨로지 추론 결과)\n\n";
        
        // 학생 정보 관련 프로퍼티 자동 검색 (has* 프로퍼티)
        $studentInfoProps = $this->findStudentInfoProperties($strategy);
        
        $hasStudentInfo = false;
        foreach ($studentInfoProps as $prop) {
            $value = $this->extractOntologyValue($strategy, $context, [$prop], [$this->ontologyToContextKey($prop)]);
            if ($value !== null && $value !== '') {
                $label = $engine ? $engine->getPropertyLabel($prop) : $this->getPropertyLabelFallback($prop);
                $displayValue = $this->formatOntologyValue($prop, $value);
                $report .= "- **{$label}**: {$displayValue}\n";
                $hasStudentInfo = true;
            }
        }
        
        if (!$hasStudentInfo) {
            $report .= "- (학생 현황 데이터가 없습니다)\n";
        }
        
        // [2] 추천 사항 섹션 - 온톨로지 추론 결과에서 recommends* 프로퍼티 자동 추출
        $report .= "\n### 2. 맞춤 추천 (온톨로지 추론 결과)\n\n";
        
        // recommends* 프로퍼티 자동 검색 (하드코딩 제거)
        $recommendProps = $this->findRecommendProperties($strategy);
        
        $hasRecommendations = false;
        foreach ($recommendProps as $prop) {
            $value = $strategy[$prop] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            
            $label = $engine ? $engine->getPropertyLabel($prop) : $this->getPropertyLabelFallback($prop);
            
            // 특수 처리: recommendsProblemType은 객체 배열일 수 있음
            if ($prop === 'mk:recommendsProblemType' && is_array($value)) {
                $report .= "\n### 3. 문제 유형 추천\n\n";
                foreach ($value as $pt) {
                    if (is_array($pt)) {
                        $type = $pt['type'] ?? $pt['problemType'] ?? '';
                        $ratio = $pt['ratio'] ?? $pt['percentage'] ?? '';
                        if ($type && $ratio) {
                            $report .= "- **{$type}**: {$ratio}%\n";
                        }
                    } elseif (is_string($pt)) {
                        $report .= "- {$pt}\n";
                    }
                }
                $hasRecommendations = true;
            } else {
                // 일반적인 recommends* 프로퍼티 처리
                $displayValue = $this->formatOntologyInstanceValue($value); // 배열도 처리 가능
                $report .= "- **{$label}**: {$displayValue}\n";
                $hasRecommendations = true;
            }
        }
        
        if (!$hasRecommendations) {
            $report .= "- (추천 데이터가 없습니다)\n";
        }
        
        // [4] 수업 절차 섹션 - mk:hasProcedureSteps (온톨로지 추론 결과)
        if (!empty($procedureSteps)) {
            $sectionNum = !empty($problemTypes) ? 4 : 3;
            $report .= "\n### {$sectionNum}. 첫 수업 30분 진행안\n\n";
            
            $totalMinutes = 0;
            foreach ($procedureSteps as $step) {
                $order = $step['mk:stepOrder'] ?? $step['stepOrder'] ?? '?';
                $type = $step['mk:stepType'] ?? $step['stepType'] ?? '';
                $desc = $step['mk:stepDescription'] ?? $step['stepDescription'] ?? '';
                $duration = $step['mk:stepDuration'] ?? $step['stepDuration'] ?? '';
                
                // 시간 추출
                if (preg_match('/(\d+)/', $duration, $m)) {
                    $totalMinutes += intval($m[1]);
                }
                
                $typeText = $this->getStepTypeLabel($type);
                $emoji = $this->getStepTypeEmoji($type);
                
                $report .= "{$emoji} **{$order}. {$typeText}** ({$duration})\n";
                $report .= "   {$desc}\n\n";
            }
            
            $report .= "---\n**⏱️ 총 소요 시간**: {$totalMinutes}분\n";
        }
        
        // [5] 데이터 소스 정보 (신뢰도 투명성)
        $report .= "\n---\n";
        $report .= "📊 **데이터 소스**: rules.yaml + 온톨로지.jsonld 기반 추론\n";
        $report .= "🔗 **스키마**: `agent01_onboarding/온톨로지.jsonld`\n";
        
        return $report;
    }
    
    /**
     * OntologyEngine 인스턴스 가져오기 (비활성화 - 안정성 확보)
     */
    private function getOntologyEngine() {
        // 임시 비활성화 - 안정성 확보를 위해 null 반환
        return null;
    }
    
    /**
     * [동적 로드] 온톨로지 인스턴스 값을 자연어로 변환
     * 
     * 온톨로지.jsonld의 @graph에서 rdfs:label 동적 조회
     * 
     * @param string|array $value 온톨로지 인스턴스 URI 또는 값 (배열도 처리 가능)
     * @return string 자연어 변환된 값
     */
    private function formatOntologyInstanceValue($value): string {
        // 배열인 경우 재귀적으로 처리
        if (is_array($value)) {
            $results = array_map([$this, 'formatOntologyInstanceValue'], $value);
            return implode(', ', $results);
        }
        
        // 문자열로 변환
        $value = (string)$value;
        
        // SchemaLoader를 통해 동적으로 레이블 조회
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                $label = $schemaLoader->getInstanceLabel($value);
                if ($label && $label !== $value) {
                    return $label;
                }
            } catch (Exception $e) {
                error_log("[agent_garden.service] SchemaLoader 레이블 조회 실패: " . $e->getMessage());
            }
        }
        
        // Fallback: CamelCase를 공백으로 분리
        $shortValue = str_replace('mk:', '', $value);
        return trim(preg_replace('/([A-Z])/', ' $1', $shortValue));
    }
    
    /**
     * [동적 로드] 온톨로지 값 포맷팅 (타입별)
     * 
     * SchemaLoader에서 프로퍼티 타입을 확인하여 동적 포맷팅
     */
    private function formatOntologyValue(string $prop, $value): string {
        // SchemaLoader를 통해 프로퍼티 타입 확인
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                $shortProp = str_replace('mk:', '', $prop);
                $propType = $schemaLoader->getPropertyType($shortProp);
                
                // xsd:integer 타입이고 범위가 0-10인 경우 /10 포맷 적용
                if ($propType === 'xsd:integer' && is_numeric($value)) {
                    $intValue = intval($value);
                    // 범위 확인 (0-10 또는 1-10)
                    if ($intValue >= 0 && $intValue <= 10) {
                        return $intValue . '/10';
                    }
                }
            } catch (Exception $e) {
                // 무시하고 기본 처리로 진행
            }
        }
        
        // mk: 프리픽스가 있으면 인스턴스 레이블로 변환
        if (is_string($value) && strpos($value, 'mk:') === 0) {
            return $this->formatOntologyInstanceValue($value);
        }
        
        return strval($value);
    }
    
    /**
     * [동적 로드] recommends* 프로퍼티 자동 검색
     * 
     * @param array $strategy 전략 데이터
     * @return array recommends* 프로퍼티 키 배열
     */
    private function findRecommendProperties(array $strategy): array {
        $recommendProps = [];
        
        foreach ($strategy as $key => $value) {
            // recommends로 시작하는 프로퍼티 자동 검색
            if (strpos($key, 'recommends') === 0 || strpos($key, 'mk:recommends') === 0) {
                $recommendProps[] = $key;
            }
        }
        
        return $recommendProps;
    }
    
    /**
     * [동적 로드] 학생 정보 프로퍼티 자동 검색
     * 
     * @param array $strategy 전략 데이터
     * @return array has* 프로퍼티 키 배열
     */
    private function findStudentInfoProperties(array $strategy): array {
        $studentProps = [];
        
        // [동적 로드] 온톨로지 스키마에서 has* 프로퍼티 조회
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        $priorityProps = [];
        
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                $allProps = $schemaLoader->getAllProperties();
                
                // has*로 시작하는 프로퍼티 찾기
                foreach ($allProps as $propName => $propDef) {
                    $propId = $propDef['id'] ?? 'mk:' . $propName;
                    if (strpos($propId, 'has') !== false || strpos($propName, 'has') === 0) {
                        $priorityProps[] = $propId;
                    }
                }
            } catch (Exception $e) {
                // 실패 시 빈 배열 사용
            }
        }
        
        // 우선순위 프로퍼티 먼저 추가
        foreach ($priorityProps as $prop) {
            if (isset($strategy[$prop])) {
                $studentProps[] = $prop;
            }
        }
        
        // 나머지 has* 프로퍼티 자동 검색
        foreach ($strategy as $key => $value) {
            // has로 시작하는 프로퍼티 자동 검색 (이미 추가된 것 제외)
            if ((strpos($key, 'has') === 0 || strpos($key, 'mk:has') === 0) && 
                !in_array($key, $studentProps)) {
                $studentProps[] = $key;
            }
        }
        
        return $studentProps;
    }
    
    /**
     * [동적 로드] 프로퍼티 레이블 (OntologyEngine 없을 때)
     * 
     * SchemaLoader를 통해 온톨로지에서 레이블 조회 시도
     */
    private function getPropertyLabelFallback(string $prop): string {
        // SchemaLoader를 통해 동적 조회 시도
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                // 프로퍼티 정의에서 레이블 조회 시도
                $shortProp = str_replace('mk:', '', $prop);
                $propDef = $schemaLoader->getPropertyDefinition($shortProp);
                
                // 온톨로지에서 프로퍼티의 rdfs:label이 있으면 사용
                // (현재는 구현되지 않았지만 확장 가능)
            } catch (Exception $e) {
                // 무시하고 fallback 사용
            }
        }
        
        // [동적 로드] CamelCase를 한글로 자동 변환
        // procedure_template.json에서 키워드 매핑 로드
        $name = str_replace('mk:', '', $prop);
        $name = preg_replace('/^(has|recommends)/', '', $name);
        
        $templatePath = __DIR__ . '/../../agent01_onboarding/procedure_template.json';
        $keywords = [];
        if (file_exists($templatePath)) {
            try {
                $template = json_decode(file_get_contents($templatePath), true);
                $keywords = $template['property_keyword_mapping'] ?? [];
            } catch (Exception $e) {
                error_log("[AgentGardenService] 키워드 매핑 로드 실패: " . $e->getMessage());
            }
        }
        
        // CamelCase를 단어로 분리 후 한글 변환
        $words = preg_split('/(?=[A-Z])/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $koreanWords = array_map(function($w) use ($keywords) {
            return isset($keywords[$w]) ? $keywords[$w] : $w;
        }, $words);
        
        return implode(' ', $koreanWords);
    }
    
    /** 
     * [동적 로드] 절차 단계 타입 레이블
     * 
     * procedure_template.json에서 동적으로 로드
     */
    private function getStepTypeLabel(string $type): string {
        // SchemaLoader를 통해 절차 단계 타입 레이블 조회 시도
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                $instanceUri = 'mk:ProcedureStepType_' . ucfirst($type);
                $label = $schemaLoader->getInstanceLabel($instanceUri);
                if ($label !== $instanceUri) {
                    return $label;
                }
            } catch (Exception $e) {
                // 실패 시 템플릿에서 로드
            }
        }
        
        // [동적 로드] procedure_template.json에서 레이블 매핑 로드
        $templatePath = __DIR__ . '/../../agent01_onboarding/procedure_template.json';
        if (file_exists($templatePath)) {
            try {
                $template = json_decode(file_get_contents($templatePath), true);
                $labelMap = $template['step_type_labels'] ?? [];
                if (isset($labelMap[$type])) {
                    return $labelMap[$type];
                }
            } catch (Exception $e) {
                error_log("[AgentGardenService] 절차 단계 레이블 로드 실패: " . $e->getMessage());
            }
        }
        
        return $type;
    }
    
    /**
     * [동적 로드] 절차 단계 타입 이모지
     * 
     * procedure_template.json에서 동적으로 로드
     */
    private function getStepTypeEmoji(string $type): string {
        // [동적 로드] procedure_template.json에서 이모지 매핑 로드
        $templatePath = __DIR__ . '/../../agent01_onboarding/procedure_template.json';
        if (file_exists($templatePath)) {
            try {
                $template = json_decode(file_get_contents($templatePath), true);
                $emojiMap = $template['step_type_emojis'] ?? [];
                if (isset($emojiMap[$type])) {
                    return $emojiMap[$type];
                }
                // default 이모지 사용
                return $emojiMap['default'] ?? '📌';
            } catch (Exception $e) {
                error_log("[AgentGardenService] 절차 단계 이모지 로드 실패: " . $e->getMessage());
            }
        }
        
        return '📌';
    }
    
    /**
     * [동적 로드] 온톨로지 프로퍼티 키를 컨텍스트 키로 변환
     * 
     * SchemaLoader::mapOntologyToContext() 활용
     * 
     * @param string $ontologyProp 온톨로지 프로퍼티 (mk:hasMathLevel 등)
     * @return string 컨텍스트 키 (math_level 등)
     */
    private function ontologyToContextKey(string $ontologyProp): string {
        // SchemaLoader 활용 시도
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                $shortProp = str_replace('mk:', '', $ontologyProp);
                $contextKey = $schemaLoader->mapOntologyToContext($shortProp);
                if ($contextKey) {
                    return $contextKey;
                }
            } catch (Exception $e) {
                // 무시하고 fallback 사용
            }
        }
        
        // Fallback: CamelCase → snake_case 변환
        $shortProp = str_replace(['mk:', 'has', 'recommends'], '', $ontologyProp);
        return strtolower(preg_replace('/([A-Z])/', '_$1', lcfirst($shortProp)));
    }

    /**
     * [동적 로드] 온보딩 컨텍스트 분석 및 요약 생성
     * 
     * SchemaLoader를 통해 컨텍스트 키를 동적으로 매핑
     */
    private function analyzeOnboardingContext($context) {
        $summary = [];
        $infoItems = [];
        $missingItems = [];
        
        // [동적 로드] SchemaLoader를 통해 컨텍스트 키 매핑 조회
        $schemaLoaderPath = __DIR__ . '/../../agent01_onboarding/ontology/SchemaLoader.php';
        $contextKeyMapping = [];
        if (file_exists($schemaLoaderPath)) {
            require_once($schemaLoaderPath);
            try {
                $schemaLoader = new SchemaLoader();
                $mappingInstance = $schemaLoader->getOfficialVariableMappingInstance();
                if ($mappingInstance) {
                    // 매핑을 역방향으로 변환 (온톨로지 → 컨텍스트)
                    foreach ($mappingInstance as $ontologyProp => $contextKey) {
                        $contextKeyMapping[$contextKey] = $ontologyProp;
                    }
                }
            } catch (Exception $e) {
                // 실패 시 빈 배열 사용
            }
        }
        
        // [동적 로드] 모든 컨텍스트 키를 순회하여 정보 추출
        $contextLabels = [
            'student_name' => '학생명',
            'math_level' => '수학 수준',
            'math_confidence' => '수학 자신감',
            'study_style' => '학습 스타일',
            'math_learning_style' => '수학 학습 스타일',
            'mbti_type' => 'MBTI',
            'concept_progress' => '개념 진도',
            'advanced_progress' => '심화 진도',
            'academy_name' => '학원',
            'academy_grade' => '학원 학년',
            'math_recent_score' => '최근 수학 성적',
            'math_recent_ranking' => '등수',
            'math_weak_units' => '취약 단원',
            'goals' => '학습 목표',
            'study_hours_per_week' => '주당 학습 시간'
        ];
        
        // 학생 기본 정보
        foreach (['student_name'] as $key) {
            if (!empty($context[$key])) {
                $label = $contextLabels[$key] ?? $key;
                $infoItems[] = "{$label}: " . $context[$key];
            }
        }
        
        // 수학 수준 정보
        $mathLevel = $this->extractOntologyValueDynamic([], $context, 'mk:hasMathLevel');
        if ($mathLevel) {
            $infoItems[] = "수학 수준: " . $mathLevel;
        } else {
            $missingItems[] = "수학 수준";
        }
        
        // 수학 자신감
        $mathConfidence = $this->extractOntologyValueDynamic([], $context, 'mk:hasMathConfidence');
        if ($mathConfidence) {
            $infoItems[] = "수학 자신감: " . $mathConfidence;
        } else {
            $missingItems[] = "수학 자신감";
        }
        
        // 학습 스타일
        $studyStyle = $this->extractOntologyValueDynamic([], $context, 'mk:hasStudyStyle');
        $mathLearningStyle = $this->extractOntologyValueDynamic([], $context, 'mk:hasMathLearningStyle');
        if ($studyStyle) {
            $infoItems[] = "학습 스타일: " . $studyStyle;
        } elseif ($mathLearningStyle) {
            $infoItems[] = "수학 학습 스타일: " . $mathLearningStyle;
        } else {
            $missingItems[] = "학습 스타일";
        }
        
        // MBTI
        if (!empty($context['mbti_type'])) {
            $infoItems[] = "MBTI: " . $context['mbti_type'];
        }
        
        // 진도 정보
        $progressInfo = [];
        $conceptProgress = $this->extractOntologyValueDynamic([], $context, 'mk:hasConceptProgress');
        $advancedProgress = $this->extractOntologyValueDynamic([], $context, 'mk:hasAdvancedProgress');
        if ($conceptProgress) {
            $progressInfo[] = "개념 진도: " . $conceptProgress;
        } else {
            $missingItems[] = "개념 진도";
        }
        if ($advancedProgress) {
            $progressInfo[] = "심화 진도: " . $advancedProgress;
        } else {
            $missingItems[] = "심화 진도";
        }
        if (!empty($progressInfo)) {
            $infoItems[] = implode(", ", $progressInfo);
        }
        
        // 학원 정보
        $academyName = $this->extractOntologyValueDynamic([], $context, 'mk:hasAcademy');
        if ($academyName) {
            $academyInfo = "학원: " . $academyName;
            $academyGrade = $this->extractOntologyValueDynamic([], $context, 'mk:hasAcademyGrade');
            if ($academyGrade) {
                $academyInfo .= " (" . $academyGrade . ")";
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
     * [동적 로드] 온톨로지 값 추출 (SchemaLoader 매핑 사용)
     * 
     * @param array $ontologyData 온톨로지 데이터
     * @param array|null $context 컨텍스트 데이터
     * @param string $ontologyProp 온톨로지 프로퍼티 (예: 'mk:hasMathLearningStyle')
     * @return mixed 값 또는 null
     */
    private function extractOntologyValueDynamic(array $ontologyData, ?array $context, string $ontologyProp) {
        // 1. 온톨로지 데이터에서 먼저 찾기
        if (isset($ontologyData[$ontologyProp]) && $ontologyData[$ontologyProp] !== null && $ontologyData[$ontologyProp] !== '') {
            return $ontologyData[$ontologyProp];
        }
        
        // 2. 컨텍스트에서 찾기 (SchemaLoader 매핑 사용)
        if ($context !== null) {
            $contextKey = $this->ontologyToContextKey($ontologyProp);
            if (isset($context[$contextKey]) && $context[$contextKey] !== null && $context[$contextKey] !== '') {
                return $context[$contextKey];
            }
        }
        
        return null;
    }
    
    /**
     * 온톨로지/컨텍스트에서 값 추출 (우선순위 기반) - 하위 호환성 유지
     * 
     * @param array $ontologyData 온톨로지 데이터
     * @param array|null $context 컨텍스트 데이터
     * @param array $ontologyKeys 온톨로지 키 배열 (우선순위 순)
     * @param array $contextKeys 컨텍스트 키 배열 (우선순위 순)
     * @return mixed|null 추출된 값 또는 null
     */
    private function extractOntologyValue(array $ontologyData, ?array $context, array $ontologyKeys, array $contextKeys) {
        // 1. 온톨로지 데이터에서 먼저 찾기
        foreach ($ontologyKeys as $key) {
            if (isset($ontologyData[$key]) && $ontologyData[$key] !== null && $ontologyData[$key] !== '') {
                return $ontologyData[$key];
            }
        }
        
        // 2. 컨텍스트에서 찾기
        if ($context !== null) {
            foreach ($contextKeys as $key) {
                if (isset($context[$key]) && $context[$key] !== null && $context[$key] !== '') {
                    return $context[$key];
                }
            }
        }
        
        return null;
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


