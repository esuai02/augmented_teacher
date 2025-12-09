# Rule-Quantum Bridge Phase 1 테스트 실행 가이드

**생성일**: 2025-12-09
**최종 수정**: 2025-12-09
**상태**: Phase 1 구현 완료, 버그 수정 완료

---

## 0. 버그 수정 이력 (2025-12-09)

### RuleYamlLoader.php 3가지 버그 수정 완료

| 버그 | 위치 | 수정 내용 |
|------|------|----------|
| Bug #1 | `parseYamlFile()` L224-234 | `yaml_parse()` 결과에서 `rules[]` 배열만 추출 |
| Bug #2 | `parseYamlValue()` L362-388 | 인라인 배열 `["a","b"]` 파싱 로직 추가 |
| Bug #3 | `customYamlParse()` L248-290 | `version:`, `scenario:` 등 top-level 키 건너뛰기 |

---

## 1. 구현 완료 파일 목록

### Core Components
| 파일 | 경로 | 상태 |
|------|------|------|
| RuleYamlLoader.php | `shared/quantum/` | ✅ 완료 (버그 수정 완료) |
| RuleToWaveMapper.php | `shared/quantum/` | ✅ 완료 |
| QuantumPersonaEngine.php | `agents/agent04_.../quantum_modeling/` | ✅ 브릿지 확장 완료 |

### Database Migration
| 파일 | 경로 | 상태 |
|------|------|------|
| run_010_migration.php | `db/migrations/` | ✅ 완료 |
| 010_create_rule_quantum_state_table.sql | `db/migrations/` | ✅ 완료 |

### Test Files
| 파일 | 경로 | 상태 |
|------|------|------|
| test_phase1_integration.php | `shared/quantum/tests/` | ✅ 완료 |
| test_yaml_loader_standalone.php | `shared/quantum/tests/` | ✅ 신규 (인증 불필요) |
| db_diagnostic.php | `shared/quantum/tests/` | ✅ 신규 (DB 진단) |

---

## 2. 테스트 실행 URL

### Step 1: DB 마이그레이션 (최초 1회)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/run_010_migration.php
```

### Step 2: Phase 1 통합 테스트
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/test_phase1_integration.php
```

### JSON API 모드
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/shared/quantum/tests/test_phase1_integration.php?format=json
```

---

## 3. 예상 테스트 결과

### Section 1: RuleYamlLoader Tests
- ✅ File existence check
- ✅ Class instantiation
- ✅ Agent04 rules.yaml loading
- ✅ Rule structure validation
- ✅ Condition field extraction

### Section 2: RuleToWaveMapper Tests
- ✅ File existence check
- ✅ Class instantiation (with RuleYamlLoader injection)
- ✅ Single rule → wave params conversion
- ✅ All agent rules mapping
- ✅ Layer 1 score calculation

### Section 3: Database Table Tests
- ✅ Table `mdl_at_rule_quantum_state` existence
- ✅ Column structure validation
- ✅ Index verification

### Section 4: QuantumPersonaEngine Bridge Tests
- ✅ Bridge initialization (`initializeBridge()`)
- ✅ 4D → 8D state vector conversion (`convert4Dto8D()`)
- ✅ Agent wave params loading (`loadAgentWaveParams()`)
- ✅ Bridge info retrieval (`getBridgeInfo()`)

### Section 5: 4-Layer Probability Tests
- ✅ Layer 1: Rule Confidence (P_rule)
- ✅ Layer 2: Wave Probability (P_wave)
- ⏳ Layer 3: Correlation Influence (Phase 2)
- ✅ Layer 4: Final HYBRID probability (P_final)
- ✅ Intervention type determination

### Section 6: Full Pipeline Tests
- ✅ Complete evaluation flow
- ✅ Summary generation
- ✅ Database persistence

---

## 4. 4-Layer 확률 계산 공식

```
Layer 1: P_rule = confidence × (priority/100) × condition_match
Layer 2: P_wave = |⟨ψ_agent|ψ_target⟩|²
Layer 3: P_corr = Σ(C_ij × P_j) / 21  [Phase 2에서 구현]
Layer 4: P_final = sigmoid(0.25×P_rule + 0.35×P_wave + 0.25×P_corr + bias)
```

### 개입 결정 임계값
| P_final 범위 | 개입 유형 | 동작 |
|-------------|----------|------|
| P ≥ 0.9 | IMMEDIATE_INTERVENTION | 100% 실행 |
| 0.7 ≤ P < 0.9 | PROBABILISTIC_GATING | P 확률로 실행 |
| 0.5 ≤ P < 0.7 | WEIGHT_ADJUSTMENT | 가중치 조정 후 대기 |
| P < 0.5 | OBSERVE_ONLY | 관찰만 |

---

## 5. 문제 해결

### 테이블이 존재하지 않는 경우
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/db/migrations/run_010_migration.php
```
위 URL에서 "Install" 버튼 클릭

### rules.yaml 파일이 없는 경우
Agent04 rules.yaml 경로 확인:
```
agents/agent04_inspect_weakpoints/rules/rules.yaml
```

### QuantumPersonaEngine 클래스 로드 실패
파일 경로 확인:
```
agents/agent04_inspect_weakpoints/quantum_modeling/QuantumPersonaEngine.php
```

---

## 6. 다음 단계 (Phase 2)

Phase 1 테스트 통과 후:
1. **CorrelationAnalyzer.php** 구현 - 22×22 상관 매트릭스
2. **22개 rules.yaml** 전체 로드 및 캐싱
3. **클러스터 탐지** 알고리즘 구현
4. **mdl_at_correlation_matrix** DB 테이블 생성

---

**문서 위치**: `shared/quantum/tests/PHASE1_TEST_GUIDE.md`
