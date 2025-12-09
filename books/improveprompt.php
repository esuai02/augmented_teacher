<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$contentsid = $_GET["cid"];
$contentstype = $_GET["ctype"];

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole->data;

if($role === 'student') {
    echo '사용권한이 없습니다.';
    exit();
}

// 1. 사용자 커스텀 프롬프트 불러오기
$customPrompt = $DB->get_record_sql("SELECT * FROM mdl_gptprompts 
    WHERE userid='$USER->id' AND type='pmemory' 
    ORDER BY timemodified DESC LIMIT 1");

// 기본 프롬프트 정의 (커스텀이 없을 경우)
$defaultPrompt = <<<PROMPT
# Role: act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for step by step instructions

입력된 수학문제와 풀이 정보를 분석 후 한국어로 단계별 풀이를 안내하는 수학 듣기평가를 위한 지시어로 변경해줘.

계산 등 자세한 내용 보다 절차에 대한 구조를 강화시키는 것이 목적임. 서술한 내용을 선생님이 직접채점하는 상황.

먼저, 문제 내용을 한 번 정리하는 것으로 시작하는데 이것도 탐구를 유도하고 해소작용으로 답을 제시하는 방식으로 해줘.

다음으로 무엇을 생각해야할지를 궁금하게 만들고 답을하며 실행사항을 제시하는 도제학습 스타일로 작성.

학생이 문제나 이미지를 보고 있다고 가정하고, 관찰 지시를 통해 시각적 이해를 강화.

구체적인 계산 과정은 최소화하고, 풀이의 핵심 흐름과 구조를 간결하면서도 몰입감 있게 설명.

설명이 끝난 뒤에는 반드시 **'절차기억 형성활동을 시작합니다'**라는 문장으로 전환.

전환 이후에는 앞서 설명한 내용을 다시 한 번 강조·요약하며, 유사한 방식으로 더 중요한 사실들을 정리.

마지막에는 이전 설명을 요약해서 한 번 더 설명하고 "이제 문제만 보고 풀 수 있는지 생각해 보세요. 스스로 머릿속으로 풀어 보세요." 라는 식으로 학생이 혼자 문제를 시도하도록 유도.

# Instructions:
- 모든 숫자, 기호, 알파벳은 반드시 한글 발음으로 변환.
- 계산식의 디테일보다는 문제 구조, 조건, 풀이 절차의 흐름을 강조.
- 관찰을 지시할 때는 "지금 그림의 오른쪽 위를 보세요"와 같이 구체적 시각 지침을 제공.
- 설명은 단계마다 요약을 포함하여 기억 정착을 돕도록 구성.
- 절차기억 형성 단계에서 반드시 다시 정리, 중요한 사실 강조, 스스로 풀어보기 유도가 포함되어야 함.
- **각각의 단락별로 @ 기호를 마지막 부분에 반드시 추가해야 함. 이는 음성파일 일시정지 지점을 표시하는 것임.**

# Guidelines:
- 반드시 한글만 사용. 숫자나 기호 절대 금지.
- 하나, 둘, 셋 같은 표현은 쓰지 말고 반드시 일, 이, 삼, 사… 와 같은 아라비아숫자 한글 발음 사용.
- 소숫점은 영점으로 읽기. 예: 0.35 → 영점삼오
- 분수는 "사분의 삼"과 같이 올바른 순서로 읽기.
- 출력은 오직 지시·설명 대본 형식으로, 다른 목차나 목록, 불필요한 기호 사용 금지.
- 각 단락 끝에는 반드시 @ 기호를 추가.

중요: 응답은 오직 나레이션 대본만 출력하세요. 다른 설명이나 서론, 부연 설명 없이 즉시 나레이션으로 시작하세요.
PROMPT;

$promptText = $customPrompt ? $customPrompt->prompttext : $defaultPrompt;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPT 프롬프트 관리</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 40px;
        }
        
        .section h2 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-description {
            color: #666;
            font-size: 13px;
            margin-bottom: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        
        textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
            resize: vertical;
            transition: border-color 0.3s;
        }
        
        textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        button {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
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
            background: #28a745;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
            margin: 40px 0;
        }
        
        .status-message {
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        
        .status-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status-message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        
        .info-box strong {
            display: block;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎓 GPT 프롬프트 관리</h1>
        <p class="subtitle">Contents ID: <?php echo $contentsid; ?> | Type: <?php echo $contentstype; ?></p>
        
        <div class="info-box">
            <strong>💡 사용 방법</strong>
            1. GPT 프롬프트를 수정하여 나레이션 생성 방식을 변경할 수 있습니다.<br>
            2. 수정된 프롬프트는 이후 "절차기억 나레이션 생성" 버튼 클릭 시 자동으로 적용됩니다.<br>
            3. 대본 수정은 <a href="openai_tts_pmemory.php?cid=<?php echo $contentsid; ?>&ctype=<?php echo $contentstype; ?>" target="_blank">TTS 생성 페이지</a>에서 가능합니다.
        </div>
        
        <!-- 프롬프트 섹션 -->
        <div class="section">
            <h2>📝 GPT 프롬프트</h2>
            <div class="section-description">
                나레이션 생성 방식을 정의하는 시스템 프롬프트입니다. 
                이 프롬프트는 "절차기억 나레이션 생성" 버튼 클릭 시 적용됩니다.
            </div>
            <textarea id="promptText" rows="20"><?php echo htmlspecialchars($promptText); ?></textarea>
            <div class="button-group">
                <button class="btn-primary" onclick="savePrompt()">
                    💾 프롬프트 저장
                </button>
                <button class="btn-danger" onclick="resetPrompt()">
                    🔄 기본값으로 복원
                </button>
            </div>
            <div id="promptStatus" class="status-message"></div>
        </div>
    </div>
    
    <script>
        const contentsid = <?php echo json_encode($contentsid); ?>;
        const contentstype = <?php echo json_encode($contentstype); ?>;
        const defaultPrompt = <?php echo json_encode($defaultPrompt); ?>;
        
        // 프롬프트 저장
        function savePrompt() {
            const promptText = document.getElementById('promptText').value;
            const statusDiv = document.getElementById('promptStatus');
            
            if(!promptText.trim()) {
                showStatus(statusDiv, 'error', '프롬프트 내용을 입력하세요.');
                return;
            }
            
            showStatus(statusDiv, 'info', '저장 중...');
            
            fetch('save_prompt.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'promptText=' + encodeURIComponent(promptText)
            })
            .then(r => r.json())
            .then(data => {
                if(data.success) {
                    showStatus(statusDiv, 'success', '✅ 프롬프트가 저장되었습니다!');
                } else {
                    showStatus(statusDiv, 'error', '❌ 저장 실패: ' + (data.error || '알 수 없는 오류'));
                }
            })
            .catch(error => {
                showStatus(statusDiv, 'error', '❌ 네트워크 오류: ' + error.message);
            });
        }
        
        // 프롬프트 기본값 복원
        function resetPrompt() {
            if(confirm('프롬프트를 기본값으로 복원하시겠습니까?')) {
                document.getElementById('promptText').value = defaultPrompt;
                const statusDiv = document.getElementById('promptStatus');
                showStatus(statusDiv, 'info', '기본 프롬프트로 복원되었습니다. "저장" 버튼을 클릭하세요.');
            }
        }
        
        // 상태 메시지 표시
        function showStatus(element, type, message) {
            element.className = 'status-message ' + type;
            element.textContent = message;
            element.style.display = 'block';
            
            if(type === 'success' || type === 'error') {
                setTimeout(() => {
                    element.style.display = 'none';
                }, 5000);
            }
        }
    </script>
</body>
</html>

