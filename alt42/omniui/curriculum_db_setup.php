<?php
/**
 * 커리큘럼 오케스트레이터 데이터베이스 설정
 * 테이블 생성 및 초기화 스크립트
 * 
 * 실행: php curriculum_db_setup.php 또는 브라우저에서 접속
 */

require_once __DIR__ . '/curriculum_orchestrator_config.php';

// 실행 권한 체크 (CLI 또는 관리자만)
if (php_sapi_name() !== 'cli') {
    session_start();
    if (!isset($_SESSION['user_id']) || !isTeacherRole($_SESSION['user_id'])) {
        die('관리자 권한이 필요합니다.');
    }
}

echo "<pre>";
echo "=================================\n";
echo "커리큘럼 시스템 데이터베이스 설정\n";
echo "=================================\n\n";

try {
    $pdo = getCurriculumDB();
    
    // 1. 설정 테이블
    $sql1 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_config` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `config_key` VARCHAR(100) NOT NULL,
        `config_value` TEXT,
        `courseid` BIGINT(10) DEFAULT NULL,
        `description` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        `timemodified` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_key_course` (`config_key`, `courseid`),
        KEY `idx_courseid` (`courseid`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 설정'";
    
    $pdo->exec($sql1);
    echo "✅ mdl_curriculum_config 테이블 생성 완료\n";
    
    // 2. 커리큘럼 계획 테이블
    $sql2 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_plan` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL,
        `courseid` BIGINT(10) NOT NULL,
        `exam_date` DATE NOT NULL,
        `exam_type` VARCHAR(50) DEFAULT NULL,
        `exam_name` VARCHAR(255) DEFAULT NULL,
        `created_by` BIGINT(10) NOT NULL,
        `ratio_lead` INT(3) NOT NULL DEFAULT 70,
        `ratio_review` INT(3) NOT NULL DEFAULT 30,
        `daily_minutes` INT(5) NOT NULL DEFAULT 120,
        `status` VARCHAR(20) NOT NULL DEFAULT 'draft',
        `start_date` DATE NOT NULL,
        `end_date` DATE NOT NULL,
        `total_days` INT(5) NOT NULL,
        `metadata` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        `timemodified` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_userid` (`userid`),
        KEY `idx_courseid` (`courseid`),
        KEY `idx_status` (`status`),
        KEY `idx_exam_date` (`exam_date`),
        KEY `idx_created_by` (`created_by`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 계획'";
    
    $pdo->exec($sql2);
    echo "✅ mdl_curriculum_plan 테이블 생성 완료\n";
    
    // 3. 커리큘럼 항목 테이블
    $sql3 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_items` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `planid` BIGINT(10) NOT NULL,
        `day_index` INT(5) NOT NULL,
        `sequence` INT(5) NOT NULL DEFAULT 1,
        `item_type` VARCHAR(20) NOT NULL,
        `ref_type` VARCHAR(20) NOT NULL,
        `ref_id` BIGINT(10) NOT NULL,
        `title` VARCHAR(255) DEFAULT NULL,
        `description` TEXT,
        `est_minutes` INT(5) NOT NULL DEFAULT 20,
        `actual_minutes` INT(5) DEFAULT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
        `due_date` DATE NOT NULL,
        `completed_date` DATETIME DEFAULT NULL,
        `difficulty` INT(1) DEFAULT 2,
        `priority` INT(1) DEFAULT 5,
        `tags` TEXT,
        `metadata` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        `timemodified` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_planid` (`planid`),
        KEY `idx_day_index` (`day_index`),
        KEY `idx_item_type` (`item_type`),
        KEY `idx_status` (`status`),
        KEY `idx_due_date` (`due_date`),
        KEY `idx_ref` (`ref_type`, `ref_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 항목'";
    
    $pdo->exec($sql3);
    echo "✅ mdl_curriculum_items 테이블 생성 완료\n";
    
    // 4. 진행 상황 추적 테이블
    $sql4 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_progress` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `planid` BIGINT(10) NOT NULL,
        `userid` BIGINT(10) NOT NULL,
        `day_index` INT(5) NOT NULL,
        `date` DATE NOT NULL,
        `planned_items` INT(5) NOT NULL DEFAULT 0,
        `completed_items` INT(5) NOT NULL DEFAULT 0,
        `planned_minutes` INT(5) NOT NULL DEFAULT 0,
        `actual_minutes` INT(5) NOT NULL DEFAULT 0,
        `completion_rate` DECIMAL(5,2) DEFAULT 0,
        `lead_completed` INT(5) NOT NULL DEFAULT 0,
        `review_completed` INT(5) NOT NULL DEFAULT 0,
        `notes` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        `timemodified` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_plan_user_day` (`planid`, `userid`, `day_index`),
        KEY `idx_userid` (`userid`),
        KEY `idx_date` (`date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 진행상황'";
    
    $pdo->exec($sql4);
    echo "✅ mdl_curriculum_progress 테이블 생성 완료\n";
    
    // 5. KPI 메트릭 테이블
    $sql5 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_kpi` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `planid` BIGINT(10) NOT NULL,
        `metric_date` DATE NOT NULL,
        `completion_rate` DECIMAL(5,2) DEFAULT 0,
        `review_resolution_rate` DECIMAL(5,2) DEFAULT 0,
        `daily_achievement_rate` DECIMAL(5,2) DEFAULT 0,
        `estimated_completion_date` DATE DEFAULT NULL,
        `days_ahead_behind` INT(5) DEFAULT 0,
        `total_study_minutes` INT(10) DEFAULT 0,
        `average_daily_minutes` DECIMAL(7,2) DEFAULT 0,
        `streak_days` INT(5) DEFAULT 0,
        `metadata` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_plan_date` (`planid`, `metric_date`),
        KEY `idx_metric_date` (`metric_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='커리큘럼 KPI'";
    
    $pdo->exec($sql5);
    echo "✅ mdl_curriculum_kpi 테이블 생성 완료\n";
    
    // 6. 오답노트 연동 테이블
    $sql6 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_review_pool` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `userid` BIGINT(10) NOT NULL,
        `courseid` BIGINT(10) NOT NULL,
        `question_id` BIGINT(10) NOT NULL,
        `question_type` VARCHAR(50) DEFAULT NULL,
        `chapter_id` BIGINT(10) DEFAULT NULL,
        `error_count` INT(5) NOT NULL DEFAULT 1,
        `last_error_date` DATETIME NOT NULL,
        `resolution_status` VARCHAR(20) NOT NULL DEFAULT 'unresolved',
        `difficulty` INT(1) DEFAULT 2,
        `priority_score` DECIMAL(5,2) DEFAULT 50.00,
        `tags` TEXT,
        `notes` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        `timemodified` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_user_question` (`userid`, `question_id`),
        KEY `idx_courseid` (`courseid`),
        KEY `idx_status` (`resolution_status`),
        KEY `idx_priority` (`priority_score`),
        KEY `idx_last_error` (`last_error_date`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='오답노트 풀'";
    
    $pdo->exec($sql6);
    echo "✅ mdl_curriculum_review_pool 테이블 생성 완료\n";
    
    // 7. 개념 맵 테이블
    $sql7 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_concept_map` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `courseid` BIGINT(10) NOT NULL,
        `concept_id` BIGINT(10) NOT NULL,
        `concept_name` VARCHAR(255) NOT NULL,
        `chapter_id` BIGINT(10) DEFAULT NULL,
        `prereq_of` TEXT COMMENT 'JSON array of concept_ids',
        `depends_on` TEXT COMMENT 'JSON array of concept_ids',
        `difficulty` INT(1) DEFAULT 2,
        `importance` INT(1) DEFAULT 5,
        `est_minutes` INT(5) DEFAULT 30,
        `tags` TEXT,
        `metadata` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        `timemodified` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `idx_course_concept` (`courseid`, `concept_id`),
        KEY `idx_chapter` (`chapter_id`),
        KEY `idx_difficulty` (`difficulty`),
        KEY `idx_importance` (`importance`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='개념 맵'";
    
    $pdo->exec($sql7);
    echo "✅ mdl_curriculum_concept_map 테이블 생성 완료\n";
    
    // 8. 활동 로그 테이블
    $sql8 = "CREATE TABLE IF NOT EXISTS `mdl_curriculum_log` (
        `id` BIGINT(10) NOT NULL AUTO_INCREMENT,
        `planid` BIGINT(10) DEFAULT NULL,
        `userid` BIGINT(10) NOT NULL,
        `action` VARCHAR(50) NOT NULL,
        `target_type` VARCHAR(50) DEFAULT NULL,
        `target_id` BIGINT(10) DEFAULT NULL,
        `old_value` TEXT,
        `new_value` TEXT,
        `metadata` TEXT,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `user_agent` TEXT,
        `timecreated` BIGINT(10) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_planid` (`planid`),
        KEY `idx_userid` (`userid`),
        KEY `idx_action` (`action`),
        KEY `idx_timecreated` (`timecreated`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='활동 로그'";
    
    $pdo->exec($sql8);
    echo "✅ mdl_curriculum_log 테이블 생성 완료\n";
    
    // 기본 설정 데이터 삽입
    $configs = [
        ['default_ratio_lead', '70', null, '기본 선행학습 비율'],
        ['default_ratio_review', '30', null, '기본 복습 비율'],
        ['default_daily_minutes', '120', null, '기본 일일 학습 시간(분)'],
        ['min_item_minutes', '10', null, '최소 학습 단위(분)'],
        ['max_item_minutes', '30', null, '최대 학습 단위(분)'],
        ['auto_redistribute', '1', null, '미이행 항목 자동 재분배'],
        ['enable_notifications', '1', null, '알림 활성화'],
        ['notification_time', '08:00:00', null, '일일 알림 시간'],
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO mdl_curriculum_config 
        (config_key, config_value, courseid, description, timecreated, timemodified) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $now = time();
    foreach ($configs as $config) {
        $stmt->execute([$config[0], $config[1], $config[2], $config[3], $now, $now]);
    }
    echo "✅ 기본 설정 데이터 삽입 완료\n";
    
    echo "\n=================================\n";
    echo "데이터베이스 설정 완료!\n";
    echo "=================================\n";
    
} catch (PDOException $e) {
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
}

echo "</pre>";
?>