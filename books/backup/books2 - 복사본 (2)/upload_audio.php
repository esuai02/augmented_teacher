<?php
// File: upload_audio.php
// Purpose: Handle audio file upload (WAV/MP3) and save to Contents/audiofiles/music

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // Check if file was uploaded
    if (!isset($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('[upload_audio.php:' . __LINE__ . '] 파일 업로드 오류: ' . ($_FILES['audio_file']['error'] ?? 'No file uploaded'));
    }

    $file = $_FILES['audio_file'];
    $thispageid = isset($_POST['thispageid']) ? intval($_POST['thispageid']) : 0;
    $studentid = isset($_POST['studentid']) ? intval($_POST['studentid']) : 0;

    if ($thispageid === 0 || $studentid === 0) {
        throw new Exception('[upload_audio.php:' . __LINE__ . '] 필수 파라미터 누락 (thispageid: ' . $thispageid . ', studentid: ' . $studentid . ')');
    }

    // Validate file type
    $allowed_types = ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['mp3', 'wav'];

    if (!in_array($file_extension, $allowed_extensions) && !in_array($file['type'], $allowed_types)) {
        throw new Exception('[upload_audio.php:' . __LINE__ . '] 허용되지 않는 파일 형식: ' . $file_extension . ' (type: ' . $file['type'] . ')');
    }

    // Validate file size (max 50MB)
    $max_size = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $max_size) {
        throw new Exception('[upload_audio.php:' . __LINE__ . '] 파일 크기 초과: ' . round($file['size']/1024/1024, 2) . 'MB (최대 50MB)');
    }

    // Create directory if not exists
    $upload_dir = '/home/moodle/public_html/Contents/audiofiles/music';
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            throw new Exception('[upload_audio.php:' . __LINE__ . '] 업로드 디렉토리 생성 실패: ' . $upload_dir);
        }
    }

    // Generate unique filename
    $timestamp = time();
    $safe_filename = 'audio_' . $thispageid . '_' . $studentid . '_' . $timestamp . '.' . $file_extension;
    $target_path = $upload_dir . '/' . $safe_filename;

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        $last_error = error_get_last();
        throw new Exception('[upload_audio.php:' . __LINE__ . '] 파일 저장 실패: ' . ($last_error['message'] ?? 'Unknown error'));
    }

    // Set proper permissions
    chmod($target_path, 0644);

    // Generate web-accessible URL
    $file_url = 'https://mathking.kr/Contents/audiofiles/music/' . $safe_filename;

    // Update mdl_icontent_pages.musicurl field
    try {
        $DB->execute("UPDATE {icontent_pages} SET musicurl = ? WHERE id = ?", [$file_url, $thispageid]);
        error_log('[upload_audio.php:' . __LINE__ . '] Updated musicurl for thispageid: ' . $thispageid . ', URL: ' . $file_url);

        // Verify the update
        $verify = $DB->get_record('icontent_pages', ['id' => $thispageid], 'id, musicurl');
        if ($verify && $verify->musicurl === $file_url) {
            error_log('[upload_audio.php:' . __LINE__ . '] Verification successful: musicurl saved correctly');
        } else {
            error_log('[upload_audio.php:' . __LINE__ . '] Verification failed: musicurl not saved correctly');
            throw new Exception('[upload_audio.php:' . __LINE__ . '] DB update verification failed');
        }
    } catch (Exception $db_error) {
        error_log('[upload_audio.php:' . __LINE__ . '] Failed to update musicurl: ' . $db_error->getMessage());
        throw new Exception('[upload_audio.php:' . __LINE__ . '] DB update failed: ' . $db_error->getMessage());
    }

    // Optional: Also save to abessi_audio_files for tracking
    try {
        $table_exists = $DB->get_manager()->table_exists('abessi_audio_files');
        if ($table_exists) {
            $record = new stdClass();
            $record->contentsid = $thispageid;
            $record->studentid = $studentid;
            $record->filename = $safe_filename;
            $record->filepath = $file_url;
            $record->filesize = $file['size'];
            $record->filetype = $file_extension;
            $record->uploadedby = $USER->id;
            $record->timecreated = time();

            $DB->insert_record('abessi_audio_files', $record);
        }
    } catch (Exception $db_error) {
        error_log('[upload_audio.php:' . __LINE__ . '] Backup table insert failed: ' . $db_error->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => '파일이 성공적으로 업로드되었습니다.',
        'data' => [
            'thispageid' => $thispageid,
            'filename' => $safe_filename,
            'url' => $file_url,
            'size' => $file['size'],
            'type' => $file_extension,
            'db_updated' => true
        ]
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}

/**
 * Database Table Schema (for reference):
 * Run create_audio_table.php to create this table
 *
 * CREATE TABLE IF NOT EXISTS `mdl_abessi_audio_files` (
 *   `id` bigint(10) NOT NULL AUTO_INCREMENT,
 *   `contentsid` bigint(10) NOT NULL,
 *   `studentid` bigint(10) NOT NULL,
 *   `filename` varchar(255) NOT NULL,
 *   `filepath` varchar(500) NOT NULL,
 *   `filesize` bigint(10) NOT NULL,
 *   `filetype` varchar(10) NOT NULL,
 *   `uploadedby` bigint(10) NOT NULL,
 *   `timecreated` bigint(10) NOT NULL,
 *   PRIMARY KEY (`id`),
 *   KEY `idx_contentsid` (`contentsid`),
 *   KEY `idx_studentid` (`studentid`)
 * ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */
?>