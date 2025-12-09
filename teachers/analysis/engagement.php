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
$tend = $_GET["tend"];

if($tbegin==null) $tbegin = $thisuser->firstaccess;
if($tend==null) $tend   = $thisuser->firstaccess + 604800 * 1000; 

$carefulness = 0;
$ncarefulness = 0;

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

// 차트 데이터를 담을 배열
$chartData = [];

// $goals를 순회하며 점수와 날짜를 배열에 저장
foreach ($goals as $row) {
    $carefulness += $row->score;
    $ncarefulness++;
    if($row->score < 10) continue;

    $chartData[] = [
        // X축 라벨: 날짜/시간 (예: 3/15 12:34 형식)
        'label' => date('Y_n.j', $row->timecreated),
        // Y축 값: score
        'score' => (float)$row->score
    ];
}

// 평균 계산 (필요시 사용)
if ($ncarefulness > 0) {
    $carefulness = $carefulness / $ncarefulness;
} else {
    $carefulness = 0;
} 
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

    
    <!-- 차트 영역 -->
    <div class="bg-white shadow rounded p-4">

      <!-- 차트를 감싸는 div에 폭/높이 설정 -->
      <div class="w-full h-96">
        <!-- canvas에 w-full, h-full 적용 -->
        <canvas id="myChart" class="w-full h-full"></canvas>
      </div>
    </div>
    <div class="flex items-center justify-between mb-4">
    <h3 class="text-lg font-bold mb-2">목표/검사요청 점수 추이</h3>
      <p class="text-sm text-gray-600 mb-4">
        최근 3개월 간 목표/검사요청 점수 평균: <strong><?php echo round($carefulness, 2); ?></strong>
      </p>
      <h2 class="text-xl font-bold"># 침착도 데이터</h2>
      <div class="text-lg font-semibold">
        <?php echo $studentname; ?> 
      </div>
    </div>
  </div>

  <!-- 차트 렌더링 스크립트 -->
  <script>
    // PHP 데이터를 JS에서 사용하기 위해 JSON으로 변환
    let chartData = <?php echo json_encode($chartData, JSON_UNESCAPED_UNICODE); ?>;

    // 차트 생성
    const ctx = document.getElementById('myChart');
    const myChart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [
          {
            label: '침착도 평점',
            data: chartData.map(item => item.score),
            borderColor: '#8884d8',
            backgroundColor: 'rgba(136,132,216,0.2)',
            fill: true,
            tension: 0.2
          }
        ]
      },
      options: {
        // responsive + maintainAspectRatio 옵션을 false로 해야 가로폭 기준으로 자동 확장됨
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });
  </script>
</body>
</html>
