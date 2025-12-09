<?php
/**
 * Image Proxy for Clipboard Copy
 * CORS 문제를 해결하기 위한 이미지 프록시
 *
 * File: /mnt/c/1 Project/augmented_teacher/books/image_proxy.php
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$imageUrl = $_GET['url'] ?? '';

if (empty($imageUrl)) {
    http_response_code(400);
    echo json_encode(['error' => 'No image URL provided', 'file' => __FILE__, 'line' => __LINE__]);
    exit();
}

// URL 디코딩
$imageUrl = urldecode($imageUrl);

// mathking.kr 도메인이 아니면 차단
if (strpos($imageUrl, 'mathking.kr') === false && strpos($imageUrl, '/Contents/') === false) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid domain', 'file' => __FILE__, 'line' => __LINE__]);
    exit();
}

// 상대 경로를 절대 경로로 변환
if (strpos($imageUrl, 'http') !== 0) {
    $imageUrl = 'https://mathking.kr' . $imageUrl;
}

try {
    // cURL로 이미지 가져오기
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $imageUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $imageData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('HTTP Error: ' . $httpCode);
    }

    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }

    if (empty($imageData)) {
        throw new Exception('Empty image data');
    }

    // Content-Type 설정
    if (strpos($contentType, 'image/') === 0) {
        header('Content-Type: ' . $contentType);
    } else {
        // 확장자로 Content-Type 추정
        $ext = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml'
        ];

        if (isset($mimeTypes[$ext])) {
            header('Content-Type: ' . $mimeTypes[$ext]);
        } else {
            header('Content-Type: image/jpeg'); // 기본값
        }
    }

    // 캐시 헤더
    header('Cache-Control: public, max-age=86400'); // 1일
    header('Content-Length: ' . strlen($imageData));

    // 이미지 데이터 출력
    echo $imageData;

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage(),
        'url' => $imageUrl,
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
