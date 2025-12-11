/**
 * 양자 붕괴 학습 미로 (Quantum Collapse Learning Maze)
 * 순수 JavaScript 구현
 */

// ========== 상수 정의 ==========

// 기본 개념 (AI가 문제 분석 후 동적으로 확장 가능)
let CONCEPTS = {
    inequality: { id: 'inequality', name: '부등식 설정', icon: '📐', color: '#06b6d4' },
    comparison: { id: 'comparison', name: '대소 비교', icon: '⚖️', color: '#8b5cf6' },
    transpose: { id: 'transpose', name: '이항 정리', icon: '↔️', color: '#f59e0b' },
    factorize: { id: 'factorize', name: '인수분해', icon: '🧩', color: '#10b981' },
    roots: { id: 'roots', name: '근 찾기', icon: '🎯', color: '#ec4899' },
    sign: { id: 'sign', name: '부호 판단', icon: '±', color: '#ef4444' },
    interval: { id: 'interval', name: '구간 해석', icon: '📊', color: '#3b82f6' },
    graph: { id: 'graph', name: '그래프 해석', icon: '📈', color: '#14b8a6' },
};

// 기본 노드 (AI 분석 후 동적으로 생성/확장)
let NODES = {};
let EDGES = [];

// ========== 상태 관리 ==========
const state = {
    currentStage: 0,
    selectedPath: ['start'],
    activatedConcepts: new Set(),
    collapsingConcept: null,
    stateVector: { alpha: 0.33, beta: 0.33, gamma: 0.34 },
    isComplete: false,
    pathHistory: [],
    isLoading: true,
    questionData: null,
    userNodes: [], // 사용자가 생성한 노드들
    learnerTags: {}, // 학습자 유형 태그
};

// ========== 초기화 ==========
document.addEventListener('DOMContentLoaded', async () => {
    console.log('[QuantumMaze] 초기화 시작');
    
    // 초기 데이터 로드
    const initialData = window.QUANTUM_DATA || {};
    state.questionData = initialData.questionData;
    
    // 문제 정보 표시
    displayQuestionInfo(initialData);
    
    // AI 분석으로 노드/엣지 생성
    await analyzeAndGenerateMaze(initialData);
    
    // 초기 히스토리 저장
    state.pathHistory = [{
        path: ['start'],
        state: { ...state.stateVector },
        concepts: new Set()
    }];
    
    // UI 렌더링
    renderConceptPanel();
    renderMaze();
    renderChoices();
    
    // 로딩 완료
    hideLoading();
});

// ========== AI 분석 ==========
async function analyzeAndGenerateMaze(data) {
    updateLoadingStatus('AI가 문제를 분석하고 있습니다...');
    
    try {
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/api/analyze_quantum_path.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                contentsId: data.contentsId,
                questionData: data.questionData,
                imageUrl: data.imageUrl
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.data) {
            // AI가 생성한 노드와 엣지 적용
            if (result.data.concepts) {
                CONCEPTS = { ...CONCEPTS, ...result.data.concepts };
            }
            if (result.data.nodes) {
                NODES = result.data.nodes;
            }
            if (result.data.edges) {
                EDGES = result.data.edges;
            }
            
            console.log('[QuantumMaze] AI 분석 완료:', { 
                concepts: Object.keys(CONCEPTS).length,
                nodes: Object.keys(NODES).length,
                edges: EDGES.length
            });
        } else {
            console.warn('[QuantumMaze] AI 분석 실패, 기본 데이터 사용');
            useDefaultMaze();
        }
    } catch (error) {
        console.error('[QuantumMaze] AI 분석 오류:', error);
        useDefaultMaze();
    }
}

