<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php");
global $DB,$USER;
require_login();

$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
$student_id = isset($_GET["student_id"]) ? $_GET["student_id"] : $userid;
$teacher_id = $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student';

// 데이터베이스에서 에이전트 정보 가져오기
try {
    $tables = $DB->get_tables();
    $wxsperta_tables_exist = false;
    
    foreach ($tables as $table) {
        if (strpos($table, 'wxsperta_') !== false) {
            $wxsperta_tables_exist = true;
            break;
        }
    }
    
    if ($wxsperta_tables_exist) {
        $agents = $DB->get_records('wxsperta_agents', [], 'id ASC');
        $agent_priorities = $DB->get_records('wxsperta_agent_priorities', ['user_id' => $userid]);
        $user_profile = $DB->get_record('wxsperta_user_profiles', ['user_id' => $userid]);
    } else {
        $agents = [];
        $agent_priorities = [];
        $user_profile = null;
    }
} catch (Exception $e) {
    $agents = [];
    $agent_priorities = [];
    $user_profile = null;
    error_log("WXsperta DB Error: " . $e->getMessage());
}

// 에이전트 속성 정의
$agent_properties = [
    1 => [
        'worldView' => '미래의 비전과 목표가 현재의 선택을 이끈다.',
        'context' => '개인의 미래 목표와 현재 활동을 연결하는 시스템이 필요하다.',
        'structure' => '비전 보드와 타임라인을 통합한 목표 관리 시스템을 구축한다.',
        'process' => '1) 미래 비전 설정 2) 역산 계획 수립 3) 일일 실행 4) 주간 검토',
        'execution' => '매일 아침 비전 확인, 우선순위 설정, 실행 추적',
        'reflection' => '주간 회고를 통해 진행 상황을 평가하고 조정한다.',
        'transfer' => '성공 사례를 문서화하고 커뮤니티에 공유한다.',
        'abstraction' => '명확한 미래 비전이 현재의 동력이 된다.'
    ],
    2 => [
        'worldView' => '시간은 선형이 아닌 다층적 구조로 이해되어야 한다.',
        'context' => '과거의 경험과 미래의 목표를 현재에 통합한다.',
        'structure' => '다차원 타임라인으로 과거-현재-미래를 시각화한다.',
        'process' => '1) 과거 분석 2) 현재 매핑 3) 미래 설계 4) 통합 실행',
        'execution' => '타임라인 대시보드 운영, 시간 블록 관리',
        'reflection' => '시간 사용 패턴을 분석하여 최적화한다.',
        'transfer' => '효과적인 시간 관리 템플릿을 제공한다.',
        'abstraction' => '시간의 다층적 이해가 효율성을 극대화한다.'
    ],
    // ... 나머지 에이전트들
];

