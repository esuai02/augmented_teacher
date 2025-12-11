/**
 * AI 튜터 통합 스크립트
 * 
 * - learning_interface.js와 chat_interface.php를 연결
 * - 상호작용 엔진 API 호출
 * - 이벤트 흐름 관리
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

const AITutor = {
    // 설정
    config: {
        apiBase: '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api',
        studentId: null,
        contentId: null,
        unitName: '수학',
        debugMode: true
    },
    
    // 상태
    state: {
        sessionActive: false,
        currentStep: 1,
        pauseTimer: null,
        lastStrokeTime: 0,
        strokeCount: 0,
        eraseCount: 0,
        lastEraseTime: 0,
        currentEmotion: null,
        detectedPersona: null
    },
    
    // 타이머 설정
    timers: {
        pauseDetection: 3000, // 3초 멈춤 감지
        longPauseDetection: 10000 // 10초 긴 멈춤
    },
    
    /**
     * 초기화
     */
    init: function(config) {
        Object.assign(this.config, config);
        
        this.log('AITutor 초기화', this.config);
        
        // 채팅 인터페이스 초기화 (SidebarChatInterface)
        if (typeof SidebarChatInterface !== 'undefined') {
            SidebarChatInterface.init({
                context: {
                    unit_name: this.config.unitName
                }
            });
        }
        
        // 이벤트 리스너 설정
        this.setupEventListeners();
        
        // 세션은 채팅 열 때 시작하도록 변경 (자동 시작 안 함)
        // this.startSession();
    },
    
    /**
     * 이벤트 리스너 설정
     */
    setupEventListeners: function() {
        // 채팅 응답 이벤트
        document.addEventListener('ai-chat-response', (e) => {
            this.handleChatResponse(e.detail);
        });
        
        // 화이트보드 메시지 (postMessage)
        window.addEventListener('message', (e) => {
            if (e.data && (e.data.type === 'stroke_start' || e.data.type === 'stroke_end')) {
                this.handleWhiteboardEvent(e.data);
            }
        });
    },
    
    /**
     * 세션 시작
     */
    startSession: async function() {
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: 'session_start',
                unit_name: this.config.unitName
            });
            
            this.state.sessionActive = true;
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
            
            this.log('세션 시작됨');
        } catch (error) {
            this.log('세션 시작 실패', error);
        }
    },
    
    /**
     * 채팅 응답 처리
     */
    handleChatResponse: async function(responseData) {
        this.log('채팅 응답 수신', responseData);
        
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: responseData.type === 'emotion' ? 'emotion' : 
                           responseData.type === 'timeout' ? 'timeout' : 'user_response',
                value: responseData.value,
                label: responseData.label,
                next_rule: responseData.next_rule,
                emotion_type: responseData.value
            });
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
        } catch (error) {
            this.log('응답 처리 실패', error);
        }
    },
    
    /**
     * 화이트보드 이벤트 처리
     */
    handleWhiteboardEvent: function(eventData) {
        if (eventData.type === 'stroke_start') {
            this.state.strokeCount++;
            this.state.lastStrokeTime = eventData.timestamp;
            
            // 멈춤 타이머 리셋
            this.resetPauseTimer();
            
        } else if (eventData.type === 'stroke_end') {
            this.state.lastStrokeTime = eventData.timestamp;
            
            // 멈춤 감지 시작
            this.startPauseDetection();
        }
    },
    
    /**
     * 멈춤 타이머 리셋
     */
    resetPauseTimer: function() {
        if (this.state.pauseTimer) {
            clearTimeout(this.state.pauseTimer);
            this.state.pauseTimer = null;
        }
    },
    
    /**
     * 멈춤 감지 시작
     */
    startPauseDetection: function() {
        this.resetPauseTimer();
        
        // 짧은 멈춤 (3초)
        this.state.pauseTimer = setTimeout(() => {
            this.onWritingPause(3);
            
            // 긴 멈춤 (10초)
            this.state.pauseTimer = setTimeout(() => {
                this.onWritingPause(10);
            }, this.timers.longPauseDetection - this.timers.pauseDetection);
            
        }, this.timers.pauseDetection);
    },
    
    /**
     * 필기 멈춤 이벤트
     */
    onWritingPause: async function(seconds) {
        this.log(`필기 멈춤 감지: ${seconds}초`);
        
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: 'writing_pause',
                pause_duration: seconds,
                stroke_count: this.state.strokeCount
            });
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
        } catch (error) {
            this.log('멈춤 처리 실패', error);
        }
    },
    
    /**
     * 지우기 이벤트
     */
    onErase: async function() {
        const now = Date.now();
        
        // 30초 내 지우기만 카운트
        if (now - this.state.lastEraseTime > 30000) {
            this.state.eraseCount = 0;
        }
        
        this.state.eraseCount++;
        this.state.lastEraseTime = now;
        
        // 3회 이상 지우기
        if (this.state.eraseCount >= 3) {
            try {
                const response = await this.callAPI('process_interaction.php', {
                    event_type: 'writing_erase',
                    erase_count: this.state.eraseCount,
                    erase_time_window: (now - this.state.lastEraseTime) / 1000
                });
                
                if (response.success && response.data) {
                    this.processEngineResponse(response.data);
                    this.state.eraseCount = 0; // 리셋
                }
            } catch (error) {
                this.log('지우기 처리 실패', error);
            }
        }
    },
    
    /**
     * 제스처 이벤트
     */
    onGesture: async function(gestureType) {
        this.log('제스처 감지:', gestureType);
        
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: 'gesture',
                gesture_type: gestureType
            });
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
        } catch (error) {
            this.log('제스처 처리 실패', error);
        }
    },
    
    /**
     * 감정 선택 이벤트
     */
    onEmotionSelect: async function(emotionType) {
        this.log('감정 선택:', emotionType);
        this.state.currentEmotion = emotionType;
        
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: 'emotion',
                emotion_type: emotionType
            });
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
        } catch (error) {
            this.log('감정 처리 실패', error);
        }
    },
    
    /**
     * 단계 완료 이벤트
     */
    onStepComplete: async function(stepNumber) {
        this.log('단계 완료:', stepNumber);
        this.state.currentStep = stepNumber + 1;
        
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: 'step_complete',
                step_number: stepNumber
            });
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
        } catch (error) {
            this.log('단계 완료 처리 실패', error);
        }
    },
    
    /**
     * 답 제출 이벤트
     */
    onAnswerSubmit: async function(answer, result, errorType = null) {
        this.log('답 제출:', { answer, result, errorType });
        
        try {
            const response = await this.callAPI('process_interaction.php', {
                event_type: 'answer_submit',
                answer: answer,
                answer_result: result,
                error_type: errorType
            });
            
            if (response.success && response.data) {
                this.processEngineResponse(response.data);
            }
        } catch (error) {
            this.log('답 제출 처리 실패', error);
        }
    },
    
    /**
     * 엔진 응답 처리
     */
    processEngineResponse: function(data) {
        this.log('엔진 응답 처리', data);
        
        // 페르소나 업데이트
        if (data.persona) {
            this.state.detectedPersona = data.persona;
            if (typeof SidebarChatInterface !== 'undefined') {
                SidebarChatInterface.setPersonaStyle(data.persona);
            }
        }
        
        // 채팅 메시지 표시
        if (data.chat_messages && data.chat_messages.length > 0) {
            data.chat_messages.forEach((msg, index) => {
                setTimeout(() => {
                    if (typeof SidebarChatInterface !== 'undefined') {
                        // 마지막 메시지에만 옵션 연결
                        const options = (index === data.chat_messages.length - 1) ? data.options : null;
                        SidebarChatInterface.addAIMessage(msg.text, options);
                    }
                }, (msg.delay || 0) + (index * 500));
            });
        } else if (data.options) {
            // 메시지 없이 옵션만 있는 경우
            if (typeof SidebarChatInterface !== 'undefined') {
                SidebarChatInterface.addAIMessage(data.options.text || '선택해줘', data.options);
            }
        }
        
        // 시스템 액션 실행
        if (data.system_actions && data.system_actions.length > 0) {
            data.system_actions.forEach(action => {
                this.executeSystemAction(action);
            });
        }
        
        // 개입 활동 실행
        if (data.actions && data.actions.length > 0) {
            data.actions.forEach(intervention => {
                this.executeIntervention(intervention);
            });
        }
    },
    
    /**
     * 시스템 액션 실행
     */
    executeSystemAction: function(action) {
        this.log('시스템 액션 실행:', action);
        
        switch (action.action) {
            case 'SESSION_INIT':
                // 세션 초기화 완료
                break;
                
            case 'SHOW_PROBLEM':
                // 문제 표시
                this.showProblem();
                break;
                
            case 'SHOW_PROBLEM_PREVIEW':
                // 문제 미리보기
                this.showProblemPreview();
                break;
                
            case 'SHOW_CONCEPT_REVIEW':
                // 개념 복습 표시
                this.showConceptReview();
                break;
                
            case 'START_TIMER':
                // 타이머 시작
                this.startTimer();
                break;
                
            case 'STEP_ADVANCE':
                // 다음 단계로
                if (typeof handleStepClick !== 'undefined') {
                    handleStepClick(this.state.currentStep);
                }
                break;
                
            case 'ITEM_ADVANCE':
                // 다음 문항으로
                this.advanceToNextItem();
                break;
                
            case 'UPDATE_PROGRESS':
                // 진행률 업데이트
                this.updateProgressUI();
                break;
                
            case 'CAPTURE_WHITEBOARD':
                // 화이트보드 캡처
                this.captureWhiteboard();
                break;
                
            case 'ANALYZE_WRITING':
                // 필기 분석
                this.analyzeWriting();
                break;
                
            case 'CLEAR_WHITEBOARD':
                // 화이트보드 지우기
                this.clearWhiteboard();
                break;
                
            case 'PAUSE_SESSION':
                // 세션 일시정지
                this.pauseSession();
                break;
                
            case 'SHOW_BREATHING_EXERCISE':
                // 호흡 운동 표시
                if (typeof SidebarChatInterface !== 'undefined') {
                    SidebarChatInterface.showBreathingBar(5000);
                }
                break;
                
            case 'GET_CONTEXTUAL_HINT':
            case 'GET_FIRST_STEP_HINT':
                // 힌트 가져오기 (API 호출)
                this.getHint(action.action);
                break;
                
            case 'INCREASE_DIFFICULTY':
            case 'DECREASE_DIFFICULTY':
                // 난이도 조절
                this.adjustDifficulty(action.action);
                break;
                
            default:
                this.log('알 수 없는 시스템 액션:', action.action);
        }
    },
    
    /**
     * 개입 활동 실행
     */
    executeIntervention: function(intervention) {
        this.log('개입 활동 실행:', intervention);
        
        // 개입 활동 로깅
        this.logIntervention(intervention);
        
        // 특별한 UI 처리가 필요한 경우 여기서 처리
        // 대부분은 chat_messages로 처리됨
    },
    
    /**
     * API 호출
     */
    callAPI: async function(endpoint, data) {
        const url = `${this.config.apiBase}/${endpoint}`;
        
        const requestData = {
            student_id: this.config.studentId,
            content_id: this.config.contentId,
            ...data
        };
        
        this.log('API 호출:', url, requestData);
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(requestData)
        });
        
        const result = await response.json();
        this.log('API 응답:', result);
        
        return result;
    },
    
    // ====== 유틸리티 함수들 ======
    
    showProblem: function() {
        // 기존 인터페이스와 연동
        if (typeof showProblemView !== 'undefined') {
            showProblemView();
        }
    },
    
    showProblemPreview: function() {
        // 문제 미리보기
        if (typeof showProblemPreview !== 'undefined') {
            showProblemPreview();
        }
    },
    
    showConceptReview: function() {
        // 개념 복습
        this.log('개념 복습 표시');
    },
    
    startTimer: function() {
        // 타이머 시작
        this.log('타이머 시작');
    },
    
    advanceToNextItem: function() {
        // 다음 문항으로
        if (typeof moveToNextItem !== 'undefined') {
            moveToNextItem();
        }
    },
    
    updateProgressUI: function() {
        // 진행률 UI 업데이트
        if (typeof updateProgress !== 'undefined') {
            updateProgress();
        }
    },
    
    captureWhiteboard: function() {
        // 화이트보드 캡처
        if (typeof captureWhiteboardAndAnalyze !== 'undefined') {
            captureWhiteboardAndAnalyze();
        }
    },
    
    analyzeWriting: async function() {
        // 필기 분석
        if (typeof triggerWritingAnalysis !== 'undefined') {
            triggerWritingAnalysis();
        }
    },
    
    clearWhiteboard: function() {
        // 화이트보드 지우기
        const iframe = document.querySelector('#whiteboardFrame, .whiteboard-frame');
        if (iframe && iframe.contentWindow) {
            iframe.contentWindow.postMessage({ type: 'clear' }, '*');
        }
    },
    
    pauseSession: function() {
        this.state.sessionActive = false;
        this.log('세션 일시정지');
    },
    
    getHint: async function(hintType) {
        // 힌트 API 호출
        this.log('힌트 요청:', hintType);
    },
    
    adjustDifficulty: function(direction) {
        this.log('난이도 조절:', direction);
    },
    
    logIntervention: function(intervention) {
        // 개입 활동 로깅
        this.log('개입 로그:', intervention);
    },
    
    /**
     * 디버그 로그
     */
    log: function(...args) {
        if (this.config.debugMode) {
            console.log('[AITutor]', ...args);
        }
    }
};

// 전역 핸들러 연결
window.handleAIChatResponse = function(response) {
    AITutor.handleChatResponse(response);
};

// 외부에서 호출 가능한 함수들
window.aiTutorGesture = function(type) {
    AITutor.onGesture(type);
};

window.aiTutorEmotion = function(type) {
    AITutor.onEmotionSelect(type);
};

window.aiTutorStepComplete = function(step) {
    AITutor.onStepComplete(step);
};

window.aiTutorAnswer = function(answer, result, errorType) {
    AITutor.onAnswerSubmit(answer, result, errorType);
};

window.aiTutorErase = function() {
    AITutor.onErase();
};

