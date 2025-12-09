# -*- coding: utf-8 -*-
"""
Phase 4: Temporal Entanglement (시간적 얽힘)
===========================================

학습 이력의 시간적 연결과 잔류파 효과를 양자역학적으로 모델링합니다.

핵심 개념:
---------
1. 잔류파 감쇠 (Residual Wave Decay)
   - 수식: A_residual(t) = A_0 × e^(-λt)
   - λ = 0.3 (주간 감쇠율)
   - 에빙하우스 망각곡선의 양자역학적 해석

2. 누적 진폭 추적 (Cumulative Amplitude Tracking)
   - 수식: A_total = Σ(A_i × e^(-λ(t - t_i)))
   - 반복 학습에 의한 기억 강화 효과

3. 비국소적 상관관계 (Non-local Correlation)
   - 시간적으로 분리된 학습 이벤트 간의 양자 얽힘
   - 관련 개념들이 함께 활성화되는 연결 효과

Python 3.6+ 호환

Author: Quantum Learning System
Version: 1.0.0
"""

from __future__ import annotations

import math
import time
from typing import Dict, List, Tuple, Optional, Any

# ============================================================================
# Constants
# ============================================================================

# 기본 감쇠율 (주간 기준, λ = 0.3)
DEFAULT_DECAY_RATE = 0.3

# 시간 단위 변환 (초 → 주)
SECONDS_PER_WEEK = 7 * 24 * 60 * 60

# 최소 유효 진폭 (이 이하는 무시)
MIN_AMPLITUDE_THRESHOLD = 0.001

# 최대 이력 저장 수
MAX_HISTORY_SIZE = 1000

# 비국소적 상관관계 전파율
NON_LOCAL_PROPAGATION_RATE = 0.15


# ============================================================================
# TemporalEvent Class
# ============================================================================

class TemporalEvent(object):
    """
    시간적 이벤트를 나타내는 클래스.

    각 학습 이벤트는 특정 시점에 발생한 진폭을 가지며,
    시간이 지남에 따라 감쇠합니다.

    Attributes:
        event_id (str): 이벤트 고유 식별자
        timestamp (float): 이벤트 발생 시간 (Unix timestamp)
        initial_amplitude (float): 초기 진폭 (0.0 ~ 1.0)
        agent_id (str): 관련 에이전트 ID
        context (Dict): 이벤트 컨텍스트 정보
        tags (List[str]): 관련 태그 (개념, 주제 등)
    """

    def __init__(
        self,
        event_id,        # type: str
        timestamp,       # type: float
        initial_amplitude,  # type: float
        agent_id,        # type: str
        context=None,    # type: Optional[Dict[str, Any]]
        tags=None        # type: Optional[List[str]]
    ):
        # type: (...) -> None
        """TemporalEvent 초기화."""
        self.event_id = event_id
        self.timestamp = timestamp
        self.initial_amplitude = max(0.0, min(1.0, initial_amplitude))
        self.agent_id = agent_id
        self.context = context if context else {}
        self.tags = tags if tags else []

    def get_decayed_amplitude(
        self,
        current_time,    # type: float
        decay_rate=DEFAULT_DECAY_RATE  # type: float
    ):
        # type: (...) -> float
        """
        현재 시점에서의 감쇠된 진폭을 계산합니다.

        수식: A_residual(t) = A_0 × e^(-λt)

        Args:
            current_time: 현재 시간 (Unix timestamp)
            decay_rate: 감쇠율 λ (주간 기준)

        Returns:
            감쇠된 진폭 값 (0.0 ~ 1.0)
        """
        # 경과 시간 (주 단위)
        elapsed_weeks = (current_time - self.timestamp) / SECONDS_PER_WEEK

        if elapsed_weeks < 0:
            # 미래 이벤트는 현재 진폭 그대로
            return self.initial_amplitude

        # 지수 감쇠 적용
        decayed = self.initial_amplitude * math.exp(-decay_rate * elapsed_weeks)

        # 최소 임계값 이하는 0으로 처리
        if decayed < MIN_AMPLITUDE_THRESHOLD:
            return 0.0

        return decayed

    def to_dict(self):
        # type: () -> Dict[str, Any]
        """딕셔너리로 변환."""
        return {
            'event_id': self.event_id,
            'timestamp': self.timestamp,
            'initial_amplitude': self.initial_amplitude,
            'agent_id': self.agent_id,
            'context': self.context,
            'tags': self.tags
        }

    @classmethod
    def from_dict(cls, data):
        # type: (Dict[str, Any]) -> TemporalEvent
        """딕셔너리에서 생성."""
        return cls(
            event_id=data['event_id'],
            timestamp=data['timestamp'],
            initial_amplitude=data['initial_amplitude'],
            agent_id=data['agent_id'],
            context=data.get('context', {}),
            tags=data.get('tags', [])
        )

    def __repr__(self):
        # type: () -> str
        return (
            "TemporalEvent(id={}, agent={}, amp={:.3f}, tags={})"
            .format(self.event_id, self.agent_id, self.initial_amplitude, self.tags)
        )


