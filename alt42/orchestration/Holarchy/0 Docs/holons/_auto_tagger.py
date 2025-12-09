#!/usr/bin/env python3
"""
🏷️ Auto-Tagger v3 - 멀티레이어 자동 태깅 엔진
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

기능:
- 문서 내용 분석하여 멀티레이어 태그 자동 부여
- 주제(semantic_topic) / 역할(role) / 페르소나(persona) / 긴급도(urgency) / 실행가능성(actionability)
- 기존 Holon JSON의 meta.tags 필드에 저장

태그 레이어:
1. module: M00~M21 모듈 매핑
2. topic: 의미 기반 주제 태그
3. role: spec, meeting, decision 등
4. persona: 학생, 선생님, 부모 등
5. urgency: 긴급, 최신, 즉시수정
6. actionability: action-required, done, issue-list
"""

import json
import logging

# 로깅 설정
logger = logging.getLogger("holarchy.auto_tagger")
import re
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Set, Tuple
from dataclasses import dataclass, field, asdict


# ═══════════════════════════════════════════════════════════════════
# 키워드 사전 정의
# ═══════════════════════════════════════════════════════════════════

# 모듈 매핑 (기존 _document_placer.py에서 통합)
MODULE_KEYWORDS = {
    "M00": ["전략", "세계관", "비전", "미션", "철학", "목표", "방향", "핵심"],
    "M01": ["시간", "경영", "의사결정", "리더", "일정", "계획", "스케줄"],
    "M02": ["운영", "프로세스", "리듬", "회의", "미팅", "절차", "워크플로우"],
    "M03": ["비즈니스", "모델", "수익", "매출", "가격", "고객", "시장"],
    "M04": ["kpi", "okr", "목표", "성과", "지표", "측정", "달성"],
    "M05": ["재무", "투자", "예산", "비용", "자금", "금융", "성장"],
    "M06": ["swot", "강점", "약점", "기회", "위협", "분석", "경쟁"],
    "M07": ["우선순위", "리소스", "배분", "집중", "선택", "비중"],
    "M08": ["내부", "브랜딩", "문화", "가치", "직원", "팀"],
    "M09": ["핵심", "기술", "개발", "연구", "깊이", "전문", "드릴링"],
    "M10": ["agent", "에이전트", "holon", "홀론", "자동화", "봇"],
    "M11": ["서비스", "파이프라인", "배포", "전달", "운영"],
    "M12": ["외부", "마케팅", "홍보", "광고", "브랜드", "인지도"],
    "M13": ["성장", "엔진", "확장", "스케일", "그로스"],
    "M14": ["온보딩", "교육", "신입", "입사", "트레이닝"],
    "M15": ["ceo", "리더십", "경영진", "대표", "총괄"],
    "M16": ["ai", "인공지능", "머신러닝", "llm", "gpt", "튜터", "학습"],
    "M17": ["신경", "데이터", "파이프라인", "연결", "통합", "api"],
    "M18": ["정보", "블록", "지능", "구조", "프레임워크"],
    "M19": ["문화", "가치관", "조직문화", "무지성"],
    "M20": ["지식", "축적", "결정체", "학습", "체계"],
    "M21": ["소프트웨어", "백본", "인프라", "시스템", "서버", "클라우드"],
}

# 주제 태그 (semantic_topic)
SEMANTIC_TOPICS = {
    # 기능 관련
    "TTS": ["tts", "음성", "읽기", "발화", "speech", "voice"],
    "질문하기": ["질문", "qna", "query", "물어", "답변"],
    "화이트보드": ["화이트보드", "whiteboard", "그리기", "캔버스"],
    "타이머": ["타이머", "timer", "시간측정", "카운트"],
    "녹화": ["녹화", "recording", "영상", "비디오"],
    
    # 경험 관련
    "학생경험": ["학생 화면", "student", "학습자", "수강생"],
    "교사도구": ["선생님", "teacher", "교사", "강사", "튜터"],
    "학부모": ["학부모", "parent", "보호자", "가정통신"],
    
    # 기술 관련
    "시선추적": ["시선", "eye tracking", "attention", "집중도", "gaze"],
    "감정분석": ["감정", "emotion", "sentiment", "표정"],
    "AI튜터": ["ai 튜터", "인공지능 튜터", "자동 튜터링"],
    
    # 시스템 관련
    "오류관리": ["오류", "error", "bug", "버그", "에러", "문제"],
    "UX문제": ["ux", "사용성", "불편", "개선"],
    "시스템안정성": ["안정성", "stability", "crash", "다운", "멈춤"],
    "자동화": ["자동화", "automation", "자동", "배치"],
    "모듈설계": ["모듈", "설계", "아키텍처", "구조"],
}

