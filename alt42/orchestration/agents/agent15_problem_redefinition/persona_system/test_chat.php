<?php
/**
 * test_chat.php
 *
 * Agent15 문제 재정의 페르소나 시스템 통합 테스트
 * - Tab 1: 채팅 테스트 (API 연동)
 * - Tab 2: 진단 테스트 (BasePersonaTest 상속)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent15ProblemRedefinition
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/persona_system/test_chat.php
 */

// =========================================================================
// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('MOODLE_INTERNAL')) define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

// Agent15 설정

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
    'number' => 15,
    'id' => 'problem_redefinition',
    'name' => '문제 재정의',
    'description' => '학습 문제 원인을 분석하고 근본적 해결 방향 제시',
    'color' => [
        'primary' => '#34495e',
        'secondary' => '#2c3e50',
        'light' => '#85929e',
        'dark' => '#1c2833',
        'gradient_start' => '#34495e',
        'gradient_end' => '#2c3e50'
    ],
    'api_endpoint' => 'api/',

    // S1-S10 트리거 시나리오
    'trigger_scenarios' => [
        'S1' => ['name' => '학습 성과 하락', 'icon' => '📉'],
        'S2' => ['name' => '학습이탈 경고', 'icon' => '⚠️'],
        'S3' => ['name' => '동일 오답 반복', 'icon' => '🔄'],
        'S4' => ['name' => '루틴 불안정', 'icon' => '📅'],
        'S5' => ['name' => '시간관리 실패', 'icon' => '⏰'],
        'S6' => ['name' => '정서/동기 저하', 'icon' => '😔'],
        'S7' => ['name' => '개념 이해 부진', 'icon' => '🧠'],
        'S8' => ['name' => '교사 피드백 경고', 'icon' => '👨‍🏫'],
        'S9' => ['name' => '전략 불일치', 'icon' => '🎯'],
        'S10' => ['name' => '회복 실패', 'icon' => '💔']
    ],

    // 4대 원인 계층
    'cause_layers' => [
        'cognitive' => ['name' => '인지적', 'icon' => '🧠', 'color' => '#3498db'],
        'behavioral' => ['name' => '행동적', 'icon' => '🏃', 'color' => '#e74c3c'],
        'motivational' => ['name' => '동기적', 'icon' => '💪', 'color' => '#f39c12'],
        'environmental' => ['name' => '환경적', 'icon' => '🏠', 'color' => '#27ae60']
    ]
];

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// AJAX 처리
if ($currentTab === 'chat' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_POST['action'] === 'send_message') {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $scenario = isset($_POST['scenario']) ? $_POST['scenario'] : 'S1';
        $causeLayer = isset($_POST['cause_layer']) ? $_POST['cause_layer'] : 'cognitive';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.', 'file' => __FILE__, 'line' => __LINE__]);
            exit;
        }

        $scenarioInfo = $agentConfig['trigger_scenarios'][$scenario] ?? $agentConfig['trigger_scenarios']['S1'];
        $causeInfo = $agentConfig['cause_layers'][$causeLayer] ?? $agentConfig['cause_layers']['cognitive'];

        $responses = [
            'cognitive' => "인지적 관점에서 분석하면, 현재 개념 이해나 학습 전략에 문제가 있을 수 있어요.",
            'behavioral' => "행동 패턴을 보면, 학습 습관이나 실천 방법을 조정해볼 필요가 있어요.",
            'motivational' => "동기 측면에서, 학습 목표나 성취감을 다시 설정해보는 건 어떨까요?",
            'environmental' => "환경적 요인을 고려하면, 학습 환경이나 외부 요소를 점검해볼 필요가 있어요."
        ];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responses[$causeLayer],
                'scenario' => ['code' => $scenario, 'name' => $scenarioInfo['name'], 'icon' => $scenarioInfo['icon']],
                'cause_layer' => ['code' => $causeLayer, 'name' => $causeInfo['name'], 'icon' => $causeInfo['icon']],
                'agent' => ['number' => $agentConfig['number'], 'name' => $agentConfig['name']]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트
class Agent15ChatPersonaTest extends BasePersonaTest {
        private $agentConfig;

        public function __construct($config) {
            $this->agentConfig = $config;
            parent::__construct(15, 'problem_redefinition', '문제 재정의', __DIR__);
        }

