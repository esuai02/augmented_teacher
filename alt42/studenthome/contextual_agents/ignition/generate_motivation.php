<?php
header('Content-Type: application/json; charset=utf-8');

// Moodle 설정
include_once("/home/moodle/public_html/moodle/config.php");
require_once('../config.php');
global $DB, $USER;
require_login();

// POST 데이터 받기
$input = json_decode(file_get_contents('php://input'), true);
$studentId = $input['studentId'] ?? 0;
$keywords = $input['keywords'] ?? [];
$studentName = $input['studentName'] ?? '학생';
$messageType = $input['type'] ?? 'go'; // 'go', 'universe', 또는 'environment'

// 학생 정보 조회
$student = $DB->get_record('user', array('id' => $studentId));
$fullStudentName = $student ? $student->firstname . ' ' . $student->lastname : $studentName;

// OpenAI API 호출 함수
function generateMotivationalMessage($keywords, $studentName, $type) {
    $keywordText = implode(', ', $keywords);
    
    if ($type === 'go') {
        $prompt = "당신은 마음을 깊이 이해하는 따뜻한 심리학자이자 학습 전문가입니다. 학생의 내면을 공감하며 감동적인 격려를 전하는 멘토입니다.

학생 이름: {$studentName}
현재 마음속 고민들: {$keywordText}

이 학생이 선택한 고민/방해요소들을 보면서, 지금 이 순간 '수학문제 속으로 GO!' 하기 전에 마음에 와닿는 개인화된 메시지를 작성해주세요.

감동적인 메시지 요구사항:
1. 학생의 고민을 진심으로 이해하고 공감하는 따뜻한 톤
2. 고민들이 사라질 수 있다는 희망적인 관점 제시
3. 수학 학습이 마음의 치유가 될 수 있음을 자연스럽게 표현
4. 학생만을 위한 특별한 메시지임을 느끼게 하기
5. 2-3문장, 감성적이면서도 진정성 있게
6. 한국어로 자연스럽게 작성
7. 학생의 이름을 따뜻하게 포함

창의적 예시:
- '{$studentName}님, [고민 키워드들]로 힘들어하는 마음이 느껴져요. 이 모든 잡념들을 수학이라는 순수한 세계로 녹여내면서, 진짜 나만의 평온을 찾아가보세요.'
- '지금 {$studentName}님의 머릿속 [키워드들]이 소란스럽죠? 수학 문제 하나에 온전히 집중하는 순간, 그 모든 것들이 작고 먼 이야기가 될 거예요.'

메시지만 작성해주세요:";
    } else {
        $prompt = "당신은 우주의 신비함을 이해하고 학생들에게 깊은 영감을 주는 철학적 멘토입니다. 우주적 관점에서 학습의 의미를 전달하는 지혜로운 안내자입니다.

학생 이름: {$studentName}
마음속 고민들: {$keywordText}

이 학생이 몰입을 위한 우주 공간에 들어왔을 때, 마음 깊이 울림을 주는 환영 메시지를 작성해주세요.

우주적 관점의 감동 메시지 요구사항:
1. 무한한 우주 앞에서 고민들이 얼마나 작은지 느끼게 하기
2. 수학을 통한 학습이 우주와 연결되는 신성한 경험임을 표현
3. 학생만을 위한 특별한 우주 공간임을 강조
4. 깊은 몰입과 집중으로의 초대
5. 2-3문장으로 시적이고 감동적으로
6. 한국어로 자연스럽게 작성
7. 학생의 이름을 특별하게 포함

시적이고 영감적인 예시:
- '무한한 별들 사이에서 {$studentName}님을 위한 특별한 공간이 열립니다. [고민 키워드들]은 이 광대한 우주 앞에서 작은 먼지일 뿐, 이제 수학이라는 별빛을 따라 진정한 나와 만나보세요.'
- '{$studentName}님, 우주는 당신의 [키워드들] 너머에 있는 무한한 가능성을 보고 있어요. 지금 이 순간, 수학과 함께 우주적 몰입의 세계로 들어가세요.'

메시지만 작성해주세요:";
    } else if ($type === 'environment') {
        $prompt = "당신은 학생들의 학습 환경과 심리 상태를 깊이 이해하는 교육 환경 전문가입니다.

학생 이름: {$studentName}
학생이 선택한 주요 고민들: {$keywordText}

이 학생의 고민을 바탕으로 현실적이면서도 개인화된 학습 환경 방해요소들을 생성해주세요. 학생이 실제로 겪을 법한 상황들로 구성하되, 선택한 키워드와 관련성이 있어야 합니다.

환경 키워드 생성 요구사항:
1. 물리적 환경 (3개): 교실/학원의 실제적인 불편함
2. 정신적 방해요소 (3개): 학습 중 떠오르는 생각들
3. 심리적 압박감 (3개): 성적이나 미래에 대한 걱정
4. 신체적 불편감 (3개): 장시간 학습으로 인한 몸의 불편함
5. 학원 특화 상황 (3개): 학원에서만 일어나는 특별한 상황들

각 카테고리별로 3개씩, 총 15개의 키워드를 생성하되:
- 선택한 키워드 '{$keywordText}'와 연관성 있게 구성
- 한국 학생들이 실제 겪는 현실적인 상황
- 간결하고 직관적인 표현 (3-6글자)
- 학습 몰입을 방해하는 요소들

응답 형식 (정확히 이 형태로):
물리적환경: 키워드1, 키워드2, 키워드3
정신적방해: 키워드1, 키워드2, 키워드3  
심리적압박: 키워드1, 키워드2, 키워드3
신체적불편: 키워드1, 키워드2, 키워드3
학원특화: 키워드1, 키워드2, 키워드3";
    }
    
    $data = [
        'model' => OPENAI_MODEL,
        'messages' => [
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ],
        'max_tokens' => 200,
        'temperature' => 0.7
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
    }
    
    // 실패시 기본 메시지 반환
    if ($type === 'go') {
        return "{$studentName}님, 지금 이 순간 수학 문제 속으로 떠나는 여정을 시작해보세요. 당신의 잠재력이 빛날 시간입니다!";
    } else {
        return "무한한 우주 속에서 {$studentName}님만의 특별한 학습 여정이 시작됩니다. 수학이라는 별빛을 따라 깊은 몰입의 세계로 들어가보세요.";
    }
}

