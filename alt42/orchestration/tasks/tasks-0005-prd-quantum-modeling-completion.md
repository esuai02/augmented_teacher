# Task List: Quantum Modeling 시스템 완성

**Based on PRD**: `0005-prd-quantum-modeling-completion.md`

**Project Context**: 실시간 AI 튜터의 핵심 엔진 - 13종 파동함수, IDE 7단계 파이프라인, 64차원 StateVector 구현

**Current State Assessment**:
- ✅ **이론 문서 완료**: quantum-learning-model.md, quantum-orchestration-design.md
- ✅ **에이전트 매핑 완료**: wavefunction-agent-mapping.md (22 에이전트 ↔ 13 파동함수)
- ✅ **Critical Issues 정리**: quantum-ide-critical-issues.md (17개 문제)
- ⚠️ **코드 미구현**: 파동함수 0/13, IDE 0/7, StateVector 8→64차원 미완료
- ⚠️ **테스트 부재**: 단위 테스트, 통합 테스트, 실제 학생 테스트 없음

## Relevant Files

### 참조 문서 (READ ONLY)
- `quantum modeling/00-INDEX.md` - 문서 허브
- `quantum modeling/SYSTEM_STATUS.yaml` - SSOT
- `quantum modeling/quantum-learning-model.md` - 이론 (13종 파동함수 수식)
- `quantum modeling/quantum-orchestration-design.md` - 설계 (IDE, StateVector)
- `quantum modeling/wavefunction-agent-mapping.md` - 에이전트 매핑
- `quantum modeling/quantum-ide-critical-issues.md` - 17개 Critical Issues
- `quantum modeling/IMPLEMENTATION_GUIDE.md` - 구현 가이드

### Python 소스 (CREATE)
- `quantum modeling/src/wavefunctions/__init__.py` - 파동함수 패키지
- `quantum modeling/src/wavefunctions/_base.py` - BaseWavefunction 클래스
- `quantum modeling/src/wavefunctions/_psi_core.py` - ψ_core 구현
- `quantum modeling/src/wavefunctions/_psi_align.py` - ψ_align 구현
- `quantum modeling/src/wavefunctions/_psi_fluct.py` - ψ_fluct 구현
- `quantum modeling/src/wavefunctions/_psi_tunnel.py` - ψ_tunnel 구현
- `quantum modeling/src/wavefunctions/_psi_wm.py` - ψ_WM 구현
- `quantum modeling/src/wavefunctions/_psi_affect.py` - ψ_affect 구현
- `quantum modeling/src/wavefunctions/_psi_routine.py` - ψ_routine 구현
- `quantum modeling/src/wavefunctions/_psi_engage.py` - ψ_engage 구현
- `quantum modeling/src/wavefunctions/_psi_concept.py` - ψ_concept 구현
- `quantum modeling/src/wavefunctions/_psi_cascade.py` - ψ_cascade 구현
- `quantum modeling/src/wavefunctions/_psi_meta.py` - ψ_meta 구현
- `quantum modeling/src/wavefunctions/_psi_context.py` - ψ_context 구현
- `quantum modeling/src/wavefunctions/_psi_predict.py` - ψ_predict 구현

### State 모듈 (CREATE)
- `quantum modeling/src/state/__init__.py` - 상태 패키지
- `quantum modeling/src/state/_student_state_vector.py` - 64차원 StateVector
- `quantum modeling/src/state/_entanglement_map.py` - 22×22 얽힘 맵

### IDE 모듈 (CREATE)
- `quantum modeling/src/ide/__init__.py` - IDE 패키지
- `quantum modeling/src/ide/_ide_trigger.py` - Step 1: AgentTrigger
- `quantum modeling/src/ide/_ide_boundary.py` - Step 2: BoundaryConditionEngine
- `quantum modeling/src/ide/_ide_scenario.py` - Step 3: ScenarioGenerator
- `quantum modeling/src/ide/_ide_priority.py` - Step 4: PriorityCalculator
- `quantum modeling/src/ide/_ide_prerequisite.py` - Step 5: PrerequisiteChecker
- `quantum modeling/src/ide/_ide_selector.py` - Step 6: InterventionSelector
- `quantum modeling/src/ide/_ide_executor.py` - Step 7: InterventionExecutor

