# 수학 인지관성 도감 최종 해결 가이드

## 문제 요약
1. 문자셋 문제: 이모지를 저장할 수 없음 (utf8 vs utf8mb4)
2. DateTime 문제: created_at 필드 타입 불일치
3. 테이블 누락: alt42i_audio_files 테이블이 없음

## 해결 방법

### 즉시 사용 가능한 솔루션

#### 1. 최소 버전 데이터 삽입 (권장)
https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/insert_60_personas_minimal.php

**특징:**
- audio_files 테이블 없이도 작동
- 이모지를 텍스트로 저장 (문자셋 문제 해결)
- created_at 필드 사용하지 않음 (DateTime 문제 해결)
- API가 오디오 URL을 자동 생성

#### 2. 테이블 생성 (선택사항)
https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/create_missing_tables.php

누락된 테이블을 생성하려면 이 스크립트 실행

#### 3. 상태 확인
https://mathking.kr/moodle/local/augmented_teacher/alt42/shiningstars/check_db_status.php

데이터가 제대로 삽입되었는지 확인

## 시스템 구성

### 필수 테이블
- `alt42i_pattern_categories` - 카테고리 정보
- `alt42i_math_patterns` - 패턴 데이터
- `alt42i_pattern_solutions` - 솔루션 정보

### 선택 테이블
- `alt42i_audio_files` - 오디오 파일 정보 (없어도 작동)
- `alt42i_user_pattern_progress` - 사용자 진행상황

### API 구성
- `/api/get_math_patterns.php` - audio_files 테이블 없이도 작동
- 오디오 URL은 pattern_id를 기반으로 자동 생성

### 프론트엔드
- `MathPersonaSystem.js` - 텍스트 아이콘을 이모지로 자동 변환
- 아이콘 매핑: brain→🧠, anxious→😰, error→❌ 등

## 사용 순서

1. **데이터 삽입**
   - insert_60_personas_minimal.php 접속
   - "테스트 데이터 삽입" 버튼 클릭
   - 3개 패턴이 성공적으로 삽입되는지 확인

2. **전체 데이터 요청**
   - 테스트가 성공하면 60개 전체 데이터 버전 요청

3. **메인 페이지 테스트**
   - index.php 접속
   - "📚 수학 인지관성 도감" 버튼 클릭
   - 카드들이 정상적으로 표시되는지 확인

## 문제 해결

- **카드가 안 보임**: 데이터가 삽입되지 않았음 → insert_60_personas_minimal.php 실행
- **이모지가 깨짐**: 프론트엔드 아이콘 매핑 확인
- **오디오가 안 나옴**: URL 패턴 확인 (thinkinginertia01.mp3 ~ 60.mp3)