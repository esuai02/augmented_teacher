# 필기 기반 AI 튜터 설계 문서

## 개요

필기 패턴을 분석하여 학생의 인지 상태를 유추하고, 비침습적인 방식으로 질문하고 개입하는 AI 튜터 시스템입니다.

## 핵심 철학

```
1순위: 필기에서 유추한다
2순위: 유추가 불확실하면 물어본다
3순위: 물어볼 때도 펜을 멈추게 하지 않는다
```

## 시스템 아키텍처

```
[필기 입력] 
    ↓
[패턴 분석 엔진]
    ↓
[유추 엔진] → 확신도 판정
    ↓
    ├─ 높음 → [즉시 개입]
    └─ 낮음 → [비침습적 질문 생성]
                ↓
            [학생 응답 대기]
                ↓
            [개입 실행] 또는 [점진적 강화]
```

## Part 1: 필기 패턴 분석

### 1.1 인지 상태 추론

| 필기 패턴 | 유추 | 확신도 | 개입 활동 매핑 |
|-----------|------|--------|----------------|
| 3초 이상 멈춤 | 막힘 또는 사고 중 | 중 (0.6) | INT_1_3 (사고 여백 제공) |
| 5초 이상 멈춤 + 아무것도 없음 | 백지 막힘 | 높음 (0.9) | INT_5_5 (힌트 질문) |
| 지우기 → 다시 쓰기 반복 | 혼란, 불확실 | 높음 (0.85) | INT_2_3 (단계 분해) |
| 빠르고 끊김 없는 필기 | 이해하고 진행 중 | 높음 (0.9) | 개입 없음 (관찰만) |
| 같은 자리에 덧쓰기 | 자기 수정 시도 | 중 (0.7) | INT_1_5 (자기 수정 대기) |
| 문제와 무관한 낙서 | 주의 산만 또는 불안 | 중 (0.65) | INT_7_5 (유머/가벼운 전환) |
| 첫 글자만 쓰고 멈춤 | 시작은 아는데 전개 모름 | 높음 (0.85) | INT_6_3 (함께 완성) |

### 1.2 오류 유형 추론

| 필기 패턴 | 유추 | 개입 활동 매핑 |
|-----------|------|----------------|
| 부호 위에 고침 흔적 | 부호 실수 가능성 | INT_6_1 (즉시 교정) |
| 분수선 불명확 | 분수 개념 혼란 | INT_3_1 (일상 비유) |
| 등호 여러 개 연속 | 등식 변형 과정 혼란 | INT_2_4 (역순 재구성) |
| 숫자 위에 숫자 | 계산 실수 인식 | INT_6_2 (부분 인정 확장) |

### 1.3 진행 상태 추론

| 필기 패턴 | 유추 | 개입 활동 매핑 |
|-----------|------|----------------|
| 풀이가 문제 바로 아래 | 정상 시작 | 개입 없음 |
| 여백 곳곳에 흩어진 필기 | 시행착오 중 | INT_5_7 (메타인지 질문) |
| 마지막 줄에서 멈춤 | 답 직전 또는 검토 중 | INT_1_3 (사고 여백 제공) |
| 답에 동그라미 | 완료 신호 | INT_7_1 (노력 인정) |

## Part 2: 비침습적 질문 방식

### 2.1 방식 1: 여백 속삭임 (Margin Whisper)

**특징:**
- 필기 영역 바깥 여백에 희미하게 질문 표시
- 학생이 원할 때만 반응
- 무시하면 5초 후 자연스럽게 사라짐

**구현 요소:**
- 위치: 필기 영역 외부 (상/하/좌/우 여백)
- 스타일: 연한 회색, 작은 글씨 (12px)
- 상호작용: 터치/클릭 시 응답, 무시 시 자동 사라짐
- 타이밍: 멈춤 5초 후 표시

### 2.2 방식 2: 펜 제스처 응답

**제스처 사전:**
- ✓ 체크 = "응" (긍정)
- ✗ 엑스 = "아니" (부정)
- ? 물음표 = "다른 게 헷갈려" (혼란)
- → 화살표 = "괜찮아, 계속 할게" (계속 진행)

**구현 요소:**
- 제스처 인식: 간단한 선형 패턴 인식
- 응답 시간: 0.1초 이내
- UI: 별도 버튼 없음, 캔버스 내 직접 그리기

### 2.3 방식 3: 하단 호흡 바 (Breathing Bar)

