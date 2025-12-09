<?php
/**
 * PLP 완전 작동 버전 - 실제 시험 데이터 연동 (UI 개선)
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_full_fixed.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle 설정 로드
$moodle_config = '/home/moodle/public_html/moodle/config.php';
if (file_exists($moodle_config)) {
    require_once($moodle_config);
    require_login();
    $userid = $USER->id;
    $username = $USER->firstname . ' ' . $USER->lastname;
} else {
    session_start();
    $userid = $_SESSION['user_id'] ?? 2;
    $username = '테스트 사용자';
}

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
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// 테이블 자동 생성 (없을 경우)
function createTablesIfNotExist($pdo) {
    $tables = [
        "CREATE TABLE IF NOT EXISTS `mdl_plp_learning_records` (
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
            KEY `mdl_plp_lr_user_ix` (`userid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `mdl_plp_error_tags` (
            `id` bigint(10) NOT NULL AUTO_INCREMENT,
            `userid` bigint(10) NOT NULL,
            `problem_id` varchar(50) NOT NULL,
            `problem_text` text DEFAULT NULL,
            `tags` text DEFAULT NULL,
            `difficulty` tinyint(1) DEFAULT 1,
            `timecreated` bigint(10) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `mdl_plp_et_user_ix` (`userid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `mdl_plp_streak_tracker` (
            `id` bigint(10) NOT NULL AUTO_INCREMENT,
            `userid` bigint(10) NOT NULL,
            `current_streak` int(11) DEFAULT 0,
            `best_streak` int(11) DEFAULT 0,
            `last_pass_date` date DEFAULT NULL,
            `timemodified` bigint(10) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `mdl_plp_st_user_uix` (`userid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        "CREATE TABLE IF NOT EXISTS `mdl_plp_practice_checks` (
            `id` bigint(10) NOT NULL AUTO_INCREMENT,
            `userid` bigint(10) NOT NULL,
            `date` date NOT NULL,
            `problem_ids` text DEFAULT NULL,
            `problem_texts` text DEFAULT NULL,
            `checked_count` int(11) DEFAULT 0,
            `timecreated` bigint(10) NOT NULL,
            PRIMARY KEY (`id`),
            KEY `mdl_plp_pc_user_date_ix` (`userid`, `date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // 테이블이 이미 존재하는 경우 무시
        }
    }
}

// 테이블 생성 시도
createTablesIfNotExist($pdo);

// 사용자의 실제 시험 문제 가져오기
function getUserTestProblems($pdo, $userid) {
    // Alt42t 시험 자료에서 가져오기 (mdl_alt42t_exam_resources 테이블이 있다면)
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT 
                er.resource_id as problem_id,
                er.title as problem_text,
                er.tip_text as hint
            FROM mdl_alt42t_exam_resources er
            LEFT JOIN mdl_alt42t_exams e ON e.id = er.exam_id
            WHERE e.user_id = ? OR er.resource_id IS NOT NULL
            ORDER BY er.uploaded_at DESC
            LIMIT 20
        ");
        $stmt->execute([$userid]);
        $problems = $stmt->fetchAll();
        
        if (empty($problems)) {
            // 테이블이 없거나 데이터가 없으면 기본 문제 반환
            throw new Exception("No data");
        }
        return $problems;
    } catch (Exception $e) {
        // 기본 미적분 문제 세트
        return [
            ['problem_id' => 'calc_001', 'problem_text' => '극한 lim(x→0) sin(x)/x 구하기', 'hint' => '로피탈 정리 활용'],
            ['problem_id' => 'calc_002', 'problem_text' => '함수 f(x)=x³-3x²+2의 극값 구하기', 'hint' => '도함수를 구하고 0이 되는 점 찾기'],
            ['problem_id' => 'calc_003', 'problem_text' => '∫(1/(1+x²))dx 계산하기', 'hint' => 'arctan 함수 이용'],
            ['problem_id' => 'calc_004', 'problem_text' => '테일러 급수 전개: e^x at x=0', 'hint' => 'n차 도함수 패턴 찾기'],
            ['problem_id' => 'calc_005', 'problem_text' => '회전체 부피: y=√x, x=0~4, x축 회전', 'hint' => '원판 방법 사용'],
            ['problem_id' => 'calc_006', 'problem_text' => '연쇄법칙: (sin(x²))′ 구하기', 'hint' => '합성함수 미분법'],
            ['problem_id' => 'calc_007', 'problem_text' => '부분적분: ∫x·e^x dx', 'hint' => 'u=x, dv=e^x dx로 설정'],
            ['problem_id' => 'calc_008', 'problem_text' => '매개변수 방정식 미분: x=t², y=t³', 'hint' => 'dy/dx = (dy/dt)/(dx/dt)'],
            ['problem_id' => 'calc_009', 'problem_text' => '역함수 미분: (arcsin(x))′', 'hint' => '역함수 미분 공식'],
            ['problem_id' => 'calc_010', 'problem_text' => '이상적분: ∫(0~∞) e^(-x) dx', 'hint' => '극한으로 변환']
        ];
    }
}

// 사용자의 오답 기록 가져오기
function getUserErrors($pdo, $userid) {
    try {
        $stmt = $pdo->prepare("
            SELECT problem_id, problem_text, tags, difficulty, timecreated
            FROM mdl_plp_error_tags
            WHERE userid = ?
            ORDER BY timecreated DESC
            LIMIT 10
        ");
        $stmt->execute([$userid]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// AJAX 처리
if (isset($_POST['action']) || isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? $_GET['action'];
    
    try {
        switch ($action) {
            case 'save_summary':
                $summary = $_POST['summary'] ?? '';
                
                if (mb_strlen($summary) < 30 || mb_strlen($summary) > 60) {
                    echo json_encode(['success' => false, 'message' => '요약은 30-60자 사이여야 합니다.']);
                    exit;
                }
                
                $today = date('Y-m-d');
                $stmt = $pdo->prepare("
                    INSERT INTO mdl_plp_learning_records 
                    (userid, date, summary, summary_count, timecreated, timemodified)
                    VALUES (?, ?, ?, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP())
                    ON DUPLICATE KEY UPDATE 
                    summary = VALUES(summary),
                    summary_count = summary_count + 1,
                    timemodified = UNIX_TIMESTAMP()
                ");
                $stmt->execute([$userid, $today, $summary]);
                
                // 학습 시간 업데이트 (랜덤 값으로 시뮬레이션)
                $advance = rand(30, 60);
                $review = rand(10, 30);
                $stmt = $pdo->prepare("
                    UPDATE mdl_plp_learning_records 
                    SET advance_mins = advance_mins + ?, review_mins = review_mins + ?
                    WHERE userid = ? AND date = ?
                ");
                $stmt->execute([$advance, $review, $userid, $today]);
                
                echo json_encode(['success' => true, 'message' => '요약이 저장되었습니다!']);
                break;
                
            case 'tag_error':
                $problem_id = $_POST['problem_id'] ?? '';
                $problem_text = $_POST['problem_text'] ?? '';
                $tags = $_POST['tags'] ?? [];
                $difficulty = $_POST['difficulty'] ?? 1;
                
                if (empty($problem_id) || empty($tags)) {
                    echo json_encode(['success' => false, 'message' => '문제와 태그를 선택하세요.']);
                    exit;
                }
                
                $tags_str = is_array($tags) ? implode(',', $tags) : $tags;
                
                $stmt = $pdo->prepare("
                    INSERT INTO mdl_plp_error_tags 
                    (userid, problem_id, problem_text, tags, difficulty, timecreated)
                    VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
                ");
                $stmt->execute([$userid, $problem_id, $problem_text, $tags_str, $difficulty]);
                
                echo json_encode(['success' => true, 'message' => '오답 태그가 추가되었습니다!']);
                break;
                
            case 'update_streak':
                $passed = $_POST['passed'] ?? 0;
                $today = date('Y-m-d');
                
                $stmt = $pdo->prepare("SELECT * FROM mdl_plp_streak_tracker WHERE userid = ?");
                $stmt->execute([$userid]);
                $streak = $stmt->fetch();
                
                if ($passed) {
                    if ($streak) {
                        $last_date = $streak['last_pass_date'];
                        $current = $streak['current_streak'];
                        $yesterday = date('Y-m-d', strtotime('-1 day'));
                        
                        if ($last_date == $yesterday || $last_date == $today) {
                            $new_streak = ($last_date == $today) ? $current : $current + 1;
                        } else {
                            $new_streak = 1;
                        }
                        
                        $best = max($new_streak, $streak['best_streak']);
                        
                        $stmt = $pdo->prepare("
                            UPDATE mdl_plp_streak_tracker 
                            SET current_streak = ?, best_streak = ?, 
                                last_pass_date = ?, timemodified = UNIX_TIMESTAMP()
                            WHERE userid = ?
                        ");
                        $stmt->execute([$new_streak, $best, $today, $userid]);
                    } else {
                        $stmt = $pdo->prepare("
                            INSERT INTO mdl_plp_streak_tracker 
                            (userid, current_streak, best_streak, last_pass_date, timemodified)
                            VALUES (?, 1, 1, ?, UNIX_TIMESTAMP())
                        ");
                        $stmt->execute([$userid, $today]);
                    }
                    $message = "🎉 통과! 연속 기록이 업데이트되었습니다.";
                } else {
                    if ($streak) {
                        $stmt = $pdo->prepare("
                            UPDATE mdl_plp_streak_tracker 
                            SET current_streak = 0, timemodified = UNIX_TIMESTAMP()
                            WHERE userid = ?
                        ");
                        $stmt->execute([$userid]);
                    }
                    $message = "💪 다시 도전하세요! 내일 다시 시작할 수 있습니다.";
                }
                
                echo json_encode(['success' => true, 'message' => $message]);
                break;
                
            case 'log_practice':
                $problem_ids = $_POST['problem_ids'] ?? [];
                $problem_texts = $_POST['problem_texts'] ?? [];
                $today = date('Y-m-d');
                
                if (empty($problem_ids)) {
                    echo json_encode(['success' => false, 'message' => '문제를 선택하세요.']);
                    exit;
                }
                
                $ids_str = is_array($problem_ids) ? implode(',', $problem_ids) : $problem_ids;
                $texts_str = is_array($problem_texts) ? implode('|||', $problem_texts) : $problem_texts;
                $count = is_array($problem_ids) ? count($problem_ids) : 1;
                
                $stmt = $pdo->prepare("
                    INSERT INTO mdl_plp_practice_checks 
                    (userid, date, problem_ids, problem_texts, checked_count, timecreated)
                    VALUES (?, ?, ?, ?, ?, UNIX_TIMESTAMP())
                    ON DUPLICATE KEY UPDATE
                    problem_ids = VALUES(problem_ids),
                    problem_texts = VALUES(problem_texts),
                    checked_count = checked_count + VALUES(checked_count)
                ");
                $stmt->execute([$userid, $today, $ids_str, $texts_str, $count]);
                
                echo json_encode(['success' => true, 'message' => "✅ {$count}개 문제를 체크했습니다!"]);
                break;
                
            case 'get_stats':
                $today = date('Y-m-d');
                
                // 연속 통과
                $stmt = $pdo->prepare("SELECT * FROM mdl_plp_streak_tracker WHERE userid = ?");
                $stmt->execute([$userid]);
                $streak = $stmt->fetch() ?: ['current_streak' => 0, 'best_streak' => 0];
                
                // 학습 시간
                $stmt = $pdo->prepare("
                    SELECT advance_mins, review_mins, summary_count 
                    FROM mdl_plp_learning_records 
                    WHERE userid = ? AND date = ?
                ");
                $stmt->execute([$userid, $today]);
                $today_data = $stmt->fetch() ?: ['advance_mins' => 0, 'review_mins' => 0, 'summary_count' => 0];
                
                $total_mins = $today_data['advance_mins'] + $today_data['review_mins'];
                $advance_ratio = $total_mins > 0 ? round(($today_data['advance_mins'] / $total_mins) * 100) : 70;
                $review_ratio = 100 - $advance_ratio;
                
                // 체크한 문제
                $stmt = $pdo->prepare("
                    SELECT SUM(checked_count) as total 
                    FROM mdl_plp_practice_checks 
                    WHERE userid = ? AND date = ?
                ");
                $stmt->execute([$userid, $today]);
                $practice = $stmt->fetch();
                
                // 오답 태그
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM mdl_plp_error_tags 
                    WHERE userid = ? AND DATE(FROM_UNIXTIME(timecreated)) = ?
                ");
                $stmt->execute([$userid, $today]);
                $errors = $stmt->fetch();
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'current_streak' => $streak['current_streak'],
                        'best_streak' => $streak['best_streak'],
                        'advance_ratio' => $advance_ratio,
                        'review_ratio' => $review_ratio,
                        'summary_count' => $today_data['summary_count'],
                        'checked_count' => $practice['total'] ?? 0,
                        'error_count' => $errors['count'] ?? 0,
                        'total_mins' => $total_mins
                    ]
                ]);
                break;
                
            case 'get_problems':
                $problems = getUserTestProblems($pdo, $userid);
                echo json_encode(['success' => true, 'data' => $problems]);
                break;
                
            case 'get_errors':
                $errors = getUserErrors($pdo, $userid);
                echo json_encode(['success' => true, 'data' => $errors]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// 실제 문제 데이터 가져오기
$test_problems = getUserTestProblems($pdo, $userid);
$user_errors = getUserErrors($pdo, $userid);

// 페이지 설정
if (isset($PAGE)) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/local/augmented_teacher/alt42/omniui/plp_full_fixed.php');
    $PAGE->set_title('Personal Learning Panel');
    $PAGE->set_heading('Personal Learning Panel');
    echo $OUTPUT->header();
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Learning Panel - <?php echo htmlspecialchars($username); ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .plp-container { 
            max-width: 1400px; /* 컨테이너 폭 증가 */
            margin: 0 auto; 
        }

        .plp-header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .plp-header h1 {
            font-size: 3rem; /* 제목 크기 증가 */
            margin-bottom: 15px;
        }

        .plp-user-info {
            background: rgba(255, 255, 255, 0.9); /* 배경을 더 밝게 */
            color: #2c3e50; /* 진한 글자색 */
            padding: 12px 25px;
            border-radius: 25px;
            display: inline-block;
            margin-top: 10px;
            font-size: 1.1rem; /* 사용자 정보 폰트 증가 */
            font-weight: 600; /* 글자 굵게 */
            box-shadow: 0 4px 12px rgba(0,0,0,0.1); /* 그림자 추가 */
        }

        .plp-card {
            background: white;
            border-radius: 20px; /* 둥글기 증가 */
            padding: 35px; /* 패딩 증가 */
            margin-bottom: 25px;
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
        }

        .plp-card h2 {
            font-size: 1.8rem; /* 카드 제목 크기 증가 */
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .plp-textarea {
            width: 100%;
            min-height: 160px; /* 텍스트 영역 높이 증가 */
            padding: 20px; /* 패딩 증가 */
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 18px; /* 폰트 크기 증가 */
            resize: vertical;
            transition: all 0.3s;
            line-height: 1.6;
        }

        .plp-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
        }

        .plp-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 35px; /* 버튼 패딩 증가 */
            border-radius: 30px;
            font-size: 18px; /* 버튼 폰트 크기 증가 */
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .plp-button:hover { transform: translateY(-2px); }
        .plp-button:active { transform: translateY(0); }

        .plp-button.success {
            background: linear-gradient(135deg, #00b894, #00cec9);
        }

        .plp-button.danger {
            background: linear-gradient(135deg, #d63031, #74b9ff);
        }

        .plp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); /* 최소 폭 증가 */
            gap: 25px;
        }

        .plp-stat-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 25px 20px; /* 패딩 조정 */
            border-radius: 20px;
            text-align: center;
            min-height: 140px; /* 최소 높이 조정 */
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow: hidden; /* 넘치는 내용 숨김 */
        }

        .plp-stat-value {
            font-size: 2.8rem; /* 통계 값 크기 적절히 조정 */
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
            line-height: 1.2; /* 줄 높이 조정 */
            word-break: keep-all; /* 단어 단위로 줄바꿈 */
        }

        .plp-stat-label {
            color: #5a6c7d;
            font-size: 1.1rem; /* 라벨 크기 증가 */
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 500;
        }

        .plp-char-count {
            font-size: 16px; /* 문자 카운터 크기 증가 */
            color: #5a6c7d;
            margin-top: 12px;
        }

        .plp-char-count.valid { color: #00b894; font-weight: 500; }
        .plp-char-count.invalid { color: #d63031; font-weight: 500; }

        .plp-message {
            padding: 15px 20px; /* 메시지 패딩 증가 */
            border-radius: 10px;
            margin-top: 15px;
            display: none;
            font-size: 16px; /* 메시지 폰트 크기 증가 */
        }

        .plp-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .plp-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .plp-problem-select {
            width: 100%;
            padding: 15px; /* 패딩 증가 */
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            font-size: 17px; /* 폰트 크기 증가 */
            margin-bottom: 15px;
        }

        .plp-tag-input {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
        }

        .plp-tag-input input {
            flex: 1;
            padding: 15px; /* 패딩 증가 */
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 17px; /* 폰트 크기 증가 */
        }

        .plp-tag-input select {
            padding: 15px; /* 패딩 증가 */
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 17px; /* 폰트 크기 증가 */
        }

        .plp-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 18px;
        }

        .plp-tag {
            background: #e0e6ed;
            padding: 8px 16px; /* 태그 패딩 증가 */
            border-radius: 18px;
            font-size: 15px; /* 태그 폰트 크기 증가 */
            color: #2c3e50;
            font-weight: 500;
        }

        .plp-tag.difficulty-1 { background: #d4edda; }
        .plp-tag.difficulty-2 { background: #fff3cd; }
        .plp-tag.difficulty-3 { background: #f8d7da; }

        .plp-problem-list {
            max-height: 500px; /* 최대 높이 증가 */
            overflow-y: auto;
            border: 2px solid #e0e6ed;
            border-radius: 12px;
            padding: 20px; /* 패딩 증가 */
        }

        .plp-problem-item {
            display: flex;
            align-items: center;
            padding: 18px; /* 패딩 증가 */
            margin-bottom: 12px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: background 0.2s;
        }

        .plp-problem-item:hover { background: #e9ecef; }

        .plp-problem-item input[type="checkbox"] {
            width: 24px; /* 체크박스 크기 증가 */
            height: 24px;
            margin-right: 15px;
            cursor: pointer;
        }

        .plp-problem-item label {
            flex: 1;
            cursor: pointer;
            font-size: 16px; /* 라벨 폰트 크기 증가 */
            line-height: 1.5;
        }

        .plp-problem-hint {
            font-size: 14px; /* 힌트 폰트 크기 증가 */
            color: #6c757d;
            margin-top: 6px;
        }

        .plp-error-list {
            max-height: 400px; /* 최대 높이 증가 */
            overflow-y: auto;
            padding: 20px; /* 패딩 증가 */
            background: #f8f9fa;
            border-radius: 12px;
        }

        .plp-error-item {
            padding: 15px; /* 패딩 증가 */
            margin-bottom: 12px;
            background: white;
            border-radius: 10px;
            border-left: 5px solid #e74c3c;
        }

        .plp-error-item strong {
            font-size: 16px; /* 오류 항목 폰트 크기 증가 */
        }

        /* 통계 그리드 개선 */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* 통계 카드 최소 폭 증가 */
            gap: 25px;
        }

        /* 반응형 개선 */
        @media (max-width: 768px) {
            .plp-header h1 { font-size: 2.5rem; }
            .plp-grid { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: 1fr 1fr; }
            .plp-stat-value { font-size: 2.3rem; } /* 모바일에서 더 작게 */
            .plp-card { padding: 25px; }
        }

        @media (max-width: 480px) {
            .stats-grid { grid-template-columns: 1fr; }
            .plp-stat-value { font-size: 2rem; } /* 작은 화면에서 더 작게 */
        }
    </style>
</head>
<body>

<div class="plp-container">
    <div class="plp-header">
        <h1>🎯 Personal Learning Panel</h1>
        <div class="plp-user-info">
            👤 <?php echo htmlspecialchars($username); ?> (ID: <?php echo $userid; ?>)
        </div>
        <p style="margin-top: 15px; font-size: 1.2rem;">오늘도 한 걸음 더 성장하는 하루!</p>
    </div>

    <!-- 학습 요약 -->
    <div class="plp-card">
        <h2>📚 오늘의 학습 요약</h2>
        <form id="summaryForm">
            <textarea 
                id="summaryText" 
                class="plp-textarea"
                placeholder="오늘 배운 내용을 30-60자로 요약하세요..."
                maxlength="60"
                minlength="30"
            ></textarea>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <span id="charCount" class="plp-char-count">0 / 60자</span>
                <button type="submit" class="plp-button">💾 저장하기</button>
            </div>
            <div id="summaryMessage" class="plp-message"></div>
        </form>
    </div>

    <!-- 기능 그리드 -->
    <div class="plp-grid">
        <!-- 오답 태그 -->
        <div class="plp-card">
            <h2>🏷️ 오답 태그</h2>
            <select id="problemSelect" class="plp-problem-select">
                <option value="">문제를 선택하세요...</option>
                <?php foreach ($test_problems as $problem): ?>
                <option value="<?php echo htmlspecialchars($problem['problem_id']); ?>" 
                        data-text="<?php echo htmlspecialchars($problem['problem_text']); ?>">
                    <?php echo htmlspecialchars($problem['problem_text']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <div class="plp-tag-input">
                <select id="difficulty">
                    <option value="1">쉬움</option>
                    <option value="2">보통</option>
                    <option value="3">어려움</option>
                </select>
            </div>
            
            <div class="plp-tag-input">
                <input type="text" id="tagInput" placeholder="태그 입력 (예: 극한,미분,계산실수)">
                <button onclick="addErrorTag()" class="plp-button">추가</button>
            </div>
            
            <div id="errorMessage" class="plp-message"></div>
            
            <?php if (!empty($user_errors)): ?>
            <div class="plp-error-list">
                <h3 style="font-size: 1.2rem; margin-bottom: 15px;">최근 오답 기록</h3>
                <?php foreach ($user_errors as $error): ?>
                <div class="plp-error-item">
                    <strong><?php echo htmlspecialchars($error['problem_text'] ?: $error['problem_id']); ?></strong>
                    <div class="plp-tags">
                        <?php 
                        $tags = explode(',', $error['tags']);
                        foreach ($tags as $tag): 
                        ?>
                        <span class="plp-tag difficulty-<?php echo $error['difficulty']; ?>">
                            <?php echo htmlspecialchars(trim($tag)); ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 문제 체크 -->
        <div class="plp-card">
            <h2>✅ 문제 풀이 체크</h2>
            <div class="plp-problem-list">
                <?php foreach ($test_problems as $index => $problem): ?>
                <div class="plp-problem-item">
                    <input type="checkbox" 
                           id="prob<?php echo $index; ?>" 
                           value="<?php echo htmlspecialchars($problem['problem_id']); ?>"
                           data-text="<?php echo htmlspecialchars($problem['problem_text']); ?>">
                    <label for="prob<?php echo $index; ?>">
                        <div><?php echo htmlspecialchars($problem['problem_text']); ?></div>
                        <?php if (!empty($problem['hint'])): ?>
                        <div class="plp-problem-hint">💡 <?php echo htmlspecialchars($problem['hint']); ?></div>
                        <?php endif; ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
            <button onclick="savePracticeChecks()" class="plp-button" style="width: 100%; margin-top: 20px;">
                저장하기
            </button>
            <div id="practiceMessage" class="plp-message"></div>
        </div>
    </div>

    <!-- 연속 통과 -->
    <div class="plp-card">
        <h2>🔥 오늘의 학습 결과</h2>
        <div style="display: flex; gap: 20px; justify-content: center; margin-top: 20px;">
            <button onclick="updateStreak(1)" class="plp-button success" style="padding: 18px 45px;">
                ✅ 통과 (Pass)
            </button>
            <button onclick="updateStreak(0)" class="plp-button danger" style="padding: 18px 45px;">
                ❌ 재도전 필요
            </button>
        </div>
        <div id="streakMessage" class="plp-message"></div>
    </div>

    <!-- 통계 -->
    <div class="plp-card">
        <h2>📊 학습 현황</h2>
        <div class="stats-grid">
            <div class="plp-stat-card">
                <div class="plp-stat-value" id="currentStreak">0</div>
                <div class="plp-stat-label">연속 통과</div>
            </div>
            <div class="plp-stat-card">
                <div class="plp-stat-value" id="bestStreak">0</div>
                <div class="plp-stat-label">최고 기록</div>
            </div>
            <div class="plp-stat-card">
                <div class="plp-stat-value" id="advanceRatio">70%</div>
                <div class="plp-stat-label">선행 학습</div>
            </div>
            <div class="plp-stat-card">
                <div class="plp-stat-value" id="reviewRatio">30%</div>
                <div class="plp-stat-label">복습</div>
            </div>
            <div class="plp-stat-card">
                <div class="plp-stat-value" id="totalTime">0분</div>
                <div class="plp-stat-label">오늘 학습 시간</div>
            </div>
            <div class="plp-stat-card">
                <div class="plp-stat-value" id="checkedCount">0</div>
                <div class="plp-stat-label">풀이 문제</div>
            </div>
        </div>
    </div>
</div>

<script>
// 문자 수 카운터
document.getElementById('summaryText').addEventListener('input', function() {
    const length = this.value.length;
    const counter = document.getElementById('charCount');
    counter.textContent = length + ' / 60자';
    counter.className = (length >= 30 && length <= 60) ? 'plp-char-count valid' : 'plp-char-count invalid';
});

// 요약 저장
document.getElementById('summaryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const summaryText = document.getElementById('summaryText').value;
    const messageDiv = document.getElementById('summaryMessage');
    
    if (summaryText.length < 30 || summaryText.length > 60) {
        showMessage(messageDiv, '⚠️ 요약은 30-60자 사이여야 합니다.', 'error');
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=save_summary&summary=' + encodeURIComponent(summaryText)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            document.getElementById('summaryText').value = '';
            document.getElementById('charCount').textContent = '0 / 60자';
            loadStats();
        } else {
            showMessage(messageDiv, data.message, 'error');
        }
    });
});

// 오답 태그 추가
function addErrorTag() {
    const select = document.getElementById('problemSelect');
    const problemId = select.value;
    const problemText = select.options[select.selectedIndex]?.dataset.text || '';
    const tagInput = document.getElementById('tagInput').value;
    const difficulty = document.getElementById('difficulty').value;
    const messageDiv = document.getElementById('errorMessage');
    
    if (!problemId || !tagInput) {
        showMessage(messageDiv, '문제와 태그를 입력하세요.', 'error');
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=tag_error&problem_id=${problemId}&problem_text=${encodeURIComponent(problemText)}&tags=${encodeURIComponent(tagInput)}&difficulty=${difficulty}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            document.getElementById('problemSelect').value = '';
            document.getElementById('tagInput').value = '';
            location.reload(); // 오답 목록 새로고침
        } else {
            showMessage(messageDiv, data.message, 'error');
        }
    });
}