// 에이전트에 속성 추가
$agents_with_properties = [];
foreach ($agents as $agent) {
    $agent_obj = (object)[
        'id' => $agent->id,
        'name' => $agent->name,
        'description' => $agent->description,
        'icon' => $agent->icon ?? '🤖',
        'color' => $agent->color ?? 'from-blue-500 to-blue-600',
        'worldView' => $agent_properties[$agent->id]['worldView'] ?? '',
        'context' => $agent_properties[$agent->id]['context'] ?? '',
        'structure' => $agent_properties[$agent->id]['structure'] ?? '',
        'process' => $agent_properties[$agent->id]['process'] ?? '',
        'execution' => $agent_properties[$agent->id]['execution'] ?? '',
        'reflection' => $agent_properties[$agent->id]['reflection'] ?? '',
        'transfer' => $agent_properties[$agent->id]['transfer'] ?? '',
        'abstraction' => $agent_properties[$agent->id]['abstraction'] ?? ''
    ];
    $agents_with_properties[] = $agent_obj;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXsperta AI 에이전트 매트릭스</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .agent-card {
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .agent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .chat-icon {
            position: absolute;
            bottom: 8px;
            right: 8px;
            width: 32px;
            height: 32px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .chat-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .chat-panel {
            position: fixed;
            right: -400px;
            top: 0;
            width: 400px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }
        
        .chat-panel.open {
            right: 0;
        }
        
        .main-container {
            transition: all 0.3s ease;
        }
        
        .main-container.shifted {
            margin-right: 400px;
            transform: translateX(-200px);
        }
        
        .property-item {
            padding: 12px;
            border-radius: 8px;
            background: #f9fafb;
            margin-bottom: 8px;
        }
        
        .property-title {
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .property-content {
            color: #6b7280;
            font-size: 14px;
        }
        
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            margin: 20px;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">
    <div id="mainContainer" class="main-container p-4">
        <div class="max-w-7xl mx-auto">
            <h1 class="text-3xl font-bold text-center mb-8 text-gray-800">
                AI 에이전트 매트릭스
            </h1>
            
            <!-- 8층 구조 설명 -->
            <div class="mb-6 text-center">
                <p class="text-sm text-gray-600">
                    세계관 → 문맥 → 구조 → 절차 → 실행 → 성찰 → 전파 → 추상화
                </p>
            </div>
            
            <!-- 에이전트 그리드 -->
            <div id="agentGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                <?php foreach ($agents_with_properties as $agent): ?>
                <div class="agent-card bg-white rounded-lg p-4 shadow-md" 
                     data-agent-id="<?php echo $agent->id; ?>"
                     onclick="showAgentDetails(<?php echo htmlspecialchars(json_encode($agent)); ?>)">
                    <div class="flex items-center mb-3">
                        <span class="text-2xl mr-3"><?php echo $agent->icon; ?></span>
                        <div>
                            <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($agent->name); ?></h3>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars($agent->description); ?></p>
                    
                    <!-- 채팅 아이콘 -->
                    <div class="chat-icon" onclick="event.stopPropagation(); openChat(<?php echo htmlspecialchars(json_encode($agent)); ?>)">
                        💬
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- 역할 표시 -->
            <div class="mt-6 text-center text-sm text-gray-500">
                현재 모드: <?php echo $role === 'teacher' ? '교사 (편집 모드)' : '학생 (대화 모드)'; ?>
            </div>
        </div>
    </div>
    
    <!-- 채팅 패널 -->
    <div id="chatPanel" class="chat-panel">
        <div class="chat-header bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 flex items-center">
            <span id="chatAgentIcon" class="text-2xl mr-3">🤖</span>
            <div class="flex-1">
                <h3 id="chatAgentName" class="font-bold">에이전트</h3>
                <p id="chatAgentDesc" class="text-sm opacity-90">설명</p>
            </div>
            <button onclick="toggleProjectView()" class="p-2 hover:bg-white/20 rounded-lg mr-2" title="프로젝트 보기">
                📋
            </button>
            <button onclick="closeChat()" class="p-2 hover:bg-white/20 rounded-lg">
                ✕
            </button>
        </div>
        
        <!-- 채팅 영역 -->
        <div id="chatContent" class="flex-1 overflow-y-auto p-4">
            <div id="messageContainer" class="space-y-3"></div>
        </div>
        
        <!-- 프로젝트 뷰 -->
        <div id="projectView" class="flex-1 overflow-y-auto p-4" style="display: none;">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">프로젝트 트리</h3>
                <div class="flex gap-2">
                    <button onclick="createNewProject()" class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                        새 프로젝트
                    </button>
                    <button onclick="toggleProjectView()" class="px-3 py-1 border rounded hover:bg-gray-50">
                        채팅으로
                    </button>
                </div>
            </div>
            <div id="projectTree"></div>
        </div>
        
        <!-- 입력 영역 -->
        <div class="border-t p-4">
            <div class="flex gap-2">
                <textarea id="messageInput" 
                    placeholder="메시지를 입력하세요..." 
                    class="flex-1 resize-none border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
                    rows="2"
                    onkeypress="handleKeyPress(event)"></textarea>
                <button onclick="sendMessage()" 
                    class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                    전송
                </button>
            </div>
        </div>
    </div>
    
    <!-- 모달 (에이전트 상세) -->
    <div id="agentModal" class="modal-overlay" onclick="closeModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div id="modalContent"></div>
            <button onclick="closeModal()" class="mt-4 px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                닫기
            </button>
        </div>
    </div>
    
    <script>
        // 전역 변수
        let currentAgent = null;
        let showingProjects = false;
        let projects = [];
        
        // PHP 데이터
        const phpData = {
            userId: <?php echo $userid; ?>,
            role: '<?php echo $role; ?>',
            apiUrl: '<?php echo WXSPERTA_BASE_URL; ?>api.php'
        };
        
        // 채팅 열기
        function openChat(agent) {
            currentAgent = agent;
            document.getElementById('chatAgentIcon').textContent = agent.icon;
            document.getElementById('chatAgentName').textContent = agent.name;
            document.getElementById('chatAgentDesc').textContent = agent.description;
            
            // 채팅 패널 열기
            document.getElementById('chatPanel').classList.add('open');
            document.getElementById('mainContainer').classList.add('shifted');
            
            // 초기 메시지
            addMessage('agent', `안녕하세요! ${agent.name}입니다. 무엇을 도와드릴까요?`);
            
            // 프로젝트 로드
            loadProjects(agent.id);
        }
        
        // 채팅 닫기
        function closeChat() {
            document.getElementById('chatPanel').classList.remove('open');
            document.getElementById('mainContainer').classList.remove('shifted');
            currentAgent = null;
            
            // 메시지 초기화
            document.getElementById('messageContainer').innerHTML = '';
            document.getElementById('messageInput').value = '';
        }
        
        // 메시지 추가
        function addMessage(type, content, action = null) {
            const container = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `flex ${type === 'user' ? 'justify-end' : 'justify-start'}`;
            
            const bubbleDiv = document.createElement('div');
            bubbleDiv.className = `max-w-[80%] rounded-lg p-3 ${
                type === 'user' ? 'bg-blue-500 text-white' : 
                type === 'system' ? 'bg-amber-50 border border-amber-200' :
                'bg-gray-100'
            }`;
            
            bubbleDiv.innerHTML = `
                <p class="whitespace-pre-wrap">${content}</p>
                ${action ? `
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <button onclick="${action.onclick}" class="px-3 py-1 ${action.class} rounded text-sm">
                            ${action.text}
                        </button>
                    </div>
                ` : ''}
                <p class="text-xs mt-1 ${
                    type === 'user' ? 'text-blue-100' : 
                    type === 'system' ? 'text-amber-600' :
                    'text-gray-500'
                }">
                    ${new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' })}
                </p>
            `;
            
            messageDiv.appendChild(bubbleDiv);
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }
        
        // 메시지 전송
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message || !currentAgent) return;
            
            // 사용자 메시지 추가
            addMessage('user', message);
            input.value = '';
            
            // 로딩 표시
            showLoading();
            
            try {
                // Chat Bridge로 메시지 전송
                const formData = new FormData();
                formData.append('action', 'process_message');
                formData.append('message', message);
                formData.append('user_id', phpData.userId);
                formData.append('agent_id', currentAgent.id);
                formData.append('page_type', 'wxsperta');
                formData.append('context', JSON.stringify({
                    agent_properties: currentAgent,
                    projects: projects
                }));
                
                const response = await fetch('/studenthome/wxsperta/chat_bridge.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                hideLoading();
                
                if (result.success) {
                    // AI 응답 추가
                    addMessage('agent', result.response);
                    
                    // 업데이트 제안 확인
                    if (result.insights && result.insights.needs_update) {
                        checkForUpdates(message, result.response);
                    }
                }
            } catch (error) {
                hideLoading();
                addMessage('system', '메시지 전송에 실패했습니다.');
                console.error('Error:', error);
            }
        }
        
        // 업데이트 확인
        async function checkForUpdates(userInput, aiResponse) {
            try {
                const response = await fetch('/studenthome/wxsperta/analyze_update.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        agent_id: currentAgent.id,
                        user_input: userInput,
                        ai_response: aiResponse,
                        current_properties: currentAgent
                    })
                });
                
                const result = await response.json();
                
                if (result.suggested_updates && Object.keys(result.suggested_updates).length > 0) {
                    addMessage('system', '💡 대화 내용을 바탕으로 WXSPERTA 속성 업데이트를 제안합니다.', {
                        text: '업데이트 적용',
                        class: 'bg-blue-500 text-white hover:bg-blue-600',
                        onclick: `applyUpdate('${JSON.stringify(result.suggested_updates).replace(/'/g, "\\'")}')`
                    });
                }
            } catch (error) {
                console.error('Update check error:', error);
            }
        }
        
        // 업데이트 적용
        async function applyUpdate(updatesJson) {
            const updates = JSON.parse(updatesJson);
            // TODO: 승인 시스템 연동
            addMessage('system', '✅ 업데이트 요청이 전송되었습니다. 승인 대기 중입니다.');
        }
        
        // 프로젝트 토글
        function toggleProjectView() {
            showingProjects = !showingProjects;
            document.getElementById('chatContent').style.display = showingProjects ? 'none' : 'flex';
            document.getElementById('projectView').style.display = showingProjects ? 'block' : 'none';
        }
        
        // 프로젝트 로드
        async function loadProjects(agentId) {
            try {
                const response = await fetch(`/studenthome/wxsperta/project_api.php?action=get_agent_projects&agent_id=${agentId}`);
                const result = await response.json();
                
                if (result.success) {
                    projects = result.projects || [];
                    renderProjectTree();
                }
            } catch (error) {
                console.error('Failed to load projects:', error);
            }
        }
        
        // 프로젝트 트리 렌더링
        function renderProjectTree() {
            const container = document.getElementById('projectTree');
            
            if (projects.length === 0) {
                container.innerHTML = '<p class="text-gray-500 text-center py-8">아직 프로젝트가 없습니다.</p>';
                return;
            }
            
            container.innerHTML = renderProjectNodes(null, 0);
        }
        
        // 재귀적 프로젝트 노드 렌더링
        function renderProjectNodes(parentId, depth) {
            const childProjects = projects.filter(p => p.parent_project_id == parentId);
            
            return childProjects.map(project => `
                <div style="margin-left: ${depth * 20}px">
                    <div class="p-3 mb-2 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="font-medium">${project.title}</h4>
                                <p class="text-sm text-gray-600">${project.description}</p>
                                <span class="text-xs px-2 py-1 rounded ${
                                    project.status === 'completed' ? 'bg-green-100 text-green-700' :
                                    project.status === 'active' ? 'bg-blue-100 text-blue-700' :
                                    'bg-gray-100 text-gray-700'
                                }">
                                    ${project.status}
                                </span>
                            </div>
                            <button onclick="createNewProject(${project.id})" 
                                class="p-2 hover:bg-gray-100 rounded"
                                title="하위 프로젝트 생성">
                                ➕
                            </button>
                        </div>
                    </div>
                    ${renderProjectNodes(project.id, depth + 1)}
                </div>
            `).join('');
        }
        
        // 새 프로젝트 생성
        function createNewProject(parentId = null) {
            // TODO: 프로젝트 생성 폼 모달 표시
            alert('프로젝트 생성 기능 준비 중');
        }
        
        // 에이전트 상세 보기
        function showAgentDetails(agent) {
            const modalContent = document.getElementById('modalContent');
            
            const properties = [
                { key: 'worldView', title: '세계관' },
                { key: 'context', title: '문맥' },
                { key: 'structure', title: '구조' },
                { key: 'process', title: '절차' },
                { key: 'execution', title: '실행' },
                { key: 'reflection', title: '성찰' },
                { key: 'transfer', title: '전파' },
                { key: 'abstraction', title: '추상화' }
            ];
            
            modalContent.innerHTML = `
                <div class="flex items-center mb-4">
                    <span class="text-3xl mr-3">${agent.icon}</span>
                    <div>
                        <h2 class="text-2xl font-bold">${agent.name}</h2>
                        <p class="text-gray-600">${agent.description}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    ${properties.map(prop => `
                        <div class="property-item">
                            <div class="property-title">${prop.title}</div>
                            <div class="property-content">${agent[prop.key] || '정의되지 않음'}</div>
                        </div>
                    `).join('')}
                </div>
            `;
            
            document.getElementById('agentModal').style.display = 'flex';
        }
        
        // 모달 닫기
        function closeModal(event) {
            if (!event || event.target.id === 'agentModal') {
                document.getElementById('agentModal').style.display = 'none';
            }
        }
        
        // 키보드 이벤트
        function handleKeyPress(event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendMessage();
            }
        }
        
        // 로딩 표시
        function showLoading() {
            const loader = document.createElement('div');
            loader.id = 'loadingIndicator';
            loader.className = 'flex justify-start';
            loader.innerHTML = `
                <div class="bg-gray-100 rounded-lg p-3">
                    <div class="flex space-x-2">
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                        <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                    </div>
                </div>
            `;
            document.getElementById('messageContainer').appendChild(loader);
        }
        
        // 로딩 숨김
        function hideLoading() {
            const loader = document.getElementById('loadingIndicator');
            if (loader) loader.remove();
        }
        
        // 반응형 그리드 조정
        function adjustGrid() {
            const grid = document.getElementById('agentGrid');
            const chatOpen = document.getElementById('chatPanel').classList.contains('open');
            
            if (chatOpen) {
                grid.classList.remove('lg:grid-cols-4');
                grid.classList.add('lg:grid-cols-3');
            } else {
                grid.classList.remove('lg:grid-cols-3');
                grid.classList.add('lg:grid-cols-4');
            }
        }
        
        // 윈도우 리사이즈 이벤트
        window.addEventListener('resize', adjustGrid);
    </script>
</body>
</html>