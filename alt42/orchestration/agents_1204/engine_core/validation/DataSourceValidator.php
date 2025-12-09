<?php
/**
 * DataSourceValidator.php
 *
 * 데이터 소스 검증 모듈 - 모든 에이전트에서 공통 사용
 * dataSources 배열의 DB 테이블/필드 존재 여부 및 NULL 값 검증
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore/Validation
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents_1204/engine_core/validation/DataSourceValidator.php
 */

defined('MOODLE_INTERNAL') || die();

class DataSourceValidator {

    /**
     * @var moodle_database
     */
    private $db;

    /**
     * @var array 검증 결과 캐시
     */
    private static $tableCache = [];

    /**
     * @var array 검증 실패 로그
     */
    private $validationErrors = [];

    /**
     * 생성자
     */
    public function __construct() {
        global $DB;
        $this->db = $DB;
    }

    /**
     * 데이터 소스 배열 검증
     *
     * @param array $dataSources 검증할 데이터 소스 배열
     *        예: [
     *            ['table' => 'alt42_student_activity', 'fields' => ['userid', 'main_category']],
     *            ['table' => 'quiz_results', 'fields' => ['score', 'timefinish']]
     *        ]
     * @param int $studentId 학생 ID (데이터 존재 확인용)
     * @param string $agentId 에이전트 ID (로깅용)
     * @return array 검증 결과 ['success' => bool, 'missing' => array, 'warnings' => array]
     */
    public function validateDataSources(array $dataSources, int $studentId, string $agentId = ''): array {
        $this->validationErrors = [];
        $result = [
            'success' => true,
            'missing' => [],
            'warnings' => [],
            'validated' => []
        ];

        foreach ($dataSources as $source) {
            $tableResult = $this->validateTable($source, $studentId);

            if (!$tableResult['exists']) {
                $result['success'] = false;
                $result['missing'][] = [
                    'table' => $source['table'],
                    'reason' => 'table_not_found'
                ];
                $this->logError($agentId, 'TABLE_NOT_FOUND', $source['table']);
            } elseif (!empty($tableResult['missing_fields'])) {
                $result['success'] = false;
                $result['missing'][] = [
                    'table' => $source['table'],
                    'fields' => $tableResult['missing_fields'],
                    'reason' => 'fields_not_found'
                ];
                $this->logError($agentId, 'FIELDS_NOT_FOUND', $source['table'] . ':' . implode(',', $tableResult['missing_fields']));
            } elseif ($tableResult['has_null_data']) {
                $result['warnings'][] = [
                    'table' => $source['table'],
                    'null_fields' => $tableResult['null_fields'],
                    'reason' => 'null_values_found'
                ];
            } else {
                $result['validated'][] = $source['table'];
            }
        }

        return $result;
    }

    /**
     * 단일 테이블 검증
     *
     * @param array $source 테이블 정보 ['table' => string, 'fields' => array]
     * @param int $studentId 학생 ID
     * @return array 검증 결과
     */
    private function validateTable(array $source, int $studentId): array {
        $result = [
            'exists' => false,
            'missing_fields' => [],
            'has_null_data' => false,
            'null_fields' => []
        ];

        $tableName = $source['table'];
        $fields = $source['fields'] ?? [];

        // 1. 테이블 존재 확인 (캐시 활용)
        if (!$this->tableExists($tableName)) {
            return $result;
        }
        $result['exists'] = true;

        // 2. 필드 존재 확인
        if (!empty($fields)) {
            $columns = $this->getTableColumns($tableName);
            foreach ($fields as $field) {
                if (!in_array($field, $columns)) {
                    $result['missing_fields'][] = $field;
                }
            }
        }

        // 필드가 누락되면 데이터 검증 스킵
        if (!empty($result['missing_fields'])) {
            return $result;
        }

        // 3. 학생 데이터 NULL 값 확인 (studentId가 있는 경우)
        if ($studentId > 0 && !empty($fields)) {
            $nullFields = $this->checkNullValues($tableName, $fields, $studentId);
            if (!empty($nullFields)) {
                $result['has_null_data'] = true;
                $result['null_fields'] = $nullFields;
            }
        }

        return $result;
    }

