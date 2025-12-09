<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$studentid = $_GET["studentid"]; 
$otuid = $_GET["otuid"];
$eventid=5;

if($cnttype==1)
    {
    $icontent=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $maintext=$icontent->maintext;
    }
elseif($cnttype==2)
    {
    $question=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $maintext=$question->mathexpression;
    }

$cnttext=$DB->get_record_sql("SELECT * FROM mdl_abessi_onetimeusecontents where userid='$USER->id' AND contentsid='$cntid' AND contentstype='$cnttype' ORDER BY id DESC LIMIT 1");  
$contentstext=$cnttext->cnttext; 
//if($cntid==NULL)$cntid=$USER->id.time();
//if($cnttype==NULL)$cnttype=2;
if($maintext==NULL)$maintext='퀴즈를 위한 사전정보가 없습니다. GPT에 직접 요청사항을 입력해 주세요.';
    
echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body><br> 
<table align=center width=90%>
<tr><td width=48% valign=top>컨텐츠</td><td width=4% align=center></td><td width=48% valign=top>GPT결과 입력하기 </td></tr>
<tr><td style="border: 1px solid black;" width=48% valign=top><br><br><table align=center width=90%><tr><td>'.$contentstext.'</td></tr>
</table></td><td width=4% align=center></td><td width=48% valign=top><textarea id="mytextarea1">'.$contentstext.'</textarea> <br><button onclick="saveContent(\''.$eventid.'\',\''.$cnttype.'\',\''.$cntid.'\')">저장하기 (두 번 클릭)</button>  <a href="https://chatgpt.com/g/g-apWVmPMop-hogisim-haegyeolsa"target="_blank"><button>GPT에게 질문하기</button></a><hr><table width=100% height=70%><tr><td align=left style="color:green;font-size:15px;" valign=top>다음 내용을 클릭,복사하여 GPT에 입력</td></tr><tr><td valign=top  class="copyable" valign="top" style="cursor: pointer;background-color:#D5F3FE;">'.$maintext.'</td></tr></table>
<hr><a href="https://chatgpt.com/g/g-y7sU3LLg9"target="_blank"><button>GPT 퀴즈 만들기</button></a>  <a href="https://chatgpt.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button> 나누어 생각하기</button></a><hr>
</td></tr>
</table><hr>
<table align=center width=60%><tr><td> <a style="font-size:20px;" href="https://mathking.kr/moodle/local/augmented_teacher/LLM/onetimeusecontents.php?otuseid='.$cnttext->id.'&cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$USER->id.'" target="_blank">📝 활동시작하기</a></td></tr></table><hr>

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

    function copyHtmlToClipboard(html) {
      var tempDiv = document.createElement("div");
      tempDiv.innerHTML = html; // HTML을 텍스트로 변환하지 않고 직접 할당
      var tempInput = document.createElement("input");
      tempInput.style = "position: absolute; left: -1000px; top: -1000px";
      document.body.appendChild(tempInput);
      tempInput.value = tempDiv.textContent || tempDiv.innerText; // HTML 내용을 순수 텍스트로 추출
      tempInput.select();
      document.execCommand("copy");
      document.body.removeChild(tempInput);
  }
  
  document.addEventListener("DOMContentLoaded", () => {
      document.querySelectorAll("td.copyable").forEach(td => {
          td.addEventListener("click", () => {
              copyHtmlToClipboard(td.innerHTML); // innerHTML을 사용하여 HTML 내용을 복사
              swal("복사되었습니다.", {buttons: false,timer: 500});          });
      });
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
function saveContent(Eventid,Cnttype,Cntid)
  {
    var editor1 = tinymce.get("mytextarea1");   
    var htmlContent1 = editor1.getContent();
    var NewHtml1 = htmlContent1;    
        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "cntid":Cntid,		
              "cnttype":Cnttype,	 
              "inputtext1":NewHtml1,           
            },
            success:function(data){
                    var Otuid=data.otuid;
                     
                    swal("OK !", "저장되었습니다.", {buttons: false,timer: 100});
                    setTimeout(function(){window.location.reload();} , 100);
                    }
             })
}
 
</script>
 
';

?>
