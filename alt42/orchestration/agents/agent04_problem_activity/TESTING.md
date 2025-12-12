# Agent04 Testing Guide

## 1. DB 스키마 검증

### 테이블 존재 확인
```bash
curl http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/api/check_db.php
```

**Expected:**
```json
{
  "status": "ok",
  "table_exists": true,
  "table_name": "mdl_alt42_student_activity"
}
```

### 테이블 구조 확인
```sql
DESCRIBE mdl_alt42_student_activity;
```

**Expected:** 8개 컬럼 (id, userid, main_category, sub_activity, behavior_type, survey_responses, created_at, updated_at)

---

## 2. API 기능 테스트

### 활동 저장 테스트
```bash
curl -X POST http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/api/save_activity.php \
  -H "Content-Type: application/json" \
  -d '{
    "userid": 2,
    "main_category": "problem_solving",
    "sub_activity": "기출문제 풀이"
  }'
```

**Expected:**
```json
{
  "status": "ok",
  "message": "Activity saved",
  "id": 1
}
```

### 활동 조회 테스트
```bash
curl "http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/api/get_activity.php?userid=2&limit=10"
```

**Expected:**
```json
{
  "status": "ok",
  "count": 1,
  "data": [{
    "id": 1,
    "userid": 2,
    "main_category": "problem_solving",
    "sub_activity": "기출문제 풀이",
    "created_at": "2025-10-21 22:00:00"
  }]
}
```

---

## 3. UI 컴포넌트 테스트

### 독립 테스트 페이지
1. 브라우저에서 접속:
   `http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/ui/test_panel.html`

2. 테스트 시나리오:
   - ✅ 7개 활동 카테고리 버튼 표시
   - ✅ 카테고리 클릭 시 모달 팝업
   - ✅ 하위 항목 4개씩 표시
   - ✅ 하위 항목 선택 시 저장
   - ✅ 성공 메시지 표시
   - ✅ 2초 후 자동 닫힘

### 통합 테스트 (orchestration_hs2)
1. 브라우저에서 접속:
   `http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration_hs2/index.php`

2. 테스트 시나리오:
   - Step 4까지 진행
   - 활동 카테고리 카드 클릭
   - Agent04 모달 표시 확인
   - 하위 항목 선택 및 저장
   - 우측 패널 결과 표시 확인

---

## 4. 데이터 검증

### DB 직접 조회
```sql
SELECT * FROM mdl_alt42_student_activity
WHERE userid = 2
ORDER BY created_at DESC
LIMIT 10;
```

### JavaScript 콘솔 테스트
```javascript
// 저장 테스트
await Agent04ActivityCategories.saveSelection('qa', '개념 질문', 2);

// 조회 테스트
const history = await Agent04ActivityCategories.getHistory(2);
console.table(history.data);
```

---

## 5. 에러 처리 테스트

### 잘못된 카테고리 키
```javascript
Agent04ActivityPanel.selectCategory('invalid_key');
// Expected: 콘솔 에러 + alert 메시지
```

### API 실패 시뮬레이션
```javascript
// API 경로 임시 변경
Agent04ActivityCategories.apiBasePath = '/invalid/path';
await Agent04ActivityCategories.saveSelection('qa', '개념 질문', 2);
// Expected: 콘솔 에러 + alert 메시지
```

---

## 6. 성능 테스트

### 연속 저장 테스트 (10회)
```javascript
for (let i = 0; i < 10; i++) {
  await Agent04ActivityCategories.saveSelection('problem_solving', '기출문제 풀이', 2);
}
// Expected: 모두 성공, upsert 로직으로 1개 레코드만 업데이트
```

### 페이지 로드 시간
- 브라우저 Network 탭에서 확인
- activity_categories.js, activity_panel.js, activity_panel.css 로드 시간
- Expected: 각 100ms 이내

---

## 7. 회귀 테스트 체크리스트

- [ ] DB 테이블이 정상적으로 생성되는가?
- [ ] API 저장이 정상 동작하는가?
- [ ] API 조회가 정상 동작하는가?
- [ ] UI 모달이 정상 표시되는가?
- [ ] 하위 항목 선택이 정상 동작하는가?
- [ ] 성공 메시지가 정상 표시되는가?
- [ ] 모달이 자동으로 닫히는가?
- [ ] orchestration_hs2 통합이 정상 동작하는가?
- [ ] 에러 처리가 적절한가?
- [ ] 데이터가 DB에 정확히 저장되는가?
