<?php
/**
 * 페르소나 기반 맞춤 지도 시스템
 * 페르소나 식별 및 스위칭을 통한 맞춤형 지도
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

require_once(__DIR__ . '/persona_manager.php');
require_once(__DIR__ . '/interaction_engine.php');
require_once(__DIR__ . '/intervention_manager.php');

class PersonaBasedTutoring {
    private $personaManager;
    private $interactionEngine;
    private $interventionManager;
    private $currentPersona;
    private $personaHistory;
    private $switchingThreshold;
    
    public function __construct($rules, $ontology, $context = []) {
        $this->personaManager = new PersonaManager();
        $this->interactionEngine = new InteractionEngine($rules, $ontology, $context);
        $this->interventionManager = new InterventionManager();
        $this->currentPersona = null;
        $this->personaHistory = [];
        $this->switchingThreshold = 0.15; // 15% 이상 차이 시 스위칭
    }
    
    /**
     * 페르소나 기반 맞춤 지도 처리
     * 
     * @param string $userInput 사용자 입력
     * @param int $studentId 학생 ID
     * @param array $currentState 현재 상태
     * @return array 맞춤 지도 결과
     */
    public function processPersonaBasedTutoring($userInput, $studentId, $currentState = []) {
        // 1. 현재 페르소나 확인 또는 식별
        $this->identifyOrUpdatePersona($studentId, $userInput, $currentState);
        
        // 2. 페르소나 스위칭 확인
        $this->checkPersonaSwitching($studentId, $userInput, $currentState);
        
        // 3. 페르소나 기반 개입 전략 적용
        $interventionStrategy = $this->getCurrentInterventionStrategy();
        
        // 3-1. 트리거 신호 분석 및 개입 활동 선택
        $triggerSignals = $this->extractTriggerSignals($userInput, $currentState);
        $selectedInterventions = $this->selectAppropriateInterventions($triggerSignals);
        
        // 4. 맞춤형 응답 생성
        $customizedResponse = $this->generateCustomizedResponse(
            $userInput,
            $interventionStrategy,
            $currentState,
            $selectedInterventions
        );
        
        // 5. 상호작용 엔진 처리 (페르소나 정보 포함)
        $context = array_merge($currentState, [
            'persona' => $this->currentPersona,
            'intervention_strategy' => $interventionStrategy
        ]);
        
        $interactionResult = $this->interactionEngine->processInteraction($userInput, $context);
        
        // 6. 페르소나별 맞춤 지도 적용
        $tutoringResult = $this->applyPersonaSpecificTutoring(
            $interactionResult,
            $customizedResponse,
            $interventionStrategy
        );
        
        // 7. 페르소나 히스토리 업데이트
        $this->updatePersonaHistory($studentId, $this->currentPersona);
        
        return [
            'response' => $tutoringResult['response'],
            'persona' => [
                'current' => $this->currentPersona,
                'switched' => $tutoringResult['persona_switched'],
                'confidence' => $tutoringResult['persona_confidence']
            ],
            'intervention_strategy' => $interventionStrategy,
            'interventions' => $selectedInterventions,
            'customized_guidance' => $tutoringResult['customized_guidance'],
            'matched_rules' => $interactionResult['matched_rules'],
            'next_steps' => $tutoringResult['next_steps'],
            'interaction_id' => $interactionResult['interaction_id']
        ];
    }
    
    /**
     * 트리거 신호 추출
     */
    private function extractTriggerSignals($userInput, $currentState) {
        $signals = [];
        $inputLower = strtolower($userInput);
        
        // 텍스트 기반 신호 추출
        $signalPatterns = [
            '네?' => '되묻기',
            '다시요?' => '되묻기',
            '모르겠어요' => '막연한 모름',
            '왜 이렇게 되는지' => '이유 모름',
            '앞부분은 알겠는데' => '부분적 이해',
            'x가 뭔데요' => '변수 두려움',
            '나만 못해요' => '자책',
            '아 잠깐' => '자기 수정 시도',
            '그래서 뭐가 중요한 거예요' => '핵심 파악 못함'
        ];
        
        foreach ($signalPatterns as $pattern => $signal) {
            if (stripos($inputLower, $pattern) !== false) {
                $signals[] = $signal;
            }
        }
        
        // 컨텍스트 기반 신호
        if (isset($currentState['understanding_indicator'])) {
            if (in_array($currentState['understanding_indicator'], ['모르', '어려'])) {
                $signals[] = '이해 어려움';
            }
        }
        
        return array_unique($signals);
    }
    
    /**
     * 적절한 개입 활동 선택
     */
    private function selectAppropriateInterventions($triggerSignals) {
        $personaId = $this->currentPersona ? $this->currentPersona['persona_id'] : null;
        
        $candidates = $this->interventionManager->selectInterventionBySignals($triggerSignals, $personaId);
        
        // 상위 3개 반환
        return array_slice($candidates, 0, 3);
    }
    
    /**
     * 페르소나 식별 또는 업데이트
     */
    private function identifyOrUpdatePersona($studentId, $userInput, $currentState) {
        // 기존 페르소나 조회
        $existingPersonas = $this->personaManager->getStudentPersonas($studentId);
        
        if (!empty($existingPersonas)) {
            $this->currentPersona = $existingPersonas[0]['persona'];
        } else {
            // 새로 매칭
            $matches = $this->personaManager->matchStudentPersona($studentId, [
                'user_input' => $userInput,
                'context' => $currentState
            ]);
            
            if (!empty($matches) && $matches[0]['score'] > 5) {
                $this->currentPersona = $matches[0]['persona'];
                $this->personaManager->saveStudentPersona(
                    $studentId,
                    $this->currentPersona['persona_id'],
                    $matches[0]['match_percentage'] / 100
                );
            }
        }
    }
    
    /**
     * 페르소나 스위칭 확인
     */
    private function checkPersonaSwitching($studentId, $userInput, $currentState) {
        if (!$this->currentPersona) {
            return;
        }
        
        // 현재 상호작용 기반 재평가
        $newMatches = $this->personaManager->matchStudentPersona($studentId, [
            'user_input' => $userInput,
            'context' => $currentState
        ]);
        
        if (empty($newMatches)) {
            return;
        }
        
        $topMatch = $newMatches[0];
        $currentPersonaId = $this->currentPersona['persona_id'];
        $newPersonaId = $topMatch['persona']['persona_id'];
        
        // 다른 페르소나가 더 높은 점수를 받았는지 확인
        if ($newPersonaId !== $currentPersonaId) {
            $currentScore = 0;
            foreach ($newMatches as $match) {
                if ($match['persona']['persona_id'] === $currentPersonaId) {
                    $currentScore = $match['score'];
                    break;
                }
            }
            
            $newScore = $topMatch['score'];
            $scoreDiff = ($newScore - $currentScore) / max($newScore, 1);
            
            // 스위칭 임계값 초과 시 페르소나 변경
            if ($scoreDiff > $this->switchingThreshold) {
                $this->currentPersona = $topMatch['persona'];
                $this->personaManager->saveStudentPersona(
                    $studentId,
                    $newPersonaId,
                    $topMatch['match_percentage'] / 100
                );
                
                // 스위칭 이벤트 기록
                $this->recordPersonaSwitch($studentId, $currentPersonaId, $newPersonaId, $scoreDiff);
            }
        }
    }
    
    /**
     * 페르소나 스위칭 기록
     */
    private function recordPersonaSwitch($studentId, $fromPersonaId, $toPersonaId, $scoreDiff) {
        $db = $this->personaManager->getDB();
        
        if (!$db->tableExists('persona_switches')) {
            $db->createTable('persona_switches', [
                'student_id' => 'integer',
                'from_persona_id' => 'string',
                'to_persona_id' => 'string',
                'score_difference' => 'float',
                'switched_at' => 'datetime',
                'context' => 'object'
            ]);
            $db->createIndex('persona_switches', 'student_id');
        }
        
        $db->insert('persona_switches', [
            'student_id' => $studentId,
            'from_persona_id' => $fromPersonaId,
            'to_persona_id' => $toPersonaId,
            'score_difference' => $scoreDiff,
            'switched_at' => date('Y-m-d H:i:s'),
            'context' => []
        ]);
    }
    
    /**
     * 현재 개입 전략 가져오기
     */
    private function getCurrentInterventionStrategy() {
        if (!$this->currentPersona) {
            return null;
        }
        
        return $this->personaManager->getInterventionStrategy(
            $this->currentPersona['persona_id']
        );
    }
    
    /**
     * 맞춤형 응답 생성
     */
    private function generateCustomizedResponse($userInput, $interventionStrategy, $currentState, $selectedInterventions) {
        if (!$interventionStrategy) {
            return null;
        }
        
        $persona = $this->currentPersona;
        $interventions = $interventionStrategy['interventions'];
        $approach = $interventionStrategy['recommended_approach'];
        
        // 페르소나별 맞춤 응답 템플릿
        $responseTemplates = $this->getPersonaResponseTemplates($persona['persona_id']);
        
        // 선택된 개입 활동 적용
        $appliedInterventions = [];
        if (!empty($selectedInterventions)) {
            $topIntervention = $selectedInterventions[0]['intervention'];
            $appliedInterventions[] = $topIntervention;
            
            // 개입 활동 실행 기록
            if (isset($currentState['student_id'])) {
                $this->interventionManager->logInterventionExecution(
                    $topIntervention['activity_id'],
                    $currentState['student_id'],
                    [
                        'user_input' => $userInput,
                        'persona_id' => $persona['persona_id'] ?? null
                    ]
                );
            }
        }
        
        return [
            'tone' => $this->getPersonaTone($persona),
            'approach' => $approach,
            'interventions' => $interventions,
            'applied_interventions' => $appliedInterventions,
            'templates' => $responseTemplates,
            'guidance_style' => $this->getGuidanceStyle($persona)
        ];
    }
    
    /**
     * 페르소나별 응답 톤 가져오기
     */
    private function getPersonaTone($persona) {
        $toneMap = [
            'P001' => 'supportive', // 막힘-회피형: 지원적
            'P002' => 'encouraging', // 확인요구형: 격려적
            'P003' => 'empathetic', // 감정출렁형: 공감적
            'P004' => 'structured', // 빠른데 허술형: 구조화된
            'P005' => 'focused', // 집중 튐형: 집중 유도
            'P006' => 'intellectual', // 패턴추론형: 지적
            'P007' => 'efficient', // 최대한 쉬운길: 효율적
            'P008' => 'calming', // 불안과몰입형: 진정시키는
            'P009' => 'concrete', // 추상-언어 약함형: 구체적
            'P010' => 'interactive', // 상호작용 의존형: 상호작용적
            'P011' => 'motivating', // 무기력형: 동기부여
            'P012' => 'challenging' // 메타인지 고수형: 도전적
        ];
        
        return $toneMap[$persona['persona_id']] ?? 'friendly';
    }
    
    /**
     * 지도 스타일 가져오기
     */
    private function getGuidanceStyle($persona) {
        $category = $persona['metadata']['category'] ?? '';
        $difficulty = $persona['metadata']['difficulty_level'] ?? 'medium';
        
        $styles = [
            '인지적' => [
                'high' => ['step_by_step', 'visual_guide', 'chunking'],
                'medium' => ['structured', 'examples', 'gradual'],
                'low' => ['abstract', 'deep', 'strategic']
            ],
            '정서적' => [
                'high' => ['immediate_empathy', 'emotional_support', 'stability'],
                'medium' => ['feedback', 'success_experience', 'motivation'],
                'low' => ['challenge', 'autonomy', 'growth']
            ],
            '전략적' => [
                'high' => ['specific_strategy', 'step_guide'],
                'medium' => ['efficient_method', 'core_focus'],
                'low' => ['advanced_strategy', 'deep_learning', 'creative']
            ],
            '행동적' => [
                'high' => ['external_stimulus', 'step_feedback'],
                'medium' => ['self_direction_training', 'gradual_independence'],
                'low' => ['autonomous_learning', 'metacognition']
            ]
        ];
        
        return $styles[$category][$difficulty] ?? ['general'];
    }
    
    /**
     * 페르소나별 응답 템플릿 가져오기
     */
    private function getPersonaResponseTemplates($personaId) {
        $templates = [
            'P001' => [ // 막힘-회피형
                'encouragement' => '괜찮아요. 한 단계씩 천천히 해봐요.',
                'guidance' => '이 부분만 먼저 보세요. {hint}',
                'redirect' => '여기를 다시 한번 봐볼까요? {focus_point}'
            ],
            'P002' => [ // 확인요구형
                'encouragement' => '잘하고 있어요! 계속 진행해보세요.',
                'guidance' => '지금까지 {progress}% 완료했어요. 다음 단계는...',
                'feedback' => '좋은 방향이에요. 이렇게 계속 해보세요.'
            ],
            'P003' => [ // 감정출렁형
                'empathy' => '아, 좀 어려웠나요? 괜찮아요.',
                'stabilization' => '한 문제 틀린 건 괜찮아요. 다음 문제로 가볼까요?',
                'encouragement' => '지금까지 잘 해왔어요. 계속 해볼 수 있어요.'
            ],
            'P004' => [ // 빠른데 허술형
                'verification' => '잠깐, 마지막 10초만 검증해볼까요?',
                'checklist' => '이 체크리스트를 확인해보세요: {checklist}',
                'pace' => '조금 천천히, 정확하게 해보는 게 중요해요.'
            ],
            'P005' => [ // 집중 튐형
                'focus' => '여기를 집중해서 봐볼까요? {highlight}',
                'redirect' => '시선을 여기로 옮겨볼까요? {focus_point}',
                'chunking' => '한 문장씩 읽어볼까요?'
            ],
            'P006' => [ // 패턴추론형
                'structure' => '전체 구조는 이렇습니다: {structure}',
                'pattern' => '여기서 패턴을 찾아볼까요? {pattern}',
                'deep' => '왜 이렇게 되는지 원리를 알아볼까요?'
            ],
            'P007' => [ // 최대한 쉬운길 찾기형
                'core' => '핵심 규칙은 이것이에요: {core_rule}',
                'shortcut' => '가장 빠른 방법은...',
                'efficient' => '이 유형은 이렇게 풀면 돼요.'
            ],
            'P008' => [ // 불안과몰입형
                'time_limit' => '5분만 더 확인하고 넘어가볼까요?',
                'limit' => '여기까지만 확인하고 다음으로 가요.',
                'reassurance' => '충분히 확인했어요. 다음 단계로 가볼까요?'
            ],
            'P009' => [ // 추상-언어 약함형
                'example' => '예시를 하나 더 볼까요? {example}',
                'concrete' => '구체적으로 이렇게 해요: {concrete_step}',
                'gradual' => '예시 → 규칙 → 적용 순서로 해볼까요?'
            ],
            'P010' => [ // 상호작용 의존형
                'stimulus' => '다음 단계는 이거예요. {next_step}',
                'interactive' => '이 부분에 대해 어떻게 생각하세요?',
                'feedback' => '잘하고 있어요! 계속 진행해볼까요?'
            ],
            'P011' => [ // 무기력형
                'micro_goal' => '지금 이 한 문제만 풀어볼까요?',
                'immediate' => '좋아요! 바로 다음 단계로 가볼까요?',
                'metacognition' => '지금 막힌 이유가 뭘까요?'
            ],
            'P012' => [ // 메타인지 고수형
                'challenge' => '더 어려운 문제로 도전해볼까요?',
                'comparison' => '다른 풀이 방법과 비교해볼까요?',
                'deep' => '이 문제의 본질은 무엇일까요?'
            ]
        ];
        
        return $templates[$personaId] ?? [];
    }
    
    /**
     * 페르소나별 맞춤 지도 적용
     */
    private function applyPersonaSpecificTutoring($interactionResult, $customizedResponse, $interventionStrategy) {
        if (!$this->currentPersona || !$customizedResponse) {
            return [
                'response' => $interactionResult['response'],
                'persona_switched' => false,
                'persona_confidence' => 0,
                'customized_guidance' => null,
                'next_steps' => $interactionResult['next_steps']
            ];
        }
        
        $persona = $this->currentPersona;
        $baseResponse = $interactionResult['response'];
        
        // 개입 활동 기반 응답 생성
        $interventionText = $this->buildInterventionResponse($customizedResponse);
        
        // 페르소나별 맞춤 응답 생성
        $customizedText = $this->buildCustomizedResponseText(
            $baseResponse['text'],
            $customizedResponse,
            $persona
        );
        
        // 개입 활동 텍스트 추가
        if ($interventionText) {
            $customizedText = $interventionText . "\n\n" . $customizedText;
        }
        
        // 페르소나별 질문 추가
        $personaQuestions = $this->generatePersonaQuestions($persona, $interactionResult);
        
        // 페르소나별 제안 추가
        $personaSuggestions = $this->generatePersonaSuggestions($persona, $interventionStrategy);
        
        return [
            'response' => [
                'text' => $customizedText,
                'actions' => array_merge($baseResponse['actions'] ?? [], $personaSuggestions),
                'questions' => array_merge($baseResponse['questions'] ?? [], $personaQuestions),
                'suggestions' => array_merge($baseResponse['suggestions'] ?? [], $personaSuggestions)
            ],
            'persona_switched' => false, // 스위칭은 이미 처리됨
            'persona_confidence' => $this->getPersonaConfidence(),
            'customized_guidance' => [
                'persona' => $persona['name'],
                'tone' => $customizedResponse['tone'],
                'approach' => $customizedResponse['approach'],
                'guidance_style' => $customizedResponse['guidance_style'],
                'applied_interventions' => $customizedResponse['applied_interventions'] ?? []
            ],
            'next_steps' => $this->generatePersonaNextSteps($persona, $interactionResult['next_steps'])
        ];
    }
    
    /**
     * 개입 활동 기반 응답 생성
     */
    private function buildInterventionResponse($customizedResponse) {
        if (empty($customizedResponse['applied_interventions'])) {
            return '';
        }
        
        $intervention = $customizedResponse['applied_interventions'][0];
        $category = $intervention['category'];
        $name = $intervention['name'];
        $description = $intervention['description'];
        
        // 카테고리별 응답 템플릿
        $templates = [
            'pause_wait' => "잠깐, {description}",
            'repeat_rephrase' => "{description} 다시 설명해볼게요.",
            'alternative_explanation' => "다른 방법으로 설명해볼게요. {description}",
            'emphasis_alerting' => "여기서 중요한 건, {description}",
            'questioning_probing' => "{description}",
            'immediate_intervention' => "잠깐! {description}",
            'emotional_regulation' => "{description}"
        ];
        
        $template = $templates[$category] ?? "{description}";
        return str_replace('{description}', $description, $template);
    }
    
    /**
     * 맞춤형 응답 텍스트 생성
     */
    private function buildCustomizedResponseText($baseText, $customizedResponse, $persona) {
        $tone = $customizedResponse['tone'];
        $templates = $customizedResponse['templates'];
        
        // 톤에 맞는 시작 문구
        $tonePrefixes = [
            'supportive' => '괜찮아요. ',
            'encouraging' => '잘하고 있어요! ',
            'empathetic' => '이해해요. ',
            'structured' => '단계별로 해볼까요? ',
            'focused' => '여기를 집중해서 봐볼까요? ',
            'intellectual' => '흥미로운 문제네요. ',
            'efficient' => '효율적으로 해볼까요? ',
            'calming' => '천천히 해볼까요? ',
            'concrete' => '구체적으로 해볼까요? ',
            'interactive' => '함께 해볼까요? ',
            'motivating' => '좋아요! ',
            'challenging' => '도전해볼까요? '
        ];
        
        $prefix = $tonePrefixes[$tone] ?? '';
        
        return $prefix . $baseText;
    }
    
    /**
     * 페르소나별 질문 생성
     */
    private function generatePersonaQuestions($persona, $interactionResult) {
        $personaId = $persona['persona_id'];
        $questions = [];
        
        switch ($personaId) {
            case 'P001': // 막힘-회피형
                $questions[] = '어느 부분이 막히나요?';
                $questions[] = '이 단계만 먼저 해볼까요?';
                break;
            case 'P002': // 확인요구형
                $questions[] = '지금까지 잘하고 있어요. 다음 단계는?';
                break;
            case 'P011': // 무기력형
                $questions[] = '지금 막힌 이유가 뭘까요?';
                $questions[] = '이 한 문제만 풀어볼까요?';
                break;
            case 'P012': // 메타인지 고수형
                $questions[] = '다른 방법으로도 풀어볼까요?';
                $questions[] = '이 문제의 핵심은 무엇일까요?';
                break;
        }
        
        return $questions;
    }
    
    /**
     * 페르소나별 제안 생성
     */
    private function generatePersonaSuggestions($persona, $interventionStrategy) {
        $suggestions = [];
        
        foreach ($interventionStrategy['interventions'] as $intervention) {
            $suggestions[] = $intervention;
        }
        
        return $suggestions;
    }
    
    /**
     * 페르소나별 다음 단계 생성
     */
    private function generatePersonaNextSteps($persona, $baseNextSteps) {
        $personaId = $persona['persona_id'];
        $nextSteps = $baseNextSteps;
        
        // 페르소나별 특별한 다음 단계 추가
        switch ($personaId) {
            case 'P001': // 막힘-회피형
                $nextSteps[] = [
                    'type' => 'chunking',
                    'content' => '문제를 작은 단위로 나누어 풀기'
                ];
                break;
            case 'P004': // 빠른데 허술형
                $nextSteps[] = [
                    'type' => 'verification',
                    'content' => '마지막 10초 검증 루틴 실행'
                ];
                break;
            case 'P005': // 집중 튐형
                $nextSteps[] = [
                    'type' => 'focus',
                    'content' => '시선 리다이렉션 및 하이라이트 가이드'
                ];
                break;
        }
        
        return $nextSteps;
    }
    
    /**
     * 페르소나 신뢰도 가져오기
     */
    private function getPersonaConfidence() {
        // TODO: 실제 신뢰도 계산 로직 구현
        return 0.8;
    }
    
    /**
     * 페르소나 히스토리 업데이트
     */
    private function updatePersonaHistory($studentId, $persona) {
        $this->personaHistory[] = [
            'student_id' => $studentId,
            'persona_id' => $persona['persona_id'],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * 페르소나 히스토리 조회
     */
    public function getPersonaHistory($studentId) {
        return array_filter($this->personaHistory, function($entry) use ($studentId) {
            return $entry['student_id'] == $studentId;
        });
    }
    
    /**
     * DB 접근
     */
    private function getDB() {
        return $this->personaManager->getDB();
    }
}

