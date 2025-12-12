# 취약점 검사 (Agent 04) 상세 정리

## 📋 개요

**Agent 04 - Inspect Weakpoints**는 학습 활동에서 탐지된 취약점을 상세 분석하여 구체적인 보강 방안을 제시하는 에이전트입니다.

---

## 🎯 핵심 목적

학습 활동(문제/연습) 선택을 최적화하여 효율과 유지력을 높임

---

## 📊 관찰 지표

- **최근 정답률**: 문제 풀이 성공률
- **소요시간**: 문제당 평균 해결 시간
- **난이도 체감**: 학생이 느끼는 난이도 수준
- **피드백 메모**: 학습 중 피드백 및 메모

---

## 🧠 해석/휴리스틱 (난이도 최적화 전략)

### 정답률 기반 난이도 조정

| 정답률 범위 | 상태 | 전략 | 조치 |
|------------|------|------|------|
| **40~70%** | 성장구간 (최적 난이도) | 유지 및 강화 | 연속 20~30분 집중 세션 유지 |
| **40% 미만** | 난이도 과다 | 난이도 하향 조정 | 개념 리프레시 5분 → 쉬운 문제로 워밍업 |
| **80% 초과** | 난이도 부족 | 난이도 상향 조정 | 난이도 상승 또는 융합형 문제 권장 |

### 코칭 템플릿

1. **성장구간 (40~70%)**:
   - "현재 구간은 성장 곡선입니다. 연속 20~30분 집중 세션으로 밀어봅시다."

2. **난이도 과다 (<40%)**:
   - "개념 리마인드 5분 후 쉬운 문제로 감각을 먼저 되살려요."

3. **난이도 부족 (>80%)**:
   - "현재 문제가 너무 쉬워 보입니다. 더 도전적인 문제로 실력을 높여봅시다."

---

## 🗂️ 활동 카테고리 구조

### 7개 주요 활동 카테고리

#### 1. 📚 개념이해 (concept_understanding)
- 핵심 개념 정리
- 공식 유도 과정
- 개념 간 연결
- 실생활 적용 예시

#### 2. 🎯 유형학습 (type_learning)
- 기본 유형 문제
- 응용 유형 문제
- 심화 유형 문제
- 신유형 문제

#### 3. ✏️ 문제풀이 (problem_solving)
- 기출문제 풀이
- 모의고사 풀이
- 단원별 문제
- 종합 문제

#### 4. 📝 오답노트 (error_notes)
- 오답 원인 분석
- 유사 문제 연습
- 개념 재정리
- 실수 방지 체크리스트

#### 5. 💬 질의응답 (qa)
- 개념 질문
- 문제 풀이 질문
- 학습 방법 상담
- 진로 상담

#### 6. 🔄 복습활동 (review)
- 일일 복습
- 주간 복습
- 단원 총정리
- 시험 대비 복습

#### 7. ⏰ 포모도르 (pomodoro)
- 25분 집중 학습
- 5분 휴식
- 긴 휴식 (15분)
- 일일 목표 설정

---

## 💾 데이터베이스 스키마

### 테이블: `mdl_alt42_student_activity`

```sql
CREATE TABLE IF NOT EXISTS mdl_alt42_student_activity (
    id BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT(10) UNSIGNED NOT NULL,
    main_category VARCHAR(100) NOT NULL,          -- 7개 카테고리 중 하나
    sub_activity VARCHAR(200),                    -- 하위 활동 항목
    behavior_type VARCHAR(50),                    -- 행동 유형 (향후 확장)
    survey_responses TEXT,                        -- 설문 응답 (JSON)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_userid (userid),
    INDEX idx_category (main_category),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Agent04: 학생 활동 선택 및 행동 유형 데이터';
```

### 필드 설명

