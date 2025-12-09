<?php
/**
 * CLI용 데이터베이스 테이블 구조 조정 스크립트
 * 사용법: php cli_adjust_db_structure.php
 */

define('CLI_SCRIPT', true);
require_once(__DIR__ . '/../../../../../../config.php');

// CLI 환경 확인
if (php_sapi_name() !== 'cli') {
    die("이 스크립트는 CLI 환경에서만 실행할 수 있습니다.\n");
}

echo "데이터베이스 테이블 구조 조정 시작...\n";
echo "======================================\n\n";

// 현재 테이블 구조 백업을 위한 SQL 생성
echo "1. 현재 테이블 구조 백업 SQL 생성\n";
echo "-------------------------------------\n";

// mdl_alt42i_math_patterns 백업
$backup_sql = "-- 백업 테이블 생성\n";
$backup_sql .= "CREATE TABLE mdl_alt42i_math_patterns_backup AS SELECT * FROM mdl_alt42i_math_patterns;\n";
$backup_sql .= "CREATE TABLE mdl_alt42i_pattern_solutions_backup AS SELECT * FROM mdl_alt42i_pattern_solutions;\n\n";

file_put_contents('backup_tables.sql', $backup_sql);
echo "✓ backup_tables.sql 파일이 생성되었습니다.\n\n";

// 테이블 구조 변경 SQL 생성
echo "2. 테이블 구조 변경 SQL 생성\n";
echo "-------------------------------------\n";

$alter_sql = "-- mdl_alt42i_math_patterns 테이블 수정\n";
$alter_sql .= "-- pattern_name을 name으로 변경\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_math_patterns CHANGE COLUMN pattern_name name VARCHAR(100) NOT NULL;\n\n";

$alter_sql .= "-- pattern_desc를 description으로 변경\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_math_patterns CHANGE COLUMN pattern_desc description TEXT NOT NULL;\n\n";

$alter_sql .= "-- pattern_id 데이터를 id로 복사 (필요한 경우)\n";
$alter_sql .= "-- UPDATE mdl_alt42i_math_patterns SET id = pattern_id WHERE id != pattern_id;\n\n";

$alter_sql .= "-- pattern_id 관련 제약조건 제거\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_math_patterns DROP INDEX uk_pattern_id;\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_math_patterns DROP COLUMN pattern_id;\n\n";

$alter_sql .= "-- is_active 컬럼 제거\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_math_patterns DROP COLUMN is_active;\n\n";

$alter_sql .= "-- mdl_alt42i_pattern_solutions 테이블 수정\n";
$alter_sql .= "-- check_method 필드 추가 (없는 경우)\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_pattern_solutions ADD COLUMN check_method TEXT NOT NULL AFTER action;\n\n";

$alter_sql .= "-- 불필요한 필드 제거\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_pattern_solutions DROP COLUMN IF EXISTS example_problem;\n";
$alter_sql .= "ALTER TABLE mdl_alt42i_pattern_solutions DROP COLUMN IF EXISTS practice_guide;\n\n";

file_put_contents('alter_tables.sql', $alter_sql);
echo "✓ alter_tables.sql 파일이 생성되었습니다.\n\n";

// 안전한 마이그레이션을 위한 PHP 스크립트 생성
echo "3. 안전한 마이그레이션 스크립트 생성\n";
echo "-------------------------------------\n";

$migration_php = '<?php
/**
 * 안전한 데이터베이스 마이그레이션 스크립트
 */
