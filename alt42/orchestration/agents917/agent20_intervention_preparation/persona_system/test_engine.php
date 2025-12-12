<?php
/**
 * Agent20 PersonaEngine 테스트
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/test_engine.php
 *
 * @package AugmentedTeacher\Agent20\Test
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 관리자 권한 확인
if (!is_siteadmin()) {
    die("관리자 권한이 필요합니다. [" . __FILE__ . ":" . __LINE__ . "]");
}

$currentFile = __FILE__;

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Agent20 PersonaEngine Test</title>";
echo "<style>
body { font-family: 'Segoe UI', monospace; padding: 20px; background: #1a1a2e; color: #eee; }
.success { color: #4ade80; }
.error { color: #f87171; }
.info { color: #60a5fa; }
.warn { color: #fbbf24; }
pre { background: #16213e; padding: 15px; border-radius: 8px; overflow: auto; }
.test-section { margin: 20px 0; padding: 15px; background: #0f3460; border-radius: 8px; }
h2 { border-bottom: 2px solid #e94560; padding-bottom: 10px; }
</style></head><body>";

echo "<h1>🧪 Agent20 PersonaEngine 테스트</h1>";

$testResults = [];
$allPassed = true;

// ========================================
// 테스트 1: 엔진 초기화
// ========================================
echo "<div class='test-section'><h2>테스트 1: 엔진 초기화</h2>";

try {
    require_once(__DIR__ . '/engine/Agent20PersonaEngine.php');
    $engine = new Agent20PersonaEngine(['debug_mode' => true]);
    echo "<p class='success'>✓ Agent20PersonaEngine 초기화 성공</p>";
    $testResults['initialization'] = true;
} catch (Exception $e) {
    echo "<p class='error'>✗ 초기화 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>위치: {$currentFile}:" . __LINE__ . "</p>";
    $testResults['initialization'] = false;
    $allPassed = false;
}
echo "</div>";

// ========================================
// 테스트 2: 학생 상태 분석
// ========================================
echo "<div class='test-section'><h2>테스트 2: 학생 상태 분석</h2>";

if ($testResults['initialization'] ?? false) {
    try {
        // 테스트 학생 상태 (불안 + 높은 오류율)
        $testState = [
            'emotion' => 'frustration',
            'cognitive_load' => 0.8,
            'engagement' => 0.3,
            'error_rate' => 0.6,
            'help_requests' => 2,
            'time_on_task' => 300,
            'current_activity' => 'quiz'
        ];

        $result = $engine->analyzeAndPrepare($USER->id, $testState);

        echo "<p class='info'>입력 상태:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($testState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        echo "<p class='info'>분석 결과:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        if ($result['success'] ?? false) {
            echo "<p class='success'>✓ 분석 성공</p>";
            if ($result['needs_intervention'] ?? false) {
                echo "<p class='success'>✓ 개입 필요성 감지됨: " . ($result['strategy']['name'] ?? 'unknown') . "</p>";
            }
            $testResults['analysis'] = true;
        } else {
            echo "<p class='error'>✗ 분석 실패: " . ($result['error'] ?? 'Unknown error') . "</p>";
            $testResults['analysis'] = false;
            $allPassed = false;
        }

    } catch (Exception $e) {
        echo "<p class='error'>✗ 테스트 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        $testResults['analysis'] = false;
        $allPassed = false;
    }
} else {
    echo "<p class='warn'>⚠️ 엔진 초기화 실패로 테스트 건너뜀</p>";
}
echo "</div>";

// ========================================
// 테스트 3: 정상 상태 분석 (개입 불필요)
// ========================================
echo "<div class='test-section'><h2>테스트 3: 정상 상태 분석</h2>";

if ($testResults['initialization'] ?? false) {
    try {
        // 정상 상태
        $normalState = [
            'emotion' => 'neutral',
            'cognitive_load' => 0.4,
            'engagement' => 0.8,
            'error_rate' => 0.1,
            'help_requests' => 0,
            'time_on_task' => 60,
            'current_activity' => 'learning'
        ];

        $result = $engine->analyzeAndPrepare($USER->id, $normalState);

        echo "<p class='info'>입력 상태 (정상):</p>";
        echo "<pre>" . htmlspecialchars(json_encode($normalState, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        echo "<p class='info'>분석 결과:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        if ($result['success'] ?? false) {
            echo "<p class='success'>✓ 분석 성공</p>";
            if (!($result['needs_intervention'] ?? true)) {
                echo "<p class='success'>✓ 올바르게 개입 불필요로 판단</p>";
            }
            $testResults['normal_analysis'] = true;
        } else {
            $testResults['normal_analysis'] = false;
            $allPassed = false;
        }

    } catch (Exception $e) {
        echo "<p class='error'>✗ 테스트 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        $testResults['normal_analysis'] = false;
        $allPassed = false;
    }
} else {
    echo "<p class='warn'>⚠️ 엔진 초기화 실패로 테스트 건너뜀</p>";
}
echo "</div>";

// ========================================
// 테스트 4: 메시지 처리
// ========================================
echo "<div class='test-section'><h2>테스트 4: 메시지 처리</h2>";

if ($testResults['initialization'] ?? false) {
    try {
        $testMessage = "이 문제가 너무 어려워요. 도와주세요.";
        $sessionData = [
            'emotion' => 'confusion',
            'cognitive_load' => 0.7
        ];

        $result = $engine->process($USER->id, $testMessage, $sessionData);

        echo "<p class='info'>입력 메시지: \"{$testMessage}\"</p>";
        echo "<p class='info'>처리 결과:</p>";
        echo "<pre>" . htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

        if ($result['text'] ?? false) {
            echo "<p class='success'>✓ 응답 생성 성공</p>";
            $testResults['message_processing'] = true;
        } else {
            echo "<p class='warn'>⚠️ 응답 없음</p>";
            $testResults['message_processing'] = false;
        }

    } catch (Exception $e) {
        echo "<p class='error'>✗ 테스트 실패: " . htmlspecialchars($e->getMessage()) . "</p>";
        $testResults['message_processing'] = false;
        $allPassed = false;
    }
} else {
    echo "<p class='warn'>⚠️ 엔진 초기화 실패로 테스트 건너뜀</p>";
}
echo "</div>";

// ========================================
// 결과 요약
// ========================================
echo "<hr><h2>📊 테스트 결과 요약</h2>";
echo "<pre>";
foreach ($testResults as $test => $passed) {
    $icon = $passed ? '✓' : '✗';
    $status = $passed ? 'PASS' : 'FAIL';
    echo "{$icon} {$test}: {$status}\n";
}
echo "</pre>";

if ($allPassed) {
    echo "<p class='success'><strong>🎉 모든 테스트 통과!</strong></p>";
} else {
    echo "<p class='warn'><strong>⚠️ 일부 테스트 실패. 로그를 확인하세요.</strong></p>";
}

// 유용한 링크
echo "<h2>🔗 관련 링크</h2>";
echo "<ul>";
echo "<li><a href='../../../ontology_engineering/persona_engine/db/db_setup.php' style='color:#60a5fa;'>DB 테이블 설정</a></li>";
echo "<li><a href='api/analyze.php?action=status' style='color:#60a5fa;'>API 상태 확인</a></li>";
echo "</ul>";

echo "</body></html>";

/*
 * 테스트 항목:
 * 1. Agent20PersonaEngine 초기화
 * 2. 학생 상태 분석 (개입 필요 케이스)
 * 3. 학생 상태 분석 (정상 케이스)
 * 4. 메시지 처리 및 응답 생성
 */
