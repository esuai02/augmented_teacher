# PRD: Realtime Voice Tutor (실시간 음성 튜터)

## Introduction/Overview

학생이 수학 문제를 풀다가 막혔을 때, OpenAI Realtime API를 활용하여 실제 선생님과 대화하듯이 실시간 음성으로 질문하고 답변을 받을 수 있는 기능입니다. 문제 이미지와 해설 이미지를 AI가 미리 분석하여 상세한 컨텍스트를 파악한 상태에서 대화를 진행하므로, 더 정확하고 맞춤형인 설명을 제공할 수 있습니다.

**문제**: 기존 텍스트 기반 채팅 인터페이스는 학생이 타이핑해야 하고, 실시간 상호작용이 제한적입니다. 또한 문제와 해설을 함께 보면서 대화하는 것이 어렵습니다.

**목표**: 학생이 마이크를 통해 자연스럽게 질문하고, AI가 음성으로 즉시 답변하며, 문제와 해설 이미지를 함께 보면서 대화할 수 있는 실시간 음성 튜터를 구현합니다.

## Goals

1. 학생이 문제를 풀다가 막혔을 때 음성으로 즉시 질문할 수 있도록 합니다.
2. AI가 문제와 해설 이미지를 사전 분석하여 정확한 컨텍스트를 파악한 상태에서 대화합니다.
3. 실시간 음성 대화를 통해 자연스러운 1:1 과외 경험을 제공합니다.
4. 기존 `learning_interface.php`의 이미지 전달 방식을 재사용하여 일관성을 유지합니다.
5. 독립적인 기능으로 구현하여 기존 시스템에 영향을 주지 않습니다.

## User Stories

1. **막힌 문제 질문하기**
   - As a 학생, I want to 마이크 버튼을 클릭하고 음성으로 질문할 수 있도록 so that 타이핑 없이 빠르게 도움을 받을 수 있습니다.

2. **문제와 해설을 함께 보며 대화하기**
   - As a 학생, I want to 문제 이미지와 해설 이미지를 AI가 함께 보면서 대화할 수 있도록 so that 더 정확하고 맥락에 맞는 설명을 받을 수 있습니다.

3. **자연스러운 대화 흐름**
   - As a 학생, I want to AI가 내 말을 중간에 끊어도 자연스럽게 대응하도록 so that 실제 선생님과 대화하는 것처럼 편안하게 학습할 수 있습니다.

4. **즉시 피드백 받기**
   - As a 학생, I want to 질문하면 즉시 음성으로 답변을 받을 수 있도록 so that 학습 흐름이 끊기지 않고 계속 진행할 수 있습니다.

## Functional Requirements

### FR1: 세션 생성 및 초기화
1.1. 시스템은 `learning_interface.php`에서 제공하는 문제 이미지 URL(`window.QUESTION_IMAGE`)과 해설 이미지 URL(`window.SOLUTION_IMAGE`)을 받아야 합니다.

1.2. 시스템은 세션 시작 전에 OpenAI Vision API를 사용하여 문제와 해설 이미지를 분석해야 합니다. (`analyze_writing.php`와 동일한 방식)

1.3. 시스템은 분석 결과를 OpenAI Realtime API 세션의 `instructions`에 포함시켜야 합니다.

1.4. 시스템은 Realtime API 세션을 생성하고 `client_secret`을 클라이언트에 반환해야 합니다.

### FR2: 이미지 전달 및 분석
2.1. 시스템은 `learning_interface.php`에서 이미지 URL을 추출하는 방식을 재사용해야 합니다:
   - `mdl_question` 테이블에서 `questiontext`와 `generalfeedback`을 조회
   - HTML에서 `<img>` 태그를 파싱하여 이미지 URL 추출
   - 문제 이미지: `questiontext`에서 추출
   - 해설 이미지: `generalfeedback`에서 추출

2.2. 시스템은 추출한 이미지 URL을 OpenAI Vision API에 전달하여 분석해야 합니다.

2.3. 시스템은 분석 결과를 JSON 형식으로 구조화해야 합니다:
   - 문제 분석: 주제, 난이도, 핵심 개념, 풀이 단계, 자주 틀리는 부분
   - 해설 분석: 풀이 단계, 가르칠 때 강조할 점, 힌트 제공 전략

### FR3: WebRTC 연결 및 음성 스트리밍
3.1. 시스템은 클라이언트에서 WebRTC를 사용하여 OpenAI Realtime API와 연결해야 합니다.

3.2. 시스템은 마이크 입력을 실시간으로 스트리밍해야 합니다.

3.3. 시스템은 AI의 음성 응답을 실시간으로 재생해야 합니다.

3.4. 시스템은 학생이 말하는 중간에 끊어도 자연스럽게 처리해야 합니다 (turn detection).

### FR4: UI/UX
4.1. 시스템은 `learning_interface.php`의 헤더 영역에 "음성 튜터" 버튼을 추가해야 합니다.

4.2. 시스템은 버튼 클릭 시 음성 튜터 세션을 시작/종료할 수 있어야 합니다.

