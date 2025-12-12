<?php
/**
 * Agent08 Calmness Persona System - DB Setup
 *
 * 평온도 관리 에이전트용 데이터베이스 테이블 생성
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent08_calmness/persona_system/setup_db.php
 *
 * @package AugmentedTeacher\Agent08\PersonaSystem
 * @version 1.0
 */

// Moodle 설정 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 표시 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 현재 파일 경로 (에러 메시지용)
define('CURRENT_FILE', __FILE__);
define('CURRENT_LINE', __LINE__);

// 결과 저장
$results = [];

/**
 * 테이블 생성 결과 기록
 */
function recordResult($table, $success, $message) {
    global $results;
    $results[] = [
        'table' => $table,
        'success' => $success,
        'message' => $message,
        'time' => date('H:i:s')
    ];
}

/**
 * 테이블 존재 확인
 */
function tableExists($tableName) {
    global $DB;
    try {
        return $DB->get_manager()->table_exists($tableName);
    } catch (Exception $e) {
        return false;
    }
}

// HTML 출력 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent08 Calmness - DB Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #1e3a5f; margin-bottom: 20px; }
        h2 { color: #2c5282; margin: 20px 0 10px; }

        .info-box {
            background: #ebf8ff;
            border: 1px solid #bee3f8;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .result-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
        }

        .status-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
            color: white;
        }
        .status-icon.success { background: #48bb78; }
        .status-icon.fail { background: #f56565; }
        .status-icon.skip { background: #ed8936; }

        .result-content { flex: 1; }
        .result-table { font-weight: bold; color: #2d3748; }
        .result-message { color: #718096; font-size: 14px; }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #718096;
            font-size: 14px;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3182ce;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin-top: 15px;
        }
        .btn:hover { background: #2c5282; }
    </style>
</head>
<body>
<div class="container">
    <h1>Agent08 Calmness - DB Setup</h1>

    <div class="info-box">
        <strong>정보:</strong> 이 스크립트는 Agent08 평온도 관리 시스템에 필요한 데이터베이스 테이블을 생성합니다.
    </div>

<?php
// ============================================
// 1. at_agent_calmness_sessions 테이블
// ============================================
$tableName = 'at_agent_calmness_sessions';
echo "<h2>1. {$tableName}</h2>";

if (tableExists($tableName)) {
    recordResult($tableName, 'skip', '테이블이 이미 존재합니다');
} else {
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL,
        session_id VARCHAR(64) NOT NULL,
        calmness_level INT(3) NOT NULL DEFAULT 85 COMMENT '평온도 레벨 (0-100)',
        calmness_category VARCHAR(10) DEFAULT 'C85' COMMENT 'C95, C90, C85, C80, C75, C_crisis',
        trigger_type VARCHAR(50) DEFAULT NULL COMMENT '불안 유발 요인',
        trigger_intensity INT(3) DEFAULT 0 COMMENT '트리거 강도 (0-100)',
        emotional_state TEXT DEFAULT NULL COMMENT '감정 상태 JSON',
        session_context TEXT DEFAULT NULL COMMENT '세션 컨텍스트 JSON',
        started_at BIGINT(10) NOT NULL,
        updated_at BIGINT(10) NOT NULL,
        ended_at BIGINT(10) DEFAULT NULL,
        status VARCHAR(20) DEFAULT 'active' COMMENT 'active, paused, ended',
        PRIMARY KEY (id),
        KEY idx_userid (userid),
        KEY idx_session_id (session_id),
        KEY idx_calmness_level (calmness_level),
        KEY idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='평온도 세션 관리'";

    try {
        $DB->execute($sql);
        recordResult($tableName, 'success', '테이블 생성 완료');
    } catch (Exception $e) {
        recordResult($tableName, 'fail', '생성 실패: ' . $e->getMessage() . ' [' . CURRENT_FILE . ':' . __LINE__ . ']');
    }
}

// ============================================
// 2. at_agent_calmness_exercises 테이블
// ============================================
$tableName = 'at_agent_calmness_exercises';
echo "<h2>2. {$tableName}</h2>";

if (tableExists($tableName)) {
    recordResult($tableName, 'skip', '테이블이 이미 존재합니다');
} else {
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL,
        session_id VARCHAR(64) NOT NULL,
        exercise_type VARCHAR(50) NOT NULL COMMENT 'breathing_478, box_breathing, grounding_54321, etc.',
        exercise_category VARCHAR(30) NOT NULL COMMENT 'breathing, grounding, body_scan, visualization',
        started_at BIGINT(10) NOT NULL,
        completed_at BIGINT(10) DEFAULT NULL,
        duration_seconds INT(5) DEFAULT 0,
        calmness_before INT(3) DEFAULT NULL COMMENT '운동 전 평온도',
        calmness_after INT(3) DEFAULT NULL COMMENT '운동 후 평온도',
        effectiveness_score INT(3) DEFAULT NULL COMMENT '효과 점수 (0-100)',
        user_feedback TEXT DEFAULT NULL COMMENT '사용자 피드백 JSON',
        completion_status VARCHAR(20) DEFAULT 'started' COMMENT 'started, completed, abandoned',
        PRIMARY KEY (id),
        KEY idx_userid (userid),
        KEY idx_session_id (session_id),
        KEY idx_exercise_type (exercise_type),
        KEY idx_completion_status (completion_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='평온화 운동 이력'";

    try {
        $DB->execute($sql);
        recordResult($tableName, 'success', '테이블 생성 완료');
    } catch (Exception $e) {
        recordResult($tableName, 'fail', '생성 실패: ' . $e->getMessage() . ' [' . CURRENT_FILE . ':' . __LINE__ . ']');
    }
}

// ============================================
// 3. at_agent_calmness_crisis_events 테이블
// ============================================
$tableName = 'at_agent_calmness_crisis_events';
echo "<h2>3. {$tableName}</h2>";

if (tableExists($tableName)) {
    recordResult($tableName, 'skip', '테이블이 이미 존재합니다');
} else {
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL,
        session_id VARCHAR(64) NOT NULL,
        crisis_level VARCHAR(20) NOT NULL COMMENT 'mild, moderate, severe, critical',
        crisis_type VARCHAR(50) DEFAULT NULL COMMENT 'panic_attack, anxiety_spike, emotional_crisis, etc.',
        detected_at BIGINT(10) NOT NULL,
        resolved_at BIGINT(10) DEFAULT NULL,
        initial_calmness INT(3) DEFAULT NULL,
        final_calmness INT(3) DEFAULT NULL,
        intervention_type VARCHAR(50) DEFAULT NULL COMMENT '개입 유형',
        intervention_steps TEXT DEFAULT NULL COMMENT '개입 단계 JSON',
        escalated TINYINT(1) DEFAULT 0 COMMENT '에스컬레이션 여부',
        escalation_target VARCHAR(50) DEFAULT NULL COMMENT 'teacher, counselor, emergency',
        notes TEXT DEFAULT NULL COMMENT '추가 메모',
        status VARCHAR(20) DEFAULT 'active' COMMENT 'active, resolved, escalated',
        PRIMARY KEY (id),
        KEY idx_userid (userid),
        KEY idx_session_id (session_id),
        KEY idx_crisis_level (crisis_level),
        KEY idx_status (status),
        KEY idx_escalated (escalated)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='위기 이벤트 기록'";

    try {
        $DB->execute($sql);
        recordResult($tableName, 'success', '테이블 생성 완료');
    } catch (Exception $e) {
        recordResult($tableName, 'fail', '생성 실패: ' . $e->getMessage() . ' [' . CURRENT_FILE . ':' . __LINE__ . ']');
    }
}

// ============================================
// 4. at_agent_persona_state 테이블 (공통)
// ============================================
$tableName = 'at_agent_persona_state';
echo "<h2>4. {$tableName}</h2>";

if (tableExists($tableName)) {
    recordResult($tableName, 'skip', '테이블이 이미 존재합니다');
} else {
    $sql = "CREATE TABLE IF NOT EXISTS {$tableName} (
        id BIGINT(10) NOT NULL AUTO_INCREMENT,
        userid BIGINT(10) NOT NULL,
        agent_type INT(2) NOT NULL COMMENT '에이전트 번호 (1-21)',
        state_key VARCHAR(100) NOT NULL COMMENT '상태 키',
        state_value TEXT DEFAULT NULL COMMENT '상태 값 JSON',
        state_category VARCHAR(50) DEFAULT 'general' COMMENT 'general, emotional, behavioral, contextual',
        confidence DECIMAL(5,4) DEFAULT 0.5000 COMMENT '신뢰도 (0-1)',
        source VARCHAR(50) DEFAULT 'system' COMMENT '상태 출처',
        created_at BIGINT(10) NOT NULL,
        updated_at BIGINT(10) NOT NULL,
        expires_at BIGINT(10) DEFAULT NULL COMMENT '만료 시간',
        PRIMARY KEY (id),
        UNIQUE KEY uk_user_agent_key (userid, agent_type, state_key),
        KEY idx_userid (userid),
        KEY idx_agent_type (agent_type),
        KEY idx_state_category (state_category)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='에이전트 페르소나 상태'";

    try {
        $DB->execute($sql);
        recordResult($tableName, 'success', '테이블 생성 완료');
    } catch (Exception $e) {
        recordResult($tableName, 'fail', '생성 실패: ' . $e->getMessage() . ' [' . CURRENT_FILE . ':' . __LINE__ . ']');
    }
}

// 결과 출력
echo '<h2>결과</h2>';
foreach ($results as $result) {
    $iconClass = $result['success'] === 'success' ? 'success' :
                 ($result['success'] === 'skip' ? 'skip' : 'fail');
    $icon = $result['success'] === 'success' ? '✓' :
            ($result['success'] === 'skip' ? '○' : '✗');

    echo "<div class='result-item'>";
    echo "<div class='status-icon {$iconClass}'>{$icon}</div>";
    echo "<div class='result-content'>";
    echo "<div class='result-table'>{$result['table']}</div>";
    echo "<div class='result-message'>{$result['message']}</div>";
    echo "</div>";
    echo "</div>";
}

// 성공 수 계산
$successCount = count(array_filter($results, function($r) { return $r['success'] === 'success'; }));
$skipCount = count(array_filter($results, function($r) { return $r['success'] === 'skip'; }));
$failCount = count(array_filter($results, function($r) { return $r['success'] === 'fail'; }));
?>

    <div class="info-box" style="margin-top: 20px;">
        <strong>요약:</strong>
        생성: <?php echo $successCount; ?>개 |
        기존: <?php echo $skipCount; ?>개 |
        실패: <?php echo $failCount; ?>개
    </div>

    <a href="test.php" class="btn">테스트 페이지로 돌아가기</a>

    <div class="footer">
        <p>Agent08 Calmness Persona System v1.0</p>
        <p>실행 시간: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p>파일 위치: <?php echo CURRENT_FILE; ?></p>
    </div>
</div>
</body>
</html>
<?php
/*
 * 생성된 테이블:
 * 1. at_agent_calmness_sessions - 평온도 세션 관리
 * 2. at_agent_calmness_exercises - 운동 이력
 * 3. at_agent_calmness_crisis_events - 위기 이벤트 기록
 * 4. at_agent_persona_state - 페르소나 상태 (공통)
 *
 * 참조 파일:
 * - test.php (테스트 페이지)
 * - engine/CalmnessPersonaRuleEngine.php (메인 엔진)
 */
?>
