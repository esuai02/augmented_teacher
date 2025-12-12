# Agent 19 - Interaction Content Persona Definitions

## 개요 (Overview)
이 문서는 **콘텐츠 상호작용 상황**에서 학생의 인지적/행동적/정서적 페르소나를 정의합니다.
Agent01의 온보딩 페르소나와 달리, Agent19는 **학습 중 실시간 상호작용 패턴**을 기반으로 페르소나를 식별합니다.

**Last-Updated**: 2025-12-02
**Version**: 1.0

---

## 페르소나 분류 체계 (Classification System)

### 3차원 페르소나 모델
- **C (Cognitive)**: 인지적 페르소나 - 문제 해결 방식, 학습 패턴, 이해 스타일
- **B (Behavioral)**: 행동적 페르소나 - 상호작용 패턴, 반응 양식, 참여 방식
- **E (Emotional)**: 정서적 페르소나 - 감정 상태, 동기 수준, 심리적 상태

### 상황 코드 (Situation Codes)
- **S1**: 학습 이탈 조짐 감지 후 재진입 유도
- **S2**: 현재 위치 지연 감지
- **S3**: 휴식 루틴 이상 탐지
- **S4**: 오답 패턴 반복
- **S5**: 정서적 침착도 저하
- **S6**: 목표 대비 활동 불균형
- **S7**: 시그너처 루틴 형성 시점

---

## C (Cognitive) - 인지적 페르소나

### C1: 인지 활성화형 (Cognitive Active)
- **ID**: C1
- **Alias**: "활발한 사고자"
- **인지적 특성**:
  - 정보 처리 속도: 빠름
  - 개념 연결 능력: 높음
  - 메타인지 수준: 높음
- **행동 지표**:
  - 문제 풀이 시간: 평균 이하
  - 힌트 요청 빈도: 낮음
  - 정답률 변동: 안정적
- **학생 발화 예시**: "아, 이거 다른 문제랑 연결되네요", "이 개념 알겠어요!"
- **상호작용 전략**:
  - Tone: Encouraging
  - Pace: Fast
  - Intervention: AutonomySupport
  - Information Depth: Advanced

### C2: 인지 피로형 (Cognitive Fatigued)
- **ID**: C2
- **Alias**: "지친 학습자"
- **인지적 특성**:
  - 정보 처리 속도: 느려짐
  - 집중력 지속 시간: 짧음
  - 오류 민감도: 낮음
- **행동 지표**:
  - 입력 이벤트 감소: 현저함
  - 응답 시간 증가: 30% 이상
  - 반복 실수 빈도: 증가
- **학생 발화 예시**: "잘 모르겠어요", "너무 어려워요", "쉬고 싶어요"
- **상호작용 전략**:
  - Tone: Calm
  - Pace: Slow
  - Intervention: RestSuggestion
  - Information Depth: Basic

### C3: 개념 지향형 (Concept-Oriented)
- **ID**: C3
- **Alias**: "이해 중심 학습자"
- **인지적 특성**:
  - 학습 스타일: 이해 후 적용
  - 개념 체류 시간: 긴 편
  - 질문 유형: 왜/어떻게 중심
- **행동 지표**:
  - 개념 설명 영역 체류 시간: 긴 편
  - 예제 확인 빈도: 높음
  - 문제 풀이 전 숙고 시간: 김
- **학생 발화 예시**: "이게 왜 이렇게 되는 거예요?", "원리가 뭐예요?"
- **상호작용 전략**:
  - Tone: Professional
  - Pace: Moderate
  - Intervention: ConceptExplanation
  - Information Depth: Detailed

### C4: 문제풀이 지향형 (Problem-Solving Oriented)
- **ID**: C4
- **Alias**: "실행 중심 학습자"
- **인지적 특성**:
  - 학습 스타일: 실행하며 학습
  - 개념 체류 시간: 짧은 편
  - 시도 성향: 즉각적
- **행동 지표**:
  - 문제 시작까지 시간: 짧음
  - 오답 후 재시도 속도: 빠름
  - 개념 복습 빈도: 낮음
