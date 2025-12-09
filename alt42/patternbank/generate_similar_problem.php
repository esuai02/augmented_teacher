<?php
/**
 * 유사문제 자동 생성 API
 * OpenAI GPT-4o를 사용하여 수학 유사문제를 자동으로 생성합니다.
 */

// Moodle 설정 로드
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// OpenAI 설정 파일 로드
require_once(__DIR__ . '/config/api_keys.php');

// JSON 안전 처리 헬퍼 로드
require_once(__DIR__ . '/lib/JsonSafeHelper.php');

// CORS 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    try {
        echo JsonSafeHelper::safeEncode(['success' => false, 'message' => 'Method not allowed']);
    } catch (Exception $e) {
        echo '{"success":false,"message":"Method not allowed"}'; // ASCII-only fallback
    }
    exit;
}

// 로그인 체크
if (!isloggedin()) {
    http_response_code(401);
    try {
        echo JsonSafeHelper::safeEncode(['success' => false, 'message' => 'Not logged in']);
    } catch (Exception $e) {
        echo '{"success":false,"message":"Not logged in"}'; // ASCII-only fallback
    }
    exit;
}

// JSON 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $errorMsg = 'Invalid JSON input';
    $location = __FILE__ . ':' . __LINE__;
    error_log("[generate_similar_problem.php:" . __LINE__ . "] " . $errorMsg);
    http_response_code(400);
    try {
        echo JsonSafeHelper::safeEncode([
            'success' => false, 
            'message' => $errorMsg . ' (' . $location . ')',
            'error' => $errorMsg,
            'location' => $location
        ]);
    } catch (Exception $e) {
        echo '{"success":false,"message":"Invalid JSON input (' . $location . ')"}'; // ASCII-only fallback
    }
    exit;
}

// 필수 파라미터 확인
$cntid = $input['cntid'] ?? '';
$cnttype = $input['cnttype'] ?? '';
$problemType = $input['problemType'] ?? 'similar'; // similar or modified
$imageUrl = $input['imageUrl'] ?? ''; // 원본 문제 이미지 URL (선택적)

if (empty($cntid) || empty($cnttype)) {
    $missing = [];
    if (empty($cntid)) {
        $missing[] = 'cntid';
    }
    if (empty($cnttype)) {
        $missing[] = 'cnttype';
    }
    $errorMsg = 'Missing required parameters: ' . implode(', ', $missing);
    $location = __FILE__ . ':' . __LINE__;
    error_log("[generate_similar_problem.php:" . __LINE__ . "] " . $errorMsg);
    http_response_code(400);
    try {
        echo JsonSafeHelper::safeEncode([
            'success' => false, 
            'message' => $errorMsg . ' (' . $location . ')',
            'error' => $errorMsg,
            'location' => $location,
            'missing' => $missing
        ]);
    } catch (Exception $e) {
        echo '{"success":false,"message":"Missing required parameters (' . $location . ')"}'; // ASCII-only fallback
    }
    exit;
}

