/**
 * 🌌 마이 궤도 - WXsperta 애플리케이션 JavaScript
 * 분리일: 2025-12-07
 * 
 * 의존성: phpData (PHP에서 전달받는 전역 객체)
 */

// ==================== 전역 상태 관리 ====================
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
    recommendedCards: [],
    currentViewMode: 'grid'
};

// 네트워크 뷰 상태
const networkState = {
    zoom: 1,
    selectedNode: null,
    nodePositions: {},
    connections: [],
    // 드래그 상태
    draggedNode: null
};

// ==================== 기본 속성값 ====================
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

// ==================== 섹터 및 매핑 정보 ====================
const sectorGroups = {
    'future_design': { 
        title: '🗺️ 항해 지도', 
        subtitle: '어디로 갈까?',
        color: 'var(--cat-voyage)' 
    },
    'execution': { 
        title: '🚀 미션 센터', 
        subtitle: '오늘 뭐 할까?',
        color: 'var(--cat-mission)' 
    },
    'branding': { 
        title: '🎨 나의 깃발', 
        subtitle: '나를 보여줘',
        color: 'var(--cat-flag)' 
    },
    'knowledge_management': { 
        title: '🌟 자원 창고', 
        subtitle: '모아서 연결해',
        color: 'var(--cat-resource)' 
    }
};

const missionIcons = {
    future_design: ['📡', '🗺️', '📊', '⭐'],
    execution: ['⚡', '🔍', '🎯', '💎', '🔬', '📦', '🤖'],
    branding: ['📢', '🏕️', '🛡️'],
    knowledge_management: ['🗼', '🌱', '🔗', '📡', '🌌', '💎', '⚙️']
};

const moodResponses = {
    'sunny': '오늘 에너지 충만하네! 🚀 새로운 도전 가볼까?',
    'cloudy': '무난한 하루~ 하나씩 해보자!',
    'overcast': '좀 뿌옇지? 가볍게 정리하면서 쉬어가도 돼',
    'rainy': '많이 지쳤구나. 오늘은 쉬어가도 괜찮아 💙'
};

const agentNameMap = {
    1: '01_time_capsule', 2: '02_timeline_synthesizer', 3: '03_growth_elevator',
    4: '04_performance_engine', 5: '05_motivation_engine', 6: '06_swot_analyzer',
    7: '07_daily_command', 8: '08_inner_branding', 9: '09_vertical_explorer',
    10: '10_resource_gardener', 11: '11_execution_pipeline', 12: '12_external_branding',
    13: '13_growth_trigger', 14: '14_competitive_strategist', 15: '15_timecapsule_ceo',
    16: '16_ai_gardener', 17: '17_neural_architect', 18: '18_info_hub',
    19: '19_knowledge_network', 20: '20_knowledge_crystal', 21: '21_flexible_backbone'
};

// ==================== 유틸리티 함수 ====================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ==================== 초기화 ====================
function init() {
    console.log('🌌 마이 궤도 초기화 - 역할:', phpData.role);
    
    // URL 파라미터 체크 (탐험 지도에서 리다이렉트 시)
    const urlParams = new URLSearchParams(window.location.search);
    const viewParam = urlParams.get('view');
    
    // 미션 데이터 준비
    if (phpData.agents.length > 0) {
        state.agents = phpData.agents.map(agent => ({
            ...agent,
            id: agent.id, // 폴더 이름 ID (예: 09_vertical_explorer)
            number: parseInt(agent.number) || parseInt(agent.id) || 0, // 숫자 번호
            icon: agent.icon || '🎯',
            shortDesc: agent.shortDesc || agent.short_desc || agent.description,
            connections: agent.connections || []
        }));
    }

    // 미션 그리드 렌더링
    renderAgentGrid();
    
    // 네트워크 뷰 준비
    prepareNetworkView();
    
    // 감정 날씨 초기화
    initMoodChecker();
    
    // URL 파라미터에 따른 뷰 전환 (탐험 지도에서 리다이렉트 시)
    if (viewParam === 'explore') {
        // 네트워크 뷰로 시작 (탐험 지도 느낌)
        setTimeout(() => switchView('network'), 100);
    }

    // ESC 키 이벤트
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (state.selectedAgent) handleCancel();
            if (state.showChat) window.handleChatClose();
        }
    });
    
    // 윈도우 리사이즈 핸들러
    window.addEventListener('resize', debounce(() => {
        if (state.currentViewMode === 'network') {
            renderNetworkView();
        }
    }, 250));
}

// ==================== 뷰 스위칭 ====================
function switchView(viewMode) {
    state.currentViewMode = viewMode;
    
    const gridView = document.getElementById('missionGrid');
    const networkView = document.getElementById('networkView');
    const gridBtn = document.getElementById('gridViewBtn');
    const networkBtn = document.getElementById('networkViewBtn');
    
    if (viewMode === 'grid') {
        gridView.style.display = 'block';
        networkView.style.display = 'none';
        gridBtn.classList.add('active');
        networkBtn.classList.remove('active');
    } else {
        gridView.style.display = 'none';
        networkView.style.display = 'block';
        gridBtn.classList.remove('active');
        networkBtn.classList.add('active');
        renderNetworkView();
    }
}

// ==================== 네트워크 뷰 ====================
function prepareNetworkView() {
    networkState.connections = [];
    
    state.agents.forEach(agent => {
        if (agent.connections && agent.connections.length > 0) {
            agent.connections.forEach(connId => {
                const targetAgent = state.agents.find(a => 
                    a.id === connId || 
                    a.number === connId ||
                    (typeof connId === 'string' && connId.includes(String(a.id).padStart(2, '0')))
                );
                
                if (targetAgent) {
                    // 중복 연결 체크
                    const exists = networkState.connections.some(c => 
                        (c.source === agent.id && c.target === targetAgent.id) ||
                        (c.source === targetAgent.id && c.target === agent.id)
                    );
                    
                    if (!exists) {
                        const isMutual = targetAgent.connections && 
                            targetAgent.connections.some(c => 
                                c === agent.id || 
                                c === agent.number ||
                                (typeof c === 'string' && c.includes(String(agent.id).padStart(2, '0')))
                            );
                        
                        networkState.connections.push({
                            source: agent.id,
                            target: targetAgent.id,
                            mutual: isMutual
                        });
                    }
                }
            });
        }
    });
}

function renderNetworkView() {
    const svg = document.getElementById('networkSvg');
    const width = svg.clientWidth || 800;
    const height = svg.clientHeight || 600;
    const centerX = width / 2;
    const centerY = height / 2;
    
    // 초기 위치가 없으면 원형 배치로 초기화
    if (Object.keys(networkState.nodePositions).length === 0) {
        initializeNodePositions(width, height, centerX, centerY);
    }
    
    // 렌더링
    renderNetwork();
    
    // 드래그 이벤트 설정
    setupDragEvents(svg);
}

