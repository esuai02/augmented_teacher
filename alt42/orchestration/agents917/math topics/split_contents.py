# -*- coding: utf-8 -*-
import codecs

# 파일 경로
input_file = r"c:\1 Project\augmented_teacher\alt42\orchestration\agents\math topics\contents_info.md"
output_dir = r"c:\1 Project\augmented_teacher\alt42\orchestration\agents\math topics"

# 영역별 라인 범위 (시작라인, 끝라인, 파일명)
areas = [
    (0, 1099, "수체계_영역.md"),
    (1099, 1245, "지수와_로그_영역.md"),
    (1245, 1420, "수열_영역.md"),
    (1420, 1705, "식의_계산_영역.md"),
    (1705, 1820, "집합과_명제_영역.md"),
    (1820, 2098, "방정식_영역.md"),
    (2098, 2214, "부등식_영역.md"),
    (2214, 2658, "함수_영역.md"),
    (2658, 3032, "미분_영역.md"),
    (3032, 3252, "적분_영역.md"),
    (3252, 3886, "평면도형_영역.md"),
    (3886, 4087, "평면좌표_영역.md"),
    (4087, 4256, "입체도형_영역.md"),
    (4256, 4405, "공간좌표_영역.md"),
    (4405, 4575, "벡터_영역.md"),
    (4575, 4807, "경우의_수와_확률_영역.md"),
    (4807, -1, "통계_영역.md")
]

# 파일 읽기
with codecs.open(input_file, 'r', encoding='utf-8') as f:
    lines = f.readlines()

# 각 영역별로 파일 생성
for start, end, filename in areas:
    if end == -1:
        content_lines = lines[start:]
    else:
        content_lines = lines[start:end]
    
    content = ''.join(content_lines)
    
    output_path = output_dir + "\\" + filename
    with codecs.open(output_path, 'w', encoding='utf-8') as f:
        f.write(content)
    
    print(f"Created: {filename} ({len(content_lines)} lines)")

print("\n모든 파일이 생성되었습니다!")

