<?php
/**
 * Content Validator for Student Manual System
 * File: alt42/orchestration/agents/studentmanual/includes/content_validator.php
 *
 * 파일 타입, 크기 검증 유틸리티
 */

class ContentValidator {
    // 허용된 이미지 파일 형식
    const ALLOWED_IMAGE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    const MAX_IMAGE_SIZE = 10 * 1024 * 1024; // 10MB

    // 허용된 동영상 파일 형식
    const ALLOWED_VIDEO_TYPES = ['mp4', 'webm', 'ogg'];
    const MAX_VIDEO_SIZE = 100 * 1024 * 1024; // 100MB

    // 허용된 음성 파일 형식
    const ALLOWED_AUDIO_TYPES = ['mp3', 'wav', 'ogg', 'm4a', 'flac'];
    const MAX_AUDIO_SIZE = 100 * 1024 * 1024; // 100MB

    /**
     * 파일 업로드 검증
     *
     * @param array $file $_FILES 배열의 파일 정보
     * @param string $contentType 컨텐츠 타입 (image, video, audio)
     * @return array ['valid' => bool, 'error' => string, 'file' => string, 'line' => int]
     */
    public static function validateUpload($file, $contentType) {
        $filePath = __FILE__;
        $line = __LINE__;

        // 파일이 업로드되었는지 확인
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = self::getUploadErrorMessage($file['error'] ?? UPLOAD_ERR_NO_FILE);
            return [
                'valid' => false,
                'error' => $errorMsg,
                'file' => $filePath,
                'line' => $line
            ];
        }

        // 파일명에서 확장자 추출
        $fileName = $file['name'];
        $fileSize = $file['size'];
        $tmpName = $file['tmp_name'];

        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // 컨텐츠 타입별 검증
        switch ($contentType) {
            case 'image':
                if (!in_array($ext, self::ALLOWED_IMAGE_TYPES)) {
                    return [
                        'valid' => false,
                        'error' => "허용되지 않는 이미지 파일 형식입니다. 허용 형식: " . implode(', ', self::ALLOWED_IMAGE_TYPES),
                        'file' => $filePath,
                        'line' => __LINE__
                    ];
                }
                if ($fileSize > self::MAX_IMAGE_SIZE) {
                    return [
                        'valid' => false,
                        'error' => "이미지 파일 크기는 최대 " . (self::MAX_IMAGE_SIZE / 1024 / 1024) . "MB입니다.",
                        'file' => $filePath,
                        'line' => __LINE__
                    ];
                }
                break;

            case 'video':
                if (!in_array($ext, self::ALLOWED_VIDEO_TYPES)) {
                    return [
                        'valid' => false,
                        'error' => "허용되지 않는 동영상 파일 형식입니다. 허용 형식: " . implode(', ', self::ALLOWED_VIDEO_TYPES),
                        'file' => $filePath,
                        'line' => __LINE__
                    ];
                }
                if ($fileSize > self::MAX_VIDEO_SIZE) {
                    return [
                        'valid' => false,
                        'error' => "동영상 파일 크기는 최대 " . (self::MAX_VIDEO_SIZE / 1024 / 1024) . "MB입니다.",
                        'file' => $filePath,
                        'line' => __LINE__
                    ];
                }
                break;

            case 'audio':
                if (!in_array($ext, self::ALLOWED_AUDIO_TYPES)) {
                    return [
                        'valid' => false,
                        'error' => "허용되지 않는 음성 파일 형식입니다. 허용 형식: " . implode(', ', self::ALLOWED_AUDIO_TYPES),
                        'file' => $filePath,
                        'line' => __LINE__
                    ];
                }
                if ($fileSize > self::MAX_AUDIO_SIZE) {
                    return [
                        'valid' => false,
                        'error' => "음성 파일 크기는 최대 " . (self::MAX_AUDIO_SIZE / 1024 / 1024) . "MB입니다.",
                        'file' => $filePath,
                        'line' => __LINE__
                    ];
                }
                break;

            default:
                return [
                    'valid' => false,
                    'error' => "지원하지 않는 컨텐츠 타입입니다: {$contentType}",
                    'file' => $filePath,
                    'line' => __LINE__
                ];
        }

        // MIME 타입 확인
        $mimeType = mime_content_type($tmpName);
        if (!$mimeType) {
            $mimeType = $file['type'] ?? 'application/octet-stream';
        }

        return [
            'valid' => true,
            'extension' => $ext,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'file' => $filePath,
            'line' => __LINE__
        ];
    }

    /**
     * 외부 URL 검증
     *
     * @param string $url 외부 URL
     * @param string $contentType 컨텐츠 타입 (video, link)
     * @return array ['valid' => bool, 'error' => string, 'file' => string, 'line' => int]
     */
    public static function validateExternalUrl($url, $contentType) {
        $filePath = __FILE__;
        $line = __LINE__;

        if (empty($url)) {
            return [
                'valid' => false,
                'error' => "URL이 비어있습니다.",
                'file' => $filePath,
                'line' => $line
            ];
        }

        // URL 형식 검증
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return [
                'valid' => false,
                'error' => "유효하지 않은 URL 형식입니다.",
                'file' => $filePath,
                'line' => $line
            ];
        }

        // 동영상의 경우 YouTube 또는 Vimeo 링크인지 확인 (선택사항)
        if ($contentType === 'video') {
            $host = parse_url($url, PHP_URL_HOST);
            $allowedHosts = ['youtube.com', 'www.youtube.com', 'youtu.be', 'vimeo.com', 'www.vimeo.com'];
            $isAllowed = false;
            foreach ($allowedHosts as $allowedHost) {
                if (strpos($host, $allowedHost) !== false) {
                    $isAllowed = true;
                    break;
                }
            }
            // 외부 링크는 허용하되, 경고만 표시
        }

        return [
            'valid' => true,
            'url' => $url,
            'file' => $filePath,
            'line' => $line
        ];
    }

    /**
     * 업로드 에러 메시지 가져오기
     *
     * @param int $errorCode PHP 업로드 에러 코드
     * @return string 에러 메시지
     */
    private static function getUploadErrorMessage($errorCode) {
        $filePath = __FILE__;
        $line = __LINE__;

        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return "파일 크기가 너무 큽니다. (파일: {$filePath}, 라인: {$line})";
            case UPLOAD_ERR_PARTIAL:
                return "파일이 부분적으로만 업로드되었습니다. (파일: {$filePath}, 라인: {$line})";
            case UPLOAD_ERR_NO_FILE:
                return "파일이 업로드되지 않았습니다. (파일: {$filePath}, 라인: {$line})";
            case UPLOAD_ERR_NO_TMP_DIR:
                return "임시 폴더를 찾을 수 없습니다. (파일: {$filePath}, 라인: {$line})";
            case UPLOAD_ERR_CANT_WRITE:
                return "파일을 디스크에 쓸 수 없습니다. (파일: {$filePath}, 라인: {$line})";
            case UPLOAD_ERR_EXTENSION:
                return "파일 업로드가 확장에 의해 중지되었습니다. (파일: {$filePath}, 라인: {$line})";
            default:
                return "알 수 없는 업로드 오류가 발생했습니다. (파일: {$filePath}, 라인: {$line})";
        }
    }

    /**
     * 파일명 생성 (타임스탬프 기반)
     *
     * @param string $originalName 원본 파일명
     * @return string 새로운 파일명
     */
    public static function generateFileName($originalName) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return "{$timestamp}_{$random}.{$ext}";
    }
}

