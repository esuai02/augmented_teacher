/**
 * File: alt42/orchestration/agents/agent04_problem_activity/ui/activity_categories.js
 * Agent04: 활동 카테고리 데이터 및 선택 로직
 */

window.Agent04ActivityCategories = {
    // 7개 주요 활동 카테고리와 하위 항목
    categories: {
        'concept_understanding': {
            name: '개념이해',
            icon: '📚',
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
            subItems: [
                '25분 집중 학습',
                '5분 휴식',
                '긴 휴식 (15분)',
                '일일 목표 설정'
            ]
        }
    },

    // API 기본 경로
    apiBasePath: '/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/api',

    // 카테고리 정보 가져오기
    getCategory(categoryKey) {
        return this.categories[categoryKey] || null;
    },

    // 하위 항목 가져오기
    getSubItems(categoryKey) {
        return this.categories[categoryKey]?.subItems || [];
    },

    // 모든 카테고리 목록
    getAllCategories() {
        return Object.keys(this.categories).map(key => ({
            key: key,
            ...this.categories[key]
        }));
    },

    // 활동 선택 저장
    async saveSelection(categoryKey, subItem, userId) {
        try {
            const response = await fetch(`${this.apiBasePath}/save_activity.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    userid: userId || window.currentUserId,
                    main_category: categoryKey,
                    sub_activity: subItem
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('💾 활동 선택 저장 성공:', data);
            return data;
        } catch (error) {
            console.error('❌ 활동 선택 저장 실패:', error);
            throw error;
        }
    },

    // 활동 이력 조회
    async getHistory(userId, limit = 10) {
        try {
            const url = `${this.apiBasePath}/get_activity.php?userid=${userId || window.currentUserId}&limit=${limit}`;
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📋 활동 이력 조회 성공:', data);
            return data;
        } catch (error) {
            console.error('❌ 활동 이력 조회 실패:', error);
            throw error;
        }
    }
};

console.log('✅ Agent04 Activity Categories 모듈 로드 완료');
