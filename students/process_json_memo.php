<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 로깅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON 응답 헤더 설정
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = array('success' => false);

try {
    // POST 데이터에서 JSON 받기
    $json_input = file_get_contents('php://input');
    
    if (empty($json_input)) {
        throw new Exception('JSON 데이터가 없습니다.');
    }
    
    // JSON 파싱
    $json_data = json_decode($json_input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON 파싱 오류: ' . json_last_error_msg());
    }
    
    // 필수 변수 추출
    $useraddcourse = isset($json_data['useraddcourse']) ? trim($json_data['useraddcourse']) : null;
    $usermathlevel = isset($json_data['usermathlevel']) ? trim($json_data['usermathlevel']) : null;
    $userprogresstype = isset($json_data['userprogresstype']) ? trim($json_data['userprogresstype']) : null;
    
    // 기본 메모 데이터 추출
    $userid = isset($json_data['userid']) ? intval($json_data['userid']) : null;
    $type = isset($json_data['type']) ? trim($json_data['type']) : 'today';
    $content = isset($json_data['content']) ? trim($json_data['content']) : null;
    $id = isset($json_data['id']) ? intval($json_data['id']) : 0;
    $created_at = isset($json_data['created_at']) ? intval($json_data['created_at']) : null;
    
    // 입력 데이터 검증
    if ($useraddcourse === null || $usermathlevel === null || $userprogresstype === null) {
        throw new Exception('필수 변수가 누락되었습니다. (useraddcourse, usermathlevel, userprogresstype)');
    }
    
    if ($userid === null) {
        throw new Exception('사용자 ID가 누락되었습니다.');
    }
    
    // 조건문에 따른 사용자 선택 및 내용 생성
    $selected_users = array();
    $generated_content = '';
    
    // 조건 1: 수학 과목이고 고급 레벨인 경우
    if ($useraddcourse === '수학' && $usermathlevel === '고급') {
        $selected_users[] = $userid;
        $generated_content = "고급 수학 과정 - {$userprogresstype} 진행중\n";
        $generated_content .= "과목: {$useraddcourse}\n";
        $generated_content .= "레벨: {$usermathlevel}\n";
        $generated_content .= "진행 형태: {$userprogresstype}\n";
        
        if ($content) {
            $generated_content .= "\n추가 내용:\n{$content}";
        }
    }
    // 조건 2: 심화학습 타입인 경우
    elseif ($userprogresstype === '심화학습') {
        $selected_users[] = $userid;
        $generated_content = "심화학습 과정 진행\n";
        $generated_content .= "과목: {$useraddcourse}\n";
        $generated_content .= "레벨: {$usermathlevel}\n";
        $generated_content .= "특별 프로그램: 심화학습\n";
        
        if ($content) {
            $generated_content .= "\n세부 내용:\n{$content}";
        }
    }
    // 조건 3: 기본 조건 - 모든 경우
    else {
        $selected_users[] = $userid;
        $generated_content = "학습 진행 상황\n";
        $generated_content .= "과목: {$useraddcourse}\n";
        $generated_content .= "레벨: {$usermathlevel}\n";
        $generated_content .= "진행 형태: {$userprogresstype}\n";
        
        if ($content) {
            $generated_content .= "\n내용:\n{$content}";
        }
    }
    
    // 허용된 타입 검증
    $allowed_types = array('timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today');
    if (!in_array($type, $allowed_types)) {
        $type = 'today'; // 기본값으로 설정
    }
    
    // 데이터베이스 테이블 존재 여부 확인
    $table_exists = $DB->get_manager()->table_exists('abessi_stickynotes');
    if (!$table_exists) {
        throw new Exception('데이터베이스 테이블이 존재하지 않습니다.');
    }
    
    $saved_records = array();
    $current_time = time();
    
    // 선택된 사용자들에게 메모 저장 (savememo.php 방식 적용)
    foreach ($selected_users as $target_userid) {
        
        if ($id == 0 || $id === null) {
            // 새로운 메모 생성
            $newmemo = new stdClass();
            $newmemo->userid = $target_userid;
            $newmemo->authorid = null;
            $newmemo->type = $type;
            $newmemo->content = $generated_content;
            $newmemo->created_at = $current_time;
            $newmemo->updated_at = $current_time;
            $newmemo->color = 'yellow';
            $newmemo->hide = 0;
            
            // JSON 메타데이터 추가
            $metadata = json_encode(array(
                'useraddcourse' => $useraddcourse,
                'usermathlevel' => $usermathlevel,
                'userprogresstype' => $userprogresstype,
                'source' => 'json_input'
            ));
            
            // 메타데이터를 content에 포함 (필요시)
            if (isset($json_data['include_metadata']) && $json_data['include_metadata']) {
                $newmemo->content .= "\n\n--- 메타데이터 ---\n" . $metadata;
            }
            
            // 삽입 전 데이터 로깅
            error_log("JSON 메모 삽입할 데이터: " . print_r($newmemo, true));
            
            try {
                $newid = $DB->insert_record('abessi_stickynotes', $newmemo);
                error_log("JSON 메모 삽입 성공. 새 ID: " . $newid);
                
                $saved_records[] = array(
                    'id' => $newid,
                    'userid' => $target_userid,
                    'action' => 'created',
                    'created_at' => $current_time
                );
                
            } catch (Exception $insert_error) {
                error_log("JSON 메모 삽입 실패: " . $insert_error->getMessage());
                throw new Exception('데이터베이스 쓰기 오류: ' . $insert_error->getMessage());
            }
            
        } else {
            // 기존 메모 업데이트 로직 (24시간 기준)
            $one_day_seconds = 24 * 60 * 60;
            $time_diff = $current_time - ($created_at ?: $current_time);
            
            if ($time_diff >= $one_day_seconds) {
                // 24시간 이상 지났으면 새로운 레코드 생성
                $newmemo = new stdClass();
                $newmemo->userid = $target_userid;
                $newmemo->authorid = null;
                $newmemo->type = $type;
                $newmemo->content = $generated_content;
                $newmemo->created_at = $current_time;
                $newmemo->updated_at = $current_time;
                $newmemo->color = 'yellow';
                $newmemo->hide = 0;
                
                $newid = $DB->insert_record('abessi_stickynotes', $newmemo);
                
                $saved_records[] = array(
                    'id' => $newid,
                    'userid' => $target_userid,
                    'action' => 'duplicated',
                    'created_at' => $current_time
                );
                
            } else {
                // 24시간 이내면 기존 레코드 업데이트
                $updatememo = new stdClass();
                $updatememo->id = $id;
                $updatememo->content = $generated_content;
                $updatememo->updated_at = $current_time;
                
                $result = $DB->update_record('abessi_stickynotes', $updatememo);
                
                $saved_records[] = array(
                    'id' => $id,
                    'userid' => $target_userid,
                    'action' => 'updated',
                    'created_at' => $created_at
                );
            }
        }
    }
    
    // 성공 응답
    $response['success'] = true;
    $response['message'] = '메모가 성공적으로 저장되었습니다.';
    $response['processed_data'] = array(
        'useraddcourse' => $useraddcourse,
        'usermathlevel' => $usermathlevel,
        'userprogresstype' => $userprogresstype,
        'selected_users' => $selected_users,
        'content_generated' => $generated_content
    );
    $response['saved_records'] = $saved_records;
    $response['timestamp'] = $current_time;
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    
    // 에러 로깅
    error_log("process_json_memo.php 에러: " . $e->getMessage());
    error_log("JSON 입력 데이터: " . $json_input);
    
    // 디버깅을 위한 상세 정보 (개발 환경에서만)
    if (defined('DEBUGGING') && DEBUGGING) {
        $response['debug'] = array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'input_data' => $json_input
        );
    }
}

// JSON 응답 전송
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?> 