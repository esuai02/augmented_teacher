<?php
/**
 * AI 튜터 온톨로지 로드맵 및 진단 체크
 * 
 * 룰과 온톨로지 완성도를 시각적으로 표시하고
 * 향후 개발 방향을 제시
 * 
 * @package    local_augmented_teacher
 * @subpackage AItutor
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$errorFile = __FILE__;

// 룰 파일 로드 및 분석
$rules = [];

// 1. 완결성 룰셋 로드
$completeRulesPath = dirname(__DIR__) . '/rules/complete_rules.php';
if (file_exists($completeRulesPath)) {
    $completeRules = include($completeRulesPath);
    if (is_array($completeRules)) {
        $rules = array_merge($rules, $completeRules);
    }
}

// 2. 페르소나별 룰셋 로드 및 페르소나 수 계산
$personaRulesPath = dirname(__DIR__) . '/rules/persona_rules.php';
$personaRulesData = [];
if (file_exists($personaRulesPath)) {
    $personaRulesData = include($personaRulesPath);
    if (is_array($personaRulesData)) {
        foreach ($personaRulesData as $persona) {
            if (isset($persona['rules']) && is_array($persona['rules'])) {
                foreach ($persona['rules'] as $rule) {
                    $rule['layer'] = 'persona';
                    $rules[$rule['rule_id']] = $rule;
                }
            }
        }
    }
}

// 3. 즉시 개입 룰셋 로드
$immediateRulesPath = dirname(__DIR__) . '/rules/immediate_rules.php';
if (file_exists($immediateRulesPath)) {
    $immediateRules = include($immediateRulesPath);
    if (is_array($immediateRules)) {
        foreach ($immediateRules as $key => $rule) {
            $rule['layer'] = 'immediate';
            $rules[$key] = $rule;
        }
    }
}

// 온톨로지 파일 로드
$ontology = [];
$ontologyPath = dirname(__DIR__) . '/ontology/persona_situation_mapping.php';
if (file_exists($ontologyPath)) {
    $ontology = include($ontologyPath);
}

// 룰 레이어별 분류
$ruleLayers = [
    'session' => ['name' => '세션 생명주기', 'icon' => '🎬', 'rules' => [], 'target' => 5],
    'writing' => ['name' => '필기 패턴', 'icon' => '✏️', 'rules' => [], 'target' => 20],
    'hint' => ['name' => '힌트 제공', 'icon' => '💡', 'rules' => [], 'target' => 6],
    'gesture' => ['name' => '제스처 반응', 'icon' => '👆', 'rules' => [], 'target' => 10],
    'emotion' => ['name' => '감정 반응', 'icon' => '😊', 'rules' => [], 'target' => 11],
    'answer' => ['name' => '답 검증', 'icon' => '✅', 'rules' => [], 'target' => 15],
    'memory' => ['name' => '장기기억', 'icon' => '🧠', 'rules' => [], 'target' => 5],
    'persona' => ['name' => '페르소나별', 'icon' => '👤', 'rules' => [], 'target' => 30],
    'immediate' => ['name' => '즉시 개입', 'icon' => '⚡', 'rules' => [], 'target' => 10]
];

foreach ($rules as $ruleId => $rule) {
    $layer = $rule['layer'] ?? 'unknown';
    if (isset($ruleLayers[$layer])) {
        $ruleLayers[$layer]['rules'][$ruleId] = $rule;
    }
}

// 온톨로지 상황 분류
$situationCategories = [
    'writing' => ['name' => '필기 패턴', 'icon' => '✏️', 'situations' => [], 'target' => 5],
    'emotion' => ['name' => '감정 상태', 'icon' => '😊', 'situations' => [], 'target' => 4],
    'error' => ['name' => '오류 패턴', 'icon' => '❌', 'situations' => [], 'target' => 5],
    'interaction' => ['name' => '상호작용', 'icon' => '🤝', 'situations' => [], 'target' => 5],
    'learning' => ['name' => '학습 패턴', 'icon' => '📚', 'situations' => [], 'target' => 5]
];

$situationMapping = [
    'writing_pause_short' => 'writing',
    'writing_pause_long' => 'writing',
    'repeated_erase' => 'writing',
    'fast_solve' => 'writing',
    'slow_progress' => 'writing',
    'emotion_confident' => 'emotion',
    'emotion_stuck' => 'emotion',
    'emotion_anxious' => 'emotion',
    'emotion_confused' => 'emotion',
    'error_sign' => 'error',
    'error_reciprocal' => 'error',
    'error_order' => 'error',
    'error_calculation' => 'error',
    'repeated_confirm_request' => 'interaction',
    'hint_request_frequent' => 'interaction',
    'passive_listening' => 'interaction',
    'early_quit_attempt' => 'interaction',
    'consecutive_correct' => 'learning',
    'consecutive_wrong' => 'learning',
    'mastery_high' => 'learning',
    'difficulty_mismatch' => 'learning'
];

if (isset($ontology['situations'])) {
    foreach ($ontology['situations'] as $sitId => $situation) {
        $category = $situationMapping[$sitId] ?? 'unknown';
        if (isset($situationCategories[$category])) {
            $situationCategories[$category]['situations'][$sitId] = $situation;
        }
    }
}

// DB 테이블 확인
$dbTables = [
    'alt42i_sessions' => ['name' => '세션 관리', 'required' => true, 'exists' => false],
    'alt42i_interaction_logs' => ['name' => '상호작용 로그', 'required' => true, 'exists' => false],
    'alt42i_context_states' => ['name' => '컨텍스트 상태', 'required' => false, 'exists' => false],
    'alt42i_persona_history' => ['name' => '페르소나 히스토리', 'required' => false, 'exists' => false],
    'alt42i_rule_executions' => ['name' => '룰 실행 로그', 'required' => false, 'exists' => false],
    'alt42i_emotion_history' => ['name' => '감정 히스토리', 'required' => false, 'exists' => false],
    'alt42i_ontology_nodes' => ['name' => '온톨로지 노드', 'required' => false, 'exists' => false],
    'alt42i_dynamic_rules' => ['name' => '동적 룰', 'required' => false, 'exists' => false]
];

foreach ($dbTables as $tableName => &$tableInfo) {
    try {
        $dbman = $DB->get_manager();
        $tableInfo['exists'] = $dbman->table_exists($tableName);
    } catch (Exception $e) {
        $tableInfo['exists'] = false;
    }
}
unset($tableInfo);

// 시스템 액션 구현 상태
$systemActions = [
    'SESSION_INIT' => ['name' => '세션 초기화', 'implemented' => true],
    'STEP_ADVANCE' => ['name' => '단계 진행', 'implemented' => true],
    'UPDATE_PROGRESS' => ['name' => '진행률 업데이트', 'implemented' => true],
    'CAPTURE_WHITEBOARD' => ['name' => '화이트보드 캡처', 'implemented' => true],
    'ANALYZE_WRITING' => ['name' => '필기 분석', 'implemented' => true],
    'SHOW_PROBLEM' => ['name' => '문제 표시', 'implemented' => true],
    'GET_CONTEXTUAL_HINT' => ['name' => '힌트 가져오기', 'implemented' => false],
    'SHOW_VISUAL_EXPLANATION' => ['name' => '시각화 설명', 'implemented' => false],
    'SHOW_FORMULA_APPLICATION' => ['name' => '공식 적용', 'implemented' => false],
    'INCREASE_DIFFICULTY' => ['name' => '난이도 증가', 'implemented' => false],
    'DECREASE_DIFFICULTY' => ['name' => '난이도 감소', 'implemented' => false],
    'START_GUIDED_MODE' => ['name' => '가이드 모드', 'implemented' => false],
    'PAUSE_SESSION' => ['name' => '세션 일시정지', 'implemented' => false],
    'SHOW_BREATHING_EXERCISE' => ['name' => '호흡 운동', 'implemented' => true]
];

// 완성도 계산
function calculateCompletion($current, $target) {
    return min(100, round(($current / max(1, $target)) * 100));
}

$totalRules = count($rules);

// 상황 수 계산 (situationMapping에서 정의된 것들)
$totalSituations = count($situationMapping);
if (isset($ontology['situations']) && is_array($ontology['situations'])) {
    $totalSituations = max($totalSituations, count($ontology['situations']));
}

// 페르소나 수 계산 (60개 인지 페르소나 시스템)
// math-persona-system.php 및 learning_interface.php에 정의된 60개
$totalPersonas = 60;

// persona_rules.php의 12개 페르소나 유형은 별도 카운트
$personaTypesCount = !empty($personaRulesData) ? count($personaRulesData) : 12;

$implementedActions = count(array_filter($systemActions, function($a) { return $a['implemented']; }));
$totalActions = count($systemActions);

$existingTables = count(array_filter($dbTables, function($t) { return $t['exists']; }));
$totalTables = count($dbTables);

// 전체 완성도 계산
$ruleCompletion = calculateCompletion($totalRules, 70);
$ontologyCompletion = calculateCompletion($totalSituations, 20);
$actionCompletion = calculateCompletion($implementedActions, $totalActions);
$overallCompletion = round(($ruleCompletion + $ontologyCompletion + $actionCompletion) / 3);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI 튜터 온톨로지 로드맵</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Pretendard', -apple-system, sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #e2e8f0;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255,255,255,0.05);
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .header h1 {
            font-size: 2.5rem;
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .header .subtitle {
            color: #94a3b8;
            font-size: 1.1rem;
        }
        
        .overall-score {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            background: rgba(255,255,255,0.05);
        }
        
        .score-circle::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            padding: 4px;
            background: linear-gradient(135deg, var(--color1), var(--color2));
            -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
            mask-composite: exclude;
            -webkit-mask-composite: xor;
        }
        
        .score-circle .value {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .score-circle .label {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .score-circle.clickable {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .score-circle.clickable:hover {
            transform: scale(1.08);
            box-shadow: 0 0 30px rgba(255,255,255,0.15);
        }
        
        .score-circle.clickable:hover::before {
            animation: pulse 1s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .score-circle .click-hint {
            position: absolute;
            bottom: -8px;
            font-size: 0.5rem;
            color: #64748b;
            opacity: 0;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }
        
        .score-circle.clickable:hover .click-hint {
            opacity: 1;
        }
        
        .section {
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 24px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        
        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }
        
        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.08);
            transition: all 0.3s;
        }
        
        .card:hover {
            border-color: rgba(99, 102, 241, 0.5);
            transform: translateY(-2px);
        }
        
        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        
        .card-title {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
        }
        
        .card-icon {
            font-size: 1.5rem;
        }
        
        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .progress-fill.green { background: linear-gradient(90deg, #10b981, #34d399); }
        .progress-fill.yellow { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
        .progress-fill.red { background: linear-gradient(90deg, #ef4444, #f87171); }
        .progress-fill.blue { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        
        .progress-stats {
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #94a3b8;
        }
        
        .status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-badge.complete { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .status-badge.partial { background: rgba(245, 158, 11, 0.2); color: #fbbf24; }
        .status-badge.pending { background: rgba(239, 68, 68, 0.2); color: #f87171; }
        
        .checklist {
            list-style: none;
        }
        
        .checklist li {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .checklist li:last-child {
            border-bottom: none;
        }
        
        .check-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
        
        .check-icon.done { background: #10b981; }
        .check-icon.pending { background: #64748b; }
        
        .roadmap {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .roadmap-phase {
            display: flex;
            gap: 20px;
            align-items: flex-start;
        }
        
        .phase-indicator {
            width: 60px;
            display: flex;
            flex-direction: column;
            align-items: center;
            flex-shrink: 0;
        }
        
        .phase-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.25rem;
        }
        
        .phase-number.current {
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.5);
        }
        
        .phase-number.done {
            background: #10b981;
        }
        
        .phase-number.upcoming {
            background: rgba(255,255,255,0.1);
            color: #64748b;
        }
        
        .phase-line {
            width: 2px;
            flex: 1;
            min-height: 60px;
            background: rgba(255,255,255,0.1);
            margin-top: 8px;
        }
        
        .phase-line.active {
            background: linear-gradient(180deg, #3b82f6, transparent);
        }
        
        .phase-content {
            flex: 1;
            background: rgba(255,255,255,0.03);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.08);
        }
        
        .phase-title {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .phase-desc {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 12px;
        }
        
        .phase-tasks {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .task-tag {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .task-tag.done { border-color: #10b981; color: #34d399; }
        .task-tag.current { border-color: #3b82f6; color: #60a5fa; background: rgba(59, 130, 246, 0.1); }
        
        .diagnostic-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }
        
        .diag-item {
            padding: 16px;
            border-radius: 10px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            text-align: center;
        }
        
        .diag-item .value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .diag-item .label {
            font-size: 0.75rem;
            color: #94a3b8;
        }
        
        .diag-item.good .value { color: #34d399; }
        .diag-item.warning .value { color: #fbbf24; }
        .diag-item.error .value { color: #f87171; }
        
        .test-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .test-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .test-results {
            margin-top: 20px;
            padding: 16px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px;
            display: none;
        }
        
        .test-results.active {
            display: block;
        }
        
        .test-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        
        .test-item:last-child {
            border-bottom: none;
        }
        
        .test-status {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        .test-status.pass { background: #10b981; }
        .test-status.fail { background: #ef4444; }
        .test-status.loading { background: #3b82f6; animation: pulse 1s infinite; }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        @media (max-width: 768px) {
            .overall-score { flex-direction: column; align-items: center; }
            .diagnostic-grid { grid-template-columns: repeat(2, 1fr); }
            .roadmap-phase { flex-direction: column; }
            .phase-indicator { flex-direction: row; width: 100%; }
            .phase-line { width: 100%; height: 2px; min-height: auto; margin: 0 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 헤더 -->
        <div class="header">
            <h1>🗺️ AI 튜터 온톨로지 로드맵</h1>
            <p class="subtitle">룰과 온톨로지 시스템 진단 및 개발 로드맵</p>
            
            <div class="overall-score">
                <div class="score-circle clickable" style="--color1: #10b981; --color2: #34d399;" 
                     onclick="openDetailPage('rules')" title="룰 정의 상세 보기">
                    <span class="value"><?php echo $totalRules; ?></span>
                    <span class="label">룰 정의</span>
                    <span class="click-hint">클릭하여 상세보기</span>
                </div>
                <div class="score-circle clickable" style="--color1: #3b82f6; --color2: #60a5fa;" 
                     onclick="openDetailPage('situations')" title="상황 정의 상세 보기">
                    <span class="value"><?php echo $totalSituations; ?></span>
                    <span class="label">상황 정의</span>
                    <span class="click-hint">클릭하여 상세보기</span>
                </div>
                <div class="score-circle clickable" style="--color1: #8b5cf6; --color2: #a78bfa;" 
                     onclick="openDetailPage('personas')" title="페르소나 상세 보기">
                    <span class="value"><?php echo $totalPersonas; ?></span>
                    <span class="label">페르소나</span>
                    <span class="click-hint">클릭하여 상세보기</span>
                </div>
                <div class="score-circle" style="--color1: #f59e0b; --color2: #fbbf24;">
                    <span class="value"><?php echo $overallCompletion; ?>%</span>
                    <span class="label">전체 완성도</span>
                </div>
            </div>
        </div>
        
        <!-- 진단 요약 -->
        <div class="section">
            <h2 class="section-title">🔍 시스템 진단</h2>
            <div class="diagnostic-grid">
                <div class="diag-item <?php echo $existingTables >= 6 ? 'good' : ($existingTables >= 3 ? 'warning' : 'error'); ?>">
                    <div class="value"><?php echo $existingTables; ?>/<?php echo $totalTables; ?></div>
                    <div class="label">DB 테이블</div>
                </div>
                <div class="diag-item <?php echo $totalRules >= 60 ? 'good' : ($totalRules >= 30 ? 'warning' : 'error'); ?>">
                    <div class="value"><?php echo $totalRules; ?></div>
                    <div class="label">정의된 룰</div>
                </div>
                <div class="diag-item <?php echo $implementedActions >= 10 ? 'good' : ($implementedActions >= 5 ? 'warning' : 'error'); ?>">
                    <div class="value"><?php echo $implementedActions; ?>/<?php echo $totalActions; ?></div>
                    <div class="label">시스템 액션</div>
                </div>
                <div class="diag-item <?php echo $totalPersonas >= 10 ? 'good' : ($totalPersonas >= 5 ? 'warning' : 'error'); ?>">
                    <div class="value"><?php echo $totalPersonas; ?></div>
                    <div class="label">페르소나</div>
                </div>
            </div>
            
            <!-- 실시간 테스트 버튼 -->
            <button class="test-btn" onclick="runDiagnosticTest()">
                🧪 실시간 연동 테스트
            </button>
            
            <div id="testResults" class="test-results">
                <div class="test-item" id="test-rules">
                    <div class="test-status loading">⏳</div>
                    <span>룰 파일 로드 테스트</span>
                </div>
                <div class="test-item" id="test-ontology">
                    <div class="test-status loading">⏳</div>
                    <span>온톨로지 파일 로드 테스트</span>
                </div>
                <div class="test-item" id="test-api">
                    <div class="test-status loading">⏳</div>
                    <span>API 엔드포인트 테스트</span>
                </div>
                <div class="test-item" id="test-session">
                    <div class="test-status loading">⏳</div>
                    <span>세션 시작 룰 매칭 테스트</span>
                </div>
            </div>
        </div>
        
        <!-- 룰 레이어별 상태 -->
        <div class="section">
            <h2 class="section-title">📋 룰 레이어별 완성도</h2>
            <div class="grid">
                <?php foreach ($ruleLayers as $layerId => $layer): 
                    $count = count($layer['rules']);
                    $percent = calculateCompletion($count, $layer['target']);
                    $colorClass = $percent >= 80 ? 'green' : ($percent >= 50 ? 'yellow' : 'red');
                    $statusClass = $percent >= 80 ? 'complete' : ($percent >= 50 ? 'partial' : 'pending');
                    $statusText = $percent >= 80 ? '완료' : ($percent >= 50 ? '진행중' : '시작필요');
                ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <span class="card-icon"><?php echo $layer['icon']; ?></span>
                            <?php echo $layer['name']; ?>
                        </div>
                        <span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $colorClass; ?>" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <div class="progress-stats">
                        <span><?php echo $count; ?> / <?php echo $layer['target']; ?> 룰</span>
                        <span><?php echo $percent; ?>%</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 온톨로지 상황별 상태 -->
        <div class="section">
            <h2 class="section-title">🧠 온톨로지 상황 매핑</h2>
            <div class="grid">
                <?php foreach ($situationCategories as $catId => $category): 
                    $count = count($category['situations']);
                    $percent = calculateCompletion($count, $category['target']);
                    $colorClass = $percent >= 80 ? 'green' : ($percent >= 50 ? 'yellow' : 'red');
                ?>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <span class="card-icon"><?php echo $category['icon']; ?></span>
                            <?php echo $category['name']; ?>
                        </div>
                        <span style="color: #94a3b8; font-size: 0.875rem;"><?php echo $count; ?>개</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill <?php echo $colorClass; ?>" style="width: <?php echo $percent; ?>%"></div>
                    </div>
                    <ul class="checklist">
                        <?php foreach (array_slice($category['situations'], 0, 3) as $sitId => $sit): ?>
                        <li>
                            <span class="check-icon done">✓</span>
                            <span><?php echo $sit['label'] ?? $sitId; ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 시스템 액션 상태 -->
        <div class="section">
            <h2 class="section-title">⚙️ 시스템 액션 구현 상태</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">✅ 구현 완료</div>
                    </div>
                    <ul class="checklist">
                        <?php foreach ($systemActions as $action => $info): if ($info['implemented']): ?>
                        <li>
                            <span class="check-icon done">✓</span>
                            <span><?php echo $info['name']; ?></span>
                        </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">⏳ 구현 필요</div>
                    </div>
                    <ul class="checklist">
                        <?php foreach ($systemActions as $action => $info): if (!$info['implemented']): ?>
                        <li>
                            <span class="check-icon pending">○</span>
                            <span><?php echo $info['name']; ?></span>
                        </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- DB 테이블 상태 -->
        <div class="section">
            <h2 class="section-title">🗄️ 데이터베이스 테이블</h2>
            <div class="grid">
                <?php foreach ($dbTables as $tableName => $info): ?>
                <div class="card" style="padding: 12px 16px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="check-icon <?php echo $info['exists'] ? 'done' : 'pending'; ?>">
                            <?php echo $info['exists'] ? '✓' : '○'; ?>
                        </span>
                        <div>
                            <div style="font-weight: 500; font-size: 0.9rem;"><?php echo $info['name']; ?></div>
                            <div style="font-size: 0.75rem; color: #64748b;">mdl_<?php echo $tableName; ?></div>
                        </div>
                        <?php if ($info['required']): ?>
                        <span class="status-badge" style="margin-left: auto; background: rgba(239, 68, 68, 0.2); color: #f87171;">필수</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- 개발 로드맵 -->
        <div class="section">
            <h2 class="section-title">🚀 향후 개발 로드맵</h2>
            <div class="roadmap">
                <!-- Phase 1 -->
                <div class="roadmap-phase">
                    <div class="phase-indicator">
                        <div class="phase-number done">1</div>
                        <div class="phase-line"></div>
                    </div>
                    <div class="phase-content">
                        <div class="phase-title">Phase 1: 핵심 구조 (완료)</div>
                        <div class="phase-desc">룰 엔진, 온톨로지 기본 구조, 채팅 UI</div>
                        <div class="phase-tasks">
                            <span class="task-tag done">룰 <?php echo $totalRules; ?>개 정의</span>
                            <span class="task-tag done">상황 <?php echo $totalSituations; ?>개 매핑</span>
                            <span class="task-tag done">페르소나 <?php echo $totalPersonas; ?>개</span>
                            <span class="task-tag done">채팅 UI</span>
                            <span class="task-tag done">API 연동</span>
                        </div>
                    </div>
                </div>
                
                <!-- Phase 2 -->
                <div class="roadmap-phase">
                    <div class="phase-indicator">
                        <div class="phase-number current">2</div>
                        <div class="phase-line active"></div>
                    </div>
                    <div class="phase-content">
                        <div class="phase-title">Phase 2: 프론트엔드 연동 (진행중)</div>
                        <div class="phase-desc">이벤트 데이터 완성, 시스템 액션 구현</div>
                        <div class="phase-tasks">
                            <span class="task-tag done">필기 멈춤 감지</span>
                            <span class="task-tag done">감정 선택</span>
                            <span class="task-tag current">지우기 카운트</span>
                            <span class="task-tag current">풀이 시간 측정</span>
                            <span class="task-tag">진행률 추적</span>
                            <span class="task-tag">연속 정답 카운트</span>
                        </div>
                    </div>
                </div>
                
                <!-- Phase 3 -->
                <div class="roadmap-phase">
                    <div class="phase-indicator">
                        <div class="phase-number upcoming">3</div>
                        <div class="phase-line"></div>
                    </div>
                    <div class="phase-content">
                        <div class="phase-title">Phase 3: 시스템 액션</div>
                        <div class="phase-desc">힌트 시스템, 난이도 조절, 시각화</div>
                        <div class="phase-tasks">
                            <span class="task-tag">힌트 API</span>
                            <span class="task-tag">난이도 조절</span>
                            <span class="task-tag">시각화 설명</span>
                            <span class="task-tag">가이드 모드</span>
                            <span class="task-tag">오류 자동 분석</span>
                        </div>
                    </div>
                </div>
                
                <!-- Phase 4 -->
                <div class="roadmap-phase">
                    <div class="phase-indicator">
                        <div class="phase-number upcoming">4</div>
                    </div>
                    <div class="phase-content">
                        <div class="phase-title">Phase 4: 학습 분석 고도화</div>
                        <div class="phase-desc">장기 학습 추적, 페르소나 전환, 적응형 학습</div>
                        <div class="phase-tasks">
                            <span class="task-tag">학습 히스토리</span>
                            <span class="task-tag">페르소나 전환</span>
                            <span class="task-tag">적응형 난이도</span>
                            <span class="task-tag">동적 룰 생성</span>
                            <span class="task-tag">학습 효과 분석</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 빠른 링크 -->
        <div class="section">
            <h2 class="section-title">🔗 빠른 링크</h2>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="learning_interface.php?studentid=1858&contentid=15652&contenttype=topic" class="test-btn" style="background: linear-gradient(135deg, #10b981, #34d399);">
                    📚 학습 인터페이스 테스트
                </a>
                <a href="math-persona-system.php" class="test-btn" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                    🎭 페르소나 도감
                </a>
                <a href="../api/process_interaction.php?student_id=1858&content_id=15652&event_type=session_start" target="_blank" class="test-btn" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                    🔌 API 직접 테스트
                </a>
            </div>
        </div>
        
        <!-- 푸터 -->
        <div style="text-align: center; padding: 20px; color: #64748b; font-size: 0.875rem;">
            마지막 업데이트: <?php echo date('Y-m-d H:i:s'); ?> | AI 튜터 시스템 v2.0
        </div>
    </div>
    
    <script>
    async function runDiagnosticTest() {
        const results = document.getElementById('testResults');
        results.classList.add('active');
        
        // 모든 테스트 로딩 상태로
        document.querySelectorAll('.test-status').forEach(el => {
            el.className = 'test-status loading';
            el.textContent = '⏳';
        });
        
        // 테스트 1: 룰 파일
        await delay(500);
        setTestResult('test-rules', <?php echo $totalRules > 0 ? 'true' : 'false'; ?>);
        
        // 테스트 2: 온톨로지
        await delay(500);
        setTestResult('test-ontology', <?php echo $totalSituations > 0 ? 'true' : 'false'; ?>);
        
        // 테스트 3: API
        await delay(500);
        try {
            const response = await fetch('../api/process_interaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: 1858,
                    content_id: 15652,
                    event_type: 'session_start',
                    unit_name: '테스트'
                })
            });
            const data = await response.json();
            setTestResult('test-api', data.success === true);
        } catch (e) {
            setTestResult('test-api', false);
        }
        
        // 테스트 4: 세션 룰
        await delay(500);
        try {
            const response = await fetch('../api/process_interaction.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: 1858,
                    content_id: 15652,
                    event_type: 'session_start',
                    unit_name: '수학'
                })
            });
            const data = await response.json();
            const hasMessage = data.data && data.data.chat_messages && data.data.chat_messages.length > 0;
            setTestResult('test-session', hasMessage);
        } catch (e) {
            setTestResult('test-session', false);
        }
    }
    
    function setTestResult(id, pass) {
        const el = document.querySelector('#' + id + ' .test-status');
        el.className = 'test-status ' + (pass ? 'pass' : 'fail');
        el.textContent = pass ? '✓' : '✗';
    }
    
    function delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
    
    // 상세 페이지 열기
    function openDetailPage(type) {
        const basePath = '/moodle/local/augmented_teacher/alt42/teachingsupport/AItutor/ui/';
        
        const pages = {
            'rules': 'rules_detail.php',
            'situations': 'situations_detail.php',
            'personas': 'math-persona-system.php'
        };
        
        const page = pages[type];
        if (page) {
            window.open(basePath + page, '_blank');
        }
    }
    </script>
</body>
</html>

