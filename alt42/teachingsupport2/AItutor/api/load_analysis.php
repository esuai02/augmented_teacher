<?php
/**
 * 분석 결과 로드 API
 * ID로 저장된 분석 결과 조회
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

// GET 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $dbManagerPath = __DIR__ . '/../includes/db_manager.php';
    if (!file_exists($dbManagerPath)) {
        throw new Exception('DB Manager 파일을 찾을 수 없습니다', 500);
    }
    require_once($dbManagerPath);
    
    $analysisId = $_GET['id'] ?? null;
    
    if (!$analysisId) {
        throw new Exception('분석 ID가 필요합니다', 400);
    }
    
    // 분석 ID 검증
    if (!preg_match('/^ANALYSIS_\d+_\d+$/', $analysisId)) {
        throw new Exception('잘못된 분석 ID 형식입니다: ' . htmlspecialchars($analysisId), 400);
    }
    
    $dbManager = new DBManager();
    
    // 디버그 로깅
    error_log("[AItutor] 분석 결과 로드 시도: " . $analysisId);
    
    $analysisResult = $dbManager->getAnalysisResult($analysisId);
    
    if (!$analysisResult) {
        // 더 자세한 에러 메시지
        error_log("[AItutor] 분석 결과를 찾을 수 없습니다: " . $analysisId);
        
        // 최근 분석 결과 목록 조회
        $availableResults = [];
        try {
            $recentResults = $dbManager->getStudentAnalysisResults($USER->id, 5);
            foreach ($recentResults as $result) {
                $availableResults[] = $result['analysis_id'] ?? 'unknown';
            }
        } catch (Exception $e) {
            error_log("[AItutor] 분석 결과 목록 조회 오류: " . $e->getMessage());
        }
        
        $errorMsg = '분석 결과를 찾을 수 없습니다. ID: ' . htmlspecialchars($analysisId);
        if (!empty($availableResults)) {
            $errorMsg .= ' (최근 분석: ' . implode(', ', $availableResults) . ')';
        }
        throw new Exception($errorMsg, 404);
    }
    
    // 결과 반환
    ob_clean();
    
    echo json_encode([
        'success' => true,
        'data' => [
            'analysis_id' => $analysisResult['analysis_id'],
            'dialogue_analysis' => $analysisResult['dialogue_analysis'] ?? [],
            'comprehensive_questions' => $analysisResult['comprehensive_questions'] ?? [],
            'detailed_questions' => $analysisResult['detailed_questions'] ?? [],
            'teaching_rules' => $analysisResult['teaching_rules'] ?? [],
            'ontology' => $analysisResult['ontology'] ?? [],
            'rule_contents' => $analysisResult['rule_contents'] ?? [],
            'text_content' => $analysisResult['text_content'] ?? '',
            'image_data' => $analysisResult['image_data'] ?? '',
            'created_at' => $analysisResult['created_at'] ?? null,
            'updated_at' => $analysisResult['updated_at'] ?? null
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // 출력 버퍼 비우기
    ob_clean();
    
    error_log("[AItutor] Load Analysis Error: " . $e->getMessage());
    http_response_code($e->getCode() ?: 500);
    
    // JSON 헤더 재설정
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
}

// 출력 버퍼 종료
ob_end_flush();
