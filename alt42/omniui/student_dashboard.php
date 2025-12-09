<?php
/**
 * 이현선 학생 전용 통합 대시보드
 * 실제 데이터 기반 전문적인 학습 관리 시스템
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

// 학생 정보
$stmt = $pdo->prepare("
    SELECT u.*, uid.data as attendance_stats
    FROM mdl_user u
    LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 23
    WHERE u.id = ?
");
$stmt->execute([$userid]);
$student = $stmt->fetch();
$student_name = $student['firstname'] . ' ' . $student['lastname'];

// 오늘의 활동 통계
$stmt = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT ml.page) as pages_studied,
        COUNT(ml.id) as total_activities,
        COALESCE(SUM(CASE WHEN ml.page LIKE '%quiz%' THEN 1 ELSE 0 END), 0) as quizzes_completed,
        MAX(ml.timecreated) as last_activity
    FROM mdl_abessi_missionlog ml
    WHERE ml.userid = ? 
    AND DATE(FROM_UNIXTIME(ml.timecreated)) = CURDATE()
");
$stmt->execute([$userid]);
$today_stats = $stmt->fetch();

// 주간 학습 트렌드
$stmt = $pdo->prepare("
    SELECT 
        DATE(FROM_UNIXTIME(timecreated)) as date,
        COUNT(*) as activities,
        COUNT(DISTINCT page) as unique_pages
    FROM mdl_abessi_missionlog
    WHERE userid = ?
    AND timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
    GROUP BY date
    ORDER BY date DESC
");
$stmt->execute([$userid]);
$weekly_trend = $stmt->fetchAll();

// 진도율 계산
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT page) as completed
    FROM mdl_abessi_chapterlog
    WHERE userid = ?
");
$stmt->execute([$userid]);
$progress = $stmt->fetch();
$progress_rate = min(100, round(($progress['completed'] / 50) * 100)); // 50 = 전체 챕터 수

// 최근 학습 챕터
$stmt = $pdo->prepare("
    SELECT DISTINCT page, MAX(timecreated) as last_visit
    FROM mdl_abessi_chapterlog
    WHERE userid = ?
    GROUP BY page
    ORDER BY last_visit DESC
    LIMIT 5
");
$stmt->execute([$userid]);
$recent_chapters = $stmt->fetchAll();

// 출결 상태
$stmt = $pdo->prepare("
    SELECT 
        COUNT(CASE WHEN type = 'absence' THEN 1 END) as absences,
        COUNT(CASE WHEN type = 'makeup_complete' THEN 1 END) as makeups
    FROM mdl_abessi_attendance_record
    WHERE userid = ?
    AND MONTH(date) = MONTH(CURDATE())
");
$stmt->execute([$userid]);
$attendance = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathKing - <?=$student_name?> 학습 대시보드</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        * {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
        }
        
        body {
            background: #0f172a;
            color: #e2e8f0;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 280px 1fr 320px;
            gap: 24px;
            height: 100vh;
            padding: 24px;
        }
        
        .sidebar {
            background: #1e293b;
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
        }
        
        .main-content {
            display: flex;
            flex-direction: column;
            gap: 24px;
            overflow-y: auto;
            padding-right: 8px;
        }
        
        .main-content::-webkit-scrollbar {
            width: 8px;
        }
        
        .main-content::-webkit-scrollbar-track {
            background: #1e293b;
            border-radius: 4px;
        }
        
        .main-content::-webkit-scrollbar-thumb {
            background: #475569;
            border-radius: 4px;
        }
        
        .right-panel {
            background: #1e293b;
            border-radius: 20px;
            padding: 24px;
            overflow-y: auto;
        }
        
        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #334155;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            border-radius: 16px;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.05) 0%, transparent 70%);
        }
        
        .nav-item {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            border-radius: 12px;
            color: #94a3b8;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 4px;
        }
        
        .nav-item:hover {
            background: #334155;
            color: #e2e8f0;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
        }
        
        .progress-bar {
            height: 8px;
            background: #334155;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #8b5cf6);
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(59, 130, 246, 0.4);
        }
        
        .quick-action {
            background: #334155;
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .quick-action:hover {
            background: #475569;
            transform: translateX(4px);
        }
        
        .badge {
            background: #22c55e;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge.warning {
            background: #f59e0b;
        }
        
        .badge.danger {
            background: #ef4444;
        }
        
        .chapter-item {
            padding: 12px 16px;
            background: #0f172a;
            border-radius: 12px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .glow {
            box-shadow: 0 0 40px rgba(139, 92, 246, 0.3);
        }
        
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(59, 130, 246, 0.4); }
            50% { box-shadow: 0 0 40px rgba(139, 92, 246, 0.6); }
        }
        
        .pulse-glow {
            animation: pulse-glow 2s infinite;
        }
    </style>
</head>
<body>
    <div class="dashboard-grid">
        <!-- 좌측 사이드바 -->
        <div class="sidebar">
            <div class="mb-8">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
                        <?=substr($student_name, 0, 1)?>
                    </div>
                    <div class="ml-3">
                        <div class="font-semibold text-white"><?=$student_name?></div>
                        <div class="text-xs text-gray-400">고2 | 미적분</div>
                    </div>
                </div>
                
                <!-- 진도율 표시 -->
                <div class="mb-6">
                    <div class="flex justify-between mb-2">
                        <span class="text-sm text-gray-400">전체 진도</span>
                        <span class="text-sm font-semibold"><?=$progress_rate?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?=$progress_rate?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- 네비게이션 -->
            <nav class="flex-1">
                <div class="nav-item active">
                    <i data-lucide="home" class="w-5 h-5 mr-3"></i>
                    <span>대시보드</span>
                </div>
                <div class="nav-item" onclick="location.href='learning_tracker.php?userid=<?=$userid?>'">
                    <i data-lucide="edit-3" class="w-5 h-5 mr-3"></i>
                    <span>학습 기록</span>
                    <?php if($today_stats['total_activities'] > 0): ?>
                    <span class="ml-auto badge"><?=$today_stats['total_activities']?></span>
                    <?php endif; ?>
                </div>
                <div class="nav-item" onclick="location.href='student_analytics.php?userid=<?=$userid?>'">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 mr-3"></i>
                    <span>상세 분석</span>
                </div>
                <div class="nav-item">
                    <i data-lucide="target" class="w-5 h-5 mr-3"></i>
                    <span>오답노트</span>
                </div>
                <div class="nav-item">
                    <i data-lucide="trophy" class="w-5 h-5 mr-3"></i>
                    <span>도전 과제</span>
                </div>
                <div class="nav-item">
                    <i data-lucide="calendar" class="w-5 h-5 mr-3"></i>
                    <span>학습 일정</span>
                </div>
            </nav>
            
            <!-- 하단 액션 -->
            <div class="mt-auto pt-6 border-t border-gray-700">
                <button class="btn-primary w-full mb-3">
                    <i data-lucide="plus" class="w-4 h-4 inline mr-2"></i>
                    빠른 기록
                </button>
                <div class="nav-item">
                    <i data-lucide="settings" class="w-5 h-5 mr-3"></i>
                    <span>설정</span>
                </div>
            </div>
        </div>
        
        <!-- 메인 컨텐츠 -->
        <div class="main-content">
            <!-- 상단 요약 카드들 -->
            <div class="grid grid-cols-4 gap-4">
                <div class="stat-card">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-1"><?=$today_stats['pages_studied'] ?? 0?></div>
                        <div class="text-sm opacity-90">오늘 학습 챕터</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-1"><?=$today_stats['quizzes_completed'] ?? 0?></div>
                        <div class="text-sm opacity-90">완료한 퀴즈</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-1">
                            <?=round($today_stats['total_activities'] * 5 / 60, 1)?>h
                        </div>
                        <div class="text-sm opacity-90">학습 시간</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="relative z-10">
                        <div class="text-3xl font-bold mb-1">
                            <?=30 - $attendance['absences']?>/30
                        </div>
                        <div class="text-sm opacity-90">이번달 출석</div>
                    </div>
                </div>
            </div>
            
            <!-- 학습 활동 차트 -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">주간 학습 활동</h2>
                    <select class="bg-gray-800 text-sm px-3 py-1 rounded-lg border border-gray-700">
                        <option>최근 7일</option>
                        <option>최근 14일</option>
                        <option>최근 30일</option>
                    </select>
                </div>
                <canvas id="weeklyChart" height="100"></canvas>
            </div>
            
            <!-- 학습 권장 사항 -->
            <div class="card glow">
                <div class="flex items-center mb-4">
                    <i data-lucide="sparkles" class="w-5 h-5 mr-2 text-yellow-500"></i>
                    <h2 class="text-xl font-bold">AI 학습 추천</h2>
                </div>
                
                <div class="grid grid-cols-3 gap-4">
                    <div class="p-4 bg-gradient-to-br from-blue-900/50 to-purple-900/50 rounded-lg border border-blue-700/50">
                        <div class="text-blue-400 mb-2">📚 개념 강화</div>
                        <div class="text-sm text-gray-300 mb-3">미분 응용 파트를 복습하세요</div>
                        <button class="text-blue-400 text-sm font-semibold hover:text-blue-300">
                            시작하기 →
                        </button>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-br from-purple-900/50 to-pink-900/50 rounded-lg border border-purple-700/50">
                        <div class="text-purple-400 mb-2">✍️ 요약 작성</div>
                        <div class="text-sm text-gray-300 mb-3">오늘 배운 내용을 정리하세요</div>
                        <button class="text-purple-400 text-sm font-semibold hover:text-purple-300" 
                                onclick="location.href='learning_tracker.php?userid=<?=$userid?>'">
                            작성하기 →
                        </button>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-br from-green-900/50 to-emerald-900/50 rounded-lg border border-green-700/50">
                        <div class="text-green-400 mb-2">🎯 오답 정리</div>
                        <div class="text-sm text-gray-300 mb-3">틀린 문제를 분류하세요</div>
                        <button class="text-green-400 text-sm font-semibold hover:text-green-300">
                            정리하기 →
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- 빠른 액션 -->
            <div class="card">
                <h2 class="text-xl font-bold mb-4">빠른 실행</h2>
                <div class="space-y-3">
                    <div class="quick-action">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center mr-3">
                                <i data-lucide="play" class="w-5 h-5 text-blue-400"></i>
                            </div>
                            <div>
                                <div class="font-medium">모의고사 시작</div>
                                <div class="text-xs text-gray-400">실전 감각을 기르세요</div>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                    </div>
                    
                    <div class="quick-action">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center mr-3">
                                <i data-lucide="book" class="w-5 h-5 text-purple-400"></i>
                            </div>
                            <div>
                                <div class="font-medium">개념 복습</div>
                                <div class="text-xs text-gray-400">기초를 다지세요</div>
                            </div>
                        </div>
                        <i data-lucide="chevron-right" class="w-5 h-5 text-gray-400"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 우측 패널 -->
        <div class="right-panel">
            <h3 class="text-lg font-bold mb-4">최근 학습 기록</h3>
            
            <!-- 최근 챕터 -->
            <div class="mb-6">
                <div class="text-sm text-gray-400 mb-3">최근 학습 챕터</div>
                <?php foreach($recent_chapters as $chapter): ?>
                <div class="chapter-item">
                    <div>
                        <div class="text-sm font-medium"><?=$chapter['page']?></div>
                        <div class="text-xs text-gray-500">
                            <?=date('m/d H:i', $chapter['last_visit'])?>
                        </div>
                    </div>
                    <i data-lucide="chevron-right" class="w-4 h-4 text-gray-500"></i>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 성취 배지 -->
            <div class="mb-6">
                <div class="text-sm text-gray-400 mb-3">이번 주 성취</div>
                <div class="grid grid-cols-3 gap-2">
                    <div class="text-center p-3 bg-gray-800 rounded-lg">
                        <div class="text-2xl mb-1">🔥</div>
                        <div class="text-xs">연속3일</div>
                    </div>
                    <div class="text-center p-3 bg-gray-800 rounded-lg opacity-50">
                        <div class="text-2xl mb-1">📚</div>
                        <div class="text-xs">10챕터</div>
                    </div>
                    <div class="text-center p-3 bg-gray-800 rounded-lg opacity-50">
                        <div class="text-2xl mb-1">🎯</div>
                        <div class="text-xs">정답률90%</div>
                    </div>
                </div>
            </div>
            
            <!-- 동기부여 메시지 -->
            <div class="p-4 bg-gradient-to-br from-yellow-900/30 to-orange-900/30 rounded-lg border border-yellow-700/50">
                <div class="flex items-center mb-2">
                    <i data-lucide="zap" class="w-4 h-4 mr-2 text-yellow-500"></i>
                    <span class="text-sm font-semibold text-yellow-400">오늘의 동기부여</span>
                </div>
                <p class="text-sm text-gray-300 leading-relaxed">
                    "작은 진전이라도 매일 꾸준히 하면 큰 변화를 만들 수 있어요. 오늘도 한 걸음 더 나아가세요! 💪"
                </p>
            </div>
            
            <!-- 다음 일정 -->
            <div class="mt-6">
                <div class="text-sm text-gray-400 mb-3">다음 학습 일정</div>
                <div class="space-y-2">
                    <div class="flex items-center p-3 bg-gray-800 rounded-lg">
                        <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                        <div class="flex-1">
                            <div class="text-sm">미적분 단원평가</div>
                            <div class="text-xs text-gray-500">D-7</div>
                        </div>
                    </div>
                    <div class="flex items-center p-3 bg-gray-800 rounded-lg">
                        <div class="w-2 h-2 bg-purple-500 rounded-full mr-3"></div>
                        <div class="flex-1">
                            <div class="text-sm">주간 복습</div>
                            <div class="text-xs text-gray-500">내일 오후 3시</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Lucide 아이콘 초기화
        lucide.createIcons();
        
        // 주간 차트 데이터
        const weeklyData = <?=json_encode($weekly_trend)?>;
        
        // 차트 그리기
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        
        const labels = weeklyData.map(d => {
            const date = new Date(d.date);
            const days = ['일', '월', '화', '수', '목', '금', '토'];
            return days[date.getDay()];
        });
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels.reverse(),
                datasets: [{
                    label: '학습 활동',
                    data: weeklyData.map(d => d.activities).reverse(),
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#8b5cf6',
                    pointBorderColor: '#1e293b',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }, {
                    label: '학습 챕터',
                    data: weeklyData.map(d => d.unique_pages * 5).reverse(),
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#1e293b',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            color: '#94a3b8',
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#334155'
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>