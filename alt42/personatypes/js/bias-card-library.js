/**
 * 🃏 편향 카드 도감 인터페이스
 * 60개 편향 카드를 탐색하고 학습할 수 있는 라이브러리 시스템
 */

class BiasCardLibrary {
    constructor() {
        this.isOpen = false;
        this.currentFilter = 'all';
        this.currentSort = 'category';
        this.searchQuery = '';
        
        this.init();
    }

    /**
     * 🚀 라이브러리 초기화
     */
    init() {
        this.createLibraryInterface();
        this.bindEventListeners();
    }

    /**
     * 🎨 도감 인터페이스 생성
     */
    createLibraryInterface() {
        const libraryHTML = `
            <div id="bias-card-library" class="bias-library-overlay">
                <div class="library-container">
                    <!-- 헤더 -->
                    <div class="library-header">
                        <h2>🃏 인지편향 카드 도감</h2>
                        <div class="library-stats">
                            <span class="collected-count">수집: <span id="collectedCount">0</span>/${this.getTotalCardCount()}</span>
                            <span class="completion-rate">완성도: <span id="completionRate">0%</span></span>
                        </div>
                        <button class="close-library" onclick="biasCardLibrary.close()">✕</button>
                    </div>

                    <!-- 필터 및 검색 -->
                    <div class="library-controls">
                        <div class="navigation-controls">
                            <button class="nav-btn map-btn" onclick="biasNavigationMap.open()" title="우주 지도 보기">
                                🗺️ 우주 지도
                            </button>
                        </div>
                        
                        <div class="filter-controls">
                            <label>카테고리:</label>
                            <select id="categoryFilter" onchange="biasCardLibrary.applyFilter()">
                                <option value="all">전체</option>
                                <option value="level1">Level 1 - 기초 편향</option>
                                <option value="level2">Level 2 - 학습 편향</option>
                                <option value="level3">Level 3 - 고차원 편향</option>
                            </select>
                        </div>
                        
                        <div class="status-filter">
                            <label>상태:</label>
                            <select id="statusFilter" onchange="biasCardLibrary.applyFilter()">
                                <option value="all">전체</option>
                                <option value="unlocked">잠금해제</option>
                                <option value="locked">잠금</option>
                            </select>
                        </div>
                        
                        <div class="search-box">
                            <input type="text" id="searchInput" placeholder="편향 이름 검색..." 
                                   oninput="biasCardLibrary.search(this.value)">
                            <span class="search-icon">🔍</span>
                        </div>
                        
                        <div class="sort-controls">
                            <label>정렬:</label>
                            <select id="sortSelect" onchange="biasCardLibrary.applySort()">
                                <option value="category">카테고리별</option>
                                <option value="name">이름순</option>
                                <option value="rarity">희귀도</option>
                                <option value="collected">수집순</option>
                            </select>
                        </div>
                    </div>

                    <!-- 카드 그리드 -->
                    <div class="library-content">
                        <div id="cardGrid" class="card-grid">
                            <!-- 카드들이 동적으로 생성됨 -->
                        </div>
                    </div>

                    <!-- 하단 정보 -->
                    <div class="library-footer">
                        <div class="legend">
                            <span class="legend-item"><span class="rarity common">■</span> 일반</span>
                            <span class="legend-item"><span class="rarity uncommon">■</span> 고급</span>
                            <span class="legend-item"><span class="rarity rare">■</span> 희귀</span>
                            <span class="legend-item"><span class="rarity legendary">■</span> 전설</span>
                        </div>
                        <div class="collection-tips">
                            💡 편향을 극복하여 카드를 수집하세요! 모든 카드를 모으면 특별한 보상이 있어요.
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 페이지에 추가
        if (!document.getElementById('bias-card-library')) {
            document.body.insertAdjacentHTML('beforeend', libraryHTML);
        }
    }

    /**
     * 📂 도감 열기
     */
    open() {
        const library = document.getElementById('bias-card-library');
        if (library) {
            library.style.display = 'flex';
            this.isOpen = true;
            this.refreshLibrary();
            
            // 통계 업데이트
            this.updateStats();
            
            // 애니메이션
            setTimeout(() => {
                library.classList.add('show');
            }, 10);
        }
    }

    /**
     * 📁 도감 닫기
     */
    close() {
        const library = document.getElementById('bias-card-library');
        if (library) {
            library.classList.remove('show');
            setTimeout(() => {
                library.style.display = 'none';
                this.isOpen = false;
            }, 300);
        }
    }

    /**
     * 🔄 라이브러리 새로고침
     */
    refreshLibrary() {
        if (!window.biasCardSystem) return;
        
        const allCards = this.getAllCards();
        const filteredCards = this.filterCards(allCards);
        const sortedCards = this.sortCards(filteredCards);
        
        this.renderCards(sortedCards);
    }

    /**
     * 🃏 모든 카드 데이터 가져오기
     */
    getAllCards() {
        if (!window.biasCardSystem) return [];
        
        const cards = [];
        const allBiasCards = window.biasCardSystem.allBiasCards;
        
        for (const [level, levelCards] of Object.entries(allBiasCards)) {
            for (const [biasName, cardData] of Object.entries(levelCards)) {
                cards.push({
                    ...cardData,
                    level: level,
                    biasName: biasName
                });
            }
        }
        
        return cards;
    }

    /**
     * 🔍 카드 필터링
     */
    filterCards(cards) {
        return cards.filter(card => {
            // 카테고리 필터
            if (this.currentFilter !== 'all' && card.level !== this.currentFilter) {
                return false;
            }
            
            // 상태 필터
            const statusFilter = document.getElementById('statusFilter')?.value;
            if (statusFilter === 'unlocked' && !card.unlocked) return false;
            if (statusFilter === 'locked' && card.unlocked) return false;
            
            // 검색 쿼리
            if (this.searchQuery && 
                !card.name.toLowerCase().includes(this.searchQuery.toLowerCase()) &&
                !card.shortDescription.toLowerCase().includes(this.searchQuery.toLowerCase())) {
                return false;
            }
            
            return true;
        });
    }

    /**
     * 📊 카드 정렬
     */
    sortCards(cards) {
        const sortBy = this.currentSort;
        
        return cards.sort((a, b) => {
            switch (sortBy) {
                case 'name':
                    return a.name.localeCompare(b.name);
                case 'rarity':
                    const rarityOrder = { common: 0, uncommon: 1, rare: 2, legendary: 3 };
                    return (rarityOrder[b.rarity] || 0) - (rarityOrder[a.rarity] || 0);
                case 'collected':
                    if (a.unlocked && !b.unlocked) return -1;
                    if (!a.unlocked && b.unlocked) return 1;
                    if (a.collectedAt && b.collectedAt) {
                        return new Date(b.collectedAt) - new Date(a.collectedAt);
                    }
                    return 0;
                case 'category':
                default:
                    const levelOrder = { level1: 1, level2: 2, level3: 3 };
                    const levelDiff = (levelOrder[a.level] || 0) - (levelOrder[b.level] || 0);
                    if (levelDiff !== 0) return levelDiff;
                    return a.name.localeCompare(b.name);
            }
        });
    }

    /**
     * 🎨 카드 렌더링
     */
    renderCards(cards) {
        const cardGrid = document.getElementById('cardGrid');
        if (!cardGrid) return;
        
        cardGrid.innerHTML = '';
        
        cards.forEach(card => {
            const cardElement = this.createCardElement(card);
            cardGrid.appendChild(cardElement);
        });
        
        // 빈 상태 표시
        if (cards.length === 0) {
            cardGrid.innerHTML = `
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <div class="empty-message">조건에 맞는 카드가 없습니다.</div>
                    <div class="empty-suggestion">다른 필터를 시도해보세요.</div>
                </div>
            `;
        }
    }

    /**
     * 🃏 개별 카드 요소 생성
     */
    createCardElement(card) {
        const cardDiv = document.createElement('div');
        cardDiv.className = `library-card ${card.rarity} ${card.unlocked ? 'unlocked' : 'locked'}`;
        cardDiv.onclick = () => this.openCardDetail(card);
        
        cardDiv.innerHTML = `
            <div class="card-preview">
                <div class="card-symbol">${card.unlocked ? card.cosmicSymbol : '🔒'}</div>
                <div class="card-name">${card.unlocked ? card.name : '???'}</div>
                <div class="card-category">${this.getCategoryName(card.level)}</div>
                <div class="rarity-indicator ${card.rarity}"></div>
            </div>
            
            <div class="card-info">
                <div class="card-description">
                    ${card.unlocked ? card.shortDescription : '편향을 극복하여 잠금해제하세요'}
                </div>
                
                ${card.unlocked && card.collectedAt ? `
                    <div class="collection-date">
                        ⭐ ${new Date(card.collectedAt).toLocaleDateString()} 수집
                    </div>
                ` : ''}
            </div>
            
            <div class="card-actions">
                <button class="view-detail-btn" onclick="event.stopPropagation(); biasCardLibrary.openCardDetail(card)">
                    ${card.unlocked ? '자세히 보기' : '미리보기'}
                </button>
                ${card.unlocked ? `
                    <button class="session-btn" onclick="event.stopPropagation(); biasCardSession.startSession('${card.biasName}')">
                        🎓 학습 세션
                    </button>
                ` : ''}
            </div>
        `;
        
        return cardDiv;
    }

    /**
     * 📖 카드 상세 정보 열기
     */
    openCardDetail(card) {
        if (window.biasCardSystem) {
            window.biasCardSystem.showLibraryCard(card.id);
        }
    }

    /**
     * 🔍 검색 기능
     */
    search(query) {
        this.searchQuery = query;
        this.refreshLibrary();
    }

    /**
     * 🔽 필터 적용
     */
    applyFilter() {
        const categoryFilter = document.getElementById('categoryFilter')?.value;
        this.currentFilter = categoryFilter || 'all';
        this.refreshLibrary();
    }

    /**
     * 📊 정렬 적용
     */
    applySort() {
        const sortSelect = document.getElementById('sortSelect')?.value;
        this.currentSort = sortSelect || 'category';
        this.refreshLibrary();
    }

    /**
     * 📈 통계 업데이트
     */
    updateStats() {
        const allCards = this.getAllCards();
        const unlockedCards = allCards.filter(card => card.unlocked);
        
        const collectedCount = document.getElementById('collectedCount');
        const completionRate = document.getElementById('completionRate');
        
        if (collectedCount) {
            collectedCount.textContent = unlockedCards.length;
        }
        
        if (completionRate) {
            const rate = Math.round((unlockedCards.length / allCards.length) * 100);
            completionRate.textContent = rate + '%';
        }
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    getTotalCardCount() {
        if (!window.biasCardSystem) return 60; // 예상 총 카드 수
        
        let count = 0;
        const allBiasCards = window.biasCardSystem.allBiasCards;
        for (const level of Object.values(allBiasCards)) {
            count += Object.keys(level).length;
        }
        return count;
    }

    getCategoryName(level) {
        const names = {
            level1: 'Level 1',
            level2: 'Level 2', 
            level3: 'Level 3'
        };
        return names[level] || 'Unknown';
    }

    /**
     * 🎪 이벤트 리스너 설정
     */
    bindEventListeners() {
        // ESC 키로 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // 카드 수집 시 라이브러리 업데이트
        document.addEventListener('biasCardCollected', () => {
            if (this.isOpen) {
                this.refreshLibrary();
                this.updateStats();
            }
        });
    }

    /**
     * 🎨 라이브러리 스타일 로드
     */
    static loadStyles() {
        if (!document.getElementById('bias-library-styles')) {
            const styles = document.createElement('style');
            styles.id = 'bias-library-styles';
            styles.textContent = `
                .bias-library-overlay {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.9);
                    z-index: 20000;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .bias-library-overlay.show {
                    opacity: 1;
                }
                
                .library-container {
                    background: linear-gradient(135deg, #1a1a2e, #16213e);
                    border-radius: 20px;
                    width: 90%;
                    max-width: 1200px;
                    height: 90%;
                    display: flex;
                    flex-direction: column;
                    color: white;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
                }
                
                .library-header {
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    padding: 20px 30px;
                    border-bottom: 2px solid #0f3460;
                }
                
                .library-header h2 {
                    margin: 0;
                    font-size: 1.8em;
                }
                
                .library-stats {
                    display: flex;
                    gap: 20px;
                    font-size: 0.9em;
                    color: #64b5f6;
                }
                
                .library-controls {
                    display: flex;
                    gap: 15px;
                    padding: 15px 30px;
                    border-bottom: 1px solid #0f3460;
                    flex-wrap: wrap;
                    align-items: center;
                }
                
                .navigation-controls {
                    display: flex;
                    gap: 10px;
                }
                
                .nav-btn {
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    border: none;
                    border-radius: 10px;
                    color: white;
                    padding: 10px 15px;
                    cursor: pointer;
                    font-weight: bold;
                    font-size: 0.9em;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                }
                
                .nav-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
                    background: linear-gradient(135deg, #764ba2, #667eea);
                }
                
                .nav-btn:active {
                    transform: translateY(0);
                }
                
                .library-controls select,
                .library-controls input {
                    background: rgba(255, 255, 255, 0.1);
                    border: 1px solid #0f3460;
                    border-radius: 8px;
                    color: white;
                    padding: 8px 12px;
                }
                
                .search-box {
                    position: relative;
                    flex: 1;
                    max-width: 300px;
                }
                
                .search-icon {
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    color: #64b5f6;
                }
                
                .library-content {
                    flex: 1;
                    overflow-y: auto;
                    padding: 20px 30px;
                }
                
                .card-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                    gap: 20px;
                }
                
                .library-card {
                    background: rgba(255, 255, 255, 0.05);
                    border-radius: 15px;
                    padding: 20px;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    border: 2px solid transparent;
                    position: relative;
                }
                
                .library-card:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                }
                
