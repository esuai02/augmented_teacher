#!/usr/bin/env python3
"""
Holarchy Self-Healing 검증 시스템 v4.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔥 Self-Healing 모드
━━━━━━━━━━━━━━━━━━━━
- 검증 실패 → 시스템 중단 (X)
- 검증 실패 → 문제 기록만 (O)
- 모든 결과는 reports/issues.json에 저장
- 시스템은 절대 멈추지 않음

📊 스코어 시스템 (참고용)
━━━━━━━━━━━━━━━━━━━━━━━━━━
- structure: W 구조 완전성 (25%)
- completeness: 내용 채움 정도 (25%)
- resonance: 상위 W와 공명 (25%)
- links: 참조 일관성 (25%)
"""

import json
import re
from pathlib import Path
from typing import Dict, List, Optional
from dataclasses import dataclass, field
from datetime import datetime


# 90% 기준 (참고용, 강제 아님)
PASS_THRESHOLD = 0.90

# Self-Healing 모드: 결과 저장 경로
REPORTS_DIR = Path(__file__).parent.parent / "reports"


@dataclass
class DocumentScore:
    """문서별 완성도 스코어"""
    holon_id: str
    file: str
    structure: float = 0.0      # W 구조 완전성
    completeness: float = 0.0   # 내용 채움 정도 (플레이스홀더 감지)
    resonance: float = 0.0      # 상위 W와 공명
    links: float = 0.0          # 참조 일관성
    
    suggestions: List[str] = field(default_factory=list)
    
    @property
    def total(self) -> float:
        """전체 스코어 (가중 평균)"""
        return (self.structure + self.completeness + self.resonance + self.links) / 4
    
    @property
    def passed(self) -> bool:
        """90% 기준 통과 여부"""
        return self.total >= PASS_THRESHOLD
    
    def to_dict(self) -> dict:
        return {
            "holon_id": self.holon_id,
            "structure": round(self.structure, 2),
            "completeness": round(self.completeness, 2),
            "resonance": round(self.resonance, 2),
            "links": round(self.links, 2),
            "total": round(self.total, 2),
            "passed": self.passed
        }


