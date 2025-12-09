<?php
/**
 * AgentLoader - 에이전트 동적 로딩 시스템
 * MD 파일과 PHP 에이전트를 연결하고 런타임에 에이전트 인스턴스를 생성
 */

require_once 'AgentCore.php';

class AgentLoader {
    private $agentRegistry = [];
    private $mdDirectory;
    private $configCache = [];
    
    public function __construct($mdDirectory = null) {
        $this->mdDirectory = $mdDirectory ?: __DIR__;
        $this->initializeRegistry();
    }
    
    /**
     * 에이전트 레지스트리 초기화
     */
    private function initializeRegistry() {
        $this->agentRegistry = [
            'curriculumcentered' => [
                'name' => '📚 체계적 진도형',
                'description' => '교과-단원 선형 진도 + 주간 진단-보정 루프',
                'class' => 'CurriculumAgent',
                'md_file' => 'curriculumcentered.md',
                'php_file' => 'curriculumcentered.php'
            ],
            'examcentered' => [
                'name' => '✏️ 성과 집중형',
                'description' => '시험 D-30 역산 플랜, 최대 점수 효율 추구',
                'class' => 'ExamAgent',
                'md_file' => 'examcentered.md',
                'php_file' => 'examcentered.php'
            ],
            'adaptationcentered' => [
                'name' => '🎯 개인 맞춤형',
                'description' => '개별 학습 패턴에 맞춘 맞춤형 로드맵',
                'class' => 'AdaptationAgent',
                'md_file' => 'adaptationcentered.md',
                'php_file' => 'adaptationcentered.php'
            ],
            'missioncentered' => [
                'name' => '⚡ 목표 달성형',
                'description' => '단기 집중 미션을 통한 동기 회복',
                'class' => 'MissionAgent',
                'md_file' => 'missioncentered.md',
                'php_file' => 'missioncentered.php'
            ],
            'reflectioncentered' => [
                'name' => '🧠 사고력 중심형',
                'description' => '메타인지 강화를 통한 사고력 개발',
                'class' => 'ReflectionAgent',
                'md_file' => 'reflectioncentered.md',
                'php_file' => 'reflectioncentered.php'
            ],
            'selfdriven' => [
                'name' => '🚀 자율학습형',
                'description' => '학습자 주도의 자율적 학습 관리',
                'class' => 'SelfDrivenAgent',
                'md_file' => 'selfdriven.md',
                'php_file' => 'selfdriven.php'
            ],
            'apprentice' => [
                'name' => '🔍 인지적 도제형',
                'description' => '사고 과정 전수를 통한 인지적 도제 학습',
                'class' => 'ApprenticeAgent',
                'md_file' => 'apprentice.md',
                'php_file' => 'apprentice.php'
            ],
            'curiositycentered' => [
                'name' => '🔭 호기심 중심형',
                'description' => '순수한 호기심 기반 탐구 학습',
                'class' => 'CuriosityAgent',
                'md_file' => 'curiositycentered.md',
                'php_file' => 'curiositycentered.php'
            ],
            'timecentered' => [
                'name' => '🕒 시간 피드백형',
                'description' => '시간 기반 학습 효율성 최적화',
                'class' => 'TimeAgent',
                'md_file' => 'timecentered.md',
                'php_file' => 'timecentered.php'
            ]
        ];
    }
    
    /**
     * 사용 가능한 에이전트 목록 반환
     */
    public function getAvailableAgents() {
        $agents = [];
        foreach ($this->agentRegistry as $mode => $info) {
            $mdPath = $this->mdDirectory . '/' . $info['md_file'];
            $phpPath = $this->mdDirectory . '/' . $info['php_file'];
            
            $agents[$mode] = [
                'name' => $info['name'],
                'description' => $info['description'],
                'md_available' => file_exists($mdPath),
                'php_available' => file_exists($phpPath),
                'ready' => file_exists($mdPath) && file_exists($phpPath)
            ];
        }
        
        return $agents;
    }
    
    /**
     * 에이전트 로드 및 인스턴스 생성
     */
    public function loadAgent($mode, $userData = null) {
        if (!isset($this->agentRegistry[$mode])) {
            throw new Exception("Unknown agent mode: {$mode}");
        }
        
        $agentInfo = $this->agentRegistry[$mode];
        $mdPath = $this->mdDirectory . '/' . $agentInfo['md_file'];
        
        // MD 파일 읽기
        if (!file_exists($mdPath)) {
            throw new Exception("MD file not found: {$mdPath}");
        }
        
        $mdContent = file_get_contents($mdPath);
        
        // 전용 에이전트 클래스가 있는지 확인
        $phpPath = $this->mdDirectory . '/' . $agentInfo['php_file'];
        $specificAgentClass = $this->loadSpecificAgent($mode, $phpPath);
        
        if ($specificAgentClass) {
            $agent = new $specificAgentClass($mode, $mdContent);
        } else {
            // 기본 AgentCore 사용
            $agent = new AgentCore($mode, $mdContent);
        }
        
        if ($userData) {
            $agent->setUserData($userData);
        }
        
        return $agent;
    }
    
