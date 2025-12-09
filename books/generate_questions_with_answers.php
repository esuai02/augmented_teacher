<?php
/**
 * 질문과 답변을 동시에 생성하는 API
 *
 * @author AI Learning System
 * @created 2025-01-26
 * @file books/generate_questions_with_answers.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // 입력 데이터 받기
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        throw new Exception('입력 데이터 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    $nodeContent = $input['nodeContent'] ?? '';
    $nodeType = $input['nodeType'] ?? 'step';
    $fullContext = $input['fullContext'] ?? '';
    $contentsid = intval($input['contentsid'] ?? 0);
    $contentstype = intval($input['contentstype'] ?? 0);
    $nstep = intval($input['nstep'] ?? 1);
    $totalSteps = intval($input['totalSteps'] ?? 0);
    $nodeIndex = intval($input['nodeIndex'] ?? 0);
    $forceRegenerate = !empty($input['forceRegenerate']);

    if (empty($nodeContent)) {
        throw new Exception('노드 내용 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    error_log(sprintf(
        '[generate_questions_with_answers.php] File: %s, Line: %d, Generating Q&A for step %d/%d%s',
        basename(__FILE__),
        __LINE__,
        $nstep,
        $totalSteps,
        $forceRegenerate ? ' (force regenerate)' : ''
    ));

    // OpenAI API 키
    $secret_key = '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';

    // 컨텐츠 타입별 원본 텍스트 로드
    $originalContent = '';
    try {
        if ($contentstype == 1) {
            // mdl_icontent_pages에서 maintext 가져오기
            $page = $DB->get_record('icontent_pages', array('id' => $contentsid), 'maintext');
            $originalContent = $page ? $page->maintext : '';
            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Loaded maintext from icontent_pages (length: %d)',
                basename(__FILE__),
                __LINE__,
                strlen($originalContent)
            ));
        } elseif ($contentstype == 2) {
            // mdl_question에서 mathexpression 가져오기
            $question = $DB->get_record('question', array('id' => $contentsid), 'mathexpression');
            $originalContent = $question ? $question->mathexpression : '';
            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Loaded mathexpression from question (length: %d)',
                basename(__FILE__),
                __LINE__,
                strlen($originalContent)
            ));
        }

        if (empty($originalContent)) {
            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Warning: No original content found for contentsid=%d, contentstype=%d',
                basename(__FILE__),
                __LINE__,
                $contentsid,
                $contentstype
            ));
        }
    } catch (Exception $e) {
        error_log(sprintf(
            '[generate_questions_with_answers.php] File: %s, Line: %d, Error loading original content: %s',
            basename(__FILE__),
            __LINE__,
            $e->getMessage()
        ));
    }

    // 노드 타입별 질문 유형 결정
    $questionTypes = [
        'premise' => '전제조건이나 개념에 대한 이해를 확인하는 질문',
        'step' => '계산 과정이나 적용 방법에 대한 실행 질문',
        'conclusion' => '결과 검증이나 확장 적용에 대한 질문'
    ];

    $questionGuide = $questionTypes[$nodeType] ?? '일반적인 이해 질문';

    // 현재 단계의 위치 파악
    $stepPosition = '';
    if ($totalSteps > 0) {
        $percentage = round(($nstep / $totalSteps) * 100);
        if ($nstep == 1) {
            $stepPosition = "문제 풀이의 시작 단계 (전체 {$totalSteps}단계 중 1단계)";
        } elseif ($nstep == $totalSteps) {
            $stepPosition = "문제 풀이의 마지막 단계 (전체 {$totalSteps}단계 중 {$nstep}단계)";
        } else {
            $stepPosition = "문제 풀이의 중간 단계 (전체 {$totalSteps}단계 중 {$nstep}단계, 약 {$percentage}% 진행)";
        }
    }

    $systemPrompt = <<<PROMPT
당신은 수학 교육 전문가입니다. **원본 학습 컨텐츠 전체**와 **전체 문제 풀이 과정의 맥락 속에서** 현재 단계에서 학생들이 자연스럽게 가질 수 있는 **의미있는 질문**과 그에 대한 답변을 생성하세요.

**질문 생성 원칙:**
1. **원본 컨텐츠 활용**: HTML/LaTeX로 작성된 원본 교재의 그래프, 도형, 개념 설명 등 시각적 요소와 텍스트 맥락 고려
2. **전체 흐름 속 위치 고려**: 이 단계가 전체 풀이에서 어떤 역할을 하는지 파악
3. **이전 단계와의 연결**: 왜 이전 단계에서 이 단계로 넘어왔는가?
4. **핵심 개념 이해**: 이 단계에서 사용되는 수학적 개념이나 공식의 의미
5. **실제 학생 질문 형태**: "왜 ~하나요?", "~와 ~의 차이는?", "다른 방법은?"
6. **학습 목표와 연결**: 이 단계를 통해 배워야 할 수학적 사고력

**답변 작성 원칙:**
1. **쉬운 설명**: 수식과 기호, 화살표 등을 통해서 생각의 순서와 내용을 유기적으로 표현하여 쉽게 설명하세요.
2. **원본 컨텐츠 참조**: 예시나 일반론이 아니라 원본 컨텐츠 내용과의 직접적 연관성을 토대로 설명하세요.
3. **수학 표기**: LaTeX 사용 - **반드시 $...$로 감싸기**
   - 인라인 수식: $x^2 + 1$, $\\frac{a}{b}$, $\\sum_{i=1}^{n}$
   - 디스플레이 수식: $$\\int_a^b f(x)dx$$
   - **중요**: \\(...\\) 형식이 아닌 $...$ 형식 사용
4. **구체적 계산**: 필요시 단계별 계산 과정을 LaTeX로 표현
5. **전체 맥락 연결**: 이 단계가 최종 답에 어떻게 기여하는지 설명
6. **길이**: 3~5문장 (150~250자)

**출력 형식 (JSON):**
{
    "qa_pairs": [
        {
            "question": "질문 1",
            "answer": "답변 1 (LaTeX 포함)"
        },
        {
            "question": "질문 2",
            "answer": "답변 2 (LaTeX 포함)"
        },
        {
            "question": "질문 3",
            "answer": "답변 3 (LaTeX 포함)"
        }
    ]
}

**중요:** 반드시 JSON 형식만 출력하고, 다른 설명은 추가하지 마세요.
PROMPT;

    // 원본 컨텐츠가 있으면 프롬프트에 추가
    $originalContentSection = '';
    if (!empty($originalContent)) {
        $originalContentSection = <<<SECTION

**원본 컨텐츠 전체**:
{$originalContent}

SECTION;
    }

    $userPrompt = <<<PROMPT
{$originalContentSection}
**전체 문제 풀이 과정:**
{$fullContext}

**현재 분석 대상 단계:**
- 위치: {$stepPosition}
- 내용:
{$nodeContent}

**요청:**
위 전체 맥락을 고려하여, 현재 단계({$nstep}단계)에서 학생들이 가질 수 있는 **의미있는 질문** 2~3개와 각각의 **수학적으로 정확한 답변**을 생성해주세요.

질문은 원본 컨텐츠의 내용  중 현재 단계에 집중한 흔히 있을 법한 학생들의 질문들로 구성해주세요
PROMPT;

    $temperature = $forceRegenerate ? 0.6 : 0.0;

    $apiData = [
        'model' => 'gpt-4o',
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $userPrompt]
        ],
        'temperature' => $temperature,
        'max_tokens' => 2000
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($apiData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret_key
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("OpenAI API 오류 (HTTP {$httpCode}) - File: " . basename(__FILE__) . ", Line: " . __LINE__);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        throw new Exception('API 응답 형식 오류 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    $aiResponse = $result['choices'][0]['message']['content'];

    // 전체 AI 응답 로깅 (디버깅용)
    error_log(sprintf(
        '[generate_questions_with_answers.php] File: %s, Line: %d, Full AI Response: %s',
        basename(__FILE__),
        __LINE__,
        $aiResponse
    ));

    // JSON 추출 전처리
    // 1. 코드 블록 제거
    $cleanedResponse = preg_replace('/```json\s*|\s*```/i', '', $aiResponse);

    // 2. 앞뒤 공백 제거
    $cleanedResponse = trim($cleanedResponse);

    // 3. JSON 시작/끝 찾기 (가장 바깥쪽 중괄호)
    $jsonStart = strpos($cleanedResponse, '{');
    $jsonEnd = strrpos($cleanedResponse, '}');

    if ($jsonStart === false || $jsonEnd === false) {
        error_log(sprintf(
            '[generate_questions_with_answers.php] File: %s, Line: %d, No JSON brackets found in response',
            basename(__FILE__),
            __LINE__
        ));
        throw new Exception('JSON 형식 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    $jsonString = substr($cleanedResponse, $jsonStart, $jsonEnd - $jsonStart + 1);

    error_log(sprintf(
        '[generate_questions_with_answers.php] File: %s, Line: %d, Extracted JSON: %s',
        basename(__FILE__),
        __LINE__,
        $jsonString
    ));

    $qaData = json_decode($jsonString, true);

    if (!$qaData) {
        error_log(sprintf(
            '[generate_questions_with_answers.php] File: %s, Line: %d, JSON decode error: %s, Attempting regex fallback',
            basename(__FILE__),
            __LINE__,
            json_last_error_msg()
        ));

        // Fallback: 정규식으로 직접 질문/답변 추출
        preg_match_all('/"question"\s*:\s*"([^"]+(?:\\.[^"]*)?)"/s', $aiResponse, $questionsMatches);
        preg_match_all('/"answer"\s*:\s*"([^"]+(?:\\.[^"]*)?)"/s', $aiResponse, $answersMatches);

        if (!empty($questionsMatches[1])) {
            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Regex fallback successful, found %d Q&A pairs',
                basename(__FILE__),
                __LINE__,
                count($questionsMatches[1])
            ));

            $qaPairs = [];
            for ($i = 0; $i < count($questionsMatches[1]); $i++) {
                $qaPairs[] = [
                    'question' => str_replace('\"', '"', $questionsMatches[1][$i]),
                    'answer' => isset($answersMatches[1][$i]) ? str_replace('\"', '"', $answersMatches[1][$i]) : '답변을 생성할 수 없습니다.'
                ];
            }
        } else {
            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Regex fallback failed, AI Response: %s',
                basename(__FILE__),
                __LINE__,
                $aiResponse
            ));
            throw new Exception('Q&A 파싱 실패 (JSON 형식 오류: ' . json_last_error_msg() . ') - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
        }
    } else {
        // qa_pairs 형식 확인 및 변환
        $qaPairs = [];

        if (isset($qaData['qa_pairs']) && is_array($qaData['qa_pairs'])) {
            // 정상 형식
            $qaPairs = $qaData['qa_pairs'];
        } elseif (isset($qaData['questions']) && is_array($qaData['questions'])) {
            // questions만 있는 경우 - 각 질문에 대해 간단한 답변 추가
            foreach ($qaData['questions'] as $index => $question) {
                $qaPairs[] = [
                    'question' => $question,
                    'answer' => isset($qaData['answers'][$index]) ? $qaData['answers'][$index] : '답변 생성 중...'
                ];
            }
        } else {
            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Invalid format, Response: %s',
                basename(__FILE__),
                __LINE__,
                json_encode($qaData, JSON_UNESCAPED_UNICODE)
            ));
            throw new Exception('Q&A 형식 오류 (qa_pairs 또는 questions 필드 없음) - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
        }
    }

    if (empty($qaPairs)) {
        throw new Exception('Q&A 데이터 없음 - File: ' . basename(__FILE__) . ', Line: ' . __LINE__);
    }

    // 질문과 답변 분리
    $questions = [];
    $answers = [];
    foreach ($qaPairs as $qa) {
        $questions[] = $qa['question'] ?? '';
        $answers[] = $qa['answer'] ?? '';
    }

    error_log(sprintf(
        '[generate_questions_with_answers.php] File: %s, Line: %d, Generated %d Q&A pairs',
        basename(__FILE__),
        __LINE__,
        count($qaPairs)
    ));

    // DB에 저장 (qstn1-3, ans1-3 사용, qstn0은 풀이 단계 전용)
    try {
        $record = $DB->get_record('abessi_tailoredcontents', [
            'contentsid' => $contentsid,
            'contentstype' => $contentstype,
            'nstep' => $nstep
        ]);

        if ($record) {
            // UPDATE - 기존 레코드에 Q&A 추가
            $record->qstn1 = $qaPairs[0]['question'] ?? '';
            $record->ans1 = $qaPairs[0]['answer'] ?? '';
            $record->qstn2 = $qaPairs[1]['question'] ?? '';
            $record->ans2 = $qaPairs[1]['answer'] ?? '';
            $record->qstn3 = $qaPairs[2]['question'] ?? '';
            $record->ans3 = $qaPairs[2]['answer'] ?? '';
            $record->timemodified = time();
            $DB->update_record('abessi_tailoredcontents', $record);

            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Updated DB record id=%d',
                basename(__FILE__),
                __LINE__,
                $record->id
            ));
        } else {
            // INSERT - 새 레코드 생성 (qstn0는 비워둠 - 풀이 단계 전용)
            $record = new stdClass();
            $record->contentsid = $contentsid;
            $record->contentstype = $contentstype;
            $record->nstep = $nstep;
            $record->qstn0 = ''; // 풀이 단계 전용 (비워둠)
            $record->qstn1 = $qaPairs[0]['question'] ?? '';
            $record->ans1 = $qaPairs[0]['answer'] ?? '';
            $record->qstn2 = $qaPairs[1]['question'] ?? '';
            $record->ans2 = $qaPairs[1]['answer'] ?? '';
            $record->qstn3 = $qaPairs[2]['question'] ?? '';
            $record->ans3 = $qaPairs[2]['answer'] ?? '';
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('abessi_tailoredcontents', $record);

            error_log(sprintf(
                '[generate_questions_with_answers.php] File: %s, Line: %d, Inserted new DB record',
                basename(__FILE__),
                __LINE__
            ));
        }
    } catch (Exception $dbError) {
        error_log(sprintf(
            '[generate_questions_with_answers.php] File: %s, Line: %d, DB save error: %s',
            basename(__FILE__),
            __LINE__,
            $dbError->getMessage()
        ));
        // DB 저장 실패해도 Q&A는 반환 (캐시 없이 동작)
    }

    echo json_encode([
        'success' => true,
        'qa_pairs' => $qaPairs,
        'questions' => $questions,
        'answers' => $answers,
        'source' => 'ai'
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    $errorDetails = sprintf(
        'Error: %s (File: %s, Line: %s)',
        $e->getMessage(),
        basename($e->getFile()),
        $e->getLine()
    );

    error_log(sprintf(
        '[generate_questions_with_answers.php] File: %s, Line: %d, %s, Stack: %s',
        basename(__FILE__),
        __LINE__,
        $errorDetails,
        $e->getTraceAsString()
    ));

    echo json_encode([
        'success' => false,
        'error' => $errorDetails
    ], JSON_UNESCAPED_UNICODE);
}
