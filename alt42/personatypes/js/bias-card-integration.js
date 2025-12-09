/**
 * 🃏 편향 카드 시스템 통합 모듈
 * 기존 시스템들과 카드 시스템을 연결하는 통합 레이어
 */

class BiasCardIntegration {
    constructor() {
        this.integrationReady = false;
        this.init();
    }

    /**
     * 🚀 통합 시스템 초기화
     */
    init() {
        // 모든 시스템이 로드될 때까지 대기
        this.waitForSystemsReady().then(() => {
            this.setupIntegrations();
            this.integrationReady = true;
            console.log('🃏 편향 카드 시스템 통합 완료');
        });
    }

    /**
     * ⏳ 시스템 준비 대기
     */
    async waitForSystemsReady() {
        const checkSystems = () => {
            return window.biasDetectionSystem && 
                   window.cosmicInterventionSystem && 
                   window.biasCardSystem;
        };

        while (!checkSystems()) {
            await new Promise(resolve => setTimeout(resolve, 100));
        }
    }

    /**
     * 🔗 시스템 간 통합 설정
     */
    setupIntegrations() {
        this.integrateBiasDetectionWithCards();
        this.integrateCosmicInterventionWithCards();
        this.setupAnswerSubmissionIntegration();
        this.setupTeacherMemoIntegration();
    }

    /**
     * 🔍 편향 감지 시스템과 카드 연동
     */
    integrateBiasDetectionWithCards() {
        // 기존 편향 감지 메소드 확장
        const originalDetectBias = window.biasDetectionSystem.detectRealTimeBias.bind(window.biasDetectionSystem);
        
        window.biasDetectionSystem.detectRealTimeBias = (inputText) => {
            const detectedBiases = originalDetectBias(inputText);
            
            // 감지된 편향들에 대해 카드 표시
            detectedBiases.forEach(bias => {
                if (bias.score > 0.6) {
                    this.triggerBiasCard(bias, inputText);
                }
            });
            
            return detectedBiases;
        };
    }

    /**
     * 🌌 우주적 개입 시스템과 카드 연동
     */
    integrateCosmicInterventionWithCards() {
        // 긴급 개입 시 카드 표시
        const originalUrgentIntervention = window.cosmicInterventionSystem.triggerUrgentCosmicIntervention.bind(window.cosmicInterventionSystem);
        
        window.cosmicInterventionSystem.triggerUrgentCosmicIntervention = async (detectedBiases) => {
            // 기존 개입 실행
            await originalUrgentIntervention(detectedBiases);
            
            // 가장 심각한 편향에 대한 카드 표시
            const primaryBias = detectedBiases[0];
            if (primaryBias && primaryBias.score > 0.8) {
                window.biasCardSystem.showDetectionCard(
                    primaryBias.name, 
                    primaryBias.score, 
                    { 
                        trigger: this.findTriggerWord(primaryBias),
                        urgent: true 
                    }
                );
            }
        };
    }

    /**
     * 📝 답안 제출 시 편향 극복 카드 수집
     */
    setupAnswerSubmissionIntegration() {
        // submitAnswer 함수 래핑
        const originalSubmitAnswer = window.submitAnswer;
        
        window.submitAnswer = () => {
            // 현재 세션의 편향 감지 기록 분석
            const overcomeBiases = this.analyzeOvercomeBiases();
            
            // 기존 답안 제출 로직 실행
            if (originalSubmitAnswer) {
                originalSubmitAnswer();
            }
            
            // 극복한 편향들에 대한 카드 수집
            setTimeout(() => {
                overcomeBiases.forEach(bias => {
                    window.biasCardSystem.showCollectionCard(bias.name, bias.evidence);
                });
            }, 1000);
        };
    }

    /**
     * 🧑‍🏫 선생님 메모와 카드 연동
     */
    setupTeacherMemoIntegration() {
        // 선생님이 편향을 체크했을 때 해당 학생에게 카드 알림
        document.addEventListener('teacherObservationSaved', (event) => {
            const { studentId, observedBiases, detailedMemo } = event.detail;
            
            // 현재 사용자가 관찰된 학생이면 카드 표시
            if (this.isCurrentUser(studentId)) {
                observedBiases.forEach(biasName => {
                    window.biasCardSystem.showDetectionCard(
                        biasName,
                        0.9, // 선생님 관찰은 높은 신뢰도
                        {
                            trigger: '선생님 관찰',
                            teacherNote: detailedMemo,
                            source: 'teacher_observation'
                        }
                    );
                });
            }
        });
    }

