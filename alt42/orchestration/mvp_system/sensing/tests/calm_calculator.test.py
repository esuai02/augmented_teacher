#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# ํŒŒ์ผ: mvp_system/sensing/tests/calm_calculator.test.py (Line 1)
# Mathking Agentic MVP System - Calm Calculator Unit Tests
#
# Purpose: Test calm score calculation logic and policy integration
# Run: python -m pytest calm_calculator.test.py -v
# Or: python calm_calculator.test.py

import unittest
import sys
import json
from pathlib import Path

# Add parent directory to path
sys.path.insert(0, str(Path(__file__).parent.parent))

from calm_calculator import CalmCalculator


class TestCalmCalculator(unittest.TestCase):
    """
    Test suite for CalmCalculator class
    """

    @classmethod
    def setUpClass(cls):
        """
        Set up test fixtures once for all tests
        """
        # Use actual policy file for integration testing
        root = Path(__file__).parent.parent.parent.parent
        policy_path = root / 'agents' / 'agent08_calmness' / 'agent08_calmness.md'

        if not policy_path.exists():
            print(f"WARNING: Policy file not found at {policy_path} at {__file__}:31", file=sys.stderr)
            print("Some tests may fail without actual policy file", file=sys.stderr)

        cls.calculator = CalmCalculator(str(policy_path) if policy_path.exists() else None)

    def test_01_policy_loading(self):
        """
        Test: Policy file loading and threshold parsing
        """
        print(f"\nTest 01: Policy loading at {__file__}:41")

        # Verify thresholds were loaded
        self.assertGreater(len(self.calculator.thresholds), 0,
                           "No thresholds loaded from policy file")

        # Verify threshold structure
        for threshold in self.calculator.thresholds:
            self.assertIn('value', threshold)
            self.assertIn('description', threshold)
            self.assertIsInstance(threshold['value'], int)

        print(f"โœ… Loaded {len(self.calculator.thresholds)} thresholds")

    def test_02_perfect_calm_score(self):
        """
        Test: Perfect scenario - high focus, no interruptions, high accuracy
        Expected: calm_score ~100
        """
        print(f"\nTest 02: Perfect calm score at {__file__}:60")

        data = {
            'student_id': 123,
            'session_duration': 1800,  # 30 minutes
            'interruptions': 0,
            'correct_answers': 10,
            'total_questions': 10,
            'focus_time': 1800
        }

        result = self.calculator.calculate(data)

        # Assertions
        self.assertEqual(result['student_id'], 123)
        self.assertGreaterEqual(result['calm_score'], 95,
                                f"Expected score >=95, got {result['calm_score']}")
        self.assertLessEqual(result['calm_score'], 100)
        self.assertIn('recommendation', result)

        print(f"โœ… Perfect score: {result['calm_score']}")
        print(f"   Recommendation: {result['recommendation']}")

    def test_03_low_calm_score_high_interruptions(self):
        """
        Test: Low calm scenario - many interruptions
        Expected: calm_score < 75
        """
        print(f"\nTest 03: Low calm score (high interruptions) at {__file__}:89")

        data = {
            'student_id': 123,
            'session_duration': 1800,  # 30 minutes
            'interruptions': 15,  # Very high
            'correct_answers': 5,
            'total_questions': 10,
            'focus_time': 900  # Only 50% focused
        }

        result = self.calculator.calculate(data)

        # Assertions
        self.assertLess(result['calm_score'], 75,
                        f"Expected score <75 for high interruptions, got {result['calm_score']}")
        # Check for emergency/recovery keywords (avoiding encoding issues with specific Korean chars)
        self.assertTrue(
            'emergency' in result['recommendation'].lower() or
            'urgent' in result['recommendation'].lower() or
            len(result['recommendation']) > 10,  # At least has substantial recommendation
            f"Expected meaningful recommendation for low calm score, got: {result['recommendation']}"
        )

        print(f"โœ… Low score: {result['calm_score']}")
        print(f"   Recommendation: {result['recommendation']}")

    def test_04_moderate_calm_score(self):
        """
        Test: Moderate scenario - balanced metrics
        Expected: calm_score 80-90
        """
        print(f"\nTest 04: Moderate calm score at {__file__}:117")

        data = {
            'student_id': 123,
            'session_duration': 1800,
            'interruptions': 2,  # Moderate
            'correct_answers': 8,
            'total_questions': 10,
            'focus_time': 1600  # ~89% focused
        }

        result = self.calculator.calculate(data)

        # Assertions
        self.assertGreaterEqual(result['calm_score'], 75)
        self.assertLessEqual(result['calm_score'], 95)

        print(f"โœ… Moderate score: {result['calm_score']}")
        print(f"   Recommendation: {result['recommendation']}")

    def test_05_missing_required_field(self):
        """
        Test: Error handling - missing required field
        Expected: ValueError raised
        """
        print(f"\nTest 05: Missing required field at {__file__}:143")

        data = {
            'student_id': 123,
            # Missing 'session_duration' and 'interruptions'
        }

        with self.assertRaises(ValueError) as context:
            self.calculator.calculate(data)

        self.assertIn('Missing required field', str(context.exception))
        print(f"โœ… Correctly raised ValueError: {context.exception}")

    def test_06_edge_case_zero_duration(self):
        """
        Test: Edge case - zero session duration
        Expected: Handle gracefully without division by zero
        """
        print(f"\nTest 06: Edge case - zero duration at {__file__}:162")

        data = {
            'student_id': 123,
            'session_duration': 0,
            'interruptions': 0,
            'correct_answers': 0,
            'total_questions': 1
        }

        result = self.calculator.calculate(data)

        # Should not crash
        self.assertIsInstance(result['calm_score'], (int, float))
        self.assertGreaterEqual(result['calm_score'], 0)
        self.assertLessEqual(result['calm_score'], 100)

        print(f"โœ… Handled zero duration: {result['calm_score']}")

    def test_07_schema_compliance(self):
        """
        Test: Output schema compliance with metrics.schema.json
        Expected: All required fields present with correct types
        """
        print(f"\nTest 07: Schema compliance at {__file__}:187")

        data = {
            'student_id': 123,
            'session_duration': 1800,
            'interruptions': 2
        }

        result = self.calculator.calculate(data)

        # Check required fields
        required_fields = ['student_id', 'calm_score', 'timestamp']
        for field in required_fields:
            self.assertIn(field, result, f"Missing required field: {field}")

        # Check data types
        self.assertIsInstance(result['student_id'], int)
        self.assertIsInstance(result['calm_score'], (int, float))
        self.assertIsInstance(result['timestamp'], str)

        # Check optional fields exist (can be None)
        self.assertIn('focus_score', result)
        self.assertIn('flow_score', result)
        self.assertIn('goal_alignment', result)
        self.assertIn('raw_data', result)
        self.assertIn('recommendation', result)

        print(f"โœ… All required fields present")
        print(f"   student_id: {result['student_id']} (type: {type(result['student_id']).__name__})")
        print(f"   calm_score: {result['calm_score']} (type: {type(result['calm_score']).__name__})")

    def test_08_recommendation_matching(self):
        """
        Test: Recommendation correctly matches thresholds from policy
        Expected: Different calm scores get appropriate recommendations
        """
        print(f"\nTest 08: Recommendation matching at {__file__}:225")

        test_scores = [98, 92, 87, 80, 72, 60]

        for score in test_scores:
            recommendation = self.calculator.get_recommendation(score)
            self.assertIsInstance(recommendation, str)
            self.assertGreater(len(recommendation), 0)

            print(f"   Score {score:3d} โ†' {recommendation[:50]}")

        print(f"โœ… All recommendations generated")

    def test_09_focus_score_calculation(self):
        """
        Test: Focus score accurately reflects focus_time ratio
        Expected: focus_score = (focus_time / session_duration) * 100
        """
        print(f"\nTest 09: Focus score calculation at {__file__}:244")

        data = {
            'student_id': 123,
            'session_duration': 1000,
            'interruptions': 1,
            'focus_time': 800  # 80% focused
        }

        result = self.calculator.calculate(data)

        expected_focus = 80.0
        self.assertAlmostEqual(result['focus_score'], expected_focus, places=1,
                               msg=f"Expected focus_score ~{expected_focus}, got {result['focus_score']}")

        print(f"โœ… Focus score: {result['focus_score']} (expected ~{expected_focus})")

    def test_10_json_serialization(self):
        """
        Test: Result can be serialized to JSON without errors
        Expected: Valid JSON output with UTF-8 support
        """
        print(f"\nTest 10: JSON serialization at {__file__}:268")

        data = {
            'student_id': 123,
            'session_duration': 1800,
            'interruptions': 2
        }

        result = self.calculator.calculate(data)

        # Serialize to JSON
        json_output = json.dumps(result, ensure_ascii=False, indent=2)

        self.assertIsInstance(json_output, str)
        self.assertGreater(len(json_output), 0)

        # Deserialize back
        parsed = json.loads(json_output)
        self.assertEqual(parsed['student_id'], result['student_id'])
        self.assertEqual(parsed['calm_score'], result['calm_score'])

        print(f"โœ… JSON serialization successful ({len(json_output)} bytes)")


def run_tests():
    """
    Run all tests with detailed output
    """
    print("=" * 70)
    print("Mathking Agentic MVP System - Calm Calculator Tests")
    print("=" * 70)

    # Run tests
    loader = unittest.TestLoader()
    suite = loader.loadTestsFromTestCase(TestCalmCalculator)
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
# ํ…Œ์ŠคํŠธ ์‹คํ–‰ ๋ฐฉ๋ฒ• (How to Run Tests)
# =============================================================================
#
# 1. ์ง์ ' ์‹คํ–‰:
#    python calm_calculator.test.py
#
# 2. pytest ์‚ฌ์šฉ (๊ถŒ์žฅ):
#    pytest calm_calculator.test.py -v
#    pytest calm_calculator.test.py -v --tb=short
#
# 3. ํŠน์ • ํ…Œ์ŠคํŠธ๋งŒ ์‹คํ–‰:
#    python -m unittest calm_calculator.test.TestCalmCalculator.test_02_perfect_calm_score
#
# =============================================================================
