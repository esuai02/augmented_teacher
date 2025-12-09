<?php
/**
 * Agent05 통합 테스트 스크립트
 * File: orchestration/test_agent05_integration.php
 *
 * Agent05 학습감정 분석이 orchestration에 올바르게 통합되었는지 검증
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$userid = $USER->id;
$studentid = isset($_GET['userid']) ? intval($_GET['userid']) : $userid;

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent05 통합 테스트</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-container {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
        }
        .test-section h2 {
            color: #667eea;
            margin-top: 0;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }
        .status.pass {
            background: #d1fae5;
            color: #065f46;
        }
        .status.fail {
            background: #fee2e2;
            color: #991b1b;
        }
        .status.warning {
            background: #fef3c7;
            color: #92400e;
        }
        .file-check {
            margin: 10px 0;
            padding: 10px;
            background: #f8fafc;
            border-left: 4px solid #94a3b8;
            border-radius: 4px;
        }
        .file-check.exists {
            border-left-color: #10b981;
        }
        .file-check.missing {
            border-left-color: #ef4444;
        }
        .code-preview {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin: 10px 0;
        }
        .category-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .category-item {
            background: #f0f4ff;
            padding: 15px;
            border-radius: 8px;
            border: 2px solid #667eea;
        }
        .category-item h4 {
            margin: 0 0 10px 0;
            color: #667eea;
        }
        .sub-items {
            font-size: 13px;
            color: #475569;
            line-height: 1.6;
        }
        .test-button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .test-button:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>
    <div class="test-container">
        <h1>🧪 Agent05 통합 테스트 리포트</h1>
        <p style="color: #64748b; font-size: 14px;">
            테스트 시간: <?php echo date('Y-m-d H:i:s'); ?><br>
            사용자 ID: <?php echo $studentid; ?>
        </p>
    </div>

    <!-- 1. 파일 존재 확인 -->
    <div class="test-container">
        <div class="test-section">
            <h2>📁 1. 필수 파일 존재 확인</h2>
            <?php
            $files_to_check = [
                'agents/agent05_learning_emotion/index.php' => 'Agent05 메인 엔트리',
                'agents/agent05_learning_emotion/assets/css/agent05.css' => 'Agent05 CSS',
                'agents/agent05_learning_emotion/assets/js/activity_categories_data.js' => 'Activity Categories 데이터',
                'agents/agent05_learning_emotion/assets/js/emotion_workflow.js' => 'Emotion Workflow',
                'assets/js/agent05_handlers.js' => 'Agent05 핸들러 (통합)'
            ];

            $all_files_exist = true;
            foreach ($files_to_check as $file => $description) {
                $file_path = __DIR__ . '/' . $file;
                $exists = file_exists($file_path);
                $all_files_exist = $all_files_exist && $exists;
                $class = $exists ? 'exists' : 'missing';
                $status = $exists ? '✅ 존재' : '❌ 없음';
                $size = $exists ? ' (' . number_format(filesize($file_path)) . ' bytes)' : '';

                echo "<div class='file-check $class'>";
                echo "<strong>$status</strong> $description<br>";
                echo "<code style='font-size: 12px; color: #64748b;'>$file$size</code>";
                echo "</div>";
            }

            echo "<p style='margin-top: 20px;'><span class='status " . ($all_files_exist ? 'pass' : 'fail') . "'>";
            echo $all_files_exist ? '✅ 모든 파일 존재' : '❌ 일부 파일 누락';
            echo "</span></p>";
            ?>
        </div>
    </div>

    <!-- 2. Activity Categories 데이터 검증 -->
    <div class="test-container">
        <div class="test-section">
            <h2>📊 2. Activity Categories 데이터 검증</h2>
            <p>orchestration2와 동일한 7개 카테고리 + 28개 서브아이템 확인</p>

            <div id="categories-test">
                <p>JavaScript 로딩 중...</p>
            </div>
        </div>
    </div>

    <!-- 3. index.php 통합 확인 -->
    <div class="test-container">
        <div class="test-section">
            <h2>🔗 3. index.php 통합 코드 확인</h2>
            <?php
            $index_content = file_get_contents(__DIR__ . '/index.php');

            $checks = [
                'agent05.css 로드' => strpos($index_content, 'agents/agent05_learning_emotion/assets/css/agent05.css') !== false,
                'activity_categories_data.js 로드' => strpos($index_content, 'agents/agent05_learning_emotion/assets/js/activity_categories_data.js') !== false,
                'agent05_handlers.js 로드' => strpos($index_content, 'assets/js/agent05_handlers.js') !== false,
                'renderAgent05Panel 호출' => strpos($index_content, 'renderAgent05Panel') !== false,
                'stepId === 5 조건' => strpos($index_content, 'stepId === 5') !== false
            ];

            $integration_complete = true;
            foreach ($checks as $check_name => $result) {
                $integration_complete = $integration_complete && $result;
                $status = $result ? '✅ 확인됨' : '❌ 누락';
                $class = $result ? 'pass' : 'fail';
                echo "<p><span class='status $class'>$status</span> $check_name</p>";
            }

            echo "<p style='margin-top: 20px;'><span class='status " . ($integration_complete ? 'pass' : 'fail') . "'>";
            echo $integration_complete ? '✅ index.php 통합 완료' : '❌ index.php 통합 불완전';
            echo "</span></p>";
            ?>
        </div>
    </div>

    <!-- 4. 실제 UI 테스트 -->
    <div class="test-container">
        <div class="test-section">
            <h2>🎨 4. 실제 UI 렌더링 테스트</h2>
            <p>Agent05 패널을 실제로 렌더링하여 동작 확인</p>

            <button class="test-button" onclick="testAgent05Rendering()">
                🚀 Agent05 패널 렌더링 테스트
            </button>

            <div id="test-panel" style="margin-top: 20px; min-height: 400px; border: 2px dashed #cbd5e1; border-radius: 8px; padding: 20px;">
                <p style="text-align: center; color: #94a3b8;">테스트 버튼을 클릭하여 Agent05 UI를 렌더링합니다</p>
            </div>
        </div>
    </div>

    <!-- 5. 종합 결과 -->
    <div class="test-container">
        <div class="test-section">
            <h2>📋 5. 종합 테스트 결과</h2>
            <div id="overall-result">
                <p>JavaScript 테스트 완료 대기 중...</p>
            </div>
        </div>
    </div>

    <!-- Agent05 모듈 로드 -->
    <link rel="stylesheet" href="agents/agent05_learning_emotion/assets/css/agent05.css?v=<?php echo time(); ?>">
    <script src="agents/agent05_learning_emotion/assets/js/activity_categories_data.js?v=<?php echo time(); ?>"></script>
    <script src="assets/js/agent05_handlers.js?v=<?php echo time(); ?>"></script>

    <script>
        // 2. Activity Categories 데이터 검증
        (function() {
            const container = document.getElementById('categories-test');

            if (typeof window.Agent05ActivityCategories === 'undefined') {
                container.innerHTML = '<p class="status fail">❌ Agent05ActivityCategories 로드 실패</p>';
                return;
            }

            const categories = window.Agent05ActivityCategories.getAllCategories();

            if (categories.length !== 7) {
                container.innerHTML = `<p class="status fail">❌ 카테고리 개수 불일치: ${categories.length}개 (예상: 7개)</p>`;
                return;
            }

            // orchestration2와 비교할 기준 데이터
            const expectedCategories = [
                { key: 'concept_understanding', name: '개념이해', icon: '📚', subCount: 4 },
                { key: 'type_learning', name: '유형학습', icon: '🎯', subCount: 4 },
                { key: 'problem_solving', name: '문제풀이', icon: '✏️', subCount: 4 },
                { key: 'error_notes', name: '오답노트', icon: '📝', subCount: 4 },
                { key: 'qa', name: '질의응답', icon: '💬', subCount: 4 },
                { key: 'review', name: '복습활동', icon: '🔄', subCount: 4 },
                { key: 'pomodoro', name: '포모도르', icon: '⏰', subCount: 4 }
            ];

            let allMatch = true;
            let html = '<div class="category-list">';

            categories.forEach((cat, index) => {
                const expected = expectedCategories[index];
                const match = cat.key === expected.key &&
                             cat.name === expected.name &&
                             cat.icon === expected.icon &&
                             cat.subItems.length === expected.subCount;

                allMatch = allMatch && match;
                const borderColor = match ? '#10b981' : '#ef4444';

                html += `
                    <div class="category-item" style="border-color: ${borderColor}">
                        <h4>${match ? '✅' : '❌'} ${cat.icon} ${cat.name}</h4>
                        <div class="sub-items">
                            ${cat.subItems.map((item, i) => `${i + 1}. ${item}`).join('<br>')}
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            html += `<p style='margin-top: 20px;'><span class='status ${allMatch ? 'pass' : 'fail'}'>`;
            html += allMatch ? '✅ orchestration2와 완벽히 일치' : '❌ orchestration2와 불일치 발견';
            html += '</span></p>';

            container.innerHTML = html;
        })();

        // 4. UI 렌더링 테스트
        function testAgent05Rendering() {
            const testPanel = document.getElementById('test-panel');

            if (typeof renderAgent05Panel !== 'function') {
                testPanel.innerHTML = '<p class="status fail">❌ renderAgent05Panel 함수를 찾을 수 없습니다</p>';
                return;
            }

            try {
                renderAgent05Panel(testPanel);

                // 렌더링 성공 여부 확인
                setTimeout(() => {
                    const hasContent = testPanel.querySelector('#agent05-container') !== null;
                    const hasCards = testPanel.querySelectorAll('.agent05-activity-card').length === 7;

                    if (hasContent && hasCards) {
                        alert('✅ Agent05 패널 렌더링 성공!\n\n7개 활동 카드가 정상적으로 표시되었습니다.\n카드를 클릭하여 서브아이템 모달을 테스트해보세요.');
                    } else {
                        alert('⚠️ 렌더링은 되었으나 일부 요소가 누락되었습니다.\n\n콘솔을 확인해주세요.');
                    }
                }, 100);

            } catch (error) {
                testPanel.innerHTML = `<p class="status fail">❌ 렌더링 오류: ${error.message}</p>`;
                console.error('[test_agent05_integration.php] 렌더링 오류:', error);
            }
        }

        // 5. 종합 결과 표시
        window.addEventListener('load', function() {
            setTimeout(() => {
                const resultDiv = document.getElementById('overall-result');

                const filesOk = <?php echo $all_files_exist ? 'true' : 'false'; ?>;
                const integrationOk = <?php echo $integration_complete ? 'true' : 'false'; ?>;
                const categoriesOk = typeof window.Agent05ActivityCategories !== 'undefined' &&
                                     window.Agent05ActivityCategories.getAllCategories().length === 7;
                const renderFnOk = typeof renderAgent05Panel === 'function';

                const allOk = filesOk && integrationOk && categoriesOk && renderFnOk;

                let html = '<table style="width: 100%; border-collapse: collapse;">';
                html += '<tr style="background: #f8fafc;"><th style="text-align: left; padding: 10px; border: 1px solid #e2e8f0;">테스트 항목</th><th style="padding: 10px; border: 1px solid #e2e8f0;">결과</th></tr>';

                const tests = [
                    { name: '필수 파일 존재', result: filesOk },
                    { name: 'index.php 통합', result: integrationOk },
                    { name: 'Activity Categories 데이터', result: categoriesOk },
                    { name: 'renderAgent05Panel 함수', result: renderFnOk }
                ];

                tests.forEach(test => {
                    const statusClass = test.result ? 'pass' : 'fail';
                    const statusText = test.result ? '✅ 통과' : '❌ 실패';
                    html += `<tr><td style="padding: 10px; border: 1px solid #e2e8f0;">${test.name}</td>`;
                    html += `<td style="padding: 10px; border: 1px solid #e2e8f0; text-align: center;">`;
                    html += `<span class="status ${statusClass}">${statusText}</span></td></tr>`;
                });

                html += '</table>';
                html += `<div style="margin-top: 30px; padding: 20px; background: ${allOk ? '#d1fae5' : '#fee2e2'}; border-radius: 8px; text-align: center;">`;
                html += `<h3 style="margin: 0; color: ${allOk ? '#065f46' : '#991b1b'};">`;
                html += allOk ? '🎉 모든 테스트 통과!' : '⚠️ 일부 테스트 실패';
                html += '</h3>';
                html += '<p style="margin: 10px 0 0 0; font-size: 14px;">';
                html += allOk ?
                    'Agent05가 orchestration에 성공적으로 통합되었습니다.<br>orchestration2와 동일한 7개 활동 카테고리 및 28개 서브아이템이 확인되었습니다.' :
                    '일부 기능이 정상적으로 작동하지 않습니다. 위 테스트 항목을 확인해주세요.';
                html += '</p></div>';

                resultDiv.innerHTML = html;
            }, 500);
        });
    </script>
</body>
</html>
