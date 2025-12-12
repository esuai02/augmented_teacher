<?php
/**
 * Agent05PersonaEngine - 학습 감정 분석 페르소나 엔진
 *
 * Agent05 학습 감정 분석 에이전트의 페르소나 엔진
 * AbstractPersonaEngine을 상속받아 학습 감정 분석에 특화된 기능 구현
 *
 * @package AugmentedTeacher\Agent05\PersonaSystem
 * @version 1.0
 * @author Claude Code
 *
 * 주요 기능:
 * - 8가지 학습 활동별 감정 분석 (개념이해, 유형학습, 문제풀이, 오답노트, QA, 복습, 뽀모도로, 홈체크)
 * - 활동별 페르소나 식별 (정리형, 반복형, 탐색형, 저항형, 도전형 등)
 * - 수학 학습 감정 패턴 분석 (불안, 좌절, 자신감, 성취감 등)
 * - 에이전트 간 감정 상태 공유
 */

namespace AugmentedTeacher\Agent05\PersonaSystem;

// Moodle 환경 로드
if (!defined('MOODLE_INTERNAL')) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $DB, $USER;

// 공통 엔진 로드
require_once(dirname(__FILE__) . '/../../../../ontology_engineering/persona_engine/core/AbstractPersonaEngine.php');

use AugmentedTeacher\PersonaEngine\Core\AbstractPersonaEngine;

// 에이전트 전용 컴포넌트 로드
require_once(__DIR__ . '/Agent05DataContext.php');
require_once(__DIR__ . '/Agent05ResponseGenerator.php');
require_once(__DIR__ . '/EmotionAnalyzer.php');
require_once(__DIR__ . '/LearningActivityDetector.php');

class Agent05PersonaEngine extends AbstractPersonaEngine {

    /** @var string 에이전트 ID */
    protected $agentId = 'agent05';

    /** @var string 현재 파일 경로 */
    protected $currentFile = __FILE__;

    /** @var EmotionAnalyzer 학습 감정 분석기 */
    protected $emotionAnalyzer;

    /** @var LearningActivityDetector 학습 활동 감지기 */
    protected $activityDetector;

    /** @var array 학습 활동 타입 */
    protected $activityTypes = [
        'concept_understanding',  // 개념 이해
        'type_learning',          // 유형 학습
        'problem_solving',        // 문제 풀이
        'error_note',             // 오답 노트
        'qa',                     // 질문 응답
        'review',                 // 복습
        'pomodoro',               // 뽀모도로 학습
        'home_check'              // 홈 체크
    ];

    /** @var array 활동별 페르소나 매핑 */
    protected $activityPersonaMap = [
        'concept_understanding' => ['정리형', '반복형', '탐색형', '저항형'],
        'type_learning' => ['패턴인식형', '암기형', '유추형', '회피형'],
        'problem_solving' => ['도전형', '보조형', '완벽형', '회피형'],
        'error_note' => ['분석형', '반성형', '방어형', '회피형'],
        'qa' => ['적극형', '수동형', '확인형', '방어형'],
        'review' => ['체계형', '반복형', '선택형', '회피형'],
        'pomodoro' => ['집중형', '분산형', '적응형', '이탈형'],
        'home_check' => ['성실형', '지연형', '선택형', '회피형']
    ];

    /**
     * 생성자
     *
     * @param array $config 설정 배열
     */
    public function __construct(array $config = []) {
        parent::__construct('agent05', $config);
    }

    /**
     * 컴포넌트 초기화
     *
     * @return void
     */
    protected function initializeComponents(): void {
        // 에이전트 전용 컴포넌트 초기화
        $this->dataContext = new Agent05DataContext();
        $this->responseGenerator = new Agent05ResponseGenerator();
        $this->emotionAnalyzer = new EmotionAnalyzer();
        $this->activityDetector = new LearningActivityDetector();

        // 공통 컴포넌트는 기본 구현 사용
        $basePath = dirname(__FILE__) . '/../../../../ontology_engineering/persona_engine/impl/';

        // BaseConditionEvaluator 로드
        if (file_exists($basePath . 'BaseConditionEvaluator.php')) {
            require_once($basePath . 'BaseConditionEvaluator.php');
            $this->conditionEvaluator = new \AugmentedTeacher\PersonaEngine\Impl\BaseConditionEvaluator();
        }

        // BaseActionExecutor 로드
        if (file_exists($basePath . 'BaseActionExecutor.php')) {
            require_once($basePath . 'BaseActionExecutor.php');
            $this->actionExecutor = new \AugmentedTeacher\PersonaEngine\Impl\BaseActionExecutor();
        }

        // BaseRuleParser 로드
        if (file_exists($basePath . 'BaseRuleParser.php')) {
            require_once($basePath . 'BaseRuleParser.php');
            $this->ruleParser = new \AugmentedTeacher\PersonaEngine\Impl\BaseRuleParser();
        }

        // 규칙 파일 로드
        $rulesPath = dirname(__FILE__) . '/../../rules/rules.yaml';
        if (file_exists($rulesPath)) {
            $this->loadRules($rulesPath);
        }
    }