**색상 의미:**
- 파랑 = AI 관찰 중 (정상)
- 노랑 천천히 깜빡 = "괜찮아?"
- 초록 = "잘하고 있어"
- 빨강 = "여기서 막혔어?"

**구현 요소:**
- 높이: 2px
- 위치: 화면 맨 아래
- 애니메이션: 미세한 움직임 (breathing effect)
- 상호작용: 탭 시 AI 메시지 확장

### 2.4 방식 4: 모서리 이모지 (Corner Emoji)

**이모지 사전:**
- 😊 = "이해됨"
- 🤔 = "헷갈려"
- ⏸️ = "잠깐 생각 중"
- 💡 = "힌트 줘"

**구현 요소:**
- 위치: 네 모서리
- 크기: 24x24px
- 상호작용: 1탭 = 0.1초
- 기능: 학생이 선제적으로 상태 전달 가능

### 2.5 방식 5: 필기 내 인라인 질문

**트리거:**
- 학생이 물음표(?)를 쓰면
- AI가 그 옆에 바로 응답

**구현 요소:**
- 위치: 물음표 옆 (인라인)
- 스타일: 작고 연한 글씨
- 트리거: 물음표 패턴 인식
- UI: 별도 UI 없이 캔버스 내에서 대화

## Part 3: 질문 타이밍 규칙

| 상황 | 시간 | AI 행동 | 개입 활동 | 확신도 |
|------|------|---------|-----------|--------|
| 멈춤 3초 | 3s | 관찰만 (아직 안 물어봄) | 없음 | - |
| 멈춤 5초 | 5s | 여백에 "생각 중?" 살짝 표시 | INT_1_3 | 0.6 |
| 멈춤 10초 | 10s | "막혔으면 ? 그려줘" | INT_5_5 | 0.7 |
| 멈춤 15초 + 무반응 | 15s | "힌트 줄까?" 좀 더 명확히 | INT_5_5 | 0.8 |
| 지우기 3회 이상 | - | "어디가 헷갈려?" | INT_5_7 | 0.85 |
| 빠른 진행 중 | - | 절대 방해 안 함 | 없음 | - |

## Part 4: 데이터 구조

### 4.1 필기 패턴 데이터

```json
{
    "pattern_id": "PATTERN_001",
    "pattern_type": "pause",
    "description": "3초 이상 멈춤",
    "detection_rules": {
        "time_threshold": 3,
        "no_stroke": true,
        "position": "any"
    },
    "inference": {
        "state": "막힘 또는 사고 중",
        "confidence": 0.6,
        "possible_states": [
            {"state": "막힘", "probability": 0.4},
            {"state": "사고 중", "probability": 0.6}
        ]
    },
    "intervention_mapping": {
        "high_confidence": ["INT_1_3"],
        "low_confidence": ["INT_5_1"]
    }
}
```

### 4.2 유추 결과 데이터

```json
{
    "inference_id": "INF_001",
    "timestamp": "2024-01-01T12:00:00",
    "student_id": 123,
    "pattern_detected": "PATTERN_001",
    "inferred_state": {
        "primary": "막힘",
        "confidence": 0.6,
        "alternatives": [
            {"state": "사고 중", "confidence": 0.4}
        ]
    },
    "recommended_interventions": [
        {
            "activity_id": "INT_1_3",
            "confidence": 0.6,
            "method": "margin_whisper",
            "message": "생각 중이야?"
        }
    ],
    "timing": {
        "detected_at": "2024-01-01T12:00:00",
        "intervention_ready_at": "2024-01-01T12:00:05",
        "escalation_at": "2024-01-01T12:00:10"
    }
}
```

### 4.3 비침습적 질문 데이터

```json
{
    "question_id": "Q_001",
    "type": "margin_whisper",
    "content": "생각 중이야?",
    "position": {
        "side": "right",
        "offset_x": 10,
        "offset_y": 0
    },
    "style": {
        "font_size": 12,
        "color": "#CCCCCC",
        "opacity": 0.6
    },
    "interaction": {
        "auto_hide": true,
        "hide_after": 5,
        "on_click": "show_response_options"
    },
    "gesture_responses": [
        {"gesture": "check", "meaning": "yes"},
        {"gesture": "cross", "meaning": "no"},
        {"gesture": "arrow", "meaning": "continue"}
    ]
}
```

### 4.4 제스처 응답 데이터

