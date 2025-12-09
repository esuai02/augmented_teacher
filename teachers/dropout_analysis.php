<?php 
// Moodle 환경 설정 및 사용자 인증
include_once("/home/moodle/public_html/moodle/config.php");
require_login(); // 로그인 체크 추가
global $DB, $USER;

// GET 파라미터 값 확인
$studentid = isset($_GET["id"]) ? $_GET["id"] : $USER->id;
$cntinput  = isset($_GET["cntinput"]) ? $_GET["cntinput"] : null;
$mode      = isset($_GET["mode"]) ? $_GET["mode"] : null;

// 시간 관련 변수
$timecreated = time(); 
$hoursago    = $timecreated - 14400;
$halfdayago  = $timecreated - 43200;
$aweekago    = $timecreated - 604800;

// 사용자 정보 및 역할 확인 (파라미터 바인딩으로 보안 강화)
$thisuser = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id = ?", array($studentid));
$stdname  = $thisuser ? $thisuser->lastname : '학생';

$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22", array($USER->id));
$role     = $userrole ? $userrole->role : 'student';
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <title>학생 모니터링 대시보드</title>
  <!-- Chart.js CDN (순수 JavaScript 차트 라이브러리 사용) -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    /* 기본 스타일 */
    .card { border: 1px solid #ccc; border-radius: 4px; margin: 1rem; padding: 1rem; }
    .card-header { margin-bottom: 1rem; }
    .card-title { font-size: 1.5rem; font-weight: bold; }
    .card-content { }
    .alert { border: 1px solid red; background: #ffe6e6; padding: 1rem; border-radius: 4px; }
    .alert-description { margin: 0.5rem 0; }
    .button { padding: 0.5rem 1rem; margin: 0.5rem; cursor: pointer; border: none; }
    .dialog-content { border: 1px solid #000; padding: 1rem; background: #fff; position: fixed; top: 20%; left: 20%; width: 60%; z-index: 1000; }
    .dialog-overlay { position: fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.5); z-index: 999; }
    .p-6 { padding: 1.5rem; }
    .space-y-6 > * + * { margin-top: 1.5rem; }
    .flex { display: flex; }
    .justify-between { justify-content: space-between; }
    .items-start { align-items: flex-start; }
    .text-2xl { font-size: 1.5rem; }
    .font-bold { font-weight: bold; }
    .mb-2 { margin-bottom: 0.5rem; }
    .text-gray-600 { color: #718096; }
    .grid { display: grid; }
    .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
    .gap-6 { gap: 1.5rem; }
    .mt-6 { margin-top: 1.5rem; }
    .bg-blue-600 { background-color: #3182ce; color: white; }
    .hover\:bg-blue-700:hover { background-color: #2b6cb0; }
    .outline { border: 1px solid #ccc; }
    .destructive { background-color: #e53e3e; color: white; }
    .w-96 { width: 24rem; }
    /* 모달 다이얼로그 기본 숨김 */
    .dialog-overlay { display: none; }
  </style>
</head>
<body>
  <!-- 대시보드 컨테이너 -->
  <div class="p-6 space-y-6" id="dashboard">
    <!-- 헤더 영역 -->
    <div class="flex justify-between items-start" id="header">
      <div>
        <h1 class="text-2xl font-bold mb-2">활동분석</h1>
        <p class="text-gray-600" id="studentInfo"></p>
      </div>
      <div id="riskAlertContainer"></div>
    </div>
    
    <!-- 차트 그리드 -->
    <div class="grid grid-cols-2 gap-6">
      <!-- 차트 카드 1: 학습 참여도 트렌드 -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">학습 참여도 트렌드</h3>
        </div>
        <div class="card-content">
          <canvas id="chart1"></canvas>
        </div>
      </div>
      <!-- 차트 카드 2: 학습 품질 지표 -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">학습 품질 지표</h3>
        </div>
        <div class="card-content">
          <canvas id="chart2"></canvas>
        </div>
      </div>
      <!-- 차트 카드 3: 행동 지표 -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">행동 지표</h3>
        </div>
        <div class="card-content">
          <canvas id="chart3"></canvas>
        </div>
      </div>
      <!-- 차트 카드 4: 만족도 트렌드 -->
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">만족도 트렌드</h3>
        </div>
        <div class="card-content">
          <canvas id="chart4"></canvas>
        </div>
      </div>
    </div>
    
    <!-- 활동 분석 도구 카드 -->
    <div class="card mt-6">
      <div class="card-header">
        <h3 class="card-title">활동 분석 도구</h3>
      </div>
      <div class="card-content">
        <div class="flex gap-4">
          <button id="openImageDialog" class="button bg-blue-600 hover:bg-blue-700">활동결과 페이지 이미지 추가 (최근 한달)</button>
          <button id="trendReportBtn" class="button outline">추이 분석 리포트 작성</button>
          <button id="improvementBtn" class="button destructive">시스템 개선 제안</button>
        </div>
        <!-- 이미지 미리보기 영역 -->
        <div class="mt-4" id="imagePreviewContainer"></div>
      </div>
    </div>
  </div>
  
  <!-- 이미지 업로드용 모달 다이얼로그 -->
  <div id="imageDialog" class="dialog-overlay">
    <div class="dialog-content">
      <div class="dialog-header">
        <h2 class="dialog-title">활동결과 이미지 추가</h2>
      </div>
      <div class="grid gap-4 py-4">
        <input type="file" id="imageInput" accept="image/*">
        <p id="selectedFileName" class="text-sm text-gray-500"></p>
        <button id="closeImageDialog" class="button">닫기</button>
      </div>
    </div>
  </div>
  
  <!-- 추이 분석 리포트 작성 모달 다이얼로그 -->
  <div id="trendReportDialog" class="dialog-overlay">
    <div class="dialog-content">
      <div class="dialog-header">
        <h2 class="dialog-title">추이 분석 리포트 작성</h2>
      </div>
      <div class="dialog-body">
        <textarea id="trendReportText" style="width:100%; height:150px;" placeholder="리포트를 작성하세요..."></textarea>
      </div>
      <div class="dialog-footer" style="text-align:right; margin-top: 10px;">
        <button id="closeTrendReportDialog" class="button">닫기</button>
        <button id="submitTrendReport" class="button bg-blue-600">작성 완료</button>
      </div>
    </div>
  </div>
  
  <!-- 시스템 개선 제안 모달 다이얼로그 -->
  <div id="improvementDialog" class="dialog-overlay">
    <div class="dialog-content">
      <div class="dialog-header">
        <h2 class="dialog-title">시스템 개선 제안</h2>
      </div>
      <div class="dialog-body">
        <textarea id="improvementText" style="width:100%; height:150px;" placeholder="개선 사항을 작성하세요..."></textarea>
      </div>
      <div class="dialog-footer" style="text-align:right; margin-top: 10px;">
        <button id="closeImprovementDialog" class="button">닫기</button>
        <button id="submitImprovement" class="button bg-blue-600">제출</button>
      </div>
    </div>
  </div>
  
  <script>
    // 샘플 학생 데이터
    const studentData = {
      name: "김철수",
      id: "2024001",
      metrics: [
        { date: "2024-01", studyLog: 85, calmness: 75, deviation: 15, delay: 20, satisfaction: 70, logFrequency: 80, quizResults: 65, missedNotes: 30, qaCount: 45, paperEval: 60 },
        { date: "2024-02", studyLog: 75, calmness: 65, deviation: 25, delay: 30, satisfaction: 60, logFrequency: 70, quizResults: 55, missedNotes: 40, qaCount: 35, paperEval: 50 },
        { date: "2024-03", studyLog: 60, calmness: 55, deviation: 35, delay: 45, satisfaction: 50, logFrequency: 60, quizResults: 45, missedNotes: 50, qaCount: 25, paperEval: 40 }
      ]
    };
    
    // 위험 지표 계산 함수
    function calculateRiskLevel(metrics) {
      const latestMetrics = metrics[metrics.length - 1];
      const previousMetrics = metrics[metrics.length - 2];
      let riskCount = 0;
      let riskFactors = [];
      
      if (latestMetrics.studyLog < 70 && latestMetrics.studyLog < previousMetrics.studyLog) {
        riskCount++;
        riskFactors.push("학습일지 작성률 저하");
      }
      if (latestMetrics.calmness < 60) {
        riskCount++;
        riskFactors.push("침착도 저하");
      }
      if (latestMetrics.deviation > 30) {
        riskCount++;
        riskFactors.push("이탈도 증가");
      }
      if (latestMetrics.delay > 40) {
        riskCount++;
        riskFactors.push("과제 지연률 증가");
      }
      if (latestMetrics.satisfaction < 55) {
        riskCount++;
        riskFactors.push("만족도 저하");
      }
      
      return { riskCount, riskFactors };
    }
    
    // DOM 로드 후 초기화
    document.addEventListener("DOMContentLoaded", function() {
      // 학생 정보 표시
      const studentInfoEl = document.getElementById("studentInfo");
      studentInfoEl.textContent = `학생: ${studentData.name} (ID: ${studentData.id})`;
      
      // 위험 알림 표시 (위험 요인이 3개 이상일 경우)
      const risk = calculateRiskLevel(studentData.metrics);
      if (risk.riskCount >= 3) {
        const riskAlertContainer = document.getElementById("riskAlertContainer");
        const alertDiv = document.createElement("div");
        alertDiv.className = "alert destructive w-96";
        alertDiv.innerHTML = `<span style="color:red; font-weight:bold;">⚠</span>
          <div class="alert-description">
            <strong>퇴원 위험 감지</strong>
            <ul class="mt-2 list-disc list-inside">
              ${risk.riskFactors.map(factor => `<li>${factor}</li>`).join('')}
            </ul>
          </div>`;
        riskAlertContainer.appendChild(alertDiv);
      }
      
      // 차트 데이터 준비
      const labels = studentData.metrics.map(m => m.date);
      
      // 차트 1: 학습 참여도 트렌드 (studyLog, logFrequency, qaCount)
      const chart1Ctx = document.getElementById("chart1").getContext("2d");
      new Chart(chart1Ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            { label: '학습일지', data: studentData.metrics.map(m => m.studyLog), borderColor: '#8884d8', fill: false },
            { label: '일지작성주기', data: studentData.metrics.map(m => m.logFrequency), borderColor: '#82ca9d', fill: false },
            { label: '질의응답수', data: studentData.metrics.map(m => m.qaCount), borderColor: '#ffc658', fill: false }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
      
      // 차트 2: 학습 품질 지표 (quizResults, paperEval, missedNotes)
      const chart2Ctx = document.getElementById("chart2").getContext("2d");
      new Chart(chart2Ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            { label: '퀴즈결과', data: studentData.metrics.map(m => m.quizResults), borderColor: '#8884d8', fill: false },
            { label: '지면평가', data: studentData.metrics.map(m => m.paperEval), borderColor: '#82ca9d', fill: false },
            { label: '오답노트밀림', data: studentData.metrics.map(m => m.missedNotes), borderColor: '#ff7300', fill: false }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
      
      // 차트 3: 행동 지표 (calmness, deviation, delay)
      const chart3Ctx = document.getElementById("chart3").getContext("2d");
      new Chart(chart3Ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            { label: '침착도', data: studentData.metrics.map(m => m.calmness), borderColor: '#8884d8', fill: false },
            { label: '이탈도', data: studentData.metrics.map(m => m.deviation), borderColor: '#ff7300', fill: false },
            { label: '지연율', data: studentData.metrics.map(m => m.delay), borderColor: '#82ca9d', fill: false }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
      
      // 차트 4: 만족도 트렌드 (satisfaction)
      const chart4Ctx = document.getElementById("chart4").getContext("2d");
      new Chart(chart4Ctx, {
        type: 'line',
        data: {
          labels: labels,
          datasets: [
            { label: '만족도', data: studentData.metrics.map(m => m.satisfaction), borderColor: '#8884d8', fill: false }
          ]
        },
        options: { responsive: true, maintainAspectRatio: false }
      });
      
      // 이미지 업로드 다이얼로그 관련 처리
      const imageDialog = document.getElementById("imageDialog");
      const openImageDialogBtn = document.getElementById("openImageDialog");
      const closeImageDialogBtn = document.getElementById("closeImageDialog");
      const imageInput = document.getElementById("imageInput");
      const selectedFileNameEl = document.getElementById("selectedFileName");
      const imagePreviewContainer = document.getElementById("imagePreviewContainer");
      
      openImageDialogBtn.addEventListener("click", function() {
        imageDialog.style.display = "block";
      });
      
      closeImageDialogBtn.addEventListener("click", function() {
        imageDialog.style.display = "none";
      });
      
      imageInput.addEventListener("change", function(event) {
        const file = event.target.files[0];
        if (file) {
          selectedFileNameEl.textContent = `선택된 파일: ${file.name}`;
          const reader = new FileReader();
          reader.onloadend = function() {
            const imgUrl = reader.result;
            imagePreviewContainer.innerHTML = `
              <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold mb-2">추가된 활동결과 이미지</h3>
                <img src="${imgUrl}" alt="활동결과" style="width:100%; max-width:800px; display:block; margin:auto;">
              </div>
            `;
          };
          reader.readAsDataURL(file);
          imageDialog.style.display = "none";
        }
      });
      
      // 추이 분석 리포트 다이얼로그 관련 처리
      const trendReportDialog = document.getElementById("trendReportDialog");
      const openTrendReportBtn = document.getElementById("trendReportBtn");
      const closeTrendReportBtn = document.getElementById("closeTrendReportDialog");
      const submitTrendReportBtn = document.getElementById("submitTrendReport");
      const trendReportText = document.getElementById("trendReportText");
      
      openTrendReportBtn.addEventListener("click", function() {
        trendReportDialog.style.display = "block";
      });
      
      closeTrendReportBtn.addEventListener("click", function() {
        trendReportDialog.style.display = "none";
      });
      
      submitTrendReportBtn.addEventListener("click", function() {
        const reportContent = trendReportText.value;
        if(reportContent.trim() === "") {
          alert("리포트를 작성해 주세요.");
        } else {
          alert("추이 분석 리포트가 제출되었습니다:\n" + reportContent);
          trendReportText.value = "";
          trendReportDialog.style.display = "none";
        }
      });
      
      // 시스템 개선 제안 다이얼로그 관련 처리
      const improvementDialog = document.getElementById("improvementDialog");
      const openImprovementBtn = document.getElementById("improvementBtn");
      const closeImprovementBtn = document.getElementById("closeImprovementDialog");
      const submitImprovementBtn = document.getElementById("submitImprovement");
      const improvementText = document.getElementById("improvementText");
      
      openImprovementBtn.addEventListener("click", function() {
        improvementDialog.style.display = "block";
      });
      
      closeImprovementBtn.addEventListener("click", function() {
        improvementDialog.style.display = "none";
      });
      
      submitImprovementBtn.addEventListener("click", function() {
        const improvementContent = improvementText.value;
        if(improvementContent.trim() === "") {
          alert("개선 제안을 작성해 주세요.");
        } else {
          alert("시스템 개선 제안이 제출되었습니다:\n" + improvementContent);
          improvementText.value = "";
          improvementDialog.style.display = "none";
        }
      });
      
      // 닫기 위한 외부 클릭 처리 (옵션)
      window.addEventListener("click", function(event) {
        if (event.target === imageDialog) {
          imageDialog.style.display = "none";
        }
        if (event.target === trendReportDialog) {
          trendReportDialog.style.display = "none";
        }
        if (event.target === improvementDialog) {
          improvementDialog.style.display = "none";
        }
      });
    });
  </script>
</body>
</html>
