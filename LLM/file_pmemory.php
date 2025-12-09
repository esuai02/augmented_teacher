<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

$path = "/home/moodle/public_html/Contents/audiofiles/pmemory/";
$valid_formats = array( "wav","mp3", "ogg", "m4a", "flac");
$data   = array();
$contentstype = $_POST['contentstype'];
$contentsid = $_POST['contentsid'];
$section = isset($_POST['section']) ? $_POST['section'] : null; // 구간 번호 (듣기평가용)
$filekey = isset($_POST['filekey']) ? $_POST['filekey'] : null; // JSON 절차기억 키 (Q1, B1, E1_1 등)
$data['success'] = false;
$hostname = $_SERVER["HTTP_HOST"];

// 로깅 함수
function logUploadActivity($message, $type = 'info') {
    global $USER, $contentsid, $contentstype;
    $timestamp = date('Y-m-d H:i:s');
    $userid = isset($USER->id) ? $USER->id : 'unknown';
    $logMessage = "[{$timestamp}] [{$type}] User:{$userid} CID:{$contentsid} CT:{$contentstype} - {$message}\n";
    error_log($logMessage, 3, "/home/moodle/logs/pmemory_upload.log");
}
if(isset($_POST) and $_SERVER['REQUEST_METHOD'] == "POST")
{
    logUploadActivity("Upload request received");

    // POST 데이터 로깅
    logUploadActivity("POST data - contentsid: {$contentsid}, contentstype: {$contentstype}, section: " . ($section ?? 'NULL') . ", filekey: " . ($filekey ?? 'NULL'));

    $name = $_FILES['audio']['name'];
    $size = $_FILES['audio']['size'];

    logUploadActivity("File info - Name: {$name}, Size: {$size} bytes");

    if(strlen($name))
    {
        $name_parts = explode(".", $name);
        $ext = end($name_parts);

        if(in_array($ext,$valid_formats))
        {
            if($size < ( 1024*1024*100 )) // Audio size max 100 MB
            {
                if($contentsid==NULL)
                {
                    $actual_audio_name = $name;
                }
                else
                {
                    // JSON 절차기억 모드 (filekey가 있으면 해당 키를 파일명에 사용)
                    if($filekey !== null && $filekey !== '') {
                        $actual_audio_name = 'cid'.$contentsid.'ct'.$contentstype.'_'.$filekey.'.'.$ext;
                        logUploadActivity("JSON mode detected - filekey: {$filekey}, filename: {$actual_audio_name}");
                    }
                    // 구간 번호가 있으면 파일명에 포함 (듣기평가 모드)
                    elseif($section !== null && $section !== '') {
                        $actual_audio_name = 'cid'.$contentsid.'ct'.$contentstype.'_section'.$section.'.'.$ext;
                    }
                    // 기본 모드 (단일 파일)
                    else {
                        $actual_audio_name = 'cid'.$contentsid.'ct'.$contentstype.'_pmemory.'.$ext;
                    }
                }

                logUploadActivity("Attempting to save file as: {$actual_audio_name}");

                // 기존 파일이 존재하면 명시적으로 삭제
                $target_file = $path . $actual_audio_name;
                if(file_exists($target_file)) {
                    logUploadActivity("Existing file found: {$actual_audio_name}, attempting to delete");
                    if(unlink($target_file)) {
                        logUploadActivity("Successfully deleted existing file: {$actual_audio_name}", "success");
                    } else {
                        logUploadActivity("Failed to delete existing file: {$actual_audio_name}", "warning");
                        $data['warning'] = "기존 파일 삭제 실패, 덮어쓰기 시도";
                    }
                }

                $tmp = $_FILES['audio']['tmp_name'];
                if(move_uploaded_file($tmp, $path.$actual_audio_name))
                {
                    $data['success'] = true;
                    $data['url']  = $actual_audio_name;
                    logUploadActivity("File uploaded successfully: {$actual_audio_name}", "success");
                }
                else
                {
                    $data['success'] = false;
                    $data['error'] = "파일 업로드 실패: 서버 권한을 확인하세요";
                    $data['details'] = "Target: {$actual_audio_name}, Size: {$size} bytes";
                    logUploadActivity("File upload failed - move_uploaded_file error, target={$actual_audio_name}, size={$size}", "error");
                }
            }
            else
            {
                $data['error'] = "Audio file size max 100 MB";
                logUploadActivity("File too large: {$size} bytes", "error");
            }
        }
        else
        {
            $data['error'] = "Invalid file format: {$ext}";
            logUploadActivity("Invalid file format: {$ext}", "error");
        }
    }
    else
    {
        $data['error'] = "Please select an audio file.";
        logUploadActivity("No file selected", "error");
    }
}
// 데이터베이스 업데이트는 업로드가 성공한 경우에만 수행
if($data['success'] && !empty($data['url'])) {
    $audiourl = 'https://mathking.kr/Contents/audiofiles/pmemory/'.$data['url'];

    // Section 체크 로직 상세 로깅
    $sectionValue = $section === null ? 'NULL' : (empty($section) ? 'EMPTY' : $section);
    $willUpdateDB = ($section === null || $section === '') ? 'YES' : 'NO';
    logUploadActivity("Section check - value: {$sectionValue}, will update DB: {$willUpdateDB}");

    // JSON 절차기억 모드는 DB 업데이트 하지 않음 (check_status.php에서 별도 처리)
    // 구간별 파일은 DB 업데이트 하지 않음 (듣기평가 모드)
    // 마지막 전체 병합 파일만 DB 업데이트
    if(($section === null || $section === '') && ($filekey === null || $filekey === '')) {
        logUploadActivity("Entering DB update block - contentstype: {$contentstype}");
        try {
            if($contentstype == 2) {
                logUploadActivity("Executing UPDATE query - table: question, audiourl2: {$audiourl}, id: {$contentsid}");
                $DB->execute("UPDATE {question} SET audiourl2=? WHERE id=?", array($audiourl, $contentsid));
                logUploadActivity("Database updated - question table, audiourl2: {$audiourl}", "success");
            } else {
                logUploadActivity("Executing UPDATE query - table: icontent_pages, audiourl2: {$audiourl}, id: {$contentsid}");
                $DB->execute("UPDATE {icontent_pages} SET audiourl2=? WHERE id=?", array($audiourl, $contentsid));
                logUploadActivity("Database updated - icontent_pages table, audiourl2: {$audiourl}", "success");
            }
            $data['audiourl'] = $audiourl; // URL을 응답에 포함
            logUploadActivity("DB update completed successfully. Response audiourl set to: {$audiourl}", "success");
        } catch (Exception $e) {
            logUploadActivity("Database update EXCEPTION: " . $e->getMessage(), "error");
            logUploadActivity("Exception trace: " . $e->getTraceAsString(), "error");
            $data['warning'] = "File uploaded but database update failed";
            $data['db_error'] = $e->getMessage();
        }
    } else {
        // JSON 절차기억 또는 구간 파일은 URL만 반환
        $data['audiourl'] = $audiourl;

        if($filekey !== null && $filekey !== '') {
            logUploadActivity("JSON procedural file uploaded (no DB update): {$audiourl}, filekey: {$filekey}", "info");
        }

        // 섹션 1(첫번째 오디오) 업로드 시, audiourl2가 비어 있으면 첫번째 오디오 URL로 저장
        try {
            if ($section === '1' || $section === 1) {
                if ($contentstype != 2) { // icontent_pages에만 적용
                    $current = $DB->get_record_sql("SELECT audiourl2 FROM mdl_icontent_pages WHERE id=? ORDER BY id DESC LIMIT 1", array($contentsid));
                    $currentVal = $current ? $current->audiourl2 : null;
                    if (empty($currentVal)) {
                        logUploadActivity("First section detected; updating icontent_pages.audiourl2 with first section URL: {$audiourl}");
                        $DB->execute("UPDATE {icontent_pages} SET audiourl2=? WHERE id=?", array($audiourl, $contentsid));
                        logUploadActivity("Database updated with first section URL", "success");
                    } else {
                        logUploadActivity("audiourl2 already set; skipping first section DB update");
                    }
                }
            }
        } catch (Exception $e) {
            logUploadActivity("First section DB update EXCEPTION: " . $e->getMessage(), "error");
        }

        logUploadActivity("Section file uploaded (no DB update): {$audiourl}, section: {$section}", "info");
    }
}

// 최종 응답 데이터 로깅
logUploadActivity("Final response data: " . json_encode($data));

// 응답 헤더 설정
header('Content-Type: application/json');
die(json_encode($data));
?>
