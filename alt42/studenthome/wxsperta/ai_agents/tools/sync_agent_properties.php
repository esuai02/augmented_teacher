<?php
/**
 * WXSPERTA 에이전트 속성 동기화 도구
 * 상위 WXSPERTA 시스템의 8-layer 속성을 각 에이전트 프로젝트와 동기화
 */

include_once("/home/moodle/public_html/moodle/config.php");
require_once("../../config.php");
global $DB, $USER;
require_login();

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole ? $userrole->data : 'student';

if ($role !== 'teacher') {
    die(json_encode(['error' => '교사 권한이 필요합니다.']));
}

// 동기화할 에이전트 ID
$agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;
$action = $_GET['action'] ?? 'view';

// 에이전트 정보
$agents_info = [
    1 => ['name' => '시간 수정체', 'path' => 'future_design/01_time_capsule'],
    2 => ['name' => '타임라인 합성기', 'path' => 'future_design/02_timeline_synthesizer'],
    3 => ['name' => '성장 엘리베이터', 'path' => 'future_design/03_growth_elevator'],
    4 => ['name' => '성과지표 엔진', 'path' => 'future_design/04_performance_engine'],
    5 => ['name' => '동기 엔진', 'path' => 'execution/05_motivation_engine'],
    6 => ['name' => 'SWOT 분석기', 'path' => 'execution/06_swot_analyzer'],
    7 => ['name' => '일일 사령부', 'path' => 'execution/07_daily_command'],
    8 => ['name' => '내면 브랜딩', 'path' => 'execution/08_inner_branding'],
    9 => ['name' => '수직 탐사기', 'path' => 'execution/09_vertical_explorer'],
    10 => ['name' => '자원 정원사', 'path' => 'execution/10_resource_gardener'],
    11 => ['name' => '실행 파이프라인', 'path' => 'execution/11_execution_pipeline'],
    12 => ['name' => '외부 브랜딩', 'path' => 'branding/12_external_branding'],
    13 => ['name' => '성장 트리거', 'path' => 'branding/13_growth_trigger'],
    14 => ['name' => '경쟁 생존 전략가', 'path' => 'branding/14_competitive_strategist'],
    15 => ['name' => '시간수정체 CEO', 'path' => 'knowledge_management/15_timecapsule_ceo'],
    16 => ['name' => 'AI 정원사', 'path' => 'knowledge_management/16_ai_gardener'],
    17 => ['name' => '신경망 설계사', 'path' => 'knowledge_management/17_neural_architect'],
    18 => ['name' => '정보 허브', 'path' => 'knowledge_management/18_info_hub'],
    19 => ['name' => '지식 연결망', 'path' => 'knowledge_management/19_knowledge_network'],
    20 => ['name' => '지식 수정체', 'path' => 'knowledge_management/20_knowledge_crystal'],
    21 => ['name' => '유연한 백본', 'path' => 'knowledge_management/21_flexible_backbone']
];

