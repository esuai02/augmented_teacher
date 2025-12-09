# MVP 점진적 확장 로드맵

**작성일**: 2025-10-17
**목적**: MVP → V2 Full 점진적 확장 전략 및 마일스톤

---

## 📍 현재 상태: MVP (Baseline)

### MVP 구조 요약
- **테이블**: 5개 (agent_registry, artifacts, links, events, audit_log)
- **외래 키**: 4개 (CASCADE 전략)
- **인덱스**: 13개 (필수 인덱스만)
- **복잡도**: ⭐⭐ (단순)
- **안정성**: ⭐⭐⭐⭐⭐ (매우 안정)

### MVP 핵심 기능
✅ Artifact 생성 및 저장 (16MB 제한)
✅ Link 생성 (source → target)
✅ 단순 프롬프트 입력/저장
✅ 준비된 결과 표시
✅ Target inbox 조회 (autodiscovery)
✅ 다중 링크 (같은 artifact, 다른 target)
✅ 이벤트 로그 기록

### MVP 제약사항
❌ 프롬프트/출력 버전 관리 없음
❌ Soft delete 없음 (하드 삭제만)
❌ 16MB 이상 데이터 저장 불가
❌ 프롬프트 히스토리 없음 (덮어쓰기)

---

## 🚀 Phase 1: MVP 구현 (현재 단계)

**목표**: 기본 링크 시스템 동작 검증
**기간**: 1-2주
**우선순위**: 🔴 Critical

### 1.1 데이터베이스 설치
```bash
# Step 1: MVP 스키마 생성
mysql -u user -p database < create_agent_links_tables_mvp.sql

# Step 2: 무결성 검증
mysql -u user -p database < test_integrity_mvp.sql
```

**검증 체크리스트**:
- [ ] 5개 테이블 생성 확인
- [ ] 22개 에이전트 등록 확인
- [ ] 4개 외래 키 확인
- [ ] 13개 인덱스 확인
- [ ] 모든 무결성 테스트 PASS

### 1.2 기본 API 구현
```php
// File: /api/links_api_mvp.php

// Endpoints:
POST   /api/artifacts              // Artifact 생성
GET    /api/artifacts/:id          // Artifact 조회
POST   /api/links                  // Link 생성
GET    /api/links/:id              // Link 조회
GET    /api/inbox/:agent_id/:student_id   // Inbox 조회
GET    /api/outbox/:agent_id/:student_id  // Outbox 조회
```

**기능 요구사항**:
- [ ] Artifact 생성 (validation: 16MB 제한)
- [ ] Link 생성 (validation: source ≠ target, target ∈ [1,21])
- [ ] Inbox 조회 (status='published', created_at DESC)
- [ ] Outbox 조회 (source_agent_id 필터)
- [ ] 에러 핸들링 (파일명:줄번호 포함)

### 1.3 기본 UI 구현
```javascript
// File: /assets/js/agent_links_mvp.js

// Components:
- AgentLinkModal (팝업)
  ├─ ArtifactSummary (분석결과 요약)
  ├─ TargetSelector (1-21 선택)
  ├─ PromptInput (프롬프트 입력)
  └─ OutputDisplay (결과 표시)

- InboxPanel (수신함)
  └─ LinkCard[] (링크 목록)
```

**UI 요구사항**:
- [ ] Agent 팝업 열기 (각 step 버튼 클릭)
- [ ] Artifact 요약 표시
- [ ] Target agent 드롭다운 (1-21)
- [ ] 프롬프트 입력창 (textarea)
- [ ] 결과 표시 영역 (JSON 렌더링)
- [ ] Inbox 사이드패널 (받은 링크 목록)

### 1.4 통합 테스트
```sql
-- Test Case 1: Basic workflow
INSERT INTO mdl_alt42_artifacts (...) VALUES (...);
INSERT INTO mdl_alt42_links (...) VALUES (...);
SELECT * FROM mdl_alt42_links WHERE target_agent_id = 10;

-- Test Case 2: Multiple links
INSERT INTO mdl_alt42_links (link_id, ...) VALUES ('lnk_001', ...);
INSERT INTO mdl_alt42_links (link_id, ...) VALUES ('lnk_002', ...);  -- ✅ 성공

-- Test Case 3: CASCADE delete
DELETE FROM mdl_alt42_artifacts WHERE artifact_id = 'artf_001';
-- 확인: 관련 links 자동 삭제됨
```

**테스트 시나리오**:
- [ ] Agent 9 → Agent 10 링크 생성
- [ ] Agent 10 inbox에서 링크 확인
- [ ] 프롬프트 수정 후 재전송
- [ ] 같은 artifact, 다른 target 전송
- [ ] Artifact 삭제 시 CASCADE 확인

