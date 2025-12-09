<?php
/**
 * A/B Testing Dashboard - Phase 11.1
 * ===================================
 * 양자 모델 vs 기존 모델 A/B 테스트 결과 시각화 대시보드
 * 실제 데이터베이스 연동 버전
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/ab_testing_dashboard.php
 *
 * 기능:
 *   1. 테스트 개요 (샘플 크기, 그룹 분포)
 *   2. 메트릭 비교 차트 (Control vs Treatment)
 *   3. 통계 분석 결과 (p-value, Cohen's d)
 *   4. 권장 사항 패널
 *   5. 실시간 데이터베이스 연동
 *
 * API 엔드포인트:
 *   ?action=dashboard      - 대시보드 UI (기본)
 *   ?action=overview       - 테스트 개요 JSON
 *   ?action=metrics        - 메트릭 비교 JSON
 *   ?action=report         - 전체 분석 보고서 JSON
 *
 * @file    ab_testing_dashboard.php
 * @package QuantumOrchestration
 * @phase   11.1
 * @version 2.0.0
 * @created 2025-12-09
 * @updated 2025-12-09
 *
 * Database Tables:
 *   - mdl_quantum_ab_tests (group assignments)
 *   - mdl_quantum_ab_test_outcomes (learning metrics)
 *   - mdl_quantum_ab_test_state_changes (8D StateVector)
 *   - mdl_quantum_ab_test_reports (cached reports)
 *   - mdl_quantum_ab_test_config (test configuration)
 */

// =============================================================================
// Moodle 통합
// =============================================================================
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 에러 표시 설정
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database Functions 로드
$dbFunctionsPath = __DIR__ . '/db/db_functions.php';
$useDatabase = false;
$dbError = null;

if (file_exists($dbFunctionsPath)) {
    include_once($dbFunctionsPath);
    $useDatabase = true;
} else {
    $dbError = "db_functions.php not found at: " . $dbFunctionsPath;
}

// A/B Testing Bridge 로드 (레거시 호환)
if (file_exists(__DIR__ . '/ab_testing_bridge.php')) {
    include_once(__DIR__ . '/ab_testing_bridge.php');
}

// =============================================================================
// API 요청 처리
// =============================================================================
$action = $_GET['action'] ?? 'dashboard';
$testId = $_GET['test_id'] ?? 'quantum_v1';
$format = $_GET['format'] ?? 'html';
$forceSimulation = isset($_GET['simulation']);

// =============================================================================
// 데이터 소스 결정 (DB vs Simulation)
// =============================================================================
$dataSource = 'simulation';
$hasDbData = false;

if ($useDatabase && !$forceSimulation) {
    try {
        // DB에 테이블이 있는지 확인
        $tableExists = $DB->get_manager()->table_exists('quantum_ab_tests');

        if ($tableExists) {
            $counts = quantum_ab_get_group_counts($testId);
            if ($counts->total > 0) {
                $hasDbData = true;
                $dataSource = 'database';
            }
        }
    } catch (Exception $e) {
        $dbError = "DB Error: " . $e->getMessage() . " (Line: " . __LINE__ . ")";
    }
}

// =============================================================================
// 시뮬레이션 함수 (DB가 비어있을 때 폴백)
// =============================================================================

/**
 * 테스트 데이터 시뮬레이션 (DB가 비어있을 때만 사용)
 */
function getSimulationTestData($testId) {
    // 고정 시드로 일관된 시뮬레이션 데이터 생성
    mt_srand(42);

    $controlData = [];
    $treatmentData = [];

    // 60명 Control, 40명 Treatment 시뮬레이션
    for ($i = 0; $i < 60; $i++) {
        $controlData[] = [
            'learning_gain' => 0.08 + (mt_rand(0, 60) / 1000),
            'engagement_rate' => 0.65 + (mt_rand(0, 100) / 1000),
            'effectiveness_score' => 0.68 + (mt_rand(0, 80) / 1000)
        ];
    }

    for ($i = 0; $i < 40; $i++) {
        $treatmentData[] = [
            'learning_gain' => 0.13 + (mt_rand(0, 60) / 1000),
            'engagement_rate' => 0.78 + (mt_rand(0, 80) / 1000),
            'effectiveness_score' => 0.78 + (mt_rand(0, 60) / 1000)
        ];
    }

    return [
        'test_id' => $testId,
        'control' => $controlData,
        'treatment' => $treatmentData,
        'created_at' => '2025-12-01',
        'status' => 'simulation'
    ];
}

