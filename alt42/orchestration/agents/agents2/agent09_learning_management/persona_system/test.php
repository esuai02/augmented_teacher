<?php
/**
 * test.php
 *
 * Agent09 학습 관리 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent09LearningManagement
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/test.php
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
 * Agent09PersonaTest
 *
 * 학습 관리 에이전트 테스트 클래스
 */
class Agent09PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            9,                          // 에이전트 번호
            'learning_management',      // 에이전트 이름
            '학습 관리',                 // 에이전트 한글명
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
            'engine/Agent09PersonaEngine.php' => 'Agent09PersonaEngine 메인 클래스',
            'engine/Agent09DataContext.php' => '데이터 컨텍스트 클래스',
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
            'mdl_at_agent_messages' => '에이전트 간 메시지 테이블'
        ];
    }

    /**
     * Agent09 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent09PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 상황 코드 (situationCodes) 테스트
        $this->testSituationCodes();

        // 3. 패턴 유형 (patternTypes) 테스트
        $this->testPatternTypes();

        // 4. 5대 핵심 지표 테스트
        $this->testCoreIndicators();

        // 5. AbstractPersonaEngine 상속 테스트
        $this->testAbstractPersonaEngineInheritance();

        // 6. DataContext 테스트
        $this->testDataContext();

        // 7. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 8. rules.yaml 구조 테스트
        $this->testRulesYaml();
    }

    /**
     * Agent09PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent09PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent09PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent09PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'Agent09PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent09PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent09PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 상황 코드 테스트
     */
    private function testSituationCodes(): void
    {
        $expectedCodes = [
            'data_collection',
            'dropout_risk_low',
            'dropout_risk_medium',
            'dropout_risk_high',
            'routine_stable',
            'routine_adjustment',
            'math_weakness',
            'goal_achievement_low',
            'pomodoro_incomplete',
            'attendance_decline',
            'test_performance_drop',
            'positive_progress',
            'default'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasSituationCodes = strpos($content, 'situationCodes') !== false;

            $this->recordTest(
                '상황 코드 프로퍼티',
                $hasSituationCodes,
                $hasSituationCodes ? 'situationCodes 프로퍼티 정의됨' : 'situationCodes 없음'
            );

            $foundCodes = 0;
            foreach ($expectedCodes as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundCodes++;
                }
            }

            $this->recordTest(
                '상황 코드 완성도',
                $foundCodes >= 10,
                "{$foundCodes}/" . count($expectedCodes) . " 상황 코드 정의됨",
                ['expected' => $expectedCodes, 'found' => $foundCodes]
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
     * 패턴 유형 테스트
     */
    private function testPatternTypes(): void
    {
        $expectedTypes = [
            'data_sparse',
            'data_imbalanced',
            'pattern_unstable',
            'automation_resistant',
            'dropout_risk',
            'high_achiever'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasPatternTypes = strpos($content, 'patternTypes') !== false;

            $this->recordTest(
                '패턴 유형 프로퍼티',
                $hasPatternTypes,
                $hasPatternTypes ? 'patternTypes 프로퍼티 정의됨' : 'patternTypes 없음'
            );

            $foundTypes = 0;
            foreach ($expectedTypes as $type) {
                if (strpos($content, "'" . $type . "'") !== false ||
                    strpos($content, '"' . $type . '"') !== false) {
                    $foundTypes++;
                }
            }

            $this->recordTest(
                '패턴 유형 완성도',
                $foundTypes >= 5,
                "{$foundTypes}/" . count($expectedTypes) . " 패턴 유형 정의됨"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '패턴 유형 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 5대 핵심 지표 테스트
     */
    private function testCoreIndicators(): void
    {
        // 출결, 목표, 포모도로, 오답노트, 시험
        $expectedIndicators = [
            'attendance' => '출결',
            'goal' => '목표',
            'pomodoro' => '포모도로',
            'error_note' => '오답노트',
            'test' => '시험'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            $foundIndicators = 0;
            foreach ($expectedIndicators as $key => $name) {
                if (stripos($content, $key) !== false ||
                    strpos($content, $name) !== false) {
                    $foundIndicators++;
                }
            }

            $this->recordTest(
                '5대 핵심 지표 참조',
                $foundIndicators >= 4,
                "{$foundIndicators}/5 핵심 지표 참조 발견"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '핵심 지표 테스트',
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
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 필수 메서드 구현 확인
            $hasIdentifyPersona = strpos($content, 'identifyPersona') !== false;
            $hasGenerateResponse = strpos($content, 'generateResponse') !== false;
            $hasInitialize = strpos($content, 'initialize') !== false;

            $this->recordTest(
                'identifyPersona 메서드',
                $hasIdentifyPersona,
                $hasIdentifyPersona ? 'identifyPersona 메서드 구현됨' : 'identifyPersona 없음'
            );

            $this->recordTest(
                'generateResponse 메서드',
                $hasGenerateResponse,
                $hasGenerateResponse ? 'generateResponse 메서드 구현됨' : 'generateResponse 없음'
            );

            $this->recordTest(
                'initialize 메서드',
                $hasInitialize,
                $hasInitialize ? 'initialize 메서드 구현됨' : 'initialize 없음'
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
     * DataContext 테스트
     */
    private function testDataContext(): void
    {
        $filePath = __DIR__ . '/engine/Agent09DataContext.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent09DataContext 클래스',
                false,
                '파일 없음: engine/Agent09DataContext.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent09DataContext') !== false;
            $hasCollect = strpos($content, 'collect') !== false;

            $this->recordTest(
                'Agent09DataContext 클래스',
                $hasClass,
                $hasClass ? 'Agent09DataContext 클래스 정의됨' : '클래스 없음'
            );

            $this->recordTest(
                'DataContext collect 메서드',
                $hasCollect,
                $hasCollect ? 'collect 메서드 정의됨' : 'collect 메서드 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'DataContext 테스트',
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

            // 규칙 구조 확인
            $hasRules = strpos($content, 'rules:') !== false ||
                       strpos($content, 'situation_rules:') !== false ||
                       strpos($content, 'conditions:') !== false;

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
    $test = new Agent09PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/test.php?format=json
 *
 * =========================================================================
 */
