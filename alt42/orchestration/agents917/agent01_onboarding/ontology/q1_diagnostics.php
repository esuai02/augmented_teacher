<?php
/**
 * Q1 진단 뷰 - 온톨로지 통합 진단 페이지
 * File: agent01_onboarding/ontology/q1_diagnostics.php
 * 
 * 체크포인트 3: 진단 페이지로 개발 속도 3배 향상
 * 
 * 확인 가능한 항목:
 * 1. 스키마 불일치 - 온톨로지.jsonld에 정의되지 않은 클래스/프로퍼티
 * 2. 매핑 누락 - rules.yaml 액션과 스키마 간 매핑 오류
 * 3. YAML 변수 불일치 - 변수명과 온톨로지 프로퍼티 매핑 오류
 * 4. 온톨로지 인스턴스 생성 실패 - 런타임 오류 추적
 * 
 * 사용법: 브라우저에서 직접 접근
 * https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration13/agents/agent01_onboarding/ontology/q1_diagnostics.php
 */

// Moodle config 로드
$configPath = '/home/moodle/public_html/moodle/config.php';
if (file_exists($configPath)) {
    require_once($configPath);
}

require_once(__DIR__ . '/SchemaLoader.php');

// 스타일 정의
$styles = <<<CSS
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { 
        font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; 
        background: #0f0f23; 
        color: #e0e0e0; 
        padding: 20px;
        line-height: 1.6;
    }
    h1 { 
        color: #00d9ff; 
        margin-bottom: 20px; 
        font-size: 1.8em;
        border-bottom: 2px solid #00d9ff;
        padding-bottom: 10px;
    }
    h2 { 
        color: #ffd700; 
        margin: 30px 0 15px; 
        font-size: 1.3em;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    h2 .status { 
        font-size: 0.8em; 
        padding: 3px 10px; 
        border-radius: 12px; 
        font-weight: normal;
    }
    h2 .status.ok { background: #00c853; color: #000; }
    h2 .status.warn { background: #ff9800; color: #000; }
    h2 .status.error { background: #ff5252; color: #fff; }
    
    .card {
        background: #1a1a2e;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        border: 1px solid #333;
    }
    .card.error { border-color: #ff5252; }
    .card.warn { border-color: #ff9800; }
    .card.ok { border-color: #00c853; }
    
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 10px 0;
        font-size: 0.9em;
    }
    th, td { 
        padding: 10px 12px; 
        text-align: left; 
        border-bottom: 1px solid #333;
    }
    th { 
        background: #252540; 
        color: #00d9ff; 
        font-weight: 600;
    }
    tr:hover { background: #252540; }
    
    .tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.8em;
        font-weight: 500;
    }
    .tag.class { background: #7c4dff; color: #fff; }
    .tag.property { background: #00bcd4; color: #000; }
    .tag.action { background: #ff9800; color: #000; }
    .tag.variable { background: #4caf50; color: #000; }
    .tag.error { background: #ff5252; color: #fff; }
    .tag.warning { background: #ff9800; color: #000; }
    .tag.ok { background: #00c853; color: #000; }
    
    .code {
        font-family: 'JetBrains Mono', 'Fira Code', monospace;
        background: #0d0d1a;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.85em;
        color: #00ff88;
    }
    
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 30px;
    }
    .summary-item {
        background: #1a1a2e;
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        border: 1px solid #333;
    }
    .summary-item .number {
        font-size: 2.5em;
        font-weight: bold;
        color: #00d9ff;
    }
    .summary-item .label {
        color: #888;
        font-size: 0.9em;
        margin-top: 5px;
    }
    .summary-item.error .number { color: #ff5252; }
    .summary-item.warn .number { color: #ff9800; }
    .summary-item.ok .number { color: #00c853; }
    
    .error-message {
        background: #2d1f1f;
        border-left: 4px solid #ff5252;
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 0 8px 8px 0;
    }
    .warning-message {
        background: #2d2a1f;
        border-left: 4px solid #ff9800;
        padding: 12px 15px;
        margin: 10px 0;
        border-radius: 0 8px 8px 0;
    }
    
    .refresh-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #00d9ff;
        color: #000;
        border: none;
        padding: 15px 25px;
        border-radius: 30px;
        font-size: 1em;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,217,255,0.3);
        transition: transform 0.2s;
    }
    .refresh-btn:hover {
        transform: scale(1.05);
    }
    
    .timestamp {
        color: #666;
        font-size: 0.85em;
        margin-bottom: 20px;
    }
    
    .collapsible {
        cursor: pointer;
        user-select: none;
    }
    .collapsible:after {
        content: ' ▼';
        font-size: 0.7em;
    }
    .collapsible.collapsed:after {
        content: ' ▶';
    }
    .collapsible-content {
        max-height: 2000px;
        overflow: hidden;
        transition: max-height 0.3s ease;
    }
    .collapsible-content.collapsed {
        max-height: 0;
    }
</style>
CSS;

// JavaScript
$scripts = <<<JS
<script>
    function toggleCollapse(id) {
        const header = document.getElementById(id + '-header');
        const content = document.getElementById(id + '-content');
        header.classList.toggle('collapsed');
        content.classList.toggle('collapsed');
    }
    
    function refreshPage() {
        location.reload();
    }
</script>
JS;

// 진단 실행
$diagnostics = [];
$totalErrors = 0;
$totalWarnings = 0;

try {
    // 1. SchemaLoader 초기화
    $schemaLoader = new SchemaLoader();
    $schemaDiag = $schemaLoader->getDiagnostics();
    $diagnostics['schema'] = $schemaDiag;
    
} catch (Exception $e) {
    $diagnostics['schema_error'] = $e->getMessage();
    $totalErrors++;
}

// 2. rules.yaml 로드 및 검증
$rulesPath = __DIR__ . '/../rules/rules.yaml';
$rulesActions = [];
$rulesMappingResult = ['valid' => true, 'mappings' => [], 'errors' => []];

if (file_exists($rulesPath)) {
    $rulesContent = file_get_contents($rulesPath);
    
    // 온톨로지 관련 액션 추출
    preg_match_all("/- \"(create_instance|set_property|reason_over|generate_strategy|generate_procedure):[^\"]+\"/", $rulesContent, $matches);
    $rulesActions = $matches[0] ?? [];
    
    // 액션 정리
    $cleanActions = [];
    foreach ($rulesActions as $action) {
        $cleanActions[] = trim($action, '- "');
    }
    
    if (isset($schemaLoader)) {
        $rulesMappingResult = $schemaLoader->validateRuleActions($cleanActions);
        $totalErrors += count($rulesMappingResult['errors']);
    }
    
    $diagnostics['rules'] = [
        'path' => $rulesPath,
        'action_count' => count($cleanActions),
        'actions' => $cleanActions,
        'mapping_result' => $rulesMappingResult
    ];
} else {
    $diagnostics['rules_error'] = "rules.yaml 파일을 찾을 수 없습니다: {$rulesPath}";
    $totalErrors++;
}

// 3. 공식 변수 매핑 테이블 검증 (SchemaLoader의 단일 진실 소스 사용)
$variableMappings = [];
$variableMappingResult = ['valid' => true, 'matched' => [], 'unmatched' => []];

if (isset($schemaLoader)) {
    // 공식 매핑 테이블 가져오기
    $officialMapping = SchemaLoader::getOfficialVariableMapping();
    
    // 매핑 테이블을 변수명 → [컨텍스트 키들] 형태로 변환
    $groupedMappings = [];
    foreach ($officialMapping as $contextKey => $ontologyProp) {
        if (!isset($groupedMappings[$ontologyProp])) {
            $groupedMappings[$ontologyProp] = [];
        }
        $groupedMappings[$ontologyProp][] = $contextKey;
    }
    
    // OntologyEngine의 확장 매핑 테이블도 추출 (하위 호환성 검증용)
    $enginePath = __DIR__ . '/OntologyEngine.php';
    if (file_exists($enginePath)) {
        $engineContent = file_get_contents($enginePath);
        
        // variableMapping 배열 추출 (정규식으로 파싱)
        if (preg_match('/\$variableMapping\s*=\s*\[([\s\S]*?)\];/', $engineContent, $matches)) {
            preg_match_all("/'(\w+)'\s*=>\s*\[([^\]]+)\]/", $matches[1], $varMatches, PREG_SET_ORDER);
            
            foreach ($varMatches as $match) {
                $varName = $match[1];
                preg_match_all("/'([^']+)'/", $match[2], $keys);
                $variableMappings[$varName] = $keys[1] ?? [];
            }
        }
    }
    
    // 검증 실행
    if (!empty($variableMappings)) {
        $variableMappingResult = $schemaLoader->validateVariableMappings($variableMappings);
        $totalWarnings += count($variableMappingResult['unmatched']);
    }
    
    $diagnostics['variables'] = [
        'official_mapping_count' => count($officialMapping),
        'grouped_mapping_count' => count($groupedMappings),
        'engine_mapping_count' => count($variableMappings),
        'mappings' => $variableMappings,
        'official_mapping' => $officialMapping,
        'validation_result' => $variableMappingResult
    ];
}

// 4. 온톨로지 인스턴스 테이블 확인
$instanceDiag = ['table_exists' => false, 'instance_count' => 0, 'recent_errors' => []];
if (isset($DB)) {
    try {
        $dbman = $DB->get_manager();
        $table = new xmldb_table('alt42_ontology_instances');
        $instanceDiag['table_exists'] = $dbman->table_exists($table);
        
        if ($instanceDiag['table_exists']) {
            $instanceDiag['instance_count'] = $DB->count_records('alt42_ontology_instances');
            
            // 최근 인스턴스 조회
            $recentInstances = $DB->get_records_sql(
                "SELECT id, instance_id, class_type, student_id, created_at 
                 FROM {alt42_ontology_instances} 
                 ORDER BY created_at DESC LIMIT 5"
            );
            $instanceDiag['recent_instances'] = array_values($recentInstances);
        }
    } catch (Exception $e) {
        $instanceDiag['db_error'] = $e->getMessage();
        $totalErrors++;
    }
}
$diagnostics['instances'] = $instanceDiag;

// 5. Q1 룰 상세 분석
$q1RulesDiag = [];
if (isset($rulesContent)) {
    // Q1 관련 룰 추출
    preg_match_all('/- rule_id: "(Q1_[^"]+)"[\s\S]*?(?=- rule_id:|$)/', $rulesContent, $q1Matches);
    
    foreach ($q1Matches[0] as $ruleBlock) {
        preg_match('/rule_id: "([^"]+)"/', $ruleBlock, $idMatch);
        $ruleId = $idMatch[1] ?? 'unknown';
        
        // 온톨로지 액션 추출
        preg_match_all('/- "(create_instance|set_property|reason_over|generate_strategy|generate_procedure):[^"]+"/m', $ruleBlock, $actionMatches);
        
        $q1RulesDiag[$ruleId] = [
            'actions' => $actionMatches[0] ?? [],
            'action_count' => count($actionMatches[0] ?? [])
        ];
    }
}
$diagnostics['q1_rules'] = $q1RulesDiag;

// HTML 출력
echo "<!DOCTYPE html>
<html lang='ko'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Q1 온톨로지 진단 뷰</title>
    {$styles}
</head>
<body>
    <h1>🔬 Q1 온톨로지 통합 진단</h1>
    <p class='timestamp'>마지막 갱신: " . date('Y-m-d H:i:s') . "</p>
    
    <!-- 요약 그리드 -->
    <div class='summary-grid'>
        <div class='summary-item " . ($totalErrors > 0 ? 'error' : 'ok') . "'>
            <div class='number'>{$totalErrors}</div>
            <div class='label'>오류</div>
        </div>
        <div class='summary-item " . ($totalWarnings > 0 ? 'warn' : 'ok') . "'>
            <div class='number'>{$totalWarnings}</div>
            <div class='label'>경고</div>
        </div>
        <div class='summary-item'>
            <div class='number'>" . ($diagnostics['schema']['class_count'] ?? 0) . "</div>
            <div class='label'>클래스</div>
        </div>
        <div class='summary-item'>
            <div class='number'>" . ($diagnostics['schema']['property_count'] ?? 0) . "</div>
            <div class='label'>프로퍼티</div>
        </div>
        <div class='summary-item'>
            <div class='number'>" . count($q1RulesDiag) . "</div>
            <div class='label'>Q1 룰</div>
        </div>
        <div class='summary-item'>
            <div class='number'>" . ($instanceDiag['instance_count'] ?? 0) . "</div>
            <div class='label'>인스턴스</div>
        </div>
    </div>";

// 1. 스키마 로드 상태
$schemaStatus = isset($diagnostics['schema_error']) ? 'error' : 'ok';
echo "
    <h2 id='schema-header' class='collapsible' onclick='toggleCollapse(\"schema\")'>
        📋 스키마 로드 상태
        <span class='status {$schemaStatus}'>" . ($schemaStatus === 'ok' ? '정상' : '오류') . "</span>
    </h2>
    <div id='schema-content' class='collapsible-content'>
        <div class='card {$schemaStatus}'>";

if (isset($diagnostics['schema_error'])) {
    echo "<div class='error-message'>{$diagnostics['schema_error']}</div>";
} else {
    echo "
            <table>
                <tr><th>항목</th><th>값</th></tr>
                <tr><td>스키마 경로</td><td><span class='code'>{$diagnostics['schema']['schema_path']}</span></td></tr>
                <tr><td>로드 상태</td><td><span class='tag ok'>로드됨</span></td></tr>
                <tr><td>클래스 수</td><td>{$diagnostics['schema']['class_count']}</td></tr>
                <tr><td>프로퍼티 수</td><td>{$diagnostics['schema']['property_count']}</td></tr>
            </table>
            
            <h3 style='margin-top:20px; color:#00d9ff;'>정의된 클래스</h3>
            <p style='margin:10px 0;'>";
    foreach ($diagnostics['schema']['classes'] as $cls) {
        echo "<span class='tag class'>{$cls}</span> ";
    }
    echo "</p>";
}
echo "
        </div>
    </div>";

// 2. Rules.yaml 매핑 검증
$rulesStatus = empty($rulesMappingResult['errors']) ? 'ok' : 'error';
echo "
    <h2 id='rules-header' class='collapsible' onclick='toggleCollapse(\"rules\")'>
        📜 Rules.yaml 매핑 검증
        <span class='status {$rulesStatus}'>" . (count($rulesMappingResult['errors'])) . " 오류</span>
    </h2>
    <div id='rules-content' class='collapsible-content'>
        <div class='card {$rulesStatus}'>";

if (!empty($rulesMappingResult['errors'])) {
    echo "<h3 style='color:#ff5252; margin-bottom:15px;'>❌ 매핑 오류</h3>";
    foreach ($rulesMappingResult['errors'] as $error) {
        echo "<div class='error-message'>
            <strong>{$error['type']}</strong>: {$error['message']}<br>
            <span class='code'>{$error['action']}</span>
        </div>";
    }
}

if (!empty($rulesMappingResult['mappings'])) {
    echo "<h3 style='color:#00c853; margin-top:20px; margin-bottom:15px;'>✅ 유효한 매핑 (" . count($rulesMappingResult['mappings']) . "개)</h3>
        <table>
            <tr><th>액션</th><th>클래스/프로퍼티</th><th>상태</th></tr>";
    foreach ($rulesMappingResult['mappings'] as $mapping) {
        $target = $mapping['class'] ?? $mapping['property'] ?? '-';
        echo "<tr>
            <td><span class='tag action'>{$mapping['action']}</span></td>
            <td><span class='code'>{$target}</span></td>
            <td><span class='tag ok'>유효</span></td>
        </tr>";
    }
    echo "</table>";
}
echo "
        </div>
    </div>";

// 3. 변수 매핑 검증
$varStatus = empty($variableMappingResult['unmatched']) ? 'ok' : 'warn';
echo "
    <h2 id='vars-header' class='collapsible' onclick='toggleCollapse(\"vars\")'>
        🔗 변수-프로퍼티 매핑
        <span class='status {$varStatus}'>" . count($variableMappingResult['unmatched']) . " 미매칭</span>
    </h2>
    <div id='vars-content' class='collapsible-content'>
        <div class='card {$varStatus}'>";

if (!empty($variableMappingResult['unmatched'])) {
    echo "<h3 style='color:#ff9800; margin-bottom:15px;'>⚠️ 미매칭 변수</h3>";
    foreach ($variableMappingResult['unmatched'] as $varName => $info) {
        echo "<div class='warning-message'>
            <strong>{$varName}</strong>: {$info['message']}<br>
            컨텍스트 키: " . implode(', ', $info['contextKeys']) . "
        </div>";
    }
}

if (!empty($variableMappingResult['matched'])) {
    echo "<h3 style='color:#00c853; margin-top:20px; margin-bottom:15px;'>✅ 매칭된 변수 (" . count($variableMappingResult['matched']) . "개)</h3>
        <table>
            <tr><th>변수명</th><th>온톨로지 프로퍼티</th><th>타입</th></tr>";
    foreach ($variableMappingResult['matched'] as $varName => $info) {
        $propId = $info['property']['id'] ?? '-';
        $propType = $info['property']['type'] ?? '-';
        echo "<tr>
            <td><span class='tag variable'>{$varName}</span></td>
            <td><span class='code'>{$propId}</span></td>
            <td>{$propType}</td>
        </tr>";
    }
    echo "</table>";
}
echo "
        </div>
    </div>";

// 4. Q1 룰 상세 분석
echo "
    <h2 id='q1-header' class='collapsible' onclick='toggleCollapse(\"q1\")'>
        🎯 Q1 첫수업 전략 룰 분석
        <span class='status ok'>" . count($q1RulesDiag) . " 룰</span>
    </h2>
    <div id='q1-content' class='collapsible-content'>
        <div class='card'>";

if (!empty($q1RulesDiag)) {
    echo "<table>
            <tr><th>룰 ID</th><th>온톨로지 액션 수</th><th>액션 목록</th></tr>";
    foreach ($q1RulesDiag as $ruleId => $info) {
        echo "<tr>
            <td><span class='code'>{$ruleId}</span></td>
            <td>{$info['action_count']}</td>
            <td>";
        foreach ($info['actions'] as $action) {
            $cleanAction = trim($action, '- "');
            echo "<div style='margin:3px 0;'><span class='tag action'>" . htmlspecialchars(substr($cleanAction, 0, 50)) . "...</span></div>";
        }
        echo "</td>
        </tr>";
    }
    echo "</table>";
} else {
    echo "<p>Q1 관련 룰을 찾을 수 없습니다.</p>";
}
echo "
        </div>
    </div>";

// 5. 인스턴스 테이블 상태
$instanceStatus = $instanceDiag['table_exists'] ? 'ok' : 'warn';
echo "
    <h2 id='instance-header' class='collapsible' onclick='toggleCollapse(\"instance\")'>
        💾 온톨로지 인스턴스 테이블
        <span class='status {$instanceStatus}'>" . ($instanceDiag['table_exists'] ? '존재' : '없음') . "</span>
    </h2>
    <div id='instance-content' class='collapsible-content'>
        <div class='card {$instanceStatus}'>";

if ($instanceDiag['table_exists']) {
    echo "
        <table>
            <tr><th>항목</th><th>값</th></tr>
            <tr><td>테이블</td><td><span class='code'>alt42_ontology_instances</span></td></tr>
            <tr><td>인스턴스 수</td><td>{$instanceDiag['instance_count']}</td></tr>
        </table>";
    
    if (!empty($instanceDiag['recent_instances'])) {
        echo "<h3 style='margin-top:20px; color:#00d9ff;'>최근 인스턴스</h3>
            <table>
                <tr><th>ID</th><th>인스턴스 ID</th><th>클래스</th><th>학생 ID</th><th>생성일</th></tr>";
        foreach ($instanceDiag['recent_instances'] as $inst) {
            $created = date('Y-m-d H:i', $inst->created_at);
            echo "<tr>
                <td>{$inst->id}</td>
                <td><span class='code'>" . substr($inst->instance_id, 0, 40) . "...</span></td>
                <td><span class='tag class'>{$inst->class_type}</span></td>
                <td>{$inst->student_id}</td>
                <td>{$created}</td>
            </tr>";
        }
        echo "</table>";
    }
} else {
    echo "<div class='warning-message'>테이블이 아직 생성되지 않았습니다. OntologyEngine이 처음 실행될 때 자동 생성됩니다.</div>";
}

if (isset($instanceDiag['db_error'])) {
    echo "<div class='error-message'>DB 오류: {$instanceDiag['db_error']}</div>";
}
echo "
        </div>
    </div>";

// 6. Q1 파이프라인 테스트 (선택적)
$pipelineResult = null;
$testStudentId = $_GET['test_student_id'] ?? null;

if ($testStudentId && isset($DB)) {
    require_once(__DIR__ . '/OntologyActionHandler.php');
    
    // 테스트용 컨텍스트 데이터
    $testContext = [
        'student_grade' => '중2',
        'school_name' => '테스트중학교',
        'academy_name' => '테스트학원',
        'academy_grade' => 'A반',
        'concept_progress' => '중2-1 일차방정식',
        'advanced_progress' => '중2-2 일차함수',
        'math_unit_mastery' => '일차방정식 완료',
        'current_progress_position' => '중2-1',
        'math_learning_style' => '개념형',
        'study_style' => '자기주도형',
        'exam_style' => '꾸준형',
        'math_confidence' => 6,
        'math_level' => '중위권'
    ];
    
    try {
        $handler = new OntologyActionHandler(null, $testContext, (int)$testStudentId);
        $pipelineResult = $handler->executeQ1Pipeline();
    } catch (Exception $e) {
        $pipelineResult = ['error' => $e->getMessage()];
    }
}

$pipelineStatus = $pipelineResult ? ($pipelineResult['success'] ?? false ? 'ok' : 'error') : 'warn';
echo "
    <h2 id='pipeline-header' class='collapsible' onclick='toggleCollapse(\"pipeline\")'>
        🚀 Q1 파이프라인 테스트
        <span class='status {$pipelineStatus}'>" . ($testStudentId ? ($pipelineResult['success'] ?? false ? '성공' : '실패') : '미실행') . "</span>
    </h2>
    <div id='pipeline-content' class='collapsible-content'>
        <div class='card {$pipelineStatus}'>";

if (!$testStudentId) {
    echo "<p>파이프라인을 테스트하려면 URL에 <span class='code'>?test_student_id=2</span> 파라미터를 추가하세요.</p>
          <p style='margin-top:10px;'><a href='?test_student_id=2' style='color:#00d9ff;'>👉 학생 ID 2로 테스트 실행</a></p>";
} else {
    echo "<h3 style='color:#00d9ff; margin-bottom:15px;'>테스트 학생 ID: {$testStudentId}</h3>";
    
    if (isset($pipelineResult['error'])) {
        echo "<div class='error-message'>{$pipelineResult['error']}</div>";
    } else if ($pipelineResult) {
        // 스테이지별 결과 표시
        if (!empty($pipelineResult['stages'])) {
            echo "<h4 style='color:#ffd700; margin:15px 0 10px;'>파이프라인 스테이지</h4>
                <table>
                    <tr><th>스테이지</th><th>상태</th><th>상세</th></tr>";
            foreach ($pipelineResult['stages'] as $stageName => $stageInfo) {
                $stageStatus = $stageInfo['status'] === 'completed' ? 'ok' : 'error';
                $stageDetails = isset($stageInfo['instance_id']) ? substr($stageInfo['instance_id'], 0, 40) . '...' : '-';
                echo "<tr>
                    <td>{$stageName}</td>
                    <td><span class='tag {$stageStatus}'>{$stageInfo['status']}</span></td>
                    <td><span class='code'>{$stageDetails}</span></td>
                </tr>";
            }
            echo "</table>";
        }
        
        // 전략 결과
        if (!empty($pipelineResult['strategy'])) {
            echo "<h4 style='color:#ffd700; margin:20px 0 10px;'>생성된 전략</h4>
                <pre style='background:#0d0d1a; padding:15px; border-radius:8px; overflow-x:auto; font-size:0.85em; color:#00ff88;'>" . 
                htmlspecialchars(json_encode($pipelineResult['strategy'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . 
                "</pre>";
        }
        
        // 절차 결과
        if (!empty($pipelineResult['procedure'])) {
            echo "<h4 style='color:#ffd700; margin:20px 0 10px;'>생성된 절차</h4>
                <pre style='background:#0d0d1a; padding:15px; border-radius:8px; overflow-x:auto; font-size:0.85em; color:#00ff88;'>" . 
                htmlspecialchars(json_encode($pipelineResult['procedure'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . 
                "</pre>";
        }
        
        // 검증 오류
        if (!empty($pipelineResult['errors'])) {
            echo "<h4 style='color:#ff5252; margin:20px 0 10px;'>검증 오류/경고</h4>";
            foreach ($pipelineResult['errors'] as $error) {
                $errorType = $error['type'] ?? 'unknown';
                $errorMsg = $error['message'] ?? json_encode($error);
                echo "<div class='warning-message'><strong>{$errorType}</strong>: {$errorMsg}</div>";
            }
        }
    }
}
echo "
        </div>
    </div>";

// 7. 전체 진단 JSON (디버깅용)
echo "
    <h2 id='json-header' class='collapsible collapsed' onclick='toggleCollapse(\"json\")'>
        📄 전체 진단 JSON
        <span class='status'>디버깅용</span>
    </h2>
    <div id='json-content' class='collapsible-content collapsed'>
        <div class='card'>
            <pre style='overflow-x:auto; font-size:0.8em; color:#00ff88;'>" . 
            htmlspecialchars(json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . 
            "</pre>
        </div>
    </div>";

echo "
    <button class='refresh-btn' onclick='refreshPage()'>🔄 새로고침</button>
    
    {$scripts}
</body>
</html>";

