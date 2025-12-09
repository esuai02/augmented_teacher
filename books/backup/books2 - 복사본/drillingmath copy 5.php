<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$secret_key = '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';
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1 "); 
$role=$userrole->data;
require_login();
$contentsid=$_GET["cid"];
$contentstype=$_GET["ctype"];
$nstep=$_GET["nstep"];
$type=$_GET["type"];
// 구간 정보 받기 (mynote2.php에서 dot 더블클릭 시 전달)
$section = isset($_GET["section"]) ? intval($_GET["section"]) : null;
$subtitle = isset($_GET["subtitle"]) ? $_GET["subtitle"] : '';
$timecreated=time();

$thiscnt=$DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults WHERE type LIKE 'conversation' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' ORDER BY id DESC LIMIT 1 ");
$inputtext=$thiscnt->outputtext;

// 구간 자막이 전달된 경우 자막 텍스트로 대체
if(!empty($subtitle)) {
    $inputtext = $subtitle;
    error_log(sprintf(
        '[drillingmath.php] File: %s, Line: %d, Section: %d, Subtitle received: %s',
        basename(__FILE__),
        __LINE__,
        $section,
        substr($subtitle, 0, 100)
    ));
}  
if($role!=='student') echo '';
else 
    {
    echo '사용권한이 없습니다.'; 
    exit();
    }

if($type==NULL)$type='conversation';
$thiscnt=$DB->get_record_sql("SELECT id FROM mdl_abrainalignment_gptresults WHERE type LIKE '$type' AND contentsid LIKE '$contentsid' AND contentstype LIKE '$contentstype' AND gid LIKE '71280'  ORDER BY id DESC LIMIT 1 ");
if($thiscnt->id==NULL)
    {
    $newrecord = new stdClass();
    $newrecord->type = $type;
    $newrecord->contentsid = $contentsid;
    $newrecord->contentstype = $contentstype;
    $newrecord->gid ='71280'; 
    $newrecord->timemodified = $timecreated;
    $newrecord->timecreated = $timecreated; // $timecreated 변수의 값 설정이 필요합니다.
    // 새 레코드를 mdl_abessi_messages 테이블에 삽입
    $DB->insert_record('abrainalignment_gptresults', $newrecord);
    }

$thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype' AND url IS NOT NULL ORDER BY id DESC LIMIT 1 ");

// 컨텐츠 정보 가져오기
$maintext = '';
$imgSrc1 = '';
$imgSrc2 = '';

if($contentstype==1) {
    // icontent_pages 테이블에서 컨텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages where id='$contentsid' ORDER BY id DESC LIMIT 1");
    $maintext = $cnttext->maintext;

    // 이미지 추출
    $getimgbk = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ORDER BY id DESC LIMIT 1");
    $ctextbk = $getimgbk->pageicontent;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctextbk);
    $imageTags2 = $htmlDom->getElementsByTagName('img');
    foreach($imageTags2 as $imageTag2) {
        $imgSrc1 = $imageTag2->getAttribute('src');
        if(strpos($imgSrc1, '.png')!= false || strpos($imgSrc1, '.jpg')!= false) break;
    }
} elseif($contentstype==2) {
    // question 테이블에서 컨텐츠 가져오기
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question where id='$contentsid' ORDER BY id DESC LIMIT 1");
    $maintext = $cnttext->mathexpression;

    // 이미지 추출
    $qtext0 = $DB->get_record_sql("SELECT questiontext,generalfeedback FROM mdl_question WHERE id='$contentsid' ORDER BY id DESC LIMIT 1 ");

    // generalfeedback에서 이미지 추출
    $htmlDom1 = new DOMDocument;
    @$htmlDom1->loadHTML($qtext0->generalfeedback);
    $imageTags1 = $htmlDom1->getElementsByTagName('img');
    foreach($imageTags1 as $imageTag1) {
        $imgSrc1 = $imageTag1->getAttribute('src');
        $imgSrc1 = str_replace(' ', '%20', $imgSrc1);
        if(strpos($imgSrc1, 'MATRIX/MATH')!= false && strpos($imgSrc1, 'hintimages')==false) break;
    }

    // questiontext에서 이미지 추출
    $htmlDom2 = new DOMDocument;
    @$htmlDom2->loadHTML($qtext0->questiontext);
    $imageTags2 = $htmlDom2->getElementsByTagName('img');
    foreach($imageTags2 as $imageTag2) {
        $imgSrc2 = $imageTag2->getAttribute('src');
        $imgSrc2 = str_replace(' ', '%20', $imgSrc2);
        if(strpos($imgSrc2, 'hintimages')!= true && (strpos($imgSrc2, '.png')!= false || strpos($imgSrc2, '.jpg')!= false)) break;
    }
}

