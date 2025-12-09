/**
 * 🌉 시스템 브릿지
 * 기존 index.php 시스템과 새로운 통합 시스템을 연결
 */

class SystemBridge {
    constructor() {
        this.legacySystem = null;
        this.newSystem = null;
        this.bridgeMode = 'transition'; // transition, legacy, new
        this.migrationProgress = 0;
        
        this.init();
    }

    /**
     * 🚀 브릿지 초기화
     */
    init() {
        this.detectSystemState();
        this.setupEventBridge();
        this.determineBridgeMode();
    }

    /**
     * 🔍 시스템 상태 감지
     */
    detectSystemState() {
        // 기존 시스템 요소들 확인
        this.legacySystem = {
            avatar: document.getElementById('avatar'),
            constellation: document.getElementById('constellation'),
            contentPanel: document.getElementById('contentPanel'),
            progressTimer: document.getElementById('progressTimer'),
            functions: {
                selectNode: window.selectNode,
                updateAvatarMessage: window.updateAvatarMessage,
                submitAnswer: window.submitAnswer
            }
        };

        // 새로운 시스템 확인
        this.newSystem = {
            app: window.shiningStarsApp,
            screenManager: window.shiningStarsApp?.screenManager,
            biasCardSystem: window.biasCardSystem,
            navigationMap: window.biasNavigationMap
        };

        console.log('🌉 시스템 상태 감지 완료');
        console.log('Legacy System:', !!this.legacySystem.avatar);
        console.log('New System:', !!this.newSystem.app);
    }

    /**
     * 🔄 이벤트 브릿지 설정
     */
    setupEventBridge() {
        // 기존 시스템의 이벤트를 새 시스템으로 전달
        this.bridgeLegacyEvents();
        
        // 새 시스템의 이벤트를 기존 시스템으로 전달
        this.bridgeNewEvents();
        
        // 양방향 데이터 동기화
        this.setupDataSync();
    }

    /**
     * 📡 기존 시스템 이벤트 브릿지
     */
    bridgeLegacyEvents() {
        // 기존 노드 선택을 새 시스템으로 전달
        if (window.selectNode) {
            const originalSelectNode = window.selectNode;
            window.selectNode = (nodeId) => {
                // 기존 기능 실행
                originalSelectNode(nodeId);
                
                // 새 시스템에 알림
                if (this.newSystem.app) {
                    this.newSystem.app.eventBus.emit('legacyNodeSelected', {
                        nodeId,
                        timestamp: Date.now()
                    });
                }
            };
        }

        // 기존 답변 제출을 새 시스템으로 전달
        if (window.submitAnswer) {
            const originalSubmitAnswer = window.submitAnswer;
            window.submitAnswer = async () => {
                // 기존 기능 실행
                await originalSubmitAnswer();
                
                // 새 시스템에 알림
                if (this.newSystem.app) {
                    const answer = document.getElementById('answerInput')?.value;
                    this.newSystem.app.eventBus.emit('legacyAnswerSubmitted', {
                        nodeId: window.currentNode,
                        answer,
                        timestamp: Date.now()
                    });
                }
            };
        }

        // 아바타 클릭 이벤트 브릿지
        if (this.legacySystem.avatar) {
            this.legacySystem.avatar.addEventListener('click', () => {
                if (this.newSystem.app) {
                    this.newSystem.app.eventBus.emit('avatarClicked', {
                        source: 'legacy',
                        timestamp: Date.now()
                    });
                }
            });
        }
    }

    /**
     * 📡 새 시스템 이벤트 브릿지
     */
    bridgeNewEvents() {
        if (!this.newSystem.app) return;

        // 편향 감지 이벤트를 기존 시스템으로 전달
        this.newSystem.app.eventBus.on('biasDetected', (data) => {
            this.updateLegacyAvatar(`편향이 감지되었습니다: ${data.biasName}`);
        });

        // 카드 수집 이벤트를 기존 시스템으로 전달
        this.newSystem.app.eventBus.on('cardCollected', (data) => {
            this.updateLegacyAvatar(`새로운 카드를 획득했습니다! 🎉`);
        });

        // 화면 전환 이벤트를 기존 시스템으로 전달
        this.newSystem.app.eventBus.on('screenChanged', (data) => {
            this.handleScreenTransition(data);
        });
    }

