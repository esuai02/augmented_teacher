<?php
/**
 * AJAX Handler - 개념 요약 저장
 * 
 * @package    local_confidence_booster
 * @copyright  2024 MathKing
 */

require_once('../config.php');

// JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$userid = $_SESSION['user_id'];

// CSRF 토큰 검증
if (!isset($_POST['csrf_token']) || !confidence_verify_csrf_token($_POST['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => '보안 검증에 실패했습니다.']);
    exit;
}

// 입력값 검증
$concept_title = isset($_POST['concept_title']) ? trim($_POST['concept_title']) : '';
$summary_text = isset($_POST['summary_text']) ? trim($_POST['summary_text']) : '';
$chapter = isset($_POST['chapter']) ? trim($_POST['chapter']) : '';
$courseid = isset($_POST['courseid']) ? intval($_POST['courseid']) : 0;

if (empty($concept_title) || empty($summary_text)) {
    echo json_encode(['success' => false, 'message' => '제목과 내용을 입력해주세요.']);
    exit;
}

if (mb_strlen($summary_text) < 20) {
    echo json_encode(['success' => false, 'message' => '요약은 최소 20자 이상 작성해주세요.']);
    exit;
}

// DB 연결
$pdo = get_confidence_db_connection();
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => '데이터베이스 연결에 실패했습니다.']);
    exit;
}

try {
    // AI 피드백 생성
    $ai_feedback = confidence_generate_ai_feedback($summary_text, $concept_title);
    
    // 품질 점수 계산
    $quality_score = calculate_quality_score($summary_text);
    
    // 데이터 저장
    $sql = "INSERT INTO " . MATHKING_DB_PREFIX . "confidence_notes 
            (userid, courseid, chapter, concept_title, summary_text, ai_feedback, quality_score, timecreated) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        $userid,
        $courseid,
        $chapter,
        $concept_title,
        $summary_text,
        $ai_feedback,
        $quality_score,
        time()
    ]);
    
    if ($result) {
        $summary_id = $pdo->lastInsertId();
        
        // 일일 메트릭 업데이트
        update_daily_metrics($userid, $pdo, 'summary');
        
        // 로깅
        confidence_log('Summary saved', 'info', [
            'user_id' => $userid,
            'summary_id' => $summary_id,
            'title' => $concept_title
        ]);
        
        echo json_encode([
            'success' => true,
            'summary_id' => $summary_id,
            'feedback' => $ai_feedback,
            'score' => $quality_score,
            'message' => '요약이 저장되었습니다!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => '저장에 실패했습니다.']);
    }
    
} catch (PDOException $e) {
    confidence_log('Save summary error', 'error', ['error' => $e->getMessage()]);
    echo json_encode(['success' => false, 'message' => '처리 중 오류가 발생했습니다.']);
}

/**
 * 품질 점수 계산
 */
function calculate_quality_score($summary_text) {
    $score = 0;
    
    // 길이 점수 (0-2점)
    $length = mb_strlen($summary_text);
    if ($length >= 50 && $length <= 200) {
        $score += 2;
    } elseif ($length >= 30 || $length <= 300) {
        $score += 1;
    }
    
    // 키워드 포함 점수 (0-2점)
    $keywords = ['정의', '공식', '예시', '중요', '핵심', '원리', '개념'];
    $keyword_count = 0;
    foreach ($keywords as $keyword) {
        if (mb_strpos($summary_text, $keyword) !== false) {
            $keyword_count++;
        }
    }
    $score += min(2, $keyword_count * 0.5);
    
    // 구조화 점수 (0-1점)
    if (preg_match('/[1-9][\.\)]/u', $summary_text) || 
        preg_match('/(첫째|둘째|셋째|마지막으로)/u', $summary_text)) {
        $score += 1;
    }
    
    return min(5, round($score, 1));
}

/**
 * 일일 메트릭 업데이트
 */
function update_daily_metrics($userid, $pdo, $action) {
    $today = date('Y-m-d');
    
    // 기존 메트릭 조회
    $sql = "SELECT id, summary_count, error_classified_count, challenge_attempted 
            FROM " . MATHKING_DB_PREFIX . "confidence_metrics 
            WHERE userid = ? AND metric_date = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userid, $today]);
    $metric = $stmt->fetch();
    
    if ($metric) {
        // 업데이트
        $update_field = '';
        switch ($action) {
            case 'summary':
                $update_field = 'summary_count = summary_count + 1';
                break;
            case 'error':
                $update_field = 'error_classified_count = error_classified_count + 1';
                break;
            case 'challenge':
                $update_field = 'challenge_attempted = 1';
                break;
        }
        
        if ($update_field) {
            $sql = "UPDATE " . MATHKING_DB_PREFIX . "confidence_metrics 
                    SET $update_field 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$metric['id']]);
        }
    } else {
        // 신규 생성
        $sql = "INSERT INTO " . MATHKING_DB_PREFIX . "confidence_metrics 
                (userid, metric_date, summary_count, error_classified_count, challenge_attempted, timecreated) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $summary_count = ($action == 'summary') ? 1 : 0;
        $error_count = ($action == 'error') ? 1 : 0;
        $challenge = ($action == 'challenge') ? 1 : 0;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$userid, $today, $summary_count, $error_count, $challenge, time()]);
    }
}
?>