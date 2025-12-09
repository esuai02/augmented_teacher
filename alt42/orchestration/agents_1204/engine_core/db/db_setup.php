<?php
/**
 * Engine Core - DB Setup Script
 *
 * 에이전트 시스템 핵심 테이블 생성 및 초기화
 * AgentErrorHandler 및 AgentDependencyManager에서 사용하는 테이블 생성
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore/DB
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 실행 방법:
 * - 브라우저: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/engine_core/db/db_setup.php
 * - CLI: php db_setup.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $CFG;
require_login();

// xmldb_table 클래스 로드
require_once($CFG->libdir . '/ddllib.php');

/**
 * 테이블 생성 결과 저장
 */
$results = [
    'success' => [],
    'skipped' => [],
    'errors' => []
];

/**
 * 1. at_agent_logs 테이블 생성
 * AgentErrorHandler.php에서 사용
 */
function createAgentLogsTable() {
    global $DB, $results;

    $tableName = 'at_agent_logs';
    $dbman = $DB->get_manager();

    try {
        // 테이블 존재 여부 확인
        $table = new xmldb_table($tableName);

        if ($dbman->table_exists($table)) {
            $results['skipped'][] = "$tableName (이미 존재)";
            return true;
        }

        // 테이블 정의
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('agent_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('severity', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'INFO');
        $table->add_field('message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('file_path', XMLDB_TYPE_CHAR, '255', null, null, null, null);
        $table->add_field('line_number', XMLDB_TYPE_INTEGER, '10', null, null, null, '0');
        $table->add_field('context_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // 기본 키
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // 인덱스
        $table->add_index('idx_agent_id', XMLDB_INDEX_NOTUNIQUE, ['agent_id']);
        $table->add_index('idx_severity', XMLDB_INDEX_NOTUNIQUE, ['severity']);
        $table->add_index('idx_created_at', XMLDB_INDEX_NOTUNIQUE, ['created_at']);

        // 테이블 생성
        $dbman->create_table($table);
        $results['success'][] = "$tableName (생성 완료)";
        return true;

    } catch (Exception $e) {
        $results['errors'][] = "$tableName: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
        return false;
    }
}

/**
 * 2. at_agent_execution_log 테이블 생성
 * AgentDependencyManager.php에서 사용
 */
function createAgentExecutionLogTable() {
    global $DB, $results;

    $tableName = 'at_agent_execution_log';
    $dbman = $DB->get_manager();

    try {
        // 테이블 존재 여부 확인
        $table = new xmldb_table($tableName);

        if ($dbman->table_exists($table)) {
            $results['skipped'][] = "$tableName (이미 존재)";
            return true;
        }

        // 테이블 정의
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('session_id', XMLDB_TYPE_CHAR, '64', null, XMLDB_NOTNULL, null, null);
        $table->add_field('student_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('agent_id', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('status', XMLDB_TYPE_CHAR, '20', null, XMLDB_NOTNULL, null, 'pending');
        $table->add_field('result_data', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('error_message', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('started_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('completed_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // 기본 키
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // 인덱스
        $table->add_index('idx_session_id', XMLDB_INDEX_NOTUNIQUE, ['session_id']);
        $table->add_index('idx_student_id', XMLDB_INDEX_NOTUNIQUE, ['student_id']);
        $table->add_index('idx_agent_id', XMLDB_INDEX_NOTUNIQUE, ['agent_id']);
        $table->add_index('idx_status', XMLDB_INDEX_NOTUNIQUE, ['status']);
        $table->add_index('idx_session_agent', XMLDB_INDEX_UNIQUE, ['session_id', 'agent_id']);

        // 테이블 생성
        $dbman->create_table($table);
        $results['success'][] = "$tableName (생성 완료)";
        return true;

    } catch (Exception $e) {
        $results['errors'][] = "$tableName: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
        return false;
    }
}

/**
 * 3. at_data_validation_cache 테이블 생성 (선택)
 * DataSourceValidator 캐시 최적화용
 */
function createDataValidationCacheTable() {
    global $DB, $results;

    $tableName = 'at_data_validation_cache';
    $dbman = $DB->get_manager();

    try {
        $table = new xmldb_table($tableName);

        if ($dbman->table_exists($table)) {
            $results['skipped'][] = "$tableName (이미 존재)";
            return true;
        }

        // 테이블 정의
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('cache_key', XMLDB_TYPE_CHAR, '128', null, XMLDB_NOTNULL, null, null);
        $table->add_field('table_name', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('validation_result', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('expires_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        // 기본 키
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // 인덱스
        $table->add_index('idx_cache_key', XMLDB_INDEX_UNIQUE, ['cache_key']);
        $table->add_index('idx_expires_at', XMLDB_INDEX_NOTUNIQUE, ['expires_at']);

        $dbman->create_table($table);
        $results['success'][] = "$tableName (생성 완료)";
        return true;

    } catch (Exception $e) {
        $results['errors'][] = "$tableName: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]";
        return false;
    }
}

// 메인 실행
echo "<pre>\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "🔧 Engine Core DB Setup\n";
echo "═══════════════════════════════════════════════════════════════════════════\n\n";

// 테이블 생성 실행
createAgentLogsTable();
createAgentExecutionLogTable();
createDataValidationCacheTable();

// 결과 출력
echo "【생성 완료】\n";
foreach ($results['success'] as $item) {
    echo "  ✅ $item\n";
}

echo "\n【건너뜀】\n";
foreach ($results['skipped'] as $item) {
    echo "  ⏭️ $item\n";
}

if (!empty($results['errors'])) {
    echo "\n【오류】\n";
    foreach ($results['errors'] as $item) {
        echo "  ❌ $item\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════════════════\n";
echo "완료 시간: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════════════════\n";
echo "</pre>\n";

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 생성 테이블:
 *
 * 1. mdl_at_agent_logs
 *    - id (int): PK
 *    - agent_id (varchar 50): 에이전트 ID (Agent01~Agent22)
 *    - severity (varchar 20): DEBUG, INFO, WARNING, ERROR, CRITICAL
 *    - message (text): 에러/로그 메시지
 *    - file_path (varchar 255): 파일 경로
 *    - line_number (int): 라인 번호
 *    - context_data (text): JSON 형식 추가 데이터
 *    - created_at (int): 생성 시간 (timestamp)
 *
 * 2. mdl_at_agent_execution_log
 *    - id (int): PK
 *    - session_id (varchar 64): 실행 세션 ID
 *    - student_id (int): 학생 ID
 *    - agent_id (int): 에이전트 번호 (1~22)
 *    - status (varchar 20): pending, running, completed, failed, skipped
 *    - result_data (text): JSON 형식 실행 결과
 *    - error_message (text): 에러 메시지
 *    - started_at (int): 시작 시간
 *    - completed_at (int): 완료 시간
 *    - created_at (int): 생성 시간
 *
 * 3. mdl_at_data_validation_cache
 *    - id (int): PK
 *    - cache_key (varchar 128): 캐시 키
 *    - table_name (varchar 100): 테이블명
 *    - validation_result (text): JSON 형식 검증 결과
 *    - expires_at (int): 만료 시간
 *    - created_at (int): 생성 시간
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
