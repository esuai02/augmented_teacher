# Agent05 학습감정 분석 에이전트

## 개요

활동별 학습 감정 유형을 분석하고 선택하는 에이전트입니다.

## 주요 기능

### 1. 7개 활동 카테고리 표시
- **개념이해** 📚: 핵심 개념 정리, 공식 유도 과정, 개념 간 연결, 실생활 적용 예시
- **유형학습** 🎯: 기본/응용/심화/신유형 문제
- **문제풀이** ✏️: 기출문제, 모의고사, 단원별 문제, 종합 문제
- **오답노트** 📝: 오답 원인 분석, 유사 문제 연습, 개념 재정리, 실수 방지 체크리스트
- **질의응답** 💬: 개념/문제 풀이/학습 방법/진로 상담
- **복습활동** 🔄: 일일/주간/단원 총정리/시험 대비 복습
- **포모도르** ⏰: 25분 집중 학습, 5분 휴식, 긴 휴식, 일일 목표 설정

### 2. 활동 세부 항목 선택
각 활동마다 4개의 하위 구조 분류

### 3. 감정 유형 분석 (추후 구현 예정)
학생의 감정 유형 관련 설문 조사

## 파일 구조

```
agent05_learning_emotion/
├── index.php                          # 메인 HTML 페이지 (62 lines)
├── README.md                          # 문서화
├── api/
│   └── activity_emotion_api.php       # API 엔드포인트 (135 lines)
├── assets/
│   ├── css/
│   │   └── agent05.css               # 스타일시트 (189 lines)
│   └── js/
│       ├── activity_categories_data.js # 활동 카테고리 데이터 (105 lines)
│       └── emotion_workflow.js        # 워크플로우 로직 (239 lines)
├── interaction_contents/              # 상호작용 컨텐츠 (추후)
├── tasks/                            # 작업 정의
├── ui/                               # UI 컴포넌트 (추후)
└── *.md                              # 활동별 문서
```

**Total Lines of Code**: 730 lines

## 사용 방법

### 접속 URL

**독립 실행 (Standalone)**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/index.php
```

**Orchestration 시스템 통합 (Embedded)**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration_hs2/index.php
→ Step 5 (학습감정 분석) 카드 클릭
→ 하단에 Agent05 iframe으로 표시
```

### 워크플로우

1. **7개 활동 카드 중 하나 선택**
   - 카드에 마우스 오버 시 호버 효과 (transform, shadow)
   - 클릭 시 하위 항목 모달 표시

2. **해당 활동의 세부 항목 모달 표시**
   - 4개의 세부 항목 버튼 표시
   - 각 항목에 번호 매핑 (1-4)

3. **세부 항목 선택**
   - 버튼 클릭 시 선택 정보 저장

4. **임시 메시지 팝업 표시**
   - 선택한 활동 및 세부 항목 표시
   - "추후 학생의 감정유형과 관련된 설문이 추가될 예정입니다" 메시지

5. **확인 버튼으로 팝업 닫기**
   - 상태 초기화
   - 다시 활동 선택 가능

### Orchestration 시스템 통합

Agent05는 orchestration_hs2 시스템의 Step 5에 통합되어 있습니다:

**통합 위치**: `orchestration_hs2/assets/js/workflow_render.js:2168-2193`

**통합 방식**:
- iframe으로 독립 페이지 임베딩
- 접기/펼치기 버튼으로 UI 제어 가능
- 자동으로 currentUserId 파라미터 전달
- Step 5 활동 선택 버튼과 함께 표시

**iframe 설정**:
```javascript
<iframe id="agent05-iframe"
        src="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/index.php?userid=${window.currentUserId || 2}"
        style="width: 100%; height: 800px; border: 2px solid rgba(...); border-radius: 8px;"
        frameborder="0"
        scrolling="auto"
        title="Agent05 학습감정 분석">
</iframe>
```

**장점**:
- 독립 실행과 통합 실행 모두 지원
- 코드 중복 없이 하나의 구현으로 양쪽에서 사용
- iframe 격리로 스타일 충돌 없음
- 독립적인 개발 및 테스트 가능

## 기술 스택

- **PHP 7.1.9** - 서버 사이드 로직
- **MySQL 5.7** - Moodle 데이터베이스
- **Vanilla JavaScript** - 클라이언트 사이드 로직 (React 사용 안 함)
- **CSS3** - 스타일링 (Grid Layout, Flexbox)
- **Moodle LMS 3.7** - 통합 플랫폼

## 데이터베이스 테이블

현재는 데이터 저장 없이 UI 플로우만 구현되어 있습니다.

추후 다음 테이블 사용 예정:
- `alt42g_activity_selections` - 활동 선택 기록
- `alt42g_emotion_categories` - 감정 카테고리 마스터
- `alt42g_emotion_items` - 감정 세부 항목
- `alt42g_emotion_selections` - 사용자 감정 선택 기록
- `alt42g_emotion_surveys` - 설문 응답 데이터

