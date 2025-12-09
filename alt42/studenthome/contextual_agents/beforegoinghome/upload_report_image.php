<?php
// 리포트 이미지 업로드 처리
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 학생 ID 가져오기
$studentId = isset($_POST['studentid']) ? intval($_POST['studentid']) : $USER->id;
$filename = isset($_POST['filename']) ? $_POST['filename'] : '';

// 학생 이름 가져오기 (공백 제거)
$student = $DB->get_record('user', ['id' => $studentId]);
$studentName = $student ? str_replace(' ', '', $student->firstname . $student->lastname) : '학생';

// 업로드 디렉토리 설정
$uploadDir = '/home/moodle/public_html/studentimg/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// 파일 업로드 처리
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['image'];
    
    // 파일명이 전달되지 않았으면 생성
    if (empty($filename)) {
        $today = date('Y-m-d');
        $dayOfWeek = ['일', '월', '화', '수', '목', '금', '토'][date('w')];
        $originalFilename = '귀가검사결과_' . $studentName . '_' . $today . '_' . $dayOfWeek . '.png';
    } else {
        $originalFilename = $filename;
    }
    
    // 파일명 검증 및 정리 (한글은 유지하되 특수문자만 제거)
    $originalFilename = preg_replace('/[^a-zA-Z0-9가-힣._-]/u', '_', $originalFilename);
    $filePath = $uploadDir . $originalFilename;
    
    // 기존 파일이 있으면 삭제 (덮어쓰기)
    if (file_exists($filePath)) {
        @unlink($filePath);
    }
    
    // 파일 이동
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        // DB에 기록 (선택사항) - 기존 레코드가 있으면 업데이트, 없으면 삽입
        try {
            if ($DB->get_manager()->table_exists('alt42_goinghome_images')) {
                // 기존 레코드 확인 (같은 학생, 같은 파일명)
                $existingRecord = $DB->get_record('alt42_goinghome_images', [
                    'userid' => $studentId,
                    'filename' => $originalFilename
                ]);
                
                if ($existingRecord) {
                    // 기존 레코드 업데이트
                    $existingRecord->filepath = $filePath;
                    $existingRecord->timecreated = time();
                    $DB->update_record('alt42_goinghome_images', $existingRecord);
                } else {
                    // 새 레코드 삽입
                    $record = new stdClass();
                    $record->userid = $studentId;
                    $record->filename = $originalFilename;
                    $record->filepath = $filePath;
                    $record->timecreated = time();
                    $DB->insert_record('alt42_goinghome_images', $record);
                }
            }
        } catch (Exception $e) {
            error_log('Error saving image record in upload_report_image.php (line ' . __LINE__ . '): ' . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => '이미지 업로드 성공',
            'filename' => $originalFilename,
            'url' => '/studentimg/' . $originalFilename
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => '파일 업로드 실패'
        ]);
    }
} else {
    $errorMsg = '파일 업로드 오류';
    if (isset($_FILES['image']['error'])) {
        switch ($_FILES['image']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = '파일 크기가 너무 큽니다';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = '파일이 부분적으로만 업로드되었습니다';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = '파일이 업로드되지 않았습니다';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMsg = '임시 폴더를 찾을 수 없습니다';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMsg = '파일을 디스크에 쓸 수 없습니다';
                break;
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMsg . ' (upload_report_image.php, line ' . __LINE__ . ')'
    ]);
}
?>

