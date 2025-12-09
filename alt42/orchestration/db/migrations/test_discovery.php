<?php
/**
 * test_discovery.php
 *
 * TestRunner 발견 테스트 (DB 테스트 없이)
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/test_discovery.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('MOODLE_INTERNAL', true);

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <title>Agent Test Discovery</title>
    <style>
        body { font-family: -apple-system, sans-serif; background: #1a1a2e; color: #eee; padding: 30px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #0ff; }
        .agent { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; display: flex; justify-content: space-between; }
        .agent.has-test { border-left: 4px solid #0f0; }
        .agent.no-test { border-left: 4px solid #ff5722; }
        .name { font-weight: bold; }
        .status { padding: 3px 10px; border-radius: 4px; font-size: 12px; }
        .status.yes { background: #0f0; color: #000; }
        .status.no { background: #ff5722; color: #fff; }
        .summary { background: #0f3460; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .stats { display: flex; gap: 30px; margin-top: 15px; }
        .stat { text-align: center; }
        .stat-num { font-size: 36px; font-weight: bold; }
        .stat-label { color: #aaa; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Agent Test Discovery</h1>
    <p>TestRunner가 21개 에이전트의 테스트 파일을 검색합니다.</p>
";

try {
    require_once(__DIR__ . '/../../agents/engine_core/testing/TestRunner.php');

    $runner = new TestRunner(null, ['output_format' => 'text']);
    $discovered = $runner->discoverTests();

    $withTests = 0;
    $withoutTests = 0;

    foreach ($discovered as $info) {
        if ($info['test_file'] !== null) {
            $withTests++;
        } else {
            $withoutTests++;
        }
    }

    echo "
    <div class='summary'>
        <h2>📊 발견 결과</h2>
        <div class='stats'>
            <div class='stat'>
                <div class='stat-num' style='color:#0ff;'>" . count($discovered) . "</div>
                <div class='stat-label'>전체 에이전트</div>
            </div>
            <div class='stat'>
                <div class='stat-num' style='color:#0f0;'>{$withTests}</div>
                <div class='stat-label'>테스트 있음</div>
            </div>
            <div class='stat'>
                <div class='stat-num' style='color:#ff5722;'>{$withoutTests}</div>
                <div class='stat-label'>테스트 없음</div>
            </div>
        </div>
    </div>

    <h2>📋 에이전트별 상태</h2>
    ";

    foreach ($discovered as $nagent => $info) {
        $hasTest = $info['test_file'] !== null;
        $class = $hasTest ? 'has-test' : 'no-test';
        $statusClass = $hasTest ? 'yes' : 'no';
        $statusText = $hasTest ? '✓ 테스트 있음' : '✗ 테스트 없음';

        echo "
        <div class='agent {$class}'>
            <div>
                <span class='name'>Agent" . sprintf('%02d', $nagent) . "</span> -
                {$info['agent_kr_name']} ({$info['agent_name']})
            </div>
            <div class='status {$statusClass}'>{$statusText}</div>
        </div>
        ";
    }

    echo "
    <div style='margin-top: 30px; padding: 15px; background: #0f3460; border-radius: 8px;'>
        <strong>🔧 다음 단계:</strong>
        <ul style='margin-top: 10px;'>
            <li>테스트 없는 14개 에이전트에 test.php 생성 필요</li>
            <li>각 에이전트의 test.php는 BasePersonaTest 클래스 상속</li>
            <li>Phase 2: 미구현 에이전트 구현 (Agent02, 12, 13)</li>
        </ul>
    </div>
    ";

} catch (Throwable $e) {
    echo "<div style='color: #f55; background: #300; padding: 20px; border-radius: 8px;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre style='color: #f88;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    echo "</div>";
}

echo "
    <p style='color: #666; margin-top: 30px;'>실행 시간: " . date('Y-m-d H:i:s') . "</p>
</div>
</body>
</html>";