# 역할 태그 (functional_role)
ROLE_KEYWORDS = {
    "spec": ["명세", "스펙", "정의", "specification", "설계서"],
    "meeting": ["회의", "미팅", "meeting", "논의", "안건"],
    "decision": ["결정", "decision", "확정", "합의"],
    "task-set": ["할일", "task", "todo", "작업"],
    "error-report": ["오류 보고", "버그 리포트", "에러 보고"],
    "planning": ["계획", "기획", "planning", "로드맵"],
    "roadmap": ["로드맵", "roadmap", "마일스톤", "일정"],
    "analysis": ["분석", "analysis", "리서치", "조사"],
    "feature": ["기능", "feature", "신규 기능"],
}

# 페르소나 태그 (persona_context)
PERSONA_KEYWORDS = {
    "학생": ["학생", "수강생", "학습자", "student", "learner"],
    "선생님": ["선생님", "교사", "강사", "teacher", "tutor", "instructor"],
    "부모": ["학부모", "부모", "보호자", "parent", "guardian"],
    "운영진": ["운영", "관리자", "admin", "operator", "매니저"],
    "AI시스템": ["ai", "시스템", "봇", "자동화", "agent"],
}

# 긴급도 키워드 (urgency)
URGENCY_KEYWORDS = {
    "긴급": ["긴급", "urgent", "asap", "즉시", "당장"],
    "즉시수정": ["오류", "error", "bug", "버그", "crash", "다운"],
    "높음": ["high", "중요", "critical", "필수"],
}

# 실행가능성 키워드 (actionability)
ACTIONABILITY_PATTERNS = {
    "action-required": [
        r"해야\s*(?:한다|함|합니다)",
        r"필요(?:하다|함|합니다)?",
        r"버튼\s*(?:필요|추가|생성)",
        r"(?:개선|수정|보완|추가|삭제|변경)\s*(?:필요|요망|요청)",
    ],
    "done": [
        r"완료",
        r"종료",
        r"해결됨",
        r"done",
        r"finished",
    ],
    "issue-list": [
        r"(?:^|\n)\s*\d+[\.)\s]",  # 번호 목록
    ],
}


# ═══════════════════════════════════════════════════════════════════
# 태그 결과 데이터 클래스
# ═══════════════════════════════════════════════════════════════════

@dataclass
class TagResult:
    """태그 결과"""
    module: List[str] = field(default_factory=list)
    topic: List[str] = field(default_factory=list)
    role: str = ""
    persona: List[str] = field(default_factory=list)
    urgency: str = ""
    actionability: str = ""
    
    # 메타 정보
    confidence: float = 0.0
    generated_at: str = ""
    
    def to_dict(self) -> Dict:
        """딕셔너리로 변환"""
        result = {}
        if self.module:
            result["module"] = self.module
        if self.topic:
            result["topic"] = self.topic
        if self.role:
            result["role"] = self.role
        if self.persona:
            result["persona"] = self.persona
        if self.urgency:
            result["urgency"] = self.urgency
        if self.actionability:
            result["actionability"] = self.actionability
        return result


# ═══════════════════════════════════════════════════════════════════
# Auto-Tagger 클래스
# ═══════════════════════════════════════════════════════════════════

