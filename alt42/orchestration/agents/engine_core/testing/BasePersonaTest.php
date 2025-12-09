<?php
/**
 * BasePersonaTest.php
 *
 * 21개 에이전트의 표준화된 테스트 프레임워크 Base 클래스
 * 모든 에이전트 테스트는 이 클래스를 상속받아 구현
 *
 * @package     AugmentedTeacher
 * @subpackage  EngineCore\Testing
 * @author      AI Agent Integration Team
 * @version     1.0.0
 * @created     2025-12-03
 *
 * 서버 URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/engine_core/testing/BasePersonaTest.php
 */

namespace ALT42\Testing;

// AgentDataLayer 로드 (DB 테스트용)
// 경로: /orchestration/api/database/agent_data_layer.php
require_once(__DIR__ . '/../../../api/database/agent_data_layer.php');

use ALT42\Database\AgentDataLayer;

/**
 * Abstract Class BasePersonaTest
 *
 * 에이전트 페르소나 시스템 테스트를 위한 기본 클래스
 * 공통 테스트 메서드와 결과 집계 기능 제공
 */
abstract class BasePersonaTest
{
    // =========================================================================
    // 에이전트 정보 (하위 클래스에서 설정)
    // =========================================================================

    /** @var int 에이전트 번호 (1-21) */
    protected $agentNumber;

    /** @var string 에이전트 이름 */
    protected $agentName;

    /** @var string 에이전트 한글명 */
    protected $agentKrName;

    /** @var string 기본 경로 */
    protected $basePath;

    // =========================================================================
    // 테스트 결과
    // =========================================================================

    /** @var array 테스트 결과 배열 */
    protected $testResults = [];

    /** @var int 총 테스트 수 */
    protected $totalTests = 0;

    /** @var int 통과한 테스트 수 */
    protected $passedTests = 0;

    /** @var float 테스트 시작 시간 */
    protected $startTime;

    /** @var array 테스트 섹션별 결과 */
    protected $sections = [];

    /** @var string 현재 섹션 */
    protected $currentSection = '';

    // =========================================================================
    // 생성자
    // =========================================================================

    /**
     * BasePersonaTest 생성자
     *
     * @param int    $agentNumber 에이전트 번호
     * @param string $agentName   에이전트 이름
     * @param string $agentKrName 에이전트 한글명
     * @param string $basePath    기본 경로 (persona_system 폴더)
     */
    public function __construct(int $agentNumber, string $agentName, string $agentKrName, string $basePath)
    {
        $this->agentNumber = $agentNumber;
        $this->agentName = $agentName;
        $this->agentKrName = $agentKrName;
        $this->basePath = rtrim($basePath, '/');
        $this->startTime = microtime(true);
    }

    // =========================================================================
    // 추상 메서드 (하위 클래스에서 구현)
    // =========================================================================

    /**
     * 에이전트별 커스텀 테스트 실행
     * 하위 클래스에서 에이전트 고유의 테스트 구현
     */
    abstract protected function runCustomTests(): void;

    /**
     * 필수 파일 목록 반환
     * @return array ['상대경로' => '설명', ...]
     */
    abstract protected function getRequiredFiles(): array;

    /**
     * 필수 DB 테이블 목록 반환
     * @return array ['테이블명' => '설명', ...]
     */
    abstract protected function getRequiredTables(): array;

    // =========================================================================
    // 테스트 실행 메인
    // =========================================================================

    /**
     * 전체 테스트 실행
     */
    public function runAllTests(): void
    {
        // 1. 기본 파일 존재 테스트
        $this->setSection('files', '필수 파일 확인');
        $this->testRequiredFiles();

        // 2. engine_core 연동 테스트
        $this->setSection('engine_core', 'Engine Core 연동');
        $this->testEngineCoreIntegration();

        // 3. DB 테이블 테스트
        $this->setSection('database', 'DB 테이블 확인');
        $this->testDatabaseTables();

        // 4. 에이전트 간 통신 테스트
        $this->setSection('communication', '에이전트 통신');
        $this->testInterAgentCommunication();

        // 5. 에이전트별 커스텀 테스트
        $this->setSection('custom', '에이전트 고유 테스트');
        $this->runCustomTests();
    }

    // =========================================================================
    // 섹션 관리
    // =========================================================================

    /**
     * 현재 테스트 섹션 설정
     *
     * @param string $key  섹션 키
     * @param string $name 섹션 이름
     */
    protected function setSection(string $key, string $name): void
    {
        $this->currentSection = $key;
        $this->sections[$key] = [
            'name' => $name,
            'tests' => [],
            'passed' => 0,
            'total' => 0
        ];
    }

