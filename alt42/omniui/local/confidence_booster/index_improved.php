<?php
/**
 * Confidence Booster - 개선된 버전
 * 이현선 학생 맞춤형 학습 지원 시스템
 * 실제 MathKing 데이터베이스와 연동
 */

// 세션 시작
session_start();

// 설정 파일 로드
require_once('config.php');

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    // config.php의 함수 사용
    $userid = confidence_require_login();
} else {
    $userid = $_SESSION['user_id'];
}

// DB 연결 (실제 MathKing DB)
$pdo = get_confidence_db_connection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 사용자 정보 조회
$stmt = $pdo->prepare("SELECT * FROM mdl_user WHERE id = ? AND deleted = 0");
$stmt->execute([$userid]);
$user = $stmt->fetch();

if (!$user) {
    die('사용자 정보를 찾을 수 없습니다.');
}

$user_name = trim($user['firstname'] . ' ' . $user['lastname']);

// 최근 활동 데이터
$recent_activities = $pdo->prepare("
    SELECT page, COUNT(*) as count, MAX(timecreated) as last_activity
    FROM mdl_abessi_missionlog
    WHERE userid = ? AND timecreated > ?
    GROUP BY page
    ORDER BY last_activity DESC
    LIMIT 10
");
$seven_days_ago = time() - (7 * 24 * 60 * 60);
$recent_activities->execute([$userid, $seven_days_ago]);
$activities = $recent_activities->fetchAll();

// 학습 진행 상황
$progress_stmt = $pdo->prepare("SELECT progress_data FROM mdl_abessi_progress WHERE userid = ?");
$progress_stmt->execute([$userid]);
$progress = $progress_stmt->fetch();
$progress_data = $progress ? json_decode($progress['progress_data'], true) : [];

// 최근 요약 (mdl_abessi_today)
$summaries_stmt = $pdo->prepare("
    SELECT goals, timecreated, timemodified
    FROM mdl_abessi_today
    WHERE userid = ?
    ORDER BY timemodified DESC
    LIMIT 10
");
$summaries_stmt->execute([$userid]);
$recent_summaries = $summaries_stmt->fetchAll();

// 오답 분석 (mdl_abessi_mathtalk)
$errors_stmt = $pdo->prepare("
    SELECT content, timecreated
    FROM mdl_abessi_mathtalk
    WHERE userid = ? AND type = 'error_analysis'
    ORDER BY timecreated DESC
    LIMIT 10
");
$errors_stmt->execute([$userid]);
$error_analyses = $errors_stmt->fetchAll();

// 연속 학습 일수 계산
$streak = 0;
for ($i = 0; $i < 365; $i++) {
    $check_date = strtotime("-{$i} days");
    $start_time = strtotime(date('Y-m-d 00:00:00', $check_date));
    $end_time = strtotime(date('Y-m-d 23:59:59', $check_date));
    
    $activity_check = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated BETWEEN ? AND ?
    ");
    $activity_check->execute([$userid, $start_time, $end_time]);
    
    if ($activity_check->fetchColumn() > 0) {
        $streak++;
    } else {
        if ($i > 0) break;
    }
}

// 통계 계산
$total_summaries = $pdo->prepare("
    SELECT COUNT(*) FROM mdl_abessi_missionlog
    WHERE userid = ? AND page = 'summary_writing'
");
$total_summaries->execute([$userid]);
$summary_count = $total_summaries->fetchColumn();

$total_errors = $pdo->prepare("
    SELECT COUNT(*) FROM mdl_abessi_mathtalk
    WHERE userid = ? AND type = 'error_analysis'
");
$total_errors->execute([$userid]);
$error_count = $total_errors->fetchColumn();

$average_quality = $progress_data['average_quality'] ?? 0;

// 주간 트렌드 데이터
$weekly_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $start = strtotime($date . ' 00:00:00');
    $end = strtotime($date . ' 23:59:59');
    
    $day_activity = $pdo->prepare("
        SELECT COUNT(*) FROM mdl_abessi_missionlog
        WHERE userid = ? AND timecreated BETWEEN ? AND ?
    ");
    $day_activity->execute([$userid, $start, $end]);
    
    $weekly_data[] = [
        'date' => $date,
        'day' => date('D', strtotime($date)),
        'activities' => $day_activity->fetchColumn()
    ];
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confidence Booster - <?php echo htmlspecialchars($user_name); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .glass-morphism {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.45);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .achievement-badge {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .progress-ring {
            transform: rotate(-90deg);
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- 헤더 -->
        <div class="glass-morphism p-8 mb-8">
            <div class="flex flex-wrap items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold gradient-text mb-2">
                        안녕하세요, <?php echo htmlspecialchars($user_name); ?>님! 👋
                    </h1>
                    <p class="text-gray-600 text-lg">
                        오늘도 한 걸음 더 성장하는 하루가 되세요 ✨
                    </p>
                </div>
                <div class="text-center mt-4 md:mt-0">
                    <div class="text-5xl font-bold text-purple-600"><?php echo $streak; ?></div>
                    <div class="text-gray-500">연속 학습일</div>
                    <?php if ($streak >= 7): ?>
                    <div class="mt-2">
                        <span class="achievement-badge inline-block px-3 py-1 bg-yellow-400 text-white rounded-full text-sm">
                            🔥 열정적인 학습자
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 통계 카드 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- 총 요약 카드 -->
            <div class="glass-morphism p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">📝</div>
                    <div class="text-sm text-green-500 font-semibold">
                        <i class="fas fa-arrow-up"></i> +12%
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $summary_count; ?></div>
                <div class="text-gray-500 text-sm mt-1">총 요약 작성</div>
                <div class="mt-4 bg-gray-200 rounded-full h-2">
                    <div class="bg-gradient-to-r from-purple-500 to-pink-500 h-2 rounded-full" 
                         style="width: <?php echo min(100, $summary_count * 2); ?>%"></div>
                </div>
            </div>

            <!-- 평균 품질 점수 -->
            <div class="glass-morphism p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">🎯</div>
                    <div class="text-sm text-blue-500 font-semibold">
                        <i class="fas fa-arrow-up"></i> +8%
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800"><?php echo round($average_quality); ?>%</div>
                <div class="text-gray-500 text-sm mt-1">평균 품질 점수</div>
                <div class="mt-4">
                    <svg class="w-full h-16">
                        <circle cx="50%" cy="50%" r="25" fill="none" stroke="#e5e7eb" stroke-width="5"/>
                        <circle class="progress-ring" cx="50%" cy="50%" r="25" fill="none" 
                                stroke="url(#gradient)" stroke-width="5"
                                stroke-dasharray="<?php echo 157 * ($average_quality/100); ?> 157"
                                stroke-linecap="round"/>
                        <defs>
                            <linearGradient id="gradient">
                                <stop offset="0%" style="stop-color:#667eea"/>
                                <stop offset="100%" style="stop-color:#764ba2"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            </div>

            <!-- 오답 분석 -->
            <div class="glass-morphism p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">🔍</div>
                    <div class="text-sm text-orange-500 font-semibold">
                        <i class="fas fa-arrow-up"></i> +15%
                    </div>
                </div>
                <div class="text-3xl font-bold text-gray-800"><?php echo $error_count; ?></div>
                <div class="text-gray-500 text-sm mt-1">오답 분석</div>
                <div class="mt-4 flex space-x-1">
                    <?php for($i = 0; $i < 5; $i++): ?>
                    <div class="flex-1 bg-<?php echo $i < min(5, $error_count/10) ? 'purple' : 'gray'; ?>-400 h-8 rounded"></div>
                    <?php endfor; ?>
                </div>
            </div>

            <!-- 획득 배지 -->
            <div class="glass-morphism p-6 card-hover">
                <div class="flex items-center justify-between mb-4">
                    <div class="text-3xl">🏆</div>
                    <div class="text-sm text-purple-500 font-semibold">NEW!</div>
                </div>
                <div class="text-3xl font-bold text-gray-800">
                    <?php 
                    $badges = 0;
                    if ($summary_count >= 10) $badges++;
                    if ($summary_count >= 50) $badges++;
                    if ($streak >= 7) $badges++;
                    if ($error_count >= 20) $badges++;
                    echo $badges;
                    ?>
                </div>
                <div class="text-gray-500 text-sm mt-1">획득 배지</div>
                <div class="mt-4 flex space-x-2">
                    <?php if ($summary_count >= 10): ?>
                    <span class="text-2xl" title="요약 입문">🌟</span>
                    <?php endif; ?>
                    <?php if ($streak >= 7): ?>
                    <span class="text-2xl" title="일주일 연속">🔥</span>
                    <?php endif; ?>
                    <?php if ($error_count >= 20): ?>
                    <span class="text-2xl" title="성실한 학습자">📝</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 메인 컨텐츠 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- 왼쪽: 학습 도구 -->
            <div class="lg:col-span-2 space-y-6">
                <!-- 요약 작성 폼 -->
                <div class="glass-morphism p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">학습 요약 작성</h2>
                    <form id="summaryForm" class="space-y-4">
                        <input type="hidden" id="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                        
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">챕터 제목</label>
                            <input type="text" id="chapterTitle" name="chapter_title"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                   placeholder="예: 미적분 - 함수의 극한" required>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">오늘의 학습 요약</label>
                            <textarea id="summaryText" name="summary_text" rows="6"
                                      class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                      placeholder="오늘 배운 내용을 자신의 말로 정리해보세요..." required></textarea>
                            <div class="mt-2 flex justify-between text-sm">
                                <span class="text-gray-500">최소 200자 이상 작성을 권장합니다</span>
                                <span id="charCount" class="text-gray-500">0 / 800</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">핵심 개념</label>
                            <input type="text" id="keyConcepts" name="key_concepts"
                                   class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-purple-500"
                                   placeholder="쉼표로 구분 (예: 극한, 연속성, 미분가능성)">
                            <div id="conceptTags" class="mt-2 flex flex-wrap gap-2"></div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">난이도</label>
                                <select name="difficulty_level" class="w-full px-4 py-2 rounded-lg border">
                                    <option value="1">매우 쉬움</option>
                                    <option value="2">쉬움</option>
                                    <option value="3" selected>보통</option>
                                    <option value="4">어려움</option>
                                    <option value="5">매우 어려움</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">자신감 레벨</label>
                                <input type="range" id="confidenceLevel" name="confidence_score" 
                                       min="0" max="100" value="50" class="w-full">
                                <div class="text-center mt-1">
                                    <span id="confidenceValue" class="font-bold text-purple-600">50%</span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="w-full py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white font-bold rounded-lg hover:opacity-90">
                            <i class="fas fa-save mr-2"></i>요약 저장하기
                        </button>
                    </form>
                </div>

                <!-- 최근 요약 목록 -->
                <div class="glass-morphism p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">최근 작성한 요약</h2>
                    <div class="space-y-3">
                        <?php foreach($recent_summaries as $summary): 
                            $goals = json_decode($summary['goals'], true);
                            if ($goals && isset($goals['summary'])):
                        ?>
                        <div class="p-4 bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <h3 class="font-bold text-gray-800">
                                    <?php echo htmlspecialchars($goals['chapter'] ?? '제목 없음'); ?>
                                </h3>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('m/d H:i', $summary['timemodified']); ?>
                                </span>
                            </div>
                            <p class="text-gray-600 text-sm">
                                <?php echo htmlspecialchars(substr($goals['summary'], 0, 150)) . '...'; ?>
                            </p>
                            <div class="mt-2 flex gap-2">
                                <span class="px-2 py-1 bg-purple-100 text-purple-600 text-xs rounded">
                                    품질: <?php echo $goals['quality'] ?? 0; ?>%
                                </span>
                                <span class="px-2 py-1 bg-blue-100 text-blue-600 text-xs rounded">
                                    자신감: <?php echo $goals['confidence'] ?? 0; ?>%
                                </span>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>

                <!-- 오답 분석 -->
                <div class="glass-morphism p-6">
                    <h2 class="text-2xl font-bold mb-4 gradient-text">최근 오답 분석</h2>
                    <div class="space-y-3">
                        <?php foreach($error_analyses as $error): 
                            $content = json_decode($error['content'], true);
                            if ($content):
                        ?>
                        <div class="p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-lg">
                            <div class="flex justify-between mb-2">
                                <span class="font-semibold">
                                    문제 #<?php echo htmlspecialchars($content['problem'] ?? ''); ?>
                                </span>
                                <span class="text-xs text-gray-500">
                                    <?php echo date('m/d', $error['timecreated']); ?>
                                </span>
                            </div>
                            <div class="flex gap-2">
                                <span class="px-2 py-1 bg-red-100 text-red-600 text-xs rounded">
                                    <?php echo htmlspecialchars($content['error_type'] ?? ''); ?>
                                </span>
                                <span class="px-2 py-1 bg-orange-100 text-orange-600 text-xs rounded">
                                    <?php echo htmlspecialchars($content['chapter'] ?? ''); ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- 오른쪽: 통계 -->
            <div class="space-y-6">
                <!-- 주간 활동 -->
                <div class="glass-morphism p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text">주간 학습 패턴</h3>
                    <canvas id="weeklyChart"></canvas>
                </div>

                <!-- 최근 활동 -->
                <div class="glass-morphism p-6">
                    <h3 class="text-xl font-bold mb-4 gradient-text">최근 활동</h3>
                    <div class="space-y-2">
                        <?php foreach($activities as $activity): ?>
                        <div class="flex justify-between items-center py-2 border-b">
                            <span class="text-sm text-gray-700">
                                <?php echo htmlspecialchars($activity['page']); ?>
                            </span>
                            <span class="text-xs text-purple-600 font-semibold">
                                <?php echo $activity['count']; ?>회
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 동기부여 메시지 -->
                <div class="glass-morphism p-6 bg-gradient-to-br from-purple-100 to-pink-100">
                    <h3 class="text-xl font-bold mb-3 gradient-text">오늘의 동기부여</h3>
                    <blockquote class="italic text-gray-700">
                        "매일 조금씩 나아지는 것이 중요합니다. 어제의 나보다 1%만 더 성장해도 1년 후에는 37배 성장합니다!"
                    </blockquote>
                </div>
            </div>
        </div>
    </div>

    <script>
    // 주간 차트
    const ctx = document.getElementById('weeklyChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($weekly_data, 'day')); ?>,
            datasets: [{
                label: '활동',
                data: <?php echo json_encode(array_column($weekly_data, 'activities')); ?>,
                borderColor: 'rgb(102, 126, 234)',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            }
        }
    });

    // 문자 수 카운트
    document.getElementById('summaryText').addEventListener('input', function() {
        const count = this.value.length;
        const counter = document.getElementById('charCount');
        counter.textContent = count + ' / 800';
        counter.className = count < 200 ? 'text-red-500' : count <= 800 ? 'text-green-500' : 'text-orange-500';
    });

    // 자신감 레벨
    document.getElementById('confidenceLevel').addEventListener('input', function() {
        document.getElementById('confidenceValue').textContent = this.value + '%';
    });

    // 태그 처리
    document.getElementById('keyConcepts').addEventListener('keyup', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            const value = this.value.replace(',', '').trim();
            if (value) {
                addTag(value);
                this.value = '';
            }
        }
    });

    function addTag(text) {
        const container = document.getElementById('conceptTags');
        const tag = document.createElement('span');
        tag.className = 'px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm';
        tag.innerHTML = `${text} <button onclick="this.parentElement.remove()" class="ml-1">×</button>`;
        container.appendChild(tag);
    }

    // 폼 제출
    document.getElementById('summaryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const tags = Array.from(document.querySelectorAll('#conceptTags span'))
            .map(tag => tag.textContent.replace('×', '').trim());
        
        const data = {
            chapter_title: formData.get('chapter_title'),
            summary_text: formData.get('summary_text'),
            key_concepts: tags,
            difficulty_level: formData.get('difficulty_level'),
            confidence_score: formData.get('confidence_score')
        };

        fetch('ajax/save_summary_improved.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                alert('요약이 저장되었습니다!');
                location.reload();
            } else {
                alert('오류: ' + result.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('네트워크 오류가 발생했습니다.');
        });
    });
    </script>
</body>
</html>