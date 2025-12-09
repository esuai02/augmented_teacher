# 🛠️ Quantum Modeling 구현 가이드

> **주니어 개발자를 위한 단계별 구현 안내서**

**버전**: 1.0  
**작성일**: 2025-12-09  
**PRD 참조**: [0005-prd-quantum-modeling-completion.md](../../../tasks/0005-prd-quantum-modeling-completion.md)

---

## 📚 관련 문서

| 문서 | 역할 | 언제 참조? |
|------|------|----------|
| [00-INDEX.md](./00-INDEX.md) | 문서 허브 | 처음 시작할 때 |
| [SYSTEM_STATUS.yaml](./SYSTEM_STATUS.yaml) | SSOT | 현재 구현 상태 확인 |
| [quantum-learning-model.md](./quantum-learning-model.md) | 이론 기반 | 파동함수 수식 이해 |
| [quantum-orchestration-design.md](./quantum-orchestration-design.md) | 시스템 설계 | 코드 구조 이해 |
| [wavefunction-agent-mapping.md](./wavefunction-agent-mapping.md) | 매핑 규칙 | 데이터 소스 확인 |
| [quantum-ide-critical-issues.md](./quantum-ide-critical-issues.md) | 문제점 | 구현 시 주의사항 |

---

## 1. 시스템 개요

### 1.1 목표

학생의 학습 상태를 **양자역학 개념**으로 모델링하여:
1. **13종 파동함수**로 학생 상태를 정밀 측정
2. **IDE 7단계 파이프라인**으로 개입 여부 자동 결정
3. **Brain/Mind/Mouth**로 실시간 AI 튜터 응답 생성

### 1.2 기술 스택

| 레이어 | 기술 | 역할 |
|--------|------|------|
| 서버 | PHP 7.1.9 + MySQL 5.7 | Moodle 통합, 웹 API |
| 양자 모델링 | Python 3.10.12 | 파동함수 계산, Hamiltonian |
| 통신 | REST API | PHP ↔ Python 데이터 교환 |

### 1.3 핵심 개념 요약

```
┌─────────────────────────────────────────────────────────────────┐
│                    핵심 개념 요약                                │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  |ψ⟩ = α|Correct⟩ + β|Misconception⟩ + γ|Confusion⟩            │
│                                                                  │
│  α: 정답 확률 (높을수록 좋음)                                     │
│  β: 오개념 확률 (낮아야 좋음)                                     │
│  γ: 혼란 확률 (낮아야 좋음)                                       │
│                                                                  │
│  CP(t) = α(t) · dα/dt · Align · (1 - γ)                         │
│  → 붕괴 확률 (CP > 0.8이면 "아하!" 순간 임박)                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## 2. 환경 설정

### 2.1 PHP 설정 (Moodle 통합)

모든 PHP 파일은 반드시 다음 코드로 시작:

```php
<?php
// [quantum modeling/php/xxx.php:L1] Moodle 통합 필수
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql(
    "SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"
);
$role = $userrole->data;
```

### 2.2 Python 설정

```bash
# Python 3.10.12 사용
python3 --version

