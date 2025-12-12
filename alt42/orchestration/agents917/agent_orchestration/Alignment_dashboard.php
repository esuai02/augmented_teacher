<?php
/**
 * Alignment Dashboard - 에이전트 구현 완성도 및 Quantum Modeling 정렬 현황판
 * 
 * 목적: 22개 AI 에이전트의 미션 대비 구현 완성도를 조망하고,
 *       rules.yaml, questions.md, persona_system, quantum_modeling 정렬 상태를 분석
 * 
 * 위치: alt42/orchestration/agents/agent_orchestration/Alignment_dashboard.php
 * 작성일: 2025-12-07
 * 참고: quantum-orchestration-design.md
 */

// 에이전트 기본 정보 정의
$agents = [
    1 => [
        'id' => 1,
        'name' => 'Onboarding',
        'name_ko' => '온보딩',
        'folder' => 'agent01_onboarding',
        'category' => 'core',
        'mission' => '신규/대상 학생의 기본 프로필과 학습 이력 로딩 시 교사의 노하우를 반영하여 초기 맥락을 정확히 수집',
        'quantum_dims' => ['prior_knowledge', 'goal_setting', 'study_style', 'math_confidence'],
        'outputs_to' => [2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22]
    ],
    2 => [
        'id' => 2,
        'name' => 'Exam Schedule',
        'name_ko' => '시험일정',
        'folder' => 'agent02_exam_schedule',
        'category' => 'core',
        'mission' => '시험 8주 전(D-56일)으로 진입하면 해당 시점의 학습전략을 수립. 학원-학교-집 학습 연계',
        'quantum_dims' => ['time_pressure', 'content_difficulty', 'goal_setting', 'time_management'],
        'inputs_from' => [1],
        'outputs_to' => [3,4,7,14]
    ],
    3 => [
        'id' => 3,
        'name' => 'Goals Analysis',
        'name_ko' => '목표분석',
        'folder' => 'agent03_goals_analysis',
        'category' => 'analysis',
        'mission' => '목표와 계획이 얼마나 잘 연계되었는지, 회복탄력성 있는 구조로 설계되었는지 분석',
        'quantum_dims' => ['goal_setting', 'self_monitoring', 'time_management', 'adaptive_behavior'],
        'inputs_from' => [1,2],
        'outputs_to' => [14,17]
    ],
    4 => [
        'id' => 4,
        'name' => 'Inspect Weakpoints',
        'name_ko' => '취약점검사',
        'folder' => 'agent04_inspect_weakpoints',
        'category' => 'analysis',
        'mission' => '각각의 학습활동에 대한 페르소나(행동유형) 분석과 맞춤 행동유도 시스템 연결',
        'quantum_dims' => ['concept_mastery', 'procedural_fluency', 'discrimination', 'cognitive_flexibility'],
        'inputs_from' => [1,5],
        'outputs_to' => [5,7,16,18]
    ],
    5 => [
        'id' => 5,
        'name' => 'Learning Emotion',
        'name_ko' => '학습감정',
        'folder' => 'agent05_learning_emotion',
        'category' => 'analysis',
        'mission' => '학생의 감정 패턴과 행동유형(페르소나)을 정밀하게 식별, 감정매핑 알고리즘으로 시그너처 루틴 탐색',
        'quantum_dims' => ['motivation', 'anxiety', 'frustration', 'engagement_emotion', 'emotional_regulation'],
        'inputs_from' => [1,4],
        'outputs_to' => [10,11,12,16,21]
    ],
    6 => [
        'id' => 6,
        'name' => 'Teacher Feedback',
        'name_ko' => '교사피드백',
        'folder' => 'agent06_teacher_feedback',
        'category' => 'support',
        'mission' => '교사의 피드백을 분석하고 학습자에게 적절한 방식으로 전달',
        'quantum_dims' => ['teacher_support', 'self_efficacy', 'growth_mindset'],
        'inputs_from' => [1,5],
        'outputs_to' => [8,16,21]
    ],
    7 => [
        'id' => 7,
        'name' => 'Interaction Targeting',
        'name_ko' => '상호작용타게팅',
        'folder' => 'agent07_interaction_targeting',
        'category' => 'analysis',
        'mission' => '학생에게 가장 효과적인 상호작용 유형과 타이밍을 결정',
        'quantum_dims' => ['attention_level', 'engagement_behavior', 'time_of_day'],
        'inputs_from' => [2,4],
        'outputs_to' => [16,19]
    ],
    8 => [
        'id' => 8,
        'name' => 'Calmness',
        'name_ko' => '침착도',
        'folder' => 'agent08_calmness',
        'category' => 'support',
        'mission' => '학생의 현재 침착도 상태를 분석하고 안정화 전략 제공',
        'quantum_dims' => ['anxiety', 'emotional_regulation', 'confidence', 'resilience'],
        'inputs_from' => [5,6],
        'outputs_to' => [13,20]
    ],
    9 => [
        'id' => 9,
        'name' => 'Learning Management',
        'name_ko' => '학습관리',
        'folder' => 'agent09_learning_management',
        'category' => 'support',
        'mission' => '학습 일정과 진도를 종합적으로 관리하고 조정',
        'quantum_dims' => ['self_regulation', 'time_management', 'goal_setting', 'practice_frequency'],
        'inputs_from' => [1,3],
        'outputs_to' => [11,14,17]
    ],
    10 => [
        'id' => 10,
        'name' => 'Concept Notes',
        'name_ko' => '개념노트',
        'folder' => 'agent10_concept_notes',
        'category' => 'execution',
        'mission' => '개념 이해를 위한 노트 작성과 정리 지원',
        'quantum_dims' => ['note_taking', 'encoding_depth', 'schema_activation', 'elaboration'],
        'inputs_from' => [5],
        'outputs_to' => [11,16]
    ],
    11 => [
        'id' => 11,
        'name' => 'Problem Notes',
        'name_ko' => '문제노트',
        'folder' => 'agent11_problem_notes',
        'category' => 'execution',
        'mission' => '문제 풀이 과정과 오답을 체계적으로 기록',
        'quantum_dims' => ['note_taking', 'problem_representation', 'metacognition', 'retrieval_strength'],
        'inputs_from' => [5,10],
        'outputs_to' => [16]
    ],
    12 => [
        'id' => 12,
        'name' => 'Rest Routine',
        'name_ko' => '휴식루틴',
        'folder' => 'agent12_rest_routine',
        'category' => 'support',
        'mission' => '학습 중 적절한 휴식 패턴과 루틴을 권장',
        'quantum_dims' => ['physical_fatigue', 'break_pattern', 'session_duration', 'sleep_quality'],
        'inputs_from' => [5],
        'outputs_to' => [8,13]
    ],
    13 => [
        'id' => 13,
        'name' => 'Learning Dropout',
        'name_ko' => '학습이탈',
        'folder' => 'agent13_learning_dropout',
        'category' => 'analysis',
        'mission' => '학습 이탈 위험을 조기에 감지하고 예방 전략 제시',
        'quantum_dims' => ['boredom', 'frustration', 'engagement_behavior', 'persistence'],
        'inputs_from' => [8,12],
        'outputs_to' => [20,21]
    ],
    14 => [
        'id' => 14,
        'name' => 'Current Position',
        'name_ko' => '현재위치',
        'folder' => 'agent14_current_position',
        'category' => 'analysis',
        'mission' => '학생의 현재 학습 위치와 진도를 정확히 파악',
        'quantum_dims' => ['concept_mastery', 'prior_knowledge', 'content_difficulty'],
        'inputs_from' => [3,9],
        'outputs_to' => [15,17,21]
    ],
    15 => [
        'id' => 15,
        'name' => 'Problem Redefinition',
        'name_ko' => '문제재정의',
        'folder' => 'agent15_problem_redefinition',
        'category' => 'analysis',
        'mission' => '학습 문제를 재정의하고 핵심 개선 방향 도출',
        'quantum_dims' => ['problem_representation', 'metacognition', 'cognitive_flexibility'],
        'inputs_from' => [14],
        'outputs_to' => [16,17,19]
    ],
    16 => [
        'id' => 16,
        'name' => 'Interaction Preparation',
        'name_ko' => '상호작용준비',
        'folder' => 'agent16_interaction_preparation',
        'category' => 'execution',
        'mission' => '상호작용에 적합한 세계관과 테마를 선택하여 스토리텔링 준비',
        'quantum_dims' => ['interest', 'curiosity', 'engagement_emotion'],
        'inputs_from' => [4,5,7,10,11,15],
        'outputs_to' => [19]
    ],
    17 => [
        'id' => 17,
        'name' => 'Remaining Activities',
        'name_ko' => '잔여활동',
        'folder' => 'agent17_remaining_activities',
        'category' => 'execution',
        'mission' => '학생의 잔여 학습 활동을 분석하고 조정하여 목표 대비 완료율 향상',
        'quantum_dims' => ['time_management', 'effort_investment', 'adaptive_behavior'],
        'inputs_from' => [3,4,14,15],
        'outputs_to' => [19,20]
    ],
    18 => [
        'id' => 18,
        'name' => 'Signature Routine',
        'name_ko' => '시그너처루틴',
        'folder' => 'agent18_signature_routine',
        'category' => 'execution',
        'mission' => '개인 최적 학습 루틴(시그너처)을 발견하고 정교화',
        'quantum_dims' => ['self_regulation', 'practice_frequency', 'engagement_behavior'],
        'inputs_from' => [1,4,5],
        'outputs_to' => [19]
    ],
    19 => [
        'id' => 19,
        'name' => 'Interaction Content',
        'name_ko' => '상호작용컨텐츠',
        'folder' => 'agent19_interaction_content',
        'category' => 'execution',
        'mission' => '맞춤형 상호작용 컨텐츠 생성 및 패키징',
        'quantum_dims' => ['interest', 'engagement_emotion', 'curiosity'],
        'inputs_from' => [15,16,17,18],
        'outputs_to' => [20]
    ],
    20 => [
        'id' => 20,
        'name' => 'Intervention Preparation',
        'name_ko' => '개입준비',
        'folder' => 'agent20_intervention_preparation',
        'category' => 'execution',
        'mission' => '개입 실행을 위한 리소스/메시지/타이밍 사전 준비',
        'quantum_dims' => ['time_of_day', 'attention_level', 'emotional_regulation'],
        'inputs_from' => [8,13,19],
        'outputs_to' => [21]
    ],
    21 => [
        'id' => 21,
        'name' => 'Intervention Execution',
        'name_ko' => '개입실행',
        'folder' => 'agent21_intervention_execution',
        'category' => 'execution',
        'mission' => '최종적으로 개입을 실행하고, 결과를 기록하며, 효과를 모니터링',
        'quantum_dims' => ['metacognition', 'self_monitoring', 'adaptive_behavior'],
        'inputs_from' => [1,5,13,14,17,19,20],
        'outputs_to' => [19,20,22]
    ],
    22 => [
        'id' => 22,
        'name' => 'Module Improvement',
        'name_ko' => '모듈개선',
        'folder' => 'agent22_module_improvement',
        'category' => 'support',
        'mission' => '모듈 성능 개선 제안 및 자가 업그레이드',
        'quantum_dims' => ['metacognition', 'adaptive_behavior'],
        'inputs_from' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21],
        'outputs_to' => [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21]
    ]
];

