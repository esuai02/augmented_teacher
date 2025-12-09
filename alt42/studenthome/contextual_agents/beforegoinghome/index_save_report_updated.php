<?php
/**
 * 업데이트된 save_report 로직
 * 파일: index_save_report_updated.php
 * 목적: 기존 index.php의 542-640줄을 대체할 코드
 * 날짜: 2025-11-13
 *
 * 변경사항:
 * 1. 복잡한 이모지 처리 로직 제거 (553-567줄) - utf8mb4 인코딩 사용
 * 2. INSERT → UPDATE JSON → UPDATE HTML 패턴 적용
 * 3. 각 단계별 상세 에러 로깅 추가
 * 4. 크기 검증 및 제한 적용
 */

// 이 코드는 index.php:542 위치부터 시작 (if ($tableExists) { 이후)

if ($tableExists) {
    // ============================================================
    // Step 1: 데이터 준비 및 검증
    // ============================================================

    // JSON 데이터 준비
    $jsonData = json_encode($reportData, JSON_UNESCAPED_UNICODE);
    $jsonSize = strlen($jsonData);

    $debugInfo['json_size'] = $jsonSize;
    $debugInfo['json_size_mb'] = round($jsonSize / (1024 * 1024), 2);

    // JSON 크기 검증 (16MB 제한)
    $maxJsonSize = 16 * 1024 * 1024;
    if ($jsonSize > $maxJsonSize) {
        $errorMessage = "JSON 데이터가 너무 큽니다: {$jsonSize} bytes (최대: {$maxJsonSize} bytes)";
        $debugInfo['json_size_exceeded'] = true;
        error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
        echo json_encode(['success' => false, 'message' => $errorMessage, 'debug' => $debugInfo], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // HTML 데이터 준비 (이모지 처리 로직 제거 - utf8mb4 사용)
    $htmlData = $reportHtml;
    $htmlSize = strlen($htmlData);

    $debugInfo['html_size'] = $htmlSize;
    $debugInfo['html_size_mb'] = round($htmlSize / (1024 * 1024), 2);

    // HTML 크기 제한 (4MB)
    $maxHtmlSize = 4 * 1024 * 1024;
    if ($htmlSize > $maxHtmlSize) {
        $htmlData = substr($htmlData, 0, $maxHtmlSize);
        $debugInfo['html_truncated'] = true;
        error_log("리포트 HTML 잘림 at " . __FILE__ . ":" . __LINE__ . " - 원본: {$htmlSize} bytes, 잘린 크기: {$maxHtmlSize} bytes");
    }

    // ============================================================
    // Step 2: 기본 레코드 INSERT
    // ============================================================

    try {
        $record = new stdClass();
        $record->userid = $studentId;
        $record->report_id = $reportId;
        $record->report_html = ''; // 빈 값으로 시작
        $record->report_data = ''; // 빈 값으로 시작
        $record->report_date = date('Y년 n월 j일');
        $record->timecreated = time();
        $record->timemodified = time();

        $insertId = $DB->insert_record($tableName, $record, true);

        if (!$insertId || $insertId <= 0) {
            $errorMessage = '기본 레코드 INSERT 실패: insert_record 반환값 없음 또는 0';
            $debugInfo['insert_failed'] = true;
            $debugInfo['insert_return_value'] = $insertId;
            error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
            echo json_encode(['success' => false, 'message' => $errorMessage, 'debug' => $debugInfo], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $debugInfo['insert_id'] = $insertId;
        $debugInfo['insert_success'] = true;

    } catch (dml_exception $e) {
        $errorMessage = '기본 레코드 INSERT 중 DB 오류: ' . $e->getMessage();
        $debugInfo['insert_dml_exception'] = $e->getMessage();
        $debugInfo['insert_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
        error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
        error_log("Details: " . json_encode($debugInfo, JSON_UNESCAPED_UNICODE));
        echo json_encode(['success' => false, 'message' => $errorMessage, 'debug' => $debugInfo], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (Exception $e) {
        $errorMessage = '기본 레코드 INSERT 중 오류: ' . $e->getMessage();
        $debugInfo['insert_exception'] = $e->getMessage();
        $debugInfo['insert_exception_class'] = get_class($e);
        error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
        echo json_encode(['success' => false, 'message' => $errorMessage, 'debug' => $debugInfo], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ============================================================
    // Step 3: JSON 데이터 UPDATE
    // ============================================================

    try {
        $updateJson = new stdClass();
        $updateJson->id = $insertId;
        $updateJson->report_data = $jsonData;
        $updateJson->timemodified = time();

        $jsonUpdateSuccess = $DB->update_record($tableName, $updateJson);

        if (!$jsonUpdateSuccess) {
            // JSON UPDATE 실패는 경고로 처리 (기본 레코드는 유지)
            $debugInfo['json_update_failed'] = true;
            error_log("JSON UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - report_id: {$reportId}, insert_id: {$insertId}");
            // 계속 진행 (부분 성공)
        } else {
            $debugInfo['json_update_success'] = true;
        }

    } catch (dml_exception $e) {
        $debugInfo['json_update_dml_exception'] = $e->getMessage();
        $debugInfo['json_update_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
        error_log("JSON UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - {$e->getMessage()}");
        // 계속 진행 (부분 성공)
    } catch (Exception $e) {
        $debugInfo['json_update_exception'] = $e->getMessage();
        error_log("JSON UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - {$e->getMessage()}");
        // 계속 진행 (부분 성공)
    }

    // ============================================================
    // Step 4: HTML 데이터 UPDATE
    // ============================================================

    try {
        $updateHtml = new stdClass();
        $updateHtml->id = $insertId;
        $updateHtml->report_html = $htmlData; // utf8mb4 인코딩으로 이모지 그대로 저장
        $updateHtml->timemodified = time();

        $htmlUpdateSuccess = $DB->update_record($tableName, $updateHtml);

        if (!$htmlUpdateSuccess) {
            // HTML UPDATE 실패는 경고로 처리 (기본 레코드와 JSON은 유지)
            $debugInfo['html_update_failed'] = true;
            error_log("HTML UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - report_id: {$reportId}, insert_id: {$insertId}");
            // 계속 진행 (부분 성공)
        } else {
            $debugInfo['html_update_success'] = true;
        }

    } catch (dml_exception $e) {
        $debugInfo['html_update_dml_exception'] = $e->getMessage();
        $debugInfo['html_update_error_code'] = isset($e->errorcode) ? $e->errorcode : 'unknown';
        error_log("HTML UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - {$e->getMessage()}");
        error_log("HTML size: {$htmlSize} bytes, contains emoji: " . (preg_match('/[\x{1F600}-\x{1F64F}]/u', $htmlData) ? 'yes' : 'no'));
        // 계속 진행 (부분 성공)
    } catch (Exception $e) {
        $debugInfo['html_update_exception'] = $e->getMessage();
        error_log("HTML UPDATE 실패 at " . __FILE__ . ":" . __LINE__ . " - {$e->getMessage()}");
        // 계속 진행 (부분 성공)
    }

    // ============================================================
    // Step 5: 최종 성공 여부 결정
    // ============================================================

    // 기본 레코드 INSERT는 항상 성공했으므로 saveSuccess = true
    $saveSuccess = true;

    // 부분 실패 여부 확인
    $partialSuccess = false;
    if (!($debugInfo['json_update_success'] ?? false) || !($debugInfo['html_update_success'] ?? false)) {
        $partialSuccess = true;
        $debugInfo['partial_success'] = true;
        $debugInfo['partial_reason'] = [];

        if (!($debugInfo['json_update_success'] ?? false)) {
            $debugInfo['partial_reason'][] = 'JSON UPDATE 실패';
        }
        if (!($debugInfo['html_update_success'] ?? false)) {
            $debugInfo['partial_reason'][] = 'HTML UPDATE 실패';
        }
    }

    $debugInfo['save_strategy'] = 'progressive_update';
    $debugInfo['emoji_processing'] = 'utf8mb4_native';

} else {
    $errorMessage = '리포트 테이블(alt42_goinghome_reports)이 존재하지 않습니다.';
    $debugInfo['table_exists'] = false;
    error_log("리포트 저장 실패 at " . __FILE__ . ":" . __LINE__ . " - {$errorMessage}");
}

// 기존 테이블에도 저장 (하위 호환성) - 이 부분은 기존 코드와 동일하게 유지
// index.php:646 이후 코드 그대로 사용
