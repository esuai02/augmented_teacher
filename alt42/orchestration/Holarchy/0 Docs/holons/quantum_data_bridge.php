<?php
/**
 * Quantum Data Bridge - PHP ↔ Python Interface
 * =============================================
 * Phase 7.2: PHP 브릿지 - 22개 에이전트 데이터를 양자 StateVector로 변환
 *
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/Holarchy/0%20Docs/holons/quantum_data_bridge.php
 *
 * 데이터 흐름:
 *   PHP (Agent Context) → Python (_quantum_data_interface.py) → 8D StateVector
 *
 * API 엔드포인트:
 *   ?action=test           - 연결 테스트
 *   ?action=transform      - 전체 변환 실행 (userid 필요)
 *   ?action=get_features   - StandardFeatures 반환
 *   ?action=get_state      - 8D StateVector 반환
 *
 * @file    quantum_data_bridge.php
 * @package QuantumOrchestration
 * @phase   7.2
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
// 상수 정의
// =============================================================================
define('HOLONS_PATH', __DIR__);
define('PYTHON_INTERFACE', HOLONS_PATH . '/_quantum_data_interface.py');
define('PYTHON_CMD', 'python3');

// =============================================================================
// QuantumDataBridge 클래스
// =============================================================================
/**
 * PHP-Python 양자 데이터 브릿지
 *
 * 22개 에이전트 컨텍스트를 수집하고 Python 인터페이스를 통해
 * 8차원 StateVector로 변환
 */
class QuantumDataBridge {

    private $db;
    private $userid;
    private $errors = [];
    private $debug = false;

    /**
     * 생성자
     * @param int $userid 대상 사용자 ID
     * @param bool $debug 디버그 모드 활성화
     */
    public function __construct($userid = null, $debug = false) {
        global $DB, $USER;
        $this->db = $DB;
        $this->userid = $userid ?? $USER->id;
        $this->debug = $debug;
    }

    /**
     * Python 인터페이스 연결 테스트
     * @return array 테스트 결과
     */
    public function testConnection(): array {
        $result = [
            'success' => false,
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'checks' => []
        ];

        // 1. Python 버전 확인
        $pythonVersion = shell_exec(PYTHON_CMD . ' --version 2>&1');
        $result['checks']['python_version'] = [
            'status' => strpos($pythonVersion, 'Python 3') !== false ? 'ok' : 'error',
            'value' => trim($pythonVersion)
        ];

        // 2. 인터페이스 파일 존재 확인
        $result['checks']['interface_file'] = [
            'status' => file_exists(PYTHON_INTERFACE) ? 'ok' : 'error',
            'value' => file_exists(PYTHON_INTERFACE) ? basename(PYTHON_INTERFACE) : 'NOT FOUND'
        ];

        // 3. Python 모듈 import 테스트
        $importTest = shell_exec(PYTHON_CMD . ' -c "from dataclasses import dataclass; print(\'OK\')" 2>&1');
        $result['checks']['python_modules'] = [
            'status' => trim($importTest) === 'OK' ? 'ok' : 'error',
            'value' => trim($importTest)
        ];

        // 4. 인터페이스 실행 테스트 (간단한 ping)
        if (file_exists(PYTHON_INTERFACE)) {
            $pingScript = <<<PYTHON
import sys
sys.path.insert(0, '{$this->escapePath(HOLONS_PATH)}')
from _quantum_data_interface import StandardFeatures
print('INTERFACE_OK')
PYTHON;
            $pingResult = $this->runPythonCode($pingScript);
            $result['checks']['interface_import'] = [
                'status' => strpos($pingResult, 'INTERFACE_OK') !== false ? 'ok' : 'error',
                'value' => trim($pingResult)
            ];
        }

        // 전체 성공 여부
        $allOk = true;
        foreach ($result['checks'] as $check) {
            if ($check['status'] !== 'ok') {
                $allOk = false;
                break;
            }
        }
        $result['success'] = $allOk;

        return $result;
    }

