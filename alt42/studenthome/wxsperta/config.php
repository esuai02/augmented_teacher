<?php
// OpenAI API 설정
define('OPENAI_API_KEY', 'your-openai-api-key-here');
define('OPENAI_MODEL', 'gpt-4o');
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');

// 데이터베이스 설정 (Moodle 설정에서 상속됨)
// 추가 커스텀 테이블 접두사
define('WXSPERTA_TABLE_PREFIX', 'mdl_wxsperta_');

// 시스템 설정
define('WXSPERTA_DEBUG', true);
define('WXSPERTA_CACHE_ENABLED', false);
define('WXSPERTA_SESSION_TIMEOUT', 3600); // 1시간

// AI 에이전트 관련 설정
define('MAX_AGENTS', 21);
define('AGENT_LAYERS', 8);
define('DEFAULT_PRIORITY', 50);

// 메시지 변환 설정
define('MAX_MESSAGE_LENGTH', 2000);
define('API_TIMEOUT', 30); // 30초
define('MAX_RETRY_ATTEMPTS', 3);

// 로깅 설정
define('LOG_DIR', __DIR__ . '/logs/');
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, WARNING, ERROR

// 보안 설정
define('CSRF_TOKEN_NAME', 'wxsperta_token');
define('API_RATE_LIMIT', 100); // 분당 최대 요청 수

// 시스템 URL (필요시 조정)
define('WXSPERTA_BASE_URL', '/studenthome/wxsperta/');

// 캐시 디렉토리
define('CACHE_DIR', __DIR__ . '/cache/');

// 업로드 설정
define('UPLOAD_MAX_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf']);

// 이메일 알림 설정
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('ADMIN_EMAIL', 'admin@example.com');

// 세션 설정
ini_set('session.gc_maxlifetime', WXSPERTA_SESSION_TIMEOUT);
session_set_cookie_params(WXSPERTA_SESSION_TIMEOUT);

// 에러 리포팅 설정
if (WXSPERTA_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// 필요한 디렉토리 생성 (권한 문제 방지를 위해 체크만 수행)
if (!is_dir(LOG_DIR)) {
    // 디렉토리가 없으면 경고만 표시
    if (WXSPERTA_DEBUG) {
        error_log("Warning: Log directory does not exist: " . LOG_DIR);
    }
}
if (!is_dir(CACHE_DIR)) {
    // 디렉토리가 없으면 경고만 표시
    if (WXSPERTA_DEBUG) {
        error_log("Warning: Cache directory does not exist: " . CACHE_DIR);
    }
}

// 유틸리티 함수
function wxsperta_log($message, $level = 'INFO') {
    if (!WXSPERTA_DEBUG && $level === 'DEBUG') {
        return;
    }
    
    // 디렉토리가 없으면 error_log 사용
    if (!is_dir(LOG_DIR)) {
        error_log("[WXSPERTA] [$level] $message");
        return;
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_file = LOG_DIR . date('Y-m-d') . '.log';
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    @file_put_contents($log_file, $log_message, FILE_APPEND | LOCK_EX);
}

// API 헬퍼 함수
function call_openai_api($messages, $temperature = 0.7) {
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ];
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'temperature' => $temperature,
        'max_tokens' => 1000
    ];
    
    $ch = curl_init(OPENAI_API_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, API_TIMEOUT);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200) {
        wxsperta_log("OpenAI API error: HTTP $http_code - $response", 'ERROR');
        return false;
    }
    
    $result = json_decode($response, true);
    if (isset($result['choices'][0]['message']['content'])) {
        return $result['choices'][0]['message']['content'];
    }
    
    return false;
}

// CSRF 토큰 생성 및 검증
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// 데이터베이스 헬퍼 함수
function wxsperta_get_db() {
    global $DB;
    return $DB;
}

// 사용자 권한 확인
function wxsperta_require_login() {
    require_login();
}

// 현재 사용자 정보 가져오기
function wxsperta_get_current_user() {
    global $USER;
    return $USER;
}

// 에이전트 우선순위 업데이트 함수
function update_agent_priority($agent_id, $event_type) {
    $db = wxsperta_get_db();
    
    // 이벤트 타입에 따른 우선순위 조정
    $priority_changes = [
        'exam' => ['3' => 20, '4' => 15, '6' => 10], // 시험 관련 에이전트
        'project' => ['2' => 20, '11' => 15, '7' => 10], // 프로젝트 관련
        'motivation_low' => ['5' => 25, '8' => 20, '13' => 15], // 동기 저하
        'achievement' => ['1' => 20, '15' => 15, '12' => 10] // 성취 달성
    ];
    
    if (isset($priority_changes[$event_type][$agent_id])) {
        $change = $priority_changes[$event_type][$agent_id];
        
        // 데이터베이스 업데이트 로직
        $sql = "UPDATE " . WXSPERTA_TABLE_PREFIX . "agent_priorities 
                SET priority = LEAST(100, priority + ?) 
                WHERE agent_id = ? AND user_id = ?";
        
        $user = wxsperta_get_current_user();
        $db->execute($sql, [$change, $agent_id, $user->id]);
        
        wxsperta_log("Updated agent $agent_id priority by +$change for event $event_type", 'INFO');
    }
}

// 메시지 변환 함수 (AI 사용)
function transform_message_with_ai($message, $from_style, $to_style) {
    $system_prompt = "당신은 교육 메시지를 학생의 학습 스타일에 맞게 변환하는 AI입니다. 
    메시지의 핵심 내용은 유지하되, 톤과 전달 방식을 대상 스타일에 맞게 조정하세요.
    한국어로 자연스럽게 작성하세요.";
    
    $user_prompt = "다음 메시지를 '$from_style' 스타일에서 '$to_style' 스타일로 변환해주세요:\n\n$message";
    
    $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => $user_prompt]
    ];
    
    $result = call_openai_api($messages);
    
    if ($result === false) {
        // 폴백: 기본 변환 규칙 적용
        return apply_basic_transformation($message, $to_style);
    }
    
    return $result;
}

// 기본 변환 규칙 (AI 실패 시 폴백)
function apply_basic_transformation($message, $style) {
    $transformations = [
        'competence' => [
            'prefix' => '💪 도전: ',
            'suffix' => ' 넌 할 수 있어!'
        ],
        'relatedness' => [
            'prefix' => '🤝 함께: ',
            'suffix' => ' 우리 같이 해보자!'
        ],
        'autonomy' => [
            'prefix' => '🎯 네 선택: ',
            'suffix' => ' 네가 결정해!'
        ]
    ];
    
    if (isset($transformations[$style])) {
        $t = $transformations[$style];
        return $t['prefix'] . $message . $t['suffix'];
    }
    
    return $message;
}
?>