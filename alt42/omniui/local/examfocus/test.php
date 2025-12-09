<?php
/**
 * ExamFocus 테스트 페이지 - 설치 및 권한 확인
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 표시 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ExamFocus 플러그인 테스트</h1>";
echo "<hr>";

// 1. PHP 버전 체크
echo "<h2>1. PHP 환경 체크</h2>";
echo "PHP 버전: " . PHP_VERSION . "<br>";
echo "최소 요구사항: 7.4 이상<br>";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "✅ PHP 버전 OK<br>";
} else {
    echo "❌ PHP 버전 업그레이드 필요<br>";
}

// 2. 파일 권한 체크
echo "<h2>2. 파일 권한 체크</h2>";
$current_dir = __DIR__;
echo "현재 디렉토리: $current_dir<br>";
echo "권한: " . substr(sprintf('%o', fileperms($current_dir)), -4) . "<br>";
echo "소유자: " . posix_getpwuid(fileowner($current_dir))['name'] . "<br>";
echo "그룹: " . posix_getgrgid(filegroup($current_dir))['name'] . "<br>";

// 3. 중요 파일 존재 확인
echo "<h2>3. 필수 파일 체크</h2>";
$required_files = [
    'version.php',
    'settings.php',
    'db/install.xml',
    'db/access.php',
    'db/tasks.php',
    'db/services.php',
    'classes/service/exam_focus_service.php',
    'lang/ko/local_examfocus.php',
    'lang/en/local_examfocus.php'
];

foreach ($required_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        echo "✅ $file - 존재함<br>";
    } else {
        echo "❌ $file - 없음<br>";
    }
}

// 4. Moodle config.php 찾기
echo "<h2>4. Moodle 설정 파일</h2>";
$config_paths = [
    __DIR__ . '/../../../../config.php',
    __DIR__ . '/../../../config.php',
    __DIR__ . '/../../config.php',
    '/home/moodle/public_html/moodle/config.php',
    $_SERVER['DOCUMENT_ROOT'] . '/moodle/config.php'
];

$config_found = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        echo "✅ Moodle config.php 발견: $path<br>";
        $config_found = true;
        require_once($path);
        break;
    }
}

if (!$config_found) {
    echo "❌ Moodle config.php를 찾을 수 없습니다.<br>";
    echo "검색한 경로:<br>";
    foreach ($config_paths as $path) {
        echo " - $path<br>";
    }
} else {
    // 5. 데이터베이스 연결 테스트
    echo "<h2>5. 데이터베이스 연결</h2>";
    
    if (isset($DB)) {
        echo "✅ Moodle DB 연결 성공<br>";
        
        // 테이블 존재 확인
        $tables = [
            'user' => 'mdl_user',
            'abessi_schedule' => 'mdl_abessi_schedule',
            'abessi_missionlog' => 'mdl_abessi_missionlog'
        ];
        
        foreach ($tables as $name => $table) {
            try {
                $count = $DB->count_records_sql("SELECT COUNT(*) FROM {{$name}}");
                echo "✅ 테이블 {$table}: {$count} 레코드<br>";
            } catch (Exception $e) {
                echo "❌ 테이블 {$table}: 접근 불가<br>";
            }
        }
        
        // ExamFocus 테이블 확인
        echo "<h3>ExamFocus 테이블 상태</h3>";
        $examfocus_tables = [
            'local_examfocus_rules',
            'local_examfocus_events',
            'local_examfocus_user_prefs'
        ];
        
        foreach ($examfocus_tables as $table) {
            if ($DB->get_manager()->table_exists($table)) {
                $count = $DB->count_records($table);
                echo "✅ {$table}: 존재 ({$count} 레코드)<br>";
            } else {
                echo "⚠️ {$table}: 아직 생성되지 않음 (플러그인 설치 필요)<br>";
            }
        }
    }
    
    // 6. Alt42t DB 연결 테스트
    echo "<h2>6. Alt42t DB 연결</h2>";
    try {
        $alt42t_dsn = "mysql:host=localhost;dbname=alt42t;charset=utf8mb4";
        $alt42t_pdo = new PDO($alt42t_dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        echo "✅ Alt42t DB 연결 성공<br>";
        
        // student_exam_settings 테이블 확인
        $stmt = $alt42t_pdo->query("SELECT COUNT(*) FROM student_exam_settings");
        $count = $stmt->fetchColumn();
        echo "✅ student_exam_settings 테이블: {$count} 레코드<br>";
    } catch (PDOException $e) {
        echo "❌ Alt42t DB 연결 실패: " . $e->getMessage() . "<br>";
    }
}

// 7. 웹서버 정보
echo "<h2>7. 웹서버 정보</h2>";
echo "서버 소프트웨어: " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
echo "문서 루트: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "현재 스크립트: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "요청 URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// 8. 권한 수정 제안
echo "<h2>8. 권한 수정 명령어</h2>";
echo "<pre>";
echo "# Linux/Mac 환경에서 실행:\n";
echo "chmod -R 755 " . __DIR__ . "\n";
echo "chmod -R 644 " . __DIR__ . "/*.php\n";
echo "\n# 웹서버 사용자로 소유자 변경 (Ubuntu/Debian):\n";
echo "sudo chown -R www-data:www-data " . __DIR__ . "\n";
echo "\n# 웹서버 사용자로 소유자 변경 (CentOS/RHEL):\n";
echo "sudo chown -R apache:apache " . __DIR__ . "\n";
echo "</pre>";

// 9. 접근 URL
echo "<h2>9. 접근 URL</h2>";
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
            "://$_SERVER[HTTP_HOST]";
$plugin_url = str_replace($_SERVER['DOCUMENT_ROOT'], '', __DIR__);
echo "플러그인 URL: {$base_url}{$plugin_url}/<br>";
echo "인덱스 페이지: <a href='{$base_url}{$plugin_url}/index.php'>{$base_url}{$plugin_url}/index.php</a><br>";

echo "<hr>";
echo "<p>테스트 완료. 위 정보를 확인하여 문제를 해결하세요.</p>";