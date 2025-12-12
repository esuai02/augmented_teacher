<?php
/**
 * DB Setup - 페르소나 시스템 테이블 생성
 *
 * augmented_teacher_personas, augmented_teacher_sessions 테이블을 생성합니다.
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/engine/db_setup.php
 *
 * @package AugmentedTeacher\Agent01\PersonaSystem
 * @version 1.0
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
require_login();

// 관리자 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid = ? AND fieldid = 22", [$USER->id]);
$role = $userrole ? $userrole->data : '';

if (!is_siteadmin() && $role !== 'admin') {
    die("[ERROR] db_setup.php:" . __LINE__ . " - 관리자 권한이 필요합니다.");
}

$currentFile = __FILE__;
$results = [];

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
function executeSQL($sql, $description) {
    global $DB, $results, $currentFile;
    try {
        $DB->execute($sql);
        $results[] = [
            'status' => 'SUCCESS',
            'description' => $description,
            'message' => '성공적으로 실행됨'
        ];
        return true;
    } catch (Exception $e) {
        $results[] = [
            'status' => 'ERROR',
            'description' => $description,
            'message' => "[{$currentFile}:" . __LINE__ . "] " . $e->getMessage()
        ];
        return false;
    }
}

// ============================================
// 1. augmented_teacher_personas 테이블 생성
// ============================================

$tableName1 = 'augmented_teacher_personas';
if (!tableExists($tableName1)) {
    $sql1 = "CREATE TABLE {$CFG->prefix}{$tableName1} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
        agent_id VARCHAR(20) NOT NULL DEFAULT 'agent01' COMMENT '에이전트 ID',
        persona_id VARCHAR(20) NOT NULL COMMENT '페르소나 ID (예: S1_P1)',
        situation VARCHAR(5) NOT NULL COMMENT '상황 코드 (S0-S5, C, Q, E)',
        confidence DECIMAL(3,2) NOT NULL DEFAULT 0.50 COMMENT '신뢰도 (0.00-1.00)',
        matched_rule VARCHAR(50) NULL COMMENT '매칭된 규칙 ID',
        context_snapshot TEXT NULL COMMENT '컨텍스트 스냅샷 (JSON)',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',

        INDEX idx_user_agent (user_id, agent_id),
        INDEX idx_persona (persona_id),
        INDEX idx_situation (situation),
        INDEX idx_created (created_at),
        INDEX idx_confidence (confidence)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='학생 페르소나 식별 이력'";

    executeSQL($sql1, "테이블 생성: {$tableName1}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName1}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 2. augmented_teacher_sessions 테이블 생성
// ============================================

$tableName2 = 'augmented_teacher_sessions';
if (!tableExists($tableName2)) {
    $sql2 = "CREATE TABLE {$CFG->prefix}{$tableName2} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
        agent_id VARCHAR(20) NOT NULL DEFAULT 'agent01' COMMENT '에이전트 ID',
        session_key VARCHAR(64) NOT NULL COMMENT '세션 고유 키',
        current_situation VARCHAR(5) NULL COMMENT '현재 상황 코드',
        current_persona VARCHAR(20) NULL COMMENT '현재 페르소나 ID',
        context_data JSON NULL COMMENT '컨텍스트 데이터 (JSON)',
        message_count INT(10) DEFAULT 0 COMMENT '메시지 수',
        last_message TEXT NULL COMMENT '마지막 메시지',
        last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '마지막 활동',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',

        UNIQUE KEY uk_session (session_key),
        INDEX idx_user_agent (user_id, agent_id),
        INDEX idx_user_session (user_id, session_key),
        INDEX idx_last_activity (last_activity),
        INDEX idx_current_persona (current_persona)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='AI 세션 컨텍스트'";

    executeSQL($sql2, "테이블 생성: {$tableName2}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName2}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 3. augmented_teacher_persona_transitions 테이블 생성 (5단계용)
// ============================================

$tableName3 = 'augmented_teacher_persona_transitions';
if (!tableExists($tableName3)) {
    $sql3 = "CREATE TABLE {$CFG->prefix}{$tableName3} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NOT NULL COMMENT '사용자 ID',
        agent_id VARCHAR(20) NOT NULL DEFAULT 'agent01' COMMENT '에이전트 ID',
        session_key VARCHAR(64) NOT NULL COMMENT '세션 키',
        from_persona VARCHAR(20) NULL COMMENT '이전 페르소나',
        to_persona VARCHAR(20) NOT NULL COMMENT '새 페르소나',
        from_situation VARCHAR(5) NULL COMMENT '이전 상황',
        to_situation VARCHAR(5) NOT NULL COMMENT '새 상황',
        trigger_type VARCHAR(30) NOT NULL COMMENT '전환 트리거 유형',
        trigger_detail TEXT NULL COMMENT '전환 트리거 상세',
        confidence_change DECIMAL(4,2) NULL COMMENT '신뢰도 변화',
        transition_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '전환 시간',

        INDEX idx_user (user_id),
        INDEX idx_session (session_key),
        INDEX idx_from_persona (from_persona),
        INDEX idx_to_persona (to_persona),
        INDEX idx_transition_time (transition_time)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='페르소나 전환 이력'";

    executeSQL($sql3, "테이블 생성: {$tableName3}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName3}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 4. augmented_teacher_ai_usage 테이블 생성 (AI API 사용량)
// ============================================

$tableName4 = 'augmented_teacher_ai_usage';
if (!tableExists($tableName4)) {
    $sql4 = "CREATE TABLE {$CFG->prefix}{$tableName4} (
        id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT(10) NULL COMMENT '사용자 ID (NULL=시스템)',
        agent_id VARCHAR(20) NOT NULL DEFAULT 'agent01' COMMENT '에이전트 ID',
        model VARCHAR(50) NOT NULL COMMENT 'OpenAI 모델명',
        purpose VARCHAR(30) NOT NULL DEFAULT 'chat' COMMENT '용도 (nlu, reasoning, chat, code)',
        prompt_tokens INT(10) DEFAULT 0 COMMENT '입력 토큰 수',
        completion_tokens INT(10) DEFAULT 0 COMMENT '출력 토큰 수',
        total_tokens INT(10) DEFAULT 0 COMMENT '총 토큰 수',
        estimated_cost DECIMAL(10,6) NULL COMMENT '예상 비용 (USD)',
        response_time_ms INT(10) NULL COMMENT '응답 시간 (밀리초)',
        success TINYINT(1) DEFAULT 1 COMMENT '성공 여부',
        error_message TEXT NULL COMMENT '에러 메시지',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '생성 시간',

        INDEX idx_user (user_id),
        INDEX idx_model (model),
        INDEX idx_purpose (purpose),
        INDEX idx_created (created_at),
        INDEX idx_success (success)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    COMMENT='AI API 사용량 로그'";

    executeSQL($sql4, "테이블 생성: {$tableName4}");
} else {
    $results[] = [
        'status' => 'SKIP',
        'description' => "테이블 확인: {$tableName4}",
        'message' => '이미 존재함'
    ];
}

// ============================================
// 결과 출력
// ============================================

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>페르소나 시스템 DB 설정</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .result { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .result.SUCCESS { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .result.ERROR { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .result.SKIP { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; }
        .status { font-weight: bold; }
        .description { font-size: 14px; }
        .message { font-size: 12px; color: #666; margin-top: 5px; }
        .summary { margin-top: 20px; padding: 15px; background: #e9ecef; border-radius: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
<div class="container">
    <h1>페르소나 시스템 DB 설정</h1>

    <h2>실행 결과</h2>
    <?php foreach ($results as $result): ?>
    <div class="result <?php echo $result['status']; ?>">
        <span class="status">[<?php echo $result['status']; ?>]</span>
        <span class="description"><?php echo $result['description']; ?></span>
        <div class="message"><?php echo $result['message']; ?></div>
    </div>
    <?php endforeach; ?>

    <div class="summary">
        <h3>생성된 테이블</h3>
        <table>
            <tr>
                <th>테이블명</th>
                <th>설명</th>
                <th>주요 필드</th>
            </tr>
            <tr>
                <td><code>augmented_teacher_personas</code></td>
                <td>페르소나 식별 이력</td>
                <td>user_id, persona_id, situation, confidence, matched_rule</td>
            </tr>
            <tr>
                <td><code>augmented_teacher_sessions</code></td>
                <td>AI 세션 컨텍스트</td>
                <td>user_id, session_key, current_situation, current_persona, context_data</td>
            </tr>
            <tr>
                <td><code>augmented_teacher_persona_transitions</code></td>
                <td>페르소나 전환 이력</td>
                <td>from_persona, to_persona, trigger_type, transition_time</td>
            </tr>
            <tr>
                <td><code>augmented_teacher_ai_usage</code></td>
                <td>AI API 사용량 로그</td>
                <td>model, purpose, prompt_tokens, completion_tokens, estimated_cost</td>
            </tr>
        </table>
    </div>

    <div class="summary">
        <h3>연동된 Moodle 테이블</h3>
        <table>
            <tr>
                <th>테이블명</th>
                <th>용도</th>
                <th>사용 필드</th>
            </tr>
            <tr>
                <td><code>mdl_user</code></td>
                <td>사용자 기본 정보</td>
                <td>id, username, firstname, lastname, email</td>
            </tr>
            <tr>
                <td><code>mdl_user_info_data</code></td>
                <td>사용자 역할</td>
                <td>userid, fieldid=22, data (역할명)</td>
            </tr>
            <tr>
                <td><code>mdl_grade_grades</code></td>
                <td>성적 데이터</td>
                <td>userid, itemid, finalgrade</td>
            </tr>
            <tr>
                <td><code>mdl_logstore_standard_log</code></td>
                <td>활동 로그</td>
                <td>userid, component, action, timecreated</td>
            </tr>
        </table>
    </div>

    <p style="margin-top: 20px; color: #666; font-size: 12px;">
        실행 시간: <?php echo date('Y-m-d H:i:s'); ?><br>
        실행 사용자: <?php echo $USER->username; ?> (ID: <?php echo $USER->id; ?>)
    </p>
</div>
</body>
</html>
<?php

/*
 * 생성 테이블:
 * - augmented_teacher_personas: 페르소나 식별 이력
 * - augmented_teacher_sessions: AI 세션 컨텍스트
 * - augmented_teacher_persona_transitions: 페르소나 전환 이력
 *
 * 연동 Moodle 테이블:
 * - mdl_user: 사용자 기본 정보
 * - mdl_user_info_data: 역할 정보 (fieldid=22)
 * - mdl_grade_grades: 성적 데이터
 * - mdl_logstore_standard_log: 활동 로그
 */
