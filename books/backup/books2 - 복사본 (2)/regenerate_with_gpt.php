<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

$secret_key = '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';

$scriptText = $_POST['scriptText'];
$contentsid = $_POST['contentsid'];
$contentstype = $_POST['contentstype'];

// 권한 확인
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22' ORDER BY id DESC LIMIT 1");
$role = $userrole->data;

if($role === 'student') {
    echo json_encode([
        'success' => false,
        'error' => '사용 권한이 없습니다.'
    ]);
    exit();
}

if(empty($scriptText)) {
    echo json_encode([
        'success' => false,
        'error' => '재구성할 대본 내용을 입력하세요.'
    ]);
    exit();
}

// 사용자 커스텀 프롬프트 가져오기
$customPrompt = $DB->get_record_sql("SELECT * FROM mdl_gptprompts 
    WHERE userid='$USER->id' AND type='pmemory' 
    ORDER BY timemodified DESC LIMIT 1");

// 기본 프롬프트 정의
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

$systemPrompt = ($customPrompt && !empty($customPrompt->prompttext)) 
    ? $customPrompt->prompttext 
    : $defaultPrompt;

// GPT API 호출
try {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret_key
    ]);
    
    $requestData = [
        'model' => 'gpt-4o',
        'messages' => [
            [
                'role' => 'system',
                'content' => $systemPrompt
            ],
            [
                'role' => 'user',
                'content' => "다음 대본을 절차기억 형성용 듣기평가 나레이션으로 재구성해주세요:\n\n" . $scriptText
            ]
        ],
        'temperature' => 0.7,
        'max_tokens' => 3000
    ];
    
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if($httpCode === 200) {
        $result = json_decode($response, true);
        
        if(isset($result['choices'][0]['message']['content'])) {
            $narration = trim($result['choices'][0]['message']['content']);
            
            // @ 기호 개수 확인
            $atCount = substr_count($narration, '@');
            
            // DB에 저장
            $timecreated = time();
            
            // 기존 레코드 확인
            $existing = $DB->get_record_sql("SELECT * FROM mdl_abrainalignment_gptresults 
                WHERE type='pmemory' AND contentsid='$contentsid' AND contentstype='$contentstype' 
                ORDER BY id DESC LIMIT 1");
            
            if($existing) {
                // 업데이트
                $DB->execute("UPDATE mdl_abrainalignment_gptresults 
                    SET outputtext=?, timemodified=? 
                    WHERE id=?",
                    [$narration, $timecreated, $existing->id]);
            } else {
                // 신규 삽입
                $record = new stdClass();
                $record->type = 'pmemory';
                $record->contentsid = $contentsid;
                $record->contentstype = $contentstype;
                $record->outputtext = $narration;
                $record->gid = '71280';
                $record->timemodified = $timecreated;
                $record->timecreated = $timecreated;
                $DB->insert_record('abrainalignment_gptresults', $record);
            }
            
            echo json_encode([
                'success' => true,
                'narration' => $narration,
                'sectionCount' => $atCount + 1,
                'message' => "GPT 재구성 완료! (총 " . ($atCount + 1) . "개 구간)\n이제 TTS를 생성할 수 있습니다."
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'GPT 응답 형식 오류'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'GPT API 호출 실패 (HTTP ' . $httpCode . ')',
            'details' => $response
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage()
    ]);
}
?>


