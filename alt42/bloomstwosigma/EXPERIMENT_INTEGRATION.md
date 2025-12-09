# 실험 데이터베이스 통합 완료

## 개요
사용자의 요청에 따라 실험 설계와 실험 결과 기록을 위한 완전한 데이터베이스 시스템을 구축했습니다.

## 완료된 기능

### 1. 데이터베이스 스키마
- **10개의 실험 관련 테이블 생성** (mdl_alt42_ 접두사 사용)
- **Unix 타임스탬프 사용** (timecreated, timemodified)
- **외래키 관계 설정** 및 인덱스 최적화

### 2. 백엔드 모델
- **ExperimentModel.php**: 모든 실험 데이터 관리
- **자동 테이블 생성** 및 UPSERT 작업 지원
- **완전한 CRUD 기능** (생성, 조회, 업데이트, 삭제)

### 3. API 엔드포인트
- **database_api.php 확장**: 8개의 새로운 실험 관리 액션
- **RESTful API 설계**: 모든 실험 데이터 조작 지원
- **에러 핸들링** 및 JSON 응답 표준화

### 4. 프론트엔드 통합
- **experiment_api.js**: 실험 관리 JavaScript 라이브러리
- **ExperimentManager 클래스**: 실험 생성 및 관리 자동화
- **기존 UI와 완전 통합**: 데이터베이스 연결시 자동 실험 저장

## 주요 테이블 구조

### mdl_alt42_experiments (실험 기본 정보)
- 실험명, 설명, 시작일, 기간, 상태, 생성자 등

### mdl_alt42_intervention_methods (개입 방법)
- 메타인지적, 학습적, 복합적, 통제 방법 관리

### mdl_alt42_tracking_configs (추적 설정)
- 성과, 행동, 참여도, 피드백 추적 설정

### mdl_alt42_group_assignments (그룹 배정)
- 통제군/실험군 배정 및 개입 방법 연결

### mdl_alt42_database_connections (DB 연결)
- Mathking DB 테이블 연결 및 쿼리 조건 저장

### mdl_alt42_experiment_results (실험 결과)
- 설문, 분석, 관찰, 측정 결과 저장

### mdl_alt42_hypotheses (가설)
- 주요, 부차, 탐색적 가설 관리

### mdl_alt42_experiment_logs (실험 로그)
- 모든 실험 활동 추적 및 로깅

## 사용 방법

### 1. 실험 생성
```javascript
const experimentData = {
    name: '실험명',
    description: '실험 설명',
    startDate: '2024-01-01',
    durationWeeks: 8,
    status: 'planned',
    createdBy: 1
};

await experimentManager.createExperiment(experimentData);
```

### 2. DB 연결 추가
```javascript
await experimentManager.addDatabaseToExperiment('table_name', conditions);
```

### 3. 가설 저장
```javascript
await saveHypothesis(experimentId, '가설 텍스트', {
    type: 'primary',
    authorId: 1
});
```

### 4. 결과 기록
```javascript
await experimentManager.recordExperimentResult({
    type: 'analysis',
    title: '분석 제목',
    content: '분석 내용',
    data: { /* 분석 데이터 */ }
});
```

## 자동화 기능

### 1. 실험 설정 저장시
- 자동으로 mdl_alt42_experiments 테이블에 저장
- 실험 생성 로그 자동 기록

### 2. DB 테이블 선택시
- 자동으로 mdl_alt42_database_connections에 연결 정보 저장
- 필드 설명 자동 연동

### 3. 가설 추가시
- 자동으로 mdl_alt42_hypotheses 테이블에 저장
- 실험 활동 로그 자동 기록

### 4. 결과 입력시
- 자동으로 mdl_alt42_experiment_results 테이블에 저장
- 결과 기록 로그 자동 생성

## 데이터 무결성

### 1. 외래키 제약조건
- 실험 삭제시 관련 데이터 자동 삭제 (CASCADE)
- 개입 방법 삭제시 그룹 배정 NULL 처리

### 2. 유니크 제약조건
- 실험당 사용자별 그룹 배정 중복 방지
- 설문 응답 중복 방지

### 3. 인덱스 최적화
- 자주 조회되는 필드에 인덱스 설정
- 검색 성능 최적화

## 로깅 시스템

### 1. 실험 활동 로그
- 모든 실험 관련 활동 자동 기록
- 시작, 수정, 완료, 오류 상태 추적

### 2. 디버깅 로그
- PHP 에러 로그 자동 기록
- JavaScript 콘솔 로그 상세 출력

## 확장 가능성

### 1. 새로운 실험 유형 추가
- 테이블 구조 확장 용이
- API 엔드포인트 추가 간편

### 2. 다양한 데이터 소스 연결
- 추가 데이터베이스 연결 지원
- 외부 시스템 통합 가능

### 3. 고급 분석 기능
- 통계 분석 모듈 추가 가능
- 시각화 도구 통합 지원

## 보안 고려사항

### 1. 데이터 검증
- 모든 입력 데이터 검증
- SQL 인젝션 방지

### 2. 접근 권한 관리
- Moodle 인증 시스템 연동
- 역할별 접근 제어

### 3. 데이터 암호화
- 민감한 데이터 암호화 저장
- 안전한 데이터 전송

이제 실험 설계부터 결과 기록까지 완전한 데이터베이스 기반 시스템이 구축되었습니다.