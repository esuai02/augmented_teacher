# 에이전트 팝업/연결 설계 스펙 v1 (Target Agent 1~21)

본 문서는 모든 에이전트 팝업에서 **분석결과 요약 → 타겟 에이전트 선택(1~21) → 타겟 준비 프롬프트 입력/표시 → 준비된 결과 표시 → 자동발견으로 양방향 연결 표기**가 일관되게 동작하도록 정의한다. 생성되는 메타데이터/이벤트 규격을 통일하여, 각 에이전트가 상호 참조 및 자동발견(autodiscovery)으로 연결 정보를 표면화할 수 있게 한다.

---

## 0. 용어

* **현재 에이전트(Current Agent, CA)**: 팝업을 띄운 주체.
* **타겟 에이전트(Target Agent, TA)**: CA가 분석결과를 전달하려는 상대(1~21 중 하나).
* **분석결과(Analysis Result)**: 에이전트가 산출한 구조화/비구조화 결과.
* **준비 프롬프트(Preparation Prompt)**: TA가 바로 활용할 수 있도록 변환/요약/정규화한 입력.
* **준비된 결과(Prepared Output)**: 위 프롬프트 실행으로 생성된 결과(요약 데이터, 행동계획 등).
* **자동발견(Auto-discovery)**: 연결 메타데이터를 구독/검색하여 TA에서 “나를 향한 전달물”을 자동 표시.

---

## 1. UX 플로우(팝업 공통)

1. **분석결과 요약(Text 영역)**

   * 요약 텍스트(필수) + 펼쳐보기(원문/첨부)
   * 태그/스코프/버전 정보 표시(선택)
2. **타겟 에이전트 선택(1~21)**

   * 드롭다운(검색 가능) + 최근 사용 고정핀(최대 5)
   * 선택 시 기존 “준비 프롬프트/준비된 결과” 자동 로드(있으면 프리필/리스트업)
3. **준비 프롬프트 입력/편집**

   * 프리셋 템플릿 다중(요약/계획/명령/데이터패키지 등)
   * 기존안이 있으면 **버전 선택** + **수정 후 새 버전으로 저장** 제공
4. **준비된 결과(렌더 영역)**

   * 최신 실행 결과 우선 표시 + 과거 버전 히스토리 탭
   * 구조화 뷰(표/태스크/지식카드) + 원문 JSON 토글
5. **연결 정보 미리보기**

   * `CA → TA` 링크 카드 + 역방향 자동발견 상태(“TA에서 노출됨/검수 대기/오류”)
6. **저장 & 전파**

   * [저장] 메타데이터 커밋 → [전파] 이벤트 버스 발행 → TA에서 자동발견 표시

---

## 2. 화면 구성(컴포넌트 계약)

* **AnalysisSummary**: 텍스트 요약, "더보기"로 원문/파일.
* **TargetAgentSelect**: 1~21 에이전트 선택(검색/핀/최근).
* **PrepPromptEditor**: 프리셋/버전/검수 상태.
* **PreparedResultPanel**: 카드/테이블/타임라인/원본 JSON 토글.
* **LinkPreview**: 연결상태 배지(노출/대기/오류) + 마지막 싱크 시간.
* **Actions**: 저장/전파/실행/초안저장/되돌리기.

---

## 3. 데이터 모델(표준 스키마)

### 3.1 Agent Registry

```json
{
  "agent_id": 1,
  "name": "Agent-01",
  "capabilities": ["plan", "summarize"],
  "inbox_channel": "agent://1/inbox",
  "outbox_channel": "agent://1/outbox",
  "visibility": "public|internal"
}
```

### 3.2 Analysis Artifact

```json
{
  "artifact_id": "artf_20251017_xxx",
  "agent_id": 3,
  "summary_text": "...",
  "full_blob_ref": "blob://...",
  "tags": ["topic:x", "priority:high"],
  "created_at": "2025-10-17T09:00:00Z",
  "schema_version": "1.0"
}
```

### 3.3 Link(핵심)

```json
{
  "link_id": "lnk_...",
  "source_agent_id": 3,
  "target_agent_id": 12,
  "artifact_id": "artf_...",
  "prep_prompt_version_id": "ppv_...",
  "prepared_output_version_id": "pov_...",
  "status": "published|draft|error",
  "autodiscovery_state": "visible|pending|error",
  "created_at": "...",
  "updated_at": "..."
}
```

