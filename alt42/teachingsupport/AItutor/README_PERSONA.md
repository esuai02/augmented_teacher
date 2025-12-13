# 학습자 페르소나 시스템

12가지 실제 수업 상황 기반 학습자 페르소나 데이터 관리 시스템

## 페르소나 목록

### 1. 막힘-회피형 (Avoider) - P001
- **상황**: 문제 읽다 막히면 바로 연필 내려놓음
- **행동**: "몰라요…" / 문제 안 읽고 다음으로 넘김
- **숨은 원인**: 실패 불안 + 작업기억 과부하
- **개입**: 1단계 청킹 → 초미세 단서제공 → 시선추적 기반 '다시 주목 리드'

### 2. 확인요구형 (Checker) - P002
- **상황**: 맞는지 계속 물어봄
- **행동**: "이렇게 하면 되죠?" 반복
- **숨은 원인**: 낮은 학습 효능감
- **개입**: 정답 확인 금지 → 진행도 피드백 강화 → 스몰 스텝 성공 경험 축적

### 3. 감정출렁형 (Emotion-driven) - P003
- **상황**: 문제 한 개 틀리면 전체 기분 하락
- **행동**: 표정 다운·속도 느려짐
- **숨은 원인**: 정서 조절력 부족
- **개입**: 즉시 공감 → 정서 레이블링 → 난이도 미세조절로 안정감 확보

### 4. 빠른데 허술형 (Speed-but-Miss) - P004
- **상황**: 빨리 끝냈는데 실수 많음
- **행동**: 계산 실수·단위 누락
- **숨은 원인**: 과도한 자동화 + 검증 회로 부재
- **개입**: 마지막 10초 검증 루틴 도입

### 5. 집중 튐형 (Attention Hopper) - P005
- **상황**: 문제 읽다가 다른 줄로 눈이 튐
- **행동**: 시선 불안정, 방향성 없는 질문
- **숨은 원인**: 주의 지속시간 짧음
- **개입**: 시선 리다이렉션 → 문장 단위로 OCR·하이라이트 가이드

### 6. 패턴추론형 (Pattern Seeker) - P006
- **상황**: 전체 구조 먼저 찾으려 함
- **행동**: "여기서 의도는…" / 원리 탐색 선호
- **숨은 원인**: 고차원적 처리 선호
- **개입**: 구조 먼저 제시 → 사례→공식 순서로 제공

### 7. 최대한 쉬운길 찾기형 (Efficiency Maximizer) - P007
- **상황**: 최소 노력으로 최대 결과 원함
- **행동**: 지름길, 공략, 노하우 질문
- **숨은 원인**: 합리적 학습자
- **개입**: '핵심 규칙 20%' 먼저 제시 → 유형화 기반 반복

### 8. 불안과몰입형 (Over-focusing Worrier) - P008
- **상황**: 쉬운 문제에도 오래 붙잡힘
- **행동**: 확인·재확인 반복
- **숨은 원인**: 실수에 대한 과도한 민감성
- **개입**: 시간제한 + "여기까지만 확인 규칙"

### 9. 추상-언어 약함형 (Concrete Learner) - P009
- **상황**: 설명은 이해 못하지만 예시는 잘 따라옴
- **행동**: "예시 하나만 더요"
- **숨은 원인**: 추상처리능력 낮음
- **개입**: 하→상 구조 (예시 → 규칙 → 적용)

### 10. 상호작용 의존형 (Interactive Dependent) - P010
- **상황**: 혼자 풀면 갑자기 정지
- **행동**: 옆에서 질문해주면 폭발적으로 진행
- **숨은 원인**: 외부 자극 필요
- **개입**: 로봇/아바타의 음성 큐로 지속 자극

### 11. 무기력·저동기형 (Low Drive) - P011
- **상황**: 시작부터 에너지 없음
- **행동**: 앉아 있지만 진도 안 나감
- **숨은 원인**: 정서적 소진 / 성공경험 부족
- **개입**: 초단위 목표·즉각 강화 → "지금 막힌 이유" 메타인지 질문

### 12. 메타인지 고수형 (Meta-high) - P012
- **상황**: 스스로 오류검출·전략수립
- **행동**: "이건 구조가 이래서…"
- **숨은 원인**: 높은 자기조절력
- **개입**: 고난도 전략·심화 → 풀이 비교·추론게임 제공

## 사용 방법

### 페르소나 조회

```php
require_once('includes/persona_manager.php');

$personaManager = new PersonaManager();

// 모든 페르소나 조회
$allPersonas = $personaManager->getAllPersonas();

// 특정 페르소나 조회
$persona = $personaManager->getPersona('P001');

// 카테고리별 조회
$cognitivePersonas = $personaManager->getPersonasByCategory('인지적');
```

### 학생 페르소나 매칭

```php
// 학생의 상호작용 데이터 기반 페르소나 매칭
$matches = $personaManager->matchStudentPersona($studentId, [
    'user_input' => '몰라요...',
    'context' => [...]
]);

// 가장 높은 점수 페르소나
$topMatch = $matches[0];
```

### 개입 전략 가져오기

```php
$strategy = $personaManager->getInterventionStrategy('P001');
// 반환: 개입 전략, 우선순위, 권장 접근 방식
```

### API 사용

```javascript
// 학생 페르소나 매칭
const response = await fetch('api/persona_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'match_student_persona',
        student_id: 1,
        interaction_data: {
            user_input: '몰라요...',
            context: {...}
        }
    })
});

// 학생의 페르소나 조회
const response = await fetch('api/persona_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'get_student_personas',
        student_id: 1
    })
});
```

## 데이터 구조

### 페르소나 데이터
```json
{
  "persona_id": "P001",
  "name": "막힘-회피형",
  "name_en": "Avoider",
  "situation": "문제 읽다 막히면 바로 연필 내려놓음",
  "behaviors": ["말하기: \"몰라요…\"", "문제 안 읽고 다음으로 넘김"],
  "hidden_causes": ["실패 불안", "작업기억 과부하"],
  "interventions": ["1단계 청킹", "초미세 단서 제공"],
  "metadata": {
    "category": "인지적",
    "difficulty_level": "high",
    "intervention_priority": 1
  }
}
```

### 학생-페르소나 매칭
```json
{
  "student_id": 1,
  "persona_id": "P001",
  "confidence": 0.85,
  "matched_at": "2025-01-27 10:00:00"
}
```

## 자동 매칭

상호작용 API (`api/interact.php`)를 사용하면 자동으로:
1. 학생의 상호작용 히스토리 분석
2. 페르소나 매칭 점수 계산
3. 가장 높은 점수 페르소나 저장
4. 개입 전략 제공

## 카테고리 분류

- **인지적**: P001, P004, P005, P006, P009
- **정서적**: P002, P003, P008, P011
- **전략적**: P007, P012
- **행동적**: P010

