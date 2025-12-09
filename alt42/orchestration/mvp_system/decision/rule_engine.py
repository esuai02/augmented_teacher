#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# File: mvp_system/decision/rule_engine.py (Line 1)
# Mathking Agentic MVP System - Rule-Based Decision Engine
#
# Purpose: Evaluate metrics against YAML rules and generate intervention decisions
# Input: Metrics JSON (from Sensing layer)
# Output: Decision JSON matching contracts/schemas/decision.schema.json
# Reference: decision/rules/calm_break_rules.yaml

import sys
import json
import yaml
import os
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Any, Optional

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent))


class RuleEngine:
    """
    Rule-Based Decision Engine

    Evaluates student metrics against YAML-defined rules to generate
    intervention decisions with explainability and confidence scoring.
    """

    def __init__(self, rules_file_path: Optional[str] = None):
        """
        Initialize rule engine with rules file

        Args:
            rules_file_path (str, optional): Path to YAML rules file.
                Defaults to calm_break_rules.yaml
        """
        if rules_file_path is None:
            # Default path: decision/rules/calm_break_rules.yaml
            rules_dir = Path(__file__).parent / 'rules'
            rules_file_path = rules_dir / 'calm_break_rules.yaml'

        self.rules_file = Path(rules_file_path)
        self.rules_config = None
        self.rules = []
        self.default_rule = None

        self.load_rules()

    def load_rules(self):
        """
        Load and parse YAML rules file

        Raises:
            FileNotFoundError: If rules file doesn't exist
            yaml.YAMLError: If YAML parsing fails
        """
        if not self.rules_file.exists():
            error_msg = f"Rules file not found: {self.rules_file} at {__file__}:61"
            print(f"ERROR: {error_msg}", file=sys.stderr)
            raise FileNotFoundError(error_msg)

        with open(self.rules_file, 'r', encoding='utf-8') as f:
            self.rules_config = yaml.safe_load(f)

        # Extract rules list
        self.rules = self.rules_config.get('rules', [])
        self.default_rule = self.rules_config.get('default_rule', {})

        # Sort rules by priority (highest first)
        self.rules.sort(key=lambda r: r.get('priority', 0), reverse=True)

        print(f"INFO: Loaded {len(self.rules)} rules from {self.rules_file} at {__file__}:76",
              file=sys.stderr)

    def evaluate_condition(self, condition: Dict[str, Any], metrics: Dict[str, Any]) -> bool:
        """
        Evaluate a single condition against metrics data

        Args:
            condition (dict): Condition definition with field, operator, value
            metrics (dict): Student metrics data

        Returns:
            bool: True if condition matches, False otherwise

        Example:
            condition = {"field": "calm_score", "operator": "less_than", "value": 75}
            metrics = {"calm_score": 70.5}
            result = evaluate_condition(condition, metrics)  # True
        """
        field = condition.get('field')
        operator = condition.get('operator')
        threshold_value = condition.get('value')

        # Get field value from metrics
        field_value = metrics.get(field)

        if field_value is None:
            print(f"WARNING: Field '{field}' not found in metrics at {__file__}:104",
                  file=sys.stderr)
            return False

        # Evaluate operator
        if operator == 'less_than':
            return field_value < threshold_value
        elif operator == 'less_than_or_equal':
            return field_value <= threshold_value
        elif operator == 'greater_than':
            return field_value > threshold_value
        elif operator == 'greater_than_or_equal':
            return field_value >= threshold_value
        elif operator == 'equal':
            return field_value == threshold_value
        elif operator == 'not_equal':
            return field_value != threshold_value
        elif operator == 'contains':
            # For string fields
            return str(threshold_value).lower() in str(field_value).lower()
        elif operator == 'not_contains':
            return str(threshold_value).lower() not in str(field_value).lower()
        else:
            print(f"WARNING: Unknown operator '{operator}' at {__file__}:128",
                  file=sys.stderr)
            return False

    def evaluate_rule(self, rule: Dict[str, Any], metrics: Dict[str, Any]) -> bool:
        """
        Evaluate all conditions of a rule (AND logic)

        Args:
            rule (dict): Rule definition with conditions list
            metrics (dict): Student metrics data

        Returns:
            bool: True if all conditions match, False otherwise
        """
        conditions = rule.get('conditions', [])

        # Empty conditions = always match
        if not conditions:
            return True

        # All conditions must be True (AND logic)
        for condition in conditions:
            if not self.evaluate_condition(condition, metrics):
                return False

        return True

    def decide(self, metrics: Dict[str, Any]) -> Dict[str, Any]:
        """
        Make intervention decision based on metrics and rules

        Args:
            metrics (dict): Student metrics from Sensing layer
                Required fields: student_id, calm_score, timestamp

        Returns:
            dict: Decision object matching decision.schema.json

        Raises:
            ValueError: If required fields missing

        Example:
            >>> engine = RuleEngine()
            >>> metrics = {
            ...     'student_id': 123,
            ...     'calm_score': 70.5,
            ...     'timestamp': '2025-11-02T10:30:00Z'
            ... }
            >>> decision = engine.decide(metrics)
            >>> decision['action']
            'micro_break'
        """
        # Validate required fields
        required_fields = ['student_id', 'calm_score']
        for field in required_fields:
            if field not in metrics:
                error_msg = f"Missing required field: {field} at {__file__}:187"
                print(f"ERROR: {error_msg}", file=sys.stderr)
                raise ValueError(error_msg)

        student_id = metrics['student_id']
        calm_score = metrics['calm_score']

        print(f"INFO: Evaluating decision for student_id={student_id}, calm_score={calm_score} at {__file__}:194",
              file=sys.stderr)

        # Evaluate rules in priority order (first match wins)
        matched_rule = None
        for rule in self.rules:
            if self.evaluate_rule(rule, metrics):
                matched_rule = rule
                print(f"INFO: Rule matched: {rule['rule_id']} at {__file__}:201",
                      file=sys.stderr)
                break

        # Use default rule if no match
        if matched_rule is None:
            matched_rule = self.default_rule
            print(f"INFO: No rule matched, using default rule at {__file__}:208",
                  file=sys.stderr)

        # Build decision output
        action = matched_rule.get('action', 'none')
        params = matched_rule.get('params', {})
        confidence = matched_rule.get('confidence', 0.5)
        rationale_template = matched_rule.get('rationale', 'Decision made by rule {rule_id}')
        rule_id = matched_rule.get('rule_id', 'unknown')

        # Format rationale with actual values
        # Create safe dict for formatting (avoid duplicate keys)
        format_vars = {
            'calm_score': calm_score,
            'rule_id': rule_id,
            'student_id': student_id
        }
        # Add other metrics that aren't already in format_vars
        for key, value in metrics.items():
            if key not in format_vars:
                format_vars[key] = value

        rationale = rationale_template.format(**format_vars)

        # Build trace data for debugging/auditing
        trace_data = {
            'rules_evaluated': len(self.rules),
            'matched_rule_id': rule_id,
            'matched_rule_priority': matched_rule.get('priority', 0),
            'metrics_snapshot': {
                'calm_score': calm_score,
                'student_id': student_id
            },
            'evaluation_timestamp': datetime.utcnow().isoformat() + 'Z'
        }

        # Construct decision object matching decision.schema.json
        decision = {
            'student_id': student_id,
            'action': action,
            'params': json.dumps(params),  # Serialize params as JSON string
            'confidence': round(confidence, 2),
            'rationale': rationale,
            'rule_id': rule_id,
            'trace_data': json.dumps(trace_data),
            'timestamp': metrics.get('timestamp', datetime.utcnow().isoformat() + 'Z')
        }

        print(f"INFO: Decision made: action={action}, confidence={confidence} at {__file__}:251",
              file=sys.stderr)

        return decision

    def get_rules_summary(self) -> Dict[str, Any]:
        """
        Get summary of loaded rules for debugging

        Returns:
            dict: Rules configuration summary
        """
        return {
            'rules_file': str(self.rules_file),
            'version': self.rules_config.get('version', 'unknown'),
            'scenario': self.rules_config.get('scenario', 'unknown'),
            'rules_count': len(self.rules),
            'rules': [
                {
                    'rule_id': r.get('rule_id'),
                    'priority': r.get('priority'),
                    'action': r.get('action'),
                    'description': r.get('description')
                }
                for r in self.rules
            ],
            'default_action': self.default_rule.get('action', 'none')
        }


def main():
    """
    CLI entry point for rule engine

    Usage:
        python rule_engine.py '{"student_id": 123, "calm_score": 70.5, "timestamp": "2025-11-02T10:30:00Z"}'

    Outputs:
        JSON string to stdout matching decision.schema.json
    """
    if len(sys.argv) < 2:
        print("ERROR: Missing input data at " + __file__ + ":295", file=sys.stderr)
        print("Usage: python rule_engine.py '<metrics_json>'", file=sys.stderr)
        sys.exit(1)

    try:
        # Parse input JSON
        metrics = json.loads(sys.argv[1])

        # Make decision
        engine = RuleEngine()
        decision = engine.decide(metrics)

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


# =============================================================================
# Usage Examples
# =============================================================================
#
# Command line:
# python rule_engine.py '{"student_id": 123, "calm_score": 70.5, "timestamp": "2025-11-02T10:30:00Z"}'
#
# From PHP:
# $result = shell_exec("python3 rule_engine.py '" . json_encode($metrics) . "'");
# $decision = json_decode($result, true);
#
# =============================================================================
