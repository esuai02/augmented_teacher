/**
 * 🌟 Shining Stars 메인 애플리케이션 컨트롤러
 * 전체 시스템의 중앙 제어 및 상태 관리
 */

class ShiningStarsApp {
    constructor() {
        this.initialized = false;
        this.state = this.initializeState();
        this.eventBus = this.createEventBus();
        this.screenManager = null;
        this.systems = {};
        
        this.init();
    }

    /**
     * 🚀 애플리케이션 초기화
     */
    async init() {
        try {
            console.log('🌟 Shining Stars 애플리케이션 시작...');
            
            // 로딩 화면 표시
            this.showLoadingScreen();
            
            // 코어 시스템 로드
            await this.loadCoreSystems();
            
            // 사용자 데이터 로드
            await this.loadUserData();
            
            // 화면 관리자 초기화
            this.initializeScreenManager();
            
            // 이벤트 시스템 연결
            this.connectEventSystems();
            
            // 첫 화면 결정 및 표시
            await this.determineInitialScreen();
            
            this.initialized = true;
            this.hideLoadingScreen();
            
            console.log('✅ Shining Stars 초기화 완료');
            
        } catch (error) {
            console.error('❌ 초기화 실패:', error);
            this.showErrorScreen(error);
        }
    }

    /**
     * 🏗️ 초기 상태 구조
     */
    initializeState() {
        return {
            // 앱 상태
            app: {
                initialized: false,
                loading: true,
                error: null,
                currentScreen: null,
                previousScreen: null
            },
            
            // 사용자 상태
            user: {
                profile: {
                    name: null,
                    explorerName: null,
                    joinedDate: null,
                    level: 1
                },
                progress: {
                    collectedCards: [],
                    completedSessions: [],
                    currentStreak: 0,
                    totalTimeSpent: 0,
                    lastActiveDate: null
                },
                preferences: {
                    soundEnabled: true,
                    animationsEnabled: true,
                    difficultyPreference: 'adaptive',
                    themeBrightness: 'auto'
                }
            },
            
            // 현재 컨텍스트
            context: {
                activeSession: null,
                detectedBias: null,
                currentActivity: null,
                notifications: [],
                modals: {
                    active: null,
                    queue: []
                }
            },
            
            // 시스템 상태
            systems: {
                biasDetection: { active: false, sensitivity: 0.7 },
                cardSystem: { initialized: false },
                sessionSystem: { initialized: false },
                navigationMap: { initialized: false },
                audioSystem: { initialized: false }
            }
        };
    }

    /**
     * 📡 이벤트 버스 생성
     */
    createEventBus() {
        const listeners = new Map();
        
        return {
            // 이벤트 구독
            on: (event, callback) => {
                if (!listeners.has(event)) {
                    listeners.set(event, []);
                }
                listeners.get(event).push(callback);
                
                // 구독 해제 함수 반환
                return () => {
                    const callbacks = listeners.get(event);
                    if (callbacks) {
                        const index = callbacks.indexOf(callback);
                        if (index > -1) {
                            callbacks.splice(index, 1);
                        }
                    }
                };
            },
            
            // 이벤트 발생
            emit: (event, data = null) => {
                const callbacks = listeners.get(event);
                if (callbacks) {
                    callbacks.forEach(callback => {
                        try {
                            callback(data);
                        } catch (error) {
                            console.error(`이벤트 처리 오류 [${event}]:`, error);
                        }
                    });
                }
            },
            
            // 일회성 이벤트 구독
            once: (event, callback) => {
                const unsubscribe = this.on(event, (data) => {
                    unsubscribe();
                    callback(data);
                });
                return unsubscribe;
            }
        };
    }

    /**
     * 🔧 코어 시스템 로드
     */
    async loadCoreSystems() {
        const coreSystemsToLoad = [
            'biasClusterData',
            'biasCardSystem', 
            'biasCardLibrary',
            'biasCardSession',
            'biasNavigationMap'
        ];

        for (const systemName of coreSystemsToLoad) {
            if (window[systemName]) {
                this.systems[systemName] = window[systemName];
                this.updateState(`systems.${systemName}.initialized`, true);
                console.log(`✅ ${systemName} 시스템 연결됨`);
            } else {
                console.warn(`⚠️ ${systemName} 시스템을 찾을 수 없습니다`);
            }
        }
    }

