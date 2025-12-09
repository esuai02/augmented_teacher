<!DOCTYPE html>
<html>
<style>
  * {
    box-sizing: border-box;
  }
  
  @media print {
    div {
      page-break-inside: avoid;
    }
  }

  body {
    margin: 0;
    font-family: Arial, sans-serif;
    background-color: #fff; /* 전체 배경 흰색 */
  }

  /* 스크롤 중 상단에 고정할 영역 */
  .top-bar {
    position: sticky;
    top: 0;
    z-index: 9999; 
    background: #fff; /* 상단 바 배경 흰색 */
    border-bottom: 2px solid #ccc;
  }

  /* 영화필름 컨테이너 */
  #filmstrip {
    width: 90%;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    position: relative;
    background: #fff;          /* 배경을 흰색으로 변경 */
    border: 0.5px double #aaa; /* 필름 테두리 느낌 유지 */
  }

  /* 실제 슬라이드 영역 */
  #imageSlides {
    width: 100%;
    position: relative;
  }

  /* 사진 정렬 */
  .column1,
  .column2 {
    width: 100%;
    margin-bottom: 20px;
    text-align: center;
  }

  .column1 img,
  .column2 img {
    display: block;
    max-width: 70%;
    height: auto;
    margin: 0 auto;
    background-color: #fff;     /* 이미지 주변 배경 흰색으로 변경 */
    border: 3px solid lightgray;
  }

  /* 체크리스트 버튼 (필요 시 유지) */
  #checklistBtn {
    display: none; /* 초기에는 숨김 */
    margin: 20px auto;
    padding: 10px 20px;
    font-size: 18px;
    cursor: pointer;
    background: #f90;
    color: #fff;
    border: none;
    border-radius: 6px;
  }

  /* Up / Down 버튼 */
  .slider-controls button {
    margin: 5px;
    padding: 10px 20px;
    font-size: 18px;
    cursor: pointer;
    background: #f90;
    color: #fff;
    border: none;
    border-radius: 6px;
  }

  /******************************************************************
   * 상단/하단 Blur 처리를 위한 오버레이 (높이 2배로, 점진적 불투명도)
   ******************************************************************/
  .blur-overlay {
    content: "";
    position: fixed;
    left: 0;
    width: 100%;
    height: 120px;            /* 2배 높이로 변경 */
    pointer-events: none;     /* 마우스 이벤트 차단 */
    backdrop-filter: blur(10px); /* Blur 강도 조절 */
    -webkit-backdrop-filter: blur(15px);
    z-index: 9998;            /* 상단바(9999) 뒤쪽에서 내용만 흐림 */
  }

  /* 위쪽 Blur 그라데이션: 위에서부터 천천히 투명해지도록 다중 스탑 적용 */
  .blur-top {
    top: 0;
    background: linear-gradient(
      to bottom,
      rgba(255, 255, 255, 1)   0%,   /* 완전 불투명 */
      rgba(255, 255, 255, 0.9) 20%,  
      rgba(255, 255, 255, 0.5) 50%,  
      rgba(255, 255, 255, 0.2) 80%,  
      rgba(255, 255, 255, 0)   100%  /* 완전 투명 */
    );
  }

  /* 아래쪽 Blur 그라데이션: 아래에서부터 천천히 투명해지도록 다중 스탑 적용 */
  .blur-bottom {
    bottom: 0;
    background: linear-gradient(
      to top,
      rgba(255, 255, 255, 1)   0%,  
      rgba(255, 255, 255, 0.9) 20%, 
      rgba(255, 255, 255, 0.5) 50%,  
      rgba(255, 255, 255, 0.2) 80%, 
      rgba(255, 255, 255, 0)   100%
    );
  }
</style>
<body>

<!-- Blur 오버레이: 상단/하단 2개 -->
<div class="blur-overlay blur-top"></div>
<div class="blur-overlay blur-bottom"></div>

<!-- PHP 부분 시작 -->
<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
$studentid= $_GET["userid"];
$tbegin= $_GET["tb"];
$tend= $_GET["te"];
if($studentid==NULL) $studentid=$USER->id;
require_login();
$timecreated=time(); 
$hoursago=$timecreated-43200;
$aweekago=$timecreated-604800;
$thisuser= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");
$stdname=$thisuser->firstname.$thisuser->lastname;

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;

$chapterlog= $DB->get_record_sql("SELECT * FROM mdl_abessi_chapterlog WHERE userid='$studentid' ORDER BY id DESC LIMIT 1 ");

if($tbegin==NULL) {
  $handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND timemodified > '$hoursago' ORDER BY timemodified DESC LIMIT 100");
} else {
  $handwriting=$DB->get_records_sql("SELECT * FROM mdl_abessi_messages WHERE userid='$studentid' AND active='1' AND timemodified > '$tbegin' AND timemodified < '$tend' ORDER BY timemodified DESC LIMIT 100");
}
$result = json_decode(json_encode($handwriting), True);

