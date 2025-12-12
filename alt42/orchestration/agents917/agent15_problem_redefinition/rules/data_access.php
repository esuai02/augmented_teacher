<?php
/**
 * Agent 15 - Problem Redefinition Data Provider
 * File: agent15_problem_redefinition/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_alt42_exam_schedule: ?œí—˜ ?¼ì • ?°ì´????ì¡´ìž¬ ?•ì¸??
 * - mdl_alt42g_goal_analysis: ëª©í‘œ ë¶„ì„ ?°ì´????ì¡´ìž¬ ?•ì¸??
 * - mdl_abessi_todayplans: êµì‚¬ ?¼ë“œë°??°ì´????ì¡´ìž¬ ?•ì¸??
 * 
 * ì°¸ê³ : ???ì´?„íŠ¸??ì£¼ë¡œ ?¤ë¥¸ ?ì´?„íŠ¸(Agent 01~14)??ë¶„ì„ ê²°ê³¼ë¥?ì¢…í•©?˜ì—¬ ?¬ìš©?©ë‹ˆ??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getProblemRedefinitionContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'current_problems' => [],
        'root_causes' => [],
        'agent_data' => []
    ];
    
    try {
        // ì¢…í•© ë¶„ì„ ?°ì´???˜ì§‘ (?´ì „ ?¨ê³„?¤ì—???˜ì§‘???°ì´??
        
        // Step 2: ?œí—˜ ?¼ì •
        $examSchedule = $DB->get_record('mdl_alt42_exam_schedule', ['userid' => $studentid], '*', IGNORE_MISSING);
        if ($examSchedule) {
            $context['agent_data']['exam_schedule'] = [
                'exam_date' => $examSchedule->exam_date ?? null,
                'd_day' => $examSchedule->d_day ?? null,
                'exam_name' => $examSchedule->exam_name ?? ''
            ];
        }
        
        // Step 3: ëª©í‘œ ë¶„ì„
        $goalAnalysis = $DB->get_records('mdl_alt42g_goal_analysis', ['userid' => $studentid], 'created_at DESC', '*', 0, 5);
        if ($goalAnalysis) {
            $context['agent_data']['goal_analysis'] = array_values($goalAnalysis);
        }
        
        // Step 6: êµì‚¬ ?¼ë“œë°?
        $teacherFeedback = $DB->get_records_sql(
            "SELECT * FROM {abessi_todayplans} WHERE userid = ? ORDER BY timecreated DESC LIMIT 3",
            [$studentid]
        );
        if ($teacherFeedback) {
            $context['agent_data']['teacher_feedback'] = array_values($teacherFeedback);
        }
        
        // TODO: ?¤ë¥¸ ?ì´?„íŠ¸?¤ì˜ ?°ì´?°ë„ ?˜ì§‘ ?„ìš”
        // - Agent 01: ?¨ë³´???•ë³´
        // - Agent 04: ?œë™ ? í˜•
        // - Agent 05: ?™ìŠµ ê°ì •
        // - Agent 08: ì¹¨ì°©??
        // - Agent 09: ?™ìŠµ ê´€ë¦?
        // - Agent 10: ê°œë…?¸íŠ¸
        // - Agent 11: ë¬¸ì œ?¸íŠ¸
        // - Agent 12: ?´ì‹ ë£¨í‹´
        // - Agent 13: ?™ìŠµ ?´íƒˆ
        // - Agent 14: ?„ìž¬ ?„ì¹˜
        
    } catch (Exception $e) {
        error_log("Error in getProblemRedefinitionContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getProblemRedefinitionContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