### Pipeline 모듈 (CREATE)
- `quantum modeling/src/pipeline/__init__.py` - 파이프라인 패키지
- `quantum modeling/src/pipeline/_brain_quantum.py` - Brain Layer (판단)
- `quantum modeling/src/pipeline/_mind_generator.py` - Mind Layer (생성)
- `quantum modeling/src/pipeline/_mouth_tts.py` - Mouth Layer (출력)
- `quantum modeling/src/pipeline/_realtime_tutor.py` - 통합 파이프라인

### Utils 모듈 (CREATE)
- `quantum modeling/src/utils/__init__.py` - 유틸리티 패키지
- `quantum modeling/src/utils/_temporal_normalizer.py` - 시간 스케일 정규화
- `quantum modeling/src/utils/_intervention_coordinator.py` - 개입 조정기
- `quantum modeling/src/utils/_priority_resolver.py` - 우선순위 해결
- `quantum modeling/src/utils/_sparse_entanglement.py` - 희소 얽힘 맵

### PHP 소스 (CREATE)
- `quantum modeling/php/api/calculate_wavefunctions.php` - 파동함수 API
- `quantum modeling/php/api/get_state_vector.php` - StateVector API
- `quantum modeling/php/api/intervention_decision.php` - IDE API
- `quantum modeling/php/dashboard/realtime_monitor.php` - 실시간 대시보드

### 테스트 (CREATE)
- `quantum modeling/tests/__init__.py` - 테스트 패키지
- `quantum modeling/tests/test_wavefunctions.py` - 파동함수 단위 테스트
- `quantum modeling/tests/test_state.py` - StateVector 테스트
- `quantum modeling/tests/test_ide.py` - IDE 단위 테스트
- `quantum modeling/tests/test_pipeline.py` - 파이프라인 테스트
- `quantum modeling/tests/test_integration.py` - 통합 테스트

### Notes

- 모든 Python 파일은 Python 3.10.12 호환
- 에러 메시지는 반드시 `[파일경로:L라인번호]` 형식 포함
- docstring은 한국어로 작성
- `pytest`를 사용하여 테스트 실행
- PHP 파일은 Moodle 설정 포함 필수: `include_once("/home/moodle/public_html/moodle/config.php");`

---

## Tasks

### Phase 0: 문서 정비 (1주)

- [ ] 0.1 문서 간 상호 참조 링크 추가
  - [ ] 0.1.1 00-INDEX.md에 모든 문서 링크 확인 및 업데이트
  - [ ] 0.1.2 quantum-learning-model.md에 "📚 Related Documents" 섹션 추가
  - [ ] 0.1.3 quantum-orchestration-design.md에 "📚 Related Documents" 섹션 추가
  - [ ] 0.1.4 wavefunction-agent-mapping.md에 "📚 Related Documents" 섹션 추가
  - [ ] 0.1.5 quantum-ide-critical-issues.md에 "📚 Related Documents" 섹션 추가

- [ ] 0.2 IMPLEMENTATION_GUIDE.md 작성
  - [ ] 0.2.1 환경 설정 가이드 작성 (Python 3.10.12, PHP 7.1.9, MySQL 5.7)
  - [ ] 0.2.2 파동함수 구현 템플릿 작성 (BaseWavefunction 상속)
  - [ ] 0.2.3 IDE 구현 가이드 작성 (7단계 파이프라인)
  - [ ] 0.2.4 테스트 가이드 작성 (pytest 사용)
  - [ ] 0.2.5 에러 처리 규칙 작성 (파일:라인 형식)

- [ ] 0.3 폴더 구조 생성
  - [ ] 0.3.1 `src/` 디렉토리 생성
  - [ ] 0.3.2 `src/wavefunctions/` 디렉토리 생성
  - [ ] 0.3.3 `src/state/` 디렉토리 생성
  - [ ] 0.3.4 `src/ide/` 디렉토리 생성
  - [ ] 0.3.5 `src/pipeline/` 디렉토리 생성
  - [ ] 0.3.6 `src/utils/` 디렉토리 생성
  - [ ] 0.3.7 `php/` 디렉토리 생성
  - [ ] 0.3.8 `tests/` 디렉토리 생성

---

