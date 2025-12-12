<?php
/**
 * test_chat.php
 *
 * Agent14 커리큘럼 혁신 페르소나 시스템 통합 테스트
 * - Tab 1: 채팅 테스트 (API 연동)
 * - Tab 2: 진단 테스트 (BasePersonaTest 상속)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent14CurriculumInnovation
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test_chat.php
 */

// =========================================================================
// Agent14 설정
// =========================================================================

// 전체 에이전트 목록 (드롭다운 메뉴용)
$allAgents = [
    ['num' => 1, 'id' => 'onboarding', 'name' => '온보딩', 'emoji' => '🎯'],
    ['num' => 2, 'id' => 'exam_schedule', 'name' => '시험일정', 'emoji' => '📅'],
    ['num' => 3, 'id' => 'goals_analysis', 'name' => '목표분석', 'emoji' => '🎯'],
    ['num' => 4, 'id' => 'inspect_weakpoints', 'name' => '취약점분석', 'emoji' => '🔍'],
    ['num' => 5, 'id' => 'learning_emotion', 'name' => '학습감정', 'emoji' => '💭'],
    ['num' => 6, 'id' => 'teacher_feedback', 'name' => '선생님피드백', 'emoji' => '👨‍🏫'],
    ['num' => 7, 'id' => 'interaction_targeting', 'name' => '상호작용타겟팅', 'emoji' => '🎯'],
    ['num' => 8, 'id' => 'calmness', 'name' => '마음챙김', 'emoji' => '🧘'],
    ['num' => 9, 'id' => 'learning_management', 'name' => '학습관리', 'emoji' => '📚'],
    ['num' => 10, 'id' => 'concept_notes', 'name' => '개념노트', 'emoji' => '📝'],
    ['num' => 11, 'id' => 'problem_notes', 'name' => '문제노트', 'emoji' => '✏️'],
    ['num' => 12, 'id' => 'rest_routine', 'name' => '휴식루틴', 'emoji' => '😴'],
    ['num' => 13, 'id' => 'learning_dropout', 'name' => '학습이탈', 'emoji' => '⚠️'],
    ['num' => 14, 'id' => 'curriculum_innovation', 'name' => '커리큘럼혁신', 'emoji' => '📍'],
    ['num' => 15, 'id' => 'problem_redefinition', 'name' => '문제재정의', 'emoji' => '🔄'],
    ['num' => 16, 'id' => 'interaction_preparation', 'name' => '상호작용준비', 'emoji' => '🤝'],
    ['num' => 17, 'id' => 'remaining_activities', 'name' => '잔여활동', 'emoji' => '⏰'],
    ['num' => 18, 'id' => 'signature_routine', 'name' => '시그니처루틴', 'emoji' => '✨'],
    ['num' => 19, 'id' => 'interaction_content', 'name' => '상호작용컨텐츠', 'emoji' => '💬'],
    ['num' => 20, 'id' => 'intervention_preparation', 'name' => '개입준비', 'emoji' => '🚀'],
    ['num' => 21, 'id' => 'intervention_execution', 'name' => '개입실행', 'emoji' => '⚡'],
];

