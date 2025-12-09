/**
 * brain_client.js - 실시간 AI 튜터 클라이언트 라이브러리
 * 
 * Brain Layer API와 통신하여 실시간 AI 튜터 기능 제공
 * SSE 스트리밍, 음성 재생, 상태 관리 포함
 * 
 * @package     AugmentedTeacher
 * @subpackage  Brain
 * @author      AI Tutor Development Team
 * @version     1.0.0
 * @created     2025-12-08
 * 
 * 사용법:
 * ```javascript
 * const tutor = new BrainClient({ studentId: 123 });
 * await tutor.start();
 * 
 * // 이벤트 리스닝
 * tutor.on('speak', (data) => console.log('튜터 발화:', data.text));
 * 
 * // 폴링 시작
 * tutor.startPolling();
 * ```
 */

class BrainClient {
    /**
     * 생성자
     * @param {Object} options 설정
     * @param {number} options.studentId 학생 ID
     * @param {string} options.baseUrl API 기본 URL
     * @param {string} options.mode 튜터 모드 (active, guide, observe, silent)
     */
    constructor(options = {}) {
        this.studentId = options.studentId || null;
        this.baseUrl = options.baseUrl || '/moodle/local/augmented_teacher/alt42/brain';
        this.mode = options.mode || 'guide';
        
        // 상태
        this.isActive = false;
        this.isPolling = false;
        this.audioQueue = [];
        this.isPlaying = false;
        
        // 이벤트 핸들러
        this.eventHandlers = {};
        
        // 오디오 컨텍스트
        this.audioContext = null;
        
        // 폴링 설정
        this.pollingInterval = 2000;  // 2초
        this.pollTimer = null;
        
        // SSE 연결
        this.eventSource = null;
    }

    // =========================================================================
    // 세션 관리
    // =========================================================================

    /**
     * 세션 시작
     */
    async start() {
        const response = await this.apiCall('brain_api.php', 'start', {
            student_id: this.studentId,
            mode: this.mode
        });
        
        if (response.success) {
            this.isActive = true;
            this.emit('started', response.data);
        }
        
        return response;
    }

    /**
     * 세션 종료
     */
    async stop() {
        this.stopPolling();
        this.closeStream();
        
        const response = await this.apiCall('brain_api.php', 'stop');
        
        this.isActive = false;
        this.emit('stopped', response.data);
        
        return response;
    }

    /**
     * 모드 변경
     */
    async setMode(mode) {
        this.mode = mode;
        return await this.apiCall('brain_api.php', 'set_mode', { mode });
    }

    // =========================================================================
    // 실시간 판단 (폴링)
    // =========================================================================

    /**
     * 단일 tick 실행
     */
    async tick(event = {}) {
        const response = await this.apiCall('brain_api.php', 'tick', { event });
        
        if (response.success && response.data) {
            this.handleTutorAction(response.data);
        }
        
        return response;
    }

    /**
     * 폴링 시작
     */
    startPolling(interval = 2000) {
        if (this.isPolling) return;
        
        this.isPolling = true;
        this.pollingInterval = interval;
        
        const poll = async () => {
            if (!this.isPolling) return;
            
            try {
                await this.tick();
            } catch (e) {
                console.error('Polling error:', e);
            }
            
            this.pollTimer = setTimeout(poll, this.pollingInterval);
        };
        
        poll();
        this.emit('polling_started');
    }

    /**
     * 폴링 중지
     */
    stopPolling() {
        this.isPolling = false;
        if (this.pollTimer) {
            clearTimeout(this.pollTimer);
            this.pollTimer = null;
        }
        this.emit('polling_stopped');
    }

    // =========================================================================
    // 스트리밍 (SSE)
    // =========================================================================

    /**
     * 스트리밍 시작
     */
    startStream(prompt = '', options = {}) {
        this.closeStream();
        
        const params = new URLSearchParams({
            action: options.action || 'stream',
            student_id: this.studentId,
            prompt: prompt,
            ...options
        });
        
        const url = `${this.baseUrl}/brain_stream_api.php?${params}`;
        this.eventSource = new EventSource(url);
        
        // 이벤트 핸들러 등록
        this.eventSource.addEventListener('start', (e) => {
            this.emit('stream_start', JSON.parse(e.data));
        });
        
        this.eventSource.addEventListener('token', (e) => {
            const data = JSON.parse(e.data);
            this.emit('token', data);
        });
        
        this.eventSource.addEventListener('chunk', (e) => {
            const data = JSON.parse(e.data);
            this.emit('chunk', data);
            
            // 오디오가 있으면 큐에 추가
            if (data.audio) {
                this.queueAudio(data.audio);
            }
        });
        
        this.eventSource.addEventListener('decision', (e) => {
            const data = JSON.parse(e.data);
            this.emit('decision', data);
        });
        
        this.eventSource.addEventListener('complete', (e) => {
            const data = JSON.parse(e.data);
            this.emit('stream_complete', data);
        });
        
        this.eventSource.addEventListener('done', (e) => {
            this.closeStream();
            this.emit('stream_done');
        });
        
        this.eventSource.addEventListener('error', (e) => {
            console.error('SSE Error:', e);
            this.emit('stream_error', e);
        });
        
        this.eventSource.onerror = (e) => {
            if (this.eventSource.readyState === EventSource.CLOSED) {
                this.emit('stream_closed');
            }
        };
        
        return this.eventSource;
    }

    /**
     * Brain 기반 개입 스트리밍
     */
    streamIntervention() {
        return this.startStream('', { action: 'intervene' });
    }