# ============================================================================
# TemporalEntanglement Class
# ============================================================================

class TemporalEntanglement(object):
    """
    시간적 얽힘을 관리하는 메인 클래스.

    학습 이벤트들의 시간적 연결과 잔류파 효과를 추적합니다.

    주요 기능:
    ---------
    1. 이벤트 기록 및 관리
    2. 잔류파 감쇠 계산
    3. 누적 진폭 추적
    4. 비국소적 상관관계 분석

    Attributes:
        events (Dict[str, List[TemporalEvent]]): 에이전트별 이벤트 목록
        tag_events (Dict[str, List[str]]): 태그별 이벤트 ID 매핑
        decay_rate (float): 감쇠율 λ
        max_history (int): 최대 이력 수
    """

    def __init__(
        self,
        decay_rate=DEFAULT_DECAY_RATE,  # type: float
        max_history=MAX_HISTORY_SIZE    # type: int
    ):
        # type: (...) -> None
        """TemporalEntanglement 초기화."""
        self.decay_rate = decay_rate
        self.max_history = max_history

        # 에이전트별 이벤트 저장소
        # type: Dict[str, List[TemporalEvent]]
        self.events = {}

        # 태그별 이벤트 ID 매핑
        # type: Dict[str, List[str]]
        self.tag_events = {}

        # 전체 이벤트 인덱스 (event_id → TemporalEvent)
        # type: Dict[str, TemporalEvent]
        self._event_index = {}

        # 이벤트 카운터 (ID 생성용)
        self._event_counter = 0

    def record_event(
        self,
        agent_id,        # type: str
        amplitude,       # type: float
        context=None,    # type: Optional[Dict[str, Any]]
        tags=None,       # type: Optional[List[str]]
        timestamp=None   # type: Optional[float]
    ):
        # type: (...) -> TemporalEvent
        """
        새로운 학습 이벤트를 기록합니다.

        Args:
            agent_id: 에이전트 ID
            amplitude: 초기 진폭 (학습 강도)
            context: 이벤트 컨텍스트
            tags: 관련 태그 (개념, 주제 등)
            timestamp: 이벤트 시간 (None이면 현재 시간)

        Returns:
            생성된 TemporalEvent
        """
        # 이벤트 ID 생성
        self._event_counter += 1
        event_id = "TE_{:08d}".format(self._event_counter)

        # 타임스탬프 설정
        if timestamp is None:
            timestamp = time.time()

        # 이벤트 생성
        event = TemporalEvent(
            event_id=event_id,
            timestamp=timestamp,
            initial_amplitude=amplitude,
            agent_id=agent_id,
            context=context,
            tags=tags
        )

        # 에이전트별 저장
        if agent_id not in self.events:
            self.events[agent_id] = []
        self.events[agent_id].append(event)

        # 최대 이력 초과 시 오래된 이벤트 제거
        if len(self.events[agent_id]) > self.max_history:
            old_event = self.events[agent_id].pop(0)
            self._remove_event_references(old_event)

        # 태그별 인덱싱
        if tags:
            for tag in tags:
                if tag not in self.tag_events:
                    self.tag_events[tag] = []
                self.tag_events[tag].append(event_id)

        # 전체 인덱스에 추가
        self._event_index[event_id] = event

        return event

    def _remove_event_references(self, event):
        # type: (TemporalEvent) -> None
        """이벤트 참조를 제거합니다."""
        # 태그 인덱스에서 제거
        for tag in event.tags:
            if tag in self.tag_events:
                if event.event_id in self.tag_events[tag]:
                    self.tag_events[tag].remove(event.event_id)
                if not self.tag_events[tag]:
                    del self.tag_events[tag]

        # 전체 인덱스에서 제거
        if event.event_id in self._event_index:
            del self._event_index[event.event_id]

    def get_cumulative_amplitude(
        self,
        agent_id,        # type: str
        current_time=None,  # type: Optional[float]
        tags_filter=None    # type: Optional[List[str]]
    ):
        # type: (...) -> float
        """
        에이전트의 누적 진폭을 계산합니다.

        수식: A_total = Σ(A_i × e^(-λ(t - t_i)))

        Args:
            agent_id: 에이전트 ID
            current_time: 현재 시간 (None이면 현재)
            tags_filter: 특정 태그로 필터링

        Returns:
            누적 진폭 값
        """
        if current_time is None:
            current_time = time.time()

        if agent_id not in self.events:
            return 0.0

        total = 0.0
        for event in self.events[agent_id]:
            # 태그 필터 적용
            if tags_filter:
                if not any(tag in event.tags for tag in tags_filter):
                    continue

            # 감쇠된 진폭 합산
            decayed = event.get_decayed_amplitude(current_time, self.decay_rate)
            total += decayed

        return total

    def get_all_cumulative_amplitudes(
        self,
        current_time=None  # type: Optional[float]
    ):
        # type: (...) -> Dict[str, float]
        """
        모든 에이전트의 누적 진폭을 계산합니다.

        Args:
            current_time: 현재 시간 (None이면 현재)

        Returns:
            에이전트별 누적 진폭 딕셔너리
        """
        if current_time is None:
            current_time = time.time()

        result = {}
        for agent_id in self.events:
            result[agent_id] = self.get_cumulative_amplitude(agent_id, current_time)

        return result

    def calculate_residual_wave(
        self,
        initial_amplitude,  # type: float
        elapsed_weeks       # type: float
    ):
        # type: (...) -> float
        """
        잔류파 감쇠를 계산합니다.

        수식: A_residual(t) = A_0 × e^(-λt)

        Args:
            initial_amplitude: 초기 진폭 A_0
            elapsed_weeks: 경과 시간 (주 단위)

        Returns:
            감쇠된 진폭 값
        """
        if elapsed_weeks < 0:
            return initial_amplitude

        decayed = initial_amplitude * math.exp(-self.decay_rate * elapsed_weeks)

        if decayed < MIN_AMPLITUDE_THRESHOLD:
            return 0.0

        return decayed

    def get_non_local_correlation(
        self,
        source_agent_id,    # type: str
        target_agent_id,    # type: str
        current_time=None   # type: Optional[float]
    ):
        # type: (...) -> float
        """
        두 에이전트 간의 비국소적 상관관계를 계산합니다.

        시간적으로 분리된 이벤트들 사이의 양자 얽힘 효과입니다.
        공통 태그를 가진 이벤트들이 많을수록 상관관계가 높아집니다.

        Args:
            source_agent_id: 소스 에이전트 ID
            target_agent_id: 타겟 에이전트 ID
            current_time: 현재 시간 (None이면 현재)

        Returns:
            상관관계 강도 (0.0 ~ 1.0)
        """
        if current_time is None:
            current_time = time.time()

        source_events = self.events.get(source_agent_id, [])
        target_events = self.events.get(target_agent_id, [])

        if not source_events or not target_events:
            return 0.0

        # 공통 태그 기반 상관관계 계산
        correlation_sum = 0.0
        pair_count = 0

        for s_event in source_events:
            s_amp = s_event.get_decayed_amplitude(current_time, self.decay_rate)
            if s_amp < MIN_AMPLITUDE_THRESHOLD:
                continue

            for t_event in target_events:
                t_amp = t_event.get_decayed_amplitude(current_time, self.decay_rate)
                if t_amp < MIN_AMPLITUDE_THRESHOLD:
                    continue

                # 공통 태그 찾기
                common_tags = set(s_event.tags) & set(t_event.tags)
                if not common_tags:
                    continue

                # 태그 기반 상관관계
                tag_factor = len(common_tags) / max(
                    len(s_event.tags), len(t_event.tags), 1
                )

                # 시간적 근접성 (가까울수록 강한 상관)
                time_diff = abs(s_event.timestamp - t_event.timestamp)
                time_factor = math.exp(-time_diff / SECONDS_PER_WEEK)

                # 진폭 기반 가중치
                amplitude_factor = s_amp * t_amp

                correlation = (
                    tag_factor * 0.4 +
                    time_factor * 0.3 +
                    amplitude_factor * 0.3
                )

                correlation_sum += correlation
                pair_count += 1

        if pair_count == 0:
            return 0.0

        # 정규화 (0.0 ~ 1.0)
        avg_correlation = correlation_sum / pair_count
        return min(1.0, avg_correlation)

    def propagate_non_local_effect(
        self,
        source_agent_id,    # type: str
        amplitude_boost,    # type: float
        current_time=None   # type: Optional[float]
    ):
        # type: (...) -> Dict[str, float]
        """
        비국소적 효과를 관련 에이전트들에게 전파합니다.

        소스 에이전트의 활성화가 연결된 에이전트들에게
        파동 함수 붕괴처럼 즉각적으로 영향을 미칩니다.

        Args:
            source_agent_id: 소스 에이전트 ID
            amplitude_boost: 전파할 진폭 부스트
            current_time: 현재 시간 (None이면 현재)

        Returns:
            에이전트별 전파된 부스트 양
        """
        if current_time is None:
            current_time = time.time()

        propagated = {}

        for target_agent_id in self.events:
            if target_agent_id == source_agent_id:
                continue

            correlation = self.get_non_local_correlation(
                source_agent_id, target_agent_id, current_time
            )

            if correlation > MIN_AMPLITUDE_THRESHOLD:
                boost = amplitude_boost * correlation * NON_LOCAL_PROPAGATION_RATE
                propagated[target_agent_id] = boost

        return propagated

    def get_tag_amplitude(
        self,
        tag,             # type: str
        current_time=None  # type: Optional[float]
    ):
        # type: (...) -> float
        """
        특정 태그(개념)의 총 누적 진폭을 계산합니다.

        Args:
            tag: 태그 (개념, 주제)
            current_time: 현재 시간 (None이면 현재)

        Returns:
            태그의 누적 진폭
        """
        if current_time is None:
            current_time = time.time()

        if tag not in self.tag_events:
            return 0.0

        total = 0.0
        for event_id in self.tag_events[tag]:
            event = self._event_index.get(event_id)
            if event:
                total += event.get_decayed_amplitude(current_time, self.decay_rate)

        return total

    def get_strongest_tags(
        self,
        top_n=10,         # type: int
        current_time=None  # type: Optional[float]
    ):
        # type: (...) -> List[Tuple[str, float]]
        """
        가장 강한 태그(개념)들을 반환합니다.

        Args:
            top_n: 상위 N개
            current_time: 현재 시간 (None이면 현재)

        Returns:
            (태그, 진폭) 튜플 리스트 (진폭 내림차순)
        """
        if current_time is None:
            current_time = time.time()

        tag_amplitudes = []
        for tag in self.tag_events:
            amp = self.get_tag_amplitude(tag, current_time)
            if amp > MIN_AMPLITUDE_THRESHOLD:
                tag_amplitudes.append((tag, amp))

        # 진폭 내림차순 정렬
        tag_amplitudes.sort(key=lambda x: x[1], reverse=True)

        return tag_amplitudes[:top_n]

    def get_learning_momentum(
        self,
        agent_id,         # type: str
        window_weeks=4,   # type: int
        current_time=None  # type: Optional[float]
    ):
        # type: (...) -> Dict[str, float]
        """
        학습 모멘텀(추세)을 계산합니다.

        최근 활동 대비 전체 활동의 비율로 학습 추세를 파악합니다.

        Args:
            agent_id: 에이전트 ID
            window_weeks: 최근 윈도우 크기 (주)
            current_time: 현재 시간 (None이면 현재)

        Returns:
            학습 모멘텀 정보 딕셔너리
        """
        if current_time is None:
            current_time = time.time()

        if agent_id not in self.events:
            return {
                'recent_amplitude': 0.0,
                'total_amplitude': 0.0,
                'momentum': 0.0,
                'trend': 'inactive'
            }

        window_seconds = window_weeks * SECONDS_PER_WEEK
        window_start = current_time - window_seconds

        recent_sum = 0.0
        total_sum = 0.0
        recent_count = 0
        total_count = 0

        for event in self.events[agent_id]:
            amp = event.get_decayed_amplitude(current_time, self.decay_rate)
            if amp < MIN_AMPLITUDE_THRESHOLD:
                continue

            total_sum += amp
            total_count += 1

            if event.timestamp >= window_start:
                recent_sum += amp
                recent_count += 1

        if total_sum == 0:
            momentum = 0.0
            trend = 'inactive'
        else:
            # 모멘텀 = 최근 활동 비율 대비 기대 비율
            expected_ratio = window_weeks / 52.0  # 1년 기준 기대 비율
            actual_ratio = recent_sum / total_sum
            momentum = actual_ratio / max(expected_ratio, 0.01)

            if momentum > 1.5:
                trend = 'accelerating'
            elif momentum > 1.0:
                trend = 'growing'
            elif momentum > 0.5:
                trend = 'stable'
            else:
                trend = 'declining'

        return {
            'recent_amplitude': recent_sum,
            'total_amplitude': total_sum,
            'recent_count': recent_count,
            'total_count': total_count,
            'momentum': momentum,
            'trend': trend
        }

    def predict_amplitude(
        self,
        agent_id,         # type: str
        future_weeks,     # type: float
        current_time=None  # type: Optional[float]
    ):
        # type: (...) -> float
        """
        미래 시점의 진폭을 예측합니다.

        현재 추세가 지속된다고 가정하고 미래 진폭을 계산합니다.

        Args:
            agent_id: 에이전트 ID
            future_weeks: 미래 시점 (주 단위)
            current_time: 현재 시간 (None이면 현재)

        Returns:
            예측 진폭 값
        """
        if current_time is None:
            current_time = time.time()

        current_amp = self.get_cumulative_amplitude(agent_id, current_time)

        # 감쇠만 적용 (새 이벤트 없다고 가정)
        future_amp = current_amp * math.exp(-self.decay_rate * future_weeks)

        return future_amp

    def get_optimal_review_time(
        self,
        agent_id,           # type: str
        target_amplitude,   # type: float
        current_time=None   # type: Optional[float]
    ):
        # type: (...) -> Optional[float]
        """
        최적의 복습 시점을 계산합니다.

        진폭이 목표치 아래로 떨어지기 전에 복습하면 효과적입니다.
        (간격 반복 학습의 양자역학적 최적화)

        Args:
            agent_id: 에이전트 ID
            target_amplitude: 목표 최소 진폭
            current_time: 현재 시간 (None이면 현재)

        Returns:
            최적 복습 시점 (Unix timestamp) 또는 None
        """
        if current_time is None:
            current_time = time.time()

        current_amp = self.get_cumulative_amplitude(agent_id, current_time)

        if current_amp <= target_amplitude:
            # 이미 목표치 이하 - 즉시 복습 필요
            return current_time

        if current_amp <= 0:
            return None

        # A_target = A_current * e^(-λt)
        # t = -ln(A_target / A_current) / λ
        ratio = target_amplitude / current_amp
        if ratio <= 0:
            return None

        weeks_until_target = -math.log(ratio) / self.decay_rate
        seconds_until_target = weeks_until_target * SECONDS_PER_WEEK

        return current_time + seconds_until_target

    def cleanup_expired_events(
        self,
        current_time=None  # type: Optional[float]
    ):
        # type: (...) -> int
        """
        만료된 이벤트들을 정리합니다.

        진폭이 최소 임계값 이하로 떨어진 이벤트들을 제거합니다.

        Args:
            current_time: 현재 시간 (None이면 현재)

        Returns:
            제거된 이벤트 수
        """
        if current_time is None:
            current_time = time.time()

        removed_count = 0

        for agent_id in list(self.events.keys()):
            remaining = []
            for event in self.events[agent_id]:
                amp = event.get_decayed_amplitude(current_time, self.decay_rate)
                if amp >= MIN_AMPLITUDE_THRESHOLD:
                    remaining.append(event)
                else:
                    self._remove_event_references(event)
                    removed_count += 1

            if remaining:
                self.events[agent_id] = remaining
            else:
                del self.events[agent_id]

        return removed_count

    def get_statistics(self, current_time=None):
        # type: (Optional[float]) -> Dict[str, Any]
        """
        시스템 통계를 반환합니다.

        Args:
            current_time: 현재 시간 (None이면 현재)

        Returns:
            통계 정보 딕셔너리
        """
        if current_time is None:
            current_time = time.time()

        total_events = sum(len(events) for events in self.events.values())
        active_events = 0
        total_amplitude = 0.0

        for agent_id in self.events:
            for event in self.events[agent_id]:
                amp = event.get_decayed_amplitude(current_time, self.decay_rate)
                if amp >= MIN_AMPLITUDE_THRESHOLD:
                    active_events += 1
                    total_amplitude += amp

        return {
            'total_events': total_events,
            'active_events': active_events,
            'total_agents': len(self.events),
            'total_tags': len(self.tag_events),
            'total_amplitude': total_amplitude,
            'average_amplitude': total_amplitude / max(active_events, 1),
            'decay_rate': self.decay_rate,
            'max_history': self.max_history
        }

    def to_dict(self):
        # type: () -> Dict[str, Any]
        """직렬화를 위해 딕셔너리로 변환."""
        return {
            'decay_rate': self.decay_rate,
            'max_history': self.max_history,
            'events': {
                agent_id: [e.to_dict() for e in events]
                for agent_id, events in self.events.items()
            },
            'event_counter': self._event_counter
        }

    @classmethod
    def from_dict(cls, data):
        # type: (Dict[str, Any]) -> TemporalEntanglement
        """딕셔너리에서 복원."""
        instance = cls(
            decay_rate=data.get('decay_rate', DEFAULT_DECAY_RATE),
            max_history=data.get('max_history', MAX_HISTORY_SIZE)
        )
        instance._event_counter = data.get('event_counter', 0)

        # 이벤트 복원
        for agent_id, events_data in data.get('events', {}).items():
            for event_data in events_data:
                event = TemporalEvent.from_dict(event_data)

                if agent_id not in instance.events:
                    instance.events[agent_id] = []
                instance.events[agent_id].append(event)

                instance._event_index[event.event_id] = event

                for tag in event.tags:
                    if tag not in instance.tag_events:
                        instance.tag_events[tag] = []
                    instance.tag_events[tag].append(event.event_id)

        return instance


