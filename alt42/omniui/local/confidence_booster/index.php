<?php
/**
 * Confidence Booster - 학생 중심 실사용 기능
 * 이현선 학생 맞춤형 학습 지원 시스템
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 설정 파일 로드
require_once('config.php');

// 로그인 체크
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}

$userid = $_SESSION['user_id'];

// DB 연결
$pdo = get_confidence_db_connection();
if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

// 사용자 정보
$stmt = $pdo->prepare("
    SELECT id, username, firstname, lastname, email
    FROM mdl_user 
    WHERE id = ? AND deleted = 0
");
$stmt->execute([$userid]);
$user = $stmt->fetch();

if (!$user) {
    die('사용자 정보를 찾을 수 없습니다.');
}

$user_name = trim($user['firstname'] . ' ' . $user['lastname']);

// 시험 정보 조회
try {
    $stmt = $pdo->prepare("
        SELECT ses.*, es.exam_scope
        FROM student_exam_settings ses
        LEFT JOIN exam_settings es ON ses.school = es.school 
            AND ses.exam_type = es.exam_type
        WHERE ses.user_id = ?
        ORDER BY ses.updated_at DESC
        LIMIT 1
    ");
    $stmt->execute([$userid]);
    $exam_info = $stmt->fetch();
} catch (PDOException $e) {
    $exam_info = null;
}

// D-Day 계산
$dday = null;
$exam_phase = 'prepare';
if ($exam_info && isset($exam_info['math_exam_date']) && $exam_info['math_exam_date']) {
    $exam_date = new DateTime($exam_info['math_exam_date']);
    $today = new DateTime();
    $interval = $today->diff($exam_date);
    $dday = $interval->invert ? -$interval->days : $interval->days;
    
    if ($dday <= 0) {
        $exam_phase = 'finished';
    } elseif ($dday <= 7) {
        $exam_phase = 'finish';
    } elseif ($dday <= 21) {
        $exam_phase = 'intensive';
    } else {
        $exam_phase = 'prepare';
    }
}

// 오늘의 학습 목표 (세션에 저장)
$today_goals = $_SESSION['today_goals'] ?? [];

// 최근 퀴즈 결과
try {
    $stmt = $pdo->prepare("
        SELECT q.name, qa.sumgrades, q.sumgrades as maxgrade, qa.timemodified
        FROM mdl_quiz_attempts qa
        JOIN mdl_quiz q ON q.id = qa.quiz
        WHERE qa.userid = ? AND qa.state = 'finished'
        ORDER BY qa.timemodified DESC
        LIMIT 5
    ");
    $stmt->execute([$userid]);
    $recent_quizzes = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_quizzes = [];
}

// 최근 과제 제출
try {
    $stmt = $pdo->prepare("
        SELECT a.name, asub.status, asub.timemodified
        FROM mdl_assign_submission asub
        JOIN mdl_assign a ON a.id = asub.assignment
        WHERE asub.userid = ? AND asub.status = 'submitted'
        ORDER BY asub.timemodified DESC
        LIMIT 5
    ");
    $stmt->execute([$userid]);
    $recent_assignments = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_assignments = [];
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confidence Booster - <?php echo htmlspecialchars($user_name); ?></title>
    
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
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px 0 rgba(31, 38, 135, 0.3);
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        
        <!-- 헤더 -->
        <div class="glass p-6 mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold gradient-text">
                        <?php echo htmlspecialchars($user_name); ?>님의 학습 도우미
                    </h1>
                    <p class="text-gray-600 mt-2">오늘도 한 걸음 더 성장해요! 💪</p>
                </div>
                <?php if ($dday !== null && $dday > 0): ?>
                <div class="text-center">
                    <div class="text-4xl font-bold text-purple-600">D-<?php echo $dday; ?></div>
                    <div class="text-gray-500">시험까지</div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 오늘의 학습 계획 -->
        <div class="glass p-6 mb-8">
            <h2 class="text-2xl font-bold mb-4 gradient-text">
                <i class="fas fa-calendar-check mr-2"></i>오늘의 학습 계획
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                
                <!-- 학습 시간 기록 -->
                <div class="bg-blue-50 p-4 rounded-lg">
                    <h3 class="font-bold text-blue-800 mb-3">⏱️ 학습 시간</h3>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-blue-600" id="studyTimer">00:00:00</div>
                        <div class="mt-2 space-x-2">
                            <button onclick="startTimer()" class="px-3 py-1 bg-blue-500 text-white rounded text-sm">시작</button>
                            <button onclick="pauseTimer()" class="px-3 py-1 bg-gray-500 text-white rounded text-sm">일시정지</button>
                            <button onclick="resetTimer()" class="px-3 py-1 bg-red-500 text-white rounded text-sm">리셋</button>
                        </div>
                    </div>
                </div>
                
                <!-- 오늘의 목표 -->
                <div class="bg-green-50 p-4 rounded-lg">
                    <h3 class="font-bold text-green-800 mb-3">🎯 오늘의 목표</h3>
                    <textarea 
                        id="todayGoal" 
                        class="w-full p-2 border rounded text-sm" 
                        rows="3" 
                        placeholder="오늘 꼭 이루고 싶은 목표를 적어보세요..."><?php echo htmlspecialchars($_SESSION['today_goal'] ?? ''); ?></textarea>
                    <button onclick="saveGoal()" class="mt-2 px-3 py-1 bg-green-500 text-white rounded text-sm w-full">저장</button>
                </div>
            </div>
        </div>

        <!-- 실용적인 학습 도구 -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- 요약 작성 도구 -->
            <div class="glass p-6 card-hover">
                <h2 class="text-xl font-bold mb-4 gradient-text">
                    <i class="fas fa-pen-to-square mr-2"></i>오늘 배운 내용 정리
                </h2>
                <div class="space-y-3">
                    <input 
                        type="text" 
                        id="summaryChapter" 
                        class="w-full px-3 py-2 border rounded-lg"
                        placeholder="단원명 (예: 미적분 - 도함수)"
                    >
                    <textarea 
                        id="summaryContent" 
                        class="w-full px-3 py-2 border rounded-lg" 
                        rows="5"
                        placeholder="오늘 배운 핵심 내용을 3줄로 요약해보세요..."></textarea>
                    
                    <div class="bg-yellow-50 p-3 rounded-lg">
                        <p class="text-sm text-yellow-800">
                            💡 Tip: 핵심 공식, 중요 개념, 실수하기 쉬운 부분을 포함하세요!
                        </p>
                    </div>
                    
                    <button 
                        onclick="saveSummary()" 
                        class="w-full py-2 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:opacity-90">
                        요약 저장하기
                    </button>
                </div>
            </div>

            <!-- 오답 노트 -->
            <div class="glass p-6 card-hover">
                <h2 class="text-xl font-bold mb-4 gradient-text">
                    <i class="fas fa-book mr-2"></i>오답 노트
                </h2>
                <div class="space-y-3">
                    <input 
                        type="text" 
                        id="errorProblem" 
                        class="w-full px-3 py-2 border rounded-lg"
                        placeholder="문제 번호/출처 (예: 모의고사 15번)"
                    >
                    <select id="errorType" class="w-full px-3 py-2 border rounded-lg">
                        <option value="">오류 유형 선택</option>
                        <option value="calculation">계산 실수</option>
                        <option value="concept">개념 이해 부족</option>
                        <option value="application">응용력 부족</option>
                        <option value="careless">부주의 (문제 오독 등)</option>
                    </select>
                    <textarea 
                        id="errorReason" 
                        class="w-full px-3 py-2 border rounded-lg" 
                        rows="3"
                        placeholder="왜 틀렸는지 분석..."></textarea>
                    <textarea 
                        id="errorSolution" 
                        class="w-full px-3 py-2 border rounded-lg" 
                        rows="3"
                        placeholder="올바른 풀이 과정..."></textarea>
                    
                    <button 
                        onclick="saveError()" 
                        class="w-full py-2 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:opacity-90">
                        오답 노트 저장
                    </button>
                </div>
            </div>
        </div>

        <!-- 학습 현황 대시보드 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mt-8">
            
            <!-- 최근 퀴즈 결과 -->
            <div class="glass p-6">
                <h3 class="text-lg font-bold mb-3 gradient-text">📊 최근 퀴즈 결과</h3>
                <?php if (count($recent_quizzes) > 0): ?>
                <div class="space-y-2">
                    <?php foreach($recent_quizzes as $quiz): 
                        $percentage = round(($quiz['sumgrades'] / $quiz['maxgrade']) * 100);
                        $color = $percentage >= 80 ? 'green' : ($percentage >= 60 ? 'yellow' : 'red');
                    ?>
                    <div class="p-3 border rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="font-semibold text-sm"><?php echo htmlspecialchars($quiz['name']); ?></span>
                            <span class="text-<?php echo $color; ?>-600 font-bold"><?php echo $percentage; ?>%</span>
                        </div>
                        <div class="bg-gray-200 rounded-full h-2 mt-2">
                            <div class="bg-<?php echo $color; ?>-500 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500">아직 퀴즈 기록이 없습니다.</p>
                <?php endif; ?>
            </div>

            <!-- 과제 제출 현황 -->
            <div class="glass p-6">
                <h3 class="text-lg font-bold mb-3 gradient-text">📝 과제 제출 현황</h3>
                <?php if (count($recent_assignments) > 0): ?>
                <div class="space-y-2">
                    <?php foreach($recent_assignments as $assignment): ?>
                    <div class="p-3 border rounded-lg">
                        <div class="flex justify-between items-center">
                            <span class="text-sm"><?php echo htmlspecialchars($assignment['name']); ?></span>
                            <span class="text-green-600 text-xs">✅ 제출완료</span>
                        </div>
                        <span class="text-xs text-gray-500"><?php echo date('m/d', $assignment['timemodified']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-gray-500">최근 제출한 과제가 없습니다.</p>
                <?php endif; ?>
            </div>

        </div>

        <!-- 빠른 실행 버튼 -->
        <div class="glass p-6 mt-8">
            <div class="flex flex-wrap gap-4 justify-center">
                <button onclick="location.href='save_summary.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-pen mr-2"></i>상세 요약 작성
                </button>
                <button onclick="location.href='save_error.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-search mr-2"></i>오답 분석 도구
                </button>
                <button onclick="location.href='../../learning_tracker.php'" 
                        class="px-6 py-3 bg-gradient-to-r from-green-600 to-blue-600 text-white rounded-lg hover:opacity-90">
                    <i class="fas fa-chart-line mr-2"></i>학습 통계 보기
                </button>
            </div>
        </div>
    </div>

    <script>
    // 학습 타이머
    let timerInterval;
    let seconds = 0;
    
    function startTimer() {
        if (!timerInterval) {
            timerInterval = setInterval(() => {
                seconds++;
                updateTimerDisplay();
            }, 1000);
        }
    }
    
    function pauseTimer() {
        clearInterval(timerInterval);
        timerInterval = null;
    }
    
    function resetTimer() {
        pauseTimer();
        seconds = 0;
        updateTimerDisplay();
    }
    
    function updateTimerDisplay() {
        const hours = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        document.getElementById('studyTimer').textContent = 
            `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
    }
    
    // 목표 저장
    function saveGoal() {
        const goal = document.getElementById('todayGoal').value;
        fetch('ajax/save_goal.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({goal: goal})
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('목표가 저장되었습니다!');
            }
        });
    }
    
    // 요약 저장 (간단 버전)
    function saveSummary() {
        const chapter = document.getElementById('summaryChapter').value;
        const content = document.getElementById('summaryContent').value;
        
        if (!chapter || !content) {
            alert('단원명과 내용을 모두 입력해주세요!');
            return;
        }
        
        fetch('ajax/quick_save_summary.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                chapter: chapter,
                content: content
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('요약이 저장되었습니다!');
                document.getElementById('summaryChapter').value = '';
                document.getElementById('summaryContent').value = '';
            }
        });
    }
    
    // 오답 저장 (간단 버전)
    function saveError() {
        const problem = document.getElementById('errorProblem').value;
        const type = document.getElementById('errorType').value;
        const reason = document.getElementById('errorReason').value;
        const solution = document.getElementById('errorSolution').value;
        
        if (!problem || !type || !reason) {
            alert('필수 항목을 모두 입력해주세요!');
            return;
        }
        
        fetch('ajax/quick_save_error.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                problem: problem,
                error_reason: reason,
                correct_solution: solution
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('오답 노트가 저장되었습니다!');
                document.getElementById('errorProblem').value = '';
                document.getElementById('errorType').value = '';
                document.getElementById('errorReason').value = '';
                document.getElementById('errorSolution').value = '';
            }
        });
    }
    
    </script>
</body>
</html>