// 기본 미로 데이터
function useDefaultMaze() {
    NODES = {
        start: { id: 'start', x: 350, y: 40, label: '문제 인식', type: 'start', stage: 0, concepts: [] },
        s1_c: { id: 's1_c', x: 180, y: 120, label: '부등식 인식', type: 'correct', stage: 1, concepts: ['inequality', 'comparison'] },
        s1_m: { id: 's1_m', x: 350, y: 120, label: '교점만 생각', type: 'partial', stage: 1, concepts: ['graph'] },
        s1_x: { id: 's1_x', x: 520, y: 120, label: '문제 혼란', type: 'confused', stage: 1, concepts: [] },
        s2_c1: { id: 's2_c1', x: 100, y: 210, label: 'f(x)>g(x)', type: 'correct', stage: 2, concepts: ['inequality', 'comparison'] },
        s2_c2: { id: 's2_c2', x: 230, y: 210, label: '그래프 접근', type: 'partial', stage: 2, concepts: ['graph', 'comparison'] },
        s2_m1: { id: 's2_m1', x: 350, y: 210, label: 'f(x)<g(x)', type: 'wrong', stage: 2, concepts: ['inequality'] },
        s2_m2: { id: 's2_m2', x: 470, y: 210, label: 'f(x)=g(x)', type: 'wrong', stage: 2, concepts: ['roots'] },
        s2_x1: { id: 's2_x1', x: 580, y: 210, label: '막막함', type: 'confused', stage: 2, concepts: [] },
        s3_c: { id: 's3_c', x: 120, y: 310, label: 'x²-3x-10>0', type: 'correct', stage: 3, concepts: ['transpose', 'inequality'] },
        s3_p: { id: 's3_p', x: 260, y: 310, label: '시각적 정리', type: 'partial', stage: 3, concepts: ['graph', 'transpose'] },
        s3_m1: { id: 's3_m1', x: 400, y: 310, label: '부호 오류', type: 'wrong', stage: 3, concepts: ['transpose'] },
        s3_m2: { id: 's3_m2', x: 530, y: 310, label: '등식만 품', type: 'wrong', stage: 3, concepts: ['factorize', 'roots'] },
        s4_c: { id: 's4_c', x: 140, y: 410, label: 'x=-2, 5', type: 'correct', stage: 4, concepts: ['factorize', 'roots'] },
        s4_p: { id: 's4_p', x: 280, y: 410, label: '그래프 추정', type: 'partial', stage: 4, concepts: ['graph', 'roots'] },
        s4_m: { id: 's4_m', x: 420, y: 410, label: '근만 구함', type: 'wrong', stage: 4, concepts: ['factorize', 'roots'] },
        s4_m2: { id: 's4_m2', x: 550, y: 410, label: '잘못된 근', type: 'wrong', stage: 4, concepts: ['roots'] },
        success: { id: 'success', x: 180, y: 510, label: '💥 x<-2, x>5', type: 'success', stage: 5, concepts: ['sign', 'interval'] },
        partial_s: { id: 'partial_s', x: 320, y: 510, label: '✨ 정답', type: 'success', stage: 5, concepts: ['graph', 'interval'] },
        fail_m1: { id: 'fail_m1', x: 450, y: 510, label: '❌ -2<x<5', type: 'fail', stage: 5, concepts: ['sign', 'interval'] },
        fail_m2: { id: 'fail_m2', x: 570, y: 510, label: '❌ 오답', type: 'fail', stage: 5, concepts: ['interval'] },
    };
    
    EDGES = [
        ['start', 's1_c'], ['start', 's1_m'], ['start', 's1_x'],
        ['s1_c', 's2_c1'], ['s1_c', 's2_c2'], ['s1_m', 's2_m1'], ['s1_m', 's2_m2'], ['s1_x', 's2_x1'],
        ['s2_c1', 's3_c'], ['s2_c2', 's3_p'], ['s2_m1', 's3_m1'], ['s2_m2', 's3_m2'], ['s2_x1', 's3_p'],
        ['s3_c', 's4_c'], ['s3_p', 's4_p'], ['s3_m1', 's4_m'], ['s3_m2', 's4_m2'],
        ['s4_c', 'success'], ['s4_p', 'partial_s'], ['s4_m', 'fail_m1'], ['s4_m2', 'fail_m2'],
    ];
}

// ========== 문제 정보 표시 ==========
function displayQuestionInfo(data) {
    const questionText = document.getElementById('question-text');
    const questionImage = document.getElementById('question-image');
    const noImage = document.getElementById('no-image');
    
    // 이미지 표시
    if (data.imageUrl) {
        questionImage.src = data.imageUrl;
        questionImage.classList.remove('hidden');
        noImage.classList.add('hidden');
        questionImage.onerror = () => {
            questionImage.classList.add('hidden');
            noImage.classList.remove('hidden');
        };
    }
    
    // 텍스트 표시
    if (data.questionData) {
        const text = data.questionData.question_text || data.questionData.narration_text || '';
        if (text) {
            // @ 기호로 분리된 경우 첫 번째 부분만 표시
            const firstPart = text.split('@')[0].trim();
            questionText.textContent = firstPart.substring(0, 200) + (firstPart.length > 200 ? '...' : '');
        }
    }
}

