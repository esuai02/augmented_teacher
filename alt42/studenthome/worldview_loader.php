<?php
/**
 * Worldview Loader - Parse W-X-S-P-E-R-T-A framework data from markdown files
 * 
 * This module loads and caches worldview data from contextual_agents/intro_modes/*.md files
 * for use in the chatbot system to provide mode-specific personality and behavior.
 */

class WorldviewLoader {
    private static $cache = [];
    private static $cache_timestamps = [];
    private $base_path;
    
    // Mode to filename mapping
    private $mode_files = [
        'curriculum' => 'curriculumcentered.md',
        'exam' => 'examcentered.md',
        'custom' => 'adaptationcentered.md',
        'mission' => 'missioncentered.md',
        'reflection' => 'reflectioncentered.md',
        'selfled' => 'selfdriven.md',
        'cognitive' => 'apprentice.md',
        'timecentered' => 'timecentered.md',
        'curiositycentered' => 'curiositycentered.md'
    ];
    
    // W-X-S-P-E-R-T-A section mappings
    private $section_mappings = [
        'W' => 'worldview',     // 세계관
        'X' => 'context',       // 문맥지능
        'S' => 'structure',     // 구조지능
        'P' => 'procedure',     // 절차지능
        'E' => 'execution',     // 실행지능
        'R' => 'reflection',    // 성찰지능
        'T' => 'traffic',       // 트래픽 지능
        'A' => 'aftermath'      // 추상화/시간 지능
    ];
    
    public function __construct() {
        $this->base_path = dirname(__FILE__) . '/contextual_agents/intro_modes/';
    }
    
    /**
     * Get worldview data for a specific mode
     * 
     * @param string $mode The learning mode (curriculum, exam, etc.)
     * @return array Structured worldview data with W-X-S-P-E-R-T-A framework
     */
    public function getWorldview($mode) {
        // Check if mode file exists
        if (!isset($this->mode_files[$mode])) {
            return $this->getDefaultWorldview($mode);
        }
        
        $filepath = $this->base_path . $this->mode_files[$mode];
        
        // Check if file exists
        if (!file_exists($filepath)) {
            error_log("Worldview file not found: $filepath");
            return $this->getDefaultWorldview($mode);
        }
        
        // Check cache
        $file_mtime = filemtime($filepath);
        if (isset(self::$cache[$mode]) && 
            isset(self::$cache_timestamps[$mode]) && 
            self::$cache_timestamps[$mode] >= $file_mtime) {
            return self::$cache[$mode];
        }
        
        // Parse the markdown file
        $content = file_get_contents($filepath);
        $worldview = $this->parseMarkdown($content, $mode);
        
        // Cache the result
        self::$cache[$mode] = $worldview;
        self::$cache_timestamps[$mode] = $file_mtime;
        
        return $worldview;
    }
    
    /**
     * Parse markdown content to extract W-X-S-P-E-R-T-A framework data
     * 
     * @param string $content Markdown content
     * @param string $mode The learning mode
     * @return array Structured worldview data
     */
    private function parseMarkdown($content, $mode) {
        $worldview = [
            'mode' => $mode,
            'title' => $this->extractTitle($content),
            'icon' => $this->extractIcon($content),
            'worldview' => [],    // W section
            'context' => [],      // X section
            'structure' => [],    // S section
            'procedure' => [],    // P section
            'execution' => [],    // E section
            'reflection' => [],   // R section
            'traffic' => [],      // T section
            'aftermath' => [],    // A section
            'core_belief' => '',
            'switching_triggers' => [],
            'kpi' => [],
            'blending_recipes' => []
        ];
        
        // Split content by main sections
        $sections = $this->splitBySections($content);
        
        foreach ($sections as $section_key => $section_content) {
            switch ($section_key) {
                case 'W':
                    $worldview['worldview'] = $this->parseWorldviewSection($section_content);
                    $worldview['core_belief'] = $this->extractCoreBelief($section_content);
                    break;
                case 'X':
                    $worldview['context'] = $this->parseContextSection($section_content);
                    $worldview['switching_triggers'] = $this->extractSwitchingTriggers($section_content);
                    break;
                case 'S':
                    $worldview['structure'] = $this->parseStructureSection($section_content);
                    $worldview['kpi'] = $this->extractKPI($section_content);
                    break;
                case 'P':
                    $worldview['procedure'] = $this->parseProcedureSection($section_content);
                    break;
                case 'E':
                    $worldview['execution'] = $this->parseExecutionSection($section_content);
                    break;
                case 'R':
                    $worldview['reflection'] = $this->parseReflectionSection($section_content);
                    break;
                case 'T':
                    $worldview['traffic'] = $this->parseTrafficSection($section_content);
                    break;
                case 'A':
                    $worldview['aftermath'] = $this->parseAftermathSection($section_content);
                    break;
            }
        }
        
        // Extract blending recipes if available
        $worldview['blending_recipes'] = $this->extractBlendingRecipes($content);
        
        return $worldview;
    }
    
