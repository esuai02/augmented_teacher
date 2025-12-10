#!/usr/bin/env bash
set -e

PROJECT_DIR="/mnt/c/1 Project/augmented_teacher"
cd "$PROJECT_DIR"

TASK_FILE="tasks/tasks-0005-prd-quantum-modeling-completion.md"
TIME_LIMIT=900   # 한 번 실행당 최대 900초 (원하면 숫자 줄이거나 늘려도 됨)

ITERATION=1

echo "🔎 QUANTUM_MODELING_IN_PROGRESS 상태인지 확인 중..."

# SHARED_TASK_NOTES.md 에 QUANTUM_MODELING_IN_PROGRESS 가 있는 동안 계속 반복
while grep -q "QUANTUM_MODELING_IN_PROGRESS" SHARED_TASK_NOTES.md 2>/dev/null; do
  echo ""
  echo "=============================="
  echo "🚀 ITERATION $ITERATION 시작  (Quantum Modeling / 남은 작업 처리)"
  echo "=============================="

  if ! timeout "$TIME_LIMIT" continuous-claude \
    --prompt "$TASK_FILE 와 SHARED_TASK_NOTES.md 를 함께 읽어라.

1) $TASK_FILE 에 정의된 전체 Task 구조(Phase 0~4, 1.1~1.16 등)를 기준으로 현재 남은 작업을 파악해라.
2) SHARED_TASK_NOTES.md 를 읽고:
   - 이미 '완료(✅ 완료)' 로 표시된 항목은 다시 구현하지 말 것.
   - '다음 작업' 섹션과 진행 상황 테이블을 기준으로 **지금 시점에서 가장 자연스러운 다음 작업**을 선택할 것.
3) 이번 실행에서는 **딱 한 묶음의 논리적인 작업 단위**만 수행해라 
   (예: Phase 1.8 전체, 혹은 1.8 안의 세부 항목들을 한 번에 정리).

작업 규칙:
- 새 코드/파일을 만들 때는 $TASK_FILE 에 정의된 파일 경로와 이름을 우선으로 따른다.
- already 구현된 모듈은 삭제하지 말고, 필요할 때만 보완/리팩토링해라.
- 에러 메시지, docstring, 타입 힌트 규칙은 $TASK_FILE 상단 Notes 섹션을 따른다.

작업이 끝난 후에는 SHARED_TASK_NOTES.md 를 아래 형식으로 업데이트해라:

### YYYY-MM-DD 이터레이션 #N

#### 이번 회차 완료 (Phase X.Y)

**Phase X.Y: 작업 이름** - 완료/부분완료/리팩토링 등 상태
- 수행한 작업:
  - ...
- 수정/생성한 파일:
  - ...
- 중요한 결정/메모:
  - ...

---

### 다음 작업 (Phase A.B)

**Phase A.B: 다음 작업 이름**
- 다음 이터레이션에서 집중해야 할 세부 작업 bullet

---

### 전체 진행 상황

| Phase | 항목 | 상태 |
|-------|------|------|
| ... | ... | ... |

---

### 관련 파일 위치

\`\`\`
(필요하면 주요 파일 트리 갱신)
\`\`\`

마지막 규칙:
- Quantum Modeling 관련 **모든 Phase와 Checklist**가 완료되었다고 판단되면,
  SHARED_TASK_NOTES.md 마지막 부분에 있는 'QUANTUM_MODELING_IN_PROGRESS' 를
  'QUANTUM_MODELING_COMPLETE' 로 **한 번만** 변경해라.
- 'QUANTUM_MODELING_COMPLETE' 로 바꾼 이후에는 새로운 구현 작업을 더 이상 생성하지 말고,
  필요한 경우 소규모 정리/주석/요약 정도만 수행해라." \
    --max-runs 1 \
    --disable-commits \
    --disable-updates; then
    echo "⏱️ ITERATION $ITERATION: TIME_LIMIT(${TIME_LIMIT}s) 안에 끝나지 않아서 중단되었습니다."
  fi

  echo ""
  echo "📝 ITERATION $ITERATION 이후 SHARED_TASK_NOTES.md 마지막 15줄:"
  tail -n 15 SHARED_TASK_NOTES.md 2>/dev/null || echo "아직 SHARED_TASK_NOTES.md 없음"

  ITERATION=$((ITERATION + 1))
done

echo ""
echo "🎉 SHARED_TASK_NOTES.md 에서 'QUANTUM_MODELING_IN_PROGRESS' 가 더 이상 보이지 않습니다."
echo "🎉 QUANTUM_MODELING_COMPLETE 상태로 간주하고 루프를 종료합니다."