// ========== 개념 패널 렌더링 ==========
function renderConceptPanel() {
    const container = document.getElementById('concept-list');
    container.innerHTML = '';
    
    Object.values(CONCEPTS).forEach(concept => {
        const isActive = state.activatedConcepts.has(concept.id);
        const isCollapsing = state.collapsingConcept === concept.id;
        
        const item = document.createElement('div');
        item.className = `concept-item relative flex items-center gap-2 px-3 py-2 rounded-lg transition-all duration-500 ${isActive ? 'active' : 'bg-slate-800/50'} ${isCollapsing ? 'collapsing' : ''}`;
        item.id = `concept-${concept.id}`;
        
        item.innerHTML = `
            ${isActive ? `<div class="absolute inset-0 rounded-lg opacity-30 animate-pulse" style="background: linear-gradient(90deg, ${concept.color}44, transparent)"></div>` : ''}
            <span class="text-lg relative z-10">${concept.icon}</span>
            <span class="text-sm relative z-10 transition-colors ${isActive ? 'text-white font-medium' : 'text-slate-500'}">${concept.name}</span>
            ${isActive ? `<div class="ml-auto relative z-10"><div class="w-2 h-2 rounded-full animate-pulse" style="background-color: ${concept.color}"></div></div>` : ''}
            ${isCollapsing ? '<span class="ml-auto text-xs text-yellow-400 animate-bounce relative z-10">붕괴!</span>' : ''}
        `;
        
        container.appendChild(item);
    });
    
    // 진행률 업데이트
    const totalConcepts = Object.keys(CONCEPTS).length;
    const activatedCount = state.activatedConcepts.size;
    document.getElementById('activated-count').textContent = activatedCount;
    document.getElementById('total-concepts').textContent = totalConcepts;
    document.getElementById('concept-progress').style.width = `${(activatedCount / totalConcepts) * 100}%`;
}

