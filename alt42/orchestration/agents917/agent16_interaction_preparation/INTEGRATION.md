# Agent 16 Interaction Preparation - Integration Guide

## 개요

Agent 16 (상호작용 준비) 패널을 main orchestration system에 통합하는 가이드입니다.

## 파일 구조

```
/agents/agent16_interaction_preparation/
├── index.php                    # Standalone demo page
├── INTEGRATION.md               # This file
├── ui/
│   ├── panel.js                 # Main panel controller
│   └── panel.css                # Panel stylesheet
├── api/
│   ├── generate_scenario.php   # GPT-4o scenario generation
│   ├── save_scenario.php        # Save scenario to DB
│   ├── list_scenarios.php       # Retrieve saved scenarios
│   └── delete_scenario.php      # Delete scenario
└── db/
    └── migration_create_scenarios_table.php  # DB setup script
```

## 데이터베이스 설정

### Step 1: 테이블 생성

브라우저에서 아래 URL을 방문하여 데이터베이스 테이블을 생성하세요:

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/db/migration_create_scenarios_table.php
```

관리자 권한이 필요합니다. 실행 후 다음 테이블이 생성됩니다:
- `agent16_interaction_scenarios`

### Step 2: GPT API 키 설정 (선택사항)

Moodle 관리자 설정에서 GPT API 키를 설정하세요:

```php
// Moodle admin: Site administration > Plugins > Local plugins > Augmented Teacher
set_config('gpt_api_key', 'your-openai-api-key', 'local_augmented_teacher');
```

**참고**: API 키가 없어도 클라이언트 사이드 폴백 시나리오가 생성됩니다.

## Main Orchestration 통합

### orchestration_hs2/index.php 수정

`orchestration_hs2/index.php` 파일에 다음 코드를 추가하세요:

#### 1. CSS 로드 (HEAD 섹션에 추가)

```html
<!-- Agent 16 Interaction Preparation Panel -->
<link rel="stylesheet" href="../orchestration/agents/agent16_interaction_preparation/ui/panel.css?v=<?php echo time(); ?>">
```

#### 2. JavaScript 로드 (BODY 끝부분에 추가)

```html
<!-- Agent 16 Interaction Preparation Panel -->
<script src="../orchestration/agents/agent16_interaction_preparation/ui/panel.js?v=<?php echo time(); ?>"></script>
```

**추천 위치**: Step 16 관련 스크립트 근처 (약 87-90번째 줄 근처)

```html
  <!-- Step 16 interaction scenario generation -->
  <script src="assets/js/step16_interaction_scenario.js?v=<?php echo time(); ?>"></script>

  <!-- Agent 16 Panel (NEW) -->
  <script src="../orchestration/agents/agent16_interaction_preparation/ui/panel.css?v=<?php echo time(); ?>"></script>
  <script src="../orchestration/agents/agent16_interaction_preparation/ui/panel.js?v=<?php echo time(); ?>"></script>

  <!-- Step 15 & 16 handlers -->
  <script src="assets/js/step15_step16_handlers.js?v=<?php echo time(); ?>"></script>
```

## Step 16 UI 연결

### workflow_render.js 또는 step16_handler에서 패널 열기

Step 16 카드를 클릭했을 때 패널을 여는 코드 추가:

```javascript
// Step 16 카드 클릭 핸들러 예시
function handleStep16Click() {
    if (typeof InteractionPreparationPanel !== 'undefined') {
        InteractionPreparationPanel.open(window.currentUserId);
    } else {
        console.error('❌ Agent 16 Panel not loaded');
    }
}
```

### 기존 step16 UI와 통합

기존 `step16_interaction_scenario.js`를 대체하거나 병합할 수 있습니다:

```javascript
// Option 1: 기존 코드 대체
// step16_interaction_scenario.js의 UI 생성 코드를 주석 처리하고
// InteractionPreparationPanel.open()으로 대체

// Option 2: 병합
// 기존 UI와 새 패널을 함께 사용 (탭 추가 방식)
```

## 사용 방법

### Standalone 테스트

```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/index.php
```

### JavaScript API

```javascript
// 패널 열기
InteractionPreparationPanel.open(userid);

