<?php
/**
 * Agent10 개념노트 페르소나 시스템 테스트 페이지
 *
 * 페르소나 시스템의 각 기능을 수동으로 테스트할 수 있는 UI 제공
 *
 * @package AugmentedTeacher\Agents\Agent10\PersonaSystem
 * @version 1.0
 * @created 2025-12-02
 */

// 현재 파일 정보
define('AGENT10_TEST_FILE', __FILE__);
define('AGENT10_TEST_DIR', __DIR__);

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 엔진 및 설정 로드
require_once(AGENT10_TEST_DIR . '/engine/Agent10PersonaEngine.php');
$config = require(AGENT10_TEST_DIR . '/engine/config/agent_config.php');

$testResult = null;
$errorMessage = null;

// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $message = trim($_POST['message'] ?? '');
        $situation = strtoupper($_POST['situation'] ?? 'N1');
        $userId = (int)($_POST['user_id'] ?? ($USER->id ?? 1));
        $noteId = isset($_POST['note_id']) && $_POST['note_id'] !== '' ? (int)$_POST['note_id'] : null;
        $aiEnabled = isset($_POST['ai_enabled']);
        $debugMode = isset($_POST['debug_mode']);

        if (empty($message)) {
            throw new Exception("메시지를 입력해주세요. (파일: " . AGENT10_TEST_FILE . ", 라인: " . __LINE__ . ")");
        }

        // 세션 데이터 구성
        $sessionData = [
            'current_situation' => $situation,
            'note_id' => $noteId,
            'request_source' => 'test_page'
        ];

        // 엔진 초기화
        $engineConfig = array_merge($config, [
            'ai_enabled' => $aiEnabled,
            'debug_mode' => $debugMode
        ]);

        $engine = new Agent10PersonaEngine($engineConfig);

        // 규칙 로드
        $rulesPath = AGENT10_TEST_DIR . '/rules.yaml';
        if (file_exists($rulesPath)) {
            $engine->loadRules($rulesPath);
        }

        // 프로세스 실행
        $testResult = $engine->process($userId, $message, $sessionData);

    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

// 사용 가능한 상황 코드
$situations = [
    'N1' => '노트 탐색 시작 - 노트 현황 파악 및 탐색 안내',
    'N2' => '개념 이해도 분석 - 노트 내용 깊이 평가',
    'N3' => '학습 흐름 해석 - 노트 작성 패턴 분석',
    'N4' => '복습 권장 판단 - 오래된 노트 복습 유도',
    'N5' => '노트 활용 전략 - 효과적 노트 활용법 제안'
];

// 테스트 시나리오 예시
$testScenarios = [
    ['message' => '내 노트 현황 보여줘', 'situation' => 'N1', 'desc' => '노트 탐색 시작'],
    ['message' => '이 개념 노트 어떻게 이해하면 좋을까?', 'situation' => 'N2', 'desc' => '개념 이해도 분석'],
    ['message' => '내 학습 패턴이 어때?', 'situation' => 'N3', 'desc' => '학습 흐름 해석'],
    ['message' => '복습해야 할 노트가 있어?', 'situation' => 'N4', 'desc' => '복습 권장 판단'],
    ['message' => '노트를 더 잘 활용하려면?', 'situation' => 'N5', 'desc' => '노트 활용 전략']
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent10 개념노트 페르소나 테스트</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        h2 {
            color: #34495e;
            margin-top: 30px;
        }
        .container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        .panel {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
        }
        input[type="text"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        textarea {
            height: 100px;
            resize: vertical;
        }
        button {
            background: #3498db;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
            margin-top: 10px;
        }
        button:hover {
            background: #2980b9;
        }
        button.secondary {
            background: #95a5a6;
        }
        button.secondary:hover {
            background: #7f8c8d;
        }
        .checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: normal;
        }
        .result-panel {
            grid-column: 1 / -1;
        }
        .result {
            background: #ecf0f1;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
            white-space: pre-wrap;
            font-family: 'Consolas', monospace;
            font-size: 13px;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            border-left: 4px solid #27ae60;
        }
        .error {
            border-left: 4px solid #e74c3c;
            background: #fce4ec;
        }
        .scenarios {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }
        .scenario-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            border-radius: 4px;
            cursor: pointer;
            text-align: left;
            transition: all 0.2s;
        }
        .scenario-btn:hover {
            background: #e3f2fd;
            border-color: #3498db;
        }
        .scenario-btn strong {
            display: block;
            color: #3498db;
            margin-bottom: 5px;
        }
        .scenario-btn small {
            color: #666;
        }
        .info-box {
            background: #e3f2fd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box h3 {
            margin: 0 0 10px 0;
            color: #1976d2;
        }
        .api-url {
            background: #263238;
            color: #80cbc4;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
        }
        .meta-info {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        .meta-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            text-align: center;
        }
        .meta-item strong {
            display: block;
            color: #3498db;
            font-size: 1.2em;
        }
    </style>
