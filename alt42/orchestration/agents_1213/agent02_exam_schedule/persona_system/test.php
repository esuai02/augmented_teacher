<?php
/**
 * test.php
 *
 * Agent02 시험일정 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent02ExamSchedule
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/test.php
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
 * Agent02PersonaTest
 *
 * 시험일정 에이전트 테스트 클래스
 */
class Agent02PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            2,                    // 에이전트 번호
            'exam_schedule',      // 에이전트 이름
            '시험일정',            // 에이전트 한글명
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
            'Agent02PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'Agent02DataContext.php' => 'DataContext 데이터 접근 클래스',
            'api/chat.php' => '채팅 API 엔드포인트',
            '../rules/rules.yaml' => '규칙 정의 파일',
            '../api/exam_strategy_api.php' => 'GPT 전략 생성 API'
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
            'mdl_alt42_exam_schedule' => '시험 일정 테이블',
            'mdl_alt42g_exam_strategies' => '생성된 전략 테이블',
            'mdl_alt42g_exam_strategy_meta' => '전략 메타 테이블'
        ];
    }

    /**
     * Agent02 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. DataContext 클래스 로드 테스트
        $this->testDataContextLoad();

        // 3. 타임라인 레벨 정의 테스트
        $this->testTimelineLevels();

        // 4. D-Day 계산 로직 테스트
        $this->testDDayCalculation();

        // 5. 학습 전략 매핑 테스트
        $this->testStudyStrategyMapping();

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
        $filePath = __DIR__ . '/Agent02PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaEngine 클래스 로드',
                false,
                '파일 없음: Agent02PersonaEngine.php'
            );
            return;
        }

        try {
            require_once($filePath);
            $exists = class_exists('Agent02PersonaEngine');

            $this->recordTest(
                'PersonaEngine 클래스 로드',
                $exists,
                $exists ? 'Agent02PersonaEngine 클래스 로드 성공' : '클래스 찾을 수 없음'
            );

            // 추상 메서드 구현 확인
            if ($exists) {
                $reflection = new ReflectionClass('Agent02PersonaEngine');
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
        $filePath = __DIR__ . '/Agent02DataContext.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'DataContext 클래스 로드',
                false,
                '파일 없음: Agent02DataContext.php'
            );
            return;
        }

        try {
            require_once($filePath);
            $exists = class_exists('Agent02DataContext');

            $this->recordTest(
                'DataContext 클래스 로드',
                $exists,
                $exists ? 'Agent02DataContext 클래스 로드 성공' : '클래스 찾을 수 없음'
            );

            // DataContextInterface 구현 확인
            if ($exists) {
                $implements = class_implements('Agent02DataContext');
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
     * 타임라인 레벨 정의 테스트
     */
    private function testTimelineLevels(): void
    {
        $expectedLevels = [
            'vacation', 'd_2month', 'd_1month', 'd_2week',
            'd_1week', 'd_3day', 'd_1day', 'no_exam'
        ];

        try {
            if (!class_exists('Agent02PersonaEngine')) {
                require_once(__DIR__ . '/Agent02PersonaEngine.php');
            }

            // Reflection을 사용하여 protected 프로퍼티 접근
            $reflection = new ReflectionClass('Agent02PersonaEngine');

            if ($reflection->hasProperty('timelineLevels')) {
                $property = $reflection->getProperty('timelineLevels');
                $property->setAccessible(true);

                // 인스턴스 없이 기본값 확인 (PHP 7.1 호환)
                $defaultProps = $reflection->getDefaultProperties();
                $levels = isset($defaultProps['timelineLevels']) ? $defaultProps['timelineLevels'] : [];

                $allPresent = true;
                $missingLevels = [];

                foreach ($expectedLevels as $level) {
                    if (!isset($levels[$level])) {
                        $allPresent = false;
                        $missingLevels[] = $level;
                    }
                }

                $this->recordTest(
                    '타임라인 레벨 정의',
                    $allPresent,
                    $allPresent
                        ? count($levels) . '개 레벨 정의됨'
                        : '누락된 레벨: ' . implode(', ', $missingLevels),
                    ['expected' => $expectedLevels, 'found' => array_keys($levels)]
                );
            } else {
                $this->recordTest(
                    '타임라인 레벨 정의',
                    false,
                    'timelineLevels 프로퍼티 없음'
                );
            }
        } catch (Throwable $e) {
            $this->recordTest(
                '타임라인 레벨 정의',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * D-Day 계산 로직 테스트
     */
    private function testDDayCalculation(): void
    {
        $testCases = [
            ['date' => date('Y-m-d'), 'expected' => 0, 'label' => 'D-Day (오늘)'],
            ['date' => date('Y-m-d', strtotime('+1 day')), 'expected' => 1, 'label' => 'D-1 (내일)'],
            ['date' => date('Y-m-d', strtotime('+7 days')), 'expected' => 7, 'label' => 'D-7 (일주일)'],
            ['date' => date('Y-m-d', strtotime('+30 days')), 'expected' => 30, 'label' => 'D-30 (한달)'],
        ];

        foreach ($testCases as $case) {
            $examDate = new DateTime($case['date']);
            $today = new DateTime('today');
            $diff = $today->diff($examDate);
            $calculated = $diff->invert ? -$diff->days : $diff->days;

            $passed = $calculated === $case['expected'];

            $this->recordTest(
                'D-Day 계산: ' . $case['label'],
                $passed,
                $passed
                    ? "예상: {$case['expected']}, 결과: {$calculated}"
                    : "불일치 - 예상: {$case['expected']}, 결과: {$calculated}"
            );
        }
    }

    /**
     * 학습 전략 매핑 테스트
     */
    private function testStudyStrategyMapping(): void
    {
        $expectedStrategies = [
            'd_1day' => ['mode' => 'final_review'],
            'd_3day' => ['mode' => 'urgent_focus'],
            'd_1week' => ['mode' => 'intensive'],
            'd_2week' => ['mode' => 'balanced'],
            'd_1month' => ['mode' => 'concept_first'],
            'd_2month' => ['mode' => 'foundation'],
            'vacation' => ['mode' => 'preview'],
            'no_exam' => ['mode' => 'general']
        ];

        try {
            if (!class_exists('Agent02PersonaEngine')) {
                require_once(__DIR__ . '/Agent02PersonaEngine.php');
            }

            $reflection = new ReflectionClass('Agent02PersonaEngine');
            $defaultProps = $reflection->getDefaultProperties();
            $strategies = isset($defaultProps['studyStrategies']) ? $defaultProps['studyStrategies'] : [];

            $allCorrect = true;
            $issues = [];

            foreach ($expectedStrategies as $level => $expected) {
                if (!isset($strategies[$level])) {
                    $allCorrect = false;
                    $issues[] = "{$level}: 누락";
                } elseif ($strategies[$level]['mode'] !== $expected['mode']) {
                    $allCorrect = false;
                    $issues[] = "{$level}: 모드 불일치";
                }
            }

            $this->recordTest(
                '학습 전략 매핑',
                $allCorrect,
                $allCorrect
                    ? count($strategies) . '개 전략 정의됨'
                    : '문제: ' . implode(', ', $issues)
            );

            // 비율 합계 검증
            $ratioValid = true;
            foreach ($strategies as $level => $strategy) {
                if (isset($strategy['ratio'])) {
                    $total = ($strategy['ratio']['concept'] ?? 0) + ($strategy['ratio']['problem'] ?? 0);
                    if ($total !== 100) {
                        $ratioValid = false;
                    }
                }
            }

            $this->recordTest(
                '학습 비율 합계 (100%)',
                $ratioValid,
                $ratioValid ? '모든 전략의 비율 합계 = 100%' : '일부 전략의 비율 합계 불일치'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '학습 전략 매핑',
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
    $test = new Agent02PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/persona_system/test.php?format=json
 *
 * =========================================================================
 */
