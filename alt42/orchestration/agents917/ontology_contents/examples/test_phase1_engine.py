#!/usr/bin/env python3
"""
Phase 1 추론 엔진 단위 테스트

ontology_loader.py와 inference_engine.py의 기능을 검증합니다.
"""

import unittest
from ontology_loader import OntologyLoader
from inference_engine import InferenceEngine


class TestOntologyLoader(unittest.TestCase):
    """온톨로지 로더 테스트"""

    def setUp(self):
        """각 테스트 전에 실행"""
        self.loader = OntologyLoader('01_minimal_ontology.json')
        self.ontology = self.loader.load()

    def test_load_ontology(self):
        """온톨로지 파일 로드 테스트"""
        self.assertIsNotNone(self.ontology)
        self.assertIn('@context', self.ontology)
        self.assertIn('@graph', self.ontology)

    def test_extract_classes(self):
        """클래스 추출 테스트"""
        classes = self.loader.extract_classes()
        self.assertEqual(len(classes), 4)

        class_ids = [cls['id'] for cls in classes]
        self.assertIn('Student', class_ids)
        self.assertIn('Emotion', class_ids)
        self.assertIn('InferenceRule', class_ids)
        self.assertIn('Condition', class_ids)

    def test_extract_emotions(self):
        """감정 추출 테스트"""
        emotions = self.loader.extract_emotions()
        self.assertEqual(len(emotions), 5)

        emotion_ids = [emotion['id'] for emotion in emotions]
        self.assertIn('Frustrated', emotion_ids)
        self.assertIn('Focused', emotion_ids)
        self.assertIn('Tired', emotion_ids)
        self.assertIn('Anxious', emotion_ids)
        self.assertIn('Happy', emotion_ids)

    def test_extract_rules(self):
        """규칙 추출 테스트"""
        rules = self.loader.extract_rules()
        self.assertEqual(len(rules), 10)

    def test_rules_sorted_by_priority(self):
        """규칙이 우선순위로 정렬되는지 테스트"""
        rules = self.loader.extract_rules()

        # 첫 번째 규칙의 우선순위가 가장 높아야 함
        self.assertGreaterEqual(rules[0]['priority'], rules[-1]['priority'])

        # 전체 리스트가 내림차순인지 확인
        for i in range(len(rules) - 1):
            self.assertGreaterEqual(rules[i]['priority'], rules[i + 1]['priority'])

    def test_get_rule_by_id(self):
        """ID로 규칙 조회 테스트"""
        rule = self.loader.get_rule_by_id('rule_frustrated')
        self.assertIsNotNone(rule)
        self.assertEqual(rule['id'], 'rule_frustrated')
        self.assertEqual(rule['name'], '좌절 → 격려')

    def test_get_emotion_by_id(self):
        """ID로 감정 조회 테스트"""
        emotion = self.loader.get_emotion_by_id('Frustrated')
        self.assertIsNotNone(emotion)
        self.assertEqual(emotion['id'], 'Frustrated')
        self.assertEqual(emotion['label'], '좌절')


