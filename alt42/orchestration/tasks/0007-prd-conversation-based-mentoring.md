# PRD: 대화 기반 장기 멘토링 시스템

## Introduction/Overview

AI 시대에 "진짜 나를 찾는 여정"을 지원하는 대화 기반 장기 멘토링 시스템을 구현합니다. 이 시스템은 학생과 AI 에이전트 간의 대화를 통해 "🌌 마이 궤도" 철학에 맞게 장기적인 자기 발견과 성장을 지원하며, 모든 대화 내용을 WXSPERTA 8층 구조로 자동 저장하여 지속적인 학습과 진화를 가능하게 합니다.

**핵심 문제**: 기존 진로 교육은 단기적이고 정적이며, AI 시대에 필요한 "고유성 발견"과 "적응력"을 키우지 못합니다. 학생들은 방향을 잃고, 대화는 기록되지 않으며, 장기적인 성장 추적이 불가능합니다.

**해결책**: 제공된 HTML 문서("AI 시대, 나를 찾는 여정")의 철학을 시스템 프롬프트에 반영하고, 학생의 자연스러운 언어(반말, 친근한 톤)로 대화하며, 모든 대화를 WXSPERTA 8층 구조로 자동 저장하여 3년 장기 멘토링 여정을 지원합니다.

## Goals

1. **철학 기반 대화**: 제공된 HTML 문서의 핵심 철학(양자적 자기 발견, AI 시대 역량, 6단계 여정)을 모든 대화에 반영
2. **학생 언어 사용**: 존댓말이 아닌 반말, 친근한 톤으로 자연스러운 대화 구현
3. **자동 WXSPERTA 저장**: 모든 대화에서 WXSPERTA 8층(worldView, context, structure, process, execution, reflection, transfer, abstraction) 자동 추출 및 저장
4. **장기 여정 추적**: 6단계 자기 발견 여정(self_awareness → world_exploration → intersection → experimentation → capacity_building → self_direction) 자동 추적
5. **역량 측정**: AI 시대 4대 역량(탐구력, 창조력, 연결력, 적응력) 자동 측정 및 추적
6. **양자 상태 추적**: 양자적 자기 발견 모델(중첩, 붕괴, 터널링 등) 상태 자동 감지 및 기록

## User Stories

1. **학생으로서**, 나는 AI 에이전트와 친구처럼 편하게 대화하고 싶어서, 반말과 자연스러운 언어로 대화할 수 있어야 합니다.
2. **학생으로서**, 내가 말한 내용이 자동으로 저장되고 정리되어서, 나중에 내 성장 과정을 돌아볼 수 있어야 합니다.
3. **학생으로서**, 내가 현재 어느 단계에 있는지(자기 인식, 세계 탐색 등) 알 수 있어서, 다음에 무엇을 해야 할지 알 수 있어야 합니다.
4. **학생으로서**, 내가 어떤 역량을 키우고 있는지(탐구력, 창조력 등) 자동으로 측정되어서, 내 강점을 알 수 있어야 합니다.
5. **학생으로서**, "이거다!" 하는 깨달음의 순간이 기록되어서, 나중에 그 순간을 다시 볼 수 있어야 합니다.
6. **멘토로서**, 학생의 대화에서 핵심 철학(양자적 자기 발견, AI 시대 역량)을 자연스럽게 전달할 수 있어야 합니다.
7. **시스템으로서**, 모든 대화를 WXSPERTA 8층 구조로 자동 분류하여 저장할 수 있어야 합니다.

## Functional Requirements

### FR1: 철학 반영 시스템 프롬프트
- 시스템은 제공된 HTML 문서의 핵심 철학을 시스템 프롬프트에 포함해야 합니다.
- 핵심 철학: AI 시대 역설, 양자적 자기 발견, 미래 자아 가이던스, 터널링 인젝션, 방황=탐험
- 각 에이전트의 WXSPERTA 속성(worldView, context, process 등)을 프롬프트에 포함해야 합니다.

### FR2: 학생 언어 사용
- 시스템은 존댓말이 아닌 반말("~야", "~어", "~지")로 대화해야 합니다.
- 학생의 언어를 그대로 사용해야 합니다 ("어려워요" → "어려워?").
- 친근하고 격려하는 톤을 유지해야 합니다 ("잘하고 있어!", "멋져!").

### FR3: 대화 저장
- 모든 대화(사용자 메시지, AI 응답)를 `mdl_wxsperta_interactions` 테이블에 저장해야 합니다.
- 세션 ID를 통해 대화를 그룹화할 수 있어야 합니다.
- 대화 컨텍스트를 `mdl_wxsperta_chat_contexts` 테이블에 업데이트해야 합니다.