    /**
     * 에이전트 컨텍스트 수집
     * @return array 22개 에이전트의 raw context
     */
    public function collectAgentContexts(): array {
        $contexts = [];

        // Agent 08: 침착도 데이터
        $contexts['agent_08'] = $this->getCalmnessContext();

        // Agent 11: 문제노트 데이터
        $contexts['agent_11'] = $this->getProblemNotesContext();

        // Agent 12: 휴식 루틴 데이터
        $contexts['agent_12'] = $this->getRestRoutineContext();

        // Agent 03: 목표 분석 데이터
        $contexts['agent_03'] = $this->getGoalAnalysisContext();

        // Agent 09: 학습 관리 데이터 (포모도로)
        $contexts['agent_09'] = $this->getPomodoroContext();

        // Agent 05: 학습 감정 데이터
        $contexts['agent_05'] = $this->getLearningEmotionContext();

        return $contexts;
    }

    /**
     * 침착도 컨텍스트 수집 (Agent 08)
     * @return array
     */
    private function getCalmnessContext(): array {
        $context = [
            'student_id' => $this->userid,
            'calm_score' => 0.5,
            'calmness_level' => 0,
            'daily_goals' => 0,
            'review_points' => 0
        ];

        try {
            // mdl_abessi_calm_log 테이블에서 최근 데이터 조회
            $record = $this->db->get_record_sql(
                "SELECT calm_score, calmness_level, daily_goals, review_points
                 FROM {abessi_calm_log}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$this->userid]
            );

            if ($record) {
                $context['calm_score'] = (float)($record->calm_score ?? 0.5);
                $context['calmness_level'] = (int)($record->calmness_level ?? 0);
                $context['daily_goals'] = (int)($record->daily_goals ?? 0);
                $context['review_points'] = (int)($record->review_points ?? 0);
            }
        } catch (Exception $e) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: " . $e->getMessage();
        }

        return $context;
    }

    /**
     * 문제노트 컨텍스트 수집 (Agent 11)
     * @return array
     */
    private function getProblemNotesContext(): array {
        $context = [
            'student_id' => $this->userid,
            'total_problems' => 0,
            'correct_count' => 0,
            'accuracy_rate' => 0.0,
            'recent_streak' => 0
        ];

        try {
            // 최근 7일간 문제 풀이 데이터
            $sevenDaysAgo = time() - (7 * 86400);

            $records = $this->db->get_records_sql(
                "SELECT iscorrect
                 FROM {abessi_problem_attempts}
                 WHERE userid = ?
                 AND timecreated >= ?
                 ORDER BY timecreated DESC",
                [$this->userid, $sevenDaysAgo]
            );

            if ($records && count($records) > 0) {
                $total = count($records);
                $correct = 0;
                $streak = 0;
                $counting = true;

                foreach ($records as $record) {
                    if ($record->iscorrect) {
                        $correct++;
                        if ($counting) $streak++;
                    } else {
                        $counting = false;
                    }
                }

                $context['total_problems'] = $total;
                $context['correct_count'] = $correct;
                $context['accuracy_rate'] = $total > 0 ? round($correct / $total, 3) : 0.0;
                $context['recent_streak'] = $streak;
            }
        } catch (Exception $e) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: " . $e->getMessage();
        }

        return $context;
    }

    /**
     * 휴식 루틴 컨텍스트 수집 (Agent 12)
     * @return array
     */
    private function getRestRoutineContext(): array {
        $context = [
            'student_id' => $this->userid,
            'rest_count' => 0,
            'average_interval' => null,
            'rest_type' => '휴식 미사용형'
        ];

        try {
            $thirtyDaysAgo = time() - (30 * 86400);

            $records = $this->db->get_records_sql(
                "SELECT duration, timecreated
                 FROM {abessi_breaktimelog}
                 WHERE userid = ?
                 AND timecreated >= ?
                 ORDER BY timecreated ASC",
                [$this->userid, $thirtyDaysAgo]
            );

            if ($records && count($records) > 0) {
                $context['rest_count'] = count($records);

                $intervals = [];
                $prevEndTime = null;

                foreach ($records as $record) {
                    if ($prevEndTime !== null) {
                        $interval = ($record->timecreated - $prevEndTime) / 60;
                        $intervals[] = $interval;
                    }
                    $prevEndTime = $record->timecreated + $record->duration;
                }

                if (!empty($intervals)) {
                    $avgInterval = array_sum($intervals) / count($intervals);
                    $context['average_interval'] = round($avgInterval, 1);

                    if ($avgInterval <= 60) {
                        $context['rest_type'] = '규칙적 휴식형';
                    } elseif ($avgInterval <= 90) {
                        $context['rest_type'] = '활동 중심 휴식형';
                    } else {
                        $context['rest_type'] = '집중 몰입형';
                    }
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: " . $e->getMessage();
        }

        return $context;
    }

    /**
     * 목표 분석 컨텍스트 수집 (Agent 03)
     * @return array
     */
    private function getGoalAnalysisContext(): array {
        $context = [
            'student_id' => $this->userid,
            'goal_clarity' => 0.5,
            'target_score' => 0,
            'current_score' => 0,
            'days_remaining' => 30
        ];

        try {
            $record = $this->db->get_record_sql(
                "SELECT target_score, current_score, exam_date, goal_clarity
                 FROM {abessi_goal_analysis}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$this->userid]
            );

            if ($record) {
                $context['target_score'] = (int)($record->target_score ?? 0);
                $context['current_score'] = (int)($record->current_score ?? 0);
                $context['goal_clarity'] = (float)($record->goal_clarity ?? 0.5);

                if (!empty($record->exam_date)) {
                    $examTime = strtotime($record->exam_date);
                    $daysRemaining = max(0, ($examTime - time()) / 86400);
                    $context['days_remaining'] = (int)$daysRemaining;
                }
            }
        } catch (Exception $e) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: " . $e->getMessage();
        }

        return $context;
    }

    /**
     * 포모도로 컨텍스트 수집 (Agent 09)
     * @return array
     */
    private function getPomodoroContext(): array {
        $context = [
            'student_id' => $this->userid,
            'total_sessions' => 0,
            'completed_sessions' => 0,
            'completion_rate' => 0.0,
            'avg_focus_duration' => 0
        ];

        try {
            $sevenDaysAgo = time() - (7 * 86400);

            $records = $this->db->get_records_sql(
                "SELECT is_completed, duration
                 FROM {abessi_pomodoro_log}
                 WHERE userid = ?
                 AND timecreated >= ?",
                [$this->userid, $sevenDaysAgo]
            );

            if ($records && count($records) > 0) {
                $total = count($records);
                $completed = 0;
                $totalDuration = 0;

                foreach ($records as $record) {
                    if ($record->is_completed) {
                        $completed++;
                    }
                    $totalDuration += $record->duration;
                }

                $context['total_sessions'] = $total;
                $context['completed_sessions'] = $completed;
                $context['completion_rate'] = round($completed / $total, 3);
                $context['avg_focus_duration'] = round($totalDuration / $total);
            }
        } catch (Exception $e) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: " . $e->getMessage();
        }

        return $context;
    }

    /**
     * 학습 감정 컨텍스트 수집 (Agent 05)
     * @return array
     */
    private function getLearningEmotionContext(): array {
        $context = [
            'student_id' => $this->userid,
            'emotion_state' => 'neutral',
            'frustration_level' => 0.0,
            'motivation_level' => 0.5,
            'confidence_level' => 0.5
        ];

        try {
            $record = $this->db->get_record_sql(
                "SELECT emotion_state, frustration_level, motivation_level, confidence_level
                 FROM {abessi_learning_emotion}
                 WHERE userid = ?
                 ORDER BY timecreated DESC
                 LIMIT 1",
                [$this->userid]
            );

            if ($record) {
                $context['emotion_state'] = $record->emotion_state ?? 'neutral';
                $context['frustration_level'] = (float)($record->frustration_level ?? 0.0);
                $context['motivation_level'] = (float)($record->motivation_level ?? 0.5);
                $context['confidence_level'] = (float)($record->confidence_level ?? 0.5);
            }
        } catch (Exception $e) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: " . $e->getMessage();
        }

        return $context;
    }

    /**
     * 전체 변환 실행: Agent Context → 8D StateVector
     * @return array 변환 결과
     */
    public function transform(): array {
        $result = [
            'success' => false,
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'userid' => $this->userid,
            'state_vector_8d' => null,
            'features' => null,
            'errors' => []
        ];

        // 1. 에이전트 컨텍스트 수집
        $contexts = $this->collectAgentContexts();

        // 2. Python 변환 스크립트 생성
        $pythonScript = $this->generateTransformScript($contexts);

        // 3. Python 실행
        $output = $this->runPythonCode($pythonScript);

        // 4. 결과 파싱
        if ($output !== null) {
            $jsonStart = strpos($output, '{"success"');
            if ($jsonStart !== false) {
                $jsonOutput = substr($output, $jsonStart);
                $decoded = json_decode($jsonOutput, true);
                if ($decoded) {
                    return $decoded;
                }
            }

            // JSON 파싱 실패 시 에러 기록
            $result['errors'][] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: Failed to parse Python output";
            $result['raw_output'] = $output;
        } else {
            $result['errors'][] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: Python execution failed";
        }

        $result['errors'] = array_merge($result['errors'], $this->errors);
        return $result;
    }

    /**
     * Python 변환 스크립트 생성
     * @param array $contexts 에이전트 컨텍스트
     * @return string Python 코드
     */
    private function generateTransformScript(array $contexts): string {
        $contextsJson = json_encode($contexts, JSON_UNESCAPED_UNICODE);
        $holonsPath = $this->escapePath(HOLONS_PATH);

        return <<<PYTHON
# -*- coding: utf-8 -*-
import sys
import json

sys.path.insert(0, '{$holonsPath}')

from _quantum_data_interface import (
    QuantumDataCollector,
    DimensionReducer,
    StandardFeatures
)

def main():
    contexts = {$contextsJson}

    collector = QuantumDataCollector()

    # 각 에이전트 컨텍스트 처리
    if 'agent_08' in contexts:
        collector.collect_from_agent(8, contexts['agent_08'])
    if 'agent_11' in contexts:
        collector.collect_from_agent(11, contexts['agent_11'])
    if 'agent_12' in contexts:
        collector.collect_from_agent(12, contexts['agent_12'])
    if 'agent_03' in contexts:
        collector.collect_from_agent(3, contexts['agent_03'])
    if 'agent_09' in contexts:
        collector.collect_from_agent(9, contexts['agent_09'])
    if 'agent_05' in contexts:
        collector.collect_from_agent(5, contexts['agent_05'])

    # StandardFeatures 빌드
    features = collector.build_standard_features()

    # 8D StateVector 변환
    state_8d = DimensionReducer.transform(features)

    # 결과 출력
    result = {
        'success': True,
        'timestamp': '{$this->getCurrentTimestamp()}',
        'userid': {$this->userid},
        'state_vector_8d': state_8d,
        'features': {
            'concept_mastery': features.concept_mastery,
            'problem_accuracy': features.problem_accuracy,
            'calmness_score': features.calmness_score,
            'routine_consistency': features.routine_consistency,
            'metacognitive_score': features.metacognitive_score,
            'motivation_level': features.motivation_level,
            'dropout_risk': features.dropout_risk
        },
        'errors': []
    }

    print(json.dumps(result, ensure_ascii=False))

if __name__ == '__main__':
    main()
PYTHON;
    }

    /**
     * Python 코드 실행
     * @param string $code Python 코드
     * @return string|null 실행 결과
     */
    private function runPythonCode(string $code): ?string {
        // 임시 파일 생성
        $tempFile = tempnam(sys_get_temp_dir(), 'quantum_bridge_');
        if ($tempFile === false) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: Failed to create temp file";
            return null;
        }

        $tempFile .= '.py';
        if (file_put_contents($tempFile, $code) === false) {
            $this->errors[] = "Error [quantum_data_bridge.php:" . __LINE__ . "]: Failed to write temp file";
            return null;
        }

        // Python 실행
        $cmd = 'PYTHONIOENCODING=utf-8 ' . PYTHON_CMD . ' ' . escapeshellarg($tempFile) . ' 2>&1';
        $output = shell_exec($cmd);

        // 임시 파일 삭제
        @unlink($tempFile);

        return $output;
    }

    /**
     * 경로 이스케이프
     * @param string $path
     * @return string
     */
    private function escapePath(string $path): string {
        return str_replace("'", "\\'", $path);
    }

    /**
     * 현재 타임스탬프
     * @return string
     */
    private function getCurrentTimestamp(): string {
        return date('Y-m-d\TH:i:s\Z');
    }

    /**
     * 에러 목록 반환
     * @return array
     */
    public function getErrors(): array {
        return $this->errors;
    }
}

