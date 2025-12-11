<?php
// OpenAI API 설정
// Moodle $CFG에서 API 키를 가져옵니다.

// Moodle config.php 로드 (아직 로드되지 않은 경우에만)
if (!isset($CFG)) {
    include_once("/home/moodle/public_html/moodle/config.php");
}
global $CFG;

// API 키를 $CFG에서 가져오기
$openai_api_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($openai_api_key)) {
    $openai_api_key = getenv('OPENAI_API_KEY');
}

define('OPENAI_API_KEY', $openai_api_key);
define('OPENAI_MODEL', 'gpt-4o'); // o3 모델 출시 시 'o3'로 변경

// API 설정
define('OPENAI_API_URL', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MAX_TOKENS', 1500);
define('OPENAI_TEMPERATURE', 0.0);
define('OPENAI_TIMEOUT', 30); // 30초 타임아웃

// 데이터베이스 테이블 이름 (필요시)
define('TABLE_TEACHING_SOLUTIONS', 'teaching_solutions');

// OpenAI API 호출 기본 함수
function callOpenAI($prompt, $systemMessage = '', $temperature = null, $maxTokens = null) {
    // API 키 검증
    if (empty(OPENAI_API_KEY) || OPENAI_API_KEY === 'your_api_key_here') {
        throw new Exception('OpenAI API 키가 설정되지 않았습니다.');
    }
    
    // 기본값 설정
    $temperature = $temperature ?? OPENAI_TEMPERATURE;
    $maxTokens = $maxTokens ?? OPENAI_MAX_TOKENS;
    
    // 메시지 구성
    $messages = [];
    if (!empty($systemMessage)) {
        $messages[] = ['role' => 'system', 'content' => $systemMessage];
    }
    $messages[] = ['role' => 'user', 'content' => $prompt];
    
    // API 요청 데이터
    $requestData = [
        'model' => OPENAI_MODEL,
        'messages' => $messages,
        'max_tokens' => $maxTokens,
        'temperature' => $temperature
    ];
    
    // cURL 설정
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => OPENAI_API_URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($requestData),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => OPENAI_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => false, // 필요시 true로 변경
    ]);
    
    // API 호출 실행
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // cURL 오류 체크
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    // 응답 파싱
    $responseData = json_decode($response, true);
    
    if ($httpCode !== 200) {
        $errorMessage = isset($responseData['error']['message']) 
            ? $responseData['error']['message'] 
            : 'HTTP Error ' . $httpCode;
        throw new Exception('OpenAI API Error: ' . $errorMessage);
    }
    
    if (!isset($responseData['choices'][0]['message']['content'])) {
        throw new Exception('Invalid API response format');
    }
    
    return $responseData;
}

