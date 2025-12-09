# 수학킹 시험 대비 시스템 (기존 DB 사용)

## 개요
이 시스템은 MathKing Moodle 데이터베이스의 기존 `mdl_alt42t_*` 테이블들을 사용하여 시험 정보를 관리합니다.

## 파일 구조
```
/omniui/
├── exam_system.php          # 메인 시험 설정 시스템
├── dashboard.php            # 기존 대시보드 (이미 존재)
├── get_exam_data_alt42t.php # 기존 파일 (시험 데이터 조회)
├── get_user_lms_data.php    # 기존 파일 (LMS 사용자 데이터)
└── index.php               # 기존 파일 (로그인 후 첫 페이지)
```

## 사용 방법

### 1. 접속 방법
```
https://your-domain.com/index.php → exam_system.php
```

### 2. 시스템 흐름
1. **Moodle 로그인** → index.php로 이동
2. **시험 설정** → exam_system.php 클릭
3. **5단계 설정**:
   - 정보입력 (자동 채워짐)
   - 시험설정
   - 전략이해
   - 단계선택
   - 시작하기
4. **대시보드** → dashboard.php로 이동

### 3. 데이터베이스 구조 (기존 테이블 사용)

#### mdl_alt42t_users
- userid: Moodle 사용자 ID
- school_name: 학교명
- grade: 학년 (예: '고3', '중2')

#### mdl_alt42t_exams
- exam_id: 시험 ID
- school_name: 학교명
- grade: 학년
- exam_type: 시험 종류 (예: '1학기 중간고사')

#### mdl_alt42t_exam_dates
- exam_id: 시험 ID 참조
- user_id: mdl_alt42t_users.id 참조
- start_date: 시험 시작일
- end_date: 시험 종료일
- math_date: 수학 시험일
- status: '예상' 또는 '확정'

#### mdl_alt42t_exam_resources
- exam_id: 시험 ID 참조
- user_id: 사용자 ID
- tip_text: 시험 범위 (예: '시험 범위: 삼각함수, 지수로그')

#### mdl_alt42t_study_status
- user_id: 사용자 ID
- exam_id: 시험 ID
- status: 학습 단계 ('concept', 'review', 'practice')

## 주요 기능

### 자동 데이터 로드
- **이름**: Moodle mdl_user 테이블에서 가져옴
- **학교**: mdl_user_info_data (fieldid=88)에서 가져옴
- **학년**: 출생년도(fieldid=89)로 자동 계산
- **학기**: 현재 날짜 기준 자동 설정
- **시험 종류**: 현재 날짜로 자동 추천

### 학년 계산 (2025년 기준)
- 2007년생 → 고3
- 2008년생 → 고2
- 2009년생 → 고1
- 2010년생 → 중3
- 2011년생 → 중2
- 2012년생 → 중1

### 시험 종류 자동 판단
- 12/11 ~ 5/1: 1학기 중간고사
- 5/2 ~ 6/30: 1학기 기말고사
- 7/1 ~ 9/30: 2학기 중간고사
- 10/1 ~ 12/10: 2학기 기말고사

### 같은 학교 시험 정보 공유
- 같은 학교, 같은 학년 학생들의 시험 정보 참조 가능
- 클릭하면 자동으로 입력됨

## 코드 수정 없이 사용하기

exam_system.php는 기존 시스템과 완전히 호환되도록 만들어졌습니다:

1. Moodle config.php include
2. require_login() 사용
3. 기존 데이터베이스 테이블 구조 그대로 사용
4. 기존 helper 함수들 (get_exam_data_alt42t.php, get_user_lms_data.php) 활용

## 문제 해결

### 데이터가 나타나지 않는 경우
1. mdl_user_info_data에 학교(fieldid=88), 출생년도(fieldid=89) 확인
2. 사용자가 로그인되어 있는지 확인

### 저장이 안 되는 경우
1. mdl_alt42t_* 테이블들이 존재하는지 확인
2. 데이터베이스 권한 확인

### 학년이 잘못 표시되는 경우
1. mdl_user_info_data의 출생년도(fieldid=89) 확인
2. 현재 년도가 2025년 기준인지 확인