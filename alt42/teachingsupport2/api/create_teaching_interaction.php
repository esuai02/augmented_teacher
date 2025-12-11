<?php
/**
 * create_teaching_interaction.php - TTS 생성 및 상호작용 저장 API
 * 파일 위치: alt42/teachingsupport/api/create_teaching_interaction.php
 *
 * asksolution.php처럼 먼저 mdl_ktm_teaching_interactions에 레코드 생성
 * teachingagent.php처럼 generate_dialog_narration.php를 통해 TTS 생성
 * 모든 응답은 JSON 형식으로 반환됩니다.
 */

// 출력 버퍼링 시작
ob_start();

// JSON 헤더 설정
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// OPTIONS 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_end_clean();
    exit(0);
}

// 에러 출력을 로그로만
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// 메모리 및 실행시간 제한 증가 (TTS 생성에 시간이 걸릴 수 있음)
ini_set('memory_limit', '512M');
set_time_limit(180);

try {
    include_once("/home/moodle/public_html/moodle/config.php");
    global $DB, $USER, $CFG;
    require_login();
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'Config 로드 실패: ' . $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
    exit;
}

// POST 데이터 받기
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => 'JSON 파싱 실패: ' . json_last_error_msg(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
    exit;
}

try {
    $studentId = $input['student_id'] ?? $USER->id;
    $contentId = $input['content_id'] ?? null;
    $analysisId = $input['analysis_id'] ?? null;
    $whiteboardId = $input['whiteboard_id'] ?? null;
    $questionImage = $input['question_image'] ?? null;
    $solutionImage = $input['solution_image'] ?? null;
    $generateAudio = $input['generate_audio'] ?? false;
    $forceRegenerate = $input['force_regenerate'] ?? false; // 강제 재생성 옵션
    
    $time = time();
    
    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] TTS 생성 요청 - studentId: {$studentId}, contentId: {$contentId}, whiteboardId: {$whiteboardId}, generateAudio: " . ($generateAudio ? 'true' : 'false') . ", forceRegenerate: " . ($forceRegenerate ? 'true' : 'false'));
    
    // =================================================================
    // 1단계: 테이블 및 필드 확인 (asksolution_save.php 방식)
    // =================================================================
    
    // 테이블 존재 확인
    $dbman = $DB->get_manager();
    if (!$dbman->table_exists('ktm_teaching_interactions')) {
        throw new Exception('ktm_teaching_interactions 테이블이 존재하지 않습니다. (파일: ' . basename(__FILE__) . ', 라인: ' . __LINE__ . ')');
    }
    
    // wboardid 필드 존재 확인 및 추가
    $wboardidExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'wboardid');
    if (!$wboardidExists) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] wboardid 필드 추가 중...");
        $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN wboardid VARCHAR(255) DEFAULT NULL";
        $DB->execute($sql);
        try {
            $indexSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD INDEX wboardid_idx (wboardid)";
            $DB->execute($indexSql);
        } catch (Exception $e) {
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] wboardid 인덱스 추가 실패 (무시): " . $e->getMessage());
        }
    }
    
    // type 필드 존재 확인 및 추가
    $typeExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'type');
    if (!$typeExists) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] type 필드 추가 중...");
        $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN type VARCHAR(50) DEFAULT NULL";
        $DB->execute($sql);
    }
    
    // solution_image 필드 존재 확인 및 추가
    $solutionImageExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'solution_image');
    if (!$solutionImageExists) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] solution_image 필드 추가 중...");
        $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN solution_image LONGTEXT DEFAULT NULL";
        $DB->execute($sql);
    }
    
    // contentsid 필드 존재 확인 및 추가
    $contentsidExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'contentsid');
    if (!$contentsidExists) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] contentsid 필드 추가 중...");
        $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN contentsid BIGINT(10) DEFAULT NULL";
        $DB->execute($sql);
    }
    
    // contentstype 필드 존재 확인 및 추가
    $contentstypeExists = $DB->get_manager()->field_exists('ktm_teaching_interactions', 'contentstype');
    if (!$contentstypeExists) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] contentstype 필드 추가 중...");
        $sql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN contentstype INT(10) DEFAULT NULL";
        $DB->execute($sql);
    }
    
    // =================================================================
    // 2단계: 기존 상호작용 확인
    // =================================================================
    
    $existing = null;
    if ($whiteboardId) {
        $existing = $DB->get_record_sql(
            "SELECT * FROM {ktm_teaching_interactions} WHERE userid = ? AND wboardid = ? ORDER BY id DESC LIMIT 1",
            [$studentId, $whiteboardId]
        );
    }
    
    $interactionId = null;
    $isNew = false;
    
    // 강제 재생성이면 기존 오디오 파일 삭제
    if ($forceRegenerate && $existing && !empty($existing->audio_url)) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 강제 재생성 요청 - 기존 TTS 삭제");
        
        // 기존 오디오 파일들 삭제
        $audioDir = __DIR__ . '/../audio/';
        $pattern = $audioDir . 'tts_' . $existing->id . '_*.mp3';
        $oldFiles = glob($pattern);
        foreach ($oldFiles as $oldFile) {
            @unlink($oldFile);
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 기존 오디오 삭제: " . basename($oldFile));
        }
        
        // 기존 레코드 audio_url 초기화
        $updateReset = new stdClass();
        $updateReset->id = $existing->id;
        $updateReset->audio_url = null;
        $updateReset->narration_text = null;
        $updateReset->status = 'pending';
        $DB->update_record('ktm_teaching_interactions', $updateReset);
        
        // 기존 객체도 갱신하여 아래에서 새로 생성하도록 함
        $existing->audio_url = null;
        $existing->narration_text = null;
    }
    
    // =================================================================
    // 3단계: 레코드 생성 또는 업데이트 (asksolution_save.php 방식)
    // =================================================================
    
    if ($existing && !empty($existing->audio_url) && !$forceRegenerate) {
        // 이미 TTS가 생성된 상호작용이 있고, 강제 재생성이 아닌 경우
        $interactionId = $existing->id;
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 기존 TTS 사용 - interactionId: {$interactionId}");
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'interaction_id' => $interactionId,
            'is_new' => false,
            'message' => '기존 TTS 사용'
        ]);
        exit;
    } elseif ($existing) {
        // 기존 레코드가 있지만 TTS가 없는 경우 - 업데이트
        $interactionId = $existing->id;
        
        $updateData = new stdClass();
        $updateData->id = $interactionId;
        $updateData->problem_image = $questionImage ?: $existing->problem_image;
        $updateData->status = 'processing';
        $updateData->timemodified = $time;
        
        // solution_image 필드가 있으면 업데이트
        if ($solutionImageExists) {
            $updateData->solution_image = $solutionImage ?: $existing->solution_image;
        }
        
        $DB->update_record('ktm_teaching_interactions', $updateData);
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 기존 레코드 업데이트 - interactionId: {$interactionId}");
    } else {
        // 새 상호작용 생성 (asksolution_save.php 방식과 동일)
        $isNew = true;
        
        $newInteraction = new stdClass();
        $newInteraction->userid = $studentId;
        $newInteraction->teacherid = $USER->id;
        $newInteraction->wboardid = $whiteboardId;
        $newInteraction->type = 'tts_generation';
        $newInteraction->problem_type = 'learning_interface_tts';
        $newInteraction->problem_image = $questionImage;
        $newInteraction->problem_text = '';
        $newInteraction->solution_text = '';
        $newInteraction->narration_text = '';
        $newInteraction->audio_url = '';
        $newInteraction->status = 'pending';
        $newInteraction->timecreated = $time;
        $newInteraction->timemodified = $time;
        
        // solution_image 필드가 있으면 추가
        if ($solutionImageExists) {
            $newInteraction->solution_image = $solutionImage;
        }
        
        // contentsid 필드가 있으면 추가
        if ($contentsidExists && $contentId) {
            $newInteraction->contentsid = $contentId;
        }
        
        $interactionId = $DB->insert_record('ktm_teaching_interactions', $newInteraction);
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 새 레코드 생성 - interactionId: {$interactionId}");
    }
    
    if (!$interactionId) {
        throw new Exception('상호작용 레코드 생성/조회 실패 (파일: ' . basename(__FILE__) . ', 라인: ' . __LINE__ . ')');
    }
    
    // =================================================================
    // 4단계: OpenAI Vision으로 풀이 생성 (solution_text가 없는 경우)
    // =================================================================
    
    // 기존 레코드의 solution_text 확인
    $currentRecord = $DB->get_record('ktm_teaching_interactions', ['id' => $interactionId]);
    
    if (empty($currentRecord->solution_text) && $questionImage) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 풀이 생성 시작 - OpenAI Vision API");
        
        $solutionResult = generateSolutionFromImage($questionImage, $solutionImage);
        if ($solutionResult['success']) {
            $updateSolution = new stdClass();
            $updateSolution->id = $interactionId;
            $updateSolution->solution_text = $solutionResult['solution'];
            $updateSolution->timemodified = time();
            $DB->update_record('ktm_teaching_interactions', $updateSolution);
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 풀이 저장 완료");
        } else {
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 풀이 생성 실패: " . ($solutionResult['error'] ?? 'Unknown error'));
        }
    }
    
    // =================================================================
    // 5단계: TTS 생성 (teachingagent.php 방식 - 직접 구현)
    // =================================================================
    
    if ($generateAudio) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] TTS 생성 시작 (직접 구현 방식)");
        
        // 최신 레코드 다시 조회
        $latestRecord = $DB->get_record('ktm_teaching_interactions', ['id' => $interactionId]);
        $solutionText = $latestRecord->solution_text ?? '';
        $problemImage = $latestRecord->problem_image ?? $questionImage;
        $solutionImg = $solutionImageExists ? ($latestRecord->solution_image ?? $solutionImage) : $solutionImage;
        
        // 나레이션 텍스트 생성 (OpenAI GPT-4o)
        $narrationResult = generateNarrationText($solutionText, $problemImage, $solutionImg);
        
        if ($narrationResult['success']) {
            $narrationText = $narrationResult['narration'];
            
            // 나레이션 텍스트 저장
            $DB->execute("UPDATE {ktm_teaching_interactions} SET narration_text = ? WHERE id = ?",
                [$narrationText, $interactionId]);
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 나레이션 텍스트 저장 완료");
            
            // @ 구분자로 섹션 분리 후 TTS 생성
            if (strpos($narrationText, '@') !== false) {
                $sections = array_values(array_filter(array_map('trim', explode('@', $narrationText))));
                $sectionCount = count($sections);
                error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 섹션 수: {$sectionCount}");
                
                $audioUrls = [];
                $audioDir = __DIR__ . '/../audio/';
                if (!file_exists($audioDir)) {
                    mkdir($audioDir, 0755, true);
                }
                
                foreach ($sections as $idx => $sectionText) {
                    if (strlen($sectionText) < 10) continue;
                    
                    $ttsResult = generateTtsSectionAudio($sectionText, $interactionId, $idx + 1, $audioDir);
                    if ($ttsResult['success']) {
                        $audioUrls[] = $ttsResult['url'];
                        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 섹션 " . ($idx + 1) . " TTS 생성 성공");
                    } else {
                        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 섹션 " . ($idx + 1) . " TTS 실패: " . $ttsResult['error']);
                    }
                }
                
                if (!empty($audioUrls)) {
                    // audio_url에 JSON 배열로 저장
                    $audioUrlJson = json_encode($audioUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET audio_url = ?, status = 'completed' WHERE id = ?",
                        [$audioUrlJson, $interactionId]);
                    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] TTS 생성 완료 - " . count($audioUrls) . "개 섹션");
                    
                    // faqtext 자동 생성 (TTS 생성 완료 후)
                    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] faqtext 자동 생성 시작");
                    $faqtextResult = generateFaqtextForSections($sections, $interactionId);
                    if ($faqtextResult['success']) {
                        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] faqtext 생성 완료 - " . $faqtextResult['sections_count'] . "개 단계");
                    } else {
                        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] faqtext 생성 실패: " . ($faqtextResult['error'] ?? 'Unknown'));
                    }
                } else {
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET status = 'tts_failed' WHERE id = ?",
                        [$interactionId]);
                    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] TTS 생성 실패 - 모든 섹션 실패");
                }
            } else {
                // @ 구분자가 없으면 단일 TTS 생성
                $ttsResult = generateTtsSectionAudio($narrationText, $interactionId, 1, __DIR__ . '/../audio/');
                if ($ttsResult['success']) {
                    $audioUrlJson = json_encode([$ttsResult['url']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET audio_url = ?, status = 'completed' WHERE id = ?",
                        [$audioUrlJson, $interactionId]);
                    
                    // 단일 섹션에도 faqtext 생성
                    $faqtextResult = generateFaqtextForSections([$narrationText], $interactionId);
                } else {
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET status = 'tts_failed' WHERE id = ?",
                        [$interactionId]);
                }
            }
        } else {
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 나레이션 생성 실패: " . ($narrationResult['error'] ?? 'Unknown error'));
            $DB->execute("UPDATE {ktm_teaching_interactions} SET status = 'tts_failed' WHERE id = ?",
                [$interactionId]);
        }
    } else {
        // TTS 없이 완료 처리
        $updateStatus = new stdClass();
        $updateStatus->id = $interactionId;
        $updateStatus->status = 'completed';
        $updateStatus->timemodified = time();
        $DB->update_record('ktm_teaching_interactions', $updateStatus);
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'interaction_id' => $interactionId,
        'is_new' => $isNew,
        'message' => 'TTS 생성 완료'
    ]);
    
} catch (Exception $e) {
    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] Exception: " . $e->getMessage());
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => basename(__FILE__),
        'line' => __LINE__
    ]);
}

