"""
Phase 7.1: Quantum Data Interface (양자 데이터 인터페이스)
=============================================================

22개 에이전트 출력 → 13종 파동함수 입력 변환 인터페이스

주요 기능:
1. STANDARD_FEATURES 스키마 정의
2. 에이전트별 데이터 어댑터
3. 70D→8D 차원 축소 변환
4. 실시간 데이터 수집 및 정규화

Data Sources:
- 침착도: mdl_abessi_today (score), at_calmness_scores
- 포모도로: mdl_alt42g_pomodoro_sessions
- 목표: mdl_alt42g_goal_analysis, mdl_alt42g_student_goals
- 문제노트: mdl_abessi_messages (contentstype=2)
- 활동: mdl_alt42_student_activity
- 휴식: mdl_abessi_breaktimelog

Server URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0 Docs/holons/_quantum_data_interface.py
"""

import math
from typing import Dict, List, Optional, Any, Tuple, Callable
from dataclasses import dataclass, field
from datetime import datetime
from enum import Enum
import json


# ============================================================================
# STANDARD_FEATURES 스키마 정의
# ============================================================================

@dataclass
class StandardFeatures:
    """
    22개 에이전트 출력을 표준화한 특성 스키마

    이 스키마는 13종 파동함수 계산의 입력으로 사용됨
    각 필드는 0.0~1.0 정규화 또는 정수 카운트
    """
    # === Core Features (ψ_core 입력) ===
    concept_mastery: float = 0.0        # 개념 이해도 (0~1)
    problem_accuracy: float = 0.0       # 문제 정답률 (0~1)
    teacher_confirm: float = 0.0        # 교사 확인 비율 (0~1)

    # === Misconception Features (ψ_core β 계산) ===
    misconception_score: float = 0.0    # 오개념 점수 (0~1)
    error_pattern_match: float = 0.0    # 오류 패턴 매칭률 (0~1)
    feedback_negative: float = 0.0      # 부정 피드백 비율 (0~1)

    # === Affect Features (ψ_affect 입력) ===
    calmness_score: float = 0.5         # 침착도 (0~100 → 0~1)
    anxiety_level: float = 0.5          # 불안 수준 (침착도 역)
    tension_level: float = 0.3          # 긴장도
    overload_level: float = 0.2         # 과부하 수준

    # === Engagement Features (ψ_engage 입력) ===
    engagement_level: float = 0.5       # 참여도/몰입도 (0~1)
    focus_duration: float = 0.0         # 집중 시간 (분, 정규화)
    pomodoro_completion: float = 0.0    # 포모도로 완료율 (0~1)
    dropout_risk: float = 0.0           # 이탈 위험도 (0~1)

    # === Activity Features (ψ_fluct, ψ_routine 입력) ===
    time_on_task: float = 0.0           # 과제 수행 시간 (분, 정규화)
    attempt_count: int = 0              # 시도 횟수
    revision_count: int = 0             # 수정 횟수
    hesitation_index: float = 0.0       # 망설임 지수 (0~1)

    # === Goal Features (ψ_align 입력) ===
    goal_progress: float = 0.0          # 목표 진행률 (0~1)
    goal_effectiveness: float = 0.0     # 목표 효과성 점수 (0~1)
    goal_completion_rate: float = 0.0   # 목표 완료율 (0~1)

    # === Routine Features (ψ_routine 입력) ===
    rest_interval: float = 0.0          # 평균 휴식 간격 (분, 정규화)
    rest_pattern_type: str = 'unknown'  # 휴식 유형
    daily_routine_strength: float = 0.5 # 일간 루틴 강도

    # === Learning Pattern Features (ψ_WM, ψ_tunnel 입력) ===
    wm_stability: float = 0.5           # 작업기억 안정도
    cognitive_energy: float = 0.5       # 인지 에너지
    concept_barrier: float = 0.5        # 개념 장벽 높이

    # === Context Features (ψ_context 입력) ===
    time_of_day: float = 0.0            # 하루 중 시간 (0~1)
    preceding_success: float = 0.5      # 직전 성공 여부
    fatigue_level: float = 0.3          # 피로도
    motivation_level: float = 0.5       # 동기 수준

    # === Meta Features (ψ_meta 입력) ===
    self_efficacy: float = 0.5          # 자기 효능감
    metacognitive_awareness: float = 0.5 # 메타인지 인식

    # === Timestamp ===
    timestamp: datetime = field(default_factory=datetime.now)
    student_id: int = 0

    def to_dict(self) -> Dict[str, Any]:
        """딕셔너리 변환"""
        return {
            'concept_mastery': self.concept_mastery,
            'problem_accuracy': self.problem_accuracy,
            'teacher_confirm': self.teacher_confirm,
            'misconception_score': self.misconception_score,
            'error_pattern_match': self.error_pattern_match,
            'feedback_negative': self.feedback_negative,
            'calmness_score': self.calmness_score,
            'anxiety_level': self.anxiety_level,
            'tension_level': self.tension_level,
            'overload_level': self.overload_level,
            'engagement_level': self.engagement_level,
            'focus_duration': self.focus_duration,
            'pomodoro_completion': self.pomodoro_completion,
            'dropout_risk': self.dropout_risk,
            'time_on_task': self.time_on_task,
            'attempt_count': self.attempt_count,
            'revision_count': self.revision_count,
            'hesitation_index': self.hesitation_index,
            'goal_progress': self.goal_progress,
            'goal_effectiveness': self.goal_effectiveness,
            'goal_completion_rate': self.goal_completion_rate,
            'rest_interval': self.rest_interval,
            'rest_pattern_type': self.rest_pattern_type,
            'daily_routine_strength': self.daily_routine_strength,
            'wm_stability': self.wm_stability,
            'cognitive_energy': self.cognitive_energy,
            'concept_barrier': self.concept_barrier,
            'time_of_day': self.time_of_day,
            'preceding_success': self.preceding_success,
            'fatigue_level': self.fatigue_level,
            'motivation_level': self.motivation_level,
            'self_efficacy': self.self_efficacy,
            'metacognitive_awareness': self.metacognitive_awareness,
            'timestamp': self.timestamp.isoformat(),
            'student_id': self.student_id
        }

    def to_json(self) -> str:
        """JSON 직렬화"""
        return json.dumps(self.to_dict(), ensure_ascii=False, indent=2)


