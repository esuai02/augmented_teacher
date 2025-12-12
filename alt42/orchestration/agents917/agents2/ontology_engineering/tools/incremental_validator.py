#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
증분 온톨로지 검증 시스템 (Incremental Ontology Validator)

변경된 모듈만 검증하여 검증 시간을 단축하는 시스템
- 파일 해시 기반 검증 캐시
- 변경된 모듈만 재검증
- 검증 결과 JSON 캐시 저장

사용법:
    from incremental_validator import IncrementalValidator
    
    validator = IncrementalValidator()
    result = validator.validate_module("modules/agent08.owl")
"""

import os
import sys
import json
import hashlib
import xml.etree.ElementTree as ET
from pathlib import Path
from typing import Dict, List, Optional, Set
from datetime import datetime

# Windows 콘솔 인코딩 설정
if sys.platform == 'win32':
    import io
    sys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8', errors='replace')
    sys.stderr = io.TextIOWrapper(sys.stderr.buffer, encoding='utf-8', errors='replace')


class IncrementalValidator:
    """증분 검증기"""
    
    def __init__(self, cache_file: str = ".validation_cache.json"):
        """
        Args:
            cache_file: 검증 캐시 파일 경로
        """
        self.cache_file = Path(cache_file)
        self.cache = self._load_cache()
    
    def _load_cache(self) -> dict:
        """캐시 로드"""
        if self.cache_file.exists():
            try:
                with open(self.cache_file, 'r', encoding='utf-8') as f:
                    return json.load(f)
            except Exception as e:
                print(f"경고: 캐시 파일 로드 실패: {e}", file=sys.stderr)
                return {}
        return {}
    
    def _save_cache(self) -> None:
        """캐시 저장"""
        try:
            with open(self.cache_file, 'w', encoding='utf-8') as f:
                json.dump(self.cache, f, indent=2, ensure_ascii=False)
        except Exception as e:
            print(f"경고: 캐시 파일 저장 실패: {e}", file=sys.stderr)
    
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
    
    def validate_xml_structure(self, file_path: Path) -> Dict:
        """
        XML 구조 검증
        
        Returns:
            검증 결과 딕셔너리
        """
        errors = []
        warnings = []
        
        try:
            tree = ET.parse(str(file_path))
            root = tree.getroot()
            
            # 기본 XML 구조 확인
            if root.tag.endswith('RDF'):
                # RDF 루트 요소 확인
                pass
            else:
                errors.append("루트 요소가 RDF가 아닙니다.")
            
            # 네임스페이스 확인
            ns = {
                'rdf': 'http://www.w3.org/1999/02/22-rdf-syntax-ns#',
                'owl': 'http://www.w3.org/2002/07/owl#',
                'rdfs': 'http://www.w3.org/2000/01/rdf-schema#'
            }
            
            # owl:Ontology 요소 확인
            ontology = root.find('.//owl:Ontology', ns)
            if ontology is None:
                warnings.append("owl:Ontology 요소가 없습니다.")
            
            # 클래스 수 계산
            classes = root.findall('.//owl:Class', ns)
            class_count = len(classes)
            
            # 속성 수 계산
            properties = root.findall('.//owl:ObjectProperty', ns) + root.findall('.//owl:DataProperty', ns)
            property_count = len(properties)
            
            return {
                'status': 'valid' if not errors else 'invalid',
                'errors': errors,
                'warnings': warnings,
                'class_count': class_count,
                'property_count': property_count,
                'timestamp': datetime.now().isoformat()
            }
            
        except ET.ParseError as e:
            return {
                'status': 'invalid',
                'errors': [f"XML 파싱 오류: {str(e)}"],
                'warnings': [],
                'class_count': 0,
                'property_count': 0,
                'timestamp': datetime.now().isoformat()
            }
        except Exception as e:
            return {
                'status': 'invalid',
                'errors': [f"검증 오류: {str(e)}"],
                'warnings': [],
                'class_count': 0,
                'property_count': 0,
                'timestamp': datetime.now().isoformat()
            }
    
    def validate_module(self, module_path: str) -> Dict:
        """
        모듈 검증 (캐시 사용)
        
        Args:
            module_path: 모듈 파일 경로
        
        Returns:
            검증 결과 딕셔너리
        """
        file_path = Path(module_path)
        
        if not file_path.exists():
            return {
                'status': 'error',
                'errors': [f"파일을 찾을 수 없습니다: {module_path}"],
                'warnings': [],
                'class_count': 0,
                'property_count': 0,
                'timestamp': datetime.now().isoformat()
            }
        
        cache_key = self._get_cache_key(file_path)
        current_hash = self._get_file_hash(file_path)
        
        # 캐시 확인
        if cache_key in self.cache:
            cached = self.cache[cache_key]
            if cached.get('hash') == current_hash:
                print(f"✅ 캐시 사용: {file_path.name}")
                return cached.get('result', {})
        
        # 검증 실행
        print(f"🔍 검증 중: {file_path.name}")
        result = self.validate_xml_structure(file_path)
        
        # 캐시 저장
        self.cache[cache_key] = {
            'hash': current_hash,
            'timestamp': datetime.now().isoformat(),
            'result': result
        }
        self._save_cache()
        
        return result
    
    def validate_all_modules(self, modules_dir: str) -> Dict:
        """
        모든 모듈 검증
        
        Args:
            modules_dir: 모듈 디렉토리 경로
        
        Returns:
            전체 검증 결과 딕셔너리
        """
        modules_path = Path(modules_dir)
        module_files = sorted(modules_path.glob("*.owl"))
        
        results = {}
        total_errors = 0
        total_warnings = 0
        total_classes = 0
        total_properties = 0
        
        for module_file in module_files:
            result = self.validate_module(str(module_file))
            results[module_file.name] = result
            
            if result.get('status') == 'invalid':
                total_errors += len(result.get('errors', []))
            total_warnings += len(result.get('warnings', []))
            total_classes += result.get('class_count', 0)
            total_properties += result.get('property_count', 0)
        
        return {
            'modules': results,
            'summary': {
                'total_modules': len(module_files),
                'total_errors': total_errors,
                'total_warnings': total_warnings,
                'total_classes': total_classes,
                'total_properties': total_properties,
                'timestamp': datetime.now().isoformat()
            }
        }
    
    def invalidate_cache(self, module_path: Optional[str] = None) -> None:
        """
        캐시 무효화
        
        Args:
            module_path: 특정 모듈 경로 (None이면 전체 캐시 삭제)
        """
        if module_path is None:
            # 전체 캐시 삭제
            self.cache.clear()
            if self.cache_file.exists():
                self.cache_file.unlink()
            print("✅ 전체 캐시가 삭제되었습니다.")
        else:
            # 특정 모듈 캐시 삭제
            file_path = Path(module_path)
            cache_key = self._get_cache_key(file_path)
            if cache_key in self.cache:
                del self.cache[cache_key]
                self._save_cache()
                print(f"✅ {file_path.name} 캐시가 삭제되었습니다.")
            else:
                print(f"경고: {file_path.name}의 캐시를 찾을 수 없습니다.")
    
    def get_cache_stats(self) -> Dict:
        """캐시 통계 조회"""
        return {
            'cached_modules': len(self.cache),
            'cache_file': str(self.cache_file),
            'cache_exists': self.cache_file.exists()
        }


def main():
    """테스트 및 예제"""
    import argparse
    
    parser = argparse.ArgumentParser(description='증분 온톨로지 검증기')
    parser.add_argument(
        '--modules-dir',
        default='../modules',
        help='모듈 디렉토리 경로 (기본값: ../modules)'
    )
    parser.add_argument(
        '--module',
        help='검증할 특정 모듈 파일'
    )
    parser.add_argument(
        '--clear-cache',
        action='store_true',
        help='캐시 초기화'
    )
    parser.add_argument(
        '--cache-stats',
        action='store_true',
        help='캐시 통계 표시'
    )
    
    args = parser.parse_args()
    
    validator = IncrementalValidator()
    
    if args.clear_cache:
        validator.invalidate_cache()
        return
    
    if args.cache_stats:
        stats = validator.get_cache_stats()
        print("캐시 통계:")
        print(f"  캐시된 모듈 수: {stats['cached_modules']}")
        print(f"  캐시 파일: {stats['cache_file']}")
        print(f"  캐시 파일 존재: {stats['cache_exists']}")
        return
    
    if args.module:
        print(f"모듈 검증: {args.module}")
        result = validator.validate_module(args.module)
        print(f"\n검증 결과:")
        print(f"  상태: {result.get('status')}")
        if result.get('errors'):
            print(f"  오류: {len(result.get('errors'))}개")
            for error in result.get('errors'):
                print(f"    - {error}")
        if result.get('warnings'):
            print(f"  경고: {len(result.get('warnings'))}개")
            for warning in result.get('warnings'):
                print(f"    - {warning}")
        print(f"  클래스 수: {result.get('class_count')}")
        print(f"  속성 수: {result.get('property_count')}")
    else:
        print(f"모든 모듈 검증: {args.modules_dir}")
        results = validator.validate_all_modules(args.modules_dir)
        
        summary = results.get('summary', {})
        print(f"\n검증 요약:")
        print(f"  총 모듈 수: {summary.get('total_modules')}")
        print(f"  총 오류: {summary.get('total_errors')}")
        print(f"  총 경고: {summary.get('total_warnings')}")
        print(f"  총 클래스 수: {summary.get('total_classes')}")
        print(f"  총 속성 수: {summary.get('total_properties')}")
        
        # 오류가 있는 모듈 표시
        error_modules = [
            name for name, result in results.get('modules', {}).items()
            if result.get('status') == 'invalid'
        ]
        if error_modules:
            print(f"\n오류가 있는 모듈:")
            for name in error_modules:
                print(f"  - {name}")


if __name__ == "__main__":
    main()

