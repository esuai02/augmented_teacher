# 이현선 학생 맞춤형 학습 지원 시스템 (Confidence Booster)

## 📋 프로젝트 개요

### 대상
- **이름**: 이현선
- **학년**: 고등학교 2학년  
- **과목**: 미적분
- **현재 수준**: 하

### 학습 특성 분석
- ✅ **강점**
  - 중간에 멈추고 이해한 내용을 곱씹으며 정리하는 적극적 태도
  - 틀린 문제는 원인 분석까지 수행하는 성실함
  - 실전 시험처럼 시간 제한을 두고 가설-해석-검산까지 하는 훈련 루틴
  - 3번 연속 합격 기준의 체계적 반복 학습
  
- ⚠️ **보완점**
  - 개념 정독보다 눈에 띄는 부분만 표시하는 습관
  - 요약문 작성보다 표시 위주의 루틴
  - 개념 정리 단계에서 직접 요약/정리하는 루틴 부족

## 🎯 목표 지표 (KPI)

### 학생 측정 지표
1. **개념 요약 작성률**
   - 일일 최소 1개 개념 요약 작성
   - 주간 요약 작성 횟수: 5개 이상 목표
   - 측정: `mdl_confidence_notes` 테이블 카운트

2. **오답 분류 완성도**
   - 오답 분류율: 90% 이상 (전체 오답 중 분류된 비율)
   - 분류 카테고리: 개념/계산/실수/응용
   - 측정: `mdl_confidence_errors` 테이블 type 필드

3. **도전레벨 참여율**
   - 주간 도전 퀘스트 시도율: 100%
   - 성공률 목표: 초기 30% → 3개월 후 60%
   - 측정: `mdl_confidence_challenges` 테이블

### 교사 모니터링 지표
- 개념 이해도 향상 추이
- 오답 패턴 분석 (가장 빈번한 오류 유형)
- 자신감 지수 변화 (자가 평가 + 성취도)

## 👤 사용자 흐름

### 학생 시나리오

#### A. 개념 학습 플로우
```
1. 기존: 개념 페이지 → 눈에 띄는 부분 표시 → 다음 진행
2. 개선: 개념 페이지 → 표시 → [새기능] 요약 카드 팝업 
   → "오늘 배운 핵심을 3줄로 정리해보세요" 
   → 저장 → AI 피드백 제공
```

#### B. 오답 정리 플로우
```
1. 기존: 문제 풀이 → 오답 확인 → 오답노트 작성
2. 개선: 문제 풀이 → 오답 확인 → [새기능] 오답 분류 선택
   → (개념/계산/실수/응용) 태깅 
   → 원인 메모 추가 → 저장
```

#### C. 도전 퀘스트 플로우
```
1. 매주 월요일: 대시보드에 "이번 주 도전 문제" 배너 표시
2. 클릭 → 상위 난이도 문제 3개 세트 제공
3. 풀이 제출 → 즉시 채점 → 성취 배지 획득
4. 실패 시 → 힌트 제공 → 재도전 기회
```

### 교사 시나리오

#### A. 학생 진도 확인
```
1. 교사 대시보드 접속
2. 학생별 카드 뷰 → 이현선 클릭
3. 주간 리포트 확인:
   - 요약 작성 횟수 그래프
   - 오답 분류 파이차트  
   - 도전 퀘스트 성공률 트렌드
```

#### B. 맞춤 피드백 제공
```
1. 학생 요약 내용 리스트 조회
2. 부족한 부분 코멘트 작성
3. 추가 학습 자료 링크 첨부
```

## 💾 데이터베이스 설계

### 기존 테이블 참조
- `mdl_user` - 사용자 정보
- `mdl_abessi_attendance_record` - 출결 기록
- `mdl_abessi_schedule` - 일정 관리
- `mdl_abessi_missionlog` - 미션/활동 로그

### 신규 테이블

#### mdl_confidence_notes (개념 요약 저장)
```sql
CREATE TABLE mdl_confidence_notes (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    courseid BIGINT(10) DEFAULT 0,
    chapter VARCHAR(255),
    concept_title VARCHAR(500),
    summary_text TEXT,
    ai_feedback TEXT DEFAULT NULL,
    quality_score DECIMAL(3,2) DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    timemodified BIGINT(10) DEFAULT 0,
    PRIMARY KEY (id),
    KEY userid_idx (userid),
    KEY timecreated_idx (timecreated)
);
```

