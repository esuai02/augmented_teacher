/**
 * 🌌 개인화된 우주적 내러티브 엔진
 * 학생의 채팅 분석과 시스템 데이터를 연결하여 맞춤형 우주 서사 생성
 */

class PersonalizedNarrativeEngine {
    constructor() {
        this.userId = null;
        this.personalityProfile = null;
        this.biasProfile = null;
        this.emotionalJourney = [];
        this.narrativeHistory = [];
        this.cosmicArchetypes = this.initializeCosmicArchetypes();
        this.storyTemplates = this.initializeStoryTemplates();
        this.contextAnalyzer = new ContextAnalyzer();
        
        this.init();
    }

    /**
     * 🌟 우주적 원형 캐릭터 정의
     */
    initializeCosmicArchetypes() {
        return {
            // 주인공 유형
            hero_types: {
                reluctant_explorer: {
                    name: "망설이는 탐험가",
                    traits: ["자기의심", "신중함", "성장잠재력"],
                    cosmic_symbol: "🌱",
                    journey_arc: "두려움 → 용기 → 마스터리",
                    bias_tendency: ["자기과소평가", "회피행동", "완벽주의"]
                },
                curious_wanderer: {
                    name: "호기심 많은 방랑자",
                    traits: ["탐구심", "개방성", "산만함"],
                    cosmic_symbol: "🔭",
                    journey_arc: "산만함 → 집중 → 통찰",
                    bias_tendency: ["주의편향", "과신편향", "가용성휴리스틱"]
                },
                perfectionist_guardian: {
                    name: "완벽주의 수호자",
                    traits: ["정확성", "책임감", "불안"],
                    cosmic_symbol: "⭐",
                    journey_arc: "경직성 → 유연성 → 균형",
                    bias_tendency: ["완벽주의", "흑백사고", "재앙화사고"]
                },
                competitive_warrior: {
                    name: "경쟁적 전사",
                    traits: ["승부욕", "리더십", "비교의식"],
                    cosmic_symbol: "⚔️",
                    journey_arc: "경쟁 → 협력 → 조화",
                    bias_tendency: ["사회적비교", "과신편향", "확증편향"]
                }
            },

            // 멘토/가이드 유형
            mentor_types: {
                wise_sage: {
                    name: "지혜로운 현자",
                    personality: "차분하고 통찰력 있는",
                    speaking_style: "깊이 있는 질문과 은유적 표현",
                    cosmic_symbol: "🔮"
                },
                playful_trickster: {
                    name: "장난스러운 트릭스터",
                    personality: "유머러스하고 창의적인",
                    speaking_style: "재미있는 비유와 실험적 접근",
                    cosmic_symbol: "🎭"
                },
                nurturing_mother: {
                    name: "보호하는 어머니",
                    personality: "따뜻하고 격려하는",
                    speaking_style: "공감적 언어와 부드러운 안내",
                    cosmic_symbol: "🌙"
                }
            }
        };
    }