require_once(__DIR__ . \'/../../../../../../config.php\');

// 트랜잭션 시작
$transaction = $DB->start_delegated_transaction();

try {
    // 1. 백업 테이블 생성
    echo "백업 테이블 생성 중...\n";
    $DB->execute("CREATE TABLE IF NOT EXISTS {alt42i_math_patterns_backup} AS SELECT * FROM {alt42i_math_patterns}");
    $DB->execute("CREATE TABLE IF NOT EXISTS {alt42i_pattern_solutions_backup} AS SELECT * FROM {alt42i_pattern_solutions}");
    
    // 2. mdl_alt42i_math_patterns 수정
    echo "mdl_alt42i_math_patterns 테이블 수정 중...\n";
    
    // pattern_id 값을 id로 복사 (AUTO_INCREMENT 해제 필요)
    $DB->execute("ALTER TABLE {alt42i_math_patterns} MODIFY id INT(11) NOT NULL");
    $DB->execute("UPDATE {alt42i_math_patterns} SET id = pattern_id WHERE pattern_id IS NOT NULL");
    
    // 필드명 변경
    $DB->execute("ALTER TABLE {alt42i_math_patterns} CHANGE pattern_name name VARCHAR(100) NOT NULL");
    $DB->execute("ALTER TABLE {alt42i_math_patterns} CHANGE pattern_desc description TEXT NOT NULL");
    
    // 불필요한 필드 제거
    $DB->execute("ALTER TABLE {alt42i_math_patterns} DROP INDEX IF EXISTS uk_pattern_id");
    $DB->execute("ALTER TABLE {alt42i_math_patterns} DROP COLUMN pattern_id");
    $DB->execute("ALTER TABLE {alt42i_math_patterns} DROP COLUMN IF EXISTS is_active");
    
    // id를 PRIMARY KEY로 재설정
    $DB->execute("ALTER TABLE {alt42i_math_patterns} MODIFY id INT(11) NOT NULL PRIMARY KEY");
    
    // 3. mdl_alt42i_pattern_solutions 수정
    echo "mdl_alt42i_pattern_solutions 테이블 수정 중...\n";
    
    // check_method 필드 추가
    $columns = $DB->get_columns(\'mdl_alt42i_pattern_solutions\');
    if (!isset($columns[\'check_method\'])) {
        $DB->execute("ALTER TABLE {alt42i_pattern_solutions} ADD COLUMN check_method TEXT NOT NULL AFTER action");
    }
    
    // 불필요한 필드 제거
    $DB->execute("ALTER TABLE {alt42i_pattern_solutions} DROP COLUMN IF EXISTS example_problem");
    $DB->execute("ALTER TABLE {alt42i_pattern_solutions} DROP COLUMN IF EXISTS practice_guide");
    
    // 트랜잭션 커밋
    $transaction->allow_commit();
    echo "✓ 마이그레이션이 성공적으로 완료되었습니다.\n";
    
} catch (Exception $e) {
    $transaction->rollback($e);
    echo "❌ 오류 발생: " . $e->getMessage() . "\n";
    echo "변경사항이 롤백되었습니다.\n";
}
';

file_put_contents('safe_migration.php', $migration_php);
echo "✓ safe_migration.php 파일이 생성되었습니다.\n\n";

// 현재 테이블 구조 출력
echo "4. 현재 테이블 구조\n";
echo "-------------------------------------\n";

// mdl_alt42i_math_patterns
$columns = $DB->get_columns('mdl_alt42i_math_patterns');
echo "\nmdl_alt42i_math_patterns 테이블:\n";
foreach ($columns as $column) {
    echo "  - {$column->name} ({$column->type}";
    if ($column->max_length) echo ", {$column->max_length}";
    if (!$column->not_null) echo ", NULL 가능";
    echo ")\n";
}

// mdl_alt42i_pattern_solutions
$columns = $DB->get_columns('mdl_alt42i_pattern_solutions');
echo "\nmdl_alt42i_pattern_solutions 테이블:\n";
foreach ($columns as $column) {
    echo "  - {$column->name} ({$column->type}";
    if ($column->max_length) echo ", {$column->max_length}";
    if (!$column->not_null) echo ", NULL 가능";
    echo ")\n";
}

echo "\n======================================\n";
echo "완료!\n\n";
echo "다음 단계:\n";
echo "1. backup_tables.sql을 실행하여 백업 생성\n";
echo "2. alter_tables.sql을 검토하고 필요시 수정\n";
echo "3. safe_migration.php를 실행하여 안전하게 마이그레이션\n";
echo "\n또는 alter_tables.sql을 직접 데이터베이스에서 실행할 수 있습니다.\n";