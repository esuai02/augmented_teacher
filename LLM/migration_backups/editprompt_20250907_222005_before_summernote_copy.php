<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
$cntid = $_GET["cntid"];
$cnttype = $_GET["cnttype"];
$mode = $_GET["duplicate"];
$studentid = $_GET["studentid"]; 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
$instructionBtn='';

if($studentid==NULL)$studentid=$USER->id;


$adaptivecontents=$DB->get_records_sql("SELECT * FROM mdl_abessi_adaptivecontents where contentsid='$cntid' AND contentstype='$cnttype'  ORDER BY id DESC LIMIT 1");  
$ankiquiz=$DB->get_record_sql("SELECT * FROM mdl_abessi_ankiquiz where contentsid='$cntid' AND contentstype='$cnttype'  ORDER BY id DESC LIMIT 1");
$quizid=$ankiquiz->id;

$result = json_decode(json_encode($adaptivecontents), True);
unset($value);
foreach($result as $value)
	{
  if(!empty($value['cnttext1']))$allcontents.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=1"target="_blank">C1</a> |</td>';
  if(!empty($value['cnttext2']))$allcontents.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=2"target="_blank">C2</a> |</td>';
  if(!empty($value['cnttext3']))$allcontents.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=3"target="_blank">C3</a> |</td>';
  if(!empty($value['cnttext4']))$allcontents.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=4"target="_blank">C4</a> |</td>'; 
  if(!empty($value['cnttext5']))$allcontents.='<td><a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/adaptivecontent.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&nadaptive=5"target="_blank">C5</a></td>';   
  }
if($cnttype==1)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $eventid=1;
    $guidetext1=$cnttext->reflections0;
    $guidetext2=$cnttext->reflections1;
    $maintext=$cnttext->maintext;
    $instructgpturl='';
    //if($cnttext->pagenum==1)$instructgpturl='https://chatgpt.com/g/g-SSDjte8lc-ijeongwajeong-jeomgeom-mic-yongeojeongri-gpt';
    //else $instructgpturl='https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu';

    //if(strpos($guidetext1,'지시사항')!==false || strpos($guidetext1,'퀴즈')!==false)
      {
      $instructionBtn1='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=0"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
      $instructionBtn2='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=1"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
      }
    if($role!=='student')$editcontent='<table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>개념본문</td></tr><tr><td valign=top>
    <textarea id="mytextarea2">'.$maintext.'</textarea></td></tr></table><table width=100% height=70%><tr><td valign=top><textarea style="display:none;" id="mytextarea3"></textarea></td></tr></table>';
    else $editcontent='';



    
	$getimgbk=$DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$cntid'  ORDER BY id DESC LIMIT 1");
	$ctextbk=$getimgbk->pageicontent;
	$htmlDom = new DOMDocument;
	@$htmlDom->loadHTML($ctextbk);
	$imageTags2 = $htmlDom->getElementsByTagName('img');
	$extractedImages = array();
	$nimg=0;
	foreach($imageTags2 as $imageTag2)
      {
      $nimg++;
        $imgSrc1 = $imageTag2->getAttribute('src');
      //$imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
      //if(strpos($imgSrc1, 'Contents/MATH%20MATRIX/MATH%20images')!= false || strpos($imgSrc1, 'ContentsIMG')!= false)
      
      if(strpos($imgSrc1, '.png')!= false || strpos($imgSrc1, '.jpg')!= false)break;
      }
    }
