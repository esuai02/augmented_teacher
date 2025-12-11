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

// ========== 노드 좌표 자동 계산 ==========
// 좌표 유효성 검사 및 보정
function validateAndFixNodeCoordinates(nodes) {
    const SVG_WIDTH = 650;
    const SVG_HEIGHT = 560;
    const MARGIN = 30;

    let hasInvalidCoords = false;

    // 1단계: 좌표 유효성 검사
    Object.values(nodes).forEach(node => {
        if (
            typeof node.x !== 'number' || typeof node.y !== 'number' ||
            node.x < MARGIN || node.x > SVG_WIDTH - MARGIN ||
            node.y < MARGIN || node.y > SVG_HEIGHT - MARGIN ||
            isNaN(node.x) || isNaN(node.y)
        ) {
            hasInvalidCoords = true;
            console.warn(`[QuantumMaze] 노드 좌표 문제 감지: ${node.id} (x=${node.x}, y=${node.y})`);
        }
    });

    // 2단계: 문제 발견 시 자동 재계산
    if (hasInvalidCoords) {
        console.log('[QuantumMaze] 좌표 자동 재계산 실행');
        return calculateNodePositions(nodes);
    }

    return nodes;
}

function calculateNodePositions(nodeDefinitions) {
    const SVG_WIDTH = 650;
    const SVG_HEIGHT = 560;
    const PADDING_X = 50;
    const PADDING_Y = 45;
    const USABLE_WIDTH = SVG_WIDTH - (PADDING_X * 2);
    const USABLE_HEIGHT = SVG_HEIGHT - (PADDING_Y * 2);

    // 단계별 노드 그룹화
    const stageGroups = {};
    Object.values(nodeDefinitions).forEach(node => {
        const stage = node.stage;
        if (!stageGroups[stage]) stageGroups[stage] = [];
        stageGroups[stage].push(node);
    });

    const stages = Object.keys(stageGroups).map(Number).sort((a, b) => a - b);
    const maxStage = Math.max(...stages);

    // Y 좌표: 단계별 균등 배분
    const stageSpacing = USABLE_HEIGHT / Math.max(maxStage, 1);

    const result = {};

    stages.forEach(stage => {
        const nodesInStage = stageGroups[stage];
        const count = nodesInStage.length;
        const y = PADDING_Y + (stage * stageSpacing);

        // X 좌표: 해당 단계의 노드 수에 따라 균등 배분
        const nodeSpacing = USABLE_WIDTH / (count + 1);

        nodesInStage.forEach((node, index) => {
            const x = PADDING_X + (nodeSpacing * (index + 1));
            result[node.id] = {
                ...node,
                x: Math.round(x),
                y: Math.round(y)
            };
        });
    });

    return result;
}

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
                // 좌표 유효성 검사 및 보정 (SVG viewBox: 650x560)
                NODES = validateAndFixNodeCoordinates(result.data.nodes);
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
    // React 코드에서 가져온 수동 좌표 적용
    NODES = {
        // Stage 0 - 시작
        start: { id: 'start', label: '문제 인식', type: 'start', stage: 0, concepts: [], x: 350, y: 40 },

        // Stage 1 - 문제 해석
        s1_c: { id: 's1_c', label: '부등식 인식', type: 'correct', stage: 1, concepts: ['inequality', 'comparison'], x: 180, y: 120 },
        s1_m: { id: 's1_m', label: '교점만 생각', type: 'partial', stage: 1, concepts: ['graph'], x: 350, y: 120 },
        s1_x: { id: 's1_x', label: '문제 혼란', type: 'confused', stage: 1, concepts: [], x: 520, y: 120 },

        // Stage 2 - 접근 방법
        s2_c1: { id: 's2_c1', label: 'f(x)>g(x)', type: 'correct', stage: 2, concepts: ['inequality', 'comparison'], x: 100, y: 220 },
        s2_c2: { id: 's2_c2', label: '그래프 접근', type: 'partial', stage: 2, concepts: ['graph', 'comparison'], x: 250, y: 220 },
        s2_m1: { id: 's2_m1', label: 'f(x)<g(x)', type: 'wrong', stage: 2, concepts: ['inequality'], x: 400, y: 220 },
        s2_m2: { id: 's2_m2', label: 'f(x)=g(x)', type: 'wrong', stage: 2, concepts: ['roots'], x: 520, y: 220 },
        s2_x1: { id: 's2_x1', label: '막막함', type: 'confused', stage: 2, concepts: [], x: 620, y: 220 },

        // Stage 3 - 식 정리
        s3_c: { id: 's3_c', label: 'x²-3x-10>0', type: 'correct', stage: 3, concepts: ['transpose', 'inequality'], x: 120, y: 320 },
        s3_p: { id: 's3_p', label: '시각적 정리', type: 'partial', stage: 3, concepts: ['graph', 'transpose'], x: 280, y: 320 },
        s3_m1: { id: 's3_m1', label: '부호 오류', type: 'wrong', stage: 3, concepts: ['transpose'], x: 450, y: 320 },
        s3_m2: { id: 's3_m2', label: '등식만 품', type: 'wrong', stage: 3, concepts: ['factorize', 'roots'], x: 580, y: 320 },

        // Stage 4 - 근 찾기
        s4_c: { id: 's4_c', label: 'x=-2, 5', type: 'correct', stage: 4, concepts: ['factorize', 'roots'], x: 140, y: 420 },
        s4_p: { id: 's4_p', label: '그래프 추정', type: 'partial', stage: 4, concepts: ['graph', 'roots'], x: 300, y: 420 },
        s4_m: { id: 's4_m', label: '근만 구함', type: 'wrong', stage: 4, concepts: ['factorize', 'roots'], x: 460, y: 420 },
        s4_m2: { id: 's4_m2', label: '잘못된 근', type: 'wrong', stage: 4, concepts: ['roots'], x: 580, y: 420 },

        // Stage 5 - 최종 결과
        success: { id: 'success', label: '💥 x<-2, x>5', type: 'success', stage: 5, concepts: ['sign', 'interval'], x: 140, y: 520 },
        partial_s: { id: 'partial_s', label: '✨ 정답', type: 'success', stage: 5, concepts: ['graph', 'interval'], x: 300, y: 520 },
        fail_m1: { id: 'fail_m1', label: '❌ -2<x<5', type: 'fail', stage: 5, concepts: ['sign', 'interval'], x: 460, y: 520 },
        fail_m2: { id: 'fail_m2', label: '❌ 오답', type: 'fail', stage: 5, concepts: ['interval'], x: 580, y: 520 },
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
    const imageContainer = document.getElementById('question-image-container');

    // 문제 이미지 표시 (questionImageUrl 우선, 없으면 imageUrl 사용)
    const primaryImageUrl = data.questionImageUrl || data.imageUrl;
    const solutionImageUrl = data.solutionImageUrl;

    if (primaryImageUrl) {
        questionImage.src = primaryImageUrl;
        questionImage.classList.remove('hidden');
        noImage.classList.add('hidden');
        questionImage.onerror = () => {
            questionImage.classList.add('hidden');
            noImage.classList.remove('hidden');
        };
    }

    // 해설 이미지도 있으면 추가 표시
    if (solutionImageUrl && solutionImageUrl !== primaryImageUrl) {
        // 해설 이미지 컨테이너 생성
        const solutionContainer = document.createElement('div');
        solutionContainer.className = 'mt-3 rounded-lg overflow-hidden bg-slate-800 cursor-zoom-in hover:ring-2 hover:ring-purple-500/50 transition';
        solutionContainer.onclick = function() { openImageZoom(this); };
        solutionContainer.innerHTML = `
            <p class="text-xs text-slate-400 px-2 pt-2">📖 해설 이미지</p>
            <img src="${solutionImageUrl}" alt="해설 이미지" class="w-full" onerror="this.parentElement.style.display='none'">
        `;
        imageContainer.parentNode.insertBefore(solutionContainer, imageContainer.nextSibling);
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

    // 콘텐츠 타입 정보 표시 (디버깅용)
    if (data.contentId) {
        console.log('[QuantumMaze] contentId:', data.contentId, 'contentsType:', data.contentsType);
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
        const isUserNode = node.isUserNode || false;
        const userNodeStatus = node.status || 'standard';

        const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        g.style.cursor = (isAvailable || isSelected) ? 'pointer' : 'default';
        g.classList.add('quantum-node');
        if (isSelected) g.classList.add('selected');
        if (isAvailable) g.classList.add('available');
        if (isUserNode) g.classList.add('user-node', `user-node-${userNodeStatus}`);
        
        // 선택 가능 표시 - 절제된 양자 효과
        if (isAvailable) {
            // 미세한 양자 파동 링
            const quantumRing = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            quantumRing.setAttribute('cx', node.x);
            quantumRing.setAttribute('cy', node.y);
            quantumRing.setAttribute('r', 26);
            quantumRing.setAttribute('fill', 'none');
            quantumRing.setAttribute('stroke', color);
            quantumRing.setAttribute('stroke-width', 1);
            quantumRing.setAttribute('opacity', 0.3);
            quantumRing.innerHTML = `
                <animate attributeName="r" values="26;30;26" dur="2.5s" repeatCount="indefinite" calcMode="spline" keySplines="0.4 0 0.2 1;0.4 0 0.2 1"/>
                <animate attributeName="opacity" values="0.3;0.15;0.3" dur="2.5s" repeatCount="indefinite"/>
            `;
            g.appendChild(quantumRing);
        }
        
        // 뒤로가기 표시 - 부드러운 회전
        if (isSelected && !isLast) {
            const backCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            backCircle.setAttribute('cx', node.x);
            backCircle.setAttribute('cy', node.y);
            backCircle.setAttribute('r', 28);
            backCircle.setAttribute('fill', 'none');
            backCircle.setAttribute('stroke', '#10b981');
            backCircle.setAttribute('stroke-width', 1);
            backCircle.setAttribute('stroke-dasharray', '4 6');
            backCircle.setAttribute('opacity', 0.5);
            backCircle.innerHTML = `<animateTransform attributeName="transform" type="rotate" from="0 ${node.x} ${node.y}" to="360 ${node.x} ${node.y}" dur="20s" repeatCount="indefinite"/>`;
            g.appendChild(backCircle);
        }
        
        // 메인 노드
        const mainCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        mainCircle.setAttribute('cx', node.x);
        mainCircle.setAttribute('cy', node.y);
        mainCircle.setAttribute('r', isSelected ? 24 : isAvailable ? 22 : 18);

        // 사용자 노드 스타일 적용
        if (isUserNode) {
            if (userNodeStatus === 'pending') {
                // Pending: 점선 테두리, 반투명
                mainCircle.setAttribute('fill', `${color}15`);
                mainCircle.setAttribute('stroke', '#22d3ee');
                mainCircle.setAttribute('stroke-dasharray', '4 2');
                mainCircle.setAttribute('opacity', 0.7);
            } else if (userNodeStatus === 'verified') {
                // Verified: 강조색 + 추가 glow
                mainCircle.setAttribute('fill', isSelected ? '#22d3ee' : '#22d3ee33');
                mainCircle.setAttribute('stroke', '#22d3ee');
                mainCircle.setAttribute('filter', 'url(#strongGlow)');
            } else {
                mainCircle.setAttribute('fill', isSelected ? color : `${color}22`);
                mainCircle.setAttribute('stroke', color);
            }
            mainCircle.setAttribute('stroke-width', isSelected ? 3 : isAvailable ? 2.5 : 2);
        } else {
            mainCircle.setAttribute('fill', isSelected ? color : `${color}22`);
            mainCircle.setAttribute('stroke', color);
            mainCircle.setAttribute('stroke-width', isSelected ? 2.5 : isAvailable ? 2 : 1);
        }

        if ((isSelected || isAvailable) && !isUserNode) mainCircle.setAttribute('filter', 'url(#glow)');
        if (!isSelected && !isAvailable && !isUserNode) mainCircle.setAttribute('opacity', 0.5);
        g.appendChild(mainCircle);

        // 사용자 노드 배지 (작성자 표시)
        if (isUserNode && node.creator) {
            const badgeBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            badgeBg.setAttribute('x', node.x + 12);
            badgeBg.setAttribute('y', node.y - 30);
            badgeBg.setAttribute('width', 32);
            badgeBg.setAttribute('height', 14);
            badgeBg.setAttribute('rx', 7);
            badgeBg.setAttribute('fill', userNodeStatus === 'pending' ? '#0891b2' : '#059669');
            badgeBg.setAttribute('opacity', 0.9);
            g.appendChild(badgeBg);

            const badgeText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            badgeText.setAttribute('x', node.x + 28);
            badgeText.setAttribute('y', node.y - 21);
            badgeText.setAttribute('text-anchor', 'middle');
            badgeText.setAttribute('font-size', 8);
            badgeText.setAttribute('fill', '#fff');
            badgeText.textContent = userNodeStatus === 'pending' ? '🔒' + node.creator.slice(0, 2) : '✓' + node.creator.slice(0, 2);
            g.appendChild(badgeText);
        }
        
        // 개념 연결 표시 - 절제된 효과
        if (hasConcepts && isSelected) {
            const conceptCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            conceptCircle.setAttribute('cx', node.x);
            conceptCircle.setAttribute('cy', node.y);
            conceptCircle.setAttribute('r', 30);
            conceptCircle.setAttribute('fill', 'none');
            conceptCircle.setAttribute('stroke', '#fbbf24');
            conceptCircle.setAttribute('stroke-width', 0.8);
            conceptCircle.setAttribute('opacity', 0.4);
            conceptCircle.innerHTML = '<animate attributeName="opacity" values="0.4;0.2;0.4" dur="3s" repeatCount="indefinite"/>';
            g.appendChild(conceptCircle);
        }
        
        // 라벨 (노드 원 아래에 배치)
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', node.x);
        text.setAttribute('y', node.y + 35);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('font-size', node.stage === 5 ? 11 : 10);
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

// ========== 이미지 확대 모달 ==========
function openImageZoom(container) {
    const img = container.querySelector('img');
    if (!img || img.classList.contains('hidden')) return;

    const modal = document.getElementById('image-zoom-modal');
    const zoomedImg = document.getElementById('zoomed-image');

    zoomedImg.src = img.src;
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeImageZoom() {
    const modal = document.getElementById('image-zoom-modal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeImageZoom();
        closeAddPathModal();
        closeNeuronCultureModal();
    }
});

// ========== 유기적 뉴런 배양 시스템 ==========
const neuronState = {
    selectedType: 'alternative',
    isAnalyzing: false,
    nudgeTimer: null,
    nodeStayTimer: null,
    wrongAttempts: 0,
    ignoreSimilar: false
};

// 뉴런 배양 모달 열기
function openNeuronCultureModal() {
    const modal = document.getElementById('neuron-culture-modal');
    const parentSelect = document.getElementById('neuron-parent-node');

    // 현재 경로의 노드들을 선택 옵션으로 채우기
    parentSelect.innerHTML = state.selectedPath.map(nodeId => {
        const node = NODES[nodeId];
        if (!node) return '';
        return `<option value="${nodeId}">${node.icon || '📍'} ${node.label} (단계 ${node.stage})</option>`;
    }).filter(Boolean).join('');

    // 마지막 노드를 기본 선택
    if (state.selectedPath.length > 0) {
        parentSelect.value = state.selectedPath[state.selectedPath.length - 1];
    }

    // 입력 필드 초기화
    document.getElementById('neuron-title').value = '';
    document.getElementById('neuron-description').value = '';
    document.getElementById('neuron-analysis-status').classList.add('hidden');
    document.getElementById('neuron-similar-alert').classList.add('hidden');
    neuronState.selectedType = 'alternative';
    neuronState.ignoreSimilar = false;

    // 유형 버튼 초기화
    document.querySelectorAll('.neuron-type-btn').forEach(btn => {
        btn.classList.remove('ring-2', 'ring-emerald-500', 'ring-amber-500', 'ring-purple-500');
        if (btn.dataset.type === 'alternative') {
            btn.classList.add('ring-2', 'ring-emerald-500');
        }
    });

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';

    console.log('[NeuronCulture] 모달 열림');
}

// 뉴런 배양 모달 닫기
function closeNeuronCultureModal() {
    const modal = document.getElementById('neuron-culture-modal');
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

// 유형 버튼 클릭 핸들러
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.neuron-type-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            neuronState.selectedType = this.dataset.type;

            // 모든 버튼의 ring 제거
            document.querySelectorAll('.neuron-type-btn').forEach(b => {
                b.classList.remove('ring-2', 'ring-emerald-500', 'ring-amber-500', 'ring-purple-500');
            });

            // 선택된 버튼에 ring 추가
            const ringColor = {
                'alternative': 'ring-emerald-500',
                'misconception': 'ring-amber-500',
                'shortcut': 'ring-purple-500'
            };
            this.classList.add('ring-2', ringColor[neuronState.selectedType]);
        });
    });
});

