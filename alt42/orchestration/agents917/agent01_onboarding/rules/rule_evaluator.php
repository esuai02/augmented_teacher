<?php
/**
 * 온보딩 에이전트 룰 평가기 (PHP 래퍼)
 * File: agent01_onboarding/rules/rule_evaluator.php
 * 
 * Python 룰 엔진을 PHP에서 호출하는 래퍼 클래스
 */

class OnboardingRuleEvaluator {
    
    private $ruleEnginePath;
    private $rulesFilePath;
    
    /**
     * Constructor
     * 
     * @param string|null $rulesFilePath Optional path to rules YAML file
     */
    public function __construct($rulesFilePath = null) {
        $baseDir = __DIR__;
        $this->ruleEnginePath = $baseDir . '/onboarding_rule_engine.py';
        
        if ($rulesFilePath === null) {
            $this->rulesFilePath = $baseDir . '/agent01_onboarding_rules.yaml';
        } else {
            $this->rulesFilePath = $rulesFilePath;
        }
        
        // Validate files exist
        if (!file_exists($this->ruleEnginePath)) {
            throw new Exception("Rule engine not found: {$this->ruleEnginePath} at " . __FILE__ . ":" . __LINE__);
        }
        
        if (!file_exists($this->rulesFilePath)) {
            throw new Exception("Rules file not found: {$this->rulesFilePath} at " . __FILE__ . ":" . __LINE__);
        }
    }
    
