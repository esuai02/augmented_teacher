/**
 * 🎓 편향 카드 교육 세션 시스템
 * 카드 클릭 시 6단계 교육 세션 진행: 소개→예시→진단→개선→실습→마무리
 */

class BiasCardSession {
    constructor() {
        this.isSessionActive = false;
        this.currentBias = null;
        this.currentStep = 0;
        this.totalSteps = 6;
        this.sessionData = {};
        this.userResponses = {};
        this.audioManager = new SessionAudioManager();
        
        this.init();
    }

    /**
     * 🚀 세션 시스템 초기화
     */
    init() {
        this.loadSessionData();
        this.createSessionInterface();
        this.bindEventListeners();
    }

    /**
     * 📚 세션 컨텐츠 데이터 로드
     */
    loadSessionData() {
        this.sessionData = {
            확증편향: {
                title: "확증편향 극복 여정",
                cosmicSymbol: "🕳️",
                cosmicMetaphor: "하나의 별만 보는 편향에서 무수한 별자리를 탐험하는 여정",
                steps: [
                    {
                        // 1단계: 편향 소개
                        title: "확증편향이란?",
                        type: "introduction",
                        audioFile: "audio/bias_sessions/confirmation_bias/01_intro.mp3",
                        content: {
                            mainText: "확증편향은 자신의 기존 믿음을 확인해주는 정보만 찾고, 반대 증거는 무시하는 인지편향입니다.",
                            cosmicStory: "🌌 우주 탐험가가 하나의 별만 바라보며 다른 수많은 별자리를 놓치는 것과 같아요.",
                            keyPoints: [
                                "기존 생각을 뒷받침하는 정보만 선택적으로 수집",
                                "반대 의견이나 증거를 의도적으로 회피",
                                "새로운 관점 수용의 어려움"
                            ]
                        },
                        interaction: {
                            type: "understanding_check",
                            question: "확증편향에 대해 이해하셨나요?",
                            options: ["완전히 이해했어요", "대충 알겠어요", "좀 더 설명이 필요해요"]
                        }
                    },
                    {
                        // 2단계: 실생활 예시
                        title: "일상 속 확증편향",
                        type: "examples",
                        audioFile: "audio/bias_sessions/confirmation_bias/02_examples.mp3",
                        content: {
                            mainText: "수학 문제를 풀 때 확증편향이 어떻게 나타나는지 살펴보세요.",
                            examples: [
                                {
                                    situation: "어려운 문제를 만났을 때",
                                    biasPattern: "첫 번째 떠오른 방법만 계속 시도하며 '이 방법이 맞을 거야'라고 고집",
                                    consequence: "다른 더 쉬운 해결법을 놓침"
                                },
                                {
                                    situation: "친구와 답이 다를 때",
                                    biasPattern: "자신의 답을 뒷받침하는 근거만 찾고 친구 답의 장점은 무시",
                                    consequence: "서로 배울 기회를 잃음"
                                },
                                {
                                    situation: "새로운 공식을 배울 때",
                                    biasPattern: "'내가 아는 방법이 더 좋아'라며 새 방법 거부",
                                    consequence: "더 효율적인 문제해결법을 놓침"
                                }
                            ]
                        },
                        interaction: {
                            type: "experience_check",
                            question: "다음 중 경험해본 것이 있나요?",
                            checkboxes: [
                                "한 가지 방법만 고집해본 적 있음",
                                "다른 의견을 듣기 싫어한 적 있음", 
                                "새로운 방법을 거부해본 적 있음",
                                "내 답만 맞다고 확신한 적 있음"
                            ]
                        }
                    },
                    {
                        // 3단계: 자가진단
                        title: "나의 확증편향 정도는?",
                        type: "self_diagnosis",
                        audioFile: "audio/bias_sessions/confirmation_bias/03_diagnosis.mp3",
                        content: {
                            mainText: "솔직한 자가진단을 통해 나의 확증편향 성향을 확인해보세요.",
                            instruction: "각 문항을 읽고 자신에게 해당하는 정도를 선택하세요."
                        },
                        interaction: {
                            type: "rating_scale",
                            questions: [
                                {
                                    question: "문제를 풀 때 첫 번째 방법이 막히면 다른 방법을 잘 시도하지 않는다",
                                    scale: [1, 2, 3, 4, 5],
                                    labels: ["전혀 아니다", "아니다", "보통이다", "그렇다", "매우 그렇다"]
                                },
                                {
                                    question: "내 의견과 다른 답을 보면 먼저 틀린 점을 찾으려 한다",
                                    scale: [1, 2, 3, 4, 5],
                                    labels: ["전혀 아니다", "아니다", "보통이다", "그렇다", "매우 그렇다"]
                                },
                                {
                                    question: "새로운 해결 방법보다는 익숙한 방법을 선호한다",
                                    scale: [1, 2, 3, 4, 5],
                                    labels: ["전혀 아니다", "아니다", "보통이다", "그렇다", "매우 그렇다"]
                                }
                            ]
                        }
                    },
                    {
                        // 4단계: 개선 방법
                        title: "확증편향 극복 전략",
                        type: "improvement_methods",
                        audioFile: "audio/bias_sessions/confirmation_bias/04_methods.mp3",
                        content: {
                            mainText: "확증편향을 극복하는 3가지 우주적 전략을 배워보세요!",
                            strategies: [
                                {
                                    name: "🔭 다중 망원경 전략",
                                    description: "한 가지 방법이 막히면 의도적으로 3가지 다른 접근법을 시도해보기",
                                    practice: "문제를 보면 '이것 말고 다른 방법은?' 자문하기",
                                    cosmicTip: "우주에는 같은 별을 보는 다양한 각도가 있어요"
                                },
                                {
                                    name: "👥 동료 탐험가 전략",
                                    description: "다른 사람의 의견을 적극적으로 듣고 장점 찾아보기",
                                    practice: "친구 답을 보면 '이 방법의 좋은 점은?' 먼저 생각하기",
                                    cosmicTip: "다른 탐험가의 별자리도 아름다운 이유가 있어요"
                                },
                                {
                                    name: "🌟 반대 증거 탐지기",
                                    description: "내 생각과 반대되는 증거를 의도적으로 찾아보기",
                                    practice: "'내가 틀릴 수도 있나?' 질문을 습관화하기",
                                    cosmicTip: "어둠 속에서도 새로운 별빛을 발견할 수 있어요"
                                }
                            ]
                        },
                        interaction: {
                            type: "commitment_check",
                            question: "어떤 전략을 실천해보시겠어요?",
                            commitments: [
                                "3가지 방법 시도하기",
                                "다른 의견의 장점 찾기",
                                "반대 증거 의도적으로 찾기"
                            ]
                        }
                    },
                    {
                        // 5단계: 실습 활동
                        title: "실전 연습하기",
                        type: "practice",
                        audioFile: "audio/bias_sessions/confirmation_bias/05_practice.mp3",
                        content: {
                            mainText: "실제 문제로 확증편향 극복을 연습해보세요!",
                            practiceTask: {
                                problem: "2x + 5 = 13 을 다양한 방법으로 풀어보세요",
                                instruction: "최소 3가지 다른 방법으로 접근해보세요",
                                methods: [
                                    "방법 1: 이항하여 계산",
                                    "방법 2: 그래프로 해석", 
                                    "방법 3: 역산으로 검증",
                                    "방법 4: 다른 창의적 접근"
                                ]
                            }
                        },
                        interaction: {
                            type: "practice_completion",
                            question: "몇 가지 방법을 시도해보셨나요?",
                            textarea: "시도한 방법들을 간단히 적어보세요",
                            methodCount: [1, 2, 3, 4, "5개 이상"]
                        }
                    },
                    {
                        // 6단계: 마무리
                        title: "여정 완료! 🌟",
                        type: "completion",
                        audioFile: "audio/bias_sessions/confirmation_bias/06_completion.mp3",
                        content: {
                            mainText: "훌륭해요! 확증편향 극복 여정을 완주하셨습니다!",
                            summary: {
                                learned: [
                                    "확증편향이 무엇인지 이해했어요",
                                    "일상 속 확증편향 패턴을 인식했어요",
                                    "3가지 극복 전략을 배웠어요",
                                    "실제 문제로 연습해봤어요"
                                ],
                                nextSteps: [
                                    "문제풀이 시 의도적으로 다양한 방법 시도하기",
                                    "다른 의견을 들을 때 장점부터 찾아보기",
                                    "내 생각과 반대되는 증거도 탐색하기"
                                ]
                            },
                            cosmicCelebration: "🎉 당신의 우주에 새로운 별자리가 추가되었어요! 이제 더 넓은 시야로 문제를 바라볼 수 있을 거예요."
                        },
                        interaction: {
                            type: "session_feedback",
                            satisfaction: [1, 2, 3, 4, 5],
                            satisfactionLabels: ["별로", "그저그래", "보통", "좋음", "매우 좋음"],
                            commitment: "앞으로 확증편향을 극복하기 위해 노력하겠어요"
                        }
                    }
                ]
            },
            
            재앙화사고: {
                title: "재앙화사고 극복 여정",
                cosmicSymbol: "☄️",
                cosmicMetaphor: "작은 운석을 행성 충돌로 보는 편향에서 아름다운 유성우로 바라보는 여정",
                steps: [
                    {
                        title: "재앙화사고란?",
                        type: "introduction",
                        audioFile: "audio/bias_sessions/catastrophizing/01_intro.mp3",
                        content: {
                            mainText: "재앙화사고는 작은 문제나 실수를 큰 재앙으로 확대 해석하는 인지편향입니다.",
                            cosmicStory: "🌌 작은 운석을 보고 행성 전체가 파괴될 것이라고 생각하는 것과 같아요.",
                            keyPoints: [
                                "작은 실수를 큰 실패로 확대 해석",
                                "최악의 시나리오만 떠올림",
                                "현실적 확률과 영향도를 무시"
                            ]
                        },
                        interaction: {
                            type: "understanding_check",
                            question: "재앙화사고에 대해 이해하셨나요?",
                            options: ["완전히 이해했어요", "대충 알겠어요", "좀 더 설명이 필요해요"]
                        }
                    }
                    // ... 나머지 5단계도 동일한 구조로 구현
                ]
            },
            
            자기과소평가: {
                title: "자기과소평가 극복 여정", 
                cosmicSymbol: "🌑",
                cosmicMetaphor: "내면의 별빛을 감추는 편향에서 밝은 별이 되는 여정",
                steps: [
                    {
                        title: "자기과소평가란?",
                        type: "introduction",
                        audioFile: "audio/bias_sessions/underconfidence/01_intro.mp3",
                        content: {
                            mainText: "자기과소평가는 자신의 능력을 실제보다 낮게 평가하여 도전을 회피하는 편향입니다.",
                            cosmicStory: "🌌 밝은 별빛을 가지고 있으면서도 구름에 가려져 있다고 생각하는 것과 같아요.",
                            keyPoints: [
                                "실제 능력보다 낮은 자기 평가",
                                "도전과 기회를 회피",
                                "과거 성공 경험을 과소평가"
                            ]
                        },
                        interaction: {
                            type: "understanding_check",
                            question: "자기과소평가에 대해 이해하셨나요?",
                            options: ["완전히 이해했어요", "대충 알겠어요", "좀 더 설명이 필요해요"]
                        }
                    }
                    // ... 나머지 5단계
                ]
            }
            
            // TODO: 나머지 57개 편향의 세션 데이터 추가
        };
    }