### FR4: WXSPERTA 레이어 자동 추출
- 각 대화에서 WXSPERTA 8층 구조에 해당하는 내용을 LLM을 통해 자동 추출해야 합니다.
- 추출된 레이어는 `mdl_wxsperta_conversation_layers` 테이블에 저장해야 합니다.
- 추출 신뢰도(confidence_score)를 기록해야 합니다.
- 학생 승인 여부(is_approved)를 추적해야 합니다 (기본값: 0, 자동 추출이므로).

### FR5: 자기 발견 여정 추적
- 대화 내용에서 여정 단계를 자동 감지해야 합니다 (키워드 기반 또는 LLM 기반).
- `mdl_wxsperta_journey_tracking` 테이블에 여정 단계를 기록해야 합니다.
- 각 단계의 진행률(phase_progress)을 계산해야 합니다.
- 핵심 깨달음(key_insights), 도전(challenges_faced), 돌파구(breakthroughs)를 기록해야 합니다.

### FR6: AI 시대 역량 측정
- 대화 내용에서 4대 역량(탐구력, 창조력, 연결력, 적응력)을 자동 측정해야 합니다.
- `mdl_wxsperta_competency_tracking` 테이블에 역량 점수를 기록해야 합니다.
- 역량 증거(evidence)를 텍스트로 저장해야 합니다.

### FR7: 양자적 자기 발견 상태 추적
- 대화에서 양자 상태(중첩, 붕괴, 터널링 등)를 자동 감지해야 합니다.
- `mdl_wxsperta_quantum_states` 테이블에 상태를 기록해야 합니다.
- 깨달음의 순간(breakthrough_moment)을 저장해야 합니다.

### FR8: 사용자 프로필 확장
- `mdl_wxsperta_chat_contexts` 테이블에 다음 필드를 추가해야 합니다:
  - conversation_phase: 현재 여정 단계
  - quantum_state: 양자 상태
  - ai_era_competencies: 4대 역량 점수 (JSON)
  - mentoring_year: 멘토링 연차 (1, 2, 3)
  - self_clarity_score: 자기 명확성 점수 (0-100)
  - direction_confidence: 방향 확신도 (0-100)
  - exploration_breadth: 탐색 폭 (탐색한 분야 수)
  - core_philosophy: 핵심 철학 텍스트

### FR9: 대화 API 확장
- `agent_chat_api.php`의 `sendMessage` 함수를 확장하여 WXSPERTA 추출 및 여정 추적을 수행해야 합니다.
- 또는 새로운 `conversation_processor.php` 파일을 생성하여 대화 후처리를 담당해야 합니다.

### FR10: 위기 상황 대응
- 시스템은 위기 상황(방향 상실, 좌절, AI 불안 등)을 감지하고 적절한 멘토 멘트를 제공해야 합니다.
- 위기 상황별 대응 멘트를 시스템 프롬프트에 포함해야 합니다.

### FR11: Moodle과 독립적인 Standalone UI
- 시스템은 Moodle UI(테마/헤더/내비게이션)와 분리된 Standalone UI를 제공해야 합니다.
- Standalone UI는 `alt42/studenthome/wxsperta/standalone_ui/` 경로의 정적 HTML/CSS/JS로 구성되어야 합니다.
- Standalone UI는 **Moodle 세션 쿠키 기반 인증**을 사용하며, API는 기존대로 `require_login()`을 유지해야 합니다.
- 비로그인 상태(세션 만료 포함)에서는 Standalone UI가 “로그인이 필요해” 안내를 표시하고 로그인 경로로 유도해야 합니다.

## Non-Goals (Out of Scope)

1. **실시간 벡터 DB 연동**: 벡터 임베딩 저장은 향후 구현 (현재는 메타데이터만 저장)
2. **학생 승인 UI**: WXSPERTA 레이어 추출 결과에 대한 학생 승인 UI는 별도 작업
3. **대시보드 시각화**: 여정 추적, 역량 측정 결과를 보여주는 대시보드는 별도 작업
4. **멘토 알림 시스템**: 멘토에게 학생 상태 변화를 알리는 알림 시스템은 별도 작업
5. **다국어 지원**: 현재는 한국어만 지원

## Design Considerations

### UI/UX
- 기존 `wxsperta_app.js`의 채팅 인터페이스를 그대로 사용
- WXSPERTA 레이어 추출 결과는 백그라운드에서 처리 (UI 표시 없음)
- 대화는 자연스럽게 진행되며, 추출은 투명하게 수행