// ========== 미로 렌더링 ==========
function renderMaze() {
    const edgesLayer = document.getElementById('edges-layer');
    const nodesLayer = document.getElementById('nodes-layer');
    
    edgesLayer.innerHTML = '';
    nodesLayer.innerHTML = '';
    
    const availableNodes = getAvailableNodes();
    
    // 엣지 렌더링
    EDGES.forEach(([from, to]) => {
        const fromNode = NODES[from];
        const toNode = NODES[to];
        if (!fromNode || !toNode) return;
        
        const isPath = state.selectedPath.includes(from) && state.selectedPath.includes(to);
        const isAvailable = state.selectedPath.includes(from) && availableNodes.some(n => n.id === to);
        
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', fromNode.x);
        line.setAttribute('y1', fromNode.y);
        line.setAttribute('x2', toNode.x);
        line.setAttribute('y2', toNode.y);
        line.setAttribute('stroke', isPath ? 'url(#pathGrad)' : isAvailable ? 'rgba(139,92,246,0.4)' : 'rgba(148,163,184,0.15)');
        line.setAttribute('stroke-width', isPath ? 3 : isAvailable ? 2 : 1);
        if (isAvailable && !isPath) line.setAttribute('stroke-dasharray', '4 4');
        if (isPath) line.setAttribute('filter', 'url(#glow)');
        line.classList.add('quantum-edge');
        if (isPath) line.classList.add('active');
        
        edgesLayer.appendChild(line);
    });
    
    // 노드 렌더링
    Object.values(NODES).forEach(node => {
        const isSelected = state.selectedPath.includes(node.id);
        const isLast = node.id === state.selectedPath[state.selectedPath.length - 1];
        const isAvailable = availableNodes.some(n => n.id === node.id);
        const canBack = isSelected && !isLast && !state.isComplete;
        const color = getNodeColor(node.type);
        const hasConcepts = node.concepts && node.concepts.length > 0;
        
        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.style.cursor = (isAvailable || isSelected) ? 'pointer' : 'default';
        g.classList.add('quantum-node');
        if (isSelected) g.classList.add('selected');
        if (isAvailable) g.classList.add('available');
        
        // 선택 가능 표시 애니메이션
        if (isAvailable) {
            const pulseCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            pulseCircle.setAttribute('cx', node.x);
            pulseCircle.setAttribute('cy', node.y);
            pulseCircle.setAttribute('r', 28);
            pulseCircle.setAttribute('fill', 'none');
            pulseCircle.setAttribute('stroke', color);
            pulseCircle.setAttribute('stroke-width', 2);
            pulseCircle.setAttribute('opacity', 0.4);
            pulseCircle.innerHTML = `
                <animate attributeName="r" values="28;36;28" dur="1.5s" repeatCount="indefinite"/>
                <animate attributeName="opacity" values="0.4;0.1;0.4" dur="1.5s" repeatCount="indefinite"/>
            `;
            g.appendChild(pulseCircle);
        }
        
        // 뒤로가기 표시
        if (isSelected && !isLast) {
            const backCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            backCircle.setAttribute('cx', node.x);
            backCircle.setAttribute('cy', node.y);
            backCircle.setAttribute('r', 30);
            backCircle.setAttribute('fill', 'none');
            backCircle.setAttribute('stroke', '#10b981');
            backCircle.setAttribute('stroke-width', 1.5);
            backCircle.setAttribute('stroke-dasharray', '3 3');
            backCircle.setAttribute('opacity', 0.7);
            backCircle.innerHTML = `<animateTransform attributeName="transform" type="rotate" from="0 ${node.x} ${node.y}" to="360 ${node.x} ${node.y}" dur="10s" repeatCount="indefinite"/>`;
            g.appendChild(backCircle);
        }
        
        // 메인 노드
        const mainCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        mainCircle.setAttribute('cx', node.x);
        mainCircle.setAttribute('cy', node.y);
        mainCircle.setAttribute('r', isSelected ? 24 : isAvailable ? 22 : 18);
        mainCircle.setAttribute('fill', isSelected ? color : `${color}22`);
        mainCircle.setAttribute('stroke', color);
        mainCircle.setAttribute('stroke-width', isSelected ? 2.5 : isAvailable ? 2 : 1);
        if (isSelected || isAvailable) mainCircle.setAttribute('filter', 'url(#glow)');
        if (!isSelected && !isAvailable) mainCircle.setAttribute('opacity', 0.5);
        g.appendChild(mainCircle);
        
        // 개념 연결 표시
        if (hasConcepts && isSelected) {
            const conceptCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            conceptCircle.setAttribute('cx', node.x);
            conceptCircle.setAttribute('cy', node.y);
            conceptCircle.setAttribute('r', 28);
            conceptCircle.setAttribute('fill', 'none');
            conceptCircle.setAttribute('stroke', '#fbbf24');
            conceptCircle.setAttribute('stroke-width', 1);
            conceptCircle.setAttribute('opacity', 0.6);
            conceptCircle.innerHTML = '<animate attributeName="opacity" values="0.6;0.2;0.6" dur="2s" repeatCount="indefinite"/>';
            g.appendChild(conceptCircle);
        }
        
        // 라벨
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', node.x);
        text.setAttribute('y', node.y + 4);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', node.stage === 5 ? 11 : 9);
        text.setAttribute('fill', isSelected || isAvailable ? '#fff' : '#94a3b8');
        text.setAttribute('font-weight', isSelected ? 'bold' : 'normal');
        text.textContent = node.label;
        g.appendChild(text);
        
        // 개념 아이콘들
        if (isSelected && hasConcepts) {
            node.concepts.slice(0, 2).forEach((cid, idx) => {
                const c = CONCEPTS[cid];
                if (!c) return;
                
                const iconCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                iconCircle.setAttribute('cx', node.x + (idx === 0 ? -18 : 18));
                iconCircle.setAttribute('cy', node.y - 28);
                iconCircle.setAttribute('r', 10);
                iconCircle.setAttribute('fill', c.color);
                iconCircle.setAttribute('opacity', 0.9);
                g.appendChild(iconCircle);
                
                const iconText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                iconText.setAttribute('x', node.x + (idx === 0 ? -18 : 18));
                iconText.setAttribute('y', node.y - 24);
                iconText.setAttribute('text-anchor', 'middle');
                iconText.setAttribute('font-size', 10);
                iconText.textContent = c.icon;
                g.appendChild(iconText);
            });
        }
        
        // 뒤로가기 힌트
        if (isSelected && !isLast) {
            const hintText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            hintText.setAttribute('x', node.x);
            hintText.setAttribute('y', node.y + 38);
            hintText.setAttribute('text-anchor', 'middle');
            hintText.setAttribute('font-size', 8);
            hintText.setAttribute('fill', '#10b981');
            hintText.textContent = '클릭하여 이동';
            g.appendChild(hintText);
        }
        
        // 클릭 이벤트
        if (isAvailable || canBack) {
            g.addEventListener('click', () => handleNodeClick(node.id));
        }
        
        nodesLayer.appendChild(g);
    });
}

