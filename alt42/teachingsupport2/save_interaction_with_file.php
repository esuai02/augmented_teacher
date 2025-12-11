<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

header('Content-Type: application/json');

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);

$action = $input['action'] ?? '';

try {
    $time = time();
    
    switch($action) {
        case 'create_interaction':
            // 새로운 상호작용 레코드 생성
            $interaction = new stdClass();
            $interaction->userid = (int)($input['studentId'] ?? $USER->id);
            $interaction->teacherid = (int)($input['teacherId'] ?? 0);
            $interaction->problem_type = $input['problemType'] ?? '';
            $interaction->problem_text = $input['problemText'] ?? '';
            $interaction->modification_prompt = $input['modificationPrompt'] ?? '';
            $interaction->status = 'pending';
            $interaction->timecreated = $time;
            $interaction->timemodified = $time;
            
            // 이미지 처리
            if (!empty($input['problemImage'])) {
                // base64 이미지 데이터 처리
                $image_data = $input['problemImage'];
                
                // data:image/png;base64, 부분 제거
                if (strpos($image_data, 'data:') === 0) {
                    list($type, $data) = explode(';', $image_data);
                    list(, $data) = explode(',', $data);
                    $image_binary = base64_decode($data);
                    
                    // 파일 확장자 결정
                    $extension = 'png';
                    if (strpos($type, 'jpeg') !== false || strpos($type, 'jpg') !== false) {
                        $extension = 'jpg';
                    } elseif (strpos($type, 'gif') !== false) {
                        $extension = 'gif';
                    }
                    
                    // 파일 저장 디렉토리 (Moodle 데이터 디렉토리 사용)
                    $upload_dir = $CFG->dataroot . '/ktm_teaching/images/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // 고유한 파일명 생성
                    $filename = 'problem_' . $interaction->userid . '_' . $time . '_' . uniqid() . '.' . $extension;
                    $filepath = $upload_dir . $filename;
                    
                    // 파일 저장
                    if (file_put_contents($filepath, $image_binary)) {
                        // DB에는 파일명만 저장
                        $interaction->problem_image = $filename;
                    } else {
                        throw new Exception('이미지 파일 저장 실패');
                    }
                } else {
                    // 이미 처리된 경로인 경우
                    $interaction->problem_image = $image_data;
                }
            } else {
                $interaction->problem_image = '';
            }
            
            // DB에 저장
            try {
                $interaction_id = $DB->insert_record('ktm_teaching_interactions', $interaction);
                
                if (!$interaction_id) {
                    throw new Exception('레코드 생성 실패');
                }
                
                echo json_encode([
                    'success' => true,
                    'interactionId' => $interaction_id,
                    'debug' => [
                        'studentId' => $interaction->userid,
                        'teacherId' => $interaction->teacherid,
                        'problemType' => $interaction->problem_type,
                        'imageSaved' => !empty($interaction->problem_image)
                    ]
                ]);
                
            } catch (Exception $e) {
                // 파일 삭제 (DB 저장 실패 시)
                if (isset($filepath) && file_exists($filepath)) {
                    unlink($filepath);
                }
                throw $e;
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => '지원되지 않는 액션']);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'debug_info' => [
            'action' => $action,
            'user_id' => $USER->id
        ]
    ]);
}
?>