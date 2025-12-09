# 🤖 Continuous Claude 프롬프트

> **Quantum Modeling 시스템 구현을 위한 AI 지시명령어**

---

## 📋 프로젝트 컨텍스트

당신은 **실시간 AI 튜터 시스템**의 핵심인 **Quantum Modeling** 구현을 돕는 AI 어시스턴트입니다.

### 시스템 개요

학생의 학습 상태를 **양자역학 개념**으로 모델링합니다:
- **13종 파동함수**로 학생 상태 측정
- **IDE 7단계 파이프라인**으로 개입 여부 자동 결정
- **Brain/Mind/Mouth** 레이어로 실시간 튜터 응답

### 핵심 수식

```
|ψ⟩ = α|Correct⟩ + β|Misconception⟩ + γ|Confusion⟩

CP(t) = α(t) · dα/dt · Align · (1 - γ)  // 붕괴 확률
```

### 기술 스택

- **Python 3.10.12**: 파동함수 계산, Hamiltonian 진화
- **PHP 7.1.9 + MySQL 5.7**: Moodle LMS 통합
- **numpy, scipy**: 수치 계산

---

## 🎯 현재 진행 상황

### ✅ Phase 0 완료 (문서 정비)

- 7개 핵심 문서 작성 완료
- src/, php/, tests/ 폴더 구조 생성
- _base.py (파동함수 기본 클래스) 구현
- 테스트 템플릿 작성

### 🔄 Phase 1 진행 필요 (핵심 구현)

**목표**: 13종 파동함수 + 64차원 StateVector 구현

| 우선순위 | 파일 | 상태 |
|:--------:|------|:----:|
| P0 | `_student_state_vector.py` | ⏳ |
| P0 | `_entanglement_map.py` | ⏳ |
| P0 | `_psi_core.py` (ψ_core) | ⏳ |
| P1 | `_psi_align.py` ~ `_psi_predict.py` (12개) | ⏳ |

---

## 📜 지시명령어 (Instructions)

### 1. 기본 규칙

```
1. 모든 코드는 Python 3.10.12 호환
2. 에러 메시지는 반드시 [파일경로:L라인번호] 형식 포함
3. docstring은 한국어로 작성
4. numpy 사용 시 type hints 포함
5. 테스트 가능한 구조로 설계
```

### 2. 파동함수 구현 시 참조

```python
# 모든 파동함수는 BaseWavefunction 상속
from wavefunctions._base import BaseWavefunction, WavefunctionResult

class PsiXxx(BaseWavefunction):
    def __init__(self):
        super().__init__("psi_xxx")
    
    def calculate(self, student_data: Dict[str, Any]) -> WavefunctionResult:
        # 1. 입력 검증
        self.validate_input(data, required_keys)
        # 2. 계산
        # 3. 결과 반환
        return WavefunctionResult(...)
```

### 3. 최신 정보 검색 요청

다음 주제에 대해 **최신 베스트 프랙티스**를 검색해주세요:

1. **Python dataclass + numpy 조합** 최적 패턴 (2024-2025)
2. **64차원 확률 벡터** 효율적 구현 방법
3. **희소 행렬 (sparse matrix)** 22×22 크기에서 최적화
4. **실시간 계산 (20초 주기)** Python 성능 최적화
5. **pytest** 대규모 수치 계산 테스트 패턴

---

## 🚀 Phase 1 구현 프롬프트

### 프롬프트 1: StudentStateVector 구현

```
다음 요구사항으로 64차원 StudentStateVector를 구현해주세요:

요구사항:
1. 64개 차원 (인지 16 + 정서 16 + 행동 16 + 컨텍스트 16)
2. 각 차원은 0.0 ~ 1.0 범위의 확률값
3. numpy 기반 효율적 연산
4. JSON 직렬화/역직렬화 지원
5. PHP와 호환되는 형식

참조: quantum-orchestration-design.md §3.1

차원 정의:
- 인지: concept_mastery, procedural_fluency, cognitive_load, ...
- 정서: motivation, self_efficacy, confidence, anxiety, ...
- 행동: engagement_behavior, persistence, help_seeking, ...
- 컨텍스트: time_pressure, social_context, teacher_support, ...

최신 Python dataclass + numpy 조합 패턴을 검색하여 적용해주세요.
```

### 프롬프트 2: EntanglementMap 구현

```
22×22 에이전트 얽힘 맵을 구현해주세요:

요구사항:
1. 22개 에이전트 간 상관관계 표현
2. 양의 상관 (동시 활성화) / 음의 상관 (상호 억제)
3. 위상(phase) 정보 포함 (0 ~ 2π)
4. 희소 행렬로 메모리/연산 최적화
5. 안정적인 엣지 동결 기능

참조: quantum-orchestration-design.md §3.2

핵심 얽힘 관계:
- (5, 8, 0.9): 학습감정→평온도 강한 양의 상관
- (13, 8, -0.4): 학습이탈↔평온도 음의 상관
- (20, 21, 0.95): 개입준비→개입실행 순차

scipy.sparse를 활용한 최적 구현 패턴을 검색해주세요.
```