    // =========================================================================
    // 결과 기록
    // =========================================================================

    /**
     * 테스트 결과 기록
     *
     * @param string $name    테스트 이름
     * @param bool   $passed  통과 여부
     * @param string $message 결과 메시지
     * @param array  $details 추가 상세 정보
     */
    protected function recordTest(string $name, bool $passed, string $message = '', array $details = []): void
    {
        $this->totalTests++;
        if ($passed) {
            $this->passedTests++;
        }

        $result = [
            'name' => $name,
            'passed' => $passed,
            'message' => $message,
            'details' => $details,
            'section' => $this->currentSection,
            'time' => date('H:i:s')
        ];

        $this->testResults[] = $result;

        // 섹션별 집계
        if (isset($this->sections[$this->currentSection])) {
            $this->sections[$this->currentSection]['tests'][] = $result;
            $this->sections[$this->currentSection]['total']++;
            if ($passed) {
                $this->sections[$this->currentSection]['passed']++;
            }
        }
    }

    // =========================================================================
    // 기본 테스트 메서드
    // =========================================================================

    /**
     * 필수 파일 존재 테스트
     */
    protected function testRequiredFiles(): void
    {
        $files = $this->getRequiredFiles();

        foreach ($files as $relativePath => $description) {
            $this->testFileExists($relativePath, $description);
        }

        // 공통 필수 파일
        $commonFiles = [
            'personas.md' => '페르소나 정의',
            'rules.yaml' => '규칙 정의'
        ];

        foreach ($commonFiles as $file => $desc) {
            $this->testFileExists($file, $desc);
        }
    }

    /**
     * 파일 존재 테스트
     *
     * @param string $relativePath 상대 경로
     * @param string $description  설명
     * @return bool
     */
    protected function testFileExists(string $relativePath, string $description): bool
    {
        $fullPath = $this->basePath . '/' . $relativePath;
        $exists = file_exists($fullPath);

        $this->recordTest(
            "파일: {$description}",
            $exists,
            $exists ? "확인됨" : "파일 없음: {$relativePath}",
            ['path' => $fullPath]
        );

        return $exists;
    }

    /**
     * 디렉토리 존재 테스트
     *
     * @param string $relativePath 상대 경로
     * @param string $description  설명
     * @return bool
     */
    protected function testDirectoryExists(string $relativePath, string $description): bool
    {
        $fullPath = $this->basePath . '/' . $relativePath;
        $exists = is_dir($fullPath);

        $this->recordTest(
            "디렉토리: {$description}",
            $exists,
            $exists ? "확인됨" : "디렉토리 없음: {$relativePath}",
            ['path' => $fullPath]
        );

        return $exists;
    }

    /**
     * Engine Core 연동 테스트
     */
    protected function testEngineCoreIntegration(): void
    {
        $engineCorePath = dirname($this->basePath) . '/../engine_core/';

        // 인터페이스 확인
        $interfaces = [
            'interfaces/PersonaEngineInterface.php' => 'PersonaEngineInterface',
            'interfaces/DataContextInterface.php' => 'DataContextInterface',
            'interfaces/CommunicatorInterface.php' => 'CommunicatorInterface'
        ];

        foreach ($interfaces as $file => $name) {
            $fullPath = $engineCorePath . $file;
            $exists = file_exists($fullPath);

            $this->recordTest(
                "인터페이스: {$name}",
                $exists,
                $exists ? "연동 가능" : "engine_core 인터페이스 없음"
            );
        }

        // AbstractPersonaEngine 확인
        $abstractPath = $engineCorePath . 'base/AbstractPersonaEngine.php';
        $abstractExists = file_exists($abstractPath);

        $this->recordTest(
            "Base 클래스: AbstractPersonaEngine",
            $abstractExists,
            $abstractExists ? "상속 가능" : "Base 클래스 없음"
        );
    }

    /**
     * DB 테이블 존재 테스트
     */
    protected function testDatabaseTables(): void
    {
        $tables = $this->getRequiredTables();

        // 공통 통신 테이블 추가
        $commonTables = [
            'mdl_at_agent_messages' => '에이전트 메시지 큐',
            'mdl_at_agent_persona_state' => '페르소나 상태',
            'mdl_at_agent_heartbeat' => '하트비트'
        ];

        $allTables = array_merge($commonTables, $tables);

        foreach ($allTables as $tableName => $description) {
            $this->testTableExists($tableName, $description);
        }
    }

