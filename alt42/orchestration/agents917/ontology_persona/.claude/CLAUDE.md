# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 프로젝트 개요

**Mathking 자동개입 v1.0** - 온톨로지 기반 AI 튜터 의사결정 시스템

학생 개개인에게 최적화된 학습 개입을 자동으로 생성하는 시스템으로, 22개 에이전트가 온톨로지와 페르소나 DB를 활용하여 규칙 기반 + LLM 추론을 수행합니다.

## 주요 테스트 및 실행 명령어

### Playwright E2E 테스트

```bash
# 전체 테스트 실행
npm test

# UI 모드로 실행 (디버깅)
npm run test:ui

# 헤드풀 모드 (브라우저 창 보기)
npm run test:headed

# 특정 테스트만 실행
npm run test:chrome  # Chrome 버전
npm run test:public  # Public 버전

# 디버그 모드
npm run test:debug

# HTML 리포트 보기
npm run report
```

### Python 추론 엔진 테스트

```bash
# Phase 1 엔진 단위 테스트
cd examples
python test_phase1_engine.py

# 성능 벤치마크
python benchmark_phase1.py

# 일관성 검증
python 03_validate_consistency.py
```

### 웹 인터페이스 접근

**온톨로지 추론 실험실 v3** (Phase 1 완료):
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v3.php
```

**온톨로지 시각화 도구**:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/ontology_visualizer/ontology_visualizer.html
```

## 시스템 아키텍처

### 핵심 구조

```
ontology_brain/
├── agents/                    # 22개 에이전트 (1개 완료, 21개 계획)
│   ├── agent_04/             # ✅ 커리큘럼 에이전트 (완전 구현)
│   └── agent_*/              # 📁 나머지 (구조만 존재)
├── examples/                  # Python 추론 엔진
│   ├── inference_engine.py   # 핵심 추론 엔진
│   ├── ontology_loader.py    # 온톨로지 로더
│   └── 01_minimal_ontology.json  # Phase 1 온톨로지
├── tests/                     # E2E 테스트
│   └── e2e/                  # Playwright 테스트
├── inference_lab_v3.php      # 웹 인터페이스
└── docs/                     # 설계 문서
```

### 데이터 흐름

1. **Evidence 수집** → 학습 데이터 (진도, 정답률, 시간)
2. **Persona State** → 감정 상태, 집중도, 인지 부하
3. **규칙 평가** → 온톨로지 기반 트리거 조건 확인
4. **추론 실행** → 규칙 엔진 + LLM (필요시)
5. **리포트/지시문 생성** → 템플릿 렌더링
6. **우선순위 정렬** → 다중 규칙 매칭 시 우선순위 적용

## 개발 가이드

### 온톨로지 기반 추론 시스템

**핵심 원칙**:
- **규칙 엔진 우선**: 명확한 규칙이 있으면 규칙을 따름
- **LLM 보완**: 모호한 경우에만 LLM 판단
- **증거 기반**: 모든 결정은 실제 학습 데이터에 근거
- **동적 확장**: JSON 파일 편집만으로 규칙 추가/수정 가능

### Phase 1 완료 기능

✅ 온톨로지 기반 동적 추론 (하드코딩 제거)
✅ 5개 감정 지원 (Frustrated, Focused, Tired, Anxious, Happy)
✅ 10개 규칙, 우선순위 기반 다중 매칭
✅ 초고속 추론 (0.778ms E2E, 109만회/초)
✅ E2E 테스트 100% 통과 (24/24)

### 새 에이전트 추가 (Phase 2-4)

```bash
# 1. 폴더 생성
mkdir -p agents/agent_XX/{tasks,prompts,tests,logs}

# 2. 필수 파일 작성
# - config.yaml: 에이전트 설정
# - tasks/task_*.yaml: 태스크 정의
# - prompts/report_*.md: 리포트 템플릿
# - prompts/directive_*.md: 지시문 템플릿

# 3. registry.yaml 등록

# 4. 온톨로지 추가 (01_minimal_ontology.json)
```

**참고**: `agents/agent_04/` 폴더를 템플릿으로 활용

### 테스트 작성

```javascript
// tests/e2e/ontology_inference_web.test.js 참조
test('TC-01: 페이지 로드 및 섹션 표시', async ({ page }) => {
  await page.goto(BASE_URL);
  await expect(page.locator('#inference-section')).toBeVisible();
  // ...
});
```

## 환경 요구사항

- **PHP**: 7.1.9+
- **MySQL**: 5.7+
- **Python**: 3.10+ (추론 엔진)
- **Node.js**: 18+ (Playwright 테스트)
- **Moodle**: 3.7+ (서버 환경)

## Moodle 연동

**중요**: 이 프로젝트는 서버에서 실시간 실행되며, Moodle과 통합됩니다.

```php
// 모든 PHP 파일에 필수
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
```

## 문서 구조

- `docs/01-AGENTS_TASK_SPECIFICATION.md` - 22개 에이전트 명세
- `docs/04-ONTOLOGY_SYSTEM_DESIGN.md` - 온톨로지 설계
- `docs/05-REASONING_ENGINE_SPEC.md` - 추론 엔진 명세
- `docs/PHASE1_COMPLETION_REPORT.md` - Phase 1 완료 보고서
- `docs/TESTING_GUIDE.md` - E2E 테스트 가이드

## 로드맵

### v1.0 (Phase 1 완료 ✅)
- 온톨로지 기반 추론 시스템
- 5개 감정, 10개 규칙
- E2E 테스트 자동화

### v2.0 (계획)
- 22개 에이전트 완전 구현
- LMS 실제 기능 호출
- A/B 테스트 시스템

## 추가 참고사항

- **에러 메시지**: 모든 에러 출력 시 파일명과 라인 번호 포함
- **파일 크기 제한**: 20KB 또는 500줄 초과 시 리팩토링 검토
- **UI 개발**: PHP, JS, CSS, HTML만 사용 (React 금지)
- **테스트 우선**: 모든 변경사항은 E2E 테스트로 검증

## 문의

- **개발팀**: dev@mathking.kr
- **문서**: [README.md](README.md)
- **이슈 트래킹**: GitHub Issues