// 경로 배양 제출
async function submitNeuronPath() {
    const parentNodeId = document.getElementById('neuron-parent-node').value;
    const title = document.getElementById('neuron-title').value.trim();
    const description = document.getElementById('neuron-description').value.trim();

    // 유효성 검사
    if (!title) {
        alert('풀이 제목을 입력해주세요.');
        return;
    }
    if (title.length < 3 || title.length > 50) {
        alert('제목은 3~50자 사이로 입력해주세요.');
        return;
    }
    if (!description) {
        alert('풀이 설명을 입력해주세요.');
        return;
    }
    if (description.length < 10) {
        alert('설명을 10자 이상 입력해주세요.');
        return;
    }

    // 분석 상태 표시
    neuronState.isAnalyzing = true;
    document.getElementById('neuron-analysis-status').classList.remove('hidden');
    document.getElementById('neuron-status-text').textContent = 'AI가 풀이를 분석하고 있습니다...';
    document.getElementById('neuron-submit-btn').disabled = true;

    try {
        // 기존 노드들 정보 수집
        const existingNodes = Object.values(NODES).map(n => ({
            id: n.id,
            label: n.label,
            desc: n.desc || ''
        }));

        // API 호출 (절대 경로 사용)
        const response = await fetch('/moodle/local/augmented_teacher/alt42/teachingsupport/api/analyze_neuron_path.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                parentNodeId,
                pathType: neuronState.selectedType,
                title,
                description,
                questionId: window.QUANTUM_DATA?.contentId || '',
                existingNodes
            })
        });

        const result = await response.json();

        if (!result.success) {
            throw new Error(result.error || '알 수 없는 오류');
        }

        // 유사 경로 감지
        if (result.isSimilar && !neuronState.ignoreSimilar) {
            document.getElementById('neuron-analysis-status').classList.add('hidden');
            document.getElementById('neuron-similar-alert').classList.remove('hidden');
            document.getElementById('neuron-similar-info').textContent =
                `기존 경로: ${result.similarNode || '유사한 풀이'}`;
            document.getElementById('neuron-submit-btn').disabled = false;
            neuronState.isAnalyzing = false;
            return;
        }

        // 새 노드 추가
        const newNode = result.node;
        const parentNode = NODES[parentNodeId];
        const newNodeId = newNode.id;

        // 노드 데이터 구성
        NODES[newNodeId] = {
            id: newNodeId,
            label: newNode.label,
            desc: newNode.desc,
            stage: (parentNode?.stage || 0) + 1,
            x: (parentNode?.x || 350) + (Math.random() - 0.5) * 80,
            y: (parentNode?.y || 50) + 100,
            type: 'user',
            status: 'pending',
            concepts: newNode.concepts || [],
            learnerType: newNode.learnerType || 'general',
            creator: newNode.creator,
            creatorId: newNode.creatorId,
            isUserNode: true
        };

        // 엣지 추가
        EDGES.push([parentNodeId, newNodeId]);

        // 상태 업데이트
        state.userNodes.push(newNodeId);

        // UI 업데이트
        renderMaze();
        renderChoices();

        // 성공 메시지
        document.getElementById('neuron-status-text').textContent = '🎉 새 경로가 배양되었습니다!';

        setTimeout(() => {
            closeNeuronCultureModal();
            alert(`🧬 "${newNode.label}" 경로가 생성되었습니다!\n\n🔒 3명의 검증을 받으면 공개됩니다.`);
        }, 1000);

    } catch (error) {
        console.error('[NeuronCulture] 오류:', error);
        document.getElementById('neuron-status-text').textContent = '❌ 오류: ' + error.message;
    } finally {
        document.getElementById('neuron-submit-btn').disabled = false;
        neuronState.isAnalyzing = false;
    }
}

