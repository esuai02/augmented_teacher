# Heartbeat Scheduler 마이그레이션 계획

**작성일**: 2025-01-27  
**상태**: 계획 단계

---

## 📋 상황 분석

### 현재 상태
- ✅ `orchestrationk/api/scheduler/heartbeat.php` - 구현 완료
- ✅ `orchestrationk` 폴더에 모든 의존성 파일 존재
- ❌ `orchestration` 폴더에는 heartbeat scheduler 없음
- ❌ `orchestration` 폴더에는 `api` 폴더 구조 없음

### 폴더 구조 차이

**orchestrationk 구조:**
```
orchestrationk/
├── api/
│   ├── scheduler/
│   │   └── heartbeat.php
│   ├── events/
│   ├── database/
│   ├── mapping/
│   ├── oa/
│   ├── config/
│   └── rule_engine/
└── db/
    └── migrations/
```

**orchestration 구조:**
```
orchestration/
├── mvp_system/
│   ├── api/
│   ├── lib/
│   ├── database/
│   └── ...
├── database/
│   └── migrations/
└── db/
```

---

## 🎯 마이그레이션 전략

### 옵션 1: `orchestration/api` 폴더 생성 (권장)
- `orchestrationk`와 동일한 구조 유지
- 기존 코드 재사용 가능
- 경로: `orchestration/api/scheduler/heartbeat.php`

### 옵션 2: `mvp_system` 내부에 배치
- `mvp_system/api/scheduler/heartbeat.php`
- 기존 mvp_system 구조와 통합

### 옵션 3: 루트에 `api` 폴더 생성
- `orchestration/api/scheduler/heartbeat.php`
- 가장 간단한 구조

---

## ✅ 권장 사항

**옵션 1 (orchestration/api 폴더 생성)**을 권장합니다.

**이유:**
1. `orchestrationk`와 구조 일치
2. 기존 코드 그대로 사용 가능
3. 의존성 파일들도 동일한 구조로 배치 가능
4. 향후 통합 시 편리

---

## 📝 마이그레이션 체크리스트

- [ ] `orchestration/api` 폴더 구조 생성
- [ ] `orchestrationk`의 의존성 파일들 확인
- [ ] heartbeat.php 및 의존성 파일 복사/이동
- [ ] 경로 수정 (필요시)
- [ ] 데이터베이스 마이그레이션 파일 이동
- [ ] 테스트 실행

---

**다음 단계**: 사용자 확인 후 진행

