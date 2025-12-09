<?php
/**
 * PLP 완전 작동 버전 - 모든 기능 구현
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/plp_full.php
 */

// 에러 표시 (개발 중)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Moodle 설정 로드
$moodle_config = '/home/moodle/public_html/moodle/config.php';
if (file_exists($moodle_config)) {
    require_once($moodle_config);
    require_login();
    $userid = $USER->id;
} else {
    // 로컬 테스트용
    session_start();
    $userid = $_SESSION['user_id'] ?? 2; // 기본값 2 (테스트 사용자)
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

// AJAX 처리
if (isset($_POST['action']) || isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? $_GET['action'];
    
    try {
        switch ($action) {
            case 'save_summary':
                $summary = $_POST['summary'] ?? '';
                $schedule_id = $_POST['schedule_id'] ?? 0;
                
                if (mb_strlen($summary) < 30 || mb_strlen($summary) > 60) {
                    echo json_encode(['success' => false, 'message' => '요약은 30-60자 사이여야 합니다.']);
                    exit;
                }
                
                // 오늘 날짜의 레코드 업데이트 또는 생성
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
                
                echo json_encode(['success' => true, 'message' => '요약이 저장되었습니다!']);
                break;
                
            case 'tag_error':
                $problem_id = $_POST['problem_id'] ?? '';
                $tags = $_POST['tags'] ?? [];
                $difficulty = $_POST['difficulty'] ?? 1;
                
                if (empty($problem_id) || empty($tags)) {
                    echo json_encode(['success' => false, 'message' => '문제 ID와 태그를 입력하세요.']);
                    exit;
                }
                
                $tags_str = is_array($tags) ? implode(',', $tags) : $tags;
                
                $stmt = $pdo->prepare("
                    INSERT INTO mdl_plp_error_tags 
                    (userid, problem_id, tags, difficulty, timecreated)
                    VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())
                ");
                $stmt->execute([$userid, $problem_id, $tags_str, $difficulty]);
                
                echo json_encode(['success' => true, 'message' => '오답 태그가 추가되었습니다!']);
                break;
                
            case 'update_streak':
                $passed = $_POST['passed'] ?? 0;
                $today = date('Y-m-d');
                
                // 현재 연속 기록 조회
                $stmt = $pdo->prepare("
                    SELECT * FROM mdl_plp_streak_tracker 
                    WHERE userid = ?
                ");
                $stmt->execute([$userid]);
                $streak = $stmt->fetch();
                
                if ($passed) {
                    // 통과한 경우
                    if ($streak) {
                        $last_date = $streak['last_pass_date'];
                        $current = $streak['current_streak'];
                        
                        // 연속인지 확인
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
                        // 첫 기록
                        $stmt = $pdo->prepare("
                            INSERT INTO mdl_plp_streak_tracker 
                            (userid, current_streak, best_streak, last_pass_date, timemodified)
                            VALUES (?, 1, 1, ?, UNIX_TIMESTAMP())
                        ");
                        $stmt->execute([$userid, $today]);
                    }
                } else {
                    // 실패한 경우 - 연속 기록 리셋
                    if ($streak) {
                        $stmt = $pdo->prepare("
                            UPDATE mdl_plp_streak_tracker 
                            SET current_streak = 0, timemodified = UNIX_TIMESTAMP()
                            WHERE userid = ?
                        ");
                        $stmt->execute([$userid]);
                    }
                }
                
                echo json_encode(['success' => true, 'message' => '연속 통과 기록이 업데이트되었습니다!']);
                break;
                
            case 'log_practice':
                $problem_ids = $_POST['problem_ids'] ?? [];
                $today = date('Y-m-d');
                
                if (empty($problem_ids)) {
                    echo json_encode(['success' => false, 'message' => '문제를 선택하세요.']);
                    exit;
                }
                
                $ids_str = is_array($problem_ids) ? implode(',', $problem_ids) : $problem_ids;
                $count = is_array($problem_ids) ? count($problem_ids) : 1;
                
                $stmt = $pdo->prepare("
                    INSERT INTO mdl_plp_practice_checks 
                    (userid, date, problem_ids, checked_count, timecreated)
                    VALUES (?, ?, ?, ?, UNIX_TIMESTAMP())
                ");
                $stmt->execute([$userid, $today, $ids_str, $count]);
                
                echo json_encode(['success' => true, 'message' => $count . '개 문제가 체크되었습니다!']);
                break;
                
            case 'get_stats':
                $today = date('Y-m-d');
                
                // 연속 통과 기록
                $stmt = $pdo->prepare("
                    SELECT current_streak, best_streak 
                    FROM mdl_plp_streak_tracker 
                    WHERE userid = ?
                ");
                $stmt->execute([$userid]);
                $streak = $stmt->fetch() ?: ['current_streak' => 0, 'best_streak' => 0];
                
                // 오늘의 학습 시간 비율
                $stmt = $pdo->prepare("
                    SELECT advance_mins, review_mins, summary_count 
                    FROM mdl_plp_learning_records 
                    WHERE userid = ? AND date = ?
                ");
                $stmt->execute([$userid, $today]);
                $today_data = $stmt->fetch() ?: ['advance_mins' => 42, 'review_mins' => 18, 'summary_count' => 0];
                
                $total_mins = $today_data['advance_mins'] + $today_data['review_mins'];
                $advance_ratio = $total_mins > 0 ? round(($today_data['advance_mins'] / $total_mins) * 100) : 70;
                $review_ratio = 100 - $advance_ratio;
                
                // 오늘 체크한 문제 수
                $stmt = $pdo->prepare("
                    SELECT checked_count 
                    FROM mdl_plp_practice_checks 
                    WHERE userid = ? AND date = ?
                ");
                $stmt->execute([$userid, $today]);
                $practice = $stmt->fetch();
                $checked_count = $practice['checked_count'] ?? 0;
                
                // 오답 태그 수
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as error_count 
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
                        'checked_count' => $checked_count,
                        'error_count' => $errors['error_count'] ?? 0
                    ]
                ]);
                break;
                
            case 'get_errors':
                // 최근 오답 태그 목록
                $stmt = $pdo->prepare("
                    SELECT problem_id, tags, difficulty, timecreated 
                    FROM mdl_plp_error_tags 
                    WHERE userid = ? 
                    ORDER BY timecreated DESC 
                    LIMIT 10
                ");
                $stmt->execute([$userid]);
                $errors = $stmt->fetchAll();
                
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

// 페이지 설정
if (isset($PAGE)) {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/local/augmented_teacher/alt42/omniui/plp_full.php');
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
    <title>Personal Learning Panel</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans KR', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .plp-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .plp-header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .plp-header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .plp-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .plp-card h2 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: #2c3e50;
        }

        .plp-textarea {
            width: 100%;
            min-height: 120px;
            padding: 15px;
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            font-size: 16px;
            resize: vertical;
            transition: all 0.3s;
        }

        .plp-textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .plp-button {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .plp-button:hover {
            transform: translateY(-2px);
        }

        .plp-button:active {
            transform: translateY(0);
        }

        .plp-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .plp-stat-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }

        .plp-stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }

        .plp-stat-label {
            color: #5a6c7d;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .plp-char-count {
            font-size: 14px;
            color: #5a6c7d;
            margin-top: 10px;
        }

        .plp-char-count.valid {
            color: #00b894;
        }

        .plp-char-count.invalid {
            color: #d63031;
        }

        .plp-message {
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
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

        .plp-tag-input {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .plp-tag-input input {
            flex: 1;
            padding: 10px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 16px;
        }

        .plp-tag-input select {
            padding: 10px;
            border: 2px solid #e0e6ed;
            border-radius: 8px;
            font-size: 16px;
        }

        .plp-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .plp-tag {
            background: #e0e6ed;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            color: #2c3e50;
        }

        .plp-problem-list {
            max-height: 300px;
            overflow-y: auto;
            border: 2px solid #e0e6ed;
            border-radius: 10px;
            padding: 15px;
        }

        .plp-problem-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin-bottom: 8px;
            background: #f8f9fa;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .plp-problem-item:hover {
            background: #e9ecef;
        }

        .plp-problem-item input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-right: 12px;
            cursor: pointer;
        }

        .plp-problem-item label {
            flex: 1;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .plp-header h1 {
                font-size: 2rem;
            }
            
            .plp-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<div class="plp-container">
    <div class="plp-header">
        <h1>🎯 Personal Learning Panel</h1>
        <p>오늘도 한 걸음 더 성장하는 하루!</p>
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
            <div class="plp-tag-input">
                <input type="text" id="problemId" placeholder="문제 번호">
                <select id="difficulty">
                    <option value="1">쉬움</option>
                    <option value="2">보통</option>
                    <option value="3">어려움</option>
                </select>
            </div>
            <div class="plp-tag-input">
                <input type="text" id="tagInput" placeholder="태그 입력 (쉼표로 구분)">
                <button onclick="addErrorTag()" class="plp-button">추가</button>
            </div>
            <div id="errorMessage" class="plp-message"></div>
            <div id="errorTags" class="plp-tags"></div>
        </div>

        <!-- 문제 체크 -->
        <div class="plp-card">
            <h2>✅ 문제 풀이 체크</h2>
            <div class="plp-problem-list">
                <div class="plp-problem-item">
                    <input type="checkbox" id="prob1" value="calc_001">
                    <label for="prob1">미적분 문제 1 - 극한</label>
                </div>
                <div class="plp-problem-item">
                    <input type="checkbox" id="prob2" value="calc_002">
                    <label for="prob2">미적분 문제 2 - 미분</label>
                </div>
                <div class="plp-problem-item">
                    <input type="checkbox" id="prob3" value="calc_003">
                    <label for="prob3">미적분 문제 3 - 적분</label>
                </div>
                <div class="plp-problem-item">
                    <input type="checkbox" id="prob4" value="calc_004">
                    <label for="prob4">미적분 문제 4 - 응용</label>
                </div>
                <div class="plp-problem-item">
                    <input type="checkbox" id="prob5" value="calc_005">
                    <label for="prob5">미적분 문제 5 - 심화</label>
                </div>
            </div>
            <button onclick="savePracticeChecks()" class="plp-button" style="width: 100%; margin-top: 15px;">
                저장하기
            </button>
            <div id="practiceMessage" class="plp-message"></div>
        </div>
    </div>

    <!-- 연속 통과 업데이트 -->
    <div class="plp-card">
        <h2>🔥 오늘의 학습 결과</h2>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <button onclick="updateStreak(1)" class="plp-button" style="background: linear-gradient(135deg, #00b894, #00cec9);">
                ✅ 통과 (Pass)
            </button>
            <button onclick="updateStreak(0)" class="plp-button" style="background: linear-gradient(135deg, #d63031, #74b9ff);">
                ❌ 재도전 필요
            </button>
        </div>
        <div id="streakMessage" class="plp-message"></div>
    </div>

    <!-- 학습 통계 -->
    <div class="plp-card">
        <h2>📊 학습 현황</h2>
        <div class="plp-grid">
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
                <div class="plp-stat-value" id="summaryCount">0</div>
                <div class="plp-stat-label">요약 작성</div>
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
    
    if (length >= 30 && length <= 60) {
        counter.className = 'plp-char-count valid';
    } else {
        counter.className = 'plp-char-count invalid';
    }
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
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
    })
    .catch(error => {
        showMessage(messageDiv, '저장 중 오류가 발생했습니다.', 'error');
    });
});

// 오답 태그 추가
function addErrorTag() {
    const problemId = document.getElementById('problemId').value;
    const tagInput = document.getElementById('tagInput').value;
    const difficulty = document.getElementById('difficulty').value;
    const messageDiv = document.getElementById('errorMessage');
    
    if (!problemId || !tagInput) {
        showMessage(messageDiv, '문제 번호와 태그를 입력하세요.', 'error');
        return;
    }
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=tag_error&problem_id=${problemId}&tags=${encodeURIComponent(tagInput)}&difficulty=${difficulty}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            document.getElementById('problemId').value = '';
            document.getElementById('tagInput').value = '';
            loadErrorTags();
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
    
    const problemIds = Array.from(checkboxes).map(cb => cb.value);
    
    fetch(window.location.href, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=log_practice&problem_ids=' + problemIds.join(',')
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
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_streak&passed=${passed}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage(messageDiv, data.message, 'success');
            loadStats();
        } else {
            showMessage(messageDiv, data.message, 'error');
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
            document.getElementById('summaryCount').textContent = data.data.summary_count;
            document.getElementById('checkedCount').textContent = data.data.checked_count;
        }
    });
}

// 오답 태그 로드
function loadErrorTags() {
    fetch(window.location.href + '?action=get_errors')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.data) {
            const container = document.getElementById('errorTags');
            container.innerHTML = '';
            
            data.data.forEach(error => {
                const tags = error.tags.split(',');
                tags.forEach(tag => {
                    const span = document.createElement('span');
                    span.className = 'plp-tag';
                    span.textContent = tag.trim();
                    container.appendChild(span);
                });
            });
        }
    });
}

// 메시지 표시
function showMessage(element, message, type) {
    element.className = 'plp-message ' + type;
    element.textContent = message;
    element.style.display = 'block';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, 3000);
}

// 페이지 로드 시 초기화
window.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadErrorTags();
});

// 5초마다 통계 새로고침
setInterval(loadStats, 5000);
</script>

</body>
</html>

<?php
if (isset($OUTPUT)) {
    echo $OUTPUT->footer();
}
?>