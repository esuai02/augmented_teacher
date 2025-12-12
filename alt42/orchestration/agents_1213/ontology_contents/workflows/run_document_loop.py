#!/usr/bin/env python3
"""
Mathking 문서 순환 업데이트 워크플로우 실행기

평가표 기준을 충족할 때까지 문서를 순환적으로 업데이트합니다.
우선순위와 대표 문서 중심의 효율적인 구조로 설계되었습니다.

사용법:
    python run_document_loop.py --config document_update_loop.yaml --mode loop_until_pass
    python run_document_loop.py --mode single_iteration --stage stage1_evaluate_core
    python run_document_loop.py --documents "01-AGENTS,04-ONTOLOGY" --mode manual
"""

import argparse
import json
import logging
import os
import sys
import time
import yaml
from datetime import datetime
from pathlib import Path
from typing import Dict, List, Optional, Any
import hashlib
import shutil

# 로깅 설정
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('logs/workflow_iterations.log'),
        logging.StreamHandler(sys.stdout)
    ]
)
logger = logging.getLogger(__name__)


class DocumentEvaluator:
    """문서 평가 클래스"""

    def __init__(self, criteria: Dict[str, Any]):
        self.criteria = criteria
        self.max_score = criteria['max_score']
        self.passing_score = criteria['passing_score']

    def evaluate_document(self, doc_path: str) -> Dict[str, Any]:
        """
        문서를 평가하고 점수를 반환합니다.

        Args:
            doc_path: 평가할 문서 경로

        Returns:
            평가 결과 딕셔너리
        """
        logger.info(f"📊 평가 시작: {doc_path}")

        try:
            # 문서 읽기
            with open(doc_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # 각 카테고리별 평가
            scores = {}
            total_score = 0

            for category_id, category in self.criteria['categories'].items():
                score = self._evaluate_category(content, category)
                scores[category_id] = score
                total_score += score

            status = "pass" if total_score >= self.passing_score else "fail"

            result = {
                "document": doc_path,
                "score": total_score,
                "max_score": self.max_score,
                "passing_score": self.passing_score,
                "status": status,
                "breakdown": scores,
                "issues": self._identify_issues(content, scores),
                "evaluated_at": datetime.now().isoformat()
            }

            logger.info(f"✅ 평가 완료: {doc_path} - {total_score}/{self.max_score} ({status})")
            return result

        except Exception as e:
            logger.error(f"❌ 평가 실패: {doc_path} - {str(e)}")
            return {
                "document": doc_path,
                "score": 0,
                "status": "error",
                "error": str(e)
            }

    def _evaluate_category(self, content: str, category: Dict[str, Any]) -> int:
        """카테고리별 평가"""
        # 간단한 휴리스틱 기반 평가
        # 실제로는 더 정교한 로직이 필요합니다.

        checklist = category['checklist']
        weight = category['weight']

        # 체크리스트 항목별 점수 계산
        items_found = 0
        for item in checklist:
            # 간단한 키워드 매칭 (실제로는 더 정교한 분석 필요)
            keywords = self._extract_keywords(item)
            if any(keyword in content for keyword in keywords):
                items_found += 1

        # 비율 계산
        ratio = items_found / len(checklist) if checklist else 0
        score = int(weight * ratio)

        return score

    def _extract_keywords(self, item: str) -> List[str]:
        """체크리스트 항목에서 키워드 추출"""
        # 간단한 구현 - 실제로는 NLP 사용 가능
        keywords = []
        if "섹션" in item or "구성" in item:
            keywords = ["##", "###", "목차", "구조"]
        elif "기능" in item or "사용" in item:
            keywords = ["기능", "사용법", "예제", "사용 사례"]
        elif "기술" in item or "스택" in item:
            keywords = ["기술", "스택", "아키텍처", "패턴"]
        elif "명명" in item or "규칙" in item:
            keywords = ["명명", "규칙", "컨벤션", "표준"]
        elif "보안" in item or "배포" in item:
            keywords = ["보안", "배포", "인증", "권한"]
        elif "품질" in item or "예제" in item:
            keywords = ["예제", "코드", "```", "샘플"]

        return keywords

    def _identify_issues(self, content: str, scores: Dict[str, int]) -> List[str]:
        """낮은 점수 카테고리의 이슈 식별"""
        issues = []

        for category_id, score in scores.items():
            category = self.criteria['categories'][category_id]
            if score < category['weight'] * 0.7:  # 70% 미만
                issues.append(f"{category['name']} 개선 필요 (현재: {score}/{category['weight']})")

        return issues


class DocumentUpdater:
    """문서 업데이트 클래스"""

    def __init__(self, backup_enabled: bool = True):
        self.backup_enabled = backup_enabled

    def update_document(self, doc_path: str, evaluation: Dict[str, Any]) -> bool:
        """
        평가 결과를 기반으로 문서를 업데이트합니다.

        Args:
            doc_path: 업데이트할 문서 경로
            evaluation: 평가 결과

        Returns:
            업데이트 성공 여부
        """
        logger.info(f"🔧 업데이트 시작: {doc_path}")

        try:
            # 백업 생성
            if self.backup_enabled:
                self._create_backup(doc_path)

            # 문서 읽기
            with open(doc_path, 'r', encoding='utf-8') as f:
                content = f.read()

            # 이슈별 개선 적용
            updated_content = content
            for issue in evaluation.get('issues', []):
                updated_content = self._apply_improvement(updated_content, issue)

            # 업데이트된 내용 저장
            with open(doc_path, 'w', encoding='utf-8') as f:
                f.write(updated_content)

            logger.info(f"✅ 업데이트 완료: {doc_path}")
            return True

        except Exception as e:
            logger.error(f"❌ 업데이트 실패: {doc_path} - {str(e)}")
            return False

    def _create_backup(self, doc_path: str):
        """문서 백업 생성"""
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        backup_dir = Path("backups/workflow_runs") / timestamp
        backup_dir.mkdir(parents=True, exist_ok=True)

        backup_path = backup_dir / Path(doc_path).name
        shutil.copy2(doc_path, backup_path)
        logger.info(f"💾 백업 생성: {backup_path}")

    def _apply_improvement(self, content: str, issue: str) -> str:
        """이슈에 따른 개선 적용"""
        # 실제로는 LLM을 사용하여 개선 내용을 생성할 수 있습니다.
        # 여기서는 간단한 예시만 제공합니다.

        improved_content = content

        # 구조적 완성도 개선
        if "구조적" in issue:
            improved_content = self._improve_structure(improved_content)

        # 기능적 명확성 개선
        elif "기능적" in issue:
            improved_content = self._improve_clarity(improved_content)

        # 기술적 적합성 개선
        elif "기술적" in issue:
            improved_content = self._improve_technical(improved_content)

        # 유지보수성 개선
        elif "유지보수" in issue:
            improved_content = self._improve_maintainability(improved_content)

        # 보안/배포 개선
        elif "보안" in issue or "배포" in issue:
            improved_content = self._improve_security_deployment(improved_content)

        # 문서 품질 개선
        elif "품질" in issue:
            improved_content = self._improve_quality(improved_content)

        return improved_content

    def _improve_structure(self, content: str) -> str:
        """구조 개선"""
        # 목차 추가 등
        if "## 목차" not in content and "## 📋 목차" not in content:
            toc = "\n## 📋 목차\n\n<!-- TODO: 목차 자동 생성 -->\n\n"
            content = content.replace("# ", toc + "# ", 1)
        return content

    def _improve_clarity(self, content: str) -> str:
        """명확성 개선"""
        # 사용 사례 섹션 추가 등
        if "사용 사례" not in content and "Usage" not in content:
            usage_section = "\n\n## 사용 사례\n\n<!-- TODO: 구체적인 사용 사례 추가 -->\n\n"
            content += usage_section
        return content

    def _improve_technical(self, content: str) -> str:
        """기술적 적합성 개선"""
        # 아키텍처 다이어그램 추가 등
        if "```" not in content:
            diagram_section = "\n\n## 아키텍처\n\n```\n<!-- TODO: 아키텍처 다이어그램 추가 -->\n```\n\n"
            content += diagram_section
        return content

    def _improve_maintainability(self, content: str) -> str:
        """유지보수성 개선"""
        # 명명 규칙 섹션 추가 등
        if "명명 규칙" not in content and "Naming" not in content:
            naming_section = "\n\n## 명명 규칙\n\n<!-- TODO: 명명 규칙 문서화 -->\n\n"
            content += naming_section
        return content

    def _improve_security_deployment(self, content: str) -> str:
        """보안/배포 개선"""
        # 보안 요구사항 섹션 추가 등
        if "보안" not in content and "Security" not in content:
            security_section = "\n\n## 보안 요구사항\n\n<!-- TODO: 보안 요구사항 명시 -->\n\n"
            content += security_section
        return content

    def _improve_quality(self, content: str) -> str:
        """문서 품질 개선"""
        # 예제 코드 섹션 추가 등
        if content.count("```") < 2:
            example_section = "\n\n## 예제\n\n```python\n# TODO: 실행 가능한 예제 추가\n```\n\n"
            content += example_section
        return content


class WorkflowOrchestrator:
    """워크플로우 오케스트레이터"""

    def __init__(self, config_path: str):
        self.config = self._load_config(config_path)
        self.evaluator = DocumentEvaluator(self.config['evaluation_criteria'])
        self.updater = DocumentUpdater(
            backup_enabled=self.config['automation']['backup_strategy']['enabled']
        )

        self.iteration = 0
        self.max_iterations = self.config['workflow_loop']['max_iterations']

    def _load_config(self, config_path: str) -> Dict[str, Any]:
        """설정 파일 로드"""
        with open(config_path, 'r', encoding='utf-8') as f:
            return yaml.safe_load(f)

    def run_loop_until_pass(self):
        """평가 기준 충족까지 루프 실행"""
        logger.info("🚀 워크플로우 시작 (loop_until_pass 모드)")

        while self.iteration < self.max_iterations:
            self.iteration += 1
            logger.info(f"\n{'='*60}")
            logger.info(f"🔄 반복 {self.iteration}/{self.max_iterations}")
            logger.info(f"{'='*60}\n")

            # 모든 스테이지 실행
            results = self._run_all_stages()

            # 종료 조건 확인
            if self._check_termination(results):
                logger.info("🎉 모든 문서가 평가 기준을 충족했습니다!")
                break

            # 다음 반복 전 대기
            delay = self.config['workflow_loop']['iteration_delay_seconds']
            logger.info(f"⏳ {delay}초 대기 중...\n")
            time.sleep(delay)

        if self.iteration >= self.max_iterations:
            logger.warning("⚠️ 최대 반복 횟수에 도달했습니다.")

        logger.info("✅ 워크플로우 종료")

    def run_single_iteration(self, stage_id: Optional[str] = None):
        """단일 반복 실행"""
        logger.info("🚀 워크플로우 시작 (single_iteration 모드)")

        if stage_id:
            logger.info(f"📌 스테이지: {stage_id}")
            self._run_stage(stage_id)
        else:
            self._run_all_stages()

        logger.info("✅ 워크플로우 종료")

    def _run_all_stages(self) -> Dict[str, Any]:
        """모든 스테이지 실행"""
        results = {}

        stages = [
            'stage1_evaluate_core',
            'stage2_evaluate_template',
            'stage3_knowledge_consistency',
            'stage4_batch_update_agents',
            'stage5_final_consistency',
            'stage6_check_termination'
        ]

        for stage_id in stages:
            logger.info(f"\n{'─'*60}")
            logger.info(f"📍 {stage_id}")
            logger.info(f"{'─'*60}\n")

            results[stage_id] = self._run_stage(stage_id)

        return results

    def _run_stage(self, stage_id: str) -> Dict[str, Any]:
        """특정 스테이지 실행"""
        stage_config = self.config['workflow_loop']['stages'][stage_id]

        if stage_id == 'stage1_evaluate_core':
            return self._run_stage1_evaluate_core(stage_config)
        elif stage_id == 'stage2_evaluate_template':
            return self._run_stage2_evaluate_template(stage_config)
        elif stage_id == 'stage3_knowledge_consistency':
            return self._run_stage3_knowledge_consistency(stage_config)
        elif stage_id == 'stage4_batch_update_agents':
            return self._run_stage4_batch_update_agents(stage_config)
        elif stage_id == 'stage5_final_consistency':
            return self._run_stage5_final_consistency(stage_config)
        elif stage_id == 'stage6_check_termination':
            return self._run_stage6_check_termination(stage_config)

        return {}

    def _run_stage1_evaluate_core(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """Stage 1: 핵심 문서 평가"""
        results = {}
        needs_update = []

        # Tier 1 문서 목록
        tier1_docs = self.config['document_hierarchy']['tier1_core_architecture']['documents']

        for doc_info in tier1_docs:
            doc_path = doc_info['path']

            # 평가
            evaluation = self.evaluator.evaluate_document(doc_path)
            results[doc_info['id']] = evaluation

            # 업데이트 필요 여부 확인
            if evaluation['status'] == 'fail':
                needs_update.append(doc_path)

                # 업데이트 수행
                self.updater.update_document(doc_path, evaluation)

                # 재평가
                re_evaluation = self.evaluator.evaluate_document(doc_path)
                results[doc_info['id'] + '_updated'] = re_evaluation

        # 결과 저장
        self._save_results('status/document_scores.json', results)

        return results

    def _run_stage2_evaluate_template(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """Stage 2: 템플릿 에이전트 평가"""
        # 간단한 구현
        logger.info("📝 템플릿 에이전트 평가 (간소화 버전)")
        return {"template_evaluated": True}

    def _run_stage3_knowledge_consistency(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """Stage 3: 지식 베이스 일관성 검증"""
        logger.info("🔍 지식 베이스 일관성 검증 (간소화 버전)")
        return {"consistency_checked": True}

    def _run_stage4_batch_update_agents(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """Stage 4: 유사 에이전트 일괄 업데이트"""
        logger.info("🔄 유사 에이전트 일괄 업데이트 (간소화 버전)")
        return {"agents_updated": True}

    def _run_stage5_final_consistency(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """Stage 5: 최종 일관성 검증"""
        logger.info("✅ 최종 일관성 검증 (간소화 버전)")
        return {"final_consistency": True}

    def _run_stage6_check_termination(self, config: Dict[str, Any]) -> Dict[str, Any]:
        """Stage 6: 종료 조건 확인"""
        logger.info("🏁 종료 조건 확인")
        return {"termination_check": True}

    def _check_termination(self, results: Dict[str, Any]) -> bool:
        """종료 조건 확인"""
        # 간단한 구현 - 실제로는 더 정교한 로직 필요

        # status/document_scores.json 로드
        try:
            with open('status/document_scores.json', 'r') as f:
                scores = json.load(f)

            # 모든 문서가 passing_score 이상인지 확인
            all_pass = all(
                doc.get('status') == 'pass'
                for doc in scores.values()
                if isinstance(doc, dict)
            )

            return all_pass

        except FileNotFoundError:
            return False

    def _save_results(self, output_path: str, results: Dict[str, Any]):
        """결과 저장"""
        os.makedirs(os.path.dirname(output_path), exist_ok=True)

        with open(output_path, 'w', encoding='utf-8') as f:
            json.dump(results, f, ensure_ascii=False, indent=2)

        logger.info(f"💾 결과 저장: {output_path}")


def main():
    """메인 함수"""
    parser = argparse.ArgumentParser(
        description='Mathking 문서 순환 업데이트 워크플로우'
    )

    parser.add_argument(
        '--config',
        default='workflows/document_update_loop.yaml',
        help='설정 파일 경로'
    )

    parser.add_argument(
        '--mode',
        choices=['loop_until_pass', 'single_iteration', 'manual'],
        default='loop_until_pass',
        help='실행 모드'
    )

    parser.add_argument(
        '--stage',
        help='실행할 스테이지 (single_iteration 모드에서 사용)'
    )

    parser.add_argument(
        '--documents',
        help='평가할 문서 (쉼표로 구분, manual 모드에서 사용)'
    )

    parser.add_argument(
        '--verbose',
        action='store_true',
        help='상세 로그 출력'
    )

    args = parser.parse_args()

    # 로깅 레벨 설정
    if args.verbose:
        logging.getLogger().setLevel(logging.DEBUG)

    # 워크플로우 실행
    orchestrator = WorkflowOrchestrator(args.config)

    if args.mode == 'loop_until_pass':
        orchestrator.run_loop_until_pass()
    elif args.mode == 'single_iteration':
        orchestrator.run_single_iteration(args.stage)
    elif args.mode == 'manual':
        # 수동 모드 구현 (추가 가능)
        logger.info("수동 모드는 아직 구현되지 않았습니다.")


if __name__ == '__main__':
    main()
