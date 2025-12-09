# 테스트 파일 백업 디렉터리

이 디렉터리에는 시험 대비 에이전트 시스템 개발 과정에서 사용된 테스트 및 디버깅 파일들이 저장되어 있습니다.
메인 시스템 운영에는 필요하지 않지만, 개발 및 문제 해결 시 참고용으로 보관됩니다.

## 백업된 파일 목록

### 테이블 구조 확인 파일
- `check_exam_table.php` - alt42t_exams 테이블 구조 확인 도구
- `check_saved_data.php` - 저장된 데이터 확인 도구
- `check_table_constraints.php` - 테이블 제약조건 확인 도구
- `check_table_structure.php` - 테이블 구조 확인 도구
- `check_user_fields.php` - 사용자 필드 확인 도구

### 디버깅 파일
- `debug_db_connection.php` - 데이터베이스 연결 디버깅 도구
- `debug_save_error.php` - 저장 오류 디버깅 도구
- `display_mathking_info.php` - MathKing DB 정보 표시 도구

### 테스트 파일
- `test_db_write.php` - 데이터베이스 쓰기 테스트
- `test_exam_system.php` - 시험 시스템 테스트
- `test_mathking_fields.php` - MathKing 필드 테스트
- `test_save.php` - 저장 기능 테스트
- `test_save_directly.php` - 직접 저장 테스트
- `test_update_fix.php` - 업데이트 수정 테스트

### 데이터 관리 파일
- `save_birth_year.php` - 출생년도 저장 도구

## 사용 방법

필요시 해당 파일을 메인 디렉터리로 복사하여 사용할 수 있습니다.

```bash
cp test_files_backup/[파일명] ./
```

## 주의사항

이 파일들은 개발 및 디버깅 목적으로만 사용되며, 프로덕션 환경에서는 사용하지 않는 것을 권장합니다.