#!/usr/bin/env python3
"""
🔥 Holarchy Dashboard Server
브라우저에서 실제 파일 생성을 가능하게 하는 로컬 서버
"""

from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import sys
import json
import re
import logging
from pathlib import Path

# 로깅 설정
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(filename)s:%(lineno)d - %(message)s'
)
logger = logging.getLogger("holarchy.server")

# holons 폴더의 모듈 import
sys.path.insert(0, str(Path(__file__).parent / "holons"))

app = Flask(__name__, static_folder='.')
CORS(app)  # 크로스 도메인 허용

# 경로 설정
BASE_PATH = Path(__file__).parent
HOLONS_PATH = BASE_PATH / "holons"


@app.route('/')
def index():
    """대시보드 페이지"""
    return send_from_directory('.', 'dashboard.html')


@app.route('/api/meeting', methods=['POST'])
def create_meeting():
    """회의록 파싱 & Holon 생성 API"""
    try:
        from _meeting_parser import MeetingParser
        
        data = request.json
        text = data.get('text', '')
        auto_spawn = data.get('auto_spawn', True)
        
        if not text.strip():
            return jsonify({"error": "회의록 내용이 비어있습니다"}), 400
        
        parser = MeetingParser(str(HOLONS_PATH))
        result = parser.parse_and_create(text, auto_spawn=auto_spawn)
        
        return jsonify({
            "success": True,
            "meeting_id": result["meeting_id"],
            "file": result["file"],
            "parent": result["parent"],
            "confidence": result["confidence"],
            "decisions": result["decisions"],
            "tasks": result["tasks"],
            "spawned": result["spawned"],
            "referenced_docs": result.get("referenced_docs", []),  # HTE 참조 문서
            "completion_rate": result.get("completion_rate", 0),
            "actual_status": result.get("actual_status", "unknown")
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/place', methods=['POST'])
def place_document():
    """문서 자동 배치 API - HTE 모듈 폴더에 생성"""
    try:
        from _document_placer import DocumentPlacer
        
        data = request.json
        text = data.get('text', '')
        doc_type = data.get('type', 'auto')
        parent_id = data.get('parent_id')
        module_hint = data.get('module')  # 직접 지정한 미션 에이전트 (M00~M21)
        
        if not text.strip():
            return jsonify({"error": "문서 내용이 비어있습니다"}), 400
        
        placer = DocumentPlacer()
        result = placer.create_document(
            text, 
            doc_type=doc_type, 
            parent_id=parent_id,
            module_hint=module_hint
        )
        
        return jsonify({
            "success": True,
            "holon_id": result["holon_id"],
            "module": result["module"],
            "filename": result["filename"],
            "filepath": result["filepath"],
            "confidence": result["confidence"],
            "matched_keywords": result["matched_keywords"],
            "title": result["title"],
            "decisions": result["decisions"],
            "tasks": result["tasks"]
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/place/multi', methods=['POST'])
def place_multi_documents():
    """다중 홀론 동시 배치 API - 22개 미션 에이전트에 자동 분류"""
    try:
        from _document_placer import DocumentPlacer
        
        data = request.json
        text = data.get('text', '')
        parent_id = data.get('parent_id')
        module_hint = data.get('module')  # 직접 지정한 미션 에이전트 (M00~M21)
        
        if not text.strip():
            return jsonify({"error": "내용이 비어있습니다"}), 400
        
        placer = DocumentPlacer()
        results = placer.create_multi_holons(
            text, 
            parent_id=parent_id,
            module_hint=module_hint
        )
        
        return jsonify({
            "success": True,
            "count": len(results),
            "target_module": module_hint,
            "target_path": f"2 Company/4 HTE/{placer.MODULES.get(module_hint.upper(), {}).get('full', 'auto')}/" if module_hint else "auto",
            "holons": [{
                "index": r["index"],
                "holon_id": r["holon_id"],
                "module": r["module"],
                "type": r["detected_type"],
                "filename": r["filename"],
                "filepath": r["filepath"],
                "confidence": r["confidence"],
                "title": r["title"]
            } for r in results]
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/missions', methods=['GET'])
def get_missions():
    """22개 미션 에이전트 정보 API"""
    try:
        from _document_placer import DocumentPlacer
        
        placer = DocumentPlacer()
        missions = []
        
        for module_id, info in placer.MODULES.items():
            # 모듈 폴더의 파일 수 계산
            module_path = placer.hte_path / info["full"]
            file_count = len(list(module_path.glob("HTE_*.md"))) if module_path.exists() else 0
            
            missions.append({
                "id": module_id,
                "name": info["name"],
                "full": info["full"],
                "desc": info["desc"],
                "keywords": info["keywords"],
                "file_count": file_count
            })
        
        # 홀론 타입 정보도 포함
        holon_types = [{
            "type": name,
            "code": info["code"],
            "desc": info["desc"],
            "keywords": info["keywords"]
        } for name, info in placer.HOLON_TYPES.items()]
        
        return jsonify({
            "success": True,
            "missions": missions,
            "holon_types": holon_types,
            "total_missions": len(missions)
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/analyze', methods=['POST'])
def analyze_content():
    """입력 내용 분석 API - 적절한 미션 에이전트 추천"""
    try:
        from _document_placer import DocumentPlacer
        
        data = request.json
        text = data.get('text', '')
        
        if not text.strip():
            return jsonify({"error": "내용이 비어있습니다"}), 400
        
        placer = DocumentPlacer()
        
        # 미션별 분석
        mission_analysis = placer.analyze_by_mission(text)
        
        # 홀론 타입 감지
        detected_type = placer.detect_holon_type(text)
        
        # 다중 홀론 파싱 미리보기
        specs = placer.parse_multi_holon_input(text)
        
        return jsonify({
            "success": True,
            "mission_recommendations": mission_analysis,
            "detected_type": detected_type,
            "detected_holons": [{
                "index": i+1,
                "title": s.title,
                "type": s.holon_type,
                "preview": s.content[:100] + "..." if len(s.content) > 100 else s.content
            } for i, s in enumerate(specs)],
            "holon_count": len(specs)
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/check', methods=['POST'])
def run_check():
    """Self-Healing 검증 API"""
    try:
        from _validate import HolarchyValidator
        
        validator = HolarchyValidator(str(BASE_PATH))
        validator.run_all_validations()
        
        # reports에서 결과 읽기
        import json
        issues_path = BASE_PATH / "reports" / "issues.json"
        risk_path = BASE_PATH / "reports" / "risk_score.json"
        
        issues = {}
        risk = {}
        
        if issues_path.exists():
            issues = json.loads(issues_path.read_text(encoding="utf-8"))
        if risk_path.exists():
            risk = json.loads(risk_path.read_text(encoding="utf-8"))
        
        return jsonify({
            "success": True,
            "issues": issues,
            "risk": risk
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/link', methods=['POST'])
def run_link():
    """링크 동기화 API"""
    try:
        from _auto_link import AutoLinker
        
        linker = AutoLinker(str(HOLONS_PATH))
        linker.run()
        
        return jsonify({"success": True, "message": "링크 동기화 완료"})
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/documents', methods=['GET'])
def get_documents():
    """모든 문서 목록 API"""
    try:
        import json
        import re
        
        documents = {
            "holons": [],
            "meetings": [],
            "decisions": [],
            "tasks": []
        }
        
        # Holons 폴더
        for md_file in HOLONS_PATH.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    documents["holons"].append({
                        "id": holon.get("holon_id"),
                        "title": holon.get("meta", {}).get("title", md_file.stem),
                        "type": holon.get("type"),
                        "status": holon.get("meta", {}).get("status"),
                        "file": md_file.name
                    })
                except json.JSONDecodeError as e:
                    logger.debug(f"Holon JSON 파싱 실패 [{md_file.name}]: {e}")

        # Meetings 폴더
        meetings_path = BASE_PATH / "meetings"
        if meetings_path.exists():
            for md_file in meetings_path.glob("*.md"):
                content = md_file.read_text(encoding="utf-8")
                json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
                if json_match:
                    try:
                        holon = json.loads(json_match.group(1))
                        documents["meetings"].append({
                            "id": holon.get("holon_id"),
                            "title": holon.get("meta", {}).get("title", md_file.stem),
                            "date": holon.get("meta", {}).get("created_at"),
                            "status": holon.get("meta", {}).get("status"),
                            "file": md_file.name
                        })
                    except json.JSONDecodeError as e:
                        logger.debug(f"Meeting JSON 파싱 실패 [{md_file.name}]: {e}")

        # Decisions 폴더
        decisions_path = BASE_PATH / "decisions"
        if decisions_path.exists():
            for md_file in decisions_path.glob("*.md"):
                content = md_file.read_text(encoding="utf-8")
                json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
                if json_match:
                    try:
                        holon = json.loads(json_match.group(1))
                        documents["decisions"].append({
                            "id": holon.get("holon_id"),
                            "title": holon.get("meta", {}).get("title", md_file.stem),
                            "status": holon.get("meta", {}).get("status"),
                            "parent": holon.get("links", {}).get("parent"),
                            "file": md_file.name
                        })
                    except json.JSONDecodeError as e:
                        logger.debug(f"Decision JSON 파싱 실패 [{md_file.name}]: {e}")

        # Tasks 폴더
        tasks_path = BASE_PATH / "tasks"
        if tasks_path.exists():
            for md_file in tasks_path.glob("*.md"):
                content = md_file.read_text(encoding="utf-8")
                json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
                if json_match:
                    try:
                        holon = json.loads(json_match.group(1))
                        documents["tasks"].append({
                            "id": holon.get("holon_id"),
                            "title": holon.get("meta", {}).get("title", md_file.stem),
                            "status": holon.get("meta", {}).get("status"),
                            "parent": holon.get("links", {}).get("parent"),
                            "file": md_file.name
                        })
                    except json.JSONDecodeError as e:
                        logger.debug(f"Task JSON 파싱 실패 [{md_file.name}]: {e}")

        return jsonify({
            "success": True,
            "documents": documents,
            "counts": {
                "holons": len(documents["holons"]),
                "meetings": len(documents["meetings"]),
                "decisions": len(documents["decisions"]),
                "tasks": len(documents["tasks"]),
                "total": sum(len(v) for v in documents.values())
            }
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


# ============================================================
# 🧠 Brain Engine API - 가중치 기반 문서 검색/생성
# ============================================================

# 전역 가중치 상태 (세션 동안 유지)
_brain_weights = {
    "recency": 0.25,
    "popularity": 0.25, 
    "relevance": 0.25,
    "importance": 0.25,
    "top_k": 7
}

_brain_presets = {
    "fast_fresh": {"recency": 0.5, "popularity": 0.1, "relevance": 0.3, "importance": 0.1},
    "wisdom": {"recency": 0.1, "popularity": 0.1, "relevance": 0.3, "importance": 0.5},
    "balanced": {"recency": 0.25, "popularity": 0.25, "relevance": 0.25, "importance": 0.25},
    "pattern": {"recency": 0.0, "popularity": 0.1, "relevance": 0.4, "importance": 0.5},
    "trend": {"recency": 0.4, "popularity": 0.4, "relevance": 0.2, "importance": 0.0},
}


@app.route('/api/brain/weights', methods=['GET', 'POST'])
def brain_weights():
    """가중치 조회/설정 API"""
    global _brain_weights
    
    if request.method == 'GET':
        return jsonify({
            "success": True,
            "weights": _brain_weights,
            "presets": list(_brain_presets.keys())
        })
    
    # POST: 가중치 설정
    try:
        data = request.json
        
        # 프리셋 적용
        if 'preset' in data:
            preset_name = data['preset']
            if preset_name in _brain_presets:
                preset = _brain_presets[preset_name]
                _brain_weights.update(preset)
                return jsonify({
                    "success": True,
                    "message": f"프리셋 '{preset_name}' 적용",
                    "weights": _brain_weights
                })
            else:
                return jsonify({"error": f"알 수 없는 프리셋: {preset_name}"}), 400
        
        # 개별 가중치 설정
        if 'recency' in data:
            _brain_weights['recency'] = float(data['recency'])
        if 'popularity' in data:
            _brain_weights['popularity'] = float(data['popularity'])
        if 'relevance' in data:
            _brain_weights['relevance'] = float(data['relevance'])
        if 'importance' in data:
            _brain_weights['importance'] = float(data['importance'])
        if 'top_k' in data:
            _brain_weights['top_k'] = int(data['top_k'])
        
        # 정규화 (합이 1.0이 되도록)
        if data.get('normalize', True):
            total = (_brain_weights['recency'] + _brain_weights['popularity'] + 
                     _brain_weights['relevance'] + _brain_weights['importance'])
            if total > 0:
                _brain_weights['recency'] /= total
                _brain_weights['popularity'] /= total
                _brain_weights['relevance'] /= total
                _brain_weights['importance'] /= total
        
        return jsonify({
            "success": True,
            "weights": _brain_weights
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/brain/search', methods=['POST'])
def brain_search():
    """가중치 기반 Top-K 문서 검색 API"""
    try:
        from _brain_engine import BrainEngine, MemoryWeights
        
        data = request.json
        query = data.get('query', '')
        top_k = data.get('top_k', _brain_weights.get('top_k', 7))
        
        # 가중치 적용
        weights = MemoryWeights(
            recency=_brain_weights['recency'],
            popularity=_brain_weights['popularity'],
            relevance=_brain_weights['relevance'],
            importance=_brain_weights['importance']
        )
        
        engine = BrainEngine(str(HOLONS_PATH))
        engine.weights = weights
        
        # 검색 실행 (limit 파라미터 사용)
        results = engine.search(query, limit=top_k)
        
        # 결과 포맷팅 (results는 List[Tuple[DocumentScore, float]])
        formatted = []
        for doc, final_score in results:
            formatted.append({
                "holon_id": doc.holon_id,
                "filename": doc.filename,
                "title": doc.filename.replace(".md", "").replace("-", " "),
                "type": doc.doc_type,
                "layer": doc.layer,
                "scores": {
                    "recency": round(doc.recency_score, 3),
                    "popularity": round(doc.popularity_score, 3),
                    "relevance": round(doc.relevance_score, 3),
                    "importance": round(doc.importance_score, 3),
                    "final": round(final_score, 3)
                },
                "age_days": doc.age_days,
                "access_count": doc.access_count
            })
        
        return jsonify({
            "success": True,
            "query": query,
            "top_k": top_k,
            "weights": {
                "recency": weights.recency,
                "popularity": weights.popularity,
                "relevance": weights.relevance,
                "importance": weights.importance
            },
            "results": formatted,
            "total_docs": len(results)
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/brain/generate', methods=['POST'])
def brain_generate():
    """가중치 기반 참조 문서로 새 Holon 생성 API"""
    try:
        from _brain_engine import BrainEngine, MemoryWeights
        from _meeting_parser import MeetingParser
        from _document_placer import DocumentPlacer
        
        data = request.json
        text = data.get('text', '')
        doc_type = data.get('type', 'auto')
        top_k = data.get('top_k', _brain_weights.get('top_k', 7))
        
        if not text.strip():
            return jsonify({"error": "내용이 비어있습니다"}), 400
        
        # 가중치 설정
        weights = MemoryWeights(
            recency=_brain_weights['recency'],
            popularity=_brain_weights['popularity'],
            relevance=_brain_weights['relevance'],
            importance=_brain_weights['importance']
        )
        
        # Brain Engine으로 참조 문서 검색
        engine = BrainEngine(str(HOLONS_PATH))
        engine.weights = weights
        
        # 텍스트에서 키워드 추출하여 검색
        keywords = ' '.join(text.split()[:50])  # 처음 50단어
        ref_docs = engine.search(keywords, limit=top_k)
        
        # 문서 생성 (타입에 따라)
        if doc_type == 'meeting' or '회의' in text or '참석자' in text:
            parser = MeetingParser(str(HOLONS_PATH))
            result = parser.parse_and_create(text, auto_spawn=True)
            create_result = {
                "holon_id": result["meeting_id"],
                "file": result["file"],
                "type": "meeting",
                "decisions": result["decisions"],
                "tasks": result["tasks"]
            }
        else:
            placer = DocumentPlacer()
            result = placer.create_document(text, doc_type=doc_type)
            create_result = {
                "holon_id": result["holon_id"],
                "file": result["filepath"],
                "type": doc_type,
                "module": result["module"]
            }
        
        # 참조 문서 정보 (results는 List[Tuple[DocumentScore, float]])
        ref_info = [{
            "holon_id": doc.holon_id,
            "filename": doc.filename,
            "final_score": round(score, 3)
        } for doc, score in ref_docs]
        
        return jsonify({
            "success": True,
            "created": create_result,
            "weights_used": {
                "recency": weights.recency,
                "popularity": weights.popularity,
                "relevance": weights.relevance,
                "importance": weights.importance
            },
            "referenced_docs": ref_info,
            "top_k": top_k
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/document/read', methods=['POST'])
def read_document():
    """문서 내용 읽기 API"""
    try:
        data = request.json
        filepath = data.get('filepath', '')
        
        if not filepath:
            return jsonify({"error": "파일 경로가 필요합니다"}), 400
        
        # 보안: BASE_PATH 내의 파일만 허용
        from pathlib import Path
        file_path = Path(filepath)
        
        # 상대 경로인 경우 BASE_PATH 기준으로 변환
        if not file_path.is_absolute():
            file_path = BASE_PATH / filepath
        
        # BASE_PATH 외부 접근 방지
        try:
            file_path.resolve().relative_to(BASE_PATH.resolve())
        except ValueError:
            return jsonify({"error": "접근 권한 없음"}), 403
        
        if not file_path.exists():
            return jsonify({"error": "파일을 찾을 수 없습니다"}), 404
        
        content = file_path.read_text(encoding="utf-8")
        
        return jsonify({
            "success": True,
            "filepath": str(file_path),
            "filename": file_path.name,
            "content": content
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


# ============================================================
# 🏷️ Auto-Tagger API
# ============================================================

@app.route('/api/tag', methods=['POST'])
def tag_documents():
    """자동 태깅 API"""
    try:
        from _auto_tagger import AutoTagger
        
        data = request.json or {}
        action = data.get('action', 'all')
        filepath = data.get('filepath')
        dry_run = data.get('dry_run', False)
        
        tagger = AutoTagger(str(HOLONS_PATH))
        
        if action == 'file' and filepath:
            # 단일 파일 태깅
            from pathlib import Path
            path = Path(filepath)
            if not path.is_absolute():
                path = BASE_PATH / filepath
            
            tags = tagger.tag_document(path, dry_run=dry_run)
            if tags:
                return jsonify({
                    "success": True,
                    "filepath": str(path),
                    "tags": tags.to_dict(),
                    "confidence": tags.confidence
                })
            else:
                return jsonify({"error": "태깅 실패"}), 400
        
        elif action == 'all':
            # 전체 문서 태깅
            results = tagger.tag_all_documents(dry_run=dry_run)
            
            summary = {}
            for filepath, tags in results.items():
                summary[filepath] = {
                    "module": tags.module,
                    "topic": tags.topic[:3],
                    "role": tags.role
                }
            
            return jsonify({
                "success": True,
                "count": len(results),
                "dry_run": dry_run,
                "summary": summary
            })
        
        else:
            return jsonify({"error": "잘못된 action"}), 400
            
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/tag/preview', methods=['POST'])
def preview_tags():
    """태그 미리보기 API (저장 안함)"""
    try:
        from _auto_tagger import AutoTagger
        
        data = request.json or {}
        text = data.get('text', '')
        
        if not text.strip():
            return jsonify({"error": "내용이 비어있습니다"}), 400
        
        tagger = AutoTagger(str(HOLONS_PATH))
        tags = tagger.generate_tags(text)
        
        return jsonify({
            "success": True,
            "tags": tags.to_dict(),
            "confidence": tags.confidence
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/rag/index', methods=['POST'])
def rag_index():
    """Vector RAG 인덱싱 API"""
    try:
        from _vector_rag import VectorRAGEngine
        
        engine = VectorRAGEngine(str(HOLONS_PATH))
        engine.index_all_documents(force_reindex=True)
        
        return jsonify({
            "success": True,
            "indexed_count": len(engine.documents),
            "message": "W.will.drive 벡터 인덱싱 완료"
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/review', methods=['POST'])
def review_document():
    """자연어 리뷰 기반 점수 자동 보정 API"""
    try:
        from _brain_engine import BrainEngine
        
        data = request.json or {}
        holon_id = data.get('holon_id', '')
        review_text = data.get('review', '')
        
        if not holon_id or not review_text:
            return jsonify({"error": "holon_id와 review가 필요합니다"}), 400
        
        engine = BrainEngine(str(HOLONS_PATH))
        result = engine.review_document(holon_id, review_text)
        
        return jsonify({
            "success": True,
            "holon_id": holon_id,
            "review": review_text,
            "label": result["label"],
            "before": result["before"],
            "after": result["after"],
            "message": f"리뷰 처리 완료 ({result['label']})"
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/rag/search', methods=['POST'])
def rag_search():
    """W 기반 의미적 검색 API"""
    try:
        from _vector_rag import VectorRAGEngine
        
        data = request.json or {}
        query = data.get('query', '')
        top_k = data.get('top_k', 10)
        mode = data.get('mode', 'hybrid')  # search 또는 hybrid
        
        if not query.strip():
            return jsonify({"error": "검색어가 비어있습니다"}), 400
        
        engine = VectorRAGEngine(str(HOLONS_PATH))
        
        # 인덱싱
        if not engine.documents:
            engine.index_all_documents()
        
        # 검색
        if mode == 'hybrid':
            results = engine.hybrid_search(query, top_k=top_k)
        else:
            results = engine.search_by_w(query, top_k=top_k)
        
        # 결과 변환
        result_list = []
        for doc in results:
            result_list.append({
                "holon_id": doc.holon_id,
                "title": doc.title,
                "filepath": doc.filepath,
                "similarity": round(doc.similarity_score, 4),
                "w_drive": doc.w_drive[:200] if doc.w_drive else "",
                "parent": doc.parent,
                "children": doc.children[:5],
                "related": doc.related[:3],
                "tags": doc.tags
            })
        
        return jsonify({
            "success": True,
            "query": query,
            "mode": mode,
            "results": result_list,
            "count": len(result_list)
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/issues/scan', methods=['POST'])
def issues_scan():
    """이슈 스캔 API"""
    try:
        from _issue_tracker import IssueTracker
        
        tracker = IssueTracker(str(HOLONS_PATH))
        new_issues = tracker.scan_all()
        
        return jsonify({
            "success": True,
            "new_count": len(new_issues),
            "total_count": len(tracker.issues),
            "open_count": len([i for i in tracker.issues if i.status == "open"])
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/issues', methods=['GET'])
def issues_list():
    """이슈 목록 API"""
    try:
        from _issue_tracker import IssueTracker
        
        category = request.args.get('category')
        status = request.args.get('status', 'open')
        severity = request.args.get('severity')
        
        tracker = IssueTracker(str(HOLONS_PATH))
        issues = tracker.list_issues(category=category, status=status, severity=severity)
        
        return jsonify({
            "success": True,
            "issues": [i.to_dict() for i in issues],
            "count": len(issues)
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/issues/update', methods=['POST'])
def issues_update():
    """이슈 상태 업데이트 API"""
    try:
        from _issue_tracker import IssueTracker
        
        data = request.json or {}
        issue_id = data.get('id')
        status = data.get('status')
        note = data.get('note', '')
        
        if not issue_id:
            return jsonify({"error": "이슈 ID 필요"}), 400
        
        tracker = IssueTracker(str(HOLONS_PATH))
        
        if tracker.update_issue(issue_id, status=status, review_note=note):
            return jsonify({
                "success": True,
                "message": f"이슈 {issue_id} 업데이트 완료"
            })
        else:
            return jsonify({"error": f"이슈 없음: {issue_id}"}), 404
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/issues/summary', methods=['GET'])
def issues_summary():
    """이슈 요약 API"""
    try:
        from _issue_tracker import IssueTracker
        
        tracker = IssueTracker(str(HOLONS_PATH))
        
        status_counts = {}
        severity_counts = {}
        category_counts = {}
        
        for issue in tracker.issues:
            status_counts[issue.status] = status_counts.get(issue.status, 0) + 1
            severity_counts[issue.severity] = severity_counts.get(issue.severity, 0) + 1
            category_counts[issue.category] = category_counts.get(issue.category, 0) + 1
        
        return jsonify({
            "success": True,
            "total": len(tracker.issues),
            "by_status": status_counts,
            "by_severity": severity_counts,
            "by_category": category_counts
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


@app.route('/api/stats', methods=['GET'])
def get_stats():
    """시스템 통계 API"""
    try:
        import json
        
        # Risk score 읽기
        risk_path = BASE_PATH / "reports" / "risk_score.json"
        risk = {"overall_score": 0}
        if risk_path.exists():
            risk = json.loads(risk_path.read_text(encoding="utf-8"))
        
        # 문서 수 계산
        holons_count = len(list(HOLONS_PATH.glob("*.md"))) - len(list(HOLONS_PATH.glob("_*.md")))
        meetings_count = len(list((BASE_PATH / "meetings").glob("*.md"))) if (BASE_PATH / "meetings").exists() else 0
        decisions_count = len(list((BASE_PATH / "decisions").glob("*.md"))) if (BASE_PATH / "decisions").exists() else 0
        tasks_count = len(list((BASE_PATH / "tasks").glob("*.md"))) if (BASE_PATH / "tasks").exists() else 0
        
        return jsonify({
            "success": True,
            "stats": {
                "holons": holons_count,
                "meetings": meetings_count,
                "decisions": decisions_count,
                "tasks": tasks_count,
                "total": holons_count + meetings_count + decisions_count + tasks_count,
                "health": risk.get("overall_score", 0)
            }
        })
    except Exception as e:
        return jsonify({"error": str(e)}), 500


if __name__ == '__main__':
    print("=" * 60)
    print("🔥 Holarchy Dashboard Server")
    print("=" * 60)
    print()
    print("📌 대시보드 URL: http://localhost:5000")
    print()
    print("💡 종료하려면 Ctrl+C")
    print("=" * 60)
    
    app.run(host='0.0.0.0', port=5000, debug=True)