class HolarchyValidator:
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.holons: Dict[str, dict] = {}
        self.scores: Dict[str, DocumentScore] = {}
        
    def load_holons(self) -> None:
        """모든 Holon 문서 로드"""
        holons_path = self.base_path / "holons"
        
        for md_file in holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
                
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    holon_id = holon.get("holon_id", md_file.stem)
                    holon["_file"] = str(md_file)
                    holon["_content"] = content
                    self.holons[holon_id] = holon
                    self.scores[holon_id] = DocumentScore(holon_id=holon_id, file=str(md_file))
                except json.JSONDecodeError:
                    pass
    
    def score_structure(self) -> None:
        """W 구조 완전성 스코어 (25%)"""
        required_w_fields = {
            "worldview": ["identity", "belief", "value_system"],
            "will": ["drive", "commitment", "non_negotiables"],
            "intention": ["primary", "secondary", "constraints"],
            "goal": ["ultimate", "milestones", "kpi", "okr"],
            "activation": ["triggers", "resonance_check", "drift_detection"]
        }
        
        total_fields = sum(len(fields) + 1 for fields in required_w_fields.values())  # +1 for section itself
        
        for holon_id, holon in self.holons.items():
            w = holon.get("W", {})
            score = self.scores[holon_id]
            
            present_fields = 0
            
            for section, fields in required_w_fields.items():
                if section in w:
                    present_fields += 1
                    for field in fields:
                        if field in w[section]:
                            present_fields += 1
                        else:
                            score.suggestions.append(f"W.{section}.{field} 추가 권장")
                else:
                    score.suggestions.append(f"W.{section} 섹션 추가 권장")
            
            # WXSPERTA will 필드 체크
            slots = ["X", "S", "P", "E", "R", "T", "A"]
            will_present = 0
            for slot in slots:
                if slot in holon and "will" in holon[slot]:
                    will_present += 1
            
            # 구조 스코어 = W 구조 (70%) + WXSPERTA will (30%)
            w_score = present_fields / total_fields if total_fields > 0 else 0
            will_score = will_present / len(slots)
            
            score.structure = w_score * 0.7 + will_score * 0.3
    
    def score_completeness(self) -> None:
        """내용 채움 정도 스코어 - 플레이스홀더 감지 (25%)"""
        placeholder_pattern = re.compile(r'\[.*?\]')  # [...] 패턴
        tbd_pattern = re.compile(r'TBD|TODO|FIXME|XXX', re.IGNORECASE)
        
        for holon_id, holon in self.holons.items():
            score = self.scores[holon_id]
            content = holon.get("_content", "")
            
            # JSON 부분만 추출
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            if not json_match:
                score.completeness = 0.0
                continue
            
            json_content = json_match.group(1)
            
            # 플레이스홀더 개수
            placeholders = placeholder_pattern.findall(json_content)
            tbd_matches = tbd_pattern.findall(json_content)
            
            # 총 필드 수 대비 플레이스홀더 비율
            total_strings = len(re.findall(r'"[^"]*"', json_content))
            placeholder_count = len(placeholders) + len(tbd_matches)
            
            if total_strings == 0:
                score.completeness = 0.0
            else:
                # 플레이스홀더가 없으면 100%, 많으면 낮아짐
                placeholder_ratio = placeholder_count / total_strings
                score.completeness = max(0, 1.0 - placeholder_ratio * 3)  # 33% 이상 플레이스홀더면 0점
            
            if placeholder_count > 0:
                score.suggestions.append(f"플레이스홀더 {placeholder_count}개 발견 - 내용 채움 권장")
    
    def score_resonance(self) -> None:
        """상위 W와 공명 스코어 (25%)"""
        critical_keywords = {"시장", "독점", "자동화", "학원", "수학", "ai", "전국"}
        
        for holon_id, holon in self.holons.items():
            score = self.scores[holon_id]
            links = holon.get("links", {})
            parent_id = links.get("parent")
            
            # Root 문서는 100% 공명
            if parent_id is None:
                score.resonance = 1.0
                continue
            
            if parent_id not in self.holons:
                score.resonance = 0.5  # 부모가 없으면 50%
                score.suggestions.append(f"Parent {parent_id}를 찾을 수 없음")
                continue
            
            parent = self.holons[parent_id]
            parent_drive = parent.get("W", {}).get("will", {}).get("drive", "")
            child_drive = holon.get("W", {}).get("will", {}).get("drive", "")
            
            # 키워드 공명 체크
            parent_keywords = set(parent_drive.lower().split()) & critical_keywords
            child_keywords = set(child_drive.lower().split()) & critical_keywords
            
            if not parent_keywords:
                score.resonance = 1.0  # 상위에 키워드 없으면 공명 불필요
            else:
                overlap = parent_keywords & child_keywords
                score.resonance = len(overlap) / len(parent_keywords)
                
                if score.resonance < 0.9:
                    missing = parent_keywords - child_keywords
                    score.suggestions.append(f"상위 키워드 추가 권장: {missing}")
    
    def score_links(self) -> None:
        """참조 일관성 스코어 (25%)"""
        for holon_id, holon in self.holons.items():
            score = self.scores[holon_id]
            links = holon.get("links", {})
            
            checks = []
            
            # Parent-Child 양방향
            parent_id = links.get("parent")
            if parent_id and parent_id in self.holons:
                parent_children = self.holons[parent_id].get("links", {}).get("children", [])
                checks.append(holon_id in parent_children)
                if holon_id not in parent_children:
                    score.suggestions.append(f"Parent {parent_id}의 children에 추가 권장")
            elif parent_id is None:
                checks.append(True)  # Root는 OK
            else:
                checks.append(False)
                score.suggestions.append(f"Parent {parent_id} 존재하지 않음")
            
            # Children 역참조
            for child_id in links.get("children", []):
                if child_id in self.holons:
                    child_parent = self.holons[child_id].get("links", {}).get("parent")
                    checks.append(child_parent == holon_id)
                else:
                    checks.append(False)
                    score.suggestions.append(f"Child {child_id} 존재하지 않음")
            
            # Related 양방향 (가산점)
            for related_id in links.get("related", []):
                if related_id in self.holons:
                    related_links = self.holons[related_id].get("links", {}).get("related", [])
                    if holon_id not in related_links:
                        score.suggestions.append(f"Related {related_id}에 양방향 링크 권장")
            
            if not checks:
                score.links = 1.0
            else:
                score.links = sum(checks) / len(checks)
    
    def save_to_reports(self) -> None:
        """Self-Healing: 결과를 reports 폴더에 저장 (기록만, 시스템 중단 없음)"""
        REPORTS_DIR.mkdir(parents=True, exist_ok=True)
        
        # issues.json 생성
        issues = []
        for holon_id, score in self.scores.items():
            for suggestion in score.suggestions:
                severity = "info"
                if "존재하지 않음" in suggestion or "찾을 수 없음" in suggestion:
                    severity = "warning"
                    
                issues.append({
                    "holon_id": holon_id,
                    "severity": severity,
                    "message": suggestion,
                    "score": round(score.total, 2)
                })
        
        issues_data = {
            "generated_at": datetime.now().isoformat(),
            "total_issues": len(issues),
            "by_severity": {
                "error": sum(1 for i in issues if i["severity"] == "error"),
                "warning": sum(1 for i in issues if i["severity"] == "warning"),
                "info": sum(1 for i in issues if i["severity"] == "info")
            },
            "issues": issues,
            "system_note": "Self-Healing 모드 - 이 파일은 문제를 기록만 합니다. 시스템은 멈추지 않습니다."
        }
        
        with open(REPORTS_DIR / "issues.json", "w", encoding="utf-8") as f:
            json.dump(issues_data, f, ensure_ascii=False, indent=2)
        
        # risk_score.json 생성
        avg_score = sum(s.total for s in self.scores.values()) / len(self.scores) if self.scores else 1.0
        avg_structure = sum(s.structure for s in self.scores.values()) / len(self.scores) if self.scores else 1.0
        avg_completeness = sum(s.completeness for s in self.scores.values()) / len(self.scores) if self.scores else 1.0
        avg_resonance = sum(s.resonance for s in self.scores.values()) / len(self.scores) if self.scores else 1.0
        avg_links = sum(s.links for s in self.scores.values()) / len(self.scores) if self.scores else 1.0
        
        risk_level = "low" if avg_score >= 0.9 else ("medium" if avg_score >= 0.7 else "high")
        
        risk_data = {
            "generated_at": datetime.now().isoformat(),
            "overall_score": round(avg_score * 100),
            "breakdown": {
                "structure": round(avg_structure * 100),
                "completeness": round(avg_completeness * 100),
                "links": round(avg_links * 100),
                "resonance": round(avg_resonance * 100)
            },
            "risk_level": risk_level,
            "system_note": "점수는 참고용입니다. 낮아도 시스템은 정상 작동합니다."
        }
        
        with open(REPORTS_DIR / "risk_score.json", "w", encoding="utf-8") as f:
            json.dump(risk_data, f, ensure_ascii=False, indent=2)
        
        # suggestions.json 생성
        suggestions = []
        for holon_id, score in self.scores.items():
            if not score.passed and score.suggestions:
                suggestions.append({
                    "holon_id": holon_id,
                    "current_score": round(score.total * 100),
                    "target_score": 90,
                    "suggestions": score.suggestions[:5]
                })
        
        suggestions_data = {
            "generated_at": datetime.now().isoformat(),
            "suggestions": suggestions,
            "system_note": "자동 생성된 수정 추천 목록 (선택사항)"
        }
        
        with open(REPORTS_DIR / "suggestions.json", "w", encoding="utf-8") as f:
            json.dump(suggestions_data, f, ensure_ascii=False, indent=2)
    
    def run_all_validations(self) -> None:
        """모든 검증 실행 (Self-Healing: 기록만, 중단 없음)"""
        print("=" * 70)
        print("🔥 Holarchy Self-Healing 검증 v4.0")
        print("   (문제 기록만, 시스템 중단 없음)")
        print("=" * 70)
        print()
        
        print("📂 Holon 문서 로드 중...")
        self.load_holons()
        print(f"   로드된 문서: {len(self.holons)}개")
        print()
        
        print("📊 스코어 계산 중...")
        self.score_structure()
        self.score_completeness()
        self.score_resonance()
        self.score_links()
        print()
        
        # 결과 출력
        passed_docs = []
        needs_improvement = []
        
        print("📋 문서별 스코어:")
        print("-" * 70)
        print(f"{'문서':<30} {'구조':>8} {'완성':>8} {'공명':>8} {'링크':>8} {'총점':>8}")
        print("-" * 70)
        
        for holon_id, score in sorted(self.scores.items()):
            status = "✅" if score.passed else "📝"
            print(f"{status} {holon_id:<28} {score.structure:>7.0%} {score.completeness:>7.0%} "
                  f"{score.resonance:>7.0%} {score.links:>7.0%} {score.total:>7.0%}")
            
            if score.passed:
                passed_docs.append(holon_id)
            else:
                needs_improvement.append((holon_id, score))
        
        print("-" * 70)
        print()
        
        # Self-Healing: 결과 저장
        self.save_to_reports()
        print(f"💾 결과 저장됨: {REPORTS_DIR}/")
        print(f"   - issues.json ({sum(len(s.suggestions) for s in self.scores.values())}개 이슈)")
        print(f"   - risk_score.json")
        print(f"   - suggestions.json")
        print()
        
        # 개선 제안 (강제 아님)
        if needs_improvement:
            print("💡 개선 제안 (선택사항 - 무시해도 시스템 정상 작동):")
            print("-" * 70)
            for holon_id, score in needs_improvement[:3]:  # 상위 3개만
                print(f"\n📄 {holon_id} (현재 {score.total:.0%})")
                for suggestion in score.suggestions[:3]:  # 최대 3개
                    print(f"   → {suggestion}")
            if len(needs_improvement) > 3:
                print(f"\n   ... 외 {len(needs_improvement) - 3}개 문서")
            print()
        
        # 요약
        print("=" * 70)
        avg_score = sum(s.total for s in self.scores.values()) / len(self.scores) if self.scores else 0
        
        # Self-Healing: 항상 성공 (기록만)
        print(f"🔥 Self-Healing 완료 - 평균 점수 {avg_score:.0%}")
        print("   • 모든 이슈가 reports/에 기록되었습니다")
        print("   • 시스템은 정상 작동합니다")
        print("   • 수정은 선택사항입니다")
        print("=" * 70)


def main():
    """Self-Healing 모드: 항상 성공 (exit 0)"""
    script_dir = Path(__file__).parent
    base_path = script_dir.parent
    
    validator = HolarchyValidator(str(base_path))
    validator.run_all_validations()
    
    # Self-Healing: 항상 성공 반환 (시스템 중단 없음)
    return 0


if __name__ == "__main__":
    exit(main())
