<?php
/**
 * test_chat.php - Agent19 상호작용 콘텐츠 통합 테스트
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent19_interaction_content/persona_system/test_chat.php
 */

// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
error_reporting(E_ALL);
ini_set('display_errors', 1);
if (!defined('MOODLE_INTERNAL')) define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

$agentConfig = [
    'number' => 19,
    'id' => 'interaction_content',
    'name' => '상호작용 콘텐츠',
    'description' => '3차원 C×B×E 복합 페르소나 기반 맞춤형 콘텐츠 생성',
    'color' => [
        'primary' => '#e91e63',
        'secondary' => '#c2185b',
        'light' => '#fce4ec',
        'dark' => '#880e4f',
        'gradient_start' => '#e91e63',
        'gradient_end' => '#9c27b0'
    ],
    'api_endpoint' => 'api/',

    // C (인지적) 페르소나 - 6개
    'cognitive' => [
        'C1' => ['name' => '활성 인지', 'icon' => '🧠', 'desc' => 'Active Cognition'],
        'C2' => ['name' => '피로 인지', 'icon' => '😴', 'desc' => 'Fatigued Cognition'],
        'C3' => ['name' => '개념 지향', 'icon' => '💡', 'desc' => 'Concept Oriented'],
        'C4' => ['name' => '문제 해결', 'icon' => '🔧', 'desc' => 'Problem Solving'],
        'C5' => ['name' => '패턴 인식', 'icon' => '🔍', 'desc' => 'Pattern Recognition'],
        'C6' => ['name' => '추론 지향', 'icon' => '🎯', 'desc' => 'Reasoning Oriented']
    ],

    // B (행동적) 페르소나 - 6개
    'behavioral' => [
        'B1' => ['name' => '적극 참여', 'icon' => '🚀', 'desc' => 'Active Engager'],
        'B2' => ['name' => '수동 관찰', 'icon' => '👀', 'desc' => 'Passive Observer'],
        'B3' => ['name' => '즉흥 학습', 'icon' => '⚡', 'desc' => 'Spontaneous Learner'],
        'B4' => ['name' => '신중 학습', 'icon' => '🎓', 'desc' => 'Deliberate Learner'],
        'B5' => ['name' => '지속 몰입', 'icon' => '🌊', 'desc' => 'Sustained Flow'],
        'B6' => ['name' => '간헐 학습', 'icon' => '🔄', 'desc' => 'Intermittent Learner']
    ],

    // E (감정적) 페르소나 - 6개
    'emotional' => [
        'E1' => ['name' => '자신감', 'icon' => '💪', 'desc' => 'Confident State'],
        'E2' => ['name' => '불안', 'icon' => '😰', 'desc' => 'Anxious State'],
        'E3' => ['name' => '권태', 'icon' => '😐', 'desc' => 'Bored State'],
        'E4' => ['name' => '도전', 'icon' => '🔥', 'desc' => 'Challenged State'],
        'E5' => ['name' => '좌절', 'icon' => '😔', 'desc' => 'Frustrated State'],
        'E6' => ['name' => '안정', 'icon' => '😌', 'desc' => 'Stable State']
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
        $cCode = isset($_POST['c_code']) ? $_POST['c_code'] : 'C1';
        $bCode = isset($_POST['b_code']) ? $_POST['b_code'] : 'B1';
        $eCode = isset($_POST['e_code']) ? $_POST['e_code'] : 'E1';

        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => '메시지를 입력해주세요.']);
            exit;
        }

        $c = $agentConfig['cognitive'][$cCode] ?? $agentConfig['cognitive']['C1'];
        $b = $agentConfig['behavioral'][$bCode] ?? $agentConfig['behavioral']['B1'];
        $e = $agentConfig['emotional'][$eCode] ?? $agentConfig['emotional']['E1'];

        $compositeCode = "{$cCode}-{$bCode}-{$eCode}";

        // 복합 페르소나 기반 응답 생성
        $cognitiveResponses = [
            'C1' => '명확하게 집중할 수 있는 상태네요.',
            'C2' => '조금 피곤해 보여요. 쉬운 내용부터 시작해볼까요?',
            'C3' => '개념을 이해하고 싶으시군요.',
            'C4' => '문제를 해결하고 싶으시네요.',
            'C5' => '패턴을 찾고 계시는군요.',
            'C6' => '논리적 추론을 원하시네요.'
        ];

        $behavioralResponses = [
            'B1' => '적극적으로 참여해주세요!',
            'B2' => '천천히 관찰하면서 배워봐요.',
            'B3' => '즉흥적으로 시작해볼까요?',
            'B4' => '차근차근 진행해봐요.',
            'B5' => '몰입 상태를 유지해봐요.',
            'B6' => '조금씩 나눠서 학습해요.'
        ];

        $emotionalResponses = [
            'E1' => '자신감이 느껴져요! 잘 하고 계세요.',
            'E2' => '걱정마세요, 함께 해결해봐요.',
            'E3' => '새로운 도전을 찾아볼까요?',
            'E4' => '도전 정신이 좋아요!',
            'E5' => '괜찮아요, 한 걸음씩 나아가요.',
            'E6' => '안정적인 상태네요. 좋아요!'
        ];

        $responseMessage = $cognitiveResponses[$cCode] . ' ' . $behavioralResponses[$bCode] . ' ' . $emotionalResponses[$eCode];

        echo json_encode([
            'success' => true,
            'response' => [
                'message' => $responseMessage,
                'composite_code' => $compositeCode,
                'cognitive' => ['code' => $cCode, 'name' => $c['name'], 'icon' => $c['icon']],
                'behavioral' => ['code' => $bCode, 'name' => $b['name'], 'icon' => $b['icon']],
                'emotional' => ['code' => $eCode, 'name' => $e['name'], 'icon' => $e['icon']],
                'agent' => ['number' => $agentConfig['number'], 'name' => $agentConfig['name']]
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}

// 진단 테스트 (class 정의는 최상위 레벨에 위치)
class Agent19ChatPersonaTest extends BasePersonaTest {
        private $agentConfig;
        public function __construct($config) {
            $this->agentConfig = $config;
            parent::__construct(19, 'interaction_content', '상호작용 콘텐츠', __DIR__);
        }
        protected function getRequiredFiles(): array {
            return ['engine/PersonaEngine.php' => 'PersonaEngine 메인 클래스', 'engine/ContextAnalyzer.php' => '컨텍스트 분석기', 'personas.md' => '페르소나 문서', 'rules.yaml' => '규칙 파일'];
        }
        protected function getRequiredTables(): array {
            return ['mdl_agent19_persona_state' => '페르소나 상태 테이블', 'mdl_agent19_persona_history' => '페르소나 이력 테이블'];
        }
        protected function runCustomTests(): void {
            $this->test3DPersonas();
            $this->testCompositePersona();
            $this->testApiEndpoint('api/', 'GET');
        }
        private function test3DPersonas(): void {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            if (!file_exists($filePath)) { $this->recordTest('3D 페르소나', false, '엔진 파일 없음'); return; }
            $content = file_get_contents($filePath);
            $hasCognitive = strpos($content, 'cognitive') !== false;
            $hasBehavioral = strpos($content, 'behavioral') !== false;
            $hasEmotional = strpos($content, 'emotional') !== false;
            $this->recordTest('C×B×E 3차원 페르소나', $hasCognitive && $hasBehavioral && $hasEmotional, ($hasCognitive && $hasBehavioral && $hasEmotional) ? '3차원 정의됨' : '일부 차원 없음');
        }
        private function testCompositePersona(): void {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            if (!file_exists($filePath)) { return; }
            $content = file_get_contents($filePath);
            $hasComposite = strpos($content, 'composite') !== false;
            $this->recordTest('복합 페르소나 코드', $hasComposite, $hasComposite ? 'composite 코드 사용' : 'composite 없음');
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
        .header { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; padding: 30px; border-radius: 16px; margin-bottom: 20px; box-shadow: 0 10px 40px rgba(233,30,99,0.3); }
        .header h1 { font-size: 28px; margin-bottom: 8px; }
        .agent-badge { background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 20px; font-size: 14px; margin-right: 12px; }
        .tabs { display: flex; gap: 4px; margin-bottom: 20px; background: white; padding: 8px; border-radius: 12px; }
        .tab { flex: 1; padding: 14px; text-align: center; border-radius: 8px; text-decoration: none; color: #666; font-weight: 500; }
        .tab.active { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }

        .dimension-section { background: white; padding: 15px; border-radius: 12px; margin-bottom: 15px; }
        .dimension-section h3 { margin-bottom: 12px; color: #333; font-size: 15px; display: flex; align-items: center; gap: 8px; }
        .dimension-section h3 span { padding: 2px 8px; border-radius: 4px; font-size: 12px; color: white; }
        .dimension-section h3 span.cognitive { background: #3f51b5; }
        .dimension-section h3 span.behavioral { background: #4caf50; }
        .dimension-section h3 span.emotional { background: #ff9800; }
        .persona-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; }
        .persona-item { padding: 12px 8px; text-align: center; border: 2px solid #e0e0e0; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .persona-item:hover { border-color: var(--primary); transform: translateY(-2px); }
        .persona-item.active { border-color: var(--primary); background: var(--light); }
        .persona-item .icon { font-size: 24px; margin-bottom: 6px; }
        .persona-item .name { font-weight: bold; font-size: 11px; margin-bottom: 2px; }
        .persona-item .code { font-size: 10px; color: #888; }

        .composite-display { background: var(--light); padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 15px; }
        .composite-code { font-size: 24px; font-weight: bold; color: var(--dark); }
        .composite-desc { font-size: 12px; color: #666; margin-top: 5px; }

        .chat-container { background: white; border-radius: 16px; overflow: hidden; }
        .chat-messages { height: 250px; overflow-y: auto; padding: 20px; background: #fafafa; }
        .message { margin-bottom: 16px; display: flex; gap: 12px; }
        .message.user { flex-direction: row-reverse; }
        .message-avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
        .message.agent .message-avatar { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .message.user .message-avatar { background: #e0e0e0; }
        .message-content { max-width: 70%; padding: 14px 18px; border-radius: 16px; line-height: 1.5; }
        .message.agent .message-content { background: white; border: 1px solid #e0e0e0; }
        .message.user .message-content { background: linear-gradient(135deg, <?= $colors['gradient_start'] ?>, <?= $colors['gradient_end'] ?>); color: white; }
        .persona-tags { display: flex; gap: 4px; margin-top: 8px; flex-wrap: wrap; }
        .persona-tag { padding: 3px 8px; border-radius: 10px; font-size: 10px; font-weight: bold; }
        .persona-tag.cognitive { background: #e8eaf6; color: #3f51b5; }
        .persona-tag.behavioral { background: #e8f5e9; color: #4caf50; }
        .persona-tag.emotional { background: #fff3e0; color: #ff9800; }
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
            <h1><span class="agent-badge">Agent <?= str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT) ?></span>💬 <?= $agentConfig['name'] ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji">💬</span>
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
        <div class="dimension-section">
            <h3><span class="cognitive">C</span> 인지적 페르소나 (Cognitive)</h3>
            <div class="persona-grid" id="cognitiveGrid">
                <?php foreach ($agentConfig['cognitive'] as $code => $info): ?>
                <div class="persona-item <?= $code === 'C1' ? 'active' : '' ?>" data-code="<?= $code ?>" data-type="cognitive" onclick="selectPersona('cognitive', '<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="code"><?= $code ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dimension-section">
            <h3><span class="behavioral">B</span> 행동적 페르소나 (Behavioral)</h3>
            <div class="persona-grid" id="behavioralGrid">
                <?php foreach ($agentConfig['behavioral'] as $code => $info): ?>
                <div class="persona-item <?= $code === 'B1' ? 'active' : '' ?>" data-code="<?= $code ?>" data-type="behavioral" onclick="selectPersona('behavioral', '<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="code"><?= $code ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dimension-section">
            <h3><span class="emotional">E</span> 감정적 페르소나 (Emotional)</h3>
            <div class="persona-grid" id="emotionalGrid">
                <?php foreach ($agentConfig['emotional'] as $code => $info): ?>
                <div class="persona-item <?= $code === 'E1' ? 'active' : '' ?>" data-code="<?= $code ?>" data-type="emotional" onclick="selectPersona('emotional', '<?= $code ?>')">
                    <div class="icon"><?= $info['icon'] ?></div>
                    <div class="name"><?= $info['name'] ?></div>
                    <div class="code"><?= $code ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="composite-display">
            <div class="composite-code" id="compositeCode">C1-B1-E1</div>
            <div class="composite-desc">현재 선택된 3차원 복합 페르소나 코드</div>
        </div>

        <div class="chat-container">
            <div class="chat-messages" id="chatMessages">
                <div class="message agent">
                    <div class="message-avatar">🎨</div>
                    <div class="message-content">안녕하세요! 상호작용 콘텐츠 에이전트입니다. C×B×E 3차원 페르소나를 선택해주세요.</div>
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
            try { $test = new Agent19ChatPersonaTest($agentConfig); $test->runAllTests(); $test->renderHtml(); }
            catch (Throwable $e) { echo "<div style='color:red;'>오류: " . htmlspecialchars($e->getMessage()) . "</div>"; }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    let currentC = 'C1', currentB = 'B1', currentE = 'E1';
    const config = <?= json_encode($agentConfig, JSON_UNESCAPED_UNICODE) ?>;

    function selectPersona(type, code) {
        const gridId = type + 'Grid';
        document.querySelectorAll(`#${gridId} .persona-item`).forEach(i => i.classList.remove('active'));
        document.querySelector(`#${gridId} .persona-item[data-code="${code}"]`).classList.add('active');
        if (type === 'cognitive') currentC = code;
        else if (type === 'behavioral') currentB = code;
        else currentE = code;
        updateCompositeCode();
    }

    function updateCompositeCode() {
        document.getElementById('compositeCode').textContent = `${currentC}-${currentB}-${currentE}`;
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
            body: `action=send_message&message=${encodeURIComponent(message)}&c_code=${currentC}&b_code=${currentB}&e_code=${currentE}`
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
        div.innerHTML = `<div class="message-avatar">${type === 'agent' ? '🎨' : '👤'}</div><div class="message-content">${text}</div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }

    function addAgentMessage(r) {
        const container = document.getElementById('chatMessages');
        const div = document.createElement('div');
        div.className = 'message agent';
        div.innerHTML = `<div class="message-avatar">🎨</div><div class="message-content">${r.message}<div class="persona-tags"><span class="persona-tag cognitive">${r.cognitive.icon} ${r.cognitive.name}</span><span class="persona-tag behavioral">${r.behavioral.icon} ${r.behavioral.name}</span><span class="persona-tag emotional">${r.emotional.icon} ${r.emotional.name}</span></div></div>`;
        container.appendChild(div);
        container.scrollTop = container.scrollHeight;
    }
    </script>
</body>
</html>
