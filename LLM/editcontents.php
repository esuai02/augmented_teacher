<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$studentid = $_GET["studentid"]; 
$eventid=4;

$cnttext=$DB->get_record_sql("SELECT * FROM mdl_abessi_adaptivecontents where contentsid='$cntid' AND contentstype='$cnttype'  ORDER BY id DESC LIMIT 1");  

$cnttext1=$cnttext->cnttext1;
$cnttext2=$cnttext->cnttext2;
$cnttext3=$cnttext->cnttext3;
$cnttext4=$cnttext->cnttext4;
$cnttext5=$cnttext->cnttext5;
 
echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body><br><br><br>
<table align=center width=90%> 
<tr><td>&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>컨텐츠 제작 GPT</button></a>  </td><td width=4%></td> <td><button onclick="saveContent(\''.$eventid.'\',\''.$cnttype.'\',\''.$cntid.'\')">저장하기</button></td></tr>
<tr><td style="border: 1px solid black;" width=48% valign=top><br><br><table align=center width=90%><tr><td>1'.$cnttext1.'</td></tr></table></td><td width=4% align=center><a style="font-size:25px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=1" target="_blank">📝</a></td><td width=48% valign=top><textarea id="mytextarea1">'.$cnttext1.'</textarea> </td></tr>
<tr><td style="border: 1px solid black;" width=48% valign=top><br><br><table align=center width=90%><tr><td>2'.$cnttext2.'</td></tr></table></td><td width=4% align=center><a style="font-size:25px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=2" target="_blank">📝</a></td><td width=48% valign=top><textarea id="mytextarea2">'.$cnttext2.'</textarea> </td></tr>
<tr><td style="border: 1px solid black;" width=48% valign=top><br><br><table align=center width=90%><tr><td>3'.$cnttext3.'</td></tr></table></td><td width=4% align=center><a style="font-size:25px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=3" target="_blank">📝</a></td><td width=48% valign=top><textarea id="mytextarea3">'.$cnttext3.'</textarea> </td></tr>
<tr><td style="border: 1px solid black;" width=48% valign=top><br><br><table align=center width=90%><tr><td>4'.$cnttext4.'</td></tr></table></td><td width=4% align=center><a style="font-size:25px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=4" target="_blank">📝</a></td><td width=48% valign=top><textarea id="mytextarea4">'.$cnttext4.'</textarea> </td></tr>
<tr><td style="border: 1px solid black;" width=48% valign=top><br><br><table align=center width=90%><tr><td>5'.$cnttext5.'</td></tr></table></td><td width=4% align=center><a style="font-size:25px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=5" target="_blank">📝</a></td><td width=48% valign=top><textarea id="mytextarea5">'.$cnttext5.'</textarea> </td></tr>
</table>

<script type="text/x-mathjax-config">
MathJax.Hub.Config({
  tex2jax: {
    inlineMath:[ ["$","$"], ["\\[","\\]"] ],
   // displayMath: [ ["$","$"], ["\\[","\\]"] ]
  }
});
</script>
<script type="text/javascript" async
  src="https://cdnjs.cloudflare.com/ajax/libs/mathjax/2.7.5/MathJax.js?config=TeX-MML-AM_CHTML">
</script>

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
<style>

a {
  user-drag: none; /* for WebKit browsers including Chrome */
  user-select: none; /* for standard-compliant browsers */
  -webkit-user-drag: none; /* for Safari and Chrome */
  -webkit-user-select: none; /* for Safari */
  -moz-user-select: none; /* for Firefox */
  -ms-user-select: none; /* for Internet Explorer/Edge */
}
img {
  user-drag: none; /* for WebKit browsers including Chrome */
  user-select: none; /* for standard-compliant browsers */
  -webkit-user-drag: none; /* for Safari and Chrome */
  -webkit-user-select: none; /* for Safari */
  -moz-user-select: none; /* for Firefox */
  -ms-user-select: none; /* for Internet Explorer/Edge */
}
</style>

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
function saveContent(Eventid,Cnttype,Cntid)
  {
    var editor1 = tinymce.get("mytextarea1");   
    var htmlContent1 = editor1.getContent();
    var NewHtml1 = htmlContent1;    
 
    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
    var NewHtml2 = htmlContent2;    

    var editor3 = tinymce.get("mytextarea3");   
    var htmlContent3 = editor3.getContent();
    var NewHtml3 = htmlContent3;    
 
    var editor4 = tinymce.get("mytextarea4");   
    var htmlContent4 = editor4.getContent();
    var NewHtml4 = htmlContent4;    
 
    var editor5 = tinymce.get("mytextarea5");   
    var htmlContent5 = editor5.getContent();
    var NewHtml5 = htmlContent5;    
   
        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "cntid":Cntid,		
              "cnttype":Cnttype,	 
              "inputtext1":NewHtml1, 
              "inputtext2":NewHtml2, 
              "inputtext3":NewHtml3, 
              "inputtext4":NewHtml4, 
              "inputtext5":NewHtml5,               
            },
            success:function(data){
                    var Cntid2=data.cntid;
                    swal("OK !", "저장되었습니다.", {buttons: false,timer: 100});
                    setTimeout(function(){location.reload();} , 100); 
                    }
             })
}
 
</script>
 
';

?>
