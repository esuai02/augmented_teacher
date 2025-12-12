<?php
/**
 * TestRunner.php
 *
 * 21개 에이전트의 테스트를 일괄 실행하고 결과를 집계하는 테스트 러너
 * BasePersonaTest를 상속한 모든 테스트를 자동으로 발견하고 실행
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/testing/TestRunner.php
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/BasePersonaTest.php');
require_once(__DIR__ . '/../config/engine_config.php');

/**
 * Class TestRunner
 *
 * 일괄 테스트 실행 및 결과 집계
 */
class TestRunner
{
    /** @var string 에이전트 루트 디렉토리 */
    private $agentsRoot;

    /** @var array 테스트 결과 모음 */
    private $results = [];

    /** @var array 발견된 테스트 파일 목록 */
    private $discoveredTests = [];

    /** @var int 전체 통과 수 */
    private $totalPassed = 0;

    /** @var int 전체 실패 수 */
    private $totalFailed = 0;

    /** @var int 전체 경고 수 */
    private $totalWarnings = 0;

    /** @var int 전체 건너뜀 수 */
    private $totalSkipped = 0;

    /** @var float 시작 시간 */
    private $startTime;

    /** @var array 에이전트 목록 (1-21) */
    private $agentList = [];

    /** @var bool 상세 출력 여부 */
    private $verbose = false;

    /** @var array 실행 옵션 */
    private $options = [];

    /**
     * TestRunner 생성자
     *
     * @param string $agentsRoot 에이전트 루트 디렉토리
     * @param array  $options    실행 옵션
     */
    public function __construct(string $agentsRoot = null, array $options = [])
    {
        $this->agentsRoot = $agentsRoot ?? dirname(__DIR__, 2);
        $this->options = array_merge([
            'verbose' => false,
            'agents' => null,           // null=전체, 배열=지정 에이전트만
            'skip_agents' => [],        // 건너뛸 에이전트
            'timeout' => 30,            // 각 테스트 타임아웃 (초)
            'stop_on_failure' => false, // 실패 시 중단 여부
            'parallel' => false,        // 병렬 실행 (미구현)
            'output_format' => 'html',  // html, json, text
        ], $options);

        $this->verbose = $this->options['verbose'];
        $this->buildAgentList();
    }

