import sys
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import re

def extract_math_and_text(question):
    # HTML 태그 제거
    pattern_html = re.compile(r'<[^>]*>')
    question = re.sub(pattern_html, ' ', question)

    # 이미지 경로 제거
    pattern_img = re.compile(r'!\[.*\]\((.*)\)')
    question = re.sub(pattern_img, ' ', question)

    # 수식 추출
    pattern_math = re.compile(r'(\$\$.*?\$\$|\$.*?\$)') # 수식 패턴 (LaTeX 표현식)
    math_expressions = re.findall(pattern_math, question)

    # 수식 제거한 텍스트 추출
    text = re.sub(pattern_math, ' ', question)
    
    return math_expressions, text

def calculate_similarity(question1, question2, threshold=0.8):
    math_expressions1, text1 = extract_math_and_text(question1)
    math_expressions2, text2 = extract_math_and_text(question2)
   
    # Initialize the vectorizer and transform the questions into vectors
    vectorizer = TfidfVectorizer()
    
    

    math_similarity = cosine_similarity(vectorizer.fit_transform([' '.join(math_expressions1), ' '.join(math_expressions2)]))[0][1]
    
    
    text_similarity = cosine_similarity(vectorizer.fit_transform([text1, text2]))[0][1]

    # 텍스트와 수식의 길이에 따른 가중치 계산
    total_length1 = len(question1)
    total_length2 = len(question2)
    math_weight = (len(' '.join(math_expressions1)) + len(' '.join(math_expressions2))) / (total_length1 + total_length2)
    text_weight = 1 - math_weight

    # 종합 유사도 계산
    total_similarity = math_weight * math_similarity + text_weight * text_similarity
 
    #total_similarity="3333" 
    #total_similarity=math_similarity
    return total_similarity

def main(question1, question2):
    print(calculate_similarity(question1, question2))

if __name__ == "__main__":
    question1 =  sys.argv[1]
    question2 = sys.argv[2]
    main(question1, question2)
