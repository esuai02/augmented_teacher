/**
 * 🃏 60개 인지편향 카드 상호작용 시스템
 * 편향 감지, 학습, 수집을 위한 시각적 카드 인터페이스
 */

class BiasCardSystem {
    constructor() {
        this.allBiasCards = this.initializeBiasCards();
        this.userCollectedCards = this.loadUserProgress();
        this.activeCard = null;
        this.cardDisplayMode = 'detection'; // detection, collection, library
        
        this.init();
    }

    /**
     * 🎯 시스템 초기화
     */
    init() {
        this.setupCardContainer();
        this.bindEventListeners();
        this.loadCardStyles();
    }

    /**
     * 🃏 60개 편향 카드 데이터 초기화
     */
    initializeBiasCards() {
        return {
            // Level 1: 기초 인식편향 (20개)
            level1: {
                확증편향: {
                    id: 'confirmation_bias',
                    name: '확증편향',
                    category: 'level1',
                    cosmicSymbol: '🕳️',
                    shortDescription: '하나의 별만 보는 편향',
                    fullDescription: '자신의 기존 믿음을 확인해주는 정보만 찾고 반대 증거는 무시하는 편향',
                    cosmicMetaphor: '확증편향 블랙홀이 다른 별빛을 삼키고 있어요',
                    overcomingTips: [
                        '3가지 다른 관점에서 문제 바라보기',
                        '반대 의견도 적극적으로 찾아보기',
                        '우주에는 무수한 별자리가 있다는 것 기억하기'
                    ],
                    triggers: ['역시', '당연히', '또', '늘 그래'],
                    rarity: 'common',
                    unlocked: false,
                    collectedAt: null
                },
                자기과소평가: {
                    id: 'underconfidence_bias',
                    name: '자기과소평가',
                    category: 'level1',
                    cosmicSymbol: '🌑',
                    shortDescription: '내면의 별빛을 감추는 편향',
                    fullDescription: '자신의 능력을 실제보다 낮게 평가하여 도전을 회피하는 편향',
                    cosmicMetaphor: '자기과소평가 블랙홀이 당신의 별빛을 삼키고 있어요',
                    overcomingTips: [
                        '지금까지의 성취 별자리 돌아보기',
                        '작은 도전부터 시작하여 자신감 쌓기',
                        '실패는 새로운 별 탄생의 과정임을 기억하기'
                    ],
                    triggers: ['못하겠어', '어려워', '불가능해'],
                    rarity: 'common',
                    unlocked: false,
                    collectedAt: null
                },
                재앙화사고: {
                    id: 'catastrophizing',
                    name: '재앙화사고',
                    category: 'level1',
                    cosmicSymbol: '☄️',
                    shortDescription: '운석을 행성 충돌로 보는 편향',
                    fullDescription: '작은 문제를 큰 재앙으로 확대 해석하는 사고 패턴',
                    cosmicMetaphor: '작은 운석을 행성 충돌로 착각하고 있어요',
                    overcomingTips: [
                        '실제 확률과 영향도 객관적으로 계산하기',
                        '최악의 시나리오와 현실 구분하기',
                        '운석은 아름다운 유성우가 될 수도 있음을 기억하기'
                    ],
                    triggers: ['끝났다', '망했다', '최악', '절대'],
                    rarity: 'common',
                    unlocked: false,
                    collectedAt: null
                },
                // ... 나머지 17개 Level 1 편향들
            },
            
            // Level 2: 학습과정편향 (25개) 
            level2: {
                앵커링편향: {
                    id: 'anchoring_bias',
                    name: '앵커링편향',
                    category: 'level2',
                    cosmicSymbol: '⚓',
                    shortDescription: '첫 번째 별에만 정박하는 편향',
                    fullDescription: '최초 정보에 과도하게 의존하여 판단하는 편향',
                    cosmicMetaphor: '첫 번째 별에만 정박하고 항해를 멈췄어요',
                    overcomingTips: [
                        '여러 정보원에서 데이터 수집하기',
                        '초기 판단을 의심하고 재검토하기',
                        '우주 항해는 계속되어야 함을 기억하기'
                    ],
                    triggers: ['처음에', '첫 번째로', '일단', '우선'],
                    rarity: 'uncommon',
                    unlocked: false,
                    collectedAt: null
                },
                // ... 나머지 24개 Level 2 편향들
            },
            
            // Level 3: 고차원편향 (15개)
            level3: {
                던닝크루거효과: {
                    id: 'dunning_kruger',
                    name: '던닝크루거효과',
                    category: 'level3',
                    cosmicSymbol: '🌍',
                    shortDescription: '작은 행성에서 전 우주를 본다는 착각',
                    fullDescription: '능력이 부족한 사람이 자신의 능력을 과대평가하는 인지편향',
                    cosmicMetaphor: '작은 행성에서 전 우주를 보고 있다고 착각하고 있어요',
                    overcomingTips: [
                        '자신이 모르는 것이 무엇인지 파악하기',
                        '지속적인 학습과 피드백 받기',
                        '우주는 무한히 넓다는 겸손함 갖기'
                    ],
                    triggers: ['쉬워', '당연하지', '나는 알아', '간단해'],
                    rarity: 'rare',
                    unlocked: false,
                    collectedAt: null
                },
                // ... 나머지 14개 Level 3 편향들
            }
        };
    }

