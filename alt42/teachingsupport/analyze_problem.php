<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
require_once(__DIR__ . '/config.php');
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// CORS 헤더 설정 (필요시)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 이미지 URL을 base64로 변환하는 함수
function imageUrlToBase64($imageUrl) {
    if (empty($imageUrl)) {
        return null;
    }
    
    // 이미 base64 데이터인 경우
    if (strpos($imageUrl, 'data:') === 0) {
        return $imageUrl;
    }
    
    // 절대 URL인 경우
    if (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0) {
        $imageData = @file_get_contents($imageUrl);
        if ($imageData === false) {
            error_log("analyze_problem.php - Failed to fetch image from URL: $imageUrl [Line: " . __LINE__ . "]");
            return null;
        }
        $mimeType = 'image/jpeg'; // 기본값
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
        if ($detectedMime) {
            $mimeType = $detectedMime;
        }
        $base64 = base64_encode($imageData);
        return "data:{$mimeType};base64,{$base64}";
    }
    
    // 상대 경로인 경우 (images/ 또는 로컬 파일)
    $fullPath = __DIR__ . '/' . ltrim($imageUrl, '/');
    if (file_exists($fullPath)) {
        $imageData = file_get_contents($fullPath);
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fullPath);
        finfo_close($finfo);
        $base64 = base64_encode($imageData);
        return "data:{$mimeType};base64,{$base64}";
    }
    
    // mathking.kr 도메인 URL로 변환 시도
    $fullUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' . ltrim($imageUrl, '/');
    $imageData = @file_get_contents($fullUrl);
    if ($imageData !== false) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedMime = finfo_buffer($finfo, $imageData);
        finfo_close($finfo);
        $mimeType = $detectedMime ?: 'image/jpeg';
        $base64 = base64_encode($imageData);
        return "data:{$mimeType};base64,{$base64}";
    }
    
    error_log("analyze_problem.php - Failed to convert image URL to base64: $imageUrl [Line: " . __LINE__ . "]");
    return null;
}

