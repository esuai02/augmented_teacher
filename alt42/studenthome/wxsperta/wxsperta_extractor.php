<?php
/**
 * WXSPERTA 8-layer extractor (LLM 기반)
 * - 입력 대화(학생/AI)에서 8층 레이어(worldView~abstraction) 자동 추출
 */
require_once(__DIR__ . "/config.php");

function wxsperta_extract_layers_from_turn($user_message, $ai_message) {
    $prompt = build_wxsperta_extraction_prompt($user_message, $ai_message);

    $raw = call_openai_api([
        ['role' => 'system', 'content' => '너는 대화에서 WXSPERTA 8층 구조를 JSON으로 추출하는 분석가야. 반드시 JSON만 반환해.'],
        ['role' => 'user', 'content' => $prompt],
    ], 0.3);

    if ($raw === false) {
        return [false, "OpenAI 호출 실패 - " . __FILE__ . ":" . __LINE__];
    }

    $layers = try_parse_json_object($raw);
    if (!is_array($layers)) {
        return [false, "JSON 파싱 실패 - " . __FILE__ . ":" . __LINE__];
    }

    $allowed = ['worldView','context','structure','process','execution','reflection','transfer','abstraction'];
    $out = [];
    foreach ($allowed as $k) {
        $v = $layers[$k] ?? null;
        if ($v === null) continue;
        $v = trim((string)$v);
        if ($v === '' || strtolower($v) === 'null') continue;
        $out[$k] = $v;
    }

    return [$out, null];
}

function build_wxsperta_extraction_prompt($user_message, $ai_message) {
    return <<<TXT
다음 대화를 WXSPERTA 8층 구조로 요약해줘. 반드시 JSON만 출력해.

대화:
- 학생: {$user_message}
- AI: {$ai_message}

출력 JSON 스키마:
{
  "worldView": "기본 철학/가치관/신념(없으면 null)",
  "context": "현재 상황/환경/조건(없으면 null)",
  "structure": "프레임/구조/틀(없으면 null)",
  "process": "단계/절차/방법(없으면 null)",
  "execution": "바로 할 수 있는 행동/실행(없으면 null)",
  "reflection": "성찰/평가/깨달음(없으면 null)",
  "transfer": "다른 상황에 적용/확장(없으면 null)",
  "abstraction": "핵심 원리/본질(없으면 null)"
}
TXT;
}

function try_parse_json_object($text) {
    $text = trim((string)$text);
    // 응답에 앞뒤 설명이 섞였을 때 첫 JSON 객체만 잡기
    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start === false || $end === false || $end <= $start) return null;
    $candidate = substr($text, $start, $end - $start + 1);
    $decoded = json_decode($candidate, true);
    if (json_last_error() !== JSON_ERROR_NONE) return null;
    return $decoded;
}


