<?php
/**
 * 발표 텍스트 기반 페르소나(60개) 취약점 분석 API
 *
 * @package    local_augmented_teacher
 * @subpackage AItutor
 * @version    1.0
 */

ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

ob_clean();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 60개 페르소나(최소 정보: id/name/desc/category) - 모델 선택용
 * NOTE: 유지보수 편의를 위해, 필요 최소 정보만 포함
 */
function get_personas_60_min() {
    return [
        ['id'=>1,'name'=>'아이디어 해방 자동발화형','category'=>'인지 과부하'],
        ['id'=>2,'name'=>'3초 패배 예감형','category'=>'자신감 왜곡'],
        ['id'=>3,'name'=>'과신-시야 협착형','category'=>'자신감 왜곡'],
        ['id'=>4,'name'=>'무의식 연쇄 실수형','category'=>'실수 패턴'],
        ['id'=>5,'name'=>'모순 확신-답불가형','category'=>'자신감 왜곡'],
        ['id'=>6,'name'=>'작업기억 ⅔ 할당형','category'=>'인지 과부하'],
        ['id'=>7,'name'=>'반(半)포기 창의 탐색형','category'=>'접근 전략 오류'],
        ['id'=>8,'name'=>'해설지-혼합 착각형','category'=>'학습 습관'],
        ['id'=>9,'name'=>'연습 회피 관성형','category'=>'학습 습관'],
        ['id'=>10,'name'=>'불확실 강행형','category'=>'접근 전략 오류'],
        ['id'=>11,'name'=>'속도 압박 억제형','category'=>'시간/압박 관리'],
        ['id'=>12,'name'=>'시험 트라우마 악수형','category'=>'시간/압박 관리'],
        ['id'=>13,'name'=>'징검다리 난도적형','category'=>'접근 전략 오류'],
        ['id'=>14,'name'=>'무의식 재현 루프형','category'=>'학습 습관'],
        ['id'=>15,'name'=>'조건 회피-추론 생략형','category'=>'검증/확인 부재'],
        ['id'=>16,'name'=>'확률적 답안 던지기형','category'=>'접근 전략 오류'],
        ['id'=>17,'name'=>'방심 단기 기억 증발형','category'=>'기타 장애'],
        ['id'=>18,'name'=>'도구 의존 과적형','category'=>'기타 장애'],
        ['id'=>19,'name'=>'과거 방식 고착형','category'=>'학습 습관'],
        ['id'=>20,'name'=>'불완전 개념 종결형','category'=>'검증/확인 부재'],
        ['id'=>21,'name'=>'피로-오답 포용형','category'=>'기타 장애'],
        ['id'=>22,'name'=>'감정 전염 스트레스형','category'=>'기타 장애'],
        ['id'=>23,'name'=>'과다 정보 섭취형','category'=>'인지 과부하'],
        ['id'=>24,'name'=>'이론-연산 전도형','category'=>'접근 전략 오류'],
        ['id'=>25,'name'=>'단일 예시 착시형','category'=>'학습 습관'],
        ['id'=>26,'name'=>'시간 왜곡 긴장형','category'=>'시간/압박 관리'],
        ['id'=>27,'name'=>'보상 심리 도박형','category'=>'기타 장애'],
        ['id'=>28,'name'=>'공간-시각 혼선형','category'=>'실수 패턴'],
        ['id'=>29,'name'=>'자기긍정 과열형','category'=>'자신감 왜곡'],
        ['id'=>30,'name'=>'메타인지 고갈형','category'=>'기타 장애'],
        ['id'=>31,'name'=>'개념-용어 혼동형','category'=>'검증/확인 부재'],
        ['id'=>32,'name'=>'역추적 단절형','category'=>'접근 전략 오류'],
        ['id'=>33,'name'=>'사다리 건너뛰기형','category'=>'접근 전략 오류'],
        ['id'=>34,'name'=>'조건 재정렬 미흡형','category'=>'검증/확인 부재'],
        ['id'=>35,'name'=>'공식 암기 과신형','category'=>'학습 습관'],
        ['id'=>36,'name'=>'근사치 타협형','category'=>'검증/확인 부재'],
        ['id'=>37,'name'=>'개념-문제 불일치 간과형','category'=>'접근 전략 오류'],
        ['id'=>38,'name'=>'단위 무시형','category'=>'실수 패턴'],
        ['id'=>39,'name'=>'시각화 회피형','category'=>'실수 패턴'],
        ['id'=>40,'name'=>'메모 불능 기억 과신형','category'=>'기타 장애'],
        ['id'=>41,'name'=>'지식-실행 단절형','category'=>'학습 습관'],
        ['id'=>42,'name'=>'노이즈 필터 실패형','category'=>'인지 과부하'],
        ['id'=>43,'name'=>'인터럽트 리셋 불능형','category'=>'기타 장애'],
        ['id'=>44,'name'=>'감정 보상 과다형','category'=>'기타 장애'],
        ['id'=>45,'name'=>'휴식 부족 저하형','category'=>'기타 장애'],
        ['id'=>46,'name'=>'전환 비용 과소평가형','category'=>'시간/압박 관리'],
        ['id'=>47,'name'=>'반례 무시형','category'=>'검증/확인 부재'],
        ['id'=>48,'name'=>'관성적 읽기 스킵형','category'=>'실수 패턴'],
        ['id'=>49,'name'=>'조건 재해석 과잉형','category'=>'검증/확인 부재'],
        ['id'=>50,'name'=>'단계 통합 과속형','category'=>'실수 패턴'],
        ['id'=>51,'name'=>'중간점검 생략형','category'=>'검증/확인 부재'],
        ['id'=>52,'name'=>'검산 회피형','category'=>'검증/확인 부재'],
        ['id'=>53,'name'=>'계산 체계 혼합형','category'=>'실수 패턴'],
        ['id'=>54,'name'=>'음운 혼동형','category'=>'실수 패턴'],
        ['id'=>55,'name'=>'참조 프레임 불일치형','category'=>'실수 패턴'],
        ['id'=>56,'name'=>'전략 중복 추적 피로형','category'=>'인지 과부하'],
        ['id'=>57,'name'=>'목표-행동 단절형','category'=>'학습 습관'],
        ['id'=>58,'name'=>'피드백 과민형','category'=>'기타 장애'],
        ['id'=>59,'name'=>'다중 문제 스위칭 과부하형','category'=>'시간/압박 관리'],
        ['id'=>60,'name'=>'자기평가 누적 오류형','category'=>'기타 장애'],
    ];
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Invalid JSON input - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $text = trim((string)($input['presentation_text'] ?? ''));
    if ($text === '') {
        throw new Exception('presentation_text가 필요합니다 - ' . basename(__FILE__) . ':' . __LINE__, 400);
    }

    $personas = get_personas_60_min();
    $personaListText = implode("\n", array_map(function($p) {
        return "{$p['id']}. {$p['name']} ({$p['category']})";
    }, $personas));

    // OpenAI API 키 로드
    $apiKey = null;
    $configPath = __DIR__ . '/../../config.php';
    if (file_exists($configPath)) {
        require_once($configPath);
        if (defined('OPENAI_API_KEY')) {
            $apiKey = OPENAI_API_KEY;
        }
    }
    if (!$apiKey) {
        $apiKey = get_config('local_augmented_teacher', 'openai_api_key');
    }
    if (!$apiKey) {
        throw new Exception('OpenAI API 키가 설정되지 않았습니다 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    $systemPrompt = <<<PROMPT
당신은 수학 학습 코칭 전문가입니다.

학생의 "발표 텍스트"를 읽고, 아래 60개 인지 페르소나 중에서 학생의 취약 패턴에 가장 가까운 페르소나를 최대 3개 선정하세요.

## 60개 페르소나 목록(선택 가능 범위)
{$personaListText}

## 출력 형식(JSON only)
{
  "summary": "분석 요약(한국어, 80자 이내)",
  "weak_personas": [
    {"id": 15, "reason": "왜 취약한지(한국어, 80자 이내)", "confidence": 0.0}
  ],
  "coach_message": "학생에게 보여줄 한 줄 안내(한국어, 60자 이내)",
  "suggested_training": [
    "바로 적용할 연습 1 (한국어, 60자 이내)",
    "바로 적용할 연습 2 (한국어, 60자 이내)"
  ]
}

## 규칙
- weak_personas는 1~3개
- confidence는 0.0~1.0
- id는 반드시 위 목록에 있는 정수만
PROMPT;

    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => "발표 텍스트:\n\n" . $text]
    ];

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    $postData = [
        'model' => 'gpt-4o',
        'messages' => $messages,
        'temperature' => 0.2,
        'max_tokens' => 1200,
        'response_format' => ['type' => 'json_object']
    ];

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($postData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ],
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($curlError)) {
        throw new Exception('OpenAI API 호출 실패: ' . $curlError . ' - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
        throw new Exception('OpenAI API 오류: ' . $errorMessage . ' - ' . basename(__FILE__) . ':' . __LINE__, $httpCode);
    }

    $data = json_decode($response, true);
    $content = $data['choices'][0]['message']['content'] ?? null;
    if (!$content) {
        throw new Exception('OpenAI 응답 형식 오류 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    $analysis = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('분석 결과 파싱 실패 - ' . basename(__FILE__) . ':' . __LINE__, 500);
    }

    // 정규화: persona name 보강
    $byId = [];
    foreach ($personas as $p) $byId[(int)$p['id']] = $p;
    if (isset($analysis['weak_personas']) && is_array($analysis['weak_personas'])) {
        foreach ($analysis['weak_personas'] as &$wp) {
            $pid = isset($wp['id']) ? (int)$wp['id'] : 0;
            if ($pid && isset($byId[$pid])) {
                $wp['name'] = $byId[$pid]['name'];
                $wp['category'] = $byId[$pid]['category'];
            }
        }
        unset($wp);
    }

    echo json_encode([
        'success' => true,
        'data' => $analysis,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_clean();
    error_log("Analyze Presentation Error in " . __FILE__ . ":" . __LINE__ . " - " . $e->getMessage());

    $code = $e->getCode() ?: 500;
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ], JSON_UNESCAPED_UNICODE);
}

ob_end_flush();


