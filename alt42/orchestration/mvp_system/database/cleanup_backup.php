<?php
// 파일: mvp_system/database/cleanup_backup.php (Line 1)
// Mathking Agentic MVP System - Cleanup Backup Rules
//
// Purpose: Remove temporary backup rules directory
// Usage: Direct browser access (one-time cleanup)

// Server connection
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $PAGE, $CFG;

// Set embedded layout
$PAGE->set_pagelayout('embedded');
$PAGE->set_context(context_system::instance());

// Authentication
ob_start();
require_login();
ob_end_clean();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

// Check if user is NOT student/parent
if ($role === 'student' || $role === 'parent') {
    header("HTTP/1.1 403 Forbidden");
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Access Denied</title></head><body>";
    echo "<h1>Access Denied</h1><p>This page is not accessible to students or parents.</p>";
    echo "<p>Error Location: cleanup_backup.php:line " . __LINE__ . "</p>";
    echo "</body></html>";
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Backup Rules - MVP System</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; max-width: 1200px; margin: 40px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; border-radius: 8px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #2c3e50; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        .info { background: #e8f4f8; border-left: 4px solid #3498db; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; color: #155724; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; color: #856404; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; color: #721c24; }
        pre { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧹 Cleanup Backup Rules Directory</h1>

        <div class="info">
            <strong>User:</strong> <?php echo htmlspecialchars($USER->firstname . ' ' . $USER->lastname); ?> (<?php echo htmlspecialchars($role); ?>)<br>
            <strong>Timestamp:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
            <strong>Purpose:</strong> Remove temporary backup rules directory created during testing
        </div>

<?php

try {
    $backup_dir = __DIR__ . "/rules";

    echo "<h2>📂 Checking Backup Directory</h2>";
    echo "<div class='info'>";
    echo "<strong>Path:</strong> {$backup_dir}<br>";
    echo "<strong>Exists:</strong> " . (file_exists($backup_dir) ? 'Yes' : 'No') . "<br>";

    if (file_exists($backup_dir)) {
        echo "<strong>Is Directory:</strong> " . (is_dir($backup_dir) ? 'Yes' : 'No') . "<br>";
        echo "<strong>Writable:</strong> " . (is_writable($backup_dir) ? 'Yes' : 'No') . "<br>";
        echo "</div>";

        // Count files
        $files = [];
        $dirs = [];

        if (is_dir($backup_dir)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($backup_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files[] = $file->getPathname();
                } elseif ($file->isDir()) {
                    $dirs[] = $file->getPathname();
                }
            }

            echo "<h2>📊 Directory Contents</h2>";
            echo "<div class='info'>";
            echo "<strong>Files:</strong> " . count($files) . "<br>";
            echo "<strong>Directories:</strong> " . count($dirs) . "<br>";
            echo "</div>";

            if (!empty($files)) {
                echo "<h3>Files to be removed:</h3>";
                echo "<pre>";
                foreach ($files as $file) {
                    echo htmlspecialchars($file) . "\n";
                }
                echo "</pre>";
            }

            // Delete files first
            $deleted_files = 0;
            $failed_files = 0;

            foreach ($files as $file) {
                if (@unlink($file)) {
                    $deleted_files++;
                } else {
                    $failed_files++;
                    echo "<div class='error'>❌ Failed to delete: {$file}</div>";
                }
            }

            // Delete directories
            $deleted_dirs = 0;
            $failed_dirs = 0;

            foreach ($dirs as $dir) {
                if (@rmdir($dir)) {
                    $deleted_dirs++;
                } else {
                    $failed_dirs++;
                    echo "<div class='error'>❌ Failed to delete: {$dir}</div>";
                }
            }

            // Delete root backup directory
            if (@rmdir($backup_dir)) {
                echo "<div class='success'>";
                echo "<h2>✅ Cleanup Complete</h2>";
                echo "<p><strong>Files deleted:</strong> {$deleted_files}</p>";
                echo "<p><strong>Directories deleted:</strong> " . ($deleted_dirs + 1) . "</p>";
                echo "<p><strong>Failed:</strong> " . ($failed_files + $failed_dirs) . "</p>";
                echo "</div>";
            } else {
                echo "<div class='error'>";
                echo "<h2>⚠️ Partial Cleanup</h2>";
                echo "<p><strong>Files deleted:</strong> {$deleted_files}</p>";
                echo "<p><strong>Directories deleted:</strong> {$deleted_dirs}</p>";
                echo "<p><strong>Failed to delete root directory:</strong> {$backup_dir}</p>";
                echo "</div>";
            }
        }
    } else {
        echo "</div>";
        echo "<div class='warning'>";
        echo "<h2>ℹ️ Nothing to Clean</h2>";
        echo "<p>Backup directory does not exist. No cleanup needed.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ Cleanup Error</h2>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "</div>";
}

?>

    </div>
</body>
</html>

<?php
/**
 * File Location: mvp_system/database/cleanup_backup.php (Line 1)
 * Purpose: Cleanup temporary backup rules directory
 */
?>
