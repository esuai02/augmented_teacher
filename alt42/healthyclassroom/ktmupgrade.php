<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
$studentid = $_GET["userid"] ?? null;

$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole->data;

// KTM 시스템 전체 통계 (PHP에서 데이터 준비)
$ktmStats = [
    'totalStudents' => 156,
    'totalTeachers' => 12,
    'avgProgress' => 74.5,
    'avgMetaCognition' => 78.2,
    'avgParentEngagement' => 82.1,
    'systemHealth' => 85.3,
    'atRiskStudents' => 12,
    'excellentStudents' => 38,
    'lastUpdated' => '2025.01.05 15:30'
];

// KTM 성과 지표
$ktmMetrics = [
    'learningEfficiency' => 87,
    'teachingQuality' => 92,
    'parentSatisfaction' => 88,
    'goalAchievementRate' => 79,
    'systemReliability' => 95
];

// 관심 학생 데이터
$attentionStudents = [
    ['id' => 1, 'name' => '김민준', 'grade' => '중2', 'ktmScore' => 45, 'issue' => '학습 진행률 급감', 'severity' => 'critical', 'progress' => 45, 'change' => -25],
    ['id' => 2, 'name' => '이서연', 'grade' => '중3', 'ktmScore' => 52, 'issue' => '메타인지 저하', 'severity' => 'warning', 'metaCognition' => 52, 'change' => -15],
    ['id' => 3, 'name' => '박지호', 'grade' => '중1', 'ktmScore' => 40, 'issue' => '부모 참여도 감소', 'severity' => 'warning', 'parentEngagement' => 40, 'change' => -30],
    ['id' => 4, 'name' => '최유나', 'grade' => '중2', 'ktmScore' => 35, 'issue' => '목표 달성 실패', 'severity' => 'critical', 'goalAchievement' => 35, 'change' => -20]
];

// KTM 진단 모듈
$ktmModules = [
    [
        'id' => 'cognitive',
        'title' => '학습인지 진단',
        'icon' => 'brain',
        'status' => '정상',
        'statusColor' => 'text-green-600',
        'ktmIndex' => 85,
        'details' => '인지 패턴 분석 정상',
        'risk' => 8,
        'trend' => 'up'
    ],
    [
        'id' => 'metacognitive',
        'title' => '메타인지 진단',
        'icon' => 'bar-chart-3',
        'status' => '주의',
        'statusColor' => 'text-yellow-600',
        'ktmIndex' => 72,
        'details' => '자기평가 정확도 하락',
        'risk' => 15,
        'trend' => 'down'
    ],
    [
        'id' => 'consultation',
        'title' => '상담 자동화',
        'icon' => 'message-square',
        'status' => '우수',
        'statusColor' => 'text-blue-600',
        'ktmIndex' => 92,
        'details' => 'AI 상담 매칭률 92%',
        'risk' => 3,
        'trend' => 'up'
    ],
    [
        'id' => 'mindreading',
        'title' => '심리상태 분석',
        'icon' => 'eye',
        'status' => '경고',
        'statusColor' => 'text-orange-600',
        'ktmIndex' => 68,
        'details' => '동기부여 지수 하락',
        'risk' => 22,
        'trend' => 'down'
    ],
    [
        'id' => 'parent',
        'title' => '학부모 소통',
        'icon' => 'home',
        'status' => '정상',
        'statusColor' => 'text-green-600',
        'ktmIndex' => 88,
        'details' => '참여도 상위 15%',
        'risk' => 5,
        'trend' => 'stable'
    ],
    [
        'id' => 'curriculum',
        'title' => '커리큘럼 최적화',
        'icon' => 'book-open',
        'status' => '우수',
        'statusColor' => 'text-blue-600',
        'ktmIndex' => 91,
        'details' => '진도 일치율 91%',
        'risk' => 7,
        'trend' => 'up'
    ],
    [
        'id' => 'goals',
        'title' => '목표 달성도',
        'icon' => 'target',
        'status' => '위험',
        'statusColor' => 'text-red-600',
        'ktmIndex' => 58,
        'details' => '목표 이탈률 증가',
        'risk' => 28,
        'trend' => 'down'
    ],
    [
        'id' => 'pomodoro',
        'title' => '학습 집중도',
        'icon' => 'clock',
        'status' => '정상',
        'statusColor' => 'text-green-600',
        'ktmIndex' => 83,
        'details' => '평균 집중시간 25분',
        'risk' => 6,
        'trend' => 'stable'
    ]
];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KTM 통합 진단 시스템</title>
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Custom CSS -->
    <link href="css/ktmupgrade.css" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
