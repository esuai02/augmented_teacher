<?php
/**
 * Test script for procedural memory narration generation
 * Tests the updated prompts and functionality
 */

// Load Moodle configuration
require_once(dirname(__FILE__) . '/../config.php');

// Enable error reporting for testing
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include OpenAI configuration
require_once('openaiconfig.php');

// Test the procedural memory prompt
function testProceduralMemoryPrompt() {
    // Sample math content for testing
    $test_content = "이차방정식 $x^2 - 5x + 6 = 0$을 인수분해하여 해를 구하기";

    // The new procedural memory prompt (first part)
    $systemPrompt = "절차기억 형성활동을 시작합니다.

당신은 수학 학습에서 절차기억(procedural memory) 형성을 위한 전문 교수자입니다.
학생의 문제 해결 과정을 관찰하고 따라가는 견습형 학습(observational apprenticeship)을 통해
자연스럽게 절차가 머릿속에 각인되도록 안내합니다.";

    $userPrompt = "다음 수학 콘텐츠를 절차기억 형성 방식으로 설명해주세요:\n\n내용:\n$test_content";

    echo "=== 절차기억 나레이션 테스트 ===\n\n";
    echo "테스트 콘텐츠: $test_content\n\n";
    echo "시스템 프롬프트 (첫 부분):\n";
    echo substr($systemPrompt, 0, 200) . "...\n\n";

    // Test OpenAI API connection
    echo "OpenAI API 연결 테스트...\n";

    try {
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ],
            'temperature' => 0.7,
            'max_tokens' => 500  // Reduced for testing
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo "❌ API 응답 오류 (HTTP $httpCode)\n";
            echo "응답: $response\n";
            return false;
        }

        $result = json_decode($response, true);

        if (isset($result['choices'][0]['message']['content'])) {
            echo "✅ 절차기억 나레이션 생성 성공!\n\n";
            echo "생성된 나레이션 (일부):\n";
            echo "=====================================\n";
            $narration = $result['choices'][0]['message']['content'];
            echo substr($narration, 0, 500) . "...\n";
            echo "=====================================\n\n";

            // Check if the narration contains procedural memory keywords
            $pm_keywords = ['절차기억', '관찰', '패턴', '구조', '따라'];
            $found_keywords = [];
            foreach ($pm_keywords as $keyword) {
                if (strpos($narration, $keyword) !== false) {
                    $found_keywords[] = $keyword;
                }
            }

            if (!empty($found_keywords)) {
                echo "✅ 절차기억 관련 키워드 발견: " . implode(', ', $found_keywords) . "\n";
            } else {
                echo "⚠️ 절차기억 관련 키워드가 나레이션에서 발견되지 않았습니다.\n";
            }

            return true;
        } else {
            echo "❌ 응답 처리 오류\n";
            print_r($result);
            return false;
        }

    } catch (Exception $e) {
        echo "❌ 오류 발생: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the test
echo "<pre>";
$test_result = testProceduralMemoryPrompt();
echo "\n=== 테스트 완료 ===\n";
echo $test_result ? "✅ 모든 테스트 통과\n" : "❌ 테스트 실패\n";
echo "</pre>";
?>