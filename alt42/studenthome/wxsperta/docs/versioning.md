# WXsperta 버전 관리 시스템 문서

## 개요

WXsperta 버전 관리 시스템은 21개 AI 에이전트 카드의 프로젝트 구조와 속성을 추적하고, 언제든지 이전 상태로 롤백할 수 있는 기능을 제공합니다.

## 시스템 아키텍처

### 데이터베이스 스키마

```
┌─────────────────────────┐     ┌──────────────────────────┐
│ wxsperta_projects_      │     │ wxsperta_projects_       │
│ current                 │     │ versions                 │
├─────────────────────────┤     ├──────────────────────────┤
│ id (1)                  │     │ version_id (PK)          │
│ project_json            │     │ created_at               │
│ last_updated            │     │ author_id                │
│ last_updated_by         │     │ commit_msg               │
└─────────────────────────┘     │ project_json             │
                                │ parent_version_id        │
                                │ is_milestone             │
                                └──────────────────────────┘
                                            │
┌─────────────────────────┐     ┌──────────────────────────┐
│ wxsperta_agent_texts_   │     │ wxsperta_agent_texts_    │
│ current                 │     │ versions                 │
├─────────────────────────┤     ├──────────────────────────┤
│ card_id (PK)            │     │ version_id (FK)          │
│ properties_json         │     │ card_id                  │
│ last_updated            │     │ properties_json          │
│ last_updated_by         │     └──────────────────────────┘
└─────────────────────────┘
```

### 주요 테이블 설명

1. **wxsperta_projects_current**: 현재 활성화된 프로젝트 구조 (단일 행)
2. **wxsperta_projects_versions**: 모든 버전의 스냅샷 저장
3. **wxsperta_agent_texts_current**: 각 에이전트 카드의 현재 8-layer 속성
4. **wxsperta_agent_texts_versions**: 버전별 에이전트 속성 히스토리
5. **wxsperta_version_diffs**: 버전 간 차이점 캐시
6. **wxsperta_rollback_history**: 롤백 작업 추적

## API 엔드포인트

### 1. 버전 커밋
```bash
POST /version_api.php
Content-Type: application/json

{
    "action": "commit",
    "commit_msg": "에이전트 속성 업데이트",
    "is_milestone": false
}
```

**응답:**
```json
{
    "success": true,
    "version_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "message": "버전이 성공적으로 커밋되었습니다."
}
```

### 2. 버전 목록 조회
```bash
GET /version_api.php?action=versions&limit=20&offset=0
```

**응답:**
```json
{
    "success": true,
    "versions": [
        {
            "version_id": "a1b2c3d4...",
            "created_at": "2024-01-01 10:00:00",
            "author_name": "홍길동",
            "commit_msg": "초기 설정",
            "is_milestone": true,
            "tags": []
        }
    ],
    "total": 100
}
```

### 3. 버전 롤백
```bash
POST /version_api.php
Content-Type: application/json

{
    "action": "rollback",
    "version_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "reason": "버그 수정을 위한 롤백"
}
```

**응답:**
```json
{
    "success": true,
    "rolled_back_to": "a1b2c3d4...",
    "backup_version_id": "f9e8d7c6...",
    "message": "성공적으로 롤백되었습니다."
}
```

### 4. 버전 비교 (Diff)
```bash
GET /version_api.php?action=diff&from=VERSION_A&to=VERSION_B
```

**응답:**
```json
{
    "success": true,
    "diff": {
        "added": { "key": "value" },
        "modified": { "key": { "old": "old_value", "new": "new_value" } },
        "deleted": { "key": "deleted_value" }
    },
    "summary": "3개 추가, 2개 수정, 1개 삭제",
    "cached": false
}
```

## 프론트엔드 사용법

### 버전 관리 UI 초기화
```javascript
// wxsperta.php에서 자동 초기화
window.versionControl = initVersionControl({
    apiUrl: 'version_api.php',
    userRole: 'teacher',  // 또는 'student'
    userId: 123
});
```

### UI 구성요소

1. **버전 아이콘**: 화면 좌측 하단의 시계 아이콘
2. **사이드 패널**: 아이콘 클릭 시 우측에서 슬라이드
3. **탭 구성**:
   - 타임라인: 버전 히스토리 보기
   - 변경사항: 두 버전 간 비교
   - 롤백: 이전 버전으로 되돌리기 (교사 전용)

## 워크플로우

### 1. 자동 커밋 시나리오
- 에이전트 속성 저장 시 (교사 모드)
- 프로젝트 구조 변경 시
- 마일스톤 달성 시

### 2. 수동 커밋 시나리오
- 버전 관리 패널의 "현재 상태 커밋" 버튼 클릭
- 커밋 메시지 입력
- 선택적으로 마일스톤 표시

### 3. 롤백 프로세스
1. 롤백할 버전 선택
2. 롤백 사유 입력
3. 확인 모달에서 최종 확인
4. 현재 상태 자동 백업 후 롤백 실행
5. 페이지 새로고침

## 보안 및 권한

- **학생**: 버전 히스토리 조회, Diff 비교 가능
- **교사**: 모든 기능 + 롤백 권한
- 모든 API 호출은 Moodle 인증 필요
- 롤백 시 현재 상태 자동 백업

## 성능 최적화

1. **Diff 캐싱**: 계산된 diff는 자동으로 캐시됨
2. **페이지네이션**: 버전 목록은 20개씩 로드
3. **지연 로딩**: 패널 열기 시 데이터 로드

## 테스트

테스트 페이지: `/test_version_control.html`

### 테스트 시나리오
1. API 연결 테스트
2. 연속 커밋 (버전 A, B, C)
3. 프로젝트 삭제 후 커밋 (버전 D)
4. 버전 B로 롤백
5. 버전 B와 D 간 Diff 확인

## 문제 해결

### 일반적인 문제

1. **"Permission denied" 오류**
   - 원인: 학생 계정으로 롤백 시도
   - 해결: 교사 계정으로 로그인

2. **버전이 표시되지 않음**
   - 원인: 데이터베이스 테이블 미생성
   - 해결: `version_control_schema.sql` 실행

3. **Diff가 비어있음**
   - 원인: 두 버전이 동일하거나 데이터 형식 오류
   - 해결: 다른 버전으로 비교 시도

## 추후 개선사항

1. **Delta 저장**: 전체 스냅샷 대신 변경사항만 저장
2. **브랜치 기능**: 실험적 변경을 위한 브랜치
3. **충돌 해결**: 동시 편집 시 충돌 관리
4. **시각화**: 버전 트리 그래프
5. **자동 정리**: 오래된 버전 압축/보관