// 패널 닫기
InteractionPreparationPanel.close();

// 특정 탭으로 이동
InteractionPreparationPanel.switchTab('mode');      // 상호작용 모드 탭
InteractionPreparationPanel.switchTab('scenario');  // 시나리오 생성 탭
InteractionPreparationPanel.switchTab('result');    // 생성 결과 탭
```

## 워크플로우

### 1. 모드 선택
- 9가지 상호작용 모드 카드 표시 (커리큘럼, 맞춤학습, 시험대비, 단기미션, 자기성찰, 자기주도, 도제학습, 시간성찰, 탐구학습)
- 모드 선택 시 하단에 해당 모드의 GPT 대화 링크 표시
- "상세보기" 버튼으로 각 모드의 전체 설명 확인 가능

### 2. 시나리오 생성
- VibeCoding 프롬프트: 학생의 감정 상태, 학습 맥락, 성향 입력
- DBTracking 프롬프트: 학습 이력, 오답 패턴, 진도 현황 입력
- "시나리오 생성" 버튼 클릭 시:
  - GPT-4o API 호출 (API 키가 설정된 경우)
  - API 실패 시 폴백 시나리오 자동 생성
- 생성된 시나리오는 마크다운으로 렌더링됨
- 복사 및 저장 기능 제공

### 3. 생성 결과
- 저장된 모든 시나리오 목록 표시
- 각 시나리오별 액션:
  - 👁️ 상세보기: 전체 내용을 모달로 표시
  - 📋 복사: 클립보드에 복사
  - 🗑️ 삭제: 확인 후 삭제
- 새로고침 버튼으로 최신 목록 갱신

## 에러 핸들링

모든 API 엔드포인트는 다음 형식의 JSON 응답을 반환합니다:

```json
{
  "success": true|false,
  "data": {...},           // success가 true일 때
  "error": "error message", // success가 false일 때
  "file": "filename.php",   // 에러 발생 파일
  "line": 123               // 에러 발생 라인
}
```

클라이언트는 에러를 gracefully handle하며, 폴백 메커니즘을 제공합니다.

## 보안 고려사항

1. **권한 검증**: 모든 API는 `require_login()` 및 userid 검증 수행
2. **SQL Injection 방지**: Moodle DB API 사용 (prepared statements)
3. **XSS 방지**: 사용자 입력은 저장 전/후 적절히 처리
4. **CSRF 방지**: Moodle 세션 기반 인증

## 문제 해결

### 패널이 열리지 않는 경우
1. 브라우저 콘솔 확인: `typeof InteractionPreparationPanel`
2. CSS/JS 파일 로드 확인 (Network 탭)
3. `window.currentUserId` 값 확인

### 시나리오 생성이 실패하는 경우
1. GPT API 키 설정 확인
2. 브라우저 콘솔에서 API 응답 확인
3. 폴백 시나리오가 생성되는지 확인

### 저장/삭제가 실패하는 경우
1. DB 테이블 생성 확인 (migration script 실행)
2. 브라우저 콘솔에서 API 에러 메시지 확인
3. Moodle 에러 로그 확인 (`/var/log/apache2/error.log` 또는 Moodle debug 모드)

## 향후 개선 사항

- [ ] 시나리오 템플릿 기능 추가
- [ ] 시나리오 공유 기능
- [ ] 모드별 추천 프롬프트 제공
- [ ] 시나리오 버전 관리
- [ ] 검색 및 필터링 기능

## 참고 자료

- 원본 UI 구조: `/orchestration_hs2/assets/js/step16_interaction_scenario.js`
- 가이드 모드 데이터: `/orchestration_hs2/assets/js/workflow_state.js`
- UI 스펙: `/docs/plans/agent16-ui-spec.md`
- 구현 계획: `/docs/plans/2025-10-21-agent16-interaction-preparation-panel.md`

---

**Last Updated**: 2025-10-22
**Version**: 1.0
**Author**: Claude Code
**Status**: Production Ready
