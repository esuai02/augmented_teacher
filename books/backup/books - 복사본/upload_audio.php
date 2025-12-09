<?php
// upload_audio.php

// 사용자 인증 및 보안 검증 필요
require_once("/home/moodle/public_html/moodle/config.php");
require_login();

// 파일 업로드 처리
if ($_FILES['audio']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '/path/to/upload/directory/'; // 실제 업로드 경로로 변경하세요.
    $fileName = basename($_FILES['audio']['name']);
    $uploadFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['audio']['tmp_name'], $uploadFile)) {
        $audioUrl = '/url/to/access/' . $fileName; // 실제 접근 가능한 URL로 변경하세요.
        echo json_encode(['audioUrl' => $audioUrl]);
    } else {
        echo json_encode(['error' => '파일 업로드 실패']);
    }
} else {
    echo json_encode(['error' => '파일 업로드 오류']);
}
?>