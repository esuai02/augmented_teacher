<?php
/**
 * Migration Runner: 004_create_base_tables.sql
 * Heartbeat VIEW를 위한 기본 테이블 생성
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

echo "=== Migration 004: Base Tables for Heartbeat VIEW ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Load SQL file
$sql_file = __DIR__ . '/004_create_base_tables.sql';
if (!file_exists($sql_file)) {
    die("ERROR: Migration file not found at " . __FILE__ . ":" . __LINE__ . "\n");
}

$sql_content = file_get_contents($sql_file);
echo "✓ Migration SQL loaded (" . strlen($sql_content) . " bytes)\n\n";

// SQL 문을 세미콜론으로 분리 (간단한 파싱)
$statements = array();
$current_statement = '';
$in_prepare = false;

$lines = explode("\n", $sql_content);
foreach ($lines as $line) {
    $line = trim($line);
    
    // 주석 건너뛰기
    if (empty($line) || strpos($line, '--') === 0) {
        continue;
    }
    
    // PREPARE 문 시작 감지
    if (stripos($line, 'PREPARE') !== false) {
        $in_prepare = true;
    }
    
    $current_statement .= $line . "\n";
    
    // 세미콜론으로 문장 종료 (PREPARE 블록 제외)
    if (!$in_prepare && substr(rtrim($line), -1) === ';') {
        $statement = trim($current_statement);
        if (!empty($statement)) {
            $statements[] = $statement;
        }
        $current_statement = '';
    }
    
    // DEALLOCATE로 PREPARE 블록 종료
    if ($in_prepare && stripos($line, 'DEALLOCATE') !== false) {
        $statement = trim($current_statement);
        if (!empty($statement)) {
            $statements[] = $statement;
        }
        $current_statement = '';
        $in_prepare = false;
    }
}

// 마지막 문장 처리
if (!empty(trim($current_statement))) {
    $statements[] = trim($current_statement);
}

echo "Found " . count($statements) . " SQL statements\n\n";

$success_count = 0;
$error_count = 0;
$skipped_count = 0;

foreach ($statements as $index => $statement) {
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    // Extract object name for logging
    $object_name = 'Unknown';
    if (preg_match('/CREATE TABLE.*?IF NOT EXISTS\s+`?(\w+)`?/i', $statement, $matches)) {
        $object_name = "TABLE {$matches[1]}";
        echo "[$index] Creating {$object_name}... ";
    } elseif (preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
        $object_name = "ALTER TABLE {$matches[1]}";
        echo "[$index] Executing {$object_name}... ";
    } elseif (preg_match('/PREPARE.*?FROM/i', $statement)) {
        $object_name = "PREPARE statement";
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
                throw new \Exception("Moodle DB Error: " . $moodle_e->getMessage() . " | Debug: " . (isset($moodle_e->debuginfo) ? $moodle_e->debuginfo : ''));
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
            strpos($error_msg, 'Column') !== false && strpos($error_msg, 'already exists') !== false) {
            echo "⚠ SKIPPED (already exists)\n";
            $skipped_count++;
        } else {
            echo "✗ FAILED\n";
            echo "    Error: " . $error_msg . " at " . __FILE__ . ":" . __LINE__ . "\n";
            $error_count++;
        }
    }
}

echo "\n=== Migration Summary ===\n";
echo "Success: {$success_count}\n";
echo "Skipped: {$skipped_count}\n";
echo "Errors: {$error_count}\n";
echo "Completed at " . date('Y-m-d H:i:s') . "\n";

