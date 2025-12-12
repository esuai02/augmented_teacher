# Tasks: 🌌 마이 궤도 — 스레드형 대화 + 앵커 스위칭 + 선택적 학생 승인

## Relevant Files

- `alt42/orchestration/tasks/0011-prd-orbit-conversation-threading-anchor-switching.md` - 본 PRD 문서
- `alt42/studenthome/wxsperta/conversation_mentoring_schema.sql` - DB 스키마(Conversation ID 및 승인 흐름 확장 필요)
- `alt42/studenthome/wxsperta/conversation_processor.php` - 턴 저장/상태 추론/앵커 스위칭(상태 업데이트) 처리
- `alt42/studenthome/wxsperta/agent_chat_api.php` - 대화 API(프롬프트 스위칭 + 3-choice 반환)
- `alt42/studenthome/wxsperta/standalone_api.php` - Standalone UI용 데이터 API(대화방 목록/상태/승인 대기 등 확장 가능)
- `alt42/studenthome/wxsperta/standalone_ui/index.html` - 학생 UI(글로벌/에이전트 대화 진입, 선택지 UI)
- `alt42/studenthome/wxsperta/standalone_ui/app.js` - 선택지 버튼/대화방 선택/재개 로직
- `alt42/studenthome/wxsperta/standalone_ui/app.css` - 선택지/상태+흐름 패널 스타일
- `alt42/studenthome/wxsperta/approval_system.php` - 승인 시스템(기존 승인 구조를 레이어 확정에 활용/연동 가능)
- `alt42/studenthome/wxsperta/wxsperta.php` - iframe 임베드 및 진입점(글로벌/에이전트 “대화하기”)

### Notes

- PHP는 Moodle 규칙 준수: `include_once("/home/moodle/public_html/moodle/config.php");`, `global $DB, $USER;`, `require_login();`
- MySQL 5.7 호환(ENUM/TEXT 위주), React 금지
- 서버 에러 메시지에는 파일 경로/라인 번호 포함

## Tasks

- [ ] 1.0 Conversation ID(대화 스레드) 데이터 모델 확정 및 마이그레이션 설계
  - [ ] 1.1 `conversation_id` 포맷/생성 규칙 결정 (예: `c_` + random hex, 서버 생성)
  - [ ] 1.2 “대화방(스레드)” 테이블 설계 (예: `mdl_wxsperta_conversations`)
  - [ ] 1.3 기존 `conversation_mentoring_schema.sql` 확장안 작성
    - [ ] 1.3.1 `mdl_wxsperta_conversation_contexts`에 `conversation_id` 컬럼 추가 + `UNIQUE(session_id)` 제약 재검토
    - [ ] 1.3.2 `mdl_wxsperta_conversation_messages`에 `conversation_id` 컬럼 추가
    - [ ] 1.3.3 `mdl_wxsperta_conversation_layers`에 `conversation_id` 컬럼 추가
    - [ ] 1.3.4 글로벌 대화는 `agent_key='global'`로 동일 구조 사용
  - [ ] 1.4 인덱스/조회 패턴 확정 (예: `(user_id, conversation_id)`, `(user_id, agent_key, last_updated)`)
  - [ ] 1.5 마이그레이션 전략 수립
    - [ ] 1.5.1 기존 데이터가 있으면 `session_id` 단위로 `conversation_id`를 생성해 백필(backfill)
    - [ ] 1.5.2 기존 `UNIQUE(session_id)`가 있으면 “동일 세션 1개 스레드”로 묶이는 한계를 문서화하고 변경/완화
  - [ ] 1.6 관리자 설치 스크립트 업데이트/추가
    - [ ] 1.6.1 `setup_conversation_schema.php`가 새 스키마/ALTER까지 반영하도록 정리
    - [ ] 1.6.2 설치/마이그레이션 실패 시, 실패 SQL 로그가 남도록 보강

