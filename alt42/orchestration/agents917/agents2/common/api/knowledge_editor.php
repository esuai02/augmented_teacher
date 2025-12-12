<?php
/**
 * Knowledge File Editor API
 * File: api/knowledge_editor.php:1
 * Handles reading and writing knowledge markdown files
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=UTF-8');

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';
$filename = $input['filename'] ?? $_POST['filename'] ?? $_GET['filename'] ?? '';

// Base directory for knowledge files
$baseDir = dirname(__DIR__) . '/agents/agent03_goals_analysis/';

// Response template
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'error' => null
];

// Validate filename (security check)
if (!empty($filename)) {
    // Only allow specific filenames
    $allowedFiles = [
        '의사결정 지식.md',
        '분기목표 지식.md',
        '주간목표 지식.md',
        '오늘목표 지식.md',
        '포모도르 지식.md',
        '커리큘럼 지식.md'
    ];

    if (!in_array($filename, $allowedFiles)) {
        $response['error'] = "Invalid filename. File: api/knowledge_editor.php:42";
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

try {
    switch ($action) {
        case 'read':
            if (empty($filename)) {
                throw new Exception("Filename is required. File: api/knowledge_editor.php:52");
            }

            $filepath = $baseDir . $filename;

            if (!file_exists($filepath)) {
                throw new Exception("File not found: {$filename}. File: api/knowledge_editor.php:58");
            }

            $content = file_get_contents($filepath);

            $response = [
                'success' => true,
                'message' => 'File loaded successfully',
                'data' => [
                    'filename' => $filename,
                    'content' => $content,
                    'filepath' => $filepath
                ]
            ];
            break;

        case 'write':
            if (empty($filename)) {
                throw new Exception("Filename is required. File: api/knowledge_editor.php:76");
            }

            $content = $input['content'] ?? '';
            $filepath = $baseDir . $filename;

            // Backup existing file
            if (file_exists($filepath)) {
                $backupPath = $baseDir . 'backups/';
                if (!is_dir($backupPath)) {
                    mkdir($backupPath, 0755, true);
                }
                $backupFile = $backupPath . date('Y-m-d_His') . '_' . $filename;
                copy($filepath, $backupFile);
            }

            // Write new content
            $result = file_put_contents($filepath, $content);

            if ($result === false) {
                throw new Exception("Failed to write file. File: api/knowledge_editor.php:95");
            }

            $response = [
                'success' => true,
                'message' => 'File saved successfully',
                'data' => [
                    'filename' => $filename,
                    'bytes_written' => $result,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            break;

        case 'list':
            $files = [];
            $allowedFiles = [
                '의사결정 지식.md',
                '분기목표 지식.md',
                '주간목표 지식.md',
                '오늘목표 지식.md',
                '포모도르 지식.md',
                '커리큘럼 지식.md'
            ];

            foreach ($allowedFiles as $file) {
                $filepath = $baseDir . $file;
                if (file_exists($filepath)) {
                    $files[] = [
                        'filename' => $file,
                        'size' => filesize($filepath),
                        'modified' => date('Y-m-d H:i:s', filemtime($filepath))
                    ];
                }
            }

            $response = [
                'success' => true,
                'message' => 'File list retrieved',
                'data' => $files
            ];
            break;

        default:
            throw new Exception("Invalid action: {$action}. File: api/knowledge_editor.php:140");
    }
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
    error_log("Knowledge Editor Error: " . $e->getMessage());
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;
?>
