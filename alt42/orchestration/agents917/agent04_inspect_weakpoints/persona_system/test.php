<?php
/**
 * Agent04 인지관성 페르소나 시스템 테스트 페이지
 *
 * 60개 인지관성 페르소나 엔진 및 API 기능을 브라우저에서 테스트
 *
 * @package AugmentedTeacher\Agent04\PersonaSystem
 * @version 2.0.0
 * @created 2025-12-03
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/persona_system/test.php
 */

// 에러 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 파일 경로 상수 정의
define('CURRENT_FILE', __FILE__);
define('CURRENT_LINE', __LINE__);

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 로그인 확인
require_login();
$context = context_system::instance();

// 설정 로드
$config = require(__DIR__ . '/config.php');

// 테스트 결과 저장
$testResults = [];
$overallSuccess = true;
$totalTime = 0;

/**
 * 테스트 실행 함수
 */
function runTest($name, $callback) {
    global $testResults, $overallSuccess, $totalTime;

    $start = microtime(true);

    try {
        $result = $callback();
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        $totalTime += $elapsed;

        $testResults[] = [
            'name' => $name,
            'success' => $result['success'],
            'message' => $result['message'],
            'elapsed_ms' => $elapsed,
            'details' => $result['details'] ?? null
        ];

        if (!$result['success']) {
            $overallSuccess = false;
        }
    } catch (Exception $e) {
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        $totalTime += $elapsed;
        $testResults[] = [
            'name' => $name,
            'success' => false,
            'message' => '예외 발생: ' . $e->getMessage(),
            'elapsed_ms' => $elapsed,
            'details' => [
                'exception' => get_class($e),
                'file' => basename($e->getFile()),
                'line' => $e->getLine()
            ]
        ];
        $overallSuccess = false;
    }
}

// ==========================================
// 테스트 1: 설정 파일 로드
// ==========================================
runTest('설정 파일 로드', function() {
    global $config;

    if (empty($config)) {
        return [
            'success' => false,
            'message' => 'config.php 로드 실패 [' . CURRENT_FILE . ':' . __LINE__ . ']'
        ];
    }

    $personaCount = count($config['personas'] ?? []);
    $categoryCount = count($config['categories'] ?? []);

    if ($personaCount !== 60) {
        return [
            'success' => false,
            'message' => "페르소나 수 불일치: 예상 60개, 실제 {$personaCount}개",
            'details' => ['expected' => 60, 'actual' => $personaCount]
        ];
    }

    return [
        'success' => true,
        'message' => '설정 파일 로드 성공',
        'details' => [
            'personas_count' => $personaCount,
            'categories_count' => $categoryCount,
            'version' => $config['version'] ?? 'unknown',
            'audio_base_url' => $config['audio']['base_url'] ?? 'not set'
        ]
    ];
});

// ==========================================
// 테스트 2: 8개 카테고리 검증
// ==========================================
runTest('카테고리 정의 검증', function() {
    global $config;

    $expectedCategories = [
        'cognitive_overload',
        'confidence_distortion',
        'mistake_pattern',
        'approach_error',
        'learning_habit',
        'time_pressure',
        'verification_absence',
        'other_障害'
    ];

    $actualCategories = array_keys($config['categories'] ?? []);
    $missing = array_diff($expectedCategories, $actualCategories);

    if (!empty($missing)) {
        return [
            'success' => false,
            'message' => count($missing) . '개 카테고리 누락',
            'details' => ['missing' => $missing]
        ];
    }

    return [
        'success' => true,
        'message' => '8개 카테고리 모두 정의됨',
        'details' => [
            'categories' => array_map(function($cat) use ($config) {
                return $config['categories'][$cat]['name'] ?? $cat;
            }, $expectedCategories)
        ]
    ];
});

