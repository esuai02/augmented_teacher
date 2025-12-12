#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
증분 온톨로지 로더 (Incremental Ontology Loader)

필요한 모듈만 로드하고 캐싱하여 성능을 최적화하는 온톨로지 로더
- 파일 해시 기반 캐싱
- Agent별 모듈 로딩
- 스트리밍 XML 파싱

사용법:
    from incremental_loader import IncrementalOntologyLoader
    
    loader = IncrementalOntologyLoader("../modules")
    classes = loader.load_classes_by_agent(8)  # Agent 08 관련 클래스만 로드
"""

import os
import sys
import hashlib
import pickle
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Set, Dict, Optional, List
from functools import lru_cache

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')


class IncrementalOntologyLoader:
    """증분 온톨로지 로더"""
    
    def __init__(self, modules_dir: str = "modules", cache_dir: str = ".ontology_cache"):
        """
        Args:
            modules_dir: 모듈 파일이 있는 디렉토리 경로
            cache_dir: 캐시 디렉토리 경로
        """
        self.modules_dir = Path(modules_dir)
        self.cache_dir = Path(cache_dir)
        self.cache_dir.mkdir(parents=True, exist_ok=True)
        
        self._cache: Dict[str, ET.ElementTree] = {}
        self._file_hashes: Dict[str, str] = {}
        self._class_cache: Dict[str, Set[str]] = {}
        
        # 네임스페이스
        self.namespace = "http://mathking.kr/ontology/alphatutor#"
    
    def _get_file_hash(self, file_path: Path) -> str:
        """파일 해시 계산 (SHA256)"""
        try:
            with open(file_path, 'rb') as f:
                return hashlib.sha256(f.read()).hexdigest()
        except Exception as e:
            print(f"경고: 파일 해시 계산 실패 ({file_path}): {e}", file=sys.stderr)
            return ""
    
    def _get_cache_key(self, file_path: Path) -> str:
        """캐시 키 생성"""
        return str(file_path.resolve())
    
    def _load_module_tree(self, module_path: Path) -> Optional[ET.ElementTree]:
        """모듈 파일을 ElementTree로 로드 (캐시 사용)"""
        cache_key = self._get_cache_key(module_path)
        
        # 파일 존재 확인
        if not module_path.exists():
            print(f"경고: 모듈 파일을 찾을 수 없습니다: {module_path}", file=sys.stderr)
            return None
        
        # 현재 파일 해시 계산
        current_hash = self._get_file_hash(module_path)
        
        # 캐시 확인
        if cache_key in self._cache:
            cached_hash = self._file_hashes.get(cache_key)
            if cached_hash == current_hash:
                return self._cache[cache_key]
        
        # 파일 로드
        try:
            tree = ET.parse(str(module_path))
            self._cache[cache_key] = tree
            self._file_hashes[cache_key] = current_hash
            return tree
        except ET.ParseError as e:
            print(f"오류: XML 파싱 실패 ({module_path}): {e}", file=sys.stderr)
            return None
        except Exception as e:
            print(f"오류: 파일 로드 실패 ({module_path}): {e}", file=sys.stderr)
            return None
    
    def _extract_classes_from_tree(self, tree: ET.ElementTree) -> Set[str]:
        """ElementTree에서 클래스 URI 추출"""
        classes = set()
        root = tree.getroot()
        
        # 네임스페이스 정의
        ns = {
            'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
            'owl': 'http://www.w3.org/2002/07/owl#',
            'rdfs': 'http://www.w3.org/2000/01/rdf-schema#'
        }
        
        # owl:Class 요소 찾기
        for class_elem in root.findall('.//owl:Class', ns):
            rdf_about = class_elem.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about')
            if rdf_about:
                classes.add(rdf_about)
        
        return classes
    
    def load_classes_by_agent(self, agent_number: int) -> Set[str]:
        """
        특정 Agent 관련 클래스만 로드
        
        Args:
            agent_number: Agent 번호 (예: 8)
        
        Returns:
            클래스 URI 집합
        """
        module_file = self.modules_dir / f"agent{agent_number:02d}.owl"
        
        if not module_file.exists():
            print(f"경고: Agent {agent_number:02d} 모듈 파일을 찾을 수 없습니다: {module_file}")
            return set()
        
        # 캐시 키 생성
        cache_key = f"agent_{agent_number:02d}"
        
        # 클래스 캐시 확인
        if cache_key in self._class_cache:
            current_hash = self._get_file_hash(module_file)
            cached_hash = self._file_hashes.get(self._get_cache_key(module_file))
            if cached_hash == current_hash:
                return self._class_cache[cache_key]
        
        # 모듈 로드
        tree = self._load_module_tree(module_file)
        if tree is None:
            return set()
        
        # 클래스 추출
        classes = self._extract_classes_from_tree(tree)
        
        # 캐시 저장
        self._class_cache[cache_key] = classes
        
        return classes
    
    def load_all_classes(self) -> Set[str]:
        """모든 모듈에서 클래스 로드"""
        all_classes = set()
        
        # core 모듈 로드
        core_file = self.modules_dir / "core.owl"
        if core_file.exists():
            tree = self._load_module_tree(core_file)
            if tree:
                all_classes.update(self._extract_classes_from_tree(tree))
        
        # 모든 Agent 모듈 로드
        for agent_file in sorted(self.modules_dir.glob("agent*.owl")):
            tree = self._load_module_tree(agent_file)
            if tree:
                all_classes.update(self._extract_classes_from_tree(tree))
        
        return all_classes
    
    def get_classes_by_prefix(self, prefix: str) -> Set[str]:
        """
        특정 URI 접두사로 시작하는 클래스만 로드
        
        Args:
            prefix: URI 접두사 (예: "http://mathking.kr/ontology/alphatutor#Thinking")
        
        Returns:
            클래스 URI 집합
        """
        matching_classes = set()
        
        # 모든 모듈 검색
        for module_file in sorted(self.modules_dir.glob("*.owl")):
            tree = self._load_module_tree(module_file)
            if tree:
                classes = self._extract_classes_from_tree(tree)
                matching_classes.update([c for c in classes if c.startswith(prefix)])
        
        return matching_classes
    
    def load_module_streaming(self, module_path: Path) -> Set[str]:
        """
        스트리밍 방식으로 모듈 로드 (메모리 효율적)
        
        Args:
            module_path: 모듈 파일 경로
        
        Returns:
            클래스 URI 집합
        """
        classes = set()
        
        if not module_path.exists():
            return classes
        
        try:
            # 스트리밍 파싱
            context = ET.iterparse(str(module_path), events=('start', 'end'))
            
            for event, elem in context:
                if event == 'end':
                    # owl:Class 요소인지 확인
                    if elem.tag.endswith('Class') or 'Class' in elem.tag:
                        rdf_about = elem.get('{http://www.w3.org/1999/02/22-rdf-syntax-ns#}about')
                        if rdf_about:
                            classes.add(rdf_about)
                    # 메모리 해제
                    elem.clear()
            
            return classes
        except Exception as e:
            print(f"오류: 스트리밍 파싱 실패 ({module_path}): {e}", file=sys.stderr)
            return classes
    
    def clear_cache(self) -> None:
        """캐시 초기화"""
        self._cache.clear()
        self._file_hashes.clear()
        self._class_cache.clear()
        
        # 캐시 디렉토리 정리 (선택사항)
        # for cache_file in self.cache_dir.glob("*.cache"):
        #     cache_file.unlink()
    
    def get_module_info(self, agent_number: int) -> Dict:
        """
        모듈 정보 조회
        
        Args:
            agent_number: Agent 번호
        
        Returns:
            모듈 정보 딕셔너리 (파일 경로, 클래스 수, 파일 크기 등)
        """
        module_file = self.modules_dir / f"agent{agent_number:02d}.owl"
        
        if not module_file.exists():
            return {}
        
        classes = self.load_classes_by_agent(agent_number)
        file_size = module_file.stat().st_size
        file_hash = self._get_file_hash(module_file)
        
        return {
            'file_path': str(module_file),
            'file_size': file_size,
            'file_hash': file_hash,
            'class_count': len(classes),
            'classes': list(classes)
        }


def main():
    """테스트 및 예제"""
    import argparse
    
    parser = argparse.ArgumentParser(description='증분 온톨로지 로더 테스트')
    parser.add_argument(
        '--modules-dir',
        default='../modules',
        help='모듈 디렉토리 경로 (기본값: ../modules)'
    )
    parser.add_argument(
        '--agent',
        type=int,
        help='로드할 Agent 번호'
    )
    parser.add_argument(
        '--prefix',
        help='클래스 URI 접두사 필터'
    )
    parser.add_argument(
        '--list-all',
        action='store_true',
        help='모든 클래스 나열'
    )
    
    args = parser.parse_args()
    
    loader = IncrementalOntologyLoader(args.modules_dir)
    
    if args.agent:
        print(f"Agent {args.agent:02d} 관련 클래스 로드 중...")
        classes = loader.load_classes_by_agent(args.agent)
        print(f"  발견된 클래스 수: {len(classes)}")
        print(f"\n클래스 목록:")
        for cls in sorted(classes)[:20]:  # 처음 20개만 표시
            print(f"  - {cls}")
        if len(classes) > 20:
            print(f"  ... 외 {len(classes) - 20}개")
        
        # 모듈 정보
        info = loader.get_module_info(args.agent)
        print(f"\n모듈 정보:")
        print(f"  파일 경로: {info.get('file_path')}")
        print(f"  파일 크기: {info.get('file_size')} bytes")
        print(f"  클래스 수: {info.get('class_count')}")
    
    elif args.prefix:
        print(f"접두사 '{args.prefix}'로 시작하는 클래스 검색 중...")
        classes = loader.get_classes_by_prefix(args.prefix)
        print(f"  발견된 클래스 수: {len(classes)}")
        for cls in sorted(classes):
            print(f"  - {cls}")
    
    elif args.list_all:
        print("모든 클래스 로드 중...")
        classes = loader.load_all_classes()
        print(f"  총 클래스 수: {len(classes)}")
        print(f"\n처음 50개 클래스:")
        for cls in sorted(list(classes))[:50]:
            print(f"  - {cls}")
        if len(classes) > 50:
            print(f"  ... 외 {len(classes) - 50}개")
    
    else:
        print("사용법:")
        print("  --agent N        : Agent N 관련 클래스 로드")
        print("  --prefix PREFIX  : 접두사로 시작하는 클래스 검색")
        print("  --list-all       : 모든 클래스 나열")


if __name__ == "__main__":
    main()

