<?php
/**
 * File: generate_detailed_thinking.php
 * Purpose: '자세히 생각하기' 섹션과 추가 질문 3개를 AI로 생성
 * Location: /mnt/c/1 Project/augmented_teacher/books/generate_detailed_thinking.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // POST 데이터 받기
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    error_log(sprintf(
        '[generate_detailed_thinking.php] File: %s, Line: %d, Request received',
        basename(__FILE__),
        __LINE__
    ));

    if (!isset($data['context'])) {
        throw new Exception('컨텍스트가 누락되었습니다.');
    }

    $context = $data['context'];
    $subtitle = isset($data['subtitle']) ? $data['subtitle'] : '';
    $contentsid = isset($data['contentsid']) ? $data['contentsid'] : '';
    $contentstype = isset($data['contentstype']) ? $data['contentstype'] : '';
    $nstep = isset($data['nstep']) ? intval($data['nstep']) : 0;

    // OpenAI API 키
    $secret_key = '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';

    // 프롬프트 구성 (1단계: 자세히 생각하기만 생성)
    $prompt = "전체 대본 내용:\n{$context}\n\n";

    if (!empty($subtitle)) {
        $prompt .= "현재 구간 내용:\n{$subtitle}\n\n";
    }

    $prompt .= "위 내용을 분석하여 플로우차트 생성:\n\n";
    $prompt .= "**필수:**\n";
    $prompt .= "1. 내용에서 핵심 수학 개념 추출\n";
    $prompt .= "2. 학습자가 수행할 구체적 행동으로 변환\n";
    $prompt .= "3. 플로우차트 형식 엄수:\n";
    $prompt .= "   ∴ [목표]\n";
    $prompt .= "   ├─ 전제조건 ∵ [이유]\n";
    $prompt .= "   │  ├─ [행동] → [도구]\n";
    $prompt .= "   │  └─ [행동]\n";
    $prompt .= "   ├─ 단계1 → [작업]\n";
    $prompt .= "   │  └─ 단계2 → [작업]\n";
    $prompt .= "   └─ 최종 ∴ [검증]\n\n";
    $prompt .= "**중요:** 실제 내용의 수식, 개념, 절차를 반영해야 함";

    // OpenAI API 호출
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Authorization: Bearer ' . $secret_key
    ));

    $requestData = array(
        'model' => 'gpt-4o-mini',
        'messages' => array(
            array(
                'role' => 'system',
                'content' => '수학적 사고 플로우차트 생성 전문가.

**필수 출력 형식:**
∴ [학습 목표]
├─ 전제조건 ∵ [필요사항]
│  ├─ [행동1] → [방법]
│  └─ [행동2]
├─ 단계1 → [작업]
│  └─ 단계2 → [작업]
└─ 최종 ∴ [확인사항]

**작성 원칙:**
1. 현재 내용의 수학 개념을 분석
2. 학생이 실제 수행할 행동 중심
3. 수식·기호 최대 활용
4. 각 단계는 구체적 행동 지시

**예시:**
∴ 이차함수 극값 찾기
├─ 전제조건 ∵ 미분 가능
│  ├─ \\(f(x) = ax^2 + bx + c\\) → 식 확인
│  └─ 도함수 공식 준비
├─ 단계1 → \\(f\'(x) = 2ax + b\\) 계산
│  └─ 단계2 → \\(f\'(x) = 0\\) 풀기
└─ 최종 ∴ \\(x = -\\frac{b}{2a}\\) 검증'
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        ),
        'max_tokens' => 800,
        'temperature' => 0.7
    );

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));

    error_log(sprintf(
        '[generate_detailed_thinking.php] File: %s, Line: %d, Calling OpenAI API',
        basename(__FILE__),
        __LINE__
    ));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception('API 호출 실패: ' . $error);
    }

    curl_close($ch);

    error_log(sprintf(
        '[generate_detailed_thinking.php] File: %s, Line: %d, API Response Code: %d',
        basename(__FILE__),
        __LINE__,
        $httpCode
    ));

    if ($httpCode !== 200) {
        throw new Exception('API 응답 오류: HTTP ' . $httpCode);
    }

    $result = json_decode($response, true);

    if (!isset($result['choices'][0]['message']['content'])) {
        error_log(sprintf(
            '[generate_detailed_thinking.php] File: %s, Line: %d, Invalid API response: %s',
            basename(__FILE__),
            __LINE__,
            substr($response, 0, 500)
        ));
        throw new Exception('유효하지 않은 API 응답');
    }

    // 1단계: 자세히 생각하기만 추출
    $thinking = trim($result['choices'][0]['message']['content']);

    // DB에 저장 (mdl_abessi_tailoredcontents) - 1단계: qstn0만
    if (!empty($contentsid) && !empty($contentstype)) {
        try {
            // 기존 레코드 확인
            $existingRecord = $DB->get_record('abessi_tailoredcontents', array(
                'contentsid' => $contentsid,
                'contentstype' => $contentstype,
                'nstep' => $nstep
            ));

            $record = new stdClass();
            $record->contentsid = $contentsid;
            $record->contentstype = $contentstype;
            $record->nstep = $nstep;
            $record->qstn0 = $thinking; // 1단계: 자세히 생각하기만 저장
            $record->ans0 = $thinking; // ans0 = qstn0와 동일
            $record->timemodified = time();

            if ($existingRecord) {
                // 업데이트
                $record->id = $existingRecord->id;
                $record->timecreated = $existingRecord->timecreated;
                $DB->update_record('abessi_tailoredcontents', $record);

                error_log(sprintf(
                    '[generate_detailed_thinking.php] File: %s, Line: %d, Updated record id=%d (thinking only)',
                    basename(__FILE__),
                    __LINE__,
                    $record->id
                ));
            } else {
                // 신규 삽입
                $record->timecreated = time();
                $recordId = $DB->insert_record('abessi_tailoredcontents', $record);

                error_log(sprintf(
                    '[generate_detailed_thinking.php] File: %s, Line: %d, Inserted record id=%d (thinking only)',
                    basename(__FILE__),
                    __LINE__,
                    $recordId
                ));
            }
        } catch (Exception $e) {
            // DB 저장 실패해도 응답은 반환
            error_log(sprintf(
                '[generate_detailed_thinking.php] File: %s, Line: %d, DB save failed: %s',
                basename(__FILE__),
                __LINE__,
                $e->getMessage()
            ));
        }
    }

    // 성공 응답 (1단계: thinking만)
    echo json_encode(array(
        'success' => true,
        'thinking' => $thinking
    ), JSON_UNESCAPED_UNICODE);

    error_log(sprintf(
        '[generate_detailed_thinking.php] File: %s, Line: %d, Generated successfully',
        basename(__FILE__),
        __LINE__
    ));

} catch (Exception $e) {
    error_log(sprintf(
        '[generate_detailed_thinking.php] File: %s, Line: %d, Error: %s',
        basename(__FILE__),
        __LINE__,
        $e->getMessage()
    ));

    // 에러 응답
    http_response_code(500);
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