    /**
     * 에이전트별 페르소나 정의 반환
     *
     * @return array 페르소나 정의 배열
     */
    public function getPersonaDefinitions(): array {
        return [
            // === 개념 이해 활동 페르소나 ===
            'CU_정리형' => [
                'id' => 'CU_정리형',
                'name' => '개념 정리형 학습자',
                'activity' => 'concept_understanding',
                'description' => '개념을 체계적으로 정리하며 이해하는 학습자',
                'characteristics' => ['노트 필기', '요약 정리', '구조화'],
                'tone' => 'Professional',
                'intervention' => 'SkillBuilding'
            ],
            'CU_반복형' => [
                'id' => 'CU_반복형',
                'name' => '개념 반복형 학습자',
                'activity' => 'concept_understanding',
                'description' => '반복 학습을 통해 개념을 익히는 학습자',
                'characteristics' => ['반복 학습', '암기 중심', '확인 반복'],
                'tone' => 'Encouraging',
                'intervention' => 'GuidedPractice'
            ],
            'CU_탐색형' => [
                'id' => 'CU_탐색형',
                'name' => '개념 탐색형 학습자',
                'activity' => 'concept_understanding',
                'description' => '호기심을 가지고 개념을 탐구하는 학습자',
                'characteristics' => ['질문 많음', '원리 탐구', '연결 탐색'],
                'tone' => 'Warm',
                'intervention' => 'InformationProvision'
            ],
            'CU_저항형' => [
                'id' => 'CU_저항형',
                'name' => '개념 저항형 학습자',
                'activity' => 'concept_understanding',
                'description' => '새로운 개념 수용에 어려움을 겪는 학습자',
                'characteristics' => ['거부감', '회의적', '수용 어려움'],
                'tone' => 'Calm',
                'intervention' => 'EmotionalSupport'
            ],

            // === 문제 풀이 활동 페르소나 ===
            'PS_도전형' => [
                'id' => 'PS_도전형',
                'name' => '문제 도전형 학습자',
                'activity' => 'problem_solving',
                'description' => '어려운 문제에 적극적으로 도전하는 학습자',
                'characteristics' => ['높은 자신감', '도전 욕구', '끈기'],
                'tone' => 'Encouraging',
                'intervention' => 'ChallengeScaffolding'
            ],
            'PS_보조형' => [
                'id' => 'PS_보조형',
                'name' => '문제 보조형 학습자',
                'activity' => 'problem_solving',
                'description' => '도움을 받으며 문제를 해결하는 학습자',
                'characteristics' => ['힌트 필요', '단계별 지원', '안내 선호'],
                'tone' => 'Supportive',
                'intervention' => 'StepByStepGuidance'
            ],
            'PS_완벽형' => [
                'id' => 'PS_완벽형',
                'name' => '문제 완벽형 학습자',
                'activity' => 'problem_solving',
                'description' => '완벽한 풀이를 추구하는 학습자',
                'characteristics' => ['세밀함', '검토 반복', '실수 불안'],
                'tone' => 'Reassuring',
                'intervention' => 'ConfidenceBuilding'
            ],
            'PS_회피형' => [
                'id' => 'PS_회피형',
                'name' => '문제 회피형 학습자',
                'activity' => 'problem_solving',
                'description' => '어려운 문제를 피하려는 학습자',
                'characteristics' => ['두려움', '포기 경향', '자신감 부족'],
                'tone' => 'Empathetic',
                'intervention' => 'MotivationSupport'
            ],

            // === 오답 노트 활동 페르소나 ===
            'EN_분석형' => [
                'id' => 'EN_분석형',
                'name' => '오답 분석형 학습자',
                'activity' => 'error_note',
                'description' => '오답 원인을 체계적으로 분석하는 학습자',
                'characteristics' => ['원인 분석', '패턴 파악', '개선 계획'],
                'tone' => 'Professional',
                'intervention' => 'AnalyticalFeedback'
            ],
            'EN_반성형' => [
                'id' => 'EN_반성형',
                'name' => '오답 반성형 학습자',
                'activity' => 'error_note',
                'description' => '실수를 깊이 반성하는 학습자',
                'characteristics' => ['자기 비판', '과도한 반성', '감정적'],
                'tone' => 'Reassuring',
                'intervention' => 'EmotionalSupport'
            ],
            'EN_방어형' => [
                'id' => 'EN_방어형',
                'name' => '오답 방어형 학습자',
                'activity' => 'error_note',
                'description' => '오답을 외부 요인으로 돌리는 학습자',
                'characteristics' => ['변명', '외부 귀인', '수용 거부'],
                'tone' => 'Calm',
                'intervention' => 'GentleConfrontation'
            ],
            'EN_회피형' => [
                'id' => 'EN_회피형',
                'name' => '오답 회피형 학습자',
                'activity' => 'error_note',
                'description' => '오답 분석을 회피하는 학습자',
                'characteristics' => ['무관심', '빠른 진행', '분석 거부'],
                'tone' => 'Encouraging',
                'intervention' => 'ValueClarification'
            ],

            // === 복습 활동 페르소나 ===
            'RV_체계형' => [
                'id' => 'RV_체계형',
                'name' => '복습 체계형 학습자',
                'activity' => 'review',
                'description' => '체계적인 복습 계획을 따르는 학습자',
                'characteristics' => ['계획적', '규칙적', '전체 복습'],
                'tone' => 'Professional',
                'intervention' => 'StructuredReview'
            ],
            'RV_반복형' => [
                'id' => 'RV_반복형',
                'name' => '복습 반복형 학습자',
                'activity' => 'review',
                'description' => '반복 복습으로 내용을 익히는 학습자',
                'characteristics' => ['반복 학습', '암기 중심', '확인 중심'],
                'tone' => 'Encouraging',
                'intervention' => 'ReinforcementPractice'
            ],
            'RV_선택형' => [
                'id' => 'RV_선택형',
                'name' => '복습 선택형 학습자',
                'activity' => 'review',
                'description' => '필요한 부분만 선택적으로 복습하는 학습자',
                'characteristics' => ['효율 추구', '취약점 중심', '선별적'],
                'tone' => 'Warm',
                'intervention' => 'TargetedReview'
            ],
            'RV_회피형' => [
                'id' => 'RV_회피형',
                'name' => '복습 회피형 학습자',
                'activity' => 'review',
                'description' => '복습을 미루거나 회피하는 학습자',
                'characteristics' => ['지연', '무관심', '복습 거부'],
                'tone' => 'Supportive',
                'intervention' => 'MotivationBuilding'
            ],

            // === 기본 페르소나 ===
            'default' => [
                'id' => 'default',
                'name' => '관찰 중인 학습자',
                'activity' => 'unknown',
                'description' => '아직 학습 패턴이 파악되지 않은 학습자',
                'characteristics' => ['관찰 필요', '데이터 부족'],
                'tone' => 'Warm',
                'intervention' => 'InformationProvision'
            ]
        ];
    }

