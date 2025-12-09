#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
온톨로지 성능 벤치마크

모듈화 전후 성능 비교:
- 로딩 시간
- 검증 시간
- 캐시 효과

사용법:
    python performance_benchmark.py [--original original.owl] [--modules-dir modules/]
"""

import sys
import time
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Dict

# 로컬 모듈 import
sys.path.insert(0, str(Path(__file__).parent))
from incremental_loader import IncrementalOntologyLoader
from incremental_validator import IncrementalValidator


class PerformanceBenchmark:
    """성능 벤치마크"""
    
    def __init__(self, original_file: str, modules_dir: str):
        self.original_file = Path(original_file)
        self.modules_dir = Path(modules_dir)
        self.results = {}
    
    def measure_loading_time(self, file_path: Path) -> float:
        """파일 로딩 시간 측정"""
        start_time = time.perf_counter()
        try:
            tree = ET.parse(str(file_path))
            root = tree.getroot()
            # 클래스 수 계산
            ns = {'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#', 'owl': 'http://www.w3.org/2002/07/owl#'}
            classes = root.findall('.//owl:Class', ns)
            end_time = time.perf_counter()
            return end_time - start_time
        except Exception as e:
            print(f"Error loading {file_path}: {e}")
            return 0
    
    def benchmark_original_file(self):
        """원본 파일 성능 측정"""
        print("\n[1/4] 원본 파일 성능 측정...")
        if not self.original_file.exists():
            print("  파일을 찾을 수 없습니다.")
            return {}
        
        file_size = self.original_file.stat().st_size
        times = []
        for i in range(3):
            times.append(self.measure_loading_time(self.original_file))
            time.sleep(0.1)
        
        avg_time = sum(times) / len(times)
        result = {
            'file_size_mb': file_size / (1024 * 1024),
            'loading_time_ms': avg_time * 1000
        }
        print(f"  파일 크기: {result['file_size_mb']:.2f} MB")
        print(f"  로딩 시간: {result['loading_time_ms']:.2f} ms")
        self.results['original'] = result
        return result
    
    def benchmark_modular_loading(self):
        """모듈화된 파일 로딩 성능 측정"""
        print("\n[2/4] 모듈화된 파일 로딩 성능 측정...")
        module_files = sorted(self.modules_dir.glob("*.owl"))
        if not module_files:
            print("  모듈 파일을 찾을 수 없습니다.")
            return {}
        
        total_size = sum(f.stat().st_size for f in module_files)
        total_time = 0
        module_times = {}
        
        for module_file in module_files:
            loading_time = self.measure_loading_time(module_file)
            total_time += loading_time
            module_times[module_file.name] = loading_time * 1000
        
        result = {
            'file_size_mb': total_size / (1024 * 1024),
            'total_loading_time_ms': total_time * 1000,
            'module_count': len(module_files),
            'module_times': module_times
        }
        print(f"  총 파일 크기: {result['file_size_mb']:.2f} MB")
        print(f"  총 로딩 시간: {result['total_loading_time_ms']:.2f} ms")
        print(f"  모듈 수: {result['module_count']}")
        
        if module_times:
            fastest = min(module_times.items(), key=lambda x: x[1])
            slowest = max(module_times.items(), key=lambda x: x[1])
            print(f"  가장 빠른 모듈: {fastest[0]} ({fastest[1]:.2f} ms)")
            print(f"  가장 느린 모듈: {slowest[0]} ({slowest[1]:.2f} ms)")
        
        self.results['modular'] = result
        return result
    
    def benchmark_incremental_loading(self):
        """증분 로딩 성능 측정"""
        print("\n[3/4] 증분 로딩 성능 측정...")
        loader = IncrementalOntologyLoader(str(self.modules_dir))
        agent_numbers = [6, 7, 8, 9, 10, 11, 12, 15, 16, 17, 18, 19, 20, 21, 22]
        agent_times = {}
        
        for agent_num in agent_numbers[:5]:
            start_time = time.perf_counter()
            classes = loader.load_classes_by_agent(agent_num)
            end_time = time.perf_counter()
            agent_times[f"agent{agent_num:02d}"] = {
                'time_ms': (end_time - start_time) * 1000,
                'class_count': len(classes)
            }
        
        # 캐시 효과 측정
        if agent_numbers:
            start_time = time.perf_counter()
            cached_classes = loader.load_classes_by_agent(agent_numbers[0])
            cached_time = (time.perf_counter() - start_time) * 1000
        else:
            cached_time = 0
        
        result = {
            'agent_times': agent_times,
            'cached_time_ms': cached_time,
            'cache_speedup': agent_times.get(f"agent{agent_numbers[0]:02d}", {}).get('time_ms', 0) / cached_time if cached_time > 0 else 0
        }
        
        print("  Agent별 로딩 시간:")
        for agent, data in list(agent_times.items())[:5]:
            print(f"    {agent}: {data['time_ms']:.2f} ms ({data['class_count']} classes)")
        if cached_time > 0:
            print(f"  캐시 사용 시: {cached_time:.2f} ms")
            print(f"  캐시 효과: {result['cache_speedup']:.1f}x 빠름")
        
        self.results['incremental'] = result
        return result
    
    def benchmark_validation(self):
        """검증 성능 측정"""
        print("\n[4/4] 검증 성능 측정...")
        validator = IncrementalValidator()
        validator.invalidate_cache()
        
        start_time = time.perf_counter()
        results = validator.validate_all_modules(str(self.modules_dir))
        first_run_time = (time.perf_counter() - start_time) * 1000
        
        start_time = time.perf_counter()
        results_cached = validator.validate_all_modules(str(self.modules_dir))
        cached_run_time = (time.perf_counter() - start_time) * 1000
        
        result = {
            'first_run_time_ms': first_run_time,
            'cached_run_time_ms': cached_run_time,
            'cache_speedup': first_run_time / cached_run_time if cached_run_time > 0 else 0,
            'modules_validated': results.get('summary', {}).get('total_modules', 0)
        }
        
        print(f"  첫 실행: {first_run_time:.2f} ms")
        print(f"  캐시 사용 시: {cached_run_time:.2f} ms")
        print(f"  캐시 효과: {result['cache_speedup']:.1f}x 빠름")
        
        self.results['validation'] = result
        return result
    
    def generate_report(self):
        """성능 리포트 생성"""
        print("\n" + "=" * 80)
        print("성능 벤치마크 리포트")
        print("=" * 80)
        
        if self.results.get('original') and self.results.get('modular'):
            orig = self.results['original']
            mod = self.results['modular']
            
            print("\n[로딩 시간 비교]")
            print(f"  원본 파일: {orig['loading_time_ms']:.2f} ms")
            print(f"  모듈화 (전체): {mod['total_loading_time_ms']:.2f} ms")
            if orig['loading_time_ms'] > 0:
                speedup = orig['loading_time_ms'] / mod['total_loading_time_ms']
                print(f"  개선율: {speedup:.2f}x")
            
            print("\n[파일 크기 비교]")
            print(f"  원본 파일: {orig['file_size_mb']:.2f} MB")
            print(f"  모듈화 (전체): {mod['file_size_mb']:.2f} MB")
        
        if self.results.get('incremental'):
            inc = self.results['incremental']
            print("\n[증분 로딩 성능]")
            if inc.get('agent_times'):
                avg_time = sum(d['time_ms'] for d in inc['agent_times'].values()) / len(inc['agent_times'])
                print(f"  평균 Agent 모듈 로딩 시간: {avg_time:.2f} ms")
            if inc.get('cached_time_ms', 0) > 0:
                print(f"  캐시 사용 시: {inc['cached_time_ms']:.2f} ms")
                print(f"  캐시 효과: {inc.get('cache_speedup', 0):.1f}x 빠름")
        
        if self.results.get('validation'):
            val = self.results['validation']
            print("\n[검증 성능]")
            print(f"  첫 실행: {val['first_run_time_ms']:.2f} ms")
            print(f"  캐시 사용 시: {val['cached_run_time_ms']:.2f} ms")
            print(f"  캐시 효과: {val.get('cache_speedup', 0):.1f}x 빠름")
        
        print("=" * 80)
    
    def run_all(self):
        """모든 벤치마크 실행"""
        print("=" * 80)
        print("온톨로지 성능 벤치마크 시작")
        print("=" * 80)
        
        self.benchmark_original_file()
        self.benchmark_modular_loading()
        self.benchmark_incremental_loading()
        self.benchmark_validation()
        self.generate_report()
        
        return self.results


def main():
    import argparse
    parser = argparse.ArgumentParser(description='온톨로지 성능 벤치마크')
    parser.add_argument('--original', default='../alphatutor_ontology.owl', help='원본 파일')
    parser.add_argument('--modules-dir', default='../modules', help='모듈 디렉토리')
    args = parser.parse_args()
    
    benchmark = PerformanceBenchmark(args.original, args.modules_dir)
    results = benchmark.run_all()


if __name__ == "__main__":
    main()
