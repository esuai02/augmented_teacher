<?php
/**
 * test_chat.php
 *
 * Agent07 상호작용 타겟팅 페르소나 시스템 통합 테스트
 * - Tab 1: 채팅 테스트 (API 연동)
 * - Tab 2: 진단 테스트 (BasePersonaTest 상속)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent07InteractionTargeting
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test_chat.php
 */

// =========================================================================
// 에이전트 설정
// =========================================================================
$agentNumber = 7;
$agentName = 'interaction_targeting';
$agentKrName = '상호작용 타겟팅';
$agentEmoji = '🎯';
$agentDescription = '상황과 페르소나를 식별하여 최적의 상호작용 방식을 결정하는 에이전트';
$apiEndpoint = './api/';
$headerGradient = 'linear-gradient(135deg, #2c3e50, #3498db)';

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
    ['num' => 14, 'id' => 'current_position', 'name' => '현재위치', 'emoji' => '📍'],
    ['num' => 15, 'id' => 'problem_redefinition', 'name' => '문제재정의', 'emoji' => '🔄'],
    ['num' => 16, 'id' => 'interaction_preparation', 'name' => '상호작용준비', 'emoji' => '🤝'],
    ['num' => 17, 'id' => 'remaining_activities', 'name' => '잔여활동', 'emoji' => '⏰'],
    ['num' => 18, 'id' => 'signature_routine', 'name' => '시그니처루틴', 'emoji' => '✨'],
    ['num' => 19, 'id' => 'interaction_content', 'name' => '상호작용컨텐츠', 'emoji' => '💬'],
    ['num' => 20, 'id' => 'intervention_preparation', 'name' => '개입준비', 'emoji' => '🚀'],
    ['num' => 21, 'id' => 'intervention_execution', 'name' => '개입실행', 'emoji' => '⚡'],
];

// =========================================================================
// 컨텍스트 코드 정의
// =========================================================================
$contextCodes = [
    'S1' => ['label' => '학습 상황', 'color' => '#3498db', 'desc' => '일반적인 학습 활동 중'],
    'S2' => ['label' => '문제 풀이', 'color' => '#e67e22', 'desc' => '문제 해결 과정 중'],
    'S3' => ['label' => '도움 요청', 'color' => '#9b59b6', 'desc' => '학생이 도움을 요청한 상황'],
    'S4' => ['label' => '평가 상황', 'color' => '#e74c3c', 'desc' => '테스트/퀴즈 진행 중'],
    'A_ACTIVE' => ['label' => '활동적', 'color' => '#27ae60', 'desc' => '활발한 학습 활동'],
    'A_PASSIVE' => ['label' => '수동적', 'color' => '#95a5a6', 'desc' => '소극적 학습 활동'],
    'T_MORNING' => ['label' => '오전', 'color' => '#f39c12', 'desc' => '오전 시간대'],
    'T_EVENING' => ['label' => '저녁', 'color' => '#8e44ad', 'desc' => '저녁 시간대'],
    'B_FOCUSED' => ['label' => '집중', 'color' => '#2ecc71', 'desc' => '집중 상태'],
    'B_DISTRACTED' => ['label' => '산만', 'color' => '#e74c3c', 'desc' => '산만한 상태'],
    'E_POSITIVE' => ['label' => '긍정적', 'color' => '#1abc9c', 'desc' => '긍정적 감정 상태'],
    'E_NEGATIVE' => ['label' => '부정적', 'color' => '#c0392b', 'desc' => '부정적 감정 상태']
];

// =========================================================================
// 빠른 메시지 정의
// =========================================================================
$quickMessages = [
    ['text' => '지금 수학 공부하고 있어요', 'context' => 'S1'],
    ['text' => '이 문제 어떻게 풀어요?', 'context' => 'S2'],
    ['text' => '힌트 좀 주세요', 'context' => 'S3'],
    ['text' => '시험 준비 중이에요', 'context' => 'S4'],
    ['text' => '문제 열심히 풀고 있어요', 'context' => 'A_ACTIVE'],
    ['text' => '모르겠어서 멍하니 있어요', 'context' => 'A_PASSIVE'],
    ['text' => '집중이 잘 돼요', 'context' => 'B_FOCUSED'],
    ['text' => '자꾸 딴생각이 나요', 'context' => 'B_DISTRACTED']
];

