<?php
/**
 * 룰 기반 컨텐츠 생성기
 * 생성된 룰을 확인하고 검증하기 위한 컨텐츠 생성
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

class RuleContentGenerator {
    
    /**
     * 룰 검증 컨텐츠 생성
     * 
     * @param array $rules 생성된 룰 목록
     * @param array $context 컨텍스트 정보
     * @return array 컨텐츠 목록
     */
    public function generateRuleContents($rules, $context = []) {
        $contents = [];
        
        foreach ($rules as $rule) {
            // 룰별 검증 컨텐츠 생성
            $contents[] = $this->generateRuleVerificationContent($rule, $context);
            
            // 룰 실행 시나리오 컨텐츠 생성
            $contents[] = $this->generateRuleScenarioContent($rule, $context);
            
            // 룰 테스트 케이스 생성
            $contents[] = $this->generateRuleTestCaseContent($rule, $context);
        }
        
        return $contents;
    }
    
    /**
     * 룰 검증 컨텐츠 생성
     */
    private function generateRuleVerificationContent($rule, $context) {
        return [
            'type' => 'rule_verification',
            'rule_id' => $rule['rule_id'] ?? 'UNKNOWN',
            'title' => "룰 검증: " . ($rule['rule_id'] ?? 'UNKNOWN'),
            'content' => [
                'rule_info' => [
                    'rule_id' => $rule['rule_id'] ?? 'UNKNOWN',
                    'description' => $rule['description'] ?? '',
                    'priority' => $rule['priority'] ?? 3,
                    'confidence' => $rule['confidence'] ?? 0.5,
                    'rationale' => $rule['rationale'] ?? ''
                ],
                'conditions' => $rule['conditions'] ?? [],
                'actions' => $rule['action'] ?? [],
                'verification_checklist' => [
                    "조건이 올바르게 정의되었는가?",
                    "액션이 조건에 적합한가?",
                    "우선순위가 적절한가?",
                    "신뢰도가 합리적인가?",
                    "근거가 명확한가?"
                ],
                'test_scenarios' => $this->generateTestScenarios($rule)
            ],
            'metadata' => [
                'created_at' => date('Y-m-d H:i:s'),
                'unit' => $context['unit'] ?? null,
                'concepts' => $context['concepts'] ?? []
            ]
        ];
    }
    
    /**
     * 룰 실행 시나리오 컨텐츠 생성
     */
    private function generateRuleScenarioContent($rule, $context) {
        $scenarios = [];
        
        // 조건별 시나리오 생성
        $conditions = $rule['conditions'] ?? [];
        foreach ($conditions as $index => $condition) {
            $conditionNum = $index + 1;
            $scenarios[] = [
                'scenario_id' => "SCENARIO_" . ($rule['rule_id'] ?? 'UNKNOWN') . "_{$index}",
                'description' => "조건 {$conditionNum} 충족 시나리오",
                'condition' => $condition,
                'expected_action' => $rule['action'] ?? [],
                'expected_outcome' => $this->generateExpectedOutcome($rule, $condition)
            ];
        }
        
        return [
            'type' => 'rule_scenario',
            'rule_id' => $rule['rule_id'] ?? 'UNKNOWN',
            'title' => "룰 실행 시나리오: " . ($rule['rule_id'] ?? 'UNKNOWN'),
            'content' => [
                'scenarios' => $scenarios,
                'execution_flow' => $this->generateExecutionFlow($rule),
                'interaction_points' => $this->generateInteractionPoints($rule)
            ],
            'metadata' => [
                'created_at' => date('Y-m-d H:i:s'),
                'scenario_count' => count($scenarios)
            ]
        ];
    }
    
    /**
     * 룰 테스트 케이스 컨텐츠 생성
     */
    private function generateRuleTestCaseContent($rule, $context) {
        return [
            'type' => 'rule_test_case',
            'rule_id' => $rule['rule_id'] ?? 'UNKNOWN',
            'title' => "룰 테스트 케이스: " . ($rule['rule_id'] ?? 'UNKNOWN'),
            'content' => [
                'test_cases' => [
                    [
                        'test_id' => "TEST_" . ($rule['rule_id'] ?? 'UNKNOWN') . "_PASS",
                        'name' => '정상 케이스',
                        'input' => $this->generateTestInput($rule, 'pass'),
                        'expected_result' => '룰이 정상적으로 실행되어 액션이 수행됨',
                        'validation' => $this->generateValidationCriteria($rule)
                    ],
                    [
                        'test_id' => "TEST_" . ($rule['rule_id'] ?? 'UNKNOWN') . "_FAIL",
                        'name' => '실패 케이스',
                        'input' => $this->generateTestInput($rule, 'fail'),
                        'expected_result' => '룰이 실행되지 않음',
                        'validation' => '조건 불일치로 룰 미실행'
                    ]
                ],
                'edge_cases' => $this->generateEdgeCases($rule)
            ],
            'metadata' => [
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * 테스트 시나리오 생성
     */
    private function generateTestScenarios($rule) {
        $scenarios = [];
        
        // 각 조건에 대한 테스트 시나리오
        $conditions = $rule['conditions'] ?? [];
        foreach ($conditions as $condition) {
            $scenarios[] = [
                'condition' => $condition,
                'test_data' => $this->generateConditionTestData($condition),
                'expected_behavior' => '조건이 충족되면 룰이 실행됨'
            ];
        }
        
        return $scenarios;
    }
    
    /**
     * 조건 테스트 데이터 생성
     */
    private function generateConditionTestData($condition) {
        $testData = [];
        
        if (isset($condition['field'])) {
            $field = $condition['field'];
            $operator = $condition['operator'] ?? '==';
            $value = $condition['value'] ?? null;
            
            // 연산자별 테스트 데이터
            switch ($operator) {
                case '==':
                    $testData['match'] = [$field => $value];
                    $testData['no_match'] = [$field => 'different_value'];
                    break;
                case '!=':
                    $testData['match'] = [$field => 'different_value'];
                    $testData['no_match'] = [$field => $value];
                    break;
                case 'in':
                    $testData['match'] = [$field => is_array($value) ? $value[0] : $value];
                    $testData['no_match'] = [$field => 'not_in_list'];
                    break;
                case 'contains':
                    $testData['match'] = [$field => "text_with_{$value}"];
                    $testData['no_match'] = [$field => 'text_without_keyword'];
                    break;
                default:
                    $testData['match'] = [$field => $value];
                    $testData['no_match'] = [$field => 'different_value'];
            }
        }
        
        return $testData;
    }
    
    /**
     * 예상 결과 생성
     */
    private function generateExpectedOutcome($rule, $condition) {
        return [
            'rule_executed' => true,
            'actions_performed' => $rule['action'] ?? [],
            'confidence' => $rule['confidence'] ?? 0.5,
            'next_steps' => $this->generateNextSteps($rule)
        ];
    }
    
    /**
     * 실행 흐름 생성
     */
    private function generateExecutionFlow($rule) {
        return [
            'step_1' => '조건 평가',
            'step_2' => '조건 충족 여부 확인',
            'step_3' => '룰 실행 (우선순위: ' . ($rule['priority'] ?? 3) . ')',
            'step_4' => '액션 수행',
            'step_5' => '결과 반환 (신뢰도: ' . ($rule['confidence'] ?? 0.5) . ')'
        ];
    }
    
    /**
     * 상호작용 포인트 생성
     */
    private function generateInteractionPoints($rule) {
        $points = [];
        
        $actions = $rule['action'] ?? [];
        if (!is_array($actions)) {
            $actions = [$actions];
        }
        
        foreach ($actions as $index => $action) {
            $actionStr = is_array($action) ? json_encode($action) : $action;
            if (strpos($actionStr, 'question:') !== false || strpos($actionStr, 'display_message:') !== false) {
                $points[] = [
                    'interaction_id' => "INTERACTION_" . ($rule['rule_id'] ?? 'UNKNOWN') . "_{$index}",
                    'type' => strpos($actionStr, 'question:') !== false ? 'question' : 'message',
                    'action' => $action,
                    'trigger' => "룰 " . ($rule['rule_id'] ?? 'UNKNOWN') . " 실행 시",
                    'response_handling' => $this->generateResponseHandling($actionStr)
                ];
            }
        }
        
        return $points;
    }
    
    /**
     * 응답 처리 방법 생성
     */
    private function generateResponseHandling($action) {
        if (strpos($action, 'question:') !== false) {
            return [
                'type' => 'question_response',
                'handling' => [
                    '학생 응답 수집',
                    '응답 분석',
                    '다음 룰 트리거 결정',
                    '온톨로지 업데이트'
                ]
            ];
        }
        
        return [
            'type' => 'message_display',
            'handling' => [
                '메시지 표시',
                '사용자 확인 대기',
                '다음 단계 진행'
            ]
        ];
    }
    
    /**
     * 테스트 입력 생성
     */
    private function generateTestInput($rule, $type) {
        $input = [];
        
        $conditions = $rule['conditions'] ?? [];
        foreach ($conditions as $condition) {
            if (isset($condition['field'])) {
                $field = $condition['field'];
                
                if ($type === 'pass') {
                    // 조건을 만족하는 입력
                    if (isset($condition['value'])) {
                        $input[$field] = $condition['value'];
                    }
                } else {
                    // 조건을 만족하지 않는 입력
                    $input[$field] = 'non_matching_value';
                }
            }
        }
        
        return $input;
    }
    
    /**
     * 검증 기준 생성
     */
    private function generateValidationCriteria($rule) {
        return [
            '룰이 실행되었는가?',
            '모든 액션이 수행되었는가?',
            '신뢰도가 예상 범위 내인가?',
            '온톨로지가 올바르게 업데이트되었는가?',
            '다음 룰이 적절히 트리거되었는가?'
        ];
    }
    
    /**
     * 엣지 케이스 생성
     */
    private function generateEdgeCases($rule) {
        return [
            [
                'case' => '조건이 부분적으로 충족되는 경우',
                'description' => '일부 조건만 충족될 때의 동작',
                'handling' => 'OR 조건이 있으면 부분 충족도 허용'
            ],
            [
                'case' => '조건 값이 null인 경우',
                'description' => '필드가 존재하지 않거나 null일 때',
                'handling' => 'null 체크 후 적절한 기본값 사용'
            ],
            [
                'case' => '우선순위가 같은 룰이 여러 개인 경우',
                'description' => '동일 우선순위 룰 충돌',
                'handling' => '신뢰도가 높은 룰 우선 실행'
            ]
        ];
    }
    
    /**
     * 다음 단계 생성
     */
    private function generateNextSteps($rule) {
        $nextSteps = [];
        
        // 액션에서 다음 단계 추출
        $actions = $rule['action'] ?? [];
        if (!is_array($actions)) {
            $actions = [$actions];
        }
        
        foreach ($actions as $action) {
            $actionStr = is_array($action) ? json_encode($action) : $action;
            if (strpos($actionStr, 'recommend_path:') !== false) {
                $nextSteps[] = '추천 경로 확인';
            }
            if (strpos($actionStr, 'generate_strategy:') !== false) {
                $nextSteps[] = '전략 생성 및 적용';
            }
            if (strpos($actionStr, 'create_instance:') !== false) {
                $nextSteps[] = '온톨로지 인스턴스 생성';
            }
        }
        
        return array_unique($nextSteps);
    }
    
    /**
     * 컨텐츠 파일 저장
     */
    public function saveContents($contents, $basePath) {
        if (!is_dir($basePath)) {
            mkdir($basePath, 0755, true);
        }
        
        $savedFiles = [];
        
        foreach ($contents as $content) {
            $filename = $this->generateFilename($content);
            $filepath = $basePath . '/' . $filename;
            
            $data = [
                'content' => $content,
                'metadata' => [
                    'saved_at' => date('Y-m-d H:i:s'),
                    'version' => '1.0'
                ]
            ];
            
            file_put_contents($filepath, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $savedFiles[] = $filepath;
        }
        
        return $savedFiles;
    }
    
    /**
     * 파일명 생성
     */
    private function generateFilename($content) {
        $ruleId = str_replace([':', '/', '\\'], '_', $content['rule_id'] ?? 'unknown');
        $type = $content['type'] ?? 'content';
        $timestamp = date('YmdHis');
        
        return "{$type}_{$ruleId}_{$timestamp}.json";
    }
}

