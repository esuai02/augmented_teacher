<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$studentid = required_param('studentid', PARAM_INT);

try {
    // 학생이 보낸 요청 가져오기
    $sql = "SELECT ti.*, u.firstname, u.lastname
            FROM {ktm_teaching_interactions} ti
            LEFT JOIN {user} u ON ti.teacherid = u.id
            WHERE ti.userid = ?
            ORDER BY ti.timecreated DESC
            LIMIT 20";
    
    $requests = $DB->get_records_sql($sql, array($studentid));
    
    // 결과 포맷팅
    $results = array();
    foreach ($requests as $request) {
        // 상태별 레이블
        $statusLabel = '';
        $statusColor = '';
        switch($request->status) {
            case 'pending':
                $statusLabel = '대기중';
                $statusColor = '#fbbf24';
                break;
            case 'processing':
                $statusLabel = '처리중';
                $statusColor = '#3b82f6';
                break;
            case 'analyzing':
                $statusLabel = '분석중';
                $statusColor = '#8b5cf6';
                break;
            case 'completed':
            case 'complete':
                $statusLabel = '완료';
                $statusColor = '#10b981';
                break;
            case 'sent':
                $statusLabel = '전송됨';
                $statusColor = '#059669';
                break;
            default:
                $statusLabel = $request->status;
                $statusColor = '#6b7280';
        }
        
        // wboardid 조회 (ktm_teaching_interactions 테이블에서 직접 가져오기)
        $wboardid = $request->wboardid ?? null;
        
        // wboardid가 없으면 abessi_messages에서 조회 시도
        if (empty($wboardid)) {
            try {
                $wbRecord = $DB->get_record_sql(
                    "SELECT wboardid FROM {abessi_messages} 
                     WHERE contentsid = ? 
                     AND contentstype = 2 
                     AND userid = ? 
                     AND active = 1 
                     AND wboardid IS NOT NULL 
                     AND wboardid != ''
                     ORDER BY timemodified DESC 
                     LIMIT 1",
                    array($request->id, $studentid)
                );
                
                if ($wbRecord && !empty($wbRecord->wboardid)) {
                    $wboardid = $wbRecord->wboardid;
                }
            } catch (Exception $e) {
                // 조회 실패 시 무시
                error_log('[get_sent_requests.php] File: ' . __FILE__ . ', Line: ' . __LINE__ . ', wboardid 조회 오류: ' . $e->getMessage());
            }
        }
        
        // 화이트보드 URL 생성
        $whiteboardUrl = '';
        if (!empty($wboardid)) {
            $whiteboardUrl = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id=' . $wboardid;
        }
        
        $results[] = array(
            'id' => $request->id,
            'teacherId' => $request->teacherid,
            'teacherName' => $request->teacherid ? fullname($request) : '미지정',
            'problemType' => $request->problem_type,
            'problemImage' => $request->problem_image,
            'status' => $request->status,
            'statusLabel' => $statusLabel,
            'statusColor' => $statusColor,
            'modificationPrompt' => $request->modification_prompt ?? '',
            'hasSolution' => !empty($request->solution_text),
            'hasAudio' => !empty($request->audio_url),
            'type' => $request->type ?? '',  // askhint 등 요청 타입
            'timecreated' => $request->timecreated,
            'timeAgo' => time_ago($request->timecreated),
            'wboardid' => $wboardid,
            'whiteboardUrl' => $whiteboardUrl
        );
    }

    echo json_encode(array(
        'success' => true,
        'requests' => $results,
        'total' => count($results)
    ));

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}

// 시간 경과 계산 함수
function time_ago($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return '방금 전';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '분 전';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '시간 전';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . '일 전';
    } else {
        return date('Y-m-d H:i', $timestamp);
    }
}
?>