class TestInferenceEngine(unittest.TestCase):
    """추론 엔진 테스트"""

    def setUp(self):
        """각 테스트 전에 실행"""
        self.engine = InferenceEngine('01_minimal_ontology.json')

    def test_engine_initialization(self):
        """엔진 초기화 테스트"""
        self.assertIsNotNone(self.engine.loader)
        self.assertIsNotNone(self.engine.ontology)
        self.assertEqual(len(self.engine.rules), 10)

    def test_evaluate_condition_frustrated(self):
        """좌절 조건 평가 테스트"""
        condition = {'emotionEquals': 'Frustrated'}
        student_state = {'emotion': 'Frustrated'}

        result = self.engine.evaluate_condition(condition, student_state)
        self.assertTrue(result)

    def test_evaluate_condition_mismatch(self):
        """조건 불일치 테스트"""
        condition = {'emotionEquals': 'Frustrated'}
        student_state = {'emotion': 'Happy'}

        result = self.engine.evaluate_condition(condition, student_state)
        self.assertFalse(result)

    def test_infer_frustrated(self):
        """좌절 상태 추론 테스트"""
        student_state = {'emotion': 'Frustrated'}
        results = self.engine.infer(student_state)

        # 좌절 감정은 2개 규칙에 매칭되어야 함
        self.assertEqual(len(results), 2)

        # 최우선 규칙 확인
        self.assertEqual(results[0]['rule_id'], 'rule_frustrated')
        self.assertEqual(results[0]['conclusion'], '격려 필요')
        self.assertEqual(results[0]['priority'], 1.0)

    def test_infer_focused(self):
        """집중 상태 추론 테스트"""
        student_state = {'emotion': 'Focused'}
        results = self.engine.infer(student_state)

        self.assertEqual(len(results), 2)
        self.assertEqual(results[0]['rule_id'], 'rule_focused')
        self.assertEqual(results[0]['conclusion'], '학습 진행')

    def test_infer_tired(self):
        """피로 상태 추론 테스트"""
        student_state = {'emotion': 'Tired'}
        results = self.engine.infer(student_state)

        self.assertEqual(len(results), 2)
        self.assertEqual(results[0]['conclusion'], '휴식 필요')

    def test_infer_anxious(self):
        """불안 상태 추론 테스트"""
        student_state = {'emotion': 'Anxious'}
        results = self.engine.infer(student_state)

        self.assertEqual(len(results), 2)
        self.assertEqual(results[0]['priority'], 0.9)

    def test_infer_happy(self):
        """기쁨 상태 추론 테스트"""
        student_state = {'emotion': 'Happy'}
        results = self.engine.infer(student_state)

        self.assertEqual(len(results), 2)
        self.assertEqual(results[0]['priority'], 0.8)

    def test_infer_unknown_emotion(self):
        """알 수 없는 감정 테스트"""
        student_state = {'emotion': 'Unknown'}
        results = self.engine.infer(student_state)

        # 매칭되는 규칙이 없어야 함
        self.assertEqual(len(results), 0)

    def test_infer_best(self):
        """최우선 규칙 반환 테스트"""
        student_state = {'emotion': 'Frustrated'}
        best = self.engine.infer_best(student_state)

        self.assertIsNotNone(best)
        self.assertEqual(best['rule_id'], 'rule_frustrated')
        self.assertEqual(best['priority'], 1.0)

    def test_infer_best_no_match(self):
        """매칭 없을 때 None 반환 테스트"""
        student_state = {'emotion': 'Unknown'}
        best = self.engine.infer_best(student_state)

        self.assertIsNone(best)

    def test_explain_reasoning(self):
        """추론 설명 생성 테스트"""
        student_state = {'emotion': 'Frustrated'}
        explanation = self.engine.explain_reasoning(student_state)

        self.assertIsInstance(explanation, str)
        self.assertIn('학생 상태', explanation)
        self.assertIn('매칭된 규칙 수', explanation)


def run_tests():
    """테스트 실행"""
    print("="*60)
    print("Phase 1 추론 엔진 단위 테스트")
    print("="*60)
    print()

    # 테스트 스위트 생성
    loader = unittest.TestLoader()
    suite = unittest.TestSuite()

    # 테스트 추가
    suite.addTests(loader.loadTestsFromTestCase(TestOntologyLoader))
    suite.addTests(loader.loadTestsFromTestCase(TestInferenceEngine))

    # 테스트 실행
    runner = unittest.TextTestRunner(verbosity=2)
    result = runner.run(suite)

    # 결과 요약
    print()
    print("="*60)
    print("테스트 결과 요약")
    print("="*60)
    print(f"총 테스트 수: {result.testsRun}개")
    print(f"성공: {result.testsRun - len(result.failures) - len(result.errors)}개")
    print(f"실패: {len(result.failures)}개")
    print(f"에러: {len(result.errors)}개")

    if result.wasSuccessful():
        print()
        print("✅ 모든 테스트 통과!")
    else:
        print()
        print("❌ 일부 테스트 실패")

    print("="*60)

    return result.wasSuccessful()


if __name__ == "__main__":
    success = run_tests()
    exit(0 if success else 1)
