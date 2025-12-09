<?php
// upload_music.php - 배경음악 파일 업로드 처리
// Location: /mnt/c/1 Project/augmented_teacher/books/upload_music.php
// Error location: [FILE_PATH:LINE_NUMBER]

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // POST 데이터 확인
    if (!isset($_FILES['musicfile']) || !isset($_POST['contentsid'])) {
        throw new Exception('[upload_music.php:15] 필수 파라미터가 누락되었습니다.');
    }

    $contentsid = intval($_POST['contentsid']);
    $file = $_FILES['musicfile'];

    // 파일 업로드 에러 확인
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('[upload_music.php:23] 파일 업로드 에러: ' . $file['error']);
    }

    // 파일 타입 확인
    $allowed_types = ['audio/wav', 'audio/mpeg', 'audio/mp3'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('[upload_music.php:33] 허용되지 않는 파일 형식입니다. WAV 또는 MP3 파일만 가능합니다.');
    }

    // 파일 크기 확인 (최대 50MB)
    $max_size = 50 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('[upload_music.php:40] 파일 크기가 너무 큽니다. 최대 50MB까지 가능합니다.');
    }

    // 저장 디렉토리 설정
    $upload_dir = '/home/moodle/public_html/Contents/audiofiles/music/';
    $web_dir = 'https://mathking.kr/Contents/audiofiles/music/';

    // 디렉토리가 없으면 생성
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('[upload_music.php:51] 디렉토리 생성 실패: ' . $upload_dir);
        }
    }

    // 파일명 생성 (contentsid_timestamp.확장자)
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'music_' . $contentsid . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    $fileurl = $web_dir . $filename;

    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('[upload_music.php:64] 파일 저장 실패: ' . $filepath);
    }

    // 권한 설정
    chmod($filepath, 0644);

    // DB에 기록 (mdl_icontent_pages 테이블의 musicurl 필드 업데이트)
    $update_result = $DB->execute(
        "UPDATE mdl_icontent_pages SET musicurl = ? WHERE id = ?",
        [$fileurl, $contentsid]
    );

    if (!$update_result) {
        // DB 업데이트 실패 시 파일 삭제
        unlink($filepath);
        throw new Exception('[upload_music.php:79] DB 업데이트 실패');
    }

    // 성공 응답
    echo json_encode([
        'success' => true,
        'message' => '음악 파일이 성공적으로 업로드되었습니다.',
        'filename' => $filename,
        'fileurl' => $fileurl,
        'contentsid' => $contentsid
    ]);

} catch (Exception $e) {
    // 에러 응답
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
