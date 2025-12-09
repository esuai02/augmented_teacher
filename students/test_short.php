<?php
/**
 * 단축 URL 테스트 페이지
 * 파일: students/test_short.php
 * 에러 출력 위치: test_short.php
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

// 테스트할 해시
$test_hash = isset($_GET['h']) ? $_GET['h'] : 'mM9G';

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단축 URL 테스트</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1976d2;
            margin-bottom: 20px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
        }
        .success {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
        }
        .error {
            background: #ffebee;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
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
    <div class="container">
        <h1>단축 URL 테스트</h1>';

// 서버 정보 출력
echo '<div class="info">
    <strong>서버 정보:</strong><br>
    REQUEST_URI: ' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . '<br>
    SCRIPT_NAME: ' . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') . '<br>
    QUERY_STRING: ' . htmlspecialchars($_SERVER['QUERY_STRING'] ?? 'N/A') . '<br>
    HTTP_HOST: ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . '<br>
</div>';

// 데이터베이스에서 해시 확인
try {
    $record = $DB->get_record_sql("
        SELECT id, hash, original_url, created_at, expired_at, click_count
        FROM mdl_short_urls 
        WHERE hash = ?
    ", array($test_hash));
    
    if ($record) {
        echo '<div class="success">
            <strong>✓ 데이터베이스에서 찾음:</strong><br>
            해시: ' . htmlspecialchars($record->hash) . '<br>
            원본 URL: <a href="' . htmlspecialchars($record->original_url) . '" target="_blank">' . htmlspecialchars($record->original_url) . '</a><br>
            생성일: ' . htmlspecialchars($record->created_at) . '<br>
            만료일: ' . ($record->expired_at ? htmlspecialchars($record->expired_at) : '없음') . '<br>
            클릭 수: ' . htmlspecialchars($record->click_count) . '<br>
        </div>';
        
        echo '<div class="info">
            <strong>테스트 링크:</strong><br>
            <a href="short.php?h=' . htmlspecialchars($test_hash) . '">short.php?h=' . htmlspecialchars($test_hash) . '</a><br>
            <a href="https://mathking.kr/' . htmlspecialchars($test_hash) . '">https://mathking.kr/' . htmlspecialchars($test_hash) . '</a>
        </div>';
    } else {
        echo '<div class="error">
            <strong>✗ 데이터베이스에서 찾을 수 없음:</strong><br>
            해시 "' . htmlspecialchars($test_hash) . '"가 데이터베이스에 존재하지 않습니다.
        </div>';
    }
    
    // 전체 단축 URL 목록 (최근 10개)
    $all_urls = $DB->get_records_sql("
        SELECT hash, original_url, created_at, click_count
        FROM mdl_short_urls 
        ORDER BY id DESC 
        LIMIT 10
    ");
    
    if ($all_urls) {
        echo '<div class="info">
            <strong>최근 단축 URL 목록 (최근 10개):</strong><br>
            <table border="1" cellpadding="5" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <tr>
                    <th>해시</th>
                    <th>원본 URL</th>
                    <th>생성일</th>
                    <th>클릭 수</th>
                </tr>';
        foreach ($all_urls as $url) {
            echo '<tr>
                <td><a href="short.php?h=' . htmlspecialchars($url->hash) . '">' . htmlspecialchars($url->hash) . '</a></td>
                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">' . htmlspecialchars(substr($url->original_url, 0, 50)) . '...</td>
                <td>' . htmlspecialchars($url->created_at) . '</td>
                <td>' . htmlspecialchars($url->click_count) . '</td>
            </tr>';
        }
        echo '</table>
        </div>';
    }
    
} catch (Exception $e) {
    echo '<div class="error">
        <strong>오류:</strong><br>
        ' . htmlspecialchars($e->getMessage()) . '
    </div>';
}

echo '</div>
</body>
</html>';
?>

