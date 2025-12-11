<?php
/**
 * Quantum Paths Table Setup
 * 양자 경로 테이블 생성 스크립트
 *
 * @package AugmentedTeacher\TeachingSupport\API
 * @version 1.0.0
 * @since 2025-12-11
 *
 * URL: /moodle/local/augmented_teacher/alt42/teachingsupport/api/setup_quantum_paths_table.php
 *
 * 테이블: mdl_at_quantum_paths
 * - 학습자의 양자 경로 로그 저장
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

$currentFile = __FILE__;

// [setup_quantum_paths_table.php:L20] Moodle 통합
try {
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        include_once("/home/moodle/public_html/moodle/config.php");
        global $DB;
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Moodle config.php not found',
            'error_location' => "$currentFile:26"
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Moodle 로드 실패: ' . $e->getMessage(),
        'error_location' => "$currentFile:33"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// [setup_quantum_paths_table.php:L40] 테이블 존재 여부 확인
function tableExists($DB, $tableName) {
    global $CFG;
    $prefix = $CFG->prefix ?? 'mdl_';
    $fullTableName = $prefix . $tableName;

    try {
        $tables = $DB->get_records_sql("SHOW TABLES LIKE ?", [$fullTableName]);
        return !empty($tables);
    } catch (Exception $e) {
        return false;
    }
}

// [setup_quantum_paths_table.php:L52] 테이블 생성 SQL
$createTableSQL = "
CREATE TABLE IF NOT EXISTS {at_quantum_paths} (
    id BIGINT(10) NOT NULL AUTO_INCREMENT,
    content_id VARCHAR(100) DEFAULT NULL COMMENT '콘텐츠 ID (문제 번호)',
    user_id BIGINT(10) DEFAULT NULL COMMENT '사용자 ID',
    session_id VARCHAR(255) DEFAULT NULL COMMENT '세션 ID',
    path_data LONGTEXT DEFAULT NULL COMMENT 'JSON: nodes, edges, visited, timestamp',
    final_result VARCHAR(50) DEFAULT NULL COMMENT '최종 결과: success, partial, fail',
    steps_count INT(10) DEFAULT 0 COMMENT '경로 단계 수',
    concepts_activated INT(10) DEFAULT 0 COMMENT '활성화된 개념 수',
    quantum_state TEXT DEFAULT NULL COMMENT 'JSON: alpha, beta, gamma 상태',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_content_id (content_id),
    KEY idx_user_id (user_id),
    KEY idx_session_id (session_id),
    KEY idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='양자 붕괴 학습 미로 경로 로그'
";

$results = [
    'tableName' => 'at_quantum_paths',
    'existed' => false,
    'created' => false,
    'error' => null,
    'structure' => null
];

try {
    // [setup_quantum_paths_table.php:L83] 테이블 존재 확인
    if (tableExists($DB, 'at_quantum_paths')) {
        $results['existed'] = true;

        // 테이블 구조 조회
        $columns = $DB->get_records_sql("DESCRIBE {at_quantum_paths}");
        $results['structure'] = array_keys((array)$columns);

        $results['message'] = '테이블이 이미 존재합니다.';
    } else {
        // [setup_quantum_paths_table.php:L93] 테이블 생성
        $DB->execute($createTableSQL);
        $results['created'] = true;

        // 생성 확인
        if (tableExists($DB, 'at_quantum_paths')) {
            $columns = $DB->get_records_sql("DESCRIBE {at_quantum_paths}");
            $results['structure'] = array_keys((array)$columns);
            $results['message'] = '테이블이 성공적으로 생성되었습니다.';
        } else {
            $results['error'] = '테이블 생성 후 확인 실패';
        }
    }
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
    $results['error_location'] = "$currentFile:" . $e->getLine();
}

// [setup_quantum_paths_table.php:L110] 응답
echo json_encode([
    'success' => $results['existed'] || $results['created'],
    'data' => $results,
    'timestamp' => date('Y-m-d H:i:s')
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/**
 * 테이블 구조:
 *
 * mdl_at_quantum_paths
 * - id (bigint, PK): 자동 증가 ID
 * - content_id (varchar): 콘텐츠/문제 ID
 * - user_id (bigint): 사용자 ID
 * - session_id (varchar): 브라우저 세션 ID
 * - path_data (longtext): JSON 형식의 경로 데이터
 *   {
 *     "nodes": ["start", "s1_c", "s2_c", ...],
 *     "edges": [["start", "s1_c"], ...],
 *     "visited": ["start", "s1_c"],
 *     "timestamp": "2025-12-11T12:00:00"
 *   }
 * - final_result (varchar): 최종 결과 (success/partial/fail)
 * - steps_count (int): 밟은 경로 단계 수
 * - concepts_activated (int): 활성화된 개념 수
 * - quantum_state (text): JSON 형식의 양자 상태
 *   { "alpha": 50, "beta": 30, "gamma": 20 }
 * - created_at (timestamp): 생성 시간
 * - updated_at (timestamp): 수정 시간
 */