/**
 * OpenAI GPT-4o로 나레이션 텍스트 생성 (generate_dialog_narration.php와 동일한 방식)
 */
function generateNarrationText($solutionText, $problemImage = null, $solutionImage = null) {
    require_once(__DIR__ . '/../config.php');
    
    if (!defined('OPENAI_API_KEY')) {
        return ['success' => false, 'error' => 'OPENAI_API_KEY 미설정'];
    }
    
    // generate_dialog_narration.php와 동일한 시스템 프롬프트
    $systemPrompt = "당신은 수학 튜터입니다. 학생이 수학 문제 풀이를 장기기억에 저장할 수 있도록 절차기억 방식의 대본을 생성해주세요.

# 응답 형식
- 각 단계를 @ 문자로 구분해주세요
- TTS로 읽을 수 있도록 자연스러운 구어체로 작성
- 숫자와 수식은 읽기 쉽게 변환 (예: x+y → 엑스 플러스 와이)
- 분수는 그대로 읽음 (예: x분의 일)
- 괄호는 발음하지 않음

# 단계 구성
1. 문제 파악 단계 (@ 구분)
2. 풀이 전략 단계 (@ 구분)
3. 풀이 과정 단계들 (@ 구분)
4. 정답 확인 단계 (@ 구분)";

    $userPrompt = "다음 수학 문제 풀이를 절차기억 형성 방식의 대본으로 변환해주세요:\n\n" . $solutionText;
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];
    
    // 이미지가 있으면 vision 형식 사용
    if ($solutionImage || $problemImage) {
        $userContent = [['type' => 'text', 'text' => $userPrompt]];
        
        if ($solutionImage) {
            $userContent[] = ['type' => 'image_url', 'image_url' => ['url' => $solutionImage]];
        }
        if ($problemImage) {
            $userContent[] = ['type' => 'image_url', 'image_url' => ['url' => $problemImage]];
        }
        
        $messages[] = ['role' => 'user', 'content' => $userContent];
    } else {
        $messages[] = ['role' => 'user', 'content' => $userPrompt];
    }
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o',
        'messages' => $messages,
        'max_tokens' => 4000,
        'temperature' => 0.7
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'OpenAI API 호출 실패: HTTP ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    
    if (empty($content)) {
        return ['success' => false, 'error' => '나레이션 생성 결과 없음'];
    }
    
    return ['success' => true, 'narration' => $content];
}

