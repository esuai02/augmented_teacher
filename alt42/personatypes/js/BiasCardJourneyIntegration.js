/**
 * 인지관성 카드와 여정 통합 시스템
 * 60개 페르소나 카드를 여정에 체계적으로 통합
 */

class InertiaCardJourneyIntegration {
    constructor() {
        this.nodeCardMapping = {
            0: ['확증관성', '선택적주의'],           // 시작 - 인식 관성
            1: ['앵커링관성', '가용성휴리스틱'],     // 계산 - 판단 관성
            2: ['프레이밍효과', '대표성휴리스틱'],   // 도형 - 인식 관성
            3: ['과신관성', '계획오류'],             // 연산 - 판단 관성
            4: ['자기과소평가', '완벽주의'],         // 전략 - 학습 관성
            5: ['패턴인식관성', '과일반화'],         // 패턴 - 감정 관성
            6: ['확실성효과', '통제착각'],           // 깨달음 - 판단 관성
            7: ['재앙화사고', '흑백사고'],           // 예측 - 감정 관성
            8: ['더닝크루거효과', '관성맹점']        // 마스터리 - 사회적 관성
        };
        
        this.collectedCards = new Set();
        this.masteredCards = new Set();
        this.cardProgress = {};
        
        this.init();
    }
    
    /**
     * 초기화
     */
    init() {
        this.loadProgress();
        this.setupEventListeners();
        this.initializeCardDisplay();
        
        console.log('🃏 인지관성 카드 여정 통합 시스템 준비 완료');
    }
    
    /**
     * 노드 완료 시 카드 처리
     */
    async processNodeCompletion(nodeId, answer, feedback) {
        console.log(`🎯 노드 ${nodeId} 완료 처리 시작`);
        
        // 해당 노드의 잠재 카드
        const potentialCards = this.nodeCardMapping[nodeId] || [];
        
        // 카드 획득 판정
        const acquiredCards = this.evaluateCardAcquisition(
            nodeId, 
            answer, 
            feedback.detectedInertias || []
        );
        
        // 획득한 카드 처리
        for (const card of acquiredCards) {
            await this.acquireCard(card, nodeId);
        }
        
        // 카드 획득 알림
        if (acquiredCards.length > 0) {
            this.showCardAcquisitionNotification(acquiredCards);
        }
        
        // 진행 상황 저장
        this.saveProgress();
        
        return acquiredCards;
    }
    
    /**
     * 카드 획득 평가
     */
    evaluateCardAcquisition(nodeId, answer, detectedInertias) {
        const potentialCards = this.nodeCardMapping[nodeId] || [];
        const acquiredCards = [];
        
        // 답변 품질 평가
        const qualityMetrics = {
            length: Math.min(answer.length / 100, 3),
            specificity: this.evaluateSpecificity(answer),
            reflection: this.evaluateReflection(answer),
            inertiaAwareness: detectedInertias.length > 0 ? 1 : 0
        };
        
        const totalScore = Object.values(qualityMetrics).reduce((a, b) => a + b, 0);
        
        // 점수에 따른 카드 획득
        if (totalScore >= 5) {
            // 우수: 모든 카드 획득
            acquiredCards.push(...potentialCards);
        } else if (totalScore >= 3) {
            // 양호: 첫 번째 카드 획득
            if (potentialCards[0]) {
                acquiredCards.push(potentialCards[0]);
            }
        } else if (totalScore >= 2 && Math.random() > 0.5) {
            // 보통: 50% 확률로 첫 번째 카드
            if (potentialCards[0]) {
                acquiredCards.push(potentialCards[0]);
            }
        }
        
        return acquiredCards.filter(card => !this.collectedCards.has(card));
    }
    
    /**
     * 구체성 평가
     */
    evaluateSpecificity(answer) {
        const specificityKeywords = [
            '예를 들어', '예시', '경험', '했을 때', '기억',
            '구체적으로', '실제로', '상황', '때문에', '결과'
        ];
        
        let score = 0;
        for (const keyword of specificityKeywords) {
            if (answer.includes(keyword)) {
                score += 0.5;
            }
        }
        
        return Math.min(score, 3);
    }
    
    /**
     * 성찰 수준 평가
     */
    evaluateReflection(answer) {
        const reflectionKeywords = [
            '생각해보니', '깨달았', '느꼈', '알게 되었',
            '이해했', '배웠', '성장', '변화', '발전'
        ];
        
        let score = 0;
        for (const keyword of reflectionKeywords) {
            if (answer.includes(keyword)) {
                score += 0.5;
            }
        }
        
        return Math.min(score, 3);
    }
    
