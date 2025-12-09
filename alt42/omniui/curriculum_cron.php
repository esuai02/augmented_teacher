<?php
/**
 * 커리큘럼 오케스트레이터 - 크론 작업
 * 매일 실행되는 자동화 작업
 * 
 * 설정: crontab -e
 * 0 6 * * * /usr/bin/php /path/to/curriculum_cron.php
 */

require_once __DIR__ . '/curriculum_orchestrator_config.php';

// CLI 실행 확인
if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

echo "=================================\n";
echo "커리큘럼 크론 작업 시작\n";
echo "실행 시간: " . date('Y-m-d H:i:s') . "\n";
echo "=================================\n\n";

try {
    $pdo = getCurriculumDB();
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    
    // 1. 미이행 항목 재분배
    echo "1. 미이행 항목 재분배...\n";
    
    $stmt = $pdo->prepare("
        UPDATE mdl_curriculum_items i
        JOIN mdl_curriculum_plan p ON i.planid = p.id
        SET i.due_date = ?,
            i.day_index = i.day_index + 1,
            i.priority = i.priority + 1,
            i.timemodified = ?
        WHERE i.status = 'pending' 
        AND i.due_date < ?
        AND p.status IN ('published', 'active')
        AND p.exam_date > ?
    ");
    
    $stmt->execute([$today, time(), $today, $today]);
    $redistributed = $stmt->rowCount();
    echo "   - {$redistributed}개 항목 재분배 완료\n";
    
    // 2. KPI 업데이트
    echo "\n2. KPI 메트릭 업데이트...\n";
    
    // 각 활성 계획별 KPI 계산
    $stmt = $pdo->prepare("
        SELECT DISTINCT p.id, p.userid, p.exam_date
        FROM mdl_curriculum_plan p
        WHERE p.status IN ('published', 'active')
        AND p.exam_date >= ?
    ");
    $stmt->execute([$today]);
    $plans = $stmt->fetchAll();
    
    foreach ($plans as $plan) {
        // 완료율 계산
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_items,
                SUM(CASE WHEN item_type = 'review' AND status = 'completed' THEN 1 ELSE 0 END) as review_completed,
                SUM(CASE WHEN item_type = 'review' THEN 1 ELSE 0 END) as review_total,
                SUM(CASE WHEN status = 'completed' THEN actual_minutes ELSE 0 END) as total_minutes
            FROM mdl_curriculum_items
            WHERE planid = ?
        ");
        $stmt->execute([$plan['id']]);
        $stats = $stmt->fetch();
        
        $completionRate = $stats['total_items'] > 0 ? 
            ($stats['completed_items'] / $stats['total_items']) * 100 : 0;
        
        $reviewResolutionRate = $stats['review_total'] > 0 ? 
            ($stats['review_completed'] / $stats['review_total']) * 100 : 0;
        
        // 일일 달성률 계산
        $stmt = $pdo->prepare("
            SELECT completion_rate 
            FROM mdl_curriculum_progress
            WHERE planid = ? AND date = ?
        ");
        $stmt->execute([$plan['id'], $yesterday]);
        $dailyAchievement = $stmt->fetchColumn() ?: 0;
        
        // 연속 학습일 계산
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM mdl_curriculum_progress
            WHERE planid = ? 
            AND completed_items > 0
            AND date >= DATE_SUB(?, INTERVAL 30 DAY)
        ");
        $stmt->execute([$plan['id'], $today]);
        $streakDays = $stmt->fetchColumn();
        
        // 예상 완료일 계산
        $daysLeft = (strtotime($plan['exam_date']) - strtotime($today)) / 86400;
        $itemsPerDay = $stats['completed_items'] > 0 ? 
            $stats['completed_items'] / max(1, 30 - $daysLeft) : 0;
        $estimatedDaysNeeded = $itemsPerDay > 0 ? 
            ($stats['total_items'] - $stats['completed_items']) / $itemsPerDay : 999;
        
        $estimatedCompletionDate = date('Y-m-d', strtotime("+{$estimatedDaysNeeded} days"));
        $daysAheadBehind = $daysLeft - $estimatedDaysNeeded;
        
        // KPI 저장
        $stmt = $pdo->prepare("
            INSERT INTO mdl_curriculum_kpi
            (planid, metric_date, completion_rate, review_resolution_rate,
             daily_achievement_rate, estimated_completion_date, days_ahead_behind,
             total_study_minutes, average_daily_minutes, streak_days,
             metadata, timecreated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                completion_rate = VALUES(completion_rate),
                review_resolution_rate = VALUES(review_resolution_rate),
                daily_achievement_rate = VALUES(daily_achievement_rate),
                estimated_completion_date = VALUES(estimated_completion_date),
                days_ahead_behind = VALUES(days_ahead_behind),
                total_study_minutes = VALUES(total_study_minutes),
                streak_days = VALUES(streak_days)
        ");
        
        $avgDailyMinutes = $streakDays > 0 ? $stats['total_minutes'] / $streakDays : 0;
        
        $stmt->execute([
            $plan['id'],
            $today,
            $completionRate,
            $reviewResolutionRate,
            $dailyAchievement,
            $estimatedCompletionDate,
            $daysAheadBehind,
            $stats['total_minutes'],
            $avgDailyMinutes,
            $streakDays,
            json_encode(['auto_generated' => true]),
            time()
        ]);
    }
    
    echo "   - " . count($plans) . "개 계획 KPI 업데이트 완료\n";
    
    // 3. 알림 발송 (D-day 체크)
    echo "\n3. 알림 확인...\n";
    
    $alertDays = ALERT_BEFORE_EXAM_DAYS;
    foreach ($alertDays as $days) {
        $targetDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $stmt = $pdo->prepare("
            SELECT p.*, u.email, u.firstname, u.lastname
            FROM mdl_curriculum_plan p
            JOIN mdl_user u ON p.userid = u.id
            WHERE p.exam_date = ?
            AND p.status IN ('published', 'active')
        ");
        $stmt->execute([$targetDate]);
        
        while ($plan = $stmt->fetch()) {
            echo "   - D-{$days} 알림: {$plan['firstname']} {$plan['lastname']} ({$plan['exam_type']})\n";
            
            // 여기에 실제 알림 발송 로직 추가
            // 예: 이메일, 푸시 알림, SMS 등
        }
    }
    
    // 4. 오래된 로그 정리
    echo "\n4. 오래된 로그 정리...\n";
    
    $oldDate = date('Y-m-d', strtotime('-90 days'));
    $stmt = $pdo->prepare("
        DELETE FROM mdl_curriculum_log 
        WHERE timecreated < UNIX_TIMESTAMP(?)
    ");
    $stmt->execute([$oldDate]);
    $deletedLogs = $stmt->rowCount();
    echo "   - {$deletedLogs}개 로그 삭제\n";
    
    // 5. 완료된 계획 상태 업데이트
    echo "\n5. 완료된 계획 상태 업데이트...\n";
    
    $stmt = $pdo->prepare("
        UPDATE mdl_curriculum_plan 
        SET status = 'completed', timemodified = ?
        WHERE exam_date < ?
        AND status IN ('published', 'active')
    ");
    $stmt->execute([time(), $today]);
    $completedPlans = $stmt->rowCount();
    echo "   - {$completedPlans}개 계획 완료 처리\n";
    
    // 6. 통계 리포트 생성
    echo "\n6. 일일 통계 리포트...\n";
    
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as active_plans,
            COUNT(DISTINCT p.userid) as active_students,
            AVG(k.completion_rate) as avg_completion,
            AVG(k.daily_achievement_rate) as avg_daily_achievement,
            SUM(pr.completed_items) as total_completed_today,
            SUM(pr.actual_minutes) as total_minutes_today
        FROM mdl_curriculum_plan p
        LEFT JOIN mdl_curriculum_kpi k ON p.id = k.planid AND k.metric_date = ?
        LEFT JOIN mdl_curriculum_progress pr ON p.id = pr.planid AND pr.date = ?
        WHERE p.status IN ('published', 'active')
    ");
    $stmt->execute([$yesterday, $yesterday]);
    $report = $stmt->fetch();
    
    echo "   ===== 일일 리포트 =====\n";
    echo "   활성 계획: {$report['active_plans']}개\n";
    echo "   활성 학생: {$report['active_students']}명\n";
    echo "   평균 완료율: " . number_format($report['avg_completion'], 1) . "%\n";
    echo "   일일 달성률: " . number_format($report['avg_daily_achievement'], 1) . "%\n";
    echo "   오늘 완료 항목: {$report['total_completed_today']}개\n";
    echo "   오늘 학습 시간: " . round($report['total_minutes_today'] / 60, 1) . "시간\n";
    
    // 로그 기록
    $logData = [
        'date' => $today,
        'redistributed_items' => $redistributed,
        'updated_kpis' => count($plans),
        'deleted_logs' => $deletedLogs,
        'completed_plans' => $completedPlans,
        'daily_report' => $report
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO mdl_curriculum_log
        (userid, action, metadata, timecreated)
        VALUES (0, 'cron_daily', ?, ?)
    ");
    $stmt->execute([json_encode($logData, JSON_UNESCAPED_UNICODE), time()]);
    
    echo "\n=================================\n";
    echo "크론 작업 완료!\n";
    echo "종료 시간: " . date('Y-m-d H:i:s') . "\n";
    echo "=================================\n";
    
} catch (Exception $e) {
    echo "\n❌ 오류 발생: " . $e->getMessage() . "\n";
    error_log("Curriculum cron error: " . $e->getMessage());
    exit(1);
}

exit(0);
?>