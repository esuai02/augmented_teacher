```json
{
  "holon_id": "strategy-2025-001",
  "slug": "pm-system",
  "type": "strategy",
  "module": "M01_TimeCrystal",
  "meta": {
    "title": "AI PM 기반 회의 자동화 및 오케스트레이션 시스템",
    "created_at": "2025-11-29",
    "updated_at": "2025-11-29",
    "status": "active",
    "owner": "HTE Core",
    "tags": {
      "module": [
        "M16",
        "M17",
        "M13"
      ],
      "topic": [
        "모듈설계",
        "감정분석",
        "UX문제",
        "자동화",
        "AI튜터"
      ],
      "role": "meeting",
      "persona": [
        "학생",
        "부모",
        "AI시스템"
      ],
      "urgency": "긴급",
      "actionability": "action-required"
    },
    "tags_generated_at": "2025-12-01T02:03:37.476801"
  },
  "W": {
    "worldview": {
      "identity": "전국 최고의 AI 기반 수학 교육 오케스트레이션 시스템",
      "belief": "자동화된 PM이 인간 실수를 제거하고 완벽한 일관성을 보장한다",
      "value_system": "견고함, 자동화, 단순함이 확장의 기반이다"
    },
    "will": {
      "drive": "전국 규모 수학 학원 자동화 시스템을 반드시 구축한다",
      "commitment": "어떤 복잡성도 단순화하고 어떤 불일치도 제거한다",
      "non_negotiables": [
        "문서 일관성",
        "자동화 수준",
        "시스템 견고성"
      ]
    },
    "intention": {
      "primary": "AI PM이 회의부터 작업까지 전 과정을 자동 오케스트레이션",
      "secondary": [
        "인간 실수 최소화",
        "의사결정 추적 완벽화",
        "하위 팀 자동 할당"
      ],
      "constraints": [
        "기능 제안 레벨까지만 (세부 개발은 하위 팀)",
        "LLM이 조작 가능한 구조 유지"
      ]
    },
    "goal": {
      "ultimate": "전국 규모 수학 학원 자동화 플랫폼의 PM 시스템",
      "milestones": [
        "Single Source 구조 완성",
        "PM 워크플로우 정의",
        "문서 마이그레이션 완료"
      ],
      "kpi": [
        "문서 일관성 100%",
        "회의→작업 자동 변환율 100%",
        "역참조 동기화 100%"
      ],
      "okr": {
        "objective": "완전 자동화된 PM 시스템 구축",
        "key_results": [
          "이중 관리 제거",
          "6개 Holon 타입 정의",
          "모든 기존 문서 마이그레이션"
        ]
      }
    },
    "activation": {
      "triggers": [
        "문서 불일치 발생",
        "회의 결과 미추적",
        "작업 지연 감지"
      ],
      "resonance_check": "모든 하위 문서가 이 PM 시스템의 W와 정렬되어 있는가?",
      "drift_detection": "복잡성 증가, 수동 작업 증가는 의지 약화 신호"
    }
  },
  "X": {
    "context": "이중 관리(graph.json + 문서 links)로 인한 불일치 문제 해결 필요",
    "constraints": [
      "세부 개발은 하위 팀 담당 (기능 제안 레벨까지만)",
      "LLM/AI가 문서를 조작할 수 있어야 함"
    ],
    "signals": [
      "문서 불일치 발생",
      "회의 결과 미추적",
      "작업 지연"
    ],
    "will": "현재의 복잡성을 단순화하여 견고한 시스템 구축"
  },
  "S": {
    "components": [
      "Holon 문서 시스템 (Single Source of Truth)",
      "PM 워크플로우 (회의 → 결정 → 작업)",
      "AI PM 자동화 엔진",
      "무결성 검증 시스템"
    ],
    "dependencies": [],
    "will": "모든 구성요소가 유기적으로 연결되어 자동 동작"
  },
  "P": {
    "steps": [
      {
        "step": 1,
        "action": "회의 준비",
        "output": "안건 및 관련 문서 수집"
      },
      {
        "step": 2,
        "action": "회의 진행",
        "output": "논의 기록 및 결정 추출"
      },
      {
        "step": 3,
        "action": "후속 처리",
        "output": "Meeting/Decision/Task Holon 생성"
      },
      {
        "step": 4,
        "action": "작업 추적",
        "output": "마감 관리 및 상태 업데이트"
      },
      {
        "step": 5,
        "action": "역참조 동기화",
        "output": "영향받는 문서 자동 업데이트"
      }
    ],
    "will": "모든 단계가 자동화되어 인간 개입 최소화"
  },
  "E": {
    "actions": [
      "기존 graph.json 제거 완료",
      ".cursor/rules PM 시스템으로 재설계 완료",
      "meetings/decisions/tasks 폴더 구조 생성 완료"
    ],
    "tools": [
      "Cursor AI",
      "GitHub",
      "Holarchy 문서 시스템"
    ],
    "eta": "2025-11-29",
    "will": "즉시 실행하여 시스템 전환 완료"
  },
  "R": {
    "insights": [
      "이중 관리는 반드시 불일치를 유발함",
      "문서 중심의 Single Source가 가장 견고함",
      "AI PM이 역참조를 관리하면 일관성 보장"
    ],
    "risks": [
      "기존 문서 마이그레이션 필요",
      "AI PM 로직이 완벽해야 함"
    ],
    "lessons": [
      "단순함이 견고함의 기초"
    ],
    "will": "지속적 개선으로 시스템 안정성 향상"
  },
  "T": {
    "impacts": [
      "hte-doc-000",
      "hte-doc-001",
      "hte-doc-002",
      "hte-doc-003",
      "hte-doc-004",
      "hte-doc-005"
    ],
    "broadcasts": [
      "전체 HTE 조직"
    ],
    "will": "모든 문서와 팀에 새 시스템 적용"
  },
  "A": {
    "next_evolution": "AI PM 자동 회의록 생성 및 실시간 결정 추출",
    "abstractions": [
      "회의 → 결정 → 작업 패턴의 일반화",
      "Holon 타입별 워크플로우 자동화"
    ],
    "will": "완전 자동화된 PM 시스템으로 발전"
  },
  "links": {
    "parent": null,
    "children": [
      "feature-2025-002"
    ],
    "related": [
      "hte-doc-000",
      "hte-framework-001"
    ],
    "supersedes": null
  },
  "attachments": []
}
```

