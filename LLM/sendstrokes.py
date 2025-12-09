#!/usr/bin/env python
import requests
import json
import sys
 

# 전달된 이미지 URL을 받아서 사용
strokedata = sys.argv[1]
 
# put input strokes here
strokes_string = strokedata
strokes = json.loads(strokes_string)
r = requests.post("https://api.mathpix.com/v3/strokes",
    json={"strokes": strokes},
        headers={
            "app_id": "esuai02_gmail_com_f31c88_66d9d5",
            "app_key": "ebc00bbf555cb104dbc5b582ccf689e0495c8137859c9e4acc29716d07e9cdff",
            "Content-type": "application/json"
        })

result = json.dumps(r.json(), indent=4, sort_keys=True)
print(result)


 