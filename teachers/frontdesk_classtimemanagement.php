<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();
$userid = $USER->id;  // 사용자 아이디 설정

// 최근 1개월(30일) 기준 타임스탬프 계산
$oneMonthAgo = time() - (30 * 24 * 60 * 60);

// 같은 사용자 아이디에 대해 가장 최근 정보만 표시하기 위한 쿼리
$sql = "SELECT *
FROM {abessi_classtimemanagement} ac
WHERE ac.timecreated >= ? AND ac.needmakeup > ?
  AND ac.timecreated = (
    SELECT MAX(ac2.timecreated)
    FROM {abessi_classtimemanagement} ac2
    WHERE ac2.userid = ac.userid
  )";
$params = [$oneMonthAgo, 4];
$records = $DB->get_records_sql($sql, $params);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>클래스 관리 학생 목록</title>
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
    
    /* 표 디자인 */
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
  </style>
</head>
<body class="registration">
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
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/frontdesk_classtimemanagement.php?userid=<?php echo $userid; ?>" class="nav-item active">출결관리</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/parental_messages.php?userid=<?php echo $userid; ?>" class="nav-item">상담관리</a>
      <a href="https://mathking.kr/moodle/local/augmented_teacher/teachers/settlement.php?userid=<?php echo $userid; ?>" class="nav-item">정산관리</a>
    </nav>
  </div>
  
  <div class="content">
    <h1 class="page-title">수강일 조정 대상 학생 목록</h1>
    <table>
      <thead>
        <tr>
          <th>학생이름</th>
          <th>등록일</th>
          <th>보강 미설정 (시간)</th>
        </tr>
      </thead>
      <tbody>
        <?php 
        if ($records) {
            foreach ($records as $record) {
                // 학생 정보 가져오기 (user 테이블 이용)
                $student = $DB->get_record('user', ['id' => $record->userid]);
                $studentName = $student ? $student->firstname . " " . $student->lastname : "알 수 없음";
                // 등록일 (timecreated)를 Y-m-d 형식으로 변환
                $registerDate = date('Y-m-d', $record->timecreated);
                $needmakeup = $record->needmakeup;
                
                echo "<tr>";
                echo "<td><a href='https://mathking.kr/moodle/local/augmented_teacher/students/attendancerecords.php?userid={$record->userid}'>{$studentName}</a></td>";
                echo "<td>{$registerDate}</td>";
                echo "<td>{$needmakeup}</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' style='text-align: center;'>최근 1개월 내 등록된 학생이 없습니다.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</body>
</html>