    /**
     * 기본 페르소나 ID 반환
     *
     * @return string 기본 페르소나 ID
     */
    public function getDefaultPersonaId(): string {
        return 'default';
    }

    /**
     * 메시지 분석 (학습 감정 특화)
     *
     * @param string $message 메시지
     * @param array $context 컨텍스트
     * @return array 분석 결과
     */
    protected function analyzeMessage(string $message, array $context): array {
        // 기본 분석
        $analysis = parent::analyzeMessage($message, $context);

        // 학습 활동 감지
        $activity = $this->activityDetector->detect($message, $context);
        $analysis['detected_activity'] = $activity['type'];
        $analysis['activity_confidence'] = $activity['confidence'];
        $analysis['activity_stage'] = $activity['stage'] ?? 'unknown';

        // 학습 감정 분석
        $emotion = $this->emotionAnalyzer->analyze($message, $context);
        $analysis['learning_emotion'] = $emotion['primary'];
        $analysis['emotion_intensity'] = $emotion['intensity'];
        $analysis['emotion_triggers'] = $emotion['triggers'];
        $analysis['emotion_history'] = $emotion['history'] ?? [];

        // 수학 특화 분석
        if (isset($context['subject']) && $context['subject'] === 'math') {
            $mathEmotion = $this->emotionAnalyzer->analyzeMathSpecific($message, $context);
            $analysis['math_anxiety'] = $mathEmotion['anxiety_level'];
            $analysis['math_confidence'] = $mathEmotion['confidence_level'];
            $analysis['problem_type_emotion'] = $mathEmotion['problem_type_emotion'] ?? null;
        }

        return $analysis;
    }

