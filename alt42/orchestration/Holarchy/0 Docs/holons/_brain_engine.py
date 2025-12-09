#!/usr/bin/env python3
"""
🧠 Enterprise Brain Search & Memory System v2.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

사람의 조절 + AI 자동화가 결합된 지식 결정 시스템

4대 가중치 (Memory Weights):
  - WR: Recency (최근성) - 최신 문서 우선
  - WP: Popularity (조회수) - 사용 빈도 반영
  - WV: Relevance (상관도) - 의미적 거리
  - WM: Importance (중요도) - 조직적 가치

최종 점수: Score = WR*R + WP*P + WV*V + WM*M
"""

import json
import re
import logging
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass, field
from enum import Enum

# 로깅 설정
logger = logging.getLogger("holarchy.brain_engine")


# ═══════════════════════════════════════════════════════════════════
# 자연어 리뷰 → 점수 자동 보정 시스템
# ═══════════════════════════════════════════════════════════════════

class ReviewClassifier:
    """
    사용자의 간단한 리뷰를 positive/negative/actionable로 분류
    
    예시:
        "좋아" → positive
        "헷갈림" → negative  
        "다시 봐야함" → actionable
    """
    RULES = {
        "positive": ["좋", "정확", "중요", "깔끔", "핵심", "유용", "맞", "훌륭", "완벽", "필요"],
        "negative": ["헷갈", "틀림", "애매", "불필요", "관련없", "약함", "이상", "오류", "잘못", "아님"],
        "actionable": ["다시", "보완", "업데이트", "수정", "정리", "확인", "검토", "추가", "개선"]
    }
    
    @staticmethod
    def classify(text: str) -> str:
        """리뷰 텍스트를 라벨로 분류"""
        text_lower = text.lower()
        
        # 우선순위: actionable > negative > positive
        for label in ["actionable", "negative", "positive"]:
            keywords = ReviewClassifier.RULES[label]
            if any(k in text_lower for k in keywords):
                return label
        
        return "positive"  # 기본값


class ReviewAdjuster:
    """
    리뷰 라벨에 따라 점수 자동 조정
    
    - positive: importance↑ relevance↑ accuracy↑ authority↑
    - negative: importance↓ relevance↓ accuracy↓
    - actionable: strategic_value↑ reusability↑ importance slight↑
    """
    ADJUST = {
        "positive": {
            "importance": +0.15,
            "relevance": +0.10,
            "accuracy": +1,
            "authority": +1
        },
        "negative": {
            "importance": -0.15,
            "relevance": -0.10,
            "accuracy": -1
        },
        "actionable": {
            "importance": +0.05,
            "reusability": +1,
            "strategic_value": +1
        }
    }
    
    @staticmethod
    def apply(review_label: str, eval_dict: Dict) -> Dict:
        """점수 조정 적용 (0~5 범위 자동 클램핑)"""
        adj = ReviewAdjuster.ADJUST.get(review_label, {})
        updated = eval_dict.copy()
        
        for key, delta in adj.items():
            current = updated.get(key, 0)
            # 0~5 범위로 클램핑 (importance/relevance는 0~1)
            if key in ["importance", "relevance"]:
                updated[key] = max(0.0, min(1.0, current + delta))
            else:
                updated[key] = max(0, min(5, int(current + delta)))
        
        return updated


class MemoryProfile(Enum):
    """Memory Style Presets - 5가지 기억 스타일"""
    FAST_FRESH = "fast_fresh"      # 최신 문서 중심
    WISDOM = "wisdom"              # 중요도 중심 (오래되어도 중요한 것)
    BALANCED = "balanced"          # 균형 모드
    PATTERN_MINING = "pattern"     # LTM 핵심 규칙 탐색
    TREND = "trend"                # 최신 + 조회수 트렌드