$agentConfig = [
    'number' => 14,
    'id' => 'curriculum_innovation',
    'name' => '커리큘럼 혁신',
    'description' => 'AI 기반 커리큘럼 분석 및 혁신 전략 제안',
    'color' => [
        'primary' => '#9b59b6',      // 보라색
        'secondary' => '#8e44ad',
        'light' => '#d2b4de',
        'dark' => '#6c3483',
        'gradient_start' => '#9b59b6',
        'gradient_end' => '#8e44ad'
    ],
    'api_endpoint' => 'api/',

    // C1-C5 상황 코드 (Curriculum Phases)
    'situation_codes' => [
        'C1' => [
            'name' => 'Curriculum Analysis',
            'name_ko' => '커리큘럼 분석',
            'description' => '현재 커리큘럼 구조 및 학습 경로 분석',
            'icon' => '🔍'
        ],
        'C2' => [
            'name' => 'Content Design',
            'name_ko' => '콘텐츠 설계',
            'description' => '학습 콘텐츠 및 자료 설계',
            'icon' => '📝'
        ],
        'C3' => [
            'name' => 'Pedagogy Innovation',
            'name_ko' => '교수법 혁신',
            'description' => '혁신적 교수 방법론 적용',
            'icon' => '💡'
        ],
        'C4' => [
            'name' => 'Assessment Design',
            'name_ko' => '평가 설계',
            'description' => '학습 평가 체계 설계 및 개선',
            'icon' => '📊'
        ],
        'C5' => [
            'name' => 'Application & Feedback',
            'name_ko' => '적용 및 피드백',
            'description' => '혁신 적용 및 피드백 수집',
            'icon' => '🔄'
        ]
    ],

    // 혁신 전략 유형
    'innovation_strategies' => [
        'personalization' => '개인화 학습 경로',
        'gamification' => '게이미피케이션 적용',
        'adaptive' => '적응형 학습 시스템',
        'competency' => '역량 기반 교육과정',
        'microlearning' => '마이크로러닝 모듈화',
        'project_based' => '프로젝트 기반 학습'
    ],

    // 컴포넌트 목록
    'components' => [
        'Agent14PersonaEngine' => '메인 엔진',
        'Agent14RuleParser' => '규칙 파서',
        'Agent14ConditionEvaluator' => '조건 평가기',
        'Agent14ActionExecutor' => '액션 실행기',
        'Agent14DataContext' => '데이터 컨텍스트',
        'Agent14ResponseGenerator' => '응답 생성기'
    ]
];

// 현재 탭 확인
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// =========================================================================
// 채팅 테스트 탭 처리
// =========================================================================
if ($currentTab === 'chat') {
    // AJAX 요청 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');

        $action = $_POST['action'];

        if ($action === 'send_message') {
            $message = isset($_POST['message']) ? trim($_POST['message']) : '';
            $userId = isset($_POST['user_id']) ? intval($_POST['user_id']) : 1;
            $situationCode = isset($_POST['situation_code']) ? $_POST['situation_code'] : 'C1';

            if (empty($message)) {
                echo json_encode([
                    'success' => false,
                    'error' => '메시지를 입력해주세요.',
                    'file' => __FILE__,
                    'line' => __LINE__
                ]);
                exit;
            }

            // API 호출 시뮬레이션
            $response = simulateCurriculumResponse($message, $situationCode, $agentConfig);

            echo json_encode([
                'success' => true,
                'response' => $response,
                'situation_code' => $situationCode,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'error' => '알 수 없는 액션입니다.',
            'file' => __FILE__,
            'line' => __LINE__
        ]);
        exit;
    }
}

/**
 * 커리큘럼 혁신 응답 시뮬레이션
 */
function simulateCurriculumResponse($message, $situationCode, $config) {
    $situations = $config['situation_codes'];
    $currentSituation = isset($situations[$situationCode]) ? $situations[$situationCode] : $situations['C1'];

    $responses = [
        'C1' => [
            "현재 커리큘럼을 분석한 결과, 학습 경로 최적화가 필요합니다.",
            "커리큘럼 구조를 검토하고 있습니다. 핵심 역량과의 연계성을 확인해 볼까요?",
            "학습 목표와 평가 기준의 정렬 상태를 분석했습니다."
        ],
        'C2' => [
            "콘텐츠 설계 단계입니다. 학습자 수준에 맞는 자료를 구성하겠습니다.",
            "멀티미디어 학습 자료와 상호작용 콘텐츠를 설계합니다.",
            "마이크로러닝 모듈로 콘텐츠를 분할하여 학습 효율을 높입니다."
        ],
        'C3' => [
            "혁신적인 교수법을 제안합니다. 게이미피케이션 요소를 적용해 볼까요?",
            "프로젝트 기반 학습(PBL)으로 실제 문제 해결 능력을 기르겠습니다.",
            "플립러닝과 토론 기반 학습을 결합한 하이브리드 교수법을 추천합니다."
        ],
        'C4' => [
            "평가 체계를 재설계합니다. 역량 기반 평가로 전환해 볼까요?",
            "형성 평가와 총괄 평가의 균형을 맞추는 평가 설계를 제안합니다.",
            "자기 평가와 동료 평가를 통합한 다면적 평가 시스템입니다."
        ],
        'C5' => [
            "혁신 전략을 적용하고 피드백을 수집하고 있습니다.",
            "학습자와 교수자의 피드백을 바탕으로 개선점을 도출합니다.",
            "적용 결과를 분석하여 다음 혁신 사이클에 반영하겠습니다."
        ]
    ];

    $situationResponses = isset($responses[$situationCode]) ? $responses[$situationCode] : $responses['C1'];
    $baseResponse = $situationResponses[array_rand($situationResponses)];

    return [
        'message' => $baseResponse,
        'situation' => [
            'code' => $situationCode,
            'name' => $currentSituation['name'],
            'name_ko' => $currentSituation['name_ko'],
            'icon' => $currentSituation['icon']
        ],
        'agent' => [
            'number' => $config['number'],
            'name' => $config['name']
        ],
        'suggestions' => getSituationSuggestions($situationCode)
    ];
}

