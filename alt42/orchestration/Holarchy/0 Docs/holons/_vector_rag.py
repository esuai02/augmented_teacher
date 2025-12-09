#!/usr/bin/env python3
"""
🧠 Vector RAG Engine - W(Worldview) 기반 의미적 검색
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

핵심 기능:
1. W.will.drive 필드를 벡터 임베딩
2. 의미적 유사도 기반 검색 (코사인 유사도)
3. 링크 그래프 탐색으로 관련 문서 확장
4. 하이브리드 검색 (벡터 + 그래프 + 태그)

의도:
- 단순 키워드 매칭이 아닌 의미 기반 검색
- Holon의 W(의지) 필드를 핵심 검색 대상으로 사용
- 링크 구조를 활용한 컨텍스트 확장
"""

import json
import re
import os
import logging
import numpy as np
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass, field

# 로깅 설정
logger = logging.getLogger("holarchy.vector_rag")
import hashlib

# API 설정
OPENAI_API_KEY = os.environ.get("OPENAI_API_KEY", "")  # 환경변수에서 로드
EMBEDDING_MODEL = "text-embedding-3-small"
EMBEDDING_DIMENSIONS = 1536

# 로컬 임베딩 설정 (sentence-transformers)
LOCAL_EMBEDDING_MODEL = "paraphrase-multilingual-MiniLM-L12-v2"  # 한국어 지원
LOCAL_EMBEDDING_DIMENSIONS = 384
USE_LOCAL_EMBEDDING = True  # True면 로컬 모델 사용


@dataclass
class VectorDocument:
    """벡터화된 문서"""
    holon_id: str
    filepath: str
    title: str
    
    # W 필드 내용
    w_drive: str  # W.will.drive
    w_worldview: str  # W.worldview (전체)
    
    # 임베딩 벡터
    embedding: List[float] = field(default_factory=list)
    
    # 링크 정보
    parent: str = ""
    children: List[str] = field(default_factory=list)
    related: List[str] = field(default_factory=list)
    
    # 태그
    tags: Dict = field(default_factory=dict)
    
    # 유사도 점수 (검색 시 계산)
    similarity_score: float = 0.0


