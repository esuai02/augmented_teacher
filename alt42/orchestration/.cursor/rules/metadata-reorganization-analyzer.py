#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
에이전트별 Metadata 관련성 분석 스크립트
각 에이전트의 mission.md와 metadata.md를 분석하여 관련성 점수를 계산합니다.
"""

import os
import re
import json
from pathlib import Path
from collections import defaultdict

# 프로젝트 루트 경로
PROJECT_ROOT = Path(__file__).parent.parent.parent
AGENTS_DIR = PROJECT_ROOT / "agents"

def read_file_content(file_path):
    """파일 내용을 읽어서 반환"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            return f.read()
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return ""

def extract_mission_keywords(mission_content):
    """mission.md에서 핵심 키워드 추출"""
    keywords = set()
    
    # 제목 추출
    titles = re.findall(r'^#+\s+(.+)$', mission_content, re.MULTILINE)
    keywords.update([t.lower() for t in titles])
    
    # 핵심 기능 키워드 추출
    key_phrases = [
        '학생', '학습', '목표', '시험', '문제', '개념', '진도', '성적', '피드백',
        '상호작용', '개입', '루틴', '감정', '동기', '보호자', '선생님',
        '온보딩', '일정', '계획', '분석', '노트', '오답', '복습', '질의응답',
        '침착도', '이탈', '위치', '재정의', '준비', '실행', '개선'
    ]
    
    mission_lower = mission_content.lower()
    for phrase in key_phrases:
        if phrase in mission_lower:
            keywords.add(phrase)
    
    # 명시적 언급된 데이터 항목 추출
    data_items = re.findall(r'[-•]\s*(.+?)(?:\.|$)', mission_content)
    keywords.update([item.strip().lower() for item in data_items])
    
    return keywords

def extract_metadata_items(metadata_content):
    """metadata.md에서 데이터 항목 추출"""
    items = []
    
    # 번호가 있는 항목 추출 (예: "1. 학생 이름")
    pattern = r'^\d+\.\s+(.+?)(?:\s+\(|$)'
    matches = re.findall(pattern, metadata_content, re.MULTILINE)
    
    for match in matches:
        item_name = match.strip()
        # 카테고리 정보 추출
        category_match = re.search(r'##\s+(.+?)\s+\(', metadata_content[:metadata_content.find(item_name)])
        category = category_match.group(1) if category_match else "기타"
        
        items.append({
            'name': item_name,
            'category': category,
            'full_text': item_name
        })
    
    return items

def calculate_relevance_score(data_item, mission_keywords, mission_content):
    """데이터 항목과 에이전트 mission 간 관련성 점수 계산"""
    score = 0
    item_lower = data_item['name'].lower()
    item_words = set(item_lower.split())
    
    # 직접 관련 (3점): mission에서 명시적으로 언급
    if item_lower in mission_content.lower():
        score += 3
    
    # 키워드 매칭 (2점): mission 키워드와 데이터 항목 단어 일치
    common_words = item_words.intersection(mission_keywords)
    if common_words:
        score += 2 * len(common_words) / max(len(item_words), 1)
    
    # 간접 관련 (1점): 관련 키워드 포함
    related_keywords = {
        '학생 이름': ['학생', '프로필', '기본'],
        '학년': ['학년', '레벨', '수준'],
        '생년월일': ['생년월일', '나이', '기본'],
        '성별': ['성별', '기본'],
        '시험': ['시험', '일정', '대비'],
        '목표': ['목표', '계획', '설정'],
        '문제': ['문제', '풀이', '활동'],
        '개념': ['개념', '이해', '학습'],
        '진도': ['진도', '진행', '완료'],
        '감정': ['감정', '동기', '스트레스'],
        '보호자': ['보호자', '부모', '가정'],
        '피드백': ['피드백', '조언', '지도']
    }
    
    for keyword, related in related_keywords.items():
        if keyword in item_lower:
            if any(r in mission_content.lower() for r in related):
                score += 1
                break
    
    return min(score, 10)  # 최대 10점으로 제한

