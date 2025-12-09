<?php
/**
 * 데이터베이스 연결 및 테이블 테스트
 */

// Moodle 설정 포함
require_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER, $CFG;

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$result = [];

try {
    // 1. DB 연결 확인
    $result['db_connected'] = true;
    
    // 2. 테이블 존재 확인
    $tables = [
        'alt42i_pattern_categories',
        'alt42i_math_patterns', 
        'alt42i_pattern_solutions',
        'alt42i_user_pattern_progress',
        'alt42i_pattern_practice_logs',
        'alt42i_pattern_audio_files',
        'alt42i_weekly_pattern_stats'
    ];
    
    $result['tables'] = [];
    foreach ($tables as $table) {
        $exists = $DB->get_manager()->table_exists($table);
        $count = $exists ? $DB->count_records($table) : 0;
        $result['tables'][$table] = [
            'exists' => $exists,
            'count' => $count
        ];
    }
    
    // 3. 간단한 쿼리 테스트
    try {
        $categories = $DB->get_records('alt42i_pattern_categories');
        $result['category_test'] = [
            'success' => true,
            'count' => count($categories)
        ];
    } catch (Exception $e) {
        $result['category_test'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // 4. 패턴 쿼리 테스트
    try {
        $patterns = $DB->get_records('alt42i_math_patterns', null, 'pattern_id ASC', '*', 0, 5);
        $result['pattern_test'] = [
            'success' => true,
            'count' => count($patterns),
            'sample' => array_values($patterns)
        ];
    } catch (Exception $e) {
        $result['pattern_test'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // 5. JOIN 쿼리 테스트
    try {
        $joined = $DB->get_records_sql("
            SELECT p.*, c.category_name
            FROM {alt42i_math_patterns} p
            LEFT JOIN {alt42i_pattern_categories} c ON p.category_id = c.id
            WHERE p.is_active = 1
            LIMIT 5
        ");
        $result['join_test'] = [
            'success' => true,
            'count' => count($joined)
        ];
    } catch (Exception $e) {
        $result['join_test'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    $result['success'] = true;
    
} catch (Exception $e) {
    $result['success'] = false;
    $result['error'] = $e->getMessage();
    $result['trace'] = $e->getTraceAsString();
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>