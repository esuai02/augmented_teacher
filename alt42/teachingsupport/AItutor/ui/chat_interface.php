<?php
/**
 * AI 튜터 채팅 인터페이스 컴포넌트 (사이드바 내장형)
 * 
 * - 버튼형 객관식 답변
 * - 실시간 메시지 표시
 * - 애니메이션 효과
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

// 이 파일은 include로 사용됨 - Moodle 연결은 부모에서 처리
?>

<script>
/**
 * AI 튜터 채팅 인터페이스 JavaScript (사이드바 내장형)
 */
const SidebarChatInterface = {
    container: null,
    messagesEl: null,
    isActive: false,
    currentOptions: null,
    sessionContext: {},
    personaStyle: null,
    optionTimeout: null,
    
    /**
     * 초기화
     */
    init: function(config = {}) {
        this.container = document.getElementById('sidebarChatContainer');
        this.messagesEl = document.getElementById('sidebarChatMessages');
        this.sessionContext = config.context || {};
        
        // 페르소나 스타일 적용
        if (config.persona) {
            this.setPersonaStyle(config.persona);
        }
        
        console.log('[SidebarChatInterface] 초기화 완료', config);
    },
    
    /**
     * 페르소나 스타일 설정
     */
    setPersonaStyle: function(persona) {
        this.personaStyle = persona;
        
        // 튜터 이름 업데이트
        const chatName = this.container?.querySelector('.chat-name');
        if (chatName && persona.icon) {
            chatName.textContent = 'AI 튜터 ' + persona.icon;
        }
    },
    
    /**
     * 채팅 활성화/비활성화
     */
    setActive: function(active) {
        this.isActive = active;
        
        if (this.container) {
            this.container.classList.toggle('active', active);
        }
        
        const stepsContent = document.getElementById('stepsContent');
        if (stepsContent) {
            stepsContent.classList.toggle('hidden', active);
        }
        
        // 타이틀 업데이트
        const title = document.getElementById('sidebarTitle');
        if (title) {
            title.textContent = active ? 'AI 튜터' : '풀이 단계';
        }
        
        // 버튼 상태 업데이트
        const btn = document.getElementById('chatToggleBtn');
        const label = document.getElementById('chatToggleLabel');
        if (btn && label) {
            btn.classList.toggle('active', active);
            label.textContent = active ? '풀이 단계' : 'AI 튜터';
        }
    },
    
    /**
     * AI 메시지 추가
     */
    addAIMessage: function(text, options = null) {
        if (!this.messagesEl) return;
        
        // 타이핑 인디케이터 표시
        this.showTypingIndicator();
        
        // 딜레이 후 메시지 표시
        const delay = Math.min(text.length * 25, 1200);
        
        setTimeout(() => {
            this.hideTypingIndicator();
            
            const messageEl = document.createElement('div');
            messageEl.className = 'sidebar-chat-message ai';
            messageEl.innerHTML = `
                <div class="message-avatar">🎓</div>
                <div class="message-content">
                    <div class="message-bubble">${this.formatMessage(text)}</div>
                    ${options ? this.renderOptions(options) : ''}
                    <div class="message-time">${this.getTimeString()}</div>
                </div>
            `;
            
            this.messagesEl.appendChild(messageEl);
            this.scrollToBottom();
            
            // 옵션 타임아웃 설정
            if (options && options.timeout) {
                this.setOptionTimeout(options.timeout, options.timeout_rule);
            }
        }, delay);
    },
    
    /**
     * 사용자 메시지 추가
     */
    addUserMessage: function(text) {
        if (!this.messagesEl) return;
        
        const messageEl = document.createElement('div');
        messageEl.className = 'sidebar-chat-message user';
        messageEl.innerHTML = `
            <div class="message-avatar">👤</div>
            <div class="message-content">
                <div class="message-bubble">${text}</div>
                <div class="message-time">${this.getTimeString()}</div>
            </div>
        `;
        
        this.messagesEl.appendChild(messageEl);
        this.scrollToBottom();
    },
    
    /**
     * 버튼 옵션 렌더링
     */
    renderOptions: function(options) {
        if (!options || !options.options) return '';
        
        let html = '<div class="sidebar-chat-options">';
        
        options.options.forEach((opt, index) => {
            const icon = opt.label.match(/^[^\w\s]/) ? '' : this.getOptionIcon(index);
            html += `
                <button class="sidebar-chat-option-btn" 
                        data-value="${opt.value}"
                        data-next-rule="${opt.next_rule || ''}"
                        data-emotion="${opt.emotion || ''}"
                        onclick="SidebarChatInterface.selectOption(this)">
                    <span class="option-icon">${icon}</span>
                    ${opt.label}
                </button>
            `;
        });
        
        html += '</div>';
        return html;
    },
    
    /**
     * 옵션 선택 처리
     */
    selectOption: function(btnEl) {
        // 타임아웃 클리어
        if (this.optionTimeout) {
            clearTimeout(this.optionTimeout);
            this.optionTimeout = null;
        }
        
        const value = btnEl.dataset.value;
        const nextRule = btnEl.dataset.nextRule;
        const emotion = btnEl.dataset.emotion;
        const label = btnEl.textContent.trim();
        
        // 모든 옵션 버튼 비활성화
        const allBtns = btnEl.parentElement.querySelectorAll('.sidebar-chat-option-btn');
        allBtns.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = btn === btnEl ? '1' : '0.4';
        });
        
        // 선택된 버튼 표시
        btnEl.classList.add('selected');
        
        // 사용자 응답 메시지 추가
        this.addUserMessage(label);
        
        // 이벤트 발생
        this.dispatchResponse({
            value: value,
            label: label,
            next_rule: nextRule,
            emotion: emotion
        });
    },
    
    /**
     * 응답 이벤트 발생
     */
    dispatchResponse: function(response) {
        const event = new CustomEvent('ai-chat-response', {
            detail: response
        });
        document.dispatchEvent(event);
        
        console.log('[SidebarChatInterface] 응답:', response);
        
        // 연결된 핸들러 호출
        if (typeof window.handleAIChatResponse === 'function') {
            window.handleAIChatResponse(response);
        }
    },
    
    /**
     * 타이핑 인디케이터 표시
     */
    showTypingIndicator: function() {
        if (!this.messagesEl || document.getElementById('sidebarTypingIndicator')) return;
        
        const indicator = document.createElement('div');
        indicator.id = 'sidebarTypingIndicator';
        indicator.className = 'sidebar-chat-message ai';
        indicator.innerHTML = `
            <div class="message-avatar">🎓</div>
            <div class="message-content">
                <div class="message-bubble">
                    <div class="sidebar-typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </div>
            </div>
        `;
        
        this.messagesEl.appendChild(indicator);
        this.scrollToBottom();
    },
    
    /**
     * 타이핑 인디케이터 숨기기
     */
    hideTypingIndicator: function() {
        const indicator = document.getElementById('sidebarTypingIndicator');
        if (indicator) {
            indicator.remove();
        }
    },
    
    /**
     * 호흡 바 표시
     */
    showBreathingBar: function(duration = 5000) {
        if (!this.messagesEl) return Promise.resolve();
        
        const barEl = document.createElement('div');
        barEl.className = 'sidebar-chat-message ai';
        barEl.innerHTML = `
            <div class="message-avatar">🎓</div>
            <div class="message-content" style="flex:1">
                <div class="message-bubble">
                    <div>천천히 숨을 쉬어봐... 🌬️</div>
                    <div class="sidebar-breathing-bar"></div>
                </div>
            </div>
        `;
        
        this.messagesEl.appendChild(barEl);
        this.scrollToBottom();
        
        return new Promise(resolve => {
            setTimeout(resolve, duration);
        });
    },
    
    /**
     * 감정 선택기 표시
     */
    showEmotionSelector: function(questionText = '지금 기분은 어때?') {
        if (!this.messagesEl) return;
        
        const emotions = [
            { value: 'confident', icon: '😊', label: '자신있어' },
            { value: 'neutral', icon: '😐', label: '보통이야' },
            { value: 'confused', icon: '🤔', label: '헷갈려' },
            { value: 'stuck', icon: '😣', label: '막혔어' },
            { value: 'anxious', icon: '😰', label: '불안해' }
        ];
        
        const selectorEl = document.createElement('div');
        selectorEl.className = 'sidebar-chat-message ai';
        selectorEl.innerHTML = `
            <div class="message-avatar">🎓</div>
            <div class="message-content">
                <div class="message-bubble">${questionText}</div>
                <div class="sidebar-emotion-selector">
                    ${emotions.map(e => `
                        <button class="sidebar-emotion-btn" 
                                data-value="${e.value}" 
                                title="${e.label}"
                                onclick="SidebarChatInterface.selectEmotion(this, '${e.value}', '${e.label}')">
                            ${e.icon}
                        </button>
                    `).join('')}
                </div>
            </div>
        `;
        
        this.messagesEl.appendChild(selectorEl);
        this.scrollToBottom();
    },
    
    /**
     * 감정 선택 처리
     */
    selectEmotion: function(btnEl, value, label) {
        // 모든 감정 버튼 비활성화
        const allBtns = btnEl.parentElement.querySelectorAll('.sidebar-emotion-btn');
        allBtns.forEach(btn => {
            btn.disabled = true;
            btn.style.opacity = btn === btnEl ? '1' : '0.3';
        });
        
        btnEl.classList.add('selected');
        
        // 사용자 응답 추가
        this.addUserMessage(btnEl.textContent + ' ' + label);
        
        // 이벤트 발생
        this.dispatchResponse({
            type: 'emotion',
            value: value,
            label: label
        });
    },
    
    /**
     * 옵션 타임아웃 설정
     */
    setOptionTimeout: function(timeout, timeoutRule) {
        if (this.optionTimeout) {
            clearTimeout(this.optionTimeout);
        }
        
        this.optionTimeout = setTimeout(() => {
            console.log('[SidebarChatInterface] 옵션 타임아웃, rule:', timeoutRule);
            
            // 현재 옵션 버튼들 비활성화
            const currentOptions = this.messagesEl?.querySelectorAll('.sidebar-chat-options:last-child .sidebar-chat-option-btn');
            if (currentOptions) {
                currentOptions.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.4';
                });
            }
            
            // 타임아웃 룰 실행
            if (timeoutRule) {
                this.dispatchResponse({
                    type: 'timeout',
                    next_rule: timeoutRule
                });
            }
        }, timeout);
    },
    
    /**
     * 메시지 포맷팅
     */
    formatMessage: function(text) {
        // 줄바꿈 처리
        text = text.replace(/\n/g, '<br>');
        
        // {변수} 치환
        text = text.replace(/\{(\w+)\}/g, (match, key) => {
            return this.sessionContext[key] || match;
        });
        
        // 강조 처리
        text = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
        
        return text;
    },
    
    /**
     * 옵션 아이콘 반환
     */
    getOptionIcon: function(index) {
        const icons = ['1️⃣', '2️⃣', '3️⃣', '4️⃣', '5️⃣'];
        return icons[index] || '';
    },
    
    /**
     * 시간 문자열 반환
     */
    getTimeString: function() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    },
    
    /**
     * 스크롤 최하단으로
     */
    scrollToBottom: function() {
        if (!this.messagesEl) return;
        
        setTimeout(() => {
            this.messagesEl.scrollTop = this.messagesEl.scrollHeight;
        }, 50);
    },
    
    /**
     * 메시지 모두 지우기
     */
    clearMessages: function() {
        if (this.messagesEl) {
            this.messagesEl.innerHTML = '';
        }
    },
    
    /**
     * 컨텍스트 업데이트
     */
    updateContext: function(newContext) {
        this.sessionContext = { ...this.sessionContext, ...newContext };
    }
};

// 기존 AIChatInterface 호환성 유지
const AIChatInterface = SidebarChatInterface;

/**
 * 사이드바 채팅 토글
 */
function toggleSidebarChat() {
    const isActive = SidebarChatInterface.isActive;
    SidebarChatInterface.setActive(!isActive);
    
    // 채팅 활성화 시 세션 시작 (처음 활성화 시에만)
    if (!isActive && typeof AITutor !== 'undefined' && !AITutor.state.sessionActive) {
        AITutor.startSession();
    }
}

// DOM 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    SidebarChatInterface.init();
});
</script>
