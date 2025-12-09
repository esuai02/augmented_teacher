<?php
// 직접 DB 연결 방식으로 변경
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// DB 접속 정보 직접 설정
$CFG = new stdClass();
$CFG->dbhost = '58.180.27.46';
$CFG->dbname = 'mathking';
$CFG->dbuser = 'moodle';
$CFG->dbpass = '@MCtrigd7128';
$CFG->prefix = 'mdl_';

try {
    // PDO 연결
    $dsn = "mysql:host={$CFG->dbhost};dbname={$CFG->dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $CFG->dbuser, $CFG->dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    // URL 파라미터 또는 세션에서 userid 가져오기
    $userid = isset($_GET['userid']) ? intval($_GET['userid']) : (isset($_SESSION['userid']) ? $_SESSION['userid'] : null);
    
    // userid가 없으면 index.php로 리다이렉트
    if (!$userid) {
        header('Location: index.php');
        exit;
    }
    
    // 사용자 이름 가져오기
    $stmt = $pdo->prepare("SELECT firstname, lastname FROM mdl_user WHERE id = ?");
    $stmt->execute([$userid]);
    $user = $stmt->fetch();
    $username = $user ? $user['firstname'] . ' ' . $user['lastname'] : '사용자';

    // 데이터베이스에서 사용자 정보 조회
    $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_users WHERE userid = ?");
    $stmt->execute([$userid]);
    $user_info = $stmt->fetch();
    
    $exam_info = null;
    $exam_dates = null;
    $study_status = null;
    
    if ($user_info) {
        // 시험 정보 조회
        $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exams WHERE school_name = ? AND grade = ? LIMIT 1");
        $stmt->execute([$user_info['school_name'], $user_info['grade']]);
        $exam_info = $stmt->fetch();
        
        if ($exam_info) {
            // 시험 날짜 정보 조회
            $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exam_dates WHERE exam_id = ? AND user_id = ?");
            $stmt->execute([$exam_info['exam_id'], $user_info['id']]);
            $exam_dates = $stmt->fetch();
            
            // 학습 상태 조회
            $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_study_status WHERE user_id = ? AND exam_id = ?");
            $stmt->execute([$user_info['id'], $exam_info['exam_id']]);
            $study_status = $stmt->fetch();
        }
    }

    // D-Day 계산
    $dday = null;
    $phase = 'prepare'; // 기본값: 준비 단계
    if ($exam_dates && $exam_dates['math_date']) {
        $exam_date = new DateTime($exam_dates['math_date']);
        $today = new DateTime();
        $interval = $today->diff($exam_date);
        $dday = $interval->invert ? -$interval->days : $interval->days;
        
        // 단계 결정
        if ($dday <= 7) {
            $phase = 'finish'; // 마무리 단계
        } elseif ($dday <= 21) {
            $phase = 'intensive'; // 정진 단계
        } else {
            $phase = 'prepare'; // 준비 단계
        }
    }
    
    // 학습 진행률 계산 (임시)
    $progress = 65; // 실제로는 DB에서 계산
    
} catch (Exception $e) {
    echo "데이터베이스 연결 오류: " . $e->getMessage();
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>학습 대시보드 - Mathking</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* 단계별 색상 변수 */
            --phase-prepare-primary: #4F46E5; /* 인디고 */
            --phase-prepare-secondary: #818CF8;
            --phase-prepare-bg: #EEF2FF;
            
            --phase-intensive-primary: #DC2626; /* 레드 */
            --phase-intensive-secondary: #F87171;
            --phase-intensive-bg: #FEF2F2;
            
            --phase-finish-primary: #059669; /* 그린 */
            --phase-finish-secondary: #34D399;
            --phase-finish-bg: #ECFDF5;
            
            /* 현재 단계 색상 */
            --current-primary: var(--phase-<?php echo $phase; ?>-primary);
            --current-secondary: var(--phase-<?php echo $phase; ?>-secondary);
            --current-bg: var(--phase-<?php echo $phase; ?>-bg);
        }
        
        /* 글라스모피즘 효과 */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .glass-dark {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        /* 애니메이션 */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        /* 프로그레스 바 */
        .progress-bar {
            background: linear-gradient(90deg, var(--current-primary) 0%, var(--current-secondary) 100%);
            transition: width 0.5s ease;
        }
        
        /* 카드 호버 효과 */
        .card-hover {
            transition: all 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        /* 스켈레톤 로딩 */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Quick Drawer */
        .quick-drawer {
            transition: all 0.3s ease;
        }
        
        .quick-drawer.collapsed {
            width: 60px;
        }
        
        .quick-drawer.expanded {
            width: 250px;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- 상단 헤더 (Mathking 기존 헤더 유지) -->
    <header class="fixed top-0 left-0 right-0 z-50 glass shadow-lg">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <a href="/" class="text-2xl font-bold text-indigo-600">Mathking</a>
                    <nav class="hidden md:flex space-x-4">
                        <a href="#" class="text-gray-700 hover:text-indigo-600">학습하기</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600">문제풀이</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600">자료실</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600">커뮤니티</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="p-2 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-bell text-gray-600"></i>
                    </button>
                    <div class="flex items-center space-x-2">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($username); ?>&background=4F46E5&color=fff" 
                             alt="Profile" class="w-8 h-8 rounded-full">
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 메인 컨테이너 -->
    <main class="pt-20 min-h-screen">
        <!-- 미니멀 헤더 (숲) -->
        <section class="container mx-auto px-4 py-6">
            <div class="glass rounded-2xl p-6 shadow-xl fade-in">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- D-Day 표시 -->
                    <div class="text-center">
                        <div class="text-4xl font-bold" style="color: var(--current-primary);">
                            <?php if ($dday !== null): ?>
                                D<?php echo $dday >= 0 ? '-' : '+'; ?><?php echo abs($dday); ?>
                            <?php else: ?>
                                D-?
                            <?php endif; ?>
                        </div>
                        <div class="text-sm text-gray-600 mt-1">
                            <?php echo $exam_info ? htmlspecialchars($exam_info['exam_type']) : '시험 정보 없음'; ?>
                        </div>
                    </div>
                    
                    <!-- 시험 정보 -->
                    <div>
                        <div class="text-sm text-gray-500">시험 정보</div>
                        <div class="font-medium">
                            <?php if ($user_info): ?>
                                <?php echo htmlspecialchars($user_info['school_name']); ?> 
                                <?php echo $user_info['grade']; ?>학년
                            <?php else: ?>
                                정보 없음
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 오늘 목표 -->
                    <div>
                        <div class="text-sm text-gray-500">오늘의 목표</div>
                        <div class="font-medium">
                            <?php 
                            switch($phase) {
                                case 'prepare': echo '기초 개념 완성하기'; break;
                                case 'intensive': echo '약점 집중 보완하기'; break;
                                case 'finish': echo '핵심 내용 정리하기'; break;
                            }
                            ?>
                        </div>
                    </div>
                    
                    <!-- 진행률 -->
                    <div>
                        <div class="text-sm text-gray-500 mb-1">전체 진행률</div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="progress-bar h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <div class="text-sm font-medium mt-1"><?php echo $progress; ?>% 완료</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 중앙 핵심 콘텐츠 (나무) -->
        <section class="container mx-auto px-4 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <?php if ($phase == 'prepare'): ?>
                <!-- 준비 단계 카드들 -->
                <div class="glass rounded-2xl p-6 card-hover fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">이번 주 학습 계획</h3>
                        <i class="fas fa-calendar-alt text-2xl" style="color: var(--current-primary);"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                            <span>수학 개념 정리</span>
                            <span class="text-sm text-gray-500">3/5 완료</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                            <span>기본 문제 풀이</span>
                            <span class="text-sm text-gray-500">2/3 완료</span>
                        </div>
                    </div>
                    <button class="w-full mt-4 py-2 text-sm rounded-lg hover:opacity-90" 
                            style="background: var(--current-primary); color: white;">
                        전체 계획 보기
                    </button>
                </div>

                <div class="glass rounded-2xl p-6 card-hover fade-in" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">추천 학습 자료</h3>
                        <i class="fas fa-book text-2xl" style="color: var(--current-primary);"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="p-4 bg-white rounded-lg">
                            <h4 class="font-medium mb-1">함수의 극한과 연속</h4>
                            <p class="text-sm text-gray-600">핵심 개념 정리 노트</p>
                            <div class="flex items-center mt-2 text-xs text-gray-500">
                                <i class="fas fa-eye mr-1"></i> 234명 학습
                            </div>
                        </div>
                    </div>
                    <button class="w-full mt-4 py-2 text-sm rounded-lg hover:opacity-90" 
                            style="background: var(--current-primary); color: white;">
                        더 많은 자료 보기
                    </button>
                </div>

                <div class="glass rounded-2xl p-6 card-hover fade-in" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">학습 가이드</h3>
                        <i class="fas fa-compass text-2xl" style="color: var(--current-primary);"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="p-4 bg-white rounded-lg">
                            <h4 class="font-medium mb-2">이번 주 추천 학습법</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>• 매일 30분씩 개념 복습</li>
                                <li>• 기본 예제 3문제씩 풀기</li>
                                <li>• 오답노트 정리하기</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?php elseif ($phase == 'intensive'): ?>
                <!-- 정진 단계 카드들 -->
                <div class="glass rounded-2xl p-6 card-hover fade-in">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">약점 보완</h3>
                        <i class="fas fa-chart-line text-2xl" style="color: var(--current-primary);"></i>
                    </div>
                    <div class="space-y-3">
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <h4 class="font-medium text-red-800 mb-1">미분 응용 문제</h4>
                            <p class="text-sm text-red-600">정답률 45% - 집중 학습 필요</p>
                            <button class="mt-2 px-4 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600">
                                바로 학습하기
                            </button>
                        </div>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6 card-hover fade-in" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">맞춤형 문제</h3>
                        <i class="fas fa-puzzle-piece text-2xl" style="color: var(--current-primary);"></i>
                    </div>
                    <div class="p-4 bg-white rounded-lg">
                        <h4 class="font-medium mb-2">오늘의 추천 문제</h4>
                        <p class="text-sm text-gray-600 mb-3">약점 단원 중심 15문제</p>
                        <button class="w-full py-2 bg-red-500 text-white rounded hover:bg-red-600">
                            문제 풀러 가기
                        </button>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6 card-hover fade-in" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">오답노트</h3>
                        <i class="fas fa-exclamation-circle text-2xl" style="color: var(--current-primary);"></i>
                    </div>
                    <div class="text-center py-4">
                        <div class="text-3xl font-bold text-red-500 mb-1">23</div>
                        <div class="text-sm text-gray-600">미해결 오답 문제</div>
                        <button class="mt-3 px-6 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                            오답 다시 풀기
                        </button>
                    </div>
                </div>

                <?php else: ?>
                <!-- 마무리 단계 카드들 -->
                <div class="glass rounded-2xl p-6 card-hover fade-in bg-green-50">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">라스트 청킹</h3>
                        <i class="fas fa-rocket text-2xl text-green-600"></i>
                    </div>
                    <div class="p-4 bg-white rounded-lg">
                        <h4 class="font-medium mb-2">시험 전 최종 점검</h4>
                        <ul class="text-sm space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                핵심 공식 정리
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                자주 틀리는 유형
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-circle text-gray-300 mr-2"></i>
                                실전 모의고사
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6 card-hover fade-in bg-green-50" style="animation-delay: 0.1s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">필수 요약 노트</h3>
                        <i class="fas fa-file-alt text-2xl text-green-600"></i>
                    </div>
                    <div class="space-y-3">
                        <button class="w-full p-3 bg-white rounded-lg text-left hover:bg-green-100">
                            <div class="font-medium">수학 공식 총정리</div>
                            <div class="text-sm text-gray-500">5분 요약본</div>
                        </button>
                        <button class="w-full p-3 bg-white rounded-lg text-left hover:bg-green-100">
                            <div class="font-medium">빈출 문제 유형</div>
                            <div class="text-sm text-gray-500">Top 10</div>
                        </button>
                    </div>
                </div>

                <div class="glass rounded-2xl p-6 card-hover fade-in bg-green-50" style="animation-delay: 0.2s;">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-xl font-bold">응원 메시지</h3>
                        <i class="fas fa-heart text-2xl text-green-600"></i>
                    </div>
                    <div class="text-center py-6">
                        <div class="text-6xl mb-4">💪</div>
                        <p class="text-lg font-medium mb-2">할 수 있어요!</p>
                        <p class="text-sm text-gray-600">
                            지금까지 열심히 준비했어요.<br>
                            자신감을 가지고 시험 보세요!
                        </p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- 빠른 실행 & 서랍 (고정 사이드바) -->
        <aside id="quickDrawer" class="fixed right-0 top-24 bottom-0 quick-drawer collapsed glass shadow-xl">
            <div class="p-4 h-full flex flex-col">
                <!-- 토글 버튼 -->
                <button onclick="toggleDrawer()" class="mb-6 text-gray-600 hover:text-gray-800">
                    <i id="drawerToggleIcon" class="fas fa-chevron-left text-xl"></i>
                </button>
                
                <!-- 메뉴 아이템들 -->
                <div class="space-y-4 flex-1">
                    <!-- AI 비법노트 -->
                    <button class="w-full text-left flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-brain text-xl text-purple-500"></i>
                        <span class="drawer-text hidden">AI 비법노트</span>
                    </button>
                    
                    <!-- 시험 자료 서랍 -->
                    <button class="w-full text-left flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-folder-open text-xl text-blue-500"></i>
                        <span class="drawer-text hidden">시험 자료 서랍</span>
                    </button>
                    
                    <!-- 수학 일기 -->
                    <button class="w-full text-left flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-book-open text-xl text-green-500"></i>
                        <span class="drawer-text hidden">수학 일기</span>
                    </button>
                    
                    <!-- 설정 -->
                    <button class="w-full text-left flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100">
                        <i class="fas fa-cog text-xl text-gray-500"></i>
                        <span class="drawer-text hidden">설정</span>
                    </button>
                </div>
                
                <!-- 다크모드 토글 -->
                <button onclick="toggleDarkMode()" class="w-full text-left flex items-center space-x-3 p-3 rounded-lg hover:bg-gray-100 mt-auto">
                    <i class="fas fa-moon text-xl text-gray-600"></i>
                    <span class="drawer-text hidden">다크모드</span>
                </button>
            </div>
        </aside>
    </main>

    <!-- 온보딩 모달 (첫 방문시) -->
    <div id="onboardingModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl p-8 max-w-md mx-4 slide-in">
            <h2 class="text-2xl font-bold mb-4">환영합니다! 👋</h2>
            <p class="text-gray-600 mb-6">
                학습 대시보드는 시험일까지 남은 기간에 따라<br>
                자동으로 최적의 학습 콘텐츠를 제공합니다.
            </p>
            <div class="space-y-3 mb-6">
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-indigo-500"></div>
                    <span><strong>준비 단계:</strong> 기초 다지기 (3주 이상)</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-red-500"></div>
                    <span><strong>정진 단계:</strong> 약점 보완 (1-3주)</span>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="w-3 h-3 rounded-full bg-green-500"></div>
                    <span><strong>마무리 단계:</strong> 최종 정리 (1주 이내)</span>
                </div>
            </div>
            <button onclick="closeOnboarding()" class="w-full py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                시작하기
            </button>
        </div>
    </div>

    <script>
        // Quick Drawer 토글
        function toggleDrawer() {
            const drawer = document.getElementById('quickDrawer');
            const icon = document.getElementById('drawerToggleIcon');
            const texts = document.querySelectorAll('.drawer-text');
            
            if (drawer.classList.contains('collapsed')) {
                drawer.classList.remove('collapsed');
                drawer.classList.add('expanded');
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
                texts.forEach(text => text.classList.remove('hidden'));
            } else {
                drawer.classList.remove('expanded');
                drawer.classList.add('collapsed');
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
                texts.forEach(text => text.classList.add('hidden'));
            }
        }
        
        // 다크모드 토글
        function toggleDarkMode() {
            document.body.classList.toggle('dark');
            // 실제 구현시 localStorage에 저장
        }
        
        // 온보딩 모달 닫기
        function closeOnboarding() {
            document.getElementById('onboardingModal').classList.add('hidden');
            // localStorage에 방문 기록 저장
            localStorage.setItem('dashboardVisited', 'true');
        }
        
        // 첫 방문 체크
        window.addEventListener('DOMContentLoaded', function() {
            if (!localStorage.getItem('dashboardVisited')) {
                document.getElementById('onboardingModal').classList.remove('hidden');
            }
            
            // 실시간 업데이트 (예: D-Day, 진행률)
            setInterval(updateRealTimeData, 60000); // 1분마다
        });
        
        // 실시간 데이터 업데이트
        function updateRealTimeData() {
            // Ajax로 최신 데이터 가져오기
            fetch('get_dashboard_data.php')
                .then(response => response.json())
                .then(data => {
                    // D-Day, 진행률 등 업데이트
                    console.log('Dashboard updated');
                });
        }
        
        // 카드 클릭 이벤트
        document.querySelectorAll('.card-hover').forEach(card => {
            card.addEventListener('click', function() {
                // 카드별 상세 페이지로 이동 또는 모달 열기
            });
        });
    </script>
</body>
</html>