    /**
     * 전용 에이전트 클래스 로드
     */
    private function loadSpecificAgent($mode, $phpPath) {
        if (!file_exists($phpPath)) {
            return null;
        }
        
        // PHP 파일에서 클래스 추출 시도
        $content = file_get_contents($phpPath);
        
        // 간단한 클래스 존재 여부 확인
        if (preg_match('/class\s+(\w+Agent)\s+extends\s+AgentCore/i', $content, $matches)) {
            include_once $phpPath;
            $className = $matches[1];
            if (class_exists($className)) {
                return $className;
            }
        }
        
        return null;
    }
    
    /**
     * MD 파일 구성 요소 추출
     */
    public function extractMDComponents($mode) {
        if (!isset($this->agentRegistry[$mode])) {
            return null;
        }
        
        $mdPath = $this->mdDirectory . '/' . $this->agentRegistry[$mode]['md_file'];
        if (!file_exists($mdPath)) {
            return null;
        }
        
        if (isset($this->configCache[$mode])) {
            return $this->configCache[$mode];
        }
        
        $content = file_get_contents($mdPath);
        $components = [
            'core_belief' => $this->extractCoreBeliefFromContent($content),
            'kpi' => $this->extractKPIFromContent($content),
            'procedures' => $this->extractProceduresFromContent($content),
            'agent_prompts' => $this->extractAgentPromptsFromContent($content),
            'blending_recipes' => $this->extractBlendingRecipesFromContent($content),
            'parameters' => $this->extractParametersFromContent($content)
        ];
        
        $this->configCache[$mode] = $components;
        return $components;
    }
    
