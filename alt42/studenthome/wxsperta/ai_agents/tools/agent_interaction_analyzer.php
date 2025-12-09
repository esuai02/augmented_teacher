<?php
/**
 * WXSPERTA 에이전트 상호작용 분석 도구
 * 학생과 에이전트 간의 대화 패턴과 효과성을 분석
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once("../../config.php");
global $DB, $USER;
require_login();

$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : $USER->id;
$agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;
$date_from = $_GET['date_from'] ?? date('Y-m-d', strtotime('-30 days'));
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole ? $userrole->data : 'student';

// 학생은 자기 자신의 데이터만 볼 수 있음
if ($role === 'student' && $student_id != $USER->id) {
    $student_id = $USER->id;
}

// 에이전트 정보
$agents_info = [
    1 => ['name' => '시간 수정체', 'icon' => '⏰', 'color' => 'purple'],
    2 => ['name' => '타임라인 합성기', 'icon' => '📅', 'color' => 'blue'],
    3 => ['name' => '성장 엘리베이터', 'icon' => '📈', 'color' => 'green'],
    4 => ['name' => '성과지표 엔진', 'icon' => '🎯', 'color' => 'red'],
    5 => ['name' => '동기 엔진', 'icon' => '🔥', 'color' => 'orange'],
    6 => ['name' => 'SWOT 분석기', 'icon' => '🔍', 'color' => 'indigo'],
    7 => ['name' => '일일 사령부', 'icon' => '📋', 'color' => 'teal'],
    8 => ['name' => '내면 브랜딩', 'icon' => '💎', 'color' => 'pink'],
    9 => ['name' => '수직 탐사기', 'icon' => '🔬', 'color' => 'cyan'],
    10 => ['name' => '자원 정원사', 'icon' => '🌱', 'color' => 'lime'],
    11 => ['name' => '실행 파이프라인', 'icon' => '⚙️', 'color' => 'gray'],
    12 => ['name' => '외부 브랜딩', 'icon' => '🎨', 'color' => 'violet'],
    13 => ['name' => '성장 트리거', 'icon' => '🚀', 'color' => 'amber'],
    14 => ['name' => '경쟁 생존 전략가', 'icon' => '♟️', 'color' => 'stone'],
    15 => ['name' => '시간수정체 CEO', 'icon' => '👔', 'color' => 'slate'],
    16 => ['name' => 'AI 정원사', 'icon' => '🤖', 'color' => 'emerald'],
    17 => ['name' => '신경망 설계사', 'icon' => '🧠', 'color' => 'fuchsia'],
    18 => ['name' => '정보 허브', 'icon' => '📚', 'color' => 'sky'],
    19 => ['name' => '지식 연결망', 'icon' => '🔗', 'color' => 'rose'],
    20 => ['name' => '지식 수정체', 'icon' => '💠', 'color' => 'purple'],
    21 => ['name' => '유연한 백본', 'icon' => '🦴', 'color' => 'zinc']
];

// 상호작용 데이터 가져오기
function getInteractionData($student_id, $agent_id, $date_from, $date_to) {
    global $DB;
    
    // 대화 기록 시뮬레이션 (실제로는 DB에서 가져와야 함)
    $interactions = [];
    
    // 더미 데이터 생성
    for ($i = 0; $i < 10; $i++) {
        $interactions[] = [
            'id' => $i + 1,
            'timestamp' => date('Y-m-d H:i:s', strtotime("-$i days")),
            'type' => rand(0, 1) ? 'question' : 'task',
            'content' => '샘플 상호작용 내용 ' . ($i + 1),
            'response_time' => rand(5, 300), // 초 단위
            'sentiment' => ['positive', 'neutral', 'negative'][rand(0, 2)],
            'effectiveness' => rand(60, 100)
        ];
    }
    
    return $interactions;
}

// 패턴 분석
function analyzePatterns($interactions) {
    $patterns = [
        'total_interactions' => count($interactions),
        'avg_response_time' => 0,
        'sentiment_distribution' => [
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0
        ],
        'effectiveness_score' => 0,
        'peak_hours' => [],
        'interaction_types' => [
            'question' => 0,
            'task' => 0
        ]
    ];
    
    if (empty($interactions)) {
        return $patterns;
    }
    
    $total_response_time = 0;
    $total_effectiveness = 0;
    $hour_counts = array_fill(0, 24, 0);
    
    foreach ($interactions as $interaction) {
        // 응답 시간
        $total_response_time += $interaction['response_time'];
        
        // 감정 분포
        $patterns['sentiment_distribution'][$interaction['sentiment']]++;
        
        // 효과성
        $total_effectiveness += $interaction['effectiveness'];
        
        // 상호작용 유형
        $patterns['interaction_types'][$interaction['type']]++;
        
        // 시간대별 분석
        $hour = (int)date('H', strtotime($interaction['timestamp']));
        $hour_counts[$hour]++;
    }
    
    $patterns['avg_response_time'] = round($total_response_time / count($interactions));
    $patterns['effectiveness_score'] = round($total_effectiveness / count($interactions));
    
    // 피크 시간대 찾기
    arsort($hour_counts);
    $patterns['peak_hours'] = array_slice(array_keys($hour_counts), 0, 3);
    
    return $patterns;
}

// 추천 생성
function generateRecommendations($patterns, $agent_id) {
    $recommendations = [];
    
    // 효과성 기반 추천
    if ($patterns['effectiveness_score'] < 70) {
        $recommendations[] = [
            'type' => 'improvement',
            'message' => '대화 효과성이 낮습니다. 더 구체적인 질문을 시도해보세요.',
            'priority' => 'high'
        ];
    }
    
    // 감정 기반 추천
    if ($patterns['sentiment_distribution']['negative'] > $patterns['sentiment_distribution']['positive']) {
        $recommendations[] = [
            'type' => 'motivation',
            'message' => '동기부여가 필요해 보입니다. 동기 엔진(5번) 에이전트와 대화를 추천합니다.',
            'priority' => 'medium'
        ];
    }
    
    // 시간대 기반 추천
    if (!empty($patterns['peak_hours'])) {
        $peak_hour = $patterns['peak_hours'][0];
        $recommendations[] = [
            'type' => 'schedule',
            'message' => "가장 활발한 시간대는 {$peak_hour}시입니다. 이 시간에 중요한 작업을 계획해보세요.",
            'priority' => 'low'
        ];
    }
    
    return $recommendations;
}

// 상호작용 네트워크 데이터 생성
function generateNetworkData($student_id) {
    global $DB, $agents_info;
    
    $nodes = [];
    $links = [];
    
    // 학생 노드
    $nodes[] = [
        'id' => 'student',
        'label' => '나',
        'type' => 'student',
        'size' => 30
    ];
    
    // 에이전트 노드들
    foreach ($agents_info as $id => $info) {
        // 상호작용 횟수 시뮬레이션
        $interaction_count = rand(0, 50);
        
        if ($interaction_count > 0) {
            $nodes[] = [
                'id' => "agent_$id",
                'label' => $info['icon'] . ' ' . $info['name'],
                'type' => 'agent',
                'size' => 10 + min($interaction_count, 20),
                'color' => $info['color']
            ];
            
            $links[] = [
                'source' => 'student',
                'target' => "agent_$id",
                'value' => $interaction_count
            ];
        }
    }
    
    return ['nodes' => $nodes, 'links' => $links];
}

// 현재 데이터 가져오기
$interactions = getInteractionData($student_id, $agent_id, $date_from, $date_to);
$patterns = analyzePatterns($interactions);
$recommendations = generateRecommendations($patterns, $agent_id);
$network_data = generateNetworkData($student_id);

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXSPERTA 에이전트 상호작용 분석</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        .node {
            stroke: #fff;
            stroke-width: 1.5px;
            cursor: pointer;
        }
        
        .link {
            stroke: #999;
            stroke-opacity: 0.6;
        }
        
        .node:hover {
            stroke-width: 3px;
        }
        
        .tooltip {
            position: absolute;
            text-align: center;
            padding: 8px;
            font-size: 12px;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            border-radius: 4px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.3s;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-7xl">
        <!-- 헤더 -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">📊 에이전트 상호작용 분석</h1>
            
            <!-- 필터 -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <?php if ($role === 'teacher'): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">학생</label>
                    <select id="studentSelect" class="w-full p-2 border rounded-lg">
                        <option value="">전체 학생</option>
                        <!-- 실제로는 학생 목록을 DB에서 가져와야 함 -->
                    </select>
                </div>
                <?php endif; ?>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">에이전트</label>
                    <select id="agentSelect" class="w-full p-2 border rounded-lg">
                        <option value="">전체 에이전트</option>
                        <?php foreach ($agents_info as $id => $info): ?>
                        <option value="<?php echo $id; ?>" <?php echo $agent_id == $id ? 'selected' : ''; ?>>
                            <?php echo $info['icon'] . ' ' . $info['name']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">시작 날짜</label>
                    <input type="date" id="dateFrom" value="<?php echo $date_from; ?>" 
                           class="w-full p-2 border rounded-lg">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">종료 날짜</label>
                    <input type="date" id="dateTo" value="<?php echo $date_to; ?>" 
                           class="w-full p-2 border rounded-lg">
                </div>
            </div>
            
            <button onclick="applyFilters()" 
                    class="mt-4 bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                필터 적용
            </button>
        </div>
        
        <!-- 핵심 지표 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">총 상호작용</p>
                        <p class="text-2xl font-bold"><?php echo $patterns['total_interactions']; ?></p>
                    </div>
                    <div class="text-3xl">💬</div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">평균 응답 시간</p>
                        <p class="text-2xl font-bold"><?php echo gmdate("i:s", $patterns['avg_response_time']); ?></p>
                    </div>
                    <div class="text-3xl">⏱️</div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">효과성 점수</p>
                        <p class="text-2xl font-bold"><?php echo $patterns['effectiveness_score']; ?>%</p>
                    </div>
                    <div class="text-3xl">🎯</div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">긍정적 대화</p>
                        <p class="text-2xl font-bold">
                            <?php 
                            $total_sentiment = array_sum($patterns['sentiment_distribution']);
                            echo $total_sentiment > 0 
                                ? round(($patterns['sentiment_distribution']['positive'] / $total_sentiment) * 100) 
                                : 0;
                            ?>%
                        </p>
                    </div>
                    <div class="text-3xl">😊</div>
                </div>
            </div>
        </div>
        
        <!-- 차트 영역 -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- 감정 분포 차트 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">감정 분포</h2>
                <canvas id="sentimentChart" width="400" height="200"></canvas>
            </div>
            
            <!-- 시간대별 활동 차트 -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold mb-4">시간대별 활동</h2>
                <canvas id="hourlyChart" width="400" height="200"></canvas>
            </div>
        </div>
        
        <!-- 네트워크 시각화 -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">에이전트 상호작용 네트워크</h2>
            <div id="networkChart" style="height: 400px;"></div>
            <div class="tooltip"></div>
        </div>
        
        <!-- 추천 사항 -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">💡 추천 사항</h2>
            <div class="space-y-3">
                <?php foreach ($recommendations as $rec): ?>
                <div class="flex items-start p-3 rounded-lg 
                    <?php 
                    echo $rec['priority'] === 'high' ? 'bg-red-50 border border-red-200' : 
                         ($rec['priority'] === 'medium' ? 'bg-yellow-50 border border-yellow-200' : 
                          'bg-blue-50 border border-blue-200'); 
                    ?>">
                    <span class="text-2xl mr-3">
                        <?php 
                        echo $rec['type'] === 'improvement' ? '⚠️' : 
                             ($rec['type'] === 'motivation' ? '🎯' : '📅'); 
                        ?>
                    </span>
                    <div>
                        <p class="text-sm font-medium"><?php echo $rec['message']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // 필터 적용
        function applyFilters() {
            const params = new URLSearchParams();
            
            <?php if ($role === 'teacher'): ?>
            const studentId = document.getElementById('studentSelect').value;
            if (studentId) params.append('student_id', studentId);
            <?php endif; ?>
            
            const agentId = document.getElementById('agentSelect').value;
            if (agentId) params.append('agent_id', agentId);
            
            params.append('date_from', document.getElementById('dateFrom').value);
            params.append('date_to', document.getElementById('dateTo').value);
            
            window.location.href = '?' + params.toString();
        }
        
        // 감정 분포 차트
        const sentimentCtx = document.getElementById('sentimentChart').getContext('2d');
        new Chart(sentimentCtx, {
            type: 'doughnut',
            data: {
                labels: ['긍정적', '중립', '부정적'],
                datasets: [{
                    data: [
                        <?php echo $patterns['sentiment_distribution']['positive']; ?>,
                        <?php echo $patterns['sentiment_distribution']['neutral']; ?>,
                        <?php echo $patterns['sentiment_distribution']['negative']; ?>
                    ],
                    backgroundColor: ['#10b981', '#6b7280', '#ef4444']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // 시간대별 활동 차트
        const hourlyCtx = document.getElementById('hourlyChart').getContext('2d');
        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: Array.from({length: 24}, (_, i) => i + '시'),
                datasets: [{
                    label: '상호작용 횟수',
                    data: Array.from({length: 24}, () => Math.floor(Math.random() * 10)),
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // 네트워크 시각화
        const networkData = <?php echo json_encode($network_data); ?>;
        const width = document.getElementById('networkChart').offsetWidth;
        const height = 400;
        
        const svg = d3.select("#networkChart")
            .append("svg")
            .attr("width", width)
            .attr("height", height);
        
        const simulation = d3.forceSimulation(networkData.nodes)
            .force("link", d3.forceLink(networkData.links).id(d => d.id).distance(100))
            .force("charge", d3.forceManyBody().strength(-300))
            .force("center", d3.forceCenter(width / 2, height / 2));
        
        const link = svg.append("g")
            .selectAll("line")
            .data(networkData.links)
            .enter().append("line")
            .attr("class", "link")
            .style("stroke-width", d => Math.sqrt(d.value));
        
        const node = svg.append("g")
            .selectAll("circle")
            .data(networkData.nodes)
            .enter().append("circle")
            .attr("class", "node")
            .attr("r", d => d.size)
            .style("fill", d => d.type === 'student' ? '#3b82f6' : '#' + 
                ['ef4444', '10b981', 'f59e0b', '6366f1', '8b5cf6'][Math.floor(Math.random() * 5)])
            .call(d3.drag()
                .on("start", dragstarted)
                .on("drag", dragged)
                .on("end", dragended));
        
        const text = svg.append("g")
            .selectAll("text")
            .data(networkData.nodes)
            .enter().append("text")
            .text(d => d.label)
            .style("font-size", "12px")
            .style("text-anchor", "middle");
        
        const tooltip = d3.select(".tooltip");
        
        node.on("mouseover", function(event, d) {
            tooltip.transition().duration(200).style("opacity", .9);
            tooltip.html(d.label + "<br/>상호작용: " + (d.size - 10))
                .style("left", (event.pageX + 10) + "px")
                .style("top", (event.pageY - 28) + "px");
        })
        .on("mouseout", function(d) {
            tooltip.transition().duration(500).style("opacity", 0);
        });
        
        simulation.on("tick", () => {
            link
                .attr("x1", d => d.source.x)
                .attr("y1", d => d.source.y)
                .attr("x2", d => d.target.x)
                .attr("y2", d => d.target.y);
            
            node
                .attr("cx", d => d.x)
                .attr("cy", d => d.y);
            
            text
                .attr("x", d => d.x)
                .attr("y", d => d.y + 30);
        });
        
        function dragstarted(event, d) {
            if (!event.active) simulation.alphaTarget(0.3).restart();
            d.fx = d.x;
            d.fy = d.y;
        }
        
        function dragged(event, d) {
            d.fx = event.x;
            d.fy = event.y;
        }
        
        function dragended(event, d) {
            if (!event.active) simulation.alphaTarget(0);
            d.fx = null;
            d.fy = null;
        }
    </script>
</body>
</html>