# Humanlike Tutor 시스템 리팩토링 계획

## 1. 현황 분석

### 1.1 현재 구조의 문제점

#### 구조적 문제
- **모듈 분산**: 유사한 기능이 여러 디렉토리에 중복 구현
- **명명 일관성 부재**: humanlikefeedback, teachingsupport, mentoring 등 유사 기능의 불명확한 구분
- **의존성 불명확**: 모듈 간 상호 의존 관계가 문서화되지 않음
- **DB 구조 파편화**: 각 모듈이 독립적인 DB 테이블 사용

#### 기능적 문제
- **중복 구현**: 학습 기록, 피드백, 분석 기능이 여러 곳에 중복
- **통합 부재**: 학생/교사/학부모 인터페이스 간 데이터 공유 미흡
- **확장성 제한**: 새로운 기능 추가 시 어디에 구현해야 할지 불명확

### 1.2 현재 주요 모듈 분석

| 모듈명 | 주요 기능 | 파일 수 | 상태 |
|--------|-----------|---------|------|
| humanlikefeedback | 수학 멘토링, 인지훈련 | 11개 | 활성 |
| teachingsupport | 문제풀이 지원, TTS | 10개 | 활성 |
| omniui | 통합 UI, 학습모드 선택 | 5개 | 활성 |
| mentoring | 주간 리포트, 신뢰구축 | 12개 | 중복많음 |
| SRL | 자기조절학습 | 10개 | 독립적 |
| consolidation | 복습, 시험분석 | 6개 | 부분활성 |

## 2. 목표 아키텍처

### 2.1 설계 원칙
1. **단일 책임 원칙**: 각 모듈은 하나의 명확한 책임만 가짐
2. **DRY (Don't Repeat Yourself)**: 중복 코드 제거
3. **계층적 구조**: Core → Service → API → UI의 명확한 계층
4. **느슨한 결합**: 모듈 간 인터페이스를 통한 통신
5. **확장 가능성**: 새 기능 추가가 용이한 구조

### 2.2 제안하는 디렉토리 구조

```
humanlike_tutor/
├── core/                    # 핵심 기능
│   ├── database/           # DB 연결 및 모델
│   ├── auth/              # 인증 및 권한
│   └── config/            # 설정 관리
├── services/               # 비즈니스 로직
│   ├── learning/          # 학습 관리
│   ├── cognitive/         # 인지 훈련
│   ├── content/           # 컨텐츠 관리
│   ├── analytics/         # 분석 서비스
│   └── communication/     # 소통 서비스
├── api/                    # API 엔드포인트
│   ├── v1/               # API 버전 1
│   └── websocket/        # 실시간 통신
├── interfaces/             # 사용자 인터페이스
│   ├── student/          # 학생용
│   ├── teacher/          # 교사용
│   ├── parent/           # 학부모용
│   └── admin/            # 관리자용
├── shared/                 # 공통 리소스
│   ├── components/       # 재사용 컴포넌트
│   ├── assets/           # 정적 자원
│   └── utils/            # 유틸리티
└── docs/                   # 문서
```

## 3. 리팩토링 프로세스

### Phase 1: 준비 단계 (2주)
1. **현재 코드 분석 및 문서화**
   - 각 모듈의 기능 매핑
   - 데이터베이스 스키마 문서화
   - API 엔드포인트 목록 작성

2. **테스트 환경 구축**
   - 개발 서버 설정
   - 자동화 테스트 환경 구축
   - 백업 시스템 구성

### Phase 2: 데이터베이스 통합 (3주)
1. **통합 스키마 설계**
   ```sql
   -- 예시: 통합 학습 기록 테이블
   CREATE TABLE learning_activities (
       id INT PRIMARY KEY AUTO_INCREMENT,
       user_id INT NOT NULL,
       activity_type ENUM('lesson', 'quiz', 'review', 'feedback'),
       module_name VARCHAR(50),
       content_id INT,
       context JSON,
       result JSON,
       created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
       INDEX idx_user_activity (user_id, activity_type, created_at)
   );
   ```

2. **데이터 마이그레이션**
   - 기존 데이터 백업
   - 마이그레이션 스크립트 작성
   - 단계별 데이터 이전

### Phase 3: 서비스 레이어 구현 (4주)
1. **핵심 서비스 개발**
   - Learning Service: 학습 관리 통합
   - Cognitive Service: 인지 훈련 모듈 통합
   - Content Service: 컨텐츠 생성/관리 통합

2. **API 표준화**
   - RESTful API 설계
   - 인증/권한 미들웨어
   - 에러 처리 표준화

### Phase 4: UI 통합 (4주)
1. **공통 컴포넌트 개발**
   - 디자인 시스템 구축
   - 재사용 가능한 UI 컴포넌트

2. **인터페이스별 구현**
   - 학생 대시보드 재구성
   - 교사 관리 도구 통합
   - 학부모 리포트 시스템

### Phase 5: 마이그레이션 및 배포 (2주)
1. **점진적 마이그레이션**
   - 기능별 순차 이전
   - A/B 테스트 실시
   - 롤백 계획 수립

2. **모니터링 및 최적화**
   - 성능 모니터링
   - 사용자 피드백 수집
   - 지속적 개선

## 4. 주요 통합 작업

### 4.1 humanlikefeedback + teachingsupport → Cognitive Service
```javascript
// 통합 전
// humanlikefeedback/math_mentor.php
// teachingsupport/interaction_history.php

// 통합 후
// services/cognitive/index.js
class CognitiveService {
    async createMentorSession(userId, type) {
        // 통합된 멘토링 세션 생성
    }
    
    async recordInteraction(sessionId, data) {
        // 통합된 상호작용 기록
    }
}
```

### 4.2 omniui → Unified Interface Framework
- 학습 모드 선택 시스템을 전체 인터페이스의 진입점으로 활용
- 각 사용자 유형별 맞춤 UI 제공

### 4.3 consolidation + SRL → Learning Service
- 복습 시스템과 자기조절학습을 통합
- 통일된 학습 추적 시스템 구축

## 5. 기대 효과

1. **개발 효율성 향상**
   - 코드 중복 50% 감소
   - 새 기능 개발 시간 30% 단축

2. **유지보수성 개선**
   - 명확한 모듈 구조로 디버깅 용이
   - 문서화된 API로 협업 개선

3. **확장성 확보**
   - 새로운 학습 방법론 쉽게 추가
   - 다양한 클라이언트 지원 가능

4. **사용자 경험 향상**
   - 일관된 UI/UX
   - 빠른 응답 속도
   - 통합된 학습 경험

## 6. 리스크 관리

| 리스크 | 영향도 | 대응 방안 |
|--------|--------|-----------|
| 데이터 손실 | 높음 | 다중 백업, 롤백 계획 |
| 서비스 중단 | 높음 | 점진적 마이그레이션, 병렬 운영 |
| 기능 누락 | 중간 | 체크리스트 기반 검증 |
| 성능 저하 | 중간 | 부하 테스트, 최적화 |

## 7. 성공 지표

- 코드 라인 수 30% 감소
- API 응답 시간 50% 개선
- 버그 발생률 40% 감소
- 개발자 만족도 향상
- 사용자 만족도 20% 상승

## 8. 다음 단계

1. 이 문서를 팀과 공유하고 피드백 수집
2. 상세 기술 스펙 작성
3. 프로토타입 개발
4. 단계별 실행 계획 수립 