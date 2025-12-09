<?php
// personapairs_form.php

// Moodle 환경 설정
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 설정
error_reporting(0);
ini_set('display_errors', 0);

// GET 파라미터
$userid  = $_GET["userid"];
$cnttype = $_GET["cnttype"];
$cntid   = $_GET["cntid"];

if($cnttype==1) {
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id='$cntid' ORDER BY id DESC LIMIT 1");  
    $eventid = 1;
    $guidetext1 = $cnttext->reflections0;
    $guidetext2 = $cnttext->reflections1;
    $maintext   = $cnttext->maintext;
    
    $getimgbk = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id='$cntid' ORDER BY id DESC LIMIT 1");
    $ctextbk = $getimgbk->pageicontent;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctextbk);
    $imageTags2 = $htmlDom->getElementsByTagName('img');
    $extractedImages = array();
    $nimg = 0;
    foreach($imageTags2 as $imageTag2) {
        $nimg++;
        $imgSrc1 = $imageTag2->getAttribute('src');
        $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
        if ((strpos($imgSrc1, 'Contents/MATH%20MATRIX/MATH%20images') !== false || strpos($imgSrc1, 'ContentsIMG') !== false) &&
            (strpos($imgSrc1, '.png') !== false || strpos($imgSrc1, '.jpg') !== false)) {
            break;
        }
    }
    // $imgSrc2가 없는 경우 빈값 할당
    $imgSrc2 = "";
}
elseif($cnttype==2) {
    $cnttext = $DB->get_record_sql("SELECT * FROM mdl_question WHERE id='$cntid' ORDER BY id DESC LIMIT 1");  
    $guidetext1 = $cnttext->reflections0;
    $guidetext2 = $cnttext->reflections1;
    $maintext   = $cnttext->mathexpression;
    $soltext    = $cnttext->ans1;
    
    $qtext0 = $DB->get_record_sql("SELECT questiontext, generalfeedback FROM mdl_question WHERE id='$cntid' ORDER BY id DESC LIMIT 1 ");
    
    // 첫번째 HTML에서 이미지 추출
    $htmlDom1 = new DOMDocument;
    @$htmlDom1->loadHTML($qtext0->generalfeedback);
    $imageTags1 = $htmlDom1->getElementsByTagName('img');
    $extractedImages = array();
    $nimg = 0;
    foreach($imageTags1 as $imageTag1) {
        $nimg++; 
        $imgSrc1 = $imageTag1->getAttribute('src'); 
        $imgSrc1 = str_replace(' ', '%20', $imgSrc1); 
        if (strpos($imgSrc1, 'MATRIX/MATH') !== false && strpos($imgSrc1, 'hintimages') === false) {
            break;
        }
    }
    
    // 두번째 HTML에서 이미지 추출
    $htmlDom2 = new DOMDocument;
    @$htmlDom2->loadHTML($qtext0->questiontext);
    $imageTags2 = $htmlDom2->getElementsByTagName('img');
    $extractedImages = array();
    $nimg = 0;
    foreach($imageTags2 as $imageTag2) {
        $nimg++; 
        $imgSrc2 = $imageTag2->getAttribute('src'); 
        $imgSrc2 = str_replace(' ', '%20', $imgSrc2); 
        if (strpos($imgSrc2, 'hintimages') === false && (strpos($imgSrc2, '.png') !== false || strpos($imgSrc2, '.jpg') !== false)) {
            break;
        }
    }
}

// 이미지 태그 생성 (이미지 복사 기능을 위해 클래스와 클릭 스타일 추가)
if(!empty($imgSrc1)) {
    $cnttext1 = '<img src="'.$imgSrc1.'" width="200" class="copyable-img" style="cursor: pointer;">';
} else {
    $cnttext1 = "";
}
if(!empty($imgSrc2)) {
    $cnttext2 = '<img src="'.$imgSrc2.'" width="200" class="copyable-img" style="cursor: pointer;">';
} else {
    $cnttext2 = "";
}
     
$cntimgs = '<td valign="top">'.$cnttext2.'</td><td valign="top">'.$cnttext1.'</td>';

