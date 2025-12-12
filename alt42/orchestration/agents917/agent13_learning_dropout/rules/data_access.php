<?php
/**
 * Agent 13 - Learning Dropout Data Provider
 * File: agent13_learning_dropout/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_abessi_today: ëª©í‘œ/ê²€???°ì´??(userid, ninactive, nlazy, activetime, checktime, status, type, timecreated) ??ì¡´ìž¬ ?•ì¸??
 * - mdl_abessi_messages: ë³´ë“œ/?¸íŠ¸ ?œë™ ?°ì´??(userid, timemodified, tlaststroke) ??ì¡´ìž¬ ?•ì¸??
 * - mdl_abessi_tracking: ?€?„ìŠ¤ìºí´???°ì´??(userid, status, timecreated, duration, text) ??ì¡´ìž¬ ?•ì¸??
 * - mdl_abessi_indicators: ?¬ëª¨?„ë¥´ ?”ì•½ ?°ì´??(userid, npomodoro, kpomodoro, pmresult, timecreated) ??ì¡´ìž¬ ?•ì¸??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getLearningDropoutContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'ninactive' => 0,
        'eye_count' => 0,
        'tlaststroke_min' => null,
        'npomodoro' => 0,
        'risk_tier' => 'Low',
        // ?˜í•™ ?¹í™” ?„ë“œ (?™ìƒ?¤ë¬¸/? ìƒ??ì²´í¬ë¦¬ìŠ¤?¸ì—???˜ì§‘)
        'current_math_unit' => null,
        'problem_difficulty' => null,
        'learning_stage' => null,
        'academy_class_understanding' => null,
        'academy_homework_burden' => null,
        'math_level' => null,
        'math_learning_style' => null
    ];
    
    try {
        $now = time();
        $windowStart = $now - 86400; // 24?œê°„ ë¡¤ë§ ?ˆë„??
        
        // 1) abessi_today: ìµœê·¼ ëª©í‘œ/ê²€????24h ??
        $goal = $DB->get_record_sql(
            "SELECT id, userid, ninactive, nlazy, activetime, checktime, status, type, timecreated, timemodified
             FROM {abessi_today}
             WHERE userid = ?
             AND timecreated >= ?
             AND (type = ? OR type = ?)
             ORDER BY id DESC
             LIMIT 1",
            [$studentid, $windowStart, '?¤ëŠ˜ëª©í‘œ', 'ê²€?¬ìš”ì²?]
        );
        
        if ($goal) {
            $context['ninactive'] = isset($goal->ninactive) ? intval($goal->ninactive) : 0;
            $context['nlazy_blocks'] = isset($goal->nlazy) ? intval(round(intval($goal->nlazy) / 20, 0)) : 0;
        }
        
        // 2) abessi_messages: ìµœê·¼ ë³´ë“œ/?¸íŠ¸ ?œë™(24h ??
        $msg = $DB->get_record_sql(
            "SELECT timemodified, tlaststroke
             FROM {abessi_messages}
             WHERE userid = ?
             AND timemodified >= ?
             ORDER BY tlaststroke DESC
             LIMIT 1",
            [$studentid, $windowStart]
        );
        
        if ($msg && isset($msg->timemodified)) {
            $timespentMin = intval(round(($now - intval($msg->timemodified)) / 60, 0));
            if ($timespentMin >= 5) {
                $context['eye_count'] = 1; // ì§€???œì²­ ?Œëž˜ê·?
            }
        }
        
        // 3) abessi_tracking: ìµœê·¼ ?€?„ìŠ¤ìºí´??
        $trk = $DB->get_record_sql(
            "SELECT status, timecreated, duration, text
             FROM {abessi_tracking}
             WHERE userid = ?
             ORDER BY id DESC
             LIMIT 1",
            [$studentid]
        );
        
        // 4) abessi_indicators: ?¬ëª¨?„ë¥´ ?”ì•½
        $ind = $DB->get_record_sql(
            "SELECT npomodoro, kpomodoro, pmresult, timecreated
             FROM {abessi_indicators}
             WHERE userid = ?
             ORDER BY id DESC
             LIMIT 1",
            [$studentid]
        );
        
        if ($ind) {
            $context['npomodoro'] = isset($ind->npomodoro) ? intval($ind->npomodoro) : 0;
        }
        
        // tlaststroke ê³„ì‚°: min(messages.tlaststroke, goal.timecreated, tracking.timecreated)
        $cands = [];
        if ($msg && isset($msg->tlaststroke) && intval($msg->tlaststroke) > 0) {
            $cands[] = intval($msg->tlaststroke);
        }
        if ($goal && isset($goal->timecreated) && intval($goal->timecreated) > 0) {
            $cands[] = intval($goal->timecreated);
        }
        if ($trk && isset($trk->timecreated) && intval($trk->timecreated) > 0) {
            $cands[] = intval($trk->timecreated);
        }
        
        if (!empty($cands)) {
            $tlaststrokeSec = $now - min($cands);
            $context['tlaststroke_min'] = intval(round($tlaststrokeSec / 60, 0));
        }
        
        // ?˜í•™ ?¹í™” ?„ë“œ ?˜ì§‘ (?™ìƒ?¤ë¬¸/? ìƒ??ì²´í¬ë¦¬ìŠ¤?¸ì—??ê°€?¸ì˜¤ê¸?
        // TODO: ?¤ì œ êµ¬í˜„ ??abessi_student_survey ?ëŠ” abessi_teacher_checklist ?Œì´ë¸”ì—??ì¡°íšŒ
        // ?„ìž¬??nullë¡?ì´ˆê¸°?”í•˜ê³? ë£??”ì§„?ì„œ ?˜ì§‘?˜ë„ë¡???
        
        // ?„í—˜ ?±ê¸‰ ?°ì • (ê¸°ë³¸)
        if (($context['ninactive'] >= 4) || ($context['npomodoro'] < 2) || ($context['tlaststroke_min'] !== null && $context['tlaststroke_min'] >= 30)) {
            $context['risk_tier'] = 'High';
        } elseif (($context['ninactive'] >= 2 && $context['ninactive'] <= 3) || ($context['npomodoro'] >= 2 && $context['npomodoro'] <= 4) || ($context['eye_count'] > 0)) {
            $context['risk_tier'] = 'Medium';
        } else {
            $context['risk_tier'] = 'Low';
        }
        
        // ?˜í•™ ?¹í™” ?„í—˜ ?±ê¸‰ ?í–¥ ì¡°ì •
        // ?´ë ¤???¨ì›(?¨ìˆ˜, ?„í˜•, ?´ì°¨)?ì„œ ?´íƒˆ ì¦ê? ??Medium ??High ?í–¥
        if ($context['risk_tier'] == 'Medium' && $context['current_math_unit'] !== null) {
            $difficultUnits = ['?¨ìˆ˜', '?„í˜•', '?´ì°¨'];
            foreach ($difficultUnits as $unit) {
                if (strpos($context['current_math_unit'], $unit) !== false && $context['ninactive'] >= 2) {
                    $context['risk_tier'] = 'High';
                    break;
                }
            }
        }
        
        // ?¬í™” ë¬¸ì œ?ì„œ ?´íƒˆ ì¦ê? ??Medium ??High ?í–¥
        if ($context['risk_tier'] == 'Medium' && $context['problem_difficulty'] == '?¬í™”' && $context['ninactive'] >= 2) {
            $context['risk_tier'] = 'High';
        }
        
        // ?™ì› ?˜ì—… ?´í•´ ëª»í•¨ + ?´íƒˆ ì¦ê? ??Medium ??High ?í–¥
        if ($context['risk_tier'] == 'Medium' && 
            in_array($context['academy_class_understanding'], ['ë°˜ë§Œ ?´í•´', 'ê±°ì˜ ?´í•´ ëª»í•¨']) && 
            $context['ninactive'] >= 2) {
            $context['risk_tier'] = 'High';
        }
        
    } catch (Exception $e) {
        error_log("Error in getLearningDropoutContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getLearningDropoutContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
