# 🌟 Shining Stars - API 설정 가이드

## GPT API 설정 방법

### 1. OpenAI API 키 발급
1. [OpenAI Platform](https://platform.openai.com/)에 접속
2. 계정 생성 또는 로그인
3. API Keys 섹션에서 새 API 키 생성
4. 생성된 키를 안전한 곳에 보관

### 2. 설정 파일 구성
1. `api/config.php` 파일 열기
2. `OPENAI_API_KEY` 값을 실제 API 키로 교체:
   ```php
   define('OPENAI_API_KEY', 'sk-YOUR_ACTUAL_API_KEY_HERE');
   ```
3. 필요시 다른 설정 조정:
   - `OPENAI_MODEL`: 'gpt-4' 또는 'gpt-3.5-turbo'
   - `API_MAX_TOKENS`: 응답 최대 길이 (기본: 500)
   - `API_TEMPERATURE`: 창의성 수준 0-1 (기본: 0.7)

### 3. 보안 설정
⚠️ **중요**: config.php 파일을 절대 버전 관리에 포함시키지 마세요!

`.gitignore` 파일에 추가:
```
api/config.php
```

### 4. 기능 활성화/비활성화
```php
// GPT API 사용 여부
define('ENABLE_GPT_API', true);  // true: 사용, false: 비활성화

// 폴백 응답 사용 여부
define('ENABLE_FALLBACK', true);  // API 실패시 로컬 응답 사용
```

### 5. 비용 관리
- GPT-4: 더 정확하지만 비용이 높음 (~$0.03/1K tokens)
- GPT-3.5-turbo: 저렴하지만 품질이 낮을 수 있음 (~$0.002/1K tokens)

일일 사용량 제한 설정:
```php
define('API_DAILY_LIMIT', 1000);  // 일일 최대 요청 수
```

## 테스트 방법

### 1. API 연결 테스트
```bash
# cURL로 직접 테스트
curl -X POST http://localhost/shiningstars/api/gpt_handler.php \
  -H "Content-Type: application/json" \
  -d '{
    "nodeId": 0,
    "answer": "테스트 답변입니다",
    "questionType": "reflection",
    "userId": 1,
    "detectedBiases": []
  }'
```

### 2. 브라우저에서 테스트
1. index.php 페이지 열기
2. 첫 번째 노드 클릭
3. 답변 작성 후 제출
4. 개발자 도구 Console에서 응답 확인

### 3. 폴백 모드 테스트
1. `ENABLE_GPT_API`를 `false`로 설정
2. 시스템이 로컬 피드백을 사용하는지 확인

## 문제 해결

### API 키 오류
- 에러: "Invalid API key"
- 해결: API 키가 올바른지, 활성화되어 있는지 확인

### 타임아웃 오류
- 에러: "Request timeout"
- 해결: `API_TIMEOUT` 값을 늘려보세요 (예: 60초)

### 비용 초과
- 에러: "Rate limit exceeded"
- 해결: 
  - 일일 제한 확인
  - GPT-3.5-turbo로 변경 고려
  - 토큰 제한 줄이기

### 폴백 응답만 나오는 경우
1. `ENABLE_GPT_API`가 `true`인지 확인
2. API 키가 올바르게 설정되었는지 확인
3. `error_log` 파일에서 오류 메시지 확인

## 모니터링

### 로그 파일 위치
- PHP 에러 로그: `/var/log/apache2/error.log` (Linux)
- 또는 `php.ini`의 `error_log` 설정 확인

### 사용량 추적
OpenAI 대시보드에서 실시간 사용량 확인:
https://platform.openai.com/usage

## 최적화 팁

1. **캐싱 구현**: 동일한 질문에 대한 응답 캐싱
2. **배치 처리**: 여러 요청을 모아서 처리
3. **토큰 최적화**: 프롬프트 길이 최소화
4. **조건부 API 호출**: 특정 조건에서만 GPT 사용

## 지원

문제가 지속되면 다음을 확인하세요:
- OpenAI API 상태: https://status.openai.com/
- 계정 크레딧 잔액
- API 키 권한 설정