#### mdl_confidence_errors (오답 분류 로그)
```sql
CREATE TABLE mdl_confidence_errors (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    questionid BIGINT(10) NOT NULL,
    error_type VARCHAR(50) NOT NULL, -- concept/calculation/mistake/application
    error_memo TEXT,
    difficulty_level VARCHAR(20), -- easy/medium/hard
    resolved TINYINT(1) DEFAULT 0,
    retry_count INT(5) DEFAULT 0,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    KEY userid_type_idx (userid, error_type),
    KEY resolved_idx (resolved)
);
```

#### mdl_confidence_challenges (도전 퀘스트)
```sql
CREATE TABLE mdl_confidence_challenges (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    week_number INT(3) NOT NULL,
    challenge_level VARCHAR(20), -- medium/hard/extreme
    questions_json TEXT, -- 문제 ID 리스트
    attempted TINYINT(1) DEFAULT 0,
    success_rate DECIMAL(5,2) DEFAULT NULL,
    completion_time INT(10) DEFAULT NULL,
    badge_earned VARCHAR(50) DEFAULT NULL,
    timecreated BIGINT(10) NOT NULL,
    timecompleted BIGINT(10) DEFAULT 0,
    PRIMARY KEY (id),
    KEY userid_week_idx (userid, week_number)
);
```

#### mdl_confidence_metrics (자신감 지수)
```sql
CREATE TABLE mdl_confidence_metrics (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    metric_date DATE NOT NULL,
    self_confidence INT(3), -- 0-100
    actual_performance DECIMAL(5,2),
    summary_count INT(5) DEFAULT 0,
    error_classified_count INT(5) DEFAULT 0,
    challenge_attempted TINYINT(1) DEFAULT 0,
    timecreated BIGINT(10) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY userid_date_idx (userid, metric_date)
);
```

## 📁 파일 구조

```
local/confidence_booster/
├── version.php                    # 플러그인 버전 정보
├── lang/
│   ├── en/
│   │   └── local_confidence_booster.php
│   └── ko/
│       └── local_confidence_booster.php
├── db/
│   ├── install.xml                # DB 스키마 정의
│   ├── upgrade.php                # 업그레이드 스크립트
│   └── access.php                 # 권한 설정
├── classes/
│   ├── service/
│   │   ├── confidence_service.php # 핵심 비즈니스 로직
│   │   └── ai_feedback_service.php # AI 피드백 처리
│   ├── task/
│   │   └── generate_weekly_challenges.php # 크론 태스크
│   └── external/
│       └── confidence_api.php     # 웹서비스 API
├── amd/
│   ├── src/
│   │   ├── confidence.js         # 메인 JS 모듈
│   │   ├── summary_card.js       # 요약 카드 컴포넌트
│   │   └── challenge_widget.js   # 도전 위젯
│   └── build/                    # 빌드된 JS 파일
├── templates/
│   ├── confidence_dashboard.mustache
│   ├── summary_card.mustache
│   ├── error_classifier.mustache
│   └── challenge_banner.mustache
├── styles/
│   └── confidence.css            # 커스텀 스타일
└── tests/
    ├── phpunit/
    └── behat/
```

## 🔌 기존 시스템 통합 포인트

### 1. 개념학습 페이지 (index1.php)
- Hook: 개념 표시 후 요약 카드 자동 팝업
- 위치: 페이지 하단 또는 모달

### 2. 오답노트 페이지
- Hook: 오답 저장 시 분류 선택 UI 추가
- 위치: 기존 오답 입력 폼 확장

### 3. 대시보드 (dashboard.php)
- Hook: 상단에 도전 퀘스트 배너
- 위치: 기존 알림 영역 아래

### 4. 일정 관리 (schedule.php)
- Hook: 주간 도전 퀘스트 일정 자동 추가
- 위치: 매주 월요일 고정 슬롯

## ⚡ 성능 고려사항

- 요약 텍스트 최대 길이: 500자
- AI 피드백 비동기 처리 (큐 시스템 활용)
- 대시보드 데이터 캐싱 (5분)
- 도전 문제 풀에서 미리 생성 (일요일 자정)

## 🔒 보안 고려사항

- XSS 방지: 모든 사용자 입력 이스케이프
- CSRF 토큰 검증
- 권한 체크: 학생은 본인 데이터만 접근
- SQL 인젝션 방지: Prepared Statement 사용

## 📊 성공 지표

### 3개월 후 목표
1. 개념 요약 습관 형성: 주 5회 이상 작성 유지
2. 오답 분류율 90% 달성
3. 도전 퀘스트 성공률 60% 도달
4. 학습 자신감 지수 30% 상승

### 측정 방법
- 주간 리포트 자동 생성
- 월간 성장 그래프 제공
- 분기별 종합 평가 리포트