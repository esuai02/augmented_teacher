<?php
/**
 * Agent 07 - Interaction Targeting Data Provider
 * File: agent07_interaction_targeting/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - ?¤ë¥¸ ?ì´?„íŠ¸?¤ë¡œë¶€??ì·¨í•©???•ë³´ (Agent 01~06, 13, 14, 15 ?? - API ?¸ì¶œ ?ëŠ” ê³µìœ  ì»¨í…?¤íŠ¸ ?œìš©
 * 
 * ì°¸ê³ : ???ì´?„íŠ¸??ì£¼ë¡œ ?¤ë¥¸ ?ì´?„íŠ¸??ë¶„ì„ ê²°ê³¼ë¥?ì¢…í•©?˜ì—¬ ?¬ìš©?˜ë?ë¡?
 * ì§ì ‘?ì¸ ?°ì´?°ë² ?´ìŠ¤ ?Œì´ë¸?ì°¸ì¡°ë³´ë‹¤???¤ë¥¸ ?ì´?„íŠ¸??ê²°ê³¼ë¥?ì°¸ì¡°?©ë‹ˆ??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * ê°œì… ?€ê²ŸíŒ… ?°ì´???˜ì§‘
 * 
 * @param int $studentid ?™ìƒ ID
 * @return array ê°œì… ?€ê²ŸíŒ… ì»¨í…?¤íŠ¸ ?°ì´??
 */
function getInteractionTargetingContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'current_time_of_day' => date('H'),
        'energy_level' => null,
        'focus_level' => null,
        'available_time' => null,
        'urgency_level' => null,
        'agent_contexts' => []
    ];
    
    try {
        // ?¤ë¥¸ ?ì´?„íŠ¸?¤ì˜ ì»¨í…?¤íŠ¸ë¥??˜ì§‘?˜ë ¤ë©?ê°??ì´?„íŠ¸??data_access.php ?¨ìˆ˜ë¥??¸ì¶œ?´ì•¼ ??
        // ?ˆì‹œ: Agent 01, 05, 13, 14??ì»¨í…?¤íŠ¸ ?œìš©
        
        // ?„ì¬ ?œê°„?€ ê¸°ë°˜ ê¸°ë³¸ ?¤ì •
        $hour = intval(date('H'));
        if ($hour >= 6 && $hour < 12) {
            $context['energy_level'] = 'morning';
        } elseif ($hour >= 12 && $hour < 18) {
            $context['energy_level'] = 'afternoon';
        } else {
            $context['energy_level'] = 'evening';
        }
        
        // TODO: ?¤ë¥¸ ?ì´?„íŠ¸??ì»¨í…?¤íŠ¸ë¥??¤ì œë¡??˜ì§‘?˜ëŠ” ë¡œì§ êµ¬í˜„ ?„ìš”
        // ?? Agent 05??ê°ì • ?íƒœ, Agent 13???´íƒˆ ?„í—˜?? Agent 14??ì§„í–‰ ?íƒœ ??
        
    } catch (Exception $e) {
        error_log("Error in getInteractionTargetingContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ë£??‰ê?ë¥??„í•œ ì»¨í…?¤íŠ¸ ì¤€ë¹?
 */
function prepareRuleContext($studentid) {
    $context = getInteractionTargetingContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
