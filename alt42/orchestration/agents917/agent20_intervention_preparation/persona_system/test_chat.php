<?php
/**
 * test_chat.php - Agent20 개입 준비 통합 테스트
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/test_chat.php
 */

// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('MOODLE_INTERNAL')) define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

$agentConfig = [
    'number' => 20,
    'id' => 'intervention_preparation',
    'name' => '개입 준비',
    'description' => '5대 개입 전략 기반 학습 지원 준비',
    'color' => [
        'primary' => '#2196f3',
        'secondary' => '#1976d2',
        'light' => '#e3f2fd',
        'dark' => '#0d47a1',
        'gradient_start' => '#2196f3',
        'gradient_end' => '#1565c0'
    ],
    'api_endpoint' => 'api/',

    // 5대 개입 전략
    'strategies' => [
        'IS01' => ['name' => '직접 교수', 'icon' => '📚', 'desc' => '개념 직접 설명 및 전달', 'color' => '#e91e63'],
        'IS02' => ['name' => '안내된 탐구', 'icon' => '🔍', 'desc' => '질문과 힌트로 발견 유도', 'color' => '#9c27b0'],
        'IS03' => ['name' => '협력 학습', 'icon' => '👥', 'desc' => '동료 학습 및 토론 촉진', 'color' => '#673ab7'],
        'IS04' => ['name' => '피드백 강화', 'icon' => '💬', 'desc' => '즉각적 피드백 및 강화', 'color' => '#3f51b5'],
        'IS05' => ['name' => '메타인지 촉진', 'icon' => '🧠', 'desc' => '자기 학습 과정 인식 유도', 'color' => '#2196f3']
    ],

    // 개입 강도
    'intensity' => [
        'low' => ['name' => '낮음', 'icon' => '🌱', 'desc' => '최소 개입'],
        'medium' => ['name' => '보통', 'icon' => '🌿', 'desc' => '적절한 개입'],
        'high' => ['name' => '높음', 'icon' => '🌳', 'desc' => '적극적 개입']
    ]
];

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

$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// AJAX 처리
if ($currentTab === 'chat' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_POST['action'] === 'send_message') {
        $message = isset($_POST['message']) ? trim($_POST['message']) : '';
        $strategy = isset($_POST['strategy']) ? $_POST['strategy'] : 'IS01';
        $intensity = isset($_POST['intensity']) ? $_POST['intensity'] : 'medium';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.']);
            exit;
        }

        $st = $agentConfig['strategies'][$strategy] ?? $agentConfig['strategies']['IS01'];
        $int = $agentConfig['intensity'][$intensity] ?? $agentConfig['intensity']['medium'];

        $responses = [
            'IS01' => "직접 교수 전략으로 개념을 명확히 설명해드릴게요. {$int['icon']} 강도로 진행합니다.",
            'IS02' => "안내된 탐구로 스스로 발견할 수 있도록 힌트를 드릴게요. {$int['icon']} 강도입니다.",
            'IS03' => "협력 학습을 통해 함께 배워봐요. {$int['icon']} 강도로 토론을 촉진합니다.",
            'IS04' => "즉각적인 피드백으로 학습을 강화해드릴게요. {$int['icon']} 강도입니다.",
            'IS05' => "메타인지를 촉진하여 학습 과정을 인식하도록 도울게요. {$int['icon']} 강도입니다."
        ];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responses[$strategy] ?? $responses['IS01'],
                'strategy' => ['code' => $strategy, 'name' => $st['name'], 'icon' => $st['icon'], 'desc' => $st['desc'], 'color' => $st['color']],
                'intensity' => ['level' => $intensity, 'name' => $int['name'], 'icon' => $int['icon']],
                'agent' => ['number' => $agentConfig['number'], 'name' => $agentConfig['name']]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트 (class 정의는 최상위 레벨에 위치)
