/**
 * 🗺️ 편향 클러스터 데이터
 * 60개 편향을 5대 클러스터로 분류하고 상관관계 정의
 */

class BiasClusterData {
    constructor() {
        this.clusters = this.initializeClusters();
        this.relationships = this.initializeRelationships();
        this.conquestPath = this.initializeConquestPath();
        this.biasMetadata = this.initializeBiasMetadata();
    }

    /**
     * 🌟 5대 클러스터 초기화
     */
    initializeClusters() {
        return {
            perception: {
                id: 'perception',
                name: '🧠 인식 편향군',
                description: '정보 수집과 해석 과정에서 발생하는 편향들',
                color: '#667eea',
                coreNode: '확증편향',
                position: { x: 400, y: 150 }, // 12시 방향
                biases: [
                    '확증편향', '선택적주의', '후광효과', '프레이밍효과', 
                    '대표성휴리스틱', '기준율무시', '회상편향', '착각상관',
                    '생존자편향', '관찰자편향', '정보편향', '현상유지편향'
                ],
                conquestOrder: 1,
                prerequisite: null
            },
            
            judgment: {
                id: 'judgment',
                name: '💭 판단 편향군', 
                description: '의사결정과 추론 과정에서 발생하는 편향들',
                color: '#10b981',
                coreNode: '앵커링편향',
                position: { x: 650, y: 300 }, // 2시 방향
                biases: [
                    '앵커링편향', '가용성휴리스틱', '기준점편향', '조정부족',
                    '투사편향', '계획오류', '과신편향', '통제착각',
                    '확실성효과', '손실회피', '매몰비용오류', '기대효용이론위반',
                    '확률가중함수', '시간할인편향', '현재편향'
                ],
                conquestOrder: 2,
                prerequisite: 'perception'
            },
            
            learning: {
                id: 'learning',
                name: '📚 학습 편향군',
                description: '학습 과정과 성장에서 방해가 되는 편향들', 
                color: '#f59e0b',
                coreNode: '자기과소평가',
                position: { x: 650, y: 500 }, // 4시 방향
                biases: [
                    '자기과소평가', '회피행동', '학습된무력감', '고정마인드셋',
                    '완벽주의', '비교편향', '실패공포', '성공공포',
                    '귀인편향', '자기방해', '조급함편향', '단기성과편향',
                    '노력역설'
                ],
                conquestOrder: 2,
                prerequisite: 'perception'
            },
            
            emotional: {
                id: 'emotional',
                name: '😰 감정 편향군',
                description: '감정이 사고를 왜곡시키는 편향들',
                color: '#ef4444', 
                coreNode: '재앙화사고',
                position: { x: 400, y: 650 }, // 6시 방향
                biases: [
                    '재앙화사고', '흑백사고', '과일반화', '감정추론',
                    '부정적필터', '긍정적무시', '마음읽기', '점쟁이오류',
                    '개인화', '감정적결정'
                ],
                conquestOrder: 3,
                prerequisite: 'learning'
            },
            
            social: {
                id: 'social',
                name: '🤝 사회적 편향군',
                description: '타인과의 관계에서 발생하는 편향들',
                color: '#8b5cf6',
                coreNode: '던닝크루거효과',
                position: { x: 150, y: 500 }, // 8시 방향
                biases: [
                    '던닝크루거효과', '편향맹점', '사회적비교', '동조편향',
                    '권위편향', '집단사고', '내집단편향', '외집단동질성편향',
                    '근본귀인오류', '행위자관찰자편향'
                ],
                conquestOrder: 4,
                prerequisite: ['judgment', 'emotional']
            }
        };
    }

