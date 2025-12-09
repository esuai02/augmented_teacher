#!/usr/bin/env python3
"""
🧠 Chunk Engine - W 기반 중요도 판단 시스템
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

핵심 원리:
- W (Worldview/Will)를 중요도 판단의 북극성으로 사용
- 회사의 의지에 부합하는 것만 Chunk로 승격
- 항상 5~7개의 Active Chunk만 유지 (인간 작업기억 모방)

알고리즘:
1. 전체 Holon 목록 검색 (Raw Pool)
2. 후보군 추출 (Candidate Pool)  
3. W 기반 salience score 계산
4. Top-K Chunk 선정
"""

import json
import re
import logging
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass, field
import math

# 로깅 설정
logger = logging.getLogger("holarchy.chunk_engine")


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 설정
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

MAX_ACTIVE_CHUNKS = 7  # 인간 작업기억 용량
MIN_SALIENCE_THRESHOLD = 0.3  # 최소 중요도 임계값

# 가중치 설정
WEIGHT_WILL_RESONANCE = 0.40  # W.will 공명도
WEIGHT_GOAL_RELEVANCE = 0.30  # W.goal 연관성
WEIGHT_INTENTION_ALIGN = 0.20  # W.intention 정합성
WEIGHT_RECENCY = 0.10  # 최근성


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 데이터 구조
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

@dataclass
class Chunk:
    """사람의 '생각 한 덩어리' 표현"""
    id: str
    title: str
    why_important: str
    linked_holons: List[str]  # 관련 holon_id 목록
    hypotheses: List[str]  # 현재 가설들
    next_actions: List[str]  # 다음 행동
    salience_score: float  # 중요도 점수 (0.0 ~ 1.0)
    
    # 스코어 상세
    score_breakdown: Dict[str, float] = field(default_factory=dict)
    
    # 메타
    created_at: str = ""
    updated_at: str = ""
    status: str = "active"  # active, dormant, archived
    
    def to_dict(self) -> dict:
        return {
            "id": self.id,
            "title": self.title,
            "why_important": self.why_important,
            "linked_holons": self.linked_holons,
            "hypotheses": self.hypotheses,
            "next_actions": self.next_actions,
            "salience_score": round(self.salience_score, 3),
            "score_breakdown": {k: round(v, 3) for k, v in self.score_breakdown.items()},
            "created_at": self.created_at,
            "updated_at": self.updated_at,
            "status": self.status
        }


