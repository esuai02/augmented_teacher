<?php
// 직접 DB 연결 방식
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// config.php 포함 (OpenAI API 설정)
require_once 'config.php';

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
    $exam_scope = null;
    
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
            
            // 시험 범위 조회
            $stmt = $pdo->prepare("SELECT * FROM mdl_alt42t_exam_resources WHERE exam_id = ? AND user_id = ?");
            $stmt->execute([$exam_info['exam_id'], $user_info['id']]);
            $exam_resource = $stmt->fetch();
            if ($exam_resource && $exam_resource['tip_text']) {
                // "시험 범위: " 접두사 제거
                $exam_scope = str_replace('시험 범위: ', '', $exam_resource['tip_text']);
            }
        }
    }

    // D-Day 계산 (시험 시작 날짜 기준)
    $dday = null;
    $phase = 'prepare'; // 기본값: 준비 단계
    if ($exam_dates && $exam_dates['start_date']) {
        $exam_date = new DateTime($exam_dates['start_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        $exam_date->setTime(0, 0, 0);
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
    
    // D-Day별 디자인 설정 함수
    function getDesignConfig($dday) {
        if ($dday === null || $dday < 0) {
            return [
                'intensity' => 0.1,
                'primaryColor' => 'from-gray-400 to-gray-500',
                'bgGradient' => 'from-gray-50 via-white to-gray-50',
                'borderColor' => 'border-gray-200',
                'emotionIcon' => '🕊️',
                'emotionTitle' => '시험이 끝났습니다',
                'emotionSubtitle' => '수고하셨습니다. 편하게 쉬세요.',
                'animation' => '',
                'buttonColor' => 'bg-gradient-to-r from-gray-400 to-gray-500 hover:from-gray-500 hover:to-gray-600',
                'showImportant' => false,
                'showAdditional' => false,
                'focusItems' => []
            ];
        }
        
        if ($dday >= 10) {
            return [
                'intensity' => 1,
                'primaryColor' => 'from-red-500 to-orange-500',
                'bgGradient' => 'from-red-50 via-orange-50 to-yellow-50',
                'borderColor' => 'border-red-200',
                'emotionIcon' => '🔥',
                'emotionTitle' => '열정적으로 시작하세요!',
                'emotionSubtitle' => '충분한 시간이 있습니다. 체계적으로 준비하세요.',
                'animation' => 'animate-pulse',
                'buttonColor' => 'bg-gradient-to-r from-red-500 to-orange-500 hover:from-red-600 hover:to-orange-600',
                'showImportant' => true,
                'showAdditional' => true,
                'focusItems' => [
                    ['📚', '전 범위 개념 정리', '2시간'],
                    ['✏️', '기본 문제 풀기', '50문제'],
                    ['📖', '오답노트 작성하기', '30분'],
                    ['🎯', '약점 분석하기', '20분']
                ]
            ];
        }
        
        if ($dday == 9) {
            return [
                'intensity' => 0.9,
                'primaryColor' => 'from-orange-500 to-amber-500',
                'bgGradient' => 'from-orange-50 via-amber-50 to-yellow-50',
                'borderColor' => 'border-orange-200',
                'emotionIcon' => '🎯',
                'emotionTitle' => '꾸준히 진행하세요!',
                'emotionSubtitle' => '매일 조금씩 실력이 늘고 있어요.',
                'animation' => 'animate-pulse',
                'buttonColor' => 'bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600',
                'showImportant' => true,
                'showAdditional' => true,
                'focusItems' => [
                    ['📚', '핵심 개념 복습', '1.5시간'],
                    ['✏️', '유형별 문제', '40문제'],
                    ['📖', '오답 정리하기', '20분']
                ]
            ];
        }
        
        if ($dday >= 7) {
            return [
                'intensity' => 0.7,
                'primaryColor' => 'from-yellow-500 to-green-500',
                'bgGradient' => 'from-yellow-50 via-green-50 to-emerald-50',
                'borderColor' => 'border-yellow-200',
                'emotionIcon' => '✨',
                'emotionTitle' => '집중력을 높여가세요',
                'emotionSubtitle' => '이제 핵심에 집중할 시간입니다.',
                'animation' => '',
                'buttonColor' => 'bg-gradient-to-r from-yellow-500 to-green-500 hover:from-yellow-600 hover:to-green-600',
                'showImportant' => true,
                'showAdditional' => false,
                'focusItems' => [
                    ['🎯', '핵심 유형 집중', '1시간'],
                    ['📝', '빈출 문제', '30문제'],
                    ['⚡', '약점 보완하기', '30분']
                ]
            ];
        }
        
        if ($dday >= 5) {
            return [
                'intensity' => 0.5,
                'primaryColor' => 'from-green-500 to-cyan-500',
                'bgGradient' => 'from-green-50 via-cyan-50 to-blue-50',
                'borderColor' => 'border-green-200',
                'emotionIcon' => '🍃',
                'emotionTitle' => '차분하게 정리하세요',
                'emotionSubtitle' => '급하지 않게, 꼼꼼하게 체크하세요.',
                'animation' => '',
                'buttonColor' => 'bg-gradient-to-r from-green-500 to-cyan-500 hover:from-green-600 hover:to-cyan-600',
                'showImportant' => false,
                'showAdditional' => false,
                'focusItems' => [
                    ['✅', '핵심 공식 정리', '30분'],
                    ['📋', '중요 문제 복습', '20문제']
                ]
            ];
        }
        
        if ($dday == 4) {
            return [
                'intensity' => 0.4,
                'primaryColor' => 'from-cyan-500 to-blue-500',
                'bgGradient' => 'from-cyan-50 via-blue-50 to-indigo-50',
                'borderColor' => 'border-cyan-200',
                'emotionIcon' => '💙',
                'emotionTitle' => '편안하게 복습하세요',
                'emotionSubtitle' => '긴장하지 마세요. 충분히 준비했어요.',
                'animation' => '',
                'buttonColor' => 'bg-gradient-to-r from-cyan-500 to-blue-500 hover:from-cyan-600 hover:to-blue-600',
                'showImportant' => false,
                'showAdditional' => false,
                'focusItems' => [
                    ['💡', '핵심 요약 확인', '30분'],
                    ['✨', '자주 틀린 문제', '10문제']
                ]
            ];
        }
        
        if ($dday == 3) {
            return [
                'intensity' => 0.3,
                'primaryColor' => 'from-blue-500 to-indigo-500',
                'bgGradient' => 'from-blue-50 via-indigo-50 to-purple-50',
                'borderColor' => 'border-blue-200',
                'emotionIcon' => '☁️',
                'emotionTitle' => '마음을 편안하게',
                'emotionSubtitle' => '깊게 숨쉼고, 자신감을 가지세요.',
                'animation' => '',
                'buttonColor' => 'bg-gradient-to-r from-blue-500 to-indigo-500 hover:from-blue-600 hover:to-indigo-600',
                'showImportant' => false,
                'showAdditional' => false,
                'focusItems' => [
                    ['🎯', '최종 핵심 정리', '20분'],
                    ['☑️', '실수 체크리스트', '확인']
                ]
            ];
        }
        
        if ($dday == 2) {
            return [
                'intensity' => 0.2,
                'primaryColor' => 'from-indigo-500 to-purple-500',
                'bgGradient' => 'from-indigo-50 via-purple-50 to-pink-50',
                'borderColor' => 'border-indigo-200',
                'emotionIcon' => '🌙',
                'emotionTitle' => '가볍게 마무리',
                'emotionSubtitle' => '과도한 학습보다 컨디션 관리가 중요해요.',
                'animation' => '',
                'buttonColor' => 'bg-gradient-to-r from-indigo-500 to-purple-500 hover:from-indigo-600 hover:to-purple-600',
                'showImportant' => false,
                'showAdditional' => false,
                'focusItems' => [
                    ['📝', '핵심 공식만 확인', '15분'],
                    ['😌', '충분한 휴식 취하기', '중요']
                ]
            ];
        }
        
        return [
            'intensity' => 0.1,
            'primaryColor' => 'from-gray-400 to-gray-500',
            'bgGradient' => 'from-gray-50 via-white to-gray-50',
            'borderColor' => 'border-gray-200',
            'emotionIcon' => '🦆',
            'emotionTitle' => '편안한 마음으로',
            'emotionSubtitle' => '최선을 다했습니다. 자신을 믿으세요.',
            'animation' => '',
            'buttonColor' => 'bg-gradient-to-r from-gray-400 to-gray-500 hover:from-gray-500 hover:to-gray-600',
            'showImportant' => false,
            'showAdditional' => false,
            'focusItems' => [
                ['✅', '준비물 확인', '5분'],
                ['🧘', '마음 편안히 갖기', '명상']
            ]
        ];
    }
    
    // D-Day 설정 가져오기
    $designConfig = getDesignConfig($dday);
    
    
    // 학습 진행률 계산 (임시)
    $progress = 65; // 실제로는 DB에서 계산
    
    // 목표 데이터 조회 (info_goal.php 참고)
    $todayGoal = $pdo->prepare("SELECT * FROM mdl_abessi_today WHERE userid = ? AND type LIKE '오늘목표' ORDER BY id DESC LIMIT 1");
    $todayGoal->execute([$userid]);
    $todayGoalData = $todayGoal->fetch();
    
    $weeklyGoal = $pdo->prepare("SELECT * FROM mdl_abessi_today WHERE userid = ? AND type LIKE '주간목표' ORDER BY id DESC LIMIT 1");
    $weeklyGoal->execute([$userid]);
    $weeklyGoalData = $weeklyGoal->fetch();
    
    $quarterlyGoal = $pdo->prepare("SELECT * FROM mdl_abessi_today WHERE userid = ? AND type LIKE '시험목표' ORDER BY id DESC LIMIT 1");
    $quarterlyGoal->execute([$userid]);
    $quarterlyGoalData = $quarterlyGoal->fetch();
    
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
        
        /* 애니메이션 */
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: .5;
            }
        }
        
        /* 글라스모피즘 효과 */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* 애니메이션 */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* 프로그레스 바 */
        .progress-bar {
            background: linear-gradient(90deg, var(--current-primary) 0%, var(--current-secondary) 100%);
            transition: width 0.5s ease;
        }
        
        /* 탭 스타일 */
        .tab-btn {
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .tab-btn.active {
            color: #6b46c1;
            border-bottom: 2px solid #6b46c1;
        }
        
        .tab-panel {
            display: none;
        }
        
        .tab-panel.active {
            display: block;
        }
        
        /* 알림 배지 */
        .notification-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-gradient-to-br <?php echo $designConfig['bgGradient']; ?> transition-all duration-1000">
    <!-- 상단 헤더 (Mathking 스타일) -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-6">
                    <a href="index.php?userid=<?php echo $userid; ?>" class="text-2xl font-bold text-indigo-600">Mathking</a>
                    <nav class="hidden md:flex space-x-4">
                        <a href="#" class="text-gray-700 hover:text-indigo-600">학습하기</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600">문제풀이</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600">자료실</a>
                        <a href="#" class="text-gray-700 hover:text-indigo-600">커뮤니티</a>
                    </nav>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="showNotifications()" class="relative p-2 text-gray-600 hover:text-gray-800">
                        <i class="fas fa-bell"></i>
                    </button>
                    <a href="last_chunking.php?userid=<?php echo $userid; ?>" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                        <span>라스트 청킹</span>
                        <i class="fas fa-chevron-right text-xs"></i>
                    </a>
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium"><?php echo htmlspecialchars($username); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- 메인 대시보드 -->
    <main class="min-h-screen">
        <!-- 미니멀 헤더 -->
        <div class="p-6 pb-0">
            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-2xl font-light text-gray-800">대시보드</h1>
                    <!-- D-Day 표시 -->   
                    <?php if($dday !== null && $dday >= 0): ?>
                    <div class="px-4 py-2 rounded-full bg-gradient-to-r <?php echo $designConfig['primaryColor']; ?> text-white font-bold shadow-lg <?php echo $designConfig['animation']; ?>">
                        D-<?php echo $dday; ?>
                    </div>
                    <?php endif; ?>
                    <!-- 모드 스위치 -->
                    <div class="flex bg-gray-200 rounded-lg p-1">
                        <button id="scroll-mode-btn" onclick="setDashboardMode('scroll')" class="px-3 py-1 text-sm rounded bg-white text-gray-800 shadow-sm transition-all cursor-pointer">스크롤</button>
                        <button id="tab-mode-btn" onclick="setDashboardMode('tab')" class="px-3 py-1 text-sm rounded text-gray-600 hover:text-gray-800 transition-all cursor-pointer">탭</button>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <p id="current-time" class="text-xl font-light text-gray-800"></p>
                        <p class="text-xs text-gray-500">Focus Mode</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 스크롤 모드 컨테이너 -->
        <div id="scroll-mode-container" class="">
            <!-- 모드 설명 -->
            <div class="px-4 lg:px-6 pt-2 pb-0">
                <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-3 lg:p-4 mb-4 border border-blue-200">
                    <div class="flex items-start gap-2 lg:gap-3">
                        <span class="text-lg lg:text-2xl">📜</span>
                        <div>
                            <h3 class="font-bold text-blue-800 mb-1 text-sm lg:text-base">스크롤 모드: 전체 현황 파악</h3>
                            <p class="text-xs lg:text-sm text-blue-700 leading-relaxed">
                                모든 학습 정보를 한 페이지에서 종합적으로 확인 • 진행도부터 목표까지 전체 상황을 빠르게 파악 • 전반적인 학습 상태를 한눈에 비교
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 스크롤 모드 내용 -->
            <div class="px-4 lg:px-6 py-4 overflow-y-auto" style="max-height: calc(100vh - 200px);">
                <!-- 감정 메시지 섹션 -->
                <?php if($dday !== null && $dday >= 0): ?>
                <div class="mb-6 p-6 bg-white/90 rounded-2xl shadow-lg <?php echo $designConfig['borderColor']; ?> border-2 transition-all duration-500">
                    <div class="flex items-center gap-4">
                        <span class="text-3xl"><?php echo $designConfig['emotionIcon']; ?></span>
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800"><?php echo $designConfig['emotionTitle']; ?></h2>
                            <p class="text-gray-600 mt-1"><?php echo $designConfig['emotionSubtitle']; ?></p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- 상단 요약 카드들 -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
                    <!-- D-Day 카드 (Essential - 항상 표시) -->
                    <div class="bg-gradient-to-r <?php echo $designConfig['primaryColor']; ?> rounded-xl p-4 lg:p-6 text-center text-white shadow-lg <?php echo $designConfig['animation']; ?>">
                        <p class="text-xs lg:text-sm mb-2 opacity-90">시험까지</p>
                        <p class="text-2xl lg:text-3xl font-bold">
                            <?php if ($dday !== null && $dday >= 0): ?>
                                D-<?php echo $dday; ?>
                            <?php elseif ($dday < 0): ?>
                                D+<?php echo abs($dday); ?>
                            <?php else: ?>
                                D-?
                            <?php endif; ?>
                        </p>
                        <?php if($dday !== null && $dday >= 0): ?>
                        <?php $progress = max(0, min(100, ((10 - min($dday, 10)) / 10) * 100)); ?>
                        <div class="mt-2 bg-white/20 rounded-full h-2 backdrop-blur-sm">
                            <div class="h-2 bg-white rounded-full transition-all duration-1000 shadow-sm" style="width: <?php echo $progress; ?>%"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <!-- 학교 정보 -->
                    <div class="bg-white rounded-xl p-4 lg:p-6 shadow-lg">
                        <p class="text-xs lg:text-sm text-gray-600 mb-2">학교 · 학년</p>
                        <p class="text-sm lg:text-lg text-gray-800 font-medium truncate">
                            <?php echo $user_info ? htmlspecialchars($user_info['school_name']) : '-'; ?>
                        </p>
                        <p class="text-xs lg:text-sm text-gray-600">
                            <?php echo $user_info ? $user_info['grade'] . '학년' : '-'; ?>
                        </p>
                    </div> 
                    <!-- 시험 종류 -->
                    <div class="bg-white rounded-xl p-4 lg:p-6 shadow-lg">
                        <p class="text-xs lg:text-sm text-gray-600 mb-2">시험 정보</p>
                        <p class="text-sm lg:text-lg text-gray-800 font-medium truncate">
                            <?php echo $exam_info ? htmlspecialchars($exam_info['exam_type']) : '-'; ?>
                        </p>
                        <p class="text-xs lg:text-sm text-gray-600">
                            <?php if ($exam_dates): ?>
                                <?php echo $exam_dates['start_date']; ?> ~ <?php echo $exam_dates['end_date']; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </p>
                    </div>
                    <!-- 오늘 학습 -->
                    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl p-4 lg:p-6 text-white shadow-lg">
                        <p class="text-xs lg:text-sm mb-2 opacity-90">오늘 학습</p>
                        <p class="text-sm lg:text-lg font-medium">0시간 0분</p>
                        <p class="text-xs lg:text-sm opacity-90">0개 완료</p>
                    </div>
                </div>

                <!-- 메인 콘텐츠 -->
                <div class="space-y-4 lg:space-y-6">
                    <!-- 시험 정보 섹션 -->
                    <div class="bg-white rounded-xl p-4 lg:p-6 shadow-lg border border-gray-100">
                        <h2 class="text-lg lg:text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-xl lg:text-2xl">📝</span> 
                            <span>시험 정보</span>
                        </h2>
                        <div class="space-y-4 lg:grid lg:grid-cols-2 lg:gap-6 lg:space-y-0">
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-2 font-medium">📚 시험 범위</p>
                                <p class="text-gray-800 text-sm lg:text-base leading-relaxed">
                                    <?php echo $exam_scope ?: '범위 미입력'; ?>
                                </p>
                            </div>
                            <div class="bg-gray-50 rounded-lg p-4">
                                <p class="text-sm text-gray-600 mb-2 font-medium">📅 시험 기간</p>
                                <p class="text-gray-800 text-sm lg:text-base font-medium">
                                    <?php if ($exam_dates): ?>
                                        <?php echo $exam_dates['start_date']; ?> ~ <?php echo $exam_dates['end_date']; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 상세 통계 (Additional - 조건부 표시) -->
                    <div class="bg-white rounded-xl p-6 shadow-md hidden-content-additional" <?php echo !$designConfig['showAdditional'] ? 'style="display:none;"' : ''; ?>>
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <span>📈</span> 상세 통계
                        </h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="text-center p-4 bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl">
                                <span class="text-2xl">⏰</span>
                                <p class="text-2xl font-bold text-gray-800 mt-2">42.5h</p>
                                <p class="text-sm text-gray-600">총 학습시간</p>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl">
                                <span class="text-2xl">📖</span>
                                <p class="text-2xl font-bold text-gray-800 mt-2">856</p>
                                <p class="text-sm text-gray-600">푼 문제</p>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl">
                                <span class="text-2xl">🧠</span>
                                <p class="text-2xl font-bold text-gray-800 mt-2">78%</p>
                                <p class="text-sm text-gray-600">정답률</p>
                            </div>
                            <div class="text-center p-4 bg-gradient-to-br from-yellow-50 to-orange-50 rounded-xl">
                                <span class="text-2xl">🏆</span>
                                <p class="text-2xl font-bold text-gray-800 mt-2">12</p>
                                <p class="text-sm text-gray-600">획득 뱃지</p>
                            </div>
                        </div>
                    </div>

                    <!-- 오늘의 목표 -->
                    <div class="bg-white rounded-xl p-6 shadow-md">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <span>🎯</span> 목표 관리
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-300">
                                <p class="text-sm text-yellow-700 mb-2">💪 오늘의 목표</p>
                                <p class="text-gray-800 font-medium" data-goal="today">
                                    <?php 
                                    if ($todayGoalData && !empty($todayGoalData['text'])) {
                                        echo htmlspecialchars($todayGoalData['text']);
                                    } else {
                                        switch($phase) {
                                            case 'prepare': echo '기초 개념 완성하기'; break;
                                            case 'intensive': echo '약점 집중 보완하기'; break;
                                            case 'finish': echo '핵심 내용 정리하기'; break;
                                            default: echo '오늘의 목표를 설정해주세요';
                                        }
                                    }
                                    ?>
                                </p>
                            </div>
                            <div class="bg-green-50 rounded-lg p-4 border border-green-300">
                                <p class="text-sm text-green-700 mb-2">📅 주간 목표</p>
                                <p class="text-gray-800 font-medium">
                                    <?php echo $weeklyGoalData && !empty($weeklyGoalData['text']) ? htmlspecialchars($weeklyGoalData['text']) : '전 단원 1회독 완료'; ?>
                                </p>
                            </div>
                            <div class="bg-purple-50 rounded-lg p-4 border border-purple-300">
                                <p class="text-sm text-purple-700 mb-2">🎯 분기 목표</p>
                                <p class="text-gray-800 font-medium">
                                    <?php echo $quarterlyGoalData && !empty($quarterlyGoalData['text']) ? htmlspecialchars($quarterlyGoalData['text']) : '목표 등급 달성'; ?>
                                </p>
                            </div>
                        </div>
                    </div>


                    <!-- 빠른 실행 버튼들 -->
                    <div class="bg-white rounded-xl p-6 shadow-md">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center gap-2">
                            <span>⚡</span> 빠른 실행
                        </h2>
                        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- 필수 버튼들 (항상 표시) -->
                            <button class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg">
                                <span class="text-2xl">⚡</span>
                                <span class="font-medium text-sm">학습 시작</span>
                            </button>
                            <button onclick="showExamInfo()" class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg">
                                <span class="text-2xl">📄</span>
                                <span class="font-medium text-sm">시험 정보</span>
                            </button>
                            
                            <!-- 조건부 버튼들 -->
                            <button onclick="openAIChat()" class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg hidden-content-important" <?php echo !$designConfig['showImportant'] ? 'style="display:none;"' : ''; ?>>
                                <span class="text-2xl">🤖</span>
                                <span class="font-medium text-sm">AI 튜터</span>
                            </button>
                            
                            <button onclick="showUpload()" class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-xl p-4 transition-all flex flex-col items-center gap-2 shadow-lg hidden-content-additional" <?php echo !$designConfig['showAdditional'] ? 'style="display:none;"' : ''; ?>>
                                <span class="text-2xl">📤</span>
                                <span class="font-medium text-sm">자료 업로드</span>
                            </button>
                        </div>
                    </div>

                </div>
                
                <!-- 전체 보기 버튼 (D-7 이하에서만 표시) -->
                <?php if($dday !== null && $dday <= 7 && (!$designConfig['showImportant'] || !$designConfig['showAdditional'])): ?>
                <div class="text-center mt-6">
                    <button id="toggle-all-content" onclick="toggleAllContent()" class="px-6 py-3 bg-white/90 hover:bg-white rounded-xl font-medium text-gray-700 shadow-md hover:shadow-lg transition-all duration-300 inline-flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        <span>전체 내용 보기</span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 탭 모드 컨테이너 -->
        <div id="tab-mode-container" class="hidden px-3 md:px-4 lg:px-6 pb-20">
            <!-- 모드 설명 -->
            <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-lg p-3 lg:p-4 mb-4 border border-purple-200">
                <div class="flex items-start gap-2 lg:gap-3">
                    <span class="text-lg lg:text-2xl">🎯</span>
                    <div>
                        <h3 class="font-bold text-purple-800 mb-1 text-sm lg:text-base">탭 모드: 집중적 학습 관리</h3>
                        <p class="text-xs lg:text-sm text-purple-700 leading-relaxed">
                            필요한 정보만 선택적으로 확인하여 집중력 극대화 • 현황-진행도-목표-통계를 체계적으로 분리 • 각 영역에 깊이 있게 집중
                        </p>
                    </div>
                </div>
            </div>

            <!-- 탭 네비게이션 -->
            <div class="flex gap-2 mb-6 border-b border-gray-300">
                <button onclick="selectTab('overview')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-800 active">현황</button>
                <button onclick="selectTab('goals')" class="tab-btn px-4 py-2 text-sm font-medium text-gray-600">목표</button>
            </div>

            <!-- 탭 콘텐츠 -->
            <div id="tab-content">
                <!-- 현황 탭 -->
                <div id="tab-overview" class="tab-panel active">
                    <div class="flex flex-col lg:grid lg:grid-cols-12 gap-4 lg:gap-6">
                        <!-- 왼쪽: 핵심 정보 -->
                        <div class="lg:col-span-8 space-y-4 lg:space-y-6">
                            <!-- 시험 정보 카드 -->
                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-md">
                                <h3 class="text-base lg:text-lg font-medium text-gray-800 mb-3 lg:mb-4">시험 정보</h3>
                                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 lg:gap-4">
                                    <div>
                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">학교</p>
                                        <p class="text-sm lg:text-base text-gray-800 font-medium truncate">
                                            <?php echo $user_info ? htmlspecialchars($user_info['school_name']) : '-'; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">학년</p>
                                        <p class="text-sm lg:text-base text-gray-800 font-medium">
                                            <?php echo $user_info ? $user_info['grade'] . '학년' : '-'; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">시험</p>
                                        <p class="text-sm lg:text-base text-gray-800 font-medium truncate">
                                            <?php echo $exam_info ? htmlspecialchars($exam_info['exam_type']) : '-'; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">기간</p>
                                        <p class="text-xs lg:text-sm text-gray-800">
                                            <?php if ($exam_dates): ?>
                                                <?php echo $exam_dates['start_date']; ?> ~ <?php echo $exam_dates['end_date']; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">D-Day</p>
                                        <p class="text-red-600 text-base lg:text-lg font-bold">
                                            <?php if ($dday !== null): ?>
                                                D<?php echo $dday >= 0 ? '-' : '+'; ?><?php echo abs($dday); ?>
                                            <?php else: ?>
                                                D-?
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-xs lg:text-sm text-gray-600 mb-1">상태</p>
                                        <p>
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs font-medium">
                                                <?php echo ($exam_dates && $exam_dates['status'] === '확정') ? '확정' : '예상'; ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-3 lg:mt-4 pt-3 lg:pt-4 border-t border-gray-200">
                                    <p class="text-xs lg:text-sm text-gray-600 mb-2">시험 범위</p>
                                    <p class="text-sm lg:text-base text-gray-800">
                                        <?php echo $exam_scope ?: '범위 미입력'; ?>
                                    </p>
                                </div>
                            </div>

                            <!-- 오늘의 학습 -->
                            <div class="bg-white rounded-xl p-4 lg:p-6 shadow-md">
                                <h3 class="text-base lg:text-lg font-medium text-gray-800 mb-3 lg:mb-4">오늘의 학습</h3>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 lg:gap-4 text-center">
                                    <div>
                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">0시간</p>
                                        <p class="text-xs text-gray-600 mt-1">학습 시간</p>
                                    </div>
                                    <div>
                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">0개</p>
                                        <p class="text-xs text-gray-600 mt-1">완료 활동</p>
                                    </div>
                                    <div>
                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">0%</p>
                                        <p class="text-xs text-gray-600 mt-1">정답률</p>
                                    </div>
                                    <div>
                                        <p class="text-lg lg:text-2xl font-bold text-gray-800">⭐⭐⭐</p>
                                        <p class="text-xs text-gray-600 mt-1">만족도</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 오른쪽: 빠른 액션 -->
                        <div class="lg:col-span-4 space-y-3">
                            <!-- 주요 액션 -->
                            <button class="w-full <?php echo $designConfig['buttonColor']; ?> text-white rounded-lg p-3 lg:p-4 transition-all flex items-center justify-center gap-2 lg:gap-3 shadow-md">
                                <span class="text-lg lg:text-2xl">⚡</span>
                                <span class="font-medium text-sm lg:text-base">학습 시작하기</span>
                            </button>

                            <!-- 서브 액션들 -->
                            <div class="grid grid-cols-3 gap-2">
                                <button onclick="showExamInfo()" class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex items-center justify-center gap-1 shadow-sm">
                                    <span class="text-sm">📄</span>
                                    <span>시험 정보</span>
                                </button>
                                <button onclick="openAIChat()" class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex items-center justify-center gap-1 shadow-sm hidden-content-important" <?php echo !$designConfig['showImportant'] ? 'style="display:none;"' : ''; ?>>
                                    <span class="text-sm">🤖</span>
                                    <span>AI 튜터</span>
                                </button>
                                <button onclick="showUpload()" class="<?php echo $designConfig['buttonColor']; ?> text-white rounded-md p-2 lg:p-3 text-xs font-medium transition-all flex items-center justify-center gap-1 shadow-sm hidden-content-additional" <?php echo !$designConfig['showAdditional'] ? 'style="display:none;"' : ''; ?>>
                                    <span class="text-sm">📤</span>
                                    <span>자료 업로드</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 목표 탭 -->
                <div id="tab-goals" class="tab-panel">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-yellow-50 rounded-xl p-6 border-2 border-yellow-300 shadow-md">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-medium text-yellow-800">오늘의 목표</h3>
                                <span class="text-2xl">💪</span>
                            </div>
                            <p class="text-gray-800 font-medium mb-2" data-goal="today">
                                <?php 
                                if ($todayGoalData && !empty($todayGoalData['text'])) {
                                    echo htmlspecialchars($todayGoalData['text']);
                                } else {
                                    switch($phase) {
                                        case 'prepare': echo '기초 개념 완성하기'; break;
                                        case 'intensive': echo '약점 집중 보완하기'; break;
                                        case 'finish': echo '핵심 내용 정리하기'; break;
                                        default: echo '오늘의 목표를 설정해주세요';
                                    }
                                }
                                ?>
                            </p>
                            <p class="text-sm text-gray-600">진행률: 30%</p>
                            <div class="w-full bg-yellow-200 rounded-full h-2 mt-2">
                                <div class="h-2 rounded-full bg-yellow-500" style="width: 30%"></div>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-xl p-6 border-2 border-green-300 shadow-md">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-medium text-green-800">주간 목표</h3>
                                <span class="text-2xl">📅</span>
                            </div>
                            <p class="text-gray-800 font-medium mb-2">
                                <?php echo $weeklyGoalData && !empty($weeklyGoalData['text']) ? htmlspecialchars($weeklyGoalData['text']) : '전 단원 1회독 완료'; ?>
                            </p>
                            <p class="text-sm text-gray-600">진행률: 60%</p>
                            <div class="w-full bg-green-200 rounded-full h-2 mt-2">
                                <div class="h-2 rounded-full bg-green-500" style="width: 60%"></div>
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-xl p-6 border-2 border-purple-300 shadow-md">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-medium text-purple-800">분기 목표</h3>
                                <span class="text-2xl">🎯</span>
                            </div>
                            <p class="text-gray-800 font-medium mb-2">
                                <?php echo $quarterlyGoalData && !empty($quarterlyGoalData['text']) ? htmlspecialchars($quarterlyGoalData['text']) : '목표 등급 달성'; ?>
                            </p>
                            <p class="text-sm text-gray-600">진행률: 45%</p>
                            <div class="w-full bg-purple-200 rounded-full h-2 mt-2">
                                <div class="h-2 rounded-full bg-purple-500" style="width: 45%"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <!-- 시험 정보 팝업 -->
    <div id="exam-info-popup" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 p-6 text-white">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold">📋 시험 정보</h2>
                    <button onclick="closeExamInfo()" class="text-white hover:text-gray-200 transition-all">
                        <span class="text-2xl">✕</span>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4" id="exam-info-main">
                    <button onclick="showExamResources()" class="p-6 bg-blue-50 hover:bg-blue-100 rounded-xl transition-all group">
                        <div class="text-4xl mb-3">📁</div>
                        <h3 class="text-lg font-semibold text-gray-800">시험 자료 보기</h3>
                        <p class="text-sm text-gray-600 mt-2">업로드된 파일 및 링크 확인</p>
                    </button>
                    <button onclick="showExamTips()" class="p-6 bg-green-50 hover:bg-green-100 rounded-xl transition-all group">
                        <div class="text-4xl mb-3">💡</div>
                        <h3 class="text-lg font-semibold text-gray-800">시험 정보 보기</h3>
                        <p class="text-sm text-gray-600 mt-2">팁과 조언 확인</p>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- AI 튜터 채팅 모달 -->
    <div id="ai-chat-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full h-[80vh] flex flex-col overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-pink-600 p-4 text-white">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <span class="text-2xl">🤖</span> AI 튜터
                    </h2>
                    <button onclick="closeAIChat()" class="text-white hover:text-gray-200 text-xl">✕</button>
                </div>
            </div>
            <div id="ai-chat-messages" class="flex-1 p-4 overflow-y-auto bg-gray-50">
                <!-- 채팅 메시지가 여기에 추가됩니다 -->
            </div>
            <div class="p-4 bg-white border-t">
                <div class="flex gap-2">
                    <input type="text" id="ai-chat-input" placeholder="질문을 입력하세요..." 
                           class="flex-1 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                           onkeypress="if(event.key==='Enter') sendAIMessage()">
                    <button id="ai-send-btn" onclick="sendAIMessage()" 
                            class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition-all">
                        <span>전송</span> <span>🚀</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 자료 업로드 모달 -->
    <div id="upload-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-3xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6">
                <div class="flex justify-between items-center">
                    <h2 class="text-2xl font-bold flex items-center gap-3">
                        <span class="text-3xl">📤</span> 시험 정보 업로드
                    </h2>
                    <button onclick="closeUploadModal()" class="text-white hover:text-gray-200 text-2xl">✕</button>
                </div>
            </div>
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="text-center py-8">
                    <div class="text-4xl mb-4">📤</div>
                    <h3 class="text-lg font-semibold mb-2">자료 업로드</h3>
                    <p class="text-gray-600">시험에 도움이 될 자료를 업로드하세요.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 알림 모달 -->
    <div id="notifications-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full max-h-[80vh] overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-cyan-600 p-6 text-white">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-bold flex items-center gap-2">
                        <span class="text-2xl">🔔</span> 알림
                    </h2>
                    <button onclick="closeNotifications()" class="text-white hover:text-gray-200 text-xl">✕</button>
                </div>
            </div>
            <div class="p-6">
                <div class="text-center py-8">
                    <div class="text-4xl mb-4">📝</div>
                    <p class="text-gray-600">새로운 알림이 없습니다.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // 전역 변수
        let aiChatConversation = [];
        let isAIProcessing = false;
        let showAllContent = false;
        
        // 전체 콘텐츠 토글
        function toggleAllContent() {
            showAllContent = !showAllContent;
            const importantElements = document.querySelectorAll('.hidden-content-important');
            const additionalElements = document.querySelectorAll('.hidden-content-additional');
            const toggleBtn = document.getElementById('toggle-all-content');
            
            if (showAllContent) {
                // 모든 숨겨진 콘텐츠 표시
                importantElements.forEach(el => {
                    el.style.display = '';
                    if (el.classList.contains('bg-white')) {
                        el.style.display = 'block';
                    } else if (el.tagName === 'BUTTON') {
                        el.style.display = 'flex';
                    }
                });
                additionalElements.forEach(el => {
                    el.style.display = '';
                    if (el.classList.contains('bg-white')) {
                        el.style.display = 'block';
                    } else if (el.tagName === 'BUTTON') {
                        el.style.display = 'flex';
                    }
                });
                
                // 버튼 그리드 레이아웃 업데이트
                const buttonGrid = document.querySelector('.grid.grid-cols-2.lg\\:grid-cols-4');
                if (buttonGrid) {
                    buttonGrid.classList.remove('grid-cols-2');
                    buttonGrid.classList.add('grid-cols-2', 'lg:grid-cols-4');
                }
                
                toggleBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                    </svg>
                    <span>필수만 보기</span>
                `;
            } else {
                // 원래 설정대로 숨기기
                importantElements.forEach(el => el.style.display = 'none');
                additionalElements.forEach(el => el.style.display = 'none');
                
                toggleBtn.innerHTML = `
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    <span>전체 내용 보기</span>
                `;
            }
        }
        
        // 현재 시간 표시
        function updateCurrentTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('ko-KR', { 
                hour: '2-digit', 
                minute: '2-digit'
            });
            document.getElementById('current-time').textContent = timeString;
        }

        // 대시보드 모드 전환
        function setDashboardMode(mode) {
            const scrollBtn = document.getElementById('scroll-mode-btn');
            const tabBtn = document.getElementById('tab-mode-btn');
            const scrollContainer = document.getElementById('scroll-mode-container');
            const tabContainer = document.getElementById('tab-mode-container');

            if (mode === 'scroll') {
                // 스크롤 모드 활성화
                scrollBtn.classList.add('bg-white', 'text-gray-800', 'shadow-sm');
                scrollBtn.classList.remove('text-gray-600');
                tabBtn.classList.remove('bg-white', 'text-gray-800', 'shadow-sm');
                tabBtn.classList.add('text-gray-600');
                
                scrollContainer.classList.remove('hidden');
                tabContainer.classList.add('hidden');
            } else {
                // 탭 모드 활성화
                tabBtn.classList.add('bg-white', 'text-gray-800', 'shadow-sm');
                tabBtn.classList.remove('text-gray-600');
                scrollBtn.classList.remove('bg-white', 'text-gray-800', 'shadow-sm');
                scrollBtn.classList.add('text-gray-600');
                
                tabContainer.classList.remove('hidden');
                scrollContainer.classList.add('hidden');
            }
        }

        // 탭 선택
        function selectTab(tabName) {
            // 모든 탭 버튼과 패널 비활성화
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active', 'text-gray-800', 'border-purple-500');
                btn.classList.add('text-gray-600');
            });
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.remove('active');
            });

            // 선택한 탭 활성화
            const activeBtn = document.querySelector(`[onclick="selectTab('${tabName}')"]`);
            const activePanel = document.getElementById(`tab-${tabName}`);
            
            if (activeBtn) {
                activeBtn.classList.add('active', 'text-gray-800');
                activeBtn.classList.remove('text-gray-600');
            }
            if (activePanel) {
                activePanel.classList.add('active');
            }
        }

        // 대시보드 정보 업데이트
        function updateDashboardInfo() {
            console.log('=== updateDashboardInfo 호출됨 ===');
            
            // D-Day 업데이트
            <?php if ($dday !== null): ?>
            const ddayElements = document.querySelectorAll('[id*="dday"]');
            ddayElements.forEach(el => {
                el.textContent = 'D<?php echo $dday >= 0 ? '-' : '+'; ?><?php echo abs($dday); ?>';
            });
            <?php endif; ?>
            
            // 시험 정보 업데이트
            const schoolElements = document.querySelectorAll('[id*="school"]');
            schoolElements.forEach(el => {
                el.textContent = '<?php echo $user_info ? htmlspecialchars($user_info['school_name']) : '-'; ?>';
            });
            
            // 시험 범위 업데이트
            const scopeElements = document.querySelectorAll('[id*="scope"]');
            scopeElements.forEach(el => {
                el.textContent = '<?php echo $exam_scope ? htmlspecialchars($exam_scope) : '범위 미입력'; ?>';
            });
            
            // 학습 진행률 업데이트 (임시 데이터)
            const progressBars = document.querySelectorAll('.progress-bar');
            progressBars.forEach(bar => {
                bar.style.width = '<?php echo $progress; ?>%';
            });
            
            console.log('대시보드 정보 업데이트 완료');
        }


        // 목표 관리 업데이트
        function displayDashboardGoals() {
            const phase = '<?php echo $phase; ?>';
            const goals = {
                'prepare': '기초 개념 완성하기',
                'intensive': '약점 집중 보완하기', 
                'finish': '핵심 내용 정리하기'
            };
            
            const goalElements = document.querySelectorAll('[data-goal="today"]');
            goalElements.forEach(el => {
                el.textContent = goals[phase] || '학습 목표 설정';
            });
        }

        // 실시간 데이터 업데이트
        function updateRealTimeData() {
            // Ajax로 최신 데이터 가져오기
            fetch(`get_dashboard_data.php?userid=<?php echo $userid; ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // D-Day, 진행률 등 업데이트
                        console.log('Dashboard updated:', data);
                    }
                })
                .catch(error => {
                    console.error('Dashboard update error:', error);
                });
        }

        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            updateCurrentTime();
            setInterval(updateCurrentTime, 1000);
            
            // 기본 모드는 스크롤 모드
            setDashboardMode('scroll');
            
            // 대시보드 정보 초기화
            updateDashboardInfo();
            displayDashboardGoals();
            
            // 실시간 업데이트 (5분마다)
            setInterval(updateRealTimeData, 300000);
        });

        // 시험 정보 팝업 관련 함수들
        function showExamInfo() {
            document.getElementById('exam-info-popup').classList.remove('hidden');
        }

        function closeExamInfo() {
            document.getElementById('exam-info-popup').classList.add('hidden');
        }

        function showExamResources() {
            // 자료 목록을 표시하는 로직
            alert('시험 자료를 불러오는 중...');
        }

        function showExamTips() {
            // 시험 팁을 표시하는 로직
            alert('시험 정보를 불러오는 중...');
        }

        // AI 튜터 채팅 관련 함수들
        function openAIChat() {
            document.getElementById('ai-chat-modal').classList.remove('hidden');
            
            // 채팅 초기화 (처음 열 때만)
            if (aiChatConversation.length === 0) {
                initializeAIChat();
            }
        }

        function closeAIChat() {
            document.getElementById('ai-chat-modal').classList.add('hidden');
        }

        function initializeAIChat() {
            const chatMessages = document.getElementById('ai-chat-messages');
            chatMessages.innerHTML = '';
            
            // 환영 메시지 추가
            const welcomeMessage = '안녕하세요! 저는 여러분의 시험 공부를 도와줄 AI 튜터예요. 📚\n\n' +
                '업로드된 시험 자료와 팁을 분석했어요. 무엇이든 물어보세요!\n\n' +
                '예를 들어:\n' +
                '• "시험 전날 어떻게 준비해야 할까요?"\n' +
                '• "공식 외우는 좋은 방법이 있나요?"\n' +
                '• "실수를 줄이는 방법을 알려주세요"';
            addAIChatMessage(welcomeMessage, 'ai');
        }

        async function sendAIMessage() {
            if (isAIProcessing) return;
            
            const input = document.getElementById('ai-chat-input');
            const message = input.value.trim();
            
            if (!message) return;
            
            // 처리 중 플래그 설정
            isAIProcessing = true;
            
            // 사용자 메시지 추가
            addAIChatMessage(message, 'user');
            aiChatConversation.push({ role: 'user', content: message });
            
            // 입력창 초기화
            input.value = '';
            
            // 버튼 비활성화 및 로딩 표시
            const sendBtn = document.getElementById('ai-send-btn');
            const originalBtnContent = sendBtn.innerHTML;
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span>생각중...</span> <span>⏳</span>';
            
            try {
                // AI API 호출
                const response = await fetch('ai_tutor_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        message: message,
                        userid: <?php echo $userid; ?>,
                        conversation: aiChatConversation.slice(-10) // 최근 10개 대화만 전송
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // AI 응답 추가
                    addAIChatMessage(data.message, 'ai');
                    aiChatConversation.push({ role: 'assistant', content: data.message });
                } else {
                    throw new Error(data.error || 'AI 응답 오류');
                }
            } catch (error) {
                console.error('AI API Error:', error);
                addAIChatMessage('죄송해요. 잠시 응답에 실패했어요. 다시 시도해주세요. 😅', 'ai');
            } finally {
                // 버튼 복구
                sendBtn.disabled = false;
                sendBtn.innerHTML = originalBtnContent;
                isAIProcessing = false;
            }
        }


        function addAIChatMessage(message, sender) {
            const chatMessages = document.getElementById('ai-chat-messages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chat-message ${sender} mb-4`;
            
            const currentTime = new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' });
            
            if (sender === 'user') {
                messageDiv.innerHTML = `
                    <div class="flex items-start gap-3 justify-end">
                        <div class="flex-1 text-right">
                            <div class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-2xl rounded-tr-none p-4 shadow-sm max-w-lg">
                                <p class="text-white">${escapeHtml(message)}</p>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">${currentTime}</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-700 font-bold">
                            나
                        </div>
                    </div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-gradient-to-r from-purple-500 to-pink-500 flex items-center justify-center text-white font-bold">
                            AI
                        </div>
                        <div class="flex-1">
                            <div class="bg-white rounded-2xl rounded-tl-none p-4 shadow-sm max-w-lg">
                                <div class="text-gray-800" style="white-space: pre-wrap;">${escapeHtml(message)}</div>
                            </div>
                            <p class="text-xs text-gray-400 mt-1">AI 튜터 · ${currentTime}</p>
                        </div>
                    </div>
                `;
            }
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTo({
                top: chatMessages.scrollHeight,
                behavior: 'smooth'
            });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // 자료 업로드 모달 관련 함수들
        function showUpload() {
            document.getElementById('upload-modal').classList.remove('hidden');
        }

        function closeUploadModal() {
            document.getElementById('upload-modal').classList.add('hidden');
        }

        // 알림 관련 함수들
        function showNotifications() {
            document.getElementById('notifications-modal').classList.remove('hidden');
        }

        function closeNotifications() {
            document.getElementById('notifications-modal').classList.add('hidden');
        }
    </script>
</body>
</html>