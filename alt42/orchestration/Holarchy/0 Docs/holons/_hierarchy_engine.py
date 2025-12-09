#!/usr/bin/env python3
"""
🏛️ Holonic Hierarchy Engine v1.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

수직적 위계질서 관리 시스템

핵심 원칙:
1. 형제 홀론 제외 - 완벽한 수직적 위계질서
2. 상위 → 하위: Directive (지시/미션 전파)
3. 하위 → 상위: Signal (정보/피드백 전달)
4. 상위 홀론은 하위 홀론 정보에 유연하게 반응

정보 흐름:
  ┌─────────────────────────────────────────┐
  │          ROOT (최상위 홀론)              │
  │    ┌──────────────────────────────┐    │
  │    │         Directive            │    │
  │    │      (미션/지시/정책)         │    │
  │    │           ↓↓↓               │    │
  │    └──────────────────────────────┘    │
  │                  ↓                      │
  │    ┌──────────────────────────────┐    │
  │    │          Signal              │    │
  │    │    (피드백/상태/결과)          │    │
  │    │           ↑↑↑               │    │
  │    └──────────────────────────────┘    │
  │                  ↑                      │
  │           하위 홀론들                    │
  └─────────────────────────────────────────┘
"""

import json
import re
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple, Set, Any
from dataclasses import dataclass, field
from enum import Enum

# 로깅 설정
logger = logging.getLogger("holarchy.hierarchy_engine")


class FlowDirection(Enum):
    """정보 흐름 방향"""
    DOWNWARD = "downward"   # 상위 → 하위 (Directive)
    UPWARD = "upward"       # 하위 → 상위 (Signal)


class DirectiveType(Enum):
    """상위→하위 지시 유형"""
    MISSION = "mission"           # 미션 전파
    POLICY = "policy"             # 정책 전달
    PRIORITY = "priority"         # 우선순위 지시
    CONSTRAINT = "constraint"     # 제약조건 전달
    ACTIVATION = "activation"     # 활성화 트리거


class SignalType(Enum):
    """하위→상위 신호 유형"""
    STATUS = "status"             # 현재 상태
    PROGRESS = "progress"         # 진행률
    ISSUE = "issue"               # 문제/이슈
    FEEDBACK = "feedback"         # 피드백
    REQUEST = "request"           # 요청 (리소스 등)
    INSIGHT = "insight"           # 통찰/발견


@dataclass
class Directive:
    """상위 → 하위 지시"""
    directive_id: str
    source_holon: str             # 상위 홀론 ID
    target_holons: List[str]      # 하위 홀론 ID들
    directive_type: DirectiveType
    content: str
    priority: int = 1             # 1(최고) ~ 5(최저)
    created_at: str = ""
    expires_at: Optional[str] = None
    acknowledged_by: List[str] = field(default_factory=list)


@dataclass
class Signal:
    """하위 → 상위 신호"""
    signal_id: str
    source_holon: str             # 하위 홀론 ID
    target_holon: str             # 상위 홀론 ID
    signal_type: SignalType
    content: str
    severity: int = 3             # 1(긴급) ~ 5(일반)
    created_at: str = ""
    processed: bool = False
    response: Optional[str] = None


@dataclass
class HolonNode:
    """위계질서에서의 홀론 노드"""
    holon_id: str
    filename: str
    filepath: str
    title: str
    parent_id: Optional[str]
    children: List[str]
    siblings: List[str]           # 형제 홀론들
    depth: int = 0
    
    # W.will.drive (미션)
    drive: str = ""
    
    # 현재 상태
    status: str = "active"
    health_score: float = 1.0     # 0~1
    
    # 최근 신호들
    pending_signals: List[Signal] = field(default_factory=list)
    
    # 받은 지시들
    active_directives: List[Directive] = field(default_factory=list)