### Phase 1: 핵심 구현 (3주)

#### Week 1: StateVector + 기반 클래스

- [ ] 1.1 BaseWavefunction 클래스 구현
  - [ ] 1.1.1 `_base.py` 파일 생성
  - [ ] 1.1.2 `BaseWavefunction` 추상 클래스 정의
  - [ ] 1.1.3 `calculate()` 추상 메서드 정의
  - [ ] 1.1.4 `validate_input()` 메서드 구현
  - [ ] 1.1.5 `WavefunctionResult` dataclass 정의 (alpha, beta, gamma)
  - [ ] 1.1.6 에러 핸들링 데코레이터 구현 (파일:라인 포함)

- [ ] 1.2 StudentStateVector 64차원 구현
  - [ ] 1.2.1 `_student_state_vector.py` 파일 생성
  - [ ] 1.2.2 인지 차원 16개 정의 (concept_mastery, cognitive_load, ...)
  - [ ] 1.2.3 정서 차원 16개 정의 (motivation, anxiety, ...)
  - [ ] 1.2.4 행동 차원 16개 정의 (engagement_behavior, persistence, ...)
  - [ ] 1.2.5 컨텍스트 차원 16개 정의 (time_pressure, teacher_support, ...)
  - [ ] 1.2.6 numpy 기반 벡터 연산 구현
  - [ ] 1.2.7 JSON 직렬화/역직렬화 구현
  - [ ] 1.2.8 8차원 → 64차원 마이그레이션 함수 구현
  - [ ] 1.2.9 단위 테스트 작성

- [ ] 1.3 EntanglementMap 22×22 구현
  - [ ] 1.3.1 `_entanglement_map.py` 파일 생성
  - [ ] 1.3.2 22개 에이전트 ID 정의
  - [ ] 1.3.3 희소 행렬 기반 구현 (scipy.sparse)
  - [ ] 1.3.4 양의 상관/음의 상관 표현
  - [ ] 1.3.5 위상(phase) 정보 저장 (0 ~ 2π)
  - [ ] 1.3.6 안정적 엣지 동결 기능 구현
  - [ ] 1.3.7 wavefunction-agent-mapping.md 기반 초기값 설정
  - [ ] 1.3.8 단위 테스트 작성

#### Week 2: 파동함수 1~7

- [ ] 1.4 ψ_core (핵심 3상태) 구현
  - [ ] 1.4.1 `_psi_core.py` 파일 생성
  - [ ] 1.4.2 입력 데이터 정의 (correct_rate, misconception_score, hesitation_time, ...)
  - [ ] 1.4.3 α 계산 로직 구현 (정답 확률)
  - [ ] 1.4.4 β 계산 로직 구현 (오개념 확률)
  - [ ] 1.4.5 γ 계산 로직 구현 (혼란 확률)
  - [ ] 1.4.6 정규화 검증 (α + β + γ = 1)
  - [ ] 1.4.7 단위 테스트 작성

- [ ] 1.5 ψ_align (정렬) 구현
  - [ ] 1.5.1 `_psi_align.py` 파일 생성
  - [ ] 1.5.2 목표 방향 벡터 계산 (Σ cos(θᵢ)/n)
  - [ ] 1.5.3 정렬도 점수 (0.0 ~ 1.0) 반환
  - [ ] 1.5.4 단위 테스트 작성

- [ ] 1.6 ψ_fluct (요동) 구현
  - [ ] 1.6.1 `_psi_fluct.py` 파일 생성
  - [ ] 1.6.2 시도/수정 횟수 기반 계산 (Σ (Δbehavior)²)
  - [ ] 1.6.3 탐색 폭 측정
  - [ ] 1.6.4 단위 테스트 작성

- [ ] 1.7 ψ_tunnel (터널링) 구현
  - [ ] 1.7.1 `_psi_tunnel.py` 파일 생성
  - [ ] 1.7.2 배리어(난이도) 계산
  - [ ] 1.7.3 인지 에너지 측정
  - [ ] 1.7.4 터널링 확률 계산 (exp(-B/E_cog))
  - [ ] 1.7.5 단위 테스트 작성

