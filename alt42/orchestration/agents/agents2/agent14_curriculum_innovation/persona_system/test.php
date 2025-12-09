<?php
/**
 * test.php
 *
 * Agent14 커리큘럼 혁신 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent14CurriculumInnovation
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test.php
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
 * Agent14PersonaTest
 *
 * 커리큘럼 혁신 에이전트 테스트 클래스
 */
class Agent14PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            14,                         // 에이전트 번호
            'curriculum_innovation',    // 에이전트 이름
            '커리큘럼 혁신',              // 에이전트 한글명
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
            'engine/Agent14PersonaEngine.php' => 'Agent14PersonaEngine 메인 클래스',
            'engine/impl/Agent14RuleParser.php' => '규칙 파서 구현',
            'engine/impl/Agent14ConditionEvaluator.php' => '조건 평가기 구현',
            'engine/impl/Agent14ActionExecutor.php' => '액션 실행기 구현',
            'engine/impl/Agent14DataContext.php' => '데이터 컨텍스트 구현',
            'engine/impl/Agent14ResponseGenerator.php' => '응답 생성기 구현',
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
     * Agent14 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent14PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 상황 코드 (C1-C5) 테스트
        $this->testSituationCodes();

        // 3. 컴포넌트 구현 테스트
        $this->testComponentImplementations();

        // 4. AgentCommunicator 연동 테스트
        $this->testAgentCommunicator();

        // 5. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 6. rules.yaml 구조 테스트
        $this->testRulesYaml();

        // 7. templates 존재 테스트
        $this->testTemplates();
    }

    /**
     * Agent14PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent14PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent14PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent14PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'Agent14PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent14PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent14PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 상황 코드 (C1-C5) 테스트
     */
    private function testSituationCodes(): void
    {
        $expectedCodes = [
            'C1' => 'Curriculum Analysis (커리큘럼 분석)',
            'C2' => 'Content Design (콘텐츠 설계)',
            'C3' => 'Pedagogy Innovation (교수법 혁신)',
            'C4' => 'Assessment Design (평가 설계)',
            'C5' => 'Application & Feedback (적용 및 피드백)'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasSituationCodes = strpos($content, 'SITUATION_CODES') !== false ||
                                 strpos($content, 'situationCodes') !== false;

            $this->recordTest(
                '상황 코드 상수',
                $hasSituationCodes,
                $hasSituationCodes ? 'SITUATION_CODES 정의됨' : 'SITUATION_CODES 없음'
            );

            $foundCodes = 0;
            foreach (array_keys($expectedCodes) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundCodes++;
                }
            }

            $this->recordTest(
                '상황 코드 C1-C5 정의',
                $foundCodes >= 5,
                $foundCodes >= 5
                    ? 'C1-C5 모든 상황 코드 정의됨'
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
     * 컴포넌트 구현 테스트
     */
    private function testComponentImplementations(): void
    {
        $components = [
            'impl/Agent14RuleParser.php' => 'Agent14RuleParser',
            'impl/Agent14ConditionEvaluator.php' => 'Agent14ConditionEvaluator',
            'impl/Agent14ActionExecutor.php' => 'Agent14ActionExecutor',
            'impl/Agent14DataContext.php' => 'Agent14DataContext',
            'impl/Agent14ResponseGenerator.php' => 'Agent14ResponseGenerator'
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
     * AgentCommunicator 연동 테스트
     */
    private function testAgentCommunicator(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent14PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasCommunicator = strpos($content, 'AgentCommunicator') !== false ||
                               strpos($content, 'communicator') !== false;
            $hasPublish = strpos($content, 'publish') !== false ||
                          strpos($content, 'broadcast') !== false;

            $this->recordTest(
                'AgentCommunicator 연동',
                $hasCommunicator,
                $hasCommunicator ? 'AgentCommunicator 참조 발견' : 'AgentCommunicator 없음'
            );

            $this->recordTest(
                '메시지 발행 기능',
                $hasPublish,
                $hasPublish ? '메시지 발행 기능 발견' : '메시지 발행 없음'
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
                       strpos($content, 'situation_rules:') !== false;

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
     * templates 존재 테스트
     */
    private function testTemplates(): void
    {
        $templatesPath = __DIR__ . '/templates';

        if (!is_dir($templatesPath)) {
            // engine/templates도 확인
            $templatesPath = __DIR__ . '/engine/templates';
        }

        $exists = is_dir($templatesPath);

        $this->recordTest(
            'templates 디렉토리',
            $exists,
            $exists ? 'templates 디렉토리 존재' : 'templates 디렉토리 없음'
        );

        if ($exists) {
            $files = glob($templatesPath . '/*');
            $fileCount = count($files);

            $this->recordTest(
                'templates 파일',
                $fileCount > 0,
                "{$fileCount}개 템플릿 파일 존재"
            );
        }
    }
}

// =========================================================================
// 테스트 실행
// =========================================================================

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent14PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent14_curriculum_innovation/persona_system/test.php?format=json
 *
 * =========================================================================
 */
