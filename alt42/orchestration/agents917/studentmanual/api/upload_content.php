<?php
/**
 * Student Manual System - Content Upload API
 * File: alt42/orchestration/agents/studentmanual/api/upload_content.php
 *
 * 컨텐츠 업로드 API (이미지, 동영상, 음성, 외부 링크)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Include error handler and validator
require_once(__DIR__ . '/../includes/error_handler.php');
require_once(__DIR__ . '/../includes/content_validator.php');

header('Content-Type: application/json; charset=utf-8');

try {
    // 사용자 역할 확인 (교사만 업로드 가능)
    $userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
    $role = $userrole->data ?? 'student';

    if (!in_array($role, ['teacher', 'admin'])) {
        echo StudentManualErrorHandler::jsonError(
            "권한이 없습니다. 교사만 컨텐츠를 업로드할 수 있습니다.",
            403,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 요청 메서드 확인
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method !== 'POST') {
        echo StudentManualErrorHandler::jsonError(
            "POST 메서드만 지원합니다.",
            405,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 업로드 디렉토리 경로
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            echo StudentManualErrorHandler::jsonError(
                "업로드 디렉토리를 생성할 수 없습니다.",
                500,
                ['file' => __FILE__, 'line' => __LINE__, 'path' => $uploadDir]
            );
            exit;
        }
    }

    // 컨텐츠 타입 확인
    $contentType = isset($_POST['content_type']) ? trim($_POST['content_type']) : '';
    if (!in_array($contentType, ['image', 'video', 'audio', 'link'])) {
        echo StudentManualErrorHandler::jsonError(
            "유효하지 않은 컨텐츠 타입입니다. (image, video, audio, link 중 하나여야 합니다)",
            400,
            ['file' => __FILE__, 'line' => __LINE__, 'content_type' => $contentType]
        );
        exit;
    }

    // 외부 링크 처리
    if ($contentType === 'link') {
        $externalUrl = isset($_POST['external_url']) ? trim($_POST['external_url']) : '';
        
        $validation = ContentValidator::validateExternalUrl($externalUrl, 'link');
        if (!$validation['valid']) {
            echo StudentManualErrorHandler::jsonError(
                $validation['error'],
                400,
                ['file' => $validation['file'], 'line' => $validation['line']]
            );
            exit;
        }

        // 데이터베이스에 저장
        $contentData = new stdClass();
        $contentData->content_type = 'link';
        $contentData->external_url = $externalUrl;
        $contentData->file_path = null;
        $contentData->file_size = null;
        $contentData->mime_type = null;
        $contentData->created_at = time();
        $contentData->created_by = $USER->id;

        $contentId = StudentManualErrorHandler::safeInsertRecord($DB, 'at42_studentmanual_contents', $contentData);
        
        if (!$contentId) {
            echo StudentManualErrorHandler::jsonError(
                "데이터베이스에 저장하는 중 오류가 발생했습니다.",
                500,
                ['file' => __FILE__, 'line' => __LINE__]
            );
            exit;
        }

        echo json_encode([
            'success' => true,
            'content_id' => $contentId,
            'content_type' => 'link',
            'external_url' => $externalUrl,
            'message' => '외부 링크가 성공적으로 저장되었습니다.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 파일 업로드 처리
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        echo StudentManualErrorHandler::jsonError(
            "파일이 업로드되지 않았습니다.",
            400,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    $file = $_FILES['file'];

    // 파일 검증
    $validation = ContentValidator::validateUpload($file, $contentType);
    if (!$validation['valid']) {
        echo StudentManualErrorHandler::jsonError(
            $validation['error'],
            400,
            ['file' => $validation['file'], 'line' => $validation['line']]
        );
        exit;
    }

    // 파일명 생성
    $newFileName = ContentValidator::generateFileName($file['name']);
    $targetPath = $uploadDir . $newFileName;

    // 파일 이동
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo StudentManualErrorHandler::jsonError(
            "파일을 업로드하는 중 오류가 발생했습니다.",
            500,
            ['file' => __FILE__, 'line' => __LINE__, 'target_path' => $targetPath]
        );
        exit;
    }

    // 데이터베이스에 저장
    $contentData = new stdClass();
    $contentData->content_type = $contentType;
    $contentData->file_path = $newFileName;
    $contentData->external_url = null;
    $contentData->file_size = $validation['file_size'];
    $contentData->mime_type = $validation['mime_type'];
    $contentData->created_at = time();
    $contentData->created_by = $USER->id;

    $contentId = StudentManualErrorHandler::safeInsertRecord($DB, 'at42_studentmanual_contents', $contentData);
    
    if (!$contentId) {
        // 파일 삭제
        @unlink($targetPath);
        echo StudentManualErrorHandler::jsonError(
            "데이터베이스에 저장하는 중 오류가 발생했습니다.",
            500,
            ['file' => __FILE__, 'line' => __LINE__]
        );
        exit;
    }

    // 업로드된 파일의 URL 생성
    $baseUrl = 'https://' . $_SERVER['HTTP_HOST'] . '/moodle/local/augmented_teacher/alt42/orchestration/agents/studentmanual/uploads/';
    $fileUrl = $baseUrl . $newFileName;

    echo json_encode([
        'success' => true,
        'content_id' => $contentId,
        'content_type' => $contentType,
        'file_name' => $newFileName,
        'file_url' => $fileUrl,
        'file_size' => $validation['file_size'],
        'mime_type' => $validation['mime_type'],
        'message' => '파일이 성공적으로 업로드되었습니다.'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $file = __FILE__;
    $line = $e->getLine();
    echo StudentManualErrorHandler::jsonError(
        "업로드 중 오류가 발생했습니다: " . $e->getMessage(),
        500,
        ['file' => $file, 'line' => $line]
    );
}

