#!/usr/bin/env bash
set -e

PROJECT_DIR="/mnt/c/1 Project/augmented_teacher"
cd "$PROJECT_DIR"

PHASE_DESC="tasks/tasks-0005-prd-quantum-modeling-completion.md / Phase 1 - 1.1"

# 한 번 실행당 허용할 최대 시간 (초 단위, 필요하면 숫자 바꿔도 됨)
TIME_LIMIT=600

for i in {1..5}; do
  echo ""
  echo "=============================="
  echo "🚀 ITERATION $i / 5 시작  ($PHASE_DESC)"
  echo "=============================="

  # TIME_LIMIT 안에 continuous-claude가 안 끝나면 자동으로 중단
  if ! timeout "$TIME_LIMIT" continuous-claude \
    --prompt "tasks/tasks-0005-prd-quantum-modeling-completion.md 읽고 Phase 1의 1.1부터 이번 회차에 한 단계만 진행해줘. 이번 회차에서 한 일과 남은 일은 SHARED_TASK_NOTES.md에 정리해줘." \
    --max-runs 1 \
    --disable-commits \
    --disable-updates; then
    echo "⏱️  ITERATION $i / 5: TIME_LIMIT 안에 끝나지 않아서 중단되었습니다."
  fi

  echo ""
  echo "📝 ITERATION $i / 5 이후 SHARED_TASK_NOTES.md 마지막 10줄:"
  tail -n 10 SHARED_TASK_NOTES.md 2>/dev/null || echo "아직 SHARED_TASK_NOTES.md 없음"

done