    /**
     * 🎓 편향 학습 세션 시작
     */
    startSession(biasName) {
        if (!this.sessionData[biasName]) {
            console.warn(`세션 데이터가 없습니다: ${biasName}`);
            return;
        }

        this.currentBias = biasName;
        this.currentStep = 0;
        this.userResponses = {};
        this.isSessionActive = true;

        this.showSessionInterface();
        this.renderCurrentStep();
    }

    /**
     * 🎨 세션 인터페이스 생성
     */
    createSessionInterface() {
        const sessionHTML = `
            <div id="bias-card-session" class="session-overlay">
                <div class="session-container">
                    <!-- 헤더 -->
                    <div class="session-header">
                        <div class="session-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" id="progressFill"></div>
                            </div>
                            <span class="progress-text" id="progressText">1 / 6</span>
                        </div>
                        <h2 class="session-title" id="sessionTitle">편향 학습 세션</h2>
                        <button class="close-session" onclick="biasCardSession.closeSession()">✕</button>
                    </div>

                    <!-- 메인 컨텐츠 -->
                    <div class="session-content" id="sessionContent">
                        <!-- 동적으로 생성됨 -->
                    </div>

                    <!-- 음성 컨트롤 -->
                    <div class="audio-controls" id="audioControls">
                        <button class="audio-btn" id="playPauseBtn" onclick="biasCardSession.toggleAudio()">
                            🔊 음성 재생
                        </button>
                        <div class="audio-progress">
                            <div class="audio-progress-bar" id="audioProgressBar"></div>
                        </div>
                        <span class="audio-time" id="audioTime">0:00 / 0:00</span>
                    </div>

                    <!-- 하단 네비게이션 -->
                    <div class="session-navigation">
                        <button class="nav-btn prev-btn" id="prevBtn" onclick="biasCardSession.previousStep()" disabled>
                            ◀ 이전
                        </button>
                        <button class="nav-btn next-btn" id="nextBtn" onclick="biasCardSession.nextStep()">
                            다음 ▶
                        </button>
                    </div>
                </div>
            </div>
        `;

        if (!document.getElementById('bias-card-session')) {
            document.body.insertAdjacentHTML('beforeend', sessionHTML);
        }
    }

