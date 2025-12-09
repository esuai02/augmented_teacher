/**
 * 🃏 완전한 인지관성 카드 도감 시스템
 * 60개 인지관성 카드를 5개 그룹으로 분류하여 관리
 * readme_personainfo.txt 기반 완전 구현
 */

class BiasCardCompleteLibrary {
    constructor() {
        this.isOpen = false;
        this.currentGroup = 'all';
        this.currentView = 'grid'; // grid, list, detail
        this.selectedCard = null;
        this.collectedCards = new Set();
        this.userRole = document.querySelector('meta[name="user-role"]')?.content || 'student';
        
        // 5개 관성군 정의
        this.inertiaGroups = {
            perception: {
                name: '인식 관성군',
                emoji: '🧠',
                color: '#667eea',
                description: '정보를 인식하고 처리하는 과정에서 발생하는 인지관성',
                cards: [
                    { id: 1, name: '확증관성', english: 'Confirmation Inertia', desc: '기존 믿음을 확인하는 정보만 선택적으로 받아들이는 경향' },
                    { id: 2, name: '선택적주의', english: 'Selective Attention', desc: '특정 정보에만 주의를 기울이고 나머지는 무시하는 경향' },
                    { id: 3, name: '후광효과', english: 'Halo Effect', desc: '한 가지 긍정적 특성이 전체 평가에 영향을 미치는 현상' },
                    { id: 4, name: '프레이밍효과', english: 'Framing Effect', desc: '정보의 제시 방식이 판단에 영향을 미치는 현상' },
                    { id: 5, name: '대표성휴리스틱', english: 'Representativeness Heuristic', desc: '전형적인 예를 기준으로 판단하는 경향' },
                    { id: 6, name: '기준율무시', english: 'Base Rate Neglect', desc: '통계적 기준율을 무시하고 개별 사례에 집중하는 경향' },
                    { id: 7, name: '회상관성', english: 'Recall Inertia', desc: '기억이 특정 방향으로 왜곡되어 회상되는 경향' },
                    { id: 8, name: '허상관', english: 'Illusory Correlation', desc: '실제로 존재하지 않는 상관관계를 인식하는 경향' },
                    { id: 9, name: '생존자관성', english: 'Survivorship Inertia', desc: '성공한 사례만 보고 실패한 사례를 무시하는 경향' },
                    { id: 10, name: '관찰자관성', english: 'Observer Inertia', desc: '관찰자의 기대가 관찰 결과에 영향을 미치는 현상' },
                    { id: 11, name: '정보관성', english: 'Information Inertia', desc: '불필요한 정보를 계속 추구하는 경향' },
                    { id: 12, name: '현상유지관성', english: 'Status Quo Inertia', desc: '현재 상태를 유지하려는 경향' }
                ]
            },
            judgment: {
                name: '판단 관성군',
                emoji: '🎯',
                color: '#764ba2',
                description: '의사결정과 판단 과정에서 발생하는 인지관성',
                cards: [
                    { id: 13, name: '앵커링관성', english: 'Anchoring Inertia', desc: '첫 정보에 과도하게 의존하는 경향' },
                    { id: 14, name: '가용성휴리스틱', english: 'Availability Heuristic', desc: '쉽게 떠오르는 정보로 판단하는 경향' },
                    { id: 15, name: '기준점관성', english: 'Reference Point Inertia', desc: '특정 기준점을 기준으로 판단하는 경향' },
                    { id: 16, name: '불충분조정', english: 'Insufficient Adjustment', desc: '초기값에서 충분히 조정하지 못하는 경향' },
                    { id: 17, name: '투사관성', english: 'Projection Inertia', desc: '자신의 생각을 타인에게 투사하는 경향' },
                    { id: 18, name: '계획오류', english: 'Planning Fallacy', desc: '과제 완료 시간을 과소평가하는 경향' },
                    { id: 19, name: '과신관성', english: 'Overconfidence Inertia', desc: '자신의 능력을 과대평가하는 경향' },
                    { id: 20, name: '통제착각', english: 'Illusion of Control', desc: '통제할 수 없는 것을 통제할 수 있다고 믿는 경향' },
                    { id: 21, name: '확실성효과', english: 'Certainty Effect', desc: '확실한 것을 과대평가하는 경향' },
                    { id: 22, name: '손실회피', english: 'Loss Aversion', desc: '손실을 이익보다 더 크게 느끼는 경향' },
                    { id: 23, name: '매몰비용오류', english: 'Sunk Cost Fallacy', desc: '이미 투자한 것 때문에 계속 투자하는 경향' },
                    { id: 24, name: '기대효용이론위반', english: 'Expected Utility Theory Violation', desc: '합리적 기대와 다르게 행동하는 경향' },
                    { id: 25, name: '확률가중관성', english: 'Probability Weighting Inertia', desc: '확률을 왜곡하여 인식하는 경향' },
                    { id: 26, name: '시간할인관성', english: 'Time Discounting Inertia', desc: '미래 가치를 과소평가하는 경향' },
                    { id: 27, name: '현재관성', english: 'Present Inertia', desc: '현재를 과도하게 중시하는 경향' }
                ]
            },
            learning: {
                name: '학습 관성군',
                emoji: '📚',
                color: '#f59e0b',
                description: '학습과 성장 과정에서 나타나는 인지관성',
                cards: [
                    { id: 28, name: '자기과소평가', english: 'Self-Underestimation', desc: '자신의 능력을 과소평가하는 경향' },
                    { id: 29, name: '회피행동', english: 'Avoidance Behavior', desc: '어려운 것을 회피하려는 경향' },
                    { id: 30, name: '학습된무기력', english: 'Learned Helplessness', desc: '시도해도 안 된다고 믿는 경향' },
                    { id: 31, name: '고정관념사고', english: 'Fixed Mindset', desc: '능력이 고정되어 있다고 믿는 사고방식' },
                    { id: 32, name: '완벽주의', english: 'Perfectionism', desc: '완벽하지 않으면 실패라고 생각하는 경향' },
                    { id: 33, name: '비교관성', english: 'Comparison Inertia', desc: '타인과 비교하여 자신을 평가하는 경향' },
                    { id: 34, name: '실패공포', english: 'Fear of Failure', desc: '실패를 지나치게 두려워하는 경향' },
                    { id: 35, name: '성공공포', english: 'Fear of Success', desc: '성공의 부담을 두려워하는 경향' },
                    { id: 36, name: '귀인관성', english: 'Attribution Inertia', desc: '원인을 잘못 귀인하는 경향' },
                    { id: 37, name: '자기방해', english: 'Self-Handicapping', desc: '스스로 성공을 방해하는 경향' },
                    { id: 38, name: '조급증관성', english: 'Impatience Inertia', desc: '빠른 결과를 원하는 경향' },
                    { id: 39, name: '단기성과관성', english: 'Short-term Performance Inertia', desc: '단기 성과에만 집중하는 경향' },
                    { id: 40, name: '노력역설', english: 'Effort Paradox', desc: '노력을 부정적으로 인식하는 경향' }
                ]
            },
            emotional: {
                name: '감정 관성군',
                emoji: '💭',
                color: '#ef4444',
                description: '감정이 인지에 미치는 영향과 관련된 인지관성',
                cards: [
                    { id: 41, name: '재앙화사고', english: 'Catastrophizing', desc: '최악의 상황을 상상하는 경향' },
                    { id: 42, name: '흑백사고', english: 'Black-and-White Thinking', desc: '극단적으로만 생각하는 경향' },
                    { id: 43, name: '과일반화', english: 'Overgeneralization', desc: '하나의 사례를 전체로 확대하는 경향' },
                    { id: 44, name: '감정추론', english: 'Emotional Reasoning', desc: '감정을 사실로 받아들이는 경향' },
                    { id: 45, name: '부정적필터', english: 'Negative Filter', desc: '부정적인 면만 보는 경향' },
                    { id: 46, name: '긍정적무시', english: 'Disqualifying the Positive', desc: '긍정적인 면을 무시하는 경향' },
                    { id: 47, name: '마음읽기', english: 'Mind Reading', desc: '타인의 생각을 안다고 착각하는 경향' },
                    { id: 48, name: '운세오류', english: 'Fortune Telling Error', desc: '미래를 부정적으로 예측하는 경향' },
                    { id: 49, name: '개인화', english: 'Personalization', desc: '모든 것을 자신과 연결짓는 경향' },
                    { id: 50, name: '감정적의사결정', english: 'Emotional Decision Making', desc: '감정에 의존하여 결정하는 경향' }
                ]
            },
            social: {
                name: '사회적 관성군',
                emoji: '👥',
                color: '#10b981',
                description: '타인과의 관계에서 발생하는 인지관성',
                cards: [
                    { id: 51, name: '더닝크루거효과', english: 'Dunning-Kruger Effect', desc: '무능한 사람이 자신을 과대평가하는 현상' },
                    { id: 52, name: '관성맹점', english: 'Inertia Blind Spot', desc: '자신의 인지관성은 못 보는 경향' },
                    { id: 53, name: '사회적비교', english: 'Social Comparison', desc: '타인과 비교하여 자신을 평가하는 경향' },
                    { id: 54, name: '동조관성', english: 'Conformity Inertia', desc: '집단 의견을 따르는 경향' },
                    { id: 55, name: '권위관성', english: 'Authority Inertia', desc: '권위자의 말을 무비판적으로 받아들이는 경향' },
                    { id: 56, name: '집단사고', english: 'Groupthink', desc: '집단 합의를 위해 비판적 사고를 포기하는 현상' },
                    { id: 57, name: '내집단관성', english: 'In-group Inertia', desc: '내집단 구성원을 우호적으로 평가하는 경향' },
                    { id: 58, name: '외집단동질성관성', english: 'Out-group Homogeneity Inertia', desc: '외집단을 동질적으로 보는 경향' },
                    { id: 59, name: '근본귀인오류', english: 'Fundamental Attribution Error', desc: '상황보다 개인 성향으로 귀인하는 경향' },
                    { id: 60, name: '행위자관찰자관성', english: 'Actor-Observer Inertia', desc: '자신과 타인의 행동을 다르게 평가하는 경향' }
                ]
            }
        };
        
        this.init();
    }

