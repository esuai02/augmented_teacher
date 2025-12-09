<?php
/**
 * GPT 채팅 API 핸들러
 * 시험 자료를 context로 포함하여 GPT와 대화
 */

require_once 'config.php';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

try {
    // PDO 연결
    $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // POST 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);
    
    $action = $input['action'] ?? '';
    $school = $input['school'] ?? '';
    $grade = intval($input['grade'] ?? 0);
    $examType = $input['examType'] ?? '';
    $message = $input['message'] ?? '';
    $conversation = $input['conversation'] ?? [];

    if (empty($action)) {
        throw new Exception(ERROR_INVALID_REQUEST);
    }

    // API 키 확인 (init 액션에서는 확인만, chat 액션에서만 에러 발생)
    $apiKeyValid = (OPENAI_API_KEY !== 'YOUR_API_KEY_HERE' && !empty(OPENAI_API_KEY));

    switch ($action) {
        case 'init':
            // 시험 자료 조회 및 초기 컨텍스트 생성
            $context = getExamContext($pdo, $school, $grade, $examType);
            
            // 설정 정보 포함
            $configInfo = [
                'model' => OPENAI_MODEL,
                'system_prompt' => SYSTEM_PROMPT,
                'api_configured' => (OPENAI_API_KEY !== 'YOUR_API_KEY_HERE' && !empty(OPENAI_API_KEY)),
                'tutor_name' => AI_TUTOR_NAME,
                'greeting' => AI_TUTOR_GREETING,
                'intro' => AI_TUTOR_INTRO
            ];
            
            echo json_encode([
                'success' => true,
                'context' => $context,
                'config' => $configInfo,
                'message' => AI_TUTOR_INTRO
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        case 'chat':
            // API 키 확인
            if (!$apiKeyValid) {
                throw new Exception(ERROR_API_KEY_MISSING . ' config.php에서 OPENAI_API_KEY를 설정해주세요.');
            }
            
            // 시험 자료 컨텍스트 가져오기
            $context = getExamContext($pdo, $school, $grade, $examType);
            
            // GPT에게 보낼 메시지 구성
            $messages = [
                [
                    'role' => 'system',
                    'content' => SYSTEM_PROMPT . "\n\n" . 
                                "【제공된 시험 자료 컨텍스트】\n" .
                                "다음은 mdl_alt42t_exam_resources 테이블에서 가져온 file_url과 tip_text 데이터입니다:\n" . 
                                $context . "\n\n" .
                                "위 자료를 참고하여 답변하되, 자료와 관련 없는 질문에도 교육적인 답변을 제공하세요."
                ]
            ];
            
            // 이전 대화 내역 추가
            foreach ($conversation as $conv) {
                $messages[] = [
                    'role' => $conv['role'],
                    'content' => $conv['content']
                ];
            }
            
            // 현재 메시지 추가
            $messages[] = [
                'role' => 'user',
                'content' => $message
            ];
            
            // OpenAI API 호출
            $response = callOpenAI($messages);
            
            echo json_encode([
                'success' => true,
                'response' => $response,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_UNESCAPED_UNICODE);
            break;
            
        default:
            throw new Exception(ERROR_INVALID_REQUEST);
    }

} catch (Exception $e) {
    error_log("GPT Chat API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 시험 자료 컨텍스트 생성
 */
function getExamContext($pdo, $school, $grade, $examType) {
    // examType 매핑
    $examTypeMap = [
        '1mid' => '1학기 중간고사',
        '1final' => '1학기 기말고사',
        '2mid' => '2학기 중간고사',
        '2final' => '2학기 기말고사'
    ];
    
    $examTypeName = $examTypeMap[$examType] ?? $examType;
    
    // exam_id 조회
    $stmt = $pdo->prepare("SELECT exam_id FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? AND exam_type = ?");
    $stmt->execute([$school, $grade, $examTypeName]);
    $exam = $stmt->fetch();
    
    if (!$exam) {
        return "현재 시험 정보가 없습니다.";
    }
    
    $exam_id = $exam['exam_id'];
    
    // 시험 자료 조회
    $stmt = $pdo->prepare("
        SELECT file_url, tip_text, created_at 
        FROM mdl_alt42t_exam_resources 
        WHERE exam_id = ? 
        ORDER BY created_at DESC 
        LIMIT 20
    ");
    $stmt->execute([$exam_id]);
    $resources = $stmt->fetchAll();
    
    // 컨텍스트 구성
    $context = "【{$school} {$grade}학년 {$examTypeName} 관련 자료】\n";
    $context .= "exam_id: {$exam_id}\n\n";
    
    $files = [];
    $tips = [];
    
    foreach ($resources as $resource) {
        if (!empty($resource['file_url'])) {
            $fileName = basename($resource['file_url']);
            $files[] = "- file_url: " . $fileName . " (업로드일: " . date('Y-m-d', strtotime($resource['created_at'])) . ")";
        }
        
        if (!empty($resource['tip_text'])) {
            // tip_text 내용을 그대로 보존
            $tipContent = $resource['tip_text'];
            
            // 카테고리 파싱
            if (strpos($tipContent, '[팁]') !== false) {
                $tips[] = "- [팁 카테고리] " . $tipContent;
            } elseif (strpos($tipContent, '[주의사항]') !== false) {
                $tips[] = "- [주의사항 카테고리] " . $tipContent;
            } elseif (strpos($tipContent, '[출제경향]') !== false) {
                $tips[] = "- [출제경향 카테고리] " . $tipContent;
            } elseif (strpos($tipContent, '[범위]') !== false) {
                $tips[] = "- [범위 카테고리] " . $tipContent;
            } else {
                $tips[] = "- [일반 팁] tip_text: " . $tipContent;
            }
        }
    }
    
    if (!empty($files)) {
        $context .= "📁 file_url 데이터 (업로드된 파일 목록):\n" . implode("\n", array_slice($files, 0, 10)) . "\n\n";
    }
    
    if (!empty($tips)) {
        $context .= "💡 tip_text 데이터 (시험 관련 팁과 정보):\n" . implode("\n", array_slice($tips, 0, 15)) . "\n";
    }
    
    if (empty($files) && empty($tips)) {
        $context .= "현재 mdl_alt42t_exam_resources 테이블에 저장된 자료가 없습니다.\n";
        $context .= "file_url: 없음\n";
        $context .= "tip_text: 없음\n";
    }
    
    return $context;
}

/**
 * OpenAI API 호출
 */
function callOpenAI($messages) {
    // 디버그 로그
    error_log("OpenAI API 호출 시작 - Model: " . OPENAI_MODEL);
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE
    ];
    
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // 디버그 정보 로깅
    error_log("OpenAI API 응답 코드: " . $httpCode);
    
    if ($response === false) {
        error_log("CURL 에러: " . $curlError);
        throw new Exception("네트워크 오류: " . $curlError);
    }
    
    if ($httpCode === 401) {
        throw new Exception("API 키가 유효하지 않습니다. config.php에서 OPENAI_API_KEY를 확인해주세요.");
    }
    
    if ($httpCode === 429) {
        throw new Exception("API 요청 한도를 초과했습니다. 잠시 후 다시 시도해주세요.");
    }
    
    if ($httpCode !== 200) {
        error_log("OpenAI API 오류 응답: " . $response);
        $errorData = json_decode($response, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : "알 수 없는 오류";
        throw new Exception("OpenAI API 오류 (HTTP " . $httpCode . "): " . $errorMessage);
    }
    
    $result = json_decode($response, true);
    
    if (!isset($result['choices'][0]['message']['content'])) {
        error_log("예상치 못한 응답 형식: " . json_encode($result));
        throw new Exception("OpenAI API 응답 형식 오류");
    }
    
    return $result['choices'][0]['message']['content'];
}
?>