- **학생 발화 예시**: "일단 풀어볼게요", "답이 뭐예요?", "다음 문제요"
- **상호작용 전략**:
  - Tone: Direct
  - Pace: Fast
  - Intervention: PracticeGuidance
  - Information Depth: Procedural

### C5: 패턴 인식형 (Pattern Recognition)
- **ID**: C5
- **Alias**: "유형 분류 학습자"
- **인지적 특성**:
  - 문제 분류 능력: 높음
  - 유사 문제 연결: 강함
  - 공식 활용 선호도: 높음
- **행동 지표**:
  - 유형별 정답률 편차: 큼
  - 새 유형 적응 시간: 긴 편
  - 익숙한 유형 속도: 매우 빠름
- **학생 발화 예시**: "이거 그 유형이죠?", "전에 풀었던 거랑 비슷해요"
- **상호작용 전략**:
  - Tone: Systematic
  - Pace: Adaptive
  - Intervention: PatternExploration
  - Information Depth: Comparative

### C6: 추론 지향형 (Reasoning-Oriented)
- **ID**: C6
- **Alias**: "논리적 사고자"
- **인지적 특성**:
  - 단계별 추론 선호: 높음
  - 검증 습관: 강함
  - 자기 설명 성향: 높음
- **행동 지표**:
  - 풀이 과정 입력: 상세함
  - 검산 빈도: 높음
  - 실수 발견 능력: 높음
- **학생 발화 예시**: "이렇게 하면 이렇게 되니까...", "맞는지 확인해볼게요"
- **상호작용 전략**:
  - Tone: Analytical
  - Pace: Moderate
  - Intervention: ReasoningSupport
  - Information Depth: Logical

---

## B (Behavioral) - 행동적 페르소나

### B1: 적극 참여형 (Active Engager)
- **ID**: B1
- **Alias**: "열정적 참여자"
- **행동적 특성**:
  - 상호작용 빈도: 높음
  - 기능 탐색 성향: 적극적
  - 피드백 수용도: 높음
- **행동 지표**:
  - 클릭/입력 이벤트: 평균 이상
  - 세션 지속 시간: 긴 편
  - 추가 자료 확인 빈도: 높음
- **학생 발화 예시**: "더 해볼게요!", "다른 문제도 있어요?"
- **상호작용 전략**:
  - Tone: Encouraging
  - Pace: Fast
  - Intervention: ChallengeExtension
  - Engagement Level: High

### B2: 소극 관망형 (Passive Observer)
- **ID**: B2
- **Alias**: "신중한 관찰자"
- **행동적 특성**:
  - 상호작용 빈도: 낮음
  - 관찰 선호: 높음
  - 시작까지 시간: 긴 편
- **행동 지표**:
  - 입력 이벤트: 평균 이하
  - 페이지 체류 시간: 긴 편
  - 첫 행동까지 시간: 김
- **학생 발화 예시**: "좀 더 볼게요", "아직이요"
- **상호작용 전략**:
  - Tone: Warm
  - Pace: Slow
  - Intervention: GentlePrompt
  - Engagement Level: Gradual

### B3: 즉흥 반응형 (Spontaneous Responder)
- **ID**: B3
- **Alias**: "직관적 행동자"
- **행동적 특성**:
  - 응답 속도: 빠름
  - 숙고 시간: 짧음
  - 수정 빈도: 높음
- **행동 지표**:
  - 첫 응답 시간: 짧음
  - 답안 수정 횟수: 많음
  - 실수 후 즉시 재시도: 빈번
- **학생 발화 예시**: "이거!", "아 아니다, 이거요"
- **상호작용 전략**:
  - Tone: Supportive
  - Pace: Fast
  - Intervention: ReflectionPrompt
  - Engagement Level: Dynamic

### B4: 신중 계획형 (Deliberate Planner)
- **ID**: B4
- **Alias**: "체계적 학습자"
- **행동적 특성**:
  - 계획 성향: 높음
  - 순서 준수: 강함
  - 검토 습관: 철저함
