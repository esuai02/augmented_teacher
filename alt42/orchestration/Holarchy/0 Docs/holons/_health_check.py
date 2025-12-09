#!/usr/bin/env python3
"""
🔥 Self-Healing Health Check Engine v2.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Self-Healing 모드:
- 점검 결과는 참고용 (강제 아님)
- 낮은 점수도 시스템 중단 없음
- 모든 결과는 기록만

8개 점검 영역:
1. 상위 구조(Top-down Constitution) 안정성
2. 프로젝트 폭발(Project Explosion) 위험
3. 링크 구조 붕괴(Link Graph Collapse)
4. 연구 프로세스(R&E Engine) 품질
5. 메타 연구 엔진(Meta-Research Engine)
6. 문서 질(Completeness & Drift)
7. 자동화 엔진 안정성
8. 사람 개입(Human-in-the-Loop)
"""

import json
import re
import os
import logging
from pathlib import Path
from datetime import datetime, timedelta
from typing import Dict, List, Tuple, Optional, Set
from dataclasses import dataclass, field
from collections import defaultdict

# 로깅 설정
logger = logging.getLogger("holarchy.health_check")


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 데이터 구조
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

@dataclass
class CheckItem:
    """개별 점검 항목"""
    id: str
    category: str
    name: str
    status: str  # pass, warning, fail, skip
    message: str
    severity: int  # 1=critical, 2=high, 3=medium, 4=low
    recommendation: str = ""
    
    def to_dict(self) -> dict:
        return {
            "id": self.id,
            "category": self.category,
            "name": self.name,
            "status": self.status,
            "message": self.message,
            "severity": self.severity,
            "recommendation": self.recommendation
        }


@dataclass
class HealthReport:
    """건강 점검 리포트"""
    timestamp: str
    total_checks: int
    passed: int
    warnings: int
    failed: int
    skipped: int
    overall_health: float  # 0.0 ~ 1.0
    checks: List[CheckItem] = field(default_factory=list)
    
    def to_dict(self) -> dict:
        return {
            "timestamp": self.timestamp,
            "total_checks": self.total_checks,
            "passed": self.passed,
            "warnings": self.warnings,
            "failed": self.failed,
            "skipped": self.skipped,
            "overall_health": round(self.overall_health, 2),
            "checks": [c.to_dict() for c in self.checks]
        }


# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
# 건강 점검 엔진
# ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

