<?php
/**
 * Agent 06 - Teacher Feedback Data Provider
 * File: agent06_teacher_feedback/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_abessi_todayplans: êµì‚¬ ?¼ë“œë°??°ì´??(userid, plan1-16, status01-16, timecreated ?? ??ì¡´ì¬ ?•ì¸??
 * 
 * TODO: ?¤ìŒ ?Œì´ë¸??°ì´?°ê? ?„ìš”?©ë‹ˆ??
 * - mdl_teacher_feedback: ?„ìš© êµì‚¬ ?¼ë“œë°??Œì´ë¸??ì„± ê³ ë ¤ (student_id, teacher_id, content, strengths, weaknesses, timecreated ??
 *   ?ëŠ” abessi_todayplans??status ?„ë“œë¥??œìš©?˜ì—¬ ?¼ë“œë°??•ë³´ ì¶”ì¶œ ë¡œì§ ê°œë°œ ?„ìš”
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * êµì‚¬ ?¼ë“œë°??°ì´???˜ì§‘
 * 
 * @param int $studentid ?™ìƒ ID
 * @return array êµì‚¬ ?¼ë“œë°?ì»¨í…?¤íŠ¸ ?°ì´??
 */
function getTeacherFeedbackContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'recent_feedback' => [],
        'strengths' => [],
        'error_patterns' => [],
        'improvement_areas' => []
    ];
    
    try {
        // êµì‚¬ ?¼ë“œë°??°ì´??ì¡°íšŒ (mdl_abessi_todayplans ?Œì´ë¸”ì—??status ?„ë“œ ?œìš©)
        $recentPlans = $DB->get_records_sql(
            "SELECT * FROM {abessi_todayplans} 
             WHERE userid = ? 
             ORDER BY timecreated DESC 
             LIMIT 5",
            [$studentid]
        );
        
        if ($recentPlans) {
            foreach ($recentPlans as $plan) {
                $feedback = [
                    'id' => $plan->id,
                    'timecreated' => $plan->timecreated,
                    'plans' => [],
                    'statuses' => []
                ];
                
                // plan1-16 ë°?status01-16 ?„ë“œ ì¶”ì¶œ
                for ($i = 1; $i <= 16; $i++) {
                    $planField = 'plan' . $i;
                    $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                    
                    if (isset($plan->$planField) && !empty($plan->$planField)) {
                        $feedback['plans'][] = $plan->$planField;
                        if (isset($plan->$statusField)) {
                            $feedback['statuses'][] = $plan->$statusField;
                        }
                    }
                }
                
                $context['recent_feedback'][] = $feedback;
            }
        }
        
        // TODO: mdl_teacher_feedback ?„ìš© ?Œì´ë¸”ì´ ?ì„±?˜ë©´ ?„ë˜ ì£¼ì„ ?´ì œ
        // if ($DB->get_manager()->table_exists(new xmldb_table('mdl_teacher_feedback'))) {
        //     $feedbacks = $DB->get_records('mdl_teacher_feedback', ['student_id' => $studentid], 'timecreated DESC', '*', 0, 10);
        //     
        //     foreach ($feedbacks as $feedback) {
        //         $context['recent_feedback'][] = [
        //             'teacher_id' => $feedback->teacher_id,
        //             'content' => $feedback->content,
        //             'strengths' => $feedback->strengths,
        //             'weaknesses' => $feedback->weaknesses,
        //             'timestamp' => $feedback->timecreated
        //         ];
        //     }
        // }
        
    } catch (Exception $e) {
        error_log("Error in getTeacherFeedbackContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ë£??‰ê?ë¥??„í•œ ì»¨í…?¤íŠ¸ ì¤€ë¹?
 */
function prepareRuleContext($studentid) {
    $context = getTeacherFeedbackContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
