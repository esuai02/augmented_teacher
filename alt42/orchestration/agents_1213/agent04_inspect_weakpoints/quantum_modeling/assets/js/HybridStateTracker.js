/**
 * HybridStateTracker - 실시간 학생 상태 추적 및 보정 모듈
 * 
 * Kalman Filter + Active Ping 하이브리드 시스템의 클라이언트 측 구현
 * - Fast Loop: 0.5초 주기로 센서 데이터 수집 및 예측
 * - Active Ping: 불확실성 높을 때 미세 자극 발사
 * - Kalman Correction: 확실한 이벤트 발생 시 즉시 보정
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

class HybridStateTracker {
    constructor(options = {}) {
        // 설정
        this.config = {
            apiEndpoint: options.apiEndpoint || '/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/api/hybrid_state_api.php',
            fastLoopInterval: options.fastLoopInterval || 500, // 0.5초
            pingThreshold: options.pingThreshold || 0.4,
            userId: options.userId || null,
            debug: options.debug || false,
            onStateChange: options.onStateChange || null,
            onPingFired: options.onPingFired || null,
            onCorrectionMade: options.onCorrectionMade || null
        };
        
        // 상태
        this.state = {
            predictedState: 0.5,
            uncertainty: 0.1,
            confidence: 1.0,
            stateVector: { focus: 0.5, flow: 0, struggle: 0, lost: 0.5 },
            dominantState: 'focus',
            needsPing: false,
            pingLevel: null
        };
        
        // 센서 데이터 버퍼
        this.sensorBuffer = {
            mousePositions: [],
            scrollEvents: [],
            keystrokes: [],
            lastMouseMove: 0,
            lastActivity: Date.now()
        };
        
        // 활성 핑
        this.activePing = null;
        
        // 루프 ID
        this.fastLoopId = null;
        
        // 바인딩
        this.handleMouseMove = this.handleMouseMove.bind(this);
        this.handleScroll = this.handleScroll.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleClick = this.handleClick.bind(this);
        this.handleVisibilityChange = this.handleVisibilityChange.bind(this);
    }
    
    // ============================================================
    // 초기화 및 종료
    // ============================================================
    
    start() {
        this.log('🚀 HybridStateTracker 시작');
        
        // 이벤트 리스너 등록
        document.addEventListener('mousemove', this.handleMouseMove, { passive: true });
        document.addEventListener('scroll', this.handleScroll, { passive: true });
        document.addEventListener('keydown', this.handleKeyDown, { passive: true });
        document.addEventListener('click', this.handleClick);
        document.addEventListener('visibilitychange', this.handleVisibilityChange);
        
        // Fast Loop 시작
        this.startFastLoop();
        
        // 초기 상태 조회
        this.getState();
        
        return this;
    }
    
    stop() {
        this.log('🛑 HybridStateTracker 중지');
        
        // 이벤트 리스너 제거
        document.removeEventListener('mousemove', this.handleMouseMove);
        document.removeEventListener('scroll', this.handleScroll);
        document.removeEventListener('keydown', this.handleKeyDown);
        document.removeEventListener('click', this.handleClick);
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        
        // Fast Loop 중지
        this.stopFastLoop();
        
        return this;
    }
    
    // ============================================================
    // Fast Loop (0.5초 주기)
    // ============================================================
    
    startFastLoop() {
        if (this.fastLoopId) return;
        
        this.fastLoopId = setInterval(() => {
            this.runFastLoop();
        }, this.config.fastLoopInterval);
        
        this.log('⚡ Fast Loop 시작 (' + this.config.fastLoopInterval + 'ms)');
    }
    
    stopFastLoop() {
        if (this.fastLoopId) {
            clearInterval(this.fastLoopId);
            this.fastLoopId = null;
            this.log('⏸️ Fast Loop 중지');
        }
    }
    
    async runFastLoop() {
        // 센서 데이터 수집
        const sensorData = this.collectSensorData();
        
        // 서버에 전송
        try {
            const response = await this.apiCall('fast_loop', { sensor_data: sensorData });
            
            if (response.success) {
                this.updateState(response.state);
                
                // Ping 필요 여부 확인
                if (this.state.needsPing && !this.activePing) {
                    this.checkAndFirePing();
                }
            }
        } catch (error) {
            this.log('❌ Fast Loop 오류: ' + error.message, 'error');
        }
        
        // 센서 버퍼 초기화
        this.resetSensorBuffer();
    }
    
    collectSensorData() {
        const now = Date.now();
        const timeSinceLastActivity = (now - this.sensorBuffer.lastActivity) / 1000;
        
        // 마우스 속도 계산
        let mouseVelocity = 0;
        if (this.sensorBuffer.mousePositions.length >= 2) {
            const positions = this.sensorBuffer.mousePositions;
            let totalDistance = 0;
            for (let i = 1; i < positions.length; i++) {
                const dx = positions[i].x - positions[i-1].x;
                const dy = positions[i].y - positions[i-1].y;
                totalDistance += Math.sqrt(dx*dx + dy*dy);
            }
            const timeSpan = (positions[positions.length-1].t - positions[0].t) / 1000;
            mouseVelocity = timeSpan > 0 ? totalDistance / timeSpan / 100 : 0; // 정규화
        }
        
        // 스크롤 속도
        const scrollRate = this.sensorBuffer.scrollEvents.length / (this.config.fastLoopInterval / 1000);
        
        // 키 입력 속도
        const keystrokeRate = this.sensorBuffer.keystrokes.length / (this.config.fastLoopInterval / 1000);
        
        // 멈춤 시간
        const pauseDuration = timeSinceLastActivity;
        
        return {
            mouse_velocity: mouseVelocity,
            scroll_rate: scrollRate,
            keystroke_rate: keystrokeRate,
            pause_duration: pauseDuration,
            timestamp: now
        };
    }
    
    resetSensorBuffer() {
        this.sensorBuffer.mousePositions = [];
        this.sensorBuffer.scrollEvents = [];
        this.sensorBuffer.keystrokes = [];
    }
    
    // ============================================================
    // 이벤트 핸들러
    // ============================================================
    
    handleMouseMove(event) {
        const now = Date.now();
        this.sensorBuffer.mousePositions.push({
            x: event.clientX,
            y: event.clientY,
            t: now
        });
        
        // 최근 20개만 유지
        if (this.sensorBuffer.mousePositions.length > 20) {
            this.sensorBuffer.mousePositions.shift();
        }
        
        this.sensorBuffer.lastMouseMove = now;
        this.sensorBuffer.lastActivity = now;
        
        // Active Ping 반응 체크
        if (this.activePing) {
            this.handlePingResponse(true, (now - this.activePing.firedAt) / 1000);
        }
    }
    
    handleScroll(event) {
        this.sensorBuffer.scrollEvents.push(Date.now());
        this.sensorBuffer.lastActivity = Date.now();
        
        // Active Ping 반응 체크
        if (this.activePing) {
            this.handlePingResponse(true, (Date.now() - this.activePing.firedAt) / 1000);
        }
    }
    
    handleKeyDown(event) {
        this.sensorBuffer.keystrokes.push({
            key: event.key,
            t: Date.now()
        });
        this.sensorBuffer.lastActivity = Date.now();
        
        // Active Ping 반응 체크
        if (this.activePing) {
            this.handlePingResponse(true, (Date.now() - this.activePing.firedAt) / 1000);
        }
    }
    
    handleClick(event) {
        this.sensorBuffer.lastActivity = Date.now();
        
        // 클릭 이벤트 Kalman Correction
        const target = event.target;
        
        // 정답 버튼 클릭 감지 (예시)
        if (target.classList.contains('answer-correct')) {
            this.triggerEvent('correct_answer', { time_taken: this.getTimeOnPage() });
        } else if (target.classList.contains('answer-wrong')) {
            this.triggerEvent('wrong_answer', {});
        } else if (target.classList.contains('hint-button')) {
            this.triggerEvent('hint_click', {});
        } else if (target.classList.contains('skip-button')) {
            this.triggerEvent('skip_problem', {});
        } else {
            this.triggerEvent('click_problem', {});
        }
        
        // Active Ping 반응 체크
        if (this.activePing) {
            this.handlePingResponse(true, (Date.now() - this.activePing.firedAt) / 1000);
        }
    }
    
    handleVisibilityChange() {
        if (document.hidden) {
            this.triggerEvent('tab_switch', {});
        }
    }
    
    // ============================================================
    // Kalman Correction (이벤트 기반 보정)
    // ============================================================
    
    async triggerEvent(eventType, eventData = {}) {
        this.log('⚡ 이벤트 발생: ' + eventType);
        
        try {
            const response = await this.apiCall('kalman_correction', {
                event_type: eventType,
                event_data: eventData
            });
            
            if (response.success) {
                this.updateState(response.state);
                
                if (this.config.onCorrectionMade) {
                    this.config.onCorrectionMade(response.result, eventType);
                }
                
                this.log('⚖️ Kalman 보정 완료: ' + JSON.stringify(response.result));
            }
        } catch (error) {
            this.log('❌ Kalman 보정 오류: ' + error.message, 'error');
        }
    }
    
    // ============================================================
    // Active Ping (능동 관측)
    // ============================================================
    
    checkAndFirePing() {
        if (this.state.confidence >= this.config.pingThreshold) return;
        if (this.activePing) return;
        
        this.firePing(this.state.pingLevel || 1);
    }
    
    async firePing(level = 1) {
        this.log('📡 Active Ping 발사 (Level ' + level + ')');
        
        try {
            const response = await this.apiCall('fire_ping', { level });
            
            if (response.success) {
                this.activePing = {
                    id: response.result.ping.id,
                    level: level,
                    firedAt: Date.now(),
                    instruction: response.result.instruction
                };
                
                // 핑 UI 표시
                this.displayPing(response.result.instruction);
                
                if (this.config.onPingFired) {
                    this.config.onPingFired(this.activePing);
                }
                
                // 타임아웃 설정 (5초 후 무반응 처리)
                setTimeout(() => {
                    if (this.activePing && this.activePing.id === response.result.ping.id) {
                        this.handlePingResponse(false, 5);
                    }
                }, 5000);
            }
        } catch (error) {
            this.log('❌ Ping 발사 오류: ' + error.message, 'error');
        }
    }
    
    async handlePingResponse(responded, responseTime) {
        if (!this.activePing) return;
        
        const pingId = this.activePing.id;
        this.hidePing();
        
        this.log(responded ? '✅ Ping 반응 감지' : '❌ Ping 무반응');
        
        try {
            const response = await this.apiCall('ping_response', {
                ping_id: pingId,
                responded: responded,
                response_time: responseTime
            });
            
            if (response.success) {
                this.updateState(response.state);
                this.activePing = null;
            }
        } catch (error) {
            this.log('❌ Ping 반응 처리 오류: ' + error.message, 'error');
            this.activePing = null;
        }
    }
    
    displayPing(instruction) {
        // 기존 핑 제거
        this.hidePing();
        
        const pingEl = document.createElement('div');
        pingEl.id = 'active-ping-display';
        pingEl.className = 'active-ping-' + instruction.type;
        
        switch (instruction.type) {
            case 'highlight':
                // 형광펜 효과
                pingEl.innerHTML = `
                    <style>
                        .ping-highlight {
                            animation: ping-glow 0.5s ease-in-out;
                        }
                        @keyframes ping-glow {
                            0%, 100% { background-color: transparent; }
                            50% { background-color: rgba(255, 255, 0, 0.3); }
                        }
                    </style>
                `;
                // 중요 키워드에 하이라이트 적용
                document.querySelectorAll('.important-keyword, .key-concept').forEach(el => {
                    el.classList.add('ping-highlight');
                });
                break;
                
            case 'character_bubble':
                // 캐릭터 말풍선
                pingEl.innerHTML = `
                    <div style="
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background: linear-gradient(135deg, #6366f1, #8b5cf6);
                        color: white;
                        padding: 15px 20px;
                        border-radius: 20px;
                        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
                        animation: ping-bounce 0.5s ease-out;
                        z-index: 10000;
                        max-width: 300px;
                    ">
                        <div style="font-size: 1.5rem; margin-bottom: 5px;">🤖</div>
                        <div>${instruction.message || '음, 여기가 중요한 부분이야!'}</div>
                    </div>
                    <style>
                        @keyframes ping-bounce {
                            0% { transform: scale(0) translateY(50px); opacity: 0; }
                            50% { transform: scale(1.1) translateY(-10px); }
                            100% { transform: scale(1) translateY(0); opacity: 1; }
                        }
                    </style>
                `;
                break;
                
            case 'modal_question':
                // 모달 질문
                pingEl.innerHTML = `
                    <div style="
                        position: fixed;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background: rgba(0,0,0,0.5);
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        z-index: 10000;
                    ">
                        <div style="
                            background: white;
                            padding: 30px;
                            border-radius: 16px;
                            text-align: center;
                            max-width: 400px;
                        ">
                            <div style="font-size: 3rem; margin-bottom: 15px;">🤔</div>
                            <div style="font-size: 1.2rem; margin-bottom: 20px; color: #333;">
                                ${instruction.message || '지금 어떤 상태야?'}
                            </div>
                            <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                                ${(instruction.options || ['좋아요', '어려워요', '휴식이 필요해요']).map((opt, i) => `
                                    <button onclick="window.hybridTracker.respondToPing('${opt}')" style="
                                        padding: 10px 20px;
                                        border: 2px solid #6366f1;
                                        background: ${i === 0 ? '#6366f1' : 'white'};
                                        color: ${i === 0 ? 'white' : '#6366f1'};
                                        border-radius: 25px;
                                        cursor: pointer;
                                        font-size: 1rem;
                                    ">${opt}</button>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
                break;
        }
        
        document.body.appendChild(pingEl);
        
        // 자동 숨김 (modal_question 제외)
        if (instruction.type !== 'modal_question' && instruction.duration_ms) {
            setTimeout(() => this.hidePing(), instruction.duration_ms);
        }
    }
    
    hidePing() {
        const existing = document.getElementById('active-ping-display');
        if (existing) {
            existing.remove();
        }
        document.querySelectorAll('.ping-highlight').forEach(el => {
            el.classList.remove('ping-highlight');
        });
    }
    
    respondToPing(response) {
        this.hidePing();
        this.handlePingResponse(true, (Date.now() - (this.activePing?.firedAt || Date.now())) / 1000);
    }
    
    // ============================================================
    // 유틸리티
    // ============================================================
    
    async apiCall(action, data = {}) {
        const response = await fetch(this.config.apiEndpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action,
                user_id: this.config.userId,
                ...data
            })
        });
        
        if (!response.ok) {
            throw new Error('API 요청 실패: ' + response.status);
        }
        
        return response.json();
    }
    
    async getState() {
        try {
            const response = await this.apiCall('get_state');
            if (response.success) {
                this.updateState(response.state);
            }
        } catch (error) {
            this.log('❌ 상태 조회 오류: ' + error.message, 'error');
        }
    }
    
    updateState(newState) {
        const oldState = { ...this.state };
        this.state = { ...this.state, ...newState };
        
        if (this.config.onStateChange) {
            this.config.onStateChange(this.state, oldState);
        }
        
        this.log('📊 상태 업데이트: ' + JSON.stringify(this.state));
    }
    
    getTimeOnPage() {
        return (Date.now() - (window.pageLoadTime || Date.now())) / 1000;
    }
    
    log(message, level = 'info') {
        if (!this.config.debug) return;
        
        const prefix = '[HybridTracker]';
        switch (level) {
            case 'error':
                console.error(prefix, message);
                break;
            case 'warn':
                console.warn(prefix, message);
                break;
            default:
                console.log(prefix, message);
        }
    }
}

// 전역 인스턴스 생성
window.HybridStateTracker = HybridStateTracker;

// 페이지 로드 시간 기록
window.pageLoadTime = Date.now();

