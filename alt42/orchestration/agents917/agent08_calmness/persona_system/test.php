<?php
/**
 * Agent08 Calmness Persona System Test
 *
 * 페르소나 시스템 컴포넌트 테스트
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent08_calmness/persona_system/test.php
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
define('BASE_PATH', __DIR__);

// 결과 저장
$testResults = [];
$totalTests = 0;
$passedTests = 0;

/**
 * 테스트 결과 기록 함수
 */
function recordTest($name, $passed, $message = '', $details = []) {
    global $testResults, $totalTests, $passedTests;
    $totalTests++;
    if ($passed) $passedTests++;

    $testResults[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message,
        'details' => $details,
        'time' => date('H:i:s')
    ];
}

/**
 * 파일 존재 테스트
 */
function testFileExists($path, $description) {
    $fullPath = BASE_PATH . '/' . $path;
    $exists = file_exists($fullPath);
    recordTest(
        "파일 존재: {$description}",
        $exists,
        $exists ? "파일 확인됨" : "파일 없음: {$fullPath}",
        ['path' => $fullPath]
    );
    return $exists;
}

/**
 * 클래스 로드 테스트
 */
function testClassExists($classPath, $className) {
    $fullPath = BASE_PATH . '/' . $classPath;

    if (!file_exists($fullPath)) {
        recordTest(
            "클래스 로드: {$className}",
            false,
            "파일 없음: {$fullPath}"
        );
        return false;
    }

    require_once($fullPath);

    // 네임스페이스 포함 클래스명 확인
    $fullClassName = "AugmentedTeacher\\Agent08\\PersonaEngine\\" . $className;
    $exists = class_exists($fullClassName) || class_exists($className);

    recordTest(
        "클래스 로드: {$className}",
        $exists,
        $exists ? "클래스 로드됨" : "클래스를 찾을 수 없음"
    );

    return $exists;
}