# ============================================================================
# Global Instance
# ============================================================================

# 전역 TemporalEntanglement 인스턴스
TEMPORAL_ENTANGLEMENT = TemporalEntanglement()


# ============================================================================
# Convenience Functions
# ============================================================================

def record_learning_event(
    agent_id,        # type: str
    amplitude,       # type: float
    tags=None,       # type: Optional[List[str]]
    context=None     # type: Optional[Dict[str, Any]]
):
    # type: (...) -> TemporalEvent
    """
    학습 이벤트를 기록하는 편의 함수.

    Args:
        agent_id: 에이전트 ID
        amplitude: 학습 강도 (0.0 ~ 1.0)
        tags: 관련 태그 (개념, 주제)
        context: 추가 컨텍스트

    Returns:
        생성된 TemporalEvent

    Example:
        >>> record_learning_event('mentor', 0.8, tags=['수학', '미분'])
    """
    return TEMPORAL_ENTANGLEMENT.record_event(
        agent_id=agent_id,
        amplitude=amplitude,
        tags=tags,
        context=context
    )


def get_agent_memory_strength(agent_id, tags=None):
    # type: (str, Optional[List[str]]) -> float
    """
    에이전트의 현재 기억 강도(누적 진폭)를 반환합니다.

    Args:
        agent_id: 에이전트 ID
        tags: 특정 태그로 필터링 (선택사항)

    Returns:
        기억 강도 (누적 진폭)
    """
    return TEMPORAL_ENTANGLEMENT.get_cumulative_amplitude(
        agent_id=agent_id,
        tags_filter=tags
    )