// 대화생성 URL 및 노트 URL 설정
$conversationUrl = 'https://chatgpt.com/g/g-fFLnnjprZ-jeonmun-nareisyeon-saengseongjangci';
$noteUrl = '';

if($contentstype==1)
    {
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype' AND url IS NOT NULL ORDER BY id DESC LIMIT 1 ");
        $noteUrl = 'https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$thisboard->url;
    }
else
    {
        $thisboard=$DB->get_record_sql("SELECT * FROM mdl_abessi_messages where contentsid='$contentsid' AND contentstype='$contentstype'  ORDER BY id DESC LIMIT 1 ");
        $noteUrl = 'https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board.php?id='.$thisboard->wboardid;
    }

echo '<script>


function saveText()
  {
    var Contentsid= \''.$contentsid.'\';
    var Contentstype= \''.$contentstype.'\';
    //var Resulttext =document.getElementById("input-text").textContent;
    var Resulttext = document.getElementById("input-text").value;
     
    $.ajax({
      url:"check_status.php",
      type: "POST",
      dataType:"json",
      data : {
      "eventid":5,
      "inputtext":Resulttext,
      "contentsid":Contentsid,
      "contentstype":Contentstype,
      },
      success:function(data){
      var Thisuserid=data.thisuserid;
       }
    })
    //setTimeout(function(){location.reload();},2000);
  }

