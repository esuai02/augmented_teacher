<?php
/**
 * 학습 대시보드 데이터 조회 API (간소화 버전)
 */

header('Content-Type: application/json; charset=utf-8');

session_start();
require_once 'config.php';

try {
    // 데이터베이스 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    // 파라미터
    $user_id = $_REQUEST['user_id'] ?? $_SESSION['userid'] ?? null;

    if (!$user_id) {
        throw new Exception('사용자 ID가 필요합니다.');
    }

    $response = ['success' => true];

    // 오늘 활동 수
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated >= UNIX_TIMESTAMP(CURDATE())
    ");
    $stmt->execute([$user_id]);
    $response['stats']['today_activities'] = (int)$stmt->fetchColumn();

    // 학습 시간 (최근 30일)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 30 DAY))
    ");
    $stmt->execute([$user_id]);
    $response['stats']['study_time'] = round($stmt->fetchColumn() * 5 / 60, 1);

    // 연속 학습일 (간단 버전)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(FROM_UNIXTIME(timecreated)))
        FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated >= UNIX_TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY))
    ");
    $stmt->execute([$user_id]);
    $response['stats']['study_streak'] = (int)$stmt->fetchColumn();

    // 완료한 과제
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_today
        WHERE userid = ? AND type = '학습과제' AND complete = '1'
    ");
    $stmt->execute([$user_id]);
    $response['stats']['completed_tasks'] = (int)$stmt->fetchColumn();

    // 최근 활동
    $stmt = $pdo->prepare("
        SELECT page, COUNT(*) as count, MAX(timecreated) as last_activity
        FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
        GROUP BY page
        ORDER BY last_activity DESC
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $response['activities'] = $stmt->fetchAll();

    // 알림
    $response['notifications'] = 0;

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    error_log("Dashboard API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'stats' => [
            'today_activities' => 0,
            'study_time' => 0,
            'study_streak' => 0,
            'completed_tasks' => 0
        ],
        'activities' => [],
        'notifications' => 0
    ], JSON_UNESCAPED_UNICODE);
}
?>
