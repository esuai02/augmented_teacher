<?php
/**
 * ktm_teaching_interactions 데이터 조회 API
 * 
 * 파라미터:
 * - id 또는 interactionid: 기본 키(id)로 조회
 * - contentsid: contentsid 컬럼으로 조회
 * - contentstype: contentsid와 함께 사용 시 추가 조건
 * - format: 'default' 또는 'section' (StepPlayer용)
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: application/json');

// 파라미터 파싱
$id = $_GET['id'] ?? $_GET['interactionid'] ?? null;
$contentsid = $_GET['contentsid'] ?? null;
$contentstype = $_GET['contentstype'] ?? null;
$format = $_GET['format'] ?? 'default'; // 'default' 또는 'section'

// ID가 하나도 없으면 에러
if (!$id && !$contentsid) {
    echo json_encode(['success' => false, 'error' => 'ID 또는 contentsid가 필요합니다.']);
    exit;
}

try {
    $interaction = null;
    
    // 1. id 또는 interactionid로 조회 (기본 키)
    if ($id) {
        $interaction = $DB->get_record('ktm_teaching_interactions', ['id' => $id]);
        if ($interaction) {
            error_log("[get_interaction_data.php] id=$id 로 조회 성공");
        }
    }
    
    // 2. contentsid로 조회 (contentsid 컬럼)
    if (!$interaction && $contentsid) {
        // contentstype이 함께 제공된 경우
        if ($contentstype !== null && $contentstype !== '') {
            $interaction = $DB->get_record_sql(
                "SELECT * FROM {ktm_teaching_interactions} 
                 WHERE contentsid = ? AND contentstype = ? 
                 AND audio_url IS NOT NULL AND audio_url != '' 
                 ORDER BY id DESC LIMIT 1",
                [$contentsid, $contentstype]
            );
            if ($interaction) {
                error_log("[get_interaction_data.php] contentsid=$contentsid, contentstype=$contentstype 로 조회 성공 (id={$interaction->id})");
            }
        }
        
        // contentstype 없이 contentsid로만 조회
        if (!$interaction) {
            $interaction = $DB->get_record_sql(
                "SELECT * FROM {ktm_teaching_interactions} 
                 WHERE contentsid = ? 
                 AND audio_url IS NOT NULL AND audio_url != '' 
                 ORDER BY id DESC LIMIT 1",
                [$contentsid]
            );
            if ($interaction) {
                error_log("[get_interaction_data.php] contentsid=$contentsid 로만 조회 성공 (id={$interaction->id})");
            }
        }
    }
    
    // 3. 여전히 없으면 contentsid를 id로 시도 (하위 호환성)
    if (!$interaction && $contentsid && is_numeric($contentsid)) {
        $interaction = $DB->get_record('ktm_teaching_interactions', ['id' => $contentsid]);
        if ($interaction) {
            error_log("[get_interaction_data.php] contentsid=$contentsid 를 id로 조회 성공 (fallback)");
        }
    }
    
    if (!$interaction) {
        $searchInfo = $id ? "id=$id" : "contentsid=$contentsid";
        echo json_encode(['success' => false, 'error' => "상호작용 데이터를 찾을 수 없습니다. ($searchInfo)"]);
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
        
        // faqtext도 함께 반환 (있는 경우)
        $faqtext = $interaction->faqtext ?? null;
        
        // StepPlayer 형식으로 반환
        echo json_encode([
            'success' => true,
            'data' => [
                'sections' => $audioSections,
                'text_sections' => $textSections,
                'total_sections' => $totalSections,
                'faqtext' => $faqtext,
                'interaction_id' => $interaction->id  // 실제 id도 반환
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
        'faqtext' => $interaction->faqtext ?? null,
        'modificationPrompt' => $interaction->modification_prompt ?? '',
        'status' => $interaction->status,
        'score' => $interaction->score ?? null,
        'timecreated' => $interaction->timecreated,
        'timemodified' => $interaction->timemodified
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '데이터베이스 오류: ' . $e->getMessage() . ' [get_interaction_data.php:' . __LINE__ . ']'
    ]);
}
?>
