#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# File: agent01_onboarding/rules/check_rule_conflicts.py
# 룰 충돌 체크 스크립트

import yaml
from pathlib import Path
from typing import Dict, List, Any, Set, Tuple

def load_rules(rules_file_path: str) -> Dict[str, Any]:
    """룰 파일 로드"""
    with open(rules_file_path, 'r', encoding='utf-8') as f:
        return yaml.safe_load(f)

def get_rule_conditions_key(rule: Dict[str, Any]) -> str:
    """룰의 조건을 문자열 키로 변환 (비교용)"""
    conditions = rule.get('conditions', [])
    key_parts = []
    for cond in sorted(conditions, key=lambda x: x.get('field', '')):
        field = cond.get('field', '')
        operator = cond.get('operator', '')
        value = cond.get('value', '')
        key_parts.append(f"{field}:{operator}:{value}")
    return "|".join(key_parts)

def check_overlapping_conditions(rule1: Dict[str, Any], rule2: Dict[str, Any]) -> bool:
    """두 룰의 조건이 겹치는지 확인"""
    cond1_list = rule1.get('conditions', [])
    cond2_list = rule2.get('conditions', [])
    
    # 같은 필드에 대한 조건이 있는지 확인
    fields1 = {c.get('field') for c in cond1_list}
    fields2 = {c.get('field') for c in cond2_list}
    
    common_fields = fields1 & fields2
    if not common_fields:
        return False
    
    # 공통 필드에 대해 조건이 충돌하는지 확인
    for field in common_fields:
        cond1 = next((c for c in cond1_list if c.get('field') == field), None)
        cond2 = next((c for c in cond2_list if c.get('field') == field), None)
        
        if cond1 and cond2:
            op1 = cond1.get('operator')
            val1 = cond1.get('value')
            op2 = cond2.get('operator')
            val2 = cond2.get('value')
            
            # 같은 필드, 같은 값, 같은 연산자는 충돌 가능
            if op1 == op2 and val1 == val2:
                return True
            
            # 포함 관계 확인 (in 연산자와 == 연산자)
            if op1 == 'in' and op2 == '==' and val2 in val1:
                return True
            if op2 == 'in' and op1 == '==' and val1 in val2:
                return True
            
            # 범위 겹침 확인 (<=, >=, <, >)
            if op1 in ['<=', '<'] and op2 in ['<=', '<']:
                # 같은 방향이면 겹칠 수 있음
                if isinstance(val1, (int, float)) and isinstance(val2, (int, float)):
                    return True
    
    return False

def check_subset_relationship(rule1: Dict[str, Any], rule2: Dict[str, Any]) -> Tuple[bool, bool]:
    """
    rule1이 rule2의 더 구체적인 버전인지 확인
    Returns: (rule1_is_subset, rule2_is_subset)
    """
    cond1_list = rule1.get('conditions', [])
    cond2_list = rule2.get('conditions', [])
    
    # rule1의 모든 조건이 rule2에 포함되거나 더 구체적인지 확인
    rule1_subset = True
    for cond1 in cond1_list:
        field1 = cond1.get('field')
        op1 = cond1.get('operator')
        val1 = cond1.get('value')
        
        cond2 = next((c for c in cond2_list if c.get('field') == field1), None)
        if not cond2:
            rule1_subset = False
            break
        
        op2 = cond2.get('operator')
        val2 = cond2.get('value')
        
        # == 연산자가 in 연산자보다 구체적
        if op1 == '==' and op2 == 'in':
            if val1 not in val2:
                rule1_subset = False
                break
        elif op1 == op2 and val1 != val2:
            rule1_subset = False
            break
    
    # rule2의 모든 조건이 rule1에 포함되거나 더 구체적인지 확인
    rule2_subset = True
    for cond2 in cond2_list:
        field2 = cond2.get('field')
        op2 = cond2.get('operator')
        val2 = cond2.get('value')
        
        cond1 = next((c for c in cond1_list if c.get('field') == field2), None)
        if not cond1:
            rule2_subset = False
            break
        
        op1 = cond1.get('operator')
        val1 = cond1.get('value')
        
        # == 연산자가 in 연산자보다 구체적
        if op2 == '==' and op1 == 'in':
            if val2 not in val1:
                rule2_subset = False
                break
        elif op1 == op2 and val1 != val2:
            rule2_subset = False
            break
    
    return rule1_subset, rule2_subset

