#!/usr/bin/env python3
"""
sync_docs.py - 문서 자동 동기화

engine_config.php (SSOT)를 기반으로 문서들을 자동으로 업데이트합니다.

Usage:
    python sync_docs.py [--dry-run] [--verbose]

Features:
    - SYSTEM_STATUS.yaml 자동 업데이트
    - 문서 내 에이전트 수 자동 수정
    - 버전 히스토리 자동 추가

Author: AI Agent Integration Team
Created: 2025-12-08
"""

import re
import os
import sys
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple
import yaml

# 같은 폴더의 parse_agents 모듈 임포트
sys.path.insert(0, str(Path(__file__).parent))
from parse_agents import parse_engine_config, ParseResult, AgentDefinition


class DocumentSynchronizer:
    """문서 동기화 실행기"""
    
    def __init__(self, base_path: Optional[Path] = None, dry_run: bool = False):
        self.base_path = base_path or Path(__file__).parent.parent
        self.dry_run = dry_run
        self.ssot: Optional[ParseResult] = None
        self.changes: List[Tuple[str, str]] = []  # (file, change_description)
        
    def load_ssot(self) -> ParseResult:
        """SSOT (engine_config.php) 로드"""
        config_path = self.base_path / "agents" / "engine_core" / "config" / "engine_config.php"
        self.ssot = parse_engine_config(config_path)
        return self.ssot
    
    def sync_all(self) -> List[Tuple[str, str]]:
        """모든 문서 동기화"""
        if self.ssot is None:
            self.load_ssot()
        
        self.changes = []
        
        # 1. SYSTEM_STATUS.yaml 동기화
        status_file = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "SYSTEM_STATUS.yaml"
        if status_file.exists():
            self._sync_system_status(status_file)
        
        # 2. quantum-orchestration-design.md 동기화 (에이전트 수만)
        design_file = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "quantum-orchestration-design.md"
        if design_file.exists():
            self._sync_agent_count_in_md(design_file)
        
        # 3. quantum-learning-model.md 동기화 (에이전트 수만)
        model_file = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "quantum-learning-model.md"
        if model_file.exists():
            self._sync_agent_count_in_md(model_file)
        
        return self.changes
    
    def _sync_system_status(self, file_path: Path):
        """SYSTEM_STATUS.yaml 동기화"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        changes_made = []
        
        # 1. 에이전트 수 업데이트
        # count: 21 → count: 22
        content, n = re.subn(
            r'(current_implementation:\s+count:\s*)(\d+)',
            lambda m: f"{m.group(1)}{self.ssot.total_count}",
            content
        )
        if n > 0:
            changes_made.append(f"agent count → {self.ssot.total_count}")
        
        # 2. last_updated 업데이트
        today = datetime.now().strftime("%Y-%m-%d")
        content, n = re.subn(
            r'(last_updated:\s*["\']?)[\d-]+(["\']?)',
            f'\\g<1>{today}\\g<2>',
            content
        )
        if n > 0:
            changes_made.append(f"last_updated → {today}")
        
        # 변경사항이 있으면 저장
        if content != original_content:
            if not self.dry_run:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
            self.changes.append((str(file_path.name), ", ".join(changes_made)))
    
    def _sync_agent_count_in_md(self, file_path: Path):
        """마크다운 문서의 에이전트 수 동기화"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        original_content = content
        changes_made = []
        
        # "21개 에이전트" → "22개 에이전트"
        content, n = re.subn(
            r'(\d+)개\s*(교육\s*)?AI\s*에이전트',
            f'{self.ssot.total_count}개 \\g<2>AI 에이전트',
            content
        )
        if n > 0:
            changes_made.append(f"에이전트 수 → {self.ssot.total_count}개")
        
        # "21단계" → "22단계"
        content, n = re.subn(
            r'(\d+)단계\s*에이전트',
            f'{self.ssot.total_count}단계 에이전트',
            content
        )
        if n > 0:
            changes_made.append(f"단계 수 → {self.ssot.total_count}단계")
        
        # 변경사항이 있으면 저장
        if content != original_content:
            if not self.dry_run:
                with open(file_path, 'w', encoding='utf-8') as f:
                    f.write(content)
            self.changes.append((str(file_path.name), ", ".join(changes_made)))
    
    def generate_agents_markdown(self) -> str:
        """에이전트 목록 마크다운 생성"""
        lines = [
            "# 에이전트 목록",
            "",
            f"> 자동 생성됨: {datetime.now().isoformat()}",
            f"> 소스: engine_config.php v{self.ssot.version}",
            "",
            "## 전체 에이전트 ({} 개)".format(self.ssot.total_count),
            "",
            "| ID | 영문명 | 한글명 | 카테고리 |",
            "|----|----|----|----|",
        ]
        
        for agent in self.ssot.agents:
            lines.append(f"| {agent.id} | {agent.name} | {agent.kr_name} | {agent.category} |")
        
        lines.extend([
            "",
            "## 카테고리별 분류",
            ""
        ])
        
        for category, agent_ids in self.ssot.categories.items():
            agent_names = [
                f"{a.kr_name} ({a.id})"
                for a in self.ssot.agents
                if a.id in agent_ids
            ]
            lines.append(f"### {category}")
            for name in agent_names:
                lines.append(f"- {name}")
            lines.append("")
        
        return "\n".join(lines)
    
    def save_agents_markdown(self, output_path: Optional[Path] = None):
        """에이전트 목록 마크다운 저장"""
        if output_path is None:
            output_path = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "AGENT_LIST.md"
        
        content = self.generate_agents_markdown()
        
        if not self.dry_run:
            with open(output_path, 'w', encoding='utf-8') as f:
                f.write(content)
        
        self.changes.append((str(output_path.name), "generated from SSOT"))


