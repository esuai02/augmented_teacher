<?php
/**
 * test_chat.php - Agent21 개입 실행 통합 테스트
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent21_intervention_execution/persona_system/test_chat.php
 */

// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('MOODLE_INTERNAL')) define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

$agentConfig = [
    'number' => 21,
    'id' => 'intervention_execution',
    'name' => '개입 실행',
    'description' => 'A/R/N/D 반응 유형 기반 실시간 개입 실행',
    'color' => [
        'primary' => '#4caf50',
        'secondary' => '#388e3c',
        'light' => '#e8f5e9',
        'dark' => '#1b5e20',
        'gradient_start' => '#4caf50',
        'gradient_end' => '#2e7d32'
    ],
    'api_endpoint' => 'api/',

    // A/R/N/D 반응 유형
    'response_types' => [
        'A' => ['name' => '수용', 'full' => 'Acceptance', 'icon' => '✅', 'desc' => '제안을 받아들이고 실행', 'color' => '#4caf50'],
        'R' => ['name' => '저항', 'full' => 'Resistance', 'icon' => '🚫', 'desc' => '제안에 대한 거부 또는 반발', 'color' => '#f44336'],
        'N' => ['name' => '무응답', 'full' => 'No Response', 'icon' => '😶', 'desc' => '응답 없음 또는 회피', 'color' => '#9e9e9e'],
        'D' => ['name' => '지연반응', 'full' => 'Delayed', 'icon' => '⏳', 'desc' => '시간을 두고 반응', 'color' => '#ff9800']
    ],

    // 전환 유형
    'transitions' => [
        'positive' => ['icon' => '📈', 'desc' => '긍정적 전환'],
        'negative' => ['icon' => '📉', 'desc' => '부정적 전환'],
        'neutral' => ['icon' => '➡️', 'desc' => '중립적 유지']
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
        $responseType = isset($_POST['response_type']) ? $_POST['response_type'] : 'A';
        $prevType = isset($_POST['prev_type']) ? $_POST['prev_type'] : '';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.']);
            exit;
        }

        $rt = $agentConfig['response_types'][$responseType] ?? $agentConfig['response_types']['A'];

        // 전환 판정
        $transition = null;
        if ($prevType && $prevType !== $responseType) {
            $positiveTransitions = ['R->A', 'N->A', 'D->A', 'R->D', 'N->D'];
            $negativeTransitions = ['A->R', 'A->N', 'D->R', 'D->N'];
            $transKey = "{$prevType}->{$responseType}";

            if (in_array($transKey, $positiveTransitions)) {
                $transition = ['type' => 'positive', 'from' => $prevType, 'to' => $responseType];
            } elseif (in_array($transKey, $negativeTransitions)) {
                $transition = ['type' => 'negative', 'from' => $prevType, 'to' => $responseType];
            } else {
                $transition = ['type' => 'neutral', 'from' => $prevType, 'to' => $responseType];
            }
        }

        $responses = [
            'A' => "수용 반응이 감지되었어요! 좋아요, 함께 진행해볼까요?",
            'R' => "저항 반응이 있네요. 다른 방법을 찾아볼까요?",
            'N' => "응답이 없으시네요. 괜찮아요, 준비되면 알려주세요.",
            'D' => "시간이 필요하시군요. 천천히 생각해보세요."
        ];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responses[$responseType] ?? $responses['A'],
                'response_type' => ['code' => $responseType, 'name' => $rt['name'], 'full' => $rt['full'], 'icon' => $rt['icon'], 'color' => $rt['color']],
                'transition' => $transition,
                'agent' => ['number' => $agentConfig['number'], 'name' => $agentConfig['name']]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트 (class 정의는 최상위 레벨에 위치)
class Agent21ChatPersonaTest extends BasePersonaTest {
        private $agentConfig;
        public function __construct($config) {
            $this->agentConfig = $config;
            parent::__construct(21, 'intervention_execution', '개입 실행', __DIR__);
        }
        protected function getRequiredFiles(): array {
            return ['engine/PersonaEngine.php' => 'PersonaEngine 메인 클래스', 'personas.md' => '페르소나 문서', 'rules.yaml' => '규칙 파일'];
        }
        protected function getRequiredTables(): array {
            return ['mdl_at_agent_persona_state' => '페르소나 상태 테이블', 'mdl_at_agent_messages' => '메시지 테이블'];
        }
        protected function runCustomTests(): void {
            $this->testResponseTypes();
            $this->testTransitionDetection();
            $this->testApiEndpoint('api/', 'GET');
        }
        private function testResponseTypes(): void {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            if (!file_exists($filePath)) { $this->recordTest('반응 유형', false, '엔진 파일 없음'); return; }
            $content = file_get_contents($filePath);
            $types = ['acceptance', 'resistance', 'no_response', 'delayed', "'A'", "'R'", "'N'", "'D'"];
            $found = 0;
            foreach ($types as $t) { if (stripos($content, $t) !== false) $found++; }
            $this->recordTest('A/R/N/D 반응 유형', $found >= 4, "$found/8 반응 유형 키워드 발견");
        }
        private function testTransitionDetection(): void {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            if (!file_exists($filePath)) { return; }
            $content = file_get_contents($filePath);
            $hasTransition = stripos($content, 'transition') !== false;
            $this->recordTest('전환 감지', $hasTransition, $hasTransition ? 'transition 로직 있음' : 'transition 없음');
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
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(76,175,80,0.3); }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .agent-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-right: 12px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; }
        .tab { flex: 1; padding: 14px; text-align: center; border-radius: 8px; text-decoration: none; color: #666; font-weight: 500; }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }

        .response-section { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; }
        .response-section h3 { margin-bottom: 15px; color: #333; font-size: 16px; }
        .response-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .response-item { padding: 25px 15px; text-align: center; border: 3px solid #e0e0e0; border-radius: 16px; cursor: pointer; transition: all 0.3s; position: relative; }
        .response-item::after { content: attr(data-code); position: absolute; top: 10px; right: 10px; font-size: 12px; font-weight: bold; color: #999; }
        .response-item:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(0,0,0,0.15); }
        .response-item.active { border-color: var(--item-color); background: linear-gradient(135deg, white, var(--item-light)); }
        .response-item .icon { font-size: 48px; margin-bottom: 12px; }
        .response-item .name { font-weight: bold; font-size: 18px; margin-bottom: 4px; }
        .response-item .full { font-size: 12px; color: #666; margin-bottom: 8px; }
        .response-item .desc { font-size: 11px; color: #888; }

        .prev-section { background: white; padding: 15px 20px; border-radius: 12px; margin-bottom: 15px; }
        .prev-section h3 { margin-bottom: 10px; color: #333; font-size: 14px; }
        .prev-row { display: flex; gap: 10px; }
        .prev-item { padding: 10px 15px; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .prev-item:hover { border-color: #999; }
        .prev-item.active { border-color: var(--primary); background: var(--light); }

        .transition-alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 15px; display: none; }
        .transition-alert.positive { background: #e8f5e9; border-left: 4px solid #4caf50; }
        .transition-alert.negative { background: #ffebee; border-left: 4px solid #f44336; }
        .transition-alert.neutral { background: #fff3e0; border-left: 4px solid #ff9800; }

        .chat-container { background: white; border-radius: 16px; overflow: hidden; }
        .chat-messages { height: 280px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; line-height: 1.5; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .response-tag { display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 15px; font-size: 12px; margin-top: 8px; color: white; font-weight: bold; }
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
            <h1><span class="agent-badge">Agent <?= str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT) ?></span>⚡ <?= $agentConfig['name'] ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji">⚡</span>
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
        <div class="response-section">
            <h3>🎯 현재 반응 유형 선택 (A/R/N/D)</h3>
            <div class="response-grid" id="responseGrid">
                <?php foreach ($agentConfig['response_types'] as $code => $info): ?>
                <div class="response-item <?= $code === 'A' ? 'active' : '' ?>" data-code="<?= $code ?>" style="--item-color: <?= $info['color'] ?>; --item-light: <?= $info['color'] ?>20" onclick="selectResponse('<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="full"><?= $info['full'] ?></div>
                    <div class="desc"><?= $info['desc'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="prev-section">
            <h3>📊 이전 반응 유형 (전환 감지용)</h3>
            <div class="prev-row" id="prevRow">
                <div class="prev-item active" data-prev="" onclick="selectPrev('')">없음 (신규)</div>
                <?php foreach ($agentConfig['response_types'] as $code => $info): ?>
                <div class="prev-item" data-prev="<?= $code ?>" onclick="selectPrev('<?= $code ?>')"><?= $info['icon'] ?> <?= $code ?> - <?= $info['name'] ?></div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="transition-alert" id="transitionAlert"></div>

        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">🎯</div>
                    <div class="message-content">안녕하세요! 개입 실행 에이전트입니다. 반응 유형을 선택하고 메시지를 보내주세요.</div>
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
            try { $test = new Agent21ChatPersonaTest($agentConfig); $test->runAllTests(); $test->renderHtml(); }
            catch (Throwable $e) { echo "<div style='color:red;'>오류: " . htmlspecialchars($e->getMessage()) . "</div>"; }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentResponse = 'A';
    let prevResponse = '';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectResponse(code) {
        currentResponse = code;
        document.querySelectorAll('#responseGrid .response-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.response-item[data-code="${code}"]`).classList.add('active');
    }

    function selectPrev(code) {
        prevResponse = code;
        document.querySelectorAll('#prevRow .prev-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.prev-item[data-prev="${code}"]`).classList.add('active');
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
            body: `action=send_message&message=${encodeURIComponent(message)}&response_type=${currentResponse}&prev_type=${prevResponse}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const r = data.response;
                showTransition(r.transition);
                addAgentMessage(r);
                // 현재 반응을 이전 반응으로 업데이트
                selectPrev(currentResponse);
            }
        });
    }

    function showTransition(transition) {
        const alert = document.getElementById('transitionAlert');
        if (!transition) {
            alert.style.display = 'none';
            return;
        }
        const icons = { positive: '📈', negative: '📉', neutral: '➡️' };
        const texts = { positive: '긍정적 전환', negative: '부정적 전환', neutral: '중립적 변화' };
        alert.className = `transition-alert ${transition.type}`;
        alert.innerHTML = `<strong>${icons[transition.type]} ${texts[transition.type]}:</strong> ${transition.from} → ${transition.to}`;
        alert.style.display = 'block';
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
        div.innerHTML = `<div class="message-avatar">🎯</div><div class="message-content">${r.message}<span class="response-tag" style="background:${r.response_type.color}">${r.response_type.icon} ${r.response_type.code}: ${r.response_type.name}</span></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
