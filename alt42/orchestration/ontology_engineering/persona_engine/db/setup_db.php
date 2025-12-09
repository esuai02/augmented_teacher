<?php
/**
 * Persona Engine DB Setup
 * 
 * 페르소나 엔진 DB 테이블 생성/마이그레이션 스크립트
 * 
 * 사용법: 브라우저에서 직접 실행 또는 CLI
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/db/setup_db.php
 * 
 * @package AugmentedTeacher\PersonaEngine\DB
 * @version 1.0
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 관리자 권한 확인 (보안)
require_login();
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die("관리자 권한이 필요합니다.");
}

$currentFile = __FILE__;

// 실행 모드 확인
$action = isset($_GET['action']) ? $_GET['action'] : 'check';
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// 결과 저장
$results = [];

/**
 * 테이블 존재 여부 확인
 */
function tableExists($tableName) {
    global $DB;
    try {
        $tables = $DB->get_tables();
        return in_array($tableName, $tables);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * 테이블 생성
 */
function createTable($tableName, $sql) {
    global $DB, $currentFile;
    try {
        $DB->execute($sql);
        return ['success' => true, 'message' => "테이블 '{$tableName}' 생성 완료"];
    } catch (Exception $e) {
        return [
            'success' => false, 
            'message' => "테이블 '{$tableName}' 생성 실패: " . $e->getMessage(),
            'location' => $currentFile . ':' . __LINE__
        ];
    }
}

// 테이블 정의
$tables = [
    'at_agent_persona_state' => "
        CREATE TABLE IF NOT EXISTS {at_agent_persona_state} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            agent_id VARCHAR(50) NOT NULL,
            persona_id VARCHAR(100) NOT NULL,
            confidence DECIMAL(5,4) DEFAULT 0.5000,
            situation_code VARCHAR(100) DEFAULT 'default',
            emotional_state VARCHAR(50) DEFAULT 'neutral',
            intent VARCHAR(100) DEFAULT NULL,
            context_data LONGTEXT,
            session_id VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_user_agent (user_id, agent_id),
            KEY idx_agent_id (agent_id),
            KEY idx_persona_id (persona_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'at_agent_messages' => "
        CREATE TABLE IF NOT EXISTS {at_agent_messages} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_agent VARCHAR(50) NOT NULL,
            target_agent VARCHAR(50) DEFAULT NULL,
            user_id BIGINT(10) UNSIGNED DEFAULT NULL,
            message_type VARCHAR(50) NOT NULL,
            event_name VARCHAR(100) DEFAULT NULL,
            payload LONGTEXT NOT NULL,
            priority TINYINT(3) UNSIGNED DEFAULT 5,
            status VARCHAR(20) DEFAULT 'pending',
            processed_at DATETIME DEFAULT NULL,
            expires_at DATETIME DEFAULT NULL,
            retry_count TINYINT(3) UNSIGNED DEFAULT 0,
            error_message LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_target_status (target_agent, status),
            KEY idx_source (source_agent),
            KEY idx_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'at_persona_log' => "
        CREATE TABLE IF NOT EXISTS {at_persona_log} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(10) UNSIGNED NOT NULL,
            agent_id VARCHAR(50) NOT NULL,
            session_id VARCHAR(255) DEFAULT NULL,
            request_type VARCHAR(50) NOT NULL,
            input_data LONGTEXT,
            persona_identified VARCHAR(100) DEFAULT NULL,
            confidence DECIMAL(5,4) DEFAULT NULL,
            processing_time_ms INT UNSIGNED DEFAULT NULL,
            output_data LONGTEXT,
            success TINYINT(1) DEFAULT 1,
            error_message LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY idx_user_agent (user_id, agent_id),
            KEY idx_session (session_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ",
    'at_agent_config' => "
        CREATE TABLE IF NOT EXISTS {at_agent_config} (
            id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            agent_id VARCHAR(50) NOT NULL,
            config_key VARCHAR(100) NOT NULL,
            config_value LONGTEXT NOT NULL,
            value_type VARCHAR(20) DEFAULT 'string',
            description VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY idx_agent_key (agent_id, config_key),
            KEY idx_agent (agent_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    "
];

// HTML 출력 시작
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Persona Engine DB Setup</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .status { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .status.exists { background: #d4edda; color: #155724; }
        .status.missing { background: #f8d7da; color: #721c24; }
        .status.created { background: #cce5ff; color: #004085; }
        .status.error { background: #f8d7da; color: #721c24; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px 10px 0; border: none; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 13px; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Persona Engine DB Setup</h1>
    <p>파일 위치: <code><?php echo $currentFile; ?></code></p>
    
    <?php if ($action === 'check'): ?>
        <h2>📋 테이블 상태 확인</h2>
        <table>
            <tr><th>테이블명</th><th>상태</th></tr>
            <?php foreach ($tables as $name => $sql): 
                $exists = tableExists($name);
            ?>
            <tr>
                <td><code><?php echo $name; ?></code></td>
                <td class="status <?php echo $exists ? 'exists' : 'missing'; ?>">
                    <?php echo $exists ? '✅ 존재함' : '❌ 없음'; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <h3>작업 선택</h3>
        <a href="?action=create" class="btn btn-primary">테이블 생성 (미리보기)</a>
        <a href="?action=drop" class="btn btn-danger">테이블 삭제 (미리보기)</a>
        
    <?php elseif ($action === 'create'): ?>
        <h2>📦 테이블 생성</h2>
        
        <?php if (!$confirm): ?>
            <p>다음 테이블을 생성합니다:</p>
            <ul>
                <?php foreach ($tables as $name => $sql): 
                    if (!tableExists($name)):
                ?>
                    <li><code><?php echo $name; ?></code></li>
                <?php endif; endforeach; ?>
            </ul>
            <a href="?action=create&confirm=yes" class="btn btn-success">확인 및 생성</a>
            <a href="?action=check" class="btn btn-primary">취소</a>
        <?php else: ?>
            <?php
            foreach ($tables as $name => $sql) {
                if (!tableExists($name)) {
                    $result = createTable($name, $sql);
                    $results[] = $result;
                    echo '<div class="status ' . ($result['success'] ? 'created' : 'error') . '">';
                    echo $result['message'];
                    if (isset($result['location'])) echo '<br><small>' . $result['location'] . '</small>';
                    echo '</div>';
                } else {
                    echo '<div class="status exists">✅ ' . $name . ' - 이미 존재함</div>';
                }
            }
            ?>
            <a href="?action=check" class="btn btn-primary">상태 확인으로 돌아가기</a>
        <?php endif; ?>
        
    <?php elseif ($action === 'drop'): ?>
        <h2>⚠️ 테이블 삭제</h2>
        
        <?php if (!$confirm): ?>
            <p style="color: red; font-weight: bold;">경고: 이 작업은 모든 데이터를 삭제합니다!</p>
            <p>다음 테이블을 삭제합니다:</p>
            <ul>
                <?php foreach ($tables as $name => $sql): 
                    if (tableExists($name)):
                ?>
                    <li><code><?php echo $name; ?></code></li>
                <?php endif; endforeach; ?>
            </ul>
            <a href="?action=drop&confirm=yes" class="btn btn-danger">확인 및 삭제</a>
            <a href="?action=check" class="btn btn-primary">취소</a>
        <?php else: ?>
            <?php
            foreach (array_reverse(array_keys($tables)) as $name) {
                if (tableExists($name)) {
                    try {
                        $DB->execute("DROP TABLE IF EXISTS {{$name}}");
                        echo '<div class="status created">🗑️ ' . $name . ' 삭제 완료</div>';
                    } catch (Exception $e) {
                        echo '<div class="status error">❌ ' . $name . ' 삭제 실패: ' . $e->getMessage() . '</div>';
                    }
                }
            }
            ?>
            <a href="?action=check" class="btn btn-primary">상태 확인으로 돌아가기</a>
        <?php endif; ?>
    <?php endif; ?>
    
    <hr style="margin-top: 30px;">
    <p><small>Persona Engine v1.0 | DB Setup Tool</small></p>
</div>
</body>
</html>
<?php
/**
 * 관련 DB 테이블:
 * - mdl_at_agent_persona_state: 에이전트별 페르소나 상태
 * - mdl_at_agent_messages: 에이전트 간 메시지
 * - mdl_at_persona_log: 처리 로그
 * - mdl_at_agent_config: 에이전트 설정
 */