def analyze_all_agents():
    """모든 에이전트 분석"""
    agents = sorted([d for d in os.listdir(AGENTS_DIR) 
                    if d.startswith('agent') and (AGENTS_DIR / d).is_dir()])
    
    # 기준 데이터셋 (agent01_onboarding의 metadata.md)
    base_metadata_path = AGENTS_DIR / "agent01_onboarding" / "rules" / "metadata.md"
    base_metadata_content = read_file_content(base_metadata_path)
    base_data_items = extract_metadata_items(base_metadata_content)
    
    print(f"기준 데이터 항목 수: {len(base_data_items)}")
    
    # 각 에이전트 분석
    agent_missions = {}
    agent_keywords = {}
    
    for agent in agents:
        mission_path = AGENTS_DIR / agent / "rules" / "mission.md"
        if mission_path.exists():
            mission_content = read_file_content(mission_path)
            keywords = extract_mission_keywords(mission_content)
            agent_missions[agent] = mission_content
            agent_keywords[agent] = keywords
            print(f"{agent}: {len(keywords)} keywords")
    
    # 관련성 매트릭스 생성
    relevance_matrix = {}
    
    for item in base_data_items:
        item_name = item['name']
        relevance_matrix[item_name] = {}
        
        for agent in agents:
            if agent in agent_missions:
                score = calculate_relevance_score(
                    item, 
                    agent_keywords[agent], 
                    agent_missions[agent]
                )
                relevance_matrix[item_name][agent] = score
    
    return relevance_matrix, base_data_items, agents

def determine_primary_agent(relevance_matrix):
    """각 데이터 항목의 주 담당 에이전트 결정"""
    primary_assignments = {}
    
    for item_name, scores in relevance_matrix.items():
        if scores:
            # 최고 점수 에이전트 찾기
            max_score = max(scores.values())
            if max_score > 0:
                primary_agent = max(scores.items(), key=lambda x: x[1])[0]
                primary_assignments[item_name] = {
                    'primary_agent': primary_agent,
                    'score': max_score,
                    'all_scores': scores
                }
            else:
                primary_assignments[item_name] = {
                    'primary_agent': None,
                    'score': 0,
                    'all_scores': scores
                }
    
    return primary_assignments

def find_related_agents(primary_assignments, threshold=2):
    """보조 관련 에이전트 찾기"""
    related_data_mapping = defaultdict(list)
    
    for item_name, assignment in primary_assignments.items():
        primary = assignment['primary_agent']
        scores = assignment['all_scores']
        
        # 주 담당 에이전트 외 관련성 점수 threshold 이상인 에이전트
        for agent, score in scores.items():
            if agent != primary and score >= threshold:
                related_data_mapping[primary].append({
                    'data_name': item_name,
                    'related_agent': agent,
                    'relevance_score': score
                })
    
    return related_data_mapping

def main():
    print("=" * 60)
    print("에이전트별 Metadata 관련성 분석 시작")
    print("=" * 60)
    
    # 분석 실행
    relevance_matrix, base_data_items, agents = analyze_all_agents()
    
    # 주 담당 에이전트 결정
    primary_assignments = determine_primary_agent(relevance_matrix)
    
    # 보조 관련 에이전트 찾기
    related_data_mapping = find_related_agents(primary_assignments, threshold=2)
    
    # 결과 저장
    output_dir = PROJECT_ROOT / ".cursor" / "rules"
    output_dir.mkdir(parents=True, exist_ok=True)
    
    # 관련성 매트릭스 저장
    with open(output_dir / "relevance_matrix.json", 'w', encoding='utf-8') as f:
        json.dump(relevance_matrix, f, ensure_ascii=False, indent=2)
    
    # 주 담당 에이전트 할당 저장
    with open(output_dir / "primary_assignments.json", 'w', encoding='utf-8') as f:
        json.dump(primary_assignments, f, ensure_ascii=False, indent=2)
    
    # 관련 데이터 매핑 저장
    with open(output_dir / "related_data_mapping.json", 'w', encoding='utf-8') as f:
        json.dump(dict(related_data_mapping), f, ensure_ascii=False, indent=2)
    
    # 통계 출력
    print("\n" + "=" * 60)
    print("분석 결과 요약")
    print("=" * 60)
    
    agent_data_count = defaultdict(int)
    for item_name, assignment in primary_assignments.items():
        primary = assignment['primary_agent']
        if primary:
            agent_data_count[primary] += 1
    
    print(f"\n주 담당 에이전트별 데이터 항목 수:")
    for agent in sorted(agents):
        count = agent_data_count[agent]
        print(f"  {agent}: {count}개")
    
    print(f"\n관련 데이터 매핑:")
    for agent, related_items in related_data_mapping.items():
        if related_items:
            print(f"  {agent}: {len(related_items)}개 관련 데이터")
    
    print("\n분석 완료! 결과 파일:")
    print(f"  - relevance_matrix.json")
    print(f"  - primary_assignments.json")
    print(f"  - related_data_mapping.json")

if __name__ == "__main__":
    main()
