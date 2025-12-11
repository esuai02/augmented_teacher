<?php
/**
 * ktm_quantum_paths 테이블 생성 스크립트
 * 한 번만 실행하면 됩니다.
 */

require_once '/home/moodle/public_html/moodle/config.php';

header('Content-Type: application/json; charset=utf-8');

global $DB, $CFG;

try {
    // 테이블 존재 여부 확인
    $tables = $DB->get_tables();
    $tableName = 'ktm_quantum_paths';
    $fullTableName = $CFG->prefix . $tableName;

    if (in_array($tableName, $tables)) {
        echo json_encode([
            'success' => true,
            'message' => '테이블이 이미 존재합니다.',
            'table' => $fullTableName
        ]);
        exit;
    }

    // 테이블 생성 SQL
    $sql = "CREATE TABLE IF NOT EXISTS {$fullTableName} (
        id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        question_id VARCHAR(100) DEFAULT '',
        parent_node_id VARCHAR(50) DEFAULT '',
        title VARCHAR(100) NOT NULL,
        summary TEXT,
        description TEXT,
        path_type VARCHAR(20) DEFAULT 'alternative',
        tags TEXT,
        creator_id BIGINT(10) UNSIGNED DEFAULT 0,
        creator_name VARCHAR(100) DEFAULT '',
        status VARCHAR(20) DEFAULT 'pending',
        votes_count INT(10) DEFAULT 0,
        created_at BIGINT(10) UNSIGNED,
        verified_at BIGINT(10) UNSIGNED DEFAULT NULL,
        PRIMARY KEY (id),
        KEY idx_question (question_id),
        KEY idx_creator (creator_id),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $DB->execute($sql);

    echo json_encode([
        'success' => true,
        'message' => '테이블이 성공적으로 생성되었습니다.',
        'table' => $fullTableName
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
