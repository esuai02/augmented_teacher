# 데이터 매핑 분석 표준화 작업 계획

**작성일**: 2025-01-XX  
**현재 상태**: 16개 에이전트 중 15개 완료

## 📊 현재 진행 상황

### ✅ 표준화 완료 (15개)
- agent01_onboarding ✅
- agent05_learning_emotion ✅
- agent08_calmness ✅
- agent09_learning_management ✅
- agent11_problem_notes ✅
- agent12_rest_routine ✅
- agent13_learning_dropout ✅ (확인 필요)
- agent14_current_position ✅
- agent15_problem_redefinition ✅
- agent16_interaction_preparation ✅
- agent17_remaining_activities ✅
- agent18_signature_routine ✅
- agent19_interaction_content ✅
- agent20_intervention_preparation ✅
- agent21_intervention_execution ✅
- agent22_module_improvement ✅

### ⏳ 표준화 미완료 (1개)
- agent02_exam_schedule ⏳ (함수 기반 구조, 특별 처리 필요)

## 🎯 다음 작업 계획

### Phase 1: agent02 표준화 완료 (우선순위: 높음)

**목표**: agent02_exam_schedule에 표준 템플릿 적용

**작업 내용**:
1. `checkDataAccessUsage()` 함수 추가
   - `analyzeAgent02Data()` 함수 내부 또는 외부에 추가
   - agent02 특화 패턴 확인 (필요시)

2. DB 실제 데이터 존재 여부 확인 로직 추가
   - `analyzeAgent02Data()` 함수 내부에 추가
   - agent02 관련 테이블 확인:
     - `alt42_exam_schedule`
     - `alt42_goinghome` (JSON 데이터)
     - `alt42_student_profiles`
     - `alt42_calmness`
     - 기타 관련 테이블

3. `inRulesNotInDataAccess` 계산 로직 변경
   - 현재: `in_array($field, $dataAccessFields)` 사용
   - 변경: `checkDataAccessUsage()` 함수 사용

4. HTML 출력에 DB 실제 데이터 섹션 추가
   - "✅ DB에 실제 데이터가 존재하는 rules.yaml 필드" 섹션 추가
   - agent02의 HTML 구조에 맞게 조정

**예상 소요 시간**: 30분

---

### Phase 2: 전체 에이전트 검증 (우선순위: 중간)

**목표**: 모든 에이전트의 표준화 상태 확인 및 일관성 검증

**작업 내용**:
1. **각 에이전트별 검증 체크리스트 확인**
   - [ ] `checkDataAccessUsage()` 함수 존재 여부
   - [ ] DB 실제 데이터 확인 로직 존재 여부
   - [ ] `inRulesNotInDataAccess` 계산 로직이 `checkDataAccessUsage()` 사용하는지
   - [ ] HTML 출력에 DB 실제 데이터 섹션 존재 여부
   - [ ] 에이전트별 특화 패턴이 올바르게 구현되었는지

2. **일관성 검증**
   - 변수명 통일 확인 (`$dbDataExists` vs `$dbDataExistsStandard`)
   - HTML 섹션 구조 일관성 확인
   - 에러 처리 패턴 일관성 확인

3. **린터 검증**
   - 모든 파일에 대해 린터 에러 확인
   - PHP 문법 오류 확인

**예상 소요 시간**: 1시간

---

### Phase 3: 표준화 문서 업데이트 (우선순위: 중간)

**목표**: 표준화 작업 완료 후 문서화

**작업 내용**:
1. **STANDARDIZATION_VERIFICATION_REPORT.md 업데이트**
   - agent14~22 추가
   - agent02 표준화 완료 후 추가
   - 전체 통계 업데이트

2. **표준 템플릿 가이드 작성**
   - `STANDARD_TEMPLATE_GUIDE.md` 생성
   - 표준 함수 사용법
   - 에이전트별 특화 패턴 추가 방법
   - HTML 출력 섹션 추가 방법

3. **에이전트별 특화 사항 문서화**
   - 각 에이전트의 특화 테이블 목록
   - 각 에이전트의 특화 패턴 목록

**예상 소요 시간**: 1시간

---

### Phase 4: 추가 개선 사항 검토 (우선순위: 낮음)

**목표**: 표준화 완료 후 추가 개선 가능성 검토

**검토 항목**:
1. **성능 최적화**
   - DB 쿼리 최적화 가능성
   - 캐싱 전략 검토

2. **기능 개선**
   - 데이터 타입 분류 정확도 향상
   - 매핑 불일치 감지 알고리즘 개선
   - 시각화 개선 (차트, 그래프 등)

3. **코드 품질**
   - 공통 함수 추출 (DRY 원칙)
   - 에러 처리 표준화
   - 테스트 코드 작성

**예상 소요 시간**: 2시간 (검토만)

---

## 📋 작업 우선순위

1. **즉시 실행** (Phase 1)
   - agent02 표준화 완료

2. **단기** (Phase 2)
   - 전체 에이전트 검증
   - 표준화 문서 업데이트

3. **중기** (Phase 4)
   - 추가 개선 사항 검토 및 구현

---

## 🔍 agent02 특별 고려사항

agent02는 함수 기반 구조를 사용하므로 다음 사항을 고려해야 합니다:

1. **함수 구조 유지**
   - `analyzeAgent02Data()` 함수 구조 유지
   - 함수 내부에 표준 로직 통합

2. **전역 변수 사용**
   - `$DB`, `$studentid` 등 전역 변수 활용
   - 함수 파라미터로 전달하는 방식 고려

3. **HTML 출력 구조**
   - 기존 HTML 구조 유지
   - 표준 섹션만 추가

---

## ✅ 완료 체크리스트

### agent02 표준화
- [ ] `checkDataAccessUsage()` 함수 추가
- [ ] DB 실제 데이터 확인 로직 추가
- [ ] `inRulesNotInDataAccess` 계산 로직 변경
- [ ] HTML 출력 섹션 추가
- [ ] 린터 에러 확인

### 전체 검증
- [ ] 모든 에이전트 검증 체크리스트 확인
- [ ] 일관성 검증 완료
- [ ] 린터 검증 완료

### 문서화
- [ ] STANDARDIZATION_VERIFICATION_REPORT.md 업데이트
- [ ] STANDARD_TEMPLATE_GUIDE.md 작성
- [ ] 에이전트별 특화 사항 문서화

---

## 📝 참고사항

- 모든 작업은 서버 환경에서 실행되므로 로컬 테스트 불가
- 에러 메시지에 파일 경로와 라인 번호 포함 필수
- PHP 7.1.9, MySQL 5.7 환경 고려
- Moodle 3.7 API 사용

