<?php
/**
 * ktm_user_neurons 테이블 생성 스크립트
 * 유기적 뉴런 배양 시스템용 사용자 생성 노드 저장
 */

require_once '/home/moodle/public_html/moodle/config.php';

header('Content-Type: application/json; charset=utf-8');

global $DB, $CFG;

try {
    $tableName = 'ktm_user_neurons';
    $fullTableName = $CFG->prefix . $tableName;

    // 테이블 존재 여부 확인
    $checkSql = "SHOW TABLES LIKE '{$fullTableName}'";
    $exists = $DB->get_records_sql($checkSql);

    if (!empty($exists)) {
        echo json_encode([
            'success' => true,
            'message' => '테이블이 이미 존재합니다.',
            'table' => $fullTableName
        ]);
        exit;
    }

    // 테이블 생성 SQL
    $sql = "CREATE TABLE {$fullTableName} (
        id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
        question_id VARCHAR(100) DEFAULT '' COMMENT '문제 ID',
        parent_node_id VARCHAR(50) DEFAULT '' COMMENT '부모 노드 ID',
        title VARCHAR(100) NOT NULL COMMENT '노드 제목',
        summary TEXT COMMENT '요약',
        description TEXT COMMENT '상세 설명',
        path_type VARCHAR(20) DEFAULT 'alternative' COMMENT 'alternative/misconception/shortcut',
        tags TEXT COMMENT 'JSON: concepts, learnerType, difficulty',
        creator_id BIGINT(10) UNSIGNED DEFAULT 0 COMMENT '생성자 ID',
        creator_name VARCHAR(100) DEFAULT '' COMMENT '생성자 이름',
        status VARCHAR(20) DEFAULT 'pending' COMMENT 'pending/verified/public/rejected',
        votes_count INT(10) DEFAULT 0 COMMENT '투표 수',
        created_at BIGINT(10) UNSIGNED COMMENT '생성 시간',
        verified_at BIGINT(10) UNSIGNED DEFAULT NULL COMMENT '검증 시간',
        PRIMARY KEY (id),
        KEY idx_question (question_id),
        KEY idx_creator (creator_id),
        KEY idx_status (status),
        KEY idx_parent (parent_node_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='유기적 뉴런 배양 시스템 - 사용자 생성 노드'";

    $DB->execute($sql);

    // 테이블 생성 확인
    $verifyCheck = $DB->get_records_sql($checkSql);

    echo json_encode([
        'success' => !empty($verifyCheck),
        'message' => !empty($verifyCheck) ? '테이블이 성공적으로 생성되었습니다.' : '테이블 생성 실패',
        'table' => $fullTableName
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
