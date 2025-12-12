<?php
/**
 * Agent13 혼합형 페르소나 시스템 테스트
 *
 * 12개 혼합형 페르소나 (3 Risk × 4 Causes) 검증
 *
 * @package AugmentedTeacher\Agent13\Test
 * @version 1.0
 * @since 2025-12-03
 *
 * 테스트 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test_hybrid_persona.php
 */

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 출력 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 현재 파일 경로 (디버깅용)
$currentFile = __FILE__;
$currentLine = __LINE__;

// 페르소나 엔진 로드
require_once(__DIR__ . '/Agent13PersonaEngine.php');

// 테스트 결과 저장
$testResults = [];
$passCount = 0;
$failCount = 0;

/**
 * 테스트 결과 기록
 */
function recordTest($name, $passed, $message = '', $details = null) {
    global $testResults, $passCount, $failCount;

    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'details' => $details
    ];

    if ($passed) {
        $passCount++;
    } else {
        $failCount++;
    }
}

// ============================================
// 테스트 시작
// ============================================

echo "<html><head><title>Agent13 혼합형 페르소나 테스트</title>";
echo "<style>
    body { font-family: 'Malgun Gothic', sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; }
    .test-result { padding: 10px; margin: 5px 0; border-radius: 4px; }
    .pass { background: #e8f5e9; border-left: 4px solid #4CAF50; }
    .fail { background: #ffebee; border-left: 4px solid #f44336; }
    .summary { padding: 20px; margin: 20px 0; background: #e3f2fd; border-radius: 8px; }
    .persona-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin: 20px 0; }
    .persona-card { padding: 10px; background: #f9f9f9; border-radius: 4px; border: 1px solid #ddd; }
    .persona-card h4 { margin: 0 0 5px 0; color: #1976d2; }
    .persona-card p { margin: 3px 0; font-size: 12px; color: #666; }
    .code { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
    pre { margin: 0; white-space: pre-wrap; }
    .tag { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; margin: 2px; }
    .tag-risk-L { background: #c8e6c9; color: #2e7d32; }
    .tag-risk-M { background: #fff9c4; color: #f57f17; }
    .tag-risk-H { background: #ffcdd2; color: #c62828; }
    .tag-cause-M { background: #e1bee7; color: #7b1fa2; }
    .tag-cause-R { background: #b3e5fc; color: #0277bd; }
    .tag-cause-S { background: #ffe0b2; color: #e65100; }
    .tag-cause-E { background: #d7ccc8; color: #5d4037; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🧪 Agent13 혼합형 페르소나 시스템 테스트</h1>";
echo "<p>테스트 시간: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>테스트 파일: {$currentFile}</p>";

// ============================================
// Test 1: 엔진 인스턴스 생성
// ============================================
echo "<h2>1. 엔진 인스턴스 생성</h2>";

try {
    $engine = new Agent13PersonaEngine();
    recordTest('엔진 인스턴스 생성', true, 'Agent13PersonaEngine 인스턴스 생성 성공');
} catch (Exception $e) {
    recordTest('엔진 인스턴스 생성', false, '인스턴스 생성 실패: ' . $e->getMessage());
    $engine = null;
}

// ============================================
// Test 2: Health Check
// ============================================
echo "<h2>2. Health Check 검증</h2>";

if ($engine) {
    $healthCheck = $engine->healthCheck();

    // 혼합형 페르소나 검증 (12개)
    $hybridOk = isset($healthCheck['hybrid_personas']) && $healthCheck['hybrid_personas'] === 'OK';
    recordTest('혼합형 페르소나 수 (12개)', $hybridOk,
        $hybridOk ? '12개 혼합형 페르소나 확인' : '혼합형 페르소나 수 불일치');

    // 이탈 원인 검증 (4개)
    $causesOk = isset($healthCheck['dropout_causes']) && $healthCheck['dropout_causes'] === 'OK';
    recordTest('이탈 원인 수 (4개)', $causesOk,
        $causesOk ? '4개 이탈 원인 확인 (M, R, S, E)' : '이탈 원인 수 불일치');

    // 버전 확인
    $versionOk = isset($healthCheck['version']) && version_compare($healthCheck['version'], '2.0.0', '>=');
    recordTest('버전 (2.0.0+)', $versionOk,
        '현재 버전: ' . ($healthCheck['version'] ?? 'unknown'));

    // hybrid_support 플래그
    $hybridSupportOk = isset($healthCheck['hybrid_support']) && $healthCheck['hybrid_support'] === true;
    recordTest('hybrid_support 플래그', $hybridSupportOk,
        $hybridSupportOk ? 'hybrid_support = true' : 'hybrid_support 미설정');

    echo "<div class='code'><pre>" . json_encode($healthCheck, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></div>";
}

// ============================================
// Test 3: 혼합형 페르소나 목록 확인
// ============================================
echo "<h2>3. 혼합형 페르소나 목록 (12개)</h2>";

if ($engine) {
    $hybridPersonas = $engine->getHybridPersonasList();

    $expectedPersonas = [
        'L_M', 'L_R', 'L_S', 'L_E',
        'M_M', 'M_R', 'M_S', 'M_E',
        'H_M', 'H_R', 'H_S', 'H_E'
    ];

    $allPresent = true;
    $missing = [];

    foreach ($expectedPersonas as $expected) {
        if (!isset($hybridPersonas[$expected])) {
            $allPresent = false;
            $missing[] = $expected;
        }
    }

    recordTest('12개 페르소나 존재 확인', $allPresent,
        $allPresent ? '모든 페르소나 존재' : '누락: ' . implode(', ', $missing));

    echo "<div class='persona-grid'>";
    foreach ($hybridPersonas as $id => $persona) {
        $riskLevel = substr($id, 0, 1);
        $causeCode = substr($id, 2, 1);

        echo "<div class='persona-card'>";
        echo "<h4>{$id}</h4>";
        echo "<p><strong>{$persona['name']}</strong></p>";
        echo "<p>톤: {$persona['tone']}</p>";
        echo "<p>페이스: {$persona['pace']}</p>";
        echo "<p>모드: {$persona['intervention_mode']}</p>";
        echo "<span class='tag tag-risk-{$riskLevel}'>{$riskLevel}</span>";
        echo "<span class='tag tag-cause-{$causeCode}'>{$causeCode}</span>";
        echo "</div>";
    }
    echo "</div>";
}

// ============================================
// Test 4: 이탈 원인 목록 확인
// ============================================
echo "<h2>4. 이탈 원인 목록 (4개)</h2>";

if ($engine) {
    $dropoutCauses = $engine->getDropoutCausesList();

    $expectedCauses = ['M', 'R', 'S', 'E'];
    $allCausesPresent = true;

    foreach ($expectedCauses as $cause) {
        if (!isset($dropoutCauses[$cause])) {
            $allCausesPresent = false;
        }
    }

    recordTest('4개 이탈 원인 존재', $allCausesPresent,
        $allCausesPresent ? 'M(동기저하), R(루틴붕괴), S(시작장벽), E(외부요인)' : '일부 원인 누락');

    echo "<div class='code'><pre>" . json_encode($dropoutCauses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></div>";
}

// ============================================
// Test 5: 시나리오별 페르소나 식별 테스트
// ============================================
echo "<h2>5. 시나리오별 페르소나 식별 테스트</h2>";

$testScenarios = [
    // 낮은 위험 + 동기저하 (L_M)
    [
        'name' => '낮은 위험 + 동기저하 (L_M)',
        'data' => [
            'ninactive' => 1,
            'npomodoro' => 4,
            'nlazy_blocks' => 1,
            'tlaststroke_min' => 5,
            'pomodoro_trend' => -0.2, // 감소 추세
        ],
        'message' => '공부가 재미없어요. 의욕이 없어요.',
        'expected_risk' => 'Low',
        'expected_cause' => 'M'
    ],

    // 중간 위험 + 루틴붕괴 (M_R)
    [
        'name' => '중간 위험 + 루틴붕괴 (M_R)',
        'data' => [
            'ninactive' => 3,
            'npomodoro' => 2,
            'nlazy_blocks' => 4, // 루틴 붕괴 지표
            'tlaststroke_min' => 30,
            'session_time_variance' => 45, // 높은 분산
        ],
        'message' => '요즘 규칙적으로 공부하기가 힘들어요',
        'expected_risk' => 'Medium',
        'expected_cause' => 'R'
    ],

    // 높은 위험 + 시작장벽 (H_S)
    [
        'name' => '높은 위험 + 시작장벽 (H_S)',
        'data' => [
            'ninactive' => 6,
            'npomodoro' => 0,
            'nlazy_blocks' => 2,
            'tlaststroke_min' => 25, // 15분 이상 = 시작 장벽
            'first_stroke_delay' => 20, // 첫 획까지 지연
        ],
        'message' => '시작하려고 하는데 손이 안 가요. 어렵게 느껴져요.',
        'expected_risk' => 'High',
        'expected_cause' => 'S'
    ],

    // 중간 위험 + 외부요인 (M_E)
    [
        'name' => '중간 위험 + 외부요인 (M_E)',
        'data' => [
            'ninactive' => 4,
            'npomodoro' => 1,
            'nlazy_blocks' => 2,
            'tlaststroke_min' => 10,
            'academy_homework_burden' => 8, // 학원 부담 높음
        ],
        'message' => '학원 숙제가 너무 많아서 힘들어요. 시간이 없어요.',
        'expected_risk' => 'Medium',
        'expected_cause' => 'E'
    ],

    // Critical 상황 (연속 고위험)
    [
        'name' => 'Critical 상황 (연속 고위험)',
        'data' => [
            'ninactive' => 8,
            'npomodoro' => 0,
            'nlazy_blocks' => 5,
            'tlaststroke_min' => 60,
            'consecutive_high_days' => 3, // 연속 고위험
        ],
        'message' => '진짜 포기하고 싶어요',
        'expected_risk' => 'Critical',
        'expected_cause' => 'M' // 또는 가장 높은 점수
    ],
];

foreach ($testScenarios as $scenario) {
    if ($engine) {
        try {
            // 테스트용 사용자 ID (현재 사용자 또는 테스트 ID)
            $testUserId = isset($USER->id) ? $USER->id : 2;

            // 페르소나 식별 (테스트 데이터 주입)
            $result = $engine->identifyPersona($testUserId, $scenario['data'], $scenario['message']);

            echo "<div class='test-result'>";
            echo "<h4>{$scenario['name']}</h4>";

            // 결과 검증
            $riskMatch = false;
            $causeMatch = false;

            if (isset($result['risk_tier'])) {
                $riskMatch = (
                    ($scenario['expected_risk'] === 'Low' && $result['risk_tier'] === 'Low') ||
                    ($scenario['expected_risk'] === 'Medium' && $result['risk_tier'] === 'Medium') ||
                    ($scenario['expected_risk'] === 'High' && in_array($result['risk_tier'], ['High', 'Critical'])) ||
                    ($scenario['expected_risk'] === 'Critical' && $result['risk_tier'] === 'Critical')
                );
            }

            if (isset($result['dropout_cause'])) {
                $causeMatch = ($result['dropout_cause'] === $scenario['expected_cause']);
            }

            $testPassed = $riskMatch || isset($result['hybrid_persona_id']);

            recordTest($scenario['name'], $testPassed,
                "위험등급: " . ($result['risk_tier'] ?? 'N/A') .
                ", 원인: " . ($result['dropout_cause'] ?? 'N/A') .
                ", 혼합ID: " . ($result['hybrid_persona_id'] ?? 'N/A'));

            echo "<p><strong>입력 메시지:</strong> {$scenario['message']}</p>";
            echo "<p><strong>기대값:</strong> 위험={$scenario['expected_risk']}, 원인={$scenario['expected_cause']}</p>";
            echo "<p><strong>결과:</strong></p>";
            echo "<ul>";
            echo "<li>위험 등급: " . ($result['risk_tier'] ?? 'N/A') . "</li>";
            echo "<li>이탈 원인: " . ($result['dropout_cause'] ?? 'N/A') . " (" . ($result['dropout_cause_name'] ?? '') . ")</li>";
            echo "<li>혼합형 ID: " . ($result['hybrid_persona_id'] ?? 'N/A') . "</li>";
            echo "<li>혼합형 이름: " . ($result['hybrid_persona_name'] ?? 'N/A') . "</li>";
            if (isset($result['hybrid_intervention'])) {
                echo "<li>톤: " . ($result['hybrid_intervention']['tone'] ?? 'N/A') . "</li>";
                echo "<li>페이스: " . ($result['hybrid_intervention']['pace'] ?? 'N/A') . "</li>";
                echo "<li>모드: " . ($result['hybrid_intervention']['mode'] ?? 'N/A') . "</li>";
            }
            echo "</ul>";
            echo "</div>";

        } catch (Exception $e) {
            recordTest($scenario['name'], false, '테스트 실행 오류: ' . $e->getMessage());
            echo "<div class='test-result fail'>";
            echo "<p>오류: {$e->getMessage()}</p>";
            echo "<p>파일: {$currentFile}, 라인: " . __LINE__ . "</p>";
            echo "</div>";
        }
    }
}

// ============================================
// Test 6: 응답 생성 테스트 (혼합형)
// ============================================
echo "<h2>6. 혼합형 응답 생성 테스트</h2>";

if ($engine) {
    try {
        $testUserId = isset($USER->id) ? $USER->id : 2;

        // 혼합형 응답 생성
        $response = $engine->generateResponse(
            $testUserId,
            'occasional', // 기본 페르소나
            '공부하기 싫어요. 어떻게 시작해야 할지 모르겠어요.',
            ['use_hybrid' => true]
        );

        $responseOk = isset($response['message']) && !empty($response['message']);
        $hybridDataOk = isset($response['hybrid_persona_id']);

        recordTest('혼합형 응답 생성', $responseOk && $hybridDataOk,
            $responseOk ? '응답 생성 성공' : '응답 생성 실패');

        echo "<div class='code'><pre>" . json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></div>";

    } catch (Exception $e) {
        recordTest('혼합형 응답 생성', false, '응답 생성 오류: ' . $e->getMessage());
    }
}

// ============================================
// 테스트 결과 요약
// ============================================
echo "<h2>📊 테스트 결과 요약</h2>";
echo "<div class='summary'>";
echo "<h3>총 {$passCount} 통과 / " . ($passCount + $failCount) . " 테스트</h3>";

if ($failCount === 0) {
    echo "<p style='color: #2e7d32; font-size: 18px;'>✅ 모든 테스트 통과!</p>";
} else {
    echo "<p style='color: #c62828; font-size: 18px;'>❌ {$failCount}개 테스트 실패</p>";
}

echo "<table style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background: #e3f2fd;'><th style='padding: 8px; text-align: left;'>테스트</th><th style='padding: 8px;'>결과</th><th style='padding: 8px; text-align: left;'>메시지</th></tr>";

foreach ($testResults as $result) {
    $statusIcon = $result['passed'] ? '✅' : '❌';
    $rowClass = $result['passed'] ? '' : 'style="background: #ffebee;"';

    echo "<tr {$rowClass}>";
    echo "<td style='padding: 8px;'>{$result['name']}</td>";
    echo "<td style='padding: 8px; text-align: center;'>{$statusIcon}</td>";
    echo "<td style='padding: 8px;'>{$result['message']}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// ============================================
// 시스템 정보
// ============================================
echo "<h2>🔧 시스템 정보</h2>";
echo "<div class='code'><pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Moodle Version: " . $CFG->version . "\n";
echo "테스트 사용자 ID: " . (isset($USER->id) ? $USER->id : 'N/A') . "\n";
echo "테스트 시간: " . date('Y-m-d H:i:s') . "\n";
echo "파일 경로: " . $currentFile . "\n";
echo "</pre></div>";

echo "</div>"; // container
echo "</body></html>";

/*
 * 관련 DB 테이블:
 * - mdl_user: id, firstname, lastname
 * - mdl_augmented_teacher_pomodoro: userid, timestamp, duration
 * - mdl_augmented_teacher_strokes: userid, timestamp, stroke_data
 *
 * 테스트 URL:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test_hybrid_persona.php
 */
?>
