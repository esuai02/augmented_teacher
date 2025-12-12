#!/usr/bin/env python3
"""
Phase 1 성능 벤치마크

측정 항목:
1. 온톨로지 로드 시간
2. 규칙 추출 시간
3. 추론 실행 시간
4. 메모리 사용량
5. 전체 처리 시간
"""

import time
import json
import sys
from typing import Dict, List
from ontology_loader import OntologyLoader
from inference_engine import InferenceEngine


class PerformanceBenchmark:
    """성능 벤치마크 클래스"""

    def __init__(self, ontology_path: str):
        self.ontology_path = ontology_path
        self.results = {}

    def measure_ontology_load(self, iterations: int = 100) -> Dict[str, float]:
        """온톨로지 로드 시간 측정"""
        print(f"\n📊 온톨로지 로드 시간 측정 ({iterations}회 반복)...")

        times = []
        for i in range(iterations):
            loader = OntologyLoader(self.ontology_path)

            start_time = time.perf_counter()
            loader.load()
            end_time = time.perf_counter()

            elapsed = (end_time - start_time) * 1000  # 밀리초
            times.append(elapsed)

            if (i + 1) % 20 == 0:
                print(f"  진행: {i + 1}/{iterations}회")

        avg_time = sum(times) / len(times)
        min_time = min(times)
        max_time = max(times)

        print(f"  ✓ 평균: {avg_time:.3f}ms")
        print(f"  ✓ 최소: {min_time:.3f}ms")
        print(f"  ✓ 최대: {max_time:.3f}ms")

        return {
            'average': avg_time,
            'min': min_time,
            'max': max_time,
            'iterations': iterations
        }

    def measure_rule_extraction(self, iterations: int = 100) -> Dict[str, float]:
        """규칙 추출 시간 측정"""
        print(f"\n📊 규칙 추출 시간 측정 ({iterations}회 반복)...")

        loader = OntologyLoader(self.ontology_path)
        loader.load()

        times = []
        for i in range(iterations):
            start_time = time.perf_counter()
            rules = loader.extract_rules()
            end_time = time.perf_counter()

            elapsed = (end_time - start_time) * 1000  # 밀리초
            times.append(elapsed)

            if (i + 1) % 20 == 0:
                print(f"  진행: {i + 1}/{iterations}회")

        avg_time = sum(times) / len(times)
        min_time = min(times)
        max_time = max(times)
        rule_count = len(rules)

        print(f"  ✓ 평균: {avg_time:.3f}ms")
        print(f"  ✓ 최소: {min_time:.3f}ms")
        print(f"  ✓ 최대: {max_time:.3f}ms")
        print(f"  ✓ 규칙 수: {rule_count}개")

        return {
            'average': avg_time,
            'min': min_time,
            'max': max_time,
            'rule_count': rule_count,
            'iterations': iterations
        }

    def measure_inference(self, iterations: int = 1000) -> Dict[str, float]:
        """추론 실행 시간 측정"""
        print(f"\n📊 추론 실행 시간 측정 ({iterations}회 반복)...")

        engine = InferenceEngine(self.ontology_path)

        # 테스트 케이스
        test_cases = [
            {'emotion': 'Frustrated'},
            {'emotion': 'Focused'},
            {'emotion': 'Tired'},
            {'emotion': 'Anxious'},
            {'emotion': 'Happy'}
        ]

        all_times = []
        case_times = {case['emotion']: [] for case in test_cases}

        for i in range(iterations):
            for test_case in test_cases:
                start_time = time.perf_counter()
                results = engine.infer(test_case)
                end_time = time.perf_counter()

                elapsed = (end_time - start_time) * 1000  # 밀리초
                all_times.append(elapsed)
                case_times[test_case['emotion']].append(elapsed)

            if (i + 1) % 200 == 0:
                print(f"  진행: {i + 1}/{iterations}회")

        # 전체 통계
        avg_time = sum(all_times) / len(all_times)
        min_time = min(all_times)
        max_time = max(all_times)

        print(f"  ✓ 전체 평균: {avg_time:.4f}ms")
        print(f"  ✓ 전체 최소: {min_time:.4f}ms")
        print(f"  ✓ 전체 최대: {max_time:.4f}ms")

        # 감정별 통계
        print("\n  감정별 평균 시간:")
        emotion_stats = {}
        for emotion, times in case_times.items():
            emotion_avg = sum(times) / len(times)
            emotion_stats[emotion] = emotion_avg
            print(f"    {emotion}: {emotion_avg:.4f}ms")

        return {
            'average': avg_time,
            'min': min_time,
            'max': max_time,
            'iterations': iterations * len(test_cases),
            'per_emotion': emotion_stats
        }

    def measure_end_to_end(self, iterations: int = 100) -> Dict[str, float]:
        """E2E 시간 측정 (로드 + 추론)"""
        print(f"\n📊 E2E 시간 측정 ({iterations}회 반복)...")

        test_case = {'emotion': 'Frustrated'}
        times = []

        for i in range(iterations):
            start_time = time.perf_counter()

            # 엔진 초기화 + 추론
            engine = InferenceEngine(self.ontology_path)
            results = engine.infer(test_case)

            end_time = time.perf_counter()

            elapsed = (end_time - start_time) * 1000  # 밀리초
            times.append(elapsed)

            if (i + 1) % 20 == 0:
                print(f"  진행: {i + 1}/{iterations}회")

        avg_time = sum(times) / len(times)
        min_time = min(times)
        max_time = max(times)

        print(f"  ✓ 평균: {avg_time:.3f}ms")
        print(f"  ✓ 최소: {min_time:.3f}ms")
        print(f"  ✓ 최대: {max_time:.3f}ms")

        return {
            'average': avg_time,
            'min': min_time,
            'max': max_time,
            'iterations': iterations
        }

    def measure_memory_usage(self) -> Dict[str, int]:
        """메모리 사용량 측정 (근사치)"""
        print("\n📊 메모리 사용량 측정...")

        import sys

        # 온톨로지 로드
        loader = OntologyLoader(self.ontology_path)
        ontology = loader.load()

        # JSON 문자열 크기
        ontology_json = json.dumps(ontology)
        ontology_size = len(ontology_json.encode('utf-8'))

        # 규칙 추출
        rules = loader.extract_rules()
        rules_json = json.dumps(rules)
        rules_size = len(rules_json.encode('utf-8'))

        # 감정 추출
        emotions = loader.extract_emotions()
        emotions_json = json.dumps(emotions)
        emotions_size = len(emotions_json.encode('utf-8'))

        # 엔진 객체
        engine = InferenceEngine(self.ontology_path)

        total_estimated = ontology_size + rules_size + emotions_size

        print(f"  ✓ 온톨로지: {ontology_size:,} bytes ({ontology_size/1024:.2f} KB)")
        print(f"  ✓ 규칙: {rules_size:,} bytes ({rules_size/1024:.2f} KB)")
        print(f"  ✓ 감정: {emotions_size:,} bytes ({emotions_size/1024:.2f} KB)")
        print(f"  ✓ 총 예상: {total_estimated:,} bytes ({total_estimated/1024:.2f} KB)")

        return {
            'ontology_bytes': ontology_size,
            'rules_bytes': rules_size,
            'emotions_bytes': emotions_size,
            'total_bytes': total_estimated
        }

    def run_all_benchmarks(self) -> Dict:
        """모든 벤치마크 실행"""
        print("="*60)
        print("🚀 Phase 1 성능 벤치마크 시작")
        print("="*60)

        results = {}

        # 1. 온톨로지 로드
        results['ontology_load'] = self.measure_ontology_load()

        # 2. 규칙 추출
        results['rule_extraction'] = self.measure_rule_extraction()

        # 3. 추론 실행
        results['inference'] = self.measure_inference()

        # 4. E2E
        results['end_to_end'] = self.measure_end_to_end()

        # 5. 메모리
        results['memory'] = self.measure_memory_usage()

        return results

    def print_summary(self, results: Dict):
        """결과 요약 출력"""
        print("\n" + "="*60)
        print("📊 성능 벤치마크 결과 요약")
        print("="*60)

        print("\n🔍 핵심 지표:")
        print(f"  • 온톨로지 로드: {results['ontology_load']['average']:.3f}ms")
        print(f"  • 규칙 추출: {results['rule_extraction']['average']:.3f}ms")
        print(f"  • 추론 실행: {results['inference']['average']:.4f}ms")
        print(f"  • E2E 처리: {results['end_to_end']['average']:.3f}ms")
        print(f"  • 메모리 사용: {results['memory']['total_bytes']/1024:.2f} KB")

        print("\n⚡ 성능 분석:")
        inference_per_sec = 1000 / results['inference']['average']
        print(f"  • 초당 추론 횟수: {inference_per_sec:.0f}회/초")

        e2e_per_sec = 1000 / results['end_to_end']['average']
        print(f"  • 초당 E2E 처리: {e2e_per_sec:.0f}회/초")

        print("\n✅ 목표 달성 여부:")
        targets = {
            'E2E < 100ms': results['end_to_end']['average'] < 100,
            '추론 < 1ms': results['inference']['average'] < 1,
            '메모리 < 1MB': results['memory']['total_bytes'] < 1024 * 1024
        }

        for target, achieved in targets.items():
            status = "✅" if achieved else "❌"
            print(f"  {status} {target}")

        print("\n" + "="*60)


def main():
    """메인 함수"""
    benchmark = PerformanceBenchmark('01_minimal_ontology.json')
    results = benchmark.run_all_benchmarks()
    benchmark.print_summary(results)

    # JSON 파일로 저장
    output_file = 'benchmark_results.json'
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2, ensure_ascii=False)

    print(f"\n💾 결과 저장: {output_file}")
    print("\n✅ 벤치마크 완료!\n")


if __name__ == "__main__":
    main()
