# 프로젝트 폴더 및 파일 구조

## 디렉토리 구조
```
shiningstars/
├── api/                     # API 엔드포인트
│   ├── agent/              # AI 에이전트 관련 API
│   │   ├── chat.php        # 대화 처리
│   │   ├── feedback.php    # 피드백 생성
│   │   └── analyze.php     # 감정/심리 분석
│   ├── student/            # 학생 관련 API
│   │   ├── progress.php    # 진행도 조회/저장
│   │   ├── reflection.php  # 성찰 기록 관리
│   │   └── profile.php     # 프로필 관리
│   └── teacher/            # 교사 관련 API
│       ├── dashboard.php   # 대시보드 데이터
│       └── insights.php    # 인사이트 조회
│
├── assets/                 # 정적 리소스
│   ├── css/               # 스타일시트
│   │   ├── main.css       # 메인 스타일
│   │   ├── journey.css    # 여정 맵 스타일
│   │   └── responsive.css # 반응형 스타일
│   ├── js/                # 자바스크립트
│   │   ├── app.js         # 메인 애플리케이션
│   │   ├── journey.js     # 여정 맵 로직
│   │   ├── agent.js       # AI 에이전트 통신
│   │   └── utils.js       # 유틸리티 함수
│   └── images/            # 이미지 리소스
│       └── icons/         # 아이콘
│
├── classes/               # PHP 클래스
│   ├── Agent.php          # AI 에이전트 클래스
│   ├── Student.php        # 학생 모델
│   ├── Journey.php        # 여정 관리
│   ├── Database.php       # DB 연결 관리
│   └── OpenAI.php         # OpenAI API 래퍼
│
├── config/                # 설정 파일
│   ├── config.php         # 기본 설정
│   ├── database.php       # DB 설정
│   └── prompts.php        # AI 프롬프트 템플릿
│
├── includes/              # 공통 포함 파일
│   ├── auth.php           # 인증 확인
│   ├── functions.php      # 공통 함수
│   └── constants.php      # 상수 정의
│
├── templates/             # UI 템플릿
│   ├── header.php         # 헤더
│   ├── footer.php         # 푸터
│   ├── journey-map.php    # 여정 맵 템플릿
│   └── modals/            # 모달 템플릿
│       ├── reflection.php # 성찰 입력 모달
│       └── feedback.php   # 피드백 표시 모달
│
├── data/                  # 데이터 파일
│   ├── prompts/           # AI 프롬프트
│   │   ├── empathy.txt    # 공감 프롬프트
│   │   ├── insight.txt    # 통찰 프롬프트
│   │   └── encourage.txt  # 격려 프롬프트
│   └── questions/         # 성찰 질문
│       └── nodes.json     # 노드별 질문 데이터
│
├── logs/                  # 로그 파일
│   ├── error.log          # 에러 로그
│   ├── ai_usage.log       # AI API 사용 로그
│   └── access.log         # 접근 로그
│
├── sql/                   # 데이터베이스 스크립트
│   ├── schema.sql         # 테이블 생성
│   ├── indexes.sql        # 인덱스 생성
│   └── seed.sql           # 초기 데이터
│
├── tests/                 # 테스트 파일
│   ├── unit/              # 단위 테스트
│   └── integration/       # 통합 테스트
│
├── docs/                  # 문서
│   ├── API.md             # API 문서
│   ├── SETUP.md           # 설치 가이드
│   └── USAGE.md           # 사용 가이드
│
├── .env.example           # 환경 변수 예제
├── .gitignore            # Git 제외 파일
├── composer.json         # Composer 설정
├── index.php             # 메인 진입점
├── agent.php             # AI 에이전트 페이지
└── README.md             # 프로젝트 설명

```

## 주요 파일 설명

### 핵심 파일
- **index.php**: 학생용 메인 인터페이스
- **agent.php**: AI 에이전트 인터페이스
- **config/config.php**: 시스템 전체 설정

### API 구조
- RESTful 엔드포인트 설계
- JSON 응답 형식
- 에러 핸들링 표준화

### 클래스 구조
- **Agent.php**: OpenAI와 통신, 프롬프트 관리
- **Student.php**: 학생 데이터 및 진행도 관리
- **Journey.php**: 여정 맵 로직 및 상태 관리

### 보안 파일
- **.env**: 민감한 설정 정보 (API 키 등)
- **includes/auth.php**: 모든 페이지에서 인증 확인

## 명명 규칙
- **PHP 파일**: snake_case.php
- **클래스**: PascalCase.php
- **JavaScript**: camelCase.js
- **CSS**: kebab-case.css
- **API 엔드포인트**: /api/resource/action

## 파일 권한
```bash
# 실행 권한이 필요한 디렉토리
chmod 755 logs/
chmod 755 data/

# 쓰기 권한이 필요한 파일
chmod 644 logs/*.log

# 보안 파일
chmod 600 .env
chmod 600 config/config.php
```