<?php
/**
 * Quantum Monitoring Dashboard - Phase 8.2
 * ==========================================
 * 실시간 학생 상태 모니터링 대시보드
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/quantum_monitoring_dashboard.php
 *
 * 기능:
 *   1. 8D StateVector 실시간 시각화 (레이더 차트)
 *   2. 양자 오케스트레이터 에이전트 추천
 *   3. 상태 분석 및 개입 권장
 *   4. 22개 에이전트 얽힘 맵 시각화
 *
 * API 엔드포인트:
 *   ?action=dashboard       - 대시보드 UI (기본)
 *   ?action=state           - 현재 8D StateVector JSON
 *   ?action=recommendations - 에이전트 추천 JSON
 *   ?action=analysis        - 상태 분석 JSON
 *   ?action=entanglement    - 얽힘 맵 JSON
 *
 * @file    quantum_monitoring_dashboard.php
 * @package QuantumOrchestration
 * @phase   8.2
 * @version 1.0.0
 * @created 2025-12-09
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

// =============================================================================
// 독립형 브릿지 모듈 로드 (Phase 8.3)
// =============================================================================
/**
 * orchestrator_bridge.php 포함
 * - QuantumOrchestratorBridge 클래스 제공
 * - 22개 에이전트 정보, 8D 차원 정보 포함
 * - PHP-Python 브릿지 기능 제공
 */
include_once(__DIR__ . '/orchestrator_bridge.php');
// =============================================================================
// API 요청 처리
// =============================================================================
$action = $_GET['action'] ?? 'dashboard';
$userid = isset($_GET['userid']) ? (int)$_GET['userid'] : $USER->id;
$format = $_GET['format'] ?? 'html';

$bridge = new QuantumOrchestratorBridge($userid, true);

