#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
온톨로지 분할 도구 (Ontology Splitter)

alphatutor_ontology.owl 파일을 Agent별 모듈로 분할하는 도구
- Agent 주석 기반 자동 분할
- 핵심 클래스 및 공통 속성 분리
- 모듈별 OWL 파일 생성

사용법:
    python split_ontology.py [--input input.owl] [--output-dir modules/]
"""

import re
import os
import sys
import xml.etree.ElementTree as ET
from collections import defaultdict
from typing import List, Dict, Tuple, Set
from pathlib import Path

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')


class OntologySplitter:
    """온톨로지 분할기"""
    
    # 핵심 클래스 목록 (core.owl로 분리)
    CORE_CLASSES = {
        'Student', 'Teacher', 'Goal', 'Plan', 'Routine', 'LearningActivity',
        'Persona', 'EmotionPattern', 'Interaction', 'Feedback', 'Module',
        'LongTermGoal', 'QuarterlyGoal', 'WeeklyGoal', 'TodayGoal',
        'SignatureRoutine', 'BehaviorChange', 'Execution'
    }
    
    # Agent 주석 패턴
    AGENT_COMMENT_PATTERN = re.compile(
        r'<!--\s*Agent\s+(\d+)[^:]*:\s*([^>]+)\s*-->',
        re.IGNORECASE
    )
    
    def __init__(self, input_file: str, output_dir: str = "modules"):
        """
        Args:
            input_file: 입력 온톨로지 파일 경로
            output_dir: 출력 디렉토리 경로
        """
        self.input_file = input_file
        self.output_dir = Path(output_dir)
        self.output_dir.mkdir(parents=True, exist_ok=True)
        
        self.namespace = "http://mathking.kr/ontology/alphatutor#"
        self.prefix = "at"
        
        # 분할 결과 저장
        self.agent_sections: Dict[int, List[str]] = defaultdict(list)
        self.core_classes: List[str] = []
        self.properties: List[str] = []
        self.header_lines: List[str] = []
        self.footer_line: str = ""
        
        # 통계
        self.stats = {
            'total_lines': 0,
            'agents_found': set(),
            'modules_created': 0,
            'classes_per_module': defaultdict(int)
        }
    
    def parse_file(self) -> None:
        """온톨로지 파일 파싱"""
        print(f"[1/5] 파일 읽기: {self.input_file}")
        
        with open(self.input_file, 'r', encoding='utf-8') as f:
            lines = f.readlines()
        
        self.stats['total_lines'] = len(lines)
        
        # 헤더 추출 (<?xml> 부터 첫 Agent 주석 전까지)
        self.header_lines = []
        current_agent = None
        current_section_lines = []
        in_agent_section = False
        
        for i, line in enumerate(lines):
            # XML 헤더 및 네임스페이스 (처음 10줄 정도)
            if i < 10:
                self.header_lines.append(line)
                continue
            
            # Agent 주석 발견
            match = self.AGENT_COMMENT_PATTERN.search(line)
            if match:
                agent_num = int(match.group(1))
                self.stats['agents_found'].add(agent_num)
                
                # 이전 섹션 저장
                if current_agent is not None and current_section_lines:
                    self.agent_sections[current_agent].extend(current_section_lines)
                
                # 새 섹션 시작
                current_agent = agent_num
                current_section_lines = [line]
                in_agent_section = True
            elif in_agent_section:
                # 현재 Agent 섹션에 추가 (다음 Agent 주석이나 파일 끝까지)
                # 다음 Agent 주석이 나오면 중단 (다음 반복에서 처리)
                if self.AGENT_COMMENT_PATTERN.search(line):
                    # 다음 Agent 주석 발견 - 현재 섹션 저장하고 새로 시작
                    if current_agent is not None:
                        self.agent_sections[current_agent].extend(current_section_lines)
                    match = self.AGENT_COMMENT_PATTERN.search(line)
                    agent_num = int(match.group(1))
                    self.stats['agents_found'].add(agent_num)
                    current_agent = agent_num
                    current_section_lines = [line]
                else:
                    current_section_lines.append(line)
            else:
                # Agent 주석이 없는 부분 (핵심 클래스 또는 속성)
                # owl:Ontology는 헤더에 포함
                if '<owl:Ontology' in line or '</owl:Ontology>' in line:
                    self.header_lines.append(line)
                else:
                    # 완전한 XML 블록만 추출 (닫는 태그까지 포함)
                    self.core_classes.append(line)
        
        # 마지막 섹션 저장
        if current_agent is not None and current_section_lines:
            self.agent_sections[current_agent].extend(current_section_lines)
        
        # 푸터 추출 (</rdf:RDF>)
        if lines:
            self.footer_line = lines[-1] if lines[-1].strip() == '</rdf:RDF>' else '</rdf:RDF>\n'
        
        print(f"  - 총 {len(lines)}줄 파싱 완료")
        print(f"  - 발견된 Agent: {sorted(self.stats['agents_found'])}")
    
    def identify_core_classes(self) -> None:
        """핵심 클래스 식별"""
        print(f"[2/5] 핵심 클래스 식별 중...")
        
        # core_classes는 이미 parse_file에서 초기 부분이 채워짐
        # 여기서는 Agent 섹션에서 핵심 클래스를 찾아 core로 이동하는 로직은 제거
        # (복잡도가 높고, 현재는 초기 부분의 클래스들을 core로 분류하는 것으로 충분)
        
        # core_classes에서 실제 클래스 수 계산
        class_count = len([l for l in self.core_classes if '<owl:Class' in l and 'rdf:about=' in l])
        print(f"  - 핵심 클래스 {class_count}개 식별")
    
    def extract_properties(self) -> None:
        """공통 속성 추출"""
        print(f"[3/5] 공통 속성 추출 중...")
        
        # 속성은 원본 파일에서 직접 추출 (완전한 XML 블록 유지)
        # 현재는 properties 모듈 생성을 건너뛰고, 각 Agent 모듈에 속성이 포함되도록 함
        # 향후 개선: 원본 파일에서 속성 정의를 완전한 블록으로 추출
        
        # 임시로 빈 리스트 유지 (properties 모듈 생성 비활성화)
        self.properties = []
        print(f"  - 공통 속성 추출 건너뜀 (각 Agent 모듈에 포함됨)")
    
    def create_module_file(self, agent_num: int, content_lines: List[str], module_name: str = None) -> str:
        """모듈 파일 생성"""
        if not content_lines:
            return ""
        
        if module_name is None:
            module_name = f"agent{agent_num:02d}"
        
        lines = []
        
        # 헤더
        lines.append('<?xml version="1.0"?>')
        lines.append('<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"')
        lines.append('         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"')
        lines.append('         xmlns:owl="http://www.w3.org/2002/07/owl#"')
        lines.append(f'         xmlns:{self.prefix}="{self.namespace}">')
        lines.append("")
        
        # 온톨로지 선언
        lines.append(f'  <owl:Ontology rdf:about="{self.namespace}{module_name}">')
        if agent_num == 0:
            if module_name == "core":
                lines.append('    <rdfs:label xml:lang="ko">AlphaTutor Core Ontology</rdfs:label>')
                lines.append('    <rdfs:comment xml:lang="ko">핵심 클래스 및 공통 온톨로지</rdfs:comment>')
            else:
                lines.append('    <rdfs:label xml:lang="ko">AlphaTutor Properties Ontology</rdfs:label>')
                lines.append('    <rdfs:comment xml:lang="ko">공통 속성 정의</rdfs:comment>')
        else:
            lines.append(f'    <rdfs:label xml:lang="ko">AlphaTutor Agent {agent_num:02d} Ontology</rdfs:label>')
            lines.append(f'    <rdfs:comment xml:lang="ko">Agent {agent_num:02d} 관련 온톨로지 모듈</rdfs:comment>')
            # 각 Agent 모듈이 core.owl을 import하여 독립적으로 작동할 수 있도록 함
            lines.append('    <owl:imports rdf:resource="core.owl"/>')
        lines.append('  </owl:Ontology>')
        lines.append("")
        
        # 본문 (중복 헤더/푸터 제거)
        for line in content_lines:
            line_stripped = line.strip()
            # 중복 헤더 제거
            if line_stripped.startswith('<?xml'):
                continue
            if 'xmlns:' in line and ('rdf:' in line or 'rdfs:' in line or 'owl:' in line):
                continue
            if '<rdf:RDF' in line or '</rdf:RDF>' in line:
                continue
            if '<owl:Ontology' in line and 'rdf:about=' in line:
                continue
            if '</owl:Ontology>' in line:
                continue
            
            # 주석은 유지
            lines.append(line.rstrip('\n'))
        
        # 푸터
        lines.append("")
        lines.append('</rdf:RDF>')
        
        return '\n'.join(lines) + '\n'
    
    def create_core_module(self) -> None:
        """핵심 클래스 모듈 생성"""
        print(f"[4/5] 모듈 파일 생성 중...")
        
        if not self.core_classes:
            print("  - 핵심 클래스가 없어 core.owl을 생성하지 않습니다.")
            return
        
        content = self.create_module_file(0, self.core_classes, module_name="core")
        
        # core.owl 파일 저장
        core_file = self.output_dir / "core.owl"
        with open(core_file, 'w', encoding='utf-8') as f:
            f.write(content)
        
        self.stats['modules_created'] += 1
        class_count = len([l for l in self.core_classes if '<owl:Class' in l and 'rdf:about=' in l])
        self.stats['classes_per_module']['core'] = class_count
        print(f"  ✅ core.owl 생성 ({class_count}개 클래스)")
    
    def create_properties_module(self) -> None:
        """공통 속성 모듈 생성"""
        if not self.properties:
            print("  - 공통 속성이 없어 properties.owl을 생성하지 않습니다.")
            return
        
        content = self.create_module_file(0, self.properties, module_name="properties")
        
        # properties.owl 파일 저장
        props_file = self.output_dir / "properties.owl"
        with open(props_file, 'w', encoding='utf-8') as f:
            f.write(content)
        
        self.stats['modules_created'] += 1
        prop_count = len([l for l in self.properties if '<owl:ObjectProperty' in l or '<owl:DataProperty' in l])
        self.stats['classes_per_module']['properties'] = prop_count
        print(f"  ✅ properties.owl 생성 ({prop_count}개 속성)")
    
    def create_agent_modules(self) -> None:
        """Agent별 모듈 생성"""
        for agent_num in sorted(self.agent_sections.keys()):
            content_lines = self.agent_sections[agent_num]
            
            if not content_lines:
                continue
            
            content = self.create_module_file(agent_num, content_lines)
            
            # agentXX.owl 파일 저장
            agent_file = self.output_dir / f"agent{agent_num:02d}.owl"
            with open(agent_file, 'w', encoding='utf-8') as f:
                f.write(content)
            
            self.stats['modules_created'] += 1
            class_count = len([l for l in content_lines if '<owl:Class' in l])
            self.stats['classes_per_module'][f'agent{agent_num:02d}'] = class_count
            print(f"  ✅ agent{agent_num:02d}.owl 생성 ({class_count}개 클래스)")
    
    def validate_xml(self, file_path: Path) -> bool:
        """XML 유효성 검증"""
        try:
            ET.parse(str(file_path))
            return True
        except ET.ParseError as e:
            print(f"  ❌ XML 파싱 오류 ({file_path}): {e}", file=sys.stderr)
            return False
    
    def create_main_ontology(self) -> None:
        """메인 온톨로지 파일 생성 (owl:imports 포함)"""
        print(f"\n메인 온톨로지 파일 생성 중...")
        
        # 생성된 모듈 파일 목록 가져오기
        module_files = sorted([f for f in self.output_dir.glob("*.owl") if f.name != "alphatutor_ontology_main.owl"])
        
        if not module_files:
            print("  - 생성된 모듈이 없어 메인 온톨로지를 생성하지 않습니다.")
            return
        
        lines = []
        
        # 헤더
        lines.append('<?xml version="1.0"?>')
        lines.append('<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"')
        lines.append('         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"')
        lines.append('         xmlns:owl="http://www.w3.org/2002/07/owl#"')
        lines.append(f'         xmlns:{self.prefix}="{self.namespace}">')
        lines.append("")
        
        # 온톨로지 선언 및 imports
        lines.append(f'  <owl:Ontology rdf:about="{self.namespace}">')
        lines.append('    <rdfs:label xml:lang="ko">AlphaTutor Learning Ontology</rdfs:label>')
        lines.append('    <rdfs:comment xml:lang="ko">수학 학습 온보딩 및 에이전트 시스템 온톨로지</rdfs:comment>')
        lines.append("")
        
        # owl:imports 추가
        for module_file in module_files:
            # 상대 경로 계산 (메인 파일 기준)
            module_name = module_file.stem
            # 상대 경로: modules/agentXX.owl
            relative_path = f"modules/{module_file.name}"
            lines.append(f'    <owl:imports rdf:resource="{relative_path}"/>')
        
        lines.append('  </owl:Ontology>')
        lines.append("")
        lines.append('</rdf:RDF>')
        
        content = '\n'.join(lines) + '\n'
        
        # 메인 온톨로지 파일 저장
        main_file = self.output_dir.parent / "alphatutor_ontology_main.owl"
        with open(main_file, 'w', encoding='utf-8') as f:
            f.write(content)
        
        print(f"  ✅ {main_file.name} 생성 완료 ({len(module_files)}개 모듈 import)")
        
        # XML 유효성 검증
        if self.validate_xml(main_file):
            print(f"  ✅ 메인 온톨로지 파일이 유효한 XML입니다.")
    
    def generate_report(self) -> None:
        """분할 결과 리포트 생성"""
        print(f"\n[5/5] 분할 완료 리포트")
        print("=" * 80)
        print(f"입력 파일: {self.input_file}")
        print(f"출력 디렉토리: {self.output_dir}")
        print(f"총 줄 수: {self.stats['total_lines']}")
        print(f"생성된 모듈 수: {self.stats['modules_created']}")
        print(f"\n모듈별 클래스 수:")
        for module, count in sorted(self.stats['classes_per_module'].items()):
            print(f"  - {module}.owl: {count}개 클래스")
        print("=" * 80)
    
    def split(self) -> bool:
        """온톨로지 분할 실행"""
        try:
            self.parse_file()
            self.identify_core_classes()
            self.extract_properties()
            self.create_core_module()
            self.create_properties_module()
            self.create_agent_modules()
            
            # XML 유효성 검증
            print(f"\nXML 유효성 검증 중...")
            all_valid = True
            for module_file in self.output_dir.glob("*.owl"):
                if not self.validate_xml(module_file):
                    all_valid = False
            
            if all_valid:
                print("  ✅ 모든 모듈 파일이 유효한 XML입니다.")
            
            # 메인 온톨로지 생성
            self.create_main_ontology()
            
            self.generate_report()
            return all_valid
            
        except Exception as e:
            print(f"❌ 오류 발생: {e}", file=sys.stderr)
            import traceback
            traceback.print_exc()
            return False


def main():
    """메인 함수"""
    import argparse
    
    parser = argparse.ArgumentParser(description='온톨로지 분할 도구')
    parser.add_argument(
        '--input',
        default='../alphatutor_ontology.owl',
        help='입력 온톨로지 파일 경로 (기본값: ../alphatutor_ontology.owl)'
    )
    parser.add_argument(
        '--output-dir',
        default='../modules',
        help='출력 디렉토리 경로 (기본값: ../modules)'
    )
    
    args = parser.parse_args()
    
    # 입력 파일 존재 확인
    if not os.path.exists(args.input):
        print(f"❌ 오류: 입력 파일을 찾을 수 없습니다: {args.input}", file=sys.stderr)
        sys.exit(1)
    
    # 분할 실행
    splitter = OntologySplitter(args.input, args.output_dir)
    success = splitter.split()
    
    if success:
        print("\n✅ 온톨로지 분할이 성공적으로 완료되었습니다!")
        sys.exit(0)
    else:
        print("\n❌ 온톨로지 분할 중 오류가 발생했습니다.", file=sys.stderr)
        sys.exit(1)


if __name__ == "__main__":
    main()

