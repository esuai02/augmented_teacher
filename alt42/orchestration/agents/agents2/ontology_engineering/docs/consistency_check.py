#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Triple 일관성 검증 스크립트
- 중복 triple 제거
- 일관성 검증
- 완전성 검증
"""

import re
import sys
from collections import defaultdict, Counter
from typing import List, Tuple, Set, Dict

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

class TripleValidator:
    def __init__(self):
        self.triples: List[Tuple[str, str, str]] = []
        self.duplicates: List[Tuple[str, str, str]] = []
        self.entities: Set[str] = set()
        self.predicates: Set[str] = set()
        self.predicate_categories = {
            'Cognitive': ['hasPart', 'requires', 'isPrerequisiteOf', 'extends'],
            'Affective': ['causes', 'affects', 'correlatesWith', 'reduces', 'enhances'],
            'Behavioral': ['leadsTo', 'supports', 'resultsIn', 'suggests', 'recommends'],
            'Meta': ['isSubtypeOf', 'contradicts', 'coOccursWith']
        }
        self.predicate_usage: Dict[str, int] = defaultdict(int)
        self.entity_connections: Dict[str, Set[str]] = defaultdict(set)
        
    def parse_triples_from_markdown(self, file_path: str) -> List[Tuple[str, str, str]]:
        """Markdown 파일에서 triple 추출"""
        triples = []
        triple_pattern = re.compile(r'\(([^,]+),\s*([^,]+),\s*([^)]+)\)')
        
        try:
            with open(file_path, 'r', encoding='utf-8') as f:
                content = f.read()
                
            matches = triple_pattern.findall(content)
            for match in matches:
                subject = match[0].strip()
                predicate = match[1].strip()
                obj = match[2].strip()
                
                # 따옴표 제거
                subject = subject.strip('"\'')
                predicate = predicate.strip('"\'')
                obj = obj.strip('"\'')
                
                triples.append((subject, predicate, obj))
                
        except Exception as e:
            print(f"파일 읽기 오류: {e}")
            
        return triples
    
    def validate_consistency(self):
        """일관성 검증"""
        print("=" * 80)
        print("Triple 일관성 검증 시작")
        print("=" * 80)
        
        # 1. 중복 검사
        self.check_duplicates()
        
        # 2. 서술어 계층 검증
        self.validate_predicate_categories()
        
        # 3. 엔티티 연결성 검증
        self.validate_entity_connectivity()
        
        # 4. 순환 참조 검사
        self.check_circular_references()
        
        # 5. 통계 출력
        self.print_statistics()
        
    def check_duplicates(self):
        """중복 triple 검사"""
        print("\n[1] 중복 검사")
        print("-" * 80)
        
        triple_counter = Counter(self.triples)
        duplicates = [(t, count) for t, count in triple_counter.items() if count > 1]
        
        if duplicates:
            print(f"[WARNING] 중복된 triple 발견: {len(duplicates)}개")
            for triple, count in duplicates[:10]:  # 최대 10개만 표시
                print(f"  - {triple} (중복 {count}회)")
            if len(duplicates) > 10:
                print(f"  ... 외 {len(duplicates) - 10}개")
        else:
            print("[OK] 중복 triple 없음")
        
        self.duplicates = duplicates
        
    def validate_predicate_categories(self):
        """서술어 계층 검증"""
        print("\n[2] 서술어 계층 검증")
        print("-" * 80)
        
        all_predicates = set()
        for category, predicates in self.predicate_categories.items():
            all_predicates.update(predicates)
        
        unknown_predicates = set()
        for _, predicate, _ in self.triples:
            self.predicate_usage[predicate] += 1
            if predicate not in all_predicates:
                unknown_predicates.add(predicate)
        
        if unknown_predicates:
            print(f"[WARNING] 정의되지 않은 서술어 발견: {len(unknown_predicates)}개")
            for pred in sorted(unknown_predicates)[:20]:
                print(f"  - {pred} (사용 {self.predicate_usage[pred]}회)")
        else:
            print("[OK] 모든 서술어가 정의된 계층에 속함")
        
        # 계층별 사용 통계
        print("\n서술어 계층별 사용 통계:")
        for category, predicates in self.predicate_categories.items():
            count = sum(self.predicate_usage[p] for p in predicates)
            print(f"  - {category}: {count}회")
    
    def validate_entity_connectivity(self):
        """엔티티 연결성 검증"""
        print("\n[3] 엔티티 연결성 검증")
        print("-" * 80)
        
        # 엔티티 추출
        for subject, predicate, obj in self.triples:
            self.entities.add(subject)
            self.entities.add(obj)
            self.entity_connections[subject].add(obj)
            self.predicates.add(predicate)
        
        # 고립된 엔티티 검사
        isolated = []
        for entity in self.entities:
            # 다른 엔티티와 연결되지 않은 경우 (단, object로만 나타나는 경우)
            is_subject = any(s == entity for s, _, _ in self.triples)
            is_object = any(o == entity for _, _, o in self.triples)
            
            if not is_subject and is_object:
                isolated.append(entity)
        
        if isolated:
            print(f"[WARNING] 고립된 엔티티 발견: {len(isolated)}개 (object로만 나타남)")
            for entity in sorted(isolated)[:10]:
                print(f"  - {entity}")
        else:
            print("[OK] 모든 엔티티가 적절히 연결됨")
        
        # 연결도 통계
        connection_counts = {e: len(conns) for e, conns in self.entity_connections.items()}
        top_connected = sorted(connection_counts.items(), key=lambda x: x[1], reverse=True)[:10]
        print(f"\n가장 많이 연결된 엔티티 (상위 10개):")
        for entity, count in top_connected:
            print(f"  - {entity}: {count}개 연결")
    
    def check_circular_references(self):
        """순환 참조 검사"""
        print("\n[4] 순환 참조 검사")
        print("-" * 80)
        
        # isSubtypeOf 관계에서 순환 검사
        subtype_graph = defaultdict(set)
        for subject, predicate, obj in self.triples:
            if predicate == 'isSubtypeOf':
                subtype_graph[subject].add(obj)
        
        # DFS로 순환 검사
        def has_cycle(node, visited, rec_stack):
            visited.add(node)
            rec_stack.add(node)
            
            for neighbor in subtype_graph.get(node, []):
                if neighbor not in visited:
                    if has_cycle(neighbor, visited, rec_stack):
                        return True
                elif neighbor in rec_stack:
                    return True
            
            rec_stack.remove(node)
            return False
        
        cycles = []
        visited = set()
        for node in subtype_graph:
            if node not in visited:
                rec_stack = set()
                if has_cycle(node, visited, rec_stack):
                    cycles.append(node)
        
        if cycles:
            print(f"[WARNING] 순환 참조 발견: {len(cycles)}개")
            for cycle in cycles[:5]:
                print(f"  - {cycle}")
        else:
            print("[OK] 순환 참조 없음")
    
    def print_statistics(self):
        """통계 출력"""
        print("\n" + "=" * 80)
        print("검증 통계")
        print("=" * 80)
        
        unique_triples = len(set(self.triples))
        total_triples = len(self.triples)
        
        print(f"총 Triple 수: {total_triples}")
        print(f"고유 Triple 수: {unique_triples}")
        print(f"중복 Triple 수: {total_triples - unique_triples}")
        print(f"엔티티 수: {len(self.entities)}")
        print(f"서술어 수: {len(self.predicates)}")
        
        print(f"\n가장 많이 사용된 서술어 (상위 10개):")
        top_predicates = sorted(self.predicate_usage.items(), key=lambda x: x[1], reverse=True)[:10]
        for predicate, count in top_predicates:
            print(f"  - {predicate}: {count}회")
    
    def save_cleaned_triples(self, output_path: str):
        """정리된 triple 저장"""
        unique_triples = sorted(set(self.triples))
        
        with open(output_path, 'w', encoding='utf-8') as f:
            f.write("# 정리된 Triple 목록\n\n")
            f.write(f"총 {len(unique_triples)}개의 고유 triple\n\n")
            f.write("```\n")
            for subject, predicate, obj in unique_triples:
                f.write(f"({subject}, {predicate}, {obj})\n")
            f.write("```\n")
        
        print(f"\n[OK] 정리된 triple 저장 완료: {output_path}")


def main():
    validator = TripleValidator()
    
    # Triple 파일 경로
    input_file = "triples_all_agents.md"
    output_file = "triples_cleaned.txt"
    
    print(f"파일 읽기: {input_file}")
    triples = validator.parse_triples_from_markdown(input_file)
    validator.triples = triples
    
    print(f"추출된 triple 수: {len(triples)}")
    
    # 검증 실행
    validator.validate_consistency()
    
    # 정리된 triple 저장
    validator.save_cleaned_triples(output_file)
    
    print("\n" + "=" * 80)
    print("검증 완료!")
    print("=" * 80)


if __name__ == "__main__":
    main()

