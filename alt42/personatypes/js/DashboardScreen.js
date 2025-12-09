/**
 * 🏠 통합 대시보드 화면
 * 모든 시스템의 중앙 허브 역할
 */

class DashboardScreen extends BaseScreen {
    constructor(app, element) {
        super(app, element);
        this.refreshInterval = null;
        this.widgets = new Map();
    }

    async render(data) {
        const userProgress = this.app.state.user.progress;
        const userName = this.app.state.user.profile.explorerName || this.app.state.user.profile.name || '우주 탐험가';
        
        this.element.innerHTML = `
            <div class="dashboard-screen">
                <!-- 헤더 영역 -->
                <header class="dashboard-header">
                    <div class="welcome-section">
                        <h1 class="welcome-title">🌟 ${userName}님, 환영합니다!</h1>
                        <p class="welcome-subtitle">오늘도 새로운 우주적 발견을 시작해보세요</p>
                    </div>
                    
                    <div class="header-actions">
                        <button class="header-btn" onclick="ShiningStars.navigateTo('profile')" title="프로필">
                            👤 프로필
                        </button>
                        <button class="header-btn" onclick="this.toggleSettings()" title="설정">
                            ⚙️ 설정
                        </button>
                    </div>
                </header>

                <!-- 메인 대시보드 그리드 -->
                <main class="dashboard-grid">
                    <!-- 진행도 위젯 -->
                    <div class="dashboard-widget progress-widget">
                        <div class="widget-header">
                            <h3>🌌 탐험 진행도</h3>
                            <span class="widget-icon">📊</span>
                        </div>
                        <div class="widget-content">
                            <div class="progress-overview">
                                <div class="progress-circle" id="progressCircle">
                                    <svg viewBox="0 0 120 120">
                                        <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8"/>
                                        <circle cx="60" cy="60" r="54" fill="none" stroke="#667eea" stroke-width="8" 
                                                stroke-linecap="round" stroke-dasharray="339" stroke-dashoffset="339" 
                                                id="progressBar" class="progress-bar-circle"/>
                                        <text x="60" y="60" text-anchor="middle" dy="7" class="progress-text" id="progressText">0%</text>
                                    </svg>
                                </div>
                                <div class="progress-stats">
                                    <div class="stat-item">
                                        <span class="stat-label">수집된 카드</span>
                                        <span class="stat-value" id="cardCount">${userProgress.collectedCards.length}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">완료된 세션</span>
                                        <span class="stat-value" id="sessionCount">${userProgress.completedSessions.length}</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-label">연속 학습</span>
                                        <span class="stat-value" id="streakCount">${userProgress.currentStreak}일</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 빠른 액세스 위젯 -->
                    <div class="dashboard-widget quick-access-widget">
                        <div class="widget-header">
                            <h3>🚀 빠른 액세스</h3>
                            <span class="widget-icon">⚡</span>
                        </div>
                        <div class="widget-content">
                            <div class="quick-actions-grid">
                                <button class="quick-action-btn map-btn" onclick="ShiningStars.navigateTo('map')">
                                    <div class="action-icon">🗺️</div>
                                    <div class="action-label">우주 지도</div>
                                    <div class="action-description">편향 네비게이션</div>
                                </button>
                                
                                <button class="quick-action-btn library-btn" onclick="ShiningStars.navigateTo('library')">
                                    <div class="action-icon">🃏</div>
                                    <div class="action-label">카드 도감</div>
                                    <div class="action-description">수집된 편향들</div>
                                </button>
                                
                                <button class="quick-action-btn session-btn" onclick="this.startRecommendedSession()">
                                    <div class="action-icon">🎓</div>
                                    <div class="action-label">학습 세션</div>
                                    <div class="action-description">추천 편향 학습</div>
                                </button>
                                
                                <button class="quick-action-btn detection-btn" onclick="this.toggleBiasDetection()">
                                    <div class="action-icon">🔍</div>
                                    <div class="action-label">편향 감지</div>
                                    <div class="action-description">실시간 분석</div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 추천 편향 위젯 -->
                    <div class="dashboard-widget recommendations-widget">
                        <div class="widget-header">
                            <h3>🎯 오늘의 추천</h3>
                            <span class="widget-icon">⭐</span>
                        </div>
                        <div class="widget-content">
                            <div id="recommendationsContainer" class="recommendations-container">
                                <!-- 추천 편향들이 동적으로 로드됩니다 -->
                            </div>
                        </div>
                    </div>

                    <!-- 최근 활동 위젯 -->
                    <div class="dashboard-widget activity-widget">
                        <div class="widget-header">
                            <h3>📈 최근 활동</h3>
                            <span class="widget-icon">🕒</span>
                        </div>
                        <div class="widget-content">
                            <div id="activityTimeline" class="activity-timeline">
                                <!-- 최근 활동들이 동적으로 로드됩니다 -->
                            </div>
                        </div>
                    </div>

                    <!-- 성취 위젯 -->
                    <div class="dashboard-widget achievements-widget">
                        <div class="widget-header">
                            <h3>🏆 성취</h3>
                            <span class="widget-icon">🎖️</span>
                        </div>
                        <div class="widget-content">
                            <div id="achievementsContainer" class="achievements-container">
                                <!-- 성취들이 동적으로 로드됩니다 -->
                            </div>
                        </div>
                    </div>

                    <!-- 우주적 상태 위젯 -->
                    <div class="dashboard-widget cosmic-status-widget">
                        <div class="widget-header">
                            <h3>🌌 우주적 상태</h3>
                            <span class="widget-icon">✨</span>
                        </div>
                        <div class="widget-content">
                            <div class="cosmic-status">
                                <div class="cosmic-avatar" id="cosmicAvatar">
                                    <div class="avatar-glow"></div>
                                    <div class="avatar-icon">🌟</div>
                                </div>
                                <div class="cosmic-message" id="cosmicMessage">
                                    오늘도 새로운 별자리를 탐험할 준비가 되었습니다! ✨
                                </div>
                            </div>
                        </div>
                    </div>
                </main>

                <!-- 알림 패널 -->
                <div class="notification-panel" id="notificationPanel">
                    <div class="notification-header">
                        <h4>🔔 알림</h4>
                        <button class="close-notifications" onclick="this.closeNotifications()">✕</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <!-- 알림들이 동적으로 로드됩니다 -->
                    </div>
                </div>
            </div>
        `;

        // 위젯 초기화
        this.initializeWidgets();
        this.initialized = true;
    }

