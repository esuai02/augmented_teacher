# ExamFocus - 시험 대비 자동 학습 모드 전환 시스템

## 📋 개요

ExamFocus는 학생의 시험 일정을 자동으로 감지하여 D-30, D-7 시점에 최적화된 학습 모드를 추천하고 자동 전환하는 Moodle 로컬 플러그인입니다.

## 🎯 주요 기능

### 1. 자동 시험 감지
- `mdl_abessi_schedule` 테이블과 Alt42t DB의 `student_exam_settings` 연동
- 시험일 자동 계산 및 D-value 추적

### 2. 지능형 추천 엔진
- **D-30**: 오답 회독 모드 추천
- **D-7**: 개념 요약 + 대표 유형 집중 모드
- 학습량 기반 조건부 추천

### 3. 하드코딩 방지 설계
- 모든 임계값, 메시지, 모드를 `settings.php`에서 관리
- 관리자 UI를 통한 실시간 설정 변경

### 4. 크론 기반 자동화
- 매일 00:05 시험 일정 스캔
- 매일 09:00 학습 알림 전송

## 🛠️ 설치 방법

### 1. 파일 배치
```bash
# 플러그인 디렉토리로 이동
cd /path/to/moodle/local/

# examfocus 폴더 복사
cp -r /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/local/examfocus .

# 권한 설정
chmod -R 755 examfocus/
chown -R www-data:www-data examfocus/
```

### 2. Moodle 관리자 설치
1. 관리자로 로그인
2. 사이트 관리 → 알림
3. 플러그인 설치 진행
4. 데이터베이스 업그레이드 실행

### 3. 설정 구성
1. 사이트 관리 → 플러그인 → 로컬 플러그인 → ExamFocus
2. 임계값 설정:
   - D-30 임계값: 30일
   - D-7 임계값: 7일
3. 메시지 템플릿 커스터마이징
4. 추천 모드 선택

## 📂 디렉토리 구조

```
local/examfocus/
├── version.php              # 버전 정보
├── settings.php             # 관리자 설정 (하드코딩 방지)
├── db/
│   ├── install.xml          # DB 테이블 정의
│   ├── access.php           # 권한 설정
│   ├── tasks.php            # 크론 태스크
│   └── services.php         # 웹서비스 API
├── classes/
│   ├── service/
│   │   └── exam_focus_service.php  # 핵심 비즈니스 로직
│   ├── task/
│   │   └── scan_exams.php         # 크론 태스크 구현
│   └── external/               # 웹서비스 구현
├── amd/src/
│   └── examfocus.js         # JavaScript AMD 모듈
├── templates/
│   └── recommendation_banner.mustache  # UI 템플릿
├── lang/
│   ├── ko/                  # 한국어
│   └── en/                  # 영어
└── integration.php          # 통합 가이드

```

## 🔗 DB 연동

### 읽기 테이블
- `mdl_abessi_schedule`: 시험 일정
- `mdl_user`: 사용자 timezone
- `block_use_stats_totaltime`: 누적 학습 시간
- `student_exam_settings` (Alt42t): 시험 정보

### 쓰기 테이블
- `local_examfocus_events`: 추천 이벤트 기록
- `local_examfocus_user_prefs`: 사용자 설정
- `mdl_abessi_missionlog`: 활동 로깅

## 💻 통합 방법

### 1. 기존 페이지에 통합

```php
// PHP 부분
require_once($CFG->dirroot . '/local/examfocus/classes/service/exam_focus_service.php');
$service = new \local_examfocus\service\exam_focus_service();
$recommendation = $service->get_recommendation_for_user($USER->id);
```

```javascript
// JavaScript 부분
require(['local_examfocus/examfocus'], function(ExamFocus) {
    ExamFocus.init(userid, '#mount-point');
});
```

### 2. 웹서비스 API 사용

```javascript
// AJAX 호출
$.ajax({
    url: '/webservice/rest/server.php',
    data: {
        wstoken: token,
        wsfunction: 'local_examfocus_get_recommendation',
        userid: userid,
        moodlewsrestformat: 'json'
    }
});
```

## 🧪 테스트 방법

### 1. 시험 데이터 삽입
```sql
-- Alt42t DB
INSERT INTO student_exam_settings 
(user_id, math_exam_date, exam_status) 
VALUES 
(2, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'confirmed');
```

### 2. 크론 수동 실행
```bash
php admin/cli/cron.php --execute=\\local_examfocus\\task\\scan_exams
```

### 3. UI 확인
- 학생으로 로그인
- 학습 모드 선택 페이지 접속
- 추천 배너 표시 확인

## 📊 KPI 모니터링

### 주요 지표
- D-30 도달 학생 수
- 추천 수락률
- 모드 전환율
- 오답 회독 완료율

### SQL 쿼리
```sql
-- 추천 수락률
SELECT 
    COUNT(*) as total,
    SUM(accepted) as accepted,
    (SUM(accepted) / COUNT(*)) * 100 as acceptance_rate
FROM mdl_local_examfocus_events
WHERE timecreated > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 DAY));
```

## ⚠️ 주의사항

1. **Moodle 버전**: 4.0 이상 필요
2. **PHP 버전**: 7.4 이상 권장
3. **크론 설정**: 시스템 크론이 활성화되어 있어야 함
4. **Alt42t DB 연결**: PDO 연결 설정 필요

## 🔧 문제 해결

### 배너가 표시되지 않음
1. 브라우저 개발자 도구 콘솔 확인
2. `require(['local_examfocus/examfocus'])` 로드 확인
3. 세션 스토리지 쿨다운 확인

### 크론이 실행되지 않음
1. `mdl_task_scheduled` 테이블 확인
2. 크론 로그 확인: `/var/log/moodle/cron.log`
3. 수동 실행 테스트

### DB 연결 오류
1. Alt42t DB 접속 정보 확인
2. PDO 확장 설치 확인
3. 방화벽 설정 확인

## 📝 변경 이력

### v1.0.0 (2025-09-01)
- 초기 릴리즈
- D-30/D-7 자동 감지
- 하드코딩 방지 설계
- 크론 기반 자동화

## 📧 지원

문의사항은 MathKing 관리자에게 연락하세요.

---

**웹 URL**: https://mathking.kr/moodle/local/examfocus/
**라이센스**: GNU GPL v3 or later