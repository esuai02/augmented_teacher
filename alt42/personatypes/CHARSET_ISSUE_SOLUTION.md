# 문자셋 문제 해결 가이드

## 문제
MySQL/MariaDB 데이터베이스가 이모지(🧠 등)를 저장할 수 없는 문제 발생
- 오류: "Incorrect string value: '\xF0\x9F\xA7\xA0' for column 'icon'"
- 원인: 테이블이 utf8mb4가 아닌 utf8로 설정되어 있음

## 해결 방법

### 방법 1: 데이터베이스 문자셋 변경 (권장)
1. 관리자 권한으로 접속
2. https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/fix_charset_issue.php 실행
3. 모든 테이블이 utf8mb4로 변경됨
4. 원본 데이터 삽입 스크립트 사용 가능

### 방법 2: 안전 버전 사용 (임시 해결책)
1. https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/insert_60_personas_data_safe.php 사용
2. 이모지를 텍스트로 저장 (예: 🧠 → "brain")
3. 프론트엔드에서 자동으로 변환하여 표시

## 현재 적용된 솔루션

### 1. 안전한 데이터 삽입 스크립트
- `insert_60_personas_data_safe.php` 생성
- 모든 이모지를 텍스트로 매핑
- 데이터베이스에 안전하게 저장

### 2. 프론트엔드 자동 변환
- `MathPersonaSystem.js`에 아이콘 매핑 추가
- `getIcon()` 메서드로 텍스트를 이모지로 자동 변환
- 사용자는 정상적으로 이모지를 볼 수 있음

### 3. 아이콘 매핑
```javascript
{
    'brain': '🧠',    // 인지 과부하
    'anxious': '😰',  // 자신감 왜곡
    'error': '❌',    // 실수 패턴
    'target': '🎯',   // 접근 전략 오류
    'book': '📚',     // 학습 습관
    'clock': '⏰',    // 시간/압박 관리
    'check': '✔️',    // 검증/확인 부재
    'tool': '🔧'      // 기타 장애
}
```

## 다음 단계

1. **즉시 사용 가능**: 
   - https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/insert_60_personas_data_safe.php 실행
   - "60 페르소나 데이터 삽입 시작 (안전 버전)" 버튼 클릭

2. **나중에 개선**:
   - DBA에게 utf8mb4 변경 요청
   - 변경 후 원본 이모지 데이터로 업데이트

## 테스트
1. 데이터 삽입 후 메인 페이지 접속
2. "📚 수학 인지관성 도감" 버튼 클릭
3. 60개 카드가 정상적으로 표시되는지 확인
4. 각 카드에 올바른 이모지가 표시되는지 확인