// 학습 완성도 분석을 위한 특화 함수
function analyzeCompletionScore($activityData, $studentContext = '') {
    $systemMessage = "당신은 수학 학습 전문가입니다. 학생의 학습 활동 데이터를 분석하여 완성도를 평가하고 개선점을 제안해주세요.

평가 기준:
1. 학습 시간의 적절성 (너무 짧거나 길지 않은지)
2. 학습 활동의 집중도 (작성량, 수정 횟수 등)
3. 피드백 활용도 (선생님 피드백에 대한 반응)
4. 학습 진행 상황의 일관성

응답 형식:
- 완성도 점수 (0-100점)
- 강점 2-3개
- 개선점 2-3개
- 구체적 조언 1-2개

간결하고 건설적인 피드백을 제공해주세요.";

    $prompt = "학습 활동 데이터:\n" . json_encode($activityData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    if (!empty($studentContext)) {
        $prompt .= "\n\n학생 배경정보:\n" . $studentContext;
    }
    
    try {
        $response = callOpenAI($prompt, $systemMessage, 0.3, 800); // 낮은 temperature로 일관성 확보
        return $response['choices'][0]['message']['content'];
    } catch (Exception $e) {
        error_log('OpenAI Analysis Error: ' . $e->getMessage());
        return null;
    }
}

// 고급 개인맞춤형 피드백 생성 시스템
function generateAdvancedPersonalizedFeedback($analysisResult, $studentProfile = [], $learningGoals = []) {
    $systemMessage = "당신은 경험 많은 수학 교육 전문가이자 학습 멘토입니다. 
학생 개개인의 특성과 학습 패턴을 깊이 이해하고, 정확한 분석을 바탕으로 동기부여가 되는 맞춤형 피드백을 제공합니다.

피드백 작성 원칙:
1. 개인화된 접근: 학생의 성향, 수준, 목표에 맞춤
2. 구체적이고 실행 가능한 조언 제공
3. 긍정적 강화와 건설적 개선점 균형
4. 단계적 목표 설정으로 성취감 증대
5. 수학적 사고력 향상에 초점

응답 형식 (JSON):
{
  \"current_status\": \"현재 학습 상태 요약 (2-3문장)\",
  \"achievements\": [\"구체적 성취 1\", \"구체적 성취 2\"],
  \"improvement_plans\": [
    {
      \"area\": \"개선 영역\",
      \"current_issue\": \"현재 문제점\",
      \"action_steps\": [\"구체적 실행 방법 1\", \"구체적 실행 방법 2\"],
      \"expected_outcome\": \"기대 효과\"
    }
  ],
  \"weekly_goals\": [\"이번 주 목표 1\", \"이번 주 목표 2\", \"이번 주 목표 3\"],
  \"motivation_message\": \"개인맞춤형 격려 메시지 (따뜻하고 구체적)\",
  \"learning_tips\": [\"학습 팁 1\", \"학습 팁 2\"],
  \"next_focus\": \"다음 집중 학습 영역\",
  \"difficulty_level\": \"현재|높음|낮음\"
}";

    // 피드백 프롬프트 생성
    $prompt = "=== 학습 분석 결과 ===\n";
    $prompt .= "종합 점수: " . ($analysisResult['overall_score'] ?? 0) . "점\n";
    
    if (isset($analysisResult['detailed_scores'])) {
        $prompt .= "세부 점수:\n";
        $prompt .= "- 개념 이해도: " . ($analysisResult['detailed_scores']['concept_understanding'] ?? 0) . "/25점\n";
        $prompt .= "- 문제 해결: " . ($analysisResult['detailed_scores']['problem_solving'] ?? 0) . "/30점\n";
        $prompt .= "- 학습 지속성: " . ($analysisResult['detailed_scores']['learning_persistence'] ?? 0) . "/20점\n";
        $prompt .= "- 피드백 활용: " . ($analysisResult['detailed_scores']['feedback_utilization'] ?? 0) . "/25점\n\n";
    }
    
    if (!empty($analysisResult['strengths'])) {
        $prompt .= "강점: " . implode(', ', $analysisResult['strengths']) . "\n";
    }
    
    if (!empty($analysisResult['improvements'])) {
        $prompt .= "개선점: " . implode(', ', $analysisResult['improvements']) . "\n";
    }
    
    if (!empty($analysisResult['learning_trend'])) {
        $prompt .= "학습 추세: " . $analysisResult['learning_trend'] . "\n";
    }
    
    if (!empty($analysisResult['connection_points'])) {
        $prompt .= "\n=== 연결지점별 분석 ===\n";
        foreach ($analysisResult['connection_points'] as $point) {
            $prompt .= "- " . ($point['topic'] ?? '연결점') . ": " . ($point['score'] ?? 0) . "점\n";
            if (!empty($point['analysis'])) {
                $prompt .= "  " . $point['analysis'] . "\n";
            }
        }
    }
    
    // 학생 프로필 정보 추가
    if (!empty($studentProfile)) {
        $prompt .= "\n=== 학생 정보 ===\n";
        if (isset($studentProfile['name'])) {
            $prompt .= "이름: " . $studentProfile['name'] . "\n";
        }
        if (isset($studentProfile['grade_level'])) {
            $prompt .= "학년: " . $studentProfile['grade_level'] . "\n";
        }
        if (isset($studentProfile['learning_style'])) {
            $prompt .= "학습 스타일: " . $studentProfile['learning_style'] . "\n";
        }
        if (isset($studentProfile['motivation_type'])) {
            $prompt .= "동기 유형: " . $studentProfile['motivation_type'] . "\n";
        }
    }
    
    // 학습 목표 정보 추가
    if (!empty($learningGoals)) {
        $prompt .= "\n=== 최근 학습 목표 ===\n";
        foreach ($learningGoals as $goal) {
            $prompt .= "- " . $goal . "\n";
        }
    }
    
    $prompt .= "\n위 정보를 종합하여 학생에게 도움이 되는 개인맞춤형 피드백을 JSON 형식으로 생성해주세요.";
    
    try {
        $response = callOpenAI($prompt, $systemMessage, 0.7, 1000);
        $feedbackText = $response['choices'][0]['message']['content'];
        
        // JSON 파싱 시도
        $parsedFeedback = parseFeedbackResult($feedbackText);
        
        return [
            'success' => true,
            'raw_feedback' => $feedbackText,
            'parsed_feedback' => $parsedFeedback,
            'token_usage' => $response['usage'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log('Advanced Feedback Generation Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'fallback_feedback' => generateFallbackFeedback($analysisResult)
        ];
    }
}

// 피드백 결과 파싱 함수
function parseFeedbackResult($feedbackText) {
    // JSON 추출 시도
    $jsonStart = strpos($feedbackText, '{');
    $jsonEnd = strrpos($feedbackText, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonString = substr($feedbackText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $parsed = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return [
                'current_status' => $parsed['current_status'] ?? '학습 상태를 분석중입니다.',
                'achievements' => $parsed['achievements'] ?? [],
                'improvement_plans' => $parsed['improvement_plans'] ?? [],
                'weekly_goals' => $parsed['weekly_goals'] ?? [],
                'motivation_message' => $parsed['motivation_message'] ?? '꾸준한 노력이 성과로 이어질 것입니다!',
                'learning_tips' => $parsed['learning_tips'] ?? [],
                'next_focus' => $parsed['next_focus'] ?? '기본 개념 복습',
                'difficulty_level' => $parsed['difficulty_level'] ?? '현재'
            ];
        }
    }
    
    // JSON 파싱 실패 시 텍스트 분석
    return parseTextFeedback($feedbackText);
}

// 텍스트 기반 피드백 파싱
function parseTextFeedback($text) {
    $result = [
        'current_status' => '현재 학습 상태를 분석하고 있습니다.',
        'achievements' => [],
        'improvement_plans' => [],
        'weekly_goals' => [],
        'motivation_message' => '꾸준한 노력이 성공의 열쇠입니다!',
        'learning_tips' => [],
        'next_focus' => '기본 개념 복습',
        'difficulty_level' => '현재'
    ];
    
    // 간단한 텍스트 파싱으로 주요 내용 추출
    $lines = explode("\n", $text);
    $currentSection = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        if (strpos($line, '성취') !== false || strpos($line, '잘한') !== false) {
            $result['achievements'][] = $line;
        } elseif (strpos($line, '목표') !== false) {
            $result['weekly_goals'][] = $line;
        } elseif (strpos($line, '팁') !== false || strpos($line, '방법') !== false) {
            $result['learning_tips'][] = $line;
        }
    }
    
    // 동기부여 메시지는 전체 텍스트의 마지막 부분에서 추출
    if (strlen($text) > 100) {
        $result['motivation_message'] = substr($text, -100) . '...';
    }
    
    return $result;
}

// 폴백 피드백 생성 (OpenAI 실패 시)
function generateFallbackFeedback($analysisResult) {
    $overallScore = $analysisResult['overall_score'] ?? 0;
    $learningTrend = $analysisResult['learning_trend'] ?? '분석 불가';
    
    $motivationLevel = $overallScore >= 80 ? 'high' : ($overallScore >= 60 ? 'medium' : 'low');
    
    $messages = [
        'high' => [
            'current_status' => '훌륭한 학습 성과를 보이고 있습니다! 현재 수준을 잘 유지하고 있어요.',
            'motivation_message' => '정말 잘하고 있어요! 이 페이스로 계속 나아가면 더 큰 성과를 얻을 수 있을 거예요.',
            'next_focus' => '심화 학습 및 응용 문제'
        ],
        'medium' => [
            'current_status' => '안정적인 학습 패턴을 보이고 있습니다. 조금만 더 노력하면 큰 발전이 있을 거예요.',
            'motivation_message' => '꾸준히 노력하고 있는 모습이 보기 좋아요. 포기하지 말고 계속 도전해보세요!',
            'next_focus' => '기본 개념 강화 및 반복 학습'
        ],
        'low' => [
            'current_status' => '학습의 기초를 다지는 시간이에요. 천천히 차근차근 해나가면 됩니다.',
            'motivation_message' => '시작이 반이에요! 작은 성취라도 축하하며 단계별로 나아가세요.',
            'next_focus' => '기본 개념 이해 및 기초 문제 연습'
        ]
    ];
    
    $selectedMessage = $messages[$motivationLevel];
    
    return [
        'current_status' => $selectedMessage['current_status'],
        'achievements' => $overallScore >= 70 ? ['꾸준한 학습 노력', '긍정적인 학습 태도'] : ['학습에 대한 의지'],
        'improvement_plans' => [
            [
                'area' => '학습 시간',
                'current_issue' => '더 많은 학습 시간 필요',
                'action_steps' => ['매일 30분씩 추가 학습', '복습 시간 확보'],
                'expected_outcome' => '학습 효과 증대'
            ]
        ],
        'weekly_goals' => ['매일 꾸준한 학습', '기본 개념 복습', '문제 해결 연습'],
        'motivation_message' => $selectedMessage['motivation_message'],
        'learning_tips' => ['작은 목표부터 시작하기', '꾸준함이 가장 중요해요'],
        'next_focus' => $selectedMessage['next_focus'],
        'difficulty_level' => $motivationLevel === 'high' ? '높음' : ($motivationLevel === 'medium' ? '현재' : '낮음')
    ];
}

// 기존 단순 피드백 생성 함수 (호환성 유지)
function generatePersonalizedFeedback($completionData, $strengths, $improvements) {
    $systemMessage = "당신은 친근하고 격려적인 수학 학습 멘토입니다. 학생에게 동기부여가 되는 개인맞춤형 피드백을 작성해주세요.

작성 원칙:
1. 긍정적이고 격려적인 톤
2. 구체적이고 실행 가능한 조언
3. 학생의 성취를 인정하면서 다음 단계 제시
4. 200-300자 내외의 적절한 길이

피드백 구조:
- 성취 인정 및 격려
- 구체적 개선 방안
- 다음 학습 목표 제시";

    $prompt = "완성도 데이터: " . json_encode($completionData, JSON_UNESCAPED_UNICODE) . "\n";
    $prompt .= "강점: " . implode(', ', $strengths) . "\n";
    $prompt .= "개선점: " . implode(', ', $improvements);
    
    try {
        $response = callOpenAI($prompt, $systemMessage, 0.8, 500); // 높은 temperature로 창의적 표현
        return $response['choices'][0]['message']['content'];
    } catch (Exception $e) {
        error_log('OpenAI Feedback Error: ' . $e->getMessage());
        return null;
    }
}

// 고급 학습완성도 분석 엔진
function analyzeLearningCompletion($activityData, $studentContext = '', $timeRange = []) {
    $systemPrompt = "당신은 수학 교육 전문가이자 학습 분석 전문가입니다. 
학생의 학습 활동 데이터를 종합적으로 분석하여 각 연결지점에서의 학습 완성도를 정확히 평가해주세요.

분석 기준:
1. 개념 이해도 (25점): 학습 시간과 반복 학습 패턴 분석
2. 문제 해결 능력 (30점): 작성량, 수정 횟수, 완료까지의 과정 분석  
3. 학습 지속성 (20점): 꾸준한 학습 패턴과 집중도 분석
4. 피드백 활용도 (25점): 선생님 피드백에 대한 반응과 개선도 분석

연결지점별 완성도 평가:
- 각 학습 활동의 연결 관계 파악
- 선행 학습과 후행 학습의 연계성 분석
- 개념 간 이해의 연결 고리 평가

응답은 반드시 다음 JSON 형식으로 제공해주세요:
{
  \"overall_score\": 85,
  \"detailed_scores\": {
    \"concept_understanding\": 22,
    \"problem_solving\": 25, 
    \"learning_persistence\": 18,
    \"feedback_utilization\": 20
  },
  \"connection_points\": [
    {
      \"topic\": \"연결점 주제명\",
      \"score\": 90,
      \"analysis\": \"상세 분석 내용\"
    }
  ],
  \"strengths\": [\"강점 1\", \"강점 2\"],
  \"improvements\": [\"개선점 1\", \"개선점 2\"],
  \"recommendations\": [\"구체적 추천사항 1\", \"구체적 추천사항 2\"],
  \"learning_trend\": \"향상중|유지중|하락중\",
  \"next_focus\": \"다음 집중 학습 영역\"
}";

    // 학습 데이터 요약 생성
    $analysisPrompt = "학습 활동 데이터 분석 요청:\n\n";
    $analysisPrompt .= "=== 기본 통계 ===\n";
    $analysisPrompt .= "총 활동 수: " . ($activityData['total_activities'] ?? 0) . "개\n";
    $analysisPrompt .= "총 학습 시간: " . ($activityData['total_time_minutes'] ?? 0) . "분\n";
    $analysisPrompt .= "평균 활동 시간: " . ($activityData['avg_time_per_activity'] ?? 0) . "분\n";
    $analysisPrompt .= "받은 피드백: " . ($activityData['feedback_received'] ?? 0) . "회\n";
    $analysisPrompt .= "총 작성량: " . ($activityData['total_strokes'] ?? 0) . "회\n\n";
    
    if (!empty($timeRange)) {
        $analysisPrompt .= "=== 분석 기간 ===\n";
        $analysisPrompt .= "시작: " . date('Y-m-d H:i', $timeRange['begin'] ?? time()) . "\n";
        $analysisPrompt .= "종료: " . date('Y-m-d H:i', $timeRange['end'] ?? time()) . "\n\n";
    }
    
    if (!empty($studentContext)) {
        $analysisPrompt .= "=== 학생 배경정보 ===\n";
        $analysisPrompt .= $studentContext . "\n\n";
    }
    
    if (!empty($activityData['recent_activities'])) {
        $analysisPrompt .= "=== 최근 학습 활동 상세 ===\n";
        foreach ($activityData['recent_activities'] as $index => $activity) {
            $analysisPrompt .= ($index + 1) . ". " . ($activity['title'] ?? '활동') . "\n";
            $analysisPrompt .= "   - 소요시간: " . ($activity['time'] ?? 0) . "분\n";
            $analysisPrompt .= "   - 작성량: " . ($activity['strokes'] ?? 0) . "회\n";
            $analysisPrompt .= "   - 피드백: " . ($activity['feedback'] ?? 0) . "회\n";
            $analysisPrompt .= "   - 상태: " . ($activity['status'] ?? '미완료') . "\n\n";
        }
    }
    
    $analysisPrompt .= "위 데이터를 종합적으로 분석하여 학습 완성도를 평가하고, 연결지점별 상세 분석을 제공해주세요.";
    
    try {
        $response = callOpenAI($analysisPrompt, $systemPrompt, 0.3, 1200);
        $analysisResult = $response['choices'][0]['message']['content'];
        
        // JSON 파싱 시도
        $parsedResult = parseAnalysisResult($analysisResult);
        
        return [
            'success' => true,
            'raw_analysis' => $analysisResult,
            'parsed_data' => $parsedResult,
            'token_usage' => $response['usage'] ?? null
        ];
        
    } catch (Exception $e) {
        error_log('Learning Completion Analysis Error: ' . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'fallback_analysis' => generateFallbackAnalysis($activityData)
        ];
    }
}