    /**
     * 📚 동적 스토리 템플릿 시스템
     */
    initializeStoryTemplates() {
        return {
            // 문제 시작 시나리오
            problem_opening: {
                reluctant_explorer: [
                    "새로운 수학 행성이 눈앞에 나타났어요. {name}의 마음속에선 '할 수 있을까?' 하는 작은 목소리가 들리네요. 🌱",
                    "미지의 수학 우주로 가는 관문이 열렸어요. {name}은 한 걸음 물러서며 깊은 숨을 쉬고 있어요. 괜찮아요, 천천히 가도 돼요. ✨",
                    "{name}의 우주선이 새로운 문제 별자리 앞에 멈춰 섰어요. 엔진 소리가 약간 떨리는 것 같지만, 그건 설렘일 수도 있어요. 🚀"
                ],
                curious_wanderer: [
                    "오! 흥미로운 수학 현상이 {name}의 망원경에 포착되었어요! 이 신비로운 패턴의 정체는 무엇일까요? 🔭",
                    "{name}의 탐험 센서가 반응하고 있어요. 저 멀리 반짝이는 것이 새로운 발견의 신호일까요? 🌟",
                    "우주의 수학적 신호가 {name}에게 메시지를 보내고 있어요. 호기심 안테나를 높이 세워보세요! 📡"
                ],
                perfectionist_guardian: [
                    "{name}이 새로운 수학 요새를 발견했어요. 완벽한 전략을 세우기 전까지는 섣불리 접근하면 안 되겠어요. ⭐",
                    "정밀한 {name}의 분석 시스템이 가동되기 시작했어요. 모든 변수를 검토한 후 최적의 경로를 찾아야겠네요. 🔍",
                    "{name}의 품질 관리 시스템이 알림을 보내고 있어요. '신중하게, 그러나 확실하게' - 이것이 {name}의 모토예요. ⚖️"
                ]
            },

            // 편향 감지 시 개입
            bias_intervention: {
                확증편향: {
                    detection: "{name}님, 확증편향의 중력장이 감지되었어요! 🕳️",
                    metaphor: "지금 {name}은 하나의 별만 보고 있어요. 하지만 우주에는 무수한 별자리가 있답니다.",
                    guidance: "다른 행성의 수학자들은 이 문제를 어떻게 풀까요? 새로운 관점의 망원경을 꺼내보세요! 🔭",
                    encouragement: "{name}의 탐험 정신이 새로운 길을 발견할 거예요."
                },
                재앙화사고: {
                    detection: "⚠️ 재앙화사고 소행성이 {name}의 우주선에 접근 중이에요!",
                    metaphor: "작은 운석을 행성 충돌로 보고 계시는군요. 하지만 실제로는 아름다운 유성우일 수도 있어요.",
                    guidance: "우주에서 실수는 새로운 별이 탄생하는 과정이에요. {name}의 실수도 성장의 별빛이 될 거예요. 🌟",
                    encouragement: "{name}은 이미 많은 우주 여행을 성공적으로 마쳤어요."
                },
                자기과소평가: {
                    detection: "🌑 자기과소평가 블랙홀이 {name}의 빛을 삼키려 해요!",
                    metaphor: "{name} 안의 별빛이 얼마나 밝은지 모르고 계시는군요. 이미 여기까지 온 것만으로도 대단한 여행자예요.",
                    guidance: "지금까지 해결한 문제들을 떠올려보세요. 그것들이 {name}만의 별자리를 만들고 있어요. ⭐",
                    encouragement: "우주는 {name}의 가능성을 믿고 있어요."
                }
            },

            // 성공 시 축하
            success_celebration: {
                small_victory: [
                    "🎉 {name}이 새로운 별을 점화시켰어요! 이 작은 빛이 더 큰 은하계의 시작이 될 거예요.",
                    "✨ 훌륭해요! {name}의 문제해결 엔진이 완벽하게 작동했네요. 우주가 박수를 보내고 있어요!",
                    "🌟 {name}만의 수학 별자리가 하나 더 완성되었어요. 이 패턴이 다음 여행의 나침반이 될 거예요."
                ],
                major_breakthrough: [
                    "🚀 놀라워요! {name}이 수학 우주의 새로운 차원을 발견했어요! 이건 진정한 탐험가의 업적이에요.",
                    "🌌 {name}의 통찰력이 새로운 은하계를 열었어요! 이제 더 많은 가능성들이 보이기 시작할 거예요.",
                    "⭐ 경이로운 순간이에요! {name}이 수학의 우주 법칙을 새롭게 이해했어요. 진정한 우주 마스터의 모습이네요!"
                ]
            },

            // 어려움 극복 스토리
            struggle_support: {
                encouragement: [
                    "{name}이 지금 우주 폭풍을 지나고 있어요. 하지만 모든 위대한 탐험가들이 거쳐온 길이에요. 🌪️",
                    "어려운 수학 소행성대를 항해 중이시군요. {name}의 우주선은 이보다 더 어려운 길도 헤쳐나갈 수 있어요. 💫",
                    "지금의 어둠은 새로운 별이 탄생하기 전의 고요함이에요. {name}의 다음 발견을 우주가 기다리고 있어요. 🌑➡️🌟"
                ]
            }
        };
    }

