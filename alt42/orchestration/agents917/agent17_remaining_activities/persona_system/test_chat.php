<?php
/**
 * test_chat.php - Agent17 잔여 활동 조정 통합 테스트
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/test_chat.php
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
    'number' => 17,
    'id' => 'remaining_activities',
    'name' => '잔여 활동 조정',
    'description' => '학습 진행 상황에 따른 남은 활동 최적화',
    'color' => [
        'primary' => '#1abc9c',
        'secondary' => '#16a085',
        'light' => '#d1f2eb',
        'dark' => '#0e6655',
        'gradient_start' => '#1abc9c',
        'gradient_end' => '#16a085'
    ],
    'api_endpoint' => 'api/',

    // R1-R5 상황 코드
    'situation_codes' => [
        'R1' => ['name' => '원활 진행', 'icon' => '🚀', 'desc' => '순조로운 학습 진행'],
        'R2' => ['name' => '적절 진행', 'icon' => '✅', 'desc' => '적당한 페이스 유지'],
        'R3' => ['name' => '지연 진행', 'icon' => '⏰', 'desc' => '약간의 지연 발생'],
        'R4' => ['name' => '정체 진행', 'icon' => '⚠️', 'desc' => '진행이 멈춘 상태'],
        'R5' => ['name' => '리듬 붕괴', 'icon' => '🆘', 'desc' => '학습 리듬 완전 붕괴']
    ],

    // ST1-ST5 전략 코드
    'strategy_codes' => [
        'ST1' => ['name' => '질문하기', 'icon' => '❓', 'desc' => '탐색적 질문으로 이해도 확인'],
        'ST2' => ['name' => '도제학습 전환', 'icon' => '👨‍🏫', 'desc' => '단계별 안내 학습으로 전환'],
        'ST3' => ['name' => '활동 축소', 'icon' => '📉', 'desc' => '부담 줄여 핵심만 집중'],
        'ST4' => ['name' => '하이튜터링', 'icon' => '🎓', 'desc' => '1:1 집중 지도 모드'],
        'ST5' => ['name' => '징검다리 활동', 'icon' => '🌉', 'desc' => '작은 성취로 연결']
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
        $situation = isset($_POST['situation']) ? $_POST['situation'] : 'R1';
        $strategy = isset($_POST['strategy']) ? $_POST['strategy'] : 'ST1';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.']);
            exit;
        }

        $sitInfo = $agentConfig['situation_codes'][$situation];
        $strInfo = $agentConfig['strategy_codes'][$strategy];

        $responses = [
            'R1_ST1' => "학습이 순조롭네요! 더 깊이 이해하고 싶은 부분이 있나요?",
            'R2_ST1' => "적절한 페이스입니다. 현재 학습 중 어려운 점이 있나요?",
            'R3_ST2' => "조금 지연되고 있어요. 단계별로 함께 진행해볼까요?",
            'R4_ST3' => "잠시 멈춘 것 같아요. 핵심 내용만 먼저 정리해볼게요.",
            'R5_ST4' => "집중 지도 모드로 전환할게요. 천천히 함께 해봐요.",
            'default' => "현재 상황을 파악하고 최적의 전략을 적용할게요."
        ];

        $key = "{$situation}_{$strategy}";
        $responseMsg = $responses[$key] ?? $responses['default'];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responseMsg,
                'situation' => ['code' => $situation, 'name' => $sitInfo['name'], 'icon' => $sitInfo['icon']],
                'strategy' => ['code' => $strategy, 'name' => $strInfo['name'], 'icon' => $strInfo['icon']],
                'persona_code' => "{$situation}_P1"
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트
class Agent17ChatPersonaTest extends BasePersonaTest {
        public function __construct($config) { parent::__construct(17, 'remaining_activities', '잔여 활동 조정', __DIR__); }
        protected function getRequiredFiles(): array {
            return ['engine/Agent17PersonaEngine.php' => '메인 엔진', 'personas.md' => '페르소나 문서', 'rules.yaml' => '규칙 파일'];
        }
        protected function getRequiredTables(): array { return ['mdl_at_agent_persona_state' => '상태', 'mdl_at_agent_messages' => '메시지']; }
        protected function runCustomTests(): void {
            $filePath = __DIR__ . '/engine/Agent17PersonaEngine.php';
            if (!file_exists($filePath)) { $this->recordTest('엔진 파일', false, '없음'); return; }
            $content = file_get_contents($filePath);
            $rFound = 0; $stFound = 0;
            foreach (['R1','R2','R3','R4','R5'] as $r) if (strpos($content, "'$r'") !== false) $rFound++;
            foreach (['ST1','ST2','ST3','ST4','ST5'] as $st) if (strpos($content, "'$st'") !== false) $stFound++;
            $this->recordTest('상황 코드 R1-R5', $rFound >= 5, "$rFound/5");
            $this->recordTest('전략 코드 ST1-ST5', $stFound >= 5, "$stFound/5");
            $this->testApiEndpoint('api/', 'GET');
        }
    }

$colors = $agentConfig['color'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent<?= $agentConfig['number'] ?> <?= $agentConfig['name'] ?></title>
    <style>
        :root { --primary: <?= $colors['primary'] ?>; --light: <?= $colors['light'] ?>; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #f5f7fa, #c3cfe2); min-height: 100vh; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; }
        .header h1 { font-size: 28px; }
        .agent-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-right: 10px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; }
        .tab { flex: 1; padding: 14px; text-align: center; border-radius: 8px; text-decoration: none; color: #666; }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .selector-row { display: flex; gap: 20px; margin-bottom: 20px; }
        .selector-box { flex: 1; background: white; padding: 20px; border-radius: 12px; }
        .selector-box h3 { font-size: 14px; color: #666; margin-bottom: 15px; }
        .code-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; }
        .code-item { padding: 12px 8px; text-align: center; border: 2px solid #e0e0e0; border-radius: 8px; cursor: pointer; font-size: 11px; }
        .code-item:hover { border-color: var(--primary); }
        .code-item.active { border-color: var(--primary); background: var(--light); }
        .code-item .icon { font-size: 20px; margin-bottom: 4px; }
        .combined-persona { background: white; padding: 15px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
        .persona-code { font-size: 24px; font-weight: bold; color: var(--primary); background: var(--light); padding: 10px 20px; border-radius: 10px; }
        .chat-container { background: white; border-radius: 16px; overflow: hidden; }
        .chat-messages { height: 300px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .meta-tags { display: flex; gap: 6px; margin-top: 8px; flex-wrap: wrap; }
        .meta-tag { padding: 3px 8px; border-radius: 10px; font-size: 10px; background: var(--light); }
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
            <h1><span class="agent-badge">Agent <?= str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT) ?></span>⏰ <?= $agentConfig['name'] ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji">⏰</span>
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
            <a href="?tab=chat" class="tab <?= $currentTab === 'chat' ? 'active' : '' ?>">💬 채팅</a>
            <a href="?tab=diagnosis" class="tab <?= $currentTab === 'diagnosis' ? 'active' : '' ?>">🔍 진단</a>
        </div>

        <?php if ($currentTab === 'chat'): ?>
        <div class="selector-row">
            <div class="selector-box">
                <h3>📊 상황 코드 (R1-R5)</h3>
                <div class="code-grid">
                    <?php foreach ($agentConfig['situation_codes'] as $code => $info): ?>
                    <div class="code-item <?= $code === 'R1' ? 'active' : '' ?>" data-type="situation" data-code="<?= $code ?>" onclick="selectCode('situation', '<?= $code ?>')">
                        <div class="icon"><?= $info['icon'] ?></div>
                        <div><?= $code ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="selector-box">
                <h3>🎯 전략 코드 (ST1-ST5)</h3>
                <div class="code-grid">
                    <?php foreach ($agentConfig['strategy_codes'] as $code => $info): ?>
                    <div class="code-item <?= $code === 'ST1' ? 'active' : '' ?>" data-type="strategy" data-code="<?= $code ?>" onclick="selectCode('strategy', '<?= $code ?>')">
                        <div class="icon"><?= $info['icon'] ?></div>
                        <div><?= $code ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="combined-persona">
            <span>현재 페르소나:</span>
            <span class="persona-code" id="personaCode">R1_P1</span>
            <span id="personaDesc">원활 진행 + 질문하기</span>
        </div>

        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">⚡</div>
                    <div class="message-content">안녕하세요! 잔여 활동 조정 에이전트입니다. 상황과 전략을 선택해주세요.</div>
                </div>
            </div>
            <div class="chat-input">
                <input type="text" id="messageInput" placeholder="메시지 입력..." onkeypress="if(event.key==='Enter')sendMessage()">
                <button onclick="sendMessage()">전송</button>
            </div>
        </div>
        <?php else: ?>
        <div class="diagnosis-container">
            <?php try { $test = new Agent17ChatPersonaTest($agentConfig); $test->runAllTests(); $test->renderHtml(); } catch (Throwable $e) { echo "오류: " . $e->getMessage(); } ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentSituation = 'R1', currentStrategy = 'ST1';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectCode(type, code) {
        if (type === 'situation') currentSituation = code;
        else currentStrategy = code;
        document.querySelectorAll(`.code-item[data-type="${type}"]`).forEach(i => i.classList.remove('active'));
        document.querySelector(`.code-item[data-type="${type}"][data-code="${code}"]`).classList.add('active');
        updatePersonaDisplay();
    }

    function updatePersonaDisplay() {
        document.getElementById('personaCode').textContent = currentSituation + '_P1';
        const sit = config.situation_codes[currentSituation];
        const str = config.strategy_codes[currentStrategy];
        document.getElementById('personaDesc').textContent = `${sit.name} + ${str.name}`;
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
            body: `action=send_message&message=${encodeURIComponent(message)}&situation=${currentSituation}&strategy=${currentStrategy}`
        })
        .then(r => r.json())
        .then(data => { if (data.success) addAgentMessage(data.response); });
    }

    function addMessage(text, type) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = `message ${type}`;
        div.innerHTML = `<div class="message-avatar">${type === 'agent' ? '⚡' : '👤'}</div><div class="message-content">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function addAgentMessage(r) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message agent';
        div.innerHTML = `<div class="message-avatar">⚡</div><div class="message-content">${r.message}<div class="meta-tags"><span class="meta-tag">${r.situation.icon} ${r.situation.code}</span><span class="meta-tag">${r.strategy.icon} ${r.strategy.code}</span></div></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
