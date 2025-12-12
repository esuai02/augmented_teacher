<?php
/**
 * test_chat.php
 *
 * Agent11 문제노트 페르소나 시스템 통합 테스트
 * 탭1: 채팅 테스트 (API 기반)
 * 탭2: 진단 테스트 (BasePersonaTest 상속)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent11ProblemNotes
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test_chat.php
 */

// =========================================================================
// 에러 리포팅 및 초기화
// =========================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// =========================================================================
// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

// =========================================================================
// 에이전트 설정
// =========================================================================
$agentNumber = 11;
$agentName = 'problem_notes';
$agentKrName = '문제노트';
$agentEmoji = '📝';
$agentDescription = '오답의 원인을 분석하고 학생의 학습 패턴을 파악하여 맞춤형 피드백을 제공합니다.';

// 전체 에이전트 목록 (드롭다운 메뉴용)
$allAgents = [
    ['num' => 1, 'id' => 'onboarding', 'name' => '온보딩', 'emoji' => '🎯'],
    ['num' => 2, 'id' => 'exam_schedule', 'name' => '시험일정', 'emoji' => '📅'],
    ['num' => 3, 'id' => 'goals_analysis', 'name' => '목표분석', 'emoji' => '🎯'],
    ['num' => 4, 'id' => 'inspect_weakpoints', 'name' => '취약점분석', 'emoji' => '🔍'],
    ['num' => 5, 'id' => 'learning_emotion', 'name' => '학습감정', 'emoji' => '💭'],
    ['num' => 6, 'id' => 'teacher_feedback', 'name' => '선생님피드백', 'emoji' => '👨‍🏫'],
    ['num' => 7, 'id' => 'interaction_targeting', 'name' => '상호작용타겟팅', 'emoji' => '🎯'],
    ['num' => 8, 'id' => 'calmness', 'name' => '마음챙김', 'emoji' => '🧘'],
    ['num' => 9, 'id' => 'learning_management', 'name' => '학습관리', 'emoji' => '📚'],
    ['num' => 10, 'id' => 'concept_notes', 'name' => '개념노트', 'emoji' => '📝'],
    ['num' => 11, 'id' => 'problem_notes', 'name' => '문제노트', 'emoji' => '✏️'],
    ['num' => 12, 'id' => 'rest_routine', 'name' => '휴식루틴', 'emoji' => '😴'],
    ['num' => 13, 'id' => 'learning_dropout', 'name' => '학습이탈', 'emoji' => '⚠️'],
    ['num' => 14, 'id' => 'curriculum_innovation', 'name' => '커리큘럼혁신', 'emoji' => '📍'],
    ['num' => 15, 'id' => 'problem_redefinition', 'name' => '문제재정의', 'emoji' => '🔄'],
    ['num' => 16, 'id' => 'interaction_preparation', 'name' => '상호작용준비', 'emoji' => '🤝'],
    ['num' => 17, 'id' => 'remaining_activities', 'name' => '잔여활동', 'emoji' => '⏰'],
    ['num' => 18, 'id' => 'signature_routine', 'name' => '시그니처루틴', 'emoji' => '✨'],
    ['num' => 19, 'id' => 'interaction_content', 'name' => '상호작용컨텐츠', 'emoji' => '💬'],
    ['num' => 20, 'id' => 'intervention_preparation', 'name' => '개입준비', 'emoji' => '🚀'],
    ['num' => 21, 'id' => 'intervention_execution', 'name' => '개입실행', 'emoji' => '⚡'],
];