function initializeNodePositions(width, height, centerX, centerY) {
    const nodeSpacingX = 130; // 가로 간격
    const nodeSpacingY = 140; // 세로 간격 (3줄)
    
    // 모든 에이전트를 번호순으로 정렬
    const sortedAgents = [...state.agents].sort((a, b) => (a.number || 0) - (b.number || 0));
    
    // 가로 3줄 레이아웃: 7-7-7
    const itemsPerRow = 7;
    const rowCount = 3;
    
    // 각 줄의 전체 너비 계산
    const rowWidth = (itemsPerRow - 1) * nodeSpacingX;
    const totalHeight = (rowCount - 1) * nodeSpacingY;
    
    // 시작 위치 (중앙 정렬)
    const startX = centerX - rowWidth / 2;
    const startY = centerY - totalHeight / 2;
    
    sortedAgents.forEach((agent, idx) => {
        const row = Math.floor(idx / itemsPerRow);
        const col = idx % itemsPerRow;
        
        const x = startX + col * nodeSpacingX;
        const y = startY + row * nodeSpacingY;
        
        networkState.nodePositions[agent.id] = { x, y };
    });
}

function renderNetwork() {
    const connectionsGroup = document.getElementById('connectionsGroup');
    const nodesGroup = document.getElementById('nodesGroup');
    
    // 연결선 렌더링
    connectionsGroup.innerHTML = '';
    networkState.connections.forEach((conn) => {
        const sourcePos = networkState.nodePositions[conn.source];
        const targetPos = networkState.nodePositions[conn.target];
        
        if (sourcePos && targetPos) {
            const dx = targetPos.x - sourcePos.x;
            const dy = targetPos.y - sourcePos.y;
            const distance = Math.sqrt(dx * dx + dy * dy) || 1;
            
            // 노드 반경 고려
            const nodeRadius = 28;
            const startX = sourcePos.x + (dx / distance) * nodeRadius;
            const startY = sourcePos.y + (dy / distance) * nodeRadius;
            const endX = targetPos.x - (dx / distance) * nodeRadius;
            const endY = targetPos.y - (dy / distance) * nodeRadius;
            
            // 곡선
            const curvature = 0.15 + (conn.mutual ? 0.05 : 0);
            const midX = (startX + endX) / 2;
            const midY = (startY + endY) / 2;
            const perpX = -dy / distance * distance * curvature;
            const perpY = dx / distance * distance * curvature;
            const ctrlX = midX + perpX;
            const ctrlY = midY + perpY;
            
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${startX} ${startY} Q ${ctrlX} ${ctrlY} ${endX} ${endY}`);
            path.setAttribute('class', `network-connection ${conn.mutual ? 'mutual' : 'outgoing'}`);
            path.setAttribute('data-source', conn.source);
            path.setAttribute('data-target', conn.target);
            path.setAttribute('marker-end', 'url(#arrowhead)');
            connectionsGroup.appendChild(path);
        }
    });
    
    // 노드 렌더링
    nodesGroup.innerHTML = '';
    state.agents.forEach(agent => {
        const pos = networkState.nodePositions[agent.id];
        if (!pos) return;
        
        const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        group.setAttribute('class', 'network-node');
        group.setAttribute('data-id', agent.id);
        group.setAttribute('transform', `translate(${pos.x}, ${pos.y})`);
        group.style.cursor = 'grab';
        
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('r', 28);
        circle.setAttribute('class', `node-${agent.category}`);
        circle.setAttribute('stroke', networkState.selectedNode?.id === agent.id ? '#FF6B6B' : 'rgba(255,255,255,0.2)');
        circle.setAttribute('stroke-width', networkState.selectedNode?.id === agent.id ? '4' : '2');
        group.appendChild(circle);
        
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('dominant-baseline', 'central');
        text.setAttribute('font-size', '20');
        text.setAttribute('pointer-events', 'none');
        text.textContent = agent.icon;
        group.appendChild(text);
        
        const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        label.setAttribute('y', 45);
        label.setAttribute('text-anchor', 'middle');
        label.setAttribute('font-size', '10');
        label.setAttribute('fill', 'var(--starlight)');
        label.setAttribute('pointer-events', 'none');
        label.textContent = agent.shortDesc ? agent.shortDesc.substring(0, 8) : '';
        group.appendChild(label);
        
        nodesGroup.appendChild(group);
    });
}

function setupDragEvents(svg) {
    // 이벤트 리스너 중복 등록 방지
    if (svg.dataset.dragEventsSetup) return;
    svg.dataset.dragEventsSetup = 'true';
    
    // 마우스/터치 이벤트
    svg.addEventListener('mousedown', handleMouseDown);
    svg.addEventListener('mousemove', handleMouseMove);
    svg.addEventListener('mouseup', handleMouseUp);
    svg.addEventListener('mouseleave', handleMouseUp);
    
    svg.addEventListener('touchstart', handleMouseDown, { passive: false });
    svg.addEventListener('touchmove', handleMouseMove, { passive: false });
    svg.addEventListener('touchend', handleMouseUp);
}

function handleMouseDown(e) {
    const nodeGroup = e.target.closest('.network-node');
    if (!nodeGroup) return;
    
    e.preventDefault();
    
    const nodeId = nodeGroup.getAttribute('data-id'); // 문자열 ID 그대로 사용
    networkState.draggedNode = nodeId;
    
    // 선택 상태 업데이트
    const agent = state.agents.find(a => a.id === nodeId);
    if (agent) {
        // 바로 전체 화면 모달로 에이전트 페이지 열기
        openAgentFullscreen(agent);
    }
    
    nodeGroup.style.cursor = 'grabbing';
    document.getElementById('networkSvg').style.cursor = 'grabbing';
}

function handleMouseMove(e) {
    if (!networkState.draggedNode) return;
    
    e.preventDefault();
    
    const svg = document.getElementById('networkSvg');
    const rect = svg.getBoundingClientRect();
    
    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
    
    const x = clientX - rect.left;
    const y = clientY - rect.top;
    
    // 경계 내로 제한
    const padding = 35;
    const newX = Math.max(padding, Math.min(rect.width - padding, x));
    const newY = Math.max(padding, Math.min(rect.height - padding, y));
    
    // 위치 업데이트
    networkState.nodePositions[networkState.draggedNode] = { x: newX, y: newY };
    
    // 노드 위치 업데이트
    const nodeGroup = document.querySelector(`.network-node[data-id="${networkState.draggedNode}"]`);
    if (nodeGroup) {
        nodeGroup.setAttribute('transform', `translate(${newX}, ${newY})`);
    }
    
    // 연결선 업데이트
    updateConnectionLines();
}

function handleMouseUp() {
    if (!networkState.draggedNode) return;
    
    const nodeGroup = document.querySelector(`.network-node[data-id="${networkState.draggedNode}"]`);
    if (nodeGroup) {
        nodeGroup.style.cursor = 'grab';
    }
    
    const svg = document.getElementById('networkSvg');
    svg.style.cursor = 'default';
    
    networkState.draggedNode = null;
}

function updateConnectionLines() {
    networkState.connections.forEach(conn => {
        const sourcePos = networkState.nodePositions[conn.source];
        const targetPos = networkState.nodePositions[conn.target];
        
        if (sourcePos && targetPos) {
            const path = document.querySelector(`.network-connection[data-source="${conn.source}"][data-target="${conn.target}"]`);
            if (path) {
                const dx = targetPos.x - sourcePos.x;
                const dy = targetPos.y - sourcePos.y;
                const distance = Math.sqrt(dx * dx + dy * dy) || 1;
                
                const nodeRadius = 28;
                const startX = sourcePos.x + (dx / distance) * nodeRadius;
                const startY = sourcePos.y + (dy / distance) * nodeRadius;
                const endX = targetPos.x - (dx / distance) * nodeRadius;
                const endY = targetPos.y - (dy / distance) * nodeRadius;
                
                const curvature = 0.15 + (conn.mutual ? 0.05 : 0);
                const midX = (startX + endX) / 2;
                const midY = (startY + endY) / 2;
                const perpX = -dy / distance * distance * curvature;
                const perpY = dx / distance * distance * curvature;
                const ctrlX = midX + perpX;
                const ctrlY = midY + perpY;
                
                path.setAttribute('d', `M ${startX} ${startY} Q ${ctrlX} ${ctrlY} ${endX} ${endY}`);
            }
        }
    });
}

function selectNetworkNode(agent) {
    networkState.selectedNode = agent;
    
    document.querySelectorAll('.network-node.selected').forEach(n => n.classList.remove('selected'));
    document.querySelectorAll('.network-connection.highlighted').forEach(c => c.classList.remove('highlighted'));
    
    const nodeEl = document.querySelector(`.network-node[data-id="${agent.id}"]`);
    if (nodeEl) nodeEl.classList.add('selected');
    
    document.querySelectorAll(`.network-connection[data-source="${agent.id}"], .network-connection[data-target="${agent.id}"]`).forEach(conn => {
        conn.classList.add('highlighted');
    });
    
    showNodeInfoPanel(agent);
}

function showNodeInfoPanel(agent) {
    if (!agent) {
        console.error('[wxsperta] showNodeInfoPanel: agent is null');
        return;
    }
    
    const panel = document.getElementById('nodeInfoPanel');
    const iconEl = document.getElementById('nodeInfoIcon');
    const nameEl = document.getElementById('nodeInfoName');
    const descEl = document.getElementById('nodeInfoDesc');
    const connListEl = document.getElementById('connectionList');
    
    if (!panel || !iconEl || !nameEl || !descEl || !connListEl) {
        console.error('[wxsperta] showNodeInfoPanel: DOM elements not found');
        return;
    }
    
    // 내용 채우기
    iconEl.textContent = agent.icon || '🚀';
    nameEl.textContent = agent.name || '에이전트';
    descEl.textContent = agent.shortDesc || agent.description || '설명 없음';
    
    // 연결 목록
    connListEl.innerHTML = '';
    if (agent.connections && agent.connections.length > 0) {
        agent.connections.forEach(connId => {
            const connAgent = state.agents.find(a => 
                a.id === connId || 
                (typeof connId === 'string' && connId.includes(String(a.id).padStart(2, '0')))
            );
            
            if (connAgent) {
                const chip = document.createElement('span');
                chip.className = 'connection-chip';
                chip.textContent = `${connAgent.icon || '🔗'} ${connAgent.name || 'Agent'}`;
                chip.onclick = (e) => {
                    e.stopPropagation();
                    selectNetworkNode(connAgent);
                };
                connListEl.appendChild(chip);
            }
        });
    } else {
        connListEl.innerHTML = '<span style="color: var(--starlight); font-size: 0.85rem;">연결된 에이전트 없음</span>';
    }
    
    // 슬라이드 패널 열기
    panel.classList.add('visible');
    console.log('[wxsperta] Panel opened for:', agent.name);
}

window.hideNodeInfoPanel = function() {
    const panel = document.getElementById('nodeInfoPanel');
    panel.classList.remove('visible');
    networkState.selectedNode = null;
    
    // 탐험 콘텐츠 초기화
    const exploreContent = document.getElementById('exploreContent');
    const exploreFrame = document.getElementById('exploreFrame');
    const exploreBtn = document.getElementById('exploreBtn');
    const exploreLoading = document.getElementById('exploreLoading');
    
    if (exploreContent) {
        exploreContent.classList.remove('active');
    }
    if (exploreFrame) {
        exploreFrame.src = '';
        exploreFrame.style.display = 'none';
    }
    if (exploreBtn) {
        exploreBtn.textContent = '🚀 탐험하기';
    }
    if (exploreLoading) {
        exploreLoading.style.display = 'flex';
        exploreLoading.innerHTML = '<div class="spinner"></div><span>로딩 중...</span>';
    }
    
    document.querySelectorAll('.network-node').forEach(n => {
        const circle = n.querySelector('circle');
        if (circle) {
            circle.setAttribute('stroke', 'rgba(255,255,255,0.2)');
            circle.setAttribute('stroke-width', '2');
        }
    });
    document.querySelectorAll('.network-connection.highlighted').forEach(c => c.classList.remove('highlighted'));
}

function openAgentFromNetwork() {
    if (networkState.selectedNode) {
        openProjectInNewSystem(networkState.selectedNode);
    }
}

// 에이전트 전체 화면으로 열기
function openAgentFullscreen(agent) {
    if (!agent) {
        console.error('[wxsperta] No agent provided');
        return;
    }
    
    console.log('[wxsperta] Opening agent fullscreen:', agent.name);
    
    // 에이전트 경로 생성 (실제 폴더 구조에 맞게)
    const agentId = agent.id || `${String(agent.number).padStart(2, '0')}_agent`;
    const category = agent.category || 'execution';
    
    // 카테고리별 폴더 경로 매핑
    const categoryFolders = {
        'future': 'future_design',
        'future_design': 'future_design',
        'execution': 'execution',
        'branding': 'branding',
        'knowledge': 'knowledge_management',
        'knowledge_management': 'knowledge_management'
    };
    
    const categoryFolder = categoryFolders[category] || 'execution';
    const agentPath = `ai_agents/${categoryFolder}/${agentId}/index.php`;
    
    console.log('[wxsperta] Loading path:', agentPath);
    
    // 전체 화면 모달 생성
    openFullscreenModal(agentPath, agent);
}

// 기존 함수 유지 (호환성)
window.openAgentInPanel = function() {
    if (networkState.selectedNode) {
        openAgentFullscreen(networkState.selectedNode);
    }
}

// 채팅 상태 관리
const chatState = {
    agentId: null,
    history: [],
    isLoading: false,
    currentAgent: null,
    chatInitialized: false
};

// 전체 화면 모달 열기 (기본: iframe, 대화하기 버튼으로 채팅 전환)
function openFullscreenModal(url, agent) {
    // 기존 모달 제거
    const existingModal = document.getElementById('fullscreenExploreModal');
    if (existingModal) existingModal.remove();
    
    // 채팅 상태 초기화
    chatState.agentId = agent.id;
    chatState.history = [];
    chatState.isLoading = false;
    chatState.currentAgent = agent;
    chatState.chatInitialized = false;
    
    // 연결된 에이전트 HTML 생성
    let connectedHtml = '';
    if (agent.connections && agent.connections.length > 0) {
        const links = agent.connections.map(connId => {
            const connAgent = state.agents.find(a => a.id === connId);
            if (connAgent) {
                return `<a href="#" class="conn-agent-link" data-agent-id="${connAgent.id}" title="${connAgent.name}">
                    <span class="conn-icon">${connAgent.icon || '🔗'}</span>
                    <span class="conn-name">${connAgent.name}</span>
                </a>`;
            }
            return '';
        }).filter(Boolean).join('');
        
        if (links) {
            connectedHtml = `<div class="connected-agents"><span class="conn-label">🔗 연결:</span>${links}</div>`;
        }
    }
    
    // 모달 생성 (iframe + 채팅 UI)
    const modal = document.createElement('div');
    modal.id = 'fullscreenExploreModal';
    modal.className = 'fullscreen-modal';
    modal.innerHTML = `
        <div class="fullscreen-modal-header">
            <div class="modal-agent-info">
                <span class="modal-agent-icon">${agent.icon || '🚀'}</span>
                <span class="modal-agent-name">${agent.name || '에이전트'}</span>
            </div>
            ${connectedHtml}
            <div class="modal-actions">
                <button id="startChatBtn" class="start-chat-btn">💬 대화하기</button>
                <button id="backToContentBtn" class="back-to-content-btn" style="display:none;">📄 콘텐츠 보기</button>
                <button class="fullscreen-close-btn" onclick="closeFullscreenModal()">✕ 닫기</button>
            </div>
        </div>
        
        <!-- iframe 콘텐츠 (기본 표시) -->
        <div id="iframeContainer" class="iframe-container">
            <div class="fullscreen-modal-loading">
                <div class="spinner"></div>
                <span>로딩 중...</span>
            </div>
            <iframe class="fullscreen-modal-iframe" src="${url}"></iframe>
        </div>
        
        <!-- 채팅 UI (숨김) -->
        <div id="chatContainer" class="chat-container" style="display:none;">
            <div class="chat-messages" id="chatMessages">
                <div class="chat-loading">
                    <div class="spinner"></div>
                    <span>대화를 준비하고 있어요...</span>
                </div>
            </div>
            <div class="chat-suggestions" id="chatSuggestions"></div>
            <div class="chat-input-area">
                <input type="text" id="chatInput" class="chat-input" placeholder="메시지를 입력하세요..." autocomplete="off">
                <button id="chatSendBtn" class="chat-send-btn">전송</button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.style.overflow = 'hidden';
    
    // iframe 로드 완료 시
    const iframe = modal.querySelector('iframe');
    const loading = modal.querySelector('.fullscreen-modal-loading');
    
    iframe.onload = function() {
        loading.style.display = 'none';
        iframe.style.opacity = '1';
    };
    
    iframe.onerror = function() {
        loading.innerHTML = '<span style="color: #FF6B6B;">❌ 로드 실패</span>';
    };
    
    // 연결된 에이전트 클릭 이벤트
    modal.querySelectorAll('.conn-agent-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('data-agent-id');
            const targetAgent = state.agents.find(a => a.id === targetId);
            if (targetAgent) {
                closeFullscreenModal();
                setTimeout(() => handleAgentClick(targetAgent), 150);
            }
        });
    });
    
    // 대화하기 버튼 이벤트
    document.getElementById('startChatBtn').addEventListener('click', () => switchToChat(agent));
    document.getElementById('backToContentBtn').addEventListener('click', switchToContent);
    
    // ESC 키로 닫기
    const escHandler = (e) => {
        if (e.key === 'Escape') {
            closeFullscreenModal();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
    
    // 애니메이션
    requestAnimationFrame(() => modal.classList.add('visible'));
}

// 채팅 화면으로 전환
function switchToChat(agent) {
    const iframeContainer = document.getElementById('iframeContainer');
    const chatContainer = document.getElementById('chatContainer');
    const startChatBtn = document.getElementById('startChatBtn');
    const backToContentBtn = document.getElementById('backToContentBtn');
    
    // 화면 전환
    iframeContainer.style.display = 'none';
    chatContainer.style.display = 'flex';
    startChatBtn.style.display = 'none';
    backToContentBtn.style.display = 'inline-flex';
    
    // 채팅 초기화 (한 번만)
    if (!chatState.chatInitialized) {
        setupChatEvents(agent);
        loadInitialMessage(agent);
        chatState.chatInitialized = true;
    }
    
    // 입력창에 포커스
    setTimeout(() => {
        const input = document.getElementById('chatInput');
        if (input) input.focus();
    }, 100);
}

// 콘텐츠 화면으로 전환
function switchToContent() {
    const iframeContainer = document.getElementById('iframeContainer');
    const chatContainer = document.getElementById('chatContainer');
    const startChatBtn = document.getElementById('startChatBtn');
    const backToContentBtn = document.getElementById('backToContentBtn');
    
    // 화면 전환
    iframeContainer.style.display = 'flex';
    chatContainer.style.display = 'none';
    startChatBtn.style.display = 'inline-flex';
    backToContentBtn.style.display = 'none';
}

// 채팅 이벤트 설정
function setupChatEvents(agent) {
    const input = document.getElementById('chatInput');
    const sendBtn = document.getElementById('chatSendBtn');
    
    // 전송 버튼 클릭
    sendBtn.addEventListener('click', () => sendChatMessage(agent));
    
    // 엔터 키로 전송
    input.addEventListener('keypress', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatMessage(agent);
        }
    });
}

