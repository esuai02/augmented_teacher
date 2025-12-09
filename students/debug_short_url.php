<?php
/**
 * 단축 URL 디버깅 페이지
 * 파일: students/debug_short_url.php
 * 에러 출력 위치: debug_short_url.php
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>단축 URL 디버깅</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #1976d2;
            margin-bottom: 20px;
        }
        h2 {
            color: #424242;
            margin-top: 30px;
            margin-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            font-family: monospace;
            font-size: 12px;
            white-space: pre-wrap;
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
        .warning {
            background: #fff3e0;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        code {
            background: #f5f5f5;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>단축 URL 디버깅 도구</h1>';

// 1. 서버 환경 정보
echo '<h2>1. 서버 환경 정보</h2>';
echo '<div class="info">';
echo 'REQUEST_URI: ' . htmlspecialchars($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo 'SCRIPT_NAME: ' . htmlspecialchars($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo 'QUERY_STRING: ' . htmlspecialchars($_SERVER['QUERY_STRING'] ?? 'N/A') . "\n";
echo 'HTTP_HOST: ' . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo 'SERVER_NAME: ' . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'N/A') . "\n";
echo 'DOCUMENT_ROOT: ' . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo 'SCRIPT_FILENAME: ' . htmlspecialchars($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo '</div>';

// 2. .htaccess 파일 확인
echo '<h2>2. .htaccess 파일 확인</h2>';
$moodle_root = '/home/moodle/public_html/moodle';
$htaccess_path = $moodle_root . '/.htaccess';

if (file_exists($htaccess_path)) {
    echo '<div class="success">✓ .htaccess 파일이 존재합니다: ' . htmlspecialchars($htaccess_path) . '</div>';
    
    $htaccess_content = file_get_contents($htaccess_path);
    $has_short_url_rule = strpos($htaccess_content, '단축 URL 리라이트 규칙') !== false;
    
    if ($has_short_url_rule) {
        echo '<div class="success">✓ 단축 URL 리라이트 규칙이 .htaccess에 있습니다.</div>';
    } else {
        echo '<div class="error">✗ 단축 URL 리라이트 규칙이 .htaccess에 없습니다.</div>';
    }
    
    // .htaccess 내용의 마지막 부분 표시
    $lines = explode("\n", $htaccess_content);
    $last_lines = array_slice($lines, -30);
    echo '<div class="info"><strong>.htaccess 파일 내용 (마지막 30줄):</strong>' . "\n" . htmlspecialchars(implode("\n", $last_lines)) . '</div>';
    
    // 파일 권한 확인
    $perms = substr(sprintf("%o", fileperms($htaccess_path)), -4);
    echo '<div class="info">파일 권한: ' . htmlspecialchars($perms) . '</div>';
} else {
    echo '<div class="error">✗ .htaccess 파일이 없습니다: ' . htmlspecialchars($htaccess_path) . '</div>';
}

// 3. mod_rewrite 확인
echo '<h2>3. Apache mod_rewrite 모듈 확인</h2>';
if (function_exists("apache_get_modules")) {
    $modules = apache_get_modules();
    if (in_array("mod_rewrite", $modules)) {
        echo '<div class="success">✓ mod_rewrite 모듈이 활성화되어 있습니다.</div>';
    } else {
        echo '<div class="error">✗ mod_rewrite 모듈이 활성화되어 있지 않습니다.</div>';
    }
} else {
    echo '<div class="warning">⚠ apache_get_modules() 함수를 사용할 수 없습니다. mod_rewrite 상태를 확인할 수 없습니다.</div>';
}

// 4. 데이터베이스 확인
echo '<h2>4. 데이터베이스 단축 URL 확인</h2>';
try {
    $test_hash = isset($_GET['h']) ? $_GET['h'] : 'mM9G';
    
    $record = $DB->get_record_sql("
        SELECT id, hash, original_url, created_at, expired_at, click_count
        FROM mdl_short_urls 
        WHERE hash = ?
    ", array($test_hash));
    
    if ($record) {
        echo '<div class="success">✓ 해시 "' . htmlspecialchars($test_hash) . '"가 데이터베이스에 있습니다.</div>';
        echo '<table>';
        echo '<tr><th>항목</th><th>값</th></tr>';
        echo '<tr><td>해시</td><td>' . htmlspecialchars($record->hash) . '</td></tr>';
        echo '<tr><td>원본 URL</td><td><a href="' . htmlspecialchars($record->original_url) . '" target="_blank">' . htmlspecialchars($record->original_url) . '</a></td></tr>';
        echo '<tr><td>생성일</td><td>' . htmlspecialchars($record->created_at) . '</td></tr>';
        echo '<tr><td>만료일</td><td>' . ($record->expired_at ? htmlspecialchars($record->expired_at) : '없음') . '</td></tr>';
        echo '<tr><td>클릭 수</td><td>' . htmlspecialchars($record->click_count) . '</td></tr>';
        echo '</table>';
    } else {
        echo '<div class="error">✗ 해시 "' . htmlspecialchars($test_hash) . '"가 데이터베이스에 없습니다.</div>';
    }
    
    // 전체 단축 URL 개수
    $total_count = $DB->count_records('short_urls');
    echo '<div class="info">데이터베이스에 총 ' . $total_count . '개의 단축 URL이 있습니다.</div>';
    
} catch (Exception $e) {
    echo '<div class="error">데이터베이스 오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// 5. 직접 테스트 링크
echo '<h2>5. 테스트 링크</h2>';
echo '<div class="info">';
echo '<strong>다음 링크들을 테스트해보세요:</strong><br><br>';
echo '1. 직접 short.php 호출:<br>';
echo '   <a href="short.php?h=mM9G" target="_blank">short.php?h=mM9G</a><br><br>';
echo '2. 서버 루트 단축 URL (리라이트 필요):<br>';
echo '   <a href="https://mathking.kr/mM9G" target="_blank">https://mathking.kr/mM9G</a><br><br>';
echo '3. 프로젝트 루트 단축 URL:<br>';
echo '   <a href="https://mathking.kr/moodle/local/augmented_teacher/mM9G" target="_blank">https://mathking.kr/moodle/local/augmented_teacher/mM9G</a><br>';
echo '</div>';

// 6. .htaccess 규칙 테스트
echo '<h2>6. .htaccess 규칙 테스트</h2>';
echo '<div class="info">';
echo '<strong>현재 .htaccess 규칙:</strong><br>';
echo '<code>RewriteCond %{REQUEST_URI} ^/([a-zA-Z0-9]{4}|[a-zA-Z0-9]{16})/?$</code><br>';
echo '<code>RewriteRule ^([a-zA-Z0-9]{4}|[a-zA-Z0-9]{16})/?$ /moodle/local/augmented_teacher/students/short.php?h=$1 [L,QSA]</code><br><br>';
echo '<strong>테스트 URL:</strong><br>';
echo '- <code>https://mathking.kr/mM9G</code> → <code>/mM9G</code> → 규칙 매칭 여부 확인<br>';
echo '- <code>https://mathking.kr/G1TegwNciF19ULJ9</code> → <code>/G1TegwNciF19ULJ9</code> → 규칙 매칭 여부 확인<br>';
echo '</div>';

// 7. 문제 해결 제안
echo '<h2>7. 문제 해결 제안</h2>';
echo '<div class="warning">';
echo '<strong>만약 단축 URL이 작동하지 않는다면:</strong><br><br>';
echo '1. Apache 재시작 필요할 수 있음:<br>';
echo '   <code>sudo systemctl restart apache2</code><br><br>';
echo '2. mod_rewrite 모듈 활성화:<br>';
echo '   <code>sudo a2enmod rewrite</code><br>';
echo '   <code>sudo systemctl restart apache2</code><br><br>';
echo '3. .htaccess 파일이 AllowOverride 설정으로 인해 무시될 수 있음<br>';
echo '   Apache 설정 파일에서 <code>AllowOverride All</code> 확인 필요<br><br>';
echo '4. 서버 루트의 다른 .htaccess 규칙과 충돌할 수 있음<br>';
echo '   규칙의 순서를 조정하거나 [L] 플래그 확인 필요<br>';
echo '</div>';

echo '</div>
</body>
</html>';
?>