/**
 * 시뮬레이션 데이터 통계 분석
 */
function analyzeSimulationMetrics($testData) {
    $metrics = ['learning_gain', 'engagement_rate', 'effectiveness_score'];
    $results = [];

    foreach ($metrics as $metric) {
        $controlValues = array_column($testData['control'], $metric);
        $treatmentValues = array_column($testData['treatment'], $metric);

        $controlMean = array_sum($controlValues) / count($controlValues);
        $treatmentMean = array_sum($treatmentValues) / count($treatmentValues);

        $controlStd = calculateSimStd($controlValues);
        $treatmentStd = calculateSimStd($treatmentValues);

        // Cohen's d
        $pooledStd = sqrt((pow($controlStd, 2) + pow($treatmentStd, 2)) / 2);
        $cohensD = $pooledStd > 0 ? abs($treatmentMean - $controlMean) / $pooledStd : 0;

        // Effect size interpretation
        $effectSize = 'negligible';
        if ($cohensD >= 0.8) $effectSize = 'large';
        elseif ($cohensD >= 0.5) $effectSize = 'medium';
        elseif ($cohensD >= 0.2) $effectSize = 'small';

        // Simple t-test approximation
        $n1 = count($controlValues);
        $n2 = count($treatmentValues);
        $se = sqrt(($controlStd * $controlStd / $n1) + ($treatmentStd * $treatmentStd / $n2));
        $t = $se > 0 ? ($treatmentMean - $controlMean) / $se : 0;

        // P-value approximation
        $df = $n1 + $n2 - 2;
        $pValue = approximateSimPValue($t, $df);

        $results[$metric] = [
            'control' => [
                'mean' => round($controlMean * 100, 2),
                'std' => round($controlStd * 100, 2),
                'n' => $n1
            ],
            'treatment' => [
                'mean' => round($treatmentMean * 100, 2),
                'std' => round($treatmentStd * 100, 2),
                'n' => $n2
            ],
            'difference' => round(($treatmentMean - $controlMean) * 100, 2),
            'cohens_d' => round($cohensD, 3),
            'effect_size' => $effectSize,
            'p_value' => $pValue,
            'significant' => $pValue < 0.05
        ];
    }

    return $results;
}

function calculateSimStd($arr) {
    $n = count($arr);
    if ($n < 2) return 0;
    $mean = array_sum($arr) / $n;
    $sumSquares = 0;
    foreach ($arr as $val) {
        $sumSquares += pow($val - $mean, 2);
    }
    return sqrt($sumSquares / ($n - 1));
}

function approximateSimPValue($t, $df) {
    $absT = abs($t);
    if ($absT > 3.5) return 0.001;
    if ($absT > 2.576) return 0.01;
    if ($absT > 1.96) return 0.05;
    if ($absT > 1.645) return 0.1;
    return 0.5;
}

/**
 * 시뮬레이션 권장 사항 생성
 */
function getSimulationRecommendation($analysisResults) {
    $largeEffects = 0;
    $significantMetrics = 0;

    foreach ($analysisResults as $metric => $result) {
        if ($result['effect_size'] === 'large') $largeEffects++;
        if ($result['significant']) $significantMetrics++;
    }

    if ($largeEffects >= 2 && $significantMetrics >= 2) {
        return [
            'action' => 'ADOPT',
            'color' => '#238636',
            'icon' => '✅',
            'message' => '양자 모델이 유의미한 개선을 보입니다. 전체 적용을 권장합니다.',
            'confidence' => 'high'
        ];
    } elseif ($largeEffects >= 1 || $significantMetrics >= 1) {
        return [
            'action' => 'CONTINUE',
            'color' => '#f0883e',
            'icon' => '🔄',
            'message' => '일부 개선이 관찰됩니다. 추가 데이터 수집을 권장합니다.',
            'confidence' => 'medium'
        ];
    } else {
        return [
            'action' => 'REJECT',
            'color' => '#f85149',
            'icon' => '❌',
            'message' => '유의미한 개선이 관찰되지 않습니다. 기존 모델 유지를 권장합니다.',
            'confidence' => 'low'
        ];
    }
}