def get_concept_strength(concept_tag):
    # type: (str) -> float
    """
    특정 개념의 현재 강도를 반환합니다.

    Args:
        concept_tag: 개념 태그

    Returns:
        개념 강도 (누적 진폭)
    """
    return TEMPORAL_ENTANGLEMENT.get_tag_amplitude(concept_tag)


def should_review(agent_id, min_amplitude=0.3):
    # type: (str, float) -> bool
    """
    복습이 필요한지 확인합니다.

    Args:
        agent_id: 에이전트 ID
        min_amplitude: 최소 유지 진폭

    Returns:
        복습 필요 여부
    """
    current = TEMPORAL_ENTANGLEMENT.get_cumulative_amplitude(agent_id)
    return current < min_amplitude


def get_related_agent_boost(source_agent_id, amplitude=1.0):
    # type: (str, float) -> Dict[str, float]
    """
    관련 에이전트들에게 전파될 부스트를 계산합니다.

    Args:
        source_agent_id: 소스 에이전트 ID
        amplitude: 전파할 진폭

    Returns:
        에이전트별 부스트 양
    """
    return TEMPORAL_ENTANGLEMENT.propagate_non_local_effect(
        source_agent_id=source_agent_id,
        amplitude_boost=amplitude
    )


# ============================================================================
# Module Documentation
# ============================================================================