    /**
     * 🔍 사용자 분석 및 프로필 생성
     */
    async analyzeUserProfile(userId, chatHistory, systemData) {
        this.userId = userId;
        
        // 채팅 패턴 분석
        const chatAnalysis = await this.analyzeChatPatterns(chatHistory);
        
        // 시스템 데이터 분석
        const behaviorAnalysis = this.analyzeSystemBehavior(systemData);
        
        // 편향 프로필 생성
        this.biasProfile = this.generateBiasProfile(chatAnalysis, behaviorAnalysis);
        
        // 성격 유형 결정
        this.personalityProfile = this.determinePersonalityType(chatAnalysis, behaviorAnalysis);
        
        // 우주적 원형 할당
        const cosmicArchetype = this.assignCosmicArchetype(this.personalityProfile, this.biasProfile);
        
        return {
            personalityProfile: this.personalityProfile,
            biasProfile: this.biasProfile,
            cosmicArchetype: cosmicArchetype,
            recommendedMentor: this.selectOptimalMentor(cosmicArchetype)
        };
    }

    /**
     * 💬 채팅 패턴 분석
     */
    async analyzeChatPatterns(chatHistory) {
        const analysis = {
            emotionalTone: [],
            keywordFrequency: {},
            responseLength: [],
            timePatterns: [],
            biasIndicators: []
        };

        chatHistory.forEach(message => {
            // 감정 톤 분석
            const emotion = this.detectEmotionInText(message.text);
            analysis.emotionalTone.push({
                timestamp: message.timestamp,
                emotion: emotion,
                intensity: this.calculateEmotionalIntensity(message.text)
            });

            // 키워드 빈도 분석
            this.updateKeywordFrequency(message.text, analysis.keywordFrequency);

            // 응답 길이 패턴
            analysis.responseLength.push(message.text.length);

            // 편향 지표 감지
            const biasIndicators = this.detectBiasIndicators(message.text);
            analysis.biasIndicators.push(...biasIndicators);
        });

        return analysis;
    }

    /**
     * 🎯 시스템 행동 분석
     */
    analyzeSystemBehavior(systemData) {
        return {
            problemSolvingSpeed: this.calculateAverageSolvingTime(systemData.sessions),
            retryPatterns: this.analyzeRetryBehavior(systemData.sessions),
            helpSeekingBehavior: this.analyzeHelpSeeking(systemData.sessions),
            persistenceLevel: this.calculatePersistence(systemData.sessions),
            preferredDifficulty: this.identifyDifficultyPreference(systemData.sessions)
        };
    }

    /**
     * 🧬 편향 프로필 생성
     */
    generateBiasProfile(chatAnalysis, behaviorAnalysis) {
        const biasScores = {};
        
        // 채팅에서 감지된 편향들
        chatAnalysis.biasIndicators.forEach(indicator => {
            if (!biasScores[indicator.bias]) {
                biasScores[indicator.bias] = { score: 0, evidence: [] };
            }
            biasScores[indicator.bias].score += indicator.confidence;
            biasScores[indicator.bias].evidence.push(indicator.evidence);
        });

        // 행동 패턴에서 추론되는 편향들
        if (behaviorAnalysis.retryPatterns.giveUpQuickly) {
            biasScores['회피행동'] = { score: 0.8, evidence: ['빠른 포기 패턴'] };
        }
        
        if (behaviorAnalysis.persistenceLevel < 0.3) {
            biasScores['자기과소평가'] = { score: 0.6, evidence: ['낮은 지속성'] };
        }

        // 상위 3개 편향 선택
        const dominantBiases = Object.entries(biasScores)
            .sort(([,a], [,b]) => b.score - a.score)
            .slice(0, 3)
            .map(([bias, data]) => ({ bias, ...data }));

        return {
            dominantBiases: dominantBiases,
            riskLevel: this.calculateOverallRiskLevel(dominantBiases),
            interventionPriority: this.determineInterventionPriority(dominantBiases)
        };
    }

