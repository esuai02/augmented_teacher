# 🌟 Shining Stars - 완성 여정 설계서

## 📋 프로젝트 개요
수학적 사고와 인지관성 극복을 위한 인터랙티브 교육 플랫폼

## 🎯 핵심 설계 원칙
1. **60개 인지관성 카드의 체계적 활용**
2. **GPT API를 통한 지능형 피드백**
3. **게이미피케이션을 통한 몰입도 향상**
4. **개인화된 학습 경로 제공**

---

## 🗺️ 전체 여정 아키텍처

### 1단계: 진입 및 평가 (Entry & Assessment)
```
┌─────────────────────────────────────┐
│         🌟 시작 노드 (0)            │
│    "수학 여정의 시작"               │
└─────────────┬───────────────────────┘
              │
              ▼
┌─────────────────────────────────────┐
│     초기 인지관성 진단 시스템        │
│  - 5개 클러스터별 간단 테스트      │
│  - 개인 인지관성 프로파일 생성      │
└─────────────────────────────────────┘
```

### 2단계: 핵심 여정 (Core Journey)
```
     [1]──────[2]──────[3]
      │        │        │
      │   정팔각형 노드  │
      │   (8개 수학영역) │
      │        │        │
     [7]──────[0]──────[4]
      │        │        │
      │    [마스터리]    │
      │        │        │
     [6]──────[5]──────[4]
```

### 3단계: 인지관성 카드 수집 시스템
```
각 노드 완료 시:
├── 주요 인지관성 카드 1-3장 획득
├── 카드별 학습 세션 활성화
└── 인지관성 극복 미션 제공
```

---

## 🃏 60개 인지관성 카드 통합 시스템

### 카드 획득 메커니즘
```javascript
// 노드별 인지관성 카드 매핑
const nodeToCardMapping = {
    0: ['확증관성', '선택적주의'],           // 시작 - 인식 관성
    1: ['앵커링관성', '가용성휴리스틱'],     // 계산 - 판단 관성
    2: ['프레이밍효과', '대표성휴리스틱'],   // 도형 - 인식 관성
    3: ['과신관성', '계획오류'],             // 연산 - 판단 관성
    4: ['자기과소평가', '완벽주의'],         // 전략 - 학습 관성
    5: ['패턴인식관성', '과일반화'],         // 패턴 - 감정 관성
    6: ['확실성효과', '통제착각'],           // 깨달음 - 판단 관성
    7: ['재앙화사고', '흑백사고'],           // 예측 - 감정 관성
    8: ['더닝크루거효과', '관성맹점']        // 마스터리 - 사회적 관성
};
```

### 카드 활용 단계
1. **발견 (Discovery)**
   - 답변 분석을 통한 인지관성 감지
   - 실시간 인지관성 경고 시스템

2. **학습 (Learning)**
   - 6단계 인지관성 교육 프로그램
   - 인터랙티브 시나리오 제공

3. **극복 (Overcoming)**
   - 인지관성별 맞춤 미션
   - 실생활 적용 과제

4. **마스터 (Mastery)**
   - 인지관성 극복 인증
   - 메타인지 능력 향상

---

## 🤖 GPT API 활용 전략

### 1. 추천 대화 시스템
```javascript
// GPT 프롬프트 템플릿
const conversationPrompts = {
    reflection: `
        학생의 답변: {answer}
        감지된 인지관성: {detectedInertias}
        
        다음 관점에서 생각을 자극하는 질문 3개를 제시하세요:
        1. 다른 관점에서 보기
        2. 구체적 예시 요청
        3. 메타인지적 성찰
    `,
    
    socratic: `
        현재 주제: {topic}
        학생 수준: {level}
        
        소크라테스식 대화법으로 사고를 확장시킬 
        단계적 질문을 생성하세요.
    `
};
```

### 2. 사고 자극 피드백
```javascript
const feedbackTypes = {
    encouraging: "강점을 인정하고 발전 방향 제시",
    challenging: "새로운 관점과 도전적 질문 제공",
    connecting: "다른 개념과의 연결점 제시",
    metacognitive: "자신의 사고 과정을 돌아보게 하는 피드백"
};
```

### 3. 인지관성 극복 가이드
```javascript
const inertiaOvercomeGuide = {
    awareness: "인지관성 인식을 위한 구체적 예시",
    practice: "인지관성 극복 연습 시나리오",
    reflection: "극복 과정 성찰 질문",
    application: "실생활 적용 제안"
};
```

---

## 🎮 게이미피케이션 요소

### 진행 시스템
```
┌──────────────────────────────────┐
│        진행도 트래킹             │
├──────────────────────────────────┤
│ ▣ 노드 완료: 9개 중 X개         │
│ ▣ 카드 수집: 60개 중 Y개        │
│ ▣ 인지관성 극복: Z개 마스터      │
│ ▣ 연속 학습: N일 스트릭         │
└──────────────────────────────────┘
```

