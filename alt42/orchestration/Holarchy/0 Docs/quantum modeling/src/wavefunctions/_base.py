# -*- coding: utf-8 -*-
"""
파동함수 기본 클래스

모든 파동함수가 상속받는 추상 기본 클래스.

참조: IMPLEMENTATION_GUIDE.md > §3.2
"""

from abc import ABC, abstractmethod
from dataclasses import dataclass, field
from typing import Dict, Any, List
from datetime import datetime
import numpy as np
import sys


@dataclass
class WavefunctionResult:
    """
    파동함수 계산 결과
    
    Attributes:
        name: 파동함수 이름 (예: "psi_core")
        value: 계산된 값 (numpy 배열)
        confidence: 계산 신뢰도 (0.0 ~ 1.0)
        timestamp: 계산 시점 (ISO 형식)
        metadata: 추가 메타데이터
    """
    name: str
    value: np.ndarray
    confidence: float
    timestamp: str = field(default_factory=lambda: datetime.now().isoformat())
    metadata: Dict[str, Any] = field(default_factory=dict)
    
    def to_dict(self) -> Dict[str, Any]:
        """딕셔너리로 변환 (JSON 직렬화용)"""
        return {
            "name": self.name,
            "value": self.value.tolist(),
            "confidence": self.confidence,
            "timestamp": self.timestamp,
            "metadata": self.metadata
        }


class BaseWavefunction(ABC):
    """
    모든 파동함수의 기본 클래스
    
    상속받아 calculate() 메서드를 구현해야 합니다.
    
    Example:
        class PsiCore(BaseWavefunction):
            def __init__(self):
                super().__init__("psi_core")
            
            def calculate(self, student_data):
                # 계산 로직
                ...
    """
    
    def __init__(self, name: str):
        """
        Args:
            name: 파동함수 이름 (예: "psi_core")
        """
        self.name = name
        self._file_path = f"quantum modeling/src/wavefunctions/_{name}.py"
    
    @abstractmethod
    def calculate(self, student_data: Dict[str, Any]) -> WavefunctionResult:
        """
        파동함수 계산 (추상 메서드)
        
        Args:
            student_data: 학생 데이터 (에이전트 출력값)
        
        Returns:
            WavefunctionResult
        
        Raises:
            ValueError: 필수 데이터 누락 시
            RuntimeError: 계산 실패 시
        """
        pass
    
    def validate_input(self, data: Dict[str, Any], required_keys: List[str]) -> bool:
        """
        입력 데이터 검증
        
        Args:
            data: 입력 데이터
            required_keys: 필수 키 목록
        
        Returns:
            True if valid
        
        Raises:
            ValueError: 필수 키 누락 시
        """
        missing = [k for k in required_keys if k not in data or data[k] is None]
        if missing:
            raise ValueError(
                f"[{self._file_path}:L{self._get_line()}] "
                f"필수 키 누락: {', '.join(missing)}"
            )
        return True
    
    def _normalize(self, value: float, min_val: float = 0.0, max_val: float = 1.0) -> float:
        """값을 지정된 범위로 정규화"""
        return max(min_val, min(max_val, value))
    
    def _get_line(self) -> int:
        """현재 라인 번호 반환"""
        return sys._getframe(2).f_lineno
    
    def _calculate_confidence(self, data: Dict[str, Any], required_keys: List[str]) -> float:
        """
        데이터 완전성 기반 신뢰도 계산
        
        Args:
            data: 입력 데이터
            required_keys: 필수 키 목록
        
        Returns:
            신뢰도 (0.0 ~ 1.0)
        """
        if not required_keys:
            return 1.0
        present = sum(1 for k in required_keys if k in data and data[k] is not None)
        return present / len(required_keys)
    
    def _create_error(self, message: str) -> RuntimeError:
        """표준 형식의 에러 생성"""
        return RuntimeError(
            f"[{self._file_path}:L{self._get_line()}] {message}"
        )

