<?php
/**
 * AI 분석 데이터 조회 AJAX 핸들러
 * 21단계 에이전트 분석 결과를 실시간으로 조회하고 생성
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// 설정 파일 포함
require_once('config.php');

// 사용자 확인
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$force_refresh = isset($_GET['refresh']) ? $_GET['refresh'] === 'true' : false;

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user']);
    exit;
}

// 데이터베이스 연결
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // Alt42t DB 연결 (시험 정보)
    $dsn_alt = "mysql:host=" . ALT42T_DB_HOST . ";dbname=" . ALT42T_DB_NAME . ";charset=utf8mb4";
    $pdo_alt = new PDO($dsn_alt, ALT42T_DB_USER, ALT42T_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database connection error']);
    exit;
}

// 21단계 AI 에이전트 분석 함수
function performAIAnalysis($pdo, $pdo_alt, $user_id) {
    $analysis = [];

    try {
        // 1-7단계: 학습 상태 진단

        // 1. 온보딩 분석
        $stmt = $pdo->prepare("
            SELECT u.*, uid.data as role
            FROM mdl_user u
            LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
            WHERE u.id = ? AND u.deleted = 0
        ");
        $stmt->execute([$user_id]);
        $userProfile = $stmt->fetch();

        $analysis[] = [
            'agent_type' => 'onboarding',
            'agent_level' => 1,
            'analysis_data' => json_encode([
                'summary' => '학습 프로필: ' . ($userProfile ? $userProfile['firstname'] . ' ' . $userProfile['lastname'] : '알 수 없음'),
                'details' => $userProfile
            ]),
            'confidence_score' => $userProfile ? 95.0 : 50.0
        ];

        // 2. 시험일정 식별
        $examInfo = null;
        if ($pdo_alt) {
            $stmt = $pdo_alt->prepare("
                SELECT * FROM student_exam_settings
                WHERE user_id = ?
                ORDER BY exam_start_date DESC LIMIT 1
            ");
            $stmt->execute([$user_id]);
            $examInfo = $stmt->fetch();
        }

        $daysUntilExam = $examInfo ?
            max(0, floor((strtotime($examInfo['exam_start_date']) - time()) / 86400)) : null;

        $analysis[] = [
            'agent_type' => 'exam_schedule',
            'agent_level' => 2,
            'analysis_data' => json_encode([
                'summary' => $daysUntilExam !== null ? "D-$daysUntilExam 시험 대비" : "시험 일정 미등록",
                'exam_info' => $examInfo,
                'days_remaining' => $daysUntilExam
            ]),
            'confidence_score' => $examInfo ? 90.0 : 40.0
        ];

        // 3. 활동 조정
        $stmt = $pdo->prepare("
            SELECT page, COUNT(*) as count,
                   DATE_FORMAT(FROM_UNIXTIME(timecreated), '%H') as hour
            FROM mdl_abessi_missionlog
            WHERE userid = ? AND timecreated > ?
            GROUP BY page, hour
            ORDER BY count DESC
            LIMIT 10
        ");
        $sevenDaysAgo = time() - (7 * 24 * 60 * 60);
        $stmt->execute([$user_id, $sevenDaysAgo]);
        $activityPatterns = $stmt->fetchAll();

        $peakHour = '';
        if (!empty($activityPatterns)) {
            $hourCounts = array_column($activityPatterns, 'count', 'hour');
            arsort($hourCounts);
            $peakHour = key($hourCounts) . '시';
        }

        $analysis[] = [
            'agent_type' => 'activity_adjustment',
            'agent_level' => 3,
            'analysis_data' => json_encode([
                'summary' => $peakHour ? "주 학습 시간: $peakHour" : "활동 패턴 분석 중",
                'patterns' => $activityPatterns
            ]),
            'confidence_score' => count($activityPatterns) > 5 ? 85.0 : 60.0
        ];

        // 4. 문제활동 식별
        $studyLevel = $examInfo['study_level'] ?? 'unknown';
        $recommendation = match($studyLevel) {
            'concept' => '개념 이해 단계 - 문제풀이 전환 권장',
            'review' => '복습 단계 - 심화 문제 도전 권장',
            'practice' => '실전 연습 단계 - 시간 관리 집중',
            default => '학습 단계 설정 필요'
        };

        $analysis[] = [
            'agent_type' => 'problem_activity',
            'agent_level' => 4,
            'analysis_data' => json_encode([
                'summary' => $recommendation,
                'current_level' => $studyLevel
            ]),
            'confidence_score' => $studyLevel !== 'unknown' ? 80.0 : 50.0
        ];

        // 5. 학습감정 분석
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as recent_activities
            FROM mdl_abessi_missionlog
            WHERE userid = ? AND timecreated > ?
        ");
        $threeDaysAgo = time() - (3 * 24 * 60 * 60);
        $stmt->execute([$user_id, $threeDaysAgo]);
        $recentActivity = $stmt->fetch();

        $motivationLevel = 'medium';
        if ($recentActivity['recent_activities'] > 20) {
            $motivationLevel = 'high';
        } elseif ($recentActivity['recent_activities'] < 5) {
            $motivationLevel = 'low';
        }

        $analysis[] = [
            'agent_type' => 'learning_emotion',
            'agent_level' => 5,
            'analysis_data' => json_encode([
                'summary' => "학습 동기: " . ($motivationLevel === 'high' ? '높음' :
                    ($motivationLevel === 'low' ? '낮음' : '보통')),
                'activity_count' => $recentActivity['recent_activities'],
                'motivation_level' => $motivationLevel
            ]),
            'confidence_score' => 75.0
        ];

        // 6. 상호작용 타게팅
        $targetStrategy = 'standard';
        if ($daysUntilExam !== null && $daysUntilExam < 7) {
            $targetStrategy = 'intensive_exam_prep';
        } elseif ($motivationLevel === 'low') {
            $targetStrategy = 'motivation_boost';
        }

        $analysis[] = [
            'agent_type' => 'interaction_targeting',
            'agent_level' => 6,
            'analysis_data' => json_encode([
                'summary' => match($targetStrategy) {
                    'intensive_exam_prep' => '시험 집중 대비 모드',
                    'motivation_boost' => '동기 부여 강화 모드',
                    default => '표준 학습 지원 모드'
                },
                'strategy' => $targetStrategy
            ]),
            'confidence_score' => 85.0
        ];

        // 7-14단계: 심층 분석 (간략화)
        for ($i = 7; $i <= 14; $i++) {
            $analysis[] = [
                'agent_type' => 'deep_analysis_' . $i,
                'agent_level' => $i,
                'analysis_data' => json_encode([
                    'summary' => "심층 분석 단계 $i 진행 중",
                    'status' => 'processing'
                ]),
                'confidence_score' => 70.0
            ];
        }

        // 15-21단계: 개입 실행 (간략화)
        for ($i = 15; $i <= 21; $i++) {
            $analysis[] = [
                'agent_type' => 'intervention_' . $i,
                'agent_level' => $i,
                'analysis_data' => json_encode([
                    'summary' => "개입 전략 $i 준비",
                    'status' => 'ready'
                ]),
                'confidence_score' => 75.0
            ];
        }

        return $analysis;

    } catch (Exception $e) {
        error_log("AI Analysis error: " . $e->getMessage());
        return [];
    }
}

// 기존 분석 결과 확인 또는 새로 생성
try {
    $today = date('Y-m-d');

    // 오늘의 기존 분석 결과 확인
    if (!$force_refresh) {
        $stmt = $pdo->prepare("
            SELECT * FROM mdl_abessi_ai_analysis
            WHERE userid = ? AND created_date = ?
            ORDER BY agent_level
        ");
        $stmt->execute([$user_id, $today]);
        $existingAnalysis = $stmt->fetchAll();

        if (count($existingAnalysis) >= 21) {
            // 이미 완전한 분석이 있으면 반환
            echo json_encode([
                'success' => true,
                'analysis' => $existingAnalysis,
                'source' => 'cached'
            ]);
            exit;
        }
    }

    // 새로운 분석 실행
    $newAnalysis = performAIAnalysis($pdo, $pdo_alt, $user_id);

    // 분석 결과 저장
    if (!empty($newAnalysis)) {
        $stmt = $pdo->prepare("
            INSERT INTO mdl_abessi_ai_analysis
            (userid, agent_type, agent_level, analysis_data, confidence_score, created_date, timecreated, timemodified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            analysis_data = VALUES(analysis_data),
            confidence_score = VALUES(confidence_score),
            timemodified = VALUES(timemodified)
        ");

        foreach ($newAnalysis as $analysis) {
            $stmt->execute([
                $user_id,
                $analysis['agent_type'],
                $analysis['agent_level'],
                $analysis['analysis_data'],
                $analysis['confidence_score'],
                $today,
                time(),
                time()
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'analysis' => $newAnalysis,
        'source' => 'fresh',
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    error_log("AI Analysis handler error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Analysis error',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ]);
}
?>