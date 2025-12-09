# 절차기억 나레이션 아이콘 표시 워크플로우 검증 가이드

## 수정 완료 사항

### 1. openai_tts_pmemory.php 수정
**파일 위치:** `/mnt/c/1 Project/augmented_teacher/books/openai_tts_pmemory.php`
**수정 라인:** 576-615

**변경 내용:**
- `uploadCombinedAudio()` 함수의 AJAX 성공 콜백에 사용자 안내 메시지 추가
- DB 업데이트 완료 후 mynote.php에서 아이콘이 표시될 것임을 알림
- 부모 창 새로고침 기능 추가 (팝업으로 열린 경우)

**주요 로직:**
```javascript
// audiourl2 필드 업데이트 확인 및 사용자 안내
if(data.audiourl) {
    outputText.innerHTML += '<p style="color:blue;">✅ 절차기억 나레이션 아이콘이 mynote.php에 표시됩니다!</p>';
    outputText.innerHTML += '<p style="color:green;">🟢 mynote.php 페이지에서 녹색 깃발 아이콘을 확인하세요.</p>';

    setTimeout(function() {
        alert('✅ 절차기억 나레이션 음성이 생성되었습니다!\\n\\nmynote.php 페이지에서 🟢 녹색 아이콘을 확인하실 수 있습니다.');

        if(window.opener) {
            window.opener.location.reload();
        }
    }, 2000);
}
```

### 2. file_pmemory.php 검증
**파일 위치:** `/mnt/c/1 Project/augmented_teacher/LLM/file_pmemory.php`
**검증 라인:** 94-102

**확인 결과:**
- ✅ 정상 동작: `section` 파라미터가 없을 때만 DB 업데이트
- ✅ 전체 병합 파일 업로드 시 `audiourl2` 필드 자동 업데이트
- ✅ 로그 기록 정상: `/home/moodle/logs/pmemory_upload.log`

## 테스트 시나리오

### Step 1: 절차기억 나레이션 텍스트 생성
**URL:** `https://mathking.kr/moodle/local/augmented_teacher/books/openai_tts_pmemory.php?cid=31906&ctype=1`

**절차:**
1. 페이지 접속
2. 대본 입력란에 수학 문제와 풀이 입력
3. "🎓 절차기억 나레이션 생성" 버튼 클릭
4. GPT가 @ 기호로 구분된 나레이션 생성 확인
5. "✅ 절차기억 나레이션이 생성되었습니다!" 메시지 확인

**예상 결과:**
- @ 기호로 구간이 구분된 나레이션 텍스트 생성
- `mdl_abrainalignment_gptresults` 테이블에 저장됨

### Step 2: 음성 파일 생성 및 업로드
**동일 페이지에서 계속:**

**절차:**
1. "🎵 음성 생성 (듣기평가 모드)" 버튼 클릭
2. 각 구간별 TTS 생성 진행 상황 확인:
   - `[구간 1/N] 음성 생성 중...`
   - `✅ 구간 1 음성 생성 완료`
   - `✅ 구간 1 업로드 완료!`
3. 모든 구간 완료 후 전체 병합 파일 업로드 메시지 확인:
   - `🔄 전체 병합 파일을 서버에 업로드 중...`
   - `✅ 전체 병합 파일 업로드 완료!`
   - `DB 업데이트됨: https://mathking.kr/Contents/audiofiles/pmemory/cid31906ct1_combined.wav`
4. **[NEW]** 추가된 안내 메시지 확인:
   - `✅ 절차기억 나레이션 아이콘이 mynote.php에 표시됩니다!`
   - `🟢 mynote.php 페이지에서 녹색 깃발 아이콘을 확인하세요.`
5. 2초 후 alert 팝업 표시:
   - "✅ 절차기억 나레이션 음성이 생성되었습니다!"
   - "mynote.php 페이지에서 🟢 녹색 아이콘을 확인하실 수 있습니다."

**예상 결과:**
- 구간별 음성 파일: `cid31906ct1_section1.wav`, `cid31906ct1_section2.wav`, ...
- 전체 병합 파일: `cid31906ct1_pmemory.wav`
- DB 업데이트: `mdl_icontent_pages.audiourl2 = 'https://mathking.kr/Contents/audiofiles/pmemory/cid31906ct1_pmemory.wav'`

### Step 3: mynote.php에서 아이콘 확인
**URL:** `https://mathking.kr/moodle/local/augmented_teacher/books/mynote.php?dmn=128&cid=62&nch=1&cmid=87789&page=1&studentid=1719&quizid=`

