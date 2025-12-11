<?php
// ktm_mathmessages 테이블 수정 스크립트
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $CFG;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h2>ktm_mathmessages 테이블 수정</h2>";

try {
    // 테이블이 존재하는지 확인
    $dbman = $DB->get_manager();
    if ($dbman->table_exists('ktm_mathmessages')) {
        echo "<p>✓ ktm_mathmessages 테이블이 존재합니다.</p>";
        
        // TEXT 필드를 LONGTEXT로 변경
        $sql = "ALTER TABLE {$CFG->prefix}ktm_mathmessages 
                MODIFY COLUMN message_content LONGTEXT NOT NULL,
                MODIFY COLUMN solution_text LONGTEXT DEFAULT NULL";
        
        try {
            $DB->execute($sql);
            echo "<p>✓ TEXT 필드를 LONGTEXT로 변경했습니다.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ 필드 타입 변경 실패: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ ktm_mathmessages 테이블이 존재하지 않습니다.</p>";
        
        // 테이블 생성
        $sql = "CREATE TABLE IF NOT EXISTS {$CFG->prefix}ktm_mathmessages (
            id BIGINT(10) NOT NULL AUTO_INCREMENT,
            teacher_id BIGINT(10) NOT NULL,
            student_id BIGINT(10) NOT NULL,
            interaction_id BIGINT(10) DEFAULT NULL,
            subject VARCHAR(255) NOT NULL DEFAULT '하이튜터링 문제 해설',
            message_content LONGTEXT NOT NULL,
            solution_text LONGTEXT DEFAULT NULL,
            audio_url VARCHAR(500) DEFAULT NULL,
            explanation_url VARCHAR(500) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            timecreated BIGINT(10) NOT NULL,
            timeread BIGINT(10) DEFAULT NULL,
            PRIMARY KEY (id),
            INDEX idx_student_id (student_id),
            INDEX idx_teacher_id (teacher_id),
            INDEX idx_interaction_id (interaction_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        try {
            $DB->execute($sql);
            echo "<p>✓ ktm_mathmessages 테이블을 생성했습니다.</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ 테이블 생성 실패: " . $e->getMessage() . "</p>";
        }
    }
    
    // 테이블 구조 확인
    $columns = $DB->get_columns('ktm_mathmessages');
    echo "<h3>현재 테이블 구조:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>필드명</th><th>타입</th><th>NULL 허용</th><th>기본값</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column->name}</td>";
        echo "<td>{$column->type} ({$column->max_length})</td>";
        echo "<td>" . ($column->not_null ? 'NO' : 'YES') . "</td>";
        echo "<td>{$column->default_value}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류 발생: " . $e->getMessage() . "</p>";
}

echo "<p><a href='teachingagent.php'>하이튜터링으로 돌아가기</a></p>";
?>