// DOM이 완전히 로드된 후 이벤트 리스너 등록
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("audio_upload").onclick = function ()
{  
    var input = document.createElement("input");
    input.type = "file";
    input.accept = "audio/*"
    var object = null;
    var Contentsid= \''.$contentsid.'\'; 
    var Contentstype= \''.$contentstype.'\'; 


    input.onchange = e =>
    {
        var file = e.target.files[0];
        var reader = new FileReader();
        var formData = new FormData();
        formData.append("audio", file);
        formData.append("contentsid", Contentsid); 
        formData.append("contentstype", Contentstype); 
        $.ajax({
            url: "../LLM/file.php",
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
}); // DOMContentLoaded 이벤트 리스너 종료
</script>';
?>  

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TTS 서비스</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f4f8;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .title-bar {
            width: 80%;
            max-width: 600px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            margin: 10px auto;
            border-radius: 10px;
        }
        .title-bar h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 600;
        }
        .upload-buttons {
            display: flex;
            gap: 10px;
        }
        #audio_upload {
            background-color: rgba(255,255,255,0.2);
            border: 2px solid white;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        #audio_upload:hover {
            background-color: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }
        #save_button {
            background-color: #4CAF50;
            border: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        #save_button:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }
        .content-info {
            width: 80%;
            max-width: 1200px;
            margin: 10px auto 10px;
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 20px;
            display: flex;
            gap: 20px;
        }
        .left-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .right-column {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .content-images {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: center;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
            background-color: #f9f9f9;
        }
        .content-images:hover {
            background-color: #D5F3FE;
        }
        .content-images img {
            max-width: 100%;
            height: auto;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            pointer-events: none;
        }
        .subtitle-section {
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        .subtitle-section h3 {
            margin: 0 0 10px 0;
            color: #4CAF50;
            font-size: 16px;
        }
        .subtitle-text {
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .thinking-section {
            padding: 15px;
            background-color: #fff8e1;
            border-radius: 5px;
            border-left: 4px solid #ff9800;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .thinking-section h3 {
            margin: 0;
            color: #ff9800;
            font-size: 18px;
            font-weight: bold;
        }
        .thinking-content {
            font-size: 14px;
            line-height: 1.8;
            color: #555;
            min-height: 100px;
        }
        .thinking-signature {
            text-align: right;
            font-style: italic;
            color: #999;
            font-size: 13px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .additional-questions {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .question-button {
            width: 100%;
            padding: 12px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-align: left;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .question-button:hover {
            background-color: #0b7dda;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .question-button::before {
            content: '💭 ';
            margin-right: 5px;
        }
        .answer-section {
            display: none;
            padding: 15px;
            background-color: #e3f2fd;
            border-radius: 5px;
            margin-top: 5px;
            animation: fadeIn 0.3s;
        }
        .answer-section.show {
            display: block;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ff9800;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .copy-notification {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50;
            color: white;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s;
        }
        .copy-notification.show {
            opacity: 1;
        }
    </style>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <!-- MathJax for LaTeX rendering -->
    <script>
        MathJax = {
            tex: {
                inlineMath: [['\\(', '\\)']],
                displayMath: [['\\[', '\\]']],
                processEscapes: true,
                processEnvironments: true
            },
            options: {
                skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre']
            }
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
</head>
<body>

<!-- 타이틀 바 -->
<div class="title-bar">
    <h1>💬 대화기반 컨텐츠 생성기<?php if($section !== null) echo ' - 구간 '.($section + 1); ?></h1>
    <div class="upload-buttons">
        <button id="audio_upload" type="button" title="오디오 파일 업로드">⬆️ 업로드</button>
        <button id="save_button" onclick="saveText()" title="대본 저장">저장</button>
    </div>
</div>

<!-- 복사 알림 -->
<div id="copy-notification" class="copy-notification">복사되었습니다!</div>

<!-- 컨텐츠 정보 표시 - 2단 레이아웃 -->
<div class="content-info">
    <!-- 좌측 컬럼: 이미지 + 자막 -->
    <div class="left-column">
        <div class="content-images" id="content-images-area" onclick="copyImageContent()" title="클릭하여 이미지 복사">
            <h3 style="margin-top:0; color:#4CAF50;">🖼️ 이미지 (클릭하여 복사)</h3>
            <?php
            if(!empty($imgSrc2)) {
                $imgSrc2_full = $imgSrc2;
                if(strpos($imgSrc2, 'http') === false) {
                    $imgSrc2_full = 'https://mathking.kr' . $imgSrc2;
                }
                echo '<img id="content-img2" src="'.$imgSrc2.'" data-original-src="'.$imgSrc2_full.'" alt="문제 이미지" crossorigin="anonymous">';
            }
            if(!empty($imgSrc1)) {
                $imgSrc1_full = $imgSrc1;
                if(strpos($imgSrc1, 'http') === false) {
                    $imgSrc1_full = 'https://mathking.kr' . $imgSrc1;
                }
                echo '<img id="content-img1" src="'.$imgSrc1.'" data-original-src="'.$imgSrc1_full.'" alt="해설 이미지" crossorigin="anonymous">';
            }
            if(empty($imgSrc1) && empty($imgSrc2)) {
                echo '<p style="color:#999;">이미지 없음</p>';
            }
            ?>
        </div>

        <?php if(!empty($subtitle)): ?>
        <div class="subtitle-section">
            <h3>📌 자세히 생각하기</h3>
            <div class="subtitle-text"><?php echo nl2br(htmlspecialchars($subtitle)); ?></div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 우측 컬럼: 자세히 생각하기 섹션 + 추가 질문 -->
    <div class="right-column">
        <div class="thinking-section">
            <h3>🧠 자세히 생각하기</h3>
            <div class="thinking-content" id="detailed-thinking">
                <div style="text-align: center; padding: 20px; color: #999;">
                    <div class="loading-spinner"></div>
                    <p>깊이 있는 설명을 생성하고 있습니다...</p>
                </div>
            </div>
            <div class="thinking-signature">
                - AI 수학 선생님 💡
            </div>
        </div>

        <div class="additional-questions" id="dynamic-questions">
            <div style="text-align: center; padding: 20px; color: #999;">
                <p>추가 질문을 생성 중입니다...</p>
            </div>
        </div>
    </div>
</div>


    <script>
// 페이지 로드 시 자세히 생각하기 및 추가 질문 생성
document.addEventListener('DOMContentLoaded', async function() {
    console.log('[drillingmath.php:DOMContentLoaded] File: <?php echo basename(__FILE__); ?>, Line: DOMContentLoaded');

    await generateDetailedThinking();
});

// 자세히 생각하기 자동 생성 (DB 확인 후 없으면 생성)
async function generateDetailedThinking() {
    const thinkingContent = document.getElementById('detailed-thinking');
    const questionsContainer = document.getElementById('dynamic-questions');

    const subtitle = `<?php echo addslashes($subtitle); ?>`;
    const maintext = `<?php echo addslashes(strip_tags($maintext)); ?>`;
    const contentsid = "<?php echo $contentsid; ?>";
    const contentstype = "<?php echo $contentstype; ?>";
    const nstep = <?php echo isset($nstep) && $nstep > 0 ? $nstep : ($section !== null ? $section + 1 : 1); ?>;

    // 전체 대본 내용
    const fullContext = subtitle || maintext;

    console.log('[drillingmath.php:generateDetailedThinking] Checking existing content...');

    try {
        // 1단계: DB에서 기존 컨텐츠 확인
        const checkResponse = await fetch('check_existing_content.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                contentsid: contentsid,
                contentstype: contentstype,
                nstep: nstep
            })
        });

        if (!checkResponse.ok) {
            throw new Error('DB 확인 실패: ' + checkResponse.status);
        }

        const checkData = await checkResponse.json();

        let data;

        if (checkData.exists) {
            // 기존 데이터 사용
            console.log('[drillingmath.php:generateDetailedThinking] Using existing content from DB');
            data = checkData;
        } else {
            // 2단계: 새로 생성
            console.log('[drillingmath.php:generateDetailedThinking] No existing content, generating new...');

            const generateResponse = await fetch('generate_detailed_thinking.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    context: fullContext,
                    subtitle: subtitle,
                    contentsid: contentsid,
                    contentstype: contentstype,
                    nstep: nstep
                })
            });

            if (!generateResponse.ok) {
                throw new Error('AI 생성 실패: ' + generateResponse.status);
            }

            data = await generateResponse.json();
        }

        if (!data.success) {
            throw new Error(data.error || '알 수 없는 오류');
        }

        // 자세히 생각하기 내용 표시
        thinkingContent.innerHTML = data.thinking.replace(/\n/g, '<br>');

        // 추가 질문 버튼 동적 생성 (답변도 함께 저장됨)
        let questionsHtml = '';
        data.questions.forEach((question, index) => {
            const questionNum = index + 1;
            const answer = data.answers && data.answers[index] ? data.answers[index] : '';

            questionsHtml += `
                <button class="question-button" onclick="toggleAnswer(${questionNum}, '${question.replace(/'/g, "\\'")}', '${answer.replace(/'/g, "\\'")}')">
                    ${question}
                </button>
                <div id="answer-${questionNum}" class="answer-section" style="display: none;">
                    <strong>💡 답변:</strong><br>
                    <div id="answer-content-${questionNum}">${answer.replace(/\n/g, '<br>')}</div>
                </div>
            `;
        });

        questionsContainer.innerHTML = questionsHtml;

        // MathJax 렌더링
        if (typeof MathJax !== 'undefined') {
            MathJax.typesetPromise([thinkingContent, questionsContainer]).catch(function(err) {
                console.error('[drillingmath.php:generateDetailedThinking] MathJax error:', err);
            });
        }

        console.log('[drillingmath.php:generateDetailedThinking] Content displayed successfully');

    } catch (error) {
        console.error('[drillingmath.php:generateDetailedThinking] Error:', error);

        thinkingContent.innerHTML = `
            <div style="color: #f44336; padding: 20px;">
                ⚠️ 설명 생성 중 오류가 발생했습니다.<br>
                ${error.message}
            </div>
        `;

        questionsContainer.innerHTML = `
            <div style="color: #f44336; padding: 20px; text-align: center;">
                질문 생성 실패
            </div>
        `;
    }
}

// 텍스트 복사 함수
function copyTextContent() {
    const textElement = document.getElementById('text-content');
    const text = textElement.innerText || textElement.textContent;

    // 클립보드에 복사
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showCopyNotification();
            console.log('[openai_tts.php:copyTextContent] 텍스트가 클립보드에 복사되었습니다.');
        }).catch(function(err) {
            console.error('[openai_tts.php:copyTextContent] 복사 실패:', err);
            // 폴백 방식
            fallbackCopyText(text);
        });
    } else {
        // 폴백 방식
        fallbackCopyText(text);
    }
}

