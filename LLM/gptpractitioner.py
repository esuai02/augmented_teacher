import openai
import os

# API 키를 환경변수에서 로드 - 서버 설정에서 OPENAI_API_KEY 환경변수 필요
openai.api_key = os.getenv("OPENAI_API_KEY")
if not openai.api_key:
    raise ValueError("[gptpractitioner.py] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.")  

response = openai.Completion.create(
  engine="text-davinci-002",
  prompt="hello world",
  max_tokens=60
)
 
result =response.choices[0].text.strip()
print(result) 

 