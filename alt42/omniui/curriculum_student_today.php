<?php
/**
 * 커리큘럼 오케스트레이터 - 학생 오늘 학습
 * 오늘 할 학습 항목 표시 및 완료 처리
 */

session_start();
require_once __DIR__ . '/curriculum_orchestrator_config.php';

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$today = date('Y-m-d');

// 오늘의 학습 항목 조회
function getTodayItems($userId, $date) {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT i.*, p.exam_type, p.exam_date, p.daily_minutes,
               DATEDIFF(p.exam_date, CURDATE()) as days_until_exam
        FROM mdl_curriculum_items i
        JOIN mdl_curriculum_plan p ON i.planid = p.id
        WHERE p.userid = ? 
        AND i.due_date = ?
        AND p.status IN ('published', 'active')
        ORDER BY i.priority DESC, i.sequence
    ");
    
    $stmt->execute([$userId, $date]);
    return $stmt->fetchAll();
}

// 진행 상황 조회
function getTodayProgress($userId, $date) {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT pr.*, p.exam_type, p.exam_date
        FROM mdl_curriculum_progress pr
        JOIN mdl_curriculum_plan p ON pr.planid = p.id
        WHERE pr.userid = ? AND pr.date = ?
        LIMIT 1
    ");
    
    $stmt->execute([$userId, $date]);
    return $stmt->fetch();
}

// 주간 통계 조회
function getWeeklyStats($userId) {
    $pdo = getCurriculumDB();
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT date) as study_days,
            SUM(completed_items) as total_completed,
            SUM(actual_minutes) as total_minutes,
            AVG(completion_rate) as avg_completion
        FROM mdl_curriculum_progress
        WHERE userid = ? 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND completed_items > 0
    ");
    
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

$todayItems = getTodayItems($userId, $today);
$todayProgress = getTodayProgress($userId, $today);
$weeklyStats = getWeeklyStats($userId);

// 항목 분류
$conceptItems = array_filter($todayItems, function($item) {
    return $item['item_type'] == 'concept';
});

