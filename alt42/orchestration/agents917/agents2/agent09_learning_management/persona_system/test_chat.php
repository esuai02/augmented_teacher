<?php
/**
 * test_chat.php
 *
 * Agent09 학습 관리 페르소나 시스템 통합 테스트
 * - 탭1: 채팅 테스트 (API 연동)
 * - 탭2: 진단 테스트 (BasePersonaTest 상속)
 *
 * @package     AugmentedTeacher
 * @subpackage  Agent09LearningManagement
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent09_learning_management/persona_system/test_chat.php
 */

// 에러 리포팅
error_reporting(E_ALL);
ini_set('display_errors', 1);

// MOODLE_INTERNAL 정의
if (!defined('MOODLE_INTERNAL')) {
    define('MOODLE_INTERNAL', true);
}

// =========================================================================
// 에이전트별 설정
// =========================================================================
$agentNumber = 9;
$agentName = 'learning_management';
$agentKrName = '학습 관리';
$agentEmoji = '📊';
$agentDescription = '5대 핵심 지표(출결, 목표, 포모도로, 오답노트, 시험)를 분석하여 학습 관리를 지원합니다.';

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

// 컨텍스트 코드 정의 (상황 코드)
$contextCodes = [
    'data_collection' => ['label' => '데이터 수집', 'color' => '#3498db', 'desc' => '데이터 수집 중'],
    'dropout_risk_low' => ['label' => '이탈 위험 낮음', 'color' => '#27ae60', 'desc' => '이탈 위험 낮음'],
    'dropout_risk_medium' => ['label' => '이탈 위험 중간', 'color' => '#f39c12', 'desc' => '이탈 위험 중간'],
    'dropout_risk_high' => ['label' => '이탈 위험 높음', 'color' => '#e74c3c', 'desc' => '이탈 위험 높음'],
    'routine_stable' => ['label' => '루틴 안정', 'color' => '#27ae60', 'desc' => '학습 루틴이 안정적임'],
    'routine_adjustment' => ['label' => '루틴 조정', 'color' => '#e67e22', 'desc' => '학습 루틴 조정 필요'],
    'math_weakness' => ['label' => '수학 취약', 'color' => '#9b59b6', 'desc' => '수학 영역 취약점 발견'],
    'goal_achievement_low' => ['label' => '목표 달성 낮음', 'color' => '#e74c3c', 'desc' => '목표 달성률이 낮음'],
    'pomodoro_incomplete' => ['label' => '포모도로 미완료', 'color' => '#f39c12', 'desc' => '포모도로 완료율 부족'],
    'attendance_decline' => ['label' => '출결 하락', 'color' => '#e74c3c', 'desc' => '출결률 하락 감지'],
    'test_performance_drop' => ['label' => '시험 성적 하락', 'color' => '#c0392b', 'desc' => '시험 성적 하락'],
    'positive_progress' => ['label' => '긍정적 진행', 'color' => '#27ae60', 'desc' => '긍정적인 학습 진행'],
    'default' => ['label' => '기본', 'color' => '#95a5a6', 'desc' => '기본 상태']
];

// 빠른 메시지 목록
$quickMessages = [
    '오늘 학습 현황을 알려줘',
    '이번 주 포모도로 완료율은?',
    '목표 달성률이 어떻게 돼?',
    '출결 상황을 확인하고 싶어',
    '오답노트 분석해줘',
    '시험 성적 추이를 보여줘',
    '학습 루틴을 개선하고 싶어',
    '다음 학습 계획을 세워줘'
];

// API 엔드포인트
$apiEndpoint = 'api/';

// 탭 설정
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'chat';

// =========================================================================
// 진단 테스트 클래스
// =========================================================================
require_once(__DIR__ . '/../../engine_core/testing/BasePersonaTest.php');

use ALT42\Testing\BasePersonaTest;

/**
 * Agent09PersonaTest
 *
 * 학습 관리 에이전트 테스트 클래스
 */
