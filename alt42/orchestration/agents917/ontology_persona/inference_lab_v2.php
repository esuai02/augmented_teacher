<?php
/**
 * 온톨로지 추론 실험실 v2 (개선된 버전)
 *
 * 목적: 실시간으로 추론 실험을 수행할 수 있는 대화형 인터페이스
 * 경로: https://mathking.kr/moodle/local/augmented_teacher/alt42/ontology_brain/inference_lab_v2.php
 *
 * 변경사항: stdin으로 Python 코드 전달 (임시 파일 불필요)
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

        // Python 스크립트 생성
        $pythonCode = <<<PYTHON
#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json
import sys

# 온톨로지 로드
try:
    with open('01_minimal_ontology.json', 'r', encoding='utf-8') as f:
        ontology = json.load(f)
except Exception as e:
    print(f"온톨로지 로드 오류: {e}", file=sys.stderr)
    sys.exit(1)

# 추론 규칙
rules = [
    {"condition": {"emotion": "좌절"}, "conclusion": "격려 필요"},
    {"condition": {"emotion": "집중"}, "conclusion": "학습 진행"},
    {"condition": {"emotion": "피로"}, "conclusion": "휴식 필요"}
]

# 입력 사실
facts = {
    "student": "{$student}",
    "emotion": "{$emotion}"
}

print("="*60)
print(f"📥 입력 사실: {facts}")
print("="*60)
print()

# 추론 실행
conclusions = []
for rule in rules:
    matched = True
    for key, value in rule["condition"].items():
        if facts.get(key) != value:
            matched = False
            break

    if matched:
        conclusion = rule["conclusion"]
        conclusions.append(conclusion)
        print(f"✓ 규칙 적용: {rule['condition']} → {conclusion}")

print()
print("="*60)
print("📊 추론 결과:")
if conclusions:
    for conclusion in conclusions:
        print(f"  → {conclusion}")
else:
    print("  (적용 가능한 규칙 없음)")
print("="*60)

# 정상 종료
sys.exit(0)
PYTHON;

        // Python 실행 (stdin으로 코드 전달)
        $descriptorspec = [
            0 => ['pipe', 'r'],  // stdin
            1 => ['pipe', 'w'],  // stdout
            2 => ['pipe', 'w']   // stderr
        ];

        $process = proc_open(
            "cd " . escapeshellarg(__DIR__ . '/examples') . " && PYTHONIOENCODING=utf-8 python3 -",
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
            // (proc_close의 exit code는 shell 체인 때문에 -1이 될 수 있음)
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
    <title>온톨로지 추론 실험실 v2</title>
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
            grid-template-columns: repeat(3, 1fr);
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
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🧠 온톨로지 추론 실험실 <span class="version-badge">v2.0</span></h1>
        <p>Mathking 자동개입 v1.0 - Interactive Inference Lab (No Temp Files)</p>
    </div>

    <div class="main-grid">
        <!-- 입력 패널 -->
        <div class="card">
            <h2>📥 실험 입력</h2>

            <div class="example-buttons">
                <div class="example-btn" onclick="setExample('철수', '좌절')">
                    😰 좌절
                </div>
                <div class="example-btn" onclick="setExample('영희', '집중')">
                    😊 집중
                </div>
                <div class="example-btn" onclick="setExample('민수', '피로')">
                    😴 피로
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
                        <option value="좌절">좌절 (Frustrated)</option>
                        <option value="집중">집중 (Focused)</option>
                        <option value="피로">피로 (Tired)</option>
                        <option value="기쁨">기쁨 (Happy)</option>
                        <option value="불안">불안 (Anxious)</option>
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
            <h2>📋 추론 규칙</h2>

            <div class="rule-list">
                <div class="rule-item">
                    <strong>규칙 1:</strong>
                    <code>좌절</code> → <strong>격려 필요</strong>
                </div>
                <div class="rule-item">
                    <strong>규칙 2:</strong>
                    <code>집중</code> → <strong>학습 진행</strong>
                </div>
                <div class="rule-item">
                    <strong>규칙 3:</strong>
                    <code>피로</code> → <strong>휴식 필요</strong>
                </div>
            </div>

            <p style="color: #666; font-size: 14px; margin-top: 15px;">
                💡 <strong>Tip:</strong> 위의 감정 외에 다른 감정을 입력하면
                "적용 가능한 규칙 없음"이 표시됩니다.
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
