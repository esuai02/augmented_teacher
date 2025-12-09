import sys
import os
import json
import requests
from datetime import datetime
import openai

# Whisper API 키 설정 - 환경변수에서 로드
try:
    openai.api_key = os.getenv("OPENAI_API_KEY")
    if not openai.api_key:
        raise ValueError("OPENAI_API_KEY 환경변수가 설정되지 않았습니다.")
except Exception as e:
    print(f"[Step 1 - API Key 설정] 오류 (voicetotext.py): {e}")
    exit(1)

# 명령줄 인수로 URL과 user_id 받기
try:
    audiofile_url = sys.argv[1]
    user_id = sys.argv[2]
except Exception as e:
    print(f"[Step 2 - 명령줄 인수 받기] 오류: {e}")
    exit(1)

# 로컬 저장 경로 및 타임스탬프 생성
try:
    now = datetime.now()
    timestamp = now.strftime("%Y%m%d%H%M%S")
    audio_file_path = f"cjnbessi7128_{user_id}_{timestamp}.wav"
except Exception as e:
    print(f"[Step 3 - 로컬 경로 및 타임스탬프 생성] 오류: {e}")
    exit(1)

# 오디오 파일 다운로드
try:
    response = requests.get(audiofile_url)
    if response.status_code == 200:
        with open(audio_file_path, "wb") as audio_file:
            audio_file.write(response.content)
    else:
        print(f"[Step 4 - 오디오 파일 다운로드] 실패: 상태 코드 {response.status_code}")
        exit(1)
except Exception as e:
    print(f"[Step 4 - 오디오 파일 다운로드] 오류: {e}")
    exit(1)

# Whisper API로 음성 -> 텍스트 변환
try:
    with open(audio_file_path, "rb") as audio_file:
        response = openai.Audio.transcribe(
            model="whisper-1",
            file=audio_file,
            response_format="json",
            language="ko"  # 한국어 설정
        )
except Exception as e:
    print(f"[Step 5 - Whisper API 변환] 오류: {e}")
    exit(1)

# 결과를 JSON으로 저장
try:
    transcription_results = response.get("segments", [])
    transcripts = [segment["text"] for segment in transcription_results]

    output_json_path = f"transcripts_{user_id}_{timestamp}.json"
    with open(output_json_path, "w", encoding="utf-8") as json_file:
        json.dump(transcription_results, json_file, ensure_ascii=False, indent=4)
except Exception as e:
    print(f"[Step 6 - 결과 저장] 오류: {e}")
    exit(1)

# 결과 출력
try:
    for transcript in transcripts:
        print(transcript)
except Exception as e:
    print(f"[Step 7 - 결과 출력] 오류: {e}")
    exit(1)

# 로컬에 저장한 파일 삭제
try:
    if os.path.exists(audio_file_path):
        os.remove(audio_file_path)
except Exception as e:
    print(f"[Step 8 - 로컬 파일 삭제] 오류: {e}")
    exit(1)