/**
 * OpenAI TTS API로 섹션별 오디오 생성
 */
function generateTtsSectionAudio($text, $interactionId, $sectionNum, $audioDir) {
    require_once(__DIR__ . '/../config.php');
    
    if (!defined('OPENAI_API_KEY')) {
        return ['success' => false, 'error' => 'OPENAI_API_KEY 미설정'];
    }
    
    if (strlen($text) < 10) {
        return ['success' => false, 'error' => '텍스트가 너무 짧음'];
    }
    
    if (strlen($text) > 4000) {
        $text = substr($text, 0, 4000);
    }
    
    $ch = curl_init('https://api.openai.com/v1/audio/speech');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'tts-1',
        'input' => $text,
        'voice' => 'alloy',
        'response_format' => 'mp3',
        'speed' => 1.0
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $audioData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'error' => 'TTS API 실패: HTTP ' . $httpCode . ' - ' . $curlError];
    }
    
    if (empty($audioData)) {
        return ['success' => false, 'error' => '빈 오디오 데이터'];
    }
    
    // 파일 저장
    if (!file_exists($audioDir)) {
        mkdir($audioDir, 0755, true);
    }
    
    $filename = "tts_{$interactionId}_{$sectionNum}_" . time() . '.mp3';
    $filepath = $audioDir . $filename;
    
    $bytesWritten = file_put_contents($filepath, $audioData);
    if ($bytesWritten === false) {
        return ['success' => false, 'error' => '파일 저장 실패'];
    }
    
    $publicUrl = '/moodle/local/augmented_teacher/alt42/teachingsupport/audio/' . $filename;
    
    return ['success' => true, 'url' => $publicUrl];
}

