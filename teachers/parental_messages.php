<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $USER->id;
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>학부모 상담 관리 시스템</title>
  <!-- Noto Sans KR 폰트 -->
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;700&display=swap" rel="stylesheet">
  <style>
    /* Global Styles (헤더 및 네비게이션) */
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

    /* 상담관리 시스템 콘텐츠 (기존 코드에서 header 부분 제외) */
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 20px;
    }
    .content-inner {
      background-color: white;
      border-radius: 5px;
      padding: 20px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .search-section {
      margin-bottom: 20px;
      display: flex;
      align-items: center;
    }
    .search-input {
      flex: 1;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    .search-button {
      margin-left: 10px;
      padding: 10px 20px;
      background-color: #3f51b5;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    .search-button:hover {
      background-color: #303f9f;
    }
    .student-list {
      max-height: 200px;
      overflow-y: auto;
      border: 1px solid #ddd;
      border-radius: 4px;
      margin-bottom: 20px;
      display: none;
    }
    .student-item {
      padding: 10px 15px;
      border-bottom: 1px solid #eee;
      cursor: pointer;
    }
    .student-item:hover {
      background-color: #f5f5f5;
    }
    .student-item:last-child {
      border-bottom: none;
    }
    .input-section {
      margin-bottom: 20px;
      display: none;
    }
    .selected-student {
      margin-bottom: 15px;
      padding: 10px;
      background-color: #e8eaf6;
      border-radius: 4px;
      font-weight: bold;
    }
    .form-group {
      margin-bottom: 15px;
    }
    .form-label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .form-control {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }
    textarea.form-control {
      min-height: 150px;
      resize: vertical;
    }
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
    }
    .btn {
      padding: 10px 20px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
    }
    .btn-primary {
      background-color: #3f51b5;
      color: white;
    }
    .btn-primary:hover {
      background-color: #303f9f;
    }
    .btn-secondary {
      background-color: #f5f5f5;
      color: #333;
      border: 1px solid #ddd;
    }
    .btn-secondary:hover {
      background-color: #e0e0e0;
    }
    .history-section {
      margin-top: 30px;
    }
    .history-title {
      font-size: 18px;
      margin-bottom: 15px;
      padding-bottom: 5px;
      border-bottom: 2px solid #3f51b5;
    }
    .history-table {
      width: 100%;
      border-collapse: collapse;
    }
    .history-table th,
    .history-table td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    .history-table th {
      background-color: #f5f5f5;
      font-weight: bold;
    }
    .history-table tr:hover {
      background-color: #f9f9f9;
    }
    .status-pending {
      color: #ff9800;
      font-weight: bold;
    }
    .status-completed {
      color: #4caf50;
      font-weight: bold;
    }
    .transfer-yes {
      color: #4caf50;
    }
    .transfer-no {
      color: #f44336;
    }
  </style>
</head>
<body>
  <!-- Global Header 및 네비게이션 -->
  <div class="header">
    <div class="top-bar">
      <div class="academy-name">카이스트 터치수학 학원</div>
      <div class="user-info">
        <span>관리자님</span>
        <img src="/api/placeholder/32/32" alt="사용자 프로필">
      </div>
    </div>
    <nav class="nav-menu">
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/frontdesk_registration.php?userid=<?php echo $userid; ?>" class="nav-item">수강신청</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/frontdesk_classtimemanagement.php?userid=<?php echo $userid; ?>" class="nav-item">출결관리</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/parental_messages.php?userid=<?php echo $userid; ?>" class="nav-item active">상담관리</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/settlement.php?userid=<?php echo $userid; ?>" class="nav-item">정산관리</a>
    </nav>
  </div>
  
  <!-- 페이지 콘텐츠 -->
  <div class="content">
    <div class="container">
      <div class="content-inner">
        <div class="search-section">
          <input type="text" class="search-input" placeholder="학생이름을 입력하세요" id="studentSearch">
          <button class="search-button" onclick="searchStudent()">검색</button>
        </div>
        
        <div class="student-list" id="studentList">
          <!-- 검색 결과 학생 목록이 여기에 표시됩니다 -->
        </div>
        
        <div class="input-section" id="inputSection">
          <div class="selected-student" id="selectedStudent">
            <!-- 선택된 학생 정보가 여기에 표시됩니다 -->
          </div>
          
          <div class="form-group">
            <label class="form-label">상담 일자</label>
            <input type="date" class="form-control" id="consultDate">
          </div>
          
          <div class="form-group">
            <label class="form-label">상담 내용</label>
            <textarea class="form-control" id="consultContent" placeholder="학부모 상담 내용을 입력하세요"></textarea>
          </div>
          
          <div class="form-group">
            <label class="form-label">상담 상태</label>
            <select class="form-control" id="consultStatus">
              <option value="pending">진행중</option>
              <option value="completed">완료</option>
            </select>
          </div>
          
          <div class="form-group">
            <label class="form-label">강의실 공유</label>
            <select class="form-control" id="shareStatus">
              <option value="yes">예</option>
              <option value="no">아니오</option>
            </select>
          </div>
          
          <div class="form-actions">
            <button class="btn btn-secondary" onclick="cancelConsult()">취소</button>
            <button class="btn btn-primary" onclick="saveConsult()">저장</button>
          </div>
        </div>
        
        <div class="history-section">
          <div class="history-title">최근 상담 이력</div>
          <table class="history-table">
            <thead>
              <tr>
                <th>이름</th>
                <th>내용</th>
                <th>상태</th>
                <th>전달</th>
                <th>날짜</th>
              </tr>
            </thead>
            <tbody id="historyTableBody">
              <!-- 상담 이력이 여기에 표시됩니다 -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <script>
    // 샘플 학생 데이터
    const students = [
      { id: 1, name: "김민준", grade: "3", class: "A" },
      { id: 2, name: "박서연", grade: "2", class: "B" },
      { id: 3, name: "이도윤", grade: "1", class: "C" },
      { id: 4, name: "최지우", grade: "3", class: "B" },
      { id: 5, name: "정예준", grade: "2", class: "A" }
    ];
    
    // 샘플 상담 이력 데이터
    const consultHistory = [
      { id: 1, studentId: 2, studentName: "박서연", content: "수학 성적 향상에 관한 상담", status: "completed", shared: "yes", date: "2025-03-01" },
      { id: 2, studentId: 4, studentName: "최지우", content: "교우관계 문제에 관한 상담", status: "pending", shared: "yes", date: "2025-02-28" },
      { id: 3, studentId: 1, studentName: "김민준", content: "진로 상담", status: "completed", shared: "no", date: "2025-02-25" },
      { id: 4, studentId: 5, studentName: "정예준", content: "방과후 활동 상담", status: "completed", shared: "yes", date: "2025-02-20" },
      { id: 5, studentId: 3, studentName: "이도윤", content: "독서 습관 형성에 관한 상담", status: "pending", shared: "no", date: "2025-02-15" }
    ];
    
    // 페이지 로드 시 상담 이력 표시
    window.onload = function() {
      loadConsultHistory();
    };
    
    // 학생 검색 함수
    function searchStudent() {
      const searchTerm = document.getElementById('studentSearch').value.trim();
      const studentList = document.getElementById('studentList');
      
      if (searchTerm === '') {
        studentList.style.display = 'none';
        return;
      }
      
      // 검색어에 맞는 학생 필터링
      const filteredStudents = students.filter(student => 
        student.name.includes(searchTerm)
      );
      
      // 검색 결과 표시
      studentList.innerHTML = '';
      if (filteredStudents.length > 0) {
        filteredStudents.forEach(student => {
          const studentItem = document.createElement('div');
          studentItem.className = 'student-item';
          studentItem.textContent = `${student.name} (${student.grade}학년 ${student.class}반)`;
          studentItem.onclick = function() {
            selectStudent(student);
          };
          studentList.appendChild(studentItem);
        });
        studentList.style.display = 'block';
      } else {
        const noResult = document.createElement('div');
        noResult.className = 'student-item';
        noResult.textContent = '검색 결과가 없습니다.';
        studentList.appendChild(noResult);
        studentList.style.display = 'block';
      }
    }
    
    // 학생 선택 함수
    function selectStudent(student) {
      const selectedStudent = document.getElementById('selectedStudent');
      const inputSection = document.getElementById('inputSection');
      const studentList = document.getElementById('studentList');
      
      selectedStudent.textContent = `${student.name} (${student.grade}학년 ${student.class}반)`;
      selectedStudent.dataset.studentId = student.id;
      
      // 입력 폼 초기화 및 표시
      document.getElementById('consultDate').value = new Date().toISOString().split('T')[0];
      document.getElementById('consultContent').value = '';
      document.getElementById('consultStatus').value = 'pending';
      document.getElementById('shareStatus').value = 'yes';
      
      inputSection.style.display = 'block';
      studentList.style.display = 'none';
      document.getElementById('studentSearch').value = '';
    }
    
    // 상담 취소 함수
    function cancelConsult() {
      document.getElementById('inputSection').style.display = 'none';
    }
    
    // 상담 저장 함수
    function saveConsult() {
      const studentId = document.getElementById('selectedStudent').dataset.studentId;
      const studentName = document.getElementById('selectedStudent').textContent.split(' ')[0];
      const content = document.getElementById('consultContent').value;
      const status = document.getElementById('consultStatus').value;
      const shared = document.getElementById('shareStatus').value;
      const date = document.getElementById('consultDate').value;
      
      if (!content) {
        alert('상담 내용을 입력해주세요.');
        return;
      }
      
      // 새 상담 이력 추가
      const newConsult = {
        id: consultHistory.length + 1,
        studentId: parseInt(studentId),
        studentName: studentName,
        content: content,
        status: status,
        shared: shared,
        date: date
      };
      
      consultHistory.unshift(newConsult); // 최신 항목을 맨 앞에 추가
      
      // 화면 업데이트
      loadConsultHistory();
      cancelConsult();
      
      alert('상담 내용이 저장되었습니다.');
    }
    
    // 상담 이력 로드 함수
    function loadConsultHistory() {
      const historyTableBody = document.getElementById('historyTableBody');
      historyTableBody.innerHTML = '';
      
      consultHistory.forEach(consult => {
        const row = document.createElement('tr');
        
        const nameCell = document.createElement('td');
        nameCell.textContent = consult.studentName;
        row.appendChild(nameCell);
        
        const contentCell = document.createElement('td');
        contentCell.textContent = consult.content.length > 30 ? 
          consult.content.substring(0, 30) + '...' : consult.content;
        contentCell.title = consult.content;
        row.appendChild(contentCell);
        
        const statusCell = document.createElement('td');
        statusCell.textContent = consult.status === 'pending' ? '진행중' : '완료';
        statusCell.className = consult.status === 'pending' ? 'status-pending' : 'status-completed';
        row.appendChild(statusCell);
        
        const sharedCell = document.createElement('td');
        sharedCell.textContent = consult.shared === 'yes' ? '예' : '아니오';
        sharedCell.className = consult.shared === 'yes' ? 'transfer-yes' : 'transfer-no';
        row.appendChild(sharedCell);
        
        const dateCell = document.createElement('td');
        dateCell.textContent = consult.date;
        row.appendChild(dateCell);
        
        historyTableBody.appendChild(row);
      });
    }
  </script>
</body>
</html>
