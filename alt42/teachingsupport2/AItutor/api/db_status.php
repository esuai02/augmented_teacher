<?php
/**
 * DB 상태 확인 API
 * MySQL 데이터베이스 상태와 저장된 데이터를 확인
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    2.0 (MySQL 버전)
 */

// 출력 버퍼링 시작
ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php');
}
global $DB, $USER;
require_login();

// 출력 버퍼 비우기
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 에러 출력 비활성화
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    require_once(__DIR__ . '/../includes/db_manager.php');
    
    $dbManager = new DBManager();
    
    // 테이블 목록 및 통계
    $tables = [
        'alt42_analysis_results' => '분석 결과',
        'alt42_interactions' => '상호작용',
        'alt42_sessions' => '세션',
        'alt42_interaction_logs' => '상호작용 로그',
        'alt42_generated_rules' => '생성된 룰',
        'alt42_rule_contents' => '룰 컨텐츠',
        'alt42_ontology_data' => '온톨로지 데이터',
        'alt42_student_contexts' => '학생 컨텍스트',
        'alt42_personas' => '페르소나',
        'alt42_student_personas' => '학생-페르소나',
        'alt42_persona_switches' => '페르소나 스위칭',
        'alt42_intervention_activities' => '개입 활동',
        'alt42_intervention_executions' => '개입 실행 기록',
        'alt42_writing_patterns' => '필기 패턴',
        'alt42_non_intrusive_questions' => '비침습적 질문'
    ];
    
    $tableStats = [];
    foreach ($tables as $tableName => $description) {
        try {
            // 테이블 존재 확인
            $exists = $DB->get_manager()->table_exists($tableName);
            
            if ($exists) {
                $count = $DB->count_records($tableName);
                $tableStats[$tableName] = [
                    'description' => $description,
                    'exists' => true,
                    'count' => $count
                ];
            } else {
                $tableStats[$tableName] = [
                    'description' => $description,
                    'exists' => false,
                    'count' => 0,
                    'message' => '테이블이 존재하지 않습니다. schema.sql을 실행하세요.'
                ];
            }
        } catch (Exception $e) {
            $tableStats[$tableName] = [
                'description' => $description,
                'exists' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // 최근 분석 결과
    $recentAnalyses = [];
    try {
        $results = $dbManager->getStudentAnalysisResults($USER->id, 10);
        foreach ($results as $result) {
            $recentAnalyses[] = [
                'analysis_id' => $result['analysis_id'] ?? 'unknown',
                'student_id' => $result['student_id'] ?? 'unknown',
                'created_at' => $result['created_at'] ?? 'unknown',
                'text_preview' => isset($result['text_content']) 
                    ? mb_substr($result['text_content'], 0, 100) . '...' 
                    : 'N/A'
            ];
        }
    } catch (Exception $e) {
        $recentAnalyses = ['error' => $e->getMessage()];
    }
    
    // 페르소나 정보
    $personas = [];
    try {
        $personaList = $dbManager->getAllPersonas();
        foreach ($personaList as $p) {
            $personas[] = [
                'persona_id' => $p['persona_id'],
                'name' => $p['name'],
                'name_en' => $p['name_en']
            ];
        }
    } catch (Exception $e) {
        $personas = ['error' => $e->getMessage()];
    }
    
    // 개입 활동 정보
    $interventions = [];
    try {
        $interventionList = $dbManager->getAllInterventions();
        $interventions = [
            'total' => count($interventionList),
            'categories' => []
        ];
        foreach ($interventionList as $i) {
            $cat = $i['category'];
            if (!isset($interventions['categories'][$cat])) {
                $interventions['categories'][$cat] = 0;
            }
            $interventions['categories'][$cat]++;
        }
    } catch (Exception $e) {
        $interventions = ['error' => $e->getMessage()];
    }
    
    // DB 연결 정보
    $dbInfo = [
        'type' => 'MySQL',
        'version' => $DB->get_server_info(),
        'prefix' => $CFG->prefix ?? 'mdl_'
    ];
    
    echo json_encode([
        'success' => true,
        'data' => [
            'database' => $dbInfo,
            'tables' => $tableStats,
            'recent_analyses' => $recentAnalyses,
            'personas' => $personas,
            'interventions' => $interventions,
            'current_user' => [
                'id' => $USER->id,
                'username' => $USER->username
            ],
            'php_info' => [
                'version' => PHP_VERSION
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    ob_clean();
    
    error_log("[AItutor] DB Status Error: " . $e->getMessage());
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
