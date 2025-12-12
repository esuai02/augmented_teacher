<?php
/**
 * 서버 동기화 확인용 스크립트
 * 생성 시간: 2025-12-03 (로컬)
 */
header('Content-Type: application/json; charset=UTF-8');

$basePath = dirname(dirname(__FILE__));
$enginePath = $basePath . '/persona_system/engine/Agent02PersonaRuleEngine.php';

$result = [
    'sync_check' => 'CREATED_2025_12_03_V1',
    'server_time' => date('Y-m-d H:i:s'),
    'engine_file_exists' => file_exists($enginePath),
    'engine_file_mtime' => file_exists($enginePath) ? date('Y-m-d H:i:s', filemtime($enginePath)) : null,
    'engine_file_size' => file_exists($enginePath) ? filesize($enginePath) : null,
    'base_path' => $basePath
];

// 파일 내용에서 student_type 관련 라인 추출
if (file_exists($enginePath)) {
    $content = file_get_contents($enginePath);

    // student_type 체크 로직 검색
    $result['has_direct_student_type'] = strpos($content, "context['student_type']") !== false;
    $result['has_preg_match_p16'] = strpos($content, "preg_match('/^P[1-6]\$/'") !== false;

    // determineStudentType 메서드 추출 (약 20줄)
    if (preg_match('/private function determineStudentType.*?(?=private function|$)/s', $content, $match)) {
        $methodContent = substr($match[0], 0, 1000);  // 처음 1000자만
        $result['determineStudentType_snippet'] = $methodContent;
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