### 보상 체계
1. **즉각적 보상**
   - 노드 완료 시 별 파티클 효과
   - 카드 획득 애니메이션
   - 진행도 시각적 피드백

2. **단계별 보상**
   - 클러스터 완료 배지
   - 특별 카드 언락
   - 커스텀 아바타 요소

3. **최종 보상**
   - 마스터리 인증서
   - 전체 여정 요약 리포트
   - 성장 그래프 제공

---

## 💾 데이터 구조 설계

### 사용자 진행 상태
```javascript
const userProgress = {
    userId: "user_id",
    journeyState: {
        completedNodes: [0, 1, 2],
        unlockedNodes: [3, 4],
        currentNode: 3,
        startTime: "2024-01-01T00:00:00Z"
    },
    
    inertiaCards: {
        collected: ["확증관성", "앵커링관성"],
        mastered: ["확증관성"],
        inProgress: {
            "앵커링관성": {
                stage: 3,
                attempts: 2,
                lastAttempt: "2024-01-02T00:00:00Z"
            }
        }
    },
    
    responses: {
        nodeId: {
            answer: "학생의 답변",
            detectedInertias: ["확증관성"],
            gptFeedback: "AI 피드백",
            timestamp: "2024-01-01T00:00:00Z"
        }
    },
    
    achievements: {
        badges: ["첫걸음", "탐험가"],
        streaks: {
            current: 5,
            best: 10
        },
        statistics: {
            totalTime: 3600,
            averageResponseLength: 150,
            inertiaDetectionAccuracy: 0.75
        }
    }
};
```

### 데이터베이스 스키마
```sql
-- 사용자 진행 상황
CREATE TABLE user_journey_progress (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    node_id INT NOT NULL,
    completed_at TIMESTAMP,
    response_text TEXT,
    detected_inertias JSON,
    gpt_feedback TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 인지관성 카드 진행
CREATE TABLE user_inertia_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    inertia_name VARCHAR(100),
    status ENUM('collected', 'in_progress', 'mastered'),
    current_stage INT DEFAULT 0,
    collected_at TIMESTAMP,
    mastered_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- 성취 기록
CREATE TABLE user_achievements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    achievement_type VARCHAR(50),
    achievement_name VARCHAR(100),
    earned_at TIMESTAMP,
    metadata JSON,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

---

## 🔄 실시간 상호작용 플로우

### 노드 클릭 → 완료까지
```
1. 노드 선택
   ↓
2. 질문 제시 + 관련 인지관성 힌트
   ↓
3. 학생 답변 입력
   ↓
4. 실시간 인지관성 감지
   ↓
5. GPT 피드백 생성
   ├── 긍정적 강화
   ├── 사고 자극 질문
   └── 인지관성 극복 가이드
   ↓
6. 카드 획득 판정
   ↓
7. 다음 노드 언락
   ↓
8. 진행 상황 저장
```

---

## 🚀 구현 우선순위

### Phase 1: 핵심 기능 (1주)
- [x] 노드 시스템 기본 구현
- [x] 채팅 인터페이스
- [ ] GPT API 연동 강화
- [ ] 기본 인지관성 감지

### Phase 2: 카드 시스템 (1주)
- [ ] 카드 획득 로직
- [ ] 카드 도감 UI
- [ ] 학습 세션 구현
- [ ] 인지관성별 미션

### Phase 3: 게이미피케이션 (3일)
- [ ] 진행도 시각화
- [ ] 성취 시스템
- [ ] 보상 애니메이션
- [ ] 리더보드

### Phase 4: 데이터 & 분석 (3일)
- [ ] 데이터베이스 연동
- [ ] 진행 상황 저장
- [ ] 분석 대시보드
- [ ] 리포트 생성

### Phase 5: 최적화 & 완성 (2일)
- [ ] 성능 최적화
- [ ] UX 개선
- [ ] 버그 수정
- [ ] 배포 준비

---

## 📊 성공 지표

1. **참여도 지표**
   - 평균 세션 시간 > 15분
   - 완료율 > 60%
   - 재방문율 > 40%

2. **학습 효과 지표**
   - 인지관성 인식 정확도 향상
   - 메타인지 능력 개선
   - 수학적 사고력 증진

3. **만족도 지표**
   - 사용자 만족도 > 4.0/5.0
   - 추천 의향 > 70%
   - 긍정적 피드백 비율 > 80%

---

## 🔗 통합 포인트

### 기존 시스템과의 연계
- Moodle LMS 통합
- 학습 데이터 동기화
- 성적 관리 시스템 연동

### 확장 가능성
- 다른 과목으로 확장
- 연령대별 커스터마이징
- 교사용 분석 도구
- 부모 리포트 시스템

---

이 설계를 바탕으로 Shining Stars 프로젝트를 완성하면,
60개의 인지관성 카드를 체계적으로 활용하면서
GPT API를 통해 지능적인 피드백을 제공하는
혁신적인 교육 플랫폼이 될 것입니다.