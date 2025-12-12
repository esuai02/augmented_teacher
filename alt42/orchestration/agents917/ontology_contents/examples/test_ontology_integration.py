#!/usr/bin/env python3
"""
ontology.jsonld 통합 테스트

새로 생성된 ontology/ontology.jsonld 파일이 기존 시스템과 정상적으로 연동되는지 검증합니다.
"""

import sys
import os

# 현재 디렉토리를 Python 경로에 추가
sys.path.insert(0, os.path.dirname(__file__))

from ontology_loader import OntologyLoader


def test_ontology_integration():
    """
    ontology.jsonld 통합 테스트

    검증 항목:
    1. 파일 로드 성공
    2. Phase 1 감정 5개 존재
    3. Phase 1 규칙 10개 존재
    4. 규칙 우선순위 정렬 확인
    5. 클래스 구조 확인
    """
    print("=" * 70)
    print("ontology.jsonld 통합 테스트")
    print("=" * 70)
    print()

    # 1. 파일 로드 테스트
    print("📂 [1/5] 파일 로드 테스트...")
    try:
        loader = OntologyLoader('../ontology/ontology.jsonld')
        ontology = loader.load()
        print("✅ ontology.jsonld 로드 성공")
        print(f"   - @context 네임스페이스: {len(ontology.get('@context', {}))}개")
        print(f"   - @graph 엔티티: {len(ontology.get('@graph', []))}개")
    except Exception as e:
        print(f"❌ 로드 실패: {e}")
        return False
    print()

    # 2. Phase 1 감정 테스트
    print("😊 [2/5] Phase 1 감정 테스트 (5개 예상)...")
    emotions = loader.extract_emotions()
    print(f"   감정 총 {len(emotions)}개 발견:")

    expected_emotions = ['Frustrated', 'Focused', 'Tired', 'Anxious', 'Happy']
    found_emotions = [e['id'].split(':')[-1] for e in emotions]

    for emotion in emotions:
        emotion_id = emotion['id'].split(':')[-1]
        status = "✅" if emotion_id in expected_emotions else "⚠️"
        print(f"   {status} {emotion['id']}: {emotion['label']}")

    if len(emotions) == 5:
        print("✅ Phase 1 감정 5개 확인")
    else:
        print(f"⚠️ 예상: 5개, 실제: {len(emotions)}개")
    print()

    # 3. Phase 1 규칙 테스트
    print("📋 [3/5] Phase 1 규칙 테스트 (10개 예상)...")
    rules = loader.extract_rules()
    print(f"   규칙 총 {len(rules)}개 발견:")

    for rule in rules:
        rule_id = rule['id'].split(':')[-1]
        print(f"   - {rule_id}: {rule['name']} (우선순위: {rule['priority']})")

    if len(rules) == 10:
        print("✅ Phase 1 규칙 10개 확인")
    else:
        print(f"⚠️ 예상: 10개, 실제: {len(rules)}개")
    print()

    # 4. 규칙 우선순위 정렬 확인
    print("🔢 [4/5] 규칙 우선순위 정렬 확인...")
    priorities = [rule['priority'] for rule in rules]
    is_sorted = all(priorities[i] >= priorities[i+1] for i in range(len(priorities)-1))

    if is_sorted:
        print(f"✅ 우선순위 내림차순 정렬 확인 (최고: {priorities[0]}, 최저: {priorities[-1]})")
    else:
        print(f"❌ 우선순위 정렬 오류")
    print()

    # 5. 클래스 구조 확인
    print("📚 [5/5] 클래스 구조 확인...")
    classes = loader.extract_classes()
    print(f"   클래스 총 {len(classes)}개 발견:")

    expected_classes = ['Student', 'Emotion', 'InferenceRule', 'Condition']
    for cls in classes[:10]:  # 처음 10개만 출력
        cls_id = cls['id'].split(':')[-1]
        status = "✅" if cls_id in expected_classes else "📦"
        print(f"   {status} {cls['id']}: {cls['label']}")

    if len(classes) > 10:
        print(f"   ... 및 {len(classes) - 10}개 추가 클래스")
    print()

    # 최종 결과
    print("=" * 70)
    print("📊 통합 테스트 결과")
    print("=" * 70)
    print(f"✅ 파일 로드: 성공")
    print(f"{'✅' if len(emotions) == 5 else '⚠️'} 감정: {len(emotions)}/5개")
    print(f"{'✅' if len(rules) == 10 else '⚠️'} 규칙: {len(rules)}/10개")
    print(f"{'✅' if is_sorted else '❌'} 우선순위 정렬: {'성공' if is_sorted else '실패'}")
    print(f"✅ 클래스: {len(classes)}개")
    print("=" * 70)

    # 종합 판정
    success = (len(emotions) == 5 and len(rules) == 10 and is_sorted)

    if success:
        print("\n🎉 통합 테스트 성공! ontology.jsonld가 정상적으로 작동합니다.")
    else:
        print("\n⚠️ 일부 테스트 실패. 상세 내용을 확인하세요.")

    return success


if __name__ == "__main__":
    success = test_ontology_integration()
    sys.exit(0 if success else 1)
