# PRD: Quantum Modeling 시스템 완성

> **실시간 AI 튜터를 위한 양자 모델링 시스템의 문서화, 구현, 검증 완성**

**문서 번호**: 0005  
**작성일**: 2025-12-09  
**버전**: 1.0  
**상태**: Draft  
**담당 영역**: `Holarchy/0 Docs/quantum modeling/`

---

## 1. Introduction / Overview

### 1.1 배경

Quantum Modeling 시스템은 학생의 학습 상태를 양자역학 개념(중첩, 붕괴, 얽힘)으로 모델링하여 **최적의 교육적 개입 시점과 방식**을 자동으로 결정하는 실시간 AI 튜터의 핵심 엔진입니다.

현재 이론적 설계는 완료되었으나:
- 문서 간 연결성이 약함
- 13종 파동함수가 코드로 구현되지 않음
- IDE(개입 의사결정 엔진)가 설계만 있고 구현이 없음
- 실제 학생 대상 검증이 이루어지지 않음

### 1.2 해결하려는 문제

| 문제 | 현재 상태 | 목표 상태 |
|------|----------|----------|
| 문서 파편화 | 6개 문서가 독립적 | 상호 참조 완전 연결 |
| 파동함수 미구현 | 이론만 존재 | 13종 모두 코드화 |
| IDE 미구현 | 설계 문서만 존재 | 7단계 파이프라인 동작 |
| 64차원 StateVector | 8차원만 구현 | 64차원 완전 구현 |
| Brain/Mind/Mouth | 컨셉만 존재 | 실시간 파이프라인 동작 |
| 검증 부재 | 테스트 없음 | 단위 테스트 + 실제 학생 테스트 |

### 1.3 목표 사용자

**Primary**: 교육자 (교사, 튜터)
- 실제 학생에게 실시간 AI 튜터링을 적용하려는 사용자
- 학생 상태를 모니터링하고 개입 효과를 확인하려는 사용자

---

## 2. Goals

### 2.1 핵심 목표

| ID | 목표 | 측정 기준 | 우선순위 |
|:--:|------|----------|:--------:|
| G1 | 문서 간 완전한 연결성 확보 | 모든 문서 간 상호 참조 링크 100% | P0 |
| G2 | 13종 파동함수 코드 구현 | 모든 파동함수 계산 가능 | P0 |
| G3 | IDE 7단계 파이프라인 구현 | Trigger→Execute 전체 흐름 동작 | P0 |
| G4 | 64차원 StateVector 구현 | 현재 8차원 → 64차원 확장 | P1 |
| G5 | Brain/Mind/Mouth 파이프라인 | 실시간 판단→생성→출력 동작 | P1 |
| G6 | 17개 Critical 문제 해결 | quantum-ide-critical-issues.md의 모든 문제 해결 | P1 |
| G7 | 단위 테스트 커버리지 80% | 핵심 모듈 테스트 완료 | P0 |
| G8 | 실제 학생 1명 이상 테스트 | End-to-End 검증 완료 | P1 |
| G9 | 교사 대시보드 실시간 모니터링 | α, β, γ 값 실시간 표시 | P1 |

### 2.2 기술 스택 제약

| 레이어 | 기술 | 용도 |
|--------|------|------|
| 서버 | PHP 7.1.9 + MySQL 5.7 | 기존 Moodle 통합, 웹 API |
| 양자 모델링 | Python 3.10.12 | 파동함수 계산, Hamiltonian 진화 |
| 실시간 통신 | PHP ↔ Python | REST API 또는 subprocess |

---

## 3. User Stories

### 3.1 교육자(교사) 관점

```
US-01: 실시간 학생 상태 모니터링
As a 교사
I want to 학생의 α(정답 확률), β(오개념), γ(혼란) 값을 실시간으로 보기
So that 개입이 필요한 학생을 빠르게 파악할 수 있다

US-02: 자동 개입 알림
As a 교사
I want to 시스템이 자동으로 "개입 필요" 알림을 받기
So that 적절한 타이밍에 학생을 도울 수 있다

US-03: 개입 효과 확인
As a 교사
I want to 개입 전후의 파동함수 변화를 보기
So that 개입이 효과적이었는지 평가할 수 있다

US-04: 학생별 패턴 분석
As a 교사
I want to 학생의 장기 파동함수 패턴을 보기
So that 맞춤형 학습 전략을 세울 수 있다
```

