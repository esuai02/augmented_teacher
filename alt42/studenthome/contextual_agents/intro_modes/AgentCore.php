<?php
/**
 * AgentCore - 기본 에이전트 클래스
 * MD 파일의 W-X-S-P-E-R-T-A 프레임워크를 PHP로 구현
 */

class AgentCore {
    protected $mode;           // 에이전트 모드 (curriculum, exam, custom 등)
    protected $config;         // MD 파일에서 파싱된 설정
    protected $userData;       // 사용자 학습 데이터
    protected $kpi;           // KPI 목표치
    protected $procedures;    // 절차 정의
    
    public function __construct($mode, $mdContent = null) {
        $this->mode = $mode;
        $this->config = $this->parseMDContent($mdContent);
        $this->kpi = $this->extractKPI();
        $this->procedures = $this->extractProcedures();
    }
    
    /**
     * MD 파일 내용을 파싱하여 설정 추출
     */
    protected function parseMDContent($mdContent) {
        if (!$mdContent) {
            $mdFile = __DIR__ . "/{$this->mode}.md";
            if (file_exists($mdFile)) {
                $mdContent = file_get_contents($mdFile);
            }
        }
        
        $config = [
            'worldview' => $this->extractSection($mdContent, 'W: 세계관'),
            'context' => $this->extractSection($mdContent, 'X: 문맥지능'),
            'structure' => $this->extractSection($mdContent, 'S: 구조지능'),
            'procedure' => $this->extractSection($mdContent, 'P: 절차지능'),
            'execution' => $this->extractSection($mdContent, 'E: 실행지능'),
            'reflection' => $this->extractSection($mdContent, 'R: 성찰지능'),
            'traffic' => $this->extractSection($mdContent, 'T: 트래픽 지능'),
            'aftermath' => $this->extractSection($mdContent, 'A: 추상화 지능')
        ];
        
        return $config;
    }
    
    /**
     * MD에서 특정 섹션 추출
     */
    protected function extractSection($content, $sectionName) {
        $pattern = "/## {$sectionName}.*?\n(.*?)(?=##|\$)/s";
        preg_match($pattern, $content, $matches);
        return isset($matches[1]) ? trim($matches[1]) : '';
    }
    
    /**
     * KPI 추출 및 파싱
     */
    protected function extractKPI() {
        $structureContent = $this->config['structure'];
        $kpi = [];
        
        // KPI 패턴 찾기
        if (preg_match_all('/([^≥<]+)\s*[≥<]\s*([0-9.%]+)/u', $structureContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $kpi[trim($match[1])] = trim($match[2]);
            }
        }
        
        return $kpi;
    }
    
    /**
     * 절차 정의 추출
     */
    protected function extractProcedures() {
        $procedureContent = $this->config['procedure'];
        $procedures = [];
        
        // 단계별 절차 추출
        if (preg_match_all('/(\d+)[단계\.]\s*[:\-]?\s*([^→\n]+)/u', $procedureContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $procedures[intval($match[1])] = trim($match[2]);
            }
        }
        
        return $procedures;
    }
    
    /**
     * 사용자 데이터 설정
     */
    public function setUserData($userData) {
        $this->userData = $userData;
        return $this;
    }
    
    /**
     * 에이전트 실행 (추상 메서드)
     */
    public function execute($input = null) {
        $result = [
            'mode' => $this->mode,
            'timestamp' => date('Y-m-d H:i:s'),
            'worldview' => $this->applyWorldview($input),
            'context' => $this->analyzeContext($input),
            'structure' => $this->applyStructure($input),
            'procedure' => $this->executeProcedure($input),
            'execution' => $this->performExecution($input),
            'reflection' => $this->generateReflection($input),
            'traffic' => $this->manageTraffic($input),
            'aftermath' => $this->processAftermath($input)
        ];
        
        return $result;
    }
    
    /**
     * W: 세계관 적용
     */
    protected function applyWorldview($input) {
        return [
            'core_belief' => $this->extractCoreBeliefFromMD(),
            'strategic_approach' => $this->getStrategicApproach(),
            'mode_connections' => $this->getModeConnections()
        ];
    }
    
    /**
     * X: 문맥 분석
     */
    protected function analyzeContext($input) {
        return [
            'required_context' => $this->getRequiredContext(),
            'switching_triggers' => $this->evaluateSwitchingTriggers($input),
            'context_score' => $this->calculateContextScore($input)
        ];
    }
    
    /**
     * S: 구조 적용
     */
    protected function applyStructure($input) {
        return [
            'variables' => $this->getStandardVariables(),
            'kpi_status' => $this->evaluateKPI($input),
            'data_model' => $this->generateDataModel($input)
        ];
    }
    
