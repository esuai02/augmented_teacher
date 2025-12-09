/**
 * 🧠 수학적 사고 프레임워크
 * 9개의 수학 사고 노드와 60개의 인지관성을 매핑하여
 * 문제 해결 능력을 향상시키는 통합 시스템
 */

class MathematicalThinkingFramework {
    constructor() {
        this.nodes = this.defineThinkingNodes();
        this.biasMapping = this.mapBiasesToNodes();
        this.problemSolvingNarrative = this.createNarrative();
        this.currentState = this.initializeState();
    }

    /**
     * 🎯 9개의 수학적 사고 노드 정의
     */
    defineThinkingNodes() {
        return {
            reflection: {
                id: 0,
                name: "수학 여정의 시작",
                koreanName: "성찰적 사고",
                description: "메타인지와 자기성찰을 통한 학습 과정 인식",
                color: "#667eea",
                symbol: "🌟",
                thinkingPattern: "자신의 사고 과정을 관찰하고 평가하는 능력",
                keyQuestions: [
                    "내가 이 문제를 어떻게 이해했는가?",
                    "나의 접근 방식은 효과적인가?",
                    "무엇을 놓치고 있을까?"
                ],
                problemSolvingRole: "문제 이해의 출발점",
                activeStrength: 0
            },
            
            calculation: {
                id: 1,
                name: "계산과의 만남",
                koreanName: "계산적 사고",
                description: "숫자와 연산을 다루는 체계적 접근",
                color: "#764ba2",
                symbol: "🔢",
                thinkingPattern: "정확성과 효율성을 추구하는 계산 전략",
                keyQuestions: [
                    "어떤 계산 방법이 가장 효율적인가?",
                    "계산 과정을 단순화할 수 있는가?",
                    "검산을 어떻게 할 것인가?"
                ],
                problemSolvingRole: "정확한 수치 처리",
                activeStrength: 0
            },
            
            geometry: {
                id: 2,
                name: "도형의 세계",
                koreanName: "공간적 사고",
                description: "시각적, 공간적 관계 이해",
                color: "#f59e0b",
                symbol: "📐",
                thinkingPattern: "도형과 공간의 관계를 시각화하는 능력",
                keyQuestions: [
                    "이 문제를 그림으로 표현할 수 있는가?",
                    "도형의 성질을 어떻게 활용할 것인가?",
                    "공간적 관계는 무엇인가?"
                ],
                problemSolvingRole: "시각적 표현과 이해",
                activeStrength: 0
            },
            
            operation: {
                id: 3,
                name: "연산의 깊이",
                koreanName: "연산적 사고",
                description: "사칙연산의 깊은 이해와 활용",
                color: "#10b981",
                symbol: "➕",
                thinkingPattern: "연산의 의미와 관계를 깊이 이해",
                keyQuestions: [
                    "어떤 연산이 필요한가?",
                    "연산의 순서는 어떻게 되는가?",
                    "역연산으로 검증할 수 있는가?"
                ],
                problemSolvingRole: "기본 연산의 정확한 적용",
                activeStrength: 0
            },
            
            strategy: {
                id: 4,
                name: "문제 해결 전략",
                koreanName: "전략적 사고",
                description: "체계적인 문제 해결 접근법",
                color: "#8b5cf6",
                symbol: "🎯",
                thinkingPattern: "다양한 해결 전략을 선택하고 적용",
                keyQuestions: [
                    "어떤 전략이 적합한가?",
                    "다른 접근 방법은 없는가?",
                    "이전 경험을 활용할 수 있는가?"
                ],
                problemSolvingRole: "해결 경로 설계",
                activeStrength: 0
            },
            
            pattern: {
                id: 5,
                name: "패턴의 발견",
                koreanName: "패턴 인식",
                description: "규칙성과 패턴을 발견하고 활용",
                color: "#ec4899",
                symbol: "🔄",
                thinkingPattern: "반복되는 구조와 규칙 파악",
                keyQuestions: [
                    "어떤 패턴이 보이는가?",
                    "이 패턴이 계속되면 어떻게 될까?",
                    "일반화할 수 있는가?"
                ],
                problemSolvingRole: "규칙성 발견과 적용",
                activeStrength: 0
            },
            
            insight: {
                id: 6,
                name: "깨달음의 순간",
                koreanName: "통찰적 사고",
                description: "직관과 통찰을 통한 이해",
                color: "#06b6d4",
                symbol: "💡",
                thinkingPattern: "갑작스러운 이해와 연결의 순간",
                keyQuestions: [
                    "핵심 아이디어는 무엇인가?",
                    "숨겨진 관계가 있는가?",
                    "다른 관점에서 보면 어떤가?"
                ],
                problemSolvingRole: "창의적 돌파구",
                activeStrength: 0
            },
            
            prediction: {
                id: 7,
                name: "미래 예측",
                koreanName: "예측적 사고",
                description: "추론과 예측을 통한 확장",
                color: "#f97316",
                symbol: "🔮",
                thinkingPattern: "결과를 예측하고 가설을 세우는 능력",
                keyQuestions: [
                    "이 방법을 적용하면 어떻게 될까?",
                    "다음 단계는 무엇일까?",
                    "일반적인 경우는 어떨까?"
                ],
                problemSolvingRole: "결과 예측과 검증",
                activeStrength: 0
            },
            
            mastery: {
                id: 8,
                name: "여정의 정점",
                koreanName: "통합적 사고",
                description: "모든 사고를 통합한 마스터리",
                color: "#dc2626",
                symbol: "👑",
                thinkingPattern: "모든 사고 방식을 융합한 종합적 접근",
                keyQuestions: [
                    "가장 효과적인 조합은 무엇인가?",
                    "전체적인 그림은 어떤가?",
                    "더 깊은 의미는 무엇인가?"
                ],
                problemSolvingRole: "종합적 문제 해결",
                activeStrength: 0
            }
        };
    }

