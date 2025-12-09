# 수학킹 시험 대비 시스템 (Exam Preparation System)

## 개요
이 시스템은 학생들이 시험을 체계적으로 준비할 수 있도록 도와주는 5단계 프로세스를 제공합니다.

## 시스템 구성
1. **정보입력**: 학생 기본 정보 입력 (이름, 학교, 학년, 학기)
2. **시험설정**: 시험 일정 및 범위 설정
3. **전략이해**: 라스트 청킹 학습 전략 소개
4. **단계선택**: 학습 시작 단계 선택 (개념공부, 개념복습, 유형공부)
5. **시작하기**: 대시보드로 이동

## 설치 방법

### 1. 데이터베이스 설정
먼저 `study_level` 컬럼을 추가해야 합니다:
```sql
mysql -h 58.180.27.46 -u moodle -p mathking < add_study_level_column.sql
```

### 2. 파일 설치
- `exam_preparation_system.php` - 메인 시스템 파일
- `save_exam_data_alt42t.php` - 데이터 저장 처리 (기존 파일 업데이트됨)

### 3. 접속 방법
```
http://your-domain/exam_preparation_system.php
```

## 주요 기능

### 자동 정보 입력
- mathking DB에서 학생 정보 자동 가져오기
- 출생년도 기반 학년 자동 계산
- 현재 날짜 기반 학기 자동 설정

### 시험 정보 공유
- 같은 학교 학생들이 입력한 시험 정보 공유
- 클릭으로 간편하게 시험 정보 입력

### 학교 홈페이지 연동
- 구글 검색을 통한 학교 홈페이지 바로가기

### 대시보드 통합
- 기존 dashboard.php와 완벽 호환
- 학습 단계 정보 전달

## 데이터베이스 구조

### mdl_alt42t_users
- userid: Moodle 사용자 ID
- name: 학생 이름
- school_name: 학교명
- grade: 학년 (숫자)

### mdl_alt42t_exams
- school_name: 학교명
- grade: 학년
- exam_type: 시험 종류

### mdl_alt42t_exam_dates
- exam_id: 시험 ID
- user_id: alt42t 사용자 ID
- start_date: 시험 시작일
- end_date: 시험 종료일
- math_exam_date: 수학 시험일
- status: 예상/확정

### mdl_alt42t_exam_resources
- exam_id: 시험 ID
- user_id: alt42t 사용자 ID
- tip_text: 시험 범위

### mdl_alt42t_study_status
- user_id: alt42t 사용자 ID
- exam_id: 시험 ID
- study_level: 학습 단계 (concept/review/practice)

## 주의사항
1. Moodle 로그인이 필요합니다 (require_login())
2. 모든 날짜는 YYYY-MM-DD 형식으로 저장됩니다
3. 학년은 문자열로 입력받지만 숫자로 저장됩니다

## 문제 해결
- 로그인 오류: Moodle 세션이 유효한지 확인
- 데이터 저장 오류: PHP 에러 로그 확인
- study_level 저장 오류: add_study_level_column.sql 실행 여부 확인