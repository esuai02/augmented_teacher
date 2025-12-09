# OpenAI 모델 정보 (2025년 1월 기준)

## 현재 사용 중인 모델

### GPT-5-mini (현재 설정)
- **모델 ID**: `gpt-5-mini`
- **API 엔드포인트**: `https://api.openai.com/v1/responses`
- **특징**: 새로운 Responses API, 향상된 추론 능력, JSON Schema 지원
- **장점**: 비용 효율적, 빠른 응답 속도

## Responses API 변경사항

### 이전 (Chat Completions API)
```json
{
  "model": "gpt-4o",
  "messages": [
    {"role": "system", "content": "..."},
    {"role": "user", "content": "..."}
  ]
}
```
**응답**: `choices[0].message.content`

### 현재 (Responses API)
```json
{
  "model": "gpt-5-mini",
  "input": "시스템 메시지\n\n사용자 메시지",
  "response_format": {
    "type": "json_schema",
    "json_schema": {...}
  }
}
```
**응답**: `output_text` 또는 `output_parsed`

## 사용 가능한 모델

### GPT-5 시리즈 (2025년 출시)
- **gpt-5**: 최고 성능, 복잡한 추론
- **gpt-5-mini**: 비용 효율적, 빠른 응답
- **gpt-5-turbo**: 균형잡힌 성능

### GPT-4 시리즈 (레거시)
- **gpt-4o**: 이전 기본 모델
- **gpt-4o-mini**: 경량 버전
- **gpt-4-turbo**: 터보 버전

## 모델 변경 방법

`config/openai_config.php` 파일에서 다음 줄을 수정:

```php
// GPT-5-mini 사용 (현재 설정 - 권장)
define('PATTERNBANK_OPENAI_MODEL', 'gpt-5-mini');
define('PATTERNBANK_OPENAI_API_URL', 'https://api.openai.com/v1/responses');

// GPT-5 전체 버전으로 변경 (더 높은 성능, 더 높은 비용)
define('PATTERNBANK_OPENAI_MODEL', 'gpt-5');
define('PATTERNBANK_OPENAI_API_URL', 'https://api.openai.com/v1/responses');

// 레거시 GPT-4o로 되돌리기 (이전 API 사용)
define('PATTERNBANK_OPENAI_MODEL', 'gpt-4o');
define('PATTERNBANK_OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
```

## API 헤더 요구사항

Responses API 사용 시 다음 헤더 추가 필요:
```php
'OpenAI-Beta: responses-api-2025-01'
```

## 주요 차이점

| 항목 | Chat Completions API | Responses API |
|------|---------------------|---------------|
| 엔드포인트 | `/v1/chat/completions` | `/v1/responses` |
| 입력 형식 | messages 배열 | input 문자열 |
| 응답 구조 | choices[0].message.content | output_text/output_parsed |
| JSON Schema | response_format: {type: "json_object"} | 상세한 JSON Schema 지원 |
| 모델 | gpt-4 시리즈 | gpt-5 시리즈 |

## 테스트 방법

```bash
# API 테스트 실행
php test_api_simple.php

# 통합 테스트 실행
php test_openai.php
```

## 문제 해결

### HTTP 404 오류
- GPT-5가 아직 사용 불가능한 경우
- 해결: GPT-4o로 임시 변경

### HTTP 401 오류
- API 키가 잘못된 경우
- 해결: config/api_keys.php 확인

### JSON 파싱 오류
- response_format 설정 확인
- output_parsed 필드 사용

## 권장사항

현재 **GPT-5-mini**와 **Responses API**를 사용하는 것이 가장 효율적입니다.
- 비용 효율적
- 빠른 응답 속도
- 향상된 JSON Schema 지원
- 더 나은 추론 능력

---
최종 업데이트: 2025년 1월