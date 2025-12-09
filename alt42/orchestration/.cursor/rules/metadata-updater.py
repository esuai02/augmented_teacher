#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
에이전트별 metadata.md 파일 업데이트 스크립트
각 에이전트의 주 담당 데이터만 포함하도록 metadata.md를 재작성합니다.
"""

import os
import json
from pathlib import Path

PROJECT_ROOT = Path(__file__).parent.parent.parent
AGENTS_DIR = PROJECT_ROOT / "agents"
MAPPING_FILE = PROJECT_ROOT / ".cursor" / "rules" / "comprehensive_data_agent_mapping.md"

def read_file_content(file_path):
    """파일 내용을 읽어서 반환"""
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            return f.read()
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return ""

def parse_mapping_file():
    """매핑 파일에서 각 에이전트별 주 담당 데이터 추출"""
    mapping_content = read_file_content(MAPPING_FILE)
    
    # 매핑 파싱 (간단한 버전 - 실제로는 더 정교한 파싱 필요)
    agent_data = {}
    
    # agent01_onboarding의 주 담당 데이터 (매핑 파일 기반)
    agent_data['agent01_onboarding'] = [
        "학생 이름", "학교 이름", "학교 급 (초/중/고)", "학년", "생년월일", "성별",
        "보호자 이름", "보호자 관계", "학생 연락처", "보호자 연락처",
        "거주지 주소", "등하교 시간", "통학 거리", "학원/과외 거리", "개인 학습 공간 유무",
        "교과서 활용 여부", "경시/심화 경험 여부",
        "공부 장소 패턴",
        "학원/과외 경험", "경험 기간 (총 개월수)", "과거 교재 목록",
        "자가진단/레벨 테스트 이력", "과목별 튜터링 여부",
        "학습에 대한 관심도", "자주 확인하는 항목", "학습 계획 세워주는지 여부",
        "학습 내용 공유 빈도", "보호자 직업군 (교육 관련 여부)", "보호자의 수학 이해도",
        "가정 내 학습 분위기", "주말/방학 학습 지도 방식",
        "LMS(학습관리시스템) 연동 여부", "AI 콘텐츠 사용 이력",
        "진단 평가 API 연동", "학부모 앱 연동 여부"
    ]
    
    return agent_data

def get_base_metadata_structure():
    """기준 metadata.md의 구조 반환"""
    base_path = AGENTS_DIR / "agent01_onboarding" / "rules" / "metadata.md"
    return read_file_content(base_path)

def extract_data_by_category(base_content, data_names):
    """카테고리별로 데이터 추출"""
    categories = {
        "1. 기본 신상 정보": [],
        "2. 위치 및 환경 정보": [],
        "3. 수학 학습 진도 정보": [],
        "4. 학습 성향 및 습관": [],
        "5. 정서 및 동기 정보": [],
        "6. 학습 이력": [],
        "7. 목표 설정 정보": [],
        "8. 보호자 정보 및 참여": [],
        "9. 시스템 연계 정보": [],
        "10. AI 분석 및 추론용 메타 정보": []
    }
    
    # 각 카테고리별 데이터 매핑
    category_mapping = {
        "1. 기본 신상 정보": ["학생 이름", "학교 이름", "학교 급", "학년", "생년월일", "성별", "보호자 이름", "보호자 관계", "학생 연락처", "보호자 연락처"],
        "2. 위치 및 환경 정보": ["거주지 주소", "등하교 시간", "통학 거리", "학원/과외 거리", "개인 학습 공간 유무"],
        "3. 수학 학습 진도 정보": ["개념 진도", "심화 진도", "최근 학교 시험 범위", "학년 대비 선행 진도 정도", "단원별 진도표", "문제집 완료율", "교과서 활용 여부", "수학 내신 등급", "경시/심화 경험 여부", "개념별 취약 영역 기록"],
        "4. 학습 성향 및 습관": ["개념 중심 vs 문제 중심", "고난도 선호도", "반복 학습 선호도", "집중 시간 평균", "쉬는 시간 패턴", "포모도로 경험 유무", "학습 루틴 시간대", "공부 장소 패턴", "시험 공부 방식", "숙제 수행률", "문제 오답 정리 습관", "실수 vs 개념 미해결 비율", "필기 습관 유무", "정리 도구(노션/노트 등) 사용 여부", "학습 자료 스스로 선택 여부"],
        "5. 정서 및 동기 정보": ["수학에 대한 자신감", "수학 스트레스 정도", "실패 경험에 대한 반응", "성취 경험 빈도", "부모의 칭찬/비판 패턴", "학습 목표 스스로 설정하는지", "목표 도달 경험 유무", "수업 중 감정 상태 기록", "질문 요청 경향", "경쟁심 or 협동성"],
        "6. 학습 이력": ["학원/과외 경험", "경험 기간", "과거 교재 목록", "과거 시험 성적 히스토리", "최근 3회 시험 점수", "개념 완성 이력", "누적 오답노트 보유 여부", "자가진단/레벨 테스트 이력", "과목별 튜터링 여부", "학기별 학습 강도 변화"],
        "7. 목표 설정 정보": ["단기 목표", "중기 목표", "장기 목표", "목표 우선순위", "본인이 설정한 목표 vs 부모 설정", "목표에 대한 지속력", "목표 달성 후 보상 방식", "목표 리뷰 빈도", "목표 실패 원인 분석 능력", "목표 기반 루틴 이행률"],
        "8. 보호자 정보 및 참여": ["학습에 대한 관심도", "자주 확인하는 항목", "피드백 방식", "학습 계획 세워주는지 여부", "학습 내용 공유 빈도", "학습 스트레스 조율 방식", "보호자 직업군", "보호자의 수학 이해도", "가정 내 학습 분위기", "주말/방학 학습 지도 방식"],
        "9. 시스템 연계 정보": ["LMS(학습관리시스템) 연동 여부", "출결 체크 방식", "온라인 수업 수강 시간", "AI 콘텐츠 사용 이력", "진단 평가 API 연동", "문제풀이 로그 트래킹", "포모도로 타이머 데이터", "진도 자동 측정 도구 연계", "콘텐츠 추천 알고리즘 연동 여부", "학부모 앱 연동 여부"],
        "10. AI 분석 및 추론용 메타 정보": ["학습 몰입도 추정값", "학습 이탈 패턴 로그", "반복 실수 유형", "선행-복습 최적 타이밍 분석", "루틴 유지 성공률", "학습 난이도 반응 로그", "감정 변동 예측 로그", "질문 타이밍 패턴", "개입 전/후 효과 측정 데이터", "시그너처 루틴 매칭 결과"]
    }
    
    # 각 카테고리에서 해당 에이전트의 데이터만 추출
    for category, all_items in category_mapping.items():
        for item in all_items:
            # 부분 매칭 (예: "학생 이름"이 "1. 학생 이름"에 매칭되도록)
            for data_name in data_names:
                if data_name in item or item in data_name:
                    categories[category].append(item)
                    break
    
    return categories

def create_metadata_content(agent_name, data_names):
    """에이전트별 metadata.md 내용 생성"""
    base_content = get_base_metadata_structure()
    categories = extract_data_by_category(base_content, data_names)
    
    # 헤더 작성
    content = f"""온보딩 시스템이 현실 세계에서 완벽하게 작동하기 위해서는 **학생 개개인의 학습 상태, 성향, 맥락, 외부 환경, 기술 연계 정보 등**을 포함한 다양한 데이터가 필요합니다. 아래는 {agent_name} 에이전트와 직접적으로 관련된 데이터를 **카테고리별로 정리한 항목**입니다.

