<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Schedule exam detection hook for Routine Coach integration
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');

use local_routinecoach\service\routine_service;

/**
 * Hook class for schedule42.php exam detection
 */
class schedule_exam_hook {
    
    /** @var routine_service Service instance */
    private $service;
    
    /** @var int User ID */
    private $userid;
    
    /**
     * Constructor
     * @param int $userid User ID
     */
    public function __construct($userid) {
        $this->userid = $userid;
        $this->service = new routine_service();
    }
    
    /**
     * Process schedule and detect exam information
     * 
     * @param int $scheduleid Schedule ID to process
     * @return array Result with success status and message
     */
    public function process_schedule($scheduleid) {
        global $DB;
        
        try {
            // Get the latest pinned schedule or special period schedule
            $schedule = $this->get_exam_schedule($scheduleid);
            
            if (!$schedule) {
                return [
                    'success' => false,
                    'message' => '시험 일정을 찾을 수 없습니다.',
                    'show_popup' => true
                ];
            }
            
            // Extract exam information from schedule
            $examInfo = $this->extract_exam_info($schedule);
            
            if (!$examInfo) {
                return [
                    'success' => false,
                    'message' => '시험 정보를 추출할 수 없습니다.',
                    'show_popup' => true,
                    'schedule' => $schedule
                ];
            }
            
            // Call routine service to create/update exam and routine
            $examid = $this->service->on_exam_saved(
                $this->userid,
                $examInfo['examdate'],
                $scheduleid,
                $examInfo['label']
            );
            
            if ($examid) {
                return [
                    'success' => true,
                    'message' => '시험 일정이 등록되었습니다: ' . $examInfo['label'],
                    'examid' => $examid,
                    'examinfo' => $examInfo
                ];
            }
            
            return [
                'success' => false,
                'message' => '시험 등록에 실패했습니다.',
                'show_popup' => true
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => '오류: ' . $e->getMessage(),
                'show_popup' => true
            ];
        }
    }
    
    /**
     * Get exam schedule based on priority
     * 1. Latest pinned schedule (pinned=1)
     * 2. Special period schedule (type=임시 or type=특강)
     * 
     * @param int $scheduleid Optional specific schedule ID
     * @return object|null Schedule object or null
     */
    private function get_exam_schedule($scheduleid = null) {
        global $DB;
        
        if ($scheduleid) {
            // Get specific schedule
            $schedule = $DB->get_record('abessi_schedule', [
                'id' => $scheduleid,
                'userid' => $this->userid
            ]);
            
            if ($schedule) {
                return $schedule;
            }
        }
        
        // Priority 1: Get latest pinned schedule
        $pinnedSchedule = $DB->get_record_sql(
            "SELECT * FROM {abessi_schedule} 
             WHERE userid = :userid AND pinned = 1 
             ORDER BY id DESC LIMIT 1",
            ['userid' => $this->userid]
        );
        
        if ($pinnedSchedule) {
            return $pinnedSchedule;
        }
        
        // Priority 2: Get special period schedule (임시 or 특강)
        $specialSchedule = $DB->get_record_sql(
            "SELECT * FROM {abessi_schedule} 
             WHERE userid = :userid 
               AND (type = '임시' OR type = '특강')
               AND date IS NOT NULL
             ORDER BY date DESC, id DESC LIMIT 1",
            ['userid' => $this->userid]
        );
        
        return $specialSchedule;
    }
    
