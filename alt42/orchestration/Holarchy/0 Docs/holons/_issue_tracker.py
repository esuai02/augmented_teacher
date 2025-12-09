#!/usr/bin/env python3
"""
🔍 Issue Tracker - 미완성/추가 개발 필요 지점 자동 탐지 및 리뷰
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

기능:
1. 코드/문서에서 미완성 지점 자동 스캔
2. 이슈 목록 관리 및 우선순위 지정
3. 대화형 리뷰 모드로 하나씩 처리
4. 수정 제안 및 자동 적용

이슈 카테고리:
- placeholder: [...], TBD, 미정 등
- hardcoded: 하드코딩된 설정값
- api_gap: API-Dashboard 연동 미비
- error_handling: 에러 처리 미흡
- integration: 기능 간 연동 미비
"""

import json
import re
import os
import logging
from pathlib import Path
from datetime import datetime
from typing import Dict, List, Optional, Tuple
from dataclasses import dataclass, field, asdict
from enum import Enum

# 로깅 설정
logger = logging.getLogger("holarchy.issue_tracker")


class IssueSeverity(Enum):
    """이슈 심각도"""
    CRITICAL = "critical"    # 즉시 수정 필요
    HIGH = "high"           # 중요
    MEDIUM = "medium"       # 보통
    LOW = "low"             # 낮음
    INFO = "info"           # 정보


class IssueCategory(Enum):
    """이슈 카테고리"""
    PLACEHOLDER = "placeholder"      # [...], TBD 등
    HARDCODED = "hardcoded"          # 하드코딩된 값
    API_GAP = "api_gap"              # API-UI 연동 미비
    ERROR_HANDLING = "error_handling" # 에러 처리 미흡
    INTEGRATION = "integration"       # 기능 간 연동 미비
    INCOMPLETE = "incomplete"         # 미완성 기능


class IssueStatus(Enum):
    """이슈 상태"""
    OPEN = "open"           # 열림
    IN_PROGRESS = "in_progress"  # 진행 중
    RESOLVED = "resolved"   # 해결됨
    WONTFIX = "wontfix"     # 수정 안함
    DEFERRED = "deferred"   # 연기됨


@dataclass
class Issue:
    """개별 이슈"""
    id: str
    category: str
    severity: str
    file: str
    line: int
    description: str
    suggestion: str
    status: str = "open"
    code_snippet: str = ""
    review_note: str = ""
    created_at: str = ""
    updated_at: str = ""
    
    def to_dict(self) -> dict:
        return asdict(self)