// 유사 경로 무시하고 생성
function ignoreSimilarAndCreate() {
    neuronState.ignoreSimilar = true;
    document.getElementById('neuron-similar-alert').classList.add('hidden');
    submitNeuronPath();
}

// 맥락적 넛지 표시
function showNudge() {
    const popup = document.getElementById('nudge-popup');
    popup.classList.remove('hidden');

    // 10초 후 자동 숨김
    setTimeout(() => {
        hideNudge();
    }, 10000);
}

// 넛지 숨김
function hideNudge() {
    const popup = document.getElementById('nudge-popup');
    popup.classList.add('hidden');
}

// 노드 체류 시간 체크 (30초 이상 머물면 넛지)
function startNodeStayTimer() {
    if (neuronState.nodeStayTimer) {
        clearTimeout(neuronState.nodeStayTimer);
    }
    neuronState.nodeStayTimer = setTimeout(() => {
        if (!state.isComplete) {
            showNudge();
        }
    }, 30000); // 30초
}

// 오답 횟수 체크 (2회 이상이면 넛지)
function checkWrongAttempts() {
    neuronState.wrongAttempts++;
    if (neuronState.wrongAttempts >= 2) {
        showNudge();
        neuronState.wrongAttempts = 0;
    }
}

// 노드 선택 시 타이머 리셋 (기존 handleNodeClick에 추가 필요)
const originalHandleNodeClick = window.handleNodeClick || function() {};
window.handleNodeClick = function(nodeId) {
    startNodeStayTimer();
    if (originalHandleNodeClick) {
        originalHandleNodeClick(nodeId);
    }
};

// 전역 함수 노출
window.resetMaze = resetMaze;
window.addNewPath = addNewPath;
window.closeAddPathModal = closeAddPathModal;
window.submitNewPath = submitNewPath;
window.backtrackOne = backtrackOne;
window.openImageZoom = openImageZoom;
window.closeImageZoom = closeImageZoom;
window.openNeuronCultureModal = openNeuronCultureModal;
window.closeNeuronCultureModal = closeNeuronCultureModal;
window.submitNeuronPath = submitNeuronPath;
window.ignoreSimilarAndCreate = ignoreSimilarAndCreate;
window.showNudge = showNudge;
window.hideNudge = hideNudge;

