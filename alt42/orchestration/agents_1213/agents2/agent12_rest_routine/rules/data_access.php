<?php
/**
 * Agent 12 - Rest Routine Data Provider
 * File: agent12_rest_routine/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_abessi_breaktimelog: ?´ì‹ ë²„íŠ¼ ?´ë¦­ ?°ì´??(userid, duration, timecreated) ??ì¡´ìž¬ ?•ì¸??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getRestRoutineContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'rest_patterns' => [],
        'average_interval' => null,
        'rest_type' => null,
        'rest_count' => 0
    ];
    
    try {
        // ìµœê·¼ 30?¼ê°„ ?´ì‹ ?°ì´??ì¡°íšŒ
        $thirtyDaysAgo = time() - (30 * 86400);
        
        $records = $DB->get_records_sql(
            "SELECT userid, duration, timecreated
             FROM {abessi_breaktimelog}
             WHERE userid = ?
             AND timecreated >= ?
             ORDER BY timecreated ASC",
            [$studentid, $thirtyDaysAgo]
        );
        
        if ($records && count($records) > 0) {
            $context['rest_count'] = count($records);
            
            $intervals = [];
            $prevEndTime = null;
            
            // ?´ì‹ ê°„ê²© ê³„ì‚°
            foreach ($records as $record) {
                if ($prevEndTime !== null) {
                    // ?´ì „ ?´ì‹ ì¢…ë£Œ ?œê°„ë¶€???¤ìŒ ?´ì‹ ?œìž‘ê¹Œì???ê°„ê²© (ë¶??¨ìœ„)
                    $interval = ($record->timecreated - $prevEndTime) / 60;
                    $intervals[] = $interval;
                }
                // ?„ìž¬ ?´ì‹??ì¢…ë£Œ ?œê°„
                $prevEndTime = $record->timecreated + $record->duration;
            }
            
            if (!empty($intervals)) {
                $avgInterval = array_sum($intervals) / count($intervals);
                $context['average_interval'] = round($avgInterval, 1);
                
                // ?¨í„´ ? í˜• ë¶„ë¥˜
                if ($avgInterval <= 60) {
                    $context['rest_type'] = '?•ê¸°???´ì‹??;
                } elseif ($avgInterval <= 90) {
                    $context['rest_type'] = '?œë™ ì¤‘ì‹¬ ?´ì‹??;
                } else {
                    $context['rest_type'] = 'ì§‘ì¤‘ ëª°ìž…??;
                }
                
                $context['rest_patterns'] = [
                    'min_interval' => round(min($intervals), 1),
                    'max_interval' => round(max($intervals), 1),
                    'interval_count' => count($intervals)
                ];
            } else {
                $context['rest_type'] = '?´ì‹ ë¯¸ì‚¬?©í˜•';
            }
        } else {
            $context['rest_type'] = '?´ì‹ ë¯¸ì‚¬?©í˜•';
        }
        
    } catch (Exception $e) {
        error_log("Error in getRestRoutineContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getRestRoutineContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
