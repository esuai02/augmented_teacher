<?php
/**
 * OntologyActionHandler.php 파일 확인 스크립트
 * File: alt42/orchestration/agents/agent01_onboarding/ontology/check_file.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>OntologyActionHandler.php 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .file-check { margin: 15px 0; padding: 15px; border: 2px solid #ddd; border-radius: 4px; }
        .exists { background-color: #d4edda; border-color: #c3e6cb; }
        .missing { background-color: #f8d7da; border-color: #f5c6cb; }
        .path { font-family: 'Courier New', monospace; color: #666; font-size: 0.9em; background: #f8f9fa; padding: 8px; border-radius: 4px; margin: 5px 0; }
        .content-preview { max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 10px; font-family: 'Courier New', monospace; font-size: 0.85em; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        .info { color: #0066cc; }
        .error { color: #dc3545; }
        .success { color: #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 OntologyActionHandler.php 파일 확인</h1>
        
        <?php
        $filePath = __DIR__ . '/OntologyActionHandler.php';
        $fileExists = file_exists($filePath);
        
        echo "<div class='file-check " . ($fileExists ? 'exists' : 'missing') . "'>";
        echo "<h2>" . ($fileExists ? "✅ 파일 존재함" : "❌ 파일 없음") . "</h2>";
        echo "<div class='path'><strong>경로:</strong> {$filePath}</div>";
        
        if ($fileExists) {
            $size = filesize($filePath);
            $modified = date('Y-m-d H:i:s', filemtime($filePath));
            $lines = count(file($filePath));
            
            echo "<div class='success'>";
            echo "<strong>파일 크기:</strong> " . number_format($size) . " bytes<br>";
            echo "<strong>수정일:</strong> {$modified}<br>";
            echo "<strong>줄 수:</strong> {$lines} lines<br>";
            echo "</div>";
            
            // 파일 내용 읽기
            $content = file_get_contents($filePath);
            
            if (empty($content)) {
                echo "<div class='error'><strong>⚠️ 경고:</strong> 파일이 비어있습니다!</div>";
            } else {
                echo "<div class='success'><strong>✅ 파일 내용 있음</strong></div>";
                
                // 클래스 정의 확인
                if (strpos($content, 'class OntologyActionHandler') !== false) {
                    echo "<div class='success'>✅ 클래스 정의 확인됨: OntologyActionHandler</div>";
                } else {
                    echo "<div class='error'>❌ 클래스 정의를 찾을 수 없습니다</div>";
                }
                
                // 주요 메서드 확인
                $methods = [
                    'executeAction',
                    'handleCreateInstance',
                    'handleReasonOver',
                    'handleGenerateStrategy',
                    'handleGenerateProcedure'
                ];
                
                echo "<h3>주요 메서드 확인:</h3>";
                foreach ($methods as $method) {
                    if (strpos($content, "function {$method}") !== false || strpos($content, "private function {$method}") !== false || strpos($content, "public function {$method}") !== false) {
                        echo "<div class='success'>✅ {$method}() 메서드 존재</div>";
                    } else {
                        echo "<div class='error'>❌ {$method}() 메서드 없음</div>";
                    }
                }
                
                // 파일 내용 미리보기 (처음 50줄)
                echo "<h3>파일 내용 미리보기 (처음 50줄):</h3>";
                $lines = explode("\n", $content);
                $preview = array_slice($lines, 0, 50);
                echo "<div class='content-preview'>";
                foreach ($preview as $i => $line) {
                    $lineNum = $i + 1;
                    echo "<div>" . str_pad($lineNum, 4, ' ', STR_PAD_LEFT) . " | " . htmlspecialchars($line) . "</div>";
                }
                if (count($lines) > 50) {
                    echo "<div>... (총 " . count($lines) . " 줄 중 50줄만 표시)</div>";
                }
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<strong>파일이 서버에 존재하지 않습니다.</strong><br>";
            echo "로컬 파일을 서버에 업로드해야 합니다.<br>";
            echo "<br>";
            echo "<strong>로컬 경로:</strong><br>";
            echo "<div class='path'>C:\\1 Project\\augmented_teacher\\alt42\\orchestration\\agents\\agent01_onboarding\\ontology\\OntologyActionHandler.php</div>";
            echo "<br>";
            echo "<strong>서버 경로:</strong><br>";
            echo "<div class='path'>{$filePath}</div>";
            echo "</div>";
        }
        
        echo "</div>";
        
        // OntologyEngine.php도 확인
        echo "<h2>📄 OntologyEngine.php 확인</h2>";
        $enginePath = __DIR__ . '/OntologyEngine.php';
        $engineExists = file_exists($enginePath);
        
        echo "<div class='file-check " . ($engineExists ? 'exists' : 'missing') . "'>";
        echo "<div class='path'><strong>경로:</strong> {$enginePath}</div>";
        if ($engineExists) {
            $engineSize = filesize($enginePath);
            echo "<div class='success'>✅ 파일 존재함 (" . number_format($engineSize) . " bytes)</div>";
        } else {
            echo "<div class='error'>❌ 파일 없음</div>";
        }
        echo "</div>";
        ?>
        
        <h2>ℹ️ 참고사항</h2>
        <div class="file-check">
            <div class="info">
                <strong>PHP 클래스 파일을 브라우저로 직접 열면 빈 화면이 보이는 것이 정상입니다.</strong><br>
                클래스 정의 파일은 출력이 없기 때문에 빈 화면으로 보일 수 있습니다.<br>
                이 파일은 다른 PHP 파일에서 <code>require_once()</code>로 로드되어 사용됩니다.
            </div>
        </div>
    </div>
</body>
</html>

