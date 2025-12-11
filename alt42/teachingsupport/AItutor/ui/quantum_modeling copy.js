/**
 * Quantum Collapse Learning Maze - Visualization Engine
 * 양자 붕괴 학습 미로 시각화 엔진
 *
 * DB에서 실제 데이터를 로드하여 트리 구조 시각화
 * API: /moodle/local/augmented_teacher/alt42/teachingsupport/api/analyze_quantum_path.php
 */

(function() {
    'use strict';

    // API 엔드포인트 (절대 경로로 설정)
    const API_BASE = window.location.origin + '/moodle/local/augmented_teacher/alt42/teachingsupport/api';

    // 전역 상태
    const state = {
        nodes: [],
        edges: [],
        concepts: {},
        rawApiData: null,
        selectedNode: null,
        visitedNodes: new Set(),
        currentPath: [],
        quantumState: { alpha: 33, beta: 33, gamma: 34 },
        isLoading: false,
        loadError: null
    };

    // SVG 요소 참조
    let svg, nodesLayer, edgesLayer;

    // 기본 폴백 트리 데이터 (API 실패 시 사용)
    const defaultTreeData = {
        id: 'root',
        label: '문제 인식',
        type: 'start',
        children: [
            {
                id: 'branch1',
                label: '조건 파악',
                type: 'correct',
                children: [
                    {
                        id: 'step1a',
                        label: '전략 수립',
                        type: 'step',
                        children: [
                            {
                                id: 'step1a1',
                                label: '정확한 풀이',
                                type: 'step',
                                children: [
                                    { id: 'end1', label: '정답!', type: 'success' }
                                ]
                            }
                        ]
                    }
                ]
            },
            {
                id: 'branch2',
                label: '부분 이해',
                type: 'partial',
                children: [
                    {
                        id: 'step2a',
                        label: '시행착오',
                        type: 'step',
                        children: [
                            {
                                id: 'step2a1',
                                label: '부분 풀이',
                                type: 'step',
                                children: [
                                    { id: 'end2', label: '부분 정답', type: 'partial_success' }
                                ]
                            }
                        ]
                    }
                ]
            },
            {
                id: 'branch3',
                label: '이해 부족',
                type: 'wrong',
                children: [
                    {
                        id: 'step3a',
                        label: '잘못된 접근',
                        type: 'wrong',
                        children: [
                            {
                                id: 'step3a1',
                                label: '계산 오류',
                                type: 'wrong',
                                children: [
                                    { id: 'end3', label: '오답', type: 'fail' }
                                ]
                            }
                        ]
                    }
                ]
            }
        ]
    };

    // 레이아웃 설정
    const layoutConfig = {
        width: 650,
        height: 560,
        marginTop: 60,
        marginBottom: 40,
        nodeRadius: {
            root: 35,
            branch: 28,
            step: 22,
            leaf: 18
        },
        levelHeight: 100,
        minHorizontalSpacing: 120
    };

    /**
     * API에서 양자 경로 데이터 로드
     * @returns {Promise<Object>} API 응답 데이터
     */
    async function loadQuantumPathFromAPI() {
        const data = window.QUANTUM_DATA || {};
        const contentsId = data.contentsId || '';

        updateLoadingStatus('API에서 경로 데이터 로드 중...');

        try {
            const response = await fetch(`${API_BASE}/analyze_quantum_path.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    contentsId: contentsId,
                    questionData: data.questionData || {},
                    imageUrl: data.imageUrl || ''
                })
            });

            if (!response.ok) {
                throw new Error(`API 오류: ${response.status} ${response.statusText}`);
            }

            const result = await response.json();

            if (result.success && result.data) {
                console.log('[quantum_modeling.js] API 데이터 로드 성공:', result.meta);
                state.rawApiData = result.data;
                state.concepts = result.data.concepts || {};
                return result.data;
            } else {
                throw new Error(result.message || 'API 응답 오류');
            }
        } catch (error) {
            console.error('[quantum_modeling.js:L170] API 로드 실패:', error.message);
            updateLoadingStatus('데이터 로드 실패: ' + error.message, true);
            state.loadError = error.message;
            return null;
        }
    }

    /**
     * API 노드/엣지 데이터를 트리 구조로 변환
     * @param {Object} apiData - API에서 받은 노드/엣지 데이터
     * @returns {Object} 트리 구조 데이터
     */
    function convertApiDataToTree(apiData) {
        if (!apiData || !apiData.nodes || !apiData.edges) {
            console.warn('[quantum_modeling.js] API 데이터 없음, 기본 트리 사용');
            return defaultTreeData;
        }

        const nodes = apiData.nodes;
        const edges = apiData.edges;

        // 노드 맵 생성
        const nodeMap = {};
        Object.values(nodes).forEach(node => {
            nodeMap[node.id] = {
                id: node.id,
                label: node.label,
                type: mapNodeType(node.type),
                stage: node.stage || 0,
                concepts: node.concepts || [],
                x: node.x,
                y: node.y,
                children: []
            };
        });

        // 엣지로 부모-자식 관계 구축
        edges.forEach(edge => {
            const [fromId, toId] = edge;
            if (nodeMap[fromId] && nodeMap[toId]) {
                nodeMap[fromId].children.push(nodeMap[toId]);
            }
        });

        // 루트 노드 찾기 (부모가 없는 노드)
        const childIds = new Set(edges.map(e => e[1]));
        const rootNodes = Object.values(nodeMap).filter(n => !childIds.has(n.id));

        if (rootNodes.length === 0) {
            console.warn('[quantum_modeling.js] 루트 노드 없음, 기본 트리 사용');
            return defaultTreeData;
        }

        // 여러 루트가 있으면 가상 루트 생성
        if (rootNodes.length > 1) {
            return {
                id: 'virtual_root',
                label: '문제 인식',
                type: 'start',
                children: rootNodes
            };
        }

        return rootNodes[0];
    }

    /**
     * API 노드 타입을 내부 타입으로 매핑
     */
    function mapNodeType(apiType) {
        const typeMap = {
            'start': 'start',
            'correct': 'correct',
            'partial': 'partial',
            'wrong': 'wrong',
            'confused': 'wrong',
            'success': 'success',
            'fail': 'fail',
            'step': 'step'
        };
        return typeMap[apiType] || 'step';
    }

    /**
     * 로딩 상태 업데이트
     * @param {string} message - 표시할 메시지
     * @param {boolean} isError - 에러 메시지 여부
     */
    function updateLoadingStatus(message, isError = false) {
        const statusEl = document.getElementById('loading-status');
        if (statusEl) {
            if (isError) {
                statusEl.innerHTML = `
                    <span class="text-red-400">⚠️ ${message}</span>
                    <br>
                    <span class="text-slate-500 text-xs">기본 데이터로 진행합니다</span>
                `;
            } else {
                statusEl.textContent = message;
            }
        }
    }

    /**
     * 트리 레이아웃 계산 - Reingold-Tilford 알고리즘 기반
     * 균형잡힌 수평 배치를 보장
     */
    function calculateTreeLayout(tree) {
        const nodes = [];
        const edges = [];

        // 1단계: 트리를 평탄화하고 각 노드의 깊이 계산
        function assignDepth(node, depth = 0, parent = null) {
            node.depth = depth;
            node.parent = parent;
            node.children = node.children || [];

            node.children.forEach(child => {
                assignDepth(child, depth + 1, node);
            });
        }
        assignDepth(tree);

        // 2단계: 각 레벨의 노드 수집
        const levels = [];
        function collectLevels(node) {
            if (!levels[node.depth]) levels[node.depth] = [];
            levels[node.depth].push(node);
            node.children.forEach(collectLevels);
        }
        collectLevels(tree);

        // 3단계: 서브트리 너비 계산 (리프부터 루트까지)
        function calculateSubtreeWidth(node) {
            if (!node.children || node.children.length === 0) {
                node.subtreeWidth = 1;
                return 1;
            }

            let totalWidth = 0;
            node.children.forEach(child => {
                totalWidth += calculateSubtreeWidth(child);
            });

            node.subtreeWidth = Math.max(totalWidth, 1);
            return node.subtreeWidth;
        }
        calculateSubtreeWidth(tree);

        // 4단계: X 좌표 계산 (균형잡힌 배치)
        const centerX = layoutConfig.width / 2;

        function assignXPositions(node, leftBound, rightBound) {
            const nodeX = (leftBound + rightBound) / 2;
            node.x = nodeX;

            if (node.children && node.children.length > 0) {
                const totalSubtreeWidth = node.children.reduce((sum, child) => sum + child.subtreeWidth, 0);
                const availableWidth = rightBound - leftBound;

                let currentX = leftBound;
                node.children.forEach(child => {
                    const childWidth = (child.subtreeWidth / totalSubtreeWidth) * availableWidth;
                    assignXPositions(child, currentX, currentX + childWidth);
                    currentX += childWidth;
                });
            }
        }

        // 전체 너비를 기준으로 배치
        const totalWidth = layoutConfig.width - 80; // 좌우 마진
        assignXPositions(tree, 40, totalWidth + 40);

        // 5단계: Y 좌표 계산 (레벨 기반)
        function assignYPositions(node) {
            node.y = layoutConfig.marginTop + (node.depth * layoutConfig.levelHeight);
            node.children.forEach(assignYPositions);
        }
        assignYPositions(tree);

        // 6단계: 노드 및 엣지 배열 생성
        function collectNodesAndEdges(node) {
            const nodeType = getNodeType(node);
            nodes.push({
                id: node.id,
                label: node.label,
                type: node.type,
                x: node.x,
                y: node.y,
                radius: layoutConfig.nodeRadius[nodeType] || 22,
                depth: node.depth,
                children: node.children.map(c => c.id)
            });

            node.children.forEach(child => {
                edges.push({
                    source: node.id,
                    target: child.id,
                    sourceX: node.x,
                    sourceY: node.y,
                    targetX: child.x,
                    targetY: child.y
                });
                collectNodesAndEdges(child);
            });
        }
        collectNodesAndEdges(tree);

        return { nodes, edges };
    }

    /**
     * 노드 타입 결정
     */
    function getNodeType(node) {
        if (node.depth === 0) return 'root';
        if (node.depth === 1) return 'branch';
        if (!node.children || node.children.length === 0) return 'leaf';
        return 'step';
    }

    /**
     * 노드 색상 가져오기
     */
    function getNodeColor(type) {
        const colors = {
            start: '#06b6d4',      // cyan
            correct: '#10b981',    // emerald
            partial: '#8b5cf6',    // purple
            wrong: '#f59e0b',      // amber
            step: '#6366f1',       // indigo
            success: '#22c55e',    // green
            partial_success: '#a855f7', // purple
            fail: '#ef4444'        // red
        };
        return colors[type] || '#64748b';
    }

    /**
     * 노드 테두리 색상
     */
    function getNodeStrokeColor(type) {
        const colors = {
            start: '#0891b2',
            correct: '#059669',
            partial: '#7c3aed',
            wrong: '#d97706',
            step: '#4f46e5',
            success: '#16a34a',
            partial_success: '#9333ea',
            fail: '#dc2626'
        };
        return colors[type] || '#475569';
    }

    /**
     * SVG 렌더링
     * @param {Object} treeData - 트리 데이터 (API에서 로드하거나 기본값)
     */
    function renderSVG(treeData = null) {
        svg = document.getElementById('maze-svg');
        nodesLayer = document.getElementById('nodes-layer');
        edgesLayer = document.getElementById('edges-layer');

        if (!svg || !nodesLayer || !edgesLayer) {
            console.error('[quantum_modeling.js:L414] SVG 요소를 찾을 수 없습니다.');
            return;
        }

        // 트리 데이터 결정: API 데이터 > 인자 > 기본값
        const dataToUse = treeData || defaultTreeData;
        console.log('[quantum_modeling.js:L420] 렌더링 데이터:', dataToUse.id);

        // 레이아웃 계산
        const layout = calculateTreeLayout(dataToUse);
        state.nodes = layout.nodes;
        state.edges = layout.edges;

        // 엣지 렌더링
        renderEdges();

        // 노드 렌더링
        renderNodes();

        // 개념 패널 업데이트
        updateConceptPanel();

        // 선택지 업데이트
        updateChoices();
    }

    /**
     * 엣지(연결선) 렌더링
     */
    function renderEdges() {
        edgesLayer.innerHTML = '';

        state.edges.forEach(edge => {
            const sourceNode = state.nodes.find(n => n.id === edge.source);
            const targetNode = state.nodes.find(n => n.id === edge.target);

            if (!sourceNode || !targetNode) return;

            // 곡선 경로 계산 (베지어 커브)
            const midY = (sourceNode.y + targetNode.y) / 2;
            const pathData = `M ${sourceNode.x} ${sourceNode.y + sourceNode.radius}
                              Q ${sourceNode.x} ${midY}, ${(sourceNode.x + targetNode.x) / 2} ${midY}
                              Q ${targetNode.x} ${midY}, ${targetNode.x} ${targetNode.y - targetNode.radius}`;

            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', pathData);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', 'rgba(148, 163, 184, 0.3)');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('stroke-dasharray', '4,4');
            path.classList.add('quantum-edge');
            path.dataset.source = edge.source;
            path.dataset.target = edge.target;

            edgesLayer.appendChild(path);
        });
    }

    /**
     * 노드 렌더링
     */
    function renderNodes() {
        nodesLayer.innerHTML = '';

        state.nodes.forEach(node => {
            const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            group.classList.add('quantum-node');
            group.dataset.id = node.id;
            group.style.transform = `translate(${node.x}px, ${node.y}px)`;
            group.setAttribute('transform', `translate(${node.x}, ${node.y})`);

            // 외곽 글로우 효과
            const glowCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            glowCircle.setAttribute('r', node.radius + 4);
            glowCircle.setAttribute('fill', 'none');
            glowCircle.setAttribute('stroke', getNodeColor(node.type));
            glowCircle.setAttribute('stroke-width', '2');
            glowCircle.setAttribute('opacity', '0.3');
            glowCircle.setAttribute('filter', 'url(#glow)');

            // 메인 원
            const mainCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            mainCircle.setAttribute('r', node.radius);
            mainCircle.setAttribute('fill', 'rgba(15, 23, 42, 0.9)');
            mainCircle.setAttribute('stroke', getNodeStrokeColor(node.type));
            mainCircle.setAttribute('stroke-width', '2.5');

            // 내부 채우기 (방문한 경우)
            const fillCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            fillCircle.setAttribute('r', node.radius - 4);
            fillCircle.setAttribute('fill', state.visitedNodes.has(node.id) ? getNodeColor(node.type) : 'transparent');
            fillCircle.setAttribute('opacity', '0.3');

            // 아이콘/텍스트
            const icon = getNodeIcon(node);
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('dominant-baseline', 'central');
            text.setAttribute('fill', 'white');
            text.setAttribute('font-size', node.depth === 0 ? '16' : '12');
            text.textContent = icon;

            // 라벨
            const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            label.setAttribute('text-anchor', 'middle');
            label.setAttribute('y', node.radius + 16);
            label.setAttribute('fill', 'rgba(148, 163, 184, 0.8)');
            label.setAttribute('font-size', '11');
            label.textContent = node.label;

            group.appendChild(glowCircle);
            group.appendChild(mainCircle);
            group.appendChild(fillCircle);
            group.appendChild(text);
            group.appendChild(label);

            // 클릭 이벤트
            group.addEventListener('click', () => handleNodeClick(node));
            group.addEventListener('mouseenter', () => handleNodeHover(node, true));
            group.addEventListener('mouseleave', () => handleNodeHover(node, false));

            nodesLayer.appendChild(group);
        });
    }

    /**
     * 노드 아이콘 가져오기
     */
    function getNodeIcon(node) {
        const icons = {
            start: '🔮',
            correct: '✓',
            partial: '◐',
            wrong: '△',
            step: '○',
            success: '★',
            partial_success: '✦',
            fail: '✗'
        };
        return icons[node.type] || '○';
    }

    /**
     * 노드 클릭 핸들러
     */
    function handleNodeClick(node) {
        // 방문 처리
        state.visitedNodes.add(node.id);
        state.selectedNode = node;
        state.currentPath.push(node.id);

        // 양자 상태 업데이트
        updateQuantumState(node.type);

        // UI 업데이트
        renderNodes();
        highlightPath();
        updateConceptPanel();
        updateChoices();

        // 종료 노드 체크
        if (node.type === 'success' || node.type === 'partial_success' || node.type === 'fail') {
            showCompletionPanel(node.type);
        }
    }

    /**
     * 노드 호버 핸들러
     */
    function handleNodeHover(node, isHovering) {
        const nodeElement = document.querySelector(`[data-id="${node.id}"]`);
        if (nodeElement) {
            if (isHovering) {
                nodeElement.classList.add('selected');
            } else {
                nodeElement.classList.remove('selected');
            }
        }
    }

    /**
     * 경로 하이라이트
     */
    function highlightPath() {
        // 모든 엣지 초기화
        document.querySelectorAll('.quantum-edge').forEach(edge => {
            edge.classList.remove('active');
            edge.setAttribute('stroke', 'rgba(148, 163, 184, 0.3)');
        });

        // 방문한 경로 하이라이트
        for (let i = 0; i < state.currentPath.length - 1; i++) {
            const source = state.currentPath[i];
            const target = state.currentPath[i + 1];
            const edge = document.querySelector(`[data-source="${source}"][data-target="${target}"]`);
            if (edge) {
                edge.classList.add('active');
                edge.setAttribute('stroke', 'url(#pathGrad)');
                edge.setAttribute('stroke-dasharray', 'none');
            }
        }
    }

    /**
     * 양자 상태 업데이트
     */
    function updateQuantumState(nodeType) {
        switch (nodeType) {
            case 'correct':
            case 'success':
                state.quantumState.alpha = Math.min(100, state.quantumState.alpha + 15);
                state.quantumState.beta = Math.max(0, state.quantumState.beta - 10);
                state.quantumState.gamma = Math.max(0, state.quantumState.gamma - 5);
                break;
            case 'wrong':
            case 'fail':
                state.quantumState.beta = Math.min(100, state.quantumState.beta + 15);
                state.quantumState.alpha = Math.max(0, state.quantumState.alpha - 10);
                state.quantumState.gamma = Math.max(0, state.quantumState.gamma - 5);
                break;
            case 'partial':
            case 'partial_success':
                state.quantumState.gamma = Math.min(100, state.quantumState.gamma + 10);
                break;
        }

        // 정규화
        const total = state.quantumState.alpha + state.quantumState.beta + state.quantumState.gamma;
        state.quantumState.alpha = Math.round((state.quantumState.alpha / total) * 100);
        state.quantumState.beta = Math.round((state.quantumState.beta / total) * 100);
        state.quantumState.gamma = 100 - state.quantumState.alpha - state.quantumState.beta;

        // UI 업데이트
        document.getElementById('alpha-bar').style.width = state.quantumState.alpha + '%';
        document.getElementById('alpha-value').textContent = state.quantumState.alpha + '%';
        document.getElementById('beta-bar').style.width = state.quantumState.beta + '%';
        document.getElementById('beta-value').textContent = state.quantumState.beta + '%';
        document.getElementById('gamma-bar').style.width = state.quantumState.gamma + '%';
        document.getElementById('gamma-value').textContent = state.quantumState.gamma + '%';
    }

    /**
     * 개념 패널 업데이트
     */
    function updateConceptPanel() {
        const conceptList = document.getElementById('concept-list');
        const activatedCount = document.getElementById('activated-count');
        const totalConcepts = document.getElementById('total-concepts');
        const conceptProgress = document.getElementById('concept-progress');

        if (!conceptList) return;

        conceptList.innerHTML = '';

        state.nodes.forEach(node => {
            const item = document.createElement('div');
            item.className = 'concept-item p-2 rounded-lg bg-slate-800/50 border border-white/5';

            const isVisited = state.visitedNodes.has(node.id);
            if (isVisited) {
                item.classList.add('active');
                item.style.borderColor = getNodeColor(node.type);
            }

            item.innerHTML = `
                <div class="flex items-center gap-2">
                    <span class="text-xs" style="color: ${getNodeColor(node.type)}">${getNodeIcon(node)}</span>
                    <span class="text-xs ${isVisited ? 'text-white' : 'text-slate-500'}">${node.label}</span>
                </div>
            `;

            item.addEventListener('click', () => {
                const nodeGroup = document.querySelector(`[data-id="${node.id}"]`);
                if (nodeGroup) {
                    nodeGroup.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    handleNodeClick(node);
                }
            });

            conceptList.appendChild(item);
        });

        const visited = state.visitedNodes.size;
        const total = state.nodes.length;
        activatedCount.textContent = visited;
        totalConcepts.textContent = total;
        conceptProgress.style.width = ((visited / total) * 100) + '%';
    }

    /**
     * 선택지 업데이트
     */
    function updateChoices() {
        const container = document.getElementById('choices-container');
        if (!container) return;

        container.innerHTML = '';

        // 현재 노드의 자식들을 선택지로 표시
        const currentNode = state.selectedNode || state.nodes.find(n => n.depth === 0);
        if (!currentNode) return;

        const children = state.nodes.filter(n => currentNode.children.includes(n.id));

        if (children.length === 0) {
            container.innerHTML = '<span class="text-slate-500 text-xs">경로 종료</span>';
            return;
        }

        children.forEach(child => {
            const btn = document.createElement('button');
            btn.className = 'px-3 py-1.5 rounded-lg text-xs font-medium transition';
            btn.style.backgroundColor = `${getNodeColor(child.type)}20`;
            btn.style.color = getNodeColor(child.type);
            btn.style.border = `1px solid ${getNodeColor(child.type)}40`;
            btn.textContent = child.label;

            btn.addEventListener('click', () => handleNodeClick(child));
            btn.addEventListener('mouseenter', () => {
                btn.style.backgroundColor = `${getNodeColor(child.type)}40`;
            });
            btn.addEventListener('mouseleave', () => {
                btn.style.backgroundColor = `${getNodeColor(child.type)}20`;
            });

            container.appendChild(btn);
        });
    }

    /**
     * 완료 패널 표시
     */
    function showCompletionPanel(type) {
        const gamePanel = document.getElementById('game-panel');
        const completePanel = document.getElementById('complete-panel');

        if (!gamePanel || !completePanel) return;

        gamePanel.classList.add('hidden');
        completePanel.classList.remove('hidden');

        const icon = document.getElementById('complete-icon');
        const title = document.getElementById('complete-title');
        const concepts = document.getElementById('complete-concepts');
        const steps = document.getElementById('complete-steps');

        if (type === 'success') {
            icon.textContent = '🎉';
            title.textContent = '정답 붕괴!';
            title.className = 'text-lg font-bold text-emerald-400';
        } else if (type === 'partial_success') {
            icon.textContent = '✨';
            title.textContent = '부분 정답!';
            title.className = 'text-lg font-bold text-purple-400';
        } else {
            icon.textContent = '💫';
            title.textContent = '다시 시도해보세요';
            title.className = 'text-lg font-bold text-amber-400';
        }

        concepts.textContent = state.visitedNodes.size;
        steps.textContent = state.currentPath.length;
    }

    /**
     * 이미지 확대 열기
     */
    window.openImageZoom = function(container) {
        const img = container.querySelector('img');
        if (!img || img.classList.contains('hidden')) return;

        const modal = document.getElementById('image-zoom-modal');
        const zoomedImg = document.getElementById('zoomed-image');

        zoomedImg.src = img.src;
        modal.classList.remove('hidden');
    };

    /**
     * 이미지 확대 닫기
     */
    window.closeImageZoom = function() {
        document.getElementById('image-zoom-modal').classList.add('hidden');
    };

    /**
     * 뉴런 배양 모달 열기
     */
    window.openNeuronCultureModal = function() {
        const modal = document.getElementById('neuron-culture-modal');
        const parentSelect = document.getElementById('neuron-parent-node');

        // 노드 옵션 채우기
        parentSelect.innerHTML = '';
        state.nodes.forEach(node => {
            if (node.type !== 'success' && node.type !== 'fail') {
                const option = document.createElement('option');
                option.value = node.id;
                option.textContent = `${node.label} (깊이: ${node.depth})`;
                parentSelect.appendChild(option);
            }
        });

        modal.classList.remove('hidden');
    };

    /**
     * 뉴런 배양 모달 닫기
     */
    window.closeNeuronCultureModal = function() {
        document.getElementById('neuron-culture-modal').classList.add('hidden');
    };

    /**
     * 새 경로 추가
     */
    window.addNewPath = function() {
        openNeuronCultureModal();
    };

    /**
     * 뉴런 경로 제출
     */
    window.submitNeuronPath = function() {
        const title = document.getElementById('neuron-title').value;
        const description = document.getElementById('neuron-description').value;
        const parentId = document.getElementById('neuron-parent-node').value;

        if (!title || !description || !parentId) {
            alert('모든 필드를 입력해주세요.');
            return;
        }

        // 상태 표시
        document.getElementById('neuron-analysis-status').classList.remove('hidden');
        document.getElementById('neuron-status-text').textContent = 'AI가 풀이를 분석하고 있습니다...';

        // 시뮬레이션 (실제로는 API 호출)
        setTimeout(() => {
            document.getElementById('neuron-status-text').textContent = '새 경로를 생성하고 있습니다...';

            setTimeout(() => {
                closeNeuronCultureModal();
                document.getElementById('neuron-analysis-status').classList.add('hidden');
                alert('새로운 경로가 성공적으로 추가되었습니다!');

                // 입력 초기화
                document.getElementById('neuron-title').value = '';
                document.getElementById('neuron-description').value = '';
            }, 1500);
        }, 2000);
    };

    /**
     * 넛지 숨기기
     */
    window.hideNudge = function() {
        document.getElementById('nudge-popup').classList.add('hidden');
    };

    /**
     * 되돌리기
     */
    window.backtrackOne = function() {
        if (state.currentPath.length > 1) {
            state.currentPath.pop();
            const lastNodeId = state.currentPath[state.currentPath.length - 1];
            state.selectedNode = state.nodes.find(n => n.id === lastNodeId);

            // 게임 패널 복원
            document.getElementById('game-panel').classList.remove('hidden');
            document.getElementById('complete-panel').classList.add('hidden');

            highlightPath();
            updateChoices();
        }
    };

    /**
     * 미로 초기화
     */
    window.resetMaze = function() {
        state.visitedNodes.clear();
        state.currentPath = [];
        state.selectedNode = null;
        state.quantumState = { alpha: 33, beta: 33, gamma: 34 };

        // UI 초기화
        document.getElementById('game-panel').classList.remove('hidden');
        document.getElementById('complete-panel').classList.add('hidden');

        document.getElementById('alpha-bar').style.width = '33%';
        document.getElementById('alpha-value').textContent = '33%';
        document.getElementById('beta-bar').style.width = '33%';
        document.getElementById('beta-value').textContent = '33%';
        document.getElementById('gamma-bar').style.width = '34%';
        document.getElementById('gamma-value').textContent = '34%';

        renderNodes();
        highlightPath();
        updateConceptPanel();
        updateChoices();
    };

    /**
     * 문제 이미지 로드
     */
    function loadQuestionImage() {
        const data = window.QUANTUM_DATA;
        if (!data) return;

        const img = document.getElementById('question-image');
        const noImage = document.getElementById('no-image');
        const questionText = document.getElementById('question-text');

        const imageUrl = data.questionImageUrl || data.solutionImageUrl || data.imageUrl;

        if (imageUrl) {
            img.src = imageUrl;
            img.onload = function() {
                img.classList.remove('hidden');
                noImage.classList.add('hidden');
            };
            img.onerror = function() {
                img.classList.add('hidden');
                noImage.classList.remove('hidden');
            };
        }

        if (data.questionData && data.questionData.narration_text) {
            questionText.textContent = data.questionData.narration_text.substring(0, 200) + '...';
        }
    }

    /**
     * 학습 유형 뱃지 업데이트
     */
    function updateLearnerBadges() {
        const container = document.getElementById('learner-badges');
        if (!container) return;

        const badges = [
            { label: '개념 이해', color: '#06b6d4' },
            { label: '문제 풀이', color: '#8b5cf6' },
            { label: '오답 분석', color: '#f59e0b' }
        ];

        container.innerHTML = badges.map(badge =>
            `<span class="px-2 py-0.5 rounded text-[10px]" style="background: ${badge.color}20; color: ${badge.color}; border: 1px solid ${badge.color}40;">${badge.label}</span>`
        ).join('');
    }

    /**
     * 초기화 - API에서 데이터 로드 후 렌더링
     */
    async function init() {
        console.log('[quantum_modeling.js:L968] 초기화 시작');
        state.isLoading = true;

        try {
            // 1. API에서 양자 경로 데이터 로드 시도
            updateLoadingStatus('DB에서 경로 데이터 로드 중...');
            const apiData = await loadQuantumPathFromAPI();

            // 2. API 데이터를 트리 구조로 변환
            let treeData;
            if (apiData) {
                updateLoadingStatus('트리 구조 변환 중...');
                treeData = convertApiDataToTree(apiData);
                console.log('[quantum_modeling.js:L980] API 데이터로 트리 생성:', treeData.id);
            } else {
                console.log('[quantum_modeling.js:L982] API 실패, 기본 트리 사용');
                treeData = defaultTreeData;
            }

            // 3. 로딩 화면 숨기고 메인 컨테이너 표시
            updateLoadingStatus('렌더링 중...');

            setTimeout(() => {
                const loadingScreen = document.getElementById('loading-screen');
                const mainContainer = document.getElementById('main-container');

                if (loadingScreen) loadingScreen.classList.add('hidden');
                if (mainContainer) mainContainer.classList.remove('hidden');

                // 4. SVG 렌더링 (API 데이터 또는 기본 데이터)
                renderSVG(treeData);

                // 5. 문제 이미지 로드
                loadQuestionImage();

                // 6. 학습 유형 뱃지 업데이트
                updateLearnerBadges();

                // 7. 루트 노드 자동 선택
                const rootNode = state.nodes.find(n => n.depth === 0);
                if (rootNode) {
                    handleNodeClick(rootNode);
                }

                state.isLoading = false;
                console.log('[quantum_modeling.js:L1010] 초기화 완료, 노드 수:', state.nodes.length);
            }, 500);

        } catch (error) {
            console.error('[quantum_modeling.js:L1014] 초기화 오류:', error.message);
            state.loadError = error.message;
            state.isLoading = false;

            // 오류 발생 시 기본 데이터로 렌더링
            const loadingScreen = document.getElementById('loading-screen');
            const mainContainer = document.getElementById('main-container');

            if (loadingScreen) loadingScreen.classList.add('hidden');
            if (mainContainer) mainContainer.classList.remove('hidden');

            renderSVG(defaultTreeData);
            loadQuestionImage();
            updateLearnerBadges();

            const rootNode = state.nodes.find(n => n.depth === 0);
            if (rootNode) {
                handleNodeClick(rootNode);
            }
        }
    }

    // DOM 로드 후 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
