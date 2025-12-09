<?php
// Moodle 설정 파일 포함
include_once("/home/moodle/public_html/moodle/config.php");
include_once("config.php"); // OpenAI API 설정 포함
global $DB, $USER;

// 로그인 확인
require_login();

echo "<h2>메시지 전송 테스트</h2>";
echo "<pre>";

// 1. 현재 사용자 정보
echo "현재 사용자: " . $USER->username . " (ID: " . $USER->id . ")\n\n";

// 2. 테스트 데이터 설정
$teacher_id = $USER->id;
$student_id = 123; // 테스트용 학생 ID
$test_message = "오늘 수학 시험 준비 잘 하고 있니? 문제집 풀이는 다 끝냈어?";

echo "=== 테스트 설정 ===\n";
echo "Teacher ID: $teacher_id\n";
echo "Student ID: $student_id\n";
echo "테스트 메시지: $test_message\n\n";

// 3. 페르소나 모드 확인
echo "=== 페르소나 모드 확인 ===\n";
try {
    $persona_modes = $DB->get_record('persona_modes', 
        array('teacher_id' => $teacher_id, 'student_id' => $student_id));
    
    if ($persona_modes) {
        echo "✓ 페르소나 모드 발견!\n";
        echo "  - Teacher Mode: {$persona_modes->teacher_mode}\n";
        echo "  - Student Mode: {$persona_modes->student_mode}\n\n";
    } else {
        echo "✗ 페르소나 모드가 설정되지 않았습니다.\n";
        echo "  테스트용 모드를 생성합니다...\n";
        
        // 테스트용 모드 생성
        $test_mode = new stdClass();
        $test_mode->teacher_id = $teacher_id;
        $test_mode->student_id = $student_id;
        $test_mode->teacher_mode = 'exam';
        $test_mode->student_mode = 'custom';
        $test_mode->created_at = time();
        $test_mode->updated_at = time();
        
        $DB->insert_record('persona_modes', $test_mode);
        $persona_modes = $test_mode;
        echo "✓ 테스트 모드 생성 완료\n\n";
    }
} catch (Exception $e) {
    echo "✗ 오류: " . $e->getMessage() . "\n\n";
}

// 4. OpenAI API 테스트
echo "=== OpenAI API 테스트 ===\n";
echo "API Key: " . (defined('OPENAI_API_KEY') ? '설정됨 (' . substr(OPENAI_API_KEY, 0, 10) . '...)' : '설정 안됨') . "\n";
echo "Model: " . (defined('OPENAI_MODEL') ? OPENAI_MODEL : '없음') . "\n\n";

// 5. 메시지 변환 테스트
echo "=== 메시지 변환 테스트 ===\n";
if ($persona_modes && defined('OPENAI_API_KEY')) {
    // transformMessageWithOpenAI 함수 정의 (chat.php에서 복사)
    function transformMessageWithOpenAI($message, $teacher_mode, $student_mode) {
        $api_key = OPENAI_API_KEY;
        $model = OPENAI_MODEL;
        
        $mode_descriptions = [
            'curriculum' => '체계적이고 계획적인 어조',
            'exam' => '긴장감 있고 동기부여적인 어조',
            'custom' => '친근하고 격려하는 어조',
            'mission' => '게임처럼 도전적이고 즉각적인 어조',
            'reflection' => '사려깊고 질문을 유도하는 어조',
            'selfled' => '자율성을 존중하는 제안형 어조'
        ];
        
        $system_prompt = "당신은 선생님의 메시지를 학생의 학습 스타일에 맞게 변환하는 전문 AI입니다.\n\n선생님 모드: {$teacher_mode} ({$mode_descriptions[$teacher_mode]})\n학생 모드: {$student_mode} ({$mode_descriptions[$student_mode]})\n\n변환 원칙:\n1. 핵심 메시지와 의도는 완전히 유지\n2. 학생 모드에 맞는 어조와 표현으로 변경\n3. 구체적이고 실용적인 표현 사용\n4. 한국어로 자연스럽게 표현\n5. 변환된 메시지만 출력 (설명 없이)\n\n원본 메시지를 학생에게 맞게 변환해주세요:";
        
        $data = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user', 'content' => $message]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500
        ];
        
        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "API 응답 코드: $http_code\n";
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            } else {
                echo "API 응답: " . print_r($result, true) . "\n";
            }
        } else {
            echo "API 호출 실패\n";
        }
        
        return $message; // 실패 시 원본 반환
    }
    
    $transformed = transformMessageWithOpenAI($test_message, $persona_modes->teacher_mode, $persona_modes->student_mode);
    echo "원본: $test_message\n";
    echo "변환: $transformed\n\n";
} else {
    echo "페르소나 모드 또는 API 키가 설정되지 않아 테스트할 수 없습니다.\n\n";
}