    /**
     * 🔗 편향 간 상관관계 초기화
     */
    initializeRelationships() {
        return [
            // 확증편향을 중심으로 한 관계들
            { from: '확증편향', to: '선택적주의', strength: 0.9, type: 'causal' },
            { from: '확증편향', to: '앵커링편향', strength: 0.7, type: 'reinforcing' },
            { from: '확증편향', to: '가용성휴리스틱', strength: 0.6, type: 'related' },
            
            // 자기과소평가를 중심으로 한 관계들
            { from: '자기과소평가', to: '회피행동', strength: 0.8, type: 'causal' },
            { from: '자기과소평가', to: '학습된무력감', strength: 0.8, type: 'causal' },
            { from: '자기과소평가', to: '재앙화사고', strength: 0.7, type: 'reinforcing' },
            { from: '자기과소평가', to: '완벽주의', strength: 0.6, type: 'compensatory' },
            
            // 재앙화사고를 중심으로 한 관계들
            { from: '재앙화사고', to: '흑백사고', strength: 0.8, type: 'causal' },
            { from: '재앙화사고', to: '과일반화', strength: 0.7, type: 'causal' },
            { from: '재앙화사고', to: '감정추론', strength: 0.7, type: 'reinforcing' },
            
            // 앵커링편향을 중심으로 한 관계들
            { from: '앵커링편향', to: '조정부족', strength: 0.9, type: 'causal' },
            { from: '앵커링편향', to: '기준점편향', strength: 0.8, type: 'related' },
            
            // 던닝크루거효과를 중심으로 한 관계들
            { from: '던닝크루거효과', to: '편향맹점', strength: 0.8, type: 'causal' },
            { from: '던닝크루거효과', to: '과신편향', strength: 0.7, type: 'related' },
            
            // 클러스터 간 관계들
            { from: '확증편향', to: '던닝크루거효과', strength: 0.6, type: 'meta' },
            { from: '자기과소평가', to: '사회적비교', strength: 0.7, type: 'social' },
            { from: '완벽주의', to: '재앙화사고', strength: 0.8, type: 'emotional' },
            { from: '과신편향', to: '계획오류', strength: 0.7, type: 'judgment' },
            
            // 학습 과정에서의 관계들
            { from: '회피행동', to: '고정마인드셋', strength: 0.7, type: 'reinforcing' },
            { from: '완벽주의', to: '실패공포', strength: 0.8, type: 'causal' },
            { from: '비교편향', to: '사회적비교', strength: 0.9, type: 'identical' },
            
            // 감정과 판단의 관계들
            { from: '감정추론', to: '가용성휴리스틱', strength: 0.6, type: 'cognitive' },
            { from: '부정적필터', to: '선택적주의', strength: 0.8, type: 'perceptual' },
            
            // 사회적 편향들의 관계
            { from: '동조편향', to: '집단사고', strength: 0.8, type: 'social' },
            { from: '권위편향', to: '동조편향', strength: 0.7, type: 'social' },
            { from: '내집단편향', to: '외집단동질성편향', strength: 0.9, type: 'complementary' }
        ];
    }

    /**
     * 🛣️ 정복 경로 초기화
     */
    initializeConquestPath() {
        return {
            stages: [
                {
                    stage: 1,
                    name: '🌟 Foundation Stage - 핵심 편향 정복',
                    description: '각 클러스터의 핵심 편향들을 먼저 정복하여 기반을 다집니다',
                    biases: ['확증편향', '자기과소평가', '재앙화사고'],
                    requiredCount: 3,
                    unlockNext: true
                },
                {
                    stage: 2,
                    name: '🚀 Expansion Stage - 연관 편향 확장',
                    description: '핵심 편향과 직접 연결된 편향들을 정복합니다',
                    biases: ['앵커링편향', '선택적주의', '회피행동', '흑백사고', '가용성휴리스틱'],
                    requiredCount: 5,
                    prerequisite: 1
                },
                {
                    stage: 3,
                    name: '⚡ Mastery Stage - 고차원 편향 정복',
                    description: '복잡하고 고차원적인 편향들을 정복합니다',
                    biases: ['던닝크루거효과', '편향맹점', '완벽주의', '과신편향', '집단사고'],
                    requiredCount: 8,
                    prerequisite: 2
                },
                {
                    stage: 4,
                    name: '🎯 Integration Stage - 통합 마스터',
                    description: '모든 편향을 통합적으로 이해하고 정복합니다',
                    biases: '모든 나머지 편향들',
                    requiredCount: 15,
                    prerequisite: 3
                }
            ],
            
            recommendations: {
                beginners: ['확증편향', '자기과소평가', '재앙화사고'],
                intermediate: ['앵커링편향', '던닝크루거효과', '완벽주의'],
                advanced: ['편향맹점', '메타인지편향', '시스템적사고결함'],
                
                byDifficulty: {
                    easy: ['확증편향', '선택적주의', '후광효과'],
                    medium: ['앵커링편향', '가용성휴리스틱', '재앙화사고'],
                    hard: ['던닝크루거효과', '편향맹점', '메타인지편향']
                },
                
                byImpact: {
                    high: ['확증편향', '자기과소평가', '재앙화사고', '던닝크루거효과'],
                    medium: ['앵커링편향', '완벽주의', '과신편향'],
                    low: ['후광효과', '프레이밍효과', '기준율무시']
                }
            }
        };
    }

