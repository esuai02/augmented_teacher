#!/usr/bin/env python3
"""
각 에이전트의 dataindex.php에서 해당 에이전트 정보만 남기고 정리
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

def extract_agent_mapping(content, agent_id):
    """특정 에이전트의 $fieldTableMapping 블록만 추출"""

    # 에이전트별 매핑 패턴 찾기
    # if ($agentid === 'agent01_onboarding') { ... } elseif ...

    # 해당 에이전트의 매핑 시작 찾기
    pattern_start = rf"(if \(\$agentid === '{agent_id}'\)|elseif \(\$agentid === '{agent_id}'\))\s*\{{\s*\n\s*\$fieldTableMapping\s*=\s*\["

    match = re.search(pattern_start, content)
    if not match:
        # 매핑이 없는 에이전트는 빈 배열 반환
        return None

    start_pos = match.start()

    # 해당 블록의 끝 찾기 (다음 elseif 또는 } else 또는 블록 끝)
    # 브라켓 카운팅으로 블록 끝 찾기
    bracket_count = 0
    in_block = False
    block_end = start_pos

    i = match.end() - 1  # '[' 위치부터 시작
    while i < len(content):
        char = content[i]
        if char == '[':
            bracket_count += 1
            in_block = True
        elif char == ']':
            bracket_count -= 1
            if bracket_count == 0 and in_block:
                # 배열 끝 찾음, 이제 블록 끝(};) 찾기
                rest = content[i:i+50]
                semi_match = re.search(r'\];\s*\n?\}', rest)
                if semi_match:
                    block_end = i + semi_match.end()
                break
        i += 1

    if block_end > start_pos:
        block = content[start_pos:block_end]
        # 매핑 배열만 추출
        array_match = re.search(r'\$fieldTableMapping\s*=\s*\[([\s\S]*?)\];', block)
        if array_match:
            return array_match.group(0)

    return None

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
    # 전체 if-elseif 체인 찾기
    mapping_block_pattern = r'// Agent01 Onboarding 필드별.*?\$fieldTableMapping = \[\];[\s\S]*?(?=\n\n// 모든 필드에 대해|// identifyDataType)'

    match = re.search(mapping_block_pattern, content)
    if match:
        # 해당 에이전트의 매핑만 추출
        agent_mapping = extract_agent_mapping(content, agent_id)

        if agent_mapping:
            # 매핑이 있으면 해당 에이전트 것만 유지
            new_block = f"""// {agent_id} 필드별 명시적 테이블 매핑
{agent_mapping}
"""
        else:
            # 매핑이 없으면 빈 배열
            new_block = f"""// {agent_id} 필드별 테이블 매핑 (정의되지 않음)
$fieldTableMapping = [];
"""

        content = content[:match.start()] + new_block + content[match.end():]

    # 2. agent01/agent13 특화 테이블 추가 블록 정리
    # if ($agentid === 'agent01_onboarding') { $agent01Tables = ... }
    agent01_block = re.search(
        r"// Agent01 Onboarding 특화:.*?if \(\$agentid === 'agent01_onboarding'\)[\s\S]*?\n\}\n",
        content
    )
    if agent01_block:
        if agent_id == 'agent01_onboarding':
            # agent01은 유지하되 조건문 제거
            block = agent01_block.group(0)
            # if 조건 제거하고 내용만 유지
            inner = re.search(r'\{\s*([\s\S]*?)\s*\}\s*$', block)
            if inner:
                content = content[:agent01_block.start()] + f"// Agent01 Onboarding 특화 테이블\n{inner.group(1)}\n" + content[agent01_block.end():]
        else:
            # 다른 에이전트는 이 블록 제거
            content = content[:agent01_block.start()] + content[agent01_block.end():]

    # agent13 블록도 동일하게
    agent13_block = re.search(
        r"// Agent13 Learning Dropout 특화:.*?if \(\$agentid === 'agent13_learning_dropout'\)[\s\S]*?\n\}\n",
        content
    )
    if agent13_block:
        if agent_id == 'agent13_learning_dropout':
            block = agent13_block.group(0)
            inner = re.search(r'\{\s*([\s\S]*?)\s*\}\s*$', block)
            if inner:
                content = content[:agent13_block.start()] + f"// Agent13 Learning Dropout 특화 테이블\n{inner.group(1)}\n" + content[agent13_block.end():]
        else:
            content = content[:agent13_block.start()] + content[agent13_block.end():]

    # 3. identifyDataType 함수 내 에이전트별 분기 정리는 복잡하므로 유지
    # (런타임에 $agentid 체크하므로 해당 에이전트만 실행됨)

    # 4. interpretFieldStatus 함수도 agent01에만 해당하므로 다른 에이전트는 간소화 가능
    # (런타임 체크로 동작하므로 일단 유지)

    new_len = len(content)

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

    reduction = original_len - new_len
    print(f"Cleaned {agent_id}: {original_len} -> {new_len} bytes (reduced {reduction} bytes)")

def main():
    print("Starting cleanup of agent dataindex.php files...")

    for agent in AGENTS:
        try:
            clean_agent_file(agent)
        except Exception as e:
            print(f"Error processing {agent}: {e}")

    print("\nDone!")

if __name__ == "__main__":
    main()