// 6. 데이터베이스 저장 테스트
echo "=== 데이터베이스 저장 테스트 ===\n";
try {
    $room_id = $teacher_id . '_' . $student_id;
    
    // 원본 메시지 저장 테스트
    $original_msg = new stdClass();
    $original_msg->room_id = $room_id;
    $original_msg->sender_id = $teacher_id;
    $original_msg->receiver_id = $student_id;
    $original_msg->message_type = 'original';
    $original_msg->message_content = $test_message;
    $original_msg->sent_at = time();
    
    $msg_id = $DB->insert_record('chat_messages', $original_msg);
    echo "✓ 원본 메시지 저장 성공 (ID: $msg_id)\n";
    
    // 변환 이력 저장 테스트
    $transformation = new stdClass();
    $transformation->teacher_id = $teacher_id;
    $transformation->student_id = $student_id;
    $transformation->original_message = $test_message;
    $transformation->transformed_message = isset($transformed) ? $transformed : $test_message;
    $transformation->teacher_mode = $persona_modes->teacher_mode;
    $transformation->student_mode = $persona_modes->student_mode;
    $transformation->transformation_time = time();
    
    $trans_id = $DB->insert_record('message_transformations', $transformation);
    echo "✓ 변환 이력 저장 성공 (ID: $trans_id)\n";
    
    // 테스트 데이터 삭제
    $DB->delete_records('chat_messages', array('id' => $msg_id));
    $DB->delete_records('message_transformations', array('id' => $trans_id));
    echo "✓ 테스트 데이터 삭제 완료\n";
    
} catch (Exception $e) {
    echo "✗ 데이터베이스 오류: " . $e->getMessage() . "\n";
    echo "상세: " . $e->getTraceAsString() . "\n";
}

echo "</pre>";

// AJAX 테스트 폼
?>
<hr>
<h3>AJAX 메시지 전송 테스트</h3>
<form id="testForm">
    <input type="hidden" id="student_id" value="<?php echo $student_id; ?>">
    <textarea id="test_message" rows="3" cols="50"><?php echo $test_message; ?></textarea><br>
    <button type="button" onclick="testSend()">테스트 전송</button>
</form>

<div id="result"></div>

<script>
async function testSend() {
    const message = document.getElementById('test_message').value;
    const studentId = document.getElementById('student_id').value;
    
    const formData = new FormData();
    formData.append('action', 'send_message');
    formData.append('message', message);
    formData.append('student_id', studentId);
    
    try {
        const response = await fetch('chat.php', {
            method: 'POST',
            body: formData
        });
        
        const text = await response.text();
        document.getElementById('result').innerHTML = '<pre>' + text + '</pre>';
        
        try {
            const json = JSON.parse(text);
            console.log('파싱된 JSON:', json);
        } catch (e) {
            console.error('JSON 파싱 실패:', e);
        }
    } catch (error) {
        document.getElementById('result').innerHTML = '<pre style="color: red;">오류: ' + error.message + '</pre>';
    }
}
</script>