<?php
/**
 * Agent21 페르소나 시스템 테스트 페이지
 *
 * @package AugmentedTeacher\Agent21\PersonaSystem
 * @version 1.0
 */

$currentFile = __FILE__;

// Moodle 환경 로드
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$apiUrl = str_replace('/test.php', '/api/chat.php', $_SERVER['REQUEST_URI']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent21 개입 실행 페르소나 테스트</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .info-box { background: #e8f5e9; border-left: 4px solid #4CAF50; padding: 15px; margin-bottom: 20px; border-radius: 4px; }
        .info-box h3 { margin-top: 0; color: #2e7d32; }
        .response-types { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .response-type { padding: 8px 15px; border-radius: 20px; font-size: 0.9em; font-weight: bold; }
        .type-A { background: #c8e6c9; color: #2e7d32; }
        .type-R { background: #ffcdd2; color: #c62828; }
        .type-N { background: #e0e0e0; color: #424242; }
        .type-D { background: #fff3e0; color: #e65100; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; }
        input[type="text"], textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        textarea { height: 100px; resize: vertical; }
        button { background: #4CAF50; color: white; padding: 12px 25px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #45a049; }
        .result { background: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .result h3 { margin-top: 0; color: #333; }
        pre { background: #263238; color: #aed581; padding: 15px; border-radius: 4px; overflow-x: auto; font-size: 13px; line-height: 1.5; }
        .samples { background: #fff; padding: 15px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .sample-btn { background: #f5f5f5; border: 1px solid #ddd; padding: 8px 12px; margin: 3px; cursor: pointer; border-radius: 4px; font-size: 13px; }
        .sample-btn:hover { background: #e0e0e0; }
        .transition { padding: 10px 15px; border-radius: 4px; margin-top: 10px; }
        .transition.positive { background: #c8e6c9; border-left: 4px solid #4CAF50; }
        .transition.negative { background: #ffcdd2; border-left: 4px solid #f44336; }
    </style>
</head>
<body>
    <h1>🎯 Agent21 개입 실행 페르소나 테스트</h1>

    <div class="info-box">
        <h3>반응 유형 코드</h3>
        <div class="response-types">
            <span class="response-type type-A">A: 수용 (Acceptance)</span>
            <span class="response-type type-R">R: 저항 (Resistance)</span>
            <span class="response-type type-N">N: 무응답 (No Response)</span>
            <span class="response-type type-D">D: 지연반응 (Delayed)</span>
        </div>
    </div>

    <form id="testForm">
        <div class="form-group">
            <label for="message">테스트 메시지:</label>
            <textarea id="message" name="message" placeholder="학생의 메시지를 입력하세요..."></textarea>
        </div>

        <div class="form-group">
            <label for="response_type">이전 반응 유형:</label>
            <select id="response_type" name="response_type">
                <option value="">선택 안함 (신규)</option>
                <option value="A">A - 수용</option>
                <option value="R">R - 저항</option>
                <option value="N">N - 무응답</option>
                <option value="D">D - 지연반응</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ai_enabled">AI 응답:</label>
            <select id="ai_enabled" name="ai_enabled">
                <option value="1">활성화</option>
                <option value="0">비활성화 (템플릿만)</option>
            </select>
        </div>

        <button type="submit">🚀 테스트 실행</button>
    </form>

    <div class="samples">
        <h3>📝 샘플 메시지</h3>
        <div>
            <strong>수용형 (A):</strong>
            <button class="sample-btn" onclick="setSample('네, 알겠습니다. 해볼게요!', 'R')">순응 응답</button>
            <button class="sample-btn" onclick="setSample('좋은 생각이네요. 바로 시작할게요.', '')">적극적 수용</button>
        </div>
        <div>
            <strong>저항형 (R):</strong>
            <button class="sample-btn" onclick="setSample('그건 좀 어려울 것 같아요. 다른 방법은 없나요?', 'A')">소극적 저항</button>
            <button class="sample-btn" onclick="setSample('싫어요. 안 할래요.', '')">강한 저항</button>
        </div>
        <div>
            <strong>무응답형 (N):</strong>
            <button class="sample-btn" onclick="setSample('...', '')">침묵</button>
            <button class="sample-btn" onclick="setSample('글쎄요...', 'A')">회피</button>
        </div>
        <div>
            <strong>지연형 (D):</strong>
            <button class="sample-btn" onclick="setSample('시간이 좀 필요해요. 나중에 해도 될까요?', '')">시간 요청</button>
            <button class="sample-btn" onclick="setSample('생각해 볼게요', 'R')">고민 중</button>
        </div>
        <div>
            <strong>위기:</strong>
            <button class="sample-btn" onclick="setSample('너무 힘들어요. 포기하고 싶어요.', 'R')" style="background:#ffcdd2;">위기 신호</button>
        </div>
    </div>

    <div id="result" class="result" style="display:none;">
        <h3>📊 응답 결과</h3>
        <div id="transitionBox"></div>
        <pre id="resultJson"></pre>
    </div>

    <script>
        function setSample(message, responseType) {
            document.getElementById('message').value = message;
            document.getElementById('response_type').value = responseType;
        }

        document.getElementById('testForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const data = {
                message: document.getElementById('message').value,
                response_type: document.getElementById('response_type').value,
                ai_enabled: document.getElementById('ai_enabled').value === '1'
            };

            try {
                const response = await fetch('<?php echo $apiUrl; ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                // 전환 표시
                let transitionHtml = '';
                if (result.transition) {
                    const type = result.transition.type === 'positive' ? 'positive' : 'negative';
                    transitionHtml = `<div class="transition ${type}">
                        <strong>반응 전환 감지:</strong> ${result.transition.from} → ${result.transition.to}
                        (${type === 'positive' ? '✅ 긍정적' : '⚠️ 부정적'})
                    </div>`;
                }
                document.getElementById('transitionBox').innerHTML = transitionHtml;

                // JSON 결과 표시
                document.getElementById('resultJson').textContent = JSON.stringify(result, null, 2);
                document.getElementById('result').style.display = 'block';

            } catch (error) {
                document.getElementById('resultJson').textContent = 'Error: ' + error.message;
                document.getElementById('result').style.display = 'block';
            }
        });
    </script>
</body>
</html>
<?php
/*
 * 테스트 페이지 URL:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent21_intervention_execution/persona_system/test.php
 *
 * 관련 파일:
 * - api/chat.php: API 엔드포인트
 * - engine/PersonaEngine.php: 페르소나 처리 엔진
 * - rules.yaml: 반응 유형 식별 규칙
 * - templates/: 응답 템플릿
 */
?>
