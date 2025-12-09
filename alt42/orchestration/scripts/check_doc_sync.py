#!/usr/bin/env python3
"""
check_doc_sync.py - 문서-시스템 동기화 상태 검사

engine_config.php (SSOT)와 문서들 간의 동기화 상태를 자동으로 검사합니다.

Usage:
    python check_doc_sync.py [--fix] [--verbose]

Features:
    - engine_config.php와 문서 간 에이전트 정의 비교
    - SYSTEM_STATUS.yaml sync_status 자동 업데이트
    - 불일치 항목 리포트 생성

Author: AI Agent Integration Team
Created: 2025-12-08
"""

import re
import os
import sys
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Tuple, Optional
from dataclasses import dataclass

# 같은 폴더의 parse_agents 모듈 임포트
sys.path.insert(0, str(Path(__file__).parent))
from parse_agents import parse_engine_config, ParseResult, AgentDefinition


@dataclass
class SyncIssue:
    """동기화 이슈"""
    file: str
    line: Optional[int]
    issue_type: str  # 'missing', 'mismatch', 'extra'
    expected: str
    actual: str
    severity: str  # 'critical', 'high', 'medium', 'low'


@dataclass
class SyncReport:
    """동기화 검사 결과"""
    checked_at: str
    ssot_version: str
    ssot_agent_count: int
    files_checked: List[str]
    issues: List[SyncIssue]
    is_synced: bool