    /**
     * 카드 획득 처리
     */
    async acquireCard(cardName, nodeId) {
        if (this.collectedCards.has(cardName)) {
            return; // 이미 획득한 카드
        }
        
        this.collectedCards.add(cardName);
        
        // 카드 진행 상황 초기화
        this.cardProgress[cardName] = {
            collected: true,
            stage: 0,
            nodeId: nodeId,
            collectedAt: new Date().toISOString()
        };
        
        // 카드 획득 이벤트 발생
        this.triggerCardEvent('acquired', cardName);
        
        // 카드 애니메이션
        await this.playCardAnimation(cardName);
        
        console.log(`🎴 카드 획득: ${cardName}`);
    }
    
    /**
     * 카드 획득 알림
     */
    showCardAcquisitionNotification(cards) {
        const container = document.createElement('div');
        container.className = 'card-acquisition-notification';
        container.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #667eea, #764ba2);
            padding: 30px;
            border-radius: 20px;
            color: white;
            z-index: 10000;
            text-align: center;
            animation: cardNotificationPulse 0.5s ease;
        `;
        
        container.innerHTML = `
            <h2 style="margin: 0 0 20px 0; font-size: 2em;">
                🎉 인지관성 카드 획득!
            </h2>
            <div class="acquired-cards">
                ${cards.map(card => `
                    <div class="card-item" style="
                        background: rgba(255,255,255,0.2);
                        padding: 15px;
                        margin: 10px;
                        border-radius: 10px;
                        font-size: 1.2em;
                    ">
                        🃏 ${card}
                    </div>
                `).join('')}
            </div>
            <button onclick="this.parentElement.remove()" style="
                margin-top: 20px;
                padding: 10px 30px;
                background: white;
                color: #667eea;
                border: none;
                border-radius: 10px;
                font-weight: bold;
                cursor: pointer;
            ">확인</button>
        `;
        
        document.body.appendChild(container);
        
        // 자동 제거
        setTimeout(() => {
            if (container.parentElement) {
                container.remove();
            }
        }, 5000);
    }
    
    /**
     * 카드 애니메이션
     */
    async playCardAnimation(cardName) {
        // 카드 획득 파티클 효과
        const particles = document.createElement('div');
        particles.className = 'card-particles';
        particles.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 9999;
        `;
        
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.innerHTML = '✨';
            particle.style.cssText = `
                position: absolute;
                animation: particleFly 1s ease-out forwards;
                animation-delay: ${Math.random() * 0.5}s;
                font-size: ${Math.random() * 20 + 10}px;
            `;
            particles.appendChild(particle);
        }
        
        document.body.appendChild(particles);
        
