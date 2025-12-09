#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# File: mvp_system/decision/tests/rule_engine.test.py (Line 1)
# Mathking Agentic MVP System - Rule Engine Unit Tests
#
# Purpose: Test rule-based decision logic and YAML rule parsing
# Run: python -m pytest rule_engine.test.py -v
# Or: python rule_engine.test.py

import unittest
import sys
import json
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from rule_engine import RuleEngine


class TestRuleEngine(unittest.TestCase):
    """
    Test suite for RuleEngine class
    """

    @classmethod
    def setUpClass(cls):
        """
        Set up test fixtures once for all tests
        """
        # Use actual rules file
        rules_path = Path(__file__).parent.parent / 'rules' / 'calm_break_rules.yaml'

        if not rules_path.exists():
            print(f"WARNING: Rules file not found at {rules_path} at {__file__}:33", file=sys.stderr)
            print("Some tests may fail without actual rules file", file=sys.stderr)

        cls.engine = RuleEngine(str(rules_path) if rules_path.exists() else None)

    def test_01_rules_loading(self):
        """
        Test: YAML rules file loading and parsing
        """
        print(f"\nTest 01: Rules loading at {__file__}:43")

        # Verify rules were loaded
        self.assertGreater(len(self.engine.rules), 0,
                           "No rules loaded from YAML file")

        # Verify rules have required fields
        for rule in self.engine.rules:
            self.assertIn('rule_id', rule)
            self.assertIn('action', rule)
            self.assertIn('priority', rule)
            self.assertIn('conditions', rule)

        # Verify rules are sorted by priority (descending)
        priorities = [r.get('priority', 0) for r in self.engine.rules]
        self.assertEqual(priorities, sorted(priorities, reverse=True),
                         "Rules should be sorted by priority (highest first)")

        print(f"✅ Loaded {len(self.engine.rules)} rules")
        print(f"   Highest priority: {priorities[0]}")
        print(f"   Lowest priority: {priorities[-1]}")

    def test_02_critical_calm_break(self):
        """
        Test: Critical low calm score triggers immediate break
        Expected: action='micro_break', high confidence
        """
        print(f"\nTest 02: Critical calm score at {__file__}:72")

        metrics = {
            'student_id': 123,
            'calm_score': 55.0,  # Very low (< 60)
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Assertions
        self.assertEqual(decision['student_id'], 123)
        self.assertEqual(decision['action'], 'micro_break',
                         "Critical calm should trigger micro_break")
        self.assertGreaterEqual(decision['confidence'], 0.90,
                                f"Critical decisions should have high confidence")
        self.assertEqual(decision['rule_id'], 'calm_break_critical')
        self.assertIn('rationale', decision)
        self.assertIn('55', decision['rationale'], "Rationale should mention actual score")

        print(f"✅ Critical break decision: action={decision['action']}")
        print(f"   Confidence: {decision['confidence']}")
        print(f"   Rule: {decision['rule_id']}")

    def test_03_low_calm_break(self):
        """
        Test: Low calm score (60-74) triggers short break
        Expected: action='micro_break', medium confidence
        """
        print(f"\nTest 03: Low calm score at {__file__}:103")

        metrics = {
            'student_id': 123,
            'calm_score': 70.5,  # Low (60-74)
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Assertions
        self.assertEqual(decision['action'], 'micro_break')
        self.assertGreaterEqual(decision['confidence'], 0.80)
        self.assertEqual(decision['rule_id'], 'calm_break_low')

        # Check params
        params = json.loads(decision['params'])
        self.assertIn('duration_minutes', params)
        self.assertEqual(params['duration_minutes'], 3,
                         "Low calm should trigger 3-minute break")

        print(f"✅ Low calm break: {params['duration_minutes']} minutes")
        print(f"   Confidence: {decision['confidence']}")

    def test_04_moderate_calm_no_action(self):
        """
        Test: Moderate calm score (75-89) continues monitoring
        Expected: action='none'
        """
        print(f"\nTest 04: Moderate calm score at {__file__}:134")

        metrics = {
            'student_id': 123,
            'calm_score': 82.0,  # Moderate (75-89)
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Assertions
        self.assertEqual(decision['action'], 'none',
                         "Moderate calm should continue without intervention")
        self.assertEqual(decision['rule_id'], 'calm_moderate_monitor')

        print(f"✅ Moderate calm: action={decision['action']}")
        print(f"   Rule: {decision['rule_id']}")

    def test_05_high_calm_optimal(self):
        """
        Test: High calm score (>=90) is optimal state
        Expected: action='none', high confidence
        """
        print(f"\nTest 05: High calm score at {__file__}:158")

        metrics = {
            'student_id': 123,
            'calm_score': 95.0,  # High (>=90)
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Assertions
        self.assertEqual(decision['action'], 'none')
        self.assertEqual(decision['rule_id'], 'calm_optimal')
        self.assertGreaterEqual(decision['confidence'], 0.85)

        # Check params
        params = json.loads(decision['params'])
        self.assertTrue(params.get('suggest_challenge', False),
                        "High calm should suggest challenge")

        print(f"✅ Optimal state: action={decision['action']}")
        print(f"   Suggests challenge: {params.get('suggest_challenge')}")

    def test_06_rule_priority_ordering(self):
        """
        Test: Rules are evaluated in priority order (highest first)
        Expected: First matching rule wins
        """
        print(f"\nTest 06: Rule priority at {__file__}:188")

        # Score 70 matches both critical (< 60) and low (60-74)
        # But low has higher priority, so it should match first
        metrics = {
            'student_id': 123,
            'calm_score': 70.0,
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Should match 'calm_break_low' (priority 90) not 'calm_break_critical' (priority 100)
        # because 70 doesn't satisfy < 60 condition
        self.assertEqual(decision['rule_id'], 'calm_break_low')

        print(f"✅ Correct rule selected: {decision['rule_id']}")

    def test_07_missing_required_field(self):
        """
        Test: Error handling - missing required field
        Expected: ValueError raised
        """
        print(f"\nTest 07: Missing required field at {__file__}:212")

        metrics = {
            'student_id': 123,
            # Missing 'calm_score'
        }

        with self.assertRaises(ValueError) as context:
            self.engine.decide(metrics)

        self.assertIn('Missing required field', str(context.exception))
        print(f"✅ Correctly raised ValueError: {context.exception}")

    def test_08_decision_schema_compliance(self):
        """
        Test: Output schema compliance with decision.schema.json
        Expected: All required fields present with correct types
        """
        print(f"\nTest 08: Schema compliance at {__file__}:230")

        metrics = {
            'student_id': 123,
            'calm_score': 80.0,
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Check required fields
        required_fields = ['student_id', 'action', 'confidence', 'rationale', 'timestamp']
        for field in required_fields:
            self.assertIn(field, decision, f"Missing required field: {field}")

        # Check data types
        self.assertIsInstance(decision['student_id'], int)
        self.assertIsInstance(decision['action'], str)
        self.assertIsInstance(decision['confidence'], (int, float))
        self.assertIsInstance(decision['rationale'], str)

        # Check confidence range
        self.assertGreaterEqual(decision['confidence'], 0.0)
        self.assertLessEqual(decision['confidence'], 1.0)

        # Check optional fields exist
        self.assertIn('params', decision)
        self.assertIn('rule_id', decision)
        self.assertIn('trace_data', decision)

        print(f"✅ All required fields present")
        print(f"   action: {decision['action']} (type: {type(decision['action']).__name__})")
        print(f"   confidence: {decision['confidence']} (range: 0-1)")

    def test_09_rationale_generation(self):
        """
        Test: Rationale includes actual metric values
        Expected: Rationale template filled with real data
        """
        print(f"\nTest 09: Rationale generation at {__file__}:270")

        metrics = {
            'student_id': 123,
            'calm_score': 65.5,
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Rationale should mention actual score
        self.assertIn('65.5', decision['rationale'] or '65',
                      "Rationale should include actual calm score")
        self.assertGreater(len(decision['rationale']), 20,
                           "Rationale should be substantive")

        print(f"✅ Rationale generated: {decision['rationale'][:80]}...")

    def test_10_trace_data_logging(self):
        """
        Test: Trace data includes debugging/auditing info
        Expected: trace_data contains evaluation details
        """
        print(f"\nTest 10: Trace data at {__file__}:294")

        metrics = {
            'student_id': 123,
            'calm_score': 75.0,
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Parse trace_data
        trace = json.loads(decision['trace_data'])

        # Check trace contents
        self.assertIn('rules_evaluated', trace)
        self.assertIn('matched_rule_id', trace)
        self.assertIn('metrics_snapshot', trace)
        self.assertIn('evaluation_timestamp', trace)

        self.assertEqual(trace['rules_evaluated'], len(self.engine.rules))
        self.assertEqual(trace['matched_rule_id'], decision['rule_id'])

        print(f"✅ Trace data logged: {trace['rules_evaluated']} rules evaluated")
        print(f"   Matched: {trace['matched_rule_id']}")

    def test_11_json_serialization(self):
        """
        Test: Decision can be serialized to JSON without errors
        Expected: Valid JSON output
        """
        print(f"\nTest 11: JSON serialization at {__file__}:326")

        metrics = {
            'student_id': 123,
            'calm_score': 80.0,
            'timestamp': '2025-11-02T10:30:00Z'
        }

        decision = self.engine.decide(metrics)

        # Serialize to JSON
        json_output = json.dumps(decision, ensure_ascii=False, indent=2)

        self.assertIsInstance(json_output, str)
        self.assertGreater(len(json_output), 0)

        # Deserialize back
        parsed = json.loads(json_output)
        self.assertEqual(parsed['student_id'], decision['student_id'])
        self.assertEqual(parsed['action'], decision['action'])

        print(f"✅ JSON serialization successful ({len(json_output)} bytes)")

    def test_12_confidence_scoring(self):
        """
        Test: Confidence scores are appropriate for different scenarios
        Expected: Critical = high confidence, edge cases = lower confidence
        """
        print(f"\nTest 12: Confidence scoring at {__file__}:355")

        test_cases = [
            {'calm_score': 50, 'expected_min_confidence': 0.90, 'description': 'Critical'},
            {'calm_score': 70, 'expected_min_confidence': 0.80, 'description': 'Low'},
            {'calm_score': 95, 'expected_min_confidence': 0.85, 'description': 'Optimal'}
        ]

        for case in test_cases:
            metrics = {
                'student_id': 123,
                'calm_score': case['calm_score'],
                'timestamp': '2025-11-02T10:30:00Z'
            }
            decision = self.engine.decide(metrics)

            self.assertGreaterEqual(
                decision['confidence'],
                case['expected_min_confidence'],
                f"{case['description']} scenario should have confidence >= {case['expected_min_confidence']}"
            )

            print(f"   {case['description']} (score={case['calm_score']}): confidence={decision['confidence']}")

        print(f"✅ All confidence scores appropriate")


def run_tests():
    """
    Run all tests with detailed output
    """
    print("=" * 70)
    print("Mathking Agentic MVP System - Rule Engine Tests")
    print("=" * 70)

    # Run tests
    loader = unittest.TestLoader()
    suite = loader.loadTestsFromTestCase(TestRuleEngine)
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)

    # Summary
    print("\n" + "=" * 70)
    print("Test Summary")
    print("=" * 70)
    print(f"Tests run: {result.testsRun}")
    print(f"Successes: {result.testsRun - len(result.failures) - len(result.errors)}")
    print(f"Failures: {len(result.failures)}")
    print(f"Errors: {len(result.errors)}")

    return 0 if result.wasSuccessful() else 1


if __name__ == '__main__':
    exit_code = run_tests()
    sys.exit(exit_code)


# =============================================================================
# Test Execution Methods
# =============================================================================
#
# 1. Direct execution:
#    python rule_engine.test.py
#
# 2. Using pytest (recommended):
#    pytest rule_engine.test.py -v
#    pytest rule_engine.test.py -v --tb=short
#
# 3. Specific test:
#    python -m unittest rule_engine.test.TestRuleEngine.test_02_critical_calm_break
#
# =============================================================================