4.3. 시스템은 연결 상태를 시각적으로 표시해야 합니다 (연결 중, 연결됨, 오류 등).

4.4. 시스템은 마이크 권한 요청 및 오류 처리를 사용자 친화적으로 처리해야 합니다.

### FR5: 에러 처리 및 안정성
5.1. 시스템은 네트워크 연결 끊김 시 재연결을 시도해야 합니다.

5.2. 시스템은 마이크 권한 거부 시 명확한 안내 메시지를 표시해야 합니다.

5.3. 시스템은 API 오류 시 사용자에게 적절한 오류 메시지를 표시해야 합니다.

5.4. 시스템은 모든 오류 메시지에 파일 경로와 라인 번호를 포함해야 합니다.

### FR6: 보안 및 비용 관리
6.1. 시스템은 `client_secret`을 클라이언트에서만 사용하고, API 키는 서버에만 보관해야 합니다.

6.2. 시스템은 세션 시간을 제한해야 합니다 (예: 60분).

6.3. 시스템은 세션 사용량을 로깅하여 비용을 추적할 수 있어야 합니다.

## Non-Goals (Out of Scope)

1. **기존 채팅 시스템과의 통합**: 이 기능은 독립적으로 작동하며, 기존 `SidebarChatInterface`와 통합하지 않습니다.

2. **페르소나 시스템 연동**: 초기 버전에서는 기존 페르소나 시스템과의 연동을 포함하지 않습니다.

3. **대화 기록 저장**: 초기 버전에서는 대화 내용을 DB에 저장하지 않습니다.

4. **화이트보드 실시간 분석**: 화이트보드 필기를 실시간으로 분석하는 기능은 포함하지 않습니다 (기존 `analyze_writing.php`와 별도).

5. **다중 언어 지원**: 초기 버전에서는 한국어만 지원합니다.

6. **모바일 앱**: 웹 브라우저 환경에서만 작동합니다.

## Design Considerations

### UI 위치
- 음성 튜터 버튼은 `learning_interface.php`의 헤더 우측 상단에 배치합니다.
- 기존 TTS 플레이어 영역(`header-right-controls`) 근처에 배치합니다.

### 버튼 상태
- 기본 상태: "🎤 음성 튜터" (비활성)
- 연결 중: "연결 중..." (로딩 표시)
- 연결됨: "음성 튜터 종료" (활성 상태 표시)
- 오류: "재시도" 버튼 표시

### 음성 상태 표시
- 마이크 레벨 표시 (선택사항, 향후 추가 가능)
- 스피커 재생 상태 표시 (선택사항, 향후 추가 가능)

## Technical Considerations

### API 엔드포인트
- **세션 생성**: `alt42/teachingsupport/AItutor/api/realtime_session.php`
  - POST 요청
  - 입력: `student_id`, `content_id`, `question_image`, `solution_image`, `current_step`, `current_emotion`
  - 출력: `session_id`, `client_secret`, `expires_at`

### 클라이언트 JavaScript
- **메인 파일**: `alt42/teachingsupport/AItutor/ui/realtime_tutor.js`
- WebRTC 연결 관리
- 오디오 스트리밍 처리
- Realtime API 이벤트 처리

### 이미지 분석
- `analyze_writing.php`의 이미지 전달 방식을 재사용
- Vision API 호출은 `realtime_session.php`에서 처리
- 분석 결과는 Realtime 세션의 `instructions`에 포함

### API 키 관리
- OpenAI API 키는 Moodle 설정(`get_config('local_augmented_teacher', 'openai_api_key')`)에서 로드
- 또는 `alt42/config/ai_services.config.php`에서 로드
- `client_secret`은 세션별로 생성되어 클라이언트에 전달

### WebRTC 연결
- SDP offer/answer 방식 사용
- ICE candidate 교환
- 오디오 코덱: Opus (48kHz, 스테레오)

### 브라우저 호환성
- Chrome, Edge, Safari (최신 버전) 지원
- Firefox는 WebRTC 지원 확인 필요

## Success Metrics

1. **사용성**: 학생이 음성 튜터를 시작하여 질문하고 답변을 받는 데 걸리는 시간이 30초 이내
2. **응답 속도**: 학생 질문 후 AI 응답까지의 지연 시간이 2초 이내
3. **연결 안정성**: 세션 중 연결 끊김 발생률이 5% 이하
4. **사용자 만족도**: 사용자 설문에서 "실제 선생님과 대화하는 것 같다" 항목 70% 이상 긍정 응답
5. **비용 효율성**: 세션당 평균 비용이 $0.50 이하

## Open Questions

1. **세션 시간 제한**: 60분이 적절한가? 사용자 피드백에 따라 조정 필요
2. **동시 세션 수**: 한 학생이 여러 세션을 동시에 열 수 있는가? (초기에는 1개로 제한)
3. **음성 품질**: Opus 코덱 설정이 최적인가? (48kHz 스테레오)
4. **대화 기록**: 향후 버전에서 대화 기록을 저장할 계획인가?
5. **페르소나 연동**: 향후 버전에서 페르소나 시스템과 연동할 계획인가?

