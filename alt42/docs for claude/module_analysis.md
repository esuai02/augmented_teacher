# Humanlike Tutor 모듈별 상세 분석

## 1. humanlikefeedback 모듈 분석

### 주요 파일 및 기능
- **math_mentor.php**: React 기반 수학 멘토링 인터페이스 (8가지 인지 영역)
  - 인지편향, 메타인지, 성장마인드, 도파민밸런스
  - 공부과학, 마이페르소나, 마이홀론즈, 장기계획
- **math_mentorCGNTV.php/js/css**: CGNTV 연동 멘토링 시스템
- **math_mentorEXAM.php**: 시험 대비 특화 멘토링
- **math_mentorHORMONE.php**: 호르몬/감정 기반 학습 관리
- **math_mentorTOOL.php**: 학습 도구 모음
- **reflectusertype.php**: 사용자 유형 분석 및 반영
- **studywithme.php**: 함께 공부하기 기능

### 통합 방향
→ **Cognitive Service**로 통합
- 인지 훈련 관련 모든 기능을 하나의 서비스로 통합
- 공통 인터페이스와 데이터 모델 사용

## 2. teachingsupport 모듈 분석

### 주요 파일 및 기능
- **teachingagent.php**: AI 기반 교육 에이전트 (989줄의 복잡한 로직)
- **analyze_problem.php**: 문제 분석 기능
- **generate_narration.php**: 해설 생성
- **generate_tts.php**: 음성 변환
- **interaction_history.php**: 상호작용 기록 관리

### 문제점
- Google LearnLM과 OpenAI API를 각각 사용
- 컨텐츠 재활용 구조 미비
- 상황 분류 체계 불명확

### 통합 방향
→ **Content Service**로 통합
- 문제 생성, 해설, TTS를 통합 관리
- 컨텐츠 재활용을 위한 메타데이터 체계 구축

## 3. omniui 모듈 분석

### 주요 파일 및 기능
- **selectmode.php**: 학습 세계관 선택 시스템 (6가지 모드)
  - 커리큘럼 중심, 맞춤학습 중심, 시험대비 중심
  - 단기미션 중심, 자기성찰 중심, 자기주도 시나리오 중심
- **omniui.php**: 메인 통합 인터페이스
- **omniui_teacher.php**: 교사용 인터페이스
- **omniui_student.php**: 학생용 인터페이스

### DB 구조
- mdl_alt42_contextlog: 활동 컨텍스트 로깅
- mdl_alt42_activitylog: 상세 활동 기록

### 통합 방향
→ **Unified Interface Framework**의 기반으로 활용
- 학습 모드 선택을 전체 시스템의 진입점으로
- 역할별 인터페이스 분기 처리

## 4. mentoring 모듈 분석

### 주요 파일 및 기능
- **weekly letter.php**: 주간 편지 (여러 버전 존재)
- **buildingtrust.php**: 신뢰 구축 시스템 (여러 버전)
- **weekly curation.php**: 주간 큐레이션
- **schexamfeedback.php**: 시험 피드백
- **parental observations.php**: 학부모 관찰 기록

### 문제점
- 동일 기능의 여러 버전 파일 존재 (copy, copy 2 등)
- 버전 관리 부재

### 통합 방향
→ **Communication Service**로 통합
- 주간 리포트 시스템 통합
- 학부모 소통 채널 일원화

## 5. SRL (Self-Regulated Learning) 모듈 분석

### 주요 파일 및 기능
- **spacedrepetition.php/js/css**: 간격 반복 학습
- **spacedchallenges.php/js/css**: 도전 과제 시스템
- **justdoit.php/js/css**: 실행 중심 학습

### 특징
- 독립적인 JavaScript 기반 구현
- 서술평가 대비 특화

### 통합 방향
→ **Learning Service**의 하위 모듈로 통합
- 자기조절학습 전략을 전체 학습 시스템에 통합

## 6. consolidation 모듈 분석

### 주요 파일 및 기능
- **consolidation_home.php**: 복습 시스템 홈
- **exam_analysis.php**: 시험 분석
- **weekly_mathreview.php**: 주간 수학 복습
- **mathnote_analysis.html**: 수학 노트 분석

### 목표
- 독립 세션 환경 구축
- 단기 목표 기반 몰입 학습
- 기출문제 분석과 연동

### 통합 방향
→ **Analytics Service**와 **Learning Service**로 분할 통합
- 분석 기능은 Analytics Service로
- 복습 시스템은 Learning Service로

## 통합 우선순위

### Phase 1 (높은 우선순위)
1. **DB 통합**: 분산된 테이블 구조 통합
2. **인증 시스템**: 통합 로그인 및 권한 관리
3. **기본 API**: 핵심 기능 API 구축

### Phase 2 (중간 우선순위)
1. **humanlikefeedback + teachingsupport 통합**
2. **omniui 기반 통합 인터페이스 구축**
3. **중복 파일 정리 및 버전 관리**

### Phase 3 (낮은 우선순위)
1. **mentoring 모듈 정리 및 통합**
2. **SRL + consolidation 통합**
3. **기타 모듈 정리**

## 데이터 마이그레이션 계획

### 1단계: 데이터 매핑
```sql
-- 기존 테이블들을 새로운 통합 스키마로 매핑
-- mdl_alt42_contextlog → learning_activities
-- mdl_alt42_activitylog → learning_activities (병합)
```

### 2단계: 데이터 정규화
- 중복 데이터 제거
- 참조 무결성 확보
- 인덱스 최적화

### 3단계: 점진적 마이그레이션
- 읽기 전용 모드로 시작
- 듀얼 라이트 (기존 + 신규)
- 완전 이전

## 기술 스택 권장사항

### Backend
- **Framework**: Express.js 또는 FastAPI
- **Database**: MySQL (기존) + Redis (캐싱)
- **API**: RESTful + GraphQL (선택적)

### Frontend
- **Framework**: React (이미 사용 중)
- **State Management**: Redux 또는 Zustand
- **UI Library**: Tailwind CSS (이미 사용 중)

### DevOps
- **Container**: Docker
- **CI/CD**: GitHub Actions
- **Monitoring**: ELK Stack 또는 Prometheus

## 성과 측정 지표

1. **코드 품질**
   - 중복 코드 비율
   - 순환 복잡도
   - 테스트 커버리지

2. **성능 지표**
   - API 응답 시간
   - 페이지 로드 시간
   - 동시 사용자 처리량

3. **사용성 지표**
   - 기능 접근 클릭 수
   - 오류 발생률
   - 사용자 만족도 