<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php"); 
global $DB, $USER;

// 페이지 방문 카운트
include("../pagecount.php");

// 파라미터 받기
$studentid = required_param('id', PARAM_INT);
$tbegin    = required_param('tb', PARAM_INT);
$initialT  = time() - $tbegin;

// URL 체크
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
  $url = "https://";   
} else {
  $url = "http://";   
}
$url .= $_SERVER['HTTP_HOST'];   
$url .= $_SERVER['REQUEST_URI']; 

if (strpos($url, 'tbegin') !== false) {
  $tbegin = required_param('tbegin', PARAM_INT); 
} else {
  $tbegin = time();
}

// 사용자 이름 불러오기
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");

// 최근 3개월 간의 메모 불러오기 (timescaffolding.php의 메모장 내용)
$threeMonthsAgo = time() - (90 * 24 * 60 * 60); // 3개월 = 약 90일
$notes = $DB->get_records_sql(
    "SELECT sn.*, uid.data AS author_role 
     FROM {abessi_stickynotes} sn 
     LEFT JOIN {user_info_data} uid ON sn.authorid = uid.userid AND uid.fieldid = 22
     WHERE sn.userid = ? AND sn.hide = 0 AND sn.type = 'timescaffolding' AND sn.created_at >= ?
     ORDER BY sn.created_at DESC", 
    [$studentid, $threeMonthsAgo]
);

// 선생님 메모와 학생 메모로 분리
$teacherNotes = [];
$studentNotes = [];

if ($notes) {
    foreach ($notes as $note) {
        $noteArray = (array)$note;
        if (isset($noteArray['author_role']) && $noteArray['author_role'] === 'student') {
            $studentNotes[] = $noteArray;
        } else {
            $teacherNotes[] = $noteArray;
        }
    }
}

// 포스트잇 HTML 생성 함수
function createStickyNoteHTML($note, $type) {
    $color = isset($note['color']) ? $note['color'] : 'yellow';
    $rawContent = isset($note['content']) ? $note['content'] : '';
    $createdAt = isset($note['created_at']) ? (int)$note['created_at'] : time();
    $dateStr = date('Y-m-d', $createdAt);
    $timeStr = date('H:i', $createdAt);
    
    // 이미지 태그가 있으면 HTML 그대로 사용, 없으면 이스케이프 처리
    if (strpos($rawContent, '<img') !== false || strpos($rawContent, '<') !== false) {
        $content = $rawContent; // HTML 태그가 포함된 경우 그대로 사용
    } else {
        $content = htmlspecialchars($rawContent, ENT_QUOTES, 'UTF-8'); // 텍스트만 있는 경우 이스케이프
    }
    
    return '<div class="sticky-note-item sticky-note-' . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . '">
        <div class="sticky-note-header">
            <span class="sticky-note-date">' . htmlspecialchars($dateStr, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($timeStr, ENT_QUOTES, 'UTF-8') . '</span>
        </div>
        <div class="sticky-note-content">' . $content . '</div>
    </div>';
}

$teacherNotesHTML = '';
$studentNotesHTML = '';

if (!empty($teacherNotes)) {
    foreach ($teacherNotes as $note) {
        $teacherNotesHTML .= createStickyNoteHTML($note, 'teacher');
    }
} else {
    $teacherNotesHTML = '<div class="empty-notes">선생님 메모가 없습니다.</div>';
}

if (!empty($studentNotes)) {
    foreach ($studentNotes as $note) {
        $studentNotesHTML .= createStickyNoteHTML($note, 'student');
    }
} else {
    $studentNotesHTML = '<div class="empty-notes">학생 메모가 없습니다.</div>';
}

