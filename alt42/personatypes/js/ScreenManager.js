/**
 * 🖥️ 화면 전환 및 관리 시스템
 * 애니메이션과 함께 부드러운 화면 전환 제공
 */

class ScreenManager {
    constructor(app) {
        this.app = app;
        this.screens = new Map();
        this.currentScreen = null;
        this.previousScreen = null;
        this.transitionDuration = 500;
        this.screenContainer = null;
        
        this.init();
    }

    /**
     * 🚀 화면 관리자 초기화
     */
    init() {
        this.createScreenContainer();
        this.setupTransitionStyles();
        this.bindEvents();
    }

    /**
     * 📦 화면 컨테이너 생성
     */
    createScreenContainer() {
        // 기존 컨테이너 제거
        const existing = document.getElementById('screen-container');
        if (existing) {
            existing.remove();
        }

        // 새 컨테이너 생성
        const container = document.createElement('div');
        container.id = 'screen-container';
        container.className = 'screen-container';
        
        document.body.appendChild(container);
        this.screenContainer = container;
    }

    /**
     * 🎨 전환 애니메이션 스타일 설정
     */
    setupTransitionStyles() {
        if (!document.getElementById('screen-transition-styles')) {
            const styles = document.createElement('style');
            styles.id = 'screen-transition-styles';
            styles.textContent = `
                .screen-container {
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    overflow: hidden;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    z-index: 1000;
                }
                
                .screen {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    opacity: 0;
                    transform: translateY(50px);
                    transition: all ${this.transitionDuration}ms cubic-bezier(0.4, 0, 0.2, 1);
                    overflow-y: auto;
                    color: white;
                }
                
                .screen.active {
                    opacity: 1;
                    transform: translateY(0);
                    z-index: 10;
                }
                
                .screen.exiting {
                    opacity: 0;
                    transform: translateY(-30px);
                    z-index: 5;
                }
                
                /* 특수 전환 효과 */
                .screen.slide-left {
                    transform: translateX(-100%);
                }
                
                .screen.slide-right {
                    transform: translateX(100%);
                }
                
                .screen.fade-in {
                    transform: scale(0.95);
                }
                
                .screen.fade-in.active {
                    transform: scale(1);
                }
                
                /* 우주적 파티클 효과 */
                .screen-transition-particles {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    pointer-events: none;
                    z-index: 15;
                }
                
                .particle {
                    position: absolute;
                    width: 2px;
                    height: 2px;
                    background: #ffffff;
                    border-radius: 50%;
                    opacity: 0.8;
                    animation: float 3s infinite ease-in-out;
                }
                
                @keyframes float {
                    0%, 100% {
                        transform: translateY(0) scale(1);
                        opacity: 0.8;
                    }
                    50% {
                        transform: translateY(-20px) scale(1.2);
                        opacity: 0.4;
                    }
                }
                
                /* 로딩 및 에러 화면 */
                .loading-screen, .error-screen {
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    min-height: 100vh;
                    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                    color: white;
                    text-align: center;
                }
                
                .cosmic-loader {
                    max-width: 300px;
                }
                
                .loading-text {
                    font-size: 1.5em;
                    margin: 20px 0;
                    animation: pulse 2s infinite;
                }
                
                .loading-progress {
                    width: 200px;
                    height: 4px;
                    background: rgba(255, 255, 255, 0.2);
                    border-radius: 2px;
                    overflow: hidden;
                    margin: 20px auto;
                }
                
                .progress-bar {
                    width: 0%;
                    height: 100%;
                    background: linear-gradient(90deg, #667eea, #764ba2);
                    border-radius: 2px;
                    animation: loading 2s infinite ease-in-out;
                }
                
                @keyframes pulse {
                    0%, 100% { opacity: 1; }
                    50% { opacity: 0.6; }
                }
                
                @keyframes loading {
                    0% { width: 0%; }
                    50% { width: 70%; }
                    100% { width: 100%; }
                }
            `;
            document.head.appendChild(styles);
        }
    }

    /**
     * 📝 화면 등록
     */
    registerScreen(name, screenClass) {
        this.screens.set(name, {
            class: screenClass,
            instance: null,
            element: null
        });
    }

    /**
     * 📱 화면 표시
     */
    async showScreen(screenName, data = null, transition = 'fade') {
        const screenConfig = this.screens.get(screenName);
        if (!screenConfig) {
            throw new Error(`화면을 찾을 수 없습니다: ${screenName}`);
        }

        try {
            // 이전 화면 페이드아웃
            if (this.currentScreen) {
                await this.hideCurrentScreen();
            }

            // 새 화면 생성 또는 재사용
            const screenElement = await this.createScreenElement(screenName, data);
            
            // 전환 애니메이션 시작
            await this.performTransition(screenElement, transition);
            
            // 화면 상태 업데이트
            this.previousScreen = this.currentScreen;
            this.currentScreen = screenName;
            
            // 화면 활성화 이벤트
            this.activateScreen(screenName, data);
            
        } catch (error) {
            console.error(`화면 표시 오류 [${screenName}]:`, error);
            throw error;
        }
    }

