# Teacher Feedback Panel - Implementation Guide

**Version**: 1.0
**Date**: 2025-10-21
**Author**: Claude Code

## 📋 개요

교사 피드백 패널은 학생의 수학일기 데이터를 기간별로 조회하고 종합 피드백을 생성하는 재사용 가능한 UI 컴포넌트입니다.

### 핵심 기능

1. **수학일기 조회** (`최근 교사 기록` 버튼)
   - **`mdl_abessi_todayplans`** 테이블에서 학생의 수학일기 데이터 조회
   - **포모도로 세션별 피드백 (fback01~fback16)** 표시
   - 6가지 기간 옵션: 오늘, 일주일, 2주일, 3주일, 4주일, 3개월
   - 학습 계획, 소요시간, URL, 만족도, **교사/AI 피드백** 표시

2. **메모장 전달 내용 조회**
   - **`mdl_abessi_stickynotes`** 테이블에서 메모 데이터 조회
   - 5가지 타입: 포모도로, 컨텐츠 페이지, 목표설정, 내공부방, 공부결과
   - 타입별 색상 구분 및 필터링

3. **종합 피드백** (`종합 피드백` 버튼)
   - 여러 데이터 소스를 통합하여 종합 리포트 생성
   - 기간별 데이터 집계 및 분석

4. **기간별 필터링**
   - 버튼 클릭으로 기간 변경
   - 선택된 기간만 데이터 조회

5. **탭 기반 UI**
   - 📚 수학일기 & 피드백 탭
   - 📝 메모장 전달 내용 탭

---

## 📁 파일 구조

```
/alt42/orchestration/agents/
├── common/
│   ├── api/
│   │   ├── get_math_diary.php          # 수학일기 API 엔드포인트
│   │   └── comprehensive_feedback.php  # 종합 피드백 API
│   └── components/
│       └── teacher_feedback_panel.php  # 재사용 UI 컴포넌트
│
└── agent01_onboarding/
    ├── index.php                        # Agent UI (통합 예시)
    └── agent.php                        # Agent API
```

---

## 🔧 사용 방법

### 1. Agent UI에 컴포넌트 통합

```php
<?php
// Agent UI 파일 (예: agent01_onboarding/index.php)

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET["userid"] ?? $USER->id;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agent UI</title>
</head>
<body>
    <div class="container">
        <!-- 교사 피드백 패널 포함 -->
        <?php
        include_once(__DIR__ . '/../common/components/teacher_feedback_panel.php');
        ?>
    </div>
</body>
</html>
```

### 2. 필수 전제 조건

- **Moodle 로그인 필수**: `require_login()` 호출 필요
- **studentid 변수 설정**: `$studentid` 변수가 정의되어 있어야 함
- **DB 접근 권한**: `mdl_abessi_todayplans` 테이블 읽기 권한 필요

---

## 📊 데이터베이스 스키마

### mdl_abessi_todayplans

수학일기 데이터를 저장하는 테이블 (포모도로 세션)

| 필드 | 타입 | 설명 |
|------|------|------|
| `userid` | int(11) | 학생 ID (FK: mdl_user.id) |
| `timecreated` | int(11) | 작성 시간 (Unix timestamp) |
| `plan1` ~ `plan16` | text | 학습 계획 내용 (최대 16개) |
| `due1` ~ `due16` | int(11) | 소요 시간(분) |
| `url1` ~ `url16` | text | 관련 URL |
| `status01` ~ `status16` | text | 만족도/상태 |
| **`fback01` ~ `fback16`** | text | **교사/AI 자동생성 피드백** ⭐ NEW |

**쿼리 예시:**
```sql
SELECT * FROM mdl_abessi_todayplans
WHERE userid = 2
AND timecreated >= 1737417600  -- 기간 시작 (Unix timestamp)
AND timecreated <= 1737503999  -- 기간 종료 (Unix timestamp)
ORDER BY timecreated DESC;
```

### mdl_abessi_stickynotes

메모장 전달 내용을 저장하는 테이블

| 필드 | 타입 | 설명 |
|------|------|------|
| `id` | int(11) | 메모 ID (PK) |
| `userid` | int(11) | 학생 ID (FK: mdl_user.id) |
| `type` | varchar(50) | 메모 타입 (아래 참조) |
| `content` | text | 메모 내용 |
| `created_at` | varchar/int | 작성 시간 (datetime 또는 Unix timestamp) |
| `updated_at` | varchar/int | 수정 시간 |

