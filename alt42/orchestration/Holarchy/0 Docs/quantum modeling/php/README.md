# PHP 모듈

> Quantum Modeling 시스템의 PHP 구현

## 구조

```
php/
├── wavefunctions/     # 파동함수 (Python 호출 래퍼)
├── ide/               # IDE 컴포넌트
├── api/               # REST API 엔드포인트
└── utils/             # 유틸리티
```

## Moodle 통합

모든 PHP 파일은 다음 코드로 시작해야 합니다:

```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();
```

## Python 호출

```php
// REST API 방식 (권장)
function call_quantum_api($endpoint, $data) {
    $url = 'http://localhost:5000/api/' . $endpoint;
    // ... (IMPLEMENTATION_GUIDE.md 참조)
}
```

## 참조 문서

- [IMPLEMENTATION_GUIDE.md](../IMPLEMENTATION_GUIDE.md) - 구현 가이드
- [quantum-orchestration-design.md](../quantum-orchestration-design.md) - 시스템 설계