---

"""
    
    # 카테고리별 내용 작성
    category_titles = {
        "1. 기본 신상 정보": "🧍‍♂️",
        "2. 위치 및 환경 정보": "📍",
        "3. 수학 학습 진도 정보": "📚",
        "4. 학습 성향 및 습관": "🧠",
        "5. 정서 및 동기 정보": "❤️",
        "6. 학습 이력": "🧾",
        "7. 목표 설정 정보": "🎯",
        "8. 보호자 정보 및 참여": "👨‍👩‍👧",
        "9. 시스템 연계 정보": "🧩",
        "10. AI 분석 및 추론용 메타 정보": "🧪"
    }
    
    item_num = 1
    for category, items in categories.items():
        if not items:
            continue
        
        emoji = category_titles.get(category, "📋")
        category_name = category.split('. ', 1)[1] if '. ' in category else category
        
        content += f"## {emoji} {category_name} ({len(items)})\n\n"
        
        for item in items:
            content += f"{item_num}. {item}\n"
            item_num += 1
        
        content += "\n---\n\n"
    
    content += "\n"
    
    return content

def update_agent_metadata(agent_name, data_names):
    """에이전트의 metadata.md 파일 업데이트"""
    metadata_path = AGENTS_DIR / agent_name / "rules" / "metadata.md"
    
    if not metadata_path.exists():
        print(f"Warning: {metadata_path} does not exist")
        return False
    
    # 백업 생성
    backup_path = metadata_path.with_suffix('.md.backup')
    if not backup_path.exists():
        import shutil
        shutil.copy2(metadata_path, backup_path)
        print(f"Backup created: {backup_path}")
    
    # 새 내용 생성
    new_content = create_metadata_content(agent_name, data_names)
    
    # 파일 쓰기
    with open(metadata_path, 'w', encoding='utf-8') as f:
        f.write(new_content)
    
    print(f"Updated: {metadata_path}")
    return True

def main():
    print("=" * 60)
    print("에이전트별 metadata.md 파일 업데이트 시작")
    print("=" * 60)
    
    agent_data = parse_mapping_file()
    
    # agent01_onboarding부터 시작
    if 'agent01_onboarding' in agent_data:
        update_agent_metadata('agent01_onboarding', agent_data['agent01_onboarding'])
    
    print("\n업데이트 완료!")

if __name__ == "__main__":
    main()