@dataclass
class DailyEpisode:
    """하루 단위 에피소드 요약"""
    date: str
    main_goals: List[str]
    active_chunks: List[str]  # chunk id 목록
    key_events: List[str]
    decisions: List[str]
    lessons_learned: List[str]
    metrics_snapshot: Dict[str, any] = field(default_factory=dict)


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# W 기반 중요도 계산 엔진
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class SalienceEngine:
    """W 기반 중요도 판단 엔진"""
    
    def __init__(self, root_w: dict):
        """
        Args:
            root_w: 루트 Holon의 W 섹션 (회사의 핵심 의지)
        """
        self.root_w = root_w
        self.will_keywords = self._extract_will_keywords()
        self.goal_kpis = self._extract_kpis()
        self.constraints = self._extract_constraints()
    
    def _extract_will_keywords(self) -> set:
        """W.will에서 핵심 키워드 추출"""
        keywords = set()
        
        will = self.root_w.get("will", {})
        
        # drive에서 추출
        drive = will.get("drive", "")
        keywords.update(self._tokenize(drive))
        
        # commitment에서 추출
        commitment = will.get("commitment", "")
        keywords.update(self._tokenize(commitment))
        
        # non_negotiables에서 추출
        non_negs = will.get("non_negotiables", [])
        for item in non_negs:
            keywords.update(self._tokenize(item))
        
        # worldview에서 추출
        worldview = self.root_w.get("worldview", {})
        for key in ["identity", "belief", "value_system"]:
            keywords.update(self._tokenize(worldview.get(key, "")))
        
        # 불용어 제거
        stopwords = {"이", "그", "저", "것", "수", "등", "를", "을", "의", "에", "로", "으로", "와", "과", "하다", "되다", "있다", "없다"}
        keywords -= stopwords
        
        return keywords
    
    def _extract_kpis(self) -> List[str]:
        """W.goal에서 KPI 추출"""
        goal = self.root_w.get("goal", {})
        kpis = goal.get("kpi", [])
        
        # OKR의 key_results도 포함
        okr = goal.get("okr", {})
        kpis.extend(okr.get("key_results", []))
        
        return kpis
    
    def _extract_constraints(self) -> List[str]:
        """W.intention에서 제약사항 추출"""
        intention = self.root_w.get("intention", {})
        return intention.get("constraints", [])
    
    def _tokenize(self, text: str) -> set:
        """텍스트를 토큰으로 분리"""
        if not text:
            return set()
        
        # 한글, 영문, 숫자만 추출
        tokens = re.findall(r'[가-힣]+|[a-zA-Z]+|\d+', text.lower())
        
        # 2글자 이상만
        return {t for t in tokens if len(t) >= 2}
    
    def calculate_salience(self, holon: dict) -> Tuple[float, Dict[str, float]]:
        """
        Holon의 중요도(salience) 점수 계산
        
        Returns:
            (total_score, breakdown_dict)
        """
        breakdown = {}
        
        # 1. Will 공명도 (40%)
        will_score = self._calc_will_resonance(holon)
        breakdown["will_resonance"] = will_score
        
        # 2. Goal 연관성 (30%)
        goal_score = self._calc_goal_relevance(holon)
        breakdown["goal_relevance"] = goal_score
        
        # 3. Intention 정합성 (20%)
        intention_score = self._calc_intention_alignment(holon)
        breakdown["intention_alignment"] = intention_score
        
        # 4. 최근성 (10%)
        recency_score = self._calc_recency(holon)
        breakdown["recency"] = recency_score
        
        # 가중 합산
        total = (
            will_score * WEIGHT_WILL_RESONANCE +
            goal_score * WEIGHT_GOAL_RELEVANCE +
            intention_score * WEIGHT_INTENTION_ALIGN +
            recency_score * WEIGHT_RECENCY
        )
        
        return total, breakdown
    
    def _calc_will_resonance(self, holon: dict) -> float:
        """W.will과의 공명도 계산"""
        # Holon의 모든 텍스트 수집
        holon_text = self._collect_holon_text(holon)
        holon_tokens = self._tokenize(holon_text)
        
        if not self.will_keywords or not holon_tokens:
            return 0.5  # 기본값
        
        # 키워드 겹침 비율
        overlap = holon_tokens & self.will_keywords
        
        # Jaccard 유사도 변형 (holon이 will 키워드를 얼마나 포함하는지)
        coverage = len(overlap) / len(self.will_keywords) if self.will_keywords else 0
        
        # 최소 1개는 겹쳐야 의미 있음
        if len(overlap) == 0:
            return 0.1
        
        return min(1.0, coverage * 1.5)  # 약간 관대하게
    
    def _calc_goal_relevance(self, holon: dict) -> float:
        """W.goal과의 연관성 계산"""
        holon_text = self._collect_holon_text(holon)
        
        if not self.goal_kpis:
            return 0.5
        
        # KPI 언급 횟수
        mentions = 0
        for kpi in self.goal_kpis:
            kpi_keywords = self._tokenize(kpi)
            holon_tokens = self._tokenize(holon_text)
            if kpi_keywords & holon_tokens:
                mentions += 1
        
        return min(1.0, mentions / len(self.goal_kpis) * 2)
    
    def _calc_intention_alignment(self, holon: dict) -> float:
        """W.intention과의 정합성 (제약 위반 여부)"""
        holon_text = self._collect_holon_text(holon)
        
        if not self.constraints:
            return 1.0  # 제약 없으면 통과
        
        # 제약 위반 키워드 체크 (간단한 휴리스틱)
        # 실제로는 더 정교한 의미론적 분석 필요
        violations = 0
        for constraint in self.constraints:
            # "하지 않는다", "금지" 등의 부정 패턴 확인
            if any(neg in constraint for neg in ["않", "금지", "제외", "불가"]):
                # 제약에 언급된 키워드가 holon에 있으면 위반 가능성
                constraint_keywords = self._tokenize(constraint)
                holon_tokens = self._tokenize(holon_text)
                if constraint_keywords & holon_tokens:
                    violations += 0.5
        
        return max(0.0, 1.0 - violations)
    
    def _calc_recency(self, holon: dict) -> float:
        """최근성 점수 (최근 수정일 기준)"""
        meta = holon.get("meta", {})
        updated_at = meta.get("updated_at", "")
        
        if not updated_at:
            return 0.5
        
        try:
            update_date = datetime.strptime(updated_at, "%Y-%m-%d")
            days_ago = (datetime.now() - update_date).days
            
            # 7일 이내: 1.0, 30일: 0.5, 90일 이상: 0.1
            if days_ago <= 7:
                return 1.0
            elif days_ago <= 30:
                return 0.7
            elif days_ago <= 90:
                return 0.4
            else:
                return 0.1
        except ValueError as e:
            logger.debug(f"날짜 파싱 실패 [recency_score]: {e}")
            return 0.5

    def _collect_holon_text(self, holon: dict) -> str:
        """Holon에서 모든 텍스트 수집"""
        texts = []
        
        # meta
        meta = holon.get("meta", {})
        texts.append(meta.get("title", ""))
        
        # W 섹션
        w = holon.get("W", {})
        for section in ["worldview", "will", "intention", "goal"]:
            section_data = w.get(section, {})
            if isinstance(section_data, dict):
                for v in section_data.values():
                    if isinstance(v, str):
                        texts.append(v)
                    elif isinstance(v, list):
                        texts.extend([str(x) for x in v])
        
        # XSPERTA의 will 필드들
        for slot in ["X", "S", "P", "E", "R", "T", "A"]:
            slot_data = holon.get(slot, {})
            if isinstance(slot_data, dict):
                texts.append(slot_data.get("will", ""))
        
        return " ".join(texts)


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# Chunk 관리자
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class ChunkManager:
    """Active Chunk 관리 (Top-K 유지)"""
    
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.holons_path = self.base_path / "holons"
        self.chunks_file = self.holons_path / "_chunks.json"
        self.episodes_file = self.holons_path / "_episodes.json"
        
        self.holons: Dict[str, dict] = {}
        self.chunks: List[Chunk] = []
        self.root_w: dict = {}
        self.engine: Optional[SalienceEngine] = None
    
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
        """루트 Holon의 W 찾기 (strategy 또는 hte-doc-000)"""
        # strategy 타입 우선
        for holon_id, holon in self.holons.items():
            if holon.get("type") == "strategy":
                return holon.get("W", {})
        
        # hte-doc-000 fallback
        if "hte-doc-000" in self.holons:
            return self.holons["hte-doc-000"].get("W", {})
        
        # 첫 번째 holon의 W
        for holon in self.holons.values():
            return holon.get("W", {})
        
        return {}
    
    def calculate_all_salience(self) -> List[Tuple[str, float, Dict]]:
        """모든 Holon의 salience 계산"""
        self.root_w = self.find_root_w()
        self.engine = SalienceEngine(self.root_w)
        
        results = []
        for holon_id, holon in self.holons.items():
            score, breakdown = self.engine.calculate_salience(holon)
            results.append((holon_id, score, breakdown))
        
        # 점수 내림차순 정렬
        results.sort(key=lambda x: x[1], reverse=True)
        return results
    
    def generate_chunks(self) -> List[Chunk]:
        """Top-K Chunk 생성"""
        salience_results = self.calculate_all_salience()
        
        chunks = []
        today = datetime.now().strftime("%Y-%m-%d")
        
        for i, (holon_id, score, breakdown) in enumerate(salience_results[:MAX_ACTIVE_CHUNKS]):
            if score < MIN_SALIENCE_THRESHOLD:
                continue
            
            holon = self.holons[holon_id]
            meta = holon.get("meta", {})
            w = holon.get("W", {})
            
            chunk = Chunk(
                id=f"chunk-{i+1:03d}",
                title=meta.get("title", holon_id),
                why_important=w.get("will", {}).get("drive", ""),
                linked_holons=[holon_id],
                hypotheses=[],
                next_actions=self._extract_next_actions(holon),
                salience_score=score,
                score_breakdown=breakdown,
                created_at=today,
                updated_at=today,
                status="active"
            )
            chunks.append(chunk)
        
        self.chunks = chunks
        return chunks
    
    def _extract_next_actions(self, holon: dict) -> List[str]:
        """Holon에서 다음 행동 추출"""
        actions = []
        
        # E (Execution) 섹션에서 추출
        e = holon.get("E", {})
        for plan in e.get("execution_plan", []):
            if isinstance(plan, dict):
                action = plan.get("action", "")
                if action and not action.startswith("["):
                    actions.append(action)
        
        # P (Process) 섹션에서 추출
        p = holon.get("P", {})
        for step in p.get("procedure_steps", []):
            if isinstance(step, dict):
                desc = step.get("description", "")
                if desc and not desc.startswith("["):
                    actions.append(desc)
        
        return actions[:3]  # 최대 3개
    
    def save_chunks(self) -> None:
        """Chunk 저장"""
        data = {
            "generated_at": datetime.now().isoformat(),
            "root_w_keywords": list(self.engine.will_keywords) if self.engine else [],
            "max_chunks": MAX_ACTIVE_CHUNKS,
            "chunks": [c.to_dict() for c in self.chunks]
        }
        
        self.chunks_file.write_text(
            json.dumps(data, ensure_ascii=False, indent=2),
            encoding="utf-8"
        )
    
    def load_chunks(self) -> List[Chunk]:
        """저장된 Chunk 로드"""
        if not self.chunks_file.exists():
            return []
        
        try:
            data = json.loads(self.chunks_file.read_text(encoding="utf-8"))
            chunks = []
            for c in data.get("chunks", []):
                chunk = Chunk(
                    id=c["id"],
                    title=c["title"],
                    why_important=c["why_important"],
                    linked_holons=c["linked_holons"],
                    hypotheses=c.get("hypotheses", []),
                    next_actions=c.get("next_actions", []),
                    salience_score=c["salience_score"],
                    score_breakdown=c.get("score_breakdown", {}),
                    created_at=c.get("created_at", ""),
                    updated_at=c.get("updated_at", ""),
                    status=c.get("status", "active")
                )
                chunks.append(chunk)
            return chunks
        except (json.JSONDecodeError, FileNotFoundError, UnicodeDecodeError) as e:
            logger.debug(f"Chunks 파일 로드 실패: {e}")
            return []

    def print_report(self) -> None:
        """오늘의 머릿속 리포트 출력"""
        print("=" * 70)
        print("🧠 오늘 회사 머릿속 (Active Chunks)")
        print("=" * 70)
        print()
        
        if not self.chunks:
            print("❌ Active Chunk 없음")
            return
        
        # Root W 요약
        if self.root_w:
            will = self.root_w.get("will", {})
            drive = will.get("drive", "")
            if drive:
                print(f"🎯 핵심 의지: {drive[:60]}...")
                print()
        
        # Chunk 카드들
        print(f"📋 Active Chunks ({len(self.chunks)}/{MAX_ACTIVE_CHUNKS}):")
        print("-" * 70)
        
        for i, chunk in enumerate(self.chunks, 1):
            status_icon = "🔥" if chunk.salience_score >= 0.7 else "📌" if chunk.salience_score >= 0.5 else "📎"
            
            print(f"\n{status_icon} [{i}] {chunk.title}")
            print(f"    중요도: {chunk.salience_score:.0%}")
            print(f"    이유: {chunk.why_important[:50]}..." if len(chunk.why_important) > 50 else f"    이유: {chunk.why_important}")
            
            # 스코어 상세
            bd = chunk.score_breakdown
            print(f"    점수: Will {bd.get('will_resonance', 0):.0%} | Goal {bd.get('goal_relevance', 0):.0%} | Intent {bd.get('intention_alignment', 0):.0%}")
            
            # 다음 행동
            if chunk.next_actions:
                print(f"    다음: {chunk.next_actions[0]}" if chunk.next_actions else "")
        
        print()
        print("-" * 70)
        print(f"ℹ️  W 기반 중요도 판단 (Top-{MAX_ACTIVE_CHUNKS} 유지)")
        print("=" * 70)