# 필요 패키지 (requirements.txt)
numpy>=1.21.0
scipy>=1.7.0
```

### 2.3 PHP ↔ Python 통신

**방법 1: REST API (권장)**

```php
// PHP에서 Python 호출
function call_quantum_api($endpoint, $data) {
    $url = 'http://localhost:5000/api/' . $endpoint;
    
    $options = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($data),
            'timeout' => 10
        ]
    ];
    
    $context = stream_context_create($options);
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        error_log("[quantum modeling/php/api.php:L" . __LINE__ . "] Python API 호출 실패: $url");
        return null;
    }
    
    return json_decode($response, true);
}
```

**방법 2: subprocess (간단한 경우)**

```php
// PHP에서 Python 직접 호출
function call_python_script($script, $args) {
    $cmd = "python3 " . escapeshellarg($script) . " " . escapeshellarg(json_encode($args));
    $output = shell_exec($cmd);
    return json_decode($output, true);
}
```

---

## 3. 파동함수 구현 가이드

### 3.1 파일 구조

```
src/wavefunctions/
├── __init__.py
├── _base.py              # 기본 클래스
├── _psi_core.py          # ψ_core (핵심 3상태)
├── _psi_align.py         # ψ_align (정렬)
├── _psi_fluct.py         # ψ_fluct (요동)
├── _psi_tunnel.py        # ψ_tunnel (터널링)
├── _psi_wm.py            # ψ_WM (작업기억)
├── _psi_affect.py        # ψ_affect (정서)
├── _psi_routine.py       # ψ_routine (루틴)
├── _psi_engage.py        # ψ_engage (이탈/복귀)
├── _psi_concept.py       # ψ_concept (개념 구조)
├── _psi_cascade.py       # ψ_cascade (연쇄 붕괴)
├── _psi_meta.py          # ψ_meta (메타인지)
├── _psi_context.py       # ψ_context (상황문맥)
└── _psi_predict.py       # ψ_predict (예측)
```

### 3.2 기본 클래스

```python
# src/wavefunctions/_base.py

from abc import ABC, abstractmethod
from dataclasses import dataclass
from typing import Dict, Any
import numpy as np

@dataclass
class WavefunctionResult:
    """파동함수 계산 결과"""
    name: str                    # 파동함수 이름 (예: "psi_core")
    value: np.ndarray           # 계산된 값 (벡터)
    confidence: float           # 계산 신뢰도 (0.0 ~ 1.0)
    timestamp: str              # 계산 시점
    metadata: Dict[str, Any]    # 추가 메타데이터


class BaseWavefunction(ABC):
    """모든 파동함수의 기본 클래스"""
    
    def __init__(self, name: str):
        self.name = name
    
    @abstractmethod
    def calculate(self, student_data: Dict[str, Any]) -> WavefunctionResult:
        """
        파동함수 계산
        
        Args:
            student_data: 학생 데이터 (에이전트 출력값)
        
        Returns:
            WavefunctionResult
        """
        pass
    
    def validate_input(self, data: Dict[str, Any], required_keys: list) -> bool:
        """입력 데이터 검증"""
        for key in required_keys:
            if key not in data:
                raise ValueError(
                    f"[quantum modeling/src/wavefunctions/{self.name}.py] "
                    f"필수 키 누락: {key}"
                )
        return True
```

### 3.3 ψ_core 구현 예시

```python
# src/wavefunctions/_psi_core.py

from ._base import BaseWavefunction, WavefunctionResult
from typing import Dict, Any
from datetime import datetime
import numpy as np

