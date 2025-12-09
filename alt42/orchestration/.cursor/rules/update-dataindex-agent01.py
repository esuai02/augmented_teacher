#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
dataindex.html을 metadata.md에 맞게 업데이트하는 스크립트
agent01_onboarding의 경우 25개 데이터만 표시하도록 수정
"""

import re
import os

# metadata.md에 있는 데이터 번호 목록
METADATA_ITEMS = {
    1: "학생 이름",
    2: "학교 이름",
    3: "학교 급 (초/중/고)",
    4: "학년",
    5: "생년월일",
    6: "성별",
    7: "보호자 이름",
    8: "보호자 관계",
    9: "학생 연락처",
    10: "보호자 연락처",
    11: "거주지 주소",
    12: "등하교 시간",
    13: "통학 거리",
    14: "학원/과외 거리",
    15: "개인 학습 공간 유무",
    22: "교과서 활용 여부",
    24: "경시/심화 경험 여부",
    33: "공부 장소 패턴",
    51: "학원/과외 경험",
    52: "경험 기간 (총 개월수)",
    53: "과거 교재 목록",
    58: "자가진단/레벨 테스트 이력",
    59: "과목별 튜터링 여부",
    71: "학습에 대한 관심도",
    72: "자주 확인하는 항목",
    74: "학습 계획 세워주는지 여부",
    75: "학습 내용 공유 빈도",
    77: "보호자 직업군 (교육 관련 여부)",
    78: "보호자의 수학 이해도",
    79: "가정 내 학습 분위기",
    80: "주말/방학 학습 지도 방식",
    81: "LMS(학습관리시스템) 연동 여부",
    84: "AI 콘텐츠 사용 이력",
    85: "진단 평가 API 연동",
    90: "학부모 앱 연동 여부"
}

# 카테고리별 데이터 번호
CATEGORY_MAPPING = {
    1: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
    2: [11, 12, 13, 14, 15],
    3: [22, 24],
    4: [33],
    6: [51, 52, 53, 58, 59],
    8: [71, 72, 74, 75, 77, 78, 79, 80],
    9: [81, 84, 85, 90]
}

def update_dataindex():
    html_path = "alt42/orchestration/agents/agent01_onboarding/rules/dataindex.html"
    
    with open(html_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 통계 업데이트 (25개로 변경)
    content = re.sub(
        r'<div class="number">100</div>\s*<div class="label">총 데이터 항목</div>',
        '<div class="number">25</div>\n                <div class="label">총 데이터 항목</div>',
        content
    )
    
    # surv 항목 수 계산 (모든 항목이 surv를 가짐)
    surv_count = len(METADATA_ITEMS)
    content = re.sub(
        r'<div class="number">100</div>\s*<div class="label">surv 항목</div>',
        f'<div class="number">{surv_count}</div>\n                <div class="label">surv 항목</div>',
        content
    )
    
    # gen 항목 수 계산 (대부분이 gen을 가짐, 추정치)
    gen_count = len([k for k in METADATA_ITEMS.keys() if k not in [4, 81, 84, 85, 90]])  # sys만 있는 항목 제외
    content = re.sub(
        r'<div class="number">100</div>\s*<div class="label">gen 항목</div>',
        f'<div class="number">{gen_count}</div>\n                <div class="label">gen 항목</div>',
        content
    )
    
    # 카테고리 3: 22, 24만 남기기
    # 카테고리 3 섹션 찾기
    cat3_pattern = r'(<div class="category-section" data-category="3">.*?<tbody>)(.*?)(</tbody>.*?</div>\s*</div>)'
    cat3_match = re.search(cat3_pattern, content, re.DOTALL)
    
    if cat3_match:
        # 22번과 24번 행만 추출
        rows = re.findall(r'<tr[^>]*>.*?</tr>', cat3_match.group(2), re.DOTALL)
        valid_rows = []
        for row in rows:
            # 번호 추출
            num_match = re.search(r'<td>(\d+)</td>', row)
            if num_match:
                num = int(num_match.group(1))
                if num in [22, 24]:
                    valid_rows.append(row)
        
        if valid_rows:
            new_tbody = '\n                        '.join(valid_rows)
            new_cat3 = cat3_match.group(1) + '\n                        ' + new_tbody + '\n                    ' + cat3_match.group(3)
            content = content[:cat3_match.start()] + new_cat3 + content[cat3_match.end():]
    
    # 카테고리 4: 33만 남기기
    cat4_pattern = r'(<div class="category-section" data-category="4">.*?<tbody>)(.*?)(</tbody>.*?</div>\s*</div>)'
    cat4_match = re.search(cat4_pattern, content, re.DOTALL)
    
    if cat4_match:
        rows = re.findall(r'<tr[^>]*>.*?</tr>', cat4_match.group(2), re.DOTALL)
        valid_rows = []
        for row in rows:
            num_match = re.search(r'<td>(\d+)</td>', row)
            if num_match:
                num = int(num_match.group(1))
                if num == 33:
                    valid_rows.append(row)
        
        if valid_rows:
            new_tbody = '\n                        '.join(valid_rows)
            new_cat4 = cat4_match.group(1) + '\n                        ' + new_tbody + '\n                    ' + cat4_match.group(3)
            content = content[:cat4_match.start()] + new_cat4 + content[cat4_match.end():]
    
    # 카테고리 5: 전체 섹션 제거
    cat5_pattern = r'<div class="category-divider">.*?<div class="category-divider-title">.*?5\. 정서 및 동기.*?</div>.*?</div>.*?<div class="category-section" data-category="5">.*?</div>\s*</div>'
    content = re.sub(cat5_pattern, '', content, flags=re.DOTALL)
    
    # 카테고리 6: 51, 52, 53, 58, 59만 남기기
    cat6_pattern = r'(<div class="category-section" data-category="6">.*?<tbody>)(.*?)(</tbody>.*?</div>\s*</div>)'
    cat6_match = re.search(cat6_pattern, content, re.DOTALL)
    
    if cat6_match:
        rows = re.findall(r'<tr[^>]*>.*?</tr>', cat6_match.group(2), re.DOTALL)
        valid_rows = []
        for row in rows:
            num_match = re.search(r'<td>(\d+)</td>', row)
            if num_match:
                num = int(num_match.group(1))
                if num in [51, 52, 53, 58, 59]:
                    valid_rows.append(row)
        
        if valid_rows:
            new_tbody = '\n                        '.join(valid_rows)
            new_cat6 = cat6_match.group(1) + '\n                        ' + new_tbody + '\n                    ' + cat6_match.group(3)
            content = content[:cat6_match.start()] + new_cat6 + content[cat6_match.end():]
    
    # 카테고리 7: 전체 섹션 제거
    cat7_pattern = r'<div class="category-divider">.*?<div class="category-divider-title">.*?7\. 목표 설정.*?</div>.*?</div>.*?<div class="category-section" data-category="7">.*?</div>\s*</div>'
    content = re.sub(cat7_pattern, '', content, flags=re.DOTALL)
    
    # 카테고리 8: 71, 72, 74, 75, 77, 78, 79, 80만 남기기 (73, 76 제거)
    cat8_pattern = r'(<div class="category-section" data-category="8">.*?<tbody>)(.*?)(</tbody>.*?</div>\s*</div>)'
    cat8_match = re.search(cat8_pattern, content, re.DOTALL)
    
    if cat8_match:
        rows = re.findall(r'<tr[^>]*>.*?</tr>', cat8_match.group(2), re.DOTALL)
        valid_rows = []
        for row in rows:
            num_match = re.search(r'<td>(\d+)</td>', row)
            if num_match:
                num = int(num_match.group(1))
                if num in [71, 72, 74, 75, 77, 78, 79, 80]:
                    valid_rows.append(row)
        
        if valid_rows:
            new_tbody = '\n                        '.join(valid_rows)
            new_cat8 = cat8_match.group(1) + '\n                        ' + new_tbody + '\n                    ' + cat8_match.group(3)
            content = content[:cat8_match.start()] + new_cat8 + content[cat8_match.end():]
    
    # 카테고리 9: 81, 84, 85, 90만 남기기
    cat9_pattern = r'(<div class="category-section" data-category="9">.*?<tbody>)(.*?)(</tbody>.*?</div>\s*</div>)'
    cat9_match = re.search(cat9_pattern, content, re.DOTALL)
    
    if cat9_match:
        rows = re.findall(r'<tr[^>]*>.*?</tr>', cat9_match.group(2), re.DOTALL)
        valid_rows = []
        for row in rows:
            num_match = re.search(r'<td>(\d+)</td>', row)
            if num_match:
                num = int(num_match.group(1))
                if num in [81, 84, 85, 90]:
                    valid_rows.append(row)
        
        if valid_rows:
            new_tbody = '\n                        '.join(valid_rows)
            new_cat9 = cat9_match.group(1) + '\n                        ' + new_tbody + '\n                    ' + cat9_match.group(3)
            content = content[:cat9_match.start()] + new_cat9 + content[cat9_match.end():]
    
    # 카테고리 10: 전체 섹션 제거
    cat10_pattern = r'<div class="category-divider">.*?<div class="category-divider-title">.*?10\. AI 분석.*?</div>.*?</div>.*?<div class="category-section" data-category="10">.*?</div>\s*</div>'
    content = re.sub(cat10_pattern, '', content, flags=re.DOTALL)
    
    # 탭에서 카테고리 5, 7, 10 제거
    content = re.sub(
        r'<button class="tab-btn"[^>]*data-tab="category5">5\. 정서 및 동기</button>\s*',
        '',
        content
    )
    content = re.sub(
        r'<button class="tab-btn"[^>]*data-tab="category7">7\. 목표 설정</button>\s*',
        '',
        content
    )
    content = re.sub(
        r'<button class="tab-btn"[^>]*data-tab="category10">10\. AI 분석 및 추론</button>\s*',
        '',
        content
    )
    
    # 탭 콘텐츠에서도 제거
    content = re.sub(
        r'<div class="tab-content"[^>]*id="tab-category5">.*?</div>\s*</div>\s*',
        '',
        content,
        flags=re.DOTALL
    )
    content = re.sub(
        r'<div class="tab-content"[^>]*id="tab-category7">.*?</div>\s*</div>\s*',
        '',
        content,
        flags=re.DOTALL
    )
    content = re.sub(
        r'<div class="tab-content"[^>]*id="tab-category10">.*?</div>\s*</div>\s*',
        '',
        content,
        flags=re.DOTALL
    )
    
    with open(html_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"✅ {html_path} 업데이트 완료")
    print(f"   총 {len(METADATA_ITEMS)}개 데이터 항목으로 업데이트됨")

if __name__ == "__main__":
    update_dataindex()

