<?php
/**
 * 진행 상황 테이블 간단 설치
 */

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die('관리자 권한이 필요합니다.');
}

echo "<h2>진행 상황 테이블 설치</h2>";

try {
    // 외래 키 체크 비활성화
    $DB->execute("SET FOREIGN_KEY_CHECKS = 0");
    
    // 테이블 생성
    $sql = "CREATE TABLE IF NOT EXISTS mdl_alt42i_user_pattern_progress (
        id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        user_id INT(10) UNSIGNED NOT NULL,
        pattern_id INT(10) UNSIGNED NOT NULL,
        is_collected TINYINT(1) DEFAULT 0,
        mastery_level INT(3) DEFAULT 0,
        practice_count INT(10) DEFAULT 0,
        last_practice_at DATETIME DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY idx_user_pattern (user_id, pattern_id),
        KEY idx_pattern (pattern_id),
        UNIQUE KEY unique_user_pattern (user_id, pattern_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $DB->execute($sql);
    
    // 외래 키 체크 다시 활성화
    $DB->execute("SET FOREIGN_KEY_CHECKS = 1");
    
    echo "<p style='color: green;'>✅ 테이블이 성공적으로 생성되었습니다!</p>";
    
    // 테이블 확인
    if ($DB->get_manager()->table_exists('alt42i_user_pattern_progress')) {
        $count = $DB->count_records('alt42i_user_pattern_progress');
        echo "<p>현재 레코드 수: $count</p>";
    }
    
    echo "<p><a href='show_math_patterns.php'>수학 인지관성 도감으로 이동</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류: " . $e->getMessage() . "</p>";
}