# Teaching Support System Setup Guide

## OpenAI API 설정

1. **API 키 설정**
   - `config.php` 파일을 열어주세요
   - `YOUR_OPENAI_API_KEY`를 실제 OpenAI API 키로 교체하세요
   - OpenAI API 키는 https://platform.openai.com/api-keys 에서 생성할 수 있습니다

2. **모델 설정**
   - 현재는 `gpt-4o` 모델을 사용합니다
   - O3 모델이 출시되면 `config.php`의 `OPENAI_MODEL` 값을 `o3`로 변경하세요

## 데이터베이스 설정 (선택사항)

문제 해설 기록을 저장하려면 다음 테이블을 생성하세요:

```sql
CREATE TABLE teaching_solutions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    userid INT NOT NULL,
    studentid VARCHAR(255),
    problemtype VARCHAR(50),
    solution TEXT,
    timecreated INT,
    INDEX idx_userid (userid),
    INDEX idx_studentid (studentid)
);
```

## 사용 방법

1. 브라우저에서 `teachingagent.php?userid=[학생ID]` 접속
2. 문제 유형 선택 (내신 기출, 학교 프린트, MathKing 문제, 시중교재)
3. 문제 이미지 업로드 (드래그 앤 드롭 또는 클릭)
4. "정답 체크" 버튼 클릭
5. AI가 생성한 해설 확인
6. 필요시 음성 생성 및 학생에게 전송

## 보안 주의사항

- `config.php` 파일은 `.gitignore`에 추가하여 버전 관리에서 제외하세요
- 프로덕션 환경에서는 API 키를 환경변수로 관리하는 것을 권장합니다
- CORS 설정은 실제 도메인에 맞게 조정하세요

## 문제 해결

- **API 키 오류**: config.php의 API 키가 올바른지 확인
- **이미지 업로드 실패**: 파일 크기 제한 확인 (php.ini의 upload_max_filesize)
- **CORS 오류**: analyze_problem.php의 CORS 헤더 설정 확인