### 3.2 시스템 관점

```
US-05: 파동함수 실시간 계산
As a 시스템
I want to 20초 단위로 13종 파동함수를 계산하기
So that 학생 상태를 정밀하게 추적할 수 있다

US-06: 개입 의사결정 자동화
As a 시스템
I want to IDE 7단계 파이프라인으로 개입 여부를 자동 결정하기
So that 교사 개입 없이도 적시 개입이 가능하다

US-07: Brain/Mind/Mouth 파이프라인
As a 시스템
I want to 판단(Brain) → 생성(Mind) → 출력(Mouth) 파이프라인 실행하기
So that 자연스러운 AI 튜터 응답을 제공할 수 있다
```

---

## 4. Functional Requirements

### 4.1 문서 완성 (FR-DOC)

| ID | 요구사항 | 상세 |
|:--:|---------|------|
| FR-DOC-01 | 문서 간 상호 참조 링크 추가 | 모든 문서에서 관련 문서로 하이퍼링크 연결 |
| FR-DOC-02 | 각 파동함수별 계산 공식 명확화 | quantum-learning-model.md에 수식 + 코드 예시 |
| FR-DOC-03 | IDE 7단계별 입출력 명세 추가 | quantum-orchestration-design.md 5.4절 보강 |
| FR-DOC-04 | SYSTEM_STATUS.yaml 최신 상태 반영 | 구현 완료 시 status 업데이트 |
| FR-DOC-05 | 구현 가이드 문서 신규 작성 | `IMPLEMENTATION_GUIDE.md` 생성 |

### 4.2 파동함수 구현 (FR-PSI)

| ID | 파동함수 | 구현 파일 | 입력 데이터 |
|:--:|---------|----------|------------|
| FR-PSI-01 | ψ_core (핵심 3상태) | `_psi_core.py` | 정답률, 오답 패턴, 망설임 시간 |
| FR-PSI-02 | ψ_align (정렬) | `_psi_align.py` | 목표 달성률, 방향성 벡터 |
| FR-PSI-03 | ψ_fluct (요동) | `_psi_fluct.py` | 시도 횟수, 수정 횟수, 탐색 폭 |
| FR-PSI-04 | ψ_tunnel (터널링) | `_psi_tunnel.py` | 난이도, 인지 에너지 |
| FR-PSI-05 | ψ_WM (작업기억) | `_psi_wm.py` | 세션 길이, 휴식 패턴 |
| FR-PSI-06 | ψ_affect (정서) | `_psi_affect.py` | 침착도, 불안 지수 |
| FR-PSI-07 | ψ_routine (루틴) | `_psi_routine.py` | 일간/주간/장기 루틴 준수율 |
| FR-PSI-08 | ψ_engage (이탈/복귀) | `_psi_engage.py` | 집중 시간, 복귀율 |
| FR-PSI-09 | ψ_concept (개념 구조) | `_psi_concept.py` | 개념 맵, 전이 패턴 |
| FR-PSI-10 | ψ_cascade (연쇄 붕괴) | `_psi_cascade.py` | 연속 정답률, 단원 진행도 |
| FR-PSI-11 | ψ_meta (메타인지) | `_psi_meta.py` | 자기 평가, 목표 설정 패턴 |
| FR-PSI-12 | ψ_context (상황문맥) | `_psi_context.py` | 학습 환경, 시간대, 시험 근접도 |
| FR-PSI-13 | ψ_predict (예측) | `_psi_predict.py` | α 시계열, 정렬도 변화 |

### 4.3 IDE 구현 (FR-IDE)

