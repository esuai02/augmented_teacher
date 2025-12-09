<?php
/**
 * ExamFocus 독립 실행 테스트 (Moodle 환경 없이)
 * 
 * @package    local_examfocus
 * @copyright  2025 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// 에러 표시
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ExamFocus 독립 실행 테스트</h1>";
echo "<hr>";

// 1. 기본 데이터베이스 연결 테스트
echo "<h2>1. MathKing DB 연결 테스트</h2>";

// config.php 직접 포함
define('MATHKING_DB_HOST', '58.180.27.46');
define('MATHKING_DB_NAME', 'mathking');
define('MATHKING_DB_USER', 'moodle');
define('MATHKING_DB_PASS', '@MCtrigd7128');

try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    echo "✅ MathKing DB 연결 성공!<br>";
    
    // 테이블 확인
    $tables = ['mdl_user', 'mdl_abessi_schedule', 'mdl_abessi_missionlog'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        $result = $stmt->fetch();
        echo "✅ $table: {$result['cnt']} 레코드<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ MathKing DB 연결 실패: " . $e->getMessage() . "<br>";
}

// 2. Alt42t DB 연결 테스트 (여러 방법)
echo "<h2>2. Alt42t DB 연결 테스트</h2>";

$connection_methods = [
    ['host' => 'localhost', 'desc' => 'localhost'],
    ['host' => '127.0.0.1', 'desc' => '127.0.0.1 (TCP/IP)'],
    ['host' => '58.180.27.46', 'desc' => '외부 IP'],
];

$alt42t_connected = false;
foreach ($connection_methods as $method) {
    try {
        echo "시도: {$method['desc']}... ";
        $dsn = "mysql:host={$method['host']};dbname=alt42t;charset=utf8mb4";
        $alt42t_pdo = new PDO($dsn, 'root', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        
        echo "✅ 성공!<br>";
        $alt42t_connected = true;
        
        // student_exam_settings 확인
        $stmt = $alt42t_pdo->query("SELECT COUNT(*) as cnt FROM student_exam_settings");
        $result = $stmt->fetch();
        echo "student_exam_settings: {$result['cnt']} 레코드<br>";
        break;
        
    } catch (PDOException $e) {
        echo "❌ 실패 - " . $e->getMessage() . "<br>";
    }
}

if (!$alt42t_connected) {
    echo "<p style='color:orange'>⚠️ Alt42t DB 연결 실패. MathKing DB의 스케줄 정보만 사용합니다.</p>";
}

// 3. 샘플 추천 로직 테스트
echo "<h2>3. 추천 로직 테스트 (샘플 데이터)</h2>";

// 테스트용 가상 데이터
$test_cases = [
    ['days' => 35, 'desc' => 'D-35 (추천 없음)'],
    ['days' => 30, 'desc' => 'D-30 (오답 회독 추천)'],
    ['days' => 15, 'desc' => 'D-15 (오답 회독 유지)'],
    ['days' => 7, 'desc' => 'D-7 (개념 요약 추천)'],
    ['days' => 3, 'desc' => 'D-3 (개념 요약 유지)'],
];

foreach ($test_cases as $case) {
    $recommendation = get_mock_recommendation($case['days']);
    echo "<div style='margin: 10px 0; padding: 10px; border: 1px solid #ddd;'>";
    echo "<strong>{$case['desc']}</strong><br>";
    
    if ($recommendation) {
        echo "➡️ 추천 모드: {$recommendation['mode']}<br>";
        echo "➡️ 메시지: {$recommendation['message']}<br>";
        echo "➡️ 우선순위: {$recommendation['priority']}<br>";
    } else {
        echo "➡️ 추천 없음<br>";
    }
    echo "</div>";
}

// 4. 실제 사용자 테스트 (ID 2)
echo "<h2>4. 실제 사용자 테스트 (User ID: 2)</h2>";

if (isset($pdo)) {
    try {
        // 사용자 정보 조회
        $stmt = $pdo->prepare("SELECT id, username, firstname, lastname FROM mdl_user WHERE id = 2");
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user) {
            echo "사용자: {$user['firstname']} {$user['lastname']} ({$user['username']})<br>";
            
            // 최근 활동 조회
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as cnt, MAX(timecreated) as last_activity 
                FROM mdl_abessi_missionlog 
                WHERE userid = 2
            ");
            $stmt->execute();
            $activity = $stmt->fetch();
            
            echo "총 활동: {$activity['cnt']}회<br>";
            if ($activity['last_activity']) {
                echo "마지막 활동: " . date('Y-m-d H:i:s', $activity['last_activity']) . "<br>";
            }
            
            // 스케줄 확인
            $stmt = $pdo->prepare("
                SELECT * FROM mdl_abessi_schedule 
                WHERE userid = 2 
                ORDER BY timemodified DESC 
                LIMIT 1
            ");
            $stmt->execute();
            $schedule = $stmt->fetch();
            
            if ($schedule) {
                echo "스케줄 데이터 존재: ";
                if ($schedule['schedule_data']) {
                    $data = json_decode($schedule['schedule_data'], true);
                    echo "<pre>" . print_r($data, true) . "</pre>";
                } else {
                    echo "비어있음<br>";
                }
            }
        }
        
    } catch (PDOException $e) {
        echo "❌ 사용자 조회 실패: " . $e->getMessage() . "<br>";
    }
}

// 5. 설치 가이드
echo "<h2>5. 설치 가이드</h2>";
echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
echo "<h3>PHP 버전 문제 해결:</h3>";
echo "현재 PHP 7.1.9 → 7.4 이상 필요<br>";
echo "임시 해결책: version.php의 requires를 2018051700 (Moodle 3.5)으로 변경<br><br>";

echo "<h3>Alt42t DB 연결 실패 해결:</h3>";
echo "1. MySQL 서버가 실행 중인지 확인: <code>ps aux | grep mysql</code><br>";
echo "2. 소켓 위치 확인: <code>mysql_config --socket</code><br>";
echo "3. TCP/IP 연결 사용: host=127.0.0.1 대신 localhost<br><br>";

echo "<h3>권한 설정:</h3>";
echo "<code>chmod -R 755 " . __DIR__ . "</code><br>";
echo "<code>chown -R moodle:users " . __DIR__ . "</code><br>";
echo "</div>";

// 헬퍼 함수
function get_mock_recommendation($days_until) {
    if ($days_until <= 7 && $days_until > 0) {
        return [
            'mode' => 'concept_summary',
            'message' => '시험 D-7! 개념요약과 대표유형에 집중하세요.',
            'priority' => 'high'
        ];
    } elseif ($days_until <= 30 && $days_until > 7) {
        return [
            'mode' => 'review_errors',
            'message' => '시험 D-30! 오답 회독 모드를 시작하세요.',
            'priority' => 'medium'
        ];
    }
    return null;
}

echo "<hr>";
echo "<p>테스트 완료: " . date('Y-m-d H:i:s') . "</p>";