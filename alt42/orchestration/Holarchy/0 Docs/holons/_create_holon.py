#!/usr/bin/env python3
"""
새 Holon 문서 자동 생성
- 템플릿 기반 구조 생성
- holon_id 자동 생성
- Parent 링크 자동 연결
"""

import json
import re
import argparse
import logging
from pathlib import Path
from datetime import datetime
from typing import Optional, Dict

# 로깅 설정
logger = logging.getLogger("holarchy.create_holon")


class HolonCreator:
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.today = datetime.now().strftime("%Y-%m-%d")
        self.year = datetime.now().strftime("%Y")
        
    def get_next_id(self, holon_type: str) -> str:
        """다음 holon_id 생성"""
        pattern = re.compile(rf'^{holon_type}-{self.year}-(\d{{3}})')
        max_num = 0
        
        for md_file in self.base_path.glob("*.md"):
            match = pattern.match(md_file.stem)
            if match:
                num = int(match.group(1))
                max_num = max(max_num, num)
        
        # meetings, decisions, tasks 폴더도 확인
        for folder in ["meetings", "decisions", "tasks"]:
            folder_path = self.base_path.parent / folder
            if folder_path.exists():
                for md_file in folder_path.glob("*.md"):
                    match = pattern.match(md_file.stem)
                    if match:
                        num = int(match.group(1))
                        max_num = max(max_num, num)
        
        return f"{holon_type}-{self.year}-{max_num + 1:03d}"
    
    def get_parent_w_drive(self, parent_id: str) -> Optional[str]:
        """상위 Holon의 W.will.drive 가져오기"""
        for md_file in self.base_path.glob("*.md"):
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    if holon.get("holon_id") == parent_id:
                        return holon.get("W", {}).get("will", {}).get("drive", "")
                except json.JSONDecodeError as e:
                    logger.debug(f"부모 Holon JSON 파싱 실패 [{parent_id}]: {e}")
        return None
    
    def create_holon(
        self,
        holon_type: str,
        title: str,
        parent_id: Optional[str] = None,
        module: str = "M00_Astral"
    ) -> str:
        """새 Holon 생성"""
        
        holon_id = self.get_next_id(holon_type)
        slug = title.lower().replace(" ", "-").replace("_", "-")[:30]
        
        # 상위 W.will.drive 가져오기 (resonance용)
        parent_drive = ""
        if parent_id:
            parent_drive = self.get_parent_w_drive(parent_id) or ""
        
        # 핵심 키워드 추출 (전국, 수학, 학원, 시장, 독점 등)
        resonance_keywords = []
        for kw in ["전국", "수학", "학원", "시장", "독점", "자동화", "AI"]:
            if kw in parent_drive:
                resonance_keywords.append(kw)
        
        resonance_prefix = " ".join(resonance_keywords[:3]) + "을 위해 " if resonance_keywords else ""
        
        holon = {
            "holon_id": holon_id,
            "slug": slug,
            "type": holon_type,
            "module": module,
            
            "meta": {
                "title": title,
                "created_at": self.today,
                "updated_at": self.today,
                "status": "draft",
                "owner": "TBD"
            },
            
            "W": {
                "worldview": {
                    "identity": f"[{title}의 정체성을 정의하세요]",
                    "belief": "[핵심 믿음을 정의하세요]",
                    "value_system": "[가치 체계를 정의하세요]"
                },
                "will": {
                    "drive": f"{resonance_prefix}[이 문서의 핵심 의지를 정의하세요]",
                    "commitment": "[어떤 각오로 임하는지 정의하세요]",
                    "non_negotiables": ["[타협 불가 사항 1]", "[타협 불가 사항 2]"]
                },
                "intention": {
                    "primary": f"[{title}의 핵심 의도]",
                    "secondary": ["[부가 의도]"],
                    "constraints": ["[의도 보호 경계선]"]
                },
                "goal": {
                    "ultimate": f"[{title}의 최종 목표]",
                    "milestones": ["[중간 목표 1]", "[중간 목표 2]"],
                    "kpi": ["[측정 지표]"],
                    "okr": {
                        "objective": "[달성할 목적]",
                        "key_results": ["[핵심 결과]"]
                    }
                },
                "activation": {
                    "triggers": ["[W 재점검 상황]"],
                    "resonance_check": f"이 문서가 상위 W와 공명하는가? (상위: {parent_id or 'ROOT'})",
                    "drift_detection": "[의지 약화 징후 감지 방법]"
                }
            },
            
            "X": {
                "context": "[현재 상황/배경]",
                "current_state": "[현재 진행 상태]",
                "heartbeat": "weekly",
                "signals": ["[관찰할 신호]"],
                "constraints": ["[제약 사항]"],
                "will": "[W의 의지에 맞는 현실을 파악하려는 의지]"
            },
            
            "S": {
                "resources": ["[리소스 1]"],
                "dependencies": [parent_id] if parent_id else [],
                "access_points": ["[접근점]"],
                "structure_model": "[구조 모델]",
                "ontology_ref": [],
                "readiness_score": 0.0,
                "will": "[견고한 리소스 지원 의지]"
            },
            
            "P": {
                "procedure_steps": [
                    {
                        "step_id": "p001",
                        "description": "[단계 1 설명]",
                        "inputs": ["[입력]"],
                        "expected_outputs": ["[출력]"],
                        "tools_required": ["[도구]"]
                    }
                ],
                "optimization_logic": "[최적화 로직]",
                "will": "[최소 엔트로피 실행 의지]"
            },
            
            "E": {
                "execution_plan": [
                    {
                        "action_id": "e001",
                        "action": "[실행 항목]",
                        "eta_hours": 0,
                        "role": "TBD"
                    }
                ],
                "tooling": [],
                "edge_case_handling": [],
                "will": "[실시간 문제해결 의지]"
            },
            
            "R": {
                "reflection_notes": [],
                "lessons_learned": [],
                "success_path_inference": "[성공 경로 추론]",
                "future_prediction": "[미래 예측]",
                "will": "[의지 보존 점검 및 성공 경로 발견 의지]"
            },
            
            "T": {
                "impact_channels": [],
                "traffic_model": "[트래픽 모델]",
                "viral_mechanics": "[바이럴 메커니즘]",
                "bottleneck_points": [],
                "will": "[시너지 연결 및 가치 확산 의지]"
            },
            
            "A": {
                "abstraction": "[추상화 방향]",
                "modularization": [],
                "automation_opportunities": [],
                "integration_targets": [],
                "resonance_logic": f"상위 W({parent_id or 'ROOT'})와의 공명 유지",
                "will": "[상위 W와 연결되도록 중재하려는 의지]"
            },
            
            "links": {
                "parent": parent_id,
                "children": [],
                "related": [],
                "supersedes": None
            },
            
            "attachments": []
        }
        
        # 파일 생성
        filename = f"{holon_id}-{slug}.md"
        
        # 타입에 따라 폴더 결정
        if holon_type in ["meeting"]:
            folder = self.base_path.parent / "meetings"
        elif holon_type in ["decision"]:
            folder = self.base_path.parent / "decisions"
        elif holon_type in ["task"]:
            folder = self.base_path.parent / "tasks"
        else:
            folder = self.base_path
        
        folder.mkdir(parents=True, exist_ok=True)
        file_path = folder / filename
        
        # 문서 내용 생성
        json_str = json.dumps(holon, ensure_ascii=False, indent=2)
        content = f"""```json
{json_str}
```

---

# {title}

## 개요

[이 문서의 개요를 작성하세요]

## 상세 내용

[상세 내용을 작성하세요]

---

## 📎 Attachments

> 이 홀론과 관련된 첨부파일 목록입니다.  
> CLI로 관리: `python _cli.py attach add <holon_id> <파일경로>`

| 파일명 | 경로 | 타입 | 설명 |
|--------|------|------|------|
| _(없음)_ | - | - | - |

---

## 🔗 Holonic Links

### ⬆️ Parent
- [{parent_id or 'ROOT'}]({f'../{parent_id}.md' if parent_id else '#'})

"""
        
        file_path.write_text(content, encoding="utf-8")
        
        print(f"✅ 생성됨: {file_path}")
        print(f"   holon_id: {holon_id}")
        print(f"   parent: {parent_id or 'None (ROOT)'}")
        
        # 자동 검증 실행
        self._run_post_creation_check(holon_id, resonance_keywords)
        
        return holon_id
    
    def _run_post_creation_check(self, holon_id: str, resonance_keywords: list) -> None:
        """생성 후 자동 검증 및 안내"""
        print()
        print("-" * 60)
        print("🔍 생성 후 자동 검증")
        print("-" * 60)
        
        # 플레이스홀더 개수 경고
        placeholder_count = 57  # 템플릿 기본 플레이스홀더 수
        print(f"⚠️  현재 완성도: 약 0% (플레이스홀더 {placeholder_count}개)")
        print(f"   → 내용을 채워 70% 이상으로 올려주세요")
        
        # 필수 키워드 안내
        required_keywords = ["전국", "수학", "학원", "AI", "자동화", "시장"]
        if resonance_keywords:
            print()
            print(f"📌 W.will.drive에 포함해야 할 핵심 키워드:")
            for kw in resonance_keywords:
                print(f"   ✓ {kw}")
        else:
            print()
            print(f"📌 W.will.drive에 다음 키워드 중 일부 포함 권장:")
            for kw in required_keywords:
                print(f"   • {kw}")
        
        print()
        print("💡 상위 W와의 공명(Resonance)을 위해 키워드를 포함하세요")


def main():
    parser = argparse.ArgumentParser(description="새 Holon 문서 생성")
    parser.add_argument("type", choices=["strategy", "structure", "feature", "meeting", "decision", "task"],
                       help="Holon 타입")
    parser.add_argument("title", help="문서 제목")
    parser.add_argument("--parent", "-p", help="상위 holon_id")
    parser.add_argument("--module", "-m", default="M00_Astral", help="모듈 (M00~M21)")
    
    args = parser.parse_args()
    
    script_dir = Path(__file__).parent
    creator = HolonCreator(str(script_dir))
    
    print("=" * 60)
    print("📄 새 Holon 문서 생성")
    print("=" * 60)
    print()
    
    holon_id = creator.create_holon(
        holon_type=args.type,
        title=args.title,
        parent_id=args.parent,
        module=args.module
    )
    
    print()
    print("💡 다음 단계:")
    print("   1. 생성된 파일의 [대괄호] 부분을 채우세요")
    print("   2. python _auto_link.py 실행하여 링크 동기화")
    print("   3. python _validate.py 실행하여 검증")


if __name__ == "__main__":
    main()
