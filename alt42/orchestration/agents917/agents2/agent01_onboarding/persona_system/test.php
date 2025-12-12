<?php
/**
 * test.php
 *
 * Agent01 온보딩 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent01Onboarding
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test.php
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
 * Agent01PersonaTest
 *
 * 온보딩 에이전트 테스트 클래스
 * - 48개 페르소나 (9개 상황 × 6개 유형)
 * - NLU 분석기 통합
 * - 페르소나 전환 관리자
 * - 응답 생성기
 */
class Agent01PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            1,                  // 에이전트 번호
            'onboarding',       // 에이전트 이름
            '온보딩',            // 에이전트 한글명
            __DIR__             // 기본 경로 (persona_system)
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
            'engine/Agent01PersonaEngine.php' => 'Agent01PersonaEngine 메인 클래스',
            'engine/PersonaRuleEngine.php' => 'PersonaRuleEngine 레거시 엔진',
            'engine/NLUAnalyzer.php' => 'NLU 분석기 클래스',
            'engine/PersonaTransitionManager.php' => '페르소나 전환 관리자',
            'engine/ResponseGenerator.php' => '응답 생성기 클래스',
            'engine/DataContext.php' => '데이터 컨텍스트 클래스',
            'engine/RuleParser.php' => '규칙 파서 클래스',
            'engine/ConditionEvaluator.php' => '조건 평가기 클래스',
            'engine/ActionExecutor.php' => '액션 실행기 클래스',
            'engine/RuleCache.php' => '규칙 캐시 클래스',
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
            'mdl_at_agent_messages' => '에이전트 간 메시지 테이블',
            'augmented_teacher_personas' => '레거시 페르소나 기록 테이블',
            'augmented_teacher_sessions' => '레거시 세션 테이블'
        ];
    }

    /**
     * Agent01 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent01PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. AbstractPersonaEngine 상속 테스트
        $this->testAbstractPersonaEngineInheritance();

        // 3. 48개 페르소나 정의 테스트
        $this->testPersonaDefinitions();

        // 4. 9개 상황 코드 테스트
        $this->testSituationCodes();

        // 5. NLU 분석기 테스트
        $this->testNLUAnalyzer();

        // 6. 페르소나 전환 관리자 테스트
        $this->testPersonaTransitionManager();

        // 7. 응답 생성기 테스트
        $this->testResponseGenerator();

        // 8. 엔진 컴포넌트 테스트
        $this->testEngineComponents();

        // 9. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 10. rules.yaml 구조 테스트
        $this->testRulesYaml();

        // 11. templates 디렉토리 테스트
        $this->testTemplatesDirectory();
    }

    /**
     * Agent01PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent01PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent01PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent01PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent01PersonaEngine') !== false;
            $hasProcess = strpos($content, 'function process') !== false;

            $this->recordTest(
                'Agent01PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent01PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'process 메서드',
                $hasProcess,
                $hasProcess ? 'process 메서드 정의됨' : 'process 메서드 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent01PersonaEngine 클래스 로드',
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
            $filePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            $content = file_get_contents($filePath);

            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

            // 필수 추상 메서드 구현 확인
            $abstractMethods = [
                'onInitialize' => 'onInitialize 메서드',
                'doIdentifyPersona' => 'doIdentifyPersona 메서드',
                'doGenerateResponse' => 'doGenerateResponse 메서드',
                'onTransition' => 'onTransition 메서드',
                'doHealthCheck' => 'doHealthCheck 메서드',
                'getRulesFilePath' => 'getRulesFilePath 메서드'
            ];

            $implementedCount = 0;
            foreach ($abstractMethods as $method => $label) {
                $hasMethod = strpos($content, 'function ' . $method) !== false;
                if ($hasMethod) {
                    $implementedCount++;
                }
            }

            $this->recordTest(
                '추상 메서드 구현',
                $implementedCount >= 6,
                $implementedCount >= 6
                    ? '6개 추상 메서드 모두 구현됨'
                    : "{$implementedCount}/6 추상 메서드 구현됨"
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
     * 48개 페르소나 정의 테스트
     */
    private function testPersonaDefinitions(): void
    {
        // S0-S5, C, Q, E 상황별 페르소나 (각 6개씩 = 9 × 6 = 54)
        // 실제로는 48개 정의됨
        $expectedPersonas = [
            // S0: 수학 특화 정보 수집
            'S0_P1', 'S0_P2', 'S0_P3', 'S0_P4', 'S0_P5', 'S0_P6',
            // S1: 신규 학생 등록
            'S1_P1', 'S1_P2', 'S1_P3', 'S1_P4', 'S1_P5', 'S1_P6',
            // S2: 수업 전 학습 설계
            'S2_P1', 'S2_P2', 'S2_P3', 'S2_P4', 'S2_P5', 'S2_P6',
            // S3: 진도 판단
            'S3_P1', 'S3_P2', 'S3_P3', 'S3_P4', 'S3_P5', 'S3_P6',
            // S4: 학부모 상담
            'S4_P1', 'S4_P2', 'S4_P3', 'S4_P4', 'S4_P5', 'S4_P6',
            // S5: 장기 목표
            'S5_P1', 'S5_P2', 'S5_P3', 'S5_P4', 'S5_P5', 'S5_P6',
            // C: 복합 상황
            'C_P1', 'C_P2', 'C_P3', 'C_P4', 'C_P5', 'C_P6',
            // Q: 포괄형 질문
            'Q_P1', 'Q_P2', 'Q_P3', 'Q_P4', 'Q_P5', 'Q_P6'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            $content = file_get_contents($filePath);

            // PERSONA_NAMES 상수 확인
            $hasPersonaNames = strpos($content, 'PERSONA_NAMES') !== false;

            $this->recordTest(
                'PERSONA_NAMES 상수',
                $hasPersonaNames,
                $hasPersonaNames ? 'PERSONA_NAMES 상수 정의됨' : 'PERSONA_NAMES 상수 없음'
            );

            // 개별 페르소나 코드 확인
            $foundPersonas = 0;
            foreach ($expectedPersonas as $personaCode) {
                if (strpos($content, "'" . $personaCode . "'") !== false ||
                    strpos($content, '"' . $personaCode . '"') !== false) {
                    $foundPersonas++;
                }
            }

            $this->recordTest(
                '페르소나 정의 (48개)',
                $foundPersonas >= 48,
                $foundPersonas >= 48
                    ? '48개 페르소나 모두 정의됨'
                    : "{$foundPersonas}/48 페르소나 발견",
                ['expected' => 48, 'found' => $foundPersonas]
            );

            // E 상황 페르소나 추가 확인 (정서적 UX)
            $ePersonas = ['E_P1', 'E_P2', 'E_P3', 'E_P4', 'E_P5', 'E_P6'];
            $foundE = 0;
            foreach ($ePersonas as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundE++;
                }
            }

            $this->recordTest(
                '정서적 UX 페르소나 (E_P1-P6)',
                $foundE >= 6,
                $foundE >= 6 ? 'E_P1-P6 모두 정의됨' : "{$foundE}/6 정서적 페르소나 발견"
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
     * 9개 상황 코드 테스트
     */
    private function testSituationCodes(): void
    {
        $expectedSituations = [
            'S0' => '수학 특화 정보 수집',
            'S1' => '신규 학생 등록',
            'S2' => '수업 전 학습 설계',
            'S3' => '진도 판단',
            'S4' => '학부모 상담',
            'S5' => '장기 목표',
            'C' => '복합 상황',
            'Q' => '포괄형 질문',
            'E' => '정서적 UX'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            $content = file_get_contents($filePath);

            // SITUATION_CODES 상수 확인
            $hasSituationCodes = strpos($content, 'SITUATION_CODES') !== false;

            $this->recordTest(
                'SITUATION_CODES 상수',
                $hasSituationCodes,
                $hasSituationCodes ? 'SITUATION_CODES 상수 정의됨' : 'SITUATION_CODES 상수 없음'
            );

            $foundSituations = 0;
            foreach (array_keys($expectedSituations) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundSituations++;
                }
            }

            $this->recordTest(
                '상황 코드 정의 (9개)',
                $foundSituations >= 9,
                $foundSituations >= 9
                    ? '9개 상황 코드 모두 정의됨'
                    : "{$foundSituations}/9 상황 코드 발견",
                ['expected' => array_keys($expectedSituations), 'found' => $foundSituations]
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
     * NLU 분석기 테스트
     */
    private function testNLUAnalyzer(): void
    {
        $filePath = __DIR__ . '/engine/NLUAnalyzer.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'NLUAnalyzer 클래스',
                false,
                '파일 없음: engine/NLUAnalyzer.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class NLUAnalyzer') !== false;
            $hasAnalyze = strpos($content, 'analyze') !== false;
            $hasExtract = strpos($content, 'extract') !== false;

            $this->recordTest(
                'NLUAnalyzer 클래스',
                $hasClass,
                $hasClass ? 'NLUAnalyzer 클래스 정의됨' : 'NLUAnalyzer 클래스 없음'
            );

            $this->recordTest(
                'NLU analyze 메서드',
                $hasAnalyze,
                $hasAnalyze ? 'analyze 메서드 정의됨' : 'analyze 메서드 없음'
            );

            // Agent01PersonaEngine에서 NLU 통합 확인
            $enginePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            $engineContent = file_get_contents($enginePath);
            $hasNLUIntegration = strpos($engineContent, 'NLUAnalyzer') !== false;

            $this->recordTest(
                'NLU 통합',
                $hasNLUIntegration,
                $hasNLUIntegration ? 'Agent01PersonaEngine에 NLU 통합됨' : 'NLU 통합 없음'
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
     * 페르소나 전환 관리자 테스트
     */
    private function testPersonaTransitionManager(): void
    {
        $filePath = __DIR__ . '/engine/PersonaTransitionManager.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaTransitionManager 클래스',
                false,
                '파일 없음: engine/PersonaTransitionManager.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class PersonaTransitionManager') !== false;
            $hasTransition = strpos($content, 'transition') !== false;
            $hasPattern = strpos($content, 'pattern') !== false || strpos($content, 'Pattern') !== false;

            $this->recordTest(
                'PersonaTransitionManager 클래스',
                $hasClass,
                $hasClass ? 'PersonaTransitionManager 클래스 정의됨' : 'PersonaTransitionManager 클래스 없음'
            );

            $this->recordTest(
                'transition 기능',
                $hasTransition,
                $hasTransition ? 'transition 관련 메서드 정의됨' : 'transition 메서드 없음'
            );

            // Agent01PersonaEngine에서 TransitionManager 통합 확인
            $enginePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            $engineContent = file_get_contents($enginePath);
            $hasManagerIntegration = strpos($engineContent, 'PersonaTransitionManager') !== false;

            $this->recordTest(
                'TransitionManager 통합',
                $hasManagerIntegration,
                $hasManagerIntegration ? 'Agent01PersonaEngine에 TransitionManager 통합됨' : 'TransitionManager 통합 없음'
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
     * 응답 생성기 테스트
     */
    private function testResponseGenerator(): void
    {
        $filePath = __DIR__ . '/engine/ResponseGenerator.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'ResponseGenerator 클래스',
                false,
                '파일 없음: engine/ResponseGenerator.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class ResponseGenerator') !== false;
            $hasGenerate = strpos($content, 'generate') !== false;

            $this->recordTest(
                'ResponseGenerator 클래스',
                $hasClass,
                $hasClass ? 'ResponseGenerator 클래스 정의됨' : 'ResponseGenerator 클래스 없음'
            );

            $this->recordTest(
                'generate 메서드',
                $hasGenerate,
                $hasGenerate ? 'generate 메서드 정의됨' : 'generate 메서드 없음'
            );

            // Agent01PersonaEngine에서 ResponseGenerator 통합 확인
            $enginePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            $engineContent = file_get_contents($enginePath);
            $hasGeneratorIntegration = strpos($engineContent, 'ResponseGenerator') !== false;

            $this->recordTest(
                'ResponseGenerator 통합',
                $hasGeneratorIntegration,
                $hasGeneratorIntegration ? 'Agent01PersonaEngine에 ResponseGenerator 통합됨' : 'ResponseGenerator 통합 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'ResponseGenerator 테스트',
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
            'RuleCache.php' => 'RuleCache'
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

            // 구조 확인
            $hasRules = strpos($content, 'rules:') !== false ||
                       strpos($content, 'persona_rules:') !== false ||
                       strpos($content, 'situations:') !== false;

            $this->recordTest(
                'rules.yaml 구조',
                $hasRules,
                $hasRules ? 'YAML 규칙 구조 확인됨' : 'YAML 규칙 구조 확인 불가'
            );

            // 상황별 규칙 확인
            $hasSituationRules = strpos($content, 'S0') !== false &&
                                 strpos($content, 'S1') !== false;

            $this->recordTest(
                '상황별 규칙',
                $hasSituationRules,
                $hasSituationRules ? '상황별 규칙 정의됨' : '상황별 규칙 없음'
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
                'templates 디렉토리 없음'
            );
            return;
        }

        // 상황별 템플릿 디렉토리 확인
        $situationDirs = ['default', 'S0', 'S1', 'E'];
        $foundDirs = 0;

        foreach ($situationDirs as $dir) {
            if (is_dir($templatesPath . '/' . $dir)) {
                $foundDirs++;
            }
        }

        $this->recordTest(
            'templates 디렉토리',
            $foundDirs >= 2,
            "templates 디렉토리 존재 ({$foundDirs}개 하위 디렉토리)"
        );

        // 기본 템플릿 파일 확인
        $defaultTemplates = [
            'templates/default/welcome.txt',
            'templates/default/acknowledgment.txt',
            'templates/default/next_step.txt'
        ];

        $foundTemplates = 0;
        foreach ($defaultTemplates as $template) {
            if (file_exists(__DIR__ . '/' . $template)) {
                $foundTemplates++;
            }
        }

        $this->recordTest(
            '기본 템플릿 파일',
            $foundTemplates >= 3,
            $foundTemplates >= 3
                ? '기본 템플릿 파일 존재'
                : "{$foundTemplates}/3 기본 템플릿 발견"
        );
    }
}

// =========================================================================
// 테스트 실행
// =========================================================================

$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent01PersonaTest();
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
        echo "<h1>테스트 실행 실패</h1>";
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test.php?format=json
 *
 * 채팅 UI 테스트 (기존):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test_chat.php
 *
 * =========================================================================
 */
