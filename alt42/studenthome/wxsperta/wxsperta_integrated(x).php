<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php"); // OpenAI API 설정 포함
require_once("ai_agents/cards_data.php"); // 공통 카드 데이터
global $DB,$USER;
require_login();

// GET 파라미터에서 userid 가져오기, 없으면 현재 로그인한 사용자 ID 사용
$userid = isset($_GET["userid"]) ? $_GET["userid"] : $USER->id;
$student_id = isset($_GET["student_id"]) ? $_GET["student_id"] : $userid;
$teacher_id = $USER->id;

// 사용자 역할 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : 'student'; // 기본값은 student

// 데이터베이스에서 에이전트 정보 가져오기
try {
    // 테이블 존재 여부 확인
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
        // 테이블이 없으면 빈 배열
        $agents = [];
        $agent_priorities = [];
        $user_profile = null;
    }
} catch (Exception $e) {
    // 오류 발생 시 빈 배열
    $agents = [];
    $agent_priorities = [];
    $user_profile = null;
    error_log("WXsperta DB Error: " . $e->getMessage());
}

// cards_data.php의 데이터를 사용
if (empty($agents)) {
    $agents = array_map(function($card) {
        return [
            'id' => $card['id'],
            'name' => $card['name'],
            'icon' => $card['icon'] ?? '🎯',
            'color' => $card['color'] ?? 'from-blue-500 to-purple-500',
            'category' => $card['category'],
            'description' => $card['description'],
            'shortDesc' => $card['subtitle']
        ];
    }, $cards_data);
}

// 카테고리별 경로 매핑
$category_paths = [
    'future' => 'future_design',
    'future_design' => 'future_design',
    'execution' => 'execution',
    'branding' => 'branding',
    'knowledge' => 'knowledge_management',
    'knowledge_management' => 'knowledge_management'
];

