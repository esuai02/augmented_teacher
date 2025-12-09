#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
3차 검증 및 최적화 스크립트
- 모든 metadata.md 파일의 일관성 및 완전성 검증
- comprehensive_data_agent_mapping.md와의 일치성 확인
- 데이터 중복 검사
- 카테고리 구조 일관성 확인
"""

import os
import re
import json
from pathlib import Path
from collections import defaultdict

# 작업 디렉토리 설정
BASE_DIR = Path(__file__).parent.parent.parent
AGENTS_DIR = BASE_DIR / "agents"
MAPPING_FILE = BASE_DIR / ".cursor" / "rules" / "comprehensive_data_agent_mapping.md"

def extract_data_numbers_from_metadata(md_content):
    """metadata.md에서 데이터 번호 추출"""
    numbers = []
    # 번호 패턴: "1. ", "22. ", "100. " 등
    pattern = r'^(\d+)\.\s+'
    for line in md_content.split('\n'):
        match = re.match(pattern, line.strip())
        if match:
            numbers.append(int(match.group(1)))
    return sorted(set(numbers))

def extract_data_from_mapping():
    """comprehensive_data_agent_mapping.md에서 데이터-에이전트 매핑 추출"""
    mapping = defaultdict(list)
    
    if not MAPPING_FILE.exists():
        print(f"⚠️  매핑 파일을 찾을 수 없습니다: {MAPPING_FILE}")
        return mapping
    
    content = MAPPING_FILE.read_text(encoding='utf-8')
    
    # 각 항목 추출
    pattern = r'\|\s*(\d+)\.\s+([^|]+)\s*\|\s*([^|]+)\s*\|'
    for match in re.finditer(pattern, content):
        data_num = int(match.group(1))
        data_name = match.group(2).strip()
        primary_agent = match.group(3).strip()
        
        if primary_agent and primary_agent != '-':
            mapping[primary_agent].append({
                'number': data_num,
                'name': data_name
            })
    
    return mapping

def verify_agent_metadata(agent_name):
    """개별 에이전트의 metadata.md 검증"""
    agent_dir = AGENTS_DIR / agent_name
    metadata_file = agent_dir / "rules" / "metadata.md"
    
    if not metadata_file.exists():
        return {
            'exists': False,
            'errors': [f"metadata.md 파일이 없습니다"]
        }
    
    content = metadata_file.read_text(encoding='utf-8')
    data_numbers = extract_data_numbers_from_metadata(content)
    
    # 기본 구조 확인
    has_intro = "에이전트가 현실 세계에서 완벽하게 작동하기 위해서는" in content
    has_reference = "다른 에이전트에 배치된 관련 데이터" in content
    
    errors = []
    warnings = []
    
    if not has_intro:
        warnings.append("표준 소개 문구가 없습니다")
    
    if not has_reference:
        warnings.append("관련 데이터 참조 안내가 없습니다")
    
    if len(data_numbers) == 0:
        errors.append("데이터 항목이 없습니다")
    
    return {
        'exists': True,
        'data_count': len(data_numbers),
        'data_numbers': data_numbers,
        'errors': errors,
        'warnings': warnings
    }

def main():
    print("=" * 80)
    print("3차 검증 및 최적화 시작")
    print("=" * 80)
    print()
    
    # 1. 매핑 파일에서 데이터 추출
    print("1. 매핑 파일에서 데이터 추출 중...")
    expected_mapping = extract_data_from_mapping()
    print(f"   ✓ {len(expected_mapping)}개 에이전트의 매핑 데이터 추출 완료")
    print()
    
    # 2. 모든 에이전트 디렉토리 찾기
    print("2. 에이전트 디렉토리 스캔 중...")
    agent_dirs = [d.name for d in AGENTS_DIR.iterdir() if d.is_dir() and d.name.startswith('agent')]
    agent_dirs.sort()
    print(f"   ✓ {len(agent_dirs)}개 에이전트 디렉토리 발견")
    print()
    
    # 3. 각 에이전트 검증
    print("3. 각 에이전트의 metadata.md 검증 중...")
    print("-" * 80)
    
    verification_results = {}
    total_errors = 0
    total_warnings = 0
    
    for agent_name in agent_dirs:
        result = verify_agent_metadata(agent_name)
        verification_results[agent_name] = result
        
        if result['exists']:
            status = "✓"
            if result['errors']:
                status = "✗"
                total_errors += len(result['errors'])
            if result['warnings']:
                total_warnings += len(result['warnings'])
            
            print(f"{status} {agent_name:30} | 데이터: {result['data_count']:3}개", end="")
            if result['errors']:
                print(f" | 오류: {len(result['errors'])}개", end="")
            if result['warnings']:
                print(f" | 경고: {len(result['warnings'])}개", end="")
            print()
        else:
            print(f"✗ {agent_name:30} | 파일 없음")
            total_errors += len(result['errors'])
    
    print("-" * 80)
    print()
    
    # 4. 매핑 일치성 검증
    print("4. 매핑 일치성 검증 중...")
    mapping_issues = []
    
    for agent_name, expected_data in expected_mapping.items():
        if agent_name not in verification_results:
            mapping_issues.append(f"{agent_name}: 에이전트 디렉토리가 없습니다")
            continue
        
        result = verification_results[agent_name]
        if not result['exists']:
            continue
        
        expected_numbers = {item['number'] for item in expected_data}
        actual_numbers = set(result['data_numbers'])
        
        missing = expected_numbers - actual_numbers
        extra = actual_numbers - expected_numbers
        
        if missing:
            mapping_issues.append(f"{agent_name}: 누락된 데이터 번호: {sorted(missing)}")
        if extra:
            mapping_issues.append(f"{agent_name}: 예상치 못한 데이터 번호: {sorted(extra)}")
    
    if mapping_issues:
        print(f"   ⚠️  {len(mapping_issues)}개 이슈 발견:")
        for issue in mapping_issues[:10]:  # 처음 10개만 표시
            print(f"      - {issue}")
        if len(mapping_issues) > 10:
            print(f"      ... 외 {len(mapping_issues) - 10}개")
    else:
        print("   ✓ 모든 매핑이 일치합니다")
    print()
    
    # 5. 중복 데이터 검사
    print("5. 중복 데이터 검사 중...")
    all_data_numbers = defaultdict(list)
    
    for agent_name, result in verification_results.items():
        if result['exists']:
            for num in result['data_numbers']:
                all_data_numbers[num].append(agent_name)
    
    duplicates = {num: agents for num, agents in all_data_numbers.items() if len(agents) > 1}
    
    if duplicates:
        print(f"   ⚠️  {len(duplicates)}개 데이터가 여러 에이전트에 중복 배치:")
        for num, agents in sorted(duplicates.items())[:10]:  # 처음 10개만 표시
            print(f"      - 데이터 {num}: {', '.join(agents)}")
        if len(duplicates) > 10:
            print(f"      ... 외 {len(duplicates) - 10}개")
    else:
        print("   ✓ 중복 데이터가 없습니다")
    print()
    
    # 6. 최종 리포트
    print("=" * 80)
    print("검증 결과 요약")
    print("=" * 80)
    print(f"총 에이전트 수: {len(agent_dirs)}")
    print(f"metadata.md 존재: {sum(1 for r in verification_results.values() if r['exists'])}개")
    print(f"총 데이터 항목 수: {sum(len(r['data_numbers']) for r in verification_results.values() if r['exists'])}개")
    print(f"오류: {total_errors}개")
    print(f"경고: {total_warnings}개")
    print(f"매핑 이슈: {len(mapping_issues)}개")
    print(f"중복 데이터: {len(duplicates)}개")
    print()
    
    if total_errors == 0 and len(mapping_issues) == 0 and len(duplicates) == 0:
        print("✓ 모든 검증을 통과했습니다!")
    else:
        print("⚠️  일부 이슈가 발견되었습니다. 위의 상세 내용을 확인하세요.")
    
    # 결과를 JSON 파일로 저장
    output_file = BASE_DIR / ".cursor" / "rules" / "verification-report.json"
    report = {
        'verification_results': verification_results,
        'mapping_issues': mapping_issues,
        'duplicates': {str(k): v for k, v in duplicates.items()},
        'summary': {
            'total_agents': len(agent_dirs),
            'total_errors': total_errors,
            'total_warnings': total_warnings,
            'mapping_issues_count': len(mapping_issues),
            'duplicates_count': len(duplicates)
        }
    }
    
    output_file.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding='utf-8')
    print(f"\n상세 리포트 저장: {output_file}")

if __name__ == "__main__":
    main()

