# KTM 코파일럿 DB 마이그레이션 요약

## 작업 완료 내역

### 1. 하드코딩된 메뉴 데이터 분석 ✅
- **daily/daily.js**에서 12개의 hasLink 항목 발견
  - 교과서 단원별 해설
  - 교과서 단원별 핵심 내용 정리
  - 외부 링크
  - 포모도로
  - 기타 8개 항목

### 2. DB 마이그레이션 SQL 파일 생성 ✅
생성된 파일들:
- `insert_default_cards_data.sql` - 모든 기본 카드 데이터
- `insert_missing_cards_data.sql` - daily.js에서 누락된 12개 항목
- `complete_menu_migration.sql` - 통합 마이그레이션 스크립트

### 3. JavaScript 모듈 수정 ✅
**daily/daily.js 변경사항:**
```javascript
// 이전: 하드코딩된 데이터
const dailyData = {
    title: '오늘활동',
    tabs: [...]
};

// 이후: DB에서 동적 로드
let dailyData = {
    title: '오늘활동',
    tabs: []
};

async function loadDailyDataFromDB() {
    // DB에서 데이터 로드
}
```

**script.js 변경사항:**
- `getMenuStructure()` 함수를 async로 변경
- 모든 호출 부분에 await 추가

### 4. 테스트 파일 생성 ✅
- `test_db_migration.html` - DB 마이그레이션 검증용 테스트 페이지

## 실행 방법

### 1단계: SQL 실행
```sql
-- 1. 기본 테이블이 있는지 확인
-- 2. 기본 카드 데이터 삽입
mysql -u root -p ktm_database < insert_default_cards_data.sql

-- 3. 누락된 카드 데이터 삽입  
mysql -u root -p ktm_database < insert_missing_cards_data.sql
```

### 2단계: 테스트
1. 브라우저에서 `test_db_migration.html` 열기
2. 각 테스트 버튼 클릭하여 확인:
   - API 응답 테스트
   - Daily 모듈 로드 테스트
   - 데이터 비교

### 3단계: 메인 페이지 확인
1. `index.html?userid=1` 접속
2. 일일활동 메뉴 클릭
3. 모든 카드가 DB에서 로드되는지 확인

## 남은 작업

### 하드코딩 코드 완전 제거 (선택사항)
현재 fallback으로 남겨둔 하드코딩 코드를 완전히 제거하려면:

1. **기타 모듈들도 동일하게 수정**
   - weekly.js
   - quarterly.js
   - realtime.js
   - interaction.js
   - bias.js
   - development.js

2. **테스트 후 하드코딩 제거**
   ```javascript
   // daily.js의 setDefaultDailyData() 함수 제거
   // 원본 하드코딩된 데이터 구조 제거
   ```

## 주의사항

1. **user_id 설정**: SQL 실행 시 실제 사용자 ID로 변경 필요
2. **URL 업데이트**: 실제 서버 URL로 변경 필요
3. **권한 설정**: DB 사용자가 INSERT 권한 필요

## 마이그레이션 이점

1. **유연성**: 코드 수정 없이 DB에서 메뉴 구조 변경 가능
2. **사용자별 맞춤화**: user_id별로 다른 메뉴 구성 가능
3. **동적 업데이트**: 실시간으로 메뉴 추가/삭제/수정 가능
4. **플러그인 통합**: 메뉴와 플러그인 설정 통합 관리