/**
 * OpenAI Vision API를 사용하여 문제 이미지에서 풀이 생성
 */
function generateSolutionFromImage($questionImage, $solutionImage = null) {
    global $CFG;

    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] generateSolutionFromImage 호출됨");

    // config.php에서 API 키 가져오기
    require_once(__DIR__ . '/../config.php');

    if (!defined('OPENAI_API_KEY')) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] OPENAI_API_KEY 미설정");
        return ['success' => false, 'error' => 'OPENAI_API_KEY 미설정'];
    }

    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] OpenAI API 키 확인됨");

    // JSON 파일에서 해설지 생성 프롬프트 로드
    $prompt = null;
    $promptsFile = __DIR__ . '/../prompts/hint_prompts.json';
    if (file_exists($promptsFile)) {
        $promptsData = json_decode(file_get_contents($promptsFile), true);
        if ($promptsData && isset($promptsData['solutionGenerationPrompt']['systemPrompt'])) {
            $prompt = $promptsData['solutionGenerationPrompt']['systemPrompt'];
            error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] JSON에서 해설지 생성 프롬프트 로드됨");
        }
    }

    // JSON 프롬프트가 없으면 기본 프롬프트 사용 (폴백)
    if (empty($prompt)) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] 기본 해설지 생성 프롬프트 사용 (폴백)");
        $prompt = "이 수학 문제를 단계별로 풀어주세요. 학생에게 직접 말하듯이 친근하게 설명해주세요.