class HierarchyEngine:
    """수직적 위계질서 엔진"""
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.reports_path = self.docs_root / "reports"
        
        # 홀론 트리
        self.nodes: Dict[str, HolonNode] = {}
        
        # 지시/신호 큐
        self.directives: List[Directive] = []
        self.signals: List[Signal] = []
        
        self.reports_path.mkdir(parents=True, exist_ok=True)
    
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
    
    def load_hierarchy(self) -> Dict[str, HolonNode]:
        """전체 위계질서 로드"""
        self.nodes = {}
        
        # 1. 모든 문서 로드
        all_files = list(self.holons_path.glob("*.md"))
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                all_files.extend(folder_path.glob("*.md"))
        
        # 2. 노드 생성
        for filepath in all_files:
            if filepath.name.startswith("_"):
                continue
            
            holon = self._parse_holon(filepath)
            if not holon:
                continue
            
            holon_id = holon.get("holon_id", filepath.stem)
            links = holon.get("links", {})
            
            node = HolonNode(
                holon_id=holon_id,
                filename=filepath.name,
                filepath=str(filepath),
                title=holon.get("meta", {}).get("title", filepath.stem),
                parent_id=links.get("parent"),
                children=links.get("children", []),
                siblings=links.get("siblings", []),  # 형제 홀론
                drive=holon.get("W", {}).get("will", {}).get("drive", ""),
                status=holon.get("meta", {}).get("status", "active")
            )
            
            self.nodes[holon_id] = node
        
        # 3. 깊이 계산 및 형제 관계 자동 설정
        self._calculate_depths()
        self._auto_set_siblings()
        
        return self.nodes
    
    def _calculate_depths(self):
        """각 노드의 깊이 계산"""
        # ROOT 찾기
        roots = [nid for nid, node in self.nodes.items() 
                if node.parent_id is None or node.parent_id not in self.nodes]
        
        # BFS로 깊이 계산
        visited = set()
        queue = [(rid, 0) for rid in roots]
        
        while queue:
            node_id, depth = queue.pop(0)
            if node_id in visited or node_id not in self.nodes:
                continue
            
            visited.add(node_id)
            self.nodes[node_id].depth = depth
            
            # 자식 노드
            for child_id in self.nodes[node_id].children:
                if child_id in self.nodes:
                    queue.append((child_id, depth + 1))
            
            # parent로 연결된 노드도 확인
            for other_id, other_node in self.nodes.items():
                if other_node.parent_id == node_id and other_id not in visited:
                    queue.append((other_id, depth + 1))
    
    def _auto_set_siblings(self):
        """형제 관계 자동 설정 (같은 parent를 가진 홀론들)"""
        # parent별로 자식들 그룹화
        children_by_parent: Dict[str, List[str]] = {}
        
        for node_id, node in self.nodes.items():
            parent = node.parent_id or "__ROOT__"
            if parent not in children_by_parent:
                children_by_parent[parent] = []
            children_by_parent[parent].append(node_id)
        
        # 형제 설정
        for parent, siblings in children_by_parent.items():
            if len(siblings) <= 1:
                continue
            
            for node_id in siblings:
                if node_id in self.nodes:
                    # 자기 자신 제외한 형제들
                    self.nodes[node_id].siblings = [s for s in siblings if s != node_id]
    
    # ═══════════════════════════════════════════════════════════════
    # 상위 → 하위: Directive (지시)
    # ═══════════════════════════════════════════════════════════════
    
    def send_directive(
        self,
        source_holon: str,
        directive_type: DirectiveType,
        content: str,
        target_holons: List[str] = None,
        priority: int = 2,
        cascade: bool = True
    ) -> Directive:
        """
        상위 홀론에서 하위 홀론으로 지시 전송
        
        cascade=True: 하위의 하위까지 전파
        """
        if source_holon not in self.nodes:
            raise ValueError(f"소스 홀론 없음: {source_holon}")
        
        source_node = self.nodes[source_holon]
        
        # 대상 결정
        if target_holons is None:
            # 기본: 직계 자식들
            target_holons = source_node.children.copy()
        
        # cascade면 하위의 하위도 추가
        if cascade:
            all_targets = set(target_holons)
            queue = list(target_holons)
            
            while queue:
                tid = queue.pop(0)
                if tid in self.nodes:
                    for child in self.nodes[tid].children:
                        if child not in all_targets:
                            all_targets.add(child)
                            queue.append(child)
            
            target_holons = list(all_targets)
        
        directive = Directive(
            directive_id=f"dir-{datetime.now().strftime('%Y%m%d%H%M%S')}",
            source_holon=source_holon,
            target_holons=target_holons,
            directive_type=directive_type,
            content=content,
            priority=priority,
            created_at=datetime.now().isoformat()
        )
        
        self.directives.append(directive)
        
        # 각 대상 노드에 지시 추가
        for tid in target_holons:
            if tid in self.nodes:
                self.nodes[tid].active_directives.append(directive)
        
        return directive
    
    def propagate_mission(self, from_holon: str = None) -> Dict:
        """
        미션을 수직적으로 하위에 전파
        
        상위 홀론의 W.will.drive를 기반으로 하위 홀론들의 미션을 자동 조정
        """
        self.load_hierarchy()
        
        result = {
            "propagated": [],
            "unchanged": [],
            "source": from_holon or "hte-doc-000"
        }
        
        # 시작점 결정
        if from_holon and from_holon in self.nodes:
            start_node = self.nodes[from_holon]
        else:
            # ROOT 찾기
            roots = [nid for nid, node in self.nodes.items() if node.parent_id is None]
            if not roots:
                return result
            start_node = self.nodes[roots[0]]
        
        # BFS로 미션 전파
        queue = [(start_node.holon_id, start_node.drive)]
        visited = set()
        
        while queue:
            node_id, parent_drive = queue.pop(0)
            if node_id in visited:
                continue
            
            visited.add(node_id)
            node = self.nodes[node_id]
            
            # 자식들에게 전파
            for child_id in node.children:
                if child_id in self.nodes:
                    child = self.nodes[child_id]
                    
                    # 하위 미션 생성 (상위 미션 기반)
                    propagated_drive = self._generate_child_drive(parent_drive, child.title)
                    
                    result["propagated"].append({
                        "holon_id": child_id,
                        "parent_drive": parent_drive[:50] + "...",
                        "child_drive": propagated_drive[:50] + "..."
                    })
                    
                    queue.append((child_id, propagated_drive))
        
        return result
    
    def _generate_child_drive(self, parent_drive: str, child_title: str) -> str:
        """상위 미션 기반 하위 미션 생성"""
        keywords = []
        for kw in ["전국", "수학", "학원", "자동화", "시장", "독점", "AI", "교육"]:
            if kw in parent_drive:
                keywords.append(kw)
        
        keywords_str = " ".join(keywords[:4]) if keywords else "핵심 목표"
        return f"{keywords_str}을 위해 {child_title} 영역을 완벽히 구축한다"
    
    # ═══════════════════════════════════════════════════════════════
    # 하위 → 상위: Signal (신호)
    # ═══════════════════════════════════════════════════════════════
    
    def send_signal(
        self,
        source_holon: str,
        signal_type: SignalType,
        content: str,
        severity: int = 3,
        bubble_up: bool = True
    ) -> Signal:
        """
        하위 홀론에서 상위 홀론으로 신호 전송
        
        bubble_up=True: ROOT까지 전파
        """
        if source_holon not in self.nodes:
            raise ValueError(f"소스 홀론 없음: {source_holon}")
        
        source_node = self.nodes[source_holon]
        target_holon = source_node.parent_id
        
        if not target_holon:
            # ROOT인 경우 자체 처리
            target_holon = source_holon
        
        signal = Signal(
            signal_id=f"sig-{datetime.now().strftime('%Y%m%d%H%M%S')}",
            source_holon=source_holon,
            target_holon=target_holon,
            signal_type=signal_type,
            content=content,
            severity=severity,
            created_at=datetime.now().isoformat()
        )
        
        self.signals.append(signal)
        
        # 대상 노드에 신호 추가
        if target_holon in self.nodes:
            self.nodes[target_holon].pending_signals.append(signal)
        
        # bubble_up이면 ROOT까지 전파
        if bubble_up:
            current = target_holon
            while current and current in self.nodes:
                parent = self.nodes[current].parent_id
                if parent and parent in self.nodes:
                    # 중요도가 높은 신호만 상위로 전파
                    if severity <= 2:  # 긴급/중요
                        bubbled_signal = Signal(
                            signal_id=f"{signal.signal_id}-bubble",
                            source_holon=source_holon,
                            target_holon=parent,
                            signal_type=signal_type,
                            content=f"[Bubbled from {source_holon}] {content}",
                            severity=severity,
                            created_at=datetime.now().isoformat()
                        )
                        self.nodes[parent].pending_signals.append(bubbled_signal)
                current = parent
        
        return signal
    
    def aggregate_signals(self, holon_id: str) -> Dict:
        """
        상위 홀론이 하위 신호들을 집계하여 분석
        
        상위 홀론이 하위 정보에 유연하게 반응하기 위한 핵심 메서드
        """
        if holon_id not in self.nodes:
            return {}
        
        node = self.nodes[holon_id]
        
        # 직계 자식들의 신호 수집
        all_signals = []
        for child_id in node.children:
            if child_id in self.nodes:
                child = self.nodes[child_id]
                all_signals.extend(child.pending_signals)
        
        # 신호 분석
        result = {
            "holon_id": holon_id,
            "total_signals": len(all_signals),
            "by_type": {},
            "by_severity": {},
            "urgent_signals": [],
            "recommendations": []
        }
        
        for signal in all_signals:
            # 타입별 분류
            stype = signal.signal_type.value
            if stype not in result["by_type"]:
                result["by_type"][stype] = []
            result["by_type"][stype].append({
                "from": signal.source_holon,
                "content": signal.content[:100],
                "severity": signal.severity
            })
            
            # 심각도별 분류
            sev = str(signal.severity)
            result["by_severity"][sev] = result["by_severity"].get(sev, 0) + 1
            
            # 긴급 신호 추출
            if signal.severity <= 2:
                result["urgent_signals"].append({
                    "from": signal.source_holon,
                    "type": stype,
                    "content": signal.content
                })
        
        # 자동 권고 생성
        if result["urgent_signals"]:
            result["recommendations"].append(
                f"긴급 신호 {len(result['urgent_signals'])}개 - 즉시 검토 필요"
            )
        
        issue_count = len(result["by_type"].get("issue", []))
        if issue_count >= 3:
            result["recommendations"].append(
                f"이슈 다수 발생 ({issue_count}건) - 패턴 분석 권장"
            )
        
        return result
    
    def respond_to_signal(
        self,
        signal_id: str,
        response: str,
        action: str = None
    ) -> bool:
        """
        상위 홀론이 하위 신호에 응답
        """
        for signal in self.signals:
            if signal.signal_id == signal_id:
                signal.processed = True
                signal.response = response
                
                # 응답 지시 생성 (필요시)
                if action:
                    self.send_directive(
                        source_holon=signal.target_holon,
                        directive_type=DirectiveType.POLICY,
                        content=f"[Response to {signal_id}] {action}",
                        target_holons=[signal.source_holon],
                        cascade=False
                    )
                
                return True
        
        return False
    
    # ═══════════════════════════════════════════════════════════════
    # 위계질서 시각화 및 리포트
    # ═══════════════════════════════════════════════════════════════
    
    def print_hierarchy_tree(self):
        """수직적 위계질서 트리 출력"""
        self.load_hierarchy()
        
        print("=" * 70)
        print("🏛️ Holonic Hierarchy (수직적 위계질서)")
        print("   ↓ Directive (상위→하위: 미션/정책)")
        print("   ↑ Signal (하위→상위: 정보/피드백)")
        print("=" * 70)
        print()
        
        # 깊이별 정렬
        nodes_by_depth = {}
        for node_id, node in self.nodes.items():
            if node.depth not in nodes_by_depth:
                nodes_by_depth[node.depth] = []
            nodes_by_depth[node.depth].append(node)
        
        for depth in sorted(nodes_by_depth.keys()):
            nodes = nodes_by_depth[depth]
            
            if depth == 0:
                print(f"📌 ROOT (최상위)")
            else:
                indent = "  " * depth
                print(f"{indent}📂 Depth {depth}")
            
            for node in nodes:
                indent = "  " * (depth + 1)
                
                # 상태 이모지
                status_emoji = "✅" if node.status == "active" else "⏸️"
                
                print(f"{indent}{status_emoji} {node.holon_id}")
                print(f"{indent}   📄 {node.filename}")
                
                if node.children:
                    print(f"{indent}   ↓ Children: {len(node.children)}개")
                
                if node.siblings:
                    print(f"{indent}   ↔ Siblings: {len(node.siblings)}개")
                
                if node.pending_signals:
                    print(f"{indent}   ⚡ Pending Signals: {len(node.pending_signals)}개")
                
                print()
        
        # 요약
        total = len(self.nodes)
        max_depth = max(nodes_by_depth.keys()) if nodes_by_depth else 0
        
        print("-" * 70)
        print(f"📊 요약:")
        print(f"   총 홀론: {total}개")
        print(f"   최대 깊이: {max_depth}")
        print(f"   대기 신호: {sum(len(n.pending_signals) for n in self.nodes.values())}개")
        print()
    
    def print_signal_flow(self):
        """신호 흐름 현황 출력"""
        self.load_hierarchy()
        
        print("=" * 70)
        print("⚡ Signal Flow (하위 → 상위 정보 흐름)")
        print("=" * 70)
        print()
        
        if not self.signals:
            print("📭 현재 신호 없음")
            return
        
        # 최근 신호 10개
        recent_signals = sorted(self.signals, key=lambda s: s.created_at, reverse=True)[:10]
        
        for signal in recent_signals:
            severity_emoji = {1: "🔴", 2: "🟠", 3: "🟡", 4: "🟢", 5: "⚪"}.get(signal.severity, "⚪")
            processed_emoji = "✅" if signal.processed else "⏳"
            
            print(f"{severity_emoji} {signal.signal_id} ({signal.signal_type.value})")
            print(f"   {signal.source_holon} → {signal.target_holon}")
            print(f"   {signal.content[:60]}...")
            print(f"   {processed_emoji} {'처리됨' if signal.processed else '대기 중'}")
            print()
    
    def save_hierarchy_report(self) -> Path:
        """위계질서 리포트 저장"""
        self.load_hierarchy()
        
        report = {
            "generated_at": datetime.now().isoformat(),
            "total_holons": len(self.nodes),
            "hierarchy": [],
            "signals": [],
            "directives": []
        }
        
        for node_id, node in self.nodes.items():
            report["hierarchy"].append({
                "holon_id": node_id,
                "filename": node.filename,
                "depth": node.depth,
                "parent": node.parent_id,
                "children": node.children,
                "siblings": node.siblings,
                "status": node.status,
                "pending_signals": len(node.pending_signals)
            })
        
        for signal in self.signals[-50:]:  # 최근 50개
            report["signals"].append({
                "signal_id": signal.signal_id,
                "source": signal.source_holon,
                "target": signal.target_holon,
                "type": signal.signal_type.value,
                "severity": signal.severity,
                "processed": signal.processed
            })
        
        for directive in self.directives[-50:]:
            report["directives"].append({
                "directive_id": directive.directive_id,
                "source": directive.source_holon,
                "targets": directive.target_holons,
                "type": directive.directive_type.value,
                "priority": directive.priority
            })
        
        report_path = self.reports_path / "hierarchy_report.json"
        with open(report_path, "w", encoding="utf-8") as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        
        return report_path
    
    def get_vertical_chain(self, holon_id: str) -> Dict:
        """
        특정 홀론의 수직 체인 (상위 → 홀론 → 하위) 조회
        """
        self.load_hierarchy()
        
        if holon_id not in self.nodes:
            return {}
        
        node = self.nodes[holon_id]
        
        # 상위 체인
        ancestors = []
        current = node.parent_id
        while current and current in self.nodes:
            ancestors.insert(0, current)
            current = self.nodes[current].parent_id
        
        # 하위 체인 (BFS)
        descendants = []
        queue = node.children.copy()
        while queue:
            child_id = queue.pop(0)
            if child_id in self.nodes:
                descendants.append(child_id)
                queue.extend(self.nodes[child_id].children)
        
        return {
            "holon_id": holon_id,
            "ancestors": ancestors,        # ROOT부터 parent까지
            "descendants": descendants,    # 모든 하위
            "siblings": node.siblings,     # 형제들
            "depth": node.depth
        }


def main():
    """테스트"""
    engine = HierarchyEngine()
    engine.print_hierarchy_tree()


if __name__ == "__main__":
    main()

