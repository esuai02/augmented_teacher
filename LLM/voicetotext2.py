

import os 

from google.cloud import storage
from google.cloud.speech_v2 import SpeechClient
from google.cloud.speech_v2.types import cloud_speech
from datetime import datetime
import json
import requests 
import sys
audiofile_url = sys.argv[1]
#sys.stdout.flush()
user_id = "ID"


#audiofile_url = "https://mathking.kr/moodle/local/augmented_teacher/bessiboard/recoder/cjnNotepageid1977jnrsorksqcrark/2023-08-03T23%3A31%3A49.091Z.wav"
print(audiofile_url)
os.environ["GOOGLE_APPLICATION_CREDENTIALS"] = "/home/moodle/composer/google_cloud_credential.json"
storage_client = storage.Client()
now = datetime.now()
timestamp = now.strftime("%Y%m%d%H%M%S")

bucket_name = "cjnground"
bucket = storage_client.bucket(bucket_name)
#blob_name = f"cjnbessi7128_{timestamp}.wav"
blob_name = f"cjnbessi7128_{user_id}_{timestamp}.wav"

blob = bucket.blob(blob_name)
response = requests.get(audiofile_url)
blob.upload_from_string(response.content)
gcs_uri = f"gs://{bucket_name}/{blob_name}"

client = SpeechClient()
name = "projects/eduground2023/locations/global/recognizers/_"

config = cloud_speech.RecognitionConfig(
    auto_decoding_config={},
    model="long",
    language_codes=["ko-KR"],
)

workspace = "gs://cjnground/transcripts"
output_config = cloud_speech.RecognitionOutputConfig(
    gcs_output_config=cloud_speech.GcsOutputConfig(uri=workspace),
)
files = [cloud_speech.BatchRecognizeFileMetadata(uri=gcs_uri)]
request = cloud_speech.BatchRecognizeRequest(
    recognizer=name, config=config, files=files, recognition_output_config=output_config
)

# future = client.batch_recognize(request=request)
# out3 = future.result()  # Store the result in the out3 variable
# print(out3)
future = client.batch_recognize(request=request)
future.result()

# Google Cloud Storage의 버킷에서 파일 리스트 가져오기
#blobs = storage_client.list_blobs(bucket_name, prefix="transcripts/cjnbessi7128")
blobs = storage_client.list_blobs(bucket_name, prefix=f"transcripts/cjnbessi7128_{user_id}")

# 최근에 생성된 transcript 파일 찾기
latest_file = None
latest_time = None
for blob in blobs:
    if "transcript_" in blob.name:
        if not latest_time or blob.time_created > latest_time:
            latest_time = blob.time_created
            latest_file = blob.name
'''
if not latest_file:
    print("Transcript file not found!")
    exit(1)
'''
json_file_path = f"gs://cjnground/{latest_file}"

# Download the JSON content from GCS
blob_to_read = storage_client.bucket(bucket_name).blob(json_file_path.replace(f"gs://{bucket_name}/", ""))
json_content = blob_to_read.download_as_text()
 
# Convert the JSON string to a Python dictionary
data = json.loads(json_content)

# Extract the 'transcript' values
transcripts = [result["alternatives"][0]["transcript"] for result in data["results"]]
print(transcripts)