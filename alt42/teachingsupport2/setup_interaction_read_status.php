<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die('관리자 권한이 필요합니다.');
}

echo "<h2>ktm_interaction_read_status 테이블 설정</h2>";

$dbman = $DB->get_manager();

// 테이블이 이미 존재하는지 확인
if ($dbman->table_exists('ktm_interaction_read_status')) {
    echo "<p style='color: green;'>✅ ktm_interaction_read_status 테이블이 이미 존재합니다.</p>";
} else {
    try {
        // SQL로 직접 테이블 생성
        $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_interaction_read_status (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            interaction_id BIGINT(10) NOT NULL,
            student_id BIGINT(10) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            timeread BIGINT(10) DEFAULT NULL,
            timecreated BIGINT(10) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY interaction_student_unique (interaction_id, student_id),
            KEY interaction_idx (interaction_id),
            KEY student_idx (student_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        $DB->execute($sql);
        echo "<p style='color: green;'>✅ ktm_interaction_read_status 테이블을 생성했습니다.</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 테이블 생성 실패: " . $e->getMessage() . "</p>";
    }
}

// 테이블 구조 확인
if ($dbman->table_exists('ktm_interaction_read_status')) {
    echo "<h3>테이블 구조:</h3>";
    $columns = $DB->get_columns('ktm_interaction_read_status');
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // 레코드 개수 확인
    $count = $DB->count_records('ktm_interaction_read_status');
    echo "<p>현재 레코드 개수: $count</p>";
}

echo "<hr>";
echo "<p><a href='student_inbox.php?studentid=817&userid=2'>학생 메시지함으로 돌아가기</a></p>";
?>