// =============================================================================
// 데이터 로드 및 분석 (DB 또는 Simulation)
// =============================================================================
$controlSize = 0;
$treatmentSize = 0;
$totalSize = 0;
$analysisResults = [];
$recommendation = [];
$testConfig = null;

if ($dataSource === 'database') {
    // 실제 데이터베이스에서 데이터 로드
    try {
        $counts = quantum_ab_get_group_counts($testId);
        $controlSize = $counts->control_count;
        $treatmentSize = $counts->treatment_count;
        $totalSize = $counts->total;

        // 테스트 설정 로드
        $testConfig = quantum_ab_get_test_config($testId);

        // 메트릭 분석
        $targetMetrics = $testConfig && $testConfig->target_metrics
            ? $testConfig->target_metrics
            : ['learning_gain', 'engagement_rate', 'effectiveness_score'];

        foreach ($targetMetrics as $metricName) {
            $stats = quantum_ab_calculate_statistics($testId, $metricName);

            if ($stats['control'] && $stats['treatment'] && $stats['analysis']) {
                $analysisResults[$metricName] = [
                    'control' => [
                        'mean' => $stats['control']->mean,
                        'std' => $stats['control']->std,
                        'n' => $stats['control']->n
                    ],
                    'treatment' => [
                        'mean' => $stats['treatment']->mean,
                        'std' => $stats['treatment']->std,
                        'n' => $stats['treatment']->n
                    ],
                    'difference' => $stats['analysis']['difference'],
                    'cohens_d' => $stats['analysis']['cohens_d'],
                    'effect_size' => $stats['analysis']['effect_size'],
                    'p_value' => $stats['analysis']['p_value'],
                    'significant' => $stats['analysis']['significant']
                ];
            }
        }

        // 권장 사항 생성
        $dbRecommendation = quantum_ab_generate_recommendation($testId);
        $recommendation = [
            'action' => $dbRecommendation['action'],
            'color' => $dbRecommendation['action'] === 'ADOPT' ? '#238636' :
                      ($dbRecommendation['action'] === 'CONTINUE' ? '#f0883e' : '#f85149'),
            'icon' => $dbRecommendation['action'] === 'ADOPT' ? '✅' :
                     ($dbRecommendation['action'] === 'CONTINUE' ? '🔄' : '❌'),
            'message' => $dbRecommendation['message'],
            'confidence' => $dbRecommendation['confidence']
        ];

    } catch (Exception $e) {
        // DB 오류 시 시뮬레이션으로 폴백
        $dataSource = 'simulation';
        $dbError = "DB Query Error: " . $e->getMessage() . " (Line: " . __LINE__ . ")";
    }
}

// 시뮬레이션 모드 (DB 없거나 빈 경우)
if ($dataSource === 'simulation') {
    $testData = getSimulationTestData($testId);
    $analysisResults = analyzeSimulationMetrics($testData);
    $recommendation = getSimulationRecommendation($analysisResults);

    $controlSize = count($testData['control']);
    $treatmentSize = count($testData['treatment']);
    $totalSize = $controlSize + $treatmentSize;
}

