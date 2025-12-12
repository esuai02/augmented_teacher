<?php
/**
 * Agent 14 - Current Position Data Provider
 * File: agent14_current_position/rules/data_access.php
 * 
 * ?�이???�스:
 * - mdl_abessi_todayplans: ?�학?�기 ?�이??(userid, tbegin, plan1-16, due1-16, tend01-16, status01-16, timecreated) ??존재 ?�인??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getCurrentPositionContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'completion_rate' => null,
        'progress_status' => null,
        'emotion_distribution' => [],
        'delayed_items' => []
    ];
    
    try {
        // 최근 12?�간 ?�내 ?�학?�기 ?�이??조회
        $twelveHoursAgo = time() - 43200;
        
        $diaryRecord = $DB->get_record_sql(
            "SELECT * FROM {abessi_todayplans}
             WHERE userid = ?
             AND timecreated >= ?
             ORDER BY timecreated DESC
             LIMIT 1",
            [$studentid, $twelveHoursAgo]
        );
        
        if ($diaryRecord) {
            $entries = [];
            $totalPlanned = 0;
            $totalCompleted = 0;
            $delayedItems = 0;
            $onTimeItems = 0;
            $earlyItems = 0;
            
            $satisfactionScores = [
                '매우만족' => 0,
                '만족' => 0,
                '불만�? => 0
            ];
            
            // ?�작 ?�간 계산 (tbegin 기�?)
            $baseTime = $diaryRecord->tbegin;
            $currentTime = $baseTime;
            
            for ($i = 1; $i <= 16; $i++) {
                $planField = 'plan' . $i;
                $dueField = 'due' . $i;
                $statusField = 'status' . str_pad($i, 2, '0', STR_PAD_LEFT);
                $tendField = 'tend' . str_pad($i, 2, '0', STR_PAD_LEFT);
                
                $planText = isset($diaryRecord->$planField) ? $diaryRecord->$planField : '';
                $duration = isset($diaryRecord->$dueField) ? intval($diaryRecord->$dueField) : 0;
                $status = isset($diaryRecord->$statusField) ? $diaryRecord->$statusField : '';
                $tend = isset($diaryRecord->$tendField) ? intval($diaryRecord->$tendField) : null;
                
                if (empty($planText)) {
                    continue;
                }
                
                $totalPlanned += $duration;
                $expectedEnd = $currentTime + ($duration * 60);
                
                if ($tend !== null && $tend > 0) {
                    $totalCompleted++;
                    $delay = round(($tend - $expectedEnd) / 60);
                    
                    if ($delay > 30) {
                        $delayedItems++;
                    } elseif ($delay >= -30 && $delay <= 30) {
                        $onTimeItems++;
                    } else {
                        $earlyItems++;
                    }
                    
                    // 만족??집계
                    if (array_key_exists($status, $satisfactionScores)) {
                        $satisfactionScores[$status]++;
                    }
                }
                
                $currentTime = $expectedEnd;
            }
            
            $context['completion_rate'] = count($entries) > 0 ? round(($totalCompleted / count($entries)) * 100, 1) : 0;
            $context['delayed_items'] = $delayedItems;
            $context['emotion_distribution'] = $satisfactionScores;
            
            // 진행 ?�태 결정
            if ($delayedItems > $onTimeItems + $earlyItems) {
                $context['progress_status'] = '지??;
            } elseif ($earlyItems > $delayedItems + $onTimeItems) {
                $context['progress_status'] = '?�활';
            } else {
                $context['progress_status'] = '?�절';
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in getCurrentPositionContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getCurrentPositionContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
