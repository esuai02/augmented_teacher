# Tasks: 대화 기반 장기 멘토링 시스템

## Relevant Files

- `alt42/studenthome/wxsperta/conversation_mentoring_schema.sql` - 데이터베이스 스키마 확장 (새 테이블 생성 및 ALTER)
- `alt42/studenthome/wxsperta/philosophy_constants.php` - 핵심 철학 상수 정의 (새 파일)
- `alt42/studenthome/wxsperta/conversation_processor.php` - 대화 후처리 및 WXSPERTA 추출 담당 (새 파일)
- `alt42/studenthome/wxsperta/agent_chat_api.php` - 기존 대화 API 확장 (수정)
- `alt42/studenthome/wxsperta/standalone_api.php` - Standalone UI용 최소 데이터 API (새 파일)
- `alt42/studenthome/wxsperta/journey_tracker.php` - 자기 발견 여정 추적 로직 (새 파일)
- `alt42/studenthome/wxsperta/competency_analyzer.php` - AI 시대 역량 측정 로직 (새 파일)
- `alt42/studenthome/wxsperta/quantum_state_detector.php` - 양자적 자기 발견 상태 감지 로직 (새 파일)
- `alt42/studenthome/wxsperta/wxsperta_extractor.php` - WXSPERTA 8층 레이어 추출 로직 (새 파일)
- `alt42/studenthome/wxsperta/standalone_ui/index.html` - Moodle UI와 분리된 단독 화면 (새 파일)
- `alt42/studenthome/wxsperta/standalone_ui/app.js` - Standalone UI 로직 (새 파일)
- `alt42/studenthome/wxsperta/standalone_ui/app.css` - Standalone UI 스타일 (새 파일)

### Notes

- 모든 PHP 파일은 Moodle 환경에 맞게 `include_once("/home/moodle/public_html/moodle/config.php");` 사용
- 데이터베이스 접근은 `global $DB` 사용
- 사용자 인증은 `require_login()` 사용
- 에러 메시지는 파일 경로와 라인 번호 포함

## Tasks

- [ ] 1.0 데이터베이스 스키마 확장
  - [ ] 1.1 `mdl_wxsperta_chat_contexts` 테이블에 새 컬럼 추가 (conversation_phase, quantum_state, ai_era_competencies, mentoring_year, self_clarity_score, direction_confidence, exploration_breadth, core_philosophy)
  - [ ] 1.2 `mdl_wxsperta_conversation_layers` 테이블 생성 (대화별 WXSPERTA 레이어 저장)
  - [ ] 1.3 `mdl_wxsperta_journey_tracking` 테이블 생성 (자기 발견 여정 추적)
  - [ ] 1.4 `mdl_wxsperta_competency_tracking` 테이블 생성 (AI 시대 역량 측정)
  - [ ] 1.5 `mdl_wxsperta_quantum_states` 테이블 생성 (양자적 자기 발견 상태 추적)
  - [ ] 1.6 스키마 파일 생성 및 마이그레이션 스크립트 작성

- [ ] 2.0 핵심 철학 상수 및 시스템 프롬프트 구현
  - [ ] 2.1 `philosophy_constants.php` 파일 생성 및 핵심 철학 상수 정의 (AI 시대 역설, 양자적 자기 발견, 미래 자아 가이던스 등)
  - [ ] 2.2 AI 시대 4대 역량 정의 (탐구력, 창조력, 연결력, 적응력)
  - [ ] 2.3 6단계 여정 정의 (self_awareness, world_exploration, intersection, experimentation, capacity_building, self_direction)
  - [ ] 2.4 양자 상태 정의 (superposition, collapse, observation, entanglement, tunneling)
  - [ ] 2.5 위기 상황별 대응 멘트 정의
  - [ ] 2.6 학생 언어 스타일 가이드 정의 (반말, 격려 표현, 질문 유도 등)