// JSON API 응답
if ($format === 'json' || in_array($action, ['overview', 'metrics', 'report'])) {
    header('Content-Type: application/json; charset=utf-8');

    // 상태 및 생성일 결정 (DB mode: $testConfig, Simulation mode: 기본값)
    $testStatus = ($testConfig && isset($testConfig->status)) ? $testConfig->status : 'active';
    $testCreated = ($testConfig && isset($testConfig->timecreated))
        ? date('Y-m-d H:i:s', $testConfig->timecreated)
        : date('Y-m-d H:i:s');

    $result = null;
    switch ($action) {
        case 'overview':
            $result = [
                'test_id' => $testId,
                'data_source' => $dataSource,
                'control_size' => $controlSize,
                'treatment_size' => $treatmentSize,
                'total_size' => $totalSize,
                'status' => $testStatus,
                'created_at' => $testCreated
            ];
            break;
        case 'metrics':
            $result = [
                'test_id' => $testId,
                'data_source' => $dataSource,
                'metrics' => $analysisResults
            ];
            break;
        case 'report':
            $result = [
                'test_id' => $testId,
                'data_source' => $dataSource,
                'overview' => [
                    'control_size' => $controlSize,
                    'treatment_size' => $treatmentSize,
                    'total_size' => $totalSize,
                    'status' => $testStatus
                ],
                'metrics' => $analysisResults,
                'recommendation' => $recommendation
            ];
            break;
        default:
            $result = ['error' => 'Unknown action', 'available_actions' => ['overview', 'metrics', 'report']];
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 변수들은 이미 위에서 데이터 소스에 따라 설정됨 (lines 258-333)
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>A/B Testing Dashboard - Phase 11.1</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #0d1117 0%, #161b22 50%, #1a1f2c 100%);
            color: #c9d1d9;
            min-height: 100vh;
        }

        .dashboard {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #30363d;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            color: #58a6ff;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header h1::before {
            content: '🧪';
            font-size: 28px;
        }

        .phase-badge {
            background: linear-gradient(135deg, #a371f7 0%, #8957e5 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .data-source-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            margin-left: 10px;
        }

        .data-source-badge.db-mode {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            color: white;
        }

        .data-source-badge.sim-mode {
            background: linear-gradient(135deg, #f0883e 0%, #d29922 100%);
            color: #1a1f2c;
        }

        .nav-links {
            display: flex;
            gap: 15px;
        }

        .nav-links a {
            color: #8b949e;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .nav-links a:hover {
            background: #21262d;
            color: #58a6ff;
        }

        /* Grid Layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 20px;
        }

        .card {
            background: rgba(22, 27, 34, 0.9);
            border: 1px solid #30363d;
            border-radius: 12px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #21262d;
        }

        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #c9d1d9;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            background: #21262d;
            color: #7ee787;
        }

        /* Overview Card */
        .overview-card { grid-column: span 4; }

        .stat-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .stat-item {
            background: #21262d;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-value {
            font-size: 28px;
            font-weight: bold;
            color: #58a6ff;
        }

        .stat-value.control { color: #f0883e; }
        .stat-value.treatment { color: #7ee787; }

        .stat-label {
            font-size: 12px;
            color: #8b949e;
            margin-top: 5px;
        }

        /* Distribution Card */
        .distribution-card { grid-column: span 4; }

        .distribution-bar {
            height: 40px;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            margin: 20px 0;
        }

        .distribution-bar .control {
            background: linear-gradient(90deg, #f0883e, #d77b31);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .distribution-bar .treatment {
            background: linear-gradient(90deg, #238636, #2ea043);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .distribution-legend {
            display: flex;
            justify-content: space-around;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .legend-dot.control { background: #f0883e; }
        .legend-dot.treatment { background: #238636; }

        /* Recommendation Card */
        .recommendation-card { grid-column: span 4; }

        .recommendation-box {
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .recommendation-box.adopt {
            background: rgba(35, 134, 54, 0.2);
            border: 2px solid #238636;
        }

        .recommendation-box.continue {
            background: rgba(240, 136, 62, 0.2);
            border: 2px solid #f0883e;
        }

        .recommendation-box.reject {
            background: rgba(248, 81, 73, 0.2);
            border: 2px solid #f85149;
        }

        .recommendation-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        .recommendation-action {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .recommendation-message {
            font-size: 14px;
            color: #8b949e;
        }

        /* Metrics Chart Card */
        .metrics-card { grid-column: span 8; }

        .chart-container {
            position: relative;
            height: 300px;
        }

        /* Results Table Card */
        .results-card { grid-column: span 4; }

        .results-table {
            width: 100%;
            border-collapse: collapse;
        }

        .results-table th,
        .results-table td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #21262d;
        }

        .results-table th {
            font-size: 11px;
            color: #8b949e;
            text-transform: uppercase;
        }

        .results-table td {
            font-size: 13px;
        }

        .effect-badge {
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .effect-badge.large {
            background: rgba(126, 231, 135, 0.2);
            color: #7ee787;
        }

        .effect-badge.medium {
            background: rgba(240, 136, 62, 0.2);
            color: #f0883e;
        }

        .effect-badge.small {
            background: rgba(139, 148, 158, 0.2);
            color: #8b949e;
        }

        .significant {
            color: #7ee787;
        }

        .not-significant {
            color: #8b949e;
        }

        /* Detail Charts */
        .detail-card { grid-column: span 4; }

        .metric-detail {
            margin-bottom: 20px;
        }

        .metric-name {
            font-size: 13px;
            color: #8b949e;
            margin-bottom: 10px;
        }

        .metric-bars {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .metric-bar {
            height: 30px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .metric-bar.control {
            background: linear-gradient(90deg, #f0883e, #d77b31);
        }

        .metric-bar.treatment {
            background: linear-gradient(90deg, #238636, #2ea043);
        }

        /* Footer */
        .footer {
            text-align: center;
            padding: 30px 0;
            color: #8b949e;
            font-size: 12px;
            margin-top: 30px;
            border-top: 1px solid #30363d;
        }

        .footer a {
            color: #58a6ff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="header">
            <h1>A/B Testing Dashboard <span class="phase-badge">Phase 11.1</span>
                <span class="data-source-badge <?= $dataSource === 'database' ? 'db-mode' : 'sim-mode' ?>">
                    <?= $dataSource === 'database' ? '🗄️ DB Mode' : '🎲 Simulation' ?>
                </span>
            </h1>
            <div class="nav-links">
                <a href="quantum_monitoring_dashboard.php">🔮 Quantum Dashboard</a>
                <a href="?action=report&format=json">📊 API</a>
                <?php if ($dataSource === 'simulation'): ?>
                <a href="db/db_install.php" title="Install database tables">⚙️ Install DB</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Overview Row -->
        <div class="grid">
            <!-- Overview Card -->
            <div class="card overview-card">
                <div class="card-header">
                    <span class="card-title">📈 Test Overview</span>
                    <span class="card-badge"><?= htmlspecialchars($testId) ?></span>
                </div>
                <div class="stat-grid">
                    <div class="stat-item">
                        <div class="stat-value"><?= $totalSize ?></div>
                        <div class="stat-label">Total Participants</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value control"><?= $controlSize ?></div>
                        <div class="stat-label">Control Group</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value treatment"><?= $treatmentSize ?></div>
                        <div class="stat-label">Treatment Group</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?= count($analysisResults) ?></div>
                        <div class="stat-label">Metrics Analyzed</div>
                    </div>
                </div>
            </div>

            <!-- Distribution Card -->
            <div class="card distribution-card">
                <div class="card-header">
                    <span class="card-title">📊 Group Distribution</span>
                    <span class="card-badge">50/50 Target</span>
                </div>
                <div class="distribution-bar">
                    <div class="control" style="width: <?= round($controlSize / $totalSize * 100) ?>%">
                        <?= round($controlSize / $totalSize * 100) ?>%
                    </div>
                    <div class="treatment" style="width: <?= round($treatmentSize / $totalSize * 100) ?>%">
                        <?= round($treatmentSize / $totalSize * 100) ?>%
                    </div>
                </div>
                <div class="distribution-legend">
                    <div class="legend-item">
                        <span class="legend-dot control"></span>
                        <span>Control (기존 모델)</span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot treatment"></span>
                        <span>Treatment (양자 모델)</span>
                    </div>
                </div>
            </div>

            <!-- Recommendation Card -->
            <div class="card recommendation-card">
                <div class="card-header">
                    <span class="card-title">🎯 Recommendation</span>
                    <span class="card-badge"><?= $recommendation['confidence'] ?> confidence</span>
                </div>
                <div class="recommendation-box <?= strtolower($recommendation['action']) ?>">
                    <div class="recommendation-icon"><?= $recommendation['icon'] ?></div>
                    <div class="recommendation-action" style="color: <?= $recommendation['color'] ?>">
                        <?= $recommendation['action'] ?>
                    </div>
                    <div class="recommendation-message">
                        <?= $recommendation['message'] ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Metrics Row -->
        <div class="grid" style="margin-top: 20px;">
            <!-- Metrics Comparison Chart -->
            <div class="card metrics-card">
                <div class="card-header">
                    <span class="card-title">📊 Metrics Comparison</span>
                    <span class="card-badge">Control vs Treatment</span>
                </div>
                <div class="chart-container">
                    <canvas id="metricsChart"></canvas>
                </div>
            </div>

            <!-- Statistical Results -->
            <div class="card results-card">
                <div class="card-header">
                    <span class="card-title">📋 Statistical Results</span>
                </div>
                <table class="results-table">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th>Effect</th>
                            <th>p-value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($analysisResults as $metric => $result): ?>
                        <tr>
                            <td><?= ucfirst(str_replace('_', ' ', $metric)) ?></td>
                            <td>
                                <span class="effect-badge <?= $result['effect_size'] ?>">
                                    <?= $result['cohens_d'] ?> (<?= $result['effect_size'] ?>)
                                </span>
                            </td>
                            <td class="<?= $result['significant'] ? 'significant' : 'not-significant' ?>">
                                <?= $result['significant'] ? '✓ p<0.05' : 'n.s.' ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Detail Charts Row -->
        <div class="grid" style="margin-top: 20px;">
            <?php foreach ($analysisResults as $metric => $result): ?>
            <div class="card detail-card">
                <div class="card-header">
                    <span class="card-title"><?= ucfirst(str_replace('_', ' ', $metric)) ?></span>
                    <span class="card-badge effect-badge <?= $result['effect_size'] ?>">
                        d=<?= $result['cohens_d'] ?>
                    </span>
                </div>
                <div class="metric-detail">
                    <div class="metric-name">Control (기존 모델)</div>
                    <div class="metric-bars">
                        <div class="metric-bar control" style="width: <?= min($result['control']['mean'], 100) ?>%">
                            <?= $result['control']['mean'] ?>%
                        </div>
                    </div>
                </div>
                <div class="metric-detail">
                    <div class="metric-name">Treatment (양자 모델)</div>
                    <div class="metric-bars">
                        <div class="metric-bar treatment" style="width: <?= min($result['treatment']['mean'], 100) ?>%">
                            <?= $result['treatment']['mean'] ?>%
                        </div>
                    </div>
                </div>
                <div style="text-align: center; margin-top: 15px; color: <?= $result['difference'] > 0 ? '#7ee787' : '#f85149' ?>">
                    <?= $result['difference'] > 0 ? '+' : '' ?><?= $result['difference'] ?>% 차이
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>A/B Testing Dashboard - Phase 11.1 | Database Integration Complete</p>
            <p>
                <a href="PHASE9_COMPLETION_REPORT.md">Documentation</a> |
                <a href="test_ab_testing_integration.php?run_test=1">Run Tests</a> |
                <a href="?action=report&format=json">API Reference</a>
            </p>
        </div>
    </div>

    <script>
        // Metrics Comparison Chart
        const ctx = document.getElementById('metricsChart').getContext('2d');
        const metricsData = <?= json_encode($analysisResults) ?>;

        const labels = Object.keys(metricsData).map(key =>
            key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())
        );
        const controlData = Object.values(metricsData).map(m => m.control.mean);
        const treatmentData = Object.values(metricsData).map(m => m.treatment.mean);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Control (기존 모델)',
                        data: controlData,
                        backgroundColor: 'rgba(240, 136, 62, 0.7)',
                        borderColor: '#f0883e',
                        borderWidth: 2,
                        borderRadius: 6
                    },
                    {
                        label: 'Treatment (양자 모델)',
                        data: treatmentData,
                        backgroundColor: 'rgba(35, 134, 54, 0.7)',
                        borderColor: '#238636',
                        borderWidth: 2,
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#c9d1d9',
                            font: { size: 12 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#21262d' },
                        ticks: {
                            color: '#8b949e',
                            callback: value => value + '%'
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: '#8b949e' }
                    }
                }
            }
        });
    </script>
</body>
</html>