class Agent20ChatPersonaTest extends BasePersonaTest {
        private $agentConfig;
        public function __construct($config) {
            $this->agentConfig = $config;
            parent::__construct(20, 'intervention_preparation', '개입 준비', __DIR__);
        }
        protected function getRequiredFiles(): array {
            return ['engine/Agent20PersonaEngine.php' => 'Agent20PersonaEngine 메인 클래스', 'personas.md' => '페르소나 문서', 'rules.yaml' => '규칙 파일'];
        }
        protected function getRequiredTables(): array {
            return ['mdl_at_agent_persona_state' => '페르소나 상태 테이블', 'mdl_at_agent_messages' => '메시지 테이블'];
        }
        protected function runCustomTests(): void {
            $this->testInterventionStrategies();
            $this->testApiEndpoint('api/', 'GET');
        }
        private function testInterventionStrategies(): void {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            if (!file_exists($filePath)) { $this->recordTest('개입 전략', false, '엔진 파일 없음'); return; }
            $content = file_get_contents($filePath);
            $strategies = ['IS01', 'IS02', 'IS03', 'IS04', 'IS05', 'direct', 'guided', 'collaborative', 'feedback', 'metacognitive'];
            $found = 0;
            foreach ($strategies as $s) { if (stripos($content, $s) !== false) $found++; }
            $this->recordTest('5대 개입 전략', $found >= 4, "$found/10 전략 키워드 발견");
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
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(33,150,243,0.3); }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .agent-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-right: 12px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; }
        .tab { flex: 1; padding: 14px; text-align: center; border-radius: 8px; text-decoration: none; color: #666; font-weight: 500; }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }

        .strategy-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .strategy-section h3 { margin-bottom: 15px; color: #333; font-size: 16px; }
        .strategy-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; }
        .strategy-item { padding: 20px 12px; text-align: center; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; position: relative; overflow: hidden; }
        .strategy-item::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: var(--item-color, #2196f3); }
        .strategy-item:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(33,150,243,0.2); }
        .strategy-item.active { border-color: var(--primary); background: var(--light); }
        .strategy-item .icon { font-size: 32px; margin-bottom: 10px; }
        .strategy-item .name { font-weight: bold; font-size: 13px; margin-bottom: 5px; }
        .strategy-item .desc { font-size: 10px; color: #888; }

        .intensity-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .intensity-row { display: flex; gap: 15px; }
        .intensity-item { flex: 1; padding: 20px; text-align: center; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; }
        .intensity-item:hover { border-color: var(--primary); }
        .intensity-item.active { border-color: var(--primary); background: var(--light); }
        .intensity-item .icon { font-size: 36px; margin-bottom: 8px; }
        .intensity-item .name { font-weight: bold; font-size: 14px; margin-bottom: 4px; }
        .intensity-item .desc { font-size: 11px; color: #888; }

        .chat-container { background: white; border-radius: 16px; overflow: hidden; }
        .chat-messages { height: 300px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; line-height: 1.5; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .strategy-tag { display: inline-flex; align-items: center; gap: 4px; padding: 4px 12px; border-radius: 15px; font-size: 11px; margin-top: 8px; color: white; }
        .chat-input { padding: 20px; border-top: 1px solid #eee; display: flex; gap: 12px; }
        .chat-input input { flex: 1; padding: 14px 20px; border: 2px solid #e0e0e0; border-radius: 25px; outline: none; }
        .chat-input button { padding: 14px 28px; background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; border: none; border-radius: 25px; cursor: pointer; }
        .diagnosis-container { background: white; border-radius: 16px; padding: 20px; }

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
        <!-- 헤더 네비게이션 -->
        <div class="header-nav">
            <h1><span class="agent-badge">Agent <?= str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT) ?></span>🚀 <?= $agentConfig['name'] ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji">🚀</span>
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

        <div class="header">
            <p><?= $agentConfig['description'] ?></p>
        </div>

        <div class="tabs">
            <a href="?tab=chat" class="tab <?= $currentTab === 'chat' ? 'active' : '' ?>">💬 채팅 테스트</a>
            <a href="?tab=diagnosis" class="tab <?= $currentTab === 'diagnosis' ? 'active' : '' ?>">🔍 진단 테스트</a>
        </div>

        <?php if ($currentTab === 'chat'): ?>
        <div class="strategy-section">
            <h3>🎯 5대 개입 전략 선택</h3>
            <div class="strategy-grid" id="strategyGrid">
                <?php foreach ($agentConfig['strategies'] as $code => $info): ?>
                <div class="strategy-item <?= $code === 'IS01' ? 'active' : '' ?>" data-code="<?= $code ?>" style="--item-color: <?= $info['color'] ?>" onclick="selectStrategy('<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="desc"><?= $info['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="intensity-section">
            <h3>📊 개입 강도 선택</h3>
            <div class="intensity-row" id="intensityRow">
                <?php foreach ($agentConfig['intensity'] as $level => $info): ?>
                <div class="intensity-item <?= $level === 'medium' ? 'active' : '' ?>" data-level="<?= $level ?>" onclick="selectIntensity('<?= $level ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="desc"><?= $info['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">🎯</div>
                    <div class="message-content">안녕하세요! 개입 준비 에이전트입니다. 개입 전략과 강도를 선택해주세요.</div>
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
            try { $test = new Agent20ChatPersonaTest($agentConfig); $test->runAllTests(); $test->renderHtml(); }
            catch (Throwable $e) { echo "<div style='color:red;'>오류: " . htmlspecialchars($e->getMessage()) . "</div>"; }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentStrategy = 'IS01';
    let currentIntensity = 'medium';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectStrategy(code) {
        currentStrategy = code;
        document.querySelectorAll('#strategyGrid .strategy-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.strategy-item[data-code="${code}"]`).classList.add('active');
    }

    function selectIntensity(level) {
        currentIntensity = level;
        document.querySelectorAll('#intensityRow .intensity-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.intensity-item[data-level="${level}"]`).classList.add('active');
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
            body: `action=send_message&message=${encodeURIComponent(message)}&strategy=${currentStrategy}&intensity=${currentIntensity}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) addAgentMessage(data.response);
        });
    }

    function addMessage(text, type) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.innerHTML = `<div class="message-avatar">${type === 'agent' ? '🎯' : '👤'}</div><div class="message-content">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function addAgentMessage(r) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message agent';
        div.innerHTML = `<div class="message-avatar">🎯</div><div class="message-content">${r.message}<span class="strategy-tag" style="background:${r.strategy.color}">${r.strategy.icon} ${r.strategy.name} (${r.intensity.icon} ${r.intensity.name})</span></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
