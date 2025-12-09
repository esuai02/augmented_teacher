<?php
/**
 * Quantum Orchestration - Minimal Test Runner
 * ============================================
 * PHP wrapper to execute _quantum_minimal_test.py
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0 Docs/holons/quantum_test_runner.php
 *
 * @file quantum_test_runner.php
 * @location alt42/orchestration/Holarchy/0 Docs/holons/
 */

// Moodle 통합
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 표시 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Python 스크립트 경로
$script_path = __DIR__ . '/_quantum_minimal_test.py';

// 파일 존재 확인
if (!file_exists($script_path)) {
    die("Error [quantum_test_runner.php:26]: Python script not found: " . $script_path);
}

// Python 실행
$python_cmd = 'python3 ' . escapeshellarg($script_path) . ' 2>&1';
$output = shell_exec($python_cmd);

// 실행 실패 체크
if ($output === null) {
    $output = "Error [quantum_test_runner.php:34]: Failed to execute Python script. Check server permissions.";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Orchestration - Minimal Test</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #58a6ff;
            border-bottom: 1px solid #30363d;
            padding-bottom: 10px;
        }
        .info-box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 14px;
        }
        .info-box span.label {
            color: #8b949e;
        }
        .info-box span.value {
            color: #7ee787;
        }
        pre {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 20px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.5;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .btn {
            display: inline-block;
            background: #238636;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            margin-top: 15px;
        }
        .btn:hover {
            background: #2ea043;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quantum Orchestration - Minimal Test</h1>

        <div class="info-box">
            <p><span class="label">User:</span> <span class="value"><?php echo htmlspecialchars($USER->username ?? 'N/A'); ?></span></p>
            <p><span class="label">Script:</span> <span class="value"><?php echo htmlspecialchars(basename($script_path)); ?></span></p>
            <p><span class="label">Executed:</span> <span class="value"><?php echo date('Y-m-d H:i:s'); ?></span></p>
        </div>

        <h2>Execution Output</h2>
        <pre><?php echo htmlspecialchars($output); ?></pre>

        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="btn">Re-run Test</a>
    </div>
</body>
</html>
