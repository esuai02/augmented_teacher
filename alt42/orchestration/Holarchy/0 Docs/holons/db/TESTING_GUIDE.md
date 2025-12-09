# A/B Testing Database Integration - Testing Guide
## Phase 11.1 테스트 가이드

**Last Updated:** 2025-12-09
**Version:** 1.0

---

## 1. 테스트 URL 목록

### Dashboard (메인)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/ab_testing_dashboard.php
```

### DB 설치 (관리자 전용)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/db/db_install.php
```

### JSON API 엔드포인트
```
# 테이블 상태 확인
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/db/db_install.php?format=json&action=status

# 테이블 설치
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/db/db_install.php?format=json&action=install

# Dashboard Overview API
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/ab_testing_dashboard.php?format=json&action=overview
```

---

## 2. 테스트 체크리스트

### Step 1: DB 설치 전 확인
- [ ] Moodle 관리자로 로그인
- [ ] `db/db_install.php` 접속
- [ ] 테이블 상태가 "Missing"으로 표시되는지 확인
- [ ] "Install Tables" 버튼이 표시되는지 확인

### Step 2: 테이블 설치
- [ ] "Install Tables" 버튼 클릭
- [ ] 5개 테이블 모두 "OK" 상태로 변경 확인:
  - `mdl_quantum_ab_tests`
  - `mdl_quantum_ab_test_outcomes`
  - `mdl_quantum_ab_test_state_changes`
  - `mdl_quantum_ab_test_reports`
  - `mdl_quantum_ab_test_config`
- [ ] `default_config`가 "inserted" 또는 "exists"로 표시되는지 확인

### Step 3: Dashboard 확인 (DB 모드)
- [ ] `ab_testing_dashboard.php` 접속
- [ ] 헤더에 "Phase 11.1" 뱃지 확인
- [ ] 헤더에 "🗄️ DB Mode" 녹색 뱃지 표시 확인
- [ ] 푸터에 "Phase 11.1 | Database Integration Complete" 표시 확인
- [ ] 그래프와 통계 정보가 올바르게 표시되는지 확인

### Step 4: JSON API 테스트
- [ ] `?format=json&action=overview` 호출
- [ ] 응답에 `"data_source": "database"` 포함 확인
- [ ] `control_size`, `treatment_size`, `total_size` 값 확인

### Step 5: Simulation 모드 테스트 (선택)
- [ ] DB 테이블이 없는 환경에서 Dashboard 접속
- [ ] "🎲 Simulation" 주황색 뱃지 표시 확인
- [ ] "Install DB" 링크 표시 확인
- [ ] `?format=json&action=overview` 응답에 `"data_source": "simulation"` 확인

---

## 3. 예상 JSON 응답

### DB 설치 상태 (성공)
```json
{
    "success": true,
    "action": "install",
    "tables": {
        "mdl_quantum_ab_tests": {
            "action": "created",
            "success": true,
            "status": {
                "exists": true,
                "record_count": 0,
                "status": "ok"
            }
        }
        // ... 다른 테이블들
    },
    "timestamp": "2025-12-09 XX:XX:XX",
    "user_id": 2
}
```

### Dashboard Overview (DB 모드)
```json
{
    "test_id": "quantum_v1",
    "data_source": "database",
    "control_size": 0,
    "treatment_size": 0,
    "total_size": 0,
    "status": "active",
    "created_at": "2025-12-09 XX:XX:XX"
}
```

### Dashboard Overview (Simulation 모드)
```json
{
    "test_id": "quantum_v1",
    "data_source": "simulation",
    "control_size": 48,
    "treatment_size": 52,
    "total_size": 100,
    "status": "active",
    "created_at": "2025-12-09 XX:XX:XX"
}
```

---

## 4. 문제 해결

### 문제: 403 Forbidden
**원인:** 관리자 권한 없음
**해결:** Moodle 사이트 관리자로 로그인

### 문제: 테이블 생성 실패
**원인:** MySQL 권한 부족 또는 구문 오류
**해결:**
1. `db/db_schema.sql` 파일을 phpMyAdmin에서 직접 실행
2. MySQL 사용자 권한 확인 (CREATE TABLE 권한 필요)

### 문제: Dashboard가 항상 Simulation 모드
**원인:**
1. DB 테이블이 존재하지 않음
2. `mdl_quantum_ab_test_config` 테이블에 레코드 없음

**해결:**
1. `db/db_install.php`에서 테이블 설치
2. 테이블 상태 확인: `?format=json&action=status`

### 문제: JSON API 에러
**원인:** PHP 구문 오류
**해결:** PHP 에러 로그 확인 또는 HTML 모드로 접속하여 에러 메시지 확인

---

## 5. 데이터베이스 테이블 구조

| 테이블명 | 용도 | 주요 필드 |
|---------|------|-----------|
| `mdl_quantum_ab_tests` | 그룹 배정 | test_id, student_id, group_name |
| `mdl_quantum_ab_test_outcomes` | 학습 지표 | metric_name, metric_value |
| `mdl_quantum_ab_test_state_changes` | 8D 상태 변화 | dimension_name, before_value, after_value |
| `mdl_quantum_ab_test_reports` | 분석 리포트 캐시 | report_type, report_data |
| `mdl_quantum_ab_test_config` | 테스트 설정 | test_name, status, treatment_ratio |

---

## 6. 8D StateVector 차원

1. `cognitive_clarity` - 인지적 명확성
2. `emotional_stability` - 정서적 안정성
3. `attention_level` - 주의력 수준
4. `motivation_strength` - 동기 강도
5. `energy_level` - 에너지 수준
6. `social_connection` - 사회적 연결성
7. `creative_flow` - 창의적 흐름
8. `learning_momentum` - 학습 모멘텀

---

**Created by:** Phase 11.1 Database Integration
**File:** db/TESTING_GUIDE.md
