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
    
    $narrationOk = false;
    $audioOk = false;
    
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
            $narrationOk = !empty(trim($narrationText));
            
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
                    $audioOk = true;
                    // audio_url에 JSON 배열로 저장
                    $audioUrlJson = json_encode($audioUrls, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET audio_url = ?, status = 'completed' WHERE id = ?",
                        [$audioUrlJson, $interactionId]);
                    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] TTS 생성 완료 - " . count($audioUrls) . "개 섹션");
                } else {
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET status = 'tts_failed' WHERE id = ?",
                        [$interactionId]);
                    error_log("[create_teaching_interaction.php:Line" . __LINE__ . "] TTS 생성 실패 - 모든 섹션 실패");
                }
            } else {
                // @ 구분자가 없으면 단일 TTS 생성
                $ttsResult = generateTtsSectionAudio($narrationText, $interactionId, 1, __DIR__ . '/../audio/');
                if ($ttsResult['success']) {
                    $audioOk = true;
                    $audioUrlJson = json_encode([$ttsResult['url']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $DB->execute("UPDATE {ktm_teaching_interactions} SET audio_url = ?, status = 'completed' WHERE id = ?",
                        [$audioUrlJson, $interactionId]);
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

    // =================================================================
    // 6단계: 응답 (실패/부분성공 케이스 정리)
    // =================================================================
    $response = [
        'success' => true,
        'interaction_id' => $interactionId,
        'is_new' => $isNew,
        'message' => 'TTS 생성 완료'
    ];
    
    // generate_audio=true인데 나레이션 생성이 실패/비어있으면 프론트에서 "완료"로 오인하지 않도록 실패 처리
    if ($generateAudio && !$narrationOk) {
        $response['success'] = false;
        $response['error'] = $narrationResult['error'] ?? '나레이션 생성에 실패했습니다.';
        $response['message'] = '나레이션 생성 실패';
    } else if ($generateAudio && $narrationOk && !$audioOk) {
        // 나레이션은 생성됐지만 오디오가 없을 수 있음(키/네트워크/쿼터 등)
        // 이 경우 프론트는 텍스트 기반(speechSynthesis)으로라도 단계별 설명을 제공할 수 있으므로 success는 유지하되 경고를 전달
        $response['message'] = '나레이션 생성 완료 (오디오 생성 실패/지연)';
        $response['warning'] = '오디오 파일 생성에 실패했거나 아직 준비되지 않았습니다. 텍스트 기반 안내로 진행합니다.';
    }
    
    ob_end_clean();
    echo json_encode($response);
    
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
 * Build absolute URL using Moodle $CFG->wwwroot when needed.
 *
 * @param string|null $url
 * @return string|null
 */
function ktm_normalize_to_absolute_url($url) {
    global $CFG;
    if (empty($url) || !is_string($url)) return null;
    $u = trim($url);
    // URL에 역슬래시가 섞여 들어오는 케이스가 있어 정규화
    $u = str_replace('\\', '/', $u);
    // 중복 슬래시 정리(프로토콜은 제외)
    $u = preg_replace('#(?<!:)//+#', '/', $u);
    if ($u === '') return null;
    if (strpos($u, '//') === 0) {
        // protocol-relative
        return 'https:' . $u;
    }
    if (preg_match('/^https?:\/\//i', $u)) {
        return $u;
    }
    if (strpos($u, '/') === 0) {
        // root-relative (e.g. /moodle/pluginfile.php/...)
        $base = isset($CFG->wwwroot) ? rtrim($CFG->wwwroot, '/') : '';
        return $base ? ($base . $u) : $u;
    }
    // relative path
    $base = isset($CFG->wwwroot) ? rtrim($CFG->wwwroot, '/') : '';
    return $base ? ($base . '/' . ltrim($u, '/')) : $u;
}

/**
 * Fetch an image URL (including Moodle-authenticated pluginfile) and return as data URL.
 * This avoids OpenAI failing to fetch private URLs (common cause of HTTP 400).
 *
 * @param string $url
 * @return array {success: bool, data_url?: string, mime?: string, error?: string}
 */
function ktm_fetch_image_as_data_url($url) {
    $absoluteUrl = ktm_normalize_to_absolute_url($url);
    if (!$absoluteUrl) {
        return ['success' => false, 'error' => '이미지 URL이 비어있습니다.'];
    }

    // Build cookie header from current request to access protected pluginfile resources.
    $cookiePairs = [];
    foreach ($_COOKIE as $k => $v) {
        if ($k === '' || $v === null) continue;
        $cookiePairs[] = $k . '=' . $v;
    }
    $cookieHeader = implode('; ', $cookiePairs);

    $ch = curl_init($absoluteUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);

    $headers = [
        'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
        'User-Agent: augmented_teacher/tts_image_fetch'
    ];
    if (!empty($cookieHeader)) {
        $headers[] = 'Cookie: ' . $cookieHeader;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $raw = curl_exec($ch);
    $curlErr = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($raw === false) {
        return ['success' => false, 'error' => '이미지 다운로드 실패(curl): ' . $curlErr];
    }
    if ($httpCode < 200 || $httpCode >= 300) {
        // Often 302/403 if cookie is missing, or 404 if url is bad.
        return ['success' => false, 'error' => '이미지 다운로드 실패(HTTP ' . $httpCode . '): ' . $absoluteUrl];
    }

    $body = substr($raw, (int)$headerSize);
    if (empty($body)) {
        return ['success' => false, 'error' => '이미지 데이터가 비어있습니다: ' . $absoluteUrl];
    }

    // Determine mime type
    $mime = null;
    if (is_string($contentType) && $contentType) {
        $mime = explode(';', $contentType)[0];
        $mime = trim($mime);
    }
    if (!$mime || stripos($mime, 'image/') !== 0) {
        // Fallback using finfo
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $detected = finfo_buffer($f, $body);
                finfo_close($f);
                if ($detected && stripos($detected, 'image/') === 0) {
                    $mime = $detected;
                }
            }
        }
    }
    if (!$mime || stripos($mime, 'image/') !== 0) {
        $mime = 'image/png';
    }

    $dataUrl = 'data:' . $mime . ';base64,' . base64_encode($body);
    return ['success' => true, 'data_url' => $dataUrl, 'mime' => $mime];
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
    
    // ✅ 요구사항: 문제/해설 이미지가 반드시 전달되어야 함
    if (empty($problemImage) || empty($solutionImage)) {
        $missing = [];
        if (empty($problemImage)) $missing[] = '문제 이미지(question_image)';
        if (empty($solutionImage)) $missing[] = '해설 이미지(solution_image)';
        return ['success' => false, 'error' => '필수 이미지 누락: ' . implode(', ', $missing)];
    }

    // ✅ OpenAI가 인증이 필요한 URL을 직접 못 읽는 경우가 많아서,
    // 서버에서 이미지를 가져와 data URL(base64)로 전송한다.
    $solutionFetch = ktm_fetch_image_as_data_url($solutionImage);
    if (!$solutionFetch['success']) {
        return ['success' => false, 'error' => '해설 이미지 처리 실패: ' . ($solutionFetch['error'] ?? 'Unknown')];
    }
    $problemFetch = ktm_fetch_image_as_data_url($problemImage);
    if (!$problemFetch['success']) {
        return ['success' => false, 'error' => '문제 이미지 처리 실패: ' . ($problemFetch['error'] ?? 'Unknown')];
    }

    $userContent = [
        ['type' => 'text', 'text' => $userPrompt],
        ['type' => 'image_url', 'image_url' => ['url' => $solutionFetch['data_url']]],
        ['type' => 'image_url', 'image_url' => ['url' => $problemFetch['data_url']]]
    ];
    $messages[] = ['role' => 'user', 'content' => $userContent];
    
    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    $payload = [
        'model' => 'gpt-4o',
        'messages' => $messages,
        'max_tokens' => 4000,
        'temperature' => 0.7
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . OPENAI_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        // 응답 바디까지 포함해서 원인 파악 가능하게 함(너무 길면 잘라서)
        $snippet = is_string($response) ? substr($response, 0, 800) : '';
        return [
            'success' => false,
            'error' => 'OpenAI API 호출 실패: HTTP ' . $httpCode . ($curlError ? (' - ' . $curlError) : '') . ($snippet ? (' / resp: ' . $snippet) : '')
        ];
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
