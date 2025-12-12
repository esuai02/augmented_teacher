import re

# 파일 읽기
with open('rules.yaml', 'r', encoding='utf-8') as f:
    content = f.read()

# 패턴: "provide_feedback: '"텍스트..." -> "provide_feedback: '텍스트..."
# 큰따옴표 안에 있는 작은따옴표 다음의 큰따옴표를 제거
content = re.sub(r'("provide_feedback: \'")([^\'"]+)(\?\'")', r'"provide_feedback: \'\2\3', content)

# 파일 쓰기
with open('rules.yaml', 'w', encoding='utf-8') as f:
    f.write(content)

print("수정 완료!")



