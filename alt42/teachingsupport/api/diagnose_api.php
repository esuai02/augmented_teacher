<?php
/**
 * API Diagnostic Tool
 * 서버 API 상태 진단 및 문제 해결
 *
 * @package AugmentedTeacher\TeachingSupport\API
 * @version 1.0.0
 * @since 2025-12-11
 *
 * URL: /moodle/local/augmented_teacher/alt42/teachingsupport/api/diagnose_api.php
 *
 * 사용법: 브라우저에서 직접 접속
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=UTF-8');

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>API Diagnostic</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#1a1a2e;color:#eee}';
echo '.ok{color:#0f0}.err{color:#f00}.warn{color:#ff0}pre{background:#0d0d1a;padding:10px;border-radius:5px;overflow-x:auto}</style></head><body>';
echo '<h1>🔍 API Diagnostic Tool</h1>';

// 1. PHP 버전 확인
echo '<h2>1. PHP 환경</h2>';
echo '<p>PHP Version: <span class="ok">' . phpversion() . '</span></p>';

// 2. Moodle config 확인
echo '<h2>2. Moodle Config</h2>';
$configPath = "/home/moodle/public_html/moodle/config.php";
if (file_exists($configPath)) {
    echo '<p class="ok">✅ config.php 존재</p>';
    try {
        include_once($configPath);
        echo '<p class="ok">✅ config.php 로드 성공</p>';
        global $DB, $USER, $CFG;
        echo '<p>DB Connected: <span class="ok">' . (isset($DB) ? 'Yes' : 'No') . '</span></p>';
        echo '<p>USER Available: <span class="ok">' . (isset($USER) ? 'Yes (ID: ' . ($USER->id ?? 'N/A') . ')' : 'No') . '</span></p>';
        echo '<p>WWWRoot: ' . ($CFG->wwwroot ?? 'N/A') . '</p>';
    } catch (Exception $e) {
        echo '<p class="err">❌ config.php 로드 실패: ' . $e->getMessage() . '</p>';
    }
} else {
    echo '<p class="err">❌ config.php 없음: ' . $configPath . '</p>';
}

// 3. API 파일 확인
echo '<h2>3. API 파일 상태</h2>';
$apiDir = __DIR__;
$apiFiles = [
    'analyze_quantum_path.php',
    'analyze_neuron_path.php'
];

foreach ($apiFiles as $file) {
    $fullPath = $apiDir . '/' . $file;
    if (file_exists($fullPath)) {
        $size = filesize($fullPath);
        $mtime = date('Y-m-d H:i:s', filemtime($fullPath));
        echo '<p class="ok">✅ ' . $file . ' (Size: ' . $size . ' bytes, Modified: ' . $mtime . ')</p>';

        // 파일 첫 10줄 확인
        $content = file_get_contents($fullPath);
        $preview = implode("\n", array_slice(explode("\n", $content), 0, 15));
        echo '<details><summary>Preview</summary><pre>' . htmlspecialchars($preview) . '</pre></details>';
    } else {
        echo '<p class="err">❌ ' . $file . ' 없음</p>';
    }
}

// 4. DB 테이블 확인
echo '<h2>4. DB 테이블 상태</h2>';
if (isset($DB)) {
    $tables = [
        'mq_question_meta' => '문제 메타데이터',
        'at_quantum_paths' => '양자 경로 로그',
        'at_neuron_paths' => '뉴런 경로'
    ];

    foreach ($tables as $table => $desc) {
        try {
            $exists = $DB->get_manager()->table_exists($table);
            if ($exists) {
                $count = $DB->count_records($table);
                echo '<p class="ok">✅ mdl_' . $table . ' (' . $desc . ') - ' . $count . ' records</p>';
            } else {
                echo '<p class="warn">⚠️ mdl_' . $table . ' (' . $desc . ') - 테이블 없음</p>';
            }
        } catch (Exception $e) {
            echo '<p class="err">❌ ' . $table . ' 확인 실패: ' . $e->getMessage() . '</p>';
        }
    }
}

// 5. API 테스트
echo '<h2>5. API 직접 테스트</h2>';
echo '<h3>analyze_quantum_path.php 테스트:</h3>';

// 직접 실행 테스트
ob_start();
try {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $testInput = json_encode(['contentsId' => 'Q7MQFA3856470', 'questionData' => [], 'imageUrl' => '']);

    // 테스트용 입력 시뮬레이션
    echo '<p>Test Input: <pre>' . htmlspecialchars($testInput) . '</pre></p>';

    // API 코드 직접 실행하지 않고 curl로 테스트
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $CFG->wwwroot . '/local/augmented_teacher/alt42/teachingsupport/api/analyze_quantum_path.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $testInput);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo '<p>HTTP Code: <span class="' . ($httpCode == 200 ? 'ok' : 'err') . '">' . $httpCode . '</span></p>';
    if ($error) {
        echo '<p class="err">cURL Error: ' . $error . '</p>';
    }
    if ($response) {
        $json = json_decode($response, true);
        if ($json) {
            echo '<p class="ok">Valid JSON Response</p>';
            echo '<pre>' . htmlspecialchars(json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
        } else {
            echo '<p class="err">Invalid JSON:</p>';
            echo '<pre>' . htmlspecialchars(substr($response, 0, 1000)) . '</pre>';
        }
    }
} catch (Exception $e) {
    echo '<p class="err">Test Error: ' . $e->getMessage() . '</p>';
}
ob_end_flush();

// 6. 해결 방법 안내
echo '<h2>6. 문제 해결</h2>';
echo '<p>API 500 에러 해결 방법:</p>';
echo '<ol>';
echo '<li>위의 진단 결과를 확인하여 누락된 파일 또는 테이블 식별</li>';
echo '<li>필요한 파일을 GitHub에서 동기화: <code>git pull origin main</code></li>';
echo '<li>DB 테이블이 없으면 마이그레이션 스크립트 실행</li>';
echo '<li>파일 권한 확인: <code>chmod 644 *.php</code></li>';
echo '</ol>';

echo '<h3>Quick Fix - 기본 데이터 강제 반환:</h3>';
echo '<p>analyze_quantum_path.php가 없거나 오류가 있으면, JavaScript의 useDefaultMaze() 폴백이 동작해야 합니다.</p>';

echo '</body></html>';

/**
 * 관련 DB 테이블:
 * - mdl_mq_question_meta: 문제 메타데이터
 * - mdl_at_quantum_paths: 양자 경로 로그
 * - mdl_at_neuron_paths: 뉴런 경로
 */
