# PRD: 화이트보드 이미지 표시 오류 수정

## 1. Introduction/Overview

### 배경
학생이 `student_inbox.php`에서 문제 이미지를 업로드하고, 선생님이 `teachingagent.php`에서 풀이 스타일을 선택하면 화이트보드(`board_capture.php`)에서 이미지가 표시됩니다. 그러나 현재 다음과 같은 문제가 발생합니다:

1. **이미지가 지나치게 크게 표시됨** - 화이트보드 영역을 벗어나거나 스크롤이 필요함
2. **이미지가 안 보이는 경우 발생** - 이미지 로드 실패 또는 경로 문제

### 목적
선생님이 이미지 편집 모달에서 조정한 이미지 크기가 화이트보드에서 정확하게 반영되도록 하고, 모든 상황에서 이미지가 정상적으로 표시되도록 수정합니다.

## 2. Goals

1. 화이트보드에서 이미지가 적절한 크기로 표시되도록 보장
2. 선생님이 조정한 이미지 크기가 화이트보드에 정확히 반영
3. 이미지 로드 실패 시 적절한 대체 처리
4. 최소한의 코드 수정으로 확실한 작동 보장

## 3. User Stories

### US-1: 선생님 이미지 편집 → 화이트보드 반영
> **As a** 선생님,  
> **I want to** 이미지 편집 모달에서 조정한 크기가 화이트보드에 정확히 반영되기를,  
> **So that** 학생이 적절한 크기의 문제 이미지를 볼 수 있습니다.

### US-2: 학생 화이트보드 이미지 확인
> **As a** 학생,  
> **I want to** 화이트보드에서 문제 이미지가 적절한 크기로 표시되기를,  
> **So that** 스크롤 없이 문제를 확인하고 풀이를 작성할 수 있습니다.

### US-3: 이미지 로드 실패 처리
> **As a** 사용자,  
> **I want to** 이미지 로드에 실패했을 때 적절한 안내를 받기를,  
> **So that** 문제 상황을 인지하고 대처할 수 있습니다.

## 4. Functional Requirements

### 4.1 이미지 크기 조정 (화이트보드 표시)
- **FR-1.1**: 화이트보드 iframe 로드 완료 후 이미지 크기 제한 CSS 주입
- **FR-1.2**: 이미지의 `max-width: 100%`, `max-height: 100vh` 적용
- **FR-1.3**: 원본 비율(aspect ratio) 유지하며 크기 조정
- **FR-1.4**: 동적으로 추가되는 이미지도 크기 제한 적용 (MutationObserver)

### 4.2 이미지 편집 모달 개선 (teachingagent.php)
- **FR-2.1**: 현재 `adjustImageSize()` 함수의 스케일 값이 저장되도록 확인
- **FR-2.2**: 편집된 이미지의 크기 메타데이터를 함께 전달
- **FR-2.3**: 이미지 크기 조정 시 미리보기에서 실제 결과 확인 가능

### 4.3 이미지 로드 오류 처리
- **FR-3.1**: 이미지 로드 실패 시 `onerror` 핸들러로 대체 이미지 또는 메시지 표시
- **FR-3.2**: 콘솔에 상세 오류 로그 출력 (디버깅용)
- **FR-3.3**: 재시도 메커니즘 또는 사용자에게 알림

### 4.4 iframe CSS 주입 (student_inbox.php)
- **FR-4.1**: `openLectureModal()` 함수에서 iframe 로드 후 CSS 주입
- **FR-4.2**: CORS 오류 대비 postMessage 방식 fallback
- **FR-4.3**: 모든 화이트보드 타입(board_capture.php)에 적용

## 5. Non-Goals (Out of Scope)

- `board_capture.php` 파일 직접 수정 (서버 파일로 로컬에 없음)
- 이미지 확대/축소 기능 추가 (이미 존재함)
- 대규모 리팩토링 또는 아키텍처 변경
- 다른 화이트보드 타입(board.php, board_topic.php) 수정

## 6. Design Considerations

### 현재 코드 구조
```
student_inbox.php
├── openLectureModal() - 화이트보드 iframe 로드
├── handleSolutionView() - 풀이보기 버튼 핸들러
└── openWhiteboardLink() - 새 탭에서 화이트보드 열기

teachingagent.php
├── imageEditModal - 이미지 편집 모달 UI
├── adjustImageSize() - 이미지 크기 조정 (10% 단위)
├── confirmImageEdit() - 편집 확인 후 스타일 선택
├── acceptNewRequest() - 풀이 요청 처리
└── acceptNewRequestWithStyle() - 스타일별 풀이 요청 처리
```

### CSS 주입 전략
```javascript
// iframe 로드 완료 후
iframe.onload = function() {
    try {
        const iframeDoc = iframe.contentDocument;
        // CSS 주입
        const style = document.createElement('style');
        style.textContent = `
            img { max-width: 100% !important; max-height: 100vh !important; }
        `;
        iframeDoc.head.appendChild(style);
    } catch (e) {
        // CORS 오류 시 postMessage 사용
        iframe.contentWindow.postMessage({ type: 'injectCSS' }, '*');
    }
};
```

## 7. Technical Considerations

### 파일 의존성
- `student_inbox.php` (3367, 3409, 3477행 근처) - iframe.src 설정 후 처리
- `teachingagent.php` (2788-2810행) - 편집된 이미지 전달
- 화이트보드 URL: `board_capture.php?id={wboardid}&studentid={studentId}&interactionid={interactionId}`

### CORS 주의사항
- 같은 도메인(`mathking.kr`)이므로 iframe 접근 가능
- 단, iframe 로드 완료 전 접근 시 오류 발생 가능
- `setTimeout` 또는 `MutationObserver` 사용 필요

### 기존 기능 유지
- 이미지 편집 모달의 크기 조정 기능 (adjustImageSize)
- 지우개 기능
- 되돌리기 기능

## 8. Success Metrics

1. **이미지 표시 성공률**: 100% (로드 실패 없음)
2. **크기 적절성**: 화이트보드 영역 내 스크롤 없이 표시
3. **편집 반영**: 선생님 편집 크기가 그대로 반영
4. **코드 변경량**: 최소화 (100줄 이하)

## 9. Open Questions

1. `board_capture.php`에 postMessage 리스너가 있는지 확인 필요
2. 편집된 이미지의 크기 메타데이터가 현재 저장되고 있는지 확인 필요
3. 이미지 로드 실패의 정확한 원인 파악 (CORS, 경로, 권한 등)

## 10. Acceptance Criteria

- [ ] 학생이 업로드한 이미지가 화이트보드에서 적절한 크기로 표시됨
- [ ] 선생님이 편집 모달에서 조정한 크기가 화이트보드에 반영됨
- [ ] 이미지 로드 실패 시 오류 메시지 표시
- [ ] 기존 이미지 확대/축소 기능이 정상 작동함
- [ ] 모든 브라우저에서 동일하게 작동함 (Chrome, Firefox, Safari, Edge)