---

## 🔧 Phase 2: 기능 확장 (2-4주 후)

**목표**: 실사용 피드백 기반 핵심 기능 추가
**기간**: 2-4주
**우선순위**: 🟡 High

### 2.1 프롬프트 히스토리 (1주)

**문제**: 현재 MVP는 프롬프트를 덮어씀 → 히스토리 없음

**해결**:
```sql
CREATE TABLE mdl_alt42_link_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    link_id VARCHAR(50) NOT NULL,
    prompt_text TEXT,
    output_data MEDIUMTEXT,
    render_hint VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by VARCHAR(50) DEFAULT 'user',

    INDEX idx_link_id (link_id),
    INDEX idx_created_at (created_at DESC),

    FOREIGN KEY (link_id) REFERENCES mdl_alt42_links(link_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Link prompt/output history';
```

**변경 로직**:
```php
// 프롬프트 업데이트 시:
// 1. 현재 prompt_text, output_data를 history에 복사
INSERT INTO mdl_alt42_link_history (link_id, prompt_text, output_data, ...)
SELECT link_id, prompt_text, output_data, ... FROM mdl_alt42_links WHERE link_id = ?;

// 2. links 테이블 업데이트
UPDATE mdl_alt42_links SET prompt_text = ?, output_data = ? WHERE link_id = ?;
```

**UI 변경**:
- [ ] "히스토리 보기" 버튼 추가
- [ ] 히스토리 모달 (버전 목록 + 복원 기능)

### 2.2 Soft Delete (3일)

**문제**: 실수로 삭제 시 복구 불가

**해결**:
```sql
-- links 테이블만 soft delete 추가 (artifacts는 CASCADE 유지)
ALTER TABLE mdl_alt42_links
ADD COLUMN is_deleted BOOLEAN DEFAULT FALSE AFTER status,
ADD COLUMN deleted_at TIMESTAMP NULL AFTER is_deleted,
ADD INDEX idx_deleted (is_deleted, deleted_at);
```

**변경 로직**:
```php
// 삭제 시:
UPDATE mdl_alt42_links
SET is_deleted = TRUE, deleted_at = NOW()
WHERE link_id = ?;

// 조회 시 (모든 쿼리에 추가):
WHERE is_deleted = FALSE

// 복구:
UPDATE mdl_alt42_links
SET is_deleted = FALSE, deleted_at = NULL
WHERE link_id = ?;

// 주기적 정리 (90일 후 하드 삭제):
DELETE FROM mdl_alt42_links
WHERE is_deleted = TRUE
  AND deleted_at < DATE_SUB(NOW(), INTERVAL 90 DAY);
```

**UI 변경**:
- [ ] "삭제" → "휴지통으로 이동"
- [ ] 휴지통 보기 (복구/영구삭제)

### 2.3 외부 Blob 저장 (1주)

**문제**: 16MB 이상 데이터 저장 불가

**해결**:
```sql
ALTER TABLE mdl_alt42_artifacts
ADD COLUMN full_blob_ref VARCHAR(255) NULL AFTER full_data,
ADD COLUMN data_size_bytes INT NULL AFTER full_blob_ref,
ADD INDEX idx_blob_ref (full_blob_ref);
```

**저장 로직**:
```php
function saveArtifact($artifact_id, $data) {
    $data_json = json_encode($data);
    $size = strlen($data_json);

    if ($size > 10 * 1024 * 1024) {  // > 10MB
        // 외부 저장
        $blob_ref = uploadToS3($artifact_id, $data_json);
        $DB->insert_record('alt42_artifacts', [
            'artifact_id' => $artifact_id,
            'full_blob_ref' => $blob_ref,
            'data_size_bytes' => $size
        ]);
    } else {
        // DB 직접 저장
        $DB->insert_record('alt42_artifacts', [
            'artifact_id' => $artifact_id,
            'full_data' => $data_json,
            'data_size_bytes' => $size
        ]);
    }
}
```

**인프라 요구사항**:
- [ ] S3 버킷 생성 또는 filesystem 경로 설정
- [ ] 업로드/다운로드 API 구현
- [ ] 정리 작업 (30일 후 삭제)

### 2.4 성능 인덱스 추가 (3일)

**문제**: 실사용 데이터 증가 시 조회 속도 저하