</head>
<body>
    <h1>🎯 Agent10 개념노트 페르소나 테스트</h1>

    <div class="info-box">
        <h3>API 엔드포인트</h3>
        <div class="api-url">
            POST https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent10_concept_notes/persona_system/api/chat.php
        </div>
        <p style="margin-top:10px; color:#666;">
            현재 파일: <?php echo AGENT10_TEST_FILE; ?>
        </p>
    </div>

    <div class="container">
        <div class="panel">
            <h2>📝 테스트 입력</h2>
            <form method="POST" id="testForm">
                <div class="form-group">
                    <label for="message">메시지 *</label>
                    <textarea name="message" id="message" placeholder="예: 내 노트 현황을 알려줘" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="situation">상황 코드</label>
                    <select name="situation" id="situation">
                        <?php foreach ($situations as $code => $desc): ?>
                        <option value="<?php echo $code; ?>" <?php echo (($_POST['situation'] ?? 'N1') === $code) ? 'selected' : ''; ?>>
                            <?php echo $code; ?> - <?php echo $desc; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="user_id">사용자 ID</label>
                    <input type="number" name="user_id" id="user_id"
                           value="<?php echo htmlspecialchars($_POST['user_id'] ?? ($USER->id ?? 1)); ?>">
                </div>

                <div class="form-group">
                    <label for="note_id">노트 ID (선택)</label>
                    <input type="number" name="note_id" id="note_id"
                           value="<?php echo htmlspecialchars($_POST['note_id'] ?? ''); ?>"
                           placeholder="특정 노트 분석시 입력">
                </div>

                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" name="ai_enabled" <?php echo isset($_POST['ai_enabled']) ? 'checked' : ''; ?>>
                        AI 응답 활성화
                    </label>
                    <label>
                        <input type="checkbox" name="debug_mode" <?php echo isset($_POST['debug_mode']) ? 'checked' : ''; ?>>
                        디버그 모드
                    </label>
                </div>

                <button type="submit">🚀 테스트 실행</button>
                <button type="button" class="secondary" onclick="document.getElementById('testForm').reset();">초기화</button>
            </form>
        </div>

        <div class="panel">
            <h2>📋 테스트 시나리오</h2>
            <p>아래 버튼을 클릭하면 해당 시나리오가 자동으로 입력됩니다.</p>
            <div class="scenarios">
                <?php foreach ($testScenarios as $scenario): ?>
                <div class="scenario-btn" onclick="loadScenario('<?php echo htmlspecialchars($scenario['message']); ?>', '<?php echo $scenario['situation']; ?>')">
                    <strong>[<?php echo $scenario['situation']; ?>] <?php echo $scenario['desc']; ?></strong>
                    <small><?php echo $scenario['message']; ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <h3 style="margin-top:20px;">📊 상황별 페르소나</h3>
            <table style="width:100%; border-collapse:collapse; margin-top:10px;">
                <tr style="background:#f8f9fa;">
                    <th style="padding:8px; text-align:left; border:1px solid #ddd;">상황</th>
                    <th style="padding:8px; text-align:left; border:1px solid #ddd;">페르소나 (4개)</th>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;">N1</td>
                    <td style="padding:8px; border:1px solid #ddd;">P1:친근한가이드, P2:효율분석가, P3:격려전문가, P4:전략가</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;">N2</td>
                    <td style="padding:8px; border:1px solid #ddd;">P1:깊이이해자, P2:연결해석자, P3:질문유도자, P4:구조설계자</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;">N3</td>
                    <td style="padding:8px; border:1px solid #ddd;">P1:패턴분석가, P2:리듬코치, P3:집중전문가, P4:시간설계자</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;">N4</td>
                    <td style="padding:8px; border:1px solid #ddd;">P1:기억보조자, P2:우선순위자, P3:복습동기부여자, P4:간격반복코치</td>
                </tr>
                <tr>
                    <td style="padding:8px; border:1px solid #ddd;">N5</td>
                    <td style="padding:8px; border:1px solid #ddd;">P1:활용최적화자, P2:연결전문가, P3:확장안내자, P4:통합설계자</td>
                </tr>
            </table>
        </div>

        <?php if ($testResult !== null || $errorMessage !== null): ?>
        <div class="panel result-panel">
            <h2><?php echo $errorMessage ? '❌ 오류 발생' : '✅ 테스트 결과'; ?></h2>

            <?php if ($errorMessage): ?>
            <div class="result error"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php else: ?>

            <div class="meta-info">
                <div class="meta-item">
                    <strong><?php echo htmlspecialchars($testResult['persona']['persona_id'] ?? 'N/A'); ?></strong>
                    <span>페르소나 ID</span>
                </div>
                <div class="meta-item">
                    <strong><?php echo htmlspecialchars($testResult['persona']['persona_name'] ?? 'N/A'); ?></strong>
                    <span>페르소나 이름</span>
                </div>
                <div class="meta-item">
                    <strong><?php echo isset($testResult['meta']['processing_time_ms']) ? round($testResult['meta']['processing_time_ms'], 2) . 'ms' : 'N/A'; ?></strong>
                    <span>처리 시간</span>
                </div>
            </div>

            <h3>💬 응답 메시지</h3>
            <div class="result success">
<?php echo htmlspecialchars($testResult['response']['text'] ?? '응답 없음'); ?>
            </div>

            <h3>📦 전체 응답 데이터</h3>
            <div class="result">
<?php echo htmlspecialchars(json_encode($testResult, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)); ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    function loadScenario(message, situation) {
        document.getElementById('message').value = message;
        document.getElementById('situation').value = situation;
    }
    </script>

    <!--
    파일: agent10_concept_notes/persona_system/test.php
    용도: 페르소나 시스템 수동 테스트 UI

    관련 DB 테이블:
    - local_augteacher_notes
      - id: bigint(10) PRIMARY KEY
      - userid: bigint(10) NOT NULL
      - title: varchar(255)
      - topic: varchar(100)
      - nstroke: int(10) - 필기 획 수
      - tlaststroke: bigint(10) - 마지막 필기 시간
      - usedtime: int(10) - 사용 시간 (초)

    - at_agent_persona_state
      - id: bigint(10) PRIMARY KEY
      - userid: bigint(10) NOT NULL
      - agent_id: varchar(50) NOT NULL
      - persona_id: varchar(50) NOT NULL
      - state_data: longtext
    -->
</body>
</html>
