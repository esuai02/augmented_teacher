#!/usr/bin/env python3
"""
🔥 Holarchy 문서 자동 배치 시스템 v2.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

기능:
- 입력 텍스트를 분석하여 적절한 HTE 모듈(M00~M21) 결정
- 문서 타입별 자동 분류: Project(P), Task(T), Drilling(D), App(A)
- 다중 홀론 동시 생성 지원
- 파일명: HTE_MXX_PYY_TZZ_VWW_AAA.md

폴더 구조:
  2 Company/4 HTE/
  ├── M00_Astral/
  ├── M01_TimeCrystal/
  ├── ...
  └── M21_SoftwareBackbone/
"""

import json
import re
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Tuple, Optional
from dataclasses import dataclass, asdict


@dataclass
class HolonSpec:
    """생성할 홀론 명세"""
    content: str
    holon_type: str  # project, task, drilling, app
    module_hint: str = None  # M00~M21 직접 지정 시
    parent_id: str = None
    title: str = None
    priority: str = "medium"


class DocumentPlacer:
    """문서 자동 배치 및 생성 (v2.0 - 다중 홀론 지원)"""
    
    # 홀론 타입 정의 (P=Project, T=Task/Topic, D=Drilling, A=App/Agent)
    HOLON_TYPES = {
        "project": {"code": "P", "desc": "프로젝트 - 목표 달성을 위한 계획", 
                   "keywords": ["프로젝트", "계획", "기획", "로드맵", "전략", "방안"]},
        "task": {"code": "T", "desc": "태스크 - 구체적 실행 단위",
                "keywords": ["할일", "작업", "실행", "구현", "개발", "테스트"]},
        "drilling": {"code": "D", "desc": "드릴링 - 깊이 있는 탐구/연구",
                    "keywords": ["연구", "분석", "탐구", "심층", "조사", "리서치"]},
        "app": {"code": "A", "desc": "앱/에이전트 - 자동화된 기능 단위",
               "keywords": ["앱", "에이전트", "봇", "자동화", "도구", "시스템"]}
    }
    
    # 모듈 정의
    MODULES = {
        "M00": {"name": "Astral", "full": "M00_Astral", 
                "keywords": ["전략", "세계관", "비전", "미션", "철학", "목표", "방향", "핵심"],
                "desc": "심연의 뿌리, 존재의 원천"},
        "M01": {"name": "TimeCrystal", "full": "M01_TimeCrystal",
                "keywords": ["시간", "경영", "의사결정", "리더", "일정", "계획", "스케줄"],
                "desc": "미래에서 온 기억, 철학의 씨앗"},
        "M02": {"name": "TimelineGenesis", "full": "M02_TimelineGenesis",
                "keywords": ["운영", "프로세스", "리듬", "회의", "미팅", "절차", "워크플로우"],
                "desc": "철학을 현실에 심는 장치"},
        "M03": {"name": "BusinessModel", "full": "M03_BusinessModel",
                "keywords": ["비즈니스", "모델", "수익", "매출", "가격", "고객", "시장"],
                "desc": "의미를 거래 가능하게 만드는 변환"},
        "M04": {"name": "KPI_OKR", "full": "M04_KPI_OKR",
                "keywords": ["kpi", "okr", "목표", "성과", "지표", "측정", "달성"],
                "desc": "숫자가 기도문이 되는 곳"},
        "M05": {"name": "FinancialGrowth", "full": "M05_FinancialGrowth",
                "keywords": ["재무", "투자", "예산", "비용", "자금", "금융", "성장"],
                "desc": "순환의 속도, 에너지의 흐름"},
        "M06": {"name": "SWOT", "full": "M06_SWOT",
                "keywords": ["swot", "강점", "약점", "기회", "위협", "분석", "경쟁"],
                "desc": "의식의 방향성, 패턴의 분류"},
        "M07": {"name": "BizWeighing", "full": "M07_BizWeighing",
                "keywords": ["전략", "비중", "우선순위", "리소스", "배분", "집중"],
                "desc": "멈추지 않는 전쟁, 리듬의 승리"},
        "M08": {"name": "InternalBranding", "full": "M08_InternalBranding",
                "keywords": ["내부", "브랜딩", "문화", "가치", "직원", "팀"],
                "desc": "자기도 모르게 흘리는 패턴"},
        "M09": {"name": "VerticalDrilling", "full": "M09_VerticalDrilling",
                "keywords": ["핵심", "기술", "개발", "연구", "깊이", "전문"],
                "desc": "핵심 전략/모델/기술개발 기획의 수직 굴착"},
        "M10": {"name": "AgentGarden", "full": "M10_AgentGarden",
                "keywords": ["agent", "에이전트", "holon", "홀론", "자동화", "봇"],
                "desc": "분산된 무지성 실행의 축제"},
        "M11": {"name": "ServicePipeline", "full": "M11_ServicePipeline",
                "keywords": ["서비스", "파이프라인", "배포", "전달", "운영"],
                "desc": "전달의 마법, 관통의 기술"},
        "M12": {"name": "ExternalBranding", "full": "M12_ExternalBranding",
                "keywords": ["외부", "마케팅", "홍보", "광고", "브랜드", "인지도"],
                "desc": "존재의 잔향을 퍼뜨리는 구조"},
        "M13": {"name": "TrackGrowthEngine", "full": "M13_TrackGrowthEngine",
                "keywords": ["성장", "엔진", "확장", "스케일", "그로스"],
                "desc": "부르는 힘, 흐르게 하는 루프"},
        "M14": {"name": "CompanyOnboarding", "full": "M14_CompanyOnboarding",
                "keywords": ["온보딩", "교육", "신입", "입사", "트레이닝"],
                "desc": "리듬이 빠르면 이미 이긴 상태"},
        "M15": {"name": "TimeCrystalCEO", "full": "M15_TimeCrystalCEO",
                "keywords": ["ceo", "리더십", "경영진", "대표", "총괄"],
                "desc": "흐름이 되는 리더십"},
        "M16": {"name": "AIMindArchitecture", "full": "M16_AIMindArchitecture",
                "keywords": ["ai", "인공지능", "머신러닝", "딥러닝", "llm", "gpt", "튜터", "학습"],
                "desc": "도구가 자랄 공간을 여는 것"},
        "M17": {"name": "NervousSystem", "full": "M17_NervousSystem",
                "keywords": ["신경", "데이터", "파이프라인", "연결", "통합", "api"],
                "desc": "연결의 신전, 패턴의 네트워크"},
        "M18": {"name": "InformationBlocks", "full": "M18_InformationBlocks",
                "keywords": ["정보", "블록", "지능", "구조", "프레임워크"],
                "desc": "무의식 정보 축적의 심연"},
        "M19": {"name": "CompanyCulture", "full": "M19_CompanyCulture",
                "keywords": ["문화", "가치관", "철학", "조직문화", "무지성"],
                "desc": "무의식적 지식축적 시스템"},
        "M20": {"name": "KnowledgeCrystal", "full": "M20_KnowledgeCrystal",
                "keywords": ["지식", "축적", "결정체", "학습", "체계"],
                "desc": "루프의 진동으로 응결된 결정"},
        "M21": {"name": "SoftwareBackbone", "full": "M21_SoftwareBackbone",
                "keywords": ["소프트웨어", "백본", "인프라", "시스템", "서버", "클라우드"],
                "desc": "모두가 중심인 무형의 뼈대"},
    }
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent.parent.parent  # Holarchy 루트
        
        self.hte_path = self.base_path / "2 Company" / "4 HTE"
    
    def detect_holon_type(self, text: str) -> str:
        """텍스트에서 홀론 타입 자동 감지"""
        text_lower = text.lower()
        scores = {}
        
        for type_name, type_info in self.HOLON_TYPES.items():
            score = sum(1 for kw in type_info["keywords"] if kw.lower() in text_lower)
            scores[type_name] = score
        
        if max(scores.values()) == 0:
            return "task"  # 기본값
        
        return max(scores, key=scores.get)
    
    def parse_multi_holon_input(self, text: str) -> List[HolonSpec]:
        """
        다중 홀론 입력 파싱
        
        지원 포맷:
        1. 번호 목록: "1. 첫번째\n2. 두번째"
        2. 구분자: "---" 또는 "==="
        3. 헤더 기반: "## 항목1\n내용\n## 항목2"
        """
        specs = []
        
        # --- 구분자로 분리
        if '\n---\n' in text or '\n===\n' in text:
            parts = re.split(r'\n[-=]{3,}\n', text)
            for part in parts:
                if part.strip():
                    specs.append(HolonSpec(
                        content=part.strip(),
                        holon_type=self.detect_holon_type(part),
                        title=self._extract_title(part)
                    ))
            return specs
        
        # ## 헤더로 분리
        header_splits = re.split(r'\n##\s+', text)
        if len(header_splits) > 1:
            for i, part in enumerate(header_splits):
                if i == 0 and not part.strip():
                    continue
                if part.strip():
                    # 첫 줄을 제목으로
                    lines = part.strip().split('\n')
                    title = lines[0].strip()
                    content = '\n'.join(lines) if len(lines) > 1 else title
                    specs.append(HolonSpec(
                        content=content,
                        holon_type=self.detect_holon_type(content),
                        title=title
                    ))
            return specs
        
        # 번호 목록으로 분리
        numbered = re.findall(r'(?:^|\n)(\d+)[.)\s]+(.+?)(?=\n\d+[.)\s]|\n\n|\Z)', text, re.DOTALL)
        if numbered and len(numbered) > 1:
            for num, content in numbered:
                if content.strip():
                    specs.append(HolonSpec(
                        content=content.strip(),
                        holon_type=self.detect_holon_type(content),
                        title=self._extract_title(content)
                    ))
            return specs
        
        # 단일 홀론
        specs.append(HolonSpec(
            content=text.strip(),
            holon_type=self.detect_holon_type(text),
            title=self._extract_title(text)
        ))
        
        return specs
    
    def _extract_title(self, text: str) -> str:
        """텍스트에서 제목 추출"""
        lines = text.strip().split('\n')
        for line in lines[:5]:
            line = line.strip()
            if line.startswith('#'):
                return line.lstrip('#').strip()
            if len(line) > 5 and len(line) < 100:
                return line
        return lines[0][:50] if lines else "문서"
    
    def create_multi_holons(self, text: str, parent_id: str = None, 
                            module_hint: str = None) -> List[Dict]:
        """
        다중 홀론 동시 생성 (SSOT 준수)
        
        Args:
            text: 다중 홀론 입력 텍스트
            parent_id: 공통 부모 ID (선택)
            module_hint: 모든 홀론에 적용할 모듈 (M00~M21)
        
        Returns:
            생성된 홀론 정보 리스트
        """
        print("=" * 60)
        print("🔥 Holarchy 다중 홀론 자동 배치 시스템")
        print("=" * 60)
        print()
        
        # 1. 입력 파싱
        specs = self.parse_multi_holon_input(text)
        print(f"📝 감지된 홀론 수: {len(specs)}")
        
        # 모듈 힌트가 있으면 표시
        if module_hint:
            print(f"📁 지정된 미션 에이전트: {module_hint}")
        print()
        
        results = []
        
        for i, spec in enumerate(specs, 1):
            print(f"─── [{i}/{len(specs)}] {spec.title or '무제'} ───")
            
            # 모듈 결정: 전역 힌트 > 개별 힌트 > 자동 분석
            effective_module = module_hint or spec.module_hint
            
            if effective_module and effective_module.upper() in self.MODULES:
                module_id = effective_module.upper()
                confidence = 1.0
            else:
                module_id, confidence, analysis = self.analyze_content(spec.content)
            
            print(f"   모듈: {self.MODULES[module_id]['full']}")
            print(f"   타입: {spec.holon_type} ({self.HOLON_TYPES[spec.holon_type]['desc']})")
            print(f"   신뢰도: {confidence:.0%}")
            
            # 3. 문서 생성 (module_hint 전달)
            result = self.create_document(
                spec.content, 
                doc_type=spec.holon_type,
                parent_id=parent_id or spec.parent_id,
                module_hint=effective_module
            )
            
            results.append({
                **result,
                "index": i,
                "detected_type": spec.holon_type
            })
            print()
        
        print("=" * 60)
        print(f"✅ 총 {len(results)}개 홀론 생성 완료")
        print("=" * 60)
        
        return results
    
    def analyze_by_mission(self, text: str) -> Dict[str, List[Dict]]:
        """
        22개 미션 에이전트별 분석 결과 반환
        
        Returns:
            {module_id: [{"score": float, "keywords": [...], "relevance": str}]}
        """
        text_lower = text.lower()
        analysis = {}
        
        for module_id, info in self.MODULES.items():
            matched = []
            for keyword in info["keywords"]:
                if keyword.lower() in text_lower:
                    matched.append(keyword)
            
            if matched:
                analysis[module_id] = {
                    "name": info["name"],
                    "full": info["full"],
                    "desc": info["desc"],
                    "score": len(matched),
                    "keywords": matched,
                    "confidence": min(len(matched) / 5, 1.0)
                }
        
        # 점수순 정렬
        return dict(sorted(analysis.items(), key=lambda x: x[1]["score"], reverse=True))
    
    def analyze_content(self, text: str) -> Tuple[str, float, Dict]:
        """텍스트 분석하여 적절한 모듈 결정"""
        text_lower = text.lower()
        scores = {}
        
        for module_id, info in self.MODULES.items():
            score = 0
            matched_keywords = []
            
            for keyword in info["keywords"]:
                if keyword.lower() in text_lower:
                    score += 1
                    matched_keywords.append(keyword)
            
            if score > 0:
                scores[module_id] = {
                    "score": score,
                    "keywords": matched_keywords,
                    "info": info
                }
        
        if not scores:
            # 기본값: M02 (회의/프로세스)
            return "M02", 0.3, {"matched": [], "reason": "기본값 (키워드 매칭 없음)"}
        
        # 최고 점수 모듈 선택
        best_module = max(scores, key=lambda x: scores[x]["score"])
        best_info = scores[best_module]
        confidence = min(best_info["score"] / 5, 1.0)  # 5개 키워드 매칭시 100%
        
        return best_module, confidence, {
            "matched": best_info["keywords"],
            "module_desc": best_info["info"]["desc"],
            "all_scores": {k: v["score"] for k, v in scores.items()}
        }
    
    def get_next_file_number(self, module_id: str) -> Tuple[int, int]:
        """해당 모듈의 다음 파일 번호 (P, T) 결정"""
        module_info = self.MODULES.get(module_id)
        if not module_info:
            return 1, 1
        
        module_path = self.hte_path / module_info["full"]
        if not module_path.exists():
            module_path.mkdir(parents=True, exist_ok=True)
            return 1, 1
        
        # 기존 파일들에서 최대 P 번호 찾기
        pattern = f"HTE_{module_id}_P*_T*_V*_A*.md"
        existing = list(module_path.glob(pattern))
        
        if not existing:
            return 1, 1
        
        max_p = 0
        max_t_for_max_p = 0
        
        for f in existing:
            match = re.search(r'P(\d+)_T(\d+)', f.name)
            if match:
                p = int(match.group(1))
                t = int(match.group(2))
                if p > max_p:
                    max_p = p
                    max_t_for_max_p = t
                elif p == max_p and t > max_t_for_max_p:
                    max_t_for_max_p = t
        
        # 같은 P에서 T 증가, 또는 새 P
        return max_p, max_t_for_max_p + 1
    
    def generate_filename(self, module_id: str, p_num: int = None, t_num: int = None, 
                          parent_id: str = None) -> str:
        """
        파일명 생성: HTE_MXX_PYY_TZZ_V00_A00_[PARENT].md
        
        하이브리드 방식:
        - 파일명에 부모 ID 포함 → 폴더 없이도 상하관계 파악 가능
        - parent_id 없으면 [ROOT] 사용
        """
        if p_num is None or t_num is None:
            p_num, t_num = self.get_next_file_number(module_id)
        
        # 부모 ID 포맷팅
        if parent_id:
            # 긴 ID를 짧게 변환: hte-m16-p01t01 → M16-P01T01
            parent_short = self._shorten_parent_id(parent_id)
        else:
            parent_short = "ROOT"
        
        return f"HTE_{module_id}_P{p_num:02d}_T{t_num:02d}_V00_A00_[{parent_short}].md"
    
    def _shorten_parent_id(self, parent_id: str) -> str:
        """
        부모 ID를 짧은 형식으로 변환
        
        예시:
        - hte-m16-p01t01 → M16-P01T01
        - hte-doc-000 → DOC-000
        - M16_P01_T01 → M16-P01T01
        """
        if not parent_id:
            return "ROOT"
        
        # hte- 접두어 제거
        short = parent_id.upper().replace("HTE-", "").replace("HTE_", "")
        
        # 언더스코어를 하이픈으로 통일
        short = short.replace("_", "-")
        
        # 너무 길면 잘라내기
        if len(short) > 15:
            short = short[:15]
        
        return short
    
    def extract_info(self, text: str) -> Dict:
        """텍스트에서 제목, 날짜 등 추출"""
        lines = text.strip().split("\n")
        
        # 제목 추출
        title = ""
        for line in lines[:5]:
            line = line.strip()
            if line.startswith("#"):
                title = line.lstrip("#").strip()
                break
            elif "제목" in line or "회의명" in line:
                title = re.sub(r"^(제목|회의명)[:\s]*", "", line).strip()
                break
        
        if not title:
            title = lines[0][:50] if lines else "문서"
        
        # 날짜 추출
        date = datetime.now().strftime("%Y-%m-%d")
        date_match = re.search(r"(\d{4}[-./]\d{2}[-./]\d{2})", text)
        if date_match:
            date = date_match.group(1).replace(".", "-").replace("/", "-")
        
        # 참석자 추출
        participants = []
        part_match = re.search(r"참석자?[:\s]*(.+?)(?:\n|$)", text)
        if part_match:
            participants = [p.strip() for p in re.split(r"[,，、\s]+", part_match.group(1)) if p.strip()]
        
        # 결정/할일 추출
        decisions = re.findall(r"결정[:\s]*(.+?)(?:\n|$)", text, re.IGNORECASE)
        tasks = re.findall(r"(?:TODO|할일)[:\s]*(.+?)(?:\n|$)", text, re.IGNORECASE)
        
        return {
            "title": title,
            "date": date,
            "participants": participants or ["미지정"],
            "decisions": [d.strip() for d in decisions],
            "tasks": [t.strip() for t in tasks]
        }
    
    def create_document(self, text: str, doc_type: str = "auto", parent_id: str = None, 
                        module_hint: str = None) -> Dict:
        """
        문서 생성
        
        Args:
            text: 문서 내용
            doc_type: 문서 타입 (auto/project/task/drilling/app)
            parent_id: 부모 문서 ID
            module_hint: 직접 지정한 모듈 (M00~M21)
        """
        print("=" * 60)
        print("🔥 Holarchy 문서 자동 배치 시스템")
        print("=" * 60)
        print()
        
        # 1. 내용 분석
        print("📝 내용 분석 중...")
        
        # 모듈 힌트가 있으면 우선 사용
        if module_hint and module_hint.upper() in self.MODULES:
            module_id = module_hint.upper()
            confidence = 1.0
            analysis = {"matched": ["직접 지정"], "module_desc": self.MODULES[module_id]["desc"]}
        else:
            module_id, confidence, analysis = self.analyze_content(text)
        
        info = self.extract_info(text)
        
        module_info = self.MODULES[module_id]
        print(f"   모듈: {module_info['full']}")
        print(f"   신뢰도: {confidence:.0%}")
        print(f"   매칭 키워드: {', '.join(analysis['matched'])}")
        print(f"   모듈 설명: {module_info['desc']}")
        print()
        
        # 2. 파일명 생성 (하이브리드 방식 - 부모 ID 포함)
        p_num, t_num = self.get_next_file_number(module_id)
        filename = self.generate_filename(module_id, p_num, t_num, parent_id)
        
        print(f"📁 파일 생성:")
        print(f"   위치: 2 Company/4 HTE/{module_info['full']}/")
        print(f"   파일명: {filename}")
        print()
        
        # 3. Holon 문서 생성
        holon_id = f"hte-{module_id.lower()}-p{p_num:02d}t{t_num:02d}"
        
        holon = {
            "holon_id": holon_id,
            "slug": info["title"].replace(" ", "-").lower()[:30],
            "type": doc_type if doc_type != "auto" else "meeting",
            "module": module_info["full"],
            "meta": {
                "title": info["title"],
                "owner": info["participants"][0],
                "created_at": info["date"],
                "updated_at": info["date"],
                "priority": "high",
                "status": "active",
                "file_code": f"{module_id}_P{p_num:02d}_T{t_num:02d}"
            },
            "W": {
                "worldview": {
                    "identity": f"{module_info['desc']}의 일부",
                    "belief": "무지성의 관찰이 오래되었을 때 지식이 스며든다",
                    "value_system": "반복, 흐름, 연결"
                },
                "will": {
                    "drive": f"{info['title']}을 통해 {module_info['desc']}를 실현한다",
                    "commitment": "완벽하게 하기 전에 반복부터 한다",
                    "non_negotiables": ["흐름 유지", "연결 생성", "반복"]
                },
                "intention": {
                    "primary": info["title"],
                    "secondary": info["decisions"][:3] if info["decisions"] else [],
                    "constraints": []
                },
                "goal": {
                    "ultimate": f"{info['title']} 완료",
                    "milestones": info["tasks"][:3] if info["tasks"] else [],
                    "kpi": [],
                    "okr": {"objective": info["title"], "key_results": info["decisions"][:3]}
                },
                "activation": {
                    "triggers": ["문서 생성"],
                    "resonance_check": f"모듈 {module_id}와 {confidence:.0%} 정렬",
                    "drift_detection": "키워드 매칭 모니터링"
                }
            },
            "X": {
                "context": text[:500] + "..." if len(text) > 500 else text,
                "current_state": "생성됨",
                "heartbeat": "once",
                "signals": ["문서 작성됨"],
                "constraints": [],
                "will": "맥락을 정확히 기록한다"
            },
            "S": {
                "resources": [{"type": "human", "id": p, "role": "참여자"} for p in info["participants"]],
                "dependencies": [],
                "access_points": ["이 문서"],
                "structure_model": f"{module_info['full']} 구조",
                "ontology_ref": [module_info["full"]],
                "readiness_score": 1.0,
                "will": "필요한 리소스를 확보한다"
            },
            "P": {
                "procedure_steps": [
                    {"step_id": "p001", "description": "문서 생성", "status": "done"},
                    {"step_id": "p002", "description": "내용 작성", "status": "done"},
                    {"step_id": "p003", "description": "검토", "status": "pending"}
                ],
                "optimization_logic": "무지성의 반복",
                "will": "절차를 흐르게 한다"
            },
            "E": {
                "execution_plan": [{"action_id": "e001", "description": "실행", "role": info["participants"][0], "eta": "TBD"}],
                "tooling": ["Holarchy System"],
                "edge_case_handling": [],
                "will": "실행에 옮긴다"
            },
            "R": {
                "reflection_notes": [],
                "lessons_learned": [],
                "success_path_inference": "반복 → 흐름 → 결정화",
                "future_prediction": "",
                "will": "되돌아보고 개선한다"
            },
            "T": {
                "impact_channels": ["관련자"],
                "traffic_model": "문서 공유",
                "viral_mechanics": "",
                "bottleneck_points": [],
                "will": "전파한다"
            },
            "A": {
                "abstraction": "반복 가능한 템플릿화",
                "modularization": [],
                "automation_opportunities": [],
                "integration_targets": [],
                "resonance_logic": f"모듈 {module_id}와 공명",
                "will": "고도화한다"
            },
            "links": {
                "parent": parent_id,  # 하이브리드: 파일명과 동일한 부모 정보
                "children": [],
                "related": [],
                "supersedes": None
            }
        }
        
        # 4. 파일 저장
        module_path = self.hte_path / module_info["full"]
        module_path.mkdir(parents=True, exist_ok=True)
        
        filepath = module_path / filename
        
        content = f"""```json
{json.dumps(holon, ensure_ascii=False, indent=2)}
```

---

# 📄 {info['title']}

## 원본 내용

{text}

---

## 자동 분석 결과

- **모듈**: {module_info['full']} - {module_info['desc']}
- **신뢰도**: {confidence:.0%}
- **매칭 키워드**: {', '.join(analysis['matched']) or '없음'}

### 추출된 결정 사항
{chr(10).join(f"- {d}" for d in info['decisions']) if info['decisions'] else "- (없음)"}

### 추출된 할일
{chr(10).join(f"- [ ] {t}" for t in info['tasks']) if info['tasks'] else "- (없음)"}

---

*🔥 Holarchy 자동 배치 시스템으로 생성됨 (하이브리드 방식)*
*파일 코드: {module_id}_P{p_num:02d}_T{t_num:02d}*
*부모: [{self._shorten_parent_id(parent_id) if parent_id else "ROOT"}]*
"""
        
        filepath.write_text(content, encoding="utf-8")
        
        # 자동 태깅 적용
        tags_applied = {}
        try:
            from _auto_tagger import AutoTagger
            tagger = AutoTagger(str(self.base_path / "0 Docs" / "holons"))
            tags = tagger.tag_document(filepath)
            if tags:
                tags_applied = tags.to_dict()
                print(f"🏷️ 태그 적용: {list(tags_applied.get('module', []))[:2]} + {list(tags_applied.get('topic', []))[:2]}...")
        except Exception as e:
            print(f"⚠️ 자동 태깅 실패: {e}")
        
        print(f"✅ 파일 생성 완료!")
        print(f"   경로: {filepath}")
        print()
        
        return {
            "success": True,
            "holon_id": holon_id,
            "module": module_info["full"],
            "filename": filename,
            "filepath": str(filepath),
            "confidence": confidence,
            "matched_keywords": analysis["matched"],
            "title": info["title"],
            "decisions": info["decisions"],
            "tags": tags_applied,
            "tasks": info["tasks"],
            "parent_id": parent_id,  # 하이브리드: 부모 정보 반환
            "parent_short": self._shorten_parent_id(parent_id) if parent_id else "ROOT"
        }


def main():
    """테스트"""
    sample = """# AI 튜터 감정 엔진 설계 회의

일시: 2025-11-30
참석자: 김철수, 이영희

## 안건
1. AI 튜터의 감정 인식 기능
2. 학생 반응 분석 알고리즘

## 논의 내용
딥러닝 기반 감정 분석 모델을 적용하기로 함.
LLM을 활용한 대화 분석도 병행.

## 결정 사항
- 결정: TensorFlow 기반 감정 모델 개발
- 결정: 학생 표정 분석 기능 추가

## 할일
- TODO: 김철수 - 모델 아키텍처 설계
- TODO: 이영희 - 학습 데이터 수집
"""
    
    placer = DocumentPlacer()
    result = placer.create_document(sample)
    print()
    print("결과:")
    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

