<?php
// 출력 버퍼링 시작 (JSON 응답 전에 모든 출력 방지)
ob_start();

include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

// 이전 출력 모두 제거
ob_clean();

header('Content-Type: application/json; charset=utf-8');

$path = "./imagefiles/";  // 이미지 파일 저장 경로
$valid_formats = array("jpg", "jpeg", "png", "gif", "bmp");  // 유효한 이미지 형식
$data = array(); 
$data['success'] = false;

// 디렉토리 존재 확인 및 생성
if (!is_dir($path)) {
    if (!mkdir($path, 0755, true)) {
        ob_clean();
        $data['error'] = '업로드 디렉토리 생성 실패 [파일: upload_question_image.php, 위치: 디렉토리 생성]';
        die(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
}

$userid = isset($_POST['userid']) ? intval($_POST['userid']) : $USER->id;

// 권한 확인 (학생 본인 또는 선생님만 가능)
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

if ($role === 'student' && $USER->id != $userid) {
    ob_clean();
    $data['error'] = '권한이 없습니다. [파일: upload_question_image.php, 위치: 권한 확인]';
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
}

if (isset($_POST) && $_SERVER['REQUEST_METHOD'] == "POST") {
    if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        ob_clean();
        $data['error'] = '이미지 파일이 업로드되지 않았습니다. [파일: upload_question_image.php, 위치: 파일 업로드 확인]';
        die(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    $name = $_FILES['image']['name'];
    $size = $_FILES['image']['size'];
    
    if (strlen($name)) {
        // 파일 확장자 추출
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        if (in_array($ext, $valid_formats)) {
            if ($size < (1024 * 1024 * 5)) { // 5MB 제한
                $unixtime = time();
                
                // 파일명 형식: qstn_userid_NN_unixtime_TT.jpg
                // NN은 사용자 아이디, TT는 unixtime
                $actual_image_name = 'qstn_userid_' . $userid . '_' . $unixtime . '_' . $unixtime . '.' . $ext;
                $tmp = $_FILES['image']['tmp_name'];
                
                if (move_uploaded_file($tmp, $path . $actual_image_name)) {
                    ob_clean();
                    $data['success'] = true;
                    $data['filename'] = $actual_image_name;
                    $data['url'] = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/imagefiles/' . $actual_image_name;
                    die(json_encode($data, JSON_UNESCAPED_UNICODE));
                } else {
                    ob_clean();
                    $data['error'] = '파일 업로드 실패 [파일: upload_question_image.php, 위치: 파일 이동]';
                    die(json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            } else {
                ob_clean();
                $data['error'] = '이미지 파일 크기는 최대 5MB까지 가능합니다. [파일: upload_question_image.php, 위치: 파일 크기 검증]';
                die(json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        } else {
            ob_clean();
            $data['error'] = '지원하지 않는 파일 형식입니다. (JPG, PNG, GIF만 가능) [파일: upload_question_image.php, 위치: 파일 형식 검증]';
            die(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
    } else {
        ob_clean();
        $data['error'] = '파일명이 없습니다. [파일: upload_question_image.php, 위치: 파일명 확인]';
        die(json_encode($data, JSON_UNESCAPED_UNICODE));
    }
} else {
    ob_clean();
    $data['error'] = '잘못된 요청 방법입니다. [파일: upload_question_image.php, 위치: 요청 방법 확인]';
    die(json_encode($data, JSON_UNESCAPED_UNICODE));
}
?>

