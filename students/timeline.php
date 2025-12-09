<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php"); 
global $DB, $USER;

// 페이지 방문 카운트
include("../pagecount.php");

$studentid = $_GET["id"];
if ($studentid == NULL) $studentid = $USER->id;

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') $url = "https://";   
else $url = "http://";   
$url .= $_SERVER['HTTP_HOST'];   
$url .= $_SERVER['REQUEST_URI'];    
if (strpos($url, 'tbegin') != false) $tbegin = required_param('tbegin', PARAM_INT); 
else $tbegin = time();

$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");

$tbegin  = $_GET["tb"];
$tfixed  = $_GET["tf"]; 
if ($tfixed == NULL) $tfixed = time();
$initialT = $tfixed - 43200;
$finalT   = $tfixed + 43200;

$checkgoal = $DB->get_record_sql("SELECT * FROM mdl_abessi_today 
                                 WHERE userid='$studentid' 
                                   AND (type LIKE '오늘목표' OR type LIKE '검사요청') 
                                 ORDER BY id DESC LIMIT 1");
$tgoal = $checkgoal->timecreated;

$jd = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday = jddayofweek($jd, 0); 
if ($nday == 0) $nday = 7;

$schedule = $DB->get_record_sql("SELECT id, editnew, 
                                        start1, start2, start3, start4, start5, start6, start7,
                                        duration1, duration2, duration3, duration4, duration5, duration6, duration7 
                                 FROM mdl_abessi_schedule 
                                 WHERE userid='$studentid' AND pinned=1  
                                 ORDER BY id DESC LIMIT 1");

if ($nday == 1) { $tstart = $schedule->start1; $hours = $schedule->duration1; }
if ($nday == 2) { $tstart = $schedule->start2; $hours = $schedule->duration2; }
if ($nday == 3) { $tstart = $schedule->start3; $hours = $schedule->duration3; }
if ($nday == 4) { $tstart = $schedule->start4; $hours = $schedule->duration4; }
if ($nday == 5) { $tstart = $schedule->start5; $hours = $schedule->duration5; }
if ($nday == 6) { $tstart = $schedule->start6; $hours = $schedule->duration6; }
if ($nday == 7) { $tstart = $schedule->start7; $hours = $schedule->duration7; }

$tcomplete0 = $tgoal + $hours * 3600;
$tcomplete  = date("h:i ", $tcomplete0);
$timestart  = date("h:i ", $tgoal);

$activitylog = $DB->get_records_sql("
    SELECT * 
    FROM mdl_logstore_standard_log 
    WHERE userid='$studentid' 
      AND component NOT LIKE 'core' 
      AND timecreated > '$initialT' 
      AND timecreated < '$finalT'  
      AND courseid NOT LIKE '239' 
    ORDER BY id DESC
");
$result = json_decode(json_encode($activitylog), true);

$timeline = NULL;
$tprev = 0;  
$n10   = 0;

foreach($result as $value) {
    $tdiff = $value['timecreated'] - $tprev;
    if ($tdiff > 600 && $tdiff < 43200) { 
        $n10++;
    }

    $mark         = '';
    $timecreated  = date("h시 i분 ", $value['timecreated']); 
    if ($value['action'] === 'loggedin') {
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">로그인</h5>
            <p class="card-text">'.$timecreated.'</p>
          </div>
        </div>';
    }
    if ($value['component'] === 'mod_quiz' && $value['action'] === 'started') {
        $attemptid = $value['objectid'];
        $atmptinfo = $DB->get_record_sql("SELECT * FROM mdl_quiz_attempts WHERE id='$attemptid' ORDER BY id DESC");
        $quizid    = $atmptinfo->quiz;
        $quizinfo  = $DB->get_record_sql("SELECT * FROM mdl_quiz WHERE id='$quizid' ORDER BY id DESC");  
        $quiztitle = $quizinfo->name;
		$quiztitle=str_replace('{ifminteacher}','',$quiztitle);
		$quiztitle=str_replace('{/ifminteacher}','',$quiztitle);
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title" style="color:red;">'.$quiztitle.' 시작</h5>
            <p class="card-text">'.$timecreated.'</p>
          </div>
        </div>';
    }
    if ($value['component'] === 'mod_quiz' &&  $value['action'] === 'reviewed') {
        $attemptid = $value['objectid'];
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">퀴즈 검토</h5>
            <p class="card-text">'.$timecreated.'</p>
            <a href="https://mathking.kr/moodle/mod/quiz/review.php?attempt='.$attemptid.'&studentid='.$studentid.'" target="_blank" class="btn btn-sm btn-primary">검토 보기</a>
          </div>
        </div>';
    }
    if ($value['component'] === 'mod_quiz' && $value['action'] === 'viewed' && $value['target'] === 'attempt') {
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">제출'.$mark.'</h5>
            <p class="card-text">'.$timecreated.'</p>
          </div>
        </div>';
    }
    if ($value['component'] === 'mod_quiz' && $value['action'] === 'submitted') {
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">시험 종료'.$mark.'</h5>
            <p class="card-text">'.$timecreated.'</p>
          </div>
        </div>';
    }
    if ($value['component'] === 'mod_icontent' && $value['action'] === 'viewed') {
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">개념공부'.$mark.'</h5>
            <p class="card-text">'.$timecreated.'</p>
          </div>
        </div>';
    }
    if ($value['component'] === 'mod_hotquestion') {
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">노트필기 활동'.$mark.'</h5>
            <p class="card-text">'.$timecreated.'</p>
          </div>
        </div>';
    }
    if ($value['action'] === 'loggedout') {
        $timeline .= '
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">로그아웃'.$mark.'</h5>
            <p class="card-text">'.$timecreated.' 이번 주 공부시간 :</p>
          </div>
        </div>';
    }
    $tprev = $value['timecreated'];
}

// 최근 퀴즈 성적 데이터
$amonthago   = time() - 604800*4;
$quizattempts = $DB->get_records_sql("
    SELECT *,
           mdl_quiz_attempts.timestart AS timestart,
           mdl_quiz_attempts.timefinish AS timefinish,
           mdl_quiz_attempts.maxgrade AS maxgrade,
           mdl_quiz_attempts.sumgrades AS sumgrades,
           mdl_quiz.sumgrades AS tgrades
    FROM mdl_quiz
    LEFT JOIN mdl_quiz_attempts
           ON mdl_quiz.id = mdl_quiz_attempts.quiz
    WHERE  mdl_quiz_attempts.timefinish > '$amonthago'
      AND  mdl_quiz_attempts.userid='$studentid'
    ORDER BY mdl_quiz_attempts.id DESC
");
$quizresult = json_decode(json_encode($quizattempts), true);
$quizlist   = '';

foreach ($quizresult as $value2) {
    $qnum      = substr_count($value2['layout'],',')+1 - substr_count($value2['layout'],',0');
    $quizgrade = round($value2['sumgrades'] / $value2['tgrades'] * 100, 0);
    if (strpos($value2['name'], 'ifmin') !== false)
        $quiztitle = substr($value2['name'], 0, strpos($value2['name'], '{'));
    else 
        $quiztitle = $value2['name'];

    if ($quizgrade > 85) {
        $imgstatus = '<img src="https://mathking.kr/Contents/Moodle/Visual%20arts/greendot.png" width="15">';
    } else {
        continue;
    }
    if ($qnum > 3) {
        $quizlist .= '
        <tr>
          <td class="align-middle" style="white-space: nowrap; width: auto; min-width: fit-content;">'.date("m/d",$value2['timestart']).'</td>
          <td class="align-middle">'.substr($quiztitle,0,60).' ('.$value2['attempt'].'회)</td>
          <td class="align-middle" style="color: rgb(239, 69, 64); white-space: nowrap; width: auto; min-width: fit-content;">'.$quizgrade.'점</td>
        </tr>';
    }
}

$tabtitle = $username->lastname.'H';
if ($tstart==NULL || $hours==0) $beginendtext = '<hr>오늘은 수업이 없는 날입니다.';
else $beginendtext = '시작 ('.$timestart.') | 종료 ('.$tcomplete.') ';

// HTML 출력
echo '
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- 반응형 설정 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>'.$tabtitle.'</title>
  
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
      font-size: 0.85rem;
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
    
    /* 카드 배경색과 정확히 일치시키기 */
    .card {
      background-color: #fff;
      border-radius: 6px;
      margin-bottom: 0.8rem;
      font-size: 0.85rem;
    }
    .card-title {
      font-size: 0.95rem;
      margin-bottom: 0.3rem;
      font-weight: bold;
    }
    .card-text {
      font-size: 0.8rem;
    }
    .table {
      font-size: 0.75rem;
      table-layout: auto;
    }
    .table td, .table th {
      padding: 0.4rem 0.3rem;
    }
    .table th {
      font-size: 0.7rem;
    }
    /* 날짜와 점수 컬럼 최소 폭, 줄바꿈 방지 */
    .table th:first-child,
    .table td:first-child {
      white-space: nowrap;
      width: auto;
      min-width: fit-content;
    }
    .table th:last-child,
    .table td:last-child {
      white-space: nowrap;
      width: auto;
      min-width: fit-content;
    }
    /* 퀴즈명 컬럼은 나머지 공간 차지 */
    .table th:nth-child(2),
    .table td:nth-child(2) {
      width: auto;
    }
    .btn-sm {
      padding: 0.3rem 0.6rem;
      font-size: 0.75rem;
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
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">계획</a>
          </td>
          <td style="padding: 0.2rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/mathpomodoro.php?userid='.$studentid.'" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일지</a>
          </td>
          <td style="padding: 0.2rem;">
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200" class="btn btn-sm btn-danger" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">오늘</a>
          </td>
        </tr>
      </table>
    </div>  
    <div class="wrapper">
      <div class="content">
        <div class="content-card-wrapper">
          <div class="container-fluid py-2 px-2">
          <div class="text-center mb-2" style="position: relative;">
            <span style="font-size:0.95rem; font-weight:bold;">
              ✨ '.$username->firstname.$username->lastname.'의 학습 현황판
            </span>
            <button id="copy-url-btn" style="background: none; border: none; cursor: pointer; padding: 0.2rem 0.5rem; margin-left: 0.5rem; vertical-align: middle;" title="URL 복사">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: #666;">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
              </svg>
            </button>
          </div>

         
       
     
          <!-- 시간 안내 -->
          <p class="text-center mb-2" style="font-weight:bold; font-size: 0.8rem;">
            '.$beginendtext.'
          </p>
          
          <!-- 퀴즈 결과 표 -->
          <div class="table-responsive mb-2" style="overflow-x: auto;">
            <table class="table table-bordered table-hover" style="width: 100%; margin: 0;">
              <thead class="thead-light">
                <tr>
                  <th style="width: auto; min-width: fit-content; white-space: nowrap; font-size: 0.7rem;">날짜</th>
                  <th style="width: auto; font-size: 0.7rem;">퀴즈명</th>
                  <th style="width: auto; min-width: fit-content; white-space: nowrap; font-size: 0.7rem;">점수</th>
                </tr>
              </thead>
              <tbody>
                '.$quizlist.'
              </tbody>
            </table>
          </div>

          <!-- 타임라인(카드) -->
          <div class="mb-2">
            '.$timeline.'
          </div>

          </div>
        </div>
      </div>
    </div>
  </div>
  
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
      
      // 단축 URL 생성 및 클립보드 복사 (한 번에 처리)
      var copyBtn = document.getElementById("copy-url-btn");
      if (copyBtn) {
        copyBtn.addEventListener("click", function() {
          var currentUrl = window.location.href;
          var btn = this;
          var originalSvg = btn.innerHTML;
          
          // 버튼 비활성화 및 로딩 표시
          btn.disabled = true;
          btn.style.opacity = "0.6";
          btn.style.cursor = "wait";
          btn.innerHTML = "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"color: #1976d2;\"><circle cx=\"12\" cy=\"12\" r=\"10\"></circle><path d=\"M12 6v6l4 2\"></path></svg>";
          
          // 단축 URL 생성 요청
          var formData = new FormData();
          formData.append("url", currentUrl);
          
          fetch("create_short_url.php", {
            method: "POST",
            body: formData
          })
          .then(function(response) {
            return response.json();
          })
          .then(function(data) {
            if (data.success && data.short_url) {
              // 클립보드에 복사
              if (navigator.clipboard && navigator.clipboard.writeText) {
                return navigator.clipboard.writeText(data.short_url).then(function() {
                  // 성공 메시지 표시
                  btn.innerHTML = "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"color: #4caf50;\"><path d=\"M20 6L9 17l-5-5\"></path></svg>";
                  btn.style.color = "#4caf50";
                  
                  // 간단한 알림 (선택사항)
                  var notification = document.createElement("div");
                  notification.style.cssText = "position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 10px 20px; border-radius: 4px; z-index: 9999; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);";
                  notification.textContent = "✓ 단축 URL이 클립보드에 복사되었습니다!";
                  document.body.appendChild(notification);
                  
                  setTimeout(function() {
                    notification.remove();
                    btn.innerHTML = originalSvg;
                    btn.style.color = "";
                    btn.disabled = false;
                    btn.style.opacity = "1";
                    btn.style.cursor = "pointer";
                  }, 2000);
                });
              } else {
                // 클립보드 API를 지원하지 않는 경우 (구형 브라우저)
                var textarea = document.createElement("textarea");
                textarea.value = data.short_url;
                textarea.style.position = "fixed";
                textarea.style.opacity = "0";
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand("copy");
                document.body.removeChild(textarea);
                
                btn.innerHTML = "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"color: #4caf50;\"><path d=\"M20 6L9 17l-5-5\"></path></svg>";
                btn.style.color = "#4caf50";
                
                var notification = document.createElement("div");
                notification.style.cssText = "position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 10px 20px; border-radius: 4px; z-index: 9999; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);";
                notification.textContent = "✓ 단축 URL이 클립보드에 복사되었습니다!";
                document.body.appendChild(notification);
                
                setTimeout(function() {
                  notification.remove();
                  btn.innerHTML = originalSvg;
                  btn.style.color = "";
                  btn.disabled = false;
                  btn.style.opacity = "1";
                  btn.style.cursor = "pointer";
                }, 2000);
              }
            } else {
              throw new Error(data.error || "단축 URL 생성에 실패했습니다.");
            }
          })
          .catch(function(error) {
            console.error("단축 URL 생성 오류:", error);
            alert("단축 URL 생성에 실패했습니다: " + (error.message || "알 수 없는 오류"));
            btn.innerHTML = originalSvg;
            btn.disabled = false;
            btn.style.opacity = "1";
            btn.style.cursor = "pointer";
          });
        });
      }
    });
  </script>
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
</body>
</html>
';
?>