- **id**: 레코드 고유 ID
- **userid**: 학생 ID (mdl_user.id 참조)
- **main_category**: 메인 카테고리 (concept_understanding, type_learning, problem_solving, error_notes, qa, review, pomodoro)
- **sub_activity**: 선택한 하위 활동 항목
- **behavior_type**: 행동 유형 (향후 설문으로 확장 예정)
- **survey_responses**: 설문 응답 JSON 데이터 (향후 확장)
- **created_at**: 최초 생성 시각
- **updated_at**: 최종 수정 시각

### Upsert 로직

- 같은 날짜에 같은 `main_category` 선택 시 기존 레코드 업데이트
- 다른 날짜 또는 다른 카테고리 선택 시 신규 레코드 생성

---

## 🔌 API 엔드포인트

### 1. DB 스키마 확인/생성
**GET** `/api/check_db.php`

**응답 예시**:
```json
{
  "status": "ok",
  "table_exists": true,
  "table_name": "mdl_alt42_student_activity",
  "columns": [...]
}
```

### 2. 활동 선택 저장
**POST** `/api/save_activity.php`

**요청 본문**:
```json
{
  "userid": 123,
  "main_category": "problem_solving",
  "sub_activity": "기출문제 풀이",
  "behavior_type": null,
  "survey_responses": null
}
```

**응답 예시**:
```json
{
  "status": "ok",
  "message": "Activity saved",
  "id": 1,
  "data": {
    "id": 1,
    "userid": 123,
    "main_category": "problem_solving",
    "sub_activity": "기출문제 풀이"
  }
}
```

### 3. 활동 이력 조회
**GET** `/api/get_activity.php?userid={id}&limit={n}`

**응답 예시**:
```json
{
  "status": "ok",
  "count": 5,
  "data": [
    {
      "id": 1,
      "userid": 123,
      "main_category": "problem_solving",
      "sub_activity": "기출문제 풀이",
      "created_at": "2025-11-03 10:30:00",
      "survey_responses": null
    }
  ]
}
```

---

## 🎨 UI 컴포넌트

### JavaScript 모듈

#### 1. `activity_categories.js`
- 카테고리 데이터 정의
- API 호출 함수
- 데이터 조회 함수

**주요 함수**:
- `getCategory(categoryKey)`: 카테고리 정보 조회
- `getSubItems(categoryKey)`: 하위 항목 목록 조회
- `getAllCategories()`: 모든 카테고리 목록
- `saveSelection(categoryKey, subItem, userId)`: 활동 선택 저장
- `getHistory(userId, limit)`: 활동 이력 조회

#### 2. `activity_panel.js`
- 모달 UI 렌더링
- 사용자 인터랙션 처리
- 결과 표시

**주요 함수**:
- `selectCategory(categoryKey)`: 메인 카테고리 선택
- `showSubItemsModal(category, categoryKey)`: 하위 항목 모달 표시
- `selectSubItem(categoryKey, subItem)`: 하위 항목 선택 및 저장
- `closeModal()`: 모달 닫기

### CSS 스타일

#### 모달 스타일 (`activity_panel.css`)
- 모달 오버레이 (fadeIn 애니메이션)
- 모달 콘텐츠 (slideUp 애니메이션)
- 하위 항목 그리드 레이아웃
- 성공 메시지 스타일
- 반응형 디자인 (모바일 대응)

---

## 🔄 사용 흐름

### 1. 활동 선택 프로세스

```
사용자 → 메인 카테고리 선택
  ↓
모달 팝업 (하위 항목 4개 표시)
  ↓
하위 항목 선택
  ↓
API 호출 (save_activity.php)
  ↓
DB 저장/업데이트
  ↓
성공 메시지 표시
  ↓
2초 후 자동 닫힘
```

### 2. 데이터 수집 흐름

```
학생 활동 선택
  ↓
mdl_alt42_student_activity 테이블 저장
  ↓
정답률, 소요시간, 난이도 체감 수집
  ↓
난이도 최적화 분석
  ↓
문제 추천 전략 생성
```

---

## 📈 난이도 최적화 알고리즘

### 성장구간 식별 로직