class AutoTagger:
    """멀티레이어 자동 태깅 엔진"""
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.hte_path = self.docs_root.parent / "2 Company" / "4 HTE"
    
    # ─────────────────────────────────────────────────────────────
    # 개별 태그 레이어 분석
    # ─────────────────────────────────────────────────────────────
    
    def detect_modules(self, text: str) -> List[Tuple[str, float]]:
        """모듈 태그 감지 (M00~M21)"""
        text_lower = text.lower()
        scores = {}
        
        for module_id, keywords in MODULE_KEYWORDS.items():
            score = sum(1 for kw in keywords if kw.lower() in text_lower)
            if score > 0:
                scores[module_id] = score / len(keywords)
        
        # 점수순 정렬
        sorted_modules = sorted(scores.items(), key=lambda x: x[1], reverse=True)
        return sorted_modules[:3]  # 상위 3개
    
    def detect_semantic_topics(self, text: str) -> List[Tuple[str, float]]:
        """주제 태그 감지"""
        text_lower = text.lower()
        scores = {}
        
        for topic, keywords in SEMANTIC_TOPICS.items():
            score = sum(1 for kw in keywords if kw.lower() in text_lower)
            if score > 0:
                scores[topic] = score / len(keywords)
        
        # 0.3 이상만 유지
        filtered = [(t, s) for t, s in scores.items() if s >= 0.2]
        return sorted(filtered, key=lambda x: x[1], reverse=True)[:5]
    
    def detect_role(self, text: str, doc_type: str = "") -> str:
        """역할 태그 감지"""
        # 기존 type 필드 우선
        if doc_type in ROLE_KEYWORDS:
            return doc_type
        
        text_lower = text.lower()
        best_role = ""
        best_score = 0
        
        for role, keywords in ROLE_KEYWORDS.items():
            score = sum(1 for kw in keywords if kw.lower() in text_lower)
            if score > best_score:
                best_score = score
                best_role = role
        
        return best_role
    
    def detect_personas(self, text: str) -> List[str]:
        """페르소나 태그 감지"""
        text_lower = text.lower()
        detected = []
        
        for persona, keywords in PERSONA_KEYWORDS.items():
            if any(kw.lower() in text_lower for kw in keywords):
                detected.append(persona)
        
        return detected
    
    def detect_urgency(self, text: str, priority: str = "", updated_at: str = "") -> str:
        """긴급도 태그 감지"""
        text_lower = text.lower()
        
        # priority 필드 우선
        if priority == "high":
            return "긴급"
        
        # 키워드 기반
        for urgency, keywords in URGENCY_KEYWORDS.items():
            if any(kw.lower() in text_lower for kw in keywords):
                return urgency
        
        # 최근 업데이트 확인
        if updated_at:
            try:
                updated = datetime.fromisoformat(updated_at.replace("Z", "+00:00"))
                if datetime.now(updated.tzinfo) - updated < timedelta(days=3):
                    return "최신"
            except ValueError as e:
                logger.debug(f"업데이트 시간 파싱 실패 [{updated_at}]: {e}")
        
        return ""
    
    def detect_actionability(self, text: str) -> str:
        """실행가능성 태그 감지"""
        for action_type, patterns in ACTIONABILITY_PATTERNS.items():
            for pattern in patterns:
                if re.search(pattern, text, re.IGNORECASE | re.MULTILINE):
                    return action_type
        return ""
    
    # ─────────────────────────────────────────────────────────────
    # 통합 태그 생성
    # ─────────────────────────────────────────────────────────────
    
    def generate_tags(self, text: str, holon: Dict = None) -> TagResult:
        """
        문서 내용을 분석하여 멀티레이어 태그 생성
        
        Args:
            text: 문서 전체 텍스트
            holon: 기존 Holon JSON (있으면 meta 정보 활용)
        
        Returns:
            TagResult 객체
        """
        # 기존 메타 정보 추출
        meta = holon.get("meta", {}) if holon else {}
        doc_type = holon.get("type", "") if holon else ""
        priority = meta.get("priority", "")
        updated_at = meta.get("updated_at", "")
        
        # 각 레이어별 분석
        modules = self.detect_modules(text)
        topics = self.detect_semantic_topics(text)
        role = self.detect_role(text, doc_type)
        personas = self.detect_personas(text)
        urgency = self.detect_urgency(text, priority, updated_at)
        actionability = self.detect_actionability(text)
        
        # 신뢰도 계산 (태그 수 기반)
        total_tags = len(modules) + len(topics) + (1 if role else 0) + len(personas)
        confidence = min(total_tags / 10, 1.0)
        
        return TagResult(
            module=[m[0] for m in modules],
            topic=[t[0] for t in topics],
            role=role,
            persona=personas,
            urgency=urgency,
            actionability=actionability,
            confidence=confidence,
            generated_at=datetime.now().isoformat()
        )
    
    # ─────────────────────────────────────────────────────────────
    # 문서 태깅
    # ─────────────────────────────────────────────────────────────
    
    def tag_document(self, filepath: Path, dry_run: bool = False) -> Optional[TagResult]:
        """
        단일 문서 태깅
        
        Args:
            filepath: 문서 경로
            dry_run: True면 태그만 생성하고 저장 안함
        
        Returns:
            TagResult 또는 None
        """
        if not filepath.exists():
            print(f"❌ 파일 없음: {filepath}")
            return None
        
        content = filepath.read_text(encoding="utf-8")
        
        # JSON 블록 추출
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        if not json_match:
            print(f"⚠️ JSON 없음: {filepath.name}")
            return None
        
        try:
            holon = json.loads(json_match.group(1))
        except json.JSONDecodeError as e:
            print(f"❌ JSON 파싱 오류: {filepath.name} - {e}")
            return None
        
        # 태그 생성
        tags = self.generate_tags(content, holon)
        
        if dry_run:
            return tags
        
        # meta.tags에 저장
        if "meta" not in holon:
            holon["meta"] = {}
        
        holon["meta"]["tags"] = tags.to_dict()
        holon["meta"]["tags_generated_at"] = tags.generated_at
        
        # 파일 업데이트
        new_json = json.dumps(holon, ensure_ascii=False, indent=2)
        new_content = re.sub(
            r'```json\s*\n.*?\n```',
            f'```json\n{new_json}\n```',
            content,
            flags=re.DOTALL
        )
        
        filepath.write_text(new_content, encoding="utf-8")
        return tags
    
    def tag_all_documents(self, dry_run: bool = False) -> Dict[str, TagResult]:
        """
        전체 문서 태깅
        
        Args:
            dry_run: True면 태그만 생성하고 저장 안함
        
        Returns:
            {filepath: TagResult}
        """
        print("=" * 60)
        print("🏷️  Auto-Tagger v3 - 전체 문서 태깅")
        print("=" * 60)
        print()
        
        results = {}
        
        # 1. holons 폴더
        print("📁 0 Docs/holons/")
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            tags = self.tag_document(md_file, dry_run)
            if tags:
                results[str(md_file)] = tags
                print(f"   ✅ {md_file.name}: {tags.module} + {tags.topic[:2]}...")
        
        # 2. meetings/decisions/tasks 폴더
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                print(f"\n📁 0 Docs/{folder}/")
                for md_file in folder_path.glob("*.md"):
                    tags = self.tag_document(md_file, dry_run)
                    if tags:
                        results[str(md_file)] = tags
                        print(f"   ✅ {md_file.name}: {tags.role or folder}")
        
        # 3. HTE 폴더
        if self.hte_path.exists():
            print(f"\n📁 2 Company/4 HTE/")
            for md_file in self.hte_path.rglob("HTE_*.md"):
                tags = self.tag_document(md_file, dry_run)
                if tags:
                    results[str(md_file)] = tags
                    print(f"   ✅ {md_file.name}: {tags.module}")
        
        print()
        print("=" * 60)
        print(f"🏷️  완료! 총 {len(results)}개 문서 태깅")
        if dry_run:
            print("   (dry-run 모드 - 저장 안함)")
        print("=" * 60)
        
        return results
    
    def print_tags(self, tags: TagResult):
        """태그 결과 출력"""
        print(f"   모듈: {', '.join(tags.module) or '-'}")
        print(f"   주제: {', '.join(tags.topic) or '-'}")
        print(f"   역할: {tags.role or '-'}")
        print(f"   페르소나: {', '.join(tags.persona) or '-'}")
        print(f"   긴급도: {tags.urgency or '-'}")
        print(f"   실행가능성: {tags.actionability or '-'}")
        print(f"   신뢰도: {tags.confidence:.0%}")


