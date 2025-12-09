#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# ํŒŒ์ผ: mvp_system/sensing/calm_calculator.py (Line 1)
# Mathking Agentic MVP System - Calm Score Calculator
#
# Purpose: Calculate calm_score from student activity logs
# Input: Raw log data (JSON)
# Output: Metrics object matching contracts/schemas/metrics.schema.json
# Reference: agents/agent08_calmness/agent08_calmness.md (read-only)

import sys
import json
import os
import re
from datetime import datetime
from pathlib import Path

# Add parent directory to path for imports
sys.path.insert(0, str(Path(__file__).parent.parent))

class CalmCalculator:
    """
    Calm Score Calculator

    ์นจ์ฐฉ๋„ ์ง€ํ'œ ๊ณ„์‚ฐ๊ธฐ - agent08 ์ •์ฑ… ๊ธฐ๋ฐ˜
    """

    def __init__(self, policy_file_path=None):
        """
        Initialize calculator with agent08 policy

        Args:
            policy_file_path (str): Path to agent08_calmness.md file
        """
        if policy_file_path is None:
            # Default path from config
            # From mvp_system/sensing/calm_calculator.py โ†' mvp_system/sensing โ†' mvp_system โ†' orchestration
            orchestration_root = Path(__file__).parent.parent.parent
            policy_file_path = orchestration_root / 'agents' / 'agent08_calmness' / 'agent08_calmness.md'

        self.policy_file = Path(policy_file_path)
        self.thresholds = []
        self.load_policy()

    def load_policy(self):
        """
        Load and parse agent08 policy file

        Parses threshold definitions like:
        - 95+: ๋งค์šฐ ์นจ์ฐฉ
        - 90~94: ์•ˆ์ •
        - <75: ๋‚ฎ์Œ, 3~5๋ถ„ ํœด์‹

        Raises:
            FileNotFoundError: If policy file doesn't exist
        """
        if not self.policy_file.exists():
            error_msg = f"Policy file not found: {self.policy_file} at {__file__}:45"
            print(f"ERROR: {error_msg}", file=sys.stderr)
            raise FileNotFoundError(error_msg)

        with open(self.policy_file, 'r', encoding='utf-8') as f:
            content = f.read()

        # Parse thresholds: "- 95+: ๋งค์šฐ ์นจ์ฐฉ", "- <75: ๊ธด๊ธ‰ ๋ณต๊ตฌ"
        # Match both "95+" and "<75" formats
        pattern = r'^[\s]*-\s*(<)?(\d+)([+~\-])?(\d*)?:\s*(.+)$'
        matches = re.finditer(pattern, content, re.MULTILINE)

        for match in matches:
            less_than_prefix = match.group(1)  # "<" if present
            threshold_value = int(match.group(2))
            range_indicator = match.group(3) or ''
            range_end = int(match.group(4)) if match.group(4) else None
            description = match.group(5).strip()

            # Handle "<75" format by converting to range_indicator
            if less_than_prefix == '<':
                range_indicator = '<'

            self.thresholds.append({
                'value': threshold_value,
                'range_indicator': range_indicator,
                'range_end': range_end,
                'description': description
            })

        # Sort by value descending for matching
        self.thresholds.sort(key=lambda x: x['value'], reverse=True)

        print(f"INFO: Loaded {len(self.thresholds)} thresholds from {self.policy_file} at {__file__}:73", file=sys.stderr)

    def calculate(self, raw_data):
        """
        Calculate calm score from raw student activity data

        Args:
            raw_data (dict): Raw log data containing:
                - student_id (int): Student ID
                - session_duration (int): Study session duration in seconds
                - interruptions (int): Number of interruptions
                - correct_answers (int): Number of correct answers
                - total_questions (int): Total questions attempted
                - focus_time (int): Time spent focused in seconds
                - timestamp (str, optional): Measurement time (ISO format)

        Returns:
            dict: Metrics object matching metrics.schema.json

        Raises:
            ValueError: If required fields are missing

        Example:
            >>> calculator = CalmCalculator()
            >>> data = {
            ...     'student_id': 123,
            ...     'session_duration': 1800,
            ...     'interruptions': 2,
            ...     'correct_answers': 8,
            ...     'total_questions': 10
            ... }
            >>> result = calculator.calculate(data)
            >>> result['calm_score']
            88.5
        """
        # Validate required fields
        required_fields = ['student_id', 'session_duration', 'interruptions']
        for field in required_fields:
            if field not in raw_data:
                error_msg = f"Missing required field: {field} at {__file__}:113"
                print(f"ERROR: {error_msg}", file=sys.stderr)
                raise ValueError(error_msg)

        student_id = raw_data['student_id']
        session_duration = raw_data.get('session_duration', 0)
        interruptions = raw_data.get('interruptions', 0)
        correct_answers = raw_data.get('correct_answers', 0)
        total_questions = raw_data.get('total_questions', 1)
        focus_time = raw_data.get('focus_time', session_duration)

        # Calculate calm score (0-100)
        # Formula: Base score - interruption penalty + focus adjustment + accuracy adjustment

        # 1. Base score: 70 points (lower baseline for more realistic scaling)
        calm_score = 70.0

        # 2. Interruption penalty: More aggressive penalty
        #    - Absolute count penalty: -3 points per interruption (significant impact)
        #    - Rate penalty: Additional penalty for high frequency
        if session_duration > 0:
            # Absolute penalty
            interruption_penalty = interruptions * 3.0

            # Additional rate-based penalty for very high frequency (>0.5/min)
            interruption_rate = interruptions / (session_duration / 60.0)  # per minute
            if interruption_rate > 0.5:
                interruption_penalty += (interruption_rate - 0.5) * 10

            calm_score -= interruption_penalty

        # 3. Focus adjustment: More nuanced scoring
        if session_duration > 0:
            focus_ratio = focus_time / session_duration
            if focus_ratio >= 0.9:
                calm_score += 15  # Excellent focus
            elif focus_ratio >= 0.8:
                calm_score += 10  # Good focus
            elif focus_ratio >= 0.6:
                calm_score += 5   # Acceptable focus
            elif focus_ratio < 0.5:
                calm_score -= 15  # Poor focus

        # 4. Accuracy adjustment: Reward consistency
        if total_questions > 0:
            accuracy = correct_answers / total_questions
            if accuracy >= 0.9:
                calm_score += 15  # Excellent accuracy
            elif accuracy >= 0.8:
                calm_score += 10  # Good accuracy
            elif accuracy >= 0.6:
                calm_score += 5   # Acceptable accuracy
            elif accuracy < 0.5:
                calm_score -= 10  # Poor accuracy

        # Clamp to 0-100 range
        calm_score = max(0.0, min(100.0, calm_score))

        # Get recommendation based on threshold
        recommendation = self.get_recommendation(calm_score)

        # Prepare output matching metrics.schema.json
        timestamp = raw_data.get('timestamp', datetime.utcnow().isoformat() + 'Z')

        result = {
            'student_id': student_id,
            'calm_score': round(calm_score, 2),
            'focus_score': round((focus_time / session_duration * 100) if session_duration > 0 else 0, 2),
            'flow_score': None,  # Not calculated in MVP
            'goal_alignment': None,  # Not calculated in MVP
            'raw_data': json.dumps(raw_data),
            'recommendation': recommendation,
            'timestamp': timestamp
        }

        print(f"INFO: Calculated calm_score={calm_score:.2f} for student_id={student_id} at {__file__}:176", file=sys.stderr)

        return result

    def get_recommendation(self, calm_score):
        """
        Get recommendation text based on calm score threshold

        Args:
            calm_score (float): Calm score (0-100)

        Returns:
            str: Recommendation text from agent08 policy
        """
        for threshold in self.thresholds:
            min_val = threshold['value']
            max_val = threshold['range_end']
            indicator = threshold['range_indicator']

            if indicator == '+':
                # 95+ format
                if calm_score >= min_val:
                    return threshold['description']
            elif indicator == '~' and max_val is not None:
                # 90~94 format
                if min_val <= calm_score <= max_val:
                    return threshold['description']
            elif indicator == '<':
                # <75 format
                if calm_score < min_val:
                    return threshold['description']
            elif indicator == '-' or (indicator == '' and max_val is None):
                # Single value or range with dash
                if calm_score < min_val:
                    return threshold['description']

        # Default fallback
        return "ํ'œ์ค€ ํ•™์Šต ์ง„ํ–‰ ๊ถŒ์žฅ"


