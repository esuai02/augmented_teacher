#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# File: agent01_onboarding/rules/onboarding_rule_engine.py
# 확장된 룰 엔진 - 온보딩 에이전트용
# 
# 추가 기능:
# - Operator 확장: ==, <=, in, matches 지원
# - 중첩 필드 접근: goals.long_term 같은 경로 지원
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

class OnboardingRuleEngine:
    """
    확장된 Rule Engine for Onboarding Agent
    
    제공된 룰 형식을 완전히 지원:
    - 다양한 operator: ==, <=, <, >, >=, in, matches
    - 중첩 필드 접근: goals.long_term
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
            rules_file_path = rules_dir / 'agent01_onboarding_rules.yaml'
        
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
            field_path: Dot-separated path (e.g., "goals.long_term")
        
        Returns:
            Field value or None if not found
        
        Example:
            data = {"goals": {"long_term": "경시대회 준비"}}
            value = get_nested_field(data, "goals.long_term")  # "경시대회 준비"
        """
        parts = field_path.split('.')
        current = data
        
        for part in parts:
            if isinstance(current, dict):
                current = current.get(part)
            elif isinstance(current, list) and part.isdigit():
                idx = int(part)
                if 0 <= idx < len(current):
                    current = current[idx]
                else:
                    return None
            else:
                return None
            
            if current is None:
                return None
        
        return current

    def evaluate_condition(self, condition: Dict[str, Any], context: Dict[str, Any]) -> bool:
        """
        Evaluate a single condition against context data
        
        Args:
            condition: Condition definition with field, operator, value
            context: Student context data
        
        Returns:
            bool: True if condition matches
        
        Supported operators:
            ==, equal: Equality check
            !=, not_equal: Inequality check
            <, less_than: Less than
            <=, less_than_or_equal: Less than or equal
            >, greater_than: Greater than
            >=, greater_than_or_equal: Greater than or equal
            in: Value in list
            matches: Regular expression match
            contains: String contains substring
        """
        field = condition.get('field')
        operator = condition.get('operator')
        threshold_value = condition.get('value')
        
        if not field or operator is None:
            print(f"WARNING: Invalid condition format at {__file__}:{self._get_line()}",
                  file=sys.stderr)
            return False
        
        # Get field value (support nested fields)
        if '.' in field:
            field_value = self.get_nested_field(context, field)
        else:
            field_value = context.get(field)
        
        if field_value is None:
            # Check if None is explicitly expected
            if operator in ['==', 'equal'] and threshold_value is None:
                return True
            print(f"WARNING: Field '{field}' not found in context at {__file__}:{self._get_line()}",
                  file=sys.stderr)
            return False
        
        # Normalize operator names
        operator_lower = str(operator).lower()
        
        # Evaluate operator
        try:
            if operator in ['==', 'equal']:
                return field_value == threshold_value
            
            elif operator in ['!=', 'not_equal']:
                return field_value != threshold_value
            
            elif operator in ['<', 'less_than']:
                return field_value < threshold_value
            
            elif operator in ['<=', 'less_than_or_equal']:
                return field_value <= threshold_value
            
            elif operator in ['>', 'greater_than']:
                return field_value > threshold_value
            
            elif operator in ['>=', 'greater_than_or_equal']:
                return field_value >= threshold_value
            
            elif operator == 'in':
                # Check if field_value is in threshold_value (list)
                if not isinstance(threshold_value, list):
                    threshold_value = [threshold_value]
                return field_value in threshold_value
            
            elif operator == 'matches':
                # Regular expression match
                if not isinstance(threshold_value, str):
                    threshold_value = str(threshold_value)
                pattern = re.compile(threshold_value)
                return bool(pattern.search(str(field_value)))
            
            elif operator == 'contains':
                # String contains substring
                return str(threshold_value).lower() in str(field_value).lower()
            
            elif operator == 'not_contains':
                return str(threshold_value).lower() not in str(field_value).lower()
            
            else:
                print(f"WARNING: Unknown operator '{operator}' at {__file__}:{self._get_line()}",
                      file=sys.stderr)
                return False
        
        except Exception as e:
            print(f"ERROR: Condition evaluation failed: {e} at {__file__}:{self._get_line()}",
                  file=sys.stderr)
            return False

    def evaluate_rule(self, rule: Dict[str, Any], context: Dict[str, Any]) -> bool:
        """
        Evaluate all conditions of a rule (AND logic with OR support)
        
        Args:
            rule: Rule definition with conditions list
            context: Student context data
        
        Returns:
            bool: True if all conditions match
        """
        conditions = rule.get('conditions', [])
        
        if not conditions:
            return True
        
        # Process conditions with OR support
        for condition in conditions:
            # Check if this is an OR condition
            if isinstance(condition, dict) and 'OR' in condition:
                # OR condition: at least one must be True
                or_conditions = condition['OR']
                or_result = False
                for or_cond in or_conditions:
                    if self.evaluate_condition(or_cond, context):
                        or_result = True
                        print(f"INFO: OR condition matched: {or_cond.get('field', 'unknown')} at {__file__}:{self._get_line()}", 
                              file=sys.stderr)
                        break
                if not or_result:
                    print(f"INFO: OR condition failed - none of {len(or_conditions)} conditions matched at {__file__}:{self._get_line()}", 
                          file=sys.stderr)
                    return False  # OR condition failed
            else:
                # Regular AND condition
                if not self.evaluate_condition(condition, context):
                    print(f"INFO: Condition failed: {condition.get('field', 'unknown')} {condition.get('operator', 'unknown')} {condition.get('value', 'unknown')} at {__file__}:{self._get_line()}", 
                          file=sys.stderr)
                    return False
        
        return True

    def parse_action(self, action_item: Any) -> Dict[str, Any]:
        """
        Parse action item (supports string or dict format)
        
        Args:
            action_item: Action string like "initialize_support_mode: true"
                        or dict like {"type": "initialize", "value": true}
        
        Returns:
            Parsed action dictionary
        """
        if isinstance(action_item, dict):
            return action_item
        
        if isinstance(action_item, str):
            # Parse string format: "key: value"
            if ':' in action_item:
                parts = action_item.split(':', 1)
                key = parts[0].strip()
                value_str = parts[1].strip()
                
                # Try to parse value
                value = value_str
                if value_str.lower() == 'true':
                    value = True
                elif value_str.lower() == 'false':
                    value = False
                elif value_str.lower() == 'null' or value_str.lower() == 'none':
                    value = None
                elif value_str.startswith('"') and value_str.endswith('"'):
                    value = value_str[1:-1]
                elif value_str.startswith("'") and value_str.endswith("'"):
                    value = value_str[1:-1]
                elif value_str.isdigit():
                    value = int(value_str)
                elif '.' in value_str and value_str.replace('.', '').isdigit():
                    value = float(value_str)
                
                return {key: value}
            
            # Simple string action
            return {'action': action_item}
        
        return {'action': str(action_item)}

    def decide(self, context: Dict[str, Any]) -> Dict[str, Any]:
        """
        Make decision based on context and rules
        
        Args:
            context: Student context data (must include student_id)
        
        Returns:
            dict: Decision object with actions and metadata
        """
        # Validate required fields
        if 'student_id' not in context:
            error_msg = f"Missing required field: student_id at {__file__}:{self._get_line()}"
            print(f"ERROR: {error_msg}", file=sys.stderr)
            raise ValueError(error_msg)
        
        student_id = context['student_id']
        
        print(f"INFO: Evaluating decision for student_id={student_id} at {__file__}:{self._get_line()}",
              file=sys.stderr)
        
        # Evaluate rules in priority order (first match wins)
        matched_rule = None
        for rule in self.rules:
            if self.evaluate_rule(rule, context):
                matched_rule = rule
                print(f"INFO: Rule matched: {rule['rule_id']} at {__file__}:{self._get_line()}",
                      file=sys.stderr)
                break
        
        # Use default rule if no match
        if matched_rule is None:
            matched_rule = self.default_rule
            print(f"INFO: No rule matched, using default rule at {__file__}:{self._get_line()}",
                  file=sys.stderr)
        
        # Ensure matched_rule is a valid dict (not None or empty)
        if not matched_rule or not isinstance(matched_rule, dict):
            # Fallback to minimal default rule
            matched_rule = {
                'rule_id': 'default',
                'action': ['display_message: 온보딩 정보를 분석 중입니다. 추가 정보가 필요할 수 있습니다.'],
                'confidence': 0.5,
                'rationale': '기본 루틴 적용 - 조건 불일치 시 사용',
                'description': '기본 루틴'
            }
            print(f"WARNING: Default rule is invalid, using fallback rule at {__file__}:{self._get_line()}",
                  file=sys.stderr)
        
        # Parse actions
        actions = []
        action_raw = matched_rule.get('action', [])
        
        if isinstance(action_raw, list):
            # Array format: ["action1", "action2"]
            for item in action_raw:
                parsed = self.parse_action(item)
                actions.append(parsed)
        elif isinstance(action_raw, dict):
            # Dict format: {"type": "action", "params": {}}
            actions.append(action_raw)
        elif action_raw:
            # Single string
            actions.append(self.parse_action(action_raw))
        
        # Build decision output
        confidence = matched_rule.get('confidence', 0.5)
        rationale_template = matched_rule.get('rationale', 'Decision made by rule {rule_id}')
        rule_id = matched_rule.get('rule_id', 'unknown')
        
        # Format rationale with actual values
        format_vars = {
            'rule_id': rule_id,
            'student_id': student_id
        }
        # Add context values for formatting
        for key, value in context.items():
            if key not in format_vars:
                format_vars[key] = value
        
        try:
            rationale = rationale_template.format(**format_vars)
        except KeyError as e:
            rationale = rationale_template.replace('{' + str(e).strip("'") + '}', 'N/A')
        
        # Build trace data
        trace_data = {
            'rules_evaluated': len(self.rules),
            'matched_rule_id': rule_id,
            'matched_rule_priority': matched_rule.get('priority', 0),
            'context_snapshot': {k: v for k, v in context.items() if k != 'student_id'},
            'evaluation_timestamp': datetime.utcnow().isoformat() + 'Z'
        }
        
        # Construct decision object
        decision = {
            'student_id': student_id,
            'rule_id': rule_id,
            'actions': actions,
            'confidence': round(confidence, 2),
            'rationale': rationale,
            'description': matched_rule.get('description', ''),
            'trace_data': trace_data,
            'timestamp': context.get('timestamp', datetime.utcnow().isoformat() + 'Z')
        }
        
        print(f"INFO: Decision made: rule_id={rule_id}, confidence={confidence} at {__file__}:{self._get_line()}",
              file=sys.stderr)
        
        return decision

    def get_rules_summary(self) -> Dict[str, Any]:
        """Get summary of loaded rules for debugging"""
        return {
            'rules_file': str(self.rules_file),
            'version': self.rules_config.get('version', 'unknown'),
            'scenario': self.rules_config.get('scenario', 'unknown'),
            'rules_count': len(self.rules),
            'rules': [
                {
                    'rule_id': r.get('rule_id'),
                    'priority': r.get('priority'),
                    'description': r.get('description')
                }
                for r in self.rules
            ]
        }


def main():
    """CLI entry point"""
    if len(sys.argv) < 2:
        print("ERROR: Missing input data at " + __file__ + ":295", file=sys.stderr)
        print("Usage: python onboarding_rule_engine.py '<context_json>' [rules_file.yaml]", file=sys.stderr)
        sys.exit(1)
    
    try:
        # Parse input JSON
        context = json.loads(sys.argv[1])
        
        # Optional rules file path
        rules_file = sys.argv[2] if len(sys.argv) > 2 else None
        
        # Make decision
        engine = OnboardingRuleEngine(rules_file)
        decision = engine.decide(context)
        
        # Output JSON to stdout
        print(json.dumps(decision, ensure_ascii=False, indent=2))
    
    except json.JSONDecodeError as e:
        print(f"ERROR: Invalid JSON input at {__file__}:311 - {e}", file=sys.stderr)
        sys.exit(1)
    except ValueError as e:
        print(f"ERROR: Validation error at {__file__}:314 - {e}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"ERROR: Unexpected error at {__file__}:317 - {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()

