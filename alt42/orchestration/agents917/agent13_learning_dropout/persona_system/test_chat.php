<?php
/**
 * test_chat.php
 *
 * Agent13 학습 이탈 페르소나 시스템 통합 테스트
 * 탭 1: 채팅 API 테스트 (대화형 인터페이스)
 * 탭 2: 진단 테스트 (BasePersonaTest 프레임워크)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent13LearningDropout
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test_chat.php
 */

// =========================================================================
// BasePersonaTest 의존성 (파일 최상위 레벨에 배치)
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

// =========================================================================
// 에이전트별 설정 (Agent13 학습 이탈 전용)
// =========================================================================

$agentEmoji = '🚨';

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

$agentConfig = [
    'number' => 13,
    'id' => 'learning_dropout',
    'name' => '학습 이탈',
    'description' => '학습 이탈 위험 감지 및 개입 에이전트',
    'color_primary' => '#e74c3c',      // 레드 (위험/경고)
    'color_secondary' => '#c0392b',    // 다크 레드
    'color_accent' => '#e67e22',       // 오렌지 (주의)
    'api_endpoint' => 'api/chat.php',

    // 위험 등급 (Risk Tiers)
    'risk_tiers' => [
        'Low' => ['score_range' => '0-30', 'color' => '#27ae60', 'label' => '저위험'],
        'Medium' => ['score_range' => '31-60', 'color' => '#f39c12', 'label' => '중위험'],
        'High' => ['score_range' => '61-100', 'color' => '#e74c3c', 'label' => '고위험'],
        'Critical' => ['score_range' => 'N/A', 'color' => '#8e44ad', 'label' => '위기상태']
    ],

    // 이탈 원인 (Dropout Causes)
    'dropout_causes' => [
        'M' => ['name' => 'Motivation', 'label' => '동기 저하', 'icon' => '💭'],
        'R' => ['name' => 'Routine', 'label' => '루틴 붕괴', 'icon' => '⏰'],
        'S' => ['name' => 'Start Barrier', 'label' => '시작 장벽', 'icon' => '🚧'],
        'E' => ['name' => 'External', 'label' => '외부 요인', 'icon' => '🌐']
    ],

    // 12개 혼합형 페르소나
    'personas' => [
        // Low Risk
        'L_M' => ['tier' => 'Low', 'cause' => 'M', 'name' => '예방적 동기 케어', 'mode' => 'encourage'],
        'L_R' => ['tier' => 'Low', 'cause' => 'R', 'name' => '루틴 유지 도우미', 'mode' => 'remind'],
        'L_S' => ['tier' => 'Low', 'cause' => 'S', 'name' => '워밍업 가이드', 'mode' => 'guide'],
        'L_E' => ['tier' => 'Low', 'cause' => 'E', 'name' => '환경 조율자', 'mode' => 'adapt'],
        // Medium Risk
        'M_M' => ['tier' => 'Medium', 'cause' => 'M', 'name' => '동기 부스터', 'mode' => 'motivate'],
        'M_R' => ['tier' => 'Medium', 'cause' => 'R', 'name' => '루틴 복구 코치', 'mode' => 'restructure'],
        'M_S' => ['tier' => 'Medium', 'cause' => 'S', 'name' => '시작 도우미', 'mode' => 'scaffold'],
        'M_E' => ['tier' => 'Medium', 'cause' => 'E', 'name' => '환경 적응 매니저', 'mode' => 'accommodate'],
        // High Risk
        'H_M' => ['tier' => 'High', 'cause' => 'M', 'name' => '동기 회복 전문가', 'mode' => 'reconnect'],
        'H_R' => ['tier' => 'High', 'cause' => 'R', 'name' => '루틴 재건 전문가', 'mode' => 'rebuild'],
        'H_S' => ['tier' => 'High', 'cause' => 'S', 'name' => '시작 불안 해소 전문가', 'mode' => 'hand_hold'],
        'H_E' => ['tier' => 'High', 'cause' => 'E', 'name' => '환경 위기 관리자', 'mode' => 'adapt_urgent']
    ],

    // 코칭 톤
    'coaching_tones' => ['supportive', 'encouraging', 'understanding', 'caring', 'coaching', 'gentle', 'urgent_caring'],

    // 빠른 메시지
    'quick_messages' => [
        '공부하기 싫어요...',
        '시작하기가 너무 어려워요',
        '학원 때문에 시간이 없어요',
        '루틴이 다 망가졌어요',
        '오늘도 또 미뤘어요',
        '포기하고 싶어요',
        '왜 해야 하는지 모르겠어요',
        '너무 피곤해서 못하겠어요'
    ]
];