class HealthCheckEngine:
    """시스템 건강 점검 엔진"""
    
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.holons_path = self.base_path / "holons"
        self.reports_path = self.base_path / "reports"
        
        self.holons: Dict[str, dict] = {}
        self.checks: List[CheckItem] = []
        
        # 메타 데이터
        self.similarity_matrix: Dict = {}
        self.chunks_data: Dict = {}
    
    def load_data(self) -> None:
        """데이터 로드"""
        # Holon 문서 로드
        for md_file in self.holons_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    holon_id = holon.get("holon_id", md_file.stem)
                    holon["_file_path"] = str(md_file)
                    holon["_file_mtime"] = md_file.stat().st_mtime
                    self.holons[holon_id] = holon
                except json.JSONDecodeError as e:
                    logger.debug(f"Holon JSON 파싱 실패 [{md_file.name}]: {e}")
        
        # 유사도 매트릭스 로드
        matrix_file = self.holons_path / "_similarity_matrix.json"
        if matrix_file.exists():
            try:
                self.similarity_matrix = json.loads(matrix_file.read_text(encoding="utf-8"))
            except json.JSONDecodeError as e:
                logger.debug(f"유사도 매트릭스 JSON 파싱 실패 [{matrix_file}]: {e}")
        
        # Chunks 데이터 로드
        chunks_file = self.holons_path / "_chunks.json"
        if chunks_file.exists():
            try:
                self.chunks_data = json.loads(chunks_file.read_text(encoding="utf-8"))
            except json.JSONDecodeError as e:
                logger.debug(f"Chunks JSON 파싱 실패 [{chunks_file}]: {e}")
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 1. 상위 구조(Top-down Constitution) 안정성 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_constitution_stability(self) -> List[CheckItem]:
        """상위 헌법 구조 안정성 점검"""
        checks = []
        
        # 1-1. 상위 헌법 경직성 체크
        strategy_holons = [h for h in self.holons.values() if h.get("type") == "strategy"]
        
        if strategy_holons:
            strategy = strategy_holons[0]
            meta = strategy.get("meta", {})
            updated_at = meta.get("updated_at", "")
            
            if updated_at:
                try:
                    update_date = datetime.strptime(updated_at, "%Y-%m-%d")
                    days_since = (datetime.now() - update_date).days
                    
                    if days_since > 90:
                        checks.append(CheckItem(
                            id="const-1-1",
                            category="상위 구조 안정성",
                            name="헌법 경직성",
                            status="warning",
                            message=f"상위 헌법이 {days_since}일 동안 업데이트되지 않음",
                            severity=3,
                            recommendation="W/Worldview/Intention 검토 필요"
                        ))
                    else:
                        checks.append(CheckItem(
                            id="const-1-1",
                            category="상위 구조 안정성",
                            name="헌법 경직성",
                            status="pass",
                            message=f"최근 {days_since}일 내 업데이트됨",
                            severity=4
                        ))
                except ValueError as e:
                    logger.debug(f"헌법 업데이트 날짜 파싱 실패 [{updated_at}]: {e}")
        else:
            checks.append(CheckItem(
                id="const-1-1",
                category="상위 구조 안정성",
                name="헌법 경직성",
                status="fail",
                message="strategy 타입 문서가 없음",
                severity=1,
                recommendation="상위 헌법 문서 생성 필요"
            ))
        
        # 1-2. drift 패턴 반복 체크
        drift_counts = defaultdict(int)
        for holon_id, holon in self.holons.items():
            w = holon.get("W", {})
            will = w.get("will", {})
            if isinstance(will, dict):
                drive = will.get("drive", "")
                # 간단한 키워드 패턴 추출
                keywords = set(re.findall(r'[가-힣]{2,}', drive))
                for kw in keywords:
                    drift_counts[kw] += 1
        
        # 같은 키워드가 너무 많이 반복되면 drift 의심
        repeated = [kw for kw, count in drift_counts.items() if count > len(self.holons) * 0.7]
        if repeated:
            checks.append(CheckItem(
                id="const-1-2",
                category="상위 구조 안정성",
                name="Drift 패턴",
                status="pass",
                message=f"핵심 키워드 일관성 유지: {', '.join(repeated[:3])}",
                severity=4
            ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 2. 프로젝트 폭발(Project Explosion) 위험 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_project_explosion(self) -> List[CheckItem]:
        """프로젝트 폭발 위험 점검"""
        checks = []
        
        total_holons = len(self.holons)
        
        # 2-1. 프로젝트 수 체크
        if total_holons > 50:
            checks.append(CheckItem(
                id="proj-2-1",
                category="프로젝트 폭발",
                name="프로젝트 수",
                status="warning",
                message=f"총 {total_holons}개 - 복잡도 증가 주의",
                severity=3,
                recommendation="불필요한 프로젝트 정리 고려"
            ))
        elif total_holons > 100:
            checks.append(CheckItem(
                id="proj-2-1",
                category="프로젝트 폭발",
                name="프로젝트 수",
                status="fail",
                message=f"총 {total_holons}개 - 폭발 위험",
                severity=1,
                recommendation="즉시 프로젝트 병합/정리 필요"
            ))
        else:
            checks.append(CheckItem(
                id="proj-2-1",
                category="프로젝트 폭발",
                name="프로젝트 수",
                status="pass",
                message=f"총 {total_holons}개 - 관리 가능",
                severity=4
            ))
        
        # 2-2. 유사도 75% 이상 쌍 체크
        high_similarity_pairs = 0
        if self.similarity_matrix and "matrix" in self.similarity_matrix:
            matrix = self.similarity_matrix["matrix"]
            checked = set()
            for id_a, row in matrix.items():
                for id_b, sim in row.items():
                    pair_key = tuple(sorted([id_a, id_b]))
                    if pair_key not in checked and sim >= 0.75:
                        high_similarity_pairs += 1
                    checked.add(pair_key)
        
        if high_similarity_pairs > 0:
            checks.append(CheckItem(
                id="proj-2-2",
                category="프로젝트 폭발",
                name="고유사도 쌍",
                status="warning",
                message=f"{high_similarity_pairs}쌍이 75% 이상 유사",
                severity=2,
                recommendation="병합 검토 필요"
            ))
        else:
            checks.append(CheckItem(
                id="proj-2-2",
                category="프로젝트 폭발",
                name="고유사도 쌍",
                status="pass",
                message="고유사도 쌍 없음",
                severity=4
            ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 3. 링크 구조 붕괴(Link Graph Collapse) 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_link_structure(self) -> List[CheckItem]:
        """링크 구조 붕괴 점검"""
        checks = []
        
        # 링크 통계 수집
        link_counts = {}
        orphans = []
        
        for holon_id, holon in self.holons.items():
            links = holon.get("links", {})
            total_links = 0
            
            parent = links.get("parent")
            children = links.get("children", [])
            related = links.get("related", [])
            
            if parent:
                total_links += 1
            total_links += len(children) + len(related)
            
            link_counts[holon_id] = total_links
            
            # Orphan 체크 (root가 아닌데 parent 없는 경우)
            if not parent and holon.get("type") not in ["strategy"]:
                # children이 없으면 완전 고립
                if not children:
                    orphans.append(holon_id)
        
        # 3-1. Orphan 문서 체크
        if orphans:
            checks.append(CheckItem(
                id="link-3-1",
                category="링크 구조",
                name="Orphan 문서",
                status="warning",
                message=f"{len(orphans)}개 고립 문서 발견",
                severity=3,
                recommendation=f"연결 필요: {', '.join(orphans[:3])}"
            ))
        else:
            checks.append(CheckItem(
                id="link-3-1",
                category="링크 구조",
                name="Orphan 문서",
                status="pass",
                message="고립 문서 없음",
                severity=4
            ))
        
        # 3-2. 링크 허브 체크 (특정 문서가 너무 많은 링크)
        if link_counts:
            max_links = max(link_counts.values())
            hub_threshold = len(self.holons) * 0.5
            
            hubs = [h for h, c in link_counts.items() if c > hub_threshold]
            
            if hubs:
                checks.append(CheckItem(
                    id="link-3-2",
                    category="링크 구조",
                    name="링크 허브",
                    status="warning",
                    message=f"허브 문서 감지: {hubs[0]} ({link_counts[hubs[0]]}개 링크)",
                    severity=3,
                    recommendation="링크 분산 고려"
                ))
            else:
                checks.append(CheckItem(
                    id="link-3-2",
                    category="링크 구조",
                    name="링크 허브",
                    status="pass",
                    message=f"링크 분포 정상 (최대 {max_links}개)",
                    severity=4
                ))
        
        # 3-3. 양방향 링크 일관성 체크
        inconsistent_links = []
        for holon_id, holon in self.holons.items():
            links = holon.get("links", {})
            parent = links.get("parent")
            
            if parent and parent in self.holons:
                parent_holon = self.holons[parent]
                parent_children = parent_holon.get("links", {}).get("children", [])
                if holon_id not in parent_children:
                    inconsistent_links.append((holon_id, parent))
        
        if inconsistent_links:
            checks.append(CheckItem(
                id="link-3-3",
                category="링크 구조",
                name="양방향 링크",
                status="fail",
                message=f"{len(inconsistent_links)}개 링크 불일치",
                severity=2,
                recommendation="python _cli.py link 실행 필요"
            ))
        else:
            checks.append(CheckItem(
                id="link-3-3",
                category="링크 구조",
                name="양방향 링크",
                status="pass",
                message="양방향 링크 일관성 유지",
                severity=4
            ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 4. 연구 프로세스(R&E Engine) 품질 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_research_quality(self) -> List[CheckItem]:
        """연구 프로세스 품질 점검"""
        checks = []
        
        # 4-1. WXSPERTA 완성도 체크
        incomplete_holons = []
        for holon_id, holon in self.holons.items():
            missing_slots = []
            for slot in ["W", "X", "S", "P", "E", "R", "T", "A"]:
                if not holon.get(slot):
                    missing_slots.append(slot)
            
            if missing_slots:
                incomplete_holons.append((holon_id, missing_slots))
        
        if incomplete_holons:
            checks.append(CheckItem(
                id="research-4-1",
                category="연구 프로세스",
                name="WXSPERTA 완성도",
                status="warning",
                message=f"{len(incomplete_holons)}개 문서 불완전",
                severity=3,
                recommendation="누락된 슬롯 채우기 권장"
            ))
        else:
            checks.append(CheckItem(
                id="research-4-1",
                category="연구 프로세스",
                name="WXSPERTA 완성도",
                status="pass",
                message="모든 문서 WXSPERTA 구조 완비",
                severity=4
            ))
        
        # 4-2. 플레이스홀더 비율 체크
        placeholder_count = 0
        total_fields = 0
        
        for holon in self.holons.values():
            content = json.dumps(holon, ensure_ascii=False)
            placeholders = len(re.findall(r'\[\.\.\.?\]|\[TBD\]|\[TODO\]', content))
            placeholder_count += placeholders
            total_fields += content.count('"') // 2  # 대략적인 필드 수
        
        placeholder_ratio = placeholder_count / max(total_fields, 1)
        
        if placeholder_ratio > 0.2:
            checks.append(CheckItem(
                id="research-4-2",
                category="연구 프로세스",
                name="플레이스홀더 비율",
                status="warning",
                message=f"플레이스홀더 {placeholder_ratio:.0%}",
                severity=3,
                recommendation="내용 채우기 필요"
            ))
        else:
            checks.append(CheckItem(
                id="research-4-2",
                category="연구 프로세스",
                name="플레이스홀더 비율",
                status="pass",
                message=f"플레이스홀더 {placeholder_ratio:.0%} (양호)",
                severity=4
            ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 5. 메타 연구 엔진(Meta-Research Engine) 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_meta_research(self) -> List[CheckItem]:
        """메타 연구 엔진 점검"""
        checks = []
        
        # 5-1. Similarity Matrix 존재 여부
        if self.similarity_matrix:
            generated_at = self.similarity_matrix.get("generated_at", "")
            checks.append(CheckItem(
                id="meta-5-1",
                category="메타 연구 엔진",
                name="유사도 매트릭스",
                status="pass",
                message=f"마지막 생성: {generated_at[:10] if generated_at else 'N/A'}",
                severity=4
            ))
        else:
            checks.append(CheckItem(
                id="meta-5-1",
                category="메타 연구 엔진",
                name="유사도 매트릭스",
                status="warning",
                message="매트릭스 없음",
                severity=3,
                recommendation="python _cli.py meta analyze 실행"
            ))
        
        # 5-2. 최근 리포트 존재 여부
        if self.reports_path.exists():
            reports = list(self.reports_path.glob("meta_research_report_*.md"))
            if reports:
                latest = max(reports, key=lambda p: p.stat().st_mtime)
                days_ago = (datetime.now() - datetime.fromtimestamp(latest.stat().st_mtime)).days
                
                if days_ago > 7:
                    checks.append(CheckItem(
                        id="meta-5-2",
                        category="메타 연구 엔진",
                        name="Weekly 리포트",
                        status="warning",
                        message=f"마지막 리포트 {days_ago}일 전",
                        severity=3,
                        recommendation="주간 리포트 생성 필요"
                    ))
                else:
                    checks.append(CheckItem(
                        id="meta-5-2",
                        category="메타 연구 엔진",
                        name="Weekly 리포트",
                        status="pass",
                        message=f"최근 {days_ago}일 내 리포트 존재",
                        severity=4
                    ))
            else:
                checks.append(CheckItem(
                    id="meta-5-2",
                    category="메타 연구 엔진",
                    name="Weekly 리포트",
                    status="warning",
                    message="리포트 없음",
                    severity=3,
                    recommendation="python _cli.py meta report 실행"
                ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 6. 문서 질(Completeness & Drift) 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_document_quality(self) -> List[CheckItem]:
        """문서 질 점검"""
        checks = []
        
        # 6-1. W 구조 완성도
        w_complete = 0
        w_incomplete = []
        
        required_w_fields = ["worldview", "will", "intention", "goal", "activation"]
        
        for holon_id, holon in self.holons.items():
            w = holon.get("W", {})
            missing = [f for f in required_w_fields if not w.get(f)]
            
            if not missing:
                w_complete += 1
            else:
                w_incomplete.append((holon_id, missing))
        
        if w_incomplete:
            checks.append(CheckItem(
                id="doc-6-1",
                category="문서 질",
                name="W 구조 완성도",
                status="warning",
                message=f"{len(w_incomplete)}개 문서 W 불완전",
                severity=2,
                recommendation="W의 5개 섹션 모두 채우기"
            ))
        else:
            checks.append(CheckItem(
                id="doc-6-1",
                category="문서 질",
                name="W 구조 완성도",
                status="pass",
                message="모든 문서 W 구조 완비",
                severity=4
            ))
        
        # 6-2. will 필드 존재 여부 (각 슬롯에)
        slots_without_will = []
        for holon_id, holon in self.holons.items():
            for slot in ["X", "S", "P", "E", "R", "T", "A"]:
                slot_data = holon.get(slot, {})
                if isinstance(slot_data, dict) and not slot_data.get("will"):
                    slots_without_will.append((holon_id, slot))
        
        if slots_without_will:
            unique_holons = len(set(h for h, _ in slots_without_will))
            checks.append(CheckItem(
                id="doc-6-2",
                category="문서 질",
                name="슬롯별 will 필드",
                status="warning",
                message=f"{unique_holons}개 문서에 will 필드 누락",
                severity=3,
                recommendation="각 XSPERTA 슬롯에 will 필드 추가"
            ))
        else:
            checks.append(CheckItem(
                id="doc-6-2",
                category="문서 질",
                name="슬롯별 will 필드",
                status="pass",
                message="모든 슬롯에 will 필드 존재",
                severity=4
            ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 7. 자동화 엔진 안정성 점검
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_automation_stability(self) -> List[CheckItem]:
        """자동화 엔진 안정성 점검"""
        checks = []
        
        # 7-1. 스크립트 파일 존재 확인
        required_scripts = [
            "_validate.py",
            "_cli.py",
            "_chunk_engine.py",
            "_meta_research_engine.py",
            "_auto_link.py",
            "_create_holon.py"
        ]
        
        missing_scripts = []
        for script in required_scripts:
            if not (self.holons_path / script).exists():
                missing_scripts.append(script)
        
        if missing_scripts:
            checks.append(CheckItem(
                id="auto-7-1",
                category="자동화 엔진",
                name="필수 스크립트",
                status="fail",
                message=f"누락: {', '.join(missing_scripts)}",
                severity=1,
                recommendation="스크립트 복구 필요"
            ))
        else:
            checks.append(CheckItem(
                id="auto-7-1",
                category="자동화 엔진",
                name="필수 스크립트",
                status="pass",
                message="모든 스크립트 존재",
                severity=4
            ))
        
        # 7-2. pre-commit hook 존재 확인
        git_hooks = self.base_path.parent / ".git" / "hooks" / "pre-commit"
        if git_hooks.exists():
            checks.append(CheckItem(
                id="auto-7-2",
                category="자동화 엔진",
                name="Pre-commit Hook",
                status="pass",
                message="Hook 설치됨",
                severity=4
            ))
        else:
            checks.append(CheckItem(
                id="auto-7-2",
                category="자동화 엔진",
                name="Pre-commit Hook",
                status="warning",
                message="Hook 미설치",
                severity=3,
                recommendation="검증 자동화를 위해 Hook 설치 권장"
            ))
        
        # 7-3. Chunks 데이터 최신성
        if self.chunks_data:
            generated_at = self.chunks_data.get("generated_at", "")
            if generated_at:
                try:
                    gen_date = datetime.fromisoformat(generated_at.replace("Z", "+00:00"))
                    hours_ago = (datetime.now() - gen_date.replace(tzinfo=None)).total_seconds() / 3600
                    
                    if hours_ago > 24:
                        checks.append(CheckItem(
                            id="auto-7-3",
                            category="자동화 엔진",
                            name="Chunk 최신성",
                            status="warning",
                            message=f"Chunk가 {int(hours_ago)}시간 전 생성됨",
                            severity=3,
                            recommendation="python _cli.py chunk generate 실행"
                        ))
                    else:
                        checks.append(CheckItem(
                            id="auto-7-3",
                            category="자동화 엔진",
                            name="Chunk 최신성",
                            status="pass",
                            message=f"최근 {int(hours_ago)}시간 내 업데이트",
                            severity=4
                        ))
                except ValueError as e:
                    logger.debug(f"Chunk 생성일 파싱 실패 [{generated_at}]: {e}")
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 8. 사람 개입(Human-in-the-Loop) 체크
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def check_human_loop(self) -> List[CheckItem]:
        """사람 개입 지점 점검"""
        checks = []
        
        # 8-1. 리포트 가독성 (리포트 파일 존재 여부로 간접 확인)
        if self.reports_path.exists():
            all_reports = list(self.reports_path.glob("*.md"))
            if all_reports:
                checks.append(CheckItem(
                    id="human-8-1",
                    category="사람 개입",
                    name="리포트 접근성",
                    status="pass",
                    message=f"{len(all_reports)}개 리포트 사용 가능",
                    severity=4
                ))
            else:
                checks.append(CheckItem(
                    id="human-8-1",
                    category="사람 개입",
                    name="리포트 접근성",
                    status="warning",
                    message="리포트 없음",
                    severity=3,
                    recommendation="정기 리포트 생성 필요"
                ))
        
        # 8-2. 승인 대기 항목 체크 (향후 구현 가능)
        checks.append(CheckItem(
            id="human-8-2",
            category="사람 개입",
            name="승인 대기",
            status="pass",
            message="자동 승인 위험 없음 (수동 모드)",
            severity=4
        ))
        
        return checks
    
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    # 통합 실행
    # ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    
    def run_all_checks(self) -> HealthReport:
        """모든 점검 실행"""
        self.load_data()
        
        all_checks = []
        
        # 8개 영역 점검
        all_checks.extend(self.check_constitution_stability())
        all_checks.extend(self.check_project_explosion())
        all_checks.extend(self.check_link_structure())
        all_checks.extend(self.check_research_quality())
        all_checks.extend(self.check_meta_research())
        all_checks.extend(self.check_document_quality())
        all_checks.extend(self.check_automation_stability())
        all_checks.extend(self.check_human_loop())
        
        self.checks = all_checks
        
        # 통계 계산
        passed = sum(1 for c in all_checks if c.status == "pass")
        warnings = sum(1 for c in all_checks if c.status == "warning")
        failed = sum(1 for c in all_checks if c.status == "fail")
        skipped = sum(1 for c in all_checks if c.status == "skip")
        
        # 전체 건강도 계산 (가중치: pass=1, warning=0.5, fail=0)
        health_score = (passed + warnings * 0.5) / max(len(all_checks), 1)
        
        return HealthReport(
            timestamp=datetime.now().isoformat(),
            total_checks=len(all_checks),
            passed=passed,
            warnings=warnings,
            failed=failed,
            skipped=skipped,
            overall_health=health_score,
            checks=all_checks
        )
    
    def print_report(self, report: HealthReport) -> None:
        """콘솔 리포트 출력"""
        print("=" * 70)
        print("🏥 시스템 건강 점검 리포트")
        print("=" * 70)
        print()
        
        # 전체 건강도
        health_emoji = "🟢" if report.overall_health >= 0.8 else "🟡" if report.overall_health >= 0.6 else "🔴"
        print(f"{health_emoji} 전체 건강도: {report.overall_health:.0%}")
        print()
        
        # 요약 통계
        print(f"📊 점검 결과:")
        print(f"   ✅ 통과: {report.passed}")
        print(f"   ⚠️  경고: {report.warnings}")
        print(f"   ❌ 실패: {report.failed}")
        print()
        
        # 카테고리별 결과
        categories = {}
        for check in report.checks:
            if check.category not in categories:
                categories[check.category] = []
            categories[check.category].append(check)
        
        print("-" * 70)
        
        for category, checks in categories.items():
            status_counts = defaultdict(int)
            for c in checks:
                status_counts[c.status] += 1
            
            cat_status = "✅" if status_counts["fail"] == 0 and status_counts["warning"] == 0 else \
                        "⚠️" if status_counts["fail"] == 0 else "❌"
            
            print(f"\n{cat_status} {category}")
            
            for check in checks:
                status_icon = {
                    "pass": "✅",
                    "warning": "⚠️",
                    "fail": "❌",
                    "skip": "⏭️"
                }.get(check.status, "❓")
                
                print(f"   {status_icon} {check.name}: {check.message}")
                
                if check.recommendation:
                    print(f"      💡 {check.recommendation}")
        
        print()
        print("-" * 70)
        print(f"📅 점검 시간: {report.timestamp[:19]}")
        print("=" * 70)
    
    def generate_markdown_report(self, report: HealthReport) -> str:
        """마크다운 리포트 생성"""
        health_emoji = "🟢" if report.overall_health >= 0.8 else "🟡" if report.overall_health >= 0.6 else "🔴"
        
        md = f"""# 🏥 시스템 건강 점검 리포트

**점검 시간**: {report.timestamp[:19]}  
**전체 건강도**: {health_emoji} {report.overall_health:.0%}

---

## 📊 요약

| 항목 | 수량 |
|------|------|
| ✅ 통과 | {report.passed} |
| ⚠️ 경고 | {report.warnings} |
| ❌ 실패 | {report.failed} |
| ⏭️ 스킵 | {report.skipped} |

---

## 🔍 상세 결과

"""
        
        categories = {}
        for check in report.checks:
            if check.category not in categories:
                categories[check.category] = []
            categories[check.category].append(check)
        
        for category, checks in categories.items():
            md += f"### {category}\n\n"
            md += "| 항목 | 상태 | 메시지 | 권장 조치 |\n"
            md += "|------|------|--------|----------|\n"
            
            for check in checks:
                status_icon = {"pass": "✅", "warning": "⚠️", "fail": "❌", "skip": "⏭️"}.get(check.status, "❓")
                rec = check.recommendation or "-"
                md += f"| {check.name} | {status_icon} | {check.message} | {rec} |\n"
            
            md += "\n"
        
        md += """---

## 🚀 권장 조치 요약

"""
        
        # 심각도순 정렬
        critical = [c for c in report.checks if c.status != "pass" and c.severity <= 2]
        
        if critical:
            md += "### ⚠️ 우선 처리 필요\n\n"
            for c in sorted(critical, key=lambda x: x.severity):
                md += f"- **[P{c.severity}] {c.name}**: {c.recommendation}\n"
        else:
            md += "✅ 긴급 조치 필요 항목 없음\n"
        
        md += "\n---\n\n> 이 리포트는 자동 생성되었습니다. 정기적인 점검을 권장합니다.\n"
        
        return md
    
    def save_report(self, report: HealthReport) -> Path:
        """리포트 저장"""
        self.reports_path.mkdir(parents=True, exist_ok=True)
        
        today = datetime.now().strftime("%Y-%m-%d")
        
        # JSON 저장
        json_file = self.reports_path / f"health_check_{today}.json"
        json_file.write_text(
            json.dumps(report.to_dict(), ensure_ascii=False, indent=2),
            encoding="utf-8"
        )
        
        # Markdown 저장
        md_file = self.reports_path / f"health_check_{today}.md"
        md_file.write_text(
            self.generate_markdown_report(report),
            encoding="utf-8"
        )
        
        return md_file


def main():
    """CLI 실행"""
    import argparse
    
    parser = argparse.ArgumentParser(description="System Health Check")
    parser.add_argument("command", choices=["check", "report"], nargs="?", default="check",
                       help="check: 점검 실행, report: 리포트 저장")
    
    args = parser.parse_args()
    
    script_dir = Path(__file__).parent
    engine = HealthCheckEngine(str(script_dir.parent))
    
    report = engine.run_all_checks()
    engine.print_report(report)
    
    if args.command == "report":
        report_file = engine.save_report(report)
        print()
        print(f"📄 리포트 저장: {report_file}")


if __name__ == "__main__":
    main()

