<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$path = "/home/moodle/public_html/audiofiles/";
$valid_formats = array( "wav","mp3", "ogg", "m4a", "flac");
$data   = array(); 
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$data['success'] = false;
$hostname = $_SERVER["HTTP_HOST"];
if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
{
    $name = $_FILES['audio']['name'];
    $size = $_FILES['audio']['size'];
    if(strlen($name))
    {       
        list($txt, $ext) = explode(".", $name);
        if(in_array($ext,$valid_formats))
        {
            if($size < ( 1024*1024*100 )) // Audio size max 10 MB
            {
                if($contentsid==NULL)
                {
                    $actual_audio_name = $name;
                }
                else
                {
                    $actual_audio_name = 'cid'.$contentsid.'ct'.$contentstype.'_audio.'.$ext;
                }
                $tmp = $_FILES['audio']['tmp_name'];
                if(move_uploaded_file($tmp, $path.$actual_audio_name))
                {       
                    $data['success'] = true;
                    $data['url']  = $actual_audio_name;   
                }
                else
                {
                    $data['success'] = false;
                    $data['error'] = "Upload error";
                }
            }
            else
                $data['error'] = "Audio file size max 50 MB";
        }
        else
            $data['error'] = "Invalid file format.";
    }
    else
        $data['error'] = "Please select an audio file.";
    }
$audiourl='https://mathking.kr/audiofiles/'.$data['url']; 
if($contentstype==2)$DB->execute("UPDATE {question} SET audiourl='$audiourl'  WHERE id='$contentsid'  ORDER BY id DESC LIMIT 1 ");
else $DB->execute("UPDATE {icontent_pages} SET audiourl='$audiourl'  WHERE id='$contentsid'  ORDER BY id DESC LIMIT 1 ");  

die(json_encode($data));
?>