// AJAX 처리
if ($action === 'sync' && $agent_id > 0) {
    header('Content-Type: application/json');
    
    try {
        // WXSPERTA에서 현재 속성 가져오기
        $current_properties = $DB->get_record('wxsperta_agent_texts_current', ['card_id' => $agent_id]);
        
        if (!$current_properties) {
            throw new Exception('에이전트 속성을 찾을 수 없습니다.');
        }
        
        $properties = json_decode($current_properties->properties_json, true);
        
        // 프로젝트 데이터 업데이트
        $project_data = generateProjectFromProperties($agent_id, $properties);
        
        // 프로젝트 상태 저장
        $project_state = new stdClass();
        $project_state->agent_id = $agent_id;
        $project_state->project_data = json_encode($project_data);
        $project_state->last_sync = time();
        $project_state->synced_by = $USER->id;
        
        // DB에 저장 (테이블이 있다고 가정)
        $existing = $DB->get_record('wxsperta_project_states', ['agent_id' => $agent_id]);
        if ($existing) {
            $project_state->id = $existing->id;
            $DB->update_record('wxsperta_project_states', $project_state);
        } else {
            $DB->insert_record('wxsperta_project_states', $project_state);
        }
        
        echo json_encode([
            'success' => true,
            'message' => '동기화 완료',
            'data' => $project_data
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 속성 기반 프로젝트 생성
function generateProjectFromProperties($agent_id, $properties) {
    $project = [
        'meta' => [
            'agent_id' => $agent_id,
            'last_updated' => date('Y-m-d H:i:s'),
            'version' => '1.0'
        ],
        'stages' => []
    ];
    
    // 8-layer 속성을 3단계 프로젝트로 변환
    // Stage 1: 기초 (세계관, 문맥, 구조)
    $project['stages'][1] = [
        'title' => '프로젝트 기초 설정',
        'description' => '에이전트의 기본 철학과 구조 정립',
        'tasks' => [
            [
                'title' => '세계관 정립',
                'content' => $properties['worldView'] ?? '',
                'type' => 'research',
                'status' => !empty($properties['worldView']) ? 'completed' : 'pending'
            ],
            [
                'title' => '문맥 분석',
                'content' => $properties['context'] ?? '',
                'type' => 'analysis',
                'status' => !empty($properties['context']) ? 'completed' : 'pending'
            ],
            [
                'title' => '구조 설계',
                'content' => $properties['structure'] ?? '',
                'type' => 'design',
                'status' => !empty($properties['structure']) ? 'completed' : 'pending'
            ]
        ]
    ];
    
    // Stage 2: 실행 (절차, 실행, 성찰)
    $project['stages'][2] = [
        'title' => '프로젝트 실행',
        'description' => '구체적인 실행 계획과 진행',
        'tasks' => [
            [
                'title' => '절차 수립',
                'content' => $properties['process'] ?? '',
                'type' => 'planning',
                'status' => !empty($properties['process']) ? 'completed' : 'pending'
            ],
            [
                'title' => '실행 도구 개발',
                'content' => $properties['execution'] ?? '',
                'type' => 'development',
                'status' => !empty($properties['execution']) ? 'completed' : 'pending'
            ],
            [
                'title' => '성찰 시스템',
                'content' => $properties['reflection'] ?? '',
                'type' => 'evaluation',
                'status' => !empty($properties['reflection']) ? 'completed' : 'pending'
            ]
        ]
    ];
    
    // Stage 3: 확산 (전파, 추상화)
    $project['stages'][3] = [
        'title' => '프로젝트 확산',
        'description' => '성과 공유와 지식 추상화',
        'tasks' => [
            [
                'title' => '지식 전파',
                'content' => $properties['transfer'] ?? '',
                'type' => 'sharing',
                'status' => !empty($properties['transfer']) ? 'completed' : 'pending'
            ],
            [
                'title' => '핵심 추상화',
                'content' => $properties['abstraction'] ?? '',
                'type' => 'abstraction',
                'status' => !empty($properties['abstraction']) ? 'completed' : 'pending'
            ]
        ]
    ];
    
    // 전체 진행률 계산
    $total_tasks = 0;
    $completed_tasks = 0;
    foreach ($project['stages'] as $stage) {
        foreach ($stage['tasks'] as $task) {
            $total_tasks++;
            if ($task['status'] === 'completed') {
                $completed_tasks++;
            }
        }
    }
    $project['meta']['progress'] = round(($completed_tasks / $total_tasks) * 100);
    
    return $project;
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXSPERTA 에이전트 속성 동기화</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">🔄 WXSPERTA 속성 동기화 도구</h1>
            <p class="text-gray-600 mb-6">상위 WXSPERTA 시스템의 8-layer 속성을 각 에이전트 프로젝트와 동기화합니다.</p>
            
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">에이전트 선택</label>
                <select id="agentSelect" class="w-full p-2 border rounded-lg">
                    <option value="">에이전트를 선택하세요</option>
                    <?php foreach ($agents_info as $id => $info): ?>
                        <option value="<?php echo $id; ?>" <?php echo $agent_id == $id ? 'selected' : ''; ?>>
                            <?php echo $id . '. ' . $info['name']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button onclick="syncProperties()" class="bg-blue-500 text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                동기화 실행
            </button>
        </div>
        
        <?php if ($agent_id > 0): ?>
        <?php
        // 현재 속성 가져오기
        $current_properties = $DB->get_record('wxsperta_agent_texts_current', ['card_id' => $agent_id]);
        $properties = $current_properties ? json_decode($current_properties->properties_json, true) : [];
        ?>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- 현재 속성 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">📝 현재 WXSPERTA 속성</h2>
                <div class="space-y-4">
                    <?php
                    $property_labels = [
                        'worldView' => '세계관',
                        'context' => '문맥',
                        'structure' => '구조',
                        'process' => '절차',
                        'execution' => '실행',
                        'reflection' => '성찰',
                        'transfer' => '전파',
                        'abstraction' => '추상화'
                    ];
                    
                    foreach ($property_labels as $key => $label):
                        $value = $properties[$key] ?? '';
                        $has_value = !empty($value);
                    ?>
                    <div class="border-l-4 <?php echo $has_value ? 'border-green-500' : 'border-gray-300'; ?> pl-4">
                        <h3 class="font-medium text-gray-700"><?php echo $label; ?></h3>
                        <p class="text-sm text-gray-600 mt-1">
                            <?php echo $has_value ? htmlspecialchars(substr($value, 0, 100)) . '...' : '(미설정)'; ?>
                        </p>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 진행률 표시 -->
                <?php
                $filled_count = count(array_filter($properties, function($v) { return !empty($v); }));
                $total_count = count($property_labels);
                $progress = round(($filled_count / $total_count) * 100);
                ?>
                <div class="mt-6">
                    <div class="flex justify-between text-sm text-gray-600 mb-2">
                        <span>속성 완성도</span>
                        <span><?php echo $progress; ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo $progress; ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- 프로젝트 미리보기 -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-semibold mb-4">📊 프로젝트 변환 미리보기</h2>
                <div id="projectPreview" class="space-y-4">
                    <?php if ($agent_id > 0): ?>
                    <?php $project_data = generateProjectFromProperties($agent_id, $properties); ?>
                    
                    <!-- 프로젝트 메타 정보 -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-sm text-gray-600">
                            <span class="font-medium">에이전트:</span> <?php echo $agents_info[$agent_id]['name']; ?><br>
                            <span class="font-medium">전체 진행률:</span> <?php echo $project_data['meta']['progress']; ?>%
                        </p>
                    </div>
                    
                    <!-- 스테이지별 표시 -->
                    <?php foreach ($project_data['stages'] as $stage_num => $stage): ?>
                    <div class="border rounded-lg p-4">
                        <h3 class="font-medium text-gray-700 mb-2">
                            Stage <?php echo $stage_num; ?>: <?php echo $stage['title']; ?>
                        </h3>
                        <p class="text-sm text-gray-600 mb-3"><?php echo $stage['description']; ?></p>
                        
                        <div class="space-y-2">
                            <?php foreach ($stage['tasks'] as $task): ?>
                            <div class="flex items-center text-sm">
                                <span class="w-4 h-4 mr-2">
                                    <?php if ($task['status'] === 'completed'): ?>
                                        ✅
                                    <?php else: ?>
                                        ⬜
                                    <?php endif; ?>
                                </span>
                                <span class="<?php echo $task['status'] === 'completed' ? 'text-green-600' : 'text-gray-500'; ?>">
                                    <?php echo $task['title']; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php else: ?>
                    <p class="text-gray-500">에이전트를 선택하면 프로젝트 미리보기가 표시됩니다.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- 동기화 결과 -->
        <div id="syncResult" class="mt-6 hidden">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <h3 class="font-medium text-green-800 mb-2">✅ 동기화 완료</h3>
                <p class="text-sm text-green-700" id="syncMessage"></p>
            </div>
        </div>
    </div>

    <script>
        // 에이전트 선택 시 페이지 새로고침
        document.getElementById('agentSelect').addEventListener('change', function() {
            const agentId = this.value;
            if (agentId) {
                window.location.href = `?agent_id=${agentId}`;
            } else {
                window.location.href = window.location.pathname;
            }
        });
        
        // 동기화 실행
        async function syncProperties() {
            const agentId = document.getElementById('agentSelect').value;
            
            if (!agentId) {
                alert('에이전트를 선택해주세요.');
                return;
            }
            
            try {
                const response = await fetch(`?action=sync&agent_id=${agentId}`);
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('syncResult').classList.remove('hidden');
                    document.getElementById('syncMessage').textContent = 
                        `${result.data.meta.agent_id}번 에이전트의 속성이 프로젝트로 동기화되었습니다. (진행률: ${result.data.meta.progress}%)`;
                    
                    // 3초 후 페이지 새로고침
                    setTimeout(() => {
                        window.location.reload();
                    }, 3000);
                } else {
                    alert('동기화 실패: ' + result.error);
                }
            } catch (error) {
                alert('네트워크 오류: ' + error.message);
            }
        }
    </script>
</body>
</html>