try {
    // 원본 문제 정보 조회 (가장 최근 문제를 참고용으로 사용)
    $recentProblem = $DB->get_record_sql(
        "SELECT question, solution, inputanswer 
         FROM {abessi_patternbank} 
         WHERE cntid = ? AND cnttype = ? 
         ORDER BY id DESC 
         LIMIT 1",
        [$cntid, $cnttype]
    );
    
    // 원본 문제 구성
    $originalProblem = [];
    if ($recentProblem) {
        $originalProblem = [
            'question' => $recentProblem->question,
            'solution' => $recentProblem->solution
        ];
        
        if (!empty($recentProblem->inputanswer)) {
            try {
                $originalProblem['choices'] = JsonSafeHelper::safeDecode($recentProblem->inputanswer);
            } catch (Exception $e) {
                error_log("[generate_similar_problem.php:" . __LINE__ . "] Failed to decode choices with JsonSafeHelper: " . $e->getMessage());
                // Try legacy format
                $legacyChoices = @json_decode($recentProblem->inputanswer, true);
                if ($legacyChoices !== null && is_array($legacyChoices)) {
                    error_log("[generate_similar_problem.php:" . __LINE__ . "] Successfully decoded using legacy format");
                    $originalProblem['choices'] = $legacyChoices;
                } else {
                    // Both decodes failed - data is corrupt
                    error_log("[generate_similar_problem.php:" . __LINE__ . "] WARNING: Completely corrupt inputanswer data for problem ID " . $recentProblem->id . " - proceeding without choices reference");
                    // Don't set choices - GPT will generate problem without choice context
                    // (Better than empty array which implies "no choices required")
                }
            }
        }
    }
    
    // 이미지 URL이 제공된 경우 이미지 기반 생성
    if (!empty($imageUrl)) {
        $originalProblem['imageUrl'] = $imageUrl;
    }
    
    // 원본 문제가 없고 이미지도 없으면 기본 템플릿 사용
    if (empty($originalProblem) && empty($imageUrl)) {
        // 기본 수학 문제 템플릿
        $originalProblem = [
            'question' => '다음 수열의 일반항을 구하시오: 2, 4, 8, 16, ...',
            'solution' => '등비수열로 첫째항이 2이고 공비가 2입니다. 따라서 일반항은 $a_n = 2^n$입니다.'
        ];
    }
    
    // OpenAI API를 통한 유사문제 생성
    error_log('PatternBank: Generating problems with type: ' . $problemType);
    $result = generateSimilarProblems($originalProblem, $problemType);
    
    if (!$result['success']) {
        throw new Exception($result['error'] ?? 'Failed to generate problems');
    }
    
    // 생성된 문제들을 DB에 저장
    $savedProblems = [];
    $errors = [];
    
    foreach ($result['problems'] as $index => $problem) {
        try {
            $problemRecord = new stdClass();
            $problemRecord->authorid = $USER->id;
            $problemRecord->cntid = $cntid;
            $problemRecord->cnttype = $cnttype;
            $problemRecord->question = $problem['question'];
            $problemRecord->solution = $problem['solution'];
            
            // 선택지가 있으면 JSON 문자열로 저장
            if (!empty($problem['choices'])) {
                try {
                    $problemRecord->inputanswer = JsonSafeHelper::safeEncode($problem['choices']);
                } catch (Exception $e) {
                    error_log("[generate_similar_problem.php:" . __LINE__ . "] CRITICAL: Failed to encode choices for problem " . ($index + 1) . ": " . $e->getMessage());
                    $errors[] = "문제 " . ($index + 1) . " 인코딩 실패로 건너뜀: " . $e->getMessage();
                    continue; // Skip this problem - don't save corrupted data
                }
            } else {
                $problemRecord->inputanswer = null;
            }
            
            $problemRecord->type = $problemType; // similar or modified
            $problemRecord->timecreated = time();
            $problemRecord->timemodified = time();
            
            // NULL 값들
            $problemRecord->qstnimgurl = null;
            $problemRecord->solimgurl = null;
            $problemRecord->fullqstnimgurl = null;
            $problemRecord->fullsolimgurl = null;
            
            // DB에 저장
            $id = $DB->insert_record('abessi_patternbank', $problemRecord);
            
            if ($id) {
                $savedProblems[] = [
                    'id' => $id,
                    'number' => $index + 1,
                    'question' => $problem['question'],
                    'solution' => $problem['solution'],
                    'choices' => $problem['choices'] ?? [],
                    'type' => $problemType
                ];
                error_log("PatternBank: Problem " . ($index + 1) . " saved with ID: " . $id);
            } else {
                $errors[] = "문제 " . ($index + 1) . " 저장 실패";
                error_log("PatternBank: Failed to save problem " . ($index + 1));
            }
            
        } catch (Exception $e) {
            $errors[] = "문제 " . ($index + 1) . " 저장 오류: " . $e->getMessage();
            error_log("PatternBank: Error saving problem " . ($index + 1) . ": " . $e->getMessage());
        }
    }
    
    // 응답 생성
    $response = [
        'success' => count($savedProblems) > 0,
        'problems' => $savedProblems,
        'totalGenerated' => count($result['problems']),
        'totalSaved' => count($savedProblems),
        'usage' => $result['usage'] ?? null
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    // 성공 메시지
    if (count($savedProblems) > 0) {
        $response['message'] = count($savedProblems) . "개의 " . 
            ($problemType === 'similar' ? '유사문제' : '변형문제') . 
            "가 성공적으로 생성되었습니다.";
    } else {
        $response['message'] = "문제 생성에 실패했습니다.";
    }
    
    // 응답 JSON 인코딩 (choices 포함 가능)
    try {
        echo JsonSafeHelper::safeEncode($response);
    } catch (Exception $e) {
        error_log("[generate_similar_problem.php:" . __LINE__ . "] CRITICAL: Failed to encode response: " . $e->getMessage());
        http_response_code(500);
        // Ultimate fallback - ASCII-only guaranteed success (matches patternbank_ajax.php pattern)
        echo '{"success":false,"error":"Response encoding failed"}'; // ASCII-only, no Korean text
    }

} catch (Exception $e) {
    error_log('PatternBank generate_similar_problem error: ' . $e->getMessage());
    http_response_code(500);
    // P0 FIX: $e->getMessage() may contain Korean text, use JsonSafeHelper
    try {
        echo JsonSafeHelper::safeEncode([
            'success' => false,
            'message' => '문제 생성 중 오류가 발생했습니다: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ]);
    } catch (Exception $jsonEx) {
        error_log("[generate_similar_problem.php:" . __LINE__ . "] Failed to encode error message: " . $e->getMessage());
        echo '{"success":false,"error":"Problem generation failed"}'; // ASCII-only fallback
    }
}
?>