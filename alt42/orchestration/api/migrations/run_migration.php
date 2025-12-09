<?php
/**
 * 마이그레이션 웹 실행 인터페이스
 * 서버에서 웹 브라우저를 통해 마이그레이션 실행
 * 
 * @package ALT42\Migrations
 * @version 1.0.0
 * @error_location __FILE__:__LINE__
 */

// Moodle config 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

if ($role !== 'admin' && $role !== 'manager') {
    die("권한이 없습니다. 관리자만 실행할 수 있습니다. at " . __FILE__ . ":" . __LINE__);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Heartbeat 마이그레이션 실행</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .button { background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; font-size: 14px; }
        .button:hover { background: #45a049; }
        .button-danger { background: #f44336; }
        .button-danger:hover { background: #da190b; }
        .output { background: #000; color: #0f0; padding: 15px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 12px; margin-top: 20px; max-height: 500px; overflow-y: auto; white-space: pre-wrap; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .status.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Heartbeat 마이그레이션 실행</h1>
        
        <div>
            <button class="button" onclick="runMigration('005')">마이그레이션 005 실행</button>
            <button class="button" onclick="runMigration('006')">마이그레이션 006 실행</button>
            <button class="button" onclick="runMigration('all')">전체 마이그레이션 실행</button>
            <button class="button" onclick="runTest()">테스트 실행</button>
            <button class="button button-danger" onclick="clearOutput()">출력 지우기</button>
        </div>
        
        <div id="status"></div>
        <div id="output" class="output"></div>
    </div>

    <script>
        function addOutput(text, type = 'info') {
            const output = document.getElementById('output');
            const timestamp = new Date().toLocaleTimeString();
            const className = type === 'error' ? 'error' : type === 'success' ? 'success' : 'info';
            output.innerHTML += `<span class="${className}">[${timestamp}] ${text}</span>\n`;
            output.scrollTop = output.scrollHeight;
        }

        function setStatus(message, type) {
            const status = document.getElementById('status');
            status.innerHTML = `<div class="status ${type}">${message}</div>`;
        }

        function clearOutput() {
            document.getElementById('output').innerHTML = '';
            document.getElementById('status').innerHTML = '';
        }

        function runMigration(migration) {
            clearOutput();
            setStatus('마이그레이션 실행 중...', 'info');
            addOutput(`마이그레이션 ${migration} 실행 시작...`, 'info');
            
            const formData = new FormData();
            formData.append('migration', migration);
            formData.append('action', 'run_migration');
            
            fetch('run_migration_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addOutput(data.output, 'success');
                    setStatus('✅ 마이그레이션 성공', 'success');
                } else {
                    addOutput(data.error || '알 수 없는 오류', 'error');
                    setStatus('❌ 마이그레이션 실패', 'error');
                }
            })
            .catch(error => {
                addOutput('오류: ' + error.message, 'error');
                setStatus('❌ 실행 오류', 'error');
            });
        }

        function runTest() {
            clearOutput();
            setStatus('테스트 실행 중...', 'info');
            addOutput('Heartbeat 테스트 실행 시작...', 'info');
            
            fetch('run_migration_api.php?action=test')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addOutput(data.output, 'success');
                    setStatus('✅ 테스트 성공', 'success');
                } else {
                    addOutput(data.error || '테스트 실패', 'error');
                    setStatus('❌ 테스트 실패', 'error');
                }
            })
            .catch(error => {
                addOutput('오류: ' + error.message, 'error');
                setStatus('❌ 실행 오류', 'error');
            });
        }
    </script>
</body>
</html>

<?php
// POST 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'run_migration') {
    header('Content-Type: application/json');
    
    $migration = $_POST['migration'] ?? '';
    $output = '';
    $success = false;
    
    try {
        if ($migration === '005' || $migration === 'all') {
            ob_start();
            include(__DIR__ . '/../../db/migrations/run_005_migration.php');
            $output .= ob_get_clean();
        }
        
        if ($migration === '006' || $migration === 'all') {
            ob_start();
            include(__DIR__ . '/../../db/migrations/run_006_migration.php');
            $output .= ob_get_clean();
        }
        
        $success = true;
    } catch (Exception $e) {
        $output .= "\n오류: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
        $success = false;
    }
    
    echo json_encode([
        'success' => $success,
        'output' => $output
    ]);
    exit;
}

// GET 요청 처리 (테스트)
if (isset($_GET['action']) && $_GET['action'] === 'test') {
    header('Content-Type: application/json');
    
    $output = '';
    $success = false;
    
    try {
        ob_start();
        include(__DIR__ . '/../../api/scheduler/test_heartbeat.php');
        $output = ob_get_clean();
        $success = true;
    } catch (Exception $e) {
        $output = "오류: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__;
        $success = false;
    }
    
    echo json_encode([
        'success' => $success,
        'output' => $output
    ]);
    exit;
}
?>

