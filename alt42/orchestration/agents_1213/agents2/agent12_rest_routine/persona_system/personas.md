# Agent12 휴식 루틴 페르소나 정의

## 개요

Agent12는 학생의 휴식 패턴을 분석하고 개인화된 휴식 전략을 제안하는 에이전트입니다.
학습 효율을 높이기 위해 적절한 휴식 타이밍과 방법을 코칭합니다.

## 핵심 지표

### 피로도 지수 (Fatigue Index)
- **범위**: 0-100
- **임계값**:
  - 0-30: 낮음 (Low) - 양호한 상태
  - 31-50: 보통 (Medium) - 관리 필요
  - 51-70: 높음 (High) - 주의 필요
  - 71-85: 심각 (Critical) - 즉시 휴식 권장
  - 86-100: 위험 (Danger) - 강제 휴식 필요

### 평균 휴식 간격 (Average Rest Interval)
- 휴식 버튼 클릭 간의 평균 시간(분)
- 페르소나 분류의 핵심 기준

---

## 페르소나 목록

### 1. regular_rest (정기적 휴식형)
- **평균 휴식 간격**: 60분 이하
- **피로 위험도**: 낮음 (Low)
- **우선순위**: 0 (최고)
- **코칭 모드**: supportive (지지형)
- **특징**:
  - 규칙적인 휴식 패턴 유지
  - 장기 학습에 최적화된 습관
  - 피로 누적 위험 낮음
  - 안정적인 집중력 유지

### 2. activity_centered_rest (활동 중심 휴식형)
- **평균 휴식 간격**: 60-90분
- **피로 위험도**: 보통 (Medium)
- **우선순위**: 1
- **코칭 모드**: balanced (균형형)
- **특징**:
  - 활동과 휴식의 균형 추구
  - 적절한 집중-휴식 사이클
  - 간헐적 피로 누적 가능성
  - 개선의 여지 있음

### 3. immersive_rest (집중 몰입형 / 비계획형)
- **평균 휴식 간격**: 90분 초과
- **피로 위험도**: 높음 (High)
- **우선순위**: 2
- **코칭 모드**: coaching (코칭형)
- **특징**:
  - 장시간 집중 후 긴 휴식
  - 번아웃 위험 높음
  - 불규칙한 패턴
  - 적극적 개입 필요

### 4. no_rest (휴식 없음형)
- **평균 휴식 간격**: 측정 불가 (휴식 기록 없음)
- **피로 위험도**: 치명적 (Critical)
- **우선순위**: 3 (최저 - 즉시 개입 필요)
- **코칭 모드**: intervention (개입형)
- **특징**:
  - 휴식 버튼 클릭 기록 없음
  - 극단적 피로 누적 위험
  - 학습 효율 급격히 저하
  - 강력한 휴식 유도 필요

---

## 페르소나 전환 조건

### 자동 전환 매트릭스

| 조건 | 결과 페르소나 | 신뢰도 |
|------|---------------|--------|
| avg_interval ≤ 60min | regular_rest | 95% |
| 60min < avg_interval ≤ 90min | activity_centered_rest | 90% |
| avg_interval > 90min | immersive_rest | 85% |
| 최근 7일 휴식 기록 없음 | no_rest | 98% |

### 수동 전환

- 휴식 세션 시작/종료 시 즉시 재평가
- 피로도 지수 급격 변화 시 (±20 이상)
- 사용자 휴식 패턴 설정 변경 시

---

## 휴식 전략 매핑

| 페르소나 | 전략 | 권장 휴식 시간 | 권장 휴식 간격 |
|----------|------|----------------|----------------|
| regular_rest | maintain | 5-10분 | 45-60분 |
| activity_centered_rest | optimize | 10-15분 | 50-60분 |
| immersive_rest | restructure | 15-20분 | 45분 |
| no_rest | establish | 10-15분 | 30-45분 |

### 휴식 타입별 권장

| 휴식 타입 | 설명 | 권장 시간 | 효과 |
|-----------|------|-----------|------|
| break | 일반 휴식 | 5-10분 | 기본 회복 |
| stretch | 스트레칭 | 5-7분 | 신체 이완 |
| walk | 가벼운 걷기 | 10-15분 | 혈액 순환 |
| nap | 짧은 낮잠 | 15-20분 | 깊은 회복 |

---

## 톤앤매너

