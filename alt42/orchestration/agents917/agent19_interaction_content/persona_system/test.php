<?php
/**
 * test.php
 *
 * Agent19 상호작용 콘텐츠 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent19InteractionContent
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent19_interaction_content/persona_system/test.php
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
 * Agent19PersonaTest
 *
 * 상호작용 콘텐츠 에이전트 테스트 클래스
 */
class Agent19PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            19,                          // 에이전트 번호
            'interaction_content',       // 에이전트 이름
            '상호작용 콘텐츠',            // 에이전트 한글명
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
            'engine/PersonaEngine.php' => 'Agent19PersonaEngine 메인 클래스',
            'engine/ContextAnalyzer.php' => '컨텍스트 분석기 클래스',
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
            'mdl_agent19_persona_state' => '페르소나 상태 테이블',
            'mdl_agent19_persona_history' => '페르소나 전환 이력 테이블'
        ];
    }

    /**
     * Agent19 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent19PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 인지적 페르소나 (C1-C6) 테스트
        $this->testCognitivePersonas();

        // 3. 행동적 페르소나 (B1-B6) 테스트
        $this->testBehavioralPersonas();

        // 4. 감정적 페르소나 (E1-E6) 테스트
        $this->testEmotionalPersonas();

        // 5. 3차원 복합 페르소나 테스트
        $this->testCompositePersona();

        // 6. ContextAnalyzer 테스트
        $this->testContextAnalyzer();

        // 7. 상호작용 유형 추천 테스트
        $this->testInteractionRecommendation();

        // 8. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 9. rules.yaml 구조 테스트
        $this->testRulesYaml();
    }

    /**
     * Agent19PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent19PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent19PersonaEngine') !== false;
            $hasDetectPersona = strpos($content, 'detectPersona') !== false;

            $this->recordTest(
                'Agent19PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent19PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'detectPersona 메서드',
                $hasDetectPersona,
                $hasDetectPersona ? 'detectPersona 메서드 정의됨' : 'detectPersona 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent19PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 인지적 페르소나 (C1-C6) 테스트
     */
    private function testCognitivePersonas(): void
    {
        $expectedPersonas = [
            'C1' => '활성 인지 (Active Cognition)',
            'C2' => '피로 인지 (Fatigued Cognition)',
            'C3' => '개념 지향 (Concept Oriented)',
            'C4' => '문제 해결 (Problem Solving)',
            'C5' => '패턴 인식 (Pattern Recognition)',
            'C6' => '추론 지향 (Reasoning Oriented)'
        ];

        try {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasCognitive = strpos($content, "'cognitive'") !== false ||
                            strpos($content, 'cognitive') !== false;

            $this->recordTest(
                '인지적 페르소나 차원',
                $hasCognitive,
                $hasCognitive ? 'cognitive 차원 정의됨' : 'cognitive 차원 없음'
            );

            $foundPersonas = 0;
            foreach (array_keys($expectedPersonas) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundPersonas++;
                }
            }

            $this->recordTest(
                '인지적 페르소나 C1-C6 정의',
                $foundPersonas >= 6,
                $foundPersonas >= 6
                    ? 'C1-C6 모든 인지적 페르소나 정의됨'
                    : "{$foundPersonas}/6 인지적 페르소나 발견",
                ['expected' => array_keys($expectedPersonas), 'found' => $foundPersonas]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '인지적 페르소나 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 행동적 페르소나 (B1-B6) 테스트
     */
    private function testBehavioralPersonas(): void
    {
        $expectedPersonas = [
            'B1' => '적극적 참여자 (Active Engager)',
            'B2' => '수동적 관찰자 (Passive Observer)',
            'B3' => '즉흥적 학습자 (Spontaneous Learner)',
            'B4' => '신중한 학습자 (Deliberate Learner)',
            'B5' => '지속 몰입형 (Sustained Flow Learner)',
            'B6' => '간헐적 학습자 (Intermittent Learner)'
        ];

        try {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasBehavioral = strpos($content, "'behavioral'") !== false ||
                             strpos($content, 'behavioral') !== false;

            $this->recordTest(
                '행동적 페르소나 차원',
                $hasBehavioral,
                $hasBehavioral ? 'behavioral 차원 정의됨' : 'behavioral 차원 없음'
            );

            $foundPersonas = 0;
            foreach (array_keys($expectedPersonas) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundPersonas++;
                }
            }

            $this->recordTest(
                '행동적 페르소나 B1-B6 정의',
                $foundPersonas >= 6,
                $foundPersonas >= 6
                    ? 'B1-B6 모든 행동적 페르소나 정의됨'
                    : "{$foundPersonas}/6 행동적 페르소나 발견",
                ['expected' => array_keys($expectedPersonas), 'found' => $foundPersonas]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '행동적 페르소나 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 감정적 페르소나 (E1-E6) 테스트
     */
    private function testEmotionalPersonas(): void
    {
        $expectedPersonas = [
            'E1' => '자신감 상태 (Confident State)',
            'E2' => '불안 상태 (Anxious State)',
            'E3' => '권태 상태 (Bored State)',
            'E4' => '도전 상태 (Challenged State)',
            'E5' => '좌절 상태 (Frustrated State)',
            'E6' => '안정 상태 (Stable State)'
        ];

        try {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasEmotional = strpos($content, "'emotional'") !== false ||
                            strpos($content, 'emotional') !== false;

            $this->recordTest(
                '감정적 페르소나 차원',
                $hasEmotional,
                $hasEmotional ? 'emotional 차원 정의됨' : 'emotional 차원 없음'
            );

            $foundPersonas = 0;
            foreach (array_keys($expectedPersonas) as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundPersonas++;
                }
            }

            $this->recordTest(
                '감정적 페르소나 E1-E6 정의',
                $foundPersonas >= 6,
                $foundPersonas >= 6
                    ? 'E1-E6 모든 감정적 페르소나 정의됨'
                    : "{$foundPersonas}/6 감정적 페르소나 발견",
                ['expected' => array_keys($expectedPersonas), 'found' => $foundPersonas]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '감정적 페르소나 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 3차원 복합 페르소나 테스트
     */
    private function testCompositePersona(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 복합 페르소나 코드 (예: C1-B1-E1)
            $hasComposite = strpos($content, 'composite') !== false;

            $this->recordTest(
                '복합 페르소나 코드',
                $hasComposite,
                $hasComposite ? 'composite 페르소나 코드 사용' : 'composite 없음'
            );

            // 3차원 탐지 메서드
            $hasCognitiveDetect = strpos($content, 'detectCognitivePersona') !== false;
            $hasBehavioralDetect = strpos($content, 'detectBehavioralPersona') !== false;
            $hasEmotionalDetect = strpos($content, 'detectEmotionalPersona') !== false;

            $this->recordTest(
                '3차원 페르소나 탐지 메서드',
                $hasCognitiveDetect && $hasBehavioralDetect && $hasEmotionalDetect,
                ($hasCognitiveDetect && $hasBehavioralDetect && $hasEmotionalDetect)
                    ? '3차원 탐지 메서드 모두 정의됨'
                    : '일부 탐지 메서드 없음'
            );

            // AI 신뢰도 임계값
            $hasAIThreshold = strpos($content, 'AI_CONFIDENCE_THRESHOLD') !== false ||
                              strpos($content, 'needs_ai_enhancement') !== false;

            $this->recordTest(
                'AI 강화 임계값',
                $hasAIThreshold,
                $hasAIThreshold ? 'AI 신뢰도 임계값 정의됨' : 'AI 임계값 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '복합 페르소나 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * ContextAnalyzer 테스트
     */
    private function testContextAnalyzer(): void
    {
        $filePath = __DIR__ . '/engine/ContextAnalyzer.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'ContextAnalyzer 클래스',
                false,
                '파일 없음: engine/ContextAnalyzer.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class ContextAnalyzer') !== false;

            $this->recordTest(
                'ContextAnalyzer 클래스',
                $hasClass,
                $hasClass ? 'ContextAnalyzer 클래스 정의됨' : 'ContextAnalyzer 클래스 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'ContextAnalyzer 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 상호작용 유형 추천 테스트
     */
    private function testInteractionRecommendation(): void
    {
        try {
            $filePath = __DIR__ . '/engine/PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 상호작용 유형 추천 메서드
            $hasGetRecommendation = strpos($content, 'getRecommendedInteraction') !== false;

            $this->recordTest(
                'getRecommendedInteraction 메서드',
                $hasGetRecommendation,
                $hasGetRecommendation ? 'getRecommendedInteraction 메서드 정의됨' : 'getRecommendedInteraction 없음'
            );

            // 상호작용 유형 코드 (I1-I7)
            $interactionTypes = ['I1', 'I2', 'I3', 'I4', 'I5', 'I6', 'I7'];
            $foundTypes = 0;
            foreach ($interactionTypes as $type) {
                if (strpos($content, "'" . $type . "'") !== false ||
                    strpos($content, '"' . $type . '"') !== false) {
                    $foundTypes++;
                }
            }

            $this->recordTest(
                '상호작용 유형 코드',
                $foundTypes >= 5,
                $foundTypes >= 5
                    ? "{$foundTypes}/7 상호작용 유형 코드 정의됨"
                    : "상호작용 유형 코드 부족: {$foundTypes}/7"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '상호작용 추천 테스트',
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
                       strpos($content, 'persona_rules:') !== false ||
                       strpos($content, 'interaction_rules:') !== false;

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
    $test = new Agent19PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent19_interaction_content/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent19_interaction_content/persona_system/test.php?format=json
 *
 * =========================================================================
 */
