#!/usr/bin/env python3
"""
🎯 Mission Propagation Engine v1.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

상위 미션을 하위 문서에 자동 전파하는 시스템

핵심 기능:
1. 상위 미션(ROOT) 정의 및 관리
2. 계층적 미션 전파 (parent → children)
3. 하위 문서 W.will.drive 자동 업데이트
4. 미션 일관성 검증 및 리포트

미션 구조:
  ROOT (헌법)
    └── "전국 규모 수학학원 자동화"
         ├── 조직 구조 (hte-doc-001)
         ├── 제품 아키텍처 (hte-doc-002)
         │    ├── feature-2025-001
         │    └── feature-2025-002
         └── ...
"""

import json
import re
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple, Set
from dataclasses import dataclass, field

# 로깅 설정
logger = logging.getLogger("holarchy.mission_propagation")


@dataclass
class MissionNode:
    """미션 트리 노드"""
    holon_id: str
    filename: str
    filepath: str
    title: str
    drive: str                          # W.will.drive
    parent_id: Optional[str] = None
    children: List[str] = field(default_factory=list)
    depth: int = 0
    mission_score: float = 0.0          # 미션 일치도 (0~1)
    needs_update: bool = False
    

class MissionPropagationEngine:
    """미션 전파 엔진"""
    
    # 핵심 미션 키워드 (상위 → 하위 전파)
    MISSION_KEYWORDS = [
        "전국",
        "수학",
        "학원",
        "자동화",
        "시장",
        "독점",
        "자기진화",
        "AI",
        "교육"
    ]
    
    # 미션 템플릿 (깊이별)
    MISSION_TEMPLATES = {
        0: "{keywords}을 위해 자기진화형 교육 시스템을 반드시 구축한다",  # ROOT
        1: "{keywords}을 위해 {domain} 영역을 완벽히 구축한다",          # 전략
        2: "{keywords}을 위해 {domain}를 통해 핵심 기능을 구현한다",     # 기능
        3: "{keywords}을 위해 {domain}을 실행한다"                      # 태스크
    }
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.holons_path = self.base_path
        self.docs_root = self.base_path.parent
        self.reports_path = self.docs_root / "reports"
        
        # ROOT 미션 (헌법에서 로드)
        self.root_mission = self._load_root_mission()
        
        # 미션 트리
        self.mission_tree: Dict[str, MissionNode] = {}
        
        self.reports_path.mkdir(parents=True, exist_ok=True)
    
    def _load_root_mission(self) -> str:
        """헌법(hte-doc-000)에서 ROOT 미션 로드"""
        root_file = self.holons_path / "00-holarchy-overview.md"
        
        if root_file.exists():
            content = root_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)

            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    return holon.get("W", {}).get("will", {}).get("drive", "")
                except json.JSONDecodeError as e:
                    logger.debug(f"루트 미션 JSON 파싱 실패: {e}")
        
        # 기본 미션
        return "전국 수학 학원 시장을 독점하는 자기진화형 교육 시스템을 반드시 구축한다"
    
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
    
    def _calculate_mission_score(self, drive: str) -> float:
        """미션 일치도 계산 (0~1)"""
        if not drive:
            return 0.0
        
        drive_lower = drive.lower()
        matched = 0
        
        for keyword in self.MISSION_KEYWORDS:
            if keyword.lower() in drive_lower:
                matched += 1
        
        return matched / len(self.MISSION_KEYWORDS)
    
    def _extract_keywords_from_mission(self, mission: str) -> List[str]:
        """미션에서 핵심 키워드 추출"""
        keywords = []
        for kw in self.MISSION_KEYWORDS:
            if kw in mission:
                keywords.append(kw)
        return keywords
    
    def _generate_propagated_drive(self, parent_drive: str, title: str, depth: int) -> str:
        """상위 미션 기반 하위 drive 생성"""
        keywords = self._extract_keywords_from_mission(parent_drive)
        keywords_str = " ".join(keywords[:4]) if keywords else "전국 수학 학원 자동화"
        
        # 깊이별 템플릿 선택
        template_depth = min(depth, max(self.MISSION_TEMPLATES.keys()))
        template = self.MISSION_TEMPLATES.get(template_depth, self.MISSION_TEMPLATES[2])
        
        return template.format(
            keywords=keywords_str,
            domain=title
        )
    
    def build_mission_tree(self) -> Dict[str, MissionNode]:
        """전체 미션 트리 구축"""
        self.mission_tree = {}
        
        # 1. 모든 문서 로드
        all_files = list(self.holons_path.glob("*.md"))
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.docs_root / folder
            if folder_path.exists():
                all_files.extend(folder_path.glob("*.md"))
        
        # 2. 미션 노드 생성
        for filepath in all_files:
            if filepath.name.startswith("_"):
                continue
            
            holon = self._parse_holon(filepath)
            if not holon:
                continue
            
            holon_id = holon.get("holon_id", filepath.stem)
            drive = holon.get("W", {}).get("will", {}).get("drive", "")
            title = holon.get("meta", {}).get("title", filepath.stem)
            parent_id = holon.get("links", {}).get("parent")
            children = holon.get("links", {}).get("children", [])
            
            node = MissionNode(
                holon_id=holon_id,
                filename=filepath.name,
                filepath=str(filepath),
                title=title,
                drive=drive,
                parent_id=parent_id,
                children=children,
                mission_score=self._calculate_mission_score(drive)
            )
            
            self.mission_tree[holon_id] = node
        
        # 3. 깊이 계산 (BFS)
        self._calculate_depths()
        
        # 4. 업데이트 필요 여부 판단
        self._check_needs_update()
        
        return self.mission_tree
    
    def _calculate_depths(self):
        """각 노드의 깊이 계산"""
        # ROOT 찾기 (parent가 None인 노드)
        roots = [nid for nid, node in self.mission_tree.items() 
                if node.parent_id is None or node.parent_id not in self.mission_tree]
        
        # BFS로 깊이 계산
        visited = set()
        queue = [(rid, 0) for rid in roots]
        
        while queue:
            node_id, depth = queue.pop(0)
            if node_id in visited or node_id not in self.mission_tree:
                continue
            
            visited.add(node_id)
            self.mission_tree[node_id].depth = depth
            
            # 자식 노드 추가
            node = self.mission_tree[node_id]
            for child_id in node.children:
                if child_id in self.mission_tree:
                    queue.append((child_id, depth + 1))
            
            # parent-children이 설정 안 된 경우, 다른 노드의 parent로 연결된 것도 확인
            for other_id, other_node in self.mission_tree.items():
                if other_node.parent_id == node_id and other_id not in visited:
                    queue.append((other_id, depth + 1))
    
    def _check_needs_update(self):
        """미션 업데이트 필요 여부 판단"""
        for node_id, node in self.mission_tree.items():
            # 미션 점수가 낮으면 업데이트 필요
            if node.mission_score < 0.3:
                node.needs_update = True
            
            # 상위 미션 키워드가 없으면 업데이트 필요
            if node.parent_id and node.parent_id in self.mission_tree:
                parent = self.mission_tree[node.parent_id]
                parent_keywords = self._extract_keywords_from_mission(parent.drive)
                
                node_drive_lower = node.drive.lower()
                missing_keywords = [kw for kw in parent_keywords 
                                   if kw.lower() not in node_drive_lower]
                
                if len(missing_keywords) > len(parent_keywords) * 0.5:
                    node.needs_update = True
    
    def propagate_mission(self, dry_run: bool = True) -> Dict:
        """미션 전파 실행"""
        self.build_mission_tree()
        
        result = {
            "total_nodes": len(self.mission_tree),
            "needs_update": 0,
            "updated": [],
            "skipped": [],
            "dry_run": dry_run
        }
        
        for node_id, node in self.mission_tree.items():
            if not node.needs_update:
                result["skipped"].append(node_id)
                continue
            
            result["needs_update"] += 1
            
            # 상위 미션 기반 새 drive 생성
            if node.parent_id and node.parent_id in self.mission_tree:
                parent = self.mission_tree[node.parent_id]
                new_drive = self._generate_propagated_drive(
                    parent.drive, 
                    node.title, 
                    node.depth
                )
            else:
                # ROOT는 기본 미션 사용
                new_drive = self.root_mission
            
            update_info = {
                "holon_id": node_id,
                "filename": node.filename,
                "old_drive": node.drive[:50] + "..." if len(node.drive) > 50 else node.drive,
                "new_drive": new_drive[:50] + "..." if len(new_drive) > 50 else new_drive,
                "depth": node.depth
            }
            
            if not dry_run:
                # 실제 파일 업데이트
                self._update_document_drive(Path(node.filepath), new_drive)
            
            result["updated"].append(update_info)
        
        return result
    
    def _update_document_drive(self, filepath: Path, new_drive: str) -> bool:
        """문서의 W.will.drive 업데이트"""
        if not filepath.exists():
            return False
        
        content = filepath.read_text(encoding="utf-8")
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        
        if not json_match:
            return False
        
        try:
            holon = json.loads(json_match.group(1))
            
            # W.will.drive 업데이트
            if "W" not in holon:
                holon["W"] = {}
            if "will" not in holon["W"]:
                holon["W"]["will"] = {}
            
            holon["W"]["will"]["drive"] = new_drive
            holon["meta"]["updated_at"] = datetime.now().strftime("%Y-%m-%d")
            
            # JSON 재생성
            new_json = json.dumps(holon, ensure_ascii=False, indent=2)
            new_content = content[:json_match.start(1)] + new_json + content[json_match.end(1):]
            
            filepath.write_text(new_content, encoding="utf-8")
            return True
            
        except Exception as e:
            print(f"❌ 업데이트 실패: {filepath.name} - {e}")
            return False
    
    def print_mission_tree(self):
        """미션 트리 시각화"""
        self.build_mission_tree()
        
        print("=" * 70)
        print("🎯 Mission Propagation Tree")
        print(f"   ROOT: {self.root_mission[:50]}...")
        print("=" * 70)
        print()
        
        # 깊이별 정렬
        nodes_by_depth = {}
        for node_id, node in self.mission_tree.items():
            if node.depth not in nodes_by_depth:
                nodes_by_depth[node.depth] = []
            nodes_by_depth[node.depth].append(node)
        
        for depth in sorted(nodes_by_depth.keys()):
            nodes = nodes_by_depth[depth]
            
            if depth == 0:
                print(f"📌 ROOT (헌법)")
            else:
                print(f"{'  ' * depth}📂 Depth {depth}")
            
            for node in nodes:
                indent = "  " * (depth + 1)
                
                # 상태 이모지
                if node.needs_update:
                    status = "⚠️"
                elif node.mission_score >= 0.5:
                    status = "✅"
                else:
                    status = "🔶"
                
                print(f"{indent}{status} {node.holon_id}")
                print(f"{indent}   📄 {node.filename}")
                print(f"{indent}   💫 미션: {node.drive[:40]}...")
                print(f"{indent}   📊 점수: {node.mission_score:.0%}")
                print()
        
        # 요약
        total = len(self.mission_tree)
        needs_update = sum(1 for n in self.mission_tree.values() if n.needs_update)
        high_score = sum(1 for n in self.mission_tree.values() if n.mission_score >= 0.5)
        
        print("-" * 70)
        print(f"📊 요약:")
        print(f"   총 문서: {total}개")
        print(f"   미션 일치 높음 (≥50%): {high_score}개")
        print(f"   업데이트 필요: {needs_update}개")
        print()
    
    def print_propagation_preview(self):
        """전파 시뮬레이션 결과 출력"""
        result = self.propagate_mission(dry_run=True)
        
        print("=" * 70)
        print("🎯 Mission Propagation Preview (시뮬레이션)")
        print("=" * 70)
        print()
        
        if not result["updated"]:
            print("✅ 모든 문서가 미션과 일치합니다. 업데이트 필요 없음.")
            return
        
        print(f"⚠️ 업데이트 필요: {result['needs_update']}개")
        print()
        
        for update in result["updated"]:
            print(f"📄 {update['holon_id']} (depth: {update['depth']})")
            print(f"   현재: {update['old_drive']}")
            print(f"   변경: {update['new_drive']}")
            print()
        
        print("-" * 70)
        print("💡 실제 적용: python _cli.py mission propagate --execute")
    
    def save_report(self) -> Path:
        """리포트 저장"""
        self.build_mission_tree()
        
        report = {
            "generated_at": datetime.now().isoformat(),
            "root_mission": self.root_mission,
            "total_nodes": len(self.mission_tree),
            "by_depth": {},
            "needs_update": [],
            "nodes": []
        }
        
        for node_id, node in self.mission_tree.items():
            depth_str = str(node.depth)
            if depth_str not in report["by_depth"]:
                report["by_depth"][depth_str] = []
            report["by_depth"][depth_str].append(node_id)
            
            if node.needs_update:
                report["needs_update"].append(node_id)
            
            report["nodes"].append({
                "holon_id": node_id,
                "filename": node.filename,
                "title": node.title,
                "drive": node.drive,
                "parent_id": node.parent_id,
                "depth": node.depth,
                "mission_score": round(node.mission_score, 3),
                "needs_update": node.needs_update
            })
        
        report_path = self.reports_path / "mission_propagation_report.json"
        with open(report_path, "w", encoding="utf-8") as f:
            json.dump(report, f, ensure_ascii=False, indent=2)
        
        return report_path


def main():
    """테스트"""
    engine = MissionPropagationEngine()
    engine.print_mission_tree()
    print()
    engine.print_propagation_preview()


if __name__ == "__main__":
    main()