class VectorRAGEngine:
    """W 기반 벡터 RAG 엔진"""
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.hte_path = self.docs_root.parent / "2 Company" / "4 HTE"
        
        # 벡터 캐시 경로
        self.cache_path = self.docs_root / "reports" / "vector_cache.json"
        self.cache_path.parent.mkdir(parents=True, exist_ok=True)
        
        # 문서 & 벡터 캐시
        self.documents: Dict[str, VectorDocument] = {}
        self.vector_cache: Dict[str, List[float]] = {}
        
        # 캐시 로드
        self._load_cache()
    
    # ═══════════════════════════════════════════════════════════════════
    # OpenAI 임베딩
    # ═══════════════════════════════════════════════════════════════════
    
    def _get_embedding(self, text: str, use_cache: bool = True) -> List[float]:
        """
        텍스트를 벡터 임베딩으로 변환
        
        Args:
            text: 임베딩할 텍스트
            use_cache: 캐시 사용 여부
        
        Returns:
            벡터 (로컬: 384차원, OpenAI: 1536차원)
        """
        if not text or not text.strip():
            dim = LOCAL_EMBEDDING_DIMENSIONS if USE_LOCAL_EMBEDDING else EMBEDDING_DIMENSIONS
            return [0.0] * dim
        
        # 캐시 키 생성 (텍스트 해시)
        cache_key = hashlib.md5(text.encode()).hexdigest()
        
        if use_cache and cache_key in self.vector_cache:
            return self.vector_cache[cache_key]
        
        # 로컬 임베딩 (sentence-transformers)
        if USE_LOCAL_EMBEDDING:
            try:
                from sentence_transformers import SentenceTransformer
                
                if not hasattr(self, '_local_model'):
                    print("   📦 로컬 임베딩 모델 로딩 중...")
                    self._local_model = SentenceTransformer(LOCAL_EMBEDDING_MODEL)
                
                embedding = self._local_model.encode(text[:2000]).tolist()
                
                # 캐시 저장
                self.vector_cache[cache_key] = embedding
                
                return embedding
                
            except ImportError:
                print("⚠️ sentence-transformers 필요: pip install sentence-transformers")
                return [0.0] * LOCAL_EMBEDDING_DIMENSIONS
            except Exception as e:
                print(f"⚠️ 로컬 임베딩 오류: {e}")
                return [0.0] * LOCAL_EMBEDDING_DIMENSIONS
        
        # OpenAI 임베딩
        try:
            import openai
            
            if not OPENAI_API_KEY:
                print("⚠️ OPENAI_API_KEY 환경변수 필요")
                return [0.0] * EMBEDDING_DIMENSIONS
            
            client = openai.OpenAI(api_key=OPENAI_API_KEY)
            
            response = client.embeddings.create(
                input=text[:8000],  # 토큰 제한
                model=EMBEDDING_MODEL
            )
            
            embedding = response.data[0].embedding
            
            # 캐시 저장
            self.vector_cache[cache_key] = embedding
            
            return embedding
            
        except ImportError:
            print("⚠️ openai 패키지 필요: pip install openai")
            return [0.0] * EMBEDDING_DIMENSIONS
        except Exception as e:
            print(f"⚠️ OpenAI 임베딩 오류: {e}")
            return [0.0] * EMBEDDING_DIMENSIONS
    
    def _cosine_similarity(self, vec1: List[float], vec2: List[float]) -> float:
        """코사인 유사도 계산"""
        if not vec1 or not vec2:
            return 0.0
        
        a = np.array(vec1)
        b = np.array(vec2)
        
        dot = np.dot(a, b)
        norm_a = np.linalg.norm(a)
        norm_b = np.linalg.norm(b)
        
        if norm_a == 0 or norm_b == 0:
            return 0.0
        
        return float(dot / (norm_a * norm_b))
    
    # ═══════════════════════════════════════════════════════════════════
    # 문서 로드 & 인덱싱
    # ═══════════════════════════════════════════════════════════════════
    
    def _extract_w_content(self, holon: Dict) -> Tuple[str, str]:
        """
        W(Worldview) 필드에서 핵심 내용 추출
        
        Returns:
            (w_drive, w_worldview_full)
        """
        w = holon.get("W", {})
        
        # W.will.drive (핵심 의지)
        w_drive = w.get("will", {}).get("drive", "")
        
        # W 전체를 텍스트로
        w_full_parts = []
        
        # worldview
        worldview = w.get("worldview", {})
        if isinstance(worldview, dict):
            w_full_parts.append(worldview.get("identity", ""))
            w_full_parts.append(worldview.get("belief", ""))
            w_full_parts.append(worldview.get("value_system", ""))
        
        # will
        will = w.get("will", {})
        if isinstance(will, dict):
            w_full_parts.append(will.get("drive", ""))
            w_full_parts.append(will.get("commitment", ""))
        
        # intention
        intention = w.get("intention", {})
        if isinstance(intention, dict):
            w_full_parts.append(intention.get("primary", ""))
        
        # goal
        goal = w.get("goal", {})
        if isinstance(goal, dict):
            w_full_parts.append(goal.get("ultimate", ""))
        
        w_worldview = " ".join(filter(None, w_full_parts))
        
        return w_drive, w_worldview
    
    def load_document(self, filepath: Path) -> Optional[VectorDocument]:
        """단일 문서 로드 및 벡터화"""
        if not filepath.exists():
            return None
        
        content = filepath.read_text(encoding="utf-8")
        
        # JSON 추출
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        if not json_match:
            return None
        
        try:
            holon = json.loads(json_match.group(1))
        except json.JSONDecodeError as e:
            logger.debug(f"Holon JSON 파싱 실패: {e}")
            return None
        
        holon_id = holon.get("holon_id", "")
        if not holon_id:
            return None
        
        # W 필드 추출
        w_drive, w_worldview = self._extract_w_content(holon)
        
        # 링크 추출
        links = holon.get("links", {})
        
        # 태그 추출
        tags = holon.get("meta", {}).get("tags", {})
        
        doc = VectorDocument(
            holon_id=holon_id,
            filepath=str(filepath),
            title=holon.get("meta", {}).get("title", filepath.stem),
            w_drive=w_drive,
            w_worldview=w_worldview,
            parent=links.get("parent", ""),
            children=links.get("children", []),
            related=links.get("related", []),
            tags=tags
        )
        
        return doc
    
    def index_all_documents(self, force_reindex: bool = False):
        """
        모든 문서 인덱싱 (W 필드 벡터화)
        
        Args:
            force_reindex: True면 캐시 무시하고 재인덱싱
        """
        print("=" * 60)
        print("🧠 Vector RAG Engine - W 기반 인덱싱")
        print("=" * 60)
        print()
        
        all_files = []
        
        # holons 폴더
        for md_file in self.holons_path.glob("*.md"):
            if not md_file.name.startswith("_"):
                all_files.append(md_file)
        
        # meetings/decisions/tasks
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                all_files.extend(folder_path.glob("*.md"))
        
        # HTE 폴더
        if self.hte_path.exists():
            all_files.extend(self.hte_path.rglob("HTE_*.md"))
        
        print(f"📁 총 {len(all_files)}개 문서 발견")
        print()
        
        indexed = 0
        for filepath in all_files:
            doc = self.load_document(filepath)
            if not doc:
                continue
            
            # 벡터 임베딩 (W.will.drive 기준)
            if doc.w_drive:
                print(f"   🔄 {doc.holon_id}: W.will.drive 임베딩 중...")
                doc.embedding = self._get_embedding(doc.w_drive)
            elif doc.w_worldview:
                print(f"   🔄 {doc.holon_id}: W 전체 임베딩 중...")
                doc.embedding = self._get_embedding(doc.w_worldview[:2000])
            else:
                print(f"   ⚠️ {doc.holon_id}: W 필드 없음")
                continue
            
            self.documents[doc.holon_id] = doc
            indexed += 1
        
        # 캐시 저장
        self._save_cache()
        
        print()
        print(f"✅ {indexed}개 문서 벡터 인덱싱 완료")
        print("=" * 60)
    
    # ═══════════════════════════════════════════════════════════════════
    # 검색
    # ═══════════════════════════════════════════════════════════════════
    
    def search_by_w(self, query: str, top_k: int = 10) -> List[VectorDocument]:
        """
        W(의지) 기반 의미적 검색
        
        Args:
            query: 검색 쿼리
            top_k: 반환할 문서 수
        
        Returns:
            유사도 순 정렬된 문서 리스트
        """
        if not self.documents:
            print("⚠️ 인덱싱된 문서가 없습니다. index_all_documents() 먼저 실행하세요.")
            return []
        
        # 쿼리 임베딩
        query_embedding = self._get_embedding(query)
        
        # 모든 문서와 유사도 계산
        results = []
        for holon_id, doc in self.documents.items():
            if doc.embedding:
                similarity = self._cosine_similarity(query_embedding, doc.embedding)
                doc.similarity_score = similarity
                results.append(doc)
        
        # 유사도 순 정렬
        results.sort(key=lambda x: x.similarity_score, reverse=True)
        
        return results[:top_k]
    
    def search_with_graph_expansion(self, query: str, top_k: int = 10, 
                                    expand_depth: int = 1) -> List[VectorDocument]:
        """
        검색 + 링크 그래프 확장
        
        1. W 기반 의미적 검색으로 초기 결과
        2. 링크(parent/children/related)를 따라 확장
        3. 중복 제거 후 반환
        
        Args:
            query: 검색 쿼리
            top_k: 초기 검색 결과 수
            expand_depth: 그래프 확장 깊이
        """
        # 1. 초기 검색
        initial_results = self.search_by_w(query, top_k=top_k)
        
        # 2. 그래프 확장
        expanded_ids = set()
        expanded_docs = []
        
        for doc in initial_results:
            expanded_ids.add(doc.holon_id)
            expanded_docs.append(doc)
            
            # parent 확장
            if doc.parent and doc.parent in self.documents:
                parent_doc = self.documents[doc.parent]
                if parent_doc.holon_id not in expanded_ids:
                    parent_doc.similarity_score = doc.similarity_score * 0.8  # 감쇠
                    expanded_docs.append(parent_doc)
                    expanded_ids.add(parent_doc.holon_id)
            
            # children 확장
            for child_id in doc.children[:3]:  # 최대 3개
                if child_id in self.documents and child_id not in expanded_ids:
                    child_doc = self.documents[child_id]
                    child_doc.similarity_score = doc.similarity_score * 0.7
                    expanded_docs.append(child_doc)
                    expanded_ids.add(child_id)
            
            # related 확장
            for related_id in doc.related[:2]:  # 최대 2개
                if related_id in self.documents and related_id not in expanded_ids:
                    related_doc = self.documents[related_id]
                    related_doc.similarity_score = doc.similarity_score * 0.6
                    expanded_docs.append(related_doc)
                    expanded_ids.add(related_id)
        
        # 3. 유사도 순 재정렬
        expanded_docs.sort(key=lambda x: x.similarity_score, reverse=True)
        
        return expanded_docs
    
    def hybrid_search(self, query: str, top_k: int = 10,
                     tag_filter: Dict = None) -> List[VectorDocument]:
        """
        하이브리드 검색: 벡터 + 그래프 + 태그 필터
        
        Args:
            query: 검색 쿼리
            top_k: 반환할 문서 수
            tag_filter: 태그 필터 (예: {"module": ["M16"], "topic": ["AI튜터"]})
        """
        # 1. 그래프 확장 검색
        results = self.search_with_graph_expansion(query, top_k=top_k * 2)
        
        # 2. 태그 필터링
        if tag_filter:
            filtered = []
            for doc in results:
                match = True
                for tag_key, tag_values in tag_filter.items():
                    doc_tags = doc.tags.get(tag_key, [])
                    if isinstance(doc_tags, str):
                        doc_tags = [doc_tags]
                    if not any(v in doc_tags for v in tag_values):
                        match = False
                        break
                if match:
                    filtered.append(doc)
            results = filtered
        
        return results[:top_k]
    
    # ═══════════════════════════════════════════════════════════════════
    # 캐시 관리
    # ═══════════════════════════════════════════════════════════════════
    
    def _save_cache(self):
        """벡터 캐시 저장"""
        cache_data = {
            "updated_at": datetime.now().isoformat(),
            "model": EMBEDDING_MODEL,
            "vectors": self.vector_cache
        }
        with open(self.cache_path, "w", encoding="utf-8") as f:
            json.dump(cache_data, f, ensure_ascii=False)
        print(f"💾 벡터 캐시 저장: {self.cache_path}")
    
    def _load_cache(self):
        """벡터 캐시 로드"""
        if self.cache_path.exists():
            try:
                with open(self.cache_path, "r", encoding="utf-8") as f:
                    cache_data = json.load(f)
                self.vector_cache = cache_data.get("vectors", {})
                print(f"📂 벡터 캐시 로드: {len(self.vector_cache)}개")
            except (json.JSONDecodeError, FileNotFoundError, UnicodeDecodeError) as e:
                logger.debug(f"벡터 캐시 로드 실패: {e}")
                self.vector_cache = {}
    
    # ═══════════════════════════════════════════════════════════════════
    # 출력
    # ═══════════════════════════════════════════════════════════════════
    
    def print_search_results(self, results: List[VectorDocument], query: str):
        """검색 결과 출력"""
        print()
        print("=" * 70)
        print(f"🔍 검색: \"{query}\"")
        print("=" * 70)
        print()
        print(f"{'#':3} {'유사도':8} {'문서 ID':25} {'제목':30}")
        print("-" * 70)
        
        for i, doc in enumerate(results, 1):
            similarity = f"{doc.similarity_score:.3f}"
            holon_id = doc.holon_id[:25]
            title = doc.title[:30]
            print(f"{i:3} {similarity:8} {holon_id:25} {title:30}")
        
        print()
        print(f"📊 총 {len(results)}개 결과")
        print("=" * 70)


