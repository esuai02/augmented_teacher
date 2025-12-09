<?php
header('Content-Type: application/json; charset=utf-8');

// 세션 시작
session_start();

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '로그인이 필요합니다.']);
    exit;
}

// 데이터베이스 연결
require_once 'config.php';

try {
    // PDO 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // POST 데이터 받기
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('잘못된 요청입니다.');
    }
    
    $user_id = $_SESSION['user_id'];
    $problem_number = $data['problem_number'] ?? '';
    $problem_type = $data['problem_type'] ?? '';
    $error_type = $data['error_type'] ?? '';
    $error_description = $data['error_description'] ?? '';
    $solution_strategy = $data['solution_strategy'] ?? '';
    $confidence_after = intval($data['confidence_after'] ?? 50);
    $chapter = $data['chapter'] ?? '';
    $difficulty = $data['difficulty'] ?? 'medium';
    
    // 입력 검증
    if (empty($problem_number) || empty($error_type)) {
        throw new Exception('필수 정보가 누락되었습니다.');
    }
    
    // 오답 분석 품질 점수 계산
    $analysis_score = calculateErrorAnalysisQuality($error_description, $solution_strategy);
    
    // 오답 데이터 구성
    $error_data = [
        'problem' => $problem_number,
        'type' => $problem_type,
        'error_type' => $error_type,
        'description' => $error_description,
        'solution' => $solution_strategy,
        'confidence' => $confidence_after,
        'chapter' => $chapter,
        'difficulty' => $difficulty,
        'analysis_score' => $analysis_score,
        'date' => date('Y-m-d H:i:s')
    ];
    
    // mdl_abessi_mathtalk 테이블에 오답 기록 저장
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_mathtalk 
        (userid, content, type, timecreated)
        VALUES (?, ?, ?, ?)
    ");
    
    $content_json = json_encode($error_data, JSON_UNESCAPED_UNICODE);
    $current_time = time();
    
    $stmt->execute([
        $user_id,
        $content_json,
        'error_analysis',
        $current_time
    ]);
    
    // 오답 패턴 분석 및 저장
    updateErrorPatterns($pdo, $user_id, $error_type, $problem_type);
    
    // 챕터별 오답 통계 업데이트
    updateChapterErrorStats($pdo, $user_id, $chapter, $error_type);
    
    // 활동 로그 기록
    $stmt = $pdo->prepare("
        INSERT INTO mdl_abessi_missionlog 
        (userid, page, timecreated)
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        'error_analysis',
        $current_time
    ]);
    
    // 유사 오답 패턴 찾기
    $similar_errors = findSimilarErrors($pdo, $user_id, $error_type, $problem_type);
    
    // AI 피드백 생성 (옵션)
    $ai_feedback = generateAIFeedback($error_type, $error_description, $solution_strategy);
    
    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '오답이 성공적으로 기록되었습니다.',
        'data' => [
            'analysis_score' => $analysis_score,
            'similar_errors' => $similar_errors,
            'ai_feedback' => $ai_feedback,
            'pattern_insight' => getPatternInsight($pdo, $user_id, $error_type),
            'improvement_tips' => getImprovementTips($error_type),
            'total_errors_recorded' => getTotalErrors($pdo, $user_id),
            'error_reduction_rate' => calculateErrorReduction($pdo, $user_id, $error_type)
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("Save error analysis: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// 오답 분석 품질 점수 계산
function calculateErrorAnalysisQuality($description, $solution) {
    $score = 40; // 기본 점수
    
    // 설명 길이 체크
    if (mb_strlen($description) >= 50) {
        $score += 20;
    }
    
    // 해결 전략 포함
    if (mb_strlen($solution) >= 30) {
        $score += 20;
    }
    
    // 구체적 키워드 체크
    $keywords = ['왜', '때문에', '원인', '이해', '방법', '다음에는'];
    foreach ($keywords as $keyword) {
        if (mb_strpos($description . $solution, $keyword) !== false) {
            $score += 5;
        }
    }
    
    return min(100, $score);
}

// 오답 패턴 업데이트
function updateErrorPatterns($pdo, $user_id, $error_type, $problem_type) {
    // mdl_abessi_progress에서 패턴 정보 가져오기
    $stmt = $pdo->prepare("
        SELECT progress_data FROM mdl_abessi_progress 
        WHERE userid = ?
    ");
    $stmt->execute([$user_id]);
    $progress_data = $stmt->fetchColumn();
    
    $progress = $progress_data ? json_decode($progress_data, true) : [];
    
    if (!isset($progress['error_patterns'])) {
        $progress['error_patterns'] = [];
    }
    
    if (!isset($progress['error_patterns'][$error_type])) {
        $progress['error_patterns'][$error_type] = [
            'count' => 0,
            'types' => [],
            'last_occurrence' => null
        ];
    }
    
    $progress['error_patterns'][$error_type]['count']++;
    $progress['error_patterns'][$error_type]['types'][] = $problem_type;
    $progress['error_patterns'][$error_type]['last_occurrence'] = time();
    
    // 업데이트
    $update = $pdo->prepare("
        UPDATE mdl_abessi_progress 
        SET progress_data = ?, timemodified = ?
        WHERE userid = ?
    ");
    
    $update->execute([
        json_encode($progress, JSON_UNESCAPED_UNICODE),
        time(),
        $user_id
    ]);
}

// 챕터별 오답 통계
function updateChapterErrorStats($pdo, $user_id, $chapter, $error_type) {
    if (empty($chapter)) return;
    
    $stmt = $pdo->prepare("
        SELECT progress FROM mdl_abessi_chapterlog 
        WHERE userid = ? AND chapter = ?
        ORDER BY timecreated DESC LIMIT 1
    ");
    
    $stmt->execute([$user_id, $chapter]);
    $existing = $stmt->fetchColumn();
    
    $chapter_data = $existing ? json_decode($existing, true) : [];
    
    if (!isset($chapter_data['errors'])) {
        $chapter_data['errors'] = [];
    }
    
    $chapter_data['errors'][] = [
        'type' => $error_type,
        'date' => date('Y-m-d'),
        'time' => time()
    ];
    
    // 새로운 기록 삽입
    $insert = $pdo->prepare("
        INSERT INTO mdl_abessi_chapterlog 
        (userid, chapter, progress, timecreated)
        VALUES (?, ?, ?, ?)
    ");
    
    $insert->execute([
        $user_id,
        $chapter,
        json_encode($chapter_data, JSON_UNESCAPED_UNICODE),
        time()
    ]);
}

// 유사 오답 찾기
function findSimilarErrors($pdo, $user_id, $error_type, $problem_type) {
    $stmt = $pdo->prepare("
        SELECT content FROM mdl_abessi_mathtalk
        WHERE userid = ? 
        AND type = 'error_analysis'
        AND content LIKE ?
        ORDER BY timecreated DESC
        LIMIT 5
    ");
    
    $search_pattern = '%"error_type":"' . $error_type . '"%';
    $stmt->execute([$user_id, $search_pattern]);
    
    $similar = [];
    while ($row = $stmt->fetch()) {
        $data = json_decode($row['content'], true);
        if ($data && $data['error_type'] == $error_type) {
            $similar[] = [
                'problem' => $data['problem'] ?? '',
                'date' => $data['date'] ?? '',
                'solved' => isset($data['confidence']) && $data['confidence'] > 70
            ];
        }
    }
    
    return $similar;
}

// AI 피드백 생성 (시뮬레이션)
function generateAIFeedback($error_type, $description, $solution) {
    $feedback_templates = [
        'calculation' => '계산 실수는 충분한 연습으로 개선됩니다. 단계별로 검산하는 습관을 기르세요.',
        'concept' => '개념 이해가 중요합니다. 기본 정의를 다시 확인하고 예제를 풀어보세요.',
        'application' => '문제 적용 능력을 기르려면 다양한 유형의 문제를 접해보는 것이 좋습니다.',
        'careless' => '실수를 줄이려면 문제를 꼼꼼히 읽고, 풀이 과정을 체계적으로 작성하세요.'
    ];
    
    $base_feedback = $feedback_templates[$error_type] ?? '꾸준한 연습과 복습이 중요합니다.';
    
    // 설명이 구체적일수록 더 상세한 피드백
    if (mb_strlen($description) > 100) {
        $base_feedback .= ' 오답 분석을 매우 구체적으로 작성했네요! 이런 자세가 실력 향상의 지름길입니다.';
    }
    
    return $base_feedback;
}

// 패턴 통찰
function getPatternInsight($pdo, $user_id, $error_type) {
    $stmt = $pdo->prepare("
        SELECT progress_data FROM mdl_abessi_progress 
        WHERE userid = ?
    ");
    $stmt->execute([$user_id]);
    $data = $stmt->fetchColumn();
    
    if (!$data) return '첫 오답 분석입니다. 꾸준히 기록해보세요!';
    
    $progress = json_decode($data, true);
    if (isset($progress['error_patterns'][$error_type])) {
        $count = $progress['error_patterns'][$error_type]['count'];
        if ($count > 5) {
            return "이 유형의 오류가 {$count}번 반복되었습니다. 집중 학습이 필요합니다.";
        } elseif ($count > 2) {
            return "비슷한 실수가 {$count}번 있었네요. 패턴을 인식하고 개선해봅시다.";
        }
    }
    
    return '오답 패턴을 분석 중입니다. 계속 기록해주세요.';
}

// 개선 팁
function getImprovementTips($error_type) {
    $tips = [
        'calculation' => [
            '계산 과정을 단계별로 나누어 작성하기',
            '각 단계마다 검산하기',
            '자주 실수하는 계산 유형 정리하기'
        ],
        'concept' => [
            '교과서 기본 개념 다시 읽기',
            '개념 설명을 자신의 말로 정리하기',
            '관련 예제 3개 이상 풀어보기'
        ],
        'application' => [
            '문제에서 구하는 것이 무엇인지 명확히 파악하기',
            '주어진 조건 모두 활용했는지 확인하기',
            '비슷한 유형 문제 추가로 풀어보기'
        ],
        'careless' => [
            '문제 두 번 읽기',
            '중요한 조건에 밑줄 긋기',
            '답 작성 후 문제와 대조하기'
        ]
    ];
    
    return $tips[$error_type] ?? ['꾸준한 연습', '오답노트 작성', '주기적인 복습'];
}

// 총 오답 수
function getTotalErrors($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_mathtalk
        WHERE userid = ? AND type = 'error_analysis'
    ");
    $stmt->execute([$user_id]);
    return $stmt->fetchColumn();
}

// 오류 감소율 계산
function calculateErrorReduction($pdo, $user_id, $error_type) {
    // 최근 30일과 그 이전 30일 비교
    $thirty_days_ago = time() - (30 * 24 * 60 * 60);
    $sixty_days_ago = time() - (60 * 24 * 60 * 60);
    
    // 이전 30일 오류 수
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_mathtalk
        WHERE userid = ? 
        AND type = 'error_analysis'
        AND content LIKE ?
        AND timecreated BETWEEN ? AND ?
    ");
    
    $search = '%"error_type":"' . $error_type . '"%';
    $stmt->execute([$user_id, $search, $sixty_days_ago, $thirty_days_ago]);
    $previous_count = $stmt->fetchColumn();
    
    // 최근 30일 오류 수
    $stmt->execute([$user_id, $search, $thirty_days_ago, time()]);
    $recent_count = $stmt->fetchColumn();
    
    if ($previous_count > 0) {
        $reduction = (($previous_count - $recent_count) / $previous_count) * 100;
        return round($reduction, 1);
    }
    
    return 0;
}
?>