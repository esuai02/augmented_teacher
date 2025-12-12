<?php
/**
 * 온톨로지 추론 엔진 웹 테스트 인터페이스 (Public Version)
 *
 * 목적: 로그인 없이 테스트 가능한 버전
 * 경로: https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/test_inference_public.php
 *
 * ⚠️ 주의: 테스트 전용. 프로덕션에서는 test_inference.php 사용
 */

// 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 기본 HTML 헤더
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>온톨로지 추론 엔진 테스트 (Public)</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 8px 8px 0 0;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .test-container {
            padding: 30px;
        }

        .test-section {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .test-section h3 {
            color: #0066cc;
            margin-top: 0;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .test-case {
            background: white;
            border: 1px solid #ced4da;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        .test-input {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-family: monospace;
        }

        .test-output {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .test-rule {
            background: #cfe2ff;
            border: 1px solid #b6d4fe;
            padding: 8px;
            border-radius: 4px;
            margin: 5px 0;
            font-size: 0.9em;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: bold;
        }

        .status-success {
            background: #28a745;
            color: white;
        }

        .status-warning {
            background: #ffc107;
            color: black;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
        }

        .btn-test {
            background: #0066cc;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin: 10px 5px;
            transition: background 0.3s;
        }

        .btn-test:hover {
            background: #0052a3;
        }

        .ontology-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin: 15px 0;
        }

        pre {
            background: #272822;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 10px 0;
        }

        .warning-banner {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }

        .warning-banner strong {
            color: #856404;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🧠 온톨로지 추론 엔진 테스트</h1>
        <p>Mathking 자동개입 v1.0 - Public Test Version</p>
    </div>

    <div class="test-container">
        <div class="warning-banner">
            <strong>⚠️ 테스트 전용 버전</strong><br>
            로그인 없이 실행 가능한 공개 테스트 버전입니다.
        </div>

        <!-- 시스템 정보 -->
        <div class="test-section">
            <h3>📊 시스템 정보</h3>
            <p><strong>프로젝트:</strong> Mathking 자동개입 v1.0</p>
            <p><strong>테스트 대상:</strong> 최소 온톨로지 추론 엔진</p>
            <p><strong>위치:</strong> <?php echo __DIR__; ?></p>
            <p><strong>Python 버전:</strong>
                <?php
                $python_version = shell_exec('python3 --version 2>&1');
                echo htmlspecialchars($python_version);
                ?>
            </p>
        </div>

        <!-- 온톨로지 구조 -->
        <div class="test-section">
            <h3>🗂️ 온톨로지 구조</h3>
            <div class="ontology-info">
                <h4>정의된 개념 (Classes)</h4>
                <ul>
                    <li><strong>Student</strong> (학생) - 학습하는 사람</li>
                    <li><strong>Emotion</strong> (감정) - 학생의 감정 상태</li>
                </ul>

                <h4>정의된 관계 (Properties)</h4>
                <ul>
                    <li><strong>hasEmotion</strong> - 학생이 특정 감정을 가짐</li>
                </ul>

                <h4>추론 규칙 (3개)</h4>
                <ol>
                    <li><code>좌절</code> 감정 → <strong>격려 필요</strong></li>
                    <li><code>집중</code> 감정 → <strong>학습 진행</strong></li>
                    <li><code>피로</code> 감정 → <strong>휴식 필요</strong></li>
                </ol>
            </div>
        </div>

        <!-- 테스트 실행 -->
        <div class="test-section">
            <h3>🚀 테스트 실행</h3>

            <form method="post" action="">
                <button type="submit" name="run_test" class="btn-test">▶️ 추론 엔진 실행</button>
                <button type="submit" name="validate" class="btn-test" style="background: #6c757d;">✓ 일관성 검증</button>
            </form>

            <?php
            if (isset($_POST['run_test'])) {
                echo '<div style="margin-top: 20px;">';

                // Python 스크립트 경로
                $script_path = __DIR__ . '/examples/02_minimal_inference.py';
                $ontology_path = __DIR__ . '/examples/01_minimal_ontology.json';

                // 파일 존재 확인
                if (!file_exists($script_path)) {
                    echo '<div class="error-message">';
                    echo '<strong>❌ 오류:</strong> 추론 스크립트를 찾을 수 없습니다.<br>';
                    echo '경로: ' . htmlspecialchars($script_path);
                    echo '</div>';
                } elseif (!file_exists($ontology_path)) {
                    echo '<div class="error-message">';
                    echo '<strong>❌ 오류:</strong> 온톨로지 파일을 찾을 수 없습니다.<br>';
                    echo '경로: ' . htmlspecialchars($ontology_path);
                    echo '</div>';
                } else {
                    echo '<h4>📋 테스트 결과</h4>';

                    // Python 스크립트 실행
                    $output = [];
                    $return_var = 0;

                    // examples 디렉토리로 이동하여 실행 (UTF-8 환경 설정)
                    $cmd = "cd " . escapeshellarg(__DIR__ . '/examples') . " && PYTHONIOENCODING=utf-8 python3 02_minimal_inference.py 2>&1";
                    exec($cmd, $output, $return_var);

                    if ($return_var === 0) {
                        echo '<div class="test-output">';
                        echo '<span class="status-badge status-success">✓ 성공</span>';
                        echo '<pre style="margin-top: 10px;">';
                        echo htmlspecialchars(implode("\n", $output));
                        echo '</pre>';
                        echo '</div>';

                        // 결과 파싱 및 시각화
                        $parsed_results = parseInferenceOutput($output);
                        displayParsedResults($parsed_results);

                    } else {
                        echo '<div class="error-message">';
                        echo '<strong>❌ 실행 오류 (Exit Code: ' . $return_var . ')</strong>';
                        echo '<pre style="background: #f8d7da; color: #721c24;">';
                        echo htmlspecialchars(implode("\n", $output));
                        echo '</pre>';
                        echo '</div>';
                    }
                }

                echo '</div>';
            }

            if (isset($_POST['validate'])) {
                echo '<div style="margin-top: 20px;">';
                echo '<h4>🔍 일관성 검증 결과</h4>';

                $validate_script = __DIR__ . '/examples/03_validate_consistency.py';

                if (!file_exists($validate_script)) {
                    echo '<div class="error-message">';
                    echo '<strong>❌ 오류:</strong> 검증 스크립트를 찾을 수 없습니다.';
                    echo '</div>';
                } else {
                    $output = [];
                    $return_var = 0;

                    $cmd = "cd " . escapeshellarg(__DIR__ . '/examples') . " && PYTHONIOENCODING=utf-8 python3 03_validate_consistency.py 2>&1";
                    exec($cmd, $output, $return_var);

                    if ($return_var === 0) {
                        echo '<div class="test-output">';
                        echo '<span class="status-badge status-success">✓ 검증 완료</span>';
                        echo '<pre style="margin-top: 10px;">';
                        echo htmlspecialchars(implode("\n", $output));
                        echo '</pre>';
                        echo '</div>';
                    } else {
                        echo '<div class="error-message">';
                        echo '<strong>⚠️ 검증 경고</strong>';
                        echo '<pre style="background: #fff3cd; color: #856404;">';
                        echo htmlspecialchars(implode("\n", $output));
                        echo '</pre>';
                        echo '</div>';
                    }
                }

                echo '</div>';
            }
            ?>
        </div>

        <!-- 다음 단계 -->
        <div class="test-section">
            <h3>📌 다음 단계</h3>
            <ul>
                <li><strong>Level 1:</strong> 새로운 감정 추가하기 (예: "불안" → "안정화 필요")</li>
                <li><strong>Level 2:</strong> 복합 조건 추가하기 (예: "좌절 + 3번 시도" → "난이도 조정")</li>
                <li><strong>Level 3:</strong> 실제 에이전트와 연동하기</li>
            </ul>
        </div>
    </div>
</div>

</body>
</html>

<?php
/**
 * 추론 엔진 출력 파싱
 */
function parseInferenceOutput($output) {
    $results = [
        'ontology_concepts' => [],
        'rules_loaded' => 0,
        'test_cases' => []
    ];

    $current_test = null;

    foreach ($output as $line) {
        // 온톨로지 개념 추출
        if (strpos($line, '온톨로지 개념:') !== false) {
            preg_match('/\[(.*?)\]/', $line, $matches);
            if (!empty($matches[1])) {
                $results['ontology_concepts'] = array_map('trim', explode(',', str_replace("'", "", $matches[1])));
            }
        }

        // 규칙 개수 추출
        if (preg_match('/추론 규칙 (\d+)개/', $line, $matches)) {
            $results['rules_loaded'] = (int)$matches[1];
        }

        // 테스트 케이스 시작
        if (preg_match('/테스트 케이스 (\d+)/', $line, $matches)) {
            if ($current_test !== null) {
                $results['test_cases'][] = $current_test;
            }
            $current_test = ['id' => $matches[1], 'input' => '', 'rules' => [], 'output' => ''];
        }

        // 입력 사실
        if ($current_test && strpos($line, '입력 사실:') !== false) {
            $current_test['input'] = trim(str_replace('입력 사실:', '', $line));
        }

        // 규칙 적용
        if ($current_test && strpos($line, '✓ 규칙 적용:') !== false) {
            $current_test['rules'][] = trim(str_replace('✓ 규칙 적용:', '', $line));
        }

        // 결과
        if ($current_test && strpos($line, '→') !== false && strpos($line, '규칙') === false) {
            $current_test['output'] = trim(str_replace('→', '', $line));
        }
    }

    if ($current_test !== null) {
        $results['test_cases'][] = $current_test;
    }

    return $results;
}

/**
 * 파싱된 결과 시각화
 */
function displayParsedResults($results) {
    if (empty($results['test_cases'])) {
        return;
    }

    echo '<div style="margin-top: 30px;">';
    echo '<h4>📊 상세 분석</h4>';

    echo '<div class="ontology-info">';
    echo '<p><strong>로드된 개념:</strong> ' . count($results['ontology_concepts']) . '개 (' .
         implode(', ', $results['ontology_concepts']) . ')</p>';
    echo '<p><strong>로드된 규칙:</strong> ' . $results['rules_loaded'] . '개</p>';
    echo '</div>';

    foreach ($results['test_cases'] as $test) {
        echo '<div class="test-case">';
        echo '<h4>테스트 케이스 ' . $test['id'] . '</h4>';

        if (!empty($test['input'])) {
            echo '<div class="test-input">';
            echo '<strong>입력:</strong> ' . htmlspecialchars($test['input']);
            echo '</div>';
        }

        if (!empty($test['rules'])) {
            echo '<div>';
            echo '<strong>적용된 규칙:</strong>';
            foreach ($test['rules'] as $rule) {
                echo '<div class="test-rule">' . htmlspecialchars($rule) . '</div>';
            }
            echo '</div>';
        }

        if (!empty($test['output'])) {
            echo '<div class="test-output">';
            echo '<strong>추론 결과:</strong> <span style="font-weight: bold; color: #155724;">' .
                 htmlspecialchars($test['output']) . '</span>';
            echo '</div>';
        }

        echo '</div>';
    }

    echo '</div>';
}
?>
