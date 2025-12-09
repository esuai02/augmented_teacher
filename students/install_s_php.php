<?php
/**
 * s.php 파일 설치 스크립트
 * 파일: students/install_s_php.php
 * 
 * 이 스크립트는 /home/moodle/public_html/moodle/s.php 파일을 생성합니다.
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

$server_root = '/home/moodle/public_html/moodle';
$target_file = $server_root . '/s.php';
$source_file = __DIR__ . '/../s.php';

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>s.php 파일 설치</title>
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
        .success {
            background: #e8f5e9;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #2e7d32;
        }
        .error {
            background: #ffebee;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #c62828;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #1565c0;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
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
        <h1>s.php 파일 설치</h1>';

try {
    // 소스 파일 읽기
    if (file_exists($source_file)) {
        $file_content = file_get_contents($source_file);
    } else {
        // 소스 파일이 없으면 직접 생성
        $file_content = '<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = \'\';
if (isset($_GET[\'h\']) && !empty($_GET[\'h\'])) {
    $hash = trim($_GET[\'h\']);
} else if (isset($_GET[\'hash\']) && !empty($_GET[\'hash\'])) {
    $hash = trim($_GET[\'hash\']);
} else if (isset($_GET[\'id\']) && !empty($_GET[\'id\'])) {
    $hash = trim($_GET[\'id\']);
}
if (empty($hash)) {
    http_response_code(400);
    echo \'해시 파라미터가 없습니다. 사용법: s.php?h=해시\';
    exit;
}
$record = $DB->get_record_sql("SELECT original_url FROM mdl_short_urls WHERE hash = ?", array($hash));
if ($record) {
    $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE hash = ?", array($hash));
    header(\'Location: \' . $record->original_url);
    exit;
}
http_response_code(404);
echo \'단축 URL을 찾을 수 없습니다.\';
?>';
    }
    
    // 파일 생성
    if (file_put_contents($target_file, $file_content)) {
        chmod($target_file, 0644);
        echo '<div class="success">
            <strong>✓ 설치 완료!</strong><br>
            파일이 성공적으로 생성되었습니다.<br>
            설치 위치: <code>' . htmlspecialchars($target_file) . '</code><br><br>
            이제 <code>https://mathking.kr/moodle/s.php?h=mM9G</code> 형식이 작동합니다!
        </div>';
        
        echo '<div class="info">
            <strong>사용법:</strong><br>
            <code>https://mathking.kr/moodle/s.php?h=g5xy</code><br>
            <code>https://mathking.kr/moodle/s.php?h=mM9G</code><br><br>
            <strong>테스트:</strong><br>';
        
        // 데이터베이스에서 해시 가져와서 테스트 링크 생성
        $test_urls = $DB->get_records_sql("SELECT hash FROM mdl_short_urls ORDER BY id DESC LIMIT 5");
        foreach ($test_urls as $url) {
            echo '<a href="https://mathking.kr/moodle/s.php?h=' . htmlspecialchars($url->hash) . '" target="_blank">https://mathking.kr/moodle/s.php?h=' . htmlspecialchars($url->hash) . '</a><br>';
        }
        
        echo '</div>';
        
    } else {
        throw new Exception('파일 쓰기에 실패했습니다. 권한을 확인하세요.');
    }
    
    // 파일 정보
    if (file_exists($target_file)) {
        $file_size = filesize($target_file);
        $file_perms = substr(sprintf("%o", fileperms($target_file)), -4);
        
        echo '<div class="info">
            <strong>파일 정보:</strong><br>
            크기: ' . number_format($file_size) . ' bytes<br>
            권한: ' . htmlspecialchars($file_perms) . '<br>
            존재 여부: ✓ 파일이 존재합니다
        </div>';
    }
    
} catch (Exception $e) {
    echo '<div class="error">
        <strong>오류 발생:</strong><br>
        ' . htmlspecialchars($e->getMessage()) . '<br><br>
        <strong>수동 설치 방법:</strong><br>
        1. <code>s.php</code> 파일을 서버에 업로드<br>
        2. 위치: <code>/home/moodle/public_html/moodle/s.php</code>
    </div>';
}

echo '</div>
</body>
</html>';
?>

