<?php
/**
 * Agent 20 - Intervention Preparation Data Provider
 * File: agent20_intervention_preparation/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - ?¤ë¥¸ ?ì´?„íŠ¸?¤ë¡œë¶€??ì·¨í•©???•ë³´ (Agent 01~19??ë¶„ì„ ê²°ê³¼)
 * 
 * ì°¸ê³ : ???ì´?„íŠ¸??ì£¼ë¡œ ?´ì „ ?¨ê³„??ëª¨ë“  ë¶„ì„ ê²°ê³¼ë¥?ì¢…í•©?˜ì—¬ ê°œìž… ê³„íš???˜ë¦½?˜ë?ë¡?
 * ì§ì ‘?ì¸ ?°ì´?°ë² ?´ìŠ¤ ?Œì´ë¸?ì°¸ì¡°ë³´ë‹¤???¤ë¥¸ ?ì´?„íŠ¸??ê²°ê³¼ë¥?ì°¸ì¡°?©ë‹ˆ??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getInterventionPreparationContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'intervention_targets' => [],
        'available_resources' => [],
        'optimal_timing' => null,
        'analysis_summary' => []
    ];
    
    try {
        // ?™ìƒ ê¸°ë³¸ ?•ë³´ ?•ì¸
        $student = $DB->get_record('user', ['id' => $studentid], 'id, firstname, lastname', MUST_EXIST);
        
        // TODO: ?¤ë¥¸ ?ì´?„íŠ¸?¤ì˜ ë¶„ì„ ê²°ê³¼ë¥??˜ì§‘?˜ì—¬ ì¢…í•© ì»¨í…?¤íŠ¸ êµ¬ì„± ?„ìš”
        // - ?„ì²´ ë¶„ì„ ?°ì´??
        // - ?°ì„ ?œìœ„ ?°ì´??
        // - ?œì•½?¬í•­ ?°ì´??
        // - ê°œìž… ëª©í‘œ ?°ì´??
        
    } catch (Exception $e) {
        error_log("Error in getInterventionPreparationContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getInterventionPreparationContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
