<?php
/**
 * 상호작용 엔진
 * 룰과 온톨로지를 사용하여 매끄러운 상호작용 구현
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

require_once(__DIR__ . '/rule_evaluator.php');

class InteractionEngine {
    private $ruleEvaluator;
    private $ontology;
    private $context;
    private $interactionHistory;
    
    public function __construct($rules, $ontology, $context = []) {
        $this->ruleEvaluator = new RuleEvaluator($rules);
        $this->ontology = $ontology;
        $this->context = $context;
        $this->interactionHistory = [];
    }
    
    /**
     * 상호작용 처리
     * 
     * @param string $userInput 사용자 입력
     * @param array $currentState 현재 상태
     * @return array 상호작용 결과
     */
    public function processInteraction($userInput, $currentState = []) {
        // 1. 컨텍스트 업데이트
        $this->updateContext($userInput, $currentState);
        
        // 2. 룰 평가
        $matchedRules = $this->ruleEvaluator->evaluate($this->context);
        
        // 3. 온톨로지 기반 추론
        $ontologyInference = $this->inferFromOntology($matchedRules);
        
        // 4. 상호작용 전략 결정
        $interactionStrategy = $this->determineInteractionStrategy($matchedRules, $ontologyInference);
        
        // 5. 응답 생성
        $response = $this->generateResponse($interactionStrategy, $userInput);
        
        // 6. 상호작용 히스토리 업데이트
        $this->updateInteractionHistory($userInput, $response, $matchedRules);
        
        // 7. 다음 단계 결정
        $nextSteps = $this->determineNextSteps($matchedRules, $ontologyInference);
        
        return [
            'response' => $response,
            'matched_rules' => $matchedRules,
            'ontology_inference' => $ontologyInference,
            'interaction_strategy' => $interactionStrategy,
            'next_steps' => $nextSteps,
            'context_updated' => $this->context,
            'interaction_id' => uniqid('INT_', true)
        ];
    }
    
    /**
     * 컨텍스트 업데이트
     */
    private function updateContext($userInput, $currentState) {
        // 사용자 입력에서 정보 추출
        $extractedInfo = $this->extractInfoFromInput($userInput);
        
        // 현재 상태와 병합
        $this->context = array_merge($this->context, $currentState, $extractedInfo);
        
        // 온톨로지 업데이트
        $this->updateOntologyFromContext();
    }
    
    /**
     * 사용자 입력에서 정보 추출
     */
    private function extractInfoFromInput($input) {
        $extracted = [];
        
        // 단원 정보 추출
        if (preg_match('/(이차방정식|함수|미분|적분|평면도형|입체도형)/u', $input, $matches)) {
            $extracted['current_unit'] = $matches[1];
        }
        
        // 이해도 표현 추출
        if (preg_match('/(이해|알겠|모르|어려|쉬워|맞아|틀려)/u', $input, $matches)) {
            $extracted['understanding_indicator'] = $matches[1];
        }
        
        // 질문 유형 추출
        if (preg_match('/(어떻게|왜|무엇|어디|언제|누구)/u', $input, $matches)) {
            $extracted['question_type'] = $matches[1];
        }
        
        return $extracted;
    }
    
    /**
     * 온톨로지 기반 추론
     */
    private function inferFromOntology($matchedRules) {
        $inference = [
            'will_alignment' => [],
            'intent_alignment' => [],
            'reasoning_applied' => [],
            'ontology_updates' => []
        ];
        
        if (!isset($this->ontology['will'])) {
            return $inference;
        }
        
        // Will Layer 정렬 확인
        foreach ($this->ontology['will']['core'] as $will) {
            foreach ($matchedRules as $rule) {
                if ($this->isRuleAlignedWithWill($rule, $will)) {
                    $inference['will_alignment'][] = [
                        'will' => $will['value'],
                        'rule' => $rule['rule_id'],
                        'alignment_score' => $this->calculateAlignmentScore($rule, $will)
                    ];
                }
            }
        }
        
        // Intent Layer 정렬 확인
        if (isset($this->ontology['intent'])) {
            foreach ($matchedRules as $rule) {
                if ($this->isRuleAlignedWithIntent($rule, $this->ontology['intent'])) {
                    $inference['intent_alignment'][] = [
                        'intent' => $this->ontology['intent']['session_goal'],
                        'rule' => $rule['rule_id']
                    ];
                }
            }
        }
        
        // Reasoning 적용
        if (isset($this->ontology['reasoning']['cosmology'])) {
            $inference['reasoning_applied'] = $this->applyReasoning($matchedRules);
        }
        
        return $inference;
    }
    
    /**
     * 룰이 Will과 정렬되는지 확인
     */
    private function isRuleAlignedWithWill($rule, $will) {
        // 룰의 액션이 Will의 가치를 반영하는지 확인
        foreach ($rule['action'] as $action) {
            if (strpos($action, $will['value']) !== false) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 룰이 Intent와 정렬되는지 확인
     */
    private function isRuleAlignedWithIntent($rule, $intent) {
        // 룰의 설명이나 액션이 Intent와 관련있는지 확인
        $ruleText = $rule['description'] . ' ' . implode(' ', $rule['action']);
        $intentText = $intent['session_goal'] . ' ' . $intent['short_term'];
        
        // 간단한 키워드 매칭
        $keywords = ['학습', '개념', '이해', '문제', '단원'];
        foreach ($keywords as $keyword) {
            if (strpos($ruleText, $keyword) !== false && strpos($intentText, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 정렬 점수 계산
     */
    private function calculateAlignmentScore($rule, $will) {
        $score = 0.5; // 기본 점수
        
        // Will의 우선순위 반영
        if (isset($will['priority'])) {
            $score += ($will['priority'] / 100) * 0.3;
        }
        
        // 룰의 신뢰도 반영
        if (isset($rule['confidence'])) {
            $score += $rule['confidence'] * 0.2;
        }
        
        return min(1.0, $score);
    }
    
    /**
     * Reasoning 적용
     */
    private function applyReasoning($matchedRules) {
        $applied = [];
        
        if (!isset($this->ontology['reasoning']['cosmology'])) {
            return $applied;
        }
        
        $cosmology = $this->ontology['reasoning']['cosmology'];
        
        // 각 룰에 대해 Reasoning 적용
        foreach ($matchedRules as $rule) {
            $reasoningResult = [
                'rule_id' => $rule['rule_id'],
                'possibility' => $cosmology['possibility'] ?? '',
                'duality' => $cosmology['duality'] ?? '',
                'tension' => $cosmology['tension'] ?? '',
                'meaning' => $cosmology['meaning'] ?? ''
            ];
            
            $applied[] = $reasoningResult;
        }
        
        return $applied;
    }
    
    /**
     * 상호작용 전략 결정
     */
    private function determineInteractionStrategy($matchedRules, $ontologyInference) {
        $strategy = [
            'tone' => 'friendly',
            'approach' => 'guided',
            'pace' => 'moderate',
            'focus' => []
        ];
        
        // Will 정렬 기반 톤 결정
        if (!empty($ontologyInference['will_alignment'])) {
            $strategy['tone'] = 'supportive'; // Will 정렬 시 지원적 톤
        }
        
        // Intent 기반 접근 방식 결정
        if (!empty($ontologyInference['intent_alignment'])) {
            $strategy['approach'] = 'goal_oriented';
        }
        
        // 룰 우선순위 기반 속도 결정
        if (!empty($matchedRules)) {
            $maxPriority = max(array_column($matchedRules, 'priority'));
            if ($maxPriority >= 95) {
                $strategy['pace'] = 'urgent';
            } elseif ($maxPriority >= 90) {
                $strategy['pace'] = 'moderate';
            } else {
                $strategy['pace'] = 'relaxed';
            }
        }
        
        // 포커스 영역 결정
        foreach ($matchedRules as $rule) {
            if (isset($rule['description'])) {
                $strategy['focus'][] = $rule['description'];
            }
        }
        
        return $strategy;
    }
    
    /**
     * 응답 생성
     */
    private function generateResponse($strategy, $userInput) {
        $response = [
            'text' => '',
            'actions' => [],
            'questions' => [],
            'suggestions' => []
        ];
        
        // 전략에 따른 응답 생성
        if ($strategy['tone'] === 'supportive') {
            $response['text'] = "좋은 질문이에요! ";
        } else {
            $response['text'] = "알겠습니다. ";
        }
        
        // 사용자 입력에 대한 구체적 응답
        if (strpos($userInput, '이해') !== false || strpos($userInput, '알겠') !== false) {
            $response['text'] .= "이해하셨다니 다행이에요. ";
            $response['suggestions'][] = "다음 단계로 넘어가볼까요?";
        } elseif (strpos($userInput, '모르') !== false || strpos($userInput, '어려') !== false) {
            $response['text'] .= "괜찮아요. 천천히 다시 설명해드릴게요. ";
            $response['actions'][] = "기본 개념부터 다시 설명";
        } else {
            $response['text'] .= "더 자세히 설명해드릴게요. ";
        }
        
        // 룰 기반 질문 추가
        $response['questions'] = $this->generateContextualQuestions($strategy);
        
        return $response;
    }
    
    /**
     * 맥락적 질문 생성
     */
    private function generateContextualQuestions($strategy) {
        $questions = [];
        
        if (!empty($strategy['focus'])) {
            foreach ($strategy['focus'] as $focus) {
                if (strpos($focus, '개념') !== false) {
                    $questions[] = "이 개념에 대해 더 알고 싶은 부분이 있나요?";
                } elseif (strpos($focus, '문제') !== false) {
                    $questions[] = "문제 풀이에서 막히는 부분이 있나요?";
                }
            }
        }
        
        return $questions;
    }
    
    /**
     * 다음 단계 결정
     */
    private function determineNextSteps($matchedRules, $ontologyInference) {
        $nextSteps = [];
        
        // 룰의 액션에서 다음 단계 추출
        foreach ($matchedRules as $rule) {
            foreach ($rule['action'] as $action) {
                if (strpos($action, 'recommend_path:') !== false) {
                    $nextSteps[] = [
                        'type' => 'recommendation',
                        'content' => str_replace('recommend_path: ', '', $action)
                    ];
                }
                if (strpos($action, 'generate_strategy:') !== false) {
                    $nextSteps[] = [
                        'type' => 'strategy',
                        'content' => str_replace('generate_strategy: ', '', $action)
                    ];
                }
            }
        }
        
        // 온톨로지 기반 다음 단계
        if (isset($this->ontology['ontology'])) {
            foreach ($this->ontology['ontology'] as $node) {
                if ($node['stage'] === 'Execution' && isset($node['properties']['hasActionSteps'])) {
                    $nextSteps[] = [
                        'type' => 'execution',
                        'content' => $node['properties']['hasActionSteps']
                    ];
                }
            }
        }
        
        return $nextSteps;
    }
    
    /**
     * 온톨로지 업데이트
     */
    private function updateOntologyFromContext() {
        // 컨텍스트 변경사항을 온톨로지에 반영
        if (isset($this->context['current_unit']) && isset($this->ontology['ontology'])) {
            foreach ($this->ontology['ontology'] as &$node) {
                if ($node['class'] === 'mk:UnitLearningContext') {
                    $node['properties']['hasCurrentUnit'] = $this->context['current_unit'];
                }
            }
        }
    }
    
    /**
     * 상호작용 히스토리 업데이트
     */
    private function updateInteractionHistory($userInput, $response, $matchedRules) {
        $this->interactionHistory[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_input' => $userInput,
            'response' => $response,
            'matched_rules' => array_column($matchedRules, 'rule_id'),
            'context_snapshot' => $this->context
        ];
    }
    
    /**
     * 상호작용 히스토리 조회
     */
    public function getInteractionHistory() {
        return $this->interactionHistory;
    }
    
    /**
     * 컨텍스트 조회
     */
    public function getContext() {
        return $this->context;
    }
}

