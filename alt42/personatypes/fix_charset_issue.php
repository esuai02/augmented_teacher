<?php
/**
 * 문자셋 문제 해결 스크립트
 * 이모지를 저장할 수 있도록 테이블 문자셋을 utf8mb4로 변경
 */

require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $CFG;

require_login();

// 관리자 권한 확인
$context = context_system::instance();
require_capability('moodle/site:config', $context);

header('Content-Type: text/html; charset=utf-8');

echo "<h1>문자셋 수정 스크립트</h1>";

try {
    // 테이블 문자셋 변경
    $tables = [
        'alt42i_math_patterns',
        'alt42i_pattern_categories',
        'alt42i_pattern_solutions',
        'alt42i_audio_files'
    ];
    
    foreach ($tables as $table) {
        $sql = "ALTER TABLE {$CFG->prefix}{$table} 
                CONVERT TO CHARACTER SET utf8mb4 
                COLLATE utf8mb4_unicode_ci";
        
        try {
            $DB->execute($sql);
            echo "<p style='color: green;'>✓ {$table} 테이블 문자셋 변경 완료</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ {$table} 테이블 변경 실패: " . $e->getMessage() . "</p>";
        }
    }
    
    // icon 컬럼만 별도로 변경
    $sql = "ALTER TABLE {$CFG->prefix}alt42i_math_patterns 
            MODIFY COLUMN icon VARCHAR(10) 
            CHARACTER SET utf8mb4 
            COLLATE utf8mb4_unicode_ci";
    
    try {
        $DB->execute($sql);
        echo "<p style='color: green;'>✓ icon 컬럼 문자셋 변경 완료</p>";
    } catch (Exception $e) {
        echo "<p style='color: orange;'>⚠ icon 컬럼 변경 실패: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<p><strong>문자셋 변경이 완료되었습니다.</strong></p>";
    echo "<p>이제 <a href='insert_60_personas_data.php'>데이터 삽입 페이지</a>로 돌아가서 다시 시도해보세요.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>오류 발생: " . $e->getMessage() . "</p>";
}
?>