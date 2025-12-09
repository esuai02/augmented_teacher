# 데이터베이스 쓰기 오류 해결

## 문제 원인
"데이터베이스 쓰기 오류"는 `save_exam_data_alt42t.php`에서 UPDATE 쿼리가 잘못된 WHERE 조건을 사용했기 때문입니다.

## 해결 방법
1. UPDATE 쿼리의 WHERE 조건을 `id`에서 `userid`로 변경
   - 기존: `WHERE id = :id`
   - 수정: `WHERE userid = :userid`

2. alt42t_users 테이블의 실제 구조:
   - `id`: 자동 증가 primary key
   - `userid`: Moodle 사용자 ID (foreign key)
   - UPDATE 시 userid로 사용자를 찾아야 함

## 수정된 파일
- `save_exam_data_alt42t.php`: 라인 134의 UPDATE 쿼리 수정

## 테스트 방법
1. test_update_fix.php 실행하여 UPDATE 쿼리 동작 확인
2. exam_preparation_system.php에서 정보 수정 후 저장
3. check_saved_data.php에서 저장된 데이터 확인

## 추가 개선사항
- 에러 핸들링 추가 (try-catch 블록)
- 더 구체적인 오류 메시지 표시
- 디버깅을 위한 로그 추가