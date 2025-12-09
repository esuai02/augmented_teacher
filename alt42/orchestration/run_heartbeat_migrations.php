<?php
/**
 * Heartbeat 마이그레이션 직접 실행 스크립트
 * 서버에서 웹 브라우저로 접근하여 실행
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/run_heartbeat_migrations.php
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

// 실행 모드 확인
$action = $_GET['action'] ?? 'show';
$migration = $_GET['migration'] ?? '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Heartbeat 마이그레이션 실행</title>
    <style>
        body { font-family: 'Malgun Gothic', Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .button { background: #3498db; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; margin: 5px; font-size: 14px; text-decoration: none; display: inline-block; }
        .button:hover { background: #2980b9; }
        .button-success { background: #27ae60; }
        .button-success:hover { background: #229954; }
        .button-danger { background: #e74c3c; }
        .button-danger:hover { background: #c0392b; }
        .output { background: #1e1e1e; color: #0f0; padding: 20px; border-radius: 4px; font-family: 'Courier New', monospace; font-size: 13px; margin-top: 20px; max-height: 600px; overflow-y: auto; white-space: pre-wrap; line-height: 1.6; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .info { color: #0ff; }
        .warning { color: #ff0; }
        .status { padding: 15px; margin: 15px 0; border-radius: 4px; font-weight: bold; }
        .status.success { background: #d4edda; color: #155724; border: 2px solid #c3e6cb; }
        .status.error { background: #f8d7da; color: #721c24; border: 2px solid #f5c6cb; }
        .status.info { background: #d1ecf1; color: #0c5460; border: 2px solid #bee5eb; }
        .button-group { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Heartbeat 마이그레이션 실행</h1>
        
        <?php if ($action === 'show'): ?>
            <div class="button-group">
                <a href="?action=run&migration=005" class="button">마이그레이션 005 실행</a>
                <a href="?action=run&migration=006" class="button">마이그레이션 006 실행</a>
                <a href="?action=run&migration=all" class="button button-success">전체 마이그레이션 실행</a>
                <a href="?action=test" class="button">테스트 실행</a>
            </div>
            
            <div class="status info">
                ℹ️ 마이그레이션을 실행하려면 위 버튼을 클릭하세요.
            </div>
            
        <?php elseif ($action === 'run'): ?>
            <div class="button-group">
                <a href="?" class="button">← 돌아가기</a>
            </div>
            
            <div class="status info">
                ⏳ 마이그레이션 실행 중...
            </div>
            
            <div class="output">
<?php
            ob_start();
            
            try {
                if ($migration === '005' || $migration === 'all') {
                    echo "=== 마이그레이션 005 실행 ===\n\n";
                    include(__DIR__ . '/db/migrations/run_005_migration.php');
                    echo "\n\n";
                }
                
                if ($migration === '006' || $migration === 'all') {
                    echo "=== 마이그레이션 006 실행 ===\n\n";
                    include(__DIR__ . '/db/migrations/run_006_migration.php');
                    echo "\n\n";
                }
                
                $output = ob_get_clean();
                echo htmlspecialchars($output);
                
                echo "\n\n✅ 마이그레이션 완료!";
                echo "\n<a href='?' style='color: #0f0;'>← 돌아가기</a>";
                
            } catch (Exception $e) {
                $output = ob_get_clean();
                echo htmlspecialchars($output);
                echo "\n\n❌ 오류 발생: " . htmlspecialchars($e->getMessage()) . " at " . __FILE__ . ":" . __LINE__;
                echo "\n<a href='?' style='color: #f00;'>← 돌아가기</a>";
            }
?>
            </div>
            
        <?php elseif ($action === 'test'): ?>
            <div class="button-group">
                <a href="?" class="button">← 돌아가기</a>
            </div>
            
            <div class="status info">
                ⏳ 테스트 실행 중...
            </div>
            
            <div class="output">
<?php
            ob_start();
            
            try {
                include(__DIR__ . '/api/scheduler/test_heartbeat.php');
                $output = ob_get_clean();
                echo htmlspecialchars($output);
                
                echo "\n\n✅ 테스트 완료!";
                echo "\n<a href='?' style='color: #0f0;'>← 돌아가기</a>";
                
            } catch (Exception $e) {
                $output = ob_get_clean();
                echo htmlspecialchars($output);
                echo "\n\n❌ 테스트 오류: " . htmlspecialchars($e->getMessage()) . " at " . __FILE__ . ":" . __LINE__;
                echo "\n<a href='?' style='color: #f00;'>← 돌아가기</a>";
            }
?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