// ==========================================
// 테스트 3: Agent04PersonaEngine 클래스 로드
// ==========================================
runTest('PersonaEngine 클래스 로드', function() {
    $enginePath = __DIR__ . '/Agent04PersonaEngine.php';

    if (!file_exists($enginePath)) {
        return [
            'success' => false,
            'message' => 'Agent04PersonaEngine.php 파일 없음 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    require_once($enginePath);

    if (!class_exists('Agent04PersonaEngine')) {
        return [
            'success' => false,
            'message' => 'Agent04PersonaEngine 클래스 정의 없음'
        ];
    }

    $engine = new Agent04PersonaEngine();
    $info = $engine->getSystemInfo();

    return [
        'success' => true,
        'message' => '엔진 클래스 로드 성공',
        'details' => [
            'version' => $info['version'] ?? 'unknown',
            'persona_count' => $info['persona_count'] ?? 0,
            'category_count' => $info['category_count'] ?? 0
        ]
    ];
});

// ==========================================
// 테스트 4: 기본 분석 기능
// ==========================================
runTest('기본 분석 기능', function() {
    global $USER;

    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    $testUserId = $USER->id ?? 1;
    $testMessage = "문제 보자마자 떠오르는 방법으로 바로 풀었어요";

    $result = $engine->analyze($testUserId, $testMessage);

    if (empty($result)) {
        return [
            'success' => false,
            'message' => '분석 결과 비어있음 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    if (!isset($result['primary_match'])) {
        return [
            'success' => false,
            'message' => 'primary_match 필드 없음',
            'details' => ['result_keys' => array_keys($result)]
        ];
    }

    return [
        'success' => true,
        'message' => '분석 기능 정상 작동',
        'details' => [
            'detected_persona_id' => $result['primary_match']['id'] ?? 'unknown',
            'detected_persona_name' => $result['primary_match']['name'] ?? 'unknown',
            'score' => $result['primary_match']['score'] ?? 0,
            'category' => $result['primary_match']['category'] ?? 'unknown',
            'has_solution' => isset($result['primary_match']['solution']),
            'has_audio' => isset($result['primary_match']['audio_url'])
        ]
    ];
});

// ==========================================
// 테스트 5: 페르소나 ID로 직접 조회
// ==========================================
runTest('페르소나 직접 조회', function() {
    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    // ID 1번 페르소나 조회 (충동적 풀이)
    $persona = $engine->getPersona(1);

    if (empty($persona)) {
        return [
            'success' => false,
            'message' => 'ID 1번 페르소나 조회 실패 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    // 필수 필드 확인
    $requiredFields = ['id', 'name', 'desc', 'category', 'priority', 'solution'];
    $missingFields = [];
    foreach ($requiredFields as $field) {
        if (!isset($persona[$field])) {
            $missingFields[] = $field;
        }
    }

    if (!empty($missingFields)) {
        return [
            'success' => false,
            'message' => '필수 필드 누락: ' . implode(', ', $missingFields),
            'details' => ['missing' => $missingFields]
        ];
    }

    return [
        'success' => true,
        'message' => '페르소나 조회 성공',
        'details' => [
            'id' => $persona['id'],
            'name' => $persona['name'],
            'category' => $persona['category'],
            'priority' => $persona['priority'],
            'has_action' => !empty($persona['solution']['action']),
            'has_check' => !empty($persona['solution']['check']),
            'has_dialog' => !empty($persona['solution']['teacher_dialog'])
        ]
    ];
});

// ==========================================
// 테스트 6: 카테고리별 페르소나 조회
// ==========================================
runTest('카테고리별 조회', function() {
    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    // 'cognitive_overload' 카테고리 조회
    $personas = $engine->getPersonasByCategory('cognitive_overload');

    if (empty($personas)) {
        return [
            'success' => false,
            'message' => 'cognitive_overload 카테고리 페르소나 없음 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    // 예상: ID 1-10이 인지 과부하 카테고리
    $expectedCount = 10;
    $actualCount = count($personas);

    return [
        'success' => $actualCount === $expectedCount,
        'message' => $actualCount === $expectedCount
            ? "cognitive_overload 카테고리 {$actualCount}개 페르소나 확인"
            : "예상 {$expectedCount}개, 실제 {$actualCount}개",
        'details' => [
            'count' => $actualCount,
            'persona_ids' => array_column($personas, 'id'),
            'persona_names' => array_column($personas, 'name')
        ]
    ];
});

// ==========================================
// 테스트 7: 우선순위별 페르소나 조회
// ==========================================
runTest('우선순위별 조회', function() {
    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    // 'high' 우선순위 조회
    $highPriority = $engine->getPersonasByPriority('high');

    if (empty($highPriority)) {
        return [
            'success' => false,
            'message' => 'high 우선순위 페르소나 없음 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    // 모든 페르소나가 'high' 우선순위인지 확인
    $allHigh = true;
    foreach ($highPriority as $p) {
        if ($p['priority'] !== 'high') {
            $allHigh = false;
            break;
        }
    }

    return [
        'success' => $allHigh && count($highPriority) > 0,
        'message' => count($highPriority) . '개 high 우선순위 페르소나 확인',
        'details' => [
            'count' => count($highPriority),
            'sample_ids' => array_slice(array_column($highPriority, 'id'), 0, 5)
        ]
    ];
});

// ==========================================
// 테스트 8: 오디오 URL 생성
// ==========================================
runTest('오디오 URL 생성', function() {
    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    $audioUrl = $engine->getAudioUrl(1);

    if (empty($audioUrl)) {
        return [
            'success' => false,
            'message' => '오디오 URL 생성 실패 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    // URL 형식 확인
    $expectedPattern = '/\/Contents\/personas\/.*\/1\.(mp3|wav)/';
    $validFormat = preg_match($expectedPattern, $audioUrl);

    return [
        'success' => !empty($audioUrl),
        'message' => '오디오 URL 생성 성공',
        'details' => [
            'url' => $audioUrl,
            'valid_format' => (bool)$validFormat
        ]
    ];
});

// ==========================================
// 테스트 9: 정복 순서 조회
// ==========================================
runTest('정복 순서 조회', function() {
    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    $conquestOrder = $engine->getConquestOrder();

    if (empty($conquestOrder)) {
        return [
            'success' => false,
            'message' => '정복 순서 조회 실패 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    // 60개 ID가 모두 포함되어 있는지 확인
    $allIds = range(1, 60);
    $missingIds = array_diff($allIds, $conquestOrder);

    return [
        'success' => empty($missingIds),
        'message' => empty($missingIds)
            ? '60개 페르소나 정복 순서 확인'
            : count($missingIds) . '개 ID 누락',
        'details' => [
            'total_count' => count($conquestOrder),
            'first_5' => array_slice($conquestOrder, 0, 5),
            'missing' => empty($missingIds) ? 'none' : array_values($missingIds)
        ]
    ];
});

// ==========================================
// 테스트 10: 복합 키워드 분석
// ==========================================
runTest('복합 키워드 분석', function() {
    global $USER;

    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    $testCases = [
        '검증 없이 바로 제출했어요' => 1, // 충동적 풀이
        '너무 많은 정보가 동시에 들어와서 정리가 안 돼요' => 2, // 정보 과다
        '이 정도면 맞겠지 하고 확인 안 했어요' => 11, // 과신
        '자꾸 부호 실수를 해요' => 21, // 부호 실수
    ];

    $results = [];
    $successCount = 0;

    foreach ($testCases as $message => $expectedId) {
        $result = $engine->analyze($USER->id ?? 1, $message);
        $detectedId = $result['primary_match']['id'] ?? 0;
        $match = ($detectedId == $expectedId);
        if ($match) $successCount++;

        $results[] = [
            'message' => mb_substr($message, 0, 20) . '...',
            'expected' => $expectedId,
            'detected' => $detectedId,
            'match' => $match
        ];
    }

    $totalTests = count($testCases);
    $successRate = round(($successCount / $totalTests) * 100);

    return [
        'success' => $successRate >= 50, // 50% 이상 매칭시 성공
        'message' => "{$successCount}/{$totalTests} 케이스 매칭 ({$successRate}%)",
        'details' => $results
    ];
});

// ==========================================
// 테스트 11: API 엔드포인트 존재
// ==========================================
runTest('API 파일 존재', function() {
    $apiPath = __DIR__ . '/api.php';

    if (!file_exists($apiPath)) {
        return [
            'success' => false,
            'message' => 'api.php 파일 없음 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    $content = file_get_contents($apiPath);

    // 필수 액션 확인
    $requiredActions = ['analyze', 'get_persona', 'get_all', 'get_by_category', 'health'];
    $missingActions = [];

    foreach ($requiredActions as $action) {
        if (strpos($content, "'{$action}'") === false && strpos($content, "\"{$action}\"") === false) {
            $missingActions[] = $action;
        }
    }

    return [
        'success' => empty($missingActions),
        'message' => empty($missingActions)
            ? 'API 엔드포인트 파일 정상'
            : count($missingActions) . '개 액션 누락',
        'details' => [
            'file_size' => filesize($apiPath) . ' bytes',
            'missing_actions' => $missingActions
        ]
    ];
});

// ==========================================
// 테스트 12: 솔루션 구조 검증
// ==========================================
runTest('솔루션 구조 검증', function() {
    require_once(__DIR__ . '/Agent04PersonaEngine.php');
    $engine = new Agent04PersonaEngine();

    $solution = $engine->getSolution(1);

    if (empty($solution)) {
        return [
            'success' => false,
            'message' => '솔루션 조회 실패 [' . __FILE__ . ':' . __LINE__ . ']'
        ];
    }

    $requiredKeys = ['action', 'check', 'teacher_dialog'];
    $missingKeys = [];

    foreach ($requiredKeys as $key) {
        if (empty($solution[$key])) {
            $missingKeys[] = $key;
        }
    }

    return [
        'success' => empty($missingKeys),
        'message' => empty($missingKeys)
            ? '솔루션 구조 정상'
            : count($missingKeys) . '개 필드 누락',
        'details' => [
            'has_action' => !empty($solution['action']),
            'has_check' => !empty($solution['check']),
            'has_teacher_dialog' => !empty($solution['teacher_dialog']),
            'action_preview' => mb_substr($solution['action'] ?? '', 0, 50) . '...'
        ]
    ];
});

// HTML 출력
header('Content-Type: text/html; charset=utf-8');
$passCount = count(array_filter($testResults, function($r) { return $r['success']; }));
$failCount = count($testResults) - $passCount;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent04 인지관성 페르소나 테스트</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', 'Malgun Gothic', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #2d3748;
            border-bottom: 3px solid #667eea;
            padding-bottom: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .subtitle {
            color: #718096;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .subtitle code {
            background: #edf2f7;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
        }
        .summary {
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        .summary.success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            border-left: 5px solid #38a169;
        }
        .summary.failure {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border-left: 5px solid #e53e3e;
        }
        .summary-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .stats {
            display: flex;
            gap: 20px;
        }
        .stat {
            text-align: center;
            padding: 10px 20px;
            background: rgba(255,255,255,0.8);
            border-radius: 8px;
        }
        .stat-value {
            font-size: 1.8rem;
            font-weight: bold;
        }
        .stat-label {
            font-size: 0.75rem;
            color: #4a5568;
        }
        .stat.pass .stat-value { color: #38a169; }
        .stat.fail .stat-value { color: #e53e3e; }
        .stat.time .stat-value { color: #667eea; }
        .test-grid {
            display: grid;
            gap: 12px;
        }
        .test-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-radius: 10px;
            background: #f7fafc;
            transition: all 0.2s;
        }
        .test-item:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .test-item.success {
            border-left: 4px solid #38a169;
        }
        .test-item.failure {
            border-left: 4px solid #e53e3e;
        }
        .test-icon {
            font-size: 1.5rem;
            margin-right: 15px;
        }
        .test-content {
            flex: 1;
        }
        .test-name {
            font-weight: 600;
            color: #2d3748;
        }
        .test-message {
            font-size: 0.9rem;
            color: #718096;
            margin-top: 3px;
        }
        .test-meta {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .badge.pass { background: #c6f6d5; color: #276749; }
        .badge.fail { background: #fed7d7; color: #c53030; }
        .badge.time { background: #e9d8fd; color: #553c9a; }
        .details-toggle {
            background: none;
            border: 1px solid #e2e8f0;
            padding: 4px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
            color: #718096;
        }
        .details-toggle:hover {
            background: #edf2f7;
        }
        .details {
            display: none;
            margin-top: 10px;
            padding: 10px;
            background: #1a202c;
            color: #e2e8f0;
            border-radius: 6px;
            font-family: 'Consolas', monospace;
            font-size: 0.8rem;
            overflow-x: auto;
            white-space: pre-wrap;
        }
        .details.show {
            display: block;
        }
        .action-bar {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(102,126,234,0.4); }
        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-success {
            background: #38a169;
            color: white;
        }
        .btn-success:hover { background: #2f855a; }
        .quick-test {
            margin-top: 25px;
            padding: 20px;
            background: #f7fafc;
            border-radius: 12px;
        }
        .quick-test h3 {
            margin-bottom: 15px;
            color: #2d3748;
        }
        .quick-test-input {
            display: flex;
            gap: 10px;
        }
        .quick-test-input input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }
        .quick-test-input input:focus {
            outline: none;
            border-color: #667eea;
        }
        #quickResult {
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-family: 'Consolas', monospace;
            font-size: 0.85rem;
            white-space: pre-wrap;
            display: none;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>
        <span style="font-size: 2rem;">🧠</span>
        Agent04 인지관성 페르소나 테스트
    </h1>
    <div class="subtitle">
        <code><?php echo htmlspecialchars(CURRENT_FILE); ?></code><br>
        실행 시간: <?php echo date('Y-m-d H:i:s'); ?> | 버전: <?php echo $config['version'] ?? '2.0.0'; ?>
    </div>

    <div class="summary <?php echo $overallSuccess ? 'success' : 'failure'; ?>">
        <div class="summary-title">
            <?php echo $overallSuccess ? '✅ 모든 테스트 통과!' : '❌ 일부 테스트 실패'; ?>
        </div>
        <div class="stats">
            <div class="stat">
                <div class="stat-value"><?php echo count($testResults); ?></div>
                <div class="stat-label">전체</div>
            </div>
            <div class="stat pass">
                <div class="stat-value"><?php echo $passCount; ?></div>
                <div class="stat-label">통과</div>
            </div>
            <div class="stat fail">
                <div class="stat-value"><?php echo $failCount; ?></div>
                <div class="stat-label">실패</div>
            </div>
            <div class="stat time">
                <div class="stat-value"><?php echo round($totalTime, 1); ?>ms</div>
                <div class="stat-label">소요시간</div>
            </div>
        </div>
    </div>

    <h2>📋 테스트 결과</h2>
    <div class="test-grid">
        <?php foreach ($testResults as $index => $result): ?>
        <div class="test-item <?php echo $result['success'] ? 'success' : 'failure'; ?>">
            <div class="test-icon"><?php echo $result['success'] ? '✅' : '❌'; ?></div>
            <div class="test-content">
                <div class="test-name"><?php echo ($index + 1) . '. ' . htmlspecialchars($result['name']); ?></div>
                <div class="test-message"><?php echo htmlspecialchars($result['message']); ?></div>
                <?php if (!empty($result['details'])): ?>
                <button class="details-toggle" onclick="toggleDetails(<?php echo $index; ?>)">상세 보기</button>
                <div class="details" id="details-<?php echo $index; ?>">
<?php echo htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="test-meta">
                <span class="badge <?php echo $result['success'] ? 'pass' : 'fail'; ?>">
                    <?php echo $result['success'] ? 'PASS' : 'FAIL'; ?>
                </span>
                <span class="badge time"><?php echo $result['elapsed_ms']; ?>ms</span>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="quick-test">
        <h3>⚡ 빠른 분석 테스트</h3>
        <div class="quick-test-input">
            <input type="text" id="quickMessage" placeholder="테스트할 메시지를 입력하세요..." value="문제 보자마자 바로 풀어버렸어요">
            <button class="btn btn-primary" onclick="quickAnalyze()">분석하기</button>
        </div>
        <div id="quickResult"></div>
    </div>

    <div class="action-bar">
        <a href="?refresh=<?php echo time(); ?>" class="btn btn-primary">🔄 테스트 다시 실행</a>
        <a href="api.php?action=health" class="btn btn-secondary" target="_blank">💓 API 헬스체크</a>
        <a href="api.php?action=help" class="btn btn-secondary" target="_blank">📖 API 문서</a>
        <a href="api.php?action=get_all" class="btn btn-secondary" target="_blank">📋 전체 페르소나</a>
        <a href="api.php?action=get_categories" class="btn btn-success" target="_blank">🏷️ 카테고리</a>
    </div>
</div>

<script>
function toggleDetails(index) {
    const details = document.getElementById('details-' + index);
    details.classList.toggle('show');
}

async function quickAnalyze() {
    const message = document.getElementById('quickMessage').value;
    const resultDiv = document.getElementById('quickResult');

    if (!message.trim()) {
        resultDiv.style.display = 'block';
        resultDiv.textContent = '메시지를 입력하세요.';
        return;
    }

    resultDiv.style.display = 'block';
    resultDiv.textContent = '분석 중...';

    try {
        const response = await fetch('api.php?action=analyze&message=' + encodeURIComponent(message) + '&user_id=<?php echo $USER->id ?? 1; ?>');
        const data = await response.json();

        if (data.success && data.data && data.data.primary_match) {
            const match = data.data.primary_match;
            let output = `🎯 감지된 인지관성: ${match.name} (ID: ${match.id})\n`;
            output += `📂 카테고리: ${match.category}\n`;
            output += `⚡ 우선순위: ${match.priority}\n`;
            output += `📊 매칭 점수: ${match.score}\n`;
            output += `\n💡 해결 전략:\n${match.solution?.action || '(없음)'}\n`;
            output += `\n🔊 오디오: ${match.audio_url || '(없음)'}`;
            resultDiv.textContent = output;
        } else {
            resultDiv.textContent = '분석 결과: ' + JSON.stringify(data, null, 2);
        }
    } catch (error) {
        resultDiv.textContent = '오류: ' + error.message + ' [test.php:quickAnalyze]';
    }
}
</script>
</body>
</html>
<?php
/**
 * 관련 DB 테이블 (미래 확장용):
 * - mdl_at_cognitive_inertia_log: 인지관성 분석 로그
 *   - id: bigint(10)
 *   - user_id: bigint(10)
 *   - persona_id: int(10)
 *   - message: text
 *   - score: decimal(5,2)
 *   - timestamp: bigint(10)
 *
 * - mdl_at_conquest_progress: 정복 진행도
 *   - id: bigint(10)
 *   - user_id: bigint(10)
 *   - persona_id: int(10)
 *   - conquered: tinyint(1)
 *   - conquered_at: bigint(10)
 */
