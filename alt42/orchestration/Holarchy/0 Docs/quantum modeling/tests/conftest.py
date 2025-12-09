# -*- coding: utf-8 -*-
"""
pytest 설정 및 공통 픽스처

모든 테스트에서 사용할 수 있는 공통 픽스처를 정의합니다.
"""

import pytest
import sys
from pathlib import Path

# src 폴더를 Python 경로에 추가
src_path = Path(__file__).parent.parent / "src"
sys.path.insert(0, str(src_path))


# =============================================================================
# 샘플 데이터 픽스처
# =============================================================================

@pytest.fixture
def sample_student_data():
    """기본 학생 데이터 샘플"""
    return {
        'correct_rate': 0.7,
        'misconception_score': 0.2,
        'hesitation_time': 10.0,
        'concept_mastery': 0.6,
        'revision_count': 2,
        'anxiety_level': 0.3,
        'calmness_score': 0.7,
        'error_pattern_match': 0.15,
        'teacher_confirm': 0.8,
        'feedback_negative': 0.1
    }


@pytest.fixture
def sample_wavefunctions():
    """13종 파동함수 샘플 결과"""
    return {
        'psi_core': {'alpha': 0.6, 'beta': 0.25, 'gamma': 0.15},
        'psi_align': {'value': 0.7},
        'psi_fluct': {'value': 0.4},
        'psi_tunnel': {'probability': 0.35},
        'psi_wm': {'stability': 0.8},
        'psi_affect': {'calm': 0.7, 'tension': 0.2, 'overload': 0.1},
        'psi_routine': {'daily': 0.6, 'weekly': 0.5, 'long': 0.4},
        'psi_engage': {'focus': 0.7, 'drift': 0.2, 'drop': 0.1},
        'psi_concept': {'entanglement': 0.5},
        'psi_cascade': {'probability': 0.3},
        'psi_meta': {'can_do': 0.65, 'uncertain': 0.35},
        'psi_context': {'value': 0.6},
        'psi_predict': {'cp': 0.45, 'dalpha_dt': 0.02}
    }


@pytest.fixture
def sample_state_vector():
    """64차원 StudentStateVector 샘플"""
    import numpy as np
    return {
        # 인지 차원 (16)
        'concept_mastery': 0.6,
        'procedural_fluency': 0.5,
        'cognitive_load': 0.4,
        'attention_level': 0.7,
        'working_memory': 0.6,
        'metacognition': 0.5,
        'transfer_ability': 0.4,
        'problem_representation': 0.5,
        # ... (나머지 차원은 0.5로 기본값)
        
        # 정서 차원 (16)
        'motivation': 0.6,
        'self_efficacy': 0.5,
        'confidence': 0.6,
        'anxiety': 0.3,
        'frustration': 0.2,
        
        # 행동 차원 (16)
        'engagement_behavior': 0.7,
        'persistence': 0.6,
        
        # 컨텍스트 차원 (16)
        'time_pressure': 0.4,
        'distraction_level': 0.3
    }


@pytest.fixture
def high_alpha_student():
    """높은 α (정답 확률) 학생 데이터"""
    return {
        'correct_rate': 0.95,
        'misconception_score': 0.05,
        'hesitation_time': 2.0,
        'concept_mastery': 0.9,
        'revision_count': 0,
        'anxiety_level': 0.1
    }


@pytest.fixture
def high_beta_student():
    """높은 β (오개념) 학생 데이터"""
    return {
        'correct_rate': 0.3,
        'misconception_score': 0.8,
        'hesitation_time': 5.0,
        'concept_mastery': 0.3,
        'revision_count': 1,
        'error_pattern_match': 0.7
    }


@pytest.fixture
def high_gamma_student():
    """높은 γ (혼란) 학생 데이터"""
    return {
        'correct_rate': 0.4,
        'misconception_score': 0.3,
        'hesitation_time': 45.0,
        'concept_mastery': 0.4,
        'revision_count': 5,
        'anxiety_level': 0.7
    }