    /**
     * 🗺️ 60개 인지편향을 9개 노드에 매핑
     */
    mapBiasesToNodes() {
        return {
            // 성찰적 사고 (Reflection) - 메타인지 관련 편향들
            reflection: [
                'DunningKrugerEffect',    // 자신의 능력을 과대평가
                'Overconfidence',         // 과신
                'SelfServingBias',        // 자기중심적 해석
                'BlindSpotBias',          // 자신의 편향을 인식하지 못함
                'IllusionOfControl',      // 통제 착각
                'PlanningFallacy',        // 계획 오류
                'OptimismBias'            // 낙관주의 편향
            ],
            
            // 계산적 사고 (Calculation) - 수치 처리 관련 편향들
            calculation: [
                'AnchoringBias',          // 첫 번째 정보에 고착
                'AvailabilityHeuristic',  // 쉽게 떠오르는 예시 과대평가
                'BaseRateNeglect',        // 기본 확률 무시
                'GamblerssFallacy',       // 도박사의 오류
                'HotHandFallacy',         // 연속 성공 과대평가
                'RegressionToMean',       // 평균으로의 회귀 무시
                'ZeroSumBias'             // 제로섬 사고
            ],
            
            // 공간적 사고 (Geometry) - 시각적 인식 관련 편향들
            geometry: [
                'ClusteringIllusion',     // 무작위에서 패턴 찾기
                'IllusoryCorrelation',    // 가짜 상관관계
                'PareidoliaEffect',       // 무의미한 것에서 의미 찾기
                'SemmelweisReflex',       // 새로운 증거 거부
                'FocusingEffect',         // 한 측면에만 집중
                'FramingEffect',          // 표현 방식에 따른 판단
                'ContrastEffect'          // 대비 효과
            ],
            
            // 연산적 사고 (Operation) - 논리 연산 관련 편향들
            operation: [
                'ConjunctionFallacy',     // 결합 오류
                'FalseConsensus',         // 거짓 합의
                'RepresentativenessHeuristic', // 대표성 휴리스틱
                'SimulationHeuristic',    // 시뮬레이션 휴리스틱
                'LawOfSmallNumbers',      // 작은 수의 법칙
                'NeglectOfProbability',   // 확률 무시
                'AmbiguityEffect'         // 모호성 효과
            ],
            
            // 전략적 사고 (Strategy) - 의사결정 관련 편향들
            strategy: [
                'StatusQuoBias',          // 현상 유지 편향
                'SunkCostFallacy',        // 매몰 비용 오류
                'LossAversion',           // 손실 회피
                'EndowmentEffect',        // 소유 효과
                'DispositionEffect',      // 처분 효과
                'RestraintBias',          // 자제력 과대평가
                'ActionBias'              // 행동 편향
            ],
            
            // 패턴 인식 (Pattern) - 패턴 관련 편향들
            pattern: [
                'Apophenia',              // 무의미한 것에서 패턴 찾기
                'RecencyEffect',          // 최신 정보 과대평가
                'PrimacyEffect',          // 첫 정보 과대평가
                'SerialPositionEffect',   // 순서 위치 효과
                'StereotypingBias',       // 고정관념
                'OutgroupHomogeneity',    // 외집단 동질성
                'InGroupBias'             // 내집단 편향
            ],
            
            // 통찰적 사고 (Insight) - 직관 관련 편향들
            insight: [
                'HindsightBias',          // 사후 과잉 확신
                'CurseOfKnowledge',       // 지식의 저주
                'FalseMemory',            // 거짓 기억
                'CryptomnesiaBias',       // 잠재 기억
                'IKEAEffect',             // 노력 가치 과대평가
                'GenerationEffect',       // 생성 효과
                'GoogleEffect'            // 구글 효과
            ],
            
            // 예측적 사고 (Prediction) - 미래 예측 관련 편향들
            prediction: [
                'ProjectionBias',         // 투사 편향
                'AffectiveForecasting',   // 감정 예측 오류
                'ImpactBias',             // 영향력 과대평가
                'DurationNeglect',        // 지속 시간 무시
                'PeakEndRule',            // 정점-종점 규칙
                'TemporalDiscounting',    // 시간 할인
                'PresentBias'             // 현재 편향
            ],
            
            // 통합적 사고 (Mastery) - 종합적 판단 관련 편향들
            mastery: [
                'ConfirmationBias',       // 확증 편향
                'Groupthink',             // 집단 사고
                'BandwagonEffect',        // 편승 효과
                'AuthorityBias',          // 권위자 편향
                'HaloEffect',             // 후광 효과
                'FundamentalAttributionError', // 기본 귀인 오류
                'JustWorldHypothesis',    // 공정한 세계 가설
                'SystemJustification',    // 체제 정당화
                'MotivatedReasoning'      // 동기화된 추론
            ]
        };
    }