// =========================================================================
// 공통 설정
// =========================================================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// 현재 탭 결정
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';
$validTabs = ['chat', 'diagnosis'];
if (!in_array($currentTab, $validTabs)) {
    $currentTab = 'chat';
}

// =========================================================================
// 진단 탭용 테스트 클래스
// =========================================================================

class Agent13ChatTest extends BasePersonaTest
{
        public function __construct()
        {
            parent::__construct(
                13,
                'learning_dropout',
                '학습 이탈',
                __DIR__
            );
        }

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

        protected function getRequiredTables(): array
        {
            return [
                'mdl_at_agent13_dropout_risk' => '이탈 위험 기록 테이블',
                'mdl_at_agent13_intervention_log' => '개입 기록 테이블',
                'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
            ];
        }

        protected function runCustomTests(): void
        {
            $this->testPersonaEngineLoad();
            $this->testDataContextLoad();
            $this->testRiskTierDefinitions();
            $this->testHybridPersonaMatrix();
            $this->testDropoutCauseDetection();
            $this->testInterventionStrategies();
            $this->testApiEndpoint('api/chat.php', 'GET');
            $this->testFileExists('personas.md', '페르소나 정의 문서');
        }

        private function testPersonaEngineLoad(): void
        {
            $filePath = __DIR__ . '/Agent13PersonaEngine.php';

            if (!file_exists($filePath)) {
                $this->recordTest('PersonaEngine 클래스 로드', false, '파일 없음: Agent13PersonaEngine.php');
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class Agent13PersonaEngine') !== false;
                $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

                $this->recordTest(
                    'Agent13PersonaEngine 클래스 정의',
                    $hasClass,
                    $hasClass ? 'Agent13PersonaEngine 클래스 발견' : '클래스 정의 없음'
                );

                $this->recordTest(
                    'AbstractPersonaEngine 상속',
                    $extendsAbstract,
                    $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
                );
            } catch (Throwable $e) {
                $this->recordTest('PersonaEngine 클래스 로드', false, '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']');
            }
        }

        private function testDataContextLoad(): void
        {
            $filePath = __DIR__ . '/Agent13DataContext.php';

            if (!file_exists($filePath)) {
                $this->recordTest('DataContext 클래스 로드', false, '파일 없음: Agent13DataContext.php');
                return;
            }

            try {
                $content = file_get_contents($filePath);
                $hasClass = strpos($content, 'class Agent13DataContext') !== false;
                $hasCollect = strpos($content, 'collect') !== false;

                $this->recordTest(
                    'Agent13DataContext 클래스',
                    $hasClass,
                    $hasClass ? 'Agent13DataContext 클래스 정의됨' : '클래스 없음'
                );

                $this->recordTest(
                    'DataContext collect 메서드',
                    $hasCollect,
                    $hasCollect ? 'collect 메서드 정의됨' : 'collect 메서드 없음'
                );
            } catch (Throwable $e) {
                $this->recordTest('DataContext 클래스 로드', false, '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']');
            }
        }