- **행동 지표**:
  - 숙고 시간: 긴 편
  - 수정 빈도: 낮음
  - 순차적 진행: 일관됨
- **학생 발화 예시**: "천천히 해볼게요", "순서대로 할게요"
- **상호작용 전략**:
  - Tone: Professional
  - Pace: Moderate
  - Intervention: StructuredGuidance
  - Engagement Level: Steady

### B5: 지속 몰입형 (Sustained Flow)
- **ID**: B5
- **Alias**: "몰입 학습자"
- **행동적 특성**:
  - 집중 지속력: 높음
  - 외부 방해 저항: 강함
  - 과제 완수율: 높음
- **행동 지표**:
  - 연속 활동 시간: 긴 편
  - 중단 빈도: 낮음
  - 과제 완료율: 높음
- **학생 발화 예시**: "좀 더 할게요", "다 끝낼게요"
- **상호작용 전략**:
  - Tone: Minimal
  - Pace: Self-Directed
  - Intervention: NonIntrusive
  - Engagement Level: Autonomous

### B6: 간헐 집중형 (Intermittent Focus)
- **ID**: B6
- **Alias**: "파동적 학습자"
- **행동적 특성**:
  - 집중 패턴: 불규칙
  - 휴식 필요도: 높음
  - 활동 간 전환: 빈번
- **행동 지표**:
  - 활동 지속 시간: 변동 큼
  - 휴식 요청: 빈번
  - 중단 후 재개: 반복됨
- **학생 발화 예시**: "잠깐 쉴게요", "다시 할게요"
- **상호작용 전략**:
  - Tone: Patient
  - Pace: Flexible
  - Intervention: RestScheduling
  - Engagement Level: Pulsed

---

## E (Emotional) - 정서적 페르소나

### E1: 자신감형 (Confident)
- **ID**: E1
- **Alias**: "자기확신 학습자"
- **정서적 특성**:
  - 자기효능감: 높음
  - 도전 수용도: 높음
  - 실패 회복력: 강함
- **행동 지표**:
  - 어려운 문제 시도: 적극적
  - 오답 후 정서 변화: 최소
  - 긍정적 표현: 빈번
- **학생 발화 예시**: "할 수 있어요", "어려워도 해볼게요"
- **상호작용 전략**:
  - Tone: Encouraging
  - Pace: Fast
  - Intervention: ChallengeProvision
  - Emotional Support: Minimal

### E2: 불안형 (Anxious)
- **ID**: E2
- **Alias**: "걱정하는 학습자"
- **정서적 특성**:
  - 실수 두려움: 높음
  - 확인 필요도: 높음
  - 스트레스 민감도: 높음
- **행동 지표**:
  - 답안 제출 주저: 빈번
  - 확인 질문: 많음
  - 부정적 자기 발화: 관찰됨
- **학생 발화 예시**: "이게 맞아요?", "틀리면 어떡하죠?", "못할 것 같아요"
- **상호작용 전략**:
  - Tone: Calm
  - Pace: Slow
  - Intervention: Reassurance
  - Emotional Support: High

### E3: 권태형 (Bored)
- **ID**: E3
- **Alias**: "지루해하는 학습자"
- **정서적 특성**:
  - 흥미 수준: 낮음
  - 동기 저하: 관찰됨
  - 외부 자극 필요: 높음
- **행동 지표**:
  - 반응 지연: 증가
  - 최소 노력 성향: 관찰됨
  - 관계없는 행동: 증가
- **학생 발화 예시**: "지루해요", "재미없어요", "언제 끝나요?"
- **상호작용 전략**:
  - Tone: Energetic
  - Pace: Variable
  - Intervention: InterestStimulation
  - Emotional Support: Engagement

### E4: 도전형 (Challenged)
- **ID**: E4
- **Alias**: "도전받는 학습자"
- **정서적 특성**:
  - 적절한 긴장감: 유지
  - 성취 동기: 높음
  - 노력 투입 의지: 강함
