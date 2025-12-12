<?php
/**
 * Agent02 DB 테이블 초기 설치 스크립트
 * 주의: 이 파일은 한 번 실행 후 삭제하거나 비활성화해야 합니다!
 *
 * 실행: GET /api/db_install.php?secret_key=install_agent02_2025
 *
 * @package AugmentedTeacher\Agent02\API
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=UTF-8');

// 보안: 간단한 설치 키 확인
$installKey = isset($_GET['secret_key']) ? $_GET['secret_key'] : '';
if ($installKey !== 'install_agent02_2025') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid install key. Usage: ?secret_key=install_agent02_2025',
        'file' => __FILE__
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Moodle 환경 로드 (로그인 없이)
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $CFG;

$currentFile = __FILE__;
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'agent' => 'agent02_exam_schedule',
    'tables' => []
];

/**
 * 테이블 존재 여부 확인
 */
function tableExists($tableName) {
    global $DB;
    try {
        return $DB->get_manager()->table_exists($tableName);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * SQL 실행 및 결과 기록
 */
function executeSQL($sql, $tableName, $description) {
    global $DB, $results, $currentFile;
    try {
        $DB->execute($sql);
        $results['tables'][$tableName] = [
            'status' => 'CREATED',
            'description' => $description,
            'message' => '테이블이 성공적으로 생성되었습니다.'
        ];
        return true;
    } catch (Exception $e) {
        $results['tables'][$tableName] = [
            'status' => 'ERROR',
            'description' => $description,
            'message' => $e->getMessage(),
            'file' => $currentFile,
            'line' => __LINE__
        ];
        return false;
    }
}

// ============================================
// 1. at_exam_schedules 테이블 생성
// ============================================

$tableName1 = 'at_exam_schedules';
if (!tableExists($tableName1)) {
    $sql1 = "CREATE TABLE {$CFG->prefix}{$tableName1} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        exam_name VARCHAR(200) NOT NULL COMMENT '시험명',
        exam_subject VARCHAR(100) NULL COMMENT '과목명',
        exam_date DATE NOT NULL COMMENT '시험 날짜',
        exam_time TIME NULL COMMENT '시험 시작 시간',
        priority TINYINT(1) DEFAULT 5 COMMENT '중요도 (1-10)',
        study_status ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started' COMMENT '학습 상태',
        notes TEXT NULL COMMENT '메모',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_exam (user_id, exam_date),
        INDEX idx_exam_date (exam_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agent02 시험 일정'";
    executeSQL($sql1, $tableName1, 'Agent02 시험 일정 테이블');
} else {
    $results['tables'][$tableName1] = [
        'status' => 'EXISTS',
        'description' => 'Agent02 시험 일정 테이블',
        'message' => '테이블이 이미 존재합니다.'
    ];
}

// ============================================
// 2. at_exam_study_logs 테이블 생성
// ============================================

$tableName2 = 'at_exam_study_logs';
if (!tableExists($tableName2)) {
    $sql2 = "CREATE TABLE {$CFG->prefix}{$tableName2} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        exam_schedule_id BIGINT(10) NULL COMMENT '연관 시험 일정 ID',
        study_date DATE NOT NULL COMMENT '학습 날짜',
        study_duration INT DEFAULT 0 COMMENT '학습 시간 (분)',
        study_content TEXT NULL COMMENT '학습 내용',
        effectiveness TINYINT(1) DEFAULT 5 COMMENT '학습 효과 자기평가 (1-10)',
        mood ENUM('positive', 'neutral', 'negative') DEFAULT 'neutral' COMMENT '학습 중 기분',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_date (user_id, study_date),
        INDEX idx_exam (exam_schedule_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agent02 학습 로그'";
    executeSQL($sql2, $tableName2, 'Agent02 학습 로그 테이블');
} else {
    $results['tables'][$tableName2] = [
        'status' => 'EXISTS',
        'description' => 'Agent02 학습 로그 테이블',
        'message' => '테이블이 이미 존재합니다.'
    ];
}

// ============================================
// 3. at_agent02_dday_snapshots 테이블 생성
// ============================================

$tableName3 = 'at_agent02_dday_snapshots';
if (!tableExists($tableName3)) {
    $sql3 = "CREATE TABLE {$CFG->prefix}{$tableName3} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '학생 사용자 ID',
        exam_schedule_id BIGINT(10) NOT NULL COMMENT '시험 일정 ID',
        snapshot_date DATE NOT NULL COMMENT '스냅샷 날짜',
        d_day INT NOT NULL COMMENT 'D-Day 값',
        situation VARCHAR(20) NOT NULL COMMENT '상황 (D_URGENT, D_BALANCED, D_CONCEPT, D_FOUNDATION)',
        student_type VARCHAR(10) NULL COMMENT '학생 유형 (P1-P6)',
        persona_id VARCHAR(30) NULL COMMENT '적용된 페르소나 ID',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_exam (user_id, exam_schedule_id),
        INDEX idx_snapshot_date (snapshot_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Agent02 D-Day 스냅샷'";
    executeSQL($sql3, $tableName3, 'Agent02 D-Day 스냅샷 테이블');
} else {
    $results['tables'][$tableName3] = [
        'status' => 'EXISTS',
        'description' => 'Agent02 D-Day 스냅샷 테이블',
        'message' => '테이블이 이미 존재합니다.'
    ];
}

// ============================================
// 4. 공유 테이블 확인 및 생성 (augmented_teacher_sessions)
// ============================================

$tableName4 = 'augmented_teacher_sessions';
if (!tableExists($tableName4)) {
    $sql4 = "CREATE TABLE {$CFG->prefix}{$tableName4} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
        agent_type VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '에이전트 타입',
        session_token VARCHAR(64) UNIQUE NOT NULL COMMENT '세션 토큰',
        context_data JSON NULL COMMENT '세션 컨텍스트 데이터',
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP NULL COMMENT '만료 시간',
        INDEX idx_user_agent (user_id, agent_type),
        INDEX idx_token (session_token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 교사 세션'";
    executeSQL($sql4, $tableName4, '공유 세션 테이블');
} else {
    $results['tables'][$tableName4] = [
        'status' => 'EXISTS',
        'description' => '공유 세션 테이블',
        'message' => '테이블이 이미 존재합니다.'
    ];
}

// ============================================
// 5. 공유 테이블 확인 및 생성 (augmented_teacher_personas)
// ============================================

$tableName5 = 'augmented_teacher_personas';
if (!tableExists($tableName5)) {
    $sql5 = "CREATE TABLE {$CFG->prefix}{$tableName5} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
        agent_type VARCHAR(50) NOT NULL DEFAULT 'general' COMMENT '에이전트 타입',
        persona_id VARCHAR(50) NOT NULL COMMENT '페르소나 ID',
        confidence DECIMAL(3,2) DEFAULT 0.50 COMMENT '신뢰도 (0.00-1.00)',
        matched_rule VARCHAR(100) NULL COMMENT '매칭된 규칙',
        context_snapshot JSON NULL COMMENT '매칭 시점 컨텍스트',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_agent (user_id, agent_type),
        INDEX idx_persona (persona_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='AI 교사 페르소나 기록'";
    executeSQL($sql5, $tableName5, '공유 페르소나 테이블');
} else {
    $results['tables'][$tableName5] = [
        'status' => 'EXISTS',
        'description' => '공유 페르소나 테이블',
        'message' => '테이블이 이미 존재합니다.'
    ];
}

// ============================================
// 결과 요약
// ============================================

$created = 0;
$exists = 0;
$errors = 0;

foreach ($results['tables'] as $table) {
    switch ($table['status']) {
        case 'CREATED': $created++; break;
        case 'EXISTS': $exists++; break;
        case 'ERROR': $errors++; break;
    }
}

$results['summary'] = [
    'created' => $created,
    'already_exists' => $exists,
    'errors' => $errors,
    'total' => count($results['tables'])
];

$results['success'] = ($errors === 0);
$results['next_step'] = $results['success']
    ? '설치 완료! 이 파일(db_install.php)을 삭제하거나 비활성화하세요.'
    : '오류를 확인하고 다시 실행하세요.';

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
