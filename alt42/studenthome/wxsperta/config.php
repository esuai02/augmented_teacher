<?php
/**
 * WXSPERTA 설정
 * - OpenAI 키는 Moodle 메인 config.php의 $CFG->openai_api_key 값을 우선 사용
 * - (대안) 서버 환경변수 OPENAI_API_KEY
 *
 * 주의: 이 파일에 실키를 저장하지 마세요.
 */

// OpenAI API 설정 (키는 Moodle $CFG를 우선 사용)
global $CFG;

if (!defined('OPENAI_API_KEY')) {
    $keyFromMoodle = '';
    if (isset($CFG) && isset($CFG->openai_api_key)) {
        $keyFromMoodle = trim((string)$CFG->openai_api_key);
    }
    $keyFromEnv = trim((string)getenv('OPENAI_API_KEY'));
    define('OPENAI_API_KEY', $keyFromMoodle !== '' ? $keyFromMoodle : ($keyFromEnv !== '' ? $keyFromEnv : 'your-openai-api-key-here'));
}

if (!defined('OPENAI_MODEL')) {
define('OPENAI_MODEL', 'gpt-4o');
}
if (!defined('OPENAI_API_URL')) {
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
}

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
    // 환경변수 우선 (서버에서 SetEnv OPENAI_API_KEY ... 형태로 주입 가능)
    $apiKey = getenv('OPENAI_API_KEY');
    if (!$apiKey && defined('OPENAI_API_KEY')) $apiKey = OPENAI_API_KEY;
    $apiKey = trim((string)$apiKey);

    // API 키가 설정되지 않은 경우: 데모 응답으로 폴백(시스템이 멈추지 않게)
    if ($apiKey === '' || wxsperta_is_placeholder_openai_key($apiKey)) {
        return wxsperta_demo_openai_response($messages);
    }

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        wxsperta_log("OpenAI API curl error: {$curl_error}", 'ERROR');
        return false;
    }
    
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

/**
 * OPENAI_API_KEY가 플레이스홀더인지 확인
 */
function wxsperta_is_placeholder_openai_key($key) {
    $key = trim((string)$key);
    if ($key === '') return true;
    $lower = strtolower($key);
    if (strpos($lower, 'your-api-key') !== false) return true;
    if (strpos($lower, 'your-openai-api-key') !== false) return true;
    return false;
}

/**
 * API 실패/미설정 시 데모 응답 생성
 * - 일부 호출은 JSON만 요구하므로, 프롬프트를 보고 JSON 형식도 맞춰 반환
 */
function wxsperta_demo_openai_response($messages) {
    $last_user = '';
    for ($i = count($messages) - 1; $i >= 0; $i--) {
        if (($messages[$i]['role'] ?? '') === 'user') {
            $last_user = (string)($messages[$i]['content'] ?? '');
            break;
        }
    }
    $u = trim($last_user);

    // 1) "반드시 JSON만" 형태 (글로벌 초기 멘트 등)
    if (stripos($u, '반드시 json') !== false && stripos($u, '"suggestions"') !== false) {
        $obj = [
            'message' => "안녕! 지금은 데모 모드로 대화 중이야. 그래도 너의 흐름을 같이 정리해볼 수 있어. 오늘은 전반적으로 뭐가 제일 중요한지 하나만 골라줘.",
            'suggestions' => [
                '오늘 제일 막히는 한 가지부터 정리해줘',
                '이번 주 목표를 1개로 줄여서 잡아보자',
                '요즘 컨디션/불안부터 먼저 다뤄보자'
            ]
        ];
        return json_encode($obj, JSON_UNESCAPED_UNICODE);
    }

    // 2) 홀론 업데이트 JSON
    if (stripos($u, 'related_agent_keys') !== false && stripos($u, '"updates"') !== false) {
        $obj = [
            'related_agent_keys' => [],
            'updates' => (object)[]
        ];
        return json_encode($obj, JSON_UNESCAPED_UNICODE);
    }

    // 3) WXSPERTA 레이어 추출 JSON
    if (stripos($u, 'wxsperta') !== false && stripos($u, '"worldview"') !== false) {
        $obj = [
            'worldView' => null,
            'context' => null,
            'structure' => null,
            'process' => null,
            'execution' => null,
            'reflection' => null,
            'transfer' => null,
            'abstraction' => null
        ];
        return json_encode($obj, JSON_UNESCAPED_UNICODE);
    }

    // 4) 일반 채팅 텍스트 응답(친근한 반말)
    if ($u !== '') {
        return "좋아, 지금은 데모 모드지만 대화는 계속할 수 있어. 방금 말한 걸 보면 \"$u\"가 핵심 같아. 그중에서 오늘 당장 바꿀 수 있는 한 가지는 뭐야?";
    }
    return "안녕! 지금은 데모 모드야. 그래도 같이 정리하고 다음 한 걸음은 잡을 수 있어. 오늘 제일 중요한 거 하나만 말해줘.";
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