// Quantum State Vector 64차원 정의 (quantum-orchestration-design.md 기반)
$quantumDimensions = [
    'cognitive' => [
        'concept_mastery', 'procedural_fluency', 'cognitive_load', 'attention_level',
        'working_memory', 'metacognition', 'transfer_ability', 'problem_representation',
        'schema_activation', 'retrieval_strength', 'encoding_depth', 'elaboration',
        'retention_strength', 'discrimination', 'generalization', 'cognitive_flexibility'
    ],
    'emotional' => [
        'motivation', 'self_efficacy', 'confidence', 'curiosity', 'interest',
        'anxiety', 'frustration', 'boredom', 'confusion', 'engagement_emotion',
        'achievement_emotion', 'social_emotion', 'epistemic_emotion', 'growth_mindset',
        'resilience', 'emotional_regulation'
    ],
    'behavioral' => [
        'engagement_behavior', 'persistence', 'help_seeking', 'self_regulation',
        'time_management', 'effort_investment', 'strategy_use', 'practice_frequency',
        'review_behavior', 'note_taking', 'question_asking', 'collaboration',
        'resource_utilization', 'goal_setting', 'self_monitoring', 'adaptive_behavior'
    ],
    'contextual' => [
        'time_pressure', 'time_of_day', 'session_duration', 'break_pattern',
        'social_context', 'peer_influence', 'teacher_support', 'family_support',
        'physical_fatigue', 'sleep_quality', 'nutrition_state', 'environment_fit',
        'distraction_level', 'technology_access', 'content_difficulty', 'prior_knowledge'
    ]
];

