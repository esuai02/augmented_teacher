<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];

if($cnttype==2)
    { 
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $guidetext=$cnttext->mathexpression;
    $maintext=$cnttext->ans1;
    $additional=$cnttext->reflections1;
    $eventid=2;
    }
    
echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
<br> <br><br>
<table width=95% align=center><tr><td valign=top width=32%>
'.$guidetext.' </td><td width=2%></td><td valign=top width=32%>'.$maintext.'</td><td width=2%></td><td valign=top width=32%>'.$additional.'</td></tr></table>
<hr>
 
<table width=100%><tr><td>&nbsp;&nbsp;&nbsp;<button onclick="createSmallQuestions(\''.$eventid.'\',\''.$cnttype.'\',\''.$cntid.'\')">웜업활동 생성</button> </td><td align=right><a href="https://chat.openai.com/chat"target="_blank"><img src=https://mathking.kr/moodle/local/augmented_teacher/IMAGES/gpt.png width=20></a>&nbsp;&nbsp;&nbsp;</td></tr></table></td></tr></table> 
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

function createSmallQuestions(Eventid,Contentstype,Contentsid)		
  swal("", "필기정보가 전달되었습니다.", {buttons: false,timer: 2000});
  $.ajax({
  url:"../LLM/createHintsfromGPT.php",
  type: "POST",
  dataType:"json",
   data : {
    "eventid":Eventid,
    "contentstype":Contentstype,
    "contentsid":Contentsid,
  },
  success:function(data){
    Logid=data.logid;
    Title=data.title;
     Swal.fire({
      title: Title,
      position: "top-end",
      backdrop:false,
      width: 500,
      height:350,
      html: `<iframe src="https://mathking.kr/moodle/local/augmented_teacher/LLM/gptresult.php?logid=`+Logid+`" width="100%" height="300" scrolling="no"></iframe>
      `,
      showCloseButton: true, 
      confirmButtonText: "확인",
      })
   }
   })
  
});	
 
</script>
 
';

?>
