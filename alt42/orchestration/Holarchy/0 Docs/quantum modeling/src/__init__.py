# -*- coding: utf-8 -*-
"""
Quantum Modeling - 실시간 AI 튜터를 위한 양자 학습 모델

이 패키지는 학생의 학습 상태를 양자역학 개념으로 모델링합니다.

주요 모듈:
- wavefunctions: 13종 파동함수 계산
- ide: 개입 의사결정 엔진 (7단계 파이프라인)
- state: 64차원 StudentStateVector
- pipeline: Brain/Mind/Mouth 실시간 파이프라인

참조 문서:
- 00-INDEX.md: 문서 허브
- IMPLEMENTATION_GUIDE.md: 구현 가이드
- quantum-learning-model.md: 이론 기반

버전: 0.1.0
"""

__version__ = "0.1.0"
__author__ = "AI Agent Integration Team"

from .wavefunctions import *
from .ide import *
from .state import *

