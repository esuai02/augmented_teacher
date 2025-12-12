<?php
/**
 * test_chat.php
 *
 * Agent05 Learning Emotion - 채팅 테스트 및 진단 결과 통합 페이지
 * 탭으로 채팅 테스트와 시스템 진단을 전환하여 볼 수 있음
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent05LearningEmotion
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/test_chat.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// =========================================================================
// 에이전트 설정
// =========================================================================
$agentNumber = 5;
$agentName = 'learning_emotion';
$agentKrName = '학습 감정';
$agentEmoji = '🎭';

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

$currentFile = __FILE__;
$basePath = dirname($currentFile);
$baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system';

// 컨텍스트 코드 정의 (감정 상태 기반)
$contextCodes = [
    'E_CONFIDENT' => ['name' => '자신감', 'color' => '#27ae60'],
    'E_ANXIOUS' => ['name' => '불안', 'color' => '#e74c3c'],
    'E_FRUSTRATED' => ['name' => '좌절', 'color' => '#c0392b'],
    'E_MOTIVATED' => ['name' => '동기부여', 'color' => '#f39c12'],
    'E_BORED' => ['name' => '지루함', 'color' => '#7f8c8d'],
    'E_CURIOUS' => ['name' => '호기심', 'color' => '#3498db'],
    'E_OVERWHELMED' => ['name' => '압도감', 'color' => '#9b59b6'],
    'E_NEUTRAL' => ['name' => '중립', 'color' => '#34495e']
];

// 빠른 메시지 정의
$quickMessages = [
    ['text' => '수학 공부하기 싫어요', 'label' => '😔 싫음'],
    ['text' => '자신감이 없어요', 'label' => '😰 자신감 저하'],
    ['text' => '공부하면 불안해져요', 'label' => '😟 불안'],
    ['text' => '이번에는 잘 할 수 있을 것 같아요', 'label' => '😊 긍정'],
    ['text' => '수학이 너무 어려워서 포기하고 싶어요', 'label' => '😢 좌절'],
    ['text' => '문제 풀 때 재밌어요', 'label' => '🎯 흥미'],
    ['text' => '왜 수학을 공부해야 하는지 모르겠어요', 'label' => '❓ 의미'],
    ['text' => '시험이 다가오니까 긴장돼요', 'label' => '😬 긴장']
];

// 탭 선택
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// =========================================================================
// 진단 테스트 클래스
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

class Agent05PersonaTest extends BasePersonaTest
{
    public function __construct()
    {
        parent::__construct(5, 'learning_emotion', '학습 감정', __DIR__);
    }

    protected function getRequiredFiles(): array
    {
        return [
            'engine/Agent05PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'engine/Agent05DataContext.php' => 'DataContext 데이터 접근 클래스',
            'engine/EmotionAnalyzer.php' => '감정 분석기 클래스',
            'engine/LearningActivityDetector.php' => '학습 활동 감지기'
        ];
    }

    protected function getRequiredTables(): array
    {
        return [
            'mdl_at_learning_emotion_log' => '감정 로그 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 테스트
        $this->testPersonaEngineLoad();

        // 2. DataContext 클래스 테스트
        $this->testDataContextLoad();

        // 3. 감정 분석기 테스트
        $this->testEmotionAnalyzer();

        // 4. 감정 키워드 매핑 테스트
        $this->testEmotionKeywords();
    }

    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent05PersonaEngine.php';
        if (!file_exists($filePath)) {
            $this->recordTest('PersonaEngine 클래스', false, '파일 없음: engine/Agent05PersonaEngine.php [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }
        $content = file_get_contents($filePath);
        $hasClass = strpos($content, 'class Agent05PersonaEngine') !== false;
        $this->recordTest('PersonaEngine 클래스', $hasClass, $hasClass ? 'Agent05PersonaEngine 클래스 발견' : '클래스 정의 없음');
    }

    private function testDataContextLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent05DataContext.php';
        if (!file_exists($filePath)) {
            $this->recordTest('DataContext 클래스', false, '파일 없음: engine/Agent05DataContext.php [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }
        $content = file_get_contents($filePath);
        $hasClass = strpos($content, 'class Agent05DataContext') !== false;
        $this->recordTest('DataContext 클래스', $hasClass, $hasClass ? 'Agent05DataContext 클래스 발견' : '클래스 정의 없음');
    }

    private function testEmotionAnalyzer(): void
    {
        $filePath = __DIR__ . '/engine/EmotionAnalyzer.php';
        if (!file_exists($filePath)) {
            $this->recordTest('감정 분석기', false, '파일 없음: engine/EmotionAnalyzer.php [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }
        $content = file_get_contents($filePath);
        $hasClass = strpos($content, 'class EmotionAnalyzer') !== false || strpos($content, 'class Agent05EmotionAnalyzer') !== false;
        $this->recordTest('감정 분석기', $hasClass, $hasClass ? 'EmotionAnalyzer 클래스 발견' : '클래스 정의 없음');
    }

    private function testEmotionKeywords(): void
    {
        $emotionKeywords = [
            'E_ANXIOUS' => ['불안', '걱정', '긴장'],
            'E_FRUSTRATED' => ['포기', '힘들', '어려워'],
            'E_CONFIDENT' => ['자신', '할 수 있', '잘']
        ];

        $testCases = [
            ['text' => '너무 불안해요', 'expected' => 'E_ANXIOUS'],
            ['text' => '포기하고 싶어요', 'expected' => 'E_FRUSTRATED'],
            ['text' => '잘 할 수 있을 것 같아요', 'expected' => 'E_CONFIDENT']
        ];

        $allPassed = true;
        foreach ($testCases as $case) {
            $detected = $this->detectEmotion($case['text'], $emotionKeywords);
            if ($detected !== $case['expected']) {
                $allPassed = false;
            }
        }
        $this->recordTest('감정 키워드 매핑', $allPassed, $allPassed ? '감정 키워드 매핑 정상' : '키워드 매핑 오류');
    }

    private function detectEmotion($text, $keywords): string
    {
        foreach ($keywords as $emotion => $words) {
            foreach ($words as $word) {
                if (strpos($text, $word) !== false) {
                    return $emotion;
                }
            }
        }
        return 'E_NEUTRAL';
    }
}

// =========================================================================
// HTML 출력 시작
// =========================================================================
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?> - 테스트</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f5f7fa; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%); color: white; padding: 20px; }
        .header h1 { font-size: 1.5rem; margin-bottom: 5px; }
        .header .subtitle { opacity: 0.9; font-size: 0.9rem; }
        .tabs { display: flex; background: white; border-bottom: 1px solid #e0e0e0; }
        .tab { padding: 15px 30px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab:hover { background: #f5f5f5; }
        .tab.active { border-bottom-color: #9b59b6; color: #9b59b6; font-weight: bold; }
        .content { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .panel { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .hidden { display: none; }
        .context-badges { margin-bottom: 20px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; margin: 3px; color: white; }
        .quick-messages { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; }
        .quick-msg { background: #f0f0f0; border: 1px solid #ddd; padding: 8px 12px; border-radius: 20px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .quick-msg:hover { background: #9b59b6; color: white; border-color: #9b59b6; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        textarea, select, input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        textarea { min-height: 100px; resize: vertical; }
        button { background: #9b59b6; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 10px; margin-bottom: 10px; transition: background 0.3s; }
        button:hover { background: #8e44ad; }
        button.secondary { background: #6c757d; }
        button.secondary:hover { background: #5a6268; }
        .result { background: #f8f9fa; border-radius: 6px; padding: 15px; font-family: 'Consolas', monospace; font-size: 13px; white-space: pre-wrap; word-break: break-word; max-height: 400px; overflow-y: auto; border-left: 4px solid #9b59b6; }
        .chat-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .chat-container { grid-template-columns: 1fr; } }
        .response-box { background: #f3e5f5; padding: 15px; border-radius: 8px; margin-top: 15px; }
        .test-summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .summary-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .summary-card .number { font-size: 2rem; font-weight: bold; }
        .summary-card .label { font-size: 0.85rem; color: #666; }
        .summary-card.success .number { color: #28a745; }
        .summary-card.fail .number { color: #dc3545; }
        .test-item { display: flex; align-items: center; padding: 10px; background: #f9f9f9; border-radius: 6px; margin-bottom: 8px; }
        .test-item .icon { font-size: 1.2rem; margin-right: 12px; }
        .test-item .name { flex: 1; font-weight: 500; }
        .test-item.pass { border-left: 4px solid #28a745; }
        .test-item.fail { border-left: 4px solid #dc3545; }
        .toggle-group { display: flex; gap: 10px; margin-bottom: 15px; }
        .toggle-btn { padding: 8px 16px; border: 2px solid #ddd; background: white; border-radius: 6px; cursor: pointer; }
        .toggle-btn.active { border-color: #9b59b6; background: #9b59b6; color: white; }
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
        <h1><?php echo $agentEmoji; ?> Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?> - 페르소나 시스템 테스트</h1>
        <div class="subtitle"><?php echo $baseUrl; ?>/test_chat.php</div>
    </div>

    <div class="tabs">
        <div class="tab <?php echo $activeTab === 'chat' ? 'active' : ''; ?>" onclick="location.href='?tab=chat'">💬 채팅 테스트</div>
        <div class="tab <?php echo $activeTab === 'diagnosis' ? 'active' : ''; ?>" onclick="location.href='?tab=diagnosis'">🔍 진단 결과</div>
    </div>

    <div class="content">
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

        <!-- 채팅 테스트 탭 -->
        <div id="chatTab" class="<?php echo $activeTab !== 'chat' ? 'hidden' : ''; ?>">
            <div class="panel">
                <h2>📊 컨텍스트 코드 (감정 상태)</h2>
                <div class="context-badges">
                    <?php foreach ($contextCodes as $code => $info): ?>
                        <span class="badge" style="background: <?php echo $info['color']; ?>"><?php echo $code; ?>: <?php echo $info['name']; ?></span>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chat-container">
                <div class="panel">
                    <h2>💬 채팅 테스트</h2>

                    <label>빠른 메시지:</label>
                    <div class="quick-messages">
                        <?php foreach ($quickMessages as $msg): ?>
                            <span class="quick-msg" onclick="setMessage('<?php echo addslashes($msg['text']); ?>')"><?php echo $msg['label']; ?></span>
                        <?php endforeach; ?>
                    </div>

                    <label for="message">메시지:</label>
                    <textarea id="message" placeholder="감정 관련 메시지를 입력하세요..."><?php echo $quickMessages[0]['text']; ?></textarea>

                    <label for="context">컨텍스트:</label>
                    <select id="context">
                        <option value="">자동 감지</option>
                        <?php foreach ($contextCodes as $code => $info): ?>
                            <option value="<?php echo $code; ?>"><?php echo $code; ?>: <?php echo $info['name']; ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="userId">사용자 ID:</label>
                    <input type="text" id="userId" value="<?php echo isset($USER->id) ? $USER->id : 1; ?>">

                    <div class="toggle-group">
                        <span class="toggle-btn active" onclick="toggleAI(this, true)">🤖 AI 응답</span>
                        <span class="toggle-btn" onclick="toggleAI(this, false)">📋 템플릿만</span>
                    </div>

                    <button onclick="sendChat()">📤 메시지 전송</button>
                    <button class="secondary" onclick="clearResult('chatResult')">🗑️ 지우기</button>

                    <h3>응답:</h3>
                    <div id="chatResult" class="result">응답이 여기에 표시됩니다...</div>
                </div>

                <div class="panel">
                    <h2>📡 API 정보</h2>
                    <p><strong>엔드포인트:</strong></p>
                    <code style="display:block; background:#2c3e50; color:#ecf0f1; padding:10px; border-radius:4px; margin:10px 0; word-break:break-all;">
                        <?php echo $baseUrl; ?>/api/chat.php
                    </code>

                    <button onclick="testApiGet()">🔍 API GET 테스트</button>
                    <button class="secondary" onclick="getApiInfo()">📋 API 정보</button>

                    <h3>결과:</h3>
                    <div id="apiResult" class="result">API 테스트 결과가 여기에 표시됩니다...</div>
                </div>
            </div>
        </div>

        <!-- 진단 결과 탭 -->
        <div id="diagnosisTab" class="<?php echo $activeTab !== 'diagnosis' ? 'hidden' : ''; ?>">
            <?php
            if ($activeTab === 'diagnosis') {
                $test = new Agent05PersonaTest();
                $test->runAllTests();
                $summary = $test->getSummary();
            ?>
            <div class="test-summary">
                <div class="summary-card">
                    <div class="number"><?php echo $summary['total']; ?></div>
                    <div class="label">전체 테스트</div>
                </div>
                <div class="summary-card success">
                    <div class="number"><?php echo $summary['passed']; ?></div>
                    <div class="label">성공</div>
                </div>
                <div class="summary-card fail">
                    <div class="number"><?php echo $summary['failed']; ?></div>
                    <div class="label">실패</div>
                </div>
                <div class="summary-card">
                    <div class="number"><?php echo $summary['pass_rate']; ?>%</div>
                    <div class="label">성공률</div>
                </div>
            </div>

            <?php echo $test->renderHtml(); ?>
            <?php } ?>
        </div>
    </div>

    <script>
    let useAI = true;

    function setMessage(text) {
        document.getElementById('message').value = text;
    }

    function toggleAI(btn, value) {
        useAI = value;
        document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    function clearResult(id) {
        document.getElementById(id).textContent = '';
    }

    async function sendChat() {
        const message = document.getElementById('message').value;
        const context = document.getElementById('context').value;
        const userId = document.getElementById('userId').value;
        const resultDiv = document.getElementById('chatResult');

        if (!message.trim()) {
            resultDiv.textContent = '메시지를 입력하세요.';
            return;
        }

        resultDiv.textContent = '전송 중...';

        try {
            const response = await fetch('./api/chat.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    message: message,
                    context: context || null,
                    user_id: userId,
                    use_ai: useAI
                })
            });

            const data = await response.json();

            if (data.success) {
                let output = '=== 응답 ===\n';
                output += data.response?.message || data.response || '(응답 없음)';
                output += '\n\n=== 메타 정보 ===\n';
                output += JSON.stringify(data.meta || data, null, 2);
                resultDiv.textContent = output;
            } else {
                resultDiv.textContent = '오류: ' + (data.error || JSON.stringify(data));
            }
        } catch (error) {
            resultDiv.textContent = '요청 실패: ' + error.message + ' [test_chat.php:sendChat]';
        }
    }

    async function testApiGet() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.textContent = '테스트 중...';

        try {
            const response = await fetch('./api/chat.php?test=1');
            const data = await response.json();
            resultDiv.textContent = JSON.stringify(data, null, 2);
        } catch (error) {
            resultDiv.textContent = '요청 실패: ' + error.message;
        }
    }

    async function getApiInfo() {
        const resultDiv = document.getElementById('apiResult');
        resultDiv.textContent = '정보 조회 중...';

        try {
            const response = await fetch('./api/chat.php?info=1');
            const data = await response.json();
            resultDiv.textContent = JSON.stringify(data, null, 2);
        } catch (error) {
            resultDiv.textContent = '요청 실패: ' + error.message;
        }
    }
    </script>
</body>
</html>