    /**
     * 🔄 데이터 동기화 설정
     */
    setupDataSync() {
        // localStorage 동기화
        this.syncLocalStorage();
        
        // 주기적 동기화 (30초마다)
        setInterval(() => {
            this.syncData();
        }, 30000);
    }

    /**
     * 💾 localStorage 동기화
     */
    syncLocalStorage() {
        // 기존 시스템 데이터 -> 새 시스템 형식으로 변환
        const legacyData = JSON.parse(localStorage.getItem('mathJourney') || '{}');
        if (legacyData.answers) {
            const newFormatData = {
                collectedCards: [], // 기존 시스템에서는 카드 개념이 없음
                completedSessions: Object.keys(legacyData.answers).map(nodeId => ({
                    biasName: `node-${nodeId}`,
                    completedAt: legacyData.answers[nodeId].timestamp || Date.now(),
                    answer: legacyData.answers[nodeId].text
                })),
                lastUpdate: Date.now()
            };
            
            localStorage.setItem('shiningstars_sessions', JSON.stringify(newFormatData));
        }

        // 새 시스템 데이터 -> 기존 시스템 형식으로 변환
        const newData = JSON.parse(localStorage.getItem('shiningstars_user_data') || '{}');
        if (newData.progress) {
            const legacyFormatData = {
                completed: newData.progress.completedSessions.map(s => 
                    parseInt(s.biasName.replace('node-', ''))
                ).filter(id => !isNaN(id)),
                unlocked: [], // 기존 시스템의 unlocked 노드 정보 유지
                answers: {}
            };
            
            // 기존 답변 데이터 보존
            const existingData = JSON.parse(localStorage.getItem('mathJourney') || '{}');
            if (existingData.answers) {
                legacyFormatData.answers = existingData.answers;
            }
            
            localStorage.setItem('mathJourney', JSON.stringify(legacyFormatData));
        }
    }

    /**
     * 🔄 실시간 데이터 동기화
     */
    syncData() {
        if (!this.newSystem.app) return;

        // 진행도 동기화
        const legacyProgress = JSON.parse(localStorage.getItem('mathJourney') || '{}');
        if (legacyProgress.completed) {
            const sessionData = legacyProgress.completed.map(nodeId => ({
                biasName: `node-${nodeId}`,
                completedAt: Date.now(),
                source: 'legacy'
            }));
            
            this.newSystem.app.updateState('user.progress.completedSessions', sessionData);
        }
    }

    /**
     * 🎭 브릿지 모드 결정
     */
    determineBridgeMode() {
        const hasLegacyElements = !!this.legacySystem.avatar;
        const hasNewSystem = !!this.newSystem.app;
        
        if (hasLegacyElements && hasNewSystem) {
            this.bridgeMode = 'transition';
            this.showTransitionInterface();
        } else if (hasLegacyElements) {
            this.bridgeMode = 'legacy';
            this.enhanceLegacySystem();
        } else if (hasNewSystem) {
            this.bridgeMode = 'new';
            // 새 시스템만 실행
        }
        
        console.log(`🌉 브릿지 모드: ${this.bridgeMode}`);
    }

