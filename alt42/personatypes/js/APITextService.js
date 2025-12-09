/**
 * 🌟 API Text Service - 동적 텍스트 생성 및 타이핑 효과
 * 모든 하드코딩된 텍스트를 API 응답으로 대체
 */

class APITextService {
    constructor() {
        this.apiEndpoint = '/shiningstars/api/enhanced_gpt_handler.php';
        this.textCache = new Map();
        this.typingQueue = [];
        this.isTyping = false;
        this.typingSpeed = 30; // 밀리초 단위
        this.currentTypingController = null;
    }

    /**
     * API를 통한 텍스트 생성
     */
    async generateText(context, params = {}) {
        const cacheKey = `${context}_${JSON.stringify(params)}`;
        
        // 캐시 확인
        if (this.textCache.has(cacheKey)) {
            return this.textCache.get(cacheKey);
        }

        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    context,
                    ...params,
                    generateType: 'text'
                })
            });

            const data = await response.json();
            
            if (data.success && data.text) {
                this.textCache.set(cacheKey, data.text);
                return data.text;
            }
            
            // 폴백 텍스트
            return this.getFallbackText(context, params);
            
        } catch (error) {
            console.error('API 텍스트 생성 오류:', error);
            return this.getFallbackText(context, params);
        }
    }

    /**
     * 타이핑 효과와 함께 텍스트 표시
     */
    async typeText(element, text, options = {}) {
        const {
            speed = this.typingSpeed,
            clearBefore = true,
            callback = null,
            pauseOnPunctuation = true,
            highlightKeywords = false
        } = options;

        // 이전 타이핑 중단
        if (this.currentTypingController) {
            this.currentTypingController.abort();
        }

        // AbortController 생성
        this.currentTypingController = new AbortController();
        const signal = this.currentTypingController.signal;

        if (clearBefore) {
            element.innerHTML = '';
        }

        // 커서 요소 추가
        const cursor = document.createElement('span');
        cursor.className = 'typing-cursor';
        cursor.textContent = '|';
        element.appendChild(cursor);

        let index = 0;
        const textSpan = document.createElement('span');
        element.insertBefore(textSpan, cursor);

        return new Promise((resolve) => {
            const typeNextChar = () => {
                if (signal.aborted) {
                    cursor.remove();
                    resolve(false);
                    return;
                }

                if (index < text.length) {
                    const char = text[index];
                    
                    // 키워드 하이라이트 처리
                    if (highlightKeywords && this.isKeywordStart(text, index)) {
                        const keyword = this.extractKeyword(text, index);
                        const keywordSpan = document.createElement('span');
                        keywordSpan.className = 'keyword-highlight';
                        keywordSpan.textContent = keyword;
                        textSpan.appendChild(keywordSpan);
                        index += keyword.length;
                    } else {
                        textSpan.appendChild(document.createTextNode(char));
                        index++;
                    }

                    // 구두점에서 일시 정지
                    let delay = speed;
                    if (pauseOnPunctuation && /[.!?]/.test(char)) {
                        delay = speed * 5;
                    } else if (pauseOnPunctuation && /[,;:]/.test(char)) {
                        delay = speed * 3;
                    }

                    setTimeout(typeNextChar, delay);
                } else {
                    // 타이핑 완료
                    cursor.remove();
                    this.currentTypingController = null;
                    
                    if (callback) {
                        callback();
                    }
                    resolve(true);
                }
            };

            typeNextChar();
        });
    }

    /**
     * 다중 텍스트 순차 타이핑
     */
    async typeMultipleTexts(elements, texts, options = {}) {
        for (let i = 0; i < elements.length && i < texts.length; i++) {
            await this.typeText(elements[i], texts[i], options);
            
            // 각 텍스트 사이 대기
            if (options.delayBetween) {
                await this.delay(options.delayBetween);
            }
        }
    }

    /**
     * 동적 피드백 생성 및 타이핑
     */
    async generateAndTypeFeedback(element, context, params = {}) {
        // 로딩 표시
        element.innerHTML = '<div class="loading-dots">분석 중<span>...</span></div>';
        
        try {
            // API 호출
            const feedback = await this.generateFeedback(context, params);
            
            // 타이핑 효과로 표시
            element.innerHTML = '';
            
            // 피드백 구조 생성
            const feedbackHTML = this.createFeedbackStructure(feedback);
            element.innerHTML = feedbackHTML;
            
            // 각 섹션에 타이핑 효과 적용
            if (feedback.positive) {
                const positiveEl = element.querySelector('.feedback-positive-text');
                await this.typeText(positiveEl, feedback.positive, { speed: 25 });
            }
            
            if (feedback.improvement) {
                const improvementEl = element.querySelector('.feedback-improvement-text');
                await this.typeText(improvementEl, feedback.improvement, { speed: 25 });
            }
            
            if (feedback.insight) {
                const insightEl = element.querySelector('.feedback-insight-text');
                await this.typeText(insightEl, feedback.insight, { speed: 25 });
            }
            
            // 질문들 순차 표시
            if (feedback.questions && feedback.questions.length > 0) {
                const questionsEl = element.querySelector('.feedback-questions-list');
                for (let question of feedback.questions) {
                    const li = document.createElement('li');
                    questionsEl.appendChild(li);
                    await this.typeText(li, question, { speed: 20 });
                    await this.delay(200);
                }
            }
            
            return feedback;
            
        } catch (error) {
            console.error('피드백 생성 오류:', error);
            element.innerHTML = '<div class="error-message">피드백을 생성할 수 없습니다.</div>';
            return null;
        }
    }

    /**
     * API를 통한 피드백 생성
     */
    async generateFeedback(context, params = {}) {
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    context,
                    ...params,
                    generateType: 'feedback'
                })
            });

            const data = await response.json();
            
            if (data.success && data.feedback) {
                return data.feedback;
            }
            
            return this.getFallbackFeedback(context);
            
        } catch (error) {
            console.error('API 피드백 생성 오류:', error);
            return this.getFallbackFeedback(context);
        }
    }

    /**
     * 피드백 HTML 구조 생성
     */
    createFeedbackStructure(feedback) {
        return `
            <div class="ai-feedback-container">
                <h3 class="feedback-header">
                    <span class="feedback-icon">🤖</span>
                    <span>AI 멘토의 피드백</span>
                </h3>
                
                ${feedback.positive ? `
                <div class="feedback-section feedback-positive">
                    <div class="section-icon">⭐</div>
                    <div class="section-content">
                        <strong>잘한 점:</strong>
                        <div class="feedback-positive-text"></div>
                    </div>
                </div>
                ` : ''}
                
                ${feedback.improvement ? `
                <div class="feedback-section feedback-improvement">
                    <div class="section-icon">🔍</div>
                    <div class="section-content">
                        <strong>개선 제안:</strong>
                        <div class="feedback-improvement-text"></div>
                    </div>
                </div>
                ` : ''}
                
                ${feedback.insight ? `
                <div class="feedback-section feedback-insight">
                    <div class="section-icon">💡</div>
                    <div class="section-content">
                        <strong>통찰:</strong>
                        <div class="feedback-insight-text"></div>
                    </div>
                </div>
                ` : ''}
                
                ${feedback.questions && feedback.questions.length > 0 ? `
                <div class="feedback-section feedback-questions">
                    <div class="section-icon">🤔</div>
                    <div class="section-content">
                        <strong>생각해볼 질문:</strong>
                        <ul class="feedback-questions-list"></ul>
                    </div>
                </div>
                ` : ''}
                
                ${feedback.inertiaOvercome ? `
                <div class="feedback-section feedback-inertia">
                    <div class="section-icon">🛡️</div>
                    <div class="section-content">
                        <strong>인지관성 극복:</strong>
                        <div class="feedback-inertia-text">${feedback.inertiaOvercome}</div>
                    </div>
                </div>
                ` : ''}
                
                ${feedback.nextChallenge ? `
                <div class="feedback-section feedback-next">
                    <div class="section-icon">🚀</div>
                    <div class="section-content">
                        <strong>다음 도전:</strong>
                        <div class="feedback-next-text">${feedback.nextChallenge}</div>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
    }

    /**
     * 폴백 텍스트 (API 실패 시)
     */
    getFallbackText(context, params = {}) {
        const fallbacks = {
            welcome: "반가워요! 🌟 함께 수학의 세계를 탐험해볼까요?",
            nodeIntro: "이 단계에서는 새로운 관점으로 문제를 바라보게 될 거예요.",
            encouragement: "훌륭해요! 계속 이렇게 생각해보세요.",
            completion: "대단해요! 한 단계를 완성했네요! 🎉",
            nextStep: "다음 여정이 기다리고 있어요. 준비되셨나요?",
            reflection: "잠시 멈추고 지금까지 배운 것을 되돌아보세요.",
            hint: "다른 각도에서 접근해보는 것은 어떨까요?",
            error: "잠시 문제가 발생했지만, 걱정하지 마세요. 다시 시도해보세요."
        };
        
        return fallbacks[context] || "계속 탐험해보세요! ✨";
    }

    /**
     * 폴백 피드백 (API 실패 시)
     */
    getFallbackFeedback(context) {
        return {
            positive: "깊이 있는 사고가 돋보여요! 🌟",
            improvement: "다음에는 더 구체적인 예시를 들어보면 좋겠어요.",
            insight: "이런 접근은 문제 해결 능력을 크게 향상시킬 거예요.",
            questions: [
                "이 방법을 다른 문제에도 적용할 수 있을까요?",
                "왜 이런 결론에 도달했나요?",
                "다른 방법도 있을까요?"
            ],
            nextChallenge: "다음 단계에서 더 흥미로운 도전이 기다리고 있어요!"
        };
    }

    /**
     * 키워드 시작 확인
     */
    isKeywordStart(text, index) {
        const keywords = ['수학', '편향', '사고', '패턴', '논리', '창의', '해결'];
        for (let keyword of keywords) {
            if (text.substr(index, keyword.length) === keyword) {
                return true;
            }
        }
        return false;
    }

    /**
     * 키워드 추출
     */
    extractKeyword(text, index) {
        const keywords = ['수학', '편향', '사고', '패턴', '논리', '창의', '해결'];
        for (let keyword of keywords) {
            if (text.substr(index, keyword.length) === keyword) {
                return keyword;
            }
        }
        return '';
    }

    /**
     * 지연 유틸리티
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * 현재 타이핑 중지
     */
    stopTyping() {
        if (this.currentTypingController) {
            this.currentTypingController.abort();
            this.currentTypingController = null;
        }
    }

    /**
     * 모든 캐시 초기화
     */
    clearCache() {
        this.textCache.clear();
    }
}

// 전역 인스턴스 생성
window.apiTextService = new APITextService();