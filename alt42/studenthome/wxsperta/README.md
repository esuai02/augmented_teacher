# WXsperta - AI 에이전트 매트릭스 시스템

## 개요
WXsperta는 21개의 AI 에이전트를 활용한 학생 맞춤형 학습 지원 시스템입니다. 각 에이전트는 8층 구조(세계관→문맥→구조→절차→실행→성찰→전파→추상화)로 설계되어 있으며, 학생과의 실시간 대화를 통해 성장을 지원합니다.

## 시스템 구성
- **21개 AI 에이전트**: 미래설계, 실행, 브랜딩, 지식관리 4개 카테고리
- **실시간 대화 시스템**: OpenAI API 기반 맞춤형 응답
- **일일 미션 시스템**: 게이미피케이션 요소
- **우선순위 자동 조정**: 학생 활동 기반 에이전트 추천

## 설치 방법

### 1. 디렉토리 생성
```bash
# 브라우저에서 실행 (권한이 있는 경우)
http://your-domain/studenthome/wxsperta/setup_directories.php

# 또는 SSH로 직접 생성
cd /path/to/studenthome/wxsperta
mkdir -p logs cache
chmod 755 logs cache
```

### 2. 데이터베이스 설정
```bash
# 브라우저에서 실행
http://your-domain/studenthome/wxsperta/setup_database.php
```

### 3. API 키 설정
`config.php` 파일에서 OpenAI API 키를 설정하세요:
```php
define('OPENAI_API_KEY', 'your-actual-api-key-here');
```

### 4. JSON 필드 수정 (필요시)
Moodle이 JSON 타입을 지원하지 않는 경우:
```sql
-- 데이터베이스에서 직접 실행
ALTER TABLE mdl_wxsperta_events MODIFY COLUMN event_data TEXT, MODIFY COLUMN triggered_agents TEXT;
ALTER TABLE mdl_wxsperta_user_profiles MODIFY COLUMN interests TEXT, MODIFY COLUMN goals TEXT;
ALTER TABLE mdl_wxsperta_achievements MODIFY COLUMN achievement_data TEXT;
```

## 사용 방법

### 학생 모드
1. `wxsperta.php`에 접속
2. 원하는 에이전트 클릭하여 대화 시작
3. 일일 미션 수행 및 포인트 획득

### 교사 모드
1. `wxsperta.php?role=teacher`로 접속
2. 에이전트 클릭하여 속성 편집
3. 학생별 진행 상황 모니터링

## 주요 기능

### 1. 에이전트 대화
- 각 에이전트의 고유한 페르소나로 대화
- 맞춤형 질문 및 피드백 제공
- 대화 내역 자동 저장

### 2. 미션 시스템
- 매일 3개의 맞춤형 미션 생성
- 진행률 실시간 추적
- 완료 시 포인트 및 배지 획득

### 3. 우선순위 시스템
- 학생 활동 패턴 분석
- 이벤트 기반 우선순위 자동 조정
- 맞춤형 에이전트 추천

## API 엔드포인트

### `api.php`
- `save_agent_properties`: 에이전트 속성 저장
- `get_agent_questions`: 에이전트 질문 조회
- `save_interaction`: 대화 내역 저장
- `get_daily_mission`: 일일 미션 조회
- `update_agent_priority`: 우선순위 업데이트
- `trigger_event`: 이벤트 트리거

## 파일 구조
```
wxsperta/
├── wxsperta.php          # 메인 인터페이스
├── config.php            # 설정 파일
├── api.php               # 백엔드 API
├── chat.js               # 대화 시스템
├── generate_response.php # AI 응답 생성
├── setup_database.php    # DB 설정
├── create_tables.sql     # 테이블 스키마
├── logs/                 # 로그 디렉토리
└── cache/                # 캐시 디렉토리
```

## 문제 해결

### OpenAI API 오류
1. API 키가 올바른지 확인
2. API 사용량 한도 확인
3. 네트워크 연결 확인

### 데이터베이스 오류
1. Moodle 데이터베이스 연결 확인
2. 테이블이 올바르게 생성되었는지 확인
3. 사용자 권한 확인

### 대화가 표시되지 않음
1. JavaScript 콘솔에서 오류 확인
2. `chat.js` 파일이 올바르게 로드되는지 확인
3. React 라이브러리가 정상 로드되는지 확인

## 향후 개발 계획
- [ ] 음성 대화 기능 추가
- [ ] 학습 분석 대시보드
- [ ] 부모님 리포트 기능
- [ ] 그룹 챌린지 시스템
- [ ] AI 에이전트 간 협업 기능

## 라이센스
이 프로젝트는 교육 목적으로 개발되었습니다.