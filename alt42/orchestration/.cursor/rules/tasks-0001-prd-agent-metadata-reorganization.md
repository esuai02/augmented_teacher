# Tasks: 에이전트별 Metadata 재배치 및 관련성 최적화

## Relevant Files

- `alt42/orchestration/agents/**/rules/mission.md` - 각 에이전트의 미션 정의 파일 (22개)
- `alt42/orchestration/agents/**/rules/metadata.md` - 각 에이전트의 메타데이터 파일 (22개)
- `alt42/orchestration/agents/**/rules/dataindex.html` - 각 에이전트의 데이터 인덱스 HTML 파일 (22개)
- `alt42/orchestration/agents/agent01_onboarding/rules/dataindex.html` - 참조용 메인 데이터 인덱스
- `alt42/orchestration/.cursor/rules/metadata-reorganization-analyzer.py` - 관련성 분석 스크립트 (생성 필요)
- `alt42/orchestration/.cursor/rules/metadata-reorganizer.py` - 메타데이터 재배치 스크립트 (생성 필요)
- `alt42/orchestration/.cursor/rules/related-data-updater.py` - dataindex.html 업데이트 스크립트 (생성 필요)

### Notes

- Python 스크립트는 서버 환경(Python 3.10.12)에서 실행 가능해야 함
- 모든 파일 작업 전 백업 필수
- 단계별 검증을 통해 진행

## Tasks

- [ ] 1.0 현재 상태 분석 및 데이터 수집
  - [ ] 1.1 모든 에이전트 폴더 목록 확인 (agent01~agent22)
  - [ ] 1.2 각 에이전트의 mission.md 파일 읽기 및 핵심 키워드 추출
  - [ ] 1.3 각 에이전트의 metadata.md 파일 읽기 및 데이터 항목 목록 생성
  - [ ] 1.4 agent01_onboarding의 metadata.md를 기준 데이터셋으로 사용 (100개 항목)
  - [ ] 1.5 모든 에이전트의 metadata.md에서 데이터 항목 중복 확인

- [ ] 2.0 관련성 분석 스크립트 개발
  - [ ] 2.1 mission.md 파싱 함수 작성 (핵심 키워드 및 기능 추출)
  - [ ] 2.2 metadata.md 파싱 함수 작성 (데이터 항목 및 카테고리 추출)
  - [ ] 2.3 관련성 점수 계산 함수 작성 (직접/간접/보조 관련성)
  - [ ] 2.4 관련성 매트릭스 생성 함수 작성 (데이터 항목 × 에이전트)
  - [ ] 2.5 관련성 매트릭스를 JSON/YAML 파일로 저장

- [ ] 3.0 1차 관련성 검증 및 주 담당 에이전트 결정
  - [ ] 3.1 각 데이터 항목에 대해 모든 에이전트와의 관련성 점수 계산
  - [ ] 3.2 각 데이터 항목의 최고 점수 에이전트를 주 담당 에이전트로 결정
  - [ ] 3.3 주 담당 에이전트별 데이터 목록 생성
  - [ ] 3.4 관련성 점수 0인 데이터 항목 확인 및 처리

- [ ] 4.0 2차 관련성 검증 (보조 관련 에이전트 식별)
  - [ ] 4.1 주 담당 에이전트 외 관련성 점수 2점 이상인 에이전트 목록 작성
  - [ ] 4.2 각 데이터 항목의 관련 에이전트 목록 생성 (주 담당 + 보조 관련)
  - [ ] 4.3 관련 에이전트 매핑 데이터 구조 생성

- [ ] 5.0 3차 검증 및 최적화
  - [ ] 5.1 주 담당 에이전트에 배치된 데이터가 해당 에이전트 mission과 일치하는지 검증
  - [ ] 5.2 관련성 점수가 낮은 데이터(1점 이하) 재검토 및 재배치
  - [ ] 5.3 중복 데이터 제거 (주 담당 에이전트에만 유지)
  - [ ] 5.4 모든 100개 데이터 항목이 적절한 에이전트에 배치되었는지 확인

- [ ] 6.0 metadata.md 파일 업데이트
  - [ ] 6.1 각 에이전트의 기존 metadata.md 백업 (metadata.md.backup)
  - [ ] 6.2 각 에이전트의 새로운 metadata.md 생성 (주 담당 데이터만 포함)
  - [ ] 6.3 카테고리 구조 유지 (10개 카테고리)
  - [ ] 6.4 각 에이전트별 데이터 항목 번호 재매핑 (1부터 시작)

- [ ] 7.0 관련 데이터 매핑 파일 생성
  - [ ] 7.1 각 에이전트별 관련 데이터 목록 생성 (다른 에이전트에 배치된 데이터)
  - [ ] 7.2 관련 데이터 매핑 JSON 파일 생성 (agent_related_data_mapping.json)
  - [ ] 7.3 매핑 파일 구조: {agent_name: {related_data: [{name, category, assigned_agent, relevance_score}]}}

- [ ] 8.0 dataindex.html 업데이트 스크립트 개발
  - [ ] 8.1 dataindex.html 파싱 함수 작성
  - [ ] 8.2 관련 데이터 섹션 추가 함수 작성
  - [ ] 8.3 관련 에이전트 링크 생성 함수 작성
  - [ ] 8.4 HTML 구조 유지하면서 새 섹션 삽입

- [ ] 9.0 dataindex.html 파일 업데이트
  - [ ] 9.1 각 에이전트의 dataindex.html 백업
  - [ ] 9.2 각 에이전트의 dataindex.html에 "관련 데이터" 섹션 추가
  - [ ] 9.3 관련 데이터 표시 (데이터 이름, 카테고리, 배치된 에이전트, 관련성 점수)
  - [ ] 9.4 관련 에이전트로 이동하는 링크 추가

- [ ] 10.0 최종 검증 및 리포트 생성
  - [ ] 10.1 재배치 후 데이터 무결성 검증 (100개 항목 모두 배치 확인)
  - [ ] 10.2 각 에이전트의 데이터 항목 수 및 관련성 점수 통계 생성
  - [ ] 10.3 재배치 리포트 생성 (reorganization_report.md)
  - [ ] 10.4 변경 사항 요약 및 각 에이전트별 변경 내역 문서화