// JSON으로 변환
$agents_json = json_encode(array_values($agents));
$priorities_json = json_encode(array_values($agent_priorities));
$profile_json = json_encode($user_profile ?: new stdClass());
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WXsperta AI 에이전트 매트릭스</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* 3열 그리드 스타일 */
        #agentGrid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            #agentGrid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
        }
        
        @media (max-width: 480px) {
            #agentGrid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }
        
        /* 추가 스타일 */
        .agent-card {
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
            min-height: 120px;
        }
        .agent-card:hover {
            transform: translateY(-4px);
        }
        .agent-card.highlighted {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            50% { transform: scale(1.05); box-shadow: 0 8px 12px rgba(59, 130, 246, 0.5); }
            100% { transform: scale(1); box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
        }
        @keyframes bounce {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
        }
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 40;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 1rem;
            width: 100%;
            max-width: 42rem;
            max-height: 90vh;
            margin: 2rem;
            display: flex;
            flex-direction: column;
            animation: modalAppear 0.3s ease;
        }
        @keyframes modalAppear {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .chat-panel {
            position: fixed;
            right: 0;
            top: 0;
            width: 25vw; /* 전체 폭의 1/4 */
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            z-index: 50;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 1024px) {
            .chat-panel {
                width: 40vw;
            }
        }
        @media (max-width: 768px) {
            .chat-panel {
                width: 100vw;
            }
        }
        .chat-panel.open {
            transform: translateX(0);
        }
        .main-container {
            transition: all 0.3s ease;
        }
        .main-container.shifted {
            margin-right: 25vw;
        }
    </style>
</head>
<body>
    <div id="mainContainer" class="main-container">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 p-4">
            <div class="max-w-7xl mx-auto px-4">
                <!-- 통합 네비게이션 -->
                <nav class="mb-6 flex justify-center gap-4">
                    <a href="ai_agents/index.php" class="px-4 py-2 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                        📊 프로젝트 시스템
                    </a>
                    <span class="px-4 py-2 bg-blue-500 text-white rounded-lg shadow">
                        💬 AI 대화 매트릭스
                    </span>
                </nav>

                <h1 class="text-2xl font-bold text-center mb-6 text-gray-800">
                    AI 에이전트 매트릭스
                </h1>
            
                <!-- 8층 구조 설명 - minimal -->
                <div class="mb-4 text-center">
                    <p class="text-xs text-gray-500 leading-relaxed">
                        <span class="font-medium">8단계 층구조</span><br/>
                        세계관 → 문맥 → 구조 → 절차 → 실행 → 성찰 → 전파 → 추상화
                    </p>
                </div>
                
                <!-- 3열 그리드 -->
                <div id="agentGrid" class="mb-8">
                    <!-- 에이전트 카드들이 여기에 동적으로 추가됩니다 -->
                </div>

                <!-- Category Legend -->
                <div class="mt-6 flex justify-center gap-4 text-xs text-gray-600">
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-purple-400"></div>
                        <span>미래설계</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-blue-400"></div>
                        <span>실행</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-pink-400"></div>
                        <span>브랜딩</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <div class="w-3 h-3 rounded-full bg-green-400"></div>
                        <span>지식관리</span>
                    </div>
                </div>
                
                <!-- 역할 표시 -->
                <div class="mt-4 text-center text-sm text-gray-500">
                    현재 모드: <?php echo $role === 'teacher' ? '교사 (편집 모드)' : '학생 (대화 모드)'; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Overlay & Popup -->
    <div id="modalOverlay" class="modal-overlay" onclick="handleCancel()">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div id="modalBody">
                <!-- 모달 내용이 여기에 동적으로 추가됩니다 -->
            </div>
        </div>
    </div>

    <!-- 채팅 패널 -->
    <div id="chatPanel" class="chat-panel">
        <!-- 채팅 내용이 여기에 동적으로 추가됩니다 -->
    </div>

    <script>
        // PHP 데이터를 JavaScript로 전달
        const phpData = {
            agents: <?php echo $agents_json; ?>,
            priorities: <?php echo $priorities_json; ?>,
            userProfile: <?php echo $profile_json; ?>,
            userId: <?php echo $userid; ?>,
            role: '<?php echo $role; ?>',
            apiUrl: '<?php echo WXSPERTA_BASE_URL; ?>api.php',
            categoryPaths: <?php echo json_encode($category_paths); ?>
        };

        // 전역 상태 관리
        const state = {
            selectedAgent: null,
            hoveredAgent: null,
            agentProperties: {},
            loading: false,
            message: '',
            showChat: false,
            chatAgent: null,
            matrixOffset: 0,
            agents: [],
            currentView: 'properties',
            previousProperties: {},
            activeAgentCard: null,
            chatHistory: [],
            recommendedCards: []
        };

        // 기본 속성값
        const defaultProperties = {
            1: { 
                worldView: "미래의 나는 현재의 선택으로 만들어진다. 시간은 선형이 아닌 결정의 연속체이다.",
                context: "학생의 현재 상황과 미래 목표 사이의 간극을 인식하고 연결점을 찾는다.",
                structure: "과거-현재-미래의 타임라인을 시각화하고 각 시점의 자아를 구체화한다.",
                process: "1) 미래 목표 설정 2) 현재 상태 분석 3) 갭 분석 4) 연결 경로 도출",
                execution: "주기적인 미래 자아 편지 작성, 시각화 보드 제작, 일일 미래 연결점 찾기",
                reflection: "목표 달성도를 측정하고 미래 비전의 현실성을 지속적으로 검증한다.",
                transfer: "성공 스토리를 문서화하고 다른 학생들과 공유할 수 있는 템플릿으로 변환한다.",
                abstraction: "시간을 통한 자아 실현과 성장의 본질을 추출한다."
            },
            2: { 
                worldView: "모든 큰 성취는 작은 단계들의 체계적인 연결에서 시작된다.",
                context: "복잡한 목표를 달성 가능한 단위로 분해하고 시간축에 배치한다.",
                structure: "간트 차트와 마일스톤을 활용한 프로젝트 관리 체계를 구축한다.",
                process: "1) 목표 분해 2) 시간 할당 3) 의존성 분석 4) 버퍼 설정 5) 추적 시스템 구축",
                execution: "주간/월간 계획 수립, 진행상황 시각화, 자동 리마인더 설정",
                reflection: "계획 대비 실행률을 분석하고 병목 구간을 식별하여 개선한다.",
                transfer: "효과적인 계획 수립 노하우를 템플릿화하여 공유한다.",
                abstraction: "시간 관리의 핵심은 우선순위와 실행의 균형이다."
            },
            3: { 
                worldView: "성장은 계단이 아닌 엘리베이터처럼 가속할 수 있다.",
                context: "현재의 성장 속도와 패턴을 분석하여 가속 포인트를 찾는다.",
                structure: "성장 지표를 다차원으로 측정하고 상관관계를 분석한다.",
                process: "1) 성장 지표 정의 2) 데이터 수집 3) 패턴 분석 4) 가속 전략 도출",
                execution: "일일 성장 로그 작성, 주간 성장 그래프 분석, 월간 전략 조정",
                reflection: "성장 궤적을 분석하고 정체 구간의 원인을 파악한다.",
                transfer: "성장 패턴과 돌파 전략을 케이스 스터디로 정리한다.",
                abstraction: "지속가능한 성장의 핵심은 복리 효과를 만드는 것이다."
            }
        };

        const propertyLabels = {
            worldView: { title: '세계관', desc: '미션의 기본 철학과 이상적 성과를 정의합니다.' },
            context: { title: '문맥', desc: '미션이 운영되는 환경과 조건을 인식합니다.' },
            structure: { title: '구조', desc: '미션 수행을 위한 구조적 설계를 담당합니다.' },
            process: { title: '절차', desc: '미션 실행의 단계별 프로세스를 정의합니다.' },
            execution: { title: '실행', desc: '미션 달성을 위한 구체적 실행 방식을 설계합니다.' },
            reflection: { title: '성찰', desc: '미션 성과 평가와 개선 전략을 관리합니다.' },
            transfer: { title: '전파', desc: '미션 수행의 경험과 학습을 전파합니다.' },
            abstraction: { title: '추상화', desc: '미션의 핵심 목표와 가치를 추상화합니다.' }
        };

        // 에이전트별 층 할당 (1-21번 에이전트를 8개 층에 매핑)
        const agentLayerMapping = {
            1: '세계관', 2: '세계관', 3: '세계관',
            4: '문맥', 5: '문맥', 6: '문맥',
            7: '구조', 8: '구조', 9: '구조',
            10: '절차', 11: '절차', 12: '절차',
            13: '실행', 14: '실행', 15: '실행',
            16: '성찰', 17: '성찰', 18: '성찰',
            19: '전파', 20: '전파',
            21: '추상화'
        };

        const categoryColors = {
            future: 'border-purple-200',
            execution: 'border-blue-200',
            branding: 'border-pink-200',
            knowledge: 'border-green-200'
        };

        // 초기화
        function init() {
            // 에이전트 데이터 준비
            if (phpData.agents.length > 0) {
                state.agents = phpData.agents.map(agent => ({
                    ...agent,
                    id: parseInt(agent.id),
                    icon: agent.icon || '🎯',
                    shortDesc: agent.shortDesc || agent.short_desc || agent.description
                }));
            }

            // 에이전트 그리드 렌더링
            renderAgentGrid();

            // ESC 키 이벤트
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    if (state.selectedAgent) {
                        handleCancel();
                    }
                    if (state.showChat) {
                        handleChatClose();
                    }
                }
            });
        }

        // 에이전트 그리드 렌더링
        function renderAgentGrid() {
            const grid = document.getElementById('agentGrid');
            grid.innerHTML = '';

            state.agents.forEach(agent => {
                const card = createAgentCard(agent);
                grid.appendChild(card);
            });
        }

        // 에이전트 카드 생성
        function createAgentCard(agent) {
            const div = document.createElement('div');
            div.className = 'relative';
            div.onmouseenter = () => state.hoveredAgent = agent.id;
            div.onmouseleave = () => state.hoveredAgent = null;

            const button = document.createElement('button');
            button.id = `agent-card-${agent.id}`;
            button.className = `agent-card w-full aspect-[4/3] rounded-2xl transition-all duration-300 transform ${
                state.hoveredAgent === agent.id || state.selectedAgent?.id === agent.id ? 'scale-105' : 'scale-100'
            } ${state.selectedAgent?.id === agent.id ? 'ring-4 ring-offset-2 ring-blue-400' : ''} ${
                state.recommendedCards.includes(agent.id) ? 'ring-2 ring-green-400 animate-pulse' : ''
            }`;
            button.onclick = () => handleAgentClick(agent);

            const cardContent = document.createElement('div');
            cardContent.className = `w-full h-full rounded-2xl bg-gradient-to-br ${agent.color} 
                flex flex-col items-center justify-center p-3 shadow-lg hover:shadow-xl 
                transition-shadow duration-300 ${state.hoveredAgent === agent.id ? 'animate-pulse' : ''} relative`;
            
            // 층 레이블 추가
            const layerLabel = agentLayerMapping[agent.id] || '';
            
            cardContent.innerHTML = `
                ${layerLabel ? `<span class="absolute top-2 right-2 text-xs font-bold text-white bg-black bg-opacity-30 px-2 py-1 rounded">${layerLabel}</span>` : ''}
                <span class="text-5xl mb-2">${agent.icon}</span>
                <span class="text-sm font-medium text-white text-center leading-tight px-2">
                    ${agent.shortDesc}
                </span>
            `;

            button.appendChild(cardContent);

            // 채팅 아이콘 (우측 하단)
            const chatButton = document.createElement('button');
            chatButton.className = 'absolute bottom-2 right-2 w-6 h-6 bg-white rounded-full shadow-md flex items-center justify-center hover:scale-110 transition-transform text-sm';
            chatButton.title = '채팅 시작';
            chatButton.innerHTML = '💬';
            chatButton.onclick = (e) => {
                e.stopPropagation();
                openChat(agent);
            };

            // 프로젝트 아이콘 (좌측 하단) - ai_agents로 리다이렉트
            const projectButton = document.createElement('button');
            projectButton.className = 'absolute bottom-2 left-2 w-6 h-6 bg-white rounded-full shadow-md flex items-center justify-center hover:scale-110 transition-transform text-sm';
            projectButton.title = '프로젝트 보기';
            projectButton.innerHTML = '📁';
            projectButton.onclick = (e) => {
                e.stopPropagation();
                openProjectInNewSystem(agent);
            };

            div.appendChild(button);
            div.appendChild(chatButton);
            div.appendChild(projectButton);

            // Hover tooltip
            if (state.hoveredAgent === agent.id) {
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute z-10 -top-2 left-1/2 transform -translate-x-1/2 -translate-y-full';
                tooltip.innerHTML = `
                    <div class="bg-gray-800 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap">
                        ${agent.name}
                        <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-full">
                            <div class="w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800"></div>
                        </div>
                    </div>
                `;
                div.appendChild(tooltip);
            }

            return div;
        }

        // 프로젝트를 iframe 팝업으로 열기
        function openProjectInNewSystem(agent) {
            const categoryPath = phpData.categoryPaths[agent.category] || agent.category;
            const agentId = String(agent.id).padStart(2, '0');
            const agentName = agent.name.toLowerCase()
                .replace(/\s+/g, '_')
                .replace(/[^a-z0-9_]/g, '');
            
            // 에이전트 ID와 이름 매핑
            const agentNameMap = {
                1: '01_time_capsule',
                2: '02_timeline_synthesizer',
                3: '03_growth_elevator',
                4: '04_performance_engine',
                5: '05_motivation_engine',
                6: '06_swot_analyzer',
                7: '07_daily_command',
                8: '08_inner_branding',
                9: '09_vertical_explorer',
                10: '10_resource_gardener',
                11: '11_execution_pipeline',
                12: '12_external_branding',
                13: '13_growth_trigger',
                14: '14_competitive_strategist',
                15: '15_timecapsule_ceo',
                16: '16_ai_gardener',
                17: '17_neural_architect',
                18: '18_info_hub',
                19: '19_knowledge_network',
                20: '20_knowledge_crystal',
                21: '21_flexible_backbone'
            };
            
            const agentFolder = agentNameMap[agent.id];
            const url = `ai_agents/${categoryPath}/${agentFolder}/index.php`;
            
            // iframe 팝업 표시
            showProjectPopup(url, agent.name);
        }
        
        // 프로젝트 iframe 팝업 표시
        function showProjectPopup(url, agentName) {
            // 기존 팝업 제거
            const existingPopup = document.getElementById('projectPopup');
            if (existingPopup) {
                existingPopup.remove();
            }
            
            // 팝업 오버레이 생성
            const popupOverlay = document.createElement('div');
            popupOverlay.id = 'projectPopup';
            popupOverlay.className = 'fixed inset-0 bg-black bg-opacity-0 flex items-center justify-center z-50 transition-all duration-300';
            popupOverlay.style.backdropFilter = 'blur(0px)';
            
            // 팝업 컨테이너
            const popupContainer = document.createElement('div');
            popupContainer.className = 'bg-white rounded-2xl shadow-2xl w-11/12 h-5/6 max-w-6xl max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300';
            popupContainer.onclick = (e) => e.stopPropagation();
            
            // 팝업 헤더
            const popupHeader = document.createElement('div');
            popupHeader.className = 'flex items-center justify-between p-4 border-b bg-gray-50 rounded-t-2xl';
            popupHeader.innerHTML = `
                <h3 class="text-xl font-bold text-gray-800">${agentName} - 프로젝트</h3>
                <button onclick="closeProjectPopup()" class="text-gray-600 hover:text-gray-800 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            `;
            
            // iframe 컨테이너
            const iframeContainer = document.createElement('div');
            iframeContainer.className = 'flex-1 overflow-hidden relative';
            
            // 로딩 표시
            const loadingDiv = document.createElement('div');
            loadingDiv.className = 'absolute inset-0 flex items-center justify-center bg-white';
            loadingDiv.innerHTML = `
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
                    <p class="mt-4 text-gray-600">프로젝트 로딩 중...</p>
                </div>
            `;
            iframeContainer.appendChild(loadingDiv);
            
            // iframe
            const iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.className = 'w-full h-full border-0';
            iframe.style.backgroundColor = 'white';
            iframe.onload = () => {
                loadingDiv.remove();
            };
            
            // 조립
            iframeContainer.appendChild(iframe);
            popupContainer.appendChild(popupHeader);
            popupContainer.appendChild(iframeContainer);
            popupOverlay.appendChild(popupContainer);
            
            // 오버레이 클릭시 닫기
            popupOverlay.onclick = () => closeProjectPopup();
            
            // body에 추가
            document.body.appendChild(popupOverlay);
            document.body.style.overflow = 'hidden';
            
            // 애니메이션 시작
            setTimeout(() => {
                popupOverlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300';
                popupOverlay.style.backdropFilter = 'blur(5px)';
                popupContainer.className = 'bg-white rounded-2xl shadow-2xl w-11/12 h-5/6 max-w-6xl max-h-[90vh] flex flex-col transform scale-100 opacity-100 transition-all duration-300';
            }, 10);
            
            // ESC 키로 닫기
            document.addEventListener('keydown', handleProjectPopupEsc);
        }
        
        // ESC 키 핸들러
        function handleProjectPopupEsc(e) {
            if (e.key === 'Escape') {
                closeProjectPopup();
            }
        }
        
        // 프로젝트 팝업 닫기
        function closeProjectPopup() {
            const popup = document.getElementById('projectPopup');
            if (popup) {
                const popupContainer = popup.querySelector('.bg-white');
                
                // 닫기 애니메이션
                popup.className = 'fixed inset-0 bg-black bg-opacity-0 flex items-center justify-center z-50 transition-all duration-300';
                popup.style.backdropFilter = 'blur(0px)';
                if (popupContainer) {
                    popupContainer.className = 'bg-white rounded-2xl shadow-2xl w-11/12 h-5/6 max-w-6xl max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300';
                }
                
                // 애니메이션 후 제거
                setTimeout(() => {
                    popup.remove();
                    document.body.style.overflow = 'auto';
                }, 300);
                
                document.removeEventListener('keydown', handleProjectPopupEsc);
            }
        }

        // 에이전트 클릭 핸들러
        function handleAgentClick(agent) {
            if (phpData.role === 'student') {
                state.chatAgent = agent;
                state.showChat = true;
                renderChat();
            } else {
                state.selectedAgent = agent;
                state.currentView = 'properties';
                // 해당 에이전트의 기본 속성값 로드
                if (!state.agentProperties[agent.id] && defaultProperties[agent.id]) {
                    state.agentProperties[agent.id] = defaultProperties[agent.id];
                }
                showModal();
            }
        }

        // 모달 표시
        function showModal() {
            const modalOverlay = document.getElementById('modalOverlay');
            modalOverlay.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            renderPropertyView();
        }

        // 속성 뷰 렌더링
        function renderPropertyView() {
            const modalBody = document.getElementById('modalBody');
            const agent = state.selectedAgent;
            const properties = state.agentProperties[agent.id] || defaultProperties[agent.id] || {};

            modalBody.innerHTML = `
                <!-- Header -->
                <div class="sticky top-0 z-10 flex items-center p-6 pb-4 border-b bg-gray-50 rounded-t-2xl">
                    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br ${agent.color} 
                        flex items-center justify-center mr-4 shadow-lg">
                        <span class="text-4xl">${agent.icon}</span>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-800">${agent.name}</h2>
                        <p class="text-sm text-gray-600 mt-1">${agent.description}</p>
                    </div>
                    <button onclick="openProjectInNewSystem(state.selectedAgent)" 
                        class="ml-2 px-3 py-1 text-sm bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors"
                        title="프로젝트 보기">
                        📁 프로젝트 보기
                    </button>
                    <button onclick="handleCancel()" 
                        class="ml-2 p-2 hover:bg-gray-200 rounded-lg transition-colors">
                        <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- Properties Form -->
                <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 200px);">
                    <div class="space-y-4">
                        ${Object.entries(propertyLabels).map(([key, label]) => `
                            <div class="space-y-2">
                                <div>
                                    <h3 class="font-semibold text-gray-700">${label.title}</h3>
                                    <p class="text-xs text-gray-500">${label.desc}</p>
                                </div>
                                <textarea
                                    id="prop_${key}"
                                    class="w-full p-3 border border-gray-300 rounded-lg text-sm resize-none 
                                        focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent 
                                        transition-all duration-200"
                                    rows="3"
                                    placeholder="${label.title}을 입력하세요..."
                                >${properties[key] || ''}</textarea>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <!-- Message Display -->
                <div id="messageDisplay" class="px-6 py-3 bg-blue-50 border-t" style="display: none;">
                    <p class="text-sm text-center"></p>
                </div>
                
                <!-- Action Buttons -->
                <div class="sticky bottom-0 z-10 flex gap-3 p-6 pt-4 border-t bg-gray-50 rounded-b-2xl">
                    <button onclick="handleSave()" 
                        id="saveButton"
                        class="flex-1 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg py-3 
                            font-medium hover:from-blue-600 hover:to-blue-700 transition-all duration-200 
                            shadow-md hover:shadow-lg">
                        저장
                    </button>
                    <button onclick="handleCancel()" 
                        class="flex-1 border border-gray-300 rounded-lg py-3 text-gray-700 
                            hover:bg-gray-100 transition-colors duration-200 font-medium">
                        취소
                    </button>
                </div>
            `;
        }

        // 속성 변경 핸들러
        function handlePropertyChange(agentId, property, value) {
            if (!state.agentProperties[agentId]) {
                state.agentProperties[agentId] = defaultProperties[agentId] || {};
            }
            state.agentProperties[agentId][property] = value;
        }

        // 저장 핸들러
        async function handleSave() {
            state.loading = true;
            const saveButton = document.getElementById('saveButton');
            saveButton.disabled = true;
            saveButton.textContent = '저장 중...';
            
            // 현재 폼 값들 수집
            const properties = {};
            Object.keys(propertyLabels).forEach(key => {
                const textarea = document.getElementById(`prop_${key}`);
                if (textarea) {
                    properties[key] = textarea.value;
                }
            });
            
            try {
                const response = await fetch(phpData.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'save_agent_properties',
                        agent_id: state.selectedAgent.id,
                        user_id: phpData.userId,
                        properties: properties
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showMessage('✅ 속성이 성공적으로 저장되었습니다!');
                    setTimeout(() => {
                        handleCancel();
                    }, 1500);
                } else {
                    showMessage('❌ 저장 중 오류가 발생했습니다: ' + result.error);
                }
            } catch (error) {
                showMessage('❌ 네트워크 오류가 발생했습니다.');
                console.error('Save error:', error);
            } finally {
                state.loading = false;
                saveButton.disabled = false;
                saveButton.textContent = '저장';
            }
        }

        // 메시지 표시
        function showMessage(message) {
            const messageDisplay = document.getElementById('messageDisplay');
            const messageText = messageDisplay.querySelector('p');
            messageText.textContent = message;
            messageDisplay.style.display = 'block';
        }

        // 취소 핸들러
        function handleCancel() {
            state.selectedAgent = null;
            document.getElementById('modalOverlay').style.display = 'none';
            document.body.style.overflow = 'unset';
        }

        // 채팅 열기
        function openChat(agent) {
            // 이전 강조 제거
            if (state.activeAgentCard) {
                state.activeAgentCard.classList.remove('highlighted');
            }
            
            // 현재 카드 강조
            const currentCard = document.getElementById(`agent-card-${agent.id}`);
            if (currentCard) {
                currentCard.classList.add('highlighted');
                state.activeAgentCard = currentCard;
            }
            
            state.chatAgent = agent;
            state.showChat = true;
            
            // 에이전트 속성 로드
            if (!state.agentProperties[agent.id] && defaultProperties[agent.id]) {
                state.agentProperties[agent.id] = defaultProperties[agent.id];
            }
            
            renderChat();
            
            // 메인 컨테이너 이동
            document.getElementById('mainContainer').classList.add('shifted');
            document.getElementById('chatPanel').classList.add('open');
            
            // 반응형 그리드 조정
            adjustGrid();
        }

        // 채팅 렌더링
        function renderChat() {
            const chatPanel = document.getElementById('chatPanel');
            const agent = state.chatAgent;
            
            chatPanel.innerHTML = `
                <div class="chat-header bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 flex items-center">
                    <span class="text-2xl mr-3">${agent.icon}</span>
                    <div class="flex-1">
                        <h3 class="font-bold">${agent.name}</h3>
                        <p class="text-sm opacity-90">${agent.description}</p>
                    </div>
                    <button onclick="openProjectInNewSystem(state.chatAgent)" 
                        class="p-2 hover:bg-white/20 rounded-lg mr-2" title="프로젝트 보기">
                        📁
                    </button>
                    <button onclick="handleChatClose()" class="p-2 hover:bg-white/20 rounded-lg">
                        ✕
                    </button>
                </div>
                
                <!-- 채팅 영역 -->
                <div id="chatContent" class="flex-1 overflow-y-auto p-4">
                    <!-- 에이전트 속성 표시 -->
                    <div id="agentPropertiesDisplay" class="mb-4 p-4 bg-blue-50 rounded-lg" style="display: none;">
                        <h4 class="font-semibold mb-3 flex justify-between items-center">
                            현재 에이전트 속성
                            <div>
                                <button id="initPropertiesBtn" onclick="generateInitialValues()" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 mr-2" style="display: none;">
                                    초기값 자동생성
                                </button>
                                <button onclick="improveProperties()" class="px-3 py-1 bg-purple-500 text-white rounded text-sm hover:bg-purple-600">
                                    개선
                                </button>
                            </div>
                        </h4>
                        <div id="propertiesContainer"></div>
                    </div>
                    
                    <div id="messageContainer" class="space-y-3"></div>
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
            `;
            
            // 속성 정보 표시
            displayAgentProperties();
            
            // 초기 메시지
            const hasProperties = state.agentProperties[agent.id] && 
                Object.values(state.agentProperties[agent.id]).some(val => val && val.trim());
            
            if (!hasProperties) {
                addMessage('system', '현재 에이전트의 속성이 설정되지 않았습니다.', {
                    text: '🎲 초기값 자동생성',
                    class: 'bg-blue-500 text-white hover:bg-blue-600',
                    onclick: 'generateInitialValues()'
                });
            } else {
                addMessage('agent', `안녕하세요! ${agent.name}입니다. 현재 설정된 세계관과 문맥을 기반으로 도와드리겠습니다.`);
            }
        }

        // 채팅 닫기
        function handleChatClose() {
            state.showChat = false;
            state.chatAgent = null;
            
            // 카드 강조 제거
            if (state.activeAgentCard) {
                state.activeAgentCard.classList.remove('highlighted');
                state.activeAgentCard = null;
            }
            
            document.getElementById('mainContainer').classList.remove('shifted');
            document.getElementById('chatPanel').classList.remove('open');
            
            // 반응형 그리드 복원
            adjustGrid();
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
            
            // 편집 가능한 메시지 내용
            const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
            messageDiv.id = messageId;
            
            // 메시지 데이터 저장
            messageDiv.dataset.content = content;
            messageDiv.dataset.type = type;
            
            bubbleDiv.innerHTML = `
                <div class="message-content" onclick="startEditMessage('${messageId}', '${type}')" title="클릭하여 편집">
                    <p class="whitespace-pre-wrap">${content}</p>
                </div>
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
            
            if (!message || !state.chatAgent) return;
            
            // 사용자 메시지 추가
            addMessage('user', message);
            input.value = '';
            
            // 채팅 히스토리에 추가
            state.chatHistory.push({ type: 'user', content: message, agentId: state.chatAgent.id });
            
            // 내용 분석 및 다음 카드 추천
            analyzeAndRecommendCards(message);
            
            // 로딩 표시
            showLoading();
            
            // AI 응답 생성
            try {
                const response = await generateAIResponse(message);
                hideLoading();
                addMessage('agent', response);
                state.chatHistory.push({ type: 'agent', content: response, agentId: state.chatAgent.id });
            } catch (error) {
                hideLoading();
                addMessage('system', '❌ 메시지 전송에 실패했습니다.');
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
            const chatOpen = state.showChat;
            
            // 7x3 그리드 유지, 채팅 열릴 때는 전체 컨테이너가 이동
            if (window.innerWidth < 1024) {
                grid.classList.remove('grid-cols-7');
                grid.classList.add('grid-cols-5');
            } else {
                grid.classList.remove('grid-cols-5');
                grid.classList.add('grid-cols-7');
            }
        }

        // 내용 분석 및 카드 추천
        function analyzeAndRecommendCards(message) {
            // 이전 추천 제거
            state.recommendedCards = [];
            updateRecommendedCards();
            
            // 키워드 기반 추천 매핑
            const keywordMap = {
                '시간': [2, 7, 11], // 타임라인 합성기, 일일 사령부, 실행 파이프라인
                '계획': [2, 3, 7], // 타임라인 합성기, 성장 엘리베이터, 일일 사령부
                '목표': [1, 4, 13], // 시간 수정체, 성과지표 엔진, 성장 트리거
                '미래': [1, 3, 15], // 시간 수정체, 성장 엘리베이터, 시간수정체 CEO
                '학습': [9, 16, 17], // 수직 탐사기, AI 정원사, 신경망 설계사
                '분석': [3, 6, 9], // 성장 엘리베이터, SWOT 분석기, 수직 탐사기
                '실행': [7, 11, 5], // 일일 사령부, 실행 파이프라인, 동기 엔진
                '성장': [3, 13, 15], // 성장 엘리베이터, 성장 트리거, 시간수정체 CEO
                '동기': [5, 13, 8], // 동기 엔진, 성장 트리거, 내면 브랜딩
                '정리': [10, 11, 16], // 자원 정원사, 실행 파이프라인, AI 정원사
                '브랜딩': [8, 12, 14], // 내면 브랜딩, 외부 브랜딩, 경쟁 생존 전략가
                '지식': [16, 17, 18, 19, 20], // AI 정원사, 신경망 설계사, 정보 허브, 지식 연결망, 지식 수정체
                '자동화': [11, 21], // 실행 파이프라인, 유연한 백본
                '전략': [6, 14] // SWOT 분석기, 경쟁 생존 전략가
            };
            
            // 메시지에서 키워드 찾기
            const foundKeywords = [];
            Object.keys(keywordMap).forEach(keyword => {
                if (message.toLowerCase().includes(keyword)) {
                    foundKeywords.push(keyword);
                }
            });
            
            // 추천 카드 수집 (현재 카드 제외)
            const recommendations = new Set();
            foundKeywords.forEach(keyword => {
                keywordMap[keyword].forEach(id => {
                    if (id !== state.chatAgent.id) {
                        recommendations.add(id);
                    }
                });
            });
            
            // 최대 3개까지 추천
            state.recommendedCards = Array.from(recommendations).slice(0, 3);
            
            if (state.recommendedCards.length > 0) {
                updateRecommendedCards();
                
                const recommendedNames = state.recommendedCards.map(id => {
                    const agent = state.agents.find(a => a.id === id);
                    return agent ? `${agent.icon} ${agent.name}` : '';
                }).join(', ');
                
                addMessage('system', `💡 다음 에이전트와의 대화를 추천합니다: ${recommendedNames}`);
            }
        }

        // 추천 카드 UI 업데이트
        function updateRecommendedCards() {
            // 모든 카드의 추천 스타일 초기화
            state.agents.forEach(agent => {
                const card = document.getElementById(`agent-card-${agent.id}`);
                if (card) {
                    if (state.recommendedCards.includes(agent.id)) {
                        card.classList.add('ring-2', 'ring-green-400', 'animate-pulse');
                    } else {
                        card.classList.remove('ring-2', 'ring-green-400', 'animate-pulse');
                    }
                }
            });
        }

        // AI 응답 생성
        async function generateAIResponse(userMessage) {
            const agent = state.chatAgent;
            const properties = state.agentProperties[agent.id] || {};
            
            // 실제로는 OpenAI API를 호출하겠지만, 여기서는 에이전트 특성에 맞는 응답 생성
            const worldView = properties.worldView || defaultProperties[agent.id]?.worldView || '';
            const context = properties.context || defaultProperties[agent.id]?.context || '';
            
            // 에이전트별 응답 스타일
            const responseTemplates = {
                1: `미래 비전의 관점에서 보면, ${userMessage}에 대한 접근은 장기적 목표와 연결되어야 합니다. ${worldView}`,
                2: `시간 관리 측면에서, ${userMessage}를 효율적으로 처리하려면 체계적인 계획이 필요합니다. ${context}`,
                3: `성장의 관점에서, 이는 새로운 도약의 기회입니다. ${worldView}`,
                5: `동기부여 관점에서, ${userMessage}는 내적 열정과 연결될 때 진정한 힘을 발휘합니다.`,
                6: `전략적 분석을 통해 보면, 강점을 활용하고 약점을 보완하는 접근이 필요합니다.`,
                // 기본 템플릿
                default: `${agent.name}의 관점에서, ${userMessage}에 대해 ${worldView || '깊이 있는 통찰'}을 제공하겠습니다.`
            };
            
            return responseTemplates[agent.id] || responseTemplates.default;
        }

        // 에이전트 속성 표시
        function displayAgentProperties() {
            const agent = state.chatAgent;
            const properties = state.agentProperties[agent.id] || {};
            const propertiesDisplay = document.getElementById('agentPropertiesDisplay');
            const propertiesContainer = document.getElementById('propertiesContainer');
            
            if (Object.values(properties).some(val => val && val.trim())) {
                propertiesDisplay.style.display = 'block';
                propertiesContainer.innerHTML = `
                    <div class="space-y-2 text-sm">
                        ${properties.worldView ? `<div><span class="font-medium">세계관:</span> ${properties.worldView.substring(0, 50)}...</div>` : ''}
                        ${properties.context ? `<div><span class="font-medium">문맥:</span> ${properties.context.substring(0, 50)}...</div>` : ''}
                    </div>
                `;
                document.getElementById('initPropertiesBtn').style.display = 'none';
            } else {
                propertiesDisplay.style.display = 'block';
                propertiesContainer.innerHTML = '<p class="text-gray-500 text-sm">속성이 설정되지 않았습니다.</p>';
                document.getElementById('initPropertiesBtn').style.display = 'inline-block';
            }
        }

        // 시스템 메시지 추가 (원시 HTML)
        function addSystemMessage(htmlContent) {
            const container = document.getElementById('messageContainer');
            const messageDiv = document.createElement('div');
            messageDiv.innerHTML = htmlContent;
            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }
        
        // 메시지 편집 시작
        function startEditMessage(messageId, messageType) {
            const messageDiv = document.getElementById(messageId);
            if (!messageDiv) return;
            
            const messageContent = messageDiv.querySelector('.message-content');
            const currentContent = messageDiv.dataset.content;
            
            // 이미 편집 중이면 무시
            if (messageContent.classList.contains('editing')) return;
            
            messageContent.classList.add('editing');
            
            // 편집 UI 생성
            const editHtml = `
                <textarea class="message-edit-textarea" id="edit-${messageId}">${currentContent}</textarea>
                <div class="message-edit-buttons">
                    <button onclick="saveEditMessage('${messageId}')" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">
                        저장
                    </button>
                    <button onclick="cancelEditMessage('${messageId}')" class="px-3 py-1 bg-gray-300 text-gray-700 rounded text-sm hover:bg-gray-400">
                        취소
                    </button>
                </div>
            `;
            
            messageContent.innerHTML = editHtml;
            
            // 텍스트 영역에 포커스
            const textarea = document.getElementById(`edit-${messageId}`);
            textarea.focus();
            textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            
            // Enter 키로 저장, Esc 키로 취소
            textarea.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    saveEditMessage(messageId);
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelEditMessage(messageId);
                }
            });
        }
        
        // 메시지 편집 저장
        function saveEditMessage(messageId) {
            const messageDiv = document.getElementById(messageId);
            if (!messageDiv) return;
            
            const textarea = document.getElementById(`edit-${messageId}`);
            const newContent = textarea.value.trim();
            
            if (!newContent) {
                cancelEditMessage(messageId);
                return;
            }
            
            // 데이터 업데이트
            messageDiv.dataset.content = newContent;
            
            // UI 업데이트
            const messageContent = messageDiv.querySelector('.message-content');
            messageContent.classList.remove('editing');
            messageContent.innerHTML = `<p class="whitespace-pre-wrap">${newContent}</p>`;
            
            // 편집 이력 추가 (시스템 메시지로)
            if (messageDiv.dataset.type !== 'system') {
                addMessage('system', '✏️ 메시지가 편집되었습니다.');
            }
            
            // 채팅 히스토리 업데이트
            const historyIndex = state.chatHistory.findIndex(msg => 
                msg.timestamp && msg.content === messageDiv.dataset.originalContent
            );
            if (historyIndex > -1) {
                state.chatHistory[historyIndex].content = newContent;
                state.chatHistory[historyIndex].edited = true;
            }
        }
        
        // 메시지 편집 취소
        function cancelEditMessage(messageId) {
            const messageDiv = document.getElementById(messageId);
            if (!messageDiv) return;
            
            const messageContent = messageDiv.querySelector('.message-content');
            const originalContent = messageDiv.dataset.content;
            
            messageContent.classList.remove('editing');
            messageContent.innerHTML = `<p class="whitespace-pre-wrap">${originalContent}</p>`;
        }

        // 초기값 자동생성
        async function generateInitialValues() {
            const agent = state.chatAgent;
            showLoading();
            
            try {
                // 문맥 기반 초기값 생성
                const contextualProperties = await generateContextualProperties(agent);
                state.agentProperties[agent.id] = contextualProperties;
                
                hideLoading();
                addMessage('system', '✅ 초기값이 생성되었습니다!');
                displayAgentProperties();
                
                // 자동 저장
                await saveGeneratedProperties();
                
            } catch (error) {
                hideLoading();
                addMessage('system', '❌ 초기값 생성에 실패했습니다.');
            }
        }

        // 속성 개선
        async function improveProperties() {
            const agent = state.chatAgent;
            const currentProperties = state.agentProperties[agent.id];
            
            // 이전 값 저장
            state.previousProperties[agent.id] = { ...currentProperties };
            
            showLoading();
            
            try {
                // TODO: API 호출로 세계관과 문맥 중심으로 개선
                // 임시 개선 로직
                const improved = {
                    ...currentProperties,
                    worldView: currentProperties.worldView + ' [개선됨]',
                    context: currentProperties.context + ' [개선됨]'
                };
                
                state.agentProperties[agent.id] = improved;
                
                hideLoading();
                addMessage('system', '✅ 속성이 개선되었습니다!', {
                    text: '↩️ 이전으로 되돌리기',
                    class: 'bg-gray-500 text-white hover:bg-gray-600',
                    onclick: 'revertProperties()'
                });
                
                displayAgentProperties();
                
                // 자동 저장
                await saveGeneratedProperties();
                
            } catch (error) {
                hideLoading();
                addMessage('system', '❌ 속성 개선에 실패했습니다.');
            }
        }

        // 속성 되돌리기
        async function revertProperties() {
            const agent = state.chatAgent;
            if (state.previousProperties[agent.id]) {
                state.agentProperties[agent.id] = state.previousProperties[agent.id];
                delete state.previousProperties[agent.id];
                
                addMessage('system', '↩️ 이전 속성으로 되돌렸습니다.');
                displayAgentProperties();
                
                // 자동 저장
                await saveGeneratedProperties();
            }
        }

        // 생성된 속성 저장
        async function saveGeneratedProperties() {
            const agent = state.chatAgent;
            const properties = state.agentProperties[agent.id];
            
            try {
                const response = await fetch(phpData.apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'save_agent_properties',
                        agent_id: agent.id,
                        user_id: phpData.userId,
                        properties: properties
                    })
                });
                
                const result = await response.json();
                
                if (!result.success) {
                    console.error('Failed to save properties:', result.error);
                }
            } catch (error) {
                console.error('Save error:', error);
            }
        }

        // 문맥 기반 속성 생성
        async function generateContextualProperties(agent) {
            // 에이전트별 맞춤형 초기값 템플릿
            const templates = {
                1: { // 시간 수정체
                    worldView: '미래의 나는 현재의 모든 선택과 행동의 집합체다. 시간은 선형이 아닌 가능성의 네트워크다.',
                    context: '학생의 현재 상황과 미래 비전 사이의 연결고리를 찾아 감정적 동력을 생성한다.',
                    structure: '미래 자아 스토리텔링 → 현재 행동 매핑 → 감정 앵커 설정 → 실행 트리거 구축',
                    process: '1) 5년 후 이상적 자아 구체화 2) 현재와의 갭 분석 3) 감정적 연결점 도출 4) 일일 리마인더 설정',
                    execution: '매일 아침 미래 자아와의 대화, 주간 비전 보드 업데이트, 월간 스토리 리뷰',
                    reflection: '미래 비전의 현실성과 동기부여 효과를 주기적으로 평가하고 조정',
                    transfer: '성공적인 미래 설계 스토리를 템플릿화하여 공유',
                    abstraction: '미래에 대한 구체적 상상력이 현재의 행동력을 결정한다'
                },
                2: { // 타임라인 합성기
                    worldView: '모든 큰 목표는 작은 단계들의 정교한 조합이며, 시간은 설계 가능한 자원이다.',
                    context: '복잡한 목표를 달성 가능한 단위로 분해하고 현실적인 시간축에 배치한다.',
                    structure: '목표 분해 트리 → 시간 블록 할당 → 의존성 매핑 → 버퍼 타임 설계',
                    process: '1) 최종 목표 정의 2) 역산 분해 3) 간트차트 작성 4) 마일스톤 설정 5) 진행 추적',
                    execution: '주간 계획 수립 세션, 일일 우선순위 조정, 진행률 시각화 대시보드 운영',
                    reflection: '계획 대비 실행률 분석, 병목 구간 식별, 시간 예측 정확도 개선',
                    transfer: '효과적인 프로젝트 계획 템플릿과 시간 관리 노하우 문서화',
                    abstraction: '체계적 계획과 유연한 실행의 균형이 목표 달성의 핵심이다'
                },
                3: { // 성장 엘리베이터
                    worldView: '성장은 단순한 축적이 아닌 질적 도약이며, 가속화가 가능한 과정이다.',
                    context: '현재의 성장 패턴을 분석하여 돌파구를 찾고 지수적 성장을 설계한다.',
                    structure: '성장 지표 정의 → 데이터 수집 체계 → 패턴 분석 엔진 → 가속 전략 도출',
                    process: '1) 다차원 성장 지표 설정 2) 일일 데이터 입력 3) 주간 패턴 분석 4) 월간 전략 수정',
                    execution: '성장 일지 작성, 주요 지표 트래킹, 성장 그래프 시각화, 인사이트 도출',
                    reflection: '성장 속도와 질의 균형 평가, 정체 구간 원인 분석, 돌파 전략 효과성 검증',
                    transfer: '개인별 성장 패턴과 가속화 전략을 케이스 스터디로 공유',
                    abstraction: '측정 가능한 것만이 개선 가능하며, 패턴 인식이 도약의 시작이다'
                },
                4: { // 성과지표 엔진
                    worldView: '목표 없는 노력은 방황이며, 측정 없는 목표는 환상이다.',
                    context: '추상적 목표를 구체적 지표로 변환하고 실시간으로 추적 관리한다.',
                    structure: 'OKR 프레임워크 → KPI 대시보드 → 실시간 모니터링 → 자동 알림 시스템',
                    process: '1) 목표 수치화 2) 측정 방법 정의 3) 추적 시스템 구축 4) 주기적 리뷰',
                    execution: '일일 지표 입력, 주간 대시보드 점검, 월간 목표 조정, 분기별 전면 검토',
                    reflection: '지표의 타당성 검증, 목표 달성률 분석, 개선점 도출',
                    transfer: '효과적인 성과 측정 시스템을 조직 전체에 확산',
                    abstraction: '명확한 지표가 명확한 행동을 만들고, 지속적 측정이 지속적 개선을 낳는다'
                },
                5: { // 동기 엔진
                    worldView: '동기는 감정의 연료이며, 지속 가능한 성과의 핵심 동력이다.',
                    context: '개인의 내적 동기를 발견하고 외적 보상과 연결하여 지속 가능한 추진력을 생성한다.',
                    structure: '동기 유형 분석 → 감정 트리거 매핑 → 보상 시스템 설계 → 피드백 루프 구축',
                    process: '1) 내적 동기 탐색 2) 감정적 앵커 설정 3) 단계별 보상 설계 4) 실시간 피드백',
                    execution: '매일 동기 점검, 감정 일지 작성, 성취 축하 리추얼, 동료 격려 시스템',
                    reflection: '동기 수준 변화 추적, 효과적인 트리거 분석, 지속성 개선 방안 도출',
                    transfer: '개인별 동기부여 전략을 팀 전체와 공유하여 상호 격려 문화 조성',
                    abstraction: '진정한 동기는 내면에서 시작되며, 작은 성취의 축적이 큰 열정을 만든다'
                },
                // ... 나머지 에이전트들도 비슷한 형식으로 정의
                default: {
                    worldView: `${agent.name}의 핵심 철학과 세계관을 담은 관점`,
                    context: `${agent.description}을 위한 구체적 상황과 맥락`,
                    structure: `목표 달성을 위한 체계적 구조와 프레임워크`,
                    process: `단계별 실행 프로세스와 워크플로우`,
                    execution: `일상적 실행 방법과 도구`,
                    reflection: `성찰과 개선을 위한 평가 체계`,
                    transfer: `지식과 경험의 공유 및 확산 방법`,
                    abstraction: `핵심 원리와 통찰의 추상화`
                }
            };
            
            // 해당 에이전트의 템플릿 반환
            return templates[agent.id] || templates.default;
        }

        // 초기화 실행
        document.addEventListener('DOMContentLoaded', init);
    </script>
</body>
</html>