// 폴백 텍스트 복사 함수 (구형 브라우저용)
function fallbackCopyText(text) {
    const textArea = document.createElement('textarea');
    textArea.value = text;
    textArea.style.position = 'fixed';
    textArea.style.left = '-9999px';
    document.body.appendChild(textArea);
    textArea.select();

    try {
        const successful = document.execCommand('copy');
        if (successful) {
            showCopyNotification();
            console.log('[openai_tts.php:fallbackCopyText] 텍스트가 클립보드에 복사되었습니다 (폴백 방식).');
        } else {
            alert('복사에 실패했습니다.');
        }
    } catch (err) {
        console.error('[openai_tts.php:fallbackCopyText] 복사 실패:', err);
        alert('복사에 실패했습니다.');
    }

    document.body.removeChild(textArea);
}

// 이미지 복사 함수 (fetch 프록시 방식)
async function copyImageContent() {
    const img1 = document.getElementById('content-img1');
    const img2 = document.getElementById('content-img2');

    // 우선순위: img2 -> img1
    const targetImg = img2 || img1;

    if (!targetImg) {
        alert('복사할 이미지가 없습니다.');
        console.log('[openai_tts.php:copyImageContent] 복사할 이미지가 없습니다.');
        return;
    }

    console.log('[openai_tts.php:copyImageContent] 이미지 복사 시작:', targetImg.src);

    try {
        // 방법 1: 프록시를 통해 이미지 가져오기 (CORS 문제 해결)
        let blob;

        try {
            console.log('[openai_tts.php:copyImageContent] 방법 1: 프록시를 통해 이미지 가져오기 시도');

            // 프록시 URL 생성
            const proxyUrl = 'image_proxy.php?url=' + encodeURIComponent(targetImg.src);
            console.log('[openai_tts.php:copyImageContent] 프록시 URL:', proxyUrl);

            const response = await fetch(proxyUrl);

            if (!response.ok) {
                const errorText = await response.text();
                console.error('[openai_tts.php:copyImageContent] 프록시 응답 에러:', errorText);
                throw new Error('프록시 fetch 실패: ' + response.status);
            }

            blob = await response.blob();

            // blob이 이미지인지 확인
            if (!blob.type.startsWith('image/')) {
                throw new Error('이미지 타입이 아님: ' + blob.type);
            }

            console.log('[openai_tts.php:copyImageContent] 프록시 fetch 성공, blob 타입:', blob.type, 'blob 크기:', blob.size);
        } catch (fetchErr) {
            console.log('[openai_tts.php:copyImageContent] 프록시 fetch 실패, Canvas 방식으로 전환:', fetchErr.message);

            // 방법 2: Canvas 방식 (CORS가 허용된 경우에만 작동)
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');

            // 이미지가 이미 로드되어 있으므로 바로 사용
            if (targetImg.complete && targetImg.naturalWidth > 0) {
                canvas.width = targetImg.naturalWidth;
                canvas.height = targetImg.naturalHeight;
                ctx.drawImage(targetImg, 0, 0);
            } else {
                // 이미지가 로드되지 않았으면 새로 로드
                const img = new Image();
                img.crossOrigin = 'anonymous';

                await new Promise((resolve, reject) => {
                    img.onload = resolve;
                    img.onerror = reject;
                    img.src = targetImg.src;
                });

                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
            }

            // Canvas를 Blob으로 변환
            blob = await new Promise((resolve, reject) => {
                canvas.toBlob((b) => {
                    if (b) resolve(b);
                    else reject(new Error('Canvas toBlob 실패'));
                }, 'image/png');
            });

            console.log('[openai_tts.php:copyImageContent] Canvas 방식 성공');
        }

        if (!blob) {
            throw new Error('Blob 생성 실패');
        }

        // ClipboardItem으로 클립보드에 복사
        const item = new ClipboardItem({ [blob.type]: blob });
        await navigator.clipboard.write([item]);

        showCopyNotification();
        console.log('[openai_tts.php:copyImageContent] 이미지가 클립보드에 복사되었습니다. 타입:', blob.type);

    } catch (err) {
        console.error('[openai_tts.php:copyImageContent] 이미지 복사 실패:', err);
        console.error('[openai_tts.php:copyImageContent] 에러 상세:', err.message);

        // 디버깅 정보 출력
        console.log('[openai_tts.php:copyImageContent] 디버깅 정보:');
        console.log('  - 이미지 src:', targetImg.src);
        console.log('  - 이미지 naturalWidth:', targetImg.naturalWidth);
        console.log('  - 이미지 naturalHeight:', targetImg.naturalHeight);
        console.log('  - 이미지 complete:', targetImg.complete);
        console.log('  - navigator.clipboard:', !!navigator.clipboard);
        console.log('  - navigator.clipboard.write:', !!navigator.clipboard?.write);

        // 폴백: 이미지 URL을 텍스트로 복사
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(targetImg.src);
                alert('이미지 복사에 실패했습니다.\n이미지 URL이 클립보드에 복사되었습니다.\n\n원인:\n- 브라우저가 이미지 복사를 지원하지 않음\n- CORS 정책으로 인한 제한\n- HTTPS 연결이 필요함\n\n콘솔(F12)에서 자세한 오류를 확인하세요.');
                console.log('[openai_tts.php:copyImageContent] 폴백: 이미지 URL 복사 완료');
            } else {
                throw new Error('Clipboard API를 사용할 수 없습니다.');
            }
        } catch (err2) {
            console.error('[openai_tts.php:copyImageContent] 폴백 복사도 실패:', err2);
            alert('이미지 복사에 실패했습니다.\n\n수동으로 이미지를 우클릭하여 "이미지 복사"를 선택해주세요.');
        }
    }
}

