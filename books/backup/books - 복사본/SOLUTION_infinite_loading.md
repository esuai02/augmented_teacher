# 무한 로딩 문제 해결 가이드

## 🔴 문제 증상
- 헤드폰 아이콘 클릭 시 "나레이션 생성 중..." 메시지와 함께 무한 로딩 지속
- 로딩 다이얼로그가 자동으로 닫히지 않음
- 브라우저 콘솔에 에러 메시지 없음

## ✅ 해결된 문제들

### 1. **Swal.close() 누락 문제 해결**
- **원인**: AJAX error 콜백에서 로딩 다이얼로그를 닫지 않음
- **해결**: error 콜백 시작 부분에 `Swal.close()` 추가
- **파일**: `mynote1.php` (1320번째 줄)

### 2. **타임아웃 설정 추가**
- **원인**: API 응답이 오래 걸릴 경우 무한 대기
- **해결**: AJAX 요청에 2분 타임아웃 설정 (`timeout: 120000`)
- **파일**: `mynote1.php` (1290번째 줄)

### 3. **PHP 에러 출력 차단**
- **원인**: PHP 에러가 JSON 응답을 손상시킴
- **해결**:
  - 출력 버퍼링 사용 (`ob_start()`, `ob_clean()`)
  - 모든 에러 출력 차단
  - JSON 헤더 즉시 설정
- **파일**: `generate_narration.php`

### 4. **API 에러 코드별 처리**
- **원인**: 모든 API 에러를 동일하게 처리
- **해결**: HTTP 상태 코드별 구체적 메시지 제공
  - 401: API 키 유효하지 않음
  - 429: 사용량 한도 초과
  - 500: 서버 오류
- **파일**: `generate_narration.php` (164-173번째 줄)

### 5. **API 키 보안 강화**
- **원인**: API 키가 코드에 하드코딩되어 노출
- **해결**:
  - 환경변수 우선 사용
  - .gitignore에 api_config.php 추가
  - API 키 교체 필요 안내
- **파일**: `api_config.php`, `.gitignore`

## 📋 즉시 필요한 조치

### 1. **새 API 키 발급**
```bash
# 1. OpenAI 대시보드 접속
https://platform.openai.com/api-keys

# 2. 새 API 키 생성

# 3. 서버에서 환경변수 설정
export OPENAI_API_KEY="sk-proj-새로운-API-키"

# 또는 api_config.php 직접 수정
$apiKey = 'sk-proj-새로운-API-키';
```

### 2. **오디오 디렉토리 권한 확인**
```bash
# 디렉토리 생성 및 권한 설정
mkdir -p /home/moodle/public_html/audiofiles
chmod 755 /home/moodle/public_html/audiofiles
chown www-data:www-data /home/moodle/public_html/audiofiles
```

### 3. **테스트 실행**
```
1. 브라우저에서 test_narration.php 접속
2. 환경 점검 확인
3. 콘텐츠 ID 1로 테스트
4. 브라우저 콘솔 확인
```

## 🔍 디버깅 방법

### 브라우저 콘솔에서 확인
```javascript
// F12 → Console 탭
// "나레이션 생성 오류 상세:" 메시지 확인
// status, statusText, responseText 값 확인
```

### 서버 로그 확인
```bash
# 에러 로그 실시간 모니터링
tail -f /mnt/c/1\ Project/augmented_teacher/books/narration_error.log

# PHP 에러 로그
tail -f /var/log/apache2/error.log
```

## ✨ 개선된 기능

1. **상세한 에러 메시지**
   - 네트워크 오류, API 키 문제, 서버 오류 구분
   - 사용자 친화적인 메시지 표시

2. **자동 로딩 종료**
   - 에러 발생 시 로딩 다이얼로그 자동 종료
   - 타임아웃 시에도 적절한 처리

3. **디버깅 정보 향상**
   - 브라우저 콘솔에 상세 정보 출력
   - 서버 로그에 디버그 정보 기록

4. **보안 강화**
   - API 키 환경변수 사용
   - 버전 관리에서 제외

## 📝 추가 권장사항

1. **API 키 정기 교체**
   - 3개월마다 API 키 갱신
   - 키 노출 시 즉시 교체

2. **모니터링 설정**
   - API 사용량 모니터링
   - 에러 발생률 추적

3. **백업 계획**
   - API 장애 시 대체 방안
   - 로컬 캐시 활용

## 작성일
2025년 1월 27일