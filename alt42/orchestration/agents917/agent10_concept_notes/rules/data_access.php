<?php
/**
 * Agent 10 - Concept Notes Data Provider
 * File: agent10_concept_notes/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_abessi_messages: ê°œë…?¸íŠ¸(?”ì´?¸ë³´???„ê¸°) ?°ì´????ì¡´ìž¬ ?•ì¸??
 *   - contentstype=1: ê°œë…ê³µë? ?”ì´?¸ë³´??
 *   - ?„ë“œ: nstroke(ì´??„ê¸°??, tlaststroke(ë§ˆì?ë§??„ê¸°?œì ), timecreated(?ì„±?œê°), 
 *           usedtime(?¸íŠ¸???¬ìš©??ì´??œê°„), contentstitle(ê°œë… ?œëª©), url
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getConceptNotesContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'note_statistics' => [],
        'concept_coverage' => [],
        'recent_notes' => []
    ];
    
    try {
        // ìµœê·¼ 1ì£¼ì¼ ê°œë…?¸íŠ¸ ?°ì´??ì¡°íšŒ (contentstype=1)
        $oneWeekAgo = time() - (7 * 24 * 60 * 60);
        
        $records = $DB->get_records_sql(
            "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, url, usedtime
             FROM {abessi_messages}
             WHERE contentstype = ?
             AND userid = ?
             AND timecreated >= ?
             ORDER BY timecreated DESC",
            [1, $studentid, $oneWeekAgo]
        );
        
        if ($records) {
            $totalNotes = count($records);
            $totalStrokes = 0;
            $totalUsedTime = 0;
            $conceptTitles = [];
            
            foreach ($records as $rec) {
                $noteData = [
                    'id' => $rec->id,
                    'nstroke' => isset($rec->nstroke) ? intval($rec->nstroke) : 0,
                    'tlaststroke' => isset($rec->tlaststroke) ? intval($rec->tlaststroke) : null,
                    'timecreated' => isset($rec->timecreated) ? intval($rec->timecreated) : null,
                    'contentstitle' => isset($rec->contentstitle) ? (string)$rec->contentstitle : '',
                    'usedtime' => isset($rec->usedtime) ? intval($rec->usedtime) : 0
                ];
                
                $context['recent_notes'][] = $noteData;
                
                $totalStrokes += $noteData['nstroke'];
                $totalUsedTime += $noteData['usedtime'];
                
                if (!empty($noteData['contentstitle'])) {
                    $conceptTitles[$noteData['contentstitle']] = ($conceptTitles[$noteData['contentstitle']] ?? 0) + 1;
                }
            }
            
            $context['note_statistics'] = [
                'total_notes' => $totalNotes,
                'total_strokes' => $totalStrokes,
                'average_strokes' => $totalNotes > 0 ? round($totalStrokes / $totalNotes, 1) : 0,
                'total_used_time' => $totalUsedTime,
                'average_used_time' => $totalNotes > 0 ? round($totalUsedTime / $totalNotes, 1) : 0
            ];
            
            $context['concept_coverage'] = $conceptTitles;
        }
        
    } catch (Exception $e) {
        error_log("Error in getConceptNotesContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getConceptNotesContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