        private function testRiskTierDefinitions(): void
        {
            $expectedTiers = ['Low', 'Medium', 'High', 'Critical'];

            try {
                $filePath = __DIR__ . '/Agent13PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasRiskTiers = strpos($content, 'riskTierThresholds') !== false ||
                               strpos($content, 'risk_tier') !== false;

                $this->recordTest(
                    '위험 수준 프로퍼티',
                    $hasRiskTiers,
                    $hasRiskTiers ? 'riskTierThresholds 프로퍼티 정의됨' : 'riskTierThresholds 없음'
                );

                $foundTiers = 0;
                foreach ($expectedTiers as $tier) {
                    if (stripos($content, "'" . $tier . "'") !== false ||
                        stripos($content, '"' . $tier . '"') !== false) {
                        $foundTiers++;
                    }
                }

                $this->recordTest(
                    '위험 수준 완성도',
                    $foundTiers >= 3,
                    "{$foundTiers}/" . count($expectedTiers) . " 위험 수준 정의됨",
                    ['expected' => $expectedTiers, 'found' => $foundTiers]
                );
            } catch (Throwable $e) {
                $this->recordTest('위험 수준 테스트', false, '확인 실패: ' . $e->getMessage());
            }
        }

        private function testHybridPersonaMatrix(): void
        {
            // 12개 혼합형 페르소나 (3 Tiers × 4 Causes)
            $expectedPersonas = [
                'L_M', 'L_R', 'L_S', 'L_E',
                'M_M', 'M_R', 'M_S', 'M_E',
                'H_M', 'H_R', 'H_S', 'H_E'
            ];

            try {
                $filePath = __DIR__ . '/Agent13PersonaEngine.php';
                $content = file_get_contents($filePath);

                $foundPersonas = 0;
                foreach ($expectedPersonas as $persona) {
                    if (strpos($content, "'" . $persona . "'") !== false ||
                        strpos($content, '"' . $persona . '"') !== false) {
                        $foundPersonas++;
                    }
                }

                $this->recordTest(
                    '혼합형 페르소나 매트릭스',
                    $foundPersonas >= 10,
                    "{$foundPersonas}/12 혼합형 페르소나 정의됨"
                );

                // 이탈 원인 코드 확인
                $causes = ['M', 'R', 'S', 'E'];
                $foundCauses = 0;
                foreach ($causes as $cause) {
                    if (strpos($content, "dropout_cause") !== false ||
                        strpos($content, "'" . $cause . "'") !== false) {
                        $foundCauses++;
                    }
                }

                $this->recordTest(
                    '이탈 원인 분류',
                    $foundCauses >= 3,
                    "{$foundCauses}/4 이탈 원인 분류 확인"
                );
            } catch (Throwable $e) {
                $this->recordTest('혼합형 페르소나 테스트', false, '확인 실패: ' . $e->getMessage());
            }
        }

        private function testDropoutCauseDetection(): void
        {
            $causeIndicators = [
                'M' => ['motivation', 'motivate', '동기'],
                'R' => ['routine', 'nlazy_blocks', '루틴'],
                'S' => ['start', 'tlaststroke', '시작'],
                'E' => ['external', '외부']
            ];

            try {
                $filePath = __DIR__ . '/Agent13PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasDetection = strpos($content, 'calculateMotivationScore') !== false ||
                               strpos($content, 'detectCause') !== false ||
                               strpos($content, 'cause_scores') !== false;

                $this->recordTest(
                    '원인 감지 로직',
                    $hasDetection,
                    $hasDetection ? '원인 감지 로직 구현됨' : '원인 감지 로직 미구현'
                );

            } catch (Throwable $e) {
                $this->recordTest('원인 감지 테스트', false, '확인 실패: ' . $e->getMessage());
            }
        }