try {
    $problemType = $_POST['problemType'] ?? '';
    $studentId = $_POST['studentId'] ?? '';
    $modificationPrompt = $_POST['modificationPrompt'] ?? '';
    $interactionId = $_POST['interactionId'] ?? null;
    $solutionStyle = $_POST['solutionStyle'] ?? 'default';
    
    $problemImageBase64 = null;
    $solutionImageBase64 = null;
    $imageUrl = '';
    
    // interactionId가 있고 이미지 파일이 업로드되지 않은 경우, 데이터베이스에서 이미지 가져오기
    if ($interactionId && (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK)) {
        error_log("analyze_problem.php - Fetching images from database for interaction ID: $interactionId [Line: " . __LINE__ . "]");
        
        $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $interactionId));
        
        if (!$interaction) {
            throw new Exception('상호작용 데이터를 찾을 수 없습니다. [analyze_problem.php:' . __LINE__ . ']');
        }
        
        // problem_image 가져오기
        if (!empty($interaction->problem_image)) {
            $problemImageBase64 = imageUrlToBase64($interaction->problem_image);
            if ($problemImageBase64) {
                error_log("analyze_problem.php - Successfully loaded problem_image from database [Line: " . __LINE__ . "]");
            } else {
                error_log("analyze_problem.php - Failed to load problem_image from database [Line: " . __LINE__ . "]");
            }
        }
        
        // solution_image 가져오기 (있는 경우)
        if (!empty($interaction->solution_image)) {
            $solutionImageBase64 = imageUrlToBase64($interaction->solution_image);
            if ($solutionImageBase64) {
                error_log("analyze_problem.php - Successfully loaded solution_image from database [Line: " . __LINE__ . "]");
            } else {
                error_log("analyze_problem.php - Failed to load solution_image from database [Line: " . __LINE__ . "]");
            }
        }
        
        // problem_image가 없으면 오류
        if (!$problemImageBase64) {
            throw new Exception('문제 이미지를 찾을 수 없습니다. [analyze_problem.php:' . __LINE__ . ']');
        }
        
        // problemType이 없으면 DB에서 가져오기
        if (empty($problemType) && !empty($interaction->problem_type)) {
            $problemType = $interaction->problem_type;
        }
        
        // imageUrl은 problem_image 사용
        $imageUrl = $interaction->problem_image;
        
    } else {
        // 기존 로직: 업로드된 이미지 파일 처리
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('이미지 업로드 실패 [analyze_problem.php:' . __LINE__ . ']');
        }

        $uploadedFile = $_FILES['image'];
        
        // 이미지를 base64로 인코딩
        $imageData = file_get_contents($uploadedFile['tmp_name']);
        $base64Image = base64_encode($imageData);
        
        // 파일 타입 확인
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $uploadedFile['tmp_name']);
        finfo_close($finfo);
        
        $problemImageBase64 = "data:{$mimeType};base64,{$base64Image}";
        
        // 이미지 파일을 images 폴더에 저장
        $imagesDir = __DIR__ . '/images/';
        if (!file_exists($imagesDir)) {
            mkdir($imagesDir, 0755, true);
        }
        
        // 고유한 파일명 생성
        $fileExtension = pathinfo($uploadedFile['name'], PATHINFO_EXTENSION);
        $uniqueFilename = 'problem_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $imagePath = $imagesDir . $uniqueFilename;
        
        // 이미지 파일 저장
        if (!move_uploaded_file($uploadedFile['tmp_name'], $imagePath)) {
            throw new Exception('이미지 파일 저장 실패 [analyze_problem.php:' . __LINE__ . ']');
        }
        
        // 웹 접근 가능한 상대 경로
        $imageUrl = 'images/' . $uniqueFilename;
    }

    // 문제 유형에 따른 프롬프트 커스터마이징
    $typeDescriptions = [
        'exam' => '내신 기출문제',
        'school' => '학교 프린트 문제',
        'mathking' => 'MathKing 문제',
        'textbook' => '시중교재 문제'
    ];
    
    $problemTypeDesc = $typeDescriptions[$problemType] ?? '일반 문제';

    // JSON 파일에서 해설지 생성 프롬프트 로드 (optimize_prompt.php의 solutionGenerationPrompt 사용)
    $promptsFile = __DIR__ . '/prompts/hint_prompts.json';
    $solutionGenerationPrompt = null;
    $promptsLoaded = false;
    
    if (file_exists($promptsFile)) {
        $promptsData = json_decode(file_get_contents($promptsFile), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // 해설지 생성 프롬프트 (solutionGenerationPrompt) 사용 - TTS가 아닌 실제 해설지 형식
            if (isset($promptsData['solutionGenerationPrompt']['systemPrompt'])) {
                $solutionGenerationPrompt = $promptsData['solutionGenerationPrompt']['systemPrompt'];
                $promptsLoaded = true;
                error_log("analyze_problem.php - solutionGenerationPrompt 로드 성공 [Line: " . __LINE__ . "]");
            } else {
                error_log("analyze_problem.php - solutionGenerationPrompt 없음, fallback 사용 [Line: " . __LINE__ . "]");
            }
        } else {
            error_log("analyze_problem.php - JSON 파싱 실패 [Line: " . __LINE__ . "]");
        }
    } else {
        error_log("analyze_problem.php - 프롬프트 파일 없음: $promptsFile [Line: " . __LINE__ . "]");
    }
    
    // JSON 로드 실패시 기본 해설지 프롬프트 사용 (fallback - LaTeX 수식 포함 해설지 형식)
    if (!$promptsLoaded || empty($solutionGenerationPrompt)) {
        error_log("analyze_problem.php - 기본 해설지 프롬프트 사용 (fallback) [Line: " . __LINE__ . "]");
        $solutionGenerationPrompt = '당신은 한국의 우수한 수학 교사입니다. 학생들이 이해하기 쉽도록 단계별로 문제를 해설해주세요.

중요: 모든 수식은 반드시 LaTeX 형식으로 작성해주세요.
- 인라인 수식: $수식$ (예: $x^2 + 2x + 1 = 0$)
- 별도 줄 수식: $$수식$$ (예: $$\\frac{-b \\pm \\sqrt{b^2-4ac}}{2a}$$)
- 분수는 \\frac{분자}{분모}
- 제곱근은 \\sqrt{내용}
- 지수는 ^{지수}
- 아래첨자는 _{아래첨자}

다음 형식으로 답변해주세요:

[문제 분석]
- 문제 유형과 난이도를 분석

[풀이 과정]
- 단계별로 상세하게 설명
- 각 단계마다 이유와 원리 설명
- 모든 수식은 LaTeX 형식 사용

[정답]
- 최종 답안 제시 (LaTeX 형식)

[핵심 개념]
- 이 문제를 풀기 위해 알아야 할 핵심 개념들

[유사 문제]
- 비슷한 유형의 문제 예시나 연습 방법';
    }

    // 해설지 생성 프롬프트 사용 (TTS 형식이 아닌 실제 해설지)
    $systemPrompt = $solutionGenerationPrompt;
    error_log("analyze_problem.php - Using solutionGenerationPrompt for solution_text (from " . ($promptsLoaded ? 'JSON' : 'fallback') . ") [Line: " . __LINE__ . "]");

    // solution_image가 있는 경우 프롬프트 수정
    if ($solutionImageBase64) {
        $systemPrompt .= "\n\n중요: 해설 이미지(solution_image)가 제공되었습니다. 이 해설 이미지를 중심으로 답변을 생성해주세요. 해설 이미지의 내용을 자세히 분석하고, 그 내용을 바탕으로 위 스타일에 맞춰 풀이 과정을 설명해주세요.";
    }
    
    $messages = [
        [
            'role' => 'system',
            'content' => $systemPrompt
        ]
    ];
    
    // 사용자 메시지 구성
    $userContent = [];
    
    // 텍스트 프롬프트
    $textPrompt = "다음 {$problemTypeDesc}를 분석하고 자세히 해설해주세요.";
    
    // solution_image가 있는 경우
    if ($solutionImageBase64) {
        $textPrompt .= "\n\n해설 이미지가 제공되었습니다. 이 해설 이미지를 중심으로 풀이 과정을 설명해주세요. 해설 이미지의 내용을 자세히 분석하고, 그 내용을 바탕으로 단계별로 설명해주세요.";
        
        // 해설 이미지를 먼저 추가 (중심이므로)
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $solutionImageBase64
            ]
        ];
        
        // 문제 이미지도 추가 (참고용)
        if ($problemImageBase64) {
            $userContent[] = [
                'type' => 'text',
                'text' => "\n[문제 이미지 참고]"
            ];
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $problemImageBase64
                ]
            ];
        }
    } else {
        // solution_image가 없는 경우 기존 로직 (problem_image만 사용)
        if ($problemImageBase64) {
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $problemImageBase64
                ]
            ];
        }
    }
    
    // 수정 프롬프트 추가
    if (!empty($modificationPrompt)) {
        $textPrompt .= "\n\n특히 다음 사항을 중점적으로 설명해주세요: {$modificationPrompt}";
    }
    
    // 텍스트 프롬프트를 맨 앞에 추가
    array_unshift($userContent, [
        'type' => 'text',
        'text' => $textPrompt
    ]);
    
    $messages[] = [
        'role' => 'user',
        'content' => $userContent
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => OPENAI_MODEL, // o3 모델은 아직 사용 불가
        'messages' => $messages,
        'max_tokens' => 2000,
        'temperature' => 0.7
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? 'Unknown error';
        throw new Exception('OpenAI API 호출 실패: HTTP ' . $httpCode . ' - ' . $errorMessage);
    }

    $responseData = json_decode($response, true);
    
    if (!isset($responseData['choices'][0]['message']['content'])) {
        throw new Exception('OpenAI 응답 형식 오류');
    }

    $solution = $responseData['choices'][0]['message']['content'];

    // 데이터베이스에 기록 저장 (선택사항)
    $record = new stdClass();
    $record->userid = $USER->id;
    $record->studentid = $studentId;
    $record->problemtype = $problemType;
    $record->solution = $solution;
    $record->timecreated = time();
    
    // teaching_solutions 테이블이 있다고 가정
    // $DB->insert_record('teaching_solutions', $record);

    // interactionId가 있으면 save_interaction.php를 통해 업데이트
    if ($interactionId) {
        error_log("analyze_problem.php - Updating existing interaction ID: $interactionId");
        
        // save_interaction.php API 호출하여 solution 업데이트
        $updateData = [
            'action' => 'update_solution',
            'interactionId' => $interactionId,
            'solution' => $solution,
            'imageUrl' => $imageUrl
        ];
        
        $ch = curl_init('http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/save_interaction.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($updateData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
        ]);
        
        $updateResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("analyze_problem.php - Failed to update interaction: HTTP $httpCode");
        } else {
            error_log("analyze_problem.php - Successfully updated interaction");
        }
    }
    
    echo json_encode([
        'success' => true,
        'solution' => $solution,
        'problemType' => $problemTypeDesc,
        'imageUrl' => $imageUrl,  // 저장된 이미지 URL 반환
        'interactionId' => $interactionId
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>