// ========== 선택지 렌더링 ==========
function renderChoices() {
    const container = document.getElementById('choices-container');
    const gamePanel = document.getElementById('game-panel');
    const completePanel = document.getElementById('complete-panel');
    
    if (state.isComplete) {
        gamePanel.classList.add('hidden');
        completePanel.classList.remove('hidden');
        
        const finalNode = NODES[state.selectedPath[state.selectedPath.length - 1]];
        const isSuccess = finalNode && (finalNode.type === 'success');
        
        document.getElementById('complete-icon').textContent = isSuccess ? '🎉' : '💫';
        document.getElementById('complete-icon').className = isSuccess ? 'text-4xl mb-2 animate-bounce' : 'text-4xl mb-2';
        document.getElementById('complete-title').textContent = isSuccess ? '정답 붕괴!' : '오개념 붕괴';
        document.getElementById('complete-title').className = isSuccess ? 'text-lg font-bold text-emerald-400' : 'text-lg font-bold text-rose-400';
        document.getElementById('complete-concepts').textContent = state.activatedConcepts.size;
        document.getElementById('complete-steps').textContent = state.selectedPath.length - 1;
        
        return;
    }
    
    gamePanel.classList.remove('hidden');
    completePanel.classList.add('hidden');
    
    const availableNodes = getAvailableNodes();
    container.innerHTML = '';
    
    availableNodes.forEach(node => {
        const btn = document.createElement('button');
        btn.className = getChoiceButtonClass(node.type);
        btn.innerHTML = `${node.label}${node.concepts && node.concepts.length > 0 ? `<span class="ml-1 opacity-60">+${node.concepts.length}</span>` : ''}`;
        btn.addEventListener('click', () => handleNodeClick(node.id));
        container.appendChild(btn);
    });
    
    // 양자 상태 벡터 업데이트
    updateStateVector();
}

function getChoiceButtonClass(type) {
    const base = 'px-3 py-2 rounded-lg text-xs font-medium transition hover:scale-105 ';
    switch(type) {
        case 'correct': case 'success':
            return base + 'bg-emerald-500/20 text-emerald-400 ring-1 ring-emerald-500/30';
        case 'wrong': case 'fail':
            return base + 'bg-rose-500/20 text-rose-400 ring-1 ring-rose-500/30';
        case 'partial':
            return base + 'bg-purple-500/20 text-purple-400 ring-1 ring-purple-500/30';
        default:
            return base + 'bg-amber-500/20 text-amber-400 ring-1 ring-amber-500/30';
    }
}

function updateStateVector() {
    document.getElementById('alpha-bar').style.width = `${state.stateVector.alpha * 100}%`;
    document.getElementById('alpha-value').textContent = `${(state.stateVector.alpha * 100).toFixed(0)}%`;
    document.getElementById('beta-bar').style.width = `${state.stateVector.beta * 100}%`;
    document.getElementById('beta-value').textContent = `${(state.stateVector.beta * 100).toFixed(0)}%`;
    document.getElementById('gamma-bar').style.width = `${state.stateVector.gamma * 100}%`;
    document.getElementById('gamma-value').textContent = `${(state.stateVector.gamma * 100).toFixed(0)}%`;
}

// ========== 유틸리티 함수 ==========
function getAvailableNodes() {
    if (state.isComplete) return [];
    const lastSelected = state.selectedPath[state.selectedPath.length - 1];
    return Object.values(NODES).filter(n =>
        n.stage === state.currentStage + 1 &&
        EDGES.some(([from, to]) => from === lastSelected && to === n.id)
    );
}

function getNodeColor(type) {
    switch(type) {
        case 'correct': case 'success': return '#10b981';
        case 'partial': return '#8b5cf6';
        case 'wrong': case 'fail': return '#ef4444';
        case 'confused': return '#f59e0b';
        default: return '#06b6d4';
    }
}

// ========== 이벤트 핸들러 ==========
function handleNodeClick(nodeId) {
    // 이미 선택된 경로의 노드면 되돌아가기
    if (state.selectedPath.includes(nodeId)) {
        handleBacktrack(nodeId);
        return;
    }
    
    // 새로운 노드 선택
    handleSelectNode(nodeId);
}

