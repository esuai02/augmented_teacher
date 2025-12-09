/**
 * 🌌 실시간 우주적 개입 시스템
 * 문제풀이 전 과정에서 편향 감지와 우주적 서사를 통한 개입
 */

class CosmicInterventionSystem {
    constructor() {
        this.currentPhase = null;
        this.interventionActive = false;
        this.cosmicEffects = new CosmicEffectsEngine();
        this.phaseManager = new ProblemSolvingPhaseManager();
        this.interventionHistory = [];
        this.userState = {
            currentBiases: [],
            emotionalState: 'neutral',
            confidenceLevel: 0.5,
            problemSolvingPhase: 'not_started'
        };
        
        this.init();
    }

    /**
     * 🚀 시스템 초기화
     */
    init() {
        this.setupPhaseMonitoring();
        this.setupRealTimeInterceptors();
        this.initializeCosmicEnvironment();
    }

    /**
     * 📊 문제풀이 단계별 모니터링 설정
     */
    setupPhaseMonitoring() {
        // 문제 선택 시 (여정 시작)
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('journey-node')) {
                this.handlePhaseTransition('problem_selected', {
                    nodeId: e.target.id,
                    difficulty: this.assessDifficulty(e.target.id)
                });
            }
        });

        // 답안 입력 시작 (탐험 과정)
        document.addEventListener('focus', (e) => {
            if (e.target.id === 'answerInput') {
                this.handlePhaseTransition('input_started', {
                    timestamp: Date.now()
                });
            }
        });

        // 실시간 입력 모니터링 (사고 과정)
        document.addEventListener('input', (e) => {
            if (e.target.id === 'answerInput') {
                this.handleRealTimeInput(e.target.value);
            }
        });

        // 답안 제출 (여정 완료)
        const originalSubmit = window.submitAnswer;
        window.submitAnswer = () => {
            this.handlePhaseTransition('answer_submitted', {
                answer: document.getElementById('answerInput').value,
                timeSpent: this.calculateTimeSpent()
            });
            originalSubmit();
        };
    }

    /**
     * 🎯 단계별 전환 처리
     */
    async handlePhaseTransition(phase, data) {
        console.log(`🌌 Phase transition: ${this.currentPhase} → ${phase}`);
        
        const previousPhase = this.currentPhase;
        this.currentPhase = phase;
        this.userState.problemSolvingPhase = phase;

        // 단계별 우주적 내러티브 생성
        const narrative = await this.generatePhaseNarrative(phase, data, previousPhase);
        
        // 우주적 환경 변화
        this.cosmicEffects.updateEnvironmentForPhase(phase);
        
        // 편향 위험도 평가
        const biasRisk = this.assessBiasRisk(phase, data);
        
        if (biasRisk.level > 0.6) {
            await this.triggerPreventiveIntervention(biasRisk);
        }

        // 아바타 메시지 업데이트
        this.updateAvatarForPhase(phase, narrative);
    }

    /**
     * ⌨️ 실시간 입력 분석
     */
    handleRealTimeInput(inputText) {
        // 타이핑 패턴 분석
        const typingPattern = this.analyzeTypingPattern(inputText);
        
        // 실시간 편향 감지
        const detectedBiases = window.biasDetectionSystem?.detectRealTimeBias(inputText) || [];
        
        // 감정 상태 업데이트
        this.userState.emotionalState = this.detectEmotionalState(inputText, typingPattern);
        
        // 즉시 개입 필요성 판단
        if (this.requiresImmediateIntervention(detectedBiases, typingPattern)) {
            this.triggerUrgentCosmicIntervention(detectedBiases);
        }

        // 부드러운 가이던스 (편향이 심각하지 않은 경우)
        if (detectedBiases.length > 0 && !this.interventionActive) {
            this.triggerGentleGuidance(detectedBiases);
        }
    }

    /**
     * 🌅 단계별 우주적 내러티브 생성
     */
    async generatePhaseNarrative(phase, data, previousPhase) {
        const narrativeContext = {
            phase: phase,
            userData: data,
            previousPhase: previousPhase,
            userState: this.userState
        };

        switch (phase) {
            case 'problem_selected':
                return this.generateProblemStartNarrative(data);
            
            case 'input_started':
                return this.generateExplorationNarrative();
                
            case 'answer_submitted':
                return this.generateCompletionNarrative(data);
                
            default:
                return this.generateTransitionNarrative(narrativeContext);
        }
    }

    /**
     * 🌟 문제 시작 내러티브
     */
    generateProblemStartNarrative(data) {
        const { nodeId, difficulty } = data;
        const userName = this.getUserName();
        
        const narratives = {
            easy: [
                `${userName}님, 새로운 별빛이 당신을 부르고 있어요! ✨ 이 따뜻한 별은 친근한 에너지를 발산하고 있네요.`,
                `🌟 아름다운 발견의 순간이에요! 이 별은 ${userName}님을 위해 부드럽게 빛나고 있어요.`
            ],
            medium: [
                `${userName}님의 우주선이 새로운 행성계에 진입했어요! 🚀 센서가 흥미로운 신호를 포착하고 있네요.`,
                `🌌 미지의 수학 현상이 감지되었어요! ${userName}님의 탐험 본능이 깨어나고 있는 것 같아요.`
            ],
            hard: [
                `${userName}님, 우주의 심층부로 향하는 관문이 열렸어요! 🌑 이곳은 용감한 탐험가만이 도달할 수 있는 영역이에요.`,
                `⭐ 전설적인 수학 은하계가 모습을 드러냈어요! ${userName}님만의 독특한 접근이 필요할 것 같아요.`
            ]
        };

        const levelNarratives = narratives[difficulty] || narratives.medium;
        return levelNarratives[Math.floor(Math.random() * levelNarratives.length)];
    }

    /**
     * 🔍 탐험 과정 내러티브
     */
    generateExplorationNarrative() {
        const userName = this.getUserName();
        const explorationNarratives = [
            `${userName}님의 분석 장비가 가동되기 시작했어요! 🔬 우주의 신비가 하나씩 풀리고 있네요.`,
            `🌟 탐험이 시작됐어요! ${userName}님의 직감이 올바른 방향을 가리키고 있을 거예요.`,
            `🚀 ${userName}님만의 독특한 접근법이 새로운 발견으로 이어질 것 같아요!`
        ];
        
        return explorationNarratives[Math.floor(Math.random() * explorationNarratives.length)];
    }

    /**
     * 🎉 완료 내러티브
     */
    generateCompletionNarrative(data) {
        const { answer, timeSpent } = data;
        const userName = this.getUserName();
        
        // 답안 품질과 시간을 고려한 내러티브
        const quality = this.assessAnswerQuality(answer);
        const timeCategory = this.categorizeTimeSpent(timeSpent);

        if (quality === 'excellent') {
            return `🌟 놀라워요! ${userName}님이 우주의 새로운 법칙을 발견했어요! 이 발견이 더 큰 은하계로의 문을 열 거예요!`;
        } else if (quality === 'good') {
            return `✨ 훌륭한 여정이었어요! ${userName}님의 별자리가 또 하나 완성되었네요. 다음 모험이 기대돼요!`;
        } else {
            return `🌱 ${userName}님의 용기 있는 도전이 새로운 씨앗을 심었어요! 이 경험이 더 큰 성장의 밑거름이 될 거예요.`;
        }
    }

    /**
     * 🚨 긴급 우주적 개입
     */
    async triggerUrgentCosmicIntervention(detectedBiases) {
        if (this.interventionActive) return;
        
        this.interventionActive = true;
        const primaryBias = detectedBiases[0];
        
        // 우주적 개입 메시지 생성
        const intervention = await this.generateCosmicIntervention(primaryBias);
        
        // 시각적 효과
        this.cosmicEffects.triggerUrgentEffects(primaryBias.name);
        
        // 아바타 긴급 모드
        this.activateEmergencyAvatarMode(intervention);
        
        // 개입 기록
        this.recordIntervention('urgent', primaryBias, intervention);
        
        // 5초 후 정상 모드 복구
        setTimeout(() => {
            this.interventionActive = false;
            this.cosmicEffects.returnToNormalMode();
        }, 5000);
    }

    /**
     * 💫 부드러운 가이던스
     */
    triggerGentleGuidance(detectedBiases) {
        const guidance = this.generateGentleGuidance(detectedBiases);
        
        // subtle한 시각적 힌트
        this.cosmicEffects.addGentleHints(detectedBiases);
        
        // 아바타에 힌트 메시지
        this.showSubtleHint(guidance);
    }

    /**
     * 🎨 우주적 개입 메시지 생성
     */
    async generateCosmicIntervention(bias) {
        const userName = this.getUserName();
        
        const interventions = {
            확증편향: {
                alert: `🌌 ${userName}님, 확증편향의 중력장이 감지되었어요!`,
                metaphor: `지금 하나의 별만 보고 계시는군요. 하지만 우주에는 무수한 별자리가 있어요! 🌟`,
                guidance: `다른 행성의 수학자들은 이 문제를 어떻게 풀까요? 새로운 관점의 망원경을 꺼내보세요! 🔭`,
                action: `3가지 다른 방법을 생각해보는 건 어떨까요?`
            },
            재앙화사고: {
                alert: `⚠️ ${userName}님, 재앙화사고 소행성이 접근 중이에요!`,
                metaphor: `작은 운석을 행성 충돌로 보고 계시는군요. 실제로는 아름다운 유성우일 수도 있어요! 💫`,
                guidance: `우주에서 실수는 새로운 별이 탄생하는 과정이에요. ${userName}님의 실수도 성장의 별빛이 될 거예요.`,
                action: `깊게 숨을 쉬고, 이 문제를 다른 각도에서 바라보세요.`
            },
            자기과소평가: {
                alert: `🌑 ${userName}님, 자기과소평가 블랙홀이 빛을 삼키고 있어요!`,
                metaphor: `${userName}님 안의 별빛이 얼마나 밝은지 모르고 계시는군요. 이미 여기까지 온 것만으로도 대단한 여행자예요!`,
                guidance: `지금까지 해결한 문제들을 떠올려보세요. 그것들이 ${userName}님만의 별자리를 만들고 있어요. ⭐`,
                action: `자신을 믿고 한 발짝 더 나아가보세요.`
            }
        };

        return interventions[bias.name] || this.generateGenericIntervention(userName);
    }

    /**
     * 🔄 예방적 개입
     */
    async triggerPreventiveIntervention(biasRisk) {
        const preventiveMessage = this.generatePreventiveMessage(biasRisk);
        
        // 미묘한 환경 변화로 주의 환기
        this.cosmicEffects.createPreventiveAtmosphere(biasRisk.biases);
        
        // 아바타가 자연스럽게 힌트 제공
        this.deliverPreventiveHint(preventiveMessage);
    }

    /**
     * 🎭 아바타 모드 전환
     */
    activateEmergencyAvatarMode(intervention) {
        const avatarSpeech = document.getElementById('avatarSpeech');
        const avatar = document.getElementById('avatar');
        
        if (avatarSpeech && avatar) {
            // 긴급 스타일 적용
            avatarSpeech.classList.add('urgent-intervention');
            avatar.classList.add('emergency-glow');
            
            // 구조화된 개입 메시지 표시
            avatarSpeech.innerHTML = `
                <div class="intervention-alert">${intervention.alert}</div>
                <div class="cosmic-metaphor">${intervention.metaphor}</div>
                <div class="guidance-text">${intervention.guidance}</div>
                <div class="action-suggestion">${intervention.action}</div>
            `;
        }
    }

    /**
     * 💡 부드러운 힌트 표시
     */
    showSubtleHint(guidance) {
        const avatarSpeech = document.getElementById('avatarSpeech');
        
        if (avatarSpeech) {
            // 기존 메시지에 힌트 추가
            const currentMessage = avatarSpeech.textContent;
            avatarSpeech.innerHTML = `
                ${currentMessage}
                <div class="gentle-hint">${guidance}</div>
            `;
            
            // 3초 후 힌트 제거
            setTimeout(() => {
                const hint = avatarSpeech.querySelector('.gentle-hint');
                if (hint) hint.remove();
            }, 3000);
        }
    }

    /**
     * 🔍 편향 위험도 평가
     */
    assessBiasRisk(phase, data) {
        const risks = {
            problem_selected: ['회피행동', '자기과소평가'],
            input_started: ['확증편향', '완벽주의'],
            answer_submitted: ['재앙화사고', '후회편향']
        };

        const phaseRisks = risks[phase] || [];
        const userBiasHistory = this.getUserBiasHistory();
        
        // 과거 편향 이력과 현재 상황을 조합하여 위험도 계산
        let riskLevel = 0;
        const applicableBiases = [];

        phaseRisks.forEach(bias => {
            if (userBiasHistory[bias]) {
                riskLevel += userBiasHistory[bias].frequency * 0.3;
                applicableBiases.push(bias);
            }
        });

        return {
            level: Math.min(riskLevel, 1.0),
            biases: applicableBiases,
            phase: phase
        };
    }

    /**
     * ⏱️ 즉시 개입 필요성 판단
     */
    requiresImmediateIntervention(biases, typingPattern) {
        // 고위험 편향 감지
        const highRiskBiases = ['재앙화사고', '자기과소평가', '학습된무력감'];
        const hasHighRiskBias = biases.some(b => 
            highRiskBiases.includes(b.name) && b.score > 0.7
        );

        // 타이핑 패턴에서 심각한 문제 징후
        const severeTypingIssues = typingPattern.excessiveDeleting || 
                                  typingPattern.longPauses > 3 ||
                                  typingPattern.rapidGiving Up;

        return hasHighRiskBias || severeTypingIssues;
    }

    /**
     * 📊 사용자 편향 이력 조회
     */
    getUserBiasHistory() {
        // TODO: DB에서 실제 데이터 조회
        return {
            확증편향: { frequency: 0.6, severity: 0.4 },
            재앙화사고: { frequency: 0.8, severity: 0.7 },
            자기과소평가: { frequency: 0.5, severity: 0.6 }
        };
    }

    /**
     * 📝 개입 기록
     */
    recordIntervention(type, bias, intervention) {
        const record = {
            timestamp: Date.now(),
            type: type,
            bias: bias,
            intervention: intervention,
            phase: this.currentPhase,
            userState: { ...this.userState }
        };

        this.interventionHistory.push(record);
        
        // TODO: DB에 저장
        console.log('🌌 우주적 개입 기록:', record);
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    getUserName() {
        return "우주 탐험가"; // TODO: 실제 사용자 이름
    }

    assessDifficulty(nodeId) {
        const difficultyMap = {
            'node-0': 'easy',
            'node-1': 'medium',
            'node-2': 'medium',
            'node-3': 'hard',
            'node-4': 'hard',
            'node-5': 'hard',
            'node-6': 'very_hard',
            'node-7': 'very_hard',
            'node-8': 'legendary'
        };
        return difficultyMap[nodeId] || 'medium';
    }

    calculateTimeSpent() {
        // TODO: 실제 시간 계산
        return Date.now() - (this.phaseStartTime || Date.now());
    }

    assessAnswerQuality(answer) {
        if (answer.length > 100 && /[0-9]+/.test(answer)) return 'excellent';
        if (answer.length > 50) return 'good';
        return 'basic';
    }

    analyzeTypingPattern(text) {
        // TODO: 실제 타이핑 패턴 분석
        return {
            excessiveDeleting: false,
            longPauses: 0,
            rapidGivingUp: false
        };
    }

    detectEmotionalState(text, pattern) {
        // 감정 상태 감지 로직
        if (text.includes('어려') || text.includes('못하겠')) return 'frustrated';
        if (text.includes('재미') || text.includes('좋')) return 'positive';
        return 'neutral';
    }
}

