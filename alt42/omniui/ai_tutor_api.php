<?php
// AI 튜터 API 엔드포인트
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// 설정 파일 포함
require_once 'config.php';

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 에러 응답 함수
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

// 성공 응답 함수
function sendSuccess($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// POST 요청만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST method allowed', 405);
}

// JSON 데이터 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    sendError('Invalid JSON data');
}

// 필수 파라미터 검증
if (!isset($input['message']) || empty(trim($input['message']))) {
    sendError('Message is required');
}

$message = trim($input['message']);
$userid = isset($input['userid']) ? intval($input['userid']) : null;
$conversation = isset($input['conversation']) ? $input['conversation'] : [];

// API 키 확인
if (!defined('OPENAI_API_KEY') || empty(OPENAI_API_KEY)) {
    sendError(ERROR_API_KEY_MISSING, 500);
}

try {
    // PDO 연결
    $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // 사용자의 시험 정보 가져오기
    $examContext = '';
    if ($userid) {
        // 사용자 정보 조회
        $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_users WHERE userid = ?");
        $stmt->execute([$userid]);
        $userInfo = $stmt->fetch();
        
        if ($userInfo) {
            // 시험 정보 조회
            $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? LIMIT 1");
            $stmt->execute([$userInfo['school_name'], $userInfo['grade']]);
            $examInfo = $stmt->fetch();
            
            if ($examInfo) {
                // 시험 자료 조회
                $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exam_resources WHERE exam_id = ? AND user_id = ?");
                $stmt->execute([$examInfo['exam_id'], $userInfo['id']]);
                $resources = $stmt->fetchAll();
                
                $examContext = "\n\n학생 정보:\n";
                $examContext .= "- 학교: {$userInfo['school_name']}\n";
                $examContext .= "- 학년: {$userInfo['grade']}학년\n";
                $examContext .= "- 시험: {$examInfo['exam_type']}\n";
                
                if (!empty($resources)) {
                    $examContext .= "\n업로드된 시험 자료:\n";
                    foreach ($resources as $resource) {
                        if (!empty($resource['file_url'])) {
                            $examContext .= "- 파일: {$resource['file_url']}\n";
                        }
                        if (!empty($resource['tip_text'])) {
                            $examContext .= "- 팁: {$resource['tip_text']}\n";
                        }
                    }
                }
            }
        }
    }
    
    // OpenAI API 메시지 배열 구성
    $messages = [
        [
            'role' => 'system',
            'content' => SYSTEM_PROMPT . $examContext
        ]
    ];
    
    // 이전 대화 내역 추가
    foreach ($conversation as $conv) {
        if (isset($conv['role']) && isset($conv['content'])) {
            $messages[] = [
                'role' => $conv['role'],
                'content' => $conv['content']
            ];
        }
    }
    
    // 현재 메시지 추가
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];
    
    // OpenAI API 요청 데이터
    $requestData = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => OPENAI_MAX_TOKENS,
        'temperature' => OPENAI_TEMPERATURE,
        'stream' => false
    ];
    
    // cURL 초기화
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, OPENAI_TIMEOUT);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    // API 호출
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // cURL 에러 체크
    if (!empty($error)) {
        if (DEBUG_MODE) {
            sendError('cURL error: ' . $error, 500);
        } else {
            sendError(ERROR_API_CALL_FAILED, 500);
        }
    }
    
    // HTTP 상태 코드 체크
    if ($httpCode !== 200) {
        if (DEBUG_MODE) {
            $errorData = json_decode($response, true);
            sendError('API error: ' . ($errorData['error']['message'] ?? 'Unknown error'), $httpCode);
        } else {
            sendError(ERROR_API_CALL_FAILED, 500);
        }
    }
    
    // 응답 파싱
    $responseData = json_decode($response, true);
    if (!$responseData || !isset($responseData['choices'][0]['message']['content'])) {
        sendError(ERROR_API_CALL_FAILED, 500);
    }
    
    // AI 응답 추출
    $aiResponse = $responseData['choices'][0]['message']['content'];
    
    // 성공 응답 전송
    sendSuccess([
        'success' => true,
        'message' => $aiResponse,
        'usage' => $responseData['usage'] ?? null
    ]);
    
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        sendError('Database error: ' . $e->getMessage(), 500);
    } else {
        sendError('Database connection failed', 500);
    }
} catch (Exception $e) {
    if (DEBUG_MODE) {
        sendError('Error: ' . $e->getMessage(), 500);
    } else {
        sendError(ERROR_API_CALL_FAILED, 500);
    }
}
?>