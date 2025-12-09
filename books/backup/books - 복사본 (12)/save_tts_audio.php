<?php
/**
 * TTS 오디오 파일 업로드 및 DB 업데이트
 * File: save_tts_audio.php
 * Location: /books/save_tts_audio.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

try {
    // 입력 데이터 검증
    if (!isset($_POST['audioData']) || !isset($_POST['contentsid']) || !isset($_POST['contentstype'])) {
        throw new Exception('필수 파라미터가 누락되었습니다. [파일: save_tts_audio.php, 위치: 입력 검증]');
    }

    $audioData = $_POST['audioData'];
    $contentsid = $_POST['contentsid'];
    $contentstype = $_POST['contentstype'];
    $type = isset($_POST['type']) ? $_POST['type'] : 'conversation';

    // Base64 데이터에서 접두사 제거
    if (strpos($audioData, 'data:audio/wav;base64,') === 0) {
        $audioData = substr($audioData, strlen('data:audio/wav;base64,'));
    }

    // Base64 디코딩
    $decodedAudio = base64_decode($audioData);
    if ($decodedAudio === false) {
        throw new Exception('오디오 데이터 디코딩 실패 [파일: save_tts_audio.php, 위치: Base64 디코딩]');
    }

    // 파일 저장 경로 및 이름 설정
    $uploadDir = '/home/moodle/public_html/audiofiles/';
    $fileName = 'cid' . $contentsid . 'ct' . $contentstype . '_' . $type . '_tts.wav';
    $filePath = $uploadDir . $fileName;

    // 디렉토리 존재 확인 및 생성
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('업로드 디렉토리 생성 실패 [파일: save_tts_audio.php, 위치: 디렉토리 생성]');
        }
    }

    // 파일 저장
    if (file_put_contents($filePath, $decodedAudio) === false) {
        throw new Exception('파일 저장 실패 [파일: save_tts_audio.php, 위치: 파일 쓰기]');
    }

    // DB 업데이트를 위한 URL 생성
    $audioUrl = 'https://mathking.kr/audiofiles/' . $fileName;

    // contentstype에 따라 적절한 테이블 업데이트
    if ($contentstype == 2) {
        // question 테이블 업데이트
        $result = $DB->execute(
            "UPDATE {question} SET audiourl = ? WHERE id = ?",
            array($audioUrl, $contentsid)
        );

        if (!$result) {
            throw new Exception('question 테이블 업데이트 실패 [파일: save_tts_audio.php, 위치: question 업데이트]');
        }

        $message = 'question 테이블의 audiourl 필드가 업데이트되었습니다.';
    } else {
        // icontent_pages 테이블 업데이트
        $result = $DB->execute(
            "UPDATE {icontent_pages} SET audiourl = ? WHERE id = ?",
            array($audioUrl, $contentsid)
        );

        if (!$result) {
            throw new Exception('icontent_pages 테이블 업데이트 실패 [파일: save_tts_audio.php, 위치: icontent_pages 업데이트]');
        }

        $message = 'icontent_pages 테이블의 audiourl 필드가 업데이트되었습니다.';
    }

    // 성공 응답
    echo json_encode(array(
        'success' => true,
        'message' => $message,
        'audioUrl' => $audioUrl,
        'fileName' => $fileName
    ));

} catch (Exception $e) {
    // 오류 응답
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ));
}
?>