// 파일 존재 여부 체크 함수
function checkFileExists($agentFolder, $relativePath) {
    $basePath = dirname(__FILE__) . '/../' . $agentFolder . '/' . $relativePath;
    return file_exists($basePath);
}

// 파일 라인 수 계산 함수
function getFileLineCount($agentFolder, $relativePath) {
    $basePath = dirname(__FILE__) . '/../' . $agentFolder . '/' . $relativePath;
    if (file_exists($basePath)) {
        return count(file($basePath));
    }
    return 0;
}

// YAML 룰 개수 추출 함수
function getRuleCount($agentFolder, $relativePath) {
    $basePath = dirname(__FILE__) . '/../' . $agentFolder . '/' . $relativePath;
    if (file_exists($basePath)) {
        $content = file_get_contents($basePath);
        preg_match_all('/rule_id:\s*["\']?([^"\']+)["\']?/i', $content, $matches);
        return count($matches[0]);
    }
    return 0;
}

// 에이전트별 구현 상태 분석 함수
function analyzeAgentImplementation($agent) {
    $folder = $agent['folder'];
    
    $status = [
        'rules_yaml' => [
            'exists' => checkFileExists($folder, 'rules/rules.yaml'),
            'lines' => getFileLineCount($folder, 'rules/rules.yaml'),
            'rule_count' => getRuleCount($folder, 'rules/rules.yaml')
        ],
        'questions_md' => [
            'exists' => checkFileExists($folder, 'rules/questions.md'),
            'lines' => getFileLineCount($folder, 'rules/questions.md')
        ],
        'mission_md' => [
            'exists' => checkFileExists($folder, 'rules/mission.md'),
            'lines' => getFileLineCount($folder, 'rules/mission.md')
        ],
        'persona_rules' => [
            'exists' => checkFileExists($folder, 'persona_system/rules.yaml'),
            'lines' => getFileLineCount($folder, 'persona_system/rules.yaml'),
            'rule_count' => getRuleCount($folder, 'persona_system/rules.yaml')
        ],
        'persona_md' => [
            'exists' => checkFileExists($folder, 'persona_system/personas.md'),
            'lines' => getFileLineCount($folder, 'persona_system/personas.md')
        ],
        'ontology' => [
            'exists' => checkFileExists($folder, 'ontology/OntologyEngine.php') || 
                       checkFileExists($folder, '온톨로지.jsonld'),
            'files' => 0
        ],
        'quantum_modeling' => [
            'exists' => checkFileExists($folder, 'quantum_modeling/') || 
                       checkFileExists($folder, 'quantum_modeling/README.md'),
            'has_engine' => checkFileExists($folder, 'quantum_modeling/QuantumPersonaEngine.php')
        ],
        'dataindex' => [
            'exists' => checkFileExists($folder, 'dataindex.php'),
            'lines' => getFileLineCount($folder, 'dataindex.php')
        ],
        'api' => [
            'exists' => is_dir(dirname(__FILE__) . '/../' . $folder . '/api')
        ]
    ];
    
    // 완성도 계산
    $completionScore = 0;
    $maxScore = 100;
    
    // 가중치 적용
    if ($status['rules_yaml']['exists']) $completionScore += 20;
    if ($status['rules_yaml']['rule_count'] >= 10) $completionScore += 5;
    if ($status['rules_yaml']['rule_count'] >= 30) $completionScore += 5;
    
    if ($status['questions_md']['exists']) $completionScore += 10;
    if ($status['questions_md']['lines'] >= 50) $completionScore += 5;
    
    if ($status['mission_md']['exists']) $completionScore += 5;
    
    if ($status['persona_rules']['exists']) $completionScore += 15;
    if ($status['persona_rules']['rule_count'] >= 5) $completionScore += 5;
    
    if ($status['persona_md']['exists']) $completionScore += 10;
    
    if ($status['ontology']['exists']) $completionScore += 10;
    
    if ($status['quantum_modeling']['exists']) $completionScore += 5;
    if ($status['quantum_modeling']['has_engine']) $completionScore += 5;
    
    $status['completion_score'] = min($completionScore, 100);
    
    return $status;
}

