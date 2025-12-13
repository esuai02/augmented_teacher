<?php
/**
 * get_section_data.php - TTS 섹션 데이터 조회 API
 * 파일 위치: alt42/teachingsupport/api/get_section_data.php
 *
 * StepPlayer 모달에서 사용하는 섹션 데이터 반환
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

try {
    $contentsId = $_GET['contentsid'] ?? null;
    $interactionId = $_GET['interaction_id'] ?? $contentsId;
    
    if (!$interactionId) {
        throw new Exception('contentsid 또는 interaction_id가 필요합니다. (파일: ' . basename(__FILE__) . ', 라인: ' . __LINE__ . ')');
    }
    
    // 상호작용 데이터 조회
    $interaction = $DB->get_record('ktm_teaching_interactions', ['id' => $interactionId]);
    
    if (!$interaction) {
        throw new Exception('상호작용을 찾을 수 없습니다. (파일: ' . basename(__FILE__) . ', 라인: ' . __LINE__ . ')');
    }
    
    $sections = [];
    $textSections = [];
    
    // 나레이션 텍스트에서 섹션 분리
    $narration = $interaction->narration_text ?? $interaction->solution_text ?? '';
    
    if (!empty($narration)) {
        // @ 문자로 섹션 분리 (teachingagent.php와 동일)
        $paragraphs = explode('@', $narration);
        
        foreach ($paragraphs as $index => $paragraph) {
            $paragraph = trim($paragraph);
            if (empty($paragraph) || strlen($paragraph) < 10) continue;
            
            // 오디오 파일 확인
            $audioPath = __DIR__ . "/../audio/tts_{$interactionId}_{$index}_*.mp3";
            $audioFiles = glob($audioPath);
            $audioUrl = '';
            
            if (!empty($audioFiles)) {
                $audioFile = basename($audioFiles[0]);
                $audioUrl = '/moodle/local/augmented_teacher/alt42/teachingsupport/audio/' . $audioFile;
            }
            
            $sections[] = [
                'index' => count($sections),
                'audio_url' => $audioUrl,
                'duration' => 0 // 클라이언트에서 로드 시 계산
            ];
            
            $textSections[] = $paragraph;
        }
    }
    
    // 섹션이 없으면 전체를 하나의 섹션으로
    if (empty($sections)) {
        $fullText = $narration ?: '풀이 내용이 없습니다.';
        
        $sections[] = [
            'index' => 0,
            'audio_url' => $interaction->audio_url ?? '',
            'duration' => 0
        ];
        
        $textSections[] = $fullText;
    }
    
    echo json_encode([
        'success' => true,
        'sections' => $sections,
        'text_sections' => $textSections,
        'total_sections' => count($sections),
        'interaction_id' => $interactionId,
        'status' => $interaction->status ?? 'unknown'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'sections' => [],
        'text_sections' => []
    ]);
}

