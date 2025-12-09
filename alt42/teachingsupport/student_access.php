<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET['studentid'] ?? $USER->id;
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;

// 학생 정보 가져오기
$student = $DB->get_record('user', array('id' => $studentid));
if (!$student) {
    print_error('학생 정보를 찾을 수 없습니다.');
}

// 권한 확인 (본인이거나 관리자)
$context = context_system::instance();
if ($studentid != $USER->id && !has_capability('moodle/site:config', $context)) {
    print_error('접근 권한이 없습니다.');
}

// 통계 데이터 가져오기
$today_start = strtotime('today');
$stats = new stdClass();
$stats->total_interactions = $DB->count_records('ktm_teaching_interactions', array('userid' => $studentid));
$stats->completed_today = $DB->count_records_select('ktm_teaching_interactions', 
    "userid = ? AND status = 'completed' AND timecreated >= ?", array($studentid, $today_start));
$stats->total_events = $DB->count_records('ktm_teaching_events', array('userid' => $studentid));
$stats->messages_count = $DB->count_records_select('messages', 
    "useridto = ? AND (subject LIKE '%문제 해설%' OR subject LIKE '%하이튜터링%')", array($studentid));
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 나의 하이튜터링 학습 현황</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        .header h1 {
            font-size: 32px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .header p {
            opacity: 0.9;
            font-size: 18px;
        }

        .dashboard {
            padding: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 10px;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #718096;
            font-size: 14px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: #f7fafc;
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            border-color: #4299e1;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(66, 153, 225, 0.2);
        }

        .action-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .action-title {
            font-size: 18px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .action-desc {
            color: #718096;
            font-size: 14px;
            line-height: 1.4;
        }

        .recent-activity {
            margin-top: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-list {
            background: #f8fafc;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .activity-item {
            padding: 15px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: white;
        }

        .activity-icon.completed {
            background: #48bb78;
        }

        .activity-icon.message {
            background: #4299e1;
        }

        .activity-icon.start {
            background: #ed8936;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 500;
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .activity-desc {
            color: #718096;
            font-size: 12px;
        }

        .activity-time {
            color: #a0aec0;
            font-size: 12px;
        }

        .no-activity {
            text-align: center;
            padding: 40px;
            color: #a0aec0;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            text-decoration: none;
            color: #4a5568;
            font-size: 14px;
        }

        .pagination a:hover {
            background: #f7fafc;
        }

        .pagination .current {
            background: #4299e1;
            color: white;
            border-color: #4299e1;
        }

        @media (max-width: 768px) {
            .container {
                margin: 0;
                border-radius: 0;
            }

            .header {
                padding: 20px;
            }

            .header h1 {
                font-size: 24px;
                flex-direction: column;
                gap: 10px;
            }

            .dashboard {
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <span>📚</span>
                나의 하이튜터링 학습 현황
            </h1>
            <p><?php echo fullname($student); ?>님의 개인 학습 대시보드</p>
        </div>

        <div class="dashboard">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">📊</div>
                    <div class="stat-number"><?php echo $stats->total_interactions; ?></div>
                    <div class="stat-label">전체 문제 해설</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-number"><?php echo $stats->completed_today; ?></div>
                    <div class="stat-label">오늘 완료</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📨</div>
                    <div class="stat-number"><?php echo $stats->messages_count; ?></div>
                    <div class="stat-label">받은 메시지</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🎯</div>
                    <div class="stat-number"><?php echo $stats->total_events; ?></div>
                    <div class="stat-label">학습 활동</div>
                </div>
            </div>

            <div class="quick-actions">
                <a href="student_inbox.php?studentid=<?php echo $studentid; ?>" class="action-card">
                    <div class="action-icon">📬</div>
                    <div class="action-title">메시지함</div>
                    <div class="action-desc">선생님이 보낸 풀이 메시지를 확인하세요</div>
                </a>
                <a href="interaction_history.php?userid=<?php echo $USER->id; ?>&studentid=<?php echo $studentid; ?>" class="action-card">
                    <div class="action-icon">📈</div>
                    <div class="action-title">학습 기록</div>
                    <div class="action-desc">나의 문제 해설 기록을 확인하세요</div>
                </a>
                <a href="teacher_explanation_interface.php" class="action-card">
                    <div class="action-icon">🎓</div>
                    <div class="action-title">최근 설명</div>
                    <div class="action-desc">가장 최근에 받은 문제 설명을 확인하세요</div>
                </a>
            </div>

            <div class="recent-activity">
                <h2 class="section-title">
                    <span>🕐</span>
                    최근 학습 활동
                </h2>
                <div class="activity-list" id="activityList">
                    <!-- 활동 내역이 여기에 동적으로 로드됩니다 -->
                </div>
            </div>
        </div>
    </div>

    <script>
        const studentId = <?php echo $studentid; ?>;
        const currentPage = <?php echo $page; ?>;
        const perPage = 10;

        // 페이지 로드 시 최근 활동 불러오기
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentActivity();
        });

        // 최근 활동 로드
        async function loadRecentActivity() {
            try {
                const response = await fetch(`get_student_activity.php?studentid=${studentId}&page=${currentPage}&perpage=${perPage}`);
                const data = await response.json();
                
                if (data.success) {
                    displayActivity(data.activities);
                } else {
                    showNoActivity();
                }
            } catch (error) {
                console.error('Error loading activity:', error);
                showNoActivity();
            }
        }

        // 활동 목록 표시
        function displayActivity(activities) {
            const activityList = document.getElementById('activityList');
            
            if (activities.length === 0) {
                showNoActivity();
                return;
            }

            activityList.innerHTML = activities.map(activity => `
                <div class="activity-item">
                    <div class="activity-icon ${activity.type}">
                        ${getActivityIcon(activity.type)}
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">${activity.title}</div>
                        <div class="activity-desc">${activity.description}</div>
                    </div>
                    <div class="activity-time">
                        ${formatTime(activity.timecreated)}
                    </div>
                </div>
            `).join('');
        }

        // 활동 아이콘 가져오기
        function getActivityIcon(type) {
            const icons = {
                'completed': '✅',
                'message': '📨',
                'start': '🚀',
                'view': '👀',
                'question': '❓',
                'error': '⚠️'
            };
            return icons[type] || '📌';
        }

        // 활동 없음 표시
        function showNoActivity() {
            const activityList = document.getElementById('activityList');
            activityList.innerHTML = `
                <div class="no-activity">
                    <div style="font-size: 48px; margin-bottom: 20px;">📭</div>
                    <h3>아직 학습 활동이 없습니다</h3>
                    <p>선생님과 함께 첫 번째 문제를 풀어보세요!</p>
                </div>
            `;
        }

        // 시간 포맷팅
        function formatTime(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) {
                return '방금 전';
            } else if (diff < 3600000) {
                return Math.floor(diff / 60000) + '분 전';
            } else if (diff < 86400000) {
                return Math.floor(diff / 3600000) + '시간 전';
            } else {
                return date.toLocaleDateString('ko-KR');
            }
        }
    </script>
</body>
</html>