    /**
     * 초기화
     */
    init() {
        this.loadCollectedCards();
        this.createLibraryInterface();
        this.bindEventListeners();
    }

    /**
     * 수집된 카드 로드
     */
    loadCollectedCards() {
        const saved = localStorage.getItem('shiningstars_collected_cards');
        if (saved) {
            this.collectedCards = new Set(JSON.parse(saved));
        }
    }

    /**
     * 도감 인터페이스 생성
     */
    createLibraryInterface() {
        const libraryHTML = `
            <div id="complete-card-library" class="complete-library-overlay">
                <div class="complete-library-container">
                    <!-- 헤더 -->
                    <div class="library-header">
                        <div class="header-left">
                            <h2>🃏 인지관성 카드 도감</h2>
                            <span class="subtitle">
                                ${this.userRole === 'student' ? 
                                    '60개의 인지관성을 정복하세요' : 
                                    '👁️ 교사 열람 모드 - 모든 카드 확인 가능'}
                            </span>
                        </div>
                        <div class="header-stats">
                            ${this.userRole === 'student' ? `
                                <div class="stat-item">
                                    <span class="stat-label">수집</span>
                                    <span class="stat-value">${this.collectedCards.size}/60</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">완성도</span>
                                    <span class="stat-value">${Math.floor(this.collectedCards.size / 60 * 100)}%</span>
                                </div>
                            ` : `
                                <div class="stat-item">
                                    <span class="stat-label">전체 카드</span>
                                    <span class="stat-value">60개</span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-label">학생 수집</span>
                                    <span class="stat-value">${this.collectedCards.size}/60</span>
                                </div>
                            `}
                        </div>
                        <button class="close-library" onclick="biasCardCompleteLibrary.close()">✕</button>
                    </div>

                    <!-- 그룹 탭 -->
                    <div class="group-tabs">
                        <button class="tab-btn active" data-group="all" onclick="biasCardCompleteLibrary.selectGroup('all')">
                            전체 (60)
                        </button>
                        ${Object.entries(this.inertiaGroups).map(([key, group]) => `
                            <button class="tab-btn" data-group="${key}" onclick="biasCardCompleteLibrary.selectGroup('${key}')">
                                ${group.emoji} ${group.name} (${group.cards.length})
                            </button>
                        `).join('')}
                    </div>

                    <!-- 뷰 모드 선택 -->
                    <div class="view-controls">
                        <button class="view-btn active" data-view="grid" onclick="biasCardCompleteLibrary.changeView('grid')">
                            <span>⊞</span> 그리드
                        </button>
                        <button class="view-btn" data-view="list" onclick="biasCardCompleteLibrary.changeView('list')">
                            <span>☰</span> 리스트
                        </button>
                        <div class="search-container">
                            <input type="text" placeholder="카드 검색..." id="card-search" oninput="biasCardCompleteLibrary.search(this.value)">
                            <span class="search-icon">🔍</span>
                        </div>
                    </div>

                    <!-- 카드 컨테이너 -->
                    <div class="cards-container" id="cards-container">
                        <!-- 카드들이 동적으로 생성됨 -->
                    </div>

                    <!-- 진행 가이드 -->
                    <div class="progress-guide">
                        <h3>🎯 단계별 정복 가이드</h3>
                        <div class="guide-steps">
                            <div class="guide-step ${this.getStepStatus(1)}">
                                <span class="step-num">1단계</span>
                                <span class="step-name">인식 관성군</span>
                                <span class="step-progress">${this.getGroupProgress('perception')}/12</span>
                            </div>
                            <div class="guide-step ${this.getStepStatus(2)}">
                                <span class="step-num">2단계</span>
                                <span class="step-name">판단 & 학습 관성군</span>
                                <span class="step-progress">${this.getGroupProgress('judgment') + this.getGroupProgress('learning')}/28</span>
                            </div>
                            <div class="guide-step ${this.getStepStatus(3)}">
                                <span class="step-num">3단계</span>
                                <span class="step-name">감정 관성군</span>
                                <span class="step-progress">${this.getGroupProgress('emotional')}/10</span>
                            </div>
                            <div class="guide-step ${this.getStepStatus(4)}">
                                <span class="step-num">4단계</span>
                                <span class="step-name">사회적 관성군</span>
                                <span class="step-progress">${this.getGroupProgress('social')}/10</span>
                            </div>
                        </div>
                        <div class="final-goal">
                            <span class="goal-label">최종 목표:</span>
                            <span class="goal-text">통합적 인지 유연성 달성</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        if (!document.getElementById('complete-card-library')) {
            document.body.insertAdjacentHTML('beforeend', libraryHTML);
        }

        this.renderCards();
    }

    /**
     * 카드 렌더링
     */
    renderCards() {
        const container = document.getElementById('cards-container');
        if (!container) return;

        let cards = [];
        
        if (this.currentGroup === 'all') {
            Object.values(this.inertiaGroups).forEach(group => {
                cards = cards.concat(group.cards.map(card => ({
                    ...card,
                    groupColor: group.color,
                    groupName: group.name
                })));
            });
        } else if (this.inertiaGroups[this.currentGroup]) {
            const group = this.inertiaGroups[this.currentGroup];
            cards = group.cards.map(card => ({
                ...card,
                groupColor: group.color,
                groupName: group.name
            }));
        }

        if (this.currentView === 'grid') {
            container.className = 'cards-container grid-view';
            container.innerHTML = cards.map(card => this.renderGridCard(card)).join('');
        } else {
            container.className = 'cards-container list-view';
            container.innerHTML = cards.map(card => this.renderListCard(card)).join('');
        }
    }

    /**
     * 그리드 카드 렌더링
     */
    renderGridCard(card) {
        const isCollected = this.collectedCards.has(card.id);
        const isAccessible = this.userRole !== 'student' || isCollected; // 학생이 아니면 항상 접근 가능
        const rarity = this.getCardRarity(card.id);
        
        return `
            <div class="card-item ${isAccessible ? 'collected' : 'locked'} rarity-${rarity} ${this.userRole !== 'student' ? 'teacher-mode' : ''}" 
                 onclick="biasCardCompleteLibrary.showCardDetail(${card.id})"
                 style="--group-color: ${card.groupColor}">
                <div class="card-number">#${String(card.id).padStart(2, '0')}</div>
                <div class="card-icon">${isAccessible ? this.getCardEmoji(card.id) : '🔒'}</div>
                <div class="card-name">${isAccessible ? card.name : '???'}</div>
                <div class="card-english">${isAccessible ? card.english : ''}</div>
                <div class="card-rarity">
                    ${this.getRarityStars(rarity)}
                </div>
                ${this.userRole !== 'student' && !isCollected ? '<div class="teacher-badge">👁️</div>' : ''}
            </div>
        `;
    }

    /**
     * 리스트 카드 렌더링
     */
    renderListCard(card) {
        const isCollected = this.collectedCards.has(card.id);
        const isAccessible = this.userRole !== 'student' || isCollected; // 학생이 아니면 항상 접근 가능
        const rarity = this.getCardRarity(card.id);
        
        return `
            <div class="list-item ${isAccessible ? 'collected' : 'locked'} ${this.userRole !== 'student' ? 'teacher-mode' : ''}"
                 onclick="biasCardCompleteLibrary.showCardDetail(${card.id})"
                 style="--group-color: ${card.groupColor}">
                <div class="list-number">#${String(card.id).padStart(2, '0')}</div>
                <div class="list-icon">${isAccessible ? this.getCardEmoji(card.id) : '🔒'}</div>
                <div class="list-info">
                    <div class="list-name">${isAccessible ? card.name : '???'}</div>
                    <div class="list-desc">${isAccessible ? card.desc : '카드를 수집하여 정보를 확인하세요'}</div>
                </div>
                <div class="list-meta">
                    <span class="list-english">${isAccessible ? card.english : ''}</span>
                    <span class="list-rarity">${this.getRarityStars(rarity)}</span>
                    ${this.userRole !== 'student' && !isCollected ? '<span class="teacher-indicator">👁️ 교사 모드</span>' : ''}
                </div>
            </div>
        `;
    }

    /**
     * 카드 상세 보기
     */
    showCardDetail(cardId) {
        const card = this.findCardById(cardId);
        if (!card) return;
        
        // 학생이 아니거나 카드를 수집한 경우에만 상세 보기 가능
        const isAccessible = this.userRole !== 'student' || this.collectedCards.has(cardId);
        if (!isAccessible) {
            this.showLockedMessage();
            return;
        }

        // 수학 페르소나 시스템의 팝업 사용
        if (window.mathPersonaSystem) {
            window.mathPersonaSystem.showCardPopup(cardId);
        } else {
            // 기존 상세 모달 표시 (fallback)
            this.showDetailModal(card);
        }
    }
    
    /**
     * 카드 상세 모달 표시
     */
    showDetailModal(card) {
        const group = this.findGroupByCardId(card.id);
        const isCollected = this.collectedCards.has(card.id);
        
        const modalHTML = `
            <div id="card-detail-modal" class="card-detail-overlay">
                <div class="card-detail-container" style="--group-color: ${group.color}">
                    <button class="close-modal" onclick="biasCardCompleteLibrary.closeDetailModal()">✕</button>
                    
                    <div class="detail-header">
                        <span class="detail-number">#${String(card.id).padStart(2, '0')}</span>
                        <span class="detail-group">${group.emoji} ${group.name}</span>
                        ${!isCollected && this.userRole !== 'student' ? '<span class="teacher-viewing">👁️ 교사 열람 모드</span>' : ''}
                    </div>
                    
                    <div class="detail-content">
                        <div class="detail-icon">${this.getCardEmoji(card.id)}</div>
                        <h3 class="detail-name">${card.name}</h3>
                        <p class="detail-english">${card.english}</p>
                        <p class="detail-desc">${card.desc}</p>
                        
                        <div class="detail-info">
                            <div class="info-item">
                                <span class="info-label">희귀도</span>
                                <span class="info-value">${this.getRarityStars(this.getCardRarity(card.id))}</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">수집 상태</span>
                                <span class="info-value">${isCollected ? '✅ 수집 완료' : '🔒 미수집'}</span>
                            </div>
                        </div>
                        
                        <div class="detail-tips">
                            <h4>💡 극복 방법</h4>
                            <p>${this.getOvercomeTips(card.id)}</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        
        // 애니메이션
        setTimeout(() => {
            document.getElementById('card-detail-modal')?.classList.add('show');
        }, 10);
    }
    
