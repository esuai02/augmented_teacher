<?php
/**
 * test.php
 *
 * Agent07 상호작용 타겟팅 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent07InteractionTargeting
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test.php
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
 * Agent07PersonaTest
 *
 * 상호작용 타겟팅 에이전트 테스트 클래스
 */
class Agent07PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            7,                           // 에이전트 번호
            'interaction_targeting',     // 에이전트 이름
            '상호작용 타겟팅',             // 에이전트 한글명
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
            'engine/PersonaRuleEngine.php' => 'PersonaRuleEngine 메인 클래스',
            'engine/RuleParser.php' => '규칙 파서 클래스',
            'engine/ConditionEvaluator.php' => '조건 평가기 클래스',
            'engine/DataContext.php' => '데이터 컨텍스트 클래스',
            'engine/ResponseGenerator.php' => '응답 생성기 클래스',
            'personas.md' => '페르소나 정의 문서',
            'rules.yaml' => '규칙 정의 파일',
            'contextlist.md' => '컨텍스트 목록 문서'
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
            'mdl_at_agent_messages' => '에이전트 간 메시지 테이블',
            'mdl_agent07_persona_log' => 'Agent07 페르소나 식별 로그',
            'mdl_agent07_context_log' => 'Agent07 컨텍스트 로그'
        ];
    }

    /**
     * Agent07 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaRuleEngine 클래스 로드 테스트
        $this->testPersonaRuleEngineLoad();

        // 2. 상황(Situation) 식별 규칙 테스트
        $this->testSituationRules();

        // 3. 페르소나(Persona) 식별 규칙 테스트
        $this->testPersonaRules();

        // 4. 2단계 식별 프로세스 테스트
        $this->testTwoStepIdentification();

        // 5. 컴포넌트 로드 테스트
        $this->testComponentsLoad();

        // 6. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 7. rules.yaml 구조 테스트
        $this->testRulesYaml();

        // 8. Fallback 규칙 테스트
        $this->testFallbackRules();
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

            $this->recordTest(
                'PersonaRuleEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'PersonaRuleEngine 클래스 발견' : '클래스 정의 없음'
            );

            // 주요 메서드 확인
            $hasIdentifyPersona = strpos($content, 'identifyPersona') !== false;
            $hasIdentifySituation = strpos($content, 'identifySituation') !== false;

            $this->recordTest(
                'identifyPersona 메서드',
                $hasIdentifyPersona,
                $hasIdentifyPersona ? 'identifyPersona 메서드 정의됨' : 'identifyPersona 없음'
            );

            $this->recordTest(
                'identifySituation 메서드',
                $hasIdentifySituation,
                $hasIdentifySituation ? 'identifySituation 메서드 정의됨' : 'identifySituation 없음'
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
     * 상황(Situation) 식별 규칙 테스트
     */
    private function testSituationRules(): void
    {
        // Agent07의 상황 코드 (S1-S4 또는 유사)
        $expectedSituations = ['S1', 'S2', 'S3', 'S4'];

        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            $hasSituationRules = strpos($content, 'situation_rules') !== false ||
                                 strpos($content, 'situationRules') !== false ||
                                 strpos($content, 'identifySituation') !== false;

            $this->recordTest(
                '상황 식별 규칙 구조',
                $hasSituationRules,
                $hasSituationRules ? '상황 식별 규칙 구조 발견' : '상황 식별 규칙 없음'
            );

            // 상황 코드 확인
            $foundSituations = 0;
            foreach ($expectedSituations as $situation) {
                if (strpos($content, "'" . $situation . "'") !== false ||
                    strpos($content, '"' . $situation . '"') !== false) {
                    $foundSituations++;
                }
            }

            $this->recordTest(
                '상황 코드 정의',
                $foundSituations > 0,
                $foundSituations > 0
                    ? "{$foundSituations}/4 상황 코드 발견"
                    : '상황 코드 확인 불가 (rules.yaml에서 정의될 수 있음)'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '상황 식별 규칙 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 페르소나(Persona) 식별 규칙 테스트
     */
    private function testPersonaRules(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            $hasPersonaRules = strpos($content, 'persona_rules') !== false ||
                               strpos($content, 'personaRules') !== false ||
                               strpos($content, 'identifyPersonaForSituation') !== false;

            $this->recordTest(
                '페르소나 식별 규칙 구조',
                $hasPersonaRules,
                $hasPersonaRules ? '페르소나 식별 규칙 구조 발견' : '페르소나 식별 규칙 없음'
            );

            // 응답 설정 매핑 확인
            $hasResponseMapping = strpos($content, 'response_mapping') !== false ||
                                  strpos($content, 'responseMapping') !== false ||
                                  strpos($content, 'getResponseConfig') !== false;

            $this->recordTest(
                '응답 설정 매핑',
                $hasResponseMapping,
                $hasResponseMapping ? '응답 설정 매핑 구조 발견' : '응답 설정 매핑 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '페르소나 식별 규칙 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 2단계 식별 프로세스 테스트
     */
    private function testTwoStepIdentification(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            // 2단계 프로세스: Situation → Persona
            $step1 = strpos($content, 'identifySituation') !== false;
            $step2 = strpos($content, 'identifyPersonaForSituation') !== false ||
                     strpos($content, 'identifyPersona') !== false;

            $hasTwoStepProcess = $step1 && $step2;

            $this->recordTest(
                '2단계 식별 프로세스',
                $hasTwoStepProcess,
                $hasTwoStepProcess
                    ? 'Situation → Persona 2단계 프로세스 확인'
                    : '2단계 프로세스 패턴 불완전'
            );

            // 결과 조합 확인
            $hasResultCombination = strpos($content, 'situation') !== false &&
                                    strpos($content, 'persona') !== false;

            $this->recordTest(
                '결과 조합 구조',
                $hasResultCombination,
                $hasResultCombination ? 'situation + persona 결과 조합 확인' : '결과 조합 구조 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '2단계 식별 프로세스 테스트',
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

            // YAML 구조 확인
            $hasSituationRules = strpos($content, 'situation_rules') !== false;
            $hasPersonaRules = strpos($content, 'persona_rules') !== false;
            $hasFallback = strpos($content, 'fallback') !== false;

            $this->recordTest(
                'rules.yaml 상황 규칙',
                $hasSituationRules,
                $hasSituationRules ? 'situation_rules 섹션 존재' : 'situation_rules 없음'
            );

            $this->recordTest(
                'rules.yaml 페르소나 규칙',
                $hasPersonaRules,
                $hasPersonaRules ? 'persona_rules 섹션 존재' : 'persona_rules 없음'
            );

            $this->recordTest(
                'rules.yaml Fallback 규칙',
                $hasFallback,
                $hasFallback ? 'fallback 규칙 존재' : 'fallback 규칙 없음'
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
     * Fallback 규칙 테스트
     */
    private function testFallbackRules(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaRuleEngine.php';
            $content = file_get_contents($filePath);

            $hasSituationFallback = strpos($content, 'getSituationFallback') !== false ||
                                    strpos($content, 'situation_fallback') !== false;
            $hasPersonaFallback = strpos($content, 'getPersonaFallback') !== false ||
                                  strpos($content, 'persona_fallback') !== false;

            $this->recordTest(
                '상황 Fallback 처리',
                $hasSituationFallback,
                $hasSituationFallback ? '상황 Fallback 로직 존재' : '상황 Fallback 없음'
            );

            $this->recordTest(
                '페르소나 Fallback 처리',
                $hasPersonaFallback,
                $hasPersonaFallback ? '페르소나 Fallback 로직 존재' : '페르소나 Fallback 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Fallback 규칙 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }
}

// =========================================================================
// 테스트 실행
// =========================================================================

// 출력 형식 결정
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent07PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent07_interaction_targeting/persona_system/test.php?format=json
 *
 * =========================================================================
 */