    /**
     * 테이블 존재 여부 확인 (캐시 사용)
     *
     * @param string $tableName 테이블명 (mdl_ 접두사 제외)
     * @return bool
     */
    public function tableExists(string $tableName): bool {
        // 캐시 확인
        if (isset(self::$tableCache[$tableName])) {
            return self::$tableCache[$tableName];
        }

        try {
            $dbman = $this->db->get_manager();
            $exists = $dbman->table_exists(new xmldb_table($tableName));
            self::$tableCache[$tableName] = $exists;
            return $exists;
        } catch (Exception $e) {
            error_log("[DataSourceValidator] tableExists error for '$tableName': " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return false;
        }
    }

    /**
     * 테이블 컬럼 목록 조회
     *
     * @param string $tableName 테이블명
     * @return array 컬럼명 배열
     */
    private function getTableColumns(string $tableName): array {
        $cacheKey = $tableName . '_columns';
        if (isset(self::$tableCache[$cacheKey])) {
            return self::$tableCache[$cacheKey];
        }

        try {
            $columns = $this->db->get_columns($tableName);
            $columnNames = array_keys($columns);
            self::$tableCache[$cacheKey] = $columnNames;
            return $columnNames;
        } catch (Exception $e) {
            error_log("[DataSourceValidator] getTableColumns error for '$tableName': " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            return [];
        }
    }

    /**
     * NULL 값 존재 확인
     *
     * @param string $tableName 테이블명
     * @param array $fields 확인할 필드 목록
     * @param int $studentId 학생 ID
     * @return array NULL 값이 있는 필드 목록
     */
    private function checkNullValues(string $tableName, array $fields, int $studentId): array {
        $nullFields = [];

        try {
            // userid 필드가 있는지 확인
            $columns = $this->getTableColumns($tableName);
            $userIdField = in_array('userid', $columns) ? 'userid' :
                          (in_array('user_id', $columns) ? 'user_id' :
                          (in_array('studentid', $columns) ? 'studentid' : null));

            if (!$userIdField) {
                return []; // 사용자 ID 필드 없으면 스킵
            }

            // 학생 데이터 조회
            $record = $this->db->get_record_sql(
                "SELECT * FROM {{$tableName}} WHERE {$userIdField} = ? ORDER BY id DESC LIMIT 1",
                [$studentId]
            );

            if ($record) {
                foreach ($fields as $field) {
                    if (property_exists($record, $field) && $record->$field === null) {
                        $nullFields[] = $field;
                    }
                }
            }
        } catch (Exception $e) {
            error_log("[DataSourceValidator] checkNullValues error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }

        return $nullFields;
    }

    /**
     * 에러 로깅
     *
     * @param string $agentId 에이전트 ID
     * @param string $errorType 에러 유형
     * @param string $details 세부 정보
     */
    private function logError(string $agentId, string $errorType, string $details): void {
        $errorLog = [
            'timestamp' => date('Y-m-d H:i:s'),
            'agent_id' => $agentId,
            'error_type' => $errorType,
            'details' => $details
        ];

        $this->validationErrors[] = $errorLog;
        error_log("[DataSourceValidator][{$agentId}] {$errorType}: {$details} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    }

    /**
     * 검증 에러 목록 반환
     *
     * @return array
     */
    public function getValidationErrors(): array {
        return $this->validationErrors;
    }

    /**
     * 캐시 초기화
     */
    public static function clearCache(): void {
        self::$tableCache = [];
    }

    /**
     * 빠른 테이블 검증 (존재 여부만 확인)
     *
     * @param string|array $tables 테이블명 또는 테이블 배열
     * @return array ['all_exist' => bool, 'missing' => array]
     */
    public function quickValidate($tables): array {
        $tables = is_array($tables) ? $tables : [$tables];
        $missing = [];

        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                $missing[] = $table;
            }
        }

        return [
            'all_exist' => empty($missing),
            'missing' => $missing
        ];
    }
}

/**
 * 헬퍼 함수: 간단한 데이터 소스 검증
 *
 * @param array $dataSources 데이터 소스 배열
 * @param int $studentId 학생 ID
 * @param string $agentId 에이전트 ID
 * @return array 검증 결과
 */
function validate_data_sources(array $dataSources, int $studentId, string $agentId = ''): array {
    $validator = new DataSourceValidator();
    return $validator->validateDataSources($dataSources, $studentId, $agentId);
}

/**
 * 헬퍼 함수: 테이블 존재 여부 확인
 *
 * @param string $tableName 테이블명
 * @return bool
 */
function table_exists_safe(string $tableName): bool {
    $validator = new DataSourceValidator();
    return $validator->tableExists($tableName);
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * // 에이전트에서 데이터 검증 사용 예시
 * require_once(__DIR__ . '/../engine_core/validation/DataSourceValidator.php');
 *
 * $dataSources = [
 *     ['table' => 'alt42_student_activity', 'fields' => ['userid', 'main_category', 'created_at']],
 *     ['table' => 'quiz_attempts', 'fields' => ['userid', 'quiz', 'sumgrades']]
 * ];
 *
 * $result = validate_data_sources($dataSources, $USER->id, 'Agent04');
 *
 * if (!$result['success']) {
 *     // 누락된 테이블/필드 처리
 *     foreach ($result['missing'] as $missing) {
 *         error_log("Missing: " . $missing['table'] . " - " . $missing['reason']);
 *     }
 *     return ['error' => 'Data validation failed', 'details' => $result['missing']];
 * }
 *
 * // 경고 처리 (NULL 값)
 * if (!empty($result['warnings'])) {
 *     foreach ($result['warnings'] as $warning) {
 *         // NULL 값에 대한 기본값 처리 로직
 *     }
 * }
 *
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 관련 정보
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 이 모듈은 다음 Moodle DB API를 사용합니다:
 * - $DB->get_manager()->table_exists(): 테이블 존재 확인
 * - $DB->get_columns(): 테이블 컬럼 정보 조회
 * - $DB->get_record_sql(): 데이터 조회
 *
 * 호환: MySQL 5.7+, Moodle 3.7+
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