    /**
     * 🏗️ 화면 요소 생성
     */
    async createScreenElement(screenName, data) {
        const screenConfig = this.screens.get(screenName);
        
        // 기존 화면 요소 제거
        if (screenConfig.element) {
            screenConfig.element.remove();
        }

        // 새 화면 요소 생성
        const screenElement = document.createElement('div');
        screenElement.className = `screen screen-${screenName}`;
        screenElement.id = `screen-${screenName}`;
        
        // 화면 클래스 인스턴스 생성
        if (!screenConfig.instance) {
            screenConfig.instance = new screenConfig.class(this.app, screenElement);
        }
        
        // 화면 콘텐츠 렌더링
        await screenConfig.instance.render(data);
        
        // 컨테이너에 추가
        this.screenContainer.appendChild(screenElement);
        screenConfig.element = screenElement;
        
        return screenElement;
    }

    /**
     * 🎭 전환 애니메이션 수행
     */
    async performTransition(screenElement, transition) {
        return new Promise((resolve) => {
            // 전환 파티클 효과 추가
            this.addTransitionParticles();
            
            // 전환 클래스 추가
            screenElement.classList.add(transition);
            
            // 다음 프레임에서 활성화
            requestAnimationFrame(() => {
                screenElement.classList.add('active');
                
                // 전환 완료 후 정리
                setTimeout(() => {
                    screenElement.classList.remove(transition);
                    this.removeTransitionParticles();
                    resolve();
                }, this.transitionDuration);
            });
        });
    }

    /**
     * 🌌 전환 파티클 효과
     */
    addTransitionParticles() {
        const particleContainer = document.createElement('div');
        particleContainer.className = 'screen-transition-particles';
        particleContainer.id = 'transition-particles';
        
        // 랜덤 파티클 생성
        for (let i = 0; i < 20; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.top = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 2 + 's';
            particleContainer.appendChild(particle);
        }
        
        this.screenContainer.appendChild(particleContainer);
    }

    /**
     * 🧹 파티클 효과 제거
     */
    removeTransitionParticles() {
        const particles = document.getElementById('transition-particles');
        if (particles) {
            particles.style.opacity = '0';
            setTimeout(() => {
                particles.remove();
            }, 300);
        }
    }

    /**
     * 📤 현재 화면 숨기기
     */
    async hideCurrentScreen() {
        if (!this.currentScreen) return;
        
        const screenConfig = this.screens.get(this.currentScreen);
        if (screenConfig && screenConfig.element) {
            return new Promise((resolve) => {
                screenConfig.element.classList.add('exiting');
                
                setTimeout(() => {
                    screenConfig.element.classList.remove('active', 'exiting');
                    resolve();
                }, this.transitionDuration / 2);
            });
        }
    }

    /**
     * ⚡ 화면 활성화
     */
    activateScreen(screenName, data) {
        const screenConfig = this.screens.get(screenName);
        if (screenConfig && screenConfig.instance) {
            // 화면 활성화 이벤트 호출
            if (typeof screenConfig.instance.onActivate === 'function') {
                screenConfig.instance.onActivate(data);
            }
            
            // 앱 상태 업데이트
            this.app.eventBus.emit('screenActivated', {
                screenName,
                instance: screenConfig.instance,
                data
            });
        }
    }

    /**
     * 🔙 이전 화면으로 돌아가기
     */
    async goBack() {
        if (this.previousScreen) {
            await this.showScreen(this.previousScreen, null, 'slide-right');
        }
    }

    /**
     * 🧹 화면 정리
     */
    cleanupScreen(screenName) {
        const screenConfig = this.screens.get(screenName);
        if (screenConfig) {
            // 인스턴스 정리
            if (screenConfig.instance && typeof screenConfig.instance.cleanup === 'function') {
                screenConfig.instance.cleanup();
            }
            
            // 요소 제거
            if (screenConfig.element) {
                screenConfig.element.remove();
                screenConfig.element = null;
            }
            
            // 인스턴스 제거
            screenConfig.instance = null;
        }
    }

    /**
     * 🎪 이벤트 바인딩
     */
    bindEvents() {
        // 브라우저 뒤로가기 버튼
        window.addEventListener('popstate', () => {
            this.goBack();
        });
        
        // 앱 이벤트 구독
        this.app.eventBus.on('navigateBack', () => {
            this.goBack();
        });
        
        this.app.eventBus.on('cleanupScreen', (screenName) => {
            this.cleanupScreen(screenName);
        });
    }

