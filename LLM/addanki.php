<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$ankiquizid = $_GET["qid"];
$cnttype = $_GET["cnttype"];

$studentid = $_GET["studentid"]; 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
$instructionBtn='';
 

$cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
$eventid=11;
$guidetext1=$cnttext->reflections0;
$ankicnt=$cnttext->reflections1;
$maintext=$cnttext->maintext;
$instructgpturl='';
 
$introtext='현재 카테고리에 새로운 ANKI가 추가됩니다.';

if($ankiquizid!=NULL) //보충컨텐츠
    {
    $cmplanki=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz WHERE id='$ankiquizid' ");
    $ankicnt=$cmplanki->text;
    $maintext=$cmplanki->helpcnt;
    $cntid = $cmplanki->contentsid;
    $cnttype = $cmplanki->contentstype;
    $introtext='현재 퀴즈를 수정하는 페이지입니다.';
    }


//if(strpos($guidetext1,'지시사항')!==false || strpos($guidetext1,'퀴즈')!==false)
  {
  $instructionBtn1='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=0"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
  $instructionBtn2='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/anki.php?qid='.$ankiquizid.'&studentid='.$studentid.'"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
  }
if($role!=='student')$quiztitle='ANKI 제목  : <input type="text" id="squareInput" value="'.$cmplanki->title.'">';
else $quiztitle='';

$textareas='<table align=center width=90%><tr><td>
<br><span style="font-size:20px;">'.$introtext.' </span>  <hr>'.$quiztitle.'<hr><table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>다음 내용을 클릭,복사하여 GPT에 입력</td></tr><tr><td valign=top  class="copyable" valign="top" style="cursor: pointer;background-color:#D5F3FE;">'.$maintext.'</td></tr></table><br><br><table width=100% height=30%><tr><td width=50% align=left style="color:green;font-size:18px;">개념유형 이해하기</td><td></td><td width=50% align=left style="color:green;font-size:18px;"><table><tr><td>ANKI 코드입력</td><td></tr></table></td></tr><tr><td valign=top><textarea id="mytextarea0">'.$guidetext1.'</textarea> </td><td></td><td><textarea id="mytextarea1">'.$ankicnt.'</textarea> </td></tr></table>
<table align=right><tr><td>&nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>지시사항 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-SSDjte8lc-ijeongwajeong-jeomgeom-mic-yongeojeongri-gpt"target="_blank"><button>대화오류 찾기 +</button></a> '.$instructionBtn1.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-Dxra8i1Oe-ktm-binkan-caeugi-jilmun-saengseonggi"target="_blank"><button>퀴즈출제 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-NHQ5KMkvu-anki-kwijeu-saengseonggi"target="_blank"><button>ANKI 퀴즈</button></a>'.$instructionBtn2.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>나레이션 +</button></a><a href="https://elevenlabs.io/?utm_source=elevenlabs_gpt"target="_blank"><button>오디오생성</button></a><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">업로드</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button onclick="saveContent(\''.$eventid.'\',$(\'#squareInput\').val(),\''.$cnttype.'\',\''.$cntid.'\')">추가하기</button></td><td width=10%></td></tr></table><br><br><br><br><br><br><br><br></td></tr></table>';
    
echo '<!DOCTYPE html>
<html>
<head>
<script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
</head>
<body>
'.$textareas.'
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
  <script> 
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
function saveContent(Eventid,Inputtitle,Cnttype,Cntid)
  {
    var editor0 = tinymce.get("mytextarea0");   
    var htmlContent0 = editor0.getContent();
    var NewHtml0 = htmlContent0;    

    var editor1 = tinymce.get("mytextarea1");   
    var htmlContent1 = editor1.getContent();
    var NewHtml1 = htmlContent1;    
  alert(Inputtitle);
  

        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "cnttype":Cnttype,	
              "cntid":Cntid,		
              "inputtitle":Inputtitle,
              "inputtext0":NewHtml0,  
              "inputtext1":NewHtml1,  
            },
            success:function(data){
                    var Cntid2=data.cntid;
                    swal("OK !", "저장되었습니다.", {buttons: false,timer: 100});
                    setTimeout(function(){location.reload();} , 100); 
                    }
             })
}

document.getElementById("audio_upload").onclick = function ()
{  
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "audio/*"
    var object = null;
    var Contentsid= \''.$cntid.'\'; 
    var Contentstype= \''.$cnttype.'\'; 


    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
        var formData = new FormData();
        formData.append("audio", file);
        formData.append("contentsid", Contentsid); 
        formData.append("contentstype", Contentstype); 
        $.ajax({
            url: "file.php",
            type: "POST",
            cache: false,
            contentType: false,
            processData: false,
            data: formData,
            success: function (data, status, xhr) 
            {
                var parsed_data = JSON.parse(data);
                // View.createAudioObject와 같은 오디오 객체를 생성하는 새 함수가 필요합니다.
                // 이 예에서는 object 변수의 할당을 단순화했습니다.
                object = parsed_data; // 오디오 객체 생성 로직에 맞게 수정 필요
                if (object)
                {
                    // 오디오 객체 처리 로직
                }
            }
        })
    }
    input.click();

}


</script>
 
';

?>
