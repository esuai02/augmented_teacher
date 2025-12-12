<?php
/**
 * 시험 일정 에이전트 룰 평가기 (PHP 래퍼)
 * File: agent02_exam_schedule/rules/rule_evaluator.php
 * 
 * Python 룰 엔진을 PHP에서 호출하는 래퍼 클래스
 */

class ExamScheduleRuleEvaluator {
    
    private $ruleEnginePath;
    private $rulesFilePath;
    
    /**
     * Constructor
     * 
     * @param string|null $rulesFilePath Optional path to rules YAML file
     */
    public function __construct($rulesFilePath = null) {
        $baseDir = __DIR__;
        // Python 스크립트 경로 확인 (여러 가능성 시도)
        $possiblePaths = [
            $baseDir . '/exam_schedule_rule_engine.py',
            $baseDir . '/rule_engine.py',
            $baseDir . '/../agent01_onboarding/rules/onboarding_rule_engine.py' // 공용 스크립트 사용 가능
        ];
        
        $this->ruleEnginePath = null;
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $this->ruleEnginePath = $path;
                error_log("[ExamScheduleRuleEvaluator] Found rule engine at: {$path} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                break;
            }
        }
        
        if ($this->ruleEnginePath === null) {
            // 기본값으로 첫 번째 경로 사용
            $this->ruleEnginePath = $possiblePaths[0];
            error_log("[ExamScheduleRuleEvaluator] Using default path: {$this->ruleEnginePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        if ($rulesFilePath === null) {
            $this->rulesFilePath = $baseDir . '/rules.yaml';
        } else {
            $this->rulesFilePath = $rulesFilePath;
        }
        
        // Rules 파일 존재 확인만 (Python 스크립트는 실행 시 확인)
        if (!file_exists($this->rulesFilePath)) {
            throw new Exception("Rules file not found: {$this->rulesFilePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            throw new Exception("Missing required field: student_id [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Python 스크립트 파일 존재 확인 (실행 시점에 다시 확인)
        if (!file_exists($this->ruleEnginePath)) {
            // 공용 스크립트 사용 시도
            $commonScriptPath = __DIR__ . '/../agent01_onboarding/rules/onboarding_rule_engine.py';
            if (file_exists($commonScriptPath)) {
                $this->ruleEnginePath = $commonScriptPath;
                error_log("[ExamScheduleRuleEvaluator] Using common rule engine: {$commonScriptPath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            } else {
                throw new Exception("Python rule engine not found: {$this->ruleEnginePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
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
            error_log("[ExamScheduleRuleEvaluator] Using python3.10 [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            error_log("[ExamScheduleRuleEvaluator] Using python3 (default) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // PyYAML 모듈 경로 확인 및 PYTHONPATH 설정
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
            error_log("[ExamScheduleRuleEvaluator] Adding user site-packages to PYTHONPATH: {$path} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // 임시 파일로 stderr 저장
        $stderrFile = sys_get_temp_dir() . '/rule_engine_stderr_' . uniqid() . '.log';
        
        // PYTHONPATH를 포함한 명령어 구성
        if (!empty($pythonPath)) {
            $command = "PYTHONPATH=" . escapeshellarg($pythonPath) . " " . $pythonCmd . " " . escapeshellarg($absoluteEnginePath) . " " . escapeshellarg($jsonInput) . " " . escapeshellarg($absoluteRulesPath) . " 2>" . escapeshellarg($stderrFile);
        } else {
            $command = $pythonCmd . " " . escapeshellarg($absoluteEnginePath) . " " . escapeshellarg($jsonInput) . " " . escapeshellarg($absoluteRulesPath) . " 2>" . escapeshellarg($stderrFile);
        }
        
        // 디버깅: 명령어 로그
        error_log("[ExamScheduleRuleEvaluator] Command: " . $command . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[ExamScheduleRuleEvaluator] JSON Input length: " . strlen($jsonInput) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // Execute with timeout
        $startTime = microtime(true);
        $output = shell_exec($command);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // stderr 읽기 및 로그 기록
        $stderr = '';
        if (file_exists($stderrFile)) {
            $stderr = file_get_contents($stderrFile);
            error_log("[ExamScheduleRuleEvaluator] Python stderr: " . substr($stderr, 0, 2000) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            @unlink($stderrFile);
        }
        
        // 디버깅: 출력 로그
        error_log("[ExamScheduleRuleEvaluator] Execution time: {$executionTime}ms [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[ExamScheduleRuleEvaluator] Output length: " . (strlen($output ?? '') ?: 'null') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        if ($output !== null && strlen($output) > 0) {
            error_log("[ExamScheduleRuleEvaluator] Output preview: " . substr($output, 0, 1000) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            error_log("[ExamScheduleRuleEvaluator] WARNING: Output is null or empty! [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // shell_exec가 null을 반환하는 경우
        if ($output === null) {
            $pythonCheck = shell_exec("which python3 2>&1");
            if (empty($pythonCheck)) {
                throw new Exception("python3 not found. Please install Python 3. [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            if (!empty($stderr)) {
                throw new Exception("Rule engine execution failed. stderr: " . substr($stderr, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            
            throw new Exception("Rule engine execution failed. Command: {$command} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // 빈 출력 체크
        if (trim($output) === '') {
            if (!empty($stderr)) {
                throw new Exception("Rule engine returned empty output but stderr has: " . substr($stderr, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            }
            throw new Exception("Rule engine returned empty output. Command: {$command} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Python 에러 체크
        if (!empty($stderr) && (strpos($stderr, 'Traceback') !== false || strpos($stderr, 'Error') !== false || strpos($stderr, 'ModuleNotFoundError') !== false)) {
            if (strpos($stderr, 'ModuleNotFoundError') !== false && strpos($stderr, 'yaml') !== false) {
                error_log("Rule engine stderr: " . $stderr . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                
                // PyYAML 자동 설치 시도
                $installAttempted = false;
                $installSuccess = false;
                
                $pythonCmds = ['python3.10', 'python3', 'python3.6', 'python3.7', 'python3.8', 'python3.9'];
                foreach ($pythonCmds as $pythonCmd) {
                    $pythonCheck = shell_exec("which {$pythonCmd} 2>&1");
                    if (!empty($pythonCheck)) {
                        error_log("[ExamScheduleRuleEvaluator] Attempting to install PyYAML for {$pythonCmd} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                        $installOutput = shell_exec("{$pythonCmd} -m pip install --user PyYAML 2>&1");
                        $installAttempted = true;
                        
                        $verifyCheck = shell_exec("{$pythonCmd} -c 'import yaml; print(yaml.__version__)' 2>&1");
                        if (strpos($verifyCheck, 'ModuleNotFoundError') === false && !empty(trim($verifyCheck))) {
                            error_log("[ExamScheduleRuleEvaluator] PyYAML installed successfully for {$pythonCmd} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
                            $installSuccess = true;
                            
                            // 재시도
                            $retryStderrFile = sys_get_temp_dir() . '/rule_engine_stderr_retry_' . uniqid() . '.log';
                            $retryCommand = "PYTHONPATH=" . escapeshellarg($pythonPath) . " " . $pythonCmd . " " . escapeshellarg($absoluteEnginePath) . " " . escapeshellarg($jsonInput) . " " . escapeshellarg($absoluteRulesPath) . " 2>" . escapeshellarg($retryStderrFile);
                            $output = shell_exec($retryCommand);
                            $retryStderr = '';
                            if (file_exists($retryStderrFile)) {
                                $retryStderr = file_get_contents($retryStderrFile);
                                @unlink($retryStderrFile);
                            }
                            if ($output && trim($output) !== '' && strpos($retryStderr, 'ModuleNotFoundError') === false) {
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
        
        // Parse JSON output
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
        return [
            'rules_file' => $this->rulesFilePath,
            'engine_path' => $this->ruleEnginePath
        ];
    }
}

// RuleEvaluator 별칭 추가 (호환성)
if (!class_exists('RuleEvaluator')) {
    class RuleEvaluator extends ExamScheduleRuleEvaluator {
        // ExamScheduleRuleEvaluator의 별칭
    }
}

