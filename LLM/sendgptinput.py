#import openai
import requests
import json
import sys
import os

prompt = sys.argv[1]

# API 키를 환경변수에서 로드
apikey = os.getenv("OPENAI_API_KEY")  # API-KEY from env
if not apikey:
    print(json.dumps({"result": "Error: OPENAI_API_KEY 환경변수가 설정되지 않았습니다."}))
    sys.exit(1)

def chatgpt(prompt):
    url = "https://api.openai.com/v1/chat/completions"
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {apikey}"
    }
    payload = {
        "messages": [{"role": "system", "content": prompt}],
        "model": "gpt-4-1106-preview", #"gpt-4-1106-preview",  # "model": "gpt-3.5-turbo",
        "temperature": 0,
        "top_p": 0,
        "max_tokens": 1000
    }
    response = requests.post(url, headers=headers, json=payload)
    
    if response.status_code == 200:
        data = response.json()
        choices = data["choices"]
        if choices:
            generated_text = choices[0]["message"]["content"]
            result = {"result": generated_text}
            output_json = json.dumps(result)
            print(output_json)  # JSON 응답 출력
        else:
            result = {"result": "No response from the model."}
            output_json = json.dumps(result)
            print(output_json)  # JSON 응답 출력
    else:
        result = {"result": "Error: " + str(response.status_code)}
        output_json = json.dumps(result)
        print(output_json)  # JSON 응답 출력


# Read prompt from command line argument
chatgpt(prompt)



