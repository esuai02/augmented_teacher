<?php
/**
 * 기존 단축 URL 해시에 대한 PHP 파일 생성 스크립트
 * 파일: students/create_existing_hash_files.php
 * 에러 출력 위치: create_existing_hash_files.php
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

$server_root = '/home/moodle/public_html/moodle';

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>기존 해시 PHP 파일 생성</title>
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
            margin: 10px 0;
            color: #2e7d32;
        }
        .error {
            background: #ffebee;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            color: #c62828;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            color: #1565c0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>기존 해시 PHP 파일 생성</h1>';

try {
    // 모든 단축 URL 가져오기
    $all_urls = $DB->get_records_sql("
        SELECT id, hash, original_url, created_at
        FROM mdl_short_urls 
        ORDER BY id ASC
    ");
    
    if (!$all_urls) {
        echo '<div class="info">데이터베이스에 단축 URL이 없습니다.</div>';
    } else {
        echo '<div class="info">총 ' . count($all_urls) . '개의 단축 URL을 찾았습니다.</div>';
        
        $created = 0;
        $skipped = 0;
        $failed = 0;
        
        echo '<table>
            <tr>
                <th>해시</th>
                <th>원본 URL</th>
                <th>파일 상태</th>
                <th>테스트 링크</th>
            </tr>';
        
        foreach ($all_urls as $url) {
            $php_file = $server_root . '/' . $url->hash . '.php';
            $file_exists = file_exists($php_file);
            
            if ($file_exists) {
                $status = '<span style="color: green;">✓ 파일 존재</span>';
                $skipped++;
            } else {
                // PHP 파일 생성
                $php_content = '<?php
require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;
$hash = \'' . $url->hash . '\';
try {
    $record = $DB->get_record_sql("SELECT id, original_url, expired_at FROM mdl_short_urls WHERE hash = ?", array($hash));
    if ($record && ($record->expired_at === null || strtotime($record->expired_at) >= time())) {
        $DB->execute("UPDATE mdl_short_urls SET click_count = click_count + 1 WHERE id = ?", array($record->id));
        header(\'Location: \' . $record->original_url, true, 302);
        exit;
    }
    http_response_code(404);
    echo \'<h1>단축 URL 오류</h1><p>단축 URL을 찾을 수 없습니다.</p>\';
} catch (Exception $e) {
    http_response_code(500);
    echo \'<h1>오류</h1><p>\' . htmlspecialchars($e->getMessage()) . \'</p>\';
}
?>';
                
                if (is_writable($server_root) || !file_exists($php_file)) {
                    if (file_put_contents($php_file, $php_content)) {
                        chmod($php_file, 0644);
                        $status = '<span style="color: green;">✓ 생성 완료</span>';
                        $created++;
                    } else {
                        $status = '<span style="color: red;">✗ 생성 실패 (권한 없음)</span>';
                        $failed++;
                    }
                } else {
                    $status = '<span style="color: red;">✗ 생성 실패 (쓰기 불가)</span>';
                    $failed++;
                }
            }
            
            $test_link = '<a href="https://mathking.kr/' . htmlspecialchars($url->hash) . '" target="_blank">테스트</a>';
            
            echo '<tr>
                <td>' . htmlspecialchars($url->hash) . '</td>
                <td style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">' . htmlspecialchars(substr($url->original_url, 0, 50)) . '...</td>
                <td>' . $status . '</td>
                <td>' . $test_link . '</td>
            </tr>';
        }
        
        echo '</table>';
        
        echo '<div class="success">
            <strong>완료:</strong><br>
            생성됨: ' . $created . '개<br>
            이미 존재: ' . $skipped . '개<br>
            실패: ' . $failed . '개
        </div>';
    }
    
} catch (Exception $e) {
    echo '<div class="error">
        <strong>오류 발생:</strong><br>
        ' . htmlspecialchars($e->getMessage()) . '
    </div>';
}

echo '</div>
</body>
</html>';
?>

