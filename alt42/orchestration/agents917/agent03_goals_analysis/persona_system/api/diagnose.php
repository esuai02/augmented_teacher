<?php
/**
 * 경로 진단 스크립트
 * 서버에서 필요한 파일들의 존재 여부를 확인합니다.
 */

header('Content-Type: application/json; charset=utf-8');

$diagnostics = [];

// 현재 스크립트 정보
$diagnostics['current_script'] = [
    'file' => __FILE__,
    'dir' => __DIR__
];

// 엔진 파일 경로
$engineDir = __DIR__ . '/../engine';
$diagnostics['engine_dir'] = [
    'path' => $engineDir,
    'real_path' => realpath($engineDir),
    'exists' => is_dir($engineDir)
];

// Agent03PersonaEngine.php 파일
$engineFile = $engineDir . '/Agent03PersonaEngine.php';
$diagnostics['engine_file'] = [
    'path' => $engineFile,
    'exists' => file_exists($engineFile)
];

// ontology_engineering 경로 계산
$basePath = dirname(__DIR__, 4);
$diagnostics['base_path'] = [
    'dirname_4' => $basePath,
    'real_path' => realpath($basePath),
    'exists' => is_dir($basePath)
];

// ontology_engineering 폴더
$ontologyPath = $basePath . '/ontology_engineering';
$diagnostics['ontology_path'] = [
    'path' => $ontologyPath,
    'real_path' => realpath($ontologyPath),
    'exists' => is_dir($ontologyPath)
];

// persona_engine 폴더
$personaEnginePath = $ontologyPath . '/persona_engine';
$diagnostics['persona_engine_path'] = [
    'path' => $personaEnginePath,
    'real_path' => realpath($personaEnginePath),
    'exists' => is_dir($personaEnginePath)
];

// 필수 파일들 체크
$requiredFiles = [
    '/core/AbstractPersonaEngine.php',
    '/impl/BaseRuleParser.php',
    '/impl/BaseConditionEvaluator.php',
    '/impl/BaseActionExecutor.php',
    '/impl/BaseDataContext.php',
    '/impl/BaseResponseGenerator.php',
    '/config/persona_engine.config.php'
];

$diagnostics['required_files'] = [];
foreach ($requiredFiles as $file) {
    $fullPath = $personaEnginePath . $file;
    $diagnostics['required_files'][$file] = [
        'path' => $fullPath,
        'exists' => file_exists($fullPath),
        'readable' => is_readable($fullPath)
    ];
}

// 디렉토리 목록 (persona_engine이 존재하는 경우)
if (is_dir($personaEnginePath)) {
    $diagnostics['persona_engine_contents'] = scandir($personaEnginePath);
}

// 상위 디렉토리 목록 (ontology_engineering이 존재하는 경우)
if (is_dir($ontologyPath)) {
    $diagnostics['ontology_contents'] = scandir($ontologyPath);
}

// orchestration 디렉토리 목록 확인
$orchestrationPath = $basePath;
if (is_dir($orchestrationPath)) {
    $diagnostics['orchestration_contents'] = scandir($orchestrationPath);
}

// 결과 출력
echo json_encode([
    'success' => true,
    'timestamp' => date('Y-m-d H:i:s'),
    'diagnostics' => $diagnostics
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