$textareas = '
<table width="100%">
  <tr>
    <td valign="top" class="copyable" style="cursor: pointer; background-color:#D5F3FE;" width="30%">
      다음 내용 분석한다음 출력형식에 맞추어 페르소나 정보 생성해줘. 반드시 출력형식과 같은 jason 형식으로 생성해줘.
      | '.$maintext.'
    </td>
    '.$cntimgs.'
    <td valign="top" width="40%">
      <textarea id="json-input" style="width:100%; color:black; height:600px; white-space:pre-wrap;" placeholder="생성된 내용을 입력하세요"></textarea>
    </td>
  </tr>
  <tr><td align="center">
  <button style="background-color:#007bff; color:white; font-size:14px;" class="persona-link" onclick="window.open(\'https://chatgpt.com/g/g-6798cec5f9ec81918767cde8c32655ad-hagseub-pereusona-saengseonggi\', \'_blank\')">페르소나 생성기</button></td><td></td><td></td><td align="center">
  <button style="background-color:#007bff; color:white; font-size:14px;" class="persona-link" onclick="savePersonaPairs()">DB 저장하기</button>
  </td></tr>
</table>';

echo '
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css"> 
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
<link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script src="//code.jquery.com/ui/1.8.18/jquery-ui.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@10.13.0/dist/sweetalert2.all.min.js"></script> 	
<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

<script>
// 텍스트 복사 함수 (HTML 문자열 그대로 복사)
function copyHtmlToClipboard(html) {
    var tempTextarea = document.createElement("textarea");
    tempTextarea.style.position = "absolute";
    tempTextarea.style.left = "-1000px";
    tempTextarea.style.top = "-1000px";
    tempTextarea.value = html;
    document.body.appendChild(tempTextarea);
    tempTextarea.select();
    document.execCommand("copy");
    document.body.removeChild(tempTextarea);
}

// blob을 PNG로 변환하는 함수 (캔버스 사용)
async function convertBlobToPNG(blob) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        // crossOrigin 설정 (필요시)
        img.crossOrigin = "anonymous";
        const objectUrl = URL.createObjectURL(blob);
        img.onload = function() {
            URL.revokeObjectURL(objectUrl);
            const canvas = document.createElement("canvas");
            canvas.width = img.width;
            canvas.height = img.height;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(img, 0, 0);
            canvas.toBlob(function(pngBlob) {
                if (pngBlob) {
                    resolve(pngBlob);
                } else {
                    reject(new Error("캔버스 변환에 실패했습니다."));
                }
            }, "image/png");
        };
        img.onerror = function() {
            URL.revokeObjectURL(objectUrl);
            reject(new Error("이미지 로딩에 실패했습니다."));
        };
        img.src = objectUrl;
    });
}

document.addEventListener("DOMContentLoaded", () => {
    // 텍스트 복사 이벤트
    document.querySelectorAll("td.copyable").forEach(td => {
        td.addEventListener("click", () => {
            copyHtmlToClipboard(td.innerHTML);
            Swal.fire({
                title: "복사되었습니다.",
                timer: 500,
                showConfirmButton: false
            });
        });
    });
    // 이미지 복사 이벤트 (실제 이미지 데이터를 클립보드로 복사)
    document.querySelectorAll("img.copyable-img").forEach(img => {
        img.addEventListener("click", async () => {
            if (navigator.clipboard && navigator.clipboard.write) {
                try {
                    // 이미지 URL을 fetch 하여 blob으로 변환
                    const response = await fetch(img.src);
                    let blob = await response.blob();
                    // Clipboard API가 image/jpeg 등 일부 포맷을 지원하지 않을 수 있으므로 PNG로 변환
                    if (blob.type !== "image/png") {
                        blob = await convertBlobToPNG(blob);
                    }
                    const item = new ClipboardItem({ [blob.type]: blob });
                    await navigator.clipboard.write([item]);
                    Swal.fire({
                        title: "이미지가 클립보드에 복사되었습니다.",
                        timer: 500,
                        showConfirmButton: false
                    });
                } catch (error) {
                    Swal.fire({
                        title: "이미지 복사 실패",
                        text: error.message,
                        icon: "error"
                    });
                }
            } else {
                Swal.fire({
                    title: "브라우저가 이 기능을 지원하지 않습니다.",
                    icon: "warning"
                });
            }
        });
    });
});
</script>
';

