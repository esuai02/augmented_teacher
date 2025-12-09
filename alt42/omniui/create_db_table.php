<?php
/**
 * mdl_alt42g_teacher_feedback 테이블 생성 스크립트
 *
 * 이 파일을 브라우저에서 실행하면 자동으로 테이블이 생성됩니다.
 */

// 에러 표시 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB 설정 파일 포함
$configFile = __DIR__ . '/config.php';

if (!file_exists($configFile)) {
    die('❌ 설정 파일을 찾을 수 없습니다: ' . $configFile);
}

require_once $configFile;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB 테이블 생성 - mdl_alt42g_teacher_feedback</title>
    <style>
        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 600px;
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        }
        h1 {
            color: #374151;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }
        .status {
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            font-size: 14px;
            line-height: 1.6;
        }
        .success {
            background: #d1fae5;
            border: 2px solid #10b981;
            color: #065f46;
        }
        .error {
            background: #fee2e2;
            border: 2px solid #ef4444;
            color: #991b1b;
        }
        .info {
            background: #e0e7ff;
            border: 2px solid #6366f1;
            color: #312e81;
        }
        .warning {
            background: #fef3c7;
            border: 2px solid #f59e0b;
            color: #92400e;
        }
        .sql-code {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.3);
        }
        .details {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            font-size: 13px;
        }
        .details h3 {
            margin: 0 0 10px 0;
            color: #6b7280;
            font-size: 14px;
        }
        .details ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .details li {
            margin: 5px 0;
            color: #4b5563;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ DB 테이블 생성</h1>
        <h2 style="text-align: center; color: #6b7280; font-size: 18px; margin-top: -20px;">mdl_alt42g_teacher_feedback</h2>

        <?php
        try {
            // PDO 연결 생성
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            echo '<div class="status info">✅ 데이터베이스 연결 성공<br>';
            echo 'Host: ' . DB_HOST . '<br>';
            echo 'Database: ' . DB_NAME . '</div>';

            // 테이블 존재 여부 확인
            $checkSQL = "SHOW TABLES LIKE 'mdl_alt42g_teacher_feedback'";
            $stmt = $pdo->query($checkSQL);
            $tableExists = $stmt->rowCount() > 0;

            if ($tableExists) {
                echo '<div class="status warning">⚠️ 테이블이 이미 존재합니다: mdl_alt42g_teacher_feedback</div>';

                // 테이블 구조 확인
                $descSQL = "DESCRIBE mdl_alt42g_teacher_feedback";
                $stmt = $pdo->query($descSQL);
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<div class="details">';
                echo '<h3>현재 테이블 구조:</h3>';
                echo '<ul>';
                foreach ($columns as $column) {
                    echo '<li><strong>' . $column['Field'] . '</strong>: ' . $column['Type'] .
                         ($column['Null'] === 'NO' ? ' NOT NULL' : '') .
                         ($column['Key'] === 'PRI' ? ' (PRIMARY KEY)' : '') .
                         ($column['Default'] ? ' DEFAULT ' . $column['Default'] : '') . '</li>';
                }
                echo '</ul>';
                echo '</div>';

                // 데이터 개수 확인
                $countSQL = "SELECT COUNT(*) as count FROM mdl_alt42g_teacher_feedback";
                $stmt = $pdo->query($countSQL);
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

                echo '<div class="status info">📊 현재 저장된 데이터: ' . $count . '개</div>';

            } else {
                // 테이블 생성
                $createSQL = "
                CREATE TABLE IF NOT EXISTS mdl_alt42g_teacher_feedback (
                    id INT(11) NOT NULL AUTO_INCREMENT,
                    userid INT(11) NOT NULL COMMENT 'Moodle user ID',
                    teacher_id INT(11) NOT NULL COMMENT 'Teacher user ID',
                    period INT(2) NOT NULL COMMENT 'Teaching period (1-8)',
                    feedback_text LONGTEXT NOT NULL COMMENT 'Teacher feedback content',
                    suggestions TEXT COMMENT 'Improvement suggestions',
                    encouragement TEXT COMMENT 'Encouragement message',
                    feedback_summary TEXT COMMENT 'Feedback summary',
                    execution_plan TEXT COMMENT 'Execution plan',
                    next_steps TEXT COMMENT 'Next steps',
                    session_id VARCHAR(255) COMMENT 'PHP session ID for tracking',
                    timecreated INT(11) NOT NULL COMMENT 'Timestamp when created',
                    timemodified INT(11) NOT NULL COMMENT 'Timestamp when last modified',
                    PRIMARY KEY (id),
                    KEY userid_idx (userid),
                    KEY teacher_id_idx (teacher_id),
                    KEY period_idx (period),
                    KEY timecreated_idx (timecreated)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
                COMMENT='Teacher feedback storage for ALT42 orchestration system'
                ";

                $pdo->exec($createSQL);

                echo '<div class="status success">🎉 테이블이 성공적으로 생성되었습니다!</div>';

                echo '<div class="details">';
                echo '<h3>생성된 테이블 정보:</h3>';
                echo '<ul>';
                echo '<li>테이블명: <strong>mdl_alt42g_teacher_feedback</strong></li>';
                echo '<li>엔진: InnoDB</li>';
                echo '<li>문자셋: utf8mb4</li>';
                echo '<li>Collation: utf8mb4_unicode_ci</li>';
                echo '</ul>';
                echo '</div>';

                echo '<div class="sql-code">' . htmlspecialchars($createSQL) . '</div>';
            }

            // 테이블 정보 표시
            echo '<div class="details">';
            echo '<h3>테이블 사용 정보:</h3>';
            echo '<ul>';
            echo '<li>API 엔드포인트: <strong>/orchestration_hs2/api/save_teacher_feedback.php</strong></li>';
            echo '<li>테스트 페이지: <strong>/orchestration_hs2/test_db_save.html</strong></li>';
            echo '<li>메인 시스템: <strong>/orchestration_hs2/index.php</strong> (Step 14)</li>';
            echo '</ul>';
            echo '</div>';

        } catch (PDOException $e) {
            echo '<div class="status error">❌ 오류 발생: ' . htmlspecialchars($e->getMessage()) . '</div>';

            if (strpos($e->getMessage(), 'Access denied') !== false) {
                echo '<div class="status warning">💡 해결 방법: DB 사용자 권한을 확인하세요.</div>';
            } else if (strpos($e->getMessage(), 'Unknown database') !== false) {
                echo '<div class="status warning">💡 해결 방법: 데이터베이스가 존재하는지 확인하세요.</div>';
            }
        }
        ?>

        <div style="text-align: center; margin-top: 30px;">
            <a href="../orchestration_hs2/test_db_save.html" class="btn">📝 테스트 페이지로 이동</a>
        </div>
    </div>
</body>
</html>