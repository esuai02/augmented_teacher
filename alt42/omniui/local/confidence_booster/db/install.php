<?php
/**
 * Confidence Booster 플러그인 데이터베이스 설치 스크립트
 * 
 * @package    local_confidence_booster
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * 
 * 사용법: 브라우저에서 직접 실행
 * URL: /moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster/db/install.php
 */

// 설정 파일 로드
require_once('../config.php');

// 관리자 권한 체크 (간단한 보안)
session_start();
if (!isset($_GET['key']) || $_GET['key'] !== 'mathking2024') {
    die('접근 권한이 없습니다. URL에 ?key=mathking2024 를 추가하세요.');
}

// DB 연결
$pdo = get_confidence_db_connection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 설치 시작
echo "<h1>Confidence Booster 데이터베이스 설치</h1>";
echo "<pre>";

$errors = [];
$success = [];

// 1. confidence_notes 테이블 생성
try {
    $sql = "CREATE TABLE IF NOT EXISTS " . MATHKING_DB_PREFIX . "confidence_notes (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL DEFAULT 0,
        courseid BIGINT(10) DEFAULT 0,
        chapter VARCHAR(255) DEFAULT NULL,
        concept_title VARCHAR(500) NOT NULL,
        summary_text TEXT NOT NULL,
        ai_feedback TEXT DEFAULT NULL,
        quality_score DECIMAL(3,2) DEFAULT NULL,
        timecreated BIGINT(10) NOT NULL DEFAULT 0,
        timemodified BIGINT(10) DEFAULT 0,
        PRIMARY KEY (id),
        KEY userid_idx (userid),
        KEY timecreated_idx (timecreated),
        KEY chapter_idx (chapter)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $success[] = "✅ confidence_notes 테이블 생성 완료";
} catch (PDOException $e) {
    $errors[] = "❌ confidence_notes 테이블 생성 실패: " . $e->getMessage();
}

// 2. confidence_errors 테이블 생성
try {
    $sql = "CREATE TABLE IF NOT EXISTS " . MATHKING_DB_PREFIX . "confidence_errors (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL DEFAULT 0,
        questionid BIGINT(10) NOT NULL DEFAULT 0,
        error_type VARCHAR(50) NOT NULL,
        error_memo TEXT DEFAULT NULL,
        difficulty_level VARCHAR(20) DEFAULT NULL,
        resolved TINYINT(1) DEFAULT 0,
        retry_count INT(5) DEFAULT 0,
        timecreated BIGINT(10) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY userid_errortype_idx (userid, error_type),
        KEY resolved_idx (resolved),
        KEY questionid_idx (questionid)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $success[] = "✅ confidence_errors 테이블 생성 완료";
} catch (PDOException $e) {
    $errors[] = "❌ confidence_errors 테이블 생성 실패: " . $e->getMessage();
}

// 3. confidence_challenges 테이블 생성
try {
    $sql = "CREATE TABLE IF NOT EXISTS " . MATHKING_DB_PREFIX . "confidence_challenges (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL DEFAULT 0,
        week_number INT(3) NOT NULL,
        year INT(4) NOT NULL,
        challenge_level VARCHAR(20) DEFAULT 'medium',
        questions_json TEXT DEFAULT NULL,
        attempted TINYINT(1) DEFAULT 0,
        success_rate DECIMAL(5,2) DEFAULT NULL,
        completion_time INT(10) DEFAULT NULL,
        badge_earned VARCHAR(50) DEFAULT NULL,
        timecreated BIGINT(10) NOT NULL DEFAULT 0,
        timecompleted BIGINT(10) DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY userid_week_year_idx (userid, week_number, year),
        KEY attempted_idx (attempted)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $success[] = "✅ confidence_challenges 테이블 생성 완료";
} catch (PDOException $e) {
    $errors[] = "❌ confidence_challenges 테이블 생성 실패: " . $e->getMessage();
}