    /**
     * 📊 현재 상태 정보
     */
    getState() {
        return {
            currentScreen: this.currentScreen,
            previousScreen: this.previousScreen,
            availableScreens: Array.from(this.screens.keys()),
            isTransitioning: this.screenContainer?.querySelector('.screen.exiting') !== null
        };
    }
}

/**
 * 📱 기본 화면 클래스
 */
class BaseScreen {
    constructor(app, element) {
        this.app = app;
        this.element = element;
        this.initialized = false;
    }

    async render(data) {
        // 하위 클래스에서 구현
        this.element.innerHTML = '<div>기본 화면</div>';
        this.initialized = true;
    }

    onActivate(data) {
        // 화면이 활성화될 때 호출
    }

    cleanup() {
        // 화면 정리 시 호출
    }
}

// 화면 클래스들 (임시 구현)
class LoadingScreen extends BaseScreen {
    async render(data) {
        this.element.innerHTML = `
            <div class="loading-screen">
                <div class="cosmic-loader">
                    <div class="stars"></div>
                    <div class="loading-text">🌟 우주를 탐험 중...</div>
                    <div class="loading-progress">
                        <div class="progress-bar"></div>
                    </div>
                </div>
            </div>
        `;
    }
}

class OnboardingScreen extends BaseScreen {
    async render(data) {
        this.element.innerHTML = `
            <div class="onboarding-screen">
                <h1>🌌 Shining Stars에 오신 것을 환영합니다!</h1>
                <p>우주적 편향 극복 여정을 시작해보세요.</p>
                <button onclick="ShiningStars.navigateTo('dashboard')">
                    🚀 탐험 시작하기
                </button>
            </div>
        `;
    }
}

class DashboardScreen extends BaseScreen {
    async render(data) {
        this.element.innerHTML = `
            <div class="dashboard-screen">
                <header class="dashboard-header">
                    <h1>🌟 우주 탐험 대시보드</h1>
                    <div class="quick-actions">
                        <button onclick="ShiningStars.navigateTo('map')">🗺️ 우주 지도</button>
                        <button onclick="ShiningStars.navigateTo('library')">🃏 카드 도감</button>
                    </div>
                </header>
                <main class="dashboard-content">
                    <div class="progress-overview">진행도 정보가 여기에 표시됩니다</div>
                    <div class="recommendations">추천 편향이 여기에 표시됩니다</div>
                </main>
            </div>
        `;
    }
}

class NavigationMapScreen extends BaseScreen {
    async render(data) {
        // 기존 네비게이션 맵 시스템 활용
        this.element.innerHTML = `
            <div class="map-screen">
                <button onclick="ShiningStars.navigateTo('dashboard')" class="back-btn">
                    ← 대시보드로 돌아가기
                </button>
                <div id="map-integration-point">
                    네비게이션 맵이 여기에 표시됩니다
                </div>
            </div>
        `;
    }
    
    onActivate(data) {
        // 네비게이션 맵 시스템 활성화
        if (window.biasNavigationMap) {
            setTimeout(() => {
                window.biasNavigationMap.open();
            }, 100);
        }
    }
}

class LibraryScreen extends BaseScreen {
    async render(data) {
        this.element.innerHTML = `
            <div class="library-screen">
                <button onclick="ShiningStars.navigateTo('dashboard')" class="back-btn">
                    ← 대시보드로 돌아가기
                </button>
                <div id="library-integration-point">
                    카드 라이브러리가 여기에 표시됩니다
                </div>
            </div>
        `;
    }
    
    onActivate(data) {
        // 카드 라이브러리 시스템 활성화
        if (window.biasCardLibrary) {
            setTimeout(() => {
                window.biasCardLibrary.open();
            }, 100);
        }
    }
}

class SessionScreen extends BaseScreen {
    async render(data) {
        this.element.innerHTML = `
            <div class="session-screen">
                <div>학습 세션 화면</div>
                <button onclick="ShiningStars.navigateTo('dashboard')">
                    대시보드로 돌아가기
                </button>
            </div>
        `;
    }
}

class ProfileScreen extends BaseScreen {
    async render(data) {
        this.element.innerHTML = `
            <div class="profile-screen">
                <div>프로필 화면</div>
                <button onclick="ShiningStars.navigateTo('dashboard')">
                    대시보드로 돌아가기
                </button>
            </div>
        `;
    }
}

// 전역 노출
window.ScreenManager = ScreenManager;
window.BaseScreen = BaseScreen;