    /**
     * 👤 사용자 데이터 로드
     */
    async loadUserData() {
        try {
            // localStorage에서 사용자 데이터 로드
            const savedData = localStorage.getItem('shiningstars_user_data');
            if (savedData) {
                const userData = JSON.parse(savedData);
                this.updateState('user', { ...this.state.user, ...userData });
            }
            
            // 편향 카드 진행도 로드
            const cardProgress = localStorage.getItem('shiningstars_bias_cards');
            if (cardProgress) {
                const progress = JSON.parse(cardProgress);
                this.updateState('user.progress.collectedCards', progress.collectedCards || []);
            }
            
            // 세션 진행도 로드  
            const sessionProgress = localStorage.getItem('shiningstars_sessions');
            if (sessionProgress) {
                const sessions = JSON.parse(sessionProgress);
                this.updateState('user.progress.completedSessions', sessions.completed || []);
            }
            
        } catch (error) {
            console.error('사용자 데이터 로드 실패:', error);
        }
    }

    /**
     * 🖥️ 화면 관리자 초기화
     */
    initializeScreenManager() {
        this.screenManager = new ScreenManager(this);
        
        // 기본 화면들 등록
        this.screenManager.registerScreen('loading', LoadingScreen);
        this.screenManager.registerScreen('onboarding', OnboardingScreen);
        this.screenManager.registerScreen('dashboard', DashboardScreen);
        this.screenManager.registerScreen('map', NavigationMapScreen);
        this.screenManager.registerScreen('library', LibraryScreen);
        this.screenManager.registerScreen('session', SessionScreen);
        this.screenManager.registerScreen('profile', ProfileScreen);
    }

    /**
     * 🔗 이벤트 시스템 연결
     */
    connectEventSystems() {
        // 편향 감지 이벤트
        this.eventBus.on('biasDetected', (data) => {
            this.handleBiasDetection(data);
        });

        // 카드 수집 이벤트
        this.eventBus.on('cardCollected', (data) => {
            this.handleCardCollection(data);
        });

        // 세션 완료 이벤트
        this.eventBus.on('sessionCompleted', (data) => {
            this.handleSessionCompletion(data);
        });

        // 화면 전환 이벤트
        this.eventBus.on('navigateToScreen', (screenName) => {
            this.navigateToScreen(screenName);
        });

        // 상태 변경 이벤트
        this.eventBus.on('stateUpdated', (data) => {
            this.handleStateUpdate(data);
        });

        // 기존 시스템들의 이벤트를 중앙 버스로 연결
        this.connectLegacyEvents();
    }

    /**
     * 🔄 기존 시스템 이벤트 연결
     */
    connectLegacyEvents() {
        // 기존 편향 감지 시스템 연결
        document.addEventListener('biasDetected', (event) => {
            this.eventBus.emit('biasDetected', event.detail);
        });

        document.addEventListener('biasOvercome', (event) => {
            this.eventBus.emit('cardCollected', event.detail);
        });

        document.addEventListener('biasCardCollected', (event) => {
            this.eventBus.emit('cardCollected', event.detail);
        });

        // 키보드 단축키
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }

    /**
     * 🎯 첫 화면 결정
     */
    async determineInitialScreen() {
        const isFirstTime = !this.state.user.profile.name;
        const hasProgress = this.state.user.progress.collectedCards.length > 0;
        
        if (isFirstTime) {
            await this.navigateToScreen('onboarding');
        } else {
            await this.navigateToScreen('dashboard');
        }
    }

    /**
     * 📱 화면 이동
     */
    async navigateToScreen(screenName, data = null) {
        if (!this.screenManager) {
            console.error('ScreenManager가 초기화되지 않았습니다');
            return;
        }

        try {
            this.updateState('app.previousScreen', this.state.app.currentScreen);
            this.updateState('app.currentScreen', screenName);
            
            await this.screenManager.showScreen(screenName, data);
            
            this.eventBus.emit('screenChanged', {
                from: this.state.app.previousScreen,
                to: screenName,
                data
            });
            
        } catch (error) {
            console.error(`화면 전환 실패 [${screenName}]:`, error);
        }
    }

