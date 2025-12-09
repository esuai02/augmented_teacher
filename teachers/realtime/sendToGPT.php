<?php
// ---------------------------------------------------------
// sendToGPT.php
// 클라이언트에서 받은 데이터를 바탕으로 OpenAI GPT API를 호출하고, 결과를 JSON으로 반환
// ---------------------------------------------------------

// 1) POST된 JSON 데이터 읽기
$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

// $data 구조 예시:
// [
//   "fullImage"   => "data:image/png;base64,iVBOR...",
//   "partialImage"=> "data:image/png;base64,iVBOR...",
//   "gazeCoords"  => [ "x" => 123, "y" => 456 ],
//   "cnttext"     => "실제 컨텐츠 정보..."
// ]

// 2) OpenAI API 호출을 위한 준비
// 실제로는 Composer 등으로 설치한 라이브러리를 이용하거나, cURL을 직접 사용 가능
// 아래는 cURL 예시
$openaiApiKey = "YOUR_OPENAI_API_KEY"; // 보안을 위해 .env 등에 보관 권장

// 여기에 GPT에게 보낼 '프롬프트' 구성
// 이미지(부분/전체)는 Base64 형태로 가지고 있으나, 
// GPT-3.5-turbo(텍스트 기반)로 단순 텍스트 질문하는 경우에는
// "이미지" 자체를 분석하진 못함 (GPT-4 Vision은 별도 권한 필요).
// 여기서는 시연 예시로, 시선좌표/컨텐츠 정보만 보낸다고 가정.
$question = "
사용자의 시선 좌표: (" . $data["gazeCoords"]["x"] . ", " . $data["gazeCoords"]["y"] . ")
컨텐츠 내용: " . $data["cnttext"] . "

아래 Base64 이미지 2종이 있음:
[전체 화면 이미지] 
" . substr($data["fullImage"], 0, 100) . "... (생략)

[시선 주변 200x200 이미지] 
" . substr($data["partialImage"], 0, 100) . "... (생략)

위 정보를 참고하여, 사용자의 상태나 의도를 짐작하거나,
간단한 피드백을 제시해줘.
";

// 3) cURL로 OpenAI API 호출
$ch = curl_init("https://api.openai.com/v1/chat/completions");
$headers = [
    "Content-Type: application/json",
    "Authorization: Bearer $openaiApiKey"
];

$postData = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => "You are a helpful assistant."],
        ["role" => "user", "content" => $question]
    ],
    "max_tokens" => 150,
    "temperature" => 0.7
];

curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    $error_msg = curl_error($ch);
    curl_close($ch);
    // 에러 시 JSON 형태로 반환
    echo json_encode(["error" => $error_msg]);
    exit;
}
curl_close($ch);

// 4) GPT 응답 파싱
$gptResult = json_decode($response, true);
$feedbackText = $gptResult["choices"][0]["message"]["content"] ?? "GPT 응답 없음";

// 5) 클라이언트로 JSON 전달
echo json_encode(["feedback" => $feedbackText]);