<?php
/**
 * Agent 11 - Problem Notes Data Provider
 * File: agent11_problem_notes/rules/data_access.php
 * 
 * ?°ì´???ŒìŠ¤:
 * - mdl_abessi_messages: ë¬¸ì œ?¸íŠ¸ ?°ì´????ì¡´ìž¬ ?•ì¸??
 *   - contentstype=2: ë¬¸ì œ?€???¸íŠ¸
 *   - status ?„ë“œë¡?ë¶„ë¥˜:
 *     - 'attempt': ?€?´ë…¸??(?œí—˜ ì¤??‘ì„±?˜ëŠ” ?¤ì œ ?€???¸íŠ¸)
 *     - 'begin': ì¤€ë¹„ë…¸??(?¤ë‹µ ë°œìƒ ???´ì„¤ì§€?€ ?¨ê»˜ ê³µë??˜ëŠ” ?¸íŠ¸)
 *     - 'exam', 'complete', 'review': ?œìˆ ?‰ê? (ì¤€ë¹„ë…¸???„ë£Œ ???¬í???
 *   - ?„ë“œ: nstroke, tlaststroke, timecreated, usedtime, contentstitle, wboardid, status
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

function getProblemNotesContext($studentid) {
    global $DB;
    
    $context = [
        'student_id' => $studentid,
        'error_patterns' => [],
        'note_statistics' => [],
        'attempt_notes' => [],
        'preparation_notes' => [],
        'essay_assessments' => []
    ];
    
    try {
        // ìµœê·¼ 1ì£¼ì¼ ë¬¸ì œ?¸íŠ¸ ?°ì´??ì¡°íšŒ (contentstype=2)
        $oneWeekAgo = time() - (7 * 24 * 60 * 60);
        
        // 1. ?€?´ë…¸??(status='attempt')
        $attemptNotes = $DB->get_records_sql(
            "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, wboardid, usedtime, status
             FROM {abessi_messages}
             WHERE contentstype = ?
             AND userid = ?
             AND status = ?
             AND timecreated >= ?
             ORDER BY timecreated DESC",
            [2, $studentid, 'attempt', $oneWeekAgo]
        );
        
        // 2. ì¤€ë¹„ë…¸??(status='begin')
        $preparationNotes = $DB->get_records_sql(
            "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, wboardid, usedtime, status
             FROM {abessi_messages}
             WHERE contentstype = ?
             AND userid = ?
             AND status = ?
             AND timecreated >= ?
             ORDER BY timecreated DESC",
            [2, $studentid, 'begin', $oneWeekAgo]
        );
        
        // 3. ?œìˆ ?‰ê? (status IN ('exam', 'complete', 'review'))
        $essayAssessments = $DB->get_records_sql(
            "SELECT id, userid, nstroke, tlaststroke, timecreated, contentstitle, wboardid, usedtime, status
             FROM {abessi_messages}
             WHERE contentstype = ?
             AND userid = ?
             AND status IN (?, ?, ?)
             AND timecreated >= ?
             ORDER BY timecreated DESC",
            [2, $studentid, 'exam', 'complete', 'review', $oneWeekAgo]
        );
        
        // ?°ì´??ë³€??ë°??µê³„ ê³„ì‚°
        $formatNotes = function($records) {
            $formatted = [];
            foreach ($records as $rec) {
                $formatted[] = [
                    'id' => $rec->id,
                    'nstroke' => isset($rec->nstroke) ? intval($rec->nstroke) : 0,
                    'tlaststroke' => isset($rec->tlaststroke) ? intval($rec->tlaststroke) : null,
                    'timecreated' => isset($rec->timecreated) ? intval($rec->timecreated) : null,
                    'contentstitle' => isset($rec->contentstitle) ? (string)$rec->contentstitle : '',
                    'usedtime' => isset($rec->usedtime) ? intval($rec->usedtime) : 0,
                    'status' => isset($rec->status) ? (string)$rec->status : ''
                ];
            }
            return $formatted;
        };
        
        $context['attempt_notes'] = $formatNotes($attemptNotes);
        $context['preparation_notes'] = $formatNotes($preparationNotes);
        $context['essay_assessments'] = $formatNotes($essayAssessments);
        
        // ?µê³„ ê³„ì‚°
        $context['note_statistics'] = [
            'attempt_count' => count($attemptNotes),
            'preparation_count' => count($preparationNotes),
            'essay_count' => count($essayAssessments),
            'total_count' => count($attemptNotes) + count($preparationNotes) + count($essayAssessments)
        ];
        
        // ?¤ë‹µ ?¨í„´ ë¶„ì„ (ì¤€ë¹„ë…¸?¸ê? ë§Žìœ¼ë©??¤ë‹µ??ë§Žë‹¤???˜ë?)
        if (count($preparationNotes) > 0) {
            $context['error_patterns'][] = [
                'type' => 'preparation_notes_high',
                'count' => count($preparationNotes),
                'message' => '?¤ë‹µ ??ì¤€ë¹„ë…¸???‘ì„±???œë°œ??
            ];
        }
        
    } catch (Exception $e) {
        error_log("Error in getProblemNotesContext: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }
    
    return $context;
}

function prepareRuleContext($studentid) {
    $context = getProblemNotesContext($studentid);
    $context['timestamp'] = date('Y-m-d\TH:i:s\Z');
    return $context;
}
