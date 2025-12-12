<?php
/**
 * Agent01 디버그 페이지
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/debug_agent01.php
 * 
 * Agent01 실행을 단계별로 테스트하고 디버깅하는 페이지
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// URL 파라미터에서 userid 가져오기
$targetUserId = isset($_GET['userid']) && !empty($_GET['userid']) ? intval($_GET['userid']) : $USER->id;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent01 디버그</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
        .step.success { border-left-color: #4caf50; }
        .step.error { border-left-color: #f44336; }
        .step.warning { border-left-color: #ff9800; }
        h2 { margin-top: 0; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .test-btn { padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; margin: 5px; }
        .test-btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <h1>🔍 Agent01 디버그 페이지</h1>
    <p>Target User ID: <?php echo $targetUserId; ?></p>
    
    <div class="step">
        <h2>Step 1: 파일 경로 확인</h2>
        <?php
        $agent01RulesPath = __DIR__ . '/../../agent01_onboarding/rules';
        $rulesFilePath = $agent01RulesPath . '/rules.yaml';
        $ruleEvaluatorPath = $agent01RulesPath . '/rule_evaluator.php';
        $dataAccessPath = $agent01RulesPath . '/data_access.php';
        $pythonScriptPath = $agent01RulesPath . '/onboarding_rule_engine.py';
        
        echo "<p><strong>agent01RulesPath:</strong> {$agent01RulesPath}</p>";
        echo "<p><strong>rulesFilePath:</strong> {$rulesFilePath}</p>";
        echo "<p><strong>ruleEvaluatorPath:</strong> {$ruleEvaluatorPath}</p>";
        echo "<p><strong>dataAccessPath:</strong> {$dataAccessPath}</p>";
        echo "<p><strong>pythonScriptPath:</strong> {$pythonScriptPath}</p>";
        
        $allFilesExist = true;
        $files = [
            'rules.yaml' => $rulesFilePath,
            'rule_evaluator.php' => $ruleEvaluatorPath,
            'data_access.php' => $dataAccessPath,
            'onboarding_rule_engine.py' => $pythonScriptPath
        ];
        
        foreach ($files as $name => $path) {
            $exists = file_exists($path);
            $allFilesExist = $allFilesExist && $exists;
            $status = $exists ? '✅' : '❌';
            echo "<p>{$status} <strong>{$name}:</strong> " . ($exists ? '존재함' : '없음') . "</p>";
            if ($exists) {
                echo "<p style='margin-left: 20px; color: #666;'>실제 경로: " . realpath($path) . "</p>";
            }
        }
        ?>
    </div>
    
    <div class="step <?php echo $allFilesExist ? 'success' : 'error'; ?>">
        <h2>Step 2: Python3 확인</h2>
        <?php
        $python3Path = shell_exec("which python3 2>&1");
        $python3Version = shell_exec("python3 --version 2>&1");
        
        if ($python3Path) {
            echo "<p>✅ <strong>python3 경로:</strong> " . trim($python3Path) . "</p>";
        } else {
            echo "<p>❌ <strong>python3:</strong> 찾을 수 없음</p>";
        }
        
        if ($python3Version) {
            echo "<p>✅ <strong>python3 버전:</strong> " . trim($python3Version) . "</p>";
        } else {
            echo "<p>❌ <strong>python3 실행:</strong> 실패</p>";
        }
        ?>
    </div>
    
    <div class="step">
        <h2>Step 3: 학생 컨텍스트 가져오기</h2>
        <?php
        try {
            if (file_exists($dataAccessPath)) {
                require_once($dataAccessPath);
                $context = prepareRuleContext($targetUserId);
                
                if ($context) {
                    echo "<p>✅ 컨텍스트 가져오기 성공</p>";
                    echo "<p><strong>student_id:</strong> " . ($context['student_id'] ?? '없음') . "</p>";
                    echo "<pre>" . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre>";
                } else {
                    echo "<p>❌ 컨텍스트가 null입니다.</p>";
                }
            } else {
                echo "<p>❌ data_access.php 파일을 찾을 수 없습니다.</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ 오류: " . $e->getMessage() . "</p>";
        }
        ?>
    </div>
    
    <div class="step">
        <h2>Step 4: Python 스크립트 테스트</h2>
        <?php
        if (file_exists($pythonScriptPath)) {
            $testContext = json_encode(['student_id' => $targetUserId], JSON_UNESCAPED_UNICODE);
            $testCommand = "python3 " . escapeshellarg(realpath($pythonScriptPath)) . " " . escapeshellarg($testContext) . " " . escapeshellarg(realpath($rulesFilePath)) . " 2>&1";
            
            echo "<p><strong>테스트 명령어:</strong></p>";
            echo "<pre>" . htmlspecialchars($testCommand) . "</pre>";
            
            $testOutput = shell_exec($testCommand);
            
            if ($testOutput) {
                echo "<p>✅ Python 스크립트 실행 성공</p>";
                echo "<p><strong>출력:</strong></p>";
                echo "<pre>" . htmlspecialchars($testOutput) . "</pre>";
                
                $testResult = json_decode($testOutput, true);
                if ($testResult) {
                    echo "<p>✅ JSON 파싱 성공</p>";
                } else {
                    echo "<p>❌ JSON 파싱 실패: " . json_last_error_msg() . "</p>";
                }
            } else {
                echo "<p>❌ Python 스크립트 실행 실패 (출력 없음)</p>";
            }
        } else {
            echo "<p>❌ Python 스크립트 파일을 찾을 수 없습니다.</p>";
        }
        ?>
    </div>
    
    <div class="step">
        <h2>Step 5: 전체 실행 테스트</h2>
        <button class="test-btn" onclick="testFullExecution()">전체 실행 테스트</button>
        <div id="testResult"></div>
    </div>
    
    <script>
        async function testFullExecution() {
            const resultDiv = document.getElementById('testResult');
            resultDiv.innerHTML = '<p>테스트 중...</p>';
            
            try {
                const response = await fetch('agent_garden.controller.php?action=execute&userid=<?php echo $targetUserId; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    body: JSON.stringify({
                        agent_id: 'agent01',
                        request: '테스트 메시지',
                        student_id: <?php echo $targetUserId; ?>
                    })
                });
                
                const result = await response.json();
                resultDiv.innerHTML = '<pre>' + JSON.stringify(result, null, 2) + '</pre>';
            } catch (error) {
                resultDiv.innerHTML = '<p style="color: red;">오류: ' + error.message + '</p>';
            }
        }
    </script>
</body>
</html>

