<?php
// Moodle 환경 설정 포함
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 표시 설정 (개발 단계에서만 사용)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data AS role FROM mdl_user_info_data WHERE userid = ? AND fieldid = ?", array($USER->id, 22));
$role = $userrole->role;

if ($role === 'student') {
    echo json_encode(array('success' => false, 'message' => '권한이 없습니다.'));
    exit;
}

// 파일 업로드 처리
if (isset($_FILES['audio_files']) && isset($_POST['game_id'])) {
    $gameId = intval($_POST['game_id']);

    // 게임별 폴더 생성
    $uploadDir = 'Gamefiles/game_' . $gameId . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $successUploads = array();
    $failedUploads = array();

    // 여러 파일 처리
    for ($i = 0; $i < count($_FILES['audio_files']['name']); $i++) {
        $fileName = basename($_FILES['audio_files']['name'][$i]);
        $targetFilePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['audio_files']['tmp_name'][$i], $targetFilePath)) {
            $successUploads[] = $targetFilePath;
        } else {
            $failedUploads[] = $_FILES['audio_files']['name'][$i];
        }
    }

    // 데이터베이스에 파일 경로 업데이트
    $gameRecord = $DB->get_record('games_info', array('id' => $gameId));
    if ($gameRecord) {
        // 기존에 저장된 파일 목록에 새로운 파일들을 추가
        $existingFiles = !empty($gameRecord->audio_files) ? json_decode($gameRecord->audio_files, true) : array();
        $updatedFiles = array_merge($existingFiles, $successUploads);
        $gameRecord->audio_files = json_encode($updatedFiles);
        $DB->update_record('games_info', $gameRecord);

        // 성공 응답
        $message = '업로드 성공: ' . count($successUploads) . '개 파일';
        if (!empty($failedUploads)) {
            $message .= ', 업로드 실패: ' . implode(', ', $failedUploads);
        }
        echo json_encode(array('success' => true, 'message' => $message));
    } else {
        echo json_encode(array('success' => false, 'message' => '게임을 찾을 수 없습니다.'));
    }
} else {
    echo json_encode(array('success' => false, 'message' => '잘못된 요청입니다.'));
}
?>