class IssueTracker:
    """미완성 지점 탐지 및 관리"""
    
    # 스캔 패턴 정의
    PATTERNS = {
        "placeholder": [
            (r'\[\.\.\.?\]', "Placeholder [...]"),
            (r'\[TBD\]', "TBD placeholder"),
            (r'\[TODO\]', "TODO placeholder"),
            (r'"TBD"', 'TBD 문자열'),
            (r"'TBD'", 'TBD 문자열'),
            (r'담당자 미정', '담당자 미정'),
            (r'\[.*?를 정의하세요\]', '정의 필요 placeholder'),
            (r'\[.*? 1\]', '번호형 placeholder'),
        ],
        "hardcoded": [
            (r'USE_LOCAL_EMBEDDING\s*=\s*True', '하드코딩된 임베딩 설정'),
            (r'TOP_K_CHUNKS\s*=\s*\d+', '하드코딩된 청크 수'),
            (r'SIMILARITY_THRESHOLD\s*=\s*[\d.]+', '하드코딩된 임계값'),
            (r'return\s+0\.5\s*#.*기본값', '하드코딩된 기본값'),
            (r'default=\d+\.?\d*', '하드코딩된 기본값'),
        ],
        "error_handling": [
            (r'except.*:\s*\n\s*pass', 'Silent fail (except: pass)'),
            (r'except\s*:', '빈 except 절'),
        ],
    }
    
    # API-Dashboard 연동 체크용
    EXPECTED_API_USAGE = {
        "/api/review": "리뷰 API",
        "/api/rag/index": "RAG 인덱싱 API",
        "/api/rag/search": "RAG 검색 API",
        "/api/tag": "태깅 API",
        "/api/tag/preview": "태그 미리보기 API",
        "/api/check": "검증 API",
        "/api/link": "링크 동기화 API",
    }
    
    # 연동 체크용
    INTEGRATION_CHECKS = [
        ("Vector RAG", "_vector_rag.py", "Brain Engine", "_brain_engine.py", "부분 연동"),
        ("Review 시스템", "_brain_engine.py", "Dashboard", "dashboard.html", "미연동"),
        ("Tag 시스템", "_auto_tagger.py", "검색 필터", "_brain_engine.py", "미연동"),
        ("Memory Layer", "_memory_engine.py", "문서 자동 이동", "_health_check.py", "미연동"),
    ]
    
    def __init__(self, base_path: str = None):
        if base_path:
            self.base_path = Path(base_path)
        else:
            self.base_path = Path(__file__).parent
        
        self.docs_root = self.base_path.parent
        self.reports_path = self.docs_root / "reports"
        self.reports_path.mkdir(parents=True, exist_ok=True)
        
        self.issues_file = self.reports_path / "tracked_issues.json"
        self.issues: List[Issue] = []
        
        # 기존 이슈 로드
        self._load_issues()
    
    def _load_issues(self):
        """저장된 이슈 로드"""
        if self.issues_file.exists():
            try:
                with open(self.issues_file, "r", encoding="utf-8") as f:
                    data = json.load(f)
                self.issues = [Issue(**item) for item in data.get("issues", [])]
            except (json.JSONDecodeError, FileNotFoundError, UnicodeDecodeError) as e:
                logger.debug(f"이슈 파일 로드 실패: {e}")
                self.issues = []
    
    def _save_issues(self):
        """이슈 저장"""
        data = {
            "updated_at": datetime.now().isoformat(),
            "total_count": len(self.issues),
            "open_count": len([i for i in self.issues if i.status == "open"]),
            "issues": [i.to_dict() for i in self.issues]
        }
        with open(self.issues_file, "w", encoding="utf-8") as f:
            json.dump(data, f, ensure_ascii=False, indent=2)
    
    def _generate_id(self, category: str, file: str, line: int) -> str:
        """이슈 ID 생성"""
        file_short = Path(file).stem[:10]
        return f"{category[:3].upper()}-{file_short}-L{line}"
    
    def _get_code_snippet(self, filepath: Path, line: int, context: int = 2) -> str:
        """코드 스니펫 추출"""
        try:
            with open(filepath, "r", encoding="utf-8") as f:
                lines = f.readlines()

            start = max(0, line - context - 1)
            end = min(len(lines), line + context)

            snippet = []
            for i in range(start, end):
                prefix = ">>>" if i == line - 1 else "   "
                snippet.append(f"{prefix} {i+1:4}| {lines[i].rstrip()}")

            return "\n".join(snippet)
        except (FileNotFoundError, UnicodeDecodeError) as e:
            logger.debug(f"코드 스니펫 추출 실패 [{filepath.name}]: {e}")
            return ""
    
    def scan_file(self, filepath: Path) -> List[Issue]:
        """단일 파일 스캔"""
        issues = []
        
        if not filepath.exists():
            return issues
        
        try:
            content = filepath.read_text(encoding="utf-8")
            lines = content.split("\n")
        except (FileNotFoundError, UnicodeDecodeError) as e:
            logger.debug(f"파일 읽기 실패 [{filepath.name}]: {e}")
            return issues
        
        # 패턴별 스캔
        for category, patterns in self.PATTERNS.items():
            for pattern, description in patterns:
                for match in re.finditer(pattern, content, re.MULTILINE):
                    # 라인 번호 계산
                    line_num = content[:match.start()].count("\n") + 1
                    
                    # 심각도 결정
                    if category == "error_handling":
                        severity = "high"
                    elif category == "placeholder" and "정의하세요" in description:
                        severity = "medium"
                    elif category == "hardcoded":
                        severity = "low"
                    else:
                        severity = "medium"
                    
                    issue_id = self._generate_id(category, str(filepath), line_num)
                    
                    # 중복 체크
                    if any(i.id == issue_id for i in self.issues):
                        continue
                    
                    issue = Issue(
                        id=issue_id,
                        category=category,
                        severity=severity,
                        file=str(filepath.relative_to(self.docs_root.parent)),
                        line=line_num,
                        description=description,
                        suggestion=self._generate_suggestion(category, match.group()),
                        code_snippet=self._get_code_snippet(filepath, line_num),
                        created_at=datetime.now().isoformat()
                    )
                    issues.append(issue)
        
        return issues
    
    def _generate_suggestion(self, category: str, matched: str) -> str:
        """수정 제안 생성"""
        suggestions = {
            "placeholder": "구체적인 값으로 대체하거나 실제 데이터를 입력하세요",
            "hardcoded": "환경변수 또는 설정 파일로 분리하세요",
            "error_handling": "구체적인 예외 처리 또는 로깅을 추가하세요",
            "api_gap": "Dashboard에서 해당 API를 호출하도록 연동하세요",
            "integration": "두 시스템 간 데이터 흐름을 구현하세요",
        }
        return suggestions.get(category, "검토 후 개선이 필요합니다")
    
    def scan_all(self) -> List[Issue]:
        """전체 스캔"""
        print("=" * 60)
        print("🔍 Issue Tracker - 전체 스캔")
        print("=" * 60)
        print()
        
        new_issues = []
        
        # Python 파일 스캔
        py_files = list(self.base_path.glob("*.py"))
        print(f"📁 Python 파일: {len(py_files)}개")
        
        for filepath in py_files:
            if filepath.name.startswith("_") and not filepath.name.startswith("__"):
                issues = self.scan_file(filepath)
                new_issues.extend(issues)
                if issues:
                    print(f"   📄 {filepath.name}: {len(issues)}개 이슈")
        
        # 서버 파일
        server_file = self.docs_root / "server.py"
        if server_file.exists():
            issues = self.scan_file(server_file)
            new_issues.extend(issues)
            if issues:
                print(f"   📄 server.py: {len(issues)}개 이슈")
        
        # Dashboard 파일
        dashboard_file = self.docs_root / "dashboard.html"
        if dashboard_file.exists():
            issues = self.scan_file(dashboard_file)
            new_issues.extend(issues)
            if issues:
                print(f"   📄 dashboard.html: {len(issues)}개 이슈")
        
        # API-Dashboard 연동 체크
        api_issues = self._check_api_dashboard_gap()
        new_issues.extend(api_issues)
        if api_issues:
            print(f"   🔗 API 연동 갭: {len(api_issues)}개 이슈")
        
        # 기능 간 연동 체크
        integration_issues = self._check_integration_gaps()
        new_issues.extend(integration_issues)
        if integration_issues:
            print(f"   🔗 기능 연동 갭: {len(integration_issues)}개 이슈")
        
        # 새 이슈 추가
        self.issues.extend(new_issues)
        self._save_issues()
        
        print()
        print(f"✅ 스캔 완료: 새로운 이슈 {len(new_issues)}개 발견")
        print(f"📊 총 이슈: {len(self.issues)}개 (열림: {len([i for i in self.issues if i.status == 'open'])}개)")
        print("=" * 60)
        
        return new_issues
    
    def _check_api_dashboard_gap(self) -> List[Issue]:
        """API-Dashboard 연동 갭 체크"""
        issues = []
        
        dashboard_file = self.docs_root / "dashboard.html"
        if not dashboard_file.exists():
            return issues
        
        dashboard_content = dashboard_file.read_text(encoding="utf-8")
        
        for api, desc in self.EXPECTED_API_USAGE.items():
            if api not in dashboard_content:
                issue_id = f"API-{api.replace('/', '-')[1:]}"
                
                if any(i.id == issue_id for i in self.issues):
                    continue
                
                issue = Issue(
                    id=issue_id,
                    category="api_gap",
                    severity="medium",
                    file="0 Docs/dashboard.html",
                    line=0,
                    description=f"{desc} ({api}) - Dashboard에서 미사용",
                    suggestion=f"Dashboard에 {api} 호출 UI를 추가하세요",
                    created_at=datetime.now().isoformat()
                )
                issues.append(issue)
        
        return issues
    
    def _check_integration_gaps(self) -> List[Issue]:
        """기능 간 연동 갭 체크"""
        issues = []
        
        for sys_a, file_a, sys_b, file_b, status in self.INTEGRATION_CHECKS:
            if status == "미연동":
                issue_id = f"INT-{sys_a[:5]}-{sys_b[:5]}"
                
                if any(i.id == issue_id for i in self.issues):
                    continue
                
                issue = Issue(
                    id=issue_id,
                    category="integration",
                    severity="low",
                    file=f"{file_a} ↔ {file_b}",
                    line=0,
                    description=f"{sys_a} ↔ {sys_b} 연동 필요",
                    suggestion=f"{sys_a}의 데이터를 {sys_b}에서 활용하도록 연동 구현",
                    created_at=datetime.now().isoformat()
                )
                issues.append(issue)
        
        return issues
    
    def list_issues(self, category: str = None, status: str = None, 
                   severity: str = None) -> List[Issue]:
        """이슈 목록 필터링"""
        filtered = self.issues
        
        if category:
            filtered = [i for i in filtered if i.category == category]
        if status:
            filtered = [i for i in filtered if i.status == status]
        if severity:
            filtered = [i for i in filtered if i.severity == severity]
        
        return filtered
    
    def get_issue(self, issue_id: str) -> Optional[Issue]:
        """특정 이슈 조회"""
        for issue in self.issues:
            if issue.id == issue_id:
                return issue
        return None
    
    def update_issue(self, issue_id: str, status: str = None, 
                    review_note: str = None) -> bool:
        """이슈 업데이트"""
        for issue in self.issues:
            if issue.id == issue_id:
                if status:
                    issue.status = status
                if review_note:
                    issue.review_note = review_note
                issue.updated_at = datetime.now().isoformat()
                self._save_issues()
                return True
        return False
    
    def review_interactive(self):
        """대화형 리뷰 모드"""
        open_issues = [i for i in self.issues if i.status == "open"]
        
        if not open_issues:
            print("✅ 열린 이슈가 없습니다!")
            return
        
        print("=" * 70)
        print("📝 대화형 이슈 리뷰 모드")
        print("=" * 70)
        print()
        print(f"📊 열린 이슈: {len(open_issues)}개")
        print()
        print("선택지:")
        print("  [r] resolved - 해결됨")
        print("  [w] wontfix  - 수정 안함")
        print("  [d] deferred - 연기")
        print("  [s] skip     - 건너뛰기")
        print("  [q] quit     - 종료")
        print()
        print("-" * 70)
        
        for i, issue in enumerate(open_issues, 1):
            print()
            print(f"[{i}/{len(open_issues)}] {issue.id}")
            print(f"   카테고리: {issue.category} | 심각도: {issue.severity}")
            print(f"   파일: {issue.file}:{issue.line}")
            print(f"   설명: {issue.description}")
            print(f"   제안: {issue.suggestion}")
            
            if issue.code_snippet:
                print()
                print("   코드:")
                for line in issue.code_snippet.split("\n"):
                    print(f"   {line}")
            
            print()
            choice = input("   선택 [r/w/d/s/q]: ").strip().lower()
            
            if choice == 'q':
                print("\n👋 리뷰 종료")
                break
            elif choice == 's':
                continue
            elif choice == 'r':
                note = input("   리뷰 노트 (선택): ").strip()
                self.update_issue(issue.id, status="resolved", review_note=note or "리뷰 완료")
                print("   ✅ resolved로 변경됨")
            elif choice == 'w':
                note = input("   이유: ").strip()
                self.update_issue(issue.id, status="wontfix", review_note=note or "수정 불필요")
                print("   ⏭️ wontfix로 변경됨")
            elif choice == 'd':
                note = input("   연기 이유: ").strip()
                self.update_issue(issue.id, status="deferred", review_note=note or "추후 처리")
                print("   📅 deferred로 변경됨")
        
        print()
        print("=" * 70)
        self._print_summary()
    
    def _print_summary(self):
        """요약 출력"""
        status_counts = {}
        severity_counts = {}
        category_counts = {}
        
        for issue in self.issues:
            status_counts[issue.status] = status_counts.get(issue.status, 0) + 1
            severity_counts[issue.severity] = severity_counts.get(issue.severity, 0) + 1
            category_counts[issue.category] = category_counts.get(issue.category, 0) + 1
        
        print("📊 이슈 요약")
        print("-" * 40)
        print("상태별:")
        for status, count in sorted(status_counts.items()):
            emoji = {"open": "🔴", "resolved": "✅", "wontfix": "⏭️", "deferred": "📅"}.get(status, "⚪")
            print(f"   {emoji} {status}: {count}개")
        
        print("\n심각도별:")
        for severity, count in sorted(severity_counts.items()):
            emoji = {"critical": "🔴", "high": "🟠", "medium": "🟡", "low": "🟢", "info": "🔵"}.get(severity, "⚪")
            print(f"   {emoji} {severity}: {count}개")
        
        print("\n카테고리별:")
        for category, count in sorted(category_counts.items()):
            print(f"   📁 {category}: {count}개")
    
    def print_issues(self, issues: List[Issue] = None, show_snippet: bool = False):
        """이슈 목록 출력"""
        if issues is None:
            issues = self.issues
        
        if not issues:
            print("📋 이슈 없음")
            return
        
        print()
        print(f"{'#':3} {'ID':25} {'카테고리':15} {'심각도':10} {'상태':12} {'파일':30}")
        print("-" * 100)
        
        for i, issue in enumerate(issues, 1):
            status_emoji = {"open": "🔴", "resolved": "✅", "wontfix": "⏭️", "deferred": "📅"}.get(issue.status, "⚪")
            severity_emoji = {"critical": "🔴", "high": "🟠", "medium": "🟡", "low": "🟢"}.get(issue.severity, "⚪")
            
            file_short = issue.file[-28:] if len(issue.file) > 28 else issue.file
            
            print(f"{i:3} {issue.id:25} {issue.category:15} {severity_emoji} {issue.severity:8} "
                  f"{status_emoji} {issue.status:10} {file_short}")
            
            if show_snippet and issue.code_snippet:
                print(f"    └─ {issue.description}")
        
        print()


