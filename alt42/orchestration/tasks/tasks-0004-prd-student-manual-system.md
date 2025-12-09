## Relevant Files

- `alt42/orchestration/agents/studentmanual/index.php` - 메인 메뉴얼 페이지 (학생용 인터페이스)
- `alt42/orchestration/agents/studentmanual/index.php` - 메인 메뉴얼 페이지 단위 테스트
- `alt42/orchestration/agents/studentmanual/admin/index.php` - 교사용 관리 페이지
- `alt42/orchestration/agents/studentmanual/admin/index.php` - 관리 페이지 단위 테스트
- `alt42/orchestration/agents/studentmanual/api/search.php` - 검색 API 엔드포인트
- `alt42/orchestration/agents/studentmanual/api/search.php` - 검색 API 단위 테스트
- `alt42/orchestration/agents/studentmanual/api/upload_content.php` - 컨텐츠 업로드 API
- `alt42/orchestration/agents/studentmanual/api/upload_content.php` - 업로드 API 단위 테스트
- `alt42/orchestration/agents/studentmanual/api/manage_item.php` - 메뉴얼 항목 관리 API (CRUD)
- `alt42/orchestration/agents/studentmanual/api/manage_item.php` - 관리 API 단위 테스트
- `alt42/orchestration/agents/studentmanual/db/migration_create_tables.php` - 데이터베이스 테이블 생성 마이그레이션
- `alt42/orchestration/agents/studentmanual/includes/error_handler.php` - 에러 핸들러 (파일 경로 및 라인 번호 포함)
- `alt42/orchestration/agents/studentmanual/includes/content_validator.php` - 컨텐츠 검증 유틸리티
- `alt42/orchestration/agents/studentmanual/includes/content_validator.php` - 검증 유틸리티 단위 테스트
- `alt42/orchestration/agents/studentmanual/assets/css/manual.css` - 메뉴얼 스타일시트
- `alt42/orchestration/agents/studentmanual/assets/js/manual.js` - 메뉴얼 JavaScript (검색/필터링)
- `alt42/orchestration/agents/studentmanual/assets/js/admin.js` - 관리 페이지 JavaScript
- `alt42/orchestration/agents/studentmanual/uploads/` - 업로드된 파일 저장 디렉토리

### Notes

- 모든 PHP 파일은 Moodle 설정을 포함하고 `require_login()`을 사용해야 합니다.
- 에러 메시지는 반드시 파일 경로와 라인 번호를 포함해야 합니다.
- 파일 업로드는 `/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/agents/studentmanual/uploads/` 디렉토리에 저장됩니다.
- 데이터베이스 테이블은 Moodle의 xmldb_table을 사용하여 생성합니다.

## Tasks

- [ ] 1.0 데이터베이스 구조 설계 및 마이그레이션 스크립트 작성
  - [ ] 1.1 `mdl_at42_studentmanual_items` 테이블 구조 설계 (id, title, description, agent_id, created_at, updated_at, created_by 등)
  - [ ] 1.2 `mdl_at42_studentmanual_contents` 테이블 구조 설계 (id, content_type, file_path, external_url, file_size, mime_type, created_at 등)
  - [ ] 1.3 `mdl_at42_studentmanual_item_contents` 연결 테이블 구조 설계 (item_id, content_id, display_order)
  - [ ] 1.4 마이그레이션 스크립트 작성 (`db/migration_create_tables.php`) - xmldb_table 사용
  - [ ] 1.5 테이블 인덱스 추가 (agent_id, content_type, created_at 등)
  - [ ] 1.6 마이그레이션 스크립트 테스트 및 검증
  - [ ] 1.7 에러 핸들러 생성 (`includes/error_handler.php`) - 파일 경로 및 라인 번호 포함

