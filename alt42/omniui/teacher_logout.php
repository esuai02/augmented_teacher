<?php
// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 모든 세션 변수 제거
$_SESSION = array();

// 세션 쿠키 삭제
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 세션 완전히 파괴
session_destroy();

// 로그인 페이지로 리다이렉트
header("Location: teacher_login.php");
exit;
?>