    /**
     * 테이블 존재 테스트
     *
     * @param string $tableName   테이블명
     * @param string $description 설명
     * @return bool
     */
    protected function testTableExists(string $tableName, string $description): bool
    {
        try {
            // Moodle config 로드 (DB 이름 필요)
            global $CFG;
            if (!isset($CFG->dbname)) {
                require_once('/home/moodle/public_html/moodle/config.php');
            }

            $sql = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.TABLES
                    WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
            $stmt = AgentDataLayer::executeQuery($sql, [$CFG->dbname, $tableName]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            $exists = (int)($row['cnt'] ?? 0) > 0;

            $this->recordTest(
                "테이블: {$description}",
                $exists,
                $exists ? "존재함" : "테이블 없음",
                ['table' => $tableName]
            );

            return $exists;

        } catch (\Exception $e) {
            $this->recordTest(
                "테이블: {$description}",
                false,
                "확인 실패: " . $e->getMessage() . " [" . __FILE__ . ":" . __LINE__ . "]"
            );
            return false;
        }
    }

    /**
     * 에이전트 간 통신 테스트
     */
    protected function testInterAgentCommunication(): void
    {
        // InterAgentBus 확인
        $busPath = dirname($this->basePath) . '/../engine_core/communication/InterAgentBus.php';
        $busExists = file_exists($busPath);

        $this->recordTest(
            "InterAgentBus 연결",
            $busExists,
            $busExists ? "통신 버스 사용 가능" : "InterAgentBus 없음"
        );

        // 하트비트 등록 테스트 (읽기 전용)
        try {
            global $CFG;
            $sql = "SELECT status, last_activity FROM `mdl_at_agent_heartbeat` WHERE nagent = ?";
            $stmt = AgentDataLayer::executeQuery($sql, [$this->agentNumber]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $registered = !empty($row);

            $this->recordTest(
                "하트비트 등록",
                $registered,
                $registered ? "상태: {$row['status']}" : "에이전트 하트비트 없음",
                ['nagent' => $this->agentNumber]
            );

        } catch (\Exception $e) {
            $this->recordTest(
                "하트비트 등록",
                false,
                "확인 실패: " . $e->getMessage()
            );
        }
    }

    // =========================================================================
    // 유틸리티 테스트 메서드
    // =========================================================================

    /**
     * 클래스 존재 테스트
     *
     * @param string $filePath  파일 경로
     * @param string $className 클래스명 (네임스페이스 포함)
     * @return bool
     */
    protected function testClassExists(string $filePath, string $className): bool
    {
        $fullPath = $this->basePath . '/' . $filePath;

        if (!file_exists($fullPath)) {
            $this->recordTest(
                "클래스: {$className}",
                false,
                "파일 없음: {$filePath}"
            );
            return false;
        }

        require_once($fullPath);
        $exists = class_exists($className);

        $this->recordTest(
            "클래스: {$className}",
            $exists,
            $exists ? "클래스 로드됨" : "클래스 찾을 수 없음"
        );

        return $exists;
    }

    /**
     * YAML 파일 파싱 테스트
     *
     * @param string $filePath YAML 파일 상대 경로
     * @return array|null
     */
    protected function testYamlParsing(string $filePath): ?array
    {
        $fullPath = $this->basePath . '/' . $filePath;

        if (!file_exists($fullPath)) {
            $this->recordTest(
                "YAML 파싱: {$filePath}",
                false,
                "파일 없음"
            );
            return null;
        }

        $content = file_get_contents($fullPath);
        $parsed = null;

        // PHP yaml 확장 시도
        if (function_exists('yaml_parse')) {
            $parsed = @yaml_parse($content);
        }

        // 간단한 규칙 카운트 (폴백)
        if (!$parsed) {
            $ruleCount = preg_match_all('/^\s+-\s+name:/m', $content);
            if ($ruleCount > 0) {
                $parsed = ['_estimated_rules' => $ruleCount];
            }
        }

        $success = !empty($parsed);

        $this->recordTest(
            "YAML 파싱: {$filePath}",
            $success,
            $success ? "파싱 성공" : "파싱 실패"
        );

        return $parsed;
    }

    /**
     * API 엔드포인트 테스트
     *
     * @param string $endpoint 상대 엔드포인트 경로
     * @param string $method   HTTP 메서드
     * @param array  $data     요청 데이터
     * @return array|null
     */
    protected function testApiEndpoint(string $endpoint, string $method = 'GET', array $data = []): ?array
    {
        $fullPath = $this->basePath . '/' . $endpoint;

        if (!file_exists($fullPath)) {
            $this->recordTest(
                "API: {$endpoint}",
                false,
                "엔드포인트 파일 없음"
            );
            return null;
        }

        $this->recordTest(
            "API: {$endpoint}",
            true,
            "엔드포인트 파일 존재"
        );

        return ['endpoint' => $endpoint, 'exists' => true];
    }

    /**
     * 템플릿 폴더 테스트
     *
     * @param string $folderPath 템플릿 폴더 상대 경로
     * @param string $extension  파일 확장자
     * @return int 템플릿 파일 수
     */
    protected function testTemplateFolder(string $folderPath, string $extension = '.txt'): int
    {
        $fullPath = $this->basePath . '/' . $folderPath;

        if (!is_dir($fullPath)) {
            $this->recordTest(
                "템플릿: {$folderPath}",
                false,
                "폴더 없음"
            );
            return 0;
        }

        $files = glob($fullPath . '/*' . $extension);
        $count = count($files);

        $this->recordTest(
            "템플릿: {$folderPath}",
            $count > 0,
            "{$count}개 템플릿 발견"
        );

        return $count;
    }

    // =========================================================================
    // 결과 출력
    // =========================================================================

    /**
     * 결과 요약 반환
     *
     * @return array
     */
    public function getSummary(): array
    {
        $duration = round((microtime(true) - $this->startTime) * 1000);
        $failed = $this->totalTests - $this->passedTests;
        $percentage = $this->totalTests > 0
            ? round(($this->passedTests / $this->totalTests) * 100)
            : 0;

        return [
            'agent_number' => $this->agentNumber,
            'agent_name' => $this->agentName,
            'agent_kr_name' => $this->agentKrName,
            'total_tests' => $this->totalTests,
            'passed_tests' => $this->passedTests,
            'failed_tests' => $failed,
            'pass_percentage' => $percentage,
            'duration_ms' => $duration,
            'sections' => $this->sections,
            'all_passed' => ($failed === 0),
            'test_time' => date('Y-m-d H:i:s')
        ];
    }

    /**
     * 모든 테스트 결과 반환
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->testResults;
    }

    /**
     * HTML 결과 페이지 렌더링
     */
    public function renderHtml(): void
    {
        $summary = $this->getSummary();
        $results = $this->getResults();

        ?>
        <!DOCTYPE html>
        <html lang="ko">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Agent<?php echo str_pad($this->agentNumber, 2, '0', STR_PAD_LEFT); ?> <?php echo htmlspecialchars($this->agentKrName); ?> - 테스트</title>
            <style>
                * { box-sizing: border-box; margin: 0; padding: 0; }
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
                    background: #f5f7fa;
                    padding: 20px;
                    line-height: 1.6;
                }
                .container { max-width: 1000px; margin: 0 auto; }
                h1 { color: #1e3a5f; margin-bottom: 10px; }
                h2 { color: #2c5282; margin: 20px 0 10px; border-bottom: 2px solid #e2e8f0; padding-bottom: 5px; }
                .subtitle { color: #718096; margin-bottom: 20px; }

                .summary {
                    background: white;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 20px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .summary-stats { display: flex; gap: 15px; margin-top: 15px; }
                .stat-box {
                    flex: 1;
                    padding: 15px;
                    border-radius: 8px;
                    text-align: center;
                }
                .stat-box.total { background: #e2e8f0; }
                .stat-box.passed { background: #c6f6d5; }
                .stat-box.failed { background: #fed7d7; }
                .stat-number { font-size: 32px; font-weight: bold; }

                .section {
                    background: white;
                    border-radius: 8px;
                    padding: 20px;
                    margin-bottom: 15px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }

                .test-item {
                    display: flex;
                    align-items: center;
                    padding: 10px;
                    border-bottom: 1px solid #e2e8f0;
                }
                .test-item:last-child { border-bottom: none; }

                .test-status {
                    width: 24px;
                    height: 24px;
                    border-radius: 50%;
                    margin-right: 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: bold;
                    color: white;
                    font-size: 12px;
                }
                .test-status.pass { background: #48bb78; }
                .test-status.fail { background: #f56565; }

                .test-name { flex: 1; font-weight: 500; }
                .test-message { color: #718096; font-size: 14px; margin-left: 15px; }

                .badge {
                    display: inline-block;
                    padding: 2px 8px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 500;
                }
                .badge.success { background: #c6f6d5; color: #276749; }
                .badge.warning { background: #fefcbf; color: #975a16; }
                .badge.error { background: #fed7d7; color: #c53030; }

                .footer {
                    text-align: center;
                    margin-top: 30px;
                    color: #718096;
                    font-size: 14px;
                }
            </style>
        </head>
        <body>
        <div class="container">
            <h1><?php echo $this->getAgentEmoji(); ?> Agent<?php echo str_pad($this->agentNumber, 2, '0', STR_PAD_LEFT); ?> <?php echo htmlspecialchars($this->agentKrName); ?></h1>
            <p class="subtitle"><?php echo htmlspecialchars($this->agentName); ?> Persona System Test</p>

            <!-- 요약 -->
            <div class="summary">
                <h2 style="margin-top: 0; border: none;">📊 테스트 요약</h2>
                <div class="summary-stats">
                    <div class="stat-box total">
                        <div class="stat-number"><?php echo $summary['total_tests']; ?></div>
                        <div>전체</div>
                    </div>
                    <div class="stat-box passed">
                        <div class="stat-number"><?php echo $summary['passed_tests']; ?></div>
                        <div>통과</div>
                    </div>
                    <div class="stat-box failed">
                        <div class="stat-number"><?php echo $summary['failed_tests']; ?></div>
                        <div>실패</div>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: center;">
                    <strong>통과율: <?php echo $summary['pass_percentage']; ?>%</strong>
                    <?php if ($summary['pass_percentage'] >= 80): ?>
                        <span class="badge success">✅ 테스트 통과</span>
                    <?php elseif ($summary['pass_percentage'] >= 50): ?>
                        <span class="badge warning">⚠️ 부분 통과</span>
                    <?php else: ?>
                        <span class="badge error">❌ 테스트 실패</span>
                    <?php endif; ?>
                    <span style="margin-left: 10px; color: #718096;">(<?php echo $summary['duration_ms']; ?>ms)</span>
                </div>
            </div>

            <!-- 섹션별 결과 -->
            <?php foreach ($this->sections as $sectionKey => $section): ?>
                <div class="section">
                    <h2><?php echo htmlspecialchars($section['name']); ?>
                        <span class="badge <?php echo $section['passed'] === $section['total'] ? 'success' : 'warning'; ?>">
                            <?php echo $section['passed']; ?>/<?php echo $section['total']; ?>
                        </span>
                    </h2>
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

            <div class="footer">
                <p>테스트 실행: <?php echo $summary['test_time']; ?></p>
                <p>BasePersonaTest Framework v1.0 | Engine Core</p>
            </div>
        </div>
        </body>
        </html>
        <?php
    }

    /**
     * JSON 결과 반환
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode([
            'summary' => $this->getSummary(),
            'results' => $this->getResults()
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * 에이전트 이모지 반환
     *
     * @return string
     */
    protected function getAgentEmoji(): string
    {
        $emojis = [
            1 => '🎯', 2 => '📅', 3 => '🎯', 4 => '🔍', 5 => '💭',
            6 => '👨‍🏫', 7 => '🎯', 8 => '🧘', 9 => '📚', 10 => '📝',
            11 => '📋', 12 => '😴', 13 => '⚠️', 14 => '🔄', 15 => '🔧',
            16 => '🤝', 17 => '⏰', 18 => '✨', 19 => '💬', 20 => '📋', 21 => '🚀'
        ];

        return $emojis[$this->agentNumber] ?? '🤖';
    }
}

/*
 * ═══════════════════════════════════════════════════════════════════════════
 * 사용 예시:
 * ═══════════════════════════════════════════════════════════════════════════
 *
 * class Agent08PersonaTest extends BasePersonaTest
 * {
 *     public function __construct()
 *     {
 *         parent::__construct(8, 'calmness', '평온도', __DIR__);
 *     }
 *
 *     protected function getRequiredFiles(): array
 *     {
 *         return [
 *             'engine/CalmnessPersonaRuleEngine.php' => '메인 엔진',
 *             'api/chat.php' => '채팅 API'
 *         ];
 *     }
 *
 *     protected function getRequiredTables(): array
 *     {
 *         return [
 *             'mdl_at_agent08_calmness_sessions' => '평온도 세션'
 *         ];
 *     }
 *
 *     protected function runCustomTests(): void
 *     {
 *         $this->testTemplateFolder('templates/C95');
 *         $this->testYamlParsing('rules.yaml');
 *     }
 * }
 *
 * // 실행
 * $test = new Agent08PersonaTest();
 * $test->runAllTests();
 * $test->renderHtml();
 *
 * ═══════════════════════════════════════════════════════════════════════════
 */