        setTimeout(() => particles.remove(), 2000);
    }
    
    /**
     * 편향 학습 세션 시작
     */
    startLearningSession(cardName) {
        if (!this.collectedCards.has(cardName)) {
            console.error(`카드 ${cardName}를 아직 획득하지 않았습니다.`);
            return;
        }
        
        const progress = this.cardProgress[cardName];
        const currentStage = progress.stage || 0;
        
        // 6단계 학습 프로그램 시작
        this.launchInertiaLearningProgram(cardName, currentStage);
    }
    
    /**
     * 인지관성 학습 프로그램 실행
     */
    launchInertiaLearningProgram(cardName, stage) {
        const learningStages = [
            { title: '인식', description: '인지관성 알아보기' },
            { title: '이해', description: '작동 원리 파악' },
            { title: '발견', description: '일상에서 찾기' },
            { title: '연습', description: '극복 연습하기' },
            { title: '적용', description: '실생활 적용' },
            { title: '마스터', description: '완전 극복' }
        ];
        
        const currentStageInfo = learningStages[stage];
        
        // 학습 세션 UI 생성
        const sessionUI = document.createElement('div');
        sessionUI.className = 'learning-session';
        sessionUI.innerHTML = `
            <div class="session-header">
                <h2>🎓 ${cardName} 학습</h2>
                <p>단계 ${stage + 1}/6: ${currentStageInfo.title}</p>
            </div>
            <div class="session-content">
                <p>${currentStageInfo.description}</p>
                <!-- 단계별 컨텐츠 로드 -->
            </div>
            <div class="session-actions">
                <button onclick="inertiaCardJourney.completeStage('${cardName}', ${stage})">
                    완료
                </button>
            </div>
        `;
        
        // 세션 표시
        document.getElementById('contentPanel').innerHTML = '';
        document.getElementById('contentPanel').appendChild(sessionUI);
    }
    
    /**
     * 학습 단계 완료
     */
    completeStage(cardName, stage) {
        const progress = this.cardProgress[cardName];
        progress.stage = stage + 1;
        
        if (progress.stage >= 6) {
            // 카드 마스터
            this.masterCard(cardName);
        } else {
            // 다음 단계로
            this.launchInertiaLearningProgram(cardName, progress.stage);
        }
        
        this.saveProgress();
    }
    
    /**
     * 카드 마스터
     */
    masterCard(cardName) {
        this.masteredCards.add(cardName);
        
        // 마스터 축하 효과
        this.showMasteryNotification(cardName);
        
        // 성취 기록
        this.recordAchievement('inertia_master', cardName);
        
        console.log(`🏆 카드 마스터: ${cardName}`);
    }
    
    /**
     * 마스터 알림
     */
    showMasteryNotification(cardName) {
        const notification = document.createElement('div');
        notification.className = 'mastery-notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ffd700, #ff6b6b);
            padding: 20px;
            border-radius: 15px;
            color: white;
            font-weight: bold;
            z-index: 10000;
            animation: slideInRight 0.5s ease;
        `;
        
        notification.innerHTML = `
            <div style="font-size: 1.5em; margin-bottom: 10px;">
                🏆 인지관성 마스터!
            </div>
            <div>${cardName}를 완전히 극복했습니다!</div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => notification.remove(), 5000);
    }
    
    /**
     * 진행 상황 저장
     */
    saveProgress() {
        const progress = {
            collectedCards: Array.from(this.collectedCards),
            masteredCards: Array.from(this.masteredCards),
            cardProgress: this.cardProgress,
            lastUpdate: new Date().toISOString()
        };
        
        localStorage.setItem('inertiaCardJourneyProgress', JSON.stringify(progress));
    }
    
    /**
     * 진행 상황 로드
     */
    loadProgress() {
        const saved = localStorage.getItem('inertiaCardJourneyProgress');
        if (saved) {
            const progress = JSON.parse(saved);
            this.collectedCards = new Set(progress.collectedCards || []);
            this.masteredCards = new Set(progress.masteredCards || []);
            this.cardProgress = progress.cardProgress || {};
        }
    }
    
    /**
     * 카드 디스플레이 초기화
     */
    initializeCardDisplay() {
        // 수집한 카드 수 표시
        this.updateCardCount();
        
        // 카드 도감 버튼 활성화
        this.setupCardLibraryButton();
    }
    
    /**
     * 카드 수 업데이트
     */
    updateCardCount() {
        const countElement = document.getElementById('cardsCount');
        if (countElement) {
            countElement.textContent = this.collectedCards.size;
        }
    }
    
    /**
     * 카드 도감 버튼 설정
     */
    setupCardLibraryButton() {
        const button = document.querySelector('.tool-btn[onclick*="inertiaCardLibrary"]');
        if (button) {
            // 수집한 카드가 있으면 반짝이는 효과
            if (this.collectedCards.size > 0) {
                button.style.animation = 'pulse 2s infinite';
            }
        }
    }
    
    /**
     * 이벤트 리스너 설정
     */
    setupEventListeners() {
        // 카드 이벤트 리스너
        document.addEventListener('cardAcquired', (e) => {
            this.updateCardCount();
        });
        
        // 노드 완료 이벤트 리스너
        document.addEventListener('nodeCompleted', (e) => {
            const { nodeId, answer, feedback } = e.detail;
            this.processNodeCompletion(nodeId, answer, feedback);
        });
    }
    
    /**
     * 카드 이벤트 트리거
     */
    triggerCardEvent(type, cardName) {
        const event = new CustomEvent(`card${type}`, {
            detail: { cardName, timestamp: Date.now() }
        });
        document.dispatchEvent(event);
    }
    
    /**
     * 성취 기록
     */
    recordAchievement(type, data) {
        const achievement = {
            type: type,
            data: data,
            timestamp: new Date().toISOString()
        };
        
        // TODO: 서버로 전송
        console.log('🏅 성취 기록:', achievement);
    }
    
    /**
     * 통계 생성
     */
    getStatistics() {
        return {
            totalCards: 60,
            collected: this.collectedCards.size,
            mastered: this.masteredCards.size,
            inProgress: this.collectedCards.size - this.masteredCards.size,
            collectionRate: (this.collectedCards.size / 60 * 100).toFixed(1) + '%',
            masteryRate: (this.masteredCards.size / 60 * 100).toFixed(1) + '%'
        };
    }
}

// 전역 인스턴스 생성
window.inertiaCardJourney = new InertiaCardJourneyIntegration();

// CSS 애니메이션 추가
const style = document.createElement('style');
style.textContent = `
@keyframes cardNotificationPulse {
    0% { transform: translate(-50%, -50%) scale(0.8); opacity: 0; }
    50% { transform: translate(-50%, -50%) scale(1.1); }
    100% { transform: translate(-50%, -50%) scale(1); opacity: 1; }
}

@keyframes particleFly {
    0% { 
        transform: translate(0, 0) scale(1);
        opacity: 1;
    }
    100% { 
        transform: translate(
            ${Math.random() * 200 - 100}px,
            ${Math.random() * 200 - 100}px
        ) scale(0);
        opacity: 0;
    }
}

@keyframes slideInRight {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
`;
document.head.appendChild(style);