class Agent09PersonaTest extends BasePersonaTest
{
    /**
     * 생성자
     */
    public function __construct()
    {
        parent::__construct(
            9,                          // 에이전트 번호
            'learning_management',      // 에이전트 이름
            '학습 관리',                 // 에이전트 한글명
            __DIR__                     // 기본 경로 (persona_system)
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
            'engine/Agent09PersonaEngine.php' => 'Agent09PersonaEngine 메인 클래스',
            'engine/Agent09DataContext.php' => '데이터 컨텍스트 클래스',
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
     * Agent09 고유 테스트 실행
     */
    protected function runCustomTests(): void
    {
        // 1. Agent09PersonaEngine 클래스 로드 테스트
        $this->testPersonaEngineLoad();

        // 2. 상황 코드 (situationCodes) 테스트
        $this->testSituationCodes();

        // 3. 패턴 유형 (patternTypes) 테스트
        $this->testPatternTypes();

        // 4. 5대 핵심 지표 테스트
        $this->testCoreIndicators();

        // 5. AbstractPersonaEngine 상속 테스트
        $this->testAbstractPersonaEngineInheritance();

        // 6. DataContext 테스트
        $this->testDataContext();

        // 7. API 엔드포인트 테스트
        $this->testApiEndpoint('api/', 'GET');

        // 8. rules.yaml 구조 테스트
        $this->testRulesYaml();
    }

    /**
     * Agent09PersonaEngine 클래스 로드 테스트
     */
    private function testPersonaEngineLoad(): void
    {
        $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent09PersonaEngine 클래스 로드',
                false,
                '파일 없음: engine/Agent09PersonaEngine.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent09PersonaEngine') !== false;
            $extendsAbstract = strpos($content, 'extends AbstractPersonaEngine') !== false;

            $this->recordTest(
                'Agent09PersonaEngine 클래스 정의',
                $hasClass,
                $hasClass ? 'Agent09PersonaEngine 클래스 발견' : '클래스 정의 없음'
            );

            $this->recordTest(
                'AbstractPersonaEngine 상속',
                $extendsAbstract,
                $extendsAbstract ? '상속 확인됨' : '상속 관계 확인 불가'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'Agent09PersonaEngine 클래스 로드',
                false,
                '로드 실패: ' . $e->getMessage() . ' [' . __FILE__ . ':' . __LINE__ . ']'
            );
        }
    }

