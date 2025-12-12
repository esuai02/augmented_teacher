<?php
/**
 * test.php
 *
 * Agent17 잔여 활동 조정 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent17RemainingActivities
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/test.php
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
 * Agent17PersonaTest
 *
 * 잔여 활동 조정 에이전트 테스트 클래스
 */
class Agent17PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            17,                          // 에이전트 번호
            'remaining_activities',      // 에이전트 이름
            '잔여 활동 조정',             // 에이전트 한글명
            __DIR__                      // 기본 경로 (persona_system)
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
            'engine/Agent17PersonaEngine.php' => 'Agent17PersonaEngine 메인 클래스',
            'engine/fallback/Agent17RuleParser.php' => '규칙 파서 클래스',
            'engine/fallback/Agent17ConditionEvaluator.php' => '조건 평가기 클래스',
            'engine/fallback/Agent17ActionExecutor.php' => '액션 실행기 클래스',
            'engine/fallback/Agent17DataContext.php' => '데이터 컨텍스트 클래스',
            'engine/fallback/Agent17ResponseGenerator.php' => '응답 생성기 클래스',
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
     * Agent17 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent17PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 상황 코드 (R1-R5) 테스트
        $this->testSituationCodes();

        // 3. 전략 코드 (ST1-ST5) 테스트
        $this->testStrategyCodes();

        // 4. AbstractPersonaEngine 상속 테스트
        $this->testAbstractPersonaEngineInheritance();

        // 5. Fallback 컴포넌트 테스트
        $this->testFallbackComponents();

        // 6. 페르소나 매핑 (R x ST) 테스트
        $this->testPersonaMapping();

        // 7. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 8. rules.yaml 구조 테스트
        $this->testRulesYaml();
    }

    /**
     * Agent17PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent17PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent17PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent17PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent17PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'Agent17PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent17PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent17PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 상황 코드 (R1-R5) 테스트
     */
    private function testSituationCodes(): void
    {
        $expectedCodes = [
            'R1' => '원활_진행',
            'R2' => '적절_진행',
            'R3' => '지연_진행',
            'R4' => '정체_진행',
            'R5' => '리듬_붕괴'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent17PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasSituationCodes = strpos($content, 'situationCodes') !== false;

            $this->recordTest(
                '상황 코드 프로퍼티',
                $hasSituationCodes,
                $hasSituationCodes ? 'situationCodes 프로퍼티 정의됨' : 'situationCodes 없음'
            );

            $foundCodes = 0;
            foreach (array_keys($expectedCodes) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundCodes++;
                }
            }

            $this->recordTest(
                '상황 코드 R1-R5 정의',
                $foundCodes >= 5,
                $foundCodes >= 5
                    ? 'R1-R5 모든 상황 코드 정의됨'
                    : "{$foundCodes}/5 상황 코드 발견",
                ['expected' => array_keys($expectedCodes), 'found' => $foundCodes]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '상황 코드 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 전략 코드 (ST1-ST5) 테스트
     */
    private function testStrategyCodes(): void
    {
        $expectedStrategies = [
            'ST1' => '질문하기',
            'ST2' => '도제학습_전환',
            'ST3' => '활동축소',
            'ST4' => '하이튜터링',
            'ST5' => '징검다리_활동'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent17PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasStrategyCodes = strpos($content, 'strategyCodes') !== false;

            $this->recordTest(
                '전략 코드 프로퍼티',
                $hasStrategyCodes,
                $hasStrategyCodes ? 'strategyCodes 프로퍼티 정의됨' : 'strategyCodes 없음'
            );

            $foundStrategies = 0;
            foreach (array_keys($expectedStrategies) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundStrategies++;
                }
            }

            $this->recordTest(
                '전략 코드 ST1-ST5 정의',
                $foundStrategies >= 5,
                $foundStrategies >= 5
                    ? 'ST1-ST5 모든 전략 코드 정의됨'
                    : "{$foundStrategies}/5 전략 코드 발견",
                ['expected' => array_keys($expectedStrategies), 'found' => $foundStrategies]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '전략 코드 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * AbstractPersonaEngine 상속 테스트
     */
    private function testAbstractPersonaEngineInheritance(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent17PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 필수 메서드 구현 확인
            $hasInitializeComponents = strpos($content, 'initializeComponents') !== false;
            $hasLoadPersonas = strpos($content, 'loadPersonas') !== false;
            $hasGetDefaultPersona = strpos($content, 'getDefaultPersona') !== false;

            $this->recordTest(
                'initializeComponents 메서드',
                $hasInitializeComponents,
                $hasInitializeComponents ? 'initializeComponents 메서드 구현됨' : 'initializeComponents 없음'
            );

            $this->recordTest(
                'loadPersonas 메서드',
                $hasLoadPersonas,
                $hasLoadPersonas ? 'loadPersonas 메서드 구현됨' : 'loadPersonas 없음'
            );

            $this->recordTest(
                'getDefaultPersona 메서드',
                $hasGetDefaultPersona,
                $hasGetDefaultPersona ? 'getDefaultPersona 메서드 구현됨' : 'getDefaultPersona 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '상속 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * Fallback 컴포넌트 테스트
     */
    private function testFallbackComponents(): void
    {
        $components = [
            'fallback/Agent17RuleParser.php' => 'Agent17RuleParser',
            'fallback/Agent17ConditionEvaluator.php' => 'Agent17ConditionEvaluator',
            'fallback/Agent17ActionExecutor.php' => 'Agent17ActionExecutor',
            'fallback/Agent17DataContext.php' => 'Agent17DataContext',
            'fallback/Agent17ResponseGenerator.php' => 'Agent17ResponseGenerator'
        ];

        foreach ($components as $file => $className) {
            $filePath = __DIR__ . '/engine/' . $file;

            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class ' . $className) !== false;

                $this->recordTest(
                    "{$className} Fallback 컴포넌트",
                    $hasClass,
                    $hasClass ? "{$className} 클래스 정의됨" : "{$className} 클래스 없음"
                );
            } else {
                $this->recordTest(
                    "{$className} Fallback 컴포넌트",
                    false,
                    "파일 없음: engine/{$file}"
                );
            }
        }
    }

    /**
     * 페르소나 매핑 (R x ST 조합) 테스트
     */
    private function testPersonaMapping(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent17PersonaEngine.php';
            $content = file_get_contents($filePath);

            // R1_P1, R2_P1 등 조합 페르소나 확인
            $hasPersonaMapping = strpos($content, 'R1_P1') !== false &&
                                 strpos($content, 'R2_P1') !== false &&
                                 strpos($content, 'R5_P1') !== false;

            $this->recordTest(
                '페르소나 매핑 (R x P)',
                $hasPersonaMapping,
                $hasPersonaMapping ? 'R_P 조합 페르소나 정의됨' : 'R_P 조합 없음'
            );

            // 전략 기반 페르소나 확인
            $hasStrategyMapping = strpos($content, 'strategy') !== false;

            $this->recordTest(
                '전략 기반 페르소나',
                $hasStrategyMapping,
                $hasStrategyMapping ? '전략 기반 페르소나 매핑 존재' : '전략 매핑 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '페르소나 매핑 테스트',
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
                       strpos($content, 'situation_rules:') !== false ||
                       strpos($content, 'strategy_rules:') !== false;

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
    $test = new Agent17PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/test.php?format=json
 *
 * =========================================================================
 */