    /**
     * Evaluate rules against student context
     * 
     * @param array $context Student context data (must include student_id)
     * @return array Decision result with actions and metadata
     * @throws Exception If evaluation fails
     */
    public function evaluate($context) {
        // Validate required fields
        if (!isset($context['student_id'])) {
            throw new Exception("Missing required field: student_id at " . __FILE__ . ":" . __LINE__);
        }
        
        // Python 스크립트 파일 존재 확인
        if (!file_exists($this->ruleEnginePath)) {
            throw new Exception("Python rule engine not found: {$this->ruleEnginePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Rules 파일 존재 확인
        if (!file_exists($this->rulesFilePath)) {
            throw new Exception("Rules file not found: {$this->rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Prepare JSON input
        $jsonInput = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($jsonInput === false) {
            throw new Exception("Failed to encode context to JSON: " . json_last_error_msg() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Escape for shell command
        $escapedInput = escapeshellarg($jsonInput);
        $escapedRulesFile = escapeshellarg($this->rulesFilePath);
        
        // Build command - 절대 경로 사용
        $absoluteEnginePath = realpath($this->ruleEnginePath);
        $absoluteRulesPath = realpath($this->rulesFilePath);
        
        if ($absoluteEnginePath === false) {
            throw new Exception("Cannot resolve absolute path for rule engine: {$this->ruleEnginePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        if ($absoluteRulesPath === false) {
            throw new Exception("Cannot resolve absolute path for rules file: {$this->rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Python 버전 확인 및 선택 (3.10 우선, 없으면 3.6)
        $pythonCmd = "python3";
        $python310Check = shell_exec("python3.10 --version 2>&1");
        if (strpos($python310Check, 'Python 3.10') !== false) {
            $pythonCmd = "python3.10";
            error_log("[Rule Evaluator] Using python3.10 [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            error_log("[Rule Evaluator] Using python3 (default) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // PyYAML 모듈 경로 확인 및 PYTHONPATH 설정
        // 사용자 디렉토리에 설치된 경우를 대비하여 PYTHONPATH 추가
        $pythonPath = getenv('PYTHONPATH') ?: '';
        
        // 여러 가능한 사용자 디렉토리 확인
        $possibleHomeDirs = [
            getenv('HOME'),
            getenv('USERPROFILE'),
            '/home/moodle',
            '/home/apache',
            '/var/www'
        ];
        
        // posix 함수가 사용 가능한 경우 추가
        if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
            $euid = posix_geteuid();
            $userInfo = posix_getpwuid($euid);
            if ($userInfo && isset($userInfo['dir'])) {
                $possibleHomeDirs[] = $userInfo['dir'];
            }
        }
        
        $userSitePackagesPaths = [];
        foreach ($possibleHomeDirs as $homeDir) {
            if (empty($homeDir)) continue;
            
            // Python 3.10용 경로
            $path310 = $homeDir . '/.local/lib/python3.10/site-packages';
            if (is_dir($path310)) {
                $userSitePackagesPaths[] = $path310;
            }
            
            // 일반 Python 3용 경로
            $path3 = $homeDir . '/.local/lib/python3/site-packages';
            if (is_dir($path3) && !in_array($path3, $userSitePackagesPaths)) {
                $userSitePackagesPaths[] = $path3;
            }
        }
        
        // PYTHONPATH에 추가
        foreach ($userSitePackagesPaths as $path) {
            $pythonPath = $pythonPath ? ($pythonPath . ':' . $path) : $path;
            error_log("[Rule Evaluator] Adding user site-packages to PYTHONPATH: {$path} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // 기본 사용자 디렉토리 (테스트용)
        $homeDir = $possibleHomeDirs[0] ?? '/home/moodle';
        $userSitePackages = $homeDir . '/.local/lib/python3.10/site-packages';
        
        // Python 스크립트 실행 전에 yaml 모듈 import 테스트
        $yamlTestCmd = $pythonCmd . " -c 'import sys; sys.path.insert(0, \"" . addslashes($userSitePackages) . "\"); import yaml; print(yaml.__version__)' 2>&1";
        $yamlTestOutput = shell_exec($yamlTestCmd);
        if (strpos($yamlTestOutput, 'ModuleNotFoundError') !== false) {
            error_log("[Rule Evaluator] WARNING: yaml module not found even with path adjustment. Output: {$yamlTestOutput} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            error_log("[Rule Evaluator] yaml module found. Version: " . trim($yamlTestOutput) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // 임시 파일로 stderr 저장
        $stderrFile = sys_get_temp_dir() . '/rule_engine_stderr_' . uniqid() . '.log';
        
        // PYTHONPATH를 포함한 명령어 구성
        // stderr는 별도 파일로, stdout만 캡처
        if (!empty($pythonPath)) {
            $command = "PYTHONPATH=" . escapeshellarg($pythonPath) . " " . $pythonCmd . " " . escapeshellarg($absoluteEnginePath) . " " . escapeshellarg($jsonInput) . " " . escapeshellarg($absoluteRulesPath) . " 2>" . escapeshellarg($stderrFile);
        } else {
            $command = $pythonCmd . " " . escapeshellarg($absoluteEnginePath) . " " . escapeshellarg($jsonInput) . " " . escapeshellarg($absoluteRulesPath) . " 2>" . escapeshellarg($stderrFile);
        }
        
        // 디버깅: 명령어 로그
        error_log("[Rule Evaluator] Command: " . $command . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[Rule Evaluator] JSON Input length: " . strlen($jsonInput) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // Execute with timeout (25초 - PHP 타임아웃보다 짧게)
        $startTime = microtime(true);
        $output = shell_exec($command);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // stderr 읽기 및 로그 기록
        $stderr = '';
        if (file_exists($stderrFile)) {
            $stderr = file_get_contents($stderrFile);
            error_log("[Rule Evaluator] Python stderr: " . substr($stderr, 0, 2000) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            @unlink($stderrFile); // 임시 파일 삭제
        }
        
        // 디버깅: 출력 로그
        error_log("[Rule Evaluator] Execution time: {$executionTime}ms [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[Rule Evaluator] Output length: " . (strlen($output ?? '') ?: 'null') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        if ($output !== null && strlen($output) > 0) {
            error_log("[Rule Evaluator] Output preview: " . substr($output, 0, 1000) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            error_log("[Rule Evaluator] WARNING: Output is null or empty! [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // shell_exec가 null을 반환하는 경우 (명령 실행 실패 또는 출력 없음)
        if ($output === null) {
            // Python 실행 가능 여부 확인
            $pythonCheck = shell_exec("which python3 2>&1");
            if (empty($pythonCheck)) {
                throw new Exception("python3 not found. Please install Python 3. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // 명령 실행 테스트
            $testOutput = shell_exec("python3 --version 2>&1");
            if (empty($testOutput)) {
                throw new Exception("Cannot execute python3. Command: {$command} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            // stderr에 에러가 있는지 확인
            if (!empty($stderr)) {
                throw new Exception("Rule engine execution failed. stderr: " . substr($stderr, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            throw new Exception("Rule engine execution failed. Command: {$command} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // 빈 출력 체크
        if (trim($output) === '') {
            // stderr에 에러가 있는지 확인
            if (!empty($stderr)) {
                throw new Exception("Rule engine returned empty output but stderr has: " . substr($stderr, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            throw new Exception("Rule engine returned empty output. Command: {$command} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Python 에러 체크 (stderr에 Traceback이 있으면 에러)
        if (!empty($stderr) && (strpos($stderr, 'Traceback') !== false || strpos($stderr, 'Error') !== false || strpos($stderr, 'ModuleNotFoundError') !== false)) {
            // yaml 모듈 에러인 경우 특별 처리
            if (strpos($stderr, 'ModuleNotFoundError') !== false && strpos($stderr, 'yaml') !== false) {
                error_log("Rule engine stderr: " . $stderr . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                
                // PyYAML 자동 설치 시도
                $installAttempted = false;
                $installSuccess = false;
                
                // Python 버전 확인 및 설치 시도
                $pythonCmds = ['python3.10', 'python3', 'python3.6', 'python3.7', 'python3.8', 'python3.9'];
                foreach ($pythonCmds as $pythonCmd) {
                    $pythonCheck = shell_exec("which {$pythonCmd} 2>&1");
                    if (!empty($pythonCheck)) {
                        // PyYAML 설치 시도 (--user 옵션으로 사용자 디렉토리에 설치)
                        error_log("[Rule Evaluator] Attempting to install PyYAML for {$pythonCmd} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        $installOutput = shell_exec("{$pythonCmd} -m pip install --user PyYAML 2>&1");
                        $installAttempted = true;
                        
                        // 설치 확인
                        $verifyCheck = shell_exec("{$pythonCmd} -c 'import yaml; print(yaml.__version__)' 2>&1");
                        if (strpos($verifyCheck, 'ModuleNotFoundError') === false && !empty(trim($verifyCheck))) {
                            error_log("[Rule Evaluator] PyYAML installed successfully for {$pythonCmd} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                            $installSuccess = true;
                            // 재시도 (stderr 분리)
                            $retryStderrFile = sys_get_temp_dir() . '/rule_engine_stderr_retry_' . uniqid() . '.log';
                            $retryCommand = "PYTHONPATH=" . escapeshellarg($pythonPath) . " " . $pythonCmd . " " . escapeshellarg($absoluteEnginePath) . " " . escapeshellarg($jsonInput) . " " . escapeshellarg($absoluteRulesPath) . " 2>" . escapeshellarg($retryStderrFile);
                            $output = shell_exec($retryCommand);
                            $retryStderr = '';
                            if (file_exists($retryStderrFile)) {
                                $retryStderr = file_get_contents($retryStderrFile);
                                @unlink($retryStderrFile);
                            }
                            if ($output && trim($output) !== '' && strpos($retryStderr, 'ModuleNotFoundError') === false) {
                                // 성공적으로 재시도됨
                                break;
                            }
                        }
                    }
                }
                
                if (!$installSuccess) {
                    $errorMsg = "Python yaml 모듈이 설치되지 않았습니다. ";
                    if ($installAttempted) {
                        $errorMsg .= "자동 설치가 실패했습니다. ";
                    }
                    $errorMsg .= "다음 방법 중 하나를 시도해주세요:\n";
                    $errorMsg .= "1. 서버에 SSH 접속 후: pip3 install --user PyYAML\n";
                    $errorMsg .= "2. 또는 웹 인터페이스: " . dirname($_SERVER['PHP_SELF']) . "/install_pyyaml.php\n";
                    $errorMsg .= "[File: " . __FILE__ . ", Line: " . __LINE__ . "]";
                    throw new Exception($errorMsg);
                }
            }
            
            error_log("Rule engine stderr: " . $stderr . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("Command: " . $command . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw new Exception("Python 스크립트 실행 오류: " . substr($stderr, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Parse JSON output (stdout만 파싱)
        // 출력에서 JSON 부분만 추출 (중괄호로 시작하는 부분)
        $jsonStart = strpos($output, '{');
        $jsonEnd = strrpos($output, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $jsonOutput = substr($output, $jsonStart, $jsonEnd - $jsonStart + 1);
        } else {
            $jsonOutput = $output;
        }
        
        $result = json_decode($jsonOutput, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Rule engine stdout: " . $output . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("Rule engine stderr: " . $stderr . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            error_log("Command: " . $command . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            throw new Exception("Invalid JSON response from rule engine: " . json_last_error_msg() . ". Output: " . substr($output, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        return $result;
    }
    
    /**
     * Get rules summary (for debugging)
     * 
     * @return array Rules summary
     */
    public function getRulesSummary() {
        $command = "python3 {$this->ruleEnginePath} " . escapeshellarg('{"student_id": 0}') . " " . escapeshellarg($this->rulesFilePath) . " 2>&1";
        $output = shell_exec($command);
        
        // This is a simplified version - you might want to add a separate method in Python
        return [
            'rules_file' => $this->rulesFilePath,
            'engine_path' => $this->ruleEnginePath
        ];
    }
}

// Example usage:
/*
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

try {
    $evaluator = new OnboardingRuleEvaluator();
    
    // Prepare student context
    $context = [
        'student_id' => $USER->id,
        'math_level' => '수학이 어려워요',
        'math_confidence' => 4,
        'exam_style' => '벼락치기',
        'parent_style' => '적극 개입',
        'study_hours_per_week' => 8,
        'goals' => [
            'long_term' => '경시대회 준비해 보기'
        ],
        'advanced_progress' => '공통수학1',
        'concept_progress' => '중등3-1',
        'study_style' => '개념 정리 위주'
    ];
    
    // Evaluate rules
    $decision = $evaluator->evaluate($context);
    
    // Process actions
    foreach ($decision['actions'] as $action) {
        // Handle each action
        echo "Action: " . json_encode($action, JSON_UNESCAPED_UNICODE) . "\n";
    }
    
    echo "Decision: " . json_encode($decision, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Rule evaluation error: " . $e->getMessage() . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
    echo "Error: " . $e->getMessage();
}
*/

