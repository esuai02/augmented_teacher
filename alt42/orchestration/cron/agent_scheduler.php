<?php
/**
 * agent_scheduler.php
 *
 * 시간 기반(배치) 트리거 실행용 스케줄러
 * - Cron에서 주기적으로 호출되는 것을 전제로 한다.
 *
 * 주의: 본 저장소는 라이브 서버 환경이므로, 실제 cron 등록은 운영 정책에 따라 수행해야 한다.
 *
 * @package ALT42\Cron
 * @version 1.0.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// cron은 웹 세션이 없을 수 있어 require_login()을 강제하지 않는다.
// 대신 내부 정책에 따라 CLI cron 전용 계정/토큰 방식이 필요할 수 있다.

require_once(__DIR__ . '/../api/events/trigger_engine.php');

class AgentScheduler {
    /** @var \moodle_database */
    private $db;

    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    public function run(): array {
        $now = time();
        $report = [
            'success' => true,
            'timestamp' => date('c', $now),
            'actions' => []
        ];

        // 예시: 매일 오전 8시 시험일정 확인(Agent 02)
        $hour = (int)date('H', $now);
        $minute = (int)date('i', $now);
        if ($hour === 8 && $minute < 2) {
            $report['actions'][] = $this->runDailyExamCheck();
        }

        // 예시: 매시간 정각 학습관리(Agent 09)
        if ($minute === 0) {
            $report['actions'][] = $this->runHourlyLearningManagement();
        }

        return $report;
    }

    private function runDailyExamCheck(): array {
        $students = $this->db->get_records_sql("
            SELECT DISTINCT user_id AS userid
            FROM {alt42_student_profiles}
            WHERE exam_date IS NOT NULL
        ");

        $results = [];
        foreach ($students as $s) {
            $sid = (int)$s->userid;
            if ($sid <= 0) continue;
            $engine = new TriggerRuleEngine($sid);
            // 여기서는 단순 실행(직접 호출). 실제로는 exam 관련 이벤트를 만들어 publish할 수도 있다.
            $results[] = $engine->execute([
                'rule_id' => 'scheduled_daily_exam_check',
                'agent_id' => 2,
                'priority' => 6,
                'event' => [
                    'event_type' => 'scheduled_daily_exam_check',
                    'student_id' => $sid,
                    'timestamp' => (int)(microtime(true) * 1000),
                    'data' => []
                ]
            ]);
        }

        return [
            'name' => 'daily_exam_check',
            'students' => count($students),
            'executed' => count($results)
        ];
    }

    private function runHourlyLearningManagement(): array {
        $students = $this->db->get_records_sql("
            SELECT DISTINCT userid
            FROM {alt42_student_activity}
            WHERE timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 2 HOUR))
        ");

        $results = [];
        foreach ($students as $s) {
            $sid = (int)$s->userid;
            if ($sid <= 0) continue;
            $engine = new TriggerRuleEngine($sid);
            $results[] = $engine->execute([
                'rule_id' => 'scheduled_hourly_learning_management',
                'agent_id' => 9,
                'priority' => 6,
                'event' => [
                    'event_type' => 'scheduled_hourly_learning_management',
                    'student_id' => $sid,
                    'timestamp' => (int)(microtime(true) * 1000),
                    'data' => []
                ]
            ]);
        }

        return [
            'name' => 'hourly_learning_management',
            'students' => count($students),
            'executed' => count($results)
        ];
    }
}

// CLI 실행용
if (php_sapi_name() === 'cli') {
    $scheduler = new AgentScheduler();
    $result = $scheduler->run();
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
}