응답 형식:
- 각 풀이 단계를 명확하게 설명
- 학생이 따라할 수 있도록 구체적인 지시 포함
- 수식은 한글로 읽을 수 있게 작성 (예: x+y는 '엑스 플러스 와이')

내용:
1. 문제 분석: 문제의 핵심 내용 파악
2. 풀이 전략: 사용할 개념과 공식 설명
3. 풀이 과정: 단계별 상세 풀이
4. 정답 및 검산: 최종 답과 확인";
    }
    
    $messages = [
        [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => $prompt]
            ]
        ]
    ];
    
    // 문제 이미지 추가
    if ($questionImage) {
        $messages[0]['content'][] = [
            'type' => 'image_url',
            'image_url' => ['url' => $questionImage]
        ];
    }
    
    // 해설 이미지 추가 (있으면)
    if ($solutionImage) {
        $messages[0]['content'][] = [
            'type' => 'image_url',
            'image_url' => ['url' => $solutionImage]
        ];
    }
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o',
        'messages' => $messages,
        'max_tokens' => 2000
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] OpenAI Vision API 응답 HTTP: {$httpCode}");
    
    if ($httpCode !== 200) {
        error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] OpenAI Vision API 실패 - HTTP: {$httpCode}, Error: {$curlError}, Response: " . substr($response, 0, 500));
        return ['success' => false, 'error' => 'OpenAI API 호출 실패: HTTP ' . $httpCode . ' - ' . $curlError];
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    
    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] OpenAI Vision API 응답 길이: " . strlen($content));
    
    return [
        'success' => true,
        'solution' => $content
    ];
}

