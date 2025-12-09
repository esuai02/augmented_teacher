<?php
/**
 * 학습 진도 관련 테이블 생성 스크립트
 * 실행 방법: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/setup_learning_tables.php
 */

session_start();
require_once 'config.php';

// 관리자 권한 체크 (선택사항)
// if (!isset($_SESSION['user_id'])) {
//     die('로그인이 필요합니다.');
// }

try {
    // MathKing DB 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h2>학습 진도 테이블 생성 시작...</h2><br>";

    // 1. 학습 진도 테이블
    $sql1 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_progress` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
        `math_level` VARCHAR(50) DEFAULT NULL COMMENT '현재 수학 실력 수준',
        `concept_level` VARCHAR(100) DEFAULT NULL COMMENT '개념 학습 레벨',
        `concept_progress` INT(3) DEFAULT 0 COMMENT '개념 진도율 (0-100)',
        `concept_details` TEXT DEFAULT NULL COMMENT '개념 학습 상세 내용',
        `advanced_level` VARCHAR(100) DEFAULT NULL COMMENT '심화 학습 레벨',
        `advanced_progress` INT(3) DEFAULT 0 COMMENT '심화 진도율 (0-100)',
        `advanced_details` TEXT DEFAULT NULL COMMENT '심화 학습 상세 내용',
        `notes` TEXT DEFAULT NULL COMMENT '비고 및 특이사항',
        `weekly_hours` DECIMAL(5,2) DEFAULT NULL COMMENT '주당 학습 시간',
        `academy_experience` TEXT DEFAULT NULL COMMENT '학원 경험',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42g_leapro_use_ix` (`userid`),
        KEY `mdl_alt42g_leapro_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 진도 정보'";

    if ($pdo->exec($sql1) !== false) {
        echo "✅ mdl_alt42g_learning_progress 테이블 생성 완료<br>";
    }

    // 2. 학습 스타일 테이블
    $sql2 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_style` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
        `problem_preference` VARCHAR(50) DEFAULT NULL COMMENT '문제 선호도',
        `exam_style` VARCHAR(50) DEFAULT NULL COMMENT '시험 대비 스타일',
        `math_confidence` INT(2) DEFAULT 5 COMMENT '수학 자신감 (1-10)',
        `parent_style` VARCHAR(50) DEFAULT NULL COMMENT '부모님 관여도',
        `stress_level` VARCHAR(50) DEFAULT NULL COMMENT '스트레스 수준',
        `feedback_preference` VARCHAR(50) DEFAULT NULL COMMENT '피드백 선호도',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `mdl_alt42g_leasty_use_uix` (`userid`),
        KEY `mdl_alt42g_leasty_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 스타일 정보'";

    if ($pdo->exec($sql2) !== false) {
        echo "✅ mdl_alt42g_learning_style 테이블 생성 완료<br>";
    }

    // 3. 학습 목표 테이블
    $sql3 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_goals` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
        `short_term_goal` TEXT DEFAULT NULL COMMENT '단기 목표 (1-3개월)',
        `mid_term_goal` TEXT DEFAULT NULL COMMENT '중기 목표 (6개월)',
        `long_term_goal` TEXT DEFAULT NULL COMMENT '장기 목표 (1년)',
        `goal_note` TEXT DEFAULT NULL COMMENT '목표 관련 메모',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `mdl_alt42g_leagoa_use_uix` (`userid`),
        KEY `mdl_alt42g_leagoa_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 목표 정보'";

    if ($pdo->exec($sql3) !== false) {
        echo "✅ mdl_alt42g_learning_goals 테이블 생성 완료<br>";
    }

    // 4. 온보딩 완료 상태 테이블
    $sql4 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_onboarding_status` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
        `basic_info_completed` TINYINT(1) DEFAULT 0 COMMENT '기본정보 완료',
        `learning_progress_completed` TINYINT(1) DEFAULT 0 COMMENT '학습진도 완료',
        `learning_style_completed` TINYINT(1) DEFAULT 0 COMMENT '학습스타일 완료',
        `learning_method_completed` TINYINT(1) DEFAULT 0 COMMENT '학습방식 완료',
        `learning_goals_completed` TINYINT(1) DEFAULT 0 COMMENT '학습목표 완료',
        `additional_info_completed` TINYINT(1) DEFAULT 0 COMMENT '추가정보 완료',
        `data_consent` TINYINT(1) DEFAULT 0 COMMENT '개인정보 동의',
        `overall_completed` TINYINT(1) DEFAULT 0 COMMENT '전체 완료',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `mdl_alt42g_onbsta_use_uix` (`userid`),
        KEY `mdl_alt42g_onbsta_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='온보딩 완료 상태'";

    if ($pdo->exec($sql4) !== false) {
        echo "✅ mdl_alt42g_onboarding_status 테이블 생성 완료<br>";
    }

    // 5. 학습 방식 테이블
    $sql5 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_learning_method` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
        `parent_style` VARCHAR(50) DEFAULT NULL COMMENT '부모님 관여도',
        `stress_level` VARCHAR(50) DEFAULT NULL COMMENT '스트레스 수준',
        `feedback_preference` VARCHAR(50) DEFAULT NULL COMMENT '피드백 선호도',
        `study_environment` VARCHAR(100) DEFAULT NULL COMMENT '학습 환경',
        `study_time_preference` VARCHAR(50) DEFAULT NULL COMMENT '선호 학습 시간대',
        `concentration_duration` INT(3) DEFAULT NULL COMMENT '집중 가능 시간(분)',
        `break_frequency` VARCHAR(50) DEFAULT NULL COMMENT '휴식 빈도',
        `motivation_type` VARCHAR(50) DEFAULT NULL COMMENT '동기부여 유형',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `mdl_alt42g_leamet_use_uix` (`userid`),
        KEY `mdl_alt42g_leamet_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 학습 방식 정보'";

    if ($pdo->exec($sql5) !== false) {
        echo "✅ mdl_alt42g_learning_method 테이블 생성 완료<br>";
    }

    // 6. 추가 정보 테이블
    $sql6 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_additional_info` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '사용자 ID (mdl_user.id 참조)',
        `weekly_hours` DECIMAL(5,2) DEFAULT NULL COMMENT '주당 학습 시간',
        `academy_experience` VARCHAR(100) DEFAULT NULL COMMENT '학원 경험',
        `previous_math_score` VARCHAR(50) DEFAULT NULL COMMENT '이전 수학 성적',
        `target_score` VARCHAR(50) DEFAULT NULL COMMENT '목표 성적',
        `health_issues` TEXT DEFAULT NULL COMMENT '건강 특이사항',
        `learning_difficulties` TEXT DEFAULT NULL COMMENT '학습 어려움',
        `special_requests` TEXT DEFAULT NULL COMMENT '특별 요청사항',
        `parent_feedback` TEXT DEFAULT NULL COMMENT '부모님 의견',
        `favorite_food` TEXT DEFAULT NULL COMMENT '좋아하는 음식',
        `favorite_fruit` TEXT DEFAULT NULL COMMENT '좋아하는 과일',
        `favorite_snack` TEXT DEFAULT NULL COMMENT '좋아하는 과자',
        `hobbies_interests` TEXT DEFAULT NULL COMMENT '취미/관심분야',
        `fandom_yn` TINYINT(1) DEFAULT 0 COMMENT '덕질 여부',
        `data_consent` TINYINT(1) DEFAULT 0 COMMENT '개인정보 수집 동의',
        `marketing_consent` TINYINT(1) DEFAULT 0 COMMENT '마케팅 정보 수신 동의',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `mdl_alt42g_addinf_use_uix` (`userid`),
        KEY `mdl_alt42g_addinf_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='학생 추가 정보'";

    if ($pdo->exec($sql6) !== false) {
        echo "✅ mdl_alt42g_additional_info 테이블 생성 완료<br>";
    }

    // 스키마 마이그레이션: 신규 컬럼 추가 (INFORMATION_SCHEMA로 존재 확인 후 추가)
    $dbName = MATHKING_DB_NAME;
    $columnsToAdd = [
        'favorite_food' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `favorite_food` TEXT DEFAULT NULL COMMENT '좋아하는 음식'",
        'favorite_fruit' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `favorite_fruit` TEXT DEFAULT NULL COMMENT '좋아하는 과일'",
        'favorite_snack' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `favorite_snack` TEXT DEFAULT NULL COMMENT '좋아하는 과자'",
        'hobbies_interests' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `hobbies_interests` TEXT DEFAULT NULL COMMENT '취미/관심분야'",
        'fandom_yn' => "ALTER TABLE `mdl_alt42g_additional_info` ADD COLUMN `fandom_yn` TINYINT(1) DEFAULT 0 COMMENT '덕질 여부'"
    ];

    foreach ($columnsToAdd as $col => $alterSQL) {
        try {
            $checkSQL = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'mdl_alt42g_additional_info' AND COLUMN_NAME = :col";
            $chk = $pdo->prepare($checkSQL);
            $chk->execute([':schema' => $dbName, ':col' => $col]);
            $exists = (int)$chk->fetchColumn() > 0;
            if (!$exists) {
                $pdo->exec($alterSQL);
                echo "✅ 컬럼 추가됨: $col<br>";
            } else {
                echo "ℹ️ 이미 존재: $col<br>";
            }
        } catch (Exception $e) {
            echo "⚠️ 컬럼 추가 실패: $col - " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }

    // 온보딩 상태 테이블 마이그레이션 (누락 컬럼 추가)
    $statusCols = [
        'basic_info_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `basic_info_completed` TINYINT(1) DEFAULT 0 COMMENT '기본정보 완료'",
        'learning_progress_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_progress_completed` TINYINT(1) DEFAULT 0 COMMENT '학습진도 완료'",
        'learning_style_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_style_completed` TINYINT(1) DEFAULT 0 COMMENT '학습스타일 완료'",
        'learning_method_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_method_completed` TINYINT(1) DEFAULT 0 COMMENT '학습방식 완료'",
        'learning_goals_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `learning_goals_completed` TINYINT(1) DEFAULT 0 COMMENT '학습목표 완료'",
        'additional_info_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `additional_info_completed` TINYINT(1) DEFAULT 0 COMMENT '추가정보 완료'",
        'data_consent' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `data_consent` TINYINT(1) DEFAULT 0 COMMENT '개인정보 동의'",
        'overall_completed' => "ALTER TABLE `mdl_alt42g_onboarding_status` ADD COLUMN `overall_completed` TINYINT(1) DEFAULT 0 COMMENT '전체 완료'",
    ];
    foreach ($statusCols as $col => $alterSQL) {
        try {
            $checkSQL = "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :schema AND TABLE_NAME = 'mdl_alt42g_onboarding_status' AND COLUMN_NAME = :col";
            $chk = $pdo->prepare($checkSQL);
            $chk->execute([':schema' => $dbName, ':col' => $col]);
            $exists = (int)$chk->fetchColumn() > 0;
            if (!$exists) {
                $pdo->exec($alterSQL);
                echo "✅ 온보딩 상태 컬럼 추가됨: $col<br>";
            } else {
                echo "ℹ️ 온보딩 상태 컬럼 이미 존재: $col<br>";
            }
        } catch (Exception $e) {
            echo "⚠️ 온보딩 상태 컬럼 추가 실패: $col - " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }

    echo "<br><h3>✨ 모든 테이블이 성공적으로 생성되었습니다!</h3>";

    // 생성된 테이블 확인
    echo "<br><h3>생성된 테이블 목록:</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'mdl_alt42g_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    echo "<h3 style='color: red;'>❌ 오류 발생:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    error_log("Table creation error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학습 테이블 생성</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        h2 {
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        h3 {
            color: #4CAF50;
        }
        pre {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        ul {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        li {
            margin: 10px 0;
            color: #555;
        }
        a {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <a href="student_onboarding.php">온보딩 페이지로 돌아가기</a>
</body>
</html>