### 3.4 Preparation Prompt Version

```json
{
  "ppv_id": "ppv_...",
  "link_id": "lnk_...",
  "prompt_text": "...",
  "meta": {"preset": "plan", "language": "ko"},
  "created_by": "user|agent",
  "created_at": "...",
  "replaces": "ppv_prev|null"
}
```

### 3.5 Prepared Output Version

```json
{
  "pov_id": "pov_...",
  "link_id": "lnk_...",
  "render_hint": "table|cards|timeline|raw",
  "payload": {"...": "..."},
  "created_by": "agent|system",
  "created_at": "...",
  "origin_run_id": "run_..."
}
```

---

## 4. 이벤트 & 자동발견(Autodiscovery)

### 4.1 이벤트 버스 표준

```json
{
  "type": "link.published",
  "link_id": "lnk_...",
  "source_agent_id": 3,
  "target_agent_id": 12,
  "artifact_id": "artf_...",
  "ppv_id": "ppv_...",
  "pov_id": "pov_...",
  "timestamp": "..."
}
```

* TA는 `target_agent_id` 구독. 수신 시 **Inbox Index**에 등록 후 배지 노출.

### 4.2 TA 측 표시 규칙

* **현재 에이전트 화면에서도** “나를 타겟으로 한 에이전트들” 사이드패널에 목록 표시

  * 카드: `SourceAgent → (PrepPreset) 제목 | 최근 업데이트 | 상태배지`
  * 클릭 시 준비 프롬프트/결과 열람 가능

### 4.3 충돌/중복 처리

* 동일 `artifact_id + target_agent_id` 조합의 다중 링크 허용하되, **최신 ppv/pov**를 기본 표시.
* 중복 감지 규칙(hash of summary_text + preset)로 합치기 제안 배너.

---

## 5. 상태머신(State Machine)

```
[DRAFT] --저장--> [PUBLISHED] --전파--> [VISIBLE at TA]
   |                       |--실패--> [ERROR]
   |--삭제--> [ARCHIVED]
```

* `autodiscovery_state`는 `pending → visible|error` 전이.

---

## 6. API 계약(예시)

* `POST /links` : 링크 생성(프롬프트/출력 버전 포함 가능)
* `GET /links?agent_id=...&role=target|source` : 연결 조회
* `POST /prep-prompts` : 프롬프트 새 버전 저장
* `POST /prepared-outputs` : 결과 새 버전 저장
* `POST /events` : 이벤트 발행(내부)
* `GET /inbox?agent_id=TA` : TA 수신함 조회(자동발견 결과)

Request 예시:

```http
POST /links
{
  "source_agent_id": 3,
  "target_agent_id": 12,
  "artifact_id": "artf_...",
  "ppv": {"prompt_text": "...", "meta": {"preset": "plan"}},
  "pov": {"render_hint": "cards", "payload": {"tasks": [...]}}
}
```

---

## 7. 보안/권한

* **Link Visibility**: public/internal/restricted
* TA가 restricted일 경우, 발신 에이전트/운영자만 열람 가능(토큰/서명 검증).
* 이벤트는 서명 포함(JWT/JWS) + 재생공격 방지 nonce.

---

## 8. 버저닝/감사로그

* 모든 ppv/pov는 불변 버전 레코드.
* Link는 최신 포인터만 갱신. 감사로그에 전/후 스냅샷 저장.

---

## 9. UI 세부 규칙

* **요약 텍스트**: 300자 내, 나머지 접기.
* **드롭다운**: 에이전트 1~21 정수 ID + 별칭표시.
* **프리셋 템플릿**:

  * `summary-ko`: “타겟이 바로 이해할 핵심 요약(불릿 최대 5)”
  * `plan`: “다음 행동 3스텝”
  * `dataset`: “키-값/테이블/스키마”
  * `command`: “명령형 프롬프트, 파라미터 명시”
* **결과 패널**: 렌더 힌트 따르는 공통 컴포넌트.
* **양방향 노출**: CA 팝업 하단에 “내 결과를 받아간 에이전트들” 목록, TA 화면에는 “나를 타겟으로 한 전달물” 목록.

---
