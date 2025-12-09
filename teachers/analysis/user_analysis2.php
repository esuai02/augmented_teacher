<?php
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET["userid"];
$timecreated = time();
$thisuser = $DB->get_record_sql("SELECT lastname, firstname, firstaccess FROM mdl_user WHERE id='$studentid'");
$studentname = $thisuser->firstname . $thisuser->lastname;

$tbegin = $_GET["tbegin"];
$tend   = $_GET["tend"];

if($tbegin==null) $tbegin = $thisuser->firstaccess;
if($tend==null)   $tend   = $thisuser->firstaccess + 604800 * 1000; 

// (1) 별도 합계/개수 준비
$sumCareful = 0;
$countCareful = 0;
$sumNinactive = 0;
$countNinactive = 0;

// goals 데이터 불러오기
$goals = $DB->get_records_sql("
    SELECT * 
    FROM mdl_abessi_today 
    WHERE userid='$studentid' 
      AND timecreated > '$tbegin' 
      AND timecreated < '$tend'  
      AND (type LIKE '오늘목표' OR type LIKE '검사요청') 
    ORDER BY id ASC
    LIMIT 1000
");

// 모든 점수를 담을 배열
$allScores = [];

// (2) 데이터 순회하며 배열 저장, 침착도/닌액티브 합계 계산
foreach ($goals as $row) {
    $score = (float)$row->score;
    $label = date('n.j', $row->timecreated);

    // 침착도(>= 10) 통계
    if ($score >= 10) {
        $sumCareful += $score;
        $countCareful++;
    }
    // ninactive(< 10) 통계
    else {
        $sumNinactive += $score;
        $countNinactive++;
    }

    // 차트용 데이터(공통 라벨)
    $allScores[] = [
        'label' => $label,
        'score' => $score
    ];
}

// (3) 평균 계산
$avgCareful = ($countCareful > 0) ? ($sumCareful / $countCareful) : 0;
$avgNinactive = ($countNinactive > 0) ? ($sumNinactive / $countNinactive) : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />
  <title>Mathking Dashboard</title>
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>
</head>
<body class="w-full max-w-6xl mx-auto p-4 space-y-4">

  <!-- 상단 카드 -->
  <div class="bg-white shadow rounded p-4">
    <!-- 공통 제목: 필요하다면 통합된 문구로 사용 가능 -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-bold mb-2">
        침착도 / ninactive Dashboard
      </h3>
    </div>

    <!-- 탭 버튼 영역 -->
    <div class="flex space-x-4 border-b mb-4">
      <button 
        id="tab-btn-1" 
        class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500"
        onclick="showTab(1)">
        침착도 그래프 (평균: <?php echo round($avgCareful,1); ?>)
      </button>
      <button 
        id="tab-btn-2" 
        class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500"
        onclick="showTab(2)">
        ninactive 그래프 (평균: <?php echo round($avgNinactive,1); ?>)
      </button>
    </div>

    <!-- 차트 1 (침착도) -->
    <div id="chart1-container" class="bg-white shadow rounded p-4">
      <div class="w-full h-60">
        <canvas id="myChart1" class="w-full h-full"></canvas>
      </div>
    </div>

    <!-- 차트 2 (ninactive) -->
    <div id="chart2-container" class="bg-white shadow rounded p-4 hidden">
      <div class="w-full h-60">
        <canvas id="myChart2" class="w-full h-full"></canvas>
      </div>
    </div>
  </div>

  <!-- 차트 렌더링 스크립트 -->
  <script>
    // PHP 배열을 JS에서 사용하기 위해 JSON으로 변환
    let chartData = <?php echo json_encode($allScores, JSON_UNESCAPED_UNICODE); ?>;

    // (A) 침착도 차트 (score >= 10)
    const ctx1 = document.getElementById('myChart1').getContext('2d');
    const myChart1 = new Chart(ctx1, {
      type: 'line',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [
          {
            label: '침착도 (score >= 10)',
            // 점수가 10 이상이면 표시, 아니면 null로 표시
            data: chartData.map(item => (item.score >= 10 ? item.score : null)),
            borderColor: '#8884d8',
            backgroundColor: 'rgba(136,132,216,0.2)',
            fill: true,
            tension: 0.2,
            spanGaps: true  // 중간에 null이 있어도 선 이어줌
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // (B) ninactive 차트 (score < 10)
    const ctx2 = document.getElementById('myChart2').getContext('2d');
    const myChart2 = new Chart(ctx2, {
      type: 'line',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [
          {
            label: 'ninactive (score < 10)',
            data: chartData.map(item => (item.score < 10 ? item.score : null)),
            borderColor: '#82ca9d',
            backgroundColor: 'rgba(130,202,157,0.2)',
            fill: true,
            tension: 0.2,
            spanGaps: true // null이 있어도 선 이어줌
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // 탭 전환 함수
    function showTab(tabIndex) {
      const chart1 = document.getElementById('chart1-container');
      const chart2 = document.getElementById('chart2-container');

      // 모든 차트 숨기기
      chart1.classList.add('hidden');
      chart2.classList.add('hidden');

      // 선택된 탭 보이기
      if (tabIndex === 1) {
        chart1.classList.remove('hidden');
      } else {
        chart2.classList.remove('hidden');
      }

      // 탭 버튼 스타일(선택된 탭 강조)
      document.getElementById('tab-btn-1').classList.remove('border-blue-500');
      document.getElementById('tab-btn-2').classList.remove('border-blue-500');
      if (tabIndex === 1) {
        document.getElementById('tab-btn-1').classList.add('border-blue-500');
      } else {
        document.getElementById('tab-btn-2').classList.add('border-blue-500');
      }
    }

    // 페이지 로드 시 기본 탭(1) 보이기
    showTab(1);
  </script>
</body>
</html>
