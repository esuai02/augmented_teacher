<?php
/**
 * 취약점 분석 에이전트 룰 평가기 (PHP 래퍼)
 * File: agent04_inspect_weakpoints/rules/rule_evaluator.php
 * 
 * Python 룰 엔진을 PHP에서 호출하는 래퍼 클래스
 */

class InspectWeakpointsRuleEvaluator {
    
    private $ruleEnginePath;
    private $rulesFilePath;
    
    /**
     * Constructor
     * 
     * @param string|null $rulesFilePath Optional path to rules YAML file
     */
    public function __construct($rulesFilePath = null) {
        $baseDir = __DIR__;
        $this->ruleEnginePath = $baseDir . '/inspect_weakpoints_rule_engine.py';
        
        if ($rulesFilePath === null) {
            $this->rulesFilePath = $baseDir . '/rules.yaml';
        } else {
            $this->rulesFilePath = $rulesFilePath;
        }
        
        // Validate files exist
        if (!file_exists($this->ruleEnginePath)) {
            throw new Exception("Rule engine not found: {$this->ruleEnginePath} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
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
            error_log("[InspectWeakpointsRuleEvaluator] Using python3.10 [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        } else {
            error_log("[InspectWeakpointsRuleEvaluator] Using python3 (default) [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            error_log("[InspectWeakpointsRuleEvaluator] Adding user site-packages to PYTHONPATH: {$path} [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
        error_log("[InspectWeakpointsRuleEvaluator] Command: " . $command . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[InspectWeakpointsRuleEvaluator] JSON Input length: " . strlen($jsonInput) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // Execute with timeout
        $startTime = microtime(true);
        $output = shell_exec($command);
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // stderr 읽기 및 로그 기록
        $stderr = '';
        if (file_exists($stderrFile)) {
            $stderr = file_get_contents($stderrFile);
            error_log("[InspectWeakpointsRuleEvaluator] Python stderr: " . substr($stderr, 0, 2000) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
            @unlink($stderrFile);
        }
        
        // 디버깅: 출력 로그
        error_log("[InspectWeakpointsRuleEvaluator] Execution time: {$executionTime}ms [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        error_log("[InspectWeakpointsRuleEvaluator] Output length: " . (strlen($output ?? '') ?: 'null') . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        
        // shell_exec가 null을 반환하는 경우
        if ($output === null) {
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
            error_log("Rule engine stderr: " . $stderr . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
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
            throw new Exception("Invalid JSON response from rule engine: " . json_last_error_msg() . ". Output: " . substr($output, 0, 500) . " [File: " . __FILE__ . ", Line: " . __LINE__ . "]");
        }
        
        // Add execution time
        $result['execution_time'] = $executionTime . 'ms';
        
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