        private function testInterventionStrategies(): void
        {
            $expectedModes = [
                'encourage', 'remind', 'guide', 'adapt',
                'motivate', 'restructure', 'scaffold', 'accommodate',
                'reconnect', 'rebuild', 'hand_hold', 'adapt_urgent'
            ];

            try {
                $filePath = __DIR__ . '/Agent13PersonaEngine.php';
                $content = file_get_contents($filePath);

                $hasStrategies = strpos($content, 'interventionStrategies') !== false ||
                                strpos($content, 'intervention_mode') !== false;

                $this->recordTest(
                    '개입 전략 프로퍼티',
                    $hasStrategies,
                    $hasStrategies ? 'interventionStrategies 정의됨' : 'interventionStrategies 없음'
                );

                $foundModes = 0;
                foreach ($expectedModes as $mode) {
                    if (strpos($content, "'" . $mode . "'") !== false ||
                        strpos($content, '"' . $mode . '"') !== false) {
                        $foundModes++;
                    }
                }

                $this->recordTest(
                    '개입 모드 완성도',
                    $foundModes >= 8,
                    "{$foundModes}/" . count($expectedModes) . " 개입 모드 정의됨"
                );

            } catch (Throwable $e) {
                $this->recordTest('개입 전략 테스트', false, '확인 실패: ' . $e->getMessage());
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
    <title>Agent<?= sprintf('%02d', $agentConfig['number']) ?> <?= $agentConfig['name'] ?> - 테스트</title>
    <style>
        :root {
            --primary: <?= $agentConfig['color_primary'] ?>;
            --secondary: <?= $agentConfig['color_secondary'] ?>;
            --accent: <?= $agentConfig['color_accent'] ?>;
            --bg: #1a1a2e;
            --surface: #16213e;
            --text: #eee;
            --text-muted: #888;
            --border: #333;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid var(--accent);
        }

        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .header .subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        /* Tab Navigation */
        .tab-nav {
            display: flex;
            background: var(--surface);
            border-bottom: 1px solid var(--border);
        }

        .tab-btn {
            flex: 1;
            padding: 15px;
            border: none;
            background: transparent;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }

        .tab-btn:hover {
            background: rgba(255,255,255,0.05);
            color: var(--text);
        }

        .tab-btn.active {
            color: var(--primary);
            border-bottom-color: var(--primary);
            background: rgba(231, 76, 60, 0.1);
        }

        .tab-content {
            display: none;
            padding: 20px;
        }

        .tab-content.active {
            display: block;
        }

        /* Risk Indicator Panel */
        .risk-panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }

        .risk-panel h3 {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .risk-tiers {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .risk-tier {
            flex: 1;
            min-width: 70px;
            padding: 8px 12px;
            border-radius: 8px;
            text-align: center;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .risk-tier:hover {
            transform: scale(1.05);
        }

        .risk-tier.active {
            border-color: white;
            box-shadow: 0 0 10px rgba(255,255,255,0.3);
        }

        .risk-tier .tier-name {
            font-weight: bold;
            margin-bottom: 3px;
        }

        .risk-tier .tier-score {
            opacity: 0.8;
            font-size: 0.65rem;
        }

        /* Dropout Cause Selector */
        .cause-panel {
            background: var(--surface);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }

        .cause-panel h3 {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 10px;
        }

        .cause-buttons {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .cause-btn {
            padding: 10px;
            border: 2px solid var(--border);
            border-radius: 8px;
            background: rgba(255,255,255,0.05);
            color: var(--text);
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }

        .cause-btn:hover {
            background: rgba(255,255,255,0.1);
        }

        .cause-btn.active {
            border-color: var(--primary);
            background: rgba(231, 76, 60, 0.2);
        }

        .cause-btn .cause-icon {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .cause-btn .cause-code {
            font-weight: bold;
            font-size: 0.9rem;
        }

        .cause-btn .cause-label {
            font-size: 0.7rem;
            color: var(--text-muted);
        }

        /* Current Persona Display */
        .persona-display {
            background: linear-gradient(135deg, var(--surface) 0%, rgba(231, 76, 60, 0.1) 100%);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--primary);
            text-align: center;
        }

        .persona-display .persona-code {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
        }

        .persona-display .persona-name {
            font-size: 1rem;
            margin-top: 5px;
        }

        .persona-display .persona-mode {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Chat Container */
        .chat-container {
            background: var(--surface);
            border-radius: 12px;
            height: 300px;
            overflow-y: auto;
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
        }

        .message {
            margin-bottom: 12px;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            text-align: right;
        }

        .message.agent {
            text-align: left;
        }

        .message-content {
            display: inline-block;
            padding: 10px 15px;
            border-radius: 12px;
            max-width: 85%;
        }

        .message.user .message-content {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-bottom-right-radius: 4px;
        }

        .message.agent .message-content {
            background: rgba(255,255,255,0.1);
            border: 1px solid var(--border);
            border-bottom-left-radius: 4px;
        }

        .message-meta {
            font-size: 0.7rem;
            color: var(--text-muted);
            margin-top: 3px;
        }

        /* Quick Messages */
        .quick-messages {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .quick-msg {
            padding: 8px 12px;
            background: rgba(231, 76, 60, 0.15);
            border: 1px solid var(--primary);
            border-radius: 20px;
            color: var(--text);
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.3s;
        }

        .quick-msg:hover {
            background: rgba(231, 76, 60, 0.3);
            transform: scale(1.02);
        }

        /* Input Area */
        .input-area {
            display: flex;
            gap: 10px;
        }

        .input-area input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            color: var(--text);
            font-size: 1rem;
        }

        .input-area input:focus {
            outline: none;
            border-color: var(--primary);
        }

        .input-area button {
            padding: 12px 25px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .input-area button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        /* API Info */
        .api-info {
            background: var(--surface);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            border: 1px solid var(--border);
        }

        .api-info h4 {
            color: var(--primary);
            margin-bottom: 10px;
            font-size: 0.9rem;
        }

        .api-url {
            font-family: monospace;
            font-size: 0.75rem;
            background: rgba(0,0,0,0.3);
            padding: 8px;
            border-radius: 4px;
            word-break: break-all;
        }

        /* Diagnosis Tab Styles */
        .diagnosis-container {
            max-width: 100%;
        }

        .test-result {
            background: var(--surface);
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            border-left: 4px solid var(--border);
        }

        .test-result.pass { border-left-color: var(--success); }
        .test-result.fail { border-left-color: var(--danger); }

        .test-result .test-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .test-result .test-message {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .summary-box {
            background: linear-gradient(135deg, var(--surface), rgba(231, 76, 60, 0.1));
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid var(--primary);
        }

        .summary-box .score {
            font-size: 3rem;
            font-weight: bold;
        }

        .summary-box .score.good { color: var(--success); }
        .summary-box .score.warning { color: var(--warning); }
        .summary-box .score.bad { color: var(--danger); }

        /* Links */
        .links-section {
            margin-top: 20px;
            padding: 15px;
            background: var(--surface);
            border-radius: 8px;
        }

        .links-section a {
            color: var(--primary);
            text-decoration: none;
            display: block;
            padding: 5px 0;
            font-size: 0.85rem;
        }

        .links-section a:hover {
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
        <h1>🚨 Agent<?= sprintf('%02d', $agentConfig['number']) ?> <?= $agentConfig['name'] ?></h1>
        <div class="subtitle"><?= $agentConfig['description'] ?></div>
    </div>

    <div class="tab-nav">
        <button class="tab-btn <?= $currentTab === 'chat' ? 'active' : '' ?>" onclick="location.href='?tab=chat'">
            💬 채팅 테스트
        </button>
        <button class="tab-btn <?= $currentTab === 'diagnosis' ? 'active' : '' ?>" onclick="location.href='?tab=diagnosis'">
            🔍 진단 테스트
        </button>
    </div>

    <?php if ($currentTab === 'chat'): ?>
    <!-- 채팅 테스트 탭 -->
    <div class="tab-content active" id="chat-tab">

        <!-- 헤더 네비게이션 -->
        <div class="header-nav">
            <h1><?php echo $agentEmoji; ?> Agent<?php echo str_pad($agentConfig['number'], 2, '0', STR_PAD_LEFT); ?> <?php echo $agentConfig['name'] ?></h1>

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
                        $isActive = ($agent['num'] === $agentConfig['number']);
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



        <!-- 위험 등급 선택 -->
        <div class="risk-panel">
            <h3>📊 위험 등급 (Risk Tier)</h3>
            <div class="risk-tiers">
                <?php foreach ($agentConfig['risk_tiers'] as $tierId => $tier): ?>
                <div class="risk-tier <?= $tierId === 'Medium' ? 'active' : '' ?>"
                     style="background: <?= $tier['color'] ?>;"
                     data-tier="<?= $tierId ?>"
                     onclick="selectRiskTier('<?= $tierId ?>')">
                    <div class="tier-name"><?= $tier['label'] ?></div>
                    <div class="tier-score"><?= $tier['score_range'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 이탈 원인 선택 -->
        <div class="cause-panel">
            <h3>🔍 이탈 원인 (Dropout Cause)</h3>
            <div class="cause-buttons">
                <?php foreach ($agentConfig['dropout_causes'] as $code => $cause): ?>
                <div class="cause-btn <?= $code === 'M' ? 'active' : '' ?>"
                     data-cause="<?= $code ?>"
                     onclick="selectCause('<?= $code ?>')">
                    <div class="cause-icon"><?= $cause['icon'] ?></div>
                    <div class="cause-code"><?= $code ?></div>
                    <div class="cause-label"><?= $cause['label'] ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 현재 페르소나 표시 -->
        <div class="persona-display">
            <div class="persona-code" id="current-persona-code">M_M</div>
            <div class="persona-name" id="current-persona-name">동기 부스터</div>
            <div class="persona-mode" id="current-persona-mode">모드: motivate</div>
        </div>

        <!-- 채팅 영역 -->
        <div class="chat-container" id="chat-container">
            <div class="message agent">
                <div class="message-content">
                    안녕하세요! 학습 이탈 관리 에이전트입니다. 🚨<br>
                    지금 학습이 어려우신가요? 어떤 부분에서 힘드신지 말씀해주세요.
                </div>
                <div class="message-meta">Agent13 · 방금</div>
            </div>
        </div>

        <!-- 빠른 메시지 -->
        <div class="quick-messages">
            <?php foreach ($agentConfig['quick_messages'] as $msg): ?>
            <span class="quick-msg" onclick="sendQuickMessage('<?= htmlspecialchars($msg, ENT_QUOTES) ?>')"><?= $msg ?></span>
            <?php endforeach; ?>
        </div>

        <!-- 입력 영역 -->
        <div class="input-area">
            <input type="text" id="user-input" placeholder="메시지를 입력하세요..." onkeypress="handleKeyPress(event)">
            <button onclick="sendMessage()">전송</button>
        </div>

        <!-- API 정보 -->
        <div class="api-info">
            <h4>📡 API 엔드포인트</h4>
            <div class="api-url">
                https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent<?= sprintf('%02d', $agentConfig['number']) ?>_<?= $agentConfig['id'] ?>/persona_system/<?= $agentConfig['api_endpoint'] ?>
            </div>
        </div>

        <!-- 관련 링크 -->
        <div class="links-section">
            <h4 style="margin-bottom: 10px; color: var(--text-muted);">📎 관련 링크</h4>
            <a href="test.php" target="_blank">📋 기존 테스트 (test.php)</a>
            <a href="personas.md" target="_blank">📄 페르소나 정의서</a>
            <a href="../rules/rules.yaml" target="_blank">⚙️ 규칙 파일</a>
        </div>
    </div>

    <script>
        // 설정 데이터
        const personas = <?= json_encode($agentConfig['personas'], JSON_UNESCAPED_UNICODE) ?>;
        const riskTiers = <?= json_encode($agentConfig['risk_tiers'], JSON_UNESCAPED_UNICODE) ?>;
        const dropoutCauses = <?= json_encode($agentConfig['dropout_causes'], JSON_UNESCAPED_UNICODE) ?>;

        let selectedTier = 'Medium';
        let selectedCause = 'M';

        // 위험 등급 선택
        function selectRiskTier(tier) {
            selectedTier = tier;
            document.querySelectorAll('.risk-tier').forEach(el => el.classList.remove('active'));
            document.querySelector(`.risk-tier[data-tier="${tier}"]`).classList.add('active');
            updatePersonaDisplay();
        }

        // 이탈 원인 선택
        function selectCause(cause) {
            selectedCause = cause;
            document.querySelectorAll('.cause-btn').forEach(el => el.classList.remove('active'));
            document.querySelector(`.cause-btn[data-cause="${cause}"]`).classList.add('active');
            updatePersonaDisplay();
        }

        // 페르소나 표시 업데이트
        function updatePersonaDisplay() {
            // Critical은 별도 처리
            if (selectedTier === 'Critical') {
                document.getElementById('current-persona-code').textContent = 'CRITICAL';
                document.getElementById('current-persona-name').textContent = '위기 상태 - 긴급 에스컬레이션';
                document.getElementById('current-persona-mode').textContent = '모드: emergency_escalation';
                return;
            }

            const tierCode = selectedTier.charAt(0); // L, M, H
            const personaCode = tierCode + '_' + selectedCause;
            const persona = personas[personaCode];

            if (persona) {
                document.getElementById('current-persona-code').textContent = personaCode;
                document.getElementById('current-persona-name').textContent = persona.name;
                document.getElementById('current-persona-mode').textContent = '모드: ' + persona.mode;
            }
        }

        // 메시지 전송
        function sendMessage() {
            const input = document.getElementById('user-input');
            const message = input.value.trim();

            if (!message) return;

            addMessage(message, 'user');
            input.value = '';

            // 시뮬레이션 응답
            setTimeout(() => {
                const tierCode = selectedTier === 'Critical' ? 'Critical' : selectedTier.charAt(0);
                const personaCode = selectedTier === 'Critical' ? 'CRITICAL' : tierCode + '_' + selectedCause;
                const response = generateResponse(message, personaCode);
                addMessage(response, 'agent', personaCode);
            }, 1000);
        }

        // 빠른 메시지 전송
        function sendQuickMessage(msg) {
            document.getElementById('user-input').value = msg;
            sendMessage();
        }

        // 메시지 추가
        function addMessage(content, type, personaCode = '') {
            const container = document.getElementById('chat-container');
            const div = document.createElement('div');
            div.className = 'message ' + type;

            const meta = type === 'agent'
                ? `Agent13 [${personaCode}] · 방금`
                : '나 · 방금';

            div.innerHTML = `
                <div class="message-content">${content}</div>
                <div class="message-meta">${meta}</div>
            `;

            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // 응답 생성 (시뮬레이션)
        function generateResponse(message, personaCode) {
            const responses = {
                'L_M': '꾸준히 잘하고 있어요! 🎯 오늘도 작은 목표를 세워볼까요?',
                'L_R': '학습 시간이 조금 불규칙해졌네요. ⏰ 평소 루틴대로 시작하면 더 수월할 거예요!',
                'L_S': '시작이 반이에요! 🌟 딱 5분만 쉬운 문제부터 풀어볼까요?',
                'L_E': '바쁜 하루였나 봐요. 📚 10분 미니 세션으로 시작해볼까요?',
                'M_M': '요즘 학습하기 좀 힘들었나요? 😊 오늘 목표: 딱 1개 문제만 풀어보는 건 어떨까요?',
                'M_R': '최근 학습 패턴이 불규칙해졌네요. 🔄 새로운 루틴을 만들어볼까요?',
                'M_S': '시작하기 어려울 때가 있죠. 💪 가장 쉬운 문제 1개만 풀어봐요!',
                'M_E': '요즘 많이 바쁜 것 같아요. 📆 오늘은 복습 10분만 해도 충분해요!',
                'H_M': '많이 지쳤나 봐요. 마음이 힘들 때도 있죠. 🤗 학습은 잠시 쉬어도 괜찮아요.',
                'H_R': '학습 루틴이 많이 흔들렸네요. 🌿 오늘 약속: 딱 5분만 책상 앞에 앉아보기!',
                'H_S': '시작하는 게 너무 어렵게 느껴지죠? 🌈 지금 바로 같이 시작해볼까요? 제가 도와줄게요.',
                'H_E': '지금 상황이 많이 힘든 것 같아요. 💙 상황이 나아지면 그때 다시 시작해도 괜찮아요.',
                'CRITICAL': '🚨 며칠째 어려움이 계속되고 있어요. 선생님과 함께 상담해볼까요? 지금 바로 도움을 드릴 수 있어요.'
            };

            return responses[personaCode] || '학습에 어려움이 있으시군요. 어떤 부분이 가장 힘드신가요?';
        }

        // 엔터키 처리
        function handleKeyPress(e) {
            if (e.key === 'Enter') sendMessage();
        }
    </script>

    <?php else: ?>
    <!-- 진단 테스트 탭 -->
    <div class="tab-content active" id="diagnosis-tab">
        <div class="diagnosis-container">
            <?php
            try {
                $test = new Agent13ChatTest();
                $test->runAllTests();
                $results = $test->getResults();
                $summary = $test->getSummary();

                $passRate = $summary['total'] > 0
                    ? round(($summary['passed'] / $summary['total']) * 100)
                    : 0;

                $scoreClass = $passRate >= 80 ? 'good' : ($passRate >= 50 ? 'warning' : 'bad');
            ?>

            <div class="summary-box">
                <div class="score <?= $scoreClass ?>"><?= $passRate ?>%</div>
                <div>통과: <?= $summary['passed'] ?> / 전체: <?= $summary['total'] ?></div>
            </div>

            <?php foreach ($results as $result): ?>
            <div class="test-result <?= $result['passed'] ? 'pass' : 'fail' ?>">
                <div class="test-name">
                    <?= $result['passed'] ? '✅' : '❌' ?> <?= htmlspecialchars($result['name']) ?>
                </div>
                <div class="test-message"><?= htmlspecialchars($result['message']) ?></div>
            </div>
            <?php endforeach; ?>

            <?php
            } catch (Throwable $e) {
                echo '<div class="test-result fail">';
                echo '<div class="test-name">❌ 테스트 실행 실패</div>';
                echo '<div class="test-message">' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="test-message">' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</div>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- 관련 링크 -->
        <div class="links-section">
            <h4 style="margin-bottom: 10px; color: var(--text-muted);">📎 관련 링크</h4>
            <a href="test.php" target="_blank">📋 기존 테스트 (test.php)</a>
            <a href="test.php?format=json" target="_blank">📊 JSON 형식 결과</a>
            <a href="personas.md" target="_blank">📄 페르소나 정의서</a>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
<?php
/*
 * =========================================================================
 * 사용법
 * =========================================================================
 *
 * 채팅 테스트:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test_chat.php?tab=chat
 *
 * 진단 테스트:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent13_learning_dropout/persona_system/test_chat.php?tab=diagnosis
 *
 * =========================================================================
 * 관련 DB 테이블
 * =========================================================================
 *
 * mdl_at_agent13_dropout_risk:
 *   - id (BIGINT PK)
 *   - user_id (BIGINT)
 *   - risk_tier (VARCHAR 20) - Low/Medium/High/Critical
 *   - dropout_cause (VARCHAR 10) - M/R/S/E
 *   - risk_score (DECIMAL 5,2) - 0-100
 *   - persona_code (VARCHAR 10) - 혼합형 코드 (예: M_M)
 *   - indicators_snapshot (JSON)
 *   - intervention_suggested (VARCHAR 50)
 *   - timecreated (INT)
 *
 * mdl_at_agent13_intervention_log:
 *   - id (BIGINT PK)
 *   - user_id (BIGINT)
 *   - intervention_type (VARCHAR 50)
 *   - persona_code (VARCHAR 10)
 *   - dropout_cause (VARCHAR 10)
 *   - message_sent (TEXT)
 *   - risk_score_before (DECIMAL 5,2)
 *   - response_type (VARCHAR 50)
 *   - timecreated (INT)
 *
 * =========================================================================
 */