```pseudo
function identifyGrowthZone(accuracy, difficulty_feeling, time_spent):
    if accuracy >= 0.40 AND accuracy <= 0.70:
        return "growth_zone"  // 최적 난이도
    else if accuracy < 0.40:
        return "too_hard"      // 난이도 과다
    else if accuracy > 0.80:
        return "too_easy"      // 난이도 부족
```

### 난이도 조정 전략

#### 정답률 < 40% (난이도 과다)
1. 개념 리프레시 (5분)
2. 쉬운 문제로 워밍업
3. 단계적 난이도 상승

#### 정답률 40~70% (성장구간)
1. 현재 난이도 유지
2. 연속 20~30분 집중 세션
3. 동일 유형 문제 연속 학습

#### 정답률 > 80% (난이도 부족)
1. 난이도 상승
2. 융합형 문제 권장
3. 심화 문제 도전

---

## 🎯 수행 가능한 액션

### 문제 추천
- `recommend_problems`: 정답률 기반 문제 추천
- `adjust_difficulty`: 난이도 자동 조정
- `suggest_problem_types`: 학습 단계별 문제 유형 제안

### 학습 전략
- `suggest_warmup`: 워밍업 문제 제안 (정답률 낮을 때)
- `recommend_concept_review`: 개념 복습 권장
- `create_practice_session`: 맞춤형 연습 세션 생성

### 분석
- `analyze_accuracy_pattern`: 정답률 패턴 분석 (트렌드 파악)
- `identify_growth_zone`: 성장구간(40~70%) 식별 및 유지

---

## 💬 자동 응답 가능한 질문

### 활동 선택 관련
- "다음에 풀 문제는 뭐가 좋을까요?"
- "현재 실력에 맞는 문제 난이도는?"
- "지금 어떤 유형의 문제를 풀어야 해?"

### 성과 분석 관련
- "최근 문제 풀이 성과는 어떤가요?"
- "정답률이 낮은데 어떻게 해야 해?"
- "너무 쉬운 문제만 풀고 있는 것 같아요"

### 전략 관련
- "성장 구간 문제는 어떻게 찾아요?"
- "난이도 조정이 필요한가요?"

---

## 🔗 통합 방법

### HTML에서 사용

```html
<!-- CSS 포함 -->
<link rel="stylesheet" href="/path/to/activity_panel.css">

<!-- JavaScript 모듈 포함 -->
<script src="/path/to/activity_categories.js"></script>
<script src="/path/to/activity_panel.js"></script>

<!-- 사용 예시 -->
<script>
// 카테고리 선택 시
Agent04ActivityPanel.selectCategory('problem_solving');

// 직접 저장
await Agent04ActivityCategories.saveSelection(
    'problem_solving', 
    '기출문제 풀이', 
    userId
);

// 이력 조회
const history = await Agent04ActivityCategories.getHistory(userId, 10);
</script>
```

---

## 📂 파일 구조

```
agent04_problem_activity/
├── api/
│   ├── check_db.php          # DB 스키마 확인/생성
│   ├── save_activity.php     # 활동 선택 저장
│   └── get_activity.php      # 활동 이력 조회
├── ui/
│   ├── activity_categories.js    # 카테고리 데이터 및 API 함수
│   ├── activity_panel.js         # UI 컴포넌트
│   ├── activity_panel.css        # 스타일시트
│   └── test_panel.html           # 독립 테스트 페이지
├── rules/
│   ├── mission.md                # 목적 및 관찰 지표
│   ├── questions.md             # 자동 응답 질문 세트
│   ├── actions.md                # 수행 가능한 액션
│   ├── data.php                  # 데이터 수집 함수
│   ├── rules.yaml                # 룰 정의 (빈 템플릿)
│   ├── strategy_step1.md         # 전략 1단계
│   └── strategy_step2.md         # 전략 2단계
├── interaction_contents/
│   ├── docs/                     # 문서
│   ├── php/                      # PHP 로직
│   ├── etc/                      # 설정
│   ├── movies/                   # 동영상
│   └── sounds/                   # 오디오
├── agent04_problem_activity.md   # 지식 파일
├── README.md                     # 프로젝트 개요
├── TEST_REPORT.md                # 테스트 리포트
└── TESTING.md                    # 테스트 가이드
```