class PsiCore(BaseWavefunction):
    """
    핵심 3상태 파동함수
    
    |ψ_core⟩ = α|Correct⟩ + β|Misconception⟩ + γ|Confusion⟩
    
    참조: quantum-learning-model.md > Part II > §4.1
    데이터 소스: wavefunction-agent-mapping.md > §3.1
    """
    
    def __init__(self):
        super().__init__("psi_core")
        
        # Primary 에이전트: 04, 10, 11, 15
        # Secondary 에이전트: 05, 06, 08, 14, 21
        self.primary_agents = [4, 10, 11, 15]
        self.secondary_agents = [5, 6, 8, 14, 21]
    
    def calculate(self, student_data: Dict[str, Any]) -> WavefunctionResult:
        """
        α, β, γ 계산
        
        Args:
            student_data: {
                'correct_rate': float,      # 정답률 (0.0 ~ 1.0)
                'misconception_score': float,  # 오개념 점수
                'hesitation_time': float,   # 망설임 시간 (초)
                'revision_count': int,      # 수정 횟수
                'concept_mastery': float,   # 개념 이해도
                'error_pattern_match': float  # 오답 패턴 일치도
            }
        
        Returns:
            WavefunctionResult with value = [α, β, γ]
        """
        try:
            # 입력 검증
            required = ['correct_rate', 'misconception_score', 'hesitation_time']
            self.validate_input(student_data, required)
            
            # α (정답 확률) 계산
            correct_rate = student_data.get('correct_rate', 0.5)
            concept_mastery = student_data.get('concept_mastery', 0.5)
            teacher_confirm = student_data.get('teacher_confirm', 0.5)
            
            alpha = self._normalize(
                correct_rate * 0.4 + 
                concept_mastery * 0.4 + 
                teacher_confirm * 0.2
            )
            
            # β (오개념 확률) 계산
            misconception_score = student_data.get('misconception_score', 0.0)
            error_pattern_match = student_data.get('error_pattern_match', 0.0)
            feedback_negative = student_data.get('feedback_negative', 0.0)
            
            beta = self._normalize(
                misconception_score * 0.5 + 
                error_pattern_match * 0.3 + 
                feedback_negative * 0.2
            )
            
            # γ (혼란 확률) 계산
            hesitation_time = student_data.get('hesitation_time', 0.0)
            revision_count = student_data.get('revision_count', 0)
            anxiety_level = student_data.get('anxiety_level', 0.0)
            
            # 정규화된 값 사용
            hesitation_index = min(hesitation_time / 60.0, 1.0)  # 60초 기준
            revision_index = min(revision_count / 5.0, 1.0)     # 5회 기준
            
            gamma = self._normalize(
                hesitation_index * 0.4 + 
                revision_index * 0.3 + 
                anxiety_level * 0.3
            )
            
            # 정규화 (α + β + γ = 1)
            total = alpha + beta + gamma
            if total > 0:
                alpha /= total
                beta /= total
                gamma /= total
            else:
                alpha, beta, gamma = 0.33, 0.33, 0.34
            
            # 신뢰도 계산 (데이터 완전성 기반)
            confidence = self._calculate_confidence(student_data)
            
            return WavefunctionResult(
                name=self.name,
                value=np.array([alpha, beta, gamma]),
                confidence=confidence,
                timestamp=datetime.now().isoformat(),
                metadata={
                    'alpha': alpha,
                    'beta': beta,
                    'gamma': gamma,
                    'components': {
                        'correct_rate': correct_rate,
                        'misconception_score': misconception_score,
                        'hesitation_index': hesitation_index
                    }
                }
            )
            
        except Exception as e:
            raise RuntimeError(
                f"[quantum modeling/src/wavefunctions/_psi_core.py:L{self._get_line()}] "
                f"ψ_core 계산 실패: {str(e)}"
            )
    
    def _normalize(self, value: float) -> float:
        """값을 0.0 ~ 1.0 범위로 정규화"""
        return max(0.0, min(1.0, value))
    
    def _calculate_confidence(self, data: Dict[str, Any]) -> float:
        """데이터 완전성 기반 신뢰도 계산"""
        required_keys = ['correct_rate', 'misconception_score', 'hesitation_time']
        present = sum(1 for k in required_keys if k in data and data[k] is not None)
        return present / len(required_keys)
    
    def _get_line(self) -> int:
        """현재 라인 번호 반환"""
        import sys
        return sys._getframe(1).f_lineno
