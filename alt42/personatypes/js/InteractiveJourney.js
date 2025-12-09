/**
 * 🌟 인터랙티브 수학 여정 시스템
 * GPT API와 연동하여 자연스러운 학습 진행 제공
 */

class InteractiveJourney {
    constructor() {
        this.currentNode = null;
        this.journeyState = {
            completedNodes: new Set(),
            unlockedNodes: new Set([0]), // 시작 노드만 열림
            nodeResponses: {},
            detectedBiases: [],
            activeThinkingNodes: [],
            journeyStartTime: Date.now(),
            lastInteractionTime: null
        };
        
        this.mathFramework = window.mathFramework || null;
        this.isProcessing = false;
        this.userRole = document.querySelector('meta[name="user-role"]')?.content || 'student';
        
        this.init();
    }

    /**
     * 초기화
     */
    async init() {
        // 진행 상황 로드
        this.loadProgress();
        
        // 이벤트 리스너 설정
        this.setupEventListeners();
        
        // 첫 화면 애니메이션
        this.playIntroAnimation();
        
        // 수학 프레임워크 연결
        if (!this.mathFramework && window.MathematicalThinkingFramework) {
            this.mathFramework = new MathematicalThinkingFramework();
        }
        
        console.log('🌟 Interactive Journey 시스템 준비 완료');
    }

    /**
     * 노드 선택 및 콘텐츠 로드
     */
    async selectNode(nodeId) {
        console.log(`🎯 selectNode 호출됨 - 노드 ID: ${nodeId}`);
        
        if (this.isProcessing) {
            console.log('⏳ 처리 중... 대기');
            return;
        }
        
        // 잠긴 노드 체크
        if (!this.isNodeAccessible(nodeId)) {
            console.log(`🔒 노드 ${nodeId}는 아직 잠겨있음`);
            this.showLockedMessage(nodeId);
            return;
        }
        
        console.log(`✅ 노드 ${nodeId} 접근 가능`);
        this.currentNode = nodeId;
        this.isProcessing = true;
        
        // UI 업데이트
        this.updateNodeVisuals(nodeId);
        
        // 콘텐츠 로드
        console.log(`📝 콘텐츠 로드 시작...`);
        await this.loadNodeContent(nodeId);
        
        // 편향 감지
        this.detectPotentialBiases(nodeId);
        
        // 사고 노드 활성화
        this.activateThinkingNodes(nodeId);
        
        this.isProcessing = false;
        console.log(`✨ 노드 ${nodeId} 로드 완료`);
    }