/**
 * 각 단계별로 6가지 점층적 faqtext 생성
 * @param array $sections - @로 구분된 텍스트 배열
 * @param int $interactionId - 상호작용 ID
 * @return array - 생성 결과
 */
function generateFaqtextForSections($sections, $interactionId) {
    global $DB, $CFG;
    
    if (!defined('OPENAI_API_KEY')) {
        return ['success' => false, 'error' => 'OPENAI_API_KEY 미설정'];
    }
    
    $faqtextData = [];
    $totalSteps = count($sections);
    
    // 단계 라벨 정의
    $defaultLabels = [
        1 => '문제 파악',
        2 => '풀이 전략',
        3 => '풀이 과정',
        4 => '정답 확인',
        5 => '장기기억화'
    ];
    
    foreach ($sections as $idx => $sectionText) {
        $stepNum = $idx + 1;
        
        // 단계 라벨 결정
        if ($totalSteps <= 5 && isset($defaultLabels[$stepNum])) {
            $stepLabel = $defaultLabels[$stepNum];
        } else {
            if ($stepNum === 1) $stepLabel = '문제 파악';
            elseif ($stepNum === 2) $stepLabel = '풀이 전략';
            elseif ($stepNum === $totalSteps) $stepLabel = '정답 확인';
            elseif ($stepNum === $totalSteps - 1) $stepLabel = '검산';
            else $stepLabel = '풀이 과정 ' . ($stepNum - 2);
        }
        
        // 텍스트가 너무 짧으면 기본값 사용
        if (strlen(trim($sectionText)) < 10) {
            $faqtextData[] = [
                'step_index' => $stepNum,
                'step_label' => $stepLabel,
                'original' => $sectionText,
                'faqtext' => generateDefaultFaqArray($sectionText)
            ];
            continue;
        }
        
        // OpenAI API로 6가지 점층적 표현 생성
        $faqResult = callOpenAIForFaqtext($sectionText, $stepNum, $stepLabel);
        
        if ($faqResult['success']) {
            $faqtextData[] = [
                'step_index' => $stepNum,
                'step_label' => $stepLabel,
                'original' => $sectionText,
                'faqtext' => $faqResult['faqtext']
            ];
        } else {
            // 실패 시 기본값 사용
            $faqtextData[] = [
                'step_index' => $stepNum,
                'step_label' => $stepLabel,
                'original' => $sectionText,
                'faqtext' => generateDefaultFaqArray($sectionText)
            ];
        }
    }
    
    // faqtext를 JSON으로 변환하여 DB에 저장
    $faqtextJson = json_encode($faqtextData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    try {
        // faqtext 필드 존재 확인 및 추가
        try {
            $fieldCheckSql = "SHOW COLUMNS FROM {$CFG->prefix}ktm_teaching_interactions LIKE 'faqtext'";
            $fieldExists = $DB->get_record_sql($fieldCheckSql);
            
            if (!$fieldExists) {
                $alterSql = "ALTER TABLE {$CFG->prefix}ktm_teaching_interactions ADD COLUMN faqtext LONGTEXT DEFAULT NULL";
                $DB->execute($alterSql);
                error_log("[create_teaching_interaction.php] faqtext 필드 추가 완료");
            }
        } catch (Exception $e) {
            // 무시 - 이미 존재할 수 있음
        }
        
        // SQL 직접 업데이트
        $sql = "UPDATE {$CFG->prefix}ktm_teaching_interactions 
                SET faqtext = :faqtext, timemodified = :timemodified 
                WHERE id = :id";
        
        $params = [
            'faqtext' => $faqtextJson,
            'timemodified' => time(),
            'id' => $interactionId
        ];
        
        $DB->execute($sql, $params);
        
        return [
            'success' => true,
            'sections_count' => count($faqtextData)
        ];
        
    } catch (Exception $e) {
        error_log("[create_teaching_interaction.php] faqtext DB 저장 오류: " . $e->getMessage());
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * OpenAI API 호출하여 6가지 점층적 표현 생성
 */
function callOpenAIForFaqtext($sectionText, $stepNum, $stepLabel) {
    // 고정된 프롬프트 템플릿 - 일관된 형태로 생성
    $systemPrompt = "당신은 수학 학습 콘텐츠 전문가입니다. 주어진 수학 풀이 단계의 핵심 내용을 학생이 장기기억에 저장할 수 있도록 점층적으로 강조하는 6가지 버전을 만들어주세요.

# 6가지 점층적 표현 규칙 (반드시 이 순서와 형식을 지켜주세요)

1. **단축형**: 10자 내외로 핵심만 짧게 요약 (예: \"분모 통일이 핵심!\")
2. **함축형**: 20자 내외로 의미를 압축 (예: \"분모를 같게 만들면 계산 가능\")
3. **변형A**: 다른 단어/표현으로 재표현 (예: \"분모 맞추기 = 통분하기\")
4. **변형B**: 비유나 예시 활용 (예: \"케이크 조각 수 맞추듯 분모도 맞춰요\")
5. **강조형**: 핵심 키워드를 반복 강조 (예: \"통분! 통분이 먼저! 분모를 통일하세요\")
6. **확정형**: 확신 있는 문장으로 마무리 (예: \"분모를 같게 하면 분수 덧셈은 끝난 거야\")

# 응답 형식
반드시 아래 JSON 형식으로만 응답하세요. 다른 텍스트 없이 JSON만 출력:
{
  \"faqtext\": [
    \"단축형 내용\",
    \"함축형 내용\",
    \"변형A 내용\",
    \"변형B 내용\",
    \"강조형 내용\",
    \"확정형 내용\"
  ]
}";

    $userPrompt = "다음은 수학 문제 풀이의 [{$stepLabel}] 단계입니다. 이 내용을 6가지 점층적 표현으로 변환해주세요:\n\n" . $sectionText;
    
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt],
        ['role' => 'user', 'content' => $userPrompt]
    ];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'model' => 'gpt-4o-mini',  // 비용 효율적인 모델
        'messages' => $messages,
        'max_tokens' => 500,
        'temperature' => 0.3,  // 일관성을 위해 낮은 temperature
        'response_format' => ['type' => 'json_object']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("[create_teaching_interaction.php] faqtext OpenAI 오류: HTTP " . $httpCode);
        return ['success' => false, 'error' => 'HTTP ' . $httpCode];
    }
    
    $result = json_decode($response, true);
    $content = $result['choices'][0]['message']['content'] ?? '';
    
    if (empty($content)) {
        return ['success' => false, 'error' => '응답 없음'];
    }
    
    // JSON 파싱
    $faqData = json_decode($content, true);
    if (json_last_error() !== JSON_ERROR_NONE || !isset($faqData['faqtext'])) {
        error_log("[create_teaching_interaction.php] faqtext JSON 파싱 실패: " . $content);
        return ['success' => false, 'error' => 'JSON 파싱 실패'];
    }
    
    // 6개가 아닌 경우 기본값으로 채우기
    $faqtext = $faqData['faqtext'];
    while (count($faqtext) < 6) {
        $faqtext[] = "[추가] " . mb_substr($sectionText, 0, 20 + count($faqtext) * 5);
    }
    
    return ['success' => true, 'faqtext' => array_slice($faqtext, 0, 6)];
}

/**
 * 기본 faqtext 배열 생성 (API 실패 시 폴백)
 */
function generateDefaultFaqArray($sectionText) {
    $short = mb_substr($sectionText, 0, 15) . '...';
    return [
        "핵심: " . $short,
        "요약: " . mb_substr($sectionText, 0, 25),
        "다시 말하면: " . mb_substr($sectionText, 0, 30),
        "쉽게 말해서: " . mb_substr($sectionText, 0, 30),
        "중요! " . $short . " 기억하세요!",
        "결론: " . mb_substr($sectionText, 0, 20) . " - 확실히 기억!"
    ];
}
