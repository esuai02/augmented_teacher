<?php
/**
 * Quantum Modeling Database Setup
 * 양자 모델링 관련 DB 테이블 생성
 *
 * @package AugmentedTeacher\Agent04\QuantumModeling
 * @version 1.0.0
 * @since 2025-12-06
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 현재 파일 경로 (에러 출력용)
$currentFile = __FILE__;

// 관리자/교사 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM {user_info_data} WHERE userid=? AND fieldid=22", [$USER->id]);
$role = $userrole->data ?? 'student';

// Moodle 사이트 관리자 확인 또는 teacher/admin 역할 확인
$isAdmin = is_siteadmin($USER->id) || in_array($role, ['admin', 'teacher', 'manager']);

if (!$isAdmin) {
    die("관리자 또는 교사 권한이 필요합니다. (현재 역할: {$role}) ({$currentFile}:" . __LINE__ . ")");
}

$messages = [];

try {
    // 테이블 존재 여부 확인
    $tableExists = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_at_quantum_state'");
    
    if (!$tableExists) {
        // 양자 상태 테이블 생성
        $sql = "CREATE TABLE IF NOT EXISTS mdl_at_quantum_state (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            agent_id VARCHAR(50) NOT NULL DEFAULT 'agent04',
            state_vector TEXT COMMENT '양자 상태 벡터 JSON',
            probabilities TEXT COMMENT '확률 분포 JSON',
            dominant_persona VARCHAR(20) COMMENT '지배적 페르소나 (S/D/G/A)',
            superposition_level VARCHAR(30) COMMENT '중첩 수준',
            synergy FLOAT DEFAULT 0 COMMENT '시너지 확률 (0~1)',
            backfire FLOAT DEFAULT 0 COMMENT '역효과 확률 (0~1)',
            golden_time INT DEFAULT 0 COMMENT '골든 타임 (초)',
            context_data TEXT COMMENT '컨텍스트 데이터 JSON',
            created_at INT(11) NOT NULL COMMENT '생성 시간 (Unix timestamp)',
            INDEX idx_user_agent (user_id, agent_id),
            INDEX idx_created (created_at),
            INDEX idx_dominant (dominant_persona)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Agent04 양자 모델링 상태 저장 테이블'";
        
        $DB->execute($sql);
        $messages[] = "✅ mdl_at_quantum_state 테이블 생성 완료";
    } else {
        $messages[] = "ℹ️ mdl_at_quantum_state 테이블이 이미 존재합니다";
    }
    
    // 양자 페르소나 전환 로그 테이블
    $tableExists2 = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_at_quantum_transition'");
    
    if (!$tableExists2) {
        $sql2 = "CREATE TABLE IF NOT EXISTS mdl_at_quantum_transition (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            agent_id VARCHAR(50) NOT NULL DEFAULT 'agent04',
            from_persona VARCHAR(20) NOT NULL COMMENT '전환 전 페르소나',
            to_persona VARCHAR(20) NOT NULL COMMENT '전환 후 페르소나',
            path TEXT COMMENT '전환 경로 JSON',
            total_cost INT DEFAULT 0 COMMENT '총 전환 비용',
            trigger_type VARCHAR(50) COMMENT '트리거 유형 (context/intervention/natural)',
            intervention_script TEXT COMMENT '사용된 개입 스크립트 JSON',
            success TINYINT(1) DEFAULT NULL COMMENT '전환 성공 여부',
            created_at INT(11) NOT NULL,
            completed_at INT(11) DEFAULT NULL,
            INDEX idx_user (user_id),
            INDEX idx_transition (from_persona, to_persona),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Agent04 페르소나 전환 로그 테이블'";
        
        $DB->execute($sql2);
        $messages[] = "✅ mdl_at_quantum_transition 테이블 생성 완료";
    } else {
        $messages[] = "ℹ️ mdl_at_quantum_transition 테이블이 이미 존재합니다";
    }
    
    // 양자 간섭 분석 로그 테이블
    $tableExists3 = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_at_quantum_interference'");
    
    if (!$tableExists3) {
        $sql3 = "CREATE TABLE IF NOT EXISTS mdl_at_quantum_interference (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            agent_id VARCHAR(50) NOT NULL DEFAULT 'agent04',
            emotion_score FLOAT COMMENT '감정 점수 (0~1)',
            fatigue_score FLOAT COMMENT '피로도 점수 (0~1)',
            amplitude FLOAT COMMENT '결합 진폭',
            theta FLOAT COMMENT '위상 각도 (라디안)',
            constructive_factor FLOAT COMMENT '보강 간섭 계수',
            interference_type VARCHAR(20) COMMENT 'constructive/destructive/neutral',
            recommendation TEXT COMMENT '추천 내용',
            created_at INT(11) NOT NULL,
            INDEX idx_user (user_id),
            INDEX idx_type (interference_type),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Agent04 양자 간섭 분석 로그 테이블'";
        
        $DB->execute($sql3);
        $messages[] = "✅ mdl_at_quantum_interference 테이블 생성 완료";
    } else {
        $messages[] = "ℹ️ mdl_at_quantum_interference 테이블이 이미 존재합니다";
    }
    
    // 골든 타임 개입 로그 테이블
    $tableExists4 = $DB->get_record_sql("SHOW TABLES LIKE 'mdl_at_quantum_intervention'");
    
    if (!$tableExists4) {
        $sql4 = "CREATE TABLE IF NOT EXISTS mdl_at_quantum_intervention (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            agent_id VARCHAR(50) NOT NULL DEFAULT 'agent04',
            golden_time INT COMMENT '계산된 골든 타임 (초)',
            intervention_time INT COMMENT '실제 개입 시간 (초)',
            synergy_at_intervention FLOAT COMMENT '개입 시점 시너지',
            backfire_at_intervention FLOAT COMMENT '개입 시점 역효과',
            intervention_type VARCHAR(50) COMMENT '개입 유형',
            intervention_content TEXT COMMENT '개입 내용',
            result VARCHAR(20) COMMENT 'success/failure/pending',
            user_response TEXT COMMENT '학생 반응',
            created_at INT(11) NOT NULL,
            INDEX idx_user (user_id),
            INDEX idx_result (result),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
        COMMENT='Agent04 골든 타임 개입 로그 테이블'";
        
        $DB->execute($sql4);
        $messages[] = "✅ mdl_at_quantum_intervention 테이블 생성 완료";
    } else {
        $messages[] = "ℹ️ mdl_at_quantum_intervention 테이블이 이미 존재합니다";
    }
    
} catch (Exception $e) {
    $messages[] = "❌ 오류 발생: " . $e->getMessage() . " ({$currentFile}:" . $e->getLine() . ")";
}

// 결과 출력
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>양자 모델링 DB 설정</title>
    <style>
        body { font-family: sans-serif; padding: 40px; background: #0f172a; color: #f1f5f9; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #6366f1; }
        .message { padding: 15px; margin: 10px 0; border-radius: 8px; background: #1e293b; border-left: 4px solid #6366f1; }
        .success { border-left-color: #10b981; }
        .info { border-left-color: #3b82f6; }
        .error { border-left-color: #ef4444; }
        a { color: #6366f1; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>⚛️ 양자 모델링 DB 설정</h1>
        
        <?php foreach ($messages as $msg): ?>
            <div class="message <?php echo strpos($msg, '✅') !== false ? 'success' : (strpos($msg, '❌') !== false ? 'error' : 'info'); ?>">
                <?php echo $msg; ?>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 30px;">
            <a href="qmodeling_dashboard.php">→ 대시보드로 이동</a>
        </div>
    </div>
</body>
</html>