- **행동 지표**:
  - 문제 해결 노력: 적극적
  - 포기 없이 재시도: 빈번
  - 집중도: 높음
- **학생 발화 예시**: "어렵지만 해볼게요", "다시 도전해볼래요"
- **상호작용 전략**:
  - Tone: Supportive
  - Pace: Moderate
  - Intervention: ScaffoldedSupport
  - Emotional Support: Encouraging

### E5: 좌절형 (Frustrated)
- **ID**: E5
- **Alias**: "좌절한 학습자"
- **정서적 특성**:
  - 무력감: 높음
  - 포기 성향: 관찰됨
  - 부정적 감정: 우세
- **행동 지표**:
  - 시도 포기: 빈번
  - 부정적 발화: 증가
  - 활동 회피: 관찰됨
- **학생 발화 예시**: "못하겠어요", "포기할래요", "왜 안 되는 거예요?"
- **상호작용 전략**:
  - Tone: Empathetic
  - Pace: Very Slow
  - Intervention: EmotionalRecovery
  - Emotional Support: Maximum

### E6: 안정형 (Stable)
- **ID**: E6
- **Alias**: "평온한 학습자"
- **정서적 특성**:
  - 감정 기복: 적음
  - 꾸준한 노력: 유지
  - 스트레스 조절: 양호
- **행동 지표**:
  - 일정한 학습 패턴: 유지
  - 감정 표현: 중립적
  - 안정적 수행: 관찰됨
- **학생 발화 예시**: "괜찮아요", "계속 할게요"
- **상호작용 전략**:
  - Tone: Neutral
  - Pace: Steady
  - Intervention: Maintenance
  - Emotional Support: Minimal

---

## 복합 페르소나 (Composite Personas)

### 상황별 복합 페르소나 매핑

#### S1 상황 (학습 이탈 조짐)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S1_CP1 | C2+B2+E3 | 인지 피로 + 소극 관망 + 권태 | 즉각적 재진입 유도, 쉬운 승리 제공 |
| S1_CP2 | C2+B6+E5 | 인지 피로 + 간헐 집중 + 좌절 | 감정 회복 후 점진적 재진입 |
| S1_CP3 | C4+B3+E3 | 문제풀이 지향 + 즉흥 반응 + 권태 | 새로운 도전 과제로 흥미 자극 |

#### S2 상황 (현재 위치 지연)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S2_CP1 | C3+B4+E2 | 개념 지향 + 신중 계획 + 불안 | 진도 압박 완화, 개념 심화 허용 |
| S2_CP2 | C2+B6+E5 | 인지 피로 + 간헐 집중 + 좌절 | 루틴 재설계, 부담 경감 |
| S2_CP3 | C4+B1+E4 | 문제풀이 지향 + 적극 참여 + 도전 | 효율적 경로 제시, 동기 강화 |

#### S3 상황 (휴식 루틴 이상)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S3_CP1 | C2+B5+E4 | 인지 피로 + 지속 몰입 + 도전 | 강제 휴식 안내, 과몰입 경고 |
| S3_CP2 | C1+B1+E1 | 인지 활성화 + 적극 참여 + 자신감 | 휴식 중요성 인식 유도 |
| S3_CP3 | C2+B6+E3 | 인지 피로 + 간헐 집중 + 권태 | 휴식 패턴 재설계 |

#### S4 상황 (오답 패턴 반복)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S4_CP1 | C4+B3+E2 | 문제풀이 지향 + 즉흥 반응 + 불안 | 개념 복습 강조, 속도 조절 |
| S4_CP2 | C5+B4+E5 | 패턴 인식 + 신중 계획 + 좌절 | 오류 패턴 분석, 대안 접근법 |
| S4_CP3 | C3+B2+E4 | 개념 지향 + 소극 관망 + 도전 | 개념 재설명, 단계별 가이드 |

