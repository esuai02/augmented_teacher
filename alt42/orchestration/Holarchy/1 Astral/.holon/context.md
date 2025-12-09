# Context Structure
# 에이전트가 만나는 상황의 구조 정의

## W_ROOT (최상위 의지 - 불변)
```yaml
identity: 전국 최고의 AI 기반 자기진화형 K-12 수학 교육 기업
drive: 1:1 과외 효과를 기술로 재현·확장 (Bloom's 2σ)
north_star: 모든 학생이 AI와 함께 수학을 정복할 수 있다
non_negotiables:
  - 학생 학습 효과
  - 시스템 자기진화 능력
  - 홀론 구조 일관성
```

---

## situations (상황 목록)
```yaml
init:
  description: 프로젝트/홀론 초기화
  next: [develop, blocked]

develop:
  description: 개발/실행 진행 중
  next: [review, blocked, complete]

review:
  description: 검토 필요 (사람 개입)
  next: [develop, blocked, complete]

blocked:
  description: 진행 불가 (위험 감지)
  next: [review, develop]

complete:
  description: 완료
  next: [init]
```

---

## current (현재 상태)
```yaml
situation: init
sub_context: null
risk_level: safe
attachments: []
active_holons: []
```

---

## history (변경 이력)
```yaml
# [timestamp]: situation 변경 기록
- 2025-12-05T00:00:00: init - 시스템 초기화
```

---

## diagnostics (진단 파라미터)
```yaml
SEI: 0.0   # 학습 효과 지수 (Student Effectiveness Index)
EC: 0.0    # 몰입 지속 지수 (Engagement Continuity)
ES: 0.0    # 정서 안전 지수 (Emotional Safety)
BV: 0.0    # 지점 편차 지수 (Branch Variance)
GR: 0.0    # 일반화 신뢰성 (Generalization Reliability)
```

---

## _splits (복잡도 증가 시 분리된 파일들)
```yaml
# 100줄 초과 시 자동 분리
includes: []
```

