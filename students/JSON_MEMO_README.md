# 📝 JSON 메모 입력 시스템

JSON 형식의 데이터를 입력받아 조건에 따라 사용자에게 메모를 저장하는 시스템입니다.

## 🚀 시작하기

### 파일 구조
```
students/
├── json_memo_input.html     # 메인 인터페이스
├── process_json_memo.php    # JSON 처리 백엔드
├── savememo.php            # 기존 메모 저장 로직
└── JSON_MEMO_README.md     # 이 파일
```

### 접속 방법
웹 브라우저에서 `students/json_memo_input.html`에 접속

## 📋 필수 JSON 필드

### 핵심 변수
- `useraddcourse`: 사용자 추가 과목 (예: "수학", "영어", "과학")
- `usermathlevel`: 사용자 수학 레벨 (예: "초급", "중급", "고급")
- `userprogresstype`: 사용자 진행 타입 (예: "기본학습", "심화학습", "복습")

### 기본 필드
- `userid`: 사용자 ID (필수, 숫자)
- `type`: 메모 타입 (선택, 기본값: "today")
- `content`: 추가 내용 (선택)
- `id`: 메모 ID (업데이트시 필요, 기본값: 0)
- `created_at`: 생성 시간 (업데이트시 필요)

## 🎯 조건문 로직

### 조건 1: 고급 수학 과정
```json
{
    "useraddcourse": "수학",
    "usermathlevel": "고급",
    "userprogresstype": "어떤값이든"
}
```
**결과**: "고급 수학 과정 - {진행타입} 진행중" 메시지 생성

### 조건 2: 심화학습 과정
```json
{
    "userprogresstype": "심화학습"
}
```
**결과**: "심화학습 과정 진행" 메시지 생성

### 조건 3: 기본 조건
위 조건들에 해당하지 않는 모든 경우
**결과**: "학습 진행 상황" 기본 메시지 생성

## 📝 JSON 예시

### 예시 1: 고급 수학 과정
```json
{
    "useraddcourse": "수학",
    "usermathlevel": "고급",
    "userprogresstype": "심화학습",
    "userid": 123,
    "type": "today",
    "content": "미적분 단원 완료"
}
```

### 예시 2: 심화학습 과정
```json
{
    "useraddcourse": "영어",
    "usermathlevel": "중급",
    "userprogresstype": "심화학습",
    "userid": 456,
    "type": "mystudy",
    "content": "고급 문법 학습",
    "include_metadata": true
}
```

### 예시 3: 기본 학습
```json
{
    "useraddcourse": "과학",
    "usermathlevel": "초급",
    "userprogresstype": "기본학습",
    "userid": 789,
    "content": "화학 기초 이론 학습"
}
```

### 예시 4: 기존 메모 업데이트
```json
{
    "useraddcourse": "수학",
    "usermathlevel": "중급",
    "userprogresstype": "복습",
    "userid": 123,
    "id": 45,
    "created_at": 1640995200,
    "type": "today",
    "content": "방정식 복습 완료"
}
```

## 🔧 허용되는 메모 타입

- `timescaffolding`: 시간 스캐폴딩
- `chapter`: 챕터
- `edittoday`: 오늘 편집
- `mystudy`: 내 학습
- `today`: 오늘 (기본값)

## 📤 응답 형식

### 성공 시
```json
{
    "success": true,
    "message": "메모가 성공적으로 저장되었습니다.",
    "processed_data": {
        "useraddcourse": "수학",
        "usermathlevel": "고급",
        "userprogresstype": "심화학습",
        "selected_users": [123],
        "content_generated": "생성된 메모 내용..."
    },
    "saved_records": [
        {
            "id": 67,
            "userid": 123,
            "action": "created",
            "created_at": 1640995200
        }
    ],
    "timestamp": 1640995200
}
```

### 실패 시
```json
{
    "success": false,
    "error": "오류 메시지"
}
```

## 🕒 시간 기반 처리

### 새 메모 생성
- `id`가 0이거나 없는 경우
- 항상 새로운 레코드 생성

### 기존 메모 처리
- `id`가 있고 `created_at`이 제공된 경우
- **24시간 이내**: 기존 레코드 업데이트
- **24시간 경과**: 새로운 레코드 생성 (복제)

## 🎨 메타데이터 포함

JSON에 `"include_metadata": true`를 추가하면 메모 내용에 메타데이터가 포함됩니다:

```json
{
    "useraddcourse": "수학",
    "usermathlevel": "고급", 
    "userprogresstype": "심화학습",
    "userid": 123,
    "include_metadata": true
}
```

결과적으로 메모 하단에 다음 내용이 추가됩니다:
```
--- 메타데이터 ---
{"useraddcourse":"수학","usermathlevel":"고급","userprogresstype":"심화학습","source":"json_input"}
```

## 🔍 디버깅

### 로그 확인
서버 에러 로그에서 다음 형식의 로그를 확인할 수 있습니다:
```
JSON 메모 삽입할 데이터: ...
JSON 메모 삽입 성공. 새 ID: 67
process_json_memo.php 에러: ...
```

### 일반적인 오류

1. **"필수 변수가 누락되었습니다"**
   - `useraddcourse`, `usermathlevel`, `userprogresstype` 중 하나 이상이 없음

2. **"사용자 ID가 누락되었습니다"**
   - `userid` 필드가 없거나 유효하지 않음

3. **"JSON 파싱 오류"**
   - JSON 형식이 올바르지 않음

4. **"데이터베이스 테이블이 존재하지 않습니다"**
   - `abessi_stickynotes` 테이블이 없음

## 🛠️ 사용자 정의

### 조건문 수정
`process_json_memo.php`의 47-80라인에서 조건문을 수정할 수 있습니다:

```php
// 새로운 조건 추가 예시
if ($useraddcourse === '과학' && $usermathlevel === '고급') {
    // 고급 과학 과정 처리
} elseif ($userprogresstype === '특별과정') {
    // 특별과정 처리  
}
```

### 메모 타입 추가
91라인의 `$allowed_types` 배열에 새로운 타입을 추가할 수 있습니다:

```php
$allowed_types = array('timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today', 'custom_type');
```

## ⚠️ 주의사항

1. **데이터베이스 연결**: Moodle 설정에 의존합니다.
2. **권한**: 적절한 데이터베이스 권한이 필요합니다.
3. **XSS 방지**: 입력 데이터는 적절히 이스케이프됩니다.
4. **로그 파일**: 민감한 정보가 로그에 기록될 수 있으니 주의하세요.

## 📞 지원

시스템 관련 문제가 있을 경우:
1. 브라우저 개발자 도구의 콘솔 확인
2. 서버 에러 로그 확인
3. JSON 형식 검증 도구 사용 