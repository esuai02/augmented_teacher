<?php
/**
 * 학습 추적기 페이지
 * 실제 데이터 기반 학습 진행 상황 시각화
 */

// 세션 체크
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 로그인 체크 및 리디렉션 처리
if (!isset($_SESSION['user_id'])) {
    // 현재 경로 확인 후 적절한 로그인 페이지로 리디렉션
    $login_path = file_exists('login.php') ? 'login.php' : '../login.php';
    if (!file_exists($login_path)) {
        $login_path = '../../login.php';
    }
    header('Location: ' . $login_path);
    exit;
}

// 설정 파일 포함
$config_path = 'local/confidence_booster/config.php';
if (!file_exists($config_path)) {
    $config_path = '../local/confidence_booster/config.php';
}
if (file_exists($config_path)) {
    require_once($config_path);
} else {
    die('설정 파일을 찾을 수 없습니다.');
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['fullname'] ?? '학생';

// DB 연결
$pdo = get_confidence_db_connection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 30일간 학습 데이터 조회
$thirty_days_ago = time() - (30 * 24 * 60 * 60);

// 일별 학습 활동 통계
$stmt = $pdo->prepare("
    SELECT DATE(FROM_UNIXTIME(timecreated)) as study_date, 
           COUNT(*) as activity_count,
           COUNT(DISTINCT page) as unique_activities
    FROM mdl_abessi_missionlog
    WHERE userid = ? AND timecreated > ?
    GROUP BY study_date
    ORDER BY study_date DESC
");
$stmt->execute([$user_id, $thirty_days_ago]);
$daily_activities = $stmt->fetchAll();

// 챕터별 학습 시간 (최근 30일)
$stmt = $pdo->prepare("
    SELECT chapter, COUNT(*) as study_count
    FROM mdl_abessi_chapterlog
    WHERE userid = ? AND timecreated > ?
    GROUP BY chapter
    ORDER BY study_count DESC
    LIMIT 10
");
$stmt->execute([$user_id, $thirty_days_ago]);
$chapter_stats = $stmt->fetchAll();

// 오답 유형 통계
$stmt = $pdo->prepare("
    SELECT content, timecreated
    FROM mdl_abessi_mathtalk
    WHERE userid = ? AND type = 'error_analysis'
    ORDER BY timecreated DESC
    LIMIT 100
");
$stmt->execute([$user_id]);
$errors = $stmt->fetchAll();

$error_stats = [
    'calculation' => 0,
    'concept' => 0,
    'application' => 0,
    'careless' => 0
];

foreach ($errors as $error) {
    $content = json_decode($error['content'], true);
    if ($content && isset($content['error_type'])) {
        $type = $content['error_type'];
        if (isset($error_stats[$type])) {
            $error_stats[$type]++;
        }
    }
}

// 요약 품질 추이
$stmt = $pdo->prepare("
    SELECT goals, timemodified
    FROM mdl_abessi_today
    WHERE userid = ?
    ORDER BY timemodified DESC
    LIMIT 20
");
$stmt->execute([$user_id]);
$summaries = $stmt->fetchAll();

$quality_trend = [];
foreach ($summaries as $summary) {
    $goals = json_decode($summary['goals'], true);
    if ($goals && isset($goals['quality'])) {
        $quality_trend[] = [
            'date' => date('m/d', $summary['timemodified']),
            'quality' => $goals['quality']
        ];
    }
}
$quality_trend = array_reverse($quality_trend);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학습 추적기 - <?php echo htmlspecialchars($user_name); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.2);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- 헤더 -->
        <div class="glass p-6 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold gradient-text">학습 추적기</h1>
                    <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($user_name); ?>님의 학습 진행 상황</p>
                </div>
                <button onclick="history.back()" class="px-4 py-2 text-purple-600 hover:bg-purple-50 rounded-lg">
                    <i class="fas fa-arrow-left mr-2"></i>돌아가기
                </button>
            </div>
        </div>

        <!-- 차트 그리드 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- 일별 학습 활동 차트 -->
            <div class="glass p-6">
                <h2 class="text-xl font-bold mb-4 gradient-text">일별 학습 활동</h2>
                <canvas id="dailyChart"></canvas>
            </div>

            <!-- 챕터별 학습 분포 -->
            <div class="glass p-6">
                <h2 class="text-xl font-bold mb-4 gradient-text">챕터별 학습 분포</h2>
                <canvas id="chapterChart"></canvas>
            </div>

            <!-- 오답 유형 분석 -->
            <div class="glass p-6">
                <h2 class="text-xl font-bold mb-4 gradient-text">오답 유형 분석</h2>
                <canvas id="errorChart"></canvas>
            </div>

            <!-- 요약 품질 추이 -->
            <div class="glass p-6">
                <h2 class="text-xl font-bold mb-4 gradient-text">요약 품질 추이</h2>
                <canvas id="qualityChart"></canvas>
            </div>
        </div>

        <!-- 학습 인사이트 -->
        <div class="glass p-6 mt-8">
            <h2 class="text-xl font-bold mb-4 gradient-text">
                <i class="fas fa-lightbulb mr-2"></i>학습 인사이트
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php
                // 가장 많이 학습한 챕터
                $top_chapter = $chapter_stats[0] ?? null;
                if ($top_chapter):
                ?>
                <div class="p-4 bg-purple-50 rounded-lg">
                    <h3 class="font-bold text-purple-800 mb-2">집중 학습 챕터</h3>
                    <p class="text-purple-600"><?php echo htmlspecialchars($top_chapter['chapter']); ?></p>
                    <p class="text-sm text-purple-500"><?php echo $top_chapter['study_count']; ?>회 학습</p>
                </div>
                <?php endif; ?>

                <?php
                // 가장 많은 오류 유형
                $max_error_type = array_keys($error_stats, max($error_stats))[0] ?? null;
                if ($max_error_type && $error_stats[$max_error_type] > 0):
                $type_labels = [
                    'calculation' => '계산 실수',
                    'concept' => '개념 부족',
                    'application' => '응용 부족',
                    'careless' => '부주의'
                ];
                ?>
                <div class="p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-bold text-blue-800 mb-2">주요 오류 유형</h3>
                    <p class="text-blue-600"><?php echo $type_labels[$max_error_type]; ?></p>
                    <p class="text-sm text-blue-500"><?php echo $error_stats[$max_error_type]; ?>회 발생</p>
                </div>
                <?php endif; ?>

                <?php
                // 평균 품질 점수
                if (count($quality_trend) > 0):
                $avg_quality = array_sum(array_column($quality_trend, 'quality')) / count($quality_trend);
                ?>
                <div class="p-4 bg-green-50 rounded-lg">
                    <h3 class="font-bold text-green-800 mb-2">평균 요약 품질</h3>
                    <p class="text-green-600 text-2xl font-bold"><?php echo round($avg_quality); ?>%</p>
                    <p class="text-sm text-green-500">최근 <?php echo count($quality_trend); ?>개 요약</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // 일별 학습 활동 차트
    const dailyCtx = document.getElementById('dailyChart').getContext('2d');
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_reverse(array_column($daily_activities, 'study_date'))); ?>,
            datasets: [{
                label: '활동 수',
                data: <?php echo json_encode(array_reverse(array_column($daily_activities, 'activity_count'))); ?>,
                borderColor: 'rgb(147, 51, 234)',
                backgroundColor: 'rgba(147, 51, 234, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 챕터별 학습 분포
    const chapterCtx = document.getElementById('chapterChart').getContext('2d');
    new Chart(chapterCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($chapter_stats, 'chapter')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($chapter_stats, 'study_count')); ?>,
                backgroundColor: [
                    'rgba(147, 51, 234, 0.8)',
                    'rgba(219, 39, 119, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(251, 146, 60, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });

    // 오답 유형 차트
    const errorCtx = document.getElementById('errorChart').getContext('2d');
    new Chart(errorCtx, {
        type: 'bar',
        data: {
            labels: ['계산 실수', '개념 부족', '응용 부족', '부주의'],
            datasets: [{
                label: '오답 수',
                data: [
                    <?php echo $error_stats['calculation']; ?>,
                    <?php echo $error_stats['concept']; ?>,
                    <?php echo $error_stats['application']; ?>,
                    <?php echo $error_stats['careless']; ?>
                ],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(147, 51, 234, 0.8)'
                ]
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // 요약 품질 추이
    const qualityCtx = document.getElementById('qualityChart').getContext('2d');
    new Chart(qualityCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($quality_trend, 'date')); ?>,
            datasets: [{
                label: '품질 점수',
                data: <?php echo json_encode(array_column($quality_trend, 'quality')); ?>,
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { 
                    beginAtZero: true,
                    max: 100
                }
            }
        }
    });
    </script>
</body>
</html>