| ID | 컴포넌트 | 구현 파일 | 역할 |
|:--:|---------|----------|------|
| FR-IDE-01 | AgentTrigger | `_ide_trigger.py` / `.php` | 22개 에이전트 트리거 감지 |
| FR-IDE-02 | BoundaryConditionEngine | `_ide_boundary.py` / `.php` | 4개 경계조건 검증 |
| FR-IDE-03 | ScenarioGenerator | `_ide_scenario.py` / `.php` | 시나리오 후보군 생성 |
| FR-IDE-04 | PriorityCalculator | `_ide_priority.py` / `.php` | 가중치 기반 우선순위 |
| FR-IDE-05 | PrerequisiteChecker | `_ide_prerequisite.py` / `.php` | 필수 조건 검증 |
| FR-IDE-06 | InterventionSelector | `_ide_selector.py` / `.php` | 최종 시나리오 선택 |
| FR-IDE-07 | InterventionExecutor | `_ide_executor.py` / `.php` | Mind→Mouth 실행 |

### 4.4 64차원 StateVector (FR-SV)

| ID | 요구사항 | 상세 |
|:--:|---------|------|
| FR-SV-01 | StudentStateVector 64차원 확장 | 인지(16) + 정서(16) + 행동(16) + 컨텍스트(16) |
| FR-SV-02 | 8차원 → 64차원 마이그레이션 | 기존 8차원 데이터 호환성 유지 |
| FR-SV-03 | PHP/Python 상호 변환 | JSON 기반 직렬화/역직렬화 |

### 4.5 Brain/Mind/Mouth 파이프라인 (FR-BMM)

| ID | 레이어 | 구현 파일 | 역할 |
|:--:|--------|----------|------|
| FR-BMM-01 | Brain (판단) | `_brain_quantum.py` | 개입 여부 판단 (CP 기반) |
| FR-BMM-02 | Mind (생성) | `_mind_generator.py` | 대사/지문 생성 (LLM 연동) |
| FR-BMM-03 | Mouth (출력) | `_mouth_tts.py` | 동적 TTS (선택적) |
| FR-BMM-04 | 파이프라인 통합 | `_realtime_tutor.py` | Brain→Mind→Mouth 실시간 연결 |

### 4.6 Critical Issues 해결 (FR-ISSUE)

| ID | 문제 카테고리 | 해결 클래스 | 우선순위 |
|:--:|-------------|------------|:--------:|
| FR-ISSUE-01 | 타이밍 문제 | `TemporalNormalizer`, `InterventionTimingGuard` | P0 |
| FR-ISSUE-02 | 우선순위 충돌 | `PriorityResolver`, `ScenarioDeduplicator` | P0 |
| FR-ISSUE-03 | 계산 비용 | `SparseEntanglementMap`, `LightweightHamiltonian` | P1 |
| FR-ISSUE-04 | 과잉 개입 | `DriftDetectionCalibrator`, `AnomalyDetector` | P1 |
| FR-ISSUE-05 | 예측 실패 | `AffectScaleNormalizer`, `ReceptivityPredictor` | P1 |
| FR-ISSUE-06 | 파동함수 불안정 | `PreferenceStabilizer`, `WavefunctionStabilityChecker` | P1 |
| FR-ISSUE-07 | 데이터 매핑 | `VariableMapper`, `SoftBCE` | P2 |
| FR-ISSUE-08 | 시스템 충돌 | `InterventionCoordinator` | P0 |

### 4.7 테스트 및 검증 (FR-TEST)

| ID | 요구사항 | 상세 |
|:--:|---------|------|
| FR-TEST-01 | 파동함수 단위 테스트 | 13종 각각에 대해 입력→출력 검증 |
| FR-TEST-02 | IDE 단위 테스트 | 7단계 각 컴포넌트 개별 테스트 |
| FR-TEST-03 | 통합 테스트 | Brain→Mind→Mouth 전체 흐름 |
| FR-TEST-04 | 실제 학생 테스트 | 최소 1명 이상 E2E 검증 |
| FR-TEST-05 | 대시보드 시각화 테스트 | 실시간 α, β, γ 표시 확인 |