function handleSelectNode(nodeId) {
    const node = NODES[nodeId];
    if (!node) return;
    
    const available = getAvailableNodes();
    if (!available.find(n => n.id === nodeId)) return;
    
    // 개념 활성화 애니메이션
    const newConcepts = new Set(state.activatedConcepts);
    if (node.concepts) {
        node.concepts.forEach((cid, idx) => {
            setTimeout(() => {
                state.collapsingConcept = cid;
                renderConceptPanel();
                setTimeout(() => {
                    state.collapsingConcept = null;
                    renderConceptPanel();
                }, 600);
            }, idx * 300);
            newConcepts.add(cid);
            
            // 학습자 태그 업데이트
            updateLearnerTags(cid);
        });
    }
    state.activatedConcepts = newConcepts;
    
    // 경로 업데이트
    state.selectedPath.push(nodeId);
    state.currentStage = node.stage;
    
    // 상태 벡터 업데이트
    if (node.type === 'correct' || node.type === 'success') {
        state.stateVector.alpha = Math.min(0.95, state.stateVector.alpha + 0.12);
        state.stateVector.beta = Math.max(0.02, state.stateVector.beta - 0.06);
        state.stateVector.gamma = Math.max(0.02, state.stateVector.gamma - 0.06);
    } else if (node.type === 'wrong' || node.type === 'fail') {
        state.stateVector.beta = Math.min(0.85, state.stateVector.beta + 0.15);
        state.stateVector.alpha = Math.max(0.05, state.stateVector.alpha - 0.08);
        state.stateVector.gamma = Math.max(0.05, state.stateVector.gamma - 0.07);
    } else if (node.type === 'partial') {
        state.stateVector.alpha = Math.min(0.7, state.stateVector.alpha + 0.05);
        state.stateVector.gamma = Math.min(0.5, state.stateVector.gamma + 0.05);
    } else {
        state.stateVector.gamma = Math.min(0.7, state.stateVector.gamma + 0.15);
    }
    
    // 히스토리 저장
    state.pathHistory.push({
        path: [...state.selectedPath],
        state: { ...state.stateVector },
        concepts: new Set(state.activatedConcepts)
    });
    
    if (node.stage === 5) {
        state.isComplete = true;
    }
    
    // UI 업데이트
    renderConceptPanel();
    renderMaze();
    renderChoices();
    renderLearnerBadges();
}

function handleBacktrack(nodeId) {
    const pathIndex = state.selectedPath.indexOf(nodeId);
    if (pathIndex === -1) return;
    
    const historyEntry = state.pathHistory[pathIndex];
    if (!historyEntry) return;
    
    // 즉시 상태 전환
    state.selectedPath = [...historyEntry.path];
    state.currentStage = NODES[nodeId].stage;
    state.stateVector = { ...historyEntry.state };
    state.activatedConcepts = new Set(historyEntry.concepts);
    state.pathHistory = state.pathHistory.slice(0, pathIndex + 1);
    state.isComplete = false;
    
    // UI 업데이트
    renderConceptPanel();
    renderMaze();
    renderChoices();
}

function backtrackOne() {
    if (state.selectedPath.length > 1) {
        handleBacktrack(state.selectedPath[state.selectedPath.length - 2]);
    }
}

// ========== 학습자 유형 태그 ==========
function updateLearnerTags(conceptId) {
    const concept = CONCEPTS[conceptId];
    if (!concept) return;
    
    // 간단한 태그 매핑
    let tag = 'general';
    if (['graph', 'interval'].includes(conceptId)) tag = '직관형';
    else if (['factorize', 'transpose', 'inequality'].includes(conceptId)) tag = '정석형';
    else if (['comparison', 'sign'].includes(conceptId)) tag = '분석형';
    else if (['roots'].includes(conceptId)) tag = '계산형';
    
    state.learnerTags[tag] = (state.learnerTags[tag] || 0) + 1;
}