</head>
<body>
    <div class="min-h-screen bg-gray-50">
        <!-- KTM 헤더 -->
        <header class="calm-header text-white shadow-sm">
            <div class="px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="bg-white/10 p-2 rounded-lg mr-4">
                            <i data-lucide="shield" class="text-white w-7 h-7"></i>
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold flex items-center">
                                KTM 통합 진단 시스템
                                <span class="ml-2 text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded-full">v2.5</span>
                            </h1>
                            <p class="text-sm text-blue-100 mt-1">
                                Knowledge Transfer Management System | 마지막 업데이트: <?php echo $ktmStats['lastUpdated']; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="text-right mr-4">
                            <div class="text-sm text-blue-100">시스템 상태</div>
                            <div class="text-lg font-bold flex items-center">
                                <i data-lucide="zap" class="mr-1 w-4 h-4"></i>
                                <?php echo $ktmStats['systemHealth']; ?>% 정상
                            </div>
                        </div>
                        <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors font-medium">
                            KTM 리포트 생성
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- KTM 핵심 지표 -->
        <div class="bg-white border-b">
            <div class="px-6 py-4">
                <div class="grid grid-cols-5 gap-4">
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-700"><?php echo $ktmMetrics['learningEfficiency']; ?>%</div>
                        <div class="text-sm text-gray-600">학습 효율성</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-700"><?php echo $ktmMetrics['teachingQuality']; ?>%</div>
                        <div class="text-sm text-gray-600">교육 품질</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-700"><?php echo $ktmMetrics['parentSatisfaction']; ?>%</div>
                        <div class="text-sm text-gray-600">학부모 만족도</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-700"><?php echo $ktmMetrics['goalAchievementRate']; ?>%</div>
                        <div class="text-sm text-gray-600">목표 달성률</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl font-bold text-gray-700"><?php echo $ktmMetrics['systemReliability']; ?>%</div>
                        <div class="text-sm text-gray-600">시스템 신뢰도</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 메인 대시보드 -->
        <div class="p-6">
            <!-- KTM 현황 요약 카드 -->
            <div class="grid grid-cols-6 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 text-sm">전체 학생</span>
                        <i data-lucide="users" class="text-gray-600 w-5 h-5"></i>
                    </div>
                    <div class="text-2xl font-bold"><?php echo $ktmStats['totalStudents']; ?>명</div>
                    <p class="text-xs text-gray-500 mt-1">교사 <?php echo $ktmStats['totalTeachers']; ?>명</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 text-sm">평균 진행률</span>
                        <i data-lucide="trending-up" class="text-gray-600 w-5 h-5"></i>
                    </div>
                    <div class="text-2xl font-bold"><?php echo $ktmStats['avgProgress']; ?>%</div>
                    <p class="text-xs text-gray-600 mt-1">+2.3% ↑</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 text-sm">메타인지</span>
                        <i data-lucide="brain" class="text-gray-600 w-5 h-5"></i>
                    </div>
                    <div class="text-2xl font-bold"><?php echo $ktmStats['avgMetaCognition']; ?>%</div>
                    <p class="text-xs text-gray-600 mt-1">안정적</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 text-sm">부모 참여</span>
                        <i data-lucide="home" class="text-gray-600 w-5 h-5"></i>
                    </div>
                    <div class="text-2xl font-bold"><?php echo $ktmStats['avgParentEngagement']; ?>%</div>
                    <p class="text-xs text-gray-600 mt-1">+5.1% ↑</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 text-sm">위험군</span>
                        <i data-lucide="alert-triangle" class="text-gray-700 w-5 h-5"></i>
                    </div>
                    <div class="text-2xl font-bold text-gray-700"><?php echo $ktmStats['atRiskStudents']; ?>명</div>
                    <p class="text-xs text-gray-700 mt-1">즉시 개입 필요</p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-gray-400">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600 text-sm">우수군</span>
                        <i data-lucide="award" class="text-gray-600 w-5 h-5"></i>
                    </div>
                    <div class="text-2xl font-bold text-gray-700"><?php echo $ktmStats['excellentStudents']; ?>명</div>
                    <p class="text-xs text-gray-600 mt-1">상위 24%</p>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <!-- KTM 진단 모듈 그리드 -->
                <div class="col-span-2 space-y-6">
                    <!-- KTM 통합 진단 현황 -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold flex items-center">
                                <i data-lucide="shield" class="mr-2 text-gray-600 w-5 h-5"></i>
                                KTM 진단 모듈 현황
                            </h3>
                            <div class="flex space-x-2">
                                <button data-filter="all" class="filter-btn px-3 py-1 text-sm rounded-lg bg-gray-700 text-white">전체</button>
                                <button data-filter="warning" class="filter-btn px-3 py-1 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">주의</button>
                                <button data-filter="critical" class="filter-btn px-3 py-1 text-sm rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200">위험</button>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4" id="ktmModulesGrid">
                            <?php foreach ($ktmModules as $module): ?>
                            <div class="module-card border rounded-lg p-4 hover:shadow-md transition-all cursor-pointer relative overflow-hidden" 
                                 data-module-id="<?php echo $module['id']; ?>"
                                 data-status="<?php echo $module['status']; ?>">
                                <!-- KTM 지수 배경 -->
                                <div class="absolute top-0 right-0 opacity-10">
                                    <div class="text-6xl font-bold <?php echo getKTMScoreColor($module['ktmIndex']); ?>">
                                        <?php echo $module['ktmIndex']; ?>
                                    </div>
                                </div>
                                
                                <div class="relative z-10">
                                    <div class="flex items-start justify-between mb-2">
                                        <div class="flex items-center">
                                            <i data-lucide="<?php echo $module['icon']; ?>" class="mr-2 text-gray-600 w-5 h-5"></i>
                                            <h4 class="font-medium"><?php echo $module['title']; ?></h4>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm font-medium <?php echo $module['statusColor']; ?>">
                                                <?php echo $module['status']; ?>
                                            </span>
                                            <?php echo getTrendIcon($module['trend']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <div class="flex items-baseline space-x-2">
                                            <span class="text-2xl font-bold <?php echo getKTMScoreColor($module['ktmIndex']); ?>">
                                                <?php echo $module['ktmIndex']; ?>
                                            </span>
                                            <span class="text-sm text-gray-500">KTM 지수</span>
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 mb-2"><?php echo $module['details']; ?></p>
                                    
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">위험군 <?php echo $module['risk']; ?>명</span>
                                        <i data-lucide="chevron-right" class="text-gray-400 w-4 h-4"></i>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- KTM 성과 분포 차트 -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i data-lucide="bar-chart-3" class="mr-2 text-gray-600 w-5 h-5"></i>
                            KTM 성과 분포 분석
                        </h3>
                        
                        <div class="mb-6">
                            <h4 class="text-sm font-medium text-gray-600 mb-3">학습 진행률 분포</h4>
                            <div class="space-y-3" id="progressDistribution">
                                <!-- JavaScript로 렌더링 -->
                            </div>
                        </div>

                        <!-- KTM 예측 지표 -->
                        <div class="border-t pt-4">
                            <h4 class="text-sm font-medium text-gray-600 mb-3">KTM 예측 지표</h4>
                            <div class="grid grid-cols-3 gap-3">
                                <div class="text-center p-3 bg-gray-100 rounded-lg">
                                    <div class="text-lg font-bold text-gray-700">15명</div>
                                    <div class="text-xs text-gray-600">2주 내 위험군 전환 예상</div>
                                </div>
                                <div class="text-center p-3 bg-gray-100 rounded-lg">
                                    <div class="text-lg font-bold text-gray-700">23명</div>
                                    <div class="text-xs text-gray-600">목표 달성 가능 학생</div>
                                </div>
                                <div class="text-center p-3 bg-gray-100 rounded-lg">
                                    <div class="text-lg font-bold text-gray-700">89%</div>
                                    <div class="text-xs text-gray-600">예측 정확도</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KTM 인사이트 패널 -->
                <div class="space-y-6">
                    <!-- 긴급 알림 -->
                    <div class="calm-alert rounded-lg p-4 shadow-sm">
                        <div class="flex items-start">
                            <i data-lucide="alert-circle" class="mr-3 mt-0.5 w-5 h-5"></i>
                            <div>
                                <h4 class="font-medium">KTM 긴급 알림</h4>
                                <p class="text-sm mt-1 text-red-100">
                                    <?php echo $ktmStats['atRiskStudents']; ?>명의 학생이 KTM 지수 40 이하로 즉각적인 개입이 필요합니다.
                                </p>
                                <button class="mt-2 bg-gray-700 text-white px-3 py-1 rounded text-sm font-medium hover:bg-gray-800 transition-colors">
                                    긴급 대응 프로토콜 실행
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- KTM 위험군 학생 목록 -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center">
                            <i data-lucide="alert-triangle" class="mr-2 text-gray-700 w-5 h-5"></i>
                            KTM 집중 관리 대상
                        </h3>
                        <div class="space-y-3">
                            <?php foreach ($attentionStudents as $student): ?>
                            <div class="student-card border rounded-lg p-3 hover:shadow-md transition-all cursor-pointer <?php echo getSeverityColor($student['severity']); ?>"
                                 data-student='<?php echo json_encode($student); ?>'>
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-medium flex items-center">
                                            <?php echo $student['name']; ?>
                                            <span class="ml-2 text-xs px-2 py-0.5 rounded-full <?php echo $student['severity'] === 'critical' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700'; ?>">
                                                <?php echo $student['severity'] === 'critical' ? '긴급' : '주의'; ?>
                                            </span>
                                        </h4>
                                        <p class="text-sm text-gray-600"><?php echo $student['grade']; ?> | <?php echo $student['issue']; ?></p>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-2xl font-bold <?php echo getKTMScoreColor($student['ktmScore']); ?>">
                                            <?php echo $student['ktmScore']; ?>
                                        </div>
                                        <p class="text-xs text-gray-500">KTM 지수</p>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-600">최근 변화율</span>
                                    <div class="flex items-center">
                                        <i data-lucide="trending-up" class="mr-1 w-3.5 h-3.5 <?php echo $student['change'] < 0 ? 'text-red-500 rotate-180' : 'text-green-500'; ?>"></i>
                                        <span class="font-medium <?php echo $student['change'] < 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                            <?php echo $student['change'] > 0 ? '+' : ''; ?><?php echo $student['change']; ?>%
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="w-full mt-4 text-center text-sm text-blue-600 hover:text-blue-700 font-medium">
                            전체 관리 대상 목록 (<?php echo $ktmStats['atRiskStudents']; ?>명) →
                        </button>
                    </div>

                    <!-- KTM AI 분석 -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-6">
                        <h3 class="text-lg font-semibold mb-3 flex items-center">
                            <i data-lucide="brain" class="mr-2 text-purple-600 w-5 h-5"></i>
                            KTM AI 통합 분석
                        </h3>
                        <div class="space-y-3 text-sm">
                            <div class="bg-white/70 rounded-lg p-3">
                                <h4 class="font-medium text-purple-900 mb-1">🎯 핵심 발견</h4>
                                <p class="text-purple-700">중2 학생 그룹에서 함수 단원 KTM 지수가 평균 15포인트 하락. 집중 보강 필요.</p>
                            </div>
                            <div class="bg-white/70 rounded-lg p-3">
                                <h4 class="font-medium text-purple-900 mb-1">📊 패턴 분석</h4>
                                <p class="text-purple-700">오후 7-8시 수업의 집중도가 23% 감소. 수업 구조 개선 권장.</p>
                            </div>
                            <div class="bg-white/70 rounded-lg p-3">
                                <h4 class="font-medium text-purple-900 mb-1">💡 개선 제안</h4>
                                <p class="text-purple-700">부모 상담 후 KTM 지수가 평균 18포인트 상승. 정기 상담 확대 권장.</p>
                            </div>
                        </div>
                        <button class="mt-4 w-full bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700 transition-colors text-sm font-medium">
                            AI 상세 분석 리포트 생성
                        </button>
                    </div>

                    <!-- KTM 실행 센터 -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-3 flex items-center">
                            <i data-lucide="zap" class="mr-2 text-yellow-600 w-5 h-5"></i>
                            KTM 실행 센터
                        </h3>
                        <div class="space-y-2">
                            <button class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-2.5 px-4 rounded-lg hover:from-blue-600 hover:to-blue-700 transition-all text-sm font-medium shadow-md">
                                위험군 일괄 상담 예약
                            </button>
                            <button class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-2.5 px-4 rounded-lg hover:from-green-600 hover:to-green-700 transition-all text-sm font-medium shadow-md">
                                맞춤형 커리큘럼 생성
                            </button>
                            <button class="w-full bg-gradient-to-r from-purple-500 to-purple-600 text-white py-2.5 px-4 rounded-lg hover:from-purple-600 hover:to-purple-700 transition-all text-sm font-medium shadow-md">
                                학부모 통합 브리핑
                            </button>
                            <button class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-2.5 px-4 rounded-lg hover:from-orange-600 hover:to-orange-700 transition-all text-sm font-medium shadow-md">
                                KTM 성과 예측 시뮬레이션
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 학생 상세 모달 -->
        <div id="studentModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[80vh] overflow-y-auto">
                <div id="studentModalContent">
                    <!-- JavaScript로 동적 렌더링 -->
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // PHP 데이터를 JavaScript로 전달
        const ktmStats = <?php echo json_encode($ktmStats); ?>;
        const ktmMetrics = <?php echo json_encode($ktmMetrics); ?>;
        const attentionStudents = <?php echo json_encode($attentionStudents); ?>;
        const ktmModules = <?php echo json_encode($ktmModules); ?>;
    </script>
    <script src="js/ktmupgrade.js"></script>
</body>
</html>

<?php
// PHP 헬퍼 함수들
function getSeverityColor($severity) {
    switch($severity) {
        case 'critical': return 'text-gray-800 bg-gray-200 border-gray-400';
        case 'warning': return 'text-gray-700 bg-gray-100 border-gray-300';
        case 'normal': return 'text-gray-600 bg-gray-50 border-gray-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
}

function getKTMScoreColor($score) {
    if ($score >= 80) return 'text-gray-700';
    if ($score >= 60) return 'text-gray-600';
    if ($score >= 40) return 'text-gray-700';
    return 'text-gray-800';
}

function getTrendIcon($trend) {
    if ($trend === 'up') return '<i data-lucide="trending-up" class="text-gray-500 w-3.5 h-3.5"></i>';
    if ($trend === 'down') return '<i data-lucide="trending-up" class="text-gray-600 rotate-180 w-3.5 h-3.5"></i>';
    return '<i data-lucide="activity" class="text-gray-400 w-3.5 h-3.5"></i>';
}
?>