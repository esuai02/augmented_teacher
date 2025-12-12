<?php
/**
 * PyYAML 설치 스크립트
 * File: alt42/orchestration/agents/agent22_module_improvement/ui/install_pyyaml.php
 * 
 * Python yaml 모듈 설치를 위한 스크립트
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PyYAML 설치</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .step { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #667eea; }
        .step.success { border-left-color: #4caf50; }
        .step.error { border-left-color: #f44336; }
        pre { background: #f0f0f0; padding: 10px; overflow-x: auto; }
        .btn { padding: 10px 20px; background: #667eea; color: white; border: none; cursor: pointer; margin: 5px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #5568d3; }
    </style>
</head>
<body>
    <h1>📦 PyYAML 설치</h1>
    
    <div class="step">
        <h2>Python 버전 확인</h2>
        <?php
        // 여러 Python 버전 확인
        $pythonVersions = [];
        $pythonCmds = ['python3', 'python3.6', 'python3.7', 'python3.8', 'python3.9', 'python3.10', 'python3.11'];
        
        foreach ($pythonCmds as $cmd) {
            $version = shell_exec("{$cmd} --version 2>&1");
            if (strpos($version, 'Python') !== false) {
                $pythonVersions[$cmd] = trim($version);
                echo "<p>✅ <strong>{$cmd}:</strong> " . trim($version) . "</p>";
            }
        }
        ?>
    </div>
    
    <div class="step">
        <h2>각 Python 버전별 yaml 모듈 확인</h2>
        <?php
        $yamlStatus = [];
        foreach ($pythonVersions as $cmd => $version) {
            $yamlCheck = shell_exec("{$cmd} -c 'import yaml; print(yaml.__version__)' 2>&1");
            if (strpos($yamlCheck, 'ModuleNotFoundError') !== false || empty(trim($yamlCheck))) {
                echo "<p>❌ <strong>{$cmd}:</strong> yaml 모듈 없음</p>";
                $yamlStatus[$cmd] = false;
            } else {
                echo "<p>✅ <strong>{$cmd}:</strong> yaml 모듈 설치됨 (버전: " . trim($yamlCheck) . ")</p>";
                $yamlStatus[$cmd] = true;
            }
        }
        ?>
    </div>
    
    <?php
    // 설치가 필요한 Python 버전 찾기
    $needsInstall = [];
    foreach ($pythonVersions as $cmd => $version) {
        if (!isset($yamlStatus[$cmd]) || !$yamlStatus[$cmd]) {
            $needsInstall[] = $cmd;
        }
    }
    ?>
    
    <?php if (!empty($needsInstall)): ?>
    <div class="step">
        <h2>PyYAML 설치</h2>
        <p>다음 Python 버전에 PyYAML을 설치해야 합니다:</p>
        <ul>
            <?php foreach ($needsInstall as $cmd): ?>
                <li><?php echo $cmd; ?></li>
            <?php endforeach; ?>
        </ul>
        
        <?php
        if (isset($_GET['install']) && $_GET['install'] === '1') {
            echo "<h3>설치 시도 중...</h3>";
            
            $installResults = [];
            foreach ($needsInstall as $cmd) {
                echo "<h4>{$cmd}에 설치 중...</h4>";
                
                // pip3로 설치 시도
                $installCommand = "{$cmd} -m pip install PyYAML 2>&1";
                $installOutput = shell_exec($installCommand);
                
                echo "<pre>" . htmlspecialchars($installOutput) . "</pre>";
                
                // 설치 확인
                $verifyCheck = shell_exec("{$cmd} -c 'import yaml; print(yaml.__version__)' 2>&1");
                if (strpos($verifyCheck, 'ModuleNotFoundError') === false && !empty(trim($verifyCheck))) {
                    echo "<p style='color: green;'>✅ {$cmd}에 설치 성공! yaml 버전: " . trim($verifyCheck) . "</p>";
                    $installResults[$cmd] = true;
                } else {
                    echo "<p style='color: red;'>❌ {$cmd}에 설치 실패</p>";
                    $installResults[$cmd] = false;
                }
            }
            
            if (in_array(true, $installResults)) {
                echo "<p style='color: green; font-weight: bold;'>✅ 일부 Python 버전에 설치가 완료되었습니다!</p>";
                echo "<p><a href='debug_agent01.php?userid=2' class='btn'>디버그 페이지로 돌아가기</a></p>";
            } else {
                echo "<p style='color: red; font-weight: bold;'>❌ 모든 설치가 실패했습니다. 수동으로 설치해주세요.</p>";
            }
        } else {
            echo "<p><a href='?install=1' class='btn'>자동 설치 시도</a></p>";
            echo "<p><strong>주의:</strong> 서버 권한에 따라 자동 설치가 실패할 수 있습니다. 그 경우 서버 관리자에게 문의하거나 수동으로 설치해주세요.</p>";
        }
        ?>
    </div>
    <?php else: ?>
    <div class="step success">
        <h2>✅ 모든 Python 버전에 PyYAML이 설치되어 있습니다!</h2>
        <p><a href="debug_agent01.php?userid=2" class="btn">디버그 페이지로 돌아가기</a></p>
    </div>
    <?php endif; ?>
    
    <div class="step">
        <h2>수동 설치 방법</h2>
        <p>서버에 SSH로 접속하여 다음 명령어를 실행하세요:</p>
        <pre>
# Python 3.6용
python3.6 -m pip install PyYAML

# Python 3.10용 (이미 설치됨)
python3.10 -m pip install PyYAML

# 기본 python3용
python3 -m pip install PyYAML

# 또는 pip3 사용
pip3 install PyYAML

# 설치 확인
python3 -c "import yaml; print(yaml.__version__)"
python3.10 -c "import yaml; print(yaml.__version__)"
        </pre>
    </div>
</body>
</html>
