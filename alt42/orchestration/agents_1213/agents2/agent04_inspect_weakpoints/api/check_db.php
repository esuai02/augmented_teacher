<?php
/**
 * File: alt42/orchestration/agents/agent04_problem_activity/api/check_db.php
 * DB 스키마 확인 및 테이블 생성 스크립트
 */

require_once("/home/moodle/public_html/moodle/config.php");
require_login();

header('Content-Type: application/json');

try {
    // 1. 테이블 존재 확인
    $table_name = 'mdl_alt42_student_activity';
    $check_sql = "SHOW TABLES LIKE ?";
    $result = $DB->get_records_sql($check_sql, [$table_name]);

    $response = [
        'status' => 'ok',
        'table_exists' => !empty($result),
        'table_name' => $table_name
    ];

    // 2. 테이블이 없으면 생성
    if (empty($result)) {
        $create_sql = "
            CREATE TABLE IF NOT EXISTS {$table_name} (
                id BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                userid BIGINT(10) UNSIGNED NOT NULL,
                main_category VARCHAR(100) NOT NULL,
                sub_activity VARCHAR(200),
                behavior_type VARCHAR(50),
                survey_responses TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_userid (userid),
                INDEX idx_category (main_category),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            COMMENT='Agent04: 학생 활동 선택 및 행동 유형 데이터'
        ";

        $DB->execute($create_sql);
        $response['table_created'] = true;
        $response['message'] = 'Table created successfully';
    } else {
        // 3. 테이블 구조 확인
        $describe_sql = "DESCRIBE {$table_name}";
        $columns = $DB->get_records_sql($describe_sql);
        $response['columns'] = $columns;
    }

    echo json_encode($response);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => $e->getLine()
    ]);
}
