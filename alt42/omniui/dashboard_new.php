<?php
session_start();

// Include login check
require_once 'login_check.php';
requireLogin();

// Include configuration
require_once 'config.php';

// Get user's exam settings from database
function getUserExamSettings($userId) {
    try {
        $dsn = "mysql:host=" . ALT42T_DB_HOST . ";dbname=" . ALT42T_DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, ALT42T_DB_USER, ALT42T_DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->prepare("
            SELECT * FROM student_exam_settings
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching exam settings: " . $e->getMessage());
        return null;
    }
}

// Get user's exam settings
$examSettings = getUserExamSettings($_SESSION['user_id']);

// If no settings found, redirect to exam system
if (!$examSettings) {
    header('Location: exam_system.php');
    exit;
}

// Calculate D-Day
function getDdayText($examDate) {
    if (!$examDate) return 'D-Day';
    
    $today = new DateTime();
    $exam = new DateTime($examDate);
    $diff = $today->diff($exam);
    $days = (int)$diff->format('%R%a');
    
    if ($days > 0) return "D-{$days}";
    if ($days == 0) return 'D-Day';
    return 'D+' . abs($days);
}

$dday = getDdayText($examSettings['math_exam_date']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학킹 대시보드 - <?= htmlspecialchars($examSettings['name']) ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #F3F4F6;
            min-height: 100vh;
        }

        /* Header */
        .header {
            background: white;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 1rem 1.5rem;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #5B21B6;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: 600;
            color: #1F2937;
        }

        .user-school {
            font-size: 0.875rem;
            color: #6B7280;
        }

        .logout-btn {
            padding: 0.5rem 1rem;
            background: #EF4444;
            color: white;
            border: none;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            cursor: pointer;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: #DC2626;
        }

        /* Dashboard Content */
        .dashboard {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        /* Exam Info Card */
        .exam-info-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .exam-info-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .exam-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1F2937;
        }

        .dday-badge {
            padding: 0.75rem 1.5rem;
            background: #5B21B6;
            color: white;
            border-radius: 2rem;
            font-size: 1.25rem;
            font-weight: bold;
        }

        .dday-badge.today {
            background: #EF4444;
        }

        .dday-badge.past {
            background: #6B7280;
        }

        .exam-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .detail-item {
            padding: 1rem;
            background: #F9FAFB;
            border-radius: 0.5rem;
        }

        .detail-label {
            font-size: 0.875rem;
            color: #6B7280;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 600;
            color: #1F2937;
        }

        /* Study Status Card */
        .study-status-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 1rem;
        }

        .study-level-display {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #F3F0FF;
            border-radius: 0.5rem;
            border: 2px solid #5B21B6;
        }

        .study-level-icon {
            font-size: 2rem;
        }

        .study-level-info {
            flex: 1;
        }

        .study-level-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #5B21B6;
            margin-bottom: 0.25rem;
        }

        .study-level-desc {
            font-size: 0.875rem;
            color: #6B7280;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            border-radius: 0.75rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .action-icon {
            width: 48px;
            height: 48px;
            background: #F3F4F6;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .action-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1F2937;
            margin-bottom: 0.5rem;
        }

        .action-desc {
            font-size: 0.875rem;
            color: #6B7280;
        }

        /* Strategy Reminder */
        .strategy-reminder {
            background: linear-gradient(135deg, #FEF3C7 0%, #FED7AA 100%);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .strategy-reminder-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #92400E;
            margin-bottom: 0.5rem;
        }

        .strategy-reminder-text {
            color: #B45309;
            font-size: 1.125rem;
        }

        .study-level-map {
            'concept': '📚',
            'review': '🧠',
            'practice': '✏️'
        };
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <h1 class="logo">수학킹</h1>
            <div class="user-info">
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($examSettings['name']) ?></div>
                    <div class="user-school"><?= htmlspecialchars($examSettings['school']) ?> | <?= htmlspecialchars($examSettings['grade']) ?></div>
                </div>
                <a href="logout.php" class="logout-btn">로그아웃</a>
            </div>
        </div>
    </div>

    <!-- Dashboard Content -->
    <div class="dashboard">
        <!-- Exam Info Card -->
        <div class="exam-info-card">
            <div class="exam-info-header">
                <h2 class="exam-title"><?= htmlspecialchars($examSettings['exam_type']) ?></h2>
                <div class="dday-badge <?= $dday == 'D-Day' ? 'today' : (strpos($dday, 'D+') !== false ? 'past' : '') ?>">
                    수학 시험 <?= $dday ?>
                </div>
            </div>
            
            <div class="exam-details">
                <div class="detail-item">
                    <div class="detail-label">시험 기간</div>
                    <div class="detail-value">
                        <?= date('m/d', strtotime($examSettings['exam_start_date'])) ?> ~ 
                        <?= date('m/d', strtotime($examSettings['exam_end_date'])) ?>
                    </div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">수학 시험일</div>
                    <div class="detail-value"><?= date('Y년 m월 d일', strtotime($examSettings['math_exam_date'])) ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">시험 범위</div>
                    <div class="detail-value"><?= htmlspecialchars($examSettings['exam_scope'] ?: '미입력') ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">상태</div>
                    <div class="detail-value"><?= $examSettings['exam_status'] == 'confirmed' ? '확정' : '예상' ?></div>
                </div>
            </div>
        </div>

        <!-- Study Status Card -->
        <div class="study-status-card">
            <h3 class="section-title">현재 학습 단계</h3>
            <div class="study-level-display">
                <div class="study-level-icon">
                    <?php
                    $icons = ['concept' => '📚', 'review' => '🧠', 'practice' => '✏️'];
                    $titles = ['concept' => '개념공부', 'review' => '개념복습', 'practice' => '유형공부'];
                    $descs = [
                        'concept' => '기본 개념부터 차근차근 시작하는 단계',
                        'review' => '배운 개념들을 다시 정리하고 복습하는 단계',
                        'practice' => '다양한 문제 유형들을 학습하는 단계'
                    ];
                    
                    echo $icons[$examSettings['study_level']] ?? '📚';
                    ?>
                </div>
                <div class="study-level-info">
                    <div class="study-level-title"><?= $titles[$examSettings['study_level']] ?? '개념공부' ?></div>
                    <div class="study-level-desc"><?= $descs[$examSettings['study_level']] ?? '기본 개념부터 차근차근 시작하는 단계' ?></div>
                </div>
            </div>
        </div>

        <!-- Strategy Reminder -->
        <?php
        $daysLeft = (int)str_replace(['D-', 'D+'], ['', '-'], $dday);
        if ($daysLeft >= 0 && $daysLeft <= 5):
        ?>
        <div class="strategy-reminder">
            <h3 class="strategy-reminder-title">⚡ 라스트 청킹 시간이야!</h3>
            <p class="strategy-reminder-text">시험까지 <?= abs($daysLeft) ?>일 남았어! 지금이 진짜 게임 체인저 타이밍! 🔥</p>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="#" class="action-card">
                <div class="action-icon" style="background: #E0E7FF;">📖</div>
                <div class="action-title">학습 시작하기</div>
                <div class="action-desc">오늘의 학습을 시작해보세요</div>
            </a>
            
            <a href="#" class="action-card">
                <div class="action-icon" style="background: #DBEAFE;">📊</div>
                <div class="action-title">학습 현황</div>
                <div class="action-desc">나의 학습 진도를 확인해보세요</div>
            </a>
            
            <a href="#" class="action-card">
                <div class="action-icon" style="background: #FEE2E2;">⚡</div>
                <div class="action-title">라스트 청킹</div>
                <div class="action-desc">시험 직전 마무리 학습</div>
            </a>
            
            <a href="exam_system.php" class="action-card">
                <div class="action-icon" style="background: #F3F4F6;">⚙️</div>
                <div class="action-title">설정 변경</div>
                <div class="action-desc">시험 정보 수정하기</div>
            </a>
        </div>
    </div>
</body>
</html>