// 컨텍스트 코드 정의 (오류 유형 기반)
$contextCodes = [
    'concept_confusion' => ['name' => '개념 혼동', 'color' => '#c0392b', 'desc' => '개념을 잘못 이해하여 발생하는 오류'],
    'calculation_mistake' => ['name' => '계산 실수', 'color' => '#e67e22', 'desc' => '계산 과정에서의 실수'],
    'reading_error' => ['name' => '문제 읽기 오류', 'color' => '#f39c12', 'desc' => '문제를 제대로 읽지 않아 발생하는 오류'],
    'process_error' => ['name' => '풀이 과정 오류', 'color' => '#d35400', 'desc' => '풀이 방법은 알지만 과정에서 실수'],
    'careless_mistake' => ['name' => '부주의 실수', 'color' => '#27ae60', 'desc' => '주의력 부족으로 인한 단순 실수'],
    'basic_gap' => ['name' => '기초 개념 부족', 'color' => '#9b59b6', 'desc' => '선수 학습 내용 미숙지'],
    'needs_practice' => ['name' => '연습 필요', 'color' => '#3498db', 'desc' => '반복 연습이 필요한 상태'],
    'default' => ['name' => '기본', 'color' => '#7f8c8d', 'desc' => '일반 문제노트 상담']
];

// 페르소나 정의
$personaMapping = [
    'AnalyticalHelper' => '분석적 조력자',
    'EncouragingCoach' => '격려형 코치',
    'PatientGuide' => '차분한 안내자',
    'PracticeLeader' => '연습 리더'
];

// 빠른 메시지 목록
$quickMessages = [
    ['msg' => '이 문제에서 왜 틀렸는지 모르겠어요', 'context' => 'concept_confusion'],
    ['msg' => '계산 실수를 자주 해요', 'context' => 'calculation_mistake'],
    ['msg' => '문제를 잘못 읽었어요', 'context' => 'reading_error'],
    ['msg' => '풀이 과정에서 막혔어요', 'context' => 'process_error'],
    ['msg' => '기초가 부족한 것 같아요', 'context' => 'basic_gap'],
    ['msg' => '비슷한 문제를 더 풀어보고 싶어요', 'context' => 'needs_practice'],
    ['msg' => '자꾸 같은 실수를 반복해요', 'context' => 'careless_mistake'],
    ['msg' => '오답노트를 어떻게 작성해야 하나요?', 'context' => 'default']
];

// 탭 상태 확인
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';
$validTabs = ['chat', 'diagnosis'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'chat';
}

// =========================================================================
// 진단 테스트 클래스 (탭2용)
// =========================================================================
class Agent11PersonaTest extends BasePersonaTest
{
        public function __construct()
        {
            parent::__construct(
                11,
                'problem_notes',
                '문제노트',
                __DIR__
            );
        }

        protected function getRequiredFiles(): array
        {
            return [
                'PersonaEngine.php' => 'Agent11PersonaEngine 메인 클래스',
                'config.php' => '에이전트 설정 파일',
                'rules/rules.yaml' => '규칙 정의 파일',
                'api/persona.php' => 'API 엔드포인트',
                'templates/default/note_analysis.txt' => '기본 노트 분석 템플릿',
                'templates/AnalyticalHelper/greeting.txt' => '분석적 조력자 인사 템플릿',
                'templates/EncouragingCoach/greeting.txt' => '격려형 코치 인사 템플릿',
                'templates/PatientGuide/greeting.txt' => '차분한 안내자 인사 템플릿',
                'templates/PracticeLeader/greeting.txt' => '연습 리더 인사 템플릿'
            ];
        }

        protected function getRequiredTables(): array
        {
            return [
                'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블',
                'mdl_at_agent_messages' => '에이전트 간 메시지 테이블'
            ];
        }

        protected function runCustomTests(): void
        {
            $this->testPersonaEngineLoad();
            $this->testAbstractPersonaEngineInheritance();
            $this->testPersonaDefinitions();
            $this->testPersonaStateSync();
            $this->testPersonaDetermination();
            $this->testEmotionalStateBroadcast();
            $this->testApiEndpoint('api/', 'GET');
            $this->testTemplatesDirectory();
            $this->testRulesYaml();
            $this->testErrorClassifications();
        }

