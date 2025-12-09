# TTS 자동 업로드 및 재생 가이드

## 📋 개요

OpenAI TTS로 생성된 오디오를 자동으로 서버에 업로드하고 DB를 업데이트하여 언제든지 재생 가능하도록 구현한 시스템입니다.

## 🗂️ 수정된 파일

### 1. **save_tts_audio.php** (신규 생성)
- **위치**: `/books/save_tts_audio.php`
- **기능**: TTS 오디오 파일을 서버에 저장하고 DB 업데이트
- **처리 흐름**:
  1. Base64 인코딩된 오디오 데이터 수신
  2. 디코딩 후 WAV 파일로 저장 (`/home/moodle/public_html/audiofiles/`)
  3. contentstype에 따라 적절한 테이블 업데이트
     - `contentstype = 2` → `mdl_question` 테이블
     - `contentstype ≠ 2` → `mdl_icontent_pages` 테이블

### 2. **openai_tts.php** (수정됨)
- **위치**: `/books/openai_tts.php`
- **추가 기능**:
  1. `uploadAudioToServer()` 함수 - 오디오 자동 업로드
  2. `arrayBufferToBase64()` 함수 - ArrayBuffer를 Base64로 변환
  3. 페이지 로드 시 기존 오디오 자동 표시
  4. 업로드 성공 시 실시간 알림

### 3. **verify_audiourl_column.php** (수정됨)
- **위치**: `/books/verify_audiourl_column.php`
- **기능**:
  - `mdl_icontent_pages` 테이블의 audiourl 컬럼 확인
  - `mdl_question` 테이블의 audiourl 컬럼 확인
  - 샘플 데이터 표시

## 🔍 DB 스키마 정보

### audiourl 필드 위치
```
mdl_icontent_pages (contentstype ≠ 2)
├── id (PK)
├── title
├── audiourl ← TTS 오디오 URL 저장
└── ...

mdl_question (contentstype = 2)
├── id (PK)
├── name
├── audiourl ← TTS 오디오 URL 저장
└── ...

mdl_abrainalignment_gptresults (텍스트만 저장)
├── id (PK)
├── type (conversation, pmemory 등)
├── outputtext ← GPT 생성 텍스트
├── contentsid
├── contentstype
└── ❌ audiourl 없음!
```

## 🚀 사용 방법

### 1단계: DB 확인
```
https://mathking.kr/moodle/local/augmented_teacher/books/verify_audiourl_column.php
```
- `icontent_pages`와 `question` 테이블의 audiourl 컬럼 존재 여부 확인
- 기존 오디오 레코드 확인

### 2단계: TTS 생성 및 자동 업로드
```
https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts.php?cid=123&ctype=1&type=conversation
```

**파라미터 설명:**
- `cid` (contentsid): 콘텐츠 ID
- `ctype` (contentstype): 콘텐츠 타입 (1=icontent_pages, 2=question)
- `type`: TTS 타입 (conversation, pmemory 등)

**자동 처리 흐름:**
1. 텍스트 입력 후 "음성 생성" 버튼 클릭
2. OpenAI TTS API로 음성 생성
3. ✅ 브라우저에서 즉시 재생
4. ✅ 서버에 자동 업로드
5. ✅ DB 자동 업데이트
6. ✅ 영구 URL로 플레이어 소스 교체
7. 알림: "오디오가 성공적으로 저장되고 재생 가능합니다!"

### 3단계: 재방문 시 자동 재생
- 같은 URL로 다시 접속하면 기존 오디오 자동 로드
- 플레이어에서 바로 재생 가능

## 📁 파일 저장 규칙

### 파일명 형식
```
cid{contentsid}ct{contentstype}_{type}_tts.wav
```

### 예시
```
cid123ct1_conversation_tts.wav
cid456ct2_pmemory_tts.wav
```

### 저장 경로
```
/home/moodle/public_html/audiofiles/
```

### 접근 URL
```
https://mathking.kr/audiofiles/{파일명}
```

## 🔧 기술 세부사항

