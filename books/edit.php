<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];

if($cntid==NULL)$scriptontopic='<span onclick="GPTTalk(\''.$gpteventname.'\',\'답변\',\''.$gpttalk.'\',\''.$contextid.'\',\''.$context.'\',\''.$url.'\',\''.$studentid.'\')"><img  style="margin-bottom:7px;" src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt3.png width=18></span> '.$gpttalk;

$wboardid = $_GET["wboardid"];
$gptlog=$DB->get_record_sql("SELECT * FROM mdl_abessi_gptultratalk where id='$cntid'  ORDER BY id DESC ");  

echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
<table width=100%><tr><td style="border: 1px solid black;"><iframe style="border: 1px none; z-index:2; width:60vw; height:100vh;  margin-left:-0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" ></iframe></td><td width=40% valign=top>
  <textarea id="mytextarea">
       '.$gptlog->gpttalk.'</textarea><table width=100%><tr><td>&nbsp;&nbsp;&nbsp;<button onclick="saveContent(\''.$cntid.'\')">저장하기</button> </td><td align=right><a href="https://chat.openai.com/chat"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt.png width=20></a>&nbsp;&nbsp;&nbsp;</td></tr></table></td></tr></table>
  <script>
    tinymce.init({
      selector: "textarea",
      plugins: "anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount ",
      toolbar: "undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table mergetags | addcomment showcomments | spellcheckdialog a11ycheck typography | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat",
      tinycomments_mode: "embedded",
      tinycomments_author: "Author name",
      mergetags_list: [
        { value: "First.Name", title: "First Name" },
        { value: "Email", title: "Email" },
      ]
    });
</script>

</body>
</html> 

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>';
 
echo '<script>   
function saveContent(Cntid)
  {
    var editor = tinymce.get("mytextarea");   
    var htmlContent = editor.getContent();
    var NewHtml = htmlContent;    
    NewHtml = NewHtml.replace(/(<([^>]+)>)/ig,"");
        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":\'1\',
              "cntid":Cntid,		 
              "inputtext":NewHtml, 
            },
            success:function(data){
                    var Cntid=data.cntid;
                    var Nexturl="https://mathking.kr/moodle/local/augmented_teacher/books/edit.php?cntid="+Cntid;
                    setTimeout(function(){window.open(Nexturl, \'_self\');} , 100);
                    }
             })
}
 
</script>
 
';

?>