    /**
     * 📋 편향 메타데이터 초기화
     */
    initializeBiasMetadata() {
        return {
            // 인식 편향군
            확증편향: {
                id: 'confirmation_bias',
                difficulty: 'medium',
                impact: 'high',
                frequency: 'very_high',
                category: 'perception',
                tags: ['기본', '핵심', '인식'],
                prerequisites: [],
                unlocks: ['선택적주의', '앵커링편향'],
                timeToMaster: '2-3주',
                realWorldExamples: ['뉴스 선택', '친구 의견', '수학 문제 접근법']
            },
            
            자기과소평가: {
                id: 'underconfidence_bias',
                difficulty: 'medium',
                impact: 'high', 
                frequency: 'high',
                category: 'learning',
                tags: ['학습', '핵심', '자신감'],
                prerequisites: [],
                unlocks: ['회피행동', '학습된무력감'],
                timeToMaster: '3-4주',
                realWorldExamples: ['문제 포기', '발표 회피', '도전 기피']
            },
            
            재앙화사고: {
                id: 'catastrophizing',
                difficulty: 'medium',
                impact: 'high',
                frequency: 'high', 
                category: 'emotional',
                tags: ['감정', '핵심', '스트레스'],
                prerequisites: [],
                unlocks: ['흑백사고', '과일반화'],
                timeToMaster: '2-3주',
                realWorldExamples: ['시험 실수', '친구 갈등', '성적 하락']
            },
            
            앵커링편향: {
                id: 'anchoring_bias',
                difficulty: 'medium',
                impact: 'medium',
                frequency: 'high',
                category: 'judgment',
                tags: ['판단', '의사결정', '첫인상'],
                prerequisites: ['확증편향'],
                unlocks: ['조정부족', '기준점편향'],
                timeToMaster: '2주',
                realWorldExamples: ['첫 번째 답', '예상 점수', '친구 평가']
            },
            
            던닝크루거효과: {
                id: 'dunning_kruger',
                difficulty: 'hard',
                impact: 'high',
                frequency: 'medium',
                category: 'social',
                tags: ['메타인지', '자기인식', '사회적'],
                prerequisites: ['확증편향', '과신편향'],
                unlocks: ['편향맹점', '메타인지부족'],
                timeToMaster: '4-6주',
                realWorldExamples: ['새 과목 자신감', '게임 실력 착각', '타인 평가']
            }
            
            // TODO: 나머지 55개 편향의 메타데이터 추가
        };
    }

    /**
     * 🔍 편향 검색 및 필터링 메소드들
     */
    getBiasesByCluster(clusterId) {
        return this.clusters[clusterId]?.biases || [];
    }

    getRelatedBiases(biasName) {
        const related = [];
        
        this.relationships.forEach(rel => {
            if (rel.from === biasName) {
                related.push({
                    bias: rel.to,
                    relationship: rel.type,
                    strength: rel.strength,
                    direction: 'outgoing'
                });
            } else if (rel.to === biasName) {
                related.push({
                    bias: rel.from,
                    relationship: rel.type, 
                    strength: rel.strength,
                    direction: 'incoming'
                });
            }
        });
        
        return related.sort((a, b) => b.strength - a.strength);
    }