- [ ] 2.0 메인 메뉴얼 페이지 개발 (학생용 인터페이스)
  - [ ] 2.1 기본 PHP 구조 작성 (`index.php`) - Moodle 설정 포함, require_login()
  - [ ] 2.2 사용자 역할 확인 로직 구현 (학생만 접근 가능)
  - [ ] 2.3 데이터베이스에서 메뉴얼 항목 조회 로직 구현 (에이전트별 그룹화)
  - [ ] 2.4 HTML 구조 작성 (헤더, 검색 바, 필터 영역, 메뉴얼 카드 그리드)
  - [ ] 2.5 CSS 스타일시트 작성 (`assets/css/manual.css`) - 학생 친화적 디자인, 반응형 레이아웃
  - [ ] 2.6 에이전트별 카드 컴포넌트 스타일링 (카드 레이아웃, 호버 효과)
  - [ ] 2.7 모바일 반응형 디자인 구현 (미디어 쿼리)
  - [ ] 2.8 메뉴얼 상세 페이지 구조 작성 (제목, 설명, 컨텐츠 표시 영역)
  - [ ] 2.9 이미지 확대 기능 구현 (모달 또는 라이트박스)
  - [ ] 2.10 동영상/음성 재생 플레이어 구현 (HTML5 video/audio 태그)

- [ ] 3.0 검색 및 필터링 기능 구현
  - [ ] 3.1 검색 API 엔드포인트 작성 (`api/search.php`) - 키워드 검색
  - [ ] 3.2 SQL 쿼리 구현 (제목, 설명에서 키워드 검색, LIKE 또는 FULLTEXT)
  - [ ] 3.3 에이전트별 필터링 로직 구현 (다중 선택 지원)
  - [ ] 3.4 검색 결과 정렬 로직 구현 (최신순, 제목순 등)
  - [ ] 3.5 JavaScript 검색 기능 구현 (`assets/js/manual.js`) - 실시간 검색
  - [ ] 3.6 필터링 UI 구현 (에이전트 체크박스 또는 드롭다운)
  - [ ] 3.7 검색/필터링 결과 동적 업데이트 (AJAX)
  - [ ] 3.8 검색 결과 없음 메시지 표시
  - [ ] 3.9 검색 API 단위 테스트 작성

- [ ] 4.0 컨텐츠 업로드 및 관리 시스템 개발
  - [ ] 4.1 컨텐츠 검증 유틸리티 작성 (`includes/content_validator.php`) - 파일 타입, 크기 검증
  - [ ] 4.2 업로드 디렉토리 생성 및 권한 설정 (`uploads/` 디렉토리)
  - [ ] 4.3 이미지 업로드 API 작성 (`api/upload_content.php`) - 최대 10MB 제한
  - [ ] 4.4 동영상/음성 업로드 API 작성 - 최대 100MB 제한, 파일 타입 검증
  - [ ] 4.5 외부 링크 처리 로직 구현 (YouTube, Vimeo URL 검증)
  - [ ] 4.6 파일명 생성 로직 구현 (타임스탬프 기반 중복 방지)
  - [ ] 4.7 업로드된 파일 정보 데이터베이스 저장 로직
  - [ ] 4.8 파일 삭제 기능 구현 (물리적 파일 및 DB 레코드 삭제)
  - [ ] 4.9 업로드 API 에러 처리 (파일 경로 및 라인 번호 포함)
  - [ ] 4.10 업로드 API 단위 테스트 작성

- [ ] 5.0 교사용 관리 인터페이스 개발
  - [ ] 5.1 관리 페이지 기본 구조 작성 (`admin/index.php`) - 교사 권한 확인
  - [ ] 5.2 메뉴얼 항목 관리 API 작성 (`api/manage_item.php`) - CRUD 기능
  - [ ] 5.3 메뉴얼 항목 생성 기능 구현 (제목, 설명, 에이전트 ID 입력)
  - [ ] 5.4 메뉴얼 항목 수정 기능 구현 (기존 데이터 로드 및 업데이트)
  - [ ] 5.5 메뉴얼 항목 삭제 기능 구현 (연결된 컨텐츠도 함께 삭제)
  - [ ] 5.6 컨텐츠 연결 기능 구현 (메뉴얼 항목에 컨텐츠 추가/제거)
  - [ ] 5.7 관리 페이지 UI 작성 (항목 목록, 추가/수정 폼)
  - [ ] 5.8 파일 업로드 UI 구현 (드래그 앤 드롭 또는 파일 선택)
  - [ ] 5.9 컨텐츠 미리보기 기능 구현 (업로드 전 미리보기)
  - [ ] 5.10 관리 페이지 JavaScript 작성 (`assets/js/admin.js`) - 폼 처리, AJAX 통신
  - [ ] 5.11 관리 API 단위 테스트 작성