    /**
     * 노드 콘텐츠 로드 (API 텍스트 서비스 통합)
     */
    async loadNodeContent(nodeId) {
        console.log(`📋 loadNodeContent 호출 - 노드 ID: ${nodeId}`);
        const panel = document.getElementById('contentPanel');
        
        if (!panel) {
            console.error('❌ contentPanel 요소를 찾을 수 없음');
            return;
        }
        
        const question = window.questions?.[nodeId];
        
        if (!question) {
            console.error(`❌ 노드 ${nodeId}에 대한 질문 데이터 없음`);
            console.log('현재 questions:', window.questions);
            return;
        }
        
        console.log(`✅ 질문 데이터 찾음:`, question);
        
        // 애니메이션과 함께 콘텐츠 전환
        panel.style.opacity = '0';
        
        setTimeout(async () => {
            panel.innerHTML = `
                <div class="node-content-wrapper">
                    <div class="node-header">
                        <h2 class="node-title">
                            ${this.getNodeIcon(nodeId)} ${question.title}
                        </h2>
                        <div class="node-progress">
                            ${this.renderProgressIndicator(nodeId)}
                        </div>
                    </div>
                    
                    <div class="question-section">
                        <p class="question-text" id="question-text-${nodeId}"></p>
                        ${this.renderHints(nodeId)}
                    </div>
                    
                    <div class="thinking-nodes-display">
                        ${this.renderActiveThinkingNodes()}
                    </div>
                    
                    <div class="answer-section">
                        ${this.renderAnswerInput(nodeId)}
                    </div>
                    
                    <div class="bias-detection-panel">
                        ${this.renderBiasWarnings()}
                    </div>
                    
                    <div id="ai-feedback" class="ai-feedback-section"></div>
                </div>
            `;
            
            panel.style.opacity = '1';
            
            // API Text Service로 질문 텍스트 타이핑
            if (window.apiTextService) {
                const questionTextEl = document.getElementById(`question-text-${nodeId}`);
                if (questionTextEl) {
                    // API로 동적 텍스트 생성 또는 기존 질문 사용
                    const dynamicText = await window.apiTextService.generateText('nodeIntro', { nodeId });
                    await window.apiTextService.typeText(questionTextEl, question.text, {
                        speed: 35,
                        highlightKeywords: true,
                        callback: () => {
                            // 타이핑 완료 후 입력 필드 포커스
                            const input = document.getElementById('answerInput');
                            if (input) input.focus();
                        }
                    });
                }
            } else {
                // 폴백: 기존 방식으로 텍스트 표시
                const questionTextEl = document.getElementById(`question-text-${nodeId}`);
                if (questionTextEl) {
                    questionTextEl.textContent = question.text;
                }
                setTimeout(() => {
                    const input = document.getElementById('answerInput');
                    if (input) input.focus();
                }, 300);
            }
            
        }, 300);
        
        // 아바타 메시지 업데이트
        this.updateAvatarMessage('questionStart', nodeId);
    }

