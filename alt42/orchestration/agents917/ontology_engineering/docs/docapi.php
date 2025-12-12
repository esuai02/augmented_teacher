<?php
/**
 * 온톨로지 문서 관리 API
 * File: ontology_engineering/docs/docapi.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data ?? '';

if ($role !== 'admin' && $role !== 'manager') {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'error' => 'Access denied. File: docapi.php:15'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$filepath = $_GET['file'] ?? $_POST['file'] ?? '';
$content = $_POST['content'] ?? '';

// 프로젝트 파일 경로 설정 (현재 워크스페이스 기준)
// __DIR__ = C:\1 Project\augmented_teacher\alt42/orchestration/agents/ontology_engineering/docs/
// agents/ 폴더까지 올라가기
$projectBaseDir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR; // agents/ 폴더

$response = ['success' => false, 'message' => '', 'data' => null, 'error' => null];

function validatePath($path, $baseDir) {
    // 경로 정리 (앞뒤 공백 제거)
    $path = trim($path);
    
    // Windows/Unix 경로 통일 (슬래시로 통일)
    $path = str_replace('\\', '/', $path);
    $baseDir = str_replace('\\', '/', rtrim($baseDir, '/\\'));
    
    // 상대 경로를 절대 경로로 변환
    $fullPath = $baseDir . '/' . ltrim($path, '/');
    
    // 경로 정규화 (realpath는 존재하는 경로만 처리)
    $basePath = realpath($baseDir);
    if ($basePath === false) {
        // baseDir이 존재하지 않으면 생성 시도
        if (!is_dir($baseDir)) {
            @mkdir($baseDir, 0755, true);
        }
        $basePath = realpath($baseDir);
        if ($basePath === false) {
            // realpath 실패 시 원본 경로 사용 (Windows에서 공백 포함 경로 문제)
            $basePath = $baseDir;
        }
    }
    
    // Windows 경로 정규화
    $basePath = str_replace('\\', '/', $basePath);
    $fullPath = str_replace('\\', '/', $fullPath);
    
    // 파일이 존재하지 않으면 경로만 검증 (새 파일 생성 가능)
    $fileDir = dirname($fullPath);
    $fileDirReal = realpath($fileDir);
    if ($fileDirReal === false) {
        // 부모 디렉토리가 존재하지 않으면 경로만 검증
        $fileDirReal = $fileDir;
    }
    $fileDirReal = str_replace('\\', '/', $fileDirReal);
    
    // 보안: baseDir 밖으로 나가는 경로 차단
    if (strpos($fileDirReal, $basePath) !== 0) {
        return false;
    }
    
    // .md 파일만 허용
    if (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)) !== 'md') {
        return false;
    }
    
    return $fullPath;
}

try {
    switch ($action) {
        case 'list':
            $files = [];
            $directories = [
                'ontology_engineering/',
                'ontology_engineering/docs/',
                'ontology_engineering/DesigningOfOntology/',
                'agent01_onboarding/ontology/',
                'agent04_inspect_weakpoints/ontology/',
                'agent04_inspect_weakpoints/tasks/',
                'agent22_module_improvement/tasks/',
                'agents/docs/',
            ];

            foreach ($directories as $dir) {
                $fullDir = $projectBaseDir . $dir;
                if (is_dir($fullDir)) {
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($fullDir, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::SELF_FIRST
                    );

                    foreach ($iterator as $file) {
                        if ($file->isFile() && $file->getExtension() === 'md') {
                            $relativePath = str_replace($projectBaseDir, '', $file->getPathname());
                            $files[] = [
                                'name' => $file->getFilename(),
                                'path' => $relativePath,
                                'size' => $file->getSize(),
                                'modified' => date('Y-m-d H:i:s', $file->getMTime())
                            ];
                        }
                    }
                }
            }

            $response = [
                'success' => true,
                'message' => 'File list loaded',
                'data' => ['files' => $files, 'count' => count($files)]
            ];
            break;

        case 'read':
            if (empty($filepath)) {
                throw new Exception("File path is required. File: docapi.php:85");
            }

            $validPath = validatePath($filepath, $projectBaseDir);
            if (!$validPath || !file_exists($validPath)) {
                throw new Exception("File not found: {$filepath}. File: docapi.php:90");
            }

            $content = file_get_contents($validPath);
            if ($content === false) {
                throw new Exception("Failed to read file. File: docapi.php:94");
            }

            $links = [];
            preg_match_all('/\[([^\]]+)\]\(([^\)]+)\)/', $content, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $links[] = ['text' => $match[1], 'path' => $match[2]];
            }

            $response = [
                'success' => true,
                'message' => 'File loaded successfully',
                'data' => [
                    'filepath' => $filepath,
                    'content' => $content,
                    'links' => $links,
                    'size' => strlen($content),
                    'modified' => date('Y-m-d H:i:s', filemtime($validPath))
                ]
            ];
            break;

        case 'write':
            if (empty($filepath)) {
                throw new Exception("File path is required. File: docapi.php:125");
            }

            $validPath = validatePath($filepath, $projectBaseDir);
            if (!$validPath) {
                throw new Exception("Invalid file path: {$filepath}. Base: {$projectBaseDir}. File: docapi.php:130");
            }

            // 디렉토리가 존재하는지 확인하고 없으면 생성
            $fileDir = dirname($validPath);
            if (!is_dir($fileDir)) {
                if (!mkdir($fileDir, 0755, true)) {
                    throw new Exception("Failed to create directory: {$fileDir}. File: docapi.php:137");
                }
            }

            // 디렉토리 쓰기 권한 확인
            if (!is_writable($fileDir)) {
                // 권한 설정 시도
                @chmod($fileDir, 0755);
                if (!is_writable($fileDir)) {
                    throw new Exception("Directory is not writable: {$fileDir}. File: docapi.php:143");
                }
            }

            // 백업 생성
            if (file_exists($validPath)) {
                $backupDir = dirname($validPath) . '/.backups/';
                if (!is_dir($backupDir)) {
                    if (!mkdir($backupDir, 0755, true)) {
                        // 백업 디렉토리 생성 실패는 경고만 하고 계속 진행
                        error_log("Failed to create backup directory: {$backupDir}");
                    }
                }
                if (is_dir($backupDir) && is_writable($backupDir)) {
                    $backupFile = $backupDir . date('Y-m-d_His') . '_' . basename($validPath);
                    @copy($validPath, $backupFile);
                }
            }

            // 파일 쓰기 시도
            $result = @file_put_contents($validPath, $content);
            if ($result === false) {
                $error = error_get_last();
                $errorMsg = $error ? $error['message'] : 'Unknown error';
                throw new Exception("Failed to write file: {$validPath}. Error: {$errorMsg}. File: docapi.php:158");
            }

            // 파일 권한 설정 (읽기/쓰기 가능하도록)
            @chmod($validPath, 0644);

            $response = [
                'success' => true,
                'message' => 'File saved successfully',
                'data' => [
                    'filepath' => $filepath,
                    'bytes_written' => $result,
                    'timestamp' => date('Y-m-d H:i:s')
                ]
            ];
            break;

        default:
            throw new Exception("Invalid action: {$action}. File: docapi.php:200");
    }

} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>

