<?php
// 오류 출력 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Today2 디버그</title>
</head>
<body>
    <h1>Today2 디버그 페이지</h1>';

try {
    // 중요 변수 확인
    echo '<h2>변수 확인</h2>';
    
    // Moodle 설정 불러오기
    include_once("/home/moodle/public_html/moodle/config.php");
    echo '<p>Moodle 설정 로딩 완료</p>';
    
    global $DB, $USER;
    echo '<p>USER ID: ' . (isset($USER->id) ? $USER->id : '설정 안됨') . '</p>';
    
    // 네비게이션 바 로딩
    include("navbar.php");
    echo '<p>navbar.php 로딩 완료</p>';
    echo '<p>Role: ' . (isset($role) ? $role : '설정 안됨') . '</p>';
    echo '<p>Student ID: ' . (isset($studentid) ? $studentid : '설정 안됨') . '</p>';
    
    $tbegin = isset($_GET["tb"]) ? $_GET["tb"] : 604800; // 기본값 1주일
    echo '<p>시간 범위: ' . $tbegin . '초</p>';
    
    // 캐시 파일 경로 확인
    $cache_file = sys_get_temp_dir() . '/student_' . $studentid . '_cache_' . $tbegin . '.json';
    echo '<p>캐시 파일 경로: ' . $cache_file . '</p>';
    echo '<p>캐시 파일 존재: ' . (file_exists($cache_file) ? '예' : '아니오') . '</p>';
    
    // 데이터베이스 연결 확인
    echo '<h2>데이터베이스 연결 확인</h2>';
    
    try {
        $test_query = "SELECT COUNT(*) FROM mdl_user WHERE id = ?";
        $result = $DB->count_records_sql($test_query, array($studentid));
        echo '<p>DB 쿼리 성공. 결과: ' . $result . '</p>';
    } catch (Exception $e) {
        echo '<p style="color:red">DB 오류: ' . $e->getMessage() . '</p>';
    }
    
    // 간단한 데이터 가져오기
    echo '<h2>기본 데이터 확인</h2>';
    
    // 퀴즈 시도
    try {
        $timestart = time() - $tbegin;
        $quiz_count = $DB->count_records_sql("SELECT COUNT(*) FROM mdl_quiz_attempts 
                                      WHERE timemodified > ? AND userid = ?", 
                                      array($timestart, $studentid));
        echo '<p>퀴즈 시도 수: ' . $quiz_count . '</p>';
    } catch (Exception $e) {
        echo '<p style="color:red">퀴즈 데이터 오류: ' . $e->getMessage() . '</p>';
    }
    
    // 화이트보드
    try {
        $wb_count = $DB->count_records_sql("SELECT COUNT(*) FROM mdl_abessi_messages 
                                   WHERE userid = ? AND tlaststroke > ? AND contentstype = 2", 
                                   array($studentid, $timestart));
        echo '<p>화이트보드 항목 수: ' . $wb_count . '</p>';
    } catch (Exception $e) {
        echo '<p style="color:red">화이트보드 데이터 오류: ' . $e->getMessage() . '</p>';
    }
    
    echo '<h2>실행 환경 정보</h2>';
    echo '<p>PHP 버전: ' . phpversion() . '</p>';
    echo '<p>서버: ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
    echo '<p>임시 디렉토리: ' . sys_get_temp_dir() . '</p>';
    echo '<p>쓰기 권한: ' . (is_writable(sys_get_temp_dir()) ? '있음' : '없음') . '</p>';
    
} catch (Exception $e) {
    echo '<div style="color:red; padding:10px; border:1px solid red;">
    <h3>오류 발생</h3>
    <p>' . $e->getMessage() . '</p>
    <p>파일: ' . $e->getFile() . ', 라인: ' . $e->getLine() . '</p>
    </div>';
}

echo '</body>
</html>'; 