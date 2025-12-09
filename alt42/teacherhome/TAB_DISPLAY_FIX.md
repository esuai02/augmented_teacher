# 좌측 메뉴 클릭 시 탭 표시 안되는 문제 해결

## 문제 설명
사용자가 좌측 메뉴를 클릭했을 때 탭이 표시되지 않는 오류가 발생함.

## 원인 분석

### 1. 모듈 데이터 로딩 문제
- 일부 모듈이 아직 초기화되지 않은 상태에서 `getData()`를 호출하면 `null`을 반환
- `categoryData.tabs`가 undefined일 때 `.map()` 메서드 호출로 오류 발생

### 2. 타이밍 이슈
- 모듈 로더가 데이터베이스에서 데이터를 가져오는 것이 비동기 작업
- 사용자가 메뉴를 클릭하는 시점에 데이터가 아직 로드되지 않았을 수 있음

## 해결 방법

### 1. 안전한 데이터 접근 (script.js)
```javascript
// getMenuStructure 함수 개선
const getModuleData = (module, fallbackData = null) => {
    if (!module) return fallbackData;
    try {
        const data = module.getData();
        if (data && !data.tabs) {
            data.tabs = [];
        }
        return data;
    } catch (error) {
        console.error('모듈 데이터 로드 오류:', error);
        return fallbackData;
    }
};
```

### 2. 탭 렌더링 시 안전 체크 (showMenuInterface)
```javascript
// 탭이 있는지 확인 후 렌더링
${categoryData.tabs && Array.isArray(categoryData.tabs) ? 
    categoryData.tabs.map(...) : 
    '<p>탭 데이터를 불러오는 중...</p>'
}
```

### 3. 모듈 재초기화 로직 추가
모듈 데이터가 없을 경우 재초기화를 시도하는 로직 추가

## 테스트 도구

### 1. test_tab_display.html
- 각 카테고리의 탭 표시 상태를 시뮬레이션
- 모듈 로딩 상태를 실시간으로 확인

### 2. debug_module_loading.html
- 모든 모듈의 로딩 상태를 상세히 확인
- 데이터베이스 연결 테스트
- 실시간 로그 모니터링

## 사용 방법

1. **문제 진단**
   ```
   http://yoursite/teacherhome/debug_module_loading.html
   ```
   - "모든 모듈 확인" 버튼 클릭
   - 문제가 있는 모듈 확인

2. **모듈 초기화**
   - "모든 모듈 초기화" 버튼 클릭
   - 초기화 완료 후 메인 페이지에서 테스트

3. **데이터베이스 확인**
   - "DB 연결 테스트" 버튼으로 데이터베이스 연결 확인
   - consultation 모듈 데이터가 제대로 추가되었는지 확인

## 추가 권장사항

1. **페이지 로드 시 모듈 초기화**
   ```javascript
   window.addEventListener('DOMContentLoaded', async () => {
       // 모든 모듈 초기화
       const modules = ['quarterlyModule', 'weeklyModule', ...];
       for (const moduleName of modules) {
           const module = window[moduleName];
           if (module && module.initialize) {
               await module.initialize();
           }
       }
   });
   ```

2. **로딩 인디케이터 추가**
   - 데이터 로딩 중일 때 사용자에게 시각적 피드백 제공

3. **에러 핸들링 강화**
   - 모든 모듈 관련 작업에 try-catch 블록 추가
   - 사용자 친화적인 에러 메시지 표시