__all__ = [
    # Classes
    'TemporalEvent',
    'TemporalEntanglement',

    # Constants
    'DEFAULT_DECAY_RATE',
    'SECONDS_PER_WEEK',
    'MIN_AMPLITUDE_THRESHOLD',
    'MAX_HISTORY_SIZE',
    'NON_LOCAL_PROPAGATION_RATE',

    # Global Instance
    'TEMPORAL_ENTANGLEMENT',

    # Convenience Functions
    'record_learning_event',
    'get_agent_memory_strength',
    'get_concept_strength',
    'should_review',
    'get_related_agent_boost',
]


# ============================================================================
# Example Usage (for testing)
# ============================================================================

if __name__ == '__main__':
    import json

    print("=== Temporal Entanglement Demo ===\n")

    # 테스트용 인스턴스 생성
    te = TemporalEntanglement(decay_rate=0.3)

    # 현재 시간 기준
    now = time.time()

    # 학습 이벤트 기록 (다양한 시점)
    print("1. Recording learning events...")

    # 4주 전 이벤트
    te.record_event(
        agent_id='mentor',
        amplitude=0.9,
        tags=['calculus', 'derivatives'],
        timestamp=now - 4 * SECONDS_PER_WEEK
    )

    # 2주 전 이벤트
    te.record_event(
        agent_id='mentor',
        amplitude=0.8,
        tags=['calculus', 'integrals'],
        timestamp=now - 2 * SECONDS_PER_WEEK
    )

    # 1주 전 이벤트
    te.record_event(
        agent_id='tutor',
        amplitude=0.85,
        tags=['calculus', 'derivatives'],
        timestamp=now - 1 * SECONDS_PER_WEEK
    )

    # 현재 이벤트
    te.record_event(
        agent_id='mentor',
        amplitude=0.95,
        tags=['calculus', 'applications'],
        timestamp=now
    )

    # 2. 누적 진폭 확인
    print("\n2. Cumulative amplitudes:")
    amplitudes = te.get_all_cumulative_amplitudes(now)
    for agent, amp in amplitudes.items():
        print("   {}: {:.3f}".format(agent, amp))

    # 3. 잔류파 감쇠 데모
    print("\n3. Residual wave decay demo:")
    initial = 1.0
    for weeks in [0, 1, 2, 4, 8]:
        decayed = te.calculate_residual_wave(initial, weeks)
        print("   Week {}: {:.3f} ({:.1f}% remaining)".format(
            weeks, decayed, decayed * 100
        ))

    # 4. 비국소적 상관관계
    print("\n4. Non-local correlation:")
    correlation = te.get_non_local_correlation('mentor', 'tutor', now)
    print("   mentor <-> tutor: {:.3f}".format(correlation))

    # 5. 태그(개념) 강도
    print("\n5. Concept strengths:")
    for tag, amp in te.get_strongest_tags(5, now):
        print("   {}: {:.3f}".format(tag, amp))

    # 6. 학습 모멘텀
    print("\n6. Learning momentum:")
    for agent in ['mentor', 'tutor']:
        momentum = te.get_learning_momentum(agent, 4, now)
        print("   {}: {} ({:.2f})".format(
            agent, momentum['trend'], momentum['momentum']
        ))

    # 7. 최적 복습 시점
    print("\n7. Optimal review timing:")
    review_time = te.get_optimal_review_time('mentor', 0.5, now)
    if review_time:
        weeks_until = (review_time - now) / SECONDS_PER_WEEK
        print("   Review 'mentor' in {:.1f} weeks".format(weeks_until))

    # 8. 통계
    print("\n8. Statistics:")
    stats = te.get_statistics(now)
    print(json.dumps(stats, indent=2))

    print("\n=== Demo Complete ===")
