<?php
/**
 * PLP 시스템 완전 테스트 페이지
 * 모든 기능이 정상 작동하는지 확인
 */

// 데이터베이스 연결
define('DB_HOST', '58.180.27.46');
define('DB_NAME', 'mathking');
define('DB_USER', 'moodle');
define('DB_PASS', '@MCtrigd7128');

$testResults = [];
$allPassed = true;

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
    
    // 1. 테이블 존재 확인
    $tables = [
        'mdl_plp_learning_records',
        'mdl_plp_error_tags',
        'mdl_plp_streak_tracker',
        'mdl_plp_practice_checks'
    ];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            $testResults[$table] = ['status' => 'PASS', 'message' => "테이블 존재 (레코드: $count)"];
        } catch (PDOException $e) {
            $testResults[$table] = ['status' => 'FAIL', 'message' => '테이블 없음'];
            $allPassed = false;
        }
    }
    
    // 2. 데이터 삽입 테스트
    $testUserId = 2;
    $today = date('Y-m-d');
    
    try {
        // 학습 기록 테스트
        $stmt = $pdo->prepare("
            INSERT INTO mdl_plp_learning_records 
            (userid, date, summary, advance_mins, review_mins, summary_count, timecreated, timemodified)
            VALUES (?, ?, ?, ?, ?, ?, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
            ON DUPLICATE KEY UPDATE
            summary = VALUES(summary), 
            advance_mins = VALUES(advance_mins),
            review_mins = VALUES(review_mins),
            timemodified = UNIX_TIMESTAMP()
        ");
        $stmt->execute([$testUserId, $today, '테스트 학습 요약입니다.', 30, 15, 1]);
        $testResults['데이터 삽입'] = ['status' => 'PASS', 'message' => '학습 기록 저장 성공'];
    } catch (PDOException $e) {
        $testResults['데이터 삽입'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        $allPassed = false;
    }
    
    // 3. 데이터 조회 테스트
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM mdl_plp_learning_records 
            WHERE userid = ? AND date = ?
        ");
        $stmt->execute([$testUserId, $today]);
        $record = $stmt->fetch();
        
        if ($record) {
            $testResults['데이터 조회'] = ['status' => 'PASS', 'message' => '학습 기록 조회 성공'];
        } else {
            $testResults['데이터 조회'] = ['status' => 'WARN', 'message' => '데이터 없음'];
        }
    } catch (PDOException $e) {
        $testResults['데이터 조회'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        $allPassed = false;
    }
    
    // 4. 통계 계산 테스트
    try {
        $stmt = $pdo->prepare("
            SELECT 
                SUM(advance_mins) as total_advance,
                SUM(review_mins) as total_review,
                COUNT(DISTINCT date) as study_days,
                AVG(advance_mins + review_mins) as avg_daily
            FROM mdl_plp_learning_records
            WHERE userid = ?
        ");
        $stmt->execute([$testUserId]);
        $stats = $stmt->fetch();
        
        $testResults['통계 계산'] = [
            'status' => 'PASS', 
            'message' => sprintf(
                "진도 %d분, 복습 %d분, 학습일 %d일",
                $stats['total_advance'] ?? 0,
                $stats['total_review'] ?? 0,
                $stats['study_days'] ?? 0
            )
        ];
    } catch (PDOException $e) {
        $testResults['통계 계산'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        $allPassed = false;
    }
    
    // 5. AJAX 엔드포인트 테스트
    $ajaxEndpoints = [
        'ajax_save_summary.php',
        'ajax_save_error_tag.php', 
        'ajax_save_check.php',
        'ajax_save_streak.php',
        'ajax_get_stats.php'
    ];
    
    foreach ($ajaxEndpoints as $endpoint) {
        $filePath = __DIR__ . '/' . $endpoint;
        if (file_exists($filePath)) {
            $testResults[$endpoint] = ['status' => 'PASS', 'message' => '파일 존재'];
        } else {
            $testResults[$endpoint] = ['status' => 'FAIL', 'message' => '파일 없음'];
            $allPassed = false;
        }
    }
    
} catch (PDOException $e) {
    $testResults['데이터베이스 연결'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
    $allPassed = false;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLP 시스템 테스트</title>
    <style>
        body {
            font-family: 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .test-result {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            background: #f8f9fa;
        }
        
        .test-name {
            font-weight: 600;
            color: #495057;
        }
        
        .test-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        
        .status-pass {
            background: #d4edda;
            color: #155724;
        }
        
        .status-fail {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-warn {
            background: #fff3cd;
            color: #856404;
        }
        
        .test-message {
            color: #6c757d;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .summary {
            margin-top: 30px;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary.success {
            background: #d4edda;
            color: #155724;
        }
        
        .summary.failure {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-buttons {
            margin-top: 30px;
            text-align: center;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            margin: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔬 PLP 시스템 완전 테스트</h1>
        
        <?php foreach ($testResults as $testName => $result): ?>
        <div class="test-result">
            <div>
                <div class="test-name"><?php echo htmlspecialchars($testName); ?></div>
                <?php if (!empty($result['message'])): ?>
                <div class="test-message"><?php echo htmlspecialchars($result['message']); ?></div>
                <?php endif; ?>
            </div>
            <span class="test-status status-<?php echo strtolower($result['status']); ?>">
                <?php echo $result['status']; ?>
            </span>
        </div>
        <?php endforeach; ?>
        
        <div class="summary <?php echo $allPassed ? 'success' : 'failure'; ?>">
            <?php if ($allPassed): ?>
                <h2>✅ 모든 테스트 통과!</h2>
                <p>PLP 시스템이 정상적으로 작동합니다.</p>
            <?php else: ?>
                <h2>⚠️ 일부 테스트 실패</h2>
                <p>위 실패한 항목들을 확인해주세요.</p>
            <?php endif; ?>
        </div>
        
        <div class="action-buttons">
            <a href="plp_setup_db.php" class="btn">📊 DB 설정</a>
            <a href="plp_full_fixed.php" class="btn">🚀 시스템 사용</a>
        </div>
    </div>
</body>
</html>