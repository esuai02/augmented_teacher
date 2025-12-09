#!/usr/bin/env python3
"""
🤝 Sibling Collaboration Engine v1.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

형제 홀론 간 효과적 협력 시스템

핵심 원칙:
1. 형제 홀론은 같은 parent를 공유
2. 형제 간에는 수평적 협력 (수직적 명령 아님)
3. 정보 공유, 리소스 협력, 시너지 창출
4. 형제 협력의 결과는 parent에 집계 보고

협력 구조:
  ┌─────────────────────────────────────────┐
  │              Parent Holon               │
  │       (형제 협력 결과 집계/조율)          │
  └────────────────┬────────────────────────┘
                   │
     ┌─────────────┼─────────────┐
     │             │             │
     ▼             ▼             ▼
  ┌──────┐     ┌──────┐     ┌──────┐
  │Sibling│◄───►│Sibling│◄───►│Sibling│
  │  A    │     │  B    │     │  C    │
  └──────┘     └──────┘     └──────┘
       └──────────┴──────────┘
            협력 채널
            - 정보 공유
            - 리소스 요청
            - 시너지 제안
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
logger = logging.getLogger("holarchy.sibling_collaboration")


class CollaborationType(Enum):
    """협력 유형"""
    SHARE = "share"               # 정보 공유
    REQUEST = "request"           # 리소스/지원 요청
    SYNC = "sync"                 # 동기화 (상태, 진행률)
    SYNERGY = "synergy"           # 시너지 제안
    HANDOFF = "handoff"           # 작업 인계
    FEEDBACK = "feedback"         # 피드백/리뷰


class CollaborationStatus(Enum):
    """협력 상태"""
    PENDING = "pending"           # 대기
    ACCEPTED = "accepted"         # 수락됨
    IN_PROGRESS = "in_progress"   # 진행 중
    COMPLETED = "completed"       # 완료
    DECLINED = "declined"         # 거절됨
    CANCELLED = "cancelled"       # 취소됨


@dataclass
class CollaborationRequest:
    """협력 요청"""
    request_id: str
    from_holon: str               # 요청하는 형제
    to_holons: List[str]          # 대상 형제들
    collab_type: CollaborationType
    title: str
    description: str
    status: CollaborationStatus = CollaborationStatus.PENDING
    priority: int = 3             # 1(최고) ~ 5(최저)
    created_at: str = ""
    updated_at: str = ""
    responses: Dict[str, str] = field(default_factory=dict)  # holon_id: response
    outcome: str = ""             # 협력 결과


@dataclass
class SynergyOpportunity:
    """시너지 기회"""
    synergy_id: str
    holons_involved: List[str]    # 관련 형제들
    synergy_type: str             # overlap, complement, dependency
    description: str
    potential_value: str          # 기대 가치
    discovered_at: str = ""
    realized: bool = False


@dataclass
class SiblingNode:
    """형제 홀론 노드"""
    holon_id: str
    filename: str
    filepath: str
    title: str
    parent_id: Optional[str]
    siblings: List[str]           # 형제 홀론들
    
    # W 정보
    drive: str = ""
    resources: List[str] = field(default_factory=list)  # S.resources
    
    # 협력 상태
    active_collaborations: List[str] = field(default_factory=list)  # request_ids
    shared_resources: List[str] = field(default_factory=list)
    
    # 시너지
    synergy_scores: Dict[str, float] = field(default_factory=dict)  # sibling_id: score


class SiblingCollaborationEngine:
    """형제 협력 엔진"""
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.reports_path = self.docs_root / "reports"
        
        # 형제 노드들
        self.nodes: Dict[str, SiblingNode] = {}
        
        # 형제 그룹 (parent별로 그룹화)
        self.sibling_groups: Dict[str, List[str]] = {}
        
        # 협력 요청들
        self.collaborations: List[CollaborationRequest] = []
        
        # 시너지 기회들
        self.synergies: List[SynergyOpportunity] = []
        
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
    
    def load_siblings(self) -> Dict[str, SiblingNode]:
        """형제 관계 로드"""
        self.nodes = {}
        self.sibling_groups = {}
        
        # 1. 모든 문서 로드
        all_files = list(self.holons_path.glob("*.md"))
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                all_files.extend(folder_path.glob("*.md"))
        
        # 2. 노드 생성 및 parent별 그룹화
        for filepath in all_files:
            if filepath.name.startswith("_"):
                continue
            
            holon = self._parse_holon(filepath)
            if not holon:
                continue
            
            holon_id = holon.get("holon_id", filepath.stem)
            links = holon.get("links", {})
            parent_id = links.get("parent")
            
            node = SiblingNode(
                holon_id=holon_id,
                filename=filepath.name,
                filepath=str(filepath),
                title=holon.get("meta", {}).get("title", filepath.stem),
                parent_id=parent_id,
                siblings=links.get("siblings", []),
                drive=holon.get("W", {}).get("will", {}).get("drive", ""),
                resources=holon.get("S", {}).get("resources", [])
            )
            
            self.nodes[holon_id] = node
            
            # parent별 그룹화
            parent_key = parent_id or "__ROOT__"
            if parent_key not in self.sibling_groups:
                self.sibling_groups[parent_key] = []
            self.sibling_groups[parent_key].append(holon_id)
        
        # 3. 형제 관계 자동 설정
        self._auto_set_siblings()
        
        # 4. 시너지 점수 계산
        self._calculate_synergy_scores()
        
        return self.nodes
    
    def _auto_set_siblings(self):
        """형제 관계 자동 설정"""
        for parent, siblings in self.sibling_groups.items():
            if len(siblings) <= 1:
                continue
            
            for node_id in siblings:
                if node_id in self.nodes:
                    # 자기 자신 제외한 형제들
                    self.nodes[node_id].siblings = [s for s in siblings if s != node_id]
    
    def _calculate_synergy_scores(self):
        """형제 간 시너지 점수 계산"""
        for node_id, node in self.nodes.items():
            for sibling_id in node.siblings:
                if sibling_id not in self.nodes:
                    continue
                
                sibling = self.nodes[sibling_id]
                score = self._compute_synergy(node, sibling)
                node.synergy_scores[sibling_id] = score
    
    def _compute_synergy(self, node1: SiblingNode, node2: SiblingNode) -> float:
        """두 형제 간 시너지 점수 계산 (0~1)"""
        score = 0.0
        
        # 1. 리소스 상보성 (서로 다른 리소스 = 협력 가능성)
        res1 = set(node1.resources)
        res2 = set(node2.resources)
        if res1 and res2:
            overlap = len(res1 & res2)
            total = len(res1 | res2)
            # 약간의 겹침 + 많은 상보성 = 높은 시너지
            complement_ratio = (total - overlap) / total if total else 0
            score += complement_ratio * 0.4
        
        # 2. 미션 정렬 (비슷한 키워드)
        drive1_words = set(node1.drive.lower().split())
        drive2_words = set(node2.drive.lower().split())
        if drive1_words and drive2_words:
            common = len(drive1_words & drive2_words)
            total = len(drive1_words | drive2_words)
            alignment_ratio = common / total if total else 0
            score += alignment_ratio * 0.3
        
        # 3. 같은 parent = 기본 협력 가능성
        if node1.parent_id == node2.parent_id:
            score += 0.3
        
        return min(score, 1.0)
    
    # ═══════════════════════════════════════════════════════════════
    # 협력 요청/응답
    # ═══════════════════════════════════════════════════════════════
    
    def request_collaboration(
        self,
        from_holon: str,
        to_holons: List[str],
        collab_type: CollaborationType,
        title: str,
        description: str,
        priority: int = 3
    ) -> CollaborationRequest:
        """
        형제에게 협력 요청
        """
        # 형제인지 확인
        if from_holon in self.nodes:
            valid_targets = [t for t in to_holons if t in self.nodes[from_holon].siblings]
        else:
            valid_targets = to_holons
        
        if not valid_targets:
            raise ValueError(f"유효한 형제 대상 없음")
        
        request = CollaborationRequest(
            request_id=f"collab-{datetime.now().strftime('%Y%m%d%H%M%S')}",
            from_holon=from_holon,
            to_holons=valid_targets,
            collab_type=collab_type,
            title=title,
            description=description,
            priority=priority,
            created_at=datetime.now().isoformat(),
            updated_at=datetime.now().isoformat()
        )
        
        self.collaborations.append(request)
        
        # 활성 협력에 추가
        if from_holon in self.nodes:
            self.nodes[from_holon].active_collaborations.append(request.request_id)
        for tid in valid_targets:
            if tid in self.nodes:
                self.nodes[tid].active_collaborations.append(request.request_id)
        
        return request
    
    def respond_to_collaboration(
        self,
        request_id: str,
        responder: str,
        accept: bool,
        response_text: str = ""
    ) -> bool:
        """협력 요청에 응답"""
        for collab in self.collaborations:
            if collab.request_id == request_id:
                if responder not in collab.to_holons:
                    return False
                
                collab.responses[responder] = response_text
                collab.updated_at = datetime.now().isoformat()
                
                # 모든 대상이 응답했으면 상태 업데이트
                all_responded = all(t in collab.responses for t in collab.to_holons)
                if all_responded:
                    accepts = sum(1 for r in collab.responses.values() if "수락" in r or "accept" in r.lower())
                    if accepts > 0:
                        collab.status = CollaborationStatus.IN_PROGRESS
                    else:
                        collab.status = CollaborationStatus.DECLINED
                elif accept:
                    collab.status = CollaborationStatus.ACCEPTED
                
                return True
        
        return False
    
    def complete_collaboration(
        self,
        request_id: str,
        outcome: str
    ) -> bool:
        """협력 완료 처리"""
        for collab in self.collaborations:
            if collab.request_id == request_id:
                collab.status = CollaborationStatus.COMPLETED
                collab.outcome = outcome
                collab.updated_at = datetime.now().isoformat()
                return True
        return False
    
    # ═══════════════════════════════════════════════════════════════
    # 정보 공유
    # ═══════════════════════════════════════════════════════════════
    
    def share_info(
        self,
        from_holon: str,
        info_type: str,
        content: str,
        to_siblings: List[str] = None
    ) -> Dict:
        """
        형제들에게 정보 공유
        
        to_siblings: None이면 모든 형제에게
        """
        if from_holon not in self.nodes:
            return {"success": False, "error": "소스 홀론 없음"}
        
        node = self.nodes[from_holon]
        targets = to_siblings if to_siblings else node.siblings
        
        # 형제만 필터
        valid_targets = [t for t in targets if t in node.siblings]
        
        share_result = {
            "success": True,
            "from": from_holon,
            "to": valid_targets,
            "info_type": info_type,
            "shared_at": datetime.now().isoformat()
        }
        
        # 협력 요청으로 기록
        self.request_collaboration(
            from_holon=from_holon,
            to_holons=valid_targets,
            collab_type=CollaborationType.SHARE,
            title=f"정보 공유: {info_type}",
            description=content[:200]
        )
        
        return share_result
    
    def request_resource(
        self,
        from_holon: str,
        resource_type: str,
        reason: str
    ) -> Dict:
        """
        형제에게 리소스 요청
        
        가장 적합한 형제를 자동 선택
        """
        if from_holon not in self.nodes:
            return {"success": False, "error": "소스 홀론 없음"}
        
        node = self.nodes[from_holon]
        
        # 해당 리소스를 가진 형제 찾기
        candidates = []
        for sibling_id in node.siblings:
            if sibling_id not in self.nodes:
                continue
            sibling = self.nodes[sibling_id]
            
            # 리소스 매칭 (간단한 키워드 매칭)
            for res in sibling.resources:
                if resource_type.lower() in res.lower():
                    candidates.append({
                        "holon_id": sibling_id,
                        "resource": res,
                        "synergy_score": node.synergy_scores.get(sibling_id, 0)
                    })
        
        if not candidates:
            return {
                "success": False,
                "error": f"리소스 '{resource_type}'를 가진 형제 없음",
                "suggestion": "상위 홀론에 요청하세요"
            }
        
        # 시너지 점수 기준 정렬
        candidates.sort(key=lambda x: x["synergy_score"], reverse=True)
        best_match = candidates[0]
        
        # 협력 요청 생성
        request = self.request_collaboration(
            from_holon=from_holon,
            to_holons=[best_match["holon_id"]],
            collab_type=CollaborationType.REQUEST,
            title=f"리소스 요청: {resource_type}",
            description=reason,
            priority=2
        )
        
        return {
            "success": True,
            "request_id": request.request_id,
            "target": best_match["holon_id"],
            "resource": best_match["resource"],
            "synergy_score": best_match["synergy_score"]
        }
    
    # ═══════════════════════════════════════════════════════════════
    # 시너지 탐지 및 제안
    # ═══════════════════════════════════════════════════════════════
    
    def detect_synergies(self) -> List[SynergyOpportunity]:
        """
        형제 간 시너지 기회 자동 탐지
        """
        self.load_siblings()
        self.synergies = []
        
        # 각 형제 그룹별로 분석
        for parent, siblings in self.sibling_groups.items():
            if len(siblings) < 2:
                continue
            
            # 모든 쌍 검사
            for i, holon1 in enumerate(siblings):
                for holon2 in siblings[i+1:]:
                    if holon1 not in self.nodes or holon2 not in self.nodes:
                        continue
                    
                    node1 = self.nodes[holon1]
                    node2 = self.nodes[holon2]
                    
                    score = node1.synergy_scores.get(holon2, 0)
                    
                    # 높은 시너지 점수 = 기회
                    if score >= 0.5:
                        synergy_type = self._determine_synergy_type(node1, node2)
                        
                        synergy = SynergyOpportunity(
                            synergy_id=f"syn-{holon1[:8]}-{holon2[:8]}",
                            holons_involved=[holon1, holon2],
                            synergy_type=synergy_type,
                            description=self._generate_synergy_description(node1, node2, synergy_type),
                            potential_value=f"시너지 점수 {score:.0%}",
                            discovered_at=datetime.now().isoformat()
                        )
                        
                        self.synergies.append(synergy)
        
        return self.synergies
    
    def _determine_synergy_type(self, node1: SiblingNode, node2: SiblingNode) -> str:
        """시너지 유형 결정"""
        res1 = set(node1.resources)
        res2 = set(node2.resources)
        
        overlap = len(res1 & res2)
        total = len(res1 | res2)
        
        if overlap > total * 0.5:
            return "overlap"      # 중복 리소스 → 통합/분업 가능
        elif overlap < total * 0.2:
            return "complement"   # 상보적 리소스 → 협력 시너지
        else:
            return "dependency"   # 의존 관계 → 조율 필요
    
    def _generate_synergy_description(
        self,
        node1: SiblingNode,
        node2: SiblingNode,
        synergy_type: str
    ) -> str:
        """시너지 설명 생성"""
        if synergy_type == "overlap":
            return f"{node1.title}와 {node2.title}이(가) 중복 리소스를 보유. 통합 또는 분업 권장."
        elif synergy_type == "complement":
            return f"{node1.title}와 {node2.title}이(가) 상보적 리소스 보유. 협력 시 시너지 기대."
        else:
            return f"{node1.title}와 {node2.title} 간 의존 관계 존재. 조율 필요."
    
    def suggest_collaboration(self, holon_id: str) -> List[Dict]:
        """
        특정 홀론에게 협력 제안
        """
        self.load_siblings()
        
        if holon_id not in self.nodes:
            return []
        
        node = self.nodes[holon_id]
        suggestions = []
        
        # 시너지 점수 기준으로 추천
        sorted_siblings = sorted(
            node.synergy_scores.items(),
            key=lambda x: x[1],
            reverse=True
        )
        
        for sibling_id, score in sorted_siblings[:5]:
            if sibling_id not in self.nodes:
                continue
            
            sibling = self.nodes[sibling_id]
            
            suggestions.append({
                "sibling_id": sibling_id,
                "sibling_title": sibling.title,
                "synergy_score": score,
                "reason": self._generate_collaboration_reason(node, sibling, score),
                "suggested_type": self._suggest_collaboration_type(node, sibling)
            })
        
        return suggestions
    
    def _generate_collaboration_reason(
        self,
        node: SiblingNode,
        sibling: SiblingNode,
        score: float
    ) -> str:
        """협력 이유 생성"""
        if score >= 0.7:
            return f"높은 시너지 ({score:.0%}): 긴밀한 협력 권장"
        elif score >= 0.5:
            return f"적절한 시너지 ({score:.0%}): 정보 공유 권장"
        else:
            return f"기본 협력 ({score:.0%}): 필요시 조율"
    
    def _suggest_collaboration_type(
        self,
        node: SiblingNode,
        sibling: SiblingNode
    ) -> CollaborationType:
        """협력 유형 제안"""
        res1 = set(node.resources)
        res2 = set(sibling.resources)
        
        # 상대가 가진 것 중 내가 없는 것
        unique_to_sibling = res2 - res1
        
        if unique_to_sibling:
            return CollaborationType.REQUEST
        elif res1 & res2:
            return CollaborationType.SYNC
        else:
            return CollaborationType.SHARE
    
    # ═══════════════════════════════════════════════════════════════
    # 협력 결과 상위 보고
    # ═══════════════════════════════════════════════════════════════
    
    def aggregate_to_parent(self, parent_id: str) -> Dict:
        """
        형제 협력 결과를 상위 홀론에 집계 보고
        """
        self.load_siblings()
        
        if parent_id not in self.sibling_groups:
            return {}
        
        siblings = self.sibling_groups[parent_id]
        
        report = {
            "parent_id": parent_id,
            "total_siblings": len(siblings),
            "collaboration_summary": {
                "total": 0,
                "completed": 0,
                "in_progress": 0,
                "pending": 0
            },
            "synergies_detected": 0,
            "active_collaborations": [],
            "recommendations": []
        }
        
        # 협력 현황 집계
        sibling_set = set(siblings)
        for collab in self.collaborations:
            involved = {collab.from_holon} | set(collab.to_holons)
            if involved & sibling_set:
                report["collaboration_summary"]["total"] += 1
                
                if collab.status == CollaborationStatus.COMPLETED:
                    report["collaboration_summary"]["completed"] += 1
                elif collab.status == CollaborationStatus.IN_PROGRESS:
                    report["collaboration_summary"]["in_progress"] += 1
                    report["active_collaborations"].append({
                        "id": collab.request_id,
                        "type": collab.collab_type.value,
                        "from": collab.from_holon,
                        "to": collab.to_holons
                    })
                else:
                    report["collaboration_summary"]["pending"] += 1
        
        # 시너지 기회 집계
        for synergy in self.synergies:
            if set(synergy.holons_involved) & sibling_set:
                report["synergies_detected"] += 1
        
        # 권고 생성
        if report["collaboration_summary"]["pending"] > 3:
            report["recommendations"].append(
                f"대기 중인 협력 요청 {report['collaboration_summary']['pending']}건 - 검토 필요"
            )
        
        if report["synergies_detected"] > 0:
            report["recommendations"].append(
                f"시너지 기회 {report['synergies_detected']}건 발견 - 활용 권장"
            )
        
        return report
    
    # ═══════════════════════════════════════════════════════════════
    # 시각화 및 리포트
    # ═══════════════════════════════════════════════════════════════
    
    def print_sibling_groups(self):
        """형제 그룹 출력"""
        self.load_siblings()
        
        print("=" * 70)
        print("🤝 Sibling Groups (형제 협력 현황)")
        print("   같은 parent를 공유하는 형제 홀론들")
        print("=" * 70)
        print()
        
        for parent, siblings in self.sibling_groups.items():
            if len(siblings) < 2:
                continue
            
            parent_name = parent if parent != "__ROOT__" else "ROOT (최상위)"
            print(f"📂 Parent: {parent_name}")
            print(f"   형제 수: {len(siblings)}개")
            print()
            
            for sibling_id in siblings:
                if sibling_id not in self.nodes:
                    continue
                
                node = self.nodes[sibling_id]
                
                # 최고 시너지 형제
                best_synergy = max(node.synergy_scores.items(), key=lambda x: x[1]) if node.synergy_scores else (None, 0)
                
                print(f"   🔹 {sibling_id}")
                print(f"      {node.title}")
                if best_synergy[0]:
                    print(f"      🔗 Best Synergy: {best_synergy[0]} ({best_synergy[1]:.0%})")
                
                active = len(node.active_collaborations)
                if active > 0:
                    print(f"      ⚡ Active Collaborations: {active}")
                
                print()
            
            print("-" * 70)
            print()
        
        # 전체 요약
        total_siblings = sum(len(s) for s in self.sibling_groups.values() if len(s) >= 2)
        total_collabs = len(self.collaborations)
        
        print("📊 전체 요약:")
        print(f"   총 형제 그룹: {sum(1 for s in self.sibling_groups.values() if len(s) >= 2)}개")
        print(f"   총 형제 홀론: {total_siblings}개")
        print(f"   총 협력 요청: {total_collabs}개")
        print()
    
    def print_collaboration_status(self):
        """협력 현황 출력"""
        print("=" * 70)
        print("⚡ Collaboration Status (협력 현황)")
        print("=" * 70)
        print()
        
        if not self.collaborations:
            print("📭 현재 협력 요청 없음")
            return
        
        # 상태별 분류
        by_status = {}
        for collab in self.collaborations:
            status = collab.status.value
            if status not in by_status:
                by_status[status] = []
            by_status[status].append(collab)
        
        status_order = ["in_progress", "pending", "accepted", "completed", "declined"]
        status_emoji = {
            "in_progress": "🔄",
            "pending": "⏳",
            "accepted": "✅",
            "completed": "🏆",
            "declined": "❌",
            "cancelled": "🚫"
        }
        
        for status in status_order:
            if status not in by_status:
                continue
            
            collabs = by_status[status]
            emoji = status_emoji.get(status, "❓")
            
            print(f"{emoji} {status.upper()} ({len(collabs)}개)")
            print("-" * 50)
            
            for collab in collabs[:5]:
                print(f"   [{collab.collab_type.value}] {collab.title}")
                print(f"   {collab.from_holon} → {', '.join(collab.to_holons)}")
                if collab.outcome:
                    print(f"   결과: {collab.outcome[:50]}")
                print()
            
            if len(collabs) > 5:
                print(f"   ... 외 {len(collabs) - 5}개")
            print()
    
    def print_synergy_report(self):
        """시너지 리포트 출력"""
        self.detect_synergies()
        
        print("=" * 70)
        print("✨ Synergy Opportunities (시너지 기회)")
        print("=" * 70)
        print()
        
        if not self.synergies:
            print("📭 발견된 시너지 기회 없음")
            return
        
        # 유형별 분류
        by_type = {"overlap": [], "complement": [], "dependency": []}
        for syn in self.synergies:
            by_type[syn.synergy_type].append(syn)
        
        type_info = {
            "complement": ("🔗", "상보적 시너지", "서로 다른 강점으로 협력"),
            "overlap": ("🔀", "중복 시너지", "리소스 통합/분업 가능"),
            "dependency": ("⛓️", "의존 시너지", "조율 필요")
        }
        
        for stype, (emoji, name, desc) in type_info.items():
            synergies = by_type.get(stype, [])
            if not synergies:
                continue
            
            print(f"{emoji} {name} ({len(synergies)}개)")
            print(f"   {desc}")
            print("-" * 50)
            
            for syn in synergies[:5]:
                holons = " ↔ ".join(syn.holons_involved)
                print(f"   {holons}")
                print(f"   {syn.description}")
                print(f"   {syn.potential_value}")
                realized = "✅ 실현됨" if syn.realized else "⏳ 미실현"
                print(f"   {realized}")
                print()
            
            if len(synergies) > 5:
                print(f"   ... 외 {len(synergies) - 5}개")
            print()
    
    def save_collaboration_report(self) -> Path:
        """협력 리포트 저장"""
        self.load_siblings()
        self.detect_synergies()
        
        report = {
            "generated_at": datetime.now().isoformat(),
            "sibling_groups": {},
            "collaborations": [],
            "synergies": []
        }
        
        # 형제 그룹
        for parent, siblings in self.sibling_groups.items():
            if len(siblings) >= 2:
                report["sibling_groups"][parent] = {
                    "count": len(siblings),
                    "members": siblings
                }
        
        # 협력
        for collab in self.collaborations:
            report["collaborations"].append({
                "request_id": collab.request_id,
                "from": collab.from_holon,
                "to": collab.to_holons,
                "type": collab.collab_type.value,
                "status": collab.status.value,
                "priority": collab.priority,
                "outcome": collab.outcome
            })
        
        # 시너지
        for syn in self.synergies:
            report["synergies"].append({
                "synergy_id": syn.synergy_id,
                "holons": syn.holons_involved,
                "type": syn.synergy_type,
                "value": syn.potential_value,
                "realized": syn.realized
            })
        
        report_path = self.reports_path / "sibling_collaboration_report.json"
        with open(report_path, "w", encoding="utf-8") as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        
        return report_path


def main():
    """테스트"""
    engine = SiblingCollaborationEngine()
    engine.print_sibling_groups()
    print()
    engine.print_synergy_report()


if __name__ == "__main__":
    main()

