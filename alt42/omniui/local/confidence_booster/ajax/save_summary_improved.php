<?php
/**
 * 요약 저장 AJAX 핸들러 - 개선된 버전
 * 실제 MathKing 데이터베이스 테이블 사용
 */

header('Content-Type: application/json; charset=utf-8');

// 세션 시작
session_start();

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

// 설정 파일 로드
require_once('../config.php');

try {
    // DB 연결
    $pdo = get_confidence_db_connection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    // JSON 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('잘못된 요청입니다.');
    }
    
    $user_id = $_SESSION['user_id'];
    $chapter_title = $input['chapter_title'] ?? '';
    $summary_text = $input['summary_text'] ?? '';
    $key_concepts = $input['key_concepts'] ?? [];
    $difficulty_level = intval($input['difficulty_level'] ?? 3);
    $confidence_score = intval($input['confidence_score'] ?? 50);
    $study_time = intval($input['study_time'] ?? 30);
    
    // 입력 검증
    if (empty($chapter_title) || empty($summary_text)) {
        throw new Exception('필수 정보가 누락되었습니다.');
    }
    
    if (mb_strlen($summary_text) < 50) {
        throw new Exception('요약은 최소 50자 이상 작성해주세요.');
    }
    
    // 품질 점수 계산
    $quality_score = calculateQualityScore($summary_text, $key_concepts);
    
    // 데이터 구성
    $summary_data = [
        'chapter' => $chapter_title,
        'summary' => $summary_text,
        'concepts' => $key_concepts,
        'quality' => $quality_score,
        'confidence' => $confidence_score,
        'difficulty' => $difficulty_level,
        'study_time' => $study_time,
        'date' => date('Y-m-d H:i:s')
    ];
    
    // mdl_abessi_today 테이블에 저장
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_today (userid, goals, timecreated, timemodified)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        goals = VALUES(goals),
        timemodified = VALUES(timemodified)
    ");
    
    $goals_json = json_encode($summary_data, JSON_UNESCAPED_UNICODE);
    $current_time = time();
    
    $stmt->execute([
        $user_id,
        $goals_json,
        $current_time,
        $current_time
    ]);
    
    // mdl_abessi_chapterlog에도 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_chapterlog 
        (userid, chapter, progress, timecreated)
        VALUES (?, ?, ?, ?)
    ");
    
    $progress_data = [
        'summary' => $summary_text,
        'quality' => $quality_score,
        'confidence' => $confidence_score,
        'concepts' => $key_concepts
    ];
    
    $stmt->execute([
        $user_id,
        $chapter_title,
        json_encode($progress_data, JSON_UNESCAPED_UNICODE),
        $current_time
    ]);
    
    // 활동 로그 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog 
        (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        'summary_writing',
        $current_time
    ]);
    
    // 진행 상황 업데이트
    updateProgressData($pdo, $user_id, $quality_score);
    
    // 배지 체크
    $badges_earned = checkBadges($pdo, $user_id);
    
    // 응답
    echo json_encode([
        'success' => true,
        'message' => '요약이 성공적으로 저장되었습니다!',
        'data' => [
            'quality_score' => $quality_score,
            'badges_earned' => $badges_earned,
            'streak' => getStudyStreak($pdo, $user_id)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Save summary error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * 품질 점수 계산
 */
function calculateQualityScore($text, $concepts) {
    $score = 40; // 기본 점수
    
    // 텍스트 길이 체크 (200-800자가 적정)
    $length = mb_strlen($text);
    if ($length >= 200 && $length <= 800) {
        $score += 25;
    } elseif ($length >= 100 && $length <= 1000) {
        $score += 15;
    } elseif ($length >= 50) {
        $score += 10;
    }
    
    // 핵심 개념 포함
    if (count($concepts) >= 5) {
        $score += 20;
    } elseif (count($concepts) >= 3) {
        $score += 15;
    } elseif (count($concepts) >= 1) {
        $score += 10;
    }
    
    // 문장 구조 (마침표, 느낌표, 물음표 개수)
    preg_match_all('/[.!?]/', $text, $matches);
    $sentences = count($matches[0]);
    
    if ($sentences >= 5 && $sentences <= 15) {
        $score += 15;
    } elseif ($sentences >= 3) {
        $score += 10;
    }
    
    return min(100, $score);
}

/**
 * 진행 상황 업데이트
 */
function updateProgressData($pdo, $user_id, $quality_score) {
    // 기존 진행 상황 조회
    $stmt = $pdo->prepare("
        SELECT progress_data 
        FROM mdl_abessi_progress 
        WHERE userid = ?
    ");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();
    
    $progress = $existing ? json_decode($existing['progress_data'], true) : [];
    
    // 요약 기록 추가
    if (!isset($progress['summaries'])) {
        $progress['summaries'] = [];
    }
    
    $progress['summaries'][] = [
        'date' => date('Y-m-d'),
        'quality' => $quality_score,
        'timestamp' => time()
    ];
    
    // 최근 30개만 유지
    if (count($progress['summaries']) > 30) {
        $progress['summaries'] = array_slice($progress['summaries'], -30);
    }
    
    // 평균 품질 계산
    $total = 0;
    $count = 0;
    foreach ($progress['summaries'] as $summary) {
        $total += $summary['quality'];
        $count++;
    }
    $progress['average_quality'] = $count > 0 ? round($total / $count, 1) : 0;
    
    // 업데이트 또는 삽입
    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE mdl_abessi_progress 
            SET progress_data = ?, timemodified = ?
            WHERE userid = ?
        ");
        $stmt->execute([
            json_encode($progress, JSON_UNESCAPED_UNICODE),
            time(),
            $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_progress 
            (userid, progress_data, timecreated, timemodified)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode($progress, JSON_UNESCAPED_UNICODE),
            time(),
            time()
        ]);
    }
}

/**
 * 배지 체크
 */
function checkBadges($pdo, $user_id) {
    $badges = [];
    
    // 총 요약 수 체크
    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM mdl_abessi_missionlog 
        WHERE userid = ? AND page = 'summary_writing'
    ");
    $stmt->execute([$user_id]);
    $total = $stmt->fetchColumn();
    
    if ($total == 1) {
        $badges[] = ['name' => '첫 요약', 'icon' => '🌱'];
    } elseif ($total == 10) {
        $badges[] = ['name' => '요약 입문', 'icon' => '🌟'];
    } elseif ($total == 50) {
        $badges[] = ['name' => '요약 전문가', 'icon' => '⭐'];
    } elseif ($total == 100) {
        $badges[] = ['name' => '요약 마스터', 'icon' => '🏆'];
    }
    
    // 연속 일수 체크
    $streak = getStudyStreak($pdo, $user_id);
    if ($streak == 3) {
        $badges[] = ['name' => '3일 연속', 'icon' => '✨'];
    } elseif ($streak == 7) {
        $badges[] = ['name' => '일주일 연속', 'icon' => '🔥'];
    } elseif ($streak == 30) {
        $badges[] = ['name' => '한 달 연속', 'icon' => '💎'];
    }
    
    return $badges;
}

/**
 * 연속 학습 일수 계산
 */
function getStudyStreak($pdo, $user_id) {
    $streak = 0;
    
    for ($i = 0; $i < 365; $i++) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $start = strtotime($date . ' 00:00:00');
        $end = strtotime($date . ' 23:59:59');
        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM mdl_abessi_missionlog 
            WHERE userid = ? 
            AND timecreated BETWEEN ? AND ?
        ");
        $stmt->execute([$user_id, $start, $end]);
        
        if ($stmt->fetchColumn() > 0) {
            $streak++;
        } else {
            if ($i > 0) break; // 오늘이 아닌 날에 활동이 없으면 중단
        }
    }
    
    return $streak;
}
?>