<?php declare(strict_types=1);
/**
 * Notification Service for Spiral Scheduler
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_spiral\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Service for sending notifications and alerts
 */
final class notify {
    
    /**
     * Send daily KPI summary to administrators
     * 
     * @param \stdClass $recipient User to send notification to
     * @param array $kpiData KPI data array
     */
    public static function daily_summary(\stdClass $recipient, array $kpiData): void {
        $subject = get_string('daily_summary_subject', 'local_spiral');
        
        $message = self::format_daily_summary($kpiData);
        
        // Send email
        email_to_user($recipient, get_admin(), $subject, $message, $message);
        
        // Also send as Moodle message
        self::send_moodle_message($recipient, $subject, $message, 'daily_summary');
    }
    
    /**
     * Send threshold alert notifications
     * 
     * @param \stdClass $recipient User to send alert to
     * @param array $alerts Array of alert messages
     * @param array $kpiData Current KPI data
     */
    public static function threshold_alert(\stdClass $recipient, array $alerts, array $kpiData): void {
        $subject = get_string('threshold_alert_subject', 'local_spiral');
        
        $message = self::format_threshold_alert($alerts, $kpiData);
        
        // Send as urgent notification
        email_to_user($recipient, get_admin(), $subject, $message, $message);
        self::send_moodle_message($recipient, $subject, $message, 'threshold_alert');
    }
    
    /**
     * Send schedule published notification to student
     * 
     * @param \stdClass $student Student user object
     * @param \stdClass $teacher Teacher user object
     * @param \stdClass $schedule Schedule object
     */
    public static function schedule_published(\stdClass $student, \stdClass $teacher, \stdClass $schedule): void {
        $subject = get_string('schedule_published_subject', 'local_spiral');
        
        $message = get_string('schedule_published_message', 'local_spiral', [
            'student_name' => fullname($student),
            'teacher_name' => fullname($teacher),
            'start_date' => userdate($schedule->start_date, '%Y-%m-%d'),
            'end_date' => userdate($schedule->end_date, '%Y-%m-%d')
        ]);
        
        // Send to student
        email_to_user($student, $teacher, $subject, $message, $message);
        self::send_moodle_message($student, $subject, $message, 'schedule_published');
        
        // Send copy to parent if phone2 (parent contact) exists
        if (!empty($student->phone2)) {
            self::send_sms_notification($student->phone2, $subject);
        }
    }
    
    /**
     * Send conflict resolution notification
     * 
     * @param \stdClass $teacher Teacher user object
     * @param int $scheduleId Schedule ID
     * @param array $conflicts Array of resolved conflicts
     */
    public static function conflicts_resolved(\stdClass $teacher, int $scheduleId, array $conflicts): void {
        $subject = get_string('conflicts_resolved_subject', 'local_spiral');
        
        $message = self::format_conflict_resolution($scheduleId, $conflicts);
        
        self::send_moodle_message($teacher, $subject, $message, 'conflicts_resolved');
    }
    
    /**
     * Send system maintenance notification
     * 
     * @param array $recipients Array of user objects
     * @param string $maintenanceMessage Maintenance details
     */
    public static function system_maintenance(array $recipients, string $maintenanceMessage): void {
        $subject = get_string('maintenance_notification_subject', 'local_spiral');
        
        foreach ($recipients as $user) {
            email_to_user($user, get_admin(), $subject, $maintenanceMessage, $maintenanceMessage);
            self::send_moodle_message($user, $subject, $maintenanceMessage, 'system_maintenance');
        }
    }
    
    /**
     * Format daily summary message
     * 
     * @param array $kpiData KPI data
     * @return string Formatted message
     */
    private static function format_daily_summary(array $kpiData): string {
        $timestamp = userdate($kpiData['timestamp'] ?? time());
        
        return "=== 스파이럴 스케줄러 일일 요약 ===\n" .
               "생성 시간: {$timestamp}\n\n" .
               "📊 주요 지표:\n" .
               "• 7:3 준수율: {$kpiData['ratio']}%\n" .
               "• 충돌 발생률: {$kpiData['conflict']}%\n" .
               "• 세션 완료율: {$kpiData['completion']}%\n" .
               "• 교사 수정횟수: {$kpiData['modcnt']}건\n" .
               "• 사용자 만족도: {$kpiData['satisfaction']}점\n" .
               "• 시스템 활용률: {$kpiData['utilization']}%\n\n" .
               "상세 내용은 대시보드에서 확인하세요:\n" .
               "https://mathking.kr/moodle/local/spiral/index.php";
    }
    