    /**
     * 🧠 편향 감지 처리
     */
    handleBiasDetection(data) {
        const { biasName, confidence, context } = data;
        
        // 감지 이력 업데이트
        this.updateState('context.detectedBias', {
            name: biasName,
            confidence,
            context,
            timestamp: Date.now()
        });

        // 카드 시스템에 전달
        if (this.systems.biasCardSystem) {
            this.systems.biasCardSystem.showDetectionCard(biasName, confidence, context);
        }

        // 통계 업데이트
        this.updateBiasStatistics(biasName, 'detected');
    }

    /**
     * 🎉 카드 수집 처리
     */
    handleCardCollection(data) {
        const { biasName, cardId } = data;
        
        // 수집 목록 업데이트
        const collectedCards = [...this.state.user.progress.collectedCards];
        if (!collectedCards.includes(cardId)) {
            collectedCards.push(cardId);
            this.updateState('user.progress.collectedCards', collectedCards);
        }

        // 성취 체크
        this.checkAchievements();
        
        // 다음 추천 업데이트
        this.updateRecommendations();

        // 데이터 저장
        this.saveUserData();
    }

    /**
     * 🎓 세션 완료 처리
     */
    handleSessionCompletion(data) {
        const { biasName, sessionData } = data;
        
        // 완료 목록 업데이트
        const completedSessions = [...this.state.user.progress.completedSessions];
        completedSessions.push({
            biasName,
            completedAt: Date.now(),
            ...sessionData
        });
        this.updateState('user.progress.completedSessions', completedSessions);

        // 연속 학습 일수 업데이트
        this.updateLearningStreak();
        
        // 성취 체크
        this.checkAchievements();

        // 데이터 저장
        this.saveUserData();
    }

    /**
     * 🏆 성취 체크
     */
    checkAchievements() {
        const achievements = this.calculateAchievements();
        
        achievements.forEach(achievement => {
            if (!achievement.unlocked) return;
            
            this.eventBus.emit('achievementUnlocked', achievement);
            this.showNotification({
                type: 'achievement',
                title: `🏆 ${achievement.title}`,
                message: achievement.description,
                duration: 5000
            });
        });
    }

    /**
     * 📊 성취 계산
     */
    calculateAchievements() {
        const cardCount = this.state.user.progress.collectedCards.length;
        const sessionCount = this.state.user.progress.completedSessions.length;
        const streak = this.state.user.progress.currentStreak;
        
        return [
            {
                id: 'first_card',
                title: '첫 번째 별',
                description: '첫 번째 편향 카드를 수집했습니다',
                unlocked: cardCount >= 1,
                progress: Math.min(cardCount, 1)
            },
            {
                id: 'five_cards',
                title: '별자리 탐험가',
                description: '5개의 편향 카드를 수집했습니다',
                unlocked: cardCount >= 5,
                progress: Math.min(cardCount / 5, 1)
            },
            {
                id: 'first_session',
                title: '학습의 시작',
                description: '첫 번째 학습 세션을 완료했습니다',
                unlocked: sessionCount >= 1,
                progress: Math.min(sessionCount, 1)
            },
            {
                id: 'week_streak',
                title: '꾸준한 탐험가',
                description: '7일 연속 학습했습니다',
                unlocked: streak >= 7,
                progress: Math.min(streak / 7, 1)
            }
        ];
    }

    /**
     * 🔄 상태 업데이트
     */
    updateState(path, value) {
        const pathArray = path.split('.');
        let current = this.state;
        
        // 중첩된 객체 경로 탐색
        for (let i = 0; i < pathArray.length - 1; i++) {
            if (!current[pathArray[i]]) {
                current[pathArray[i]] = {};
            }
            current = current[pathArray[i]];
        }
        
        // 값 설정
        const lastKey = pathArray[pathArray.length - 1];
        current[lastKey] = value;
        
        // 상태 변경 이벤트 발생
        this.eventBus.emit('stateUpdated', { path, value, state: this.state });
    }

