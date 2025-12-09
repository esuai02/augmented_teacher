<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$path = "./audiofiles/";
$valid_formats = array("wav", "mp3", "ogg", "m4a", "flac", "webm", "mp4");
$data = array(); 
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$userid = $_POST['userid'];
$data['success'] = false;
$hostname = $_SERVER["HTTP_HOST"];

if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST") {
    $name = $_FILES['audio']['name'];
    $size = $_FILES['audio']['size'];
    
    if(strlen($name)) {       
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if(in_array($ext, $valid_formats)) {
            if($size < (1024*1024*100)) { // Audio size max 100 MB
                if($contentsid == NULL) {
                    $actual_audio_name = $name;
                } else {
                    $actual_audio_name = 'recording_'.$userid.'_'.time().'.'.$ext;
                }
                
                $tmp = $_FILES['audio']['tmp_name'];
                if(move_uploaded_file($tmp, $path.$actual_audio_name)) {       
                    $data['success'] = true;
                    $data['url'] = "/audiofiles/".$actual_audio_name;   
                    
                    // DB에 녹음 정보 저장
                    $audiourl = 'https://mathking.kr/moodle/local/augmented_teacher/LLM/audiofiles/'.$actual_audio_name;
                    $timecreated = time();
                    $text = '녹음 파일 - '.date('Y-m-d H:i:s', $timecreated);
                    
                    $record = new stdClass();
                    $record->userid = $userid;
                    $record->type = 'recording';
                    $record->text = $text;
                    $record->fileurl = $audiourl;
                    $record->hide = 0;
                    $record->timecreated = $timecreated;
                    $record->timemodified = $timecreated;
                    
                    $DB->insert_record('abessi_mathtalk', $record);
                    
                } else {
                    $data['success'] = false;
                    $data['error'] = "Upload error";
                }
            } else {
                $data['error'] = "Audio file size max 100 MB";
            }
        } else {
            $data['error'] = "Invalid file format.";
        }
    } else {
        $data['error'] = "Please select an audio file.";
    }
}

die(json_encode($data));
?> 