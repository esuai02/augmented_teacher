# ⚛️ 양자 모델링 페르소나 시스템 (Quantum Persona Modeling)

## 개요

학생의 학습 상태를 양자역학의 파동 함수(Wave Function)로 모델링하여, 페르소나의 중첩(Superposition), 간섭(Interference), 붕괴(Collapse)를 시뮬레이션합니다.

이 시스템은 "학생의 심리 상태가 0과 1로 딱 떨어지지 않는다"는 점을 수학적으로 표현하며, **최적의 개입 타이밍(골든 타임)**과 **페르소나 전환 경로**를 계산합니다.

## 핵심 개념

### 1. 상태 벡터 (State Vector)

학생을 4가지 기저 페르소나의 **중첩 상태**로 정의합니다:

```
|Student⟩ = α|Sprinter⟩ + β|Diver⟩ + γ|Gamer⟩ + δ|Architect⟩
```

| 페르소나 | 아이콘 | 설명 |
|----------|--------|------|
| **Sprinter** | ⚡ | 속도 중심, 직관적, 실수 잦음 |
| **Diver** | 🤿 | 원리 중심, 느림, 완벽주의 |
| **Gamer** | 🎮 | 보상/경쟁 중심, 도파민 추구 |
| **Architect** | 🏛️ | 계획/안정 중심, 리스크 회피 |

### 2. 감쇠 진동 모델 (Damped Oscillation)

시간에 따른 **시너지(Synergy)**와 **역효과(Backfire)** 확률을 계산합니다:

```
P_synergy(t) = 0.5 × (1 + cos(ωt) × e^(-γt))
P_backfire(t) = (1 - P_synergy) + α × t
```

- **ω (omega)**: 인지 진동수 - 문제 난이도에 비례
- **γ (gamma)**: 감쇠율 - 학생 회복탄력성에 반비례
- **골든 타임**: P_backfire > P_synergy 되는 시점

### 3. 양자 간섭 (Quantum Interference)

감정과 피로도의 파동이 겹쳐 **보강 간섭** 또는 **상쇄 간섭**이 발생합니다:

- **보강 간섭 (Constructive)**: 학습 에너지 증폭 → 도전적 문제 제시
- **상쇄 간섭 (Destructive)**: 학습 효율 0 → 즉시 휴식 권장

### 4. 페르소나 스위칭 경로

정반대 성향 전환 시 **중간 단계(Bridge State)**를 거쳐 심리적 저항 최소화:

```
Sprinter → Diver (직접: 비용 5)
Sprinter → Gamer → Diver (우회: 비용 3)
```

## 파일 구조

```
quantum_modeling/
├── QuantumPersonaEngine.php   # 핵심 엔진 클래스
├── qmodeling_dashboard.php    # 시각화 대시보드
├── api.php                    # REST API 엔드포인트
├── db_setup.php               # DB 테이블 생성
└── README.md                  # 문서
```

## 사용법

### 1. 대시보드 접속

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/quantum_modeling/qmodeling_dashboard.php
```

### 2. API 호출

#### 엔진 정보 조회
```
GET api.php?action=engine_info
```

#### 전체 시뮬레이션
```
GET api.php?action=simulate&user_id=123&time_pressure=0.5&fatigue=0.3&emotion=0.7
```

#### 학습 역학 계산
```
GET api.php?action=calculate_dynamics&resilience=0.6&difficulty=0.5&elapsed=30
```

#### 페르소나 스위칭 경로
```
GET api.php?action=calculate_path&current=S&target=D
```

#### 골든 타임 계산
```
GET api.php?action=calculate_golden_time&resilience=0.6&difficulty=0.7
```

### 3. PHP 코드에서 사용

```php
require_once('QuantumPersonaEngine.php');

$engine = new QuantumPersonaEngine($userId);

// 초기 상태 생성
$state = $engine->initializeStateVector(['mbti' => 'INTJ']);

// 환경 연산자 적용
$state = $engine->applyContextOperator($state, 0.5, 0.3, 0.7);

// 페르소나 측정
$measurement = $engine->measurePersona($state);
echo "지배 페르소나: " . $measurement['dominant_name'];

// 학습 역학 계산
$dynamics = $engine->calculateLearningDynamics(0.6, 0.5, 30);
echo "골든 타임: " . $dynamics['golden_time'] . "초";

// 전환 경로 계산
$path = $engine->getOptimalSwitchingPath('S', 'D');
echo "경로: " . implode(' → ', $path['path_names']);
```

## 데이터베이스 테이블

### mdl_at_quantum_state
양자 상태 스냅샷 저장

| 필드 | 타입 | 설명 |
|------|------|------|
| state_vector | TEXT | 상태 벡터 JSON |
| probabilities | TEXT | 확률 분포 JSON |
| dominant_persona | VARCHAR(20) | 지배 페르소나 |
| synergy | FLOAT | 시너지 확률 |
| backfire | FLOAT | 역효과 확률 |
| golden_time | INT | 골든 타임 (초) |

### mdl_at_quantum_transition
페르소나 전환 로그

### mdl_at_quantum_interference
간섭 분석 로그

### mdl_at_quantum_intervention
골든 타임 개입 로그

## 적용 시나리오

### 시나리오 1: "찍어서 맞춘 것인가?"

```php
// 정답 상태를 '실력'과 '운'의 중첩으로 정의
$state = ['skill' => 0.6, 'luck' => 0.4];

// 응답 시간, 마우스 망설임 패턴으로 관측
if ($luck_probability > 0.5) {
    // "답은 맞았는데, 혹시 2번 보기랑 헷갈리지 않았어?"
    $engine->triggerMetacognitionQuestion();
}
```

### 시나리오 2: "칭찬 먼저? 지적 먼저?"

```php
// 순서 효과 계산 (비가환성)
$option_a = applyOrder(['praise', 'criticism'], $currentState); // 격려 후 지적
$option_b = applyOrder(['criticism', 'praise'], $currentState); // 지적 후 격려

// 최종 동기 벡터 크기가 큰 쪽 선택
if (magnitude($option_a) > magnitude($option_b)) {
    return "칭찬 먼저";
}
```

### 시나리오 3: "몰입 보호"

```php
// 양자 제논 효과 적용
if ($calmness_score > 0.9) {
    // 완전 몰입 상태 → 모든 개입 음소거
    $engine->muteAllInterventions();
}
```

## 기대 효과

1. **순서 효과 최적화**: 학생의 현재 '위상'에 따라 개념 설명과 퀴즈 순서 결정
2. **문맥 의존성 해결**: "모르겠어요"가 지적 어려움인지 심리적 회피인지 구분
3. **불확실성 관리**: 돌발 행동을 오류가 아닌 확률적 필연으로 수용

## 버전 히스토리

- **v1.0.0** (2025-12-06): 초기 버전
  - 상태 벡터 모델링
  - 감쇠 진동 모델
  - 간섭 효과 계산
  - 페르소나 스위칭 경로
  - 대시보드 UI

## 참고 문헌

- Quantum Probability Theory in Cognitive Science
- Damped Harmonic Oscillator Models
- Dijkstra's Algorithm for Optimal Path Finding

---

*이 시스템은 양자 컴퓨터를 사용하지 않습니다. 양자역학의 수학적 원리를 고전 컴퓨터에서 시뮬레이션하여 인간 심리의 복잡성을 모델링합니다.*


