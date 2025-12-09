#!/usr/bin/env python3
"""
Holarchy Self-Healing CLI v2.0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
- 모든 명령은 "기록만, 중단 없음"
- 시스템은 절대 멈추지 않음
- 수정은 항상 선택사항
"""

import argparse
import sys
import logging
from pathlib import Path

# 로깅 설정
logger = logging.getLogger("holarchy.cli")

# 같은 폴더의 모듈 import
script_dir = Path(__file__).parent
sys.path.insert(0, str(script_dir))


def cmd_check(args):
    """Self-Healing 검사 (기록만, 중단 없음)"""
    from _validate import HolarchyValidator
    validator = HolarchyValidator(str(script_dir.parent))
    validator.run_all_validations()
    # Self-Healing: 항상 성공


def cmd_risk(args):
    """위험도 점수 확인"""
    import json
    reports_path = script_dir.parent / "reports" / "risk_score.json"
    
    print("=" * 60)
    print("📊 Self-Healing 위험도 점수")
    print("   (참고용 - 낮아도 시스템 정상 작동)")
    print("=" * 60)
    print()
    
    if not reports_path.exists():
        print("⚠️  risk_score.json 없음")
        print("💡 'python _cli.py check' 먼저 실행하세요")
        return
    
    with open(reports_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    
    score = data.get("overall_score", 0)
    risk_level = data.get("risk_level", "unknown")
    
    emoji = "🟢" if risk_level == "low" else ("🟡" if risk_level == "medium" else "🔴")
    print(f"{emoji} 전체 점수: {score}% ({risk_level.upper()})")
    print()
    
    print("📋 세부 점수:")
    breakdown = data.get("breakdown", {})
    for key, value in breakdown.items():
        print(f"   {key}: {value}%")
    print()
    
    print("ℹ️  " + data.get("system_note", ""))


def cmd_suggest(args):
    """자동 수정 추천 보기"""
    import json
    reports_path = script_dir.parent / "reports" / "suggestions.json"
    
    print("=" * 60)
    print("💡 Self-Healing 수정 추천")
    print("   (선택사항 - 무시해도 시스템 정상 작동)")
    print("=" * 60)
    print()
    
    if not reports_path.exists():
        print("⚠️  suggestions.json 없음")
        print("💡 'python _cli.py check' 먼저 실행하세요")
        return
    
    with open(reports_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    
    suggestions = data.get("suggestions", [])
    
    if not suggestions:
        print("✅ 수정 추천 없음 - 모든 문서가 양호합니다")
        return
    
    for item in suggestions:
        print(f"📄 {item['holon_id']} (현재 {item['current_score']}% → 목표 {item['target_score']}%)")
        for s in item.get("suggestions", []):
            print(f"   → {s}")
        print()


def cmd_report(args):
    """현재 이슈 리포트 보기"""
    import json
    reports_path = script_dir.parent / "reports" / "issues.json"
    
    print("=" * 60)
    print("📋 Self-Healing 이슈 리포트")
    print("   (기록만 - 시스템 중단 없음)")
    print("=" * 60)
    print()
    
    if not reports_path.exists():
        print("⚠️  issues.json 없음")
        print("💡 'python _cli.py check' 먼저 실행하세요")
        return
    
    with open(reports_path, "r", encoding="utf-8") as f:
        data = json.load(f)
    
    print(f"📅 생성 시각: {data.get('generated_at', 'N/A')}")
    print(f"📊 총 이슈: {data.get('total_issues', 0)}개")
    print()
    
    by_severity = data.get("by_severity", {})
    print("📈 심각도별:")
    print(f"   🔴 error: {by_severity.get('error', 0)}")
    print(f"   🟡 warning: {by_severity.get('warning', 0)}")
    print(f"   ℹ️  info: {by_severity.get('info', 0)}")
    print()
    
    issues = data.get("issues", [])
    if issues:
        print("📋 이슈 목록 (상위 10개):")
        print("-" * 60)
        for issue in issues[:10]:
            severity_emoji = {"error": "🔴", "warning": "🟡", "info": "ℹ️"}.get(issue["severity"], "❓")
            print(f"{severity_emoji} [{issue['holon_id']}] {issue['message']}")
        
        if len(issues) > 10:
            print(f"   ... 외 {len(issues) - 10}개")
    print()
    
    print("ℹ️  " + data.get("system_note", ""))


# 기존 validate 명령어 유지 (check로 리다이렉트)
def cmd_validate(args):
    """검증 실행 (check로 리다이렉트)"""
    print("ℹ️  validate 명령이 check로 변경되었습니다.")
    print("   Self-Healing 모드: 기록만, 시스템 중단 없음")
    print()
    cmd_check(args)


def cmd_link(args):
    """양방향 링크 동기화"""
    from _auto_link import AutoLinker
    linker = AutoLinker(str(script_dir))
    linker.run()


def cmd_create(args):
    """새 Holon 생성"""
    from _create_holon import HolonCreator
    
    print("=" * 60)
    print("📄 새 Holon 문서 생성")
    print("=" * 60)
    print()
    
    creator = HolonCreator(str(script_dir))
    holon_id = creator.create_holon(
        holon_type=args.type,
        title=args.title,
        parent_id=args.parent,
        module=args.module
    )
    
    print()
    print("💡 다음 단계:")
    print("   1. 생성된 파일의 [대괄호] 부분을 채우세요")
    print("   2. python _cli.py link  # 링크 동기화")
    print("   3. python _cli.py validate  # 검증")


def cmd_spawn(args):
    """Meeting에서 Decision/Task 생성"""
    from _spawn_meeting import MeetingSpawner
    spawner = MeetingSpawner(str(script_dir))
    spawner.spawn(args.meeting_id)


def cmd_place(args):
    """문서 자동 배치 - HTE 모듈 폴더에 파일명 규칙 적용"""
    from _document_placer import DocumentPlacer
    
    placer = DocumentPlacer()
    
    if args.file:
        # 파일에서 읽기
        file_path = Path(args.file)
        if not file_path.exists():
            print(f"❌ 파일을 찾을 수 없습니다: {args.file}")
            return
        text = file_path.read_text(encoding="utf-8")
    elif args.text:
        # 직접 텍스트 입력
        text = args.text
    else:
        # 대화형 입력
        print("=" * 60)
        print("📝 문서 내용을 입력하세요 (빈 줄 2번으로 종료):")
        print("=" * 60)
        lines = []
        empty_count = 0
        while True:
            try:
                line = input()
                if line == "":
                    empty_count += 1
                    if empty_count >= 2:
                        break
                else:
                    empty_count = 0
                lines.append(line)
            except EOFError:
                break
        text = "\n".join(lines)
    
    if not text.strip():
        print("❌ 문서 내용이 비어있습니다")
        return
    
    # 배치 및 생성 (하이브리드 방식: parent_id 포함)
    parent_id = getattr(args, 'parent', None)
    result = placer.create_document(text, doc_type=args.type or "auto", parent_id=parent_id)
    
    print()
    print("💡 생성 완료! (하이브리드 방식)")
    print(f"   모듈: {result['module']}")
    print(f"   파일: {result['filename']}")
    print(f"   부모: [{result.get('parent_short', 'ROOT')}]")
    print(f"   경로: {result['filepath']}")


def cmd_tag(args):
    """🏷️ Auto-Tagger v3 - 멀티레이어 자동 태깅"""
    from _auto_tagger import AutoTagger
    
    script_dir = Path(__file__).parent
    tagger = AutoTagger(str(script_dir))
    
    if args.action == "all":
        # 전체 문서 태깅
        tagger.tag_all_documents(dry_run=args.dry_run)
        
    elif args.action == "file":
        # 단일 파일 태깅
        if not args.file:
            print("❌ --file 옵션 필요")
            return
        
        filepath = Path(args.file)
        if not filepath.is_absolute():
            filepath = script_dir / args.file
        
        tags = tagger.tag_document(filepath, dry_run=args.dry_run)
        if tags:
            print(f"\n🏷️ {filepath.name} 태그:")
            tagger.print_tags(tags)
        
    elif args.action == "show":
        # 태그 확인 (저장 안함)
        if args.file:
            filepath = Path(args.file)
            if not filepath.is_absolute():
                filepath = script_dir / args.file
            
            tags = tagger.tag_document(filepath, dry_run=True)
            if tags:
                print(f"\n🏷️ {filepath.name} 태그 (미리보기):")
                tagger.print_tags(tags)
        else:
            # 전체 문서 태그 미리보기
            tagger.tag_all_documents(dry_run=True)


def cmd_rag(args):
    """🧠 Vector RAG - W(의지) 기반 의미적 검색"""
    from _vector_rag import VectorRAGEngine
    
    script_dir = Path(__file__).parent
    engine = VectorRAGEngine(str(script_dir))
    
    if args.action == "index":
        # W.will.drive 인덱싱
        engine.index_all_documents(force_reindex=args.force)
        
    elif args.action == "search":
        # 의미적 검색
        if not args.query:
            print("❌ --query 옵션 필요")
            return
        
        # 인덱싱 안되어 있으면 로드
        if not engine.documents:
            engine.index_all_documents()
        
        results = engine.search_by_w(args.query, top_k=args.top_k)
        engine.print_search_results(results, args.query)
        
    elif args.action == "hybrid":
        # 하이브리드 검색 (벡터 + 그래프)
        if not args.query:
            print("❌ --query 옵션 필요")
            return
        
        if not engine.documents:
            engine.index_all_documents()
        
        results = engine.hybrid_search(args.query, top_k=args.top_k)
        engine.print_search_results(results, args.query)


def cmd_review(args):
    """📝 자연어 리뷰로 문서 점수 자동 보정"""
    from _brain_engine import BrainEngine
    
    script_dir = Path(__file__).parent
    engine = BrainEngine(str(script_dir))
    
    if args.batch:
        # 대화형 배치 모드
        print("=" * 60)
        print("📝 자연어 리뷰 모드 (종료: 'q' 또는 'quit')")
        print("=" * 60)
        print()
        print("📌 리뷰 예시:")
        print("   positive: '좋아', '정확함', '중요', '핵심임'")
        print("   negative: '헷갈림', '틀림', '관련 없음'")
        print("   actionable: '다시 봐야함', '수정 필요', '보완해야함'")
        print()
        
        while True:
            holon_id = input("📄 문서 ID: ").strip()
            if holon_id.lower() in ['q', 'quit', 'exit']:
                break
            
            review_text = input("💬 리뷰: ").strip()
            if not review_text:
                continue
            
            result = engine.review_document(holon_id, review_text)
            engine.print_review_result(result)
    else:
        # 단일 리뷰
        if not args.holon_id or not args.review_text:
            print("❌ 문서 ID와 리뷰 텍스트가 필요합니다")
            print("   사용법: python _cli.py review <holon_id> '<리뷰>'")
            print("   예시: python _cli.py review hte-doc-001 '중요하고 정확함'")
            return
        
        result = engine.review_document(args.holon_id, args.review_text)
        engine.print_review_result(result)


def cmd_issues(args):
    """🔍 미완성 지점 탐지 및 리뷰"""
    from _issue_tracker import IssueTracker
    
    script_dir = Path(__file__).parent
    tracker = IssueTracker(str(script_dir))
    
    if args.action == "scan":
        # 전체 스캔
        tracker.scan_all()
        
    elif args.action == "list":
        # 목록 표시
        issues = tracker.list_issues(
            category=args.category,
            status=args.status or "open",
            severity=args.severity
        )
        tracker.print_issues(issues, show_snippet=args.snippet)
        
    elif args.action == "review":
        # 대화형 리뷰
        tracker.review_interactive()
        
    elif args.action == "show":
        # 상세 보기
        if not args.id:
            print("❌ --id 옵션 필요")
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
        # 상태 변경
        if not args.id:
            print("❌ --id 옵션 필요")
            return
        
        if tracker.update_issue(args.id, status=args.set_status, review_note=args.note):
            print(f"✅ 업데이트 완료: {args.id}")
        else:
            print(f"❌ 이슈 없음: {args.id}")
            
    elif args.action == "summary":
        # 요약
        tracker._print_summary()


def cmd_status(args):
    """시스템 상태 확인"""
    import json
    import re
    
    print("=" * 60)
    print("📊 Holarchy 시스템 상태")
    print("=" * 60)
    print()
    
    # 문서 카운트
    holons_path = script_dir
    meetings_path = script_dir.parent / "meetings"
    decisions_path = script_dir.parent / "decisions"
    tasks_path = script_dir.parent / "tasks"
    
    def count_holons(path):
        if not path.exists():
            return 0
        return len([f for f in path.glob("*.md") if not f.name.startswith("_")])
    
    holons = count_holons(holons_path)
    meetings = count_holons(meetings_path)
    decisions = count_holons(decisions_path)
    tasks = count_holons(tasks_path)
    
    print("📂 문서 현황:")
    print(f"   holons/    : {holons}개")
    print(f"   meetings/  : {meetings}개")
    print(f"   decisions/ : {decisions}개")
    print(f"   tasks/     : {tasks}개")
    print(f"   ─────────────────")
    print(f"   총계       : {holons + meetings + decisions + tasks}개")
    print()
    
    # 상태별 카운트
    status_count = {"draft": 0, "active": 0, "completed": 0, "archived": 0, "pending": 0}
    
    for folder in [holons_path, meetings_path, decisions_path, tasks_path]:
        if not folder.exists():
            continue
        for md_file in folder.glob("*.md"):
            if md_file.name.startswith("_"):
                continue
            content = md_file.read_text(encoding="utf-8")
            json_match = re.search(r'```json\s*\n(.*?)\n```', content, re.DOTALL)
            if json_match:
                try:
                    holon = json.loads(json_match.group(1))
                    status = holon.get("meta", {}).get("status", "unknown")
                    status_count[status] = status_count.get(status, 0) + 1
                except json.JSONDecodeError as e:
                    logger.debug(f"Holon JSON 파싱 실패 [{md_file.name}]: {e}")
    
    print("📋 상태별 현황:")
    for status, count in status_count.items():
        if count > 0:
            print(f"   {status}: {count}개")
    print()
    
    # 최근 검증 결과 (간단히) - v3.0 API
    from _validate import HolarchyValidator
    validator = HolarchyValidator(str(script_dir.parent))
    validator.load_holons()
    validator.score_structure()
    validator.score_completeness()
    validator.score_resonance()
    validator.score_links()
    
    # 90% 기준 검증
    passed_docs = [s for s in validator.scores.values() if s.passed]
    needs_improvement = [s for s in validator.scores.values() if not s.passed]
    avg_score = sum(s.total for s in validator.scores.values()) / len(validator.scores) if validator.scores else 0
    
    print("🔍 검증 상태:")
    if avg_score >= 0.90:
        print(f"   ✅ 평균 완성도 {avg_score:.0%} (기준 90% 통과)")
    else:
        print(f"   📝 평균 완성도 {avg_score:.0%} (기준 90% 미달)")
        print(f"   ✅ 통과: {len(passed_docs)}개 / 📝 개선권장: {len(needs_improvement)}개")
    print()
    print("=" * 60)


def cmd_meeting(args):
    """회의록 자동 파싱 & Holon 생성"""
    from _meeting_parser import MeetingParser
    
    script_dir = Path(__file__).parent
    parser = MeetingParser(str(script_dir))
    
    if args.file:
        # 파일에서 읽기
        file_path = Path(args.file)
        if not file_path.exists():
            print(f"❌ 파일을 찾을 수 없습니다: {args.file}")
            return
        text = file_path.read_text(encoding="utf-8")
    elif args.text:
        # 직접 텍스트 입력
        text = args.text
    else:
        # 대화형 입력
        print("=" * 60)
        print("📝 회의록을 입력하세요 (빈 줄 2번으로 종료):")
        print("=" * 60)
        lines = []
        empty_count = 0
        while True:
            try:
                line = input()
                if line == "":
                    empty_count += 1
                    if empty_count >= 2:
                        break
                else:
                    empty_count = 0
                lines.append(line)
            except EOFError:
                break
        text = "\n".join(lines)
    
    if not text.strip():
        print("❌ 회의록 내용이 비어있습니다")
        return
    
    # 파싱 및 생성
    result = parser.parse_and_create(text, auto_spawn=not args.no_spawn)
    
    print()
    print("💡 다음 단계:")
    print(f"   1. 생성된 파일 확인: 0 Docs/meetings/{result['meeting_id']}*.md")
    print("   2. python _cli.py link  # 링크 동기화")
    print("   3. python _cli.py check  # 검증")


def cmd_chunk(args):
    """W 기반 Active Chunk 관리"""
    from _chunk_engine import ChunkManager
    
    script_dir = Path(__file__).parent
    manager = ChunkManager(str(script_dir.parent))
    
    if args.action == "generate":
        print("📂 Holon 로드 중...")
        manager.load_holons()
        print(f"   로드된 문서: {len(manager.holons)}개")
        print()
        
        print("🧮 W 기반 Salience 계산 중...")
        manager.generate_chunks()
        manager.save_chunks()
        print(f"   생성된 Chunk: {len(manager.chunks)}개")
        print()
        
        manager.print_report()
    
    else:  # show
        manager.load_holons()
        chunks = manager.load_chunks()
        if chunks:
            manager.chunks = chunks
            manager.root_w = manager.find_root_w()
            manager.print_report()
        else:
            print("❌ 저장된 Chunk 없음")
            print("💡 'python _cli.py chunk generate' 먼저 실행하세요")


def cmd_meta(args):
    """Meta-Research Engine"""
    from _meta_research_engine import MetaResearchEngine
    
    script_dir = Path(__file__).parent
    engine = MetaResearchEngine(str(script_dir.parent))
    
    if args.action == "report":
        result = engine.run_analysis()
        report = engine.generate_report(result)
        report_file = engine.save_report(report)
        
        engine.print_summary(result)
        print(f"📄 리포트 생성: {report_file}")
        print()
        print("🔔 리포트를 검토하고 제안을 승인/거부해주세요.")
    else:  # analyze
        result = engine.run_analysis()
        engine.print_summary(result)
        
        matrix_file = engine.save_matrix()
        print(f"📁 유사도 매트릭스: {matrix_file}")


def cmd_health(args):
    """시스템 건강 점검"""
    from _health_check import HealthCheckEngine
    
    script_dir = Path(__file__).parent
    engine = HealthCheckEngine(str(script_dir.parent))
    
    report = engine.run_all_checks()
    engine.print_report(report)
    
    if args.action == "report":
        report_file = engine.save_report(report)
        print()
        print(f"📄 리포트 저장: {report_file}")


def cmd_memory(args):
    """🧠 Enterprise Memory Engine - 뇌의 장기기억 원리 적용"""
    from _memory_engine import MemoryEngine
    
    script_dir = Path(__file__).parent
    engine = MemoryEngine(str(script_dir))
    
    if args.action == "report":
        # 전체 리포트
        engine.print_memory_report()
        
    elif args.action == "score":
        # M-score 상위 문서
        print("=" * 70)
        print("🧠 M-score 기반 문서 순위 (뇌의 기억 원리)")
        print("=" * 70)
        print()
        
        scores = engine.analyze_all_documents()
        
        print("📊 M-score = (사용빈도×0.3) + (성과영향×0.3) + (규칙반영×0.2)")
        print("           + (목표정렬×0.15) + (감정임팩트×0.05)")
        print()
        
        print(f"{'순위':^4} {'문서':^40} {'M-score':^8} {'레이어':^15}")
        print("-" * 70)
        
        layer_names = {
            "ltm_permanent": "🔷 영구보존",
            "ltm_extended": "🟢 장기(1년)",
            "compressed_ltm": "🟡 중기(6월)",
            "working_memory": "🟠 단기(3월)"
        }
        
        for i, score in enumerate(scores[:20], 1):
            layer = layer_names.get(score.memory_layer.value, "?")
            core = "⭐" if score.is_core else ""
            print(f"{i:3}. {score.filename[:38]:38} {score.m_score:6.3f}   {layer} {core}")
        
        if len(scores) > 20:
            print(f"     ... 외 {len(scores) - 20}개")
        
        print()
        
    elif args.action == "layer":
        # 레이어별 문서
        from _memory_engine import MemoryLayer
        
        print("=" * 70)
        print("🧠 메모리 레이어별 문서 분포")
        print("=" * 70)
        print()
        
        layer_info = {
            MemoryLayer.LTM_PERMANENT: ("🔷", "영구 보존 (Core)", "M ≥ 0.8"),
            MemoryLayer.LTM_EXTENDED: ("🟢", "장기 보존 (1년)", "0.5 ≤ M < 0.8"),
            MemoryLayer.COMPRESSED_LTM: ("🟡", "중기 보존 (6개월)", "0.2 ≤ M < 0.5"),
            MemoryLayer.WORKING_MEMORY: ("🟠", "단기 보존 (3개월)", "M < 0.2")
        }
        
        for layer, (emoji, name, condition) in layer_info.items():
            docs = engine.get_by_layer(layer)
            print(f"{emoji} {name} [{condition}] - {len(docs)}개")
            print("-" * 50)
            
            for doc in docs[:5]:
                days = doc.days_until_action
                if days == -1:
                    time_info = "영구"
                elif days <= 0:
                    time_info = "⚠️ 조치필요"
                else:
                    time_info = f"{days}일 남음"
                print(f"   {doc.filename[:35]:35} M={doc.m_score:.3f} | {time_info}")
            
            if len(docs) > 5:
                print(f"   ... 외 {len(docs) - 5}개")
            print()
        
    elif args.action == "prune":
        # 시냅스 가지치기 (망각 메커니즘)
        print("=" * 70)
        print("🧹 시냅스 가지치기 (Synapse Pruning)")
        print("   뇌의 망각 메커니즘 - 중요하지 않은 기억 정리")
        print("=" * 70)
        print()
        
        dry_run = not args.execute
        result = engine.run_synapse_pruning(dry_run=dry_run)
        
        if dry_run:
            print("📋 시뮬레이션 결과 (실제 실행 아님):")
        else:
            print("✅ 실행 결과:")
        
        print()
        
        if result["compressed"]:
            print(f"📦 압축 대상: {len(result['compressed'])}개")
            for f in result["compressed"][:5]:
                print(f"   - {f}")
            if len(result["compressed"]) > 5:
                print(f"   ... 외 {len(result['compressed']) - 5}개")
        else:
            print("✅ 압축 대상 없음 - 모든 문서가 보존 기간 내")
        
        if result["archived"]:
            print(f"\n🗄️ 아카이브됨: {len(result['archived'])}개")
        
        print()
        
        if dry_run:
            print("💡 실제 실행: python _cli.py memory prune --execute")
        
    elif args.action == "save":
        # JSON 리포트 저장
        engine.print_memory_report()
        report_path = engine.save_report()
        print()
        print(f"📄 리포트 저장: {report_path}")
        
    else:
        # 기본: report
        engine.print_memory_report()


def cmd_brain(args):
    """🧠 Enterprise Brain Search & Memory System v2.0"""
    from _brain_engine import BrainEngine, MemoryProfile
    
    script_dir = Path(__file__).parent
    engine = BrainEngine(str(script_dir))
    
    if args.action == "search":
        # 검색 실행
        query = args.query or ""
        engine.print_search_results(query, limit=15)
        
    elif args.action == "profile":
        # 프로필 목록 또는 변경
        if args.set:
            try:
                profile = MemoryProfile(args.set)
                engine.set_profile(profile)
                print(f"✅ 프로필 변경: {profile.value}")
                print()
                engine.print_weights_panel()
            except ValueError:
                print(f"❌ 잘못된 프로필: {args.set}")
                print("   사용 가능: fast_fresh, wisdom, balanced, pattern, trend")
        else:
            engine.print_profiles()
        
    elif args.action == "weights":
        # 가중치 직접 설정
        if args.wr is not None or args.wp is not None or args.wv is not None or args.wm is not None:
            wr = args.wr if args.wr is not None else engine.weights.recency
            wp = args.wp if args.wp is not None else engine.weights.popularity
            wv = args.wv if args.wv is not None else engine.weights.relevance
            wm = args.wm if args.wm is not None else engine.weights.importance
            
            engine.set_weights(wr, wp, wv, wm)
            print("✅ 가중치 변경됨 (정규화 적용)")
            print()
        
        engine.print_weights_panel()
        
    elif args.action == "layer":
        # 레이어별 요약
        engine.print_layer_summary()
        
    elif args.action == "eval":
        # 문서 평가
        if not args.id:
            print("❌ 문서 ID가 필요합니다: --id <holon_id>")
            return
        
        engine.evaluate_document(
            args.id,
            accuracy=args.accuracy or 0,
            importance=args.importance or 0,
            reusability=args.reusability or 0,
            authority=args.authority or 0,
            strategic_value=args.strategic or 0
        )
        print(f"✅ 평가 저장: {args.id}")
        print()
        print("📊 평가 항목:")
        print(f"   정확성: {args.accuracy or 0}/5")
        print(f"   중요성: {args.importance or 0}/5")
        print(f"   재사용: {args.reusability or 0}/5")
        print(f"   신뢰도: {args.authority or 0}/5")
        print(f"   전략적 가치: {args.strategic or 0}/5")
        
    elif args.action == "access":
        # 접근 기록
        if not args.id:
            print("❌ 문서 ID가 필요합니다: --id <holon_id>")
            return
        
        engine.record_access(args.id)
        print(f"✅ 접근 기록: {args.id}")
        
    elif args.action == "save":
        # 리포트 저장
        engine.print_search_results()
        report_path = engine.save_report()
        print()
        print(f"📄 리포트 저장: {report_path}")
        
    else:
        # 기본: search
        engine.print_search_results()


def cmd_attach(args):
    """📎 첨부파일 관리"""
    from _attachment_manager import AttachmentManager
    
    script_dir = Path(__file__).parent
    manager = AttachmentManager(str(script_dir))
    
    if args.action == "add":
        # 첨부파일 추가
        if not args.holon or not args.file:
            print("❌ 홀론 ID와 파일 경로가 필요합니다")
            print("   사용법: python _cli.py attach add --holon <ID> --file <경로>")
            return
        
        manager.add_attachment(
            args.holon, 
            args.file, 
            description=args.desc or "",
            copy_file=not args.no_copy
        )
        
    elif args.action == "remove":
        # 첨부파일 제거
        if not args.holon or not args.file:
            print("❌ 홀론 ID와 파일명이 필요합니다")
            print("   사용법: python _cli.py attach remove --holon <ID> --file <파일명>")
            return
        
        manager.remove_attachment(
            args.holon, 
            args.file,
            delete_file=args.delete
        )
        
    elif args.action == "list":
        # 첨부파일 목록
        if not args.holon:
            print("❌ 홀론 ID가 필요합니다")
            print("   사용법: python _cli.py attach list --holon <ID>")
            return
        
        manager.print_attachments(args.holon)
        
    elif args.action == "sync":
        # 모든 홀론에 attachments 필드 동기화
        print("=" * 60)
        print("🔄 모든 홀론에 attachments 필드 동기화")
        print("=" * 60)
        print()
        
        result = manager.sync_all_holons()
        
        print(f"✅ 업데이트: {result['updated']}개")
        print(f"⏭️  스킵 (이미 있음): {result['skipped']}개")
        if result['error'] > 0:
            print(f"❌ 오류: {result['error']}개")
        
        print()
        print("💡 이제 모든 홀론에서 첨부파일을 관리할 수 있습니다:")
        print("   python _cli.py attach add --holon <ID> --file <경로>")


def cmd_mission(args):
    """🎯 Mission Propagation Engine - 상위 미션 하위 전파"""
    from _mission_propagation import MissionPropagationEngine
    
    script_dir = Path(__file__).parent
    engine = MissionPropagationEngine(str(script_dir))
    
    if args.action == "tree":
        # 미션 트리 시각화
        engine.print_mission_tree()
        
    elif args.action == "preview":
        # 전파 시뮬레이션
        engine.print_propagation_preview()
        
    elif args.action == "propagate":
        # 실제 전파 실행
        dry_run = not args.execute
        result = engine.propagate_mission(dry_run=dry_run)
        
        print("=" * 70)
        if dry_run:
            print("🎯 Mission Propagation (시뮬레이션)")
        else:
            print("🎯 Mission Propagation (실행 완료)")
        print("=" * 70)
        print()
        
        if not result["updated"]:
            print("✅ 모든 문서가 미션과 일치합니다.")
            return
        
        for update in result["updated"]:
            emoji = "✅" if not dry_run else "📝"
            print(f"{emoji} {update['holon_id']}")
            print(f"   현재: {update['old_drive']}")
            print(f"   변경: {update['new_drive']}")
            print()
        
        print("-" * 70)
        if dry_run:
            print(f"📊 {result['needs_update']}개 문서 업데이트 예정")
            print("💡 실제 적용: python _cli.py mission propagate --execute")
        else:
            print(f"✅ {len(result['updated'])}개 문서 업데이트 완료")
        
    elif args.action == "save":
        # 리포트 저장
        engine.print_mission_tree()
        report_path = engine.save_report()
        print()
        print(f"📄 리포트 저장: {report_path}")
        
    else:
        # 기본: tree
        engine.print_mission_tree()


def cmd_hierarchy(args):
    """🏛️ Hierarchy Engine - 수직적 위계질서 관리"""
    from _hierarchy_engine import HierarchyEngine, DirectiveType, SignalType
    
    script_dir = Path(__file__).parent
    engine = HierarchyEngine(str(script_dir))
    
    if args.action == "tree":
        # 위계질서 트리 시각화
        engine.print_hierarchy_tree()
        
    elif args.action == "signal":
        # 신호 흐름 현황
        engine.print_signal_flow()
        
    elif args.action == "chain":
        # 특정 홀론의 수직 체인
        if not args.id:
            print("❌ --id 옵션 필요")
            return
        
        engine.load_hierarchy()
        chain = engine.get_vertical_chain(args.id)
        
        if not chain:
            print(f"❌ 홀론 없음: {args.id}")
            return
        
        print("=" * 70)
        print(f"🏛️ Vertical Chain: {args.id}")
        print("=" * 70)
        print()
        
        print("⬆️ Ancestors (상위 체인):")
        for i, anc in enumerate(chain["ancestors"]):
            print(f"   {'  ' * i}└─ {anc}")
        print()
        
        print(f"📍 Current: {args.id} (depth: {chain['depth']})")
        print()
        
        print("↔️ Siblings (형제):")
        for sib in chain["siblings"]:
            print(f"   ├─ {sib}")
        print()
        
        print("⬇️ Descendants (하위 체인):")
        for desc in chain["descendants"][:10]:
            print(f"   └─ {desc}")
        if len(chain["descendants"]) > 10:
            print(f"   ... 외 {len(chain['descendants']) - 10}개")
        
    elif args.action == "aggregate":
        # 하위 신호 집계
        if not args.id:
            print("❌ --id 옵션 필요")
            return
        
        engine.load_hierarchy()
        result = engine.aggregate_signals(args.id)
        
        if not result:
            print(f"❌ 홀론 없음: {args.id}")
            return
        
        print("=" * 70)
        print(f"⚡ Signal Aggregation: {args.id}")
        print("   하위 홀론들로부터 수집된 신호 분석")
        print("=" * 70)
        print()
        
        print(f"📊 총 신호: {result['total_signals']}개")
        print()
        
        if result["urgent_signals"]:
            print("🔴 긴급 신호:")
            for sig in result["urgent_signals"]:
                print(f"   [{sig['type']}] {sig['from']}")
                print(f"      {sig['content'][:60]}...")
            print()
        
        if result["by_type"]:
            print("📋 타입별 신호:")
            for stype, signals in result["by_type"].items():
                print(f"   {stype}: {len(signals)}개")
            print()
        
        if result["recommendations"]:
            print("💡 권고:")
            for rec in result["recommendations"]:
                print(f"   → {rec}")
        
    elif args.action == "propagate":
        # 미션 수직 전파
        dry_run = not args.execute
        engine.load_hierarchy()
        result = engine.propagate_mission(args.id)
        
        print("=" * 70)
        if dry_run:
            print("🏛️ Mission Propagation (시뮬레이션)")
        else:
            print("🏛️ Mission Propagation (실행)")
        print("=" * 70)
        print()
        
        print(f"📍 시작점: {result['source']}")
        print(f"📊 전파된 홀론: {len(result['propagated'])}개")
        print()
        
        for prop in result["propagated"][:10]:
            print(f"   {prop['holon_id']}")
            print(f"   └─ {prop['child_drive']}")
            print()
        
        if len(result["propagated"]) > 10:
            print(f"   ... 외 {len(result['propagated']) - 10}개")
        
    elif args.action == "save":
        # 리포트 저장
        engine.print_hierarchy_tree()
        report_path = engine.save_hierarchy_report()
        print()
        print(f"📄 리포트 저장: {report_path}")
        
    else:
        # 기본: tree
        engine.print_hierarchy_tree()


def cmd_sibling(args):
    """🤝 Sibling Collaboration Engine - 형제 협력 관리"""
    from _sibling_collaboration import SiblingCollaborationEngine, CollaborationType
    
    script_dir = Path(__file__).parent
    engine = SiblingCollaborationEngine(str(script_dir))
    
    if args.action == "groups":
        # 형제 그룹 표시
        engine.print_sibling_groups()
        
    elif args.action == "collab":
        # 협력 현황
        engine.load_siblings()
        engine.print_collaboration_status()
        
    elif args.action == "synergy":
        # 시너지 기회
        engine.print_synergy_report()
        
    elif args.action == "suggest":
        # 협력 제안
        if not args.id:
            print("❌ --id 옵션 필요")
            return
        
        engine.load_siblings()
        suggestions = engine.suggest_collaboration(args.id)
        
        if not suggestions:
            print(f"❌ 홀론 없음 또는 형제 없음: {args.id}")
            return
        
        print("=" * 70)
        print(f"🤝 Collaboration Suggestions: {args.id}")
        print("   시너지 점수 기반 형제 협력 추천")
        print("=" * 70)
        print()
        
        for i, sug in enumerate(suggestions, 1):
            score_bar = "█" * int(sug["synergy_score"] * 10)
            print(f"{i}. {sug['sibling_id']}")
            print(f"   {sug['sibling_title']}")
            print(f"   📊 시너지: {score_bar} {sug['synergy_score']:.0%}")
            print(f"   💡 {sug['reason']}")
            print(f"   🔧 추천 협력: {sug['suggested_type'].value}")
            print()
        
    elif args.action == "aggregate":
        # parent에 형제 협력 결과 집계
        if not args.id:
            print("❌ --id (parent ID) 옵션 필요")
            return
        
        engine.load_siblings()
        result = engine.aggregate_to_parent(args.id)
        
        if not result:
            print(f"❌ Parent 없음 또는 형제 없음: {args.id}")
            return
        
        print("=" * 70)
        print(f"📊 Sibling Collaboration Summary: {args.id}")
        print("   형제 홀론들의 협력 현황 집계")
        print("=" * 70)
        print()
        
        print(f"👥 형제 수: {result['total_siblings']}개")
        print()
        
        summary = result["collaboration_summary"]
        print("⚡ 협력 현황:")
        print(f"   총 협력: {summary['total']}개")
        print(f"   완료: {summary['completed']}개")
        print(f"   진행 중: {summary['in_progress']}개")
        print(f"   대기: {summary['pending']}개")
        print()
        
        print(f"✨ 발견된 시너지 기회: {result['synergies_detected']}개")
        print()
        
        if result["recommendations"]:
            print("💡 권고:")
            for rec in result["recommendations"]:
                print(f"   → {rec}")
        
    elif args.action == "share":
        # 정보 공유
        if not args.id or not args.content:
            print("❌ --id와 --content 옵션 필요")
            print("   예: sibling share --id hte-doc-001 --content '진행 상황 업데이트'")
            return
        
        engine.load_siblings()
        result = engine.share_info(
            from_holon=args.id,
            info_type=args.type or "update",
            content=args.content
        )
        
        if result["success"]:
            print(f"✅ 정보 공유 완료: {args.id} → {', '.join(result['to'])}")
        else:
            print(f"❌ 공유 실패: {result.get('error', 'Unknown')}")
        
    elif args.action == "request":
        # 리소스 요청
        if not args.id or not args.resource:
            print("❌ --id와 --resource 옵션 필요")
            print("   예: sibling request --id hte-doc-001 --resource 'AI 모델'")
            return
        
        engine.load_siblings()
        result = engine.request_resource(
            from_holon=args.id,
            resource_type=args.resource,
            reason=args.content or "리소스 필요"
        )
        
        if result["success"]:
            print(f"✅ 리소스 요청 생성: {result['request_id']}")
            print(f"   대상: {result['target']}")
            print(f"   리소스: {result['resource']}")
            print(f"   시너지 점수: {result['synergy_score']:.0%}")
        else:
            print(f"❌ 요청 실패: {result.get('error', 'Unknown')}")
            if result.get("suggestion"):
                print(f"   💡 {result['suggestion']}")
        
    elif args.action == "save":
        # 리포트 저장
        engine.print_sibling_groups()
        report_path = engine.save_collaboration_report()
        print()
        print(f"📄 리포트 저장: {report_path}")
        
    else:
        # 기본: groups
        engine.print_sibling_groups()


def cmd_help(args):
    """도움말"""
    print("""
╔══════════════════════════════════════════════════════════════╗
║       🔥 Holarchy Self-Healing CLI v2.0 - 명령어 가이드     ║
╠══════════════════════════════════════════════════════════════╣
║                                                              ║
║  ⚡ Self-Healing 핵심 명령어 (NEW)                           ║
║  ──────────────────────────────────────────────────────────  ║
║  check              문서 검사 (기록만, 시스템 중단 없음)     ║
║  risk               위험도 점수 확인 (참고용)                ║
║  suggest            자동 수정 추천 보기                      ║
║  report             현재 이슈 리포트 보기                    ║
║                                                              ║
║  * 모든 결과는 0 Docs/reports/에 저장                        ║
║  * 시스템은 절대 멈추지 않음                                 ║
║  * 수정은 항상 선택사항                                      ║
║                                                              ║
║  📋 기본 명령어                                              ║
║  ──────────────────────────────────────────────────────────  ║
║  status              시스템 상태 확인                        ║
║  link                양방향 링크 자동 동기화                 ║
║                                                              ║
║  📄 문서 생성                                                ║
║  ──────────────────────────────────────────────────────────  ║
║  create <type> <title> [--parent ID] [--module M00]          ║
║    type: strategy, structure, feature, meeting,              ║
║          decision, task                                      ║
║                                                              ║
║  🚀 자동 배치 (HTE 모듈)                                     ║
║  ──────────────────────────────────────────────────────────  ║
║  place               문서 → HTE 모듈 자동 배치               ║
║  place -f 파일       파일에서 읽어서 배치                    ║
║  place -t "내용"     텍스트로 직접 배치                      ║
║                                                              ║
║  * 내용 분석 → M00~M21 모듈 자동 선택                        ║
║  * 파일명: HTE_MXX_PYY_TZZ_V00_A00.md                        ║
║                                                              ║
║  🚀 회의 자동화                                              ║
║  ──────────────────────────────────────────────────────────  ║
║  meeting             회의록 자동 파싱 & Holon 생성           ║
║  spawn <meeting_id>  회의에서 Decision/Task 생성             ║
║                                                              ║
║  🧠 Working Memory                                           ║
║  ──────────────────────────────────────────────────────────  ║
║  chunk generate    W 기반 중요도로 Active Chunk 생성         ║
║  chunk show        현재 Active Chunk 표시                    ║
║                                                              ║
║  🔬 Meta-Research                                            ║
║  ──────────────────────────────────────────────────────────  ║
║  meta analyze      프로젝트 간 관계 분석                     ║
║  meta report       정제 제안 리포트 생성                     ║
║                                                              ║
║  🏥 Health (참고용)                                          ║
║  ──────────────────────────────────────────────────────────  ║
║  health check      8개 영역 건강 점검                        ║
║  health report     건강 점검 리포트 저장                     ║
║                                                              ║
║  🧠 Memory Engine (뇌의 장기기억 원리)                       ║
║  ──────────────────────────────────────────────────────────  ║
║  memory report     전체 메모리 상태 리포트                   ║
║  memory score      M-score 기반 문서 순위                    ║
║  memory layer      레이어별 문서 분포                        ║
║  memory prune      시냅스 가지치기 (망각 시뮬레이션)         ║
║                                                              ║
║  🧠 Brain Engine (4대 가중치 검색) NEW!                      ║
║  ──────────────────────────────────────────────────────────  ║
║  brain search      4대 가중치 기반 검색                      ║
║  brain search -q "쿼리"  쿼리로 검색                         ║
║  brain profile     프로필 목록 보기                          ║
║  brain profile --set wisdom  프로필 변경                     ║
║  brain weights     현재 가중치 확인                          ║
║  brain weights --wr 0.3 --wm 0.5  가중치 직접 설정           ║
║  brain layer       WM/LTM/Archive 레이어 요약                ║
║  brain eval --id <ID> --accuracy 5  문서 평가 (0~5)          ║
║                                                              ║
║  * Score = WR×Recency + WP×Popularity + WV×Relevance + WM×M  ║
║  * 프로필: fast_fresh, wisdom, balanced, pattern, trend      ║
║                                                              ║
║  🎯 Mission Propagation (미션 전파) NEW!                     ║
║  ──────────────────────────────────────────────────────────  ║
║  mission tree        미션 트리 시각화                        ║
║  mission preview     전파 시뮬레이션 (변경 없음)             ║
║  mission propagate   미션 전파 (시뮬레이션)                  ║
║  mission propagate --execute  실제 전파 실행                 ║
║  mission save        리포트 저장                             ║
║                                                              ║
║  * ROOT: "전국 규모 수학학원 자동화" → 모든 하위 문서 전파   ║
║  * W.will.drive 자동 업데이트                                ║
║                                                              ║
║  📎 Attachment (첨부파일 관리) NEW!                          ║
║  ──────────────────────────────────────────────────────────  ║
║  attach list --holon <ID>           첨부파일 목록            ║
║  attach add --holon <ID> --file <경로>  첨부파일 추가        ║
║  attach add --holon <ID> --file <경로> --desc "설명"         ║
║  attach remove --holon <ID> --file <파일명>  첨부파일 제거   ║
║  attach sync                        모든 홀론에 필드 추가    ║
║                                                              ║
║  * 첨부파일은 _attachments/<holon_id>/ 폴더에 복사됨         ║
║  * --no-copy: 원본 경로 그대로 사용                          ║
║  * --delete: remove 시 실제 파일도 삭제                      ║
║                                                              ║
║  🏛️ Hierarchy (수직적 위계질서) NEW!                         ║
║  ──────────────────────────────────────────────────────────  ║
║  hierarchy tree         수직적 위계질서 트리                 ║
║  hierarchy signal       하위→상위 신호 흐름 현황             ║
║  hierarchy chain --id <ID>  특정 홀론의 수직 체인            ║
║  hierarchy aggregate --id <ID>  하위 신호 집계               ║
║  hierarchy propagate --id <ID>  미션 수직 전파 (시뮬레이션)  ║
║  hierarchy propagate --id <ID> --execute  미션 전파 실행     ║
║  hierarchy save         리포트 저장                          ║
║                                                              ║
║  * 형제 제외 완벽한 수직적 위계질서                          ║
║  * ↓ Directive: 상위→하위 (미션/정책 전파)                   ║
║  * ↑ Signal: 하위→상위 (정보/피드백 전달)                    ║
║                                                              ║
║  🤝 Sibling (형제 협력) NEW!                                 ║
║  ──────────────────────────────────────────────────────────  ║
║  sibling groups         형제 그룹 현황                       ║
║  sibling collab         협력 현황                            ║
║  sibling synergy        시너지 기회 탐지                     ║
║  sibling suggest --id <ID>  협력 제안 받기                   ║
║  sibling aggregate --id <parent_ID>  형제 협력 결과 집계     ║
║  sibling share --id <ID> --content "내용"  정보 공유         ║
║  sibling request --id <ID> --resource "리소스"  리소스 요청  ║
║  sibling save           리포트 저장                          ║
║                                                              ║
║  * 같은 parent의 형제들 간 수평적 협력                       ║
║  * 시너지 점수 기반 협력 추천                                ║
║  * 협력 결과는 parent에 자동 집계                            ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
    """)


def main():
    parser = argparse.ArgumentParser(
        description="Holarchy Self-Healing CLI v2.0",
        formatter_class=argparse.RawDescriptionHelpFormatter
    )
    
    subparsers = parser.add_subparsers(dest="command", help="명령어")
    
    # Self-Healing 핵심 명령어
    # check (NEW - 핵심)
    parser_check = subparsers.add_parser("check", help="문서 검사 (Self-Healing)")
    parser_check.set_defaults(func=cmd_check)
    
    # risk (NEW)
    parser_risk = subparsers.add_parser("risk", help="위험도 점수 확인")
    parser_risk.set_defaults(func=cmd_risk)
    
    # suggest (NEW)
    parser_suggest = subparsers.add_parser("suggest", help="자동 수정 추천 보기")
    parser_suggest.set_defaults(func=cmd_suggest)
    
    # report (NEW)
    parser_report = subparsers.add_parser("report", help="현재 이슈 리포트")
    parser_report.set_defaults(func=cmd_report)
    
    # validate (기존 - check로 리다이렉트)
    parser_validate = subparsers.add_parser("validate", help="검증 (check로 리다이렉트)")
    parser_validate.set_defaults(func=cmd_validate)
    
    # link
    parser_link = subparsers.add_parser("link", help="양방향 링크 동기화")
    parser_link.set_defaults(func=cmd_link)
    
    # create
    parser_create = subparsers.add_parser("create", help="새 Holon 생성")
    parser_create.add_argument("type", choices=["strategy", "structure", "feature", "meeting", "decision", "task"])
    parser_create.add_argument("title", help="문서 제목")
    parser_create.add_argument("--parent", "-p", help="상위 holon_id")
    parser_create.add_argument("--module", "-m", default="M00_Astral", help="모듈")
    parser_create.set_defaults(func=cmd_create)
    
    # spawn
    parser_spawn = subparsers.add_parser("spawn", help="Meeting에서 Decision/Task 생성")
    parser_spawn.add_argument("meeting_id", help="Meeting holon_id")
    parser_spawn.set_defaults(func=cmd_spawn)
    
    # meeting (회의록 자동 파싱)
    parser_meeting = subparsers.add_parser("meeting", help="회의록 자동 파싱 & Holon 생성")
    parser_meeting.add_argument("--file", "-f", help="회의록 파일 경로 (.txt, .md)")
    parser_meeting.add_argument("--text", "-t", help="회의록 텍스트 직접 입력")
    parser_meeting.add_argument("--no-spawn", action="store_true", help="Decision/Task 자동 생성 안함")
    parser_meeting.set_defaults(func=cmd_meeting)
    
    # place (NEW - HTE 모듈 자동 배치 + 하이브리드 방식)
    parser_place = subparsers.add_parser("place", help="문서 자동 배치 - HTE 모듈 폴더에 생성")
    parser_place.add_argument("--file", "-f", help="문서 파일 경로")
    parser_place.add_argument("--text", "-t", help="문서 내용 직접 입력")
    parser_place.add_argument("--type", choices=["meeting", "strategy", "feature", "task", "decision", "auto"], 
                             default="auto", help="문서 타입")
    parser_place.add_argument("--parent", "-p", help="부모 holon_id (하이브리드 방식: 파일명에 [PARENT] 포함)")
    parser_place.set_defaults(func=cmd_place)
    
    # chunk
    parser_chunk = subparsers.add_parser("chunk", help="W 기반 Active Chunk 관리")
    parser_chunk.add_argument("action", choices=["generate", "show"], nargs="?", default="show",
                             help="generate: 새로 생성, show: 현재 표시")
    parser_chunk.set_defaults(func=cmd_chunk)
    
    # meta
    parser_meta = subparsers.add_parser("meta", help="Meta-Research Engine")
    parser_meta.add_argument("action", choices=["analyze", "report"], nargs="?", default="analyze",
                            help="analyze: 분석, report: 리포트 생성")
    parser_meta.set_defaults(func=cmd_meta)
    
    # health
    parser_health = subparsers.add_parser("health", help="시스템 건강 점검")
    parser_health.add_argument("action", choices=["check", "report"], nargs="?", default="check",
                              help="check: 점검, report: 리포트 저장")
    parser_health.set_defaults(func=cmd_health)
    
    # memory (뇌의 장기기억 원리)
    parser_memory = subparsers.add_parser("memory", help="🧠 Enterprise Memory Engine")
    parser_memory.add_argument("action", 
                              choices=["report", "score", "layer", "prune", "save"], 
                              nargs="?", default="report",
                              help="report: 전체 리포트, score: M-score 순위, "
                                   "layer: 레이어별 분포, prune: 시냅스 가지치기, "
                                   "save: JSON 저장")
    parser_memory.add_argument("--execute", action="store_true", 
                              help="prune 실제 실행 (기본: 시뮬레이션)")
    parser_memory.set_defaults(func=cmd_memory)
    
    # brain (NEW - 4대 가중치 검색 시스템)
    parser_brain = subparsers.add_parser("brain", help="🧠 Enterprise Brain Search v2.0")
    parser_brain.add_argument("action", 
                             choices=["search", "profile", "weights", "layer", "eval", "access", "save"], 
                             nargs="?", default="search",
                             help="search: 검색, profile: 프로필 관리, weights: 가중치 설정, "
                                  "layer: 레이어 요약, eval: 문서 평가, access: 접근 기록, save: 저장")
    # 검색 옵션
    parser_brain.add_argument("-q", "--query", help="검색 쿼리")
    # 프로필 옵션
    parser_brain.add_argument("--set", help="프로필 설정 (fast_fresh, wisdom, balanced, pattern, trend)")
    # 가중치 옵션
    parser_brain.add_argument("--wr", type=float, help="Recency 가중치 (최근성)")
    parser_brain.add_argument("--wp", type=float, help="Popularity 가중치 (조회수)")
    parser_brain.add_argument("--wv", type=float, help="Relevance 가중치 (상관도)")
    parser_brain.add_argument("--wm", type=float, help="Importance 가중치 (중요도)")
    # 평가 옵션
    parser_brain.add_argument("--id", help="문서 holon_id")
    parser_brain.add_argument("--accuracy", type=int, help="정확성 (0~5)")
    parser_brain.add_argument("--importance", type=int, help="중요성 (0~5)")
    parser_brain.add_argument("--reusability", type=int, help="재사용성 (0~5)")
    parser_brain.add_argument("--authority", type=int, help="신뢰도 (0~5)")
    parser_brain.add_argument("--strategic", type=int, help="전략적 가치 (0~5)")
    parser_brain.set_defaults(func=cmd_brain)
    
    # mission (NEW - 미션 전파 시스템)
    parser_mission = subparsers.add_parser("mission", help="🎯 Mission Propagation Engine")
    parser_mission.add_argument("action", 
                               choices=["tree", "preview", "propagate", "save"], 
                               nargs="?", default="tree",
                               help="tree: 미션 트리, preview: 전파 시뮬레이션, "
                                    "propagate: 실제 전파, save: 리포트 저장")
    parser_mission.add_argument("--execute", action="store_true", 
                               help="propagate 실제 실행 (기본: 시뮬레이션)")
    parser_mission.set_defaults(func=cmd_mission)
    
    # tag (NEW - 멀티레이어 자동 태깅)
    parser_tag = subparsers.add_parser("tag", help="🏷️ Auto-Tagger v3 - 멀티레이어 자동 태깅")
    parser_tag.add_argument("action", 
                           choices=["all", "file", "show"], 
                           nargs="?", default="all",
                           help="all: 전체 문서 태깅, file: 단일 파일, show: 태그 확인")
    parser_tag.add_argument("--file", "-f", help="단일 파일 경로")
    parser_tag.add_argument("--dry-run", action="store_true", 
                           help="저장하지 않고 태그만 확인")
    parser_tag.set_defaults(func=cmd_tag)
    
    # rag (NEW - W 기반 벡터 RAG)
    parser_rag = subparsers.add_parser("rag", help="🧠 Vector RAG - W(의지) 기반 의미적 검색")
    parser_rag.add_argument("action", 
                           choices=["index", "search", "hybrid"], 
                           nargs="?", default="search",
                           help="index: 인덱싱, search: 의미적 검색, hybrid: 그래프 확장 검색")
    parser_rag.add_argument("-q", "--query", help="검색 쿼리")
    parser_rag.add_argument("-k", "--top-k", type=int, default=10, help="결과 수")
    parser_rag.add_argument("--force", action="store_true", help="강제 재인덱싱")
    parser_rag.set_defaults(func=cmd_rag)
    
    # review (NEW - 자연어 리뷰 기반 점수 보정)
    parser_review = subparsers.add_parser("review", help="📝 자연어 리뷰로 문서 점수 자동 보정")
    parser_review.add_argument("holon_id", nargs="?", help="문서 ID")
    parser_review.add_argument("review_text", nargs="?", help="리뷰 텍스트 (예: '좋아', '헷갈림', '다시 봐야함')")
    parser_review.add_argument("--batch", action="store_true", help="대화형 배치 모드")
    parser_review.set_defaults(func=cmd_review)
    
    # attach (NEW - 첨부파일 관리)
    parser_attach = subparsers.add_parser("attach", help="📎 첨부파일 관리")
    parser_attach.add_argument("action", 
                              choices=["add", "remove", "list", "sync"],
                              nargs="?", default="list",
                              help="add: 추가, remove: 제거, list: 목록, sync: 전체 동기화")
    parser_attach.add_argument("--holon", "-H", help="홀론 ID")
    parser_attach.add_argument("--file", "-f", help="파일 경로 또는 파일명")
    parser_attach.add_argument("--desc", "-d", default="", help="첨부파일 설명")
    parser_attach.add_argument("--no-copy", action="store_true", 
                              help="파일을 복사하지 않고 원본 경로 사용")
    parser_attach.add_argument("--delete", action="store_true", 
                              help="remove 시 실제 파일도 삭제")
    parser_attach.set_defaults(func=cmd_attach)
    
    # issues (NEW - 미완성 지점 탐지 및 리뷰)
    parser_issues = subparsers.add_parser("issues", help="🔍 미완성 지점 탐지 및 리뷰")
    parser_issues.add_argument("action", 
                              choices=["scan", "list", "review", "show", "update", "summary"],
                              nargs="?", default="list",
                              help="scan: 전체 스캔, list: 목록, review: 대화형 리뷰, "
                                   "show: 상세, update: 상태 변경, summary: 요약")
    parser_issues.add_argument("--id", help="이슈 ID")
    parser_issues.add_argument("--category", "-c", 
                              choices=["placeholder", "hardcoded", "api_gap", "error_handling", "integration"],
                              help="카테고리 필터")
    parser_issues.add_argument("--status", "-s",
                              choices=["open", "resolved", "wontfix", "deferred"],
                              help="상태 필터")
    parser_issues.add_argument("--severity",
                              choices=["critical", "high", "medium", "low"],
                              help="심각도 필터")
    parser_issues.add_argument("--set-status", help="상태 변경")
    parser_issues.add_argument("--note", help="리뷰 노트")
    parser_issues.add_argument("--snippet", action="store_true", help="코드 스니펫 표시")
    parser_issues.set_defaults(func=cmd_issues)
    
    # hierarchy (NEW - 수직적 위계질서)
    parser_hierarchy = subparsers.add_parser("hierarchy", help="🏛️ 수직적 위계질서 관리")
    parser_hierarchy.add_argument("action", 
                                 choices=["tree", "signal", "chain", "aggregate", "propagate", "save"], 
                                 nargs="?", default="tree",
                                 help="tree: 위계 트리, signal: 신호 흐름, chain: 수직 체인, "
                                      "aggregate: 신호 집계, propagate: 미션 전파, save: 저장")
    parser_hierarchy.add_argument("--id", help="홀론 ID")
    parser_hierarchy.add_argument("--execute", action="store_true", 
                                 help="propagate 실제 실행 (기본: 시뮬레이션)")
    parser_hierarchy.set_defaults(func=cmd_hierarchy)
    
    # sibling (NEW - 형제 협력)
    parser_sibling = subparsers.add_parser("sibling", help="🤝 형제 협력 관리")
    parser_sibling.add_argument("action", 
                               choices=["groups", "collab", "synergy", "suggest", "aggregate", "share", "request", "save"], 
                               nargs="?", default="groups",
                               help="groups: 형제 그룹, collab: 협력 현황, synergy: 시너지, "
                                    "suggest: 협력 제안, aggregate: 집계, share: 공유, request: 요청, save: 저장")
    parser_sibling.add_argument("--id", help="홀론 ID")
    parser_sibling.add_argument("--content", "-c", help="공유할 내용")
    parser_sibling.add_argument("--type", "-t", default="update", help="정보 타입")
    parser_sibling.add_argument("--resource", "-r", help="요청할 리소스")
    parser_sibling.set_defaults(func=cmd_sibling)
    
    # status
    parser_status = subparsers.add_parser("status", help="시스템 상태")
    parser_status.set_defaults(func=cmd_status)
    
    # help
    parser_help = subparsers.add_parser("help", help="도움말")
    parser_help.set_defaults(func=cmd_help)
    
    args = parser.parse_args()
    
    if args.command is None:
        cmd_help(args)
    else:
        args.func(args)


if __name__ == "__main__":
    main()