def main():
    import argparse
    
    parser = argparse.ArgumentParser(description="Issue Tracker")
    parser.add_argument("action", choices=["scan", "list", "review", "show", "update", "summary"],
                       help="scan: 전체 스캔, list: 목록, review: 대화형 리뷰, show: 상세, update: 상태 변경, summary: 요약")
    parser.add_argument("--id", help="이슈 ID")
    parser.add_argument("--category", "-c", help="카테고리 필터")
    parser.add_argument("--status", "-s", help="상태 필터 (open, resolved, wontfix, deferred)")
    parser.add_argument("--severity", help="심각도 필터")
    parser.add_argument("--set-status", help="상태 변경")
    parser.add_argument("--note", help="리뷰 노트")
    parser.add_argument("--snippet", action="store_true", help="코드 스니펫 표시")
    
    args = parser.parse_args()
    
    tracker = IssueTracker()
    
    if args.action == "scan":
        tracker.scan_all()
        
    elif args.action == "list":
        issues = tracker.list_issues(
            category=args.category,
            status=args.status or "open",
            severity=args.severity
        )
        tracker.print_issues(issues, show_snippet=args.snippet)
        
    elif args.action == "review":
        tracker.review_interactive()
        
    elif args.action == "show":
        if not args.id:
            print("❌ --id 필요")
            return
        issue = tracker.get_issue(args.id)
        if issue:
            print(f"\n📋 이슈: {issue.id}")
            print(f"   카테고리: {issue.category}")
            print(f"   심각도: {issue.severity}")
            print(f"   상태: {issue.status}")
            print(f"   파일: {issue.file}:{issue.line}")
            print(f"   설명: {issue.description}")
            print(f"   제안: {issue.suggestion}")
            if issue.review_note:
                print(f"   리뷰 노트: {issue.review_note}")
            if issue.code_snippet:
                print(f"\n   코드:\n{issue.code_snippet}")
        else:
            print(f"❌ 이슈 없음: {args.id}")
            
    elif args.action == "update":
        if not args.id:
            print("❌ --id 필요")
            return
        if tracker.update_issue(args.id, status=args.set_status, review_note=args.note):
            print(f"✅ 업데이트 완료: {args.id}")
        else:
            print(f"❌ 이슈 없음: {args.id}")
            
    elif args.action == "summary":
        tracker._print_summary()


if __name__ == "__main__":
    main()