    /**
     * 📖 문제 해결 서사 구조
     */
    createNarrative() {
        return {
            title: "수학적 사고의 우주 여행",
            
            acts: {
                // Act 1: 문제와의 만남
                encounter: {
                    name: "문제와의 조우",
                    description: "새로운 수학 문제를 만나는 순간",
                    primaryNodes: ['reflection', 'strategy'],
                    narrative: "우주 탐험가인 당신은 새로운 수학 문제라는 미지의 행성을 발견합니다.",
                    challenges: [
                        "문제를 정확히 이해하기",
                        "주어진 정보 파악하기",
                        "목표 명확히 하기"
                    ],
                    biasRisks: [
                        "Overconfidence - 문제를 너무 쉽게 생각함",
                        "AnchoringBias - 첫인상에 고착됨",
                        "FramingEffect - 문제 표현에 휘둘림"
                    ]
                },
                
                // Act 2: 탐색과 시도
                exploration: {
                    name: "탐색의 시간",
                    description: "다양한 접근법을 시도하는 단계",
                    primaryNodes: ['calculation', 'geometry', 'operation'],
                    narrative: "행성의 지형을 탐색하며 다양한 도구와 방법을 시험해봅니다.",
                    challenges: [
                        "적절한 방법 선택하기",
                        "계산 실수 피하기",
                        "시각적 표현 활용하기"
                    ],
                    biasRisks: [
                        "ConfirmationBias - 원하는 답만 찾기",
                        "SunkCostFallacy - 잘못된 방법 고집",
                        "AvailabilityHeuristic - 익숙한 방법만 사용"
                    ]
                },
                
                // Act 3: 패턴 발견
                discovery: {
                    name: "패턴의 발견",
                    description: "문제의 구조와 패턴을 파악하는 단계",
                    primaryNodes: ['pattern', 'insight'],
                    narrative: "탐험 중 숨겨진 패턴과 규칙을 발견하기 시작합니다.",
                    challenges: [
                        "진짜 패턴 구별하기",
                        "규칙 일반화하기",
                        "통찰력 발휘하기"
                    ],
                    biasRisks: [
                        "ClusteringIllusion - 가짜 패턴 보기",
                        "Apophenia - 무의미한 연결 만들기",
                        "IllusoryCorrelation - 잘못된 상관관계"
                    ]
                },
                
                // Act 4: 해결과 검증
                resolution: {
                    name: "해답의 정점",
                    description: "솔루션을 완성하고 검증하는 단계",
                    primaryNodes: ['prediction', 'mastery'],
                    narrative: "모든 발견을 종합하여 문제의 열쇠를 찾아냅니다.",
                    challenges: [
                        "해답 검증하기",
                        "일반화 가능성 확인",
                        "다른 방법 고려하기"
                    ],
                    biasRisks: [
                        "HindsightBias - 당연했다고 생각",
                        "DunningKrugerEffect - 과도한 자신감",
                        "ProjectionBias - 미래 문제 과소평가"
                    ]
                }
            },
            
            // 인지관성 극복 메커니즘
            biasOvercomeMechanism: {
                detection: "편향이 감지되면 관련 노드가 활성화",
                intervention: "해당 노드의 사고 패턴으로 편향 교정",
                reinforcement: "성공적인 극복 시 노드 강화",
                integration: "모든 노드가 협력하여 통합적 해결"
            }
        };
    }

