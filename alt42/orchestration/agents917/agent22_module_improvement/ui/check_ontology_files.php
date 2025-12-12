<?php
/**
 * 온톨로지 파일 존재 확인 디버그 페이지
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/check_ontology_files.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole ? $userrole->data : 'student';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>온톨로지 파일 확인</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .file-check { margin: 10px 0; padding: 10px; border: 1px solid #ddd; }
        .exists { background-color: #d4edda; }
        .missing { background-color: #f8d7da; }
        .path { font-family: monospace; color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>온톨로지 파일 존재 확인</h1>
    
    <?php
    // 확인할 파일들
    $filesToCheck = [
        'OntologyActionHandler (Agent01)' => __DIR__ . '/../../agent01_onboarding/ontology/OntologyActionHandler.php',
        'OntologyEngine (Agent01)' => __DIR__ . '/../../agent01_onboarding/ontology/OntologyEngine.php',
        'OntologyActionHandler (Generic)' => __DIR__ . '/../ontology/OntologyActionHandler.php',
        'agent_garden.service.php' => __DIR__ . '/agent_garden.service.php',
    ];
    
    foreach ($filesToCheck as $name => $path) {
        $exists = file_exists($path);
        $class = $exists ? 'exists' : 'missing';
        $status = $exists ? '✅ 존재함' : '❌ 없음';
        
        echo "<div class='file-check {$class}'>";
        echo "<strong>{$name}</strong>: {$status}<br>";
        echo "<span class='path'>경로: {$path}</span><br>";
        
        if ($exists) {
            $size = filesize($path);
            $modified = date('Y-m-d H:i:s', filemtime($path));
            echo "크기: " . number_format($size) . " bytes<br>";
            echo "수정일: {$modified}<br>";
            
            // 파일 내용 일부 확인
            $content = file_get_contents($path);
            if (strpos($content, 'class OntologyActionHandler') !== false || 
                strpos($content, 'class OntologyEngine') !== false ||
                strpos($content, 'class AgentGardenService') !== false) {
                echo "✅ 클래스 정의 확인됨<br>";
            }
        }
        
        echo "</div>";
    }
    
    // 실제 경로 확인
    echo "<h2>실제 경로 정보</h2>";
    echo "<div class='file-check'>";
    echo "<strong>현재 디렉토리 (__DIR__):</strong><br>";
    echo "<span class='path'>" . __DIR__ . "</span><br><br>";
    
    echo "<strong>Agent01 핸들러 예상 경로:</strong><br>";
    $expectedPath = __DIR__ . '/../../agent01_onboarding/ontology/OntologyActionHandler.php';
    echo "<span class='path'>" . $expectedPath . "</span><br>";
    echo "</div>";
    ?>
</body>
</html>