@dataclass
class MemoryWeights:
    """4대 가중치 설정"""
    recency: float = 0.25      # WR: 최근성
    popularity: float = 0.25   # WP: 조회수
    relevance: float = 0.25    # WV: 상관도
    importance: float = 0.25   # WM: 중요도
    
    # 프리셋 정의
    PRESETS = {
        MemoryProfile.FAST_FRESH: {"recency": 0.5, "popularity": 0.1, "relevance": 0.3, "importance": 0.1},
        MemoryProfile.WISDOM: {"recency": 0.1, "popularity": 0.1, "relevance": 0.3, "importance": 0.5},
        MemoryProfile.BALANCED: {"recency": 0.25, "popularity": 0.25, "relevance": 0.25, "importance": 0.25},
        MemoryProfile.PATTERN_MINING: {"recency": 0.0, "popularity": 0.1, "relevance": 0.4, "importance": 0.5},
        MemoryProfile.TREND: {"recency": 0.4, "popularity": 0.4, "relevance": 0.2, "importance": 0.0},
    }
    
    @classmethod
    def from_profile(cls, profile: MemoryProfile) -> 'MemoryWeights':
        """프리셋에서 가중치 생성"""
        preset = cls.PRESETS.get(profile, cls.PRESETS[MemoryProfile.BALANCED])
        return cls(**preset)
    
    def validate(self) -> bool:
        """가중치 합이 1.0인지 확인"""
        total = self.recency + self.popularity + self.relevance + self.importance
        return abs(total - 1.0) < 0.01
    
    def normalize(self):
        """가중치 정규화 (합이 1.0이 되도록)"""
        total = self.recency + self.popularity + self.relevance + self.importance
        if total > 0:
            self.recency /= total
            self.popularity /= total
            self.relevance /= total
            self.importance /= total


@dataclass
class DocumentScore:
    """문서별 4차원 점수"""
    holon_id: str
    filename: str
    filepath: str
    
    # 4대 점수 (0~1)
    recency_score: float = 0.0      # R: 최근성 점수
    popularity_score: float = 0.0   # P: 조회수 점수
    relevance_score: float = 0.0    # V: 상관도 점수 (쿼리 의존)
    importance_score: float = 0.0   # M: 중요도 점수
    
    # 메타데이터
    created_at: str = ""
    updated_at: str = ""
    age_days: int = 0
    access_count: int = 0
    doc_type: str = ""
    layer: str = "wm"  # wm, ltm, archive
    
    # 사람 평가 (0~5)
    human_eval: Dict = field(default_factory=lambda: {
        "accuracy": 0,          # 정확성
        "importance": 0,        # 조직적 중요성
        "reusability": 0,       # 재사용 가능성
        "authority": 0,         # 신뢰도
        "strategic_value": 0    # 전략적 가치
    })
    
    def calculate_final_score(self, weights: MemoryWeights) -> float:
        """최종 점수 계산: Score = WR*R + WP*P + WV*V + WM*M"""
        score = (
            weights.recency * self.recency_score +
            weights.popularity * self.popularity_score +
            weights.relevance * self.relevance_score +
            weights.importance * self.importance_score
        )
        return min(1.0, max(0.0, score))
    
    def get_human_eval_avg(self) -> float:
        """사람 평가 평균 (0~1 정규화)"""
        values = list(self.human_eval.values())
        if not values:
            return 0.0
        return sum(values) / (len(values) * 5)  # 5점 만점 정규화


