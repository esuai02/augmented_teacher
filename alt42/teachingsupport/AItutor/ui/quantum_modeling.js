/**
 * Quantum Collapse Learning Maze - Visualization Engine
 * y=x²-ax 정삼각형 문제 양자 경로 분석
 *
 * React 코드를 Vanilla JS로 변환
 * 정답: a=2√3 | 모든 가능한 풀이/오류 경로 시각화
 *
 * 파일: quantum_modeling.js
 * 위치: alt42/teachingsupport/AItutor/ui/
 */

(function() {
    'use strict';

    // ========================================
    // 데이터 정의 (React 코드에서 변환)
    // ========================================

    const CONCEPTS = {
        factor: { id: 'factor', name: '인수분해', icon: '🧩', color: '#10b981' },
        vertex: { id: 'vertex', name: '꼭짓점 공식', icon: '📍', color: '#8b5cf6' },
        distance: { id: 'distance', name: '거리 계산', icon: '📏', color: '#f59e0b' },
        equilateral: { id: 'equilateral', name: '정삼각형 성질', icon: '△', color: '#06b6d4' },
        midpoint: { id: 'midpoint', name: '중점 공식', icon: '◐', color: '#ec4899' },
        complete_sq: { id: 'complete_sq', name: '완전제곱식', icon: '²', color: '#3b82f6' },
        equation: { id: 'equation', name: '방정식 풀이', icon: '⚖️', color: '#ef4444' },
        condition: { id: 'condition', name: '조건 확인', icon: '✓', color: '#14b8a6' },
        graph: { id: 'graph', name: '그래프 해석', icon: '📈', color: '#a855f7' },
        height: { id: 'height', name: '삼각형 높이', icon: '↕', color: '#f97316' },
    };

    // 기본 노드 (하드코딩) - DB 데이터와 병합됨
    const BASE_NODES = {
        start: { id: 'start', x: 500, y: 50, label: '문제 인식', type: 'start', stage: 0, concepts: [], desc: '이차함수, 정삼각형 조건 파악' },

        // Stage 1: 문제 해석
        s1_full: { id: 's1_full', x: 200, y: 170, label: '완전 이해', type: 'correct', stage: 1, concepts: ['graph'], desc: 'A,B는 x절편, C는 꼭짓점, 정삼각형 조건' },
        s1_partial: { id: 's1_partial', x: 500, y: 170, label: '부분 이해', type: 'partial', stage: 1, concepts: ['graph'], desc: '점들의 의미는 알지만 정삼각형 조건 모호' },
        s1_confuse: { id: 's1_confuse', x: 800, y: 170, label: '혼란', type: 'confused', stage: 1, concepts: [], desc: '무엇을 구해야 할지 모름' },

        // Stage 2: x절편 구하기
        s2_factor: { id: 's2_factor', x: 100, y: 310, label: 'x(x-a)=0', type: 'correct', stage: 2, concepts: ['factor'], desc: '인수분해로 x=0, x=a' },
        s2_formula: { id: 's2_formula', x: 280, y: 310, label: '근의 공식', type: 'partial', stage: 2, concepts: ['equation'], desc: '근의 공식 사용 (비효율적이지만 정답)' },
        s2_sign_err: { id: 's2_sign_err', x: 500, y: 310, label: 'x=-a 오류', type: 'wrong', stage: 2, concepts: ['factor'], desc: 'x(x-a)=0에서 x=0, x=-a로 착각' },
        s2_forget_zero: { id: 's2_forget_zero', x: 700, y: 310, label: 'x=0 누락', type: 'wrong', stage: 2, concepts: ['factor'], desc: 'x-a=0만 풀어서 x=a만 구함' },
        s2_stuck: { id: 's2_stuck', x: 900, y: 310, label: '막힘', type: 'confused', stage: 2, concepts: [], desc: '어떻게 교점을 구하는지 모름' },

        // Stage 3: 꼭짓점 구하기
        s3_complete: { id: 's3_complete', x: 80, y: 460, label: '완전제곱식', type: 'correct', stage: 3, concepts: ['complete_sq', 'vertex'], desc: 'y=(x-a/2)²-a²/4 → C(a/2, -a²/4)' },
        s3_formula: { id: 's3_formula', x: 260, y: 460, label: '꼭짓점 공식', type: 'correct', stage: 3, concepts: ['vertex'], desc: 'x=-b/2a=a/2, y 대입' },
        s3_mid_sub: { id: 's3_mid_sub', x: 440, y: 460, label: '중점 대입', type: 'partial', stage: 3, concepts: ['midpoint'], desc: 'A,B 중점의 x좌표를 대입' },
        s3_sign_err: { id: 's3_sign_err', x: 640, y: 460, label: 'y좌표 부호오류', type: 'wrong', stage: 3, concepts: ['vertex'], desc: 'C(a/2, a²/4)로 착각 (양수)' },
        s3_coef_err: { id: 's3_coef_err', x: 860, y: 460, label: '계수 착각', type: 'wrong', stage: 3, concepts: ['vertex'], desc: '-b/2a에서 a=1 대입 오류' },

        // Stage 4: 정삼각형 조건 접근법
        s4_height: { id: 's4_height', x: 100, y: 610, label: '높이 활용', type: 'correct', stage: 4, concepts: ['equilateral', 'height'], desc: 'MC = (√3/2)AB 관계 사용' },
        s4_sides: { id: 's4_sides', x: 300, y: 610, label: '세 변 같음', type: 'correct', stage: 4, concepts: ['equilateral', 'distance'], desc: 'AB=BC=CA 조건 사용' },
        s4_angle: { id: 's4_angle', x: 500, y: 610, label: '60° 조건', type: 'partial', stage: 4, concepts: ['equilateral'], desc: '각도 60° 조건으로 접근 (복잡)' },
        s4_iso_only: { id: 's4_iso_only', x: 700, y: 610, label: '이등변만', type: 'wrong', stage: 4, concepts: ['distance'], desc: 'BC=CA만 확인, AB 무시' },
        s4_height_err: { id: 's4_height_err', x: 900, y: 610, label: '높이공식 오류', type: 'wrong', stage: 4, concepts: ['height'], desc: '√3/2 대신 1/2 또는 √3 사용' },

        // Stage 5: 거리 계산
        s5_ab_correct: { id: 's5_ab_correct', x: 100, y: 760, label: 'AB=a 정확', type: 'correct', stage: 5, concepts: ['distance'], desc: '|a-0|=a' },
        s5_mc_correct: { id: 's5_mc_correct', x: 300, y: 760, label: 'MC=a²/4', type: 'correct', stage: 5, concepts: ['distance', 'midpoint'], desc: 'M(a/2,0), C(a/2,-a²/4) → MC=a²/4' },
        s5_bc_calc: { id: 's5_bc_calc', x: 500, y: 760, label: 'BC 거리계산', type: 'partial', stage: 5, concepts: ['distance'], desc: '√[(a-a/2)²+(a²/4)²] 계산' },
        s5_ab_err: { id: 's5_ab_err', x: 700, y: 760, label: 'AB=2a 오류', type: 'wrong', stage: 5, concepts: ['distance'], desc: 'AB를 2a로 착각' },
        s5_mc_sign: { id: 's5_mc_sign', x: 900, y: 760, label: 'MC 부호오류', type: 'wrong', stage: 5, concepts: ['distance'], desc: 'MC=-a²/4 (음수 처리 실패)' },

        // Stage 6: 방정식 설정
        s6_eq_correct: { id: 's6_eq_correct', x: 150, y: 910, label: 'a²/4=(√3/2)a', type: 'correct', stage: 6, concepts: ['equation', 'equilateral'], desc: '정삼각형 높이 관계식 설정' },
        s6_eq_sides: { id: 's6_eq_sides', x: 400, y: 910, label: 'a=BC 설정', type: 'correct', stage: 6, concepts: ['equation', 'distance'], desc: 'AB=BC에서 방정식 유도' },
        s6_eq_wrong: { id: 's6_eq_wrong', x: 650, y: 910, label: '관계식 오류', type: 'wrong', stage: 6, concepts: ['equation'], desc: 'a²/4 = a/2 등 잘못된 관계' },
        s6_sqrt_err: { id: 's6_sqrt_err', x: 880, y: 910, label: '√3 누락', type: 'wrong', stage: 6, concepts: ['equilateral'], desc: '높이=(1/2)×밑변으로 착각' },

        // Stage 7: 최종 답
        s7_success: { id: 's7_success', x: 200, y: 1060, label: '💥 a=2√3', type: 'success', stage: 7, concepts: ['equation', 'condition'], desc: 'a²-2√3a=0 → a=2√3 (a>0)' },
        s7_success2: { id: 's7_success2', x: 450, y: 1060, label: '✨ a=2√3', type: 'success', stage: 7, concepts: ['equation', 'condition'], desc: '세 변 방법으로도 동일 결과' },
        s7_fail_calc: { id: 's7_fail_calc', x: 680, y: 1060, label: '❌ 계산오류', type: 'fail', stage: 7, concepts: ['equation'], desc: 'a=√3 또는 a=2 등 오답' },
        s7_fail_cond: { id: 's7_fail_cond', x: 900, y: 1060, label: '❌ a=0 선택', type: 'fail', stage: 7, concepts: ['condition'], desc: 'a>0 조건 무시하고 a=0' },
    };

    // 기본 엣지 (하드코딩) - DB 데이터와 병합됨
    const BASE_EDGES = [
        ['start', 's1_full'], ['start', 's1_partial'], ['start', 's1_confuse'],
        ['s1_full', 's2_factor'], ['s1_full', 's2_formula'], ['s1_partial', 's2_formula'], ['s1_partial', 's2_sign_err'],
        ['s1_confuse', 's2_stuck'], ['s1_confuse', 's2_forget_zero'],
        ['s2_factor', 's3_complete'], ['s2_factor', 's3_formula'], ['s2_formula', 's3_formula'], ['s2_formula', 's3_mid_sub'],
        ['s2_sign_err', 's3_sign_err'], ['s2_forget_zero', 's3_coef_err'], ['s2_stuck', 's3_mid_sub'],
        ['s3_complete', 's4_height'], ['s3_complete', 's4_sides'], ['s3_formula', 's4_height'], ['s3_formula', 's4_sides'],
        ['s3_mid_sub', 's4_angle'], ['s3_mid_sub', 's4_sides'], ['s3_sign_err', 's4_height_err'], ['s3_coef_err', 's4_iso_only'],
        ['s4_height', 's5_ab_correct'], ['s4_height', 's5_mc_correct'], ['s4_sides', 's5_bc_calc'], ['s4_sides', 's5_ab_correct'],
        ['s4_angle', 's5_bc_calc'], ['s4_iso_only', 's5_ab_err'], ['s4_height_err', 's5_mc_sign'],
        ['s5_ab_correct', 's6_eq_correct'], ['s5_mc_correct', 's6_eq_correct'], ['s5_bc_calc', 's6_eq_sides'],
        ['s5_ab_err', 's6_eq_wrong'], ['s5_mc_sign', 's6_sqrt_err'],
        ['s6_eq_correct', 's7_success'], ['s6_eq_sides', 's7_success2'], ['s6_eq_wrong', 's7_fail_calc'], ['s6_sqrt_err', 's7_fail_cond'],
    ];

    const STAGE_NAMES = ['시작', '문제해석', 'x절편', '꼭짓점', '접근법', '거리계산', '방정식', '최종'];

    // 실제 사용할 노드/엣지 (DB 데이터 병합 후)
    let NODES = { ...BASE_NODES };
    let EDGES = [...BASE_EDGES];

    // DB 데이터 병합 함수
    function mergeDbData() {
        if (!window.QUANTUM_DATA) return;
        
        const dbNodes = window.QUANTUM_DATA.dbNodes || [];
        const dbEdges = window.QUANTUM_DATA.dbEdges || [];
        
        // DB 노드 병합 (기존 노드와 중복되지 않는 것만)
        dbNodes.forEach(node => {
            if (!NODES[node.id]) {
                NODES[node.id] = {
                    id: node.id,
                    x: node.x,
                    y: node.y,
                    label: node.label,
                    type: node.type,
                    stage: node.stage,
                    concepts: node.concepts || [],
                    desc: node.desc || '',
                    fromDb: true  // DB에서 온 노드 표시
                };
                console.log('[quantum_modeling.js] DB 노드 추가:', node.id, node.label);
            }
        });
        
        // DB 엣지 병합 (중복 체크)
        dbEdges.forEach(edge => {
            const exists = EDGES.some(e => e[0] === edge[0] && e[1] === edge[1]);
            if (!exists) {
                EDGES.push(edge);
                console.log('[quantum_modeling.js] DB 엣지 추가:', edge[0], '->', edge[1]);
            }
        });
        
        if (dbNodes.length > 0 || dbEdges.length > 0) {
            console.log('[quantum_modeling.js] DB 데이터 병합 완료:', dbNodes.length, '노드,', dbEdges.length, '엣지');
        }
    }

    // ========================================
    // 상태 관리
    // ========================================

    const state = {
        sessionId: null,
        contentId: null,
        currentStage: 0,
        selectedPath: ['start'],
        activatedConcepts: new Set(),
        collapsingConcept: null,
        stateVec: { alpha: 0.33, beta: 0.33, gamma: 0.34 },
        isComplete: false,
        history: [{ path: ['start'], state: { alpha: 0.33, beta: 0.33, gamma: 0.34 }, concepts: new Set() }],
        hoveredNode: null,
        mapScale: 1.0,
        // 드래그 관련 상태
        isDragging: false,
        dragNodeId: null,
        dragStartX: 0,
        dragStartY: 0,
        dragOffsetX: 0,
        dragOffsetY: 0
    };

    // ========================================
    // API 통신 모듈
    // ========================================

    const quantumAPI = {
        baseUrl: '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/api/',

        async call(endpoint, data = {}, method = 'POST') {
            let url = this.baseUrl + endpoint;
            const options = {
                method: method,
                headers: {}
            };

            if (method === 'GET') {
                const params = new URLSearchParams(data);
                url += '?' + params.toString();
            } else {
                options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                options.body = new URLSearchParams(data).toString();
            }

            try {
                const response = await fetch(url, options);
                const result = await response.json();
                return result;
            } catch (error) {
                console.error(`[quantumAPI] ${endpoint} 오류:`, error);
                return { success: false, error: error.message };
            }
        },

        async saveSession() {
            const data = {
                action: 'saveSession',
                sessionId: state.sessionId,
                contentId: state.contentId,
                currentStage: state.currentStage,
                currentNodeId: state.selectedPath[state.selectedPath.length - 1] || null,
                isComplete: state.isComplete ? 1 : 0,
                selectedPath: JSON.stringify(state.selectedPath),
                activatedConcepts: JSON.stringify(Array.from(state.activatedConcepts)),
                quantumState: JSON.stringify(state.stateVec),
                historySnapshot: JSON.stringify(state.history.map(h => ({
                    path: h.path,
                    state: h.state,
                    concepts: Array.from(h.concepts)
                }))),
                finalResult: state.isComplete ? (state.selectedPath.length > 0 && NODES[state.selectedPath[state.selectedPath.length - 1]]?.type === 'success' ? 'success' : 'fail') : null
            };
            return await this.call('quantum_session_api.php', data);
        },

        async loadSession(sessionId) {
            return await this.call('quantum_session_api.php', { action: 'loadSession', sessionId: sessionId }, 'GET');
        },

        async getLastSession(contentId) {
            return await this.call('quantum_session_api.php', { action: 'getLastSession', contentId: contentId }, 'GET');
        },

        async updateNodePosition(nodeId, x, y) {
            return await this.call('quantum_session_api.php', {
                action: 'updateNodePosition',
                nodeId: nodeId,
                contentId: state.contentId,
                x: Math.round(x),
                y: Math.round(y)
            });
        },

        async saveNodeToDb(node) {
            return await this.call('quantum_session_api.php', {
                action: 'saveNodeToDb',
                nodeId: node.id,
                contentId: state.contentId,
                x: Math.round(node.x),
                y: Math.round(node.y),
                label: node.label,
                type: node.type,
                stage: node.stage,
                description: node.desc || ''
            });
        }
    };

    // ========================================
    // 유틸리티 함수
    // ========================================

    function getNodeColor(type) {
        const colors = {
            start: '#06b6d4',
            correct: '#10b981',
            partial: '#8b5cf6',
            wrong: '#ef4444',
            confused: '#f59e0b',
            success: '#10b981',
            fail: '#ef4444'
        };
        return colors[type] || '#64748b';
    }

    function getTypeLabel(type) {
        return type || 'unknown';
    }

    function getTypeClass(type) {
        const classes = {
            correct: 'bg-emerald-500/20 text-emerald-400',
            success: 'bg-emerald-500/20 text-emerald-400',
            wrong: 'bg-rose-500/20 text-rose-400',
            fail: 'bg-rose-500/20 text-rose-400',
            partial: 'bg-purple-500/20 text-purple-400',
            confused: 'bg-amber-500/20 text-amber-400',
            start: 'bg-cyan-500/20 text-cyan-400'
        };
        return classes[type] || 'bg-slate-500/20 text-slate-400';
    }

    function getButtonClass(type) {
        const classes = {
            correct: 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25',
            success: 'bg-emerald-500/15 text-emerald-400 hover:bg-emerald-500/25',
            wrong: 'bg-rose-500/15 text-rose-400 hover:bg-rose-500/25',
            fail: 'bg-rose-500/15 text-rose-400 hover:bg-rose-500/25',
            partial: 'bg-purple-500/15 text-purple-400 hover:bg-purple-500/25',
            confused: 'bg-amber-500/15 text-amber-400 hover:bg-amber-500/25'
        };
        return classes[type] || 'bg-slate-500/15 text-slate-400 hover:bg-slate-500/25';
    }

    function getAvailableNodes() {
        if (state.isComplete) return [];
        const lastNodeId = state.selectedPath[state.selectedPath.length - 1];
        return Object.values(NODES).filter(n =>
            n.stage === state.currentStage + 1 &&
            EDGES.some(([f, t]) => f === lastNodeId && t === n.id)
        );
    }

    // ========================================
    // 렌더링 함수들
    // ========================================

    function renderStageProgress() {
        const container = document.getElementById('stage-progress');
        if (!container) return;

        container.innerHTML = STAGE_NAMES.map((name, i) => {
            let cls = 'px-3 py-1.5 rounded-lg text-sm whitespace-nowrap font-medium ';
            if (i < state.currentStage) {
                cls += 'bg-emerald-500/20 text-emerald-400';
            } else if (i === state.currentStage) {
                cls += 'bg-purple-500/30 text-purple-300 ring-2 ring-purple-500';
            } else {
                cls += 'bg-slate-800 text-slate-500';
            }
            return `<div class="${cls}">${i}. ${name}</div>`;
        }).join('');
    }

    function renderConceptPanel() {
        const container = document.getElementById('concept-list');
        const countEl = document.getElementById('activated-count');
        const progressEl = document.getElementById('concept-progress');
        if (!container) return;

        container.innerHTML = Object.values(CONCEPTS).map(c => {
            const isActive = state.activatedConcepts.has(c.id);
            const isColl = state.collapsingConcept === c.id;
            let cls = 'relative flex items-center gap-2 px-3 py-2 rounded-lg transition-all ';
            cls += isActive ? 'bg-white/10' : 'bg-slate-800/50';
            if (isColl) cls += ' ring-2 ring-yellow-400 animate-pulse';

            let gradient = '';
            if (isActive) {
                gradient = `<div class="absolute inset-0 rounded-lg opacity-20" style="background: linear-gradient(90deg, ${c.color}, transparent)"></div>`;
            }
            let dot = '';
            if (isActive) {
                dot = `<div class="ml-auto w-2 h-2 rounded-full animate-pulse relative z-10" style="background-color: ${c.color}"></div>`;
            }

            return `
                <div class="${cls}">
                    ${gradient}
                    <span class="text-xl relative z-10">${c.icon}</span>
                    <span class="text-sm relative z-10 ${isActive ? 'text-white font-medium' : 'text-slate-500'}">${c.name}</span>
                    ${dot}
                </div>
            `;
        }).join('');

        if (countEl) countEl.textContent = state.activatedConcepts.size;
        if (progressEl) {
            progressEl.style.width = `${(state.activatedConcepts.size / Object.keys(CONCEPTS).length) * 100}%`;
        }
    }

    function renderNodeDetail(node) {
        const detailEl = document.getElementById('node-detail');
        if (!detailEl || !node) {
            if (detailEl) detailEl.classList.add('hidden');
            return;
        }

        detailEl.classList.remove('hidden');
        const color = getNodeColor(node.type);
        detailEl.style.borderColor = color;

        document.getElementById('detail-label').textContent = node.label;
        const typeEl = document.getElementById('detail-type');
        typeEl.textContent = getTypeLabel(node.type);
        typeEl.className = `text-xs px-2 py-1 rounded ${getTypeClass(node.type)}`;
        document.getElementById('detail-desc').textContent = node.desc;

        const conceptsEl = document.getElementById('detail-concepts');
        if (node.concepts && node.concepts.length > 0) {
            conceptsEl.innerHTML = node.concepts.map(cid => {
                const c = CONCEPTS[cid];
                if (!c) return '';
                return `<span class="text-sm px-2 py-1 rounded" style="background-color: ${c.color}33; color: ${c.color}">${c.icon} ${c.name}</span>`;
            }).join('');
        } else {
            conceptsEl.innerHTML = '';
        }
    }

    function renderMaze() {
        const edgesLayer = document.getElementById('edges-layer');
        const nodesLayer = document.getElementById('nodes-layer');
        const stageLabels = document.getElementById('stage-labels');
        if (!edgesLayer || !nodesLayer) return;

        // SVG 내부 라벨은 비움 (고정 라벨 사용)
        stageLabels.innerHTML = '';
        
        // 고정 단계 라벨 렌더링 (HTML 요소 - 맵 크기 변경에 영향받지 않음)
        const fixedLabels = document.getElementById('stage-labels-fixed');
        if (fixedLabels) {
            fixedLabels.innerHTML = STAGE_NAMES.map((name, i) =>
                `<div class="text-xs font-bold text-slate-500 whitespace-nowrap" style="margin-top: ${i === 0 ? '40px' : '120px'};">${i}. ${name}</div>`
            ).join('');
        }

        edgesLayer.innerHTML = EDGES.map(([f, t]) => {
            const fn = NODES[f], tn = NODES[t];
            if (!fn || !tn) return '';

            const isPath = state.selectedPath.includes(f) && state.selectedPath.includes(t);
            const avail = getAvailableNodes();
            const isAvail = state.selectedPath.includes(f) && avail.some(n => n.id === t);

            let stroke = 'rgba(148,163,184,0.15)';
            let strokeWidth = 1.5;
            let dasharray = 'none';
            let filter = 'none';

            if (isPath) {
                stroke = 'url(#pathG)';
                strokeWidth = 4;
                filter = 'url(#glow)';
            } else if (isAvail) {
                stroke = 'rgba(139,92,246,0.5)';
                strokeWidth = 2.5;
                dasharray = '6 6';
            }

            return `<line x1="${fn.x}" y1="${fn.y}" x2="${tn.x}" y2="${tn.y}"
                stroke="${stroke}" stroke-width="${strokeWidth}"
                stroke-dasharray="${dasharray}" filter="${filter}"/>`;
        }).join('');

        nodesLayer.innerHTML = Object.values(NODES).map(node => {
            const isSel = state.selectedPath.includes(node.id);
            const isLast = node.id === state.selectedPath[state.selectedPath.length - 1];
            const avail = getAvailableNodes();
            const isAvail = avail.some(n => n.id === node.id);
            const color = getNodeColor(node.type);
            const hasConcepts = node.concepts && node.concepts.length > 0;

            const radius = isSel ? 35 : isAvail ? 32 : 26;
            const fillOpacity = !isSel && !isAvail ? 0.5 : 1;
            const filter = isSel || isAvail ? 'url(#glow)' : 'none';

            let pulseCircle = '';
            if (isAvail) {
                pulseCircle = `<circle cx="${node.x}" cy="${node.y}" r="38" fill="none" stroke="${color}" stroke-width="2" class="animate-pulse-glow"></circle>`;
            }

            let backCircle = '';
            if (isSel && !isLast) {
                backCircle = `<circle cx="${node.x}" cy="${node.y}" r="42" fill="none" stroke="#10b981"
                    stroke-width="2" stroke-dasharray="4 4" opacity="0.7" class="animate-rotate-slow"
                    style="transform-origin: ${node.x}px ${node.y}px"/>`;
            }

            let conceptRing = '';
            if (hasConcepts && isSel) {
                conceptRing = `<circle cx="${node.x}" cy="${node.y}" r="40" fill="none" stroke="#fbbf24"
                    stroke-width="1.5" class="animate-pulse-glow"/>`;
            }

            let backHint = '';
            if (isSel && !isLast) {
                backHint = `<text x="${node.x}" y="${node.y + 55}" text-anchor="middle" font-size="11" fill="#10b981">클릭→이동</text>`;
            }

            const cursor = 'grab';
            const isFromDb = node.fromDb ? true : false;

            // DB에서 온 노드 표시
            let dbBadge = '';
            if (isFromDb) {
                dbBadge = `<circle cx="${node.x + radius - 8}" cy="${node.y - radius + 8}" r="6" fill="#10b981"/>
                           <text x="${node.x + radius - 8}" y="${node.y - radius + 11}" text-anchor="middle" font-size="8" fill="white">✓</text>`;
            }

            return `
                <g class="quantum-node" data-id="${node.id}" style="cursor: ${cursor}"
                   onmouseenter="handleNodeHover('${node.id}')"
                   onmouseleave="handleNodeHover(null)"
                   onmousedown="startNodeDrag('${node.id}', event)"
                   onclick="if(!window.wasDragging) handleNodeClick('${node.id}')">
                    ${pulseCircle}
                    ${backCircle}
                    <circle cx="${node.x}" cy="${node.y}" r="${radius}"
                        fill="${isSel ? color : color + '22'}" stroke="${color}"
                        stroke-width="${isSel ? 3 : isAvail ? 2 : 1}"
                        filter="${filter}" opacity="${fillOpacity}"/>
                    ${conceptRing}
                    ${dbBadge}
                    <text x="${node.x}" y="${node.y + 5}" text-anchor="middle" font-size="14"
                        fill="${isSel || isAvail ? '#fff' : '#94a3b8'}" font-weight="${isSel ? 'bold' : 'normal'}"
                        style="pointer-events: none;">
                        ${node.label}
                    </text>
                    ${backHint}
                </g>
            `;
        }).join('');
    }

    function renderChoices() {
        const container = document.getElementById('choices-container');
        const countEl = document.getElementById('avail-count');
        const choicesPanel = document.getElementById('choices-panel');
        const completePanel = document.getElementById('complete-panel');
        if (!container) return;

        const avail = getAvailableNodes();
        if (countEl) countEl.textContent = avail.length;

        if (state.isComplete) {
            if (choicesPanel) choicesPanel.classList.add('hidden');
            if (completePanel) completePanel.classList.remove('hidden');

            const finalNode = NODES[state.selectedPath[state.selectedPath.length - 1]];
            if (finalNode) {
                const iconEl = document.getElementById('complete-icon');
                const titleEl = document.getElementById('complete-title');
                const labelEl = document.getElementById('complete-label');

                if (finalNode.type === 'success') {
                    iconEl.textContent = '🎉';
                    iconEl.classList.add('animate-bounce');
                    titleEl.textContent = '정답!';
                    titleEl.className = 'text-lg font-bold text-emerald-400';
                } else {
                    iconEl.textContent = '💫';
                    iconEl.classList.remove('animate-bounce');
                    titleEl.textContent = '오답';
                    titleEl.className = 'text-lg font-bold text-rose-400';
                }
                labelEl.textContent = finalNode.label;
            }
            return;
        }

        if (choicesPanel) choicesPanel.classList.remove('hidden');
        if (completePanel) completePanel.classList.add('hidden');

        container.innerHTML = avail.map(n => {
            const btnClass = getButtonClass(n.type);
            return `
                <button onclick="handleNodeClick('${n.id}')"
                    class="w-full text-left px-3 py-2.5 rounded-xl text-sm transition hover:scale-[1.02] ${btnClass}">
                    <div class="font-bold">${n.label}</div>
                    <div class="text-xs opacity-70 mt-0.5">${n.desc}</div>
                </button>
            `;
        }).join('');
    }

    function renderPathHistory() {
        const container = document.getElementById('path-history');
        const countEl = document.getElementById('path-count');
        if (!container) return;

        if (countEl) countEl.textContent = state.selectedPath.length - 1;

        container.innerHTML = state.selectedPath.slice(1).map(id => {
            const n = NODES[id];
            if (!n) return '';
            const color = getNodeColor(n.type);
            return `
                <span onclick="handleNodeClick('${id}')"
                    class="text-xs px-2 py-1 rounded-lg cursor-pointer hover:opacity-80 font-medium"
                    style="background-color: ${color}33; color: ${color}">
                    ${n.label}
                </span>
            `;
        }).join('');
    }

    function updateQuantumState() {
        document.getElementById('alpha-bar').style.width = `${state.stateVec.alpha * 100}%`;
        document.getElementById('alpha-value').textContent = `${Math.round(state.stateVec.alpha * 100)}%`;
        document.getElementById('beta-bar').style.width = `${state.stateVec.beta * 100}%`;
        document.getElementById('beta-value').textContent = `${Math.round(state.stateVec.beta * 100)}%`;
        document.getElementById('gamma-bar').style.width = `${state.stateVec.gamma * 100}%`;
        document.getElementById('gamma-value').textContent = `${Math.round(state.stateVec.gamma * 100)}%`;
    }

    // ========================================
    // 이벤트 핸들러
    // ========================================

    window.handleNodeClick = async function(nodeId) {
        const node = NODES[nodeId];
        if (!node) return;

        if (state.selectedPath.includes(nodeId)) {
            handleBack(nodeId);
            return;
        }

        const avail = getAvailableNodes();
        if (!avail.find(n => n.id === nodeId)) return;

        const newConcepts = new Set(state.activatedConcepts);
        if (node.concepts) {
            node.concepts.forEach((cid, i) => {
                setTimeout(() => {
                    state.collapsingConcept = cid;
                    renderConceptPanel();
                    setTimeout(() => {
                        state.collapsingConcept = null;
                        renderConceptPanel();
                    }, 500);
                }, i * 250);
                newConcepts.add(cid);
            });
        }
        state.activatedConcepts = newConcepts;

        state.selectedPath.push(nodeId);
        state.currentStage = node.stage;

        const ns = { ...state.stateVec };
        if (node.type === 'correct' || node.type === 'success') {
            ns.alpha = Math.min(0.95, ns.alpha + 0.1);
            ns.beta = Math.max(0.02, ns.beta - 0.05);
            ns.gamma = Math.max(0.02, ns.gamma - 0.05);
        } else if (node.type === 'wrong' || node.type === 'fail') {
            ns.beta = Math.min(0.85, ns.beta + 0.12);
            ns.alpha = Math.max(0.05, ns.alpha - 0.06);
            ns.gamma = Math.max(0.05, ns.gamma - 0.06);
        } else if (node.type === 'partial') {
            ns.alpha = Math.min(0.7, ns.alpha + 0.04);
            ns.gamma = Math.min(0.5, ns.gamma + 0.04);
        } else {
            ns.gamma = Math.min(0.7, ns.gamma + 0.12);
        }
        
        const sum = ns.alpha + ns.beta + ns.gamma;
        if (sum > 0) {
            ns.alpha = ns.alpha / sum;
            ns.beta = ns.beta / sum;
            ns.gamma = ns.gamma / sum;
        }
        
        state.stateVec = ns;

        state.history.push({
            path: [...state.selectedPath],
            state: { ...ns },
            concepts: new Set(newConcepts)
        });

        if (node.stage === 7) {
            state.isComplete = true;
        }

        if (state.sessionId && state.contentId) {
            try {
                await quantumAPI.saveSession();
            } catch (error) {
                console.warn('[quantum_modeling.js] DB 저장 오류:', error);
            }
        }

        renderAll();
    };

    async function handleBack(nodeId) {
        const idx = state.selectedPath.indexOf(nodeId);
        if (idx === -1) return;

        const h = state.history[idx];
        if (!h) return;

        state.selectedPath = [...h.path];
        state.currentStage = NODES[nodeId].stage;
        state.stateVec = { ...h.state };
        state.activatedConcepts = new Set(h.concepts);
        state.history = state.history.slice(0, idx + 1);
        state.isComplete = false;

        if (state.sessionId && state.contentId) {
            try {
                await quantumAPI.saveSession();
            } catch (error) {
                console.warn('[quantum_modeling.js] DB 저장 오류:', error);
            }
        }

        renderAll();
    }

    window.handleNodeHover = function(nodeId) {
        if (nodeId) {
            state.hoveredNode = NODES[nodeId];
        } else {
            state.hoveredNode = null;
        }
        const currentNode = NODES[state.selectedPath[state.selectedPath.length - 1]];
        renderNodeDetail(state.hoveredNode || currentNode);
    };

    window.backtrackOne = function() {
        if (state.selectedPath.length > 1) {
            const prevNodeId = state.selectedPath[state.selectedPath.length - 2];
            handleBack(prevNodeId);
        }
    };

    window.resetMaze = async function() {
        state.currentStage = 0;
        state.selectedPath = ['start'];
        state.activatedConcepts = new Set();
        state.collapsingConcept = null;
        state.stateVec = { alpha: 0.33, beta: 0.33, gamma: 0.34 };
        state.isComplete = false;
        state.history = [{ path: ['start'], state: { alpha: 0.33, beta: 0.33, gamma: 0.34 }, concepts: new Set() }];
        state.hoveredNode = null;

        if (state.contentId) {
            state.sessionId = 'QM_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }

        if (state.sessionId && state.contentId) {
            try {
                await quantumAPI.saveSession();
            } catch (error) {
                console.warn('[quantum_modeling.js] DB 저장 오류:', error);
            }
        }

        renderAll();
    };

    window.updateMapScale = function(value) {
        const scale = parseInt(value) / 100;
        state.mapScale = scale;

        const valueEl = document.getElementById('map-scale-value');
        if (valueEl) {
            valueEl.textContent = value + '%';
        }

        const svg = document.querySelector('#maze-svg');
        if (svg) {
            const baseWidth = 1000;
            const baseHeight = 1150;
            const newWidth = baseWidth / scale;
            const newHeight = baseHeight / scale;
            const offsetX = (baseWidth - newWidth) / 2;
            const offsetY = (baseHeight - newHeight) / 2;
            svg.setAttribute('viewBox', `${offsetX} ${offsetY} ${newWidth} ${newHeight}`);
        }
    };

    // ========================================
    // 노드 드래그 기능
    // ========================================

    function getSvgPoint(evt) {
        const svg = document.getElementById('maze-svg');
        if (!svg) return { x: 0, y: 0 };
        
        const pt = svg.createSVGPoint();
        pt.x = evt.clientX;
        pt.y = evt.clientY;
        
        const svgP = pt.matrixTransform(svg.getScreenCTM().inverse());
        return { x: svgP.x, y: svgP.y };
    }

    function handleDragStart(nodeId, evt) {
        if (!nodeId || !NODES[nodeId]) return;
        
        evt.preventDefault();
        evt.stopPropagation();
        
        const svgPoint = getSvgPoint(evt);
        const node = NODES[nodeId];
        
        state.isDragging = true;
        state.dragNodeId = nodeId;
        state.dragStartX = node.x;
        state.dragStartY = node.y;
        state.dragOffsetX = svgPoint.x - node.x;
        state.dragOffsetY = svgPoint.y - node.y;
        
        document.body.style.cursor = 'grabbing';
    }

    function handleDragMove(evt) {
        if (!state.isDragging || !state.dragNodeId) return;
        
        const svgPoint = getSvgPoint(evt);
        const node = NODES[state.dragNodeId];
        
        if (node) {
            node.x = svgPoint.x - state.dragOffsetX;
            node.y = svgPoint.y - state.dragOffsetY;
            
            // 실시간 렌더링
            renderMaze();
        }
    }

    async function handleDragEnd(evt) {
        if (!state.isDragging || !state.dragNodeId) return;
        
        const nodeId = state.dragNodeId;
        const node = NODES[nodeId];
        
        // 드래그 여부 확인 (클릭 방지용)
        const wasMoved = node && (Math.abs(node.x - state.dragStartX) > 5 || Math.abs(node.y - state.dragStartY) > 5);
        window.wasDragging = wasMoved;
        setTimeout(() => { window.wasDragging = false; }, 100);
        
        state.isDragging = false;
        state.dragNodeId = null;
        document.body.style.cursor = 'default';
        
        // 위치가 변경되었으면 저장
        if (wasMoved) {
            console.log('[quantum_modeling.js] 노드 위치 변경:', nodeId, node.x, node.y);
            
            if (state.contentId) {
                // DB에 저장 시도
                let result = await quantumAPI.updateNodePosition(nodeId, node.x, node.y);
                
                if (!result.success && result.needsSave) {
                    // 노드가 DB에 없으면 새로 저장
                    result = await quantumAPI.saveNodeToDb(node);
                }
                
                if (result.success) {
                    showToast('노드 위치가 저장되었습니다.', 'success');
                } else {
                    console.warn('[quantum_modeling.js] 위치 저장 실패:', result.error);
                    showToast('위치 저장 실패: ' + (result.error || ''), 'error');
                }
            }
        }
    }

    function showToast(message, type = 'info') {
        // 간단한 토스트 메시지
        const toast = document.createElement('div');
        toast.className = `fixed bottom-20 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded-lg text-sm font-medium z-50 transition-opacity duration-300 ${
            type === 'success' ? 'bg-emerald-500 text-white' : 
            type === 'error' ? 'bg-rose-500 text-white' : 
            'bg-slate-700 text-white'
        }`;
        toast.textContent = message;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 2000);
    }

    function initDragEvents() {
        const svg = document.getElementById('maze-svg');
        if (!svg) return;
        
        // 마우스 이벤트
        svg.addEventListener('mousemove', handleDragMove);
        svg.addEventListener('mouseup', handleDragEnd);
        svg.addEventListener('mouseleave', handleDragEnd);
        
        // 터치 이벤트 (모바일)
        svg.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1) {
                handleDragMove({ clientX: e.touches[0].clientX, clientY: e.touches[0].clientY });
            }
        });
        svg.addEventListener('touchend', handleDragEnd);
        
        console.log('[quantum_modeling.js] 드래그 이벤트 초기화 완료');
    }

    // 전역 함수로 노출 (SVG에서 호출)
    window.startNodeDrag = function(nodeId, evt) {
        handleDragStart(nodeId, evt);
    };

    // ========================================
    // 전체 렌더링
    // ========================================

    function renderAll() {
        renderStageProgress();
        renderConceptPanel();
        renderMaze();
        renderChoices();
        renderPathHistory();
        updateQuantumState();

        const currentNode = NODES[state.selectedPath[state.selectedPath.length - 1]];
        renderNodeDetail(state.hoveredNode || currentNode);
    }

    // ========================================
    // 히스토리 복원 함수
    // ========================================

    function restoreFromHistory(sessionData) {
        if (!sessionData || !sessionData.historySnapshot || sessionData.historySnapshot.length === 0) {
            return false;
        }

        try {
            state.history = sessionData.historySnapshot.map(h => ({
                path: h.path,
                state: h.state,
                concepts: new Set(h.concepts || [])
            }));

            const lastSnapshot = state.history[state.history.length - 1];
            if (lastSnapshot) {
                state.selectedPath = [...lastSnapshot.path];
                state.currentStage = sessionData.currentStage || 0;
                state.stateVec = { ...lastSnapshot.state };
                state.activatedConcepts = new Set(lastSnapshot.concepts || []);
                state.isComplete = sessionData.isComplete || false;
            }

            return true;
        } catch (error) {
            console.error('[quantum_modeling.js] 세션 복원 오류:', error);
            return false;
        }
    }

    // ========================================
    // 초기화
    // ========================================

    async function init() {
        console.log('[quantum_modeling.js] 초기화 시작');

        // DB 데이터 병합 (가장 먼저 실행)
        mergeDbData();

        if (window.QUANTUM_DATA) {
            state.contentId = window.QUANTUM_DATA.contentId || null;
            
            if (window.QUANTUM_DATA.sessionId) {
                state.sessionId = window.QUANTUM_DATA.sessionId;
            } else {
                state.sessionId = 'QM_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            }

            if (state.contentId && window.QUANTUM_DATA.hasExistingSession !== false) {
                try {
                    const result = await quantumAPI.getLastSession(state.contentId);
                    if (result.success && result.hasSession && result.data) {
                        state.sessionId = result.data.sessionId;
                        restoreFromHistory(result.data);
                    }
                } catch (error) {
                    console.warn('[quantum_modeling.js] 세션 복원 실패:', error);
                }
            }
        }

        renderAll();
        
        // 드래그 이벤트 초기화
        initDragEvents();
        
        console.log('[quantum_modeling.js] 초기화 완료 - 총 노드:', Object.keys(NODES).length, ', 총 엣지:', EDGES.length);
    }

    // DOM 로드 후 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // ========================================
    // 인지맵 성장시키기 기능
    // ========================================

    const growthState = {
        currentRequestType: null,
        currentSuggestion: null,
        suggestedNodes: [],
        suggestedEdges: [],
        isPreviewMode: false
    };

    const growthAPI = {
        baseUrl: '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/api/',

        async call(endpoint, data = {}, method = 'POST') {
            let url = this.baseUrl + endpoint;
            const options = { method: method, headers: {} };

            if (method === 'GET') {
                url += '?' + new URLSearchParams(data).toString();
            } else {
                options.headers['Content-Type'] = 'application/x-www-form-urlencoded';
                options.body = new URLSearchParams(data).toString();
            }

            try {
                const response = await fetch(url, options);
                return await response.json();
            } catch (error) {
                return { success: false, error: error.message };
            }
        },

        async createRequest(requestType, userInput = null) {
            return await this.call('quantum_growth_api.php', {
                action: 'createRequest',
                contentId: state.contentId,
                requestType: requestType,
                userInput: userInput
            });
        },

        async approveSuggestion(suggestionId) {
            return await this.call('quantum_growth_api.php', {
                action: 'approveSuggestion',
                suggestionId: suggestionId
            });
        },

        async rejectSuggestion(suggestionId) {
            return await this.call('quantum_growth_api.php', {
                action: 'rejectSuggestion',
                suggestionId: suggestionId
            });
        },

        async getVersionHistory(contentId) {
            return await this.call('quantum_version_api.php', {
                action: 'getVersionHistory',
                contentId: contentId
            }, 'GET');
        },

        async rollbackToVersion(versionId) {
            return await this.call('quantum_version_api.php', {
                action: 'rollbackToVersion',
                versionId: versionId
            });
        }
    };

    window.toggleGrowthMenu = function() {
        const menu = document.getElementById('growth-menu');
        const arrow = document.getElementById('growth-menu-arrow');
        
        if (menu.classList.contains('hidden')) {
            menu.classList.remove('hidden');
            arrow.style.transform = 'rotate(180deg)';
        } else {
            menu.classList.add('hidden');
            arrow.style.transform = 'rotate(0deg)';
        }
    };

    document.addEventListener('click', function(e) {
        const container = document.getElementById('growth-menu-container');
        if (container && !container.contains(e.target)) {
            const menu = document.getElementById('growth-menu');
            const arrow = document.getElementById('growth-menu-arrow');
            if (menu && !menu.classList.contains('hidden')) {
                menu.classList.add('hidden');
                arrow.style.transform = 'rotate(0deg)';
            }
        }
    });

    window.openGrowthModal = function(requestType) {
        growthState.currentRequestType = requestType;
        
        const modal = document.getElementById('growth-modal');
        const icon = document.getElementById('growth-modal-icon');
        const title = document.getElementById('growth-modal-title');
        const desc = document.getElementById('growth-modal-desc');
        const customInput = document.getElementById('custom-input-area');
        const loading = document.getElementById('growth-loading');
        const error = document.getElementById('growth-error');
        const footer = document.getElementById('growth-modal-footer');
        
        loading.classList.add('hidden');
        error.classList.add('hidden');
        footer.classList.remove('hidden');
        document.getElementById('custom-solution-input').value = '';
        
        switch (requestType) {
            case 'new_solution':
                icon.textContent = '✨';
                title.textContent = '새로운 풀이 탐색';
                desc.textContent = 'AI가 기존 인지맵을 분석하여 새로운 정답 풀이 경로를 제안합니다.';
                customInput.classList.add('hidden');
                break;
            case 'misconception':
                icon.textContent = '🔍';
                title.textContent = '오개념 풀이 탐색';
                desc.textContent = 'AI가 학생들이 자주 범하는 오개념이나 실수 경로를 분석하여 제안합니다.';
                customInput.classList.add('hidden');
                break;
            case 'custom_input':
                icon.textContent = '📝';
                title.textContent = '풀이 입력하여 제안';
                desc.textContent = '직접 입력한 풀이를 AI가 분석하여 인지맵에 반영할 노드와 엣지를 제안합니다.';
                customInput.classList.remove('hidden');
                break;
        }
        
        modal.classList.remove('hidden');
        toggleGrowthMenu();
    };

    window.closeGrowthModal = function() {
        document.getElementById('growth-modal').classList.add('hidden');
        growthState.currentRequestType = null;
    };

    window.generateSuggestion = async function() {
        const loading = document.getElementById('growth-loading');
        const error = document.getElementById('growth-error');
        const footer = document.getElementById('growth-modal-footer');
        
        let userInput = null;
        if (growthState.currentRequestType === 'custom_input') {
            userInput = document.getElementById('custom-solution-input').value.trim();
            if (!userInput) {
                showGrowthError('풀이를 입력해주세요.');
                return;
            }
        }
        
        loading.classList.remove('hidden');
        error.classList.add('hidden');
        footer.classList.add('hidden');
        
        try {
            const result = await growthAPI.createRequest(growthState.currentRequestType, userInput);
            
            if (result.success) {
                growthState.currentSuggestion = result.suggestion;
                growthState.currentSuggestion.suggestionId = result.suggestionId;
                closeGrowthModal();
                activatePreviewMode(result.suggestion);
            } else {
                showGrowthError(result.error || '제안 생성에 실패했습니다.');
                loading.classList.add('hidden');
                footer.classList.remove('hidden');
            }
        } catch (err) {
            showGrowthError(err.message || '네트워크 오류가 발생했습니다.');
            loading.classList.add('hidden');
            footer.classList.remove('hidden');
        }
    };

    function showGrowthError(message) {
        const error = document.getElementById('growth-error');
        document.getElementById('growth-error-message').textContent = message;
        error.classList.remove('hidden');
    }

    function activatePreviewMode(suggestion) {
        growthState.isPreviewMode = true;
        growthState.suggestedNodes = suggestion.nodes || [];
        growthState.suggestedEdges = suggestion.edges || [];
        
        const panel = document.getElementById('suggestion-panel');
        document.getElementById('suggestion-title').textContent = suggestion.title || 'AI 제안';
        document.getElementById('suggestion-desc').textContent = suggestion.description || '';
        document.getElementById('suggestion-nodes-count').textContent = growthState.suggestedNodes.length;
        document.getElementById('suggestion-edges-count').textContent = growthState.suggestedEdges.length;
        document.getElementById('suggestion-confidence').textContent = suggestion.confidence 
            ? Math.round(suggestion.confidence * 100) + '%' : '-';
        
        panel.classList.remove('translate-y-full');
        renderMazeWithPreview();
    }

    function renderMazeWithPreview() {
        renderMaze();
        
        if (!growthState.isPreviewMode) return;
        
        const nodesLayer = document.getElementById('nodes-layer');
        const edgesLayer = document.getElementById('edges-layer');
        
        // Add preview edges
        growthState.suggestedEdges.forEach(edge => {
            const sourceNode = NODES[edge.source] || growthState.suggestedNodes.find(n => n.node_id === edge.source);
            const targetNode = NODES[edge.target] || growthState.suggestedNodes.find(n => n.node_id === edge.target);
            
            if (sourceNode && targetNode) {
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', sourceNode.x);
                line.setAttribute('y1', sourceNode.y);
                line.setAttribute('x2', targetNode.x);
                line.setAttribute('y2', targetNode.y);
                line.setAttribute('stroke', '#10b981');
                line.setAttribute('stroke-width', '3');
                line.setAttribute('stroke-dasharray', '8 4');
                line.setAttribute('opacity', '0.8');
                line.classList.add('animate-pulse');
                edgesLayer.appendChild(line);
            }
        });
        
        // Add preview nodes
        growthState.suggestedNodes.forEach(node => {
            const color = getNodeColor(node.type);
            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.innerHTML = `
                <circle cx="${node.x}" cy="${node.y}" r="32" fill="${color}22" stroke="${color}"
                    stroke-width="3" stroke-dasharray="6 3" class="animate-pulse"/>
                <circle cx="${node.x}" cy="${node.y}" r="38" fill="none" stroke="#10b981"
                    stroke-width="2" opacity="0.6" class="animate-pulse-glow"/>
                <text x="${node.x}" y="${node.y + 5}" text-anchor="middle" font-size="14"
                    fill="#10b981" font-weight="bold">${node.label}</text>
                <text x="${node.x}" y="${node.y + 50}" text-anchor="middle" font-size="10"
                    fill="#10b981" opacity="0.8">[미리보기]</text>
            `;
            nodesLayer.appendChild(g);
        });
    }

    window.approveSuggestion = async function() {
        if (!growthState.currentSuggestion) return;
        
        try {
            const result = await growthAPI.approveSuggestion(growthState.currentSuggestion.suggestionId);
            
            if (result.success) {
                alert('제안이 승인되어 인지맵에 반영되었습니다.');
                deactivatePreviewMode();
                location.reload();
            } else {
                let errorMsg = result.error || '알 수 없는 오류';
                if (result.error_location) {
                    errorMsg += '\n\n위치: ' + result.error_location;
                }
                alert('승인 실패: ' + errorMsg);
                console.error('[approveSuggestion] Error:', result);
            }
        } catch (err) {
            alert('네트워크 오류: ' + err.message);
            console.error('[approveSuggestion] Exception:', err);
        }
    };

    window.rejectSuggestion = async function() {
        if (!growthState.currentSuggestion) return;
        
        try {
            await growthAPI.rejectSuggestion(growthState.currentSuggestion.suggestionId);
            deactivatePreviewMode();
        } catch (err) {
            alert('오류: ' + err.message);
        }
    };

    function deactivatePreviewMode() {
        growthState.isPreviewMode = false;
        growthState.currentSuggestion = null;
        growthState.suggestedNodes = [];
        growthState.suggestedEdges = [];
        
        document.getElementById('suggestion-panel').classList.add('translate-y-full');
        renderMaze();
    }

    window.openVersionHistory = async function() {
        const modal = document.getElementById('version-modal');
        const list = document.getElementById('version-list');
        
        modal.classList.remove('hidden');
        toggleGrowthMenu();
        
        list.innerHTML = '<div class="text-center py-8 text-slate-500"><div class="animate-spin w-8 h-8 border-2 border-purple-500 border-t-transparent rounded-full mx-auto mb-4"></div>버전 히스토리를 불러오는 중...</div>';
        
        try {
            const result = await growthAPI.getVersionHistory(state.contentId);
            
            if (result.success && result.versions) {
                if (result.versions.length === 0) {
                    list.innerHTML = '<div class="text-center py-8 text-slate-500"><span class="text-4xl mb-4 block">📭</span>저장된 버전이 없습니다.</div>';
                } else {
                    list.innerHTML = result.versions.map(v => `
                        <div class="bg-slate-900/50 rounded-xl p-4 border ${v.isCurrent ? 'border-emerald-500/50' : 'border-white/10'}">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-white">버전 ${v.versionNumber}</span>
                                        ${v.isCurrent ? '<span class="text-xs px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400">현재</span>' : ''}
                                    </div>
                                    <p class="text-sm text-slate-400">${v.changeSummary || '-'}</p>
                                    <p class="text-xs text-slate-500 mt-1">${formatDate(v.createdAt)}</p>
                                </div>
                                ${!v.isCurrent ? `<button onclick="rollbackVersion('${v.versionId}')" class="px-3 py-1.5 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-400 text-sm font-medium transition">롤백</button>` : ''}
                            </div>
                        </div>
                    `).join('');
                }
            } else {
                list.innerHTML = `<div class="text-center py-8 text-rose-400"><span class="text-4xl mb-4 block">⚠️</span>${result.error || '버전 히스토리를 불러오지 못했습니다.'}</div>`;
            }
        } catch (err) {
            list.innerHTML = `<div class="text-center py-8 text-rose-400"><span class="text-4xl mb-4 block">⚠️</span>${err.message || '네트워크 오류가 발생했습니다.'}</div>`;
        }
    };

    window.closeVersionHistory = function() {
        document.getElementById('version-modal').classList.add('hidden');
    };

    window.rollbackVersion = async function(versionId) {
        if (!confirm('이 버전으로 롤백하시겠습니까?')) return;
        
        try {
            const result = await growthAPI.rollbackToVersion(versionId);
            
            if (result.success) {
                alert(result.message || '롤백이 완료되었습니다.');
                closeVersionHistory();
                location.reload();
            } else {
                alert('롤백 실패: ' + (result.error || '알 수 없는 오류'));
            }
        } catch (err) {
            alert('오류: ' + err.message);
        }
    };

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        return date.toLocaleString('ko-KR');
    }

})();