    /**
     * 위젯들 초기화
     */
    initializeWidgets() {
        this.updateProgressWidget();
        this.loadRecommendations();
        this.loadRecentActivity();
        this.loadAchievements();
        this.updateCosmicStatus();
        this.startPeriodicUpdates();
    }

    /**
     * 진행도 위젯 업데이트
     */
    updateProgressWidget() {
        const progress = this.calculateOverallProgress();
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        
        if (progressBar && progressText) {
            const circumference = 339; // 2 * π * 54
            const offset = circumference - (progress.percentage / 100) * circumference;
            
            progressBar.style.strokeDashoffset = offset;
            progressText.textContent = `${progress.percentage}%`;
        }

        // 애니메이션 효과
        setTimeout(() => {
            progressBar?.classList.add('animated');
        }, 100);
    }

    /**
     * 전체 진행도 계산
     */
    calculateOverallProgress() {
        const userProgress = this.app.state.user.progress;
        const totalPossibleCards = 60; // 전체 편향 카드 수
        const collectedCards = userProgress.collectedCards.length;
        const completedSessions = userProgress.completedSessions.length;
        
        // 가중 평균으로 진행도 계산
        const cardProgress = (collectedCards / totalPossibleCards) * 0.6;
        const sessionProgress = (completedSessions / totalPossibleCards) * 0.4;
        const totalProgress = Math.round((cardProgress + sessionProgress) * 100);
        
        return {
            percentage: Math.min(totalProgress, 100),
            collectedCards,
            completedSessions,
            totalCards: totalPossibleCards
        };
    }

