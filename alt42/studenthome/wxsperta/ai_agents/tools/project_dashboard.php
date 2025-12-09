<?php
/**
 * WXSPERTA 프로젝트 진행 상황 대시보드
 * 21개 에이전트의 프로젝트 진행 현황을 한눈에 보여주는 통합 대시보드
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once("../../config.php");
global $DB, $USER;
require_login();

$view_mode = $_GET['view'] ?? 'grid'; // grid, list, timeline
$category_filter = $_GET['category'] ?? 'all';
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : $USER->id;

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole ? $userrole->data : 'student';

// 학생은 자신의 데이터만 볼 수 있음
if ($role === 'student' && $student_id != $USER->id) {
    $student_id = $USER->id;
}

// 에이전트 정보 및 카테고리
$categories = [
    'future_design' => ['name' => '미래설계', 'color' => 'purple', 'agents' => [1, 2, 3, 4]],
    'execution' => ['name' => '실행', 'color' => 'blue', 'agents' => [5, 6, 7, 8, 9, 10, 11]],
    'branding' => ['name' => '브랜딩', 'color' => 'pink', 'agents' => [12, 13, 14]],
    'knowledge_management' => ['name' => '지식관리', 'color' => 'green', 'agents' => [15, 16, 17, 18, 19, 20, 21]]
];

$agents_info = [
    1 => ['name' => '시간 수정체', 'icon' => '⏰', 'category' => 'future_design'],
    2 => ['name' => '타임라인 합성기', 'icon' => '📅', 'category' => 'future_design'],
    3 => ['name' => '성장 엘리베이터', 'icon' => '📈', 'category' => 'future_design'],
    4 => ['name' => '성과지표 엔진', 'icon' => '🎯', 'category' => 'future_design'],
    5 => ['name' => '동기 엔진', 'icon' => '🔥', 'category' => 'execution'],
    6 => ['name' => 'SWOT 분석기', 'icon' => '🔍', 'category' => 'execution'],
    7 => ['name' => '일일 사령부', 'icon' => '📋', 'category' => 'execution'],
    8 => ['name' => '내면 브랜딩', 'icon' => '💎', 'category' => 'execution'],
    9 => ['name' => '수직 탐사기', 'icon' => '🔬', 'category' => 'execution'],
    10 => ['name' => '자원 정원사', 'icon' => '🌱', 'category' => 'execution'],
    11 => ['name' => '실행 파이프라인', 'icon' => '⚙️', 'category' => 'execution'],
    12 => ['name' => '외부 브랜딩', 'icon' => '🎨', 'category' => 'branding'],
    13 => ['name' => '성장 트리거', 'icon' => '🚀', 'category' => 'branding'],
    14 => ['name' => '경쟁 생존 전략가', 'icon' => '♟️', 'category' => 'branding'],
    15 => ['name' => '시간수정체 CEO', 'icon' => '👔', 'category' => 'knowledge_management'],
    16 => ['name' => 'AI 정원사', 'icon' => '🤖', 'category' => 'knowledge_management'],
    17 => ['name' => '신경망 설계사', 'icon' => '🧠', 'category' => 'knowledge_management'],
    18 => ['name' => '정보 허브', 'icon' => '📚', 'category' => 'knowledge_management'],
    19 => ['name' => '지식 연결망', 'icon' => '🔗', 'category' => 'knowledge_management'],
    20 => ['name' => '지식 수정체', 'icon' => '💠', 'category' => 'knowledge_management'],
    21 => ['name' => '유연한 백본', 'icon' => '🦴', 'category' => 'knowledge_management']
];

// 프로젝트 진행 상황 데이터 가져오기
function getProjectProgress($student_id, $agent_id) {
    global $DB;
    
    // WXSPERTA 속성에서 진행률 계산
    $properties = $DB->get_record('wxsperta_agent_texts_current', ['card_id' => $agent_id]);
    
    if (!$properties) {
        return [
            'stage1' => 0,
            'stage2' => 0,
            'stage3' => 0,
            'overall' => 0,
            'last_activity' => null,
            'status' => 'not_started'
        ];
    }
    
    $props = json_decode($properties->properties_json, true);
    
    // 각 스테이지별 진행률 계산
    $stage1_props = ['worldView', 'context', 'structure'];
    $stage2_props = ['process', 'execution', 'reflection'];
    $stage3_props = ['transfer', 'abstraction'];
    
    $stage1_count = count(array_filter($stage1_props, function($key) use ($props) {
        return !empty($props[$key]);
    }));
    
    $stage2_count = count(array_filter($stage2_props, function($key) use ($props) {
        return !empty($props[$key]);
    }));
    
    $stage3_count = count(array_filter($stage3_props, function($key) use ($props) {
        return !empty($props[$key]);
    }));
    
    $stage1_progress = round(($stage1_count / count($stage1_props)) * 100);
    $stage2_progress = round(($stage2_count / count($stage2_props)) * 100);
    $stage3_progress = round(($stage3_count / count($stage3_props)) * 100);
    
    $overall = round(($stage1_progress + $stage2_progress + $stage3_progress) / 3);
    
    // 상태 결정
    $status = 'not_started';
    if ($overall > 0 && $overall < 30) $status = 'planning';
    else if ($overall >= 30 && $overall < 70) $status = 'in_progress';
    else if ($overall >= 70 && $overall < 100) $status = 'review';
    else if ($overall == 100) $status = 'completed';
    
    // 마지막 활동 시간 (시뮬레이션)
    $last_activity = $properties->last_updated ?? null;
    
    return [
        'stage1' => $stage1_progress,
        'stage2' => $stage2_progress,
        'stage3' => $stage3_progress,
        'overall' => $overall,
        'last_activity' => $last_activity,
        'status' => $status
    ];
}

// 전체 대시보드 데이터 수집
$dashboard_data = [];
$total_progress = 0;
$category_progress = [];

foreach ($agents_info as $agent_id => $agent) {
    if ($category_filter !== 'all' && $agent['category'] !== $category_filter) {
        continue;
    }
    
    $progress = getProjectProgress($student_id, $agent_id);
    $dashboard_data[$agent_id] = array_merge($agent, $progress);
    
    $total_progress += $progress['overall'];
    
    if (!isset($category_progress[$agent['category']])) {
        $category_progress[$agent['category']] = [
            'total' => 0,
            'count' => 0
        ];
    }
    
    $category_progress[$agent['category']]['total'] += $progress['overall'];
    $category_progress[$agent['category']]['count']++;
}

// 평균 계산
$average_progress = count($dashboard_data) > 0 ? round($total_progress / count($dashboard_data)) : 0;

foreach ($category_progress as $cat => &$data) {
    $data['average'] = $data['count'] > 0 ? round($data['total'] / $data['count']) : 0;
}

// 타임라인 데이터 생성
function generateTimelineData($dashboard_data) {
    $timeline = [];
    
    foreach ($dashboard_data as $agent_id => $data) {
        if ($data['last_activity']) {
            $timeline[] = [
                'date' => date('Y-m-d', $data['last_activity']),
                'time' => date('H:i', $data['last_activity']),
                'agent_id' => $agent_id,
                'agent_name' => $data['name'],
                'agent_icon' => $data['icon'],
                'progress' => $data['overall'],
                'status' => $data['status']
            ];
        }
    }
    
    // 날짜순 정렬
    usort($timeline, function($a, $b) {
        return strtotime($b['date'] . ' ' . $b['time']) - strtotime($a['date'] . ' ' . $a['time']);
    });
    
    return $timeline;
}

$timeline_data = generateTimelineData($dashboard_data);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXSPERTA 프로젝트 대시보드</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-ring {
            transform: rotate(-90deg);
        }
        
        .progress-ring-circle {
            transition: stroke-dashoffset 0.5s ease;
        }
        
        .agent-card {
            transition: all 0.3s ease;
        }
        
        .agent-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .timeline-item {
            position: relative;
            padding-left: 40px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: -20px;
            width: 2px;
            background: #e5e7eb;
        }
        
        .timeline-item:last-child::before {
            display: none;
        }
        
        .timeline-dot {
            position: absolute;
            left: 10px;
            top: 10px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: white;
            border: 3px solid #3b82f6;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- 헤더 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-4">
                <h1 class="text-2xl font-bold">📊 WXSPERTA 프로젝트 대시보드</h1>
                <div class="flex gap-2">
                    <button onclick="changeView('grid')" 
                            class="px-4 py-2 rounded-lg <?php echo $view_mode === 'grid' ? 'bg-blue-500 text-white' : 'bg-gray-200'; ?>">
                        그리드 뷰
                    </button>
                    <button onclick="changeView('list')" 
                            class="px-4 py-2 rounded-lg <?php echo $view_mode === 'list' ? 'bg-blue-500 text-white' : 'bg-gray-200'; ?>">
                        리스트 뷰
                    </button>
                    <button onclick="changeView('timeline')" 
                            class="px-4 py-2 rounded-lg <?php echo $view_mode === 'timeline' ? 'bg-blue-500 text-white' : 'bg-gray-200'; ?>">
                        타임라인
                    </button>
                </div>
            </div>
            
            <!-- 카테고리 필터 -->
            <div class="flex gap-2 flex-wrap">
                <button onclick="filterCategory('all')" 
                        class="px-4 py-2 rounded-lg <?php echo $category_filter === 'all' ? 'bg-gray-800 text-white' : 'bg-gray-200'; ?>">
                    전체
                </button>
                <?php foreach ($categories as $cat_id => $cat_info): ?>
                <button onclick="filterCategory('<?php echo $cat_id; ?>')" 
                        class="px-4 py-2 rounded-lg <?php echo $category_filter === $cat_id ? 'bg-' . $cat_info['color'] . '-500 text-white' : 'bg-gray-200'; ?>">
                    <?php echo $cat_info['name']; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 전체 진행률 요약 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm text-gray-600 mb-2">전체 진행률</h3>
                <div class="flex items-center justify-between">
                    <span class="text-3xl font-bold"><?php echo $average_progress; ?>%</span>
                    <svg width="60" height="60">
                        <circle cx="30" cy="30" r="25" stroke="#e5e7eb" stroke-width="5" fill="none" />
                        <circle cx="30" cy="30" r="25" stroke="#3b82f6" stroke-width="5" fill="none"
                                class="progress-ring progress-ring-circle"
                                stroke-dasharray="157"
                                stroke-dashoffset="<?php echo 157 - (157 * $average_progress / 100); ?>" />
                    </svg>
                </div>
            </div>
            
            <?php foreach ($categories as $cat_id => $cat_info): ?>
            <?php if (isset($category_progress[$cat_id])): ?>
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm text-gray-600 mb-2"><?php echo $cat_info['name']; ?></h3>
                <div class="flex items-center justify-between">
                    <span class="text-2xl font-bold"><?php echo $category_progress[$cat_id]['average']; ?>%</span>
                    <div class="text-<?php echo $cat_info['color']; ?>-500">
                        <svg width="40" height="40" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <!-- 메인 콘텐츠 -->
        <?php if ($view_mode === 'grid'): ?>
        <!-- 그리드 뷰 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($dashboard_data as $agent_id => $data): ?>
            <div class="agent-card bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center">
                        <span class="text-3xl mr-3"><?php echo $data['icon']; ?></span>
                        <div>
                            <h3 class="font-semibold"><?php echo $data['name']; ?></h3>
                            <span class="text-xs text-gray-500"><?php echo $categories[$data['category']]['name']; ?></span>
                        </div>
                    </div>
                    <span class="text-2xl font-bold text-<?php echo $data['overall'] >= 70 ? 'green' : ($data['overall'] >= 30 ? 'yellow' : 'gray'); ?>-500">
                        <?php echo $data['overall']; ?>%
                    </span>
                </div>
                
                <!-- 스테이지별 진행률 -->
                <div class="space-y-2 mb-4">
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Stage 1: 기초</span>
                            <span><?php echo $data['stage1']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $data['stage1']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Stage 2: 실행</span>
                            <span><?php echo $data['stage2']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $data['stage2']; ?>%"></div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex justify-between text-xs text-gray-600 mb-1">
                            <span>Stage 3: 확산</span>
                            <span><?php echo $data['stage3']; ?>%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $data['stage3']; ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- 상태 및 액션 -->
                <div class="flex items-center justify-between">
                    <span class="px-2 py-1 text-xs rounded-full 
                        <?php 
                        echo $data['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                             ($data['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                              ($data['status'] === 'review' ? 'bg-yellow-100 text-yellow-800' : 
                               'bg-gray-100 text-gray-800')); 
                        ?>">
                        <?php 
                        $status_labels = [
                            'not_started' => '시작 전',
                            'planning' => '계획 중',
                            'in_progress' => '진행 중',
                            'review' => '검토 중',
                            'completed' => '완료'
                        ];
                        echo $status_labels[$data['status']] ?? '알 수 없음';
                        ?>
                    </span>
                    
                    <a href="../<?php echo str_replace('_', '/', $data['category']); ?>/<?php echo str_pad($agent_id, 2, '0', STR_PAD_LEFT); ?>_*/index.php" 
                       class="text-blue-500 hover:text-blue-700 text-sm">
                        프로젝트 보기 →
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php elseif ($view_mode === 'list'): ?>
        <!-- 리스트 뷰 -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            에이전트
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            카테고리
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stage 1
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stage 2
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Stage 3
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            전체
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            상태
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            액션
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($dashboard_data as $agent_id => $data): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3"><?php echo $data['icon']; ?></span>
                                <span class="text-sm font-medium text-gray-900"><?php echo $data['name']; ?></span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm text-gray-500"><?php echo $categories[$data['category']]['name']; ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-900 mr-2"><?php echo $data['stage1']; ?>%</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo $data['stage1']; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-900 mr-2"><?php echo $data['stage2']; ?>%</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $data['stage2']; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <span class="text-sm text-gray-900 mr-2"><?php echo $data['stage3']; ?>%</span>
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: <?php echo $data['stage3']; ?>%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-sm font-bold text-<?php echo $data['overall'] >= 70 ? 'green' : ($data['overall'] >= 30 ? 'yellow' : 'gray'); ?>-500">
                                <?php echo $data['overall']; ?>%
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php 
                                echo $data['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                     ($data['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                                      ($data['status'] === 'review' ? 'bg-yellow-100 text-yellow-800' : 
                                       'bg-gray-100 text-gray-800')); 
                                ?>">
                                <?php 
                                $status_labels = [
                                    'not_started' => '시작 전',
                                    'planning' => '계획 중',
                                    'in_progress' => '진행 중',
                                    'review' => '검토 중',
                                    'completed' => '완료'
                                ];
                                echo $status_labels[$data['status']] ?? '알 수 없음';
                                ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <a href="#" class="text-blue-500 hover:text-blue-700">보기</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php else: ?>
        <!-- 타임라인 뷰 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">최근 활동 타임라인</h2>
            <div class="space-y-4">
                <?php foreach ($timeline_data as $item): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2"><?php echo $item['agent_icon']; ?></span>
                                <span class="font-medium"><?php echo $item['agent_name']; ?></span>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo $item['date']; ?> <?php echo $item['time']; ?></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600">진행률: <?php echo $item['progress']; ?>%</span>
                            <span class="px-2 py-1 text-xs rounded-full 
                                <?php 
                                echo $item['status'] === 'completed' ? 'bg-green-100 text-green-800' : 
                                     ($item['status'] === 'in_progress' ? 'bg-blue-100 text-blue-800' : 
                                      'bg-gray-100 text-gray-800'); 
                                ?>">
                                <?php 
                                $status_labels = [
                                    'not_started' => '시작 전',
                                    'planning' => '계획 중',
                                    'in_progress' => '진행 중',
                                    'review' => '검토 중',
                                    'completed' => '완료'
                                ];
                                echo $status_labels[$item['status']] ?? '알 수 없음';
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($timeline_data)): ?>
                <p class="text-gray-500 text-center py-8">아직 활동 기록이 없습니다.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 진행률 차트 -->
        <div class="mt-6 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">카테고리별 진행률 분석</h2>
            <canvas id="progressChart" width="400" height="100"></canvas>
        </div>
    </div>

    <script>
        // 뷰 모드 변경
        function changeView(mode) {
            const params = new URLSearchParams(window.location.search);
            params.set('view', mode);
            window.location.search = params.toString();
        }
        
        // 카테고리 필터
        function filterCategory(category) {
            const params = new URLSearchParams(window.location.search);
            params.set('category', category);
            window.location.search = params.toString();
        }
        
        // 진행률 차트
        const ctx = document.getElementById('progressChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php foreach ($categories as $cat_id => $cat_info): ?>
                    '<?php echo $cat_info['name']; ?>',
                    <?php endforeach; ?>
                ],
                datasets: [{
                    label: '평균 진행률',
                    data: [
                        <?php foreach ($categories as $cat_id => $cat_info): ?>
                        <?php echo $category_progress[$cat_id]['average'] ?? 0; ?>,
                        <?php endforeach; ?>
                    ],
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
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + '%';
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>