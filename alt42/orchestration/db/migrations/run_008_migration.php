<?php
/**
 * Migration Runner: 008_create_learning_sessions_and_verify_view.sql
 * learning_sessions 테이블 생성 및 VIEW 검증
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

echo "=== Migration 008: Learning Sessions Table and VIEW Verification ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

$success_count = 0;
$error_count = 0;
$skipped_count = 0;

// 1. Learning Sessions Table 생성
$create_learning_sessions = "CREATE TABLE IF NOT EXISTS `mdl_alt42_learning_sessions` (
    `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
    `session_id` VARCHAR(32) NOT NULL UNIQUE COMMENT '세션 ID',
    `student_id` VARCHAR(32) NOT NULL COMMENT '학생 ID',
    `start_time` BIGINT(10) NOT NULL COMMENT '시작 시간 (Unix timestamp)',
    `end_time` BIGINT(10) DEFAULT NULL COMMENT '종료 시간 (Unix timestamp)',
    `session_end` TIMESTAMP NULL DEFAULT NULL COMMENT '세션 종료 시간 (VIEW용)',
    `activity_type` VARCHAR(50) DEFAULT NULL COMMENT '활동 유형',
    `completion_rate` DECIMAL(5,2) DEFAULT NULL COMMENT '완료율 (%)',
    `engagement_time` INT(11) DEFAULT NULL COMMENT '참여 시간 (초)',
    `focus_time` INT(11) DEFAULT NULL COMMENT '집중 시간 (초)',
    `performance` DECIMAL(5,2) DEFAULT NULL COMMENT '성과 점수',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',
    `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '업데이트 시간',
    PRIMARY KEY (`id`),
    KEY `mdl_alt42sess_sid_ix` (`session_id`),
    KEY `mdl_alt42sess_stuid_ix` (`student_id`),
    KEY `mdl_alt42sess_start_ix` (`start_time`),
    KEY `idx_end_time` (`end_time`),
    KEY `idx_session_end` (`session_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ALT42 learning session records'";

echo "[0] Creating table: mdl_alt42_learning_sessions... ";

try {
    if (isset($DB) && $DB) {
        $DB->execute($create_learning_sessions);
    } else {
        \ALT42\Database\AgentDataLayer::executeQuery($create_learning_sessions);
    }
    echo "✓ SUCCESS\n";
    $success_count++;
} catch (\Exception $e) {
    $error_msg = $e->getMessage();
    if (strpos($error_msg, 'already exists') !== false || 
        strpos($error_msg, 'Duplicate') !== false) {
        echo "⚠ SKIPPED (already exists)\n";
        $skipped_count++;
    } else {
        echo "✗ FAILED\n";
        echo "    Error: " . $error_msg . " at " . __FILE__ . ":" . __LINE__ . "\n";
        $error_count++;
    }
}

// 2. VIEW 재생성 (확인 및 업데이트)
$create_view = "CREATE OR REPLACE VIEW `mdl_alt42_v_student_state` AS
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

echo "[1] Creating/Updating VIEW: mdl_alt42_v_student_state... ";

try {
    if (isset($DB) && $DB) {
        $DB->execute($create_view);
    } else {
        \ALT42\Database\AgentDataLayer::executeQuery($create_view);
    }
    echo "✓ SUCCESS\n";
    $success_count++;
} catch (\Exception $e) {
    echo "✗ FAILED\n";
    echo "    Error: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    $error_count++;
}

// 3. session_end 컬럼 추가 (없으면)
if (isset($DB) && $DB) {
    $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS 
                  WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'mdl_alt42_learning_sessions' 
                  AND COLUMN_NAME = 'session_end'";
    $result = $DB->get_record_sql($check_sql);
    
    if ($result && $result->cnt == 0) {
        echo "[2] Adding column: session_end to mdl_alt42_learning_sessions... ";
        try {
            $alter_sql = "ALTER TABLE `mdl_alt42_learning_sessions` ADD COLUMN `session_end` TIMESTAMP NULL DEFAULT NULL COMMENT '세션 종료 시간 (VIEW용)' AFTER `end_time`";
            $DB->execute($alter_sql);
            echo "✓ SUCCESS\n";
            $success_count++;
        } catch (\Exception $e) {
            $error_msg = $e->getMessage();
            if (strpos($error_msg, 'Duplicate column') !== false || 
                strpos($error_msg, 'already exists') !== false) {
                echo "⚠ SKIPPED (already exists)\n";
                $skipped_count++;
            } else {
                echo "✗ FAILED\n";
                echo "    Error: " . $error_msg . " at " . __FILE__ . ":" . __LINE__ . "\n";
                $error_count++;
            }
        }
    } else {
        echo "[2] Column session_end already exists... ⚠ SKIPPED\n";
        $skipped_count++;
    }
}

echo "\n=== Migration Summary ===\n";
echo "Success: {$success_count}\n";
echo "Skipped: {$skipped_count}\n";
echo "Errors: {$error_count}\n";
echo "Completed at " . date('Y-m-d H:i:s') . "\n";