**해결** (실제 쿼리 패턴 분석 후 추가):
```sql
-- 학생별 최신 링크 조회 최적화
ALTER TABLE mdl_alt42_links
ADD INDEX idx_student_source_created (student_id, source_agent_id, created_at DESC);

-- Task 범위 조회 최적화
ALTER TABLE mdl_alt42_links
ADD INDEX idx_task_student (task_id, student_id);

ALTER TABLE mdl_alt42_artifacts
ADD INDEX idx_task_student_agent (task_id, student_id, agent_id);
```

---

## ⚡ Phase 3: 최적화 (1-2개월 후)

**목표**: 성능 모니터링 및 병목 제거
**기간**: 1-2주
**우선순위**: 🟢 Medium

### 3.1 쿼리 성능 분석

```sql
-- Slow Query Log 활성화
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;  -- 1초 이상 쿼리 로그

-- 실행 계획 분석
EXPLAIN SELECT ... FROM mdl_alt42_links WHERE ...;

-- 인덱스 사용률 확인
SHOW INDEX FROM mdl_alt42_links;
```

**분석 체크리스트**:
- [ ] 1초 이상 쿼리 식별
- [ ] 인덱스 미사용 쿼리 식별
- [ ] Full table scan 쿼리 식별
- [ ] JOIN 최적화 필요 쿼리 식별

### 3.2 인덱스 튜닝

```sql
-- 사용하지 않는 인덱스 제거
ALTER TABLE mdl_alt42_links DROP INDEX unused_index_name;

-- 복합 인덱스 추가 (커버링 인덱스)
ALTER TABLE mdl_alt42_links
ADD INDEX idx_covering (student_id, target_agent_id, status, created_at, link_id);
```

### 3.3 캐싱 전략

```php
// Redis 캐싱 (inbox 조회)
function getInbox($agent_id, $student_id) {
    $cache_key = "inbox:{$agent_id}:{$student_id}";
    $cached = $REDIS->get($cache_key);

    if ($cached) {
        return json_decode($cached, true);
    }

    $inbox = $DB->get_records_sql("SELECT ... FROM mdl_alt42_links ...");
    $REDIS->setex($cache_key, 300, json_encode($inbox));  // 5분 캐시
    return $inbox;
}

// 캐시 무효화 (link 생성/수정 시)
$REDIS->del("inbox:{$target_agent_id}:{$student_id}");
```

---

## 🏗️ Phase 4: V2 Full 마이그레이션 (6개월+ 후)

**목표**: 프롬프트/출력 버전 관리 시스템 도입
**기간**: 2-3주
**우선순위**: 🔵 Low (선택적)

### 4.1 버전 관리 테이블 추가

```sql
-- prep_prompts 테이블
CREATE TABLE mdl_alt42_prep_prompts (
    ppv_id VARCHAR(50) NOT NULL UNIQUE,
    link_id VARCHAR(50) NOT NULL,
    prompt_text TEXT NOT NULL,
    preset_type ENUM('summary-ko', 'plan', 'dataset', 'command', 'custom'),
    replaces VARCHAR(50) NULL,  -- 주의: 순환 참조 검증 필요
    created_at TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES mdl_alt42_links(link_id) ON DELETE CASCADE
);

-- prep_outputs 테이블
CREATE TABLE mdl_alt42_prep_outputs (
    pov_id VARCHAR(50) NOT NULL UNIQUE,
    link_id VARCHAR(50) NOT NULL,
    ppv_id VARCHAR(50) NOT NULL,
    payload MEDIUMTEXT NOT NULL,
    render_hint VARCHAR(20),
    created_at TIMESTAMP,
    FOREIGN KEY (link_id) REFERENCES mdl_alt42_links(link_id) ON DELETE CASCADE,
    FOREIGN KEY (ppv_id) REFERENCES mdl_alt42_prep_prompts(ppv_id) ON DELETE CASCADE
);
```

### 4.2 Links 테이블 마이그레이션

