<?php
/**
 * Migration Runner: 007_create_students_and_biometrics_tables.sql
 * 학생 및 생체신호 테이블 생성 및 프로필 테이블 컬럼 추가
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

echo "=== Migration 007: Students and Biometrics Tables ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// Load SQL file
$sql_file = __DIR__ . '/007_create_students_and_biometrics_tables.sql';
if (!file_exists($sql_file)) {
    die("ERROR: Migration file not found at " . __FILE__ . ":" . __LINE__ . "\n");
}

$sql_content = file_get_contents($sql_file);
echo "✓ Migration SQL loaded (" . strlen($sql_content) . " bytes)\n\n";

// 직접 SQL 문 정의 (파일 파싱 대신)
$create_statements = [
    // 1. Students Table
    "CREATE TABLE IF NOT EXISTS `mdl_alt42_students` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `student_id` VARCHAR(32) NOT NULL UNIQUE COMMENT '학생 ID (mdl_user.id와 매핑)',
        `userid` BIGINT(10) DEFAULT NULL COMMENT 'Moodle user ID reference',
        `grade` INT(2) DEFAULT NULL COMMENT '학년',
        `class` VARCHAR(10) DEFAULT NULL COMMENT '반',
        `mbti` VARCHAR(4) DEFAULT NULL COMMENT 'MBTI 유형',
        `profile_info` LONGTEXT DEFAULT NULL COMMENT '프로필 정보 (JSON)',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간 (Unix timestamp)',
        `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간 (VIEW용)',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42stud_stuid_ix` (`student_id`),
        KEY `mdl_alt42stud_userid_ix` (`userid`),
        KEY `idx_updated_at` (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 student basic information'",
    
    // 2. Student Biometrics Table
    "CREATE TABLE IF NOT EXISTS `mdl_alt42_student_biometrics` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `student_id` VARCHAR(32) NOT NULL COMMENT '학생 ID',
        `stress_level` DECIMAL(5,2) DEFAULT 0.0 COMMENT '스트레스 레벨 (0-10)',
        `concentration_level` DECIMAL(5,2) DEFAULT 5.0 COMMENT '집중도 레벨 (0-10)',
        `heart_rate` INT(11) DEFAULT NULL COMMENT '심박수',
        `bio_data` LONGTEXT DEFAULT NULL COMMENT '생체 데이터 (JSON)',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
        `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42bio_stuid_ix` (`student_id`),
        KEY `idx_stress_level` (`stress_level`),
        KEY `idx_concentration_level` (`concentration_level`),
        KEY `idx_updated_at` (`updated_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 student biometric data'"
];

echo "Found " . count($create_statements) . " CREATE TABLE statements\n\n";

// ALTER TABLE 문 정의 (컬럼 추가)
$alter_statements = [];

$success_count = 0;
$error_count = 0;
$skipped_count = 0;

// CREATE TABLE 문 실행
foreach ($create_statements as $index => $statement) {
    $statement = trim($statement);
    
    // Extract table name for logging
    if (preg_match('/CREATE TABLE.*?IF NOT EXISTS\s+`?(\w+)`?/i', $statement, $matches)) {
        $table_name = $matches[1];
        echo "[$index] Creating table: {$table_name}... ";
        
        try {
            if (isset($DB) && $DB) {
                $DB->execute($statement);
            } else {
                \ALT42\Database\AgentDataLayer::executeQuery($statement);
            }
            
            echo "✓ SUCCESS\n";
            $success_count++;
        } catch (\Exception $e) {
            echo "✗ FAILED\n";
            echo "    Error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
            $error_count++;
        }
    }
}

// ALTER TABLE 문 실행 (컬럼 추가)
$columns_to_add = [
    'student_id' => "VARCHAR(32) COMMENT '학생 ID (VIEW JOIN용)'",
    'emotion_state' => "VARCHAR(20) DEFAULT 'neutral' COMMENT '감정 상태'",
    'immersion_level' => "DECIMAL(5,2) DEFAULT 5.0 COMMENT '몰입도 수준 (0-10)'",
    'engagement_score' => "DECIMAL(5,2) DEFAULT 0.0 COMMENT '참여도 점수 (0-10)'",
    'math_confidence' => "DECIMAL(5,2) DEFAULT 5.0 COMMENT '수학 자신감 (0-10)'"
];

foreach ($columns_to_add as $col_name => $col_definition) {
    echo "[ALTER] Adding column: {$col_name}... ";
    
    try {
        if (isset($DB) && $DB) {
            // Moodle DB 사용 - INFORMATION_SCHEMA로 확인 후 직접 ALTER 실행
            $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'mdl_alt42_student_profiles' 
                          AND COLUMN_NAME = ?";
            $result = $DB->get_record_sql($check_sql, [$col_name]);
            
            if ($result && $result->cnt == 0) {
                // AFTER 절 결정
                $after_clause = '';
                if ($col_name === 'student_id') {
                    $after_clause = ' AFTER `user_id`';
                } elseif ($col_name === 'emotion_state') {
                    $after_clause = ' AFTER `mbti_type`';
                } elseif ($col_name === 'immersion_level') {
                    $after_clause = ' AFTER `emotion_state`';
                } elseif ($col_name === 'engagement_score') {
                    $after_clause = ' AFTER `immersion_level`';
                } elseif ($col_name === 'math_confidence') {
                    $after_clause = ' AFTER `engagement_score`';
                }
                
                $alter_sql = "ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `{$col_name}` {$col_definition}{$after_clause}";
                $DB->execute($alter_sql);
                echo "✓ SUCCESS\n";
                $success_count++;
            } else {
                echo "⚠ SKIPPED (already exists)\n";
                $skipped_count++;
            }
        } else {
            // Standalone DB 사용
            $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                          WHERE TABLE_SCHEMA = DATABASE() 
                          AND TABLE_NAME = 'mdl_alt42_student_profiles' 
                          AND COLUMN_NAME = ?";
            $stmt = \ALT42\Database\AgentDataLayer::executeQuery($check_sql, [$col_name]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if ($result && $result['cnt'] == 0) {
                // AFTER 절 결정
                $after_clause = '';
                if ($col_name === 'student_id') {
                    $after_clause = ' AFTER `user_id`';
                } elseif ($col_name === 'emotion_state') {
                    $after_clause = ' AFTER `mbti_type`';
                } elseif ($col_name === 'immersion_level') {
                    $after_clause = ' AFTER `emotion_state`';
                } elseif ($col_name === 'engagement_score') {
                    $after_clause = ' AFTER `immersion_level`';
                } elseif ($col_name === 'math_confidence') {
                    $after_clause = ' AFTER `engagement_score`';
                }
                
                $alter_sql = "ALTER TABLE `mdl_alt42_student_profiles` ADD COLUMN `{$col_name}` {$col_definition}{$after_clause}";
                \ALT42\Database\AgentDataLayer::executeQuery($alter_sql);
                echo "✓ SUCCESS\n";
                $success_count++;
            } else {
                echo "⚠ SKIPPED (already exists)\n";
                $skipped_count++;
            }
        }
    } catch (\Exception $e) {
        $error_msg = $e->getMessage();
        if (strpos($error_msg, 'Duplicate column') !== false || 
            strpos($error_msg, 'already exists') !== false ||
            strpos($error_msg, 'Duplicate entry') !== false) {
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

// exit() 제거 - include로 호출될 때 전체 스크립트가 종료되지 않도록
// if ($error_count > 0) {
//     exit(1);
// }
// exit(0);