// 초기 메시지 로드
async function loadInitialMessage(agent) {
    const messagesDiv = document.getElementById('chatMessages');
    const suggestionsDiv = document.getElementById('chatSuggestions');
    
    try {
        const formData = new FormData();
        formData.append('action', 'get_initial');
        formData.append('agent_id', agent.id);
        
        const response = await fetch('agent_chat_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 로딩 제거 및 메시지 표시
            messagesDiv.innerHTML = '';
            addChatMessage('agent', data.message, agent);
            
            // 선택지 표시
            displaySuggestions(data.suggestions, agent);
        } else {
            messagesDiv.innerHTML = `<div class="chat-error">❌ ${data.error}</div>`;
        }
    } catch (error) {
        messagesDiv.innerHTML = `<div class="chat-error">❌ 연결 오류: ${error.message}</div>`;
    }
}

// 메시지 추가
function addChatMessage(role, content, agent) {
    const messagesDiv = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${role}-message`;
    
    if (role === 'agent') {
        // 마크다운 기본 변환 (** 볼드)
        const formattedContent = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        messageDiv.innerHTML = `
            <div class="message-avatar">${agent?.icon || '🤖'}</div>
            <div class="message-content">${formattedContent.replace(/\n/g, '<br>')}</div>
        `;
    } else {
        messageDiv.innerHTML = `
            <div class="message-content">${content}</div>
            <div class="message-avatar">👤</div>
        `;
    }
    
    messagesDiv.appendChild(messageDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}

// 선택지 표시
function displaySuggestions(suggestions, agent) {
    const suggestionsDiv = document.getElementById('chatSuggestions');
    suggestionsDiv.innerHTML = '';
    
    suggestions.forEach(suggestion => {
        const btn = document.createElement('button');
        btn.className = 'suggestion-btn';
        btn.textContent = suggestion;
        btn.addEventListener('click', () => {
            document.getElementById('chatInput').value = suggestion;
            sendChatMessage(agent);
        });
        suggestionsDiv.appendChild(btn);
    });
}

// 메시지 전송
async function sendChatMessage(agent) {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message || chatState.isLoading) return;
    
    chatState.isLoading = true;
    input.value = '';
    
    // 사용자 메시지 표시
    addChatMessage('user', message, agent);
    
    // 선택지 숨기기
    document.getElementById('chatSuggestions').innerHTML = '';
    
    // 로딩 표시
    const messagesDiv = document.getElementById('chatMessages');
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'chat-message agent-message typing';
    loadingDiv.innerHTML = `
        <div class="message-avatar">${agent.icon || '🤖'}</div>
        <div class="message-content"><span class="typing-dots">...</span></div>
    `;
    messagesDiv.appendChild(loadingDiv);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
    
    try {
        // 기록에 추가
        chatState.history.push({ role: 'user', content: message });
        
        const formData = new FormData();
        formData.append('action', 'send_message');
        formData.append('agent_id', agent.id);
        formData.append('message', message);
        formData.append('history', JSON.stringify(chatState.history));
        
        const response = await fetch('agent_chat_api.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        // 로딩 제거
        loadingDiv.remove();
        
        if (data.success) {
            // AI 응답 표시
            addChatMessage('agent', data.message, agent);
            chatState.history.push({ role: 'assistant', content: data.message });
            
            // 새 선택지 표시
            if (data.suggestions) {
                displaySuggestions(data.suggestions, agent);
            }
        } else {
            addChatMessage('agent', `❌ 오류: ${data.error}`, agent);
        }
    } catch (error) {
        loadingDiv.remove();
        addChatMessage('agent', `❌ 연결 오류: ${error.message}`, agent);
    }
    
    chatState.isLoading = false;
}

// 전체 화면 모달 닫기
window.closeFullscreenModal = function() {
    const modal = document.getElementById('fullscreenExploreModal');
    if (modal) {
        modal.classList.remove('visible');
        setTimeout(() => {
            modal.remove();
            document.body.style.overflow = '';
        }, 300);
    }
}

function zoomNetwork(factor) {
    networkState.zoom *= factor;
    networkState.zoom = Math.max(0.5, Math.min(2, networkState.zoom));
    
    const svg = document.getElementById('networkSvg');
    const nodesGroup = document.getElementById('nodesGroup');
    const connectionsGroup = document.getElementById('connectionsGroup');
    
    const centerX = svg.clientWidth / 2;
    const centerY = svg.clientHeight / 2;
    
    nodesGroup.setAttribute('transform', `translate(${centerX * (1 - networkState.zoom)}, ${centerY * (1 - networkState.zoom)}) scale(${networkState.zoom})`);
    connectionsGroup.setAttribute('transform', `translate(${centerX * (1 - networkState.zoom)}, ${centerY * (1 - networkState.zoom)}) scale(${networkState.zoom})`);
}

function resetNetworkView() {
    networkState.zoom = 1;
    networkState.selectedNode = null;
    networkState.nodePositions = {};
    networkState.draggedNode = null;
    
    document.getElementById('nodesGroup').setAttribute('transform', '');
    document.getElementById('connectionsGroup').setAttribute('transform', '');
    document.getElementById('nodeInfoPanel').classList.remove('visible');
    
    document.querySelectorAll('.network-node.selected').forEach(n => n.classList.remove('selected'));
    document.querySelectorAll('.network-connection.highlighted').forEach(c => c.classList.remove('highlighted'));
    
    // 초기 위치 재설정 후 렌더링
    renderNetworkView();
}

function toggleAnimation() {
    // 정적 모드에서는 사용하지 않음
}

// ==================== 그리드 뷰 ====================
function renderAgentGrid() {
    const grid = document.getElementById('missionGrid');
    grid.innerHTML = '';
    
    const groupedMissions = {};
    state.agents.forEach(agent => {
        const cat = agent.category || 'other';
        if (!groupedMissions[cat]) groupedMissions[cat] = [];
        groupedMissions[cat].push(agent);
    });
    
    const sectorOrder = ['future_design', 'execution', 'branding', 'knowledge_management'];
    
    sectorOrder.forEach(category => {
        const missions = groupedMissions[category];
        if (!missions || missions.length === 0) return;
        
        const section = document.createElement('div');
        section.className = 'sector-section';
        
        const title = document.createElement('div');
        title.className = 'sector-title';
        title.style.borderColor = sectorGroups[category]?.color || '#ccc';
        title.innerHTML = `
            <span style="color: ${sectorGroups[category]?.color}">${sectorGroups[category]?.title}</span>
            <span style="font-size: 0.75rem; opacity: 0.7;">${sectorGroups[category]?.subtitle}</span>
        `;
        
        const sectorGrid = document.createElement('div');
        sectorGrid.className = 'sector-grid';
        
        missions.forEach((agent, idx) => {
            const card = createMissionCard(agent, category, idx);
            sectorGrid.appendChild(card);
        });
        
        section.appendChild(title);
        section.appendChild(sectorGrid);
        grid.appendChild(section);
    });
}

function createMissionCard(agent, category, idx) {
    const div = document.createElement('div');
    div.id = `agent-card-${agent.id}`;
    div.className = 'mission-card';
    div.setAttribute('data-category', category);
    div.onclick = () => handleAgentClick(agent);
    
    if (state.recommendedCards.includes(agent.id)) div.classList.add('recommended');
    if (state.agentProperties[agent.id]) div.classList.add('has-data');
    
    const icons = missionIcons[category] || ['🎯'];
    const iconIdx = idx % icons.length;
    const displayIcon = agent.icon || icons[iconIdx];
    
    div.innerHTML = `
        <div class="mission-icon">${displayIcon}</div>
        <div class="mission-name">${agent.shortDesc || agent.name}</div>
        <div class="status-dot"></div>
        <div class="action-buttons">
            <button class="action-btn" title="관제탑" onclick="event.stopPropagation(); openChat(state.agents.find(a=>a.id===${agent.id}))">📡</button>
            <button class="action-btn" title="탐험" onclick="event.stopPropagation(); openProjectInNewSystem(state.agents.find(a=>a.id===${agent.id}))">🚀</button>
        </div>
    `;
    
    return div;
}

function initMoodChecker() {
    const moodBtns = document.querySelectorAll('.mood-btn');
    const responseEl = document.getElementById('moodResponse');
    
    moodBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            moodBtns.forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');
            
            const mood = this.dataset.mood;
            responseEl.textContent = moodResponses[mood] || '';
            responseEl.style.opacity = '0';
            setTimeout(() => {
                responseEl.style.transition = 'opacity 0.3s';
                responseEl.style.opacity = '1';
            }, 50);
            
            state.currentMood = mood;
        });
    });
}

// ==================== 프로젝트 팝업 ====================
function openProjectInNewSystem(agent) {
    const categoryPath = phpData.categoryPaths[agent.category] || agent.category;
    const agentFolder = agentNameMap[agent.id];
    const url = `ai_agents/${categoryPath}/${agentFolder}/index.php`;
    showProjectPopup(url, agent.name);
}

function showProjectPopup(url, agentName) {
    const existingPopup = document.getElementById('projectPopup');
    if (existingPopup) existingPopup.remove();
    
    const popupOverlay = document.createElement('div');
    popupOverlay.id = 'projectPopup';
    popupOverlay.className = 'fixed inset-0 bg-black bg-opacity-0 flex items-center justify-center z-50 transition-all duration-300';
    popupOverlay.style.backdropFilter = 'blur(0px)';
    
    const popupContainer = document.createElement('div');
    popupContainer.className = 'bg-white rounded-2xl shadow-2xl w-11/12 h-5/6 max-w-6xl max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300';
    popupContainer.onclick = (e) => e.stopPropagation();
    
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
    
    const iframeContainer = document.createElement('div');
    iframeContainer.className = 'flex-1 overflow-hidden relative';
    
    const loadingDiv = document.createElement('div');
    loadingDiv.className = 'absolute inset-0 flex items-center justify-center bg-white';
    loadingDiv.innerHTML = `
        <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
            <p class="mt-4 text-gray-600">프로젝트 로딩 중...</p>
        </div>
    `;
    iframeContainer.appendChild(loadingDiv);
    
    const iframe = document.createElement('iframe');
    iframe.src = url;
    iframe.className = 'w-full h-full border-0';
    iframe.style.backgroundColor = 'white';
    iframe.onload = () => loadingDiv.remove();
    
    iframeContainer.appendChild(iframe);
    popupContainer.appendChild(popupHeader);
    popupContainer.appendChild(iframeContainer);
    popupOverlay.appendChild(popupContainer);
    popupOverlay.onclick = () => closeProjectPopup();
    
    document.body.appendChild(popupOverlay);
    document.body.style.overflow = 'hidden';
    
    setTimeout(() => {
        popupOverlay.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 transition-all duration-300';
        popupOverlay.style.backdropFilter = 'blur(5px)';
        popupContainer.className = 'bg-white rounded-2xl shadow-2xl w-11/12 h-5/6 max-w-6xl max-h-[90vh] flex flex-col transform scale-100 opacity-100 transition-all duration-300';
    }, 10);
    
    document.addEventListener('keydown', handleProjectPopupEsc);
}

function handleProjectPopupEsc(e) {
    if (e.key === 'Escape') closeProjectPopup();
}

function closeProjectPopup() {
    const popup = document.getElementById('projectPopup');
    if (popup) {
        const popupContainer = popup.querySelector('.bg-white');
        popup.className = 'fixed inset-0 bg-black bg-opacity-0 flex items-center justify-center z-50 transition-all duration-300';
        popup.style.backdropFilter = 'blur(0px)';
        if (popupContainer) {
            popupContainer.className = 'bg-white rounded-2xl shadow-2xl w-11/12 h-5/6 max-w-6xl max-h-[90vh] flex flex-col transform scale-95 opacity-0 transition-all duration-300';
        }
        setTimeout(() => {
            popup.remove();
            document.body.style.overflow = 'auto';
        }, 300);
        document.removeEventListener('keydown', handleProjectPopupEsc);
    }
}

// ==================== 에이전트 클릭 & 모달 ====================
function handleAgentClick(agent) {
    console.log('Agent clicked:', agent.name, 'Role:', phpData.role);
    
    // 바로 전체 화면 모달로 에이전트 페이지 열기
    openAgentFullscreen(agent);
}

function showModal() {
    const modalOverlay = document.getElementById('modalOverlay');
    modalOverlay.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    renderPropertyView();
}

function renderPropertyView() {
    const modalBody = document.getElementById('modalBody');
    const agent = state.selectedAgent;
    const properties = state.agentProperties[agent.id] || defaultProperties[agent.id] || {};

    modalBody.innerHTML = `
        <div class="sticky top-0 z-10 flex items-center p-5 border-b bg-white rounded-t-2xl" style="background: linear-gradient(135deg, #f8faf8 0%, #e8f5e9 100%);">
            <div style="width: 56px; height: 56px; border-radius: 12px; background: white; display: flex; align-items: center; justify-content: center; margin-right: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
                <span style="font-size: 1.75rem;">${agent.icon}</span>
            </div>
            <div class="flex-1">
                <h2 style="font-size: 1.25rem; font-weight: 600; color: #2E4A3A;">${agent.name}</h2>
                <p style="font-size: 0.8rem; color: #666; margin-top: 4px;">${agent.shortDesc || agent.description}</p>
            </div>
            <button onclick="openProjectInNewSystem(state.selectedAgent)" 
                style="padding: 0.5rem 1rem; font-size: 0.8rem; background: #43A047; color: white; border: none; border-radius: 8px; cursor: pointer; margin-right: 0.5rem;"
                title="프로젝트 보기">📂 프로젝트</button>
            <button onclick="handleCancel()" 
                style="width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e0e0e0; background: white; cursor: pointer; display: flex; align-items: center; justify-content: center;">✕</button>
        </div>
        
        <div class="p-6 overflow-y-auto" style="max-height: calc(90vh - 200px);">
            <div class="space-y-4">
                ${Object.entries(propertyLabels).map(([key, label]) => `
                    <div class="space-y-2">
                        <div><h3 class="font-semibold text-gray-700">${label.title}</h3><p class="text-xs text-gray-500">${label.desc}</p></div>
                        <textarea id="prop_${key}" class="w-full p-3 border border-gray-300 rounded-lg text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-transparent transition-all duration-200" rows="3" placeholder="${label.title}을 입력하세요...">${properties[key] || ''}</textarea>
                    </div>
                `).join('')}
            </div>
        </div>
        
        <div id="messageDisplay" class="px-6 py-3 bg-blue-50 border-t" style="display: none;"><p class="text-sm text-center"></p></div>
        
        <div class="sticky bottom-0 z-10 flex gap-3 p-5 border-t bg-white rounded-b-2xl">
            <button onclick="handleSave()" id="saveButton" style="flex: 1; padding: 0.75rem 1.5rem; background: #43A047; color: white; border: none; border-radius: 10px; font-size: 0.9rem; font-weight: 500; cursor: pointer;">🌱 저장하기</button>
            <button onclick="handleCancel()" style="flex: 1; padding: 0.75rem 1.5rem; background: white; color: #666; border: 1px solid #e0e0e0; border-radius: 10px; font-size: 0.9rem; font-weight: 500; cursor: pointer;">취소</button>
        </div>
    `;
}

async function handleSave() {
    state.loading = true;
    const saveButton = document.getElementById('saveButton');
    saveButton.disabled = true;
    saveButton.textContent = '저장 중...';
    
    const properties = {};
    Object.keys(propertyLabels).forEach(key => {
        const textarea = document.getElementById(`prop_${key}`);
        if (textarea) properties[key] = textarea.value;
    });
    
    try {
        const response = await fetch(phpData.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_agent_properties',
                agent_id: state.selectedAgent.id,
                user_id: phpData.userId,
                properties: properties
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (phpData.role === 'teacher' && window.versionControl) {
                try {
                    await window.versionControl.createCommit(`${state.selectedAgent.name} - 속성 수정`, false);
                } catch (e) {
                    console.error('Auto-commit failed:', e);
                }
            }
            showMessage('✅ 속성이 성공적으로 저장되었습니다!');
            setTimeout(() => handleCancel(), 1500);
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

function showMessage(message) {
    const messageDisplay = document.getElementById('messageDisplay');
    const messageText = messageDisplay.querySelector('p');
    messageText.textContent = message;
    messageDisplay.style.display = 'block';
}

function handleCancel() {
    state.selectedAgent = null;
    document.getElementById('modalOverlay').style.display = 'none';
    document.body.style.overflow = 'unset';
}

// ==================== 채팅 ====================
function openChat(agent) {
    if (window.versionControl && window.versionControl.elements && 
        window.versionControl.elements.panel.classList.contains('open')) {
        window.versionControl.closePanel();
    }
    
    if (state.activeAgentCard) state.activeAgentCard.classList.remove('highlighted');
    
    const currentCard = document.getElementById(`agent-card-${agent.id}`);
    if (currentCard) {
        currentCard.classList.add('highlighted');
        state.activeAgentCard = currentCard;
    }
    
    state.chatAgent = agent;
    state.showChat = true;
    
    if (!state.agentProperties[agent.id] && defaultProperties[agent.id]) {
        state.agentProperties[agent.id] = defaultProperties[agent.id];
    }
    
    renderChat();
    
    document.getElementById('mainContainer').classList.add('shifted');
    document.getElementById('chatPanel').classList.add('open');
    adjustGrid();
}

function renderChat() {
    const chatPanel = document.getElementById('chatPanel');
    const agent = state.chatAgent;
    
    chatPanel.innerHTML = `
        <div class="chat-header" style="background: linear-gradient(135deg, var(--space-mid) 0%, var(--space-dark) 100%); border-bottom: 1px solid rgba(255,255,255,0.1); padding: 1rem; display: flex; align-items: center;">
            <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; margin-right: 0.75rem;">${agent.icon}</div>
            <div style="flex: 1;">
                <h3 style="font-weight: 600; color: var(--moon); font-size: 0.95rem;">📡 ${agent.name}</h3>
                <p style="font-size: 0.7rem; color: var(--starlight); margin-top: 3px;">${agent.shortDesc || agent.description}</p>
            </div>
            <button onclick="openProjectInNewSystem(state.chatAgent)" style="width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); cursor: pointer; margin-right: 0.5rem; font-size: 0.9rem;" title="탐험하기">🚀</button>
            <button onclick="window.handleChatClose()" style="width: 34px; height: 34px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); cursor: pointer; color: var(--starlight);">✕</button>
        </div>
        
        <div id="chatContent" class="flex-1 overflow-y-auto p-4">
            <div id="agentPropertiesDisplay" class="mb-4 p-4 bg-blue-50 rounded-lg" style="display: none;">
                <h4 class="font-semibold mb-3 flex justify-between items-center">현재 에이전트 속성
                    <div>
                        <button id="initPropertiesBtn" onclick="generateInitialValues()" class="px-3 py-1 bg-green-500 text-white rounded text-sm hover:bg-green-600 mr-2" style="display: none;">초기값 자동생성</button>
                        <button onclick="improveProperties()" class="px-3 py-1 bg-purple-500 text-white rounded text-sm hover:bg-purple-600">개선</button>
                    </div>
                </h4>
                <div id="propertiesContainer"></div>
            </div>
            <div id="messageContainer" class="space-y-3"></div>
        </div>
        
        <div class="border-t p-4">
            <div class="flex gap-2">
                <textarea id="messageInput" placeholder="메시지를 입력하세요..." class="flex-1 resize-none border rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-400" rows="2" onkeypress="handleKeyPress(event)"></textarea>
                <button onclick="sendMessage()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">전송</button>
            </div>
        </div>
    `;
    
    displayAgentProperties();
    
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

window.handleChatClose = function() {
    state.showChat = false;
    state.chatAgent = null;
    
    if (state.activeAgentCard) {
        state.activeAgentCard.classList.remove('highlighted');
        state.activeAgentCard = null;
    }
    
    document.getElementById('mainContainer').classList.remove('shifted');
    document.getElementById('chatPanel').classList.remove('open');
    adjustGrid();
}

function addMessage(type, content, action = null) {
    const container = document.getElementById('messageContainer');
    const messageDiv = document.createElement('div');
    messageDiv.className = `flex ${type === 'user' ? 'justify-end' : 'justify-start'}`;
    
    const bubbleDiv = document.createElement('div');
    bubbleDiv.className = `max-w-[80%] rounded-lg p-3 ${
        type === 'user' ? 'bg-blue-500 text-white' : 
        type === 'system' ? 'bg-amber-50 border border-amber-200' : 'bg-gray-100'
    }`;
    
    const messageId = 'msg-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9);
    messageDiv.id = messageId;
    messageDiv.dataset.content = content;
    messageDiv.dataset.type = type;
    
    bubbleDiv.innerHTML = `
        <div class="message-content" onclick="startEditMessage('${messageId}', '${type}')" title="클릭하여 편집"><p class="whitespace-pre-wrap">${content}</p></div>
        ${action ? `<div class="mt-2 pt-2 border-t border-gray-200"><button onclick="${action.onclick}" class="px-3 py-1 ${action.class} rounded text-sm">${action.text}</button></div>` : ''}
        <p class="text-xs mt-1 ${type === 'user' ? 'text-blue-100' : type === 'system' ? 'text-amber-600' : 'text-gray-500'}">${new Date().toLocaleTimeString('ko-KR', { hour: '2-digit', minute: '2-digit' })}</p>
    `;
    
    messageDiv.appendChild(bubbleDiv);
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
}

async function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if (!message || !state.chatAgent) return;
    
    addMessage('user', message);
    input.value = '';
    
    state.chatHistory.push({ type: 'user', content: message, agentId: state.chatAgent.id });
    analyzeAndRecommendCards(message);
    showLoading();
    
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

function handleKeyPress(event) {
    if (event.key === 'Enter' && !event.shiftKey) {
        event.preventDefault();
        sendMessage();
    }
}

function showLoading() {
    const loader = document.createElement('div');
    loader.id = 'loadingIndicator';
    loader.className = 'flex justify-start';
    loader.innerHTML = `<div class="bg-gray-100 rounded-lg p-3"><div class="flex space-x-2"><div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce"></div><div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div><div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div></div></div>`;
    document.getElementById('messageContainer').appendChild(loader);
}

