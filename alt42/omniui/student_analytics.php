<?php
/**
 * 학생 학습 분석 시스템 - 이현선 학생 맞춤형
 * 실제 데이터 기반 전문적인 UX 시스템
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

// 사용자 ID 확인
$userid = $_GET['userid'] ?? $_SESSION['userid'] ?? null;
if (!$userid) {
    header('Location: login.php');
    exit;
}

// 학생 정보 조회
$stmt = $pdo->prepare("
    SELECT u.id, u.firstname, u.lastname, u.email, 
           uid.data as role
    FROM mdl_user u
    LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
    WHERE u.id = ? AND u.deleted = 0
");
$stmt->execute([$userid]);
$student = $stmt->fetch();

if (!$student) {
    die("학생 정보를 찾을 수 없습니다.");
}

// 학생 이름
$student_name = $student['firstname'] . ' ' . $student['lastname'];

// 최근 학습 활동 조회 (mdl_abessi_missionlog 활용)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as activity_count, 
           DATE(FROM_UNIXTIME(timecreated)) as activity_date,
           MAX(timecreated) as last_activity
    FROM mdl_abessi_missionlog 
    WHERE userid = ? 
    AND timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY))
    GROUP BY activity_date
    ORDER BY activity_date DESC
");
$stmt->execute([$userid]);
$activities = $stmt->fetchAll();

// 일일 학습 패턴 분석
$daily_pattern = [];
$total_activities = 0;
foreach ($activities as $activity) {
    $daily_pattern[$activity['activity_date']] = $activity['activity_count'];
    $total_activities += $activity['activity_count'];
}

// 출결 데이터 조회
$stmt = $pdo->prepare("
    SELECT type, COUNT(*) as count, SUM(hours) as total_hours
    FROM mdl_abessi_attendance_record
    WHERE userid = ?
    AND date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY type
");
$stmt->execute([$userid]);
$attendance_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// 스케줄 데이터 조회
$stmt = $pdo->prepare("
    SELECT schedule_data, pinned
    FROM mdl_abessi_schedule
    WHERE userid = ?
    ORDER BY timemodified DESC
    LIMIT 1
");
$stmt->execute([$userid]);
$schedule = $stmt->fetch();
$schedule_data = $schedule ? json_decode($schedule['schedule_data'], true) : [];

// 챕터 학습 진도 조회
$stmt = $pdo->prepare("
    SELECT page, COUNT(*) as visit_count, MAX(timecreated) as last_visit
    FROM mdl_abessi_chapterlog
    WHERE userid = ?
    GROUP BY page
    ORDER BY visit_count DESC
    LIMIT 10
");
$stmt->execute([$userid]);
$chapter_progress = $stmt->fetchAll();

// 학습 진도율 계산
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT page) as completed_chapters
    FROM mdl_abessi_chapterlog
    WHERE userid = ?
");
$stmt->execute([$userid]);
$progress_data = $stmt->fetch();
$total_chapters = 50; // 전체 챕터 수 (실제 커리큘럼에 맞게 조정)
$progress_rate = round(($progress_data['completed_chapters'] / $total_chapters) * 100, 1);

// 오늘의 목표 조회
$stmt = $pdo->prepare("
    SELECT * FROM mdl_abessi_today
    WHERE userid = ?
    ORDER BY timecreated DESC
    LIMIT 1
");
$stmt->execute([$userid]);
$today_goal = $stmt->fetch();

// 학습 강도 분석 (최근 7일)
$stmt = $pdo->prepare("
    SELECT 
        DATE(FROM_UNIXTIME(timecreated)) as study_date,
        COUNT(*) as activity_count,
        COUNT(DISTINCT page) as unique_pages
    FROM mdl_abessi_missionlog
    WHERE userid = ?
    AND timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 7 DAY))
    GROUP BY study_date
");
$stmt->execute([$userid]);
$weekly_intensity = $stmt->fetchAll();

// 평균 일일 학습량 계산
$avg_daily_activities = count($activities) > 0 ? round($total_activities / count($activities), 1) : 0;

// 학습 패턴 분류
$learning_pattern = '';
$pattern_description = '';
if ($avg_daily_activities >= 20) {
    $learning_pattern = '집중형';
    $pattern_description = '매일 꾸준히 높은 집중도로 학습하는 우수한 패턴입니다.';
    $pattern_color = '#10b981';
} elseif ($avg_daily_activities >= 10) {
    $learning_pattern = '안정형';
    $pattern_description = '규칙적으로 학습하는 좋은 습관을 가지고 있습니다.';
    $pattern_color = '#3b82f6';
} elseif ($avg_daily_activities >= 5) {
    $learning_pattern = '성장형';
    $pattern_description = '학습량을 점진적으로 늘려가고 있습니다.';
    $pattern_color = '#f59e0b';
} else {
    $learning_pattern = '시작형';
    $pattern_description = '학습 루틴을 만들어가는 단계입니다.';
    $pattern_color = '#8b5cf6';
}

// 추천 학습 전략 생성
$recommendations = [];
if ($progress_rate < 30) {
    $recommendations[] = [
        'icon' => '📚',
        'title' => '기초 개념 강화',
        'desc' => '핵심 개념을 먼저 정리하고 이해도를 높이세요'
    ];
} elseif ($progress_rate < 60) {
    $recommendations[] = [
        'icon' => '🎯',
        'title' => '문제 풀이 집중',
        'desc' => '다양한 유형의 문제를 풀며 응용력을 기르세요'
    ];
} else {
    $recommendations[] = [
        'icon' => '🚀',
        'title' => '심화 학습',
        'desc' => '고난도 문제와 실전 모의고사에 도전하세요'
    ];
}

// 오늘의 학습 시간 계산
$stmt = $pdo->prepare("
    SELECT COUNT(*) * 5 as minutes
    FROM mdl_abessi_missionlog
    WHERE userid = ?
    AND DATE(FROM_UNIXTIME(timecreated)) = CURDATE()
");
$stmt->execute([$userid]);
$today_time = $stmt->fetch();
$today_minutes = $today_time['minutes'] ?? 0;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?=$student_name?>님의 학습 분석 리포트</title>
    <link href="https://fonts.googleapis.com/css2?family=Pretendard:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 24px;
            color: white;
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
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        }
        
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .card-hover {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .pattern-badge {
            background: <?=$pattern_color?>;
            color: white;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-block;
            box-shadow: 0 4px 15px <?=$pattern_color?>40;
        }
        
        .activity-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }
        
        .activity-high { background: #10b981; }
        .activity-medium { background: #f59e0b; }
        .activity-low { background: #ef4444; }
        
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-up {
            animation: slideInUp 0.6s ease-out forwards;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- 헤더 섹션 -->
        <div class="glass-card p-8 mb-8 animate-slide-up">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">
                        <?=$student_name?>님의 학습 분석
                    </h1>
                    <p class="text-gray-600">
                        고등학교 2학년 | 미적분 | 
                        <span class="pattern-badge"><?=$learning_pattern?> 학습자</span>
                    </p>
                    <p class="text-sm text-gray-500 mt-2"><?=$pattern_description?></p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">오늘의 학습 시간</div>
                    <div class="text-3xl font-bold text-purple-600"><?=$today_minutes?>분</div>
                </div>
            </div>
        </div>
        
        <!-- 주요 지표 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- 진도율 카드 -->
            <div class="stat-card card-hover animate-slide-up" style="animation-delay: 0.1s">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="trending-up" class="w-8 h-8"></i>
                        <span class="text-2xl font-bold"><?=$progress_rate?>%</span>
                    </div>
                    <div class="text-white/80 text-sm">전체 진도율</div>
                    <div class="w-full bg-white/20 rounded-full h-2 mt-3">
                        <div class="bg-white rounded-full h-2" style="width: <?=$progress_rate?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- 일평균 활동 카드 -->
            <div class="stat-card card-hover animate-slide-up" style="animation-delay: 0.2s">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="activity" class="w-8 h-8"></i>
                        <span class="text-2xl font-bold"><?=$avg_daily_activities?></span>
                    </div>
                    <div class="text-white/80 text-sm">일평균 학습 활동</div>
                    <div class="mt-3 flex items-center">
                        <?php for($i = 0; $i < 5; $i++): ?>
                            <span class="activity-dot <?=$i < ($avg_daily_activities/5) ? 'activity-high' : 'bg-white/20'?>"></span>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
            
            <!-- 연속 학습일 카드 -->
            <div class="stat-card card-hover animate-slide-up" style="animation-delay: 0.3s">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="flame" class="w-8 h-8"></i>
                        <span class="text-2xl font-bold"><?=count($activities)?></span>
                    </div>
                    <div class="text-white/80 text-sm">최근 30일 학습일</div>
                    <div class="mt-3 text-xs text-white/60">
                        연속 <?=count($activities)?>일 학습 중
                    </div>
                </div>
            </div>
            
            <!-- 출석률 카드 -->
            <div class="stat-card card-hover animate-slide-up" style="animation-delay: 0.4s">
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="calendar-check" class="w-8 h-8"></i>
                        <span class="text-2xl font-bold">
                            <?php 
                            $total_attendance = array_sum($attendance_stats);
                            echo $total_attendance > 0 ? round((($total_attendance - ($attendance_stats['absence'] ?? 0)) / $total_attendance) * 100) : 100;
                            ?>%
                        </span>
                    </div>
                    <div class="text-white/80 text-sm">출석률</div>
                    <div class="mt-3 text-xs text-white/60">
                        결석 <?=$attendance_stats['absence'] ?? 0?>회
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 학습 패턴 분석 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <!-- 주간 학습 패턴 -->
            <div class="glass-card p-6 card-hover animate-slide-up" style="animation-delay: 0.5s">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i data-lucide="bar-chart-3" class="w-5 h-5 mr-2 text-purple-600"></i>
                    주간 학습 패턴
                </h3>
                <div class="chart-container">
                    <canvas id="weeklyChart"></canvas>
                </div>
            </div>
            
            <!-- 챕터별 진도 -->
            <div class="glass-card p-6 card-hover animate-slide-up" style="animation-delay: 0.6s">
                <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i data-lucide="book-open" class="w-5 h-5 mr-2 text-purple-600"></i>
                    최근 학습 챕터
                </h3>
                <div class="space-y-3">
                    <?php foreach(array_slice($chapter_progress, 0, 5) as $chapter): ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div>
                            <div class="font-medium text-gray-800"><?=$chapter['page']?></div>
                            <div class="text-xs text-gray-500">
                                방문 <?=$chapter['visit_count']?>회
                            </div>
                        </div>
                        <div class="text-sm text-gray-600">
                            <?=date('m/d H:i', $chapter['last_visit'])?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- 학습 추천 섹션 -->
        <div class="glass-card p-6 animate-slide-up" style="animation-delay: 0.7s">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i data-lucide="lightbulb" class="w-5 h-5 mr-2 text-purple-600"></i>
                맞춤 학습 전략
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach($recommendations as $rec): ?>
                <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl">
                    <div class="text-3xl mb-2"><?=$rec['icon']?></div>
                    <h4 class="font-semibold text-gray-800"><?=$rec['title']?></h4>
                    <p class="text-sm text-gray-600 mt-1"><?=$rec['desc']?></p>
                </div>
                <?php endforeach; ?>
                
                <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl">
                    <div class="text-3xl mb-2">✍️</div>
                    <h4 class="font-semibold text-gray-800">요약 습관 만들기</h4>
                    <p class="text-sm text-gray-600 mt-1">개념 학습 후 3줄로 정리하는 습관을 기르세요</p>
                </div>
                
                <div class="p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl">
                    <div class="text-3xl mb-2">🎯</div>
                    <h4 class="font-semibold text-gray-800">오답 분류하기</h4>
                    <p class="text-sm text-gray-600 mt-1">틀린 이유를 분석하고 패턴을 파악하세요</p>
                </div>
            </div>
        </div>
        
        <!-- 오늘의 목표 -->
        <?php if($today_goal): ?>
        <div class="glass-card p-6 mt-6 animate-slide-up" style="animation-delay: 0.8s">
            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                <i data-lucide="target" class="w-5 h-5 mr-2 text-purple-600"></i>
                오늘의 목표
            </h3>
            <div class="p-4 bg-yellow-50 border-l-4 border-yellow-400 rounded-lg">
                <p class="text-gray-800"><?=$today_goal['goal_text'] ?? '오늘도 열심히 공부해요!'?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Lucide 아이콘 초기화
        lucide.createIcons();
        
        // 주간 학습 패턴 차트
        const ctx = document.getElementById('weeklyChart').getContext('2d');
        const weeklyData = <?=json_encode($weekly_intensity)?>;
        
        const labels = weeklyData.map(d => {
            const date = new Date(d.study_date);
            return (date.getMonth() + 1) + '/' + date.getDate();
        });
        
        const data = weeklyData.map(d => d.activity_count);
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: '학습 활동',
                    data: data,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 5
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>