// =============================================================================
// API 요청 처리
// =============================================================================
$action = $_GET['action'] ?? 'test';
$userid = isset($_GET['userid']) ? (int)$_GET['userid'] : $USER->id;
$format = $_GET['format'] ?? 'html';  // html 또는 json

$bridge = new QuantumDataBridge($userid, true);
$result = null;

switch ($action) {
    case 'test':
        $result = $bridge->testConnection();
        break;

    case 'transform':
        $result = $bridge->transform();
        break;

    case 'contexts':
        $result = [
            'success' => true,
            'timestamp' => date('Y-m-d\TH:i:s\Z'),
            'userid' => $userid,
            'contexts' => $bridge->collectAgentContexts(),
            'errors' => $bridge->getErrors()
        ];
        break;

    default:
        $result = [
            'success' => false,
            'error' => "Error [quantum_data_bridge.php:" . __LINE__ . "]: Unknown action: {$action}",
            'available_actions' => ['test', 'transform', 'contexts']
        ];
}

// JSON 응답
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML 응답
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quantum Data Bridge - Phase 7.2</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            color: #c9d1d9;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #58a6ff;
            border-bottom: 2px solid #30363d;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        h1::before {
            content: '🔗';
            font-size: 32px;
        }
        h2 {
            color: #7ee787;
            margin-top: 30px;
            font-size: 18px;
        }
        .phase-badge {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .info-box {
            background: rgba(22, 27, 34, 0.8);
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }
        .info-row {
            display: flex;
            margin: 8px 0;
        }
        .label {
            color: #8b949e;
            width: 150px;
            flex-shrink: 0;
        }
        .value {
            color: #7ee787;
            font-family: 'Consolas', monospace;
        }
        .value.error {
            color: #f85149;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            border: 1px solid transparent;
        }
        .btn-primary {
            background: linear-gradient(135deg, #238636 0%, #2ea043 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(35, 134, 54, 0.4);
        }
        .btn-secondary {
            background: #21262d;
            color: #c9d1d9;
            border-color: #30363d;
        }
        .btn-secondary:hover {
            background: #30363d;
        }
        pre {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            overflow-x: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 13px;
            line-height: 1.6;
        }
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .status-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 15px;
        }
        .status-card.ok {
            border-left: 3px solid #238636;
        }
        .status-card.error {
            border-left: 3px solid #f85149;
        }
        .status-title {
            color: #8b949e;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .status-value {
            font-size: 16px;
            font-weight: 500;
        }
        .state-vector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin: 15px 0;
        }
        .sv-item {
            background: #21262d;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }
        .sv-label {
            font-size: 11px;
            color: #8b949e;
            margin-bottom: 5px;
        }
        .sv-value {
            font-size: 20px;
            font-weight: bold;
            color: #58a6ff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            Quantum Data Bridge
            <span class="phase-badge">Phase 7.2</span>
        </h1>

        <div class="info-box">
            <div class="info-row">
                <span class="label">현재 사용자:</span>
                <span class="value"><?php echo htmlspecialchars($USER->username ?? 'N/A'); ?> (ID: <?php echo $userid; ?>)</span>
            </div>
            <div class="info-row">
                <span class="label">현재 액션:</span>
                <span class="value"><?php echo htmlspecialchars($action); ?></span>
            </div>
            <div class="info-row">
                <span class="label">실행 시간:</span>
                <span class="value"><?php echo date('Y-m-d H:i:s'); ?></span>
            </div>
        </div>

        <div class="action-buttons">
            <a href="?action=test" class="btn btn-secondary">🔍 연결 테스트</a>
            <a href="?action=contexts" class="btn btn-secondary">📊 컨텍스트 조회</a>
            <a href="?action=transform" class="btn btn-primary">⚡ 변환 실행</a>
            <a href="?action=transform&format=json" class="btn btn-secondary">📄 JSON 결과</a>
        </div>

        <?php if ($action === 'test' && isset($result['checks'])): ?>
        <h2>연결 테스트 결과</h2>
        <div class="status-grid">
            <?php foreach ($result['checks'] as $name => $check): ?>
            <div class="status-card <?php echo $check['status']; ?>">
                <div class="status-title"><?php echo htmlspecialchars($name); ?></div>
                <div class="status-value">
                    <?php echo $check['status'] === 'ok' ? '✅' : '❌'; ?>
                    <?php echo htmlspecialchars($check['value']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($action === 'transform' && isset($result['state_vector_8d'])): ?>
        <h2>8D StateVector 결과</h2>
        <div class="state-vector">
            <?php
            $labels = ['인지명료도', '정서안정성', '참여수준', '개념숙달도', '루틴강도', '메타인지', '이탈위험', '개입준비도'];
            $i = 0;
            foreach ($result['state_vector_8d'] as $key => $value):
            ?>
            <div class="sv-item">
                <div class="sv-label"><?php echo $labels[$i] ?? $key; ?></div>
                <div class="sv-value"><?php echo number_format($value, 3); ?></div>
            </div>
            <?php $i++; endforeach; ?>
        </div>
        <?php endif; ?>

        <h2>전체 결과 (JSON)</h2>
        <pre><?php echo htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #30363d; color: #8b949e; font-size: 12px;">
            <p>📁 파일 위치: <?php echo __FILE__; ?></p>
            <p>🔗 Python 인터페이스: <?php echo PYTHON_INTERFACE; ?></p>
        </div>
    </div>
</body>
</html>
<?php
/*
 * ═══════════════════════════════════════════════════════════════════════════
 * DB 테이블 참조 목록
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * 이 브릿지에서 참조하는 테이블:
 *
 * 1. mdl_abessi_calm_log (Agent 08: 침착도)
 *    - userid: int, 사용자 ID
 *    - calm_score: float, 침착도 점수 (0.0-1.0)
 *    - calmness_level: int, 침착도 레벨 (0-10)
 *    - daily_goals: int, 일일 목표 수
 *    - review_points: int, 복습 포인트
 *    - timecreated: int, 생성 시간
 *
 * 2. mdl_abessi_problem_attempts (Agent 11: 문제노트)
 *    - userid: int, 사용자 ID
 *    - iscorrect: tinyint, 정답 여부 (0/1)
 *    - timecreated: int, 생성 시간
 *
 * 3. mdl_abessi_breaktimelog (Agent 12: 휴식 루틴)
 *    - userid: int, 사용자 ID
 *    - duration: int, 휴식 시간 (초)
 *    - timecreated: int, 생성 시간
 *
 * 4. mdl_abessi_goal_analysis (Agent 03: 목표 분석)
 *    - userid: int, 사용자 ID
 *    - target_score: int, 목표 점수
 *    - current_score: int, 현재 점수
 *    - exam_date: varchar, 시험 날짜
 *    - goal_clarity: float, 목표 명확도 (0.0-1.0)
 *    - timecreated: int, 생성 시간
 *
 * 5. mdl_abessi_pomodoro_log (Agent 09: 포모도로)
 *    - userid: int, 사용자 ID
 *    - is_completed: tinyint, 완료 여부 (0/1)
 *    - duration: int, 집중 시간 (초)
 *    - timecreated: int, 생성 시간
 *
 * 6. mdl_abessi_learning_emotion (Agent 05: 학습 감정)
 *    - userid: int, 사용자 ID
 *    - emotion_state: varchar, 감정 상태
 *    - frustration_level: float, 좌절 수준 (0.0-1.0)
 *    - motivation_level: float, 동기 수준 (0.0-1.0)
 *    - confidence_level: float, 자신감 수준 (0.0-1.0)
 *    - timecreated: int, 생성 시간
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