$reviewItems = array_filter($todayItems, function($item) {
    return $item['item_type'] == 'review';
});
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>오늘의 학습 - 커리큘럼 시스템</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary: #6c5ce7;
            --success: #00b894;
            --warning: #fdcb6e;
            --danger: #d63031;
            --dark: #2d3436;
            --light: #dfe6e9;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Noto Sans KR', sans-serif;
        }
        
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .d-day-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.2rem;
        }
        
        .d-day-urgent {
            background: linear-gradient(135deg, #ff6b6b, #ff8787);
            color: white;
        }
        
        .d-day-warning {
            background: linear-gradient(135deg, #feca57, #ffdd59);
            color: #2d3436;
        }
        
        .d-day-safe {
            background: linear-gradient(135deg, #54a0ff, #74b9ff);
            color: white;
        }
        
        .progress-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .progress-bar-custom {
            height: 30px;
            border-radius: 15px;
            background: #f1f3f4;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            border-radius: 15px;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .study-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 5px solid #667eea;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .study-item:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .study-item.completed {
            opacity: 0.7;
            border-left-color: #00b894;
            background: #f8f9fa;
        }
        
        .study-item.review {
            border-left-color: #fdcb6e;
        }
        
        .item-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-concept {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .badge-review {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .badge-difficulty-1 { background: #e8f5e9; color: #4caf50; }
        .badge-difficulty-2 { background: #fff9c4; color: #f9a825; }
        .badge-difficulty-3 { background: #ffebee; color: #e53935; }
        .badge-difficulty-4 { background: #f3e5f5; color: #8e24aa; }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }
        
        .stat-label {
            color: #95a5a6;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        .complete-btn {
            background: linear-gradient(135deg, #00b894, #00cec9);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .complete-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,184,148,0.3);
        }
        
        .complete-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .timer-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary);
            margin: 10px 0;
        }
        
        .motivational-quote {
            background: linear-gradient(135deg, #f093fb, #f5576c);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            text-align: center;
            font-style: italic;
        }
        
        .floating-timer {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: white;
            border-radius: 50%;
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 1000;
        }
        
        .section-divider {
            border-top: 2px dashed #e0e0e0;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <!-- 헤더 -->
        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-3">
                        <i class="bi bi-calendar-check"></i> 오늘의 학습 계획
                    </h2>
                    <p class="text-muted mb-2">
                        <?php echo date('Y년 m월 d일'); ?> | 
                        <?php echo $_SESSION['fullname'] ?? '학생'; ?>님
                    </p>
                    <?php if ($todayProgress): ?>
                        <div class="d-day-badge <?php 
                            $days = $todayProgress['days_until_exam'] ?? 30;
                            echo $days <= 7 ? 'd-day-urgent' : ($days <= 14 ? 'd-day-warning' : 'd-day-safe');
                        ?>">
                            📚 <?php echo $todayProgress['exam_type']; ?> D-<?php echo max(0, $days); ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-primary" onclick="location.href='curriculum_student_stats.php'">
                        <i class="bi bi-graph-up"></i> 학습 통계
                    </button>
                    <button class="btn btn-outline-secondary" onclick="location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> 새로고침
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 동기부여 문구 -->
        <div class="motivational-quote">
            <i class="bi bi-quote"></i> 
            <?php
            $quotes = [
                "오늘의 노력이 내일의 성공을 만듭니다!",
                "꾸준함이 천재를 이깁니다!",
                "한 걸음씩 나아가면 목표에 도달합니다!",
                "포기하지 마세요, 당신은 할 수 있습니다!",
                "오늘도 최선을 다해 화이팅!"
            ];
            echo $quotes[array_rand($quotes)];
            ?>
            <i class="bi bi-quote"></i>
        </div>
        
        <!-- 통계 카드 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo count($todayItems); ?>
                </div>
                <div class="stat-label">오늘의 학습 항목</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php 
                    $completedToday = count(array_filter($todayItems, function($item) {
                        return $item['status'] == 'completed';
                    }));
                    echo $completedToday;
                    ?>
                </div>
                <div class="stat-label">완료한 항목</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo $todayProgress['daily_minutes'] ?? 120; ?>분
                </div>
                <div class="stat-label">목표 학습시간</div>
            </div>
            <div class="stat-card">
                <div class="stat-value">
                    <?php echo $weeklyStats['study_days'] ?? 0; ?>일
                </div>
                <div class="stat-label">이번주 학습일</div>
            </div>
        </div>
        
        <!-- 진행률 -->
        <?php if ($todayProgress): ?>
        <div class="progress-card">
            <h5 class="mb-3">오늘의 진행률</h5>
            <div class="progress-bar-custom">
                <div class="progress-fill" style="width: <?php echo $todayProgress['completion_rate'] ?? 0; ?>%">
                    <?php echo number_format($todayProgress['completion_rate'] ?? 0, 1); ?>%
                </div>
            </div>
            <div class="mt-3 d-flex justify-content-between">
                <span>완료: <?php echo $todayProgress['completed_items'] ?? 0; ?>개</span>
                <span>남음: <?php echo ($todayProgress['planned_items'] ?? 0) - ($todayProgress['completed_items'] ?? 0); ?>개</span>
                <span>학습시간: <?php echo $todayProgress['actual_minutes'] ?? 0; ?>분</span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 개념학습 섹션 -->
        <div class="progress-card">
            <h4 class="mb-4">
                <span class="badge-concept">개념학습</span>
                <small class="text-muted ms-2"><?php echo count($conceptItems); ?>개</small>
            </h4>
            
            <?php if (empty($conceptItems)): ?>
                <p class="text-muted text-center py-3">오늘의 개념학습이 없습니다.</p>
            <?php else: ?>
                <?php foreach ($conceptItems as $item): ?>
                <div class="study-item <?php echo $item['status'] == 'completed' ? 'completed' : ''; ?>" 
                     data-item-id="<?php echo $item['id']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-2">
                                <?php echo htmlspecialchars($item['title']); ?>
                                <?php if ($item['status'] == 'completed'): ?>
                                    <i class="bi bi-check-circle-fill text-success ms-2"></i>
                                <?php endif; ?>
                            </h6>
                            <p class="text-muted mb-2 small">
                                <?php echo htmlspecialchars($item['description'] ?? ''); ?>
                            </p>
                            <div>
                                <span class="item-badge badge-concept">개념</span>
                                <span class="item-badge badge-difficulty-<?php echo $item['difficulty']; ?>">
                                    난이도 <?php echo $item['difficulty']; ?>
                                </span>
                                <span class="text-muted small ms-2">
                                    <i class="bi bi-clock"></i> <?php echo $item['est_minutes']; ?>분
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($item['status'] != 'completed'): ?>
                                <button class="complete-btn" onclick="completeItem(<?php echo $item['id']; ?>)">
                                    <i class="bi bi-check"></i> 완료
                                </button>
                            <?php else: ?>
                                <span class="text-success">
                                    <i class="bi bi-check-all"></i> 완료됨
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="section-divider"></div>
        
        <!-- 복습 섹션 -->
        <div class="progress-card">
            <h4 class="mb-4">
                <span class="badge-review">오답 복습</span>
                <small class="text-muted ms-2"><?php echo count($reviewItems); ?>개</small>
            </h4>
            
            <?php if (empty($reviewItems)): ?>
                <p class="text-muted text-center py-3">오늘의 복습 항목이 없습니다.</p>
            <?php else: ?>
                <?php foreach ($reviewItems as $item): ?>
                <div class="study-item review <?php echo $item['status'] == 'completed' ? 'completed' : ''; ?>" 
                     data-item-id="<?php echo $item['id']; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h6 class="mb-2">
                                <?php echo htmlspecialchars($item['title']); ?>
                                <?php if ($item['status'] == 'completed'): ?>
                                    <i class="bi bi-check-circle-fill text-success ms-2"></i>
                                <?php endif; ?>
                            </h6>
                            <p class="text-muted mb-2 small">
                                <?php echo htmlspecialchars($item['description'] ?? ''); ?>
                            </p>
                            <div>
                                <span class="item-badge badge-review">복습</span>
                                <span class="item-badge badge-difficulty-<?php echo $item['difficulty']; ?>">
                                    난이도 <?php echo $item['difficulty']; ?>
                                </span>
                                <span class="text-muted small ms-2">
                                    <i class="bi bi-clock"></i> <?php echo $item['est_minutes']; ?>분
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <?php if ($item['status'] != 'completed'): ?>
                                <button class="complete-btn" onclick="completeItem(<?php echo $item['id']; ?>)">
                                    <i class="bi bi-check"></i> 완료
                                </button>
                            <?php else: ?>
                                <span class="text-success">
                                    <i class="bi bi-check-all"></i> 완료됨
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- 플로팅 타이머 -->
    <div class="floating-timer" onclick="toggleTimer()">
        <div id="timer-display">
            <i class="bi bi-play-circle" style="font-size: 2rem; color: var(--primary);"></i>
        </div>
    </div>
    
    <!-- 완료 모달 -->
    <div class="modal fade" id="completeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">학습 완료</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>이 항목을 완료하셨나요?</p>
                    <div class="mb-3">
                        <label class="form-label">실제 학습 시간(분)</label>
                        <input type="number" class="form-control" id="actualMinutes" min="1" max="180">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">취소</button>
                    <button type="button" class="btn btn-success" onclick="confirmComplete()">완료</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentItemId = null;
        let timerRunning = false;
        let timerSeconds = 0;
        let timerInterval = null;
        
        // 항목 완료 처리
        function completeItem(itemId) {
            currentItemId = itemId;
            const item = document.querySelector(`[data-item-id="${itemId}"]`);
            const estimatedMinutes = parseInt(item.querySelector('.bi-clock').parentElement.textContent);
            
            document.getElementById('actualMinutes').value = estimatedMinutes;
            new bootstrap.Modal(document.getElementById('completeModal')).show();
        }
        
        // 완료 확인
        function confirmComplete() {
            const actualMinutes = document.getElementById('actualMinutes').value;
            
            if (!actualMinutes || actualMinutes < 1) {
                alert('학습 시간을 입력해주세요.');
                return;
            }
            
            $.ajax({
                url: 'curriculum_api.php',
                method: 'POST',
                data: {
                    action: 'complete_item',
                    item_id: currentItemId,
                    actual_minutes: actualMinutes
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // UI 업데이트
                        const item = document.querySelector(`[data-item-id="${currentItemId}"]`);
                        item.classList.add('completed');
                        
                        const btn = item.querySelector('.complete-btn');
                        btn.outerHTML = '<span class="text-success"><i class="bi bi-check-all"></i> 완료됨</span>';
                        
                        // 체크 아이콘 추가
                        const title = item.querySelector('h6');
                        if (!title.querySelector('.bi-check-circle-fill')) {
                            title.innerHTML += ' <i class="bi bi-check-circle-fill text-success ms-2"></i>';
                        }
                        
                        // 통계 업데이트
                        updateStats();
                        
                        // 모달 닫기
                        bootstrap.Modal.getInstance(document.getElementById('completeModal')).hide();
                        
                        // 축하 메시지
                        showCongrats();
                    } else {
                        alert('오류: ' + response.message);
                    }
                },
                error: function() {
                    alert('서버 오류가 발생했습니다.');
                }
            });
        }
        
        // 통계 업데이트
        function updateStats() {
            // 완료 개수 업데이트
            const completedItems = document.querySelectorAll('.study-item.completed').length;
            const totalItems = document.querySelectorAll('.study-item').length;
            const completionRate = (completedItems / totalItems * 100).toFixed(1);
            
            // 진행률 바 업데이트
            const progressFill = document.querySelector('.progress-fill');
            if (progressFill) {
                progressFill.style.width = completionRate + '%';
                progressFill.textContent = completionRate + '%';
            }
        }
        
        // 축하 메시지
        function showCongrats() {
            const messages = [
                "잘했어요! 💪",
                "훌륭해요! 🎉",
                "대단해요! 👏",
                "멋져요! ⭐",
                "최고예요! 🏆"
            ];
            
            const toast = document.createElement('div');
            toast.className = 'position-fixed top-50 start-50 translate-middle';
            toast.style.zIndex = '9999';
            toast.innerHTML = `
                <div class="alert alert-success shadow-lg" style="min-width: 200px; text-align: center;">
                    <h4 class="mb-0">${messages[Math.floor(Math.random() * messages.length)]}</h4>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.transition = 'opacity 0.5s';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 2000);
        }
        
        // 타이머 토글
        function toggleTimer() {
            if (timerRunning) {
                stopTimer();
            } else {
                startTimer();
            }
        }
        
        // 타이머 시작
        function startTimer() {
            timerRunning = true;
            document.getElementById('timer-display').innerHTML = '00:00';
            
            timerInterval = setInterval(() => {
                timerSeconds++;
                const minutes = Math.floor(timerSeconds / 60);
                const seconds = timerSeconds % 60;
                document.getElementById('timer-display').innerHTML = 
                    `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            }, 1000);
        }
        
        // 타이머 정지
        function stopTimer() {
            timerRunning = false;
            clearInterval(timerInterval);
            timerSeconds = 0;
            document.getElementById('timer-display').innerHTML = 
                '<i class="bi bi-play-circle" style="font-size: 2rem; color: var(--primary);"></i>';
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            updateStats();
            
            // 10분마다 자동 새로고침
            setInterval(() => {
                if (!timerRunning) {
                    location.reload();
                }
            }, 600000);
        });
    </script>
</body>
</html>