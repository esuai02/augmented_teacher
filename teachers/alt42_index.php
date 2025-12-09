<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
session_start();
$userid= $_GET['userid'];

$userrole=$DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22' "); 
$role=$userrole->role;


// 현재 뷰를 결정하는 변수
if (isset($_GET['view'])) {
    $currentView = $_GET['view'];
} else {
    $currentView = 'student';
}


if($role==='student' && $currentView = 'student')
{
    echo '<script>alert("권한이 없습니다."); window.location.href="https://mathking.kr/moodle/local/augmented_teacher/teachers/dashboard.php";</script>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Alt42</title>
    <link rel="stylesheet" href="alt42_style.css"> <!-- 스타일시트를 연결합니다. -->
</head>
<body>
    <!-- 네비게이션 -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <span>👩🏻‍🦱 포모도르 촉진 시스템 (인과관계 맵핑 & 선택추적)</span> 
                
            </div> <br> <br> 
            <div class="navbar-menu">
                <a href="?view=student" class="<?php echo $currentView == 'student' ? 'active' : ''; ?>">학생</a>
                <a href="?view=teacher" class="<?php echo $currentView == 'teacher' ? 'active' : ''; ?>">선생님</a>
                <a href="?view=dashboard" class="<?php echo $currentView == 'dashboard' ? 'active' : ''; ?>">Tracking</a>
            </div>
        </div>
    </nav>

    <!-- 콘텐츠 영역 -->
    <div class="container">
        <?php
        if ($currentView == 'student') {
            include 'alt42_student_view.php';
        } elseif ($currentView == 'teacher') {
            include 'alt42_teacher_view.php';
        } elseif ($currentView == 'dashboard') {
            include 'alt42_dashboard.php';
        }
        ?>
    </div>
</body>
</html>
 