<?php
// WebVTT 필드 추가 스크립트
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $CFG;
require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

echo "<h2>ktm_teaching_interactions 테이블에 WebVTT 필드 추가</h2>";

try {
    $dbman = $DB->get_manager();
    
    if ($dbman->table_exists('ktm_teaching_interactions')) {
        echo "<p>✓ ktm_teaching_interactions 테이블이 존재합니다.</p>";
        
        // 필드가 이미 존재하는지 확인
        $columns = $DB->get_columns('ktm_teaching_interactions');
        if (isset($columns['webvtt_data'])) {
            echo "<p style='color: orange;'>⚠ webvtt_data 필드가 이미 존재합니다.</p>";
        } else {
            // WebVTT 필드 추가
            $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions 
                    ADD COLUMN webvtt_data LONGTEXT DEFAULT NULL COMMENT 'WebVTT 형식의 타이밍 데이터' 
                    AFTER narration_text";
            
            try {
                $DB->execute($sql);
                echo "<p>✓ webvtt_data 필드를 추가했습니다.</p>";
                
                // 인덱스 추가
                $indexSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions 
                            ADD INDEX idx_has_webvtt ((CASE WHEN webvtt_data IS NOT NULL THEN 1 ELSE 0 END))";
                
                try {
                    $DB->execute($indexSql);
                    echo "<p>✓ WebVTT 존재 여부 인덱스를 추가했습니다.</p>";
                } catch (Exception $e) {
                    echo "<p style='color: orange;'>⚠ 인덱스 추가 실패 (이미 존재할 수 있음): " . $e->getMessage() . "</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ 필드 추가 실패: " . $e->getMessage() . "</p>";
            }
        }
        
        // 현재 테이블 구조 표시
        $columns = $DB->get_columns('ktm_teaching_interactions');
        echo "<h3>현재 테이블 구조 (주요 필드):</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>필드명</th><th>타입</th><th>NULL 허용</th></tr>";
        
        $importantFields = ['id', 'userid', 'problem_image', 'solution_text', 'narration_text', 'webvtt_data', 'audio_url'];
        foreach ($columns as $column) {
            if (in_array($column->name, $importantFields)) {
                echo "<tr>";
                echo "<td>{$column->name}</td>";
                echo "<td>{$column->type} " . ($column->max_length ? "({$column->max_length})" : "") . "</td>";
                echo "<td>" . ($column->not_null ? 'NO' : 'YES') . "</td>";
                echo "</tr>";
            }
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>✗ ktm_teaching_interactions 테이블이 존재하지 않습니다.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류 발생: " . $e->getMessage() . "</p>";
}

echo "<p><a href='teachingagent.php'>하이튜터링으로 돌아가기</a></p>";
?>