    /**
     * 페르소나 식별 (학습 감정 기반)
     *
     * @param array $analysis 분석 결과
     * @param array $context 컨텍스트
     * @return array 식별된 페르소나
     */
    protected function identifyPersona(array $analysis, array $context): array {
        // 활동 타입 확인
        $activityType = $analysis['detected_activity'] ?? 'unknown';

        // 규칙 기반 식별 시도
        $ruleBasedPersona = parent::identifyPersona($analysis, $context);

        // 규칙 매칭 성공 시 반환
        if ($ruleBasedPersona['persona_id'] !== $this->getDefaultPersonaId()) {
            return $this->enrichPersonaWithEmotionData($ruleBasedPersona, $analysis);
        }

        // 감정 기반 페르소나 식별
        $emotionBasedPersona = $this->identifyByEmotion($analysis, $context, $activityType);

        return $this->enrichPersonaWithEmotionData($emotionBasedPersona, $analysis);
    }

    /**
     * 감정 기반 페르소나 식별
     *
     * @param array $analysis 분석 결과
     * @param array $context 컨텍스트
     * @param string $activityType 활동 타입
     * @return array 식별된 페르소나
     */
    protected function identifyByEmotion(array $analysis, array $context, string $activityType): array {
        $emotion = $analysis['learning_emotion'] ?? 'neutral';
        $intensity = $analysis['emotion_intensity'] ?? 0.5;

        // 활동별 페르소나 매핑 확인
        $availablePersonas = $this->activityPersonaMap[$activityType] ?? [];

        if (empty($availablePersonas)) {
            return $this->getDefaultPersona();
        }

        // 감정-페르소나 매칭 규칙
        $personaIndex = 0;

        switch ($emotion) {
            case 'confident':
            case 'curious':
            case 'engaged':
                $personaIndex = 0; // 긍정적 감정 → 첫 번째 페르소나 (적극형)
                break;

            case 'anxious':
            case 'worried':
                $personaIndex = 1; // 불안 감정 → 두 번째 페르소나 (보조형)
                break;

            case 'frustrated':
            case 'overwhelmed':
                $personaIndex = 2; // 좌절 감정 → 세 번째 페르소나 (완벽/방어형)
                break;

            case 'disengaged':
            case 'avoidant':
            case 'resistant':
                $personaIndex = 3; // 회피 감정 → 네 번째 페르소나 (회피형)
                break;

            default:
                $personaIndex = 0;
        }

        // 인덱스 범위 확인
        $personaIndex = min($personaIndex, count($availablePersonas) - 1);
        $personaType = $availablePersonas[$personaIndex];

        // 활동-타입 조합 ID 생성
        $activityPrefix = $this->getActivityPrefix($activityType);
        $personaId = $activityPrefix . '_' . $personaType;

        // 정의된 페르소나 정보 가져오기
        $personas = $this->getPersonaDefinitions();

        if (isset($personas[$personaId])) {
            return [
                'persona_id' => $personaId,
                'persona_name' => $personas[$personaId]['name'],
                'description' => $personas[$personaId]['description'],
                'tone' => $personas[$personaId]['tone'],
                'intervention' => $personas[$personaId]['intervention'],
                'matched_rule' => 'emotion_based_' . $emotion,
                'confidence' => $intensity
            ];
        }

        return $this->getDefaultPersona();
    }

    /**
     * 활동 타입 접두사 반환
     *
     * @param string $activityType 활동 타입
     * @return string 접두사
     */
    protected function getActivityPrefix(string $activityType): string {
        $prefixes = [
            'concept_understanding' => 'CU',
            'type_learning' => 'TL',
            'problem_solving' => 'PS',
            'error_note' => 'EN',
            'qa' => 'QA',
            'review' => 'RV',
            'pomodoro' => 'PM',
            'home_check' => 'HC'
        ];

        return $prefixes[$activityType] ?? 'XX';
    }

    /**
     * 페르소나에 감정 데이터 추가
     *
     * @param array $persona 페르소나
     * @param array $analysis 분석 결과
     * @return array 보강된 페르소나
     */
    protected function enrichPersonaWithEmotionData(array $persona, array $analysis): array {
        $persona['learning_emotion'] = $analysis['learning_emotion'] ?? 'neutral';
        $persona['emotion_intensity'] = $analysis['emotion_intensity'] ?? 0.5;
        $persona['activity_type'] = $analysis['detected_activity'] ?? 'unknown';
        $persona['emotion_triggers'] = $analysis['emotion_triggers'] ?? [];

        // 수학 특화 데이터 추가
        if (isset($analysis['math_anxiety'])) {
            $persona['math_anxiety'] = $analysis['math_anxiety'];
            $persona['math_confidence'] = $analysis['math_confidence'] ?? 0.5;
        }

        return $persona;
    }