# ============================================================================
# 에이전트 출력 스키마 (22개 에이전트별)
# ============================================================================

class AgentOutputSchema(Enum):
    """22개 에이전트별 출력 스키마 매핑"""

    # Phase 1: Daily Information Collection (Agent 01-06)
    AGENT_01_ONBOARDING = {
        'name': 'onboarding',
        'output_fields': ['user_profile', 'initial_assessment', 'preferences'],
        'target_features': ['self_efficacy', 'motivation_level']
    }
    AGENT_02_EXAM_SCHEDULE = {
        'name': 'exam_schedule',
        'output_fields': ['exam_dates', 'preparation_time', 'priority'],
        'target_features': ['time_of_day', 'goal_progress']
    }
    AGENT_03_GOALS_ANALYSIS = {
        'name': 'goals_analysis',
        'output_fields': ['goal_type', 'status', 'progress', 'effectiveness_score', 'completion_rate'],
        'target_features': ['goal_progress', 'goal_effectiveness', 'goal_completion_rate']
    }
    AGENT_04_INSPECT_WEAKPOINTS = {
        'name': 'inspect_weakpoints',
        'output_fields': ['main_category', 'sub_activity', 'behavior_type', 'activity_patterns'],
        'target_features': ['concept_mastery', 'misconception_score', 'error_pattern_match']
    }
    AGENT_05_LEARNING_EMOTION = {
        'name': 'learning_emotion',
        'output_fields': ['emotion_type', 'activity_type', 'triggers'],
        'target_features': ['anxiety_level', 'tension_level', 'motivation_level']
    }
    AGENT_06_TEACHER_FEEDBACK = {
        'name': 'teacher_feedback',
        'output_fields': ['feedback_type', 'rating', 'comments'],
        'target_features': ['teacher_confirm', 'feedback_negative']
    }

    # Phase 2: Real-time Interaction (Agent 07-13)
    AGENT_07_INTERACTION_TARGETING = {
        'name': 'interaction_targeting',
        'output_fields': ['target_type', 'timing', 'method'],
        'target_features': ['engagement_level', 'focus_duration']
    }
    AGENT_08_CALMNESS = {
        'name': 'calmness',
        'output_fields': ['calm_score', 'calmness_level', 'daily_goals', 'review_points'],
        'target_features': ['calmness_score', 'anxiety_level', 'overload_level']
    }
    AGENT_09_LEARNING_MANAGEMENT = {
        'name': 'learning_management',
        'output_fields': ['pomodoro_stats', 'goal_analysis', 'whiteboard_activity'],
        'target_features': ['pomodoro_completion', 'engagement_level', 'time_on_task']
    }
    AGENT_10_CONCEPT_NOTES = {
        'name': 'concept_notes',
        'output_fields': ['note_count', 'concept_coverage', 'understanding_level'],
        'target_features': ['concept_mastery', 'wm_stability']
    }
    AGENT_11_PROBLEM_NOTES = {
        'name': 'problem_notes',
        'output_fields': ['attempt_count', 'preparation_count', 'essay_count', 'nstroke', 'usedtime'],
        'target_features': ['problem_accuracy', 'attempt_count', 'revision_count', 'misconception_score']
    }
    AGENT_12_REST_ROUTINE = {
        'name': 'rest_routine',
        'output_fields': ['rest_count', 'average_interval', 'rest_type', 'rest_patterns'],
        'target_features': ['rest_interval', 'rest_pattern_type', 'daily_routine_strength']
    }
    AGENT_13_LEARNING_DROPOUT = {
        'name': 'learning_dropout',
        'output_fields': ['dropout_risk', 'warning_signals', 'engagement_trend'],
        'target_features': ['dropout_risk', 'engagement_level']
    }

    # Phase 3: Diagnosis & Preparation (Agent 14-19)
    AGENT_14_CURRENT_POSITION = {
        'name': 'current_position',
        'output_fields': ['curriculum_position', 'mastery_level', 'next_target'],
        'target_features': ['concept_mastery', 'goal_progress']
    }
    AGENT_15_PROBLEM_REDEFINITION = {
        'name': 'problem_redefinition',
        'output_fields': ['problem_type', 'difficulty', 'prerequisites'],
        'target_features': ['concept_barrier', 'cognitive_energy']
    }
    AGENT_16_INTERACTION_PREPARATION = {
        'name': 'interaction_preparation',
        'output_fields': ['preparation_status', 'resources', 'timing'],
        'target_features': ['wm_stability', 'preceding_success']
    }
    AGENT_17_REMAINING_ACTIVITIES = {
        'name': 'remaining_activities',
        'output_fields': ['remaining_tasks', 'priority_order', 'time_estimate'],
        'target_features': ['time_on_task', 'fatigue_level']
    }
    AGENT_18_SIGNATURE_ROUTINE = {
        'name': 'signature_routine',
        'output_fields': ['routine_type', 'strength', 'consistency'],
        'target_features': ['daily_routine_strength', 'self_efficacy']
    }
    AGENT_19_INTERACTION_CONTENT = {
        'name': 'interaction_content',
        'output_fields': ['content_type', 'relevance', 'difficulty'],
        'target_features': ['engagement_level', 'motivation_level']
    }

    # Phase 4: Intervention & Improvement (Agent 20-22)
    AGENT_20_INTERVENTION_PREPARATION = {
        'name': 'intervention_preparation',
        'output_fields': ['intervention_type', 'timing', 'target'],
        'target_features': ['dropout_risk', 'anxiety_level']
    }
    AGENT_21_INTERVENTION_EXECUTION = {
        'name': 'intervention_execution',
        'output_fields': ['execution_status', 'response', 'effectiveness'],
        'target_features': ['engagement_level', 'metacognitive_awareness']
    }
    AGENT_22_MODULE_IMPROVEMENT = {
        'name': 'module_improvement',
        'output_fields': ['improvement_area', 'metrics', 'suggestions'],
        'target_features': ['self_efficacy', 'goal_effectiveness']
    }


