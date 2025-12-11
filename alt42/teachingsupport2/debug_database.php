<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // 데이터베이스 테이블 정보 수집
    $debug_info = array();
    
    // 1. 기본 테이블 존재 확인
    $tables = $DB->get_tables();
    $debug_info['all_tables_count'] = count($tables);
    
    // 2. 메시지 관련 테이블 찾기
    $message_tables = array();
    foreach ($tables as $table) {
        if (strpos($table, 'message') !== false) {
            $message_tables[] = $table;
        }
    }
    $debug_info['message_tables'] = $message_tables;
    
    // 3. 사용자 테이블 확인
    $user_tables = array();
    foreach ($tables as $table) {
        if (strpos($table, 'user') !== false) {
            $user_tables[] = $table;
        }
    }
    $debug_info['user_tables'] = $user_tables;
    
    // 4. 특정 테이블 구조 확인
    $table_structures = array();
    
    // message 테이블 구조 확인
    if (in_array('message', $tables)) {
        try {
            $columns = $DB->get_columns('message');
            $table_structures['message'] = array_keys($columns);
        } catch (Exception $e) {
            $table_structures['message'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // messages 테이블 구조 확인
    if (in_array('messages', $tables)) {
        try {
            $columns = $DB->get_columns('messages');
            $table_structures['messages'] = array_keys($columns);
        } catch (Exception $e) {
            $table_structures['messages'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // user 테이블 구조 확인
    if (in_array('user', $tables)) {
        try {
            $columns = $DB->get_columns('user');
            $table_structures['user'] = array_keys($columns);
        } catch (Exception $e) {
            $table_structures['user'] = 'Error: ' . $e->getMessage();
        }
    }
    
    // message_read 테이블 구조 확인
    if (in_array('message_read', $tables)) {
        try {
            $columns = $DB->get_columns('message_read');
            $table_structures['message_read'] = array_keys($columns);
        } catch (Exception $e) {
            $table_structures['message_read'] = 'Error: ' . $e->getMessage();
        }
    }
    
    $debug_info['table_structures'] = $table_structures;
    
    // 5. 간단한 쿼리 테스트
    $query_tests = array();
    
    // 사용자 정보 조회 테스트
    try {
        $user_test = $DB->get_record('user', array('id' => $USER->id));
        $query_tests['user_query'] = 'Success';
    } catch (Exception $e) {
        $query_tests['user_query'] = 'Error: ' . $e->getMessage();
    }
    
    // 메시지 테이블 조회 테스트
    foreach (['message', 'messages'] as $table) {
        if (in_array($table, $tables)) {
            try {
                $count = $DB->count_records($table);
                $query_tests[$table . '_count'] = $count;
            } catch (Exception $e) {
                $query_tests[$table . '_count'] = 'Error: ' . $e->getMessage();
            }
        }
    }
    
    $debug_info['query_tests'] = $query_tests;
    
    // 6. 현재 사용자 정보
    $debug_info['current_user'] = array(
        'id' => $USER->id,
        'username' => $USER->username,
        'email' => $USER->email
    );
    
    // 7. Moodle 버전 및 설정 정보
    $debug_info['moodle_info'] = array(
        'version' => $CFG->version ?? 'Unknown',
        'release' => $CFG->release ?? 'Unknown',
        'dbtype' => $CFG->dbtype ?? 'Unknown',
        'prefix' => $CFG->prefix ?? 'Unknown'
    );
    
    echo json_encode(array(
        'success' => true,
        'debug_info' => $debug_info
    ));
    
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ));
}
?>