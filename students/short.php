<?php
/**
 * 단축 URL 리다이렉트 처리
 * 파일: students/short.php
 * 에러 출력 위치: short.php
 * 
 * 사용법: https://mathking.kr/short.php?id=g5xy
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

try {
    // 해시 파라미터 확인 (h, hash 또는 id 파라미터로 받기)
    $hash = '';
    if (isset($_GET['h']) && !empty($_GET['h'])) {
        $hash = trim($_GET['h']);
    } else if (isset($_GET['hash']) && !empty($_GET['hash'])) {
        $hash = trim($_GET['hash']);
    } else if (isset($_GET['id']) && !empty($_GET['id'])) {
        $hash = trim($_GET['id']);
    }
    
    if (empty($hash)) {
        throw new Exception('해시 파라미터가 없습니다. 사용법: short.php?h=해시 [short.php:22]');
    }
    
    // 해시 길이 검증 (4자리 또는 16자리 허용)
    $hash_length = strlen($hash);
    if ($hash_length !== 4 && $hash_length !== 16) {
        throw new Exception('잘못된 해시 형식입니다. (4자리 또는 16자리 필요) [short.php:27]');
    }
    
    // 데이터베이스에서 원본 URL 조회
    $record = $DB->get_record_sql("
        SELECT id, original_url, expired_at 
        FROM mdl_short_urls 
        WHERE hash = ?
    ", array($hash));
    
    if (!$record) {
        throw new Exception('단축 URL을 찾을 수 없습니다. [short.php:44]');
    }
    
    // 만료일 확인
    if ($record->expired_at !== null && strtotime($record->expired_at) < time()) {
        throw new Exception('만료된 단축 URL입니다. [short.php:49]');
    }
    
    // 클릭 수 증가
    $DB->execute("
        UPDATE mdl_short_urls 
        SET click_count = click_count + 1 
        WHERE id = ?
    ", array($record->id));
    
    // 원본 URL로 리다이렉트
    header('Location: ' . $record->original_url, true, 302);
    exit;
    
} catch (Exception $e) {
    // 에러 발생 시 에러 페이지 표시
    http_response_code(404);
    echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단축 URL 오류</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-color: #f5f5f5;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #d32f2f;
            margin-bottom: 1rem;
        }
        p {
            color: #666;
            margin-bottom: 1rem;
        }
        a {
            color: #1976d2;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>단축 URL 오류</h1>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
        <p><a href="https://mathking.kr/moodle">홈으로 돌아가기</a></p>
    </div>
</body>
</html>';
    exit;
}
?>