    /**
     * 🎨 편향 감지 시 카드 표시
     */
    showDetectionCard(biasName, confidence, context) {
        const card = this.findBiasCard(biasName);
        if (!card) return;

        this.cardDisplayMode = 'detection';
        this.activeCard = card;

        const cardHTML = this.generateDetectionCardHTML(card, confidence, context);
        this.displayCard(cardHTML, 'detection-card');

        // 3초 후 자동 사라짐 (긴급 개입이 아닌 경우)
        if (confidence < 0.8) {
            setTimeout(() => this.hideCard(), 3000);
        }
    }

    /**
     * 🎉 편향 극복 시 수집 카드 표시
     */
    showCollectionCard(biasName, overcomingEvidence) {
        const card = this.findBiasCard(biasName);
        if (!card || card.unlocked) return;

        // 카드 잠금해제
        card.unlocked = true;
        card.collectedAt = new Date().toISOString();
        this.userCollectedCards.push(card.id);

        this.cardDisplayMode = 'collection';
        this.activeCard = card;

        const cardHTML = this.generateCollectionCardHTML(card, overcomingEvidence);
        this.displayCard(cardHTML, 'collection-card');

        // 축하 효과
        this.triggerCollectionEffects(card);

        // 사용자 클릭 후 사라짐
        this.waitForUserAcknowledgment();
    }

    /**
     * 📚 편향 도감 카드 표시
     */
    showLibraryCard(biasId) {
        const card = this.findBiasCardById(biasId);
        if (!card) return;

        this.cardDisplayMode = 'library';
        this.activeCard = card;

        const cardHTML = this.generateLibraryCardHTML(card);
        this.displayCard(cardHTML, 'library-card');
    }