// Quantum 정렬도 계산 함수
function calculateQuantumAlignment($agent, $quantumDimensions) {
    $agentDims = $agent['quantum_dims'] ?? [];
    $allDims = array_merge(...array_values($quantumDimensions));
    
    $mappedCount = 0;
    $totalDims = count($agentDims);
    
    foreach ($agentDims as $dim) {
        if (in_array($dim, $allDims)) {
            $mappedCount++;
        }
    }
    
    return $totalDims > 0 ? round(($mappedCount / $totalDims) * 100) : 0;
}

// 카테고리별 색상
$categoryColors = [
    'core' => '#4CAF50',
    'analysis' => '#2196F3',
    'support' => '#FF9800',
    'execution' => '#9C27B0'
];

$categoryNames = [
    'core' => '핵심 에이전트',
    'analysis' => '분석 에이전트',
    'support' => '지원 에이전트',
    'execution' => '실행 에이전트'
];

// 전체 통계 계산
$totalCompletion = 0;
$totalQuantumAlignment = 0;
$agentStats = [];

foreach ($agents as $id => $agent) {
    $impl = analyzeAgentImplementation($agent);
    $quantumAlign = calculateQuantumAlignment($agent, $quantumDimensions);
    
    $agentStats[$id] = [
        'agent' => $agent,
        'implementation' => $impl,
        'quantum_alignment' => $quantumAlign
    ];
    
    $totalCompletion += $impl['completion_score'];
    $totalQuantumAlignment += $quantumAlign;
}

