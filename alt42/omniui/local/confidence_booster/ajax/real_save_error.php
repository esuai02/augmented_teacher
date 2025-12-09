<?php
/**
 * 실제 오답 분석 저장 AJAX 핸들러
 * mdl_abessi_mathtalk 테이블에 저장
 */

header('Content-Type: application/json; charset=utf-8');

// 세션 체크
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

// 설정 파일
require_once('../config.php');

try {
    $pdo = get_confidence_db_connection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결 실패');
    }
    
    // POST 데이터
    $input = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $_SESSION['user_id'];
    $problem = $input['problem'] ?? '';
    $error_type = $input['error_type'] ?? '';
    $chapter = $input['chapter'] ?? '';
    $description = $input['description'] ?? '';
    $solution = $input['solution'] ?? '';
    
    if (empty($problem) || empty($error_type)) {
        throw new Exception('문제 번호와 오류 유형은 필수입니다.');
    }
    
    // 오답 데이터 구성
    $error_data = [
        'problem' => $problem,
        'error_type' => $error_type,
        'chapter' => $chapter,
        'description' => $description,
        'solution' => $solution,
        'date' => date('Y-m-d H:i:s')
    ];
    
    // mdl_abessi_mathtalk에 저장
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_mathtalk (userid, content, type, timecreated)
        VALUES (?, ?, ?, ?)
    ");
    
    $current_time = time();
    $stmt->execute([
        $user_id,
        json_encode($error_data, JSON_UNESCAPED_UNICODE),
        'error_analysis',
        $current_time
    ]);
    
    // 활동 로그
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([$user_id, 'error_analysis', $current_time]);
    
    // 챕터 로그에도 기록
    if (!empty($chapter)) {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_chapterlog (userid, chapter, progress, timecreated)
            VALUES (?, ?, ?, ?)
        ");
        
        $chapter_data = [
            'error_analysis' => true,
            'problem' => $problem,
            'error_type' => $error_type
        ];
        
        $stmt->execute([
            $user_id,
            $chapter,
            json_encode($chapter_data, JSON_UNESCAPED_UNICODE),
            $current_time
        ]);
    }
    
    // 오답 패턴 업데이트 (progress에 저장)
    $stmt = $pdo->prepare("SELECT progress_data FROM mdl_abessi_progress WHERE userid = ?");
    $stmt->execute([$user_id]);
    $existing = $stmt->fetch();
    
    $progress = $existing ? json_decode($existing['progress_data'], true) : [];
    if (!isset($progress['error_patterns'])) {
        $progress['error_patterns'] = [];
    }
    if (!isset($progress['error_patterns'][$error_type])) {
        $progress['error_patterns'][$error_type] = 0;
    }
    $progress['error_patterns'][$error_type]++;
    
    if ($existing) {
        $stmt = $pdo->prepare("
            UPDATE mdl_abessi_progress 
            SET progress_data = ?, timemodified = ?
            WHERE userid = ?
        ");
        $stmt->execute([
            json_encode($progress, JSON_UNESCAPED_UNICODE),
            $current_time,
            $user_id
        ]);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_progress (userid, progress_data, timecreated, timemodified)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            json_encode($progress, JSON_UNESCAPED_UNICODE),
            $current_time,
            $current_time
        ]);
    }
    
    // 유사 오답 찾기
    $stmt = $pdo->prepare("
        SELECT content FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'error_analysis'
        AND content LIKE ?
        AND timecreated != ?
        ORDER BY timecreated DESC
        LIMIT 3
    ");
    
    $search = '%"error_type":"' . $error_type . '"%';
    $stmt->execute([$user_id, $search, $current_time]);
    $similar_errors = [];
    
    while ($row = $stmt->fetch()) {
        $content = json_decode($row['content'], true);
        if ($content) {
            $similar_errors[] = [
                'problem' => $content['problem'] ?? '',
                'chapter' => $content['chapter'] ?? ''
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => '오답이 분석되었습니다!',
        'data' => [
            'similar_errors' => $similar_errors,
            'total_errors' => $progress['error_patterns'][$error_type] ?? 1,
            'tip' => getErrorTip($error_type)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function getErrorTip($type) {
    $tips = [
        'calculation' => '계산 과정을 단계별로 나누어 검산하는 습관을 기르세요.',
        'concept' => '기본 개념을 다시 정리하고 예제를 풀어보세요.',
        'application' => '문제에서 구하는 것이 무엇인지 명확히 파악하세요.',
        'careless' => '문제를 두 번 읽고 중요한 조건에 밑줄을 그으세요.'
    ];
    
    return $tips[$type] ?? '꾸준한 연습이 실력 향상의 지름길입니다.';
}
?>