    /**
     * 🎨 감지 카드 HTML 생성
     */
    generateDetectionCardHTML(card, confidence, context) {
        const urgencyClass = confidence > 0.8 ? 'urgent' : 'gentle';
        
        return `
            <div class="bias-card detection-card ${urgencyClass}" data-bias="${card.id}">
                <div class="card-header">
                    <div class="cosmic-symbol">${card.cosmicSymbol}</div>
                    <div class="bias-name">${card.name}</div>
                    <div class="confidence-meter">
                        <div class="confidence-bar" style="width: ${confidence * 100}%"></div>
                    </div>
                </div>
                
                <div class="cosmic-metaphor">
                    ${card.cosmicMetaphor}
                </div>
                
                <div class="detection-context">
                    감지된 키워드: "${context.trigger}"
                </div>
                
                <div class="quick-tip">
                    💡 ${card.overcomingTips[0]}
                </div>
                
                <div class="card-actions">
                    <button class="learn-more-btn" onclick="biasCardSystem.showLibraryCard('${card.id}')">
                        자세히 알아보기
                    </button>
                    <button class="session-btn" onclick="biasCardSession.startSession('${card.name}')">
                        🎓 학습 세션 시작
                    </button>
                    <button class="dismiss-btn" onclick="biasCardSystem.hideCard()">
                        이해했어요
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * 🎉 수집 카드 HTML 생성
     */
    generateCollectionCardHTML(card, evidence) {
        return `
            <div class="bias-card collection-card unlocked" data-bias="${card.id}">
                <div class="collection-celebration">
                    <div class="sparkles">✨ 새로운 편향 카드 획득! ✨</div>
                </div>
                
                <div class="card-header">
                    <div class="cosmic-symbol">${card.cosmicSymbol}</div>
                    <div class="bias-name">${card.name}</div>
                    <div class="rarity-badge ${card.rarity}">${this.getRarityText(card.rarity)}</div>
                </div>
                
                <div class="card-description">
                    ${card.fullDescription}
                </div>
                
                <div class="overcoming-evidence">
                    <strong>극복 증거:</strong>
                    ${evidence.description}
                </div>
                
                <div class="cosmic-celebration">
                    🌟 ${this.getUserName()}님의 우주가 더욱 밝아졌어요!
                </div>
                
                <div class="card-actions">
                    <button class="collection-btn" onclick="biasCardSystem.acknowledgCollection()">
                        멋져요! 계속 탐험하기
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * 📚 도감 카드 HTML 생성
     */
    generateLibraryCardHTML(card) {
        const isUnlocked = card.unlocked;
        const lockClass = isUnlocked ? 'unlocked' : 'locked';
        
        return `
            <div class="bias-card library-card ${lockClass}" data-bias="${card.id}">
                <div class="card-header">
                    <div class="cosmic-symbol">${isUnlocked ? card.cosmicSymbol : '🔒'}</div>
                    <div class="bias-name">${isUnlocked ? card.name : '???'}</div>
                    <div class="category-badge">${card.category.toUpperCase()}</div>
                </div>
                
                ${isUnlocked ? `
                    <div class="card-description">
                        ${card.fullDescription}
                    </div>
                    
                    <div class="cosmic-metaphor">
                        ${card.cosmicMetaphor}
                    </div>
                    
                    <div class="overcoming-tips">
                        <h4>극복 방법:</h4>
                        <ul>
                            ${card.overcomingTips.map(tip => `<li>${tip}</li>`).join('')}
                        </ul>
                    </div>
                    
                    <div class="trigger-words">
                        <strong>감지 키워드:</strong> ${card.triggers.join(', ')}
                    </div>
                    
                    ${card.collectedAt ? `
                        <div class="collection-info">
                            ⭐ ${new Date(card.collectedAt).toLocaleDateString()}에 극복
                        </div>
                    ` : ''}
                ` : `
                    <div class="locked-content">
                        <div class="lock-message">
                            이 편향을 극복하면 카드가 잠금해제됩니다!
                        </div>
                        <div class="unlock-hint">
                            ${card.shortDescription}
                        </div>
                    </div>
                `}
                
                <div class="card-actions">
                    <button class="close-btn" onclick="biasCardSystem.hideCard()">
                        닫기
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * 🎨 카드 표시
     */
    displayCard(cardHTML, className) {
        // 기존 카드 제거
        this.hideCard();
        
        // 카드 컨테이너 생성
        const cardContainer = document.createElement('div');
        cardContainer.id = 'bias-card-overlay';
        cardContainer.className = `card-overlay ${className}`;
        cardContainer.innerHTML = `
            <div class="card-backdrop" onclick="biasCardSystem.hideCard()"></div>
            ${cardHTML}
        `;
        
        document.body.appendChild(cardContainer);
        
        // 애니메이션 효과
        setTimeout(() => {
            cardContainer.classList.add('show');
        }, 10);
    }

    /**
     * 🎯 카드 숨기기
     */
    hideCard() {
        const overlay = document.getElementById('bias-card-overlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.remove();
            }, 300);
        }
        this.activeCard = null;
    }

    /**
     * 🎉 수집 승인
     */
    acknowledgCollection() {
        if (this.activeCard) {
            this.saveUserProgress();
            this.hideCard();
            
            // 전체 컬렉션 진행도 체크
            this.checkCollectionMilestones();
        }
    }

    /**
     * 🔍 편향 카드 찾기
     */
    findBiasCard(biasName) {
        for (const level of Object.values(this.allBiasCards)) {
            if (level[biasName]) {
                return level[biasName];
            }
        }
        return null;
    }

    findBiasCardById(biasId) {
        for (const level of Object.values(this.allBiasCards)) {
            for (const card of Object.values(level)) {
                if (card.id === biasId) {
                    return card;
                }
            }
        }
        return null;
    }

    /**
     * 🎊 수집 효과
     */
    triggerCollectionEffects(card) {
        // 우주적 축하 효과
        if (window.cosmicEffects) {
            window.cosmicEffects.triggerCardCollectionEffect(card);
        }
        
        // 사운드 효과 (있다면)
        this.playCollectionSound(card.rarity);
    }

    /**
     * 📊 컬렉션 마일스톤 체크
     */
    checkCollectionMilestones() {
        const totalCards = this.getTotalCardCount();
        const collectedCount = this.userCollectedCards.length;
        const progress = collectedCount / totalCards;
        
        const milestones = [
            { threshold: 0.25, title: "편향 탐험가", message: "25% 달성! 우주 탐험이 시작되었어요!" },
            { threshold: 0.5, title: "편향 정복자", message: "50% 달성! 우주의 절반을 이해했어요!" },
            { threshold: 0.75, title: "편향 마스터", message: "75% 달성! 우주의 대부분을 정복했어요!" },
            { threshold: 1.0, title: "편향 그랜드마스터", message: "100% 달성! 우주의 모든 편향을 극복했어요!" }
        ];
        
        for (const milestone of milestones) {
            if (progress >= milestone.threshold && !this.hasAchievedMilestone(milestone.title)) {
                this.showMilestoneAchievement(milestone);
                break;
            }
        }
    }

    /**
     * 💾 사용자 진행도 저장
     */
    saveUserProgress() {
        localStorage.setItem('shiningstars_bias_cards', JSON.stringify({
            collectedCards: this.userCollectedCards,
            lastUpdate: new Date().toISOString()
        }));
    }

    loadUserProgress() {
        const saved = localStorage.getItem('shiningstars_bias_cards');
        if (saved) {
            const data = JSON.parse(saved);
            return data.collectedCards || [];
        }
        return [];
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    getTotalCardCount() {
        let total = 0;
        for (const level of Object.values(this.allBiasCards)) {
            total += Object.keys(level).length;
        }
        return total;
    }

    getRarityText(rarity) {
        const rarityTexts = {
            common: '일반',
            uncommon: '고급',
            rare: '희귀',
            legendary: '전설'
        };
        return rarityTexts[rarity] || '일반';
    }

    getUserName() {
        return "우주 탐험가"; // TODO: 실제 사용자 이름
    }

    /**
     * 🎨 카드 스타일 로드
     */
    loadCardStyles() {
        if (!document.getElementById('bias-card-styles')) {
            const styles = document.createElement('style');
            styles.id = 'bias-card-styles';
            styles.textContent = this.getCardCSS();
            document.head.appendChild(styles);
        }
    }

    getCardCSS() {
        return `
            .card-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 10000;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .card-overlay.show {
                opacity: 1;
            }
            
            .card-backdrop {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
            }
            
            .bias-card {
                position: relative;
                background: linear-gradient(135deg, #1a1a2e, #16213e);
                border: 2px solid #0f3460;
                border-radius: 20px;
                padding: 25px;
                max-width: 500px;
                color: white;
                box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
                transform: scale(0.8);
                transition: transform 0.3s ease;
            }
            
            .card-overlay.show .bias-card {
                transform: scale(1);
            }
            
            .detection-card.urgent {
                border-color: #ff6b6b;
                box-shadow: 0 0 30px rgba(255, 107, 107, 0.5);
                animation: urgentPulse 2s infinite;
            }
            
            @keyframes urgentPulse {
                0%, 100% { box-shadow: 0 0 30px rgba(255, 107, 107, 0.5); }
                50% { box-shadow: 0 0 50px rgba(255, 107, 107, 0.8); }
            }
            
            .collection-card {
                border-color: #ffd700;
                box-shadow: 0 0 40px rgba(255, 215, 0, 0.6);
            }
            
            .card-header {
                display: flex;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .cosmic-symbol {
                font-size: 3em;
                margin-right: 15px;
            }
            
            .bias-name {
                font-size: 1.5em;
                font-weight: bold;
                flex: 1;
            }
            
            .confidence-meter {
                width: 100px;
                height: 8px;
                background: rgba(255, 255, 255, 0.2);
                border-radius: 4px;
                overflow: hidden;
            }
            
            .confidence-bar {
                height: 100%;
                background: linear-gradient(90deg, #4ade80, #facc15, #f87171);
                transition: width 0.5s ease;
            }
            
            .cosmic-metaphor {
                font-style: italic;
                color: #64b5f6;
                margin: 15px 0;
                padding: 10px;
                background: rgba(100, 181, 246, 0.1);
                border-radius: 10px;
            }
            
            .card-actions {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            
            .card-actions button {
                flex: 1;
                padding: 12px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s ease;
            }
            
            .learn-more-btn {
                background: #2196f3;
                color: white;
            }
            
            .learn-more-btn:hover {
                background: #1976d2;
                transform: translateY(-2px);
            }
            
            .collection-btn {
                background: #ffd700;
                color: #1a1a2e;
            }
            
            .collection-btn:hover {
                background: #ffed4e;
                transform: translateY(-2px);
            }
            
            .sparkles {
                text-align: center;
                font-size: 1.2em;
                margin-bottom: 15px;
                animation: sparkle 1.5s ease-in-out infinite;
            }
            
            @keyframes sparkle {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.8; transform: scale(1.05); }
            }
        `;
    }

    /**
     * 🎮 편향 감지 시스템과 연동
     */
    setupBiasDetectionIntegration() {
        // 편향 감지 시스템에서 카드 표시 요청 받기
        document.addEventListener('biasDetected', (event) => {
            const { biasName, confidence, context } = event.detail;
            this.showDetectionCard(biasName, confidence, context);
        });

        // 편향 극복 시 카드 수집
        document.addEventListener('biasOvercome', (event) => {
            const { biasName, evidence } = event.detail;
            this.showCollectionCard(biasName, evidence);
        });
    }

    /**
     * 🎪 이벤트 리스너 설정
     */
    bindEventListeners() {
        this.setupBiasDetectionIntegration();
        
        // ESC 키로 카드 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeCard) {
                this.hideCard();
            }
        });
    }
}

// 전역 인스턴스 생성
window.biasCardSystem = new BiasCardSystem();