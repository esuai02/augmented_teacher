<?php
/**
 * test_chat.php
 *
 * Agent04 Inspect Weakpoints - 채팅 테스트 및 진단 결과 통합 페이지
 * 탭으로 채팅 테스트와 시스템 진단을 전환하여 볼 수 있음
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent04InspectWeakpoints
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/test_chat.php
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
$agentNumber = 4;
$agentName = 'inspect_weakpoints';
$agentKrName = '취약점 점검';
$agentEmoji = '🔍';

$currentFile = __FILE__;
$basePath = dirname($currentFile);
$baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system';

// 컨텍스트 코드 정의 (취약점 유형 기반)
$contextCodes = [
    'W_CONCEPT' => ['name' => '개념 부족', 'color' => '#e74c3c'],
    'W_CALCULATION' => ['name' => '계산 실수', 'color' => '#f39c12'],
    'W_PATTERN' => ['name' => '문제유형 취약', 'color' => '#9b59b6'],
    'W_TIME' => ['name' => '시간관리 미흡', 'color' => '#3498db'],
    'W_FOCUS' => ['name' => '집중력 저하', 'color' => '#1abc9c'],
    'W_APPLICATION' => ['name' => '응용력 부족', 'color' => '#34495e'],
    'W_MIXED' => ['name' => '복합 취약점', 'color' => '#7f8c8d']
];

// 빠른 메시지 정의
$quickMessages = [
    ['text' => '자꾸 같은 유형에서 틀려요', 'label' => '🔄 반복 오류'],
    ['text' => '개념은 아는데 문제에 적용이 안 돼요', 'label' => '📚 응용 어려움'],
    ['text' => '계산 실수가 너무 많아요', 'label' => '🔢 계산 실수'],
    ['text' => '시간이 부족해서 다 못 풀어요', 'label' => '⏱️ 시간 부족'],
    ['text' => '어디가 취약한지 모르겠어요', 'label' => '❓ 진단 요청'],
    ['text' => '분수 계산이 자꾸 틀려요', 'label' => '📐 분수 오류'],
    ['text' => '문장제 문제가 어려워요', 'label' => '📝 문장제'],
    ['text' => '공부할 때 집중이 안 돼요', 'label' => '🎯 집중력']
];

// 탭 선택
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// =========================================================================
// 진단 테스트 클래스
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

class Agent04PersonaTest extends BasePersonaTest
{
    public function __construct()
    {
        parent::__construct(4, 'inspect_weakpoints', '취약점 점검', __DIR__);
    }

    protected function getRequiredFiles(): array
    {
        return [
            'Agent04PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'api.php' => 'API 엔드포인트',
            'config.php' => '설정 파일'
        ];
    }

    protected function getRequiredTables(): array
    {
        return [
            'mdl_at_weakpoint_analysis' => '취약점 분석 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 테스트
        $this->testPersonaEngineLoad();

        // 2. Config 로드 테스트
        $this->testConfigLoad();

        // 3. 취약점 유형 정의 테스트
        $this->testWeakpointTypes();

        // 4. 활동 도메인 테스트
        $this->testActivityDomains();
    }

    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/Agent04PersonaEngine.php';
        if (!file_exists($filePath)) {
            $this->recordTest('PersonaEngine 클래스', false, '파일 없음: Agent04PersonaEngine.php [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }
        $content = file_get_contents($filePath);
        $hasClass = strpos($content, 'class Agent04PersonaEngine') !== false;
        $this->recordTest('PersonaEngine 클래스', $hasClass, $hasClass ? 'Agent04PersonaEngine 클래스 발견' : '클래스 정의 없음');
    }

    private function testConfigLoad(): void
    {
        $configPath = __DIR__ . '/config.php';
        if (!file_exists($configPath)) {
            $this->recordTest('설정 파일', false, '파일 없음: config.php [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }

        $config = require($configPath);
        $hasAgentId = !empty($config['agent']['id']);
        $this->recordTest('설정 파일', $hasAgentId, $hasAgentId ? 'Agent ID: ' . $config['agent']['id'] : 'agent.id 없음');
    }

    private function testWeakpointTypes(): void
    {
        $configPath = __DIR__ . '/config.php';
        if (!file_exists($configPath)) {
            $this->recordTest('취약점 유형', false, 'config.php 없음 [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }

        $config = require($configPath);
        $types = $config['weakpoint_types'] ?? [];
        $hasTypes = count($types) > 0;
        $this->recordTest('취약점 유형', $hasTypes, $hasTypes ? count($types) . '개 취약점 유형 정의됨' : '취약점 유형 정의 없음');
    }

    private function testActivityDomains(): void
    {
        $configPath = __DIR__ . '/config.php';
        if (!file_exists($configPath)) {
            $this->recordTest('활동 도메인', false, 'config.php 없음 [' . __FILE__ . ':' . __LINE__ . ']');
            return;
        }

        $config = require($configPath);
        $domains = $config['activity_domains'] ?? [];
        $hasDomains = count($domains) > 0;
        $this->recordTest('활동 도메인', $hasDomains, $hasDomains ? count($domains) . '개 활동 도메인 정의됨' : '활동 도메인 정의 없음');
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
        .header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 20px; }
        .header h1 { font-size: 1.5rem; margin-bottom: 5px; }
        .header .subtitle { opacity: 0.9; font-size: 0.9rem; }
        .tabs { display: flex; background: white; border-bottom: 1px solid #e0e0e0; }
        .tab { padding: 15px 30px; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab:hover { background: #f5f5f5; }
        .tab.active { border-bottom-color: #e74c3c; color: #e74c3c; font-weight: bold; }
        .content { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .panel { background: white; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .hidden { display: none; }
        .context-badges { margin-bottom: 20px; }
        .badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; margin: 3px; color: white; }
        .quick-messages { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 15px; }
        .quick-msg { background: #f0f0f0; border: 1px solid #ddd; padding: 8px 12px; border-radius: 20px; cursor: pointer; font-size: 13px; transition: all 0.2s; }
        .quick-msg:hover { background: #e74c3c; color: white; border-color: #e74c3c; }
        label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
        textarea, select, input[type="text"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; font-size: 14px; }
        textarea { min-height: 100px; resize: vertical; }
        button { background: #e74c3c; color: white; border: none; padding: 12px 24px; border-radius: 6px; cursor: pointer; font-size: 14px; margin-right: 10px; margin-bottom: 10px; transition: background 0.3s; }
        button:hover { background: #c0392b; }
        button.secondary { background: #6c757d; }
        button.secondary:hover { background: #5a6268; }
        .result { background: #f8f9fa; border-radius: 6px; padding: 15px; font-family: 'Consolas', monospace; font-size: 13px; white-space: pre-wrap; word-break: break-word; max-height: 400px; overflow-y: auto; border-left: 4px solid #e74c3c; }
        .chat-container { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        @media (max-width: 768px) { .chat-container { grid-template-columns: 1fr; } }
        .response-box { background: #fce4e4; padding: 15px; border-radius: 8px; margin-top: 15px; }
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
        .toggle-btn.active { border-color: #e74c3c; background: #e74c3c; color: white; }
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
        <!-- 채팅 테스트 탭 -->
        <div id="chatTab" class="<?php echo $activeTab !== 'chat' ? 'hidden' : ''; ?>">
            <div class="panel">
                <h2>📊 컨텍스트 코드 (취약점 유형)</h2>
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
                    <textarea id="message" placeholder="취약점 관련 메시지를 입력하세요..."><?php echo $quickMessages[0]['text']; ?></textarea>

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
                        <?php echo $baseUrl; ?>/api.php
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
                $test = new Agent04PersonaTest();
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
            const response = await fetch('./api.php', {
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
            const response = await fetch('./api.php?test=1');
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
            const response = await fetch('./api.php?info=1');
            const data = await response.json();
            resultDiv.textContent = JSON.stringify(data, null, 2);
        } catch (error) {
            resultDiv.textContent = '요청 실패: ' + error.message;
        }
    }
    </script>
</body>
</html>