    /**
     * 상황 코드 테스트
     */
    private function testSituationCodes(): void
    {
        $expectedCodes = [
            'data_collection',
            'dropout_risk_low',
            'dropout_risk_medium',
            'dropout_risk_high',
            'routine_stable',
            'routine_adjustment',
            'math_weakness',
            'goal_achievement_low',
            'pomodoro_incomplete',
            'attendance_decline',
            'test_performance_drop',
            'positive_progress',
            'default'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasSituationCodes = strpos($content, 'situationCodes') !== false;

            $this->recordTest(
                '상황 코드 프로퍼티',
                $hasSituationCodes,
                $hasSituationCodes ? 'situationCodes 프로퍼티 정의됨' : 'situationCodes 없음'
            );

            $foundCodes = 0;
            foreach ($expectedCodes as $code) {
                if (strpos($content, "'" . $code . "'") !== false ||
                    strpos($content, '"' . $code . '"') !== false) {
                    $foundCodes++;
                }
            }

            $this->recordTest(
                '상황 코드 완성도',
                $foundCodes >= 10,
                "{$foundCodes}/" . count($expectedCodes) . " 상황 코드 정의됨",
                ['expected' => $expectedCodes, 'found' => $foundCodes]
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '상황 코드 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 패턴 유형 테스트
     */
    private function testPatternTypes(): void
    {
        $expectedTypes = [
            'data_sparse',
            'data_imbalanced',
            'pattern_unstable',
            'automation_resistant',
            'dropout_risk',
            'high_achiever'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            $hasPatternTypes = strpos($content, 'patternTypes') !== false;

            $this->recordTest(
                '패턴 유형 프로퍼티',
                $hasPatternTypes,
                $hasPatternTypes ? 'patternTypes 프로퍼티 정의됨' : 'patternTypes 없음'
            );

            $foundTypes = 0;
            foreach ($expectedTypes as $type) {
                if (strpos($content, "'" . $type . "'") !== false ||
                    strpos($content, '"' . $type . '"') !== false) {
                    $foundTypes++;
                }
            }

            $this->recordTest(
                '패턴 유형 완성도',
                $foundTypes >= 5,
                "{$foundTypes}/" . count($expectedTypes) . " 패턴 유형 정의됨"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '패턴 유형 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * 5대 핵심 지표 테스트
     */
    private function testCoreIndicators(): void
    {
        // 출결, 목표, 포모도로, 오답노트, 시험
        $expectedIndicators = [
            'attendance' => '출결',
            'goal' => '목표',
            'pomodoro' => '포모도로',
            'error_note' => '오답노트',
            'test' => '시험'
        ];

        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            $foundIndicators = 0;
            foreach ($expectedIndicators as $key => $name) {
                if (stripos($content, $key) !== false ||
                    strpos($content, $name) !== false) {
                    $foundIndicators++;
                }
            }

            $this->recordTest(
                '5대 핵심 지표 참조',
                $foundIndicators >= 4,
                "{$foundIndicators}/5 핵심 지표 참조 발견"
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '핵심 지표 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * AbstractPersonaEngine 상속 테스트
     */
    private function testAbstractPersonaEngineInheritance(): void
    {
        try {
            $filePath = __DIR__ . '/engine/Agent09PersonaEngine.php';
            $content = file_get_contents($filePath);

            // 필수 메서드 구현 확인
            $hasIdentifyPersona = strpos($content, 'identifyPersona') !== false;
            $hasGenerateResponse = strpos($content, 'generateResponse') !== false;
            $hasInitialize = strpos($content, 'initialize') !== false;

            $this->recordTest(
                'identifyPersona 메서드',
                $hasIdentifyPersona,
                $hasIdentifyPersona ? 'identifyPersona 메서드 구현됨' : 'identifyPersona 없음'
            );

            $this->recordTest(
                'generateResponse 메서드',
                $hasGenerateResponse,
                $hasGenerateResponse ? 'generateResponse 메서드 구현됨' : 'generateResponse 없음'
            );

            $this->recordTest(
                'initialize 메서드',
                $hasInitialize,
                $hasInitialize ? 'initialize 메서드 구현됨' : 'initialize 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                '상속 테스트',
                false,
                '확인 실패: ' . $e->getMessage()
            );
        }
    }

    /**
     * DataContext 테스트
     */
    private function testDataContext(): void
    {
        $filePath = __DIR__ . '/engine/Agent09DataContext.php';

        if (!file_exists($filePath)) {
            $this->recordTest(
                'Agent09DataContext 클래스',
                false,
                '파일 없음: engine/Agent09DataContext.php'
            );
            return;
        }

        try {
            $content = file_get_contents($filePath);
            $hasClass = strpos($content, 'class Agent09DataContext') !== false;
            $hasCollect = strpos($content, 'collect') !== false;

            $this->recordTest(
                'Agent09DataContext 클래스',
                $hasClass,
                $hasClass ? 'Agent09DataContext 클래스 정의됨' : '클래스 없음'
            );

            $this->recordTest(
                'DataContext collect 메서드',
                $hasCollect,
                $hasCollect ? 'collect 메서드 정의됨' : 'collect 메서드 없음'
            );

        } catch (Throwable $e) {
            $this->recordTest(
                'DataContext 테스트',
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

            // 규칙 구조 확인
            $hasRules = strpos($content, 'rules:') !== false ||
                       strpos($content, 'situation_rules:') !== false ||
                       strpos($content, 'conditions:') !== false;

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* 헤더 */
        .header {
            background: linear-gradient(135deg, #2980b9 0%, #3498db 100%);
            border-radius: 20px 20px 0 0;
            padding: 25px 30px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .agent-icon {
            font-size: 3rem;
        }

        .header-info h1 {
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .header-info p {
            opacity: 0.9;
            font-size: 0.9rem;
        }

        .header-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
        }

        /* 탭 네비게이션 */
        .tab-nav {
            display: flex;
            background: #2c3e50;
        }

        .tab-btn {
            flex: 1;
            padding: 15px 20px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            text-align: center;
        }

        .tab-btn:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }

        .tab-btn.active {
            background: white;
            color: #2c3e50;
            font-weight: 600;
        }

        /* 탭 콘텐츠 */
        .tab-content {
            display: none;
            background: white;
            border-radius: 0 0 20px 20px;
            min-height: 600px;
        }

        .tab-content.active {
            display: block;
        }

        /* ========== 채팅 탭 스타일 ========== */
        .chat-container {
            display: flex;
            flex-direction: column;
            height: 700px;
        }

        .context-selector {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
        }

        .context-selector label {
            font-weight: 600;
            margin-right: 10px;
        }

        .context-selector select {
            padding: 8px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            min-width: 200px;
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .message.assistant .message-avatar {
            background: linear-gradient(135deg, #2980b9, #3498db);
        }

        .message.user .message-avatar {
            background: linear-gradient(135deg, #3498db, #2980b9);
        }

        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 15px;
            line-height: 1.5;
        }

        .message.assistant .message-content {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 15px 15px 15px 0;
        }

        .message.user .message-content {
            background: #3498db;
            color: white;
            border-radius: 15px 15px 0 15px;
        }

        .quick-messages {
            padding: 10px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .quick-btn {
            padding: 6px 12px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 15px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .quick-btn:hover {
            background: #2980b9;
            color: white;
            border-color: #2980b9;
        }

        .chat-input {
            display: flex;
            padding: 15px 20px;
            gap: 10px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .chat-input input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .chat-input input:focus {
            border-color: #2980b9;
        }

        .chat-input button {
            padding: 12px 25px;
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .chat-input button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        /* ========== 진단 탭 스타일 ========== */
        .diagnosis-container {
            padding: 30px;
        }

        .diagnosis-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .run-diagnosis-btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }

        .run-diagnosis-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(41, 128, 185, 0.3);
        }

        .diagnosis-results {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            min-height: 400px;
        }

        .test-item {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            border-left: 4px solid #dee2e6;
        }

        .test-item.pass {
            border-left-color: #27ae60;
        }

        .test-item.fail {
            border-left-color: #e74c3c;
        }

        .test-icon {
            font-size: 1.5rem;
        }

        .test-info {
            flex: 1;
        }

        .test-name {
            font-weight: 600;
            margin-bottom: 3px;
        }

        .test-message {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .summary-box {
            background: linear-gradient(135deg, #2980b9, #3498db);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }

        .summary-item h3 {
            font-size: 2rem;
            margin-bottom: 5px;
        }

        .summary-item p {
            opacity: 0.9;
        }

        /* 로딩 */
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 50px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #2980b9;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* 컨텍스트 배지 */
        .context-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
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
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <div class="header-left">
                <div class="agent-icon"><?php echo $agentEmoji; ?></div>
                <div class="header-info">
                    <h1>Agent<?php echo sprintf('%02d', $agentNumber); ?> <?php echo $agentKrName; ?></h1>
                    <p><?php echo $agentDescription; ?></p>
                </div>
            </div>

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

        <!-- 탭 네비게이션 -->
        <div class="tab-nav">
            <a href="?tab=chat" class="tab-btn <?php echo $currentTab === 'chat' ? 'active' : ''; ?>">
                💬 채팅 테스트
            </a>
            <a href="?tab=diagnosis" class="tab-btn <?php echo $currentTab === 'diagnosis' ? 'active' : ''; ?>">
                🔍 진단 테스트
            </a>
        </div>

        <!-- 채팅 탭 -->
        <div class="tab-content <?php echo $currentTab === 'chat' ? 'active' : ''; ?>" id="chatTab">
            <div class="chat-container">
                <!-- 컨텍스트 선택 -->
                <div class="context-selector">
                    <label for="contextCode">상황 코드:</label>
                    <select id="contextCode">
                        <?php foreach ($contextCodes as $code => $info): ?>
                        <option value="<?php echo $code; ?>" data-color="<?php echo $info['color']; ?>">
                            <?php echo $info['label']; ?> - <?php echo $info['desc']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 메시지 영역 -->
                <div class="chat-messages" id="chatMessages">
                    <div class="message assistant">
                        <div class="message-avatar"><?php echo $agentEmoji; ?></div>
                        <div class="message-content">
                            안녕하세요! 저는 <?php echo $agentKrName; ?> 에이전트입니다.
                            5대 핵심 지표(출결, 목표, 포모도로, 오답노트, 시험)를 분석하여 학습 관리를 도와드릴게요.
                            무엇을 도와드릴까요?
                        </div>
                    </div>
                </div>

                <!-- 빠른 메시지 -->
                <div class="quick-messages">
                    <?php foreach ($quickMessages as $msg): ?>
                    <button class="quick-btn" onclick="sendQuickMessage('<?php echo addslashes($msg); ?>')">
                        <?php echo $msg; ?>
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- 입력 영역 -->
                <div class="chat-input">
                    <input type="text" id="userInput" placeholder="메시지를 입력하세요..."
                           onkeypress="if(event.key==='Enter') sendMessage()">
                    <button onclick="sendMessage()">전송</button>
                </div>
            </div>
        </div>

        <!-- 진단 탭 -->
        <div class="tab-content <?php echo $currentTab === 'diagnosis' ? 'active' : ''; ?>" id="diagnosisTab">
            <div class="diagnosis-container">
                <div class="diagnosis-header">
                    <h2>🔍 시스템 진단</h2>
                    <button class="run-diagnosis-btn" onclick="runDiagnosis()">진단 실행</button>
                </div>
                <div class="diagnosis-results" id="diagnosisResults">
                    <p style="text-align: center; color: #6c757d; padding: 50px;">
                        '진단 실행' 버튼을 클릭하여 시스템 진단을 시작하세요.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        const agentEmoji = '<?php echo $agentEmoji; ?>';
        const apiEndpoint = '<?php echo $apiEndpoint; ?>';
        const contextCodes = <?php echo json_encode($contextCodes); ?>;

        // 메시지 전송
        async function sendMessage() {
            const input = document.getElementById('userInput');
            const message = input.value.trim();
            if (!message) return;

            const contextCode = document.getElementById('contextCode').value;

            // 사용자 메시지 표시
            addMessage('user', message);
            input.value = '';

            // 로딩 표시
            const loadingId = addLoading();

            try {
                const response = await fetch(apiEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        message: message,
                        context_code: contextCode,
                        user_id: 1,
                        session_id: 'test_' + Date.now()
                    })
                });

                removeLoading(loadingId);

                if (response.ok) {
                    const data = await response.json();
                    addMessage('assistant', data.response || data.message || '응답을 받았습니다.', contextCode);
                } else {
                    addMessage('assistant', '⚠️ API 응답 오류: ' + response.status, 'error');
                }
            } catch (error) {
                removeLoading(loadingId);
                addMessage('assistant', '⚠️ 연결 오류: ' + error.message, 'error');
            }
        }

        // 빠른 메시지 전송
        function sendQuickMessage(msg) {
            document.getElementById('userInput').value = msg;
            sendMessage();
        }

        // 메시지 추가
        function addMessage(type, content, contextCode = null) {
            const container = document.getElementById('chatMessages');
            const div = document.createElement('div');
            div.className = 'message ' + type;

            let badge = '';
            if (contextCode && contextCodes[contextCode]) {
                const ctx = contextCodes[contextCode];
                badge = `<span class="context-badge" style="background: ${ctx.color}; color: white;">${contextCode}</span>`;
            }

            div.innerHTML = `
                <div class="message-avatar">${type === 'assistant' ? agentEmoji : '👤'}</div>
                <div class="message-content">${content}${badge}</div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
        }

        // 로딩 추가
        function addLoading() {
            const container = document.getElementById('chatMessages');
            const id = 'loading_' + Date.now();
            const div = document.createElement('div');
            div.id = id;
            div.className = 'message assistant';
            div.innerHTML = `
                <div class="message-avatar">${agentEmoji}</div>
                <div class="message-content"><div class="spinner" style="width:20px;height:20px;border-width:2px;"></div></div>
            `;
            container.appendChild(div);
            container.scrollTop = container.scrollHeight;
            return id;
        }

        // 로딩 제거
        function removeLoading(id) {
            const el = document.getElementById(id);
            if (el) el.remove();
        }

        // 진단 실행
        async function runDiagnosis() {
            const container = document.getElementById('diagnosisResults');
            container.innerHTML = '<div class="loading"><div class="spinner"></div></div>';

            try {
                const response = await fetch('?tab=diagnosis&format=json');
                const data = await response.json();

                let html = '';
                let passCount = 0;
                let failCount = 0;

                if (data.results) {
                    data.results.forEach(test => {
                        const isPass = test.success || test.passed;
                        if (isPass) passCount++; else failCount++;

                        html += `
                            <div class="test-item ${isPass ? 'pass' : 'fail'}">
                                <div class="test-icon">${isPass ? '✅' : '❌'}</div>
                                <div class="test-info">
                                    <div class="test-name">${test.name || test.test}</div>
                                    <div class="test-message">${test.message || test.details || ''}</div>
                                </div>
                            </div>
                        `;
                    });
                }

                html += `
                    <div class="summary-box">
                        <div class="summary-item">
                            <h3>${passCount}</h3>
                            <p>통과</p>
                        </div>
                        <div class="summary-item">
                            <h3>${failCount}</h3>
                            <p>실패</p>
                        </div>
                        <div class="summary-item">
                            <h3>${passCount + failCount > 0 ? Math.round(passCount / (passCount + failCount) * 100) : 0}%</h3>
                            <p>성공률</p>
                        </div>
                    </div>
                `;

                container.innerHTML = html;

            } catch (error) {
                container.innerHTML = `<p style="color: #e74c3c; text-align: center;">진단 실행 오류: ${error.message}</p>`;
            }
        }
    </script>

<?php
// JSON 형식 요청 처리 (진단 탭)
if ($currentTab === 'diagnosis' && isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $test = new Agent09PersonaTest();
        $test->runAllTests();
        echo $test->toJson();
    } catch (Throwable $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}
?>
</body>
</html>