    /**
     * 📱 알림 표시
     */
    showNotification(notification) {
        const notifications = [...this.state.context.notifications];
        notifications.push({
            id: Date.now(),
            ...notification,
            timestamp: Date.now()
        });
        
        this.updateState('context.notifications', notifications);
        
        // 자동 제거 (기본 3초)
        setTimeout(() => {
            this.removeNotification(notification.id || Date.now());
        }, notification.duration || 3000);
    }

    /**
     * 🗑️ 알림 제거
     */
    removeNotification(notificationId) {
        const notifications = this.state.context.notifications.filter(
            n => n.id !== notificationId
        );
        this.updateState('context.notifications', notifications);
    }

    /**
     * 💾 사용자 데이터 저장
     */
    saveUserData() {
        try {
            const dataToSave = {
                profile: this.state.user.profile,
                progress: this.state.user.progress,
                preferences: this.state.user.preferences
            };
            
            localStorage.setItem('shiningstars_user_data', JSON.stringify(dataToSave));
            
        } catch (error) {
            console.error('사용자 데이터 저장 실패:', error);
        }
    }

    /**
     * ⌨️ 키보드 단축키 처리
     */
    handleKeyboardShortcuts(event) {
        // ESC - 현재 모달 닫기
        if (event.key === 'Escape') {
            this.eventBus.emit('closeModal');
        }
        
        // Ctrl+M - 네비게이션 맵 토글
        if (event.ctrlKey && event.key === 'm') {
            event.preventDefault();
            this.navigateToScreen('map');
        }
        
        // Ctrl+L - 라이브러리 토글
        if (event.ctrlKey && event.key === 'l') {
            event.preventDefault();
            this.navigateToScreen('library');
        }
        
        // Ctrl+D - 대시보드로 이동
        if (event.ctrlKey && event.key === 'd') {
            event.preventDefault();
            this.navigateToScreen('dashboard');
        }
    }

    /**
     * 🖥️ 로딩 화면 표시
     */
    showLoadingScreen() {
        const loadingHTML = `
            <div id="app-loading-screen" class="loading-screen">
                <div class="cosmic-loader">
                    <div class="stars"></div>
                    <div class="loading-text">🌟 우주를 탐험 중...</div>
                    <div class="loading-progress">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', loadingHTML);
    }

    /**
     * 🚫 로딩 화면 숨기기
     */
    hideLoadingScreen() {
        const loadingScreen = document.getElementById('app-loading-screen');
        if (loadingScreen) {
            loadingScreen.style.opacity = '0';
            setTimeout(() => {
                loadingScreen.remove();
            }, 500);
        }
    }

    /**
     * ❌ 오류 화면 표시
     */
    showErrorScreen(error) {
        const errorHTML = `
            <div id="app-error-screen" class="error-screen">
                <div class="error-content">
                    <div class="error-icon">🚫</div>
                    <h2>시스템 오류</h2>
                    <p>애플리케이션을 시작할 수 없습니다.</p>
                    <details>
                        <summary>오류 상세</summary>
                        <pre>${error.stack || error.message}</pre>
                    </details>
                    <button onclick="location.reload()">다시 시도</button>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', errorHTML);
    }

    // 기타 유틸리티 메소드들...
    updateBiasStatistics(biasName, action) {
        // 편향 통계 업데이트 로직
    }

    updateRecommendations() {
        // 추천 시스템 업데이트 로직
    }

    updateLearningStreak() {
        // 연속 학습 일수 계산 로직
    }
}

// 전역 인스턴스 생성 및 자동 시작
window.addEventListener('DOMContentLoaded', () => {
    window.shiningStarsApp = new ShiningStarsApp();
});

// 전역 API 노출
window.ShiningStars = {
    getApp: () => window.shiningStarsApp,
    getState: () => window.shiningStarsApp?.state,
    navigateTo: (screen) => window.shiningStarsApp?.navigateToScreen(screen),
    emit: (event, data) => window.shiningStarsApp?.eventBus.emit(event, data)
};