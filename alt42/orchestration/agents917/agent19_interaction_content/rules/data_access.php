<?php
/**
 * Agent 19 - Interaction Content Data Provider
 * File: agent19_interaction_content/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - ?¤ë¥¸ ?ì´?„íŠ¸?¤ë¡œë¶€??ì·¨í•©???•ë³´:
 *   - Agent 01: user_profile (mdl_user, mdl_abessi_mbtilog, mdl_alt42_student_profiles) ??ì¡´ìž¬ ?•ì¸??
 * 
 * ì°¸ê³ : ???ì´?„íŠ¸??ì£¼ë¡œ ?´ì „ ?¨ê³„??ë¶„ì„ ê²°ê³¼ë¥?ì¢…í•©?˜ì—¬ ?í˜¸?‘ìš© ì»¨í…ì¸ ë? ?ì„±?˜ë?ë¡?
 * ì§ì ‘?ì¸ ?°ì´?°ë² ?´ìŠ¤ ?Œì´ë¸?ì°¸ì¡°ë³´ë‹¤???¤ë¥¸ ?ì´?„íŠ¸??ê²°ê³¼ë¥?ì°¸ì¡°?©ë‹ˆ??
 * 
 * TODO: ?¤ìŒ ?°ì´???ŒìŠ¤ê°€ ?„ìš”?©ë‹ˆ??
 * - analysis_summary: ?´ì „ ?¨ê³„??ëª¨ë“  ë¶„ì„ ê²°ê³¼ ?µí•© ?°ì´??êµ¬ì¡° ?„ìš”
 * - ?í˜¸?‘ìš© ?œí”Œë¦??¼ì´ë¸ŒëŸ¬ë¦? ê¸°ì¡´ ?í˜¸?‘ìš© ?œí”Œë¦??°ì´???€?¥ì†Œ ?„ìš”
 * - ?´ì „ ?í˜¸?‘ìš© ê¸°ë¡: ê³¼ê±° ?í˜¸?‘ìš© ?¨ê³¼???°ì´???€?¥ì†Œ ?„ìš”
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getInteractionContentContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'target_goals' => [],
        'current_level' => null,
        'user_profile' => []
    ];
    
    try {
        // ?¬ìš©???„ë¡œ???•ë³´ ?˜ì§‘ (Agent 01 ?°ì´???œìš©)
        $student = $DB->get_record('user', ['id' => $studentid], 'id, firstname, lastname', MUST_EXIST);
        
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_abessi_mbtilog'))) {
            $mbtiLog = $DB->get_record_sql(
                "SELECT * FROM {abessi_mbtilog} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$studentid]
            );
            if ($mbtiLog && !empty($mbtiLog->mbti)) {
                $context['user_profile']['mbti'] = strtoupper($mbtiLog->mbti);
            }
        }
        
        // TODO: analysis_summary, ?í˜¸?‘ìš© ?œí”Œë¦? ?´ì „ ?í˜¸?‘ìš© ê¸°ë¡ ???˜ì§‘ ë¡œì§ êµ¬í˜„ ?„ìš”
        
    } catch (Exception $e) {
        error_log("Error in getInteractionContentContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getInteractionContentContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