$quizstatus=0;
$eventspaceanalysis='<a style="text-decoration:none;" href="https://mathking.kr/moodle/local/augmented_teacher/teachers/synergetic_timeline.php?userid='.$studentid.'">📊</a>';
$ForDeepLearning='<a href="https://mathking.kr/moodle/local/augmented_teacher/students/synergetic_step.php?userid='.$studentid.'">
<img loading="lazy" src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1651023487.png" width=40></a>';

$imagegrid = '';
foreach($result as $value) {
  $wboardid = $value['wboardid'];
  $status   = $value['status'];
  if($status==='commitquiz' || $status==='reflect' || $status==='examplenote') continue;

  $contentsid   = $value['contentsid'];
  $ncommit      = $value['feedback'];
  if($ncommit!=0) $ncommit='<b style="color:#FF0000;">'.$ncommit.'</b>';

  $timestamp = $timecreated - $value['timemodified'];
  if($timestamp <= 60)          $timestamp = $timestamp.'초 전';
  else if($timestamp<=3600)     $timestamp = round($timestamp/60,0).'분 전';
  else if($timestamp<=86400)    $timestamp = round($timestamp/3600,0).'시간 전';
  else if($timestamp<=2592000)  $timestamp = round($timestamp/86400,0).'일 전';

  // column1 or column2
  if(strpos($wboardid, 'jnrsorksqcrark') !== false) {
    // 전자책
    $noteurl = $value['url'];
    $getimg  = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id ='$contentsid' ");
    $ctext   = $getimg->pageicontent;
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($ctext);
    $imageTags = $htmlDom->getElementsByTagName('img');
    $imgSrc = '';
    foreach($imageTags as $imageTag) {
      $imgSrc = $imageTag->getAttribute('src');
      $imgSrc = str_replace(' ', '%20', $imgSrc);
      if(strpos($imgSrc, 'MATRIX')!== false || strpos($imgSrc, 'MATH')!== false || strpos($imgSrc, 'imgur')!== false) {
        break;
      }
    }
    if($imgSrc=='') $imgSrc='https://via.placeholder.com/300x200?text=No+Image';
    $imagegrid.='
      <div class="column1">
        <a href="https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?'.$noteurl.'" target="_blank">
          <img loading="lazy" src="'.$imgSrc.'">
        </a>
      </div>';

  } else {
    // 일반 문제
    $qtext = $DB->get_record_sql("SELECT questiontext,reflections1 FROM mdl_question WHERE id='$contentsid'");
    $htmlDom = new DOMDocument; 
    @$htmlDom->loadHTML($qtext->questiontext); 
    $imageTags = $htmlDom->getElementsByTagName('img');
    $imgSrc = '';
    foreach($imageTags as $imageTag) {
      $imgSrc = $imageTag->getAttribute('src');
      $imgSrc = str_replace(' ', '%20', $imgSrc);
      if(strpos($imgSrc, 'MATRIX/MATH')!== false || strpos($imgSrc, 'HintIMG')!== false) {
        break;
      }
    }
    if($imgSrc=='') $imgSrc='https://via.placeholder.com/300x200?text=No+Image';
    $imagegrid.='
      <div class="column2">
        <a href="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_onair.php?mode=1&userid='.$studentid.'&wboardid='.$wboardid.'" target="_blank">
          <img loading="lazy" src="'.$imgSrc.'">
        </a>
      </div>';
  }
}

if($quizstatus==1) $currentstatus='응시중';
else $currentstatus='검토';
?>

<!-- 상단 고정 바 -->
<div class="top-bar"> 
  <table align="right">
    <tr>
      <td>
        <button id="prevBtn">↑ Up</button>
        <button id="nextBtn">↓ Down</button>
      </td>
    </tr>
  </table>
</div>

<!-- 영화필름 스타일 컨테이너 -->
<div id="filmstrip">
  <div id="imageSlides">
    <?php echo $imagegrid; ?>
  </div>
</div>

<!-- 체크리스트 버튼 (필요 시 유지) -->
<button id="checklistBtn" onclick="alert('체크리스트를 확인하세요!')">
  체크리스트 보기
</button>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Up / Down 버튼으로 개별 이미지 단위로 스크롤 이동
  const images       = document.querySelectorAll('#imageSlides .column1, #imageSlides .column2');
  const prevBtn      = document.getElementById('prevBtn');
  const nextBtn      = document.getElementById('nextBtn');
  let currentIndex   = 0; // 현재 몇 번째 이미지를 보고 있는지

  // 특정 인덱스 이미지로 부드럽게 스크롤
  function scrollToImage(index) {
    if (index >= 0 && index < images.length) {
      images[index].scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });
    }
  }

  // 초기 첫 이미지를 기준으로
  scrollToImage(currentIndex);

  // 위로 이동
  prevBtn.addEventListener('click', () => {
    if (currentIndex > 0) {
      currentIndex--;
      scrollToImage(currentIndex);
    }
  });

  // 아래로 이동
  nextBtn.addEventListener('click', () => {
    if (currentIndex < images.length - 1) {
      currentIndex++;
      scrollToImage(currentIndex);
    }
  });

  // 체크리스트 버튼 표시 예시
  // document.getElementById('checklistBtn').style.display = 'inline-block';
});
</script>
</body>
</html>
