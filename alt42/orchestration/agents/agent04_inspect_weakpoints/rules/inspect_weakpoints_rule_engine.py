#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# File: agent04_inspect_weakpoints/rules/inspect_weakpoints_rule_engine.py
# 확장된 룰 엔진 - 취약점 분석 에이전트용
# 
# 추가 기능:
# - Operator 확장: ==, <=, in, matches 지원
# - 중첩 필드 접근: activity_patterns.main_category 같은 경로 지원
# - 액션 배열 형식 처리

import sys
import json
import re
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Any, Optional
import os

# 사용자 site-packages 경로 추가 (PyYAML이 사용자 디렉토리에 설치된 경우)
home_dir = os.path.expanduser('~')
possible_paths = [
    os.path.join(home_dir, '.local', 'lib', 'python3.10', 'site-packages'),
    os.path.join(home_dir, '.local', 'lib', 'python3', 'site-packages'),
    '/home/moodle/.local/lib/python3.10/site-packages',
    '/home/apache/.local/lib/python3.10/site-packages',
]

for path in possible_paths:
    if os.path.isdir(path) and path not in sys.path:
        sys.path.insert(0, path)

# yaml 모듈 import
try:
    import yaml
except ImportError:
    # 추가 경로에서 시도
    import site
    site.addsitedir(os.path.join(home_dir, '.local', 'lib', 'python3.10', 'site-packages'))
    import yaml

