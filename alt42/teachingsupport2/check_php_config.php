<?php
/**
 * check_php_config.php - PHP 설정 진단 스크립트
 * 파일 위치: alt42/teachingsupport/check_php_config.php
 *
 * 15MB 이미지 업로드를 위한 PHP 설정을 검사합니다.
 */

header('Content-Type: application/json; charset=UTF-8');

try {
    // 필요한 설정값 (15MB 업로드 지원)
    $required = [
        'upload_max_filesize' => 20, // 20MB (base64 인코딩으로 인한 증가 고려)
        'post_max_size' => 25,        // 25MB
        'memory_limit' => 128,        // 128MB
        'max_execution_time' => 60,   // 60초
        'max_input_time' => 60        // 60초
    ];

    $config = [];
    $issues = [];
    $warnings = [];

    // 현재 설정 확인
    foreach ($required as $key => $minValue) {
        $currentValue = ini_get($key);
        $config[$key] = $currentValue;

        // 값 파싱 (숫자로 변환)
        $currentNumeric = parseSize($currentValue);

        if ($key === 'upload_max_filesize' || $key === 'post_max_size' || $key === 'memory_limit') {
            // MB 단위로 비교
            if ($currentNumeric < $minValue) {
                $issues[] = [
                    'setting' => $key,
                    'current' => $currentValue,
                    'required' => $minValue . 'M',
                    'message' => "$key가 너무 작습니다. 최소 {$minValue}M 필요"
                ];
            }
        } else {
            // 초 단위로 비교
            if ($currentNumeric < $minValue && $currentNumeric != -1) { // -1은 무제한
                $warnings[] = [
                    'setting' => $key,
                    'current' => $currentValue,
                    'required' => $minValue,
                    'message' => "$key가 짧을 수 있습니다. 최소 {$minValue}초 권장"
                ];
            }
        }
    }

    // MySQL max_allowed_packet 확인 (가능한 경우)
    $mysqlInfo = null;
    if (file_exists("/home/moodle/public_html/moodle/config.php")) {
        try {
            include_once("/home/moodle/public_html/moodle/config.php");
            global $DB;

            $result = $DB->get_record_sql("SHOW VARIABLES LIKE 'max_allowed_packet'");
            if ($result) {
                $mysqlInfo = [
                    'max_allowed_packet' => $result->value,
                    'max_allowed_packet_mb' => round($result->value / 1024 / 1024, 2)
                ];

                // 25MB 미만이면 경고
                if ($result->value < 25 * 1024 * 1024) {
                    $warnings[] = [
                        'setting' => 'MySQL max_allowed_packet',
                        'current' => round($result->value / 1024 / 1024, 2) . 'M',
                        'required' => '25M',
                        'message' => 'MySQL max_allowed_packet이 작습니다. 큰 이미지 저장 시 문제가 발생할 수 있습니다.'
                    ];
                }
            }
        } catch (Exception $e) {
            $mysqlInfo = ['error' => $e->getMessage()];
        }
    }

    // 결과 생성
    $canHandle15MB = count($issues) === 0;

    $result = [
        'success' => true,
        'can_handle_15mb' => $canHandle15MB,
        'current_config' => $config,
        'mysql_config' => $mysqlInfo,
        'issues' => $issues,
        'warnings' => $warnings,
        'recommendation' => $canHandle15MB
            ? '현재 설정으로 15MB 업로드가 가능합니다.'
            : '아래 설정을 php.ini에서 수정해주세요.',
        'php_ini_location' => php_ini_loaded_file(),
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => $e->getLine()
    ], JSON_PRETTY_PRINT);
}

/**
 * 설정값을 숫자로 파싱 (K, M, G 단위 처리)
 */
function parseSize($size) {
    $size = trim($size);
    $last = strtolower($size[strlen($size)-1]);
    $size = (int)$size;

    switch($last) {
        case 'g':
            $size *= 1024;
        case 'm':
            $size *= 1024;
        case 'k':
            $size *= 1024;
    }

    // MB 단위로 반환
    return round($size / 1024 / 1024, 2);
}
?>
