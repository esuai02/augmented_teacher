<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$teacherid = required_param('teacherid', PARAM_INT);

try {
    // 디버깅 로그
    error_log("get_new_requests.php - teacherid: $teacherid, USER->id: {$USER->id}");
    
    // 테이블 존재 확인
    if (!$DB->get_manager()->table_exists('ktm_teaching_interactions')) {
        throw new Exception('ktm_teaching_interactions 테이블이 존재하지 않습니다.');
    }
    
    // 권한 확인 - 본인이거나 관리자만 접근 가능
    $context = context_system::instance();
    if ($teacherid != $USER->id && !has_capability('moodle/site:config', $context)) {
        throw new Exception('접근 권한이 없습니다.');
    }

    // 새로운 풀이요청 가져오기 
    // 1. 선생님이 지정되어 있고 아직 처리되지 않은 요청
    // 2. 또는 선생님이 지정되지 않은 새로운 요청
    // 3. status가 NULL이거나 빈 문자열인 경우도 포함
    // 4. type='askhint'인 경우 problem_image 없어도 표시 (contentsid로 이미지 조회 가능)
    $sql = "SELECT ti.*, u.firstname, u.lastname
            FROM {ktm_teaching_interactions} ti
            JOIN {user} u ON ti.userid = u.id
            WHERE (
                LOWER(TRIM(COALESCE(ti.status, ''))) IN ('pending', 'processing', 'new', 'received', '')
                OR ti.status IS NULL
            )
            AND LOWER(TRIM(COALESCE(ti.status, ''))) NOT IN ('completed', 'complete', 'sent', 'finished', 'done')
            AND (
                ti.teacherid = ? 
                OR ti.teacherid = 0 
                OR ti.teacherid IS NULL
            )
            AND (ti.solution_text IS NULL OR ti.solution_text = '' OR LENGTH(TRIM(ti.solution_text)) = 0)
            AND (
                (ti.problem_image IS NOT NULL AND ti.problem_image != '')
                OR ti.type = 'askhint'
            )
            AND ti.timecreated > ?
            ORDER BY ti.timecreated DESC";
    
    $params = array(
        $teacherid,
        time() - (7 * 24 * 3600) // 7일 전 (24시간에서 확장)
    );
    
    error_log("get_new_requests.php - SQL: " . $sql);
    error_log("get_new_requests.php - Params: " . json_encode($params));
    
    try {
        // Moodle의 표준 방식으로 LIMIT 적용
        $requests = $DB->get_records_sql($sql, $params, 0, 20);
        error_log("get_new_requests.php - Found " . count($requests) . " requests");
        
        // 디버깅: 각 요청의 상태 확인
        foreach ($requests as $req) {
            error_log("Request ID: {$req->id}, Status: {$req->status}, Solution: " . (empty($req->solution_text) ? 'empty' : 'exists'));
        }
    } catch (dml_exception $e) {
        error_log("get_new_requests.php - SQL Error: " . $e->getMessage());
        error_log("get_new_requests.php - SQL: " . $sql);
        error_log("get_new_requests.php - Params: " . json_encode($params));
        throw new Exception('데이터베이스 읽기 오류: ' . $e->getMessage());
    }
    
    // 결과 포맷팅
    $results = array();
    foreach ($requests as $request) {
        // modification_prompt 필드 확인 (있으면 사용)
        $modificationPrompt = '';
        if (isset($request->modification_prompt) && !empty($request->modification_prompt)) {
            $modificationPrompt = $request->modification_prompt;
        }
        
        // problem_image 처리: 없고 type='askhint'인 경우 contentsid로 조회
        $problemImage = $request->problem_image ?? '';
        if (empty($problemImage) && $request->type === 'askhint' && !empty($request->contentsid)) {
            try {
                // contentstype=2 (문제 유형)인 경우 mdl_question에서 이미지 추출
                if ($request->contentstype == 2) {
                    $qtext = $DB->get_record_sql("SELECT questiontext FROM {question} WHERE id=? LIMIT 1", array($request->contentsid));
                    if ($qtext && !empty($qtext->questiontext)) {
                        $htmlDom = new DOMDocument;
                        @$htmlDom->loadHTML($qtext->questiontext);
                        $imageTags = $htmlDom->getElementsByTagName('img');
                        foreach ($imageTags as $imageTag) {
                            $imgSrc = $imageTag->getAttribute('src');
                            $imgSrc = str_replace(' ', '%20', $imgSrc);
                            if (strpos($imgSrc, 'hintimages') === false && (strpos($imgSrc, '.png') !== false || strpos($imgSrc, '.jpg') !== false)) {
                                $problemImage = $imgSrc;
                                break;
                            }
                        }
                    }
                }
                error_log("get_new_requests.php - askhint problemImage from contentsid {$request->contentsid}: $problemImage");
            } catch (Exception $imgError) {
                error_log("get_new_requests.php - Error getting problem image: " . $imgError->getMessage());
            }
        }
        
        $results[] = array(
            'id' => $request->id,
            'studentId' => $request->userid,
            'studentName' => fullname($request),
            'problemType' => $request->problem_type ?? '',
            'problemImage' => $problemImage,
            'problemText' => $request->problem_text ?? '',
            'additionalRequest' => $modificationPrompt,
            'isReRequest' => false,     // 재요청 기능 비활성화
            'reRequestReason' => '',
            'previousSolution' => $request->solution_text ?? '',
            'status' => $request->status ?? 'pending',
            'type' => $request->type ?? '',  // askhint 등 요청 타입
            'contentsid' => $request->contentsid ?? null,  // contentsid 추가
            'contentstype' => $request->contentstype ?? null,  // contentstype 추가
            'timecreated' => $request->timecreated,
            'timeAgo' => time_ago($request->timecreated)
        );
    }

    error_log("get_new_requests.php - Returning " . count($results) . " formatted results");
    
    echo json_encode(array(
        'success' => true,
        'requests' => $results,
        'total' => count($results),
        'debug' => array(
            'teacherid' => $teacherid,
            'user_id' => $USER->id,
            'sql_params' => $params
        )
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