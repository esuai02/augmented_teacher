'''
import sys
import time
import requests
import json

# 전달된 이미지 URL을 받아서 사용
imageurl = sys.argv[1]

# 이미지가 존재하는지 확인
while True:
    response = requests.head(imageurl)
    if response.status_code == 200:
        # 이미지가 존재하면, 처리를 계속합니다.
        break
    else:
        # 이미지가 아직 준비되지 않았다면, 일정 시간 동안 대기하고 다시 확인합니다.
        print("Image not ready, waiting...")
        time.sleep(3)  # 10초 대기

r = requests.post("https://api.mathpix.com/v3/text",
    json={
        "src": imageurl,
        "math_inline_delimiters": ["$", "$"],
        "rm_spaces": True
    },
    headers={
        "app_id": "esuai02_gmail_com_f31c88_66d9d5",
        "app_key": "ebc00bbf555cb104dbc5b582ccf689e0495c8137859c9e4acc29716d07e9cdff",
        "Content-type": "application/json"
    }
)

result = json.dumps(r.json(), indent=4, sort_keys=True)
print(result)
'''
 
import sys

# 전달된 이미지 URL을 받아서 사용
imageurl = sys.argv[1]
 
#!/usr/bin/env python
import requests
import json


r = requests.post("https://api.mathpix.com/v3/text",
        json={
            "src": imageurl,
            "math_inline_delimiters": ["$", "$"],
            "rm_spaces": True
        },
        headers={
            "app_id": "esuai02_gmail_com_f31c88_f8665f",
            "app_key": "7a1d52bc124760b558c290448910903e453480e02a2d1eb5c0388c404c065f74",
            "Content-type": "application/json"
        }
    )
#print(json.dumps(r.json(), indent=4, sort_keys=True))

result = json.dumps(r.json(), indent=4, sort_keys=True)
print(result)

#"app_id": "esuai02_gmail_com_f31c88_f8665f",
#"app_key": "7a1d52bc124760b558c290448910903e453480e02a2d1eb5c0388c404c065f74",
 
#"app_id": "esuai02_gmail_com_f31c88_66d9d5",
#"app_key": "ebc00bbf555cb104dbc5b582ccf689e0495c8137859c9e4acc29716d07e9cdff",
   