def main():
    import argparse
    
    parser = argparse.ArgumentParser(description="Synchronize documents with engine_config.php")
    parser.add_argument("--dry-run", "-n", action="store_true", help="Show what would be changed without making changes")
    parser.add_argument("--verbose", "-v", action="store_true", help="Verbose output")
    parser.add_argument("--generate-list", "-g", action="store_true", help="Generate AGENT_LIST.md")
    parser.add_argument("--base-path", type=str, default=None, help="Base path for orchestration folder")
    
    args = parser.parse_args()
    
    try:
        base_path = Path(args.base_path) if args.base_path else None
        syncer = DocumentSynchronizer(base_path, dry_run=args.dry_run)
        
        print("🔄 SSOT (engine_config.php) 로드 중...")
        syncer.load_ssot()
        print(f"   ✓ {syncer.ssot.total_count}개 에이전트 발견")
        print(f"   ✓ 버전: {syncer.ssot.version}")
        
        if args.dry_run:
            print("\n⚠️  DRY RUN 모드 - 실제 변경 없음")
        
        print("\n🔄 문서 동기화 중...")
        changes = syncer.sync_all()
        
        if args.generate_list:
            print("\n📝 AGENT_LIST.md 생성 중...")
            syncer.save_agents_markdown()
        
        print("\n" + "=" * 50)
        if syncer.changes:
            print(f"✅ {len(syncer.changes)}개 파일 {'업데이트 예정' if args.dry_run else '업데이트됨'}:")
            for file, change in syncer.changes:
                print(f"   • {file}: {change}")
        else:
            print("✓ 모든 문서가 이미 동기화되어 있습니다.")
        
        print("=" * 50)
        
    except FileNotFoundError as e:
        print(f"❌ Error: {e}")
        sys.exit(1)
    except Exception as e:
        print(f"❌ Error: {e}")
        if args.verbose:
            import traceback
            traceback.print_exc()
        sys.exit(1)


if __name__ == "__main__":
    main()

