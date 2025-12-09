#!/usr/bin/env python3
"""
🔬 Meta-Research Engine - 연구 프로세스를 연구하는 메타 연구 시스템
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

핵심 원리:
- Layer 1: Research Engine (프로젝트 생성/수정/가설/분석)
- Layer 2: Meta-Research Engine (프로젝트 간 관계 분석, 품질 평가, 정제)

5단계 파이프라인:
1. 신호 감지 (Signal Detection)
2. 의미 분석 & 분류 (Semantic Analysis)
3. 프로젝트 생성/진화 (Project Evolution)
4. 메타 연구 검증·정제 (Meta Validation)
5. 사람 승인 (Human Approval)

출력:
- similarity_matrix.json
- meta_research_report_YYYY-MM-DD.md
"""

import json
import re
import os
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Tuple, Optional, Set
from dataclasses import dataclass, field
from collections import defaultdict
import math

# 로깅 설정
logger = logging.getLogger("holarchy.meta_research_engine")


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 설정
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

SIMILARITY_THRESHOLD = 0.6  # 중복 의심 임계값
CONFLICT_THRESHOLD = 0.4   # 충돌 가능성 임계값
DRIFT_THRESHOLD = 0.5      # drift 감지 임계값 (30% → 50% 조정)


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 데이터 구조
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

@dataclass
class Signal:
    """하위 정보 신호"""
    type: str  # pattern, exception, drift, risk, insight, opportunity
    source_holon: str
    description: str
    severity: float  # 0.0 ~ 1.0
    detected_at: str
    related_holons: List[str] = field(default_factory=list)


@dataclass
class SimilarityPair:
    """유사도 쌍"""
    holon_a: str
    holon_b: str
    
    # 유사도 점수들
    will_similarity: float
    intention_similarity: float
    goal_similarity: float
    link_similarity: float
    overall_similarity: float
    
    # 분석 결과
    is_duplicate: bool
    is_conflicting: bool
    conflict_points: List[str] = field(default_factory=list)


