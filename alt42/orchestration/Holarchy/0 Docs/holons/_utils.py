#!/usr/bin/env python3
"""
_utils.py - Holarchy 공통 유틸리티 함수

에러 핸들링을 개선하기 위한 안전한 파싱/로드 함수들을 제공합니다.
Self-Healing 모드: 에러 발생 시 로깅 후 기본값 반환 (시스템 중단 없음)

사용법:
    from _utils import safe_json_load, safe_parse_date, safe_read_file
"""

import json
import logging
from datetime import datetime
from pathlib import Path
from typing import Any, Optional, Dict, List

# 로깅 설정
logging.basicConfig(
    level=logging.DEBUG,
    format='%(asctime)s [%(levelname)s] %(filename)s:%(lineno)d - %(message)s'
)
logger = logging.getLogger("holarchy")


def safe_json_load(json_str: str, context: str = "", default: Any = None) -> Any:
    """
    JSON 문자열을 안전하게 파싱합니다.

    Args:
        json_str: 파싱할 JSON 문자열
        context: 디버깅을 위한 컨텍스트 정보 (예: 파일명, 함수명)
        default: 파싱 실패 시 반환할 기본값

    Returns:
        파싱된 객체 또는 기본값
    """
    try:
        return json.loads(json_str)
    except json.JSONDecodeError as e:
        logger.warning(f"JSON 파싱 실패 [{context}]: {e}")
        return default
    except TypeError as e:
        logger.warning(f"JSON 입력 타입 오류 [{context}]: {e}")
        return default


def safe_parse_date(date_str: str, context: str = "",
                    fmt: str = "%Y-%m-%d", default: Optional[datetime] = None) -> Optional[datetime]:
    """
    날짜 문자열을 안전하게 파싱합니다.

    Args:
        date_str: 파싱할 날짜 문자열
        context: 디버깅을 위한 컨텍스트 정보
        fmt: 날짜 형식 (기본: %Y-%m-%d)
        default: 파싱 실패 시 반환할 기본값

    Returns:
        datetime 객체 또는 기본값
    """
    try:
        # ISO format 시도
        return datetime.fromisoformat(date_str.replace("Z", "+00:00"))
    except (ValueError, AttributeError):
        pass

    try:
        # 지정된 형식으로 시도
        return datetime.strptime(date_str[:len(fmt.replace("%", ""))], fmt)
    except (ValueError, AttributeError, TypeError) as e:
        logger.debug(f"날짜 파싱 실패 [{context}]: '{date_str}' - {e}")
        return default


def safe_parse_datetime(datetime_str: str, context: str = "",
                        default: Optional[datetime] = None) -> Optional[datetime]:
    """
    다양한 형식의 datetime 문자열을 안전하게 파싱합니다.

    Args:
        datetime_str: 파싱할 datetime 문자열
        context: 디버깅을 위한 컨텍스트 정보
        default: 파싱 실패 시 반환할 기본값

    Returns:
        datetime 객체 또는 기본값
    """
    formats = [
        "%Y-%m-%dT%H:%M:%S",
        "%Y-%m-%dT%H:%M:%S.%f",
        "%Y-%m-%d %H:%M:%S",
        "%Y-%m-%d",
    ]

    for fmt in formats:
        try:
            return datetime.strptime(datetime_str[:len(fmt.replace("%", ""))], fmt)
        except (ValueError, AttributeError, TypeError):
            continue

    # ISO format with timezone
    try:
        return datetime.fromisoformat(datetime_str.replace("Z", "+00:00"))
    except (ValueError, AttributeError, TypeError) as e:
        logger.debug(f"datetime 파싱 실패 [{context}]: '{datetime_str}' - {e}")
        return default


def safe_read_file(filepath: Path, context: str = "",
                   encoding: str = "utf-8", default: str = "") -> str:
    """
    파일을 안전하게 읽습니다.

    Args:
        filepath: 읽을 파일 경로
        context: 디버깅을 위한 컨텍스트 정보
        encoding: 파일 인코딩
        default: 읽기 실패 시 반환할 기본값

    Returns:
        파일 내용 또는 기본값
    """
    try:
        return filepath.read_text(encoding=encoding)
    except FileNotFoundError:
        logger.debug(f"파일 없음 [{context}]: {filepath}")
        return default
    except PermissionError:
        logger.warning(f"파일 접근 권한 없음 [{context}]: {filepath}")
        return default
    except UnicodeDecodeError as e:
        logger.warning(f"파일 인코딩 오류 [{context}]: {filepath} - {e}")
        return default
    except Exception as e:
        logger.warning(f"파일 읽기 실패 [{context}]: {filepath} - {e}")
        return default


def safe_json_file_load(filepath: Path, context: str = "",
                        default: Any = None) -> Any:
    """
    JSON 파일을 안전하게 로드합니다.

    Args:
        filepath: JSON 파일 경로
        context: 디버깅을 위한 컨텍스트 정보
        default: 로드 실패 시 반환할 기본값

    Returns:
        파싱된 JSON 객체 또는 기본값
    """
    content = safe_read_file(filepath, context)
    if not content:
        return default
    return safe_json_load(content, context, default)


def safe_extract_holon_json(content: str, context: str = "") -> Optional[Dict]:
    """
    마크다운 파일에서 ```holon-data JSON 블록을 안전하게 추출합니다.

    Args:
        content: 마크다운 파일 내용
        context: 디버깅을 위한 컨텍스트 정보

    Returns:
        추출된 holon 딕셔너리 또는 None
    """
    import re

    try:
        json_match = re.search(r'```holon-data\s*(.*?)\s*```', content, re.DOTALL)
        if json_match:
            return safe_json_load(json_match.group(1), context)
    except Exception as e:
        logger.debug(f"holon-data 추출 실패 [{context}]: {e}")

    return None


def safe_enum_parse(enum_class, value: str, context: str = "", default=None):
    """
    Enum 값을 안전하게 파싱합니다.

    Args:
        enum_class: Enum 클래스
        value: 파싱할 값
        context: 디버깅을 위한 컨텍스트 정보
        default: 파싱 실패 시 반환할 기본값

    Returns:
        Enum 값 또는 기본값
    """
    try:
        return enum_class(value)
    except (ValueError, KeyError) as e:
        logger.debug(f"Enum 파싱 실패 [{context}]: {value} - {e}")
        return default if default is not None else list(enum_class)[0]


def log_and_return(value: Any, message: str, level: str = "debug") -> Any:
    """
    값을 로깅하고 반환합니다.

    Args:
        value: 반환할 값
        message: 로그 메시지
        level: 로그 레벨 (debug, info, warning, error)

    Returns:
        전달받은 값을 그대로 반환
    """
    log_func = getattr(logger, level, logger.debug)
    log_func(message)
    return value
