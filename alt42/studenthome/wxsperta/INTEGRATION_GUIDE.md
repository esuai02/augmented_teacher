# Holonic WXSPERTA 통합 가이드

## 개요
Holonic WXSPERTA는 기존 index1.php, index2.php, index3.php, index4.php, indexm.php 페이지의 채팅 시스템과 통합되어 자동으로 학습 패턴을 분석하고 AI 에이전트를 업데이트합니다.

## 시스템 구성

### 1. 핵심 컴포넌트
- **holonic_schema.sql**: Holonic 구조 데이터베이스 (무한 재귀 방지 포함)
- **event_bus.php**: 실시간 이벤트 처리 시스템
- **llm_orchestrator.php**: Holon-Loop 실행 (Perceive → Plan → Act → Learn)
- **approval_system.php**: 학생 승인 기반 업데이트
- **chat_bridge.php**: 기존 채팅 시스템과의 연동
- **holonic_integration.js**: 클라이언트 통합 스크립트

### 2. 페이지별 에이전트 매핑
- **index1.php** (개념학습) → 시간 수정체 (Agent #1)
- **index2.php** (문제풀이) → 타임라인 합성기 (Agent #2)
- **index3.php** (복습) → 동기 엔진 (Agent #5)
- **index4.php** (시험대비) → 일일 사령부 (Agent #7)
- **indexm.php** (종합) → 시간수정체 CEO (Agent #15)

## 설치 방법

### 1. 데이터베이스 설정
```bash
# Holonic 스키마 적용
mysql -u username -p database_name < /studenthome/wxsperta/holonic_schema.sql
```

### 2. 기존 페이지에 통합 스크립트 추가
각 index 페이지의 `</body>` 태그 직전에 다음 코드 추가:

```html
<!-- Holonic WXSPERTA Integration -->
<script src="/studenthome/wxsperta/holonic_integration.js"></script>
```

### 3. 크론 작업 설정
```bash
# 이벤트 처리 (매 분)
* * * * * php /path/to/wxsperta/event_bus.php process

# Holon Loop 실행 (매 시간)
0 * * * * php /path/to/wxsperta/llm_orchestrator.php all

# 만료된 승인 정리 (매일)
0 0 * * * php /path/to/wxsperta/approval_system.php cleanup
```

## 작동 원리

### 1. 채팅 메시지 인터셉트
- `holonic_integration.js`가 모든 채팅 메시지를 자동으로 감지
- AJAX 요청 및 WebSocket 메시지 인터셉트
- 중복 방지 메커니즘 내장

### 2. 실시간 분석 및 이벤트
```
학생 메시지 → Chat Bridge → Event Bus → LLM Orchestrator
                ↓                          ↓
           인사이트 추출              Holon Loop 실행
                ↓                          ↓
           감정/학습격차 감지         프로젝트 자동 생성
```

### 3. 학생 승인 프로세스
- AI가 제안한 변경사항은 자동으로 승인 요청 생성
- 우측 상단 알림 배지로 표시
- 승인/거부 시 즉시 적용

### 4. 무한 재귀 방지
- RecursionGuard 클래스가 호출 깊이 추적
- 최대 깊이 10레벨로 제한
- 순환 참조 감지 시 자동 중단

## 주요 기능

### 1. 자동 감정 분석
- "힘들어", "어려워" 등 키워드 감지
- 동기 엔진 자동 활성화
- 맞춤형 격려 메시지 제공

### 2. 학습 격차 감지
- "모르겠어", "이해 안 돼" 등 분석
- 보충 학습 프로젝트 자동 제안
- 단계별 학습 경로 생성

### 3. 프로젝트 자동 생성
- 목표/계획 관련 대화 시 자동 트리거
- 중복 방지 (Jaccard 유사도 80% 이상 차단)
- WXSPERTA 8층 구조 자동 설정

### 4. 실시간 승인 알림
- 페이지 새로고침 없이 실시간 업데이트
- 시각적 알림 배지 및 팝업
- 상세 변경사항 미리보기

## 모니터링

### 1. 이벤트 로그 확인
```sql
SELECT * FROM mdl_wxsperta_event_bus 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
ORDER BY created_at DESC;
```

### 2. 승인 요청 상태
```sql
SELECT ar.*, a.name as agent_name
FROM mdl_wxsperta_approval_requests ar
JOIN mdl_wxsperta_agents a ON ar.requested_by_agent_id = a.id
WHERE ar.status = 'pending';
```

### 3. 재귀 감지 로그
```sql
SELECT * FROM mdl_wxsperta_recursion_guard
WHERE resolved = 0
ORDER BY detected_at DESC;
```

## 문제 해결

### 1. 채팅 메시지가 감지되지 않음
- 콘솔에서 `holonicIntegration` 객체 확인
- 네트워크 탭에서 chat_bridge.php 요청 확인
- 채팅 엔드포인트가 `isChatRequest()`에 포함되어 있는지 확인

### 2. 승인 알림이 표시되지 않음
- approval_system.php 직접 접근하여 테스트
- 브라우저 콘솔에서 오류 확인
- 폴링 간격 조정 (기본 10초)

### 3. 무한 재귀 오류
- recursion_guard 테이블 확인
- max_depth 값 조정 (기본 10)
- 순환 참조 에이전트 관계 점검

## 커스터마이징

### 1. 에이전트 매핑 변경
`chat_bridge.php`의 `mapPageToAgent()` 함수 수정

### 2. 인사이트 키워드 추가
`chat_bridge.php`의 `extractInsights()` 함수에 키워드 추가

### 3. 승인 UI 스타일 변경
`holonic_integration.js`의 CSS 부분 수정

## 보안 고려사항

- 모든 API 엔드포인트는 Moodle 로그인 필수
- CSRF 토큰 검증 (옵션)
- XSS 방지를 위한 입력 검증
- SQL 인젝션 방지 (prepared statements 사용)

## 성능 최적화

- 이벤트 버스는 배치 처리 (10개씩)
- 승인 체크는 10초 간격 폴링
- 중복 메시지 1초 이내 차단
- 재귀 깊이 제한으로 무한 루프 방지

## 향후 개발 계획

- WebSocket 실시간 통신 추가
- 벡터 DB 연동 (Qdrant/Weaviate)
- 그룹 학습 협업 기능
- 학부모 리포트 자동 생성