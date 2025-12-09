<?php
/**
 * Heartbeat Scheduler 수동 테스트 스크립트
 * 상세한 테스트 및 검증
 * 
 * @package ALT42\Scheduler\Test
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Moodle config 로드
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Heartbeat Scheduler 수동 테스트</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        .section { margin: 20px 0; padding: 10px; border: 1px solid #0f0; }
        pre { background: #111; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #0f0; padding: 8px; text-align: left; }
        th { background: #0f0; color: #000; }
    </style>
</head>
<body>
<pre>";

echo "=== Heartbeat Scheduler 수동 테스트 ===\n";
echo "시작 시간: " . date('Y-m-d H:i:s') . "\n\n";

// 1. 환경 확인
echo "1. 환경 확인\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$checks = [];

// PHP 버전 확인
$php_version = phpversion();
$checks['PHP 버전'] = version_compare($php_version, '7.1.0', '>=') ? ['success', $php_version] : ['error', $php_version . ' (7.1.0 이상 필요)'];
echo "  PHP 버전: " . ($checks['PHP 버전'][0] === 'success' ? '✓' : '✗') . " {$php_version}\n";

// Moodle 연결 확인
try {
    $db_test = $DB->get_record_sql("SELECT 1 as test");
    $checks['Moodle DB 연결'] = $db_test ? ['success', '연결 성공'] : ['error', '연결 실패'];
    echo "  Moodle DB 연결: " . ($checks['Moodle DB 연결'][0] === 'success' ? '✓' : '✗') . " 연결 성공\n";
} catch (\Exception $e) {
    $checks['Moodle DB 연결'] = ['error', $e->getMessage()];
    echo "  Moodle DB 연결: ✗ " . $e->getMessage() . "\n";
}

// 필수 파일 확인
require_once(__DIR__ . '/../database/agent_data_layer.php');
$required_files = [
    'heartbeat.php' => __DIR__ . '/heartbeat.php',
    'event_bus.php' => __DIR__ . '/../events/event_bus.php',
    'agent_data_layer.php' => __DIR__ . '/../database/agent_data_layer.php',
    'event_scenario_mapper.php' => __DIR__ . '/../mapping/event_scenario_mapper.php',
    'route.php' => __DIR__ . '/../oa/route.php',
    'event_schemas.php' => __DIR__ . '/../config/event_schemas.php',
    'rule_evaluator.php' => __DIR__ . '/../rule_engine/rule_evaluator.php'
];

echo "\n  필수 파일 확인:\n";
foreach ($required_files as $name => $path) {
    $exists = file_exists($path);
    $checks["파일: {$name}"] = $exists ? ['success', '존재'] : ['error', '없음'];
    echo "    " . ($exists ? '✓' : '✗') . " {$name}\n";
}

echo "\n";

// 2. 데이터베이스 테이블 확인
echo "2. 데이터베이스 테이블 확인\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$required_tables = [
    'mdl_alt42_heartbeat_log',
    'mdl_alt42_state_change_log',
    'mdl_alt42_event_processing_log',
    'mdl_alt42_student_state_cache',
    'mdl_alt42_scenario_evaluation_log',
    'mdl_alt42_students',
    'mdl_alt42_student_biometrics',
    'mdl_alt42_student_profiles',
    'mdl_alt42_learning_sessions',
    'mdl_alt42_student_activity'
];

foreach ($required_tables as $table) {
    try {
        $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
        $result = $DB->get_record_sql($check_sql, [$table]);
        $exists = $result && $result->cnt > 0;
        echo "  " . ($exists ? '✓' : '✗') . " {$table}\n";
    } catch (\Exception $e) {
        echo "  ✗ {$table} - 오류: " . $e->getMessage() . "\n";
    }
}

// VIEW 확인
echo "\n  VIEW 확인:\n";
try {
    $check_view_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.VIEWS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'mdl_alt42_v_student_state'";
    $view_result = $DB->get_record_sql($check_view_sql);
    $view_exists = $view_result && $view_result->cnt > 0;
    echo "  " . ($view_exists ? '✓' : '✗') . " mdl_alt42_v_student_state\n";
    
    if ($view_exists) {
        // VIEW에서 데이터 조회 테스트
        try {
            $test_sql = "SELECT COUNT(*) as cnt FROM mdl_alt42_v_student_state LIMIT 1";
            $test_result = $DB->get_record_sql($test_sql);
            echo "    → VIEW 데이터: " . ($test_result->cnt ?? 0) . " rows\n";
        } catch (\Exception $e) {
            echo "    → VIEW 쿼리 오류: " . $e->getMessage() . "\n";
        }
    }
} catch (\Exception $e) {
    echo "  ✗ VIEW 확인 오류: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. Heartbeat 스케줄러 인스턴스 생성 테스트
echo "3. Heartbeat 스케줄러 인스턴스 생성 테스트\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    require_once(__DIR__ . '/heartbeat.php');
    
    $scheduler = new HeartbeatScheduler();
    echo "  ✓ HeartbeatScheduler 인스턴스 생성 성공\n";
    
    // 메서드 존재 확인
    $methods = ['execute', 'getActiveStudents', 'getStudentState', 'evaluateScenarios', 'logHeartbeatExecution'];
    foreach ($methods as $method) {
        $exists = method_exists($scheduler, $method);
        echo "    " . ($exists ? '✓' : '✗') . " 메서드: {$method}()\n";
    }
} catch (\Exception $e) {
    echo "  ✗ 인스턴스 생성 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
}

echo "\n";

// 4. Heartbeat 실행 테스트
echo "4. Heartbeat 실행 테스트\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $scheduler = new HeartbeatScheduler();
    
    $start_time = microtime(true);
    $result = $scheduler->execute();
    $duration = (microtime(true) - $start_time) * 1000;
    
    echo "  실행 결과:\n";
    echo "    성공: " . ($result['success'] ? '✓ YES' : '✗ NO') . "\n";
    echo "    처리된 학생 수: " . ($result['students_processed'] ?? 0) . "\n";
    echo "    오류 수: " . ($result['errors'] ?? 0) . "\n";
    echo "    실행 시간: " . number_format($duration, 2) . " ms\n";
    echo "    타임스탬프: " . ($result['timestamp'] ?? 'N/A') . "\n";
    
    if (isset($result['results']) && is_array($result['results'])) {
        echo "\n    상세 결과:\n";
        foreach ($result['results'] as $idx => $student_result) {
            echo "      [{$idx}] 학생 ID: " . ($student_result['student_id'] ?? 'N/A') . "\n";
            echo "            시나리오 평가: " . ($student_result['scenarios_evaluated'] ?? 0) . "개\n";
            if (isset($student_result['errors']) && $student_result['errors'] > 0) {
                echo "            ⚠ 오류: " . $student_result['errors'] . "개\n";
            }
        }
    }
    
} catch (\Exception $e) {
    echo "  ✗ 실행 실패: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    echo "    스택 트레이스:\n";
    echo "    " . str_replace("\n", "\n    ", $e->getTraceAsString()) . "\n";
}

echo "\n";

// 5. 최근 실행 로그 확인
echo "5. 최근 실행 로그 확인\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $log_sql = "SELECT 
        id,
        students_processed,
        errors,
        duration_ms,
        created_at
    FROM mdl_alt42_heartbeat_log
    ORDER BY created_at DESC
    LIMIT 5";
    
    $logs = $DB->get_records_sql($log_sql);
    
    if ($logs) {
        echo "  최근 5개 실행 기록:\n";
        echo "  ┌─────┬──────────────┬────────┬──────────────┬─────────────────────┐\n";
        echo "  │ ID  │ 학생 처리 수 │ 오류 수 │ 실행 시간(ms)│ 실행 시간          │\n";
        echo "  ├─────┼──────────────┼────────┼──────────────┼─────────────────────┤\n";
        
        foreach ($logs as $log) {
            printf("  │ %-3d │ %-12d │ %-6d │ %-12.2f │ %-19s │\n",
                $log->id,
                $log->students_processed,
                $log->errors,
                $log->duration_ms,
                date('Y-m-d H:i:s', strtotime($log->created_at))
            );
        }
        echo "  └─────┴──────────────┴────────┴──────────────┴─────────────────────┘\n";
    } else {
        echo "  ⚠ 실행 기록이 없습니다.\n";
    }
} catch (\Exception $e) {
    echo "  ✗ 로그 조회 실패: " . $e->getMessage() . "\n";
}

echo "\n";

// 6. 시나리오 평가 로그 확인
echo "6. 시나리오 평가 로그 확인\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

try {
    $scenario_sql = "SELECT 
        id,
        student_id,
        scenario_name,
        evaluation_result,
        evaluated_at
    FROM mdl_alt42_scenario_evaluation_log
    ORDER BY evaluated_at DESC
    LIMIT 5";
    
    $scenarios = $DB->get_records_sql($scenario_sql);
    
    if ($scenarios) {
        echo "  최근 5개 시나리오 평가:\n";
        foreach ($scenarios as $scenario) {
            echo "    [{$scenario->id}] 학생: {$scenario->student_id}\n";
            echo "        시나리오: {$scenario->scenario_name}\n";
            echo "        결과: {$scenario->evaluation_result}\n";
            echo "        평가 시간: " . date('Y-m-d H:i:s', strtotime($scenario->evaluated_at)) . "\n\n";
        }
    } else {
        echo "  ⚠ 시나리오 평가 기록이 없습니다.\n";
    }
} catch (\Exception $e) {
    echo "  ✗ 시나리오 로그 조회 실패: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. 요약
echo "7. 테스트 요약\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$success_count = 0;
$error_count = 0;
foreach ($checks as $check) {
    if ($check[0] === 'success') {
        $success_count++;
    } else {
        $error_count++;
    }
}

echo "  환경 확인: " . ($error_count === 0 ? '✓ ALL OK' : "⚠ {$error_count}개 오류") . "\n";
echo "  데이터베이스: " . ($view_exists ? '✓ VIEW OK' : '✗ VIEW MISSING') . "\n";
echo "  스케줄러 실행: " . (isset($result) && $result['success'] ? '✓ SUCCESS' : '✗ FAILED') . "\n";

echo "\n";
echo "=== 테스트 완료 ===\n";
echo "완료 시간: " . date('Y-m-d H:i:s') . "\n";

echo "</pre></body></html>";

