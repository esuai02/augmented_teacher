<?php
/**
 * 온톨로지 추론 실험실 v3 (온톨로지 기반 추론)
 *
 * 목적: 온톨로지 파일을 동적으로 로드하여 추론하는 실험실
 * 경로: https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v3.php
 *
 * 변경사항:
 * - InferenceEngine 클래스 사용 (온톨로지 기반 동적 추론)
 * - 5개 감정 지원 (Frustrated, Focused, Tired, Anxious, Happy)
 * - 우선순위 기반 다중 규칙 매칭 지원
 */

// 에러 표시 (디버깅용)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'infer') {
        $student = $_POST['student'] ?? '학생';
        $emotion = $_POST['emotion'] ?? '';

        $examplesDir = __DIR__ . '/examples';

        // Python 스크립트 생성 (온톨로지 기반)
        $pythonCode = <<<PYTHON
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import sys
import json
sys.path.append('{$examplesDir}')

from inference_engine import InferenceEngine

try:
    # 온톨로지 기반 추론 엔진 초기화 (Phase 1: ontology.jsonld 사용)
    engine = InferenceEngine('{$examplesDir}/../ontology/ontology.jsonld')

    # 추론 실행
    student_state = {
        "student": "{$student}",
        "emotion": "{$emotion}"
    }

    results = engine.infer(student_state)

    # 결과 포맷팅
    print("="*60)
    print(f"📥 학생 상태: {student_state}")
    print("="*60)
    print()

    if results:
        print(f"✅ 매칭된 규칙 수: {len(results)}개")
        print()

        for i, result in enumerate(results, 1):
            priority_stars = "★" * int(result['priority'])
            print(f"{i}. [{result['priority']}] {priority_stars}")
            print(f"   규칙: {result['rule_name']}")
            print(f"   결론: {result['conclusion']}")
            print()
    else:
        print("⚠️  매칭된 규칙이 없습니다.")
        print()

    print("="*60)
    print("📊 최종 결과:")
    if results:
        best_result = results[0]
        print(f"  → {best_result['conclusion']}")
        print(f"  (우선순위: {best_result['priority']}, 규칙: {best_result['rule_id']})")
    else:
        print("  → 적용 가능한 규칙 없음")
    print("="*60)

    sys.exit(0)

except Exception as e:
    print(f"오류: {e}", file=sys.stderr)
    import traceback
    traceback.print_exc(file=sys.stderr)
    sys.exit(1)
