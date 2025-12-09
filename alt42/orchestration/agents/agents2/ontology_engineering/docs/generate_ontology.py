#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
RDF/OWL 온톨로지 생성 스크립트
- Triple을 RDF/OWL 형식으로 변환
- 네임스페이스 정의
- 클래스 및 속성 정의
"""

import re
import sys
from collections import defaultdict
from typing import List, Tuple, Dict, Set

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

class OntologyGenerator:
    def __init__(self):
        self.namespace = "http://mathking.kr/ontology/alphatutor#"
        self.prefix = "at"
        self.triples: List[Tuple[str, str, str]] = []
        self.classes: Set[str] = set()
        self.properties: Set[str] = set()
        self.individuals: Set[str] = set()
        
    def parse_triples_from_markdown(self, file_path: str) -> List[Tuple[str, str, str]]:
        """Markdown 파일에서 triple 추출 (코드 블록 내부만)"""
        triples = []
        triple_pattern = re.compile(r'\(([^,]+),\s*([^,]+),\s*([^)]+)\)')
        
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
            
            # 코드 블록만 추출 (```로 감싸진 부분)
            code_block_pattern = re.compile(r'```(?:\w+)?\n(.*?)```', re.DOTALL)
            code_blocks = code_block_pattern.findall(content)
            
            # 각 코드 블록에서 triple 추출
            for code_block in code_blocks:
                matches = triple_pattern.findall(code_block)
                for match in matches:
                    subject = match[0].strip().strip('"\'')
                    predicate = match[1].strip().strip('"\'')
                    obj = match[2].strip().strip('"\'')
                    
                    # 유효한 triple인지 확인 (너무 긴 것은 제외)
                    if len(subject) < 100 and len(predicate) < 100 and len(obj) < 200:
                        # 마크다운 형식 문자가 포함되지 않은지 확인
                        if not any(char in subject + predicate + obj for char in ['**', '###', '#', '*', '-']):
                            triples.append((subject, predicate, obj))
                
        except Exception as e:
            print(f"파일 읽기 오류: {e}")
            
        return triples
    
    def normalize_entity(self, entity: str) -> str:
        """엔티티 이름 정규화"""
        # 공백 제거 및 CamelCase 변환
        entity = entity.strip()
        # 한글과 특수문자 처리
        if entity.startswith('"') or entity.startswith("'"):
            return entity.strip('"\'')
        return entity
    
    def sanitize_uri(self, name: str) -> str:
        """URI 안전한 이름으로 변환"""
        # XML에서 문제가 되는 문자 제거/변환
        # 숫자로 시작하는 경우 앞에 언더스코어 추가
        if name and name[0].isdigit():
            name = "_" + name
        # 특수 문자를 언더스코어로 변환
        import urllib.parse
        # URI 안전하지 않은 문자 처리
        name = name.replace(" ", "_")
        name = name.replace("-", "_")
        name = name.replace("(", "")
        name = name.replace(")", "")
        return name
    
    def generate_rdf_turtle(self, triples: List[Tuple[str, str, str]]) -> str:
        """RDF Turtle 형식 생성"""
        lines = []
        
        # 헤더
        lines.append("@prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .")
        lines.append("@prefix rdfs: <http://www.w3.org/2000/01/rdf-schema#> .")
        lines.append("@prefix owl: <http://www.w3.org/2002/07/owl#> .")
        lines.append(f"@prefix {self.prefix}: <{self.namespace}> .")
        lines.append("")
        lines.append(f"<{self.namespace}> rdf:type owl:Ontology ;")
        lines.append(f'    rdfs:label "AlphaTutor Learning Ontology"@ko ;')
        lines.append(f'    rdfs:comment "수학 학습 온보딩 및 에이전트 시스템 온톨로지"@ko .')
        lines.append("")
        
        # 클래스 정의
        classes = set()
        for s, p, o in triples:
            if p == 'isSubtypeOf':
                classes.add(s)
                classes.add(o)
            else:
                classes.add(s)
                classes.add(o)
        
        # 주요 클래스 정의
        main_classes = ['Student', 'Teacher', 'Goal', 'Plan', 'Routine', 'LearningActivity', 
                       'Persona', 'EmotionPattern', 'Interaction', 'Feedback', 'Module']
        
        for cls in sorted(classes):
            if cls in main_classes or any(c.isupper() for c in cls):
                lines.append(f"{self.prefix}:{cls} rdf:type owl:Class ;")
                lines.append(f'    rdfs:label "{cls}"@ko .')
                lines.append("")
        
        # 속성 정의
        predicates = set(p for _, p, _ in triples)
        predicate_categories = {
            'Cognitive': ['hasPart', 'requires', 'isPrerequisiteOf', 'extends'],
            'Affective': ['causes', 'affects', 'correlatesWith', 'reduces', 'enhances'],
            'Behavioral': ['leadsTo', 'supports', 'resultsIn', 'suggests', 'recommends'],
            'Meta': ['isSubtypeOf', 'contradicts', 'coOccursWith']
        }
        
        for pred in sorted(predicates):
            category = None
            for cat, preds in predicate_categories.items():
                if pred in preds:
                    category = cat
                    break
            
            lines.append(f"{self.prefix}:{pred} rdf:type owl:ObjectProperty ;")
            lines.append(f'    rdfs:label "{pred}"@ko ;')
            if category:
                lines.append(f'    rdfs:comment "카테고리: {category}"@ko ;')
            lines.append(f'    rdfs:domain rdfs:Resource ;')
            lines.append(f'    rdfs:range rdfs:Resource .')
            lines.append("")
        
        # Triple 데이터
        lines.append("# Triple 데이터")
        lines.append("")
        
        current_subject = None
        for subject, predicate, obj in triples:
            subject = self.normalize_entity(subject)
            predicate = self.normalize_entity(predicate)
            obj = self.normalize_entity(obj)
            
            # 주어가 변경되면 새 블록 시작
            if current_subject != subject:
                if current_subject is not None:
                    lines.append(" .")
                    lines.append("")
                current_subject = subject
                lines.append(f"{self.prefix}:{subject}")
            
            # 객체가 리터럴인지 확인
            if obj.startswith('"') or obj.replace('.', '').replace('-', '').isdigit():
                lines.append(f'    {self.prefix}:{predicate} "{obj}" ;')
            else:
                lines.append(f'    {self.prefix}:{predicate} {self.prefix}:{obj} ;')
        
        if current_subject:
            lines.append(" .")
        
        return "\n".join(lines)
    
    def generate_owl_xml(self, triples: List[Tuple[str, str, str]]) -> str:
        """OWL XML 형식 생성 (간소화된 버전)"""
        lines = []
        
        lines.append('<?xml version="1.0"?>')
        lines.append('<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"')
        lines.append('         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"')
        lines.append('         xmlns:owl="http://www.w3.org/2002/07/owl#"')
        lines.append(f'         xmlns:{self.prefix}="{self.namespace}">')
        lines.append("")
        lines.append(f'  <owl:Ontology rdf:about="{self.namespace}">')
        lines.append('    <rdfs:label xml:lang="ko">AlphaTutor Learning Ontology</rdfs:label>')
        lines.append('    <rdfs:comment xml:lang="ko">수학 학습 온보딩 및 에이전트 시스템 온톨로지</rdfs:comment>')
        lines.append('  </owl:Ontology>')
        lines.append("")
        
        # 클래스 정의
        classes = set()
        for s, p, o in triples:
            if p == 'isSubtypeOf':
                classes.add(s)
                classes.add(o)
            else:
                classes.add(s)
                classes.add(o)
        
        for cls in sorted(classes):
            cls_normalized = self.normalize_entity(cls)
            cls_safe = self.sanitize_uri(cls_normalized)
            # XML에서 유효한 이름인지 확인
            if cls_safe and (cls_safe[0].isalpha() or cls_safe[0] == '_'):
                lines.append(f'  <owl:Class rdf:about="{self.namespace}{cls_safe}">')
                lines.append(f'    <rdfs:label xml:lang="ko">{cls_normalized}</rdfs:label>')
                lines.append('  </owl:Class>')
                lines.append("")
        
        # 속성 정의
        predicates = set(p for _, p, _ in triples)
        for pred in sorted(predicates):
            lines.append(f'  <owl:ObjectProperty rdf:about="{self.namespace}{pred}">')
            lines.append(f'    <rdfs:label xml:lang="ko">{pred}</rdfs:label>')
            lines.append('  </owl:ObjectProperty>')
            lines.append("")
        
        # Triple 데이터
        for subject, predicate, obj in triples[:100]:  # 처음 100개만 (파일 크기 제한)
            subject = self.normalize_entity(subject)
            predicate = self.normalize_entity(predicate)
            obj = self.normalize_entity(obj)
            
            subject_safe = self.sanitize_uri(subject)
            predicate_safe = self.sanitize_uri(predicate)
            
            # XML에서 유효한 이름인지 확인
            if not (subject_safe and (subject_safe[0].isalpha() or subject_safe[0] == '_')):
                continue
            if not (predicate_safe and (predicate_safe[0].isalpha() or predicate_safe[0] == '_')):
                continue
            
            lines.append(f'  <rdf:Description rdf:about="{self.namespace}{subject_safe}">')
            if obj.startswith('"') or obj.replace('.', '').replace('-', '').isdigit():
                # XML 특수 문자 이스케이프
                import html
                obj_escaped = html.escape(obj)
                lines.append(f'    <{self.prefix}:{predicate_safe} rdf:datatype="http://www.w3.org/2001/XMLSchema#string">{obj_escaped}</{self.prefix}:{predicate_safe}>')
            else:
                obj_safe = self.sanitize_uri(obj)
                if obj_safe and (obj_safe[0].isalpha() or obj_safe[0] == '_'):
                    lines.append(f'    <{self.prefix}:{predicate_safe} rdf:resource="{self.namespace}{obj_safe}"/>')
            lines.append('  </rdf:Description>')
            lines.append("")
        
        lines.append('</rdf:RDF>')
        
        return "\n".join(lines)


def main():
    generator = OntologyGenerator()
    
    input_file = "triples_all_agents.md"
    
    print(f"파일 읽기: {input_file}")
    triples = generator.parse_triples_from_markdown(input_file)
    
    # 중복 제거
    unique_triples = list(set(triples))
    print(f"총 triple 수: {len(triples)}")
    print(f"고유 triple 수: {len(unique_triples)}")
    
    # RDF Turtle 형식 생성
    print("\nRDF Turtle 형식 생성 중...")
    turtle_content = generator.generate_rdf_turtle(unique_triples)
    
    with open("alphatutor_ontology.ttl", "w", encoding="utf-8") as f:
        f.write(turtle_content)
    
    print("✅ alphatutor_ontology.ttl 생성 완료")
    
    # OWL XML 형식 생성 (샘플)
    print("\nOWL XML 형식 생성 중...")
    owl_content = generator.generate_owl_xml(unique_triples)
    
    with open("alphatutor_ontology.owl", "w", encoding="utf-8") as f:
        f.write(owl_content)
    
    print("✅ alphatutor_ontology.owl 생성 완료")


if __name__ == "__main__":
    main()

