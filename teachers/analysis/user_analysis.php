<?php
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 파라미터 세팅
$studentid = $_GET["userid"];
$tbegin = $_GET["tbegin"];
$tend   = $_GET["tend"];

// 기본값 지정
$thisuser = $DB->get_record_sql("SELECT lastname, firstname, firstaccess FROM mdl_user WHERE id='$studentid'");
if($tbegin==null) $tbegin = $thisuser->firstaccess;
if($tend==null)   $tend   = $thisuser->firstaccess + 604800 * 1000;

// 1) 합계/개수 변수
$sumScore = 0;  $countScore = 0;
$sumNinactive = 0; $countNinactive = 0;
$sumNlazy = 0; $countNlazy = 0;

// 2) DB에서 score, ninactive, nlazy를 모두 불러오기
$rows = $DB->get_records_sql("
    SELECT id,score, ninactive, nlazy, timecreated
    FROM mdl_abessi_today
    WHERE userid = :userid
      AND timecreated > :tbegin
      AND timecreated < :tend
    ORDER BY timecreated ASC
    LIMIT 1000
", [
    'userid' => $studentid,
    'tbegin' => $tbegin,
    'tend'   => $tend
]);

// 3) 차트용 배열
$allData = [];
foreach($rows as $row) {
    // 합계/개수 집계
    $scoreVal     = (float)$row->score;
    if($scoreVal==0)$scoreVal=80;
    $ninactiveVal = (float)$row->ninactive;
    if($ninactiveVal>50)$ninactiveVal=50;
    $nlazyVal     = (float)($row->nlazy/20);

    $sumScore     += $scoreVal;     $countScore++;
    $sumNinactive += $ninactiveVal; $countNinactive++;
    $sumNlazy     += $nlazyVal;     $countNlazy++;

    // chartData 구성
    $label = date('Y-m-d', $row->timecreated); // date('n.j', $row->timecreated); 
    $allData[] = [
        'label'     => $label,
        'score'     => $scoreVal,
        'ninactive' => $ninactiveVal,
        'nlazy'     => $nlazyVal
    ];
}

// 4) 평균 계산
$avgScore     = ($countScore>0)     ? ($sumScore     / $countScore)     : 0;
$avgNinactive = ($countNinactive>0) ? ($sumNinactive / $countNinactive) : 0;
$avgNlazy     = ($countNlazy>0)     ? ($sumNlazy     / $countNlazy)     : 0;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8" />  
  <!-- Tailwind CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.2.1/dist/chart.umd.min.js"></script>
</head>
<body class="w-full max-w-6xl mx-auto p-4 space-y-4">
  <div class="bg-white shadow rounded p-4"> 
    <div class="flex space-x-4 border-b mb-4">
      <button 
        id="tab-btn-1" 
        class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500"
        onclick="showTab(1)">
        침착도(<?php echo round($avgScore,1); ?>)
      </button>
      <button 
        id="tab-btn-2" 
        class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500"
        onclick="showTab(2)">
        이탈(<?php echo round($avgNinactive,1); ?>)
      </button>
      <button 
        id="tab-btn-3" 
        class="py-2 px-4 border-b-2 border-transparent hover:border-blue-500"
        onclick="showTab(3)">
       지연 (<?php echo round($avgNlazy,1); ?>)
      </button>
    </div>

    <!-- score 그래프 -->
    <div id="chart1-container" class="bg-white shadow rounded p-4">
      <div class="w-full h-60">
        <canvas id="myChartScore" class="w-full h-full"></canvas>
      </div>
      <div class="mt-4 text-center">
        <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/studenthome/calmness.php?id=<?php echo $studentid; ?>&tb=604800" 
           target="_blank"
           class="text-blue-500 hover:text-blue-700 underline">
          실시간 침착도
        </a>
      </div>
    </div>

    <!-- ninactive 그래프 -->
    <div id="chart2-container" class="bg-white shadow rounded p-4 hidden">
      <div class="w-full h-60">
        <canvas id="myChartNinactive" class="w-full h-full"></canvas>
      </div>
    </div>

    <!-- nlazy 그래프 -->
    <div id="chart3-container" class="bg-white shadow rounded p-4 hidden">
      <div class="w-full h-60">
        <canvas id="myChartNlazy" class="w-full h-full"></canvas>
      </div>
    </div>
  </div>

  <script>
    // PHP -> JS로 변환
    const chartData = <?php echo json_encode($allData, JSON_UNESCAPED_UNICODE); ?>;

    // 1) score 그래프
    const ctxScore = document.getElementById('myChartScore').getContext('2d');
    const chartScore = new Chart(ctxScore, {
      type: 'line',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [
          {
            label: '침착도(score)',
            data: chartData.map(item => item.score),
            borderColor: '#8884d8',
            backgroundColor: 'rgba(136,132,216,0.2)',
            fill: true,
            tension: 0.2,
            spanGaps: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    // 2) ninactive 그래프
    const ctxNinactive = document.getElementById('myChartNinactive').getContext('2d');
    const chartNinactive = new Chart(ctxNinactive, {
      type: 'line',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [
          {
            label: 'ninactive',
            data: chartData.map(item => item.ninactive),
            borderColor: '#82ca9d',
            backgroundColor: 'rgba(130,202,157,0.2)',
            fill: true,
            tension: 0.2,
            spanGaps: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    // 3) nlazy 그래프
    const ctxNlazy = document.getElementById('myChartNlazy').getContext('2d');
    const chartNlazy = new Chart(ctxNlazy, {
      type: 'line',
      data: {
        labels: chartData.map(item => item.label),
        datasets: [
          {
            label: 'nlazy',
            data: chartData.map(item => item.nlazy),
            borderColor: '#ff7f50',
            backgroundColor: 'rgba(255,127,80,0.2)',
            fill: true,
            tension: 0.2,
            spanGaps: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true }
        }
      }
    });

    // 탭 전환 함수
    function showTab(tabIndex) {
      const c1 = document.getElementById('chart1-container');
      const c2 = document.getElementById('chart2-container');
      const c3 = document.getElementById('chart3-container');

      // 모두 숨김
      c1.classList.add('hidden');
      c2.classList.add('hidden');
      c3.classList.add('hidden');

      // 선택된 탭만 표시
      if (tabIndex === 1) c1.classList.remove('hidden');
      else if (tabIndex === 2) c2.classList.remove('hidden');
      else c3.classList.remove('hidden');

      // 탭 스타일
      document.getElementById('tab-btn-1').classList.remove('border-blue-500');
      document.getElementById('tab-btn-2').classList.remove('border-blue-500');
      document.getElementById('tab-btn-3').classList.remove('border-blue-500');

      if (tabIndex === 1) {
        document.getElementById('tab-btn-1').classList.add('border-blue-500');
      } else if (tabIndex === 2) {
        document.getElementById('tab-btn-2').classList.add('border-blue-500');
      } else {
        document.getElementById('tab-btn-3').classList.add('border-blue-500');
      }
    }

    // 기본 첫 탭
    showTab(1);
  </script>
</body>
</html>
