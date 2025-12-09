<?php
/**
 * 커리큘럼 플래너 서비스
 * 핵심 비즈니스 로직 처리
 */

require_once __DIR__ . '/curriculum_orchestrator_config.php';

class CurriculumPlannerService {
    
    private $pdo;
    private $userId;
    
    public function __construct($userId = null) {
        $this->pdo = getCurriculumDB();
        $this->userId = $userId;
    }
    
    /**
     * 커리큘럼 계획 생성
     */
    public function createPlan($params) {
        try {
            $this->pdo->beginTransaction();
            
            // 파라미터 검증
            $courseid = $params['courseid'] ?? 0;
            $exam_date = $params['exam_date'] ?? '';
            $exam_type = $params['exam_type'] ?? '중간고사';
            $exam_name = $params['exam_name'] ?? '';
            $ratio_lead = $params['ratio_lead'] ?? 70;
            $ratio_review = $params['ratio_review'] ?? 30;
            $daily_minutes = $params['daily_minutes'] ?? 120;
            $target_chapters = $params['target_chapters'] ?? [];
            
            if (empty($exam_date) || empty($courseid)) {
                throw new Exception('필수 정보가 누락되었습니다.');
            }
            
            // D-day 계산
            $today = new DateTime();
            $examDate = new DateTime($exam_date);
            $interval = $today->diff($examDate);
            $total_days = $interval->days;
            
            if ($total_days < 1) {
                throw new Exception('시험일이 이미 지났거나 너무 가깝습니다.');
            }
            
            // 계획 생성
            $stmt = $this->pdo->prepare("
                INSERT INTO mdl_curriculum_plan 
                (userid, courseid, exam_date, exam_type, exam_name, created_by, 
                 ratio_lead, ratio_review, daily_minutes, status, 
                 start_date, end_date, total_days, metadata, timecreated, timemodified)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $metadata = json_encode([
                'target_chapters' => $target_chapters,
                'original_params' => $params
            ], JSON_UNESCAPED_UNICODE);
            
            $now = time();
            $stmt->execute([
                $this->userId,
                $courseid,
                $exam_date,
                $exam_type,
                $exam_name,
                $this->userId,
                $ratio_lead,
                $ratio_review,
                $daily_minutes,
                PLAN_STATUS_DRAFT,
                $today->format('Y-m-d'),
                $examDate->format('Y-m-d'),
                $total_days,
                $metadata,
                $now,
                $now
            ]);
            
            $planId = $this->pdo->lastInsertId();
            
            // 일별 항목 생성
            $this->generateDailyItems($planId, $total_days, $params);
            
            // KPI 초기화
            $this->initializeKPI($planId);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'plan_id' => $planId,
                'total_days' => $total_days,
                'message' => SUCCESS_PLAN_CREATED
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            logCurriculumAction('create_plan_error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 일별 학습 항목 생성
     */
    private function generateDailyItems($planId, $totalDays, $params) {
        $ratio_lead = $params['ratio_lead'] ?? 70;
        $ratio_review = $params['ratio_review'] ?? 30;
        $daily_minutes = $params['daily_minutes'] ?? 120;
        
        // 선행 개념 조회
        $leadConcepts = $this->getLeadConcepts($params['courseid'], $params['target_chapters'] ?? []);
        
        // 오답노트 항목 조회
        $reviewItems = $this->getReviewItems($this->userId, $params['courseid']);
        
        // 일별 시간 분배
        $lead_minutes = round($daily_minutes * $ratio_lead / 100);
        $review_minutes = round($daily_minutes * $ratio_review / 100);
        
        $stmt = $this->pdo->prepare("
            INSERT INTO mdl_curriculum_items
            (planid, day_index, sequence, item_type, ref_type, ref_id, 
             title, description, est_minutes, status, due_date, 
             difficulty, priority, tags, metadata, timecreated, timemodified)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $today = new DateTime();
        $now = time();
        
        for ($day = 1; $day <= $totalDays; $day++) {
            $due_date = clone $today;
            $due_date->add(new DateInterval("P{$day}D"));
            $sequence = 1;
            
            // 선행 개념 항목 추가
            $lead_items_today = $this->selectDailyItems($leadConcepts, $lead_minutes, MIN_ITEM_MINUTES, MAX_ITEM_MINUTES);
            foreach ($lead_items_today as $item) {
                $stmt->execute([
                    $planId,
                    $day,
                    $sequence++,
                    ITEM_TYPE_CONCEPT,
                    REF_TYPE_CONCEPT,
                    $item['id'],
                    $item['title'],
                    $item['description'] ?? '',
                    $item['minutes'],
                    'pending',
                    $due_date->format('Y-m-d'),
                    $item['difficulty'] ?? 2,
                    $item['priority'] ?? 5,
                    json_encode($item['tags'] ?? []),
                    json_encode(['source' => 'lead']),
                    $now,
                    $now
                ]);
            }
            
            // 복습 항목 추가
            $review_items_today = $this->selectDailyItems($reviewItems, $review_minutes, MIN_ITEM_MINUTES, MAX_ITEM_MINUTES);
            foreach ($review_items_today as $item) {
                $stmt->execute([
                    $planId,
                    $day,
                    $sequence++,
                    ITEM_TYPE_REVIEW,
                    REF_TYPE_QUESTION,
                    $item['id'],
                    $item['title'],
                    $item['description'] ?? '',
                    $item['minutes'],
                    'pending',
                    $due_date->format('Y-m-d'),
                    $item['difficulty'] ?? 2,
                    $item['priority'] ?? 8,
                    json_encode($item['tags'] ?? []),
                    json_encode(['source' => 'review']),
                    $now,
                    $now
                ]);
            }
            
            // 진행 상황 레코드 초기화
            $this->initializeDailyProgress($planId, $day, $due_date->format('Y-m-d'), 
                                          count($lead_items_today) + count($review_items_today),
                                          $daily_minutes);
        }
    }
    
    /**
     * 선행 개념 조회 (태그 기반)
     */
    private function getLeadConcepts($courseid, $chapters = []) {
        global $CURRICULUM_TAGS;
        
        // 실제 구현에서는 Moodle 코스/챕터 구조와 연동
        // 여기서는 더미 데이터로 시뮬레이션
        $concepts = [];
        
        // 태그 기반 조회 시뮬레이션
        $tags = $CURRICULUM_TAGS['lead'];
        
        // 개념 맵에서 조회
        $sql = "SELECT * FROM mdl_curriculum_concept_map 
                WHERE courseid = ? 
                ORDER BY importance DESC, difficulty ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$courseid]);
        
        while ($row = $stmt->fetch()) {
            $concepts[] = [
                'id' => $row['concept_id'],
                'title' => $row['concept_name'],
                'description' => '개념 학습',
                'minutes' => $row['est_minutes'] ?? 20,
                'difficulty' => $row['difficulty'],
                'priority' => $row['importance'],
                'tags' => json_decode($row['tags'] ?? '[]', true)
            ];
        }
        
        // 개념이 없으면 더미 데이터 생성
        if (empty($concepts)) {
            for ($i = 1; $i <= 30; $i++) {
                $concepts[] = [
                    'id' => $i,
                    'title' => "개념 $i - " . $tags[array_rand($tags)],
                    'description' => "선행 개념 학습 항목",
                    'minutes' => rand(15, 30),
                    'difficulty' => rand(1, 3),
                    'priority' => rand(3, 7),
                    'tags' => [$tags[array_rand($tags)]]
                ];
            }
        }
        
        return $concepts;
    }
    
    /**
     * 오답노트 항목 조회
     */
    private function getReviewItems($userid, $courseid) {
        $items = [];
        
        // 오답노트 풀에서 조회
        $sql = "SELECT * FROM mdl_curriculum_review_pool 
                WHERE userid = ? AND courseid = ? AND resolution_status = 'unresolved'
                ORDER BY priority_score DESC, last_error_date DESC
                LIMIT 100";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userid, $courseid]);
        
        while ($row = $stmt->fetch()) {
            $items[] = [
                'id' => $row['question_id'],
                'title' => "오답 문제 #" . $row['question_id'],
                'description' => $row['notes'] ?? '오답 복습',
                'minutes' => rand(10, 20),
                'difficulty' => $row['difficulty'],
                'priority' => round($row['priority_score'] / 10),
                'tags' => json_decode($row['tags'] ?? '[]', true)
            ];
        }
        
        // 오답이 없으면 더미 데이터 생성
        if (empty($items)) {
            global $CURRICULUM_TAGS;
            $tags = $CURRICULUM_TAGS['review'];
            
            for ($i = 1; $i <= 20; $i++) {
                $items[] = [
                    'id' => 1000 + $i,
                    'title' => "오답노트 #$i",
                    'description' => "최근 틀린 문제 복습",
                    'minutes' => rand(10, 20),
                    'difficulty' => rand(2, 4),
                    'priority' => rand(6, 9),
                    'tags' => [$tags[array_rand($tags)]]
                ];
            }
        }
        
        return $items;
    }
    
    /**
     * 일별 항목 선택 (시간 제약 고려)
     */
    private function selectDailyItems($pool, $targetMinutes, $minMinutes, $maxMinutes) {
        if (empty($pool)) return [];
        
        $selected = [];
        $totalMinutes = 0;
        $poolCopy = $pool;
        
        while ($totalMinutes < $targetMinutes && !empty($poolCopy)) {
            $index = array_rand($poolCopy);
            $item = $poolCopy[$index];
            
            // 시간 조정
            $item['minutes'] = min($item['minutes'], $maxMinutes);
            $item['minutes'] = max($item['minutes'], $minMinutes);
            
            if ($totalMinutes + $item['minutes'] <= $targetMinutes + 5) {
                $selected[] = $item;
                $totalMinutes += $item['minutes'];
            }
            
            unset($poolCopy[$index]);
            $poolCopy = array_values($poolCopy);
            
            // 최대 항목 수 제한
            if (count($selected) >= 10) break;
        }
        
        return $selected;
    }
    
    /**
     * 일별 진행 상황 초기화
     */
    private function initializeDailyProgress($planId, $dayIndex, $date, $itemCount, $minutes) {
        $stmt = $this->pdo->prepare("
            INSERT INTO mdl_curriculum_progress
            (planid, userid, day_index, date, planned_items, completed_items,
             planned_minutes, actual_minutes, completion_rate, 
             lead_completed, review_completed, timecreated, timemodified)
            VALUES (?, ?, ?, ?, ?, 0, ?, 0, 0, 0, 0, ?, ?)
        ");
        
        $now = time();
        $stmt->execute([
            $planId,
            $this->userId,
            $dayIndex,
            $date,
            $itemCount,
            $minutes,
            $now,
            $now
        ]);
    }
    
    /**
     * KPI 초기화
     */
    private function initializeKPI($planId) {
        $stmt = $this->pdo->prepare("
            INSERT INTO mdl_curriculum_kpi
            (planid, metric_date, completion_rate, review_resolution_rate,
             daily_achievement_rate, estimated_completion_date, days_ahead_behind,
             total_study_minutes, average_daily_minutes, streak_days,
             metadata, timecreated)
            VALUES (?, ?, 0, 0, 0, NULL, 0, 0, 0, 0, '{}', ?)
        ");
        
        $now = time();
        $stmt->execute([
            $planId,
            date('Y-m-d'),
            $now
        ]);
    }
    
    /**
     * 커리큘럼 미리보기
     */
    public function previewPlan($planId) {
        try {
            // 계획 정보 조회
            $stmt = $this->pdo->prepare("
                SELECT p.*, u.firstname, u.lastname 
                FROM mdl_curriculum_plan p
                LEFT JOIN mdl_user u ON p.userid = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$planId]);
            $plan = $stmt->fetch();
            
            if (!$plan) {
                throw new Exception('계획을 찾을 수 없습니다.');
            }
            
            // 일별 항목 조회
            $stmt = $this->pdo->prepare("
                SELECT day_index, item_type, COUNT(*) as count, SUM(est_minutes) as total_minutes
                FROM mdl_curriculum_items
                WHERE planid = ?
                GROUP BY day_index, item_type
                ORDER BY day_index, item_type
            ");
            $stmt->execute([$planId]);
            
            $dailyItems = [];
            while ($row = $stmt->fetch()) {
                if (!isset($dailyItems[$row['day_index']])) {
                    $dailyItems[$row['day_index']] = [
                        'day' => $row['day_index'],
                        'concept_count' => 0,
                        'concept_minutes' => 0,
                        'review_count' => 0,
                        'review_minutes' => 0
                    ];
                }
                
                if ($row['item_type'] == ITEM_TYPE_CONCEPT) {
                    $dailyItems[$row['day_index']]['concept_count'] = $row['count'];
                    $dailyItems[$row['day_index']]['concept_minutes'] = $row['total_minutes'];
                } else if ($row['item_type'] == ITEM_TYPE_REVIEW) {
                    $dailyItems[$row['day_index']]['review_count'] = $row['count'];
                    $dailyItems[$row['day_index']]['review_minutes'] = $row['total_minutes'];
                }
            }
            
            // 통계 계산
            $stats = $this->calculatePlanStats($planId);
            
            return [
                'success' => true,
                'plan' => $plan,
                'daily_items' => array_values($dailyItems),
                'stats' => $stats
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 계획 통계 계산
     */
    private function calculatePlanStats($planId) {
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_items,
                SUM(CASE WHEN item_type = 'concept' THEN 1 ELSE 0 END) as concept_items,
                SUM(CASE WHEN item_type = 'review' THEN 1 ELSE 0 END) as review_items,
                SUM(est_minutes) as total_minutes,
                AVG(difficulty) as avg_difficulty
            FROM mdl_curriculum_items
            WHERE planid = ?
        ");
        $stmt->execute([$planId]);
        
        return $stmt->fetch();
    }
    
    /**
     * 커리큘럼 배포
     */
    public function publishPlan($planId) {
        try {
            $this->pdo->beginTransaction();
            
            // 상태 업데이트
            $stmt = $this->pdo->prepare("
                UPDATE mdl_curriculum_plan 
                SET status = ?, timemodified = ?
                WHERE id = ? AND status = ?
            ");
            
            $stmt->execute([PLAN_STATUS_PUBLISHED, time(), $planId, PLAN_STATUS_DRAFT]);
            
            if ($stmt->rowCount() == 0) {
                throw new Exception('계획을 배포할 수 없습니다.');
            }
            
            // 로그 기록
            $this->logAction('publish_plan', 'plan', $planId, PLAN_STATUS_DRAFT, PLAN_STATUS_PUBLISHED);
            
            $this->pdo->commit();
            
            return [
                'success' => true,
                'message' => SUCCESS_PLAN_PUBLISHED
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * 활동 로그 기록
     */
    private function logAction($action, $targetType, $targetId, $oldValue = null, $newValue = null) {
        $stmt = $this->pdo->prepare("
            INSERT INTO mdl_curriculum_log
            (planid, userid, action, target_type, target_id, 
             old_value, new_value, ip_address, user_agent, timecreated)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            null,
            $this->userId,
            $action,
            $targetType,
            $targetId,
            $oldValue,
            $newValue,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            time()
        ]);
    }
}
?>