def main():
    """CLI 실행"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Chunk Engine - W 기반 중요도 판단")
    parser.add_argument("command", choices=["generate", "show", "export"],
                       help="명령: generate(생성), show(표시), export(JSON 내보내기)")
    
    args = parser.parse_args()
    
    script_dir = Path(__file__).parent
    manager = ChunkManager(str(script_dir.parent))
    
    if args.command == "generate":
        print("📂 Holon 로드 중...")
        manager.load_holons()
        print(f"   로드된 문서: {len(manager.holons)}개")
        print()
        
        print("🧮 Salience 계산 중...")
        manager.generate_chunks()
        manager.save_chunks()
        print(f"   생성된 Chunk: {len(manager.chunks)}개")
        print()
        
        manager.print_report()
        
    elif args.command == "show":
        manager.load_holons()
        chunks = manager.load_chunks()
        if chunks:
            manager.chunks = chunks
            manager.root_w = manager.find_root_w()
            manager.print_report()
        else:
            print("❌ 저장된 Chunk 없음. 'generate' 먼저 실행하세요.")
    
    elif args.command == "export":
        chunks = manager.load_chunks()
        if chunks:
            print(json.dumps([c.to_dict() for c in chunks], ensure_ascii=False, indent=2))
        else:
            print("[]")


if __name__ == "__main__":
    main()