- [ ] 2.0 API/프로세서: `conversation_id` 기반 저장·조회·재개 플로우 구현
  - [ ] 2.1 “스레드 생성/재개” 헬퍼 구현
    - [ ] 2.1.1 키: `(user_id, agent_key)`로 “최근 활성 스레드”를 찾고 없으면 생성
    - [ ] 2.1.2 글로벌(`agent_key=global`)과 에이전트 스레드 모두 지원
  - [ ] 2.2 `conversation_processor.php`를 `conversation_id` 중심으로 리팩토링
    - [ ] 2.2.1 `orbit_ensure_conversation_context()`가 `session_id` 대신 `conversation_id`를 primary로 사용
    - [ ] 2.2.2 `orbit_save_message()` / `orbit_save_layers()`에 `conversation_id` 저장
    - [ ] 2.2.3 `orbit_process_turn()` 반환값에 `conversation_id` 포함
    - [ ] 2.2.4 테이블 존재 체크(prefix 포함) 및 “설치 전 폴백” 유지
  - [ ] 2.3 `agent_chat_api.php` 확장
    - [ ] 2.3.1 요청 파라미터로 `conversation_id`를 받기 (없으면 서버에서 생성/재개)
    - [ ] 2.3.2 history를 “클라이언트 전체 전송” 대신 “서버에서 최근 N개 로드”로 전환(옵션)
    - [ ] 2.3.3 응답에 `conversation_id` 포함(클라이언트 재개용)
  - [ ] 2.4 `standalone_api.php` 확장(Standalone UI가 스레드를 다루게)
    - [ ] 2.4.1 `action=get_conversations` (user_id + agent_key 기준 최근 스레드 목록)
    - [ ] 2.4.2 `action=create_or_resume_conversation` (agent_key 기반 conversation_id 반환)
    - [ ] 2.4.3 `action=get_conversation_messages` (conversation_id 기준 최근 메시지)
    - [ ] 2.4.4 `action=get_conversation_state` (emotion/phase/anchor/next 등 “상태+흐름”)
  - [ ] 2.5 검증/디버깅 도구 업데이트
    - [ ] 2.5.1 `wxsperta_chat_verify.php`에 conversation_id 기준 필터 추가
    - [ ] 2.5.2 “글로벌 vs 에이전트” 별로 최근 저장/추출 확인 가능하게

- [ ] 3.0 앵커 자동 스위칭(B): 상태 추론 → 프롬프트/선택지(3-choice) 동시 스위칭
  - [ ] 3.1 상태 추론(State Extract) 구현
    - [ ] 3.1.1 폴백(룰) 기반: emotion/phase/quantum/anchor/forcedness 추정
    - [ ] 3.1.2 LLM 기반 JSON 추출(가능하면): 실패 시 폴백으로 자동 전환
  - [ ] 3.2 컨텍스트 업데이트(상태 저장)
    - [ ] 3.2.1 `conversation_contexts`의 `emotion_state`, `conversation_phase`, `quantum_state` 갱신
    - [ ] 3.2.2 점수(명확성/확신/탐색폭) 누적 갱신 규칙 정의 및 저장
    - [ ] 3.2.3 (권장) `anchor_layer`, `forcedness`, `micro_next_action` 컬럼 추가 또는 메타라인 저장 방식 확정
  - [ ] 3.3 프롬프트 스위칭
    - [ ] 3.3.1 `buildSystemPrompt()`에 “앵커별 질문유형/톤 블록”을 주입
    - [ ] 3.3.2 “전환 표현(학생 언어)” 규칙을 프롬프트에 포함
  - [ ] 3.4 ODE 3-choice 생성기 구현
    - [ ] 3.4.1 anchor(W/X/S/P/E/R/T/A) 기반 선택지 템플릿 3개 세트
    - [ ] 3.4.2 emotion 상태일 때는 회복/안전 선택지를 1개 강제 포함
    - [ ] 3.4.3 forcedness(억지 비용)가 높아지면 “설명/지시”를 줄이고 “질문/선택”을 늘리는 규칙
  - [ ] 3.5 출력 전환기(표현 치환) 적용
    - [ ] 3.5.1 `orbit_surface_rewrite()`를 응답/선택지에 공통 적용(목적함수 언어 노출 방지)
    - [ ] 3.5.2 데모 응답/폴백에서도 동일 톤 유지

