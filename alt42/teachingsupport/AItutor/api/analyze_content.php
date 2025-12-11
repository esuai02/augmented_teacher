<?php
/**
 * 컨텐츠 분석 API
 * 이미지나 텍스트를 입력받아 포괄적 질문, 룰, 온톨로지 생성
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

// 출력 버퍼링 시작 (예기치 않은 출력 방지)
ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
// config.php가 있으면 로드
if (file_exists(__DIR__ . '/../../config.php')) {
    require_once(__DIR__ . '/../../config.php');
}
global $DB, $USER, $CFG;
require_login();

/**
 * 안전한 통계 조회 함수
 */
function safeGetStats($dbManager) {
    try {
        return $dbManager->getStats();
    } catch (Exception $e) {
        error_log("Stats error: " . $e->getMessage());
        return [];
    }
}

// 출력 버퍼 비우기 (Moodle의 출력 제거)
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 에러 출력 비활성화 (JSON 응답 보호)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// API 키를 $CFG에서 가져오기
$apiKey = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($apiKey)) {
    error_log('[analyze_content.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Error: API 키가 설정되지 않았습니다.');
}
$model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o';

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
    $inputRaw = file_get_contents('php://input');
    if (empty($inputRaw)) {
        throw new Exception('입력 데이터가 없습니다', 400);
    }
    
    $input = json_decode($inputRaw, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON input: ' . json_last_error_msg(), 400);
    }
    
    if (!$input) {
        throw new Exception('Invalid JSON input', 400);
    }

    $textContent = $input['text'] ?? '';
    $imageData = $input['image'] ?? '';
    $studentId = $input['student_id'] ?? $USER->id;

    // 텍스트나 이미지 중 하나는 필수
    if (empty($textContent) && empty($imageData)) {
        throw new Exception('텍스트나 이미지 중 하나는 반드시 입력해야 합니다', 400);
    }

    // OpenAI API를 통한 분석 프로세스
    $openaiAnalyzerPath = __DIR__ . '/../includes/openai_analyzer.php';
    if (!file_exists($openaiAnalyzerPath)) {
        throw new Exception('OpenAI Analyzer 파일을 찾을 수 없습니다: ' . $openaiAnalyzerPath, 500);
    }
    require_once($openaiAnalyzerPath);
    
    $openaiAnalyzer = new OpenAIAnalyzer($apiKey, $model);
    
    // OpenAI API를 통한 종합 분석
    $analysisResult = $openaiAnalyzer->analyzeContent($textContent, $imageData, $studentId);
    
    if (empty($analysisResult)) {
        throw new Exception('분석 결과가 비어있습니다', 500);
    }
    
    // 결과 분리
    $dialogueAnalysis = $analysisResult['dialogue_analysis'] ?? [];
    $comprehensiveQuestions = $analysisResult['comprehensive_questions'] ?? [];
    $detailedQuestions = $analysisResult['detailed_questions'] ?? [];
    $teachingRules = $analysisResult['teaching_rules'] ?? [];
    $ontology = $analysisResult['ontology'] ?? [];
    
    // 룰 기반 컨텐츠 생성 및 저장
    $ruleContentGenPath = __DIR__ . '/../includes/rule_content_generator.php';
    $dbManagerPath = __DIR__ . '/../includes/db_manager.php';
    
    if (!file_exists($ruleContentGenPath)) {
        throw new Exception('Rule Content Generator 파일을 찾을 수 없습니다: ' . $ruleContentGenPath, 500);
    }
    if (!file_exists($dbManagerPath)) {
        throw new Exception('DB Manager 파일을 찾을 수 없습니다: ' . $dbManagerPath, 500);
    }
    
    require_once($ruleContentGenPath);
    require_once($dbManagerPath);
    
    $dbManager = new DBManager();
    $contentGenerator = new RuleContentGenerator();
    $ruleContents = $contentGenerator->generateRuleContents($teachingRules, [
        'unit' => $dialogueAnalysis['unit'] ?? null,
        'concepts' => $dialogueAnalysis['concepts'] ?? []
    ]);
    
    // FileDB에 저장 (에러가 발생해도 계속 진행)
    $savedIds = [];
    try {
        foreach ($ruleContents as $content) {
            try {
                $id = $dbManager->saveRuleContent($content);
                $savedIds[] = $id;
                
                // 생성된 룰도 저장
                if (isset($content['rule_id'])) {
                    $rule = null;
                    foreach ($teachingRules as $r) {
                        if ($r['rule_id'] === $content['rule_id']) {
                            $rule = $r;
                            break;
                        }
                    }
                    if ($rule) {
                        $dbManager->saveGeneratedRule($rule);
                    }
                }
            } catch (Exception $e) {
                error_log("Rule content save error: " . $e->getMessage());
            }
        }
        
        // 온톨로지 데이터 저장
        if (isset($ontology['ontology']) && is_array($ontology['ontology'])) {
            foreach ($ontology['ontology'] as $node) {
                try {
                    $dbManager->saveOntologyData([
                        'node_id' => $node['id'] ?? uniqid('node_'),
                        'class' => $node['class'] ?? '',
                        'stage' => $node['stage'] ?? '',
                        'properties' => $node['properties'] ?? [],
                        'metadata' => [
                            'created_at' => date('Y-m-d H:i:s'),
                            'student_id' => $studentId
                        ]
                    ]);
                } catch (Exception $e) {
                    error_log("Ontology data save error: " . $e->getMessage());
                }
            }
        }
        
        // 학생 컨텍스트 저장
        try {
            $dbManager->saveStudentContext($studentId, [
                'current_unit' => $dialogueAnalysis['unit']['korean'] ?? null,
                'concepts' => array_column($dialogueAnalysis['concepts'] ?? [], 'name'),
                'understanding_level' => 'medium', // 기본값
                'context_data' => $dialogueAnalysis
            ]);
        } catch (Exception $e) {
            error_log("Student context save error: " . $e->getMessage());
        }
    } catch (Exception $dbError) {
        error_log("Database save error: " . $dbError->getMessage());
        // DB 저장 실패해도 분석 결과는 반환
    }
    
    // 컨텐츠 뱅크에도 저장 (하위 호환성)
    $contentsBankPath = __DIR__ . '/../contentsbank';
    $savedFiles = $contentGenerator->saveContents($ruleContents, $contentsBankPath);
    
    // 컨텐츠 인덱스 생성
    $contentLoaderPath = __DIR__ . '/../includes/content_loader.php';
    if (file_exists($contentLoaderPath)) {
        require_once($contentLoaderPath);
        $contentLoader = new ContentLoader($contentsBankPath);
        $contentIndex = $contentLoader->generateContentIndex();
    } else {
        $contentIndex = [];
        error_log("Content Loader 파일을 찾을 수 없습니다: " . $contentLoaderPath);
    }

    // 분석 결과 ID 생성
    $analysisId = 'ANALYSIS_' . time() . '_' . mt_rand(1000, 9999);
    
    // 분석 결과 전체 저장
    $analysisResult = [
        'analysis_id' => $analysisId,
        'student_id' => $studentId,
        'text_content' => $textContent,
        'image_data' => $imageData, // base64 이미지 데이터
        'dialogue_analysis' => $dialogueAnalysis,
        'comprehensive_questions' => $comprehensiveQuestions,
        'detailed_questions' => $detailedQuestions,
        'teaching_rules' => $teachingRules,
        'ontology' => $ontology,
        'rule_contents' => [
            'generated' => count($ruleContents),
            'saved_ids' => $savedIds,
            'saved_files' => $savedFiles,
            'index' => $contentIndex
        ],
        'metadata' => [
            'created_by' => $USER->id,
            'db_stats' => safeGetStats($dbManager)
        ]
    ];
    
    // FileDB에 저장
    $saveSuccess = false;
    $saveError = null;
    try {
        error_log("분석 결과 저장 시작: " . $analysisId);
        $saveResult = $dbManager->saveAnalysisResult($analysisResult);
        $saveSuccess = ($saveResult !== false && $saveResult !== null);
        error_log("분석 결과 저장 결과: " . ($saveSuccess ? "성공 (ID: {$saveResult})" : "실패"));
        
        // 저장 확인
        if ($saveSuccess) {
            $verifyResult = $dbManager->getAnalysisResult($analysisId);
            if (!$verifyResult) {
                error_log("저장 확인 실패: 저장 후 조회되지 않음");
                $saveSuccess = false;
                $saveError = "저장 후 확인 실패";
            } else {
                error_log("저장 확인 성공: " . $analysisId);
            }
        }
    } catch (Exception $e) {
        $saveError = $e->getMessage();
        error_log("Analysis result save error: " . $saveError);
        // 저장 실패해도 결과는 반환
    }

    // 출력 버퍼 비우기 (최종 확인)
    ob_clean();
    
    // 결과 반환
    $responseData = [
        'success' => true,
        'data' => [
            'analysis_id' => $analysisId,
            'dialogue_analysis' => $dialogueAnalysis,
            'comprehensive_questions' => $comprehensiveQuestions,
            'detailed_questions' => $detailedQuestions,
            'teaching_rules' => $teachingRules,
            'ontology' => $ontology,
            'rule_contents' => [
                'generated' => count($ruleContents),
                'saved_ids' => $savedIds,
                'saved_files' => $savedFiles,
                'index' => $contentIndex
            ],
            'db_stats' => safeGetStats($dbManager),
            'save_status' => [
                'success' => $saveSuccess,
                'error' => $saveError
            ]
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $jsonOutput = json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encoding error: " . json_last_error_msg());
        throw new Exception('응답 데이터 인코딩 오류: ' . json_last_error_msg(), 500);
    }
    
    echo $jsonOutput;

} catch (Exception $e) {
    // 출력 버퍼 비우기
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode() ?: 500;
    $errorFile = basename(__FILE__);
    $errorLine = $e->getLine() ?: __LINE__;
    
    // 에러 로그 기록
    error_log("AI Tutor Analysis Error in {$errorFile}:{$errorLine} - {$errorMessage}");
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // HTTP 상태 코드 설정
    http_response_code($errorCode);
    
    // JSON 헤더 재설정
    header('Content-Type: application/json; charset=utf-8');
    
    // 에러 응답
    $errorResponse = [
        'success' => false,
        'error' => $errorMessage,
        'file' => $errorFile,
        'line' => $errorLine
    ];
    
    // 개발 모드에서만 상세 정보 추가
    if (defined('DEBUG') && DEBUG) {
        $errorResponse['trace'] = $e->getTraceAsString();
    }
    
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
    
    exit;
} catch (Error $e) {
    // PHP 7+ Fatal Error 처리
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    
    $errorMessage = $e->getMessage();
    $errorFile = basename($e->getFile());
    $errorLine = $e->getLine();
    
    error_log("AI Tutor Fatal Error in {$errorFile}:{$errorLine} - {$errorMessage}");
    
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'success' => false,
        'error' => '서버 오류가 발생했습니다: ' . $errorMessage,
        'file' => $errorFile,
        'line' => $errorLine
    ], JSON_UNESCAPED_UNICODE);
    
    exit;
}

// 출력 버퍼 종료 (정상 완료 시)
if (ob_get_level() > 0) {
    ob_end_flush();
}