class DocumentSyncChecker:
    """문서 동기화 검사기"""
    
    def __init__(self, base_path: Optional[Path] = None):
        self.base_path = base_path or Path(__file__).parent.parent
        self.ssot: Optional[ParseResult] = None
        self.issues: List[SyncIssue] = []
        
    def load_ssot(self) -> ParseResult:
        """SSOT (engine_config.php) 로드"""
        config_path = self.base_path / "agents" / "engine_core" / "config" / "engine_config.php"
        self.ssot = parse_engine_config(config_path)
        return self.ssot
    
    def check_all(self) -> SyncReport:
        """모든 문서 검사"""
        if self.ssot is None:
            self.load_ssot()
        
        self.issues = []
        files_checked = []
        
        # 1. quantum-orchestration-design.md 검사
        design_file = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "quantum-orchestration-design.md"
        if design_file.exists():
            self._check_orchestration_design(design_file)
            files_checked.append(str(design_file.relative_to(self.base_path)))
        
        # 2. quantum-learning-model.md 검사
        model_file = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "quantum-learning-model.md"
        if model_file.exists():
            self._check_learning_model(model_file)
            files_checked.append(str(model_file.relative_to(self.base_path)))
        
        # 3. SYSTEM_STATUS.yaml 검사
        status_file = self.base_path / "Holarchy" / "0 Docs" / "quantum modeling" / "SYSTEM_STATUS.yaml"
        if status_file.exists():
            self._check_system_status(status_file)
            files_checked.append(str(status_file.relative_to(self.base_path)))
        
        # 4. registry.yaml 검사 (ontology_brain)
        registry_file = self.base_path.parent / "ontology_brain" / "agents" / "registry.yaml"
        if registry_file.exists():
            self._check_registry(registry_file)
            files_checked.append(str(registry_file))
        
        return SyncReport(
            checked_at=datetime.now().isoformat(),
            ssot_version=self.ssot.version,
            ssot_agent_count=self.ssot.total_count,
            files_checked=files_checked,
            issues=self.issues,
            is_synced=len(self.issues) == 0
        )
    
    def _check_orchestration_design(self, file_path: Path):
        """quantum-orchestration-design.md 검사"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # 에이전트 수 검사
        agent_count_match = re.search(r'(\d+)개\s*(?:교육\s*)?AI\s*에이전트', content)
        if agent_count_match:
            doc_count = int(agent_count_match.group(1))
            if doc_count != self.ssot.total_count:
                self.issues.append(SyncIssue(
                    file=str(file_path.name),
                    line=None,
                    issue_type='mismatch',
                    expected=f"{self.ssot.total_count}개 에이전트",
                    actual=f"{doc_count}개 에이전트",
                    severity='high'
                ))
        
        # AGENTS 정의 검사
        for agent in self.ssot.agents:
            # 에이전트 이름이 문서에 있는지 확인 (공백 무시)
            kr_name_no_space = agent.kr_name.replace(" ", "")
            # 원본 이름 또는 공백 제거 버전이 있으면 OK
            if agent.kr_name not in content and kr_name_no_space not in content:
                self.issues.append(SyncIssue(
                    file=str(file_path.name),
                    line=None,
                    issue_type='missing',
                    expected=f"Agent {agent.id}: {agent.kr_name}",
                    actual="not found",
                    severity='medium'
                ))
    
    def _check_learning_model(self, file_path: Path):
        """quantum-learning-model.md 검사"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # 에이전트 단계 수 검사
        stage_match = re.search(r'(\d+)단계\s*에이전트', content)
        if stage_match:
            doc_stages = int(stage_match.group(1))
            if doc_stages != self.ssot.total_count:
                self.issues.append(SyncIssue(
                    file=str(file_path.name),
                    line=None,
                    issue_type='mismatch',
                    expected=f"{self.ssot.total_count}단계",
                    actual=f"{doc_stages}단계",
                    severity='medium'
                ))
    
    def _check_system_status(self, file_path: Path):
        """SYSTEM_STATUS.yaml 검사"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # 에이전트 수 검사
        count_match = re.search(r'count:\s*(\d+)', content)
        if count_match:
            doc_count = int(count_match.group(1))
            if doc_count != self.ssot.total_count:
                self.issues.append(SyncIssue(
                    file=str(file_path.name),
                    line=None,
                    issue_type='mismatch',
                    expected=f"count: {self.ssot.total_count}",
                    actual=f"count: {doc_count}",
                    severity='high'
                ))
    
    def _check_registry(self, file_path: Path):
        """registry.yaml 검사"""
        with open(file_path, 'r', encoding='utf-8') as f:
            content = f.read()
        
        # 각 에이전트가 registry에 있는지 확인
        for agent in self.ssot.agents:
            agent_key = f"agent_{agent.id:02d}:"
            if agent_key not in content:
                self.issues.append(SyncIssue(
                    file=str(file_path.name),
                    line=None,
                    issue_type='missing',
                    expected=f"{agent_key} (Agent {agent.id}: {agent.name})",
                    actual="not found",
                    severity='high'
                ))
    
    def generate_report(self, report: SyncReport) -> str:
        """동기화 리포트 생성"""
        lines = [
            "=" * 60,
            "📋 문서-시스템 동기화 검사 결과",
            "=" * 60,
            f"검사 시간: {report.checked_at}",
            f"SSOT 버전: {report.ssot_version}",
            f"SSOT 에이전트 수: {report.ssot_agent_count}",
            f"검사된 파일: {len(report.files_checked)}개",
            "",
        ]
        
        if report.is_synced:
            lines.append("✅ 모든 문서가 동기화되어 있습니다!")
        else:
            lines.append(f"⚠️ {len(report.issues)}개의 동기화 이슈 발견")
            lines.append("")
            lines.append("-" * 60)
            
            # 심각도별 그룹핑
            by_severity = {'critical': [], 'high': [], 'medium': [], 'low': []}
            for issue in report.issues:
                by_severity[issue.severity].append(issue)
            
            for severity in ['critical', 'high', 'medium', 'low']:
                issues = by_severity[severity]
                if issues:
                    emoji = {'critical': '🔴', 'high': '🟠', 'medium': '🟡', 'low': '🟢'}[severity]
                    lines.append(f"\n{emoji} {severity.upper()} ({len(issues)}개)")
                    for issue in issues:
                        lines.append(f"  • [{issue.file}] {issue.issue_type}: {issue.expected} → {issue.actual}")
        
        lines.append("")
        lines.append("-" * 60)
        lines.append("검사된 파일:")
        for f in report.files_checked:
            lines.append(f"  - {f}")
        
        return "\n".join(lines)


def main():
    import argparse
    
    parser = argparse.ArgumentParser(description="Check document-system synchronization")
    parser.add_argument("--verbose", "-v", action="store_true", help="Verbose output")
    parser.add_argument("--json", action="store_true", help="Output as JSON")
    parser.add_argument("--base-path", type=str, default=None, help="Base path for orchestration folder")
    
    args = parser.parse_args()
    
    try:
        base_path = Path(args.base_path) if args.base_path else None
        checker = DocumentSyncChecker(base_path)
        
        print("🔍 SSOT (engine_config.php) 로드 중...")
        checker.load_ssot()
        print(f"   ✓ {checker.ssot.total_count}개 에이전트 발견")
        
        print("\n🔍 문서 동기화 검사 중...")
        report = checker.check_all()
        
        if args.json:
            import json
            result = {
                "checked_at": report.checked_at,
                "ssot_version": report.ssot_version,
                "ssot_agent_count": report.ssot_agent_count,
                "files_checked": report.files_checked,
                "issues": [
                    {
                        "file": i.file,
                        "line": i.line,
                        "issue_type": i.issue_type,
                        "expected": i.expected,
                        "actual": i.actual,
                        "severity": i.severity
                    }
                    for i in report.issues
                ],
                "is_synced": report.is_synced
            }
            print(json.dumps(result, ensure_ascii=False, indent=2))
        else:
            print(checker.generate_report(report))
        
        # 동기화되지 않았으면 exit code 1
        sys.exit(0 if report.is_synced else 1)
        
    except FileNotFoundError as e:
        print(f"❌ Error: {e}")
        sys.exit(2)
    except Exception as e:
        print(f"❌ Error: {e}")
        if args.verbose:
            import traceback
            traceback.print_exc()
        sys.exit(2)


if __name__ == "__main__":
    main()