// =========================================================================
// 에러 리포팅
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =========================================================================
// MOODLE_INTERNAL 정의
// =========================================================================
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// =========================================================================
// BasePersonaTest 로드 (use 문은 파일 최상위에 있어야 함)
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

// =========================================================================
// 진단 테스트 클래스 정의 (클래스는 항상 로드, 실행은 조건부)
// =========================================================================
class Agent07PersonaTest extends BasePersonaTest
{
    public function __construct()
    {
        parent::__construct(
            7,
            'interaction_targeting',
            '상호작용 타겟팅',
            __DIR__
        );
    }

    protected function getRequiredFiles(): array
    {
        return [
            'engine/PersonaRuleEngine.php' => 'PersonaRuleEngine 메인 클래스',
            'engine/RuleParser.php' => '규칙 파서 클래스',
            'engine/ConditionEvaluator.php' => '조건 평가기 클래스',
            'engine/DataContext.php' => '데이터 컨텍스트 클래스',
            'engine/ResponseGenerator.php' => '응답 생성기 클래스',
            'personas.md' => '페르소나 정의 문서',
            'rules.yaml' => '규칙 정의 파일',
            'contextlist.md' => '컨텍스트 목록 문서'
        ];
    }

    protected function getRequiredTables(): array
    {
        return [
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블',
            'mdl_at_agent_messages' => '에이전트 간 메시지 테이블',
            'mdl_agent07_persona_log' => 'Agent07 페르소나 식별 로그',
            'mdl_agent07_context_log' => 'Agent07 컨텍스트 로그'
        ];
    }

    protected function runCustomTests(): void
    {
        $this->testSituationIdentification();
        $this->testPersonaIdentification();
        $this->testTwoStepProcess();
        $this->testComponentsLoad();
        $this->testApiEndpoint('api/', 'GET');
    }

    private function testSituationIdentification(): void
    {
        $expectedSituations = ['S1', 'S2', 'S3', 'S4'];

        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) {
                $this->recordTest('상황 식별 규칙', false, '엔진 파일 없음');
                return;
            }

            $content = file_get_contents($filePath);
            $hasSituationRules = strpos($content, 'situationRules') !== false ||
                                 strpos($content, 'identifySituation') !== false;

            $this->recordTest(
                '상황 식별 규칙',
                $hasSituationRules,
                $hasSituationRules ? '상황 식별 로직 발견' : '상황 식별 로직 없음'
            );
        } catch (Throwable $e) {
            $this->recordTest('상황 식별 규칙', false, $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']');
        }
    }

    private function testPersonaIdentification(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) {
                $this->recordTest('페르소나 식별 규칙', false, '엔진 파일 없음');
                return;
            }

            $content = file_get_contents($filePath);
            $hasPersonaRules = strpos($content, 'personaRules') !== false ||
                               strpos($content, 'identifyPersona') !== false;

            $this->recordTest(
                '페르소나 식별 규칙',
                $hasPersonaRules,
                $hasPersonaRules ? '페르소나 식별 로직 발견' : '페르소나 식별 로직 없음'
            );
        } catch (Throwable $e) {
            $this->recordTest('페르소나 식별 규칙', false, $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']');
        }
    }

    private function testTwoStepProcess(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) {
                $this->recordTest('2단계 식별 프로세스', false, '엔진 파일 없음');
                return;
            }

            $content = file_get_contents($filePath);
            $step1 = strpos($content, 'identifySituation') !== false;
            $step2 = strpos($content, 'identifyPersona') !== false;

            $this->recordTest(
                '2단계 식별 프로세스',
                $step1 && $step2,
                ($step1 && $step2) ? 'Situation → Persona 프로세스 확인' : '프로세스 불완전'
            );
        } catch (Throwable $e) {
            $this->recordTest('2단계 식별 프로세스', false, $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']');
        }
    }

    private function testComponentsLoad(): void
    {
        $components = [
            'RuleParser.php' => 'RuleParser',
            'ConditionEvaluator.php' => 'ConditionEvaluator',
            'DataContext.php' => 'DataContext',
            'ResponseGenerator.php' => 'ResponseGenerator'
        ];

        $foundCount = 0;
        foreach ($components as $file => $className) {
            $filePath = __DIR__ . '/engine/' . $file;
            if (file_exists($filePath)) {
                $foundCount++;
            }
        }

        $this->recordTest(
            '엔진 컴포넌트 존재',
            $foundCount > 0,
            "{$foundCount}/" . count($components) . " 컴포넌트 발견"
        );
    }
}

