<?php
/**
 * PLP 데이터베이스 자동 설정 스크립트
 * 테이블이 없으면 자동으로 생성
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_setup_db.php
 */

// 데이터베이스 연결
define('DB_HOST', '58.180.27.46');
define('DB_NAME', 'mathking');
define('DB_USER', 'moodle');
define('DB_PASS', '@MCtrigd7128');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "<h2>PLP 데이터베이스 설정</h2>";
    echo "<pre>";
    
    // 테이블 생성 쿼리들
    $tables = [
        'mdl_plp_learning_records' => "
            CREATE TABLE IF NOT EXISTS `mdl_plp_learning_records` (
                `id` bigint(10) NOT NULL AUTO_INCREMENT,
                `userid` bigint(10) NOT NULL,
                `date` date NOT NULL,
                `summary` text DEFAULT NULL,
                `advance_mins` int(11) DEFAULT 0,
                `review_mins` int(11) DEFAULT 0,
                `summary_count` int(11) DEFAULT 0,
                `timecreated` bigint(10) NOT NULL,
                `timemodified` bigint(10) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `mdl_plp_lr_user_date_uix` (`userid`, `date`),
                KEY `mdl_plp_lr_user_ix` (`userid`),
                KEY `mdl_plp_lr_date_ix` (`date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mdl_plp_error_tags' => "
            CREATE TABLE IF NOT EXISTS `mdl_plp_error_tags` (
                `id` bigint(10) NOT NULL AUTO_INCREMENT,
                `userid` bigint(10) NOT NULL,
                `problem_id` varchar(50) NOT NULL,
                `problem_text` text DEFAULT NULL,
                `tags` text DEFAULT NULL,
                `difficulty` tinyint(1) DEFAULT 1,
                `timecreated` bigint(10) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `mdl_plp_et_user_ix` (`userid`),
                KEY `mdl_plp_et_problem_ix` (`problem_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mdl_plp_streak_tracker' => "
            CREATE TABLE IF NOT EXISTS `mdl_plp_streak_tracker` (
                `id` bigint(10) NOT NULL AUTO_INCREMENT,
                `userid` bigint(10) NOT NULL,
                `current_streak` int(11) DEFAULT 0,
                `best_streak` int(11) DEFAULT 0,
                `last_pass_date` date DEFAULT NULL,
                `timemodified` bigint(10) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `mdl_plp_st_user_uix` (`userid`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ",
        
        'mdl_plp_practice_checks' => "
            CREATE TABLE IF NOT EXISTS `mdl_plp_practice_checks` (
                `id` bigint(10) NOT NULL AUTO_INCREMENT,
                `userid` bigint(10) NOT NULL,
                `date` date NOT NULL,
                `problem_ids` text DEFAULT NULL,
                `problem_texts` text DEFAULT NULL,
                `checked_count` int(11) DEFAULT 0,
                `timecreated` bigint(10) NOT NULL,
                PRIMARY KEY (`id`),
                KEY `mdl_plp_pc_user_date_ix` (`userid`, `date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        "
    ];
    
    // 각 테이블 생성
    foreach ($tables as $tableName => $createSQL) {
        try {
            $pdo->exec($createSQL);
            echo "✅ 테이블 생성/확인: $tableName\n";
        } catch (PDOException $e) {
            echo "❌ 테이블 오류 $tableName: " . $e->getMessage() . "\n";
        }
    }
    
    // 테이블 존재 확인
    echo "\n<strong>테이블 존재 확인:</strong>\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'mdl_plp_%'");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($existingTables as $table) {
        echo "✅ $table 존재\n";
    }
    
    // 샘플 데이터 삽입 (중복 방지)
    echo "\n<strong>샘플 데이터 삽입:</strong>\n";
    
    try {
        // 테스트 사용자 ID 2로 샘플 데이터
        $userid = 2;
        $today = date('Y-m-d');
        
        // 학습 기록 샘플
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO mdl_plp_learning_records 
            (userid, date, summary, advance_mins, review_mins, summary_count, timecreated, timemodified)
            VALUES (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
        ");
        $stmt->execute([$userid, $today, '오늘 미적분 극한 개념을 학습하고 문제를 풀었습니다.', 42, 18, 1]);
        echo "✅ 학습 기록 샘플 데이터 삽입\n";
        
        // 연속 통과 샘플
        $stmt = $pdo->prepare("
            INSERT INTO mdl_plp_streak_tracker 
            (userid, current_streak, best_streak, last_pass_date, timemodified)
            VALUES (?, 3, 7, ?, UNIX_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
            current_streak = 3, best_streak = 7, timemodified = UNIX_TIMESTAMP()
        ");
        $stmt->execute([$userid, $today]);
        echo "✅ 연속 통과 샘플 데이터 삽입\n";
        
    } catch (PDOException $e) {
        echo "샘플 데이터 삽입 스킵 (이미 존재할 수 있음)\n";
    }
    
    echo "\n<strong>✅ 데이터베이스 설정 완료!</strong>\n";
    echo "이제 PLP 시스템을 사용할 수 있습니다.\n";
    echo "</pre>";
    
    // 메인 페이지로 이동 버튼
    echo '<br><a href="plp_full_fixed.php" style="display:inline-block;padding:10px 20px;background:#667eea;color:white;text-decoration:none;border-radius:5px;">PLP 시스템으로 이동</a>';
    
} catch (PDOException $e) {
    echo "<h2>데이터베이스 연결 실패</h2>";
    echo "<pre>";
    echo "오류: " . $e->getMessage();
    echo "</pre>";
}
?>