// 4. confidence_metrics 테이블 생성
try {
    $sql = "CREATE TABLE IF NOT EXISTS " . MATHKING_DB_PREFIX . "confidence_metrics (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL DEFAULT 0,
        metric_date DATE NOT NULL,
        self_confidence INT(3) DEFAULT NULL,
        actual_performance DECIMAL(5,2) DEFAULT NULL,
        summary_count INT(5) DEFAULT 0,
        error_classified_count INT(5) DEFAULT 0,
        challenge_attempted TINYINT(1) DEFAULT 0,
        timecreated BIGINT(10) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY userid_date_idx (userid, metric_date),
        KEY metric_date_idx (metric_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $success[] = "✅ confidence_metrics 테이블 생성 완료";
} catch (PDOException $e) {
    $errors[] = "❌ confidence_metrics 테이블 생성 실패: " . $e->getMessage();
}

// 5. confidence_feedback 테이블 생성
try {
    $sql = "CREATE TABLE IF NOT EXISTS " . MATHKING_DB_PREFIX . "confidence_feedback (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        studentid BIGINT(10) NOT NULL DEFAULT 0,
        teacherid BIGINT(10) NOT NULL DEFAULT 0,
        target_type VARCHAR(50) NOT NULL,
        target_id BIGINT(10) NOT NULL DEFAULT 0,
        feedback_text TEXT NOT NULL,
        resources_json TEXT DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        timecreated BIGINT(10) NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        KEY studentid_isread_idx (studentid, is_read),
        KEY target_type_id_idx (target_type, target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    $success[] = "✅ confidence_feedback 테이블 생성 완료";
} catch (PDOException $e) {
    $errors[] = "❌ confidence_feedback 테이블 생성 실패: " . $e->getMessage();
}

// 6. 테스트 데이터 삽입 (선택사항)
if (isset($_GET['test']) && $_GET['test'] === '1') {
    echo "\n<strong>테스트 데이터 삽입 중...</strong>\n";
    
    try {
        // 테스트 사용자 찾기 (이현선 학생)
        $sql = "SELECT id FROM " . MATHKING_DB_PREFIX . "user 
                WHERE firstname LIKE '%현선%' OR lastname LIKE '%이%' 
                LIMIT 1";
        $stmt = $pdo->query($sql);
        $test_user = $stmt->fetch();
        
        if ($test_user) {
            $userid = $test_user['id'];
            
            // 샘플 요약 데이터
            $sql = "INSERT INTO " . MATHKING_DB_PREFIX . "confidence_notes 
                    (userid, concept_title, summary_text, ai_feedback, quality_score, timecreated) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userid,
                '미분의 정의',
                '미분은 함수의 순간변화율을 나타내는 개념입니다. 극한을 이용하여 정의되며, 기하학적으로는 접선의 기울기를 의미합니다.',
                '좋은 요약입니다! 핵심 개념을 잘 정리했네요. 다음에는 미분의 실제 활용 예시도 추가해보세요.',
                4.5,
                time()
            ]);
            $success[] = "✅ 테스트 요약 데이터 삽입 완료";
            
            // 샘플 오답 데이터
            $sql = "INSERT INTO " . MATHKING_DB_PREFIX . "confidence_errors 
                    (userid, questionid, error_type, error_memo, difficulty_level, timecreated) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userid,
                1001,
                'calculation',
                '부호 실수로 인한 계산 오류',
                'medium',
                time()
            ]);
            $success[] = "✅ 테스트 오답 데이터 삽입 완료";
            
            // 샘플 메트릭 데이터
            $sql = "INSERT INTO " . MATHKING_DB_PREFIX . "confidence_metrics 
                    (userid, metric_date, self_confidence, summary_count, error_classified_count, timecreated) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $userid,
                date('Y-m-d'),
                75,
                1,
                1,
                time()
            ]);
            $success[] = "✅ 테스트 메트릭 데이터 삽입 완료";
            
        } else {
            echo "⚠️ 테스트 사용자를 찾을 수 없습니다.\n";
        }
    } catch (PDOException $e) {
        $errors[] = "❌ 테스트 데이터 삽입 실패: " . $e->getMessage();
    }
}

// 결과 출력
echo "\n<strong>설치 결과:</strong>\n";
echo "=====================================\n";

if (!empty($success)) {
    echo "<span style='color: green;'>성공:\n";
    foreach ($success as $msg) {
        echo "  " . $msg . "\n";
    }
    echo "</span>";
}

if (!empty($errors)) {
    echo "<span style='color: red;'>오류:\n";
    foreach ($errors as $msg) {
        echo "  " . $msg . "\n";
    }
    echo "</span>";
}

if (empty($errors)) {
    echo "\n<strong style='color: green;'>✨ Confidence Booster 설치가 완료되었습니다!</strong>\n";
    echo "\n다음 URL로 접속하세요:\n";
    echo "<a href='/moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster/'>Confidence Booster 대시보드</a>\n";
} else {
    echo "\n<strong style='color: red;'>⚠️ 설치 중 일부 오류가 발생했습니다.</strong>\n";
    echo "오류를 확인하고 다시 시도해주세요.\n";
}

echo "</pre>";

// 로그 기록
confidence_log('Database installation executed', 'info', [
    'success_count' => count($success),
    'error_count' => count($errors)
]);
?>