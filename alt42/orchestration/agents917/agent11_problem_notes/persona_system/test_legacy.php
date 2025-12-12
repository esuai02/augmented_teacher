<?php
/**
 * Agent11 PersonaEngine 테스트
 *
 * 페르소나 엔진 기능 테스트 및 검증
 * 실행: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent11_problem_notes/persona_system/test.php
 *
 * @package AugmentedTeacher\Agent11\PersonaSystem
 * @version 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

$currentFile = __FILE__;

// 관리자 권한 확인
$context = context_system::instance();
if (!has_capability('moodle/site:config', $context)) {
    die("[{$currentFile}:" . __LINE__ . "] 관리자 권한이 필요합니다.");
}

require_once(__DIR__ . '/PersonaEngine.php');
require_once(__DIR__ . '/config.php');

use AugmentedTeacher\Agent11\PersonaSystem\Agent11PersonaEngine;
use AugmentedTeacher\Agent11\PersonaSystem\Agent11Config;

$results = [];
$errors = [];

/**
 * 테스트 실행
 */
function runTest($name, $callback) {
    global $results, $errors;
    
    try {
        $start = microtime(true);
        $result = $callback();
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        
        $results[] = [
            'name' => $name,
            'success' => true,
            'result' => $result,
            'time' => $elapsed
        ];
    } catch (Exception $e) {
        $results[] = [
            'name' => $name,
            'success' => false,
            'error' => $e->getMessage()
        ];
        $errors[] = $name;
    }
}

// =====================================================
// 테스트 1: 설정 로드
// =====================================================
runTest('설정 로드', function() {
    $default = Agent11Config::get('personas.default');
    $personas = Agent11Config::getAvailablePersonas();
    
    if ($default !== 'AnalyticalHelper') {
        throw new Exception("기본 페르소나가 올바르지 않음: {$default}");
    }
    if (count($personas) !== 4) {
        throw new Exception("페르소나 수가 올바르지 않음: " . count($personas));
    }
    
    return "기본: {$default}, 총 " . count($personas) . "개 페르소나";
});

// =====================================================
// 테스트 2: 엔진 초기화
// =====================================================
runTest('엔진 초기화', function() {
    $engine = new Agent11PersonaEngine(true);
    $agentId = $engine->getAgentId();
    
    if ($agentId !== 'agent11') {
        throw new Exception("에이전트 ID가 올바르지 않음: {$agentId}");
    }
    
    return "에이전트 ID: {$agentId}";
});

// =====================================================
// 테스트 3: 페르소나 특성 조회
// =====================================================
runTest('페르소나 특성 조회', function() {
    $engine = new Agent11PersonaEngine(false);
    $all = $engine->getPersonaCharacteristics();
    $specific = $engine->getPersonaCharacteristics('EncouragingCoach');
    
    if (count($all) !== 4) {
        throw new Exception("전체 페르소나 수가 올바르지 않음");
    }
    if ($specific['tone'] !== 'Encouraging') {
        throw new Exception("EncouragingCoach 톤이 올바르지 않음");
    }
    
    return "EncouragingCoach: " . $specific['name'];
});

// =====================================================
// 테스트 4: 페르소나 결정 (감정 기반)
// =====================================================
runTest('페르소나 결정 (감정 기반)', function() use ($USER) {
    $engine = new Agent11PersonaEngine(false);
    
    // 좌절 상태 → EncouragingCoach
    $persona = $engine->determinePersona($USER->id, [
        'emotional_state' => 'frustrated'
    ]);
    
    if ($persona !== 'EncouragingCoach') {
        throw new Exception("좌절 상태에서 EncouragingCoach가 아님: {$persona}");
    }
    
    return "좌절 상태 → {$persona}";
});

