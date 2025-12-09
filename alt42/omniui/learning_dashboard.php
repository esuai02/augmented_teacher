<?php
/**
 * 개인화 학습 시스템 대시보드
 * 실제 데이터 기반 학습 관리 시스템
 */

session_start();
require_once 'config.php';

// 데이터베이스 연결
try {
    $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch(PDOException $e) {
    die("연결 실패: " . $e->getMessage());
}

// 사용자 확인
$userid = $_GET['userid'] ?? $_SESSION['userid'] ?? null;
if (!$userid) {
    header('Location: login.php');
    exit;
}

// 사용자 정보 조회
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.firstname, u.lastname, u.email
    FROM mdl_user u
    WHERE u.id = ? AND u.deleted = 0
");
$stmt->execute([$userid]);
$user = $stmt->fetch();

if (!$user) {
    die("사용자를 찾을 수 없습니다.");
}

$user_name = trim($user['firstname'] . ' ' . $user['lastname']);
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="개인화 학습 시스템">
    <title>학습 대시보드 - <?= htmlspecialchars($user_name) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --bg-primary: #ffffff;
            --bg-secondary: #f3f4f6;
            --text-primary: #111827;
            --text-secondary: #374151;
            --border: #e5e7eb;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
        }

        [data-theme="dark"] {
            --bg-primary: #111827;
            --bg-secondary: #1f2937;
            --text-primary: #f9fafb;
            --text-secondary: #e5e7eb;
            --border: #374151;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Pretendard', -apple-system, system-ui, sans-serif;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.5;
            transition: background-color 0.3s;
        }

        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0,0,0,0);
            border: 0;
        }

        .layout {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: var(--bg-secondary);
            border-right: 1px solid var(--border);
            padding: 1.5rem;
            position: fixed;
            height: 100vh;
            width: 280px;
            overflow-y: auto;
        }

        .main {
            margin-left: 280px;
            padding: 2rem;
        }

        .header {
            position: sticky;
            top: 0;
            background: var(--bg-primary);
            padding: 1rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid var(--border);
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-content h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .header-content p {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            border: 1px solid var(--border);
            background: var(--bg-secondary);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn:hover {
            background: var(--border);
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border: none;
        }

        .btn-primary:hover {
            opacity: 0.9;
        }

        .card {
            background: var(--bg-secondary);
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border: 1px solid var(--border);
        }

        .card h2 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-secondary);
            border-radius: 0.75rem;
            padding: 1.25rem;
            border: 1px solid var(--border);
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .stat-card .label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background: var(--primary);
            color: white;
        }

        .badge.success {
            background: var(--success);
        }

        .badge.warning {
            background: var(--warning);
        }

        .badge.error {
            background: var(--error);
        }

        .nav-list {
            list-style: none;
        }

        .nav-list li {
            margin-bottom: 0.5rem;
        }

        .nav-list a {
            display: block;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.2s;
        }

        .nav-list a:hover,
        .nav-list a[aria-current="page"] {
            background: var(--primary);
            color: white;
        }

        .task-list {
            list-style: none;
        }

        .task-item {
            background: var(--bg-primary);
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .task-item input[type="checkbox"] {
            margin-right: 0.75rem;
            width: 1.25rem;
            height: 1.25rem;
        }

        .task-content {
            flex: 1;
        }

        .task-title {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .task-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }

        .timer-display {
            font-size: 3rem;
            font-weight: 700;
            text-align: center;
            margin: 2rem 0;
            color: var(--primary);
        }

        .timer-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .loading-state,
        .error-state,
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-secondary);
        }

        .notification-count {
            background: var(--error);
            color: white;
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* 로딩 스피너 */
        .spinner {
            border: 3px solid var(--border);
            border-top: 3px solid var(--primary);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .skeleton {
            background: linear-gradient(90deg, var(--bg-secondary) 25%, var(--border) 50%, var(--bg-secondary) 75%);
            background-size: 200% 100%;
            animation: loading 1.5s ease-in-out infinite;
            border-radius: 0.5rem;
        }

        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .skeleton-text {
            height: 1rem;
            margin-bottom: 0.5rem;
        }

        .skeleton-stat {
            height: 4rem;
        }

        @media (max-width: 768px) {
            .layout {
                grid-template-columns: 1fr;
            }
            .sidebar {
                display: none;
            }
            .main {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body data-theme="light">
    <div class="layout">
        <aside class="sidebar" role="complementary">
            <div style="margin-bottom: 2rem;">
                <h2 style="font-size: 1.25rem; font-weight: 700; margin-bottom: 0.5rem;">
                    <?= htmlspecialchars($user_name) ?>
                </h2>
                <p style="font-size: 0.875rem; color: var(--text-secondary);">
                    <?= htmlspecialchars($user['email']) ?>
                </p>
            </div>

            <nav role="navigation" aria-label="메인 메뉴">
                <h3 style="font-size: 0.875rem; font-weight: 600; margin-bottom: 0.75rem; color: var(--text-secondary);">
                    학습 메뉴
                </h3>
                <ul class="nav-list">
                    <li><a href="#dashboard" aria-current="page" onclick="showSection('dashboard'); return false;">📊 대시보드</a></li>
                    <li><a href="#tasks" onclick="showSection('tasks'); return false;">✅ 학습 과제</a></li>
                    <li><a href="#timer" onclick="showSection('timer'); return false;">⏱️ 학습 타이머</a></li>
                    <li><a href="learning_tracker.php?userid=<?= $userid ?>">📝 학습 기록</a></li>
                    <li><a href="student_analytics.php?userid=<?= $userid ?>">📈 상세 분석</a></li>
                    <li><a href="student_dashboard.php?userid=<?= $userid ?>">🎯 전체 대시보드</a></li>
                </ul>
            </nav>
        </aside>

        <main class="main" role="main">
            <header class="header">
                <div class="header-content">
                    <h1>학습 대시보드</h1>
                    <p id="current-date"></p>
                </div>
                <div class="header-actions">
                    <button id="theme-toggle" class="btn" aria-label="테마 변경">
                        <span class="theme-icon">🌙</span>
                    </button>
                    <button id="notifications" class="btn" aria-label="알림">
                        🔔 <span class="notification-count" id="notification-count">0</span>
                    </button>
                </div>
            </header>

            <!-- 학습 통계 -->
            <div class="stats-grid" id="stats-container">
                <div class="stat-card">
                    <div class="skeleton skeleton-stat"></div>
                </div>
                <div class="stat-card">
                    <div class="skeleton skeleton-stat"></div>
                </div>
                <div class="stat-card">
                    <div class="skeleton skeleton-stat"></div>
                </div>
                <div class="stat-card">
                    <div class="skeleton skeleton-stat"></div>
                </div>
            </div>

            <!-- 오늘의 학습 과제 -->
            <div class="card" id="tasks-section">
                <h2>오늘의 학습 과제</h2>
                <div id="tasks-container">
                    <div class="spinner"></div>
                </div>
                <button class="btn btn-primary" onclick="addNewTask()" style="margin-top: 1rem;">
                    ➕ 과제 추가
                </button>
            </div>

            <!-- 학습 타이머 -->
            <div class="card" id="timer-section">
                <h2>학습 타이머</h2>
                <div class="timer-display" id="timer-display">00:00:00</div>
                <div class="timer-controls">
                    <button class="btn btn-primary" onclick="startTimer()">▶️ 시작</button>
                    <button class="btn" onclick="pauseTimer()">⏸️ 일시정지</button>
                    <button class="btn" onclick="resetTimer()">🔄 초기화</button>
                </div>
                <div style="margin-top: 1rem; text-align: center;">
                    <span class="badge" id="timer-status">준비</span>
                </div>
            </div>

            <!-- 최근 학습 기록 -->
            <div class="card" id="recent-section">
                <h2>최근 학습 기록</h2>
                <div id="recent-activities">
                    <div class="spinner"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        const userId = <?= $userid ?>;
        let timerInterval = null;
        let timerSeconds = 0;
        let timerRunning = false;

        // 페이지 로드시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentDate();

            // 병렬 로딩으로 성능 개선
            Promise.all([
                loadDashboardData(),
                loadTasks(),
                loadRecentActivities()
            ]).catch(error => {
                console.error('초기 데이터 로드 실패:', error);
            });

            // 3분마다 데이터 갱신 (1분에서 3분으로 변경)
            setInterval(loadDashboardData, 180000);
        });

        // 현재 날짜 표시
        function updateCurrentDate() {
            const now = new Date();
            const options = { year: 'numeric', month: 'long', day: 'numeric', weekday: 'long' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('ko-KR', options);
        }

        // 대시보드 데이터 로드
        async function loadDashboardData() {
            try {
                const response = await fetch(`get_learning_dashboard.php?user_id=${userId}`);
                const data = await response.json();

                if (data.success && data.stats) {
                    updateStats(data.stats);
                    updateNotifications(data.notifications || 0);
                } else {
                    console.error('데이터 형식 오류:', data);
                    // 기본값 표시
                    updateStats({
                        today_activities: 0,
                        study_time: 0,
                        study_streak: 0,
                        completed_tasks: 0
                    });
                }
            } catch (error) {
                console.error('데이터 로드 실패:', error);
                // 에러 시 기본값 표시
                updateStats({
                    today_activities: 0,
                    study_time: 0,
                    study_streak: 0,
                    completed_tasks: 0
                });
            }
        }

        // 통계 업데이트
        function updateStats(stats) {
            const container = document.getElementById('stats-container');
            container.innerHTML = `
                <div class="stat-card">
                    <div class="value">${stats.today_activities || 0}</div>
                    <div class="label">오늘 학습 활동</div>
                </div>
                <div class="stat-card">
                    <div class="value">${(stats.study_time || 0).toFixed(1)}</div>
                    <div class="label">학습 시간 (시간)</div>
                </div>
                <div class="stat-card">
                    <div class="value">${stats.study_streak || 0}</div>
                    <div class="label">연속 학습일</div>
                </div>
                <div class="stat-card">
                    <div class="value">${stats.completed_tasks || 0}</div>
                    <div class="label">완료한 과제</div>
                </div>
            `;
        }

        // 알림 업데이트
        function updateNotifications(count) {
            document.getElementById('notification-count').textContent = count || 0;
        }

        // 학습 과제 로드
        async function loadTasks() {
            const container = document.getElementById('tasks-container');
            try {
                const response = await fetch(`ajax_learning_tasks.php?action=list&user_id=${userId}`);
                if (!response.ok) throw new Error('Network error');

                const data = await response.json();

                if (data.success && data.tasks && data.tasks.length > 0) {
                    container.innerHTML = '<ul class="task-list">' +
                        data.tasks.map(task => `
                            <li class="task-item">
                                <input type="checkbox"
                                       ${task.completed ? 'checked' : ''}
                                       onchange="toggleTask(${task.id}, this.checked)">
                                <div class="task-content">
                                    <div class="task-title">${escapeHtml(task.title)}</div>
                                    <div class="task-meta">${task.due_date || '기한 없음'}</div>
                                </div>
                                <span class="badge ${task.priority === 'high' ? 'error' : task.priority === 'medium' ? 'warning' : 'success'}">
                                    ${task.priority === 'high' ? '긴급' : task.priority === 'medium' ? '보통' : '낮음'}
                                </span>
                            </li>
                        `).join('') + '</ul>';
                } else {
                    container.innerHTML = '<div class="empty-state">등록된 학습 과제가 없습니다.<br><small style="color: var(--text-secondary);">과제 추가 버튼을 눌러 새 과제를 만드세요.</small></div>';
                }
            } catch (error) {
                console.error('과제 로드 실패:', error);
                container.innerHTML = '<div class="empty-state">과제를 불러올 수 없습니다.<br><small style="color: var(--text-secondary);">네트워크 연결을 확인하세요.</small></div>';
            }
        }

        // 최근 활동 로드
        async function loadRecentActivities() {
            const container = document.getElementById('recent-activities');
            try {
                const response = await fetch(`get_learning_dashboard.php?user_id=${userId}`);
                if (!response.ok) throw new Error('Network error');

                const data = await response.json();

                if (data.success && data.activities && data.activities.length > 0) {
                    container.innerHTML = '<ul class="task-list">' +
                        data.activities.map(activity => `
                            <li class="task-item">
                                <div class="task-content">
                                    <div class="task-title">${escapeHtml(activity.page || '활동')}</div>
                                    <div class="task-meta">${formatDate(activity.last_activity)}</div>
                                </div>
                                <span class="badge">${activity.count}회</span>
                            </li>
                        `).join('') + '</ul>';
                } else {
                    container.innerHTML = '<div class="empty-state">최근 7일간 학습 기록이 없습니다.<br><small style="color: var(--text-secondary);">학습을 시작하면 여기에 표시됩니다.</small></div>';
                }
            } catch (error) {
                console.error('활동 로드 실패:', error);
                container.innerHTML = '<div class="empty-state">활동을 불러올 수 없습니다.</div>';
            }
        }

        // 과제 완료/미완료 토글
        async function toggleTask(taskId, completed) {
            try {
                const response = await fetch('ajax_learning_tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'toggle',
                        task_id: taskId,
                        completed: completed,
                        user_id: userId
                    })
                });

                const data = await response.json();
                if (data.success) {
                    loadDashboardData(); // 통계 갱신
                }
            } catch (error) {
                console.error('과제 토글 실패:', error);
            }
        }

        // 새 과제 추가
        function addNewTask() {
            const title = prompt('과제 제목을 입력하세요:');
            if (!title) return;

            const dueDate = prompt('마감일을 입력하세요 (YYYY-MM-DD):');
            const priority = prompt('우선순위를 입력하세요 (high/medium/low):', 'medium');

            saveTask(title, dueDate, priority);
        }

        // 과제 저장
        async function saveTask(title, dueDate, priority) {
            try {
                const response = await fetch('ajax_learning_tasks.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'add',
                        user_id: userId,
                        title: title,
                        due_date: dueDate,
                        priority: priority
                    })
                });

                const data = await response.json();
                if (data.success) {
                    loadTasks();
                    loadDashboardData();
                } else {
                    alert('과제 추가 실패: ' + (data.error || '알 수 없는 오류'));
                }
            } catch (error) {
                console.error('과제 저장 실패:', error);
                alert('과제 저장 중 오류가 발생했습니다.');
            }
        }

        // 타이머 시작
        function startTimer() {
            if (timerRunning) return;

            timerRunning = true;
            document.getElementById('timer-status').textContent = '진행중';
            document.getElementById('timer-status').className = 'badge success';

            timerInterval = setInterval(() => {
                timerSeconds++;
                updateTimerDisplay();
            }, 1000);

            // 서버에 타이머 시작 기록
            saveTimerEvent('start');
        }

        // 타이머 일시정지
        function pauseTimer() {
            if (!timerRunning) return;

            timerRunning = false;
            clearInterval(timerInterval);
            document.getElementById('timer-status').textContent = '일시정지';
            document.getElementById('timer-status').className = 'badge warning';

            // 서버에 타이머 일시정지 기록
            saveTimerEvent('pause');
        }

        // 타이머 초기화
        function resetTimer() {
            timerRunning = false;
            clearInterval(timerInterval);
            timerSeconds = 0;
            updateTimerDisplay();
            document.getElementById('timer-status').textContent = '준비';
            document.getElementById('timer-status').className = 'badge';

            // 서버에 타이머 초기화 기록
            saveTimerEvent('reset');
        }

        // 타이머 디스플레이 업데이트
        function updateTimerDisplay() {
            const hours = Math.floor(timerSeconds / 3600);
            const minutes = Math.floor((timerSeconds % 3600) / 60);
            const seconds = timerSeconds % 60;

            document.getElementById('timer-display').textContent =
                `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
        }

        // 타이머 이벤트 저장
        async function saveTimerEvent(action) {
            try {
                await fetch('ajax_learning_timer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: action,
                        user_id: userId,
                        duration: timerSeconds
                    })
                });
            } catch (error) {
                console.error('타이머 이벤트 저장 실패:', error);
            }
        }

        // 테마 토글
        document.getElementById('theme-toggle').addEventListener('click', function() {
            const body = document.body;
            const currentTheme = body.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';

            body.setAttribute('data-theme', newTheme);
            this.querySelector('.theme-icon').textContent = newTheme === 'light' ? '🌙' : '☀️';

            // 테마 설정 저장
            localStorage.setItem('theme', newTheme);
        });

        // 저장된 테마 불러오기
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.body.setAttribute('data-theme', savedTheme);
            document.querySelector('.theme-icon').textContent = savedTheme === 'light' ? '🌙' : '☀️';
        }

        // 유틸리티 함수
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);

            if (diff < 60) return '방금 전';
            if (diff < 3600) return `${Math.floor(diff / 60)}분 전`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}시간 전`;
            return date.toLocaleDateString('ko-KR');
        }

        function pad(num) {
            return num.toString().padStart(2, '0');
        }

        // 섹션 표시 함수
        function showSection(section) {
            // 모든 섹션 숨기기
            const sections = ['tasks', 'timer', 'recent'];
            sections.forEach(s => {
                const el = document.querySelector(`#${s}-section`);
                if (el) el.style.display = 'none';
            });

            // 선택한 섹션만 표시
            const targetSection = document.querySelector(`#${section}-section`);
            if (targetSection) {
                targetSection.style.display = 'block';
            }

            // 네비게이션 활성화 상태 변경
            document.querySelectorAll('.nav-list a').forEach(a => {
                a.removeAttribute('aria-current');
            });
            event.target.setAttribute('aria-current', 'page');
        }
    </script>
</body>
</html>