        protected function getRequiredFiles(): array {
            return [
                'engine/PersonaRuleEngine.php' => 'PersonaRuleEngine 메인 클래스',
                'engine/RuleParser.php' => '규칙 파서 클래스',
                'engine/ConditionEvaluator.php' => '조건 평가기 클래스',
                'engine/ActionExecutor.php' => '액션 실행기 클래스',
                'engine/DataContext.php' => '데이터 컨텍스트 클래스',
                'engine/NLUAnalyzer.php' => 'NLU 분석기 클래스',
                'personas.md' => '페르소나 정의 문서',
                'rules.yaml' => '규칙 정의 파일'
            ];
        }

        protected function getRequiredTables(): array {
            return [
                'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블',
                'mdl_at_agent_messages' => '에이전트 간 메시지 테이블'
            ];
        }

        protected function runCustomTests(): void {
            $this->testPersonaRuleEngineLoad();
            $this->testTriggerScenarios();
            $this->testCauseLayers();
            $this->testApiEndpoint('api/', 'GET');
        }

        private function testPersonaRuleEngineLoad(): void {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) {
                $this->recordTest('PersonaRuleEngine 클래스', false, '파일 없음');
                return;
            }
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class PersonaRuleEngine') !== false;
            $this->recordTest('PersonaRuleEngine 클래스', $hasClass, $hasClass ? '클래스 발견' : '클래스 없음');
        }

        private function testTriggerScenarios(): void {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) return;
            $content = file_get_contents($filePath);
            $scenarios = ['S1', 'S2', 'S3', 'S4', 'S5', 'S6', 'S7', 'S8', 'S9', 'S10'];
            $found = 0;
            foreach ($scenarios as $s) {
                if (strpos($content, "'$s'") !== false || strpos($content, "\"$s\"") !== false) $found++;
            }
            $this->recordTest('트리거 시나리오 S1-S10', $found >= 10, "$found/10 시나리오 발견");
        }

        private function testCauseLayers(): void {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) return;
            $content = file_get_contents($filePath);
            $layers = ['cognitive', 'behavioral', 'motivational', 'environmental'];
            $found = 0;
            foreach ($layers as $l) {
                if (stripos($content, $l) !== false) $found++;
            }
            $this->recordTest('4대 원인 계층', $found >= 4, "$found/4 원인 계층 발견");
        }
    }