function renderLearnerBadges() {
    const container = document.getElementById('learner-badges');
    container.innerHTML = '';
    
    const sortedTags = Object.entries(state.learnerTags)
        .sort((a, b) => b[1] - a[1])
        .slice(0, 3);
    
    const badgeColors = {
        '직관형': 'bg-purple-500/20 text-purple-400',
        '정석형': 'bg-emerald-500/20 text-emerald-400',
        '분석형': 'bg-cyan-500/20 text-cyan-400',
        '계산형': 'bg-amber-500/20 text-amber-400',
        'general': 'bg-slate-500/20 text-slate-400'
    };
    
    sortedTags.forEach(([tag, count]) => {
        const badge = document.createElement('span');
        badge.className = `px-2 py-1 rounded text-xs ${badgeColors[tag] || badgeColors.general}`;
        badge.textContent = `#${tag} (${count})`;
        container.appendChild(badge);
    });
}

// ========== 새 경로 추가 ==========
function addNewPath() {
    const modal = document.getElementById('add-path-modal');
    const parentSelect = document.getElementById('new-path-parent');
    
    // 현재 경로의 노드들을 선택 옵션으로
    parentSelect.innerHTML = state.selectedPath.map(nodeId => {
        const node = NODES[nodeId];
        return `<option value="${nodeId}">${node.label} (단계 ${node.stage})</option>`;
    }).join('');
    
    // 마지막 노드를 기본 선택
    parentSelect.value = state.selectedPath[state.selectedPath.length - 1];
    
    modal.classList.remove('hidden');
}

function closeAddPathModal() {
    document.getElementById('add-path-modal').classList.add('hidden');
}

async function submitNewPath() {
    const title = document.getElementById('new-path-title').value.trim();
    const desc = document.getElementById('new-path-desc').value.trim();
    const parentId = document.getElementById('new-path-parent').value;
    
    if (!title || !desc) {
        alert('제목과 설명을 모두 입력해주세요.');
        return;
    }
    
    closeAddPathModal();
    showLoading();
    updateLoadingStatus('AI가 새 경로를 분석하고 있습니다...');
    
    try {
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/api/analyze_quantum_path.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'create_node',
                contentsId: window.QUANTUM_DATA.contentsId,
                title: title,
                description: desc,
                parentNodeId: parentId,
                userId: window.QUANTUM_DATA.userId
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.node) {
            // 새 노드 추가
            const parentNode = NODES[parentId];
            const newNode = {
                id: result.node.id,
                x: parentNode.x + Math.random() * 100 - 50,
                y: parentNode.y + 100,
                label: result.node.label || title,
                type: result.node.type || 'partial',
                stage: parentNode.stage + 1,
                concepts: result.node.concepts || [],
                isUserNode: true,
                creator: window.QUANTUM_DATA.userName
            };
            
            NODES[newNode.id] = newNode;
            EDGES.push([parentId, newNode.id]);
            state.userNodes.push(newNode.id);
            
            // UI 업데이트
            renderMaze();
            renderChoices();
            
            alert(`🎉 새 경로 "${title}"가 생성되었습니다!`);
        } else {
            alert('경로 생성에 실패했습니다: ' + (result.error || '알 수 없는 오류'));
        }
    } catch (error) {
        console.error('[QuantumMaze] 경로 생성 오류:', error);
        alert('경로 생성 중 오류가 발생했습니다.');
    }
    
    hideLoading();
}

// ========== 초기화 ==========
function resetMaze() {
    state.currentStage = 0;
    state.selectedPath = ['start'];
    state.activatedConcepts = new Set();
    state.collapsingConcept = null;
    state.stateVector = { alpha: 0.33, beta: 0.33, gamma: 0.34 };
    state.isComplete = false;
    state.pathHistory = [{
        path: ['start'],
        state: { alpha: 0.33, beta: 0.33, gamma: 0.34 },
        concepts: new Set()
    }];
    state.learnerTags = {};
    
    renderConceptPanel();
    renderMaze();
    renderChoices();
    renderLearnerBadges();
}

// ========== 로딩 ==========
function showLoading() {
    document.getElementById('loading-screen').classList.remove('hidden');
    document.getElementById('main-container').classList.add('hidden');
}

function hideLoading() {
    document.getElementById('loading-screen').classList.add('hidden');
    document.getElementById('main-container').classList.remove('hidden');
    state.isLoading = false;
}

function updateLoadingStatus(text) {
    document.getElementById('loading-status').textContent = text;
}

// 전역 함수 노출
window.resetMaze = resetMaze;
window.addNewPath = addNewPath;
window.closeAddPathModal = closeAddPathModal;
window.submitNewPath = submitNewPath;
window.backtrackOne = backtrackOne;

