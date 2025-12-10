<?php
/**
 * 양자 모델링 관련 DB 테이블 생성 스크립트
 *
 * 실행 방법:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/install/create_quantum_tables.php
 *
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 *
 * 생성되는 테이블:
 * 1. mdl_alt42_quantum_solutions - 풀이 방법 저장
 * 2. mdl_alt42_quantum_misconceptions - 오개념 저장
 * 3. mdl_alt42_quantum_collapse_circuit - 양자 붕괴 회로 상태
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자만 실행 가능
if (!is_siteadmin($USER->id)) {
    die("관리자만 실행할 수 있습니다.");
}

header('Content-Type: text/html; charset=utf-8');

echo "<html><head><title>Quantum Tables Setup</title>";
echo "<style>
    body { font-family: 'Pretendard', sans-serif; padding: 20px; background: #0f172a; color: #f1f5f9; }
    .success { color: #10b981; }
    .error { color: #ef4444; }
    .info { color: #6366f1; }
    pre { background: #1e293b; padding: 15px; border-radius: 8px; overflow-x: auto; }
    h1 { background: linear-gradient(135deg, #6366f1, #8b5cf6); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    .card { background: #1e293b; border-radius: 12px; padding: 20px; margin: 20px 0; border: 1px solid #334155; }
</style></head><body>";

echo "<h1>양자 모델링 DB 테이블 생성</h1>";

$results = [];

// 1. mdl_alt42_quantum_solutions 테이블 생성
echo "<div class='card'>";
echo "<h2>1. mdl_alt42_quantum_solutions</h2>";

$sql1 = "CREATE TABLE IF NOT EXISTS mdl_alt42_quantum_solutions (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    content_id BIGINT(10) UNSIGNED NOT NULL,
    student_id BIGINT(10) UNSIGNED NOT NULL DEFAULT 0,
    solution_type VARCHAR(50) NOT NULL DEFAULT 'general',
    solution_data LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_content (content_id),
    INDEX idx_student (student_id),
    INDEX idx_type (solution_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='양자 모델링 - 다양한 풀이 방법 저장 테이블'";

try {
    $DB->execute($sql1);
    echo "<p class='success'>✅ mdl_alt42_quantum_solutions 테이블 생성 완료</p>";
    $results['quantum_solutions'] = 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Table') !== false) {
        echo "<p class='info'>ℹ️ mdl_alt42_quantum_solutions 테이블이 이미 존재합니다</p>";
        $results['quantum_solutions'] = 'exists';
    } else {
        echo "<p class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
        $results['quantum_solutions'] = 'error';
    }
}

echo "<pre>" . htmlspecialchars($sql1) . "</pre>";
echo "</div>";

// 2. mdl_alt42_quantum_misconceptions 테이블 생성
echo "<div class='card'>";
echo "<h2>2. mdl_alt42_quantum_misconceptions</h2>";

$sql2 = "CREATE TABLE IF NOT EXISTS mdl_alt42_quantum_misconceptions (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    content_id BIGINT(10) UNSIGNED NOT NULL,
    student_id BIGINT(10) UNSIGNED NOT NULL DEFAULT 0,
    misconception_type VARCHAR(50) NOT NULL DEFAULT 'general',
    misconception_data LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_content (content_id),
    INDEX idx_student (student_id),
    INDEX idx_type (misconception_type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='양자 모델링 - 오개념 풀이 저장 테이블'";

try {
    $DB->execute($sql2);
    echo "<p class='success'>✅ mdl_alt42_quantum_misconceptions 테이블 생성 완료</p>";
    $results['quantum_misconceptions'] = 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Table') !== false) {
        echo "<p class='info'>ℹ️ mdl_alt42_quantum_misconceptions 테이블이 이미 존재합니다</p>";
        $results['quantum_misconceptions'] = 'exists';
    } else {
        echo "<p class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
        $results['quantum_misconceptions'] = 'error';
    }
}

echo "<pre>" . htmlspecialchars($sql2) . "</pre>";
echo "</div>";

// 3. mdl_alt42_quantum_collapse_circuit 테이블 생성
echo "<div class='card'>";
echo "<h2>3. mdl_alt42_quantum_collapse_circuit</h2>";

$sql3 = "CREATE TABLE IF NOT EXISTS mdl_alt42_quantum_collapse_circuit (
    id BIGINT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    content_id BIGINT(10) UNSIGNED NOT NULL,
    circuit_state LONGTEXT NOT NULL,
    solution_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
    misconception_count INT(10) UNSIGNED NOT NULL DEFAULT 0,
    last_updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE INDEX idx_content_unique (content_id),
    INDEX idx_updated (last_updated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='양자 모델링 - 양자 붕괴 회로 상태 테이블'";

try {
    $DB->execute($sql3);
    echo "<p class='success'>✅ mdl_alt42_quantum_collapse_circuit 테이블 생성 완료</p>";
    $results['quantum_circuit'] = 'success';
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Table') !== false) {
        echo "<p class='info'>ℹ️ mdl_alt42_quantum_collapse_circuit 테이블이 이미 존재합니다</p>";
        $results['quantum_circuit'] = 'exists';
    } else {
        echo "<p class='error'>❌ 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
        $results['quantum_circuit'] = 'error';
    }
}

echo "<pre>" . htmlspecialchars($sql3) . "</pre>";
echo "</div>";

// 결과 요약
echo "<div class='card'>";
echo "<h2>📊 결과 요약</h2>";
echo "<table style='width:100%; border-collapse: collapse;'>";
echo "<tr style='border-bottom: 1px solid #334155;'><th style='padding: 10px; text-align: left;'>테이블</th><th style='padding: 10px; text-align: left;'>상태</th></tr>";

foreach ($results as $table => $status) {
    $statusClass = $status === 'success' ? 'success' : ($status === 'exists' ? 'info' : 'error');
    $statusText = $status === 'success' ? '생성됨' : ($status === 'exists' ? '이미 존재' : '오류');
    echo "<tr><td style='padding: 10px;'>mdl_" . htmlspecialchars($table) . "</td>";
    echo "<td style='padding: 10px;' class='$statusClass'>$statusText</td></tr>";
}

echo "</table>";
echo "</div>";

// 테이블 확인
echo "<div class='card'>";
echo "<h2>🔍 테이블 구조 확인</h2>";

$tables = ['alt42_quantum_solutions', 'alt42_quantum_misconceptions', 'alt42_quantum_collapse_circuit'];

foreach ($tables as $tableName) {
    try {
        $columns = $DB->get_records_sql("SHOW COLUMNS FROM {" . $tableName . "}");
        echo "<h3>" . htmlspecialchars($tableName) . "</h3>";
        echo "<table style='width:100%; border-collapse: collapse; margin-bottom: 20px;'>";
        echo "<tr style='background: #334155;'><th style='padding: 8px; text-align: left;'>Field</th><th style='padding: 8px; text-align: left;'>Type</th><th style='padding: 8px; text-align: left;'>Null</th><th style='padding: 8px; text-align: left;'>Key</th><th style='padding: 8px; text-align: left;'>Default</th></tr>";

        foreach ($columns as $col) {
            echo "<tr style='border-bottom: 1px solid #334155;'>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($col->field) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($col->type) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($col->null) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($col->key) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($col->default ?? 'NULL') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ $tableName 테이블 조회 오류: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "</div>";

// 링크
echo "<div class='card'>";
echo "<h2>🔗 관련 링크</h2>";
echo "<ul style='list-style: none; padding: 0;'>";
echo "<li style='margin: 10px 0;'><a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/' style='color: #6366f1;'>→ Orchestration 메인</a></li>";
echo "<li style='margin: 10px 0;'><a href='https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/quantum_modeling.php?id=test' style='color: #6366f1;'>→ Quantum Modeling 테스트</a></li>";
echo "</ul>";
echo "</div>";

echo "</body></html>";