- [ ] 1.8 ψ_WM (작업기억) 구현
  - [ ] 1.8.1 `_psi_wm.py` 파일 생성
  - [ ] 1.8.2 세션 길이 기반 감쇠 (exp(-t/τ))
  - [ ] 1.8.3 휴식 패턴 반영
  - [ ] 1.8.4 단위 테스트 작성

- [ ] 1.9 ψ_affect (정서) 구현
  - [ ] 1.9.1 `_psi_affect.py` 파일 생성
  - [ ] 1.9.2 침착/불안/동기 3상태 계산 [μ, ν, ξ]
  - [ ] 1.9.3 침착도 정규화 (0.0 ~ 1.0)
  - [ ] 1.9.4 단위 테스트 작성

- [ ] 1.10 ψ_routine (루틴) 구현
  - [ ] 1.10.1 `_psi_routine.py` 파일 생성
  - [ ] 1.10.2 일간 루틴 준수율 계산 (R_daily)
  - [ ] 1.10.3 주간 루틴 준수율 계산 (R_weekly)
  - [ ] 1.10.4 장기 루틴 준수율 계산 (R_long)
  - [ ] 1.10.5 복합 루틴 점수 산출
  - [ ] 1.10.6 단위 테스트 작성

#### Week 3: 파동함수 8~13

- [ ] 1.11 ψ_engage (이탈/복귀) 구현
  - [ ] 1.11.1 `_psi_engage.py` 파일 생성
  - [ ] 1.11.2 집중/중립/이탈 3상태 계산 [p, q, r]
  - [ ] 1.11.3 복귀율 측정
  - [ ] 1.11.4 단위 테스트 작성

- [ ] 1.12 ψ_concept (개념 구조) 구현
  - [ ] 1.12.1 `_psi_concept.py` 파일 생성
  - [ ] 1.12.2 개념 맵 얽힘 계산 (Σ entangle(i,j))
  - [ ] 1.12.3 전이 패턴 분석
  - [ ] 1.12.4 단위 테스트 작성

- [ ] 1.13 ψ_cascade (연쇄 붕괴) 구현
  - [ ] 1.13.1 `_psi_cascade.py` 파일 생성
  - [ ] 1.13.2 연속 정답률 계산 (α₁·α₂·...·exp(-Δt/k))
  - [ ] 1.13.3 단원 진행도 반영
  - [ ] 1.13.4 단위 테스트 작성

- [ ] 1.14 ψ_meta (메타인지) 구현
  - [ ] 1.14.1 `_psi_meta.py` 파일 생성
  - [ ] 1.14.2 자기 평가 정확도 계산
  - [ ] 1.14.3 목표 설정 패턴 분석 [s, t]
  - [ ] 1.14.4 단위 테스트 작성

- [ ] 1.15 ψ_context (상황문맥) 구현
  - [ ] 1.15.1 `_psi_context.py` 파일 생성
  - [ ] 1.15.2 환경 변수 가중합 (Σ contextᵢ·wᵢ)
  - [ ] 1.15.3 시간대, 시험 근접도 반영
  - [ ] 1.15.4 단위 테스트 작성

- [ ] 1.16 ψ_predict (예측) 구현
  - [ ] 1.16.1 `_psi_predict.py` 파일 생성
  - [ ] 1.16.2 α 시계열 분석
  - [ ] 1.16.3 붕괴 확률 예측 (α·dα/dt·Align)
  - [ ] 1.16.4 단위 테스트 작성

---

### Phase 2: IDE 구현 (3주)

#### Week 4: IDE Step 1~3

- [ ] 2.1 AgentTrigger (Step 1) 구현
  - [ ] 2.1.1 `_ide_trigger.py` 파일 생성
  - [ ] 2.1.2 22개 에이전트별 트리거 조건 정의
  - [ ] 2.1.3 파동함수 결과 기반 트리거 감지
  - [ ] 2.1.4 트리거된 에이전트 목록 반환
  - [ ] 2.1.5 단위 테스트 작성

- [ ] 2.2 BoundaryConditionEngine (Step 2) 구현
  - [ ] 2.2.1 `_ide_boundary.py` 파일 생성
  - [ ] 2.2.2 4개 경계조건 정의 (진입/유지/퇴장/금지)
  - [ ] 2.2.3 경계조건 검증 로직 구현
  - [ ] 2.2.4 SoftBCE 옵션 구현 (유연한 경계)
  - [ ] 2.2.5 단위 테스트 작성

