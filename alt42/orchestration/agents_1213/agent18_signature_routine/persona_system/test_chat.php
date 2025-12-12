<?php
/**
 * test_chat.php - Agent18 시그니처 루틴 통합 테스트
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent18_signature_routine/persona_system/test_chat.php
 */

// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('MOODLE_INTERNAL')) define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

$agentConfig = [
    'number' => 18,
    'id' => 'signature_routine',
    'name' => '시그니처 루틴',
    'description' => '개인화된 학습 루틴 패턴 분석 및 최적화',
    'color' => [
        'primary' => '#f1c40f',
        'secondary' => '#d4ac0d',
        'light' => '#fef9e7',
        'dark' => '#9a7d0a',
        'gradient_start' => '#f1c40f',
        'gradient_end' => '#f39c12'
    ],
    'api_endpoint' => 'api/',

    // SR (시그니처 루틴) 컨텍스트 코드
    'sr_contexts' => [
        'SR01' => ['name' => '첫 루틴 분석', 'icon' => '🔍', 'desc' => '학습 패턴 첫 분석 시작'],
        'SR02' => ['name' => '패턴 발견', 'icon' => '📊', 'desc' => '규칙적인 학습 패턴 감지'],
        'SR03' => ['name' => '루틴 형성', 'icon' => '🔄', 'desc' => '지속적 학습 루틴 형성 중'],
        'SR04' => ['name' => '루틴 정착', 'icon' => '✅', 'desc' => '안정적인 루틴 정착'],
        'SR05' => ['name' => '루틴 최적화', 'icon' => '⚡', 'desc' => '더 나은 루틴으로 개선']
    ],

    // TP (골든타임) 컨텍스트 코드
    'tp_contexts' => [
        'TP01' => ['name' => '골든타임 탐색', 'icon' => '🌅', 'desc' => '최적 학습 시간대 탐색'],
        'TP02' => ['name' => '골든타임 발견', 'icon' => '⭐', 'desc' => '최적 학습 시간 발견'],
        'TP03' => ['name' => '시간 활용', 'icon' => '⏰', 'desc' => '골든타임 적극 활용'],
        'TP04' => ['name' => '시간 확장', 'icon' => '📈', 'desc' => '효율적 학습 시간 확대']
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
        $srCode = isset($_POST['sr_code']) ? $_POST['sr_code'] : 'SR01';
        $tpCode = isset($_POST['tp_code']) ? $_POST['tp_code'] : 'TP01';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.']);
            exit;
        }

        $sr = $agentConfig['sr_contexts'][$srCode] ?? $agentConfig['sr_contexts']['SR01'];
        $tp = $agentConfig['tp_contexts'][$tpCode] ?? $agentConfig['tp_contexts']['TP01'];

        $responses = [
            'SR01' => "학습 패턴을 분석하기 시작했어요. {$tp['icon']} {$tp['name']} 시간대에 집중해볼게요.",
            'SR02' => "당신만의 학습 패턴이 보이기 시작해요! {$tp['icon']} {$tp['name']}을 활용해보세요.",
            'SR03' => "루틴이 형성되고 있어요. {$tp['icon']} {$tp['name']}과 함께 꾸준히 해봐요.",
            'SR04' => "훌륭해요! 안정적인 루틴이 자리잡았어요. {$tp['icon']} {$tp['name']}을 유지해요.",
            'SR05' => "더 효율적인 루틴으로 발전시켜볼까요? {$tp['icon']} {$tp['name']}을 최적화해요."
        ];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responses[$srCode] ?? $responses['SR01'],
                'sr_context' => ['code' => $srCode, 'name' => $sr['name'], 'icon' => $sr['icon'], 'desc' => $sr['desc']],
                'tp_context' => ['code' => $tpCode, 'name' => $tp['name'], 'icon' => $tp['icon'], 'desc' => $tp['desc']],
                'agent' => ['number' => $agentConfig['number'], 'name' => $agentConfig['name']]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트 (class 정의는 최상위 레벨에 위치)
class Agent18ChatPersonaTest extends BasePersonaTest {
        private $agentConfig;
        public function __construct($config) {
            $this->agentConfig = $config;
            parent::__construct(18, 'signature_routine', '시그니처 루틴', __DIR__);
        }
        protected function getRequiredFiles(): array {
            return ['engine/PersonaRuleEngine.php' => 'PersonaRuleEngine 메인 클래스', 'engine/RoutineAnalyzer.php' => '루틴 분석기', 'personas.md' => '페르소나 문서', 'rules.yaml' => '규칙 파일'];
        }
        protected function getRequiredTables(): array {
            return ['mdl_alt42_agent18_persona_records' => '페르소나 기록 테이블', 'mdl_alt42_agent18_routine_patterns' => '루틴 패턴 테이블'];
        }
        protected function runCustomTests(): void {
            $this->testContextCodes();
            $this->testRoutineAnalyzer();
            $this->testApiEndpoint('api/', 'GET');
        }
        private function testContextCodes(): void {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            if (!file_exists($filePath)) { $this->recordTest('컨텍스트 코드', false, '엔진 파일 없음'); return; }
            $content = file_get_contents($filePath);
            $codes = ['SR01', 'SR02', 'TP01', 'TP02'];
            $found = 0;
            foreach ($codes as $c) { if (stripos($content, $c) !== false) $found++; }
            $this->recordTest('SR/TP 컨텍스트 코드', $found >= 3, "$found/4 코드 발견");
        }
        private function testRoutineAnalyzer(): void {
            $filePath = __DIR__ . '/engine/RoutineAnalyzer.php';
            if (!file_exists($filePath)) { $this->recordTest('RoutineAnalyzer', false, '파일 없음'); return; }
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class RoutineAnalyzer') !== false;
            $this->recordTest('RoutineAnalyzer 클래스', $hasClass, $hasClass ? '클래스 정의됨' : '클래스 없음');
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
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: #333; padding: 30px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(241,196,15,0.3); }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .agent-badge { background: rgba(0,0,0,0.1); padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-right: 12px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; }
        .tab { flex: 1; padding: 14px; text-align: center; border-radius: 8px; text-decoration: none; color: #666; font-weight: 500; }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: #333; }

        .context-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .context-section h3 { margin-bottom: 15px; color: #333; font-size: 16px; }
        .context-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .context-item { flex: 1; min-width: 120px; padding: 15px 10px; text-align: center; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .context-item:hover { border-color: var(--primary); transform: translateY(-2px); }
        .context-item.active { border-color: var(--primary); background: var(--light); }
        .context-item .icon { font-size: 28px; margin-bottom: 8px; }
        .context-item .name { font-weight: bold; font-size: 13px; margin-bottom: 4px; }
        .context-item .desc { font-size: 10px; color: #888; }

        .chat-container { background: white; border-radius: 16px; overflow: hidden; }
        .chat-messages { height: 300px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; line-height: 1.5; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: #333; }
        .context-tags { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
        .context-tag { display: inline-flex; align-items: center; gap: 4px; background: var(--light); padding: 4px 10px; border-radius: 12px; font-size: 11px; }
        .chat-input { padding: 20px; border-top: 1px solid #eee; display: flex; gap: 12px; }
        .chat-input input { flex: 1; padding: 14px 20px; border: 2px solid #e0e0e0; border-radius: 25px; outline: none; }
        .chat-input button { padding: 14px 28px; background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: #333; border: none; border-radius: 25px; cursor: pointer; font-weight: bold; }
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
            <h1><span class="agent-badge">Agent <?= str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT) ?></span>✨ <?= $agentConfig['name'] ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji">✨</span>
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
        <div class="context-section">
            <h3>🔄 시그니처 루틴 (SR) 컨텍스트</h3>
            <div class="context-row" id="srContexts">
                <?php foreach ($agentConfig['sr_contexts'] as $code => $info): ?>
                <div class="context-item <?= $code === 'SR01' ? 'active' : '' ?>" data-code="<?= $code ?>" data-type="sr" onclick="selectContext('sr', '<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="desc"><?= $info['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="context-section">
            <h3>⏰ 골든타임 (TP) 컨텍스트</h3>
            <div class="context-row" id="tpContexts">
                <?php foreach ($agentConfig['tp_contexts'] as $code => $info): ?>
                <div class="context-item <?= $code === 'TP01' ? 'active' : '' ?>" data-code="<?= $code ?>" data-type="tp" onclick="selectContext('tp', '<?= $code ?>')">
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
                    <div class="message-avatar">🔄</div>
                    <div class="message-content">안녕하세요! 시그니처 루틴 에이전트입니다. SR과 TP 컨텍스트를 선택해주세요.</div>
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
            try { $test = new Agent18ChatPersonaTest($agentConfig); $test->runAllTests(); $test->renderHtml(); }
            catch (Throwable $e) { echo "<div style='color:red;'>오류: " . htmlspecialchars($e->getMessage()) . "</div>"; }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentSR = 'SR01';
    let currentTP = 'TP01';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectContext(type, code) {
        if (type === 'sr') {
            currentSR = code;
            document.querySelectorAll('#srContexts .context-item').forEach(i => i.classList.remove('active'));
        } else {
            currentTP = code;
            document.querySelectorAll('#tpContexts .context-item').forEach(i => i.classList.remove('active'));
        }
        document.querySelector(`.context-item[data-code="${code}"][data-type="${type}"]`).classList.add('active');
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
            body: `action=send_message&message=${encodeURIComponent(message)}&sr_code=${currentSR}&tp_code=${currentTP}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const r = data.response;
                addAgentMessage(r.message, r.sr_context, r.tp_context);
            }
        });
    }

    function addMessage(text, type) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.innerHTML = `<div class="message-avatar">${type === 'agent' ? '🔄' : '👤'}</div><div class="message-content">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function addAgentMessage(text, sr, tp) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message agent';
        div.innerHTML = `<div class="message-avatar">🔄</div><div class="message-content">${text}<div class="context-tags"><span class="context-tag">${sr.icon} ${sr.name}</span><span class="context-tag">${tp.icon} ${tp.name}</span></div></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
