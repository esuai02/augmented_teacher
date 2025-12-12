#!/bin/bash

# 서버 정보
SERVER="root@58.180.27.46"
REMOTE_BASE="/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration13/agents"

echo "📤 에이전트 dataindex.php 파일 업로드 시작..."

# 에이전트 목록
agents=(
    "agent01_onboarding"
    "agent02_exam_schedule"
    "agent03_goals_analysis"
    "agent04_inspect_weakpoints"
    "agent05_learning_emotion"
    "agent06_teacher_feedback"
    "agent07_interaction_targeting"
    "agent08_calmness"
    "agent09_learning_management"
    "agent10_concept_notes"
    "agent11_problem_notes"
    "agent12_rest_routine"
    "agent13_learning_dropout"
    "agent14_current_position"
    "agent15_problem_redefinition"
    "agent16_interaction_preparation"
    "agent17_remaining_activities"
    "agent18_signature_routine"
    "agent19_interaction_content"
    "agent20_intervention_preparation"
    "agent21_intervention_execution"
    "agent22_module_improvement"
)

for agent in "${agents[@]}"; do
    echo "Uploading ${agent}/dataindex.php..."
    scp "./${agent}/dataindex.php" "${SERVER}:${REMOTE_BASE}/${agent}/"
done

# 공통 컴포넌트도 업로드
echo "Uploading agent_orchestration/dataindex_common.php..."
scp "./agent_orchestration/dataindex_common.php" "${SERVER}:${REMOTE_BASE}/agent_orchestration/"

echo "✅ 업로드 완료!"
echo ""
echo "테스트 URL:"
echo "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration13/agents/agent18_signature_routine/dataindex.php"