### Standalone UI/UX (Moodle UI 분리)
- Standalone UI는 Moodle UI와 분리된 단독 화면으로 제공한다.
- 기능 구성(최소):
  - 에이전트 목록/검색/카테고리 필터
  - 대화 패널(선택 에이전트와 채팅)
  - 세션 유지(브라우저 메모리/필요시 로컬스토리지)
  - 로그인 가드(세션 만료 시 안내)

### 학생 언어 스타일 가이드
- 반말 사용: "~야", "~어", "~지", "~해"
- 격려 표현: "잘하고 있어!", "멋져!", "그래도 해봐!"
- 질문 유도: "왜 그런 거 같아?", "만약에 ~한다면?"
- 공감 표현: "그렇구나", "이해해", "힘들었겠다"

### 철학 통합 방식
- 시스템 프롬프트에 핵심 철학을 상수로 정의
- 각 에이전트별로 철학을 개인화하여 적용
- 대화 맥락에 따라 적절한 철학 메시지 선택

## Technical Considerations

### 데이터베이스
- 기존 `mdl_wxsperta_chat_contexts` 테이블에 컬럼 추가 (ALTER TABLE)
- 새로운 테이블 3개 생성:
  - `mdl_wxsperta_conversation_layers`: 대화별 WXSPERTA 레이어 저장
  - `mdl_wxsperta_journey_tracking`: 자기 발견 여정 추적
  - `mdl_wxsperta_competency_tracking`: AI 시대 역량 측정
  - `mdl_wxsperta_quantum_states`: 양자적 자기 발견 상태 추적

### API 구조
- 기존 `agent_chat_api.php`를 확장하거나
- 새로운 `conversation_processor.php`를 생성하여 대화 후처리 담당
- 비동기 처리 고려 (대화 응답 후 백그라운드에서 추출 작업)

### Standalone UI 데이터 제공 API
- Standalone UI에서 필요한 최소 데이터를 제공하는 `standalone_api.php` 엔드포인트를 추가한다.
  - 예: `action=get_agents`, `action=get_user_state`
- 인증은 Moodle 세션 기반(`require_login()`)을 유지한다.

### LLM 활용
- OpenAI API를 사용하여 WXSPERTA 레이어 추출
- Function Calling 또는 구조화된 프롬프트로 JSON 응답 받기
- 에러 처리 및 폴백 메커니즘 필요

### 성능 고려사항
- 대화 응답은 즉시 반환 (사용자 경험 우선)
- WXSPERTA 추출은 비동기로 처리 가능
- LLM 호출 최소화 (필요시에만 추출)

### 기존 시스템 통합
- `chat_bridge.php`의 기존 로직 활용
- `event_bus.php`를 통한 이벤트 발행 고려
- `llm_orchestrator.php`의 Holon Loop와 연계 가능

## Success Metrics

1. **대화 품질**: 학생이 "친구처럼 편하게 대화할 수 있다"고 느끼는 비율 80% 이상
2. **WXSPERTA 저장률**: 대화의 70% 이상에서 최소 1개 이상의 레이어 추출 성공
3. **여정 추적 정확도**: 여정 단계 자동 감지 정확도 75% 이상
4. **역량 측정 정확도**: 역량 점수와 실제 활동의 상관관계 0.6 이상
5. **시스템 안정성**: 대화 처리 오류율 5% 이하
6. **응답 속도**: 대화 응답 시간 3초 이하 (LLM 호출 포함)

## Open Questions

1. **WXSPERTA 추출 타이밍**: 매 대화마다 추출할지, 세션 종료 시 일괄 추출할지?
   - **제안**: 매 대화마다 추출하되, 비동기 처리로 응답 속도 유지

2. **학생 승인 프로세스**: 자동 추출된 레이어에 대한 학생 승인은 언제 요청할지?
   - **제안**: 별도 UI에서 주기적으로 승인 요청 (본 PRD 범위 외)

3. **여정 단계 전환 기준**: 언제 다음 단계로 넘어가는지 판단 기준은?
   - **제안**: 키워드 빈도 + LLM 판단 조합

4. **역량 점수 계산 방식**: 키워드 기반인지, LLM 기반인지?
   - **제안**: 초기에는 키워드 기반, 향후 LLM 기반으로 개선

5. **양자 상태 감지 정확도**: 자동 감지의 신뢰도를 어떻게 보장할지?
   - **제안**: 신뢰도 점수와 함께 저장, 향후 수동 검증 가능

6. **기존 대화 데이터**: 이미 저장된 대화 데이터는 어떻게 처리할지?
   - **제안**: 마이그레이션 스크립트로 기존 데이터 처리 (별도 작업)

