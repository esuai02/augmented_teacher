# PRD: 학생 발표하기(음성→자막) 기반 페르소나 분석 & 인지맵(음성해설 맵) 자동 재생

## Introduction/Overview

학습 화면([`alt42/teachingsupport/AItutor/ui/learning_interface.php`](alt42/teachingsupport/AItutor/ui/learning_interface.php))에서 학생이 **혼자 음성 발표**를 수행하고, 발표 음성을 **자막(TEXT)으로 변환(STT)**하여 **취약 페르소나를 분석**한 뒤, 선택된 페르소나의 음성 가이드를 재생하고 **양자모델링 인지맵 페이지**([`alt42/teachingsupport/AItutor/ui/quantum_modeling.php`](alt42/teachingsupport/AItutor/ui/quantum_modeling.php))로 이동해 발표 자막을 전달하여 **음성해설 맵(자동 노드 클릭 애니메이션)**을 자동 재생한다.

핵심은 “학생이 말로 설명하며 자신의 풀이를 재구성”하는 과정을 제품화하여, 약점을 **페르소나(60개)** 관점으로 진단하고 즉시 보정 학습으로 연결하는 것이다.

## Goals

1. 학생이 버튼 1개로 발표를 시작/일시정지/재개/종료할 수 있다.
2. 발표 진행 중 상태 멘트(시작/일시정지/재개/마무리/이후 안내)를 **중앙 얼굴 아이콘 말풍선**으로 노출한다.
3. 발표 종료 시 음성을 STT로 텍스트 변환(Whisper)한다.
4. 발표 텍스트 기반으로 취약 페르소나를 분석하고, 학생이 페르소나를 선택해 학습을 진행할 수 있다.
5. 선택 후 `quantum_modeling.php`로 이동하며 발표 자막 텍스트를 전달하여 **음성해설 맵**을 자동 재생한다.

## User Stories

1. **학생으로서**, 발표하기를 눌러 내 풀이를 말로 설명하고 싶다.
2. **학생으로서**, 일시정지/재개가 가능해야 중간에 생각을 정리할 수 있다.
3. **학생으로서**, 발표가 끝나면 내 설명이 자막으로 정리되고, 내가 약한 습관(페르소나)을 알려주길 원한다.
4. **학생으로서**, 약한 페르소나를 선택하면 음성 가이드를 듣고 바로 인지맵에서 내 흐름을 다시 재생해보고 싶다.

## Functional Requirements

### FR-01 발표하기 버튼/컨트롤
- 학습 화면 상단에 “발표하기” 아이콘(버튼)을 제공해야 한다.
- 버튼 클릭 시 발표가 시작되어야 하며, 발표 중에는 **일시정지/재개/종료** 컨트롤이 제공되어야 한다.
- 발표 중 타이머(경과 시간)를 표시해야 한다.

### FR-02 말풍선(중앙 얼굴 아이콘) 상태 멘트
- 발표 시작/일시정지/재개/마무리/이후 안내 멘트를 중앙 얼굴 아이콘의 말풍선으로 표시해야 한다.
- 발표 중에는 학생의 단독 발화 구간이며, 시스템은 자동 개입(말 끊기)을 하지 않는다.

### FR-03 음성 수집 및 STT
- 브라우저에서 마이크를 사용해 음성을 수집한다.
- 발표 종료 시 음성 데이터를 서버로 전송하고, 서버는 OpenAI Whisper를 이용해 텍스트로 변환한다.
- **비기능 요구(중요)**: 음성 파일은 서버에 영구 저장하지 않는다(임시 파일 생성 후 즉시 삭제).

### FR-04 발표 텍스트 저장(별도 DB)
- 발표 텍스트/분석결과 저장을 위한 별도 테이블을 사용한다(기존 `ktm_teaching_interactions` 사용 금지).
- 필수 컬럼: `userid`, `contentsid`, `contentstype`, `nrepeat`
- 발표 기록 식별자(`presentation_id` 또는 `id`)를 생성한다.

### FR-05 페르소나 분석 및 선택
- 발표 텍스트로 취약 페르소나를 분석한다(60개 페르소나 ID 기반).
- 분석 결과를 UI로 표시하고 학생이 하나(또는 복수)를 선택할 수 있어야 한다.
- 선택된 페르소나에 대해 “학습 음성 가이드”를 재생한다.
  - 현재 단계: 음성 파일 링크는 임시(placeholder)로 처리(향후 실제 링크 연결 예정).

### FR-06 quantum_modeling.php로 전달 및 자동 재생
- 페르소나 선택 완료 후 `quantum_modeling.php?id=...&presentation_id=...`로 이동한다.
- `quantum_modeling.php`는 `presentation_id`로 발표 텍스트를 조회한다.
- 조회된 발표 텍스트를 기존 음성해설 맵 분석 흐름(`analyze_tts_script.php`)에 연결하여 노드 시퀀스를 추출하고 자동 재생한다.

## Non-Goals (Out of Scope)

1. 음성 파일의 서버 영구 저장/다운로드 제공
2. 기존 `ktm_teaching_interactions` 기반 저장/조회
3. 페르소나 음성 가이드 파일의 실데이터 연결(지금은 placeholder 링크만 사용)

## Design Considerations

### UI/UX
- 발표 상태 멘트는 학습 화면의 중앙 얼굴 아이콘 말풍선을 재사용(또는 동일 스타일로 확장)한다.
- 발표 컨트롤은 최소 UI로 제공하되, 상태가 명확히 구분되어야 한다(녹음중/일시정지/분석중).

## Technical Considerations

### DB 설계
테이블: `mdl_at_student_presentations`

- 필수: `userid`, `contentsid`, `contentstype`, `nrepeat`
- 추가(권장): `presentation_text`, `analysis_json`, `weak_personas_json`, `selected_persona_ids_json`, `duration_seconds`, `created_at`

### API 설계
- `transcribe_presentation.php`: 음성 Blob → Whisper STT
- `save_presentation.php`: DB 저장/업데이트(텍스트/분석/선택)
- `analyze_presentation.php`: 텍스트 → 취약 페르소나(60개) 추출(JSON)
- `get_presentation.php`: `presentation_id`로 발표 텍스트/분석 조회(quantum_modeling 연동)

### 전달 방식
- `learning_interface.php`에서 발표 완료 후 `presentation_id`를 발급/저장하고,
- 선택 완료 시 `quantum_modeling.php`로 `presentation_id`를 URL 파라미터로 전달한다.

## Success Metrics

- 발표 완료율(시작 대비 종료 비율)
- STT 성공률(에러 없이 텍스트 생성)
- 페르소나 선택률(분석 결과 노출 대비 선택 비율)
- quantum_modeling 자동 재생 도달률(이동 후 자동 재생 성공)

## Open Questions

1. 취약 페르소나를 1개만 선택하게 할지, 복수 선택을 허용할지
2. 발표 텍스트 길이가 매우 길 경우(예: 10분 이상) 분석/노드 추출 시간 제한 정책
3. 동일 컨텐츠에서 nrepeat 정책(무조건 +1, 또는 학생이 선택)


