<?php
/**
 * Agent17 Persona Engine 테스트 스크립트
 *
 * 서버에서 직접 실행하여 엔진 동작을 검증합니다.
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/tests/test_engine.php
 *
 * @package AugmentedTeacher\Agent17\Tests
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

$currentFile = __FILE__;

// HTML 헤더
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Agent17 Persona Engine Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
        .test { margin: 10px 0; padding: 10px; border-radius: 5px; }
        .pass { background: #16213e; border-left: 4px solid #0f0; }
        .fail { background: #16213e; border-left: 4px solid #f00; }
        .info { background: #16213e; border-left: 4px solid #0af; }
        h1 { color: #e94560; }
        h2 { color: #0f3460; background: #e94560; padding: 10px; border-radius: 5px; }
        pre { background: #0f0f0f; padding: 10px; overflow-x: auto; }
        .label { font-weight: bold; color: #e94560; }
    </style>
</head>
<body>
<h1>🧪 Agent17 Persona Engine 테스트</h1>
<p>실행 시간: " . date('Y-m-d H:i:s') . "</p>
<p>파일 위치: {$currentFile}</p>
<hr>
";

$testResults = [];

// ========================================
// 테스트 1: Moodle 환경 로드
// ========================================
echo "<h2>테스트 1: Moodle 환경 로드</h2>";

try {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER;
    require_login();

    echo "<div class='test pass'>✅ Moodle 환경 로드 성공</div>";
    echo "<div class='test info'>현재 사용자 ID: " . ($USER->id ?? '없음') . "</div>";
    $testResults['moodle'] = true;
} catch (Exception $e) {
    echo "<div class='test fail'>❌ Moodle 환경 로드 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
    $testResults['moodle'] = false;
}

// ========================================
// 테스트 2: 엔진 클래스 로드
// ========================================
echo "<h2>테스트 2: 엔진 클래스 로드</h2>";

try {
    require_once(dirname(__DIR__) . '/engine/Agent17PersonaEngine.php');

    if (class_exists('Agent17PersonaEngine')) {
        echo "<div class='test pass'>✅ Agent17PersonaEngine 클래스 로드 성공</div>";
        $testResults['class_load'] = true;
    } else {
        echo "<div class='test fail'>❌ Agent17PersonaEngine 클래스가 존재하지 않습니다</div>";
        $testResults['class_load'] = false;
    }
} catch (Exception $e) {
    echo "<div class='test fail'>❌ 엔진 로드 실패: " . htmlspecialchars($e->getMessage()) . " ({$currentFile}:" . __LINE__ . ")</div>";
    $testResults['class_load'] = false;
}

// ========================================
// 테스트 3: 엔진 인스턴스 생성
// ========================================
echo "<h2>테스트 3: 엔진 인스턴스 생성</h2>";

$engine = null;
try {
    $engine = new Agent17PersonaEngine([
        'debug_mode' => true,
        'log_enabled' => true,
        'cache_enabled' => false
    ]);

    echo "<div class='test pass'>✅ Agent17PersonaEngine 인스턴스 생성 성공</div>";
    $testResults['instance'] = true;
} catch (Exception $e) {
    echo "<div class='test fail'>❌ 인스턴스 생성 실패: " . htmlspecialchars($e->getMessage()) . " ({$currentFile}:" . __LINE__ . ")</div>";
    $testResults['instance'] = false;
}

// ========================================
// 테스트 4: 상황 코드 확인
// ========================================
echo "<h2>테스트 4: 상황 코드 정의 확인</h2>";

if ($engine) {
    try {
        $situations = $engine->getSituationCodes();

        if (!empty($situations)) {
            echo "<div class='test pass'>✅ 상황 코드 정의됨 (" . count($situations) . "개)</div>";
            echo "<div class='test info'><pre>" . json_encode($situations, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre></div>";
            $testResults['situations'] = true;
        } else {
            echo "<div class='test fail'>❌ 상황 코드가 비어있습니다</div>";
            $testResults['situations'] = false;
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ 상황 코드 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        $testResults['situations'] = false;
    }
} else {
    echo "<div class='test fail'>❌ 엔진 인스턴스 없음 - 테스트 건너뜀</div>";
    $testResults['situations'] = false;
}

// ========================================
// 테스트 5: 전략 코드 확인
// ========================================
echo "<h2>테스트 5: 전략 코드 정의 확인</h2>";

if ($engine) {
    try {
        $strategies = $engine->getStrategyCodes();

        if (!empty($strategies)) {
            echo "<div class='test pass'>✅ 전략 코드 정의됨 (" . count($strategies) . "개)</div>";
            echo "<div class='test info'><pre>" . json_encode($strategies, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre></div>";
            $testResults['strategies'] = true;
        } else {
            echo "<div class='test fail'>❌ 전략 코드가 비어있습니다</div>";
            $testResults['strategies'] = false;
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ 전략 코드 확인 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        $testResults['strategies'] = false;
    }
} else {
    echo "<div class='test fail'>❌ 엔진 인스턴스 없음 - 테스트 건너뜀</div>";
    $testResults['strategies'] = false;
}

// ========================================
// 테스트 6: 페르소나 로드
// ========================================
echo "<h2>테스트 6: 페르소나 로드 확인</h2>";

if ($engine) {
    try {
        $personas = $engine->getAllPersonas();

        if (!empty($personas)) {
            echo "<div class='test pass'>✅ 페르소나 로드됨 (" . count($personas) . "개)</div>";

            // 각 상황별 페르소나 수 표시
            $bySituation = [];
            foreach ($personas as $id => $persona) {
                $situation = substr($id, 0, 2);
                if (!isset($bySituation[$situation])) {
                    $bySituation[$situation] = 0;
                }
                $bySituation[$situation]++;
            }

            echo "<div class='test info'><span class='label'>상황별 분포:</span><pre>" . json_encode($bySituation, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "</pre></div>";
            $testResults['personas'] = true;
        } else {
            echo "<div class='test fail'>❌ 페르소나가 비어있습니다</div>";
            $testResults['personas'] = false;
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ 페르소나 로드 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        $testResults['personas'] = false;
    }
} else {
    echo "<div class='test fail'>❌ 엔진 인스턴스 없음 - 테스트 건너뜀</div>";
    $testResults['personas'] = false;
}

// ========================================
// 테스트 7: 진행 상태 판단
// ========================================
echo "<h2>테스트 7: 진행 상태 판단 테스트</h2>";

if ($engine) {
    try {
        // 테스트 케이스
        $testCases = [
            ['completion_rate' => 85, 'expected' => 'R1', 'desc' => '완료율 85%'],
            ['completion_rate' => 60, 'expected' => 'R2', 'desc' => '완료율 60%'],
            ['completion_rate' => 35, 'expected' => 'R3', 'desc' => '완료율 35%'],
            ['completion_rate' => 15, 'expected' => 'R4', 'desc' => '완료율 15%'],
            ['consecutive_failures' => 6, 'expected' => 'R5', 'desc' => '연속 실패 6회']
        ];

        $passed = 0;
        foreach ($testCases as $case) {
            $result = $engine->determineProgressState($case);
            $match = ($result === $case['expected']);

            if ($match) {
                echo "<div class='test pass'>✅ {$case['desc']}: 예상 {$case['expected']} = 결과 {$result}</div>";
                $passed++;
            } else {
                echo "<div class='test fail'>❌ {$case['desc']}: 예상 {$case['expected']} ≠ 결과 {$result}</div>";
            }
        }

        $testResults['progress_state'] = ($passed === count($testCases));
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ 진행 상태 판단 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        $testResults['progress_state'] = false;
    }
} else {
    echo "<div class='test fail'>❌ 엔진 인스턴스 없음 - 테스트 건너뜀</div>";
    $testResults['progress_state'] = false;
}

// ========================================
// 테스트 8: 메시지 처리 (process)
// ========================================
echo "<h2>테스트 8: 메시지 처리 테스트</h2>";

if ($engine && isset($USER->id)) {
    try {
        $result = $engine->process($USER->id, "이 활동이 너무 어려워요", [
            'course_id' => 1,
            'activity_id' => 1
        ]);

        if ($result['success']) {
            echo "<div class='test pass'>✅ 메시지 처리 성공</div>";
            echo "<div class='test info'><span class='label'>응답:</span><pre>" . htmlspecialchars($result['response']['text'] ?? '(응답 없음)') . "</pre></div>";
            echo "<div class='test info'><span class='label'>페르소나:</span> " . htmlspecialchars($result['persona']['persona_id'] ?? '없음') . " - " . htmlspecialchars($result['persona']['persona_name'] ?? '없음') . "</div>";
            echo "<div class='test info'><span class='label'>상황:</span> " . htmlspecialchars($result['context']['situation'] ?? '없음') . "</div>";
            echo "<div class='test info'><span class='label'>처리 시간:</span> " . ($result['meta']['processing_time_ms'] ?? 0) . "ms</div>";
            $testResults['process'] = true;
        } else {
            echo "<div class='test fail'>❌ 메시지 처리 실패: " . htmlspecialchars($result['error'] ?? '알 수 없는 오류') . "</div>";
            $testResults['process'] = false;
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ 메시지 처리 예외: " . htmlspecialchars($e->getMessage()) . " ({$currentFile}:" . __LINE__ . ")</div>";
        $testResults['process'] = false;
    }
} else {
    echo "<div class='test fail'>❌ 엔진 인스턴스 또는 사용자 정보 없음 - 테스트 건너뜀</div>";
    $testResults['process'] = false;
}

// ========================================
// 테스트 9: 설정 파일 로드
// ========================================
echo "<h2>테스트 9: 설정 파일 로드</h2>";

$configPath = dirname(__DIR__) . '/engine/config/agent_config.php';
if (file_exists($configPath)) {
    try {
        $config = include($configPath);

        if (is_array($config) && !empty($config)) {
            echo "<div class='test pass'>✅ 설정 파일 로드 성공</div>";
            echo "<div class='test info'><span class='label'>에이전트 ID:</span> " . ($config['agent']['id'] ?? '없음') . "</div>";
            echo "<div class='test info'><span class='label'>에이전트 이름:</span> " . ($config['agent']['name'] ?? '없음') . "</div>";
            echo "<div class='test info'><span class='label'>전략 수:</span> " . count($config['strategies'] ?? []) . "개</div>";
            $testResults['config'] = true;
        } else {
            echo "<div class='test fail'>❌ 설정 파일이 비어있습니다</div>";
            $testResults['config'] = false;
        }
    } catch (Exception $e) {
        echo "<div class='test fail'>❌ 설정 파일 로드 실패: " . htmlspecialchars($e->getMessage()) . "</div>";
        $testResults['config'] = false;
    }
} else {
    echo "<div class='test fail'>❌ 설정 파일이 존재하지 않습니다: {$configPath}</div>";
    $testResults['config'] = false;
}

// ========================================
// 테스트 10: 템플릿 파일 존재 확인
// ========================================
echo "<h2>테스트 10: 템플릿 파일 존재 확인</h2>";

$templateDir = dirname(__DIR__) . '/templates/default/';
$expectedTemplates = ['R1_default.php', 'R2_default.php', 'R3_default.php', 'R4_default.php', 'R5_default.php'];

$missingTemplates = [];
foreach ($expectedTemplates as $template) {
    if (!file_exists($templateDir . $template)) {
        $missingTemplates[] = $template;
    }
}

if (empty($missingTemplates)) {
    echo "<div class='test pass'>✅ 모든 템플릿 파일 존재 (" . count($expectedTemplates) . "개)</div>";
    $testResults['templates'] = true;
} else {
    echo "<div class='test fail'>❌ 누락된 템플릿: " . implode(', ', $missingTemplates) . "</div>";
    $testResults['templates'] = false;
}

// ========================================
// 결과 요약
// ========================================
echo "<h2>📊 테스트 결과 요약</h2>";

$totalTests = count($testResults);
$passedTests = count(array_filter($testResults));
$failedTests = $totalTests - $passedTests;

$statusClass = ($failedTests === 0) ? 'pass' : (($passedTests > $failedTests) ? 'info' : 'fail');

echo "<div class='test {$statusClass}'>";
echo "<strong>전체: {$totalTests}개 | 성공: {$passedTests}개 | 실패: {$failedTests}개</strong>";
echo "</div>";

echo "<div class='test info'><pre>";
foreach ($testResults as $test => $result) {
    $icon = $result ? '✅' : '❌';
    echo "{$icon} {$test}\n";
}
echo "</pre></div>";

// JSON 결과 (API 호출용)
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => ($failedTests === 0),
        'total' => $totalTests,
        'passed' => $passedTests,
        'failed' => $failedTests,
        'results' => $testResults,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

echo "
</body>
</html>
";
