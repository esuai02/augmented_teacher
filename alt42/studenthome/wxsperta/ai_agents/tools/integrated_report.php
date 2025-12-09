<?php
/**
 * WXSPERTA 통합 리포트 생성 도구
 * 학생의 전체 활동을 분석하여 종합적인 리포트 생성
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once("../../config.php");
global $DB, $USER;
require_login();

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : $USER->id;
$period = $_GET['period'] ?? 'month'; // week, month, quarter
$format = $_GET['format'] ?? 'view'; // view, pdf, email

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole ? $userrole->data : 'student';

// 학생은 자신의 데이터만 볼 수 있음
if ($role === 'student' && $student_id != $USER->id) {
    $student_id = $USER->id;
}

// 기간 설정
$date_ranges = [
    'week' => ['start' => date('Y-m-d', strtotime('-1 week')), 'end' => date('Y-m-d')],
    'month' => ['start' => date('Y-m-d', strtotime('-1 month')), 'end' => date('Y-m-d')],
    'quarter' => ['start' => date('Y-m-d', strtotime('-3 months')), 'end' => date('Y-m-d')]
];

$date_range = $date_ranges[$period];

// 학생 정보
$student = $DB->get_record('user', ['id' => $student_id]);
$student_name = $student ? $student->firstname . ' ' . $student->lastname : 'Unknown';

// 리포트 데이터 수집
function collectReportData($student_id, $date_range) {
    global $DB;
    
    $report = [
        'summary' => getSummaryData($student_id),
        'agent_progress' => getAgentProgressData($student_id),
        'interaction_stats' => getInteractionStats($student_id, $date_range),
        'achievements' => getAchievements($student_id, $date_range),
        'recommendations' => getPersonalizedRecommendations($student_id),
        'growth_trends' => getGrowthTrends($student_id, $date_range)
    ];
    
    return $report;
}

// 요약 데이터
function getSummaryData($student_id) {
    global $DB;
    
    // 전체 진행률 계산
    $total_progress = 0;
    $agent_count = 0;
    
    for ($i = 1; $i <= 21; $i++) {
        $properties = $DB->get_record('wxsperta_agent_texts_current', ['card_id' => $i]);
        if ($properties) {
            $props = json_decode($properties->properties_json, true);
            $filled = count(array_filter($props, function($v) { return !empty($v); }));
            $progress = round(($filled / 8) * 100);
            $total_progress += $progress;
            $agent_count++;
        }
    }
    
    $average_progress = $agent_count > 0 ? round($total_progress / $agent_count) : 0;
    
    // 활동일수 계산 (시뮬레이션)
    $active_days = rand(15, 28);
    $total_interactions = rand(50, 200);
    $completed_projects = rand(2, 8);
    
    return [
        'average_progress' => $average_progress,
        'active_days' => $active_days,
        'total_interactions' => $total_interactions,
        'completed_projects' => $completed_projects,
        'strongest_category' => getStrongestCategory($student_id),
        'improvement_rate' => rand(10, 35) // 개선율 %
    ];
}

// 에이전트별 진행률 데이터
function getAgentProgressData($student_id) {
    global $DB;
    
    $agents_info = [
        1 => ['name' => '시간 수정체', 'category' => 'future_design'],
        2 => ['name' => '타임라인 합성기', 'category' => 'future_design'],
        3 => ['name' => '성장 엘리베이터', 'category' => 'future_design'],
        4 => ['name' => '성과지표 엔진', 'category' => 'future_design'],
        5 => ['name' => '동기 엔진', 'category' => 'execution'],
        6 => ['name' => 'SWOT 분석기', 'category' => 'execution'],
        7 => ['name' => '일일 사령부', 'category' => 'execution'],
        8 => ['name' => '내면 브랜딩', 'category' => 'execution'],
        9 => ['name' => '수직 탐사기', 'category' => 'execution'],
        10 => ['name' => '자원 정원사', 'category' => 'execution'],
        11 => ['name' => '실행 파이프라인', 'category' => 'execution'],
        12 => ['name' => '외부 브랜딩', 'category' => 'branding'],
        13 => ['name' => '성장 트리거', 'category' => 'branding'],
        14 => ['name' => '경쟁 생존 전략가', 'category' => 'branding'],
        15 => ['name' => '시간수정체 CEO', 'category' => 'knowledge_management'],
        16 => ['name' => 'AI 정원사', 'category' => 'knowledge_management'],
        17 => ['name' => '신경망 설계사', 'category' => 'knowledge_management'],
        18 => ['name' => '정보 허브', 'category' => 'knowledge_management'],
        19 => ['name' => '지식 연결망', 'category' => 'knowledge_management'],
        20 => ['name' => '지식 수정체', 'category' => 'knowledge_management'],
        21 => ['name' => '유연한 백본', 'category' => 'knowledge_management']
    ];
    
    $progress_data = [];
    
    foreach ($agents_info as $agent_id => $info) {
        $properties = $DB->get_record('wxsperta_agent_texts_current', ['card_id' => $agent_id]);
        
        if ($properties) {
            $props = json_decode($properties->properties_json, true);
            $filled = count(array_filter($props, function($v) { return !empty($v); }));
            $progress = round(($filled / 8) * 100);
        } else {
            $progress = 0;
        }
        
        $progress_data[] = [
            'agent_id' => $agent_id,
            'name' => $info['name'],
            'category' => $info['category'],
            'progress' => $progress,
            'status' => getProgressStatus($progress)
        ];
    }
    
    // 진행률 기준 정렬
    usort($progress_data, function($a, $b) {
        return $b['progress'] - $a['progress'];
    });
    
    return $progress_data;
}

// 상호작용 통계
function getInteractionStats($student_id, $date_range) {
    // 시뮬레이션 데이터
    return [
        'total_messages' => rand(100, 500),
        'avg_daily_interactions' => rand(3, 15),
        'peak_hour' => rand(14, 20),
        'response_rate' => rand(70, 95),
        'quality_score' => rand(65, 90),
        'most_active_agents' => [
            ['id' => 7, 'name' => '일일 사령부', 'count' => rand(20, 50)],
            ['id' => 5, 'name' => '동기 엔진', 'count' => rand(15, 40)],
            ['id' => 3, 'name' => '성장 엘리베이터', 'count' => rand(10, 35)]
        ]
    ];
}

// 성취 데이터
function getAchievements($student_id, $date_range) {
    return [
        'milestones' => [
            ['date' => date('Y-m-d', strtotime('-2 weeks')), 'title' => '첫 프로젝트 완료', 'agent' => '시간 수정체'],
            ['date' => date('Y-m-d', strtotime('-1 week')), 'title' => '일주일 연속 활동', 'agent' => '일일 사령부'],
            ['date' => date('Y-m-d', strtotime('-3 days')), 'title' => 'SWOT 분석 마스터', 'agent' => 'SWOT 분석기']
        ],
        'badges' => [
            ['name' => '꾸준한 학습자', 'icon' => '🏆', 'description' => '7일 연속 학습'],
            ['name' => '탐구자', 'icon' => '🔍', 'description' => '5개 이상 에이전트 활용'],
            ['name' => '성장 주도자', 'icon' => '📈', 'description' => '월간 30% 이상 성장']
        ],
        'completed_projects' => rand(3, 10),
        'total_points' => rand(500, 2000)
    ];
}

// 개인화된 추천
function getPersonalizedRecommendations($student_id) {
    return [
        'next_steps' => [
            '미완성 프로젝트를 우선적으로 완료하세요',
            '지식관리 카테고리의 에이전트를 더 활용해보세요',
            '주간 목표를 설정하고 일일 사령부로 관리하세요'
        ],
        'focus_areas' => [
            ['area' => '시간 관리', 'priority' => 'high'],
            ['area' => '지식 체계화', 'priority' => 'medium'],
            ['area' => '동기 유지', 'priority' => 'low']
        ],
        'recommended_agents' => [16, 17, 20] // AI 정원사, 신경망 설계사, 지식 수정체
    ];
}

// 성장 트렌드
function getGrowthTrends($student_id, $date_range) {
    // 주간 데이터 생성
    $weeks = [];
    for ($i = 4; $i >= 0; $i--) {
        $weeks[] = [
            'week' => date('Y-m-d', strtotime("-$i weeks")),
            'progress' => rand(40 + ($i * 5), 60 + ($i * 8)),
            'interactions' => rand(20, 50),
            'completions' => rand(0, 3)
        ];
    }
    
    return [
        'weekly_data' => $weeks,
        'growth_rate' => rand(5, 20),
        'consistency_score' => rand(60, 90),
        'momentum' => 'increasing' // increasing, stable, decreasing
    ];
}

// 헬퍼 함수들
function getStrongestCategory($student_id) {
    $categories = ['미래설계', '실행', '브랜딩', '지식관리'];
    return $categories[rand(0, 3)];
}

function getProgressStatus($progress) {
    if ($progress >= 80) return 'completed';
    if ($progress >= 50) return 'active';
    if ($progress >= 20) return 'started';
    return 'not_started';
}

// 리포트 데이터 수집
$report_data = collectReportData($student_id, $date_range);

// PDF 생성 처리
if ($format === 'pdf') {
    // PDF 생성 로직 (실제 구현시 TCPDF 등 사용)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="wxsperta_report_' . date('Y-m-d') . '.pdf"');
    echo "PDF 생성 기능은 추후 구현됩니다.";
    exit;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXSPERTA 통합 리포트</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            .page-break {
                page-break-after: always;
            }
            
            body {
                font-size: 12pt;
            }
        }
        
        .report-section {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .progress-item {
            transition: all 0.2s ease;
        }
        
        .progress-item:hover {
            background: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <!-- 헤더 -->
        <div class="report-section no-print">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-3xl font-bold">WXSPERTA 통합 리포트</h1>
                <div class="flex gap-2">
                    <button onclick="window.print()" 
                            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700">
                        🖨️ 인쇄
                    </button>
                    <a href="?student_id=<?php echo $student_id; ?>&period=<?php echo $period; ?>&format=pdf" 
                       class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        📄 PDF 다운로드
                    </a>
                </div>
            </div>
            
            <!-- 기간 선택 -->
            <div class="flex gap-2">
                <?php
                $periods = [
                    'week' => '주간',
                    'month' => '월간',
                    'quarter' => '분기'
                ];
                foreach ($periods as $p_id => $p_label): ?>
                <button onclick="changePeriod('<?php echo $p_id; ?>')" 
                        class="px-4 py-2 rounded-lg <?php echo $period === $p_id ? 'bg-blue-500 text-white' : 'bg-gray-200'; ?>">
                    <?php echo $p_label; ?> 리포트
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 리포트 타이틀 -->
        <div class="report-section">
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-2"><?php echo $student_name; ?>님의 학습 리포트</h2>
                <p class="text-gray-600">
                    기간: <?php echo $date_range['start']; ?> ~ <?php echo $date_range['end']; ?>
                </p>
            </div>
        </div>
        
        <!-- 핵심 요약 -->
        <div class="report-section">
            <h3 class="text-xl font-semibold mb-4">📊 핵심 요약</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="stat-card bg-blue-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">전체 진행률</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $report_data['summary']['average_progress']; ?>%</p>
                </div>
                <div class="stat-card bg-green-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">활동일수</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $report_data['summary']['active_days']; ?>일</p>
                </div>
                <div class="stat-card bg-purple-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">총 상호작용</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo $report_data['summary']['total_interactions']; ?>회</p>
                </div>
                <div class="stat-card bg-yellow-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">개선율</p>
                    <p class="text-3xl font-bold text-yellow-600">+<?php echo $report_data['summary']['improvement_rate']; ?>%</p>
                </div>
            </div>
            
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm text-gray-700">
                    <span class="font-semibold">가장 강한 영역:</span> <?php echo $report_data['summary']['strongest_category']; ?> | 
                    <span class="font-semibold">완료 프로젝트:</span> <?php echo $report_data['summary']['completed_projects']; ?>개
                </p>
            </div>
        </div>
        
        <!-- 에이전트별 진행 현황 -->
        <div class="report-section">
            <h3 class="text-xl font-semibold mb-4">🤖 에이전트별 진행 현황</h3>
            
            <!-- 상위 5개 -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-600 mb-3">TOP 5 진행률</h4>
                <div class="space-y-2">
                    <?php foreach (array_slice($report_data['agent_progress'], 0, 5) as $agent): ?>
                    <div class="progress-item flex items-center p-3 rounded-lg">
                        <span class="font-medium mr-auto"><?php echo $agent['name']; ?></span>
                        <div class="flex items-center">
                            <div class="w-32 bg-gray-200 rounded-full h-2 mr-3">
                                <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $agent['progress']; ?>%"></div>
                            </div>
                            <span class="text-sm font-medium"><?php echo $agent['progress']; ?>%</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 카테고리별 평균 -->
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-3">카테고리별 평균 진행률</h4>
                <canvas id="categoryChart" height="80"></canvas>
            </div>
        </div>
        
        <!-- 성장 트렌드 -->
        <div class="report-section page-break">
            <h3 class="text-xl font-semibold mb-4">📈 성장 트렌드</h3>
            <canvas id="growthChart" height="100"></canvas>
            
            <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">성장률</p>
                    <p class="text-lg font-semibold">+<?php echo $report_data['growth_trends']['growth_rate']; ?>%</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">일관성 점수</p>
                    <p class="text-lg font-semibold"><?php echo $report_data['growth_trends']['consistency_score']; ?>/100</p>
                </div>
                <div class="p-3 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">모멘텀</p>
                    <p class="text-lg font-semibold">
                        <?php 
                        $momentum_labels = [
                            'increasing' => '상승 중 ↗',
                            'stable' => '안정적 →',
                            'decreasing' => '하락 중 ↘'
                        ];
                        echo $momentum_labels[$report_data['growth_trends']['momentum']];
                        ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- 성취 및 배지 -->
        <div class="report-section">
            <h3 class="text-xl font-semibold mb-4">🏆 성취 및 배지</h3>
            
            <!-- 최근 마일스톤 -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-600 mb-3">최근 마일스톤</h4>
                <div class="space-y-2">
                    <?php foreach ($report_data['achievements']['milestones'] as $milestone): ?>
                    <div class="flex items-center p-3 bg-blue-50 rounded-lg">
                        <span class="text-sm text-gray-500 mr-4"><?php echo $milestone['date']; ?></span>
                        <span class="font-medium"><?php echo $milestone['title']; ?></span>
                        <span class="ml-auto text-sm text-gray-600"><?php echo $milestone['agent']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- 획득 배지 -->
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-3">획득한 배지</h4>
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach ($report_data['achievements']['badges'] as $badge): ?>
                    <div class="text-center p-4 bg-yellow-50 rounded-lg">
                        <span class="text-3xl"><?php echo $badge['icon']; ?></span>
                        <p class="font-medium mt-2"><?php echo $badge['name']; ?></p>
                        <p class="text-xs text-gray-600"><?php echo $badge['description']; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- 추천 사항 -->
        <div class="report-section">
            <h3 class="text-xl font-semibold mb-4">💡 맞춤형 추천</h3>
            
            <!-- 다음 단계 -->
            <div class="mb-6">
                <h4 class="text-sm font-medium text-gray-600 mb-3">다음 단계 제안</h4>
                <ul class="space-y-2">
                    <?php foreach ($report_data['recommendations']['next_steps'] as $step): ?>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span><?php echo $step; ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- 집중 영역 -->
            <div>
                <h4 class="text-sm font-medium text-gray-600 mb-3">집중이 필요한 영역</h4>
                <div class="grid grid-cols-3 gap-4">
                    <?php foreach ($report_data['recommendations']['focus_areas'] as $area): ?>
                    <div class="p-3 border rounded-lg <?php 
                        echo $area['priority'] === 'high' ? 'border-red-300 bg-red-50' : 
                             ($area['priority'] === 'medium' ? 'border-yellow-300 bg-yellow-50' : 
                              'border-green-300 bg-green-50'); 
                    ?>">
                        <p class="font-medium"><?php echo $area['area']; ?></p>
                        <p class="text-xs text-gray-600">
                            우선순위: <?php 
                            echo $area['priority'] === 'high' ? '높음' : 
                                 ($area['priority'] === 'medium' ? '중간' : '낮음'); 
                            ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- 푸터 -->
        <div class="text-center text-sm text-gray-500 mt-8">
            <p>이 리포트는 <?php echo date('Y년 m월 d일 H:i'); ?>에 생성되었습니다.</p>
            <p class="mt-1">WXSPERTA AI 에이전트 시스템</p>
        </div>
    </div>

    <script>
        // 기간 변경
        function changePeriod(newPeriod) {
            const params = new URLSearchParams(window.location.search);
            params.set('period', newPeriod);
            window.location.search = params.toString();
        }
        
        // 카테고리별 차트
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'bar',
            data: {
                labels: ['미래설계', '실행', '브랜딩', '지식관리'],
                datasets: [{
                    label: '평균 진행률',
                    data: [65, 72, 48, 55],
                    backgroundColor: [
                        'rgba(147, 51, 234, 0.5)',
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                        'rgba(34, 197, 94, 0.5)'
                    ],
                    borderColor: [
                        'rgba(147, 51, 234, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(236, 72, 153, 1)',
                        'rgba(34, 197, 94, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
        
        // 성장 트렌드 차트
        const growthCtx = document.getElementById('growthChart').getContext('2d');
        const weeklyData = <?php echo json_encode($report_data['growth_trends']['weekly_data']); ?>;
        
        new Chart(growthCtx, {
            type: 'line',
            data: {
                labels: weeklyData.map(w => w.week.substring(5)),
                datasets: [{
                    label: '진행률',
                    data: weeklyData.map(w => w.progress),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                }, {
                    label: '상호작용',
                    data: weeklyData.map(w => w.interactions),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.4,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: '진행률 (%)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: '상호작용 (회)'
                        },
                        grid: {
                            drawOnChartArea: false,
                        },
                    },
                }
            }
        });
    </script>
</body>
</html>