### 클라이언트 측 (JavaScript)
```javascript
// 1. TTS 생성 완료 후 playAudio() 호출
const playAudio = (audioBuffer) => {
    // 오디오 재생
    // ...

    // 자동 업로드
    uploadAudioToServer(audioData);
};

// 2. ArrayBuffer를 Base64로 변환
function arrayBufferToBase64(buffer) {
    let binary = '';
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return 'data:audio/wav;base64,' + window.btoa(binary);
}

// 3. AJAX로 서버 전송
$.ajax({
    url: 'save_tts_audio.php',
    type: 'POST',
    data: {
        audioData: base64Audio,
        contentsid: contentsid,
        contentstype: contentstype,
        type: type
    }
});
```

### 서버 측 (PHP)
```php
// 1. Base64 디코딩
$audioData = $_POST['audioData'];
$audioData = substr($audioData, strlen('data:audio/wav;base64,'));
$decodedAudio = base64_decode($audioData);

// 2. 파일 저장
$fileName = 'cid' . $contentsid . 'ct' . $contentstype . '_' . $type . '_tts.wav';
file_put_contents($uploadDir . $fileName, $decodedAudio);

// 3. DB 업데이트
if ($contentstype == 2) {
    $DB->execute("UPDATE {question} SET audiourl = ? WHERE id = ?",
                 array($audioUrl, $contentsid));
} else {
    $DB->execute("UPDATE {icontent_pages} SET audiourl = ? WHERE id = ?",
                 array($audioUrl, $contentsid));
}
```

## 🐛 에러 처리

### 모든 에러 메시지 형식
```
에러 설명 [파일: 파일명.php, 위치: 함수/위치]
```

### 주요 에러 케이스
1. **필수 파라미터 누락**
   ```
   필수 파라미터가 누락되었습니다. [파일: save_tts_audio.php, 위치: 입력 검증]
   ```

2. **Base64 디코딩 실패**
   ```
   오디오 데이터 디코딩 실패 [파일: save_tts_audio.php, 위치: Base64 디코딩]
   ```

3. **파일 저장 실패**
   ```
   파일 저장 실패 [파일: save_tts_audio.php, 위치: 파일 쓰기]
   ```

4. **DB 업데이트 실패**
   ```
   icontent_pages 테이블 업데이트 실패 [파일: save_tts_audio.php, 위치: icontent_pages 업데이트]
   ```

## ✅ 테스트 체크리스트

- [ ] verify_audiourl_column.php 실행하여 DB 테이블 확인
- [ ] 새 TTS 생성 시 자동 업로드 동작 확인
- [ ] 브라우저 Console에서 업로드 성공 로그 확인
- [ ] 페이지 새로고침 후 기존 오디오 자동 로드 확인
- [ ] /audiofiles/ 폴더에 파일 생성 확인
- [ ] DB에 audiourl 값 저장 확인
- [ ] 오디오 플레이어에서 재생 가능 확인

## 📊 성공 응답 예시

```json
{
    "success": true,
    "message": "icontent_pages 테이블의 audiourl 필드가 업데이트되었습니다.",
    "audioUrl": "https://mathking.kr/audiofiles/cid123ct1_conversation_tts.wav",
    "fileName": "cid123ct1_conversation_tts.wav"
}
```

## 🔒 보안 고려사항

1. **파일 저장 경로**: 절대 경로 사용으로 경로 탐색 공격 방지
2. **파일 덮어쓰기**: 동일 contentsid+contentstype+type은 기존 파일 덮어쓰기
3. **권한 확인**: `require_login()` 으로 인증된 사용자만 접근
4. **에러 정보**: 프로덕션에서는 상세 에러 메시지 숨김 권장

## 📌 참고사항

- 파일 크기 제한 없음 (필요시 save_tts_audio.php에서 추가)
- WAV 형식만 지원 (OpenAI TTS 기본 출력)
- 오디오는 영구 저장되며 수동 삭제 필요
- 동일 조건의 TTS 재생성 시 기존 파일 덮어쓰기

---

**마지막 업데이트**: 2025-10-25
**작성자**: Claude Code
**버전**: 1.0
