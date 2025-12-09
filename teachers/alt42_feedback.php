<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
session_start();

// 현재 뷰를 결정하는 변수
if (isset($_GET['view'])) {
    $currentView = $_GET['view'];
} else {
    $currentView = 'student';
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>학습 관리 시스템</title>
    <link rel="stylesheet" href="style.css"> <!-- 스타일시트를 연결합니다. -->
</head>
<body>
    <!-- 네비게이션 -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-brand">
                <span class="icon">&#128218;</span>
                <span>학습 관리 시스템</span>
            </div>
            <div class="navbar-menu">
                <a href="?view=student" class="<?php echo $currentView == 'student' ? 'active' : ''; ?>">학생 뷰</a>
                <a href="?view=teacher" class="<?php echo $currentView == 'teacher' ? 'active' : ''; ?>">교사 뷰</a>
                <a href="?view=dashboard" class="<?php echo $currentView == 'dashboard' ? 'active' : ''; ?>">대시보드</a>
            </div>
        </div>
    </nav>

    <!-- 콘텐츠 영역 -->
    <div class="container">
        <?php
        if ($currentView == 'student') {
            include 'student_view.php';
        } elseif ($currentView == 'teacher') {
            include 'teacher_view.php';
        } elseif ($currentView == 'dashboard') {
            include 'dashboard.php';
        }
        ?>
    </div>
</body>
</html>
 