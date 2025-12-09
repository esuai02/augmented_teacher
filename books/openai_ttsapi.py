
  
import openai
import os
import sys
from pydub import AudioSegment
from flask import Flask, send_file
 
# OpenAI API 키 설정 - 환경변수에서 로드
openai.api_key = os.getenv("OPENAI_API_KEY")
if not openai.api_key:
    raise ValueError("[openai_ttsapi.py] OPENAI_API_KEY 환경변수가 설정되지 않았습니다.")
 
  

# 입력된 텍스트 가져오기
text = sys.argv[1]
   
print(text)

# 대화를 분리하는 함수
def split_dialogue(text):
    lines = text.split('\n')
    teacher_lines = [line[4:] for line in lines if line.startswith('선생님:')]
    student_lines = [line[3:] for line in lines if line.startswith('학생:')]
    return teacher_lines, student_lines

# TTS 함수
def text_to_speech(text, voice, filename):
    response = openai.audio.speech.create(
        model="tts-1",
        voice=voice,
        input=text
    )
    response.stream_to_file(filename)

# 음성 파일 생성 함수
def generate_audio():
    # 대화 분리
    teacher_lines, student_lines = split_dialogue(text)

    # 음성 생성
    for i, line in enumerate(teacher_lines):
        text_to_speech(line, "onyx", f"teacher_{i}.mp3")

    for i, line in enumerate(student_lines):
        text_to_speech(line, "nova", f"student_{i}.mp3")

    # 음성 파일 결합
    combined = AudioSegment.empty()
    for i in range(max(len(teacher_lines), len(student_lines))):
        if i < len(teacher_lines):
            combined += AudioSegment.from_mp3(f"teacher_{i}.mp3")
        if i < len(student_lines):
            combined += AudioSegment.from_mp3(f"student_{i}.mp3")

    # 최종 파일 저장
    combined.export("final_dialogue.mp3", format="mp3")

    # 임시 파일 삭제
    for i in range(len(teacher_lines)):
        os.remove(f"teacher_{i}.mp3")
    for i in range(len(student_lines)):
        os.remove(f"student_{i}.mp3")

    print("음성 파일이 생성되었습니다: final_dialogue.mp3")
 