```json
{
    "gesture_id": "GEST_001",
    "pattern": "check",
    "meaning": "yes",
    "stroke_data": {
        "points": [[x1, y1], [x2, y2], ...],
        "duration": 0.5,
        "pressure": 0.8
    },
    "recognition_confidence": 0.95,
    "interpreted_response": {
        "type": "affirmative",
        "message": "학생이 긍정 응답"
    }
}
```

## Part 5: 클래스 구조

### 5.1 WritingPatternAnalyzer

```php
class WritingPatternAnalyzer {
    // 필기 패턴 감지
    public function detectPatterns($strokeData, $timingData);
    
    // 패턴 분류
    public function classifyPattern($pattern);
    
    // 패턴 확신도 계산
    public function calculateConfidence($pattern, $context);
}
```

### 5.2 InferenceEngine

```php
class InferenceEngine {
    // 인지 상태 유추
    public function inferCognitiveState($pattern, $confidence);
    
    // 오류 유형 유추
    public function inferErrorType($pattern);
    
    // 진행 상태 유추
    public function inferProgressState($pattern);
    
    // 확신도 기반 개입 결정
    public function decideIntervention($inference, $confidence);
}
```

### 5.3 NonIntrusiveQuestionManager

```php
class NonIntrusiveQuestionManager {
    // 질문 생성
    public function generateQuestion($inference, $method);
    
    // 질문 표시
    public function displayQuestion($question, $method);
    
    // 제스처 응답 처리
    public function handleGestureResponse($gesture);
    
    // 점진적 강화
    public function escalateQuestion($question, $noResponseTime);
}
```

### 5.4 TimingRuleEngine

```php
class TimingRuleEngine {
    // 타이밍 규칙 평가
    public function evaluateTimingRules($situation, $elapsedTime);
    
    // 개입 타이밍 결정
    public function decideInterventionTiming($pattern, $context);
    
    // 점진적 강화 타이밍
    public function getEscalationTiming($currentLevel);
}
```

## Part 6: 42개 개입 활동과의 통합

### 6.1 필기 패턴 → 개입 활동 매핑 테이블

| 필기 패턴 | 확신도 | 개입 활동 | 질문 방식 |
|-----------|--------|-----------|-----------|
| 3초 멈춤 | 중 | INT_1_3 (사고 여백 제공) | 여백 속삭임 |
| 5초 멈춤 + 백지 | 높음 | INT_5_5 (힌트 질문) | 하단 호흡 바 |
| 지우기 반복 | 높음 | INT_2_3 (단계 분해) | 인라인 질문 |
| 부호 고침 | 높음 | INT_6_1 (즉시 교정) | 인라인 질문 |
| 첫 글자만 | 높음 | INT_6_3 (함께 완성) | 펜 제스처 |
| 낙서 | 중 | INT_7_5 (유머/가벼운 전환) | 모서리 이모지 |

### 6.2 통합 흐름

```
[필기 패턴 감지]
    ↓
[패턴 분석] → 패턴 ID
    ↓
[유추 엔진] → 인지 상태 + 확신도
    ↓
[개입 활동 선택] → 42개 개입 중 선택
    ↓
[질문 방식 선택] → 5가지 방식 중 선택
    ↓
[비침습적 질문 표시]
    ↓
[학생 응답 대기]
    ↓
[응답 처리] → 개입 활동 실행
```

## Part 7: 구현 우선순위

### Phase 1: 기본 패턴 감지
1. 멈춤 감지 (시간 기반)
2. 지우기 감지 (스트로크 취소)
3. 덧쓰기 감지 (같은 위치)

### Phase 2: 유추 엔진
1. 확신도 계산 로직
2. 인지 상태 유추
3. 개입 활동 매핑

### Phase 3: 비침습적 질문
1. 여백 속삭임 구현
2. 하단 호흡 바 구현
3. 펜 제스처 인식

### Phase 4: 타이밍 규칙
1. 타이밍 규칙 엔진
2. 점진적 강화 로직
3. 자동 사라짐 처리

### Phase 5: 통합
1. 42개 개입 활동과 통합
2. 페르소나 기반 맞춤화
3. 효과성 추적

## Part 8: 확장 가능성

### 8.1 추가 패턴
- 수식 구조 분석
- 그래프 그리기 패턴
- 도형 그리기 패턴

### 8.2 추가 제스처
- 원 그리기 = "이 부분 설명해줘"
- 화살표 = "다음 단계로"
- 별표 = "중요해"

### 8.3 머신러닝 통합
- 패턴 인식 정확도 향상
- 개인화된 유추 모델
- 효과성 기반 학습

