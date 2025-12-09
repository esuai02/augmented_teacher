#!/usr/bin/env python3
"""
자동 양방향 링크 업데이트 v2.0
- A가 B를 참조하면 B의 related에도 A 추가
- parent-child 관계 자동 동기화
- siblings (형제) 관계 자동 설정 (NEW)
"""

import json
import re
from pathlib import Path
from typing import Dict, List, Optional


class AutoLinker:
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.holons: Dict[str, dict] = {}
        self.holon_files: Dict[str, Path] = {}
        
        # 형제 그룹 (parent별로 그룹화)
        self.sibling_groups: Dict[str, List[str]] = {}
        
    def load_holons(self) -> None:
        """모든 Holon 문서 로드"""
        for md_file in self.base_path.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
                
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    holon_id = holon.get("holon_id", md_file.stem)
                    self.holons[holon_id] = holon
                    self.holon_files[holon_id] = md_file
                except json.JSONDecodeError:
                    pass
    
    def update_holon_file(self, holon_id: str, holon: dict) -> None:
        """Holon 파일의 JSON 부분 업데이트"""
        file_path = self.holon_files.get(holon_id)
        if not file_path:
            return
            
        content = file_path.read_text(encoding="utf-8")
        
        # JSON 부분 찾기
        json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
        if not json_match:
            return
        
        # 새 JSON 생성 (예쁘게 포맷)
        new_json = json.dumps(holon, ensure_ascii=False, indent=2)
        
        # 교체
        new_content = content[:json_match.start()] + "```json\n" + new_json + "\n```" + content[json_match.end():]
        
        file_path.write_text(new_content, encoding="utf-8")
    
    def sync_parent_child(self) -> int:
        """Parent-Child 관계 양방향 동기화"""
        changes = 0
        
        for holon_id, holon in self.holons.items():
            links = holon.get("links", {})
            parent_id = links.get("parent")
            
            # Parent가 있으면 Parent의 children에 자신 추가
            if parent_id and parent_id in self.holons:
                parent = self.holons[parent_id]
                parent_links = parent.setdefault("links", {})
                parent_children = parent_links.setdefault("children", [])
                
                if holon_id not in parent_children:
                    parent_children.append(holon_id)
                    self.update_holon_file(parent_id, parent)
                    print(f"  ✅ {parent_id}.children에 {holon_id} 추가")
                    changes += 1
            
            # Children이 있으면 각 Child의 parent를 자신으로 설정
            for child_id in links.get("children", []):
                if child_id in self.holons:
                    child = self.holons[child_id]
                    child_links = child.setdefault("links", {})
                    
                    if child_links.get("parent") != holon_id:
                        child_links["parent"] = holon_id
                        self.update_holon_file(child_id, child)
                        print(f"  ✅ {child_id}.parent를 {holon_id}로 설정")
                        changes += 1
        
        return changes
    
    def sync_related(self) -> int:
        """Related 관계 양방향 동기화"""
        changes = 0
        
        for holon_id, holon in self.holons.items():
            links = holon.get("links", {})
            
            for related_id in links.get("related", []):
                if related_id in self.holons:
                    related = self.holons[related_id]
                    related_links = related.setdefault("links", {})
                    related_related = related_links.setdefault("related", [])
                    
                    if holon_id not in related_related:
                        related_related.append(holon_id)
                        self.update_holon_file(related_id, related)
                        print(f"  ✅ {related_id}.related에 {holon_id} 추가")
                        changes += 1
        
        return changes
    
    def build_sibling_groups(self) -> None:
        """형제 그룹 구축 (같은 parent를 가진 홀론들)"""
        self.sibling_groups = {}
        
        for holon_id, holon in self.holons.items():
            links = holon.get("links", {})
            parent_id = links.get("parent") or "__ROOT__"
            
            if parent_id not in self.sibling_groups:
                self.sibling_groups[parent_id] = []
            self.sibling_groups[parent_id].append(holon_id)
    
    def sync_siblings(self) -> int:
        """Siblings (형제) 관계 자동 동기화 - 같은 parent를 가진 홀론들"""
        changes = 0
        
        # 먼저 형제 그룹 구축
        self.build_sibling_groups()
        
        for parent_id, siblings in self.sibling_groups.items():
            if len(siblings) < 2:
                continue
            
            # 각 형제에게 다른 형제들을 siblings로 설정
            for holon_id in siblings:
                if holon_id not in self.holons:
                    continue
                
                holon = self.holons[holon_id]
                links = holon.setdefault("links", {})
                current_siblings = links.get("siblings", [])
                
                # 자기 자신을 제외한 형제들
                expected_siblings = sorted([s for s in siblings if s != holon_id])
                
                # 변경이 필요한지 확인
                if sorted(current_siblings) != expected_siblings:
                    links["siblings"] = expected_siblings
                    self.update_holon_file(holon_id, holon)
                    
                    added = set(expected_siblings) - set(current_siblings)
                    if added:
                        print(f"  ✅ {holon_id}.siblings에 {len(added)}개 형제 추가")
                    
                    changes += 1
        
        return changes
    
    def run(self) -> None:
        """전체 동기화 실행"""
        print("=" * 60)
        print("🔗 자동 양방향 링크 업데이트 v2.0")
        print("   수직적 위계질서 + 형제 협력 구조 지원")
        print("=" * 60)
        print()
        
        print("📂 Holon 문서 로드 중...")
        self.load_holons()
        print(f"   로드된 문서: {len(self.holons)}개")
        print()
        
        print("🔄 Parent-Child 동기화 (수직적 위계질서)...")
        pc_changes = self.sync_parent_child()
        print()
        
        print("🤝 Siblings 동기화 (형제 협력)...")
        sib_changes = self.sync_siblings()
        print()
        
        print("🔄 Related 동기화...")
        r_changes = self.sync_related()
        print()
        
        total = pc_changes + sib_changes + r_changes
        print("=" * 60)
        if total > 0:
            print(f"✅ 완료 - {total}개 링크 업데이트")
            print(f"   Parent-Child: {pc_changes}")
            print(f"   Siblings: {sib_changes}")
            print(f"   Related: {r_changes}")
        else:
            print("✅ 완료 - 모든 링크가 이미 동기화됨")
        
        # 형제 그룹 요약
        groups_with_siblings = sum(1 for g in self.sibling_groups.values() if len(g) >= 2)
        total_siblings = sum(len(g) for g in self.sibling_groups.values() if len(g) >= 2)
        print()
        print(f"📊 형제 그룹 현황: {groups_with_siblings}개 그룹, {total_siblings}개 홀론")
        print("=" * 60)


def main():
    script_dir = Path(__file__).parent
    linker = AutoLinker(str(script_dir))
    linker.run()


if __name__ == "__main__":
    main()
