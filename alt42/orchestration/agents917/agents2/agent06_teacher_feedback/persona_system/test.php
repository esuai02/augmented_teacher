<?php
/**
 * test.php
 *
 * Agent06 교사 피드백 페르소나 시스템 테스트
 * BasePersonaTest 프레임워크 상속
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent06TeacherFeedback
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/test.php
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
 * Agent06PersonaTest
 *
 * 교사 피드백 에이전트 테스트 클래스
 */
class Agent06PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            6,                        // 에이전트 번호
            'teacher_feedback',       // 에이전트 이름
            '교사 피드백',             // 에이전트 한글명
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
            'engine/TeacherPersonaEngine.php' => 'PersonaEngine 메인 클래스',
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
     * Agent06 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 상황별 피드백 유형 (T1-T5) 테스트
        $this->testSituationTypes();

        // 3. 선생님 톤 수정자 테스트
        $this->testTeacherToneModifiers();

        // 4. 학생-선생님 페르소나 매칭 테스트
        $this->testPersonaMatching();

        // 5. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 6. personas.md 문서 존재 테스트
        $this->testFileExists('personas.md', '페르소나 정의 문서');

        // 7. rules.yaml 파일 테스트
        $this->testRulesYaml();
    }

    /**
     * PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/TeacherPersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'TeacherPersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/TeacherPersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class TeacherPersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'TeacherPersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'TeacherPersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );
        } catch (Throwable $e) {
            $this->recordTest(
                'TeacherPersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 상황별 피드백 유형 (T1-T5) 테스트
     */
    private function testSituationTypes(): void
    {
        $expectedTypes = [
            'T1' => '격려',      // 격려/칭찬
            'T2' => '교정',      // 오류 교정/안내
            'T3' => '학습',      // 학습 설계/추천
            'T4' => '감정',      // 감정 지원/상담
            'T5' => '성과'       // 성과 평가/리포트
        ];

        try {
            $filePath = __DIR__ . '/engine/TeacherPersonaEngine.php';
            $content = file_get_contents($filePath);

            $foundTypes = 0;
            $missingTypes = [];

            foreach ($expectedTypes as $type => $keyword) {
                if (strpos($content, "'" . $type . "'") !== false) {
                    $foundTypes++;
                } else {
                    $missingTypes[] = $type;
                }
            }

            $this->recordTest(
                '상황별 피드백 유형 정의',
                $foundTypes >= 5,
                $foundTypes >= 5
                    ? 'T1-T5 모든 피드백 유형 정의됨'
                    : '누락된 유형: ' . implode(', ', $missingTypes),
                ['expected' => array_keys($expectedTypes), 'found' => $foundTypes]
            );
        } catch (Throwable $e) {
            $this->recordTest(
                '상황별 피드백 유형 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 선생님 톤 수정자 테스트
     */
    private function testTeacherToneModifiers(): void
    {
        $expectedTones = [
            'Warm',          // 따뜻한
            'Encouraging',   // 격려하는
            'Professional',  // 전문적인
            'Empathetic',    // 공감하는
            'Reassuring',    // 안심시키는
            'Analytical'     // 분석적인
        ];

        try {
            $filePath = __DIR__ . '/engine/TeacherPersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasToneModifiers = strpos($content, 'teacherToneModifiers') !== false;

            $this->recordTest(
                '선생님 톤 수정자 정의',
                $hasToneModifiers,
                $hasToneModifiers ? 'teacherToneModifiers 프로퍼티 정의됨' : 'teacherToneModifiers 없음'
            );

            $foundTones = 0;
            foreach ($expectedTones as $tone) {
                if (strpos($content, "'" . $tone . "'") !== false) {
                    $foundTones++;
                }
            }

            $this->recordTest(
                '선생님 톤 유형 완성도',
                $foundTones >= 5,
                "{$foundTones}/" . count($expectedTones) . " 톤 유형 정의됨"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '선생님 톤 수정자 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 학생-선생님 페르소나 매칭 테스트
     */
    private function testPersonaMatching(): void
    {
        try {
            $filePath = __DIR__ . '/engine/TeacherPersonaEngine.php';
            $content = file_get_contents($filePath);

            // 매칭 관련 구조 확인
            $hasTeacherPersonas = strpos($content, 'teacherPersonas') !== false;
            $hasMatchingRules = strpos($content, 'matchingRules') !== false;
            $hasAgent01Reference = strpos($content, 'Agent01') !== false ||
                                   strpos($content, 'agent01') !== false ||
                                   strpos($content, '학생 페르소나') !== false;

            $this->recordTest(
                '선생님 페르소나 정의',
                $hasTeacherPersonas,
                $hasTeacherPersonas ? 'teacherPersonas 프로퍼티 정의됨' : 'teacherPersonas 없음'
            );

            $this->recordTest(
                '매칭 규칙 정의',
                $hasMatchingRules,
                $hasMatchingRules ? 'matchingRules 프로퍼티 정의됨' : 'matchingRules 없음'
            );

            $this->recordTest(
                'Agent01 연동 참조',
                $hasAgent01Reference,
                $hasAgent01Reference ? 'Agent01(학생 페르소나) 연동 참조 발견' : 'Agent01 연동 참조 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '페르소나 매칭 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * rules.yaml 파일 테스트
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

            // YAML 구조 확인 (간단한 키워드 검사)
            $hasRules = strpos($content, 'rules:') !== false ||
                       strpos($content, 'conditions:') !== false ||
                       strpos($content, 'actions:') !== false;

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

// 출력 형식 결정
$format = isset($_GET['format']) ? strtolower($_GET['format']) : 'html';

try {
    $test = new Agent06PersonaTest();
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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/test.php
 *
 * JSON 출력:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent06_teacher_feedback/persona_system/test.php?format=json
 *
 * =========================================================================
 */