// =====================================================
// 테스트 5: 페르소나 결정 (오류 유형 기반)
// =====================================================
runTest('페르소나 결정 (오류 유형 기반)', function() use ($USER) {
    $engine = new Agent11PersonaEngine(false);
    
    // 개념 혼동 → AnalyticalHelper
    $persona = $engine->determinePersona($USER->id, [
        'error_type' => 'concept_confusion'
    ]);
    
    if ($persona !== 'AnalyticalHelper') {
        throw new Exception("개념 혼동에서 AnalyticalHelper가 아님: {$persona}");
    }
    
    return "개념 혼동 → {$persona}";
});

// =====================================================
// 테스트 6: 상태 동기화
// =====================================================
runTest('상태 동기화', function() use ($USER) {
    $engine = new Agent11PersonaEngine(false);
    $stateSync = $engine->getStateSync();
    
    // 상태 저장
    $saved = $stateSync->saveState($USER->id, 'AnalyticalHelper', [
        'test' => true,
        'timestamp' => time()
    ], false);  // 브로드캐스트 비활성화 (테스트)
    
    if (!$saved) {
        throw new Exception("상태 저장 실패");
    }
    
    // 상태 조회
    $state = $stateSync->getState($USER->id);
    if (!$state || $state['persona_id'] !== 'AnalyticalHelper') {
        throw new Exception("상태 조회 실패");
    }
    
    return "저장 및 조회 성공";
});

// =====================================================
// 테스트 7: 오류 분류 설정
// =====================================================
runTest('오류 분류 설정', function() {
    $classifications = Agent11Config::getErrorClassifications();
    
    if (!isset($classifications['concept_confusion'])) {
        throw new Exception("concept_confusion 분류 없음");
    }
    
    return count($classifications) . "개 오류 분류";
});

// =====================================================
// 결과 출력
// =====================================================
$successCount = count(array_filter($results, fn($r) => $r['success']));
$totalCount = count($results);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Agent11 PersonaEngine 테스트</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 900px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .summary { padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .summary.success { background: #d4edda; color: #155724; }
        .summary.partial { background: #fff3cd; color: #856404; }
        .summary.failure { background: #f8d7da; color: #721c24; }
        .test { margin: 10px 0; padding: 15px; border-radius: 5px; border-left: 4px solid #ccc; }
        .test.success { background: #f8f9fa; border-left-color: #28a745; }
        .test.failure { background: #fff5f5; border-left-color: #dc3545; }
        .test-name { font-weight: bold; margin-bottom: 5px; }
        .test-result { color: #666; font-size: 0.9em; }
        .test-time { color: #999; font-size: 0.8em; }
        .test-error { color: #dc3545; font-size: 0.9em; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; font-size: 0.85em; }
    </style>
</head>
<body>
    <h1>🧪 Agent11 PersonaEngine 테스트</h1>
    <p>실행 시간: <?php echo date('Y-m-d H:i:s'); ?></p>

    <div class="summary <?php echo $successCount === $totalCount ? 'success' : ($successCount > 0 ? 'partial' : 'failure'); ?>">
        <strong>결과: <?php echo $successCount; ?>/<?php echo $totalCount; ?> 테스트 통과</strong>
        <?php if (!empty($errors)): ?>
            <br>실패: <?php echo implode(', ', $errors); ?>
        <?php endif; ?>
    </div>

    <h2>테스트 상세</h2>
    <?php foreach ($results as $r): ?>
        <div class="test <?php echo $r['success'] ? 'success' : 'failure'; ?>">
            <div class="test-name">
                <?php echo $r['success'] ? '✅' : '❌'; ?>
                <?php echo htmlspecialchars($r['name']); ?>
            </div>
            <?php if ($r['success']): ?>
                <div class="test-result"><?php echo htmlspecialchars($r['result'] ?? ''); ?></div>
                <div class="test-time"><?php echo $r['time']; ?>ms</div>
            <?php else: ?>
                <div class="test-error"><?php echo htmlspecialchars($r['error']); ?></div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <h2>설정 정보</h2>
    <pre><?php echo htmlspecialchars(json_encode(Agent11Config::getAll(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></pre>

    <p>
        <a href="https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/ontology_engineering/persona_engine/db/install.php">
            ← DB 설치 스크립트
        </a>
    </p>
</body>
</html>
