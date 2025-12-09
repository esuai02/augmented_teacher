<?php
/**
 * Pure PHP Test (No Moodle)
 * =========================
 * Moodle 없이 순수 PHP 동작 확인
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/_test_pure.php
 */

// Moodle 없이 테스트
header('Content-Type: text/html; charset=utf-8');

$start = microtime(true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pure PHP Test</title>
    <style>
        body { background: #1a1a2e; color: #fff; font-family: monospace; padding: 20px; }
        .success { color: #22c55e; }
    </style>
</head>
<body>
    <h1>Pure PHP Test</h1>
    <p class="success">✅ PHP 정상 작동</p>
    <p>PHP Version: <?php echo phpversion(); ?></p>
    <p>Load Time: <?php echo round((microtime(true) - $start) * 1000, 2); ?>ms</p>
    <p>Server Time: <?php echo date('Y-m-d H:i:s'); ?></p>
    <hr>
    <p><a href="pocdashboard.php" style="color: #00d9ff;">POC Dashboard</a></p>
</body>
</html>