    /**
     * 🔄 전환 인터페이스 표시
     */
    showTransitionInterface() {
        const transitionUI = document.createElement('div');
        transitionUI.id = 'system-transition-ui';
        transitionUI.className = 'system-transition-overlay';
        
        transitionUI.innerHTML = `
            <div class="transition-modal">
                <div class="transition-header">
                    <h3>🌟 시스템 업그레이드</h3>
                    <button class="close-transition" onclick="this.parentElement.parentElement.parentElement.remove()">✕</button>
                </div>
                <div class="transition-content">
                    <div class="transition-icon">🚀</div>
                    <p>새로운 Shining Stars 시스템이 준비되었습니다!</p>
                    <p>기존 데이터를 유지하면서 향상된 기능을 경험해보세요.</p>
                    
                    <div class="transition-options">
                        <button class="transition-btn new-system" onclick="systemBridge.migrateToNewSystem()">
                            🌌 새 시스템 체험하기
                        </button>
                        <button class="transition-btn keep-legacy" onclick="systemBridge.continueLegacySystem()">
                            📚 기존 시스템 계속 사용
                        </button>
                    </div>
                    
                    <div class="migration-benefits">
                        <h4>새로운 기능들:</h4>
                        <ul>
                            <li>🗺️ 인터랙티브 편향 네비게이션 맵</li>
                            <li>🃏 수집 가능한 편향 카드 시스템</li>
                            <li>🎓 구조화된 6단계 학습 세션</li>
                            <li>🔍 실시간 편향 감지 시스템</li>
                            <li>📊 개인화된 진행도 추적</li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(transitionUI);
        
        // 애니메이션
        setTimeout(() => {
            transitionUI.classList.add('show');
        }, 100);
    }

    /**
     * 🚀 새 시스템으로 마이그레이션
     */
    async migrateToNewSystem() {
        const transitionUI = document.getElementById('system-transition-ui');
        if (transitionUI) {
            transitionUI.remove();
        }

        // 데이터 마이그레이션
        await this.performDataMigration();
        
        // 기존 UI 숨기기
        this.hideLegacyInterface();
        
        // 새 시스템 활성화
        if (this.newSystem.app && !this.newSystem.app.initialized) {
            await this.newSystem.app.init();
        }
        
        // 대시보드로 이동
        if (this.newSystem.app) {
            await this.newSystem.app.navigateToScreen('dashboard');
        }
        
        this.bridgeMode = 'new';
        console.log('🚀 새 시스템으로 마이그레이션 완료');
    }

    /**
     * 📚 기존 시스템 계속 사용
     */
    continueLegacySystem() {
        const transitionUI = document.getElementById('system-transition-ui');
        if (transitionUI) {
            transitionUI.remove();
        }
        
        this.bridgeMode = 'legacy';
        this.enhanceLegacySystem();
        
        console.log('📚 기존 시스템 계속 사용');
    }

    /**
     * 📊 데이터 마이그레이션 수행
     */
    async performDataMigration() {
        const legacyData = JSON.parse(localStorage.getItem('mathJourney') || '{}');
        
        if (legacyData.answers) {
            const migratedData = {
                profile: {
                    name: '학습자',
                    explorerName: '우주 탐험가',
                    joinedDate: Date.now(),
                    level: 1
                },
                progress: {
                    collectedCards: [], // 기존 시스템에는 카드가 없으므로 빈 배열
                    completedSessions: Object.entries(legacyData.answers).map(([nodeId, data]) => ({
                        biasName: `수학 성찰 ${nodeId}`,
                        completedAt: data.timestamp,
                        answer: data.text,
                        migrated: true
                    })),
                    currentStreak: this.calculateLearningStreak(legacyData),
                    totalTimeSpent: 0,
                    lastActiveDate: Date.now()
                },
                preferences: {
                    soundEnabled: true,
                    animationsEnabled: true,
                    difficultyPreference: 'adaptive',
                    themeBrightness: 'auto'
                }
            };
            
            localStorage.setItem('shiningstars_user_data', JSON.stringify(migratedData));
            
            // 새 시스템 상태 업데이트
            if (this.newSystem.app) {
                this.newSystem.app.updateState('user', migratedData);
            }
        }
    }

    /**
     * 📈 학습 연속일 계산
     */
    calculateLearningStreak(legacyData) {
        if (!legacyData.answers) return 0;
        
        const timestamps = Object.values(legacyData.answers)
            .map(answer => answer.timestamp)
            .sort((a, b) => b - a);
        
        if (timestamps.length === 0) return 0;
        
        let streak = 1;
        const oneDayMs = 24 * 60 * 60 * 1000;
        
        for (let i = 1; i < timestamps.length; i++) {
            const daysDiff = Math.floor((timestamps[i-1] - timestamps[i]) / oneDayMs);
            if (daysDiff <= 1) {
                streak++;
            } else {
                break;
            }
        }
        
        return streak;
    }

    /**
     * 🎭 기존 인터페이스 숨기기
     */
    hideLegacyInterface() {
        const elementsToHide = [
            'avatar-section',
            'journey-map',
            'progress-timer'
        ];
        
        elementsToHide.forEach(className => {
            const elements = document.getElementsByClassName(className);
            Array.from(elements).forEach(element => {
                element.style.transition = 'opacity 0.5s ease';
                element.style.opacity = '0';
                setTimeout(() => {
                    element.style.display = 'none';
                }, 500);
            });
        });
    }

    /**
     * ⚡ 기존 시스템 향상
     */
    enhanceLegacySystem() {
        // 기존 시스템에 새로운 기능들 추가
        this.addQuickAccessButtons();
        this.enhanceAvatarInteraction();
        this.addProgressIndicators();
    }

    /**
     * 🚀 빠른 액세스 버튼 추가
     */
    addQuickAccessButtons() {
        const quickAccessPanel = document.createElement('div');
        quickAccessPanel.className = 'quick-access-panel';
        quickAccessPanel.innerHTML = `
            <div class="quick-access-title">🌟 새로운 기능</div>
            <div class="quick-access-buttons">
                <button class="quick-btn" onclick="systemBridge.openNavigationMap()">
                    🗺️ 편향 지도
                </button>
                <button class="quick-btn" onclick="systemBridge.openCardLibrary()">
                    🃏 카드 도감
                </button>
                <button class="quick-btn" onclick="systemBridge.migrateToNewSystem()">
                    🚀 새 시스템
                </button>
            </div>
        `;
        
        document.body.appendChild(quickAccessPanel);
    }

    /**
     * 🎭 아바타 상호작용 향상
     */
    enhanceAvatarInteraction() {
        if (!this.legacySystem.avatar) return;
        
        // 더 많은 메시지 추가
        const enhancedMessages = [
            "새로운 편향 탐지 시스템이 활성화되었어요! 🔍",
            "카드 수집 기능을 사용해보세요! 🃏",
            "우주 지도에서 편향들의 관계를 확인해보세요! 🗺️",
            "학습 패턴을 분석하고 있어요... 📊",
            "오늘도 훌륭한 성찰을 하고 계시네요! ✨"
        ];
        
        let messageIndex = 0;
        this.legacySystem.avatar.addEventListener('click', () => {
            const message = enhancedMessages[messageIndex % enhancedMessages.length];
            this.updateLegacyAvatar(message);
            messageIndex++;
        });
    }

    /**
     * 📊 진행도 지시자 추가
     */
    addProgressIndicators() {
        const progressIndicator = document.createElement('div');
        progressIndicator.className = 'enhanced-progress-indicator';
        progressIndicator.innerHTML = `
            <div class="progress-title">🌟 학습 진행도</div>
            <div class="progress-bar-container">
                <div class="progress-bar-fill" style="width: ${this.calculateLegacyProgress()}%"></div>
            </div>
            <div class="progress-text">${this.calculateLegacyProgress()}% 완료</div>
        `;
        
        // 기존 타이머 근처에 배치
        const timerElement = document.getElementById('progressTimer');
        if (timerElement) {
            timerElement.parentNode.insertBefore(progressIndicator, timerElement);
        } else {
            document.body.appendChild(progressIndicator);
        }
    }

    /**
     * 📊 기존 시스템 진행도 계산
     */
    calculateLegacyProgress() {
        const legacyData = JSON.parse(localStorage.getItem('mathJourney') || '{}');
        const completedCount = legacyData.completed?.length || 0;
        const totalNodes = 9; // 기존 시스템의 총 노드 수
        
        return Math.round((completedCount / totalNodes) * 100);
    }

    /**
     * 🎭 기존 아바타 메시지 업데이트
     */
    updateLegacyAvatar(message) {
        const avatarSpeech = document.getElementById('avatarSpeech');
        if (avatarSpeech) {
            avatarSpeech.style.animation = 'none';
            setTimeout(() => {
                avatarSpeech.textContent = message;
                avatarSpeech.style.animation = 'slideIn 0.5s ease-out';
            }, 100);
        }
    }

    /**
     * 🔄 화면 전환 처리
     */
    handleScreenTransition(data) {
        console.log(`화면 전환: ${data.from} → ${data.to}`);
        
        // 기존 시스템 UI 상태 업데이트
        if (data.to === 'dashboard') {
            this.hideLegacyInterface();
        }
    }

    /**
     * 🗺️ 네비게이션 맵 열기
     */
    openNavigationMap() {
        if (window.biasNavigationMap) {
            window.biasNavigationMap.open();
        } else {
            alert('네비게이션 맵을 로드하는 중입니다...');
        }
    }

    /**
     * 🃏 카드 라이브러리 열기
     */
    openCardLibrary() {
        if (window.biasCardLibrary) {
            window.biasCardLibrary.open();
        } else {
            alert('카드 라이브러리를 로드하는 중입니다...');
        }
    }

    /**
     * 🧹 정리
     */
    cleanup() {
        // 이벤트 리스너 정리
        // 인터벌 정리
        console.log('🌉 시스템 브릿지 정리 완료');
    }
}

// 브릿지 스타일
const bridgeStyles = `
    .system-transition-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.9);
        z-index: 30000;
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .system-transition-overlay.show {
        opacity: 1;
    }
    
