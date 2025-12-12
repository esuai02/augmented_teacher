# 오류 수정 내역

## 발견된 오류

**오류 메시지**:
```
데이터 로드 실패: Error in agent.php line 48: error/invalidmysqlnativetype
(File: agents/agent01_onboarding/ui/agent.js, Line: 186)
```

## 원인 분석

### 1. agent.php의 테이블 조회 오류
**문제 코드** (agent.php:20):
```php
$profile = $DB->get_record('alt42_student_profiles', ['userid' => $studentid]);
```

**문제점**:
1. 필드명 오류: `userid` → `user_id` (실제 테이블 스키마와 불일치)
2. 테이블 존재 여부 검증 없음
3. 예외 처리 부족

### 2. DB 테이블 미생성
- `mdl_alt42o_onboarding_reports` 테이블이 생성되지 않았을 가능성
- `mdl_alt42_student_profiles` 테이블 존재 여부 불확실
- `mdl_abessi_mbtilog` 테이블 존재 여부 불확실

## 수정 사항

### 1. agent.php 완전 재작성

**수정 전** (취약한 코드):
```php
try {
    $student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);
    $profile = $DB->get_record('alt42_student_profiles', ['userid' => $studentid]);

    $response = [
        'mbti' => $profile ? $profile->mbti : 'INTJ',
        // ...
    ];
} catch (Exception $e) {
    $response = ['error' => $e->getMessage()];
}
```

**수정 후** (방어적 프로그래밍):
```php
try {
    // 1. 학생 기본 정보 (필수)
    $student = $DB->get_record('user', ['id' => $studentid], '*', MUST_EXIST);

    // 2. 프로필 정보 (선택, 테이블 존재 확인 포함)
    $profile = null;
    try {
        if ($DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
            $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $studentid]);
        }
    } catch (Exception $profileError) {
        error_log("Profile fetch error: " . $profileError->getMessage());
    }

    // 3. MBTI 정보 (선택, mdl_abessi_mbtilog에서 최신 레코드)
    $mbtiType = 'INTJ'; // default
    try {
        if ($DB->get_manager()->table_exists(new xmldb_table('abessi_mbtilog'))) {
            $mbtiLog = $DB->get_record_sql(
                "SELECT * FROM {abessi_mbtilog}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$studentid]
            );
            if ($mbtiLog && !empty($mbtiLog->mbti)) {
                $mbtiType = strtoupper($mbtiLog->mbti);
            }
        }
    } catch (Exception $mbtiError) {
        error_log("MBTI fetch error: " . $mbtiError->getMessage());
    }

    $response = [
        'success' => true,
        'data' => [
            'mbti' => $mbtiType,
            'profile_complete' => $profile ? true : false,
            // ...
        ]
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => 'Error in agent.php line ' . __LINE__ . ': ' . $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ];
}
```

**개선 사항**:
1. ✅ 테이블 존재 여부 확인: `table_exists()` 사용
2. ✅ 필드명 수정: `userid` → `user_id`
3. ✅ 중첩 try-catch: 개별 데이터 소스 실패 시에도 계속 진행
4. ✅ 오류 로깅: `error_log()`로 서버 로그에 기록
5. ✅ 기본값 제공: 테이블이 없어도 기본 MBTI 반환
6. ✅ 파일/라인 정보: 오류 위치 명확히 표시

### 2. fix_db.php 생성

**목적**: 데이터베이스 상태 진단 및 복구

**기능**:
1. 필수 테이블 존재 여부 확인
   - `mdl_alt42_student_profiles`
   - `mdl_abessi_mbtilog`
   - `mdl_alt42o_onboarding_reports`

2. `mdl_alt42o_onboarding_reports` 자동 생성
   - 10개 필드 정의
   - 3개 인덱스 생성
   - 기존 테이블 보호

3. 테이블 구조 검증
   - 필드 개수 확인
   - 필드 목록 출력

4. 데이터 통계
   - 리포트 개수 조회

**사용 방법**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/fix_db.php
```

**예상 출력**:
```json
{
    "check_student_profiles": {
        "table": "mdl_alt42_student_profiles",
        "exists": true,
        "note": "User provided this table structure earlier"
    },
    "check_mbti_log": {
        "table": "mdl_abessi_mbtilog",
        "exists": true,
        "note": "MBTI data source table"
    },
    "check_reports_table": {
        "table": "mdl_alt42o_onboarding_reports",
        "exists": false
    },
    "create_reports_table": {
        "action": "Creating mdl_alt42o_onboarding_reports table...",
        "success": true,
        "message": "Table created successfully"
    },
    "verify_structure": {
        "success": true,
        "field_count": 10,
        "fields": ["id", "userid", "report_type", "info_data", ...]
    },
    "summary": {
        "success": true,
        "tables_checked": 3,
        "tables_created": 1,
        "message": "Database verification complete"
    }
}
```

## 해결 절차

### Step 1: DB 테이블 확인 및 생성
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/fix_db.php
```