**절차:**
1. mynote.php 페이지 접속 (또는 새로고침)
2. 목록에서 해당 페이지 찾기
3. **아이콘 색상 확인:**
   - ❌ 이전: 🟡 (노란색) - audiourl2가 NULL
   - ✅ 현재: 🟢 (녹색) - audiourl2에 값 존재
4. 아이콘 옆에 재생횟수 표시 확인 (있는 경우)
5. 🟢 아이콘 클릭 시:
   - "절차기억 나레이션 재생성" 확인 대화상자 표시
   - 확인 클릭 시 나레이션 재생성 시작

**예상 결과:**
- 🟢 녹색 아이콘이 표시됨
- 아이콘에 마우스 오버 시 "절차기억 나레이션 재생성" 툴팁 표시
- 클릭 시 재생성 워크플로우 정상 작동

## 문제 해결 가이드

### 아이콘이 여전히 🟡로 표시되는 경우

**원인 1: audiourl2 필드 업데이트 실패**
```sql
-- DB 확인 쿼리
SELECT id, title, audiourl, audiourl2
FROM mdl_icontent_pages
WHERE id = 31906;
```
**해결방법:**
- audiourl2 필드가 NULL인지 확인
- `/home/moodle/logs/pmemory_upload.log` 로그 파일 확인
- "Database updated - icontent_pages table" 메시지 확인

**원인 2: 캐시 문제**
**해결방법:**
- 브라우저 새로고침 (Ctrl+F5 또는 Cmd+Shift+R)
- mynote.php 페이지 강제 새로고침
- 브라우저 캐시 삭제

**원인 3: section 파라미터 전송 문제**
**해결방법:**
- openai_tts_pmemory.php 571-572줄 확인:
  ```javascript
  formData.append('contentsid', contentsid);
  formData.append('contentstype', contentstype);
  // section 없음 = DB 업데이트 함
  ```
- `section` 파라미터가 포함되지 않았는지 확인

### 음성 생성은 되지만 DB 업데이트가 안 되는 경우

**로그 확인:**
```bash
tail -f /home/moodle/logs/pmemory_upload.log
```

**DB 권한 확인:**
```sql
-- Moodle DB 사용자 권한 확인
SHOW GRANTS FOR 'moodle_user'@'localhost';
```

**수동 DB 업데이트 (임시):**
```sql
UPDATE mdl_icontent_pages
SET audiourl2 = 'https://mathking.kr/Contents/audiofiles/pmemory/cid31906ct1_pmemory.wav'
WHERE id = 31906;
```

## 데이터베이스 관련 정보

### 관련 테이블

**1. mdl_icontent_pages**
- **필드:** `audiourl` (수업 엿듣기), `audiourl2` (절차기억 나레이션)
- **용도:** 학습 콘텐츠 페이지 정보

**2. mdl_abrainalignment_gptresults**
- **필드:** `outputtext` (생성된 나레이션 텍스트)
- **용도:** GPT 생성 결과 저장

**3. mdl_abessi_messages**
- **필드:** `nreview` (재생횟수), `wboardid`, `url`
- **용도:** 학습 세션 추적

## 참고사항

### 파일 명명 규칙
- 구간별: `cid{contentsid}ct{contentstype}_section{n}.wav`
- 전체: `cid{contentsid}ct{contentstype}_pmemory.wav`

### 음성 파일 저장 위치
```
/home/moodle/public_html/Contents/audiofiles/pmemory/
└── cid31906ct1_pmemory.wav
└── cid31906ct1_section1.wav
└── cid31906ct1_section2.wav
└── ...
```

### URL 형식
```
https://mathking.kr/Contents/audiofiles/pmemory/cid{contentsid}ct{contentstype}_pmemory.wav
```

## 디버깅 도구 사용 가이드

### 1. 서버측 로그 확인
**파일:** `/home/moodle/logs/pmemory_upload.log`

**확인 사항:**
```bash
# 최근 로그 확인
tail -f /home/moodle/logs/pmemory_upload.log

# 특정 contentsid 관련 로그 필터링
grep "CID:31906" /home/moodle/logs/pmemory_upload.log

# DB 업데이트 메시지 확인
grep "Database updated" /home/moodle/logs/pmemory_upload.log
```