function hideLoading() {
    const loader = document.getElementById('loadingIndicator');
    if (loader) loader.remove();
}

function adjustGrid() {
    const grid = document.getElementById('agentGrid');
    if (!grid) return;
    
    if (window.innerWidth < 1024) {
        grid.classList.remove('grid-cols-7');
        grid.classList.add('grid-cols-5');
    } else {
        grid.classList.remove('grid-cols-5');
        grid.classList.add('grid-cols-7');
    }
}

function analyzeAndRecommendCards(message) {
    state.recommendedCards = [];
    updateRecommendedCards();
    
    const keywordMap = {
        '시간': [2, 7, 11], '계획': [2, 3, 7], '목표': [1, 4, 13], '미래': [1, 3, 15],
        '학습': [9, 16, 17], '분석': [3, 6, 9], '실행': [7, 11, 5], '성장': [3, 13, 15],
        '동기': [5, 13, 8], '정리': [10, 11, 16], '브랜딩': [8, 12, 14],
        '지식': [16, 17, 18, 19, 20], '자동화': [11, 21], '전략': [6, 14]
    };
    
    const foundKeywords = [];
    Object.keys(keywordMap).forEach(keyword => {
        if (message.toLowerCase().includes(keyword)) foundKeywords.push(keyword);
    });
    
    const recommendations = new Set();
    foundKeywords.forEach(keyword => {
        keywordMap[keyword].forEach(id => {
            if (id !== state.chatAgent.id) recommendations.add(id);
        });
    });
    
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

function updateRecommendedCards() {
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

async function generateAIResponse(userMessage) {
    const agent = state.chatAgent;
    const properties = state.agentProperties[agent.id] || {};
    const worldView = properties.worldView || defaultProperties[agent.id]?.worldView || '';
    const context = properties.context || defaultProperties[agent.id]?.context || '';
    
    const responseTemplates = {
        1: `미래 비전의 관점에서 보면, ${userMessage}에 대한 접근은 장기적 목표와 연결되어야 합니다. ${worldView}`,
        2: `시간 관리 측면에서, ${userMessage}를 효율적으로 처리하려면 체계적인 계획이 필요합니다. ${context}`,
        3: `성장의 관점에서, 이는 새로운 도약의 기회입니다. ${worldView}`,
        5: `동기부여 관점에서, ${userMessage}는 내적 열정과 연결될 때 진정한 힘을 발휘합니다.`,
        6: `전략적 분석을 통해 보면, 강점을 활용하고 약점을 보완하는 접근이 필요합니다.`,
        default: `${agent.name}의 관점에서, ${userMessage}에 대해 ${worldView || '깊이 있는 통찰'}을 제공하겠습니다.`
    };
    
    return responseTemplates[agent.id] || responseTemplates.default;
}

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

// ==================== 메시지 편집 ====================
function startEditMessage(messageId, messageType) {
    const messageDiv = document.getElementById(messageId);
    if (!messageDiv) return;
    
    const messageContent = messageDiv.querySelector('.message-content');
    const currentContent = messageDiv.dataset.content;
    
    if (messageContent.classList.contains('editing')) return;
    
    messageContent.classList.add('editing');
    messageContent.innerHTML = `
        <textarea class="message-edit-textarea" id="edit-${messageId}">${currentContent}</textarea>
        <div class="message-edit-buttons">
            <button onclick="saveEditMessage('${messageId}')" class="px-3 py-1 bg-blue-500 text-white rounded text-sm hover:bg-blue-600">저장</button>
            <button onclick="cancelEditMessage('${messageId}')" class="px-3 py-1 bg-gray-300 text-gray-700 rounded text-sm hover:bg-gray-400">취소</button>
        </div>
    `;
    
    const textarea = document.getElementById(`edit-${messageId}`);
    textarea.focus();
    textarea.setSelectionRange(textarea.value.length, textarea.value.length);
    
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

function saveEditMessage(messageId) {
    const messageDiv = document.getElementById(messageId);
    if (!messageDiv) return;
    
    const textarea = document.getElementById(`edit-${messageId}`);
    const newContent = textarea.value.trim();
    
    if (!newContent) {
        cancelEditMessage(messageId);
        return;
    }
    
    messageDiv.dataset.content = newContent;
    
    const messageContent = messageDiv.querySelector('.message-content');
    messageContent.classList.remove('editing');
    messageContent.innerHTML = `<p class="whitespace-pre-wrap">${newContent}</p>`;
    
    if (messageDiv.dataset.type !== 'system') {
        addMessage('system', '✏️ 메시지가 편집되었습니다.');
    }
}

function cancelEditMessage(messageId) {
    const messageDiv = document.getElementById(messageId);
    if (!messageDiv) return;
    
    const messageContent = messageDiv.querySelector('.message-content');
    const originalContent = messageDiv.dataset.content;
    
    messageContent.classList.remove('editing');
    messageContent.innerHTML = `<p class="whitespace-pre-wrap">${originalContent}</p>`;
}

// ==================== 속성 관리 ====================
async function generateInitialValues() {
    const agent = state.chatAgent;
    showLoading();
    
    try {
        const contextualProperties = await generateContextualProperties(agent);
        state.agentProperties[agent.id] = contextualProperties;
        
        hideLoading();
        addMessage('system', '✅ 초기값이 생성되었습니다!');
        displayAgentProperties();
        await saveGeneratedProperties();
    } catch (error) {
        hideLoading();
        addMessage('system', '❌ 초기값 생성에 실패했습니다.');
    }
}

async function improveProperties() {
    const agent = state.chatAgent;
    const currentProperties = state.agentProperties[agent.id];
    
    state.previousProperties[agent.id] = { ...currentProperties };
    showLoading();
    
    try {
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
        await saveGeneratedProperties();
    } catch (error) {
        hideLoading();
        addMessage('system', '❌ 속성 개선에 실패했습니다.');
    }
}

async function revertProperties() {
    const agent = state.chatAgent;
    if (state.previousProperties[agent.id]) {
        state.agentProperties[agent.id] = state.previousProperties[agent.id];
        delete state.previousProperties[agent.id];
        
        addMessage('system', '↩️ 이전 속성으로 되돌렸습니다.');
        displayAgentProperties();
        await saveGeneratedProperties();
    }
}

async function saveGeneratedProperties() {
    const agent = state.chatAgent;
    const properties = state.agentProperties[agent.id];
    
    try {
        const response = await fetch(phpData.apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'save_agent_properties',
                agent_id: agent.id,
                user_id: phpData.userId,
                properties: properties
            })
        });
        
        const result = await response.json();
        if (!result.success) console.error('Failed to save properties:', result.error);
    } catch (error) {
        console.error('Save error:', error);
    }
}