    /**
     * 🃏 편향 카드 트리거
     */
    triggerBiasCard(bias, inputText) {
        const trigger = this.extractTriggerFromText(inputText, bias.name);
        
        window.biasCardSystem.showDetectionCard(
            bias.name,
            bias.score,
            {
                trigger: trigger,
                inputText: inputText,
                timestamp: Date.now(),
                source: 'real_time_detection'
            }
        );
    }

    /**
     * 🔍 극복한 편향 분석
     */
    analyzeOvercomeBiases() {
        const overcomeBiases = [];
        const sessionData = this.getSessionAnalysis();
        
        // 문제 풀이 과정에서 편향을 감지했다가 극복한 케이스 찾기
        sessionData.detectedBiases.forEach(bias => {
            if (this.wasOvercome(bias, sessionData)) {
                overcomeBiases.push({
                    name: bias.name,
                    evidence: {
                        description: this.generateOvercomeEvidence(bias, sessionData),
                        beforeBehavior: bias.initialEvidence,
                        afterBehavior: sessionData.finalAnswer,
                        timeToOvercome: sessionData.endTime - bias.detectedAt
                    }
                });
            }
        });
        
        return overcomeBiases;
    }

    /**
     * 📊 세션 분석 데이터 생성
     */
    getSessionAnalysis() {
        const answerInput = document.getElementById('answerInput');
        const answerText = answerInput ? answerInput.value : '';
        
        return {
            detectedBiases: window.biasDetectionSystem?.sessionBiases || [],
            finalAnswer: answerText,
            answerQuality: this.assessAnswerQuality(answerText),
            timeSpent: Date.now() - (window.sessionStartTime || Date.now()),
            endTime: Date.now(),
            approachDiversity: this.analyzeApproachDiversity(answerText)
        };
    }

    /**
     * 🎯 편향 극복 여부 판단
     */
    wasOvercome(bias, sessionData) {
        const overcomeCriteria = {
            확증편향: () => sessionData.approachDiversity >= 2, // 2가지 이상 방법 시도
            재앙화사고: () => sessionData.answerQuality !== 'incomplete', // 끝까지 완료
            자기과소평가: () => sessionData.finalAnswer.length > 30, // 충분한 답안 작성
            완벽주의: () => sessionData.timeSpent < 1800000, // 30분 이내 완료 (너무 오래 고민하지 않음)
            흑백사고: () => sessionData.finalAnswer.includes('또는') || sessionData.finalAnswer.includes('다른'), // 대안적 사고
            회피행동: () => sessionData.answerQuality !== 'incomplete' // 포기하지 않고 완료
        };
        
        const criterion = overcomeCriteria[bias.name];
        return criterion ? criterion() : false;
    }

    /**
     * 📝 극복 증거 생성
     */
    generateOvercomeEvidence(bias, sessionData) {
        const evidenceTemplates = {
            확증편향: `처음에는 한 가지 방법만 고집했지만, 최종적으로 ${sessionData.approachDiversity}가지 다른 접근법을 시도했어요!`,
            재앙화사고: `작은 어려움에 좌절했지만 끝까지 포기하지 않고 문제를 완료했어요!`,
            자기과소평가: `"못하겠다"고 했지만 실제로는 ${sessionData.finalAnswer.length}자의 상세한 답안을 작성했어요!`,
            완벽주의: `완벽을 추구하느라 시간을 너무 쓰지 않고 적절한 시점에 답안을 제출했어요!`,
            흑백사고: `흑백논리에서 벗어나 다양한 가능성을 고려한 답안을 작성했어요!`,
            회피행동: `어려움을 피하지 않고 도전을 끝까지 완수했어요!`
        };
        
        return evidenceTemplates[bias.name] || `${bias.name}를 성공적으로 극복했어요!`;
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    extractTriggerFromText(text, biasName) {
        const biasPatterns = window.biasDetectionSystem?.biasPatterns;
        if (!biasPatterns) return '';
        
        // 편향별 키워드 찾기
        for (const level of Object.values(biasPatterns)) {
            if (level[biasName] && level[biasName].keywords) {
                for (const keyword of level[biasName].keywords) {
                    if (text.includes(keyword)) {
                        return keyword;
                    }
                }
            }
        }
        return '';
    }

    findTriggerWord(bias) {
        return bias.evidence || bias.name;
    }

    isCurrentUser(studentId) {
        // TODO: 실제 사용자 ID 비교 로직
        return true; // 현재는 모든 사용자에게 표시
    }

    assessAnswerQuality(answerText) {
        if (!answerText || answerText.trim().length < 10) return 'incomplete';
        if (answerText.length > 100 && answerText.includes('이유')) return 'excellent';
        if (answerText.length > 50) return 'good';
        return 'basic';
    }

    analyzeApproachDiversity(answerText) {
        const approachIndicators = [
            '첫번째', '두번째', '다른 방법', '또는', '그런데', '하지만', 
            '대신', '반대로', '다시', '만약', '경우'
        ];
        
        let diversityCount = 0;
        approachIndicators.forEach(indicator => {
            if (answerText.includes(indicator)) {
                diversityCount++;
            }
        });
        
        return Math.min(diversityCount, 3); // 최대 3개까지
    }
}

/**
 * 🎮 카드 상호작용 시나리오별 정의
 */
class BiasCardScenarios {
    /**
     * 🌅 문제 풀이 시작 시 카드 상호작용
     */
    static onProblemStart(nodeId) {
        const riskBiases = BiasCardScenarios.assessInitialRisk(nodeId);
        
        // 위험도가 높은 편향에 대한 예방적 카드 표시
        riskBiases.forEach(bias => {
            if (bias.riskLevel > 0.7) {
                window.biasCardSystem.showDetectionCard(
                    bias.name,
                    bias.riskLevel,
                    {
                        trigger: '예방적 알림',
                        phase: 'problem_start',
                        preventive: true
                    }
                );
            }
        });
    }

