<?php
/**
 * Agent 03 - Goals Analysis Data Provider
 * File: agent03_goals_analysis/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_alt42g_goal_analysis: ëª©í‘œ ë¶„ì„ ?Œì´ë¸?(userid, analysis_type, analysis_result, created_at ?? ??ì¡´ì¬ ?•ì¸??
 * - mdl_alt42g_student_goals: ?™ìƒ ëª©í‘œ ?Œì´ë¸?(userid, goal_type, status, progress ?? ??ì¡´ì¬ ?•ì¸??
 * - mdl_alt42g_learning_sessions: ?™ìŠµ ?¸ì…˜ ê¸°ë¡ ?Œì´ë¸???ì¡´ì¬ ?•ì¸??
 * - mdl_alt42g_pomodoro_sessions: ?¬ëª¨?„ë¥´ ?¸ì…˜ ?Œì´ë¸???ì¡´ì¬ ?•ì¸??
 * - mdl_alt42g_curriculum_progress: ì»¤ë¦¬?˜ëŸ¼ ì§„í–‰???Œì´ë¸???ì¡´ì¬ ?•ì¸??
 * - mdl_alt42g_completed_units: ?„ë£Œ???¨ì› ?Œì´ë¸???ì¡´ì¬ ?•ì¸??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * ëª©í‘œ ë¶„ì„ ?°ì´???˜ì§‘
 * 
 * @param int $studentid ?™ìƒ ID
 * @return array ëª©í‘œ ë¶„ì„ ì»¨í…?¤íŠ¸ ?°ì´??
 */
function getGoalsAnalysisContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'goals' => [],
        'completion_rate' => null,
        'category_balance' => [],
        'average_duration' => null
    ];
    
    try {
        // ëª©í‘œ ?°ì´??ì¡°íšŒ (mdl_alt42g_student_goals ?Œì´ë¸?
        $goals = $DB->get_records_sql(
            "SELECT * FROM {alt42g_student_goals} WHERE userid = ? ORDER BY timecreated DESC LIMIT 10",
            [$studentid]
        );
        
        if ($goals) {
            $totalGoals = count($goals);
            $completedGoals = 0;
            $totalDuration = 0;
            $categoryCount = [];
            
            foreach ($goals as $goal) {
                $goalData = [
                    'id' => $goal->id,
                    'goal_type' => $goal->goal_type ?? null,
                    'status' => $goal->status ?? null,
                    'progress' => isset($goal->progress) ? floatval($goal->progress) : 0
                ];
                
                if (isset($goal->status) && $goal->status === 'completed') {
                    $completedGoals++;
                }
                
                if (isset($goal->goal_type)) {
                    $categoryCount[$goal->goal_type] = ($categoryCount[$goal->goal_type] ?? 0) + 1;
                }
                
                $context['goals'][] = $goalData;
            }
            
            $context['completion_rate'] = $totalGoals > 0 ? round(($completedGoals / $totalGoals) * 100, 1) : 0;
            $context['category_balance'] = $categoryCount;
        }
        
        // ìµœê·¼ ëª©í‘œ ë¶„ì„ ê²°ê³¼ ì¡°íšŒ (mdl_alt42g_goal_analysis ?Œì´ë¸?
        $latestAnalysis = $DB->get_record_sql(
            "SELECT * FROM {alt42g_goal_analysis} WHERE userid = ? ORDER BY created_at DESC LIMIT 1",
            [$studentid]
        );
        
        if ($latestAnalysis) {
            $context['latest_analysis'] = [
                'analysis_type' => $latestAnalysis->analysis_type ?? null,
                'analysis_result' => $latestAnalysis->analysis_result ?? null,
                'effectiveness_score' => isset($latestAnalysis->effectiveness_score) ? floatval($latestAnalysis->effectiveness_score) : null
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error in getGoalsAnalysisContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ë£??‰ê?ë¥??„í•œ ì»¨í…?¤íŠ¸ ì¤€ë¹?
 */
function prepareRuleContext($studentid) {
    $context = getGoalsAnalysisContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