// =========================================================================
// 탭 상태 확인
// =========================================================================
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';
$format = isset($_GET['format']) ? $_GET['format'] : 'html';

// =========================================================================
// JSON 형식 진단 출력
// =========================================================================
if ($currentTab === 'diagnosis' && $format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $test = new Agent07PersonaTest();
        $test->runAllTests();
        echo $test->toJson();
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $agentEmoji; ?> Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?> - 통합 테스트</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1a2e;
            color: #eee;
            min-height: 100vh;
        }

        .header {
            background: <?php echo $headerGradient; ?>;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .tabs {
            display: flex;
            background: #16213e;
            border-bottom: 2px solid #0f3460;
        }

        .tab {
            flex: 1;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            background: transparent;
            color: #888;
            font-size: 1rem;
            text-decoration: none;
        }

        .tab:hover {
            background: #1a1a40;
            color: #fff;
        }

        .tab.active {
            background: #1a1a2e;
            color: #3498db;
            border-bottom: 3px solid #3498db;
            font-weight: bold;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 채팅 탭 스타일 */
        .chat-container {
            display: <?php echo $currentTab === 'chat' ? 'block' : 'none'; ?>;
        }

        .context-selector {
            background: #16213e;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .context-selector h3 {
            margin-bottom: 10px;
            color: #3498db;
        }

        .context-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .context-btn {
            padding: 8px 15px;
            border: 2px solid transparent;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.85rem;
            transition: all 0.3s;
            color: #fff;
        }

        .context-btn:hover {
            transform: scale(1.05);
        }

        .context-btn.selected {
            border-color: #fff;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.3);
        }

        .quick-messages {
            background: #16213e;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .quick-messages h3 {
            margin-bottom: 10px;
            color: #3498db;
        }

        .quick-btn {
            display: inline-block;
            padding: 8px 12px;
            margin: 4px;
            background: #0f3460;
            border: 1px solid #3498db;
            border-radius: 15px;
            color: #eee;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .quick-btn:hover {
            background: #3498db;
            color: #000;
        }

        .chat-box {
            background: #16213e;
            border-radius: 10px;
            height: 400px;
            overflow-y: auto;
            padding: 15px;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 15px;
            padding: 12px 15px;
            border-radius: 15px;
            max-width: 80%;
        }

        .message.user {
            background: #3498db;
            color: #fff;
            margin-left: auto;
            border-bottom-right-radius: 5px;
        }

        .message.assistant {
            background: #0f3460;
            border-bottom-left-radius: 5px;
        }

        .message .meta {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 5px;
        }

        .input-area {
            display: flex;
            gap: 10px;
        }

        .input-area input {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 25px;
            background: #16213e;
            color: #fff;
            font-size: 1rem;
        }

        .input-area input:focus {
            outline: 2px solid #3498db;
        }

        .input-area button {
            padding: 15px 30px;
            border: none;
            border-radius: 25px;
            background: #3498db;
            color: #fff;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .input-area button:hover {
            background: #2980b9;
            transform: scale(1.05);
        }

        .input-area button:disabled {
            background: #555;
            cursor: not-allowed;
            transform: none;
        }

        /* 진단 탭 스타일 */
        .diagnosis-container {
            display: <?php echo $currentTab === 'diagnosis' ? 'block' : 'none'; ?>;
        }

        .diagnosis-frame {
            background: #16213e;
            border-radius: 10px;
            padding: 20px;
            min-height: 500px;
        }

        .loading {
            text-align: center;
            padding: 50px;
            color: #888;
        }

        .loading::after {
            content: '';
            display: block;
            width: 40px;
            height: 40px;
            margin: 20px auto;
            border: 4px solid #3498db;
            border-top-color: transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .api-info {
            background: #0f3460;
            border-radius: 8px;
            padding: 10px 15px;
            margin-top: 20px;
            font-size: 0.85rem;
        }

        .api-info code {
            background: #1a1a2e;
            padding: 2px 8px;
            border-radius: 4px;
            color: #3498db;
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
    <div class="header">
        <h1><?php echo $agentEmoji; ?> Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?></h1>
        <p><?php echo $agentDescription; ?></p>
    </div>

    <div class="tabs">
        <a href="?tab=chat" class="tab <?php echo $currentTab === 'chat' ? 'active' : ''; ?>">
            💬 채팅 테스트
        </a>
        <a href="?tab=diagnosis" class="tab <?php echo $currentTab === 'diagnosis' ? 'active' : ''; ?>">
            🔍 진단 테스트
        </a>
    </div>

    <div class="container">
        <!-- 헤더 네비게이션 -->
        <div class="header-nav">
            <h1><?php echo $agentEmoji; ?> Agent<?php echo str_pad($agentNumber, 2, '0', STR_PAD_LEFT); ?> <?php echo $agentKrName; ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji"><?php echo $agentEmoji; ?></span>
                    <span>에이전트 전환</span>
                </button>
                <div class="agent-dropdown-content">
                    <?php foreach ($allAgents as $agent):
                        $agentNumPadded = str_pad($agent['num'], 2, '0', STR_PAD_LEFT);
                        $agentUrl = "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent{$agentNumPadded}_{$agent['id']}/persona_system/test_chat.php";
                        $isActive = ($agent['num'] === $agentNumber);
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

        <!-- 채팅 탭 -->
        <div class="chat-container">
            <div class="context-selector">
                <h3>📍 컨텍스트 선택</h3>
                <div class="context-buttons">
                    <?php foreach ($contextCodes as $code => $info): ?>
                    <button class="context-btn"
                            data-context="<?php echo $code; ?>"
                            style="background: <?php echo $info['color']; ?>;"
                            title="<?php echo $info['desc']; ?>">
                        <?php echo $code; ?> <?php echo $info['label']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="quick-messages">
                <h3>⚡ 빠른 메시지</h3>
                <?php foreach ($quickMessages as $msg): ?>
                <button class="quick-btn" data-message="<?php echo htmlspecialchars($msg['text']); ?>" data-context="<?php echo $msg['context']; ?>">
                    <?php echo $msg['text']; ?>
                </button>
                <?php endforeach; ?>
            </div>

            <div class="chat-box" id="chatBox">
                <div class="message assistant">
                    안녕하세요! 저는 Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?> 에이전트입니다.
                    <?php echo $agentDescription; ?>
                    <div class="meta">시스템 메시지</div>
                </div>
            </div>

            <div class="input-area">
                <input type="text" id="messageInput" placeholder="메시지를 입력하세요..." />
                <button id="sendBtn" onclick="sendMessage()">전송</button>
            </div>

            <div class="api-info">
                <strong>API Endpoint:</strong> <code><?php echo $apiEndpoint; ?></code> |
                <strong>Selected Context:</strong> <code id="selectedContextDisplay">S1</code>
            </div>
        </div>

        <!-- 진단 탭 -->
        <div class="diagnosis-container">
            <div class="diagnosis-frame" id="diagnosisFrame">
                <?php if ($currentTab === 'diagnosis'): ?>
                <div class="loading">진단 테스트 실행 중...</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // 현재 선택된 컨텍스트
        let selectedContext = 'S1';

        // 컨텍스트 버튼 클릭
        document.querySelectorAll('.context-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.context-btn').forEach(b => b.classList.remove('selected'));
                this.classList.add('selected');
                selectedContext = this.dataset.context;
                document.getElementById('selectedContextDisplay').textContent = selectedContext;
            });
        });

        // 빠른 메시지 클릭
        document.querySelectorAll('.quick-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const message = this.dataset.message;
                const context = this.dataset.context;

                // 컨텍스트 자동 선택
                document.querySelectorAll('.context-btn').forEach(b => {
                    if (b.dataset.context === context) {
                        b.classList.add('selected');
                        selectedContext = context;
                        document.getElementById('selectedContextDisplay').textContent = selectedContext;
                    } else {
                        b.classList.remove('selected');
                    }
                });

                document.getElementById('messageInput').value = message;
                sendMessage();
            });
        });

        // 엔터 키 전송
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });

        // 메시지 전송
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            if (!message) return;

            const chatBox = document.getElementById('chatBox');
            const sendBtn = document.getElementById('sendBtn');

            // 사용자 메시지 표시
            chatBox.innerHTML += `
                <div class="message user">
                    ${escapeHtml(message)}
                    <div class="meta">Context: ${selectedContext}</div>
                </div>
            `;

            input.value = '';
            sendBtn.disabled = true;
            chatBox.scrollTop = chatBox.scrollHeight;

            try {
                const response = await fetch('<?php echo $apiEndpoint; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        context: selectedContext,
                        agent: <?php echo $agentNumber; ?>
                    })
                });

                const data = await response.json();

                chatBox.innerHTML += `
                    <div class="message assistant">
                        ${escapeHtml(data.response || data.message || JSON.stringify(data))}
                        <div class="meta">Agent<?php echo sprintf('%02d', $agentNumber); ?> | ${new Date().toLocaleTimeString()}</div>
                    </div>
                `;
            } catch (error) {
                chatBox.innerHTML += `
                    <div class="message assistant" style="background: #c0392b;">
                        오류 발생: ${escapeHtml(error.message)}
                        <div class="meta">Error | ${new Date().toLocaleTimeString()}</div>
                    </div>
                `;
            }

            sendBtn.disabled = false;
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 진단 탭 로드
        <?php if ($currentTab === 'diagnosis'): ?>
        document.addEventListener('DOMContentLoaded', function() {
            <?php
            ob_start();
            try {
                $test = new Agent07PersonaTest();
                $test->runAllTests();
                $test->renderHtml();
                $diagnosisHtml = ob_get_clean();
                $diagnosisHtml = json_encode($diagnosisHtml);
            } catch (Throwable $e) {
                ob_end_clean();
                $diagnosisHtml = json_encode('<div style="color: #e74c3c; padding: 20px;">
                    <h3>❌ 진단 실행 실패</h3>
                    <p>' . htmlspecialchars($e->getMessage()) . '</p>
                    <p>File: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</p>
                </div>');
            }
            ?>
            document.getElementById('diagnosisFrame').innerHTML = <?php echo $diagnosisHtml; ?>;
        });
        <?php endif; ?>
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test_chat.php
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test_chat.php?tab=chat
 *
 * 진단 테스트 (HTML):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test_chat.php?tab=diagnosis
 *
 * 진단 테스트 (JSON):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test_chat.php?tab=diagnosis&format=json
 *
 * =========================================================================
 * 관련 DB 테이블
 * =========================================================================
 *
 * mdl_at_agent_persona_state - 페르소나 상태 저장
 *   - id (INT, PK)
 *   - agent_id (INT) - 에이전트 번호
 *   - student_id (INT) - 학생 ID
 *   - persona_code (VARCHAR) - 페르소나 코드
 *   - context_code (VARCHAR) - 컨텍스트 코드
 *   - confidence (FLOAT) - 신뢰도
 *   - created_at (DATETIME)
 *
 * mdl_at_agent_messages - 에이전트 메시지 로그
 *   - id (INT, PK)
 *   - agent_id (INT)
 *   - message_type (VARCHAR) - 'input', 'output'
 *   - content (TEXT)
 *   - context_code (VARCHAR)
 *   - created_at (DATETIME)
 *
 * mdl_agent07_persona_log - Agent07 페르소나 식별 로그
 *   - id (INT, PK)
 *   - student_id (INT)
 *   - situation_code (VARCHAR) - S1, S2, S3, S4
 *   - persona_code (VARCHAR)
 *   - confidence (FLOAT)
 *   - created_at (DATETIME)
 *
 * mdl_agent07_context_log - Agent07 컨텍스트 로그
 *   - id (INT, PK)
 *   - student_id (INT)
 *   - activity_type (VARCHAR) - ACTIVE, PASSIVE
 *   - temporal_context (VARCHAR) - MORNING, EVENING
 *   - behavioral_state (VARCHAR) - FOCUSED, DISTRACTED
 *   - emotional_state (VARCHAR) - POSITIVE, NEGATIVE
 *   - created_at (DATETIME)
 *
 * =========================================================================
 */
?>