- [ ] 2.3 ScenarioGenerator (Step 3) 구현
  - [ ] 2.3.1 `_ide_scenario.py` 파일 생성
  - [ ] 2.3.2 시나리오 후보군 생성 로직
  - [ ] 2.3.3 중복 제거 (ScenarioDeduplicator)
  - [ ] 2.3.4 시나리오 리스트 반환
  - [ ] 2.3.5 단위 테스트 작성

#### Week 5: IDE Step 4~5

- [ ] 2.4 PriorityCalculator (Step 4) 구현
  - [ ] 2.4.1 `_ide_priority.py` 파일 생성
  - [ ] 2.4.2 가중치 기반 우선순위 계산
  - [ ] 2.4.3 PriorityResolver (충돌 해결)
  - [ ] 2.4.4 우선순위 정렬된 시나리오 반환
  - [ ] 2.4.5 단위 테스트 작성

- [ ] 2.5 PrerequisiteChecker (Step 5) 구현
  - [ ] 2.5.1 `_ide_prerequisite.py` 파일 생성
  - [ ] 2.5.2 각 시나리오 필수 조건 정의
  - [ ] 2.5.3 조건 충족 여부 검증
  - [ ] 2.5.4 통과/실패 시나리오 분리
  - [ ] 2.5.5 단위 테스트 작성

#### Week 6: IDE Step 6~7 + Brain

- [ ] 2.6 InterventionSelector (Step 6) 구현
  - [ ] 2.6.1 `_ide_selector.py` 파일 생성
  - [ ] 2.6.2 최종 시나리오 선택 로직
  - [ ] 2.6.3 다중 시나리오 조합 지원
  - [ ] 2.6.4 단위 테스트 작성

- [ ] 2.7 InterventionExecutor (Step 7) 구현
  - [ ] 2.7.1 `_ide_executor.py` 파일 생성
  - [ ] 2.7.2 Mind Layer 호출
  - [ ] 2.7.3 Mouth Layer 호출 (선택적)
  - [ ] 2.7.4 실행 결과 로깅
  - [ ] 2.7.5 단위 테스트 작성

- [ ] 2.8 Brain Layer 통합
  - [ ] 2.8.1 `_brain_quantum.py` 파일 생성
  - [ ] 2.8.2 CP(t) 계산 로직 (α·dα/dt·Align·(1-γ))
  - [ ] 2.8.3 threshold 기반 개입/비개입/미세개입 결정
  - [ ] 2.8.4 IDE 7단계 파이프라인 통합 호출
  - [ ] 2.8.5 단위 테스트 작성

---

### Phase 3: 파이프라인 및 검증 (2주)

#### Week 7: Mind Layer + 테스트

- [ ] 3.1 Mind Layer 구현
  - [ ] 3.1.1 `_mind_generator.py` 파일 생성
  - [ ] 3.1.2 LLM API 연동 (GPT-4 또는 Claude)
  - [ ] 3.1.3 개입 유형별 프롬프트 템플릿
  - [ ] 3.1.4 대사/지문 생성 로직
  - [ ] 3.1.5 단위 테스트 작성

- [ ] 3.2 Mouth Layer 구현 (선택적)
  - [ ] 3.2.1 `_mouth_tts.py` 파일 생성
  - [ ] 3.2.2 TTS API 연동 (선택적)
  - [ ] 3.2.3 텍스트 출력 fallback
  - [ ] 3.2.4 단위 테스트 작성

- [ ] 3.3 통합 파이프라인 구현
  - [ ] 3.3.1 `_realtime_tutor.py` 파일 생성
  - [ ] 3.3.2 Brain→Mind→Mouth 순차 실행
  - [ ] 3.3.3 20초 주기 스케줄링
  - [ ] 3.3.4 통합 테스트 작성

- [ ] 3.4 단위 테스트 완성
  - [ ] 3.4.1 `test_wavefunctions.py` 작성 (13종)
  - [ ] 3.4.2 `test_state.py` 작성 (StateVector, EntanglementMap)
  - [ ] 3.4.3 `test_ide.py` 작성 (7단계)
  - [ ] 3.4.4 `test_pipeline.py` 작성 (Brain/Mind/Mouth)
  - [ ] 3.4.5 커버리지 80% 이상 달성 확인

