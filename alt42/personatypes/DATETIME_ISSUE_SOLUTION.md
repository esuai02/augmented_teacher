# DateTime 문제 해결 가이드

## 문제
- 오류: "Incorrect datetime value: '1754364087' for column 'created_at'"
- 원인: created_at 필드가 DATETIME 타입인데 Unix timestamp(정수)를 전달

## 해결 방법

### 1. 테스트 버전 사용 (즉시 사용 가능)
https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/insert_60_personas_final.php

**특징:**
- created_at/updated_at 필드를 사용하지 않음
- 이모지를 텍스트로 저장 (문자셋 문제도 해결)
- 처음 3개 패턴만 테스트로 삽입

### 2. 스키마 확인
https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/check_table_schema.php

테이블 구조를 확인하여 어떤 필드가 있는지 파악

### 3. 전체 60개 데이터 삽입
테스트가 성공하면 60personas.txt의 전체 내용을 포함한 완전한 버전 생성 필요

## 현재 상황

1. **문자셋 문제**: 이모지를 텍스트로 변환하여 해결
2. **DateTime 문제**: created_at 필드 제거로 해결
3. **프론트엔드**: 텍스트를 이모지로 자동 변환

## 다음 단계

1. insert_60_personas_final.php 실행하여 3개 테스트
2. 성공하면 전체 60개 데이터 버전 요청
3. 메인 페이지에서 수학 인지관성 도감 확인