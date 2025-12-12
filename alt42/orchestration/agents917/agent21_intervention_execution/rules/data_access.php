<?php
/**
 * Agent 21 - Intervention Execution Data Provider
 * File: agent21_intervention_execution/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - Agent 20?ì„œ ?„ë‹¬ë°›ì? ê°œìž… ê³„íš ?°ì´??(?´ë? ì»¨í…?¤íŠ¸)
 * - ?¤ë¥¸ ?ì´?„íŠ¸?¤ë¡œë¶€??ì·¨í•©???•ë³´:
 *   - Agent 01: ?™ìƒ ?„ë¡œ?? ì§‘ì¤‘ ?œê°„?€ (mdl_user, mdl_abessi_mbtilog) ??ì¡´ìž¬ ?•ì¸??
 *   - Agent 13: ?´íƒˆ ?„í—˜??(mdl_abessi_today, mdl_abessi_messages, mdl_abessi_tracking, mdl_abessi_indicators) ??ì¡´ìž¬ ?•ì¸??
 *   - Agent 14: ?„ìž¬ ì§„í–‰ ?„ì¹˜ (mdl_abessi_todayplans) ??ì¡´ìž¬ ?•ì¸??
 * 
 * TODO: ?¤ìŒ ?°ì´???ŒìŠ¤ê°€ ?„ìš”?©ë‹ˆ??
 * - ê°œì¸ ê°œìž… ëª©ë¡: ?€ê¸?ì¤‘ì¸ ê°œìž… ëª©ë¡ ?€?¥ì†Œ ?„ìš”
 * - ?¤í–‰ ?ˆìŠ¤? ë¦¬: ê³¼ê±° ?¤í–‰ ê¸°ë¡, ?¨ê³¼???°ì´???€?¥ì†Œ ?„ìš”
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getInterventionExecutionContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'intervention_plan' => [],
        'execution_status' => null,
        'student_state' => []
    ];
    
    try {
        // ?™ìƒ ?íƒœ ?°ì´???˜ì§‘ (Agent 13, 14 ?œìš©)
        
        // Agent 13???´íƒˆ ?„í—˜??
        $goal = $DB->get_record_sql(
            "SELECT ninactive, npomodoro FROM {abessi_today}
             WHERE userid = ? AND timecreated >= ? AND (type = ? OR type = ?)
             ORDER BY id DESC LIMIT 1",
            [$studentid, time() - 86400, '?¤ëŠ˜ëª©í‘œ', 'ê²€?¬ìš”ì²?]
        );
        
        if ($goal) {
            $context['student_state']['ninactive'] = isset($goal->ninactive) ? intval($goal->ninactive) : 0;
            $context['student_state']['npomodoro'] = isset($goal->npomodoro) ? intval($goal->npomodoro) : 0;
        }
        
        // Agent 14???„ìž¬ ì§„í–‰ ?„ì¹˜
        $diaryRecord = $DB->get_record_sql(
            "SELECT * FROM {abessi_todayplans}
             WHERE userid = ? AND timecreated >= ?
             ORDER BY timecreated DESC LIMIT 1",
            [$studentid, time() - 43200]
        );
        
        if ($diaryRecord) {
            $context['student_state']['has_active_diary'] = true;
        }
        
        // TODO: ê°œì¸ ê°œìž… ëª©ë¡, ?¤í–‰ ?ˆìŠ¤? ë¦¬ ???˜ì§‘ ë¡œì§ êµ¬í˜„ ?„ìš”
        
    } catch (Exception $e) {
        error_log("Error in getInterventionExecutionContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getInterventionExecutionContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
