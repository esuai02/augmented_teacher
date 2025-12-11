<?php
/**
 * Quantum Orchestration POC Dashboard
 * ====================================
 * 구조적 완결성 추적 및 모듈 상태 확인
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/pocdashboard.php
 *
 * @author Claude Code
 * @version 1.2.1
 * @date 2025-12-07
 * @updated ast.parse() 사용 - py_compile __pycache__ 권한 문제 해결
 */

// 출력 버퍼링 시작 (Moodle 출력 캡처)
ob_start();

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
 

// AJAX 요청 처리 (JSON 응답은 버퍼 정리 후 출력)
$action = $_GET['action'] ?? 'dashboard';
if (in_array($action, ['run_scenario', 'run_quantum_test', 'check_module', 'debug_pycompile', 'check_all_modules'])) {
    // Moodle 출력 버퍼 비우고 JSON 반환
    ob_end_clean();
}

// 현재 파일 위치
define('HOLONS_PATH', __DIR__);
define('AGENTS_PATH', dirname(dirname(dirname(HOLONS_PATH))) . '/agents');

// 에러 핸들링
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * 모듈 상태 체크 클래스
 */
class ModuleChecker {
    private $results = [];

    /**
     * Python 파일 존재 및 문법 체크
     * v1.2.1 - ast.parse() 사용 (py_compile의 __pycache__ 권한 문제 해결)
     */
    public function checkPythonFile($filepath, $moduleName) {
        $result = [
            'name' => $moduleName,
            'path' => $filepath,
            'exists' => false,
            'syntax_valid' => false,
            'size' => 0,
            'lines' => 0,
            'status' => 'error',
            'message' => ''
        ];

        if (file_exists($filepath)) {
            $result['exists'] = true;
            $result['size'] = filesize($filepath);
            $result['lines'] = count(file($filepath));

            // Python 문법 체크 (ast.parse - 파일 쓰기 없이 문법만 검증)
            $output = [];
            $returnCode = 0;
            $cmd = "python3 -c \"import ast; ast.parse(open(" . escapeshellarg($filepath) . ", encoding='utf-8').read())\" 2>&1";
            exec($cmd, $output, $returnCode);

            if ($returnCode === 0) {
                $result['syntax_valid'] = true;
                $result['status'] = 'success';
                $result['message'] = "✅ 정상 ({$result['lines']} lines, " . round($result['size']/1024, 1) . "KB)";
            } else {
                $result['status'] = 'error';
                $errorMsg = implode("\n", $output);
                // 간략화된 에러 메시지
                if (preg_match('/SyntaxError:(.+)/', $errorMsg, $matches)) {
                    $result['message'] = "❌ 문법 오류:" . trim($matches[1]);
                } else {
                    $result['message'] = "❌ 오류: " . substr($errorMsg, 0, 100);
                }
            }
        } else {
            $result['message'] = "❌ 파일 없음";
        }

        $this->results[$moduleName] = $result;
        return $result;
    }

    /**
     * 에이전트 폴더 체크
     */
    public function checkAgentFolder($agentNum) {
        $agentNames = [
            1 => 'onboarding',
            2 => 'exam_schedule',
            3 => 'goals_analysis',
            4 => 'inspect_weakpoints'
        ];

        $agentName = $agentNames[$agentNum] ?? "agent{$agentNum}";
        $folderName = sprintf("agent%02d_%s", $agentNum, $agentName);
        $agentPath = AGENTS_PATH . '/' . $folderName;

        $result = [
            'name' => "Agent {$agentNum}: {$agentName}",
            'path' => $agentPath,
            'exists' => false,
            'has_rules' => false,
            'rules_count' => 0,
            'status' => 'error',
            'message' => ''
        ];

        if (is_dir($agentPath)) {
            $result['exists'] = true;

            $rulesPath = $agentPath . '/rules/rules.yaml';
            if (file_exists($rulesPath)) {
                $result['has_rules'] = true;
                $content = file_get_contents($rulesPath);
                // 대략적인 룰 개수 (rule_id 등장 횟수)
                $result['rules_count'] = substr_count($content, 'rule_id:');
                $result['status'] = 'success';
                $result['message'] = "✅ rules.yaml ({$result['rules_count']} rules)";
            } else {
                $result['message'] = "⚠️ 폴더 있음, rules.yaml 없음";
                $result['status'] = 'warning';
            }
        } else {
            $result['message'] = "❌ 폴더 없음";
        }

        return $result;
    }

    /**
     * Quantum 테스트 실행
     */
    public function runQuantumTest() {
        $testFile = HOLONS_PATH . '/_quantum_minimal_test.py';

        if (!file_exists($testFile)) {
            return [
                'status' => 'error',
                'message' => '❌ _quantum_minimal_test.py 파일 없음',
                'output' => ''
            ];
        }

        $output = [];
        $returnCode = 0;
        exec("cd " . escapeshellarg(HOLONS_PATH) . " && python3 _quantum_minimal_test.py 2>&1", $output, $returnCode);

        return [
            'status' => $returnCode === 0 ? 'success' : 'error',
            'message' => $returnCode === 0 ? '✅ 테스트 통과' : '❌ 테스트 실패',
            'output' => implode("\n", $output)
        ];
    }

    public function getResults() {
        return $this->results;
    }
}

// 모듈 체커 인스턴스 생성
$checker = new ModuleChecker();

// AJAX 요청 처리
if ($action === 'run_quantum_test') {
    header('Content-Type: application/json');
    echo json_encode($checker->runQuantumTest());
    exit;
}

if ($action === 'check_module') {
    header('Content-Type: application/json');
    $module = $_GET['module'] ?? '';
    $path = HOLONS_PATH . '/' . $module;
    echo json_encode($checker->checkPythonFile($path, $module));
    exit;
}