PYTHON;

        // Python 실행 (stdin으로 코드 전달)
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];

        $process = proc_open(
            "cd " . escapeshellarg($examplesDir) . " && PYTHONIOENCODING=utf-8 python3 -",
            $descriptorspec,
            $pipes
        );

        if (is_resource($process)) {
            // Python 코드를 stdin으로 전달
            fwrite($pipes[0], $pythonCode);
            fclose($pipes[0]);

            // 출력 읽기
            $output = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            // 에러 읽기
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            // 종료 코드
            $return_var = proc_close($process);

            $finalOutput = $output;
            if ($error) {
                $finalOutput .= "\n\n[에러 출력]\n" . $error;
            }

            // 성공 판단: 출력이 있고 에러가 없으면 성공
            $isSuccess = !empty($output) && empty($error);

            echo json_encode([
                'success' => $isSuccess,
                'output' => $finalOutput,
                'input' => ['student' => $student, 'emotion' => $emotion]
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success' => false,
                'output' => 'Python 프로세스 실행 실패',
                'input' => ['student' => $student, 'emotion' => $emotion]
            ], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }

    if ($_POST['action'] === 'validate') {
        $cmd = "cd " . escapeshellarg(__DIR__ . '/examples') . " && PYTHONIOENCODING=utf-8 python3 03_validate_consistency.py 2>&1";
        exec($cmd, $output, $return_var);

        echo json_encode([
            'success' => ($return_var === 0),
            'output' => implode("\n", $output)
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>온톨로지 추론 실험실 v3</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 36px;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            margin-bottom: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .result-box {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
            min-height: 200px;
            display: none;
        }

        .result-box.active {
            display: block;
        }

        .result-box h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 18px;
        }

        .result-content {
            background: white;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            white-space: pre-wrap;
            line-height: 1.6;
            max-height: 400px;
            overflow-y: auto;
        }

        .loading {
            text-align: center;
            padding: 40px;
            color: #667eea;
        }

        .loading::after {
            content: '...';
            animation: loading 1.5s infinite;
        }

        @keyframes loading {
            0%, 20% { content: '.'; }
            40% { content: '..'; }
            60%, 100% { content: '...'; }
        }

        .rule-list {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .rule-item {
            padding: 8px 0;
            border-bottom: 1px solid #cce5ff;
        }

        .rule-item:last-child {
            border-bottom: none;
        }

        .rule-item code {
            background: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: 600;
            color: #667eea;
        }

        .example-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }

        .example-btn {
            padding: 10px;
            background: #f0f0f0;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }

        .example-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .success-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        .error-badge {
            background: #dc3545;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }

        .version-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }

        .phase-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🧠 온톨로지 추론 실험실 <span class="version-badge">v3.0</span> <span class="phase-badge">Phase 1</span></h1>
        <p>Ontology-Based Dynamic Inference Engine (10 Rules, 5 Emotions)</p>
        <div style="margin-top: 15px;">
            <a href="ontology_visualizer/ontology_visualizer.html" target="_blank" style="
                display: inline-block;
                padding: 10px 20px;
                background: rgba(255, 255, 255, 0.2);
                color: white;
                text-decoration: none;
                border-radius: 8px;
                border: 2px solid white;
                font-weight: 600;
                transition: all 0.3s ease;
            " onmouseover="this.style.background='rgba(255, 255, 255, 0.3)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.2)'">
                🎨 온톨로지 시각화 도구 열기
            </a>
        </div>
    </div>

    <div class="main-grid">
        <!-- 입력 패널 -->
        <div class="card">
            <h2>📥 실험 입력</h2>

            <div class="example-buttons">
                <div class="example-btn" onclick="setExample('철수', 'Frustrated')">
                    😰 좌절
                </div>
                <div class="example-btn" onclick="setExample('영희', 'Focused')">
                    😊 집중
                </div>
                <div class="example-btn" onclick="setExample('민수', 'Tired')">
                    😴 피로
                </div>
                <div class="example-btn" onclick="setExample('지수', 'Anxious')">
                    😟 불안
                </div>
                <div class="example-btn" onclick="setExample('현수', 'Happy')">
                    😄 기쁨
                </div>
            </div>

            <form id="inferenceForm">
                <div class="form-group">
                    <label for="student">학생 이름</label>
                    <input type="text" id="student" name="student" value="철수" required>
                </div>

                <div class="form-group">
                    <label for="emotion">감정 상태</label>
                    <select id="emotion" name="emotion" required>
                        <option value="">선택하세요</option>
                        <option value="Frustrated">😰 좌절 (Frustrated)</option>
                        <option value="Focused">😊 집중 (Focused)</option>
                        <option value="Tired">😴 피로 (Tired)</option>
                        <option value="Anxious">😟 불안 (Anxious)</option>
                        <option value="Happy">😄 기쁨 (Happy)</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    ▶️ 추론 실행
                </button>

                <button type="button" class="btn btn-secondary" onclick="validateOntology()">
                    ✓ 일관성 검증
                </button>
            </form>
        </div>

        <!-- 규칙 정보 -->
        <div class="card">
            <h2>📋 추론 규칙 (10개, 우선순위 순)</h2>

            <div class="rule-list">
                <div class="rule-item">
                    <strong>[1.0]</strong> <code>좌절</code> → 격려 필요
                </div>
                <div class="rule-item">
                    <strong>[1.0]</strong> <code>집중</code> → 학습 진행
                </div>
                <div class="rule-item">
                    <strong>[1.0]</strong> <code>피로</code> → 휴식 필요
                </div>
                <div class="rule-item">
                    <strong>[0.9]</strong> <code>불안</code> → 마음 안정화 필요
                </div>
                <div class="rule-item">
                    <strong>[0.8]</strong> <code>기쁨</code> → 칭찬 및 격려
                </div>
                <div class="rule-item">
                    <strong>[0.7]</strong> <code>좌절</code> → 학습 난이도 조정 권장
                </div>
                <div class="rule-item">
                    <strong>[0.6]</strong> <code>집중</code> → 심화 학습 제공 권장
                </div>
                <div class="rule-item">
                    <strong>[0.5]</strong> <code>피로</code> → 10분 이상 휴식 권장
                </div>
                <div class="rule-item">
                    <strong>[0.4]</strong> <code>불안</code> → 추가 학습 자료 제공
                </div>
                <div class="rule-item">
                    <strong>[0.3]</strong> <code>기쁨</code> → 도전적인 문제 제공
                </div>
            </div>

            <p style="color: #666; font-size: 14px; margin-top: 15px;">
                💡 <strong>Phase 1 특징:</strong> 온톨로지에서 동적으로 규칙을 로드합니다.
                같은 감정에 여러 규칙이 매칭될 수 있으며, 우선순위가 높은 규칙부터 적용됩니다.
            </p>
        </div>
    </div>

    <!-- 결과 패널 -->
    <div class="card">
        <h2>📊 추론 결과</h2>

        <div id="resultBox" class="result-box">
            <div id="resultStatus"></div>
            <h3 id="resultTitle">실행 결과</h3>
            <div id="resultContent" class="result-content"></div>
        </div>

        <div id="loadingBox" class="loading" style="display: none;">
            추론 엔진 실행 중
        </div>

        <div style="text-align: center; color: #999; padding: 40px;" id="emptyState">
            위의 폼에서 학생 이름과 감정을 입력한 후<br>
            "추론 실행" 버튼을 클릭하세요.
        </div>
    </div>
</div>

<script>
// 예제 설정
function setExample(student, emotion) {
    document.getElementById('student').value = student;
    document.getElementById('emotion').value = emotion;
}

// 폼 제출
document.getElementById('inferenceForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const student = document.getElementById('student').value;
    const emotion = document.getElementById('emotion').value;

    if (!emotion) {
        alert('감정 상태를 선택해주세요.');
        return;
    }

    runInference(student, emotion);
});

// 추론 실행
function runInference(student, emotion) {
    const loadingBox = document.getElementById('loadingBox');
    const resultBox = document.getElementById('resultBox');
    const emptyState = document.getElementById('emptyState');

    // UI 업데이트
    loadingBox.style.display = 'block';
    resultBox.classList.remove('active');
    emptyState.style.display = 'none';

    // AJAX 요청
    const formData = new FormData();
    formData.append('action', 'infer');
    formData.append('student', student);
    formData.append('emotion', emotion);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingBox.style.display = 'none';
        resultBox.classList.add('active');

        const statusHtml = data.success
            ? '<span class="success-badge">✓ 성공</span>'
            : '<span class="error-badge">✗ 오류</span>';

        document.getElementById('resultStatus').innerHTML = statusHtml;
        document.getElementById('resultTitle').textContent =
            `추론 결과: ${data.input.student} (${data.input.emotion})`;
        document.getElementById('resultContent').textContent = data.output;
    })
    .catch(error => {
        loadingBox.style.display = 'none';
        resultBox.classList.add('active');

        document.getElementById('resultStatus').innerHTML =
            '<span class="error-badge">✗ 오류</span>';
        document.getElementById('resultTitle').textContent = '실행 오류';
        document.getElementById('resultContent').textContent =
            '오류가 발생했습니다: ' + error.message;
    });
}

// 일관성 검증
function validateOntology() {
    const loadingBox = document.getElementById('loadingBox');
    const resultBox = document.getElementById('resultBox');
    const emptyState = document.getElementById('emptyState');

    loadingBox.style.display = 'block';
    resultBox.classList.remove('active');
    emptyState.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'validate');

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        loadingBox.style.display = 'none';
        resultBox.classList.add('active');

        const statusHtml = data.success
            ? '<span class="success-badge">✓ 검증 완료</span>'
            : '<span class="error-badge">⚠️ 경고</span>';

        document.getElementById('resultStatus').innerHTML = statusHtml;
        document.getElementById('resultTitle').textContent = '일관성 검증 결과';
        document.getElementById('resultContent').textContent = data.output;
    })
    .catch(error => {
        loadingBox.style.display = 'none';
        alert('검증 중 오류가 발생했습니다: ' + error.message);
    });
}
</script>

</body>
</html>
