/**
 * OpenAI Realtime API 클라이언트
 * WebRTC 기반 실시간 음성 대화
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

const RealtimeTutor = {
    // 상태
    state: {
        sessionId: null,
        clientSecret: null,
        peerConnection: null,
        dataChannel: null,
        audioContext: null,
        audioElement: null,
        mediaStream: null,
        isConnected: false,
        isRecording: false,
        reconnectAttempts: 0,
        maxReconnectAttempts: 3,
        sessionStartTime: null,
        sessionTimeout: 60 * 60 * 1000 // 60분
    },
    
    // 설정
    config: {
        studentId: null,
        contentId: null,
        unitName: '수학',
        questionImage: null,
        solutionImage: null,
        currentStep: 1,
        currentEmotion: 'neutral',
        apiBase: '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/api'
    },
    
    /**
     * 초기화 및 세션 시작
     */
    async init(config) {
        Object.assign(this.config, config);

        try {
            // 1. 백엔드에서 세션 생성 및 client_secret 받기
            const sessionResponse = await this.createSession();

            this.state.sessionId = sessionResponse.session_id;
            this.state.clientSecret = sessionResponse.client_secret;
            this.state.sessionStartTime = Date.now();

            // 2. 마이크 권한 먼저 획득 (SDP offer 전에 트랙이 있어야 함)
            await this.acquireMicrophone();

            // 3. WebRTC 연결 설정 (마이크 트랙 포함하여 SDP offer 생성)
            await this.setupWebRTC();

            // 4. 세션 타임아웃 설정
            this.setupSessionTimeout();
            
            console.log('[RealtimeTutor] 초기화 완료');
            
            // 연결 성공 이벤트 발생
            this.dispatchEvent('connected');
            
        } catch (error) {
            console.error('[RealtimeTutor] 초기화 실패:', error);
            this.dispatchEvent('error', { error: error.message });
            throw error;
        }
    },
    
    /**
     * 백엔드에서 세션 생성
     */
    async createSession() {
        const response = await fetch(`${this.config.apiBase}/realtime_session.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                student_id: this.config.studentId,
                content_id: this.config.contentId,
                unit_name: this.config.unitName,
                question_image: this.config.questionImage,
                solution_image: this.config.solutionImage,
                current_step: this.config.currentStep,
                current_emotion: this.config.currentEmotion
            })
        });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error || `HTTP ${response.status}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || '세션 생성 실패');
        }
        
        return data;
    },
    
    /**
     * WebRTC 연결 설정
     */
    async setupWebRTC() {
        // RTCPeerConnection 생성
        this.state.peerConnection = new RTCPeerConnection({
            iceServers: [
                { urls: 'stun:stun.l.google.com:19302' },
                { urls: 'stun:stun1.l.google.com:19302' }
            ]
        });
        
        // 데이터 채널 생성 (이벤트 전송용) - OpenAI Realtime API 표준 이름 사용
        this.state.dataChannel = this.state.peerConnection.createDataChannel('oai-events', {
            ordered: true
        });
        
        this.state.dataChannel.onopen = () => {
            console.log('[RealtimeTutor] 데이터 채널 연결됨 (oai-events)');
            this.state.isConnected = true;
            this.dispatchEvent('dataChannelOpen');

            // 데이터 채널이 열리면 ICE 연결 상태 확인 후 초기 인사 요청
            const iceState = this.state.peerConnection?.iceConnectionState;
            console.log('[RealtimeTutor] 데이터 채널 열림 시점 ICE 상태:', iceState);

            if (iceState === 'connected' || iceState === 'completed') {
                // ICE가 이미 연결되어 있으면 바로 초기 인사 요청
                setTimeout(() => {
                    this.requestInitialGreeting();
                }, 500);
            }
            // ICE가 아직 연결 중이면 oniceconnectionstatechange에서 처리
        };
        
        this.state.dataChannel.onmessage = (event) => {
            try {
                const eventData = JSON.parse(event.data);
                this.handleRealtimeEvent(eventData);
            } catch (e) {
                console.error('[RealtimeTutor] 이벤트 파싱 오류:', e);
            }
        };
        
        this.state.dataChannel.onerror = (error) => {
            console.error('[RealtimeTutor] 데이터 채널 오류:', error);
            this.dispatchEvent('error', { error: '데이터 채널 오류' });
        };
        
        this.state.dataChannel.onclose = () => {
            console.log('[RealtimeTutor] 데이터 채널 닫힘');
            this.state.isConnected = false;
            this.dispatchEvent('dataChannelClose');
        };
        
        // ICE candidate 처리
        this.state.peerConnection.onicecandidate = (event) => {
            if (event.candidate) {
                // OpenAI Realtime API에 ICE candidate 전송
                this.sendIceCandidate(event.candidate);
            }
        };
        
        // ICE 연결 상태 변경 감지
        this.state.peerConnection.oniceconnectionstatechange = () => {
            const state = this.state.peerConnection.iceConnectionState;
            console.log('[RealtimeTutor] ICE 연결 상태:', state);
            
            if (state === 'failed' || state === 'disconnected') {
                this.handleConnectionLoss();
            } else if (state === 'connected' || state === 'completed') {
                this.state.reconnectAttempts = 0;
                console.log('[RealtimeTutor] WebRTC 연결 완료');
                
                // 연결 완료 후 초기 인사 요청 (DataChannel이 열려있고 연결이 완료된 경우)
                if (this.state.dataChannel && this.state.dataChannel.readyState === 'open') {
                    setTimeout(() => {
                        this.requestInitialGreeting();
                    }, 1000);
                }
            }
        };
        
        // 오디오 트랙 수신 처리
        this.state.peerConnection.ontrack = (event) => {
            console.log('[RealtimeTutor] 오디오 트랙 수신:', event.track.kind, event.streams.length);

            // 이미 오디오 엘리먼트가 있으면 재사용
            if (!this.state.audioElement) {
                this.state.audioElement = new Audio();
                this.state.audioElement.autoplay = true;

                // 오디오 재생 성공/실패 이벤트
                this.state.audioElement.addEventListener('play', () => {
                    console.log('[RealtimeTutor] 오디오 재생 시작됨');
                    this.dispatchEvent('audioPlaying');
                });

                this.state.audioElement.addEventListener('error', (e) => {
                    console.error('[RealtimeTutor] 오디오 재생 오류:', e);
                    this.dispatchEvent('error', { error: '오디오 재생 실패' });
                });
            }

            // 스트림 연결
            this.state.audioElement.srcObject = event.streams[0];

            // 재생 시도
            this.state.audioElement.play().catch(e => {
                console.error('[RealtimeTutor] 오디오 재생 실패:', e);
                // 사용자 상호작용 후 재생 가능하도록 안내
                console.warn('[RealtimeTutor] 사용자 상호작용이 필요할 수 있습니다');
            });
        };

        // ★ 중요: 마이크 트랙을 SDP offer 생성 전에 추가해야 함
        if (this.state.mediaStream) {
            console.log('[RealtimeTutor] 마이크 트랙 추가 중...');
            this.state.mediaStream.getTracks().forEach(track => {
                console.log('[RealtimeTutor] 트랙 추가:', track.kind, track.label);
                this.state.peerConnection.addTrack(track, this.state.mediaStream);
            });
            console.log('[RealtimeTutor] 마이크 트랙 추가 완료');
        } else {
            console.warn('[RealtimeTutor] 마이크 스트림이 없습니다!');
        }

        // SDP offer 생성 및 전송
        const offer = await this.state.peerConnection.createOffer({
            offerToReceiveAudio: true,
            offerToReceiveVideo: false
        });

        console.log('[RealtimeTutor] SDP offer 생성됨, 오디오 트랙 포함 여부 확인...');
        console.log('[RealtimeTutor] SDP:', offer.sdp.includes('m=audio') ? '오디오 섹션 있음' : '오디오 섹션 없음');

        await this.state.peerConnection.setLocalDescription(offer);

        // OpenAI Realtime API에 SDP offer 전송
        await this.sendSdpOffer(offer);
    },
    
    /**
     * SDP offer를 OpenAI Realtime API에 전송
     * 올바른 방식: SDP 문자열을 직접 body로 전송 (Content-Type: application/sdp)
     */
    async sendSdpOffer(offer) {
        try {
            const model = 'gpt-4o-realtime-preview-2024-12-17';
            const baseUrl = 'https://api.openai.com/v1/realtime';
            
            console.log('[RealtimeTutor] SDP offer 전송 중...');
            console.log('[RealtimeTutor] Model:', model);
            console.log('[RealtimeTutor] SDP 길이:', offer.sdp.length);
            
            // SDP 문자열을 직접 body로 전송 (Content-Type: application/sdp)
            const response = await fetch(`${baseUrl}?model=${model}`, {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${this.state.clientSecret}`,
                    'Content-Type': 'application/sdp'
                },
                body: offer.sdp
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('[RealtimeTutor] API 응답 오류:', errorText);
                console.error('[RealtimeTutor] 응답 상태:', response.status, response.statusText);
                throw new Error(errorText || `HTTP ${response.status}`);
            }
            
            // 응답은 SDP 문자열 (text/plain)
            const sdpAnswer = await response.text();
            console.log('[RealtimeTutor] SDP answer 수신 완료, 길이:', sdpAnswer.length);
            
            // SDP answer 처리
            await this.state.peerConnection.setRemoteDescription({
                type: 'answer',
                sdp: sdpAnswer
            });
            
            console.log('[RealtimeTutor] RemoteDescription 설정 완료');
            
        } catch (error) {
            console.error('[RealtimeTutor] SDP offer 전송 실패:', error);
            throw error;
        }
    },
    
    /**
     * ICE candidate 전송
     */
    async sendIceCandidate(candidate) {
        if (!this.state.isConnected || !this.state.dataChannel) {
            return;
        }
        
        try {
            // 데이터 채널을 통해 ICE candidate 전송
            this.state.dataChannel.send(JSON.stringify({
                type: 'ice_candidate',
                candidate: candidate.candidate,
                sdpMLineIndex: candidate.sdpMLineIndex,
                sdpMid: candidate.sdpMid
            }));
        } catch (error) {
            console.error('[RealtimeTutor] ICE candidate 전송 실패:', error);
        }
    },
    
    /**
     * AI에게 초기 인사 요청
     */
    requestInitialGreeting() {
        if (!this.state.dataChannel || this.state.dataChannel.readyState !== 'open') {
            console.warn('[RealtimeTutor] 데이터 채널이 열려있지 않음, 상태:', this.state.dataChannel?.readyState);
            return;
        }
        
        console.log('[RealtimeTutor] AI에게 초기 인사 요청 중...');
        
        try {
            // OpenAI Realtime API 표준 이벤트 형식으로 응답 생성 요청
            const event = {
                type: 'response.create',
                response: {
                    modalities: ['text', 'audio']
                }
            };
            
            const eventString = JSON.stringify(event);
            console.log('[RealtimeTutor] 전송할 이벤트:', eventString);
            
            this.state.dataChannel.send(eventString);
            
            console.log('[RealtimeTutor] 초기 인사 요청 전송 완료');
        } catch (error) {
            console.error('[RealtimeTutor] 초기 인사 요청 실패:', error);
        }
    },
    
    /**
     * 마이크 권한 획득 (SDP offer 전에 호출해야 함)
     */
    async acquireMicrophone() {
        try {
            console.log('[RealtimeTutor] 마이크 권한 요청 중...');

            // 마이크 권한 요청
            this.state.mediaStream = await navigator.mediaDevices.getUserMedia({
                audio: {
                    sampleRate: 24000,  // OpenAI Realtime API 권장 샘플레이트
                    channelCount: 1,
                    echoCancellation: true,
                    noiseSuppression: true,
                    autoGainControl: true
                }
            });

            // 오디오 컨텍스트 생성
            this.state.audioContext = new (window.AudioContext || window.webkitAudioContext)({
                sampleRate: 24000
            });

            this.state.isRecording = true;
            console.log('[RealtimeTutor] 마이크 권한 획득 완료');

            // 마이크 시작 이벤트
            this.dispatchEvent('recordingStarted');

        } catch (error) {
            console.error('[RealtimeTutor] 마이크 권한 획득 실패:', error);

            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                throw new Error('마이크 권한이 거부되었습니다. 브라우저 설정에서 마이크 권한을 허용해주세요.');
            } else if (error.name === 'NotFoundError') {
                throw new Error('마이크를 찾을 수 없습니다. 마이크가 연결되어 있는지 확인해주세요.');
            } else {
                throw new Error('마이크 권한 획득 실패: ' + error.message);
            }
        }
    },
    
    /**
     * Realtime 이벤트 처리
     */
    handleRealtimeEvent(event) {
        // 상세 로그 (디버깅용)
        console.log('[RealtimeTutor] 이벤트 수신:', event.type, JSON.stringify(event).substring(0, 500));

        switch (event.type) {
            case 'session.created':
                console.log('[RealtimeTutor] 세션 생성됨:', event.session?.id);
                break;

            case 'session.updated':
                console.log('[RealtimeTutor] 세션 업데이트됨');
                break;

            case 'conversation.item.created':
                console.log('[RealtimeTutor] 대화 아이템 생성:', event.item?.role, event.item?.type);
                if (event.item && event.item.role === 'assistant') {
                    // AI 응답 텍스트 표시
                    const textContent = event.item.content?.find(c => c.type === 'text');
                    if (textContent && textContent.text) {
                        this.displayAssistantMessage(textContent.text);
                    }
                }
                break;

            case 'response.created':
                console.log('[RealtimeTutor] 응답 생성 시작:', event.response?.id, event.response?.status);
                break;

            case 'response.output_item.added':
                console.log('[RealtimeTutor] 출력 아이템 추가:', event.item?.type);
                break;

            case 'response.content_part.added':
                console.log('[RealtimeTutor] 콘텐츠 파트 추가:', event.part?.type);
                break;

            case 'response.audio_transcript.delta':
                // 실시간 음성 전사 업데이트
                if (event.delta) {
                    console.log('[RealtimeTutor] 전사 델타:', event.delta);
                    this.updateTranscript(event.delta);
                }
                break;

            case 'response.audio.delta':
                // 오디오 데이터 수신 (이건 WebRTC로 자동 처리됨)
                console.log('[RealtimeTutor] 오디오 델타 수신');
                break;

            case 'response.audio_transcript.done':
                // 음성 전사 완료
                console.log('[RealtimeTutor] 전사 완료:', event.transcript);
                if (event.transcript) {
                    this.finalizeTranscript(event.transcript);
                }
                break;

            case 'response.output_item.done':
                console.log('[RealtimeTutor] 출력 아이템 완료');
                break;

            case 'response.content_part.done':
                console.log('[RealtimeTutor] 콘텐츠 파트 완료');
                break;

            case 'response.done':
                // 응답 완료 - 상세 정보 출력
                console.log('[RealtimeTutor] 응답 완료:', event.response?.status, event.response?.status_details);
                if (event.response?.output) {
                    console.log('[RealtimeTutor] 응답 출력:', JSON.stringify(event.response.output));
                }
                this.dispatchEvent('responseDone');
                break;

            case 'error':
                // 오류 발생
                console.error('[RealtimeTutor] Realtime API 오류:', event.error?.type, event.error?.message, event);
                this.dispatchEvent('error', { error: event.error?.message || '알 수 없는 오류' });
                break;

            case 'input_audio_buffer.speech_started':
                console.log('[RealtimeTutor] 사용자 음성 감지 시작');
                break;

            case 'input_audio_buffer.speech_stopped':
                console.log('[RealtimeTutor] 사용자 음성 감지 종료');
                break;

            case 'input_audio_buffer.committed':
                console.log('[RealtimeTutor] 오디오 버퍼 커밋됨');
                break;

            default:
                console.log('[RealtimeTutor] 알 수 없는 이벤트:', event.type);
        }
    },
    
    /**
     * AI 메시지 표시
     */
    displayAssistantMessage(text) {
        // chat_interface.php의 SidebarChatInterface와 연동 (선택사항)
        if (typeof SidebarChatInterface !== 'undefined') {
            SidebarChatInterface.addAIMessage(text);
        }
        
        // 메시지 표시 이벤트
        this.dispatchEvent('message', { text: text });
    },
    
    /**
     * 전사 업데이트 (선택사항)
     */
    updateTranscript(delta) {
        // 실시간 전사 표시 로직 (필요시 구현)
        this.dispatchEvent('transcriptUpdate', { delta: delta });
    },
    
    /**
     * 전사 완료 (선택사항)
     */
    finalizeTranscript(transcript) {
        // 전사 완료 처리 (필요시 구현)
        this.dispatchEvent('transcriptDone', { transcript: transcript });
    },
    
    /**
     * 연결 끊김 처리
     */
    handleConnectionLoss() {
        if (this.state.reconnectAttempts < this.state.maxReconnectAttempts) {
            this.state.reconnectAttempts++;
            console.log(`[RealtimeTutor] 재연결 시도 ${this.state.reconnectAttempts}/${this.state.maxReconnectAttempts}`);
            
            setTimeout(() => {
                this.reconnect();
            }, 2000 * this.state.reconnectAttempts); // 지수 백오프
        } else {
            console.error('[RealtimeTutor] 재연결 실패');
            this.dispatchEvent('error', { error: '연결이 끊어졌습니다. 세션을 다시 시작해주세요.' });
        }
    },
    
    /**
     * 재연결 시도
     */
    async reconnect() {
        try {
            await this.setupWebRTC();
        } catch (error) {
            console.error('[RealtimeTutor] 재연결 실패:', error);
            this.handleConnectionLoss();
        }
    },
    
    /**
     * 세션 타임아웃 설정
     */
    setupSessionTimeout() {
        setTimeout(() => {
            if (this.state.isConnected) {
                console.log('[RealtimeTutor] 세션 타임아웃');
                this.dispatchEvent('timeout');
                this.stop();
            }
        }, this.state.sessionTimeout);
    },
    
    /**
     * 세션 종료
     */
    async stop() {
        try {
            // 오디오 엘리먼트 정리
            if (this.state.audioElement) {
                this.state.audioElement.pause();
                this.state.audioElement.srcObject = null;
                this.state.audioElement = null;
            }
            
            // 오디오 스트림 정리
            if (this.state.mediaStream) {
                this.state.mediaStream.getTracks().forEach(track => track.stop());
                this.state.mediaStream = null;
            }
            
            // DataChannel 정리
            if (this.state.dataChannel) {
                this.state.dataChannel.close();
                this.state.dataChannel = null;
            }
            
            // PeerConnection 정리
            if (this.state.peerConnection) {
                this.state.peerConnection.close();
                this.state.peerConnection = null;
            }
            
            // 오디오 컨텍스트 정리
            if (this.state.audioContext) {
                await this.state.audioContext.close();
                this.state.audioContext = null;
            }
            
            this.state.isConnected = false;
            this.state.isRecording = false;
            this.state.sessionId = null;
            this.state.clientSecret = null;
            
            console.log('[RealtimeTutor] 세션 종료됨');
            this.dispatchEvent('stopped');
            
        } catch (error) {
            console.error('[RealtimeTutor] 세션 종료 오류:', error);
        }
    },
    
    /**
     * 이벤트 디스패치
     */
    dispatchEvent(eventName, data = {}) {
        const event = new CustomEvent(`realtime-tutor-${eventName}`, {
            detail: data
        });
        document.dispatchEvent(event);
    }
};

// 전역 함수로 노출
window.startRealtimeTutor = async function(config) {
    await RealtimeTutor.init(config);
};

window.stopRealtimeTutor = function() {
    RealtimeTutor.stop();
};

window.getRealtimeTutorState = function() {
    return {
        isConnected: RealtimeTutor.state.isConnected,
        isRecording: RealtimeTutor.state.isRecording,
        sessionId: RealtimeTutor.state.sessionId
    };
};

