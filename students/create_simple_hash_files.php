<?php
/**
 * 간단한 해시별 PHP 파일 생성 스크립트
 * 파일: students/create_simple_hash_files.php
 */

require_once("/home/moodle/public_html/moodle/config_abessi.php");
global $DB, $USER;

$server_root = '/home/moodle/public_html/moodle';
$template_file = __DIR__ . '/../simple_short.php';

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <title>간단한 해시 파일 생성</title>
    <style>
        body { font-family: Arial; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { background: #e8f5e9; padding: 15px; margin: 10px 0; border-radius: 4px; }
        .error { background: #ffebee; padding: 15px; margin: 10px 0; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>간단한 해시 파일 생성</h1>';

try {
    // 템플릿 파일 읽기
    if (!file_exists($template_file)) {
        throw new Exception('템플릿 파일을 찾을 수 없습니다: ' . $template_file);
    }
    
    $template_content = file_get_contents($template_file);
    
    // 모든 해시 가져오기
    $all_urls = $DB->get_records_sql("SELECT hash FROM mdl_short_urls ORDER BY id ASC");
    
    if (!$all_urls) {
        echo '<div class="error">데이터베이스에 단축 URL이 없습니다.</div>';
    } else {
        echo '<div class="success">총 ' . count($all_urls) . '개의 해시를 찾았습니다.</div>';
        
        $created = 0;
        $skipped = 0;
        $failed = 0;
        
        echo '<table><tr><th>해시</th><th>파일명</th><th>상태</th><th>테스트</th></tr>';
        
        foreach ($all_urls as $url) {
            $hash = $url->hash;
            $php_file = $server_root . '/' . $hash . '.php';
            
            if (file_exists($php_file)) {
                echo '<tr><td>' . htmlspecialchars($hash) . '</td><td>' . htmlspecialchars($hash . '.php') . '</td><td style="color:green;">✓ 존재</td><td><a href="https://mathking.kr/' . htmlspecialchars($hash) . '" target="_blank">테스트</a></td></tr>';
                $skipped++;
            } else {
                // 파일 생성 (템플릿을 그대로 사용 - 파일명에서 해시 추출)
                if (is_writable($server_root)) {
                    if (file_put_contents($php_file, $template_content)) {
                        chmod($php_file, 0644);
                        echo '<tr><td>' . htmlspecialchars($hash) . '</td><td>' . htmlspecialchars($hash . '.php') . '</td><td style="color:green;">✓ 생성됨</td><td><a href="https://mathking.kr/' . htmlspecialchars($hash) . '" target="_blank">테스트</a></td></tr>';
                        $created++;
                    } else {
                        echo '<tr><td>' . htmlspecialchars($hash) . '</td><td>' . htmlspecialchars($hash . '.php') . '</td><td style="color:red;">✗ 실패</td><td>-</td></tr>';
                        $failed++;
                    }
                } else {
                    echo '<tr><td>' . htmlspecialchars($hash) . '</td><td>' . htmlspecialchars($hash . '.php') . '</td><td style="color:red;">✗ 권한 없음</td><td>-</td></tr>';
                    $failed++;
                }
            }
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
    echo '<div class="error">오류: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

echo '</body></html>';
?>