    /**
     * Extract exam information from schedule
     * 
     * @param object $schedule Schedule object
     * @return array|null Exam info array or null
     */
    private function extract_exam_info($schedule) {
        // Parse memo field for exam information
        $memo = $schedule->memo ?? '';
        $type = $schedule->type ?? '';
        $date = $schedule->date ?? null;
        
        // Initialize exam info
        $examInfo = [
            'label' => '',
            'examdate' => 0,
            'type' => ''
        ];
        
        // Pattern matching for exam keywords in memo
        $examPatterns = [
            '/중간고사/u',
            '/기말고사/u',
            '/모의고사/u',
            '/월말평가/u',
            '/단원평가/u',
            '/수행평가/u'
        ];
        
        // Check memo for exam keywords
        foreach ($examPatterns as $pattern) {
            if (preg_match($pattern, $memo, $matches)) {
                $examInfo['type'] = $matches[0];
                break;
            }
        }
        
        // Extract date from memo or use schedule date
        if ($date) {
            // Use schedule date field for special periods
            $examInfo['examdate'] = strtotime($date);
        } else {
            // Try to extract date from memo
            $datePattern = '/(\d{4})[년\-\/]?\s*(\d{1,2})[월\-\/]?\s*(\d{1,2})[일]?/';
            if (preg_match($datePattern, $memo, $dateMatches)) {
                $year = $dateMatches[1];
                $month = $dateMatches[2];
                $day = $dateMatches[3];
                $examInfo['examdate'] = strtotime("$year-$month-$day");
            } else {
                // Try MM/DD or MM-DD format
                $shortDatePattern = '/(\d{1,2})[\/\-](\d{1,2})/';
                if (preg_match($shortDatePattern, $memo, $shortMatches)) {
                    $month = $shortMatches[1];
                    $day = $shortMatches[2];
                    $year = date('Y');
                    // If month is less than current month, assume next year
                    if ($month < date('n')) {
                        $year++;
                    }
                    $examInfo['examdate'] = strtotime("$year-$month-$day");
                }
            }
        }
        
        // Generate label
        if ($examInfo['type']) {
            $monthLabel = $examInfo['examdate'] ? date('n월', $examInfo['examdate']) : '';
            $examInfo['label'] = $monthLabel . ' ' . $examInfo['type'];
        } else if ($type === '임시' || $type === '특강') {
            // Use schedule type as exam type
            $examInfo['type'] = $type;
            $examInfo['label'] = date('n월', $examInfo['examdate']) . ' ' . $type . ' 시험';
        }
        
        // Validate extracted info
        if (empty($examInfo['label']) || empty($examInfo['examdate'])) {
            return null;
        }
        
        return $examInfo;
    }
    
    /**
     * Show exam registration popup
     * 
     * @return string HTML for popup
     */
    public function get_registration_popup_html() {
        $popupHtml = '
        <div id="routinecoach-exam-popup" style="display: none; position: fixed; top: 50%; left: 50%; 
             transform: translate(-50%, -50%); background: white; padding: 20px; 
             border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); z-index: 10000;">
            <h3>시험 정보 등록</h3>
            <p>스케줄에서 시험 정보를 자동으로 감지할 수 없습니다.</p>
            <p>수동으로 시험 정보를 등록하시겠습니까?</p>
            
            <form id="exam-registration-form">
                <div style="margin: 10px 0;">
                    <label>시험 이름:</label>
                    <input type="text" id="exam-label" placeholder="예: 3월 중간고사" required 
                           style="width: 100%; padding: 5px;">
                </div>
                <div style="margin: 10px 0;">
                    <label>시험 날짜:</label>
                    <input type="date" id="exam-date" required 
                           style="width: 100%; padding: 5px;">
                </div>
                <div style="margin: 10px 0;">
                    <label>시험 유형:</label>
                    <select id="exam-type" style="width: 100%; padding: 5px;">
                        <option value="중간고사">중간고사</option>
                        <option value="기말고사">기말고사</option>
                        <option value="모의고사">모의고사</option>
                        <option value="월말평가">월말평가</option>
                        <option value="단원평가">단원평가</option>
                        <option value="수행평가">수행평가</option>
                    </select>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" onclick="closeExamPopup()" 
                            style="padding: 5px 15px; margin-right: 10px;">취소</button>
                    <button type="submit" 
                            style="padding: 5px 15px; background: #667eea; color: white; 
                                   border: none; border-radius: 4px;">등록</button>
                </div>
            </form>
        </div>
        <div id="routinecoach-exam-overlay" style="display: none; position: fixed; top: 0; left: 0; 
             width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;"></div>
        ';
        
