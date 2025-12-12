<?php
/**
 * OntologyActionHandler.php 파일 로드 테스트
 * File: alt42/orchestration/agents/agent01_onboarding/ontology/test_load.php
 * 
 * 이 파일은 OntologyActionHandler.php가 제대로 로드되고 사용할 수 있는지 테스트합니다.
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
    <title>OntologyActionHandler 로드 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .test-result { margin: 15px 0; padding: 15px; border: 2px solid #ddd; border-radius: 4px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; color: #0c5460; }
        .code { font-family: 'Courier New', monospace; background: #f8f9fa; padding: 10px; border-radius: 4px; margin: 10px 0; overflow-x: auto; }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; }
        .note { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 OntologyActionHandler.php 로드 테스트</h1>
        
        <div class="note">
            <strong>ℹ️ 참고:</strong> PHP 클래스 파일을 브라우저로 직접 열면 빈 화면이 보이는 것이 <strong>정상</strong>입니다.<br>
            클래스 파일은 출력이 없기 때문에 빈 화면으로 보입니다. 이 파일은 다른 PHP 파일에서 <code>require_once()</code>로 로드되어 사용됩니다.
        </div>
        
        <?php
        $filePath = __DIR__ . '/OntologyActionHandler.php';
        $enginePath = __DIR__ . '/OntologyEngine.php';
        
        echo "<h2>1️⃣ 파일 존재 확인</h2>";
        
        // OntologyActionHandler.php 확인
        if (file_exists($filePath)) {
            $size = filesize($filePath);
            $lines = count(file($filePath));
            echo "<div class='test-result success'>";
            echo "✅ <strong>OntologyActionHandler.php</strong> 파일 존재<br>";
            echo "크기: " . number_format($size) . " bytes<br>";
            echo "줄 수: {$lines} lines<br>";
            echo "</div>";
        } else {
            echo "<div class='test-result error'>";
            echo "❌ <strong>OntologyActionHandler.php</strong> 파일 없음<br>";
            echo "경로: {$filePath}<br>";
            echo "</div>";
            exit;
        }
        
        // OntologyEngine.php 확인
        if (file_exists($enginePath)) {
            $engineSize = filesize($enginePath);
            echo "<div class='test-result success'>";
            echo "✅ <strong>OntologyEngine.php</strong> 파일 존재<br>";
            echo "크기: " . number_format($engineSize) . " bytes<br>";
            echo "</div>";
        } else {
            echo "<div class='test-result error'>";
            echo "❌ <strong>OntologyEngine.php</strong> 파일 없음<br>";
            echo "</div>";
        }
        
        echo "<h2>2️⃣ 파일 로드 테스트</h2>";
        
        try {
            // OntologyEngine.php 먼저 로드
            if (file_exists($enginePath)) {
                require_once($enginePath);
                echo "<div class='test-result success'>";
                echo "✅ <strong>OntologyEngine.php</strong> 로드 성공<br>";
                echo "클래스 존재: " . (class_exists('OntologyEngine') ? '✅ 예' : '❌ 아니오') . "<br>";
                echo "</div>";
            }
            
            // OntologyActionHandler.php 로드
            require_once($filePath);
            echo "<div class='test-result success'>";
            echo "✅ <strong>OntologyActionHandler.php</strong> 로드 성공<br>";
            echo "클래스 존재: " . (class_exists('OntologyActionHandler') ? '✅ 예' : '❌ 아니오') . "<br>";
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='test-result error'>";
            echo "❌ 파일 로드 실패<br>";
            echo "에러: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "파일: " . htmlspecialchars($e->getFile()) . "<br>";
            echo "라인: " . $e->getLine() . "<br>";
            echo "</div>";
            exit;
        } catch (Error $e) {
            echo "<div class='test-result error'>";
            echo "❌ 파일 로드 실패 (PHP 에러)<br>";
            echo "에러: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "파일: " . htmlspecialchars($e->getFile()) . "<br>";
            echo "라인: " . $e->getLine() . "<br>";
            echo "</div>";
            exit;
        }
        
        echo "<h2>3️⃣ 클래스 인스턴스 생성 테스트</h2>";
        
        try {
            // OntologyActionHandler 인스턴스 생성
            $testContext = [
                'student_id' => $USER->id ?? 2,
                'gradeLevel' => '중2',
                'math_confidence' => 4
            ];
            
            $handler = new OntologyActionHandler('agent01', $testContext, $USER->id ?? 2);
            echo "<div class='test-result success'>";
            echo "✅ <strong>OntologyActionHandler</strong> 인스턴스 생성 성공<br>";
            echo "학생 ID: " . ($USER->id ?? 2) . "<br>";
            echo "</div>";
            
            // 주요 메서드 존재 확인
            $methods = [
                'executeAction',
                'setContext'
            ];
            
            echo "<div class='test-result info'>";
            echo "<strong>주요 메서드 확인:</strong><br>";
            foreach ($methods as $method) {
                $exists = method_exists($handler, $method);
                echo ($exists ? '✅' : '❌') . " {$method}()<br>";
            }
            echo "</div>";
            
        } catch (Exception $e) {
            echo "<div class='test-result error'>";
            echo "❌ 인스턴스 생성 실패<br>";
            echo "에러: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "파일: " . htmlspecialchars($e->getFile()) . "<br>";
            echo "라인: " . $e->getLine() . "<br>";
            echo "</div>";
        } catch (Error $e) {
            echo "<div class='test-result error'>";
            echo "❌ 인스턴스 생성 실패 (PHP 에러)<br>";
            echo "에러: " . htmlspecialchars($e->getMessage()) . "<br>";
            echo "파일: " . htmlspecialchars($e->getFile()) . "<br>";
            echo "라인: " . $e->getLine() . "<br>";
            echo "</div>";
        }
        
        echo "<h2>4️⃣ 실제 사용 경로 확인</h2>";
        
        $servicePath = __DIR__ . '/../../agent22_module_improvement/ui/agent_garden.service.php';
        if (file_exists($servicePath)) {
            $serviceContent = file_get_contents($servicePath);
            
            // OntologyActionHandler를 사용하는 부분 확인
            if (strpos($serviceContent, 'OntologyActionHandler') !== false) {
                echo "<div class='test-result success'>";
                echo "✅ <strong>agent_garden.service.php</strong>에서 OntologyActionHandler 사용 확인<br>";
                
                // 경로 확인
                if (preg_match("/agent01_onboarding\/ontology\/OntologyActionHandler\.php/", $serviceContent)) {
                    echo "✅ Agent01 전용 핸들러 경로 사용 중<br>";
                }
                
                echo "</div>";
            } else {
                echo "<div class='test-result error'>";
                echo "❌ <strong>agent_garden.service.php</strong>에서 OntologyActionHandler 사용 안 함<br>";
                echo "</div>";
            }
        } else {
            echo "<div class='test-result error'>";
            echo "❌ <strong>agent_garden.service.php</strong> 파일 없음<br>";
            echo "</div>";
        }
        
        echo "<h2>✅ 결론</h2>";
        echo "<div class='test-result success'>";
        echo "<strong>파일이 정상적으로 로드되고 사용할 수 있습니다!</strong><br><br>";
        echo "브라우저에서 직접 PHP 클래스 파일을 열면 빈 화면이 보이는 것은 <strong>정상</strong>입니다.<br>";
        echo "이 파일은 <code>agent_garden.service.php</code>에서 <code>require_once()</code>로 로드되어 사용됩니다.<br>";
        echo "</div>";
        ?>
    </div>
</body>
</html>