@dataclass
class RefinementSuggestion:
    """정제 제안"""
    action: str  # merge, derive_new, archive, split, redirect
    targets: List[str]
    reason: str
    impact: str
    priority: int  # 1=highest, 5=lowest
    
    def to_dict(self) -> dict:
        return {
            "action": self.action,
            "targets": self.targets,
            "reason": self.reason,
            "impact": self.impact,
            "priority": self.priority
        }


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 유사도 분석 엔진
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class SimilarityAnalyzer:
    """프로젝트 간 유사도 분석"""
    
    def __init__(self, holons: Dict[str, dict]):
        self.holons = holons
        self.matrix: Dict[str, Dict[str, float]] = {}
        self.pairs: List[SimilarityPair] = []
    
    def _tokenize(self, text: str) -> Set[str]:
        """텍스트 토큰화"""
        if not text:
            return set()
        tokens = re.findall(r'[가-힣]+|[a-zA-Z]+|\d+', text.lower())
        return {t for t in tokens if len(t) >= 2}
    
    def _jaccard_similarity(self, set_a: Set[str], set_b: Set[str]) -> float:
        """Jaccard 유사도"""
        if not set_a or not set_b:
            return 0.0
        intersection = len(set_a & set_b)
        union = len(set_a | set_b)
        return intersection / union if union > 0 else 0.0
    
    def _extract_will_tokens(self, holon: dict) -> Set[str]:
        """W.will에서 토큰 추출"""
        tokens = set()
        w = holon.get("W", {})
        will = w.get("will", {})
        
        if isinstance(will, dict):
            for key in ["drive", "commitment"]:
                tokens.update(self._tokenize(will.get(key, "")))
            for item in will.get("non_negotiables", []):
                tokens.update(self._tokenize(item))
        elif isinstance(will, str):
            tokens.update(self._tokenize(will))
        
        return tokens
    
    def _extract_intention_tokens(self, holon: dict) -> Set[str]:
        """W.intention에서 토큰 추출"""
        tokens = set()
        w = holon.get("W", {})
        intention = w.get("intention", {})
        
        if isinstance(intention, dict):
            tokens.update(self._tokenize(intention.get("primary", "")))
            for item in intention.get("secondary", []):
                tokens.update(self._tokenize(item))
        
        return tokens
    
    def _extract_goal_tokens(self, holon: dict) -> Set[str]:
        """W.goal에서 토큰 추출"""
        tokens = set()
        w = holon.get("W", {})
        goal = w.get("goal", {})
        
        if isinstance(goal, dict):
            tokens.update(self._tokenize(goal.get("ultimate", "")))
            for item in goal.get("milestones", []):
                tokens.update(self._tokenize(item))
            for item in goal.get("kpi", []):
                tokens.update(self._tokenize(item))
        
        return tokens
    
    def _get_links(self, holon: dict) -> Set[str]:
        """링크 추출"""
        links = set()
        link_section = holon.get("links", {})
        
        for key in ["parent", "children", "related", "spawned_from"]:
            value = link_section.get(key)
            if isinstance(value, str) and value:
                links.add(value)
            elif isinstance(value, list):
                links.update([v for v in value if v])
        
        return links
    
    def calculate_similarity(self, holon_a_id: str, holon_b_id: str) -> SimilarityPair:
        """두 Holon 간 유사도 계산"""
        holon_a = self.holons[holon_a_id]
        holon_b = self.holons[holon_b_id]
        
        # 각 영역별 유사도
        will_sim = self._jaccard_similarity(
            self._extract_will_tokens(holon_a),
            self._extract_will_tokens(holon_b)
        )
        
        intention_sim = self._jaccard_similarity(
            self._extract_intention_tokens(holon_a),
            self._extract_intention_tokens(holon_b)
        )
        
        goal_sim = self._jaccard_similarity(
            self._extract_goal_tokens(holon_a),
            self._extract_goal_tokens(holon_b)
        )
        
        link_sim = self._jaccard_similarity(
            self._get_links(holon_a),
            self._get_links(holon_b)
        )
        
        # 종합 유사도 (가중 평균)
        overall = (
            will_sim * 0.35 +
            intention_sim * 0.25 +
            goal_sim * 0.25 +
            link_sim * 0.15
        )
        
        # 중복/충돌 판단
        is_duplicate = overall >= SIMILARITY_THRESHOLD
        
        # 충돌 감지 (유사하지만 목표가 다른 경우)
        is_conflicting = (will_sim > 0.5 and goal_sim < 0.3) or \
                        (intention_sim > 0.5 and goal_sim < 0.3)
        
        conflict_points = []
        if is_conflicting:
            if will_sim > 0.5 and goal_sim < 0.3:
                conflict_points.append("Will은 유사하나 Goal이 다름")
            if intention_sim > 0.5 and goal_sim < 0.3:
                conflict_points.append("Intention은 유사하나 Goal이 다름")
        
        return SimilarityPair(
            holon_a=holon_a_id,
            holon_b=holon_b_id,
            will_similarity=will_sim,
            intention_similarity=intention_sim,
            goal_similarity=goal_sim,
            link_similarity=link_sim,
            overall_similarity=overall,
            is_duplicate=is_duplicate,
            is_conflicting=is_conflicting,
            conflict_points=conflict_points
        )
    
    def build_matrix(self) -> Dict[str, Dict[str, float]]:
        """전체 유사도 매트릭스 구축"""
        holon_ids = list(self.holons.keys())
        self.matrix = {h: {} for h in holon_ids}
        self.pairs = []
        
        for i, id_a in enumerate(holon_ids):
            for id_b in holon_ids[i+1:]:
                pair = self.calculate_similarity(id_a, id_b)
                self.pairs.append(pair)
                self.matrix[id_a][id_b] = pair.overall_similarity
                self.matrix[id_b][id_a] = pair.overall_similarity
        
        return self.matrix
    
    def get_duplicates(self) -> List[SimilarityPair]:
        """중복 의심 쌍 반환"""
        return [p for p in self.pairs if p.is_duplicate]
    
    def get_conflicts(self) -> List[SimilarityPair]:
        """충돌 쌍 반환"""
        return [p for p in self.pairs if p.is_conflicting]


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Drift 분석 엔진
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class DriftAnalyzer:
    """상위 헌법과의 alignment 분석 및 drift 감지"""
    
    def __init__(self, holons: Dict[str, dict], root_w: dict):
        self.holons = holons
        self.root_w = root_w
        self.root_keywords = self._extract_root_keywords()
    
    def _tokenize(self, text: str) -> Set[str]:
        if not text:
            return set()
        tokens = re.findall(r'[가-힣]+|[a-zA-Z]+|\d+', text.lower())
        return {t for t in tokens if len(t) >= 2}
    
    def _extract_root_keywords(self) -> Set[str]:
        """Root W에서 핵심 키워드 추출"""
        keywords = set()
        
        will = self.root_w.get("will", {})
        if isinstance(will, dict):
            keywords.update(self._tokenize(will.get("drive", "")))
            keywords.update(self._tokenize(will.get("commitment", "")))
        
        goal = self.root_w.get("goal", {})
        if isinstance(goal, dict):
            keywords.update(self._tokenize(goal.get("ultimate", "")))
        
        return keywords
    
    def calculate_drift(self, holon: dict) -> Tuple[float, List[str]]:
        """
        Holon의 drift 점수 계산
        
        Returns:
            (drift_score, missing_keywords)
            drift_score가 높을수록 상위 헌법에서 벗어남
        """
        w = holon.get("W", {})
        will = w.get("will", {})
        
        holon_keywords = set()
        if isinstance(will, dict):
            holon_keywords.update(self._tokenize(will.get("drive", "")))
        
        if not self.root_keywords:
            return 0.0, []
        
        # 공통 키워드
        common = holon_keywords & self.root_keywords
        coverage = len(common) / len(self.root_keywords)
        
        # drift = 1 - coverage (coverage가 낮을수록 drift가 높음)
        drift_score = 1 - coverage
        
        # 누락된 핵심 키워드
        missing = list(self.root_keywords - holon_keywords)[:5]
        
        return drift_score, missing
    
    def analyze_all(self) -> Dict[str, Tuple[float, List[str]]]:
        """모든 Holon의 drift 분석"""
        results = {}
        for holon_id, holon in self.holons.items():
            results[holon_id] = self.calculate_drift(holon)
        return results


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 품질 검사 엔진
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class QualityInspector:
    """연구 프로세스 품질 검사"""
    
    def __init__(self, holons: Dict[str, dict]):
        self.holons = holons
    
    def check_problem_definition(self, holon: dict) -> Tuple[bool, str]:
        """문제 정의 적절성 검사"""
        x = holon.get("X", {})
        
        # X.problem 또는 X.context 확인
        problem = x.get("problem", "") or x.get("context", "")
        
        if not problem or problem.startswith("["):
            return False, "문제 정의가 비어있거나 플레이스홀더"
        
        if len(problem) < 20:
            return False, "문제 정의가 너무 짧음"
        
        return True, "OK"
    
    def check_reasoning_structure(self, holon: dict) -> Tuple[bool, str]:
        """근거 구조 충족성 검사"""
        s = holon.get("S", {})
        
        # S 섹션에 핵심 요소가 있는지
        has_structure = any([
            s.get("core_components"),
            s.get("key_variables"),
            s.get("constraints")
        ])
        
        if not has_structure:
            return False, "S 섹션에 근거 구조 없음"
        
        return True, "OK"
    
    def check_hypothesis_flow(self, holon: dict) -> Tuple[bool, str]:
        """가설 생성 흐름 검사"""
        # W → X → P 흐름이 논리적인지
        w = holon.get("W", {})
        x = holon.get("X", {})
        p = holon.get("P", {})
        
        has_will = bool(w.get("will"))
        has_context = bool(x.get("context") or x.get("problem"))
        has_process = bool(p.get("procedure_steps") or p.get("workflow"))
        
        if not all([has_will, has_context, has_process]):
            return False, "W→X→P 흐름이 불완전"
        
        return True, "OK"
    
    def check_causality(self, holon: dict) -> Tuple[bool, str]:
        """인과관계 왜곡 검사 (간단한 휴리스틱)"""
        # E의 실행 계획이 P의 절차와 연결되는지
        p = holon.get("P", {})
        e = holon.get("E", {})
        
        procedure = p.get("procedure_steps", [])
        execution = e.get("execution_plan", [])
        
        if procedure and not execution:
            return False, "절차(P)는 있으나 실행(E)이 비어있음"
        
        return True, "OK"
    
    def inspect(self, holon_id: str) -> Dict[str, Tuple[bool, str]]:
        """Holon 품질 검사"""
        holon = self.holons.get(holon_id, {})
        
        return {
            "problem_definition": self.check_problem_definition(holon),
            "reasoning_structure": self.check_reasoning_structure(holon),
            "hypothesis_flow": self.check_hypothesis_flow(holon),
            "causality": self.check_causality(holon)
        }
    
    def inspect_all(self) -> Dict[str, Dict[str, Tuple[bool, str]]]:
        """모든 Holon 품질 검사"""
        return {hid: self.inspect(hid) for hid in self.holons}


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 정제 제안 엔진
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class RefinementEngine:
    """정제/통합 제안 생성"""
    
    def __init__(self, similarity_analyzer: SimilarityAnalyzer, 
                 drift_analyzer: DriftAnalyzer,
                 quality_inspector: QualityInspector):
        self.similarity = similarity_analyzer
        self.drift = drift_analyzer
        self.quality = quality_inspector
        self.suggestions: List[RefinementSuggestion] = []
    
    def generate_suggestions(self) -> List[RefinementSuggestion]:
        """정제 제안 생성"""
        self.suggestions = []
        
        # 1. 중복 문서 병합 제안
        for pair in self.similarity.get_duplicates():
            self.suggestions.append(RefinementSuggestion(
                action="merge",
                targets=[pair.holon_a, pair.holon_b],
                reason=f"유사도 {pair.overall_similarity:.0%} - 중복 의심",
                impact="문서 수 감소, 유지보수 비용 절감",
                priority=2
            ))
        
        # 2. 충돌 문서 재정의 제안
        for pair in self.similarity.get_conflicts():
            self.suggestions.append(RefinementSuggestion(
                action="redirect",
                targets=[pair.holon_a, pair.holon_b],
                reason=f"충돌: {', '.join(pair.conflict_points)}",
                impact="목표 명확화, 혼선 방지",
                priority=1
            ))
        
        # 3. Drift가 높은 문서 재정렬 제안
        drift_results = self.drift.analyze_all()
        for holon_id, (drift_score, missing) in drift_results.items():
            if drift_score > DRIFT_THRESHOLD:
                self.suggestions.append(RefinementSuggestion(
                    action="redirect",
                    targets=[holon_id],
                    reason=f"Drift {drift_score:.0%} - 상위 헌법과 불일치",
                    impact=f"누락 키워드: {', '.join(missing[:3])}",
                    priority=3
                ))
        
        # 4. 품질 미달 문서 개선 제안
        quality_results = self.quality.inspect_all()
        for holon_id, checks in quality_results.items():
            failed_checks = [k for k, (passed, _) in checks.items() if not passed]
            if failed_checks:
                self.suggestions.append(RefinementSuggestion(
                    action="derive_new",
                    targets=[holon_id],
                    reason=f"품질 미달: {', '.join(failed_checks)}",
                    impact="연구 프로세스 품질 향상",
                    priority=4
                ))
        
        # 우선순위 정렬
        self.suggestions.sort(key=lambda x: x.priority)
        
        return self.suggestions


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 메타 연구 엔진 (통합)
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class MetaResearchEngine:
    """메타 연구 엔진 - 연구 프로세스를 연구"""
    
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.holons_path = self.base_path / "holons"
        self.reports_path = self.base_path / "reports"
        
        self.holons: Dict[str, dict] = {}
        self.root_w: dict = {}
        
        # 서브 엔진들
        self.similarity_analyzer: Optional[SimilarityAnalyzer] = None
        self.drift_analyzer: Optional[DriftAnalyzer] = None
        self.quality_inspector: Optional[QualityInspector] = None
        self.refinement_engine: Optional[RefinementEngine] = None
    
    def load_holons(self) -> None:
        """모든 Holon 로드"""
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    holon_id = holon.get("holon_id", md_file.stem)
                    self.holons[holon_id] = holon
                except json.JSONDecodeError as e:
                    logger.debug(f"Holon JSON 파싱 실패 [{md_file.name}]: {e}")
    
    def find_root_w(self) -> dict:
        """루트 W 찾기"""
        for holon_id, holon in self.holons.items():
            if holon.get("type") == "strategy":
                return holon.get("W", {})
        
        if "hte-doc-000" in self.holons:
            return self.holons["hte-doc-000"].get("W", {})
        
        for holon in self.holons.values():
            return holon.get("W", {})
        
        return {}
    
    def run_analysis(self) -> dict:
        """전체 분석 실행"""
        print("=" * 70)
        print("🔬 Meta-Research Engine v1.0")
        print("   연구 프로세스를 연구하는 메타 연구 시스템")
        print("=" * 70)
        print()
        
        # Step 1: 데이터 로드
        print("📂 Step 1: 프로젝트 스캔...")
        self.load_holons()
        self.root_w = self.find_root_w()
        print(f"   로드된 문서: {len(self.holons)}개")
        print()
        
        # Step 2: 유사도 분석
        print("🔗 Step 2: 유사도 매트릭스 구축...")
        self.similarity_analyzer = SimilarityAnalyzer(self.holons)
        matrix = self.similarity_analyzer.build_matrix()
        duplicates = self.similarity_analyzer.get_duplicates()
        conflicts = self.similarity_analyzer.get_conflicts()
        print(f"   중복 의심: {len(duplicates)}쌍")
        print(f"   충돌 감지: {len(conflicts)}쌍")
        print()
        
        # Step 3: Drift 분석
        print("📐 Step 3: Drift 분석...")
        self.drift_analyzer = DriftAnalyzer(self.holons, self.root_w)
        drift_results = self.drift_analyzer.analyze_all()
        high_drift = [h for h, (d, _) in drift_results.items() if d > DRIFT_THRESHOLD]
        print(f"   고drift 문서: {len(high_drift)}개")
        print()
        
        # Step 4: 품질 검사
        print("🔍 Step 4: 품질 검사...")
        self.quality_inspector = QualityInspector(self.holons)
        quality_results = self.quality_inspector.inspect_all()
        quality_issues = sum(1 for checks in quality_results.values() 
                           if any(not passed for passed, _ in checks.values()))
        print(f"   품질 이슈: {quality_issues}개")
        print()
        
        # Step 5: 정제 제안 생성
        print("💡 Step 5: 정제 제안 생성...")
        self.refinement_engine = RefinementEngine(
            self.similarity_analyzer,
            self.drift_analyzer,
            self.quality_inspector
        )
        suggestions = self.refinement_engine.generate_suggestions()
        print(f"   생성된 제안: {len(suggestions)}개")
        print()
        
        # 결과 반환
        return {
            "total_holons": len(self.holons),
            "duplicates": len(duplicates),
            "conflicts": len(conflicts),
            "high_drift": len(high_drift),
            "quality_issues": quality_issues,
            "suggestions": len(suggestions),
            "similarity_matrix": matrix,
            "drift_results": {h: d for h, (d, _) in drift_results.items()},
            "quality_results": quality_results,
            "refinement_suggestions": [s.to_dict() for s in suggestions]
        }
    
    def generate_report(self, analysis_result: dict) -> str:
        """마크다운 리포트 생성"""
        today = datetime.now().strftime("%Y-%m-%d")
        
        report = f"""# 🔬 Meta-Research Report

**생성일**: {today}  
**분석 대상**: {analysis_result['total_holons']}개 Holon

---

## 📊 분석 요약

| 항목 | 수량 |
|------|------|
| 중복 의심 쌍 | {analysis_result['duplicates']} |
| 충돌 감지 쌍 | {analysis_result['conflicts']} |
| 고Drift 문서 | {analysis_result['high_drift']} |
| 품질 이슈 문서 | {analysis_result['quality_issues']} |
| 정제 제안 | {analysis_result['suggestions']} |

---

## 🔗 중복/충돌 분석

"""
        
        # 중복/충돌 상세
        for pair in self.similarity_analyzer.pairs:
            if pair.is_duplicate or pair.is_conflicting:
                status = "🔴 중복" if pair.is_duplicate else "⚠️ 충돌"
                report += f"""### {status}: `{pair.holon_a}` ↔ `{pair.holon_b}`

- **전체 유사도**: {pair.overall_similarity:.0%}
- **Will 유사도**: {pair.will_similarity:.0%}
- **Goal 유사도**: {pair.goal_similarity:.0%}
"""
                if pair.conflict_points:
                    report += f"- **충돌 포인트**: {', '.join(pair.conflict_points)}\n"
                report += "\n"
        
        # Drift 분석
        report += """---

## 📐 Drift 분석 (상위 헌법과의 alignment)

| 문서 | Drift 점수 | 상태 |
|------|-----------|------|
"""
        drift_results = self.drift_analyzer.analyze_all()
        for holon_id, (drift, missing) in sorted(drift_results.items(), key=lambda x: -x[1][0]):
            status = "🔴 높음" if drift > DRIFT_THRESHOLD else "✅ 정상"
            report += f"| {holon_id} | {drift:.0%} | {status} |\n"
        
        # 품질 검사
        report += """
---

## 🔍 품질 검사 결과

"""
        for holon_id, checks in self.quality_inspector.inspect_all().items():
            failed = [(k, msg) for k, (passed, msg) in checks.items() if not passed]
            if failed:
                report += f"### ⚠️ `{holon_id}`\n\n"
                for check_name, msg in failed:
                    report += f"- **{check_name}**: {msg}\n"
                report += "\n"
        
        # 정제 제안
        report += """---

## 💡 정제 제안 (우선순위순)

"""
        for i, s in enumerate(self.refinement_engine.suggestions, 1):
            action_emoji = {
                "merge": "🔀",
                "redirect": "↪️",
                "derive_new": "🆕",
                "archive": "📦",
                "split": "✂️"
            }.get(s.action, "📌")
            
            report += f"""### {i}. {action_emoji} {s.action.upper()}: `{', '.join(s.targets)}`

- **이유**: {s.reason}
- **영향**: {s.impact}
- **우선순위**: P{s.priority}

"""
        
        # 다음 액션
        report += """---

## 🚀 권장 다음 액션

1. 위 제안 검토 후 승인/거부 결정
2. 승인된 제안에 대해 `python _cli.py meta apply` 실행
3. 변경사항 커밋 및 문서 재검증

---

> 📝 이 리포트는 AI가 자동 생성했습니다. 최종 판단은 사람이 합니다.
"""
        
        return report
    
    def save_report(self, report: str) -> Path:
        """리포트 저장"""
        self.reports_path.mkdir(parents=True, exist_ok=True)
        
        today = datetime.now().strftime("%Y-%m-%d")
        report_file = self.reports_path / f"meta_research_report_{today}.md"
        
        report_file.write_text(report, encoding="utf-8")
        return report_file
    
    def save_matrix(self) -> Path:
        """유사도 매트릭스 저장"""
        matrix_file = self.holons_path / "_similarity_matrix.json"
        
        data = {
            "generated_at": datetime.now().isoformat(),
            "threshold": SIMILARITY_THRESHOLD,
            "matrix": self.similarity_analyzer.matrix
        }
        
        matrix_file.write_text(
            json.dumps(data, ensure_ascii=False, indent=2),
            encoding="utf-8"
        )
        return matrix_file
    
    def print_summary(self, analysis_result: dict) -> None:
        """콘솔 요약 출력"""
        print("=" * 70)
        print("📋 분석 결과 요약")
        print("=" * 70)
        print()
        
        print(f"📊 통계:")
        print(f"   • 총 문서: {analysis_result['total_holons']}개")
        print(f"   • 중복 의심: {analysis_result['duplicates']}쌍")
        print(f"   • 충돌 감지: {analysis_result['conflicts']}쌍")
        print(f"   • 고Drift: {analysis_result['high_drift']}개")
        print(f"   • 품질 이슈: {analysis_result['quality_issues']}개")
        print()
        
        suggestions = self.refinement_engine.suggestions
        if suggestions:
            print("💡 정제 제안 (상위 5개):")
            print("-" * 70)
            for i, s in enumerate(suggestions[:5], 1):
                print(f"   [{i}] {s.action.upper()}: {', '.join(s.targets)}")
                print(f"       → {s.reason}")
            print()
        
        print("=" * 70)


def main():
    """CLI 실행"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Meta-Research Engine")
    parser.add_argument("command", choices=["analyze", "report"],
                       help="명령: analyze(분석), report(리포트 생성)")
    
    args = parser.parse_args()
    
    script_dir = Path(__file__).parent
    engine = MetaResearchEngine(str(script_dir.parent))
    
    if args.command == "analyze":
        result = engine.run_analysis()
        engine.print_summary(result)
        
        # 매트릭스 저장
        matrix_file = engine.save_matrix()
        print(f"📁 유사도 매트릭스: {matrix_file}")
        
    elif args.command == "report":
        result = engine.run_analysis()
        report = engine.generate_report(result)
        report_file = engine.save_report(report)
        
        engine.print_summary(result)
        print(f"📄 리포트 생성: {report_file}")
        print()
        print("🔔 리포트를 검토하고 제안을 승인/거부해주세요.")


if __name__ == "__main__":
    main()