// AI 분석 결과 파싱 함수
function parseAnalysisResult($analysisText) {
    // JSON 추출 시도
    $jsonStart = strpos($analysisText, '{');
    $jsonEnd = strrpos($analysisText, '}');
    
    if ($jsonStart !== false && $jsonEnd !== false) {
        $jsonString = substr($analysisText, $jsonStart, $jsonEnd - $jsonStart + 1);
        $parsed = json_decode($jsonString, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // 필수 필드 검증 및 기본값 설정
            return [
                'overall_score' => $parsed['overall_score'] ?? 0,
                'detailed_scores' => $parsed['detailed_scores'] ?? [
                    'concept_understanding' => 0,
                    'problem_solving' => 0,
                    'learning_persistence' => 0,
                    'feedback_utilization' => 0
                ],
                'connection_points' => $parsed['connection_points'] ?? [],
                'strengths' => $parsed['strengths'] ?? [],
                'improvements' => $parsed['improvements'] ?? [],
                'recommendations' => $parsed['recommendations'] ?? [],
                'learning_trend' => $parsed['learning_trend'] ?? '분석 불가',
                'next_focus' => $parsed['next_focus'] ?? '미정'
            ];
        }
    }
    
    // JSON 파싱 실패 시 텍스트 분석으로 폴백
    return parseTextAnalysis($analysisText);
}

