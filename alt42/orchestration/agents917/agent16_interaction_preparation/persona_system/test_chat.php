<?php
/**
 * test_chat.php - Agent16 상호작용 준비 통합 테스트
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/persona_system/test_chat.php
 */

// =========================================================================
// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('MOODLE_INTERNAL')) define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

$agentConfig = [
    'number' => 16,
    'id' => 'interaction_preparation',
    'name' => '상호작용 준비',
    'description' => '학습 세계관 기반 최적의 상호작용 전략 준비',
    'color' => [
        'primary' => '#f39c12',
        'secondary' => '#e67e22',
        'light' => '#fdebd0',
        'dark' => '#9a7d0a',
        'gradient_start' => '#f39c12',
        'gradient_end' => '#e67e22'
    ],
    'api_endpoint' => 'api/',

    // 9개 세계관
    'worldviews' => [
        'curriculum' => ['name' => '커리큘럼 기반', 'icon' => '📚', 'desc' => '정해진 교육과정 따라가기'],
        'personalized' => ['name' => '개인화', 'icon' => '👤', 'desc' => '맞춤형 학습 경로'],
        'exam_prep' => ['name' => '시험 준비', 'icon' => '📝', 'desc' => '시험 대비 집중 학습'],
        'short_mission' => ['name' => '짧은 미션', 'icon' => '🎯', 'desc' => '작은 목표 달성'],
        'self_reflection' => ['name' => '자기 성찰', 'icon' => '🪞', 'desc' => '학습 과정 되돌아보기'],
        'self_directed' => ['name' => '자기주도', 'icon' => '🧭', 'desc' => '스스로 방향 설정'],
        'apprenticeship' => ['name' => '도제 학습', 'icon' => '👨‍🎓', 'desc' => '전문가 따라 배우기'],
        'time_reflection' => ['name' => '시간 성찰', 'icon' => '⏳', 'desc' => '시간 활용 점검'],
        'inquiry_learning' => ['name' => '탐구 학습', 'icon' => '🔬', 'desc' => '질문과 탐색으로 배우기']
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
        $worldview = isset($_POST['worldview']) ? $_POST['worldview'] : 'curriculum';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.']);
            exit;
        }

        $wv = $agentConfig['worldviews'][$worldview] ?? $agentConfig['worldviews']['curriculum'];

        $responses = [
            'curriculum' => "교육과정에 맞춰 체계적으로 학습을 준비할게요. 다음 단계를 확인해볼까요?",
            'personalized' => "당신만의 학습 스타일에 맞춰 상호작용을 준비했어요.",
            'exam_prep' => "시험 준비에 최적화된 학습 전략을 세워볼게요.",
            'short_mission' => "작은 미션으로 성취감을 느껴볼까요? 오늘의 목표를 설정해요.",
            'self_reflection' => "지금까지의 학습 과정을 함께 돌아봐요.",
            'self_directed' => "스스로 정한 방향으로 나아갈 준비가 되셨나요?",
            'apprenticeship' => "전문가의 방법을 따라 단계별로 배워볼까요?",
            'time_reflection' => "시간을 어떻게 사용하고 있는지 점검해볼게요.",
            'inquiry_learning' => "궁금한 것을 탐구하며 배워가요. 어떤 질문이 있나요?"
        ];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responses[$worldview],
                'worldview' => ['code' => $worldview, 'name' => $wv['name'], 'icon' => $wv['icon'], 'desc' => $wv['desc']],
                'agent' => ['number' => $agentConfig['number'], 'name' => $agentConfig['name']]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트
class Agent16ChatPersonaTest extends BasePersonaTest {
        private $agentConfig;
        public function __construct($config) {
            $this->agentConfig = $config;
            parent::__construct(16, 'interaction_preparation', '상호작용 준비', __DIR__);
        }
        protected function getRequiredFiles(): array {
            return ['engine/Agent16PersonaEngine.php' => 'Agent16PersonaEngine 메인 클래스', 'personas.md' => '페르소나 문서', 'rules.yaml' => '규칙 파일'];
        }
        protected function getRequiredTables(): array {
            return ['mdl_at_agent_persona_state' => '상태 테이블', 'mdl_at_agent_messages' => '메시지 테이블'];
        }
        protected function runCustomTests(): void {
            $this->testWorldviews();
            $this->testApiEndpoint('api/', 'GET');
        }
        private function testWorldviews(): void {
            $filePath = __DIR__ . '/engine/Agent16PersonaEngine.php';
            if (!file_exists($filePath)) { $this->recordTest('세계관 테스트', false, '엔진 파일 없음'); return; }
            $content = file_get_contents($filePath);
            $worldviews = ['curriculum', 'personalized', 'exam_prep', 'short_mission', 'self_reflection', 'self_directed', 'apprenticeship', 'time_reflection', 'inquiry_learning'];
            $found = 0;
            foreach ($worldviews as $w) { if (stripos($content, $w) !== false) $found++; }
            $this->recordTest('9개 세계관', $found >= 8, "$found/9 세계관 발견");
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
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(243,156,18,0.3); }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .agent-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-right: 12px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; }
        .tab { flex: 1; padding: 14px; text-align: center; border-radius: 8px; text-decoration: none; color: #666; font-weight: 500; }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .worldview-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; background: white; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
        .worldview-item { padding: 20px; text-align: center; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; transition: all 0.3s; }
        .worldview-item:hover { border-color: var(--primary); transform: translateY(-3px); box-shadow: 0 5px 15px rgba(243,156,18,0.2); }
        .worldview-item.active { border-color: var(--primary); background: var(--light); }
        .worldview-item .icon { font-size: 36px; margin-bottom: 10px; }
        .worldview-item .name { font-weight: bold; margin-bottom: 5px; }
        .worldview-item .desc { font-size: 11px; color: #888; }
        .chat-container { background: white; border-radius: 16px; overflow: hidden; }
        .chat-messages { height: 350px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; line-height: 1.5; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .worldview-tag { display: inline-flex; align-items: center; gap: 4px; background: var(--light); padding: 4px 10px; border-radius: 12px; font-size: 11px; margin-top: 8px; }
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
            <h1><span class="agent-badge">Agent <?= str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT) ?></span>🤝 <?= $agentConfig['name'] ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji">🤝</span>
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
        <div class="worldview-grid">
            <?php foreach ($agentConfig['worldviews'] as $code => $info): ?>
            <div class="worldview-item <?= $code === 'curriculum' ? 'active' : '' ?>" data-code="<?= $code ?>" onclick="selectWorldview('<?= $code ?>')">
                <div class="icon"><?= $info['icon'] ?></div>
                <div class="name"><?= $info['name'] ?></div>
                <div class="desc"><?= $info['desc'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">🎯</div>
                    <div class="message-content">안녕하세요! 상호작용 준비 에이전트입니다. 위에서 학습 세계관을 선택해주세요.</div>
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
            try { $test = new Agent16ChatPersonaTest($agentConfig); $test->runAllTests(); $test->renderHtml(); }
            catch (Throwable $e) { echo "<div style='color:red;'>오류: " . htmlspecialchars($e->getMessage()) . "</div>"; }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentWorldview = 'curriculum';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectWorldview(code) {
        currentWorldview = code;
        document.querySelectorAll('.worldview-item').forEach(i => i.classList.remove('active'));
        document.querySelector(`.worldview-item[data-code="${code}"]`).classList.add('active');
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
            body: `action=send_message&message=${encodeURIComponent(message)}&worldview=${currentWorldview}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const r = data.response;
                addAgentMessage(r.message, r.worldview);
            }
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

    function addAgentMessage(text, worldview) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message agent';
        div.innerHTML = `<div class="message-avatar">🎯</div><div class="message-content">${text}<div class="worldview-tag">${worldview.icon} ${worldview.name}</div></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