    /**
     * 스트리밍 연결 종료
     */
    closeStream() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
        }
    }

    // =========================================================================
    // 오디오 재생
    // =========================================================================

    /**
     * 오디오 컨텍스트 초기화
     */
    initAudioContext() {
        if (!this.audioContext) {
            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }
        return this.audioContext;
    }

    /**
     * 오디오 큐에 추가
     */
    queueAudio(base64Audio) {
        this.audioQueue.push(base64Audio);
        this.playNextAudio();
    }

    /**
     * 다음 오디오 재생
     */
    async playNextAudio() {
        if (this.isPlaying || this.audioQueue.length === 0) return;
        
        this.isPlaying = true;
        const base64Audio = this.audioQueue.shift();
        
        try {
            await this.playBase64Audio(base64Audio);
        } catch (e) {
            console.error('Audio playback error:', e);
        }
        
        this.isPlaying = false;
        this.emit('audio_ended');
        
        // 다음 오디오 재생
        if (this.audioQueue.length > 0) {
            this.playNextAudio();
        }
    }

    /**
     * Base64 오디오 재생
     */
    async playBase64Audio(base64Audio) {
        return new Promise((resolve, reject) => {
            const audio = new Audio('data:audio/mp3;base64,' + base64Audio);
            
            audio.onended = () => {
                resolve();
            };
            
            audio.onerror = (e) => {
                reject(e);
            };
            
            this.emit('audio_started');
            audio.play().catch(reject);
        });
    }

    /**
     * 오디오 큐 비우기
     */
    clearAudioQueue() {
        this.audioQueue = [];
    }

    // =========================================================================
    // 수동 제어
    // =========================================================================

    /**
     * 수동 발화
     */
    async speak(text, style = {}) {
        const response = await this.apiCall('brain_api.php', 'speak', {
            text,
            style
        });
        
        if (response.success && response.data.audio) {
            this.queueAudio(response.data.audio);
        }
        
        return response;
    }

    /**
     * 이벤트 전송
     */
    async sendEvent(eventType, payload = {}) {
        return await this.apiCall('brain_api.php', 'event', {
            event_type: eventType,
            payload
        });
    }

    // =========================================================================
    // 상태 조회
    // =========================================================================

    /**
     * 현재 상태 조회
     */
    async getState() {
        return await this.apiCall('brain_api.php', 'state');
    }

    /**
     * 파동함수 조회
     */
    async getWavefunctions() {
        return await this.apiCall('brain_api.php', 'wavefunctions');
    }

    /**
     * 디버그 정보 조회
     */
    async getDebug() {
        return await this.apiCall('brain_api.php', 'debug');
    }

    /**
     * 시스템 상태 조회
     */
    async getStatus() {
        return await this.apiCall('brain_api.php', 'status');
    }

    // =========================================================================
    // 이벤트 시스템
    // =========================================================================

    /**
     * 이벤트 리스너 등록
     */
    on(event, handler) {
        if (!this.eventHandlers[event]) {
            this.eventHandlers[event] = [];
        }
        this.eventHandlers[event].push(handler);
        return this;
    }

    /**
     * 이벤트 리스너 제거
     */
    off(event, handler) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event] = this.eventHandlers[event].filter(h => h !== handler);
        }
        return this;
    }

    /**
     * 이벤트 발생
     */
    emit(event, data = null) {
        if (this.eventHandlers[event]) {
            this.eventHandlers[event].forEach(handler => {
                try {
                    handler(data);
                } catch (e) {
                    console.error(`Event handler error (${event}):`, e);
                }
            });
        }
    }

    // =========================================================================
    // 내부 액션 처리
    // =========================================================================

    /**
     * 튜터 액션 처리
     */
    handleTutorAction(data) {
        switch (data.action) {
            case 'speak':
                this.emit('speak', data);
                if (data.audio) {
                    this.queueAudio(data.audio);
                }
                break;
                
            case 'backchannel':
                this.emit('backchannel', data);
                if (data.audio) {
                    this.queueAudio(data.audio);
                }
                break;
                
            case 'observe_alert':
                this.emit('alert', data);
                break;
                
            case 'none':
                this.emit('idle', data);
                break;
                
            case 'error':
                this.emit('error', data);
                break;
        }
    }

    // =========================================================================
    // API 호출
    // =========================================================================

    /**
     * API 호출
     */
    async apiCall(endpoint, action, data = {}) {
        const url = `${this.baseUrl}/${endpoint}?action=${action}`;
        
        const body = {
            student_id: this.studentId,
            ...data
        };
        
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body),
                credentials: 'include'
            });
            
            return await response.json();
        } catch (error) {
            console.error('API Error:', error);
            return {
                success: false,
                error: error.message
            };
        }
    }
}

// =========================================================================
// 유틸리티 함수
// =========================================================================

/**
 * 간단한 튜터 시작 헬퍼
 */
async function startBrainTutor(studentId, options = {}) {
    const tutor = new BrainClient({
        studentId,
        ...options
    });
    
    await tutor.start();
    
    // 기본 이벤트 로깅
    tutor.on('speak', (data) => console.log('🎙️ Tutor:', data.text));
    tutor.on('backchannel', (data) => console.log('💬 Backchannel:', data.text));
    tutor.on('error', (data) => console.error('❌ Error:', data));
    
    return tutor;
}

// 전역 export (브라우저용)
if (typeof window !== 'undefined') {
    window.BrainClient = BrainClient;
    window.startBrainTutor = startBrainTutor;
}

// ES 모듈 export
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { BrainClient, startBrainTutor };
}

