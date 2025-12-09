<?php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 페이지 방문 카운트
include("../pagecount.php");

$studentid = required_param('id', PARAM_INT); 
$nedit     = required_param('eid', PARAM_INT); 
$nprev     = $nedit + 1;
$nnext     = $nedit - 1;

// 사용자 이름
$username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$studentid' ");

// 수정/일반 모드
$displaymode = $_GET["mode"];

// 로그 남기기
$timecreated = time();
$DB->execute("INSERT INTO {abessi_missionlog} (userid,page,timecreated) 
              VALUES('$studentid','studentschedule','$timecreated')");

// pinned=1 스케줄 조회
$timeplan = $DB->get_records_sql("
    SELECT *
    FROM mdl_abessi_schedule
    WHERE userid='$studentid'
      AND pinned=1
    ORDER BY timecreated DESC
    LIMIT 1
");
$result = json_decode(json_encode($timeplan), true);

// 초기화
$weektotal=0; 
$edittime='';
$startdate='';
$start1=$start2=$start3=$start4=$start5=$start6=$start7='';
$start11=$start12=$start13=$start14=$start15=$start16=$start17='';
$schtype='';
$duration1=$duration2=$duration3=$duration4=$duration5=$duration6=$duration7=0;
$memo1=$memo2=$memo3=$memo4=$memo5=$memo6=$memo7=$memo8=$memo9='';

// 데이터 가져오기
$index=0;
foreach($result as $value) {
    $index++;
    if($index==$nedit) {
        $weektotal = $value['duration1'] + $value['duration2'] + $value['duration3']
                   + $value['duration4'] + $value['duration5'] + $value['duration6'] + $value['duration7'];

        $edittime   = date('m/d', $value['timecreated']);
        $startdate  = $value['date'];
        $start1     = $value['start1'];
        $start2     = $value['start2'];
        $start3     = $value['start3'];
        $start4     = $value['start4'];
        $start5     = $value['start5'];
        $start6     = $value['start6'];
        $start7     = $value['start7'];

        $start11    = $value['start11'];
        $start12    = $value['start12'];
        $start13    = $value['start13'];
        $start14    = $value['start14'];
        $start15    = $value['start15'];
        $start16    = $value['start16'];
        $start17    = $value['start17'];

        $schtype    = $value['type'];
        if($schtype == NULL) $schtype = '기본';

        // 12:00 AM이면 표시 안 하도록
        if($start1 == '12:00 AM') $start1=NULL;
        if($start2 == '12:00 AM') $start2=NULL;
        if($start3 == '12:00 AM') $start3=NULL;
        if($start4 == '12:00 AM') $start4=NULL;
        if($start5 == '12:00 AM') $start5=NULL;
        if($start6 == '12:00 AM') $start6=NULL;
        if($start7 == '12:00 AM') $start7=NULL;
        if($start11=='12:00 AM') $start11=NULL;
        if($start12=='12:00 AM') $start12=NULL;
        if($start13=='12:00 AM') $start13=NULL;
        if($start14=='12:00 AM') $start14=NULL;
        if($start15=='12:00 AM') $start15=NULL;
        if($start16=='12:00 AM') $start16=NULL;
        if($start17=='12:00 AM') $start17=NULL;

        $duration1=$value['duration1'];
        $duration2=$value['duration2'];
        $duration3=$value['duration3'];
        $duration4=$value['duration4'];
        $duration5=$value['duration5'];
        $duration6=$value['duration6'];
        $duration7=$value['duration7'];

        // 0이면 표시 안 하도록
        if($duration1==0) $duration1=NULL;
        if($duration2==0) $duration2=NULL;
        if($duration3==0) $duration3=NULL;
        if($duration4==0) $duration4=NULL;
        if($duration5==0) $duration5=NULL;
        if($duration6==0) $duration6=NULL;
        if($duration7==0) $duration7=NULL;

        // 메모
        $memo1=$value['memo1'];
        $memo2=$value['memo2'];
        $memo3=$value['memo3'];
        $memo4=$value['memo4'];
        $memo5=$value['memo5'];
        $memo6=$value['memo6'];
        $memo7=$value['memo7'];
        $memo8=$value['memo8'];
        $memo9=$value['memo9'];
    }
}

// (요일 계산)
$jd  = cal_to_jd(CAL_GREGORIAN, date("m"), date("d"), date("Y"));
$nday= jddayofweek($jd,0);

$schedule= $DB->get_record_sql("
    SELECT *
    FROM mdl_abessi_schedule
    WHERE userid='$studentid'
      AND pinned=1
    ORDER BY timecreated DESC
    LIMIT 1
");
if($nday==1) $untiltoday = $schedule->duration1;
if($nday==2) $untiltoday = $schedule->duration1+$schedule->duration2;
if($nday==3) $untiltoday = $schedule->duration1+$schedule->duration2+$schedule->duration3;
if($nday==4) $untiltoday = $schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4;
if($nday==5) $untiltoday = $schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5;
if($nday==6) $untiltoday = $schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6;
// 0이면 일요일
if($nday==0) $untiltoday = $schedule->duration1+$schedule->duration2+$schedule->duration3+$schedule->duration4+$schedule->duration5+$schedule->duration6+$schedule->duration7;

// 상단 탭
$tabtitle = $username->lastname . "님의 스케줄";

// =================== HTML 출력 시작 ===================
echo '
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="utf-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <!-- 반응형 설정 -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
  <title>'.$tabtitle.'</title>

  <link rel="icon" href="https://mathking.kr/moodle/local/augmented_teacher/assets/img/favicon.ico" type="image/x-icon"/>

  <!-- 웹폰트 -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/webfont/webfont.min.js"></script>
  <script>
    WebFont.load({
      google: {families:["Montserrat:100,200,300,400,500,600,700,800,900"]},
      custom: {families:["Flaticon","LineAwesome"], urls:["https://mathking.kr/moodle/local/augmented_teacher/assets/css/fonts.css"]},
      active: function() {
        sessionStorage.fonts = true;
      }
    });
  </script>

  <!-- 부트스트랩 CSS -->
  <link rel="stylesheet" href="https://mathking.kr/moodle/local/augmented_teacher/assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://mathking.kr/moodle/local/augmented_teacher/assets/css/ready.min.css">
  <link rel="stylesheet" href="https://mathking.kr/moodle/local/augmented_teacher/assets/css/demo.css">
  
  <!-- jQuery UI 테마 (필요 시) -->
  <link rel="stylesheet" href="//code.jquery.com/ui/1.8.18/themes/base/jquery-ui.css" />
  
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
    
    .card {
      border-radius: 6px;
      margin-bottom: 0.8rem;
      font-size: 0.85rem;
    }
    .card-title {
      font-size: 0.95rem;
      margin-bottom: 0.3rem;
      font-weight: bold;
    }
    .table {
      font-size: 0.75rem;
    }
    .table td, .table th {
      padding: 0.4rem 0.3rem;
    }
    .table th {
      font-size: 0.7rem;
    }
    .btn-sm {
      padding: 0.3rem 0.6rem;
      font-size: 0.75rem;
    }
    .form-control-sm {
      font-size: 0.75rem;
      padding: 0.3rem 0.5rem;
    }
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
      padding: 0.2rem;
    }
    .top-menu .btn {
      width: 100%;
      font-size: 0.7rem;
      padding: 0.3rem 0.4rem;
      white-space: nowrap;
    }
  </style>
</head>

<body>
  <div class="mobile-container">
    <div class="top-menu">
      <table align="center" style="width: 100%;">
        <tr>
          <td>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1" class="btn btn-sm btn-danger" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일정</a>
          </td>
          <td>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timelineWeek.php?id='.$studentid.'&tb=604800" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">계획</a>
          </td>
          <td>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/mathpomodoro.php?userid='.$studentid.'" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">일지</a>
          </td>
          <td>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/students/timeline.php?id='.$studentid.'&tb=43200" class="btn btn-sm btn-info" style="width: 100%; font-size: 0.7rem; padding: 0.3rem 0.4rem;">오늘</a>
          </td>
        </tr>
      </table>
    </div>
    <div class="wrapper">
      <div class="content">
        <div class="container-fluid py-2 px-2">

        <!-- 상단 안내 -->
        <div class="text-center mb-2">
          <span style="font-size:0.95rem; font-weight:bold;">
            '.$username->firstname.$username->lastname.'의 시간표
          </span>
        </div>
           
        <div class="row">
          <div class="col-md-12">
            <div class="card">
              <div class="card-body">
';

// ====================== 본문 표시 ======================
if($displaymode==='edit') {
  // 저장 버튼
  $savebutton = '
    <button type="button" class="btn btn-sm btn-success"
      onclick="editschedule(
        33, '.$studentid.',
        $(\'#timepicker1\').val(), $(\'#timepicker2\').val(),
        $(\'#timepicker3\').val(), $(\'#timepicker4\').val(),
        $(\'#timepicker5\').val(), $(\'#timepicker6\').val(),
        $(\'#timepicker7\').val(),
        $(\'#basic1\').val(), $(\'#basic2\').val(), $(\'#basic3\').val(),
        $(\'#basic4\').val(), $(\'#basic5\').val(), $(\'#basic6\').val(),
        $(\'#basic7\').val()
      )"
    >
      저장하기
    </button>
  ';

  echo '
    <h5 class="card-title">스케줄 편집</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="thead-light">
          <tr>
            <th style="width: 12.5%;">요일</th>
            <th>시작</th>
            <th>공부시간</th>
          </tr>
        </thead>
        <tbody>
          <!-- 월 -->
          <tr>
            <td>월</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker1" value="'.$start1.'" style="width:120px;">
            </td>
            <td>
              <select id="basic1" name="basic1" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration1.'">'.$duration1.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <!-- 화 -->
          <tr>
            <td>화</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker2" value="'.$start2.'" style="width:120px;">
            </td>
            <td>
              <select id="basic2" name="basic2" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration2.'">'.$duration2.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <!-- 수 -->
          <tr>
            <td>수</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker3" value="'.$start3.'" style="width:120px;">
            </td>
            <td>
              <select id="basic3" name="basic3" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration3.'">'.$duration3.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <!-- 목 -->
          <tr>
            <td>목</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker4" value="'.$start4.'" style="width:120px;">
            </td>
            <td>
              <select id="basic4" name="basic4" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration4.'">'.$duration4.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <!-- 금 -->
          <tr>
            <td>금</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker5" value="'.$start5.'" style="width:120px;">
            </td>
            <td>
              <select id="basic5" name="basic5" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration5.'">'.$duration5.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <!-- 토 -->
          <tr>
            <td>토</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker6" value="'.$start6.'" style="width:120px;">
            </td>
            <td>
              <select id="basic6" name="basic6" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration6.'">'.$duration6.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <!-- 일 -->
          <tr>
            <td>일</td>
            <td>
              <input type="text" class="form-control form-control-sm" id="timepicker7" value="'.$start7.'" style="width:120px;">
            </td>
            <td>
              <select id="basic7" name="basic7" class="form-control form-control-sm" style="width:120px;">
                <option value="'.$duration7.'">'.$duration7.'</option>
                <option value="0">0</option>
                <option value="1">1</option>
                <option value="1.5">1.5</option>
                <option value="2">2</option>
                <option value="2.5">2.5</option>
                <option value="3">3</option>
                <option value="3.5">3.5</option>
                <option value="4">4</option>
                <option value="4.5">4.5</option>
                <option value="5">5</option>
                <option value="5.5">5.5</option>
                <option value="6">6</option>
                <option value="6.5">6.5</option>
                <option value="7">7</option>
                <option value="7.5">7.5</option>
                <option value="8">8</option>
                <option value="8.5">8.5</option>
                <option value="9">9</option>
                <option value="9.5">9.5</option>
                <option value="10">10</option>
              </select>
            </td>
          </tr>
          <tr>
            <td colspan="3" class="text-right">
              '.$savebutton.'
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  ';
} else {
  // 일반 모드
  echo '
    <h5 class="card-title">스케줄 조회</h5>
    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="thead-light">
          <tr>
            <th style="width: 12.5%;">요일</th>
            <th>시작</th>
            <th>공부시간</th>
          </tr>
        </thead>
        <tbody>
  ';
  if($duration1>0)
    echo '<tr><td>월</td><td>'.$start1.'</td><td>'.$duration1.'</td></tr>';
  if($duration2>0)
    echo '<tr><td>화</td><td>'.$start2.'</td><td>'.$duration2.'</td></tr>';
  if($duration3>0)
    echo '<tr><td>수</td><td>'.$start3.'</td><td>'.$duration3.'</td></tr>';
  if($duration4>0)
    echo '<tr><td>목</td><td>'.$start4.'</td><td>'.$duration4.'</td></tr>';
  if($duration5>0)
    echo '<tr><td>금</td><td>'.$start5.'</td><td>'.$duration5.'</td></tr>';
  if($duration6>0)
    echo '<tr><td>토</td><td>'.$start6.'</td><td>'.$duration6.'</td></tr>';
  if($duration7>0)
    echo '<tr><td>일</td><td>'.$start7.'</td><td>'.$duration7.'</td></tr>';

  echo '
          <tr>
            <td colspan="3" class="text-right">
              <button class="btn btn-sm btn-secondary">
                <a href="https://mathking.kr/moodle/local/augmented_teacher/students/p_schedule.php?id='.$studentid.'&eid=1&mode=edit" 
                   style="color:#fff; text-decoration:none;">변경하기</a>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  ';
}

echo '
              </div> <!-- card-body -->
            </div> <!-- card -->
          </div> <!-- col-md-12 -->
        </div> <!-- row -->
        </div> <!-- container-fluid -->
      </div> <!-- content -->
    </div> <!-- wrapper -->
  </div> <!-- mobile-container -->

  <!-- Core JS Files -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/core/popper.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/core/bootstrap.min.js"></script>
  
  <!-- jQuery UI -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
  
  <!-- jQuery Scrollbar -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
  
  <!-- Moment JS -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/moment/moment.min.js"></script>
  
  <!-- Datetimepicker -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>
  
  <!-- Select2 -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/select2/select2.full.min.js"></script>
  
  <!-- 기타 플러그인 -->
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/chart.js/chart.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/chart-circle/circles.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/datatables/datatables.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/gmaps/gmaps.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/dropzone/dropzone.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/summernote/summernote-bs4.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/ready.min.js"></script>
  <script src="https://mathking.kr/moodle/local/augmented_teacher/assets/js/setting-demo.js"></script>

  <!-- 스크립트 로직 -->
  <script>
    // 저장 로직
    function editschedule(
      Eventid, Userid,
      Start1, Start2, Start3, Start4, Start5, Start6, Start7,
      Duration1, Duration2, Duration3, Duration4, Duration5, Duration6, Duration7
    ) {
      var Schtype = \''.$schtype.'\';
      $.ajax({
        url:"database.php",
        type: "POST",
        dataType:"json",
        data : {
          "userid": Userid,
          "eventid": Eventid,
          "start1": Start1,
          "start2": Start2,
          "start3": Start3,
          "start4": Start4,
          "start5": Start5,
          "start6": Start6,
          "start7": Start7,
          "duration1": Duration1,
          "duration2": Duration2,
          "duration3": Duration3,
          "duration4": Duration4,
          "duration5": Duration5,
          "duration6": Duration6,
          "duration7": Duration7,
          "schtype": Schtype
        },
        success:function(data){
          alert("저장 완료!");
          // location.reload(); // 페이지 새로고침 or 원하는 리다이렉트
        },
        error:function(e){
          alert("에러 발생: " + e.responseText);
        }
      });
    }

    // 시간 선택(datetimepicker) 설정
    $("#timepicker1, #timepicker2, #timepicker3, #timepicker4, #timepicker5, #timepicker6, #timepicker7").datetimepicker({
      format: "h:mm A",  // 12시간제 표시
      showClear: true,
      showClose: true,
      stepping: 15,
    });

    // 공부시간 select2
    $("#basic1, #basic2, #basic3, #basic4, #basic5, #basic6, #basic7").select2({
      theme: "bootstrap"
    });
  </script>
</body>
</html>
';
?>
