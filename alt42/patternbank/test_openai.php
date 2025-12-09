<?php
/**
 * OpenAI API 연결 테스트 스크립트
 * PatternBank 유사문제 생성 기능 테스트
 */

// Moodle 설정 로드
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// OpenAI 설정 파일 로드
require_once(__DIR__ . '/config/openai_config.php');

// CLI 모드 체크
$isCLI = (php_sapi_name() === 'cli');

if (!$isCLI) {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "=== PatternBank OpenAI API 테스트 ===\n\n";

// 1. API 키 확인
echo "1. API 키 확인...\n";
if (PATTERNBANK_OPENAI_API_KEY === 'your_api_key_here' || empty(PATTERNBANK_OPENAI_API_KEY)) {
    echo "   ❌ API 키가 설정되지 않았습니다.\n";
    echo "   config/openai_config.php 파일에서 PATTERNBANK_OPENAI_API_KEY를 설정하세요.\n";
    exit(1);
} else {
    echo "   ✅ API 키 설정됨: " . substr(PATTERNBANK_OPENAI_API_KEY, 0, 10) . "...\n";
}

// 2. 연결 테스트
echo "\n2. OpenAI API 연결 테스트...\n";
$testResult = testPatternBankOpenAI();

if ($testResult['success']) {
    echo "   ✅ 연결 성공!\n";
    echo "   - 모델: " . $testResult['model'] . "\n";
    if (isset($testResult['usage'])) {
        echo "   - 토큰 사용: " . json_encode($testResult['usage']) . "\n";
    }
    echo "   - 응답: " . substr($testResult['response'], 0, 100) . "...\n";
} else {
    echo "   ❌ 연결 실패: " . $testResult['error'] . "\n";
    exit(1);
}

// 3. 유사문제 생성 테스트
echo "\n3. 유사문제 생성 테스트...\n";

$testProblem = [
    'question' => '다음 수열의 일반항을 구하시오: 3, 6, 12, 24, ...',
    'solution' => '첫째항이 3이고 공비가 2인 등비수열입니다. 따라서 일반항은 $a_n = 3 \\cdot 2^{n-1}$입니다.'
];

echo "   원본 문제:\n";
echo "   - 문제: " . $testProblem['question'] . "\n";
echo "   - 해설: " . $testProblem['solution'] . "\n\n";

echo "   유사문제 생성 중... (최대 30초 소요)\n";

$startTime = microtime(true);
$result = generateSimilarProblems($testProblem, 'similar');
$endTime = microtime(true);
$elapsed = round($endTime - $startTime, 2);

echo "   생성 시간: {$elapsed}초\n\n";

if ($result['success']) {
    echo "   ✅ 생성 성공! " . count($result['problems']) . "개 문제 생성됨\n\n";
    
    foreach ($result['problems'] as $index => $problem) {
        echo "   === 문제 " . ($index + 1) . " ===\n";
        echo "   문제: " . $problem['question'] . "\n";
        
        if (!empty($problem['choices'])) {
            echo "   선택지:\n";
            foreach ($problem['choices'] as $choice) {
                echo "      " . $choice . "\n";
            }
        }
        
        echo "   해설: " . substr($problem['solution'], 0, 200);
        if (strlen($problem['solution']) > 200) {
            echo "...";
        }
        echo "\n\n";
    }
    
    if (isset($result['usage'])) {
        echo "   토큰 사용량: " . json_encode($result['usage']) . "\n";
    }
} else {
    echo "   ❌ 생성 실패: " . $result['error'] . "\n";
}

// 4. DB 저장 테스트 (선택적)
if (!$isCLI && isset($_GET['save']) && $_GET['save'] === 'true') {
    echo "\n4. DB 저장 테스트...\n";
    
    if ($result['success'] && count($result['problems']) > 0) {
        $problem = $result['problems'][0];
        
        $record = new stdClass();
        $record->authorid = 2; // Admin user
        $record->cntid = 999; // 테스트 ID
        $record->cnttype = 1; // 테스트 타입
        $record->question = $problem['question'];
        $record->solution = $problem['solution'];
        $record->inputanswer = json_encode($problem['choices'] ?? [], JSON_UNESCAPED_UNICODE);
        $record->type = 'similar';
        $record->timecreated = time();
        $record->timemodified = time();
        
        // NULL 필드들
        $record->qstnimgurl = null;
        $record->solimgurl = null;
        $record->fullqstnimgurl = null;
        $record->fullsolimgurl = null;
        
        try {
            $id = $DB->insert_record('abessi_patternbank', $record);
            echo "   ✅ DB 저장 성공! ID: $id\n";
        } catch (Exception $e) {
            echo "   ❌ DB 저장 실패: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== 테스트 완료 ===\n";

if (!$isCLI) {
    echo "\n\n";
    echo "DB 저장을 테스트하려면 URL에 ?save=true 를 추가하세요.\n";
    echo "예: test_openai.php?save=true\n";
}
?>