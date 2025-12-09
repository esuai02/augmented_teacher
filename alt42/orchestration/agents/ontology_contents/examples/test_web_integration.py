#!/usr/bin/env python3
"""
웹 인터페이스 연동 테스트

inference_lab_v3.php에서 사용하는 것과 동일한 방식으로
ontology.jsonld를 로드하여 추론이 정상 작동하는지 확인합니다.
"""

import sys
import os

# 현재 디렉토리를 Python 경로에 추가
sys.path.insert(0, os.path.dirname(__file__))

from inference_engine import InferenceEngine


def test_web_integration():
    """
    웹 인터페이스 연동 테스트

    inference_lab_v3.php에서 사용하는 경로로 온톨로지를 로드하고
    5가지 감정에 대한 추론을 실행하여 결과를 확인합니다.
    """
    print("=" * 70)
    print("🌐 inference_lab_v3.php 연동 테스트")
    print("=" * 70)
    print()

    # 1. 온톨로지 로드 (PHP에서 사용하는 경로와 동일)
    print("📂 [1/6] 온톨로지 로드 테스트...")
    ontology_path = os.path.join(os.path.dirname(__file__), '..', 'ontology', 'ontology.jsonld')

    try:
        engine = InferenceEngine(ontology_path)
        print(f"✅ ontology.jsonld 로드 성공")
        print(f"   경로: {ontology_path}")
        print(f"   규칙 수: {len(engine.rules)}개")
    except Exception as e:
        print(f"❌ 로드 실패: {e}")
        return False
    print()

    # 2-6. 5가지 감정에 대한 추론 테스트
    test_cases = [
        ("철수", "Frustrated", "😰 좌절"),
        ("영희", "Focused", "😊 집중"),
        ("민수", "Tired", "😴 피로"),
        ("지수", "Anxious", "😟 불안"),
        ("현수", "Happy", "😄 기쁨")
    ]

    all_success = True

    for i, (student, emotion, label) in enumerate(test_cases, start=2):
        print(f"{label} [{i}/6] {student} - {emotion} 추론 테스트...")

        student_state = {
            "student": student,
            "emotion": emotion
        }

        try:
            results = engine.infer(student_state)

            if results:
                print(f"   ✅ 매칭된 규칙: {len(results)}개")
                best = results[0]
                print(f"   최우선 규칙: {best['rule_name']}")
                print(f"   결론: {best['conclusion']}")
                print(f"   우선순위: {best['priority']}")
            else:
                print(f"   ⚠️ 매칭된 규칙이 없습니다")
                all_success = False

        except Exception as e:
            print(f"   ❌ 추론 실패: {e}")
            all_success = False

        print()

    # 최종 결과
    print("=" * 70)
    print("📊 연동 테스트 결과")
    print("=" * 70)
    print(f"✅ 온톨로지 로드: 성공")
    print(f"{'✅' if all_success else '❌'} 추론 실행: {'성공' if all_success else '실패'}")
    print(f"✅ 웹 인터페이스 연동: {'준비 완료' if all_success else '문제 발견'}")
    print("=" * 70)

    if all_success:
        print("\n🎉 inference_lab_v3.php와 ontology.jsonld 연동 준비 완료!")
        print("   웹 브라우저에서 다음 URL로 접속하여 테스트할 수 있습니다:")
        print("   https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v3.php")
    else:
        print("\n⚠️ 일부 테스트 실패. 상세 내용을 확인하세요.")

    return all_success


if __name__ == "__main__":
    success = test_web_integration()
    sys.exit(0 if success else 1)
