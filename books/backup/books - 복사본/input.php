<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$srcid = $_GET["srcid"];
$wboardid = $_GET["wboardid"];
$ncnt = $_GET["ncnt"]; 
$contentsid = $_GET["contentsid"]; 

$thissrcid=$srcid.'ncnt'.$ncnt.'nstep';
$instruction=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitiveassessment WHERE contentsid='$contentsid' AND ncnt='$ncnt' ORDER BY id DESC LIMIT 1 "); 
 
$ncntstr='select'.$ncnt;
$$ncntstr='selected';
$title=$instruction->title;
if($instruction->step1!=NULL)$step1=$instruction->step1;
if($instruction->step2!=NULL)$step2=$instruction->step2;
if($instruction->step3!=NULL)$step3=$instruction->step3;
if($instruction->step4!=NULL)$step4=$instruction->step4;
if($instruction->step5!=NULL)$step5=$instruction->step5;
if($instruction->step6!=NULL)$step6=$instruction->step6;
if($instruction->step7!=NULL)$step7=$instruction->step7;

echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
<table width=100%><tr><td style="border: 1px solid black;"><iframe style="border: 1px none; z-index:2; width:60vw; height:100vh;  margin-left:-0px; margin-top: 0px; "  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" ></iframe></td><td width=40% valign=top><br><br>
    <table width=100%><tr><th width=10%></th><th width=60%></th></tr>
    <tr><td></td><td align=center>지시사항 '.$ncnt.' </td></tr>
    <tr><td align=center>1단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'1"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput1" name="squareInput1"  placeholder="지시사항을 입력해 주세요" value="'.$step1.'"></td></tr>
    <tr><td align=center>2단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'2"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput2" name="squareInput2"  placeholder="지시사항을 입력해 주세요" value="'.$step2.'"></td></tr>
    <tr><td align=center>3단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'3"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput3" name="squareInput3"  placeholder="지시사항을 입력해 주세요" value="'.$step3.'"></td></tr>
    <tr><td align=center>4단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'4"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput4" name="squareInput4"  placeholder="지시사항을 입력해 주세요" value="'.$step4.'"></td></tr>
    <tr><td align=center>5단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'5"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput5" name="squareInput5"  placeholder="지시사항을 입력해 주세요" value="'.$step5.'"></td></tr>
    <tr><td align=center>6단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'6"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput6" name="squareInput6"  placeholder="지시사항을 입력해 주세요" value="'.$step6.'"></td></tr>
    <tr><td align=center>7단계 <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$thissrcid.'7"target="_blank">#</a></td><td> <input type="text" class="form-control input-square" id="squareInput7" name="squareInput7"  placeholder="지시사항을 입력해 주세요" value="'.$step7.'"></td></tr>
    </tr></table><hr>
    <table width=100%><tr><th width=3%></th><th>활동추가</th><th width=3%></th><th><select id="basic1" name="basic" class="form-control"  ><h3><option value="">유형번호</option><option value="1" '.$select1.'>1</option><option value="2" '.$select2.'>2</option><option value="3" '.$select3.'>3</option><option value="4" '.$select4.'>4</option><option value="5" '.$select5.'>5</option><option value="6" '.$select6.'>6</option><option value="7" '.$select7.'>7</option></h3></select></th><th><input type="text" class="form-control input-square" id="title" name="title"  placeholder="제목입력" value="'.$title.'"></td><th>&nbsp;</th><th>&nbsp;&nbsp;<button type="button" onclick="saveinstructions(\''.$srcid.'\',\''.$wboardid.'\',\''.$contentsid.'\',1,$(\'#basic1\').val(),$(\'#title\').val(),$(\'#squareInput1\').val(),$(\'#squareInput2\').val(),$(\'#squareInput3\').val(),$(\'#squareInput4\').val(),$(\'#squareInput5\').val(),$(\'#squareInput6\').val(),$(\'#squareInput7\').val())">
    <img src="http://mathking.kr/Contents/Moodle/save.gif" width=20></a></button>&nbsp;</th><th>&nbsp;<button type="button" onclick="removesteps(\''.$srcid.'\',\''.$contentsid.'\',1,\''.$nstep.'\') "><a href="https://mathking.kr/moodle/local/augmented_teacher/students/index.php?id='.$userid.'"><img src="http://mathking.kr/Contents/IMAGES/delete.png" width=20></a></button></th><th></th></tr></table>
    </td></tr></table>
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
function saveinstructions(Srcid,Wboardid,Contentsid,Contentstype,Ncnt,Title,Step1,Step2,Step3,Step4,Step5,Step6,Step7)
		    {	
 		   // alert(Ncnt);
		        $.ajax({
                url: "check_status.php",
			    type: "POST",
		        dataType:"json",
 			    data : {
                    "eventid":\'3\',
                    "srcid":Srcid,
                    "wboardid":Wboardid,
                    "contentsid":Contentsid,
                    "contentstype":Contentstype,
                    "ncnt":Ncnt,
                    "title":Title,
                    "step1":Step1,
                    "step2":Step2,
                    "step3":Step3,
                    "step4":Step4,
                    "step5":Step5,
                    "step6":Step6,
                    "step7":Step7,	
		               },
		            success:function(data){
                  window.location=data.url;
                }
		        }) 

		}
$("#basic1").select2({
    theme: "bootstrap"
});
</script>
 
';

?>
