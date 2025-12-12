#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""성능 테스트 스크립트"""

import sys
import time
import xml.etree.ElementTree as ET
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from incremental_loader import IncrementalOntologyLoader
from incremental_validator import IncrementalValidator

def test_loading():
    print("=" * 60)
    print("로딩 성능 테스트")
    print("=" * 60)
    
    original_file = Path(__file__).parent.parent / "alphatutor_ontology.owl"
    if original_file.exists():
        start = time.perf_counter()
        tree = ET.parse(str(original_file))
        original_time = (time.perf_counter() - start) * 1000
        print(f"\n원본 파일 로딩: {original_time:.2f} ms")
    else:
        print(f"\n원본 파일을 찾을 수 없습니다")
        original_time = 0
    
    loader = IncrementalOntologyLoader("modules")
    
    start = time.perf_counter()
    classes = loader.load_classes_by_agent(8)
    module_time = (time.perf_counter() - start) * 1000
    print(f"Agent 08 모듈 로딩: {module_time:.2f} ms ({len(classes)}개 클래스)")
    
    if original_time > 0:
        speedup = original_time / module_time
        print(f"개선율: {speedup:.1f}x 빠름")
    
    start = time.perf_counter()
    cached_classes = loader.load_classes_by_agent(8)
    cached_time = (time.perf_counter() - start) * 1000
    print(f"\n캐시 사용 시: {cached_time:.2f} ms")
    if module_time > 0:
        cache_speedup = module_time / cached_time
        print(f"캐시 효과: {cache_speedup:.1f}x 빠름")

def test_multiple_agents():
    print("\n" + "=" * 60)
    print("여러 Agent 모듈 로딩 테스트")
    print("=" * 60)
    
    loader = IncrementalOntologyLoader("modules")
    agents = [6, 7, 8, 9, 10, 11, 12]
    total_time = 0
    
    for agent_num in agents:
        start = time.perf_counter()
        classes = loader.load_classes_by_agent(agent_num)
        load_time = (time.perf_counter() - start) * 1000
        total_time += load_time
        print(f"Agent {agent_num:02d}: {load_time:.2f} ms ({len(classes)}개 클래스)")
    
    print(f"\n총 {len(agents)}개 모듈 로딩 시간: {total_time:.2f} ms")
    print(f"평균 모듈 로딩 시간: {total_time / len(agents):.2f} ms")

def test_validation():
    print("\n" + "=" * 60)
    print("검증 성능 테스트")
    print("=" * 60)
    
    validator = IncrementalValidator()
    validator.invalidate_cache()
    
    start = time.perf_counter()
    results1 = validator.validate_all_modules("modules")
    first_time = (time.perf_counter() - start) * 1000
    
    print(f"\n첫 검증 (캐시 없음): {first_time:.2f} ms")
    print(f"검증된 모듈 수: {results1.get('summary', {}).get('total_modules', 0)}")
    
    start = time.perf_counter()
    results2 = validator.validate_all_modules("modules")
    cached_time = (time.perf_counter() - start) * 1000
    
    print(f"캐시 사용 시: {cached_time:.2f} ms")
    if cached_time > 0:
        cache_speedup = first_time / cached_time
        print(f"캐시 효과: {cache_speedup:.1f}x 빠름")

if __name__ == "__main__":
    test_loading()
    test_multiple_agents()
    test_validation()
    print("\n" + "=" * 60)
    print("테스트 완료")
    print("=" * 60)