# ═══════════════════════════════════════════════════════════════════
# CLI
# ═══════════════════════════════════════════════════════════════════

def main():
    import argparse
    
    parser = argparse.ArgumentParser(description="Vector RAG Engine")
    parser.add_argument("action", choices=["index", "search", "hybrid"], 
                       help="index: 인덱싱, search: 검색, hybrid: 하이브리드 검색")
    parser.add_argument("-q", "--query", help="검색 쿼리")
    parser.add_argument("-k", "--top-k", type=int, default=10, help="결과 수")
    parser.add_argument("--force", action="store_true", help="강제 재인덱싱")
    
    args = parser.parse_args()
    
    engine = VectorRAGEngine()
    
    if args.action == "index":
        engine.index_all_documents(force_reindex=args.force)
        
    elif args.action == "search":
        if not args.query:
            print("❌ --query 필요")
            return
        
        # 인덱싱 안되어 있으면 로드
        if not engine.documents:
            engine.index_all_documents()
        
        results = engine.search_by_w(args.query, top_k=args.top_k)
        engine.print_search_results(results, args.query)
        
    elif args.action == "hybrid":
        if not args.query:
            print("❌ --query 필요")
            return
        
        if not engine.documents:
            engine.index_all_documents()
        
        results = engine.hybrid_search(args.query, top_k=args.top_k)
        engine.print_search_results(results, args.query)


if __name__ == "__main__":
    main()