// 텍스트 기반 분석 결과 파싱
function parseTextAnalysis($text) {
    $result = [
        'overall_score' => 0,
        'detailed_scores' => [
            'concept_understanding' => 0,
            'problem_solving' => 0, 
            'learning_persistence' => 0,
            'feedback_utilization' => 0
        ],
        'connection_points' => [],
        'strengths' => [],
        'improvements' => [],
        'recommendations' => [],
        'learning_trend' => '분석 불가',
        'next_focus' => '미정'
    ];
    
    // 점수 추출 시도
    if (preg_match('/(\d+)점/', $text, $matches)) {
        $result['overall_score'] = intval($matches[1]);
    }
    
    // 강점/개선점 추출 시도  
    if (preg_match('/강점[:\s]*(.+?)(?=개선|$)/s', $text, $matches)) {
        $strengths = explode(',', $matches[1]);
        $result['strengths'] = array_map('trim', $strengths);
    }
    
    if (preg_match('/개선[:\s]*(.+?)(?=추천|권장|$)/s', $text, $matches)) {
        $improvements = explode(',', $matches[1]);
        $result['improvements'] = array_map('trim', $improvements);
    }
    
    return $result;
}

// 폴백 분석 생성 (OpenAI 실패 시)
function generateFallbackAnalysis($activityData) {
    $totalActivities = $activityData['total_activities'] ?? 0;
    $totalTime = $activityData['total_time_minutes'] ?? 0;
    $avgTime = $activityData['avg_time_per_activity'] ?? 0;
    $feedbackCount = $activityData['feedback_received'] ?? 0;
    $totalStrokes = $activityData['total_strokes'] ?? 0;
    
    // 기본 점수 계산 알고리즘
    $conceptScore = min(25, ($avgTime / 10) * 5); // 평균 시간 기반
    $problemScore = min(30, ($totalStrokes / 100) * 6); // 작성량 기반  
    $persistenceScore = min(20, ($totalActivities / 5) * 4); // 활동 수 기반
    $feedbackScore = min(25, $feedbackCount * 5); // 피드백 활용도
    
    $overallScore = $conceptScore + $problemScore + $persistenceScore + $feedbackScore;
    
    return [
        'overall_score' => round($overallScore),
        'detailed_scores' => [
            'concept_understanding' => round($conceptScore),
            'problem_solving' => round($problemScore),
            'learning_persistence' => round($persistenceScore), 
            'feedback_utilization' => round($feedbackScore)
        ],
        'connection_points' => [
            [
                'topic' => '전반적 학습 상태',
                'score' => round($overallScore),
                'analysis' => '기본 통계를 바탕으로 한 자동 분석 결과입니다.'
            ]
        ],
        'strengths' => $overallScore >= 70 ? ['꾸준한 학습 노력'] : [],
        'improvements' => $overallScore < 70 ? ['학습 시간 확대', '피드백 적극 활용'] : [],
        'recommendations' => ['AI 분석 시스템 재연결 후 더 정확한 분석 확인'],
        'learning_trend' => $overallScore >= 70 ? '유지중' : '개선 필요',
        'next_focus' => '기본 개념 복습'
    ];
}

