# Task List: Realtime Voice Tutor Implementation

Based on PRD: `0006-prd-realtime-voice-tutor.md`

## Relevant Files

- `alt42/teachingsupport/AItutor/api/realtime_session.php` - 백엔드 API 엔드포인트: Realtime 세션 생성, 이미지 분석, client_secret 발급
- `alt42/teachingsupport/AItutor/ui/realtime_tutor.js` - 프론트엔드 클라이언트: WebRTC 연결, 오디오 스트리밍, Realtime API 이벤트 처리
- `alt42/teachingsupport/AItutor/ui/learning_interface.php` - 메인 UI 파일: 음성 튜터 버튼 추가 및 통합
- `alt42/teachingsupport/AItutor/ui/learning_interface.js` - 기존 JavaScript 파일: 음성 튜터 토글 함수 추가
- `alt42/teachingsupport/AItutor/css/realtime_tutor.css` - 스타일시트: 음성 튜터 버튼 및 상태 표시 스타일

### Notes

- 기존 `analyze_writing.php`의 이미지 전달 방식을 재사용합니다.
- OpenAI API 키는 `get_config('local_augmented_teacher', 'openai_api_key')` 또는 `alt42/config/ai_services.config.php`에서 로드합니다.
- 모든 오류 메시지는 파일 경로와 라인 번호를 포함해야 합니다.

## Tasks

- [ ] 1.0 백엔드 API 엔드포인트 구현 (realtime_session.php)
  - [ ] 1.1 `realtime_session.php` 파일 생성: 기본 구조 및 Moodle 인증 설정
  - [ ] 1.2 입력 파라미터 검증: `student_id`, `content_id`, `question_image`, `solution_image`, `current_step`, `current_emotion` 검증 로직 구현
  - [ ] 1.3 OpenAI API 키 로드: `get_config()` 또는 `config.php`에서 API 키 로드 (analyze_writing.php와 동일한 방식)
  - [ ] 1.4 문제/해설 이미지 분석 함수 구현: Vision API를 사용하여 이미지 분석 (analyze_writing.php의 이미지 전달 방식 재사용)
  - [ ] 1.5 분석 결과 구조화: 문제 분석(주제, 난이도, 개념, 단계, 자주 틀리는 부분) 및 해설 분석(풀이 단계, 강조점, 힌트 전략) JSON 생성
  - [ ] 1.6 튜터 instructions 생성 함수: 분석 결과를 포함한 프롬프트 생성 (학생 정보, 단원, 풀이 단계, 감정 상태 포함)
  - [ ] 1.7 Realtime 세션 생성: OpenAI Realtime API `/v1/realtime/sessions` 엔드포인트 호출
  - [ ] 1.8 client_secret 반환: 세션 ID, client_secret, 만료 시간을 JSON으로 반환
  - [ ] 1.9 에러 처리: 모든 예외에 파일 경로와 라인 번호 포함하여 에러 메시지 생성

- [ ] 2.0 프론트엔드 Realtime 클라이언트 구현 (realtime_tutor.js)
  - [ ] 2.1 `realtime_tutor.js` 파일 생성: 기본 구조 및 상태 관리 객체 정의
  - [ ] 2.2 세션 생성 함수 구현: 백엔드 API 호출하여 세션 생성 및 client_secret 받기
  - [ ] 2.3 WebRTC 연결 설정: RTCPeerConnection 생성 및 ICE candidate 처리
  - [ ] 2.4 SDP offer 생성 및 전송: OpenAI Realtime API에 SDP offer 전송 (제공된 curl 예제 참고)
  - [ ] 2.5 SDP answer 처리: OpenAI로부터 받은 SDP answer를 RemoteDescription으로 설정
  - [ ] 2.6 마이크 스트림 시작: `getUserMedia()`로 마이크 권한 요청 및 오디오 스트림 시작
  - [ ] 2.7 오디오 스트림을 PeerConnection에 추가: 마이크 입력을 WebRTC 연결에 연결
  - [ ] 2.8 AI 음성 응답 재생: PeerConnection의 오디오 트랙을 Audio 엘리먼트로 재생
  - [ ] 2.9 Realtime 이벤트 처리: conversation.item.created, response.audio_transcript.delta 등 이벤트 핸들러 구현
  - [ ] 2.10 텍스트 메시지 표시: AI 응답 텍스트를 SidebarChatInterface에 표시 (선택사항)
  - [ ] 2.11 세션 종료 함수: 모든 스트림 및 연결 정리

- [ ] 3.0 UI 통합 및 버튼 추가 (learning_interface.php/js)
  - [ ] 3.1 `learning_interface.php`에 음성 튜터 버튼 추가: 헤더 우측 상단 `header-right-controls` 영역에 버튼 추가
  - [ ] 3.2 버튼 스타일 추가: 기본/활성/로딩/오류 상태별 스타일 정의 (CSS 또는 인라인 스타일)
  - [ ] 3.3 `learning_interface.js`에 토글 함수 추가: `toggleRealtimeTutor()` 함수 구현
  - [ ] 3.4 세션 시작 로직: 버튼 클릭 시 `realtime_tutor.js`의 `init()` 함수 호출
  - [ ] 3.5 연결 상태 표시: 연결 중/연결됨/오류 상태를 버튼에 반영
  - [ ] 3.6 이미지 URL 전달: `window.QUESTION_IMAGE`와 `window.SOLUTION_IMAGE`를 `realtime_tutor.js`에 전달
  - [ ] 3.7 학생 정보 전달: `window.STUDENT_ID`, `window.CONTENT_ID`, 현재 풀이 단계, 감정 상태 전달
  - [ ] 3.8 마이크 권한 오류 처리: 권한 거부 시 사용자 친화적 안내 메시지 표시

- [ ] 4.0 에러 처리 및 안정성 개선
  - [ ] 4.1 네트워크 연결 끊김 감지: WebRTC 연결 상태 모니터링
  - [ ] 4.2 자동 재연결 로직: 연결 끊김 시 재연결 시도 (최대 3회)
  - [ ] 4.3 세션 시간 제한: 60분 후 자동 종료 및 사용자 알림
  - [ ] 4.4 API 오류 처리: OpenAI API 오류 시 명확한 오류 메시지 표시
  - [ ] 4.5 마이크 권한 오류 처리: 권한 거부/차단 시 안내 및 해결 방법 제시
  - [ ] 4.6 브라우저 호환성 체크: WebRTC 지원 여부 확인 및 미지원 브라우저 안내
  - [ ] 4.7 리소스 정리: 세션 종료 시 모든 타이머, 스트림, 연결 정리

- [ ] 5.0 테스트 및 검증
  - [ ] 5.1 로컬 테스트: 개발 환경에서 세션 생성 및 연결 테스트
  - [ ] 5.2 이미지 분석 테스트: 문제/해설 이미지가 올바르게 분석되는지 확인
  - [ ] 5.3 음성 스트리밍 테스트: 마이크 입력 및 AI 응답 재생 테스트
  - [ ] 5.4 에러 시나리오 테스트: 네트워크 끊김, 권한 거부, API 오류 등 테스트
  - [ ] 5.5 브라우저 호환성 테스트: Chrome, Edge, Safari에서 동작 확인
  - [ ] 5.6 성능 테스트: 응답 시간, 연결 안정성 측정
  - [ ] 5.7 사용자 테스트: 실제 학생 대상 테스트 및 피드백 수집

