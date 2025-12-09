#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
각 에이전트의 rules 폴더에 있는 파일들을 통합하여 gendata.md 생성
"""

import os
from pathlib import Path
from datetime import datetime

def create_gendata(agents_dir):
    """각 에이전트의 rules 폴더에서 파일들을 통합하여 gendata.md 생성"""
    
    agents_path = Path(agents_dir)
    agents = sorted([d for d in agents_path.iterdir() if d.is_dir() and d.name.startswith('agent')])
    
    for agent_dir in agents:
        rules_dir = agent_dir / 'rules'
        
        if not rules_dir.exists():
            print(f"[SKIP] {agent_dir.name} - rules 폴더 없음")
            continue
        
        gendata_path = rules_dir / 'gendata.md'
        
        # 파일 내용 수집
        content_parts = []
        
        # 헤더
        content_parts.append(f"# {agent_dir.name} - Generated Data Documentation\n")
        content_parts.append(f"생성일: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
        content_parts.append("\n---\n\n")
        
        # mission.md
        mission_path = rules_dir / 'mission.md'
        if mission_path.exists():
            content_parts.append("## Mission\n\n")
            try:
                content_parts.append(mission_path.read_text(encoding='utf-8'))
            except:
                content_parts.append(mission_path.read_text(encoding='utf-8-sig'))
            content_parts.append("\n\n---\n\n")
        
        # questions.md
        questions_path = rules_dir / 'questions.md'
        if questions_path.exists():
            content_parts.append("## Questions\n\n")
            try:
                content_parts.append(questions_path.read_text(encoding='utf-8'))
            except:
                content_parts.append(questions_path.read_text(encoding='utf-8-sig'))
            content_parts.append("\n\n---\n\n")
        
        # metadata.md
        metadata_path = rules_dir / 'metadata.md'
        if metadata_path.exists():
            content_parts.append("## Metadata\n\n")
            try:
                content_parts.append(metadata_path.read_text(encoding='utf-8'))
            except:
                content_parts.append(metadata_path.read_text(encoding='utf-8-sig'))
            content_parts.append("\n\n---\n\n")
        
        # rules.yaml
        rules_path = rules_dir / 'rules.yaml'
        if rules_path.exists():
            content_parts.append("## Rules\n\n")
            content_parts.append("```yaml\n")
            try:
                content_parts.append(rules_path.read_text(encoding='utf-8'))
            except:
                content_parts.append(rules_path.read_text(encoding='utf-8-sig'))
            content_parts.append("\n```\n")
        
        # 파일 작성
        if content_parts:
            try:
                gendata_path.write_text(''.join(content_parts), encoding='utf-8')
                print(f"[OK] {agent_dir.name}/gendata.md 생성 완료")
            except Exception as e:
                print(f"[ERROR] {agent_dir.name} - 파일 작성 실패: {e}")
        else:
            print(f"[SKIP] {agent_dir.name} - 통합할 파일 없음")

if __name__ == '__main__':
    import sys
    if len(sys.argv) > 1:
        agents_dir = sys.argv[1]
    else:
        agents_dir = r'C:\1 Project\augmented_teacher\alt42\orchestration\agents'
    create_gendata(agents_dir)

