<?php
/**
 * test.php
 *
 * Agent18 시그니처 루틴 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent18SignatureRoutine
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent18_signature_routine/persona_system/test.php
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
 * Agent18PersonaTest
 *
 * 시그니처 루틴 에이전트 테스트 클래스
 */
class Agent18PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            18,                        // 에이전트 번호
            'signature_routine',       // 에이전트 이름
            '시그니처 루틴',            // 에이전트 한글명
            __DIR__                    // 기본 경로 (persona_system)
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
            'engine/RoutineAnalyzer.php' => '루틴 분석기 클래스',
            'engine/ResponseGenerator.php' => '응답 생성기 클래스',
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
            'mdl_alt42_agent18_persona_records' => '페르소나 기록 테이블',
            'mdl_alt42_agent18_session_logs' => '세션 로그 테이블',
            'mdl_alt42_agent18_routine_patterns' => '루틴 패턴 테이블'
        ];
    }

    /**
     * Agent18 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaRuleEngine 클래스 로드 테스트
        $this->testPersonaRuleEngineLoad();

        // 2. 컨텍스트 코드 테스트 (SR, TP)
        $this->testContextCodes();

        // 3. RoutineAnalyzer 컴포넌트 테스트
        $this->testRoutineAnalyzer();

        // 4. 엔진 컴포넌트 테스트
        $this->testEngineComponents();

        // 5. 규칙 기반 처리 테스트
        $this->testRuleBasedProcessing();

        // 6. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 7. rules.yaml 구조 테스트
        $this->testRulesYaml();

        // 8. templates 디렉토리 테스트
        $this->testTemplatesDirectory();
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
            $hasProcess = strpos($content, 'function process') !== false;

            $this->recordTest(
                'PersonaRuleEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'PersonaRuleEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'process 메서드',
                $hasProcess,
                $hasProcess ? 'process 메서드 정의됨' : 'process 메서드 없음'
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
     * 컨텍스트 코드 테스트 (SR, TP)
     */
    private function testContextCodes(): void
    {
        $expectedContexts = [
            'SR01' => '첫 루틴 분석 시작',
            'SR02' => '루틴 패턴 발견',
            'TP02' => '골든타임 발견'
        ];

        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            $foundContexts = 0;
            foreach (array_keys($expectedContexts) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundContexts++;
                }
            }

            $this->recordTest(
                '컨텍스트 코드 (SR/TP) 정의',
                $foundContexts >= 3,
                $foundContexts >= 3
                    ? 'SR01, SR02, TP02 컨텍스트 코드 정의됨'
                    : "{$foundContexts}/3 컨텍스트 코드 발견",
                ['expected' => array_keys($expectedContexts), 'found' => $foundContexts]
            );

            // determineContext 메서드 확인
            $hasDetermineContext = strpos($content, 'determineContext') !== false;

            $this->recordTest(
                'determineContext 메서드',
                $hasDetermineContext,
                $hasDetermineContext ? 'determineContext 메서드 정의됨' : 'determineContext 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '컨텍스트 코드 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * RoutineAnalyzer 컴포넌트 테스트
     */
    private function testRoutineAnalyzer(): void
    {
        $filePath = __DIR__ . '/engine/RoutineAnalyzer.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'RoutineAnalyzer 클래스',
                false,
                '파일 없음: engine/RoutineAnalyzer.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class RoutineAnalyzer') !== false;
            $hasAnalyze = strpos($content, 'analyze') !== false;
            $hasRecommendation = strpos($content, 'getRecommendation') !== false;

            $this->recordTest(
                'RoutineAnalyzer 클래스',
                $hasClass,
                $hasClass ? 'RoutineAnalyzer 클래스 정의됨' : 'RoutineAnalyzer 클래스 없음'
            );

            $this->recordTest(
                'analyze 메서드',
                $hasAnalyze,
                $hasAnalyze ? 'analyze 메서드 정의됨' : 'analyze 메서드 없음'
            );

            $this->recordTest(
                'getRecommendation 메서드',
                $hasRecommendation,
                $hasRecommendation ? 'getRecommendation 메서드 정의됨' : 'getRecommendation 메서드 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'RoutineAnalyzer 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 엔진 컴포넌트 테스트
     */
    private function testEngineComponents(): void
    {
        $components = [
            'RuleParser.php' => 'RuleParser',
            'ConditionEvaluator.php' => 'ConditionEvaluator',
            'ActionExecutor.php' => 'ActionExecutor',
            'DataContext.php' => 'DataContext',
            'ResponseGenerator.php' => 'ResponseGenerator'
        ];

        foreach ($components as $file => $className) {
            $filePath = __DIR__ . '/engine/' . $file;

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class ' . $className) !== false;

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
     * 규칙 기반 처리 테스트
     */
    private function testRuleBasedProcessing(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            // 규칙 평가 메서드
            $hasEvaluateRules = strpos($content, 'evaluateRules') !== false;

            $this->recordTest(
                'evaluateRules 메서드',
                $hasEvaluateRules,
                $hasEvaluateRules ? 'evaluateRules 메서드 정의됨' : 'evaluateRules 없음'
            );

            // 액션 실행 메서드
            $hasExecuteActions = strpos($content, 'executeActions') !== false;

            $this->recordTest(
                'executeActions 메서드',
                $hasExecuteActions,
                $hasExecuteActions ? 'executeActions 메서드 정의됨' : 'executeActions 없음'
            );

            // 응답 생성 메서드
            $hasGenerateResponse = strpos($content, 'generateResponse') !== false;

            $this->recordTest(
                'generateResponse 메서드',
                $hasGenerateResponse,
                $hasGenerateResponse ? 'generateResponse 메서드 정의됨' : 'generateResponse 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '규칙 처리 테스트',
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
                       strpos($content, 'routine_rules:') !== false;

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

    /**
     * templates 디렉토리 테스트
     */
    private function testTemplatesDirectory(): void
    {
        $templatesPath = __DIR__ . '/templates';

        if (!is_dir($templatesPath)) {
            $this->recordTest(
                'templates 디렉토리',
                false,
                'templates 디렉토리 없음 (선택적)'
            );
            return;
        }

        $files = glob($templatesPath . '/*');
        $fileCount = count($files);

        $this->recordTest(
            'templates 디렉토리',
            true,
            "templates 디렉토리 존재 ({$fileCount}개 파일)"
        );
    }
}

// =========================================================================
// 테스트 실행
// =========================================================================

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent18PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent18_signature_routine/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent18_signature_routine/persona_system/test.php?format=json
 *
 * =========================================================================
 */
