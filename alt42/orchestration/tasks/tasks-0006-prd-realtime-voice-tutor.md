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
- [ ] 2.0 프론트엔드 Realtime 클라이언트 구현 (realtime_tutor.js)
- [ ] 3.0 UI 통합 및 버튼 추가 (learning_interface.php/js)
- [ ] 4.0 에러 처리 및 안정성 개선
- [ ] 5.0 테스트 및 검증