// 복사 알림 표시 함수
function showCopyNotification() {
    const notification = document.getElementById('copy-notification');
    notification.classList.add('show');

    setTimeout(function() {
        notification.classList.remove('show');
    }, 1500);
}

// 추가 질문 답변 토글 함수 (단순 show/hide)
function toggleAnswer(questionNum, questionText, answerText) {
    const answerSection = document.getElementById('answer-' + questionNum);

    console.log('[drillingmath.php:toggleAnswer] File: <?php echo basename(__FILE__); ?>, Line: toggleAnswer, Question: ' + questionNum);

    // 답변 영역 토글
    if (answerSection.style.display === 'none' || !answerSection.style.display) {
        // 열기
        answerSection.style.display = 'block';
        console.log('[drillingmath.php:toggleAnswer] Showing answer ' + questionNum);
    } else {
        // 닫기
        answerSection.style.display = 'none';
        console.log('[drillingmath.php:toggleAnswer] Hiding answer ' + questionNum);
    }

    // MathJax 렌더링
    if (answerSection.style.display === 'block' && typeof MathJax !== 'undefined') {
        MathJax.typesetPromise([answerSection]).catch(function(err) {
            console.error('[drillingmath.php:toggleAnswer] MathJax error:', err);
        });
}
    </script> 
</body>
</html>