def main():
    """
    CLI entry point for calm calculator

    Usage:
        python calm_calculator.py '{"student_id": 123, "session_duration": 1800, "interruptions": 2}'

    Outputs:
        JSON string to stdout matching metrics.schema.json
    """
    if len(sys.argv) < 2:
        print("ERROR: Missing input data at " + __file__ + ":227", file=sys.stderr)
        print("Usage: python calm_calculator.py '<json_data>'", file=sys.stderr)
        sys.exit(1)

    try:
        # Parse input JSON
        raw_data = json.loads(sys.argv[1])

        # Calculate metrics
        calculator = CalmCalculator()
        result = calculator.calculate(raw_data)

        # Output JSON to stdout
        print(json.dumps(result, ensure_ascii=False, indent=2))

    except json.JSONDecodeError as e:
        print(f"ERROR: Invalid JSON input at {__file__}:243 - {e}", file=sys.stderr)
        sys.exit(1)
    except ValueError as e:
        print(f"ERROR: Validation error at {__file__}:246 - {e}", file=sys.stderr)
        sys.exit(1)
    except Exception as e:
        print(f"ERROR: Unexpected error at {__file__}:249 - {e}", file=sys.stderr)
        sys.exit(1)


if __name__ == '__main__':
    main()


# =============================================================================
# ์‚ฌ์šฉ ์˜ˆ์‹œ (Example Usage)
# =============================================================================
#
# ํ„ฐ๋ฏธ๋„์—์„œ ์ง์ ' ์‹คํ–‰:
# python calm_calculator.py '{"student_id": 123, "session_duration": 1800, "interruptions": 2, "correct_answers": 8, "total_questions": 10}'
#
# PHP์—์„œ ํ˜ธ์ถœ:
# $result = shell_exec("python3 calm_calculator.py '" . json_encode($data) . "'");
# $metrics = json_decode($result, true);
#
# =============================================================================
