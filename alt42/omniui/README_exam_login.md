# 수학킹 시험 대비 시스템 로그인 가이드

## 로그인 방법

### 1. 직접 접속
```
https://your-domain.com/omniui/exam_system.php
```
- Moodle 로그인 화면이 나타나면 로그인
- 로그인 후 자동으로 exam_system.php로 이동

### 2. index.php에서 접속
index.php에 다음 코드를 추가하여 버튼 생성:

```html
<a href="exam_system.php?userid=<?php echo $userid; ?>" class="bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg hover:bg-green-700 flex items-center gap-2">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
    </svg>
    시험 설정
</a>
```

### 3. 로그인 확인
exam_system.php는 다음과 같이 로그인을 확인합니다:

```php
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
require_login();  // 로그인 필수

// URL 파라미터에서 userid 가져오기
$userid = optional_param('userid', 0, PARAM_INT);

// userid가 없으면 현재 로그인한 사용자 ID 사용
if ($userid == 0) {
    $userid = $USER->id;
}
```

## 로그인 문제 해결

### 문제: "User not found" 에러
- Moodle에 로그인되어 있는지 확인
- userid가 올바른지 확인

### 문제: 로그인 화면으로 계속 리다이렉트
1. Moodle config.php 경로 확인:
   ```php
   include_once("/home/moodle/public_html/moodle/config.php");
   ```
2. 실제 Moodle 설치 경로와 일치하는지 확인

### 문제: 권한 에러
- 사용자가 Moodle에 정상적으로 등록되어 있는지 확인
- 사용자 권한이 충분한지 확인

## 사용 흐름

1. **Moodle 로그인**
   - 기존 Moodle 계정으로 로그인

2. **exam_system.php 접속**
   - 직접 URL 입력 또는
   - index.php의 "시험 설정" 버튼 클릭

3. **자동 데이터 로드**
   - 이름: Moodle user 테이블에서 자동
   - 학교: mdl_user_info_data에서 자동
   - 학년: 출생년도로 자동 계산

4. **5단계 설정 완료**
   - 정보입력 → 시험설정 → 전략이해 → 단계선택 → 시작하기

5. **대시보드 이동**
   - dashboard.php?userid=X 로 자동 이동

## 보안 사항

- `require_login()` 으로 로그인 필수
- userid 파라미터 검증
- 사용자 존재 여부 확인
- Moodle 세션 관리 활용