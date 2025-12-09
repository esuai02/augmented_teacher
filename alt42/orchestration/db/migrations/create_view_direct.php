<?php
/**
 * VIEW 직접 생성 스크립트
 * mdl_alt42_v_student_state VIEW 생성 및 검증
 * 
 * @package ALT42\Database\Migrations
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Moodle config 로드
require_once('/home/moodle/public_html/moodle/config.php');
global $DB;

echo "=== VIEW 직접 생성 및 검증 ===\n";
echo "Starting at " . date('Y-m-d H:i:s') . "\n\n";

// 1. 참조 테이블 확인
echo "1. 참조 테이블 확인 중...\n";
$required_tables = array(
    'mdl_user',
    'mdl_alt42_students',
    'mdl_alt42_student_profiles',
    'mdl_alt42_student_biometrics'
);

foreach ($required_tables as $table) {
    try {
        $check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
        $result = $DB->get_record_sql($check_sql, [$table]);
        if ($result && $result->cnt > 0) {
            echo "  ✓ {$table}: EXISTS\n";
        } else {
            echo "  ✗ {$table}: NOT FOUND\n";
        }
    } catch (\Exception $e) {
        echo "  ✗ {$table}: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// 2. VIEW 존재 여부 확인
echo "2. VIEW 존재 여부 확인 중...\n";
try {
    $check_view_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.VIEWS 
                       WHERE TABLE_SCHEMA = DATABASE() 
                       AND TABLE_NAME = 'mdl_alt42_v_student_state'";
    $view_result = $DB->get_record_sql($check_view_sql);
    
    if ($view_result && $view_result->cnt > 0) {
        echo "  ✓ VIEW already exists\n";
        
        // VIEW 구조 확인
        echo "\n3. VIEW 구조 확인 중...\n";
        try {
            $describe_sql = "DESCRIBE mdl_alt42_v_student_state";
            $columns = $DB->get_records_sql($describe_sql);
            foreach ($columns as $col) {
                echo "  - {$col->Field} ({$col->Type})\n";
            }
        } catch (\Exception $e) {
            echo "  ✗ Error describing VIEW: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ✗ VIEW does not exist - will create\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Error checking VIEW: " . $e->getMessage() . "\n";
}

echo "\n";

// 3. VIEW 생성/재생성
echo "4. VIEW 생성/재생성 중...\n";

// 기존 VIEW 삭제 (있으면)
try {
    $drop_sql = "DROP VIEW IF EXISTS `mdl_alt42_v_student_state`";
    $DB->execute($drop_sql);
    echo "  ✓ Dropped existing VIEW (if any)\n";
} catch (\Exception $e) {
    echo "  ⚠ Could not drop VIEW: " . $e->getMessage() . "\n";
}

// VIEW 생성
$create_view_sql = "CREATE VIEW `mdl_alt42_v_student_state` AS
SELECT 
    COALESCE(s.student_id, CAST(u.id AS CHAR)) AS student_id,
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
LEFT JOIN mdl_alt42_students s ON u.id = CAST(s.student_id AS UNSIGNED)
LEFT JOIN mdl_alt42_student_profiles sp ON COALESCE(CAST(s.student_id AS UNSIGNED), u.id) = COALESCE(CAST(sp.student_id AS UNSIGNED), sp.user_id)
LEFT JOIN mdl_alt42_student_biometrics sb ON COALESCE(CAST(s.student_id AS UNSIGNED), u.id) = CAST(sb.student_id AS UNSIGNED)
WHERE u.deleted = 0";

try {
    $DB->execute($create_view_sql);
    echo "  ✓ VIEW created successfully\n";
} catch (\Exception $e) {
    echo "  ✗ VIEW creation failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    
    // 간단한 버전으로 재시도
    echo "\n5. 간단한 버전으로 재시도 중...\n";
    $simple_view_sql = "CREATE VIEW `mdl_alt42_v_student_state` AS
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
    NOW() AS updated_at
FROM mdl_user u
LEFT JOIN mdl_alt42_students s ON u.id = s.student_id
LEFT JOIN mdl_alt42_student_profiles sp ON u.id = sp.user_id
LEFT JOIN mdl_alt42_student_biometrics sb ON u.id = sb.student_id
WHERE u.deleted = 0";
    
    try {
        $DB->execute($simple_view_sql);
        echo "  ✓ Simple VIEW created successfully\n";
    } catch (\Exception $e2) {
        echo "  ✗ Simple VIEW creation also failed: " . $e2->getMessage() . " at " . __FILE__ . ":" . __LINE__ . "\n";
    }
}

echo "\n";

// 4. 최종 확인
echo "6. 최종 확인 중...\n";
try {
    $final_check_sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.VIEWS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'mdl_alt42_v_student_state'";
    $final_result = $DB->get_record_sql($final_check_sql);
    
    if ($final_result && $final_result->cnt > 0) {
        echo "  ✓ VIEW exists and is accessible\n";
        
        // 테스트 쿼리 실행
        try {
            $test_sql = "SELECT COUNT(*) as cnt FROM mdl_alt42_v_student_state LIMIT 1";
            $test_result = $DB->get_record_sql($test_sql);
            echo "  ✓ VIEW query test successful (found " . ($test_result->cnt ?? 0) . " rows)\n";
        } catch (\Exception $e) {
            echo "  ✗ VIEW query test failed: " . $e->getMessage() . "\n";
        }
    } else {
        echo "  ✗ VIEW still does not exist\n";
    }
} catch (\Exception $e) {
    echo "  ✗ Final check failed: " . $e->getMessage() . "\n";
}

echo "\n=== 완료 ===\n";
echo "Completed at " . date('Y-m-d H:i:s') . "\n";

