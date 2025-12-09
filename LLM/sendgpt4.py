import openai
import requests
import json
import sys

prompt = sys.argv[1]

apikey = "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" 

def chatgpt(prompt):
    url = "https://api.openai.com/v1/chat/completions"
    headers = {
        "Content-Type": "application/json",
        "Authorization": f"Bearer {apikey}"
    }
    payload = {
        "messages": [{"role": "system", "content": prompt}],
        "model": "gpt-4o-mini",  
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



