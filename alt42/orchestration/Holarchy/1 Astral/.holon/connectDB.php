<?php
/**
 * Holonic AGI DB/Interface Connector
 * 
 * 역할:
 * - 현재 DB와 questions.md가 필요로 하는 가상 데이터 구조 연결
 * - 사람 개입 필요 시 가독성 있는 인터페이스 생성
 * - 위험 상태 확인 및 제거 검증
 */

class HolonConnector {
    
    private $dbConnection;
    private $contextPath;
    private $questionsPath;
    private $rulesPath;
    
    public function __construct($dbConfig = null) {
        $this->contextPath = __DIR__ . '/context.md';
        $this->questionsPath = __DIR__ . '/questions.md';
        $this->rulesPath = __DIR__ . '/rules.yaml';
        
        if ($dbConfig) {
            $this->connectDB($dbConfig);
        }
    }
    
    //═══════════════════════════════════════════════════════════════
    // 1. DB 연결
    //═══════════════════════════════════════════════════════════════
    
    private function connectDB($config) {
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
            $this->dbConnection = new PDO($dsn, $config['username'], $config['password']);
            $this->dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new Exception("DB 연결 실패: " . $e->getMessage());
        }
    }
    
    //═══════════════════════════════════════════════════════════════
    // 2. Context 조회/업데이트
    //═══════════════════════════════════════════════════════════════
    
    public function fetchContext() {
        $content = file_get_contents($this->contextPath);
        return $this->parseMarkdownYaml($content);
    }
    
    public function updateContext($section, $data) {
        // context.md의 특정 섹션 업데이트
        $context = $this->fetchContext();
        $context[$section] = array_merge($context[$section] ?? [], $data);
        
        // 히스토리에 변경 기록
        $context['history'][] = [
            'timestamp' => date('c'),
            'section' => $section,
            'action' => 'updated'
        ];
        
        return $this->saveContext($context);
    }
    
    //═══════════════════════════════════════════════════════════════
    // 3. 진단 파라미터 조회 (DB에서 계산)
    //═══════════════════════════════════════════════════════════════
    
    public function fetchDiagnostics() {
        // 실제 DB에서 계산된 진단 파라미터 조회
        // 현재는 가상 데이터 반환 (실제 연동 시 쿼리로 대체)
        
        return [
            'SEI' => $this->calculateSEI(),  // 학습 효과 지수
            'EC'  => $this->calculateEC(),   // 몰입 지속 지수
            'ES'  => $this->calculateES(),   // 정서 안전 지수
            'BV'  => $this->calculateBV(),   // 지점 편차 지수
            'GR'  => $this->calculateGR(),   // 일반화 신뢰성
        ];
    }
    
    private function calculateSEI() {
        // 실제 구현: 학생별 (현재실력 - 이전실력) / 학습시간 평균
        if ($this->dbConnection) {
            // $stmt = $this->dbConnection->query("SELECT AVG(...) FROM learning_logs");
            // return $stmt->fetchColumn();
        }
        return 0.82; // 가상 데이터
    }
    
    private function calculateEC() {
        // 실제 구현: 1 - (이탈률 + 장기공백세션비율) / 2
        return 0.71; // 가상 데이터
    }
    
    private function calculateES() {
        // 실제 구현: 1 - (고스트레스비율 + 부정감정플래그비율) / 2
        return 0.89; // 가상 데이터
    }
    
    private function calculateBV() {
        // 실제 구현: 지점별 SEI의 분산
        return 0.23; // 가상 데이터 (낮을수록 좋음)
    }
    
    private function calculateGR() {
        // 실제 구현: 예측결과와 실제결과의 상관계수
        return 0.76; // 가상 데이터
    }
    
    //═══════════════════════════════════════════════════════════════
    // 4. 위험 수준 판정
    //═══════════════════════════════════════════════════════════════
    
    public function assessRiskLevel() {
        $diagnostics = $this->fetchDiagnostics();
        $minValue = min(array_values($diagnostics));
        
        // BV는 낮을수록 좋으므로 반전
        $bvScore = 1 - $diagnostics['BV'];
        $scores = [$diagnostics['SEI'], $diagnostics['EC'], $diagnostics['ES'], $bvScore, $diagnostics['GR']];
        $minScore = min($scores);
        
        if ($minScore >= 0.8) return ['level' => 'safe', 'color' => '#22c55e'];
        if ($minScore >= 0.6) return ['level' => 'caution', 'color' => '#eab308'];
        if ($minScore >= 0.4) return ['level' => 'danger', 'color' => '#f97316'];
        return ['level' => 'critical', 'color' => '#ef4444'];
    }
    
    public function checkRiskCleared($previousRisk) {
        $currentRisk = $this->assessRiskLevel();
        
        // 위험이 caution 이상으로 개선되었는지 확인
        $levelOrder = ['critical' => 0, 'danger' => 1, 'caution' => 2, 'safe' => 3];
        
        return $levelOrder[$currentRisk['level']] >= $levelOrder['caution'];
    }
    
    //═══════════════════════════════════════════════════════════════
    // 5. questions.md 기반 가상 데이터 구조 생성
    //═══════════════════════════════════════════════════════════════
    
    public function generateDataStructure($situation) {
        $questions = $this->loadQuestions();
        $situationQuestions = $questions[$situation] ?? $questions['_global'];
        
        // 질문에 필요한 데이터 구조 생성
        $dataStructure = [];
        
        foreach ($situationQuestions as $key => $question) {
            $dataStructure[$key] = [
                'question' => $question,
                'data_source' => $this->mapQuestionToDataSource($key),
                'current_value' => $this->fetchDataForQuestion($key)
            ];
        }
        
        return $dataStructure;
    }
    
    private function mapQuestionToDataSource($questionKey) {
        $mapping = [
            'W_ALIGN' => 'context.W_ROOT',
            'RISK' => 'diagnostics',
            'PROGRESS' => 'holon_status',
            'SEI' => 'learning_logs',
            'EC' => 'session_logs',
            'ES' => 'emotion_logs',
            'BV' => 'branch_stats',
            'GR' => 'prediction_logs'
        ];
        
        return $mapping[$questionKey] ?? 'manual_input';
    }
    
    private function fetchDataForQuestion($questionKey) {
        // 실제 구현: DB에서 해당 질문에 필요한 데이터 조회
        return null; // 가상: 사람 입력 필요
    }
    
    //═══════════════════════════════════════════════════════════════
    // 6. 사람 개입 인터페이스 생성
    //═══════════════════════════════════════════════════════════════
    
    public function renderHumanInterface($context, $options = []) {
        $risk = $this->assessRiskLevel();
        $diagnostics = $this->fetchDiagnostics();
        
        $html = <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>Holonic AGI - 사람 확인 필요</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background: #0f172a; 
            color: #e2e8f0; 
            padding: 2rem; 
        }
        .container { max-width: 800px; margin: 0 auto; }
        .header { 
            text-align: center; 
            padding: 2rem; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
        }
        .risk-badge { 
            display: inline-block; 
            padding: 0.5rem 1rem; 
            border-radius: 0.5rem; 
            font-weight: bold; 
            background: {$risk['color']}; 
            color: #000; 
        }
        .section { 
            margin: 1.5rem 0; 
            padding: 1.5rem; 
            background: rgba(255,255,255,0.05); 
            border-radius: 0.5rem; 
        }
        .section h3 { color: #22d3ee; margin-bottom: 1rem; }
        .param { 
            display: flex; 
            justify-content: space-between; 
            padding: 0.5rem 0; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
        }
        .options { margin-top: 1.5rem; }
        .option-btn { 
            display: block; 
            width: 100%; 
            padding: 1rem; 
            margin: 0.5rem 0; 
            border: 2px solid rgba(255,255,255,0.2); 
            background: rgba(255,255,255,0.05); 
            color: #e2e8f0; 
            border-radius: 0.5rem; 
            cursor: pointer; 
            text-align: left;
        }
        .option-btn:hover { background: rgba(255,255,255,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔔 사람 확인 필요</h1>
            <p style="margin: 1rem 0;">현재 상황: <strong>{$context['situation']}</strong></p>
            <span class="risk-badge">위험 수준: {$risk['level']}</span>
        </div>
        
        <div class="section">
            <h3>📊 진단 파라미터</h3>
            <div class="param"><span>SEI (학습 효과)</span><span>{$diagnostics['SEI']}</span></div>
            <div class="param"><span>EC (몰입 지속)</span><span>{$diagnostics['EC']}</span></div>
            <div class="param"><span>ES (정서 안전)</span><span>{$diagnostics['ES']}</span></div>
            <div class="param"><span>BV (지점 편차)</span><span>{$diagnostics['BV']}</span></div>
            <div class="param"><span>GR (일반화 신뢰성)</span><span>{$diagnostics['GR']}</span></div>
        </div>
        
        <div class="section">
            <h3>❓ 확인 사항</h3>
            <p>{$options['message'] ?? '진행 여부를 결정해주세요.'}</p>
        </div>
        
        <div class="options">
HTML;
        
        foreach ($options['choices'] ?? ['approve' => '승인', 'reject' => '거부'] as $key => $label) {
            $html .= "<button class='option-btn' onclick=\"respond('{$key}')\">{$label}</button>";
        }
        
        $html .= <<<HTML
        </div>
    </div>
    <script>
        function respond(choice) {
            fetch('/holon/respond', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({choice: choice, context: '{$context['situation']}'})
            }).then(() => window.close());
        }
    </script>
</body>
</html>
HTML;
        
        return $html;
    }
    
    //═══════════════════════════════════════════════════════════════
    // 7. 홀론 트리 조회
    //═══════════════════════════════════════════════════════════════
    
    public function fetchHolonTree() {
        // 활성 홀론들의 계층 구조 반환
        $context = $this->fetchContext();
        
        return [
            'root' => [
                'id' => 'root',
                'name' => 'K-12 EdTech',
                'status' => 'safe',
                'children' => $context['active_holons'] ?? []
            ]
        ];
    }
    
    //═══════════════════════════════════════════════════════════════
    // 유틸리티 함수
    //═══════════════════════════════════════════════════════════════
    
    private function parseMarkdownYaml($content) {
        // Markdown 내 YAML 블록 파싱
        preg_match_all('/```yaml\n(.*?)\n```/s', $content, $matches);
        
        $parsed = [];
        foreach ($matches[1] as $yaml) {
            $data = yaml_parse($yaml);
            if ($data) $parsed = array_merge($parsed, $data);
        }
        
        return $parsed;
    }
    
    private function loadQuestions() {
        $content = file_get_contents($this->questionsPath);
        return $this->parseMarkdownYaml($content);
    }
    
    private function saveContext($context) {
        // context.md 파일 저장 로직
        return true;
    }
}

//═══════════════════════════════════════════════════════════════
// API 엔드포인트 예시
//═══════════════════════════════════════════════════════════════

if (php_sapi_name() !== 'cli' && isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $connector = new HolonConnector();
    
    switch ($_GET['action']) {
        case 'diagnostics':
            echo json_encode($connector->fetchDiagnostics());
            break;
            
        case 'risk':
            echo json_encode($connector->assessRiskLevel());
            break;
            
        case 'context':
            echo json_encode($connector->fetchContext());
            break;
            
        case 'holons':
            echo json_encode($connector->fetchHolonTree());
            break;
            
        default:
            echo json_encode(['error' => 'Unknown action']);
    }
}

