<?php
/**
 * Agent 17 - Remaining Activities Data Provider
 * File: agent17_remaining_activities/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - ?¤ë¥¸ ?ì´?„íŠ¸?¤ë¡œë¶€??ì·¨í•©???•ë³´:
 *   - Agent 03: mdl_alt42g_goal_analysis, mdl_alt42g_student_goals ??ì¡´ìž¬ ?•ì¸??
 *   - Agent 14: mdl_abessi_todayplans ??ì¡´ìž¬ ?•ì¸??
 *   - Agent 04: mdl_alt42_student_activity ??ì¡´ìž¬ ?•ì¸??
 *   - Agent 15: ë¬¸ì œ ?¬ì •???°ì´??(?¤ë¥¸ ?ì´?„íŠ¸ ì¢…í•©)
 *   - Agent 01: ?¨ë³´???•ë³´
 *   - Agent 05: ?™ìŠµ ê°ì • ?°ì´??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getRemainingActivitiesContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'remaining_activities' => [],
        'completion_rate' => null,
        'agent_contexts' => []
    ];
    
    try {
        // Agent 14???„ìž¬ ?„ì¹˜ ?°ì´???œìš©
        $diaryRecord = $DB->get_record_sql(
            "SELECT * FROM {abessi_todayplans}
             WHERE userid = ?
             AND timecreated >= ?
             ORDER BY timecreated DESC
             LIMIT 1",
            [$studentid, time() - 43200]
        );
        
        if ($diaryRecord) {
            $completed = 0;
            $total = 0;
            
            for ($i = 1; $i <= 16; $i++) {
                $planField = 'plan' . $i;
                $tendField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                $planText = isset($diaryRecord->$planField) ? $diaryRecord->$planField : '';
                $tend = isset($diaryRecord->$tendField) ? intval($diaryRecord->$tendField) : null;
                
                if (!empty($planText)) {
                    $total++;
                    if ($tend !== null && $tend > 0) {
                        $completed++;
                    } else {
                        $context['remaining_activities'][] = [
                            'index' => $i,
                            'plan' => $planText,
                            'status' => 'pending'
                        ];
                    }
                }
            }
            
            $context['completion_rate'] = $total > 0 ? round(($completed / $total) * 100, 1) : 0;
        }
        
        // TODO: Agent 03, 04, 15???°ì´?°ë„ ?˜ì§‘?˜ì—¬ ?µí•© ì»¨í…?¤íŠ¸ êµ¬ì„± ?„ìš”
        
    } catch (Exception $e) {
        error_log("Error in getRemainingActivitiesContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getRemainingActivitiesContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
