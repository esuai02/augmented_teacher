<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// 'id' 또는 'interactionid' 또는 'contentsid' 파라미터 모두 지원
$id = $_GET['id'] ?? $_GET['interactionid'] ?? $_GET['contentsid'] ?? 0;
$format = $_GET['format'] ?? 'default'; // 'default' 또는 'section'

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID가 필요합니다.']);
    exit;
}

try {
    // 상호작용 데이터 가져오기
    $interaction = $DB->get_record('ktm_teaching_interactions', array('id' => $id));
    
    if (!$interaction) {
        echo json_encode(['success' => false, 'error' => '상호작용 데이터를 찾을 수 없습니다.']);
        exit;
    }
    
    // StepPlayer 형식으로 반환하는 경우
    if ($format === 'section') {
        // narration_text를 @ 기호로 분할
        $narrationText = $interaction->narration_text ?? '';
        $textSections = [];
        if (!empty($narrationText)) {
            $textSections = array_filter(array_map('trim', explode('@', $narrationText)));
            $textSections = array_values($textSections);
        }
        
        // audio_url에서 섹션 URL 배열 파싱
        $audioSections = [];
        $audioUrl = $interaction->audio_url ?? '';
        if (!empty($audioUrl)) {
            try {
                $audioData = json_decode($audioUrl, true);
                if (is_array($audioData)) {
                    // 배열 형식인 경우
                    $audioSections = $audioData;
                } else if (is_string($audioUrl) && filter_var($audioUrl, FILTER_VALIDATE_URL)) {
                    // 단일 URL인 경우
                    $audioSections = [$audioUrl];
                }
            } catch (Exception $e) {
                // JSON 파싱 실패 시 빈 배열
                $audioSections = [];
            }
        }
        
        // 섹션 수 맞추기 (텍스트와 오디오 중 더 긴 것을 기준)
        $totalSections = max(count($textSections), count($audioSections));
        
        // 텍스트 섹션이 부족하면 빈 문자열로 채우기
        while (count($textSections) < $totalSections) {
            $textSections[] = '';
        }
        
        // 오디오 섹션이 부족하면 빈 문자열로 채우기
        while (count($audioSections) < $totalSections) {
            $audioSections[] = '';
        }
        
        // StepPlayer 형식으로 반환
        echo json_encode([
            'success' => true,
            'data' => [
                'sections' => $audioSections,
                'text_sections' => $textSections,
                'total_sections' => $totalSections
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // 기본 형식으로 반환
    // 문제 유형 텍스트 변환
    $problemTypeText = '';
    switch($interaction->problem_type) {
        case 'exam': $problemTypeText = '내신 기출'; break;
        case 'school': $problemTypeText = '학교 프린트'; break;
        case 'mathking': $problemTypeText = 'MathKing 문제'; break;
        case 'textbook': $problemTypeText = '시중교재'; break;
        default: $problemTypeText = $interaction->problem_type;
    }
    
    echo json_encode([
        'success' => true,
        'id' => $interaction->id,
        'userId' => $interaction->userid,
        'teacherId' => $interaction->teacherid,
        'problemType' => $problemTypeText,
        'problemImage' => $interaction->problem_image,
        'solutionImage' => $interaction->solution_image ?? '',
        'problemText' => $interaction->problem_text,
        'solutionText' => $interaction->solution_text,
        'narrationText' => $interaction->narration_text,
        'audioUrl' => $interaction->audio_url,
        'modificationPrompt' => $interaction->modification_prompt ?? '',
        'status' => $interaction->status,
        'score' => $interaction->score ?? null,
        'timecreated' => $interaction->timecreated,
        'timemodified' => $interaction->timemodified
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류: ' . $e->getMessage() . ' [get_interaction_data.php:' . __LINE__ . ']'
    ]);
}
?>