<?php
/**
 * test_chat.php
 *
 * Agent12 휴식 루틴 페르소나 시스템 통합 테스트
 * 탭1: 채팅 테스트 (API 기반)
 * 탭2: 진단 테스트 (BasePersonaTest 상속)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent12RestRoutine
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test_chat.php
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
$agentNumber = 12;
$agentName = 'rest_routine';
$agentKrName = '휴식 루틴';
$agentEmoji = '☕';
$agentDescription = '학습과 휴식의 균형을 찾아 효과적인 휴식 루틴을 만들어 드립니다.';

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

// 컨텍스트 코드 정의 (휴식 패턴 기반)
$contextCodes = [
    'regular_rest' => ['name' => '규칙적 휴식', 'color' => '#27ae60', 'desc' => '적절한 간격으로 휴식을 취하고 있는 상태', 'mode' => 'maintain'],
    'activity_centered_rest' => ['name' => '활동 중심 휴식', 'color' => '#3498db', 'desc' => '휴식 중 활동적인 행동을 선호하는 패턴', 'mode' => 'optimize'],
    'immersive_rest' => ['name' => '몰입형 휴식', 'color' => '#f39c12', 'desc' => '한 번에 긴 휴식을 취하는 패턴', 'mode' => 'restructure'],
    'no_rest' => ['name' => '휴식 없음', 'color' => '#e74c3c', 'desc' => '휴식을 거의 취하지 않는 위험 상태', 'mode' => 'establish'],
    'fatigue_low' => ['name' => '낮은 피로도', 'color' => '#2ecc71', 'desc' => '피로도 지수 0-30'],
    'fatigue_medium' => ['name' => '중간 피로도', 'color' => '#f1c40f', 'desc' => '피로도 지수 31-60'],
    'fatigue_high' => ['name' => '높은 피로도', 'color' => '#e67e22', 'desc' => '피로도 지수 61-80'],
    'fatigue_critical' => ['name' => '위험 피로도', 'color' => '#c0392b', 'desc' => '피로도 지수 81-100'],
    'default' => ['name' => '기본', 'color' => '#7f8c8d', 'desc' => '일반 휴식 상담']
];

// 코칭 톤 정의
$coachingTones = [
    'supportive' => '지지적',
    'balanced' => '균형적',
    'coaching' => '코칭형',
    'intervention' => '개입형'
];

// 빠른 메시지 목록
$quickMessages = [
    ['msg' => '공부할 때 언제 쉬어야 할지 모르겠어요', 'context' => 'no_rest'],
    ['msg' => '휴식 시간이 너무 길어지는 것 같아요', 'context' => 'immersive_rest'],
    ['msg' => '규칙적으로 쉬고 있는데 효과가 있나요?', 'context' => 'regular_rest'],
    ['msg' => '휴식 중에 뭘 해야 할지 모르겠어요', 'context' => 'activity_centered_rest'],
    ['msg' => '피곤해서 집중이 안 돼요', 'context' => 'fatigue_high'],
    ['msg' => '번아웃이 온 것 같아요', 'context' => 'fatigue_critical'],
    ['msg' => '효과적인 휴식 방법이 뭔가요?', 'context' => 'default'],
    ['msg' => '포모도로 기법을 사용하고 있어요', 'context' => 'regular_rest']
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
class Agent12PersonaTest extends BasePersonaTest
{
        public function __construct()
        {
            parent::__construct(
                12,
                'rest_routine',
                '휴식 루틴',
                __DIR__
            );
        }

        protected function getRequiredFiles(): array
        {
            return [
                'Agent12PersonaEngine.php' => 'PersonaEngine 메인 클래스',
                'Agent12DataContext.php' => 'DataContext 데이터 접근 클래스',
                'api/chat.php' => '채팅 API 엔드포인트',
                'personas.md' => '페르소나 정의 문서'
            ];
        }

        protected function getRequiredTables(): array
        {
            return [
                'mdl_at_agent12_rest_sessions' => '휴식 세션 테이블',
                'mdl_at_agent12_routine_history' => '루틴 히스토리 테이블',
                'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
            ];
        }

        protected function runCustomTests(): void
        {
            $this->testPersonaEngineLoad();
            $this->testDataContextLoad();
            $this->testRestPatternLevels();
            $this->testRestStrategyMapping();
            $this->testFatigueIndexLogic();
            $this->testApiEndpoint('api/chat.php', 'GET');
            $this->testFileExists('personas.md', '페르소나 정의 문서');
        }

        private function testPersonaEngineLoad(): void
        {
            $filePath = __DIR__ . '/Agent12PersonaEngine.php';

            if (!file_exists($filePath)) {
                $this->recordTest(
                    'PersonaEngine 클래스 로드',
                    false,
                    '파일 없음: Agent12PersonaEngine.php [' . __FILE__ . ':' . __LINE__ . ']'
                );
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class Agent12PersonaEngine') !== false;
                $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

                $this->recordTest(
                    'Agent12PersonaEngine 클래스 정의',
                    $hasClass,
                    $hasClass ? 'Agent12PersonaEngine 클래스 발견' : '클래스 정의 없음'
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

        private function testDataContextLoad(): void
        {
            $filePath = __DIR__ . '/Agent12DataContext.php';

            if (!file_exists($filePath)) {
                $this->recordTest(
                    'DataContext 클래스 로드',
                    false,
                    '파일 없음: Agent12DataContext.php [' . __FILE__ . ':' . __LINE__ . ']'
                );
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class Agent12DataContext') !== false;
                $hasCollect = strpos($content, 'collect') !== false;

                $this->recordTest(
                    'Agent12DataContext 클래스 정의',
                    $hasClass,
                    $hasClass ? 'Agent12DataContext 클래스 발견' : '클래스 정의 없음'
                );

                $this->recordTest(
                    'DataContext collect 메서드',
                    $hasCollect,
                    $hasCollect ? 'collect 메서드 존재' : 'collect 메서드 없음'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    'DataContext 클래스 로드',
                    false,
                    '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testRestPatternLevels(): void
        {
            $expectedLevels = [
                'regular_rest',
                'activity_centered_rest',
                'immersive_rest',
                'no_rest'
            ];

            try {
                $filePath = __DIR__ . '/Agent12PersonaEngine.php';
                $content = file_get_contents($filePath);

                $foundLevels = 0;
                foreach ($expectedLevels as $level) {
                    if (strpos($content, "'" . $level . "'") !== false ||
                        strpos($content, '"' . $level . '"') !== false) {
                        $foundLevels++;
                    }
                }

                $this->recordTest(
                    '휴식 패턴 레벨 정의',
                    $foundLevels === 4,
                    $foundLevels === 4
                        ? '4개 휴식 패턴 모두 정의됨'
                        : "{$foundLevels}/4 휴식 패턴 발견"
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '휴식 패턴 레벨 정의',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testRestStrategyMapping(): void
        {
            $expectedModes = ['maintain', 'optimize', 'restructure', 'establish'];

            try {
                $filePath = __DIR__ . '/Agent12PersonaEngine.php';
                $content = file_get_contents($filePath);

                $foundModes = 0;
                foreach ($expectedModes as $mode) {
                    if (strpos($content, "'" . $mode . "'") !== false ||
                        strpos($content, '"' . $mode . '"') !== false) {
                        $foundModes++;
                    }
                }

                $this->recordTest(
                    '휴식 전략 모드 정의',
                    $foundModes >= 3,
                    "{$foundModes}/" . count($expectedModes) . " 전략 모드 정의됨"
                );

                // 코칭 톤 확인
                $coachingTones = ['supportive', 'balanced', 'coaching', 'intervention'];
                $foundTones = 0;
                foreach ($coachingTones as $tone) {
                    if (strpos($content, "'" . $tone . "'") !== false ||
                        strpos($content, '"' . $tone . '"') !== false) {
                        $foundTones++;
                    }
                }

                $this->recordTest(
                    '코칭 톤 정의',
                    $foundTones >= 3,
                    "{$foundTones}/" . count($coachingTones) . " 코칭 톤 정의됨"
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '휴식 전략 매핑',
                    false,
                    '확인 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
                );
            }
        }

        private function testFatigueIndexLogic(): void
        {
            try {
                $filePath = __DIR__ . '/Agent12PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasFatigueCalculation = strpos($content, 'fatigue') !== false ||
                                        strpos($content, 'Fatigue') !== false;

                $this->recordTest(
                    '피로도 계산 로직',
                    $hasFatigueCalculation,
                    $hasFatigueCalculation ? '피로도 관련 로직 존재' : '피로도 계산 로직 없음'
                );

                // 휴식 간격 분석 로직 확인
                $hasIntervalLogic = strpos($content, 'interval') !== false ||
                                   strpos($content, 'avg_interval') !== false;

                $this->recordTest(
                    '휴식 간격 분석',
                    $hasIntervalLogic,
                    $hasIntervalLogic ? '휴식 간격 분석 로직 존재' : '간격 분석 로직 없음'
                );

            } catch (Throwable $e) {
                $this->recordTest(
                    '피로도 지수 로직',
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
            background: linear-gradient(135deg, #1abc9c 0%, #16a085 100%);
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
            border-bottom: 2px solid #1abc9c;
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
            background: #e8f8f5;
            color: #1abc9c;
        }

        .tab.active {
            color: #1abc9c;
            border-bottom-color: #1abc9c;
            background: #e8f8f5;
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
            color: #1abc9c;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #d5f5e3;
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
            background: #e8f8f5;
            transform: translateX(3px);
        }

        .context-item.active {
            background: #e8f8f5;
            border-left-color: #1abc9c;
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

        .coaching-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .coaching-item {
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
            background: #1abc9c;
        }

        .message.bot .message-avatar {
            background: #16a085;
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 12px;
            line-height: 1.5;
        }

        .message.user .message-content {
            background: #1abc9c;
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
            background: #e8f8f5;
            border: 1px solid #d5f5e3;
            border-radius: 20px;
            font-size: 0.8em;
            color: #1abc9c;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-msg:hover {
            background: #1abc9c;
            color: white;
            border-color: #1abc9c;
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
            border-color: #1abc9c;
        }

        .send-btn {
            padding: 12px 25px;
            background: #1abc9c;
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .send-btn:hover {
            background: #16a085;
            transform: scale(1.02);
        }

        .current-context {
            padding: 10px 15px;
            background: #e8f8f5;
            border-bottom: 1px solid #d5f5e3;
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
            border-top: 2px solid #1abc9c;
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
            color: #1abc9c;
            text-decoration: none;
        }

        .server-url a:hover {
            text-decoration: underline;
        }

        .fatigue-meter {
            margin-top: 20px;
            padding: 15px;
            background: #fafafa;
            border-radius: 8px;
        }

        .fatigue-bar {
            height: 10px;
            background: #eee;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .fatigue-fill {
            height: 100%;
            width: 30%;
            background: linear-gradient(90deg, #27ae60, #f1c40f, #e74c3c);
            transition: width 0.3s;
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
                <h3>🌿 휴식 패턴 컨텍스트</h3>
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

                <div class="coaching-section">
                    <h3>🎯 코칭 톤</h3>
                    <?php foreach ($coachingTones as $key => $name): ?>
                    <div class="coaching-item">
                        <strong><?php echo $name; ?></strong>
                        <div style="font-size:0.75em;color:#888;"><?php echo $key; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="fatigue-meter">
                    <strong>피로도 미터</strong>
                    <div class="fatigue-bar">
                        <div class="fatigue-fill" id="fatigueFill"></div>
                    </div>
                    <div style="font-size:0.8em;color:#888;margin-top:5px;">
                        현재: <span id="fatigueLevel">30%</span>
                    </div>
                </div>
            </div>

            <div class="chat-main">
                <div class="current-context">
                    <span>현재 컨텍스트:</span>
                    <span class="context-badge" id="currentContextBadge" style="background: #7f8c8d;">기본</span>
                    <span>|</span>
                    <span>코칭 톤: <strong id="currentTone">지지적</strong></span>
                </div>

                <div class="chat-messages" id="chatMessages">
                    <div class="message bot">
                        <div class="message-avatar">☕</div>
                        <div class="message-content">
                            안녕하세요! 저는 휴식 루틴 도우미입니다. 효과적인 학습을 위해서는 적절한 휴식이 필요해요. 지금 어떤 휴식 관련 고민이 있으신가요?
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
                               placeholder="휴식 관련 질문을 입력하세요..."
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
            <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/api/chat.php" target="_blank">
                https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/api/chat.php
            </a>
        </div>

        <?php else: ?>
        <!-- 진단 테스트 탭 -->
        <div class="diagnosis-container">
            <?php
            try {
                $test = new Agent12PersonaTest();
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
        const coachingTones = <?php echo json_encode($coachingTones); ?>;
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

            // 피로도 미터 업데이트
            updateFatigueMeter(code);
        }

        function updateFatigueMeter(code) {
            let fatigue = 30;
            if (code === 'fatigue_low' || code === 'regular_rest') fatigue = 20;
            else if (code === 'fatigue_medium' || code === 'activity_centered_rest') fatigue = 45;
            else if (code === 'fatigue_high' || code === 'immersive_rest') fatigue = 70;
            else if (code === 'fatigue_critical' || code === 'no_rest') fatigue = 90;

            document.getElementById('fatigueFill').style.width = fatigue + '%';
            document.getElementById('fatigueLevel').textContent = fatigue + '%';
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
            fetch('api/chat.php', {
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

                    // 코칭 톤 업데이트
                    if (data.coaching_tone) {
                        document.getElementById('currentTone').textContent =
                            coachingTones[data.coaching_tone] || data.coaching_tone;
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

            const avatar = type === 'user' ? '👤' : '☕';

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
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test_chat.php
 *
 * 채팅 테스트 탭:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test_chat.php?tab=chat
 *
 * 진단 테스트 탭:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent12_rest_routine/persona_system/test_chat.php?tab=diagnosis
 *
 * =========================================================================
 */
