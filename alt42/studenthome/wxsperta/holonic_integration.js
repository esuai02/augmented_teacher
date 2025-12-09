/**
 * Holonic WXSPERTA Integration Script
 * 기존 index1~4.php, indexm.php 페이지에 주입하여 사용
 */

(function() {
    'use strict';
    
    // 설정
    const CONFIG = {
        bridgeUrl: '/studenthome/wxsperta/chat_bridge.php',
        approvalUrl: '/studenthome/wxsperta/approval_system.php',
        pollInterval: 10000, // 10초
        maxRetries: 3
    };
    
    // 현재 페이지 타입 감지
    const getPageType = () => {
        const path = window.location.pathname;
        const filename = path.split('/').pop();
        return filename.replace('.php', '') || 'index1';
    };
    
    // Holonic WXSPERTA 통합 클래스
    class HolonicIntegration {
        constructor() {
            this.pageType = getPageType();
            this.sessionId = this.generateSessionId();
            this.pendingApprovals = [];
            this.approvalCheckInterval = null;
            this.lastMessageTime = Date.now();
            
            this.init();
        }
        
        init() {
            console.log('Holonic WXSPERTA 통합 시작:', this.pageType);
            
            // 채팅 메시지 인터셉트
            this.interceptChatMessages();
            
            // 승인 패널 추가
            this.createApprovalPanel();
            
            // 주기적 승인 체크
            this.startApprovalCheck();
            
            // 이벤트 리스너
            this.attachEventListeners();
        }
        
        /**
         * 채팅 메시지 인터셉트
         */
        interceptChatMessages() {
            // 기존 send 함수 오버라이드
            const originalSend = window.sendMessage || window.send || function() {};
            
            window.sendMessage = window.send = (message) => {
                // 원본 함수 실행
                const result = originalSend(message);
                
                // Holonic 시스템으로 전송
                this.processMessage(message);
                
                return result;
            };
            
            // fetch/XMLHttpRequest 인터셉트
            this.interceptAjax();
        }
        
        /**
         * AJAX 요청 인터셉트
         */
        interceptAjax() {
            // Fetch API 인터셉트
            const originalFetch = window.fetch;
            window.fetch = (...args) => {
                const [url, options = {}] = args;
                
                // 채팅 관련 요청 감지
                if (this.isChatRequest(url)) {
                    this.interceptChatRequest(options);
                }
                
                return originalFetch.apply(window, args);
            };
            
            // XMLHttpRequest 인터셉트
            const originalXHR = window.XMLHttpRequest;
            window.XMLHttpRequest = function() {
                const xhr = new originalXHR();
                const originalSend = xhr.send;
                
                xhr.send = function(data) {
                    if (xhr._url && this.isChatRequest(xhr._url)) {
                        this.interceptChatRequest({ body: data });
                    }
                    return originalSend.apply(xhr, arguments);
                }.bind(this);
                
                const originalOpen = xhr.open;
                xhr.open = function(method, url) {
                    xhr._url = url;
                    return originalOpen.apply(xhr, arguments);
                };
                
                return xhr;
            }.bind(this);
        }
        
        /**
         * 채팅 요청인지 확인
         */
        isChatRequest(url) {
            const chatEndpoints = [
                'chat.php',
                'message.php',
                'send_message.php',
                'process_chat.php'
            ];
            
            return chatEndpoints.some(endpoint => url.includes(endpoint));
        }
        
        /**
         * 채팅 요청 인터셉트
         */
        interceptChatRequest(options) {
            try {
                let message = '';
                
                if (options.body) {
                    if (typeof options.body === 'string') {
                        const params = new URLSearchParams(options.body);
                        message = params.get('message') || params.get('text') || params.get('content') || '';
                    } else if (options.body instanceof FormData) {
                        message = options.body.get('message') || options.body.get('text') || '';
                    }
                }
                
                if (message) {
                    this.processMessage(message);
                }
            } catch (error) {
                console.error('채팅 인터셉트 오류:', error);
            }
        }
        
        /**
         * 메시지 처리
         */
        async processMessage(message) {
            if (!message || message.trim() === '') return;
            
            // 중복 방지 (1초 이내 동일 메시지)
            const now = Date.now();
            if (now - this.lastMessageTime < 1000) return;
            this.lastMessageTime = now;
            
            try {
                const formData = new FormData();
                formData.append('action', 'process_message');
                formData.append('message', message);
                formData.append('page_type', this.pageType);
                formData.append('context', JSON.stringify({
                    page_type: this.pageType,
                    timestamp: now,
                    session_id: this.sessionId
                }));
                
                const response = await fetch(CONFIG.bridgeUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 인사이트 처리
                    if (result.insights && result.insights.needs_update) {
                        this.handleInsights(result.insights);
                    }
                    
                    // 응답 표시 (옵션)
                    if (result.response && window.displayAIResponse) {
                        window.displayAIResponse(result.response);
                    }
                }
            } catch (error) {
                console.error('Holonic 메시지 처리 오류:', error);
            }
        }
        
        /**
         * 인사이트 처리
         */
        handleInsights(insights) {
            // 감정 상태 표시
            if (insights.emotion !== 'neutral') {
                this.showEmotionIndicator(insights.emotion);
            }
            
            // 학습 격차 알림
            if (insights.learning_gaps && insights.learning_gaps.length > 0) {
                this.showLearningGapNotification(insights.learning_gaps);
            }
            
            // 제안된 액션
            if (insights.suggested_actions && insights.suggested_actions.length > 0) {
                insights.suggested_actions.forEach(action => {
                    this.executeSuggestedAction(action);
                });
            }
        }
        
        /**
         * 승인 패널 생성
         */
        createApprovalPanel() {
            // 승인 배지
            const badge = document.createElement('div');
            badge.id = 'holonic-approval-badge';
            badge.className = 'holonic-approval-badge';
            badge.innerHTML = `
                <span class="badge-icon">🔔</span>
                <span class="badge-count">0</span>
            `;
            badge.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(45deg, #f59e0b, #ef4444);
                color: white;
                padding: 10px 15px;
                border-radius: 25px;
                cursor: pointer;
                display: none;
                align-items: center;
                gap: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 10000;
                animation: pulse 2s infinite;
            `;
            
            // 승인 패널
            const panel = document.createElement('div');
            panel.id = 'holonic-approval-panel';
            panel.className = 'holonic-approval-panel';
            panel.style.cssText = `
                position: fixed;
                top: 70px;
                right: 20px;
                width: 350px;
                max-height: 500px;
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                display: none;
                z-index: 9999;
                overflow: hidden;
            `;
            
            panel.innerHTML = `
                <div class="panel-header" style="padding: 15px; background: #f3f4f6; border-bottom: 1px solid #e5e7eb;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 600;">승인 대기 요청</h3>
                    <button class="close-btn" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 20px; cursor: pointer;">×</button>
                </div>
                <div class="panel-content" style="padding: 15px; max-height: 400px; overflow-y: auto;">
                    <div class="loading" style="text-align: center; color: #6b7280;">로딩 중...</div>
                </div>
            `;
            
            document.body.appendChild(badge);
            document.body.appendChild(panel);
            
            // CSS 애니메이션 추가
            const style = document.createElement('style');
            style.textContent = `
                @keyframes pulse {
                    0%, 100% { transform: scale(1); opacity: 1; }
                    50% { transform: scale(1.05); opacity: 0.9; }
                }
                
                .holonic-approval-item {
                    padding: 12px;
                    margin-bottom: 10px;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    background: #f9fafb;
                }
                
                .holonic-approval-item:hover {
                    background: #f3f4f6;
                }
                
                .approval-actions {
                    display: flex;
                    gap: 8px;
                    margin-top: 10px;
                }
                
                .approval-btn {
                    padding: 6px 12px;
                    border: none;
                    border-radius: 5px;
                    font-size: 14px;
                    cursor: pointer;
                    transition: all 0.2s;
                }
                
                .approve-btn {
                    background: #10b981;
                    color: white;
                }
                
                .approve-btn:hover {
                    background: #059669;
                }
                
                .reject-btn {
                    background: #ef4444;
                    color: white;
                }
                
                .reject-btn:hover {
                    background: #dc2626;
                }
            `;
            document.head.appendChild(style);
        }
        
        /**
         * 승인 체크 시작
         */
        startApprovalCheck() {
            // 초기 체크
            this.checkPendingApprovals();
            
            // 주기적 체크
            this.approvalCheckInterval = setInterval(() => {
                this.checkPendingApprovals();
            }, CONFIG.pollInterval);
        }
        
        /**
         * 승인 대기 확인
         */
        async checkPendingApprovals() {
            try {
                const response = await fetch(`${CONFIG.approvalUrl}?action=get_pending`, {
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success && data.requests) {
                    this.updateApprovalBadge(data.requests);
                }
            } catch (error) {
                console.error('승인 체크 오류:', error);
            }
        }
        
        /**
         * 승인 배지 업데이트
         */
        updateApprovalBadge(approvals) {
            const badge = document.getElementById('holonic-approval-badge');
            const count = approvals.length;
            
            if (count > 0) {
                badge.style.display = 'flex';
                badge.querySelector('.badge-count').textContent = count;
                
                // 새 승인이 있으면 알림
                if (count > this.pendingApprovals.length) {
                    this.showNotification('새로운 승인 요청이 있습니다!');
                }
            } else {
                badge.style.display = 'none';
            }
            
            this.pendingApprovals = approvals;
        }
        
        /**
         * 이벤트 리스너 연결
         */
        attachEventListeners() {
            // 승인 배지 클릭
            document.getElementById('holonic-approval-badge').addEventListener('click', () => {
                this.toggleApprovalPanel();
            });
            
            // 패널 닫기
            document.querySelector('#holonic-approval-panel .close-btn').addEventListener('click', () => {
                this.hideApprovalPanel();
            });
            
            // 페이지 언로드 시 정리
            window.addEventListener('beforeunload', () => {
                if (this.approvalCheckInterval) {
                    clearInterval(this.approvalCheckInterval);
                }
            });
        }
        
        /**
         * 승인 패널 토글
         */
        toggleApprovalPanel() {
            const panel = document.getElementById('holonic-approval-panel');
            if (panel.style.display === 'none' || !panel.style.display) {
                this.showApprovalPanel();
            } else {
                this.hideApprovalPanel();
            }
        }
        
        /**
         * 승인 패널 표시
         */
        showApprovalPanel() {
            const panel = document.getElementById('holonic-approval-panel');
            panel.style.display = 'block';
            
            // 승인 목록 렌더링
            this.renderApprovalList();
        }
        
        /**
         * 승인 패널 숨기기
         */
        hideApprovalPanel() {
            const panel = document.getElementById('holonic-approval-panel');
            panel.style.display = 'none';
        }
        
        /**
         * 승인 목록 렌더링
         */
        renderApprovalList() {
            const content = document.querySelector('#holonic-approval-panel .panel-content');
            
            if (this.pendingApprovals.length === 0) {
                content.innerHTML = '<p style="text-align: center; color: #6b7280;">승인 대기 중인 요청이 없습니다.</p>';
                return;
            }
            
            content.innerHTML = this.pendingApprovals.map(approval => `
                <div class="holonic-approval-item" data-id="${approval.id}">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <span style="font-size: 24px;">${approval.agent_icon || '🤖'}</span>
                        <div>
                            <div style="font-weight: 600; color: #1f2937;">${approval.agent_name}</div>
                            <div style="font-size: 12px; color: #6b7280;">${this.formatTime(approval.requested_at)}</div>
                        </div>
                    </div>
                    <p style="margin: 8px 0; color: #4b5563; font-size: 14px;">
                        ${approval.change_description}
                    </p>
                    <div class="approval-actions">
                        <button class="approval-btn approve-btn" onclick="holonicIntegration.handleApproval(${approval.id}, true)">
                            승인
                        </button>
                        <button class="approval-btn reject-btn" onclick="holonicIntegration.handleApproval(${approval.id}, false)">
                            거부
                        </button>
                    </div>
                </div>
            `).join('');
        }
        
        /**
         * 승인 처리
         */
        async handleApproval(requestId, approve) {
            try {
                const formData = new FormData();
                formData.append('action', approve ? 'approve' : 'reject');
                formData.append('request_id', requestId);
                
                if (!approve) {
                    const reason = prompt('거부 사유를 입력하세요:');
                    if (reason) {
                        formData.append('reason', reason);
                    }
                }
                
                const response = await fetch(CONFIG.approvalUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showNotification(approve ? '요청이 승인되었습니다!' : '요청이 거부되었습니다.');
                    this.checkPendingApprovals();
                } else {
                    this.showNotification('처리 중 오류가 발생했습니다.', 'error');
                }
            } catch (error) {
                console.error('승인 처리 오류:', error);
                this.showNotification('네트워크 오류가 발생했습니다.', 'error');
            }
        }
        
        /**
         * 알림 표시
         */
        showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                left: 50%;
                transform: translateX(-50%);
                padding: 15px 25px;
                background: ${type === 'error' ? '#ef4444' : '#3b82f6'};
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 10001;
                animation: slideDown 0.3s ease-out;
            `;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideUp 0.3s ease-in';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        /**
         * 감정 표시
         */
        showEmotionIndicator(emotion) {
            const indicator = document.createElement('div');
            indicator.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 20px;
                padding: 10px 20px;
                background: rgba(0,0,0,0.8);
                color: white;
                border-radius: 20px;
                font-size: 14px;
                z-index: 9998;
            `;
            
            const emotionEmojis = {
                frustrated: '😔',
                happy: '😊',
                confused: '😕',
                excited: '🎉'
            };
            
            indicator.innerHTML = `${emotionEmojis[emotion] || '😐'} 감정 상태: ${emotion}`;
            document.body.appendChild(indicator);
            
            setTimeout(() => indicator.remove(), 5000);
        }
        
        /**
         * 학습 격차 알림
         */
        showLearningGapNotification(gaps) {
            const message = `학습 보완이 필요한 부분: ${gaps.join(', ')}`;
            this.showNotification(message, 'warning');
        }
        
        /**
         * 제안된 액션 실행
         */
        executeSuggestedAction(action) {
            switch (action) {
                case 'create_study_plan':
                    console.log('학습 계획 생성 제안됨');
                    break;
                case 'review_basics':
                    console.log('기초 복습 제안됨');
                    break;
                case 'take_break':
                    console.log('휴식 제안됨');
                    break;
            }
        }
        
        /**
         * 세션 ID 생성
         */
        generateSessionId() {
            return 'holonic_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        }
        
        /**
         * 시간 포맷
         */
        formatTime(timestamp) {
            const date = new Date(timestamp * 1000);
            const now = new Date();
            const diff = now - date;
            
            if (diff < 60000) return '방금 전';
            if (diff < 3600000) return Math.floor(diff / 60000) + '분 전';
            if (diff < 86400000) return Math.floor(diff / 3600000) + '시간 전';
            
            return date.toLocaleDateString('ko-KR');
        }
    }
    
    // 전역 인스턴스 생성
    window.holonicIntegration = new HolonicIntegration();
    
    // 슬라이드 애니메이션 CSS
    const animationStyle = document.createElement('style');
    animationStyle.textContent = `
        @keyframes slideDown {
            from { transform: translate(-50%, -100%); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translate(-50%, 0); opacity: 1; }
            to { transform: translate(-50%, -100%); opacity: 0; }
        }
    `;
    document.head.appendChild(animationStyle);
    
})();