// JSON API 응답
if ($format === 'json' || in_array($action, ['state', 'recommendations', 'analysis', 'entanglement'])) {
    header('Content-Type: application/json; charset=utf-8');

    $result = null;
    switch ($action) {
        case 'state':
            $result = $bridge->getCurrentState();
            break;
        case 'recommendations':
            $triggered = isset($_GET['agents']) ? explode(',', $_GET['agents']) : [];
            $triggered = array_map('intval', $triggered);
            $result = $bridge->getRecommendations($triggered);
            break;
        case 'analysis':
            $result = $bridge->getStateAnalysis();
            break;
        case 'entanglement':
            $result = $bridge->getEntanglementMap();
            break;
        default:
            $result = ['error' => 'Unknown action', 'actions' => ['state', 'recommendations', 'analysis', 'entanglement']];
    }

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// 데이터 조회 (대시보드용)
$stateData = $bridge->getCurrentState();
$analysisData = $bridge->getStateAnalysis();
$recommendationsData = $bridge->getRecommendations([5, 8, 10, 12]);
$entanglementData = $bridge->getEntanglementMap();

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Monitoring Dashboard - Phase 8.2</title>
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
            content: '🔮';
            font-size: 28px;
        }

        .phase-badge {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #8b949e;
        }

        .user-info .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #58a6ff, #a371f7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
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

        /* StateVector Card */
        .state-card { grid-column: span 6; }

        .radar-container {
            position: relative;
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        /* Analysis Card */
        .analysis-card { grid-column: span 6; }

        .risk-indicator {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .risk-indicator.low { background: rgba(35, 134, 54, 0.2); border-left: 4px solid #238636; }
        .risk-indicator.medium { background: rgba(240, 136, 62, 0.2); border-left: 4px solid #f0883e; }
        .risk-indicator.high { background: rgba(248, 81, 73, 0.2); border-left: 4px solid #f85149; }

        .risk-icon {
            font-size: 32px;
        }

        .risk-details h3 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .risk-details p {
            font-size: 13px;
            color: #8b949e;
        }

        .analysis-section {
            margin-top: 20px;
        }

        .analysis-section h4 {
            font-size: 12px;
            color: #8b949e;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .tag-list {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .tag {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 12px;
        }

        .tag.strength { background: rgba(126, 231, 135, 0.2); color: #7ee787; }
        .tag.weakness { background: rgba(248, 81, 73, 0.2); color: #f85149; }

        /* Recommendations Card */
        .recommendations-card { grid-column: span 6; }

        .agent-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .agent-item {
            display: flex;
            align-items: center;
            padding: 12px;
            background: #21262d;
            border-radius: 8px;
            transition: transform 0.2s;
        }

        .agent-item:hover {
            transform: translateX(5px);
        }

        .agent-rank {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            margin-right: 15px;
        }

        .agent-rank.rank-1 { background: linear-gradient(135deg, #ffd700, #ffb347); color: #000; }
        .agent-rank.rank-2 { background: linear-gradient(135deg, #c0c0c0, #a8a8a8); color: #000; }
        .agent-rank.rank-3 { background: linear-gradient(135deg, #cd7f32, #b87333); color: #fff; }
        .agent-rank.rank-other { background: #30363d; color: #8b949e; }

        .agent-info {
            flex: 1;
        }

        .agent-name {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 3px;
        }

        .agent-id {
            font-size: 11px;
            color: #8b949e;
        }

        .agent-score {
            font-size: 16px;
            font-weight: bold;
            color: #58a6ff;
        }

        /* Dimensions Card */
        .dimensions-card { grid-column: span 6; }

        .dimension-bars {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .dimension-bar {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .dimension-label {
            width: 120px;
            font-size: 12px;
            color: #8b949e;
        }

        .dimension-track {
            flex: 1;
            height: 8px;
            background: #21262d;
            border-radius: 4px;
            overflow: hidden;
        }

        .dimension-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }

        .dimension-value {
            width: 45px;
            text-align: right;
            font-size: 13px;
            font-weight: 500;
        }

        /* Status Footer */
        .status-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #30363d;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #8b949e;
            font-size: 12px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-dot.active { background: #238636; }
        .status-dot.inactive { background: #f85149; }

        /* Refresh Button */
        .refresh-btn {
            padding: 8px 16px;
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 6px;
            color: #c9d1d9;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .refresh-btn:hover {
            background: #30363d;
            border-color: #58a6ff;
        }

        /* API Links */
        .api-links {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .api-link {
            font-size: 11px;
            color: #58a6ff;
            text-decoration: none;
        }

        .api-link:hover {
            text-decoration: underline;
        }

        /* Entanglement Map Card */
        .entanglement-card { grid-column: span 12; }

        .entanglement-container {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }

        .entanglement-matrix {
            flex: 1;
            min-width: 400px;
            overflow-x: auto;
        }

        .matrix-table {
            border-collapse: collapse;
            font-size: 10px;
        }

        .matrix-table th,
        .matrix-table td {
            width: 28px;
            height: 28px;
            text-align: center;
            border: 1px solid #21262d;
            padding: 2px;
        }

        .matrix-table th {
            background: #21262d;
            color: #8b949e;
            font-weight: 500;
            position: sticky;
        }

        .matrix-table th.row-header {
            left: 0;
            z-index: 2;
        }

        .matrix-table th.col-header {
            top: 0;
            z-index: 1;
        }

        .matrix-table th.corner {
            z-index: 3;
        }

        .matrix-cell {
            cursor: pointer;
            transition: all 0.2s;
        }

        .matrix-cell:hover {
            transform: scale(1.3);
            z-index: 10;
            box-shadow: 0 0 10px rgba(88, 166, 255, 0.5);
        }

        .matrix-cell.self { background: #30363d; }
        .matrix-cell.strength-0 { background: #161b22; }
        .matrix-cell.strength-1 { background: rgba(88, 166, 255, 0.2); }
        .matrix-cell.strength-2 { background: rgba(88, 166, 255, 0.4); }
        .matrix-cell.strength-3 { background: rgba(88, 166, 255, 0.6); }
        .matrix-cell.strength-4 { background: rgba(88, 166, 255, 0.8); }
        .matrix-cell.strength-5 { background: #58a6ff; }

        .phase-legend {
            flex: 0 0 280px;
        }

        .phase-group {
            margin-bottom: 20px;
            padding: 15px;
            background: #21262d;
            border-radius: 8px;
            border-left: 4px solid;
        }

        .phase-group.phase-1 { border-color: #7ee787; }
        .phase-group.phase-2 { border-color: #58a6ff; }
        .phase-group.phase-3 { border-color: #f0883e; }
        .phase-group.phase-4 { border-color: #f778ba; }

        .phase-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .phase-1 .phase-title { color: #7ee787; }
        .phase-2 .phase-title { color: #58a6ff; }
        .phase-3 .phase-title { color: #f0883e; }
        .phase-4 .phase-title { color: #f778ba; }

        .phase-agents {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .agent-chip {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            background: rgba(255,255,255,0.1);
            color: #c9d1d9;
        }

        .matrix-tooltip {
            position: fixed;
            padding: 10px 14px;
            background: #1f2937;
            border: 1px solid #30363d;
            border-radius: 8px;
            font-size: 12px;
            color: #c9d1d9;
            pointer-events: none;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .matrix-tooltip .tooltip-title {
            font-weight: 600;
            margin-bottom: 6px;
            color: #58a6ff;
        }

        .matrix-tooltip .tooltip-strength {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .strength-bar {
            flex: 1;
            height: 6px;
            background: #30363d;
            border-radius: 3px;
            overflow: hidden;
        }

        .strength-fill {
            height: 100%;
            background: #58a6ff;
            border-radius: 3px;
        }

        .entanglement-stats {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #21262d;
        }

        .stat-item {
            padding: 10px 15px;
            background: #21262d;
            border-radius: 6px;
            text-align: center;
        }

        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #58a6ff;
        }

        .stat-label {
            font-size: 11px;
            color: #8b949e;
            margin-top: 4px;
        }

        /* Entanglement Legends */
        .entanglement-legends {
            display: flex;
            flex-direction: column;
            gap: 15px;
            min-width: 150px;
        }

        .phase-legend {
            background: #21262d;
            padding: 12px;
            border-radius: 6px;
        }

        .strength-legend {
            background: #21262d;
            padding: 12px;
            border-radius: 6px;
        }

        .legend-title {
            font-size: 12px;
            font-weight: 600;
            color: #c9d1d9;
            margin-bottom: 10px;
            border-bottom: 1px solid #30363d;
            padding-bottom: 6px;
        }

        .legend-items {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .legend-scale {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 11px;
            color: #8b949e;
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
            flex-shrink: 0;
        }

        /* Phase Colors for Legend */
        .legend-color.phase-1 { background: #238636; }
        .legend-color.phase-2 { background: #1f6feb; }
        .legend-color.phase-3 { background: #a371f7; }
        .legend-color.phase-4 { background: #f85149; }

        /* Strength Scale Colors for Legend */
        .legend-color.strength-0 { background: #21262d; }
        .legend-color.strength-1 { background: #0d4429; }
        .legend-color.strength-2 { background: #1a7f37; }
        .legend-color.strength-3 { background: #2da44e; }
        .legend-color.strength-4 { background: #57ab5a; }
        .legend-color.strength-5 { background: #7ee787; }

        /* ============================================ */
        /* 반응형 디자인 및 크기 조절 기능 개선 */
        /* ============================================ */

        /* 반응형 그리드 - 태블릿 */
        @media (max-width: 1200px) {
            .grid {
                grid-template-columns: repeat(6, 1fr);
            }
            .state-card, .analysis-card, .recommendations-card, .dimensions-card {
                grid-column: span 6;
            }
            .entanglement-card {
                grid-column: span 6;
            }
            .entanglement-container {
                flex-direction: column;
            }
            .phase-legend {
                flex: 1 1 100%;
            }
        }

        /* 반응형 그리드 - 모바일 */
        @media (max-width: 768px) {
            .dashboard {
                padding: 10px;
            }
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            .header h1 {
                font-size: 18px;
            }
            .user-info {
                flex-wrap: wrap;
                justify-content: center;
            }
            .grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .state-card, .analysis-card, .recommendations-card, .dimensions-card, .entanglement-card {
                grid-column: span 1;
            }
            .card {
                padding: 15px;
            }
            .radar-container {
                max-width: 280px;
            }
            .dimension-label {
                width: 80px;
                font-size: 10px;
            }
            .matrix-table th, .matrix-table td {
                width: 20px;
                height: 20px;
                font-size: 8px;
            }
            .status-footer {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            .api-links {
                justify-content: center;
                flex-wrap: wrap;
            }
            .entanglement-stats {
                flex-wrap: wrap;
                justify-content: center;
            }
            .stat-item {
                min-width: 80px;
            }
        }

        /* 카드 크기 제한 */
        .card {
            min-height: 200px;
            max-height: none;
            overflow: auto;
        }

        .state-card {
            min-height: 380px;
        }

        .analysis-card {
            min-height: 350px;
        }

        .recommendations-card {
            min-height: 350px;
        }

        .dimensions-card {
            min-height: 380px;
        }

        /* 매트릭스 스크롤 개선 */
        .matrix-section {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 500px;
            position: relative;
        }

        .matrix-table {
            min-width: 600px;
        }

        /* 버튼 스타일 개선 */
        .refresh-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 100px;
            justify-content: center;
        }

        .refresh-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .refresh-btn.loading {
            background: #30363d;
        }

        .refresh-btn .spinner {
            display: none;
            animation: spin 1s linear infinite;
        }

        .refresh-btn.loading .spinner {
            display: inline-block;
        }

        .refresh-btn.loading .icon {
            display: none;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        /* 확대/축소 컨트롤 */
        .zoom-controls {
            display: flex;
            gap: 8px;
            margin-left: auto;
        }

        .zoom-btn {
            padding: 6px 12px;
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #c9d1d9;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .zoom-btn:hover {
            background: #30363d;
            border-color: #58a6ff;
        }

        .zoom-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* 토스트 알림 */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #238636;
            color: white;
            border-radius: 8px;
            font-size: 13px;
            z-index: 9999;
            opacity: 0;
            transform: translateY(20px);
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }

        .toast.error {
            background: #f85149;
        }

        .toast.warning {
            background: #f0883e;
        }

        /* 로딩 오버레이 */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(13, 17, 23, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 100;
            border-radius: 12px;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #30363d;
            border-top-color: #58a6ff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        /* 카드 위치 조정용 클래스 */
        .card {
            position: relative;
        }

        /* 크기 조절 핸들 */
        .resize-handle {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 20px;
            height: 20px;
            cursor: se-resize;
            opacity: 0.3;
            transition: opacity 0.2s;
        }

        .resize-handle:hover {
            opacity: 0.8;
        }

        .resize-handle::after {
            content: '';
            position: absolute;
            bottom: 4px;
            right: 4px;
            width: 10px;
            height: 10px;
            border-right: 2px solid #58a6ff;
            border-bottom: 2px solid #58a6ff;
        }

        /* 전체화면 토글 버튼 */
        .fullscreen-btn {
            padding: 4px 8px;
            background: transparent;
            border: 1px solid #30363d;
            border-radius: 4px;
            color: #8b949e;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .fullscreen-btn:hover {
            background: #21262d;
            color: #c9d1d9;
        }

        /* 카드 전체화면 모드 */
        .card.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1000;
            border-radius: 0;
            max-height: 100vh;
            overflow: auto;
        }

        .card.fullscreen .fullscreen-btn::before {
            content: '⊙';
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <!-- Header -->
        <div class="header">
            <h1>
                Quantum Monitoring Dashboard
                <span class="phase-badge">Phase 8.2</span>
            </h1>
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($USER->username ?? 'U', 0, 1)); ?></div>
                <div>
                    <div style="font-weight: 500; color: #c9d1d9;"><?php echo htmlspecialchars($USER->username ?? 'User'); ?></div>
                    <div style="font-size: 12px;">ID: <?php echo $userid; ?></div>
                </div>
                <button class="refresh-btn" id="refreshBtn" onclick="refreshDashboard()">
                    <span class="icon">🔄</span>
                    <span class="spinner">⟳</span>
                    <span class="text">새로고침</span>
                </button>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="grid">
            <!-- StateVector Radar Chart -->
            <div class="card state-card" id="stateCard">
                <div class="loading-overlay"><div class="loading-spinner"></div></div>
                <div class="card-header">
                    <span class="card-title">📊 8D StateVector</span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="card-badge"><?php echo $stateData['success'] ? '✅ Active' : '❌ Error'; ?></span>
                        <button class="fullscreen-btn" onclick="toggleFullscreen('stateCard')" title="전체화면">⛶</button>
                    </div>
                </div>
                <div class="radar-container">
                    <canvas id="stateRadar"></canvas>
                </div>
            </div>

            <!-- State Analysis -->
            <div class="card analysis-card" id="analysisCard">
                <div class="loading-overlay"><div class="loading-spinner"></div></div>
                <div class="card-header">
                    <span class="card-title">🔬 상태 분석</span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="card-badge"><?php
                            $risk = $analysisData['analysis']['risk_level'] ?? 'Unknown';
                            echo $risk;
                        ?></span>
                        <button class="fullscreen-btn" onclick="toggleFullscreen('analysisCard')" title="전체화면">⛶</button>
                    </div>
                </div>

                <?php
                $riskLevel = strtolower($analysisData['analysis']['risk_level'] ?? 'medium');
                $riskClass = in_array($riskLevel, ['low', 'medium', 'high']) ? $riskLevel : 'medium';
                $riskIcon = ['low' => '✅', 'medium' => '⚠️', 'high' => '🚨'][$riskClass];
                ?>
                <div class="risk-indicator <?php echo $riskClass; ?>">
                    <span class="risk-icon"><?php echo $riskIcon; ?></span>
                    <div class="risk-details">
                        <h3>위험 수준: <?php echo ucfirst($riskLevel); ?></h3>
                        <p><?php echo htmlspecialchars($analysisData['analysis']['intervention_recommendation'] ?? '분석 중...'); ?></p>
                    </div>
                </div>

                <div class="analysis-section">
                    <h4>강점</h4>
                    <div class="tag-list">
                        <?php
                        $strengths = $analysisData['analysis']['strengths'] ?? ['분석 중...'];
                        foreach ($strengths as $s): ?>
                        <span class="tag strength"><?php echo htmlspecialchars($s); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="analysis-section">
                    <h4>개선 필요</h4>
                    <div class="tag-list">
                        <?php
                        $weaknesses = $analysisData['analysis']['weaknesses'] ?? ['분석 중...'];
                        foreach ($weaknesses as $w): ?>
                        <span class="tag weakness"><?php echo htmlspecialchars($w); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Agent Recommendations -->
            <div class="card recommendations-card" id="recommendationsCard">
                <div class="loading-overlay"><div class="loading-spinner"></div></div>
                <div class="card-header">
                    <span class="card-title">🎯 에이전트 추천 순위</span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="card-badge"><?php
                            echo htmlspecialchars($recommendationsData['recommendations']['matched_persona'] ?? 'Unknown');
                        ?></span>
                        <button class="fullscreen-btn" onclick="toggleFullscreen('recommendationsCard')" title="전체화면">⛶</button>
                    </div>
                </div>
                <div class="agent-list">
                    <?php
                    $agentOrder = $recommendationsData['agent_order'] ?? [];
                    $priorityScores = $recommendationsData['recommendations']['priority_scores'] ?? [];
                    $agents = $bridge->getAgents();

                    $rank = 0;
                    foreach (array_slice($agentOrder, 0, 5) as $agentId):
                        $rank++;
                        $agentInfo = $agents[$agentId] ?? ['name' => "Agent $agentId", 'phase' => 0];
                        $score = $priorityScores[$agentId] ?? 0;
                        $rankClass = $rank <= 3 ? "rank-$rank" : "rank-other";
                    ?>
                    <div class="agent-item">
                        <div class="agent-rank <?php echo $rankClass; ?>"><?php echo $rank; ?></div>
                        <div class="agent-info">
                            <div class="agent-name"><?php echo htmlspecialchars($agentInfo['name']); ?></div>
                            <div class="agent-id">Agent <?php echo $agentId; ?> · Phase <?php echo $agentInfo['phase']; ?></div>
                        </div>
                        <div class="agent-score"><?php echo number_format($score, 2); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Dimension Bars -->
            <div class="card dimensions-card" id="dimensionsCard">
                <div class="loading-overlay"><div class="loading-spinner"></div></div>
                <div class="card-header">
                    <span class="card-title">📈 차원별 상세</span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="card-badge">8 Dimensions</span>
                        <button class="fullscreen-btn" onclick="toggleFullscreen('dimensionsCard')" title="전체화면">⛶</button>
                    </div>
                </div>
                <div class="dimension-bars">
                    <?php
                    $stateVector = $stateData['state_vector'] ?? [];
                    $dimensions = $bridge->getDimensions();

                    foreach ($dimensions as $idx => $dim):
                        $value = $stateVector[$dim['name']] ?? 0;
                        $percent = $value * 100;
                    ?>
                    <div class="dimension-bar">
                        <span class="dimension-label"><?php echo $dim['label']; ?></span>
                        <div class="dimension-track">
                            <div class="dimension-fill" style="width: <?php echo $percent; ?>%; background: <?php echo $dim['color']; ?>;"></div>
                        </div>
                        <span class="dimension-value" style="color: <?php echo $dim['color']; ?>;"><?php echo number_format($value, 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Entanglement Map -->
            <div class="card entanglement-card" id="entanglementCard">
                <div class="loading-overlay"><div class="loading-spinner"></div></div>
                <div class="card-header">
                    <span class="card-title">🔗 22 Agent Entanglement Map</span>
                    <div style="display: flex; gap: 8px; align-items: center;">
                        <span class="card-badge"><?php echo count($entanglementData['agents'] ?? []) . ' Agents'; ?></span>
                        <div class="zoom-controls">
                            <button class="zoom-btn" onclick="zoomMatrix(-0.1)" title="축소">−</button>
                            <button class="zoom-btn" onclick="zoomMatrix(0.1)" title="확대">+</button>
                            <button class="zoom-btn" onclick="resetMatrixZoom()" title="원래 크기">⟲</button>
                        </div>
                        <button class="fullscreen-btn" onclick="toggleFullscreen('entanglementCard')" title="전체화면">⛶</button>
                    </div>
                </div>
                <div class="entanglement-container">
                    <!-- Matrix Heatmap -->
                    <div class="matrix-section">
                        <table class="matrix-table" id="entanglementMatrix">
                            <thead>
                                <tr>
                                    <th></th>
                                    <?php
                                    $agents = $entanglementData['agents'] ?? [];
                                    foreach ($agents as $agent):
                                    ?>
                                    <th class="matrix-header phase-<?php echo $agent['phase']; ?>" title="Agent <?php echo $agent['id']; ?>: <?php echo htmlspecialchars($agent['name']); ?>">
                                        <?php echo $agent['id']; ?>
                                    </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $matrix = $entanglementData['matrix'] ?? [];
                                foreach ($agents as $rowAgent):
                                    $rowId = $rowAgent['id'];
                                ?>
                                <tr>
                                    <td class="matrix-header phase-<?php echo $rowAgent['phase']; ?>" title="Agent <?php echo $rowId; ?>: <?php echo htmlspecialchars($rowAgent['name']); ?>">
                                        <?php echo $rowId; ?>
                                    </td>
                                    <?php foreach ($agents as $colAgent):
                                        $colId = $colAgent['id'];
                                        $strength = $matrix[$rowId][$colId] ?? 0;
                                        $strengthClass = min(5, max(0, round($strength * 5)));
                                    ?>
                                    <td class="matrix-cell strength-<?php echo $strengthClass; ?>"
                                        data-from="<?php echo $rowId; ?>"
                                        data-to="<?php echo $colId; ?>"
                                        data-strength="<?php echo $strength; ?>"
                                        data-from-name="<?php echo htmlspecialchars($rowAgent['name']); ?>"
                                        data-to-name="<?php echo htmlspecialchars($colAgent['name']); ?>">
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Phase Legend & Stats -->
                    <div class="phase-legend">
                        <?php
                        $phaseNames = [
                            1 => ['name' => 'Daily Information', 'icon' => '📊'],
                            2 => ['name' => 'Real-time Interaction', 'icon' => '⚡'],
                            3 => ['name' => 'Diagnosis & Preparation', 'icon' => '🔬'],
                            4 => ['name' => 'Intervention & Improvement', 'icon' => '🎯']
                        ];
                        $agentsByPhase = [];
                        foreach ($agents as $agent) {
                            $agentsByPhase[$agent['phase']][] = $agent;
                        }
                        foreach ($phaseNames as $phaseNum => $phaseInfo):
                        ?>
                        <div class="phase-group phase-<?php echo $phaseNum; ?>">
                            <div class="phase-title"><?php echo $phaseInfo['icon']; ?> Phase <?php echo $phaseNum; ?>: <?php echo $phaseInfo['name']; ?></div>
                            <div class="phase-agents">
                                <?php foreach ($agentsByPhase[$phaseNum] ?? [] as $agent): ?>
                                <span class="agent-chip"><?php echo $agent['id']; ?>. <?php echo htmlspecialchars(substr($agent['name'], 0, 15)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Strength Legend -->
                        <div class="strength-legend">
                            <div class="legend-title">연결 강도</div>
                            <div class="legend-scale">
                                <span class="legend-item">
                                    <span class="legend-color strength-0"></span>
                                    <span>0 (없음)</span>
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color strength-2"></span>
                                    <span>0.4 (약함)</span>
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color strength-4"></span>
                                    <span>0.8 (강함)</span>
                                </span>
                                <span class="legend-item">
                                    <span class="legend-color strength-5"></span>
                                    <span>1.0 (최대)</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Entanglement Stats -->
                <div class="entanglement-stats">
                    <?php
                    $totalConnections = 0;
                    $strongConnections = 0;
                    $avgStrength = 0;
                    $connectionCount = 0;
                    foreach ($matrix as $rowId => $row) {
                        foreach ($row as $colId => $strength) {
                            if ($rowId != $colId && $strength > 0) {
                                $totalConnections++;
                                $avgStrength += $strength;
                                $connectionCount++;
                                if ($strength >= 0.6) $strongConnections++;
                            }
                        }
                    }
                    $avgStrength = $connectionCount > 0 ? $avgStrength / $connectionCount : 0;
                    ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalConnections; ?></div>
                        <div class="stat-label">총 연결 수</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $strongConnections; ?></div>
                        <div class="stat-label">강한 연결 (≥0.6)</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo number_format($avgStrength, 2); ?></div>
                        <div class="stat-label">평균 강도</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo count($agents); ?></div>
                        <div class="stat-label">에이전트 수</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Footer -->
        <div class="status-footer">
            <div>
                <span class="status-dot <?php echo ($stateData['data_interface_available'] ?? false) ? 'active' : 'inactive'; ?>"></span>
                Data Interface: <?php echo ($stateData['data_interface_available'] ?? false) ? '연결됨' : '미연결'; ?>
                &nbsp;|&nbsp;
                최종 업데이트: <?php echo date('Y-m-d H:i:s'); ?>
            </div>
            <div class="api-links">
                <a href="?action=state&format=json" class="api-link">📄 State API</a>
                <a href="?action=analysis&format=json" class="api-link">📄 Analysis API</a>
                <a href="?action=recommendations&format=json" class="api-link">📄 Recommendations API</a>
                <a href="?action=entanglement&format=json" class="api-link">📄 Entanglement API</a>
            </div>
        </div>
    </div>

    <!-- Chart.js Radar Chart -->
    <script>
        // StateVector data from PHP
        const stateVector = <?php echo json_encode($stateData['state_vector'] ?? []); ?>;

        // Radar chart
        const ctx = document.getElementById('stateRadar').getContext('2d');
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: [
                    '인지 명확성',
                    '정서 안정성',
                    '참여 수준',
                    '개념 숙달도',
                    '루틴 강도',
                    '메타인지',
                    '이탈 위험도',
                    '개입 준비도'
                ],
                datasets: [{
                    label: '현재 상태',
                    data: [
                        stateVector.cognitive_clarity || 0,
                        stateVector.emotional_stability || 0,
                        stateVector.engagement_level || 0,
                        stateVector.concept_mastery || 0,
                        stateVector.routine_strength || 0,
                        stateVector.metacognitive_awareness || 0,
                        stateVector.dropout_risk || 0,
                        stateVector.intervention_readiness || 0
                    ],
                    backgroundColor: 'rgba(88, 166, 255, 0.2)',
                    borderColor: '#58a6ff',
                    borderWidth: 2,
                    pointBackgroundColor: '#58a6ff',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: '#58a6ff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    r: {
                        min: 0,
                        max: 1,
                        ticks: {
                            stepSize: 0.2,
                            color: '#8b949e',
                            backdropColor: 'transparent'
                        },
                        grid: {
                            color: '#30363d'
                        },
                        angleLines: {
                            color: '#30363d'
                        },
                        pointLabels: {
                            color: '#c9d1d9',
                            font: {
                                size: 11
                            }
                        }
                    }
                }
            }
        });

        // Auto-refresh every 30 seconds
        // setTimeout(() => location.reload(), 30000);
    </script>

    <!-- Entanglement Matrix Tooltip -->
    <div class="matrix-tooltip" id="matrixTooltip">
        <div class="tooltip-title"></div>
        <div class="tooltip-strength">
            <span>연결 강도:</span>
            <div class="strength-bar">
                <div class="strength-fill"></div>
            </div>
            <span class="strength-value"></span>
        </div>
    </div>

    <script>
        // Entanglement Matrix Tooltip
        (function() {
            const tooltip = document.getElementById('matrixTooltip');
            const cells = document.querySelectorAll('.matrix-cell');

            cells.forEach(cell => {
                cell.addEventListener('mouseenter', function(e) {
                    const fromId = this.dataset.from;
                    const toId = this.dataset.to;
                    const strength = parseFloat(this.dataset.strength) || 0;
                    const fromName = this.dataset.fromName;
                    const toName = this.dataset.toName;

                    // Don't show tooltip for self-connections
                    if (fromId === toId) {
                        tooltip.style.display = 'none';
                        return;
                    }

                    // Update tooltip content
                    tooltip.querySelector('.tooltip-title').textContent =
                        `Agent ${fromId} → Agent ${toId}`;
                    tooltip.querySelector('.strength-value').textContent =
                        strength.toFixed(2);
                    tooltip.querySelector('.strength-fill').style.width =
                        (strength * 100) + '%';

                    // Show tooltip
                    tooltip.style.display = 'block';
                });

                cell.addEventListener('mousemove', function(e) {
                    tooltip.style.left = (e.clientX + 15) + 'px';
                    tooltip.style.top = (e.clientY + 15) + 'px';
                });

                cell.addEventListener('mouseleave', function() {
                    tooltip.style.display = 'none';
                });
            });

            // Highlight row/column on hover
            cells.forEach(cell => {
                cell.addEventListener('mouseenter', function() {
                    const fromId = this.dataset.from;
                    const toId = this.dataset.to;

                    // Highlight headers
                    document.querySelectorAll('.matrix-header').forEach(h => {
                        h.style.opacity = '0.5';
                    });
                    document.querySelectorAll(`.matrix-header[title*="Agent ${fromId}:"]`).forEach(h => {
                        h.style.opacity = '1';
                        h.style.fontWeight = 'bold';
                    });
                    document.querySelectorAll(`.matrix-header[title*="Agent ${toId}:"]`).forEach(h => {
                        h.style.opacity = '1';
                        h.style.fontWeight = 'bold';
                    });
                });

                cell.addEventListener('mouseleave', function() {
                    document.querySelectorAll('.matrix-header').forEach(h => {
                        h.style.opacity = '1';
                        h.style.fontWeight = 'normal';
                    });
                });
            });
        })();
    </script>

    <!-- Toast Notification Element -->
    <div class="toast" id="toast"></div>

    <!-- Enhanced JavaScript Functions -->
    <script>
        // ============================================
        // Toast Notification System
        // ============================================
        function showToast(message, type = 'success', duration = 3000) {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + type + ' show';
            setTimeout(() => {
                toast.classList.remove('show');
            }, duration);
        }

        // ============================================
        // AJAX Dashboard Refresh
        // ============================================
        let isRefreshing = false;
        let radarChart = null;

        async function refreshDashboard() {
            if (isRefreshing) return;

            const btn = document.getElementById('refreshBtn');
            btn.classList.add('loading');
            btn.disabled = true;
            isRefreshing = true;

            // Show loading overlays on all cards
            document.querySelectorAll('.loading-overlay').forEach(el => {
                el.classList.add('active');
            });

            try {
                // Fetch all data in parallel
                const [stateRes, analysisRes, recommendationsRes, entanglementRes] = await Promise.all([
                    fetch('?action=state&format=json&_t=' + Date.now()),
                    fetch('?action=analysis&format=json&_t=' + Date.now()),
                    fetch('?action=recommendations&agents=5,8,10,12&format=json&_t=' + Date.now()),
                    fetch('?action=entanglement&format=json&_t=' + Date.now())
                ]);

                const [stateData, analysisData, recommendationsData, entanglementData] = await Promise.all([
                    stateRes.json(),
                    analysisRes.json(),
                    recommendationsRes.json(),
                    entanglementRes.json()
                ]);

                // Update Radar Chart
                updateRadarChart(stateData);

                // Update timestamp
                document.querySelector('.status-footer div').innerHTML =
                    '<span class="status-dot ' + (stateData.data_interface_available ? 'active' : 'inactive') + '"></span>' +
                    'Data Interface: ' + (stateData.data_interface_available ? '연결됨' : '미연결') +
                    '&nbsp;|&nbsp;최종 업데이트: ' + new Date().toLocaleString('ko-KR');

                showToast('✅ 대시보드가 새로고침되었습니다.', 'success');

            } catch (error) {
                console.error('Dashboard refresh error:', error);
                showToast('❌ 새로고침 실패: ' + error.message, 'error');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
                isRefreshing = false;

                // Hide loading overlays
                document.querySelectorAll('.loading-overlay').forEach(el => {
                    el.classList.remove('active');
                });
            }
        }

        function updateRadarChart(stateData) {
            const stateVector = stateData.state_vector || {};
            const newData = [
                stateVector.cognitive_clarity || 0,
                stateVector.emotional_stability || 0,
                stateVector.engagement_level || 0,
                stateVector.concept_mastery || 0,
                stateVector.routine_strength || 0,
                stateVector.metacognitive_awareness || 0,
                stateVector.dropout_risk || 0,
                stateVector.intervention_readiness || 0
            ];

            // Get the chart instance
            const chartInstance = Chart.getChart('stateRadar');
            if (chartInstance) {
                chartInstance.data.datasets[0].data = newData;
                chartInstance.update('active');
            }
        }

        // ============================================
        // Fullscreen Toggle
        // ============================================
        function toggleFullscreen(cardId) {
            const card = document.getElementById(cardId);
            if (!card) return;

            card.classList.toggle('fullscreen');

            // Update button text
            const btn = card.querySelector('.fullscreen-btn');
            if (card.classList.contains('fullscreen')) {
                btn.textContent = '✕';
                btn.title = '전체화면 종료';
                showToast('전체화면 모드 (ESC로 종료)', 'success', 2000);
            } else {
                btn.textContent = '⛶';
                btn.title = '전체화면';
            }

            // Resize chart if needed
            if (cardId === 'stateCard') {
                setTimeout(() => {
                    const chartInstance = Chart.getChart('stateRadar');
                    if (chartInstance) chartInstance.resize();
                }, 100);
            }
        }

        // ESC key to exit fullscreen
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.card.fullscreen').forEach(card => {
                    card.classList.remove('fullscreen');
                    const btn = card.querySelector('.fullscreen-btn');
                    if (btn) {
                        btn.textContent = '⛶';
                        btn.title = '전체화면';
                    }
                });
            }
        });

        // ============================================
        // Matrix Zoom Controls
        // ============================================
        let matrixScale = 1;
        const minScale = 0.5;
        const maxScale = 2;

        function zoomMatrix(delta) {
            matrixScale = Math.max(minScale, Math.min(maxScale, matrixScale + delta));
            applyMatrixZoom();
        }

        function resetMatrixZoom() {
            matrixScale = 1;
            applyMatrixZoom();
            showToast('매트릭스 크기 초기화', 'success', 1500);
        }

        function applyMatrixZoom() {
            const matrix = document.getElementById('entanglementMatrix');
            if (matrix) {
                matrix.style.transform = `scale(${matrixScale})`;
                matrix.style.transformOrigin = 'top left';
            }
        }

        // ============================================
        // Keyboard Shortcuts
        // ============================================
        document.addEventListener('keydown', function(e) {
            // R key for refresh
            if (e.key === 'r' && !e.ctrlKey && !e.metaKey && !e.altKey) {
                const activeElement = document.activeElement;
                if (activeElement.tagName !== 'INPUT' && activeElement.tagName !== 'TEXTAREA') {
                    e.preventDefault();
                    refreshDashboard();
                }
            }

            // + / - for matrix zoom
            if (e.key === '+' || e.key === '=') {
                zoomMatrix(0.1);
            } else if (e.key === '-') {
                zoomMatrix(-0.1);
            }

            // 0 to reset zoom
            if (e.key === '0') {
                resetMatrixZoom();
            }
        });

        // ============================================
        // Auto-refresh (optional, uncomment to enable)
        // ============================================
        // setInterval(refreshDashboard, 30000);

        // ============================================
        // Touch/Swipe Support for Mobile
        // ============================================
        let touchStartX = 0;
        let touchEndX = 0;

        document.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            const swipeThreshold = 100;
            const diff = touchStartX - touchEndX;

            // Right to left swipe - could trigger next card focus
            if (diff > swipeThreshold) {
                // Optional: implement card navigation
            }
            // Left to right swipe - refresh
            else if (diff < -swipeThreshold) {
                refreshDashboard();
            }
        }

        // ============================================
        // Window Resize Handler
        // ============================================
        let resizeTimeout;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(function() {
                const chartInstance = Chart.getChart('stateRadar');
                if (chartInstance) {
                    chartInstance.resize();
                }
            }, 250);
        });

        // ============================================
        // Initialization
        // ============================================
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Quantum Dashboard] Phase 8.2 Initialized');
            console.log('[Quantum Dashboard] Keyboard shortcuts: R=Refresh, +/-=Zoom, 0=Reset, ESC=Exit fullscreen');
        });
    </script>
</body>
</html>
<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * Phase 8.2 Quantum Monitoring Dashboard
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * API Endpoints:
 *   - ?action=state          → 8D StateVector JSON
 *   - ?action=analysis       → State Analysis JSON
 *   - ?action=recommendations → Agent Recommendations JSON
 *   - ?action=entanglement   → Entanglement Map JSON
 *
 * Python Dependencies:
 *   - _quantum_orchestrator.py (Phase 8)
 *   - _quantum_data_interface.py (Phase 7)
 *   - _quantum_entanglement.py (Phase 3)
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
