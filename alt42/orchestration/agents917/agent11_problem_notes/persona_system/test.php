<?php
/**
 * test.php
 *
 * Agent11 문제노트 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent11ProblemNotes
 * @version     2.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test.php
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
 * Agent11PersonaTest
 *
 * 문제노트 에이전트 테스트 클래스
 */
class Agent11PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            11,                        // 에이전트 번호
            'problem_notes',           // 에이전트 이름
            '문제노트',                 // 에이전트 한글명
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
            'PersonaEngine.php' => 'Agent11PersonaEngine 메인 클래스',
            'config.php' => '에이전트 설정 파일',
            'rules/rules.yaml' => '규칙 정의 파일',
            'api/persona.php' => 'API 엔드포인트',
            'templates/default.yaml' => '기본 템플릿',
            'templates/AnalyticalHelper.yaml' => '분석적 조력자 템플릿',
            'templates/EncouragingCoach.yaml' => '격려형 코치 템플릿',
            'templates/PatientGuide.yaml' => '차분한 안내자 템플릿',
            'templates/PracticeLeader.yaml' => '연습 리더 템플릿'
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
     * Agent11 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent11PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. AbstractPersonaEngine 상속 테스트
        $this->testAbstractPersonaEngineInheritance();

        // 3. 4개 페르소나 정의 테스트
        $this->testPersonaDefinitions();

        // 4. PersonaStateSync 연동 테스트
        $this->testPersonaStateSync();

        // 5. 페르소나 결정 로직 테스트
        $this->testPersonaDetermination();

        // 6. 감정 상태 브로드캐스트 테스트
        $this->testEmotionalStateBroadcast();

        // 7. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 8. 템플릿 디렉토리 테스트
        $this->testTemplatesDirectory();

        // 9. config.php 테스트
        $this->testConfigFile();

        // 10. rules.yaml 테스트
        $this->testRulesYaml();
    }

    /**
     * Agent11PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent11PersonaEngine 클래스 로드',
                false,
                '파일 없음: PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent11PersonaEngine') !== false;
            $hasNamespace = strpos($content, 'namespace AugmentedTeacher\\Agent11\\PersonaSystem') !== false;

            $this->recordTest(
                'Agent11PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent11PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                '네임스페이스 정의',
                $hasNamespace,
                $hasNamespace ? '올바른 네임스페이스' : '네임스페이스 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent11PersonaEngine 클래스 로드',
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
            $filePath = __DIR__ . '/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

            // 공통 컴포넌트 사용 확인
            $usesCommonComponents =
                strpos($content, 'BaseConditionEvaluator') !== false &&
                strpos($content, 'BaseActionExecutor') !== false &&
                strpos($content, 'YamlRuleParser') !== false;

            $this->recordTest(
                '공통 컴포넌트 사용',
                $usesCommonComponents,
                $usesCommonComponents ? '공통 컴포넌트 사용됨' : '일부 컴포넌트 누락'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'AbstractPersonaEngine 상속 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 4개 페르소나 정의 테스트
     */
    private function testPersonaDefinitions(): void
    {
        $expectedPersonas = [
            'AnalyticalHelper' => '분석적 조력자',
            'EncouragingCoach' => '격려형 코치',
            'PatientGuide' => '차분한 안내자',
            'PracticeLeader' => '연습 리더'
        ];

        try {
            $filePath = __DIR__ . '/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $foundPersonas = 0;
            foreach (array_keys($expectedPersonas) as $persona) {
                if (strpos($content, "'" . $persona . "'") !== false ||
                    strpos($content, '"' . $persona . '"') !== false) {
                    $foundPersonas++;
                }
            }

            $this->recordTest(
                '4개 페르소나 정의',
                $foundPersonas === 4,
                $foundPersonas === 4
                    ? '모든 페르소나 정의됨 (AnalyticalHelper, EncouragingCoach, PatientGuide, PracticeLeader)'
                    : "{$foundPersonas}/4 페르소나 발견",
                ['expected' => array_keys($expectedPersonas), 'found' => $foundPersonas]
            );

            // 기본 페르소나 확인
            $hasDefaultPersona = strpos($content, "defaultPersona = 'AnalyticalHelper'") !== false;

            $this->recordTest(
                '기본 페르소나 설정',
                $hasDefaultPersona,
                $hasDefaultPersona ? '기본 페르소나: AnalyticalHelper' : '기본 페르소나 설정 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '페르소나 정의 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * PersonaStateSync 연동 테스트
     */
    private function testPersonaStateSync(): void
    {
        try {
            $filePath = __DIR__ . '/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasStateSync = strpos($content, 'PersonaStateSync') !== false;
            $hasGetStateSync = strpos($content, 'getStateSync') !== false;
            $hasSaveState = strpos($content, 'saveState') !== false;
            $hasGetState = strpos($content, 'getState') !== false;

            $this->recordTest(
                'PersonaStateSync 클래스 참조',
                $hasStateSync,
                $hasStateSync ? 'PersonaStateSync 참조됨' : 'PersonaStateSync 없음'
            );

            $this->recordTest(
                'getStateSync 메서드',
                $hasGetStateSync,
                $hasGetStateSync ? 'getStateSync 메서드 존재' : 'getStateSync 없음'
            );

            $stateMethods = ($hasSaveState ? 1 : 0) + ($hasGetState ? 1 : 0);

            $this->recordTest(
                '상태 관리 메서드',
                $stateMethods >= 2,
                $stateMethods >= 2 ? 'saveState, getState 메서드 존재' : '상태 관리 메서드 부족'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'PersonaStateSync 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 페르소나 결정 로직 테스트
     */
    private function testPersonaDetermination(): void
    {
        try {
            $filePath = __DIR__ . '/PersonaEngine.php';
            $content = file_get_contents($filePath);

            // determinePersona 메서드 확인
            $hasDeterminePersona = strpos($content, 'function determinePersona') !== false;

            $this->recordTest(
                'determinePersona 메서드',
                $hasDeterminePersona,
                $hasDeterminePersona ? 'determinePersona 메서드 정의됨' : 'determinePersona 없음'
            );

            // 감정 상태 기반 페르소나 전환
            $hasEmotionalTransition = strpos($content, 'emotional_state') !== false &&
                                       strpos($content, 'EncouragingCoach') !== false;

            $this->recordTest(
                '감정 기반 페르소나 전환',
                $hasEmotionalTransition,
                $hasEmotionalTransition ? '감정 상태 → EncouragingCoach 전환 로직' : '감정 기반 전환 없음'
            );

            // 오류 유형 기반 페르소나 결정
            $hasErrorTypeLogic = strpos($content, 'error_type') !== false;

            $this->recordTest(
                '오류 유형 기반 결정',
                $hasErrorTypeLogic,
                $hasErrorTypeLogic ? 'error_type 기반 페르소나 결정 로직' : '오류 유형 로직 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '페르소나 결정 로직 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 감정 상태 브로드캐스트 테스트
     */
    private function testEmotionalStateBroadcast(): void
    {
        try {
            $filePath = __DIR__ . '/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasBroadcast = strpos($content, 'broadcastEmotionalState') !== false;
            $hasMessageBus = strpos($content, 'getMessageBus') !== false;

            $this->recordTest(
                'broadcastEmotionalState 메서드',
                $hasBroadcast,
                $hasBroadcast ? '감정 상태 브로드캐스트 메서드 정의됨' : 'broadcastEmotionalState 없음'
            );

            $this->recordTest(
                'MessageBus 연동',
                $hasMessageBus,
                $hasMessageBus ? 'MessageBus를 통한 에이전트 간 통신' : 'MessageBus 연동 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '감정 브로드캐스트 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 템플릿 디렉토리 테스트
     */
    private function testTemplatesDirectory(): void
    {
        $templatesPath = __DIR__ . '/templates';

        if (!is_dir($templatesPath)) {
            $this->recordTest(
                'templates 디렉토리',
                false,
                'templates 디렉토리 없음'
            );
            return;
        }

        $expectedTemplates = [
            'default.yaml',
            'AnalyticalHelper.yaml',
            'EncouragingCoach.yaml',
            'PatientGuide.yaml',
            'PracticeLeader.yaml'
        ];

        $foundTemplates = 0;
        foreach ($expectedTemplates as $template) {
            if (file_exists($templatesPath . '/' . $template)) {
                $foundTemplates++;
            }
        }

        $this->recordTest(
            'templates 디렉토리',
            true,
            "templates 디렉토리 존재 ({$foundTemplates}/" . count($expectedTemplates) . "개 템플릿)",
            ['expected' => $expectedTemplates, 'found' => $foundTemplates]
        );

        $this->recordTest(
            '페르소나별 템플릿',
            $foundTemplates >= 4,
            $foundTemplates >= 4 ? '4개 페르소나 템플릿 존재' : '일부 템플릿 누락'
        );
    }

    /**
     * config.php 테스트
     */
    private function testConfigFile(): void
    {
        $filePath = __DIR__ . '/config.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'config.php 존재',
                false,
                '파일 없음: config.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $fileSize = strlen($content);

            $this->recordTest(
                'config.php 존재',
                true,
                "config.php 파일 존재 ({$fileSize} bytes)"
            );

            // 설정 클래스 확인
            $hasConfigClass = strpos($content, 'class Agent11Config') !== false;

            $this->recordTest(
                'Agent11Config 클래스',
                $hasConfigClass,
                $hasConfigClass ? 'Agent11Config 클래스 정의됨' : '설정 클래스 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'config.php 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * rules.yaml 테스트
     */
    private function testRulesYaml(): void
    {
        $filePath = __DIR__ . '/rules/rules.yaml';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'rules.yaml 존재',
                false,
                '파일 없음: rules/rules.yaml'
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
                       strpos($content, 'persona_rules:') !== false;

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
    $test = new Agent11PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test.php?format=json
 *
 * 레거시 테스트 (기능 테스트):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test_legacy.php
 *
 * =========================================================================
 */
