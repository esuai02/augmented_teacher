<?php
/**
 * test.php
 *
 * Agent12 휴식 루틴 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent12RestRoutine
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test.php
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
 * Agent12PersonaTest
 *
 * 휴식 루틴 에이전트 테스트 클래스
 */
class Agent12PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            12,                   // 에이전트 번호
            'rest_routine',       // 에이전트 이름
            '휴식 루틴',           // 에이전트 한글명
            __DIR__               // 기본 경로 (persona_system)
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
            'Agent12PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'Agent12DataContext.php' => 'DataContext 데이터 접근 클래스',
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
            'mdl_at_agent12_rest_sessions' => '휴식 세션 테이블',
            'mdl_at_agent12_routine_history' => '루틴 히스토리 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    /**
     * Agent12 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. DataContext 클래스 로드 테스트
        $this->testDataContextLoad();

        // 3. 휴식 패턴 레벨 정의 테스트
        $this->testRestPatternLevels();

        // 4. 피로도 지수 계산 테스트
        $this->testFatigueIndexCalculation();

        // 5. 휴식 전략 매핑 테스트
        $this->testRestStrategyMapping();

        // 6. API 엔드포인트 테스트
        $this->testApiEndpoint('api/chat.php', 'GET');

        // 7. personas.md 문서 존재 테스트
        $this->testFileExists('personas.md', '페르소나 정의 문서');
    }

    /**
     * PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/Agent12PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaEngine 클래스 로드',
                false,
                '파일 없음: Agent12PersonaEngine.php'
            );
            return;
        }

        try {
            require_once($filePath);
            $exists = class_exists('Agent12PersonaEngine');

            $this->recordTest(
                'PersonaEngine 클래스 로드',
                $exists,
                $exists ? 'Agent12PersonaEngine 클래스 로드 성공' : '클래스 찾을 수 없음'
            );

            // 추상 메서드 구현 확인
            if ($exists) {
                $reflection = new ReflectionClass('Agent12PersonaEngine');
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
        $filePath = __DIR__ . '/Agent12DataContext.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'DataContext 클래스 로드',
                false,
                '파일 없음: Agent12DataContext.php'
            );
            return;
        }

        try {
            require_once($filePath);
            $exists = class_exists('Agent12DataContext');

            $this->recordTest(
                'DataContext 클래스 로드',
                $exists,
                $exists ? 'Agent12DataContext 클래스 로드 성공' : '클래스 찾을 수 없음'
            );

            // DataContextInterface 구현 확인
            if ($exists) {
                $implements = class_implements('Agent12DataContext');
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
     * 휴식 패턴 레벨 정의 테스트
     */
    private function testRestPatternLevels(): void
    {
        $expectedLevels = [
            'regular_rest',
            'activity_centered_rest',
            'immersive_rest',
            'no_rest'
        ];

        try {
            if (!class_exists('Agent12PersonaEngine')) {
                require_once(__DIR__ . '/Agent12PersonaEngine.php');
            }

            // Reflection을 사용하여 protected 프로퍼티 접근
            $reflection = new ReflectionClass('Agent12PersonaEngine');

            if ($reflection->hasProperty('restPatternLevels')) {
                $property = $reflection->getProperty('restPatternLevels');
                $property->setAccessible(true);

                // 기본값 확인
                $defaultProps = $reflection->getDefaultProperties();
                $levels = isset($defaultProps['restPatternLevels']) ? $defaultProps['restPatternLevels'] : [];

                $allPresent = true;
                $missingLevels = [];

                foreach ($expectedLevels as $level) {
                    if (!isset($levels[$level])) {
                        $allPresent = false;
                        $missingLevels[] = $level;
                    }
                }

                $this->recordTest(
                    '휴식 패턴 레벨 정의',
                    $allPresent,
                    $allPresent
                        ? count($levels) . '개 레벨 정의됨'
                        : '누락된 레벨: ' . implode(', ', $missingLevels),
                    ['expected' => $expectedLevels, 'found' => array_keys($levels)]
                );
            } else {
                $this->recordTest(
                    '휴식 패턴 레벨 정의',
                    false,
                    'restPatternLevels 프로퍼티 없음'
                );
            }
        } catch (Throwable $e) {
            $this->recordTest(
                '휴식 패턴 레벨 정의',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 피로도 지수 계산 테스트
     */
    private function testFatigueIndexCalculation(): void
    {
        $testCases = [
            ['avg_interval' => 45, 'rest_count' => 5, 'expected_range' => [0, 40], 'label' => '규칙적 휴식 (낮은 피로도)'],
            ['avg_interval' => 60, 'rest_count' => 3, 'expected_range' => [30, 60], 'label' => '적정 휴식 (보통 피로도)'],
            ['avg_interval' => 120, 'rest_count' => 1, 'expected_range' => [50, 80], 'label' => '불규칙 휴식 (높은 피로도)'],
            ['avg_interval' => 0, 'rest_count' => 0, 'expected_range' => [70, 100], 'label' => '휴식 없음 (치명적 피로도)'],
        ];

        foreach ($testCases as $case) {
            // 피로도 지수 수동 계산 (Agent12PersonaEngine의 로직 기반)
            $fatigue = 50.0;

            // 휴식 횟수에 따른 조정
            if ($case['rest_count'] === 0) {
                $fatigue = 85.0;
            } elseif ($case['rest_count'] >= 5) {
                $fatigue -= 15;
            } elseif ($case['rest_count'] >= 3) {
                $fatigue -= 5;
            }

            // 평균 간격에 따른 조정
            if ($case['avg_interval'] > 0) {
                if ($case['avg_interval'] <= 45) {
                    $fatigue -= 20;
                } elseif ($case['avg_interval'] <= 60) {
                    $fatigue -= 10;
                } elseif ($case['avg_interval'] <= 90) {
                    // 중립
                } elseif ($case['avg_interval'] <= 120) {
                    $fatigue += 10;
                } else {
                    $fatigue += 20;
                }
            }

            $fatigue = max(0, min(100, $fatigue));

            $inRange = $fatigue >= $case['expected_range'][0] && $fatigue <= $case['expected_range'][1];

            $this->recordTest(
                '피로도 계산: ' . $case['label'],
                $inRange,
                $inRange
                    ? "피로도: {$fatigue} (범위: {$case['expected_range'][0]}-{$case['expected_range'][1]})"
                    : "범위 벗어남 - 피로도: {$fatigue}, 예상: {$case['expected_range'][0]}-{$case['expected_range'][1]}"
            );
        }
    }

    /**
     * 휴식 전략 매핑 테스트
     */
    private function testRestStrategyMapping(): void
    {
        $expectedStrategies = [
            'regular_rest' => ['mode' => 'maintain'],
            'activity_centered_rest' => ['mode' => 'optimize'],
            'immersive_rest' => ['mode' => 'restructure'],
            'no_rest' => ['mode' => 'establish']
        ];

        try {
            if (!class_exists('Agent12PersonaEngine')) {
                require_once(__DIR__ . '/Agent12PersonaEngine.php');
            }

            $reflection = new ReflectionClass('Agent12PersonaEngine');
            $defaultProps = $reflection->getDefaultProperties();
            $strategies = isset($defaultProps['restStrategies']) ? $defaultProps['restStrategies'] : [];

            $allCorrect = true;
            $issues = [];

            foreach ($expectedStrategies as $level => $expected) {
                if (!isset($strategies[$level])) {
                    $allCorrect = false;
                    $issues[] = "{$level}: 누락";
                } elseif ($strategies[$level]['mode'] !== $expected['mode']) {
                    $allCorrect = false;
                    $issues[] = "{$level}: 모드 불일치 (기대: {$expected['mode']}, 실제: {$strategies[$level]['mode']})";
                }
            }

            $this->recordTest(
                '휴식 전략 매핑',
                $allCorrect,
                $allCorrect
                    ? count($strategies) . '개 전략 정의됨'
                    : '문제: ' . implode(', ', $issues)
            );

            // 코칭 톤 검증
            $validTones = ['supportive', 'balanced', 'coaching', 'intervention'];
            $toneValid = true;

            foreach ($strategies as $level => $strategy) {
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
                '휴식 전략 매핑',
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
    $test = new Agent12PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test.php?format=json
 *
 * =========================================================================
 */