// 환경 키워드 파싱 함수
function parseEnvironmentKeywords($response) {
    $lines = explode("\n", trim($response));
    $result = [
        'physicalEnv' => [],
        'distractions' => [],
        'mentalPressure' => [],
        'physicalDiscomfort' => [],
        'academySpecific' => []
    ];
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (strpos($line, '물리적환경:') === 0) {
            $result['physicalEnv'] = explode(', ', trim(substr($line, 6)));
        } else if (strpos($line, '정신적방해:') === 0) {
            $result['distractions'] = explode(', ', trim(substr($line, 6)));
        } else if (strpos($line, '심리적압박:') === 0) {
            $result['mentalPressure'] = explode(', ', trim(substr($line, 6)));
        } else if (strpos($line, '신체적불편:') === 0) {
            $result['physicalDiscomfort'] = explode(', ', trim(substr($line, 6)));
        } else if (strpos($line, '학원특화:') === 0) {
            $result['academySpecific'] = explode(', ', trim(substr($line, 5)));
        }
    }
    
    return $result;
}

if ($messageType === 'environment') {
    // 환경 키워드 생성
    $environmentData = generateMotivationalMessage($keywords, $fullStudentName, $messageType);
    $parsedKeywords = parseEnvironmentKeywords($environmentData);
    
    // JSON 응답
    echo json_encode([
        'success' => true,
        'keywords' => $parsedKeywords,
        'type' => $messageType
    ], JSON_UNESCAPED_UNICODE);
} else {
    // 메시지 생성
    $message = generateMotivationalMessage($keywords, $fullStudentName, $messageType);
    
    // JSON 응답
    echo json_encode([
        'success' => true,
        'message' => $message,
        'type' => $messageType
    ], JSON_UNESCAPED_UNICODE);
}
?>