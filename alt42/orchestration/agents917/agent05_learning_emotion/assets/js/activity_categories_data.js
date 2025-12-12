/**
 * Agent05 학습감정 분석 - 활동 카테고리 데이터
 * 7개 활동 카테고리와 각각의 하위 구조 분류
 *
 * File: alt42/orchestration/agents/agent05_learning_emotion/assets/js/activity_categories_data.js
 */

window.Agent05ActivityCategories = {
    categories: {
        'concept_understanding': {
            name: '개념이해',
            icon: '📚',
            emotionType: 'cognitive',
            subItems: [
                '핵심 개념 정리',
                '공식 유도 과정',
                '개념 간 연결',
                '실생활 적용 예시'
            ]
        },
        'type_learning': {
            name: '유형학습',
            icon: '🎯',
            emotionType: 'mastery',
            subItems: [
                '기본 유형 문제',
                '응용 유형 문제',
                '심화 유형 문제',
                '신유형 문제'
            ]
        },
        'problem_solving': {
            name: '문제풀이',
            icon: '✏️',
            emotionType: 'performance',
            subItems: [
                '기출문제 풀이',
                '모의고사 풀이',
                '단원별 문제',
                '종합 문제'
            ]
        },
        'error_notes': {
            name: '오답노트',
            icon: '📝',
            emotionType: 'reflection',
            subItems: [
                '오답 원인 분석',
                '유사 문제 연습',
                '개념 재정리',
                '실수 방지 체크리스트'
            ]
        },
        'qa': {
            name: '질의응답',
            icon: '💬',
            emotionType: 'curiosity',
            subItems: [
                '개념 질문',
                '문제 풀이 질문',
                '학습 방법 상담',
                '진로 상담'
            ]
        },
        'review': {
            name: '복습활동',
            icon: '🔄',
            emotionType: 'consolidation',
            subItems: [
                '일일 복습',
                '주간 복습',
                '단원 총정리',
                '시험 대비 복습'
            ]
        },
        'pomodoro': {
            name: '포모도르',
            icon: '⏰',
            emotionType: 'regulation',
            subItems: [
                '25분 집중 학습',
                '5분 휴식',
                '긴 휴식 (15분)',
                '일일 목표 설정'
            ]
        }
    },

    /**
     * 카테고리 정보 가져오기
     */
    getCategory: function(categoryKey) {
        return this.categories[categoryKey] || null;
    },

    /**
     * 모든 카테고리 목록 가져오기
     */
    getAllCategories: function() {
        return Object.keys(this.categories).map(key => ({
            key: key,
            ...this.categories[key]
        }));
    }
};
