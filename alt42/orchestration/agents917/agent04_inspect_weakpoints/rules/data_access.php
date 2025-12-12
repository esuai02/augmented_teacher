<?php
/**
 * Agent 04 - Problem Activity Data Provider
 * File: agent04_problem_activity/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_alt42_student_activity: ?™ìƒ ?œë™ ? íƒ ë°??‰ë™ ? í˜• ?°ì´??
 *   (userid, main_category, sub_activity, behavior_type, survey_responses, created_at) ??ì¡´ì¬ ?•ì¸??
 *   - main_category: concept_understanding, type_learning, problem_solving, error_notes, qa, review, pomodoro
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * ë¬¸ì œ ?œë™ ?°ì´???˜ì§‘
 * 
 * @param int $studentid ?™ìƒ ID
 * @return array ë¬¸ì œ ?œë™ ì»¨í…?¤íŠ¸ ?°ì´??
 */
function getProblemActivityContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'recent_activities' => [],
        'activity_patterns' => [],
        'main_categories' => []
    ];
    
    try {
        // ?™ìƒ ?œë™ ?°ì´??ì¡°íšŒ (mdl_alt42_student_activity ?Œì´ë¸?
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42_student_activity'))) {
            $activities = $DB->get_records_sql(
                "SELECT * FROM {alt42_student_activity} 
                 WHERE userid = ? 
                 ORDER BY created_at DESC 
                 LIMIT 20",
                [$studentid]
            );
            
            if ($activities) {
                $categoryCount = [];
                foreach ($activities as $activity) {
                    $activityData = [
                        'id' => $activity->id,
                        'main_category' => $activity->main_category ?? null,
                        'sub_activity' => $activity->sub_activity ?? null,
                        'behavior_type' => $activity->behavior_type ?? null,
                        'created_at' => $activity->created_at ?? null
                    ];
                    
                    if (isset($activity->survey_responses)) {
                        $activityData['survey_responses'] = json_decode($activity->survey_responses, true);
                    }
                    
                    $context['recent_activities'][] = $activityData;
                    
                    // ì¹´í…Œê³ ë¦¬ë³?ì§‘ê³„
                    if (isset($activity->main_category)) {
                        $categoryCount[$activity->main_category] = ($categoryCount[$activity->main_category] ?? 0) + 1;
                    }
                }
                
                $context['activity_patterns'] = $categoryCount;
                $context['main_categories'] = array_keys($categoryCount);
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in getProblemActivityContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ë£??‰ê?ë¥??„í•œ ì»¨í…?¤íŠ¸ ì¤€ë¹?
 */
function prepareRuleContext($studentid) {
    $context = getProblemActivityContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