    /**
     * MD에서 핵심 신념 추출
     */
    private function extractCoreBeliefFromContent($content) {
        if (preg_match('/핵심\s*신념[:\s]*["\"]([^"\"]+)["\"]/', $content, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    
    /**
     * MD에서 KPI 추출
     */
    private function extractKPIFromContent($content) {
        $kpi = [];
        if (preg_match('/KPI.*?\n(.*?)(?=##|\* |$)/s', $content, $matches)) {
            $kpiText = $matches[1];
            if (preg_match_all('/([^≥<\n]+)\s*[≥<]\s*([0-9.%]+)/u', $kpiText, $kpiMatches, PREG_SET_ORDER)) {
                foreach ($kpiMatches as $match) {
                    $kpi[trim($match[1])] = trim($match[2]);
                }
            }
        }
        return $kpi;
    }
    
    /**
     * MD에서 절차 추출
     */
    private function extractProceduresFromContent($content) {
        $procedures = [];
        if (preg_match_all('/(\d+)[단계\.]\s*[:\-]?\s*\*\*([^*\n]+)\*\*/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $procedures[intval($match[1])] = trim($match[2]);
            }
        }
        return $procedures;
    }
    
    /**
     * MD에서 에이전트 프롬프트 추출
     */
    private function extractAgentPromptsFromContent($content) {
        $agents = [];
        if (preg_match_all('/(\d+)\.\s*\*\*([^*]+)\*\*\s*```([^`]+)```/s', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $agents[] = [
                    'name' => trim($match[2]),
                    'prompt' => trim($match[3])
                ];
            }
        }
        return $agents;
    }
    
    /**
     * MD에서 블렌딩 레시피 추출
     */
    private function extractBlendingRecipesFromContent($content) {
        $recipes = [];
        if (preg_match_all('/\*\s*\*\*([^*]+)\*\*\s*→\s*([^\n]+)/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $recipes[trim($match[1])] = trim($match[2]);
            }
        }
        return $recipes;
    }
    
    /**
     * MD에서 파라미터 추출
     */
    private function extractParametersFromContent($content) {
        $parameters = [];
        if (preg_match_all('/\*\s*([^:]+):\s*([^(]+)\(([^)]*)\)/', $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $parameters[trim($match[1])] = [
                    'default' => trim($match[2]),
                    'description' => trim($match[3])
                ];
            }
        }
        return $parameters;
    }
    
    /**
     * 에이전트 상태 통계
     */
    public function getAgentStats() {
        $stats = [
            'total_agents' => count($this->agentRegistry),
            'ready_agents' => 0,
            'md_only' => 0,
            'php_only' => 0,
            'missing_both' => 0
        ];
        
        foreach ($this->getAvailableAgents() as $mode => $info) {
            if ($info['ready']) {
                $stats['ready_agents']++;
            } elseif ($info['md_available'] && !$info['php_available']) {
                $stats['md_only']++;
            } elseif (!$info['md_available'] && $info['php_available']) {
                $stats['php_only']++;
            } else {
                $stats['missing_both']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * 에이전트 호환성 검사
     */
    public function validateAgent($mode) {
        $validation = [
            'mode' => $mode,
            'exists' => isset($this->agentRegistry[$mode]),
            'md_file' => false,
            'php_file' => false,
            'md_components' => [],
            'errors' => []
        ];
        
        if (!$validation['exists']) {
            $validation['errors'][] = "Agent mode '{$mode}' not registered";
            return $validation;
        }
        
        $agentInfo = $this->agentRegistry[$mode];
        $mdPath = $this->mdDirectory . '/' . $agentInfo['md_file'];
        $phpPath = $this->mdDirectory . '/' . $agentInfo['php_file'];
        
        // MD 파일 검사
        if (file_exists($mdPath)) {
            $validation['md_file'] = true;
            $validation['md_components'] = $this->extractMDComponents($mode);
        } else {
            $validation['errors'][] = "MD file not found: {$mdPath}";
        }
        
        // PHP 파일 검사
        if (file_exists($phpPath)) {
            $validation['php_file'] = true;
            $content = file_get_contents($phpPath);
            if (!preg_match('/class\s+\w+Agent\s+extends\s+AgentCore/i', $content)) {
                $validation['errors'][] = "PHP file does not contain proper agent class";
            }
        } else {
            $validation['errors'][] = "PHP file not found: {$phpPath}";
        }
        
        return $validation;
    }
    
    /**
     * 에이전트 자동 생성 (MD 기반)
     */
    public function generateAgentFromMD($mode) {
        $components = $this->extractMDComponents($mode);
        if (!$components) {
            return false;
        }
        
        $agentInfo = $this->agentRegistry[$mode];
        $className = $agentInfo['class'];
        $phpPath = $this->mdDirectory . '/' . $agentInfo['php_file'];
        
        $template = $this->generateAgentTemplate($mode, $className, $components);
        
        return file_put_contents($phpPath, $template) !== false;
    }
    
    /**
     * 에이전트 PHP 템플릿 생성
     */
    private function generateAgentTemplate($mode, $className, $components) {
        $template = "<?php\n";
        $template .= "/**\n * {$className} - {$this->agentRegistry[$mode]['name']}\n";
        $template .= " * {$this->agentRegistry[$mode]['description']}\n */\n\n";
        $template .= "require_once 'AgentCore.php';\n\n";
        $template .= "class {$className} extends AgentCore {\n\n";
        
        // Core belief 구현
        if (!empty($components['core_belief'])) {
            $template .= "    protected function extractCoreBeliefFromMD() {\n";
            $template .= "        return '{$components['core_belief']}';\n";
            $template .= "    }\n\n";
        }
        
        // KPI 구현
        if (!empty($components['kpi'])) {
            $template .= "    protected function getStandardVariables() {\n";
            $template .= "        return [\n";
            foreach ($components['kpi'] as $key => $value) {
                $template .= "            '{$key}' => '{$value}',\n";
            }
            $template .= "        ];\n";
            $template .= "    }\n\n";
        }
        
        // 절차 구현
        if (!empty($components['procedures'])) {
            $template .= "    protected function executeStep(\$stepNum, \$input) {\n";
            $template .= "        switch (\$stepNum) {\n";
            foreach ($components['procedures'] as $step => $desc) {
                $template .= "            case {$step}:\n";
                $template .= "                // {$desc}\n";
                $template .= "                return \$this->processStep{$step}(\$input);\n";
            }
            $template .= "            default:\n";
            $template .= "                return 'unknown';\n";
            $template .= "        }\n";
            $template .= "    }\n\n";
            
            // 각 단계별 메서드 생성
            foreach ($components['procedures'] as $step => $desc) {
                $template .= "    private function processStep{$step}(\$input) {\n";
                $template .= "        // TODO: Implement {$desc}\n";
                $template .= "        return 'completed';\n";
                $template .= "    }\n\n";
            }
        }
        
        // 블렌딩 규칙 구현
        if (!empty($components['blending_recipes'])) {
            $template .= "    protected function extractBlendingRules() {\n";
            $template .= "        return [\n";
            foreach ($components['blending_recipes'] as $condition => $recipe) {
                $template .= "            '{$condition}' => '{$recipe}',\n";
            }
            $template .= "        ];\n";
            $template .= "    }\n\n";
        }
        
        $template .= "}\n";
        
        return $template;
    }
}