                .library-card.unlocked {
                    border-color: #4ade80;
                }
                
                .library-card.locked {
                    border-color: #64748b;
                    opacity: 0.6;
                }
                
                .card-preview {
                    text-align: center;
                    margin-bottom: 15px;
                }
                
                .card-symbol {
                    font-size: 3em;
                    margin-bottom: 10px;
                }
                
                .card-name {
                    font-size: 1.3em;
                    font-weight: bold;
                    margin-bottom: 5px;
                }
                
                .card-category {
                    font-size: 0.9em;
                    color: #64b5f6;
                    margin-bottom: 10px;
                }
                
                .rarity-indicator {
                    width: 100%;
                    height: 4px;
                    border-radius: 2px;
                    margin: 10px 0;
                }
                
                .rarity-indicator.common { background: #9ca3af; }
                .rarity-indicator.uncommon { background: #10b981; }
                .rarity-indicator.rare { background: #3b82f6; }
                .rarity-indicator.legendary { background: #f59e0b; }
                
                .card-description {
                    font-size: 0.9em;
                    color: #d1d5db;
                    margin-bottom: 10px;
                    min-height: 40px;
                }
                
                .collection-date {
                    font-size: 0.8em;
                    color: #fbbf24;
                    margin-bottom: 10px;
                }
                
                .view-detail-btn {
                    width: 100%;
                    padding: 8px;
                    background: #2563eb;
                    color: white;
                    border: none;
                    border-radius: 8px;
                    cursor: pointer;
                    transition: background 0.3s ease;
                }
                
                .view-detail-btn:hover {
                    background: #1d4ed8;
                }
                
                .library-footer {
                    padding: 15px 30px;
                    border-top: 1px solid #0f3460;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    flex-wrap: wrap;
                    gap: 15px;
                }
                
                .legend {
                    display: flex;
                    gap: 15px;
                }
                
                .legend-item {
                    display: flex;
                    align-items: center;
                    gap: 5px;
                    font-size: 0.9em;
                }
                
                .empty-state {
                    text-align: center;
                    padding: 60px 20px;
                    color: #9ca3af;
                }
                
                .empty-icon {
                    font-size: 4em;
                    margin-bottom: 20px;
                }
                
                .empty-message {
                    font-size: 1.2em;
                    margin-bottom: 10px;
                }
                
                .close-library {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.5em;
                    cursor: pointer;
                    padding: 5px 10px;
                    border-radius: 50%;
                    transition: background 0.3s ease;
                }
                
                .close-library:hover {
                    background: rgba(255, 255, 255, 0.1);
                }
            `;
            document.head.appendChild(styles);
        }
    }
}

// 스타일 로드 및 전역 인스턴스 생성
BiasCardLibrary.loadStyles();
window.biasCardLibrary = new BiasCardLibrary();