class InspectWeakpointsRuleEngine:
    """
    확장된 Rule Engine for Inspect Weakpoints Agent
    
    제공된 룰 형식을 완전히 지원:
    - 다양한 operator: ==, <=, <, >, >=, in, matches
    - 중첩 필드 접근: activity_patterns.main_category
    - 액션 배열 형식 처리
    """

    def __init__(self, rules_file_path: Optional[str] = None):
        """
        Initialize rule engine with rules file
        
        Args:
            rules_file_path (str, optional): Path to YAML rules file
        """
        if rules_file_path is None:
            rules_dir = Path(__file__).parent
            rules_file_path = rules_dir / 'rules.yaml'
        
        self.rules_file = Path(rules_file_path)
        self.rules_config = None
        self.rules = []
        self.default_rule = None
        
        self.load_rules()

    def load_rules(self):
        """Load and parse YAML rules file"""
        if not self.rules_file.exists():
            error_msg = f"Rules file not found: {self.rules_file} at {__file__}:{self._get_line()}"
            print(f"ERROR: {error_msg}", file=sys.stderr)
            raise FileNotFoundError(error_msg)
        
        with open(self.rules_file, 'r', encoding='utf-8') as f:
            self.rules_config = yaml.safe_load(f)
        
        self.rules = self.rules_config.get('rules', [])
        self.default_rule = self.rules_config.get('default_rule', {})
        
        # Sort rules by priority (highest first)
        self.rules.sort(key=lambda r: r.get('priority', 0), reverse=True)
        
        print(f"INFO: Loaded {len(self.rules)} rules from {self.rules_file} at {__file__}:{self._get_line()}",
              file=sys.stderr)

    def _get_line(self):
        """Get current line number for error reporting"""
        import inspect
        return inspect.currentframe().f_back.f_lineno

    def get_nested_field(self, data: Dict[str, Any], field_path: str) -> Any:
        """
        Get nested field value using dot notation
        
        Args:
            data: Dictionary to search
            field_path: Dot-separated path (e.g., "activity_patterns.main_category")
        
        Returns:
            Field value or None if not found
        """
        parts = field_path.split('.')
        value = data
        
        for part in parts:
            if isinstance(value, dict):
                value = value.get(part)
            elif isinstance(value, list) and part.isdigit():
                try:
                    value = value[int(part)]
                except (IndexError, ValueError):
                    return None
            else:
                return None
            
            if value is None:
                return None
        
        return value

    def evaluate_condition(self, condition: Dict[str, Any], context: Dict[str, Any]) -> bool:
        """
        Evaluate a single condition
        
        Args:
            condition: Condition dictionary with 'field', 'operator', 'value'
            context: Student context data
        
        Returns:
            True if condition matches, False otherwise
        """
        field = condition.get('field')
        operator = condition.get('operator', '==')
        expected_value = condition.get('value')
        
        if field is None:
            return False
        
        # Get field value (support nested fields)
        actual_value = self.get_nested_field(context, field)
        
        # Handle None values
        if actual_value is None:
            if operator == '!=':
                return expected_value is not None
            elif operator == '==':
                return expected_value is None
            else:
                return False
        
        # Evaluate based on operator
        if operator == '==':
            return actual_value == expected_value
        elif operator == '!=':
            return actual_value != expected_value
        elif operator == '<':
            return actual_value < expected_value
        elif operator == '<=':
            return actual_value <= expected_value
        elif operator == '>':
            return actual_value > expected_value
        elif operator == '>=':
            return actual_value >= expected_value
        elif operator == 'in':
            if isinstance(expected_value, list):
                return actual_value in expected_value
            return False
        elif operator == 'not_in':
            if isinstance(expected_value, list):
                return actual_value not in expected_value
            return True
        elif operator == 'contains':
            if isinstance(actual_value, str) and isinstance(expected_value, str):
                return expected_value in actual_value
            elif isinstance(actual_value, list):
                return expected_value in actual_value
            return False
        elif operator == 'matches':
            if isinstance(actual_value, str) and isinstance(expected_value, str):
                return bool(re.search(expected_value, actual_value))
            return False
        else:
            print(f"WARNING: Unknown operator '{operator}' at {__file__}:{self._get_line()}", file=sys.stderr)
            return False

    def evaluate_rule(self, rule: Dict[str, Any], context: Dict[str, Any]) -> bool:
        """
        Evaluate all conditions for a rule
        
        Args:
            rule: Rule dictionary
            context: Student context data
        
        Returns:
            True if all conditions match, False otherwise
        """
        conditions = rule.get('conditions', [])
        
        if not conditions:
            return True
        
        for condition in conditions:
            if not self.evaluate_condition(condition, context):
                return False
        
        return True

    def substitute_variables(self, text: str, context: Dict[str, Any]) -> str:
        """
        Substitute variables in text using context
        
        Args:
            text: Text with variables like {{field_name}}
            context: Student context data
        
        Returns:
            Text with variables substituted
        """
        if not isinstance(text, str):
            return text
        
        # Find all {{variable}} patterns
        pattern = r'\{\{([^}]+)\}\}'
        matches = re.findall(pattern, text)
        
        result = text
        for match in matches:
            field_path = match.strip()
            value = self.get_nested_field(context, field_path)
            
            if value is not None:
                result = result.replace('{{' + match + '}}', str(value))
            else:
                # Variable not found, keep original
                print(f"WARNING: Variable '{{{{field_path}}}}' not found in context at {__file__}:{self._get_line()}", file=sys.stderr)
        
        return result

    def process_actions(self, actions: List[Any], context: Dict[str, Any]) -> List[Any]:
        """
        Process actions and substitute variables
        
        Args:
            actions: List of actions
            context: Student context data
        
        Returns:
            Processed actions list
        """
        processed_actions = []
        
        for action in actions:
            if isinstance(action, str):
                # Substitute variables in string actions
                processed_action = self.substitute_variables(action, context)
                processed_actions.append(processed_action)
            elif isinstance(action, dict):
                # Process dictionary actions
                processed_action = {}
                for key, value in action.items():
                    if isinstance(value, str):
                        processed_action[key] = self.substitute_variables(value, context)
                    else:
                        processed_action[key] = value
                processed_actions.append(processed_action)
            else:
                processed_actions.append(action)
        
        return processed_actions

    def evaluate(self, context: Dict[str, Any]) -> Dict[str, Any]:
        """
        Evaluate rules against context and return decision
        
        Args:
            context: Student context data (must include student_id)
        
        Returns:
            Decision dictionary with matched rule and actions
        """
        if 'student_id' not in context:
            error_msg = f"Missing required field: student_id at {__file__}:{self._get_line()}"
            print(f"ERROR: {error_msg}", file=sys.stderr)
            raise ValueError(error_msg)
        
        # Try to match rules in priority order
        for rule in self.rules:
            if self.evaluate_rule(rule, context):
                # Rule matched
                actions = rule.get('action', [])
                # 변수 치환만 수행 (형식은 그대로 유지 - Agent01과 동일)
                processed_actions = self.process_actions(actions, context)
                
                decision = {
                    'rule_id': rule.get('rule_id', 'unknown'),
                    'priority': rule.get('priority', 0),
                    'confidence': rule.get('confidence', 1.0),
                    'actions': processed_actions,
                    'matched_at': datetime.now().isoformat(),
                    'execution_time': 0  # Will be set by PHP wrapper
                }
                
                print(f"INFO: Matched rule '{decision['rule_id']}' at {__file__}:{self._get_line()}", file=sys.stderr)
                print(f"INFO: Returning {len(processed_actions)} actions at {__file__}:{self._get_line()}", file=sys.stderr)
                return decision
        
        # No rule matched, use default rule if available
        if self.default_rule:
            actions = self.default_rule.get('action', [])
            processed_actions = self.process_actions(actions, context)
            
            decision = {
                'rule_id': 'default',
                'priority': 0,
                'confidence': 0.5,
                'actions': processed_actions,
                'matched_at': datetime.now().isoformat(),
                'execution_time': 0
            }
            
            print(f"INFO: Using default rule at {__file__}:{self._get_line()}", file=sys.stderr)
            return decision
        
        # No rule matched and no default rule
        decision = {
            'rule_id': None,
            'priority': 0,
            'confidence': 0.0,
            'actions': [],
            'matched_at': datetime.now().isoformat(),
            'execution_time': 0
        }
        
        print(f"WARNING: No rule matched and no default rule at {__file__}:{self._get_line()}", file=sys.stderr)
        return decision


def main():
    """
    Main entry point for command-line usage
    Usage: python inspect_weakpoints_rule_engine.py <json_context> <rules_file_path>
    """
    if len(sys.argv) < 3:
        error_msg = f"Usage: {sys.argv[0]} <json_context> <rules_file_path> at {__file__}:{sys.argv.__len__()}"
        print(f"ERROR: {error_msg}", file=sys.stderr)
        sys.exit(1)
    
    json_context = sys.argv[1]
    rules_file_path = sys.argv[2]
    
    try:
        context = json.loads(json_context)
    except json.JSONDecodeError as e:
        error_msg = f"Invalid JSON context: {e} at {__file__}:{sys.argv.__len__()}"
        print(f"ERROR: {error_msg}", file=sys.stderr)
        sys.exit(1)
    
    try:
        engine = InspectWeakpointsRuleEngine(rules_file_path)
        decision = engine.evaluate(context)
        
        # Output JSON result
        print(json.dumps(decision, ensure_ascii=False, indent=2))
        
    except Exception as e:
        error_msg = f"Rule engine error: {e} at {__file__}:{sys.argv.__len__()}"
        print(f"ERROR: {error_msg}", file=sys.stderr)
        import traceback
        traceback.print_exc()
        sys.exit(1)


if __name__ == '__main__':
    main()