```

### 3.4 모든 파동함수 공식

| 파동함수 | 수식 | 데이터 소스 |
|---------|------|------------|
| **ψ_core** | `[α, β, γ]` normalized | 정답률, 오개념, 망설임 |
| **ψ_align** | `Σᵢ cos(θᵢ) / n` | 목표 방향 벡터 |
| **ψ_fluct** | `Σ (Δbehavior)²` | 시도/수정 횟수 |
| **ψ_tunnel** | `exp(-B / E_cog)` | 난이도, 인지 에너지 |
| **ψ_WM** | `exp(-t / τ)` | 세션 시간, 휴식 |
| **ψ_affect** | `[μ, ν, ξ]` (Calm, Tension, Overload) | 침착도, 불안 |
| **ψ_routine** | `R_daily + R_weekly + R_long` | 루틴 준수율 |
| **ψ_engage** | `[p, q, r]` (Focus, Drift, Drop) | 집중/이탈 시간 |
| **ψ_concept** | `Σ entangle(i,j)` | 개념 맵 |
| **ψ_cascade** | `α₁·α₂·α₃·exp(-Δt/k)` | 연속 정답률 |
| **ψ_meta** | `[s, t]` (CanDo, Uncertain) | 자기 평가 |
| **ψ_context** | `Σ contextᵢ·wᵢ` | 학습 환경 |
| **ψ_predict** | `α · dα/dt · Align` | α 시계열 |

---

## 4. IDE 구현 가이드

### 4.1 IDE 7단계 파이프라인

```
┌─────────────────────────────────────────────────────────────────┐
│                     IDE 7단계 파이프라인                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  [1] Trigger 식별                                                │
│      └─ 22개 에이전트 중 누가 문제 상황 감지?                      │
│           ↓                                                      │
│  [2] BCE (경계조건) 체크                                          │
│      └─ 이전 개입? 현재 활동? 선호도? 수용성?                      │
│           ↓ (PASS)                                               │
│  [3] 시나리오 후보군 생성                                         │
│      └─ 개입/비개입/미세개입 시나리오 목록                         │
│           ↓                                                      │
│  [4] 우선순위 결정                                                │
│      └─ Priority = α₁×Severity + α₂×Timing + ...                │
│           ↓                                                      │
│  [5] 필수 조건 체크                                               │
│      └─ 각 시나리오의 전제 조건 충족?                              │
│           ↓                                                      │
│  [6] 최종 선택                                                    │
│      └─ 가장 높은 우선순위 + 조건 충족 시나리오                    │
│           ↓                                                      │
│  [7] 개입 실행 (Mind → Mouth)                                    │
│      └─ 대사 생성 → (선택) TTS → 학생에게 전달                    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 파일 구조

```
src/ide/
├── __init__.py
├── _ide_trigger.py         # Step 1: 트리거 감지
├── _ide_boundary.py        # Step 2: 경계조건 체크
├── _ide_scenario.py        # Step 3: 시나리오 생성
├── _ide_priority.py        # Step 4: 우선순위 계산
├── _ide_prerequisite.py    # Step 5: 필수조건 체크
├── _ide_selector.py        # Step 6: 최종 선택
├── _ide_executor.py        # Step 7: 개입 실행
└── _intervention_decision_engine.py  # 통합 엔진
```

### 4.3 통합 엔진 예시

```python
# src/ide/_intervention_decision_engine.py

from typing import Optional, Dict, Any
from ._ide_trigger import AgentTrigger
from ._ide_boundary import BoundaryConditionEngine
from ._ide_scenario import ScenarioGenerator
from ._ide_priority import PriorityCalculator
from ._ide_prerequisite import PrerequisiteChecker
from ._ide_selector import InterventionSelector
from ._ide_executor import InterventionExecutor

class InterventionDecisionEngine:
    """
    개입 의사결정 엔진 (IDE) - 메인 클래스
    
    참조: quantum-orchestration-design.md > §5.4
    문제점: quantum-ide-critical-issues.md
    """
    
    def __init__(self):
        self.trigger = AgentTrigger()
        self.bce = BoundaryConditionEngine()
        self.generator = ScenarioGenerator()
        self.priority_calc = PriorityCalculator()
        self.prereq_checker = PrerequisiteChecker()
        self.selector = InterventionSelector()
        self.executor = InterventionExecutor()
    
    def process(
        self,
        student_id: int,
        trigger_agent: int,
        student_state: Dict[str, Any],
        wavefunctions: Dict[str, Any]
    ) -> Optional[Dict[str, Any]]:
        """
        IDE 전체 파이프라인 실행
        
        Args:
            student_id: 학생 ID
            trigger_agent: 트리거 발생 에이전트 (1~22)
            student_state: 학생 상태 (64차원)
            wavefunctions: 13종 파동함수 계산 결과
        
        Returns:
            성공 시: 개입 실행 결과
            실패 시: None 또는 {"status": "blocked", "reason": ...}
        """
        try:
            # STEP 1: Trigger 확인 (이미 발생)
            trigger_info = self.trigger.get_trigger_info(trigger_agent)
            
            # STEP 2: BCE 체크
            bce_passed, bce_reason = self.bce.check_all(
                student_id, trigger_agent
            )
            if not bce_passed:
                return {"status": "blocked", "reason": bce_reason}
            
            # STEP 3: 시나리오 후보군 생성
            candidates = self.generator.generate(
                trigger_agent, student_state
            )
            if not candidates:
                return {"status": "no_candidates"}
            
            # STEP 4: 우선순위 계산
            for candidate in candidates:
                candidate['priority'] = self.priority_calc.calculate(
                    candidate, student_state, wavefunctions
                )
            candidates.sort(key=lambda x: -x['priority'])
            
            # STEP 5 & 6: 필수조건 체크 + 최종 선택
            decision = self.selector.select(
                candidates, student_state, wavefunctions
            )
            if decision is None:
                return {"status": "no_valid_scenario"}
            
            # STEP 7: 개입 실행
            result = self.executor.execute(decision, student_id)
            result["status"] = "executed"
            
            return result
            
        except Exception as e:
            return {
                "status": "error",
                "error": f"[quantum modeling/src/ide/_intervention_decision_engine.py] {str(e)}"
            }
```