    /**
     * 에이전트 목록 구성
     */
    private function buildAgentList(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $info = get_agent_info($i);
            if ($info) {
                $this->agentList[$i] = $info;
            }
        }
    }

    /**
     * 테스트 파일 자동 발견
     *
     * @return array 발견된 테스트 파일 목록
     */
    public function discoverTests(): array
    {
        $this->discoveredTests = [];

        foreach ($this->agentList as $nagent => $info) {
            // 건너뛸 에이전트 확인
            if (in_array($nagent, $this->options['skip_agents'])) {
                continue;
            }

            // 특정 에이전트만 실행하는 경우
            if ($this->options['agents'] !== null && !in_array($nagent, $this->options['agents'])) {
                continue;
            }

            // 에이전트 폴더 경로 구성
            $agentFolder = sprintf('%02d', $nagent);
            $agentPath = $this->agentsRoot . "/agent{$agentFolder}_{$info['name']}/persona_system";

            // test.php 파일 확인
            $testFile = $agentPath . '/test.php';
            if (file_exists($testFile)) {
                $this->discoveredTests[$nagent] = [
                    'agent_number' => $nagent,
                    'agent_name' => $info['name'],
                    'agent_kr_name' => $info['kr_name'],
                    'test_file' => $testFile,
                    'agent_path' => $agentPath,
                    'status' => 'pending',
                ];
            } else {
                $this->discoveredTests[$nagent] = [
                    'agent_number' => $nagent,
                    'agent_name' => $info['name'],
                    'agent_kr_name' => $info['kr_name'],
                    'test_file' => null,
                    'agent_path' => $agentPath,
                    'status' => 'no_test',
                ];
            }
        }

        return $this->discoveredTests;
    }

    /**
     * 모든 발견된 테스트 실행
     *
     * @return array 테스트 결과
     */
    public function runAll(): array
    {
        $this->startTime = microtime(true);
        $this->results = [];
        $this->totalPassed = 0;
        $this->totalFailed = 0;
        $this->totalWarnings = 0;
        $this->totalSkipped = 0;

        // 테스트 발견
        if (empty($this->discoveredTests)) {
            $this->discoverTests();
        }

        foreach ($this->discoveredTests as $nagent => $testInfo) {
            if ($testInfo['test_file'] === null) {
                $this->results[$nagent] = [
                    'agent_number' => $nagent,
                    'agent_name' => $testInfo['agent_name'],
                    'agent_kr_name' => $testInfo['agent_kr_name'],
                    'status' => 'skipped',
                    'reason' => 'test.php 파일 없음',
                    'passed' => 0,
                    'failed' => 0,
                    'warnings' => 0,
                    'duration' => 0,
                    'sections' => [],
                ];
                $this->totalSkipped++;
                continue;
            }

            // 개별 테스트 실행
            $result = $this->runAgentTest($nagent, $testInfo);
            $this->results[$nagent] = $result;

            // 통계 업데이트
            $this->totalPassed += $result['passed'];
            $this->totalFailed += $result['failed'];
            $this->totalWarnings += $result['warnings'];

            // 실패 시 중단 옵션
            if ($this->options['stop_on_failure'] && $result['failed'] > 0) {
                break;
            }
        }

        return $this->results;
    }

    /**
     * 개별 에이전트 테스트 실행
     *
     * @param int   $nagent   에이전트 번호
     * @param array $testInfo 테스트 정보
     * @return array 테스트 결과
     */
    private function runAgentTest(int $nagent, array $testInfo): array
    {
        $startTime = microtime(true);

        $result = [
            'agent_number' => $nagent,
            'agent_name' => $testInfo['agent_name'],
            'agent_kr_name' => $testInfo['agent_kr_name'],
            'status' => 'unknown',
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'duration' => 0,
            'sections' => [],
            'error' => null,
        ];

        try {
            // BasePersonaTest 기반 테스트 클래스 찾기
            $testClass = $this->findTestClass($nagent, $testInfo);

            if ($testClass) {
                // BasePersonaTest 상속 클래스 실행
                $testInstance = new $testClass();
                $testResult = $testInstance->run();

                $result['passed'] = $testResult['summary']['passed'];
                $result['failed'] = $testResult['summary']['failed'];
                $result['warnings'] = $testResult['summary']['warnings'];
                $result['sections'] = $testResult['sections'];
                $result['status'] = $result['failed'] > 0 ? 'failed' : 'passed';
            } else {
                // 레거시 test.php 실행 (호환성)
                $legacyResult = $this->runLegacyTest($testInfo['test_file']);
                $result = array_merge($result, $legacyResult);
            }

        } catch (Throwable $e) {
            $result['status'] = 'error';
            $result['error'] = sprintf(
                "[TestRunner.php:%d] Agent%02d 테스트 오류: %s",
                __LINE__,
                $nagent,
                $e->getMessage()
            );
            $result['failed'] = 1;
        }

        $result['duration'] = round((microtime(true) - $startTime) * 1000, 2);

        return $result;
    }

    /**
     * BasePersonaTest 상속 테스트 클래스 찾기
     *
     * @param int   $nagent   에이전트 번호
     * @param array $testInfo 테스트 정보
     * @return string|null 테스트 클래스 이름 또는 null
     */
    private function findTestClass(int $nagent, array $testInfo): ?string
    {
        $agentFolder = sprintf('%02d', $nagent);
        $className = "Agent{$agentFolder}PersonaTest";

        // 클래스가 이미 로드되어 있는지 확인
        if (class_exists($className)) {
            if (is_subclass_of($className, 'BasePersonaTest')) {
                return $className;
            }
        }

        // test.php 파일에서 BasePersonaTest 상속 클래스 찾기
        $testFile = $testInfo['test_file'];
        if (!file_exists($testFile)) {
            return null;
        }

        $content = file_get_contents($testFile);

        // BasePersonaTest를 상속하는지 확인
        if (strpos($content, 'extends BasePersonaTest') !== false) {
            require_once($testFile);

            // 클래스 이름 찾기
            if (preg_match('/class\s+(\w+)\s+extends\s+BasePersonaTest/', $content, $matches)) {
                $foundClass = $matches[1];
                if (class_exists($foundClass)) {
                    return $foundClass;
                }
            }
        }

        return null;
    }

    /**
     * 레거시 test.php 실행 (BasePersonaTest 미사용)
     *
     * @param string $testFile 테스트 파일 경로
     * @return array 실행 결과
     */
    private function runLegacyTest(string $testFile): array
    {
        ob_start();

        try {
            // 테스트 파일 실행 (출력 캡처)
            include($testFile);
            $output = ob_get_clean();

            // 출력에서 결과 파싱 시도
            $passed = 0;
            $failed = 0;
            $warnings = 0;

            // 성공/실패 패턴 검색
            $passed += preg_match_all('/✓|PASS|성공|OK/u', $output);
            $failed += preg_match_all('/✗|FAIL|실패|ERROR/u', $output);
            $warnings += preg_match_all('/⚠|WARN|경고/u', $output);

            return [
                'status' => $failed > 0 ? 'failed' : ($passed > 0 ? 'passed' : 'unknown'),
                'passed' => $passed,
                'failed' => $failed,
                'warnings' => $warnings,
                'legacy' => true,
                'output' => $this->verbose ? $output : null,
            ];

        } catch (Throwable $e) {
            ob_end_clean();
            return [
                'status' => 'error',
                'passed' => 0,
                'failed' => 1,
                'warnings' => 0,
                'legacy' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * 특정 에이전트 테스트만 실행
     *
     * @param int $nagent 에이전트 번호
     * @return array 테스트 결과
     */
    public function runAgent(int $nagent): array
    {
        if (!isset($this->discoveredTests[$nagent])) {
            $this->discoverTests();
        }

        if (!isset($this->discoveredTests[$nagent])) {
            return [
                'status' => 'error',
                'error' => "Agent{$nagent} 테스트를 찾을 수 없습니다",
            ];
        }

        return $this->runAgentTest($nagent, $this->discoveredTests[$nagent]);
    }

    /**
     * 결과 요약 생성
     *
     * @return array 요약 정보
     */
    public function getSummary(): array
    {
        $totalDuration = microtime(true) - ($this->startTime ?? microtime(true));

        $agentsWithTests = 0;
        $agentsWithoutTests = 0;
        $agentsPassed = 0;
        $agentsFailed = 0;

        foreach ($this->results as $result) {
            if ($result['status'] === 'skipped') {
                $agentsWithoutTests++;
            } else {
                $agentsWithTests++;
                if ($result['failed'] === 0) {
                    $agentsPassed++;
                } else {
                    $agentsFailed++;
                }
            }
        }

        return [
            'total_agents' => count($this->agentList),
            'agents_with_tests' => $agentsWithTests,
            'agents_without_tests' => $agentsWithoutTests,
            'agents_passed' => $agentsPassed,
            'agents_failed' => $agentsFailed,
            'total_tests_passed' => $this->totalPassed,
            'total_tests_failed' => $this->totalFailed,
            'total_tests_warnings' => $this->totalWarnings,
            'total_skipped' => $this->totalSkipped,
            'total_duration' => round($totalDuration * 1000, 2),
            'success_rate' => $agentsWithTests > 0
                ? round(($agentsPassed / $agentsWithTests) * 100, 1)
                : 0,
        ];
    }

    /**
     * 결과를 HTML로 렌더링
     *
     * @return string HTML 출력
     */
    public function renderHtml(): string
    {
        $summary = $this->getSummary();

        $html = '<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TestRunner - 21 Agents Test Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            line-height: 1.6;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            color: #58a6ff;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        h1::before { content: "🧪"; }

        /* Summary Cards */
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .summary-card.success { border-color: #238636; }
        .summary-card.failure { border-color: #da3633; }
        .summary-card.warning { border-color: #d29922; }
        .summary-card.info { border-color: #58a6ff; }
        .summary-value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .summary-card.success .summary-value { color: #3fb950; }
        .summary-card.failure .summary-value { color: #f85149; }
        .summary-card.warning .summary-value { color: #d29922; }
        .summary-card.info .summary-value { color: #58a6ff; }
        .summary-label {
            font-size: 12px;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Progress Bar */
        .progress-bar {
            background: #21262d;
            border-radius: 4px;
            height: 20px;
            margin-bottom: 30px;
            overflow: hidden;
            display: flex;
        }
        .progress-segment {
            height: 100%;
            transition: width 0.3s;
        }
        .progress-passed { background: #238636; }
        .progress-failed { background: #da3633; }
        .progress-skipped { background: #484f58; }

        /* Agent Grid */
        .agent-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .agent-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            overflow: hidden;
        }
        .agent-card.passed { border-left: 4px solid #238636; }
        .agent-card.failed { border-left: 4px solid #da3633; }
        .agent-card.skipped { border-left: 4px solid #484f58; }
        .agent-card.error { border-left: 4px solid #f85149; }

        .agent-header {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #30363d;
        }
        .agent-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .agent-number {
            background: #21262d;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
        }
        .agent-name {
            font-weight: 600;
            color: #c9d1d9;
        }
        .agent-kr-name {
            font-size: 12px;
            color: #8b949e;
        }

        .agent-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-passed { background: #238636; color: white; }
        .badge-failed { background: #da3633; color: white; }
        .badge-skipped { background: #484f58; color: #8b949e; }
        .badge-error { background: #f85149; color: white; }

        .agent-stats {
            padding: 15px;
            display: flex;
            gap: 20px;
        }
        .stat {
            display: flex;
            flex-direction: column;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
        }
        .stat-label {
            font-size: 11px;
            color: #8b949e;
            text-transform: uppercase;
        }
        .stat.passed .stat-value { color: #3fb950; }
        .stat.failed .stat-value { color: #f85149; }
        .stat.warnings .stat-value { color: #d29922; }

        .agent-duration {
            padding: 0 15px 15px;
            font-size: 12px;
            color: #8b949e;
        }

        /* Footer */
        .footer {
            text-align: center;
            color: #8b949e;
            font-size: 12px;
            padding: 20px;
            border-top: 1px solid #30363d;
            margin-top: 30px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .summary-grid { grid-template-columns: repeat(2, 1fr); }
            .agent-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>21 Agents Test Dashboard</h1>

        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card info">
                <div class="summary-value">' . $summary['total_agents'] . '</div>
                <div class="summary-label">전체 에이전트</div>
            </div>
            <div class="summary-card success">
                <div class="summary-value">' . $summary['agents_passed'] . '</div>
                <div class="summary-label">테스트 통과</div>
            </div>
            <div class="summary-card failure">
                <div class="summary-value">' . $summary['agents_failed'] . '</div>
                <div class="summary-label">테스트 실패</div>
            </div>
            <div class="summary-card warning">
                <div class="summary-value">' . $summary['agents_without_tests'] . '</div>
                <div class="summary-label">테스트 없음</div>
            </div>
            <div class="summary-card info">
                <div class="summary-value">' . $summary['success_rate'] . '%</div>
                <div class="summary-label">성공률</div>
            </div>
            <div class="summary-card info">
                <div class="summary-value">' . number_format($summary['total_duration'], 0) . 'ms</div>
                <div class="summary-label">총 실행 시간</div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="progress-bar">';

        $total = $summary['agents_passed'] + $summary['agents_failed'] + $summary['agents_without_tests'];
        if ($total > 0) {
            $passedWidth = ($summary['agents_passed'] / $total) * 100;
            $failedWidth = ($summary['agents_failed'] / $total) * 100;
            $skippedWidth = ($summary['agents_without_tests'] / $total) * 100;

            $html .= '<div class="progress-segment progress-passed" style="width: ' . $passedWidth . '%"></div>';
            $html .= '<div class="progress-segment progress-failed" style="width: ' . $failedWidth . '%"></div>';
            $html .= '<div class="progress-segment progress-skipped" style="width: ' . $skippedWidth . '%"></div>';
        }

        $html .= '</div>

        <!-- Agent Cards -->
        <div class="agent-grid">';

        foreach ($this->results as $nagent => $result) {
            $statusClass = $result['status'];
            if ($statusClass === 'unknown') {
                $statusClass = 'skipped';
            }

            $badgeClass = 'badge-' . $statusClass;
            $badgeText = [
                'passed' => 'PASSED',
                'failed' => 'FAILED',
                'skipped' => 'SKIPPED',
                'error' => 'ERROR',
            ][$statusClass] ?? 'UNKNOWN';

            $html .= '
            <div class="agent-card ' . $statusClass . '">
                <div class="agent-header">
                    <div class="agent-title">
                        <span class="agent-number">Agent' . sprintf('%02d', $nagent) . '</span>
                        <div>
                            <div class="agent-name">' . htmlspecialchars($result['agent_name']) . '</div>
                            <div class="agent-kr-name">' . htmlspecialchars($result['agent_kr_name']) . '</div>
                        </div>
                    </div>
                    <span class="agent-badge ' . $badgeClass . '">' . $badgeText . '</span>
                </div>
                <div class="agent-stats">
                    <div class="stat passed">
                        <span class="stat-value">' . $result['passed'] . '</span>
                        <span class="stat-label">Passed</span>
                    </div>
                    <div class="stat failed">
                        <span class="stat-value">' . $result['failed'] . '</span>
                        <span class="stat-label">Failed</span>
                    </div>
                    <div class="stat warnings">
                        <span class="stat-value">' . $result['warnings'] . '</span>
                        <span class="stat-label">Warnings</span>
                    </div>
                </div>
                <div class="agent-duration">⏱ ' . number_format($result['duration'], 2) . 'ms</div>
            </div>';
        }

        $html .= '</div>

        <div class="footer">
            Engine Core Test Runner v1.0.0 | 실행: ' . date('Y-m-d H:i:s') . '
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * 결과를 JSON으로 반환
     *
     * @return string JSON 출력
     */
    public function renderJson(): string
    {
        return json_encode([
            'summary' => $this->getSummary(),
            'results' => $this->results,
            'discovered_tests' => $this->discoveredTests,
            'timestamp' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 결과를 텍스트로 반환
     *
     * @return string 텍스트 출력
     */
    public function renderText(): string
    {
        $summary = $this->getSummary();

        $text = "═══════════════════════════════════════════════════════════════\n";
        $text .= "  21 Agents Test Runner - Results\n";
        $text .= "═══════════════════════════════════════════════════════════════\n\n";

        $text .= sprintf("전체 에이전트: %d\n", $summary['total_agents']);
        $text .= sprintf("테스트 통과: %d / 실패: %d / 건너뜀: %d\n",
            $summary['agents_passed'],
            $summary['agents_failed'],
            $summary['agents_without_tests']
        );
        $text .= sprintf("성공률: %.1f%%\n", $summary['success_rate']);
        $text .= sprintf("총 실행 시간: %.2fms\n\n", $summary['total_duration']);

        $text .= "───────────────────────────────────────────────────────────────\n";
        $text .= "  개별 결과\n";
        $text .= "───────────────────────────────────────────────────────────────\n\n";

        foreach ($this->results as $nagent => $result) {
            // PHP 7.1 호환 (match → switch)
            switch ($result['status']) {
                case 'passed': $icon = '✓'; break;
                case 'failed': $icon = '✗'; break;
                case 'skipped': $icon = '○'; break;
                case 'error': $icon = '!'; break;
                default: $icon = '?';
            }

            $text .= sprintf(
                "%s Agent%02d (%s): %s - P:%d F:%d W:%d (%.2fms)\n",
                $icon,
                $nagent,
                $result['agent_kr_name'],
                strtoupper($result['status']),
                $result['passed'],
                $result['failed'],
                $result['warnings'],
                $result['duration']
            );

            if (isset($result['error']) && $result['error']) {
                $text .= "    └─ Error: " . $result['error'] . "\n";
            }
        }

        $text .= "\n═══════════════════════════════════════════════════════════════\n";
        $text .= "  실행 완료: " . date('Y-m-d H:i:s') . "\n";
        $text .= "═══════════════════════════════════════════════════════════════\n";

        return $text;
    }

    /**
     * 결과 렌더링 (옵션에 따른 포맷)
     *
     * @return string 렌더링된 결과
     */
    public function render(): string
    {
        // PHP 7.1 호환 (match → switch)
        switch ($this->options['output_format']) {
            case 'json':
                return $this->renderJson();
            case 'text':
                return $this->renderText();
            default:
                return $this->renderHtml();
        }
    }

    // =========================================================================
    // Getter 메서드
    // =========================================================================

    public function getResults(): array
    {
        return $this->results;
    }

    public function getDiscoveredTests(): array
    {
        return $this->discoveredTests;
    }

    public function getTotalPassed(): int
    {
        return $this->totalPassed;
    }

    public function getTotalFailed(): int
    {
        return $this->totalFailed;
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * // 1. 기본 실행 (전체 에이전트)
 * $runner = new TestRunner();
 * $runner->discoverTests();
 * $runner->runAll();
 * echo $runner->render();
 *
 * // 2. 특정 에이전트만 실행
 * $runner = new TestRunner(null, [
 *     'agents' => [1, 5, 8, 11],
 * ]);
 * $runner->runAll();
 * echo $runner->renderHtml();
 *
 * // 3. JSON 출력
 * $runner = new TestRunner(null, [
 *     'output_format' => 'json',
 *     'verbose' => true,
 * ]);
 * $runner->runAll();
 * header('Content-Type: application/json');
 * echo $runner->renderJson();
 *
 * // 4. 개별 에이전트 테스트
 * $runner = new TestRunner();
 * $result = $runner->runAgent(5);
 * print_r($result);
 *
 * // 5. 프로그래밍 방식 결과 접근
 * $runner = new TestRunner();
 * $runner->runAll();
 * $summary = $runner->getSummary();
 * if ($summary['success_rate'] < 80) {
 *     // 알림 발송
 * }
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