// API 연결 테스트 함수
function testOpenAIConnection() {
    try {
        $response = callOpenAI(
            "안녕하세요! 연결 테스트입니다.", 
            "간단히 '연결 성공'이라고 답변해주세요.",
            0.1,
            50
        );
        
        return [
            'success' => true,
            'response' => $response['choices'][0]['message']['content'],
            'model' => OPENAI_MODEL,
            'usage' => $response['usage'] ?? null
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// ===== AI 분석 결과 캐싱 시스템 =====

/**
 * AI 분석 결과 캐시 테이블 생성
 * MySQL 스크립트를 실행하여 캐시 테이블을 생성합니다.
 */
function createAnalysisCacheTable() {
    global $DB;
    
    $sql = "CREATE TABLE IF NOT EXISTS mdl_abessi_ai_analysis_cache (
        id BIGINT PRIMARY KEY AUTO_INCREMENT,
        userid BIGINT NOT NULL,
        activity_hash VARCHAR(64) NOT NULL,
        analysis_result TEXT,
        feedback_result TEXT,
        analysis_type VARCHAR(50) DEFAULT 'openai_enhanced',
        token_usage TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at TIMESTAMP,
        INDEX idx_userid_hash (userid, activity_hash),
        INDEX idx_expires (expires_at),
        INDEX idx_userid_type (userid, analysis_type)
    )";
    
    try {
        $DB->execute($sql);
        return ['success' => true, 'message' => '캐시 테이블이 성공적으로 생성되었습니다.'];
    } catch (Exception $e) {
        error_log('Cache table creation error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 활동 데이터로부터 캐시 키 생성
 * 학습 활동 데이터의 해시값을 생성하여 캐시 키로 사용
 */
function generateActivityHash($activityData, $analysisType = 'openai_enhanced') {
    // 시간에 민감하지 않은 데이터만 해시에 포함
    $cacheData = [
        'total_activities' => $activityData['total_activities'] ?? 0,
        'total_time_minutes' => $activityData['total_time_minutes'] ?? 0,
        'total_strokes' => $activityData['total_strokes'] ?? 0,
        'feedback_received' => $activityData['feedback_received'] ?? 0,
        'analysis_type' => $analysisType
    ];
    
    // 최근 활동들의 기본 패턴만 포함 (상세 시간은 제외)
    if (!empty($activityData['recent_activities'])) {
        $recentPattern = [];
        foreach ($activityData['recent_activities'] as $activity) {
            $recentPattern[] = [
                'time_range' => intval(($activity['time'] ?? 0) / 10) * 10, // 10분 단위로 반올림
                'stroke_range' => intval(($activity['strokes'] ?? 0) / 50) * 50, // 50개 단위로 반올림
                'status' => $activity['status'] ?? 'unknown'
            ];
        }
        $cacheData['recent_pattern'] = $recentPattern;
    }
    
    return md5(json_encode($cacheData));
}

/**
 * 캐시된 분석 결과 조회
 * 사용자ID와 활동 데이터 해시로 유효한 캐시 결과를 찾습니다.
 */
function getCachedAnalysis($userid, $activityData, $analysisType = 'openai_enhanced') {
    global $DB;
    
    $activityHash = generateActivityHash($activityData, $analysisType);
    
    try {
        $cached = $DB->get_record_sql(
            "SELECT * FROM mdl_abessi_ai_analysis_cache 
             WHERE userid = ? AND activity_hash = ? AND analysis_type = ? 
             AND expires_at > NOW() 
             ORDER BY created_at DESC 
             LIMIT 1",
            [$userid, $activityHash, $analysisType]
        );
        
        if ($cached) {
            return [
                'success' => true,
                'cache_hit' => true,
                'data' => [
                    'analysis_result' => $cached->analysis_result ? json_decode($cached->analysis_result, true) : null,
                    'feedback_result' => $cached->feedback_result ? json_decode($cached->feedback_result, true) : null,
                    'analysis_type' => $cached->analysis_type,
                    'token_usage' => $cached->token_usage ? json_decode($cached->token_usage, true) : null,
                    'cached_at' => $cached->created_at,
                    'expires_at' => $cached->expires_at
                ]
            ];
        }
        
        return ['success' => true, 'cache_hit' => false];
        
    } catch (Exception $e) {
        error_log('Cache retrieval error: ' . $e->getMessage());
        return ['success' => false, 'cache_hit' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 분석 결과를 캐시에 저장
 * 새로운 분석 결과와 피드백을 캐시 테이블에 저장합니다.
 */
function setCachedAnalysis($userid, $activityData, $analysisResult, $feedbackResult = null, $analysisType = 'openai_enhanced', $tokenUsage = null) {
    global $DB;
    
    $activityHash = generateActivityHash($activityData, $analysisType);
    $expiresAt = date('Y-m-d H:i:s', time() + (24 * 3600)); // 24시간 TTL
    
    try {
        // 기존 캐시 삭제 (동일한 해시)
        $DB->delete_records('mdl_abessi_ai_analysis_cache', [
            'userid' => $userid,
            'activity_hash' => $activityHash,
            'analysis_type' => $analysisType
        ]);
        
        // 새 캐시 저장
        $cacheRecord = [
            'userid' => $userid,
            'activity_hash' => $activityHash,
            'analysis_result' => $analysisResult ? json_encode($analysisResult) : null,
            'feedback_result' => $feedbackResult ? json_encode($feedbackResult) : null,
            'analysis_type' => $analysisType,
            'token_usage' => $tokenUsage ? json_encode($tokenUsage) : null,
            'expires_at' => $expiresAt
        ];
        
        $insertId = $DB->insert_record('mdl_abessi_ai_analysis_cache', $cacheRecord);
        
        return [
            'success' => true,
            'cache_id' => $insertId,
            'expires_at' => $expiresAt,
            'message' => '분석 결과가 캐시에 저장되었습니다.'
        ];
        
    } catch (Exception $e) {
        error_log('Cache storage error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 만료된 캐시 정리
 * 만료된 캐시 레코드들을 삭제하여 데이터베이스 용량을 관리합니다.
 */
function cleanupExpiredCache() {
    global $DB;
    
    try {
        $deletedCount = $DB->count_records_sql(
            "SELECT COUNT(*) FROM mdl_abessi_ai_analysis_cache WHERE expires_at <= NOW()"
        );
        
        $DB->execute("DELETE FROM mdl_abessi_ai_analysis_cache WHERE expires_at <= NOW()");
        
        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => "만료된 캐시 {$deletedCount}개가 정리되었습니다."
        ];
        
    } catch (Exception $e) {
        error_log('Cache cleanup error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 사용자별 캐시 무효화
 * 특정 사용자의 모든 캐시를 무효화합니다.
 */
function invalidateUserCache($userid) {
    global $DB;
    
    try {
        $deletedCount = $DB->count_records('mdl_abessi_ai_analysis_cache', ['userid' => $userid]);
        $DB->delete_records('mdl_abessi_ai_analysis_cache', ['userid' => $userid]);
        
        return [
            'success' => true,
            'deleted_count' => $deletedCount,
            'message' => "사용자 {$userid}의 캐시 {$deletedCount}개가 무효화되었습니다."
        ];
        
    } catch (Exception $e) {
        error_log('User cache invalidation error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 캐시 통계 조회
 * 캐시 사용 현황과 통계를 반환합니다.
 */
function getCacheStatistics() {
    global $DB;
    
    try {
        $stats = [];
        
        // 전체 캐시 수
        $stats['total_cache_count'] = $DB->count_records('mdl_abessi_ai_analysis_cache');
        
        // 유효한 캐시 수
        $stats['valid_cache_count'] = $DB->count_records_sql(
            "SELECT COUNT(*) FROM mdl_abessi_ai_analysis_cache WHERE expires_at > NOW()"
        );
        
        // 만료된 캐시 수
        $stats['expired_cache_count'] = $DB->count_records_sql(
            "SELECT COUNT(*) FROM mdl_abessi_ai_analysis_cache WHERE expires_at <= NOW()"
        );
        
        // 분석 타입별 통계
        $typeStats = $DB->get_records_sql(
            "SELECT analysis_type, COUNT(*) as count 
             FROM mdl_abessi_ai_analysis_cache 
             WHERE expires_at > NOW() 
             GROUP BY analysis_type"
        );
        
        $stats['by_type'] = [];
        foreach ($typeStats as $stat) {
            $stats['by_type'][$stat->analysis_type] = $stat->count;
        }
        
        // 최근 24시간 캐시 히트율 (간접적 추정)
        $recentCacheCount = $DB->count_records_sql(
            "SELECT COUNT(*) FROM mdl_abessi_ai_analysis_cache 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        $stats['recent_24h_cache_count'] = $recentCacheCount;
        $stats['cache_efficiency'] = $stats['valid_cache_count'] > 0 ? 
            round(($stats['valid_cache_count'] / ($stats['total_cache_count'] ?: 1)) * 100, 2) : 0;
        
        return [
            'success' => true,
            'statistics' => $stats
        ];
        
    } catch (Exception $e) {
        error_log('Cache statistics error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 캐시 테이블 초기화 (개발/테스트용)
 * 주의: 모든 캐시 데이터가 삭제됩니다.
 */
function resetCacheTable() {
    global $DB;
    
    try {
        $DB->execute("TRUNCATE TABLE mdl_abessi_ai_analysis_cache");
        return [
            'success' => true,
            'message' => '캐시 테이블이 초기화되었습니다.'
        ];
    } catch (Exception $e) {
        error_log('Cache table reset error: ' . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>