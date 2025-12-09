#!/usr/bin/env python3
"""
parse_agents.py - engine_config.php에서 에이전트 정의 파싱

SSOT(Single Source of Truth)인 engine_config.php를 파싱하여
에이전트 정의를 추출합니다.

Usage:
    python parse_agents.py [--output json|yaml]

Author: AI Agent Integration Team
Created: 2025-12-08
"""

import re
import json
import yaml
import os
from pathlib import Path
from typing import Dict, List, Optional
from dataclasses import dataclass, asdict


@dataclass
class AgentDefinition:
    """에이전트 정의"""
    id: int
    name: str
    kr_name: str
    category: str


@dataclass
class ParseResult:
    """파싱 결과"""
    agents: List[AgentDefinition]
    categories: Dict[str, List[int]]
    version: str
    source_file: str
    total_count: int


def get_engine_config_path() -> Path:
    """engine_config.php 경로 반환"""
    # 스크립트 위치 기준으로 상대 경로 계산
    script_dir = Path(__file__).parent
    config_path = script_dir.parent / "agents" / "engine_core" / "config" / "engine_config.php"
    
    if not config_path.exists():
        # 대체 경로 시도
        alt_path = script_dir.parent.parent / "agents" / "engine_core" / "config" / "engine_config.php"
        if alt_path.exists():
            return alt_path
    
    return config_path


def parse_engine_config(config_path: Optional[Path] = None) -> ParseResult:
    """
    engine_config.php를 파싱하여 에이전트 정의 추출
    
    Args:
        config_path: engine_config.php 경로 (None이면 자동 탐지)
    
    Returns:
        ParseResult: 파싱된 에이전트 정의
    """
    if config_path is None:
        config_path = get_engine_config_path()
    
    if not config_path.exists():
        raise FileNotFoundError(f"[parse_agents.py] engine_config.php not found: {config_path}")
    
    with open(config_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    agents = []
    categories = {}
    version = "unknown"
    
    # 버전 추출
    version_match = re.search(r"ENGINE_CORE_VERSION['\"],\s*['\"]([^'\"]+)['\"]", content)
    if version_match:
        version = version_match.group(1)
    
    # AGENT_CONFIG 배열 파싱
    # 패턴: 1  => ['name' => 'onboarding', 'kr_name' => '온보딩', 'category' => 'foundation'],
    agent_pattern = re.compile(
        r"(\d+)\s*=>\s*\[\s*"
        r"'name'\s*=>\s*'([^']+)'\s*,\s*"
        r"'kr_name'\s*=>\s*'([^']+)'\s*,\s*"
        r"'category'\s*=>\s*'([^']+)'\s*"
        r"\]",
        re.MULTILINE
    )
    
    for match in agent_pattern.finditer(content):
        agent_id = int(match.group(1))
        name = match.group(2)
        kr_name = match.group(3)
        category = match.group(4)
        
        agents.append(AgentDefinition(
            id=agent_id,
            name=name,
            kr_name=kr_name,
            category=category
        ))
        
        # 카테고리별 그룹핑
        if category not in categories:
            categories[category] = []
        categories[category].append(agent_id)
    
    # ID 순으로 정렬
    agents.sort(key=lambda x: x.id)
    
    return ParseResult(
        agents=agents,
        categories=categories,
        version=version,
        source_file=str(config_path),
        total_count=len(agents)
    )


def to_dict(result: ParseResult) -> dict:
    """ParseResult를 dict로 변환"""
    return {
        "agents": [asdict(a) for a in result.agents],
        "categories": result.categories,
        "version": result.version,
        "source_file": result.source_file,
        "total_count": result.total_count
    }


def to_json(result: ParseResult, indent: int = 2) -> str:
    """JSON 형식으로 출력"""
    return json.dumps(to_dict(result), ensure_ascii=False, indent=indent)


def to_yaml(result: ParseResult) -> str:
    """YAML 형식으로 출력"""
    return yaml.dump(to_dict(result), allow_unicode=True, default_flow_style=False, sort_keys=False)


def get_agent_by_id(result: ParseResult, agent_id: int) -> Optional[AgentDefinition]:
    """ID로 에이전트 조회"""
    for agent in result.agents:
        if agent.id == agent_id:
            return agent
    return None


def get_agent_by_name(result: ParseResult, name: str) -> Optional[AgentDefinition]:
    """이름으로 에이전트 조회"""
    for agent in result.agents:
        if agent.name == name:
            return agent
    return None


if __name__ == "__main__":
    import argparse
    
    parser = argparse.ArgumentParser(description="Parse engine_config.php for agent definitions")
    parser.add_argument("--output", "-o", choices=["json", "yaml"], default="json",
                       help="Output format (default: json)")
    parser.add_argument("--config", "-c", type=str, default=None,
                       help="Path to engine_config.php")
    
    args = parser.parse_args()
    
    try:
        config_path = Path(args.config) if args.config else None
        result = parse_engine_config(config_path)
        
        if args.output == "json":
            print(to_json(result))
        else:
            print(to_yaml(result))
            
    except FileNotFoundError as e:
        print(f"Error: {e}")
        exit(1)
    except Exception as e:
        print(f"Error parsing config: {e}")
        exit(1)