**결과 확인**:
- ✅ `summary.success: true` → 성공
- ❌ 오류 발생 시 → `error` 섹션 확인

### Step 2: 테스트 페이지 재시도
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/test_integration.php
```

**예상 동작**:
1. Agent 01 카드 클릭
2. 패널 슬라이드 인
3. "리포트 생성하기" 버튼 표시 (리포트 없을 경우)
4. 버튼 클릭 시 리포트 생성
5. MBTI 추가/변경 기능 작동

### Step 3: 브라우저 콘솔 확인
F12 → Console 탭

**성공 로그**:
```javascript
📦 panel.js loading...
🔧 OnboardingPanel IIFE starting...
✅ OnboardingPanel initialized successfully
=== Test: Opening panel ===
Panel opened successfully
```

**오류 발생 시**:
- 빨간 오류 메시지 확인
- Network 탭에서 agent.php 응답 확인

## 핵심 학습 포인트

### 1. 방어적 프로그래밍
```php
// ❌ 나쁜 예: 테이블이 없으면 치명적 오류
$profile = $DB->get_record('alt42_student_profiles', ['userid' => $studentid]);

// ✅ 좋은 예: 테이블 존재 확인 후 조회
if ($DB->get_manager()->table_exists(new xmldb_table('alt42_student_profiles'))) {
    $profile = $DB->get_record('alt42_student_profiles', ['user_id' => $studentid]);
}
```

### 2. 오류 격리
```php
// ❌ 나쁜 예: 하나의 데이터 소스 실패 시 전체 실패
try {
    $student = getStudent();
    $profile = getProfile();  // 실패 시 student도 못 얻음
} catch (Exception $e) {
    // 모든 데이터 손실
}

// ✅ 좋은 예: 개별 데이터 소스 독립적 처리
$student = getStudent();  // 필수

try {
    $profile = getProfile();  // 선택
} catch (Exception $e) {
    $profile = null;  // 실패해도 student는 사용 가능
}
```

### 3. 명확한 오류 메시지
```php
// ❌ 나쁜 예
catch (Exception $e) {
    echo $e->getMessage();  // "error/invalidmysqlnativetype" - 의미 불명
}

// ✅ 좋은 예
catch (Exception $e) {
    error_log("Profile fetch error: " . $e->getMessage() .
              " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    // 로그: "Profile fetch error: Table 'alt42_student_profiles' doesn't exist [File: agent.php, Line: 26]"
}
```

## 파일 변경 요약

### 수정된 파일
1. **agent.php** (Lines 15-78)
   - 테이블 존재 확인 로직 추가
   - 필드명 수정 (`userid` → `user_id`)
   - MBTI 조회를 mdl_abessi_mbtilog로 변경
   - 중첩 try-catch로 오류 격리
   - error_log() 추가

### 새로 생성된 파일
1. **fix_db.php** (NEW)
   - DB 진단 및 복구 스크립트
   - 테이블 생성 자동화
   - 구조 검증

2. **FIXES.md** (이 문서)
   - 오류 분석 및 해결 과정 문서화

## 검증 체크리스트

### 데이터베이스
- [ ] fix_db.php 실행하여 summary.success: true 확인
- [ ] mdl_alt42o_onboarding_reports 테이블 생성 확인
- [ ] 테이블 필드 개수 10개 확인

### 기능 테스트
- [ ] test_integration.php 접속
- [ ] Agent 01 카드 클릭 시 패널 열림
- [ ] "리포트 생성하기" 버튼 표시
- [ ] 리포트 생성 성공
- [ ] MBTI 입력 및 저장 성공
- [ ] 리포트 재생성 성공

### 콘솔 확인
- [ ] panel.js 로딩 로그 정상
- [ ] OnboardingPanel 초기화 성공
- [ ] agent.php 오류 없음
- [ ] Network 탭에서 200 OK 응답

## 추가 참고 자료

- [db_schema.md](db_schema.md) - 데이터베이스 구조
- [integration_guide.md](integration_guide.md) - 통합 가이드
- [mbti_integration.md](mbti_integration.md) - MBTI 기능 문서
- [debug_guide.md](debug_guide.md) - 디버깅 가이드
