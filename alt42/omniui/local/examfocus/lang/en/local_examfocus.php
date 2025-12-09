<?php
/**
 * English language file
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Exam Focus Auto Study Mode';

// Settings
$string['d30_threshold'] = 'D-30 Threshold';
$string['d30_threshold_desc'] = 'Days before exam to start 30-day notifications';
$string['d7_threshold'] = 'D-7 Threshold';
$string['d7_threshold_desc'] = 'Days before exam to start intensive mode';
$string['message_d30'] = 'D-30 Message';
$string['message_d30_desc'] = 'Message to display 30 days before exam';
$string['message_d7'] = 'D-7 Message';
$string['message_d7_desc'] = 'Message to display 7 days before exam';
$string['min_week_hours'] = 'Minimum Weekly Study Hours';
$string['min_week_hours_desc'] = 'Minimum weekly study hours required for recommendations';
$string['min_total_hours'] = 'Minimum Total Study Hours';
$string['min_total_hours_desc'] = 'Minimum total study hours required for recommendations';
$string['cooldown_hours'] = 'Notification Cooldown';
$string['cooldown_hours_desc'] = 'Hours to wait before showing notification again after dismissal';
$string['auto_switch'] = 'Auto Mode Switch';
$string['auto_switch_desc'] = 'Automatically switch study mode when accepting recommendation';

// Study Modes
$string['mode_d30'] = 'D-30 Recommended Mode';
$string['mode_d30_desc'] = 'Study mode to recommend 30 days before exam';
$string['mode_d7'] = 'D-7 Recommended Mode';
$string['mode_d7_desc'] = 'Study mode to recommend 7 days before exam';
$string['mode_review_errors'] = 'Review Errors';
$string['mode_concept_summary'] = 'Concept Summary';
$string['mode_practice_problems'] = 'Practice Problems';
$string['mode_key_problems'] = 'Key Problems';
$string['mode_final_review'] = 'Final Review';

// UI Text
$string['recommendation_title'] = '📚 Exam Preparation Mode Recommendation';
$string['apply_recommendation'] = 'Switch to Recommended Mode';
$string['dismiss'] = 'Later';
$string['exam_approaching'] = 'Your exam is approaching!';
$string['days_remaining'] = 'Days remaining: {$a}';

// Tasks
$string['task_scan_exams'] = 'Scan Exam Schedules';
$string['task_send_reminders'] = 'Send Study Reminders';

// Capabilities
$string['examfocus:view_recommendations'] = 'View study recommendations';
$string['examfocus:manage_settings'] = 'Manage settings';
$string['examfocus:manage_rules'] = 'Manage rules';
$string['examfocus:view_statistics'] = 'View statistics';

// Messages
$string['recommendation_accepted'] = 'Study mode has been changed.';
$string['recommendation_dismissed'] = 'Recommendation dismissed. We\'ll remind you later.';
$string['no_exam_scheduled'] = 'No exam scheduled.';
$string['feature_disabled'] = 'Exam focus mode is disabled.';