<?php
/**
 * 화이트보드 ID 조회 API
 * contentsid, contentstype, studentid로 active=1인 레코드의 wboardid 조회
 */
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// 파라미터 받기
$contentsid_raw = $_GET['contentsid'] ?? $_GET['cid'] ?? '';
$contentsid = !empty($contentsid_raw) ? intval($contentsid_raw) : 0;
$contentstype = intval($_GET['contentstype'] ?? $_GET['ctype'] ?? 2);
$studentid = intval($_GET['studentid'] ?? $_GET['userid'] ?? $USER->id);
$questionNumber = intval($_GET['questionNumber'] ?? $_GET['qnum'] ?? 0); // 질문 번호 (0이면 메인 화이트보드)

// 필수 파라미터 검증
if (empty($contentsid) || $contentsid <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'contentsid가 필요합니다.',
        'debug' => [
            'received_contentsid' => $contentsid_raw,
            'received_contentstype' => $_GET['contentstype'] ?? $_GET['ctype'] ?? 'N/A',
            'received_studentid' => $_GET['studentid'] ?? $_GET['userid'] ?? 'N/A'
        ]
    ]);
    exit;
}

try {
    $wboardid = null;
    
    if ($questionNumber > 0) {
        // 질문별 화이트보드 조회 (stepquiz 형식)
        $expectedPattern = 'stepquiz_q' . $questionNumber . '_' . $contentsid . '_user' . $studentid;
        
        $wbRecord = $DB->get_record_sql(
            "SELECT wboardid FROM {abessi_messages} 
             WHERE contentsid = ? 
             AND contentstype = ? 
             AND userid = ? 
             AND active = 1 
             AND wboardid LIKE ?
             ORDER BY timemodified DESC LIMIT 1",
            [$contentsid, $contentstype, $studentid, $expectedPattern . '%']
        );
        
        if ($wbRecord && !empty($wbRecord->wboardid)) {
            $wboardid = $wbRecord->wboardid;
        }
    } else {
        // 메인 화이트보드 조회
        // active=1인 레코드 우선 조회
        $wbRecord = $DB->get_record_sql(
            "SELECT wboardid FROM {abessi_messages} 
             WHERE contentsid = ? 
             AND contentstype = ? 
             AND userid = ? 
             AND active = 1 
             ORDER BY timemodified DESC LIMIT 1",
            [$contentsid, $contentstype, $studentid]
        );
        
        if ($wbRecord && !empty($wbRecord->wboardid)) {
            $wboardid = $wbRecord->wboardid;
        } else {
            // active=1인 레코드가 없으면 active 조건 없이 조회
            $wbRecord = $DB->get_record_sql(
                "SELECT wboardid FROM {abessi_messages} 
                 WHERE contentsid = ? 
                 AND contentstype = ? 
                 AND userid = ? 
                 ORDER BY timemodified DESC LIMIT 1",
                [$contentsid, $contentstype, $studentid]
            );
            
            if ($wbRecord && !empty($wbRecord->wboardid)) {
                $wboardid = $wbRecord->wboardid;
            }
        }
    }
    
    if (empty($wboardid)) {
        // 디버깅: 실제 DB에 있는 레코드 확인
        $debugRecords = [];
        try {
            // contentsid로만 조회 (모든 레코드 확인)
            $allByContentsid = $DB->get_records_sql(
                "SELECT id, wboardid, contentsid, contentstype, userid, active, timemodified 
                 FROM {abessi_messages} 
                 WHERE contentsid = ? 
                 ORDER BY timemodified DESC LIMIT 10",
                [$contentsid]
            );
            
            foreach ($allByContentsid as $rec) {
                $debugRecords[] = [
                    'id' => $rec->id,
                    'wboardid' => $rec->wboardid ?? 'NULL',
                    'contentstype' => $rec->contentstype ?? 'NULL',
                    'userid' => $rec->userid ?? 'NULL',
                    'active' => $rec->active ?? 'NULL',
                    'timemodified' => $rec->timemodified ?? 'NULL'
                ];
            }
            
            // contentstype과 userid로도 조회
            $allByTypeAndUser = $DB->get_records_sql(
                "SELECT id, wboardid, contentsid, contentstype, userid, active, timemodified 
                 FROM {abessi_messages} 
                 WHERE contentstype = ? 
                 AND userid = ? 
                 ORDER BY timemodified DESC LIMIT 10",
                [$contentstype, $studentid]
            );
            
            $debugRecordsByTypeAndUser = [];
            foreach ($allByTypeAndUser as $rec) {
                $debugRecordsByTypeAndUser[] = [
                    'id' => $rec->id,
                    'wboardid' => $rec->wboardid ?? 'NULL',
                    'contentsid' => $rec->contentsid ?? 'NULL',
                    'active' => $rec->active ?? 'NULL'
                ];
            }
            
        } catch (Exception $debugError) {
            error_log('[get_whiteboard_id.php] 디버그 레코드 조회 오류: ' . $debugError->getMessage());
        }
        
        error_log(sprintf(
            '[get_whiteboard_id.php] File: %s, Line: %d, 화이트보드 조회 실패 - contentsid=%d, contentstype=%d, studentid=%d, found_by_contentsid=%d, found_by_type_user=%d',
            basename(__FILE__),
            __LINE__,
            $contentsid,
            $contentstype,
            $studentid,
            count($debugRecords),
            count($debugRecordsByTypeAndUser ?? [])
        ));
        
        echo json_encode([
            'success' => false,
            'error' => '화이트보드를 찾을 수 없습니다.',
            'debug' => [
                'contentsid' => $contentsid,
                'contentstype' => $contentstype,
                'studentid' => $studentid,
                'questionNumber' => $questionNumber,
                'records_by_contentsid' => $debugRecords,
                'records_by_type_and_user' => $debugRecordsByTypeAndUser ?? []
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'wboardid' => $wboardid,
            'contentsid' => $contentsid,
            'contentstype' => $contentstype,
            'studentid' => $studentid
        ]);
    }
    
} catch (Exception $e) {
    error_log(sprintf(
        '[get_whiteboard_id.php] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));
    
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류: ' . $e->getMessage()
    ]);
}
?>
