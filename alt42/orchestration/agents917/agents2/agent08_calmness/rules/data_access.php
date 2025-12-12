<?php
/**
 * Agent 08 - Calmness Data Provider
 * File: agent08_calmness/rules/data_access.php
 * 
 * ?�이???�스:
 * - mdl_abessi_today: 침착??계산 ?�이??(userid, type, score, timecreated ?? ??존재 ?�인??
 *   - type='?�늘목표' ?�는 '검?�요�????�코?�의 score ?�드가 침착??지?�로 ?�용??
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

/**
 * 침착???�이???�집
 * 
 * @param int $studentid ?�생 ID
 * @return array 침착??컨텍?�트 ?�이??
 */
function getCalmnessContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'calm_score' => null,
        'baseline_calm' => null,
        'calm_trend' => null,
        'factors' => []
    ];
    
    try {
        // ?�재 침착???�이??(최근 12?�간)
        $current = $DB->get_record_sql(
            "SELECT * FROM {abessi_today}
             WHERE userid = ?
             AND (type = ? OR type = ?)
             AND timecreated > ?
             ORDER BY id DESC
             LIMIT 1",
            [
                $studentid,
                '?�늘목표',
                '검?�요�?,
                time() - 43200
            ]
        );
        
        if ($current && isset($current->score)) {
            $context['calm_score'] = floatval($current->score);
        }
        
        // 주간 기�???(주간목표??score)
        $baseline = $DB->get_record_sql(
            "SELECT * FROM {abessi_today}
             WHERE userid = ?
             AND type = ?
             ORDER BY id DESC
             LIMIT 1",
            [
                $studentid,
                '주간목표'
            ]
        );
        
        if ($baseline && isset($baseline->score)) {
            $context['baseline_calm'] = floatval($baseline->score);
            
            // 추이 분석
            if ($context['calm_score'] !== null) {
                $delta = $context['calm_score'] - $context['baseline_calm'];
                if ($delta >= 5) {
                    $context['calm_trend'] = 'improving';
                } elseif ($delta <= -5) {
                    $context['calm_trend'] = 'declining';
                } else {
                    $context['calm_trend'] = 'stable';
                }
            }
        }
        
        // 최근 7???�이??(추이 분석??
        $recentRecords = $DB->get_records_sql(
            "SELECT * FROM {abessi_today}
             WHERE userid = ?
             AND (type = ? OR type = ?)
             AND timecreated >= ?
             ORDER BY timecreated DESC
             LIMIT 30",
            [
                $studentid,
                '?�늘목표',
                '검?�요�?,
                time() - (7 * 86400)
            ]
        );
        
        if ($recentRecords) {
            $scores = [];
            foreach ($recentRecords as $record) {
                if (isset($record->score)) {
                    $scores[] = floatval($record->score);
                }
            }
            
            if (!empty($scores)) {
                $context['factors']['recent_average'] = round(array_sum($scores) / count($scores), 1);
                $context['factors']['recent_min'] = min($scores);
                $context['factors']['recent_max'] = max($scores);
                $context['factors']['data_count'] = count($scores);
            }
        }
        
    } catch (Exception $e) {
        error_log("Error in getCalmnessContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

/**
 * �??��?�??�한 컨텍?�트 준�?
 */
function prepareRuleContext($studentid) {
    $context = getCalmnessContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
