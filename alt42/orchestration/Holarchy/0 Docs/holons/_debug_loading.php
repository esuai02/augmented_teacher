<?php
/**
 * POC Dashboard Loading Debug Tool
 * =================================
 * 무한 로딩 원인 진단용
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/_debug_loading.php
 *
 * @file _debug_loading.php
 * @location alt42/orchestration/Holarchy/0 Docs/holons/
 */

// 타이밍 시작
$start_time = microtime(true);
$checkpoints = [];

function checkpoint($name) {
    global $start_time, $checkpoints;
    $elapsed = round((microtime(true) - $start_time) * 1000, 2);
    $checkpoints[] = "[{$elapsed}ms] {$name}";
}

checkpoint("Script start");

// Step 1: Moodle 로드 전
echo "<!-- Step 1: Before Moodle -->\n";
checkpoint("Before Moodle include");

include_once("/home/moodle/public_html/moodle/config.php");
checkpoint("After Moodle include");

global $DB, $USER;
checkpoint("After global");

require_login();
checkpoint("After require_login");

// Step 2: 경로 정의
define('HOLONS_PATH', __DIR__);
define('AGENTS_PATH', dirname(dirname(dirname(HOLONS_PATH))) . '/agents');
checkpoint("Paths defined");

// Step 3: 파일 시스템 체크 (에이전트 폴더)
for ($i = 1; $i <= 4; $i++) {
    $agentPath = AGENTS_PATH . "/agent" . sprintf("%02d", $i) . "_*";
    $exists = !empty(glob($agentPath));
    checkpoint("Agent $i folder check: " . ($exists ? "exists" : "not found"));
}

// Step 4: Phase 배열 정의 (대용량)
$phases = array_fill(0, 6, ['modules' => array_fill(0, 5, ['file' => 'test.py'])]);
checkpoint("Phases array created");

// Step 5: HTML 출력
checkpoint("Before HTML output");

$total_time = round((microtime(true) - $start_time) * 1000, 2);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>POC Dashboard Debug</title>
    <style>
        body { font-family: monospace; background: #1a1a2e; color: #e0e0e0; padding: 20px; }
        .success { color: #22c55e; }
        .warning { color: #f59e0b; }
        .error { color: #ef4444; }
        pre { background: #0d1117; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>
    <h1>POC Dashboard Loading Debug</h1>

    <h2>Summary</h2>
    <p class="<?php echo $total_time < 1000 ? 'success' : ($total_time < 3000 ? 'warning' : 'error'); ?>">
        Total Load Time: <strong><?php echo $total_time; ?>ms</strong>
    </p>

    <h2>Checkpoints</h2>
    <pre><?php
    foreach ($checkpoints as $cp) {
        echo htmlspecialchars($cp) . "\n";
    }
    ?></pre>

    <h2>Memory Usage</h2>
    <pre>
Peak: <?php echo round(memory_get_peak_usage() / 1024 / 1024, 2); ?> MB
Current: <?php echo round(memory_get_usage() / 1024 / 1024, 2); ?> MB
    </pre>

    <h2>PHP Info</h2>
    <pre>
PHP Version: <?php echo phpversion(); ?>

Max Execution Time: <?php echo ini_get('max_execution_time'); ?>s
Memory Limit: <?php echo ini_get('memory_limit'); ?>

Output Buffering: <?php echo ob_get_level(); ?> levels
    </pre>

    <h2>User Info</h2>
    <pre>
User ID: <?php echo $USER->id ?? 'N/A'; ?>

Username: <?php echo $USER->username ?? 'N/A'; ?>

    </pre>

    <h2>Actions</h2>
    <p>
        <a href="pocdashboard.php" style="color: #00d9ff;">← POC Dashboard로 이동</a>
        |
        <a href="pocdashboard.php?action=test_json" style="color: #00d9ff;">JSON 테스트</a>
    </p>
</body>
</html>
<?php
// DB 정보 (이 파일에서 사용되는 테이블 정보)
/**
 * 이 디버그 파일은 DB를 사용하지 않음
 * 관련 DB: mdl_abessi_todayplans (Agent14 진도 분석용)
 */
?>