    /**
     * 🎮 현재 상태 초기화
     */
    initializeState() {
        return {
            currentProblem: null,
            activeNodes: [],
            detectedBiases: [],
            overcameBiases: [],
            solutionPath: [],
            nodeStrengths: {},
            narrativeStage: 'encounter',
            insights: []
        };
    }

    /**
     * 🧩 문제 해결 프로세스
     */
    solveProblem(problem) {
        const solution = {
            problem: problem,
            stages: [],
            biasesDetected: [],
            biasesOvercome: [],
            nodesActivated: [],
            finalSolution: null
        };

        // Stage 1: 문제 분석
        solution.stages.push(this.analyzeProblem(problem));
        
        // Stage 2: 편향 감지
        const detectedBiases = this.detectPotentialBiases(problem);
        solution.biasesDetected = detectedBiases;
        
        // Stage 3: 노드 활성화
        const activatedNodes = this.activateRelevantNodes(problem, detectedBiases);
        solution.nodesActivated = activatedNodes;
        
        // Stage 4: 편향 극복
        solution.biasesOvercome = this.overcomeBiases(detectedBiases, activatedNodes);
        
        // Stage 5: 통합적 해결
        solution.finalSolution = this.integrateSolution(activatedNodes);
        
        return solution;
    }

    /**
     * 🔍 문제 분석
     */
    analyzeProblem(problem) {
        return {
            stage: 'analysis',
            problemType: this.categorizeProblem(problem),
            requiredThinking: this.identifyRequiredThinking(problem),
            potentialPitfalls: this.identifyPitfalls(problem),
            timestamp: Date.now()
        };
    }

    /**
     * 🚨 편향 감지
     */
    detectPotentialBiases(problem) {
        const biases = [];
        
        // 문제 유형에 따라 발생 가능한 편향 예측
        if (problem.type === 'calculation') {
            biases.push(...this.biasMapping.calculation);
        }
        if (problem.requiresVisualization) {
            biases.push(...this.biasMapping.geometry);
        }
        if (problem.requiresStrategy) {
            biases.push(...this.biasMapping.strategy);
        }
        
        return biases.map(bias => ({
            name: bias,
            probability: Math.random() * 0.5 + 0.3,
            impact: this.assessBiasImpact(bias, problem)
        }));
    }

    /**
     * ⚡ 관련 노드 활성화
     */
    activateRelevantNodes(problem, biases) {
        const activeNodes = [];
        
        // 편향에 대응하는 노드 활성화
        biases.forEach(bias => {
            Object.entries(this.biasMapping).forEach(([nodeKey, biasesList]) => {
                if (biasesList.includes(bias.name)) {
                    if (!activeNodes.find(n => n.id === nodeKey)) {
                        activeNodes.push({
                            id: nodeKey,
                            node: this.nodes[nodeKey],
                            activationStrength: bias.probability,
                            purpose: `Counter ${bias.name}`
                        });
                    }
                }
            });
        });
        
        return activeNodes;
    }

    /**
     * 💪 편향 극복
     */
    overcomeBiases(biases, nodes) {
        const overcomeResults = [];
        
        biases.forEach(bias => {
            const relevantNodes = nodes.filter(node => 
                this.biasMapping[node.id]?.includes(bias.name)
            );
            
            if (relevantNodes.length > 0) {
                const successProbability = relevantNodes.reduce((sum, node) => 
                    sum + node.activationStrength, 0
                ) / relevantNodes.length;
                
                overcomeResults.push({
                    bias: bias.name,
                    success: successProbability > 0.6,
                    nodesUsed: relevantNodes.map(n => n.id),
                    newInsight: this.generateInsight(bias, relevantNodes)
                });
            }
        });
        
        return overcomeResults;
    }