// HTML 출력 시작
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent08 Calmness - Persona System Test</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
            line-height: 1.6;
        }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #1e3a5f; margin-bottom: 20px; }
        h2 { color: #2c5282; margin: 20px 0 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }

        .summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .summary-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
        }
        .stat-box {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        .stat-box.total { background: #e2e8f0; }
        .stat-box.passed { background: #c6f6d5; }
        .stat-box.failed { background: #fed7d7; }
        .stat-number { font-size: 32px; font-weight: bold; }

        .test-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .test-item {
            display: flex;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .test-item:last-child { border-bottom: none; }

        .test-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        .test-status.pass { background: #48bb78; }
        .test-status.fail { background: #f56565; }

        .test-name { flex: 1; font-weight: 500; }
        .test-message { color: #718096; font-size: 14px; }

        .code-block {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 13px;
            margin: 10px 0;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            color: #718096;
            font-size: 14px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🧘 Agent08 Calmness - Persona System Test</h1>

    <?php
    // ============================================
    // 1. 핵심 파일 존재 테스트
    // ============================================
    echo '<h2>1. 핵심 파일 존재 확인</h2>';
    echo '<div class="test-section">';

    // 엔진 파일
    testFileExists('engine/CalmnessPersonaRuleEngine.php', 'CalmnessPersonaRuleEngine');
    testFileExists('engine/CalmnessRuleParser.php', 'CalmnessRuleParser');
    testFileExists('engine/CalmnessConditionEvaluator.php', 'CalmnessConditionEvaluator');
    testFileExists('engine/CalmnessActionExecutor.php', 'CalmnessActionExecutor');
    testFileExists('engine/CalmnessNLUAnalyzer.php', 'CalmnessNLUAnalyzer');
    testFileExists('engine/CalmnessDataContext.php', 'CalmnessDataContext');
    testFileExists('engine/CalmnessResponseGenerator.php', 'CalmnessResponseGenerator');
    testFileExists('engine/README.md', 'Engine README');

    // 페르소나/규칙 파일
    testFileExists('personas.md', 'Personas Definition');
    testFileExists('rules.yaml', 'Rules YAML');

    // 테스트 결과 출력
    foreach ($testResults as $result) {
        $statusClass = $result['passed'] ? 'pass' : 'fail';
        $statusIcon = $result['passed'] ? '✓' : '✗';
        echo "<div class='test-item'>";
        echo "<div class='test-status {$statusClass}'>{$statusIcon}</div>";
        echo "<div class='test-name'>{$result['name']}</div>";
        echo "<div class='test-message'>{$result['message']}</div>";
        echo "</div>";
    }
    echo '</div>';

    // ============================================
    // 2. 템플릿 파일 테스트
    // ============================================
    $templateResults = [];
    $testResults = []; // 리셋

    echo '<h2>2. 템플릿 파일 확인</h2>';
    echo '<div class="test-section">';

    $templateFolders = ['C95', 'C90', 'C85', 'C80', 'C75', 'C_crisis', 'default'];

    foreach ($templateFolders as $folder) {
        $folderPath = BASE_PATH . '/templates/' . $folder;
        if (is_dir($folderPath)) {
            $files = glob($folderPath . '/*.txt');
            $fileCount = count($files);
            recordTest(
                "템플릿 폴더: {$folder}",
                $fileCount > 0,
                "{$fileCount}개 템플릿 발견",
                ['files' => array_map('basename', $files)]
            );
        } else {
            recordTest(
                "템플릿 폴더: {$folder}",
                false,
                "폴더 없음"
            );
        }
    }

    foreach ($testResults as $result) {
        $statusClass = $result['passed'] ? 'pass' : 'fail';
        $statusIcon = $result['passed'] ? '✓' : '✗';
        echo "<div class='test-item'>";
        echo "<div class='test-status {$statusClass}'>{$statusIcon}</div>";
        echo "<div class='test-name'>{$result['name']}</div>";
        echo "<div class='test-message'>{$result['message']}</div>";
        echo "</div>";
    }
    echo '</div>';

    // ============================================
    // 3. YAML 규칙 파싱 테스트
    // ============================================
    $testResults = [];

    echo '<h2>3. YAML 규칙 파싱 테스트</h2>';
    echo '<div class="test-section">';

    $rulesPath = BASE_PATH . '/rules.yaml';
    if (file_exists($rulesPath)) {
        $rulesContent = file_get_contents($rulesPath);
        $yamlLoaded = false;
        $rules = null;

        // YAML 파서 시도
        if (function_exists('yaml_parse')) {
            $rules = @yaml_parse($rulesContent);
            $yamlLoaded = ($rules !== false);
        }

        // SimpleYamlParser 폴백 (여러 경로 시도)
        if (!$yamlLoaded) {
            $parserPaths = [
                dirname(BASE_PATH) . '/../../ontology_engineering/persona_engine/lib/SimpleYamlParser.php',
                '/home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/lib/SimpleYamlParser.php',
                BASE_PATH . '/lib/SimpleYamlParser.php'
            ];

            foreach ($parserPaths as $parserPath) {
                if (file_exists($parserPath)) {
                    require_once($parserPath);
                    if (class_exists('SimpleYamlParser')) {
                        $rules = SimpleYamlParser::parse($rulesContent);
                        $yamlLoaded = !empty($rules);
                        break;
                    }
                }
            }
        }

        // 마지막 폴백: 간단한 인라인 파서
        if (!$yamlLoaded) {
            // 규칙 수만 카운트하는 간단한 분석
            preg_match_all('/^[a-z_]+_rules:/m', $rulesContent, $sectionMatches);
            $ruleCount = preg_match_all('/^\s+-\s+name:/m', $rulesContent);
            if ($ruleCount > 0) {
                $rules = ['_simple_parse' => true, '_rule_count' => $ruleCount, '_sections' => $sectionMatches[0]];
                $yamlLoaded = true;
            }
        }

        if ($yamlLoaded && is_array($rules)) {
            // 간단한 파싱인지 전체 파싱인지 확인
            $isSimpleParse = isset($rules['_simple_parse']) && $rules['_simple_parse'];

            if ($isSimpleParse) {
                recordTest('YAML 파싱', true, 'rules.yaml 간단 파싱 성공 (규칙 ' . $rules['_rule_count'] . '개 감지)');

                // 간단 파싱에서 발견된 섹션 표시
                $foundSections = $rules['_sections'] ?? [];
                recordTest(
                    "규칙 섹션 검출",
                    count($foundSections) > 0,
                    count($foundSections) . "개 규칙 섹션 발견"
                );
            } else {
                recordTest('YAML 파싱', true, 'rules.yaml 전체 파싱 성공');

                // 규칙 섹션 확인
                $expectedSections = [
                    'crisis_intervention_rules',
                    'calmness_level_identification_rules',
                    'anxiety_trigger_identification_rules',
                    'recovery_pattern_identification_rules',
                    'exercise_recommendation_rules',
                    'response_generation_rules',
                    'tone_pace_rules',
                    'calmness_transition_rules',
                    'monitoring_rules'
                ];

                foreach ($expectedSections as $section) {
                    $hasSection = isset($rules[$section]) && !empty($rules[$section]);
                    $count = $hasSection ? count($rules[$section]) : 0;
                    recordTest(
                        "규칙 섹션: {$section}",
                        $hasSection,
                        $hasSection ? "{$count}개 규칙" : "섹션 없음"
                    );
                }

                // 메타데이터 확인
                if (isset($rules['metadata'])) {
                    $totalRules = $rules['metadata']['total_rules'] ?? 0;
                    recordTest(
                        "메타데이터 확인",
                        $totalRules > 0,
                        "총 {$totalRules}개 규칙 정의됨"
                    );
                }
            }
        } else {
            recordTest('YAML 파싱', false, 'YAML 파싱 실패 - YAML 파서가 필요합니다');
        }
    } else {
        recordTest('YAML 파싱', false, 'rules.yaml 파일 없음');
    }

    foreach ($testResults as $result) {
        $statusClass = $result['passed'] ? 'pass' : 'fail';
        $statusIcon = $result['passed'] ? '✓' : '✗';
        echo "<div class='test-item'>";
        echo "<div class='test-status {$statusClass}'>{$statusIcon}</div>";
        echo "<div class='test-name'>{$result['name']}</div>";
        echo "<div class='test-message'>{$result['message']}</div>";
        echo "</div>";
    }
    echo '</div>';

    // ============================================
    // 4. 공통 인터페이스 연동 테스트
    // ============================================
    $testResults = [];

    echo '<h2>4. 공통 인터페이스 연동 확인</h2>';
    echo '<div class="test-section">';

    $interfacePath = dirname(BASE_PATH) . '/../../ontology_engineering/persona_engine/core/';
    $interfaces = [
        'IRuleParser.php' => 'IRuleParser',
        'IConditionEvaluator.php' => 'IConditionEvaluator',
        'IActionExecutor.php' => 'IActionExecutor',
        'IResponseGenerator.php' => 'IResponseGenerator',
        'INLUAnalyzer.php' => 'INLUAnalyzer',
        'IDataContext.php' => 'IDataContext'
    ];

    foreach ($interfaces as $file => $interface) {
        $fullPath = $interfacePath . $file;
        $exists = file_exists($fullPath);
        recordTest(
            "인터페이스: {$interface}",
            $exists,
            $exists ? "인터페이스 확인됨" : "파일 없음"
        );
    }

    foreach ($testResults as $result) {
        $statusClass = $result['passed'] ? 'pass' : 'fail';
        $statusIcon = $result['passed'] ? '✓' : '✗';
        echo "<div class='test-item'>";
        echo "<div class='test-status {$statusClass}'>{$statusIcon}</div>";
        echo "<div class='test-name'>{$result['name']}</div>";
        echo "<div class='test-message'>{$result['message']}</div>";
        echo "</div>";
    }
    echo '</div>';

    // ============================================
    // 5. DB 테이블 존재 확인
    // ============================================
    $testResults = [];

    echo '<h2>5. DB 테이블 확인</h2>';
    echo '<div class="test-section">';

    try {
        $tables = [
            'at_agent_calmness_sessions' => '평온도 세션',
            'at_agent_calmness_exercises' => '운동 이력',
            'at_agent_calmness_crisis_events' => '위기 이벤트',
            'at_agent_persona_state' => '페르소나 상태'
        ];

        foreach ($tables as $table => $description) {
            try {
                // 직접 SQL로 테이블 존재 확인 (Moodle XMLDB 우회)
                $sql = "SHOW TABLES LIKE '{$table}'";
                $result = $DB->get_records_sql($sql);
                $exists = !empty($result);

                // 폴백: XMLDB 매니저
                if (!$exists) {
                    try {
                        $exists = $DB->get_manager()->table_exists($table);
                    } catch (Exception $e2) {
                        // 무시
                    }
                }

                recordTest(
                    "테이블: {$description}",
                    $exists,
                    $exists ? "테이블 존재함" : "테이블 없음 (생성 필요)"
                );
            } catch (Exception $e) {
                recordTest(
                    "테이블: {$description}",
                    false,
                    "확인 실패: " . $e->getMessage()
                );
            }
        }
    } catch (Exception $e) {
        recordTest("DB 연결", false, "DB 연결 실패: " . $e->getMessage());
    }

    foreach ($testResults as $result) {
        $statusClass = $result['passed'] ? 'pass' : 'fail';
        $statusIcon = $result['passed'] ? '✓' : '✗';
        echo "<div class='test-item'>";
        echo "<div class='test-status {$statusClass}'>{$statusIcon}</div>";
        echo "<div class='test-name'>{$result['name']}</div>";
        echo "<div class='test-message'>{$result['message']}</div>";
        echo "</div>";
    }
    echo '</div>';

    // ============================================
    // 요약 출력
    // ============================================
    $failed = $totalTests - $passedTests;
    $percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100) : 0;
    ?>

    <div class="summary">
        <h2 style="margin-top: 0;">📊 테스트 요약</h2>
        <div class="summary-stats">
            <div class="stat-box total">
                <div class="stat-number"><?php echo $totalTests; ?></div>
                <div>전체 테스트</div>
            </div>
            <div class="stat-box passed">
                <div class="stat-number"><?php echo $passedTests; ?></div>
                <div>통과</div>
            </div>
            <div class="stat-box failed">
                <div class="stat-number"><?php echo $failed; ?></div>
                <div>실패</div>
            </div>
        </div>
        <div style="margin-top: 20px; text-align: center;">
            <strong>통과율: <?php echo $percentage; ?>%</strong>
            <?php if ($percentage >= 80): ?>
                <span style="color: #48bb78;">✅ 테스트 통과</span>
            <?php else: ?>
                <span style="color: #f56565;">⚠️ 추가 작업 필요</span>
            <?php endif; ?>
        </div>
    </div>

    <h2>📝 다음 단계</h2>
    <div class="test-section">
        <ul style="padding-left: 20px;">
            <?php if ($failed > 0): ?>
                <li>실패한 테스트 항목 확인 및 수정</li>
            <?php endif; ?>
            <li>DB 테이블이 없으면 <a href="setup_db.php">setup_db.php</a> 실행</li>
            <li>API 엔드포인트 테스트: <a href="api/test.php">api/test.php</a></li>
            <li>통합 테스트 진행</li>
        </ul>
    </div>

    <div class="footer">
        <p>Agent08 Calmness Persona System v1.0</p>
        <p>테스트 실행: <?php echo date('Y-m-d H:i:s'); ?></p>
        <p>파일 위치: <?php echo CURRENT_FILE; ?></p>
    </div>
</div>
</body>
</html>
<?php
/*
 * 관련 DB 테이블:
 * - at_agent_calmness_sessions (세션 관리)
 * - at_agent_calmness_exercises (운동 이력)
 * - at_agent_calmness_crisis_events (위기 이벤트)
 * - at_agent_persona_state (페르소나 상태)
 *
 * 참조 파일:
 * - engine/CalmnessPersonaRuleEngine.php (메인 엔진)
 * - personas.md (페르소나 정의)
 * - rules.yaml (규칙 정의)
 */
?>
