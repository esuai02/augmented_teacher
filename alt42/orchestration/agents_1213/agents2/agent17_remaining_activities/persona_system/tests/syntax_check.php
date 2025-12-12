<?php
/**
 * Agent17 PHP 문법 검사 스크립트
 *
 * 인증 없이 PHP 파일의 문법 오류를 확인합니다.
 * URL: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/tests/syntax_check.php
 *
 * @package AugmentedTeacher\Agent17\Tests
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$currentFile = __FILE__;
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => [],
    'overall' => true
];

// 검사할 파일 목록
$filesToCheck = [
    'engine/Agent17PersonaEngine.php',
    'engine/config/agent_config.php',
    'api/chat.php',
    // Fallback 구현체
    'engine/fallback/Agent17RuleParser.php',
    'engine/fallback/Agent17ConditionEvaluator.php',
    'engine/fallback/Agent17ActionExecutor.php',
    'engine/fallback/Agent17DataContext.php',
    'engine/fallback/Agent17ResponseGenerator.php'
];

$baseDir = dirname(__DIR__);

foreach ($filesToCheck as $file) {
    $fullPath = $baseDir . '/' . $file;
    $check = [
        'file' => $file,
        'exists' => false,
        'syntax_valid' => false,
        'error' => null
    ];

    if (file_exists($fullPath)) {
        $check['exists'] = true;

        // PHP 문법 검사
        $output = [];
        $returnCode = 0;
        exec("php -l " . escapeshellarg($fullPath) . " 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $check['syntax_valid'] = true;
        } else {
            $check['syntax_valid'] = false;
            $check['error'] = implode("\n", $output);
            $results['overall'] = false;
        }
    } else {
        $check['error'] = "File not found: {$fullPath}";
        $results['overall'] = false;
    }

    $results['checks'][] = $check;
}

// 클래스 로드 테스트 (Moodle 없이)
$results['class_load_test'] = [
    'status' => 'skipped',
    'reason' => 'Requires Moodle environment - use test_engine.php with authentication'
];

// 템플릿 파일 존재 확인
$templateDir = $baseDir . '/templates/default/';
$expectedTemplates = ['R1_default.php', 'R2_default.php', 'R3_default.php', 'R4_default.php', 'R5_default.php'];
$templateCheck = [
    'total' => count($expectedTemplates),
    'found' => 0,
    'missing' => []
];

foreach ($expectedTemplates as $template) {
    if (file_exists($templateDir . $template)) {
        $templateCheck['found']++;
    } else {
        $templateCheck['missing'][] = $template;
    }
}

$results['templates'] = $templateCheck;

// 설정 파일 검증
$configPath = $baseDir . '/engine/config/agent_config.php';
if (file_exists($configPath)) {
    try {
        $config = @include($configPath);
        if (is_array($config) && isset($config['agent']['id'])) {
            $results['config'] = [
                'valid' => true,
                'agent_id' => $config['agent']['id'],
                'agent_name' => $config['agent']['name'] ?? 'unknown'
            ];
        } else {
            $results['config'] = [
                'valid' => false,
                'error' => 'Invalid config structure'
            ];
            $results['overall'] = false;
        }
    } catch (Exception $e) {
        $results['config'] = [
            'valid' => false,
            'error' => $e->getMessage()
        ];
        $results['overall'] = false;
    }
} else {
    $results['config'] = [
        'valid' => false,
        'error' => 'Config file not found'
    ];
    $results['overall'] = false;
}

// 결과 출력
echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

/*
 * 관련 테스트 URL:
 * - 전체 테스트 (인증 필요): test_engine.php
 * - 문법 검사 (인증 불필요): syntax_check.php
 *
 * 사용법:
 * curl https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent17_remaining_activities/persona_system/tests/syntax_check.php
 */
