<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 로깅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

// POST 데이터 검증 및 받기
$id = isset($_POST['id']) ? intval($_POST['id']) : null;
$userid = isset($_POST['userid']) ? intval($_POST['userid']) : null;
$type = isset($_POST['type']) ? trim($_POST['type']) : null;
$content = isset($_POST['content']) ? trim($_POST['content']) : null;
$created_at = isset($_POST['created_at']) ? intval($_POST['created_at']) : null;

$response = array('success' => false);

// 입력 데이터 검증
if ($userid === null || $type === null || $content === null) {
    $response['error'] = '필수 데이터가 누락되었습니다.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

if (empty($content)) {
    $response['error'] = '메모 내용이 비어있습니다.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// 허용된 타입 검증
$allowed_types = array('timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today');
if (!in_array($type, $allowed_types)) {
    $response['error'] = '유효하지 않은 메모 타입입니다.';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    $current_time = time();
    
    // 데이터베이스 테이블 존재 여부 확인
    $table_exists = $DB->get_manager()->table_exists('abessi_stickynotes');
    if (!$table_exists) {
        throw new Exception('데이터베이스 테이블이 존재하지 않습니다.');
    }
    
    if ($id == 0 || $id === null) {
        // 새로운 메모 생성
        $newmemo = new stdClass();
        $newmemo->userid = $userid;  // NOT NULL 필드
        $newmemo->authorid = null;   // NULL 허용 필드
        $newmemo->type = $type;
        $newmemo->content = $content;
        $newmemo->created_at = $current_time;
        $newmemo->updated_at = $current_time;
        $newmemo->color = 'yellow'; // 기본 색상
        $newmemo->hide = 0; // 기본값
        
        // 삽입 전 데이터 로깅
        error_log("삽입할 데이터: " . print_r($newmemo, true));
        
        try {
            $newid = $DB->insert_record('abessi_stickynotes', $newmemo);
            error_log("삽입 성공. 새 ID: " . $newid);
        } catch (Exception $insert_error) {
            error_log("삽입 실패: " . $insert_error->getMessage());
            throw new Exception('데이터베이스 쓰기 오류: ' . $insert_error->getMessage());
        }
        
        if ($newid) {
            $response['success'] = true;
            $response['id'] = $newid;
            $response['created_at'] = $current_time;
            $response['action'] = 'created';
        } else {
            throw new Exception('새 메모 생성에 실패했습니다.');
        }
        
    } else {
        // 기존 메모가 있는 경우
        $one_day_seconds = 24 * 60 * 60; // 24시간을 초로 변환
        $time_diff = $current_time - $created_at;
        
        if ($time_diff >= $one_day_seconds) {
            // 24시간 이상 지났으면 새로운 레코드 생성
            $newmemo = new stdClass();
            $newmemo->userid = $userid;  // NOT NULL 필드
            $newmemo->authorid = null;   // NULL 허용 필드
            $newmemo->type = $type;
            $newmemo->content = $content;
            $newmemo->created_at = $current_time;
            $newmemo->updated_at = $current_time;
            $newmemo->color = 'yellow'; // 기본 색상
            $newmemo->hide = 0; // 기본값
            
            // 삽입 전 데이터 로깅
            error_log("복사 삽입할 데이터: " . print_r($newmemo, true));
            
            try {
                $newid = $DB->insert_record('abessi_stickynotes', $newmemo);
                error_log("복사 삽입 성공. 새 ID: " . $newid);
            } catch (Exception $insert_error) {
                error_log("복사 삽입 실패: " . $insert_error->getMessage());
                throw new Exception('데이터베이스 쓰기 오류: ' . $insert_error->getMessage());
            }
            
            if ($newid) {
                $response['success'] = true;
                $response['id'] = $newid;
                $response['created_at'] = $current_time;
                $response['action'] = 'duplicated';
            } else {
                throw new Exception('새 메모 생성에 실패했습니다.');
            }
            
        } else {
            // 24시간 이내면 기존 레코드 업데이트
            $updatememo = new stdClass();
            $updatememo->id = $id;
            $updatememo->content = $content;
            $updatememo->updated_at = $current_time; // 수정 시간 업데이트
            
            // 업데이트 전 데이터 로깅
            error_log("업데이트할 데이터: " . print_r($updatememo, true));
            
            try {
                $result = $DB->update_record('abessi_stickynotes', $updatememo);
                error_log("업데이트 결과: " . ($result ? 'success' : 'failed'));
            } catch (Exception $update_error) {
                error_log("업데이트 실패: " . $update_error->getMessage());
                throw new Exception('데이터베이스 쓰기 오류: ' . $update_error->getMessage());
            }
            
            if ($result) {
                $response['success'] = true;
                $response['id'] = $id;
                $response['created_at'] = $created_at; // 생성 시간은 유지
                $response['action'] = 'updated';
            } else {
                throw new Exception('메모 업데이트에 실패했습니다.');
            }
        }
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    
    // 에러 로깅
    error_log("savememo.php 에러: " . $e->getMessage());
    error_log("POST 데이터: " . print_r($_POST, true));
    
    // 디버깅을 위한 상세 정보 (개발 환경에서만)
    if (defined('DEBUGGING') && DEBUGGING) {
        $response['debug'] = array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        );
    }
}

// JSON 응답 전송
header('Content-Type: application/json');
echo json_encode($response);
?> 