    /**
     * 📱 세션 인터페이스 표시
     */
    showSessionInterface() {
        const session = document.getElementById('bias-card-session');
        if (session) {
            session.style.display = 'flex';
            setTimeout(() => {
                session.classList.add('show');
            }, 10);

            // 세션 타이틀 업데이트
            const titleElement = document.getElementById('sessionTitle');
            if (titleElement) {
                titleElement.textContent = this.sessionData[this.currentBias].title;
            }
        }
    }

    /**
     * 🚪 세션 닫기
     */
    closeSession() {
        const session = document.getElementById('bias-card-session');
        if (session) {
            session.classList.remove('show');
            setTimeout(() => {
                session.style.display = 'none';
                this.isSessionActive = false;
                this.audioManager.stop();
            }, 300);
        }
    }

    /**
     * 🎯 현재 단계 렌더링
     */
    renderCurrentStep() {
        const stepData = this.sessionData[this.currentBias].steps[this.currentStep];
        const contentElement = document.getElementById('sessionContent');
        
        if (!contentElement || !stepData) return;

        // 진행도 업데이트
        this.updateProgress();

        // 단계별 컨텐츠 렌더링
        let stepHTML = '';
        
        switch (stepData.type) {
            case 'introduction':
                stepHTML = this.renderIntroductionStep(stepData);
                break;
            case 'examples':
                stepHTML = this.renderExamplesStep(stepData);
                break;
            case 'self_diagnosis':
                stepHTML = this.renderDiagnosisStep(stepData);
                break;
            case 'improvement_methods':
                stepHTML = this.renderMethodsStep(stepData);
                break;
            case 'practice':
                stepHTML = this.renderPracticeStep(stepData);
                break;
            case 'completion':
                stepHTML = this.renderCompletionStep(stepData);
                break;
        }

        contentElement.innerHTML = stepHTML;

        // 음성 파일 로드
        this.loadStepAudio(stepData.audioFile);

        // 네비게이션 버튼 상태 업데이트
        this.updateNavigation();
    }