#### Week 8: 대시보드 + 학생 테스트

- [ ] 3.5 PHP API 구현
  - [ ] 3.5.1 `calculate_wavefunctions.php` 작성
  - [ ] 3.5.2 `get_state_vector.php` 작성
  - [ ] 3.5.3 `intervention_decision.php` 작성
  - [ ] 3.5.4 Python 서버 호출 로직 (REST API)
  - [ ] 3.5.5 API 테스트

- [ ] 3.6 대시보드 연동
  - [ ] 3.6.1 `realtime_monitor.php` 작성
  - [ ] 3.6.2 실시간 α, β, γ 그래프 표시
  - [ ] 3.6.3 개입 이력 표시
  - [ ] 3.6.4 CP 경고 알림 (CP > 0.8)
  - [ ] 3.6.5 학생별 페르소나 표시

- [ ] 3.7 실제 학생 테스트
  - [ ] 3.7.1 테스트 대상 학생 1명 선정
  - [ ] 3.7.2 E2E 시나리오 정의
  - [ ] 3.7.3 테스트 실행 및 로그 수집
  - [ ] 3.7.4 결과 분석 및 보고서 작성

---

### Phase 4: Critical Issues 해결 (2주)

#### Week 9: 타이밍/충돌 문제

- [ ] 4.1 타이밍 문제 해결
  - [ ] 4.1.1 `_temporal_normalizer.py` 구현 (시간 스케일 정규화)
  - [ ] 4.1.2 `InterventionTimingGuard` 구현 (중복 개입 방지)
  - [ ] 4.1.3 단위 테스트 작성

- [ ] 4.2 시스템 충돌 문제 해결
  - [ ] 4.2.1 `_intervention_coordinator.py` 구현
  - [ ] 4.2.2 다중 에이전트 개입 조정
  - [ ] 4.2.3 단위 테스트 작성

- [ ] 4.3 우선순위 충돌 문제 해결
  - [ ] 4.3.1 `_priority_resolver.py` 구현
  - [ ] 4.3.2 `ScenarioDeduplicator` 구현
  - [ ] 4.3.3 단위 테스트 작성

#### Week 10: 성능/안정성 문제

- [ ] 4.4 계산 비용 문제 해결
  - [ ] 4.4.1 `_sparse_entanglement.py` 구현 (희소 행렬)
  - [ ] 4.4.2 `LightweightHamiltonian` 구현 (16차원 압축)
  - [ ] 4.4.3 성능 벤치마크

- [ ] 4.5 과잉 개입 문제 해결
  - [ ] 4.5.1 `DriftDetectionCalibrator` 구현
  - [ ] 4.5.2 `AnomalyDetector` 구현
  - [ ] 4.5.3 단위 테스트 작성

- [ ] 4.6 예측 실패 문제 해결
  - [ ] 4.6.1 `AffectScaleNormalizer` 구현
  - [ ] 4.6.2 `ReceptivityPredictor` 구현
  - [ ] 4.6.3 단위 테스트 작성

- [ ] 4.7 파동함수 불안정 문제 해결
  - [ ] 4.7.1 `PreferenceStabilizer` 구현
  - [ ] 4.7.2 `WavefunctionStabilityChecker` 구현
  - [ ] 4.7.3 상호 영향도 상한 설정 (max_mutual_influence = 0.3)
  - [ ] 4.7.4 단위 테스트 작성

---

## Completion Checklist

- [ ] Phase 0 완료: 문서 정비, 폴더 구조 생성
- [ ] Phase 1 완료: 13종 파동함수 + 64차원 StateVector + EntanglementMap
- [ ] Phase 2 완료: IDE 7단계 파이프라인 + Brain Layer
- [ ] Phase 3 완료: Mind/Mouth Layer + 대시보드 + 실제 학생 테스트
- [ ] Phase 4 완료: 17개 Critical Issues 해결
- [ ] 테스트 커버리지 80% 이상
- [ ] SYSTEM_STATUS.yaml 업데이트 (모든 컴포넌트 status: implemented)
- [ ] PRD Success Metrics 모두 달성

---

**문서 끝**

