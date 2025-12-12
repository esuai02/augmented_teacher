<?php
/**
 * DataValidator.php
 *
 * 에이전트 데이터 소스 검증 클래스
 * 모든 에이전트에서 사용하는 공통 데이터 검증 로직
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-09
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/validation/DataValidator.php
 *
 * 사용 예시:
 * ```php
 * $validator = new DataValidator($DB, $student_id);
 * $validation = $validator->validateDataSources([
 *     ['table' => 'mdl_user', 'fields' => ['id', 'username', 'email']],
 *     ['table' => 'mdl_quiz_attempts', 'fields' => ['quiz', 'attempt', 'state']]
 * ]);
 *
 * if (!$validation['valid']) {
 *     // 검증 실패 처리
 *     foreach ($validation['missing'] as $issue) {
 *         error_log("Missing: " . $issue['message']);
 *     }
 * }
 * ```
 *
 * 관련 DB 테이블:
 * - 모든 moodle 테이블 (mdl_*)
 * - 커스텀 테이블 (mdl_alt42_*)
 */

defined('MOODLE_INTERNAL') || die();

class DataValidator
{
    /** @var moodle_database Moodle 데이터베이스 객체 */
    private $db;

    /** @var int 검증 대상 학생 ID */
    private $student_id;

    /** @var array 검증 결과 캐시 */
    private $cache = [];

    /** @var string 에이전트 ID (로깅용) */
    private $agent_id;

    /**
     * DataValidator 생성자
     *
     * @param moodle_database $db Moodle 데이터베이스 객체
     * @param int $student_id 학생 ID
     * @param string $agent_id 에이전트 식별자 (기본값: 'unknown')
     */
    public function __construct($db, $student_id, $agent_id = 'unknown')
    {
        $this->db = $db;
        $this->student_id = $student_id;
        $this->agent_id = $agent_id;
    }

    /**
     * 데이터 소스 검증 실행
     *
     * @param array $dataSources 검증할 데이터 소스 배열
     *        [
     *            ['table' => 'mdl_user', 'fields' => ['id', 'username'], 'required' => true],
     *            ['table' => 'mdl_quiz_attempts', 'fields' => ['quiz', 'state'], 'required' => false]
     *        ]
     * @return array 검증 결과
     *         [
     *             'valid' => true|false,
     *             'checked' => 검증된 항목 수,
     *             'missing' => [ ['type' => 'table|field|data', 'message' => '...'] ],
     *             'warnings' => [ ['type' => 'null|empty', 'message' => '...'] ],
     *             'details' => [ ... 상세 검증 결과 ]
     *         ]
     */
    public function validateDataSources(array $dataSources): array
    {
        $result = [
            'valid' => true,
            'checked' => 0,
            'missing' => [],
            'warnings' => [],
            'details' => [],
            'timestamp' => date('Y-m-d H:i:s'),
            'agent_id' => $this->agent_id,
            'student_id' => $this->student_id
        ];

        foreach ($dataSources as $source) {
            $result['checked']++;
            $sourceResult = $this->validateSource($source);
            $result['details'][] = $sourceResult;

            if (!$sourceResult['valid']) {
                $result['valid'] = false;
                $result['missing'] = array_merge($result['missing'], $sourceResult['issues']);
            }

            if (!empty($sourceResult['warnings'])) {
                $result['warnings'] = array_merge($result['warnings'], $sourceResult['warnings']);
            }
        }

        // 검증 결과 로깅
        $this->logValidationResult($result);

        return $result;
    }