**메모 타입:**
- `timescaffolding`: 포모도로 (색상: 보라)
- `chapter`: 컨텐츠 페이지 (색상: 파랑)
- `edittoday`: 목표설정 (색상: 초록)
- `mystudy`: 내공부방 (색상: 주황)
- `today`: 공부결과 (색상: 빨강)

**쿼리 예시:**
```sql
SELECT * FROM mdl_abessi_stickynotes
WHERE userid = 2
AND type IN ('timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today')
ORDER BY id DESC;
```

---

## 🌐 API 엔드포인트

### 1. get_math_diary.php

**URL**: `/moodle/local/augmented_teacher/alt42/orchestration/agents/common/api/get_math_diary.php`

**Method**: POST

**Request Body**:
```json
{
  "action": "getMathDiary",
  "period": "today",
  "user_id": 2
}
```

**Period Options**:
- `"today"` - 오늘 00:00 ~ 23:59
- `"week"` - 7일 전 00:00 ~ 오늘 23:59
- `"2weeks"` - 14일 전 00:00 ~ 오늘 23:59
- `"3weeks"` - 21일 전 00:00 ~ 오늘 23:59
- `"4weeks"` - 28일 전 00:00 ~ 오늘 23:59
- `"3months"` - 3개월 전 00:00 ~ 오늘 23:59

**Response**:
```json
{
  "success": true,
  "period": "today",
  "date_from": "2025-01-21 00:00:00",
  "date_to": "2025-01-21 23:59:59",
  "diary_entries": [
    {
      "timecreated": 1737417600,
      "date": "2025-01-21",
      "time": "09:00:00",
      "plans": [
        {
          "index": 1,
          "plan": "방정식 복습",
          "duration": 30,
          "url": "https://...",
          "status": "만족",
          "feedback": "잘 이해했습니다. 다음은 응용문제를 풀어보세요."
        }
      ]
    }
  ],
  "sticky_notes": [
    {
      "id": 123,
      "type": "timescaffolding",
      "type_label": "포모도로",
      "content": "오늘 학습 목표를 잘 달성했습니다.",
      "created_at": "2025-01-21 10:30:00",
      "timestamp": 1737422400,
      "date": "2025-01-21",
      "time": "10:30:00"
    }
  ],
  "total_diary_count": 1,
  "total_notes_count": 1,
  "debug": { ... }
}
```

**Error Response**:
```json
{
  "success": false,
  "error": "Error message",
  "file": "get_math_diary.php",
  "line": 237
}
```

### 2. comprehensive_feedback.php

**URL**: `/moodle/local/augmented_teacher/alt42/orchestration/agents/common/api/comprehensive_feedback.php`

**Method**: POST

**Request Body**:
```json
{
  "action": "getComprehensiveFeedback",
  "period": "week",
  "user_id": 2
}
```

**Response**: (orchestration_hs2와 동일한 형식)

---

## 🎨 UI 컴포넌트 구조

### teacher_feedback_panel.php

```
┌─────────────────────────────────────────┐
│  🔍 최근 교사 기록  📊 종합 피드백      │  ← 액션 버튼
├─────────────────────────────────────────┤
│  [오늘] [일주일] [2주일] [3주일] [4주일] [3개월]  │  ← 기간 선택
├─────────────────────────────────────────┤
│                                         │
│  📚 수학일기 요약                        │
│  ┌─────┬─────┬─────┐                   │
│  │  0  │  0  │  0  │                   │
│  │전체 │계획 │시간 │                   │
│  └─────┴─────┴─────┘                   │
│                                         │
│  📅 2025-01-21      09:00:00           │
│  ┌─────────────────────────────────┐   │
│  │ 1. 방정식 복습        30분      │   │
│  │ 만족도: 만족                     │   │
│  │ 🔗 링크                          │   │
│  └─────────────────────────────────┘   │
│                                         │
└─────────────────────────────────────────┘
```

### 주요 HTML 요소

| ID | 설명 |
|----|------|
| `#loadMathDiary` | 수학일기 불러오기 버튼 |
| `#comprehensiveFeedback` | 종합 피드백 버튼 |
| `.period-btn` | 기간 선택 버튼 (6개) |
| `#feedback-loading` | 로딩 인디케이터 |
| `#math-diary-display` | 수학일기 표시 영역 |
| `#comprehensive-display` | 종합 피드백 표시 영역 |
| `#empty-feedback` | 빈 상태 메시지 |

---

## 🔍 코드 참조 위치

### get_math_diary.php