---

## 5. 테스트 가이드

### 5.1 단위 테스트 구조

```
tests/
├── test_wavefunctions.py    # 13종 파동함수 테스트
├── test_ide.py              # IDE 7단계 테스트
├── test_state_vector.py     # 64차원 StateVector 테스트
└── test_integration.py      # 통합 테스트
```

### 5.2 파동함수 테스트 예시

```python
# tests/test_wavefunctions.py

import pytest
import numpy as np
from src.wavefunctions._psi_core import PsiCore

class TestPsiCore:
    """ψ_core 단위 테스트"""
    
    def setup_method(self):
        self.psi = PsiCore()
    
    def test_basic_calculation(self):
        """기본 계산 테스트"""
        data = {
            'correct_rate': 0.8,
            'misconception_score': 0.1,
            'hesitation_time': 5.0,
            'concept_mastery': 0.7,
            'revision_count': 1
        }
        
        result = self.psi.calculate(data)
        
        # 결과 검증
        assert result.name == "psi_core"
        assert len(result.value) == 3  # α, β, γ
        assert np.isclose(sum(result.value), 1.0)  # 합 = 1
        assert all(0 <= v <= 1 for v in result.value)  # 범위 검증
    
    def test_high_correct_rate(self):
        """높은 정답률 → α ↑"""
        data = {
            'correct_rate': 0.95,
            'misconception_score': 0.0,
            'hesitation_time': 1.0
        }
        
        result = self.psi.calculate(data)
        alpha = result.metadata['alpha']
        
        assert alpha > 0.6  # α가 높아야 함
    
    def test_high_misconception(self):
        """높은 오개념 → β ↑"""
        data = {
            'correct_rate': 0.3,
            'misconception_score': 0.8,
            'hesitation_time': 2.0,
            'error_pattern_match': 0.7
        }
        
        result = self.psi.calculate(data)
        beta = result.metadata['beta']
        
        assert beta > 0.3  # β가 높아야 함
    
    def test_missing_data(self):
        """필수 데이터 누락 시 에러"""
        data = {'correct_rate': 0.5}  # 필수 데이터 부족
        
        with pytest.raises(ValueError):
            self.psi.calculate(data)
```

### 5.3 테스트 실행

```bash
# 전체 테스트
pytest tests/ -v

# 커버리지 포함
pytest tests/ --cov=src --cov-report=html

# 특정 테스트만
pytest tests/test_wavefunctions.py -v
```

---

## 6. 에러 처리 규칙

### 6.1 에러 메시지 형식

모든 에러 메시지는 **파일 경로 + 라인 번호**를 포함:

```python
# Python
raise ValueError(
    f"[quantum modeling/src/wavefunctions/_psi_core.py:L{lineno}] "
    f"에러 설명: {details}"
)
```

```php
// PHP
error_log("[quantum modeling/php/api.php:L" . __LINE__ . "] 에러 설명: $details");
```

### 6.2 로깅 레벨

| 레벨 | 용도 | 예시 |
|------|------|------|
| DEBUG | 개발 중 상세 정보 | 파동함수 계산 과정 |
| INFO | 정상 동작 기록 | 개입 실행 완료 |
| WARNING | 주의 필요 상황 | BCE 조건 근접 |
| ERROR | 오류 발생 | 계산 실패 |
| CRITICAL | 시스템 장애 | DB 연결 실패 |

---

## 7. 체크리스트

### 7.1 Phase 1 완료 조건

- [ ] `_student_state_vector.py` 64차원 구현
- [ ] `_entanglement_map.py` 22×22 구현
- [ ] 13종 파동함수 모두 구현
  - [ ] `_psi_core.py`
  - [ ] `_psi_align.py`
  - [ ] `_psi_fluct.py`
  - [ ] `_psi_tunnel.py`
  - [ ] `_psi_wm.py`
  - [ ] `_psi_affect.py`
  - [ ] `_psi_routine.py`
  - [ ] `_psi_engage.py`
  - [ ] `_psi_concept.py`
  - [ ] `_psi_cascade.py`
  - [ ] `_psi_meta.py`
  - [ ] `_psi_context.py`
  - [ ] `_psi_predict.py`
- [ ] 단위 테스트 통과

### 7.2 Phase 2 완료 조건

- [ ] IDE 7단계 컴포넌트 모두 구현
  - [ ] `_ide_trigger.py`
  - [ ] `_ide_boundary.py`
  - [ ] `_ide_scenario.py`
  - [ ] `_ide_priority.py`
  - [ ] `_ide_prerequisite.py`
  - [ ] `_ide_selector.py`
  - [ ] `_ide_executor.py`
- [ ] `_intervention_decision_engine.py` 통합
- [ ] 단위 테스트 통과

### 7.3 Phase 3 완료 조건

- [ ] Mind Layer (LLM 연동) 구현
- [ ] 대시보드 실시간 연동
- [ ] 테스트 커버리지 80% 이상
- [ ] 실제 학생 1명 이상 테스트 완료

---

## 8. FAQ

### Q1: PHP와 Python 중 어디에 구현해야 하나요?

- **파동함수 계산**: Python (numpy 필요)
- **데이터 조회**: PHP (Moodle DB 직접 접근)
- **API 엔드포인트**: PHP (웹 서버)
- **IDE 로직**: Python (복잡한 계산) → PHP (호출)

### Q2: 20초 주기는 어떻게 구현하나요?

```php
// PHP: cron 또는 JavaScript setInterval
// JavaScript (대시보드)
setInterval(async () => {
    const result = await fetch('/api/quantum/calculate', {
        method: 'POST',
        body: JSON.stringify({ student_id: studentId })
    });
    updateDashboard(await result.json());
}, 20000);  // 20초
```

### Q3: 테스트 데이터는 어떻게 준비하나요?

```python
# tests/fixtures.py

SAMPLE_STUDENT_DATA = {
    'correct_rate': 0.7,
    'misconception_score': 0.2,
    'hesitation_time': 10.0,
    'concept_mastery': 0.6,
    'revision_count': 2,
    'anxiety_level': 0.3
}

SAMPLE_WAVEFUNCTIONS = {
    'psi_core': {'alpha': 0.6, 'beta': 0.25, 'gamma': 0.15},
    'psi_affect': {'calm': 0.7, 'tension': 0.2, 'overload': 0.1},
    # ... 13종 모두
}
```

---

## 📝 변경 이력

| 날짜 | 버전 | 변경 내용 |
|------|------|----------|
| 2025-12-09 | 1.0 | 초기 문서 작성 |

---

*질문이 있으면 담당자에게 문의하세요.*