#### S5 상황 (정서적 침착도 저하)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S5_CP1 | C2+B3+E2 | 인지 피로 + 즉흥 반응 + 불안 | 감정 안정화, 속도 조절 |
| S5_CP2 | C4+B1+E5 | 문제풀이 지향 + 적극 참여 + 좌절 | 성공 경험 제공, 난이도 조절 |
| S5_CP3 | C6+B4+E2 | 추론 지향 + 신중 계획 + 불안 | 검증 지원, 확신 강화 |

#### S6 상황 (목표 대비 활동 불균형)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S6_CP1 | C3+B5+E6 | 개념 지향 + 지속 몰입 + 안정 | 활동 균형 인식 유도 |
| S6_CP2 | C4+B1+E1 | 문제풀이 지향 + 적극 참여 + 자신감 | 개념 보강 필요성 제시 |
| S6_CP3 | C5+B4+E4 | 패턴 인식 + 신중 계획 + 도전 | 다양한 유형 경험 권장 |

#### S7 상황 (시그너처 루틴 형성)
| 복합 페르소나 | 구성 | 특징 | 우선 전략 |
|--------------|------|------|----------|
| S7_CP1 | C1+B5+E1 | 인지 활성화 + 지속 몰입 + 자신감 | 루틴 강화, 긍정 피드백 |
| S7_CP2 | C6+B4+E6 | 추론 지향 + 신중 계획 + 안정 | 루틴 정착 지원 |
| S7_CP3 | C5+B1+E4 | 패턴 인식 + 적극 참여 + 도전 | 루틴 확장, 도전 추가 |

---

## 페르소나 식별 우선순위

### 식별 순서
1. **정서적 페르소나 (E)** - 가장 먼저 확인 (감정 상태가 다른 모든 것에 영향)
2. **행동적 페르소나 (B)** - 실시간 행동 패턴 기반
3. **인지적 페르소나 (C)** - 학습 스타일 및 문제 해결 방식

### 신뢰도 임계값
- **높은 신뢰도**: 0.85 이상 - 단일 페르소나 적용
- **중간 신뢰도**: 0.7 ~ 0.85 - 주 페르소나 + 보조 페르소나
- **낮은 신뢰도**: 0.7 미만 - AI 보강 분석 요청

---

## MBTI 페르소나 매핑

### 내향형 (I) 학생
- 선호 페르소나: B2, B4, B5
- 상호작용 조정: 텍스트 기반, 조용한 톤, 적은 방해

### 외향형 (E) 학생
- 선호 페르소나: B1, B3
- 상호작용 조정: 시각적 요소, 활발한 톤, 빈번한 피드백

### 감각형 (S) 학생
- 선호 페르소나: C4, C5
- 상호작용 조정: 구체적 예시, 단계별 안내

### 직관형 (N) 학생
- 선호 페르소나: C3, C6
- 상호작용 조정: 개념적 설명, 연결 관계 강조

### 사고형 (T) 학생
- 선호 페르소나: C6, E6
- 상호작용 조정: 논리적 피드백, 객관적 평가

### 감정형 (F) 학생
- 선호 페르소나: E1, E4, E5
- 상호작용 조정: 공감적 피드백, 격려 중심

---

## 관련 데이터베이스 테이블

### at_persona_identification
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL
- `session_id` VARCHAR(64)
- `cognitive_persona` VARCHAR(10) - C1~C6
- `behavioral_persona` VARCHAR(10) - B1~B6
- `emotional_persona` VARCHAR(10) - E1~E6
- `composite_persona` VARCHAR(20) - S1_CP1 등
- `confidence_c` DECIMAL(3,2)
- `confidence_b` DECIMAL(3,2)
- `confidence_e` DECIMAL(3,2)
- `situation_code` VARCHAR(10) - S1~S7
- `mbti_type` VARCHAR(4)
- `detected_at` TIMESTAMP

### at_persona_transition_log
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `user_id` INT NOT NULL
- `from_persona` VARCHAR(20)
- `to_persona` VARCHAR(20)
- `trigger_event` VARCHAR(100)
- `transition_reason` TEXT
- `created_at` TIMESTAMP
