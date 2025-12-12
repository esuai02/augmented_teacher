<?php
/**
 * Agent 16 - Interaction Preparation Data Provider
 * File: agent16_interaction_preparation/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_agent16_interaction_scenarios: ?í˜¸?‘ìš© ?œë‚˜ë¦¬ì˜¤ ?Œì´ë¸???ì¡´ì¬ ?•ì¸??
 *   (id, userid, guide_mode, vibe_coding_prompt, db_tracking_prompt, scenario, created_at, updated_at)
 * - ?¤ë¥¸ ?ì´?„íŠ¸ ?°ì´?? Agent 01, 05, 13, 14, 15
 * - ?™ìƒ?¤ë¬¸ ?°ì´?? ?™ìŠµ ?¨ê³„, ?±ê³¼, ì·¨ì•½ ?¨ì› ??
 * - ? ìƒ??ì²´í¬ë¦¬ìŠ¤???ìŠ¤???…ë ¥ ?°ì´??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * ?í˜¸?‘ìš© ì¤€ë¹?ì»¨í…?¤íŠ¸ ?˜ì§‘
 * @param int $studentid ?™ìƒ ID
 * @return array ì»¨í…?¤íŠ¸ ?°ì´??
 */
function getInteractionPreparationContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'math_learning_stage' => null, // ê°œë…?™ìŠµ, ? í˜•?°ìŠµ, ?¬í™”, ê¸°ì¶œ
        'math_recent_accuracy' => null, // 0.0-1.0
        'unit_accuracy' => [], // ?¨ì›ë³??•ë‹µë¥?
        'student_level' => null, // ?˜ìœ„ê¶? ì¤‘ìœ„ê¶? ?ìœ„ê¶?
        'math_learning_style' => null, // ê³„ì‚°?? ê°œë…?? ?‘ìš©??
        'weak_units' => [], // ì·¨ì•½ ?¨ì› ëª©ë¡
        'academy_context' => [
            'pre_class_needed' => false,
            'post_class_needed' => false,
            'class_time' => null,
            'class_content' => null,
            'understanding_level' => null
        ],
        'problem_solving_status' => [
            'in_progress' => false,
            'stuck_detected' => false,
            'stuck_time_minutes' => 0,
            'calculation_error_frequency' => 0,
            'calculation_error_pattern' => null
        ],
        'previous_interaction' => [
            'worldview' => null,
            'narrative_theme' => null,
            'tone' => null,
            'character' => null
        ],
        'goals' => [],
        'teacher_notes' => [],
        'scenarios' => []
    ];
    
    try {
        // ?í˜¸?‘ìš© ?œë‚˜ë¦¬ì˜¤ ì¡°íšŒ
        if ($DB->get_manager()->table_exists(new xmldb_table('mdl_agent16_interaction_scenarios'))) {
            $scenarios = $DB->get_records(
                'mdl_agent16_interaction_scenarios',
                ['userid' => $studentid],
                'created_at DESC',
                '*',
                0,
                10
            );
            
            if ($scenarios) {
                foreach ($scenarios as $scenario) {
                    $context['scenarios'][] = [
                        'id' => $scenario->id,
                        'guide_mode' => $scenario->guide_mode ?? null,
                        'scenario' => $scenario->scenario ?? null,
                        'created_at' => $scenario->created_at ?? null
                    ];
                    
                    // ?´ì „ ?í˜¸?‘ìš© ?•ë³´ ì¶”ì¶œ (ê°€??ìµœê·¼ ê²?
                    if (empty($context['previous_interaction']['worldview']) && !empty($scenario->guide_mode)) {
                        $context['previous_interaction']['worldview'] = $scenario->guide_mode;
                    }
                }
            }
        }
        
        // Agent 01 ?¨ë³´???°ì´???°ê³„ (?˜í•™ ?™ìŠµ ?¤í???
        // TODO: Agent 01 ?°ì´???°ê³„ ë¡œì§ êµ¬í˜„
        // $agent01_data = getAgent01Data($studentid);
        // $context['math_learning_style'] = $agent01_data['math_learning_style'] ?? null;
        
        // Agent 05 ?™ìŠµ ê°ì • ?°ì´???°ê³„
        // TODO: Agent 05 ?°ì´???°ê³„ ë¡œì§ êµ¬í˜„
        // $agent05_data = getAgent05Data($studentid);
        // $context['learning_emotion'] = $agent05_data['emotion'] ?? null;
        
        // Agent 13 ?™ìŠµ ?´íƒˆ ?¨í„´ ?°ì´???°ê³„
        // TODO: Agent 13 ?°ì´???°ê³„ ë¡œì§ êµ¬í˜„
        // $agent13_data = getAgent13Data($studentid);
        // $context['dropout_pattern'] = $agent13_data['pattern'] ?? null;
        
        // Agent 14 ?„ì¬ ?„ì¹˜ ?°ì´???°ê³„
        // TODO: Agent 14 ?°ì´???°ê³„ ë¡œì§ êµ¬í˜„
        // $agent14_data = getAgent14Data($studentid);
        // $context['current_position'] = $agent14_data['position'] ?? null;
        
        // Agent 15 ë¬¸ì œ ?¬ì •???°ì´???°ê³„
        // TODO: Agent 15 ?°ì´???°ê³„ ë¡œì§ êµ¬í˜„
        // $agent15_data = getAgent15Data($studentid);
        // $context['problem_redefinition'] = $agent15_data['redefinition'] ?? null;
        
        // ë¬¸ì œ ?€??ë¡œê·¸ ì¡°íšŒ (?¤ì‹œê°??íƒœ ê°ì?)
        // TODO: ë¬¸ì œ ?€??ë¡œê·¸ ?Œì´ë¸?ì¡°íšŒ ë¡œì§ êµ¬í˜„
        // $problem_solving_log = getProblemSolvingLog($studentid);
        // if ($problem_solving_log) {
        //     $context['problem_solving_status']['in_progress'] = $problem_solving_log['in_progress'] ?? false;
        //     $context['problem_solving_status']['stuck_time_minutes'] = $problem_solving_log['stuck_time'] ?? 0;
        //     if ($context['problem_solving_status']['stuck_time_minutes'] >= 5) {
        //         $context['problem_solving_status']['stuck_detected'] = true;
        //     }
        // }
        
        // ?™ìƒ?¤ë¬¸ ?°ì´??ì¡°íšŒ (?™ìŠµ ?¨ê³„, ?±ê³¼ ??
        // TODO: ?™ìƒ?¤ë¬¸ ?°ì´???Œì´ë¸?ì¡°íšŒ ë¡œì§ êµ¬í˜„
        // $student_survey = getStudentSurveyData($studentid);
        // if ($student_survey) {
        //     $context['math_learning_stage'] = $student_survey['learning_stage'] ?? null;
        //     $context['math_recent_accuracy'] = $student_survey['recent_accuracy'] ?? null;
        //     $context['unit_accuracy'] = $student_survey['unit_accuracy'] ?? [];
        //     $context['weak_units'] = $student_survey['weak_units'] ?? [];
        //     $context['academy_context'] = $student_survey['academy_context'] ?? $context['academy_context'];
        // }
        
        // ? ìƒ??ì²´í¬ë¦¬ìŠ¤???ìŠ¤???…ë ¥ ?°ì´??ì¡°íšŒ
        // TODO: ? ìƒ???…ë ¥ ?°ì´???Œì´ë¸?ì¡°íšŒ ë¡œì§ êµ¬í˜„
        // $teacher_input = getTeacherInputData($studentid);
        // if ($teacher_input) {
        //     $context['teacher_notes'] = $teacher_input['notes'] ?? [];
        //     // ? ìƒ???…ë ¥ ?°ì´?°ê? ?ˆìœ¼ë©??°ì„  ?ìš©
        // }
        
        // ?™ìƒ ?˜ì? ?ë™ ê³„ì‚° (?•ë‹µë¥?ê¸°ë°˜)
        if (!empty($context['math_recent_accuracy'])) {
            $accuracy = floatval($context['math_recent_accuracy']);
            if ($accuracy < 0.5) {
                $context['student_level'] = '?˜ìœ„ê¶?;
            } elseif ($accuracy < 0.8) {
                $context['student_level'] = 'ì¤‘ìœ„ê¶?;
            } else {
                $context['student_level'] = '?ìœ„ê¶?;
            }
        }
        
        // ì·¨ì•½ ?¨ì› ?ë™ ?ë³„ (?¨ì›ë³??•ë‹µë¥?< 60%)
        if (!empty($context['unit_accuracy'])) {
            foreach ($context['unit_accuracy'] as $unit => $accuracy) {
                if (floatval($accuracy) < 0.6) {
                    if (!in_array($unit, $context['weak_units'])) {
                        $context['weak_units'][] = $unit;
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in getInteractionPreparationContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * ë£??¤í–‰???„í•œ ì»¨í…?¤íŠ¸ ì¤€ë¹?
 * @param int $studentid ?™ìƒ ID
 * @return array ë£?ì»¨í…?¤íŠ¸
 */
function prepareRuleContext($studentid) {
    $context = getInteractionPreparationContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    
    // ?°ì´??ê°€?? ?°ì´?°ê? ?†ëŠ” ê²½ìš° ê¸°ë³¸ê°??¤ì •
    // ?¤ì œ ?´ì˜ ?œì—???™ìƒ?¤ë¬¸ ?ëŠ” ? ìƒ???…ë ¥?¼ë¡œ ì±„ì›Œì§?
    
    if (empty($context['math_learning_stage'])) {
        // ê¸°ë³¸ê°? ?°ì´???†ìŒ ?œì‹œ (?¤ì œë¡œëŠ” ?™ìƒ?¤ë¬¸ ?”ì²­)
        $context['math_learning_stage'] = null; // ?™ìƒ?¤ë¬¸ ?„ìš”
    }
    
    if (empty($context['math_learning_style'])) {
        // ê¸°ë³¸ê°? ?°ì´???†ìŒ ?œì‹œ (?¤ì œë¡œëŠ” Agent 01 ?°ê³„ ?ëŠ” ?™ìƒ?¤ë¬¸ ?”ì²­)
        $context['math_learning_style'] = null; // Agent 01 ?°ê³„ ?ëŠ” ?™ìƒ?¤ë¬¸ ?„ìš”
    }
    
    return $context;
}

/**
 * ?í˜¸?‘ìš© ?¨ê³¼???°ì´???€??
 * @param int $studentid ?™ìƒ ID
 * @param string $worldview ? íƒ???¸ê³„ê´€
 * @param array $effectiveness_data ?¨ê³¼???°ì´??
 * @return bool ?€???±ê³µ ?¬ë?
 */
function saveInteractionEffectiveness($studentid, $worldview, $effectiveness_data) {
    global $DB;
    
    try {
        // TODO: ?í˜¸?‘ìš© ?¨ê³¼???Œì´ë¸??ì„± ë°??€??ë¡œì§ êµ¬í˜„
        // ?Œì´ë¸? mdl_agent16_interaction_effectiveness
        // ?„ë“œ: id, userid, worldview, effectiveness_score, learning_continuity, accuracy_improvement, created_at
        
        // ?¨ê³¼???°ì´??êµ¬ì¡°:
        // - effectiveness_score: 0.0-1.0 (?í˜¸?‘ìš© ?¨ê³¼???ìˆ˜)
        // - learning_continuity: ?™ìŠµ ì§€?ì„± (ë¶?
        // - accuracy_improvement: ?•ë‹µë¥??¥ìƒ (%)
        
        return true;
    } catch (Exception $e) {
        error_log("Error in saveInteractionEffectiveness: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return false;
    }
}

/**
 * ?™ìƒë³?? í˜¸ ?¸ê³„ê´€ ì¡°íšŒ
 * @param int $studentid ?™ìƒ ID
 * @return string|null ? í˜¸ ?¸ê³„ê´€
 */
function getPreferredWorldview($studentid) {
    global $DB;
    
    try {
        // TODO: ?í˜¸?‘ìš© ?¨ê³¼???°ì´??ê¸°ë°˜ ? í˜¸ ?¸ê³„ê´€ ë¶„ì„ ë¡œì§ êµ¬í˜„
        // ?¨ê³¼?±ì´ ?’ì? ?¸ê³„ê´€??? í˜¸ ?¸ê³„ê´€?¼ë¡œ ?ë³„
        
        return null;
    } catch (Exception $e) {
        error_log("Error in getPreferredWorldview: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        return null;
    }
}
