<?php
/**
 * Agent02 시스템 검증 API
 *
 * DB 테이블 존재 여부, 파일 존재 여부, 규칙 파싱 상태 등을 검증합니다.
 * 인증 없이 시스템 상태만 확인하는 공개 엔드포인트입니다.
 *
 * 실행: GET https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent02_exam_schedule/api/verify_system.php
 *
 * @package AugmentedTeacher\Agent02\API
 * @version 1.0
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$currentFile = __FILE__;
$results = [];
$errors = [];

// ============================================
// 1. 파일 존재 여부 확인
// ============================================
$basePath = dirname(dirname(__FILE__));
$requiredFiles = [
    'persona_system/engine/Agent02DataContext.php' => 'DataContext 클래스',
    'persona_system/engine/Agent02PersonaRuleEngine.php' => 'RuleEngine 클래스',
    'persona_system/engine/RuleParser.php' => 'YAML 파서',
    'persona_system/engine/ConditionEvaluator.php' => '조건 평가기',
    'persona_system/engine/ActionExecutor.php' => '액션 실행기',
    'persona_system/engine/RuleCache.php' => '규칙 캐시',
    'persona_system/engine/config/ai_config.php' => 'AI 설정',
    'persona_system/rules.yaml' => '규칙 파일',
    'persona_system/personas.md' => '페르소나 정의',
    'api/chat.php' => '채팅 API',
    'db_setup.php' => 'DB 설정'
];

$fileResults = [];
foreach ($requiredFiles as $file => $description) {
    $fullPath = $basePath . '/' . $file;
    $exists = file_exists($fullPath);
    $fileResults[$file] = [
        'exists' => $exists,
        'description' => $description,
        'size' => $exists ? filesize($fullPath) : 0
    ];
    if (!$exists) {
        $errors[] = "파일 누락: $file ($description) | $currentFile:" . __LINE__;
    }
}

$results['files'] = [
    'total' => count($requiredFiles),
    'found' => count(array_filter($fileResults, function($f) { return $f['exists']; })),
    'details' => $fileResults
];

// ============================================
// 2. DB 연결 및 테이블 확인
// ============================================
try {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $CFG;

    $results['db_connection'] = [
        'status' => 'connected',
        'prefix' => $CFG->prefix
    ];

    // 테이블 존재 확인 (직접 쿼리)
    $tablesToCheck = [
        'at_exam_schedules' => 'Agent02 시험 일정',
        'at_exam_study_logs' => 'Agent02 학습 로그',
        'at_agent02_dday_snapshots' => 'D-Day 스냅샷',
        'augmented_teacher_sessions' => '공유 세션 (필수)',
        'augmented_teacher_personas' => '공유 페르소나 (필수)',
        'augmented_teacher_ai_context' => '공유 AI 컨텍스트 (필수)',
        'augmented_teacher_user_types' => '공유 사용자 유형 (필수)'
    ];

    $tableResults = [];
    foreach ($tablesToCheck as $table => $description) {
        $fullTable = $CFG->prefix . $table;
        try {
            $exists = $DB->get_manager()->table_exists($table);
            $count = null;
            if ($exists) {
                try {
                    $count = $DB->count_records($table);
                } catch (Exception $e) {
                    $count = 'error';
                }
            }
            $tableResults[$table] = [
                'exists' => $exists,
                'description' => $description,
                'record_count' => $count
            ];
        } catch (Exception $e) {
            $tableResults[$table] = [
                'exists' => false,
                'description' => $description,
                'error' => $e->getMessage()
            ];
        }

        if (!($tableResults[$table]['exists'] ?? false)) {
            $errors[] = "테이블 누락: $fullTable ($description) | $currentFile:" . __LINE__;
        }
    }

    $results['tables'] = [
        'total' => count($tablesToCheck),
        'found' => count(array_filter($tableResults, function($t) { return ($t['exists'] ?? false); })),
        'details' => $tableResults
    ];

} catch (Exception $e) {
    $results['db_connection'] = [
        'status' => 'error',
        'message' => $e->getMessage()
    ];
    $errors[] = "DB 연결 실패: " . $e->getMessage() . " | $currentFile:" . __LINE__;
}

// ============================================
// 3. YAML 규칙 파일 파싱 테스트
// ============================================
$rulesPath = $basePath . '/persona_system/rules.yaml';
try {
    if (file_exists($rulesPath)) {
        $yamlContent = file_get_contents($rulesPath);

        // YAML 파서 로드
        require_once($basePath . '/persona_system/engine/RuleParser.php');

        $parser = new RuleParser();
        $rules = $parser->parseFile($rulesPath);

        $results['rules_yaml'] = [
            'status' => 'parsed',
            'file_size' => strlen($yamlContent),
            'rule_count' => count($rules['rules'] ?? []),
            'persona_count' => count($rules['personas'] ?? []),
            'sample_rules' => array_slice(array_keys($rules['rules'] ?? []), 0, 5)
        ];
    } else {
        $results['rules_yaml'] = [
            'status' => 'missing',
            'path' => $rulesPath
        ];
        $errors[] = "rules.yaml 파일 없음 | $currentFile:" . __LINE__;
    }
} catch (Exception $e) {
    $results['rules_yaml'] = [
        'status' => 'parse_error',
        'error' => $e->getMessage()
    ];
    $errors[] = "YAML 파싱 오류: " . $e->getMessage() . " | $currentFile:" . __LINE__;
}

// ============================================
// 4. 페르소나 정의 파일 확인
// ============================================
$personasPath = $basePath . '/persona_system/personas.md';
try {
    if (file_exists($personasPath)) {
        $content = file_get_contents($personasPath);

        // 페르소나 ID 추출 (## D_URGENT_P1 형식)
        preg_match_all('/^## (D_[A-Z]+_P[1-6]|[CEQ]_P[1-3])/m', $content, $matches);
        $personaIds = $matches[1] ?? [];

        $results['personas_md'] = [
            'status' => 'found',
            'file_size' => strlen($content),
            'persona_count' => count($personaIds),
            'expected_count' => 33,
            'sample_personas' => array_slice($personaIds, 0, 8)
        ];

        if (count($personaIds) < 33) {
            $errors[] = "페르소나 정의 부족: " . count($personaIds) . "/33 | $currentFile:" . __LINE__;
        }
    } else {
        $results['personas_md'] = [
            'status' => 'missing'
        ];
        $errors[] = "personas.md 파일 없음 | $currentFile:" . __LINE__;
    }
} catch (Exception $e) {
    $results['personas_md'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// ============================================
// 5. DataContext 클래스 로드 테스트
// ============================================
try {
    require_once($basePath . '/persona_system/engine/Agent02DataContext.php');

    $testContext = new Agent02DataContext([
        'user_id' => 1,
        'user_message' => '테스트 메시지',
        'dday' => 5,
        'student_type' => 'P1'
    ]);

    $situation = $testContext->getDDaySituation();

    $results['data_context'] = [
        'status' => 'loaded',
        'test_dday' => 5,
        'test_situation' => $situation,
        'expected_situation' => 'D_BALANCED'
    ];

    if ($situation !== 'D_BALANCED') {
        $errors[] = "D-Day 상황 계산 오류: $situation (예상: D_BALANCED) | $currentFile:" . __LINE__;
    }
} catch (Exception $e) {
    $results['data_context'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    $errors[] = "DataContext 로드 실패: " . $e->getMessage() . " | $currentFile:" . __LINE__;
}

// ============================================
// 6. RuleEngine 클래스 로드 테스트
// ============================================
try {
    require_once($basePath . '/persona_system/engine/Agent02PersonaRuleEngine.php');

    $engine = new Agent02PersonaRuleEngine([
        'rules_path' => $basePath . '/persona_system/rules.yaml',
        'cache_enabled' => false
    ]);

    $results['rule_engine'] = [
        'status' => 'loaded',
        'class' => get_class($engine)
    ];
} catch (Exception $e) {
    $results['rule_engine'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    $errors[] = "RuleEngine 로드 실패: " . $e->getMessage() . " | $currentFile:" . __LINE__;
}

// ============================================
// 최종 결과 종합
// ============================================
$allPassed = empty($errors);

$output = [
    'success' => $allPassed,
    'timestamp' => date('Y-m-d H:i:s'),
    'agent' => 'agent02_exam_schedule',
    'version' => '1.0',
    'summary' => [
        'files' => $results['files']['found'] . '/' . $results['files']['total'],
        'tables' => ($results['tables']['found'] ?? 0) . '/' . ($results['tables']['total'] ?? 0),
        'personas' => ($results['personas_md']['persona_count'] ?? 0) . '/33',
        'rules' => ($results['rules_yaml']['rule_count'] ?? 0) . ' rules'
    ],
    'details' => $results,
    'errors' => $errors,
    'file' => $currentFile
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