    /**
     * P: 절차 실행
     */
    protected function executeProcedure($input) {
        $steps = [];
        foreach ($this->procedures as $stepNum => $stepDesc) {
            $steps[$stepNum] = [
                'description' => $stepDesc,
                'status' => $this->executeStep($stepNum, $input),
                'next_action' => $this->determineNextAction($stepNum, $input)
            ];
        }
        
        return $steps;
    }
    
    /**
     * E: 실행 지능
     */
    protected function performExecution($input) {
        return [
            'teacher_checklist' => $this->getTeacherChecklist(),
            'student_routine' => $this->getStudentRoutine(),
            'automation_mapping' => $this->getAutomationMapping(),
            'execution_status' => $this->checkExecutionStatus($input)
        ];
    }
    
    /**
     * R: 성찰 생성
     */
    protected function generateReflection($input) {
        return [
            'reflection_questions' => $this->getReflectionQuestions(),
            'improvement_rules' => $this->getImprovementRules(),
            'reflection_results' => $this->processReflection($input)
        ];
    }
    
    /**
     * T: 트래픽 관리
     */
    protected function manageTraffic($input) {
        return [
            'information_flow' => $this->getInformationFlow(),
            'j_curve_preparation' => $this->prepareJCurve($input),
            'disconnection_points' => $this->detectDisconnectionPoints($input)
        ];
    }
    
    /**
     * A: 추상화 처리
     */
    protected function processAftermath($input) {
        return [
            'period_review' => $this->conductPeriodReview(),
            'reusable_assets' => $this->generateReusableAssets(),
            'optimization_suggestions' => $this->suggestOptimizations($input)
        ];
    }
    
    /**
     * KPI 평가
     */
    public function evaluateKPI($input) {
        $results = [];
        foreach ($this->kpi as $metric => $target) {
            $current = $this->calculateCurrentKPI($metric, $input);
            $results[$metric] = [
                'target' => $target,
                'current' => $current,
                'status' => $this->compareKPI($current, $target),
                'improvement_needed' => $this->calculateImprovement($current, $target)
            ];
        }
        
        return $results;
    }
    
    /**
     * 상황별 블렌딩 추천
     */
    public function recommendBlending($situation) {
        $blendingRules = $this->extractBlendingRules();
        
        foreach ($blendingRules as $condition => $recommendation) {
            if ($this->matchesSituation($situation, $condition)) {
                return $recommendation;
            }
        }
        
        return $this->getDefaultBlending();
    }
    
    /**
     * 에이전트 상태 저장
     */
    public function saveState($filePath = null) {
        if (!$filePath) {
            $filePath = __DIR__ . "/states/{$this->mode}_state.json";
        }
        
        $state = [
            'mode' => $this->mode,
            'config' => $this->config,
            'kpi' => $this->kpi,
            'procedures' => $this->procedures,
            'userData' => $this->userData,
            'timestamp' => time()
        ];
        
        file_put_contents($filePath, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }
    
    /**
     * 에이전트 상태 로드
     */
    public function loadState($filePath = null) {
        if (!$filePath) {
            $filePath = __DIR__ . "/states/{$this->mode}_state.json";
        }
        
        if (file_exists($filePath)) {
            $state = json_decode(file_get_contents($filePath), true);
            if ($state) {
                $this->config = $state['config'];
                $this->kpi = $state['kpi'];
                $this->procedures = $state['procedures'];
                $this->userData = $state['userData'] ?? [];
                return true;
            }
        }
        
        return false;
    }
    
    // 추상 메서드들 (하위 클래스에서 구현)
    protected function extractCoreBeliefFromMD() { return ''; }
    protected function getStrategicApproach() { return []; }
    protected function getModeConnections() { return []; }
    protected function getRequiredContext() { return []; }
    protected function evaluateSwitchingTriggers($input) { return []; }
    protected function calculateContextScore($input) { return 0; }
    protected function getStandardVariables() { return []; }
    protected function generateDataModel($input) { return []; }
    protected function executeStep($stepNum, $input) { return 'pending'; }
    protected function determineNextAction($stepNum, $input) { return ''; }
    protected function getTeacherChecklist() { return []; }
    protected function getStudentRoutine() { return []; }
    protected function getAutomationMapping() { return []; }
    protected function checkExecutionStatus($input) { return 'ready'; }
    protected function getReflectionQuestions() { return []; }
    protected function getImprovementRules() { return []; }
    protected function processReflection($input) { return []; }
    protected function getInformationFlow() { return []; }
    protected function prepareJCurve($input) { return []; }
    protected function detectDisconnectionPoints($input) { return []; }
    protected function conductPeriodReview() { return []; }
    protected function generateReusableAssets() { return []; }
    protected function suggestOptimizations($input) { return []; }
    protected function calculateCurrentKPI($metric, $input) { return 0; }
    protected function compareKPI($current, $target) { return 'unknown'; }
    protected function calculateImprovement($current, $target) { return 0; }
    protected function extractBlendingRules() { return []; }
    protected function matchesSituation($situation, $condition) { return false; }
    protected function getDefaultBlending() { return []; }
}