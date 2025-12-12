#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Turtle 파일 검증 스크립트
"""

import sys

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')

try:
    from rdflib import Graph
    from rdflib.exceptions import ParserError
    
    print("=" * 80)
    print("Turtle 파일 검증")
    print("=" * 80)
    
    file_path = "alphatutor_ontology.ttl"
    
    print(f"\n파일 읽기: {file_path}")
    
    g = Graph()
    try:
        g.parse(file_path, format="turtle")
        print(f"[OK] 파일이 올바른 Turtle 형식입니다!")
        print(f"\n통계:")
        print(f"  - Triple 수: {len(g)}")
        print(f"  - 주제(Subject) 수: {len(set(s for s, p, o in g))}")
        print(f"  - 속성(Predicate) 수: {len(set(p for s, p, o in g))}")
        print(f"  - 객체(Object) 수: {len(set(o for s, p, o in g))}")
        
        # 네임스페이스 확인
        print(f"\n네임스페이스:")
        for prefix, namespace in g.namespaces():
            print(f"  - {prefix}: {namespace}")
        
        print("\n" + "=" * 80)
        print("[OK] 검증 완료! 파일은 Protégé에서 열 수 있습니다.")
        print("=" * 80)
        print("\nProtégé에서 열기:")
        print("1. File → Open...")
        print('2. 파일 형식: "Turtle Files (*.ttl)" 선택')
        print(f"3. 파일 선택: {file_path}")
        print("4. Open 클릭")
        
    except ParserError as e:
        print(f"[ERROR] 파싱 오류: {e}")
        print("\n파일 형식에 문제가 있을 수 있습니다.")
        sys.exit(1)
    except Exception as e:
        print(f"[ERROR] 오류 발생: {e}")
        sys.exit(1)
        
except ImportError:
    print("[WARNING] rdflib가 설치되어 있지 않습니다.")
    print("설치 방법: pip install rdflib")
    print("\n파일 형식은 수동으로 확인해야 합니다:")
    print("1. 파일이 @prefix로 시작하는지 확인")
    print("2. UTF-8 인코딩인지 확인")
    print("3. Protégé에서 파일 형식을 명시적으로 지정하여 열기")