$avgCompletion = round($totalCompletion / count($agents));
$avgQuantumAlignment = round($totalQuantumAlignment / count($agents));
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alignment Dashboard - 에이전트 구현 완성도 및 Quantum Modeling 정렬 현황</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Pretendard', 'Malgun Gothic', '맑은 고딕', sans-serif;
            background: linear-gradient(135deg, #0a0a1a 0%, #1a1a3e 50%, #0d2847 100%);
            min-height: 100vh;
            padding: 0;
            line-height: 1.6;
            color: #e0e0e0;
        }

        .nav-dropdown {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1000;
            display: flex;
            gap: 2px;
            align-items: flex-start;
        }

        .nav-dropdown select {
            padding: 10px 15px;
            border: 2px solid rgba(0,255,255,0.3);
            border-top: none;
            border-left: none;
            border-right: none;
            background: rgba(26,26,46,0.95);
            color: #00ffff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            min-width: 200px;
            height: 42px;
            box-shadow: 0 2px 8px rgba(0,255,255,0.2);
        }

        .container {
            max-width: 1800px;
            margin: 0 auto;
            padding: 30px;
            padding-top: 70px;
        }

        .header-section {
            text-align: center;
            margin-bottom: 30px;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(0,255,255,0.2);
            backdrop-filter: blur(10px);
        }

        h1 {
            color: #00ffff;
            font-size: 2rem;
            margin-bottom: 10px;
            text-shadow: 0 0 20px rgba(0,255,255,0.5);
        }

        .subtitle {
            color: #aaa;
            font-size: 1rem;
        }

        /* 전체 통계 카드 */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, #00ffff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card .label {
            color: #888;
            font-size: 0.9rem;
            margin-top: 5px;
        }

        /* 필터 탭 */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 10px 20px;
            border-radius: 20px;
            cursor: pointer;
            border: 1px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.05);
            color: #ccc;
            transition: all 0.3s;
        }

        .filter-tab:hover, .filter-tab.active {
            background: rgba(0,255,255,0.2);
            border-color: #00ffff;
            color: #00ffff;
        }

        /* 에이전트 그리드 */
        .agents-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
        }

        .agent-card {
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: all 0.3s;
        }

        .agent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,255,255,0.2);
            border-color: rgba(0,255,255,0.5);
        }

        .agent-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }

        .agent-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .agent-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
            color: white;
        }

        .agent-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #fff;
        }

        .agent-name-ko {
            font-size: 0.85rem;
            color: #888;
        }

        .category-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .agent-mission {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        /* 진행률 바 */
        .progress-section {
            margin-bottom: 15px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .progress-fill.completion {
            background: linear-gradient(90deg, #00ff88, #00ffff);
        }

        .progress-fill.quantum {
            background: linear-gradient(90deg, #ff6b6b, #ffd93d, #6bcb77);
        }

        /* 구현 상태 그리드 */
        .impl-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-top: 15px;
        }

        .impl-item {
            padding: 8px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            text-align: center;
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .impl-item.exists {
            background: rgba(0,255,136,0.15);
            border-color: rgba(0,255,136,0.3);
            color: #00ff88;
        }

        .impl-item.missing {
            background: rgba(255,107,107,0.15);
            border-color: rgba(255,107,107,0.3);
            color: #ff6b6b;
        }

        .impl-item.partial {
            background: rgba(255,217,61,0.15);
            border-color: rgba(255,217,61,0.3);
            color: #ffd93d;
        }

        .impl-item .count {
            font-weight: bold;
            display: block;
        }

        /* Quantum 차원 태그 */
        .quantum-dims {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }

        .quantum-dim {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
            background: rgba(138,43,226,0.2);
            border: 1px solid rgba(138,43,226,0.4);
            color: #da70d6;
        }

        /* 잔여 개발 섹션 */
        .remaining-section {
            margin-top: 30px;
            background: rgba(255,255,255,0.03);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .remaining-section h2 {
            color: #ffd93d;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .remaining-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
        }

        .remaining-item {
            padding: 15px;
            background: rgba(255,217,61,0.1);
            border-radius: 10px;
            border-left: 4px solid #ffd93d;
        }

        .remaining-item .agent-ref {
            font-weight: bold;
            color: #ffd93d;
        }

        .remaining-item .task {
            font-size: 0.9rem;
            color: #ccc;
            margin-top: 5px;
        }

        /* Quantum Modeling 섹션 */
        .quantum-section {
            margin-top: 30px;
            background: rgba(138,43,226,0.1);
            border-radius: 15px;
            padding: 25px;
            border: 1px solid rgba(138,43,226,0.3);
        }

        .quantum-section h2 {
            color: #da70d6;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .dimension-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .dimension-category {
            background: rgba(255,255,255,0.03);
            border-radius: 10px;
            padding: 15px;
        }

        .dimension-category h3 {
            font-size: 0.9rem;
            color: #00ffff;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .dim-list {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .dim-item {
            font-size: 0.75rem;
            color: #aaa;
            padding: 3px 0;
        }

        .dim-item.used {
            color: #00ff88;
        }

        /* 반응형 */
        @media (max-width: 768px) {
            .dimension-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .agents-grid {
                grid-template-columns: 1fr;
            }
        }

        /* 스크롤바 스타일 */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.05);
        }

        ::-webkit-scrollbar-thumb {
            background: rgba(0,255,255,0.3);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(0,255,255,0.5);
        }
    </style>
</head>
<body>
    <div class="nav-dropdown">
        <select onchange="if(this.value) location.href=this.value">
            <option value="">📋 메뉴 선택</option>
            <option value="agentmission.html">1. 에이전트 미션</option>
            <option value="questions.html">2. 주요 요청들</option>
            <option value="dataindex.php">3. 데이터 통합</option>
            <option value="rules_viewer.html">4. 에이전트 룰들</option>
            <option value="progress_dashboard.php">5. Mathking AI 조교</option>
            <option value="heartbeat_dashboard.html">6. Heartbeat Dashboard</option>
            <option value="#">7. 에이전트 가드닝</option>
            <option value="#">8. 페르소나 테스트</option>
            <option value="quantum_modeling.html">9. Quantum Modeling</option>
            <option value="Alignment_dashboard.php" selected>10. Alignment Dashboard</option>
        </select>
    </div>

    <div class="container">
        <div class="header-section">
            <h1>🎯 Alignment Dashboard</h1>
            <p class="subtitle">22개 AI 에이전트 구현 완성도 및 Quantum Modeling 정렬 현황</p>
            <p class="subtitle" style="color: #666; font-size: 0.85rem; margin-top: 10px;">
                참고: quantum-orchestration-design.md | 64차원 상태벡터 × 21개 에이전트 매핑
            </p>
        </div>

        <!-- 전체 통계 -->
        <div class="stats-overview">
            <div class="stat-card">
                <div class="value"><?= count($agents) ?></div>
                <div class="label">전체 에이전트</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $avgCompletion ?>%</div>
                <div class="label">평균 구현 완성도</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $avgQuantumAlignment ?>%</div>
                <div class="label">평균 Quantum 정렬도</div>
            </div>
            <div class="stat-card">
                <div class="value">64</div>
                <div class="label">상태 벡터 차원</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count($agents) > 1 ? count($agents) * (count($agents) - 1) / 2 : 0 ?></div>
                <div class="label">에이전트 간 연결</div>
            </div>
        </div>

        <!-- 필터 탭 -->
        <div class="filter-tabs">
            <div class="filter-tab active" onclick="filterAgents('all')">전체</div>
            <?php foreach ($categoryNames as $cat => $name): ?>
            <div class="filter-tab" onclick="filterAgents('<?= $cat ?>')" style="border-color: <?= $categoryColors[$cat] ?>;">
                <?= $name ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 에이전트 그리드 -->
        <div class="agents-grid">
            <?php foreach ($agentStats as $id => $stat): 
                $agent = $stat['agent'];
                $impl = $stat['implementation'];
                $qAlign = $stat['quantum_alignment'];
                $catColor = $categoryColors[$agent['category']];
            ?>
            <div class="agent-card" data-category="<?= $agent['category'] ?>">
                <div class="agent-header">
                    <div class="agent-title">
                        <div class="agent-number" style="background: <?= $catColor ?>;">
                            <?= sprintf('%02d', $id) ?>
                        </div>
                        <div>
                            <div class="agent-name"><?= $agent['name'] ?></div>
                            <div class="agent-name-ko"><?= $agent['name_ko'] ?></div>
                        </div>
                    </div>
                    <span class="category-badge" style="background: <?= $catColor ?>20; color: <?= $catColor ?>; border: 1px solid <?= $catColor ?>50;">
                        <?= $categoryNames[$agent['category']] ?>
                    </span>
                </div>

                <div class="agent-mission"><?= $agent['mission'] ?></div>

                <!-- 구현 완성도 -->
                <div class="progress-section">
                    <div class="progress-label">
                        <span>구현 완성도</span>
                        <span style="color: #00ffff;"><?= $impl['completion_score'] ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill completion" style="width: <?= $impl['completion_score'] ?>%;"></div>
                    </div>
                </div>

                <!-- Quantum 정렬도 -->
                <div class="progress-section">
                    <div class="progress-label">
                        <span>Quantum 정렬도</span>
                        <span style="color: #da70d6;"><?= $qAlign ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill quantum" style="width: <?= $qAlign ?>%;"></div>
                    </div>
                </div>

                <!-- 구현 상태 그리드 -->
                <div class="impl-grid">
                    <div class="impl-item <?= $impl['rules_yaml']['exists'] ? 'exists' : 'missing' ?>">
                        rules.yaml
                        <span class="count"><?= $impl['rules_yaml']['rule_count'] ?: '-' ?> rules</span>
                    </div>
                    <div class="impl-item <?= $impl['questions_md']['exists'] ? 'exists' : 'missing' ?>">
                        questions.md
                        <span class="count"><?= $impl['questions_md']['lines'] ?: '-' ?> lines</span>
                    </div>
                    <div class="impl-item <?= $impl['persona_rules']['exists'] ? 'exists' : 'missing' ?>">
                        persona rules
                        <span class="count"><?= $impl['persona_rules']['rule_count'] ?: '-' ?> rules</span>
                    </div>
                    <div class="impl-item <?= $impl['persona_md']['exists'] ? 'exists' : 'missing' ?>">
                        personas.md
                        <span class="count"><?= $impl['persona_md']['lines'] ?: '-' ?> lines</span>
                    </div>
                    <div class="impl-item <?= $impl['ontology']['exists'] ? 'exists' : 'missing' ?>">
                        ontology
                        <span class="count"><?= $impl['ontology']['exists'] ? '✓' : '✗' ?></span>
                    </div>
                    <div class="impl-item <?= $impl['quantum_modeling']['exists'] ? ($impl['quantum_modeling']['has_engine'] ? 'exists' : 'partial') : 'missing' ?>">
                        quantum
                        <span class="count"><?= $impl['quantum_modeling']['has_engine'] ? 'Engine' : ($impl['quantum_modeling']['exists'] ? 'Basic' : '✗') ?></span>
                    </div>
                </div>

                <!-- Quantum 차원 태그 -->
                <div class="quantum-dims">
                    <?php foreach ($agent['quantum_dims'] as $dim): ?>
                    <span class="quantum-dim"><?= $dim ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Quantum Modeling 상세 섹션 -->
        <div class="quantum-section">
            <h2>🔮 Quantum State Vector - 64차원 매핑 현황</h2>
            <p style="color: #999; margin-bottom: 20px; font-size: 0.9rem;">
                quantum-orchestration-design.md 기반 StudentStateVector 64차원과 에이전트 연결 상태
            </p>
            
            <div class="dimension-grid">
                <?php 
                // 사용 중인 차원 집계
                $usedDims = [];
                foreach ($agents as $agent) {
                    foreach ($agent['quantum_dims'] as $dim) {
                        if (!isset($usedDims[$dim])) $usedDims[$dim] = [];
                        $usedDims[$dim][] = $agent['id'];
                    }
                }
                
                foreach ($quantumDimensions as $category => $dims): 
                    $categoryLabels = [
                        'cognitive' => '🧠 인지 차원 (16)',
                        'emotional' => '💝 정서 차원 (16)',
                        'behavioral' => '🎯 행동 차원 (16)',
                        'contextual' => '🌍 컨텍스트 차원 (16)'
                    ];
                ?>
                <div class="dimension-category">
                    <h3><?= $categoryLabels[$category] ?></h3>
                    <div class="dim-list">
                        <?php foreach ($dims as $dim): 
                            $isUsed = isset($usedDims[$dim]);
                            $agentCount = $isUsed ? count($usedDims[$dim]) : 0;
                        ?>
                        <div class="dim-item <?= $isUsed ? 'used' : '' ?>">
                            <?= $dim ?>
                            <?php if ($agentCount > 0): ?>
                            <span style="color: #666;">(<?= $agentCount ?>)</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 잔여 개발 내용 -->
        <div class="remaining-section">
            <h2>📋 잔여 개발 내용</h2>
            <div class="remaining-list">
                <?php foreach ($agentStats as $id => $stat): 
                    $agent = $stat['agent'];
                    $impl = $stat['implementation'];
                    $remainingTasks = [];
                    
                    if (!$impl['rules_yaml']['exists']) $remainingTasks[] = 'rules.yaml 생성 필요';
                    elseif ($impl['rules_yaml']['rule_count'] < 10) $remainingTasks[] = 'rules.yaml 룰 보강 필요 (현재 ' . $impl['rules_yaml']['rule_count'] . '개)';
                    
                    if (!$impl['questions_md']['exists']) $remainingTasks[] = 'questions.md 생성 필요';
                    
                    if (!$impl['persona_rules']['exists']) $remainingTasks[] = 'persona_system/rules.yaml 생성 필요';
                    
                    if (!$impl['persona_md']['exists']) $remainingTasks[] = 'personas.md 생성 필요';
                    
                    if (!$impl['ontology']['exists']) $remainingTasks[] = '온톨로지 연동 필요';
                    
                    if (!$impl['quantum_modeling']['exists']) $remainingTasks[] = 'Quantum Modeling 폴더 구성 필요';
                    elseif (!$impl['quantum_modeling']['has_engine']) $remainingTasks[] = 'QuantumPersonaEngine.php 구현 필요';
                    
                    if (count($remainingTasks) > 0):
                ?>
                <div class="remaining-item">
                    <div class="agent-ref">Agent <?= sprintf('%02d', $id) ?>: <?= $agent['name_ko'] ?></div>
                    <div class="task">
                        <ul style="margin: 0; padding-left: 20px;">
                            <?php foreach ($remainingTasks as $task): ?>
                            <li><?= $task ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function filterAgents(category) {
            const cards = document.querySelectorAll('.agent-card');
            const tabs = document.querySelectorAll('.filter-tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