    /**
     * 상세 모달 닫기
     */
    closeDetailModal() {
        const modal = document.getElementById('card-detail-modal');
        if (modal) {
            modal.classList.remove('show');
            setTimeout(() => modal.remove(), 300);
        }
    }
    
    /**
     * 잠긴 카드 메시지
     */
    showLockedMessage() {
        const message = document.createElement('div');
        message.className = 'locked-message';
        message.textContent = '🔒 이 카드는 아직 수집되지 않았습니다';
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.classList.add('show');
        }, 10);
        
        setTimeout(() => {
            message.classList.remove('show');
            setTimeout(() => message.remove(), 300);
        }, 2000);
    }
    
    /**
     * 카드가 속한 그룹 찾기
     */
    findGroupByCardId(cardId) {
        for (const [key, group] of Object.entries(this.inertiaGroups)) {
            if (group.cards.some(c => c.id === cardId)) {
                return group;
            }
        }
        return null;
    }
    
    /**
     * 극복 팁 제공
     */
    getOvercomeTips(cardId) {
        const tips = {
            1: '다양한 관점에서 정보를 수집하고, 반대 의견도 적극적으로 찾아보세요.',
            2: '의도적으로 평소 관심 없던 분야의 정보도 살펴보는 습관을 기르세요.',
            41: '최악의 상황이 실제로 일어날 확률을 객관적으로 계산해보세요.',
            51: '자신의 지식 수준을 정기적으로 점검하고 피드백을 구하세요.',
            // ... 더 많은 팁 추가 가능
        };
        
        return tips[cardId] || '이 인지관성을 인식하는 것이 극복의 첫걸음입니다. 의식적으로 다른 관점을 고려해보세요.';
    }

    /**
     * 그룹 선택
     */
    selectGroup(group) {
        this.currentGroup = group;
        
        // 탭 활성화 상태 업데이트
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.group === group);
        });
        
        this.renderCards();
    }

    /**
     * 뷰 모드 변경
     */
    changeView(view) {
        this.currentView = view;
        
        // 버튼 활성화 상태 업데이트
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === view);
        });
        
        this.renderCards();
    }

    /**
     * 검색
     */
    search(query) {
        // 검색 구현 (필요시)
        console.log('Search:', query);
    }

    /**
     * 카드 희귀도 계산
     */
    getCardRarity(cardId) {
        if (cardId <= 12) return 'common';
        if (cardId <= 27) return 'uncommon';
        if (cardId <= 50) return 'rare';
        return 'legendary';
    }

    /**
     * 희귀도 별 표시
     */
    getRarityStars(rarity) {
        const stars = {
            common: '⭐',
            uncommon: '⭐⭐',
            rare: '⭐⭐⭐',
            legendary: '⭐⭐⭐⭐'
        };
        return stars[rarity] || '⭐';
    }

    /**
     * 카드 이모지
     */
    getCardEmoji(cardId) {
        const emojis = ['🎯', '🧩', '💡', '🔍', '🎨', '🎭', '🎪', '🎰', '🎲', '🃏'];
        return emojis[cardId % 10];
    }

    /**
     * 그룹 진행도
     */
    getGroupProgress(groupKey) {
        const group = this.inertiaGroups[groupKey];
        if (!group) return 0;
        
        return group.cards.filter(card => this.collectedCards.has(card.id)).length;
    }

    /**
     * 단계 상태
     */
    getStepStatus(step) {
        // 단계별 완료 상태 계산
        const perceptionComplete = this.getGroupProgress('perception') === 12;
        const judgmentLearningComplete = 
            this.getGroupProgress('judgment') + this.getGroupProgress('learning') === 28;
        const emotionalComplete = this.getGroupProgress('emotional') === 10;
        const socialComplete = this.getGroupProgress('social') === 10;

        switch(step) {
            case 1: return perceptionComplete ? 'completed' : 'active';
            case 2: return judgmentLearningComplete ? 'completed' : (perceptionComplete ? 'active' : 'locked');
            case 3: return emotionalComplete ? 'completed' : (judgmentLearningComplete ? 'active' : 'locked');
            case 4: return socialComplete ? 'completed' : (emotionalComplete ? 'active' : 'locked');
            default: return 'locked';
        }
    }

    /**
     * ID로 카드 찾기
     */
    findCardById(cardId) {
        for (const group of Object.values(this.inertiaGroups)) {
            const card = group.cards.find(c => c.id === cardId);
            if (card) return card;
        }
        return null;
    }

    /**
     * 카드 수집
     */
    collectCard(cardId) {
        this.collectedCards.add(cardId);
        localStorage.setItem('shiningstars_collected_cards', 
            JSON.stringify(Array.from(this.collectedCards)));
        this.renderCards();
    }

    /**
     * 도감 열기
     */
    open() {
        const library = document.getElementById('complete-card-library');
        if (library) {
            library.style.display = 'flex';
            setTimeout(() => library.classList.add('show'), 10);
            this.isOpen = true;
        }
    }

    /**
     * 도감 닫기
     */
    close() {
        const library = document.getElementById('complete-card-library');
        if (library) {
            library.classList.remove('show');
            setTimeout(() => {
                library.style.display = 'none';
            }, 300);
            this.isOpen = false;
        }
    }

    /**
     * 이벤트 바인딩
     */
    bindEventListeners() {
        // ESC 키로 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });
    }
}

// 전역 인스턴스 생성
window.biasCardCompleteLibrary = new BiasCardCompleteLibrary();