### 4.8 대시보드 연동 (FR-DASH)

| ID | 요구사항 | 상세 |
|:--:|---------|------|
| FR-DASH-01 | 실시간 파동함수 그래프 | 20초 단위 업데이트 |
| FR-DASH-02 | 개입 이력 표시 | 개입 시점, 유형, 결과 |
| FR-DASH-03 | CP(붕괴 확률) 경고 | CP > 0.8 시 알림 |
| FR-DASH-04 | 학생별 페르소나 표시 | Hyperia/Explorer/Sensitive/Drift |

---

## 5. Non-Goals (Out of Scope)

| 항목 | 제외 사유 |
|------|----------|
| TTS 음성 합성 완전 구현 | 초기 버전은 텍스트 출력에 집중, TTS는 선택적 |
| 모바일 앱 | 웹 기반 대시보드에 집중 |
| 다국어 지원 | 한국어 우선 |
| 실시간 화상/음성 통화 | 텍스트 기반 개입 우선 |
| 학부모 대시보드 | 교사 대시보드에 집중 |
| A/B 테스트 프레임워크 | 수동 검증으로 충분 |

---

## 6. Design Considerations

### 6.1 폴더 구조 (제안)

```
Holarchy/0 Docs/quantum modeling/
├── 00-INDEX.md                     # 진입점
├── SYSTEM_STATUS.yaml              # SSOT
├── IMPLEMENTATION_GUIDE.md         # [신규] 구현 가이드
│
├── theory/                         # [신규] 이론 문서
│   ├── quantum-learning-model.md   # 이동
│   └── wavefunction-agent-mapping.md  # 이동
│
├── design/                         # [신규] 설계 문서
│   ├── quantum-orchestration-design.md  # 이동
│   └── quantum-ide-critical-issues.md   # 이동
│
├── src/                            # [신규] Python 소스
│   ├── wavefunctions/              # 13종 파동함수
│   │   ├── __init__.py
│   │   ├── _psi_core.py
│   │   ├── _psi_align.py
│   │   └── ... (13개)
│   │
│   ├── ide/                        # IDE 컴포넌트
│   │   ├── __init__.py
│   │   ├── _ide_trigger.py
│   │   ├── _ide_boundary.py
│   │   └── ... (7개)
│   │
│   ├── state/                      # 상태 관리
│   │   ├── _student_state_vector.py  # 64차원
│   │   └── _entanglement_map.py
│   │
│   ├── pipeline/                   # Brain/Mind/Mouth
│   │   ├── _brain_quantum.py
│   │   ├── _mind_generator.py
│   │   └── _mouth_tts.py
│   │
│   └── utils/                      # 유틸리티
│       ├── _temporal_normalizer.py
│       └── _intervention_coordinator.py
│
├── php/                            # [신규] PHP 소스
│   ├── wavefunctions/
│   ├── ide/
│   └── api/
│
└── tests/                          # [신규] 테스트
    ├── test_wavefunctions.py
    ├── test_ide.py
    └── test_integration.py
```