### 프롬프트 3: ψ_core 구현

```
핵심 3상태 파동함수 ψ_core를 구현해주세요:

수식:
|ψ_core⟩ = α|Correct⟩ + β|Misconception⟩ + γ|Confusion⟩

α + β + γ = 1 (정규화)

입력 데이터:
- correct_rate: 정답률 (0.0 ~ 1.0)
- misconception_score: 오개념 점수
- hesitation_time: 망설임 시간 (초)
- concept_mastery: 개념 이해도
- revision_count: 수정 횟수
- anxiety_level: 불안 수준

계산 공식:
α = normalize(correct_rate*0.4 + concept_mastery*0.4 + teacher_confirm*0.2)
β = normalize(misconception_score*0.5 + error_pattern_match*0.3 + feedback_negative*0.2)
γ = normalize(hesitation_index*0.4 + revision_index*0.3 + anxiety_level*0.3)

BaseWavefunction을 상속하여 구현하고,
단위 테스트도 함께 작성해주세요.
```

### 프롬프트 4: 나머지 12종 파동함수

```
다음 12종 파동함수를 순차적으로 구현해주세요:

| 파동함수 | 수식 | 핵심 입력 |
|---------|------|----------|
| ψ_align | Σ cos(θᵢ)/n | 목표 방향 벡터 |
| ψ_fluct | Σ (Δbehavior)² | 시도/수정 횟수 |
| ψ_tunnel | exp(-B/E_cog) | 난이도, 에너지 |
| ψ_wm | exp(-t/τ) | 세션 시간 |
| ψ_affect | [μ, ν, ξ] | 침착도, 불안 |
| ψ_routine | R_daily + R_weekly + R_long | 루틴 준수율 |
| ψ_engage | [p, q, r] | 집중/이탈 시간 |
| ψ_concept | Σ entangle(i,j) | 개념 맵 |
| ψ_cascade | α₁·α₂·...·exp(-Δt/k) | 연속 정답률 |
| ψ_meta | [s, t] | 자기 평가 |
| ψ_context | Σ contextᵢ·wᵢ | 환경 변수 |
| ψ_predict | α·dα/dt·Align | α 시계열 |

각 파동함수에 대해:
1. 상세 수식을 quantum-learning-model.md에서 확인
2. 데이터 소스를 wavefunction-agent-mapping.md에서 확인
3. 단위 테스트 작성
```

---

## ⚠️ 주의사항 (Critical Issues)

구현 시 다음 17개 문제를 고려해주세요 (quantum-ide-critical-issues.md 참조):

### 타이밍 문제

```python
# #01 시간 스케일 불일치 해결
class TemporalNormalizer:
    windows = {'immediate': 5, 'short_term': 60, 'long_term': 3600}
```

### 계산 비용

```python
# #06 희소 행렬 사용
class SparseEntanglementMap:
    def __init__(self):
        self.sparse_matrix = {}  # {(i,j): weight}
        self.frozen_edges = set()  # 안정적 엣지 동결
```

### 파동함수 불안정

```python
# #05 순환 오류 방지
max_mutual_influence = 0.3  # 상호 영향도 상한
```

---

## 📦 산출물 형식

각 구현에 대해 다음 형식으로 제출:

```
1. 소스 코드 (src/xxx/_yyy.py)
2. 단위 테스트 (tests/test_xxx.py)
3. 사용 예시
4. 성능 고려사항 (있다면)
```

---

## 🔗 참조 문서 경로

```
quantum modeling/
├── 00-INDEX.md                    # 문서 허브
├── IMPLEMENTATION_GUIDE.md        # 구현 가이드 (상세)
├── quantum-learning-model.md      # 이론 (수식)
├── quantum-orchestration-design.md # 설계 (코드 구조)
├── wavefunction-agent-mapping.md  # 매핑 (데이터 소스)
├── quantum-ide-critical-issues.md # 문제점 (주의사항)
└── src/wavefunctions/_base.py     # 기본 클래스 (이미 구현됨)
```

---

## 🎬 시작하기

1. 먼저 **최신 Python 베스트 프랙티스**를 검색해주세요
2. **StudentStateVector**부터 구현 시작
3. 각 파동함수 구현 후 **테스트 실행**으로 검증
4. **성능 측정** 후 필요시 최적화

준비되면 "Phase 1 시작"이라고 말씀해주세요!

