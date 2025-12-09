<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$path = "./imagefiles/";  // 오디오 파일 대신 이미지 파일을 저장할 경로
$valid_formats = array("jpg", "png", "gif", "bmp", "jpeg");  // 유효한 이미지 형식
$data = array(); 
$contentsid = $_POST['contentsid'];
$data['success'] = false;
$hostname = $_SERVER["HTTP_HOST"];
if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
{ 
    $name = $_FILES['image']['name'];  // 'audio' 대신 'image'를 사용
    $size = $_FILES['image']['size'];
    if(strlen($name)) 
    {       
        list($txt, $ext) = explode(".", $name);
        if(in_array($ext,$valid_formats))
        {
            if($size < (1024*1024*5)) // 이미지 사이즈 제한을 5 MB로 설정
            {
                $actual_image_name = 'MATHuser_'.$USER->id.'_cnt'.$contentsid.'_'.time().'_IMG.'.$ext;
                $tmp = $_FILES['image']['tmp_name'];
                if(move_uploaded_file($tmp, $path.$actual_image_name))
                {       
                    $data['success'] = true;
                    $data['url'] = "/imagefiles/".$actual_image_name;   
                }
                else
                {
                    $data['success'] = false;
                    $data['error'] = "Upload error";
                }
            }
            else
                $data['error'] = "Image file size max 5 MB";
        }
        else
            $data['error'] = "Invalid file format.";
    }
    else
        $data['error'] = "Please select an image file.";
}
$imageurl = 'https://mathking.kr/moodle/local/augmented_teacher/books'.$data['url'];
// DB 업데이트 부분은 오디오와 동일한 로직을 유지합니다.
//$DB->execute("UPDATE {icontent_pages} SET pageicontent='$imageurl' WHERE id='$contentsid' ORDER BY id DESC LIMIT 1 ");  

$thiscnt=$DB->get_record_sql("SELECT pageicontent FROM mdl_icontent_pages where id='$contentsid'  ORDER BY id DESC LIMIT 1");
$currentimgurl = preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $thiscnt->pageicontent, $matches) ? $matches[1] : '';

if($currentimgurl !== '') 
    {
    $DB->execute("UPDATE {icontent_pages} SET pageicontent = REPLACE(pageicontent, '$currentimgurl', '$imageurl') WHERE id = '$contentsid'");
    }
else 
    {
    $imgsrc='<p><img src='.$imageurl.'></p>';
    $DB->execute("UPDATE {icontent_pages} SET pageicontent = '$imgsrc'  WHERE id = '$contentsid'");
    }
die(json_encode($data));
?>
