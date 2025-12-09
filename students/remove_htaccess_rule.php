<?php
/**
 * .htaccess에서 단축 URL 규칙 제거 스크립트
 * 파일: students/remove_htaccess_rule.php
 * 에러 출력 위치: remove_htaccess_rule.php
 */

$moodle_root = '/home/moodle/public_html/moodle';
$htaccess_path = $moodle_root . '/.htaccess';

echo '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>.htaccess 규칙 제거</title>
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
        .warning {
            background: #fff3e0;
            padding: 15px;
            border-radius: 4px;
            margin: 15px 0;
            color: #e65100;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>.htaccess 단축 URL 규칙 제거</h1>';

try {
    if (!file_exists($htaccess_path)) {
        echo '<div class="info">
            .htaccess 파일이 없습니다. 제거할 규칙이 없습니다.
        </div>';
    } else {
        $htaccess_content = file_get_contents($htaccess_path);
        $original_content = $htaccess_content;
        
        // 단축 URL 규칙 찾기
        $rule_pattern = '/# 단축 URL 리라이트 규칙.*?<\/IfModule>\s*/s';
        
        if (preg_match($rule_pattern, $htaccess_content)) {
            // 규칙 제거
            $new_content = preg_replace($rule_pattern, '', $htaccess_content);
            
            // 연속된 빈 줄 정리
            $new_content = preg_replace('/\n{3,}/', "\n\n", $new_content);
            
            // 파일 쓰기
            if (is_writable($htaccess_path)) {
                if (file_put_contents($htaccess_path, $new_content)) {
                    echo '<div class="success">
                        <strong>✓ 규칙 제거 완료!</strong><br>
                        .htaccess 파일에서 단축 URL 리라이트 규칙이 제거되었습니다.<br>
                        파일 위치: ' . htmlspecialchars($htaccess_path) . '<br><br>
                        <strong>참고:</strong> 이제 각 해시별 PHP 파일 방식으로 작동하므로 .htaccess 규칙이 필요 없습니다.
                    </div>';
                    
                    // 변경 전후 비교
                    echo '<div class="info">
                        <strong>변경 전 (제거된 규칙):</strong>
                    </div>';
                    preg_match($rule_pattern, $original_content, $matches);
                    echo '<pre>' . htmlspecialchars($matches[0] ?? '규칙을 찾을 수 없음') . '</pre>';
                    
                } else {
                    throw new Exception('파일 쓰기에 실패했습니다. [remove_htaccess_rule.php:65]');
                }
            } else {
                echo '<div class="error">
                    <strong>⚠ 권한 오류</strong><br>
                    .htaccess 파일에 쓰기 권한이 없습니다.<br>
                    파일 위치: ' . htmlspecialchars($htaccess_path) . '<br><br>
                    <strong>수동 제거 방법:</strong><br>
                    1. .htaccess 파일을 열어서<br>
                    2. "단축 URL 리라이트 규칙"으로 시작하는 부분을 찾아서<br>
                    3. 해당 부분 전체를 삭제하세요.
                </div>';
            }
        } else {
            echo '<div class="info">
                .htaccess 파일에 단축 URL 리라이트 규칙이 없습니다.<br>
                이미 제거되었거나 추가되지 않았습니다.
            </div>';
        }
        
        // 현재 .htaccess 내용 확인 (마지막 30줄)
        $lines = explode("\n", file_get_contents($htaccess_path));
        $last_lines = array_slice($lines, -30);
        echo '<div class="info">
            <strong>현재 .htaccess 파일 내용 (마지막 30줄):</strong>
        </div>';
        echo '<pre>' . htmlspecialchars(implode("\n", $last_lines)) . '</pre>';
    }
    
    echo '<div class="warning">
        <strong>중요:</strong><br>
        .htaccess 규칙을 제거해도 단축 URL은 정상 작동합니다.<br>
        각 해시별 PHP 파일 방식으로 작동하므로 .htaccess가 필요 없습니다.<br><br>
        테스트: <a href="https://mathking.kr/mM9G" target="_blank">https://mathking.kr/mM9G</a>
    </div>';
    
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