    /**
     * 👤 성격 유형 결정
     */
    determinePersonalityType(chatAnalysis, behaviorAnalysis) {
        const traits = {
            openness: 0,
            conscientiousness: 0,
            confidence: 0,
            curiosity: 0,
            anxiety: 0
        };

        // 채팅 패턴에서 성격 추론
        const positiveEmotions = chatAnalysis.emotionalTone.filter(e => 
            ['curious', 'confident', 'excited'].includes(e.emotion)
        ).length;
        
        const negativeEmotions = chatAnalysis.emotionalTone.filter(e => 
            ['anxious', 'frustrated', 'worried'].includes(e.emotion)
        ).length;

        traits.confidence = positiveEmotions / (positiveEmotions + negativeEmotions + 1);
        traits.anxiety = negativeEmotions / (positiveEmotions + negativeEmotions + 1);

        // 행동 패턴에서 성격 추론
        traits.conscientiousness = behaviorAnalysis.persistenceLevel;
        traits.openness = behaviorAnalysis.preferredDifficulty === 'challenging' ? 0.8 : 0.4;
        traits.curiosity = behaviorAnalysis.helpSeekingBehavior.explorationRate || 0.5;

        return {
            traits: traits,
            primaryType: this.classifyPersonalityType(traits),
            learningStyle: this.inferLearningStyle(chatAnalysis, behaviorAnalysis)
        };
    }

    /**
     * 🌟 우주적 원형 할당
     */
    assignCosmicArchetype(personalityProfile, biasProfile) {
        const { traits } = personalityProfile;
        const dominantBiases = biasProfile.dominantBiases.map(b => b.bias);

        // 성격과 편향 패턴을 기반으로 원형 결정
        if (traits.anxiety > 0.6 && dominantBiases.includes('자기과소평가')) {
            return this.cosmicArchetypes.hero_types.reluctant_explorer;
        }
        
        if (traits.curiosity > 0.7 && traits.openness > 0.6) {
            return this.cosmicArchetypes.hero_types.curious_wanderer;
        }
        
        if (traits.conscientiousness > 0.7 && dominantBiases.includes('완벽주의')) {
            return this.cosmicArchetypes.hero_types.perfectionist_guardian;
        }
        
        if (traits.confidence > 0.6 && dominantBiases.includes('사회적비교')) {
            return this.cosmicArchetypes.hero_types.competitive_warrior;
        }

        // 기본값
        return this.cosmicArchetypes.hero_types.reluctant_explorer;
    }

    /**
     * 🎭 최적 멘토 선택
     */
    selectOptimalMentor(cosmicArchetype) {
        const mentorMapping = {
            reluctant_explorer: this.cosmicArchetypes.mentor_types.nurturing_mother,
            curious_wanderer: this.cosmicArchetypes.mentor_types.playful_trickster,
            perfectionist_guardian: this.cosmicArchetypes.mentor_types.wise_sage,
            competitive_warrior: this.cosmicArchetypes.mentor_types.wise_sage
        };

        return mentorMapping[cosmicArchetype.name] || this.cosmicArchetypes.mentor_types.nurturing_mother;
    }

    /**
     * 📝 개인화된 내러티브 생성
     */
    generatePersonalizedNarrative(context, userInput = "") {
        const { 
            phase, // 'opening', 'bias_intervention', 'success', 'struggle'
            biasDetected = null,
            achievementLevel = 'small_victory',
            emotionalState = 'neutral'
        } = context;

        let narrative = "";
        const userName = this.getUserName();
        const archetype = this.personalityProfile?.primaryType || 'reluctant_explorer';

        switch (phase) {
            case 'opening':
                narrative = this.generateOpeningNarrative(archetype, userName);
                break;
            
            case 'bias_intervention':
                narrative = this.generateBiasInterventionNarrative(biasDetected, userName);
                break;
            
            case 'success':
                narrative = this.generateSuccessNarrative(achievementLevel, userName);
                break;
            
            case 'struggle':
                narrative = this.generateStruggleNarrative(emotionalState, userName);
                break;
            
            case 'progress_reflection':
                narrative = this.generateProgressReflection(userName, userInput);
                break;
        }

        // 개인화 요소 추가
        narrative = this.enhanceWithPersonalization(narrative, userInput);
        
        // 내러티브 히스토리에 추가
        this.narrativeHistory.push({
            timestamp: Date.now(),
            phase: phase,
            narrative: narrative,
            context: context
        });

        return narrative;
    }

