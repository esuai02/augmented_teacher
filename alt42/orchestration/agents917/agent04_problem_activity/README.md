# Agent04: 문제활동 식별 (Problem Activity Identification)

## 개요
학생의 학습 활동 유형을 식별하고 선택 데이터를 저장하는 에이전트

## 기능
- 7개 주요 활동 카테고리 제공
  - 개념이해, 유형학습, 문제풀이, 오답노트, 질의응답, 복습활동, 포모도르
- 각 카테고리별 4개 하위 활동 항목
- 선택 데이터 실시간 저장
- 향후 행동 유형 설문 확장 예정

## 디렉토리 구조
```
agent04_problem_activity/
├── api/
│   ├── check_db.php       # DB 스키마 확인/생성
│   ├── save_activity.php  # 활동 선택 저장
│   └── get_activity.php   # 활동 이력 조회
├── ui/
│   ├── activity_categories.js  # 카테고리 데이터
│   ├── activity_panel.js       # UI 컴포넌트
│   ├── activity_panel.css      # 스타일
│   └── test_panel.html        # 테스트 페이지
└── interaction_contents/
    ├── docs/     # 문서
    ├── php/      # PHP 로직
    ├── etc/      # 설정
    ├── movies/   # 동영상
    └── sounds/   # 오디오
```

## 데이터베이스 스키마
```sql
Table: mdl_alt42_student_activity
- id: BIGINT (PK)
- userid: BIGINT (FK to mdl_user.id)
- main_category: VARCHAR(100)
- sub_activity: VARCHAR(200)
- behavior_type: VARCHAR(50)
- survey_responses: TEXT (JSON)
- created_at: TIMESTAMP
- updated_at: TIMESTAMP
```

## API 사용법

### 활동 저장
```javascript
await Agent04ActivityCategories.saveSelection('problem_solving', '기출문제 풀이', userId);
```

### 활동 이력 조회
```javascript
const history = await Agent04ActivityCategories.getHistory(userId, 10);
console.log(history.data);
```

### UI 패널 표시
```javascript
Agent04ActivityPanel.selectCategory('problem_solving');
```

## 통합 방법
```html
<link rel="stylesheet" href="path/to/activity_panel.css">
<script src="path/to/activity_categories.js"></script>
<script src="path/to/activity_panel.js"></script>

<script>
// 카테고리 선택 시
Agent04ActivityPanel.selectCategory('problem_solving');
</script>
```

## 향후 확장
- [ ] 행동 유형 설문 추가
- [ ] 설문 응답 저장 (survey_responses 컬럼)
- [ ] 활동 패턴 분석 기능
- [ ] 추천 활동 제안 로직
