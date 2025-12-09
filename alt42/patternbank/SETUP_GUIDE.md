# PatternBank OpenAI 자동 생성 설정 가이드

## ⚠️ 현재 문제
- **"자동 생성에 실패했습니다. 수동 입력 모드로 전환합니다."** 오류 발생
- 원인: OpenAI API 키가 설정되지 않음

## 🔧 해결 방법

### 방법 1: 직접 API 키 설정 (권장)

1. OpenAI API 키 발급:
   - https://platform.openai.com/api-keys 방문
   - 새 API 키 생성 (sk-로 시작)

2. 설정 파일 수정:
   ```php
   // alt42/patternbank/config/openai_config.php 파일 수정
   
   // 이 부분을 찾아서:
   define('PATTERNBANK_OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'your_api_key_here');
   
   // 실제 API 키로 변경:
   define('PATTERNBANK_OPENAI_API_KEY', 'sk-실제-API-키-여기에-입력');
   ```

### 방법 2: 환경 변수 설정

Linux/Mac:
```bash
export OPENAI_API_KEY="sk-실제-API-키-여기에-입력"
```

Windows:
```cmd
set OPENAI_API_KEY=sk-실제-API-키-여기에-입력
```

또는 Apache/Nginx 설정에 추가:
```apache
SetEnv OPENAI_API_KEY "sk-실제-API-키-여기에-입력"
```

### 방법 3: 별도 설정 파일 사용 (보안 강화)

1. 보안 설정 파일 생성:
   ```php
   // alt42/patternbank/config/api_keys.php (새 파일)
   <?php
   define('OPENAI_API_KEY_SECURE', 'sk-실제-API-키-여기에-입력');
   ?>
   ```

2. .gitignore에 추가:
   ```
   alt42/patternbank/config/api_keys.php
   ```

3. openai_config.php 수정:
   ```php
   // API 키 로드
   if (file_exists(__DIR__ . '/api_keys.php')) {
       require_once(__DIR__ . '/api_keys.php');
       define('PATTERNBANK_OPENAI_API_KEY', OPENAI_API_KEY_SECURE);
   } else {
       define('PATTERNBANK_OPENAI_API_KEY', getenv('OPENAI_API_KEY') ?: 'your_api_key_here');
   }
   ```

## 🧪 설정 테스트

1. 브라우저에서 테스트:
   ```
   http://your-domain/alt42/patternbank/test_openai.php
   ```

2. 예상 결과:
   ```
   === PatternBank OpenAI API 테스트 ===
   
   1. API 키 확인...
      ✅ API 키 설정됨: sk-proj-...
   
   2. OpenAI API 연결 테스트...
      ✅ 연결 성공!
      - 모델: gpt-4o
   ```

## 📊 API 사용량 관리

- GPT-4o 모델 요금: $5.00 / 1M input tokens, $15.00 / 1M output tokens
- 예상 사용량: 문제 생성 1회당 약 2,000~3,000 토큰
- 월 예상 비용: 하루 10회 사용 시 약 $5~10

## 🛡️ 보안 주의사항

1. **API 키를 절대 GitHub에 커밋하지 마세요**
2. 프로덕션 환경에서는 환경 변수 사용 권장
3. API 키는 정기적으로 재생성
4. Rate Limiting 구현 고려

## 🐛 추가 문제 해결

### SSL 인증서 오류
```php
// openai_config.php에서 수정
CURLOPT_SSL_VERIFYPEER => false, // 개발 환경에서만
```

### 타임아웃 오류
```php
// 타임아웃 시간 증가
define('PATTERNBANK_OPENAI_TIMEOUT', 60); // 60초로 증가
```

### 프록시 설정 (필요시)
```php
curl_setopt($ch, CURLOPT_PROXY, 'http://proxy.example.com:8080');
curl_setopt($ch, CURLOPT_PROXYUSERPWD, 'username:password');
```

## 📞 문의

추가 문제가 있으면 다음을 확인하세요:
- 서버 로그: `/var/log/apache2/error.log`
- PHP 에러 로그 활성화
- 브라우저 개발자 도구 > 네트워크 탭 확인