// (예시) wboardid 가져오기
$thisboard = $DB->get_record_sql("
  SELECT wboardid
    FROM mdl_abessi_messages
   WHERE userid = :userid
ORDER BY timemodified DESC
   LIMIT 1
", ['userid' => $userid]);
$wboardid = $thisboard ? $thisboard->wboardid : '';

// (예시) 컨텐츠 본문 가져오기
if ($cnttype == 1) {
  $row = $DB->get_record_sql("
    SELECT *
      FROM mdl_icontent_pages
     WHERE id = :cntid
  ", ['cntid' => $cntid]);
}
elseif ($cnttype == 2) {
  $row = $DB->get_record_sql("
    SELECT *
      FROM mdl_question
     WHERE id = :cntid
  ", ['cntid' => $cntid]);
}
else {
  $contentstext = "해당 컨텐츠 타입($cnttype)은 예시가 없습니다.";
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>페르소나 입력 폼 (아이콘도 DB 저장)</title>
  <!-- jQuery & SweetAlert2 (Ajax, 알림) -->
  <script src="https://code.jquery.com/jquery-3.5.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
  
  <?php echo $textareas; ?>
 
  <br>
  <style>
  .persona-link {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #007bff;
    color: #fff !important;
    text-decoration: none;
    border-radius: 4px;
    margin-bottom: 2rem;
    transition: background 0.3s ease;
  }
  .persona-link:hover {
    background: #0056b3;
  }
  </style>
  <script>
  function savePersonaPairs() {
    const jsonStr = document.getElementById("json-input").value.trim();
    
    // JSON 입력값이 비어있는지 확인
    if (!jsonStr) {
      Swal.fire("오류", "JSON 입력값이 비어있습니다.", "error");
      return;
    }
    
    let personaData;
    try {
      personaData = JSON.parse(jsonStr);
    } catch(e) {
      console.error(e);
      Swal.fire("오류", "JSON 형식이 잘못되었습니다: " + e.message, "error");
      return;
    }
  
    const negList = personaData.negative_persona || [];
    const posList = personaData.positive_persona || [];
  
    // npersona1..6 / ppersona1..6 매칭
    let pairs = [];
    for (let i = 1; i <= 6; i++){
      const neg = negList.find(item => item.id === "npersona" + i);
      const pos = posList.find(item => item.id === "ppersona" + i);
      if (neg && pos) {
        pairs.push({
          nindex: i,               // 1..6
          neg_icon: neg.icon || "",// icon
          neg_name: neg.name,
          neg_desc: neg.description,
          pos_name: pos.name,
          pos_desc: pos.description,
          pos_enepoem: pos.enepoem || ""
        });
      }
      setTimeout(function() {
        window.parent.location.reload();
      }, 1000);
    }
  
    if (pairs.length === 0) {
      Swal.fire("알림", "npersonaX / ppersonaX에 해당하는 매칭이 없습니다.", "warning");
      return;
    }
  
    // 서버 전송
    $.ajax({
      url: "savepersonas.php",
      type: "POST",
      dataType: "json",
      data: {
        eventid: "1",
        wboardid: "<?php echo $wboardid; ?>",
        contentstype: "<?php echo $cnttype; ?>",
        contentsid: "<?php echo $cntid; ?>",
        persona_pairs: JSON.stringify(pairs)
      },
      success: function(res) {
        if (res.status === "success") {
          Swal.fire("완료", res.msg, "success");
        } else {
          Swal.fire("에러", "DB 저장 실패: " + (res.msg || ""), "error");
        }
      },
      error: function(err) {
        console.error("에러:", err);
        Swal.fire("에러", "통신 중 오류가 발생했습니다.", "error");
      }
    });
  }
  </script>
</body>
</html>