**로그 메시지 해석:**
- `POST data - contentsid: X, contentstype: Y, section: NULL` ✅ section 파라미터가 없음 (정상)
- `Section check - value: NULL, will update DB: YES` ✅ DB 업데이트 예정
- `Entering DB update block` ✅ DB 업데이트 시작
- `Executing UPDATE query` ✅ 쿼리 실행 중
- `Database updated - icontent_pages table, audiourl2: URL` ✅ 업데이트 성공
- `DB update completed successfully` ✅ 완료

### 2. 브라우저 콘솔 로그 확인
**페이지:** `openai_tts_pmemory.php`
**도구:** F12 개발자 도구 → Console 탭

**확인 사항:**
```javascript
// 업로드 시작 로그
=== 전체 병합 파일 업로드 시작 ===
contentsid: "31906"
contentstype: "1"
section parameter: "NOT SENT (should update DB)"

// 응답 로그
=== 업로드 응답 받음 ===
Full response: {success: true, audiourl: "...", url: "..."}
data.success: true
data.audiourl: "https://mathking.kr/Contents/audiofiles/pmemory/..."
```

**오류 발생 시:**
```javascript
=== AJAX 업로드 오류 ===
error: "..."
status: "error"
xhr.status: 500
xhr.responseText: "..."
```

### 3. DB 검증 페이지 사용
**URL:** `https://mathking.kr/moodle/local/augmented_teacher/books/verify_db_audiourl2.php?cid=31906&ctype=1`

**기능:**
- ✅ audiourl2 필드 값 실시간 조회
- ✅ GPT 나레이션 결과 확인
- ✅ 최근 업로드 로그 50줄 표시 (해당 contentsid 강조)
- ✅ 수동 DB 업데이트 쿼리 제공
- ✅ 관련 페이지 빠른 링크
- ✅ 문제 해결 체크리스트

**사용 시나리오:**
1. TTS 생성 후 아이콘이 🟡로 남아있는 경우
2. DB 업데이트가 성공했는지 확인하고 싶은 경우
3. 수동으로 DB를 업데이트해야 하는 경우
4. 로그를 빠르게 확인하고 싶은 경우

## 검증 완료 체크리스트

### Phase 1: 코드 수정 (완료)
- [x] openai_tts_pmemory.php 수정 완료 (사용자 안내 메시지 추가)
- [x] file_pmemory.php 디버그 로깅 추가
- [x] openai_tts_pmemory.php 콘솔 로깅 추가
- [x] DB 검증 페이지 생성 (verify_db_audiourl2.php)

### Phase 2: 테스트 (진행 중)
- [ ] 절차기억 나레이션 텍스트 생성 테스트
- [ ] TTS 음성 생성 및 업로드 테스트
- [ ] 서버 로그 확인 (/home/moodle/logs/pmemory_upload.log)
- [ ] 브라우저 콘솔 로그 확인 (F12 개발자 도구)
- [ ] DB 검증 페이지로 audiourl2 필드 확인
- [ ] mynote.php에서 🟢 아이콘 표시 확인
- [ ] 아이콘 클릭 시 재생성 기능 동작 확인

### Phase 3: 문제 해결 (필요시)
- [ ] 로그 분석을 통한 실패 지점 파악
- [ ] DB 업데이트가 실행되지 않는 경우 원인 분석
- [ ] section 파라미터가 의도와 다르게 전송되는지 확인
- [ ] 필요시 수동 DB 업데이트 실행
- [ ] 코드 수정 후 재테스트

## 디버깅 워크플로우

```
1. TTS 생성 실행
   ↓
2. 브라우저 콘솔 확인 (F12)
   → AJAX 요청/응답 로그 확인
   → data.success: true인지 확인
   → data.audiourl에 URL이 있는지 확인
   ↓
3. 서버 로그 확인
   → tail -f /home/moodle/logs/pmemory_upload.log
   → "Database updated" 메시지 확인
   → section 파라미터가 NULL/EMPTY인지 확인
   ↓
4. DB 검증 페이지 확인
   → verify_db_audiourl2.php?cid=X&ctype=Y
   → audiourl2 필드 값 확인
   → 최근 로그 재확인
   ↓
5. mynote.php 확인
   → 아이콘 색상 확인 (🟢 = 성공, 🟡 = 실패)
   → 필요시 페이지 새로고침 (Ctrl+F5)
```

---

**작성일:** 2025-10-15
**최종 수정:** 2025-10-16
**수정자:** Claude Code
**버전:** 2.0 (디버깅 도구 추가)
