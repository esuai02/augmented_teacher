<?php
/**
 * test.php
 *
 * Agent15 문제 재정의 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent15ProblemRedefinition
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/persona_system/test.php
 */

// 에러 리포팅
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MOODLE_INTERNAL 정의
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// BasePersonaTest 로드
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

/**
 * Agent15PersonaTest
 *
 * 문제 재정의 에이전트 테스트 클래스
 */
class Agent15PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            15,                         // 에이전트 번호
            'problem_redefinition',     // 에이전트 이름
            '문제 재정의',               // 에이전트 한글명
            __DIR__                     // 기본 경로 (persona_system)
        );
    }

    /**
     * 필수 파일 목록 반환
     *
     * @return array ['상대경로' => '설명', ...]
     */
    protected function getRequiredFiles(): array
    {
        return [
            'engine/PersonaRuleEngine.php' => 'PersonaRuleEngine 메인 클래스',
            'engine/RuleParser.php' => '규칙 파서 클래스',
            'engine/ConditionEvaluator.php' => '조건 평가기 클래스',
            'engine/ActionExecutor.php' => '액션 실행기 클래스',
            'engine/DataContext.php' => '데이터 컨텍스트 클래스',
            'engine/ResponseGenerator.php' => '응답 생성기 클래스',
            'engine/NLUAnalyzer.php' => 'NLU 분석기 클래스',
            'engine/PersonaTransitionManager.php' => '페르소나 전환 관리자',
            'personas.md' => '페르소나 정의 문서',
            'rules.yaml' => '규칙 정의 파일'
        ];
    }

    /**
     * 필수 DB 테이블 목록 반환
     *
     * @return array ['테이블명' => '설명', ...]
     */
    protected function getRequiredTables(): array
    {
        return [
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블',
            'mdl_at_agent_messages' => '에이전트 간 메시지 테이블'
        ];
    }

    /**
     * Agent15 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaRuleEngine 클래스 로드 테스트
        $this->testPersonaRuleEngineLoad();

        // 2. 트리거 시나리오 (S1-S10) 테스트
        $this->testTriggerScenarios();

        // 3. 원인 계층 (CAUSE_LAYERS) 테스트
        $this->testCauseLayers();

        // 4. 컴포넌트 로드 테스트
        $this->testComponentsLoad();

        // 5. NLUAnalyzer 테스트
        $this->testNLUAnalyzer();

        // 6. PersonaTransitionManager 테스트
        $this->testPersonaTransitionManager();

        // 7. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 8. rules.yaml 구조 테스트
        $this->testRulesYaml();
    }

    /**
     * PersonaRuleEngine 클래스 로드 테스트
     */
    private function testPersonaRuleEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaRuleEngine 클래스 로드',
                false,
                '파일 없음: engine/PersonaRuleEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class PersonaRuleEngine') !== false;
            $hasAgentNumber = strpos($content, 'AGENT_NUMBER') !== false ||
                              strpos($content, '15') !== false;

            $this->recordTest(
                'PersonaRuleEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'PersonaRuleEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'Agent15 식별자',
                $hasAgentNumber,
                $hasAgentNumber ? 'AGENT_NUMBER = 15 확인' : 'Agent15 식별자 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'PersonaRuleEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 트리거 시나리오 (S1-S10) 테스트
     */
    private function testTriggerScenarios(): void
    {
        $expectedScenarios = [
            'S1' => '학습 성과 하락 탐지',
            'S2' => '학습이탈 경고 감지',
            'S3' => '동일 오답 반복',
            'S4' => '루틴 불안정',
            'S5' => '시간관리 실패',
            'S6' => '정서/동기 저하',
            'S7' => '개념 이해 부진',
            'S8' => '교사 피드백 경고',
            'S9' => '전략 불일치',
            'S10' => '회복 실패'
        ];

        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            $hasTriggerScenarios = strpos($content, 'TRIGGER_SCENARIOS') !== false;

            $this->recordTest(
                '트리거 시나리오 상수',
                $hasTriggerScenarios,
                $hasTriggerScenarios ? 'TRIGGER_SCENARIOS 정의됨' : 'TRIGGER_SCENARIOS 없음'
            );

            $foundScenarios = 0;
            foreach (array_keys($expectedScenarios) as $scenario) {
                if (strpos($content, "'" . $scenario . "'") !== false ||
                    strpos($content, '"' . $scenario . '"') !== false) {
                    $foundScenarios++;
                }
            }

            $this->recordTest(
                '트리거 시나리오 S1-S10 정의',
                $foundScenarios >= 10,
                $foundScenarios >= 10
                    ? 'S1-S10 모든 트리거 시나리오 정의됨'
                    : "{$foundScenarios}/10 트리거 시나리오 발견",
                ['expected' => array_keys($expectedScenarios), 'found' => $foundScenarios]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '트리거 시나리오 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 원인 계층 (CAUSE_LAYERS) 테스트
     */
    private function testCauseLayers(): void
    {
        $expectedLayers = [
            'cognitive',        // 인지적
            'behavioral',       // 행동적
            'motivational',     // 동기적
            'environmental'     // 환경적
        ];

        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            $hasCauseLayers = strpos($content, 'CAUSE_LAYERS') !== false;

            $this->recordTest(
                '원인 계층 상수',
                $hasCauseLayers,
                $hasCauseLayers ? 'CAUSE_LAYERS 정의됨' : 'CAUSE_LAYERS 없음'
            );

            $foundLayers = 0;
            foreach ($expectedLayers as $layer) {
                if (strpos($content, "'" . $layer . "'") !== false ||
                    strpos($content, '"' . $layer . '"') !== false) {
                    $foundLayers++;
                }
            }

            $this->recordTest(
                '4대 원인 계층 정의',
                $foundLayers >= 4,
                $foundLayers >= 4
                    ? '인지/행동/동기/환경 4대 원인 계층 정의됨'
                    : "{$foundLayers}/4 원인 계층 발견"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '원인 계층 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 컴포넌트 로드 테스트
     */
    private function testComponentsLoad(): void
    {
        $components = [
            'RuleParser.php' => 'RuleParser',
            'ConditionEvaluator.php' => 'ConditionEvaluator',
            'ActionExecutor.php' => 'ActionExecutor',
            'DataContext.php' => 'DataContext',
            'ResponseGenerator.php' => 'ResponseGenerator',
            'RuleCache.php' => 'RuleCache'
        ];

        foreach ($components as $file => $className) {
            $filePath = __DIR__ . '/engine/' . $file;

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class ' . $className) !== false ||
                            strpos($content, 'class Agent15' . $className) !== false;

                $this->recordTest(
                    "{$className} 컴포넌트",
                    $hasClass,
                    $hasClass ? "{$className} 클래스 정의됨" : "{$className} 클래스 없음"
                );
            } else {
                $this->recordTest(
                    "{$className} 컴포넌트",
                    false,
                    "파일 없음: engine/{$file}"
                );
            }
        }
    }

    /**
     * NLUAnalyzer 테스트
     */
    private function testNLUAnalyzer(): void
    {
        $filePath = __DIR__ . '/engine/NLUAnalyzer.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'NLUAnalyzer 클래스',
                false,
                '파일 없음: engine/NLUAnalyzer.php (고급 기능)'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class NLUAnalyzer') !== false;
            $hasAnalyze = strpos($content, 'analyze') !== false;

            $this->recordTest(
                'NLUAnalyzer 클래스',
                $hasClass,
                $hasClass ? 'NLUAnalyzer 클래스 정의됨' : 'NLUAnalyzer 없음'
            );

            $this->recordTest(
                'NLU 분석 메서드',
                $hasAnalyze,
                $hasAnalyze ? 'analyze 메서드 정의됨' : 'analyze 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'NLUAnalyzer 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * PersonaTransitionManager 테스트
     */
    private function testPersonaTransitionManager(): void
    {
        $filePath = __DIR__ . '/engine/PersonaTransitionManager.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaTransitionManager 클래스',
                false,
                '파일 없음: engine/PersonaTransitionManager.php (고급 기능)'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class PersonaTransitionManager') !== false;
            $hasTransition = strpos($content, 'transition') !== false;

            $this->recordTest(
                'PersonaTransitionManager 클래스',
                $hasClass,
                $hasClass ? 'PersonaTransitionManager 클래스 정의됨' : 'PersonaTransitionManager 없음'
            );

            $this->recordTest(
                '페르소나 전환 메서드',
                $hasTransition,
                $hasTransition ? 'transition 메서드 정의됨' : 'transition 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'PersonaTransitionManager 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * rules.yaml 구조 테스트
     */
    private function testRulesYaml(): void
    {
        $filePath = __DIR__ . '/rules.yaml';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'rules.yaml 존재',
                false,
                '파일 없음: rules.yaml'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $fileSize = strlen($content);

            $this->recordTest(
                'rules.yaml 존재',
                true,
                "rules.yaml 파일 존재 ({$fileSize} bytes)"
            );

            // 구조 확인
            $hasRules = strpos($content, 'rules:') !== false ||
                       strpos($content, 'trigger_scenarios:') !== false;

            $this->recordTest(
                'rules.yaml 구조',
                $hasRules,
                $hasRules ? 'YAML 규칙 구조 확인됨' : 'YAML 규칙 구조 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'rules.yaml 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }
}

// =========================================================================
// 테스트 실행
// =========================================================================

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent15PersonaTest();
    $test->runAllTests();

    switch ($format) {
        case 'json':
            header('Content-Type: application/json; charset=utf-8');
            echo $test->toJson();
            break;

        case 'html':
        default:
            $test->renderHtml();
            break;
    }
} catch (Throwable $e) {
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
        echo "<h1>❌ 테스트 실행 실패</h1>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</body></html>";
    }
}

/*
 * =========================================================================
 * 사용법
 * =========================================================================
 *
 * HTML 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent15_problem_redefinition/persona_system/test.php?format=json
 *
 * =========================================================================
 */
