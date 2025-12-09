<?php
/**
 * 커리큘럼 오케스트레이터 API 엔드포인트
 * AJAX 요청 처리
 */

session_start();
require_once __DIR__ . '/curriculum_orchestrator_config.php';
require_once __DIR__ . '/curriculum_planner_service.php';

// CORS 헤더 (필요시)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 로그인 체크
$userId = checkCurriculumSession();

// 액션 파라미터
$action = $_REQUEST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// 서비스 인스턴스
$planner = new CurriculumPlannerService($userId);

try {
    switch ($action) {
        
        /**
         * 커리큘럼 생성
         * POST /curriculum_api.php?action=create_plan
         */
        case 'create_plan':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            if (!isTeacherRole($userId)) {
                throw new Exception(ERROR_NO_PERMISSION);
            }
            
            $params = [
                'userid' => $_POST['userid'] ?? $userId,
                'courseid' => $_POST['courseid'] ?? 0,
                'exam_date' => $_POST['exam_date'] ?? '',
                'exam_type' => $_POST['exam_type'] ?? '중간고사',
                'exam_name' => $_POST['exam_name'] ?? '',
                'ratio_lead' => intval($_POST['ratio_lead'] ?? 70),
                'ratio_review' => intval($_POST['ratio_review'] ?? 30),
                'daily_minutes' => intval($_POST['daily_minutes'] ?? 120),
                'target_chapters' => $_POST['target_chapters'] ?? []
            ];
            
            $result = $planner->createPlan($params);
            
            if ($result['success']) {
                jsonResponse(true, $result, $result['message']);
            } else {
                jsonResponse(false, null, $result['message']);
            }
            break;
            
        /**
         * 커리큘럼 미리보기
         * GET /curriculum_api.php?action=preview_plan&plan_id=123
         */
        case 'preview_plan':
            $planId = intval($_GET['plan_id'] ?? 0);
            
            if ($planId <= 0) {
                throw new Exception('Invalid plan ID');
            }
            
            $result = $planner->previewPlan($planId);
            
            if ($result['success']) {
                jsonResponse(true, $result, '');
            } else {
                jsonResponse(false, null, $result['message']);
            }
            break;
            
        /**
         * 커리큘럼 배포
         * POST /curriculum_api.php?action=publish_plan
         */
        case 'publish_plan':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            if (!isTeacherRole($userId)) {
                throw new Exception(ERROR_NO_PERMISSION);
            }
            
            $planId = intval($_POST['plan_id'] ?? 0);
            
            if ($planId <= 0) {
                throw new Exception('Invalid plan ID');
            }
            
            $result = $planner->publishPlan($planId);
            
            if ($result['success']) {
                jsonResponse(true, $result, $result['message']);
            } else {
                jsonResponse(false, null, $result['message']);
            }
            break;
            
        /**
         * 오늘 학습 항목 조회 (학생용)
         * GET /curriculum_api.php?action=get_today_items
         */
        case 'get_today_items':
            $pdo = getCurriculumDB();
            $today = date('Y-m-d');
            
            $stmt = $pdo->prepare("
                SELECT i.*, p.exam_type, p.exam_date
                FROM mdl_curriculum_items i
                JOIN mdl_curriculum_plan p ON i.planid = p.id
                WHERE p.userid = ? 
                AND i.due_date = ?
                AND i.status = 'pending'
                AND p.status IN ('published', 'active')
                ORDER BY i.sequence
            ");
            
            $stmt->execute([$userId, $today]);
            $items = $stmt->fetchAll();
            
            jsonResponse(true, ['items' => $items], '');
            break;
            
        /**
         * 항목 완료 처리
         * POST /curriculum_api.php?action=complete_item
         */
        case 'complete_item':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $itemId = intval($_POST['item_id'] ?? 0);
            $actualMinutes = intval($_POST['actual_minutes'] ?? 0);
            
            if ($itemId <= 0) {
                throw new Exception('Invalid item ID');
            }
            
            $pdo = getCurriculumDB();
            $pdo->beginTransaction();
            
            try {
                // 항목 업데이트
                $stmt = $pdo->prepare("
                    UPDATE mdl_curriculum_items 
                    SET status = 'completed',
                        actual_minutes = ?,
                        completed_date = NOW(),
                        timemodified = ?
                    WHERE id = ? AND status = 'pending'
                ");
                
                $stmt->execute([$actualMinutes, time(), $itemId]);
                
                if ($stmt->rowCount() == 0) {
                    throw new Exception('항목을 완료할 수 없습니다.');
                }
                
                // 진행상황 업데이트
                $stmt = $pdo->prepare("
                    UPDATE mdl_curriculum_progress p
                    JOIN mdl_curriculum_items i ON p.planid = i.planid AND p.day_index = i.day_index
                    SET p.completed_items = p.completed_items + 1,
                        p.actual_minutes = p.actual_minutes + ?,
                        p.completion_rate = (p.completed_items / p.planned_items) * 100,
                        p.timemodified = ?
                    WHERE i.id = ?
                ");
                
                $stmt->execute([$actualMinutes, time(), $itemId]);
                
                $pdo->commit();
                jsonResponse(true, null, '항목이 완료되었습니다.');
                
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;
            
        /**
         * KPI 데이터 조회
         * GET /curriculum_api.php?action=get_kpi&plan_id=123
         */
        case 'get_kpi':
            $planId = intval($_GET['plan_id'] ?? 0);
            $pdo = getCurriculumDB();
            
            if ($planId > 0) {
                $stmt = $pdo->prepare("
                    SELECT * FROM mdl_curriculum_kpi 
                    WHERE planid = ? 
                    ORDER BY metric_date DESC 
                    LIMIT 1
                ");
                $stmt->execute([$planId]);
            } else {
                // 전체 KPI
                $stmt = $pdo->prepare("
                    SELECT 
                        AVG(completion_rate) as avg_completion,
                        AVG(review_resolution_rate) as avg_review_resolution,
                        AVG(daily_achievement_rate) as avg_daily_achievement,
                        COUNT(DISTINCT planid) as total_plans
                    FROM mdl_curriculum_kpi
                    WHERE metric_date = CURDATE()
                ");
                $stmt->execute();
            }
            
            $kpi = $stmt->fetch();
            jsonResponse(true, ['kpi' => $kpi], '');
            break;
            
        /**
         * 진행상황 업데이트 (크론용)
         * POST /curriculum_api.php?action=update_progress
         */
        case 'update_progress':
            if (!isTeacherRole($userId)) {
                throw new Exception(ERROR_NO_PERMISSION);
            }
            
            $pdo = getCurriculumDB();
            $today = date('Y-m-d');
            
            // 모든 활성 계획의 KPI 업데이트
            $stmt = $pdo->prepare("
                INSERT INTO mdl_curriculum_kpi 
                (planid, metric_date, completion_rate, review_resolution_rate,
                 daily_achievement_rate, total_study_minutes, average_daily_minutes,
                 streak_days, timecreated)
                SELECT 
                    p.id,
                    ?,
                    COALESCE(AVG(pr.completion_rate), 0),
                    COALESCE(
                        (SELECT COUNT(*) FROM mdl_curriculum_items 
                         WHERE planid = p.id AND item_type = 'review' AND status = 'completed') * 100.0 /
                        NULLIF((SELECT COUNT(*) FROM mdl_curriculum_items 
                         WHERE planid = p.id AND item_type = 'review'), 0), 0),
                    COALESCE(
                        (SELECT completion_rate FROM mdl_curriculum_progress 
                         WHERE planid = p.id AND date = ? LIMIT 1), 0),
                    COALESCE(SUM(pr.actual_minutes), 0),
                    COALESCE(AVG(pr.actual_minutes), 0),
                    0,
                    ?
                FROM mdl_curriculum_plan p
                LEFT JOIN mdl_curriculum_progress pr ON p.id = pr.planid
                WHERE p.status IN ('published', 'active')
                GROUP BY p.id
                ON DUPLICATE KEY UPDATE
                    completion_rate = VALUES(completion_rate),
                    review_resolution_rate = VALUES(review_resolution_rate),
                    daily_achievement_rate = VALUES(daily_achievement_rate),
                    total_study_minutes = VALUES(total_study_minutes),
                    average_daily_minutes = VALUES(average_daily_minutes)
            ");
            
            $stmt->execute([$today, $today, time()]);
            
            jsonResponse(true, null, 'KPI 업데이트 완료');
            break;
            
        /**
         * 미이행 항목 재분배
         * POST /curriculum_api.php?action=redistribute_pending
         */
        case 'redistribute_pending':
            if (!isTeacherRole($userId)) {
                throw new Exception(ERROR_NO_PERMISSION);
            }
            
            $pdo = getCurriculumDB();
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $today = date('Y-m-d');
            $tomorrow = date('Y-m-d', strtotime('+1 day'));
            
            // 어제 미완료 항목을 오늘로 이동
            $stmt = $pdo->prepare("
                UPDATE mdl_curriculum_items 
                SET due_date = ?,
                    day_index = day_index + 1,
                    timemodified = ?
                WHERE status = 'pending' 
                AND due_date = ?
                AND planid IN (
                    SELECT id FROM mdl_curriculum_plan 
                    WHERE status IN ('published', 'active')
                    AND exam_date > ?
                )
            ");
            
            $stmt->execute([$today, time(), $yesterday, $today]);
            $movedItems = $stmt->rowCount();
            
            jsonResponse(true, ['moved_items' => $movedItems], "$movedItems 개 항목이 재분배되었습니다.");
            break;
            
        /**
         * 학습 통계 조회
         * GET /curriculum_api.php?action=get_stats&user_id=123&period=week
         */
        case 'get_stats':
            $targetUserId = intval($_GET['user_id'] ?? $userId);
            $period = $_GET['period'] ?? 'week';
            
            $pdo = getCurriculumDB();
            
            // 기간 설정
            switch ($period) {
                case 'week':
                    $startDate = date('Y-m-d', strtotime('-7 days'));
                    break;
                case 'month':
                    $startDate = date('Y-m-d', strtotime('-30 days'));
                    break;
                default:
                    $startDate = date('Y-m-d', strtotime('-7 days'));
            }
            
            $stmt = $pdo->prepare("
                SELECT 
                    date,
                    SUM(completed_items) as completed,
                    SUM(planned_items) as planned,
                    AVG(completion_rate) as avg_completion,
                    SUM(actual_minutes) as study_minutes
                FROM mdl_curriculum_progress
                WHERE userid = ? AND date >= ?
                GROUP BY date
                ORDER BY date ASC
            ");
            
            $stmt->execute([$targetUserId, $startDate]);
            $stats = $stmt->fetchAll();
            
            jsonResponse(true, ['stats' => $stats, 'period' => $period], '');
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    logCurriculumAction('api_error', [
        'action' => $action,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    jsonResponse(false, null, $e->getMessage());
}
?>