// 귀가검사 보고서 이미지 링크 가져오기 (파일 시스템에서 직접 찾기)
$goinghomeImagesHTML = '';
try {
    $studentNameForFile = str_replace(' ', '', $username->firstname . $username->lastname);
    $imageDir = '/home/moodle/public_html/studentimg/';
    
    if (is_dir($imageDir)) {
        // 파일명 패턴: 귀가검사결과_{학생이름}_{날짜}_{요일}.jpg
        $pattern = '귀가검사결과_' . preg_quote($studentNameForFile, '/') . '_*.jpg';
        $files = glob($imageDir . $pattern);
        
        if ($files && count($files) > 0) {
            // 파일명으로 정렬 (최신순)
            usort($files, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // 최근 10개만 표시
            $files = array_slice($files, 0, 10);
            
            $goinghomeImagesHTML = '<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">';
            $goinghomeImagesHTML .= '<div style="font-size: 0.9rem; font-weight: bold; margin-bottom: 0.5rem; color: #333;">📋 귀가검사 결과</div>';
            $goinghomeImagesHTML .= '<div style="display: flex; flex-direction: column; gap: 0.5rem;">';
            
            foreach ($files as $filePath) {
                $filename = basename($filePath);
                $fileTime = filemtime($filePath);
                $imageDate = date('Y-m-d', $fileTime);
                $dayOfWeek = ['일', '월', '화', '수', '목', '금', '토'][date('w', $fileTime)];
                $imageUrl = 'https://mathking.kr/studentimg/' . $filename;
                $linkText = '귀가검사 결과 (' . $imageDate . ', ' . $dayOfWeek . ')';
                
                $goinghomeImagesHTML .= '<div style="padding: 0.5rem; background: #f0f9ff; border-radius: 4px; border: 1px solid #3b82f6;">';
                $goinghomeImagesHTML .= '<a href="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" style="color: #3b82f6; text-decoration: underline; font-size: 0.85rem;">';
                $goinghomeImagesHTML .= htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8');
                $goinghomeImagesHTML .= '</a>';
                $goinghomeImagesHTML .= '</div>';
            }
            
            $goinghomeImagesHTML .= '</div></div>';
        }
    }
} catch (Exception $e) {
    // 에러 발생 시 로그만 남기고 계속 진행
    error_log('timelineWeek.php: 귀가검사 이미지 조회 오류 (line ' . __LINE__ . '): ' . $e->getMessage());
}

// 미션/목표 리스트 불러오기
$missionlist = $DB->get_records_sql("
    SELECT * 
    FROM mdl_abessi_progress 
    WHERE userid='$studentid' 
      AND hide=0 
    ORDER BY deadline DESC 
    LIMIT 1
");
$result = json_decode(json_encode($missionlist), true);

$timeline1 = '';
foreach ($result as $value) {
  $missionid  = $value['id'];
  $plantype   = $value['plantype'];
  $text       = $value['memo'];
  $deadline   = $value['deadline'];
  $dateString = date("m-d", $deadline);

  if ($value['complete'] == 1) {
    $checkdeadline = '<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422637.png" width=20>';
  } elseif (time() > $deadline) {
    $checkdeadline = '<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641423140.png" width=20>';
  } elseif (time() <= $deadline && $deadline - time() < 604800) {
    $checkdeadline = '<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641424532.png" width=20>';
  } else {
    $checkdeadline = '<img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1641422011.png" width=20>';
  }

  if ($plantype === '분기목표') {
    $plantype = '<span style="color:red;">분기목표</span>  : ';
  } elseif ($plantype === '방향설정') {
    $plantype = '<span style="color:green;">진행순서</span>  : ';
  } elseif ($plantype === '장기계획') {
    // 필요시 추가 처리 가능
  }
  
  $timeline1 .= '
    <h6 class="timeline-title">
      ' . $plantype . '' . $text . ' ' . $dateString . ' ' . $checkdeadline . '
    </h6>';
}

// 주간 목표
$Weekly = $DB->get_record_sql("
    SELECT MIN(timecreated) AS tmin 
    FROM mdl_abessi_today 
    WHERE userid='$studentid' 
      AND timecreated > '$initialT' 
      AND type LIKE '주간목표'
");

$amonthago    = time() - 604800*4;
$WeekTimeline = $DB->get_records_sql("
    SELECT * 
    FROM mdl_abessi_today 
    WHERE userid='$studentid' 
      AND timecreated >= '$amonthago' 
    ORDER BY id
");
$result   = json_decode(json_encode($WeekTimeline), true);
$timeline = '';

foreach ($result as $value) {
  $timecreated = date("m월 d일", $value['timecreated']);
  $goalid      = $value['id'];

  if ($value['type'] === '오늘목표' || $value['type'] === '검사요청') {
    $timeline .= '# ' . $value['type'] . ' : ' . $value['text'] . '  ' . $timecreated . '<hr>';
  }
  if ($value['type'] === '주간목표') {
    $timeline .= '<span style="color:blue;"># ' . $value['type'] . ' : ' . $value['text'] . ' </span> ' . $timecreated . '<hr>';
  }
}

// HTML 출력 (첫 번째 코드 스타일로 적용)
echo '
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- 반응형 설정 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>목표 및 일정</title>
  
  <link rel="icon" href="../assets/img/favicon.ico" type="image/x-icon"/>
  
  <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
  <script>
    WebFont.load({
      google: {families:["Montserrat:100,200,300,400,500,600,700,800,900"]},
      custom: {families:["Flaticon", "LineAwesome"], urls: ["../assets/css/fonts.css"]},
      active: function() {
        sessionStorage.fonts = true;
      }
    });
  </script>

  <!-- 부트스트랩 CSS -->
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="../assets/css/ready.min.css">
  <link rel="stylesheet" href="../assets/css/demo.css">
  
  <style>
    /* 모바일 뷰 컨테이너 - PC에서도 모바일처럼 보이게 */
    body {
      font-size: 0.7rem;
      background-color: #f9f9f9;
      padding-top: 60px;
      margin: 0;
    }
    
    .mobile-container {
      max-width: 480px;
      margin: 0 auto;
      background: #fff;
      min-height: 100vh;
      box-shadow: 0 0 20px rgba(0,0,0,0.1);
    }
    
    /* 표와 타임라인을 감싸는 카드 컨테이너 */
    .content-card-wrapper {
      background-color: #fff;
      background: #fff;
      padding-bottom: 2rem;
      min-height: calc(100vh - 120px);
      position: relative;
    }
    
    /* 스크롤 끝까지 배경이 이어지도록 */
    .wrapper {
      min-height: calc(100vh - 60px);
    }
    
    .card {
      background-color: #fff;
      border-radius: 6px;
      margin-bottom: 0.7rem;
      font-size: 0.7rem;
    }
    .card-title {
      font-size: 0.75rem;
      margin-bottom: 0.5rem;
      font-weight: bold;
    }
    .table td {
      padding: 0.4rem 0.3rem;
      font-size: 0.7rem;
    }
    .table th {
      font-size: 0.65rem;
      padding: 0.4rem 0.3rem;
    }
    .timeline-title {
      font-size: 0.7rem;
      margin-bottom: 0.5rem;
    }
    .btn-sm {
      padding: 0.3rem 0.5rem;
      font-size: 0.7rem;
    }
    
    /* 포스트잇 메모 스타일 */
    .sticky-notes-section {
      margin-bottom: 2rem;
      padding: 1rem;
      background: #fff;
      border-radius: 8px;
    }
    
    .sticky-notes-header {
      font-size: 1rem;
      font-weight: bold;
      margin-bottom: 1rem;
      color: #333;
    }
    
    .sticky-notes-container {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    
    .notes-column {
      width: 100%;
      min-width: 100%;
    }
    
    .notes-column-title {
      font-size: 0.9rem;
      font-weight: bold;
      margin-bottom: 0.8rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #ddd;
    }
    
    .teacher-notes-title {
      color: #0066cc;
    }
    
    .student-notes-title {
      color: #cc6600;
    }
    
    .sticky-notes-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }
    
    .sticky-note-item {
      position: relative;
      padding: 1rem;
      min-height: 120px;
      box-shadow: 2px 2px 8px rgba(0,0,0,0.15);
      transform: rotate(-1deg);
      transition: transform 0.2s;
      cursor: default;
    }
    
    .sticky-note-item:nth-child(even) {
      transform: rotate(1deg);
    }
    
    .sticky-note-item:hover {
      transform: rotate(0deg) scale(1.02);
      box-shadow: 3px 3px 12px rgba(0,0,0,0.2);
    }
    
    .sticky-note-yellow {
      background: #ffeb3b;
      border-left: 4px solid #fbc02d;
    }
    
    .sticky-note-green {
      background: #c8e6c9;
      border-left: 4px solid #66bb6a;
    }
    
    .sticky-note-blue {
      background: #bbdefb;
      border-left: 4px solid #42a5f5;
    }
    
    .sticky-note-pink {
      background: #f8bbd0;
      border-left: 4px solid #ec407a;
    }
    
    .sticky-note-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
      padding-bottom: 0.3rem;
      border-bottom: 1px solid rgba(0,0,0,0.1);
    }
    
    .sticky-note-date {
      font-size: 0.75rem;
      color: #666;
      font-weight: 500;
    }
    
    .sticky-note-content {
      font-size: 0.85rem;
      line-height: 1.5;
      color: #333;
      word-wrap: break-word;
    }
    
    .sticky-note-content img {
      max-width: 100%;
      height: auto;
      border-radius: 4px;
      margin-top: 0.5rem;
    }
    
    .empty-notes {
      text-align: center;
      padding: 2rem;
      color: #999;
      font-style: italic;
      background: #fff;
      border-radius: 4px;
      border: 1px dashed #ddd;
    }
    
    .goinghome-report-btn {
      display: block;
      width: 100%;
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      background: #f5f5f5;
      border: 1px solid #e0e0e0;
      border-radius: 4px;
      text-align: center;
      text-decoration: none;
      color: #666;
      font-size: 0.85rem;
      font-weight: 500;
      transition: all 0.2s ease;
      box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    
    .goinghome-report-btn:hover {
      background: #eeeeee;
      border-color: #d0d0d0;
      color: #333;
      box-shadow: 0 2px 4px rgba(0,0,0,0.08);
      text-decoration: none;
    }
    
  </style>
</head>
<body>
  <div class="mobile-container">
    <div class="top-menu">
      <table align="center" style="width: 100%;">
        <tr>
          <td style="padding: 0.2rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일정</a>
          </td>
          <td style="padding: 0.2rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800" class="btn btn-sm btn-danger" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">계획</a>
          </td>
          <td style="padding: 0.2rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/mathpomodoro.php?userid='.$studentid.'" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일지</a>
          </td>
          <td style="padding: 0.2rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">오늘</a>
          </td>
        </tr>
      </table>
    </div>
    <div class="wrapper">
      <div class="content">
        <div class="content-card-wrapper">
          <div class="container-fluid py-2 px-2">
    
          <!-- 상단 안내 -->
          <div class="text-center mb-2">
            <span style="font-size:0.95rem; font-weight:bold;">
              '.$username->firstname.$username->lastname.'의 학습목표 기록
            </span>
          </div>
          
          <!-- 포스트잇 메모 섹션 -->
          <div class="sticky-notes-section">
          
            <div class="sticky-notes-container">
              <div class="notes-column">
                <div class="notes-column-title teacher-notes-title">👨‍🏫 선생님 메모</div>
                <div class="sticky-notes-list">
                  '.$teacherNotesHTML.'
                </div>
              </div>
              <div class="notes-column">
                <div class="notes-column-title student-notes-title">👨‍🎓 '.$username->firstname.$username->lastname.' 메모</div>
                <div class="sticky-notes-list">
                  '.$studentNotesHTML.'
                </div>
                '.$goinghomeImagesHTML.'
              </div>
            </div>
          
          
          <div class="row mt-3">
            <div class="col-md-12">
              <hr>
                ' . $timeline1 . '
              <hr style="border: solid 1.5px orange;">
                ' . $timeline . '
            </div>
          </div>
          </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  </div>
<style>
    /* 상단 가로 메뉴 - 모바일 스타일 */
    .top-menu {
      position: fixed;
      top: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 100%;
      max-width: 480px;
      background: #f8f8f8;
      padding: 0.5rem;
      border-bottom: 1px solid #ddd;
      z-index: 50;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .top-menu table {
      width: 100%;
      margin: 0;
    }
    .top-menu td {
      width: 25%;
    }
    .top-menu .btn {
      width: 100%;
      font-size: 0.7rem;
      padding: 0.3rem 0.4rem;
      white-space: nowrap;
    }
</style>
  <!-- Core JS Files -->
  <script src="../assets/js/core/jquery.3.2.1.min.js"></script>
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  
  <!-- jQuery UI -->
  <script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
  <script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
  <!-- Bootstrap Toggle -->
  <script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
  <!-- jQuery Scrollbar -->
  <script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  <!-- Ready Pro JS -->
  <script src="../assets/js/ready.min.js"></script>
  <!-- Demo methods (Optional) -->
  <script src="../assets/js/setting-demo.js"></script>
  <script>
    // 표 배경 카드가 스크롤 끝까지 연결되도록 높이 조정
    document.addEventListener("DOMContentLoaded", function() {
      function adjustContentHeight() {
        var contentWrapper = document.querySelector(".content-card-wrapper");
        var wrapper = document.querySelector(".wrapper");
        if (contentWrapper && wrapper) {
          var windowHeight = window.innerHeight;
          var topMenuHeight = 60;
          var minContentHeight = windowHeight - topMenuHeight;
          contentWrapper.style.minHeight = minContentHeight + "px";
        }
      }
      
      adjustContentHeight();
      window.addEventListener("resize", adjustContentHeight);
      
      // 콘텐츠가 로드된 후에도 높이 조정
      setTimeout(adjustContentHeight, 500);
    });
  </script>
</body>
</html>
';
?>