    /**
     * 🎨 소개 단계 렌더링
     */
    renderIntroductionStep(stepData) {
        return `
            <div class="step-container introduction-step">
                <div class="step-header">
                    <div class="cosmic-symbol">${this.sessionData[this.currentBias].cosmicSymbol}</div>
                    <h3>${stepData.title}</h3>
                </div>
                
                <div class="cosmic-story">
                    ${stepData.content.cosmicStory}
                </div>
                
                <div class="main-content">
                    <p class="main-text">${stepData.content.mainText}</p>
                    
                    <div class="key-points">
                        <h4>🔑 핵심 특징</h4>
                        <ul>
                            ${stepData.content.keyPoints.map(point => `<li>${point}</li>`).join('')}
                        </ul>
                    </div>
                </div>
                
                <div class="step-interaction">
                    <div class="interaction-question">
                        ${stepData.interaction.question}
                    </div>
                    <div class="interaction-options">
                        ${stepData.interaction.options.map((option, index) => 
                            `<button class="option-btn" onclick="biasCardSession.selectOption(${index})">${option}</button>`
                        ).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * 📋 예시 단계 렌더링
     */
    renderExamplesStep(stepData) {
        return `
            <div class="step-container examples-step">
                <div class="step-header">
                    <h3>${stepData.title}</h3>
                </div>
                
                <div class="main-content">
                    <p class="main-text">${stepData.content.mainText}</p>
                    
                    <div class="examples-grid">
                        ${stepData.content.examples.map((example, index) => `
                            <div class="example-card">
                                <div class="example-situation">
                                    <strong>상황:</strong> ${example.situation}
                                </div>
                                <div class="bias-pattern">
                                    <strong>편향 패턴:</strong> ${example.biasPattern}
                                </div>
                                <div class="consequence">
                                    <strong>결과:</strong> ${example.consequence}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="step-interaction">
                    <div class="interaction-question">
                        ${stepData.interaction.question}
                    </div>
                    <div class="checkbox-group">
                        ${stepData.interaction.checkboxes.map((option, index) => `
                            <label class="checkbox-option">
                                <input type="checkbox" value="${index}" onchange="biasCardSession.updateCheckboxes()">
                                <span>${option}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * 🔍 진단 단계 렌더링
     */
    renderDiagnosisStep(stepData) {
        return `
            <div class="step-container diagnosis-step">
                <div class="step-header">
                    <h3>${stepData.title}</h3>
                </div>
                
                <div class="main-content">
                    <p class="main-text">${stepData.content.mainText}</p>
                    <p class="instruction">${stepData.content.instruction}</p>
                    
                    <div class="diagnosis-questions">
                        ${stepData.interaction.questions.map((q, qIndex) => `
                            <div class="question-item">
                                <div class="question-text">${q.question}</div>
                                <div class="rating-scale">
                                    ${q.scale.map((score, sIndex) => `
                                        <label class="rating-option">
                                            <input type="radio" name="question_${qIndex}" value="${score}" 
                                                   onchange="biasCardSession.updateRating(${qIndex}, ${score})">
                                            <span class="rating-label">${q.labels[sIndex]}</span>
                                            <span class="rating-number">${score}</span>
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * 💡 개선 방법 단계 렌더링
     */
    renderMethodsStep(stepData) {
        return `
            <div class="step-container methods-step">
                <div class="step-header">
                    <h3>${stepData.title}</h3>
                </div>
                
                <div class="main-content">
                    <p class="main-text">${stepData.content.mainText}</p>
                    
                    <div class="strategies-grid">
                        ${stepData.content.strategies.map((strategy, index) => `
                            <div class="strategy-card">
                                <div class="strategy-header">
                                    <h4>${strategy.name}</h4>
                                </div>
                                <div class="strategy-description">
                                    ${strategy.description}
                                </div>
                                <div class="strategy-practice">
                                    <strong>실천법:</strong> ${strategy.practice}
                                </div>
                                <div class="cosmic-tip">
                                    💫 ${strategy.cosmicTip}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
                
                <div class="step-interaction">
                    <div class="interaction-question">
                        ${stepData.interaction.question}
                    </div>
                    <div class="commitment-group">
                        ${stepData.interaction.commitments.map((commitment, index) => `
                            <label class="commitment-option">
                                <input type="checkbox" value="${index}" onchange="biasCardSession.updateCommitments()">
                                <span>✨ ${commitment}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * 🏃‍♂️ 실습 단계 렌더링
     */
    renderPracticeStep(stepData) {
        return `
            <div class="step-container practice-step">
                <div class="step-header">
                    <h3>${stepData.title}</h3>
                </div>
                
                <div class="main-content">
                    <p class="main-text">${stepData.content.mainText}</p>
                    
                    <div class="practice-task">
                        <div class="task-problem">
                            <h4>📋 연습 문제</h4>
                            <div class="problem-text">${stepData.content.practiceTask.problem}</div>
                            <div class="task-instruction">${stepData.content.practiceTask.instruction}</div>
                        </div>
                        
                        <div class="method-suggestions">
                            <h4>💡 시도해볼 방법들</h4>
                            <ul>
                                ${stepData.content.practiceTask.methods.map(method => 
                                    `<li>${method}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="step-interaction">
                    <div class="interaction-question">
                        ${stepData.interaction.question}
                    </div>
                    <textarea class="practice-textarea" placeholder="${stepData.interaction.textarea}"
                              oninput="biasCardSession.updatePracticeResponse(this.value)"></textarea>
                    
                    <div class="method-count">
                        <span>시도한 방법 수:</span>
                        ${stepData.interaction.methodCount.map((count, index) => `
                            <label class="count-option">
                                <input type="radio" name="methodCount" value="${count}" 
                                       onchange="biasCardSession.updateMethodCount('${count}')">
                                <span>${count}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * 🎉 완료 단계 렌더링
     */
    renderCompletionStep(stepData) {
        return `
            <div class="step-container completion-step">
                <div class="step-header celebration">
                    <div class="cosmic-symbol celebration-symbol">${this.sessionData[this.currentBias].cosmicSymbol}</div>
                    <h3>${stepData.title}</h3>
                    <div class="celebration-effects">✨🌟⭐🎉✨</div>
                </div>
                
                <div class="main-content">
                    <p class="main-text">${stepData.content.mainText}</p>
                    
                    <div class="summary-section">
                        <div class="learned-section">
                            <h4>📚 배운 내용</h4>
                            <ul class="learned-list">
                                ${stepData.content.summary.learned.map(item => 
                                    `<li>✅ ${item}</li>`
                                ).join('')}
                            </ul>
                        </div>
                        
                        <div class="next-steps-section">
                            <h4>🚀 앞으로 실천할 것</h4>
                            <ul class="next-steps-list">
                                ${stepData.content.summary.nextSteps.map(item => 
                                    `<li>💪 ${item}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    </div>
                    
                    <div class="cosmic-celebration">
                        ${stepData.content.cosmicCelebration}
                    </div>
                </div>
                
                <div class="step-interaction">
                    <div class="feedback-section">
                        <div class="satisfaction-rating">
                            <span>이 세션이 도움이 되었나요?</span>
                            <div class="satisfaction-options">
                                ${stepData.interaction.satisfaction.map((score, index) => `
                                    <label class="satisfaction-option">
                                        <input type="radio" name="satisfaction" value="${score}" 
                                               onchange="biasCardSession.updateSatisfaction(${score})">
                                        <span class="satisfaction-emoji">${'😞😐😊😍🤩'[index]}</span>
                                        <span class="satisfaction-text">${stepData.interaction.satisfactionLabels[index]}</span>
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="commitment-section">
                            <label class="final-commitment">
                                <input type="checkbox" onchange="biasCardSession.updateFinalCommitment(this.checked)">
                                <span>💝 ${stepData.interaction.commitment}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * ⏭️ 다음 단계로 이동
     */
    nextStep() {
        if (this.currentStep < this.totalSteps - 1) {
            this.currentStep++;
            this.audioManager.stop();
            this.renderCurrentStep();
        } else {
            // 세션 완료
            this.completeSession();
        }
    }

    /**
     * ⏮️ 이전 단계로 이동
     */
    previousStep() {
        if (this.currentStep > 0) {
            this.currentStep--;
            this.audioManager.stop();
            this.renderCurrentStep();
        }
    }

    /**
     * 📊 진행도 업데이트
     */
    updateProgress() {
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        if (progressFill && progressText) {
            const progress = ((this.currentStep + 1) / this.totalSteps) * 100;
            progressFill.style.width = progress + '%';
            progressText.textContent = `${this.currentStep + 1} / ${this.totalSteps}`;
        }
    }

    /**
     * 🎵 음성 파일 로드
     */
    loadStepAudio(audioFile) {
        if (audioFile) {
            this.audioManager.load(audioFile);
        }
    }

    /**
     * 🔊 음성 재생/일시정지 토글
     */
    toggleAudio() {
        this.audioManager.toggle();
    }

    /**
     * 🎓 세션 완료 처리
     */
    completeSession() {
        // 카드 수집 효과
        if (window.biasCardSystem) {
            const evidence = {
                description: `${this.currentBias} 학습 세션을 완료하여 체계적으로 극복 방법을 학습했어요!`,
                sessionData: this.userResponses,
                completedAt: new Date().toISOString()
            };
            
            window.biasCardSystem.showCollectionCard(this.currentBias, evidence);
        }

        // 세션 완료 이벤트 발송
        const completionEvent = new CustomEvent('biasSessionCompleted', {
            detail: {
                biasName: this.currentBias,
                responses: this.userResponses,
                completedAt: Date.now()
            }
        });
        document.dispatchEvent(completionEvent);

        // 잠깐 후 세션 닫기
        setTimeout(() => {
            this.closeSession();
        }, 2000);
    }

    /**
     * 🔧 상호작용 핸들러들
     */
    selectOption(optionIndex) {
        this.userResponses[`step_${this.currentStep}_option`] = optionIndex;
        this.enableNextButton();
    }

    updateCheckboxes() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]:checked');
        this.userResponses[`step_${this.currentStep}_checkboxes`] = Array.from(checkboxes).map(cb => cb.value);
        this.enableNextButton();
    }

    updateRating(questionIndex, score) {
        if (!this.userResponses[`step_${this.currentStep}_ratings`]) {
            this.userResponses[`step_${this.currentStep}_ratings`] = {};
        }
        this.userResponses[`step_${this.currentStep}_ratings`][questionIndex] = score;
        this.checkAllRatingsComplete();
    }

    updateCommitments() {
        const commitments = document.querySelectorAll('.commitment-group input[type="checkbox"]:checked');
        this.userResponses[`step_${this.currentStep}_commitments`] = Array.from(commitments).map(cb => cb.value);
        this.enableNextButton();
    }

    updatePracticeResponse(value) {
        this.userResponses[`step_${this.currentStep}_practice`] = value;
        if (value.trim().length > 10) {
            this.enableNextButton();
        }
    }

    updateMethodCount(count) {
        this.userResponses[`step_${this.currentStep}_methodCount`] = count;
        this.enableNextButton();
    }

    updateSatisfaction(score) {
        this.userResponses['satisfaction'] = score;
        this.enableNextButton();
    }

    updateFinalCommitment(checked) {
        this.userResponses['finalCommitment'] = checked;
        this.enableNextButton();
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    enableNextButton() {
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.disabled = false;
            nextBtn.classList.add('enabled');
        }
    }

    checkAllRatingsComplete() {
        const totalQuestions = this.sessionData[this.currentBias].steps[this.currentStep].interaction.questions.length;
        const ratingsCount = Object.keys(this.userResponses[`step_${this.currentStep}_ratings`] || {}).length;
        
        if (ratingsCount >= totalQuestions) {
            this.enableNextButton();
        }
    }

    updateNavigation() {
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentStep === 0;
        }
        
        if (nextBtn) {
            nextBtn.disabled = true;
            nextBtn.classList.remove('enabled');
            
            if (this.currentStep === this.totalSteps - 1) {
                nextBtn.textContent = '세션 완료 🎉';
            } else {
                nextBtn.textContent = '다음 ▶';
            }
        }
    }

    /**
     * 🎪 이벤트 리스너
     */
    bindEventListeners() {
        // ESC 키로 세션 닫기
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isSessionActive) {
                this.closeSession();
            }
        });
    }
}

/**
 * 🎵 세션 음성 관리자
 */
class SessionAudioManager {
    constructor() {
        this.audio = null;
        this.isPlaying = false;
        this.currentFile = null;
    }

    load(audioFile) {
        this.stop();
        this.currentFile = audioFile;
        
        this.audio = new Audio(audioFile);
        this.audio.addEventListener('loadeddata', () => {
            this.updateUI();
        });
        
        this.audio.addEventListener('error', () => {
            console.warn(`음성 파일을 로드할 수 없습니다: ${audioFile}`);
            this.hideAudioControls();
        });

        this.audio.addEventListener('timeupdate', () => {
            this.updateProgress();
        });

        this.audio.addEventListener('ended', () => {
            this.isPlaying = false;
            this.updateUI();
        });
    }

    play() {
        if (this.audio) {
            this.audio.play();
            this.isPlaying = true;
            this.updateUI();
        }
    }

    pause() {
        if (this.audio) {
            this.audio.pause();
            this.isPlaying = false;
            this.updateUI();
        }
    }

    stop() {
        if (this.audio) {
            this.audio.pause();
            this.audio.currentTime = 0;
            this.isPlaying = false;
        }
    }

    toggle() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }

    updateUI() {
        const playPauseBtn = document.getElementById('playPauseBtn');
        const audioControls = document.getElementById('audioControls');
        
        if (playPauseBtn) {
            playPauseBtn.textContent = this.isPlaying ? '⏸️ 일시정지' : '🔊 음성 재생';
        }
        
        if (audioControls && this.audio) {
            audioControls.style.display = 'flex';
        }
    }

    updateProgress() {
        if (!this.audio) return;
        
        const progressBar = document.getElementById('audioProgressBar');
        const timeDisplay = document.getElementById('audioTime');
        
        if (progressBar) {
            const progress = (this.audio.currentTime / this.audio.duration) * 100;
            progressBar.style.width = progress + '%';
        }
        
        if (timeDisplay) {
            const current = this.formatTime(this.audio.currentTime);
            const total = this.formatTime(this.audio.duration);
            timeDisplay.textContent = `${current} / ${total}`;
        }
    }

    formatTime(seconds) {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    hideAudioControls() {
        const audioControls = document.getElementById('audioControls');
        if (audioControls) {
            audioControls.style.display = 'none';
        }
    }
}

// 전역 인스턴스 생성
window.biasCardSession = new BiasCardSession();