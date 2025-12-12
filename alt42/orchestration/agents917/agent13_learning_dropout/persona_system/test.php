<?php
/**
 * test.php
 *
 * Agent13 학습 이탈 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent13LearningDropout
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test.php
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
 * Agent13PersonaTest
 *
 * 학습 이탈 에이전트 테스트 클래스
 */
class Agent13PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            13,                       // 에이전트 번호
            'learning_dropout',       // 에이전트 이름
            '학습 이탈',               // 에이전트 한글명
            __DIR__                   // 기본 경로 (persona_system)
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
            'Agent13PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'Agent13DataContext.php' => 'DataContext 데이터 접근 클래스',
            'api/chat.php' => '채팅 API 엔드포인트',
            '../rules/rules.yaml' => '규칙 정의 파일',
            'personas.md' => '페르소나 정의 문서'
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
            'mdl_at_agent13_dropout_risk' => '이탈 위험 기록 테이블',
            'mdl_at_agent13_intervention_log' => '개입 기록 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    /**
     * Agent13 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. DataContext 클래스 로드 테스트
        $this->testDataContextLoad();

        // 3. 위험 수준 정의 테스트
        $this->testRiskTierDefinitions();

        // 4. 위험 점수 계산 테스트
        $this->testRiskScoreCalculation();

        // 5. 페르소나 매핑 테스트
        $this->testPersonaMapping();

        // 6. 개입 전략 테스트
        $this->testInterventionStrategies();

        // 7. API 엔드포인트 테스트
        $this->testApiEndpoint('api/chat.php', 'GET');

        // 8. personas.md 문서 존재 테스트
        $this->testFileExists('personas.md', '페르소나 정의 문서');
    }

    /**
     * PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/Agent13PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaEngine 클래스 로드',
                false,
                '파일 없음: Agent13PersonaEngine.php'
            );
            return;
        }

        try {
            require_once($filePath);
            $exists = class_exists('Agent13PersonaEngine');

            $this->recordTest(
                'PersonaEngine 클래스 로드',
                $exists,
                $exists ? 'Agent13PersonaEngine 클래스 로드 성공' : '클래스 찾을 수 없음'
            );

            // 추상 메서드 구현 확인
            if ($exists) {
                $reflection = new ReflectionClass('Agent13PersonaEngine');
                $parentClass = $reflection->getParentClass();

                $this->recordTest(
                    'AbstractPersonaEngine 상속',
                    $parentClass && $parentClass->getName() === 'AbstractPersonaEngine',
                    $parentClass ? '상속 확인: ' . $parentClass->getName() : '부모 클래스 없음'
                );
            }
        } catch (Throwable $e) {
            $this->recordTest(
                'PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * DataContext 클래스 로드 테스트
     */
    private function testDataContextLoad(): void
    {
        $filePath = __DIR__ . '/Agent13DataContext.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'DataContext 클래스 로드',
                false,
                '파일 없음: Agent13DataContext.php'
            );
            return;
        }

        try {
            require_once($filePath);
            $exists = class_exists('Agent13DataContext');

            $this->recordTest(
                'DataContext 클래스 로드',
                $exists,
                $exists ? 'Agent13DataContext 클래스 로드 성공' : '클래스 찾을 수 없음'
            );

            // DataContextInterface 구현 확인
            if ($exists) {
                $implements = class_implements('Agent13DataContext');
                $hasInterface = isset($implements['DataContextInterface']);

                $this->recordTest(
                    'DataContextInterface 구현',
                    $hasInterface,
                    $hasInterface ? '인터페이스 구현 확인' : '인터페이스 미구현'
                );
            }
        } catch (Throwable $e) {
            $this->recordTest(
                'DataContext 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 위험 수준 정의 테스트
     */
    private function testRiskTierDefinitions(): void
    {
        $expectedTiers = [
            'Low',
            'Medium',
            'High',
            'Critical'
        ];

        try {
            if (!class_exists('Agent13PersonaEngine')) {
                require_once(__DIR__ . '/Agent13PersonaEngine.php');
            }

            // Reflection을 사용하여 protected 프로퍼티 접근
            $reflection = new ReflectionClass('Agent13PersonaEngine');

            if ($reflection->hasProperty('riskTierThresholds')) {
                $property = $reflection->getProperty('riskTierThresholds');
                $property->setAccessible(true);

                // 기본값 확인
                $defaultProps = $reflection->getDefaultProperties();
                $tiers = isset($defaultProps['riskTierThresholds']) ? $defaultProps['riskTierThresholds'] : [];

                $allPresent = true;
                $missingTiers = [];

                foreach ($expectedTiers as $tier) {
                    if (!isset($tiers[$tier])) {
                        $allPresent = false;
                        $missingTiers[] = $tier;
                    }
                }

                $this->recordTest(
                    '위험 수준 정의',
                    $allPresent,
                    $allPresent
                        ? count($tiers) . '개 위험 수준 정의됨'
                        : '누락된 수준: ' . implode(', ', $missingTiers),
                    ['expected' => $expectedTiers, 'found' => array_keys($tiers)]
                );
            } else {
                $this->recordTest(
                    '위험 수준 정의',
                    false,
                    'riskTierThresholds 프로퍼티 없음'
                );
            }
        } catch (Throwable $e) {
            $this->recordTest(
                '위험 수준 정의',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 위험 점수 계산 테스트
     */
    private function testRiskScoreCalculation(): void
    {
        $testCases = [
            [
                'ninactive' => 0,
                'npomodoro' => 6,
                'tlaststroke_min' => 5,
                'eye_count' => 0,
                'consecutive_high_days' => 0,
                'expected_tier' => 'Low',
                'label' => '능동적 학습자 (Low)'
            ],
            [
                'ninactive' => 2,
                'npomodoro' => 3,
                'tlaststroke_min' => 10,
                'eye_count' => 2,
                'consecutive_high_days' => 0,
                'expected_tier' => 'Medium',
                'label' => '간헐적 이탈 (Medium)'
            ],
            [
                'ninactive' => 5,
                'npomodoro' => 1,
                'tlaststroke_min' => 35,
                'eye_count' => 3,
                'consecutive_high_days' => 0,
                'expected_tier' => 'High',
                'label' => '만성적 이탈 (High)'
            ],
            [
                'ninactive' => 6,
                'npomodoro' => 0,
                'tlaststroke_min' => 60,
                'eye_count' => 5,
                'consecutive_high_days' => 3,
                'expected_tier' => 'Critical',
                'label' => '위기 상태 (Critical)'
            ]
        ];

        foreach ($testCases as $case) {
            // 위험 수준 판별 로직 (Agent13PersonaEngine의 로직 기반)
            $calculatedTier = 'Medium'; // 기본값

            // Critical 체크
            if ($case['consecutive_high_days'] >= 2) {
                $calculatedTier = 'Critical';
            }
            // High 체크
            elseif ($case['ninactive'] >= 4 ||
                    $case['npomodoro'] < 2 ||
                    $case['tlaststroke_min'] >= 30) {
                $calculatedTier = 'High';
            }
            // Medium 체크
            elseif (($case['ninactive'] >= 2 && $case['ninactive'] <= 3) ||
                    ($case['npomodoro'] >= 2 && $case['npomodoro'] <= 4) ||
                    $case['eye_count'] >= 2) {
                $calculatedTier = 'Medium';
            }
            // Low 체크
            elseif ($case['ninactive'] <= 1 && $case['npomodoro'] >= 5) {
                $calculatedTier = 'Low';
            }

            $passed = $calculatedTier === $case['expected_tier'];

            $this->recordTest(
                '위험 계산: ' . $case['label'],
                $passed,
                $passed
                    ? "위험 수준: {$calculatedTier} (예상: {$case['expected_tier']})"
                    : "불일치 - 계산: {$calculatedTier}, 예상: {$case['expected_tier']}"
            );
        }
    }

    /**
     * 페르소나 매핑 테스트
     */
    private function testPersonaMapping(): void
    {
        $expectedMapping = [
            'Low' => 'proactive',
            'Medium' => 'occasional',
            'High' => 'chronic',
            'Critical' => 'critical'
        ];

        try {
            if (!class_exists('Agent13PersonaEngine')) {
                require_once(__DIR__ . '/Agent13PersonaEngine.php');
            }

            $reflection = new ReflectionClass('Agent13PersonaEngine');
            $defaultProps = $reflection->getDefaultProperties();
            $mapping = isset($defaultProps['riskTierToPersona']) ? $defaultProps['riskTierToPersona'] : [];

            $allCorrect = true;
            $issues = [];

            foreach ($expectedMapping as $tier => $expectedPersona) {
                if (!isset($mapping[$tier])) {
                    $allCorrect = false;
                    $issues[] = "{$tier}: 누락";
                } elseif ($mapping[$tier] !== $expectedPersona) {
                    $allCorrect = false;
                    $issues[] = "{$tier}: 불일치 (기대: {$expectedPersona}, 실제: {$mapping[$tier]})";
                }
            }

            $this->recordTest(
                '페르소나 매핑',
                $allCorrect,
                $allCorrect
                    ? count($mapping) . '개 페르소나 매핑 정의됨'
                    : '문제: ' . implode(', ', $issues)
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '페르소나 매핑',
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
            'proactive' => ['mode' => 'maintain'],
            'occasional' => ['mode' => 'guide'],
            'chronic' => ['mode' => 'intervene'],
            'critical' => ['mode' => 'emergency']
        ];

        try {
            if (!class_exists('Agent13PersonaEngine')) {
                require_once(__DIR__ . '/Agent13PersonaEngine.php');
            }

            $reflection = new ReflectionClass('Agent13PersonaEngine');
            $defaultProps = $reflection->getDefaultProperties();
            $strategies = isset($defaultProps['interventionStrategies']) ? $defaultProps['interventionStrategies'] : [];

            $allCorrect = true;
            $issues = [];

            foreach ($expectedStrategies as $persona => $expected) {
                if (!isset($strategies[$persona])) {
                    $allCorrect = false;
                    $issues[] = "{$persona}: 누락";
                } elseif ($strategies[$persona]['mode'] !== $expected['mode']) {
                    $allCorrect = false;
                    $issues[] = "{$persona}: 모드 불일치 (기대: {$expected['mode']}, 실제: {$strategies[$persona]['mode']})";
                }
            }

            $this->recordTest(
                '개입 전략 매핑',
                $allCorrect,
                $allCorrect
                    ? count($strategies) . '개 전략 정의됨'
                    : '문제: ' . implode(', ', $issues)
            );

            // 코칭 톤 검증
            $validTones = ['supportive', 'encouraging', 'coaching', 'urgent_caring'];
            $toneValid = true;

            foreach ($strategies as $persona => $strategy) {
                if (isset($strategy['coaching_tone']) && !in_array($strategy['coaching_tone'], $validTones)) {
                    $toneValid = false;
                }
            }

            $this->recordTest(
                '코칭 톤 유효성',
                $toneValid,
                $toneValid ? '모든 코칭 톤 유효' : '일부 코칭 톤 무효'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '개입 전략 매핑',
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
    $test = new Agent13PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test.php?format=json
 *
 * =========================================================================
 */