## API 엔드포인트

`api/activity_emotion_api.php`

### 사용 가능한 액션:

1. **saveActivitySelection** - 활동 선택 저장 (추후 구현)
   ```json
   {
     "action": "saveActivitySelection",
     "activity_key": "problem_solving",
     "activity_name": "문제풀이",
     "sub_item": "기출문제 풀이",
     "userid": 2
   }
   ```

2. **getActivitySelections** - 활동 선택 이력 조회 (추후 구현)
   ```json
   {
     "action": "getActivitySelections",
     "userid": 2
   }
   ```

3. **getEmotionSurveyQuestions** - 감정 설문 문항 조회 (추후 구현)
   ```json
   {
     "action": "getEmotionSurveyQuestions",
     "activity_key": "problem_solving"
   }
   ```

4. **saveEmotionSurvey** - 감정 설문 응답 저장 (추후 구현)
   ```json
   {
     "action": "saveEmotionSurvey",
     "userid": 2,
     "activity_key": "problem_solving",
     "responses": []
   }
   ```

## 개발 이력

### 2025-10-22: 초기 구현 및 Orchestration 통합
- ✅ 파일 구조 설계 및 디렉토리 준비
- ✅ 7개 활동 카테고리 데이터 정의
- ✅ 메인 HTML 페이지 생성 (Moodle 통합)
- ✅ CSS 스타일시트 작성 (그라디언트 배경, 그리드 레이아웃, 모달)
- ✅ JavaScript 워크플로우 로직 구현
  - 활동 카드 동적 렌더링
  - 하위 항목 모달 표시
  - 임시 메시지 팝업
- ✅ API 엔드포인트 스켈레톤 생성
- ✅ 콘솔 로깅 및 에러 위치 표시
- ✅ 문서화 (README.md)
- ✅ **Orchestration 시스템 통합**
  - `orchestration_hs2/assets/js/workflow_render.js:2168-2193` 수정
  - Step 5에 iframe으로 Agent05 페이지 임베딩
  - 접기/펼치기 버튼 UI 추가
  - currentUserId 자동 전달
  - 독립 실행과 통합 실행 모두 지원

## 주요 특징

### 1. Moodle 통합
- `include_once("/home/moodle/public_html/moodle/config.php")`
- `global $DB, $USER`
- `require_login()`
- 사용자 역할 확인 (field 22)

### 2. 에러 추적
- 모든 에러 메시지에 파일명과 라인 번호 포함
- 콘솔 로깅으로 디버깅 가능

### 3. 캐시 무효화
- JavaScript/CSS 로드 시 `?v=<?php echo time(); ?>` 파라미터 사용

### 4. 반응형 디자인
- 모바일 디바이스 지원 (@media 768px)
- Grid auto-fit 레이아웃

### 5. IIFE 패턴
- 전역 스코프 오염 방지
- 필요한 함수만 `window` 객체에 노출

## 브라우저 콘솔 로그 예시

```
[emotion_workflow.js] Agent05 초기화 시작
[emotion_workflow.js:renderActivityCards] 활동 카드 렌더링 시작
[emotion_workflow.js:renderActivityCards:37] 카테고리 수: 7
[emotion_workflow.js:renderActivityCards:60] 활동 카드 렌더링 완료
[emotion_workflow.js:handleActivityCardClick:67] 활동 선택: problem_solving 문제풀이
[emotion_workflow.js:showSubItemsModal:82] 모달 표시: 문제풀이
[emotion_workflow.js:showSubItemsModal:135] 모달 표시 완료
[emotion_workflow.js:handleSubItemClick:142] 하위 항목 선택: 문제풀이 기출문제 풀이
[emotion_workflow.js:closeModal:159] 모달 닫기
[emotion_workflow.js:showTemporaryMessage:173] 임시 메시지 팝업 표시
[emotion_workflow.js:showTemporaryMessage:226] 임시 메시지 팝업 표시 완료
```

## 추후 작업

- [ ] 감정 유형 설문 설계
  - 활동별 맞춤 설문 문항 정의
  - 감정 카테고리 및 세부 항목 DB 구축
- [ ] 설문 데이터 DB 저장
  - `alt42g_activity_selections` 테이블 생성
  - `alt42g_emotion_surveys` 테이블 생성
- [ ] 분석 결과 시각화
  - 학생별 감정 패턴 차트
  - 활동별 감정 분포 그래프
- [ ] 선생님용 대시보드 연동
  - 학급 전체 감정 트렌드
  - 개별 학생 감정 변화 추이

## 참고 자료

- Moodle LMS Documentation: https://docs.moodle.org/
- orchestration_hs2 참조 구현
- Agent05 기획 문서: `agent05_learning_emotion.md`

## 라이선스

Copyright © 2025 MathKing. All rights reserved.