    /**
     * 단일 데이터 소스 검증
     *
     * @param array $source 데이터 소스 정의
     * @return array 검증 결과
     */
    private function validateSource(array $source): array
    {
        $table = $source['table'] ?? '';
        $fields = $source['fields'] ?? [];
        $required = $source['required'] ?? true;

        $result = [
            'table' => $table,
            'valid' => true,
            'issues' => [],
            'warnings' => [],
            'field_results' => []
        ];

        // 1. 테이블 존재 확인
        if (!$this->tableExists($table)) {
            $result['valid'] = $required;
            $issue = [
                'type' => 'table',
                'severity' => $required ? 'error' : 'warning',
                'message' => "[{$this->agent_id}] Table '{$table}' does not exist",
                'file' => __FILE__,
                'line' => __LINE__
            ];

            if ($required) {
                $result['issues'][] = $issue;
            } else {
                $result['warnings'][] = $issue;
            }
            return $result;
        }

        // 2. 필드 존재 확인
        foreach ($fields as $field) {
            $fieldResult = $this->validateField($table, $field, $required);
            $result['field_results'][$field] = $fieldResult;

            if (!$fieldResult['exists']) {
                $result['valid'] = $required ? false : $result['valid'];
                $issue = [
                    'type' => 'field',
                    'severity' => $required ? 'error' : 'warning',
                    'message' => "[{$this->agent_id}] Field '{$field}' does not exist in table '{$table}'",
                    'file' => __FILE__,
                    'line' => __LINE__
                ];

                if ($required) {
                    $result['issues'][] = $issue;
                } else {
                    $result['warnings'][] = $issue;
                }
            }
        }

        // 3. 학생 데이터 존재 확인 (student_id 또는 userid 필드가 있는 경우)
        $studentIdFields = ['userid', 'student_id', 'user_id', 'studentid'];
        foreach ($studentIdFields as $sidField) {
            if ($this->fieldExists($table, $sidField)) {
                $hasData = $this->studentHasData($table, $sidField);
                if (!$hasData) {
                    $result['warnings'][] = [
                        'type' => 'data',
                        'severity' => 'warning',
                        'message' => "[{$this->agent_id}] No data found for student {$this->student_id} in table '{$table}'",
                        'file' => __FILE__,
                        'line' => __LINE__
                    ];
                }
                break;
            }
        }

        return $result;
    }

    /**
     * 필드 검증
     *
     * @param string $table 테이블명
     * @param string $field 필드명
     * @param bool $required 필수 여부
     * @return array 필드 검증 결과
     */
    private function validateField(string $table, string $field, bool $required): array
    {
        $result = [
            'field' => $field,
            'exists' => false,
            'has_null' => false,
            'null_count' => 0
        ];

        // 필드 존재 확인
        $result['exists'] = $this->fieldExists($table, $field);

        if ($result['exists']) {
            // NULL 값 확인
            $nullInfo = $this->checkNullValues($table, $field);
            $result['has_null'] = $nullInfo['has_null'];
            $result['null_count'] = $nullInfo['count'];
        }

        return $result;
    }

