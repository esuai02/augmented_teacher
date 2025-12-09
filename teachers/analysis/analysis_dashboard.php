<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER; 
require_login(); 
$teacherid=$_GET["userid"]; 
$timecreated=time(); 
//include("gpttalk.php"); astname;

$collegues=$DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid' "); 
$teacher=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79' "); 
$tsymbol=$teacher->symbol;
$teacher1=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79' "); 
$tsymbol1=$teacher1->symbol;
$teacher2=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79' "); 
$tsymbol2=$teacher2->symbol;
$teacher3=$DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79' "); 
$tsymbol3=$teacher3->symbol;  
 
$username= $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid' ");
$teachername=$username->firstname.$username->lastname;
$mystudents=$DB->get_records_sql("SELECT id,firstname,lastname FROM mdl_user WHERE suspended=0 AND lastaccess> '$halfdayago' AND  (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%'  OR firstname LIKE '%$tsymbol2%' OR firstname LIKE  '%$tsymbol3%') ORDER BY id DESC ");  

$result= json_decode(json_encode($mystudents), True);
 
unset($user);
foreach($result as $user)
	{
	$userid=$user['id'];
	$userlastaccess=$user['lastaccess'];
	$goal= $DB->get_record_sql("SELECT * FROM mdl_abessi_today WHERE userid='$userid' AND timecreated  >'$halfdayago'  AND ( type LIKE '오늘목표' OR type LIKE '검사요청') ORDER BY id DESC LIMIT 1 ");

  }



$sampleData = [
  '학생성적' => [
    [ 'name' => '3월', 'value' => 85 ],
    [ 'name' => '4월', 'value' => 88 ],
    [ 'name' => '5월', 'value' => 92 ],
    [ 'name' => '6월', 'value' => 90 ]
  ],
  '문제풀이통계' => [
    [ 'name' => '대수', 'solved' => 150, 'total' => 200 ],
    [ 'name' => '기하', 'solved' => 120, 'total' => 180 ],
    [ 'name' => '해석', 'solved' => 90, 'total' => 150 ],
    [ 'name' => '확률', 'solved' => 75, 'total' => 100 ]
  ],
  '학습시간' => [
    [ 'name' => '월', 'hours' => 2.5 ],
    [ 'name' => '화', 'hours' => 3.0 ],
    [ 'name' => '수', 'hours' => 2.0 ],
    [ 'name' => '목', 'hours' => 3.5 ],
    [ 'name' => '금', 'hours' => 2.8 ]
  ]
];

// 선생님 이름
$teacherName = $teachername;
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
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-bold">Mathking 데이터 시각화</h2>
      <div class="text-lg font-semibold">
        <?php echo $teacherName; ?> 선생님
      </div>
    </div>

    <!-- 탭 영역 -->
    <div class="w-full">
      <!-- 탭 버튼 -->
      <div class="grid w-full grid-cols-3 border-b">
        <button onclick="showTab('학생성적')" id="tabButton학생성적" class="py-2 px-4 border-r focus:outline-none">학생 성적</button>
        <button onclick="showTab('문제풀이통계')" id="tabButton문제풀이통계" class="py-2 px-4 border-r focus:outline-none">문제 풀이 통계</button>
        <button onclick="showTab('학습시간')" id="tabButton학습시간" class="py-2 px-4 focus:outline-none">학습 시간</button>
      </div>

      <!-- 탭 컨텐츠: 학생성적 -->
      <div id="tabContent학생성적" class="mt-4">
        <div class="bg-white shadow rounded p-4">
          <h3 class="text-lg font-bold mb-2">월별 평균 성적 추이</h3>
          <div class="h-96">
            <canvas id="chart학생성적"></canvas>
          </div>
        </div>
      </div>

      <!-- 탭 컨텐츠: 문제풀이통계 -->
      <div id="tabContent문제풀이통계" class="mt-4 hidden">
        <div class="bg-white shadow rounded p-4">ass
          <h3 class="text-lg font-bold mb-2">영역별 문제 풀이 현황</h3>
          <div class="h-96">
            <canvas id="chart문제풀이통계"></canvas>
          </div>
        </div>
      </div>

      <!-- 탭 컨텐츠: 학습시간 -->
      <div id="tabContent학습시간" class="mt-4 hidden">
        <div class="bg-white shadow rounded p-4">
          <h3 class="text-lg font-bold mb-2">일별 학습 시간</h3>
          <div class="h-96">
            <canvas id="chart학습시간"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 차트 렌더링 스크립트 -->
  <script>
    // PHP 데이터를 JS에서 사용하기 위해 JSON으로 변환
    let sampleData = <?php echo json_encode($sampleData, JSON_UNESCAPED_UNICODE); ?>;

    // 1. 학생성적 차트 (Line Chart)
    const chart학생성적 = new Chart(document.getElementById('chart학생성적'), {
      type: 'line',
      data: {
        labels: sampleData['학생성적'].map(item => item.name),
        datasets: [
          {
            label: '성적',
            data: sampleData['학생성적'].map(item => item.value),
            borderColor: '#8884d8',
            backgroundColor: 'rgba(136,132,216,0.2)',
            fill: true,
            tension: 0.2
          }
        ]
      },
      options: {
        scales: {
          y: {
            min: 0,
            max: 100
          }
        }
      }
    });

    // 2. 문제풀이통계 차트 (Bar Chart)
    const chart문제풀이통계 = new Chart(document.getElementById('chart문제풀이통계'), {
      type: 'bar',
      data: {
        labels: sampleData['문제풀이통계'].map(item => item.name),
        datasets: [
          {
            label: '풀이 완료',
            data: sampleData['문제풀이통계'].map(item => item.solved),
            backgroundColor: '#8884d8'
          },
          {
            label: '전체 문제',
            data: sampleData['문제풀이통계'].map(item => item.total),
            backgroundColor: '#82ca9d'
          }
        ]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // 3. 학습시간 차트 (Line Chart)
    const chart학습시간 = new Chart(document.getElementById('chart학습시간'), {
      type: 'line',
      data: {
        labels: sampleData['학습시간'].map(item => item.name),
        datasets: [
          {
            label: '학습시간',
            data: sampleData['학습시간'].map(item => item.hours),
            borderColor: '#82ca9d',
            backgroundColor: 'rgba(130,202,157,0.2)',
            fill: true,
            tension: 0.2
          }
        ]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true
          }
        }
      }
    });

    // 탭 전환 함수
    function showTab(tabName) {
      document.getElementById('tabContent학생성적').classList.add('hidden');
      document.getElementById('tabContent문제풀이통계').classList.add('hidden');
      document.getElementById('tabContent학습시간').classList.add('hidden');

      document.getElementById('tabButton학생성적').classList.remove('bg-gray-200');
      document.getElementById('tabButton문제풀이통계').classList.remove('bg-gray-200');
      document.getElementById('tabButton학습시간').classList.remove('bg-gray-200');

      document.getElementById('tabContent' + tabName).classList.remove('hidden');
      document.getElementById('tabButton' + tabName).classList.add('bg-gray-200');
    }

    // 기본 탭 설정
    showTab('학생성적');
  </script>
</body>
</html>