| 페르소나 | 톤 | 예시 메시지 |
|---------|-----|-------------|
| regular_rest | 칭찬, 지지 | "👏 훌륭해요! 규칙적인 휴식 습관이 학습 효율을 높이고 있어요." |
| activity_centered_rest | 격려, 조언 | "💪 잘하고 있어요! 조금만 더 규칙적으로 쉬면 완벽해요." |
| immersive_rest | 우려, 코칭 | "⚠️ 너무 오래 집중하면 오히려 효율이 떨어져요. 45분마다 한 번씩 쉬어볼까요?" |
| no_rest | 경고, 설득 | "🚨 휴식 없이 공부하면 금방 지쳐요. 타이머를 설정해서 휴식 알림을 받아보세요!" |

---

## 에이전트 간 통신

### 발신 메시지

| 수신자 | 메시지 타입 | 발신 조건 |
|--------|------------|-----------|
| Agent05 (학습감정) | high_fatigue_alert | 피로도 지수 70 이상 |
| Agent08 (평온도) | fatigue_status_update | 피로도 상태 변경 시 |
| Broadcast | rest_pattern_changed | 페르소나 전환 시 |

### 수신 메시지

| 발신자 | 메시지 타입 | 처리 |
|--------|------------|------|
| Agent05 | emotion_stress_detected | 즉시 휴식 권장 강화 |
| Agent08 | calmness_level_update | 휴식 전략 조정 |
| Agent09 | study_session_update | 학습 시간 데이터 동기화 |

---

## 피로도 계산 공식

```
fatigue_index = base_fatigue
              + interval_factor
              + frequency_factor
              + trend_factor

where:
  base_fatigue = 50 (기본값)

  interval_factor:
    - avg_interval ≤ 45min: -20
    - avg_interval ≤ 60min: -10
    - avg_interval ≤ 90min: 0
    - avg_interval ≤ 120min: +10
    - avg_interval > 120min: +20

  frequency_factor:
    - rest_count = 0: +35
    - rest_count ≥ 5: -15
    - rest_count ≥ 3: -5

  trend_factor:
    - 피로도 증가 추세: +10
    - 피로도 감소 추세: -10
    - 안정: 0
```

---

## 분석 카테고리 (S0-S8)

| 코드 | 카테고리 | 분석 내용 |
|------|---------|----------|
| S0 | 휴식 히스토리 | 최근 휴식 세션 데이터 |
| S1 | 휴식 패턴 | 평균 간격, 시간대별 분포 |
| S2 | 피로도 분석 | 현재 및 추세 분석 |
| S3 | 학습-휴식 비율 | 효율적인 비율 유지 여부 |
| S4 | 휴식 품질 | 휴식 전후 피로도 변화 |
| S5 | 개인화 권장 | 맞춤형 휴식 전략 |
| S6 | 위험 신호 | 번아웃 징후 감지 |
| S7 | 비교 분석 | 동일 그룹 대비 분석 |
| S8 | 장기 추세 | 월간/분기별 패턴 변화 |

---

## 관련 파일

- `Agent12PersonaEngine.php`: 페르소나 엔진 구현
- `Agent12DataContext.php`: 데이터 컨텍스트
- `../rules/rules.yaml`: 상세 규칙 정의
- `api/chat.php`: 채팅 API 엔드포인트

---

## DB 테이블

### mdl_at_agent12_rest_sessions
휴식 세션 기록 테이블

| 필드 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| user_id | BIGINT | 사용자 ID |
| session_start | INT | 휴식 시작 시간 |
| session_end | INT | 휴식 종료 시간 |
| duration_minutes | INT | 휴식 시간(분) |
| rest_type | VARCHAR(20) | 휴식 타입 |
| fatigue_level_before | DECIMAL(3,2) | 휴식 전 피로도 |
| fatigue_level_after | DECIMAL(3,2) | 휴식 후 피로도 |

### mdl_at_agent12_routine_history
일일 루틴 히스토리 테이블

| 필드 | 타입 | 설명 |
|------|------|------|
| id | BIGINT | PK |
| user_id | BIGINT | 사용자 ID |
| date_key | VARCHAR(10) | 날짜(YYYY-MM-DD) |
| rest_count | INT | 휴식 횟수 |
| avg_rest_interval | INT | 평균 휴식 간격 |
| fatigue_index | DECIMAL(5,2) | 피로도 지수 |
| persona_code | VARCHAR(30) | 페르소나 코드 |
