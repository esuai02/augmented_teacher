#!/usr/bin/env python3
"""
각 에이전트의 dataindex.php에서 $tablesToCheck if-elseif 체인 정리 - Version 4
"""

import os
import re

AGENTS_DIR = "/mnt/c/alt42/orchestration13/agents"

AGENTS = [
    "agent01_onboarding",
    "agent02_exam_schedule",
    "agent03_goals_analysis",
    "agent04_inspect_weakpoints",
    "agent05_learning_emotion",
    "agent06_teacher_feedback",
    "agent07_interaction_targeting",
    "agent08_calmness",
    "agent09_learning_management",
    "agent10_concept_notes",
    "agent11_problem_notes",
    "agent12_rest_routine",
    "agent13_learning_dropout",
    "agent14_current_position",
    "agent15_problem_redefinition",
    "agent16_interaction_preparation",
    "agent17_remaining_activities",
    "agent18_signature_routine",
    "agent19_interaction_content",
    "agent20_intervention_preparation",
    "agent21_intervention_execution",
    "agent22_module_improvement",
]

# Agent 01-14까지만 특정 테이블 목록이 정의되어 있음
AGENTS_WITH_TABLES = {
    "agent01_onboarding": """['name' => 'mdl_alt42g_learning_progress', 'type' => 'column'],
            ['name' => 'mdl_alt42g_learning_style', 'type' => 'column'],
            ['name' => 'mdl_alt42g_learning_method', 'type' => 'column'],
            ['name' => 'mdl_alt42g_learning_goals', 'type' => 'column'],
            ['name' => 'mdl_alt42g_additional_info', 'type' => 'column'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42o_learning_assessment_results', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'abessi_mbtilog', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],""",

    "agent02_exam_schedule": """['name' => 'alt42_exam_schedule', 'type' => 'column'],
            ['name' => 'alt42t_exam_settings', 'type' => 'column'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'abessi_today', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'abessi_messages', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent03_goals_analysis": """['name' => 'alt42g_student_goals', 'type' => 'column'],
            ['name' => 'alt42g_goal_analysis', 'type' => 'column'],
            ['name' => 'alt42g_learning_sessions', 'type' => 'column'],
            ['name' => 'alt42g_pomodoro_sessions', 'type' => 'column'],
            ['name' => 'alt42g_curriculum_progress', 'type' => 'column'],
            ['name' => 'alt42g_completed_units', 'type' => 'column'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42t_exam_settings', 'type' => 'column'],
            ['name' => 'abessi_today', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],""",

    "agent04_inspect_weakpoints": """['name' => 'alt42_student_activity', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42_ontology_instances', 'type' => 'column'],""",

    "agent05_learning_emotion": """['name' => 'alt42_student_activity', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42_exam_schedule', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent06_teacher_feedback": """['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42t_exam_settings', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],""",

    "agent07_interaction_targeting": """['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'local_aug_reports', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent08_calmness": """['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42_student_activity', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent09_learning_management": """['name' => 'abessi_attendance_record', 'type' => 'column'],
            ['name' => 'alt42g_goal_analysis', 'type' => 'column'],
            ['name' => 'alt42g_pomodoro_sessions', 'type' => 'column'],
            ['name' => 'abessi_messages', 'type' => 'column'],
            ['name' => 'alt42t_exam_settings', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'abessi_schedule', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent10_concept_notes": """['name' => 'abessi_messages', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'abessi_curriculum', 'type' => 'column'],
            ['name' => 'alt42g_teacher_feedback', 'type' => 'column'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent11_problem_notes": """['name' => 'abessi_messages', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'abessi_curriculum', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent12_rest_routine": """['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'abessi_schedule', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent13_learning_dropout": """['name' => 'abessi_today', 'type' => 'column'],
            ['name' => 'abessi_messages', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'abessi_indicators', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",

    "agent14_current_position": """['name' => 'abessi_todayplans', 'type' => 'column'],
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
            ['name' => 'alt42g_pomodoro_sessions', 'type' => 'column'],
            ['name' => 'alt42g_student_goals', 'type' => 'column'],
            ['name' => 'abessi_tracking', 'type' => 'column'],
            ['name' => 'user', 'type' => 'column'],""",
}

DEFAULT_TABLES = """['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42o_learning_assessment_results', 'type' => 'column'],
            ['name' => 'abessi_mbtilog', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],"""

def clean_agent_file(agent_id):
    """에이전트 파일 정리"""

    file_path = os.path.join(AGENTS_DIR, agent_id, "dataindex.php")

    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        return

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    original_len = len(content)

    # $tablesToCheck = []; 이후 if-elseif 체인 찾기
    # 패턴: $tablesToCheck = [];\n\n        if ($agentid === 'agent01_onboarding') { ... } else { ... }

    # 시작점 찾기
    start_marker = "$tablesToCheck = [];\n"
    start_pos = content.find(start_marker)

    if start_pos == -1:
        print(f"{agent_id}: $tablesToCheck pattern not found")
        return

    # if 문 시작 찾기
    if_start = content.find("if ($agentid === 'agent01_onboarding')", start_pos)
    if if_start == -1:
        print(f"{agent_id}: if pattern not found")
        return

    # else 블록 끝 찾기 - "} else {" 다음의 닫는 }
    else_marker = "} else {"
    else_pos = content.find(else_marker, if_start)
    if else_pos == -1:
        print(f"{agent_id}: else block not found")
        return

    # else 블록의 닫는 } 찾기
    # "        }" 형태로 끝남
    search_start = else_pos + len(else_marker)
    bracket_count = 1
    end_pos = search_start

    while end_pos < len(content) and bracket_count > 0:
        if content[end_pos] == '{':
            bracket_count += 1
        elif content[end_pos] == '}':
            bracket_count -= 1
        end_pos += 1

    block_start = start_pos + len(start_marker)
    block_end = end_pos

    # 해당 에이전트의 테이블 목록 가져오기
    if agent_id in AGENTS_WITH_TABLES:
        tables = AGENTS_WITH_TABLES[agent_id]
        comment = f"// {agent_id} 테이블 목록"
    else:
        tables = DEFAULT_TABLES
        comment = f"// {agent_id} - 기본 테이블 목록"

    new_block = f"""
        {comment}
        $tablesToCheck = [
            {tables}
        ];
"""

    content = content[:block_start] + new_block + content[block_end:]

    new_len = len(content)

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

    reduction = original_len - new_len
    print(f"Cleaned {agent_id}: {original_len} -> {new_len} bytes (reduced {reduction} bytes)")

def main():
    print("Starting cleanup of $tablesToCheck blocks (v4)...")

    for agent in AGENTS:
        try:
            clean_agent_file(agent)
        except Exception as e:
            print(f"Error processing {agent}: {e}")
            import traceback
            traceback.print_exc()

    print("\nDone!")

if __name__ == "__main__":
    main()