---

## 🧪 테스트 정보

### 테스트 URL

1. **독립 UI 테스트**:
   ```
   http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/ui/test_panel.html
   ```

2. **통합 테스트**:
   ```
   http://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration_hs2/index.php
   ```

### 테스트 시나리오

1. ✅ 7개 활동 카테고리 버튼 표시 확인
2. ✅ 카테고리 클릭 시 모달 팝업 확인
3. ✅ 하위 항목 4개씩 표시 확인
4. ✅ 하위 항목 선택 시 DB 저장 확인
5. ✅ 성공 메시지 표시 확인
6. ✅ 2초 후 자동 닫힘 확인
7. ✅ ESC 키로 모달 닫기 확인

---

## 🚀 향후 확장 계획

### 단기 계획
- [ ] 행동 유형 설문 추가
- [ ] 설문 응답 저장 (`survey_responses` 컬럼 활용)
- [ ] 활동 패턴 분석 기능

### 중기 계획
- [ ] 추천 활동 제안 로직 (정답률 기반)
- [ ] 학습 효율성 대시보드
- [ ] 다른 에이전트와의 연계 (Agent 05 학습 감정, Agent 09 학습 관리 등)

### 장기 계획
- [ ] AI 기반 문제 추천 엔진
- [ ] 개인화된 학습 경로 생성
- [ ] 실시간 난이도 자동 조정

---

## 📊 데이터 분석 예시

### 정답률 패턴 분석

```
최근 10개 문제 풀이:
- 정답률: 45% (성장구간 유지)
- 평균 소요시간: 8분/문제
- 난이도 체감: "적당함"
→ 전략: 현재 난이도 유지, 집중 세션 연장
```

### 난이도 조정 필요 시

```
최근 10개 문제 풀이:
- 정답률: 35% (너무 어려움)
- 평균 소요시간: 15분/문제
- 난이도 체감: "어려움"
→ 전략: 개념 리프레시 5분 → 쉬운 문제로 워밍업
```

---

## 🔍 관련 에이전트 연계

### Agent 05 - Learning Emotion
- 감정 상태와 활동 선택의 상관관계 분석
- 좌절 감정 시 쉬운 문제로 전환

### Agent 09 - Learning Management
- 활동 선택 패턴과 학습 관리 전략 연계
- 출결 패턴과 활동 유형 매칭

### Agent 11 - Problem Notes
- 오답노트 활동과 문제 풀이 활동 연계
- 오류 패턴 기반 문제 추천

---

## 📝 코칭 메시지 템플릿

### 성장구간 유지 (40~70%)
- "현재 구간은 성장 곡선입니다. 연속 20~30분 집중 세션으로 밀어봅시다."
- "이 난이도가 최적입니다. 계속 이대로 진행하세요."

### 난이도 과다 (<40%)
- "개념 리마인드 5분 후 쉬운 문제로 감각을 먼저 되살려요."
- "문제가 어려워 보입니다. 기초 개념부터 다시 확인해봅시다."

### 난이도 부족 (>80%)
- "현재 문제가 너무 쉬워 보입니다. 더 도전적인 문제로 실력을 높여봅시다."
- "난이도를 높여 심화 문제에 도전해볼까요?"

---

## 🎓 참고 자료

- **지식 파일**: `agent04_problem_activity.md`
- **API 문서**: `api/` 폴더 내 PHP 파일들
- **UI 문서**: `ui/` 폴더 내 JavaScript 파일들
- **테스트 문서**: `TEST_REPORT.md`, `TESTING.md`
- **의사결정 지식**: `의사결정 지식.md` (작성 예정)

---

**작성일**: 2025-11-03  
**최종 업데이트**: 2025-11-03  
**버전**: 1.0

