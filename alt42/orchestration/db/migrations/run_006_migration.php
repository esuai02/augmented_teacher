<?php
/**
 * Migration Runner: 006_create_heartbeat_views.sql
 * Heartbeat scheduler를 위한 뷰 및 테이블 생성
 * 
 * @package ALT42\Database\Migrations
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// AgentDataLayer 로드 (필요시 사용)
require_once(__DIR__ . '/../../api/database/agent_data_layer.php');

// Moodle config 체크 (있으면 사용, 없으면 독립 모드)
$moodle_available = file_exists('/home/moodle/public_html/moodle/config.php');
if ($moodle_available) {
    require_once('/home/moodle/public_html/moodle/config.php');
    global $DB;
}

echo "=== Migration 006: Heartbeat Views and Tables ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Load SQL file
$sql_file = __DIR__ . '/006_create_heartbeat_views.sql';
if (!file_exists($sql_file)) {
    die("ERROR: Migration file not found at " . __FILE__ . ":" . __LINE__ . "\n");
}

$sql_content = file_get_contents($sql_file);
echo "✓ Migration SQL loaded (" . strlen($sql_content) . " bytes)\n\n";

// VIEW 생성 전 참조 테이블 확인
$view_tables_exist = true;
if (isset($DB) && $DB) {
    $required_tables = array('mdl_user', 'mdl_alt42_students', 'mdl_alt42_student_profiles', 'mdl_alt42_student_biometrics');
    foreach ($required_tables as $table) {
        try {
            $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'";
            $result = $DB->get_record_sql($check_sql);
            if (!$result || $result->cnt == 0) {
                echo "⚠ Warning: Table {$table} does not exist. VIEW creation will be skipped.\n";
                $view_tables_exist = false;
                break;
            }
        } catch (\Exception $e) {
            echo "⚠ Warning: Could not check table {$table}: " . $e->getMessage() . "\n";
            $view_tables_exist = false;
            break;
        }
    }
}

// 직접 SQL 문 정의
$statements = array();

// VIEW는 참조 테이블이 모두 존재할 때만 추가
if ($view_tables_exist) {
    $statements[] = "CREATE OR REPLACE VIEW `mdl_alt42_v_student_state` AS
    SELECT 
        COALESCE(s.student_id, u.id) AS student_id,
        s.mbti,
        s.grade,
        s.class,
        COALESCE(sp.emotion_state, 'neutral') AS emotion_state,
        COALESCE(sp.immersion_level, 5.0) AS immersion_level,
        COALESCE(sb.stress_level, 0.0) AS stress_level,
        COALESCE(sb.concentration_level, 5.0) AS concentration_level,
        COALESCE(sp.engagement_score, 0.0) AS engagement_score,
        COALESCE(sp.math_confidence, 5.0) AS math_confidence,
        GREATEST(
            COALESCE(sp.updated_at, '1970-01-01 00:00:00'),
            COALESCE(sb.updated_at, '1970-01-01 00:00:00'),
            COALESCE(s.updated_at, '1970-01-01 00:00:00')
        ) AS updated_at
    FROM mdl_user u
    LEFT JOIN mdl_alt42_students s ON u.id = s.student_id
    LEFT JOIN mdl_alt42_student_profiles sp ON COALESCE(s.student_id, u.id) = COALESCE(sp.student_id, sp.user_id)
    LEFT JOIN mdl_alt42_student_biometrics sb ON COALESCE(s.student_id, u.id) = sb.student_id
    WHERE u.deleted = 0";
} else {
    echo "⚠ Skipping VIEW creation - required tables missing\n";
}

// 2. Student Activity Table (항상 생성)
$statements[] = "CREATE TABLE IF NOT EXISTS `mdl_alt42_student_activity` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `student_id` VARCHAR(20) NOT NULL COMMENT '학생 ID',
        `activity_type` VARCHAR(50) COMMENT '활동 유형',
        `activity_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '활동 날짜',
        `activity_data` JSON COMMENT '활동 데이터',
        INDEX `idx_student_id` (`student_id`),
        INDEX `idx_activity_date` (`activity_date`),
        INDEX `idx_student_activity_date` (`student_id`, `activity_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 활동 로그 (Heartbeat fallback용)'";

echo "Found " . count($statements) . " SQL statements\n\n";

// session_end 컬럼 추가는 별도 처리
$alter_statements = array();

// 테이블 존재 여부 먼저 확인
$learning_sessions_table_exists = false;
if (isset($DB) && $DB) {
    try {
        $table_check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                           WHERE TABLE_SCHEMA = DATABASE() 
                           AND TABLE_NAME = 'mdl_alt42_learning_sessions'";
        $table_result = $DB->get_record_sql($table_check_sql);
        if ($table_result && $table_result->cnt > 0) {
            $learning_sessions_table_exists = true;
        } else {
            echo "⚠ Table mdl_alt42_learning_sessions does not exist. ALTER TABLE will be skipped.\n";
        }
    } catch (\Exception $e) {
        echo "⚠ Could not check table mdl_alt42_learning_sessions: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    }
}

// session_end 컬럼이 있는지 확인하고 추가
if ($learning_sessions_table_exists) {
    try {
        if (isset($DB) && $DB) {
            // Moodle DB 사용
            $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'mdl_alt42_learning_sessions' 
                          AND COLUMN_NAME = 'session_end'";
            try {
                $result = $DB->get_record_sql($check_sql);
                if ($result && $result->cnt == 0) {
                    // end_time 컬럼 존재 여부 확인
                    $end_time_check = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                                      WHERE TABLE_SCHEMA = DATABASE() 
                                      AND TABLE_NAME = 'mdl_alt42_learning_sessions' 
                                      AND COLUMN_NAME = 'end_time'";
                    $end_time_result = $DB->get_record_sql($end_time_check);
                    
                    if ($end_time_result && $end_time_result->cnt > 0) {
                        $alter_statements[] = "ALTER TABLE `mdl_alt42_learning_sessions` 
                                               ADD COLUMN `session_end` TIMESTAMP NULL COMMENT '세션 종료 시간' AFTER `end_time`";
                    } else {
                        $alter_statements[] = "ALTER TABLE `mdl_alt42_learning_sessions` 
                                               ADD COLUMN `session_end` TIMESTAMP NULL COMMENT '세션 종료 시간'";
                    }
                    $alter_statements[] = "ALTER TABLE `mdl_alt42_learning_sessions` 
                                           ADD INDEX `idx_session_end` (`session_end`)";
                }
            } catch (\Exception $e) {
                echo "⚠ 컬럼 확인 중 오류 (무시됨): " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
            }
        } else {
            // Standalone DB 사용
            if (class_exists('ALT42\Database\AgentDataLayer')) {
                $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'mdl_alt42_learning_sessions' 
                              AND COLUMN_NAME = 'session_end'";
                try {
                    $stmt = \ALT42\Database\AgentDataLayer::executeQuery($check_sql);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($result && $result['cnt'] == 0) {
                        $alter_statements[] = "ALTER TABLE `mdl_alt42_learning_sessions` 
                                               ADD COLUMN `session_end` TIMESTAMP NULL COMMENT '세션 종료 시간' AFTER `end_time`";
                        $alter_statements[] = "ALTER TABLE `mdl_alt42_learning_sessions` 
                                               ADD INDEX `idx_session_end` (`session_end`)";
                    }
                } catch (\Exception $e) {
                    echo "⚠ 컬럼 확인 중 오류 (무시됨): " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
                }
            }
        }
    } catch (\Exception $e) {
        echo "⚠ ALTER TABLE 준비 중 오류 (무시됨): " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    }
}

// ALTER 문을 statements에 추가
$statements = array_merge($statements, $alter_statements);

$success_count = 0;
$error_count = 0;
$skipped_count = 0;

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    
    // Extract object name for logging
    $object_name = 'Unknown';
    if (preg_match('/CREATE\s+(OR\s+REPLACE\s+)?VIEW\s+`?(\w+)`?/i', $statement, $matches)) {
        $object_name = "VIEW {$matches[2]}";
        echo "[$index] Creating {$object_name}... ";
    } elseif (preg_match('/CREATE TABLE.*?IF NOT EXISTS\s+`?(\w+)`?/i', $statement, $matches)) {
        $object_name = "TABLE {$matches[1]}";
        echo "[$index] Creating {$object_name}... ";
    } elseif (preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
        $object_name = "ALTER TABLE {$matches[1]}";
        echo "[$index] Executing {$object_name}... ";
    } else {
        echo "[$index] Executing statement... ";
    }
    
    try {
        if (isset($DB) && $DB) {
            // Moodle DB 사용
            try {
                // Moodle의 execute_sql 메서드 사용 (더 나은 에러 처리)
                if (method_exists($DB, 'execute_sql')) {
                    $DB->execute_sql($statement);
                } else {
                    // execute_sql이 없으면 execute 사용
                    $DB->execute($statement);
                }
                
                // 에러 확인 (Moodle DB는 예외를 던지지 않을 수 있음)
                $last_error = null;
                if (method_exists($DB, 'get_last_error')) {
                    $last_error = $DB->get_last_error();
                } elseif (method_exists($DB, 'get_error')) {
                    $last_error = $DB->get_error();
                }
                
                if ($last_error) {
                    throw new \Exception($last_error);
                }
            } catch (\moodle_exception $moodle_e) {
                // Moodle 예외 처리
                throw new \Exception("Moodle DB Error: " . $moodle_e->getMessage() . " | Debug: " . $moodle_e->debuginfo);
            } catch (PDOException $pdo_e) {
                throw new \Exception("PDO Error: " . $pdo_e->getMessage() . " | Code: " . $pdo_e->getCode());
            } catch (\Exception $e) {
                // 다른 예외는 그대로 전달
                throw $e;
            }
        } else {
            // Standalone DB 사용
            if (class_exists('ALT42\Database\AgentDataLayer')) {
                \ALT42\Database\AgentDataLayer::executeQuery($statement);
            } else {
                throw new \Exception("AgentDataLayer class not found at " . __FILE__ . ":" . __LINE__);
            }
        }
        
        echo "✓ SUCCESS\n";
        $success_count++;
    } catch (\Exception $e) {
        // 이미 존재하는 경우는 스킵
        $error_msg = $e->getMessage();
        
        // 더 자세한 에러 정보 출력
        if (isset($DB) && $DB && method_exists($DB, 'get_last_error')) {
            $db_error = $DB->get_last_error();
            if ($db_error) {
                $error_msg = $db_error . " | " . $error_msg;
            }
        }
        
        if (strpos($error_msg, 'Duplicate key') !== false || 
            strpos($error_msg, 'already exists') !== false ||
            strpos($error_msg, 'Duplicate column') !== false ||
            strpos($error_msg, 'Duplicate entry') !== false ||
            strpos($error_msg, 'Table') !== false && strpos($error_msg, "doesn't exist") !== false) {
            echo "⚠ SKIPPED (already exists or table missing)\n";
            if (strpos($error_msg, "doesn't exist") !== false) {
                echo "    Note: Referenced table may not exist yet\n";
            }
            $skipped_count++;
        } else {
            echo "✗ FAILED\n";
            echo "    Error: " . $error_msg . " at " . __FILE__ . ":" . __LINE__ . "\n";
            
            // VIEW 생성 실패 시 참조 테이블 확인
            if (preg_match('/CREATE\s+(OR\s+REPLACE\s+)?VIEW/i', $statement)) {
                echo "    Checking referenced tables...\n";
                $tables_to_check = array('mdl_user', 'mdl_alt42_students', 'mdl_alt42_student_profiles', 'mdl_alt42_student_biometrics');
                foreach ($tables_to_check as $table) {
                    try {
                        if (isset($DB) && $DB) {
                            $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                                         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'";
                            $result = $DB->get_record_sql($check_sql);
                            if ($result && $result->cnt > 0) {
                                echo "      ✓ {$table} exists\n";
                            } else {
                                echo "      ✗ {$table} NOT FOUND\n";
                            }
                        }
                    } catch (\Exception $check_e) {
                        echo "      ? {$table} check failed: " . $check_e->getMessage() . "\n";
                    }
                }
            }
            
            $error_count++;
        }
    }
}

echo "\n=== Migration Summary ===\n";
echo "Success: {$success_count}\n";
echo "Skipped: {$skipped_count}\n";
echo "Errors: {$error_count}\n";
echo "Completed at " . date('Y-m-d H:i:s') . "\n";

// exit() 제거 - include로 호출될 때 전체 스크립트가 종료되지 않도록
// if ($error_count > 0) {
//     exit(1);
// }
// exit(0);