# ═══════════════════════════════════════════════════════════════════
# CLI 인터페이스
# ═══════════════════════════════════════════════════════════════════

def main():
    """테스트/CLI"""
    import argparse
    
    parser = argparse.ArgumentParser(description="Auto-Tagger v3")
    parser.add_argument("--all", action="store_true", help="전체 문서 태깅")
    parser.add_argument("--file", type=str, help="단일 파일 태깅")
    parser.add_argument("--dry-run", action="store_true", help="저장 안함")
    
    args = parser.parse_args()
    
    tagger = AutoTagger()
    
    if args.all:
        tagger.tag_all_documents(dry_run=args.dry_run)
    elif args.file:
        filepath = Path(args.file)
        tags = tagger.tag_document(filepath, dry_run=args.dry_run)
        if tags:
            print(f"\n🏷️ {filepath.name} 태그:")
            tagger.print_tags(tags)
    else:
        # 테스트
        sample = """# AI 튜터 감정 분석 기능 설계

이 문서는 학생의 감정을 분석하여 맞춤형 피드백을 제공하는 기능을 정의합니다.

## 문제
1. 학생이 TTS 음성을 들을 때 집중도가 떨어짐
2. 선생님이 학생 상태를 실시간으로 파악하기 어려움

## 해결
- 시선 추적 기능 추가 필요
- 감정 분석 AI 연동 필요

## TODO
- 김철수 담당: API 설계
"""
        tags = tagger.generate_tags(sample)
        print("🏷️ 샘플 태그 결과:")
        tagger.print_tags(tags)


if __name__ == "__main__":
    main()

