#!/usr/bin/env python3
"""
회의 → 결정 → 작업 자동 생성
- Meeting Holon의 decisions에서 Decision Holon 자동 생성
- Meeting Holon의 next_actions에서 Task Holon 자동 생성
- 모든 링크 자동 연결
"""

import json
import re
import argparse
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional

# 로깅 설정
logger = logging.getLogger("holarchy.spawn_meeting")


class MeetingSpawner:
    def __init__(self, base_path: str):
        self.base_path = Path(base_path)
        self.meetings_path = self.base_path.parent / "meetings"
        self.decisions_path = self.base_path.parent / "decisions"
        self.tasks_path = self.base_path.parent / "tasks"
        self.today = datetime.now().strftime("%Y-%m-%d")
        self.year = datetime.now().strftime("%Y")
        
    def get_next_id(self, holon_type: str, folder: Path) -> str:
        """다음 holon_id 생성"""
        pattern = re.compile(rf'^{holon_type}-{self.year}-(\d{{3}})')
        max_num = 0
        
        if folder.exists():
            for md_file in folder.glob("*.md"):
                match = pattern.match(md_file.stem)
                if match:
                    num = int(match.group(1))
                    max_num = max(max_num, num)
        
        return f"{holon_type}-{self.year}-{max_num + 1:03d}"
    
    def load_meeting(self, meeting_id: str) -> Optional[dict]:
        """Meeting Holon 로드"""
        for md_file in self.meetings_path.glob("*.md"):
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    if holon.get("holon_id") == meeting_id:
                        holon["_file"] = md_file
                        return holon
                except json.JSONDecodeError as e:
                    logger.debug(f"Meeting Holon JSON 파싱 실패 [{meeting_id}]: {e}")
        return None
    
    def create_decision(self, decision_data: dict, meeting_id: str) -> str:
        """Decision Holon 생성"""
        decision_id = self.get_next_id("decision", self.decisions_path)
        
        title = decision_data.get("title", "결정 사항")
        slug = title[:20].lower().replace(" ", "-")
        
        holon = {
            "holon_id": decision_id,
            "slug": slug,
            "type": "decision",
            "module": "M01_TimeCrystal",
            
            "meta": {
                "title": title,
                "created_at": self.today,
                "updated_at": self.today,
                "status": "active",
                "owner": ", ".join(decision_data.get("decided_by", ["TBD"]))
            },
            
            "decision": {
                "made_at": self.today,
                "made_in": meeting_id,
                "decided_by": decision_data.get("decided_by", []),
                "rationale": decision_data.get("rationale", ""),
                "affects": decision_data.get("affects", [])
            },
            
            "W": {
                "worldview": {
                    "identity": f"'{title}' 결정의 실행 보장자",
                    "belief": "명확한 결정이 빠른 실행을 가능하게 한다",
                    "value_system": "결정의 일관성과 추적 가능성"
                },
                "will": {
                    "drive": f"전국 수학 학원 시장 독점을 위해 '{title}' 결정을 반드시 실행한다",
                    "commitment": "이 결정을 번복하지 않고 끝까지 실행한다",
                    "non_negotiables": ["결정 실행", "영향 추적"]
                },
                "intention": {
                    "primary": title,
                    "secondary": [],
                    "constraints": []
                },
                "goal": {
                    "ultimate": f"'{title}' 완전 실행",
                    "milestones": ["결정 공유", "실행 시작", "결과 확인"],
                    "kpi": ["실행 완료율"],
                    "okr": {
                        "objective": title,
                        "key_results": ["관련 작업 100% 완료"]
                    }
                },
                "activation": {
                    "triggers": ["실행 지연", "결정 번복 시도"],
                    "resonance_check": f"회의 {meeting_id}의 목적과 공명하는가?",
                    "drift_detection": "결정 의도와 다른 실행은 경고"
                }
            },
            
            "X": {
                "context": f"회의 {meeting_id}에서 결정됨",
                "current_state": "결정 완료, 실행 대기",
                "heartbeat": "daily",
                "signals": ["실행 상태", "영향 확인"],
                "constraints": [],
                "will": "결정 배경을 명확히 추적하려는 의지"
            },
            
            "S": {
                "resources": [],
                "dependencies": [meeting_id] + decision_data.get("affects", []),
                "access_points": [],
                "structure_model": "Decision Record",
                "ontology_ref": [],
                "readiness_score": 1.0,
                "will": "결정 실행에 필요한 모든 리소스 확보 의지"
            },
            
            "P": {
                "procedure_steps": [],
                "optimization_logic": "결정 → 작업 → 완료 최단 경로",
                "will": "결정을 작업으로 빠르게 변환하려는 의지"
            },
            
            "E": {
                "execution_plan": [],
                "tooling": [],
                "edge_case_handling": [],
                "will": "결정을 즉시 실행하려는 의지"
            },
            
            "R": {
                "reflection_notes": [],
                "lessons_learned": [],
                "success_path_inference": "",
                "future_prediction": "",
                "will": "결정 결과를 성찰하고 개선하려는 의지"
            },
            
            "T": {
                "impact_channels": decision_data.get("affects", []),
                "traffic_model": "결정 → 영향받는 문서",
                "viral_mechanics": "",
                "bottleneck_points": [],
                "will": "결정 영향을 명확히 전파하려는 의지"
            },
            
            "A": {
                "abstraction": "결정 패턴 추출",
                "modularization": [],
                "automation_opportunities": [],
                "integration_targets": [],
                "resonance_logic": f"회의 {meeting_id}와 공명 유지",
                "will": "결정을 고도화하여 패턴화하려는 의지"
            },
            
            "links": {
                "parent": meeting_id,
                "children": [],
                "related": decision_data.get("affects", []),
                "supersedes": None,
                "spawned_from": meeting_id
            }
        }
        
        # 파일 생성
        self.decisions_path.mkdir(parents=True, exist_ok=True)
        filename = f"{decision_id}-{slug}.md"
        file_path = self.decisions_path / filename
        
        json_str = json.dumps(holon, ensure_ascii=False, indent=2)
        content = f"""```json
{json_str}
```

---

# {title}

## 결정 내용

{decision_data.get('rationale', '[결정 이유를 작성하세요]')}

## 영향받는 문서

{chr(10).join([f'- {a}' for a in decision_data.get('affects', [])])}

---

## 🔗 Holonic Links

### ⬆️ Spawned From
- [{meeting_id}](../meetings/{meeting_id}.md)

"""
        
        file_path.write_text(content, encoding="utf-8")
        print(f"  ✅ Decision 생성: {decision_id} - {title}")
        
        return decision_id
    
    def create_task(self, action_data: dict, meeting_id: str) -> str:
        """Task Holon 생성"""
        task_id = self.get_next_id("task", self.tasks_path)
        
        action = action_data.get("action", "작업")
        slug = action[:20].lower().replace(" ", "-")
        assignee = action_data.get("assignee", "TBD")
        due_date = action_data.get("due_date", self.today)
        
        holon = {
            "holon_id": task_id,
            "slug": slug,
            "type": "task",
            "module": "M02_TimelineGenesis",
            
            "meta": {
                "title": action,
                "created_at": self.today,
                "updated_at": self.today,
                "status": "pending",
                "owner": assignee
            },
            
            "task": {
                "assignee": assignee,
                "due_date": due_date,
                "created_from": meeting_id,
                "acceptance_criteria": []
            },
            
            "W": {
                "worldview": {
                    "identity": f"'{action}' 작업 완수자",
                    "belief": "명확한 작업이 결과를 만든다",
                    "value_system": "완료, 품질, 기한 준수"
                },
                "will": {
                    "drive": f"전국 수학 학원 시장 독점을 위해 '{action}' 작업을 기한 내 완료한다",
                    "commitment": f"{due_date}까지 반드시 완료",
                    "non_negotiables": ["기한 준수", "품질 유지"]
                },
                "intention": {
                    "primary": action,
                    "secondary": [],
                    "constraints": [f"기한: {due_date}"]
                },
                "goal": {
                    "ultimate": f"'{action}' 완료",
                    "milestones": ["시작", "진행 중", "완료"],
                    "kpi": ["완료 여부", "기한 준수"],
                    "okr": {
                        "objective": action,
                        "key_results": [f"{due_date}까지 완료"]
                    }
                },
                "activation": {
                    "triggers": ["기한 임박", "진행 지연"],
                    "resonance_check": f"회의 {meeting_id}의 목적과 공명하는가?",
                    "drift_detection": "범위 확대, 기한 지연은 경고"
                }
            },
            
            "X": {
                "context": f"회의 {meeting_id}에서 생성됨",
                "current_state": "대기 중",
                "heartbeat": "daily",
                "signals": ["진행률", "블로커"],
                "constraints": [f"기한: {due_date}"],
                "will": "작업 상태를 명확히 파악하려는 의지"
            },
            
            "S": {
                "resources": [],
                "dependencies": [meeting_id],
                "access_points": [],
                "structure_model": "Task",
                "ontology_ref": [],
                "readiness_score": 0.0,
                "will": "작업 완료에 필요한 리소스 확보 의지"
            },
            
            "P": {
                "procedure_steps": [],
                "optimization_logic": "최단 시간 내 완료",
                "will": "효율적 절차로 빠르게 완료하려는 의지"
            },
            
            "E": {
                "execution_plan": [
                    {
                        "action_id": "t001",
                        "action": action,
                        "eta_hours": 0,
                        "role": assignee
                    }
                ],
                "tooling": [],
                "edge_case_handling": [],
                "will": "즉시 실행하여 기한 내 완료하려는 의지"
            },
            
            "R": {
                "reflection_notes": [],
                "lessons_learned": [],
                "success_path_inference": "",
                "future_prediction": "",
                "will": "작업 결과를 성찰하고 개선하려는 의지"
            },
            
            "T": {
                "impact_channels": [],
                "traffic_model": "작업 → 결과물",
                "viral_mechanics": "",
                "bottleneck_points": [],
                "will": "작업 결과를 명확히 전달하려는 의지"
            },
            
            "A": {
                "abstraction": "작업 패턴 추출",
                "modularization": [],
                "automation_opportunities": [],
                "integration_targets": [],
                "resonance_logic": f"회의 {meeting_id}와 공명 유지",
                "will": "반복 작업을 자동화하려는 의지"
            },
            
            "links": {
                "parent": meeting_id,
                "children": [],
                "related": [],
                "supersedes": None,
                "spawned_from": meeting_id
            }
        }
        
        # 파일 생성
        self.tasks_path.mkdir(parents=True, exist_ok=True)
        filename = f"{task_id}-{slug}.md"
        file_path = self.tasks_path / filename
        
        json_str = json.dumps(holon, ensure_ascii=False, indent=2)
        content = f"""```json
{json_str}
```

---

# {action}

## 작업 내용

- **담당자**: {assignee}
- **기한**: {due_date}
- **상태**: pending

## 완료 기준

[완료 기준을 작성하세요]

---

## 🔗 Holonic Links

### ⬆️ Spawned From
- [{meeting_id}](../meetings/{meeting_id}.md)

"""
        
        file_path.write_text(content, encoding="utf-8")
        print(f"  ✅ Task 생성: {task_id} - {action} (담당: {assignee}, 기한: {due_date})")
        
        return task_id
    
    def spawn(self, meeting_id: str) -> Dict[str, List[str]]:
        """Meeting에서 Decision/Task 생성"""
        print("=" * 60)
        print(f"🚀 Meeting에서 Decision/Task 생성: {meeting_id}")
        print("=" * 60)
        print()
        
        meeting = self.load_meeting(meeting_id)
        if not meeting:
            print(f"❌ Meeting을 찾을 수 없음: {meeting_id}")
            return {"decisions": [], "tasks": []}
        
        spawned = {"decisions": [], "tasks": []}
        
        # Decisions 생성
        decisions = meeting.get("decisions", [])
        if decisions:
            print("📋 Decisions 생성:")
            for d in decisions:
                did = self.create_decision(d, meeting_id)
                spawned["decisions"].append(did)
        else:
            print("📋 생성할 Decision 없음")
        
        print()
        
        # Tasks 생성
        actions = meeting.get("next_actions", [])
        if actions:
            print("✅ Tasks 생성:")
            for a in actions:
                tid = self.create_task(a, meeting_id)
                spawned["tasks"].append(tid)
        else:
            print("✅ 생성할 Task 없음")
        
        print()
        print("=" * 60)
        print(f"✅ 완료 - Decision {len(spawned['decisions'])}개, Task {len(spawned['tasks'])}개 생성")
        print("=" * 60)
        
        return spawned


def main():
    parser = argparse.ArgumentParser(description="Meeting에서 Decision/Task 자동 생성")
    parser.add_argument("meeting_id", help="Meeting holon_id")
    
    args = parser.parse_args()
    
    script_dir = Path(__file__).parent
    spawner = MeetingSpawner(str(script_dir))
    spawner.spawn(args.meeting_id)


if __name__ == "__main__":
    main()
