#!/usr/bin/env python3
"""
각 에이전트의 dataindex.php에서 해당 에이전트 정보만 남기고 정리 - Version 2
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

def find_block_end(content, start_pos):
    """중괄호 블록의 끝 위치 찾기"""
    bracket_count = 0
    i = start_pos
    while i < len(content):
        if content[i] == '{':
            bracket_count += 1
        elif content[i] == '}':
            bracket_count -= 1
            if bracket_count == 0:
                return i
        i += 1
    return len(content)

def extract_agent_mapping_block(content, agent_id):
    """해당 에이전트의 $fieldTableMapping 블록만 추출"""

    # if ($agentid === 'agent01_onboarding') 패턴
    if_pattern = rf"if \(\$agentid === '{agent_id}'\) \{{\s*\n\s*\$fieldTableMapping = \["
    elseif_pattern = rf"\}} elseif \(\$agentid === '{agent_id}'\) \{{\s*\n\s*\$fieldTableMapping = \["

    # if 패턴 먼저 시도
    match = re.search(if_pattern, content)
    if match:
        # 첫 번째 if 블록 - 배열 내용만 추출
        array_start = match.end() - 1  # '[' 위치
        bracket_count = 1
        i = array_start + 1
        while i < len(content) and bracket_count > 0:
            if content[i] == '[':
                bracket_count += 1
            elif content[i] == ']':
                bracket_count -= 1
            i += 1
        array_end = i
        array_content = content[array_start:array_end]
        return f"$fieldTableMapping = {array_content};"

    # elseif 패턴 시도
    match = re.search(elseif_pattern, content)
    if match:
        array_start = match.end() - 1  # '[' 위치
        bracket_count = 1
        i = array_start + 1
        while i < len(content) and bracket_count > 0:
            if content[i] == '[':
                bracket_count += 1
            elif content[i] == ']':
                bracket_count -= 1
            i += 1
        array_end = i
        array_content = content[array_start:array_end]
        return f"$fieldTableMapping = {array_content};"

    return None

def find_mapping_block_range(content):
    """전체 if-elseif 체인의 시작과 끝 위치 찾기"""

    # 시작: $fieldTableMapping = []; 다음 줄의 if문
    start_pattern = r'\$fieldTableMapping = \[\];\s*\n'
    start_match = re.search(start_pattern, content)
    if not start_match:
        return None, None

    # if 문 시작 찾기
    if_match = re.search(r"if \(\$agentid === 'agent\d+_\w+'\) \{", content[start_match.end():])
    if not if_match:
        return None, None

    block_start = start_match.end() + if_match.start()

    # 마지막 } 찾기 - 중괄호 매칭으로
    i = block_start
    bracket_count = 0
    in_block = False

    while i < len(content):
        if content[i] == '{':
            bracket_count += 1
            in_block = True
        elif content[i] == '}':
            bracket_count -= 1
            if bracket_count == 0 and in_block:
                # 다음 줄이 elseif인지 확인
                rest = content[i+1:i+50]
                if not re.match(r'\s*elseif', rest):
                    return block_start, i + 1
        i += 1

    return block_start, len(content)

def clean_agent_file(agent_id):
    """에이전트 파일 정리"""

    file_path = os.path.join(AGENTS_DIR, agent_id, "dataindex.php")

    if not os.path.exists(file_path):
        print(f"File not found: {file_path}")
        return

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    original_len = len(content)

    # 1. $fieldTableMapping 블록 정리
    # 해당 에이전트의 매핑 추출
    agent_mapping = extract_agent_mapping_block(content, agent_id)

    # 전체 if-elseif 체인 범위 찾기
    block_start, block_end = find_mapping_block_range(content)

    if block_start and block_end:
        if agent_mapping:
            # 매핑이 있으면 해당 에이전트 것만 유지
            new_block = f"\n// {agent_id} 필드별 테이블 매핑\n{agent_mapping}\n"
        else:
            # 매핑이 없으면 빈 배열 (이미 $fieldTableMapping = []; 가 있음)
            new_block = f"\n// {agent_id} - 매핑 없음\n"

        content = content[:block_start] + new_block + content[block_end:]

    # 2. agent01/agent13 특화 블록 정리 (lines 209-287 근처)
    # agent01 특화 블록
    agent01_pattern = r"if \(\$agentid === 'agent01_onboarding'\) \{\s*\n\s*\$agent01Tables = \[[\s\S]*?\];\s*\n\s*\$agentTables = array_merge\(\$agentTables, \$agent01Tables\);\s*\n\}"

    agent01_match = re.search(agent01_pattern, content)
    if agent01_match:
        if agent_id == 'agent01_onboarding':
            # agent01은 조건문 제거하고 내용만 유지
            inner_content = re.search(r'\$agent01Tables = \[[\s\S]*?\];\s*\n\s*\$agentTables = array_merge\(\$agentTables, \$agent01Tables\);', agent01_match.group(0))
            if inner_content:
                content = content[:agent01_match.start()] + "// Agent01 특화 테이블\n" + inner_content.group(0) + "\n" + content[agent01_match.end():]
        else:
            # 다른 에이전트는 이 블록 제거
            content = content[:agent01_match.start()] + content[agent01_match.end():]

    # agent13 특화 블록
    agent13_pattern = r"if \(\$agentid === 'agent13_learning_dropout'\) \{\s*\n\s*\$agent13Tables = \[[\s\S]*?\];\s*\n\s*\$agentTables = array_merge\(\$agentTables, \$agent13Tables\);\s*\n\}"

    agent13_match = re.search(agent13_pattern, content)
    if agent13_match:
        if agent_id == 'agent13_learning_dropout':
            inner_content = re.search(r'\$agent13Tables = \[[\s\S]*?\];\s*\n\s*\$agentTables = array_merge\(\$agentTables, \$agent13Tables\);', agent13_match.group(0))
            if inner_content:
                content = content[:agent13_match.start()] + "// Agent13 특화 테이블\n" + inner_content.group(0) + "\n" + content[agent13_match.end():]
        else:
            content = content[:agent13_match.start()] + content[agent13_match.end():]

    # 3. identifyDataType 함수 내 에이전트별 분기도 정리
    # 런타임에 $agentid로 체크하므로 해당 에이전트 블록만 남김

    new_len = len(content)

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

    reduction = original_len - new_len
    if reduction > 0:
        print(f"Cleaned {agent_id}: {original_len} -> {new_len} bytes (reduced {reduction} bytes, {reduction*100//original_len}%)")
    else:
        print(f"Cleaned {agent_id}: no changes needed or pattern not found")

def main():
    print("Starting cleanup of agent dataindex.php files (v2)...")

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