$colors = $agentConfig['color'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent<?= $agentConfig['number'] ?> <?= $agentConfig['name'] ?> - 통합 테스트</title>
    <style>
        :root { --primary: <?= $colors['primary'] ?>; --secondary: <?= $colors['secondary'] ?>; --light: <?= $colors['light'] ?>; --dark: <?= $colors['dark'] ?>; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(52,73,94,0.3); }
        .header h1 { font-size: 28px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .agent-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .tab { flex: 1; padding: 14px 24px; text-align: center; border-radius: 8px; cursor: pointer; transition: all 0.3s; text-decoration: none; color: #666; font-weight: 500; }
        .tab:hover { background: var(--light); color: var(--dark); }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .selector-panel { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .selector-panel h3 { font-size: 14px; color: #666; margin-bottom: 15px; }
        .scenario-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px; }
        .scenario-item { padding: 12px 8px; text-align: center; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; transition: all 0.3s; font-size: 12px; }
        .scenario-item:hover { border-color: var(--primary); background: var(--light); }
        .scenario-item.active { border-color: var(--primary); background: var(--primary); color: white; }
        .scenario-item .icon { font-size: 20px; margin-bottom: 4px; }
        .cause-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; }
        .cause-item { padding: 15px; text-align: center; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .cause-item:hover { transform: translateY(-2px); }
        .cause-item.active { border-width: 3px; }
        .cause-item .icon { font-size: 28px; margin-bottom: 8px; }
        .chat-container { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); overflow: hidden; }
        .chat-messages { height: 350px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; line-height: 1.5; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; border-radius: 16px 16px 16px 4px; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; border-radius: 16px 16px 4px 16px; }
        .message-meta { display: flex; gap: 8px; margin-top: 8px; flex-wrap: wrap; }
        .meta-tag { padding: 3px 8px; border-radius: 10px; font-size: 10px; background: var(--light); color: var(--dark); }
        .chat-input { padding: 20px; border-top: 1px solid #eee; display: flex; gap: 12px; }
        .chat-input input { flex: 1; padding: 14px 20px; border: 2px solid #e0e0e0; border-radius: 25px; font-size: 14px; outline: none; }
        .chat-input input:focus { border-color: var(--primary); }
        .chat-input button { padding: 14px 28px; background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; border: none; border-radius: 25px; cursor: pointer; font-weight: 600; }
        .diagnosis-container { background: white; border-radius: 16px; padding: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        @media (max-width: 768px) { .scenario-grid { grid-template-columns: repeat(3, 1fr); } .cause-grid { grid-template-columns: repeat(2, 1fr); } }

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
        <div class="header">
            <div class="header-nav">
                <h1><span class="agent-badge">Agent <?= $agentConfig['number'] ?></span> <?= $agentConfig['name'] ?></h1>

                <!-- 에이전트 드롭다운 -->
                <div class="agent-dropdown">
                    <button class="agent-dropdown-btn">
                        <span class="agent-emoji">🔍</span>
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

        <div class="tabs">
            <a href="?tab=chat" class="tab <?= $currentTab === 'chat' ? 'active' : '' ?>">💬 채팅 테스트</a>
            <a href="?tab=diagnosis" class="tab <?= $currentTab === 'diagnosis' ? 'active' : '' ?>">🔍 진단 테스트</a>
        </div>

        <?php if ($currentTab === 'chat'): ?>
        <div class="selector-panel">
            <h3>📍 트리거 시나리오 선택 (S1-S10)</h3>
            <div class="scenario-grid">
                <?php foreach ($agentConfig['trigger_scenarios'] as $code => $info): ?>
                <div class="scenario-item <?= $code === 'S1' ? 'active' : '' ?>" data-code="<?= $code ?>" onclick="selectScenario('<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div><?= $code ?></div>
                    <div style="font-size:10px;opacity:0.8;"><?= $info['name'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <h3>🔬 원인 계층 선택</h3>
            <div class="cause-grid">
                <?php foreach ($agentConfig['cause_layers'] as $code => $info): ?>
                <div class="cause-item <?= $code === 'cognitive' ? 'active' : '' ?>" data-code="<?= $code ?>" onclick="selectCause('<?= $code ?>')" style="border-color: <?= $info['color'] ?>;">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div style="font-weight:bold;"><?= $info['name'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">🔍</div>
                    <div class="message-content">
                        안녕하세요! 문제 재정의 에이전트입니다. 학습 문제의 근본 원인을 함께 분석해볼까요?
                        트리거 시나리오와 원인 계층을 선택한 후 질문해주세요.
                    </div>
                </div>
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="메시지를 입력하세요..." onkeypress="if(event.key==='Enter')sendMessage()">
                <button onclick="sendMessage()">전송</button>
            </div>
        </div>
        <?php else: ?>
        <div class="diagnosis-container">
            <?php
            try {
                $test = new Agent15ChatPersonaTest($agentConfig);
                $test->runAllTests();
                $test->renderHtml();
            } catch (Throwable $e) {
                echo "<div style='color:red;'><h3>❌ 오류</h3><p>" . htmlspecialchars($e->getMessage()) . "</p></div>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentScenario = 'S1', currentCause = 'cognitive';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectScenario(code) {
        currentScenario = code;
        document.querySelectorAll('.scenario-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.scenario-item[data-code="${code}"]`).classList.add('active');
    }

    function selectCause(code) {
        currentCause = code;
        document.querySelectorAll('.cause-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.cause-item[data-code="${code}"]`).classList.add('active');
    }

    function sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        if (!message) return;

        addMessage(message, 'user');
        input.value = '';

        fetch('?tab=chat', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=send_message&message=${encodeURIComponent(message)}&scenario=${currentScenario}&cause_layer=${currentCause}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const r = data.response;
                addAgentMessage(r.message, `${r.scenario.icon} ${r.scenario.code}`, `${r.cause_layer.icon} ${r.cause_layer.name}`);
            }
        });
    }

    function addMessage(text, type) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.innerHTML = `<div class="message-avatar">${type === 'agent' ? '🔍' : '👤'}</div><div class="message-content">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function addAgentMessage(text, scenario, cause) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message agent';
        div.innerHTML = `<div class="message-avatar">🔍</div><div class="message-content">${text}<div class="message-meta"><span class="meta-tag">${scenario}</span><span class="meta-tag">${cause}</span></div></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