def check_rule_conflicts(rules_file_path: str):
    """룰 충돌 체크"""
    config = load_rules(rules_file_path)
    rules = config.get('rules', [])
    
    print(f"총 {len(rules)}개의 룰을 분석합니다.\n")
    
    conflicts = []
    warnings = []
    
    # 모든 룰 쌍 비교
    for i, rule1 in enumerate(rules):
        for j, rule2 in enumerate(rules[i+1:], start=i+1):
            rule1_id = rule1.get('rule_id', f'Unknown{i}')
            rule2_id = rule2.get('rule_id', f'Unknown{j}')
            rule1_priority = rule1.get('priority', 0)
            rule2_priority = rule2.get('priority', 0)
            
            # 조건 겹침 확인
            if check_overlapping_conditions(rule1, rule2):
                # 더 구체적인 룰 확인
                rule1_subset, rule2_subset = check_subset_relationship(rule1, rule2)
                
                if rule1_subset and not rule2_subset:
                    # rule1이 더 구체적
                    if rule1_priority <= rule2_priority:
                        warnings.append({
                            'type': 'priority_warning',
                            'rule1': rule1_id,
                            'rule2': rule2_id,
                            'message': f"{rule1_id}가 {rule2_id}보다 구체적이지만 우선순위가 같거나 낮습니다. ({rule1_priority} <= {rule2_priority})"
                        })
                elif rule2_subset and not rule1_subset:
                    # rule2가 더 구체적
                    if rule2_priority <= rule1_priority:
                        warnings.append({
                            'type': 'priority_warning',
                            'rule1': rule1_id,
                            'rule2': rule2_id,
                            'message': f"{rule2_id}가 {rule1_id}보다 구체적이지만 우선순위가 같거나 낮습니다. ({rule2_priority} <= {rule1_priority})"
                        })
                elif rule1_subset and rule2_subset:
                    # 같은 조건
                    conflicts.append({
                        'type': 'duplicate_conditions',
                        'rule1': rule1_id,
                        'rule2': rule2_id,
                        'message': f"{rule1_id}와 {rule2_id}가 동일한 조건을 가지고 있습니다."
                    })
                else:
                    # 조건이 겹치지만 포함 관계가 명확하지 않음
                    warnings.append({
                        'type': 'overlapping_conditions',
                        'rule1': rule1_id,
                        'rule2': rule2_id,
                        'message': f"{rule1_id}와 {rule2_id}의 조건이 겹칩니다. 확인이 필요합니다."
                    })
    
    # 결과 출력
    print("=" * 80)
    print("룰 충돌 체크 결과")
    print("=" * 80)
    
    if conflicts:
        print(f"\n❌ 충돌 발견: {len(conflicts)}개")
        for conflict in conflicts:
            print(f"  - {conflict['message']}")
    else:
        print("\n✅ 중복 조건 충돌 없음")
    
    if warnings:
        print(f"\n⚠️  경고: {len(warnings)}개")
        for warning in warnings:
            print(f"  - {warning['message']}")
    else:
        print("\n✅ 우선순위 경고 없음")
    
    # 우선순위별 정렬 확인
    print("\n" + "=" * 80)
    print("우선순위 확인")
    print("=" * 80)
    
    sorted_rules = sorted(rules, key=lambda r: r.get('priority', 0), reverse=True)
    print("\n상위 10개 룰 (우선순위 순):")
    for i, rule in enumerate(sorted_rules[:10], 1):
        rule_id = rule.get('rule_id', 'Unknown')
        priority = rule.get('priority', 0)
        desc = rule.get('description', '')[:50]
        print(f"  {i:2d}. [{priority:3d}] {rule_id:30s} - {desc}")
    
    return len(conflicts) == 0 and len(warnings) == 0

if __name__ == '__main__':
    rules_file = Path(__file__).parent / 'agent01_onboarding_rules.yaml'
    success = check_rule_conflicts(str(rules_file))
    exit(0 if success else 1)