class BrainEngine:
    """Enterprise Brain Search & Memory System"""
    
    # 메모리 레이어 정의
    MEMORY_LAYERS = {
        "wm": {"name": "Working Memory", "max_age_days": 90, "description": "최근 90일"},
        "ltm": {"name": "Long-term Memory", "max_age_days": 365, "description": "압축 저장"},
        "archive": {"name": "Archive", "max_age_days": None, "description": "원본 저장소"}
    }
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.hte_path = self.base_path.parent.parent / "2 Company" / "4 HTE"
        self.reports_path = self.docs_root / "reports"
        
        # 설정 파일 경로
        self.config_path = self.reports_path / "brain_config.json"
        self.access_log_path = self.reports_path / "access_log.json"
        self.eval_path = self.reports_path / "human_evaluations.json"
        
        # 현재 가중치 (기본: Balanced)
        self.weights = MemoryWeights()
        self.current_profile = MemoryProfile.BALANCED
        
        # 디렉토리 생성
        self.reports_path.mkdir(parents=True, exist_ok=True)
        
        # 설정 로드
        self._load_config()
    
    def _load_config(self):
        """설정 로드"""
        if self.config_path.exists():
            with open(self.config_path, "r", encoding="utf-8") as f:
                config = json.load(f)
                self.weights = MemoryWeights(
                    recency=config.get("weights", {}).get("recency", 0.25),
                    popularity=config.get("weights", {}).get("popularity", 0.25),
                    relevance=config.get("weights", {}).get("relevance", 0.25),
                    importance=config.get("weights", {}).get("importance", 0.25)
                )
                profile_name = config.get("profile", "balanced")
                try:
                    self.current_profile = MemoryProfile(profile_name)
                except ValueError as e:
                    logger.debug(f"MemoryProfile 파싱 실패 [{profile_name}]: {e}")
                    self.current_profile = MemoryProfile.BALANCED
    
    def _save_config(self):
        """설정 저장"""
        config = {
            "profile": self.current_profile.value,
            "weights": {
                "recency": self.weights.recency,
                "popularity": self.weights.popularity,
                "relevance": self.weights.relevance,
                "importance": self.weights.importance
            },
            "updated_at": datetime.now().isoformat()
        }
        with open(self.config_path, "w", encoding="utf-8") as f:
            json.dump(config, f, ensure_ascii=False, indent=2)
    
    def set_profile(self, profile: MemoryProfile):
        """프리셋 적용"""
        self.current_profile = profile
        self.weights = MemoryWeights.from_profile(profile)
        self._save_config()
    
    def set_weights(self, recency: float, popularity: float, 
                   relevance: float, importance: float):
        """가중치 직접 설정"""
        self.weights = MemoryWeights(
            recency=recency,
            popularity=popularity,
            relevance=relevance,
            importance=importance
        )
        self.weights.normalize()  # 정규화
        self.current_profile = MemoryProfile.BALANCED  # Custom
        self._save_config()
    
    def _load_access_log(self) -> Dict:
        """접근 기록 로드"""
        if self.access_log_path.exists():
            with open(self.access_log_path, "r", encoding="utf-8") as f:
                return json.load(f)
        return {"documents": {}, "last_updated": None}
    
    def _save_access_log(self, log: Dict):
        """접근 기록 저장"""
        log["last_updated"] = datetime.now().isoformat()
        with open(self.access_log_path, "w", encoding="utf-8") as f:
            json.dump(log, f, ensure_ascii=False, indent=2)
    
    def _load_evaluations(self) -> Dict:
        """사람 평가 로드"""
        if self.eval_path.exists():
            with open(self.eval_path, "r", encoding="utf-8") as f:
                return json.load(f)
        return {"evaluations": {}}
    
    def _save_evaluations(self, evals: Dict):
        """사람 평가 저장"""
        evals["updated_at"] = datetime.now().isoformat()
        with open(self.eval_path, "w", encoding="utf-8") as f:
            json.dump(evals, f, ensure_ascii=False, indent=2)
    
    def record_access(self, holon_id: str):
        """문서 접근 기록 (조회수 증가)"""
        log = self._load_access_log()
        
        if holon_id not in log["documents"]:
            log["documents"][holon_id] = {"count": 0, "accesses": []}
        
        log["documents"][holon_id]["count"] += 1
        log["documents"][holon_id]["accesses"].append(datetime.now().isoformat())
        
        # 최근 100개만 유지
        log["documents"][holon_id]["accesses"] = log["documents"][holon_id]["accesses"][-100:]
        
        self._save_access_log(log)
    
    def evaluate_document(self, holon_id: str, accuracy: int = 0, importance: int = 0,
                         reusability: int = 0, authority: int = 0, strategic_value: int = 0):
        """문서 평가 (사람이 0~5 점수 부여)"""
        evals = self._load_evaluations()
        
        evals["evaluations"][holon_id] = {
            "accuracy": min(5, max(0, accuracy)),
            "importance": min(5, max(0, importance)),
            "reusability": min(5, max(0, reusability)),
            "authority": min(5, max(0, authority)),
            "strategic_value": min(5, max(0, strategic_value)),
            "evaluated_at": datetime.now().isoformat()
        }
        
        self._save_evaluations(evals)
    
    def review_document(self, holon_id: str, review_text: str) -> Dict:
        """
        🎯 자연어 리뷰 기반 자동 점수 보정
        
        사용자가 간단히 한 줄만 입력하면 시스템이 자동으로:
        - Importance ↑↓
        - Relevance ↑↓
        - Human Evaluation Score 자동 업데이트
        - 미래 검색 점수 반영
        - 문서 레이어 자동 이동 (WM → LTM 등)
        
        Args:
            holon_id: 문서 ID
            review_text: 자연어 리뷰 (예: "좋아", "헷갈림", "다시 봐야함")
        
        Returns:
            {"label": str, "before": dict, "after": dict}
        """
        # 1) 리뷰 라벨링
        label = ReviewClassifier.classify(review_text)
        
        # 2) 기존 평가 불러오기
        evals = self._load_evaluations()
        
        before = evals["evaluations"].get(holon_id, {
            "accuracy": 0,
            "importance": 0,
            "reusability": 0,
            "authority": 0,
            "strategic_value": 0
        })
        
        # 3) 자동 보정
        after = ReviewAdjuster.apply(label, before)
        after["evaluated_at"] = datetime.now().isoformat()
        after["last_review"] = review_text
        after["last_label"] = label
        
        # 4) 저장
        evals["evaluations"][holon_id] = after
        self._save_evaluations(evals)
        
        # 5) 문서 접근 기록 증가 (학습 효과)
        self.record_access(holon_id)
        
        return {
            "label": label,
            "before": before,
            "after": after
        }
    
    def print_review_result(self, result: Dict):
        """리뷰 결과 출력"""
        label = result["label"]
        before = result["before"]
        after = result["after"]
        
        # 이모지 매핑
        emoji_map = {"positive": "👍", "negative": "👎", "actionable": "📝"}
        emoji = emoji_map.get(label, "📌")
        
        print()
        print(f"{emoji} 리뷰 처리 완료: {label.upper()}")
        print("-" * 40)
        
        # 변화 표시
        for key in ["accuracy", "importance", "reusability", "authority", "strategic_value"]:
            b = before.get(key, 0)
            a = after.get(key, 0)
            if a != b:
                arrow = "↑" if a > b else "↓"
                print(f"   {key}: {b} → {a} {arrow}")
        
        print()
    
    def _calculate_recency(self, age_days: int) -> float:
        """최근성 점수 계산 (R)"""
        if age_days <= 7:
            return 1.0
        elif age_days <= 30:
            return 0.8
        elif age_days <= 90:
            return 0.5
        elif age_days <= 180:
            return 0.3
        elif age_days <= 365:
            return 0.1
        else:
            return 0.0
    
    def _calculate_popularity(self, holon_id: str) -> float:
        """조회수 점수 계산 (P)"""
        log = self._load_access_log()
        
        if holon_id not in log["documents"]:
            return 0.0
        
        doc_log = log["documents"][holon_id]
        count = doc_log.get("count", 0)
        
        # 최근 90일 내 접근 횟수
        recent_accesses = 0
        cutoff = datetime.now() - timedelta(days=90)
        
        for access_time in doc_log.get("accesses", []):
            try:
                access_dt = datetime.fromisoformat(access_time)
                if access_dt > cutoff:
                    recent_accesses += 1
            except ValueError as e:
                logger.debug(f"접근 시간 파싱 실패 [{access_time}]: {e}")
        
        # 10회 이상이면 1.0
        return min(1.0, recent_accesses / 10.0)
    
    def _calculate_relevance(self, content: str, query: str = "", 
                             holon: Dict = None) -> float:
        """
        상관도 점수 계산 (V) - W 기반 벡터 유사도 + 키워드 매칭 하이브리드
        
        Args:
            content: 문서 전체 내용
            query: 검색 쿼리
            holon: Holon JSON (W 필드 추출용)
        """
        if not query:
            return 0.5  # 기본값
        
        # 1. W.will.drive 벡터 유사도 (있으면)
        vector_score = 0.0
        if holon:
            w_drive = holon.get("W", {}).get("will", {}).get("drive", "")
            if w_drive:
                try:
                    from _vector_rag import VectorRAGEngine
                    engine = VectorRAGEngine(str(self.base_path))
                    
                    query_embedding = engine._get_embedding(query)
                    doc_embedding = engine._get_embedding(w_drive)
                    
                    vector_score = engine._cosine_similarity(query_embedding, doc_embedding)
                except Exception as e:
                    # 벡터 RAG 실패 시 키워드 매칭으로 폴백
                    pass
        
        # 2. 키워드 매칭 (폴백 또는 보완)
        content_lower = content.lower()
        query_words = query.lower().split()
        
        matched = sum(1 for word in query_words if word in content_lower)
        
        if len(query_words) == 0:
            keyword_score = 0.5
        else:
            keyword_score = min(1.0, matched / len(query_words))
        
        # 3. 하이브리드 점수: 벡터 70% + 키워드 30%
        if vector_score > 0:
            return (vector_score * 0.7) + (keyword_score * 0.3)
        else:
            return keyword_score
    
    def _calculate_importance(self, holon: Dict, content: str) -> float:
        """중요도 점수 계산 (M) - 기존 M-score 로직"""
        # 성과 영향 키워드
        impact_keywords = ["매출", "성과", "KPI", "OKR", "달성", "목표", "수익", "성장", "효율"]
        # 시스템 반영 키워드
        system_keywords = ["규칙", "원칙", "정책", "기준", "표준", "프로세스", "철학", "가이드"]
        # 감정적 임팩트 키워드
        emotional_keywords = ["위기", "실패", "성공", "폭증", "폭락", "해결", "돌파", "혁신"]
        
        content_lower = content.lower()
        
        # 점수 계산
        impact = sum(0.1 for k in impact_keywords if k in content_lower)
        system = sum(0.15 for k in system_keywords if k in content_lower)
        emotional = sum(0.1 for k in emotional_keywords if k in content_lower)
        
        # 사람 평가 반영
        evals = self._load_evaluations()
        holon_id = holon.get("holon_id", "")
        if holon_id in evals.get("evaluations", {}):
            eval_data = evals["evaluations"][holon_id]
            human_avg = sum(eval_data.get(k, 0) for k in 
                          ["accuracy", "importance", "reusability", "authority", "strategic_value"])
            human_score = human_avg / 25.0  # 25점 만점
            
            # 키워드 점수 + 사람 평가 점수 결합
            return min(1.0, (impact + system + emotional + human_score) / 2)
        
        return min(1.0, impact + system + emotional)
    
    def _determine_layer(self, age_days: int, importance: float) -> str:
        """메모리 레이어 결정"""
        if age_days <= 90:
            return "wm"  # Working Memory
        elif importance >= 0.5 or age_days <= 365:
            return "ltm"  # Long-term Memory
        else:
            return "archive"  # Archive
    
    def _parse_holon(self, filepath: Path) -> Optional[Dict]:
        """Holon JSON 파싱"""
        try:
            content = filepath.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            if json_match:
                return json.loads(json_match.group(1))
        except (json.JSONDecodeError, FileNotFoundError, UnicodeDecodeError) as e:
            logger.debug(f"Holon 파싱 실패 [{filepath.name}]: {e}")
        return None
    
    def analyze_document(self, filepath: Path, query: str = "") -> Optional[DocumentScore]:
        """단일 문서 분석"""
        if not filepath.exists():
            return None
        
        content = filepath.read_text(encoding="utf-8")
        holon = self._parse_holon(filepath)
        
        if not holon:
            return None
        
        holon_id = holon.get("holon_id", filepath.stem)
        meta = holon.get("meta", {})
        
        # 날짜 파싱
        created_at = meta.get("created_at", datetime.now().strftime("%Y-%m-%d"))
        updated_at = meta.get("updated_at", created_at)
        
        try:
            created_dt = datetime.strptime(created_at[:10], "%Y-%m-%d")
            age_days = (datetime.now() - created_dt).days
        except ValueError as e:
            logger.debug(f"날짜 파싱 실패 [{created_at}]: {e}")
            age_days = 0
        
        # 4대 점수 계산
        recency_score = self._calculate_recency(age_days)
        popularity_score = self._calculate_popularity(holon_id)
        relevance_score = self._calculate_relevance(content, query, holon)  # W 기반 벡터 유사도
        importance_score = self._calculate_importance(holon, content)
        
        # 접근 기록 카운트
        log = self._load_access_log()
        access_count = log.get("documents", {}).get(holon_id, {}).get("count", 0)
        
        # 레이어 결정
        layer = self._determine_layer(age_days, importance_score)
        
        # 사람 평가 로드
        evals = self._load_evaluations()
        human_eval = evals.get("evaluations", {}).get(holon_id, {
            "accuracy": 0, "importance": 0, "reusability": 0,
            "authority": 0, "strategic_value": 0
        })
        
        return DocumentScore(
            holon_id=holon_id,
            filename=filepath.name,
            filepath=str(filepath),
            recency_score=recency_score,
            popularity_score=popularity_score,
            relevance_score=relevance_score,
            importance_score=importance_score,
            created_at=created_at,
            updated_at=updated_at,
            age_days=age_days,
            access_count=access_count,
            doc_type=holon.get("type", "unknown"),
            layer=layer,
            human_eval=human_eval
        )
    
    def search(self, query: str = "", limit: int = 20) -> List[Tuple[DocumentScore, float]]:
        """검색 실행 - 4대 가중치 기반 순위"""
        all_scores = []
        
        # holons 폴더
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            score = self.analyze_document(md_file, query)
            if score:
                all_scores.append(score)
        
        # meetings/decisions/tasks 폴더
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                for md_file in folder_path.glob("*.md"):
                    if md_file.name.startswith("_"):
                        continue
                    score = self.analyze_document(md_file, query)
                    if score:
                        all_scores.append(score)
        
        # HTE 모듈 폴더
        if self.hte_path.exists():
            for md_file in self.hte_path.rglob("HTE_*.md"):
                score = self.analyze_document(md_file, query)
                if score:
                    all_scores.append(score)
        
        # 최종 점수 계산 및 정렬
        results = []
        for score in all_scores:
            final = score.calculate_final_score(self.weights)
            results.append((score, final))
        
        # 점수 기준 내림차순 정렬
        results.sort(key=lambda x: x[1], reverse=True)
        
        return results[:limit]
    
    def get_by_layer(self, layer: str) -> List[DocumentScore]:
        """특정 레이어의 문서들"""
        all_docs = self.search(limit=1000)
        return [doc for doc, _ in all_docs if doc.layer == layer]
    
    def print_weights_panel(self):
        """가중치 조절 패널 출력"""
        print("=" * 60)
        print("🎛️  Memory Tuning Panel - 가중치 조절")
        print("=" * 60)
        print()
        
        def make_bar(value: float) -> str:
            filled = int(value * 20)
            return "█" * filled + "░" * (20 - filled)
        
        print(f"  [최근성 (Recency)]     {make_bar(self.weights.recency)} {self.weights.recency:.2f}")
        print(f"  [조회수 (Popularity)]  {make_bar(self.weights.popularity)} {self.weights.popularity:.2f}")
        print(f"  [상관도 (Relevance)]   {make_bar(self.weights.relevance)} {self.weights.relevance:.2f}")
        print(f"  [중요도 (Importance)]  {make_bar(self.weights.importance)} {self.weights.importance:.2f}")
        print()
        print(f"  📋 현재 프로필: {self.current_profile.value}")
        print()
    
    def print_profiles(self):
        """프로필 목록 출력"""
        print("=" * 60)
        print("🎨 Memory Style Presets - 기억 스타일 프리셋")
        print("=" * 60)
        print()
        
        profiles = [
            (MemoryProfile.FAST_FRESH, "⚡ Fast & Fresh", "최신 문서 중심, 실무 최신 정보"),
            (MemoryProfile.WISDOM, "📚 Wisdom", "중요도 중심, 오래되어도 핵심 문서"),
            (MemoryProfile.BALANCED, "⚖️ Balanced", "모든 요소 균형"),
            (MemoryProfile.PATTERN_MINING, "🔍 Pattern Mining", "LTM 핵심 규칙 탐색"),
            (MemoryProfile.TREND, "📈 Trend", "최신 + 조회수 트렌드"),
        ]
        
        for profile, name, desc in profiles:
            preset = MemoryWeights.PRESETS[profile]
            active = "→" if profile == self.current_profile else " "
            print(f"  {active} {name:20} {desc}")
            print(f"      WR={preset['recency']:.1f}  WP={preset['popularity']:.1f}  "
                  f"WV={preset['relevance']:.1f}  WM={preset['importance']:.1f}")
            print()
    
    def print_search_results(self, query: str = "", limit: int = 15):
        """검색 결과 출력"""
        print("=" * 70)
        print(f"🔍 Enterprise Brain Search")
        if query:
            print(f"   쿼리: \"{query}\"")
        print(f"   프로필: {self.current_profile.value}")
        print("=" * 70)
        print()
        
        self.print_weights_panel()
        
        results = self.search(query, limit)
        
        if not results:
            print("❌ 검색 결과 없음")
            return
        
        layer_emoji = {"wm": "🟠", "ltm": "🟢", "archive": "📦"}
        
        print("📊 검색 결과 (최종 점수 기준):")
        print("-" * 70)
        print(f"{'순위':^4} {'문서':^35} {'점수':^8} {'레이어':^8} {'R':^5} {'P':^5} {'V':^5} {'M':^5}")
        print("-" * 70)
        
        for i, (doc, final_score) in enumerate(results, 1):
            emoji = layer_emoji.get(doc.layer, "❓")
            print(f"{i:3}. {doc.filename[:33]:33} {final_score:.3f}   "
                  f"{emoji} {doc.layer:6} "
                  f"{doc.recency_score:.2f} {doc.popularity_score:.2f} "
                  f"{doc.relevance_score:.2f} {doc.importance_score:.2f}")
        
        print()
        print("💡 점수 해석:")
        print("   R=최근성, P=조회수, V=상관도, M=중요도")
        print(f"   Final = WR×R + WP×P + WV×V + WM×M")
        print()
    
    def print_layer_summary(self):
        """메모리 레이어별 요약"""
        print("=" * 60)
        print("🧠 Enterprise Memory Architecture")
        print("=" * 60)
        print()
        
        all_results = self.search(limit=1000)
        
        layer_stats = {"wm": [], "ltm": [], "archive": []}
        for doc, score in all_results:
            layer_stats[doc.layer].append((doc, score))
        
        layer_info = {
            "wm": ("🟠", "Working Memory", "최근 90일"),
            "ltm": ("🟢", "Long-term Memory", "압축 저장"),
            "archive": ("📦", "Archive", "원본 저장소")
        }
        
        for layer, (emoji, name, desc) in layer_info.items():
            docs = layer_stats[layer]
            avg_score = sum(s for _, s in docs) / len(docs) if docs else 0
            
            print(f"{emoji} {name} [{desc}]")
            print(f"   문서 수: {len(docs)}개 | 평균 점수: {avg_score:.3f}")
            print("-" * 50)
            
            for doc, score in docs[:5]:
                print(f"   {doc.filename[:35]:35} 점수: {score:.3f}")
            
            if len(docs) > 5:
                print(f"   ... 외 {len(docs) - 5}개")
            print()
    
    def save_report(self) -> Path:
        """리포트 JSON 저장"""
        results = self.search(limit=1000)
        
        report = {
            "generated_at": datetime.now().isoformat(),
            "profile": self.current_profile.value,
            "weights": {
                "recency": self.weights.recency,
                "popularity": self.weights.popularity,
                "relevance": self.weights.relevance,
                "importance": self.weights.importance
            },
            "total_documents": len(results),
            "layers": {"wm": 0, "ltm": 0, "archive": 0},
            "documents": []
        }
        
        for doc, final_score in results:
            report["layers"][doc.layer] += 1
            report["documents"].append({
                "holon_id": doc.holon_id,
                "filename": doc.filename,
                "final_score": round(final_score, 4),
                "layer": doc.layer,
                "scores": {
                    "recency": round(doc.recency_score, 3),
                    "popularity": round(doc.popularity_score, 3),
                    "relevance": round(doc.relevance_score, 3),
                    "importance": round(doc.importance_score, 3)
                },
                "human_eval": doc.human_eval,
                "age_days": doc.age_days,
                "access_count": doc.access_count
            })
        
        report_path = self.reports_path / "brain_search_report.json"
        with open(report_path, "w", encoding="utf-8") as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        
        return report_path


def main():
    """테스트"""
    engine = BrainEngine()
    engine.print_profiles()
    engine.print_search_results()


if __name__ == "__main__":
    main()