async function generateContextualProperties(agent) {
    const templates = {
        1: { worldView: '미래의 나는 현재의 모든 선택과 행동의 집합체다.', context: '학생의 현재 상황과 미래 비전 사이의 연결고리를 찾는다.', structure: '미래 자아 스토리텔링 → 현재 행동 매핑', process: '1) 5년 후 이상적 자아 구체화 2) 현재와의 갭 분석', execution: '매일 아침 미래 자아와의 대화', reflection: '미래 비전의 현실성 평가', transfer: '성공적인 미래 설계 스토리 공유', abstraction: '미래에 대한 구체적 상상력이 현재의 행동력을 결정한다' },
        2: { worldView: '모든 큰 목표는 작은 단계들의 정교한 조합이다.', context: '복잡한 목표를 달성 가능한 단위로 분해한다.', structure: '목표 분해 트리 → 시간 블록 할당', process: '1) 최종 목표 정의 2) 역산 분해 3) 간트차트 작성', execution: '주간 계획 수립 세션', reflection: '계획 대비 실행률 분석', transfer: '효과적인 프로젝트 계획 템플릿 문서화', abstraction: '체계적 계획과 유연한 실행의 균형' },
        3: { worldView: '성장은 단순한 축적이 아닌 질적 도약이다.', context: '현재의 성장 패턴을 분석하여 돌파구를 찾는다.', structure: '성장 지표 정의 → 데이터 수집 체계', process: '1) 다차원 성장 지표 설정 2) 일일 데이터 입력', execution: '성장 일지 작성, 주요 지표 트래킹', reflection: '성장 속도와 질의 균형 평가', transfer: '개인별 성장 패턴 케이스 스터디 공유', abstraction: '측정 가능한 것만이 개선 가능하다' },
        default: { worldView: `${agent.name}의 핵심 철학`, context: `${agent.description}을 위한 구체적 상황`, structure: `목표 달성을 위한 체계적 구조`, process: `단계별 실행 프로세스`, execution: `일상적 실행 방법`, reflection: `성찰과 개선을 위한 평가 체계`, transfer: `지식과 경험의 공유 방법`, abstraction: `핵심 원리와 통찰의 추상화` }
    };
    
    return templates[agent.id] || templates.default;
}

// ==================== 초기화 실행 ====================
document.addEventListener('DOMContentLoaded', () => {
    init();
    
    // 버전 관리 시스템 초기화
    if (typeof initVersionControl === 'function') {
        window.versionControl = initVersionControl({
            apiUrl: 'version_api.php',
            userRole: phpData.role,
            userId: phpData.userId
        });
    }
});

