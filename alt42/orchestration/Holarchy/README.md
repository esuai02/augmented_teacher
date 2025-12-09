# Holarchy - 홀론 기반 문서 아키텍처 시스템

> **버전**: 2.0
> **상태**: Self-Healing Mode
> **최종 업데이트**: 2025-12-07

## 📋 프로젝트 개요

Holarchy는 **WXSPERTA 프레임워크**를 기반으로 한 홀론(Holon) 구조의 문서 관리 시스템입니다.
21개의 AI 에이전트가 유기적으로 협력하여 교육 혁신 플랫폼(UNITAS)의 문서화와 개발을 지원합니다.

### 핵심 개념

- **홀론(Holon)**: 자기유사성을 가진 계층적 문서 구조 단위
- **WXSPERTA**: 8가지 요소로 구성된 문서 표준 프레임워크
  - **W**orldview (세계관) - 왜 존재하는가
  - **X**-Context (맥락) - 어디에 위치하는가
  - **S**tructure (구조) - 무엇으로 구성되는가
  - **P**rocedure (절차) - 어떻게 작동하는가
  - **E**xecution (실행) - 무엇을 수행하는가
  - **R**eflection (회고) - 무엇을 배웠는가
  - **T**raffic (트래픽) - 어떻게 연결되는가
  - **A**bstraction (추상화) - 핵심이 무엇인가

## 📁 폴더 구조

```
Holarchy/
├── 0 Docs/           # 문서, 회의록, 리포트, 홀론 정의
│   ├── 00-holarchy-overview.md  # 루트 홀론 (시작점)
│   ├── 회의록/       # 프로젝트 회의 기록
│   └── 리포트/       # 분석 및 진행 리포트
├── 1 Astral/         # UI 컴포넌트, 에이전트 구현체
│   ├── 정보체/       # Information Agent
│   ├── 지식체/       # Knowledge Agent
│   ├── 실행체/       # Execution Agent
│   └── .holon/       # 홀론 메타데이터 및 규칙
├── 2 Neural/         # 신경망 시스템
│   ├── api/          # 신경망 API
│   ├── indexing/     # 좌표 기반 인덱싱
│   └── activation/   # 컨텍스트 활성화 시스템
├── meta.yaml         # SSOT (Single Source of Truth)
├── AGENT.md          # DevAI 에이전트 가이드
├── CONTEXT.md        # 자동 생성된 프로젝트 컨텍스트
└── README.md         # 이 파일
```

## 🚀 시작하기

### 1. 문서 탐색

루트 홀론에서 시작하세요:
```
0 Docs/00-holarchy-overview.md
```

### 2. 에이전트 규칙 확인

에이전트 의사결정 규칙:
```
1 Astral/.holon/rules.yaml
```

### 3. 시스템 설정

메인 설정 파일:
```
meta.yaml
```

## ⚙️ Self-Healing 모드

Holarchy는 **자가 치유 모드**로 운영됩니다:

- 문서 검증 실패 시 에러 대신 `issues.json`에 기록
- 플레이스홀더(`[placeholder]`)는 경고로 처리
- 시스템 중단 없이 지속적인 개선 가능

## 🔗 관련 링크

- **서버 URL**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/
- **상위 프로젝트**: augmented_teacher/alt42

## 📊 현재 상태

| 지표 | 상태 |
|------|------|
| 총 파일 | 211개 |
| 총 라인 | 71,339줄 |
| 주요 언어 | Markdown, Python, JSON |
| 테스트 커버리지 | 개선 필요 |

---

*이 프로젝트는 하이페리얼 테크놀로지스의 UNITAS 교육 혁신 플랫폼의 일부입니다.*
