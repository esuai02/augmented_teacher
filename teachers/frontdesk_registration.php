<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();

$userid = $USER->id; // 사용자 아이디 설정

// 사용자 역할 조회 (예: fieldid가 22이면 role)
$userrole = $DB->get_record_sql(
    "SELECT data AS role FROM mdl_user_info_data WHERE userid = :userid AND fieldid = 22", 
    ['userid' => $userid]
);
$role = $userrole ? $userrole->role : '';

// 상태 매핑 배열 (DB에 저장된 값 → 한글표시)
$statusMapping = [
    'begin'    => '대기중',
    'inactive' => '취소',
    'active'   => '수강중'
];

// 최근 1개월(30일) 기준 기록만 조회
$oneMonthAgo = time() - (30 * 24 * 60 * 60);
$registrations = $DB->get_records_select('abessi_registration', 'timecreated >= ?', [$oneMonthAgo]);

// 정렬: 대기중('begin') → 수강중('active') → 취소('inactive')
$registrationArray = $registrations ? array_values($registrations) : [];
$order = ['begin' => 1, 'active' => 2, 'inactive' => 3];
usort($registrationArray, function($a, $b) use ($order) {
    $statusA = isset($order[$a->status]) ? $order[$a->status] : 99;
    $statusB = isset($order[$b->status]) ? $order[$b->status] : 99;
    return $statusA - $statusB;
});
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>수강신청 현황판</title>
  <!-- Noto Sans KR 폰트 -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Noto Sans KR', sans-serif;
    }
    
    body {
      background-color: #f5f7fa;
    }
    
    .header {
      background-color: #ffffff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      position: fixed;
      width: 100%;
      top: 0;
      z-index: 1000;
    }
    
    .top-bar {
      background-color: #3b5cb8;
      color: white;
      padding: 12px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    
    .academy-name {
      font-size: 20px;
      font-weight: 700;
    }
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    
    .user-info img {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background-color: #ffffff;
      padding: 2px;
    }
    
    .nav-menu {
      display: flex;
      justify-content: center;
      padding: 0 20px;
    }
    
    .nav-item {
      padding: 18px 30px;
      font-size: 16px;
      font-weight: 500;
      color: #555;
      text-decoration: none;
      position: relative;
      transition: all 0.3s ease;
    }
    
    .nav-item:hover {
      color: #3b5cb8;
    }
    
    .nav-item.active {
      color: #3b5cb8;
      font-weight: 700;
    }
    
    .nav-item.active::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 50%;
      height: 3px;
      background-color: #3b5cb8;
    }
    
    .content {
      margin-top: 120px;
      padding: 20px;
    }
    
    .page-title {
      font-size: 24px;
      font-weight: 700;
      color: #333;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 2px solid #eee;
    }
    
    /* Table styling */
    table {
      width: 100%;
      border-collapse: collapse;
      background-color: #fff;
      border: 1px solid #ddd;
    }
    
    th, td {
      padding: 12px;
      border: 1px solid #ddd;
      text-align: left;
    }
    
    th {
      background-color: #f2f2f2;
    }
    
    a {
      color: #3b5cb8;
      text-decoration: none;
    }
    
    a:hover {
      text-decoration: underline;
    }
    
    /* Statistics boxes */
    .stats {
      margin-top: 20px;
      display: flex;
      gap: 20px;
    }
    
    .stat-box {
      flex: 1;
      padding: 15px;
      text-align: center;
      border: 1px solid #ddd;
      border-radius: 4px;
      background-color: #fff;
    }
    
    /* Modal styling */
    .modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal.hidden {
      display: none !important;
    }
    .modal-content {
      background-color: #fff;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
      max-width: 400px;
      width: 90%;
      text-align: center;
    }
    .modal-content h2 {
      font-size: 20px;
      margin-bottom: 10px;
    }
    .modal-content p {
      margin-bottom: 20px;
    }
    .modal-buttons {
      display: flex;
      justify-content: space-around;
      margin-bottom: 20px;
    }
    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 14px;
    }
    .btn-green {
      background-color: #28a745;
      color: #fff;
    }
    .btn-yellow {
      background-color: #ffc107;
      color: #000;
    }
    .btn-red {
      background-color: #dc3545;
      color: #fff;
    }
    .btn-secondary {
      background-color: #6c757d;
      color: #fff;
    }
  </style>
