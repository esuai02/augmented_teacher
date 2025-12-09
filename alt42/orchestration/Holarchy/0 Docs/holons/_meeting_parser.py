#!/usr/bin/env python3
"""
🔥 회의록 자동 파싱 & Holon 생성기
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

기능:
- 회의록 텍스트를 분석하여 자동으로 Holon 문서 생성
- 내용 기반으로 적절한 parent 자동 선택
- Decision, Task 항목 자동 추출
"""

import json
import re
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple

# 로깅 설정
logger = logging.getLogger("holarchy.meeting_parser")


class MeetingParser:
    """회의록 파싱 및 Holon 자동 생성"""
    
    # 키워드 → parent 매핑 (기본)
    KEYWORD_PARENT_MAP = {
        # 제품/기술 관련
        "api": "hte-doc-005",
        "시스템": "hte-doc-002",
        "아키텍처": "hte-doc-002",
        "제품": "hte-doc-002",
        "기능": "hte-doc-002",
        "개발": "hte-doc-002",
        
        # 조직 관련
        "조직": "hte-doc-001",
        "팀": "hte-doc-001",
        "인사": "hte-doc-001",
        "채용": "hte-doc-001",
        
        # 전략 관련
        "전략": "hte-doc-003",
        "운영": "hte-doc-003",
        "프로세스": "hte-doc-003",
        
        # 투자/재무
        "투자": "hte-doc-004",
        "재무": "hte-doc-004",
        "비용": "hte-doc-004",
        "예산": "hte-doc-004",
        
        # AI/PM
        "ai": "strategy-2025-001",
        "pm": "strategy-2025-001",
        "자동화": "strategy-2025-001",
        "학생": "strategy-2025-001",
        "진단": "feature-2025-001",
        "시선": "feature-2025-002",
        "집중도": "feature-2025-002",
    }
    
    # HTE 미션 에이전트 키워드 → 모듈 매핑
    HTE_MODULE_MAP = {
        "M00": {"name": "Astral", "keywords": ["전략", "세계관", "비전", "미션", "철학", "목표", "방향"]},
        "M01": {"name": "TimeCrystal", "keywords": ["시간", "경영", "의사결정", "리더", "일정", "계획"]},
        "M02": {"name": "TimelineGenesis", "keywords": ["운영", "프로세스", "리듬", "회의", "미팅", "절차"]},
        "M03": {"name": "BusinessModel", "keywords": ["비즈니스", "모델", "수익", "매출", "가격", "고객"]},
        "M04": {"name": "KPI_OKR", "keywords": ["kpi", "okr", "목표", "성과", "지표", "측정"]},
        "M05": {"name": "FinancialGrowth", "keywords": ["재무", "투자", "예산", "비용", "자금", "금융"]},
        "M06": {"name": "SWOT", "keywords": ["swot", "강점", "약점", "기회", "위협", "분석"]},
        "M07": {"name": "BizWeighing", "keywords": ["우선순위", "리소스", "배분", "집중", "선택"]},
        "M08": {"name": "InternalBranding", "keywords": ["내부", "브랜딩", "문화", "가치", "직원"]},
        "M09": {"name": "VerticalDrilling", "keywords": ["핵심", "기술", "개발", "연구", "깊이", "전문"]},
        "M10": {"name": "AgentGarden", "keywords": ["agent", "에이전트", "holon", "홀론", "자동화", "봇"]},
        "M11": {"name": "ServicePipeline", "keywords": ["서비스", "파이프라인", "배포", "전달"]},
        "M12": {"name": "ExternalBranding", "keywords": ["외부", "마케팅", "홍보", "광고", "브랜드"]},
        "M13": {"name": "TrackGrowthEngine", "keywords": ["성장", "엔진", "확장", "스케일", "그로스"]},
        "M14": {"name": "CompanyOnboarding", "keywords": ["온보딩", "교육", "신입", "입사", "트레이닝"]},
        "M15": {"name": "TimeCrystalCEO", "keywords": ["ceo", "리더십", "경영진", "대표", "총괄"]},
        "M16": {"name": "AIMindArchitecture", "keywords": ["ai", "인공지능", "머신러닝", "llm", "gpt", "튜터"]},
        "M17": {"name": "NervousSystem", "keywords": ["신경", "데이터", "파이프라인", "연결", "통합", "api"]},
        "M18": {"name": "InformationBlocks", "keywords": ["정보", "블록", "지능", "구조", "프레임워크"]},
        "M19": {"name": "CompanyCulture", "keywords": ["문화", "가치관", "조직문화", "무지성"]},
        "M20": {"name": "KnowledgeCrystal", "keywords": ["지식", "축적", "결정체", "학습", "체계"]},
        "M21": {"name": "SoftwareBackbone", "keywords": ["소프트웨어", "백본", "인프라", "시스템", "서버"]},
    }
    
    # Decision/Task 추출 패턴 (확장)
    DECISION_PATTERNS = [
        r"결정[:\s]*(.+?)(?:\n|$)",
        r"합의[:\s]*(.+?)(?:\n|$)",
        r"확정[:\s]*(.+?)(?:\n|$)",
        r"→\s*결정[:\s]*(.+?)(?:\n|$)",
        r"결론[:\s]*(.+?)(?:\n|$)",
        r"(.+?)(?:로|으로)\s*(?:결정|확정|합의)",  # "A로 결정"
        r"(.+?)(?:하기로|하도록)\s*(?:했|함|하였)",  # "A하기로 함"
    ]
    
    TASK_PATTERNS = [
        r"할일[:\s]*(.+?)(?:\n|$)",
        r"TODO[:\s]*(.+?)(?:\n|$)",
        r"액션[:\s]*(.+?)(?:\n|$)",
        r"담당[:\s]*(.+?)(?:\n|$)",
        r"→\s*(.+?)\s*담당",
        r"\[\s*\]\s*(.+?)(?:\n|$)",  # [ ] 체크박스
        r"(.+?)\s*(?:담당|책임)[:\s]*(\w+)",  # "A 담당: 홍길동"
    ]
    
    # Action 패턴 (결정/할일 자동 감지) - 확장
    ACTION_PATTERNS = [
        r"(.+?)(?:해야\s*(?:한다|함|합니다|해요))",  # "~해야 한다"
        r"(.+?)(?:하자|합시다|해봐요)",  # "~하자"
        r"(.+?)(?:필요(?:하다|함|합니다|해요)?)",  # "~필요"
        r"(.+?)(?:개선|수정|보완|추가|삭제|변경)(?:\s*(?:필요|요망|요청|해야))",  # "~개선 필요"
        r"(.+?)\s*버튼\s*(?:필요|추가|생성)",  # "버튼 필요"
        r"(.+?)(?:안\s*됨|안됨|오류|에러|버그)",  # "~안됨", "오류"
        r"(?:문제|이슈)[:\s]*(.+?)(?:\n|$)",  # "문제: ~"
    ]
    
    # 번호 목록 패턴
    NUMBERED_ITEM_PATTERN = r"(?:^|\n)\s*(\d+)[\.)\s]+(.+?)(?=\n\s*\d+[\.)\s]|\n\n|\Z)"
    
    def __init__(self, holons_path: str):
        self.holons_path = Path(holons_path)
        self.meetings_path = self.holons_path.parent / "meetings"
        self.decisions_path = self.holons_path.parent / "decisions"
        self.tasks_path = self.holons_path.parent / "tasks"
        
        # HTE 미션 에이전트 경로
        self.hte_path = self.holons_path.parent.parent / "2 Company" / "4 HTE"
        
        # 폴더 생성
        self.meetings_path.mkdir(exist_ok=True)
        self.decisions_path.mkdir(exist_ok=True)
        self.tasks_path.mkdir(exist_ok=True)
        
        # 기존 Holon 로드 (holons + HTE)
        self.holons = self._load_holons()
        self.hte_docs = self._load_hte_documents()
    
    def _load_holons(self) -> Dict[str, dict]:
        """기존 Holon 문서 로드"""
        holons = {}
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    holon_id = holon.get("holon_id")
                    if holon_id:
                        holons[holon_id] = holon
                except json.JSONDecodeError as e:
                    logger.debug(f"Holon JSON 파싱 실패 [{md_file.name}]: {e}")
        return holons
    
    def _load_hte_documents(self) -> Dict[str, dict]:
        """HTE 미션 에이전트 문서 로드"""
        hte_docs = {}
        
        if not self.hte_path.exists():
            return hte_docs
        
        # HTE 폴더의 모든 HTE_*.md 파일 로드
        for md_file in self.hte_path.rglob("HTE_*.md"):
            try:
                content = md_file.read_text(encoding="utf-8")
                json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
                if json_match:
                    holon = json.loads(json_match.group(1))
                    holon_id = holon.get("holon_id")
                    if holon_id:
                        # 모듈 정보 추가
                        module = holon.get("module", "")
                        hte_docs[holon_id] = {
                            **holon,
                            "_filepath": str(md_file),
                            "_module": module
                        }
            except (json.JSONDecodeError, FileNotFoundError, UnicodeDecodeError) as e:
                logger.debug(f"HTE 문서 로드 실패 [{md_file.name}]: {e}")
        
        return hte_docs
    
    def _search_hte_by_keywords(self, text: str) -> List[Tuple[str, float, str]]:
        """
        키워드로 HTE 문서 검색
        
        Returns:
            [(holon_id, score, module), ...]
        """
        text_lower = text.lower()
        results = []
        
        # 1. 모듈 레벨 매칭
        for module_id, info in self.HTE_MODULE_MAP.items():
            score = 0
            matched = []
            for keyword in info["keywords"]:
                if keyword.lower() in text_lower:
                    score += 1
                    matched.append(keyword)
            
            if score > 0:
                # 해당 모듈의 문서들 검색
                module_full = f"{module_id}_{info['name']}"
                for holon_id, doc in self.hte_docs.items():
                    if module_full in doc.get("_module", ""):
                        results.append((holon_id, score / 5, module_full))
        
        # 2. 문서 제목/내용 매칭
        for holon_id, doc in self.hte_docs.items():
            title = doc.get("meta", {}).get("title", "").lower()
            drive = doc.get("W", {}).get("will", {}).get("drive", "").lower()
            
            # 제목이나 drive에 키워드가 있으면 추가 점수
            for keyword in text_lower.split()[:20]:  # 처음 20단어만
                if len(keyword) > 2:
                    if keyword in title or keyword in drive:
                        # 이미 있으면 점수 증가
                        found = False
                        for i, (hid, score, mod) in enumerate(results):
                            if hid == holon_id:
                                results[i] = (hid, score + 0.1, mod)
                                found = True
                                break
                        if not found:
                            results.append((holon_id, 0.3, doc.get("_module", "")))
        
        # 점수순 정렬
        results.sort(key=lambda x: x[1], reverse=True)
        return results[:10]  # 상위 10개
    
    def _extract_title(self, text: str) -> str:
        """회의록에서 제목 추출"""
        lines = text.strip().split("\n")
        
        # 첫 줄이 제목일 가능성
        first_line = lines[0].strip()
        
        # # 으로 시작하면 마크다운 제목
        if first_line.startswith("#"):
            return first_line.lstrip("#").strip()
        
        # 제목: 패턴
        title_match = re.search(r"제목[:\s]*(.+?)(?:\n|$)", text)
        if title_match:
            return title_match.group(1).strip()
        
        # 회의명: 패턴
        meeting_match = re.search(r"회의명[:\s]*(.+?)(?:\n|$)", text)
        if meeting_match:
            return meeting_match.group(1).strip()
        
        # 첫 줄 사용 (30자 제한)
        return first_line[:30] if first_line else "회의록"
    
    def _extract_date(self, text: str) -> str:
        """회의록에서 날짜 추출"""
        # YYYY-MM-DD 패턴
        date_match = re.search(r"(\d{4}-\d{2}-\d{2})", text)
        if date_match:
            return date_match.group(1)
        
        # YYYY.MM.DD 패턴
        date_match = re.search(r"(\d{4})\.(\d{2})\.(\d{2})", text)
        if date_match:
            return f"{date_match.group(1)}-{date_match.group(2)}-{date_match.group(3)}"
        
        # 오늘 날짜
        return datetime.now().strftime("%Y-%m-%d")
    
    def _extract_participants(self, text: str) -> List[str]:
        """참석자 추출"""
        participants = []
        
        # 참석자: 패턴
        match = re.search(r"참석자?[:\s]*(.+?)(?:\n|$)", text)
        if match:
            names = re.split(r"[,，、\s]+", match.group(1))
            participants = [n.strip() for n in names if n.strip()]
        
        return participants if participants else ["미지정"]
    
    def _find_best_parent(self, text: str) -> Tuple[str, float, List[Dict]]:
        """
        내용 기반으로 최적의 parent 찾기 (HTE 문서 포함)
        
        Returns:
            (parent_id, confidence, referenced_docs)
        """
        text_lower = text.lower()
        
        # 1. 기본 키워드 매칭
        scores = {}
        for keyword, parent_id in self.KEYWORD_PARENT_MAP.items():
            if keyword in text_lower:
                scores[parent_id] = scores.get(parent_id, 0) + 1
        
        # 2. HTE 문서 검색
        hte_matches = self._search_hte_by_keywords(text)
        for holon_id, score, module in hte_matches:
            # HTE 문서 점수 추가 (가중치 2배)
            scores[holon_id] = scores.get(holon_id, 0) + (score * 2)
        
        # 참조 문서 목록 생성
        referenced_docs = []
        for holon_id, score, module in hte_matches[:5]:
            doc = self.hte_docs.get(holon_id, {})
            referenced_docs.append({
                "holon_id": holon_id,
                "title": doc.get("meta", {}).get("title", holon_id),
                "module": module,
                "score": score
            })
        
        if scores:
            best_parent = max(scores, key=scores.get)
            max_score = scores[best_parent]
            confidence = min(max_score / 5, 1.0)  # 최대 1.0
            return best_parent, confidence, referenced_docs
        
        # 기본값: strategy-2025-001 (AI PM)
        return "strategy-2025-001", 0.3, referenced_docs
    
    def _extract_decisions(self, text: str) -> List[str]:
        """결정 사항 추출"""
        decisions = []
        for pattern in self.DECISION_PATTERNS:
            matches = re.findall(pattern, text, re.IGNORECASE)
            decisions.extend([m.strip() for m in matches if m.strip()])
        return list(set(decisions))  # 중복 제거
    
    def _extract_tasks(self, text: str) -> List[str]:
        """할일 추출"""
        tasks = []
        for pattern in self.TASK_PATTERNS:
            matches = re.findall(pattern, text, re.IGNORECASE)
            for m in matches:
                if isinstance(m, tuple):
                    tasks.append(' - '.join(m).strip())
                else:
                    tasks.append(m.strip())
        return list(set(t for t in tasks if t and len(t) > 3))  # 중복 제거, 최소 길이
    
    def _extract_actions_from_patterns(self, text: str) -> Tuple[List[str], List[str]]:
        """
        Action 패턴으로 결정/할일 자동 추출
        
        Returns:
            (inferred_decisions, inferred_tasks)
        """
        inferred_decisions = []
        inferred_tasks = []
        
        for pattern in self.ACTION_PATTERNS:
            matches = re.findall(pattern, text, re.IGNORECASE | re.MULTILINE)
            for m in matches:
                content = m.strip() if isinstance(m, str) else m[0].strip()
                if not content or len(content) < 5:
                    continue
                
                # 분류: "필요", "해야" → Task, "로 결정", "확정" → Decision
                content_lower = content.lower()
                if any(kw in content_lower for kw in ['필요', '해야', '오류', '버그', '안됨', '문제']):
                    inferred_tasks.append(content[:100])
                else:
                    inferred_decisions.append(content[:100])
        
        return list(set(inferred_decisions))[:5], list(set(inferred_tasks))[:5]
    
    def _extract_numbered_items(self, text: str) -> List[Dict]:
        """
        번호 목록 파싱 (1. 첫번째, 2. 두번째...)
        
        Returns:
            [{"num": 1, "content": "...", "type": "issue/task/decision"}]
        """
        items = []
        matches = re.findall(self.NUMBERED_ITEM_PATTERN, text, re.DOTALL)
        
        for num, content in matches:
            content = content.strip()
            if not content:
                continue
            
            # 타입 추론
            content_lower = content.lower()
            if any(kw in content_lower for kw in ['오류', '에러', '버그', '안됨', '문제']):
                item_type = 'issue'
            elif any(kw in content_lower for kw in ['필요', '해야', '추가', '수정']):
                item_type = 'task'
            elif any(kw in content_lower for kw in ['결정', '확정', '합의', '완료']):
                item_type = 'decision'
            else:
                item_type = 'info'
            
            items.append({
                "num": int(num),
                "content": content[:200],
                "type": item_type
            })
        
        return items
    
    def _determine_status(self, text: str, decisions: List[str], tasks: List[str]) -> str:
        """
        실제 상태 판단 (표면적 상태가 아닌 실질적 완료 여부)
        """
        # 결정/할일이 없으면 미완료
        if not decisions and not tasks:
            return "incomplete"
        
        # 미완료 키워드 체크
        incomplete_keywords = ['추후', '나중에', 'TBD', '미정', '검토 필요', '추가 논의']
        text_lower = text.lower()
        for kw in incomplete_keywords:
            if kw.lower() in text_lower:
                return "pending"
        
        # 진행중 키워드 체크
        progress_keywords = ['진행중', '진행 중', '개발중', '작업중']
        for kw in progress_keywords:
            if kw.lower() in text_lower:
                return "in_progress"
        
        return "completed"
    
    def _extract_agenda(self, text: str) -> List[str]:
        """안건 추출"""
        agenda = []
        
        # 안건: 패턴
        agenda_match = re.search(r"안건[:\s]*\n?((?:.+\n?)+)", text)
        if agenda_match:
            items = agenda_match.group(1).split("\n")
            for item in items:
                item = item.strip()
                if item and not item.startswith(("참석", "일시", "장소")):
                    # 번호/불릿 제거
                    item = re.sub(r"^[\d\.\-\*\•]+\s*", "", item)
                    if item:
                        agenda.append(item)
        
        return agenda[:5]  # 최대 5개
    
    def _generate_meeting_id(self) -> str:
        """회의 ID 생성"""
        today = datetime.now()
        year = today.strftime("%Y")
        
        # 기존 meeting 개수 확인
        existing = list(self.meetings_path.glob(f"meeting-{year}-*.md"))
        next_num = len(existing) + 1
        
        return f"meeting-{year}-{next_num:03d}"
    
    def _generate_execution_plan(self, decisions: List[str], tasks: List[str], 
                                  participants: List[str]) -> List[Dict]:
        """
        결정/할일에서 상세 실행 계획 자동 생성
        """
        plan = []
        action_id = 1
        
        # 결정사항 → 실행 계획
        for i, decision in enumerate(decisions[:5]):
            plan.append({
                "action_id": f"e{action_id:03d}",
                "type": "decision",
                "description": decision[:100],
                "role": participants[i % len(participants)] if participants else "담당자 미정",
                "eta": "TBD",
                "status": "pending"
            })
            action_id += 1
        
        # 할일 → 실행 계획
        for i, task in enumerate(tasks[:5]):
            # 담당자 추출 시도
            assignee = "담당자 미정"
            assignee_match = re.search(r"(\w+)\s*(?:담당|책임)", task)
            if assignee_match:
                assignee = assignee_match.group(1)
            elif participants:
                assignee = participants[i % len(participants)]
            
            plan.append({
                "action_id": f"e{action_id:03d}",
                "type": "task",
                "description": task[:100],
                "role": assignee,
                "eta": "TBD",
                "status": "pending"
            })
            action_id += 1
        
        # 계획이 비어있으면 기본 항목 추가
        if not plan:
            plan.append({
                "action_id": "e001",
                "type": "review",
                "description": "회의 내용 검토 및 후속 조치 결정 필요",
                "role": participants[0] if participants else "담당자 미정",
                "eta": "TBD",
                "status": "pending"
            })
        
        return plan
    
    def _generate_procedure_steps(self, status: str, decisions: List[str], 
                                   tasks: List[str]) -> List[Dict]:
        """
        상태에 따른 절차 단계 생성
        """
        steps = [
            {"step_id": "p001", "description": "안건 검토", "status": "done"},
            {"step_id": "p002", "description": "논의 진행", "status": "done"},
        ]
        
        # 결정 도출 상태
        if decisions:
            steps.append({"step_id": "p003", "description": f"결정 도출 ({len(decisions)}건)", "status": "done"})
        else:
            steps.append({"step_id": "p003", "description": "결정 도출", "status": "pending"})
        
        # 할일 도출 상태
        if tasks:
            steps.append({"step_id": "p004", "description": f"할일 도출 ({len(tasks)}건)", "status": "done"})
        else:
            steps.append({"step_id": "p004", "description": "할일 도출", "status": "pending"})
        
        # 후속 조치 상태
        if status == "completed":
            steps.append({"step_id": "p005", "description": "후속 조치 확정", "status": "done"})
        elif status == "in_progress":
            steps.append({"step_id": "p005", "description": "후속 조치 진행 중", "status": "in_progress"})
        else:
            steps.append({"step_id": "p005", "description": "후속 조치 필요", "status": "pending"})
        
        return steps
    
    def _generate_kpi(self, decisions: List[str], tasks: List[str], 
                      numbered_items: List[Dict]) -> List[str]:
        """
        구체적인 KPI 생성
        """
        kpis = []
        
        if decisions:
            kpis.append(f"결정 사항: {len(decisions)}건 확정")
        else:
            kpis.append("결정 사항: 0건 (추가 논의 필요)")
        
        if tasks:
            kpis.append(f"실행 과제: {len(tasks)}건 도출")
        else:
            kpis.append("실행 과제: 0건 (액션 아이템 필요)")
        
        # 이슈 카운트
        issues = [i for i in numbered_items if i.get("type") == "issue"]
        if issues:
            kpis.append(f"이슈/문제: {len(issues)}건 식별")
        
        return kpis
    
    def _calculate_completion_rate(self, decisions: List[str], tasks: List[str], 
                                    status: str) -> Dict:
        """
        완료율 계산
        """
        # 기본 점수 체계
        score = 0
        max_score = 100
        breakdown = {}
        
        # 결정 사항 (30점)
        if decisions:
            decision_score = min(len(decisions) * 10, 30)
            score += decision_score
            breakdown["decisions"] = {"score": decision_score, "max": 30, "count": len(decisions)}
        else:
            breakdown["decisions"] = {"score": 0, "max": 30, "count": 0}
        
        # 할일 (30점)
        if tasks:
            task_score = min(len(tasks) * 10, 30)
            score += task_score
            breakdown["tasks"] = {"score": task_score, "max": 30, "count": len(tasks)}
        else:
            breakdown["tasks"] = {"score": 0, "max": 30, "count": 0}
        
        # 상태 (40점)
        status_scores = {
            "completed": 40,
            "in_progress": 20,
            "pending": 10,
            "incomplete": 0
        }
        status_score = status_scores.get(status, 0)
        score += status_score
        breakdown["status"] = {"score": status_score, "max": 40, "value": status}
        
        return {
            "total": score,
            "max": max_score,
            "percentage": round(score / max_score * 100),
            "breakdown": breakdown,
            "is_complete": score >= 70
        }
    
    def _create_meeting_holon(self, text: str) -> dict:
        """회의록 텍스트로 Meeting Holon 생성"""
        title = self._extract_title(text)
        date = self._extract_date(text)
        participants = self._extract_participants(text)
        parent_id, confidence, referenced_docs = self._find_best_parent(text)
        
        # 기본 패턴으로 추출
        decisions = self._extract_decisions(text)
        tasks = self._extract_tasks(text)
        
        # Action 패턴으로 추가 추출
        inferred_decisions, inferred_tasks = self._extract_actions_from_patterns(text)
        decisions = list(set(decisions + inferred_decisions))
        tasks = list(set(tasks + inferred_tasks))
        
        # 번호 목록에서 추가 추출
        numbered_items = self._extract_numbered_items(text)
        for item in numbered_items:
            if item["type"] == "issue":
                tasks.append(f"[이슈 #{item['num']}] {item['content'][:80]}")
            elif item["type"] == "task":
                tasks.append(f"[항목 #{item['num']}] {item['content'][:80]}")
            elif item["type"] == "decision":
                decisions.append(f"[결정 #{item['num']}] {item['content'][:80]}")
        
        # 중복 제거 및 정리
        decisions = list(set(decisions))[:10]
        tasks = list(set(tasks))[:10]
        
        agenda = self._extract_agenda(text)
        meeting_id = self._generate_meeting_id()
        
        # 실제 상태 판단
        actual_status = self._determine_status(text, decisions, tasks)
        
        # Root W 가져오기
        root_w = self.holons.get("hte-doc-000", {}).get("W", {})
        root_drive = root_w.get("will", {}).get("drive", "")
        
        holon = {
            "holon_id": meeting_id,
            "slug": title.replace(" ", "-").lower()[:30],
            "type": "meeting",
            "module": "M02_TimelineGenesis",
            "meta": {
                "title": title,
                "owner": participants[0] if participants else "미지정",
                "created_at": date,
                "updated_at": date,
                "priority": "high",
                "status": "active"
            },
            "W": {
                "worldview": {
                    "identity": "회의를 통해 의사결정과 방향 정렬을 수행하는 협업 세션",
                    "belief": "효과적인 회의는 명확한 결정과 실행 가능한 태스크를 만들어낸다",
                    "value_system": "시간 효율, 결정 명확성, 실행 연결"
                },
                "will": {
                    "drive": f"이 회의를 통해 {title} 관련 명확한 결정을 내리고 다음 단계로 진행한다. {root_drive[:50]}...",
                    "commitment": "모든 안건에 대해 결론을 내리고 담당자를 지정한다",
                    "non_negotiables": ["결정 사항 문서화", "담당자 지정", "기한 설정"]
                },
                "intention": {
                    "primary": title,
                    "secondary": agenda[:3] if agenda else ["안건 논의"],
                    "constraints": ["시간 제한", "참석자 일정"]
                },
                "goal": {
                    "ultimate": f"{title} 완료 및 후속 조치 확정",
                    "milestones": ["안건 논의", "결정", "액션 아이템 도출"],
                    "kpi": self._generate_kpi(decisions, tasks, numbered_items),
                    "okr": {
                        "objective": title,
                        "key_results": decisions[:3] if decisions else ["결정 사항 도출"]
                    },
                    "completion_rate": self._calculate_completion_rate(decisions, tasks, actual_status)
                },
                "activation": {
                    "triggers": ["회의 시작"],
                    "resonance_check": f"상위 목표({parent_id})와 정렬 확인됨 (신뢰도: {confidence:.0%})",
                    "drift_detection": "회의 목적에서 벗어나는 논의 감지"
                }
            },
            "X": {
                "context": text[:500] + "..." if len(text) > 500 else text,
                "current_state": actual_status,  # 실질적 상태 반영
                "numbered_items": [f"#{i['num']}: {i['content'][:50]}..." for i in numbered_items[:5]],
                "heartbeat": "once",
                "signals": ["회의록 작성됨"] + ([f"이슈 {len([i for i in numbered_items if i['type']=='issue'])}건 감지"] if numbered_items else []),
                "constraints": ["참석자 일정", "시간 제한"],
                "will": "회의 맥락을 정확히 기록하여 후속 조치에 활용한다"
            },
            "S": {
                "resources": [
                    {"type": "human", "id": p, "role": "참석자"} for p in participants
                ],
                "dependencies": [parent_id],
                "access_points": ["이 문서"],
                "structure_model": "회의 → 결정 → 태스크",
                "ontology_ref": ["M02_TimelineGenesis"],
                "readiness_score": 1.0,
                "will": "필요한 리소스를 확보하여 회의 목적을 달성한다"
            },
            "P": {
                "procedure_steps": self._generate_procedure_steps(actual_status, decisions, tasks),
                "optimization_logic": "효율적인 회의 진행",
                "will": "체계적인 절차로 생산적인 회의를 수행한다"
            },
            "E": {
                "execution_plan": self._generate_execution_plan(decisions, tasks, participants),
                "tooling": ["회의실", "화상회의"],
                "edge_case_handling": ["불참 시 회의록 공유"],
                "will": "결정된 사항을 실제로 실행한다"
            },
            "R": {
                "reflection_notes": ["회의 완료"],
                "lessons_learned": [],
                "success_path_inference": "명확한 결정 → 실행 → 성과",
                "future_prediction": "후속 회의 필요 여부 판단",
                "will": "회의 결과를 되돌아보고 개선점을 찾는다"
            },
            "T": {
                "impact_channels": ["참석자", "관련 팀"],
                "traffic_model": "회의록 공유 → 액션 실행",
                "viral_mechanics": "결정 사항의 팀 전파",
                "bottleneck_points": ["실행 지연"],
                "will": "회의 결과를 관련자에게 효과적으로 전파한다"
            },
            "A": {
                "abstraction": "반복 가능한 회의 템플릿화",
                "modularization": ["안건 모듈", "결정 모듈", "태스크 모듈"],
                "automation_opportunities": ["회의록 자동 생성", "태스크 자동 추출"],
                "integration_targets": [parent_id],
                "resonance_logic": f"상위 목표와 정렬 (신뢰도: {confidence:.0%})",
                "will": "회의 패턴을 고도화하여 효율성을 높인다"
            },
            "links": {
                "parent": parent_id,
                "children": [],
                "related": [doc["holon_id"] for doc in referenced_docs],  # HTE 참조 문서
                "supersedes": None
            },
            "_parsed": {
                "decisions": decisions,
                "tasks": tasks,
                "agenda": agenda,
                "participants": participants,
                "confidence": confidence,
                "numbered_items": numbered_items,
                "actual_status": actual_status,
                "completion_rate": holon["W"]["goal"]["completion_rate"]["percentage"],
                "referenced_docs": referenced_docs  # HTE 참조 문서
            }
        }
        
        return holon
    
    def parse_and_create(self, text: str, auto_spawn: bool = True) -> dict:
        """회의록 파싱 후 Holon 파일 생성"""
        print("=" * 60)
        print("🔥 회의록 자동 파싱 & Holon 생성")
        print("=" * 60)
        print()
        
        # Meeting Holon 생성
        print("📝 회의록 분석 중...")
        holon = self._create_meeting_holon(text)
        
        meeting_id = holon["holon_id"]
        title = holon["meta"]["title"]
        parent_id = holon["links"]["parent"]
        confidence = holon["_parsed"]["confidence"]
        decisions = holon["_parsed"]["decisions"]
        tasks = holon["_parsed"]["tasks"]
        
        actual_status = holon["_parsed"]["actual_status"]
        completion = holon["_parsed"]["completion_rate"]
        numbered_items = holon["_parsed"]["numbered_items"]
        
        referenced_docs = holon["_parsed"]["referenced_docs"]
        
        print(f"   제목: {title}")
        print(f"   ID: {meeting_id}")
        print(f"   상위 연결: {parent_id} (신뢰도: {confidence:.0%})")
        print(f"   결정 사항: {len(decisions)}건")
        print(f"   할일: {len(tasks)}건")
        print(f"   번호 항목: {len(numbered_items)}건 (이슈: {len([i for i in numbered_items if i['type']=='issue'])}건)")
        print(f"   실제 상태: {actual_status} (완료율: {completion}%)")
        
        # HTE 참조 문서 표시
        if referenced_docs:
            print(f"   📚 참조된 HTE 문서: {len(referenced_docs)}건")
            for doc in referenced_docs[:3]:
                print(f"      └─ {doc['module']}: {doc['title'][:30]}... ({doc['score']:.0%})")
        print()
        
        # _parsed 제거 후 저장
        parsed_info = holon.pop("_parsed")
        
        # Meeting 파일 저장
        filename = f"{meeting_id}-{holon['slug']}.md"
        filepath = self.meetings_path / filename
        
        # 이슈 목록 생성
        issues = [i for i in numbered_items if i["type"] == "issue"]
        
        content = f"""```json
{json.dumps(holon, ensure_ascii=False, indent=2)}
```

---

# 📋 {title}

| 항목 | 값 |
|------|-----|
| 상태 | {actual_status} |
| 완료율 | {completion}% |
| 결정 | {len(decisions)}건 |
| 할일 | {len(tasks)}건 |
| 이슈 | {len(issues)}건 |

---

## 원본 회의록

{text}

---

## 🔍 자동 추출 정보

### ✅ 결정 사항 ({len(decisions)}건)
{chr(10).join(f"- {d}" for d in decisions) if decisions else "- (추출된 결정 없음)"}

### ⬜ 할일 ({len(tasks)}건)
{chr(10).join(f"- [ ] {t}" for t in tasks) if tasks else "- (추출된 할일 없음)"}

### 🔴 이슈/문제 ({len(issues)}건)
{chr(10).join(f"- #{i['num']}: {i['content'][:80]}" for i in issues) if issues else "- (식별된 이슈 없음)"}

### 📚 참조된 HTE 문서 ({len(referenced_docs)}건)
{chr(10).join(f"- **{doc['module']}**: {doc['title']} ({doc['score']:.0%})" for doc in referenced_docs) if referenced_docs else "- (관련 HTE 문서 없음)"}

---

## 📊 완료율 분석

- **결정**: {holon['W']['goal']['completion_rate']['breakdown']['decisions']['count']}건 → {holon['W']['goal']['completion_rate']['breakdown']['decisions']['score']}/{holon['W']['goal']['completion_rate']['breakdown']['decisions']['max']}점
- **할일**: {holon['W']['goal']['completion_rate']['breakdown']['tasks']['count']}건 → {holon['W']['goal']['completion_rate']['breakdown']['tasks']['score']}/{holon['W']['goal']['completion_rate']['breakdown']['tasks']['max']}점
- **상태**: {holon['W']['goal']['completion_rate']['breakdown']['status']['value']} → {holon['W']['goal']['completion_rate']['breakdown']['status']['score']}/{holon['W']['goal']['completion_rate']['breakdown']['status']['max']}점
- **총점**: **{completion}%** {'✅ 완료' if holon['W']['goal']['completion_rate']['is_complete'] else '⚠️ 미완료'}

---

*🔥 Self-Healing 모드로 자동 생성됨*
"""
        
        filepath.write_text(content, encoding="utf-8")
        print(f"✅ Meeting 생성: {filepath}")
        
        result = {
            "meeting_id": meeting_id,
            "file": str(filepath),
            "parent": parent_id,
            "confidence": confidence,
            "decisions": decisions,
            "tasks": tasks,
            "spawned": [],
            "referenced_docs": referenced_docs,  # HTE 참조 문서
            "completion_rate": completion,
            "actual_status": actual_status
        }
        
        # Decision/Task 자동 생성
        if auto_spawn and (decisions or tasks):
            print()
            print("🚀 Decision/Task 자동 생성 중...")
            spawned = self._spawn_from_meeting(meeting_id, decisions, tasks)
            result["spawned"] = spawned
        
        print()
        print("=" * 60)
        print(f"🔥 완료! Meeting + {len(result['spawned'])}개 하위 문서 생성")
        print("=" * 60)
        
        return result
    
    def _spawn_from_meeting(self, meeting_id: str, decisions: List[str], tasks: List[str]) -> List[str]:
        """Meeting에서 Decision/Task 생성"""
        spawned = []
        today = datetime.now().strftime("%Y-%m-%d")
        year = datetime.now().strftime("%Y")
        
        # Decision 생성
        for i, decision in enumerate(decisions[:5], 1):  # 최대 5개
            decision_id = f"decision-{year}-{len(list(self.decisions_path.glob('*.md'))) + i:03d}"
            
            decision_holon = {
                "holon_id": decision_id,
                "slug": decision[:20].replace(" ", "-").lower(),
                "type": "decision",
                "meta": {
                    "title": decision[:50],
                    "created_at": today,
                    "status": "active"
                },
                "W": {
                    "will": {
                        "drive": f"'{decision}'을 실행에 옮긴다"
                    }
                },
                "links": {
                    "parent": meeting_id,
                    "children": [],
                    "related": []
                }
            }
            
            filepath = self.decisions_path / f"{decision_id}.md"
            content = f"""```json
{json.dumps(decision_holon, ensure_ascii=False, indent=2)}
```

# ✅ {decision[:50]}

*{meeting_id}에서 자동 생성됨*
"""
            filepath.write_text(content, encoding="utf-8")
            spawned.append(decision_id)
            print(f"   ✅ Decision: {decision_id}")
        
        # Task 생성
        for i, task in enumerate(tasks[:5], 1):  # 최대 5개
            task_id = f"task-{year}-{len(list(self.tasks_path.glob('*.md'))) + i:03d}"
            
            task_holon = {
                "holon_id": task_id,
                "slug": task[:20].replace(" ", "-").lower(),
                "type": "task",
                "meta": {
                    "title": task[:50],
                    "created_at": today,
                    "status": "pending"
                },
                "W": {
                    "will": {
                        "drive": f"'{task}'를 완료한다"
                    }
                },
                "links": {
                    "parent": meeting_id,
                    "children": [],
                    "related": []
                }
            }
            
            filepath = self.tasks_path / f"{task_id}.md"
            content = f"""```json
{json.dumps(task_holon, ensure_ascii=False, indent=2)}
```

# ⬜ {task[:50]}

- [ ] 완료

*{meeting_id}에서 자동 생성됨*
"""
            filepath.write_text(content, encoding="utf-8")
            spawned.append(task_id)
            print(f"   ⬜ Task: {task_id}")
        
        return spawned


def main():
    """테스트용"""
    sample = """
# 2025년 AI 튜터 개발 킥오프 회의

일시: 2025-11-30
참석자: 김철수, 이영희, 박민수

## 안건
1. AI 튜터 MVP 범위 확정
2. 개발 일정 논의
3. 담당자 배정

## 논의 내용
학생 진단 기능을 먼저 개발하기로 함.
시선 추적 기능은 2단계로 진행.

## 결정 사항
- 결정: MVP는 진단 리포트 기능으로 한정
- 결정: 2월 말 베타 출시 목표

## 할일
- TODO: 김철수 - UI 디자인 초안 (12/15까지)
- TODO: 이영희 - 백엔드 API 설계
- 박민수 담당 - 데이터 모델 설계
"""
    
    parser = MeetingParser(str(Path(__file__).parent))
    result = parser.parse_and_create(sample)
    print(json.dumps(result, ensure_ascii=False, indent=2))


if __name__ == "__main__":
    main()

