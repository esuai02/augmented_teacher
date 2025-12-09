# 기본 카드 지속성 디버깅 가이드

## 문제 해결 방법

### 1. 브라우저 콘솔 확인
1. F12를 눌러 개발자 도구를 엽니다
2. Console 탭을 선택합니다
3. 페이지를 새로고침하고 다음 로그를 확인합니다:
   - `Using user ID from...` - 사용 중인 사용자 ID
   - `Loading cards for category:` - 카드 로드 시도
   - `Number of cards loaded:` - 로드된 카드 개수
   - `Number of default cards:` - 기본 카드 개수

### 2. 테스트 페이지 사용
1. 브라우저에서 `test_default_card_persistence.html` 파일을 엽니다
2. 각 버튼을 순서대로 클릭하여 테스트합니다:
   - "저장된 카드 확인" - 현재 DB에 저장된 모든 카드 확인
   - "기본 카드 저장" - 테스트 카드 저장
   - "플러그인 타입 확인" - default_card 타입이 존재하는지 확인
   - "카드 로드" - 특정 카테고리/탭의 카드 로드

### 3. 수정된 사항
- **사용자 ID 고정**: 이제 URL에 userid가 없으면 기본값 1을 사용합니다
- **디버깅 로그 추가**: 카드 로드 과정에서 상세한 로그를 출력합니다

### 4. 확인 사항
1. **동일한 사용자 ID 사용**: 콘솔에서 "Current user ID"가 일치하는지 확인
2. **올바른 카테고리/탭**: 저장할 때와 로드할 때 동일한 카테고리와 탭 제목 사용
3. **로컬 스토리지**: Application > Local Storage에서 `ktm_user_id` 값 확인

### 5. 일반적인 문제와 해결책

#### 문제: 새로고침 후 카드가 사라짐
- **원인**: 다른 사용자 ID로 로드됨
- **해결**: URL에 `?userid=1` 추가하거나 로컬 스토리지 확인

#### 문제: 카드가 저장되지 않음
- **원인**: DB 연결 오류 또는 권한 문제
- **해결**: test_default_card_persistence.html에서 "직접 API 호출" 테스트

#### 문제: 기본 카드가 표시되지 않음
- **원인**: plugin_id가 'default_card'가 아님
- **해결**: DB에서 plugin_id 확인

### 6. 데이터베이스 직접 확인 (선택사항)
```sql
-- 저장된 카드 확인
SELECT * FROM mdl_alt42DB_card_plugin_settings 
WHERE user_id = 1 
AND plugin_id = 'default_card';

-- 플러그인 타입 확인
SELECT * FROM mdl_alt42DB_plugin_types 
WHERE plugin_id = 'default_card';
```

## 추가 지원
문제가 지속되면 다음 정보와 함께 보고해주세요:
1. 브라우저 콘솔의 전체 로그
2. test_default_card_persistence.html의 테스트 결과
3. 사용 중인 브라우저와 버전