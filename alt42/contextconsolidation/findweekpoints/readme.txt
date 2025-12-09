WrongAnswerMap

1. 프로젝트 이름

WrongAnswerMap

2. 주요 Tech Stack (언어·프레임워크·버전)

Back‑end: Python 3.12 + FastAPI 0.111

Front‑end: Next.js 15 / React 19 (TypeScript) + Reagraph & D3 (for graph viz)

Graph rendering: Reagraph 2.x (vis.js‑based) + visx for custom overlays

Database: PostgreSQL 16 (relational) & Neo4j 5 (graph)

Task queue: Celery 5 + Redis 7 (optional)

Infrastructure: Docker 24 + Docker Compose, GitHub Actions CI/CD

3. 주요 디렉터리 구조 (경로 → 용도)

/app → FastAPI 백엔드

/app/api → REST & GraphQL 엔드포인트

/app/services → 비즈니스 로직 (분석, 태깅, 추천)

/app/db → ORM, Neo4j 드라이버

/frontend → Next.js 프론트엔드

/frontend/components → 공통 컴포넌트

/frontend/features/map → 그래프 맵 UI

/scripts → ETL & 배치 스크립트

/tests → pytest / Playwright

/docs → 기술 문서

/infra → IaC (Docker, terraform 등)

4. 빌드 / 테스트 / 린트 / 배포 명령

make build          # docker compose build --parallel
make test           # pytest && pnpm test
make lint           # ruff + mypy + eslint + prettier
make deploy         # gh workflow dispatch deploy

5. 코드 컨벤션 (스타일 가이드·브랜치 전략 등)

Python: PEP 8 준수, ruff + black 포맷터

JS/TS: eslint + prettier

Commit: commitizen semantic commit

브랜치: trunk‑based (main + short‑lived feature branches)

6. 개발·운영 환경 설정

Docker Compose 단일 명령으로 로컬 스택 구동

환경 변수는 .env 와 .env.example 로 관리

GitHub Actions 에서 테스트/배포 자동화

7. 허용 스크립트 / 금지 스크립트

허용:

scripts/analyze_wrong_answers.py – 오답 분석 ETL

scripts/import_curriculum.py – 교과 구조/콘텐츠 링크 적재

금지:

scripts/drop_production_db.py – 프로덕션 DB 삭제

어떤 형태든 개인식별정보(PII) 를 원본으로 내보내는 스크립트

8. Do Not Touch (건드리면 안 되는 영역)

/infra/prod – 프로덕션 IaC

/migrations/history – 마이그레이션 이력

9. 용어·약어 글로서리

용어

정의

Concept Node

개념(교과 소주제) 정점

Wrong‑Answer Note

학생의 오답 기록

Topic Tag

문항에 부여된 소주제 라벨

Map View

그래프 시각화 UI

LTI

Learning Tools Interoperability

xAPI

Experience API(학습 활동 로그 표준)

10. 특별 주의사항 (토큰 한계, 성능, 보안 등)

그래프 노드 500개 이상 조회 시 동적 클러스터링 및 페이지네이션 필요

민감 데이터(PII) 는 SHA‑256 해시 또는 속성 제거 후 저장

오답 분석 파이프라인은 주간 배치(금 02:00 KST); SLA 30분 이내

시각화는 모바일에서 60 FPS 가 목표; canvas‑based 렌더링으로 fall‑back

신뢰도 지표와 추천 로직은 A/B 테스트 (Opt‑in) 후 배포

