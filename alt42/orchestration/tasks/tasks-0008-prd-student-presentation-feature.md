## Relevant Files

- `alt42/teachingsupport/AItutor/db/schema_presentation.sql` - 학생 발표 텍스트/분석 저장용 신규 테이블 스키마.
- `alt42/teachingsupport/AItutor/api/transcribe_presentation.php` - Whisper STT(음성→텍스트) 변환 API(음성 영구저장 금지, 임시파일 후 삭제).
- `alt42/teachingsupport/AItutor/api/save_presentation.php` - 발표 기록 생성/업데이트(텍스트/분석/선택 페르소나) API.
- `alt42/teachingsupport/AItutor/api/analyze_presentation.php` - 발표 텍스트 기반 취약 페르소나(60개) 분석 API.
- `alt42/teachingsupport/AItutor/api/get_presentation.php` - `presentation_id` 기반 조회 API(quantum_modeling 연동).
- `alt42/teachingsupport/AItutor/ui/learning_interface.php` - “발표하기” 버튼/컨트롤 UI 추가 및 JS 로딩.
- `alt42/teachingsupport/AItutor/ui/learning_interface.js` - 발표 녹음/일시정지/종료/분석/선택/이동 플로우 연결.
- `alt42/teachingsupport/AItutor/ui/learning_interface.css` - 발표 컨트롤/모달 스타일 추가.
- `alt42/teachingsupport/AItutor/ui/quantum_modeling.php` - `presentation_id` 처리 및 발표 텍스트를 음성해설 맵 입력으로 주입.
- `alt42/teachingsupport/AItutor/ui/quantum_modeling.js` - (기존) 음성해설 맵 자동 재생 트리거 확장.
- `alt42/teachingsupport/AItutor/api/analyze_tts_script.php` - (기존) 텍스트→노드 시퀀스 추출을 발표 텍스트에도 재사용.

### Notes

- Moodle 환경: `include_once("/home/moodle/public_html/moodle/config.php");`, `global $DB, $USER;`, `require_login();` 필수.
- 에러 메시지는 파일 경로와 라인 정보를 포함해야 한다.
- 음성 파일은 서버에 영구 저장하지 않는다(임시 파일 생성 후 Whisper 전송, 즉시 삭제).
- 기존 `ktm_teaching_interactions`는 본 기능에서 사용하지 않는다.
- 페르소나별 음성 가이드 파일 링크는 현재 placeholder로 처리(향후 실제 링크 연결 예정).

## Tasks

- [ ] 1.0 Database schema 추가 (학생 발표 기록)
  - [ ] 1.1 `mdl_at_student_presentations` 테이블 스키마 설계(필수: userid, contentsid, contentstype, nrepeat)
  - [ ] 1.2 `schema_presentation.sql` 작성 및 설치 경로/절차 문서화(운영 서버 반영 시 주의 포함)
  - [ ] 1.3 nrepeat 산정 규칙 정의(동일 userid+contentsid+contentstype 기준으로 +1)

- [ ] 2.0 발표 STT/저장/분석 API 구현
  - [ ] 2.1 `transcribe_presentation.php`: base64/webm 업로드 → 임시파일 생성 → Whisper STT → 텍스트 반환(임시파일 삭제)
  - [ ] 2.2 `save_presentation.php`: 발표 세션 생성(초기 레코드 생성) 및 결과 업데이트(텍스트/분석/선택 페르소나)
  - [ ] 2.3 `analyze_presentation.php`: 발표 텍스트 → 취약 페르소나(60개) JSON 응답
  - [ ] 2.4 `get_presentation.php`: `presentation_id`로 발표 텍스트/분석 결과 반환(quantum_modeling에서 사용)
  - [ ] 2.5 모든 API 에러 응답에 file/line 포함

- [ ] 3.0 learning_interface UI/UX (발표하기)
  - [ ] 3.1 “발표하기” 버튼 추가(헤더 영역)
  - [ ] 3.2 발표 컨트롤(타이머, 일시정지/재개, 종료) UI 추가
  - [ ] 3.3 중앙 얼굴 아이콘 말풍선으로 상태 멘트 출력(시작/일시정지/재개/마무리/이후 안내)
  - [ ] 3.4 발표 종료 후 로딩/분석 상태 표시

- [ ] 4.0 페르소나 선택 및 음성 가이드 재생(placeholder)
  - [ ] 4.1 분석 결과(취약 페르소나) 표시 모달 추가
  - [ ] 4.2 학생이 페르소나 선택 → placeholder 오디오 URL 생성 및 재생
  - [ ] 4.3 선택 결과를 발표 레코드에 저장(selected_persona_ids_json)

- [ ] 5.0 quantum_modeling 연동(음성해설 맵 자동재생)
  - [ ] 5.1 `quantum_modeling.php`에서 `presentation_id` 파라미터 처리 및 발표 텍스트 조회
  - [ ] 5.2 발표 텍스트를 음성해설 맵 입력으로 설정(`ttsScript` 대체 또는 병행)
  - [ ] 5.3 `analyze_tts_script.php`로 노드 시퀀스를 추출하고 자동 재생 트리거


