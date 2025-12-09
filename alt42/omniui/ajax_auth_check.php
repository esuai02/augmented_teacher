<?php
/**
 * AJAX 인증 체크 및 세션 관리
 * 모든 AJAX 핸들러에서 include하여 사용
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// JSON 응답 헤더 설정
header('Content-Type: application/json; charset=utf-8');

// CORS 설정 (필요시)
header('Access-Control-Allow-Origin: https://mathking.kr');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 세션 타임아웃 설정 (1시간)
define('SESSION_TIMEOUT', 3600);

/**
 * 로그인 상태 체크
 */
function checkAuthentication() {
    // 세션에 사용자 ID가 있는지 확인
    if (!isset($_SESSION['user_id'])) {
        sendErrorResponse('로그인이 필요합니다.', 401);
    }
    
    // 세션 타임아웃 체크
    if (isset($_SESSION['login_time'])) {
        if (time() - $_SESSION['login_time'] > SESSION_TIMEOUT) {
            // 세션 만료
            session_destroy();
            sendErrorResponse('세션이 만료되었습니다. 다시 로그인해주세요.', 401);
        }
    }
    
    // 마지막 활동 시간 업데이트
    $_SESSION['last_activity'] = time();
    
    return $_SESSION['user_id'];
}

/**
 * 교사 권한 체크
 */
function checkTeacherRole($user_id = null) {
    if (!$user_id) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    if (!$user_id) {
        sendErrorResponse('사용자 ID가 없습니다.', 401);
    }
    
    // 데이터베이스 연결
    require_once 'config.php';
    
    try {
        $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        
        // 역할 확인 (fieldid 22)
        $stmt = $pdo->prepare("
            SELECT data FROM mdl_user_info_data 
            WHERE userid = ? AND fieldid = 22
        ");
        $stmt->execute([$user_id]);
        $role = $stmt->fetchColumn();
        
        // 'student'가 아니면 교사로 간주
        if ($role === 'student') {
            sendErrorResponse('교사 권한이 필요합니다.', 403);
        }
        
        return true;
        
    } catch (Exception $e) {
        error_log("Role check error: " . $e->getMessage());
        sendErrorResponse('권한 확인 중 오류가 발생했습니다.', 500);
    }
}

/**
 * 학생 본인 확인
 */
function checkStudentOwnership($target_user_id) {
    $current_user_id = $_SESSION['user_id'] ?? null;
    
    if (!$current_user_id) {
        sendErrorResponse('로그인이 필요합니다.', 401);
    }
    
    // 본인이거나 교사인 경우 허용
    if ($current_user_id == $target_user_id) {
        return true;
    }
    
    // 교사 권한 체크
    try {
        checkTeacherRole($current_user_id);
        return true;
    } catch (Exception $e) {
        sendErrorResponse('접근 권한이 없습니다.', 403);
    }
}

/**
 * CSRF 토큰 검증
 */
function validateCSRFToken($token = null) {
    if (!$token) {
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
    }
    
    if (!$token || !isset($_SESSION['csrf_token'])) {
        sendErrorResponse('보안 토큰이 유효하지 않습니다.', 403);
    }
    
    if ($token !== $_SESSION['csrf_token']) {
        sendErrorResponse('보안 토큰이 일치하지 않습니다.', 403);
    }
    
    return true;
}

/**
 * CSRF 토큰 생성
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * 입력 데이터 검증 및 정제
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    // HTML 태그 제거 및 특수문자 이스케이프
    $data = strip_tags($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = trim($data);
    
    return $data;
}

/**
 * 정수 검증
 */
function validateInteger($value, $min = null, $max = null) {
    if (!is_numeric($value)) {
        return false;
    }
    
    $value = intval($value);
    
    if ($min !== null && $value < $min) {
        return false;
    }
    
    if ($max !== null && $value > $max) {
        return false;
    }
    
    return $value;
}

/**
 * 이메일 검증
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * 날짜 검증
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * 에러 응답 전송
 */
function sendErrorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $message,
        'code' => $code
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 성공 응답 전송
 */
function sendSuccessResponse($data = null, $message = '성공적으로 처리되었습니다.') {
    $response = [
        'success' => true,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 요청 메소드 체크
 */
function requireMethod($method) {
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        sendErrorResponse('허용되지 않은 요청 방법입니다.', 405);
    }
}

/**
 * 필수 파라미터 체크
 */
function requireParameters($params, $source = 'POST') {
    $data = $source === 'POST' ? $_POST : $_GET;
    $missing = [];
    
    foreach ($params as $param) {
        if (!isset($data[$param]) || empty($data[$param])) {
            $missing[] = $param;
        }
    }
    
    if (!empty($missing)) {
        sendErrorResponse('필수 파라미터가 누락되었습니다: ' . implode(', ', $missing), 400);
    }
}

/**
 * JSON 요청 바디 파싱
 */
function getJSONInput() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendErrorResponse('잘못된 JSON 형식입니다.', 400);
    }
    
    return $data;
}

/**
 * 로그 기록
 */
function logActivity($action, $data = null) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'action' => $action,
        'ip' => $ip,
        'user_agent' => $user_agent,
        'data' => $data
    ];
    
    // 로그 파일에 기록 (필요시 DB 저장으로 변경 가능)
    error_log(json_encode($log_entry, JSON_UNESCAPED_UNICODE) . "\n", 3, 'ajax_activity.log');
}

/**
 * Rate Limiting (요청 제한)
 */
function checkRateLimit($action, $limit = 60, $window = 60) {
    $user_id = $_SESSION['user_id'] ?? 0;
    $key = "rate_limit_{$user_id}_{$action}";
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'count' => 0,
            'reset_time' => time() + $window
        ];
    }
    
    // 시간 윈도우가 지났으면 리셋
    if (time() > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = [
            'count' => 0,
            'reset_time' => time() + $window
        ];
    }
    
    // 요청 수 증가
    $_SESSION[$key]['count']++;
    
    // 제한 초과 체크
    if ($_SESSION[$key]['count'] > $limit) {
        sendErrorResponse('너무 많은 요청입니다. 잠시 후 다시 시도해주세요.', 429);
    }
}

// 기본 인증 체크 실행 (이 파일을 include하면 자동 실행)
// 특정 파일에서는 이를 건너뛸 수 있도록 SKIP_AUTH_CHECK 상수 체크
if (!defined('SKIP_AUTH_CHECK') || !SKIP_AUTH_CHECK) {
    checkAuthentication();
}
?>