</head>
<body class="registration">
  <!-- 상단 헤더 및 네비게이션 -->
  <div class="header">
    <div class="top-bar">
      <div class="academy-name">카이스트 터치수학 학원</div>
      <div class="user-info">
        <span>관리자님</span>
        <img src="/api/placeholder/32/32" alt="사용자 프로필">
      </div>
    </div>
    <nav class="nav-menu">
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/frontdesk_registration.php?userid=<?php echo $userid; ?>" class="nav-item active">수강신청</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/frontdesk_classtimemanagement.php?userid=<?php echo $userid; ?>" class="nav-item">출결관리</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/parental_messages.php?userid=<?php echo $userid; ?>" class="nav-item">상담관리</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/settlement.php?userid=<?php echo $userid; ?>" class="nav-item">정산관리</a>
    </nav>
  </div>
  
  <!-- 페이지 콘텐츠 -->
  <div class="content">
    <h1 class="page-title">수강신청 현황판 (최근 1개월)</h1>
    
    <!-- 수강신청 현황판 테이블 -->
    <table>
      <thead>
        <tr>
          <th>학생이름</th>
          <th>시작일</th>
          <th>공부시간</th>
          <th>특강유형</th>
          <th>상태</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        if ($registrationArray) {
            foreach ($registrationArray as $registration) {
                // 학생 정보 (mdl_user 테이블)
                $student = $DB->get_record('user', ['id' => $registration->studentid]);
                $studentName = $student ? $student->firstname . " " . $student->lastname : "알 수 없음";
                $startDate = date('Y-m-d', $registration->timecreated);
                $studyHours = isset($registration->studyHours) ? $registration->studyHours : "";
                $courseType = isset($registration->courseType) ? $registration->courseType : "";
                $status = $registration->status;
                $displayStatus = isset($statusMapping[$status]) ? $statusMapping[$status] : $status;
                
                // 상태에 따른 행 배경색 (색상은 예시)
                if ($status == 'active') {
                    $rowStyle = "background-color: #e6f9e9;";
                } elseif ($status == 'inactive') {
                    $rowStyle = "background-color: #fce8e6;";
                } elseif ($status == 'begin') {
                    $rowStyle = "background-color: #fff9e6;";
                } else {
                    $rowStyle = "";
                }
                
                echo "<tr style='{$rowStyle}'>";
                echo "<td class='border p-2'>{$studentName}</td>";
                echo "<td class='border p-2'>{$startDate}</td>";
                echo "<td class='border p-2'>{$studyHours}시간</td>";
                echo "<td class='border p-2'>{$courseType}</td>";
                echo "<td class='border p-2'><span id='status-{$registration->id}' style='color:#3b5cb8; cursor:pointer;' onclick=\"openStatusModal({$registration->id}, '{$status}')\">{$displayStatus}</span></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='5' style='text-align: center;'>최근 1개월 내 등록 내역이 없습니다.</td></tr>";
        }
        ?>
      </tbody>
    </table>
    
    <!-- 통계 정보 -->
    <?php 
      $countActive = $countBegin = $countInactive = 0;
      if($registrationArray){
          foreach($registrationArray as $registration){
              if($registration->status == 'active') { $countActive++; }
              if($registration->status == 'begin') { $countBegin++; }
              if($registration->status == 'inactive') { $countInactive++; }
          }
      }
    ?>
    <div class="stats">
      <div class="stat-box">
        <h3>수강중 학생 수</h3>
        <p><?php echo $countActive; ?>명</p>
      </div>
      <div class="stat-box">
        <h3>대기중 학생 수</h3>
        <p><?php echo $countBegin; ?>명</p>
      </div>
      <div class="stat-box">
        <h3>취소 학생 수</h3>
        <p><?php echo $countInactive; ?>명</p>
      </div>
    </div>
  </div>
  
  <!-- 상태 변경을 위한 모달 팝업 -->
  <div id="statusModal" class="modal hidden">
    <div class="modal-content">
      <h2>상태 변경</h2>
      <p>원하는 상태를 선택하세요.</p>
      <div class="modal-buttons">
        <button onclick="updateStatus('active')" class="btn btn-green">수강중</button>
        <button onclick="updateStatus('begin')" class="btn btn-yellow">대기중</button>
        <button onclick="updateStatus('inactive')" class="btn btn-red">취소</button>
      </div>
      <button onclick="closeStatusModal()" class="btn btn-secondary">취소</button>
    </div>
  </div>
  
  <script>
    let currentRegistrationId = null;
    
    // 모달 열기 (등록 id 저장)
    function openStatusModal(registrationId, currentStatus) {
      currentRegistrationId = registrationId;
      document.getElementById('statusModal').classList.remove('hidden');
    }
    
    // 모달 닫기
    function closeStatusModal() {
      document.getElementById('statusModal').classList.add('hidden');
      currentRegistrationId = null;
    }
    
    // 모달 외부 클릭 시 닫기
    window.onclick = function(event) {
      const modal = document.getElementById('statusModal');
      if (event.target === modal) {
        closeStatusModal();
      }
    };
    
    // 상태 업데이트 (AJAX 예시)
    function updateStatus(newStatus) {
      if (!currentRegistrationId) return;
      fetch('frontdesk_update.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: currentRegistrationId, status: newStatus })
      })
      .then(response => response.json())
      .then(data => {
        if(data.success) {
          const statusMapping = {
            'begin': '대기중',
            'inactive': '취소',
            'active': '수강중'
          };
          const cell = document.getElementById('status-' + currentRegistrationId);
          cell.innerText = statusMapping[newStatus] || newStatus;
          const row = cell.parentElement.parentElement;
          row.style.backgroundColor = newStatus === 'active' ? "#e6f9e9" : newStatus === 'inactive' ? "#fce8e6" : newStatus === 'begin' ? "#fff9e6" : "";
          closeStatusModal();
        } else {
          alert('상태 업데이트 실패: ' + data.message);
        }
      })
      .catch(error => {
        alert('상태 업데이트 실패: ' + error);
      });
    }
  </script>
</body>
</html>