    getConquestRecommendations(userProgress = []) {
        const conquered = new Set(userProgress);
        const available = [];
        
        // 각 편향의 전제조건을 확인하여 정복 가능한 편향들 찾기
        Object.entries(this.biasMetadata).forEach(([biasName, metadata]) => {
            if (conquered.has(biasName)) return; // 이미 정복함
            
            const prerequisites = metadata.prerequisites || [];
            const canConquer = prerequisites.every(prereq => conquered.has(prereq));
            
            if (canConquer) {
                available.push({
                    bias: biasName,
                    difficulty: metadata.difficulty,
                    impact: metadata.impact,
                    category: metadata.category,
                    unlocks: metadata.unlocks?.filter(unlock => !conquered.has(unlock)) || []
                });
            }
        });
        
        // 영향도와 난이도를 고려하여 추천 순서 결정
        return available.sort((a, b) => {
            const impactScore = { high: 3, medium: 2, low: 1 };
            const difficultyScore = { easy: 1, medium: 2, hard: 3 };
            
            const scoreA = impactScore[a.impact] - difficultyScore[a.difficulty] * 0.5;
            const scoreB = impactScore[b.impact] - difficultyScore[b.difficulty] * 0.5;
            
            return scoreB - scoreA;
        });
    }

    getClusterProgress(clusterId, userProgress = []) {
        const clusterBiases = this.getBiasesByCluster(clusterId);
        const conquered = userProgress.filter(bias => clusterBiases.includes(bias));
        
        return {
            total: clusterBiases.length,
            conquered: conquered.length,
            percentage: Math.round((conquered.length / clusterBiases.length) * 100),
            remaining: clusterBiases.filter(bias => !userProgress.includes(bias))
        };
    }

    getOverallProgress(userProgress = []) {
        const totalBiases = Object.values(this.clusters)
            .reduce((total, cluster) => total + cluster.biases.length, 0);
        
        return {
            total: totalBiases,
            conquered: userProgress.length,
            percentage: Math.round((userProgress.length / totalBiases) * 100),
            byCluster: Object.keys(this.clusters).reduce((acc, clusterId) => {
                acc[clusterId] = this.getClusterProgress(clusterId, userProgress);
                return acc;
            }, {})
        };
    }

    /**
     * 🎯 다음 목표 편향 추천
     */
    getNextTargets(userProgress = [], count = 3) {
        const recommendations = this.getConquestRecommendations(userProgress);
        return recommendations.slice(0, count).map(rec => ({
            ...rec,
            reason: this.getRecommendationReason(rec, userProgress)
        }));
    }

    getRecommendationReason(recommendation, userProgress) {
        const { bias, impact, difficulty, unlocks } = recommendation;
        const unlockCount = unlocks.length;
        
        if (impact === 'high' && difficulty === 'medium') {
            return `높은 영향도를 가지면서 적당한 난이도입니다`;
        } else if (unlockCount > 2) {
            return `${unlockCount}개의 새로운 편향을 잠금해제할 수 있습니다`;
        } else if (difficulty === 'easy') {
            return `상대적으로 쉬워서 빠른 성취감을 얻을 수 있습니다`;
        } else {
            return `현재 진행 상황에 맞는 다음 단계입니다`;
        }
    }

    /**
     * 🔍 편향 검색
     */
    searchBiases(query) {
        const results = [];
        const lowerQuery = query.toLowerCase();
        
        Object.values(this.clusters).forEach(cluster => {
            cluster.biases.forEach(biasName => {
                if (biasName.toLowerCase().includes(lowerQuery)) {
                    const metadata = this.biasMetadata[biasName];
                    results.push({
                        bias: biasName,
                        cluster: cluster.id,
                        clusterName: cluster.name,
                        metadata: metadata || {}
                    });
                }
            });
        });
        
        return results;
    }
}

// 전역 인스턴스 생성
window.biasClusterData = new BiasClusterData();