```sql
-- 현재 버전 포인터 추가
ALTER TABLE mdl_alt42_links
ADD COLUMN current_ppv_id VARCHAR(50) NULL,
ADD COLUMN current_pov_id VARCHAR(50) NULL,
ADD INDEX idx_current_versions (current_ppv_id, current_pov_id);

-- 기존 데이터 마이그레이션
-- 1. 현재 prompt_text → prep_prompts 이동
INSERT INTO mdl_alt42_prep_prompts (ppv_id, link_id, prompt_text, created_at)
SELECT CONCAT('ppv_', link_id), link_id, prompt_text, created_at
FROM mdl_alt42_links WHERE prompt_text IS NOT NULL;

-- 2. 현재 output_data → prep_outputs 이동
INSERT INTO mdl_alt42_prep_outputs (pov_id, link_id, ppv_id, payload, created_at)
SELECT CONCAT('pov_', link_id), link_id, CONCAT('ppv_', link_id), output_data, created_at
FROM mdl_alt42_links WHERE output_data IS NOT NULL;

-- 3. current_ppv_id/pov_id 업데이트
UPDATE mdl_alt42_links SET
    current_ppv_id = CONCAT('ppv_', link_id),
    current_pov_id = CONCAT('pov_', link_id);

-- 4. 외래 키 추가
ALTER TABLE mdl_alt42_links
ADD FOREIGN KEY (current_ppv_id) REFERENCES mdl_alt42_prep_prompts(ppv_id) ON DELETE SET NULL,
ADD FOREIGN KEY (current_pov_id) REFERENCES mdl_alt42_prep_outputs(pov_id) ON DELETE SET NULL;

-- 5. 기존 필드 제거 (선택적)
-- ALTER TABLE mdl_alt42_links DROP COLUMN prompt_text, DROP COLUMN output_data;
```

### 4.3 순환 참조 검증 로직

```php
function checkCircularReplaces($ppv_id, $replaces, $max_depth = 10) {
    global $DB;
    $visited = [];
    $current = $replaces;
    $depth = 0;

    while ($current && $depth < $max_depth) {
        if ($current === $ppv_id || in_array($current, $visited)) {
            throw new Exception(
                "Circular reference detected: {$ppv_id} -> {$current} - " .
                "File: " . __FILE__ . ", Line: " . __LINE__
            );
        }

        $visited[] = $current;
        $parent = $DB->get_field('alt42_prep_prompts', 'replaces', ['ppv_id' => $current]);
        $current = $parent;
        $depth++;
    }

    if ($depth >= $max_depth) {
        throw new Exception("Max version depth ({$max_depth}) exceeded - File: " . __FILE__ . ", Line: " . __LINE__);
    }

    return true;
}

// 사용
if ($replaces) {
    checkCircularReplaces($new_ppv_id, $replaces);
}
```

---

## 📊 마일스톤 요약

| Phase | 기간 | 테이블 | FK | 인덱스 | 복잡도 | 기능 |
|-------|------|--------|-----|--------|--------|------|
| **MVP** | 현재 | 5 | 4 | 13 | ⭐⭐ | 기본 링크 |
| **Phase 2** | +2-4주 | 6 | 5 | 18 | ⭐⭐⭐ | 히스토리, Soft Delete, Blob |
| **Phase 3** | +1-2개월 | 6 | 5 | 22 | ⭐⭐⭐ | 최적화 |
| **Phase 4** | +6개월 | 8 | 12 | 43 | ⭐⭐⭐⭐⭐ | 버전 관리 (V2 Full) |

---

## 🎯 의사결정 기준

### Phase 2 진입 조건
- [ ] MVP 1개월 안정 운영
- [ ] 100+ links 생성 (실사용 데이터)
- [ ] 사용자 피드백 3건+ (히스토리 필요성)
- [ ] 삭제 실수 사례 1건+

### Phase 3 진입 조건
- [ ] Phase 2 기능 안정화
- [ ] 1000+ links (성능 테스트 가능)
- [ ] Slow query 10건+ 식별
- [ ] 사용자 불만 (느린 조회 속도)

### Phase 4 진입 조건
- [ ] 프롬프트 버전 관리 명확한 요구사항
- [ ] 복잡도 증가 수용 가능
- [ ] 순환 참조 검증 로직 검증 완료
- [ ] 6개월+ 안정 운영 경험

---

## 📋 최종 권장사항

### 현재 즉시 실행
1. ✅ MVP 스키마 생성 (`create_agent_links_tables_mvp.sql`)
2. ✅ 무결성 검증 (`test_integrity_mvp.sql`)
3. ✅ 기본 API 구현 (artifacts, links, inbox)
4. ✅ 기본 UI 구현 (agent 팝업, inbox 패널)
5. ✅ 통합 테스트 (3-5 시나리오)

### 단기 목표 (1-2주)
- MVP 안정성 검증
- 실사용 데이터 수집
- 사용자 피드백 청취

### 중기 목표 (2-4주)
- Phase 2 기능 선택적 추가 (필요시)
- 성능 모니터링 시작

### 장기 목표 (6개월+)
- Phase 3 최적화 (필요시)
- Phase 4 버전 관리 (선택적)

**핵심 원칙**: **필요할 때만 복잡도 추가, 안정성 최우선**

---

*문서 버전: 1.0*
*작성일: 2025-10-17*
*다음 단계: MVP 스키마 실행 및 검증*
