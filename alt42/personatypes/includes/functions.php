<?php
// 공통 함수 모음
// PHP 7.3 호환

/**
 * 안전한 파라미터 가져오기 (Moodle 방식)
 */
function ss_get_param($name, $type = PARAM_TEXT, $default = null) {
    global $CFG;
    if (isset($_GET[$name])) {
        return clean_param($_GET[$name], $type);
    } elseif (isset($_POST[$name])) {
        return clean_param($_POST[$name], $type);
    }
    return $default;
}

/**
 * JSON 응답 출력
 */
function ss_json_response($success, $data = null, $error = null) {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = array('success' => $success);
    
    if ($success && $data !== null) {
        $response['data'] = $data;
    } elseif (!$success && $error !== null) {
        $response['error'] = $error;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 에러 로깅
 */
function ss_log_error($message, $context = array()) {
    $log_entry = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => 'error',
        'message' => $message,
        'context' => $context
    );
    
    $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    error_log($log_line, 3, SS_ERROR_LOG);
}

/**
 * 일반 로깅
 */
function ss_log($level, $message, $category = 'general', $context = array()) {
    if (!ss_should_log($level)) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'category' => $category,
        'message' => $message,
        'context' => $context
    );
    
    $log_line = json_encode($log_entry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
    error_log($log_line, 3, SS_LOG_FILE);
}

/**
 * 로그 레벨 확인
 */
function ss_should_log($level) {
    $levels = array('debug' => 1, 'info' => 2, 'warning' => 3, 'error' => 4);
    $current_level = $levels[SS_LOG_LEVEL] ?? 2;
    $check_level = $levels[$level] ?? 2;
    return $check_level >= $current_level;
}

/**
 * AI API 사용 로깅
 */
function ss_log_ai_usage($user_id, $tokens, $response_time, $model = 'gpt-4') {
    $log_entry = array(
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $user_id,
        'model' => $model,
        'tokens' => $tokens,
        'response_time' => $response_time
    );
    
    $log_line = json_encode($log_entry) . PHP_EOL;
    error_log($log_line, 3, SS_AI_LOG);
}

/**
 * 사용자 역할 확인
 */
function ss_get_user_role($userid = null) {
    global $DB, $USER;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Moodle의 사용자 정보 필드에서 역할 확인
    $role_record = $DB->get_record_sql(
        "SELECT data FROM {user_info_data} WHERE userid = ? AND fieldid = 22",
        array($userid)
    );
    
    if ($role_record && $role_record->data) {
        return $role_record->data;
    }
    
    // 기본값은 student
    return 'student';
}

/**
 * 권한 확인
 */
function ss_require_role($required_role) {
    $user_role = ss_get_user_role();
    
    if ($required_role === 'teacher' && $user_role !== 'teacher' && $user_role !== 'admin') {
        ss_json_response(false, null, array(
            'code' => 'PERMISSION_DENIED',
            'message' => '권한이 없습니다.'
        ));
    }
    
    if ($required_role === 'admin' && $user_role !== 'admin') {
        ss_json_response(false, null, array(
            'code' => 'PERMISSION_DENIED',
            'message' => '관리자 권한이 필요합니다.'
        ));
    }
}

/**
 * XSS 방지를 위한 출력 이스케이핑
 */
function ss_escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * 배열을 안전하게 JSON 문자열로 변환 (MySQL 5.2.1용)
 */
function ss_json_encode($data) {
    return json_encode($data, JSON_UNESCAPED_UNICODE);
}

/**
 * JSON 문자열을 배열로 변환
 */
function ss_json_decode($json) {
    if (empty($json)) {
        return array();
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : array();
}

/**
 * 현재 시간 가져오기
 */
function ss_now() {
    return date('Y-m-d H:i:s');
}

/**
 * 시간 차이 계산 (초 단위)
 */
function ss_time_diff($start, $end = null) {
    if ($end === null) {
        $end = time();
    } else {
        $end = strtotime($end);
    }
    $start = strtotime($start);
    return $end - $start;
}

/**
 * 파일 업로드 검증
 */
function ss_validate_upload($file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return array('valid' => false, 'error' => '파일 업로드 실패');
    }
    
    if ($file['size'] > SS_MAX_UPLOAD_SIZE) {
        return array('valid' => false, 'error' => '파일 크기가 너무 큽니다');
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, SS_ALLOWED_EXTENSIONS)) {
        return array('valid' => false, 'error' => '허용되지 않는 파일 형식입니다');
    }
    
    return array('valid' => true);
}

/**
 * Rate Limiting 확인
 */
function ss_check_rate_limit($user_id, $type = 'api') {
    session_start();
    $key = 'rate_limit_' . $type . '_' . $user_id;
    $limit = ($type === 'ai') ? SS_AI_RATE_LIMIT : SS_API_RATE_LIMIT;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = array(
            'count' => 0,
            'reset_time' => time() + 60
        );
    }
    
    if (time() > $_SESSION[$key]['reset_time']) {
        $_SESSION[$key] = array(
            'count' => 1,
            'reset_time' => time() + 60
        );
        return true;
    }
    
    if ($_SESSION[$key]['count'] >= $limit) {
        return false;
    }
    
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * 데이터베이스 트랜잭션 헬퍼
 */
function ss_transaction($callback) {
    global $DB;
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        $result = $callback();
        $transaction->allow_commit();
        return $result;
    } catch (Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }
}

/**
 * 학생 프로필 가져오기 또는 생성
 */
function ss_get_or_create_profile($user_id) {
    global $DB;
    
    $profile = $DB->get_record('ss_student_profiles', array('user_id' => $user_id));
    
    if (!$profile) {
        $profile = new stdClass();
        $profile->user_id = $user_id;
        $profile->learning_style = 'visual';
        $profile->math_confidence_level = 50;
        $profile->dopamine_baseline = 50;
        $profile->created_at = ss_now();
        $profile->id = $DB->insert_record('ss_student_profiles', $profile);
    }
    
    return $profile;
}