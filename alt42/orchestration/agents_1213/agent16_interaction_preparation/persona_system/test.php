<?php
/**
 * test.php
 *
 * Agent16 상호작용 준비 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent16InteractionPreparation
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/persona_system/test.php
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
 * Agent16PersonaTest
 *
 * 상호작용 준비 에이전트 테스트 클래스
 */
class Agent16PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            16,                            // 에이전트 번호
            'interaction_preparation',     // 에이전트 이름
            '상호작용 준비',                 // 에이전트 한글명
            __DIR__                        // 기본 경로 (persona_system)
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
            'engine/Agent16PersonaEngine.php' => 'Agent16PersonaEngine 메인 클래스',
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
     * Agent16 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent16PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 9개 세계관 (Worldviews) 테스트
        $this->testWorldviews();

        // 3. AgentCommunicator 연동 테스트
        $this->testAgentCommunicator();

        // 4. AgentStateSync 연동 테스트
        $this->testAgentStateSync();

        // 5. 스토리텔링 테마 구조 테스트
        $this->testStorytellingThemes();

        // 6. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 7. personas 디렉토리 테스트
        $this->testPersonasDirectory();

        // 8. rules.yaml 구조 테스트
        $this->testRulesYaml();
    }

    /**
     * Agent16PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent16PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent16PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent16PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent16PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'Agent16PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent16PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent16PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 9개 세계관 (Worldviews) 테스트
     */
    private function testWorldviews(): void
    {
        $expectedWorldviews = [
            'curriculum',         // 커리큘럼 기반
            'personalized',       // 개인화
            'exam_prep',          // 시험 준비
            'short_mission',      // 짧은 미션
            'self_reflection',    // 자기 성찰
            'self_directed',      // 자기주도
            'apprenticeship',     // 도제 학습
            'time_reflection',    // 시간 성찰
            'inquiry_learning'    // 탐구 학습
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent16PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasWorldviews = strpos($content, 'worldviews') !== false ||
                             strpos($content, 'WORLDVIEWS') !== false;

            $this->recordTest(
                '세계관 프로퍼티',
                $hasWorldviews,
                $hasWorldviews ? 'worldviews 프로퍼티 정의됨' : 'worldviews 없음'
            );

            $foundWorldviews = 0;
            foreach ($expectedWorldviews as $worldview) {
                if (strpos($content, "'" . $worldview . "'") !== false ||
                    strpos($content, '"' . $worldview . '"') !== false) {
                    $foundWorldviews++;
                }
            }

            $this->recordTest(
                '9개 세계관 정의',
                $foundWorldviews >= 8,
                $foundWorldviews >= 8
                    ? '9개 세계관 대부분 정의됨'
                    : "{$foundWorldviews}/9 세계관 발견",
                ['expected' => $expectedWorldviews, 'found' => $foundWorldviews]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '세계관 테스트',
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
            $filePath = __DIR__ . '/engine/Agent16PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasCommunicator = strpos($content, 'AgentCommunicator') !== false ||
                               strpos($content, 'communicator') !== false;

            $this->recordTest(
                'AgentCommunicator 연동',
                $hasCommunicator,
                $hasCommunicator ? 'AgentCommunicator 참조 발견' : 'AgentCommunicator 없음'
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
     * AgentStateSync 연동 테스트
     */
    private function testAgentStateSync(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent16PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasStateSync = strpos($content, 'AgentStateSync') !== false ||
                            strpos($content, 'stateSync') !== false ||
                            strpos($content, 'syncState') !== false;

            $this->recordTest(
                'AgentStateSync 연동',
                $hasStateSync,
                $hasStateSync ? 'AgentStateSync 참조 발견' : 'AgentStateSync 없음 (선택적)'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'AgentStateSync 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 스토리텔링 테마 구조 테스트
     */
    private function testStorytellingThemes(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent16PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasThemes = strpos($content, 'theme') !== false ||
                         strpos($content, 'storytelling') !== false ||
                         strpos($content, 'narrative') !== false;

            $this->recordTest(
                '스토리텔링 테마 구조',
                $hasThemes,
                $hasThemes ? '스토리텔링/테마 참조 발견' : '스토리텔링 구조 없음 (선택적)'
            );

            // 학습 테마 참조 확인
            $hasLearningTheme = strpos($content, 'learning') !== false ||
                                strpos($content, '학습') !== false;

            $this->recordTest(
                '학습 테마 참조',
                $hasLearningTheme,
                $hasLearningTheme ? '학습 관련 테마 발견' : '학습 테마 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '스토리텔링 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * personas 디렉토리 테스트
     */
    private function testPersonasDirectory(): void
    {
        $personasPath = __DIR__ . '/personas';

        if (!is_dir($personasPath)) {
            $this->recordTest(
                'personas 디렉토리',
                false,
                'personas 디렉토리 없음 (선택적)'
            );
            return;
        }

        $files = glob($personasPath . '/*');
        $fileCount = count($files);

        $this->recordTest(
            'personas 디렉토리',
            true,
            "personas 디렉토리 존재 ({$fileCount}개 파일)"
        );
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
                       strpos($content, 'worldview_rules:') !== false;

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
    $test = new Agent16PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent16_interaction_preparation/persona_system/test.php?format=json
 *
 * =========================================================================
 */
