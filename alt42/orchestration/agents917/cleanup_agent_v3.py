#!/usr/bin/env python3
"""
각 에이전트의 dataindex.php에서 해당 에이전트 정보만 남기고 정리 - Version 3
identifyDataType 함수 내 $tablesToCheck if-elseif 체인도 정리
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

# Agent 01-14까지만 특정 매핑이 있음
AGENTS_WITH_TABLES = [
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
]

def extract_agent_tables_block(content, agent_id):
    """해당 에이전트의 $tablesToCheck 블록 추출"""

    # if ($agentid === 'agent01_onboarding') 패턴 (첫 번째 if)
    if_pattern = rf"if \(\$agentid === '{agent_id}'\) \{{\s*\n\s*// .*?\n\s*\$tablesToCheck = \["
    elseif_pattern = rf"\}} elseif \(\$agentid === '{agent_id}'\) \{{\s*\n\s*// .*?\n\s*\$tablesToCheck = \["

    for pattern in [if_pattern, elseif_pattern]:
        match = re.search(pattern, content)
        if match:
            # 배열 시작부터 끝까지 찾기
            array_start = content.find('$tablesToCheck = [', match.start())
            if array_start == -1:
                continue

            bracket_count = 0
            i = array_start + len('$tablesToCheck = ')
            while i < len(content):
                if content[i] == '[':
                    bracket_count += 1
                elif content[i] == ']':
                    bracket_count -= 1
                    if bracket_count == 0:
                        # 배열 종료
                        array_end = i + 1
                        # 코멘트도 추출
                        comment_match = re.search(r'// .*?\n', content[match.start():array_start])
                        if comment_match:
                            comment = content[match.start():match.start()+comment_match.end()].strip()
                            comment = re.sub(r'^.*?// ', '// ', comment)
                        else:
                            comment = f"// {agent_id} 테이블 목록"
                        return comment + "\n        " + content[array_start:array_end] + ";"
                i += 1

    return None

def clean_tables_to_check(content, agent_id):
    """$tablesToCheck if-elseif 체인 정리"""

    # 전체 if-elseif 체인 찾기
    # "// Agent01 특화 테이블 목록 (우선순위 순서)" 코멘트 이후부터
    # else { ... } 까지

    chain_start_pattern = r"\$tablesToCheck = \[\];\s*\n\s*if \(\$agentid === 'agent01_onboarding'\)"
    chain_start = re.search(chain_start_pattern, content)

    if not chain_start:
        return content

    # else 블록 끝 찾기
    chain_end_pattern = r"\} else \{\s*\n\s*// 다른 에이전트의 경우.*?\$tablesToCheck = \[[\s\S]*?\];\s*\n\s*\}"

    chain_end = re.search(chain_end_pattern, content[chain_start.start():])
    if not chain_end:
        return content

    block_start = chain_start.start() + len('$tablesToCheck = [];') + 1
    block_end = chain_start.start() + chain_end.end()

    # 해당 에이전트의 테이블 블록 추출
    agent_tables = extract_agent_tables_block(content, agent_id)

    if agent_id in AGENTS_WITH_TABLES and agent_tables:
        # 해당 에이전트의 테이블 목록이 있으면 직접 할당
        new_block = f"\n        {agent_tables}\n"
    else:
        # 없으면 기본 테이블 사용
        new_block = """
        // 기본 테이블 목록
        $tablesToCheck = [
            ['name' => 'alt42_goinghome', 'type' => 'json'],
            ['name' => 'alt42o_onboarding', 'type' => 'column'],
            ['name' => 'alt42o_learning_assessment_results', 'type' => 'column'],
            ['name' => 'abessi_mbtilog', 'type' => 'column'],
            ['name' => 'alt42_student_profiles', 'type' => 'json'],
        ];
"""

    content = content[:block_start] + new_block + content[block_end:]

    return content

def clean_agent_file(agent_id):
    """에이전트 파일 정리"""

    file_path = os.path.join(AGENTS_DIR, agent_id, "dataindex.php")

    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        return

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    original_len = len(content)

    # $tablesToCheck if-elseif 체인 정리
    content = clean_tables_to_check(content, agent_id)

    new_len = len(content)

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

    reduction = original_len - new_len
    if reduction > 0:
        print(f"Cleaned {agent_id}: {original_len} -> {new_len} bytes (reduced {reduction} bytes, {reduction*100//original_len}%)")
    else:
        print(f"Cleaned {agent_id}: no changes or pattern not found")

def main():
    print("Starting cleanup of $tablesToCheck blocks (v3)...")

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
