<?php
/**
 * 마이그레이션 직접 실행 스크립트 (로그인 불필요 - 보안 주의!)
 * 서버에서 직접 실행: php run_migrations_direct.php
 * 또는 웹 브라우저: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/run_migrations_direct.php
 * 
 * ⚠️ 보안 경고: 이 파일은 로그인 없이 실행되므로, 서버 접근이 제한된 환경에서만 사용하세요.
 * 
 * @package ALT42\Migrations
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// 보안: IP 체크 또는 특정 토큰 필요 (선택사항)
// $allowed_ips = ['127.0.0.1', '::1'];
// if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips)) {
//     die("접근이 거부되었습니다. at " . __FILE__ . ":" . __LINE__);
// }

// Moodle config 로드
$moodle_config = '/home/moodle/public_html/moodle/config.php';
if (!file_exists($moodle_config)) {
    die("Moodle config 파일을 찾을 수 없습니다: {$moodle_config} at " . __FILE__ . ":" . __LINE__);
}

include_once($moodle_config);

// CLI 모드 확인
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // 웹 모드: HTML 출력
    header('Content-Type: text/html; charset=utf-8');
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>마이그레이션 실행</title>";
    echo "<style>body{font-family:monospace;background:#000;color:#0f0;padding:20px;}";
    echo ".error{color:#f00;}.success{color:#0f0;}.info{color:#0ff;}</style></head><body>";
    echo "<pre>";
}

echo "=== Heartbeat 마이그레이션 실행 ===\n";
echo "시작 시간: " . date('Y-m-d H:i:s') . "\n\n";

$errors = [];
$success = [];

// 마이그레이션 005 실행
echo "1. 마이그레이션 005 실행 중...\n";
try {
    ob_start();
    include(__DIR__ . '/db/migrations/run_005_migration.php');
    $output = ob_get_clean();
    echo $output;
    $success[] = '005';
} catch (Exception $e) {
    $error_msg = "마이그레이션 005 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
    echo $error_msg . "\n";
    $errors[] = $error_msg;
}

echo "\n";

// 마이그레이션 006 실행
echo "2. 마이그레이션 006 실행 중...\n";
try {
    ob_start();
    include(__DIR__ . '/db/migrations/run_006_migration.php');
    $output = ob_get_clean();
    echo $output;
    $success[] = '006';
} catch (Exception $e) {
    $error_msg = "마이그레이션 006 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
    echo $error_msg . "\n";
    $errors[] = $error_msg;
}

echo "\n";

// 마이그레이션 007 실행
echo "3. 마이그레이션 007 실행 중...\n";
try {
    ob_start();
    include(__DIR__ . '/db/migrations/run_007_migration.php');
    $output = ob_get_clean();
    echo $output;
    $success[] = '007';
} catch (Exception $e) {
    $error_msg = "마이그레이션 007 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
    echo $error_msg . "\n";
    $errors[] = $error_msg;
}

echo "\n";

// 마이그레이션 008 실행
echo "4. 마이그레이션 008 실행 중...\n";
try {
    ob_start();
    include(__DIR__ . '/db/migrations/run_008_migration.php');
    $output = ob_get_clean();
    echo $output;
    $success[] = '008';
} catch (Exception $e) {
    $error_msg = "마이그레이션 008 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
    echo $error_msg . "\n";
    $errors[] = $error_msg;
}

echo "\n";

// 테스트 실행
echo "5. 테스트 실행 중...\n";
try {
    ob_start();
    include(__DIR__ . '/api/scheduler/test_heartbeat.php');
    $output = ob_get_clean();
    echo $output;
    $success[] = 'test';
} catch (Exception $e) {
    $error_msg = "테스트 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
    echo $error_msg . "\n";
    $errors[] = $error_msg;
}

echo "\n";
echo "=== 실행 완료 ===\n";
echo "성공: " . count($success) . "개\n";
echo "실패: " . count($errors) . "개\n";
echo "완료 시간: " . date('Y-m-d H:i:s') . "\n";

if (count($errors) > 0) {
    echo "\n오류 목록:\n";
    foreach ($errors as $error) {
        echo "  - " . $error . "\n";
    }
    if (!$is_cli) {
        echo "</pre></body></html>";
    }
    exit(1);
}

if (!$is_cli) {
    echo "</pre></body></html>";
}
exit(0);

