# agent01_onboarding 데이터 검증 체크리스트

이 문서는 `dataindex.php?agentid=agent01_onboarding` 페이지의 내용을 정교하게 체크하기 위해 필요한 파일 목록입니다.

## 📋 필수 파일 목록

### 1. Metadata 관련 파일

#### 1.1 메타데이터 정의 파일
- **경로**: `alt42/orchestration/agents/agent01_onboarding/rules/metadata.md`
- **용도**: 에이전트가 사용하는 모든 데이터 필드의 메타데이터 정의
- **체크 항목**: 
  - 필드명 일관성
  - 데이터 타입 정의
  - 필수/선택 여부
  - 설명 및 용도

#### 1.2 Rules YAML 파일
- **경로**: `alt42/orchestration/agents/agent01_onboarding/rules/rules.yaml`
- **용도**: 에이전트의 규칙 정의 및 필드 사용 정보
- **체크 항목**:
  - `field:` 패턴으로 정의된 모든 필드
  - `source_type:` (survey/system/generated/interface) 정의
  - `generation_rule:` 존재 여부 (gendata 판단)
  - `depends_on:` 존재 여부 (gendata 판단)
  - `analyze:` 액션 존재 여부 (gendata 판단)

### 2. DB 적용 관련 파일

#### 2.1 Data Access 파일
- **경로**: `alt42/orchestration/agents/agent01_onboarding/rules/data_access.php`
- **용도**: 데이터베이스에서 데이터를 가져오는 로직
- **체크 항목**:
  - `$context['필드명']` 패턴 사용
  - `$onboarding->필드명` 패턴 사용
  - `get_record()` 호출로 조회하는 테이블명
  - 실제 DB 테이블과의 매핑

#### 2.2 View Reports 파일 (인터페이스 입력 확인)
- **경로**: `alt42/studenthome/contextual_agents/beforegoinghome/view_reports.php`
- **용도**: 사용자 인터페이스를 통한 데이터 입력 확인
- **체크 항목**:
  - `$data['필드명']` 패턴 사용
  - `input`, `textarea`, `select` 태그 사용
  - `responses[필드명]` 패턴 사용
  - 사용자 직접 입력 필드 식별

#### 2.3 데이터베이스 스키마 파일
- **경로**: `alt42/orchestration/agents/agent01_onboarding/db_schema.md`
- **용도**: DB 테이블 구조 정의
- **체크 항목**:
  - `alt42o_onboarding` 테이블 필드 목록
  - `alt42_goinghome` 테이블 구조 (JSON 필드)
  - 관련 테이블들 (`mdl_alt42_student_profiles`, `mdl_alt42_calmness` 등)

#### 2.4 DB 생성 스크립트 (선택)
- **경로**: `alt42/orchestrationk/db/create_alt42o_tables.sql`
- **용도**: 실제 DB 테이블 생성 스크립트
- **체크 항목**:
  - 테이블 구조 확인
  - 필드 타입 및 제약조건

### 3. Inputtype 관련 파일

#### 3.1 Rules YAML (재참조)
- **경로**: `alt42/orchestration/agents/agent01_onboarding/rules/rules.yaml`
- **용도**: inputtype 판단의 주요 근거
- **체크 항목**:
  - `source_type: survey` → `survdata`
  - `source_type: system` → `sysdata`
  - `source_type: generated` → `gendata`
  - `source_type: interface` → `uidata`

#### 3.2 Data Access 파일 (재참조)
- **경로**: `alt42/orchestration/agents/agent01_onboarding/rules/data_access.php`
- **용도**: 데이터 소스 확인
- **체크 항목**:
  - 설문 테이블 조회 → `survdata`
  - 시스템 테이블 조회 → `sysdata`
  - LLM 생성 로직 → `gendata`

#### 3.3 View Reports 파일 (재참조)
- **경로**: `alt42/studenthome/contextual_agents/beforegoinghome/view_reports.php`
- **용도**: 사용자 인터페이스 입력 확인
- **체크 항목**:
  - 사용자 직접 입력 필드 → `uidata`

### 4. 통합 분석 파일

#### 4.1 Data Index 파일 (메인)
- **경로**: `alt42/orchestration/agents/agent_orchestration/dataindex.php`
- **용도**: 모든 파일을 통합하여 분석하는 메인 파일
- **체크 항목**:
  - `identifyDataType()` 함수의 로직
  - 필드 매핑 분석 결과
  - DB 적용 여부 판단

#### 4.2 Data Mapping Analysis 파일 (에이전트별)
- **경로**: `alt42/orchestration/agents/agent01_onboarding/rules/data_mapping_analysis.php`
- **용도**: 에이전트별 상세 분석 (선택)
- **체크 항목**: 
  - 에이전트별 특화 분석 로직

## 🔍 체크 프로세스

### Step 1: Metadata 검증
1. `metadata.md` 파일에서 정의된 모든 필드 확인
2. `rules.yaml`에서 사용되는 필드와 비교
3. 누락된 필드 또는 불일치 필드 식별

### Step 2: DB 적용 검증
1. `data_access.php`에서 조회하는 필드 확인
2. `view_reports.php`에서 사용하는 필드 확인
3. 실제 DB 테이블 구조와 비교
4. `db_applied` 플래그가 올바르게 설정되었는지 확인

### Step 3: Inputtype 검증
1. `rules.yaml`의 `source_type` 확인
2. `data_access.php`의 데이터 소스 확인
3. `view_reports.php`의 입력 방식 확인
4. `identifyDataType()` 함수의 판단 로직 검증
5. 최종 inputtype이 올바른지 확인:
   - `uidata`: 사용자 직접 입력
   - `gendata`: LLM/AI 생성
   - `sysdata`: 시스템 자동 입력
   - `survdata`: 설문 응답

## 📊 검증 기준

### Metadata 일관성
- ✅ 모든 필드가 `metadata.md`에 정의되어 있는가?
- ✅ `rules.yaml`의 필드명과 일치하는가?
- ✅ 필드 설명이 명확한가?

### DB 적용 정확성
- ✅ `data_access.php`에서 실제로 조회되는가?
- ✅ DB 테이블에 해당 필드가 존재하는가?
- ✅ `db_applied` 플래그가 올바른가?

### Inputtype 정확성
- ✅ `source_type`이 올바르게 정의되었는가?
- ✅ 실제 데이터 소스와 일치하는가?
- ✅ `identifyDataType()` 함수의 판단이 정확한가?

## 🚨 주의사항

1. **파일 경로**: 서버 환경이므로 절대 경로 사용 (`/home/moodle/public_html/moodle/config.php`)
2. **DB 접근**: Moodle의 `$DB` 객체를 통해 접근
3. **에러 처리**: 모든 파일 읽기 및 DB 조회 시 예외 처리 필요
4. **필드명 일관성**: snake_case 사용 권장
5. **데이터 타입**: JSON 필드의 경우 구조 확인 필요

## 📝 체크리스트 사용법

1. 위 파일들을 순서대로 열어서 확인
2. `dataindex.php` 페이지에서 표시되는 결과와 비교
3. 불일치 사항 발견 시 해당 파일 수정
4. 수정 후 `dataindex.php` 페이지에서 재확인

---

**마지막 업데이트**: 2025-01-28
**관련 URL**: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent_orchestration/dataindex.php?agentid=agent01_onboarding`