/**
 * 🎨 우주적 효과 엔진
 */
class CosmicEffectsEngine {
    constructor() {
        this.currentEffects = [];
        this.environmentState = 'normal';
    }

    updateEnvironmentForPhase(phase) {
        const effects = {
            problem_selected: () => this.createStarFormationEffect(),
            input_started: () => this.createThinkingNebulaEffect(),
            answer_submitted: () => this.createCompletionBurstEffect()
        };

        const effect = effects[phase];
        if (effect) effect();
    }

    triggerUrgentEffects(biasType) {
        const urgentEffects = {
            확증편향: () => this.createTunnelVisionEffect(),
            재앙화사고: () => this.createStormWarningEffect(),
            자기과소평가: () => this.createDimmingStarsEffect()
        };

        const effect = urgentEffects[biasType];
        if (effect) effect();
    }

    createStarFormationEffect() {
        // 새로운 별 생성 애니메이션
        console.log('✨ 별 생성 효과');
    }

    createThinkingNebulaEffect() {
        // 사고 과정을 나타내는 성운 효과
        console.log('🌌 사고 성운 효과');
    }

    createTunnelVisionEffect() {
        // 터널 비전을 나타내는 시각적 효과
        console.log('🕳️ 터널 비전 효과');
    }

    returnToNormalMode() {
        this.environmentState = 'normal';
        console.log('🌟 정상 모드 복구');
    }
}

/**
 * 📊 문제풀이 단계 관리자
 */
class ProblemSolvingPhaseManager {
    constructor() {
        this.phases = [
            'problem_approach',
            'strategy_selection', 
            'execution',
            'verification',
            'reflection'
        ];
        this.currentPhaseIndex = 0;
    }

    getCurrentPhase() {
        return this.phases[this.currentPhaseIndex];
    }

    advancePhase() {
        if (this.currentPhaseIndex < this.phases.length - 1) {
            this.currentPhaseIndex++;
        }
        return this.getCurrentPhase();
    }

    resetPhases() {
        this.currentPhaseIndex = 0;
    }
}

// 전역 인스턴스 생성
window.cosmicInterventionSystem = new CosmicInterventionSystem();