# ============================================================================
# 유틸리티 함수
# ============================================================================

def clip(value: float, min_val: float = 0.0, max_val: float = 1.0) -> float:
    """값을 범위 내로 제한"""
    return max(min_val, min(max_val, value))


def normalize_score(value: float, min_val: float, max_val: float) -> float:
    """점수를 0~1 범위로 정규화"""
    if max_val <= min_val:
        return 0.5
    return clip((value - min_val) / (max_val - min_val), 0.0, 1.0)


def normalize_count(count: int, expected_max: int) -> float:
    """카운트를 0~1 범위로 정규화"""
    if expected_max <= 0:
        return 0.0
    return clip(count / expected_max, 0.0, 1.0)


def calculate_rate(success: int, total: int) -> float:
    """성공률 계산 (0~1)"""
    if total <= 0:
        return 0.0
    return clip(success / total, 0.0, 1.0)


# ============================================================================
# 데이터 어댑터 클래스
# ============================================================================

class CalmnessAdapter:
    """
    침착도 데이터 어댑터

    Source Tables:
    - mdl_abessi_today (score, type='오늘목표'/'검토요점'/'주간목표')
    - at_calmness_scores (calm_score, timestamp)
    """

    @staticmethod
    def adapt(raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        침착도 컨텍스트 → StandardFeatures 필드

        Args:
            raw_context: Agent 08 getCalmnessContext() 출력
            {
                'calm_score': 0-100,
                'calmness_level': 'C95'/'C90'/...,
                'daily_goals': {...},
                'review_points': {...}
            }
        """
        calm_score = raw_context.get('calm_score', 50)

        # 0~100 → 0~1 변환
        normalized_calm = normalize_score(calm_score, 0, 100)

        # 레벨 기반 보정
        level = raw_context.get('calmness_level', 'C80')
        level_bonus = {
            'C95': 0.05, 'C90': 0.03, 'C85': 0.01,
            'C80': 0.0, 'C75': -0.02, 'C_crisis': -0.05
        }.get(level, 0.0)

        final_calm = clip(normalized_calm + level_bonus)

        return {
            'calmness_score': final_calm,
            'anxiety_level': 1.0 - final_calm,  # 침착도의 역
            'overload_level': max(0.0, 0.5 - final_calm) * 2  # 침착도 낮으면 과부하 증가
        }


class PomodoroAdapter:
    """
    포모도로 데이터 어댑터

    Source Table:
    - mdl_alt42g_pomodoro_sessions (status, timecreated)
    """

    @staticmethod
    def adapt(raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        포모도로 컨텍스트 → StandardFeatures 필드

        Args:
            raw_context: Agent 09 getLearningManagementContext() 출력
            {
                'pomodoro_completion': 0-100 (완료율 %),
                'total_sessions': int,
                'completed_sessions': int
            }
        """
        completion_rate = raw_context.get('pomodoro_completion', 0)
        total_sessions = raw_context.get('total_sessions', 0)

        # 완료율 정규화 (0~100 → 0~1)
        normalized_completion = normalize_score(completion_rate, 0, 100)

        # 세션 수 기반 참여도 보정
        session_bonus = min(total_sessions / 30, 0.1)  # 30세션 = +0.1

        # 참여도 계산
        engagement = clip(normalized_completion * 0.7 + session_bonus + 0.2)

        return {
            'pomodoro_completion': normalized_completion,
            'engagement_level': engagement,
            'focus_duration': normalize_count(total_sessions * 25, 30 * 25)  # 25분 세션
        }


class ProblemNotesAdapter:
    """
    문제노트 데이터 어댑터

    Source Table:
    - mdl_abessi_messages (contentstype=2)
    - status: 'attempt' (도전노트), 'begin' (준비노트), 'exam'/'complete'/'review' (서술평가)
    """

    @staticmethod
    def adapt(raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        문제노트 컨텍스트 → StandardFeatures 필드

        Args:
            raw_context: Agent 11 getProblemNotesContext() 출력
            {
                'note_statistics': {
                    'attempt_count': int,
                    'preparation_count': int,  # 오답 → 준비노트
                    'essay_count': int,
                    'total_count': int
                },
                'attempt_notes': [...],
                'preparation_notes': [...]
            }
        """
        stats = raw_context.get('note_statistics', {})
        attempt_count = stats.get('attempt_count', 0)
        prep_count = stats.get('preparation_count', 0)  # 오답 지표
        total_count = stats.get('total_count', 0)

        # 오개념 점수: 준비노트(오답) 비율
        misconception = calculate_rate(prep_count, total_count) if total_count > 0 else 0.0

        # 문제 정답률: 1 - 오답률
        accuracy = 1.0 - misconception

        # 시도 횟수 기반 hesitation
        notes = raw_context.get('attempt_notes', [])
        avg_usedtime = 0
        if notes:
            usedtimes = [n.get('usedtime', 0) for n in notes if n.get('usedtime', 0) > 0]
            avg_usedtime = sum(usedtimes) / len(usedtimes) if usedtimes else 0

        # 망설임 지수: 평균 사용시간이 길수록 증가 (10분 = 0.5 기준)
        hesitation = normalize_score(avg_usedtime / 60, 0, 20)

        return {
            'problem_accuracy': accuracy,
            'misconception_score': misconception,
            'attempt_count': attempt_count,
            'revision_count': prep_count,
            'hesitation_index': hesitation,
            'time_on_task': normalize_score(avg_usedtime / 60, 0, 30)  # 30분 기준
        }


class GoalAnalysisAdapter:
    """
    목표 분석 데이터 어댑터

    Source Tables:
    - mdl_alt42g_goal_analysis (effectiveness_score)
    - mdl_alt42g_student_goals (progress, status)
    """

    @staticmethod
    def adapt(raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        목표 분석 컨텍스트 → StandardFeatures 필드

        Args:
            raw_context: Agent 03 getGoalsAnalysisContext() 출력
            {
                'completion_rate': float (0-100),
                'latest_analysis': {'effectiveness_score': float},
                'goals': [...]
            }
        """
        completion_rate = raw_context.get('completion_rate', 0)
        latest = raw_context.get('latest_analysis', {})
        effectiveness = latest.get('effectiveness_score', 0.5)

        # 진행률 계산
        goals = raw_context.get('goals', [])
        if goals:
            total_progress = sum(g.get('progress', 0) for g in goals)
            avg_progress = total_progress / len(goals)
        else:
            avg_progress = 0

        return {
            'goal_progress': normalize_score(avg_progress, 0, 100),
            'goal_effectiveness': clip(effectiveness, 0, 1),
            'goal_completion_rate': normalize_score(completion_rate, 0, 100)
        }


class RestRoutineAdapter:
    """
    휴식 루틴 데이터 어댑터

    Source Table:
    - mdl_abessi_breaktimelog (userid, duration, timecreated)
    """

    @staticmethod
    def adapt(raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        휴식 루틴 컨텍스트 → StandardFeatures 필드

        Args:
            raw_context: Agent 12 getRestRoutineContext() 출력
            {
                'rest_count': int,
                'average_interval': float (분),
                'rest_type': '규칙적 휴식형'/'활동 중심 휴식형'/'집중 몰입형'/'휴식 미사용형',
                'rest_patterns': {...}
            }
        """
        rest_count = raw_context.get('rest_count', 0)
        avg_interval = raw_context.get('average_interval', 90)
        rest_type = raw_context.get('rest_type', 'unknown')

        # 휴식 간격 정규화 (60분 기준 = 0.5)
        interval_normalized = normalize_score(avg_interval, 0, 180)

        # 루틴 강도: 휴식 횟수와 규칙성 기반
        type_bonus = {
            '규칙적 휴식형': 0.3,
            '활동 중심 휴식형': 0.2,
            '집중 몰입형': 0.1,
            '휴식 미사용형': 0.0
        }.get(rest_type, 0.0)

        routine_strength = clip(
            normalize_count(rest_count, 30) * 0.5 +  # 30회/월 = 0.5
            type_bonus +
            0.2  # 기본값
        )

        return {
            'rest_interval': interval_normalized,
            'rest_pattern_type': rest_type,
            'daily_routine_strength': routine_strength
        }


class ActivityPatternAdapter:
    """
    활동 패턴 데이터 어댑터

    Source Table:
    - mdl_alt42_student_activity (main_category, sub_activity, behavior_type)
    """

    @staticmethod
    def adapt(raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        활동 패턴 컨텍스트 → StandardFeatures 필드

        Args:
            raw_context: Agent 04 getProblemActivityContext() 출력
            {
                'recent_activities': [...],
                'activity_patterns': {...},
                'main_categories': {
                    'concept_understanding': int,
                    'type_learning': int,
                    'problem_solving': int,
                    'error_notes': int,
                    'qa': int,
                    'review': int,
                    'pomodoro': int
                }
            }
        """
        categories = raw_context.get('main_categories', {})

        # 개념 이해도: concept_understanding 비율
        total_activities = sum(categories.values())
        concept_count = categories.get('concept_understanding', 0)
        error_count = categories.get('error_notes', 0)
        review_count = categories.get('review', 0)

        # 개념 숙달도
        mastery = calculate_rate(concept_count + review_count, total_activities)

        # 오류 패턴 매칭
        error_match = calculate_rate(error_count, total_activities)

        return {
            'concept_mastery': clip(mastery * 1.2),  # 약간 가중
            'error_pattern_match': error_match
        }


# ============================================================================
# 통합 데이터 수집기
# ============================================================================

class QuantumDataCollector:
    """
    22개 에이전트 출력을 수집하여 StandardFeatures로 변환
    """

    def __init__(self, student_id: int):
        self.student_id = student_id
        self.features = StandardFeatures(student_id=student_id)
        self.adapters = {
            'calmness': CalmnessAdapter(),
            'pomodoro': PomodoroAdapter(),
            'problem_notes': ProblemNotesAdapter(),
            'goal_analysis': GoalAnalysisAdapter(),
            'rest_routine': RestRoutineAdapter(),
            'activity_pattern': ActivityPatternAdapter()
        }
        self.last_update = datetime.now()

    def collect_from_agent(self, agent_id: int, raw_context: Dict[str, Any]) -> Dict[str, float]:
        """
        특정 에이전트 출력을 수집하고 변환

        Args:
            agent_id: 에이전트 번호 (1-22)
            raw_context: 에이전트의 prepareRuleContext() 출력

        Returns:
            변환된 feature 딕셔너리
        """
        updated_features = {}

        # 에이전트별 어댑터 매핑
        adapter_map = {
            3: ('goal_analysis', self.adapters['goal_analysis']),
            4: ('activity_pattern', self.adapters['activity_pattern']),
            8: ('calmness', self.adapters['calmness']),
            9: ('pomodoro', self.adapters['pomodoro']),
            11: ('problem_notes', self.adapters['problem_notes']),
            12: ('rest_routine', self.adapters['rest_routine']),
        }

        if agent_id in adapter_map:
            name, adapter = adapter_map[agent_id]
            try:
                updated_features = adapter.adapt(raw_context)
                self._apply_features(updated_features)
            except Exception as e:
                print(f"[ERROR] Agent {agent_id} adapter error: {e} [File: _quantum_data_interface.py]")

        return updated_features

    def collect_all(self, agent_contexts: Dict[int, Dict[str, Any]]) -> StandardFeatures:
        """
        모든 에이전트 출력을 한번에 수집

        Args:
            agent_contexts: {agent_id: raw_context} 딕셔너리

        Returns:
            통합된 StandardFeatures
        """
        for agent_id, context in agent_contexts.items():
            self.collect_from_agent(agent_id, context)

        self.features.timestamp = datetime.now()
        self.last_update = datetime.now()
        return self.features

    def _apply_features(self, updates: Dict[str, Any]):
        """업데이트를 features 객체에 적용"""
        for key, value in updates.items():
            if hasattr(self.features, key):
                setattr(self.features, key, value)

    def get_features(self) -> StandardFeatures:
        """현재 features 반환"""
        return self.features

    def get_wavefunction_inputs(self) -> Dict[str, Dict[str, float]]:
        """
        13종 파동함수별 입력 데이터 생성

        Returns:
            {wavefunction_name: {input_field: value}}
        """
        f = self.features

        return {
            'psi_core': {
                'alpha_hint': f.concept_mastery * f.problem_accuracy,
                'beta_hint': f.misconception_score,
                'gamma_hint': 1.0 - f.concept_mastery - f.misconception_score
            },
            'psi_align': {
                'goal_alignment': f.goal_progress,
                'effectiveness': f.goal_effectiveness,
                'teacher_confirm': f.teacher_confirm
            },
            'psi_fluct': {
                'hesitation': f.hesitation_index,
                'revision_rate': normalize_count(f.revision_count, 10)
            },
            'psi_tunnel': {
                'E_cog': f.cognitive_energy,
                'B_concept': f.concept_barrier
            },
            'psi_WM': {
                'stability': f.wm_stability,
                'overload': f.overload_level
            },
            'psi_affect': {
                'mu_calm': f.calmness_score,
                'nu_tension': f.tension_level,
                'xi_overload': f.overload_level
            },
            'psi_routine': {
                'R_daily': f.daily_routine_strength,
                'rest_interval': f.rest_interval
            },
            'psi_engage': {
                'engagement': f.engagement_level,
                'dropout_risk': f.dropout_risk,
                'pomodoro': f.pomodoro_completion
            },
            'psi_concept': {
                'mastery': f.concept_mastery,
                'error_pattern': f.error_pattern_match
            },
            'psi_cascade': {
                'preceding_success': f.preceding_success
            },
            'psi_meta': {
                'self_efficacy': f.self_efficacy,
                'metacognition': f.metacognitive_awareness
            },
            'psi_context': {
                'time_of_day': f.time_of_day,
                'fatigue': f.fatigue_level,
                'motivation': f.motivation_level
            },
            'psi_predict': {
                'current_alpha': f.concept_mastery * f.problem_accuracy,
                'alignment': f.goal_progress
            }
        }


# ============================================================================
# 70D → 8D 차원 축소 변환기
# ============================================================================

class DimensionReducer:
    """
    70D 파동함수 상태 → 8D StateVector 변환

    8D StateVector (현재 Production):
    1. cognitive_clarity (인지 명확도)
    2. emotional_stability (정서 안정도)
    3. engagement_level (참여도)
    4. concept_mastery (개념 숙달도)
    5. routine_strength (루틴 강도)
    6. metacognitive_awareness (메타인지)
    7. dropout_risk (이탈 위험도)
    8. intervention_readiness (개입 준비도)
    """

    # 70D→8D 변환 가중치 행렬 (각 8D 차원에 대한 70D 기여도)
    TRANSFORM_WEIGHTS = {
        'cognitive_clarity': {
            'concept_mastery': 0.35,
            'problem_accuracy': 0.30,
            'wm_stability': 0.20,
            'hesitation_index': -0.15  # 음의 기여
        },
        'emotional_stability': {
            'calmness_score': 0.40,
            'anxiety_level': -0.30,
            'tension_level': -0.15,
            'overload_level': -0.15
        },
        'engagement_level': {
            'engagement_level': 0.35,
            'pomodoro_completion': 0.25,
            'focus_duration': 0.20,
            'motivation_level': 0.20
        },
        'concept_mastery': {
            'concept_mastery': 0.40,
            'goal_effectiveness': 0.30,
            'error_pattern_match': -0.15,
            'misconception_score': -0.15
        },
        'routine_strength': {
            'daily_routine_strength': 0.40,
            'rest_interval': 0.20,
            'pomodoro_completion': 0.25,
            'goal_completion_rate': 0.15
        },
        'metacognitive_awareness': {
            'self_efficacy': 0.40,
            'metacognitive_awareness': 0.35,
            'goal_progress': 0.25
        },
        'dropout_risk': {
            'dropout_risk': 0.40,
            'engagement_level': -0.25,
            'anxiety_level': 0.20,
            'fatigue_level': 0.15
        },
        'intervention_readiness': {
            'calmness_score': 0.25,
            'engagement_level': 0.25,
            'motivation_level': 0.25,
            'fatigue_level': -0.25
        }
    }

    @classmethod
    def transform(cls, features: StandardFeatures) -> Dict[str, float]:
        """
        StandardFeatures → 8D StateVector 변환

        Returns:
            8D StateVector 딕셔너리
        """
        state_vector = {}
        feature_dict = features.to_dict()

        for dim_name, weights in cls.TRANSFORM_WEIGHTS.items():
            value = 0.0
            total_weight = 0.0

            for feature_name, weight in weights.items():
                if feature_name in feature_dict:
                    feat_val = feature_dict[feature_name]
                    if isinstance(feat_val, (int, float)):
                        value += feat_val * weight
                        total_weight += abs(weight)

            # 정규화
            if total_weight > 0:
                state_vector[dim_name] = clip(value / total_weight + 0.5, 0.0, 1.0)
            else:
                state_vector[dim_name] = 0.5

        return state_vector

    @classmethod
    def transform_to_list(cls, features: StandardFeatures) -> List[float]:
        """8D StateVector를 리스트로 반환"""
        sv = cls.transform(features)
        return [
            sv['cognitive_clarity'],
            sv['emotional_stability'],
            sv['engagement_level'],
            sv['concept_mastery'],
            sv['routine_strength'],
            sv['metacognitive_awareness'],
            sv['dropout_risk'],
            sv['intervention_readiness']
        ]


# ============================================================================
# 글로벌 인스턴스 관리
# ============================================================================

_DATA_COLLECTORS: Dict[int, QuantumDataCollector] = {}


def get_data_collector(student_id: int) -> QuantumDataCollector:
    """학생별 데이터 수집기 획득"""
    if student_id not in _DATA_COLLECTORS:
        _DATA_COLLECTORS[student_id] = QuantumDataCollector(student_id)
    return _DATA_COLLECTORS[student_id]


def reset_data_collector(student_id: int):
    """학생의 데이터 수집기 리셋"""
    if student_id in _DATA_COLLECTORS:
        del _DATA_COLLECTORS[student_id]


# ============================================================================
# 테스트 함수
# ============================================================================

def run_interface_test():
    """Phase 7.1 데이터 인터페이스 테스트"""
    print("=" * 60)
    print("Phase 7.1: Quantum Data Interface 테스트")
    print("=" * 60)

    # 1. 데이터 수집기 생성
    print("\n[1] 데이터 수집기 생성")
    collector = get_data_collector(student_id=12345)
    print(f"  - 학생 ID: {collector.student_id}")

    # 2. 모의 에이전트 출력 생성
    print("\n[2] 모의 에이전트 출력 테스트")

    # Agent 08 침착도 테스트
    calmness_context = {
        'calm_score': 85,
        'calmness_level': 'C85',
        'daily_goals': {},
        'review_points': {}
    }
    result = collector.collect_from_agent(8, calmness_context)
    print(f"  - Agent 08 (침착도): {result}")

    # Agent 09 포모도로 테스트
    pomodoro_context = {
        'pomodoro_completion': 75.0,
        'total_sessions': 20,
        'completed_sessions': 15
    }
    result = collector.collect_from_agent(9, pomodoro_context)
    print(f"  - Agent 09 (포모도로): {result}")

    # Agent 11 문제노트 테스트
    problem_context = {
        'note_statistics': {
            'attempt_count': 30,
            'preparation_count': 5,
            'essay_count': 10,
            'total_count': 45
        },
        'attempt_notes': [
            {'usedtime': 600},  # 10분
            {'usedtime': 480}   # 8분
        ]
    }
    result = collector.collect_from_agent(11, problem_context)
    print(f"  - Agent 11 (문제노트): {result}")

    # Agent 03 목표분석 테스트
    goal_context = {
        'completion_rate': 60.0,
        'latest_analysis': {'effectiveness_score': 0.75},
        'goals': [
            {'progress': 80},
            {'progress': 40}
        ]
    }
    result = collector.collect_from_agent(3, goal_context)
    print(f"  - Agent 03 (목표분석): {result}")

    # 3. 통합 Features 확인
    print("\n[3] 통합 StandardFeatures")
    features = collector.get_features()
    print(f"  - concept_mastery: {features.concept_mastery:.3f}")
    print(f"  - problem_accuracy: {features.problem_accuracy:.3f}")
    print(f"  - calmness_score: {features.calmness_score:.3f}")
    print(f"  - engagement_level: {features.engagement_level:.3f}")
    print(f"  - pomodoro_completion: {features.pomodoro_completion:.3f}")
    print(f"  - goal_progress: {features.goal_progress:.3f}")

    # 4. 파동함수 입력 생성
    print("\n[4] 13종 파동함수 입력 생성")
    wf_inputs = collector.get_wavefunction_inputs()
    for wf_name, inputs in list(wf_inputs.items())[:5]:  # 처음 5개만
        print(f"  - {wf_name}: {inputs}")

    # 5. 70D → 8D 변환
    print("\n[5] 70D → 8D 차원 축소")
    state_8d = DimensionReducer.transform(features)
    for dim_name, value in state_8d.items():
        print(f"  - {dim_name}: {value:.3f}")

    # 6. 8D 리스트 형태
    print("\n[6] 8D StateVector (리스트)")
    state_list = DimensionReducer.transform_to_list(features)
    print(f"  - {state_list}")

    print("\n" + "=" * 60)
    print("Phase 7.1 테스트 완료!")
    print("=" * 60)

    return collector


# 직접 실행 시
if __name__ == "__main__":
    run_interface_test()


# ============================================================================
# DB 참조 정보
# ============================================================================
"""
관련 DB 테이블:

1. 침착도 데이터:
   - mdl_abessi_today (score, type, userid, timecreated)
   - at_calmness_scores (calm_score, userid, timestamp)

2. 포모도로 데이터:
   - mdl_alt42g_pomodoro_sessions (userid, status, timecreated)
     status: 'completed' | 'incomplete'

3. 문제노트 데이터:
   - mdl_abessi_messages (contentstype=2)
     status: 'attempt' | 'begin' | 'exam' | 'complete' | 'review'
     fields: nstroke, tlaststroke, timecreated, usedtime, contentstitle

4. 목표 분석:
   - mdl_alt42g_goal_analysis (effectiveness_score, userid)
   - mdl_alt42g_student_goals (goal_type, status, progress, userid)

5. 휴식 루틴:
   - mdl_abessi_breaktimelog (userid, duration, timecreated)

6. 활동 패턴:
   - mdl_alt42_student_activity (main_category, sub_activity, behavior_type)
     main_category: concept_understanding, type_learning, problem_solving,
                   error_notes, qa, review, pomodoro

File: _quantum_data_interface.py
Location: /Holarchy/0 Docs/holons/
"""