    /**
     * 🎯 통합적 해결
     */
    integrateSolution(nodes) {
        // 모든 활성화된 노드의 힘을 합쳐 최종 해결책 도출
        const totalStrength = nodes.reduce((sum, node) => 
            sum + node.activationStrength, 0
        );
        
        return {
            solutionQuality: Math.min(totalStrength / nodes.length, 1),
            approach: this.determineBestApproach(nodes),
            confidence: totalStrength / (nodes.length || 1),
            learningOutcome: this.generateLearningOutcome(nodes)
        };
    }

    /**
     * 💡 통찰 생성
     */
    generateInsight(bias, nodes) {
        const insights = [
            `${bias.name}을 극복하기 위해 ${nodes[0].node.koreanName}을 활용했습니다.`,
            `${nodes.map(n => n.node.symbol).join(' + ')} 조합으로 새로운 관점을 얻었습니다.`,
            `편향을 인식하고 ${nodes[0].node.keyQuestions[0]}라고 자문했습니다.`
        ];
        
        return insights[Math.floor(Math.random() * insights.length)];
    }

    /**
     * 📚 학습 성과 생성
     */
    generateLearningOutcome(nodes) {
        return {
            strengthenedNodes: nodes.map(n => n.id),
            newConnections: this.findNodeConnections(nodes),
            masteryLevel: this.calculateMasteryLevel(nodes),
            recommendation: this.generateRecommendation(nodes)
        };
    }

    /**
     * 🔗 노드 연결 찾기
     */
    findNodeConnections(nodes) {
        const connections = [];
        
        for (let i = 0; i < nodes.length - 1; i++) {
            for (let j = i + 1; j < nodes.length; j++) {
                connections.push({
                    from: nodes[i].id,
                    to: nodes[j].id,
                    strength: (nodes[i].activationStrength + nodes[j].activationStrength) / 2
                });
            }
        }
        
        return connections;
    }

    /**
     * 🏆 숙달 수준 계산
     */
    calculateMasteryLevel(nodes) {
        const avgStrength = nodes.reduce((sum, n) => sum + n.activationStrength, 0) / nodes.length;
        
        if (avgStrength > 0.8) return 'master';
        if (avgStrength > 0.6) return 'advanced';
        if (avgStrength > 0.4) return 'intermediate';
        return 'beginner';
    }

    /**
     * 📝 추천 생성
     */
    generateRecommendation(nodes) {
        const weakestNode = nodes.reduce((min, n) => 
            n.activationStrength < min.activationStrength ? n : min
        );
        
        return {
            focus: weakestNode.node.koreanName,
            exercise: `${weakestNode.node.keyQuestions[0]}를 연습해보세요.`,
            nextChallenge: this.suggestNextChallenge(weakestNode)
        };
    }

    /**
     * 🎯 다음 도전 제안
     */
    suggestNextChallenge(weakNode) {
        const challenges = {
            reflection: "더 복잡한 문제에서 자신의 사고 과정을 기록해보세요",
            calculation: "암산 속도를 높이는 연습을 해보세요",
            geometry: "3차원 도형 문제에 도전해보세요",
            operation: "복합 연산 문제를 풀어보세요",
            strategy: "다양한 해결 방법을 찾는 연습을 해보세요",
            pattern: "수열과 규칙 찾기 문제를 풀어보세요",
            insight: "창의적인 문제 해결 방법을 탐구해보세요",
            prediction: "결과를 예측하고 검증하는 연습을 해보세요",
            mastery: "복합적인 실생활 문제를 해결해보세요"
        };
        
        return challenges[weakNode.id] || "새로운 유형의 문제에 도전해보세요";
    }

    // 유틸리티 메서드들
    categorizeProblem(problem) {
        // 문제 유형 분류 로직
        return 'mixed';
    }

    identifyRequiredThinking(problem) {
        // 필요한 사고 유형 식별
        return ['calculation', 'pattern', 'strategy'];
    }

    identifyPitfalls(problem) {
        // 잠재적 함정 식별
        return ['calculation_error', 'pattern_misidentification'];
    }

    assessBiasImpact(bias, problem) {
        // 편향의 영향력 평가
        return Math.random() * 0.5 + 0.5;
    }

    determineBestApproach(nodes) {
        // 최적 접근법 결정
        const strongest = nodes.reduce((max, n) => 
            n.activationStrength > max.activationStrength ? n : max
        );
        return strongest.node.koreanName;
    }
}

// 전역 인스턴스 생성
window.mathFramework = new MathematicalThinkingFramework();