    /**
     * 추천 편향 로드
     */
    loadRecommendations() {
        const container = document.getElementById('recommendationsContainer');
        if (!container || !window.biasClusterData) return;

        const userProgress = this.app.state.user.progress.collectedCards;
        const recommendations = window.biasClusterData.getNextTargets(userProgress, 3);
        
        if (recommendations.length === 0) {
            container.innerHTML = `
                <div class="no-recommendations">
                    <div class="no-rec-icon">🎉</div>
                    <p>모든 편향을 정복했습니다!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = recommendations.map(rec => `
            <div class="recommendation-card" onclick="this.openBiasDetail('${rec.bias}')">
                <div class="rec-header">
                    <span class="rec-name">${rec.bias}</span>
                    <span class="rec-difficulty difficulty-${rec.difficulty}">${this.getDifficultyText(rec.difficulty)}</span>
                </div>
                <div class="rec-reason">${rec.reason}</div>
                <div class="rec-unlock-count">
                    ${rec.unlocks.length > 0 ? `🔓 ${rec.unlocks.length}개 편향 잠금해제` : ''}
                </div>
            </div>
        `).join('');
    }

    /**
     * 최근 활동 로드
     */
    loadRecentActivity() {
        const container = document.getElementById('activityTimeline');
        if (!container) return;

        const activities = this.getRecentActivities();
        
        if (activities.length === 0) {
            container.innerHTML = `
                <div class="no-activity">
                    <div class="no-activity-icon">🌱</div>
                    <p>아직 활동이 없습니다. 첫 번째 편향을 탐험해보세요!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = activities.map(activity => `
            <div class="activity-item">
                <div class="activity-icon">${activity.icon}</div>
                <div class="activity-content">
                    <div class="activity-title">${activity.title}</div>
                    <div class="activity-time">${this.formatRelativeTime(activity.timestamp)}</div>
                </div>
            </div>
        `).join('');
    }

    /**
     * 최근 활동 데이터 생성
     */
    getRecentActivities() {
        const activities = [];
        const userProgress = this.app.state.user.progress;
        
        // 최근 완료된 세션들
        userProgress.completedSessions.slice(-5).forEach(session => {
            activities.push({
                icon: '🎓',
                title: `${session.biasName} 학습 세션 완료`,
                timestamp: session.completedAt || Date.now(),
                type: 'session'
            });
        });

        // 최근 수집된 카드들 (임시 데이터)
        userProgress.collectedCards.slice(-3).forEach((cardId, index) => {
            activities.push({
                icon: '🃏',
                title: `새로운 편향 카드 획득`,
                timestamp: Date.now() - (index * 3600000), // 1시간씩 차이
                type: 'card'
            });
        });

        return activities.sort((a, b) => b.timestamp - a.timestamp).slice(0, 5);
    }

    /**
     * 성취 로드
     */
    loadAchievements() {
        const container = document.getElementById('achievementsContainer');
        if (!container) return;

        const achievements = this.app.calculateAchievements();
        const unlockedAchievements = achievements.filter(a => a.unlocked);
        
        if (unlockedAchievements.length === 0) {
            container.innerHTML = `
                <div class="no-achievements">
                    <div class="no-ach-icon">🏆</div>
                    <p>첫 번째 성취를 달성해보세요!</p>
                </div>
            `;
            return;
        }

        container.innerHTML = unlockedAchievements.slice(0, 3).map(achievement => `
            <div class="achievement-badge">
                <div class="achievement-icon">🏆</div>
                <div class="achievement-info">
                    <div class="achievement-title">${achievement.title}</div>
                    <div class="achievement-description">${achievement.description}</div>
                </div>
            </div>
        `).join('');
    }

    /**
     * 우주적 상태 업데이트
     */
    updateCosmicStatus() {
        const messageElement = document.getElementById('cosmicMessage');
        if (!messageElement) return;

        const messages = [
            '오늘도 새로운 별자리를 탐험할 준비가 되었습니다! ✨',
            '우주의 신비로운 편향들이 당신을 기다리고 있어요 🌌',
            '지금이 새로운 통찰을 얻을 완벽한 시간입니다 🔮',
            '당신의 학습 여정이 별처럼 빛나고 있어요 ⭐',
            '오늘은 어떤 편향과 마주하게 될까요? 🎭'
        ];

        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        messageElement.textContent = randomMessage;
    }

    /**
     * 주기적 업데이트 시작
     */
    startPeriodicUpdates() {
        // 30초마다 상태 업데이트
        this.refreshInterval = setInterval(() => {
            this.updateCosmicStatus();
        }, 30000);
    }

    /**
     * 추천 세션 시작
     */
    startRecommendedSession() {
        if (!window.biasClusterData) {
            alert('시스템을 준비 중입니다. 잠시 후 다시 시도해주세요.');
            return;
        }

        const userProgress = this.app.state.user.progress.collectedCards;
        const recommendations = window.biasClusterData.getNextTargets(userProgress, 1);
        
        if (recommendations.length > 0) {
            const recommendedBias = recommendations[0].bias;
            if (window.biasCardSession) {
                window.biasCardSession.startSession(recommendedBias);
            } else {
                alert(`${recommendedBias} 학습을 시작하세요!`);
            }
        } else {
            alert('현재 추천할 수 있는 편향이 없습니다.');
        }
    }

    /**
     * 편향 감지 토글
     */
    toggleBiasDetection() {
        const detectionBtn = document.querySelector('.detection-btn');
        const isActive = detectionBtn.classList.contains('active');
        
        if (isActive) {
            detectionBtn.classList.remove('active');
            detectionBtn.querySelector('.action-description').textContent = '실시간 분석';
            // 감지 시스템 비활성화
            if (window.biasDetectionSystem) {
                window.biasDetectionSystem.pauseDetection();
            }
        } else {
            detectionBtn.classList.add('active');
            detectionBtn.querySelector('.action-description').textContent = '감지 중...';
            // 감지 시스템 활성화
            if (window.biasDetectionSystem) {
                window.biasDetectionSystem.resumeDetection();
            }
        }
    }

    /**
     * 편향 상세 정보 열기
     */
    openBiasDetail(biasName) {
        if (window.biasNavigationMap) {
            // 네비게이션 맵에서 해당 편향으로 이동
            ShiningStars.navigateTo('map');
            setTimeout(() => {
                window.biasNavigationMap.selectBias(biasName);
            }, 500);
        }
    }

    /**
     * 알림 닫기
     */
    closeNotifications() {
        const panel = document.getElementById('notificationPanel');
        if (panel) {
            panel.classList.remove('show');
        }
    }

    /**
     * 화면 활성화 시 호출
     */
    onActivate(data) {
        this.updateProgressWidget();
        this.loadRecommendations();
        this.updateCosmicStatus();
    }

    /**
     * 정리
     */
    cleanup() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
        }
    }

    // 유틸리티 메소드들
    getDifficultyText(difficulty) {
        const texts = { easy: '쉬움', medium: '보통', hard: '어려움' };
        return texts[difficulty] || '보통';
    }

    formatRelativeTime(timestamp) {
        const now = Date.now();
        const diff = now - timestamp;
        const minutes = Math.floor(diff / 60000);
        const hours = Math.floor(diff / 3600000);
        const days = Math.floor(diff / 86400000);

        if (days > 0) return `${days}일 전`;
        if (hours > 0) return `${hours}시간 전`;
        if (minutes > 0) return `${minutes}분 전`;
        return '방금 전';
    }
}

