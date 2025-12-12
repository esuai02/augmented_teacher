## Relevant Files

- `alt42/teachingsupport/AItutor/ui/activity_tracker.js` - 학생 학습 화면 이벤트 수집(오답/힌트/비활성 등) 및 백엔드 전송.
- `alt42/teachingsupport/AItutor/ui/learning_interface.php` - `activity_tracker.js` 로드(학습 UI에 트래커 부착).
- `alt42/orchestration/api/events/collect.php` - 프론트 이벤트 수집 API(정규화 → EventBus publish → TriggerEngine).
- `alt42/orchestration/api/events/event_bus.php` - 이벤트 버스(싱글톤 추가 포함).
- `alt42/orchestration/api/events/trigger_engine.php` - 규칙 로딩/평가 및 AgentOrchestratorLib 기반 실행.
- `alt42/orchestration/api/events/trigger_rules.yaml` - 트리거 룰 정의(YAML).
- `alt42/orchestration/api/events/init.php` - (선택) 이벤트 시스템 초기화 헬퍼.
- `alt42/orchestration/agents_1204/engine_core/orchestration/AgentOrchestratorLib.php` - 트리거 엔진에서 안전하게 include 가능한 오케스트레이터 라이브러리.
- `alt42/orchestration/cron/agent_scheduler.php` - 시간 기반 스케줄러(CLI cron 호출).

### Notes

- 이 저장소는 **라이브 서버 환경**이므로, 배포/cron 등록은 운영 절차에 따라 진행해야 합니다.
- PHP 7.1.9 환경에서 YAML 파서가 없을 수 있으므로 `trigger_engine.php`는 단순 파서를 포함합니다.

## Tasks

- [ ] 1.0 프론트엔드 ActivityTracker 구현
  - [ ] 1.1 `ActivityTracker`를 전역 변수(`STUDENT_ID`, `CONTENT_ID`, `ANALYSIS_ID`) 기반으로 초기화
  - [ ] 1.2 `saveInteraction`, `requestHintWithPersona` 등의 훅을 최소 침습으로 래핑하여 이벤트 생성
  - [ ] 1.3 버퍼링/우선순위 기반 전송(High priority 즉시 flush, 기본 2초 flush)
  - [ ] 1.4 `learning_interface.php`에 `activity_tracker.js` 로드 추가

- [ ] 2.0 백엔드 Event Collector API 구현
  - [ ] 2.1 `collect.php` 생성 및 Moodle 인증/JSON 응답 처리
  - [ ] 2.2 입력 이벤트 배열 검증/정규화(`event_type`, `timestamp`, `context` 등)
  - [ ] 2.3 EventBus publish 및 Trigger Engine 호출
  - [ ] 2.4 오류 메시지에 파일/라인 포함

- [ ] 3.0 Trigger Engine 구현
  - [ ] 3.1 `AgentOrchestratorLib.php` 기반으로 단일 에이전트 실행 지원
  - [ ] 3.2 `trigger_rules.yaml` 로딩 및 조건 평가(필드 경로: `data.consecutive_wrong_count` 등)
  - [ ] 3.3 기본 규칙: `problem_wrong` → Agent 11

- [ ] 4.0 트리거 규칙 정의 파일 작성
  - [ ] 4.1 `trigger_rules.yaml` 생성
  - [ ] 4.2 확장 규칙: 연속 오답 3회(Agent 05), 5회(Agent 13)

- [ ] 5.0 시간 기반 스케줄러 구현
  - [ ] 5.1 `cron/agent_scheduler.php` 생성(CLI용)
  - [ ] 5.2 예시 스케줄: 매일 08시 Agent 02, 매시간 정각 Agent 09
  - [ ] 5.3 운영 cron 등록 가이드(문서/주석)

- [ ] 6.0 통합 및 테스트
  - [ ] 6.1 학습 화면에서 이벤트 전송 확인(브라우저 네트워크 탭)
  - [ ] 6.2 수동 API 호출로 트리거 동작 확인(아래 예시 참고)
  - [ ] 6.3 AgentOrchestrator 실행 결과(JSON) 및 실패 격리 확인

### 수동 테스트 예시

- `collect.php`로 오답 이벤트를 직접 전송(로그인 상태 필요):
  - POST `alt42/orchestration/api/events/collect.php`
  - Body(JSON):
    - `student_id`: 123
    - `session_id`: "sess_test"
    - `events`: `[{"event_type":"problem_wrong","timestamp":1700000000000,"priority":9,"data":{"consecutive_wrong_count":1},"context":{"content_id":456,"analysis_id":"abc"}}]`


