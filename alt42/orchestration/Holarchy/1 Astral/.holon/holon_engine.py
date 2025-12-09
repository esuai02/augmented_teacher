"""
Holonic AGI Engine - 단순 반복 루프 실행기

핵심 플로우:
1. context.md 읽기 (현재 상황)
2. rules.yaml 위험 체크
3. 위험 시 → 사람 인터페이스 생성, 확인 대기
4. 위험 제거 확인
5. questions.md 해당 상황 질문 실행
6. 결과 → context.md 업데이트
7. 홀론 확장 필요 시 → 새 홀론 생성 (사람 검증)
8. LOOP 반복
"""

import os
import yaml
import json
import re
from datetime import datetime
from pathlib import Path
from typing import Dict, Any, List, Optional

class HolonEngine:
    """단순 반복구조의 Holonic AGI 엔진"""
    
    def __init__(self, holon_path: str = None):
        self.holon_path = Path(holon_path or os.path.dirname(__file__))
        self.context_path = self.holon_path / 'context.md'
        self.questions_path = self.holon_path / 'questions.md'
        self.rules_path = self.holon_path / 'rules.yaml'
        
        self.context = {}
        self.questions = {}
        self.rules = {}
        
        self._load_files()
    
    #═══════════════════════════════════════════════════════════════
    # 1. 파일 로드
    #═══════════════════════════════════════════════════════════════
    
    def _load_files(self):
        """핵심 파일들 로드"""
        self.context = self._parse_markdown_yaml(self.context_path)
        self.questions = self._parse_markdown_yaml(self.questions_path)
        self.rules = self._load_yaml(self.rules_path)
    
    def _parse_markdown_yaml(self, path: Path) -> Dict:
        """Markdown 내 YAML 블록 파싱"""
        if not path.exists():
            return {}
        
        content = path.read_text(encoding='utf-8')
        yaml_blocks = re.findall(r'```yaml\n(.*?)\n```', content, re.DOTALL)
        
        parsed = {}
        for block in yaml_blocks:
            try:
                data = yaml.safe_load(block)
                if data:
                    parsed.update(data)
            except yaml.YAMLError:
                continue
        
        return parsed
    
    def _load_yaml(self, path: Path) -> Dict:
        """YAML 파일 로드"""
        if not path.exists():
            return {}
        
        content = path.read_text(encoding='utf-8')
        return yaml.safe_load(content) or {}
    
    #═══════════════════════════════════════════════════════════════
    # 2. 위험 체크
    #═══════════════════════════════════════════════════════════════
    
    def check_risk(self) -> Dict[str, Any]:
        """현재 위험 수준 판정"""
        diagnostics = self.get_diagnostics()
        
        # BV는 낮을수록 좋으므로 반전
        scores = [
            diagnostics.get('SEI', 0),
            diagnostics.get('EC', 0),
            diagnostics.get('ES', 0),
            1 - diagnostics.get('BV', 1),  # 반전
            diagnostics.get('GR', 0)
        ]
        
        min_score = min(scores) if scores else 0
        
        risk_levels = self.rules.get('risk_check', {}).get('levels', {})
        
        if min_score >= 0.8:
            return {'level': 'safe', 'color': '#22c55e', 'action': '자동 진행'}
        elif min_score >= 0.6:
            return {'level': 'caution', 'color': '#eab308', 'action': '경고 후 진행'}
        elif min_score >= 0.4:
            return {'level': 'danger', 'color': '#f97316', 'action': '사람 확인 필수'}
        else:
            return {'level': 'critical', 'color': '#ef4444', 'action': '즉시 중단'}
    
    def is_risk_cleared(self) -> bool:
        """위험이 제거되었는지 확인"""
        risk = self.check_risk()
        return risk['level'] in ['safe', 'caution']
    
    #═══════════════════════════════════════════════════════════════
    # 3. 진단 파라미터
    #═══════════════════════════════════════════════════════════════
    
    def get_diagnostics(self) -> Dict[str, float]:
        """진단 파라미터 조회"""
        return self.context.get('diagnostics', {
            'SEI': 0.82,  # 학습 효과 지수
            'EC': 0.71,   # 몰입 지속 지수
            'ES': 0.89,   # 정서 안전 지수
            'BV': 0.23,   # 지점 편차 지수 (낮을수록 좋음)
            'GR': 0.76    # 일반화 신뢰성
        })
    
    def update_diagnostics(self, diagnostics: Dict[str, float]):
        """진단 파라미터 업데이트"""
        self.context['diagnostics'] = diagnostics
        self._save_context()
    
    #═══════════════════════════════════════════════════════════════
    # 4. 상황 관리
    #═══════════════════════════════════════════════════════════════
    
    def get_current_situation(self) -> str:
        """현재 상황 조회"""
        current = self.context.get('current', {})
        return current.get('situation', 'init')
    
    def transition_situation(self, new_situation: str, reason: str = ''):
        """상황 전이"""
        current_situation = self.get_current_situation()
        
        # 전이 규칙 확인
        transitions = self.rules.get('transitions', {})
        valid_transition = False
        
        for trans_name, trans_rule in transitions.items():
            from_state = trans_rule.get('from', '')
            to_state = trans_rule.get('to', '')
            
            if (from_state == current_situation or from_state == '*') and to_state == new_situation:
                valid_transition = True
                break
        
        if not valid_transition:
            print(f"⚠️ 경고: {current_situation} → {new_situation} 전이가 규칙에 없습니다.")
        
        # 상황 업데이트
        self.context['current'] = {
            'situation': new_situation,
            'sub_context': None,
            'risk_level': self.check_risk()['level'],
            'attachments': self.context.get('current', {}).get('attachments', [])
        }
        
        # 히스토리 기록
        history = self.context.get('history', [])
        history.append({
            'timestamp': datetime.now().isoformat(),
            'from': current_situation,
            'to': new_situation,
            'reason': reason
        })
        self.context['history'] = history
        
        self._save_context()
        print(f"✅ 상황 전이: {current_situation} → {new_situation}")
    
    #═══════════════════════════════════════════════════════════════
    # 5. 질문 실행
    #═══════════════════════════════════════════════════════════════
    
    def get_questions_for_situation(self, situation: str = None) -> Dict:
        """해당 상황의 질문 목록 조회"""
        situation = situation or self.get_current_situation()
        
        # 전역 질문 + 상황별 질문
        global_questions = self.questions.get('_global', {})
        situation_questions = self.questions.get(situation, {})
        
        return {
            'global': global_questions,
            'situation': situation_questions
        }
    
    def execute_questions(self) -> Dict[str, Any]:
        """현재 상황의 질문 실행"""
        questions = self.get_questions_for_situation()
        results = {}
        
        print(f"\n📋 상황: {self.get_current_situation()}")
        print("=" * 50)
        
        # 포괄질문 출력
        for category in ['global', 'situation']:
            q_set = questions.get(category, {})
            if '포괄질문' in q_set:
                print(f"\n🎯 {category.upper()} 포괄질문:")
                for key, question in q_set['포괄질문'].items():
                    print(f"  - [{key}] {question}")
        
        return results
    
    #═══════════════════════════════════════════════════════════════
    # 6. 홀론 관리
    #═══════════════════════════════════════════════════════════════
    
    def get_active_holons(self) -> List[Dict]:
        """활성 홀론 목록"""
        return self.context.get('active_holons', [])
    
    def create_holon_draft(self, name: str, purpose: str) -> Dict:
        """홀론 초안 생성 (사람 검증 필수)"""
        draft = {
            'id': f"holon-{datetime.now().strftime('%Y%m%d%H%M%S')}",
            'name': name,
            'status': 'draft',
            'created_at': datetime.now().isoformat(),
            'W': {
                'identity': purpose,
                'will': f"{purpose}을 달성하려는 의지",
                'goal': f"{purpose} 완료"
            },
            'X': {'context': '초기화 상태', 'current_state': 'init'},
            'S': {'resources': [], 'dependencies': []},
            'P': {'steps': []},
            'E': {'actions': []},
            'R': {'reflections': []},
            'T': {'channels': []},
            'A': {'abstractions': []},
            'requires_human_verification': True  # 필수
        }
        
        print(f"\n🆕 홀론 초안 생성: {name}")
        print("⚠️ 사람 검증 필수 - 승인 후 확정됩니다.")
        
        return draft
    
    def approve_holon(self, holon_draft: Dict) -> bool:
        """홀론 승인 (사람이 호출)"""
        if not holon_draft.get('requires_human_verification'):
            print("⚠️ 검증이 필요하지 않은 홀론입니다.")
            return False
        
        holon_draft['status'] = 'approved'
        holon_draft['approved_at'] = datetime.now().isoformat()
        
        # 활성 홀론에 추가
        active_holons = self.context.get('active_holons', [])
        active_holons.append(holon_draft)
        self.context['active_holons'] = active_holons
        
        self._save_context()
        print(f"✅ 홀론 승인됨: {holon_draft['name']}")
        
        return True
    
    #═══════════════════════════════════════════════════════════════
    # 7. 메인 루프
    #═══════════════════════════════════════════════════════════════
    
    def run_loop_iteration(self) -> Dict[str, Any]:
        """단일 반복 실행"""
        result = {
            'timestamp': datetime.now().isoformat(),
            'situation': self.get_current_situation(),
            'risk': None,
            'questions_executed': False,
            'needs_human': False
        }
        
        print("\n" + "=" * 60)
        print("🔄 HOLONIC AGI LOOP ITERATION")
        print("=" * 60)
        
        # Step 1: 현재 상황 읽기
        situation = self.get_current_situation()
        print(f"\n1️⃣ 현재 상황: {situation}")
        
        # Step 2: 위험 체크
        risk = self.check_risk()
        result['risk'] = risk
        print(f"2️⃣ 위험 수준: {risk['level']} ({risk['action']})")
        
        # Step 3: 위험 시 사람 확인 필요
        if risk['level'] in ['danger', 'critical']:
            print(f"⚠️ {risk['level'].upper()}: 사람 확인이 필요합니다.")
            result['needs_human'] = True
            return result
        
        # Step 4: 위험 제거 확인
        if not self.is_risk_cleared():
            print("⏳ 위험 제거 대기 중...")
            result['needs_human'] = True
            return result
        
        # Step 5: 질문 실행
        print("\n3️⃣ 질문 실행:")
        self.execute_questions()
        result['questions_executed'] = True
        
        # Step 6: 진단 파라미터 출력
        print("\n4️⃣ 진단 파라미터:")
        diagnostics = self.get_diagnostics()
        for key, value in diagnostics.items():
            bar = "█" * int(value * 10) + "░" * (10 - int(value * 10))
            print(f"   {key}: [{bar}] {value:.0%}")
        
        print("\n" + "=" * 60)
        print("✅ LOOP ITERATION 완료")
        print("=" * 60)
        
        return result
    
    #═══════════════════════════════════════════════════════════════
    # 유틸리티
    #═══════════════════════════════════════════════════════════════
    
    def _save_context(self):
        """context.md 저장"""
        # 실제 구현: context.md 파일 업데이트
        pass
    
    def get_status_summary(self) -> Dict:
        """상태 요약"""
        return {
            'situation': self.get_current_situation(),
            'risk': self.check_risk(),
            'diagnostics': self.get_diagnostics(),
            'active_holons': len(self.get_active_holons()),
            'timestamp': datetime.now().isoformat()
        }
    
    def export_for_diagnostic_page(self) -> str:
        """diagnostic.html용 JSON 출력"""
        return json.dumps(self.get_status_summary(), indent=2, ensure_ascii=False)


#═══════════════════════════════════════════════════════════════
# CLI 실행
#═══════════════════════════════════════════════════════════════

if __name__ == '__main__':
    import sys
    
    engine = HolonEngine()
    
    if len(sys.argv) > 1:
        command = sys.argv[1]
        
        if command == 'status':
            print(json.dumps(engine.get_status_summary(), indent=2, ensure_ascii=False))
        elif command == 'loop':
            engine.run_loop_iteration()
        elif command == 'risk':
            risk = engine.check_risk()
            print(f"위험 수준: {risk['level']} - {risk['action']}")
        elif command == 'questions':
            engine.execute_questions()
        else:
            print(f"알 수 없는 명령: {command}")
            print("사용법: python holon_engine.py [status|loop|risk|questions]")
    else:
        # 기본: 루프 1회 실행
        engine.run_loop_iteration()