/**
 * 상황별 제안 반환
 */
function getSituationSuggestions($situationCode) {
    $suggestions = [
        'C1' => ['학습 경로 분석', '역량 매핑 확인', '병목 지점 식별'],
        'C2' => ['콘텐츠 유형 선택', '학습 자료 구성', '상호작용 설계'],
        'C3' => ['교수법 비교', '혁신 전략 적용', '파일럿 테스트'],
        'C4' => ['평가 루브릭 설계', '평가 도구 선택', '피드백 설계'],
        'C5' => ['적용 현황 확인', '피드백 수집', '개선 방안 도출']
    ];

    return isset($suggestions[$situationCode]) ? $suggestions[$situationCode] : $suggestions['C1'];
}

// =========================================================================
// 진단 테스트 탭 - BasePersonaTest 상속
// =========================================================================
if ($currentTab === 'diagnosis') {
    // 에러 리포팅
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // MOODLE_INTERNAL 정의
    if (!defined('MOODLE_INTERNAL')) {
        define('MOODLE_INTERNAL', true);
    }

    // BasePersonaTest 로드
    require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

    use ALT42\Testing\BasePersonaTest;

    /**
     * Agent14PersonaTest
     * 커리큘럼 혁신 에이전트 테스트 클래스
     */
    class Agent14ChatPersonaTest extends BasePersonaTest
    {
        private $agentConfig;

        public function __construct($config)
        {
            $this->agentConfig = $config;
            parent::__construct(
                14,
                'curriculum_innovation',
                '커리큘럼 혁신',
                __DIR__
            );
        }

        protected function getRequiredFiles(): array
        {
            return [
                'engine/Agent14PersonaEngine.php' => 'Agent14PersonaEngine 메인 클래스',
                'engine/impl/Agent14RuleParser.php' => '규칙 파서 구현',
                'engine/impl/Agent14ConditionEvaluator.php' => '조건 평가기 구현',
                'engine/impl/Agent14ActionExecutor.php' => '액션 실행기 구현',
                'engine/impl/Agent14DataContext.php' => '데이터 컨텍스트 구현',
                'engine/impl/Agent14ResponseGenerator.php' => '응답 생성기 구현',
                'personas.md' => '페르소나 정의 문서',
                'rules.yaml' => '규칙 정의 파일'
            ];
        }

        protected function getRequiredTables(): array
        {
            return [
                'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블',
                'mdl_at_agent_messages' => '에이전트 간 메시지 테이블'
            ];
        }

        protected function runCustomTests(): void
        {
            $this->testPersonaEngineLoad();
            $this->testSituationCodes();
            $this->testComponentImplementations();
            $this->testInnovationStrategies();
            $this->testAgentCommunicator();
            $this->testApiEndpoint('api/', 'GET');
            $this->testRulesYaml();
            $this->testCurriculumPhaseTransitions();
        }

        private function testPersonaEngineLoad(): void
        {
            $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';

            if (!file_exists($filePath)) {
                $this->recordTest(
                    'Agent14PersonaEngine 클래스 로드',
                    false,
                    '파일 없음: engine/Agent14PersonaEngine.php'
                );
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class Agent14PersonaEngine') !== false;
                $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

                $this->recordTest(
                    'Agent14PersonaEngine 클래스 정의',
                    $hasClass,
                    $hasClass ? 'Agent14PersonaEngine 클래스 발견' : '클래스 정의 없음'
                );

                $this->recordTest(
                    'AbstractPersonaEngine 상속',
                    $extendsAbstract,
                    $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
                );
            } catch (Throwable $e) {
                $this->recordTest(
                    'Agent14PersonaEngine 클래스 로드',
                    false,
                    '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testSituationCodes(): void
        {
            $expectedCodes = ['C1', 'C2', 'C3', 'C4', 'C5'];

            try {
                $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';

                if (!file_exists($filePath)) {
                    $this->recordTest(
                        '상황 코드 C1-C5',
                        false,
                        '엔진 파일 없음'
                    );
                    return;
                }

                $content = file_get_contents($filePath);
                $foundCodes = 0;

                foreach ($expectedCodes as $code) {
                    if (strpos($content, "'" . $code . "'") !== false ||
                        strpos($content, '"' . $code . '"') !== false) {
                        $foundCodes++;
                    }
                }

                $this->recordTest(
                    '상황 코드 C1-C5 정의',
                    $foundCodes >= 5,
                    $foundCodes >= 5
                        ? 'C1-C5 모든 상황 코드 정의됨'
                        : "{$foundCodes}/5 상황 코드 발견"
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '상황 코드 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage()
                );
            }
        }

        private function testComponentImplementations(): void
        {
            $components = [
                'impl/Agent14RuleParser.php' => 'Agent14RuleParser',
                'impl/Agent14ConditionEvaluator.php' => 'Agent14ConditionEvaluator',
                'impl/Agent14ActionExecutor.php' => 'Agent14ActionExecutor',
                'impl/Agent14DataContext.php' => 'Agent14DataContext',
                'impl/Agent14ResponseGenerator.php' => 'Agent14ResponseGenerator'
            ];

            foreach ($components as $file => $className) {
                $filePath = __DIR__ . '/engine/' . $file;

                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    $hasClass = strpos($content, 'class ' . $className) !== false;

                    $this->recordTest(
                        "{$className} 컴포넌트",
                        $hasClass,
                        $hasClass ? "{$className} 클래스 정의됨" : "{$className} 클래스 없음"
                    );
                } else {
                    $this->recordTest(
                        "{$className} 컴포넌트",
                        false,
                        "파일 없음: engine/{$file}"
                    );
                }
            }
        }

        private function testInnovationStrategies(): void
        {
            $strategies = [
                'personalization',
                'gamification',
                'adaptive',
                'competency',
                'microlearning',
                'project_based'
            ];

            try {
                $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';

                if (!file_exists($filePath)) {
                    $this->recordTest(
                        '혁신 전략 정의',
                        false,
                        '엔진 파일 없음'
                    );
                    return;
                }

                $content = file_get_contents($filePath);
                $foundStrategies = 0;

                foreach ($strategies as $strategy) {
                    if (stripos($content, $strategy) !== false) {
                        $foundStrategies++;
                    }
                }

                $this->recordTest(
                    '혁신 전략 참조',
                    $foundStrategies >= 3,
                    "{$foundStrategies}/" . count($strategies) . " 혁신 전략 참조됨"
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '혁신 전략 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage()
                );
            }
        }

        private function testAgentCommunicator(): void
        {
            try {
                $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';

                if (!file_exists($filePath)) {
                    $this->recordTest(
                        'AgentCommunicator 연동',
                        false,
                        '엔진 파일 없음'
                    );
                    return;
                }

                $content = file_get_contents($filePath);
                $hasCommunicator = strpos($content, 'AgentCommunicator') !== false ||
                                   strpos($content, 'communicator') !== false;

                $this->recordTest(
                    'AgentCommunicator 연동',
                    $hasCommunicator,
                    $hasCommunicator ? 'AgentCommunicator 참조 발견' : 'AgentCommunicator 없음'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'AgentCommunicator 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage()
                );
            }
        }

        private function testRulesYaml(): void
        {
            $filePath = __DIR__ . '/rules.yaml';

            if (!file_exists($filePath)) {
                $this->recordTest(
                    'rules.yaml 존재',
                    false,
                    '파일 없음: rules.yaml'
                );
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $fileSize = strlen($content);

                $this->recordTest(
                    'rules.yaml 존재',
                    true,
                    "rules.yaml 파일 존재 ({$fileSize} bytes)"
                );

                $hasRules = strpos($content, 'rules:') !== false ||
                           strpos($content, 'situation_rules:') !== false;

                $this->recordTest(
                    'rules.yaml 구조',
                    $hasRules,
                    $hasRules ? 'YAML 규칙 구조 확인됨' : 'YAML 규칙 구조 확인 불가'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'rules.yaml 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage()
                );
            }
        }

        private function testCurriculumPhaseTransitions(): void
        {
            try {
                $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';

                if (!file_exists($filePath)) {
                    $this->recordTest(
                        '커리큘럼 단계 전환',
                        false,
                        '엔진 파일 없음'
                    );
                    return;
                }

                $content = file_get_contents($filePath);

                // 단계 전환 로직 확인
                $hasTransition = strpos($content, 'transition') !== false ||
                                strpos($content, 'phase') !== false ||
                                strpos($content, 'next') !== false;

                $this->recordTest(
                    '커리큘럼 단계 전환 로직',
                    $hasTransition,
                    $hasTransition ? '단계 전환 로직 발견' : '단계 전환 로직 미확인'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '단계 전환 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage()
                );
            }
        }
    }
}

// =========================================================================
// HTML 출력
// =========================================================================
$colors = $agentConfig['color'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent<?= $agentConfig['number'] ?> <?= $agentConfig['name'] ?> - 통합 테스트</title>
    <style>
        :root {
            --primary-color: <?= $colors['primary'] ?>;
            --secondary-color: <?= $colors['secondary'] ?>;
            --light-color: <?= $colors['light'] ?>;
            --dark-color: <?= $colors['dark'] ?>;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            background: linear-gradient(135deg, <?= $colors['gradient_start'] ?> 0%, <?= $colors['gradient_end'] ?> 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 20px;
            box-shadow: 0 10px 40px rgba(155, 89, 182, 0.3);
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .agent-badge {
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }

        /* Tabs */
        .tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 20px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .tab {
            flex: 1;
            padding: 14px 24px;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #666;
            font-weight: 500;
        }

        .tab:hover {
            background: var(--light-color);
            color: var(--dark-color);
        }

        .tab.active {
            background: linear-gradient(135deg, <?= $colors['gradient_start'] ?> 0%, <?= $colors['gradient_end'] ?> 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.4);
        }

        /* Situation Selector */
        .situation-selector {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .situation-selector h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .situation-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }

        .situation-item {
            padding: 15px 10px;
            text-align: center;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .situation-item:hover {
            border-color: var(--primary-color);
            background: var(--light-color);
        }

        .situation-item.active {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .situation-item .icon {
            font-size: 24px;
            margin-bottom: 8px;
        }

        .situation-item .code {
            font-weight: bold;
            font-size: 16px;
        }

        .situation-item .name {
            font-size: 11px;
            margin-top: 4px;
            opacity: 0.8;
        }

        .situation-item.active .name {
            opacity: 1;
        }

        /* Current Situation Display */
        .current-situation {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .situation-icon-large {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            background: var(--light-color);
            border-radius: 12px;
        }

        .situation-info h3 {
            color: var(--dark-color);
            margin-bottom: 4px;
        }

        .situation-info p {
            color: #666;
            font-size: 14px;
        }

        /* Chat Container */
        .chat-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .chat-messages {
            height: 400px;
            overflow-y: auto;
            padding: 20px;
            background: #fafafa;
        }

        .message {
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .message.agent .message-avatar {
            background: linear-gradient(135deg, <?= $colors['gradient_start'] ?> 0%, <?= $colors['gradient_end'] ?> 100%);
            color: white;
        }

        .message.user .message-avatar {
            background: #e0e0e0;
        }

        .message-content {
            max-width: 70%;
            padding: 14px 18px;
            border-radius: 16px;
            line-height: 1.5;
        }

        .message.agent .message-content {
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 16px 16px 16px 4px;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, <?= $colors['gradient_start'] ?> 0%, <?= $colors['gradient_end'] ?> 100%);
            color: white;
            border-radius: 16px 16px 4px 16px;
        }

        .message-meta {
            font-size: 11px;
            margin-top: 6px;
            opacity: 0.7;
        }

        .message-situation {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            background: var(--light-color);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-top: 8px;
        }

        /* Suggestions */
        .suggestions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .suggestion-btn {
            padding: 6px 12px;
            background: var(--light-color);
            border: none;
            border-radius: 15px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--dark-color);
        }

        .suggestion-btn:hover {
            background: var(--primary-color);
            color: white;
        }

        /* Input Area */
        .chat-input {
            padding: 20px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 12px;
        }

        .chat-input input {
            flex: 1;
            padding: 14px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input input:focus {
            border-color: var(--primary-color);
        }

        .chat-input button {
            padding: 14px 28px;
            background: linear-gradient(135deg, <?= $colors['gradient_start'] ?> 0%, <?= $colors['gradient_end'] ?> 100%);
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .chat-input button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(155, 89, 182, 0.4);
        }

        /* Diagnosis Styles */
        .diagnosis-container {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .situation-grid {
                grid-template-columns: repeat(3, 1fr);
            }

            .header h1 {
                font-size: 22px;
            }
        }

        @media (max-width: 480px) {
            .situation-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* 에이전트 네비게이션 드롭다운 */
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header-nav h1 {
            margin-bottom: 0;
        }
        .agent-dropdown {
            position: relative;
            display: inline-block;
        }
        .agent-dropdown-btn {
            padding: 12px 20px;
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .agent-dropdown-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .agent-dropdown-btn::after {
            content: '▼';
            font-size: 10px;
            margin-left: 5px;
        }
        .agent-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            min-width: 280px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
        }
        .agent-dropdown:hover .agent-dropdown-content {
            display: block;
        }
        .agent-dropdown-content a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        .agent-dropdown-content a:last-child {
            border-bottom: none;
        }
        .agent-dropdown-content a:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .agent-dropdown-content a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .agent-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #f0f0f0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            color: #666;
        }
        .agent-dropdown-content a.active .agent-num {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .agent-emoji {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-nav">
                <h1>
                    <span class="agent-badge">Agent <?= $agentConfig['number'] ?></span>
                    <?= $agentConfig['name'] ?>
                </h1>

                <!-- 에이전트 드롭다운 -->
                <div class="agent-dropdown">
                    <button class="agent-dropdown-btn">
                        <span class="agent-emoji">🎓</span>
                        <span>에이전트 전환</span>
                    </button>
                    <div class="agent-dropdown-content">
                        <?php foreach ($allAgents as $agent):
                            $agentNumPadded = str_pad($agent['num'], 2, '0', STR_PAD_LEFT);
                            $agentUrl = "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent{$agentNumPadded}_{$agent['id']}/persona_system/test_chat.php";
                            $isActive = ($agent['num'] === $agentConfig['number']);
                        ?>
                            <a href="<?php echo $agentUrl; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <span class="agent-num"><?php echo $agentNumPadded; ?></span>
                                <span class="agent-emoji"><?php echo $agent['emoji']; ?></span>
                                <span><?php echo $agent['name']; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <p><?= $agentConfig['description'] ?></p>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=chat" class="tab <?= $currentTab === 'chat' ? 'active' : '' ?>">
                💬 채팅 테스트
            </a>
            <a href="?tab=diagnosis" class="tab <?= $currentTab === 'diagnosis' ? 'active' : '' ?>">
                🔍 진단 테스트
            </a>
        </div>

        <?php if ($currentTab === 'chat'): ?>
        <!-- Chat Test Tab -->

        <!-- Situation Selector -->
        <div class="situation-selector">
            <h3>📍 커리큘럼 단계 선택 (Curriculum Phase)</h3>
            <div class="situation-grid">
                <?php foreach ($agentConfig['situation_codes'] as $code => $info): ?>
                <div class="situation-item <?= $code === 'C1' ? 'active' : '' ?>"
                     data-code="<?= $code ?>"
                     onclick="selectSituation('<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="code"><?= $code ?></div>
                    <div class="name"><?= $info['name_ko'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Current Situation Display -->
        <div class="current-situation" id="currentSituation">
            <div class="situation-icon-large" id="situationIcon">🔍</div>
            <div class="situation-info">
                <h3 id="situationTitle">C1 - 커리큘럼 분석</h3>
                <p id="situationDesc">현재 커리큘럼 구조 및 학습 경로 분석</p>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">🎓</div>
                    <div class="message-content">
                        안녕하세요! 저는 커리큘럼 혁신 에이전트입니다.
                        위에서 커리큘럼 단계를 선택하고 질문해 주세요.
                        <div class="suggestions">
                            <button class="suggestion-btn" onclick="sendSuggestion('학습 경로 분석')">학습 경로 분석</button>
                            <button class="suggestion-btn" onclick="sendSuggestion('콘텐츠 설계')">콘텐츠 설계</button>
                            <button class="suggestion-btn" onclick="sendSuggestion('교수법 혁신')">교수법 혁신</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="메시지를 입력하세요..."
                       onkeypress="if(event.key==='Enter')sendMessage()">
                <button onclick="sendMessage()">전송</button>
            </div>
        </div>

        <?php else: ?>
        <!-- Diagnosis Test Tab -->
        <div class="diagnosis-container">
            <?php
            $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

            try {
                $test = new Agent14ChatPersonaTest($agentConfig);
                $test->runAllTests();

                if ($format === 'json') {
                    header('Content-Type: application/json; charset=utf-8');
                    echo $test->toJson();
                } else {
                    $test->renderHtml();
                }
            } catch (Throwable $e) {
                echo "<div style='color:red; padding:20px;'>";
                echo "<h3>❌ 테스트 실행 실패</h3>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
                echo "</div>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Agent Configuration
    const agentConfig = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;
    let currentSituationCode = 'C1';

    // Situation selection
    function selectSituation(code) {
        currentSituationCode = code;

        // Update visual selection
        document.querySelectorAll('.situation-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`.situation-item[data-code="${code}"]`).classList.add('active');

        // Update current situation display
        const situation = agentConfig.situation_codes[code];
        document.getElementById('situationIcon').textContent = situation.icon;
        document.getElementById('situationTitle').textContent = `${code} - ${situation.name_ko}`;
        document.getElementById('situationDesc').textContent = situation.description;
    }

    // Send message
    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();

        if (!message) return;

        // Add user message
        addMessage(message, 'user');
        input.value = '';

        // Send to API
        fetch('?tab=chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&message=${encodeURIComponent(message)}&situation_code=${currentSituationCode}&user_id=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                addAgentMessage(data.response);
            } else {
                addMessage('오류: ' + data.error, 'agent');
            }
        })
        .catch(error => {
            addMessage('통신 오류가 발생했습니다.', 'agent');
        });
    }

    // Send suggestion
    function sendSuggestion(text) {
        document.getElementById('messageInput').value = text;
        sendMessage();
    }

    // Add user message
    function addMessage(text, type) {
        const container = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${type}`;

        const avatar = type === 'agent' ? '🎓' : '👤';

        messageDiv.innerHTML = `
            <div class="message-avatar">${avatar}</div>
            <div class="message-content">${text}</div>
        `;

        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
    }

    // Add agent message with metadata
    function addAgentMessage(response) {
        const container = document.getElementById('chatMessages');
        const messageDiv = document.createElement('div');
        messageDiv.className = 'message agent';

        let suggestionsHtml = '';
        if (response.suggestions && response.suggestions.length > 0) {
            suggestionsHtml = '<div class="suggestions">';
            response.suggestions.forEach(s => {
                suggestionsHtml += `<button class="suggestion-btn" onclick="sendSuggestion('${s}')">${s}</button>`;
            });
            suggestionsHtml += '</div>';
        }

        messageDiv.innerHTML = `
            <div class="message-avatar">🎓</div>
            <div class="message-content">
                ${response.message}
                <div class="message-situation">
                    ${response.situation.icon} ${response.situation.code} - ${response.situation.name_ko}
                </div>
                ${suggestionsHtml}
            </div>
        `;

        container.appendChild(messageDiv);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
<?php
/*
 * =========================================================================
 * 사용법
 * =========================================================================
 *
 * 채팅 테스트:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test_chat.php?tab=chat
 *
 * 진단 테스트 (HTML):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test_chat.php?tab=diagnosis
 *
 * 진단 테스트 (JSON):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test_chat.php?tab=diagnosis&format=json
 *
 * =========================================================================
 */