    /**
     * 🌅 오프닝 내러티브 생성
     */
    generateOpeningNarrative(archetype, userName) {
        const templates = this.storyTemplates.problem_opening[archetype] || 
                         this.storyTemplates.problem_opening.reluctant_explorer;
        
        const selectedTemplate = templates[Math.floor(Math.random() * templates.length)];
        return selectedTemplate.replace(/{name}/g, userName);
    }

    /**
     * ⚠️ 편향 개입 내러티브 생성
     */
    generateBiasInterventionNarrative(biasType, userName) {
        const interventionData = this.storyTemplates.bias_intervention[biasType];
        if (!interventionData) return this.generateGenericIntervention(userName);

        return {
            detection: interventionData.detection.replace(/{name}/g, userName),
            metaphor: interventionData.metaphor.replace(/{name}/g, userName),
            guidance: interventionData.guidance.replace(/{name}/g, userName),
            encouragement: interventionData.encouragement.replace(/{name}/g, userName)
        };
    }

    /**
     * 🎉 성공 내러티브 생성
     */
    generateSuccessNarrative(achievementLevel, userName) {
        const templates = this.storyTemplates.success_celebration[achievementLevel];
        const selectedTemplate = templates[Math.floor(Math.random() * templates.length)];
        return selectedTemplate.replace(/{name}/g, userName);
    }

    /**
     * 💪 고군분투 지원 내러티브 생성
     */
    generateStruggleNarrative(emotionalState, userName) {
        const templates = this.storyTemplates.struggle_support.encouragement;
        const selectedTemplate = templates[Math.floor(Math.random() * templates.length)];
        return selectedTemplate.replace(/{name}/g, userName);
    }

    /**
     * 🔮 진행상황 성찰 내러티브
     */
    generateProgressReflection(userName, userInput) {
        // 사용자 입력 분석
        const sentiment = this.analyzeSentiment(userInput);
        const growth = this.detectGrowthIndicators(userInput);
        const challenges = this.detectChallenges(userInput);

        let reflection = `${userName}의 우주 여행 일지를 살펴보니... ✨\n\n`;

        if (growth.length > 0) {
            reflection += `🌟 새롭게 빛나는 별들: ${growth.join(', ')}\n`;
        }

        if (challenges.length > 0) {
            reflection += `🌪️ 현재 항해 중인 우주 폭풍: ${challenges.join(', ')}\n`;
        }

        reflection += `\n${this.generateFutureGuidance(sentiment, growth, challenges)}`;

        return reflection;
    }

    /**
     * 🎨 개인화 강화
     */
    enhanceWithPersonalization(narrative, userInput) {
        // 사용자의 최근 관심사나 언급한 내용 반영
        const recentTopics = this.extractRecentTopics(userInput);
        const personalityAdjustment = this.getPersonalityAdjustment();

        // 말투나 표현 방식을 성격에 맞게 조정
        if (personalityAdjustment.formal) {
            narrative = narrative.replace(/야/g, '요').replace(/해/g, '해요');
        }

        return narrative;
    }

    /**
     * 🔧 유틸리티 메소드들
     */
    getUserName() {
        // TODO: 실제 사용자 이름 가져오기
        return "탐험가";
    }

    detectEmotionInText(text) {
        // 간단한 감정 감지 (실제로는 더 정교한 NLP 필요)
        const emotionKeywords = {
            anxious: ['불안', '걱정', '무서', '두려'],
            confident: ['할 수 있', '자신', '확신', '괜찮'],
            curious: ['궁금', '왜', '어떻게', '신기'],
            frustrated: ['짜증', '답답', '어려', '힘들']
        };

        for (const [emotion, keywords] of Object.entries(emotionKeywords)) {
            if (keywords.some(keyword => text.includes(keyword))) {
                return emotion;
            }
        }

        return 'neutral';
    }

