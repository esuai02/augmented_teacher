<?php
/**
 * test_chat.php
 *
 * Agent01 Onboarding - 채팅 테스트 및 진단 결과 통합 페이지
 * 탭으로 채팅 테스트와 시스템 진단을 전환하여 볼 수 있음
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent01Onboarding
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test_chat.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// =========================================================================
// 에이전트 설정 (각 에이전트별로 수정)
// =========================================================================
$agentNumber = 1;
$agentName = 'onboarding';
$agentKrName = '온보딩';
$agentEmoji = '🎯';

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
    ['num' => 14, 'id' => 'current_position', 'name' => '현재위치', 'emoji' => '📍'],
    ['num' => 15, 'id' => 'problem_redefinition', 'name' => '문제재정의', 'emoji' => '🔄'],
    ['num' => 16, 'id' => 'interaction_preparation', 'name' => '상호작용준비', 'emoji' => '🤝'],
    ['num' => 17, 'id' => 'remaining_activities', 'name' => '잔여활동', 'emoji' => '⏰'],
    ['num' => 18, 'id' => 'signature_routine', 'name' => '시그니처루틴', 'emoji' => '✨'],
    ['num' => 19, 'id' => 'interaction_content', 'name' => '상호작용컨텐츠', 'emoji' => '💬'],
    ['num' => 20, 'id' => 'intervention_preparation', 'name' => '개입준비', 'emoji' => '🚀'],
    ['num' => 21, 'id' => 'intervention_execution', 'name' => '개입실행', 'emoji' => '⚡'],
];

$currentFile = __FILE__;
$basePath = dirname($currentFile);
$baseUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system';

// 컨텍스트 코드 정의
$contextCodes = [
    'S0' => ['name' => '시작 전', 'color' => '#6c757d'],
    'S1' => ['name' => '프로필 설정', 'color' => '#007bff'],
    'S2' => ['name' => '진단 테스트', 'color' => '#28a745'],
    'S3' => ['name' => '목표 설정', 'color' => '#ffc107'],
    'S4' => ['name' => '학습 안내', 'color' => '#17a2b8'],
    'S5' => ['name' => '완료', 'color' => '#6f42c1'],
    'Q' => ['name' => '질문', 'color' => '#fd7e14'],
    'E' => ['name' => '감정', 'color' => '#e83e8c'],
    'C' => ['name' => '위기', 'color' => '#dc3545']
];

// 빠른 메시지 정의
$quickMessages = [
    ['text' => '처음 사용해보는데 어떻게 시작하나요?', 'label' => '시작 안내'],
    ['text' => '제 프로필을 설정하고 싶어요', 'label' => '프로필 설정'],
    ['text' => '학습 목표를 어떻게 정하나요?', 'label' => '목표 설정'],
    ['text' => '시스템 기능이 뭐가 있나요?', 'label' => '기능 안내'],
    ['text' => '튜토리얼을 다시 보고 싶어요', 'label' => '튜토리얼'],
    ['text' => '처음부터 다시 설정하고 싶어요', 'label' => '초기화'],
    ['text' => '수학이 너무 어려워요', 'label' => '😰 어려움'],
    ['text' => '포기하고 싶어요', 'label' => '😢 포기'],
];

// 탭 선택
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// =========================================================================
// 진단 테스트 클래스
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

class Agent01PersonaTest extends BasePersonaTest
{
    public function __construct()
    {
        parent::__construct(1, 'onboarding', '온보딩', __DIR__);
    }

    protected function getRequiredFiles(): array
    {
        return [
            'engine/Agent01PersonaEngine.php' => 'PersonaEngine 메인 클래스',
            'engine/Agent01DataContext.php' => 'DataContext 데이터 접근 클래스',
            'engine/Agent01ResponseGenerator.php' => '응답 생성기 클래스',
        ];
    }

    protected function getRequiredTables(): array
    {
        return [
            'mdl_at_onboarding_state' => '온보딩 상태 테이블',
            'mdl_at_agent_persona_state' => '공통 페르소나 상태 테이블'
        ];
    }

    protected function runCustomTests(): void
    {
        $this->testPersonaEngineLoad();
        $this->testOnboardingStages();
        $this->testResponseGenerator();
    }

    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
        if (!file_exists($filePath)) {
            $this->recordTest('PersonaEngine 클래스 로드', false, '파일 없음: engine/Agent01PersonaEngine.php');
            return;
        }
        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent01PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;
            $this->recordTest('PersonaEngine 클래스 정의', $hasClass, $hasClass ? 'Agent01PersonaEngine 클래스 발견' : '클래스 정의 없음');
            $this->recordTest('AbstractPersonaEngine 상속', $extendsAbstract, $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가');
        } catch (Throwable $e) {
            $this->recordTest('PersonaEngine 클래스 로드', false, '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']');
        }
    }

    private function testOnboardingStages(): void
    {
        $expectedStages = ['S0', 'S1', 'S2', 'S3', 'S4', 'S5'];
        try {
            $filePath = __DIR__ . '/engine/Agent01PersonaEngine.php';
            if (!file_exists($filePath)) {
                $this->recordTest('온보딩 단계 정의', false, '엔진 파일 없음');
                return;
            }
            $content = file_get_contents($filePath);
            $foundStages = 0;
            foreach ($expectedStages as $stage) {
                if (strpos($content, "'" . $stage . "'") !== false) {
                    $foundStages++;
                }
            }
            $this->recordTest('온보딩 단계 정의', $foundStages >= 3, "{$foundStages}/" . count($expectedStages) . " 단계 정의됨");
        } catch (Throwable $e) {
            $this->recordTest('온보딩 단계 정의', false, '확인 실패: ' . $e->getMessage());
        }
    }

    private function testResponseGenerator(): void
    {
        $filePath = __DIR__ . '/engine/Agent01ResponseGenerator.php';
        if (!file_exists($filePath)) {
            $this->recordTest('ResponseGenerator 클래스', false, '파일 없음: engine/Agent01ResponseGenerator.php');
            return;
        }
        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent01ResponseGenerator') !== false;
            $hasGenerate = preg_match('/function\s+\w*generate/i', $content);
            $this->recordTest('Agent01ResponseGenerator 클래스 정의', $hasClass, $hasClass ? 'Agent01ResponseGenerator 클래스 발견' : '클래스 정의 없음');
            $this->recordTest('ResponseGenerator 생성 메서드', $hasGenerate, $hasGenerate ? '응답 생성 메서드 발견' : '응답 생성 메서드 없음');
        } catch (Throwable $e) {
            $this->recordTest('ResponseGenerator 테스트', false, '확인 실패: ' . $e->getMessage());
        }
    }
}

// 진단 탭인 경우 테스트 실행
$testResults = null;
$testSummary = null;
$testError = null;
if ($activeTab === 'diagnosis') {
    try {
        $test = new Agent01PersonaTest();
        $test->runAllTests();
        $testSummary = $test->getSummary();
        $testResults = $test->getResults();
    } catch (Throwable $e) {
        $testError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent<?php echo str_pad($agentNumber, 2, '0', STR_PAD_LEFT); ?> <?php echo $agentKrName; ?> - 테스트</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1100px; margin: 0 auto; }
        h1 { color: white; margin-bottom: 20px; text-shadow: 0 2px 4px rgba(0,0,0,0.2); }

        /* 탭 스타일 */
        .tabs {
            display: flex;
            margin-bottom: 20px;
            background: rgba(255,255,255,0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .tab {
            flex: 1;
            padding: 16px 24px;
            text-align: center;
            cursor: pointer;
            border: none;
            background: rgba(255,255,255,0.9);
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
            text-decoration: none;
        }
        .tab:hover { background: rgba(102, 126, 234, 0.1); }
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px;
        }
        .card-header h2 { font-size: 1.3rem; margin-bottom: 5px; }
        .card-header p { opacity: 0.9; font-size: 0.9rem; }
        .card-body { padding: 20px; }

        /* 컨텍스트 배지 */
        .context-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin: 2px;
            color: white;
        }
        .info-box {
            background: #e8f4fd;
            border: 1px solid #3498db;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        /* 채팅 스타일 */
        .chat-container {
            height: 350px;
            overflow-y: auto;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            background: #fafafa;
        }
        .message { margin-bottom: 15px; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message.user { text-align: right; }
        .message.bot { text-align: left; }
        .message-bubble {
            display: inline-block;
            max-width: 75%;
            padding: 12px 16px;
            border-radius: 18px;
            line-height: 1.5;
        }
        .message.user .message-bubble {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        .message.bot .message-bubble {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 4px;
        }
        .message-meta { font-size: 0.75rem; color: #888; margin-top: 4px; }

        .quick-messages { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 15px; }
        .quick-btn {
            padding: 8px 14px;
            background: #f0f0f0;
            border: none;
            border-radius: 20px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .quick-btn:hover { background: #e0e0e0; transform: translateY(-1px); }

        .input-area { display: flex; gap: 10px; }
        .input-area input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .input-area input:focus { outline: none; border-color: #667eea; }
        .input-area button {
            padding: 14px 28px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .input-area button:hover { transform: translateY(-2px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4); }
        .input-area button:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        .settings { display: flex; gap: 20px; margin-bottom: 15px; flex-wrap: wrap; align-items: center; }
        .setting-item { display: flex; align-items: center; gap: 8px; }
        .setting-item label { font-size: 0.9rem; color: #555; }

        .toggle { position: relative; width: 50px; height: 26px; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            inset: 0;
            background: #ccc;
            border-radius: 26px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            content: "";
            position: absolute;
            height: 20px;
            width: 20px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .toggle input:checked + .toggle-slider:before { transform: translateX(24px); }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-indicator.success { background: #e8f5e9; color: #2e7d32; }
        .status-indicator.error { background: #ffebee; color: #c62828; }
        .status-indicator.loading { background: #e3f2fd; color: #1565c0; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: currentColor; }

        .persona-badge {
            display: inline-block;
            padding: 4px 10px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-right: 5px;
        }

        .debug-panel {
            background: #1e1e1e;
            color: #d4d4d4;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Fira Code', monospace;
            font-size: 0.8rem;
            max-height: 250px;
            overflow-y: auto;
            display: none;
            margin-top: 15px;
        }
        .debug-panel.show { display: block; }
        .debug-panel pre { white-space: pre-wrap; word-break: break-all; }

        /* 진단 결과 스타일 */
        .summary { background: white; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .summary-stats { display: flex; gap: 15px; margin-top: 15px; }
        .stat-box { flex: 1; padding: 20px; border-radius: 12px; text-align: center; }
        .stat-box.total { background: #e2e8f0; }
        .stat-box.passed { background: #c6f6d5; }
        .stat-box.failed { background: #fed7d7; }
        .stat-number { font-size: 36px; font-weight: bold; }

        .section { background: white; border-radius: 12px; padding: 20px; margin-bottom: 15px; }
        .section h3 { color: #2c5282; margin-bottom: 15px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        .test-item { display: flex; align-items: center; padding: 12px; border-bottom: 1px solid #e2e8f0; }
        .test-item:last-child { border-bottom: none; }
        .test-status {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            margin-right: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 14px;
        }
        .test-status.pass { background: #48bb78; }
        .test-status.fail { background: #f56565; }
        .test-name { flex: 1; font-weight: 500; }
        .test-message { color: #718096; font-size: 14px; }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge.success { background: #c6f6d5; color: #276749; }
        .badge.warning { background: #fefcbf; color: #975a16; }
        .badge.err { background: #fed7d7; color: #c53030; }

        footer {
            margin-top: 30px;
            padding: 20px;
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            color: rgba(255,255,255,0.8);
            font-size: 12px;
        }
        footer a { color: rgba(255,255,255,0.9); }

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
        .agent-dropdown-content.show {
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

        <!-- 탭 -->
        <div class="tabs">
            <a href="?tab=chat" class="tab <?php echo $activeTab === 'chat' ? 'active' : ''; ?>">💬 채팅 테스트</a>
            <a href="?tab=diagnosis" class="tab <?php echo $activeTab === 'diagnosis' ? 'active' : ''; ?>">🔍 진단 결과</a>
        </div>

        <?php if ($activeTab === 'chat'): ?>
        <!-- ========== 채팅 테스트 탭 ========== -->
        <div class="card">
            <div class="card-header">
                <h2>🤖 페르소나 채팅 테스트</h2>
                <p>사용자: <?php echo htmlspecialchars($USER->firstname ?? 'Guest'); ?> | API: <?php echo $baseUrl; ?>/api/chat.php</p>
            </div>
            <div class="card-body">
                <!-- 컨텍스트 정보 -->
                <div class="info-box">
                    <strong>컨텍스트 코드:</strong><br>
                    <?php foreach ($contextCodes as $code => $info): ?>
                        <span class="context-badge" style="background: <?php echo $info['color']; ?>;"><?php echo $code; ?>: <?php echo $info['name']; ?></span>
                    <?php endforeach; ?>
                </div>

                <!-- 설정 -->
                <div class="settings">
                    <div class="setting-item">
                        <label class="toggle">
                            <input type="checkbox" id="aiEnabled" checked>
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="aiEnabled">AI 활성화</label>
                    </div>
                    <div class="setting-item">
                        <label class="toggle">
                            <input type="checkbox" id="debugMode">
                            <span class="toggle-slider"></span>
                        </label>
                        <label for="debugMode">디버그</label>
                    </div>
                    <span id="statusIndicator" class="status-indicator success">
                        <span class="status-dot"></span>준비됨
                    </span>
                </div>

                <!-- 빠른 메시지 -->
                <div class="quick-messages">
                    <?php foreach ($quickMessages as $msg): ?>
                        <button class="quick-btn" onclick="sendQuickMessage('<?php echo addslashes($msg['text']); ?>')"><?php echo $msg['label']; ?></button>
                    <?php endforeach; ?>
                </div>

                <!-- 채팅 영역 -->
                <div class="chat-container" id="chatContainer">
                    <div class="message bot">
                        <div class="message-bubble">안녕하세요! 저는 <?php echo $agentKrName; ?> 에이전트입니다. 무엇을 도와드릴까요? 😊</div>
                        <div class="message-meta">시스템 · 방금</div>
                    </div>
                </div>

                <!-- 입력 -->
                <div class="input-area">
                    <input type="text" id="messageInput" placeholder="메시지를 입력하세요..." onkeypress="handleKeyPress(event)">
                    <button id="sendBtn" onclick="sendMessage()">전송</button>
                </div>

                <!-- 디버그 패널 -->
                <div class="debug-panel" id="debugPanel">
                    <pre id="debugOutput"></pre>
                </div>
            </div>
        </div>

        <script>
            const API_URL = './api/chat.php';
            const userId = <?php echo (int)($USER->id ?? 1); ?>;

            function sendMessage() {
                const input = document.getElementById('messageInput');
                const message = input.value.trim();
                if (!message) return;

                const aiEnabled = document.getElementById('aiEnabled').checked;
                const debugMode = document.getElementById('debugMode').checked;

                addMessage(message, 'user');
                input.value = '';
                setStatus('loading', '처리 중...');
                document.getElementById('sendBtn').disabled = true;

                fetch(API_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ message: message, user_id: userId, ai_enabled: aiEnabled })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const persona = data.persona || {};
                        const response = data.response || {};
                        const meta = data.meta || {};
                        let metaText = `${persona.persona_name || '미식별'}`;
                        if (meta.ai_used) metaText += ' · AI';
                        if (meta.processing_time_ms) metaText += ` · ${meta.processing_time_ms}ms`;
                        addMessage(response.text || '응답 생성 실패', 'bot', {
                            personaId: persona.persona_id,
                            personaName: persona.persona_name,
                            meta: metaText
                        });
                        setStatus('success', 'AI: ' + (meta.ai_used ? 'ON' : 'OFF'));
                    } else {
                        addMessage('오류: ' + (data.error || '알 수 없는 오류'), 'bot');
                        setStatus('error', '오류 발생');
                    }
                    if (debugMode) {
                        document.getElementById('debugPanel').classList.add('show');
                        document.getElementById('debugOutput').textContent = JSON.stringify(data, null, 2);
                    }
                })
                .catch(err => {
                    addMessage('네트워크 오류: ' + err.message, 'bot');
                    setStatus('error', '연결 실패');
                })
                .finally(() => {
                    document.getElementById('sendBtn').disabled = false;
                });
            }

            function sendQuickMessage(msg) {
                document.getElementById('messageInput').value = msg;
                sendMessage();
            }

            function addMessage(text, type, options = {}) {
                const container = document.getElementById('chatContainer');
                const div = document.createElement('div');
                div.className = `message ${type}`;
                let badgeHtml = '';
                if (options.personaId && type === 'bot') {
                    badgeHtml = `<span class="persona-badge">${options.personaId}</span>`;
                }
                div.innerHTML = `
                    <div class="message-bubble">${badgeHtml}${escapeHtml(text)}</div>
                    <div class="message-meta">${options.meta || (type === 'user' ? '나' : '봇')} · 방금</div>
                `;
                container.appendChild(div);
                container.scrollTop = container.scrollHeight;
            }

            function setStatus(type, text) {
                const indicator = document.getElementById('statusIndicator');
                indicator.className = `status-indicator ${type}`;
                indicator.innerHTML = `<span class="status-dot"></span>${text}`;
            }

            function handleKeyPress(e) { if (e.key === 'Enter') sendMessage(); }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            document.getElementById('debugMode').addEventListener('change', function() {
                if (!this.checked) document.getElementById('debugPanel').classList.remove('show');
            });
        </script>

        <?php else: ?>
        <!-- ========== 진단 결과 탭 ========== -->
        <?php if (isset($testError)): ?>
            <div class="card">
                <div class="card-body">
                    <h2>❌ 테스트 실행 실패</h2>
                    <p style="color: #c53030;"><strong>Error:</strong> <?php echo htmlspecialchars($testError); ?></p>
                    <p style="color: #718096; font-size: 12px;">파일: <?php echo __FILE__; ?></p>
                </div>
            </div>
        <?php elseif ($testSummary): ?>
            <!-- 요약 -->
            <div class="summary">
                <h2 style="margin: 0 0 15px 0;">📊 테스트 요약</h2>
                <div class="summary-stats">
                    <div class="stat-box total">
                        <div class="stat-number"><?php echo $testSummary['total_tests']; ?></div>
                        <div>전체</div>
                    </div>
                    <div class="stat-box passed">
                        <div class="stat-number"><?php echo $testSummary['passed_tests']; ?></div>
                        <div>통과</div>
                    </div>
                    <div class="stat-box failed">
                        <div class="stat-number"><?php echo $testSummary['failed_tests']; ?></div>
                        <div>실패</div>
                    </div>
                </div>
                <div style="margin-top: 20px; text-align: center; font-size: 18px;">
                    <strong>통과율: <?php echo $testSummary['pass_percentage']; ?>%</strong>
                    <?php if ($testSummary['pass_percentage'] >= 80): ?>
                        <span class="badge success">✅ 테스트 통과</span>
                    <?php elseif ($testSummary['pass_percentage'] >= 50): ?>
                        <span class="badge warning">⚠️ 부분 통과</span>
                    <?php else: ?>
                        <span class="badge err">❌ 테스트 실패</span>
                    <?php endif; ?>
                    <span style="margin-left: 10px; color: #718096; font-size: 14px;">(<?php echo $testSummary['duration_ms']; ?>ms)</span>
                </div>
            </div>

            <!-- 섹션별 결과 -->
            <?php foreach ($testSummary['sections'] as $sectionKey => $section): ?>
                <div class="section">
                    <h3><?php echo htmlspecialchars($section['name']); ?>
                        <span class="badge <?php echo $section['passed'] === $section['total'] ? 'success' : 'warning'; ?>">
                            <?php echo $section['passed']; ?>/<?php echo $section['total']; ?>
                        </span>
                    </h3>
                    <?php foreach ($section['tests'] as $test): ?>
                        <div class="test-item">
                            <div class="test-status <?php echo $test['passed'] ? 'pass' : 'fail'; ?>">
                                <?php echo $test['passed'] ? '✓' : '✗'; ?>
                            </div>
                            <div class="test-name"><?php echo htmlspecialchars($test['name']); ?></div>
                            <div class="test-message"><?php echo htmlspecialchars($test['message']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <?php endif; ?>

        <footer>
            <p><strong>파일:</strong> <?php echo $currentFile; ?></p>
            <p><strong>API:</strong> <a href="<?php echo $baseUrl; ?>/api/chat.php" target="_blank"><?php echo $baseUrl; ?>/api/chat.php</a></p>
            <p><strong>테스트 시간:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        </footer>
    </div>
    
    <script>
    // 에이전트 드롭다운 클릭 기반 동작
    (function() {
        const dropdownBtn = document.querySelector('.agent-dropdown-btn');
        const dropdownContent = document.querySelector('.agent-dropdown-content');
        
        if (dropdownBtn && dropdownContent) {
            // 버튼 클릭 시 드롭다운 토글
            dropdownBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdownContent.classList.toggle('show');
            });
            
            // 문서 다른 곳 클릭 시 드롭다운 닫기
            document.addEventListener('click', function(e) {
                if (!dropdownContent.contains(e.target) && !dropdownBtn.contains(e.target)) {
                    dropdownContent.classList.remove('show');
                }
            });
            
            // 메뉴 항목 클릭 시 드롭다운 닫기 (페이지 이동 전)
            dropdownContent.querySelectorAll('a').forEach(function(link) {
                link.addEventListener('click', function() {
                    dropdownContent.classList.remove('show');
                });
            });
        }
    })();
    </script>
</body>
</html>
<?php
/*
 * 테스트 URL:
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test_chat.php
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test_chat.php?tab=diagnosis
 *
 * 관련 DB: mdl_at_onboarding_state, mdl_at_agent_persona_state
 * 파일 위치: /mnt/c/1 Project/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/persona_system/test_chat.php
 */
?>
