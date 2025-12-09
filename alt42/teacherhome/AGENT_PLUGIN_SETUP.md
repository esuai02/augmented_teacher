# 에이전트 플러그인 설정 가이드

## 🚀 빠른 실행 (권장)

1. **브라우저에서 다음 파일을 열어주세요:**
   ```
   /mnt/c/1 Project/alt42/teacherhome/run_agent_setup.html
   ```

2. **자동으로 실행됩니다:**
   - 에이전트 플러그인이 데이터베이스에 추가됩니다
   - 성공 메시지가 표시됩니다
   - "메인 페이지로 이동" 버튼을 클릭하세요

## ✅ 확인 방법

1. **메인 페이지에서 확인:**
   - `index.php`를 열어주세요
   - 상단 헤더의 🔌 버튼을 클릭하세요
   - 플러그인 추가 드롭다운에서 "🤖 에이전트"가 표시되는지 확인하세요

2. **플러그인 타입 확인:**
   - `check_plugin_types.html`을 열어주세요
   - 현재 등록된 모든 플러그인 타입이 표시됩니다

## 📁 생성된 파일들

- `run_agent_setup.html` - 자동 실행 페이지
- `auto_add_agent.html` - 에이전트 추가 및 확인
- `execute_add_agent.php` - PHP 실행 스크립트
- `check_plugin_types.html` - 플러그인 타입 확인
- `add_agent_plugin_type.sql` - SQL 스크립트
- `create_plugin_tables_alt42.sql` - 테이블 생성 SQL

## 🎯 결과

에이전트 플러그인이 추가되면 다음과 같이 사용할 수 있습니다:
- URL 기반 에이전트: 외부 URL을 호출하여 결과를 표시
- PHP 코드 에이전트: 입력한 PHP 코드를 실행하여 결과를 표시

## ⚠️ 문제 해결

만약 에이전트가 나타나지 않는다면:
1. 브라우저 캐시를 지우고 새로고침하세요
2. `run_agent_setup.html`을 다시 실행하세요
3. 데이터베이스에 직접 SQL을 실행하세요 (add_agent_plugin_type.sql)