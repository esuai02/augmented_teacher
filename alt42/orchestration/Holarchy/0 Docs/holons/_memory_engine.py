#!/usr/bin/env python3
"""
🧠 Enterprise Memory Engine v1.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

뇌의 장기기억 원리를 그대로 적용한 기업 문서 기억 시스템

핵심 개념:
1. M-score (Memory Importance Score) - 문서 중요도 점수
2. 4단계 보존 레이어:
   - LTM Permanent (M ≥ 0.8) - 영구 보존
   - LTM Extended (0.5 ≤ M < 0.8) - 1년 보존
   - Compressed LTM (0.2 ≤ M < 0.5) - 6개월 보존
   - Working Memory (M < 0.2) - 3개월 보존

M-score 계산:
  M = (반복 사용 빈도 × 0.3)
    + (성과에 미친 영향 × 0.3)
    + (시스템 규칙에 반영된 정도 × 0.2)
    + (조직의 핵심 목표와의 정렬도 × 0.15)
    + (감정적 임팩트 × 0.05)
"""

import json
import re
import shutil
import logging
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass, field
from enum import Enum

# 로깅 설정
logger = logging.getLogger("holarchy.memory_engine")


class MemoryLayer(Enum):
    """기억 보존 레이어"""
    LTM_PERMANENT = "ltm_permanent"    # M ≥ 0.8 → 영구 보존
    LTM_EXTENDED = "ltm_extended"      # 0.5 ≤ M < 0.8 → 1년 보존
    COMPRESSED_LTM = "compressed_ltm"  # 0.2 ≤ M < 0.5 → 6개월 보존
    WORKING_MEMORY = "working_memory"  # M < 0.2 → 3개월 보존


@dataclass
class MemoryScore:
    """문서별 M-score 및 메타데이터"""
    holon_id: str
    filename: str
    filepath: str
    
    # M-score 구성 요소 (0~1)
    usage_frequency: float = 0.0      # 반복 사용 빈도
    impact_score: float = 0.0         # 성과에 미친 영향
    system_reflection: float = 0.0    # 시스템 규칙에 반영된 정도
    goal_alignment: float = 0.0       # 조직 핵심 목표와의 정렬도
    emotional_intensity: float = 0.0  # 감정적 임팩트
    
    # 메타데이터
    created_at: str = ""
    updated_at: str = ""
    age_days: int = 0
    access_count: int = 0
    is_core: bool = False
    doc_type: str = ""
    
    # 가중치 (뇌의 기억 원리 기반)
    WEIGHTS = {
        "usage_frequency": 0.30,      # 반복 인출 (시냅스 강화)
        "impact_score": 0.30,         # 성과 영향 (아드레날린 기반 강화)
        "system_reflection": 0.20,    # 규칙 반영 (전전두엽 유지)
        "goal_alignment": 0.15,       # 목적 관련성
        "emotional_intensity": 0.05   # 감정적 강도
    }
    
    @property
    def m_score(self) -> float:
        """M-score 계산"""
        score = (
            self.usage_frequency * self.WEIGHTS["usage_frequency"] +
            self.impact_score * self.WEIGHTS["impact_score"] +
            self.system_reflection * self.WEIGHTS["system_reflection"] +
            self.goal_alignment * self.WEIGHTS["goal_alignment"] +
            self.emotional_intensity * self.WEIGHTS["emotional_intensity"]
        )
        return min(1.0, max(0.0, score))
    
    @property
    def memory_layer(self) -> MemoryLayer:
        """M-score 기반 메모리 레이어 결정"""
        m = self.m_score
        if m >= 0.8 or self.is_core:
            return MemoryLayer.LTM_PERMANENT
        elif m >= 0.5:
            return MemoryLayer.LTM_EXTENDED
        elif m >= 0.2:
            return MemoryLayer.COMPRESSED_LTM
        else:
            return MemoryLayer.WORKING_MEMORY
    
    @property
    def retention_days(self) -> int:
        """보존 기간 (일)"""
        layer = self.memory_layer
        if layer == MemoryLayer.LTM_PERMANENT:
            return -1  # 영구 보존
        elif layer == MemoryLayer.LTM_EXTENDED:
            return 365  # 1년
        elif layer == MemoryLayer.COMPRESSED_LTM:
            return 180  # 6개월
        else:
            return 90   # 3개월
    
    @property
    def days_until_action(self) -> int:
        """조치가 필요할 때까지 남은 일수"""
        if self.retention_days == -1:
            return -1  # 영구 보존
        return max(0, self.retention_days - self.age_days)
    
    @property
    def action_needed(self) -> Optional[str]:
        """필요한 조치"""
        if self.days_until_action == -1:
            return None  # 영구 보존
        elif self.days_until_action <= 0:
            layer = self.memory_layer
            if layer == MemoryLayer.WORKING_MEMORY:
                return "compress_or_delete"
            elif layer == MemoryLayer.COMPRESSED_LTM:
                return "compress"
            else:
                return "review"
        elif self.days_until_action <= 7:
            return "warning"
        return None


