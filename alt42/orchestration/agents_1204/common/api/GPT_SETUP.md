# GPT API 설정 가이드

OpenAI GPT-4 API를 agent 분석 리포트 생성에 통합하는 방법

---

## 📋 사전 준비

### 1. OpenAI API 키 발급

1. [OpenAI Platform](https://platform.openai.com/)에 접속
2. 계정 생성 및 로그인
3. **API Keys** 메뉴로 이동
4. **+ Create new secret key** 클릭
5. 키 이름 입력 (예: "ALT42-Agent-Analysis")
6. 생성된 키를 안전한 곳에 복사 (한 번만 표시됨!)

**중요**: API 키는 절대 공개 저장소에 커밋하지 마세요!

### 2. API 사용량 및 요금 확인

- **무료 체험**: 신규 계정은 $5 크레딧 제공 (3개월 유효)
- **요금제**: [OpenAI Pricing](https://openai.com/pricing) 참조
- **GPT-4 가격** (2025년 1월 기준):
  - Input: $0.03 / 1K tokens
  - Output: $0.06 / 1K tokens
- **예상 비용**: 분석 1회당 약 500-800 tokens → $0.02-0.05

---

## ⚙️ 설정 방법

### 1. API 키 설정

파일 경로: `/api/gpt_config.php`

```php
// Line 20: API 키를 본인의 키로 교체
define('OPENAI_API_KEY', 'sk-proj-YOUR-ACTUAL-API-KEY-HERE');
```

**교체 전**:
```php
define('OPENAI_API_KEY', 'sk-YOUR-API-KEY-HERE');
```

**교체 후**:
```php
define('OPENAI_API_KEY', 'sk-proj-abc123def456ghi789...');
```

### 2. 모델 선택 (선택사항)

```php
// Line 28: 사용할 GPT 모델 선택
define('OPENAI_MODEL', 'gpt-4');
```

**모델 옵션**:
- `'gpt-4'`: 최고 품질, 느림, 비쌈 (권장)
- `'gpt-4o'`: GPT-4 Omni, 빠름, 저렴
- `'gpt-4o-mini'`: 가장 저렴, 빠름
- `'gpt-3.5-turbo'`: 빠름, 저렴, 품질 낮음

### 3. 파라미터 조정 (선택사항)

```php
// Temperature (0.0-2.0): 낮을수록 일관성↑, 높을수록 창의성↑
define('OPENAI_TEMPERATURE', 0.7);  // 기본값: 0.7

// Max Tokens: 응답 최대 길이
define('OPENAI_MAX_TOKENS', 1500);  // 기본값: 1500

// Timeout: API 요청 타임아웃 (초)
define('OPENAI_TIMEOUT', 30);  // 기본값: 30초
```

---

## ✅ 설정 확인

### 1. 로그 확인

`/var/log/apache2/error.log` 또는 `/var/log/php-errors.log`에서:

```bash
# GPT 설정 로드 확인
tail -f /var/log/apache2/error.log | grep "gpt_config"

# 예상 출력:
[gpt_config.php] GPT API integration status: ENABLED | Model: gpt-4
```

### 2. 테스트 요청

1. 브라우저에서 orchestration 시스템 열기
2. 아무 agent 카드에서 **🎯 문제 타게팅** 클릭
3. 문제 항목 하나 선택
4. 우측 패널에 분석 리포트 생성 확인

**로딩 시간**: 5-15초 (GPT-4 사용 시)

### 3. API 호출 로그 확인

```bash
tail -f /var/log/apache2/error.log | grep "gpt_helper"

# 성공 시 예상 출력:
[gpt_helper.php] Calling GPT API | Model: gpt-4 | Tokens: 1500
[gpt_helper.php] API call successful | Response length: 847 chars
```

---

## 🔧 문제 해결

### 문제 1: "GPT API not configured" 오류

**원인**: API 키가 설정되지 않음

**해결**:
1. `/api/gpt_config.php` 열기
2. Line 20의 `OPENAI_API_KEY` 확인
3. `sk-YOUR-API-KEY-HERE`를 실제 키로 교체
4. 파일 저장 후 페이지 새로고침

### 문제 2: "Invalid API key" (HTTP 401)

**원인**: API 키가 잘못되었거나 만료됨

**해결**:
1. [OpenAI Platform](https://platform.openai.com/api-keys)에서 키 확인
2. 키가 만료되었으면 새 키 생성
3. `gpt_config.php`에 새 키 입력

### 문제 3: "Rate limit exceeded" (HTTP 429)

**원인**: API 호출 제한 초과

**해결**:
1. OpenAI 대시보드에서 사용량 확인
2. 요금제 업그레이드 고려
3. 잠시 대기 후 재시도 (1-2분)

### 문제 4: 응답이 너무 느림

**원인**: GPT-4는 응답 생성에 10-20초 소요

**해결**:
1. 더 빠른 모델로 변경 (`gpt-4o` 또는 `gpt-4o-mini`)
2. `MAX_TOKENS` 값 낮추기 (1500 → 1000)
3. 로딩 애니메이션이 정상 작동하는지 확인

### 문제 5: Placeholder 분석만 표시됨

**원인**: GPT API가 비활성화되었거나 오류 발생

**확인**:
```bash
# 로그에서 placeholder 사용 여부 확인
grep "using placeholder" /var/log/apache2/error.log

# GPT 실패 원인 확인
grep "GPT analysis failed" /var/log/apache2/error.log
```

**해결**:
1. `gpt_config.php`에서 API 키 확인
2. `isGPTEnabled()` 함수가 true 반환하는지 확인
3. 로그에서 구체적인 오류 메시지 확인

---

## 🔐 보안 권장사항

### 1. API 키 보호

❌ **하지 말 것**:
```php
// 공개 저장소에 커밋
define('OPENAI_API_KEY', 'sk-proj-abc123...');
```

✅ **권장 방법**:
```php
// 환경 변수 사용
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
```

또는 `.gitignore`에 `gpt_config.php` 추가:
```
# .gitignore
/api/gpt_config.php
```

### 2. Rate Limiting 구현

향후 개선 사항:
- 사용자당 일일 요청 제한 (예: 50회/일)
- IP 기반 Rate Limiting
- 캐싱으로 중복 요청 방지

### 3. 비용 모니터링

- OpenAI 대시보드에서 사용량 확인
- 월별 예산 한도 설정
- 비용 알림 설정 (예: $50 도달 시)

---

## 📊 성능 최적화

### 1. 응답 캐싱

동일한 agent + problem 조합에 대해 24시간 캐싱:

```php
// 향후 구현 예정
$cache_key = "analysis_{$agent_id}_{$problem_index}_{$student_id}";
$cached = $cache->get($cache_key);
if ($cached) {
    return $cached;  // 캐시된 분석 반환
}
```

### 2. Batch Processing

여러 학생에 대한 분석을 일괄 처리:
- 야간 배치로 실행
- 미리 생성된 분석 저장
- 실시간 조회 시 DB에서 로드

### 3. 모델 다운그레이드

덜 중요한 agent는 저렴한 모델 사용:
```php
// Agent별 모델 선택
$model = ($agent_number <= 5) ? 'gpt-4' : 'gpt-4o-mini';
```

---

## 📈 모니터링

### DB 분석 로그

`alt42_agent_analyses` 테이블 생성 (선택사항):

```sql
CREATE TABLE alt42_agent_analyses (
    id BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(50) NOT NULL,
    agent_number INT(11) NOT NULL,
    agent_name VARCHAR(255) NOT NULL,
    problem_text TEXT NOT NULL,
    problem_index INT(11) NOT NULL,
    student_id BIGINT(10) UNSIGNED NOT NULL,
    student_name VARCHAR(255) NOT NULL,
    analysis_json LONGTEXT NOT NULL,
    timecreated BIGINT(10) UNSIGNED NOT NULL,
    timemodified BIGINT(10) UNSIGNED NOT NULL,
    INDEX idx_student (student_id),
    INDEX idx_agent (agent_number),
    INDEX idx_created (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Agent analysis audit trail';
```

### 사용량 통계 쿼리

```sql
-- 일별 분석 생성 수
SELECT DATE(FROM_UNIXTIME(timecreated)) as date,
       COUNT(*) as analysis_count,
       COUNT(DISTINCT student_id) as unique_students
FROM alt42_agent_analyses
GROUP BY DATE(FROM_UNIXTIME(timecreated))
ORDER BY date DESC;

-- Agent별 사용 빈도
SELECT agent_number, agent_name,
       COUNT(*) as usage_count
FROM alt42_agent_analyses
GROUP BY agent_number, agent_name
ORDER BY usage_count DESC;
```

---

## 🆘 지원

문제가 지속되면:
1. 로그 파일 확인 (`error.log`)
2. OpenAI Status 페이지 확인: https://status.openai.com/
3. API 키 및 요금 상태 확인
4. 시스템 관리자에게 문의

---

**마지막 업데이트**: 2025-01-21
**버전**: 1.0