    calculateEmotionalIntensity(text) {
        // 감정 강도 계산 (느낌표, 대문자 등 고려)
        let intensity = 0.5;
        
        if (text.includes('!')) intensity += 0.2;
        if (text.includes('?')) intensity += 0.1;
        if (/[ㅋㅎ]{2,}/.test(text)) intensity += 0.3; // 웃음 표현
        if (text.length > 100) intensity += 0.1; // 긴 텍스트는 감정이 강할 가능성

        return Math.min(intensity, 1.0);
    }

    updateKeywordFrequency(text, frequency) {
        const words = text.split(/\s+/);
        words.forEach(word => {
            frequency[word] = (frequency[word] || 0) + 1;
        });
    }

    detectBiasIndicators(text) {
        const indicators = [];
        
        // 확증편향 지표
        if (/역시|당연히|또|늘 그래/.test(text)) {
            indicators.push({
                bias: '확증편향',
                confidence: 0.7,
                evidence: '기존 믿음 강화 표현 사용'
            });
        }

        // 재앙화사고 지표
        if (/끝났다|망했다|최악|절대/.test(text)) {
            indicators.push({
                bias: '재앙화사고',
                confidence: 0.8,
                evidence: '극단적 부정 표현 사용'
            });
        }

        return indicators;
    }

    analyzeSentiment(text) {
        // 간단한 감정 분석
        const positiveWords = ['좋', '재미', '성공', '해냈', '이해'];
        const negativeWords = ['어려', '힘들', '못하', '실패', '포기'];

        const positiveCount = positiveWords.filter(word => text.includes(word)).length;
        const negativeCount = negativeWords.filter(word => text.includes(word)).length;

        if (positiveCount > negativeCount) return 'positive';
        if (negativeCount > positiveCount) return 'negative';
        return 'neutral';
    }

    detectGrowthIndicators(text) {
        const growthKeywords = ['이해했', '깨달았', '발견했', '배웠', '성장'];
        return growthKeywords.filter(keyword => text.includes(keyword));
    }

    detectChallenges(text) {
        const challengeKeywords = ['어려운', '복잡한', '모르겠', '헷갈리'];
        return challengeKeywords.filter(keyword => text.includes(keyword));
    }

    generateFutureGuidance(sentiment, growth, challenges) {
        if (sentiment === 'positive' && growth.length > 0) {
            return "🚀 멋진 성장세네요! 이 기세로 다음 우주를 탐험할 준비가 되었어요.";
        }
        
        if (challenges.length > 0) {
            return "⭐ 지금의 도전들이 더 밝은 별로 변화할 거예요. 우주는 당신의 성장을 기다리고 있어요.";
        }

        return "🌌 새로운 발견을 위한 여정이 계속됩니다. 우주의 신비를 함께 풀어가요!";
    }

    /**
     * 🔄 DB 연동 요청 메소드들
     */
    async requestSystemData(userId) {
        // TODO: 실제 API 호출
        console.log('🔄 시스템 데이터 요청:', userId);
        return {
            sessions: [],
            preferences: {},
            progress: {}
        };
    }

    async requestChatHistory(userId) {
        // TODO: 실제 채팅 히스토리 API 호출
        console.log('🔄 채팅 히스토리 요청:', userId);
        return [];
    }

    async saveNarrativeProfile(userId, profile) {
        // TODO: DB 저장
        console.log('💾 내러티브 프로필 저장:', userId, profile);
    }
}

/**
 * 🔍 컨텍스트 분석기
 */
class ContextAnalyzer {
    analyzeContext(userInput, systemState) {
        return {
            currentEmotion: this.detectCurrentEmotion(userInput),
            problemDifficulty: systemState.currentProblem?.difficulty || 'medium',
            timeSpent: systemState.timeInCurrentSession || 0,
            recentPerformance: systemState.recentScores || []
        };
    }

    detectCurrentEmotion(text) {
        // 실시간 감정 상태 분석
        return 'neutral'; // 단순화
    }
}

// 전역 인스턴스 생성
window.personalizedNarrativeEngine = new PersonalizedNarrativeEngine();