    /**
     * 🔍 입력 과정 중 카드 상호작용
     */
    static onInputProcess(inputText, typingPattern) {
        // 실시간 편향 감지는 기본 시스템에서 처리
        // 여기서는 특별한 패턴만 추가 처리
        
        if (typingPattern.excessiveDeleting && typingPattern.longPauses > 2) {
            // 완벽주의 편향 의심
            window.biasCardSystem.showDetectionCard(
                '완벽주의',
                0.8,
                {
                    trigger: '과도한 수정',
                    pattern: 'excessive_editing',
                    suggestion: '완벽하지 않아도 진행해보세요'
                }
            );
        }
    }

    /**
     * 🎉 문제 완료 시 카드 상호작용
     */
    static onProblemComplete(answerData) {
        // 통합 시스템에서 자동으로 처리되지만
        // 특별한 성취에 대한 추가 카드 표시
        
        if (answerData.qualityImprovement > 0.3) {
            // 특별 성취 카드
            window.biasCardSystem.showSpecialAchievementCard({
                title: '🌟 놀라운 성장',
                description: '이번 문제에서 이전보다 훨씬 발전된 모습을 보여주셨어요!',
                reward: 'growth_star'
            });
        }
    }

    /**
     * 📊 초기 위험도 평가
     */
    static assessInitialRisk(nodeId) {
        const difficultyMap = {
            'node-0': 'easy',
            'node-1': 'easy', 
            'node-2': 'medium',
            'node-3': 'medium',
            'node-4': 'hard',
            'node-5': 'hard',
            'node-6': 'very_hard',
            'node-7': 'very_hard',
            'node-8': 'legendary'
        };
        
        const difficulty = difficultyMap[nodeId] || 'medium';
        const userHistory = BiasCardScenarios.getUserBiasHistory();
        
        const riskBiases = [];
        
        // 난이도별 위험 편향
        const riskMap = {
            easy: [{ name: '자기과신', riskLevel: 0.3 }],
            medium: [
                { name: '확증편향', riskLevel: 0.5 },
                { name: '자기과소평가', riskLevel: 0.4 }
            ],
            hard: [
                { name: '재앙화사고', riskLevel: 0.7 },
                { name: '회피행동', riskLevel: 0.6 },
                { name: '완벽주의', riskLevel: 0.5 }
            ],
            very_hard: [
                { name: '재앙화사고', riskLevel: 0.8 },
                { name: '학습된무력감', riskLevel: 0.7 }
            ],
            legendary: [
                { name: '던닝크루거효과', riskLevel: 0.6 },
                { name: '과신편향', riskLevel: 0.5 }
            ]
        };
        
        const risks = riskMap[difficulty] || [];
        
        // 사용자 이력을 고려하여 위험도 조정
        risks.forEach(risk => {
            if (userHistory[risk.name]) {
                risk.riskLevel += userHistory[risk.name].frequency * 0.3;
            }
        });
        
        return risks;
    }

    static getUserBiasHistory() {
        // TODO: 실제 사용자 편향 이력 조회
        return {
            확증편향: { frequency: 0.6, severity: 0.4 },
            재앙화사고: { frequency: 0.8, severity: 0.7 },
            자기과소평가: { frequency: 0.5, severity: 0.6 }
        };
    }
}

// 전역 인스턴스 생성
window.biasCardIntegration = new BiasCardIntegration();
window.biasCardScenarios = BiasCardScenarios;