// 디버그 엔드포인트 - py_compile 상세 정보 확인
if ($action === 'debug_pycompile') {
    header('Content-Type: application/json');
    $debug = [
        'python_version' => shell_exec('python3 --version 2>&1'),
        'python_path' => shell_exec('which python3 2>&1'),
        'holons_path' => HOLONS_PATH,
        'test_file' => HOLONS_PATH . '/_quantum_minimal_test.py',
        'file_exists' => file_exists(HOLONS_PATH . '/_quantum_minimal_test.py'),
        'file_readable' => is_readable(HOLONS_PATH . '/_quantum_minimal_test.py'),
    ];

    // py_compile 테스트
    $testFile = HOLONS_PATH . '/_quantum_minimal_test.py';
    $cmd = "python3 -m py_compile " . escapeshellarg($testFile) . " 2>&1";
    $debug['py_compile_cmd'] = $cmd;

    $output = [];
    $returnCode = 0;
    exec($cmd, $output, $returnCode);

    $debug['py_compile_output'] = implode("\n", $output);
    $debug['py_compile_return_code'] = $returnCode;
    $debug['py_compile_success'] = ($returnCode === 0);

    // 다른 모듈들도 테스트
    $testModules = ['_utils.py', '_brain_engine.py'];
    foreach ($testModules as $mod) {
        $modPath = HOLONS_PATH . '/' . $mod;
        $modOutput = [];
        $modReturn = 0;
        exec("python3 -m py_compile " . escapeshellarg($modPath) . " 2>&1", $modOutput, $modReturn);
        $debug['modules'][$mod] = [
            'exists' => file_exists($modPath),
            'return_code' => $modReturn,
            'output' => implode("\n", $modOutput)
        ];
    }

    echo json_encode($debug, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 전체 모듈 검증 (페이지 로드용)
if ($action === 'check_all_modules') {
    header('Content-Type: application/json');
    $allModules = [
        // Phase 1-2: 기본 구조 + 엔진
        '_quantum_minimal_test.py', '_utils.py',
        '_brain_engine.py', '_memory_engine.py', '_hierarchy_engine.py', '_chunk_engine.py',
        // Phase 3-4: 지원 + Holon 관리
        '_auto_tagger.py', '_issue_tracker.py', '_health_check.py', '_cli.py',
        '_create_holon.py', '_spawn_meeting.py', '_auto_link.py', '_validate.py',
        // Phase 5: Quantum Core
        '_quantum_orchestrator.py', '_quantum_integration.py', '_quantum_persona_mapper.py', '_quantum_entanglement.py',
        // Phase 6: 확장 모듈
        '_vector_rag.py', '_meeting_parser.py', '_sibling_collaboration.py', '_mission_propagation.py', '_meta_research_engine.py'
    ];
    $results = [];
    foreach ($allModules as $module) {
        $path = HOLONS_PATH . '/' . $module;
        $results[$module] = $checker->checkPythonFile($path, $module);
    }
    echo json_encode($results);
    exit;
}

// 🔧 디버그 테스트 엔드포인트 (Python 없이 순수 JSON 테스트)
if ($action === 'test_json') {
    header('Content-Type: application/json');
    echo json_encode([
        'test' => 'success',
        'scenario_id' => 99,
        'scenario_name' => 'JSON 테스트',
        'context' => ['student_id' => 'TEST_001'],
        'intervention_level' => 'LOW',
        'message' => 'JSON 출력 정상 [pocdashboard.php:test_json]'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 시나리오 실행 엔드포인트
if ($action === 'run_scenario') {
    header('Content-Type: application/json');
    $scenarioId = intval($_GET['id'] ?? 1);

    // 디버그 모드: Python 없이 PHP로 직접 시나리오 데이터 생성
    $debugMode = isset($_GET['debug']);

    if ($debugMode) {
        // PHP 직접 반환 (Python 실행 건너뜀)
        $scenarios = [
            1 => [
                'scenario_id' => 1,
                'scenario_name' => '주간 목표 달성률 저하',
                'context' => [
                    'student_id' => 'STU_2025_001',
                    'weekly_completion_rate' => 55.0,
                    'quarterly_goal_id' => 'Q2025_MATH_TOP10'
                ],
                'agents_triggered' => ['Agent03'],
                'quantum_signal' => ['amplitude' => 0.8967, 'phase_deg' => 45.0],
                'interference_result' => ['total_amplitude' => 0.8967, 'efficiency' => '100%'],
                'intervention_level' => 'HIGH',
                'recommended_action' => [
                    'type' => 'analyze',
                    'action' => 'goal_plan_mismatch_diagnosis',
                    'message' => '분기 목표와 주간 목표의 불일치를 진단합니다.'
                ]
            ],
            2 => [
                'scenario_id' => 2,
                'scenario_name' => '신규 학생 온보딩',
                'context' => [
                    'student_id' => 'STU_2025_NEW',
                    'is_new_student' => true,
                    'enrolled_date' => '2025-12-07'
                ],
                'agents_triggered' => ['Agent01', 'Agent02'],
                'quantum_signals' => [
                    ['agent_id' => 1, 'amplitude' => 0.849, 'phase_deg' => 0],
                    ['agent_id' => 2, 'amplitude' => 0.671, 'phase_deg' => 0]
                ],
                'interference_result' => [
                    'type' => 'CONSTRUCTIVE',
                    'total_amplitude' => 1.520,
                    'efficiency' => '100%',
                    'explanation' => '같은 S0 시나리오로 보강 간섭 발생'
                ],
                'intervention_level' => 'MEDIUM',
                'recommended_actions' => [
                    ['agent' => 'Agent01', 'action' => 'collect_math_learning_style', 'message' => '수학 학습 스타일 수집'],
                    ['agent' => 'Agent02', 'action' => 'collect_basic_assessment', 'message' => '기초 평가 수집']
                ]
            ],
            3 => [
                'scenario_id' => 3,
                'scenario_name' => '목표 불일치 + 취약점 복합',
                'context' => [
                    'student_id' => 'STU_2025_003',
                    'weekly_completion_rate' => 45.0,
                    'vulnerability_score' => 0.85
                ],
                'agents_triggered' => ['Agent03', 'Agent04'],
                'quantum_signals' => [
                    ['agent_id' => 3, 'amplitude' => 0.897, 'phase_deg' => 45],
                    ['agent_id' => 4, 'amplitude' => 0.824, 'phase_deg' => 45]
                ],
                'interference_result' => [
                    'type' => 'CONSTRUCTIVE',
                    'total_amplitude' => 1.721,
                    'efficiency' => '100%',
                    'explanation' => '같은 S1 시나리오로 보강 간섭 발생'
                ],
                'intervention_level' => 'CRITICAL',
                'recommended_actions' => [
                    ['agent' => 'Agent03', 'action' => 'goal_mismatch_analysis', 'message' => '목표 불일치 분석'],
                    ['agent' => 'Agent04', 'action' => 'vulnerability_intervention', 'message' => '취약점 개입']
                ],
                'combined_insight' => 'Agent03과 Agent04의 보강 간섭으로 복합적 개입 필요. 학습 목표 재설정과 취약점 보강을 동시 진행.'
            ]
        ];

        echo json_encode($scenarios[$scenarioId] ?? ['error' => 'Invalid scenario ID'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // Python 시나리오 스크립트 실행
    $pythonCode = '';

    switch ($scenarioId) {
        case 1:
            // 시나리오 1: 주간 목표 달성률 저하 (Agent03 단독)
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 1: 주간 달성률 55% → R1a 트리거
context = {
    "student_id": "STU_2025_001",
    "weekly_completion_rate": 55.0,
    "quarterly_goal_id": "Q2025_MATH_TOP10",
    "weekly_goal_id": "W202512_MATH_REVIEW"
}

# R1a 룰 정의
rule = {
    "rule_id": "R1a_weekly_completion_rate_analysis",
    "priority": 95,
    "confidence": 0.92,
    "scenario": "S1"
}

# 조건 체크
triggered = (context["weekly_completion_rate"] < 70 and
             context["quarterly_goal_id"] and
             context["weekly_goal_id"])

# Quantum 신호 변환
amplitude = rule["confidence"] * math.sqrt(rule["priority"] / 100)
phase = math.pi / 4  # S1 = 45도

# 개입 수준 결정
if amplitude > 0.8:
    intervention = "HIGH"
elif amplitude > 0.6:
    intervention = "MEDIUM"
else:
    intervention = "LOW"

result = {
    "scenario_id": 1,
    "scenario_name": "주간 목표 달성률 저하",
    "context": context,
    "agents_triggered": ["Agent03"],
    "rules_triggered": [rule["rule_id"]],
    "condition_check": triggered,
    "quantum_signal": {
        "amplitude": round(amplitude, 4),
        "phase_deg": 45.0,
        "phase_rad": round(phase, 4)
    },
    "interference_result": {
        "total_amplitude": round(amplitude, 4),
        "efficiency": "100%"
    },
    "intervention_level": intervention,
    "recommended_action": {
        "type": "analyze",
        "action": "goal_plan_mismatch_diagnosis",
        "message": "분기 목표와 주간 목표의 불일치를 진단합니다."
    }
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        case 2:
            // 시나리오 2: 신규 학생 온보딩 (Agent01+02 동시)
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 2: 신규 학생 → Agent01+02 정보 수집
context = {
    "student_id": "STU_2025_NEW",
    "is_new_student": True,
    "grade": None,
    "learning_style": None,
    "enrolled_date": "2025-12-07"
}

# Agent01, Agent02 룰 정의 (S0 정보 수집)
rules = [
    {
        "agent_id": 1,
        "rule_id": "S0_R1_math_learning_style_collection",
        "priority": 99,
        "confidence": 0.97,
        "scenario": "S0",
        "desc": "수학 학습 스타일 수집"
    },
    {
        "agent_id": 2,
        "rule_id": "S0_R1_student_grade_collection",
        "priority": 99,
        "confidence": 0.98,
        "scenario": "S0",
        "desc": "학년 정보 수집"
    }
]

signals = []
for rule in rules:
    amp = rule["confidence"] * math.sqrt(rule["priority"] / 100)
    signals.append({
        "agent_id": rule["agent_id"],
        "rule_id": rule["rule_id"],
        "amplitude": round(amp, 4),
        "phase_deg": 0.0  # S0 = 0도
    })

# 간섭 계산 (같은 위상 = 보강 간섭)
total_amp = sum(s["amplitude"] for s in signals)
individual_sum = total_amp
efficiency = 100.0

result = {
    "scenario_id": 2,
    "scenario_name": "신규 학생 온보딩",
    "context": context,
    "agents_triggered": ["Agent01", "Agent02"],
    "rules_triggered": [r["rule_id"] for r in rules],
    "condition_check": True,
    "quantum_signals": signals,
    "interference_result": {
        "type": "CONSTRUCTIVE",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "explanation": "같은 시나리오(S0) → 보강 간섭 100%"
    },
    "intervention_level": "HIGH",
    "recommended_actions": [
        {"agent": "Agent01", "action": "collect_learning_style", "message": "학습 스타일 설문 시작"},
        {"agent": "Agent02", "action": "collect_grade_info", "message": "학년 정보 입력 요청"}
    ]
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        case 3:
            // 시나리오 3: 복합 문제 (Agent03+04 보강 간섭)
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 3: 목표 불일치 + 취약점 발견 → Agent03+04 동시 트리거
context = {
    "student_id": "STU_2025_003",
    "weekly_completion_rate": 45.0,
    "quarterly_goal_id": "Q2025_MATH_ADV",
    "weekly_goal_id": "W202512_BASIC_REVIEW",
    "detected_weakness": ["분수 연산", "소수점 계산"],
    "concept_stage": "기초"
}

# Agent03, Agent04 룰 정의 (S1 목표-계획 불일치)
rules = [
    {
        "agent_id": 3,
        "rule_id": "R1a_weekly_completion_rate_analysis",
        "priority": 95,
        "confidence": 0.92,
        "scenario": "S1",
        "desc": "주간 달성률 불일치 진단"
    },
    {
        "agent_id": 4,
        "rule_id": "CU_A1_weak_point_detection",
        "priority": 95,
        "confidence": 0.92,
        "scenario": "S1",
        "desc": "취약구간 탐지"
    }
]

signals = []
for rule in rules:
    amp = rule["confidence"] * math.sqrt(rule["priority"] / 100)
    phase = math.pi / 4  # S1 = 45도
    signals.append({
        "agent_id": rule["agent_id"],
        "rule_id": rule["rule_id"],
        "amplitude": round(amp, 4),
        "phase_deg": 45.0,
        "real": round(amp * math.cos(phase), 4),
        "imag": round(amp * math.sin(phase), 4)
    })

# 간섭 계산 (같은 위상 = 완벽한 보강 간섭)
real_sum = sum(s["real"] for s in signals)
imag_sum = sum(s["imag"] for s in signals)
total_amp = math.sqrt(real_sum**2 + imag_sum**2)
individual_sum = sum(s["amplitude"] for s in signals)
efficiency = (total_amp / individual_sum) * 100

result = {
    "scenario_id": 3,
    "scenario_name": "목표 불일치 + 취약점 복합",
    "context": context,
    "agents_triggered": ["Agent03", "Agent04"],
    "rules_triggered": [r["rule_id"] for r in rules],
    "condition_check": True,
    "quantum_signals": signals,
    "interference_result": {
        "type": "CONSTRUCTIVE",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "real_component": round(real_sum, 4),
        "imag_component": round(imag_sum, 4),
        "explanation": "같은 시나리오(S1) → 보강 간섭 100%"
    },
    "intervention_level": "CRITICAL",
    "priority_order": ["Agent03", "Agent04"],
    "recommended_actions": [
        {"agent": "Agent03", "action": "diagnose_goal_mismatch", "message": "분기-주간 목표 갭 분석"},
        {"agent": "Agent04", "action": "analyze_weakness", "message": "분수/소수점 취약점 개선 계획"}
    ],
    "combined_insight": "목표 난이도(고급)와 현재 수준(기초) 간 큰 갭 → 단계적 접근 필요"
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        case 4:
            // 시나리오 4: 3시간 수업 실시간 (Agent01→08→04→05→13→12→14)
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 4: 3시간 수업 실시간 오케스트레이션
# 7단계 Phase 전이: 수업 시작 → 문제풀이 → 감정변화 → 이탈감지 → 휴식 → 재집중 → 마무리

context = {
    "student_id": "STU_2025_CLASS",
    "session_type": "3hour_class",
    "class_duration_minutes": 180,
    "timeline": [
        {"t": 0, "event": "수업시작", "agent": "Agent01"},
        {"t": 5, "event": "침착도체크", "agent": "Agent08", "calmness": 95},
        {"t": 30, "event": "문제풀이시작", "agent": "Agent04", "accuracy": 65},
        {"t": 60, "event": "감정변화감지", "agent": "Agent05", "emotion": "neutral"},
        {"t": 90, "event": "집중도저하", "agent": "Agent13", "ninactive": 2},
        {"t": 100, "event": "휴식권장", "agent": "Agent12", "rest_type": "활동중심"},
        {"t": 110, "event": "재집중", "agent": "Agent08", "calmness": 88},
        {"t": 150, "event": "오답분석", "agent": "Agent11", "error_type": "계산실수"},
        {"t": 175, "event": "현재위치확인", "agent": "Agent14", "progress": "적절"}
    ]
}

# 각 시점별 Quantum 신호 생성 (Agent 룰 기반)
phase_map = {"S0": 0, "S1": 45, "S2": 90, "S3": 135, "S4": 180}
signals = []

# Agent01: 온보딩 (S0, 초기 맥락)
signals.append({
    "agent": "Agent01", "rule_id": "R01_onboarding",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.9, "priority": 70,
    "amplitude": round(0.9 * math.sqrt(70/100), 4),
    "message": "학생 프로필 로딩 완료"
})

# Agent08: 침착도 95 → 심화 추천 (S0)
signals.append({
    "agent": "Agent08", "rule_id": "R08_calmness_high",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.95, "priority": 85,
    "amplitude": round(0.95 * math.sqrt(85/100), 4),
    "message": "침착도 95+ → 심화 과제 배치"
})

# Agent04: 정답률 65% → 성장구간 (S1)
signals.append({
    "agent": "Agent04", "rule_id": "R04_accuracy_growth",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.85, "priority": 80,
    "amplitude": round(0.85 * math.sqrt(80/100), 4),
    "message": "정답률 40~70% → 최적 난이도 유지"
})

# Agent05: 감정 중립 (S1)
signals.append({
    "agent": "Agent05", "rule_id": "R05_emotion_neutral",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.7, "priority": 60,
    "amplitude": round(0.7 * math.sqrt(60/100), 4),
    "message": "감정 안정 → 현재 강도 유지"
})

# Agent13: ninactive=2 → Medium 위험 (S2)
signals.append({
    "agent": "Agent13", "rule_id": "R13_dropout_medium",
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.8, "priority": 75,
    "amplitude": round(0.8 * math.sqrt(75/100), 4),
    "message": "이탈 위험 Medium → 리포커스 필요"
})

# Agent12: 휴식 권장 (S2)
signals.append({
    "agent": "Agent12", "rule_id": "R12_rest_activity",
    "scenario": "S2", "phase_deg": 90,
    "confidence": 0.75, "priority": 65,
    "amplitude": round(0.75 * math.sqrt(65/100), 4),
    "message": "60~90분 경과 → 활동중심 휴식"
})

# Agent11: 오답 분석 (S1)
signals.append({
    "agent": "Agent11", "rule_id": "R11_error_analysis",
    "scenario": "S1", "phase_deg": 45,
    "confidence": 0.85, "priority": 70,
    "amplitude": round(0.85 * math.sqrt(70/100), 4),
    "message": "계산실수 패턴 → 체크리스트 권장"
})

# Agent14: 진행 상태 적절 (S0)
signals.append({
    "agent": "Agent14", "rule_id": "R14_progress_normal",
    "scenario": "S0", "phase_deg": 0,
    "confidence": 0.9, "priority": 75,
    "amplitude": round(0.9 * math.sqrt(75/100), 4),
    "message": "진행 적절 → 현재 페이스 유지"
})

# 간섭 계산 (위상 차이 고려)
real_sum = 0
imag_sum = 0
individual_sum = 0

for sig in signals:
    phase_rad = math.radians(sig["phase_deg"])
    real_sum += sig["amplitude"] * math.cos(phase_rad)
    imag_sum += sig["amplitude"] * math.sin(phase_rad)
    individual_sum += sig["amplitude"]

total_amp = math.sqrt(real_sum**2 + imag_sum**2)
efficiency = (total_amp / individual_sum * 100) if individual_sum > 0 else 0

# Phase 그룹별 간섭 분석
phase_groups = {}
for sig in signals:
    phase = sig["phase_deg"]
    if phase not in phase_groups:
        phase_groups[phase] = []
    phase_groups[phase].append(sig["agent"])

interference_detail = []
for phase, agents in phase_groups.items():
    if len(agents) > 1:
        interference_detail.append(f"Phase {phase}°: {'+'.join(agents)} 보강")

result = {
    "scenario_id": 4,
    "scenario_name": "3시간 수업 실시간",
    "context": context,
    "agents_triggered": ["Agent01", "Agent08", "Agent04", "Agent05", "Agent13", "Agent12", "Agent11", "Agent14"],
    "total_agents": 8,
    "quantum_signals": signals,
    "phase_distribution": {str(k): len([s for s in signals if s["phase_deg"]==k]) for k in [0,45,90]},
    "interference_result": {
        "type": "MULTI_PHASE_CONSTRUCTIVE",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "phase_groups": phase_groups,
        "interference_detail": interference_detail,
        "explanation": f"3개 Phase(0°,45°,90°) → 부분 보강 {efficiency:.0f}%"
    },
    "intervention_level": "MEDIUM",
    "recommended_actions": [
        {"time": "t=90분", "action": "3분 호흡 휴식 후 재시작"},
        {"time": "t=150분", "action": "오답 체크리스트 적용"},
        {"time": "t=175분", "action": "성취 요약 및 다음 목표 설정"}
    ],
    "session_summary": "수업 효율 72% | 침착도 변화: 95→88→재회복 | 이탈 방지 성공"
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        case 5:
            // 시나리오 5: 1주일 주간목표 (Agent02→03→09→07→05→06→11)
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 5: 1주일 주간목표 추적
# 시험 D-5부터 D-day까지 7일간 일별 모니터링

context = {
    "student_id": "STU_2025_WEEKLY",
    "exam_date": "2025-12-12",
    "exam_subject": "수학",
    "weekly_goal": "이차방정식 마스터",
    "daily_timeline": [
        {"day": "D-7", "agent": "Agent02", "event": "시험일정등록", "d_day": 7},
        {"day": "D-6", "agent": "Agent03", "event": "주간목표설정", "goal_quality": 75},
        {"day": "D-5", "agent": "Agent09", "event": "학습계획수립", "pomodoro_target": 8},
        {"day": "D-4", "agent": "Agent07", "event": "개입타겟설정", "focus_time": "오후3시"},
        {"day": "D-3", "agent": "Agent05", "event": "감정체크", "emotion": "불안"},
        {"day": "D-2", "agent": "Agent06", "event": "교사피드백", "feedback": "강점강화"},
        {"day": "D-1", "agent": "Agent11", "event": "오답총정리", "error_count": 15},
        {"day": "D-day", "agent": "Agent14", "event": "최종점검", "readiness": 85}
    ]
}

# 7일간 Quantum 신호 누적 (시간적 간섭)
phase_map = {"S0": 0, "S1": 45, "S2": 90, "S3": 135, "S4": 180}
signals = []
decay_rate = 0.3  # 시간적 감쇠율 λ

# D-7: Agent02 시험일정 (D≤7 → 개념정립 모드, S0)
signals.append({
    "agent": "Agent02", "rule_id": "R02_exam_d7",
    "scenario": "S0", "phase_deg": 0, "day": "D-7",
    "confidence": 0.85, "priority": 70,
    "amplitude": round(0.85 * math.sqrt(70/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 7), 4),
    "message": "D>10 → 개념 정립 후 유형 확장"
})

# D-6: Agent03 목표설정 (S1)
signals.append({
    "agent": "Agent03", "rule_id": "R03_goal_setup",
    "scenario": "S1", "phase_deg": 45, "day": "D-6",
    "confidence": 0.75, "priority": 80,
    "amplitude": round(0.75 * math.sqrt(80/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 6), 4),
    "message": "목표 품질 75% → 세분화 권장"
})

# D-5: Agent09 학습관리 (S1)
signals.append({
    "agent": "Agent09", "rule_id": "R09_learning_plan",
    "scenario": "S1", "phase_deg": 45, "day": "D-5",
    "confidence": 0.8, "priority": 85,
    "amplitude": round(0.8 * math.sqrt(85/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 5), 4),
    "message": "포모도로 8세션 목표 설정"
})

# D-4: Agent07 개입타겟 (S2)
signals.append({
    "agent": "Agent07", "rule_id": "R07_interaction_target",
    "scenario": "S2", "phase_deg": 90, "day": "D-4",
    "confidence": 0.85, "priority": 75,
    "amplitude": round(0.85 * math.sqrt(75/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 4), 4),
    "message": "오후3시 집중시간 → 어려운 과제 배치"
})

# D-3: Agent05 감정불안 (S3) - 시험 불안
signals.append({
    "agent": "Agent05", "rule_id": "R05_emotion_anxiety",
    "scenario": "S3", "phase_deg": 135, "day": "D-3",
    "confidence": 0.9, "priority": 90,
    "amplitude": round(0.9 * math.sqrt(90/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 3), 4),
    "message": "불안 감지 → 짧은 성공 과제로 자신감 회복"
})

# D-2: Agent06 교사피드백 (S1)
signals.append({
    "agent": "Agent06", "rule_id": "R06_teacher_feedback",
    "scenario": "S1", "phase_deg": 45, "day": "D-2",
    "confidence": 0.8, "priority": 80,
    "amplitude": round(0.8 * math.sqrt(80/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 2), 4),
    "message": "강점 과제 첫 세션 배치 → 동기 상승"
})

# D-1: Agent11 오답정리 (S2)
signals.append({
    "agent": "Agent11", "rule_id": "R11_error_review",
    "scenario": "S2", "phase_deg": 90, "day": "D-1",
    "confidence": 0.95, "priority": 95,
    "amplitude": round(0.95 * math.sqrt(95/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 1), 4),
    "message": "오답 15개 집중 복습 → 서술평가 준비"
})

# D-day: Agent14 최종점검 (S0)
signals.append({
    "agent": "Agent14", "rule_id": "R14_final_check",
    "scenario": "S0", "phase_deg": 0, "day": "D-day",
    "confidence": 0.85, "priority": 90,
    "amplitude": round(0.85 * math.sqrt(90/100), 4),
    "temporal_weight": 1.0,
    "message": "준비도 85% → 자신감 유지 메시지"
})

# 시간 가중 간섭 계산 (최근 신호가 더 강함)
real_sum = 0
imag_sum = 0
individual_sum = 0

for sig in signals:
    phase_rad = math.radians(sig["phase_deg"])
    weighted_amp = sig["amplitude"] * sig["temporal_weight"]
    real_sum += weighted_amp * math.cos(phase_rad)
    imag_sum += weighted_amp * math.sin(phase_rad)
    individual_sum += weighted_amp

total_amp = math.sqrt(real_sum**2 + imag_sum**2)
efficiency = (total_amp / individual_sum * 100) if individual_sum > 0 else 0

# D-3 불안 vs 나머지 긍정 신호 간섭 분석
anxiety_signal = [s for s in signals if s["day"] == "D-3"][0]
positive_signals = [s for s in signals if s["phase_deg"] < 135]

result = {
    "scenario_id": 5,
    "scenario_name": "1주일 주간목표",
    "context": context,
    "agents_triggered": ["Agent02", "Agent03", "Agent09", "Agent07", "Agent05", "Agent06", "Agent11", "Agent14"],
    "total_agents": 8,
    "quantum_signals": signals,
    "temporal_analysis": {
        "decay_rate": decay_rate,
        "recent_weight": "D-day=100%, D-1=74%, D-3=41%",
        "critical_day": "D-3 (불안 감지)"
    },
    "interference_result": {
        "type": "TEMPORAL_WEIGHTED",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "anxiety_impact": "S3(135°)가 보강 효율 감소시킴",
        "explanation": f"시간 가중 간섭 → 최근 신호 우선 {efficiency:.0f}%"
    },
    "intervention_level": "HIGH",
    "weekly_trend": {
        "early_week": "계획 수립 단계 (S0-S1 보강)",
        "mid_week": "불안 발생 (S3 상쇄 효과)",
        "late_week": "피드백+오답으로 회복"
    },
    "recommended_actions": [
        {"day": "D-3", "priority": "CRITICAL", "action": "시험 불안 해소 → 쉬운 문제 3개로 자신감 회복"},
        {"day": "D-1", "priority": "HIGH", "action": "오답 15개 중 핵심 5개 집중"},
        {"day": "D-day", "priority": "MEDIUM", "action": "긍정 메시지 + 시간 관리 리마인드"}
    ]
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        case 6:
            // 시나리오 6: 2개월 분기목표 (Agent01→02→03→09→12→13→14)
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 6: 2개월 분기목표 (장기 추적)
# 시간적 연쇄 효과: 과거 신호가 현재에 영향

context = {
    "student_id": "STU_2025_QUARTER",
    "quarter": "2025-Q4",
    "quarter_goal": "수학 등급 3→2 상승",
    "duration_weeks": 8,
    "monthly_events": [
        {"week": 1, "agent": "Agent01", "event": "분기시작", "baseline": "3등급"},
        {"week": 2, "agent": "Agent02", "event": "중간고사일정", "d_day": 35},
        {"week": 3, "agent": "Agent03", "event": "월간목표검토", "achievement": 65},
        {"week": 4, "agent": "Agent09", "event": "학습패턴분석", "pomodoro_avg": 5},
        {"week": 5, "agent": "Agent12", "event": "휴식패턴경고", "rest_type": "비계획형"},
        {"week": 6, "agent": "Agent13", "event": "슬럼프감지", "ninactive": 6, "risk": "HIGH"},
        {"week": 7, "agent": "Agent05", "event": "감정추세", "emotion_trend": "하락"},
        {"week": 8, "agent": "Agent14", "event": "분기종료", "final_grade": "2.5등급"}
    ]
}

# 8주간 Quantum 신호 (시간적 연쇄 효과)
phase_map = {"S0": 0, "S1": 45, "S2": 90, "S3": 135, "S4": 180}
signals = []
decay_rate = 0.3  # λ = 0.3

# Week 1: Agent01 분기 시작 (S0)
signals.append({
    "agent": "Agent01", "rule_id": "R01_quarter_start",
    "scenario": "S0", "phase_deg": 0, "week": 1,
    "confidence": 0.9, "priority": 75,
    "amplitude": round(0.9 * math.sqrt(75/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 7), 4),
    "message": "분기 시작 → 기준선 3등급 설정"
})

# Week 2: Agent02 시험일정 (S0)
signals.append({
    "agent": "Agent02", "rule_id": "R02_midterm_schedule",
    "scenario": "S0", "phase_deg": 0, "week": 2,
    "confidence": 0.85, "priority": 80,
    "amplitude": round(0.85 * math.sqrt(80/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 6), 4),
    "message": "D-35 → 장기 계획 수립"
})

# Week 3: Agent03 목표달성 65% (S1)
signals.append({
    "agent": "Agent03", "rule_id": "R03_goal_check",
    "scenario": "S1", "phase_deg": 45, "week": 3,
    "confidence": 0.75, "priority": 85,
    "amplitude": round(0.75 * math.sqrt(85/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 5), 4),
    "message": "달성률 65% < 70% → 목표 세분화 필요"
})

# Week 4: Agent09 포모도로 평균 5회 (S1)
signals.append({
    "agent": "Agent09", "rule_id": "R09_pomodoro_low",
    "scenario": "S1", "phase_deg": 45, "week": 4,
    "confidence": 0.8, "priority": 75,
    "amplitude": round(0.8 * math.sqrt(75/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 4), 4),
    "message": "포모도로 <8 → 세션 길이 조정 권장"
})

# Week 5: Agent12 휴식 비계획형 경고 (S2)
signals.append({
    "agent": "Agent12", "rule_id": "R12_rest_warning",
    "scenario": "S2", "phase_deg": 90, "week": 5,
    "confidence": 0.85, "priority": 80,
    "amplitude": round(0.85 * math.sqrt(80/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 3), 4),
    "message": "휴식 비계획형 → 슬럼프 전조"
})

# Week 6: Agent13 슬럼프 HIGH (S3) - 중요!
signals.append({
    "agent": "Agent13", "rule_id": "R13_dropout_high",
    "scenario": "S3", "phase_deg": 135, "week": 6,
    "confidence": 0.95, "priority": 95,
    "amplitude": round(0.95 * math.sqrt(95/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 2), 4),
    "message": "ninactive≥4 → HIGH 위험 → 에스컬레이션"
})

# Week 7: Agent05 감정 하락 추세 (S3)
signals.append({
    "agent": "Agent05", "rule_id": "R05_emotion_decline",
    "scenario": "S3", "phase_deg": 135, "week": 7,
    "confidence": 0.9, "priority": 90,
    "amplitude": round(0.9 * math.sqrt(90/100), 4),
    "temporal_weight": round(math.exp(-decay_rate * 1), 4),
    "message": "감정 하락 지속 → 긴급 동기 부여 필요"
})

# Week 8: Agent14 최종 평가 (S1) - 부분 회복
signals.append({
    "agent": "Agent14", "rule_id": "R14_quarter_end",
    "scenario": "S1", "phase_deg": 45, "week": 8,
    "confidence": 0.8, "priority": 85,
    "amplitude": round(0.8 * math.sqrt(85/100), 4),
    "temporal_weight": 1.0,
    "message": "2.5등급 → 목표 부분 달성 (3→2.5)"
})

# 시간적 연쇄 간섭 계산
# A_total = Σ(A_i × e^(-λ(t-t_i)))
real_sum = 0
imag_sum = 0
individual_sum = 0

for sig in signals:
    phase_rad = math.radians(sig["phase_deg"])
    weighted_amp = sig["amplitude"] * sig["temporal_weight"]
    real_sum += weighted_amp * math.cos(phase_rad)
    imag_sum += weighted_amp * math.sin(phase_rad)
    individual_sum += weighted_amp

total_amp = math.sqrt(real_sum**2 + imag_sum**2)
efficiency = (total_amp / individual_sum * 100) if individual_sum > 0 else 0

# 슬럼프 기간 분석 (Week 5-7)
slump_signals = [s for s in signals if s["week"] in [5,6,7]]
slump_amp = sum(s["amplitude"] * s["temporal_weight"] for s in slump_signals)

# 복구 신호 분석 (Week 8)
recovery_signal = [s for s in signals if s["week"] == 8][0]

result = {
    "scenario_id": 6,
    "scenario_name": "2개월 분기목표",
    "context": context,
    "agents_triggered": ["Agent01", "Agent02", "Agent03", "Agent09", "Agent12", "Agent13", "Agent05", "Agent14"],
    "total_agents": 8,
    "quantum_signals": signals,
    "temporal_cascade": {
        "formula": "A_total = Σ(A_i × e^(-λ(t-t_i)))",
        "decay_rate": decay_rate,
        "cascade_effect": "Week5 휴식경고 → Week6 슬럼프 → Week7 감정하락",
        "early_warning_missed": "Week5에서 개입했다면 Week6-7 방지 가능"
    },
    "slump_analysis": {
        "slump_period": "Week 5-7",
        "slump_amplitude": round(slump_amp, 4),
        "slump_phase": "S2→S3→S3 (상쇄 방향)",
        "root_cause": "휴식 루틴 미정착 → 컨디션 저하 → 이탈"
    },
    "interference_result": {
        "type": "TEMPORAL_CASCADE",
        "total_amplitude": round(total_amp, 4),
        "individual_sum": round(individual_sum, 4),
        "efficiency": f"{efficiency:.1f}%",
        "destructive_interference": "S3(135°) 신호가 S0-S1 보강 효과 상쇄",
        "explanation": f"슬럼프 기간 상쇄 간섭 → 효율 {efficiency:.0f}%로 감소"
    },
    "intervention_level": "CRITICAL",
    "quarter_outcome": {
        "goal": "3등급 → 2등급",
        "actual": "3등급 → 2.5등급",
        "achievement": "50% (부분 달성)",
        "lesson_learned": "Week5 휴식 경고 시 즉시 개입 필요"
    },
    "counterfactual_analysis": {
        "title": "만약 Week5에서 개입했다면?",
        "scenario": "Week5 휴식루틴 개선 → Week6 슬럼프 방지",
        "expected_outcome": "2.0등급 달성 가능 (효율 +35%)",
        "quantum_insight": "시간적 연쇄 조기 차단의 중요성"
    },
    "recommended_actions": [
        {"week": 5, "priority": "CRITICAL", "action": "휴식 루틴 즉시 개입 (자신만의 규칙 수립)"},
        {"week": 6, "priority": "HIGH", "action": "보호자/담임 알림 + 쉬운 승리 태스크"},
        {"week": 8, "priority": "MEDIUM", "action": "성취 인정 + 다음 분기 목표 하향 조정"}
    ]
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        case 7:
            // 시나리오 7: 가중치 붕괴 - 침착도 정체 후 반등/하락 예측
            $pythonCode = <<<'PYTHON'
import math
import json

# 시나리오 7: 가중치 붕괴 (Weight Collapse) - 미래 연쇄작용 예측
# 침착도 데이터 정체 감지 → 확률적 미래 시나리오 계산 → 파동함수 붕괴

context = {
    "student_id": "STU_2025_007",
    "metric": "composure_score",
    "metric_name": "침착도",
    "observation_window": "2주",
    "current_value": 72.0,
    "historical_values": [75, 74, 73, 72, 72, 72, 72, 71, 72, 72],  # 10일 추적
    "stagnation_detected": True,
    "stagnation_duration_days": 6,
    "baseline_value": 75.0,
    "threshold_critical": 65.0
}

# === 1. 정체 패턴 분석 (Stagnation Detection) ===
values = context["historical_values"]
variance = sum((v - sum(values)/len(values))**2 for v in values[-6:]) / 6
trend = (values[-1] - values[-6]) / 6  # 6일 기울기
stagnation_score = 1.0 / (1.0 + variance)  # 분산이 작을수록 높은 정체도

# === 2. 미래 시나리오 정의 (Quantum Superposition) ===
# 정체 상태는 여러 미래 시나리오의 중첩 상태
future_scenarios = {
    "rebound": {
        "name": "반등 시나리오",
        "description": "정체 후 자연스러운 회복",
        "phase_deg": 0,
        "base_probability": 0.35,
        "predicted_value": 78,
        "agent_intervention": "Agent08_동기부여"
    },
    "decline": {
        "name": "하락 시나리오", 
        "description": "정체가 하락으로 전환",
        "phase_deg": 135,
        "base_probability": 0.40,
        "predicted_value": 62,
        "agent_intervention": "Agent05_감정지원"
    },
    "plateau": {
        "name": "정체 지속 시나리오",
        "description": "현재 수준 유지",
        "phase_deg": 90,
        "base_probability": 0.25,
        "predicted_value": 72,
        "agent_intervention": "Agent09_메타인지촉진"
    }
}

# === 3. 조건부 확률 조정 (Bayesian Update) ===
# 맥락 정보에 따라 확률 조정
def adjust_probability(base_prob, factors):
    adjusted = base_prob
    for factor, weight in factors.items():
        adjusted *= weight
    return adjusted

context_factors = {
    "stagnation_long": 1.2 if context["stagnation_duration_days"] >= 5 else 1.0,
    "near_critical": 1.3 if context["current_value"] < context["baseline_value"] - 5 else 1.0,
    "recent_decline": 1.1 if trend < -0.5 else 1.0
}

# 하락 시나리오 가중치 증가 (정체 + 하락 추세)
decline_boost = context_factors["stagnation_long"] * context_factors["near_critical"]
rebound_penalty = 1.0 / decline_boost  # 반등 확률 감소

adjusted_probs = {
    "rebound": adjust_probability(future_scenarios["rebound"]["base_probability"], {"rebound": rebound_penalty}),
    "decline": adjust_probability(future_scenarios["decline"]["base_probability"], context_factors),
    "plateau": future_scenarios["plateau"]["base_probability"]
}

# 정규화
total_prob = sum(adjusted_probs.values())
normalized_probs = {k: v/total_prob for k, v in adjusted_probs.items()}

# === 4. Quantum Amplitude 계산 ===
# amplitude = sqrt(probability), phase = scenario phase
quantum_signals = []
for scenario_key, scenario in future_scenarios.items():
    prob = normalized_probs[scenario_key]
    amplitude = math.sqrt(prob)
    phase_rad = scenario["phase_deg"] * math.pi / 180
    
    quantum_signals.append({
        "scenario": scenario_key,
        "scenario_name": scenario["name"],
        "amplitude": round(amplitude, 4),
        "phase_deg": scenario["phase_deg"],
        "probability": round(prob * 100, 1),
        "predicted_value": scenario["predicted_value"],
        "agent": scenario["agent_intervention"]
    })

# === 5. 간섭 패턴 계산 (Multi-scenario Interference) ===
# 복소수 표현: Ψ = A * e^(iφ)
real_sum = 0
imag_sum = 0
for sig in quantum_signals:
    phase_rad = sig["phase_deg"] * math.pi / 180
    real_sum += sig["amplitude"] * math.cos(phase_rad)
    imag_sum += sig["amplitude"] * math.sin(phase_rad)

total_amplitude = math.sqrt(real_sum**2 + imag_sum**2)
resultant_phase_deg = math.degrees(math.atan2(imag_sum, real_sum)) % 360

# === 6. 가중치 붕괴 결정 (Wave Function Collapse) ===
# 가장 높은 확률의 시나리오로 "관측"
max_prob_scenario = max(normalized_probs, key=normalized_probs.get)
collapse_scenario = future_scenarios[max_prob_scenario]
collapse_probability = normalized_probs[max_prob_scenario]

# 붕괴 전후 엔트로피 계산
import math as m
entropy_before = -sum(p * m.log2(p) if p > 0 else 0 for p in normalized_probs.values())
entropy_after = 0  # 완전 확정 = 엔트로피 0

# === 7. 개입 전략 결정 ===
# 붕괴 시나리오에 따른 차별화된 개입
if max_prob_scenario == "decline":
    intervention_level = "CRITICAL"
    intervention_urgency = "즉시 개입 필요"
    recommended_actions = [
        {"priority": "P0", "agent": "Agent05", "action": "감정 상태 점검 대화", "timing": "24시간 내"},
        {"priority": "P1", "agent": "Agent08", "action": "작은 성공 경험 제공", "timing": "48시간 내"},
        {"priority": "P2", "agent": "Agent04", "action": "난이도 하향 조정", "timing": "이번 주 내"}
    ]
elif max_prob_scenario == "plateau":
    intervention_level = "HIGH"
    intervention_urgency = "모니터링 강화"
    recommended_actions = [
        {"priority": "P1", "agent": "Agent09", "action": "메타인지 촉진 질문", "timing": "48시간 내"},
        {"priority": "P2", "agent": "Agent03", "action": "학습 패턴 분석", "timing": "이번 주 내"}
    ]
else:  # rebound
    intervention_level = "MEDIUM"
    intervention_urgency = "자연 회복 관찰"
    recommended_actions = [
        {"priority": "P2", "agent": "Agent08", "action": "긍정적 피드백 제공", "timing": "이번 주 내"},
        {"priority": "P3", "agent": "Agent11", "action": "복습 스케줄 최적화", "timing": "다음 주"}
    ]

result = {
    "scenario_id": 7,
    "scenario_name": "가중치 붕괴 - 침착도 정체 예측",
    "context": context,
    
    # 정체 분석
    "stagnation_analysis": {
        "stagnation_score": round(stagnation_score, 3),
        "variance": round(variance, 3),
        "trend": round(trend, 3),
        "duration_days": context["stagnation_duration_days"],
        "pattern": "LOW_VARIANCE_PLATEAU" if variance < 1.0 else "FLUCTUATING"
    },
    
    # Quantum 상태 (중첩)
    "quantum_superposition": {
        "description": "미래 시나리오들의 확률적 중첩 상태",
        "total_scenarios": len(future_scenarios),
        "entropy_bits": round(entropy_before, 3)
    },
    "quantum_signals": quantum_signals,
    
    # 간섭 결과
    "interference_result": {
        "type": "MULTI_SCENARIO_SUPERPOSITION",
        "total_amplitude": round(total_amplitude, 4),
        "resultant_phase_deg": round(resultant_phase_deg, 1),
        "dominant_scenario": max_prob_scenario,
        "dominant_probability": f"{collapse_probability*100:.1f}%"
    },
    
    # 붕괴 (관측)
    "wave_function_collapse": {
        "collapsed_to": max_prob_scenario,
        "collapsed_scenario_name": collapse_scenario["name"],
        "collapse_probability": round(collapse_probability, 3),
        "predicted_value": collapse_scenario["predicted_value"],
        "entropy_reduction": round(entropy_before - entropy_after, 3),
        "collapse_reasoning": f"정체 {context['stagnation_duration_days']}일 + 하락 추세 → {collapse_scenario['name']} 가능성 {collapse_probability*100:.0f}%"
    },
    
    # 개입 결정
    "intervention_level": intervention_level,
    "intervention_urgency": intervention_urgency,
    "recommended_actions": recommended_actions,
    
    # 시간적 연쇄작용 분석
    "temporal_chain_effect": {
        "current_state": f"침착도 {context['current_value']}% (정체)",
        "if_no_intervention": f"7일 후 예측: {collapse_scenario['predicted_value']}%",
        "if_intervention": f"7일 후 예측: {context['baseline_value']}% (기준선 복귀)",
        "intervention_value": round(context['baseline_value'] - collapse_scenario['predicted_value'], 1)
    },
    
    # 메타 인사이트
    "quantum_insight": f"🔮 파동함수 붕괴 분석: {len(future_scenarios)}개 미래 시나리오 중 '{collapse_scenario['name']}'이 {collapse_probability*100:.0f}% 확률로 실현 예상. 정체 패턴(분산={variance:.2f})이 {context['stagnation_duration_days']}일 지속되어 자연 반등 가능성 감소. 선제적 개입으로 양자 상태를 '반등' 방향으로 유도 필요."
}

print(json.dumps(result, ensure_ascii=False, indent=2))
PYTHON;
            break;

        default:
            echo json_encode(['error' => 'Invalid scenario ID [pocdashboard.php:run_scenario]']);
            exit;
    }

    // Python 코드 실행 - UTF-8 인코딩 설정 추가
    $encodingHeader = "# -*- coding: utf-8 -*-\nimport sys\nimport io\nsys.stdout = io.TextIOWrapper(sys.stdout.buffer, encoding='utf-8')\n\n";
    $pythonCode = $encodingHeader . $pythonCode;

    $tempFile = tempnam(sys_get_temp_dir(), 'scenario_');
    file_put_contents($tempFile, $pythonCode);

    $output = [];
    $returnCode = 0;
    exec("PYTHONIOENCODING=utf-8 python3 " . escapeshellarg($tempFile) . " 2>&1", $output, $returnCode);

    unlink($tempFile);

    if ($returnCode === 0) {
        echo implode("\n", $output);
    } else {
        echo json_encode([
            'error' => 'Scenario execution failed [pocdashboard.php:run_scenario]',
            'output' => implode("\n", $output),
            'return_code' => $returnCode
        ]);
    }
    exit;
}

// Phase 정의
$phases = [
    [
        'id' => 1,
        'name' => 'Phase 1: 기본 구조',
        'description' => 'Quantum 변환 핵심 모듈',
        'modules' => [
            ['file' => '_quantum_minimal_test.py', 'desc' => 'Quantum 최소 동작 케이스'],
            ['file' => '_utils.py', 'desc' => '유틸리티 함수'],
        ]
    ],
    [
        'id' => 2,
        'name' => 'Phase 2: 엔진 모듈',
        'description' => 'Holarchy 핵심 엔진들',
        'modules' => [
            ['file' => '_brain_engine.py', 'desc' => 'Brain Engine (추론)'],
            ['file' => '_memory_engine.py', 'desc' => 'Memory Engine (기억)'],
            ['file' => '_hierarchy_engine.py', 'desc' => 'Hierarchy Engine (계층)'],
            ['file' => '_chunk_engine.py', 'desc' => 'Chunk Engine (청킹)'],
        ]
    ],
    [
        'id' => 3,
        'name' => 'Phase 3: 지원 모듈',
        'description' => '부가 기능 모듈들',
        'modules' => [
            ['file' => '_auto_tagger.py', 'desc' => '자동 태깅'],
            ['file' => '_issue_tracker.py', 'desc' => '이슈 추적'],
            ['file' => '_health_check.py', 'desc' => '상태 점검'],
            ['file' => '_cli.py', 'desc' => 'CLI 인터페이스'],
        ]
    ],
    [
        'id' => 4,
        'name' => 'Phase 4: Holon 관리',
        'description' => 'Holon 생성/관리 모듈',
        'modules' => [
            ['file' => '_create_holon.py', 'desc' => 'Holon 생성'],
            ['file' => '_spawn_meeting.py', 'desc' => 'Meeting 스폰'],
            ['file' => '_auto_link.py', 'desc' => '자동 링크'],
            ['file' => '_validate.py', 'desc' => '유효성 검증'],
        ]
    ],
    [
        'id' => 5,
        'name' => 'Phase 5: Quantum Core ⚛️',
        'description' => 'Quantum Orchestration 핵심 엔진',
        'modules' => [
            ['file' => '_quantum_orchestrator.py', 'desc' => 'Quantum 오케스트레이터'],
            ['file' => '_quantum_integration.py', 'desc' => 'Quantum 통합 엔진'],
            ['file' => '_quantum_persona_mapper.py', 'desc' => '페르소나 매퍼'],
            ['file' => '_quantum_entanglement.py', 'desc' => '에이전트 얽힘'],
        ]
    ],
    [
        'id' => 6,
        'name' => 'Phase 6: 확장 모듈',
        'description' => '고급 기능 및 협업 모듈',
        'modules' => [
            ['file' => '_vector_rag.py', 'desc' => '벡터 RAG 검색'],
            ['file' => '_meeting_parser.py', 'desc' => '미팅 파서'],
            ['file' => '_sibling_collaboration.py', 'desc' => '형제 협업'],
            ['file' => '_mission_propagation.py', 'desc' => '미션 전파'],
            ['file' => '_meta_research_engine.py', 'desc' => '메타 리서치'],
        ]
    ]
];

// 에이전트 체크
$agentResults = [];
for ($i = 1; $i <= 4; $i++) {
    $agentResults[$i] = $checker->checkAgentFolder($i);
}

// ✅ FIX v2: 모든 출력 버퍼 완전 정리 (Moodle 중첩 버퍼 대응)
while (ob_get_level() > 0) {
    ob_end_clean();
}

?>
<!DOCTYPE html>
<!-- 📌 FILE VERSION: v1.2 | Modified: <?php echo date('Y-m-d H:i:s', filemtime(__FILE__)); ?> | Hash: <?php echo substr(md5_file(__FILE__), 0, 8); ?> -->
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔬 Quantum POC v1.2 - AUTO CHECK</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #e0e0e0;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            padding: 30px 0;
            border-bottom: 1px solid #333;
            margin-bottom: 30px;
        }

        h1 {
            font-size: 2.5em;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #888;
            font-size: 1.1em;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .card-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #00d9ff;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 600;
        }

        .badge-success { background: #00c853; color: #000; }
        .badge-warning { background: #ffc107; color: #000; }
        .badge-error { background: #ff5252; color: #fff; }
        .badge-pending { background: #666; color: #fff; }

        .module-list {
            list-style: none;
        }

        .module-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: rgba(255,255,255,0.03);
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }

        .module-item:hover {
            background: rgba(255,255,255,0.08);
        }

        .module-name {
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.9em;
        }

        .module-desc {
            color: #888;
            font-size: 0.85em;
            margin-top: 4px;
        }

        .status-icon {
            font-size: 1.2em;
        }

        .status-success { color: #00c853; }
        .status-warning { color: #ffc107; }
        .status-error { color: #ff5252; }
        .status-pending { color: #666; }

        .quantum-panel {
            background: linear-gradient(135deg, rgba(0,217,255,0.1), rgba(0,255,136,0.1));
            border: 2px solid rgba(0,217,255,0.3);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary {
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            color: #000;
        }

        .btn-primary:hover {
            transform: scale(1.02);
            box-shadow: 0 5px 20px rgba(0,217,255,0.3);
        }

        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .output-box {
            background: #0d1117;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            font-family: 'Monaco', 'Consolas', monospace;
            font-size: 0.85em;
            max-height: 400px;
            overflow-y: auto;
            white-space: pre-wrap;
            display: none;
        }

        .output-box.show {
            display: block;
        }

        .progress-bar {
            height: 8px;
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
            overflow: hidden;
            margin: 15px 0;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            transition: width 0.5s ease;
        }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            flex: 1;
            background: rgba(255,255,255,0.05);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: 700;
            background: linear-gradient(90deg, #00d9ff, #00ff88);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: #888;
            margin-top: 5px;
        }

        .agent-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .agent-card {
            background: rgba(255,255,255,0.05);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .agent-num {
            font-size: 2em;
            font-weight: 700;
            color: #00d9ff;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: #00d9ff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        footer {
            text-align: center;
            padding: 30px;
            color: #666;
            border-top: 1px solid #333;
            margin-top: 30px;
        }

        /* 🎨 파동 간섭 시각화 스타일 */
        .visualization-container {
            background: #0a0e14;
            border-radius: 8px;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #1e293b;
        }
        .viz-title {
            font-size: 11px;
            color: #8b5cf6;
            margin-bottom: 8px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .wave-canvas {
            border-radius: 6px;
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 100%);
        }
        .viz-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
            font-size: 9px;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            background: #1e293b;
            border-radius: 4px;
        }
        .legend-color {
            width: 10px;
            height: 10px;
            border-radius: 2px;
        }
        .prob-bar-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 4px 0;
        }
        .prob-bar-label {
            font-size: 9px;
            width: 50px;
            color: #9ca3af;
        }
        .prob-bar {
            flex: 1;
            height: 14px;
            background: #1e293b;
            border-radius: 3px;
            overflow: hidden;
            position: relative;
        }
        .prob-bar-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 0.5s ease;
        }
        .prob-bar-value {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 8px;
            color: #fff;
            text-shadow: 0 0 3px #000;
        }
        .phase-indicator {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 9px;
            font-weight: bold;
        }
        .phase-0 { background: #166534; color: #86efac; }
        .phase-45 { background: #854d0e; color: #fde047; }
        .phase-90 { background: #1e40af; color: #93c5fd; }
        .phase-135 { background: #7c2d12; color: #fdba74; }
        .phase-180 { background: #701a75; color: #f0abfc; }
    </style>
</head>
<body>
    <!-- v1.2 자동 검증 상태 배너 -->
    <div id="auto-check-banner" style="background: linear-gradient(90deg, #ff6b00, #ff9500); color: white; padding: 12px 20px; text-align: center; font-weight: bold; font-size: 14px; position: fixed; top: 0; left: 0; right: 0; z-index: 9999; box-shadow: 0 2px 10px rgba(0,0,0,0.3);">
        🔄 v1.2 - 모듈 자동 검증 중... 잠시만 기다려 주세요
    </div>
    <div style="height: 50px;"></div><!-- 배너 공간 확보 -->

    <div class="container">
        <header>
            <h1>⚛️ Quantum Orchestration POC v1.2</h1>
            <p class="subtitle">구조적 완결성 추적 대시보드 | Agent01~04 기반</p>
        </header>

        <!-- 전체 통계 -->
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-value" id="total-modules">0</div>
                <div class="stat-label">총 모듈</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" id="ready-modules">0</div>
                <div class="stat-label">준비 완료</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" id="agents-ready">0/4</div>
                <div class="stat-label">에이전트</div>
            </div>
            <div class="stat-box">
                <div class="stat-value" id="progress-percent">0%</div>
                <div class="stat-label">진행률</div>
            </div>
        </div>

        <!-- Quantum 테스트 패널 -->
        <div class="card quantum-panel" style="margin-bottom: 30px;">
            <div class="card-header">
                <span class="card-title">⚡ Quantum Minimal Test</span>
                <span class="badge badge-pending" id="quantum-status">대기</span>
            </div>
            <p style="color: #aaa; margin-bottom: 15px;">
                Agent01~04의 룰을 Quantum 신호로 변환하고 간섭 패턴을 계산합니다.
            </p>
            <button class="btn btn-primary" onclick="runQuantumTest()">
                🚀 Quantum 테스트 실행
            </button>
            <div class="output-box" id="quantum-output"></div>
        </div>

        <!-- 🎬 전체 동작 시나리오 예시 -->
        <div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, #1e3a5f 0%, #2d1b4e 100%); border: 2px solid #6366f1;">
            <div class="card-header">
                <span class="card-title">🎬 전체 동작 시나리오 예시 (E2E Demo)</span>
                <span class="badge badge-success">6개 시나리오</span>
            </div>
            <p style="color: #aaa; margin-bottom: 20px;">
                실제 학생 컨텍스트 → 룰 트리거 → Quantum 신호 변환 → 간섭 계산 → 액션 추천까지 전체 흐름을 확인합니다.
            </p>

            <div class="scenario-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
                <!-- 시나리오 1 -->
                <div class="scenario-card" data-scenario="1" onclick="runScenario(1)" style="background: #1a1a2e; border: 1px solid #30363d; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">📉</span>
                        <span style="font-weight: bold; color: #f59e0b;">시나리오 1</span>
                    </div>
                    <div style="font-size: 14px; color: #e0e0e0; font-weight: 600; margin-bottom: 8px;">
                        주간 목표 달성률 저하
                    </div>
                    <div style="font-size: 12px; color: #888;">
                        • Agent03 단독 트리거<br>
                        • 달성률 55% &lt; 70%<br>
                        • S1 시나리오 (45°)
                    </div>
                    <div class="scenario-badge" style="margin-top: 10px; font-size: 11px; padding: 4px 8px; border-radius: 4px; display: inline-block; background: #f59e0b33; color: #f59e0b;">
                        🔴 HIGH 개입
                    </div>
                </div>

                <!-- 시나리오 2 -->
                <div class="scenario-card" data-scenario="2" onclick="runScenario(2)" style="background: #1a1a2e; border: 1px solid #30363d; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">👋</span>
                        <span style="font-weight: bold; color: #22c55e;">시나리오 2</span>
                    </div>
                    <div style="font-size: 14px; color: #e0e0e0; font-weight: 600; margin-bottom: 8px;">
                        신규 학생 온보딩
                    </div>
                    <div style="font-size: 12px; color: #888;">
                        • Agent01+02 동시 트리거<br>
                        • S0 정보 수집 (0°)<br>
                        • 보강 간섭 100%
                    </div>
                    <div class="scenario-badge" style="margin-top: 10px; font-size: 11px; padding: 4px 8px; border-radius: 4px; display: inline-block; background: #22c55e33; color: #22c55e;">
                        ✅ CONSTRUCTIVE
                    </div>
                </div>

                <!-- 시나리오 3 -->
                <div class="scenario-card" data-scenario="3" onclick="runScenario(3)" style="background: #1a1a2e; border: 1px solid #30363d; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <span style="font-size: 24px;">⚠️</span>
                        <span style="font-weight: bold; color: #ef4444;">시나리오 3</span>
                    </div>
                    <div style="font-size: 14px; color: #e0e0e0; font-weight: 600; margin-bottom: 8px;">
                        목표 불일치 + 취약점 복합
                    </div>
                    <div style="font-size: 12px; color: #888;">
                        • Agent03+04 동시 트리거<br>
                        • S1 보강 간섭 (45°)<br>
                        • 복합 인사이트 생성
                    </div>
                    <div class="scenario-badge" style="margin-top: 10px; font-size: 11px; padding: 4px 8px; border-radius: 4px; display: inline-block; background: #ef444433; color: #ef4444;">
                        🚨 CRITICAL 개입
                    </div>
                </div>
            </div>

            <!-- 📚 Agent01-14 종합 시나리오 (3가지 시간 스케일) -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 2px dashed #4f46e5;">
                <div style="font-weight: bold; color: #a5b4fc; margin-bottom: 15px; font-size: 14px;">
                    📚 Agent01-14 종합 시나리오 (14개 에이전트 통합)
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                    <!-- 시나리오 4: 3시간 수업 -->
                    <div class="scenario-card" data-scenario="4" onclick="runScenario(4)" style="background: linear-gradient(135deg, #1a1a2e, #0f172a); border: 2px solid #3b82f6; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 24px;">⏱️</span>
                            <span style="font-weight: bold; color: #3b82f6;">시나리오 4</span>
                        </div>
                        <div style="font-size: 14px; color: #e0e0e0; font-weight: 600; margin-bottom: 8px;">
                            3시간 수업 실시간
                        </div>
                        <div style="font-size: 11px; color: #888; line-height: 1.5;">
                            • Agent01→08→04→05→13→12→14<br>
                            • 침착도95→문제풀이→감정변화→이탈감지<br>
                            • 실시간 Quantum 신호 진화
                        </div>
                        <div class="scenario-badge" style="margin-top: 10px; font-size: 10px; padding: 4px 8px; border-radius: 4px; display: inline-block; background: #3b82f633; color: #3b82f6;">
                            🔄 7단계 Phase 전이
                        </div>
                    </div>

                    <!-- 시나리오 5: 1주일 주간목표 -->
                    <div class="scenario-card" data-scenario="5" onclick="runScenario(5)" style="background: linear-gradient(135deg, #1a1a2e, #0f172a); border: 2px solid #8b5cf6; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 24px;">📅</span>
                            <span style="font-weight: bold; color: #8b5cf6;">시나리오 5</span>
                        </div>
                        <div style="font-size: 14px; color: #e0e0e0; font-weight: 600; margin-bottom: 8px;">
                            1주일 주간목표
                        </div>
                        <div style="font-size: 11px; color: #888; line-height: 1.5;">
                            • Agent02→03→09→07→05→06→11<br>
                            • 시험D-5→목표분석→이탈감지→피드백<br>
                            • 일별 간섭 패턴 누적
                        </div>
                        <div class="scenario-badge" style="margin-top: 10px; font-size: 10px; padding: 4px 8px; border-radius: 4px; display: inline-block; background: #8b5cf633; color: #8b5cf6;">
                            📈 7일 추세 분석
                        </div>
                    </div>

                    <!-- 시나리오 6: 2개월 분기목표 -->
                    <div class="scenario-card" data-scenario="6" onclick="runScenario(6)" style="background: linear-gradient(135deg, #1a1a2e, #0f172a); border: 2px solid #f59e0b; border-radius: 8px; padding: 15px; cursor: pointer; transition: all 0.3s;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 24px;">🎯</span>
                            <span style="font-weight: bold; color: #f59e0b;">시나리오 6</span>
                        </div>
                        <div style="font-size: 14px; color: #e0e0e0; font-weight: 600; margin-bottom: 8px;">
                            2개월 분기목표
                        </div>
                        <div style="font-size: 11px; color: #888; line-height: 1.5;">
                            • Agent01→02→03→09→12→13→14<br>
                            • 장기목표→월간리뷰→슬럼프감지→복구<br>
                            • 시간적 연쇄 효과 (λ=0.3)
                        </div>
                        <div class="scenario-badge" style="margin-top: 10px; font-size: 10px; padding: 4px 8px; border-radius: 4px; display: inline-block; background: #f59e0b33; color: #f59e0b;">
                            🌊 Temporal 간섭
                        </div>
                    </div>
                </div>
            </div>

            <!-- 🔮 가중치 붕괴 시나리오 (새로운 섹션) -->
            <div style="margin-top: 25px; padding-top: 20px; border-top: 2px dashed #a855f7;">
                <div style="font-weight: bold; color: #e9d5ff; margin-bottom: 15px; font-size: 14px;">
                    🔮 가중치 붕괴 시나리오 (Wave Function Collapse)
                </div>
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <!-- 시나리오 7: 가중치 붕괴 - 침착도 정체 -->
                    <div class="scenario-card" data-scenario="7" onclick="runScenario(7)" style="background: linear-gradient(135deg, #1a0f2e, #0f1a2e); border: 2px solid #a855f7; border-radius: 8px; padding: 20px; cursor: pointer; transition: all 0.3s;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                            <span style="font-size: 28px;">🔮</span>
                            <span style="font-weight: bold; color: #a855f7; font-size: 16px;">시나리오 7</span>
                            <span style="background: #7c3aed33; color: #c4b5fd; padding: 3px 10px; border-radius: 12px; font-size: 10px; font-weight: 600;">NEW - Phase 3 Preview</span>
                        </div>
                        <div style="font-size: 16px; color: #e0e0e0; font-weight: 600; margin-bottom: 10px;">
                            가중치 붕괴 - 침착도 정체 예측
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 12px;">
                            <div style="background: #0f0a1a; padding: 10px; border-radius: 6px; text-align: center;">
                                <div style="font-size: 10px; color: #a78bfa; margin-bottom: 4px;">📊 패턴 감지</div>
                                <div style="font-size: 12px; color: #e9d5ff;">정체 상태 6일</div>
                            </div>
                            <div style="background: #0f0a1a; padding: 10px; border-radius: 6px; text-align: center;">
                                <div style="font-size: 10px; color: #a78bfa; margin-bottom: 4px;">🌊 중첩 상태</div>
                                <div style="font-size: 12px; color: #e9d5ff;">3개 미래 시나리오</div>
                            </div>
                            <div style="background: #0f0a1a; padding: 10px; border-radius: 6px; text-align: center;">
                                <div style="font-size: 10px; color: #a78bfa; margin-bottom: 4px;">💥 붕괴 결정</div>
                                <div style="font-size: 12px; color: #e9d5ff;">확률 기반 예측</div>
                            </div>
                        </div>
                        <div style="font-size: 11px; color: #888; line-height: 1.6; margin-bottom: 12px;">
                            • <span style="color: #22c55e;">반등(0°)</span> vs <span style="color: #ef4444;">하락(135°)</span> vs <span style="color: #3b82f6;">정체(90°)</span> 시나리오 중첩<br>
                            • 베이지안 확률 조정 → 가장 가능성 높은 미래로 붕괴<br>
                            • 엔트로피 감소량 = 의사결정 정보량
                        </div>
                        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                            <div class="scenario-badge" style="font-size: 10px; padding: 4px 10px; border-radius: 4px; display: inline-block; background: #a855f733; color: #c4b5fd;">
                                🔮 Wave Function Collapse
                            </div>
                            <div class="scenario-badge" style="font-size: 10px; padding: 4px 10px; border-radius: 4px; display: inline-block; background: #ef444433; color: #fca5a5;">
                                🚨 선제적 개입
                            </div>
                            <div class="scenario-badge" style="font-size: 10px; padding: 4px 10px; border-radius: 4px; display: inline-block; background: #22c55e33; color: #86efac;">
                                📈 미래 예측
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button class="btn btn-primary" onclick="runAllScenarios()" style="background: linear-gradient(90deg, #6366f1, #8b5cf6); margin-top: 20px;">
                🎯 전체 시나리오 실행 (7개)
            </button>

            <div id="scenario-output" class="output-box" style="margin-top: 10px; display: none;"></div>
        </div>

        <!-- 🔬 Quantum Orchestration 원리 설명 -->
        <div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%); border: 1px solid #4f46e5;">
            <div class="card-header">
                <span class="card-title">🔬 Quantum Orchestration이 특별한 이유</span>
                <span class="badge" style="background: #4f46e5;">핵심 원리</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px;">
                <!-- 원리 1: 신호 변환 -->
                <div style="background: #1e293b; border-radius: 8px; padding: 15px; border-left: 3px solid #22c55e;">
                    <div style="font-size: 24px; margin-bottom: 8px;">🌊</div>
                    <div style="font-weight: bold; color: #22c55e; margin-bottom: 8px;">1. Quantum 신호 변환</div>
                    <div style="font-size: 12px; color: #94a3b8; line-height: 1.6;">
                        룰의 <span style="color: #f59e0b;">confidence</span>와 <span style="color: #8b5cf6;">priority</span>를
                        단일 <span style="color: #22c55e;">amplitude</span>로 통합
                    </div>
                    <div style="background: #0f172a; padding: 8px; border-radius: 4px; margin-top: 10px; font-family: monospace; font-size: 11px; color: #a5b4fc;">
                        A = conf × √(priority/100)
                    </div>
                </div>

                <!-- 원리 2: Phase 매핑 -->
                <div style="background: #1e293b; border-radius: 8px; padding: 15px; border-left: 3px solid #8b5cf6;">
                    <div style="font-size: 24px; margin-bottom: 8px;">🎯</div>
                    <div style="font-weight: bold; color: #8b5cf6; margin-bottom: 8px;">2. 시나리오 Phase 매핑</div>
                    <div style="font-size: 12px; color: #94a3b8; line-height: 1.6;">
                        각 시나리오(S0~S4)를 <span style="color: #22c55e;">위상각</span>으로 변환하여
                        <span style="color: #f59e0b;">간섭 패턴</span> 계산
                    </div>
                    <div style="display: flex; gap: 4px; margin-top: 10px; flex-wrap: wrap;">
                        <span style="background: #22c55e33; color: #22c55e; padding: 2px 6px; border-radius: 3px; font-size: 10px;">S0=0°</span>
                        <span style="background: #3b82f633; color: #3b82f6; padding: 2px 6px; border-radius: 3px; font-size: 10px;">S1=45°</span>
                        <span style="background: #8b5cf633; color: #8b5cf6; padding: 2px 6px; border-radius: 3px; font-size: 10px;">S2=90°</span>
                        <span style="background: #f59e0b33; color: #f59e0b; padding: 2px 6px; border-radius: 3px; font-size: 10px;">S3=135°</span>
                        <span style="background: #ef444433; color: #ef4444; padding: 2px 6px; border-radius: 3px; font-size: 10px;">S4=180°</span>
                    </div>
                </div>

                <!-- 원리 3: 간섭 계산 -->
                <div style="background: #1e293b; border-radius: 8px; padding: 15px; border-left: 3px solid #f59e0b;">
                    <div style="font-size: 24px; margin-bottom: 8px;">⚡</div>
                    <div style="font-weight: bold; color: #f59e0b; margin-bottom: 8px;">3. 간섭 패턴 계산</div>
                    <div style="font-size: 12px; color: #94a3b8; line-height: 1.6;">
                        다중 에이전트가 동시 트리거될 때
                        <span style="color: #22c55e;">보강/상쇄</span> 간섭으로 최적 개입 결정
                    </div>
                    <div style="margin-top: 10px; font-size: 11px;">
                        <div style="color: #22c55e;">✓ 같은 phase → 보강 (100%)</div>
                        <div style="color: #f59e0b;">△ 45° 차이 → 부분 (~92%)</div>
                        <div style="color: #ef4444;">✗ 180° 차이 → 상쇄 (~0%)</div>
                    </div>
                </div>
            </div>

            <!-- 기존 방식 vs Quantum 방식 비교 -->
            <div style="background: #0f172a; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <div style="font-weight: bold; color: #e0e0e0; margin-bottom: 15px; font-size: 14px;">📊 기존 Rule Engine vs Quantum Orchestration</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div style="background: #dc262622; border: 1px solid #dc2626; border-radius: 6px; padding: 12px;">
                        <div style="font-weight: bold; color: #dc2626; margin-bottom: 8px;">❌ 기존 Rule Engine</div>
                        <ul style="font-size: 11px; color: #94a3b8; margin: 0; padding-left: 15px; line-height: 1.8;">
                            <li>룰 간 <span style="color: #dc2626;">충돌 시 우선순위만</span> 고려</li>
                            <li>다중 에이전트 <span style="color: #dc2626;">개별 실행</span></li>
                            <li>시나리오 간 <span style="color: #dc2626;">상호작용 무시</span></li>
                            <li>개입 강도 = MAX(개별 점수)</li>
                        </ul>
                    </div>
                    <div style="background: #22c55e22; border: 1px solid #22c55e; border-radius: 6px; padding: 12px;">
                        <div style="font-weight: bold; color: #22c55e; margin-bottom: 8px;">✅ Quantum Orchestration</div>
                        <ul style="font-size: 11px; color: #94a3b8; margin: 0; padding-left: 15px; line-height: 1.8;">
                            <li>룰의 <span style="color: #22c55e;">confidence × priority</span> 통합</li>
                            <li>다중 에이전트 <span style="color: #22c55e;">간섭 패턴</span> 계산</li>
                            <li>시나리오 간 <span style="color: #22c55e;">보강/상쇄</span> 효과</li>
                            <li>개입 강도 = Σ(amplitude × cos(Δphase))</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 파동 간섭 시각화 -->
            <div style="background: #1e293b; border-radius: 8px; padding: 15px;">
                <div style="font-weight: bold; color: #e0e0e0; margin-bottom: 10px; font-size: 14px;">🌊 파동 간섭 시각화</div>
                <div style="display: flex; gap: 20px; justify-content: center; align-items: center;">
                    <!-- 보강 간섭 -->
                    <div style="text-align: center;">
                        <svg width="100" height="60" viewBox="0 0 100 60">
                            <path d="M0,30 Q25,10 50,30 Q75,50 100,30" stroke="#22c55e" stroke-width="2" fill="none" opacity="0.5"/>
                            <path d="M0,30 Q25,10 50,30 Q75,50 100,30" stroke="#3b82f6" stroke-width="2" fill="none" opacity="0.5"/>
                            <path d="M0,30 Q25,0 50,30 Q75,60 100,30" stroke="#f59e0b" stroke-width="2" fill="none"/>
                        </svg>
                        <div style="font-size: 10px; color: #22c55e; margin-top: 5px;">보강 (같은 Phase)</div>
                        <div style="font-size: 9px; color: #888;">A₁ + A₂ = 2A</div>
                    </div>
                    <!-- 부분 간섭 -->
                    <div style="text-align: center;">
                        <svg width="100" height="60" viewBox="0 0 100 60">
                            <path d="M0,30 Q25,10 50,30 Q75,50 100,30" stroke="#22c55e" stroke-width="2" fill="none" opacity="0.5"/>
                            <path d="M0,35 Q25,15 50,35 Q75,55 100,35" stroke="#3b82f6" stroke-width="2" fill="none" opacity="0.5"/>
                            <path d="M0,32 Q25,8 50,32 Q75,56 100,32" stroke="#f59e0b" stroke-width="2" fill="none"/>
                        </svg>
                        <div style="font-size: 10px; color: #f59e0b; margin-top: 5px;">부분 (45° 차이)</div>
                        <div style="font-size: 9px; color: #888;">~1.85A (92%)</div>
                    </div>
                    <!-- 상쇄 간섭 -->
                    <div style="text-align: center;">
                        <svg width="100" height="60" viewBox="0 0 100 60">
                            <path d="M0,30 Q25,10 50,30 Q75,50 100,30" stroke="#22c55e" stroke-width="2" fill="none" opacity="0.5"/>
                            <path d="M0,30 Q25,50 50,30 Q75,10 100,30" stroke="#3b82f6" stroke-width="2" fill="none" opacity="0.5"/>
                            <path d="M0,30 L100,30" stroke="#ef4444" stroke-width="2" fill="none"/>
                        </svg>
                        <div style="font-size: 10px; color: #ef4444; margin-top: 5px;">상쇄 (180° 차이)</div>
                        <div style="font-size: 9px; color: #888;">A₁ - A₂ ≈ 0</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 🚀 향후 업그레이드 로드맵 -->
        <div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 1px solid #0ea5e9;">
            <div class="card-header">
                <span class="card-title">🚀 향후 업그레이드 로드맵</span>
                <span class="badge" style="background: #0ea5e9;">v2.0 → v3.0</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px;">
                <!-- Phase 1 -->
                <div style="background: #0f172a; border-radius: 8px; padding: 15px; border-top: 3px solid #22c55e;">
                    <div style="font-size: 11px; color: #22c55e; margin-bottom: 5px;">Phase 1 ✅</div>
                    <div style="font-weight: bold; color: #e0e0e0; font-size: 13px; margin-bottom: 8px;">기본 Quantum 변환</div>
                    <ul style="font-size: 10px; color: #94a3b8; margin: 0; padding-left: 12px; line-height: 1.6;">
                        <li>신호 변환 공식</li>
                        <li>Phase 매핑</li>
                        <li>2-Agent 간섭</li>
                    </ul>
                </div>

                <!-- Phase 2 -->
                <div style="background: #0f172a; border-radius: 8px; padding: 15px; border-top: 3px solid #3b82f6;">
                    <div style="font-size: 11px; color: #3b82f6; margin-bottom: 5px;">Phase 2 🔄</div>
                    <div style="font-weight: bold; color: #e0e0e0; font-size: 13px; margin-bottom: 8px;">다중 에이전트 간섭</div>
                    <ul style="font-size: 10px; color: #94a3b8; margin: 0; padding-left: 12px; line-height: 1.6;">
                        <li>N-Agent 동시 간섭</li>
                        <li>간섭 매트릭스</li>
                        <li>시간적 지연 효과</li>
                    </ul>
                </div>

                <!-- Phase 3 -->
                <div style="background: #0f172a; border-radius: 8px; padding: 15px; border-top: 3px solid #8b5cf6;">
                    <div style="font-size: 11px; color: #8b5cf6; margin-bottom: 5px;">Phase 3 📋</div>
                    <div style="font-weight: bold; color: #e0e0e0; font-size: 13px; margin-bottom: 8px;">Quantum Entanglement</div>
                    <ul style="font-size: 10px; color: #94a3b8; margin: 0; padding-left: 12px; line-height: 1.6;">
                        <li>에이전트 간 얽힘</li>
                        <li>상태 전파 메커니즘</li>
                        <li>비국소적 상관관계</li>
                    </ul>
                </div>

                <!-- Phase 4 -->
                <div style="background: #0f172a; border-radius: 8px; padding: 15px; border-top: 3px solid #f59e0b;">
                    <div style="font-size: 11px; color: #f59e0b; margin-bottom: 5px;">Phase 4 🎯</div>
                    <div style="font-weight: bold; color: #e0e0e0; font-size: 13px; margin-bottom: 8px;">자가 최적화</div>
                    <ul style="font-size: 10px; color: #94a3b8; margin: 0; padding-left: 12px; line-height: 1.6;">
                        <li>학습 기반 Phase 조정</li>
                        <li>동적 amplitude 스케일링</li>
                        <li>피드백 루프 통합</li>
                    </ul>
                </div>
            </div>

            <!-- 기술 스택 확장 -->
            <div style="margin-top: 20px; background: #0f172a; border-radius: 8px; padding: 15px;">
                <div style="font-weight: bold; color: #e0e0e0; margin-bottom: 10px; font-size: 13px;">🔧 기술 스택 확장 계획</div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <span style="background: #3b82f633; color: #3b82f6; padding: 4px 10px; border-radius: 4px; font-size: 11px;">NumPy 복소수 연산</span>
                    <span style="background: #8b5cf633; color: #8b5cf6; padding: 4px 10px; border-radius: 4px; font-size: 11px;">FFT 주파수 분석</span>
                    <span style="background: #22c55e33; color: #22c55e; padding: 4px 10px; border-radius: 4px; font-size: 11px;">실시간 스트리밍</span>
                    <span style="background: #f59e0b33; color: #f59e0b; padding: 4px 10px; border-radius: 4px; font-size: 11px;">벡터 DB 통합</span>
                    <span style="background: #ef444433; color: #ef4444; padding: 4px 10px; border-radius: 4px; font-size: 11px;">ML 기반 예측</span>
                </div>
            </div>
        </div>

        <!-- 🔮 시간적 연쇄작용 시나리오: 미래 업그레이드로 가능해지는 것 -->
        <div class="card" style="margin-bottom: 30px; background: linear-gradient(135deg, #1a0f2e 0%, #0f1a2e 100%); border: 1px solid #a855f7;">
            <div class="card-header">
                <span class="card-title">🔮 시간적 연쇄작용 시나리오</span>
                <span class="badge" style="background: #a855f7;">Phase 3-4 Preview</span>
            </div>

            <!-- 시나리오 설명 -->
            <div style="background: #0f0a1a; border-radius: 8px; padding: 12px; margin-bottom: 15px; border-left: 3px solid #a855f7;">
                <div style="font-size: 13px; font-weight: bold; color: #e9d5ff; margin-bottom: 6px;">📖 시나리오: 김민수 학생의 3주간 변화</div>
                <div style="font-size: 11px; color: #a78bfa; line-height: 1.6;">
                    Week 1: 목표 달성률 급락 (75%→45%) → Week 2: 취약점 감지 + 동기 저하 → Week 3: 회복 시도 중 재발 위험
                </div>
            </div>

            <!-- Before vs After 비교 -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                <!-- 현재 (v2.0) -->
                <div style="background: #1a0a0a; border-radius: 8px; padding: 12px; border: 1px solid #ef4444;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 12px; font-weight: bold; color: #fca5a5;">❌ 현재 v2.0 (독립적 처리)</span>
                        <span style="background: #7f1d1d; padding: 2px 8px; border-radius: 10px; font-size: 9px; color: #fca5a5;">제한적</span>
                    </div>

                    <!-- 타임라인 -->
                    <div style="font-size: 10px; color: #94a3b8;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding: 6px; background: #1f1f1f; border-radius: 4px;">
                            <span style="color: #f87171;">W1</span>
                            <span style="color: #6b7280;">⚡0.85 → 🌀45° → 🌊0.85</span>
                            <span style="background: #f97316; padding: 1px 6px; border-radius: 8px; font-size: 9px;">HIGH</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding: 6px; background: #1f1f1f; border-radius: 4px;">
                            <span style="color: #f87171;">W2</span>
                            <span style="color: #6b7280;">⚡0.90 → 🌀90° → 🌊0.90</span>
                            <span style="background: #f97316; padding: 1px 6px; border-radius: 8px; font-size: 9px;">HIGH</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 6px; background: #1f1f1f; border-radius: 4px;">
                            <span style="color: #f87171;">W3</span>
                            <span style="color: #6b7280;">⚡0.75 → 🌀45° → 🌊0.75</span>
                            <span style="background: #f59e0b; padding: 1px 6px; border-radius: 8px; font-size: 9px;">MED</span>
                        </div>
                    </div>

                    <div style="margin-top: 10px; padding: 8px; background: #2d1f1f; border-radius: 4px;">
                        <div style="font-size: 10px; color: #f87171; margin-bottom: 4px;">⚠️ 문제점</div>
                        <ul style="font-size: 9px; color: #fca5a5; margin: 0; padding-left: 12px; line-height: 1.5;">
                            <li>각 주차를 <b>독립적으로</b> 평가</li>
                            <li>W1→W2 악화 패턴 미감지</li>
                            <li>W3 "회복 중" 오판 (실제는 재발 직전)</li>
                            <li>누적 피로 효과 무시</li>
                        </ul>
                    </div>
                </div>

                <!-- 미래 (v3.0) -->
                <div style="background: #0a1a0f; border-radius: 8px; padding: 12px; border: 1px solid #22c55e;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 12px; font-weight: bold; color: #86efac;">✅ 미래 v3.0 (시간적 얽힘)</span>
                        <span style="background: #166534; padding: 2px 8px; border-radius: 10px; font-size: 9px; color: #86efac;">Phase 3-4</span>
                    </div>

                    <!-- 얽힘 타임라인 -->
                    <div style="font-size: 10px; color: #94a3b8;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; padding: 6px; background: #1f2f1f; border-radius: 4px;">
                            <span style="color: #4ade80;">W1</span>
                            <span style="color: #6b7280;">⚡0.85∠45°</span>
                            <span style="color: #facc15;">→ 메모리 저장</span>
                        </div>
                        <div style="text-align: center; color: #a855f7; font-size: 16px;">⟨ψ₁|ψ₂⟩ 얽힘</div>
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px; padding: 6px; background: #1f2f1f; border-radius: 4px;">
                            <span style="color: #4ade80;">W2</span>
                            <span style="color: #6b7280;">⚡0.90∠90° <span style="color: #f87171;">+ W1 잔류파</span></span>
                        </div>
                        <div style="text-align: center; color: #a855f7; font-size: 16px;">⟨ψ₂|ψ₃⟩ 전파</div>
                        <div style="display: flex; align-items: center; gap: 8px; padding: 6px; background: #2f1f2f; border-radius: 4px; border: 1px solid #a855f7;">
                            <span style="color: #c084fc;">W3</span>
                            <span style="color: #e9d5ff;">⚡0.75 + Σ(W1,W2) = <b style="color: #f87171;">1.45</b></span>
                            <span style="background: #ef4444; padding: 1px 6px; border-radius: 8px; font-size: 9px;">CRITICAL</span>
                        </div>
                    </div>

                    <div style="margin-top: 10px; padding: 8px; background: #1f2d1f; border-radius: 4px;">
                        <div style="font-size: 10px; color: #4ade80; margin-bottom: 4px;">✨ 새로운 기능</div>
                        <ul style="font-size: 9px; color: #86efac; margin: 0; padding-left: 12px; line-height: 1.5;">
                            <li><b>시간적 간섭</b>: 과거 신호가 현재에 영향</li>
                            <li><b>패턴 인식</b>: W1→W2 악화 추세 감지</li>
                            <li><b>누적 amplitude</b>: 1.45 > 임계값 1.2</li>
                            <li><b>선제적 개입</b>: "회복 중" 아닌 "재발 위험"</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- 시간적 간섭 공식 -->
            <div style="background: #0f0a1a; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                <div style="font-size: 11px; font-weight: bold; color: #c4b5fd; margin-bottom: 8px;">📐 시간적 간섭 공식 (Phase 3)</div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-family: monospace; font-size: 10px;">
                    <div style="background: #1a1025; padding: 8px; border-radius: 4px;">
                        <div style="color: #a78bfa; margin-bottom: 4px;">잔류파 감쇠:</div>
                        <div style="color: #e9d5ff;">A<sub>residual</sub>(t) = A<sub>0</sub> × e<sup>-λt</sup></div>
                        <div style="color: #6b7280; font-size: 9px; margin-top: 4px;">λ = 0.3 (주간 감쇠율)</div>
                    </div>
                    <div style="background: #1a1025; padding: 8px; border-radius: 4px;">
                        <div style="color: #a78bfa; margin-bottom: 4px;">누적 amplitude:</div>
                        <div style="color: #e9d5ff;">A<sub>total</sub> = Σ(A<sub>i</sub> × e<sup>-λ(t-t<sub>i</sub>)</sup>)</div>
                        <div style="color: #6b7280; font-size: 9px; margin-top: 4px;">과거 모든 신호의 가중합</div>
                    </div>
                </div>
            </div>

            <!-- 구체적 피드백 비교 -->
            <div style="background: #0f0a1a; border-radius: 8px; padding: 12px;">
                <div style="font-size: 11px; font-weight: bold; color: #fbbf24; margin-bottom: 10px;">🎯 김민수 학생에게 보내는 피드백 비교</div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <!-- 현재 피드백 -->
                    <div style="background: #1a0f0f; padding: 10px; border-radius: 6px; border-left: 3px solid #ef4444;">
                        <div style="font-size: 10px; color: #f87171; margin-bottom: 6px;">❌ 현재 시스템 (W3 단독 분석)</div>
                        <div style="font-size: 11px; color: #d1d5db; background: #0f0f0f; padding: 8px; border-radius: 4px; line-height: 1.5;">
                            "목표 달성률이 <b>개선</b>되고 있습니다. 현재 페이스를 유지하세요. 💪"
                        </div>
                        <div style="font-size: 9px; color: #f87171; margin-top: 6px;">
                            → W3 단독 75% > W2 45%로 "개선"으로 오판
                        </div>
                    </div>

                    <!-- 미래 피드백 -->
                    <div style="background: #0f1a0f; padding: 10px; border-radius: 6px; border-left: 3px solid #22c55e;">
                        <div style="font-size: 10px; color: #4ade80; margin-bottom: 6px;">✅ 미래 시스템 (시간적 얽힘)</div>
                        <div style="font-size: 11px; color: #d1d5db; background: #0f1f0f; padding: 8px; border-radius: 4px; line-height: 1.5;">
                            "⚠️ <b>누적 스트레스</b> 감지. 3주간 연속 하락 패턴에서 <b>일시적 반등</b> 중입니다. <b>번아웃 예방</b>을 위해 이번 주는 목표를 70%로 <b>하향 조정</b>하고 충분한 휴식을 권장합니다. 🌿"
                        </div>
                        <div style="font-size: 9px; color: #4ade80; margin-top: 6px;">
                            → 누적 amplitude 1.45 기반 선제적 개입
                        </div>
                    </div>
                </div>

                <!-- 추가 인사이트 -->
                <div style="margin-top: 12px; padding: 8px; background: #1a1025; border-radius: 4px; border: 1px dashed #a855f7;">
                    <div style="font-size: 10px; color: #c4b5fd;">
                        💡 <b>Quantum Entanglement 효과</b>: Agent01(취약점)의 W1 신호가 Agent02(목표관리)의 W3 판단에 <b>비국소적으로</b> 영향을 미침.
                        이는 기존 Rule Engine에서는 절대 불가능한 <b>시공간 초월 상관관계</b>입니다.
                    </div>
                </div>
            </div>
        </div>

        <!-- 에이전트 상태 -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-header">
                <span class="card-title">🤖 에이전트 상태 (Agent01~04)</span>
            </div>
            <div class="agent-grid">
                <?php foreach ($agentResults as $num => $result): ?>
                <div class="agent-card">
                    <div class="agent-num"><?php echo sprintf("%02d", $num); ?></div>
                    <div style="margin: 10px 0;">
                        <span class="status-icon status-<?php echo $result['status']; ?>">
                            <?php echo $result['status'] === 'success' ? '✅' : ($result['status'] === 'warning' ? '⚠️' : '❌'); ?>
                        </span>
                    </div>
                    <div style="font-size: 0.85em; color: #888;">
                        <?php echo $result['has_rules'] ? "{$result['rules_count']} rules" : "No rules"; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Phase별 모듈 체크 -->
        <div class="grid">
            <?php foreach ($phases as $phase): ?>
            <div class="card" data-phase="<?php echo $phase['id']; ?>">
                <div class="card-header">
                    <span class="card-title"><?php echo htmlspecialchars($phase['name']); ?></span>
                    <span class="badge badge-pending phase-badge">체크 필요</span>
                </div>
                <p style="color: #888; margin-bottom: 15px; font-size: 0.9em;">
                    <?php echo htmlspecialchars($phase['description']); ?>
                </p>
                <div class="progress-bar">
                    <div class="progress-fill phase-progress" style="width: 0%"></div>
                </div>
                <ul class="module-list">
                    <?php foreach ($phase['modules'] as $module): ?>
                    <li class="module-item" data-module="<?php echo htmlspecialchars($module['file']); ?>" onclick="checkModule(this)">
                        <div>
                            <div class="module-name"><?php echo htmlspecialchars($module['file']); ?></div>
                            <div class="module-desc"><?php echo htmlspecialchars($module['desc']); ?></div>
                        </div>
                        <span class="status-icon status-pending">⏳</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <button class="btn btn-secondary" style="margin-top: 15px; width: 100%;" onclick="checkAllInPhase(this)">
                    📋 전체 체크
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <footer>
            <p>Quantum Orchestration POC Dashboard v1.2 - AUTO CHECK ENABLED</p>
            <p style="margin-top: 5px;">User: <?php echo htmlspecialchars($USER->username ?? 'Unknown'); ?> |
               Path: <?php echo htmlspecialchars(HOLONS_PATH); ?></p>
        </footer>
    </div>

    <script>
        // v1.2 - 자동 검증 기능 + 캐시 디버깅
        console.log('🚀 POC Dashboard v1.2 로드됨 - AUTO CHECK ACTIVE');
        console.log('📌 이 메시지가 보이면 v1.2 코드가 실행 중입니다');

        // 🌊 파동 간섭 시각화 함수
        const phaseColors = {
            0: '#22c55e',    // S0: 녹색 (정보수집)
            45: '#f59e0b',   // S1: 주황 (목표-계획)
            90: '#3b82f6',   // S2: 파랑 (학습패턴)
            135: '#ef4444',  // S3: 빨강 (이탈위험)
            180: '#a855f7'   // S4: 보라 (임계)
        };

        const phaseNames = {
            0: 'S0 정보수집',
            45: 'S1 목표-계획',
            90: 'S2 학습패턴',
            135: 'S3 이탈위험',
            180: 'S4 임계'
        };

        // 🎨 파동 간섭 캔버스 그리기
        function drawWaveInterference(canvasId, signals, interferenceResult) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            const centerY = height / 2;

            // 배경 클리어
            ctx.clearRect(0, 0, width, height);

            // 격자 그리기
            ctx.strokeStyle = '#1e293b';
            ctx.lineWidth = 0.5;
            for (let i = 0; i <= 10; i++) {
                ctx.beginPath();
                ctx.moveTo(i * width / 10, 0);
                ctx.lineTo(i * width / 10, height);
                ctx.stroke();
            }
            ctx.beginPath();
            ctx.moveTo(0, centerY);
            ctx.lineTo(width, centerY);
            ctx.strokeStyle = '#334155';
            ctx.stroke();

            // 개별 파동 그리기
            const waveData = [];
            signals.forEach((sig, idx) => {
                const amp = sig.amplitude || 0;
                const phaseDeg = sig.phase_deg || 0;
                const phaseRad = phaseDeg * Math.PI / 180;
                const color = phaseColors[phaseDeg] || '#888';

                ctx.beginPath();
                ctx.strokeStyle = color;
                ctx.lineWidth = 1.5;
                ctx.globalAlpha = 0.6;

                const points = [];
                for (let x = 0; x < width; x++) {
                    const t = (x / width) * 4 * Math.PI;
                    const y = centerY - amp * 30 * Math.sin(t + phaseRad);
                    points.push(y);
                    if (x === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.stroke();
                waveData.push(points);
            });

            // 합성 파동 그리기 (두꺼운 흰색)
            if (signals.length > 1) {
                ctx.beginPath();
                ctx.strokeStyle = '#ffffff';
                ctx.lineWidth = 2.5;
                ctx.globalAlpha = 1;

                for (let x = 0; x < width; x++) {
                    let sumY = 0;
                    signals.forEach((sig, idx) => {
                        const amp = sig.amplitude || 0;
                        const phaseDeg = sig.phase_deg || 0;
                        const phaseRad = phaseDeg * Math.PI / 180;
                        const t = (x / width) * 4 * Math.PI;
                        sumY += amp * 30 * Math.sin(t + phaseRad);
                    });
                    const y = centerY - sumY;
                    if (x === 0) ctx.moveTo(x, y);
                    else ctx.lineTo(x, y);
                }
                ctx.stroke();
            }

            // 간섭 타입 표시
            const intType = interferenceResult?.type || 'SINGLE';
            const efficiency = interferenceResult?.efficiency || '100%';
            ctx.globalAlpha = 1;
            ctx.fillStyle = '#8b5cf6';
            ctx.font = 'bold 10px sans-serif';
            ctx.fillText(`${intType}`, 5, 12);
            ctx.fillStyle = '#22c55e';
            ctx.fillText(`효율: ${efficiency}`, width - 70, 12);
        }

        // 📊 확률 분포 막대 그래프 생성
        function createProbabilityBars(signals) {
            if (!signals || signals.length === 0) return '';

            // 위상별 확률 계산 (|amplitude|²)
            const phaseProbs = {};
            let totalProb = 0;

            signals.forEach(sig => {
                const phase = sig.phase_deg || 0;
                const amp = sig.amplitude || 0;
                const prob = amp * amp; // |Ψ|²

                if (!phaseProbs[phase]) phaseProbs[phase] = 0;
                phaseProbs[phase] += prob;
                totalProb += prob;
            });

            // 정규화
            let html = '<div class="visualization-container"><div class="viz-title">📊 확률 분포 |Ψ|²</div>';

            Object.keys(phaseProbs).sort((a,b) => a-b).forEach(phase => {
                const prob = phaseProbs[phase];
                const percent = totalProb > 0 ? (prob / totalProb * 100).toFixed(1) : 0;
                const color = phaseColors[phase] || '#888';
                const name = phaseNames[phase] || `Phase ${phase}°`;

                html += `<div class="prob-bar-container">
                    <span class="prob-bar-label">${name}</span>
                    <div class="prob-bar">
                        <div class="prob-bar-fill" style="width:${percent}%;background:${color};"></div>
                        <span class="prob-bar-value">${percent}%</span>
                    </div>
                </div>`;
            });

            html += '</div>';
            return html;
        }

        // 🌀 에이전트별 위상 레이더 차트 (간단 버전)
        function createPhaseRadar(canvasId, signals) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            const width = canvas.width;
            const height = canvas.height;
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) / 2 - 20;

            ctx.clearRect(0, 0, width, height);

            // 배경 원 그리기
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
            ctx.strokeStyle = '#334155';
            ctx.lineWidth = 1;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(centerX, centerY, radius * 0.5, 0, 2 * Math.PI);
            ctx.strokeStyle = '#1e293b';
            ctx.stroke();

            // 축 그리기 (0°, 45°, 90°, 135°, 180°)
            [0, 45, 90, 135, 180, 225, 270, 315].forEach(deg => {
                const rad = (deg - 90) * Math.PI / 180;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.lineTo(centerX + radius * Math.cos(rad), centerY + radius * Math.sin(rad));
                ctx.strokeStyle = '#1e293b';
                ctx.stroke();
            });

            // 각 신호를 점으로 표시
            signals.forEach((sig, idx) => {
                const amp = sig.amplitude || 0;
                const phaseDeg = sig.phase_deg || 0;
                const phaseRad = (phaseDeg - 90) * Math.PI / 180;
                const r = amp * radius;
                const x = centerX + r * Math.cos(phaseRad);
                const y = centerY + r * Math.sin(phaseRad);
                const color = phaseColors[phaseDeg] || '#888';

                // 점 그리기
                ctx.beginPath();
                ctx.arc(x, y, 5, 0, 2 * Math.PI);
                ctx.fillStyle = color;
                ctx.fill();

                // 레이블
                ctx.fillStyle = '#e0e0e0';
                ctx.font = '9px sans-serif';
                const label = sig.agent || `A${sig.agent_id || idx+1}`;
                ctx.fillText(label, x + 7, y + 3);
            });

            // 합성 벡터 그리기
            let realSum = 0, imagSum = 0;
            signals.forEach(sig => {
                const amp = sig.amplitude || 0;
                const phaseRad = (sig.phase_deg || 0) * Math.PI / 180;
                realSum += amp * Math.cos(phaseRad);
                imagSum += amp * Math.sin(phaseRad);
            });

            const totalAmp = Math.sqrt(realSum*realSum + imagSum*imagSum);
            const totalPhase = Math.atan2(imagSum, realSum);
            const totalR = totalAmp * radius / (signals.length || 1);
            const totalX = centerX + totalR * Math.cos(totalPhase - Math.PI/2);
            const totalY = centerY + totalR * Math.sin(totalPhase - Math.PI/2);

            // 합성 벡터 화살표
            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(totalX, totalY);
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 2;
            ctx.stroke();

            // 합성 점
            ctx.beginPath();
            ctx.arc(totalX, totalY, 7, 0, 2 * Math.PI);
            ctx.fillStyle = '#ffffff';
            ctx.fill();
        }

        // 🎬 전체 시각화 생성 함수
        function generateVisualization(result, scenarioId) {
            const signals = result.quantum_signals || (result.quantum_signal ? [result.quantum_signal] : []);
            if (signals.length === 0) return '';

            const canvasId = `wave-canvas-${scenarioId}`;
            const radarId = `radar-canvas-${scenarioId}`;

            let html = `<div class="visualization-container">
                <div class="viz-title">🌊 파동 간섭 시각화 (${signals.length} Agents)</div>
                <canvas id="${canvasId}" class="wave-canvas" width="360" height="100"></canvas>
                <div class="viz-legend">`;

            // 범례 생성
            const usedPhases = [...new Set(signals.map(s => s.phase_deg || 0))];
            usedPhases.sort((a,b) => a-b).forEach(phase => {
                const color = phaseColors[phase] || '#888';
                const name = phaseNames[phase] || `Phase ${phase}°`;
                const agents = signals.filter(s => (s.phase_deg || 0) === phase)
                    .map(s => s.agent || `A${s.agent_id}`).join(', ');
                html += `<div class="legend-item">
                    <div class="legend-color" style="background:${color}"></div>
                    <span>${name}: ${agents}</span>
                </div>`;
            });

            html += `</div></div>`;

            // 위상 레이더 차트 (복수 에이전트인 경우)
            if (signals.length > 1) {
                html += `<div class="visualization-container">
                    <div class="viz-title">🌀 위상 공간 분포</div>
                    <canvas id="${radarId}" class="wave-canvas" width="180" height="180" style="display:block;margin:0 auto;"></canvas>
                </div>`;
            }

            // 확률 분포 막대 그래프
            html += createProbabilityBars(signals);

            // 캔버스 그리기를 위한 setTimeout (DOM 렌더 후 실행)
            setTimeout(() => {
                drawWaveInterference(canvasId, signals, result.interference_result);
                if (signals.length > 1) {
                    createPhaseRadar(radarId, signals);
                }
            }, 100);

            return html;
        }

        let moduleResults = {};
        let totalModules = 0;
        let readyModules = 0;

        // 초기화 - 페이지 로드 시 자동 검증
        document.addEventListener('DOMContentLoaded', async function() {
            totalModules = document.querySelectorAll('.module-item').length;
            document.getElementById('total-modules').textContent = totalModules;

            // 자동 검증 실행
            await autoCheckAllModules();
        });

        // 페이지 로드 시 전체 모듈 자동 검증
        async function autoCheckAllModules() {
            // 모든 아이콘을 로딩 상태로 변경
            document.querySelectorAll('.module-item').forEach(item => {
                const icon = item.querySelector('.status-icon');
                icon.innerHTML = '<span class="loading"></span>';
            });

            try {
                console.log('🔄 자동 모듈 검증 시작...');
                const response = await fetch('?action=check_all_modules&t=' + Date.now());
                const results = await response.json();
                console.log('✅ 검증 결과:', results);

                // 결과 적용
                for (const [module, result] of Object.entries(results)) {
                    moduleResults[module] = result;

                    const item = document.querySelector(`.module-item[data-module="${module}"]`);
                    if (item) {
                        const icon = item.querySelector('.status-icon');
                        icon.className = 'status-icon status-' + result.status;
                        icon.textContent = result.status === 'success' ? '✅' : (result.status === 'warning' ? '⚠️' : '❌');
                        item.title = result.message;
                    }
                }

                updateStats();

                // 성공 시 배너 업데이트
                const banner = document.getElementById('auto-check-banner');
                if (banner) {
                    const successCount = Object.values(results).filter(r => r.status === 'success').length;
                    const totalCount = Object.keys(results).length;
                    banner.style.background = successCount === totalCount
                        ? 'linear-gradient(90deg, #00c853, #00e676)'
                        : 'linear-gradient(90deg, #ff9800, #ffc107)';
                    banner.innerHTML = `✅ v1.2 자동 검증 완료: ${successCount}/${totalCount} 모듈 준비됨`;
                }

            } catch (err) {
                console.error('❌ Auto-check failed:', err);
                // 실패 시 배너 업데이트
                const banner = document.getElementById('auto-check-banner');
                if (banner) {
                    banner.style.background = 'linear-gradient(90deg, #f44336, #e91e63)';
                    banner.innerHTML = '❌ 자동 검증 실패: ' + err.message;
                }
                // 실패 시 모든 아이콘을 에러로 표시
                document.querySelectorAll('.module-item').forEach(item => {
                    const icon = item.querySelector('.status-icon');
                    icon.className = 'status-icon status-error';
                    icon.textContent = '❌';
                    item.title = '자동 체크 실패: ' + err.message;
                });
            }
        }

        // 통계 업데이트
        function updateStats() {
            readyModules = Object.values(moduleResults).filter(r => r.status === 'success').length;
            document.getElementById('ready-modules').textContent = readyModules;

            const percent = totalModules > 0 ? Math.round((readyModules / totalModules) * 100) : 0;
            document.getElementById('progress-percent').textContent = percent + '%';

            // 에이전트 카운트
            const agentCards = document.querySelectorAll('.agent-card .status-icon');
            let agentsReady = 0;
            agentCards.forEach(icon => {
                if (icon.textContent.includes('✅')) agentsReady++;
            });
            document.getElementById('agents-ready').textContent = agentsReady + '/4';

            // Phase별 진행률 업데이트
            document.querySelectorAll('.card[data-phase]').forEach(card => {
                const items = card.querySelectorAll('.module-item');
                let phaseReady = 0;
                items.forEach(item => {
                    const module = item.dataset.module;
                    if (moduleResults[module]?.status === 'success') phaseReady++;
                });
                const phasePercent = items.length > 0 ? (phaseReady / items.length) * 100 : 0;
                card.querySelector('.phase-progress').style.width = phasePercent + '%';

                const badge = card.querySelector('.phase-badge');
                if (phasePercent === 100) {
                    badge.className = 'badge badge-success';
                    badge.textContent = '완료';
                } else if (phasePercent > 0) {
                    badge.className = 'badge badge-warning';
                    badge.textContent = Math.round(phasePercent) + '%';
                }
            });
        }

        // 모듈 체크
        async function checkModule(element) {
            const module = element.dataset.module;
            const icon = element.querySelector('.status-icon');

            icon.innerHTML = '<span class="loading"></span>';

            try {
                const response = await fetch(`?action=check_module&module=${encodeURIComponent(module)}`);
                const result = await response.json();

                moduleResults[module] = result;

                icon.className = 'status-icon status-' + result.status;
                icon.textContent = result.status === 'success' ? '✅' : (result.status === 'warning' ? '⚠️' : '❌');

                element.title = result.message;

            } catch (err) {
                icon.className = 'status-icon status-error';
                icon.textContent = '❌';
                element.title = '체크 실패: ' + err.message;
            }

            updateStats();
        }

        // Phase 전체 체크
        async function checkAllInPhase(button) {
            const card = button.closest('.card');
            const items = card.querySelectorAll('.module-item');

            button.disabled = true;
            button.innerHTML = '<span class="loading"></span> 체크 중...';

            for (const item of items) {
                await checkModule(item);
                await new Promise(resolve => setTimeout(resolve, 200)); // 딜레이
            }

            button.disabled = false;
            button.textContent = '📋 전체 체크';
        }

        // Quantum 테스트 실행
        async function runQuantumTest() {
            const statusBadge = document.getElementById('quantum-status');
            const outputBox = document.getElementById('quantum-output');

            statusBadge.className = 'badge badge-warning';
            statusBadge.textContent = '실행 중...';
            outputBox.classList.add('show');
            outputBox.textContent = '⏳ Quantum 테스트 실행 중...\n';

            try {
                const response = await fetch('?action=run_quantum_test');
                const result = await response.json();

                statusBadge.className = 'badge badge-' + result.status;
                statusBadge.textContent = result.status === 'success' ? '통과' : '실패';

                outputBox.textContent = result.output;

            } catch (err) {
                statusBadge.className = 'badge badge-error';
                statusBadge.textContent = '에러';
                outputBox.textContent = '❌ 실행 실패: ' + err.message;
            }
        }

        // 🎬 시나리오 실행 함수
        async function runScenario(scenarioId) {
            const outputBox = document.getElementById('scenario-output');
            const card = document.querySelector(`.scenario-card[data-scenario="${scenarioId}"]`);

            // 선택된 카드 하이라이트
            document.querySelectorAll('.scenario-card').forEach(c => {
                c.style.borderColor = '#30363d';
                c.style.transform = 'scale(1)';
            });
            card.style.borderColor = '#6366f1';
            card.style.transform = 'scale(1.02)';

            outputBox.style.display = 'block';
            outputBox.innerHTML = `<div style="color: #aaa;">⏳ 시나리오 ${scenarioId} 실행 중...</div>`;

            try {
                const response = await fetch(`?action=run_scenario&id=${scenarioId}&t=${Date.now()}`);
                const text = await response.text();

                // JSON 파싱 시도
                let result;
                try {
                    result = JSON.parse(text);
                } catch (parseErr) {
                    // JSON 파싱 실패 시 원본 응답 표시
                    outputBox.innerHTML = `<div style="color: #ef4444;">
                        ❌ JSON 파싱 실패 [pocdashboard.php:runScenario]<br>
                        <small style="color: #888;">응답 길이: ${text.length}자</small><br>
                        <pre style="font-size: 11px; max-height: 200px; overflow: auto; background: #1a1a2e; padding: 10px; margin-top: 10px;">${text.substring(0, 500)}${text.length > 500 ? '...' : ''}</pre>
                    </div>`;
                    console.error('Raw response:', text);
                    return;
                }

                if (result.error) {
                    outputBox.innerHTML = `<div style="color: #ef4444;">❌ Error: ${result.error}<br><small>${result.output || ''}</small></div>`;
                    return;
                }

                // 결과 포맷팅
                outputBox.innerHTML = formatScenarioResult(result);

            } catch (err) {
                outputBox.innerHTML = `<div style="color: #ef4444;">❌ 네트워크 오류 [pocdashboard.php:runScenario]: ${err.message}</div>`;
            }
        }

        // 전체 시나리오 실행
        async function runAllScenarios() {
            const outputBox = document.getElementById('scenario-output');
            outputBox.style.display = 'block';
            outputBox.innerHTML = '<div style="color: #aaa;">⏳ 전체 시나리오 실행 중 (1~7)...</div>';

            let allResults = '';

            for (let i = 1; i <= 7; i++) {
                const card = document.querySelector(`.scenario-card[data-scenario="${i}"]`);
                card.style.borderColor = '#f59e0b';

                try {
                    const response = await fetch(`?action=run_scenario&id=${i}&t=${Date.now()}`);
                    const text = await response.text();

                    let result;
                    try {
                        result = JSON.parse(text);
                    } catch (parseErr) {
                        allResults += `<div style="color: #ef4444; padding: 10px; border-bottom: 2px solid #30363d; margin-bottom: 20px;">
                            ❌ 시나리오 ${i} JSON 파싱 실패<br>
                            <pre style="font-size: 10px; max-height: 100px; overflow: auto;">${text.substring(0, 200)}</pre>
                        </div>`;
                        card.style.borderColor = '#ef4444';
                        continue;
                    }

                    allResults += formatScenarioResult(result);

                    card.style.borderColor = '#22c55e';

                } catch (err) {
                    allResults += `<div style="color: #ef4444;">❌ 시나리오 ${i} 네트워크 오류: ${err.message}</div>`;
                    card.style.borderColor = '#ef4444';
                }

                await new Promise(r => setTimeout(r, 300));
            }

            outputBox.innerHTML = allResults;
        }

        // 🎨 울트라 컴팩트 시나리오 결과 포맷팅
        function formatScenarioResult(result) {
            const levelColors = { 'LOW': '#22c55e', 'MEDIUM': '#f59e0b', 'HIGH': '#f97316', 'CRITICAL': '#ef4444' };
            const levelEmoji = { 'LOW': '🟢', 'MEDIUM': '🟡', 'HIGH': '🟠', 'CRITICAL': '🔴' };
            const levelColor = levelColors[result.intervention_level] || '#888';
            const emoji = levelEmoji[result.intervention_level] || '⚪';

            const ctx = result.context || {};
            const contextBadges = [
                ctx.student_id && `<span style="background:#1e293b;padding:1px 5px;border-radius:3px;margin-right:4px;">ID:${ctx.student_id}</span>`,
                ctx.weekly_goal_achievement !== undefined && `<span style="background:#166534;padding:1px 5px;border-radius:3px;margin-right:4px;">목표:${ctx.weekly_goal_achievement}%</span>`,
                ctx.current_stage !== undefined && `<span style="background:#4c1d95;padding:1px 5px;border-radius:3px;margin-right:4px;">S${ctx.current_stage}</span>`,
                ctx.vulnerability_detected && `<span style="background:#991b1b;padding:1px 5px;border-radius:3px;margin-right:4px;">⚠️취약</span>`,
                ctx.is_new_student && `<span style="background:#1d4ed8;padding:1px 5px;border-radius:3px;margin-right:4px;">🆕신규</span>`,
                ctx.goal_mismatch && `<span style="background:#92400e;padding:1px 5px;border-radius:3px;margin-right:4px;">🎯불일치</span>`
            ].filter(Boolean).join('');

            const signal = result.quantum_signal || (result.quantum_signals?.[0]) || {};
            const amp = signal.amplitude || 0;
            const phase = signal.phase_deg || 0;
            const interference = result.interference_result || {};
            const totalAmp = interference.total_amplitude || amp;
            const efficiency = interference.efficiency || '100%';

            let html = `<div style="background:#12151a;border-radius:6px;padding:8px 10px;margin-bottom:6px;border-left:3px solid ${levelColor};">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                    <span style="font-size:13px;font-weight:bold;color:#e0e0e0;">${emoji} #${result.scenario_id} ${result.scenario_name || ''}</span>
                    <span style="background:${levelColor}22;border:1px solid ${levelColor};padding:2px 8px;border-radius:10px;font-size:10px;font-weight:bold;color:${levelColor};">${result.intervention_level}</span>
                </div>
                <div style="font-size:10px;color:#9ca3af;margin-bottom:6px;">${contextBadges || 'No context'}</div>
                <div style="display:flex;align-items:center;gap:6px;background:#0d1117;padding:6px 8px;border-radius:4px;margin-bottom:6px;">
                    <span style="color:#6366f1;font-size:10px;">⚡${amp.toFixed(2)}</span>
                    <span style="color:#4a5568;">→</span>
                    <span style="color:#8b5cf6;font-size:10px;">🌀${phase}°</span>
                    <span style="color:#4a5568;">→</span>
                    <span style="color:#22c55e;font-size:12px;font-weight:bold;">🌊${typeof totalAmp === 'number' ? totalAmp.toFixed(2) : totalAmp}</span>
                    <span style="color:${levelColor};font-size:9px;margin-left:auto;">${efficiency}</span>
                    <div style="width:50px;height:4px;background:#333;border-radius:2px;overflow:hidden;"><div style="height:100%;width:${Math.round(amp*100)}%;background:linear-gradient(90deg,#22c55e,#f59e0b);"></div></div>
                </div>`;

            // Multi-agent (인라인)
            if (result.quantum_signals?.length > 1) {
                html += `<div style="font-size:9px;color:#f59e0b;margin-bottom:4px;">🔀 ${result.quantum_signals.map(s => `A${s.agent_id}:${s.amplitude}∠${s.phase_deg}°`).join(' ⊕ ')}</div>`;
            }

            // 추천 액션 (인라인)
            if (result.recommended_action) {
                html += `<div style="font-size:11px;"><span style="color:#f59e0b;">🎯 ${result.recommended_action.action}</span> <span style="color:#6b7280;">${result.recommended_action.message}</span></div>`;
            } else if (result.recommended_actions) {
                html += `<div style="font-size:10px;">🎯 ${result.recommended_actions.map(a => `<span style="color:#8b5cf6;">${a.agent}</span>:<span style="color:#f59e0b;">${a.action}</span>`).join(' │ ')}</div>`;
            }

            // 복합 인사이트 (있으면)
            if (result.combined_insight) {
                html += `<div style="font-size:10px;color:#c4b5fd;margin-top:4px;padding-left:8px;border-left:2px solid #8b5cf6;">💡 ${result.combined_insight}</div>`;
            }

            // 🔮 시나리오 7: 가중치 붕괴 특수 표시
            if (result.scenario_id === 7 && result.wave_function_collapse) {
                const collapse = result.wave_function_collapse;
                const stagnation = result.stagnation_analysis || {};
                const temporal = result.temporal_chain_effect || {};
                const superposition = result.quantum_superposition || {};
                
                html += `<div style="background:#1a0f2e;border-radius:6px;padding:10px;margin-top:8px;border:1px solid #7c3aed;">
                    <div style="font-size:11px;font-weight:bold;color:#c4b5fd;margin-bottom:8px;">🔮 파동함수 붕괴 분석</div>
                    
                    <!-- 정체 분석 -->
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px;">
                        <div style="background:#0f0a1a;padding:6px;border-radius:4px;text-align:center;">
                            <div style="font-size:9px;color:#a78bfa;">정체 점수</div>
                            <div style="font-size:14px;font-weight:bold;color:#e9d5ff;">${stagnation.stagnation_score || 'N/A'}</div>
                        </div>
                        <div style="background:#0f0a1a;padding:6px;border-radius:4px;text-align:center;">
                            <div style="font-size:9px;color:#a78bfa;">분산</div>
                            <div style="font-size:14px;font-weight:bold;color:#e9d5ff;">${stagnation.variance || 'N/A'}</div>
                        </div>
                        <div style="background:#0f0a1a;padding:6px;border-radius:4px;text-align:center;">
                            <div style="font-size:9px;color:#a78bfa;">추세</div>
                            <div style="font-size:14px;font-weight:bold;color:${(stagnation.trend || 0) < 0 ? '#ef4444' : '#22c55e'};">${stagnation.trend || 'N/A'}</div>
                        </div>
                    </div>
                    
                    <!-- 중첩 상태 시각화 -->
                    <div style="margin-bottom:10px;">
                        <div style="font-size:10px;color:#a78bfa;margin-bottom:6px;">🌊 중첩 상태 (${superposition.total_scenarios || 3}개 시나리오, 엔트로피: ${superposition.entropy_bits || 'N/A'} bits)</div>
                        ${result.quantum_signals ? result.quantum_signals.map(s => {
                            const barColor = s.scenario === 'rebound' ? '#22c55e' : s.scenario === 'decline' ? '#ef4444' : '#3b82f6';
                            return `<div style="display:flex;align-items:center;gap:6px;margin-bottom:4px;">
                                <span style="font-size:9px;color:#9ca3af;width:60px;">${s.scenario_name || s.scenario}</span>
                                <div style="flex:1;height:12px;background:#1e1b4b;border-radius:3px;overflow:hidden;">
                                    <div style="height:100%;width:${s.probability || 0}%;background:${barColor};"></div>
                                </div>
                                <span style="font-size:10px;color:${barColor};font-weight:bold;width:40px;text-align:right;">${s.probability || 0}%</span>
                            </div>`;
                        }).join('') : ''}
                    </div>
                    
                    <!-- 붕괴 결과 -->
                    <div style="background:#2d1f4d;border-radius:6px;padding:10px;border:1px dashed #a855f7;">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <span style="font-size:11px;font-weight:bold;color:#e9d5ff;">💥 붕괴 결과: ${collapse.collapsed_scenario_name}</span>
                            <span style="background:#7c3aed;padding:2px 8px;border-radius:10px;font-size:10px;color:white;">${(collapse.collapse_probability * 100).toFixed(0)}%</span>
                        </div>
                        <div style="font-size:10px;color:#c4b5fd;margin-bottom:6px;">${collapse.collapse_reasoning || ''}</div>
                        <div style="display:flex;gap:10px;font-size:9px;color:#9ca3af;">
                            <span>📍 현재: ${temporal.current_state || 'N/A'}</span>
                            <span style="color:#ef4444;">⚠️ 무개입: ${temporal.if_no_intervention || 'N/A'}</span>
                            <span style="color:#22c55e;">✅ 개입 시: ${temporal.if_intervention || 'N/A'}</span>
                        </div>
                    </div>
                </div>`;
                
                // Quantum 인사이트
                if (result.quantum_insight) {
                    html += `<div style="font-size:10px;color:#fbbf24;margin-top:8px;padding:8px;background:#1f1a0a;border-radius:4px;border-left:3px solid #f59e0b;">
                        ${result.quantum_insight}
                    </div>`;
                }
            }

            // 🌊 파동 간섭 및 확률함수 시각화 추가
            html += generateVisualization(result, result.scenario_id);

            html += `</div>`;
            return html;
        }
    </script>
</body>
</html>