    /**
     * 답변 입력 UI 렌더링
     */
    renderAnswerInput(nodeId) {
        const isCompleted = this.journeyState.completedNodes.has(nodeId);
        
        if (isCompleted) {
            const response = this.journeyState.nodeResponses[nodeId];
            return `
                <div class="completed-answer">
                    <div class="answer-badge">✅ 완료됨</div>
                    <div class="previous-answer">
                        <strong>작성한 답변:</strong>
                        <p>${response?.answer || '답변 없음'}</p>
                    </div>
                    ${response?.feedback ? `
                        <div class="previous-feedback">
                            <strong>받은 피드백:</strong>
                            <p>${response.feedback.positive}</p>
                        </div>
                    ` : ''}
                    <button class="review-btn" onclick="journey.reviewNode(${nodeId})">
                        📚 다시 학습하기
                    </button>
                </div>
            `;
        }
        
        return `
            <div class="answer-input-wrapper">
                <textarea 
                    id="answerInput" 
                    class="answer-textarea"
                    placeholder="여기에 당신의 생각을 자유롭게 적어보세요..."
                    rows="5"
                    oninput="journey.handleInputChange(this)"
                ></textarea>
                
                <div class="input-helpers">
                    <button class="helper-btn" onclick="journey.showWritingTips()">
                        💡 작성 팁
                    </button>
                    <button class="helper-btn" onclick="journey.useVoiceInput()">
                        🎤 음성 입력
                    </button>
                    <button class="helper-btn" onclick="journey.addDrawing()">
                        ✏️ 그림 추가
                    </button>
                </div>
                
                <div class="submit-section">
                    <div class="character-count">
                        <span id="charCount">0</span> / 500자
                    </div>
                    <button 
                        id="submitBtn"
                        class="submit-btn"
                        onclick="journey.submitAnswer()"
                        disabled
                    >
                        전송하기 ✨
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * 답변 제출 및 GPT 피드백
     */
    async submitAnswer() {
        const answer = document.getElementById('answerInput')?.value;
        if (!answer || answer.trim().length < 10) {
            this.showMessage('조금 더 자세히 적어주세요! 😊', 'warning');
            return;
        }
        
        // 제출 버튼 비활성화
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '분석 중... 🤔';
        }
        
        try {
            // GPT API 호출
            const feedback = await this.getGPTFeedback(this.currentNode, answer);
            
            // 피드백 표시
            this.displayFeedback(feedback);
            
            // 진행 상황 저장
            this.saveNodeResponse(this.currentNode, answer, feedback);
            
            // 다음 노드 해금
            this.unlockNextNodes(this.currentNode);
            
            // 성취 체크
            this.checkAchievements();
            
            // 애니메이션 효과
            this.celebrateCompletion(this.currentNode);
            
        } catch (error) {
            console.error('피드백 처리 오류:', error);
            this.showMessage('일시적인 오류가 발생했습니다. 다시 시도해주세요.', 'error');
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '전송하기 ✨';
            }
        }
    }

    /**
     * GPT 피드백 요청 (API Text Service 사용)
     */
    async getGPTFeedback(nodeId, answer) {
        // API Text Service를 통한 동적 피드백 생성
        if (window.apiTextService) {
            return await window.apiTextService.generateFeedback('nodeFeedback', {
                nodeId: nodeId,
                answer: answer,
                questionType: window.questions?.[nodeId]?.type || 'reflection',
                userId: window.userId || 0,
                detectedBiases: this.journeyState.detectedBiases
            });
        }
        
        // 폴백 처리
        return this.generateLocalFeedback(nodeId, answer);
    }

    /**
     * 로컬 피드백 생성 (폴백)
     */
    generateLocalFeedback(nodeId, answer) {
        const encouragements = [
            "훌륭한 성찰이에요! 🌟",
            "깊은 생각이 느껴집니다! 💭",
            "창의적인 접근이 돋보여요! 🎨",
            "논리적으로 잘 설명했어요! 🧩",
            "수학적 사고가 발전하고 있어요! 📈"
        ];
        
        const insights = [
            "이런 사고 과정은 복잡한 문제 해결에 큰 도움이 될 거예요.",
            "당신의 관점은 수학의 새로운 면을 보여주고 있어요.",
            "이 접근법을 다른 문제에도 적용해보면 좋겠어요.",
            "패턴을 발견하는 능력이 향상되고 있네요!",
            "메타인지 능력이 발전하고 있어요!"
        ];
        
        return {
            positive: encouragements[Math.floor(Math.random() * encouragements.length)],
            improvement: "다음에는 구체적인 예시를 더 들어보면 좋겠어요.",
            insight: insights[Math.floor(Math.random() * insights.length)],
            nextChallenge: "다음 노드에서 더 흥미로운 발견이 있을 거예요!",
            biasOvercome: this.journeyState.detectedBiases.length > 0 ? 
                "편향을 인식하고 극복하려는 노력이 보여요!" : ""
        };
    }

    /**
     * 피드백 표시 (타이핑 효과 포함)
     */
    async displayFeedback(feedback) {
        const feedbackSection = document.getElementById('ai-feedback');
        if (!feedbackSection) return;
        
        // API Text Service를 사용한 타이핑 효과
        if (window.apiTextService) {
            await window.apiTextService.generateAndTypeFeedback(feedbackSection, 'feedback', {
                feedback: feedback
            });
        } else {
            // 폴백: 기존 방식으로 표시
            feedbackSection.innerHTML = `
                <div class="feedback-container">
                    <h3 class="feedback-title">🤖 AI 멘토의 피드백</h3>
                    
                    <div class="feedback-card positive">
                        <div class="feedback-icon">⭐</div>
                        <div class="feedback-content">
                            <strong>잘한 점:</strong>
                            <p>${feedback.positive}</p>
                        </div>
                    </div>
                    
                    ${feedback.improvement ? `
                    <div class="feedback-card improvement">
                        <div class="feedback-icon">🔍</div>
                        <div class="feedback-content">
                            <strong>개선 제안:</strong>
                            <p>${feedback.improvement}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="feedback-card insight">
                        <div class="feedback-icon">💡</div>
                        <div class="feedback-content">
                            <strong>통찰:</strong>
                            <p>${feedback.insight}</p>
                        </div>
                    </div>
                    
                    ${feedback.biasOvercome ? `
                    <div class="feedback-card bias-overcome">
                        <div class="feedback-icon">🛡️</div>
                        <div class="feedback-content">
                            <strong>편향 극복:</strong>
                            <p>${feedback.biasOvercome}</p>
                        </div>
                    </div>
                    ` : ''}
                    
                    <div class="next-action">
                        <button class="continue-btn" onclick="journey.continueJourney()">
                            다음 단계로 진행 →
                        </button>
                    </div>
                </div>
            `;
        }
        
        // 스크롤 애니메이션
        feedbackSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    /**
     * 편향 감지
     */
    detectPotentialBiases(nodeId) {
        if (!this.mathFramework) return;
        
        const problem = {
            type: window.questions?.[nodeId]?.type || 'reflection',
            nodeId: nodeId
        };
        
        const biases = this.mathFramework.detectPotentialBiases(problem);
        this.journeyState.detectedBiases = biases
            .filter(b => b.probability > 0.5)
            .map(b => b.name);
        
        // UI 업데이트
        this.updateBiasDisplay();
    }

    /**
     * 사고 노드 활성화
     */
    activateThinkingNodes(nodeId) {
        if (!this.mathFramework) return;
        
        // 노드에 해당하는 사고 유형 활성화
        const nodeMapping = {
            0: ['reflection'],
            1: ['calculation'],
            2: ['geometry'],
            3: ['operation'],
            4: ['strategy'],
            5: ['pattern'],
            6: ['insight'],
            7: ['prediction'],
            8: ['mastery']
        };
        
        this.journeyState.activeThinkingNodes = nodeMapping[nodeId] || [];
        
        // 시각적 표시 업데이트
        this.updateThinkingNodesDisplay();
    }

    /**
     * 활성 사고 노드 렌더링
     */
    renderActiveThinkingNodes() {
        if (this.journeyState.activeThinkingNodes.length === 0) return '';
        
        const nodes = this.journeyState.activeThinkingNodes.map(nodeKey => {
            const node = this.mathFramework?.nodes[nodeKey];
            if (!node) return '';
            
            return `
                <div class="thinking-node-badge" style="background: ${node.color};">
                    ${node.symbol} ${node.koreanName}
                </div>
            `;
        }).join('');
        
        return `
            <div class="thinking-nodes-container">
                <div class="thinking-nodes-label">활성화된 사고 모드:</div>
                <div class="thinking-nodes-list">${nodes}</div>
            </div>
        `;
    }

    /**
     * 편향 경고 렌더링
     */
    renderBiasWarnings() {
        if (this.journeyState.detectedBiases.length === 0) return '';
        
        const biases = this.journeyState.detectedBiases.slice(0, 3).map(bias => `
            <div class="bias-warning-item">
                ⚠️ ${this.getBiasKoreanName(bias)}
            </div>
        `).join('');
        
        return `
            <div class="bias-warnings">
                <div class="bias-warning-title">주의할 인지편향:</div>
                ${biases}
                <button class="bias-help-btn" onclick="journey.showBiasHelp()">
                    도움말
                </button>
            </div>
        `;
    }

    /**
     * 다음 노드 해금
     */
    unlockNextNodes(completedNodeId) {
        // 노드 연결 구조 (정팔각형 진행)
        const connections = {
            0: [1, 7],      // 시작 노드에서 양옆 노드
            1: [2, 3],      // 진행 경로
            2: [3, 4],      
            3: [4, 5],      
            4: [5, 6],      
            5: [6],         
            6: [7],         
            7: [1],         // 원형 연결
            8: []           // 마스터리 노드 (최종)
        };
        
        const nextNodes = connections[completedNodeId] || [];
        
        // 역할별 대기 시간
        const waitTime = this.userRole === 'student' ? 3600000 : 0; // 1시간 또는 즉시
        
        if (waitTime > 0) {
            this.startUnlockTimer(nextNodes, waitTime);
            this.showMessage(`다음 노드는 1시간 후에 열립니다. 🕐`, 'info');
        } else {
            nextNodes.forEach(nodeId => {
                this.journeyState.unlockedNodes.add(nodeId);
                this.updateNodeVisuals(nodeId);
            });
            this.showMessage(`새로운 경로가 열렸습니다! ✨`, 'success');
        }
        
        // 완료 노드 추가
        this.journeyState.completedNodes.add(completedNodeId);
        
        // 마스터리 노드 해금 체크 (1-7번 노드 모두 완료 시)
        const requiredNodes = [1, 2, 3, 4, 5, 6, 7];
        const allCompleted = requiredNodes.every(nodeId => 
            this.journeyState.completedNodes.has(nodeId)
        );
        
        if (allCompleted && !this.journeyState.unlockedNodes.has(8)) {
            // 마스터리 노드 해금
            setTimeout(() => {
                this.journeyState.unlockedNodes.add(8);
                this.updateNodeVisuals(8);
                this.showMessage('🎊 축하합니다! 마스터리 단계가 열렸습니다! 👑', 'success');
                this.playUnlockAnimation([8]);
                
                // 특별 효과
                this.createStarParticles();
                this.shakeAvatar();
            }, 1000);
        }
        
        // 진행 상황 저장
        this.saveProgress();
    }

    /**
     * 노드 해금 애니메이션
     */
    playUnlockAnimation(nodes) {
        nodes.forEach(nodeId => {
            const nodeElement = document.querySelector(`[data-node-id="${nodeId}"]`);
            if (nodeElement) {
                nodeElement.classList.add('unlock-animation');
                setTimeout(() => {
                    nodeElement.classList.remove('unlock-animation');
                }, 1000);
            }
        });
    }

    /**
     * 해금 타이머
     */
    startUnlockTimer(nodes, duration) {
        const endTime = Date.now() + duration;
        
        const timer = setInterval(() => {
            const remaining = Math.max(0, endTime - Date.now());
            
            if (remaining === 0) {
                clearInterval(timer);
                nodes.forEach(nodeId => {
                    this.journeyState.unlockedNodes.add(nodeId);
                    this.updateNodeVisuals(nodeId);
                });
                this.showMessage('새로운 노드가 열렸습니다! 🎉', 'success');
                this.playUnlockAnimation(nodes);
            } else {
                this.updateTimerDisplay(remaining);
            }
        }, 1000);
    }

    /**
     * 완료 축하 애니메이션
     */
    celebrateCompletion(nodeId) {
        // 별 파티클 효과
        this.createStarParticles();
        
        // 노드 완료 효과
        const nodeElement = document.querySelector(`[data-node-id="${nodeId}"]`);
        if (nodeElement) {
            nodeElement.classList.add('completed');
            nodeElement.classList.add('celebrate');
            
            setTimeout(() => {
                nodeElement.classList.remove('celebrate');
            }, 2000);
        }
        
        // 아바타 반응
        this.updateAvatarMessage('celebration');
        this.shakeAvatar();
        
        // 사운드 효과 (옵션)
        this.playSound('success');
    }

    /**
     * 별 파티클 생성
     */
    createStarParticles() {
        const container = document.getElementById('contentPanel');
        if (!container) return;
        
        for (let i = 0; i < 20; i++) {
            const star = document.createElement('div');
            star.className = 'star-particle';
            star.style.left = Math.random() * 100 + '%';
            star.style.animationDelay = Math.random() * 1 + 's';
            star.innerHTML = '⭐';
            container.appendChild(star);
            
            setTimeout(() => star.remove(), 3000);
        }
    }

    /**
     * 진행 상황 저장
     */
    saveProgress() {
        const progress = {
            completedNodes: Array.from(this.journeyState.completedNodes),
            unlockedNodes: Array.from(this.journeyState.unlockedNodes),
            nodeResponses: this.journeyState.nodeResponses,
            lastInteractionTime: Date.now(),
            journeyStartTime: this.journeyState.journeyStartTime
        };
        
        localStorage.setItem('interactiveJourney', JSON.stringify(progress));
    }

    /**
     * 진행 상황 로드
     */
    loadProgress() {
        const saved = localStorage.getItem('interactiveJourney');
        if (saved) {
            const progress = JSON.parse(saved);
            this.journeyState.completedNodes = new Set(progress.completedNodes || []);
            this.journeyState.unlockedNodes = new Set(progress.unlockedNodes || [0]);
            this.journeyState.nodeResponses = progress.nodeResponses || {};
            this.journeyState.journeyStartTime = progress.journeyStartTime || Date.now();
        }
    }

    /**
     * 노드 응답 저장
     */
    saveNodeResponse(nodeId, answer, feedback) {
        this.journeyState.nodeResponses[nodeId] = {
            answer: answer,
            feedback: feedback,
            timestamp: Date.now()
        };
        
        this.saveProgress();
    }

    /**
     * 입력 변경 처리
     */
    handleInputChange(textarea) {
        const charCount = textarea.value.length;
        const charDisplay = document.getElementById('charCount');
        const submitBtn = document.getElementById('submitBtn');
        
        if (charDisplay) {
            charDisplay.textContent = charCount;
            charDisplay.style.color = charCount > 450 ? '#ef4444' : '#94a3b8';
        }
        
        if (submitBtn) {
            submitBtn.disabled = charCount < 10 || charCount > 500;
        }
        
        // 실시간 편향 감지 (디바운스)
        clearTimeout(this.biasDetectionTimeout);
        this.biasDetectionTimeout = setTimeout(() => {
            this.detectRealtimeBias(textarea.value);
        }, 1000);
    }

    /**
     * 실시간 편향 감지
     */
    detectRealtimeBias(text) {
        // 간단한 키워드 기반 편향 감지
        const biasKeywords = {
            'ConfirmationBias': ['당연히', '분명히', '확실히'],
            'OverconfidenceBias': ['쉽게', '간단히', '당연하게'],
            'AnchoringBias': ['처음부터', '원래', '항상']
        };
        
        const detected = [];
        Object.entries(biasKeywords).forEach(([bias, keywords]) => {
            if (keywords.some(keyword => text.includes(keyword))) {
                detected.push(bias);
            }
        });
        
        if (detected.length > 0) {
            this.showBiasWarning(detected);
        }
    }

    /**
     * 편향 경고 표시
     */
    showBiasWarning(biases) {
        const warningDiv = document.createElement('div');
        warningDiv.className = 'realtime-bias-warning';
        warningDiv.innerHTML = `
            💡 인지편향 주의: ${biases.map(b => this.getBiasKoreanName(b)).join(', ')}
        `;
        
        const inputWrapper = document.querySelector('.answer-input-wrapper');
        if (inputWrapper && !inputWrapper.querySelector('.realtime-bias-warning')) {
            inputWrapper.prepend(warningDiv);
            
            setTimeout(() => warningDiv.remove(), 5000);
        }
    }

    /**
     * 메시지 표시
     */
    showMessage(text, type = 'info') {
        const message = document.createElement('div');
        message.className = `journey-message ${type}`;
        message.innerHTML = text;
        
        document.body.appendChild(message);
        
        setTimeout(() => {
            message.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            message.classList.remove('show');
            setTimeout(() => message.remove(), 300);
        }, 3000);
    }

    /**
     * 아바타 메시지 업데이트 (API 텍스트 서비스 통합)
     */
    async updateAvatarMessage(type, nodeId = null) {
        const avatarSpeech = document.getElementById('avatarSpeech');
        if (!avatarSpeech) return;
        
        // API Text Service 사용
        if (window.apiTextService) {
            avatarSpeech.classList.add('update');
            
            // 컨텍스트에 따른 동적 텍스트 생성
            const text = await window.apiTextService.generateText(type, { nodeId });
            
            // 타이핑 효과로 메시지 표시
            await window.apiTextService.typeText(avatarSpeech, text, {
                speed: 30,
                clearBefore: true,
                highlightKeywords: true
            });
            
            setTimeout(() => avatarSpeech.classList.remove('update'), 500);
        } else {
            // 폴백: 기존 하드코딩된 메시지
            const messages = {
                welcome: "안녕! 오늘은 어떤 수학적 발견을 할까요? 🌟",
                questionStart: "이 질문에 대해 자유롭게 생각해보세요.",
                celebration: "훌륭해요! 정말 잘했어요! 🎉",
                locked: "아직 이 노드는 잠겨있어요. 먼저 다른 노드를 완료해주세요.",
                thinking: "흥미로운 관점이네요... 🤔",
                encouragement: "계속해서 탐구해보세요! 당신은 잘하고 있어요!"
            };
            
            avatarSpeech.textContent = messages[type] || messages.welcome;
            avatarSpeech.classList.add('update');
            setTimeout(() => avatarSpeech.classList.remove('update'), 500);
        }
    }

    /**
     * 아바타 흔들기
     */
    shakeAvatar() {
        const avatar = document.getElementById('avatar');
        if (avatar) {
            avatar.classList.add('shake');
            setTimeout(() => avatar.classList.remove('shake'), 500);
        }
    }

    /**
     * 노드 시각 업데이트
     */
    updateNodeVisuals(nodeId) {
        const nodeElement = document.querySelector(`[data-node-id="${nodeId}"]`);
        if (!nodeElement) return;
        
        nodeElement.classList.remove('locked');
        
        if (this.journeyState.completedNodes.has(nodeId)) {
            nodeElement.classList.add('completed');
        } else if (this.journeyState.unlockedNodes.has(nodeId)) {
            nodeElement.classList.add('unlocked');
        }
        
        if (this.currentNode === nodeId) {
            nodeElement.classList.add('active');
        } else {
            nodeElement.classList.remove('active');
        }
    }

    /**
     * 노드 접근 가능 여부
     */
    isNodeAccessible(nodeId) {
        return this.journeyState.unlockedNodes.has(nodeId) || 
               this.journeyState.completedNodes.has(nodeId);
    }

    /**
     * 잠긴 노드 메시지 표시
     */
    showLockedMessage(nodeId) {
        const panel = document.getElementById('contentPanel');
        if (!panel) return;
        
        panel.innerHTML = `
            <div style="text-align: center; padding: 40px 20px;">
                <div style="font-size: 3em; margin-bottom: 20px;">🔒</div>
                <h3 style="color: #ef4444; margin-bottom: 15px;">이 노드는 아직 잠겨있습니다</h3>
                <p style="color: #94a3b8; line-height: 1.6;">
                    다른 노드를 먼저 완료해야 이 노드를 열 수 있습니다.<br>
                    현재 열려있는 노드를 선택해주세요.
                </p>
                <div style="margin-top: 30px; padding: 20px; background: rgba(255,255,255,0.05); border-radius: 10px;">
                    <p style="color: #fbbf24; font-size: 0.9em;">
                        💡 팁: 초록색으로 빛나는 노드들을 클릭할 수 있습니다
                    </p>
                </div>
            </div>
        `;
        
        this.updateAvatarMessage('locked', nodeId);
        this.showMessage('🔒 이 노드는 아직 열리지 않았습니다', 'warning');
    }

    /**
     * 진행도 표시기 렌더링
     */
    renderProgressIndicator(nodeId) {
        const total = 9;
        const completed = this.journeyState.completedNodes.size;
        const percentage = Math.round((completed / total) * 100);
        
        return `
            <div class="progress-indicator">
                <div class="progress-bar" style="width: ${percentage}%; background: linear-gradient(90deg, #22c55e, #10b981);">
                    <span class="progress-text">${completed}/${total} 완료</span>
                </div>
            </div>
        `;
    }

    /**
     * 힌트 렌더링
     */
    renderHints(nodeId) {
        const hints = {
            0: "자유롭게 오늘의 경험을 떠올려보세요",
            1: "계산 과정에서 느낀 점도 함께 적어보세요",
            2: "일상에서 본 도형들을 생각해보세요",
            3: "어려운 연산을 극복한 경험이 있나요?",
            4: "문제를 해결한 자신만의 비법을 공유해주세요",
            5: "패턴을 발견한 순간의 기쁨을 표현해보세요",
            6: "깨달음의 순간을 자세히 묘사해보세요",
            7: "수학이 미래에 어떻게 도움이 될까요?",
            8: "이 여정을 통해 무엇을 배웠나요?"
        };
        
        const hint = hints[nodeId] || "";
        if (!hint) return "";
        
        return `
            <div class="hint-box" style="margin-top: 15px; padding: 10px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid #3b82f6; border-radius: 5px;">
                <p style="color: #93c5fd; font-size: 0.9em;">💡 ${hint}</p>
            </div>
        `;
    }

    /**
     * 작성 팁 표시
     */
    showWritingTips() {
        const tips = [
            "구체적인 예시를 들어보세요",
            "왜 그렇게 생각하는지 설명해보세요",
            "일상생활과 연결해보세요",
            "다른 방법도 생각해보세요",
            "감정도 함께 표현해보세요"
        ];
        
        const tipText = tips[Math.floor(Math.random() * tips.length)];
        this.showMessage(`💡 팁: ${tipText}`, 'info');
    }

    /**
     * 편향 한글 이름
     */
    getBiasKoreanName(bias) {
        const names = {
            'ConfirmationBias': '확증 편향',
            'OverconfidenceBias': '과신 편향',
            'AnchoringBias': '앵커링 편향',
            'AvailabilityHeuristic': '가용성 휴리스틱'
        };
        return names[bias] || bias;
    }

    /**
     * 노드 아이콘
     */
    getNodeIcon(nodeId) {
        const icons = ['🌟', '🔢', '📐', '➕', '🎯', '🔄', '💡', '🔮', '👑'];
        return icons[nodeId] || '⭐';
    }

    /**
     * 사운드 재생 (옵션)
     */
    playSound(type) {
        // 사운드 효과 구현 (옵션)
    }

    /**
     * 인트로 애니메이션
     */
    playIntroAnimation() {
        // 첫 방문시 환영 애니메이션
        if (!localStorage.getItem('journeyIntroShown')) {
            // 인트로 애니메이션 실행
            localStorage.setItem('journeyIntroShown', 'true');
        }
    }

    /**
     * 이벤트 리스너 설정
     */
    setupEventListeners() {
        // 키보드 단축키
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'Enter') {
                this.submitAnswer();
            }
        });
        
        // 페이지 벗어나기 경고
        window.addEventListener('beforeunload', (e) => {
            if (this.hasUnsavedWork()) {
                e.preventDefault();
                e.returnValue = '작성 중인 내용이 있습니다. 정말 나가시겠습니까?';
            }
        });
    }

    /**
     * 저장되지 않은 작업 확인
     */
    hasUnsavedWork() {
        const input = document.getElementById('answerInput');
        return input && input.value.length > 10;
    }

    /**
     * 여정 계속하기
     */
    continueJourney() {
        // 다음 가능한 노드 찾기
        const availableNodes = Array.from(this.journeyState.unlockedNodes)
            .filter(id => !this.journeyState.completedNodes.has(id));
        
        if (availableNodes.length > 0) {
            // 추천 노드 선택 (가장 낮은 번호)
            const nextNode = Math.min(...availableNodes);
            this.selectNode(nextNode);
        } else {
            this.showMessage('모든 노드를 완료했습니다! 🎊', 'success');
        }
    }
}

// 전역에서 사용 가능하도록 등록
window.InteractiveJourney = InteractiveJourney;

// DOMContentLoaded 이벤트 후 인스턴스 생성
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.journey) {
            console.log('🌟 InteractiveJourney 인스턴스 자동 생성');
            window.journey = new InteractiveJourney();
        }
    });
} else {
    // 이미 DOM이 로드된 경우
    if (!window.journey) {
        console.log('🌟 InteractiveJourney 인스턴스 즉시 생성');
        window.journey = new InteractiveJourney();
    }
}