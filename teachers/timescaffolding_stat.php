<?php
/****************************************************
 * 1) Moodle 환경 기본 설정 및 변수 선언
 ****************************************************/
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 로그인 보호 (필요시)
require_login();

// 세션 사용자 & URL 파라미터
$userid = $_GET["userid"] ?? $USER->id;

// 유저정보 조회
$thisuser = $DB->get_record_sql("
    SELECT lastname, firstname, firstaccess
    FROM {user}
    WHERE id = :uid
", ['uid' => $userid]);

$studentname = $thisuser->firstname . $thisuser->lastname;

/****************************************************
 * 2) 지표(indicators) 데이터 조회
 ****************************************************/
$sql = "
    SELECT 
        timecreated,
        nalt,
        kpomodoro,
        npomodoro,
        pmresult
    FROM {abessi_indicators}
    WHERE userid = :uid AND timecreated> :timecreated AND pmresult>0
    ORDER BY id ASC LIMIT 10000
";
$params = ['uid' => $userid, 'timecreated' => time()-3*30*24*60*60];

$indicators = $DB->get_records_sql($sql, $params);

/****************************************************
 * 3) 그래프용 데이터 전처리
 ****************************************************/
$xLabels   = []; // 예: ["1/14","1/15","1/20",...]
$naltData  = [];
$kPomData  = [];
$nPomData  = [];
$pmData    = [];

foreach ($indicators as $row) {
    $tc = (int)$row->timecreated;
    // X축 라벨: 'n.j' 예) 1.18
    $xLabels[]  = date('n.j', $tc);

    // Y축 값
    $naltData[] = (int)$row->nalt;
    $kPomData[] = (int)$row->kpomodoro;
    $nPomData[] = (int)$row->npomodoro;
    $pmData[]   = (int)$row->pmresult;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>ABESSI 지표 시각화 (탭 형태)</title>
  <!-- Chart.js (4.2.1) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>
  <style>
    body {
      margin: 0; 
      padding: 0;
      font-family: Arial, sans-serif;
      background-color: #f9f9f9;
    }
    .container {
      max-width: 1200px;
      margin: 20px auto;
      padding: 20px;
      background-color: #fff;
      border: 1px solid #ddd;
      border-radius: 4px;
    }
    h2 {
      margin: 0 0 10px 0;
      padding: 0;
      font-weight: 600;
    }
    .tab-buttons {
      margin-bottom: 20px;
    }
    .tab-button {
      background-color: #e7e7e7;
      border: none;
      cursor: pointer;
      padding: 10px 20px;
      margin-right: 4px;
      font-size: 14px;
    }
    .tab-button.active {
      background-color: #ccc;
      font-weight: bold;
    }
    .tab-content {
      display: none; 
      width: 100%;
      height: 300px; /* 차트 높이 */
      margin-bottom: 40px;
    }
    .tab-content.active {
      display: block;
    }
  </style>
</head>
<body>
  <div class="container"> 
    <div class="tab-buttons">
      <button class="tab-button active" onclick="openTab(event, 'tabKPomodoro')">평균주기</button>
      <button class="tab-button" onclick="openTab(event, 'tabNPomodoro')">세션수/주</button>
      <button class="tab-button" onclick="openTab(event, 'tabPMResult')">만족도</button>
      <button class="tab-button" onclick="openTab(event, 'tabNalt')">능동지수</button>
    </div>

    <!-- 탭 콘텐츠: kPomodoro -->
    <div id="tabKPomodoro" class="tab-content active">
      <canvas id="chartKPomodoro"></canvas>
    </div>

    <!-- 탭 콘텐츠: nPomodoro -->
    <div id="tabNPomodoro" class="tab-content">
      <canvas id="chartNPomodoro"></canvas>
    </div>

    <!-- 탭 콘텐츠: pmresult -->
    <div id="tabPMResult" class="tab-content">
      <canvas id="chartPMResult"></canvas>
    </div>

    <!-- 탭 콘텐츠: NALT -->
    <div id="tabNalt" class="tab-content">
      <canvas id="chartNalt"></canvas>
    </div>
  </div>

  <script>
    // PHP -> JS
    let xLabelsJS   = <?php echo json_encode($xLabels); ?>;     // ["1/14","1/15",...]
    let naltDataJS  = <?php echo json_encode($naltData); ?>;    // [값, 값, ...]
    let kPomDataJS  = <?php echo json_encode($kPomData); ?>;    // [값, 값, ...]
    let nPomDataJS  = <?php echo json_encode($nPomData); ?>;    // [값, 값, ...]
    let pmDataJS    = <?php echo json_encode($pmData); ?>;      // [값, 값, ...]

    // 탭 열기 함수
    function openTab(evt, tabId) {
      // 모든 탭 내용 숨기기
      var contents = document.getElementsByClassName("tab-content");
      for (var i = 0; i < contents.length; i++) {
        contents[i].classList.remove("active");
      }
      // 모든 탭 버튼 비활성화
      var btns = document.getElementsByClassName("tab-button");
      for (var j = 0; j < btns.length; j++) {
        btns[j].classList.remove("active");
      }
      // 선택한 탭 활성화
      document.getElementById(tabId).classList.add("active");
      evt.currentTarget.classList.add("active");
    }

    // 1) kPomodoro 차트
    const ctxKPom = document.getElementById('chartKPomodoro').getContext('2d');
    new Chart(ctxKPom, {
      type: 'line',
      data: {
        labels: xLabelsJS,
        datasets: [{
          label: 'kPomodoro',
          data: kPomDataJS,
          borderColor: 'rgba(255, 159, 64, 1)',
          backgroundColor: 'rgba(255, 159, 64, 0.2)',
          fill: false,
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: false,
              text: '월/일'
            }
          },
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // 2) nPomodoro 차트
    const ctxNPom = document.getElementById('chartNPomodoro').getContext('2d');
    new Chart(ctxNPom, {
      type: 'line',
      data: {
        labels: xLabelsJS,
        datasets: [{
          label: 'nPomodoro',
          data: nPomDataJS,
          borderColor: 'rgba(153, 102, 255, 1)',
          backgroundColor: 'rgba(153, 102, 255, 0.2)',
          fill: false,
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: '월/일'
            }
          },
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // 3) pmresult 차트
    const ctxPM = document.getElementById('chartPMResult').getContext('2d');
    new Chart(ctxPM, {
      type: 'line',
      data: {
        labels: xLabelsJS,
        datasets: [{
          label: 'pmresult',
          data: pmDataJS,
          borderColor: 'rgba(255, 99, 132, 1)',
          backgroundColor: 'rgba(255, 99, 132, 0.2)',
          fill: false,
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: '월/일'
            }
          },
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // 4) nalt 차트 (마지막 탭)
    const ctxNalt = document.getElementById('chartNalt').getContext('2d');
    new Chart(ctxNalt, {
      type: 'line',
      data: {
        labels: xLabelsJS,
        datasets: [{
          label: 'nalt',
          data: naltDataJS,
          borderColor: 'rgba(75, 192, 192, 1)',
          backgroundColor: 'rgba(75, 192, 192, 0.2)',
          fill: false,
          tension: 0.1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: {
            title: {
              display: true,
              text: '월/일'
            }
          },
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>
