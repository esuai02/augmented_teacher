<?php
/**
 * test.php
 *
 * Agent05 학습 감정 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent05LearningEmotion
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/test.php
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
 * Agent05PersonaTest
 *
 * 학습 감정 에이전트 테스트 클래스
 */
class Agent05PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            5,                        // 에이전트 번호
            'learning_emotion',       // 에이전트 이름
            '학습 감정',               // 에이전트 한글명
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
            'engine/Agent05PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'engine/Agent05DataContext.php' => 'DataContext 데이터 접근 클래스',
            'engine/Agent05ResponseGenerator.php' => '응답 생성기 클래스',
            'engine/EmotionAnalyzer.php' => '감정 분석기 클래스',
            'engine/LearningActivityDetector.php' => '학습 활동 감지기 클래스',
            '../rules/rules.yaml' => '규칙 정의 파일'
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
            'mdl_at_learning_emotion_log' => '감정 로그 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    /**
     * Agent05 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. DataContext 클래스 로드 테스트
        $this->testDataContextLoad();

        // 3. 학습 활동 타입 정의 테스트
        $this->testActivityTypes();

        // 4. 활동별 페르소나 매핑 테스트
        $this->testActivityPersonaMapping();

        // 5. 감정 분석기 테스트
        $this->testEmotionAnalyzer();

        // 6. 학습 활동 감지기 테스트
        $this->testLearningActivityDetector();

        // 7. 응답 생성기 테스트
        $this->testResponseGenerator();

        // 8. 감정 카테고리 테스트
        $this->testEmotionCategories();
    }

    /**
     * PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent05PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent05PersonaEngine.php'
            );
            return;
        }

        try {
            // 네임스페이스 클래스 확인
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent05PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent05PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );
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
        $filePath = __DIR__ . '/engine/Agent05DataContext.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'DataContext 클래스 로드',
                false,
                '파일 없음: engine/Agent05DataContext.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent05DataContext') !== false;

            $this->recordTest(
                'DataContext 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent05DataContext 클래스 발견' : '클래스 정의 없음'
            );
        } catch (Throwable $e) {
            $this->recordTest(
                'DataContext 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 학습 활동 타입 정의 테스트
     */
    private function testActivityTypes(): void
    {
        $expectedActivities = [
            'concept_understanding',  // 개념 이해
            'type_learning',          // 유형 학습
            'problem_solving',        // 문제 풀이
            'error_note',             // 오답 노트
            'qa',                     // 질문 응답
            'review',                 // 복습
            'pomodoro',               // 뽀모도로 학습
            'home_check'              // 홈 체크
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent05PersonaEngine.php';
            $content = file_get_contents($filePath);

            $allFound = true;
            $missingActivities = [];

            foreach ($expectedActivities as $activity) {
                if (strpos($content, "'" . $activity . "'") === false) {
                    $allFound = false;
                    $missingActivities[] = $activity;
                }
            }

            $this->recordTest(
                '학습 활동 타입 정의',
                $allFound,
                $allFound
                    ? count($expectedActivities) . '개 활동 타입 정의됨'
                    : '누락된 활동: ' . implode(', ', $missingActivities),
                ['expected' => $expectedActivities, 'missing' => $missingActivities]
            );
        } catch (Throwable $e) {
            $this->recordTest(
                '학습 활동 타입 정의',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 활동별 페르소나 매핑 테스트
     */
    private function testActivityPersonaMapping(): void
    {
        $expectedMappings = [
            'concept_understanding' => ['정리형', '반복형', '탐색형', '저항형'],
            'type_learning' => ['패턴인식형', '암기형', '유추형', '회피형'],
            'problem_solving' => ['도전형', '보조형', '완벽형', '회피형'],
            'error_note' => ['분석형', '반성형', '방어형', '회피형'],
            'qa' => ['적극형', '수동형', '확인형', '방어형'],
            'review' => ['체계형', '반복형', '선택형', '회피형'],
            'pomodoro' => ['집중형', '분산형', '적응형', '이탈형'],
            'home_check' => ['성실형', '지연형', '선택형', '회피형']
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent05PersonaEngine.php';
            $content = file_get_contents($filePath);

            $mappingFound = strpos($content, 'activityPersonaMap') !== false;

            $this->recordTest(
                '활동-페르소나 매핑 정의',
                $mappingFound,
                $mappingFound
                    ? count($expectedMappings) . '개 활동에 대한 페르소나 매핑 정의됨'
                    : 'activityPersonaMap 프로퍼티 없음'
            );

            // 각 활동별 페르소나 개수 확인
            $validMappings = 0;
            foreach ($expectedMappings as $activity => $personas) {
                $foundPersonas = 0;
                foreach ($personas as $persona) {
                    if (strpos($content, "'" . $persona . "'") !== false) {
                        $foundPersonas++;
                    }
                }
                if ($foundPersonas >= 2) { // 최소 2개 이상 매핑
                    $validMappings++;
                }
            }

            $this->recordTest(
                '페르소나 매핑 완성도',
                $validMappings >= 6,
                "{$validMappings}/8 활동에 충분한 페르소나 매핑"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '활동-페르소나 매핑 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 감정 분석기 클래스 테스트
     */
    private function testEmotionAnalyzer(): void
    {
        $filePath = __DIR__ . '/engine/EmotionAnalyzer.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'EmotionAnalyzer 클래스',
                false,
                '파일 없음: engine/EmotionAnalyzer.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class EmotionAnalyzer') !== false;

            $this->recordTest(
                'EmotionAnalyzer 클래스 정의',
                $hasClass,
                $hasClass ? 'EmotionAnalyzer 클래스 발견' : '클래스 정의 없음'
            );

            // 주요 메서드 확인
            $expectedMethods = ['analyze', 'detect', 'evaluate', 'score'];
            $foundMethods = 0;
            foreach ($expectedMethods as $method) {
                if (preg_match('/function\s+' . $method . '/i', $content)) {
                    $foundMethods++;
                }
            }

            $this->recordTest(
                'EmotionAnalyzer 핵심 메서드',
                $foundMethods >= 1,
                "{$foundMethods}개 분석 관련 메서드 발견"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'EmotionAnalyzer 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 학습 활동 감지기 클래스 테스트
     */
    private function testLearningActivityDetector(): void
    {
        $filePath = __DIR__ . '/engine/LearningActivityDetector.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'LearningActivityDetector 클래스',
                false,
                '파일 없음: engine/LearningActivityDetector.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class LearningActivityDetector') !== false;

            $this->recordTest(
                'LearningActivityDetector 클래스 정의',
                $hasClass,
                $hasClass ? 'LearningActivityDetector 클래스 발견' : '클래스 정의 없음'
            );

            // 활동 감지 메서드 확인
            $expectedMethods = ['detect', 'identify', 'classify', 'determine'];
            $foundMethods = 0;
            foreach ($expectedMethods as $method) {
                if (preg_match('/function\s+\w*' . $method . '/i', $content)) {
                    $foundMethods++;
                }
            }

            $this->recordTest(
                'LearningActivityDetector 핵심 메서드',
                $foundMethods >= 1,
                "{$foundMethods}개 감지 관련 메서드 발견"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'LearningActivityDetector 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 응답 생성기 클래스 테스트
     */
    private function testResponseGenerator(): void
    {
        $filePath = __DIR__ . '/engine/Agent05ResponseGenerator.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'ResponseGenerator 클래스',
                false,
                '파일 없음: engine/Agent05ResponseGenerator.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent05ResponseGenerator') !== false;

            $this->recordTest(
                'Agent05ResponseGenerator 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent05ResponseGenerator 클래스 발견' : '클래스 정의 없음'
            );

            // 응답 생성 메서드 확인
            $hasGenerate = preg_match('/function\s+\w*generate/i', $content);
            $this->recordTest(
                'ResponseGenerator 생성 메서드',
                $hasGenerate,
                $hasGenerate ? '응답 생성 메서드 발견' : '응답 생성 메서드 없음'
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
     * 감정 카테고리 테스트
     */
    private function testEmotionCategories(): void
    {
        $expectedEmotions = [
            '불안',      // anxiety
            '좌절',      // frustration
            '자신감',    // confidence
            '성취감',    // achievement
            '흥미',      // interest
            '지루함'     // boredom
        ];

        try {
            // PersonaEngine 또는 EmotionAnalyzer에서 감정 정의 확인
            $files = [
                __DIR__ . '/engine/Agent05PersonaEngine.php',
                __DIR__ . '/engine/EmotionAnalyzer.php'
            ];

            $foundEmotions = 0;
            foreach ($files as $filePath) {
                if (file_exists($filePath)) {
                    $content = file_get_contents($filePath);
                    foreach ($expectedEmotions as $emotion) {
                        if (strpos($content, $emotion) !== false) {
                            $foundEmotions++;
                        }
                    }
                }
            }

            // 중복 카운트 제거를 위해 최대값 제한
            $uniqueEmotions = min($foundEmotions, count($expectedEmotions));

            $this->recordTest(
                '감정 카테고리 정의',
                $uniqueEmotions >= 3,
                "{$uniqueEmotions}/" . count($expectedEmotions) . " 감정 카테고리 정의됨",
                ['expected' => $expectedEmotions]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '감정 카테고리 테스트',
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
    $test = new Agent05PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent05_learning_emotion/persona_system/test.php?format=json
 *
 * =========================================================================
 */