| 기능 | 라인 | 설명 |
|------|------|------|
| Moodle 통합 | 12-30 | config.php 로드 및 로그인 체크 |
| 기간 계산 | 74-119 | `calculate_period_range()` 함수 |
| 데이터 파싱 | 127-157 | `parse_diary_plans()` 함수 |
| 메인 쿼리 | 174-188 | mdl_abessi_todayplans 쿼리 |
| 에러 처리 | 237-244 | Exception 처리 및 JSON 응답 |

### teacher_feedback_panel.php

| 기능 | 라인 | 설명 |
|------|------|------|
| 기간 선택 버튼 | 52-86 | 6개 기간 버튼 HTML |
| 수학일기 표시 | 98-130 | 일기 리스트 영역 |
| 이벤트 리스너 | 197-224 | 버튼 클릭 이벤트 |
| API 호출 | 230-265 | `loadMathDiary()` 함수 |
| HTML 생성 | 318-349 | `createDiaryEntryHtml()` 함수 |

---

## ✅ 통합 체크리스트

Agent에 교사 피드백 패널을 통합할 때 확인할 사항:

- [ ] Moodle config.php include 확인
- [ ] `require_login()` 호출 확인
- [ ] `$studentid` 변수 정의 확인
- [ ] 컴포넌트 파일 경로 확인 (`__DIR__ . '/../common/components/teacher_feedback_panel.php'`)
- [ ] API 엔드포인트 URL 경로 확인
- [ ] 브라우저 콘솔에서 JavaScript 에러 확인
- [ ] 네트워크 탭에서 API 응답 확인
- [ ] DB 권한 확인 (mdl_abessi_todayplans 읽기)

---

## 🐛 트러블슈팅

### 문제 1: "데이터가 없습니다" 메시지

**원인**:
- 선택한 기간에 실제로 데이터가 없음
- 기간 계산 오류
- DB 쿼리 권한 부족

**해결**:
1. 브라우저 콘솔에서 API 응답 확인
2. `debug` 필드에서 `total_records` 확인
3. DB에서 직접 쿼리 실행하여 데이터 존재 여부 확인

### 문제 2: API 호출 실패

**원인**:
- URL 경로 오류
- Moodle 로그인 세션 만료
- CORS 오류

**해결**:
1. 네트워크 탭에서 실제 요청 URL 확인
2. 401 에러: 로그인 세션 확인
3. 404 에러: API 파일 경로 확인

### 문제 3: 데이터 파싱 오류

**원인**:
- `timecreated` 필드 포맷 오류
- plan/due/status 필드 누락

**해결**:
1. API 응답의 `debug.raw_data_sample` 확인
2. DB 테이블 구조 확인 (DESCRIBE mdl_abessi_todayplans)

---

## 📝 다른 Agent에 통합하기

### Step 1: UI 파일 생성

각 Agent 폴더에 `index.php` 파일 생성:

```bash
# Agent 폴더 이동
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/

# index.php 생성
touch index.php
```

### Step 2: index.php 기본 구조

```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$studentid = $_GET["userid"] ?? $USER->id;
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Agent UI</title>
</head>
<body>
    <div class="container">
        <!-- Agent 고유 컨텐츠 -->
        <div class="panel">
            <h2>Agent 메인 컨텐츠</h2>
            <!-- ... -->
        </div>

        <!-- 교사 피드백 패널 -->
        <div class="panel">
            <h2>👨‍🏫 교사 피드백</h2>
            <?php
            include_once(__DIR__ . '/../common/components/teacher_feedback_panel.php');
            ?>
        </div>
    </div>
</body>
</html>
```

### Step 3: 테스트

브라우저에서 접속:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent04_problem_activity/index.php?userid=2
```

---

## 🎯 향후 개선 사항

1. **캐싱 구현**
   - Redis/Memcached를 사용한 API 응답 캐싱
   - 동일한 기간 요청 시 DB 쿼리 생략

2. **페이지네이션**
   - 대량 데이터 처리를 위한 페이지네이션 추가
   - 무한 스크롤 구현

3. **필터링 옵션 확장**
   - 만족도별 필터링
   - 학습 시간별 정렬
   - 검색 기능

4. **데이터 시각화**
   - Chart.js를 이용한 그래프 추가
   - 학습 시간 추이 차트
   - 만족도 분포 차트

5. **알림 기능**
   - 신규 피드백 알림
   - 주간/월간 리포트 자동 생성

---

## 📞 문의

구현 관련 질문이나 버그 리포트:
- GitHub Issues
- Email: support@mathking.kr

---

**Last Updated**: 2025-10-21
**Version**: 1.0
