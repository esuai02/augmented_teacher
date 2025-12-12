#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
증분 온톨로지 로더 단위 테스트
"""

import unittest
import tempfile
import shutil
from pathlib import Path
import xml.etree.ElementTree as ET

from incremental_loader import IncrementalOntologyLoader


class TestIncrementalOntologyLoader(unittest.TestCase):
    """증분 온톨로지 로더 테스트"""
    
    def setUp(self):
        """테스트 설정"""
        # 임시 디렉토리 생성
        self.test_dir = Path(tempfile.mkdtemp())
        self.modules_dir = self.test_dir / "modules"
        self.modules_dir.mkdir()
        
        # 테스트용 모듈 파일 생성
        self.create_test_modules()
        
        # 로더 인스턴스 생성
        self.loader = IncrementalOntologyLoader(
            modules_dir=str(self.modules_dir),
            cache_dir=str(self.test_dir / ".cache")
        )
    
    def tearDown(self):
        """테스트 정리"""
        shutil.rmtree(self.test_dir)
    
    def create_test_modules(self):
        """테스트용 모듈 파일 생성"""
        # Agent 01 모듈
        agent01_content = '''<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:at="http://mathking.kr/ontology/alphatutor#">

  <owl:Ontology rdf:about="http://mathking.kr/ontology/alphatutor#agent01">
    <rdfs:label xml:lang="ko">AlphaTutor Agent 01 Ontology</rdfs:label>
  </owl:Ontology>

  <owl:Class rdf:about="http://mathking.kr/ontology/alphatutor#TestClass01">
    <rdfs:label xml:lang="ko">TestClass01</rdfs:label>
  </owl:Class>

  <owl:Class rdf:about="http://mathking.kr/ontology/alphatutor#TestClass02">
    <rdfs:label xml:lang="ko">TestClass02</rdfs:label>
  </owl:Class>

</rdf:RDF>
'''
        
        # Agent 02 모듈
        agent02_content = '''<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:at="http://mathking.kr/ontology/alphatutor#">

  <owl:Ontology rdf:about="http://mathking.kr/ontology/alphatutor#agent02">
    <rdfs:label xml:lang="ko">AlphaTutor Agent 02 Ontology</rdfs:label>
  </owl:Ontology>

  <owl:Class rdf:about="http://mathking.kr/ontology/alphatutor#ThinkingClass01">
    <rdfs:label xml:lang="ko">ThinkingClass01</rdfs:label>
  </owl:Class>

  <owl:Class rdf:about="http://mathking.kr/ontology/alphatutor#OtherClass01">
    <rdfs:label xml:lang="ko">OtherClass01</rdfs:label>
  </owl:Class>

</rdf:RDF>
'''
        
        # core 모듈
        core_content = '''<?xml version="1.0"?>
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
         xmlns:rdfs="http://www.w3.org/2000/01/rdf-schema#"
         xmlns:owl="http://www.w3.org/2002/07/owl#"
         xmlns:at="http://mathking.kr/ontology/alphatutor#">

  <owl:Ontology rdf:about="http://mathking.kr/ontology/alphatutor#core">
    <rdfs:label xml:lang="ko">AlphaTutor Core Ontology</rdfs:label>
  </owl:Ontology>

  <owl:Class rdf:about="http://mathking.kr/ontology/alphatutor#Student">
    <rdfs:label xml:lang="ko">Student</rdfs:label>
  </owl:Class>

  <owl:Class rdf:about="http://mathking.kr/ontology/alphatutor#Goal">
    <rdfs:label xml:lang="ko">Goal</rdfs:label>
  </owl:Class>

</rdf:RDF>
'''
        
        # 파일 저장
        (self.modules_dir / "agent01.owl").write_text(agent01_content, encoding='utf-8')
        (self.modules_dir / "agent02.owl").write_text(agent02_content, encoding='utf-8')
        (self.modules_dir / "core.owl").write_text(core_content, encoding='utf-8')
    
    def test_load_classes_by_agent(self):
        """Agent별 클래스 로드 테스트"""
        classes = self.loader.load_classes_by_agent(1)
        
        self.assertIsInstance(classes, set)
        self.assertGreater(len(classes), 0)
        self.assertIn("http://mathking.kr/ontology/alphatutor#TestClass01", classes)
        self.assertIn("http://mathking.kr/ontology/alphatutor#TestClass02", classes)
    
    def test_load_nonexistent_agent(self):
        """존재하지 않는 Agent 로드 테스트"""
        classes = self.loader.load_classes_by_agent(99)
        
        self.assertIsInstance(classes, set)
        self.assertEqual(len(classes), 0)
    
    def test_get_classes_by_prefix(self):
        """접두사로 클래스 검색 테스트"""
        classes = self.loader.get_classes_by_prefix("http://mathking.kr/ontology/alphatutor#Thinking")
        
        self.assertIsInstance(classes, set)
        self.assertIn("http://mathking.kr/ontology/alphatutor#ThinkingClass01", classes)
        self.assertNotIn("http://mathking.kr/ontology/alphatutor#OtherClass01", classes)
    
    def test_load_all_classes(self):
        """모든 클래스 로드 테스트"""
        classes = self.loader.load_all_classes()
        
        self.assertIsInstance(classes, set)
        self.assertGreater(len(classes), 0)
        # core 모듈의 클래스 포함 확인
        self.assertIn("http://mathking.kr/ontology/alphatutor#Student", classes)
        self.assertIn("http://mathking.kr/ontology/alphatutor#Goal", classes)
        # Agent 모듈의 클래스 포함 확인
        self.assertIn("http://mathking.kr/ontology/alphatutor#TestClass01", classes)
    
    def test_caching(self):
        """캐싱 기능 테스트"""
        # 첫 번째 로드
        classes1 = self.loader.load_classes_by_agent(1)
        
        # 두 번째 로드 (캐시 사용)
        classes2 = self.loader.load_classes_by_agent(1)
        
        self.assertEqual(classes1, classes2)
        # 캐시가 작동하는지 확인 (같은 결과 반환)
    
    def test_get_module_info(self):
        """모듈 정보 조회 테스트"""
        info = self.loader.get_module_info(1)
        
        self.assertIsInstance(info, dict)
        self.assertIn('file_path', info)
        self.assertIn('file_size', info)
        self.assertIn('class_count', info)
        self.assertGreater(info['class_count'], 0)
    
    def test_clear_cache(self):
        """캐시 초기화 테스트"""
        # 클래스 로드 (캐시 생성)
        self.loader.load_classes_by_agent(1)
        
        # 캐시 초기화
        self.loader.clear_cache()
        
        # 캐시가 비어있는지 확인
        self.assertEqual(len(self.loader._cache), 0)
        self.assertEqual(len(self.loader._file_hashes), 0)
    
    def test_load_module_streaming(self):
        """스트리밍 로드 테스트"""
        module_path = self.modules_dir / "agent01.owl"
        classes = self.loader.load_module_streaming(module_path)
        
        self.assertIsInstance(classes, set)
        self.assertGreater(len(classes), 0)
        self.assertIn("http://mathking.kr/ontology/alphatutor#TestClass01", classes)


if __name__ == "__main__":
    unittest.main()