    /**
     * 에이전트 간 감정 상태 공유
     *
     * @param int $userId 사용자 ID
     * @param array $emotionData 감정 데이터
     * @return bool 성공 여부
     */
    public function shareEmotionState(int $userId, array $emotionData): bool {
        global $DB;

        try {
            $record = new \stdClass();
            $record->userid = $userId;
            $record->source_agent = 'agent05';
            $record->emotion_type = $emotionData['type'] ?? 'unknown';
            $record->emotion_intensity = $emotionData['intensity'] ?? 0.5;
            $record->activity_type = $emotionData['activity'] ?? 'unknown';
            $record->context_data = json_encode($emotionData);
            $record->timecreated = time();

            // at_agent_emotion_share 테이블에 저장
            $DB->insert_record('at_agent_emotion_share', $record);

            return true;

        } catch (\Exception $e) {
            $this->logError("감정 상태 공유 실패: " . $e->getMessage(), __LINE__);
            return false;
        }
    }

    /**
     * 다른 에이전트로부터 감정 상태 수신
     *
     * @param int $userId 사용자 ID
     * @param string $sourceAgent 소스 에이전트 ID
     * @return array 감정 상태 데이터
     */
    public function receiveEmotionState(int $userId, string $sourceAgent = ''): array {
        global $DB;

        try {
            $sql = "SELECT * FROM {at_agent_emotion_share}
                    WHERE userid = ?
                    AND source_agent != 'agent05'";
            $params = [$userId];

            if (!empty($sourceAgent)) {
                $sql .= " AND source_agent = ?";
                $params[] = $sourceAgent;
            }

            $sql .= " ORDER BY timecreated DESC LIMIT 10";

            $records = $DB->get_records_sql($sql, $params);

            return array_values(array_map(function($record) {
                $data = (array) $record;
                $data['context_data'] = json_decode($record->context_data, true);
                return $data;
            }, $records));

        } catch (\Exception $e) {
            $this->logError("감정 상태 수신 실패: " . $e->getMessage(), __LINE__);
            return [];
        }
    }

    /**
     * 학습 감정 트렌드 분석
     *
     * @param int $userId 사용자 ID
     * @param int $days 분석 기간 (일)
     * @return array 트렌드 분석 결과
     */
    public function analyzeEmotionTrend(int $userId, int $days = 7): array {
        return $this->emotionAnalyzer->analyzeTrend($userId, $days);
    }

    /**
     * 디버그 정보 반환 (확장)
     *
     * @return array 디버그 정보
     */
    public function getDebugInfo(): array {
        $baseDebug = parent::getDebugInfo();

        return array_merge($baseDebug, [
            'emotion_analyzer' => isset($this->emotionAnalyzer) ? 'initialized' : 'not_initialized',
            'activity_detector' => isset($this->activityDetector) ? 'initialized' : 'not_initialized',
            'activity_types' => $this->activityTypes,
            'persona_count' => count($this->getPersonaDefinitions()),
            'version' => '1.0',
            'specialization' => 'learning_emotion_analysis'
        ]);
    }
}

/*
 * 관련 DB 테이블:
 *
 * at_agent_persona_state:
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - agent_id: varchar(50) NOT NULL
 *   - persona_id: varchar(50) NOT NULL
 *   - state_data: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 *   - timemodified: bigint(10) NOT NULL
 *
 * at_agent_emotion_share (신규):
 *   - id: bigint(10) PRIMARY KEY AUTO_INCREMENT
 *   - userid: bigint(10) NOT NULL
 *   - source_agent: varchar(50) NOT NULL
 *   - emotion_type: varchar(50) NOT NULL
 *   - emotion_intensity: decimal(3,2) NOT NULL
 *   - activity_type: varchar(50)
 *   - context_data: longtext (JSON)
 *   - timecreated: bigint(10) NOT NULL
 *
 * 참조 파일:
 * - ontology_engineering/persona_engine/core/AbstractPersonaEngine.php
 * - agents/agent05_learning_emotion/rules/rules.yaml
 * - agents/agent05_learning_emotion/rules/mission.md
 */
