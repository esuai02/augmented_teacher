<?php
/**
 * Agent 05 - Learning Emotion Data Provider
 * File: agent05_learning_emotion/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_user_info_data: ?¬ìš©????•  ?•ë³´ (fieldid='22') ??ì¡´ì¬ ?•ì¸??
 * 
 * TODO: ?¤ìŒ ?Œì´ë¸??°ì´?°ê? ?„ìš”?©ë‹ˆ??
 * - mdl_learning_emotions: ?™ìŠµ ê°ì • ?°ì´???Œì´ë¸??ì„± ?„ìš” (user_id, emotion_type, activity_type, timecreated ??
 * - ê°ì • ?¤ë¬¸ ?‘ë‹µ: ?œë™ë³?ê°ì • ?¤ë¬¸ ?‘ë‹µ ?°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 * - ?‰ë™ë¡œê·¸: ê°??œë™ë³??‰ë™ ?¨í„´ (?„ê¸°?¨í„´, ë°˜ë³µ?œë„, ë©ˆì¶¤?œê°„, ì§ˆë¬¸ ë¹ˆë„, TTS ?¬ìš©, ?´ì‹ ?€?´ë° ?? ?˜ì§‘ ë¡œì§ ?„ìš”
 * - ê°ì •ê´€??ë°˜ì‘ ?°ì´?? ?œì •Â·?œì„ Â·?Œì„±?¤Â·ë§ˆ?°ìŠ¤ ?€ì§ì„ ???˜ì§‘ ë¡œì§ ?„ìš”
 * - ?˜ë¥´?Œë‚˜ ë§¤ì¹­ ?°ì´?? ê°ì •-?‰ë™ ë²¡í„° ë°??˜ë¥´?Œë‚˜ ?•ë³´ ?€???Œì´ë¸??ì„± ?„ìš”
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * ?™ìŠµ ê°ì • ?°ì´???˜ì§‘
 * 
 * @param int $studentid ?™ìƒ ID
 * @return array ?™ìŠµ ê°ì • ì»¨í…?¤íŠ¸ ?°ì´??
 */
function getLearningEmotionContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'current_emotion' => null,
        'emotion_history' => [],
        'emotion_pattern' => null,
        'triggers' => []
    ];
    
    try {
        // TODO: mdl_learning_emotions ?Œì´ë¸”ì´ ?ì„±?˜ë©´ ?„ë˜ ì£¼ì„ ?´ì œ
        // if ($DB->get_manager()->table_exists(new xmldb_table('mdl_learning_emotions'))) {
        //     $emotions = $DB->get_records('mdl_learning_emotions', ['user_id' => $studentid], 'timecreated DESC', '*', 0, 20);
        //     
        //     foreach ($emotions as $emotion) {
        //         $context['emotion_history'][] = [
        //             'emotion' => $emotion->emotion_type,
        //             'timestamp' => $emotion->timecreated,
        //             'activity_type' => $emotion->activity_type
        //         ];
        //     }
        //     
        //     if (!empty($emotions)) {
        //         $latest = reset($emotions);
        //         $context['current_emotion'] = $latest->emotion_type ?? null;
        //     }
        // }
        
    } catch (Exception $e) {
        error_log("Error in getLearningEmotionContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ë£??‰ê?ë¥??„í•œ ì»¨í…?¤íŠ¸ ì¤€ë¹?
 */
function prepareRuleContext($studentid) {
    $context = getLearningEmotionContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
