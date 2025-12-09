<?php 
/////////////////////////////// Summernote Only Version - No TinyMCE ///////////////////////////////
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
      {
      $instructionBtn1='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=0"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
      $instructionBtn2='<a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/print_papertest.php?cntid='.$cntid.'&cnttype='.$cnttype.'&studentid='.$studentid.'&print=1"target="_blank"><img loading="lazy"  src="https://mathking.kr/Contents/IMAGES/instructions.png" width=30></a>';
      }
    $eventid=2;
    if($role!=='student')$editcontent='<table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>문제</td></tr><tr><td valign=top> <textarea id="mytextarea2">'.$maintext.'</textarea></td></tr></table>
    <table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>해설</td></tr><tr><td valign=top><textarea id="mytextarea3">'.$soltext.'</textarea></td></tr></table>';
    else $editcontent='';
    
    $textareas='<br><br><table width=100% height=70% ><tr><td align=left style="color:green;font-size:18px;" valign=top>다음 내용을 클릭,복사하여 GPT에 입력</td></tr><tr><td valign=top  class="copyable" valign="top" style="cursor: pointer;background-color:#D5F3FE;">'.$maintext.'<br>'.$soltext.'</td>'.$cntimgs.'</tr></table><br><br><table width=100% height=30%><tr><td width=50% align=left style="color:green;font-size:18px;">지시사항</td><td></td><td width=50% align=left style="color:green;font-size:18px;">확인퀴즈</td></tr><tr><td valign=top><textarea id="mytextarea0">'.$guidetext1.'</textarea> </td><td></td><td><textarea id="mytextarea1">'.$guidetext2.'</textarea> </td></tr></table>
    <table align=right><tr><td>&nbsp;&nbsp;&nbsp;   &nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>단계지시 GPT</button></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>나레이션 +</button></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">업로드</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button onclick="saveContent(\''.$eventid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>
    '.$editcontent.'';

    $qtext0 = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$cntid' ORDER BY id DESC LIMIT 1 ");
    $htmlDom1 = new DOMDocument;@$htmlDom1->loadHTML($qtext0->generalfeedback); $imageTags1 = $htmlDom1->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags1 as $imageTag1)
      {
      $nimg++; $imgSrc1 = $imageTag1->getAttribute('src'); $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
      if(strpos($imgSrc1, 'MATRIX/MATH')!= false && strpos($imgSrc1, 'hintimages')==false)break;
      }
    $htmlDom2 = new DOMDocument;@$htmlDom2->loadHTML($qtext0->questiontext); $imageTags2 = $htmlDom2->getElementsByTagName('img'); $extractedImages = array(); $nimg=0;
    foreach($imageTags2 as $imageTag2)
      {
      $nimg++; $imgSrc2 = $imageTag2->getAttribute('src'); $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
      if(strpos($imgSrc2, 'hintimages')!= true && (strpos($imgSrc2, '.png')!= false || strpos($imgSrc2, '.jpg')!= false))break;
      }

    }

    if($imgSrc1!=NULL)$cnttext1='<img src="'.$imgSrc1.'" width=200>';
    if($imgSrc2!=NULL)$cnttext2='<img src="'.$imgSrc2.'" width=400>';
     
   $cntimgs= '<td>'.$cnttext2.'</td><td>'.$cnttext1.'</td>';

      $textareas='<br><br><table width=100% height=70%><tr><td align=left style="color:green;font-size:18px;" valign=top>다음 내용을 클릭,복사하여 GPT에 입력</td></tr><tr><td valign=top  class="copyable" valign="top" style="cursor: pointer;background-color:#D5F3FE;">'.$maintext.'</td>'.$cntimgs.'</tr></table><br><br><table width=100% height=30%><tr><td width=50% align=left style="color:green;font-size:18px;">지시사항</td><td></td><td width=50% align=left style="color:green;font-size:18px;"><table><tr><td><select  id="basic" name="quizOptions"><option value="quiz1">빈칸 채우기</option><option value="quiz2">유사문제</option><option value="quiz3">변형문제</option><option value="quiz4">계산연습</option><option value="quiz5">개념 서술평가</option><option value="quiz6">난이도 연습문제</option><option value="quiz7">복잡도 연습문제</option><option value="quiz8">분석지점 연습문제</option></select> | <a href="https://mathking.kr/moodle/local/augmented_teacher/LLM/editcontents.php?cntid='.$cntid.'&cnttype='.$cnttype.'"target="_blank">보충학습</a> :</td><td>'.$allcontents.'</tr></table></td></tr><tr><td valign=top><textarea id="mytextarea0">'.$guidetext1.'</textarea> </td><td></td><td><textarea id="mytextarea1">'.$guidetext2.'</textarea> </td></tr></table>
      <table align=right><tr><td>&nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>단계지시 GPT</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-68ed08ad8898819185fb057fb5583786-injijeog-dojehagseub-jeonmunga"target="_blank"><button>절차기억 GPT</button></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>내용설명 GPT</button></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a><button id="image_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">이미지 +</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="saveContent(\''.$eventid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>'.$editcontent;

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prompt - Summernote Editor</title>
    
    <!-- jQuery (required for Summernote) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Bootstrap 3.4 (keeping for compatibility) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <!-- Summernote CSS/JS -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
    
    <style>
        a {
            user-drag: none;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        img {
            user-drag: none;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
        }
        .note-editor {
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .note-toolbar {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
'.$textareas.'

<script>
    $(document).ready(function() {
        // Summernote 초기화 - 모든 textarea에 적용
        $("#mytextarea0, #mytextarea1, #mytextarea2, #mytextarea3").summernote({
            height: 300,
            lang: "ko-KR",
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "italic", "underline", "clear"]],
                ["fontname", ["fontname"]],
                ["fontsize", ["fontsize"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture", "video"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ],
            fontNames: ["Arial", "Arial Black", "Comic Sans MS", "Courier New", "맑은 고딕", "굴림체", "굴림", "돋움체", "바탕체"],
            fontSizes: ["8", "9", "10", "11", "12", "14", "16", "18", "20", "22", "24", "28", "30", "36", "50", "72"],
            callbacks: {
                onInit: function() {
                    console.log("Summernote is initialized");
                },
                onChange: function(contents, $editable) {
                    console.log("Content changed");
                },
                onImageUpload: function(files) {
                    // 이미지 업로드 처리
                    for(let i = 0; i < files.length; i++) {
                        uploadImage(files[i], this);
                    }
                }
            }
        });
    });
    
    // 이미지 업로드 함수
    function uploadImage(file, editor) {
        var formData = new FormData();
        formData.append("file", file);
        
        $.ajax({
            url: "upload_image.php", // 이미지 업로드 처리 서버 스크립트
            method: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function(data) {
                // 성공 시 에디터에 이미지 삽입
                $(editor).summernote("insertImage", data.url);
            },
            error: function() {
                console.log("Image upload failed");
            }
        });
    }
    
    // 콘텐츠 저장 함수
    function saveContent(Eventid, Cntid) {
        // Summernote에서 콘텐츠 가져오기
        var NewHtml0 = $("#mytextarea0").summernote("code");
        var NewHtml1 = $("#mytextarea1").summernote("code");
        var NewHtml2 = $("#mytextarea2").length ? $("#mytextarea2").summernote("code") : "";
        var NewHtml3 = $("#mytextarea3").length ? $("#mytextarea3").summernote("code") : "";
        
        $.ajax({
            url: "check_status.php",
            type: "POST",
            dataType: "json",
            data: {
                "eventid": Eventid,
                "cntid": Cntid,
                "inputtext0": NewHtml0,
                "inputtext1": NewHtml1,
                "inputtext2": NewHtml2,
                "inputtext3": NewHtml3
            },
            success: function(data) {
                alert("적용되었습니다.");
                setTimeout(function() { location.reload(); }, 100);
            },
            error: function() {
                swal("Error", "저장에 실패했습니다.", "error");
            }
        });
    }
    
    // 복사 기능
    function copyHtmlToClipboard(html) {
        var tempDiv = document.createElement("div");
        tempDiv.innerHTML = html;
        var tempInput = document.createElement("input");
        tempInput.style = "position: absolute; left: -1000px; top: -1000px";
        document.body.appendChild(tempInput);
        tempInput.value = tempDiv.textContent || tempDiv.innerText;
        tempInput.select();
        document.execCommand("copy");
        document.body.removeChild(tempInput);
    }
    
    document.addEventListener("DOMContentLoaded", () => {
        document.querySelectorAll("td.copyable").forEach(td => {
            td.addEventListener("click", () => {
                copyHtmlToClipboard(td.innerHTML);
                swal("복사되었습니다.", {buttons: false, timer: 500});
            });
        });
    });
    
    // 오디오 업로드
    document.getElementById("audio_upload").onclick = function() {
        var input = document.createElement("input");
        input.type = "file";
        input.accept = "audio/*";
        var Contentsid = "'.$cntid.'";
        var Contentstype = "'.$cnttype.'";
        
        input.onchange = e => {
            var file = e.target.files[0];
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
                success: function(data) {
                    var parsed_data = JSON.parse(data);
                    console.log("Audio uploaded successfully");
                }
            });
        };
        input.click();
    };
    
    // 이미지 업로드
    document.getElementById("image_upload").onclick = function() {
        var input = document.createElement("input");
        input.type = "file";
        input.accept = "image/*";
        var Quizid = "'.$quizid.'";
        
        alert("ANKI QUIZ에 이미지가 추가됩니다. 계속하시겠습니까?");
        
        input.onchange = e => {
            var file = e.target.files[0];
            var formData = new FormData();
            formData.append("image", file);
            formData.append("quizid", Quizid);
            
            $.ajax({
                url: "addimagetoanki.php",
                type: "POST",
                cache: false,
                contentType: false,
                processData: false,
                data: formData,
                success: function(data) {
                    var parsed_data = JSON.parse(data);
                    console.log("Image uploaded successfully");
                }
            });
        };
        input.click();
    };
</script>

</body>
</html>';

?>