### 6.2 데이터 흐름

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          DATA FLOW                                       │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  [Moodle DB]                                                             │
│      │                                                                   │
│      ▼                                                                   │
│  [PHP: 데이터 수집]                                                       │
│      │ - 침착도, 정답률, 풀이 패턴                                         │
│      │ - JSON 직렬화                                                     │
│      ▼                                                                   │
│  [Python: 파동함수 계산] ◄──── 20초 주기                                  │
│      │ - 13종 ψ 계산                                                     │
│      │ - α, β, γ 추정                                                    │
│      ▼                                                                   │
│  [Python: IDE 파이프라인]                                                 │
│      │ - Trigger → BCE → Scenario → Priority → Select                   │
│      ▼                                                                   │
│  [Python: Brain 판단]                                                    │
│      │ - CP(t) > threshold?                                              │
│      │ - 개입/비개입/미세개입                                             │
│      ▼                                                                   │
│  [Python/LLM: Mind 생성]                                                 │
│      │ - 대사/지문 생성                                                   │
│      ▼                                                                   │
│  [PHP: 결과 전달]                                                         │
│      │ - 대시보드 업데이트                                                │
│      │ - 학생에게 개입 메시지                                             │
│      ▼                                                                   │
│  [교사 대시보드] ◄──── [학생 인터페이스]                                  │
│                                                                          │
└─────────────────────────────────────────────────────────────────────────┘
```

### 6.3 PHP ↔ Python 통신

**옵션 A: REST API (권장)**
```php
// PHP에서 Python 호출
$response = file_get_contents('http://localhost:5000/api/wavefunction/calculate', false, 
    stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($student_data)
        ]
    ])
);
```

**옵션 B: subprocess**
```php
// PHP에서 Python 직접 호출
$result = shell_exec("python3 calculate_wavefunctions.py --student_id=$id");
```

---

## 7. Technical Considerations

### 7.1 성능 제약

| 항목 | 제약 | 해결 방안 |
|------|------|----------|
| 22×22 Matrix 연산 | 484개 셀 실시간 업데이트 | `SparseEntanglementMap` 사용, 안정적 엣지 동결 |
| Hamiltonian 계산 | 64차원 → 수백만 연산/초 | `LightweightHamiltonian` (16차원 압축) |
| 파동함수 계산 주기 | 20초마다 13종 계산 | 캐싱, 변화 없으면 스킵 |
| 서버 지연 | 400ms 초과 시 개입 무효 | `ServerLoadManager` 적응형 개입 |

### 7.2 에러 핸들링

```python
# 모든 에러에 파일명 + 위치 포함
try:
    result = calculate_psi_core(data)
except Exception as e:
    raise QuantumModelingError(
        f"[quantum modeling/_psi_core.py:L{lineno}] {str(e)}"
    )
