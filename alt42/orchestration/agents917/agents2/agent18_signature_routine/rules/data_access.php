<?php
/**
 * Agent 18 - Signature Routine Data Provider
 * File: agent18_signature_routine/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - ?¤ë¥¸ ?ì´?„íŠ¸?¤ë¡œë¶€??ì·¨í•©???•ë³´:
 *   - Agent 01: ?¨ë³´???•ë³´ (mdl_user, mdl_abessi_mbtilog, mdl_alt42_student_profiles) ??ì¡´ìž¬ ?•ì¸??
 *   - Agent 09: ?¬ëª¨?„ë¥´ ?°ì´??(mdl_alt42g_pomodoro_sessions) ??ì¡´ìž¬ ?•ì¸??
 * 
 * TODO: ?¤ìŒ ?°ì´???ŒìŠ¤ê°€ ?„ìš”?©ë‹ˆ??
 * - ìµœê·¼ ? í˜¸???•ë³´: ?™ìŠµ ?™ê¸°Â·?¥ë?Â·ê°ì • ë³€???°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 * - ?œê°„?€ ?±ê³¼ ?°ì´?? ?œê°„?€ë³??™ìŠµ ?±ê³¼ ?°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 * - ?¸ì…˜ ê¸¸ì´ ?°ì´?? ?¸ì…˜ ê¸¸ì´ë³??±ê³¼ ê³¡ì„  ?°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 * - ê³¼ëª© ?í•©???°ì´?? ê³¼ëª©ë³?ìµœì  ?™ìŠµ ?œê°„?€ ?°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 * - ë°˜ë³µ ??ëª°ìž… ?ìŠ¹ ?¨í„´: ë°˜ë³µ ??ëª°ìž… ?ìŠ¹ ?¨í„´ ê°ì? ?°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 * - ê°ì • ?ˆì • ë°??±ì·¨ê°??ìŠ¹ êµ¬ê°„: ê°ì • ?¨í„´ ë°??±ì·¨ê°??°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getSignatureRoutineContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'time_performance' => [],
        'session_lengths' => [],
        'subject_suitability' => [],
        'onboarding_info' => []
    ];
    
    try {
        // Agent 01???¨ë³´???•ë³´ ?˜ì§‘
        $student = $DB->get_record('user', ['id' => $studentid], 'id, firstname, lastname', MUST_EXIST);
        
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_abessi_mbtilog'))) {
            $mbtiLog = $DB->get_record_sql(
                "SELECT * FROM {abessi_mbtilog} WHERE userid = ? ORDER BY timecreated DESC LIMIT 1",
                [$studentid]
            );
            if ($mbtiLog && !empty($mbtiLog->mbti)) {
                $context['onboarding_info']['mbti'] = strtoupper($mbtiLog->mbti);
            }
        }
        
        // Agent 09???¬ëª¨?„ë¥´ ?°ì´???˜ì§‘
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_alt42g_pomodoro_sessions'))) {
            $pomodoroSessions = $DB->get_records_sql(
                "SELECT * FROM {alt42g_pomodoro_sessions}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 30",
                [$studentid]
            );
            
            if ($pomodoroSessions) {
                $sessionLengths = [];
                foreach ($pomodoroSessions as $session) {
                    if (isset($session->duration)) {
                        $sessionLengths[] = intval($session->duration);
                    }
                }
                
                if (!empty($sessionLengths)) {
                    $context['session_lengths'] = [
                        'average' => round(array_sum($sessionLengths) / count($sessionLengths), 1),
                        'min' => min($sessionLengths),
                        'max' => max($sessionLengths),
                        'count' => count($sessionLengths)
                    ];
                }
            }
        }
        
        // TODO: ?œê°„?€ë³??±ê³¼, ê³¼ëª© ?í•©?? ëª°ìž… ?¨í„´ ??ì¶”ê? ?°ì´???˜ì§‘ ë¡œì§ êµ¬í˜„ ?„ìš”
        
    } catch (Exception $e) {
        error_log("Error in getSignatureRoutineContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getSignatureRoutineContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
