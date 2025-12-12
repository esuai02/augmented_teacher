<?php
/**
 * OntologyActionHandler.php 파일 내용 보기
 * File: alt42/orchestration/agents/agent01_onboarding/ontology/view_file.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole ? $userrole->data : 'student';

// 관리자만 접근 가능하도록 (선택사항)
// if ($role !== 'admin' && $role !== 'manager') {
//     die('접근 권한이 없습니다.');
// }

$filePath = __DIR__ . '/OntologyActionHandler.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OntologyActionHandler.php 내용</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #4ec9b0; margin-bottom: 10px; }
        .file-info { background: #252526; padding: 15px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #3e3e42; }
        .file-info strong { color: #4ec9b0; }
        .file-path { font-family: 'Courier New', monospace; color: #ce9178; background: #1e1e1e; padding: 8px; border-radius: 4px; margin: 5px 0; }
        .code-container { background: #1e1e1e; border: 1px solid #3e3e42; border-radius: 4px; overflow: auto; max-height: 80vh; }
        .code-content { font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.5; padding: 15px; }
        .line-number { color: #858585; padding-right: 15px; text-align: right; user-select: none; }
        .line { display: flex; }
        .line:hover { background: #2a2d2e; }
        .status { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-left: 10px; }
        .status.exists { background: #28a745; color: white; }
        .status.missing { background: #dc3545; color: white; }
        .status.empty { background: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📄 OntologyActionHandler.php 파일 내용</h1>
        
        <?php
        $fileExists = file_exists($filePath);
        
        echo "<div class='file-info'>";
        echo "<strong>파일 경로:</strong><br>";
        echo "<div class='file-path'>{$filePath}</div>";
        
        if ($fileExists) {
            $size = filesize($filePath);
            $modified = date('Y-m-d H:i:s', filemtime($filePath));
            $lines = count(file($filePath));
            
            echo "<span class='status exists'>✅ 파일 존재</span>";
            echo "<br><br>";
            echo "<strong>파일 크기:</strong> " . number_format($size) . " bytes<br>";
            echo "<strong>수정일:</strong> {$modified}<br>";
            echo "<strong>줄 수:</strong> {$lines} lines<br>";
            
            // 파일 내용 읽기
            $content = file_get_contents($filePath);
            
            if (empty($content) || trim($content) === '') {
                echo "<br><span class='status empty'>⚠️ 파일이 비어있습니다!</span>";
            } else {
                echo "<br><span class='status exists'>✅ 파일 내용 있음</span>";
                
                // 파일 내용 표시
                echo "</div>";
                echo "<div class='code-container'>";
                echo "<div class='code-content'>";
                
                $lines = explode("\n", $content);
                foreach ($lines as $i => $line) {
                    $lineNum = $i + 1;
                    $escapedLine = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
                    echo "<div class='line'>";
                    echo "<span class='line-number'>{$lineNum}</span>";
                    echo "<span>{$escapedLine}</span>";
                    echo "</div>";
                }
                
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "<span class='status missing'>❌ 파일 없음</span>";
            echo "<br><br>";
            echo "<strong>파일이 서버에 존재하지 않습니다.</strong><br>";
            echo "로컬 파일을 서버에 업로드해야 합니다.";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html>

