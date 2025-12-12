<?php
/**
 * test.php
 *
 * Agent20 개입 준비 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent20InterventionPreparation
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/test.php
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
 * Agent20PersonaTest
 *
 * 개입 준비 에이전트 테스트 클래스
 */
class Agent20PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            20,                              // 에이전트 번호
            'intervention_preparation',      // 에이전트 이름
            '개입 준비',                      // 에이전트 한글명
            __DIR__                          // 기본 경로 (persona_system)
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
            'engine/Agent20PersonaEngine.php' => 'Agent20PersonaEngine 메인 클래스',
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
            'mdl_at_agent_messages' => '에이전트 간 통신 테이블',
            'mdl_at_persona_events' => '페르소나 이벤트 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    /**
     * Agent20 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent20PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. AbstractPersonaEngine 상속 테스트
        $this->testAbstractPersonaEngineInheritance();

        // 3. 개입 전략 테스트
        $this->testInterventionStrategies();

        // 4. AgentCommunicator 연동 테스트
        $this->testAgentCommunicator();

        // 5. PersonaEventPublisher 연동 테스트
        $this->testPersonaEventPublisher();

        // 6. 액션 핸들러 테스트
        $this->testActionHandlers();

        // 7. 분석 및 개입 메서드 테스트
        $this->testAnalysisAndIntervention();

        // 8. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 9. rules 디렉토리 테스트
        $this->testRulesDirectory();
    }

    /**
     * Agent20PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent20PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent20PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent20PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'Agent20PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent20PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent20PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * AbstractPersonaEngine 상속 테스트
     */
    private function testAbstractPersonaEngineInheritance(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 필수 추상 메서드 구현 확인
            $hasInitializeComponents = strpos($content, 'initializeComponents') !== false;
            $hasGetRulesPath = strpos($content, 'getRulesPath') !== false;

            $this->recordTest(
                'initializeComponents 메서드',
                $hasInitializeComponents,
                $hasInitializeComponents ? 'initializeComponents 메서드 구현됨' : 'initializeComponents 없음'
            );

            $this->recordTest(
                'getRulesPath 메서드',
                $hasGetRulesPath,
                $hasGetRulesPath ? 'getRulesPath 메서드 구현됨' : 'getRulesPath 없음'
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
     * 개입 전략 테스트
     */
    private function testInterventionStrategies(): void
    {
        $expectedStrategies = [
            'emotional_support' => '정서적 지원',
            'cognitive_scaffolding' => '학습 지원 (인지적 스캐폴딩)',
            'motivation_boost' => '동기 부여',
            'behavior_guidance' => '행동 안내',
            'immediate_help' => '즉각적 도움'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasStrategies = strpos($content, 'interventionStrategies') !== false;

            $this->recordTest(
                '개입 전략 프로퍼티',
                $hasStrategies,
                $hasStrategies ? 'interventionStrategies 프로퍼티 정의됨' : 'interventionStrategies 없음'
            );

            $foundStrategies = 0;
            foreach (array_keys($expectedStrategies) as $strategy) {
                if (strpos($content, "'" . $strategy . "'") !== false ||
                    strpos($content, '"' . $strategy . '"') !== false) {
                    $foundStrategies++;
                }
            }

            $this->recordTest(
                '5대 개입 전략 정의',
                $foundStrategies >= 5,
                $foundStrategies >= 5
                    ? '5대 개입 전략 모두 정의됨'
                    : "{$foundStrategies}/5 개입 전략 발견",
                ['expected' => array_keys($expectedStrategies), 'found' => $foundStrategies]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '개입 전략 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * AgentCommunicator 연동 테스트
     */
    private function testAgentCommunicator(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasCommunicator = strpos($content, 'AgentCommunicator') !== false;
            $hasMulticast = strpos($content, 'multicast') !== false;
            $hasReceiveMessages = strpos($content, 'receiveMessages') !== false;

            $this->recordTest(
                'AgentCommunicator 연동',
                $hasCommunicator,
                $hasCommunicator ? 'AgentCommunicator 참조 발견' : 'AgentCommunicator 없음'
            );

            $this->recordTest(
                'multicast 통신 기능',
                $hasMulticast,
                $hasMulticast ? 'multicast 메서드 사용' : 'multicast 없음'
            );

            $this->recordTest(
                'receiveMessages 기능',
                $hasReceiveMessages,
                $hasReceiveMessages ? 'receiveMessages 메서드 사용' : 'receiveMessages 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'AgentCommunicator 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * PersonaEventPublisher 연동 테스트
     */
    private function testPersonaEventPublisher(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasEventPublisher = strpos($content, 'PersonaEventPublisher') !== false;
            $hasPublish = strpos($content, 'publish') !== false;

            $this->recordTest(
                'PersonaEventPublisher 연동',
                $hasEventPublisher,
                $hasEventPublisher ? 'PersonaEventPublisher 참조 발견' : 'PersonaEventPublisher 없음'
            );

            $this->recordTest(
                'publish 이벤트 기능',
                $hasPublish,
                $hasPublish ? 'publish 메서드 사용' : 'publish 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'PersonaEventPublisher 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 액션 핸들러 테스트
     */
    private function testActionHandlers(): void
    {
        $expectedHandlers = [
            'prepare_intervention' => '개입 준비',
            'request_intervention' => '개입 요청',
            'select_strategy' => '전략 선택',
            'collaborate' => '협력 요청',
            'adjust_priority' => '우선순위 조정'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            $content = file_get_contents($filePath);

            $foundHandlers = 0;
            foreach (array_keys($expectedHandlers) as $handler) {
                if (strpos($content, "'" . $handler . "'") !== false ||
                    strpos($content, '"' . $handler . '"') !== false) {
                    $foundHandlers++;
                }
            }

            $this->recordTest(
                '액션 핸들러 등록',
                $foundHandlers >= 5,
                $foundHandlers >= 5
                    ? '5개 액션 핸들러 모두 등록됨'
                    : "{$foundHandlers}/5 액션 핸들러 발견",
                ['expected' => array_keys($expectedHandlers), 'found' => $foundHandlers]
            );

            // registerHandler 또는 registerAgent20ActionHandlers
            $hasRegisterHandler = strpos($content, 'registerHandler') !== false ||
                                  strpos($content, 'registerAgent20ActionHandlers') !== false;

            $this->recordTest(
                '액션 핸들러 등록 메서드',
                $hasRegisterHandler,
                $hasRegisterHandler ? '핸들러 등록 메서드 발견' : '핸들러 등록 메서드 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '액션 핸들러 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 분석 및 개입 메서드 테스트
     */
    private function testAnalysisAndIntervention(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent20PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 분석 메서드
            $hasAnalyzeAndPrepare = strpos($content, 'analyzeAndPrepare') !== false;
            $hasAnalyzeStudentState = strpos($content, 'analyzeStudentState') !== false;

            $this->recordTest(
                'analyzeAndPrepare 메서드',
                $hasAnalyzeAndPrepare,
                $hasAnalyzeAndPrepare ? 'analyzeAndPrepare 메서드 정의됨' : 'analyzeAndPrepare 없음'
            );

            $this->recordTest(
                'analyzeStudentState 메서드',
                $hasAnalyzeStudentState,
                $hasAnalyzeStudentState ? 'analyzeStudentState 메서드 정의됨' : 'analyzeStudentState 없음'
            );

            // 전략 선택 메서드
            $hasSelectBestStrategy = strpos($content, 'selectBestStrategy') !== false;

            $this->recordTest(
                'selectBestStrategy 메서드',
                $hasSelectBestStrategy,
                $hasSelectBestStrategy ? 'selectBestStrategy 메서드 정의됨' : 'selectBestStrategy 없음'
            );

            // 점수 계산 메서드
            $hasCalculateScore = strpos($content, 'calculateInterventionScore') !== false;

            $this->recordTest(
                'calculateInterventionScore 메서드',
                $hasCalculateScore,
                $hasCalculateScore ? 'calculateInterventionScore 메서드 정의됨' : 'calculateInterventionScore 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '분석/개입 메서드 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * rules 디렉토리 테스트
     */
    private function testRulesDirectory(): void
    {
        $rulesPath = __DIR__ . '/rules';

        if (!is_dir($rulesPath)) {
            // rules.yaml 파일 직접 확인
            $rulesFile = __DIR__ . '/rules.yaml';
            if (file_exists($rulesFile)) {
                $content = file_get_contents($rulesFile);
                $fileSize = strlen($content);

                $this->recordTest(
                    'rules.yaml 존재',
                    true,
                    "rules.yaml 파일 존재 ({$fileSize} bytes)"
                );
                return;
            }

            $this->recordTest(
                'rules 디렉토리/파일',
                false,
                'rules 디렉토리 또는 rules.yaml 파일 없음'
            );
            return;
        }

        $files = glob($rulesPath . '/*.yaml');
        $fileCount = count($files);

        $this->recordTest(
            'rules 디렉토리',
            true,
            "rules 디렉토리 존재 ({$fileCount}개 YAML 파일)"
        );

        // rules.yaml 확인
        $rulesYaml = $rulesPath . '/rules.yaml';
        if (file_exists($rulesYaml)) {
            $content = file_get_contents($rulesYaml);
            $hasRules = strpos($content, 'rules:') !== false ||
                       strpos($content, 'intervention_rules:') !== false;

            $this->recordTest(
                'rules.yaml 구조',
                $hasRules,
                $hasRules ? 'YAML 규칙 구조 확인됨' : 'YAML 규칙 구조 확인 불가'
            );
        }
    }
}

// =========================================================================
// 테스트 실행
// =========================================================================

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent20PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent20_intervention_preparation/persona_system/test.php?format=json
 *
 * =========================================================================
 */
