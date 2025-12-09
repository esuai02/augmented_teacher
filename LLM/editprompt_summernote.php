<?php 
/////////////////////////////// TinyMCE to Summernote Migration ///////////////////////////////
// Feature flags for progressive migration
$use_summernote = isset($_GET['use_summernote']) ? true : false;
$dual_mode = isset($_GET['dual_mode']) ? true : false;
$debug_mode = isset($_GET['debug']) ? true : false;

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
    <table align=right><tr><td>&nbsp;&nbsp;&nbsp;   &nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>지시사항 +</button></a>'.$instructionBtn1.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-Dxra8i1Oe-ktm-binkan-caeugi-jilmun-saengseonggi"target="_blank"><button>퀴즈출제 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-NHQ5KMkvu-anki-kwijeu-saengseonggi"target="_blank"><button>ANKI 퀴즈</button></a>'.$instructionBtn2.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>나레이션 +</button></a> <a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">업로드</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <button onclick="saveContent(\''.$eventid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>
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
      <table align=right><tr><td>&nbsp;&nbsp;&nbsp; <a href="https://chat.openai.com/g/g-RNnwgPr07-jimyeonpyeongga-culje-dogu"target="_blank"><button>지시사항 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-67e4f6cc2d1c819198d945eabe513021"target="_blank"><button>성장 마인드 안내자</button></a> '.$instructionBtn1.' &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-Dxra8i1Oe-ktm-binkan-caeugi-jilmun-saengseonggi"target="_blank"><button>퀴즈출제 +</button></a>&nbsp;&nbsp;&nbsp;<a href="https://chatgpt.com/g/g-NHQ5KMkvu-anki-kwijeu-saengseonggi"target="_blank"><button>ANKI 퀴즈</button></a>'.$instructionBtn2.'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="https://chat.openai.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci"target="_blank"><button>나레이션 +</button></a><a href="https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid='.$cntid.'&ctype='.$cnttype.'"target="_blank"><button>오디오생성</button></a><button id="audio_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">오디오 +</button><button id="image_upload" type="button" class="" data-toggle="collapse" data-target="#demo" accesskey="a">이미지 +</button> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="saveContent(\''.$eventid.'\',\''.$cntid.'\')">저장하기</button></td><td width=10%></td></tr></table><hr>'.$editcontent;

// PHP 변수를 JavaScript에서 안전하게 사용하기 위한 JSON 인코딩
$js_use_summernote = $use_summernote ? 'true' : 'false';
$js_dual_mode = $dual_mode ? 'true' : 'false';
$js_debug_mode = $debug_mode ? 'true' : 'false';

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prompt - Editor Migration</title>
    
    <!-- Migration Configuration -->
    <script>
        window.EDITOR_CONFIG = {
            useSummernote: '.$js_use_summernote.',
            enableDualMode: '.$js_dual_mode.',
            debugMode: '.$js_debug_mode.'
        };
    </script>
    
    <!-- jQuery (upgraded for compatibility) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/jquery-migrate-3.4.0.min.js"></script>
    
    <!-- Bootstrap 3.4 (keeping existing version) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
    
    <!-- jQuery UI -->
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css" />
    <script src="//code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    
    <!-- Summernote (Primary Editor) -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote.min.js"></script>
    
    <!-- TinyMCE (Fallback) -->
    <script src="https://cdn.tiny.cloud/1/x12vtt6v4a0t8v78wuir39dwg6xpu6eftx9cf9iumf0wtfhd/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    
    <!-- SweetAlert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script>
    
    <style>
        /* Migration Styles */
        .editor-migration-notice {
            background-color: #d9edf7;
            border: 1px solid #bce8f1;
            color: #31708f;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        
        .editor-performance-metrics {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 4px;
            font-size: 12px;
            z-index: 9999;
            display: none;
        }
        
        .editor-loading {
            display: none;
            text-align: center;
            padding: 20px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
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
    </style>
</head>
<body>
    <!-- Performance Metrics (Debug Mode) -->
    <div class="editor-performance-metrics" id="performanceMetrics">
        <div>Editor: <span id="currentEditor">Loading...</span></div>
        <div>Load Time: <span id="loadTime">0ms</span></div>
        <div>Memory: <span id="memoryUsage">0KB</span></div>
    </div>
    
    <!-- Migration Notice -->
    <div class="editor-migration-notice" id="migrationNotice" style="display: none;">
        <strong>Editor Migration:</strong> Testing new Summernote editor. 
        <a href="?cntid='.$cntid.'&cnttype='.$cnttype.'&use_summernote=0" class="btn btn-xs btn-default">Use Legacy Editor</a>
        <a href="?cntid='.$cntid.'&cnttype='.$cnttype.'&dual_mode=1" class="btn btn-xs btn-info">Enable Dual Mode</a>
    </div>
    
'.$textareas.'

<script>
    // Performance tracking
    const perfMetrics = {
        startTime: performance.now(),
        editorLoads: 0
    };
    
    // Migration state
    const migrationState = {
        currentEditor: window.EDITOR_CONFIG.useSummernote ? "summernote" : "tinymce",
        editors: {}
    };
    
    // Initialize editors based on configuration
    function initializeEditors() {
        if (window.EDITOR_CONFIG.useSummernote) {
            initializeSummernote();
        } else {
            initializeTinyMCE();
        }
        
        // Show migration notice if using Summernote
        if (window.EDITOR_CONFIG.useSummernote) {
            document.getElementById("migrationNotice").style.display = "block";
        }
        
        // Show performance metrics in debug mode
        if (window.EDITOR_CONFIG.debugMode) {
            document.getElementById("performanceMetrics").style.display = "block";
            updatePerformanceMetrics();
        }
    }
    
    // Initialize Summernote
    function initializeSummernote() {
        const summernoteConfig = {
            height: 300,
            toolbar: [
                ["style", ["style"]],
                ["font", ["bold", "italic", "underline", "clear"]],
                ["fontname", ["fontname"]],
                ["color", ["color"]],
                ["para", ["ul", "ol", "paragraph"]],
                ["table", ["table"]],
                ["insert", ["link", "picture", "video"]],
                ["view", ["fullscreen", "codeview", "help"]]
            ],
            callbacks: {
                onInit: function() {
                    perfMetrics.editorLoads++;
                    console.log("Summernote initialized");
                }
            }
        };
        
        // Initialize all textareas
        $("textarea").each(function() {
            $(this).summernote(summernoteConfig);
            migrationState.editors[this.id] = "summernote";
        });
        
        document.getElementById("currentEditor").textContent = "Summernote";
    }
    
    // Initialize TinyMCE (fallback)
    function initializeTinyMCE() {
        tinymce.init({
            selector: "textarea",
            height: 300,
            plugins: "anchor autolink charmap codesample emoticons image link lists media searchreplace table visualblocks wordcount",
            toolbar: "undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | link image media table | align lineheight | checklist numlist bullist indent outdent | emoticons charmap | removeformat",
            tinycomments_mode: "embedded",
            tinycomments_author: "Author name",
            mergetags_list: [
                { value: "First.Name", title: "First Name" },
                { value: "Email", title: "Email" },
            ],
            setup: function(editor) {
                editor.on("init", function() {
                    perfMetrics.editorLoads++;
                    migrationState.editors[editor.id] = "tinymce";
                    console.log("TinyMCE initialized");
                });
            }
        });
        
        document.getElementById("currentEditor").textContent = "TinyMCE (Legacy)";
    }
    
    // Update performance metrics
    function updatePerformanceMetrics() {
        const loadTime = Math.round(performance.now() - perfMetrics.startTime);
        document.getElementById("loadTime").textContent = loadTime + "ms";
        
        if (performance.memory) {
            const memoryUsage = Math.round(performance.memory.usedJSHeapSize / 1024);
            document.getElementById("memoryUsage").textContent = memoryUsage + "KB";
        }
    }
    
    // Wait for DOM ready
    $(document).ready(function() {
        initializeEditors();
    });
    
    // Copy functionality
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
                swal("복사되었습니다.", {buttons: false,timer: 500});
            });
        });
    });
</script>

<script>   
function saveContent(Eventid,Cntid)
  {
    // Universal content getter for both editors
    function getEditorContent(elementId) {
        const element = document.getElementById(elementId);
        if (!element) return "";
        
        if (migrationState.editors[elementId] === "summernote") {
            return $(element).summernote("code");
        } else if (migrationState.editors[elementId] === "tinymce") {
            const editor = tinymce.get(elementId);
            return editor ? editor.getContent() : element.value;
        } else {
            return element.value;
        }
    }
    
    var NewHtml0 = getEditorContent("mytextarea0");
    var NewHtml1 = getEditorContent("mytextarea1");
    var NewHtml2 = getEditorContent("mytextarea2");
    var NewHtml3 = getEditorContent("mytextarea3");

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
    var Contentsid= "'.$cntid.'"; 
    var Contentstype= "'.$cnttype.'"; 


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
                object = parsed_data;
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
    input.accept = "image/*";
    var object = null;
    var Quizid = "'.$quizid.'"; 
    alert("ANKI QUIZ에 이미지가 추가됩니다. 계속하시겠습니까 ?");
    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
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
            success: function (data, status, xhr) 
            {
                var parsed_data = JSON.parse(data);
                object = parsed_data;
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
</body>
</html>';

?>