        return $popupHtml;
    }
    
    /**
     * Get JavaScript for schedule monitoring
     * 
     * @return string JavaScript code
     */
    public function get_monitoring_script() {
        global $CFG, $USER;
        
        $script = "
        <script>
        // Routine Coach Schedule Monitoring Script
        (function() {
            var lastScheduleData = null;
            var checkInterval = null;
            
            // Monitor schedule changes
            function monitorScheduleChanges() {
                // Check for schedule form submission
                var scheduleForm = document.querySelector('form[action*=\"schedule\"]');
                if (scheduleForm) {
                    scheduleForm.addEventListener('submit', function(e) {
                        // Let form submit normally, then check for changes
                        setTimeout(checkScheduleUpdate, 1000);
                    });
                }
                
                // Monitor AJAX updates
                if (typeof $ !== 'undefined') {
                    $(document).ajaxComplete(function(event, xhr, settings) {
                        if (settings.url && settings.url.indexOf('schedule') !== -1) {
                            checkScheduleUpdate();
                        }
                    });
                }
            }
            
            // Check for schedule updates
            function checkScheduleUpdate() {
                $.ajax({
                    url: '" . $CFG->wwwroot . "/local/routinecoach/ajax/check_schedule.php',
                    type: 'POST',
                    data: {
                        userid: " . $USER->id . ",
                        sesskey: M.cfg.sesskey
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.schedule_changed) {
                            processScheduleChange(response.scheduleid);
                        }
                    }
                });
            }
            
            // Process detected schedule change
            function processScheduleChange(scheduleid) {
                $.ajax({
                    url: '" . $CFG->wwwroot . "/local/routinecoach/ajax/process_exam.php',
                    type: 'POST',
                    data: {
                        userid: " . $USER->id . ",
                        scheduleid: scheduleid,
                        sesskey: M.cfg.sesskey
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification('시험 일정이 자동으로 등록되었습니다: ' + response.examinfo.label);
                        } else if (response.show_popup) {
                            showExamRegistrationPopup();
                        }
                    }
                });
            }
            
            // Show notification
            function showNotification(message) {
                var notification = $('<div>')
                    .text(message)
                    .css({
                        position: 'fixed',
                        top: '20px',
                        right: '20px',
                        padding: '10px 20px',
                        background: '#4CAF50',
                        color: 'white',
                        borderRadius: '4px',
                        zIndex: 10000
                    })
                    .appendTo('body');
                
                setTimeout(function() {
                    notification.fadeOut(function() {
                        notification.remove();
                    });
                }, 3000);
            }
            
            // Show exam registration popup
            function showExamRegistrationPopup() {
                $('#routinecoach-exam-popup').show();
                $('#routinecoach-exam-overlay').show();
            }
            
            // Close popup
            window.closeExamPopup = function() {
                $('#routinecoach-exam-popup').hide();
                $('#routinecoach-exam-overlay').hide();
            }
            
            // Handle manual exam registration
            $('#exam-registration-form').on('submit', function(e) {
                e.preventDefault();
                
                var examData = {
                    label: $('#exam-label').val(),
                    date: $('#exam-date').val(),
                    type: $('#exam-type').val(),
                    userid: " . $USER->id . ",
                    sesskey: M.cfg.sesskey
                };
                
                $.ajax({
                    url: '" . $CFG->wwwroot . "/local/routinecoach/ajax/register_exam.php',
                    type: 'POST',
                    data: examData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            showNotification('시험이 성공적으로 등록되었습니다');
                            closeExamPopup();
                        } else {
                            alert('시험 등록에 실패했습니다: ' + response.message);
                        }
                    }
                });
            });
            
            // Initialize monitoring
            $(document).ready(function() {
                monitorScheduleChanges();
            });
        })();
        </script>
        ";
        
        return $script;
    }
}

// Usage in schedule42.php:
/*
// Add this at the beginning of schedule42.php after require_login()

require_once($CFG->dirroot . '/local/routinecoach/hooks/schedule_exam_hook.php');

// Initialize hook
$examHook = new schedule_exam_hook($USER->id);

// Add popup HTML to page
echo $examHook->get_registration_popup_html();

// Add monitoring script
echo $examHook->get_monitoring_script();

// When schedule is saved/updated, call:
if ($schedule_saved) {
    $result = $examHook->process_schedule($scheduleid);
    
    if ($result['success']) {
        // Show success message
        echo '<div class="alert alert-success">' . $result['message'] . '</div>';
    } else if ($result['show_popup']) {
        // Trigger popup via JavaScript
        echo '<script>showExamRegistrationPopup();</script>';
    }
}
*/