// 대시보드 스타일
const dashboardStyles = `
    .dashboard-screen {
        min-height: 100vh;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        color: white;
        overflow-y: auto;
    }

    .dashboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(0, 0, 0, 0.2);
        backdrop-filter: blur(10px);
    }

    .welcome-title {
        font-size: 2rem;
        font-weight: 700;
        margin: 0 0 0.5rem 0;
        background: linear-gradient(135deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .welcome-subtitle {
        margin: 0;
        color: #94a3b8;
        font-size: 1.1rem;
    }

    .header-actions {
        display: flex;
        gap: 1rem;
    }

    .header-btn {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 10px;
        color: white;
        padding: 0.75rem 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .header-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 1.5rem;
        padding: 2rem;
        max-width: 1400px;
        margin: 0 auto;
    }

    .dashboard-widget {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        padding: 1.5rem;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
    }

    .dashboard-widget:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
        border-color: rgba(102, 126, 234, 0.3);
    }

    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
    }

    .widget-header h3 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .widget-icon {
        font-size: 1.5rem;
        opacity: 0.7;
    }

    /* 진행도 위젯 */
    .progress-overview {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .progress-circle {
        width: 120px;
        height: 120px;
    }

    .progress-circle svg {
        width: 100%;
        height: 100%;
        transform: rotate(-90deg);
    }

    .progress-bar-circle {
        transition: stroke-dashoffset 2s ease-in-out;
    }

    .progress-text {
        font-size: 1.2rem;
        font-weight: 700;
        fill: #667eea;
        transform: rotate(90deg);
    }

    .progress-stats {
        flex: 1;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-item:last-child {
        border-bottom: none;
    }

    .stat-label {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #667eea;
    }

    /* 빠른 액세스 위젯 */
    .quick-actions-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .quick-action-btn {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 15px;
        padding: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
        color: white;
    }

    .quick-action-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        transform: translateY(-3px);
    }

    .quick-action-btn.active {
        border-color: #667eea;
        background: rgba(102, 126, 234, 0.1);
    }

    .action-icon {
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .action-label {
        font-size: 1rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .action-description {
        font-size: 0.8rem;
        color: #94a3b8;
    }

    /* 추천 편향 위젯 */
    .recommendations-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .recommendation-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        padding: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .recommendation-card:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: #667eea;
    }

    .rec-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .rec-name {
        font-weight: 600;
        font-size: 1rem;
    }

    .rec-difficulty {
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 500;
    }

    .difficulty-easy { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
    .difficulty-medium { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
    .difficulty-hard { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

    .rec-reason {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .rec-unlock-count {
        color: #fbbf24;
        font-size: 0.8rem;
    }

    /* 활동 타임라인 */
    .activity-timeline {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem;
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
    }

    .activity-icon {
        font-size: 1.5rem;
        width: 40px;
        text-align: center;
    }

    .activity-content {
        flex: 1;
    }

    .activity-title {
        font-weight: 500;
        margin-bottom: 0.25rem;
    }

    .activity-time {
        color: #94a3b8;
        font-size: 0.8rem;
    }

    /* 성취 위젯 */
    .achievements-container {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .achievement-badge {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        background: rgba(255, 215, 0, 0.1);
        border: 1px solid rgba(255, 215, 0, 0.2);
        border-radius: 12px;
    }

    .achievement-icon {
        font-size: 1.5rem;
    }

    .achievement-title {
        font-weight: 600;
        margin-bottom: 0.25rem;
    }

    .achievement-description {
        color: #94a3b8;
        font-size: 0.9rem;
    }

    /* 우주적 상태 위젯 */
    .cosmic-status {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .cosmic-avatar {
        position: relative;
        width: 80px;
        height: 80px;
    }

    .avatar-glow {
        position: absolute;
        top: -10px;
        left: -10px;
        right: -10px;
        bottom: -10px;
        background: radial-gradient(circle, rgba(102, 126, 234, 0.3) 0%, transparent 70%);
        border-radius: 50%;
        animation: pulse 2s ease-in-out infinite;
    }

    .avatar-icon {
        position: relative;
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        animation: float 3s ease-in-out infinite;
    }

    .cosmic-message {
        flex: 1;
        font-size: 1rem;
        line-height: 1.5;
        color: #e2e8f0;
    }

    /* 빈 상태 스타일들 */
    .no-recommendations, .no-activity, .no-achievements {
        text-align: center;
        padding: 2rem;
        color: #94a3b8;
    }

    .no-rec-icon, .no-activity-icon, .no-ach-icon {
        font-size: 3rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    /* 애니메이션 */
    @keyframes pulse {
        0%, 100% { opacity: 0.5; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-5px); }
    }

    /* 반응형 */
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
            padding: 1rem;
        }
        
        .progress-overview {
            flex-direction: column;
            text-align: center;
        }
        
        .quick-actions-grid {
            grid-template-columns: 1fr;
        }
        
        .cosmic-status {
            flex-direction: column;
            text-align: center;
        }
    }
`;

// 스타일 추가
if (!document.getElementById('dashboard-styles')) {
    const style = document.createElement('style');
    style.id = 'dashboard-styles';
    style.textContent = dashboardStyles;
    document.head.appendChild(style);
}

// 전역 노출
window.DashboardScreen = DashboardScreen;