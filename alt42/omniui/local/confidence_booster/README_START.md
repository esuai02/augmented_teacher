# 🚀 Confidence Booster 시작 가이드

## 📌 시스템 개요

**Confidence Booster**는 이현선 학생을 위한 맞춤형 학습 지원 시스템입니다.
- 🎯 대상: 고등학교 2학년, 미적분, 학습 수준 하
- 💪 목표: 성적보다 "자신감" 강화에 중점

## 🛠️ 설치 방법

### 1단계: 파일 업로드
```bash
# 프로젝트 디렉토리 확인
cd /mnt/c/Users/hnsn9/OneDrive/Desktop/alt42/omniui/

# 플러그인 디렉토리 구조 확인
ls -la local/confidence_booster/
```

### 2단계: 데이터베이스 설치

#### 방법 1: 웹 브라우저에서 설치 (권장)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster/db/install.php?key=mathking2024
```

테스트 데이터도 함께 설치하려면:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster/db/install.php?key=mathking2024&test=1
```

#### 방법 2: MySQL 직접 실행
```sql
-- MySQL 접속
mysql -h 58.180.27.46 -u moodle -p@MCtrigd7128 mathking

-- install.xml 내용을 SQL로 변환하여 실행
-- 또는 install.php 파일의 SQL 쿼리 부분을 복사하여 실행
```

### 3단계: 권한 설정
```bash
# 로그 디렉토리 생성 및 권한 설정
mkdir -p local/confidence_booster/logs
chmod 777 local/confidence_booster/logs

# 업로드 디렉토리 권한
chmod 777 local/confidence_booster/uploads
```

## 🔐 접속 정보

### 메인 대시보드
- **URL**: `https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/local/confidence_booster/`
- **로그인**: 기존 MathKing 계정 사용

### 관리자 기능
- 교사 계정으로 로그인 시 학생 관리 기능 활성화
- 학생별 진도 확인 및 피드백 제공 가능

## 📱 주요 기능

### 1. 개념 요약 카드 📝
- **위치**: 개념 학습 페이지 하단
- **사용법**: 
  1. 개념 학습 후 "+" 버튼 클릭
  2. 3줄로 핵심 요약 작성
  3. AI 피드백 확인

### 2. 오답 분류 시스템 📊
- **위치**: 문제 풀이 페이지
- **분류 카테고리**:
  - 개념 이해 부족
  - 계산 실수
  - 단순 실수
  - 응용력 부족

### 3. 주간 도전 퀘스트 🎯
- **시작**: 매주 월요일 자동 생성
- **난이도**: 학생 수준에 맞춰 자동 조정
- **보상**: 성공 시 배지 획득

### 4. 자신감 대시보드 💪
- **실시간 지표**:
  - 자신감 지수 (0-100)
  - 요약 작성 수
  - 오답 분류율
  - 도전 성공률

## 🔗 기존 시스템 통합 위치

### 1. 개념학습 (index1.php)
```php
// 페이지 하단에 추가
include('local/confidence_booster/widgets/summary_card.php');
```

### 2. 문제풀이/오답노트
```php
// 오답 저장 폼에 추가
include('local/confidence_booster/widgets/error_classifier.php');
```

### 3. 대시보드 (dashboard.php)
```php
// 상단 배너 영역에 추가
include('local/confidence_booster/widgets/challenge_banner.php');
```

### 4. 일정관리 (schedule.php)
```php
// 주간 일정에 자동 추가
$weekly_challenge = get_weekly_challenge($userid);
```

## 📊 데이터베이스 구조

### 새로 생성되는 테이블
- `mdl_confidence_notes` - 개념 요약
- `mdl_confidence_errors` - 오답 분류
- `mdl_confidence_challenges` - 도전 과제
- `mdl_confidence_metrics` - 일일 지표
- `mdl_confidence_feedback` - 교사 피드백

### 기존 테이블 활용
- `mdl_user` - 사용자 정보
- `mdl_user_info_data` - 역할 구분 (fieldid=22)
- `mdl_abessi_*` - 기존 학습 데이터

## 🐛 문제 해결

### DB 연결 오류
```php
// config.php 확인
define('MATHKING_DB_HOST', '58.180.27.46');
define('MATHKING_DB_USER', 'moodle');
define('MATHKING_DB_PASS', '@MCtrigd7128');
```

### 세션 오류
```php
// 세션 시작 확인
session_start();
$_SESSION['user_id'] = $userid;
```

### 권한 오류
```bash
# 파일 권한 확인
ls -la local/confidence_booster/
chmod -R 755 local/confidence_booster/
```

## 🎯 성공 지표 모니터링

### 일일 체크리스트
- [ ] 요약 작성 1개 이상
- [ ] 오답 분류 완료
- [ ] 자신감 지수 입력

### 주간 목표
- [ ] 요약 5개 이상 작성
- [ ] 오답 분류율 90% 달성
- [ ] 도전 퀘스트 완료

### 월간 리포트
- 자신감 지수 변화 그래프
- 오답 패턴 분석
- 성장 포인트 확인

## 📞 지원 및 문의

### 기술 지원
- 로그 파일 위치: `local/confidence_booster/logs/`
- 디버그 모드: `config.php`에서 `DEBUG_MODE = true`

### 개발자 정보
- 프로젝트: MathKing Confidence Booster
- 버전: 1.0.0
- 라이선스: GNU GPL v3

## ✅ 체크리스트

### 설치 완료 확인
- [ ] DB 테이블 생성 완료
- [ ] 로그인 테스트 성공
- [ ] 대시보드 정상 표시
- [ ] 요약 작성 테스트
- [ ] 오답 분류 테스트
- [ ] AI 피드백 작동 확인

### 통합 완료 확인
- [ ] 기존 로그인 시스템 연동
- [ ] 기존 DB 연결 정상
- [ ] 세션 공유 확인
- [ ] 권한 체크 작동

---

💡 **Tip**: 처음 실행 시 테스트 데이터로 기능을 확인해보세요!
🎉 **Success**: 모든 설정이 완료되면 학생들이 바로 사용할 수 있습니다!