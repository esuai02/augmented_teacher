<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 

$studentid = $_POST['studentid'];
$contentsid = $_POST['contentsid'];
$print = $_POST['print'];

$path = "./uploadimages/";  // 이미지 파일을 저장할 경로
$valid_formats = array("jpg", "png", "gif", "bmp", "jpeg");  // 유효한 이미지 형식
$data = array(); 
$data['success'] = false;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role; 

if(isset($_FILES['image']['name'])) {
    $name = $_FILES['image']['name'];  
    $size = $_FILES['image']['size'];

    if(strlen($name)) {       
        list($txt, $ext) = explode(".", $name);
        $ext = strtolower($ext);
        if(in_array($ext, $valid_formats)) {
            if($size < (1024 * 1024 * 5)) { // 이미지 사이즈 제한을 5 MB로 설정
                $actual_image_name = 'cnt_'.$contentsid.'_IMG'.$print.'.'.$ext; // 파일명 설정 변경
                $file_path = $path . $actual_image_name;

                // 파일이 이미 존재하는지 확인합니다.
                if (file_exists($file_path) && $role==='student') {
                    //$data['error'] = "File already exists.";
                    $data['url'] = 'https://mathking.kr/moodle/local/augmented_teacher/LLM/uploadimages/'.$actual_image_name;
                } else {
                    $tmp = $_FILES['image']['tmp_name'];
                    if(move_uploaded_file($tmp, $file_path)) {       
                        $data['success'] = true;
                        $data['url'] = 'https://mathking.kr/moodle/local/augmented_teacher/LLM/uploadimages/'.$actual_image_name;
                    } else {
                        $data['error'] = "Upload error";
                    }
                }
            } else {
                $data['error'] = "Image file size max 5 MB";
            }
        } else {
            $data['error'] = "Invalid file format.";
        }
    } else {
        $data['error'] = "Please select an image file.";
    }
}

// JSON 형태로 결과 반환
echo json_encode($data);
?>
