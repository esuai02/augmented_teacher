<?php
/**
 * Agent 09 - Learning Management Data Provider
 * File: agent09_learning_management/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_user: ?™ìƒ ê¸°ë³¸ ?•ë³´ (id, firstname, lastname) ??ì¡´ìž¬ ?•ì¸??
 * - mdl_alt42g_goal_analysis: ëª©í‘œ ë¶„ì„ ?°ì´??(Agent 03ê³??°ê³„) ??ì¡´ìž¬ ?•ì¸??
 * - mdl_alt42g_pomodoro_sessions: ?¬ëª¨?„ë¥´ ?°ì´????ì¡´ìž¬ ?•ì¸??
 * - mdl_abessi_messages: ?¤ë‹µ?¸íŠ¸ ?°ì´??(contentstype=2, Agent 11ê³??°ê³„) ??ì¡´ìž¬ ?•ì¸??
 * 
 * TODO: ?¤ìŒ ?°ì´???ŒìŠ¤ê°€ ?„ìš”?©ë‹ˆ??
 * - ì¶œê²° ?°ì´?? ??ì£??¨í„´, ??Œ/ê²°ì„, ?œê°„?€ë³?ê°€ì¤‘ì¹˜ ?˜ì§‘ ë¡œì§ ?„ìš” (mdl_logstore_standard_log ?œìš© ê°€??
 * - ?œí—˜ ?°ì´?? ?‰ê· /ìµœê³ /ìµœì?, ?œì´???œê°„ê´€ë¦? ê³¼ëª©ë³??¸ì°¨ ?˜ì§‘ ë¡œì§ ?„ìš”
 * - ?œë™ê²°ê³¼ ?°ì´?? ?˜ì—… ì°¸ì—¬ ?¬ë¦¬???˜ì¡´???°ì´???˜ì§‘ ë¡œì§ ?„ìš”
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * ?™ìŠµ ê´€ë¦??°ì´???˜ì§‘
 */
function getLearningManagementContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'attendance_pattern' => [],
        'goal_achievement' => null,
        'pomodoro_completion' => null,
        'error_patterns' => []
    ];
    
    try {
        // ?™ìƒ ê¸°ë³¸ ?•ë³´ ?•ì¸
        $student = $DB->get_record('user', ['id' => $studentid], 'id, firstname, lastname', MUST_EXIST);
        
        // ëª©í‘œ ?¬ì„±ë¥?(mdl_alt42g_goal_analysis ?œìš©)
        $goalAnalysis = $DB->get_record_sql(
            "SELECT * FROM {alt42g_goal_analysis} 
             WHERE userid = ? 
             ORDER BY created_at DESC 
             LIMIT 1",
            [$studentid]
        );
        
        if ($goalAnalysis && isset($goalAnalysis->effectiveness_score)) {
            $context['goal_achievement'] = floatval($goalAnalysis->effectiveness_score);
        }
        
        // ?¬ëª¨?„ë¥´ ?„ì„±ë¥?(mdl_alt42g_pomodoro_sessions ?œìš©)
        $pomodoroStats = $DB->get_record_sql(
            "SELECT 
                COUNT(*) as total_sessions,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
             FROM {alt42g_pomodoro_sessions}
             WHERE userid = ?
             AND timecreated >= ?",
            [$studentid, time() - (30 * 86400)]
        );
        
        if ($pomodoroStats && $pomodoroStats->total_sessions > 0) {
            $context['pomodoro_completion'] = round(($pomodoroStats->completed_sessions / $pomodoroStats->total_sessions) * 100, 1);
        }
        
        // ?¤ë‹µ?¸íŠ¸ ?¨í„´ (mdl_abessi_messages??contentstype=2 ?œìš©)
        $wrongNotes = $DB->get_records_sql(
            "SELECT COUNT(*) as total, status
             FROM {abessi_messages}
             WHERE userid = ?
             AND contentstype = 2
             AND timecreated >= ?
             GROUP BY status",
            [$studentid, time() - (30 * 86400)]
        );
        
        if ($wrongNotes) {
            foreach ($wrongNotes as $note) {
                $context['error_patterns'][] = [
                    'status' => $note->status,
                    'count' => intval($note->total)
                ];
            }
        }
        
        // TODO: ì¶œê²° ?°ì´???˜ì§‘ ë¡œì§ êµ¬í˜„ ?„ìš”
        // TODO: ?œí—˜ ?°ì´???˜ì§‘ ë¡œì§ êµ¬í˜„ ?„ìš”
        
    } catch (Exception $e) {
        error_log("Error in getLearningManagementContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getLearningManagementContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