    /**
     * Format threshold alert message
     * 
     * @param array $alerts Array of alert messages
     * @param array $kpiData Current KPI data
     * @return string Formatted message
     */
    private static function format_threshold_alert(array $alerts, array $kpiData): string {
        $timestamp = userdate(time());
        
        $message = "🚨 스파이럴 스케줄러 임계치 경고\n" .
                   "발생 시간: {$timestamp}\n\n" .
                   "⚠️ 감지된 문제:\n";
        
        foreach ($alerts as $alert) {
            $message .= "• {$alert}\n";
        }
        
        $message .= "\n📈 현재 지표:\n" .
                    "• 7:3 준수율: {$kpiData['ratio']}%\n" .
                    "• 충돌 발생률: {$kpiData['conflict']}%\n" .
                    "• 세션 완료율: {$kpiData['completion']}%\n\n" .
                    "즉시 확인 및 조치가 필요합니다.\n" .
                    "대시보드: https://mathking.kr/moodle/local/spiral/index.php";
        
        return $message;
    }
    
    /**
     * Format conflict resolution message
     * 
     * @param int $scheduleId Schedule ID
     * @param array $conflicts Resolved conflicts
     * @return string Formatted message
     */
    private static function format_conflict_resolution(int $scheduleId, array $conflicts): string {
        $conflictCount = count($conflicts);
        
        $message = "✅ 스케줄 충돌이 해결되었습니다\n" .
                   "스케줄 ID: {$scheduleId}\n" .
                   "해결된 충돌: {$conflictCount}건\n\n";
        
        if ($conflictCount > 0) {
            $message .= "해결된 충돌 유형:\n";
            $conflictTypes = array_count_values(array_column($conflicts, 'type'));
            
            foreach ($conflictTypes as $type => $count) {
                $typeName = get_string(strtolower($type), 'local_spiral');
                $message .= "• {$typeName}: {$count}건\n";
            }
        }
        
        return $message;
    }
    
    /**
     * Send Moodle internal message
     * 
     * @param \stdClass $recipient User to send to
     * @param string $subject Message subject
     * @param string $message Message content
     * @param string $eventName Event name for message type
     */
    private static function send_moodle_message(\stdClass $recipient, string $subject, string $message, string $eventName): void {
        $messageObject = new \core\message\message();
        $messageObject->component = 'local_spiral';
        $messageObject->name = $eventName;
        $messageObject->userfrom = get_admin();
        $messageObject->userto = $recipient;
        $messageObject->subject = $subject;
        $messageObject->fullmessage = $message;
        $messageObject->fullmessageformat = FORMAT_PLAIN;
        $messageObject->fullmessagehtml = nl2br(s($message));
        $messageObject->smallmessage = $subject;
        $messageObject->notification = 1;
        
        try {
            message_send($messageObject);
        } catch (\Exception $e) {
            // Log error but don't fail
            error_log("Failed to send Moodle message: " . $e->getMessage());
        }
    }
    
    /**
     * Send SMS notification (placeholder for future implementation)
     * 
     * @param string $phoneNumber Phone number
     * @param string $message SMS message
     */
    private static function send_sms_notification(string $phoneNumber, string $message): void {
        // TODO: Implement SMS integration with external service
        // For now, just log the attempt
        error_log("SMS notification attempted to {$phoneNumber}: {$message}");
        
        /*
         * Example SMS integration:
         * 
         * $smsService = new ExternalSMSService();
         * $smsService->send($phoneNumber, $message);
         */
    }
    
    /**
     * Send batch notifications to multiple users
     * 
     * @param array $recipients Array of user objects
     * @param string $subject Message subject
     * @param string $message Message content
     * @param string $eventName Event name
     */
    public static function send_batch_notifications(array $recipients, string $subject, string $message, string $eventName): void {
        foreach ($recipients as $recipient) {
            self::send_moodle_message($recipient, $subject, $message, $eventName);
        }
    }
    
    /**
     * Schedule future notification
     * 
     * @param \stdClass $recipient User to notify
     * @param string $subject Message subject
     * @param string $message Message content
     * @param int $sendTime Unix timestamp when to send
     */
    public static function schedule_notification(\stdClass $recipient, string $subject, string $message, int $sendTime): void {
        global $DB;
        
        // Store scheduled notification in database
        $record = (object)[
            'recipient_id' => $recipient->id,
            'subject' => $subject,
            'message' => $message,
            'send_time' => $sendTime,
            'status' => 'scheduled',
            'timecreated' => time()
        ];
        
        $DB->insert_record('spiral_scheduled_notifications', $record);
    }
}