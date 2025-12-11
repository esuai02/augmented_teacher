<?php
/**
 * DB 설치 API
 * AI Tutor 시스템 테이블 설치 및 기본 데이터 삽입
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

// 출력 버퍼링 시작
ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php');
}
global $DB, $USER, $CFG;
require_login();

// 관리자만 실행 가능
if (!is_siteadmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => '관리자만 실행할 수 있습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 출력 버퍼 비우기
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 에러 출력 비활성화
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $action = $_GET['action'] ?? 'check';
    $results = [];
    
    // 테이블 목록
    $tables = [
        'alt42_analysis_results',
        'alt42_interactions',
        'alt42_sessions',
        'alt42_interaction_logs',
        'alt42_generated_rules',
        'alt42_rule_contents',
        'alt42_ontology_data',
        'alt42_student_contexts',
        'alt42_personas',
        'alt42_student_personas',
        'alt42_persona_switches',
        'alt42_intervention_activities',
        'alt42_intervention_executions',
        'alt42_writing_patterns',
        'alt42_non_intrusive_questions'
    ];
    
    if ($action === 'check') {
        // 테이블 존재 여부 확인
        $dbman = $DB->get_manager();
        
        foreach ($tables as $table) {
            $exists = $dbman->table_exists($table);
            $count = 0;
            if ($exists) {
                try {
                    $count = $DB->count_records($table);
                } catch (Exception $e) {
                    $count = -1;
                }
            }
            $results[$table] = [
                'exists' => $exists,
                'count' => $count
            ];
        }
        
        // 모든 테이블이 존재하는지 확인
        $allExist = true;
        foreach ($results as $table => $info) {
            if (!$info['exists']) {
                $allExist = false;
                break;
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'all_tables_exist' => $allExist,
                'tables' => $results,
                'message' => $allExist 
                    ? '모든 테이블이 설치되어 있습니다.' 
                    : 'schema.sql 파일을 실행하여 테이블을 설치하세요.'
            ],
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else if ($action === 'install') {
        // schema.sql 파일 읽기 및 실행
        $schemaPath = __DIR__ . '/../db/schema.sql';
        
        if (!file_exists($schemaPath)) {
            throw new Exception('schema.sql 파일을 찾을 수 없습니다: ' . $schemaPath);
        }
        
        $sql = file_get_contents($schemaPath);
        
        // SQL 문장 분리
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $executed = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }
            
            try {
                $DB->execute($statement);
                $executed++;
            } catch (Exception $e) {
                $errors[] = [
                    'sql' => substr($statement, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        echo json_encode([
            'success' => count($errors) === 0,
            'data' => [
                'executed' => $executed,
                'errors' => $errors,
                'message' => count($errors) === 0 
                    ? "성공적으로 {$executed}개의 SQL 문을 실행했습니다."
                    : count($errors) . "개의 오류가 발생했습니다."
            ],
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else if ($action === 'init_data') {
        // 기본 데이터 확인 및 삽입
        $dbman = $DB->get_manager();
        
        // 페르소나 데이터 확인
        $personaCount = 0;
        if ($dbman->table_exists('alt42_personas')) {
            $personaCount = $DB->count_records('alt42_personas');
        }
        
        // 개입 활동 데이터 확인
        $interventionCount = 0;
        if ($dbman->table_exists('alt42_intervention_activities')) {
            $interventionCount = $DB->count_records('alt42_intervention_activities');
        }
        
        $results['personas'] = [
            'expected' => 12,
            'current' => $personaCount,
            'status' => $personaCount >= 12 ? 'OK' : 'NEED_INSERT'
        ];
        
        $results['intervention_activities'] = [
            'expected' => 42,
            'current' => $interventionCount,
            'status' => $interventionCount >= 42 ? 'OK' : 'NEED_INSERT'
        ];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'init_data' => $results,
                'message' => ($personaCount >= 12 && $interventionCount >= 42)
                    ? '기본 데이터가 모두 설치되어 있습니다.'
                    : 'schema.sql의 INSERT 문을 실행하여 기본 데이터를 삽입하세요.'
            ],
            'action' => $action,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
    } else {
        throw new Exception('알 수 없는 액션입니다: ' . $action);
    }

} catch (Exception $e) {
    ob_clean();
    
    error_log("[AItutor] DB Install Error: " . $e->getMessage());
    http_response_code(500);
    
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
}

ob_end_flush();

