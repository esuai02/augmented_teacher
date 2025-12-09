/**
 * 🗺️ 편향 네비게이션 맵 시각화 시스템
 * 60개 편향을 5대 클러스터로 분류하여 우주적 지도로 표현
 */

class BiasNavigationMap {
    constructor() {
        this.mapContainer = null;
        this.svg = null;
        this.width = 800;
        this.height = 700;
        this.userProgress = this.loadUserProgress();
        this.selectedCluster = null;
        this.selectedBias = null;
        this.animationActive = false;
        
        this.init();
    }

    /**
     * 🚀 지도 시스템 초기화
     */
    init() {
        this.createMapInterface();
        this.bindEventListeners();
        this.loadMapStyles();
    }

    /**
     * 🎨 네비게이션 맵 인터페이스 생성
     */
    createMapInterface() {
        const mapHTML = `
            <div id="bias-navigation-map" class="navigation-map-overlay">
                <div class="map-container">
                    <!-- 헤더 -->
                    <div class="map-header">
                        <h2>🌌 편향 우주 지도</h2>
                        <div class="map-stats">
                            <span class="progress-indicator">
                                정복률: <span id="mapProgressRate">0%</span> 
                                (<span id="mapProgressCount">0</span>/60)
                            </span>
                        </div>
                        <button class="close-map" onclick="biasNavigationMap.close()">✕</button>
                    </div>

                    <!-- 컨트롤 패널 -->
                    <div class="map-controls">
                        <div class="view-controls">
                            <button class="control-btn" onclick="biasNavigationMap.resetView()">
                                🏠 전체 보기
                            </button>
                            <button class="control-btn" onclick="biasNavigationMap.showConquestPath()">
                                🛤️ 정복 경로
                            </button>
                        </div>
                        
                        <div class="filter-controls">
                            <label>표시:</label>
                            <select id="mapFilter" onchange="biasNavigationMap.applyFilter()">
                                <option value="all">전체</option>
                                <option value="unlocked">잠금해제</option>
                                <option value="locked">잠금</option>
                                <option value="recommended">추천</option>
                            </select>
                        </div>
                    </div>

                    <!-- 우주 지도 SVG -->
                    <div class="map-viewport">
                        <svg id="cosmic-map" viewBox="0 0 800 700">
                            <!-- 배경 별자리 -->
                            <defs>
                                <radialGradient id="spaceGradient" cx="50%" cy="50%" r="50%">
                                    <stop offset="0%" style="stop-color:#0f172a;stop-opacity:1" />
                                    <stop offset="100%" style="stop-color:#020617;stop-opacity:1" />
                                </radialGradient>
                                
                                <filter id="glow">
                                    <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                                    <feMerge> 
                                        <feMergeNode in="coloredBlur"/>
                                        <feMergeNode in="SourceGraphic"/>
                                    </feMerge>
                                </filter>
                            </defs>
                            
                            <!-- 우주 배경 -->
                            <rect width="100%" height="100%" fill="url(#spaceGradient)" />
                            
                            <!-- 배경 별들 -->
                            <g id="background-stars"></g>
                            
                            <!-- 연결선 -->
                            <g id="connection-lines"></g>
                            
                            <!-- 클러스터 노드들 -->
                            <g id="cluster-nodes"></g>
                            
                            <!-- 편향 노드들 -->
                            <g id="bias-nodes"></g>
                            
                            <!-- 정복 경로 -->
                            <g id="conquest-path"></g>
                        </svg>
                    </div>

                    <!-- 상세 정보 패널 -->
                    <div class="info-panel" id="infoPanel">
                        <div class="panel-content">
                            <div class="panel-header">
                                <h3 id="panelTitle">클러스터를 선택해주세요</h3>
                                <button class="panel-close" onclick="biasNavigationMap.closeInfoPanel()">✕</button>
                            </div>
                            <div class="panel-body" id="panelBody">
                                <p>편향 클러스터나 개별 편향을 클릭하면 상세 정보가 표시됩니다.</p>
                            </div>
                        </div>
                    </div>

                    <!-- 범례 -->
                    <div class="map-legend">
                        <div class="legend-title">🧭 범례</div>
                        <div class="legend-items">
                            <div class="legend-item">
                                <span class="legend-dot cluster-core"></span>
                                <span>핵심 클러스터</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot bias-unlocked"></span>
                                <span>잠금해제 편향</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot bias-locked"></span>
                                <span>잠금 편향</span>
                            </div>
                            <div class="legend-item">
                                <span class="legend-dot bias-recommended"></span>
                                <span>추천 편향</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 페이지에 추가
        if (!document.getElementById('bias-navigation-map')) {
            document.body.insertAdjacentHTML('beforeend', mapHTML);
        }
    }

    /**
     * 🌌 지도 열기
     */
    open() {
        const mapElement = document.getElementById('bias-navigation-map');
        if (mapElement) {
            mapElement.style.display = 'flex';
            
            // SVG 참조 설정
            this.svg = document.getElementById('cosmic-map');
            this.mapContainer = document.querySelector('.map-container');
            
            // 지도 렌더링
            this.renderCosmicMap();
            this.updateProgress();
            
            // 애니메이션
            setTimeout(() => {
                mapElement.classList.add('show');
            }, 10);
        }
    }

    /**
     * 🚪 지도 닫기
     */
    close() {
        const mapElement = document.getElementById('bias-navigation-map');
        if (mapElement) {
            mapElement.classList.remove('show');
            setTimeout(() => {
                mapElement.style.display = 'none';
                this.resetView();
            }, 300);
        }
    }

    /**
     * 🎨 우주 지도 렌더링
     */
    renderCosmicMap() {
        if (!this.svg || !window.biasClusterData) return;

        // 배경 별들 생성
        this.createBackgroundStars();
        
        // 클러스터 노드들 생성
        this.renderClusterNodes();
        
        // 편향 노드들 생성 (처음에는 숨김)
        this.renderBiasNodes();
        
        // 연결선 생성
        this.renderConnectionLines();
    }

    /**
     * ⭐ 배경 별들 생성
     */
    createBackgroundStars() {
        const starsGroup = document.getElementById('background-stars');
        starsGroup.innerHTML = '';

        // 랜덤 배경 별들 생성
        for (let i = 0; i < 100; i++) {
            const star = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            star.setAttribute('cx', Math.random() * this.width);
            star.setAttribute('cy', Math.random() * this.height);
            star.setAttribute('r', Math.random() * 1.5 + 0.5);
            star.setAttribute('fill', '#ffffff');
            star.setAttribute('opacity', Math.random() * 0.3 + 0.1);
            
            // 반짝임 애니메이션
            const animate = document.createElementNS('http://www.w3.org/2000/svg', 'animate');
            animate.setAttribute('attributeName', 'opacity');
            animate.setAttribute('values', `${star.getAttribute('opacity')};0.1;${star.getAttribute('opacity')}`);
            animate.setAttribute('dur', `${Math.random() * 3 + 2}s`);
            animate.setAttribute('repeatCount', 'indefinite');
            star.appendChild(animate);
            
            starsGroup.appendChild(star);
        }
    }

    /**
     * 🌍 클러스터 노드들 렌더링
     */
    renderClusterNodes() {
        const clustersGroup = document.getElementById('cluster-nodes');
        clustersGroup.innerHTML = '';

        const clusters = window.biasClusterData.clusters;
        
        Object.values(clusters).forEach(cluster => {
            const clusterGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            clusterGroup.setAttribute('class', 'cluster-group');
            clusterGroup.setAttribute('data-cluster', cluster.id);

            // 클러스터 원
            const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', cluster.position.x);
            circle.setAttribute('cy', cluster.position.y);
            circle.setAttribute('r', 40);
            circle.setAttribute('fill', cluster.color);
            circle.setAttribute('stroke', '#ffffff');
            circle.setAttribute('stroke-width', 2);
            circle.setAttribute('opacity', 0.8);
            circle.setAttribute('filter', 'url(#glow)');
            circle.setAttribute('class', 'cluster-node');

            // 클러스터 이름
            const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            text.setAttribute('x', cluster.position.x);
            text.setAttribute('y', cluster.position.y + 5);
            text.setAttribute('text-anchor', 'middle');
            text.setAttribute('fill', '#ffffff');
            text.setAttribute('font-size', '12');
            text.setAttribute('font-weight', 'bold');
            text.textContent = cluster.name.replace('🧠 ', '').replace('💭 ', '').replace('📚 ', '').replace('😰 ', '').replace('🤝 ', '');

            // 진행도 링
            const progress = this.getClusterProgress(cluster.id);
            const progressRing = this.createProgressRing(
                cluster.position.x, 
                cluster.position.y, 
                progress.percentage / 100
            );

            // 클릭 이벤트
            clusterGroup.addEventListener('click', () => {
                this.selectCluster(cluster.id);
            });

            clusterGroup.appendChild(circle);
            clusterGroup.appendChild(progressRing);
            clusterGroup.appendChild(text);
            clustersGroup.appendChild(clusterGroup);
        });
    }

    /**
     * 🎯 편향 노드들 렌더링
     */
    renderBiasNodes() {
        const biasGroup = document.getElementById('bias-nodes');
        biasGroup.innerHTML = '';

        const clusters = window.biasClusterData.clusters;
        
        Object.values(clusters).forEach(cluster => {
            cluster.biases.forEach((biasName, index) => {
                const biasData = window.biasClusterData.biasMetadata[biasName];
                if (!biasData) return;

                // 위성 궤도 위치 계산
                const angle = (index / cluster.biases.length) * 2 * Math.PI;
                const radius = 80 + Math.random() * 20; // 80-100px 거리
                const x = cluster.position.x + Math.cos(angle) * radius;
                const y = cluster.position.y + Math.sin(angle) * radius;

                const biasNodeGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                biasNodeGroup.setAttribute('class', 'bias-node-group');
                biasNodeGroup.setAttribute('data-bias', biasName);
                biasNodeGroup.setAttribute('data-cluster', cluster.id);
                biasNodeGroup.style.display = 'none'; // 처음에는 숨김

                // 편향 노드
                const isUnlocked = this.userProgress.includes(biasData.id);
                const isRecommended = this.isRecommendedBias(biasName);
                
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', x);
                circle.setAttribute('cy', y);
                circle.setAttribute('r', 8);
                circle.setAttribute('fill', this.getBiasColor(biasData, isUnlocked, isRecommended));
                circle.setAttribute('stroke', '#ffffff');
                circle.setAttribute('stroke-width', 1);
                circle.setAttribute('class', `bias-node ${isUnlocked ? 'unlocked' : 'locked'} ${isRecommended ? 'recommended' : ''}`);

                // 편향 이름 (잠금해제된 경우만)
                if (isUnlocked) {
                    const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                    text.setAttribute('x', x);
                    text.setAttribute('y', y - 15);
                    text.setAttribute('text-anchor', 'middle');
                    text.setAttribute('fill', '#ffffff');
                    text.setAttribute('font-size', '8');
                    text.textContent = biasName;
                    biasNodeGroup.appendChild(text);
                }

                // 클릭 이벤트
                biasNodeGroup.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.selectBias(biasName);
                });

                biasNodeGroup.appendChild(circle);
                biasGroup.appendChild(biasNodeGroup);
            });
        });
    }

    /**
     * 🔗 연결선 렌더링
     */
    renderConnectionLines() {
        const linesGroup = document.getElementById('connection-lines');
        linesGroup.innerHTML = '';

        if (!window.biasClusterData.relationships) return;

        window.biasClusterData.relationships.forEach(rel => {
            const fromBias = this.findBiasPosition(rel.from);
            const toBias = this.findBiasPosition(rel.to);
            
            if (fromBias && toBias) {
                const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                line.setAttribute('x1', fromBias.x);
                line.setAttribute('y1', fromBias.y);
                line.setAttribute('x2', toBias.x);
                line.setAttribute('y2', toBias.y);
                line.setAttribute('stroke', this.getRelationshipColor(rel.type));
                line.setAttribute('stroke-width', rel.strength * 2);
                line.setAttribute('opacity', 0.3);
                line.setAttribute('class', 'relationship-line');
                line.style.display = 'none'; // 처음에는 숨김
                
                linesGroup.appendChild(line);
            }
        });
    }

    /**
     * 🎯 클러스터 선택
     */
    selectCluster(clusterId) {
        this.selectedCluster = clusterId;
        this.selectedBias = null;

        // 모든 편향 노드 숨기기
        document.querySelectorAll('.bias-node-group').forEach(node => {
            node.style.display = 'none';
        });

        // 선택된 클러스터의 편향들 표시
        document.querySelectorAll(`[data-cluster="${clusterId}"]`).forEach(node => {
            if (node.classList.contains('bias-node-group')) {
                node.style.display = 'block';
                // 등장 애니메이션
                node.style.animation = 'fadeInScale 0.5s ease-out';
            }
        });

        // 클러스터 강조
        this.highlightCluster(clusterId);

        // 정보 패널 업데이트
        this.showClusterInfo(clusterId);
    }

    /**
     * 🎯 편향 선택
     */
    selectBias(biasName) {
        this.selectedBias = biasName;

        // 편향 강조
        this.highlightBias(biasName);

        // 관련 편향들과의 연결선 표시
        this.showRelatedBiases(biasName);

        // 정보 패널 업데이트
        this.showBiasInfo(biasName);
    }

    /**
     * 💡 클러스터 정보 표시
     */
    showClusterInfo(clusterId) {
        const cluster = window.biasClusterData.clusters[clusterId];
        const progress = this.getClusterProgress(clusterId);
        
        const panel = document.getElementById('infoPanel');
        const title = document.getElementById('panelTitle');
        const body = document.getElementById('panelBody');

        title.textContent = cluster.name;
        body.innerHTML = `
            <div class="cluster-info">
                <p class="cluster-description">${cluster.description}</p>
                
                <div class="cluster-stats">
                    <div class="stat-item">
                        <span class="stat-label">진행도:</span>
                        <span class="stat-value">${progress.conquered}/${progress.total} (${progress.percentage}%)</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">핵심 편향:</span>
                        <span class="stat-value">${cluster.coreNode}</span>
                    </div>
                </div>

                <div class="bias-list">
                    <h4>포함된 편향들:</h4>
                    <div class="bias-grid">
                        ${cluster.biases.map(biasName => {
                            const biasData = window.biasClusterData.biasMetadata[biasName];
                            const isUnlocked = biasData && this.userProgress.includes(biasData.id);
                            return `
                                <div class="bias-item ${isUnlocked ? 'unlocked' : 'locked'}" 
                                     onclick="biasNavigationMap.selectBias('${biasName}')">
                                    <span class="bias-status">${isUnlocked ? '✅' : '🔒'}</span>
                                    <span class="bias-name">${biasName}</span>
                                </div>
                            `;
                        }).join('')}
                    </div>
                </div>

                <div class="cluster-actions">
                    <button class="action-btn primary" onclick="biasNavigationMap.showRecommendedPath('${clusterId}')">
                        🎯 추천 경로 보기
                    </button>
                    <button class="action-btn secondary" onclick="biasNavigationMap.resetView()">
                        🏠 전체 보기
                    </button>
                </div>
            </div>
        `;

        panel.style.display = 'block';
        panel.classList.add('show');
    }

    /**
     * 💡 편향 정보 표시
     */
    showBiasInfo(biasName) {
        const biasData = window.biasClusterData.biasMetadata[biasName];
        const isUnlocked = biasData && this.userProgress.includes(biasData.id);
        const relatedBiases = window.biasClusterData.getRelatedBiases(biasName);
        
        const panel = document.getElementById('infoPanel');
        const title = document.getElementById('panelTitle');
        const body = document.getElementById('panelBody');

        title.textContent = biasName;
        body.innerHTML = `
            <div class="bias-info">
                <div class="bias-status-badge ${isUnlocked ? 'unlocked' : 'locked'}">
                    ${isUnlocked ? '✅ 잠금해제됨' : '🔒 잠금됨'}
                </div>

                ${biasData ? `
                    <div class="bias-meta">
                        <div class="meta-item">
                            <span class="meta-label">난이도:</span>
                            <span class="meta-value difficulty-${biasData.difficulty}">${this.getDifficultyText(biasData.difficulty)}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">영향도:</span>
                            <span class="meta-value impact-${biasData.impact}">${this.getImpactText(biasData.impact)}</span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">예상 학습 시간:</span>
                            <span class="meta-value">${biasData.timeToMaster}</span>
                        </div>
                    </div>

                    ${biasData.realWorldExamples ? `
                        <div class="examples">
                            <h4>실생활 예시:</h4>
                            <ul>
                                ${biasData.realWorldExamples.map(example => `<li>${example}</li>`).join('')}
                            </ul>
                        </div>
                    ` : ''}
                ` : ''}

                ${relatedBiases.length > 0 ? `
                    <div class="related-biases">
                        <h4>연관된 편향들:</h4>
                        <div class="related-list">
                            ${relatedBiases.slice(0, 5).map(rel => `
                                <div class="related-item" onclick="biasNavigationMap.selectBias('${rel.bias}')">
                                    <span class="relation-type">${this.getRelationshipText(rel.relationship)}</span>
                                    <span class="related-name">${rel.bias}</span>
                                    <span class="strength-indicator" style="width: ${rel.strength * 100}%"></span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                ` : ''}

                <div class="bias-actions">
                    ${isUnlocked ? `
                        <button class="action-btn primary" onclick="biasCardSession.startSession('${biasName}')">
                            🎓 학습 세션 시작
                        </button>
                        <button class="action-btn secondary" onclick="biasCardSystem.showLibraryCard('${biasData.id}')">
                            📖 카드 자세히 보기
                        </button>
                    ` : `
                        <div class="unlock-hint">
                            <p>이 편향을 극복하여 카드를 잠금해제하세요!</p>
                            ${biasData && biasData.prerequisites.length > 0 ? `
                                <p class="prerequisites">
                                    전제조건: ${biasData.prerequisites.join(', ')}
                                </p>
                            ` : ''}
                        </div>
                    `}
                </div>
            </div>
        `;

        panel.style.display = 'block';
        panel.classList.add('show');
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    getClusterProgress(clusterId) {
        if (!window.biasClusterData) {
            return { total: 0, conquered: 0, percentage: 0 };
        }
        return window.biasClusterData.getClusterProgress(clusterId, this.userProgress);
    }

    createProgressRing(cx, cy, progress) {
        const radius = 42;
        const circumference = 2 * Math.PI * radius;
        const progressRing = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        
        progressRing.setAttribute('cx', cx);
        progressRing.setAttribute('cy', cy);
        progressRing.setAttribute('r', radius);
        progressRing.setAttribute('fill', 'none');
        progressRing.setAttribute('stroke', '#ffd700');
        progressRing.setAttribute('stroke-width', 3);
        progressRing.setAttribute('stroke-dasharray', circumference);
        progressRing.setAttribute('stroke-dashoffset', circumference * (1 - progress));
        progressRing.setAttribute('opacity', 0.8);
        
        return progressRing;
    }

    getBiasColor(biasData, isUnlocked, isRecommended) {
        if (isRecommended) return '#ffd700'; // 금색 - 추천
        if (isUnlocked) return '#4ade80'; // 녹색 - 잠금해제
        return '#64748b'; // 회색 - 잠금
    }

    getRelationshipColor(type) {
        const colors = {
            causal: '#ff6b6b',
            reinforcing: '#4ecdc4',
            related: '#45b7d1',
            compensatory: '#96ceb4',
            meta: '#feca57'
        };
        return colors[type] || '#ffffff';
    }

    findBiasPosition(biasName) {
        const biasNode = document.querySelector(`[data-bias="${biasName}"] circle`);
        if (biasNode) {
            return {
                x: parseFloat(biasNode.getAttribute('cx')),
                y: parseFloat(biasNode.getAttribute('cy'))
            };
        }
        return null;
    }

    isRecommendedBias(biasName) {
        if (!window.biasClusterData) return false;
        const recommendations = window.biasClusterData.getNextTargets(this.userProgress, 5);
        return recommendations.some(rec => rec.bias === biasName);
    }

    highlightCluster(clusterId) {
        // 모든 클러스터 하이라이트 제거
        document.querySelectorAll('.cluster-node').forEach(node => {
            node.setAttribute('stroke-width', 2);
            node.setAttribute('opacity', 0.8);
        });

        // 선택된 클러스터 하이라이트
        const selectedCluster = document.querySelector(`[data-cluster="${clusterId}"] .cluster-node`);
        if (selectedCluster) {
            selectedCluster.setAttribute('stroke-width', 4);
            selectedCluster.setAttribute('opacity', 1);
        }
    }

    highlightBias(biasName) {
        // 모든 편향 하이라이트 제거
        document.querySelectorAll('.bias-node').forEach(node => {
            node.setAttribute('stroke-width', 1);
        });

        // 선택된 편향 하이라이트
        const selectedBias = document.querySelector(`[data-bias="${biasName}"] .bias-node`);
        if (selectedBias) {
            selectedBias.setAttribute('stroke-width', 3);
        }
    }

    showRelatedBiases(biasName) {
        // 모든 연결선 숨기기
        document.querySelectorAll('.relationship-line').forEach(line => {
            line.style.display = 'none';
        });

        // 관련된 연결선만 표시
        const relationships = window.biasClusterData.relationships || [];
        relationships.forEach(rel => {
            if (rel.from === biasName || rel.to === biasName) {
                const lines = document.querySelectorAll('.relationship-line');
                // 해당하는 연결선 찾아서 표시
                // (실제 구현에서는 더 정교한 매칭 필요)
                lines.forEach(line => {
                    line.style.display = 'block';
                    line.style.opacity = 0.7;
                });
            }
        });
    }

    resetView() {
        this.selectedCluster = null;
        this.selectedBias = null;

        // 모든 편향 노드 숨기기
        document.querySelectorAll('.bias-node-group').forEach(node => {
            node.style.display = 'none';
        });

        // 모든 연결선 숨기기
        document.querySelectorAll('.relationship-line').forEach(line => {
            line.style.display = 'none';
        });

        // 클러스터 하이라이트 제거
        document.querySelectorAll('.cluster-node').forEach(node => {
            node.setAttribute('stroke-width', 2);
            node.setAttribute('opacity', 0.8);
        });

        // 정보 패널 닫기
        this.closeInfoPanel();
    }

    closeInfoPanel() {
        const panel = document.getElementById('infoPanel');
        if (panel) {
            panel.classList.remove('show');
            setTimeout(() => {
                panel.style.display = 'none';
            }, 300);
        }
    }

    showConquestPath() {
        if (!window.biasClusterData) return;

        const pathData = window.biasClusterData.conquestPath;
        const recommendations = window.biasClusterData.getNextTargets(this.userProgress, 10);

        // 정복 경로 시각화 구현
        const pathGroup = document.getElementById('conquest-path');
        pathGroup.innerHTML = '';

        // 추천된 편향들을 연결하는 경로 그리기
        recommendations.forEach((rec, index) => {
            const biasPos = this.findBiasPosition(rec.bias);
            if (biasPos && index < recommendations.length - 1) {
                const nextPos = this.findBiasPosition(recommendations[index + 1].bias);
                if (nextPos) {
                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    path.setAttribute('x1', biasPos.x);
                    path.setAttribute('y1', biasPos.y);
                    path.setAttribute('x2', nextPos.x);
                    path.setAttribute('y2', nextPos.y);
                    path.setAttribute('stroke', '#ffd700');
                    path.setAttribute('stroke-width', 3);
                    path.setAttribute('stroke-dasharray', '5,5');
                    path.setAttribute('opacity', 0.8);
                    pathGroup.appendChild(path);
                }
            }
        });

        // 정보 패널에 경로 정보 표시
        this.showPathInfo(pathData, recommendations);
    }

    showPathInfo(pathData, recommendations) {
        const panel = document.getElementById('infoPanel');
        const title = document.getElementById('panelTitle');
        const body = document.getElementById('panelBody');

        title.textContent = '🛤️ 추천 정복 경로';
        body.innerHTML = `
            <div class="path-info">
                <div class="next-targets">
                    <h4>다음 추천 편향:</h4>
                    <div class="target-list">
                        ${recommendations.slice(0, 5).map((rec, index) => `
                            <div class="target-item" onclick="biasNavigationMap.selectBias('${rec.bias}')">
                                <span class="target-order">${index + 1}</span>
                                <span class="target-name">${rec.bias}</span>
                                <span class="target-reason">${rec.reason}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>

                <div class="stages-info">
                    <h4>정복 단계:</h4>
                    ${pathData.stages.map(stage => `
                        <div class="stage-item">
                            <h5>${stage.name}</h5>
                            <p>${stage.description}</p>
                            <div class="stage-progress">
                                진행률: ${this.getStageProgress(stage)}%
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;

        panel.style.display = 'block';
        panel.classList.add('show');
    }

    updateProgress() {
        const progressCount = document.getElementById('mapProgressCount');
        const progressRate = document.getElementById('mapProgressRate');
        
        if (progressCount && progressRate) {
            const totalBiases = 60; // TODO: 실제 총 편향 수 계산
            const conqueredCount = this.userProgress.length;
            const rate = Math.round((conqueredCount / totalBiases) * 100);

            progressCount.textContent = conqueredCount;
            progressRate.textContent = rate + '%';
        }
    }

    loadUserProgress() {
        const saved = localStorage.getItem('shiningstars_bias_cards');
        if (saved) {
            const data = JSON.parse(saved);
            return data.collectedCards || [];
        }
        return [];
    }

    // 텍스트 변환 헬퍼 메소드들
    getDifficultyText(difficulty) {
        const texts = { easy: '쉬움', medium: '보통', hard: '어려움' };
        return texts[difficulty] || '보통';
    }

    getImpactText(impact) {
        const texts = { low: '낮음', medium: '보통', high: '높음' };
        return texts[impact] || '보통';
    }

    getRelationshipText(type) {
        const texts = {
            causal: '원인-결과',
            reinforcing: '상호강화',
            related: '관련',
            compensatory: '보상',
            meta: '메타인지'
        };
        return texts[type] || '관련';
    }

    getStageProgress(stage) {
        // TODO: 실제 단계별 진행률 계산
        return Math.round((this.userProgress.length / 60) * 100);
    }

    applyFilter() {
        const filter = document.getElementById('mapFilter')?.value || 'all';
        
        document.querySelectorAll('.bias-node-group').forEach(node => {
            const biasName = node.getAttribute('data-bias');
            const biasData = window.biasClusterData.biasMetadata[biasName];
            const isUnlocked = biasData && this.userProgress.includes(biasData.id);
            const isRecommended = this.isRecommendedBias(biasName);

            let shouldShow = true;
            
            switch (filter) {
                case 'unlocked':
                    shouldShow = isUnlocked;
                    break;
                case 'locked':
                    shouldShow = !isUnlocked;
                    break;
                case 'recommended':
                    shouldShow = isRecommended;
                    break;
                case 'all':
                default:
                    shouldShow = true;
            }

            node.style.display = (shouldShow && this.selectedCluster) ? 'block' : 'none';
        });
    }

    /**
     * 🎪 이벤트 리스너 설정
     */
    bindEventListeners() {
        // ESC 키로 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (this.selectedBias || this.selectedCluster) {
                    this.resetView();
                } else {
                    this.close();
                }
            }
        });

        // 윈도우 리사이즈
        window.addEventListener('resize', () => {
            if (document.getElementById('bias-navigation-map').style.display !== 'none') {
                this.renderCosmicMap();
            }
        });
    }

    /**
     * 🎨 맵 스타일 로드
     */
    loadMapStyles() {
        if (!document.getElementById('navigation-map-styles')) {
            const styles = document.createElement('style');
            styles.id = 'navigation-map-styles';
            styles.textContent = `
                .navigation-map-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.95);
                    z-index: 15000;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .navigation-map-overlay.show {
                    opacity: 1;
                }
                
                .map-container {
                    background: linear-gradient(135deg, #0f172a, #1e293b);
                    border-radius: 20px;
                    width: 95%;
                    max-width: 1400px;
                    height: 90%;
                    display: flex;
                    flex-direction: column;
                    color: white;
                    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
                    position: relative;
                }
                
                .map-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 20px 30px;
                    border-bottom: 2px solid #334155;
                }
                
                .map-header h2 {
                    margin: 0;
                    font-size: 1.8em;
                }
                
                .map-stats {
                    font-size: 0.9em;
                    color: #64b5f6;
                }
                
                .map-controls {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 15px 30px;
                    border-bottom: 1px solid #334155;
                    flex-wrap: wrap;
                    gap: 15px;
                }
                
                .view-controls, .filter-controls {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                }
                
                .control-btn {
                    background: rgba(255, 255, 255, 0.1);
                    border: 1px solid #334155;
                    border-radius: 8px;
                    color: white;
                    padding: 8px 15px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                
                .control-btn:hover {
                    background: rgba(255, 255, 255, 0.2);
                    transform: translateY(-2px);
                }
                
                .map-viewport {
                    flex: 1;
                    position: relative;
                    overflow: hidden;
                }
                
                #cosmic-map {
                    width: 100%;
                    height: 100%;
                    cursor: grab;
                }
                
                #cosmic-map:active {
                    cursor: grabbing;
                }
                
                .cluster-group {
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                
                .cluster-group:hover .cluster-node {
                    transform: scale(1.1);
                    opacity: 1;
                }
                
                .bias-node-group {
                    cursor: pointer;
                    transition: all 0.3s ease;
                }
                
                .bias-node-group:hover .bias-node {
                    transform: scale(1.3);
                    stroke-width: 2;
                }
                
                .info-panel {
                    position: absolute;
                    right: 20px;
                    top: 120px;
                    bottom: 80px;
                    width: 350px;
                    background: rgba(0, 0, 0, 0.9);
                    border-radius: 15px;
                    border: 1px solid #334155;
                    display: none;
                    opacity: 0;
                    transform: translateX(20px);
                    transition: all 0.3s ease;
                }
                
                .info-panel.show {
                    opacity: 1;
                    transform: translateX(0);
                }
                
                .panel-content {
                    height: 100%;
                    display: flex;
                    flex-direction: column;
                }
                
                .panel-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 20px;
                    border-bottom: 1px solid #334155;
                }
                
                .panel-header h3 {
                    margin: 0;
                    font-size: 1.3em;
                }
                
                .panel-close {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5em;
                    cursor: pointer;
                    padding: 5px;
                    opacity: 0.7;
                    transition: opacity 0.3s ease;
                }
                
                .panel-close:hover {
                    opacity: 1;
                }
                
                .panel-body {
                    flex: 1;
                    padding: 20px;
                    overflow-y: auto;
                }
                
                .cluster-stats {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                    margin: 15px 0;
                }
                
                .stat-item {
                    display: flex;
                    justify-content: space-between;
                }
                
                .stat-label {
                    color: #94a3b8;
                }
                
                .bias-grid {
                    display: grid;
                    grid-template-columns: 1fr;
                    gap: 8px;
                    margin-top: 10px;
                }
                
                .bias-item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 8px;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 8px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                
                .bias-item:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
                
                .bias-item.locked {
                    opacity: 0.6;
                }
                
                .action-btn {
                    padding: 10px 15px;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    font-weight: bold;
                    transition: all 0.3s ease;
                    margin: 5px;
                }
                
                .action-btn.primary {
                    background: #3b82f6;
                    color: white;
                }
                
                .action-btn.primary:hover {
                    background: #2563eb;
                    transform: translateY(-2px);
                }
                
                .action-btn.secondary {
                    background: rgba(255, 255, 255, 0.1);
                    color: white;
                    border: 1px solid #334155;
                }
                
                .action-btn.secondary:hover {
                    background: rgba(255, 255, 255, 0.2);
                }
                
                .map-legend {
                    position: absolute;
                    bottom: 20px;
                    left: 20px;
                    background: rgba(0, 0, 0, 0.8);
                    border-radius: 10px;
                    padding: 15px;
                    border: 1px solid #334155;
                }
                
                .legend-title {
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                
                .legend-items {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                
                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 0.9em;
                }
                
                .legend-dot {
                    width: 12px;
                    height: 12px;
                    border-radius: 50%;
                    display: inline-block;
                }
                
                .legend-dot.cluster-core {
                    background: #667eea;
                    box-shadow: 0 0 10px rgba(102, 126, 234, 0.5);
                }
                
                .legend-dot.bias-unlocked {
                    background: #4ade80;
                }
                
                .legend-dot.bias-locked {
                    background: #64748b;
                }
                
                .legend-dot.bias-recommended {
                    background: #ffd700;
                    box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
                }
                
                .close-map {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5em;
                    cursor: pointer;
                    padding: 5px 10px;
                    border-radius: 50%;
                    transition: background 0.3s ease;
                }
                
                .close-map:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
                
                @keyframes fadeInScale {
                    from {
                        opacity: 0;
                        transform: scale(0.8);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
                
                .bias-status-badge {
                    display: inline-block;
                    padding: 5px 10px;
                    border-radius: 15px;
                    font-size: 0.8em;
                    font-weight: bold;
                    margin-bottom: 15px;
                }
                
                .bias-status-badge.unlocked {
                    background: #4ade80;
                    color: #1a1a2e;
                }
                
                .bias-status-badge.locked {
                    background: #64748b;
                    color: white;
                }
                
                .bias-meta {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    margin-bottom: 15px;
                }
                
                .meta-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                }
                
                .difficulty-easy { color: #4ade80; }
                .difficulty-medium { color: #fbbf24; }
                .difficulty-hard { color: #f87171; }
                
                .impact-low { color: #94a3b8; }
                .impact-medium { color: #fbbf24; }
                .impact-high { color: #f87171; }
                
                .related-list {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                
                .related-item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 8px;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 8px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                
                .related-item:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
                
                .relation-type {
                    font-size: 0.8em;
                    color: #94a3b8;
                    min-width: 60px;
                }
                
                .strength-indicator {
                    height: 3px;
                    background: #3b82f6;
                    border-radius: 2px;
                    min-width: 20px;
                }
                
                .target-list {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                
                .target-item {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 12px;
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 8px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                
                .target-item:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
                
                .target-order {
                    background: #ffd700;
                    color: #1a1a2e;
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    font-size: 0.9em;
                }
                
                .target-name {
                    font-weight: bold;
                    flex: 1;
                }
                
                .target-reason {
                    font-size: 0.8em;
                    color: #94a3b8;
                    max-width: 200px;
                    text-align: right;
                }

                /* 모바일 반응형 */
                @media (max-width: 768px) {
                    .map-container {
                        width: 100%;
                        height: 100%;
                        border-radius: 0;
                    }
                    
                    .info-panel {
                        position: relative;
                        right: auto;
                        top: auto;
                        bottom: auto;
                        width: 100%;
                        margin-top: 10px;
                    }
                    
                    .map-viewport {
                        height: 300px;
                    }
                }
            `;
            document.head.appendChild(styles);
        }
    }
}

// 전역 인스턴스 생성
window.biasNavigationMap = new BiasNavigationMap();