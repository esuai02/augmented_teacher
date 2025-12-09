<?php
/**
 * 상호작용 API
 * 룰과 온톨로지를 사용한 매끄러운 상호작용 처리
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
global $DB, $USER;
require_login();

// 출력 버퍼 비우기
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 에러 출력 비활성화
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'file' => __FILE__,
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 입력 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid JSON input', 400);
    }

    $userInput = $input['user_input'] ?? '';
    $rules = $input['rules'] ?? [];
    $ontology = $input['ontology'] ?? [];
    $context = $input['context'] ?? [];
    $interactionId = $input['interaction_id'] ?? null;

    if (empty($userInput)) {
        throw new Exception('사용자 입력이 필요합니다', 400);
    }

    if (empty($rules)) {
        throw new Exception('룰이 필요합니다', 400);
    }

    // 페르소나 기반 맞춤 지도 시스템 사용
    require_once(__DIR__ . '/../includes/persona_based_tutoring.php');
    require_once(__DIR__ . '/../includes/db_manager.php');
    
    $dbManager = new DBManager();
    
    // 학생 컨텍스트 로드
    $studentId = $input['student_id'] ?? null;
    if ($studentId) {
        $studentContext = $dbManager->getStudentContext($studentId);
        if ($studentContext) {
            $context = array_merge($context, $studentContext['context_data'] ?? []);
        }
    }
    
    // 페르소나 기반 맞춤 지도 엔진 초기화
    $tutoringEngine = new PersonaBasedTutoring($rules, $ontology, $context);
    
    // 페르소나 기반 맞춤 지도 처리 (자동으로 페르소나 식별 및 스위칭 포함)
    $result = $tutoringEngine->processPersonaBasedTutoring($userInput, $studentId, $context);
    
    // 상호작용 히스토리 저장
    $dbManager->saveInteraction([
        'interaction_id' => $result['interaction_id'],
        'student_id' => $input['student_id'] ?? 0,
        'user_input' => $userInput,
        'response' => $result['response'],
        'matched_rules' => array_column($result['matched_rules'] ?? [], 'rule_id'),
        'context' => $context,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    // FileDB에서 관련 컨텐츠 로드
    $relatedContents = [];
    if (isset($result['matched_rules'])) {
        foreach ($result['matched_rules'] as $rule) {
            $ruleId = is_array($rule) ? $rule['rule_id'] : $rule;
            $ruleContents = $dbManager->getRuleContents($ruleId);
            $relatedContents = array_merge($relatedContents, $ruleContents);
        }
    }
    
    // 컨텐츠 뱅크에서도 로드 (하위 호환성)
    require_once(__DIR__ . '/../includes/content_loader.php');
    $contentLoader = new ContentLoader();
    $fileContents = $contentLoader->loadRelatedContents($result['matched_rules'] ?? [], $ontology);
    $relatedContents = array_merge($relatedContents, $fileContents);
    
    // 결과에 컨텐츠 추가
    $result['related_contents'] = $relatedContents;
    
    // 결과 반환
    echo json_encode([
        'success' => true,
        'data' => $result,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // 출력 버퍼 비우기
    ob_clean();
    
    error_log("Interaction Engine Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());
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