- [ ] 4.0 선택적 학생 승인(B): worldView/abstraction 확정(승격) 플로우 구현
  - [ ] 4.1 승인 데이터 모델 결정
    - [ ] 4.1.1 기존 `mdl_wxsperta_approval_requests`는 entity_type이 `agent|project`로 제한 → “대화 레이어 승인”은 별도 테이블이 안전
    - [ ] 4.1.2 신규 테이블(예: `mdl_wxsperta_layer_approvals`) 설계: `conversation_id`, `agent_key`, `layer`, `proposed_text`, `status`, `approved_text`, timestamps
  - [ ] 4.2 승인 생성 로직
    - [ ] 4.2.1 레이어 추출 시 `worldView/abstraction`이 나오면 자동으로 “승인 대기” 생성
    - [ ] 4.2.2 승인 전에는 `is_approved=0` 유지, 승인 후 최신 승인본을 조회 가능하게
  - [ ] 4.3 승인 처리 API
    - [ ] 4.3.1 `standalone_api.php`에 `action=get_pending_layer_approvals`
    - [ ] 4.3.2 `action=submit_layer_approval` (approve/reject + 수정 허용 옵션)
  - [ ] 4.4 “승인본 우선” 조회 규칙
    - [ ] 4.4.1 `wxsperta_neuron.php`는 승인본이 있으면 승인본을 우선 표시
    - [ ] 4.4.2 승인본이 없으면 “초안(제안)”을 구분 표시(학생에게는 부담 없이)
  - [ ] 4.5 학생 UX 문구/형태(강요 금지)
    - [ ] 4.5.1 “시험/평가” 느낌이 아닌 확인 질문 UI (“이 말, 너랑 맞아?”)
    - [ ] 4.5.2 “아니야/수정할래/나중에” 3-choice로 처리

- [ ] 5.0 UI(C/A): Standalone UI에서 글로벌/에이전트 대화 + 대화방 선택/재개 + 선택지 버튼 + (선택)멘토/교사 최소 뷰
  - [ ] 5.1 Standalone UI에 “선택지 버튼” UI 추가
    - [ ] 5.1.1 `standalone_ui/index.html`에 `#suggestions` 컨테이너 추가
    - [ ] 5.1.2 `standalone_ui/app.css`에 버튼 스타일 + 모바일 대응
    - [ ] 5.1.3 `standalone_ui/app.js`에 `renderSuggestions()` + 클릭 시 자동 전송
  - [ ] 5.2 Standalone UI에 “대화방 선택/재개” UI 추가
    - [ ] 5.2.1 agent 선택 시 `create_or_resume_conversation` 호출 → `conversation_id` 확보
    - [ ] 5.2.2 `get_conversation_messages`로 최근 메시지 로드 후 이어서 대화
    - [ ] 5.2.3 최근 3개 스레드 빠른 재개(리텐션 우선)
  - [ ] 5.3 글로벌 멘토링 모드(C) 지원
    - [ ] 5.3.1 Standalone UI에서 `mode=global` 또는 `agent_id=global` 지원
    - [ ] 5.3.2 `wxsperta_app.js`의 `openGlobalMentorChat()` iframe src에 파라미터 전달(예: `standalone_ui/index.html?mode=global&embed=1`)
  - [ ] 5.4 “상태+흐름” 미니 패널(UI) 추가(학생용)
    - [ ] 5.4.1 emotion/phase/anchor/next 한 줄 표시(설명 장문 금지)
    - [ ] 5.4.2 근거(evidence)는 접기/펼치기(스크롤 폭증 방지)
  - [ ] 5.5 승인 UI(학생용) 최소 구현
    - [ ] 5.5.1 pending approvals가 있으면 채팅 상단/하단에 카드로 노출
    - [ ] 5.5.2 “맞아/수정/나중에” 처리 후 API 호출
  - [ ] 5.6 멘토/교사 최소 뷰(C) 구현(권장: 별도 PHP 페이지)
    - [ ] 5.6.1 역할 체크(`fieldid=22`)로 teacher/mentor만 접근 허용
    - [ ] 5.6.2 학생별 최근 스레드/상태/승인대기 개수 조회
    - [ ] 5.6.3 상세: 특정 학생+conversation_id의 최근 메시지/레이어/승인 상태 보기
  - [ ] 5.7 통합 동작 확인(핵심 플로우)
    - [ ] 5.7.1 글로벌 대화 시작→재접속→재개
    - [ ] 5.7.2 에이전트 대화 시작→앵커 스위칭에 따라 선택지 변화
    - [ ] 5.7.3 worldView/abstraction 승인 생성→승인/거부→조회 우선순위 확인