        private function testPersonaEngineLoad(): void
        {
            $filePath = __DIR__ . '/PersonaEngine.php';

            if (!file_exists($filePath)) {
                $this->recordTest(
                    'Agent11PersonaEngine 클래스 로드',
                    false,
                    '파일 없음: PersonaEngine.php [' . __FILE__ . ':' . __LINE__ . ']'
                );
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class Agent11PersonaEngine') !== false;
                $hasNamespace = strpos($content, 'namespace AugmentedTeacher\\Agent11\\PersonaSystem') !== false;

                $this->recordTest(
                    'Agent11PersonaEngine 클래스 정의',
                    $hasClass,
                    $hasClass ? 'Agent11PersonaEngine 클래스 발견' : '클래스 정의 없음'
                );

                $this->recordTest(
                    '네임스페이스 정의',
                    $hasNamespace,
                    $hasNamespace ? '올바른 네임스페이스' : '네임스페이스 없음'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'Agent11PersonaEngine 클래스 로드',
                    false,
                    '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testAbstractPersonaEngineInheritance(): void
        {
            try {
                $filePath = __DIR__ . '/PersonaEngine.php';
                $content = file_get_contents($filePath);

                $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

                $this->recordTest(
                    'AbstractPersonaEngine 상속',
                    $extendsAbstract,
                    $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
                );

                $usesCommonComponents =
                    strpos($content, 'BaseConditionEvaluator') !== false ||
                    strpos($content, 'BaseActionExecutor') !== false ||
                    strpos($content, 'YamlRuleParser') !== false;

                $this->recordTest(
                    '공통 컴포넌트 사용',
                    $usesCommonComponents,
                    $usesCommonComponents ? '공통 컴포넌트 사용됨' : '일부 컴포넌트 누락 가능'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'AbstractPersonaEngine 상속 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testPersonaDefinitions(): void
        {
            $expectedPersonas = [
                'AnalyticalHelper' => '분석적 조력자',
                'EncouragingCoach' => '격려형 코치',
                'PatientGuide' => '차분한 안내자',
                'PracticeLeader' => '연습 리더'
            ];

            try {
                $filePath = __DIR__ . '/PersonaEngine.php';
                $content = file_get_contents($filePath);

                $foundPersonas = 0;
                foreach (array_keys($expectedPersonas) as $persona) {
                    if (strpos($content, "'" . $persona . "'") !== false ||
                        strpos($content, '"' . $persona . '"') !== false) {
                        $foundPersonas++;
                    }
                }

                $this->recordTest(
                    '4개 페르소나 정의',
                    $foundPersonas === 4,
                    $foundPersonas === 4
                        ? '모든 페르소나 정의됨 (AnalyticalHelper, EncouragingCoach, PatientGuide, PracticeLeader)'
                        : "{$foundPersonas}/4 페르소나 발견"
                );

                $hasDefaultPersona = strpos($content, "defaultPersona = 'AnalyticalHelper'") !== false ||
                                    strpos($content, 'AnalyticalHelper') !== false;

                $this->recordTest(
                    '기본 페르소나 설정',
                    $hasDefaultPersona,
                    $hasDefaultPersona ? '기본 페르소나: AnalyticalHelper' : '기본 페르소나 설정 확인 불가'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '페르소나 정의 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testPersonaStateSync(): void
        {
            try {
                $filePath = __DIR__ . '/PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasStateSync = strpos($content, 'PersonaStateSync') !== false;
                $hasSaveState = strpos($content, 'saveState') !== false;
                $hasGetState = strpos($content, 'getState') !== false;

                $this->recordTest(
                    'PersonaStateSync 클래스 참조',
                    $hasStateSync,
                    $hasStateSync ? 'PersonaStateSync 참조됨' : 'PersonaStateSync 없음'
                );

                $stateMethods = ($hasSaveState ? 1 : 0) + ($hasGetState ? 1 : 0);

                $this->recordTest(
                    '상태 관리 메서드',
                    $stateMethods >= 1,
                    $stateMethods >= 1 ? '상태 관리 메서드 존재' : '상태 관리 메서드 부족'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'PersonaStateSync 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testPersonaDetermination(): void
        {
            try {
                $filePath = __DIR__ . '/PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasDeterminePersona = strpos($content, 'determinePersona') !== false;

                $this->recordTest(
                    'determinePersona 메서드',
                    $hasDeterminePersona,
                    $hasDeterminePersona ? 'determinePersona 메서드 정의됨' : 'determinePersona 없음'
                );

                $hasEmotionalTransition = strpos($content, 'emotional_state') !== false;

                $this->recordTest(
                    '감정 기반 페르소나 전환',
                    $hasEmotionalTransition,
                    $hasEmotionalTransition ? '감정 상태 기반 전환 로직 존재' : '감정 기반 전환 없음'
                );

                $hasErrorTypeLogic = strpos($content, 'error_type') !== false;

                $this->recordTest(
                    '오류 유형 기반 결정',
                    $hasErrorTypeLogic,
                    $hasErrorTypeLogic ? 'error_type 기반 페르소나 결정 로직' : '오류 유형 로직 없음'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '페르소나 결정 로직 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testEmotionalStateBroadcast(): void
        {
            try {
                $filePath = __DIR__ . '/PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasBroadcast = strpos($content, 'broadcastEmotionalState') !== false ||
                               strpos($content, 'broadcast') !== false;

                $this->recordTest(
                    '감정 상태 브로드캐스트',
                    $hasBroadcast,
                    $hasBroadcast ? '감정 상태 브로드캐스트 기능 존재' : '브로드캐스트 기능 없음'
                );

                $hasMessageBus = strpos($content, 'MessageBus') !== false ||
                                strpos($content, 'getMessageBus') !== false;

                $this->recordTest(
                    'MessageBus 연동',
                    $hasMessageBus,
                    $hasMessageBus ? 'MessageBus를 통한 에이전트 간 통신' : 'MessageBus 연동 없음'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '감정 브로드캐스트 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testTemplatesDirectory(): void
        {
            $templatesPath = __DIR__ . '/templates';

            if (!is_dir($templatesPath)) {
                $this->recordTest(
                    'templates 디렉토리',
                    false,
                    'templates 디렉토리 없음 [' . __FILE__ . ':' . __LINE__ . ']'
                );
                return;
            }

            $expectedDirs = ['default', 'AnalyticalHelper', 'EncouragingCoach', 'PatientGuide', 'PracticeLeader'];
            $foundDirs = 0;

            foreach ($expectedDirs as $dir) {
                if (is_dir($templatesPath . '/' . $dir)) {
                    $foundDirs++;
                }
            }

            $this->recordTest(
                'templates 디렉토리',
                true,
                "templates 디렉토리 존재 ({$foundDirs}/" . count($expectedDirs) . "개 하위 폴더)"
            );

            $this->recordTest(
                '페르소나별 템플릿 폴더',
                $foundDirs >= 4,
                $foundDirs >= 4 ? '4개 페르소나 템플릿 폴더 존재' : '일부 템플릿 폴더 누락'
            );
        }

        private function testRulesYaml(): void
        {
            $filePath = __DIR__ . '/rules/rules.yaml';

            if (!file_exists($filePath)) {
                $this->recordTest(
                    'rules.yaml 존재',
                    false,
                    '파일 없음: rules/rules.yaml [' . __FILE__ . ':' . __LINE__ . ']'
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

                $hasPersonas = strpos($content, 'personas:') !== false;
                $hasTransitionRules = strpos($content, 'transition_rules:') !== false;

                $this->recordTest(
                    'rules.yaml 구조',
                    $hasPersonas && $hasTransitionRules,
                    ($hasPersonas && $hasTransitionRules)
                        ? 'personas, transition_rules 섹션 확인됨'
                        : 'YAML 규칙 구조 불완전'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'rules.yaml 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testErrorClassifications(): void
        {
            $expectedErrors = [
                'concept_confusion',
                'calculation_mistake',
                'reading_error',
                'process_error',
                'careless_mistake',
                'basic_gap'
            ];

            try {
                $filePath = __DIR__ . '/rules/rules.yaml';
                $content = file_get_contents($filePath);

                $hasErrorClassifications = strpos($content, 'error_classifications:') !== false;

                $this->recordTest(
                    '오류 분류 섹션',
                    $hasErrorClassifications,
                    $hasErrorClassifications ? 'error_classifications 섹션 존재' : '오류 분류 섹션 없음'
                );

                $foundErrors = 0;
                foreach ($expectedErrors as $error) {
                    if (strpos($content, $error . ':') !== false) {
                        $foundErrors++;
                    }
                }

                $this->recordTest(
                    '오류 유형 정의',
                    $foundErrors >= 5,
                    "{$foundErrors}/" . count($expectedErrors) . " 오류 유형 정의됨"
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '오류 분류 테스트',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }
    }

// =========================================================================
// HTML 출력
// =========================================================================
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $agentEmoji; ?> Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?> - 통합 테스트</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8ec 100%);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%);
            color: white;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header h1 {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .header p {
            opacity: 0.9;
            font-size: 0.95em;
        }

        .tabs {
            display: flex;
            background: white;
            border-bottom: 2px solid #e67e22;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .tab {
            flex: 1;
            padding: 15px 20px;
            text-align: center;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
            text-decoration: none;
        }

        .tab:hover {
            background: #fff8f0;
            color: #e67e22;
        }

        .tab.active {
            color: #e67e22;
            border-bottom-color: #e67e22;
            background: #fff8f0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* 채팅 탭 스타일 */
        .chat-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 20px;
            height: calc(100vh - 200px);
            min-height: 500px;
        }

        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow-y: auto;
        }

        .sidebar h3 {
            color: #e67e22;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ffecd2;
            font-size: 1em;
        }

        .context-item {
            padding: 10px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 4px solid transparent;
        }

        .context-item:hover {
            background: #fff8f0;
            transform: translateX(3px);
        }

        .context-item.active {
            background: #fff8f0;
            border-left-color: #e67e22;
        }

        .context-name {
            font-weight: 600;
            font-size: 0.9em;
            margin-bottom: 3px;
        }

        .context-desc {
            font-size: 0.75em;
            color: #888;
        }

        .persona-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .persona-item {
            padding: 8px 10px;
            margin-bottom: 5px;
            font-size: 0.85em;
            border-radius: 6px;
            background: #fafafa;
        }

        .chat-main {
            display: flex;
            flex-direction: column;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #fafafa;
        }

        .message {
            margin-bottom: 15px;
            display: flex;
            gap: 10px;
        }

        .message.user {
            flex-direction: row-reverse;
        }

        .message-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1em;
            flex-shrink: 0;
        }

        .message.user .message-avatar {
            background: #e67e22;
        }

        .message.bot .message-avatar {
            background: #f39c12;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            line-height: 1.5;
        }

        .message.user .message-content {
            background: #e67e22;
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.bot .message-content {
            background: white;
            border: 1px solid #eee;
            border-bottom-left-radius: 4px;
        }

        .chat-input-area {
            padding: 15px;
            background: white;
            border-top: 1px solid #eee;
        }

        .quick-messages {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
        }

        .quick-msg {
            padding: 6px 12px;
            background: #fff8f0;
            border: 1px solid #ffecd2;
            border-radius: 20px;
            font-size: 0.8em;
            color: #e67e22;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-msg:hover {
            background: #e67e22;
            color: white;
            border-color: #e67e22;
        }

        .input-row {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #eee;
            border-radius: 25px;
            font-size: 0.95em;
            outline: none;
            transition: border-color 0.2s;
        }

        .chat-input:focus {
            border-color: #e67e22;
        }

        .send-btn {
            padding: 12px 25px;
            background: #e67e22;
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .send-btn:hover {
            background: #d35400;
            transform: scale(1.02);
        }

        .current-context {
            padding: 10px 15px;
            background: #fff8f0;
            border-bottom: 1px solid #ffecd2;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.9em;
        }

        .context-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            color: white;
        }

        /* 진단 탭은 BasePersonaTest가 렌더링 */
        .diagnosis-container {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .loading {
            display: none;
            align-items: center;
            gap: 10px;
            padding: 10px;
            color: #888;
        }

        .loading.active {
            display: flex;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #e67e22;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .server-url {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-family: monospace;
            font-size: 0.85em;
            word-break: break-all;
        }

        .server-url a {
            color: #e67e22;
            text-decoration: none;
        }

        .server-url a:hover {
            text-decoration: underline;
        }

        /* 에이전트 네비게이션 드롭다운 */
        .header-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .header-nav h1 {
            margin-bottom: 0;
        }
        .agent-dropdown {
            position: relative;
            display: inline-block;
        }
        .agent-dropdown-btn {
            padding: 12px 20px;
            background: rgba(255,255,255,0.95);
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: all 0.3s;
        }
        .agent-dropdown-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        }
        .agent-dropdown-btn::after {
            content: '▼';
            font-size: 10px;
            margin-left: 5px;
        }
        .agent-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 8px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            min-width: 280px;
            max-height: 400px;
            overflow-y: auto;
            z-index: 1000;
        }
        .agent-dropdown:hover .agent-dropdown-content {
            display: block;
        }
        .agent-dropdown-content a {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.2s;
            border-bottom: 1px solid #f0f0f0;
        }
        .agent-dropdown-content a:last-child {
            border-bottom: none;
        }
        .agent-dropdown-content a:hover {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }
        .agent-dropdown-content a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .agent-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            background: #f0f0f0;
            border-radius: 8px;
            font-size: 12px;
            font-weight: bold;
            color: #666;
        }
        .agent-dropdown-content a.active .agent-num {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .agent-emoji {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?php echo $agentEmoji; ?> Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?></h1>
        <p><?php echo $agentDescription; ?></p>
    </div>

    <div class="tabs">
        <a href="?tab=chat" class="tab <?php echo $currentTab === 'chat' ? 'active' : ''; ?>">
            💬 채팅 테스트
        </a>
        <a href="?tab=diagnosis" class="tab <?php echo $currentTab === 'diagnosis' ? 'active' : ''; ?>">
            🔍 진단 테스트
        </a>
    </div>

    <div class="container">
        <!-- 헤더 네비게이션 -->
        <div class="header-nav">
            <h1><?php echo $agentEmoji; ?> Agent<?php echo str_pad($agentNumber, 2, '0', STR_PAD_LEFT); ?> <?php echo $agentKrName; ?></h1>

            <!-- 에이전트 드롭다운 -->
            <div class="agent-dropdown">
                <button class="agent-dropdown-btn">
                    <span class="agent-emoji"><?php echo $agentEmoji; ?></span>
                    <span>에이전트 전환</span>
                </button>
                <div class="agent-dropdown-content">
                    <?php foreach ($allAgents as $agent):
                        $agentNumPadded = str_pad($agent['num'], 2, '0', STR_PAD_LEFT);
                        $agentUrl = "https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent{$agentNumPadded}_{$agent['id']}/persona_system/test_chat.php";
                        $isActive = ($agent['num'] === $agentNumber);
                    ?>
                        <a href="<?php echo $agentUrl; ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                            <span class="agent-num"><?php echo $agentNumPadded; ?></span>
                            <span class="agent-emoji"><?php echo $agent['emoji']; ?></span>
                            <span><?php echo $agent['name']; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>


        <?php if ($currentTab === 'chat'): ?>
        <!-- 채팅 테스트 탭 -->
        <div class="chat-container">
            <div class="sidebar">
                <h3>📋 오류 유형 컨텍스트</h3>
                <?php foreach ($contextCodes as $code => $info): ?>
                <div class="context-item <?php echo $code === 'default' ? 'active' : ''; ?>"
                     data-context="<?php echo $code; ?>"
                     onclick="setContext('<?php echo $code; ?>')">
                    <div class="context-name" style="color: <?php echo $info['color']; ?>">
                        <?php echo $info['name']; ?>
                    </div>
                    <div class="context-desc"><?php echo $info['desc']; ?></div>
                </div>
                <?php endforeach; ?>

                <div class="persona-section">
                    <h3>🎭 페르소나 목록</h3>
                    <?php foreach ($personaMapping as $key => $name): ?>
                    <div class="persona-item">
                        <strong><?php echo $name; ?></strong>
                        <div style="font-size:0.75em;color:#888;"><?php echo $key; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="chat-main">
                <div class="current-context">
                    <span>현재 컨텍스트:</span>
                    <span class="context-badge" id="currentContextBadge" style="background: #7f8c8d;">기본</span>
                    <span>|</span>
                    <span>페르소나: <strong id="currentPersona">분석적 조력자</strong></span>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="message bot">
                        <div class="message-avatar">📝</div>
                        <div class="message-content">
                            안녕하세요! 저는 문제노트 도우미입니다. 오답의 원인을 함께 분석하고 더 나은 학습 방법을 찾아볼게요. 어떤 문제에서 어려움을 겪고 계신가요?
                        </div>
                    </div>
                </div>

                <div class="chat-input-area">
                    <div class="quick-messages">
                        <?php foreach ($quickMessages as $qm): ?>
                        <span class="quick-msg"
                              data-context="<?php echo $qm['context']; ?>"
                              onclick="sendQuickMessage('<?php echo htmlspecialchars($qm['msg'], ENT_QUOTES); ?>', '<?php echo $qm['context']; ?>')">
                            <?php echo $qm['msg']; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>

                    <div class="input-row">
                        <input type="text" class="chat-input" id="chatInput"
                               placeholder="오답 관련 질문을 입력하세요..."
                               onkeypress="if(event.key==='Enter')sendMessage()">
                        <button class="send-btn" onclick="sendMessage()">전송</button>
                    </div>

                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <span>응답 생성 중...</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="server-url">
            <strong>API 엔드포인트:</strong><br>
            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/api/" target="_blank">
                https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/api/
            </a>
        </div>

        <?php else: ?>
        <!-- 진단 테스트 탭 -->
        <div class="diagnosis-container">
            <?php
            try {
                $test = new Agent11PersonaTest();
                $test->runAllTests();
                $test->renderHtml();
            } catch (Throwable $e) {
                echo "<div style='color:red;padding:20px;'>";
                echo "<h3>❌ 테스트 실행 실패</h3>";
                echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                echo "</div>";
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($currentTab === 'chat'): ?>
    <script>
        // 컨텍스트 설정
        const contextCodes = <?php echo json_encode($contextCodes); ?>;
        let currentContext = 'default';

        function setContext(code) {
            currentContext = code;

            // UI 업데이트
            document.querySelectorAll('.context-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.context === code) {
                    item.classList.add('active');
                }
            });

            const badge = document.getElementById('currentContextBadge');
            badge.textContent = contextCodes[code].name;
            badge.style.background = contextCodes[code].color;
        }

        function sendQuickMessage(msg, context) {
            setContext(context);
            document.getElementById('chatInput').value = msg;
            sendMessage();
        }

        function sendMessage() {
            const input = document.getElementById('chatInput');
            const message = input.value.trim();

            if (!message) return;

            // 사용자 메시지 표시
            addMessage(message, 'user');
            input.value = '';

            // 로딩 표시
            document.getElementById('loading').classList.add('active');

            // API 호출
            fetch('api/', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    message: message,
                    context: currentContext,
                    user_id: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').classList.remove('active');

                if (data.success && data.response) {
                    addMessage(data.response, 'bot');

                    // 페르소나 업데이트
                    if (data.persona) {
                        const personaNames = <?php echo json_encode($personaMapping); ?>;
                        document.getElementById('currentPersona').textContent =
                            personaNames[data.persona] || data.persona;
                    }
                } else {
                    addMessage('죄송합니다. 응답을 생성하는 중 오류가 발생했습니다: ' + (data.error || '알 수 없는 오류'), 'bot');
                }
            })
            .catch(error => {
                document.getElementById('loading').classList.remove('active');
                addMessage('네트워크 오류가 발생했습니다: ' + error.message, 'bot');
            });
        }

        function addMessage(text, type) {
            const container = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = 'message ' + type;

            const avatar = type === 'user' ? '👤' : '📝';

            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">${escapeHtml(text)}</div>
            `;

            container.appendChild(messageDiv);
            container.scrollTop = container.scrollHeight;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    <?php endif; ?>
</body>
</html>
<?php
/*
 * =========================================================================
 * 사용법
 * =========================================================================
 *
 * 채팅 테스트 (기본):
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test_chat.php
 *
 * 채팅 테스트 탭:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test_chat.php?tab=chat
 *
 * 진단 테스트 탭:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test_chat.php?tab=diagnosis
 *
 * =========================================================================
 */