elseif($cnttype==2)
    {
    $cnttext=$DB->get_record_sql("SELECT * FROM mdl_question where id='$cntid'  ORDER BY id DESC LIMIT 1");  
    $guidetext1=$cnttext->reflections0;
    $guidetext2=$cnttext->reflections1;
    $maintext=$cnttext->mathexpression;
    $soltext=$cnttext->ans1;
   // if(strpos($guidetext1,'지시사항')!==false|| strpos($guidetext1,'퀴즈')!==false)
      {
      $instructionBtn1='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=0"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
      $instructionBtn2='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=1"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
      }
    $eventid=2;
    if($role!=='student')$editcontent='<table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>문제</td></tr><tr><td valign=top> <textarea id="mytextarea2">'.$maintext.'</textarea></td></tr></table>
    <table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>해설</td></tr><tr><td valign=top><textarea id="mytextarea3">'.$soltext.'</textarea></td></tr></table>';
    else $editcontent='';
    
    $textareas='<br><br><table width=100% height=70% ><tr><td align=left style="color:green;font-size:18px;" valign=top>다음 내용을 클릭,복사하여 GPT에 입력</td></tr><tr><td valign=top  class="copyable" valign="top" style="cursor: pointer;background-color:#D5F3FE;">'.$maintext.'<br>'.$soltext.'</td>'.$cntimgs.'</tr></table><br><br><table width=100% height=30%><tr><td width=50% align=left style="color:green;font-size:18px;">지시사항</td><td></td><td width=50% align=left style="color:green;font-size:18px;">확인퀴즈</td></tr><tr><td valign=top><textarea id="mytextarea0">'.$guidetext1.'</textarea> </td><td></td><td><textarea id="mytextarea1">'.$guidetext2.'</textarea> </td></tr></table>
    <table align=right><tr><td>&nbsp;&nbsp;&nbsp;   &nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>지시사항 +</button></a>'.$instructionBtn1.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-Dxra8i1Oe-ktm-binkan-caeugi-jilmun-saengseonggi"target="_blank"><button>퀴즈출제 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-NHQ5KMkvu-anki-kwijeu-saengseonggi"target="_blank"><button>ANKI 퀴즈</button></a>'.$instructionBtn2.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>나레이션 +</button></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">업로드</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button onclick="saveContent(\''.$eventid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>
    '.$editcontent.'';

    $qtext0 = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$cntid' ORDER BY id DESC LIMIT 1 ");
    $htmlDom1 = new DOMDocument;@$htmlDom1->loadHTML($qtext0->generalfeedback); $imageTags1 = $htmlDom1->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags1 as $imageTag1)
      {
      $nimg++; $imgSrc1 = $imageTag1->getAttribute('src'); $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
      if(strpos($imgSrc1, 'MATRIX/MATH')!= false && strpos($imgSrc1, 'hintimages')==false)break;
  
      //if(strpos($imgSrc1, 'Contents/MATH%20MATRIX/MATH%20images')!= false || strpos($imgSrc1, 'ContentsIMG')!= false)break;  //local/ContentsIMG
      }
    $htmlDom2 = new DOMDocument;@$htmlDom2->loadHTML($qtext0->questiontext); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags2 as $imageTag2)
      {
      $nimg++; $imgSrc2 = $imageTag2->getAttribute('src'); $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
      if(strpos($imgSrc2, 'hintimages')!= true && (strpos($imgSrc2, '.png')!= false || strpos($imgSrc2, '.jpg')!= false))break;
  
      //if(strpos($imgSrc2, 'Contents/MATH%20MATRIX/MATH%20images')!= false || strpos($imgSrc1, 'ContentsIMG')!= false)break;
      }

    }

    if($imgSrc1!=NULL)$cnttext1='<img src="'.$imgSrc1.'" width=200>';
    if($imgSrc2!=NULL)$cnttext2='<img src="'.$imgSrc2.'" width=400>';
     
   $cntimgs= '<td>'.$cnttext2.'</td><td>'.$cnttext1.'</td>';

      //onclick="edittoday(2,'.$studentid.',$(\'#squareInput\').val(),$(\'#basic1\').val(),$(\'#basic2\').val(),$(\'#datepicker\').val()); "
      $textareas='<br><br><table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>다음 내용을 클릭,복사하여 GPT에 입력</td></tr><tr><td valign=top  class="copyable" valign="top" style="cursor: pointer;background-color:#D5F3FE;">'.$maintext.'</td>'.$cntimgs.'</tr></table><br><br><table width=100% height=30%><tr><td width=50% align=left style="color:green;font-size:18px;">지시사항</td><td></td><td width=50% align=left style="color:green;font-size:18px;"><table><tr><td><select  id="basic" name="quizOptions"><option value="quiz1">빈칸 채우기</option><option value="quiz2">유사문제</option><option value="quiz3">변형문제</option><option value="quiz4">계산연습</option><option value="quiz5">개념 서술평가</option><option value="quiz6">난이도 연습문제</option><option value="quiz7">복잡도 연습문제</option><option value="quiz8">분석지점 연습문제</option></select> | <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editcontents.php?cntid='.$cntid.'&cnttype='.$cnttype.'"target="_blank">보충학습</a> :</td><td>'.$allcontents.'</tr></table></td></tr><tr><td valign=top><textarea id="mytextarea0">'.$guidetext1.'</textarea> </td><td></td><td><textarea id="mytextarea1">'.$guidetext2.'</textarea> </td></tr></table>
      <table align=right><tr><td>&nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>지시사항 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-67e4f6cc2d1c819198d945eabe513021"target="_blank"><button>성장 마인드 안내자</button></a> '.$instructionBtn1.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-Dxra8i1Oe-ktm-binkan-caeugi-jilmun-saengseonggi"target="_blank"><button>퀴즈출제 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-NHQ5KMkvu-anki-kwijeu-saengseonggi"target="_blank"><button>ANKI 퀴즈</button></a>'.$instructionBtn2.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>나레이션 +</button></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">오디오 +</button><button id="image_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">이미지 +</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="saveContent(\''.$eventid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>'.$editcontent;

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
function saveContent(Eventid,Cntid)
  {
    var editor0 = tinymce.get("mytextarea0");   
    var htmlContent0 = editor0.getContent();
    var NewHtml0 = htmlContent0;    

    var editor1 = tinymce.get("mytextarea1");   
    var htmlContent1 = editor1.getContent();
    var NewHtml1 = htmlContent1;    
 
    var editor2 = tinymce.get("mytextarea2");   
    var htmlContent2 = editor2.getContent();
    var NewHtml2 = htmlContent2;    
  
   var editor3 = tinymce.get("mytextarea3");   
   var htmlContent3 = editor3.getContent();
   var NewHtml3 = htmlContent3;    
 

        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType:"json", 
            data : {
              "eventid":Eventid,
              "cntid":Cntid,		
              "inputtext0":NewHtml0,  
              "inputtext1":NewHtml1, 
              "inputtext2":NewHtml2, 
              "inputtext3":NewHtml3, 
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
document.getElementById("image_upload").onclick = function () 
{  
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";  // 이미지 파일만 선택할 수 있도록 변경
    var object = null;
    var Quizid = \''.$quizid.'\'; 
    alert("ANKI QUIZ에 이미지가 추가됩니다. 계속하시겠습니까 ?");
    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
        var formData = new FormData();
        formData.append("image", file);  // 
        formData.append("quizid", Quizid); 
        
        $.ajax({
            url: "addimagetoanki.php",  // 이미지를 처리할 서버의 URL
            type: "POST",
            cache: false,
            contentType: false,
            processData: false,
            data: formData,
            success: function (data, status, xhr) 
            {
                var parsed_data = JSON.parse(data);
                // 이 부분에 이미지 객체를 생성하고 처리하는 로직을 추가합니다.
                object = parsed_data; // 필요에 따라 수정
                if (object)
                {
                    // 이미지 객체 처리 로직
                }
            }
        })
    }
    input.click();
}

</script>
 
';

?>
