# Agent 15: 문제 재정의 & 개선방안 - UI 컴포넌트

## 📌 개요

이 폴더는 `orchestration`의 Step 15 "문제 재정의 가져오기" 기능을 `agent15_problem_redefinition` 폴더로 이식한 것입니다.

**주요 기능:**
- "문제 재정의 가져오기" 버튼 클릭 시 자동으로 GPT API 호출
- Step 2, 3, 4, 5, 6, 14의 데이터를 수집하여 문제 재정의 생성
- 생성된 내용을 우측 패널에 표시
- 사용자가 내용을 수정 가능
- 로컬 스토리지에 저장 기능

## 📁 파일 구조

```
/ui/
├── problem_redefinition_panel.php     # UI 패널 컴포넌트 (PHP)
├── problem_redefinition_functions.js  # JavaScript 기능
├── index.php                          # 통합 예제 페이지
└── README.md                          # 이 문서
```

## 🚀 사용 방법

### 1. 독립 실행 방식 (권장)

브라우저에서 직접 접속:

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/index.php
```

**동작 순서:**
1. 좌측 패널에서 "문제 재정의 & 개선방안" 카드 클릭
2. 우측 패널이 나타남
3. "문제 재정의 가져오기" 버튼 클릭
4. GPT API로 자동 생성된 내용이 텍스트박스에 표시됨
5. 필요시 내용 수정 가능
6. "저장" 버튼으로 로컬 스토리지에 저장

### 2. 다른 페이지에서 컴포넌트 포함

```php
<?php
// PHP 페이지에서 컴포넌트 포함
include '/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/problem_redefinition_panel.php';
?>

<!-- JavaScript 함수 포함 -->
<script>
    window.currentUserId = <?php echo $USER->id; ?>;
</script>
<script src="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/problem_redefinition_functions.js"></script>
```

## 🔧 기술 스택

- **Backend:** PHP 7.1.9, Moodle 3.7
- **Frontend:** Vanilla JavaScript (ES6+)
- **Data Storage:** LocalStorage (로컬 저장)
- **API:**
  - `collect_workflow_data.php` - 데이터 수집
  - `problem_redefinition_api.php` - GPT API 호출

## 📊 데이터 흐름

```
[카드 클릭]
    ↓
[우측 패널 표시]
    ↓
["문제 재정의 가져오기" 버튼 클릭]
    ↓
[collect_workflow_data.php - Step 2~14 데이터 수집]
    ↓
[problem_redefinition_api.php - GPT API 호출]
    ↓
[생성된 내용을 텍스트박스에 표시]
    ↓
[사용자 수정 가능]
    ↓
["저장" 버튼 클릭 → LocalStorage 저장]
```

## 🎨 UI 구조

### 좌측 패널 (Left Panel)
- 카드 목록
- 클릭 시 우측 패널 활성화

### 우측 패널 (Right Panel)
- 제목: "🔄 문제 재정의 & 개선방안"
- 텍스트박스: 자동 생성된 내용 표시 및 수정
- 버튼:
  - "📊 문제 재정의 가져오기" - GPT API 호출
  - "💾 저장" - 로컬 스토리지 저장
- 상태 메시지: 성공/실패/진행 중 표시

## ⚙️ 설정

### currentUserId 설정

JavaScript에서 `window.currentUserId` 변수를 설정해야 합니다:

```javascript
window.currentUserId = <?php echo $USER->id; ?>;
```

### API 경로

현재 `orchestration` 폴더의 API를 사용합니다:

```javascript
// 데이터 수집 API
/moodle/local/augmented_teacher/alt42/orchestration/api/collect_workflow_data.php

// GPT API
/moodle/local/augmented_teacher/alt42/orchestration/api/problem_redefinition_api.php
```

## 🐛 에러 처리

모든 에러 메시지에 파일명과 라인 번호가 포함됩니다:

```javascript
throw new Error('데이터 수집 실패 (file: problem_redefinition_functions.js, line: 48)');
```

이를 통해 디버깅이 용이합니다.

## 💾 로컬 스토리지 키

```javascript
agent15_redefinition_{userId}
```

예: `agent15_redefinition_2`

## 🔍 디버깅

브라우저 콘솔에서 다음 로그를 확인할 수 있습니다:

```javascript
// 초기화 로그
Agent 15: 문제 재정의 패널 초기화 시작...
Agent 15: 초기화 완료

// 데이터 가져오기 로그
📊 Agent 15: 데이터 수집 시작...
✅ 데이터 수집 완료
✅ GPT 분석 완료
✅ agent15-problem-redefinition-text에 설정 완료

// 저장 로그
✅ 로컬 스토리지 저장 완료
```

## 📝 개발 노트

### orchestration_hs2와의 차이점

1. **네이밍:** 모든 ID와 함수명에 `agent15-` 접두사 추가
2. **파일 구조:** UI 폴더에 독립적으로 배치
3. **API 경로:** orchestration 폴더의 새로운 API 사용 (collect_workflow_data.php, problem_redefinition_api.php)
4. **스토리지 키:** `step15_` → `agent15_`로 변경

### 향후 개선 사항

- [x] agent15 전용 API 엔드포인트 생성 (완료)
- [ ] DB 저장 기능 추가 (현재는 로컬 스토리지만 사용)
- [ ] 지도 모드 선택 기능 통합
- [ ] 여러 개의 문제 재정의 버전 관리

## 🔗 관련 파일

- 참고 원본: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration_hs2/components/step15_problem_redefinition.php`
- 현재 위치: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/ui/`
- API 위치: `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/api/`

## 📞 문의

- 에러 발생 시 브라우저 콘솔 로그 확인
- 파일 경로와 라인 번호를 함께 보고

---

**Last Updated:** 2025-10-21
**Version:** 1.0
**Author:** Claude Code