    /**
     * 테이블 존재 여부 확인 (캐시 적용)
     *
     * @param string $table 테이블명
     * @return bool 존재 여부
     */
    private function tableExists(string $table): bool
    {
        $cacheKey = "table_exists_{$table}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // Moodle DB Manager 사용
            $dbman = $this->db->get_manager();
            $tableName = str_replace('mdl_', '', $table);
            $exists = $dbman->table_exists($tableName);
            $this->cache[$cacheKey] = $exists;
            return $exists;
        } catch (Exception $e) {
            error_log("[{$this->agent_id}] Table existence check failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }

    /**
     * 필드 존재 여부 확인 (캐시 적용)
     *
     * @param string $table 테이블명
     * @param string $field 필드명
     * @return bool 존재 여부
     */
    private function fieldExists(string $table, string $field): bool
    {
        $cacheKey = "field_exists_{$table}_{$field}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        try {
            // Moodle DB Manager 사용
            $dbman = $this->db->get_manager();
            $tableName = str_replace('mdl_', '', $table);
            $exists = $dbman->field_exists($tableName, $field);
            $this->cache[$cacheKey] = $exists;
            return $exists;
        } catch (Exception $e) {
            error_log("[{$this->agent_id}] Field existence check failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }

    /**
     * NULL 값 존재 확인
     *
     * @param string $table 테이블명
     * @param string $field 필드명
     * @return array ['has_null' => bool, 'count' => int]
     */
    private function checkNullValues(string $table, string $field): array
    {
        try {
            $tableName = str_replace('mdl_', '', $table);
            $sql = "SELECT COUNT(*) FROM {{$tableName}} WHERE {$field} IS NULL";
            $count = $this->db->count_records_sql($sql);

            return [
                'has_null' => $count > 0,
                'count' => (int)$count
            ];
        } catch (Exception $e) {
            error_log("[{$this->agent_id}] NULL check failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return ['has_null' => false, 'count' => 0];
        }
    }

    /**
     * 학생 데이터 존재 확인
     *
     * @param string $table 테이블명
     * @param string $studentIdField 학생 ID 필드명
     * @return bool 데이터 존재 여부
     */
    private function studentHasData(string $table, string $studentIdField): bool
    {
        try {
            $tableName = str_replace('mdl_', '', $table);
            return $this->db->record_exists($tableName, [$studentIdField => $this->student_id]);
        } catch (Exception $e) {
            error_log("[{$this->agent_id}] Student data check failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }

    /**
     * 검증 결과 로깅
     *
     * @param array $result 검증 결과
     */
    private function logValidationResult(array $result): void
    {
        $status = $result['valid'] ? 'PASS' : 'FAIL';
        $logMessage = sprintf(
            "[%s] DataValidation %s - Agent: %s, Student: %d, Checked: %d, Missing: %d, Warnings: %d",
            date('Y-m-d H:i:s'),
            $status,
            $this->agent_id,
            $this->student_id,
            $result['checked'],
            count($result['missing']),
            count($result['warnings'])
        );

        error_log($logMessage);

        // 에러가 있는 경우 상세 로깅
        if (!$result['valid']) {
            foreach ($result['missing'] as $issue) {
                error_log("[{$this->agent_id}] VALIDATION_ERROR: " . $issue['message']);
            }
        }
    }

    /**
     * 빠른 테이블 검증 (존재 여부만 확인)
     *
     * @param array $tables 테이블명 배열
     * @return array ['valid' => bool, 'missing' => array]
     */
    public function quickValidateTables(array $tables): array
    {
        $missing = [];

        foreach ($tables as $table) {
            if (!$this->tableExists($table)) {
                $missing[] = $table;
            }
        }

        return [
            'valid' => empty($missing),
            'missing' => $missing
        ];
    }

    /**
     * 학생에 대한 필수 데이터 존재 여부 확인
     *
     * @param string $table 테이블명
     * @param string $studentIdField 학생 ID 필드명 (기본값: 'userid')
     * @param array $additionalConditions 추가 조건 (예: ['status' => 'active'])
     * @return bool 데이터 존재 여부
     */
    public function hasStudentData(string $table, string $studentIdField = 'userid', array $additionalConditions = []): bool
    {
        try {
            $tableName = str_replace('mdl_', '', $table);
            $conditions = array_merge([$studentIdField => $this->student_id], $additionalConditions);
            return $this->db->record_exists($tableName, $conditions);
        } catch (Exception $e) {
            error_log("[{$this->agent_id}] hasStudentData failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return false;
        }
    }

    /**
     * 학생 데이터 개수 조회
     *
     * @param string $table 테이블명
     * @param string $studentIdField 학생 ID 필드명
     * @return int 데이터 개수
     */
    public function getStudentDataCount(string $table, string $studentIdField = 'userid'): int
    {
        try {
            $tableName = str_replace('mdl_', '', $table);
            return $this->db->count_records($tableName, [$studentIdField => $this->student_id]);
        } catch (Exception $e) {
            error_log("[{$this->agent_id}] getStudentDataCount failed: " . $e->getMessage() . " at " . __FILE__ . ":" . __LINE__);
            return 0;
        }
    }

    /**
     * 캐시 초기화
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * 에이전트 ID 설정
     *
     * @param string $agent_id 에이전트 식별자
     */
    public function setAgentId(string $agent_id): void
    {
        $this->agent_id = $agent_id;
    }

    /**
     * 학생 ID 설정
     *
     * @param int $student_id 학생 ID
     */
    public function setStudentId(int $student_id): void
    {
        $this->student_id = $student_id;
        $this->clearCache();
    }
}
