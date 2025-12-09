<?php
/**
 * Confidence Booster 플러그인 설정 파일
 * 
 * @package    local_confidence_booster
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 기존 config.php 파일을 포함하여 DB 설정 상속
$parent_config = dirname(dirname(dirname(__FILE__))) . '/config.php';
if (file_exists($parent_config)) {
    require_once($parent_config);
}

// Confidence Booster 전용 설정
if (!defined('CONFIDENCE_BOOSTER_VERSION')) {
    define('CONFIDENCE_BOOSTER_VERSION', '1.0.0');
    define('CONFIDENCE_BOOSTER_ENABLED', true);
    
    // 플러그인 경로 설정
    define('CONFIDENCE_BOOSTER_PATH', dirname(__FILE__));
    define('CONFIDENCE_BOOSTER_URL', '/moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster');
    
    // 학생별 설정 (이현선 학생)
    define('CONFIDENCE_TARGET_STUDENT', '이현선');
    define('CONFIDENCE_TARGET_GRADE', '고등학교 2학년');
    define('CONFIDENCE_TARGET_SUBJECT', '미적분');
    define('CONFIDENCE_TARGET_LEVEL', '하');
    
    // 학습 목표 설정
    define('CONFIDENCE_DAILY_SUMMARY_GOAL', 1);  // 일일 최소 요약 개수
    define('CONFIDENCE_WEEKLY_SUMMARY_GOAL', 5); // 주간 최소 요약 개수
    define('CONFIDENCE_ERROR_CLASSIFICATION_GOAL', 0.9); // 오답 분류율 목표 (90%)
    define('CONFIDENCE_CHALLENGE_SUCCESS_INITIAL', 0.3); // 초기 도전 성공률 목표 (30%)
    define('CONFIDENCE_CHALLENGE_SUCCESS_TARGET', 0.6);  // 3개월 후 목표 (60%)
    
    // AI 피드백 설정 (기존 OpenAI 설정 활용)
    define('CONFIDENCE_AI_ENABLED', defined('OPENAI_API_KEY') && OPENAI_API_KEY !== '');
    define('CONFIDENCE_AI_MODEL', defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o');
    define('CONFIDENCE_AI_MAX_TOKENS', 200); // 요약 피드백용 토큰 수
    define('CONFIDENCE_AI_TEMPERATURE', 0.5); // 더 일관된 피드백을 위해 낮은 온도
    
    // 캐싱 설정
    define('CONFIDENCE_CACHE_ENABLED', true);
    define('CONFIDENCE_CACHE_TIMEOUT', 300); // 5분
    
    // 세션 설정
    define('CONFIDENCE_SESSION_KEY', 'confidence_booster_session');
    
    // 로깅 설정
    define('CONFIDENCE_LOG_ENABLED', true);
    define('CONFIDENCE_LOG_PATH', CONFIDENCE_BOOSTER_PATH . '/logs');
    
    // UI 설정
    define('CONFIDENCE_THEME_COLOR', '#667eea'); // 보라색 그라데이션 메인 컬러
    define('CONFIDENCE_SUCCESS_COLOR', '#48bb78'); // 성공 표시 색상
    define('CONFIDENCE_WARNING_COLOR', '#ed8936'); // 경고 표시 색상
    define('CONFIDENCE_DANGER_COLOR', '#f56565');  // 위험 표시 색상
    
    // 배지 시스템 설정
    define('CONFIDENCE_BADGES', json_encode([
        'first_summary' => ['name' => '첫 요약 작성', 'icon' => '✍️'],
        'week_warrior' => ['name' => '주간 전사', 'icon' => '⚔️'],
        'error_master' => ['name' => '오답 마스터', 'icon' => '🎯'],
        'challenge_winner' => ['name' => '도전 승리자', 'icon' => '🏆'],
        'perfect_week' => ['name' => '완벽한 한 주', 'icon' => '💎'],
        'improvement_star' => ['name' => '성장의 별', 'icon' => '⭐'],
    ]));
    
    // 오답 분류 카테고리
    define('CONFIDENCE_ERROR_TYPES', json_encode([
        'concept' => '개념 이해 부족',
        'calculation' => '계산 실수',
        'mistake' => '단순 실수',
        'application' => '응용력 부족'
    ]));
    
    // 도전 레벨 설정
    define('CONFIDENCE_CHALLENGE_LEVELS', json_encode([
        'medium' => ['name' => '중급', 'color' => '#3182ce', 'min_score' => 0],
        'hard' => ['name' => '상급', 'color' => '#e53e3e', 'min_score' => 40],
        'extreme' => ['name' => '최상급', 'color' => '#9f7aea', 'min_score' => 70]
    ]));
}

// 데이터베이스 연결 함수 (기존 DB 설정 재사용)
if (!function_exists('get_confidence_db_connection')) {
    function get_confidence_db_connection() {
        try {
            // 기존 MathKing DB 설정 사용
            $dsn = "mysql:host=" . MATHKING_DB_HOST . 
                   ";dbname=" . MATHKING_DB_NAME . 
                   ";charset=utf8mb4";
            
            $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            
            return $pdo;
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Confidence Booster DB Connection Error: " . $e->getMessage());
            }
            return false;
        }
    }
}

// 사용자 인증 체크 함수 (기존 세션 활용)
if (!function_exists('confidence_require_login')) {
    function confidence_require_login() {
        // 세션이 이미 시작되지 않았을 때만 시작
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: /moodle/local/augmented_teacher/alt42/omniui/login.php');
            exit;
        }
        
        // 세션 타임아웃 체크
        if (defined('SESSION_TIMEOUT') && 
            isset($_SESSION['login_time']) && 
            (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
            session_destroy();
            header('Location: /moodle/local/augmented_teacher/alt42/omniui/login.php');
            exit;
        }
        
        return $_SESSION['user_id'];
    }
}

// 교사 권한 체크 함수
if (!function_exists('confidence_is_teacher')) {
    function confidence_is_teacher($userid) {
        $pdo = get_confidence_db_connection();
        if (!$pdo) return false;
        
        try {
            $sql = "SELECT data FROM " . MATHKING_DB_PREFIX . "user_info_data 
                    WHERE userid = ? AND fieldid = 22";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userid]);
            $role = $stmt->fetchColumn();
            
            return $role !== 'student';
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Confidence Booster Role Check Error: " . $e->getMessage());
            }
            return false;
        }
    }
}

// 학생 정보 조회 함수
if (!function_exists('confidence_get_student_info')) {
    function confidence_get_student_info($userid) {
        $pdo = get_confidence_db_connection();
        if (!$pdo) return false;
        
        try {
            $sql = "SELECT id, username, firstname, lastname, email, phone1, phone2 
                    FROM " . MATHKING_DB_PREFIX . "user 
                    WHERE id = ? AND deleted = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userid]);
            
            return $stmt->fetch();
        } catch (PDOException $e) {
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                error_log("Confidence Booster Student Info Error: " . $e->getMessage());
            }
            return false;
        }
    }
}

// 로깅 함수
if (!function_exists('confidence_log')) {
    function confidence_log($message, $level = 'info', $context = []) {
        if (!defined('CONFIDENCE_LOG_ENABLED') || !CONFIDENCE_LOG_ENABLED) {
            return;
        }
        
        // 로그 경로를 쓰기 가능한 위치로 설정
        $log_path = defined('CONFIDENCE_LOG_PATH') ? CONFIDENCE_LOG_PATH : sys_get_temp_dir();
        $log_file = $log_path . '/confidence_booster_' . date('Y-m-d') . '.log';
        
        // 로그 디렉토리 생성 시도 (권한 에러 무시)
        if (!is_dir($log_path)) {
            @mkdir($log_path, 0777, true);
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $userid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'anonymous';
        $context_str = !empty($context) ? json_encode($context) : '';
        
        $log_entry = "[$timestamp] [$level] [User: $userid] $message $context_str\n";
        
        // 파일에 쓰기 시도 (실패해도 무시)
        @error_log($log_entry, 3, $log_file);
    }
}

// AI 피드백 생성 함수 (OpenAI API 활용)
if (!function_exists('confidence_generate_ai_feedback')) {
    function confidence_generate_ai_feedback($summary_text, $concept_title = '') {
        if (!defined('CONFIDENCE_AI_ENABLED') || !CONFIDENCE_AI_ENABLED) {
            return confidence_generate_simple_feedback($summary_text);
        }
        
        try {
            $prompt = "다음은 고등학교 2학년 학생이 '$concept_title' 개념에 대해 작성한 요약입니다:\n\n" . 
                     $summary_text . "\n\n" .
                     "이 요약에 대해 긍정적이고 건설적인 피드백을 2-3문장으로 제공해주세요. " .
                     "학생의 자신감을 높이면서도 개선점을 제시해주세요.";
            
            $data = [
                'model' => CONFIDENCE_AI_MODEL,
                'messages' => [
                    ['role' => 'system', 'content' => '당신은 친절한 수학 선생님입니다.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => CONFIDENCE_AI_MAX_TOKENS,
                'temperature' => CONFIDENCE_AI_TEMPERATURE
            ];
            
            $ch = curl_init(OPENAI_API_URL);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . OPENAI_API_KEY
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200) {
                $result = json_decode($response, true);
                if (isset($result['choices'][0]['message']['content'])) {
                    return $result['choices'][0]['message']['content'];
                }
            }
        } catch (Exception $e) {
            confidence_log('AI feedback generation failed', 'error', ['error' => $e->getMessage()]);
        }
        
        // Fallback to simple feedback
        return confidence_generate_simple_feedback($summary_text);
    }
}

// 간단한 피드백 생성 함수 (AI 불가능할 때 대체)
if (!function_exists('confidence_generate_simple_feedback')) {
    function confidence_generate_simple_feedback($summary_text) {
        $word_count = mb_strlen($summary_text);
        $has_keywords = preg_match('/(\b정의\b|\b공식\b|\b예시\b|\b중요\b)/u', $summary_text);
        
        if ($word_count < 50) {
            return "요약을 시작했네요! 👍 다음에는 핵심 개념을 조금 더 자세히 설명해보면 좋겠어요.";
        } elseif ($word_count < 150) {
            if ($has_keywords) {
                return "좋은 요약이에요! 핵심 키워드를 잘 포함시켰네요. 계속 이렇게 정리해보세요! 💪";
            } else {
                return "잘 정리했어요! 다음에는 '정의', '공식', '예시' 같은 키워드를 사용하면 더 체계적일 거예요.";
            }
        } else {
            return "아주 상세한 요약이네요! 훌륭해요! 🌟 이제는 더 간결하게 핵심만 정리하는 연습도 해보세요.";
        }
    }
}

// CSRF 토큰 생성 및 검증
if (!function_exists('confidence_generate_csrf_token')) {
    function confidence_generate_csrf_token() {
        if (!isset($_SESSION['confidence_csrf_token'])) {
            $_SESSION['confidence_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['confidence_csrf_token'];
    }
}

if (!function_exists('confidence_verify_csrf_token')) {
    function confidence_verify_csrf_token($token) {
        return isset($_SESSION['confidence_csrf_token']) && 
               hash_equals($_SESSION['confidence_csrf_token'], $token);
    }
}
?>