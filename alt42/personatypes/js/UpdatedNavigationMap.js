/**
 * 🗺️ 업데이트된 네비게이션 맵
 * 9개 수학 사고 노드를 중심으로 60개 인지편향을 매핑
 */

class UpdatedNavigationMap {
    constructor() {
        this.framework = window.mathFramework || new MathematicalThinkingFramework();
        this.container = null;
        this.svg = null;
        this.currentView = 'constellation'; // constellation, network, journey
        this.selectedNode = null;
        this.activeBiases = new Set();
        
        this.init();
    }

    /**
     * 🚀 초기화
     */
    init() {
        this.createContainer();
        this.renderConstellationView();
        this.setupInteractions();
        this.setupKeyboardHandlers();
        this.startAnimation();
    }
    
    /**
     * ⌨️ 키보드 핸들러 설정
     */
    setupKeyboardHandlers() {
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.container && this.container.classList.contains('active')) {
                this.close();
            }
        });
    }

    /**
     * 📦 컨테이너 생성
     */
    createContainer() {
        // 기존 컨테이너 제거
        const existing = document.getElementById('math-navigation-map');
        if (existing) existing.remove();

        // 새 컨테이너 생성
        this.container = document.createElement('div');
        this.container.id = 'math-navigation-map';
        this.container.className = 'math-navigation-container';
        this.container.innerHTML = `
            <div class="map-header">
                <h2>🌌 수학적 사고의 우주 지도</h2>
                <button class="close-map-btn" onclick="updatedNavigationMap.close()">✕</button>
                <div class="view-controls">
                    <button class="view-btn active" data-view="constellation">
                        ✨ 별자리 뷰
                    </button>
                    <button class="view-btn" data-view="network">
                        🕸️ 네트워크 뷰
                    </button>
                    <button class="view-btn" data-view="journey">
                        🚀 여정 뷰
                    </button>
                </div>
            </div>
            <div class="map-canvas" id="map-canvas"></div>
            <div class="map-sidebar" id="map-sidebar">
                <div class="node-info">
                    <h3>사고 노드 정보</h3>
                    <div id="node-details">노드를 선택하세요</div>
                </div>
                <div class="bias-list">
                    <h3>연결된 인지편향</h3>
                    <div id="bias-details">-</div>
                </div>
                <div class="problem-solver">
                    <h3>문제 해결 시뮬레이터</h3>
                    <button id="simulate-btn">시뮬레이션 시작</button>
                    <div id="simulation-result"></div>
                </div>
            </div>
        `;

        document.body.appendChild(this.container);
        this.addStyles();
    }

    /**
     * 🎨 스타일 추가
     */
    addStyles() {
        if (document.getElementById('math-nav-styles')) return;

        const styles = document.createElement('style');
        styles.id = 'math-nav-styles';
        styles.textContent = `
            .math-navigation-container {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: linear-gradient(135deg, #0a0e27 0%, #1a1e3a 100%);
                z-index: 10000;
                display: none;
                color: white;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                opacity: 0;
                transition: opacity 0.3s ease;
            }

            .math-navigation-container.active {
                display: flex;
                flex-direction: column;
                opacity: 1;
                display: flex;
            }

            .map-header {
                padding: 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: rgba(0, 0, 0, 0.3);
                position: relative;
            }

            .map-header h2 {
                margin: 0;
                font-size: 1.8em;
                background: linear-gradient(135deg, #667eea, #764ba2);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
            }

            .close-map-btn {
                position: absolute;
                top: 20px;
                right: 20px;
                background: rgba(239, 68, 68, 0.2);
                border: 1px solid rgba(239, 68, 68, 0.5);
                color: #fff;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                font-size: 1.5rem;
                cursor: pointer;
                transition: all 0.3s ease;
                z-index: 10;
            }

            .close-map-btn:hover {
                background: rgba(239, 68, 68, 0.4);
                transform: rotate(90deg);
            }

            .view-controls {
                display: flex;
                gap: 10px;
                margin-left: auto;
                margin-right: 60px;
            }

            .view-btn {
                padding: 8px 16px;
                background: rgba(255, 255, 255, 0.1);
                border: 1px solid rgba(255, 255, 255, 0.2);
                border-radius: 20px;
                color: white;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .view-btn:hover {
                background: rgba(255, 255, 255, 0.2);
            }

            .view-btn.active {
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-color: transparent;
            }

            .map-canvas {
                flex: 1;
                position: relative;
                overflow: hidden;
            }

            .map-sidebar {
                position: absolute;
                right: 20px;
                top: 100px;
                width: 300px;
                background: rgba(0, 0, 0, 0.8);
                border: 1px solid rgba(255, 255, 255, 0.1);
                border-radius: 15px;
                padding: 20px;
                backdrop-filter: blur(10px);
                max-height: calc(100vh - 140px);
                overflow-y: auto;
            }

            .map-sidebar h3 {
                margin: 0 0 15px 0;
                font-size: 1.1em;
                color: #64b5f6;
            }

            .node-info, .bias-list, .problem-solver {
                margin-bottom: 25px;
                padding-bottom: 20px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }

            .problem-solver {
                border-bottom: none;
            }

            #node-details {
                color: #94a3b8;
                line-height: 1.6;
            }

            #bias-details {
                max-height: 200px;
                overflow-y: auto;
            }

            .bias-item {
                padding: 5px 10px;
                margin: 5px 0;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 8px;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .bias-item:hover {
                background: rgba(255, 255, 255, 0.1);
                transform: translateX(5px);
            }

            .bias-item.active {
                background: rgba(102, 126, 234, 0.3);
                border: 1px solid #667eea;
            }

            #simulate-btn {
                width: 100%;
                padding: 10px;
                background: linear-gradient(135deg, #10b981, #06b6d4);
                border: none;
                border-radius: 10px;
                color: white;
                font-weight: bold;
                cursor: pointer;
                transition: transform 0.3s ease;
            }

            #simulate-btn:hover {
                transform: translateY(-2px);
            }

            #simulation-result {
                margin-top: 15px;
                padding: 10px;
                background: rgba(255, 255, 255, 0.05);
                border-radius: 8px;
                min-height: 50px;
                color: #94a3b8;
            }

            /* SVG 스타일 */
            .thinking-node {
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .thinking-node:hover {
                transform: scale(1.1);
            }

            .thinking-node.active {
                filter: drop-shadow(0 0 20px rgba(102, 126, 234, 0.8));
            }

            .node-connection {
                stroke: rgba(255, 255, 255, 0.2);
                stroke-width: 1;
                fill: none;
            }

            .node-connection.active {
                stroke: #667eea;
                stroke-width: 2;
                animation: pulse-line 2s infinite;
            }

            @keyframes pulse-line {
                0%, 100% { opacity: 0.3; }
                50% { opacity: 1; }
            }

            .bias-bubble {
                opacity: 0;
                transition: opacity 0.3s ease;
                pointer-events: none;
            }

            .bias-bubble.visible {
                opacity: 1;
                pointer-events: all;
            }

            /* 애니메이션 */
            @keyframes float {
                0%, 100% { transform: translateY(0); }
                50% { transform: translateY(-10px); }
            }

            @keyframes rotate {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }

            .floating {
                animation: float 3s ease-in-out infinite;
            }

            .rotating {
                animation: rotate 20s linear infinite;
            }
        `;
        document.head.appendChild(styles);
    }

    /**
     * ✨ 별자리 뷰 렌더링
     */
    renderConstellationView() {
        const canvas = document.getElementById('map-canvas');
        canvas.innerHTML = '';

        // SVG 생성
        this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        this.svg.setAttribute('width', '100%');
        this.svg.setAttribute('height', '100%');
        this.svg.setAttribute('viewBox', '0 0 1200 800');

        // 배경 별들
        this.createStarField();

        // 9개 노드를 원형으로 배치
        const centerX = 600;
        const centerY = 400;
        const radius = 250;
        const nodes = Object.values(this.framework.nodes);

        // 연결선 그리기
        const connections = this.svg.createElementNS('http://www.w3.org/2000/svg', 'g');
        connections.id = 'connections';

        nodes.forEach((node, i) => {
            const angle1 = (i * 2 * Math.PI) / nodes.length - Math.PI / 2;
            const x1 = centerX + radius * Math.cos(angle1);
            const y1 = centerY + radius * Math.sin(angle1);

            // 다음 노드와 연결
            const nextIndex = (i + 1) % nodes.length;
            const angle2 = (nextIndex * 2 * Math.PI) / nodes.length - Math.PI / 2;
            const x2 = centerX + radius * Math.cos(angle2);
            const y2 = centerY + radius * Math.sin(angle2);

            const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            line.setAttribute('x1', x1);
            line.setAttribute('y1', y1);
            line.setAttribute('x2', x2);
            line.setAttribute('y2', y2);
            line.setAttribute('class', 'node-connection');
            line.setAttribute('data-from', node.id);
            line.setAttribute('data-to', nodes[nextIndex].id);
            connections.appendChild(line);

            // 중앙과 연결
            const centerLine = document.createElementNS('http://www.w3.org/2000/svg', 'line');
            centerLine.setAttribute('x1', x1);
            centerLine.setAttribute('y1', y1);
            centerLine.setAttribute('x2', centerX);
            centerLine.setAttribute('y2', centerY);
            centerLine.setAttribute('class', 'node-connection');
            centerLine.style.opacity = '0.1';
            connections.appendChild(centerLine);
        });

        this.svg.appendChild(connections);

        // 노드 그리기
        const nodesGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        nodesGroup.id = 'nodes';

        nodes.forEach((node, i) => {
            const angle = (i * 2 * Math.PI) / nodes.length - Math.PI / 2;
            const x = centerX + radius * Math.cos(angle);
            const y = centerY + radius * Math.sin(angle);

            const g = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            g.setAttribute('class', 'thinking-node');
            g.setAttribute('data-node-id', node.id);
            g.setAttribute('transform', `translate(${x}, ${y})`);

            // 외부 원 (발광 효과)
            const glow = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            glow.setAttribute('r', '35');
            glow.setAttribute('fill', node.color);
            glow.setAttribute('opacity', '0.3');
            g.appendChild(glow);

            // 메인 원
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('r', '25');
            circle.setAttribute('fill', node.color);
            g.appendChild(circle);

            // 심볼
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('dominant-baseline', 'middle');
            text.setAttribute('font-size', '20');
            text.textContent = node.symbol;
            g.appendChild(text);

            // 이름
            const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            label.setAttribute('text-anchor', 'middle');
            label.setAttribute('y', '45');
            label.setAttribute('font-size', '12');
            label.setAttribute('fill', 'white');
            label.textContent = node.koreanName;
            g.appendChild(label);

            // 연결된 편향 수
            const biasCount = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            biasCount.setAttribute('cx', '20');
            biasCount.setAttribute('cy', '-20');
            biasCount.setAttribute('r', '10');
            biasCount.setAttribute('fill', '#ef4444');
            g.appendChild(biasCount);

            const biasCountText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            biasCountText.setAttribute('x', '20');
            biasCountText.setAttribute('y', '-20');
            biasCountText.setAttribute('text-anchor', 'middle');
            biasCountText.setAttribute('dominant-baseline', 'middle');
            biasCountText.setAttribute('font-size', '10');
            biasCountText.setAttribute('fill', 'white');
            biasCountText.textContent = this.framework.biasMapping[node.id]?.length || 0;
            g.appendChild(biasCountText);

            nodesGroup.appendChild(g);
        });

        // 중앙 마스터리 노드
        const centerNode = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        centerNode.setAttribute('class', 'thinking-node');
        centerNode.setAttribute('data-node-id', 'mastery-center');
        centerNode.setAttribute('transform', `translate(${centerX}, ${centerY})`);

        const centerGlow = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        centerGlow.setAttribute('r', '40');
        centerGlow.setAttribute('fill', 'url(#radialGradient)');
        centerGlow.setAttribute('opacity', '0.5');
        centerNode.appendChild(centerGlow);

        const centerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        centerCircle.setAttribute('r', '30');
        centerCircle.setAttribute('fill', '#dc2626');
        centerNode.appendChild(centerCircle);

        const centerText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        centerText.setAttribute('text-anchor', 'middle');
        centerText.setAttribute('dominant-baseline', 'middle');
        centerText.setAttribute('font-size', '24');
        centerText.textContent = '👑';
        centerNode.appendChild(centerText);

        nodesGroup.appendChild(centerNode);
        this.svg.appendChild(nodesGroup);

        // 그라디언트 정의
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'radialGradient');
        gradient.id = 'radialGradient';

        const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop1.setAttribute('offset', '0%');
        stop1.setAttribute('stop-color', '#dc2626');
        stop1.setAttribute('stop-opacity', '1');
        gradient.appendChild(stop1);

        const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
        stop2.setAttribute('offset', '100%');
        stop2.setAttribute('stop-color', '#dc2626');
        stop2.setAttribute('stop-opacity', '0');
        gradient.appendChild(stop2);

        defs.appendChild(gradient);
        this.svg.appendChild(defs);

        canvas.appendChild(this.svg);
    }

    /**
     * 🌟 별 필드 생성
     */
    createStarField() {
        const stars = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        stars.id = 'starfield';

        for (let i = 0; i < 100; i++) {
            const star = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            star.setAttribute('cx', Math.random() * 1200);
            star.setAttribute('cy', Math.random() * 800);
            star.setAttribute('r', Math.random() * 2);
            star.setAttribute('fill', 'white');
            star.setAttribute('opacity', Math.random() * 0.8);
            stars.appendChild(star);
        }

        this.svg.appendChild(stars);
    }

    /**
     * 🎮 상호작용 설정
     */
    setupInteractions() {
        // 노드 클릭
        document.querySelectorAll('.thinking-node').forEach(node => {
            node.addEventListener('click', (e) => {
                const nodeId = node.getAttribute('data-node-id');
                this.selectNode(nodeId);
            });
        });

        // 뷰 전환
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = btn.getAttribute('data-view');
                this.switchView(view);
            });
        });

        // 시뮬레이션 버튼
        document.getElementById('simulate-btn')?.addEventListener('click', () => {
            this.runSimulation();
        });

        // ESC 키로 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.close();
            }
        });
    }

    /**
     * 🎯 노드 선택
     */
    selectNode(nodeId) {
        // 이전 선택 해제
        document.querySelectorAll('.thinking-node').forEach(n => {
            n.classList.remove('active');
        });

        // 새 노드 선택
        const nodeElement = document.querySelector(`[data-node-id="${nodeId}"]`);
        if (nodeElement) {
            nodeElement.classList.add('active');
        }

        // 노드 정보 표시
        const node = this.framework.nodes[nodeId] || 
                    (nodeId === 'mastery-center' ? this.framework.nodes.mastery : null);
                    
        if (node) {
            this.selectedNode = node;
            this.showNodeDetails(node);
            this.showConnectedBiases(nodeId === 'mastery-center' ? 'mastery' : nodeId);
            this.highlightConnections(nodeId);
        }
    }

    /**
     * 📋 노드 상세 정보 표시
     */
    showNodeDetails(node) {
        const details = document.getElementById('node-details');
        details.innerHTML = `
            <div style="color: ${node.color}; font-size: 2em; margin-bottom: 10px;">
                ${node.symbol} ${node.koreanName}
            </div>
            <p style="margin-bottom: 10px;">${node.description}</p>
            <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                <strong>사고 패턴:</strong><br>
                ${node.thinkingPattern}
            </div>
            <div style="background: rgba(255,255,255,0.05); padding: 10px; border-radius: 8px;">
                <strong>핵심 질문:</strong><br>
                ${node.keyQuestions.map(q => `• ${q}`).join('<br>')}
            </div>
        `;
    }

    /**
     * 🔗 연결된 편향 표시
     */
    showConnectedBiases(nodeId) {
        const biases = this.framework.biasMapping[nodeId] || [];
        const details = document.getElementById('bias-details');
        
        if (biases.length === 0) {
            details.innerHTML = '<div style="color: #94a3b8;">연결된 편향이 없습니다.</div>';
            return;
        }

        details.innerHTML = biases.map(bias => `
            <div class="bias-item ${this.activeBiases.has(bias) ? 'active' : ''}" 
                 data-bias="${bias}">
                ${this.getBiasKoreanName(bias)}
            </div>
        `).join('');

        // 편향 클릭 이벤트
        details.querySelectorAll('.bias-item').forEach(item => {
            item.addEventListener('click', () => {
                const bias = item.getAttribute('data-bias');
                this.toggleBias(bias);
            });
        });
    }

    /**
     * 🔦 연결 강조
     */
    highlightConnections(nodeId) {
        // 모든 연결 초기화
        document.querySelectorAll('.node-connection').forEach(line => {
            line.classList.remove('active');
        });

        // 선택된 노드의 연결 강조
        document.querySelectorAll('.node-connection').forEach(line => {
            const from = line.getAttribute('data-from');
            const to = line.getAttribute('data-to');
            if (from === nodeId || to === nodeId) {
                line.classList.add('active');
            }
        });
    }

    /**
     * 🎲 시뮬레이션 실행
     */
    runSimulation() {
        const problem = {
            type: 'mixed',
            description: '복잡한 수학 문제',
            requiresVisualization: true,
            requiresStrategy: true
        };

        const solution = this.framework.solveProblem(problem);
        
        const resultDiv = document.getElementById('simulation-result');
        resultDiv.innerHTML = `
            <div style="margin-bottom: 10px;">
                <strong>🔍 감지된 편향:</strong><br>
                ${solution.biasesDetected.slice(0, 3).map(b => 
                    `• ${this.getBiasKoreanName(b.name)} (${Math.round(b.probability * 100)}%)`
                ).join('<br>')}
            </div>
            <div style="margin-bottom: 10px;">
                <strong>⚡ 활성화된 노드:</strong><br>
                ${solution.nodesActivated.map(n => 
                    `• ${n.node.symbol} ${n.node.koreanName}`
                ).join('<br>')}
            </div>
            <div style="margin-bottom: 10px;">
                <strong>✅ 극복된 편향:</strong><br>
                ${solution.biasesOvercome.filter(b => b.success).map(b => 
                    `• ${this.getBiasKoreanName(b.bias)}`
                ).join('<br>')}
            </div>
            <div>
                <strong>🎯 해결 품질:</strong> 
                ${Math.round(solution.finalSolution.solutionQuality * 100)}%
            </div>
        `;

        // 시각적 피드백
        this.animateSolution(solution);
    }

    /**
     * 🎬 솔루션 애니메이션
     */
    animateSolution(solution) {
        // 활성화된 노드 애니메이션
        solution.nodesActivated.forEach((node, i) => {
            setTimeout(() => {
                const nodeElement = document.querySelector(`[data-node-id="${node.id}"]`);
                if (nodeElement) {
                    nodeElement.classList.add('active');
                    setTimeout(() => nodeElement.classList.remove('active'), 1000);
                }
            }, i * 500);
        });
    }

    /**
     * 🔄 뷰 전환
     */
    switchView(view) {
        this.currentView = view;
        
        // 버튼 상태 업데이트
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('data-view') === view) {
                btn.classList.add('active');
            }
        });

        // 뷰 렌더링
        switch(view) {
            case 'constellation':
                this.renderConstellationView();
                break;
            case 'network':
                this.renderNetworkView();
                break;
            case 'journey':
                this.renderJourneyView();
                break;
        }

        this.setupInteractions();
    }

    /**
     * 🕸️ 네트워크 뷰 렌더링
     */
    renderNetworkView() {
        // 편향과 노드의 복잡한 네트워크 시각화
        // 구현 예정
    }

    /**
     * 🚀 여정 뷰 렌더링
     */
    renderJourneyView() {
        // 문제 해결 여정을 단계별로 시각화
        // 구현 예정
    }

    /**
     * 🔄 편향 토글
     */
    toggleBias(bias) {
        if (this.activeBiases.has(bias)) {
            this.activeBiases.delete(bias);
        } else {
            this.activeBiases.add(bias);
        }
        
        // UI 업데이트
        this.showConnectedBiases(this.selectedNode?.id || 'mastery');
    }

    /**
     * 🏷️ 편향 한글 이름
     */
    getBiasKoreanName(bias) {
        const names = {
            'ConfirmationBias': '확증 편향',
            'AnchoringBias': '앵커링 편향',
            'AvailabilityHeuristic': '가용성 휴리스틱',
            'DunningKrugerEffect': '더닝-크루거 효과',
            'Overconfidence': '과신 편향',
            'HindsightBias': '사후 과잉 확신',
            'SelfServingBias': '자기 이익 편향',
            'StatusQuoBias': '현상 유지 편향',
            'SunkCostFallacy': '매몰 비용 오류',
            'LossAversion': '손실 회피',
            'FramingEffect': '프레이밍 효과',
            'BandwagonEffect': '편승 효과',
            'HaloEffect': '후광 효과',
            'GamblerssFallacy': '도박사의 오류',
            'ClusteringIllusion': '군집 착각',
            'RepresentativenessHeuristic': '대표성 휴리스틱',
            'ProjectionBias': '투사 편향',
            'FundamentalAttributionError': '기본 귀인 오류',
            'InGroupBias': '내집단 편향',
            'OutgroupHomogeneity': '외집단 동질성',
            'AuthorityBias': '권위자 편향',
            'PlaceboEffect': '플라시보 효과',
            'FalseConsensus': '거짓 합의 효과',
            'CurseOfKnowledge': '지식의 저주',
            'SpotlightEffect': '스포트라이트 효과',
            'Apophenia': '무의미한 패턴 찾기',
            'RecencyEffect': '최신성 효과',
            'PrimacyEffect': '초두 효과',
            'StereotypingBias': '고정관념 편향',
            'PlanningFallacy': '계획 오류',
            'OptimismBias': '낙관주의 편향',
            'PessimismBias': '비관주의 편향',
            'ActorObserverBias': '행위자-관찰자 편향',
            'BlindSpotBias': '맹점 편향',
            'ChoiceSupportiveBias': '선택 지지 편향',
            'IllusionOfControl': '통제 착각',
            'IllusoryCorrelation': '환상적 상관',
            'ImpactBias': '영향 편향',
            'NotInventedHere': '여기서 발명되지 않음',
            'OutcomeVariability': '결과 변동성',
            'ParkinsonsLawOfTriviality': '파킨슨의 사소함 법칙',
            'Reactance': '반발 심리',
            'RhymeAsReasonEffect': '운율 이유 효과',
            'RiskCompensation': '위험 보상',
            'SystemJustification': '체제 정당화',
            'TemporalDiscounting': '시간 할인',
            'ThirdPersonEffect': '제3자 효과',
            'ZeigarnikEffect': '자이가르닉 효과',
            'ZeroRiskBias': '제로 리스크 편향',
            'AmbiguityEffect': '모호성 효과',
            'BaseRateNeglect': '기저율 무시',
            'ConjunctionFallacy': '결합 오류',
            'ConservatismBias': '보수주의 편향',
            'FunctionalFixedness': '기능 고착',
            'LawOfSmallNumbers': '작은 수의 법칙',
            'NeglectOfProbability': '확률 무시',
            'NormalizationOfDeviance': '일탈의 정상화',
            'ObserverExpectancyEffect': '관찰자 기대 효과',
            'SelectivePerception': '선택적 지각',
            'SemmelweisReflex': '제멜바이스 반사',
            'UnitBias': '단위 편향',
            'ZeroSumBias': '제로섬 편향'
        };
        
        return names[bias] || bias;
    }

    /**
     * 🎬 애니메이션 시작
     */
    startAnimation() {
        // 노드 부유 애니메이션
        document.querySelectorAll('.thinking-node').forEach((node, i) => {
            setTimeout(() => {
                node.classList.add('floating');
            }, i * 100);
        });
    }

    /**
     * 📂 열기
     */
    open() {
        if (!this.container) {
            this.createContainer();
            this.renderConstellationView();
            this.setupInteractions();
        }
        this.container.style.display = 'flex';
        setTimeout(() => {
            this.container.classList.add('active');
        }, 10);
    }

    /**
     * ❌ 닫기
     */
    close() {
        if (this.container) {
            this.container.classList.remove('active');
            setTimeout(() => {
                this.container.style.display = 'none';
            }, 300);
        }
    }
}

// 전역 인스턴스 생성
window.updatedNavigationMap = new UpdatedNavigationMap();