    /**
     * Split markdown content by W-X-S-P-E-R-T-A sections
     */
    private function splitBySections($content) {
        $sections = [];
        
        // Pattern to match ## W:, ## X:, etc.
        $pattern = '/^##\s*([WXSPERTA]):\s*(.+?)$/m';
        
        // Find all section headers
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);
        
        for ($i = 0; $i < count($matches[0]); $i++) {
            $section_key = $matches[1][$i][0];
            $start = $matches[0][$i][1];
            
            // Find the end of this section (start of next section or end of content)
            if ($i + 1 < count($matches[0])) {
                $end = $matches[0][$i + 1][1];
            } else {
                $end = strlen($content);
            }
            
            $section_content = substr($content, $start, $end - $start);
            $sections[$section_key] = $section_content;
        }
        
        return $sections;
    }
    
    /**
     * Extract title from content
     */
    private function extractTitle($content) {
        if (preg_match('/^#\s+[^\n]*?([가-힣]+.*?)(?:\s*–|\s*$)/m', $content, $matches)) {
            return trim($matches[1]);
        }
        return '';
    }
    
    /**
     * Extract icon emoji from content
     */
    private function extractIcon($content) {
        if (preg_match('/([📚✏️🎯⚡🧠🚀🔍🕒🔭])/u', $content, $matches)) {
            return $matches[1];
        }
        return '📚';
    }
    
    /**
     * Extract core belief from worldview section
     */
    private function extractCoreBelief($content) {
        if (preg_match('/\*\s*핵심 신념:\s*"([^"]+)"/', $content, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    /**
     * Parse worldview (W) section
     */
    private function parseWorldviewSection($content) {
        $worldview_data = [];
        
        // Extract bullet points
        preg_match_all('/^\*\s+(.+?)$/m', $content, $matches);
        foreach ($matches[1] as $line) {
            // Skip sub-bullets
            if (!preg_match('/^\s+/', $line)) {
                $worldview_data[] = trim($line);
            }
        }
        
        return $worldview_data;
    }
    
    /**
     * Parse context (X) section
     */
    private function parseContextSection($content) {
        $context_data = [];
        
        // Extract main context items
        if (preg_match('/필수 컨텍스트[^*]+\*\s+(.+?)(?=\*\s*스위칭|$)/s', $content, $matches)) {
            $context_items = trim($matches[1]);
            $context_data['required_context'] = $context_items;
        }
        
        return $context_data;
    }
    
    /**
     * Extract switching triggers from context section
     */
    private function extractSwitchingTriggers($content) {
        $triggers = [];
        
        // Pattern to extract trigger conditions
        if (preg_match_all('/\*\s+([^:]+):\s+(.+?)$/m', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $condition = trim($matches[1][$i]);
                $action = trim($matches[2][$i]);
                if (strpos($condition, 'D') === 0 || strpos($condition, '진도이탈') !== false) {
                    $triggers[] = [
                        'condition' => $condition,
                        'action' => $action
                    ];
                }
            }
        }
        
        return $triggers;
    }
    
    /**
     * Parse structure (S) section
     */
    private function parseStructureSection($content) {
        $structure_data = [];
        
        // Extract JSON data model if present
        if (preg_match('/```json\s*(.+?)```/s', $content, $matches)) {
            $json_str = $matches[1];
            $structure_data['data_model'] = json_decode($json_str, true);
        }
        
        // Extract standard variables
        if (preg_match('/표준 변수[^*]+\*\s+`([^`]+)`/', $content, $matches)) {
            $structure_data['variables'] = $matches[1];
        }
        
        return $structure_data;
    }
    
    /**
     * Extract KPI from structure section
     */
    private function extractKPI($content) {
        $kpi = [];
        
        if (preg_match('/KPI[^*]+\*\s+(.+?)(?=\*|$)/s', $content, $matches)) {
            $kpi_text = trim($matches[1]);
            // Parse individual KPI items
            if (preg_match_all('/([\w가-힣\s]+)\s*[≥>]\s*([\d.]+%?)/', $kpi_text, $kpi_matches)) {
                for ($i = 0; $i < count($kpi_matches[0]); $i++) {
                    $kpi[trim($kpi_matches[1][$i])] = $kpi_matches[2][$i];
                }
            }
        }
        
        return $kpi;
    }
    
    /**
     * Parse procedure (P) section
     */
    private function parseProcedureSection($content) {
        $procedure_data = [];
        
        // Extract procedure items
        preg_match_all('/^\*\s+(.+?)$/m', $content, $matches);
        foreach ($matches[1] as $line) {
            if (!preg_match('/^\s+/', $line)) {
                $procedure_data[] = trim($line);
            }
        }
        
        return $procedure_data;
    }
    
    /**
     * Parse execution (E) section
     */
    private function parseExecutionSection($content) {
        $execution_data = [];
        
        // Extract checklist items
        if (preg_match('/교사 체크리스트(.+?)(?=\*\s*학생|$)/s', $content, $matches)) {
            $execution_data['teacher_checklist'] = $this->extractChecklist($matches[1]);
        }
        
        // Extract student routine
        if (preg_match('/학생 루틴(.+?)(?=\*\s*자동화|$)/s', $content, $matches)) {
            $execution_data['student_routine'] = trim($matches[1]);
        }
        
        return $execution_data;
    }
    
    /**
     * Parse reflection (R) section
     */
    private function parseReflectionSection($content) {
        $reflection_data = [];
        
        // Extract reflection questions
        if (preg_match('/주간 리플렉션(.+?)(?=\*\s*개선|$)/s', $content, $matches)) {
            $questions = [];
            if (preg_match_all('/(\d+)\.\s+(.+?)$/m', $matches[1], $q_matches)) {
                for ($i = 0; $i < count($q_matches[0]); $i++) {
                    $questions[] = trim($q_matches[2][$i]);
                }
            }
            $reflection_data['weekly_questions'] = $questions;
        }
        
        // Extract improvement rules
        if (preg_match('/개선 규칙(.+?)(?=##|$)/s', $content, $matches)) {
            $reflection_data['improvement_rules'] = trim($matches[1]);
        }
        
        return $reflection_data;
    }
    
    /**
     * Parse traffic (T) section
     */
    private function parseTrafficSection($content) {
        $traffic_data = [];
        
        // Extract information flow design
        if (preg_match('/정보흐름[^*]+\*\s+(.+?)$/m', $content, $matches)) {
            $traffic_data['information_flow'] = trim($matches[1]);
        }
        
        return $traffic_data;
    }
    
    /**
     * Parse aftermath (A) section
     */
    private function parseAftermathSection($content) {
        $aftermath_data = [];
        
        // Extract quarterly review items
        if (preg_match('/분기 회고:\s*(.+?)$/m', $content, $matches)) {
            $aftermath_data['quarterly_review'] = trim($matches[1]);
        }
        
        // Extract reusable assets
        if (preg_match('/재사용 자산:\s*(.+?)$/m', $content, $matches)) {
            $aftermath_data['reusable_assets'] = trim($matches[1]);
        }
        
        return $aftermath_data;
    }
    
    /**
     * Extract blending recipes
     */
    private function extractBlendingRecipes($content) {
        $recipes = [];
        
        if (preg_match('/상황별 블렌딩 레시피(.+?)(?=---|\z)/s', $content, $matches)) {
            if (preg_match_all('/\*\s+\*\*([^*]+)\*\*\s+→\s+(.+?)$/m', $matches[1], $r_matches)) {
                for ($i = 0; $i < count($r_matches[0]); $i++) {
                    $recipes[$r_matches[1][$i]] = $r_matches[2][$i];
                }
            }
        }
        
        return $recipes;
    }
    
    /**
     * Extract checklist items
     */
    private function extractChecklist($content) {
        $checklist = [];
        
        if (preg_match_all('/\[\s*\]\s+(.+?)(?:\s+\\\\?\[|\s*$)/s', $content, $matches)) {
            foreach ($matches[1] as $item) {
                $checklist[] = trim($item);
            }
        }
        
        return $checklist;
    }
    
    /**
     * Get default worldview when file is not found
     */
    private function getDefaultWorldview($mode) {
        $defaults = [
            'curriculum' => [
                'mode' => 'curriculum',
                'title' => '체계적 진도형',
                'icon' => '📚',
                'core_belief' => '진도는 전략, 보정은 일상',
                'worldview' => ['교과-단원 선형 진도 + 주간 진단-보정 루프'],
                'kpi' => ['주간 진도달성' => '90%', '단원 마스터리' => '80%'],
                'switching_triggers' => []
            ],
            'exam' => [
                'mode' => 'exam',
                'title' => '시험대비 중심모드',
                'icon' => '✏️',
                'core_belief' => '시험은 전투, 출제자는 상대',
                'worldview' => ['내신 분석 → 파이널 기억인출 구조 세팅'],
                'kpi' => ['기출 3회독', '오답노트 2회독', '일일 50문항'],
                'switching_triggers' => []
            ],
            'custom' => [
                'mode' => 'custom',
                'title' => '맞춤학습 중심모드',
                'icon' => '🎯',
                'core_belief' => '모든 학생은 고유한 학습 DNA를 가진다',
                'worldview' => ['개별 수준 맞춤 문제 배치와 진단 루프 활용'],
                'kpi' => ['개인 속도 유지', '강점 극대화', '약점 보완'],
                'switching_triggers' => []
            ],
            'mission' => [
                'mode' => 'mission',
                'title' => '단기미션 중심모드',
                'icon' => '⚡',
                'core_belief' => '작은 승리가 큰 성공을 만든다',
                'worldview' => ['짧은 목표 → 성취 → 피드백 → 반복 학습 루프'],
                'kpi' => ['일일 5미션', '주간 보스전', '월간 레벨업'],
                'switching_triggers' => []
            ],
            'reflection' => [
                'mode' => 'reflection',
                'title' => '자기성찰 중심모드',
                'icon' => '🧠',
                'core_belief' => '이해 없는 정답은 무의미하다',
                'worldview' => ['학습 후 자기평가 → 피드백 기록 → 학습전략 수정'],
                'kpi' => ['백지복습법', '개념맵 작성', '메타인지 발달'],
                'switching_triggers' => []
            ],
            'selfled' => [
                'mode' => 'selfled',
                'title' => '자기주도 중심모드',
                'icon' => '🚀',
                'core_belief' => '스스로 설계한 길이 가장 빠른 길',
                'worldview' => ['수업 시나리오를 본인이 직접 설계하고 주도'],
                'kpi' => ['자율 실행률 70%', '계획 품질', '실행률'],
                'switching_triggers' => []
            ],
            'cognitive' => [
                'mode' => 'cognitive',
                'title' => '도제학습 중심모드',
                'icon' => '🔍',
                'core_belief' => '마스터의 사고를 모방하며 성장한다',
                'worldview' => ['사고하는 법을 가르치는 수업, 결과보다 과정 중심'],
                'kpi' => ['모델링', '코칭', '스캐폴딩', '명료화'],
                'switching_triggers' => []
            ],
            'timecentered' => [
                'mode' => 'timecentered',
                'title' => '시간성찰 중심모드',
                'icon' => '🕒',
                'core_belief' => '시간은 학습의 생명선',
                'worldview' => ['시간 관리와 학습 밀도를 최적화하여 효율을 극대화'],
                'kpi' => ['포모도로 25/5', '시간당 18문항', '집중 밀도'],
                'switching_triggers' => []
            ],
            'curiositycentered' => [
                'mode' => 'curiositycentered',
                'title' => '탐구학습 중심모드',
                'icon' => '🔭',
                'core_belief' => '궁금증이 최고의 선생님',
                'worldview' => ['호기심과 질문을 중심으로 탐구적 학습을 진행'],
                'kpi' => ['왜? 질문', '가설 설정', '실험 검증'],
                'switching_triggers' => []
            ]
        ];
        
        return isset($defaults[$mode]) ? $defaults[$mode] : $defaults['curriculum'];
    }
}

/**
 * Helper function to get worldview data
 * 
 * @param string $mode Learning mode
 * @return array Worldview data
 */
function parseWorldviewFromMarkdown($mode) {
    static $loader = null;
    
    if ($loader === null) {
        $loader = new WorldviewLoader();
    }
    
    return $loader->getWorldview($mode);
}

/**
 * Format worldview for system prompt
 * 
 * @param array $worldview Worldview data
 * @return string Formatted text for system prompt
 */
function formatWorldviewForPrompt($worldview) {
    $prompt = "【학습 모드 세계관】\n";
    $prompt .= "모드: {$worldview['icon']} {$worldview['title']}\n";
    
    if (!empty($worldview['core_belief'])) {
        $prompt .= "핵심 신념: \"{$worldview['core_belief']}\"\n\n";
    }
    
    if (!empty($worldview['worldview'])) {
        $prompt .= "세계관 (W):\n";
        foreach ($worldview['worldview'] as $item) {
            $prompt .= "- $item\n";
        }
        $prompt .= "\n";
    }
    
    if (!empty($worldview['kpi'])) {
        $prompt .= "KPI 목표:\n";
        foreach ($worldview['kpi'] as $key => $value) {
            $prompt .= "- $key: $value\n";
        }
        $prompt .= "\n";
    }
    
    if (!empty($worldview['switching_triggers'])) {
        $prompt .= "스위칭 트리거:\n";
        foreach ($worldview['switching_triggers'] as $trigger) {
            $prompt .= "- {$trigger['condition']} → {$trigger['action']}\n";
        }
        $prompt .= "\n";
    }
    
    if (!empty($worldview['execution']['student_routine'])) {
        $prompt .= "학생 루틴:\n{$worldview['execution']['student_routine']}\n\n";
    }
    
    if (!empty($worldview['reflection']['weekly_questions'])) {
        $prompt .= "주간 성찰 질문:\n";
        foreach ($worldview['reflection']['weekly_questions'] as $i => $question) {
            $prompt .= ($i + 1) . ". $question\n";
        }
        $prompt .= "\n";
    }
    
    if (!empty($worldview['blending_recipes'])) {
        $prompt .= "상황별 대응:\n";
        foreach ($worldview['blending_recipes'] as $situation => $recipe) {
            $prompt .= "- $situation: $recipe\n";
        }
    }
    
    return $prompt;
}

?>