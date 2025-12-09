<?php
/**
 * 커리큘럼 오케스트레이터 설정 파일
 * 시험 D-30 자동 커리큘럼 편성 시스템
 * 
 * @author MathKing Curriculum System
 * @version 1.0.0
 */

// 기존 설정 파일 포함
require_once __DIR__ . '/config.php';

// 커리큘럼 시스템 설정
define('CURRICULUM_VERSION', '1.0.0');
define('CURRICULUM_PREFIX', 'mdl_curriculum_');

// 학습 비율 설정 (선행:복습)
define('RATIO_LEAD', 70);  // 선행학습 70%
define('RATIO_REVIEW', 30); // 복습 30%

// 일일 학습 시간 기본값 (분)
define('DAILY_STUDY_MINUTES', 120); // 2시간
define('MIN_ITEM_MINUTES', 10);     // 최소 학습 단위
define('MAX_ITEM_MINUTES', 30);     // 최대 학습 단위

// 커리큘럼 상태
define('PLAN_STATUS_DRAFT', 'draft');
define('PLAN_STATUS_PUBLISHED', 'published');
define('PLAN_STATUS_ACTIVE', 'active');
define('PLAN_STATUS_COMPLETED', 'completed');
define('PLAN_STATUS_CANCELLED', 'cancelled');

// 항목 타입
define('ITEM_TYPE_CONCEPT', 'concept');      // 개념학습
define('ITEM_TYPE_REVIEW', 'review');        // 복습(오답)
define('ITEM_TYPE_PRACTICE', 'practice');    // 문제풀이
define('ITEM_TYPE_TEST', 'test');           // 모의고사

// 참조 타입
define('REF_TYPE_CONCEPT', 'concept');      // 개념 참조
define('REF_TYPE_QUESTION', 'question');    // 문제 참조
define('REF_TYPE_TEST', 'test');           // 시험 참조
define('REF_TYPE_VIDEO', 'video');         // 동영상 참조

// 태그 설정 (동적 조회용)
$CURRICULUM_TAGS = [
    'lead' => ['선행개념', '개념요약', '예제퀴즈', '기본개념'],
    'review' => ['오답', '복습필요', '재학습', '보충학습'],
    'priority' => ['중요', '핵심', '필수', '시험출제'],
];

// 난이도 레벨
define('DIFFICULTY_EASY', 1);
define('DIFFICULTY_NORMAL', 2);
define('DIFFICULTY_HARD', 3);
define('DIFFICULTY_EXPERT', 4);

// KPI 임계값
define('KPI_COMPLETION_WARNING', 70);  // 완료율 경고 (%)
define('KPI_REVIEW_TARGET', 80);       // 오답 해결 목표 (%)
define('KPI_DAILY_TARGET', 90);        // 일일 이행 목표 (%)

// 캐시 설정
define('CURRICULUM_CACHE_TTL', 3600);  // 1시간

// 알림 설정
define('ALERT_BEFORE_EXAM_DAYS', [30, 14, 7, 3, 1]); // D-30, D-14, D-7, D-3, D-1
define('ALERT_DAILY_REMINDER_TIME', '08:00:00');     // 매일 알림 시간

// 에러 메시지
define('ERROR_NO_EXAM_DATE', '시험일이 설정되지 않았습니다.');
define('ERROR_NO_COURSE', '대상 과정이 선택되지 않았습니다.');
define('ERROR_NO_PERMISSION', '권한이 없습니다.');
define('ERROR_INVALID_RATIO', '학습 비율이 올바르지 않습니다.');

// 성공 메시지
define('SUCCESS_PLAN_CREATED', '커리큘럼이 성공적으로 생성되었습니다.');
define('SUCCESS_PLAN_PUBLISHED', '커리큘럼이 배포되었습니다.');
define('SUCCESS_PLAN_UPDATED', '커리큘럼이 업데이트되었습니다.');

// 데이터베이스 함수
function getCurriculumDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Curriculum DB connection error: " . $e->getMessage());
            throw $e;
        }
    }
    return $pdo;
}

// 세션 체크 함수
function checkCurriculumSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => ERROR_NO_PERMISSION]);
        exit;
    }
    
    return $_SESSION['user_id'];
}

// 교사 권한 체크
function isTeacherRole($userId) {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT data 
        FROM mdl_user_info_data 
        WHERE userid = ? AND fieldid = 22
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $role = $stmt->fetchColumn();
    
    return $role !== 'student';
}

// JSON 응답 헬퍼
function jsonResponse($success, $data = null, $message = '') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => time()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 로그 기록 함수
function logCurriculumAction($action, $data = []) {
    if (DEBUG_MODE) {
        error_log("Curriculum Action: $action - " . json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}
?>