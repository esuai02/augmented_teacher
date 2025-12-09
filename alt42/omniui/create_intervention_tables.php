<?php
/**
 * AI 시험대비 시스템 테이블 생성 스크립트
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/create_intervention_tables.php
 */

session_start();
require_once 'config.php';

try {
    // MathKing DB 연결
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "<h2>AI 시험대비 시스템 테이블 생성 시작...</h2><br>";

    // 1. AI 진단 리포트 테이블
    $sql1 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_intervention_reports` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '학생 ID',
        `exam_date` DATE DEFAULT NULL COMMENT '시험 날짜',
        `current_score` INT(3) DEFAULT NULL COMMENT '현재 점수',
        `target_score` INT(3) DEFAULT NULL COMMENT '목표 점수',
        `exam_type` VARCHAR(50) DEFAULT NULL COMMENT '시험 종류',
        `agent_analysis` JSON DEFAULT NULL COMMENT '21개 에이전트 분석 결과',
        `problem_definition` TEXT DEFAULT NULL COMMENT '문제 정의',
        `intervention_strategy` TEXT DEFAULT NULL COMMENT '개입 전략',
        `confidence_level` INT(2) DEFAULT 5 COMMENT '자신감 수준 (1-10)',
        `stress_level` VARCHAR(20) DEFAULT NULL COMMENT '스트레스 수준',
        `improvement_roadmap` JSON DEFAULT NULL COMMENT '개선 로드맵',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42g_intrep_use_ix` (`userid`),
        KEY `mdl_alt42g_intrep_exa_ix` (`exam_date`),
        KEY `mdl_alt42g_intrep_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 진단 리포트'";

    if ($pdo->exec($sql1) !== false) {
        echo "✅ mdl_alt42g_intervention_reports 테이블 생성 완료<br>";
    }

    // 2. 시험 전략 테이블
    $sql2 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_exam_strategies` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '학생 ID',
        `exam_id` INT(10) DEFAULT NULL COMMENT '시험 ID',
        `d_day` INT(3) DEFAULT NULL COMMENT '시험까지 남은 일수',
        `daily_plan` JSON DEFAULT NULL COMMENT '일별 계획',
        `weak_points` TEXT DEFAULT NULL COMMENT '취약점',
        `focus_areas` TEXT DEFAULT NULL COMMENT '집중 영역',
        `simulation_count` INT(3) DEFAULT 0 COMMENT '실전 시뮬레이션 횟수',
        `achievement_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT '달성률 %',
        `study_checklist` JSON DEFAULT NULL COMMENT '학습 체크리스트',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42g_exastr_use_ix` (`userid`),
        KEY `mdl_alt42g_exastr_exa_ix` (`exam_id`),
        KEY `mdl_alt42g_exastr_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시험 전략'";

    if ($pdo->exec($sql2) !== false) {
        echo "✅ mdl_alt42g_exam_strategies 테이블 생성 완료<br>";
    }

    // 3. 시그니처 루틴 테이블
    $sql3 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_signature_routines` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '학생 ID',
        `routine_name` VARCHAR(200) DEFAULT NULL COMMENT '루틴 이름',
        `routine_steps` JSON DEFAULT NULL COMMENT '루틴 단계들',
        `daily_duration` INT(3) DEFAULT 120 COMMENT '일일 소요시간(분)',
        `completion_count` INT(5) DEFAULT 0 COMMENT '완료 횟수',
        `success_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT '성공률 %',
        `level` INT(1) DEFAULT 1 COMMENT '레벨 (1-5)',
        `achievements` JSON DEFAULT NULL COMMENT '달성 기록',
        `is_active` TINYINT(1) DEFAULT 1 COMMENT '활성 상태',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        `timemodified` BIGINT(10) NOT NULL COMMENT '수정 시간',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42g_sigrou_use_ix` (`userid`),
        KEY `mdl_alt42g_sigrou_act_ix` (`is_active`),
        KEY `mdl_alt42g_sigrou_tim_ix` (`timemodified`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='시그니처 루틴'";

    if ($pdo->exec($sql3) !== false) {
        echo "✅ mdl_alt42g_signature_routines 테이블 생성 완료<br>";
    }

    // 4. 개입 지표 테이블
    $sql4 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_intervention_metrics` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '학생 ID',
        `metric_date` DATE NOT NULL COMMENT '측정 날짜',
        `problem_solved` INT(5) DEFAULT 0 COMMENT '해결한 문제 수',
        `accuracy_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT '정답률 %',
        `study_time` INT(5) DEFAULT 0 COMMENT '학습 시간(분)',
        `confidence_score` INT(2) DEFAULT 5 COMMENT '자신감 점수 (1-10)',
        `completion_rate` DECIMAL(5,2) DEFAULT 0.00 COMMENT '완료율 %',
        `weak_types_conquered` INT(3) DEFAULT 0 COMMENT '정복한 취약 유형 수',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        PRIMARY KEY (`id`),
        UNIQUE KEY `mdl_alt42g_intmet_usedat_uix` (`userid`, `metric_date`),
        KEY `mdl_alt42g_intmet_use_ix` (`userid`),
        KEY `mdl_alt42g_intmet_dat_ix` (`metric_date`),
        KEY `mdl_alt42g_intmet_tim_ix` (`timecreated`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개입 지표'";

    if ($pdo->exec($sql4) !== false) {
        echo "✅ mdl_alt42g_intervention_metrics 테이블 생성 완료<br>";
    }

    // 5. 실전 시뮬레이션 기록 테이블
    $sql5 = "CREATE TABLE IF NOT EXISTS `mdl_alt42g_exam_simulations` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL COMMENT '학생 ID',
        `exam_type` VARCHAR(50) DEFAULT NULL COMMENT '시험 종류',
        `simulation_date` DATE NOT NULL COMMENT '시뮬레이션 날짜',
        `duration` INT(5) DEFAULT 0 COMMENT '소요 시간(초)',
        `score` INT(3) DEFAULT 0 COMMENT '점수',
        `problems_attempted` INT(3) DEFAULT 0 COMMENT '시도한 문제 수',
        `problems_correct` INT(3) DEFAULT 0 COMMENT '맞춘 문제 수',
        `weak_areas` TEXT DEFAULT NULL COMMENT '발견된 취약 영역',
        `timecreated` BIGINT(10) NOT NULL COMMENT '생성 시간',
        PRIMARY KEY (`id`),
        KEY `mdl_alt42g_exasim_use_ix` (`userid`),
        KEY `mdl_alt42g_exasim_dat_ix` (`simulation_date`),
        KEY `mdl_alt42g_exasim_tim_ix` (`timecreated`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='실전 시뮬레이션 기록'";

    if ($pdo->exec($sql5) !== false) {
        echo "✅ mdl_alt42g_exam_simulations 테이블 생성 완료<br>";
    }

    echo "<br><h3>✨ 모든 테이블이 성공적으로 생성되었습니다!</h3>";

    // 생성된 테이블 확인
    echo "<br><h3>생성된 테이블 목록:</h3>";
    $tables = $pdo->query("SHOW TABLES LIKE 'mdl_alt42g_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        // 테이블의 레코드 수 확인
        $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
        echo "<li>$table (레코드: $count개)</li>";
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
    <title>AI 시험대비 시스템 테이블 생성</title>
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
        .btn-container {
            margin-top: 30px;
            text-align: center;
        }
        a {
            display: inline-block;
            margin: 0 10px;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        a:hover {
            background-color: #45a049;
        }
        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="warning">
        <strong>📌 참고:</strong> 이 테이블들은 AI 시험대비 시스템을 위한 것입니다.
        테이블이 이미 존재하는 경우 재생성되지 않습니다.
    </div>

    <div class="btn-container">
        <a href="intervention_system.php">AI 시험대비 시스템으로 이동</a>
        <a href="student_dashboard.php">대시보드로 돌아가기</a>
    </div>
</body>
</html>