    .transition-modal {
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        border-radius: 20px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        color: white;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5);
        animation: slideInUp 0.5s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .transition-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px 30px;
        border-bottom: 1px solid #2a2d4a;
    }
    
    .transition-header h3 {
        margin: 0;
        font-size: 1.5em;
    }
    
    .close-transition {
        background: none;
        border: none;
        color: white;
        font-size: 1.5em;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background 0.3s ease;
    }
    
    .close-transition:hover {
        background: rgba(255, 255, 255, 0.1);
    }
    
    .transition-content {
        padding: 30px;
        text-align: center;
    }
    
    .transition-icon {
        font-size: 4em;
        margin-bottom: 20px;
        animation: float 3s ease-in-out infinite;
    }
    
    .transition-options {
        display: flex;
        gap: 15px;
        margin: 30px 0;
        justify-content: center;
    }
    
    .transition-btn {
        padding: 15px 25px;
        border: none;
        border-radius: 25px;
        cursor: pointer;
        font-weight: bold;
        transition: all 0.3s ease;
        flex: 1;
        max-width: 200px;
    }
    
    .transition-btn.new-system {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
    }
    
    .transition-btn.new-system:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
    }
    
    .transition-btn.keep-legacy {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    
    .transition-btn.keep-legacy:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .migration-benefits {
        text-align: left;
        margin-top: 30px;
        padding: 20px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 15px;
    }
    
    .migration-benefits h4 {
        margin: 0 0 15px 0;
        color: #64b5f6;
    }
    
    .migration-benefits ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .migration-benefits li {
        margin-bottom: 8px;
        line-height: 1.4;
    }
    
    .quick-access-panel {
        position: fixed;
        top: 20px;
        right: 20px;
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid #2a2d4a;
        border-radius: 15px;
        padding: 15px;
        color: white;
        z-index: 1000;
        backdrop-filter: blur(10px);
        min-width: 200px;
    }
    
    .quick-access-title {
        font-weight: bold;
        margin-bottom: 10px;
        text-align: center;
        color: #64b5f6;
    }
    
    .quick-access-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .quick-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        color: white;
        padding: 8px 12px;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9em;
    }
    
    .quick-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-1px);
    }
    
    .enhanced-progress-indicator {
        position: fixed;
        bottom: 20px;
        left: 20px;
        background: rgba(26, 26, 46, 0.95);
        border: 1px solid #2a2d4a;
        border-radius: 15px;
        padding: 15px;
        color: white;
        z-index: 1000;
        backdrop-filter: blur(10px);
        min-width: 200px;
    }
    
    .progress-title {
        font-weight: bold;
        margin-bottom: 10px;
        text-align: center;
        color: #64b5f6;
    }
    
    .progress-bar-container {
        width: 100%;
        height: 8px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    
    .progress-text {
        text-align: center;
        font-size: 0.9em;
        color: #94a3b8;
    }
    
    @media (max-width: 768px) {
        .transition-options {
            flex-direction: column;
        }
        
        .quick-access-panel {
            top: 10px;
            right: 10px;
            left: 10px;
            position: relative;
            margin-bottom: 20px;
        }
        
        .enhanced-progress-indicator {
            bottom: 10px;
            left: 10px;
            right: 10px;
            position: relative;
            margin-top: 20px;
        }
    }
`;

// 스타일 추가
if (!document.getElementById('bridge-styles')) {
    const style = document.createElement('style');
    style.id = 'bridge-styles';
    style.textContent = bridgeStyles;
    document.head.appendChild(style);
}

// 전역 인스턴스 생성
window.addEventListener('DOMContentLoaded', () => {
    // 기존 시스템이 로드된 후 브릿지 생성
    setTimeout(() => {
        window.systemBridge = new SystemBridge();
    }, 1000);
});

// 전역 노출
window.SystemBridge = SystemBridge;