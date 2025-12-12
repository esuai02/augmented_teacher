# Agent04 온톨로지 통합 테스트

**생성일**: 2025-01-27  
**목적**: Agent04 온톨로지 통합의 전체 흐름 검증 및 테스트

---

## 테스트 파일

### 1. `test_agent04_integration.php`
통합 테스트 스크립트 - 전체 흐름 검증

**테스트 항목**:
- 시나리오 1: 개념이해 취약구간 탐지 (CU_A1)
- 시나리오 2: TTS 주의집중 패턴 분석 (CU_A2)
- 시나리오 3: 개념 혼동 탐지 (CU_A3)
- 에러 케이스: 변수 누락 시 기본 동작 확인
- 성능 테스트: 응답 시간 측정
- 데이터베이스 검증: 온톨로지 인스턴스 저장 확인

**실행 방법**:
```
브라우저에서 접속:
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_inspect_weakpoints/tests/test_agent04_integration.php
```

**예상 결과**:
- 모든 테스트 케이스 통과
- 통과율 95% 이상
- 응답 시간 2초 이하
- 온톨로지 인스턴스가 DB에 저장됨

---

## 테스트 시나리오 상세

### 시나리오 1: 개념이해 취약구간 탐지 (CU_A1)

**입력 데이터**:
```php
[
    'student_id' => $USER->id,
    'activity_type' => 'concept_understanding',
    'concept_stage' => 'understanding',
    'pause_frequency' => 5,
    'pause_stage' => '핵심 의미 파악'
]
```

**검증 항목**:
1. ✅ 룰 평가 결과 확인 (CU_A1 룰 매칭)
2. ✅ 응답 생성 확인
3. ✅ 온톨로지 결과 확인
4. ✅ 보강 방안 확인
5. ✅ 메시지 확인

**예상 온톨로지 액션**:
- `create_instance: 'mk-a04:WeakpointDetectionContext'`
- `create_instance: 'mk-a04:ActivityAnalysisContext'`
- `reason_over: 'mk-a04:ActivityAnalysisContext'`
- `generate_reinforcement_plan: 'mk-a04:WeakpointAnalysisDecisionModel'`

---

### 시나리오 2: TTS 주의집중 패턴 분석 (CU_A2)

**입력 데이터**:
```php
[
    'student_id' => $USER->id,
    'activity_type' => 'concept_understanding',
    'learning_method' => 'TTS',
    'gaze_attention_score' => 0.5,
    'note_taking_pattern_change' => true
]
```

**검증 항목**:
1. ✅ 룰 평가 결과 확인 (CU_A2 룰 매칭)
2. ✅ 온톨로지 결과 확인

---

### 시나리오 3: 개념 혼동 탐지 (CU_A3)

**입력 데이터**:
```php
[
    'student_id' => $USER->id,
    'activity_type' => 'concept_understanding',
    'concept_confusion_detected' => true,
    'confusion_type' => 'definition_vs_example'
]
```

**검증 항목**:
1. ✅ 룰 평가 결과 확인 (CU_A3 룰 매칭)
2. ✅ 온톨로지 결과 확인

---

## 에러 케이스 테스트

### 변수 누락 시 기본 동작 확인

**입력 데이터**:
```php
[
    'student_id' => $USER->id,
    // activity_type 누락
]
```

**검증 항목**:
1. ✅ 기본 응답 반환 확인
2. ✅ 에러 상태 확인

---

## 성능 테스트

### 응답 시간 측정

**테스트 방법**:
- 동일한 시나리오를 3회 반복 실행
- 각 실행의 응답 시간 측정
- 평균, 최대, 최소 응답 시간 계산

**성능 기준**:
- 평균 응답 시간: 2초 이하
- 최대 응답 시간: 3초 이하

---

## 데이터베이스 검증

### 온톨로지 인스턴스 저장 확인

**검증 항목**:
1. ✅ `alt42_ontology_instances` 테이블에 인스턴스 저장 확인
2. ✅ 학생별 인스턴스 조회 확인
3. ✅ JSON-LD 데이터 형식 확인

**SQL 쿼리**:
```sql
SELECT * FROM mdl_alt42_ontology_instances 
WHERE student_id = ? 
ORDER BY created_at DESC 
LIMIT 10
```

---

## 테스트 결과 해석

### 통과율 기준

- **95% 이상**: ✅ 통합 성공
- **80-95%**: ⚠️ 일부 개선 필요
- **80% 미만**: ❌ 심각한 문제

### 주요 확인 사항

1. **룰 평가**: 모든 시나리오에서 올바른 룰이 매칭되는가?
2. **온톨로지 결과**: 온톨로지 인스턴스가 생성되고 추론이 수행되는가?
3. **보강 방안**: 보강 방안이 정상적으로 생성되는가?
4. **응답 메시지**: 사용자 친화적인 메시지가 생성되는가?
5. **에러 처리**: 에러 발생 시 기본 동작이 유지되는가?
6. **성능**: 응답 시간이 기준을 만족하는가?
7. **데이터베이스**: 인스턴스가 DB에 저장되는가?

---

## 문제 해결

### 테스트 실패 시 확인 사항

1. **Moodle 설정 확인**
   - `config.php` 경로 확인
   - 데이터베이스 연결 확인
   - 사용자 인증 확인

2. **파일 경로 확인**
   - `agent_garden.service.php` 경로 확인
   - `OntologyEngine.php` 경로 확인
   - `OntologyActionHandler.php` 경로 확인

3. **데이터베이스 확인**
   - `alt42_ontology_instances` 테이블 존재 확인
   - 테이블 구조 확인

4. **로그 확인**
   - PHP 에러 로그 확인
   - Moodle 로그 확인
   - 브라우저 콘솔 확인

---

## 추가 테스트

### 수동 테스트

1. **실제 학습 활동 데이터로 테스트**
   - `mdl_alt42_student_activity` 테이블의 실제 데이터 사용
   - 다양한 활동 유형 테스트

2. **대량 데이터 테스트**
   - 100개 이상의 활동 데이터 처리
   - 성능 및 메모리 사용량 확인

3. **동시 요청 테스트**
   - 10개 이상의 동시 요청 처리
   - 동시성 문제 확인

---

**작성일**: 2025-01-27  
**버전**: 1.0