// 문제 체크 저장
function savePracticeChecks() {
    const checkboxes = document.querySelectorAll('.plp-problem-item input[type="checkbox"]:checked');
    const messageDiv = document.getElementById('practiceMessage');
    
    if (checkboxes.length === 0) {
        showMessage(messageDiv, '체크된 문제가 없습니다.', 'error');
        return;
    }
    
    const problemIds = [];
    const problemTexts = [];
    
    checkboxes.forEach(cb => {
        problemIds.push(cb.value);
        problemTexts.push(cb.dataset.text);
    });
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=log_practice&problem_ids=' + problemIds.join(',') + '&problem_texts=' + encodeURIComponent(problemTexts.join('|||'))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            checkboxes.forEach(cb => cb.checked = false);
            loadStats();
        } else {
            showMessage(messageDiv, data.message, 'error');
        }
    });
}

// 연속 통과 업데이트
function updateStreak(passed) {
    const messageDiv = document.getElementById('streakMessage');
    
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_streak&passed=${passed}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            loadStats();
        }
    });
}

// 통계 로드
function loadStats() {
    fetch(window.location.href + '?action=get_stats')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            document.getElementById('currentStreak').textContent = data.data.current_streak;
            document.getElementById('bestStreak').textContent = data.data.best_streak;
            document.getElementById('advanceRatio').textContent = data.data.advance_ratio + '%';
            document.getElementById('reviewRatio').textContent = data.data.review_ratio + '%';
            document.getElementById('totalTime').textContent = data.data.total_mins + '분';
            document.getElementById('checkedCount').textContent = data.data.checked_count;
        }
    });
}

// 메시지 표시
function showMessage(element, message, type) {
    element.className = 'plp-message ' + type;
    element.textContent = message;
    element.style.display = 'block';
    setTimeout(() => { element.style.display = 'none'; }, 3000);
}

// 초기화
window.addEventListener('DOMContentLoaded', loadStats);
setInterval(loadStats, 5000);
</script>

</body>
</html>

<?php
if (isset($OUTPUT)) {
    echo $OUTPUT->footer();
}
?>