```

### 7.3 Moodle 통합

```php
// 항상 Moodle config 포함
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 사용자 역할 확인
$userrole = $DB->get_record_sql(
    "SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'"
);
$role = $userrole->data;
```

---

## 8. Success Metrics

| 지표 | 목표 | 측정 방법 |
|------|------|----------|
| 문서 연결성 | 100% 상호 참조 | 수동 검증 (체크리스트) |
| 파동함수 구현율 | 13/13 (100%) | 단위 테스트 통과 |
| IDE 구현율 | 7/7 컴포넌트 (100%) | 단위 테스트 통과 |
| 테스트 커버리지 | 80% 이상 | pytest --cov |
| 실제 학생 테스트 | 최소 1명 | E2E 시나리오 완료 |
| 대시보드 실시간성 | 20초 이내 업데이트 | 타이밍 로그 |
| 개입 정확도 | 교사 피드백 긍정률 70% | 설문 조사 |
| 시스템 가용성 | 99% uptime | 모니터링 로그 |

---

## 9. Open Questions

| # | 질문 | 담당 | 기한 |
|:-:|------|------|------|
| Q1 | LLM Mind 레이어에 어떤 모델 사용? (GPT-4, Claude, 로컬) | 아키텍트 | Phase 2 전 |
| Q2 | TTS는 필수인가 선택인가? | PM | Phase 2 전 |
| Q3 | 64차원 StateVector의 각 차원 초기값은? | 데이터 분석가 | Phase 1 전 |
| Q4 | 실제 학생 테스트 대상 선정 기준? | 교육팀 | Phase 3 전 |
| Q5 | 대시보드 UI 프레임워크? (순수 PHP vs React) | 프론트엔드 | Phase 2 전 |
| Q6 | Python 서버 호스팅 방식? (같은 서버 vs 분리) | 인프라 | Phase 1 전 |

---

## 10. Implementation Roadmap

### Phase 0: 문서 정비 (1주)

| 태스크 | 산출물 | 담당 |
|--------|--------|------|
| 문서 간 상호 참조 링크 추가 | 업데이트된 6개 문서 | 문서화 담당 |
| IMPLEMENTATION_GUIDE.md 작성 | 신규 문서 | 문서화 담당 |
| 폴더 구조 재정리 | theory/, design/, src/, tests/ | 개발 담당 |

### Phase 1: 핵심 구현 (3주)

| 주차 | 태스크 | 산출물 |
|:----:|--------|--------|
| W1 | 64차원 StudentStateVector | `_student_state_vector.py` |
| W1 | EntanglementMap (22×22) | `_entanglement_map.py` |
| W2 | ψ_core, ψ_align, ψ_fluct, ψ_tunnel | 4개 파동함수 |
| W2 | ψ_WM, ψ_affect, ψ_routine | 3개 파동함수 |
| W3 | ψ_engage, ψ_concept, ψ_cascade | 3개 파동함수 |
| W3 | ψ_meta, ψ_context, ψ_predict | 3개 파동함수 |

### Phase 2: IDE 구현 (3주)

| 주차 | 태스크 | 산출물 |
|:----:|--------|--------|
| W4 | AgentTrigger, BCE | IDE Step 1-2 |
| W4 | ScenarioGenerator | IDE Step 3 |
| W5 | PriorityCalculator, PrerequisiteChecker | IDE Step 4-5 |
| W5 | InterventionSelector | IDE Step 6 |
| W6 | InterventionExecutor | IDE Step 7 |
| W6 | Brain Layer 통합 | `_brain_quantum.py` |

### Phase 3: 파이프라인 및 검증 (2주)

| 주차 | 태스크 | 산출물 |
|:----:|--------|--------|
| W7 | Mind Layer (LLM 연동) | `_mind_generator.py` |
| W7 | 단위 테스트 작성 | 80% 커버리지 |
| W8 | 대시보드 연동 | 실시간 모니터링 UI |
| W8 | 실제 학생 테스트 | E2E 검증 보고서 |

### Phase 4: Critical Issues 해결 (2주)

| 주차 | 태스크 | 산출물 |
|:----:|--------|--------|
| W9 | 타이밍/시스템 충돌 문제 | `TemporalNormalizer`, `InterventionCoordinator` |
| W9 | 우선순위 충돌 문제 | `PriorityResolver`, `ScenarioDeduplicator` |
| W10 | 계산 비용/과잉 개입 문제 | `SparseEntanglementMap`, `DriftDetectionCalibrator` |
| W10 | 예측 실패/파동함수 불안정 | `AffectScaleNormalizer`, `WavefunctionStabilityChecker` |

---

## 11. Appendix

### A. 관련 문서 링크

| 문서 | 경로 |
|------|------|
| 00-INDEX.md | `quantum modeling/00-INDEX.md` |
| SYSTEM_STATUS.yaml | `quantum modeling/SYSTEM_STATUS.yaml` |
| quantum-learning-model.md | `quantum modeling/quantum-learning-model.md` |
| quantum-orchestration-design.md | `quantum modeling/quantum-orchestration-design.md` |
| wavefunction-agent-mapping.md | `quantum modeling/wavefunction-agent-mapping.md` |
| quantum-ide-critical-issues.md | `quantum modeling/quantum-ide-critical-issues.md` |

### B. 용어 정의

| 용어 | 정의 |
|------|------|
| α (알파) | 정답 상태 확률 진폭 |
| β (베타) | 오개념 상태 확률 진폭 |
| γ (감마) | 혼란 상태 확률 진폭 |
| CP | Collapse Probability (붕괴 확률) |
| IDE | Intervention Decision Engine (개입 의사결정 엔진) |
| BCE | Boundary Condition Engine (경계조건 엔진) |
| SSOT | Single Source of Truth |

### C. 참고 자료

- quantum-ide-critical-issues.md의 17개 문제 목록
- engine_config.php의 22개 에이전트 정의
- AGENT_INTERDEPENDENCY_DOCUMENTATION.md의 에이전트 상호의존성

---

**문서 끝**

---

*이 PRD는 주니어 개발자가 이해하고 구현할 수 있도록 작성되었습니다.*
*질문이 있으면 담당자에게 문의하세요.*