---

# AI PM 기반 회의 자동화 시스템

## 개요

이 문서는 Holarchy 시스템의 핵심 전략으로, AI PM이 회의를 자동화하고 결정을 추적하는 오케스트레이션 시스템을 정의합니다.

## 핵심 변경 사항

### 1. Single Source of Truth

```
이전:
├── _graph.json (별도 관리)
└── 문서 links (각 문서)
→ 이중 관리로 불일치 발생

현재:
└── 문서 links (유일한 진실)
→ AI PM이 역참조 자동 관리
```

### 2. PM 워크플로우

```
회의 → Meeting Holon 생성
     ├── Decision Holon (각 결정마다)
     └── Task Holon (각 Action Item마다)
           └── 하위 개발팀에 할당
```

### 3. 기능 제안 레벨

```
이 시스템이 담당:
✔ 전략 수립
✔ 아키텍처 설계
✔ 기능 제안
✔ 결정 추적
✔ 작업 할당

하위 팀이 담당:
→ 상세 UI/UX
→ 데이터베이스
→ API 명세
→ 코드 구현
```

## Holon 타입

| 타입 | 용도 | 예시 |
|------|------|------|
| strategy | 전략, 비전 | 시장 독점 전략 |
| structure | 시스템 구조 | AI 튜터 아키텍처 |
| feature | 기능 제안 | 학습 진단 리포트 |
| meeting | 회의 기록 | MVP 범위 회의 |
| decision | 의사결정 | 감정 분석 제외 결정 |
| task | 실행 작업 | 대시보드 설계 |

## 파일명 규칙

```
<type>-<YYYY>-<NNN>-<slug>.md

예시:
- strategy-2025-001-pm-system.md
- meeting-2025-028-mvp-scope.md
- decision-2025-015-exclude-emotion.md
- task-2025-089-student-dashboard.md
```

## 폴더 구조

```
0 Docs/
├── holons/         # 전략/구조/기능
├── meetings/       # 회의 기록
├── decisions/      # 의사결정
└── tasks/          # 작업 항목
```

