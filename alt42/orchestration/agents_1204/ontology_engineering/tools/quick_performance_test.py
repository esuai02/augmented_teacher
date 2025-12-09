#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
빠른 성능 테스트 스크립트

간단한 성능 측정을 위한 스크립트
"""

import sys
import time
import xml.etree.ElementTree as ET
from pathlib import Path

sys.path.insert(0, str(Path(__file__).parent))
from incremental_loader import IncrementalOntologyLoader
from incremental_validator import IncrementalValidator


def test_loading_performance():
    """로딩 성능 테스트"""
    print("=" * 60)
    print("로딩 성능 테스트")
    print("=" * 60)
    
    # 원본 파일 로딩
    original_file = Path(__file__).parent.parent / "alphatutor_ontology.owl"
    if original_file.exists():
        start = time.perf_counter()
        tree = ET.parse(str(original_file))
        original_time = (time.perf_counter() - start) * 1000
        print(f"\n원본 파일 로딩: {original_time:.2f} ms")
    else:
        print(f"\n원본 파일을 찾을 수 없습니다: {original_file}")
        original_time = 0
    
    # 모듈 로딩
    modules_dir = Path(__file__).parent.parent / "modules"
    loader = IncrementalOntologyLoader(str(modules_dir))
    
    start = time.perf_counter()
    classes = loader.load_classes_by_agent(8)
    module_time = (time.perf_counter() - start) * 1000
    print(f"Agent 08 모듈 로딩: {module_time:.2f} ms ({len(classes)}개 클래스)")
    
    if original_time > 0:
        speedup = original_time / module_time
        print(f"\n개선율: {speedup:.1f}x 빠름")
    
    # 캐시 효과
    start = time.perf_counter()
    cached_classes = loader.load_classes_by_agent(8)
    cached_time = (time.perf_counter() - start) * 1000
    print(f"\n캐시 사용 시: {cached_time:.2f} ms")
    if module_time > 0:
        cache_speedup = module_time / cached_time
        print(f"캐시 효과: {cache_speedup:.1f}x 빠름")


def test_validation_performance():
    """검증 성능 테스트"""
    print("\n" + "=" * 60)
    print("검증 성능 테스트")
    print("=" * 60)
    
    modules_dir = Path(__file__).parent.parent / "modules"
    validator = IncrementalValidator()
    
    # 캐시 초기화
    validator.invalidate_cache()
    
    # 첫 검증
    start = time.perf_counter()
    results1 = validator.validate_all_modules(str(modules_dir))
    first_time = (time.perf_counter() - start) * 1000
    
    print(f"\n첫 검증 (캐시 없음): {first_time:.2f} ms")
    print(f"검증된 모듈 수: {results1.get('summary', {}).get('total_modules', 0)}")
    
    # 캐시 사용 시 검증
    start = time.perf_counter()
    results2 = validator.validate_all_modules(str(modules_dir))
    cached_time = (time.perf_counter() - start) * 1000
    
    print(f"캐시 사용 시: {cached_time:.2f} ms")
    if cached_time > 0:
        cache_speedup = first_time / cached_time
        print(f"캐시 효과: {cache_speedup:.1f}x 빠름")


if __name__ == "__main__":
    test_loading_performance()
    test_validation_performance()
    print("\n" + "=" * 60)
    print("테스트 완료")
    print("=" * 60)