- [ ] 3.0 학생 언어 사용 대화 시스템 구현
  - [ ] 3.1 `agent_chat_api.php`의 `buildSystemPrompt` 함수 수정하여 철학 상수 통합
  - [ ] 3.2 학생 언어 스타일 가이드를 시스템 프롬프트에 반영
  - [ ] 3.3 위기 상황 감지 로직 추가 (키워드 기반)
  - [ ] 3.4 위기 상황별 적절한 멘토 멘트 선택 로직 구현
  - [ ] 3.5 대화 응답에서 반말 사용 강제 (프롬프트 수정)
  - [ ] 3.6 테스트: 다양한 학생 메시지에 대한 응답 품질 확인

- [ ] 4.0 WXSPERTA 레이어 자동 추출 및 저장 시스템
  - [ ] 4.1 `wxsperta_extractor.php` 파일 생성
  - [ ] 4.2 LLM을 통한 WXSPERTA 8층 레이어 추출 함수 구현 (Function Calling 또는 구조화된 프롬프트)
  - [ ] 4.3 추출된 레이어를 `mdl_wxsperta_conversation_layers` 테이블에 저장하는 함수 구현
  - [ ] 4.4 추출 신뢰도 계산 로직 구현
  - [ ] 4.5 `conversation_processor.php` 파일 생성하여 대화 후처리 통합
  - [ ] 4.6 `agent_chat_api.php`의 `sendMessage` 함수에 WXSPERTA 추출 통합 (비동기 또는 동기)
  - [ ] 4.7 에러 처리 및 폴백 메커니즘 구현
  - [ ] 4.8 테스트: 다양한 대화에서 레이어 추출 정확도 확인

- [ ] 5.0 자기 발견 여정 및 역량 추적 시스템
  - [ ] 5.1 `journey_tracker.php` 파일 생성
  - [ ] 5.2 대화에서 여정 단계 감지 함수 구현 (키워드 기반 + LLM 보조)
  - [ ] 5.3 여정 단계 전환 로직 구현 (진행률 계산 포함)
  - [ ] 5.4 핵심 깨달음, 도전, 돌파구 추출 및 저장 로직 구현
  - [ ] 5.5 `competency_analyzer.php` 파일 생성
  - [ ] 5.6 4대 역량 자동 측정 함수 구현 (키워드 기반)
  - [ ] 5.7 역량 점수 계산 및 저장 로직 구현
  - [ ] 5.8 `quantum_state_detector.php` 파일 생성
  - [ ] 5.9 양자 상태 감지 함수 구현 (키워드 기반)
  - [ ] 5.10 깨달음의 순간(breakthrough_moment) 추출 및 저장 로직 구현
  - [ ] 5.11 `conversation_processor.php`에 여정/역량/양자 상태 추적 통합
  - [ ] 5.12 사용자 프로필 업데이트 로직 구현 (self_clarity_score, direction_confidence 등)
  - [ ] 5.13 테스트: 전체 추적 시스템 통합 테스트

- [ ] 6.0 Standalone UI 구현 (Moodle UI 분리, 세션/DB는 Moodle 사용)
  - [ ] 6.1 `standalone_ui/` 디렉토리 구성 (`index.html`, `app.js`, `app.css`)
  - [ ] 6.2 `standalone_api.php` 구현: `action=get_agents` (에이전트 목록/카테고리/설명/아이콘)
  - [ ] 6.3 `standalone_api.php` 구현: `action=get_user_state` (user_id, role, 로그인 상태 확인용)
  - [ ] 6.4 Standalone UI에서 에이전트 목록 렌더링(검색/카테고리 필터)
  - [ ] 6.5 Standalone UI에서 대화 패널 구현: `agent_chat_api.php`의 `send_message` 호출
  - [ ] 6.6 로그인 가드: API 응답이 로그인 필요/HTML 리다이렉트면 안내 + 로그인 링크 제공
  - [ ] 6.7 에러 메시지 표준화(파일 경로/라인 포함은 서버 로그/JSON 에러에서 유지)
  - [ ] 6.8 통합 테스트: Standalone UI에서 대화→후처리→DB 저장 흐름 확인