class MemoryEngine:
    """Enterprise Memory Engine - 뇌의 기억 원리 적용"""
    
    # Core Files (철학/정체성) - 시효 없이 항상 LTM_PERMANENT
    CORE_FILES = [
        "00-holarchy-overview.md",
        "_PHILOSOPHY.md",
        "_ONTOLOGY.md",
        "_MISSION.md",
    ]
    
    # 감정적 임팩트 키워드 (위기/성공/실패)
    EMOTIONAL_KEYWORDS = {
        "high": ["위기", "실패", "성공", "폭증", "폭락", "해결", "돌파", "혁신", "위험", "긴급"],
        "medium": ["개선", "문제", "이슈", "변경", "결정", "승인", "완료"],
        "low": ["계획", "논의", "검토", "회의", "정리"]
    }
    
    # 시스템 반영 키워드
    SYSTEM_KEYWORDS = ["규칙", "원칙", "정책", "기준", "표준", "프로세스", "철학", "가이드"]
    
    # 성과 영향 키워드
    IMPACT_KEYWORDS = ["매출", "성과", "KPI", "OKR", "달성", "목표", "수익", "성장", "효율"]
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.hte_path = self.base_path.parent.parent / "2 Company" / "4 HTE"
        self.reports_path = self.docs_root / "reports"
        self.archive_path = self.docs_root / "_archive"
        self.compressed_path = self.docs_root / "_compressed"
        
        # 접근 기록 파일
        self.access_log_path = self.reports_path / "access_log.json"
        self.memory_scores_path = self.reports_path / "memory_scores.json"
        
        # 디렉토리 생성
        self.reports_path.mkdir(parents=True, exist_ok=True)
    
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
    
    def record_access(self, holon_id: str):
        """문서 접근 기록"""
        log = self._load_access_log()
        
        if holon_id not in log["documents"]:
            log["documents"][holon_id] = {"count": 0, "accesses": []}
        
        log["documents"][holon_id]["count"] += 1
        log["documents"][holon_id]["accesses"].append(datetime.now().isoformat())
        
        # 최근 100개만 유지
        log["documents"][holon_id]["accesses"] = log["documents"][holon_id]["accesses"][-100:]
        
        self._save_access_log(log)
    
    def _calculate_usage_frequency(self, holon_id: str) -> float:
        """반복 사용 빈도 계산 (0~1)"""
        log = self._load_access_log()
        
        if holon_id not in log["documents"]:
            return 0.0
        
        doc_log = log["documents"][holon_id]
        count = doc_log.get("count", 0)
        
        # 최근 90일 내 접근 횟수 기준
        recent_accesses = 0
        cutoff = datetime.now() - timedelta(days=90)
        
        for access_time in doc_log.get("accesses", []):
            try:
                access_dt = datetime.fromisoformat(access_time)
                if access_dt > cutoff:
                    recent_accesses += 1
            except ValueError as e:
                logger.debug(f"접근 시간 파싱 실패 [{access_time}]: {e}")

        # 스코어 계산 (10회 이상이면 1.0)
        return min(1.0, recent_accesses / 10.0)
    
    def _calculate_emotional_intensity(self, content: str) -> float:
        """감정적 임팩트 계산 (0~1)"""
        content_lower = content.lower()
        score = 0.0
        
        # High 키워드
        for keyword in self.EMOTIONAL_KEYWORDS["high"]:
            if keyword in content_lower:
                score += 0.4
        
        # Medium 키워드
        for keyword in self.EMOTIONAL_KEYWORDS["medium"]:
            if keyword in content_lower:
                score += 0.2
        
        # Low 키워드
        for keyword in self.EMOTIONAL_KEYWORDS["low"]:
            if keyword in content_lower:
                score += 0.05
        
        return min(1.0, score)
    
    def _calculate_system_reflection(self, content: str) -> float:
        """시스템 규칙에 반영된 정도 (0~1)"""
        content_lower = content.lower()
        score = 0.0
        
        for keyword in self.SYSTEM_KEYWORDS:
            if keyword in content_lower:
                score += 0.15
        
        return min(1.0, score)
    
    def _calculate_impact_score(self, content: str) -> float:
        """성과에 미친 영향 (0~1)"""
        content_lower = content.lower()
        score = 0.0
        
        for keyword in self.IMPACT_KEYWORDS:
            if keyword in content_lower:
                score += 0.15
        
        return min(1.0, score)
    
    def _calculate_goal_alignment(self, holon: Dict) -> float:
        """조직 핵심 목표와의 정렬도 (0~1)"""
        # W 섹션의 will.drive에서 핵심 키워드 추출
        w_section = holon.get("W", {})
        will = w_section.get("will", {})
        drive = will.get("drive", "") if isinstance(will, dict) else str(will)
        
        # 핵심 목표 키워드
        core_goals = ["자기진화", "교육", "수학", "학원", "AI", "튜터", "시스템", "성장"]
        
        score = 0.0
        for goal in core_goals:
            if goal.lower() in drive.lower():
                score += 0.15
        
        return min(1.0, score)
    
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
    
    def _is_core_file(self, filename: str) -> bool:
        """Core 파일 여부 확인"""
        for core in self.CORE_FILES:
            if core in filename:
                return True
        return False
    
    def analyze_document(self, filepath: Path) -> Optional[MemoryScore]:
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
            logger.debug(f"생성일 파싱 실패 [{created_at}]: {e}")
            age_days = 0

        # M-score 구성 요소 계산
        usage_frequency = self._calculate_usage_frequency(holon_id)
        emotional_intensity = self._calculate_emotional_intensity(content)
        system_reflection = self._calculate_system_reflection(content)
        impact_score = self._calculate_impact_score(content)
        goal_alignment = self._calculate_goal_alignment(holon)
        
        # 접근 기록 카운트
        log = self._load_access_log()
        access_count = log.get("documents", {}).get(holon_id, {}).get("count", 0)
        
        return MemoryScore(
            holon_id=holon_id,
            filename=filepath.name,
            filepath=str(filepath),
            usage_frequency=usage_frequency,
            impact_score=impact_score,
            system_reflection=system_reflection,
            goal_alignment=goal_alignment,
            emotional_intensity=emotional_intensity,
            created_at=created_at,
            updated_at=updated_at,
            age_days=age_days,
            access_count=access_count,
            is_core=self._is_core_file(filepath.name),
            doc_type=holon.get("type", "unknown")
        )
    
    def analyze_all_documents(self) -> List[MemoryScore]:
        """모든 문서 분석"""
        scores = []
        
        # holons 폴더
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            score = self.analyze_document(md_file)
            if score:
                scores.append(score)
        
        # meetings/decisions/tasks 폴더
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                for md_file in folder_path.glob("*.md"):
                    if md_file.name.startswith("_"):
                        continue
                    score = self.analyze_document(md_file)
                    if score:
                        scores.append(score)
        
        # HTE 모듈 폴더
        if self.hte_path.exists():
            for md_file in self.hte_path.rglob("HTE_*.md"):
                score = self.analyze_document(md_file)
                if score:
                    scores.append(score)
        
        # M-score 기준 정렬
        scores.sort(key=lambda x: x.m_score, reverse=True)
        
        return scores
    
    def get_by_layer(self, layer: MemoryLayer) -> List[MemoryScore]:
        """특정 레이어의 문서들"""
        all_scores = self.analyze_all_documents()
        return [s for s in all_scores if s.memory_layer == layer]
    
    def get_action_needed(self) -> Dict[str, List[MemoryScore]]:
        """조치가 필요한 문서들"""
        all_scores = self.analyze_all_documents()
        
        result = {
            "compress_or_delete": [],
            "compress": [],
            "review": [],
            "warning": []
        }
        
        for score in all_scores:
            action = score.action_needed
            if action and action in result:
                result[action].append(score)
        
        return result
    
    def compress_document(self, filepath: Path) -> Optional[Path]:
        """문서 압축 (핵심 속성만 유지)"""
        holon = self._parse_holon(filepath)
        if not holon:
            return None
        
        # 압축 버전 생성 (W, meta, links만 유지)
        compressed = {
            "holon_id": holon.get("holon_id"),
            "type": holon.get("type"),
            "meta": {
                "title": holon.get("meta", {}).get("title"),
                "created_at": holon.get("meta", {}).get("created_at"),
                "compressed_at": datetime.now().strftime("%Y-%m-%d"),
                "original_path": str(filepath)
            },
            "W": {
                "will": holon.get("W", {}).get("will", {}),
                "goal": holon.get("W", {}).get("goal", {})
            },
            "links": holon.get("links", {}),
            "_compressed": True,
            "_compression_note": "Working Memory → Compressed LTM 자동 압축"
        }
        
        # 압축 폴더에 저장
        self.compressed_path.mkdir(parents=True, exist_ok=True)
        compressed_file = self.compressed_path / f"compressed_{filepath.name}"
        
        content = f"""```json
{json.dumps(compressed, ensure_ascii=False, indent=2)}
```

---

# 📦 압축된 문서

- **원본**: `{filepath.name}`
- **압축일**: {datetime.now().strftime("%Y-%m-%d")}
- **M-score**: Working Memory (< 0.2)

*이 문서는 자동 압축되었습니다. 핵심 속성만 유지됩니다.*
"""
        
        compressed_file.write_text(content, encoding="utf-8")
        return compressed_file
    
    def archive_document(self, filepath: Path) -> Optional[Path]:
        """문서 아카이브"""
        if not filepath.exists():
            return None
        
        self.archive_path.mkdir(parents=True, exist_ok=True)
        
        # 날짜 기반 서브폴더
        date_folder = self.archive_path / datetime.now().strftime("%Y-%m")
        date_folder.mkdir(parents=True, exist_ok=True)
        
        dest = date_folder / filepath.name
        shutil.move(str(filepath), str(dest))
        
        return dest
    
    def print_memory_report(self):
        """메모리 상태 리포트 출력"""
        scores = self.analyze_all_documents()
        
        print("=" * 70)
        print("🧠 Enterprise Memory Engine - 기억 상태 리포트")
        print("=" * 70)
        print()
        
        # 레이어별 집계
        layer_counts = {layer: 0 for layer in MemoryLayer}
        for score in scores:
            layer_counts[score.memory_layer] += 1
        
        print("📊 메모리 레이어 분포:")
        print("-" * 50)
        
        layer_info = {
            MemoryLayer.LTM_PERMANENT: ("🔷", "영구 보존", "M ≥ 0.8"),
            MemoryLayer.LTM_EXTENDED: ("🟢", "장기 (1년)", "0.5 ≤ M < 0.8"),
            MemoryLayer.COMPRESSED_LTM: ("🟡", "중기 (6개월)", "0.2 ≤ M < 0.5"),
            MemoryLayer.WORKING_MEMORY: ("🟠", "단기 (3개월)", "M < 0.2")
        }
        
        total = len(scores)
        for layer, (emoji, name, condition) in layer_info.items():
            count = layer_counts[layer]
            pct = (count / total * 100) if total > 0 else 0
            bar = "█" * int(pct / 5) + "░" * (20 - int(pct / 5))
            print(f"  {emoji} {name:15} {bar} {count:3}개 ({pct:5.1f}%) | {condition}")
        
        print()
        print(f"  총 문서: {total}개")
        print()
        
        # 조치 필요 문서
        actions = self.get_action_needed()
        action_total = sum(len(v) for v in actions.values())
        
        if action_total > 0:
            print("⚠️  조치 필요 문서:")
            print("-" * 50)
            
            if actions["compress_or_delete"]:
                print(f"  🔴 압축/삭제 필요: {len(actions['compress_or_delete'])}개 (90일 초과)")
                for s in actions["compress_or_delete"][:3]:
                    print(f"      - {s.filename} (M={s.m_score:.2f}, {s.age_days}일)")
            
            if actions["compress"]:
                print(f"  🟡 압축 필요: {len(actions['compress'])}개 (180일 초과)")
            
            if actions["warning"]:
                print(f"  🟠 7일 내 조치 필요: {len(actions['warning'])}개")
            
            print()
        
        # 상위 10개 문서
        print("🏆 M-score 상위 10개 (기업의 핵심 기억):")
        print("-" * 50)
        
        for i, score in enumerate(scores[:10], 1):
            layer_emoji = layer_info[score.memory_layer][0]
            core_mark = "⭐" if score.is_core else "  "
            print(f"  {i:2}. {layer_emoji}{core_mark} {score.filename[:35]:35} M={score.m_score:.3f}")
            print(f"        사용:{score.usage_frequency:.2f} 영향:{score.impact_score:.2f} "
                  f"규칙:{score.system_reflection:.2f} 정렬:{score.goal_alignment:.2f} 감정:{score.emotional_intensity:.2f}")
        
        print()
        
        # 하위 5개 문서 (망각 대상)
        print("🗑️ M-score 하위 5개 (망각 대상):")
        print("-" * 50)
        
        for score in scores[-5:]:
            layer_emoji = layer_info[score.memory_layer][0]
            action = score.action_needed or "유지"
            print(f"  {layer_emoji} {score.filename[:40]:40} M={score.m_score:.3f} | {action}")
        
        print()
    
    def save_report(self) -> Path:
        """리포트 JSON 저장"""
        scores = self.analyze_all_documents()
        
        report = {
            "generated_at": datetime.now().isoformat(),
            "total_documents": len(scores),
            "layer_distribution": {},
            "action_needed": {},
            "documents": []
        }
        
        # 레이어별 집계
        for layer in MemoryLayer:
            layer_docs = [s for s in scores if s.memory_layer == layer]
            report["layer_distribution"][layer.value] = {
                "count": len(layer_docs),
                "documents": [s.holon_id for s in layer_docs]
            }
        
        # 조치 필요
        actions = self.get_action_needed()
        for action, docs in actions.items():
            report["action_needed"][action] = [s.holon_id for s in docs]
        
        # 문서별 상세
        for score in scores:
            report["documents"].append({
                "holon_id": score.holon_id,
                "filename": score.filename,
                "m_score": round(score.m_score, 4),
                "layer": score.memory_layer.value,
                "retention_days": score.retention_days,
                "age_days": score.age_days,
                "days_until_action": score.days_until_action,
                "is_core": score.is_core,
                "components": {
                    "usage_frequency": round(score.usage_frequency, 3),
                    "impact_score": round(score.impact_score, 3),
                    "system_reflection": round(score.system_reflection, 3),
                    "goal_alignment": round(score.goal_alignment, 3),
                    "emotional_intensity": round(score.emotional_intensity, 3)
                }
            })
        
        # 저장
        with open(self.memory_scores_path, "w", encoding="utf-8") as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        
        return self.memory_scores_path
    
    def run_synapse_pruning(self, dry_run: bool = True) -> Dict:
        """
        시냅스 가지치기 실행 (뇌의 망각 메커니즘)
        - Working Memory 90일 초과 → 압축 또는 삭제
        - Compressed LTM 180일 초과 → 압축
        """
        actions = self.get_action_needed()
        result = {
            "compressed": [],
            "archived": [],
            "skipped": [],
            "dry_run": dry_run
        }
        
        # compress_or_delete 대상
        for score in actions.get("compress_or_delete", []):
            filepath = Path(score.filepath)
            if not filepath.exists():
                continue
            
            if dry_run:
                result["compressed"].append(score.filename)
            else:
                compressed = self.compress_document(filepath)
                if compressed:
                    # 원본 아카이브
                    self.archive_document(filepath)
                    result["compressed"].append(score.filename)
        
        # compress 대상
        for score in actions.get("compress", []):
            filepath = Path(score.filepath)
            if not filepath.exists():
                continue
            
            if dry_run:
                result["compressed"].append(score.filename)
            else:
                self.compress_document(filepath)
                result["compressed"].append(score.filename)
        
        return result


def main():
    """테스트"""
    engine = MemoryEngine()
    engine.print_memory_report()
    
    print()
    print("📄 리포트 저장 중...")
    report_path = engine.save_report()
    print(f"✅ 저장 완료: {report_path}")


if __name__ == "__main__":
    main()

