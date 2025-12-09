<?php
/////////////////////////////// 대화형 나레이션 자동 생성 및 TTS 생성 (ktm_teaching_interactions용) ///////////////////////////////

// 출력 버퍼링 시작 (에러가 JSON 응답을 방해하지 않도록)
ob_start();

// 에러 핸들링 설정 - 모든 에러 출력 차단
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/dialog_narration_error.log');

// 메모리 및 실행시간 제한 증가
ini_set('memory_limit', '512M');
set_time_limit(180);

// JSON 헤더 즉시 설정
header('Content-Type: application/json; charset=utf-8');

// 응답 초기화
$response = ['success' => false, 'message' => '', 'debug' => []];

try {
    // 디버그 로깅 함수
    function debug_log($message) {
        global $response;
        $logMessage = "[DIALOG_NARRATION] " . date('Y-m-d H:i:s') . " - " . $message;
        error_log($logMessage);
        $response['debug'][] = $message; // 디버그 메시지를 응답에 포함
    }
    
    /**
     * JSON 파일에서 프롬프트 로드
     * @return array 프롬프트 데이터
     */
    function loadPromptConfig() {
        $promptsFile = __DIR__ . '/prompts/hint_prompts.json';
        
        if (!file_exists($promptsFile)) {
            debug_log("프롬프트 파일 없음, 기본값 사용: $promptsFile");
            return null;
        }
        
        $content = file_get_contents($promptsFile);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            debug_log("프롬프트 JSON 파싱 오류: " . json_last_error_msg());
            return null;
        }
        
        debug_log("프롬프트 파일 로드 성공 (버전: " . ($data['version'] ?? 'unknown') . ")");
        return $data;
    }
    
    /**
     * 힌트 레벨별 프롬프트 가져오기
     * @param string $hintLevel 힌트 레벨 (explain, early, middle, full, custom)
     * @param array|null $promptConfig 프롬프트 설정 데이터
     * @return array [systemPrompt, example, ttsGuidelines]
     */
    function getHintPrompt($hintLevel, $promptConfig = null) {
        // 프롬프트 설정이 있으면 JSON에서 가져오기
        if ($promptConfig && isset($promptConfig['hintLevels'][$hintLevel])) {
            $hint = $promptConfig['hintLevels'][$hintLevel];
            $ttsGuidelines = $promptConfig['ttsGuidelines'] ?? '';
            
            return [
                'systemPrompt' => $hint['systemPrompt'] ?? '',
                'example' => $hint['example'] ?? '',
                'ttsGuidelines' => $ttsGuidelines,
                'name' => $hint['name'] ?? $hintLevel,
                'description' => $hint['description'] ?? ''
            ];
        }
        
        // 기본값 반환 (JSON 파일이 없거나 해당 힌트 레벨이 없는 경우)
        return null;
    }
    
    /**
     * 이미지 지침 가져오기
     * @param string $mode 모드 (askhint, normal)
     * @param array|null $promptConfig 프롬프트 설정 데이터
     * @return string 이미지 지침
     */
    function getImageGuidelines($mode, $promptConfig = null) {
        if ($promptConfig && isset($promptConfig['imageGuidelines'][$mode])) {
            return $promptConfig['imageGuidelines'][$mode];
        }
        return '';
    }

    /**
     * 해설지 생성 기본 프롬프트 가져오기 (일반 모드용)
     * @param array|null $promptConfig 프롬프트 설정 데이터
     * @return array|null [systemPrompt, example] 또는 null
     */
    function getSolutionBasePrompt($promptConfig = null) {
        // 프롬프트 설정이 있으면 JSON에서 가져오기
        if ($promptConfig && isset($promptConfig['solutionBasePrompt'])) {
            $solution = $promptConfig['solutionBasePrompt'];

            return [
                'systemPrompt' => $solution['systemPrompt'] ?? '',
                'example' => $solution['example'] ?? '',
                'name' => $solution['name'] ?? '해설지 생성 기본 프롬프트',
                'description' => $solution['description'] ?? ''
            ];
        }

        // 기본값 반환 (JSON 파일이 없거나 해당 섹션이 없는 경우)
        return null;
    }

    // PCM 데이터를 WAV 파일로 변환하는 함수
    // OpenAI TTS API의 PCM 형식: 16-bit PCM, 24kHz, mono
    function pcmToWav($pcmData, $sampleRate = 24000, $channels = 1, $bitsPerSample = 16) {
        $dataSize = strlen($pcmData);
        $fileSize = 36 + $dataSize; // RIFF chunk size (전체 파일 크기 - 8 bytes)
        
        // WAV 헤더 생성 (리틀 엔디안)
        $header = '';
        $header .= 'RIFF';                                    // ChunkID (4 bytes)
        $header .= pack('V', $fileSize);                     // ChunkSize (전체 파일 크기 - 8) (4 bytes, 리틀 엔디안)
        $header .= 'WAVE';                                    // Format (4 bytes)
        $header .= 'fmt ';                                   // Subchunk1ID (4 bytes)
        $header .= pack('V', 16);                            // Subchunk1Size (16 for PCM) (4 bytes, 리틀 엔디안)
        $header .= pack('v', 1);                             // AudioFormat (1 = PCM) (2 bytes, 리틀 엔디안)
        $header .= pack('v', $channels);                     // NumChannels (2 bytes, 리틀 엔디안)
        $header .= pack('V', $sampleRate);                   // SampleRate (4 bytes, 리틀 엔디안)
        $header .= pack('V', $sampleRate * $channels * $bitsPerSample / 8); // ByteRate (4 bytes, 리틀 엔디안)
        $header .= pack('v', $channels * $bitsPerSample / 8); // BlockAlign (2 bytes, 리틀 엔디안)
        $header .= pack('v', $bitsPerSample);                // BitsPerSample (2 bytes, 리틀 엔디안)
        $header .= 'data';                                   // Subchunk2ID (4 bytes)
        $header .= pack('V', $dataSize);                     // Subchunk2Size (4 bytes, 리틀 엔디안)
        
        return $header . $pcmData;
    }

    // 이미지 URL을 base64로 변환하는 함수 (cURL 사용)
    function imageUrlToBase64($imageUrl) {
        error_log("[imageUrlToBase64] 시작 - URL: " . substr($imageUrl ?? '', 0, 200));
        
        if (empty($imageUrl)) {
            error_log("[imageUrlToBase64] URL이 비어있음");
            return null;
        }

        // 이미 base64 데이터인 경우
        if (strpos($imageUrl, 'data:') === 0) {
            error_log("[imageUrlToBase64] 이미 base64 형식");
            return $imageUrl;
        }

        // cURL로 이미지 가져오는 헬퍼 함수
        $fetchImageWithCurl = function($url) {
            error_log("[imageUrlToBase64] cURL fetch 시도: " . substr($url, 0, 150));
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: ko-KR,ko;q=0.9,en-US;q=0.8,en;q=0.7'
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                error_log("[imageUrlToBase64] cURL 오류: $curlError");
                return false;
            }
            
            if ($httpCode !== 200) {
                error_log("[imageUrlToBase64] HTTP 응답 코드: $httpCode");
                return false;
            }
            
            if (empty($imageData)) {
                error_log("[imageUrlToBase64] 빈 응답");
                return false;
            }
            
            error_log("[imageUrlToBase64] cURL 성공, 크기: " . strlen($imageData));
            return $imageData;
        };

        // /moodle/ 또는 /pluginfile.php로 시작하는 상대 경로인 경우
        if (strpos($imageUrl, '/moodle/') === 0 || strpos($imageUrl, '/pluginfile.php') === 0) {
            $fullUrl = 'https://mathking.kr' . $imageUrl;
            error_log("[imageUrlToBase64] 상대 경로 감지, 절대 URL로 변환: $fullUrl");
            $imageData = $fetchImageWithCurl($fullUrl);
            if ($imageData !== false) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedMime = finfo_buffer($finfo, $imageData);
                finfo_close($finfo);
                $mimeType = $detectedMime ?: 'image/jpeg';
                $base64 = base64_encode($imageData);
                error_log("[imageUrlToBase64] base64 변환 성공, mime: $mimeType");
                return "data:{$mimeType};base64,{$base64}";
            }
        }
        
        // 절대 URL인 경우
        if (strpos($imageUrl, 'http://') === 0 || strpos($imageUrl, 'https://') === 0) {
            error_log("[imageUrlToBase64] 절대 URL 감지");
            $imageData = $fetchImageWithCurl($imageUrl);
            
            if ($imageData === false) {
                // mathking.kr 도메인으로 재시도
                if (strpos($imageUrl, 'mathking.kr') === false) {
                    $retryUrl = 'https://mathking.kr' . $imageUrl;
                    error_log("[imageUrlToBase64] mathking.kr 도메인으로 재시도: $retryUrl");
                    $imageData = $fetchImageWithCurl($retryUrl);
                }
            }
            
            if ($imageData !== false) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $detectedMime = finfo_buffer($finfo, $imageData);
                finfo_close($finfo);
                $mimeType = $detectedMime ?: 'image/jpeg';
                $base64 = base64_encode($imageData);
                error_log("[imageUrlToBase64] base64 변환 성공, mime: $mimeType, 길이: " . strlen($base64));
                return "data:{$mimeType};base64,{$base64}";
            }
        }

        // 상대 경로인 경우 (images/ 또는 로컬 파일)
        $fullPath = __DIR__ . '/' . ltrim($imageUrl, '/');
        error_log("[imageUrlToBase64] 로컬 파일 경로 확인: $fullPath");
        if (file_exists($fullPath)) {
            $imageData = file_get_contents($fullPath);
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fullPath);
            finfo_close($finfo);
            $base64 = base64_encode($imageData);
            error_log("[imageUrlToBase64] 로컬 파일 base64 변환 성공");
            return "data:{$mimeType};base64,{$base64}";
        }

        // mathking.kr 도메인 URL로 변환 시도
        $fullUrl = 'https://mathking.kr/moodle/local/augmented_teacher/alt42/teachingsupport/' . ltrim($imageUrl, '/');
        error_log("[imageUrlToBase64] mathking.kr 경로로 시도: $fullUrl");
        $imageData = $fetchImageWithCurl($fullUrl);
        if ($imageData !== false) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_buffer($finfo, $imageData);
            finfo_close($finfo);
            $mimeType = $detectedMime ?: 'image/jpeg';
            $base64 = base64_encode($imageData);
            error_log("[imageUrlToBase64] mathking.kr 경로에서 성공");
            return "data:{$mimeType};base64,{$base64}";
        }

        error_log("[imageUrlToBase64] 모든 시도 실패 - URL: $imageUrl");
        return null;
    }

    debug_log("대화형 나레이션 생성 시작 (ktm_teaching_interactions)");

    // Moodle 설정 포함
    $moodleConfig = "/home/moodle/public_html/moodle/config.php";
    if (file_exists($moodleConfig)) {
        include_once($moodleConfig);
        global $DB, $USER;
        require_login();
        debug_log("Moodle 설정 로드 완료");
    } else {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] Moodle 설정 파일을 찾을 수 없습니다");
    }

    // 파라미터 받기 및 검증
    $interactionId = isset($_POST['interactionId']) ? intval($_POST['interactionId']) : 0;
    $solutionText = isset($_POST['solution']) ? $_POST['solution'] : '';
    $generateTTS = isset($_POST['generateTTS']) ? $_POST['generateTTS'] === 'true' : true;
    $customSolution = isset($_POST['customSolution']) ? $_POST['customSolution'] === 'true' : false;
    $hintLevel = isset($_POST['hintLevel']) ? $_POST['hintLevel'] : 'early'; // 힌트 레벨: explain, early, middle, full
    $timecreated = time();

    debug_log("파라미터 - interactionId: $interactionId, generateTTS: " . ($generateTTS ? 'true' : 'false') . ", customSolution: " . ($customSolution ? 'true' : 'false') . ", hintLevel: $hintLevel");

    if ($interactionId <= 0) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 유효하지 않은 상호작용 ID입니다: $interactionId");
    }

    // ktm_teaching_interactions 레코드 가져오기 (SQL로 모든 필드 명시적 조회)
    $interaction = $DB->get_record_sql(
        "SELECT id, type, userid, contentsid, contentstype, problem_type, problem_image, solution_image, solution_text, narration_text 
         FROM {ktm_teaching_interactions} 
         WHERE id = ?", 
        array($interactionId)
    );
    if (!$interaction) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 상호작용 레코드를 찾을 수 없습니다. ID: $interactionId");
    }
    
    // DB에서 가져온 이미지 값 상세 로깅 (원본 값 그대로)
    debug_log("=== DB에서 가져온 이미지 정보 (원본) ===");
    debug_log("interaction ID: " . $interactionId);
    debug_log("interaction type: " . ($interaction->type ?? 'NULL'));
    debug_log("problem_image 존재여부: " . (isset($interaction->problem_image) ? 'YES' : 'NO'));
    debug_log("problem_image 값: " . ($interaction->problem_image ?? 'NULL'));
    debug_log("problem_image 길이: " . (isset($interaction->problem_image) ? strlen($interaction->problem_image) : 0));
    debug_log("solution_image 존재여부: " . (isset($interaction->solution_image) ? 'YES' : 'NO'));
    debug_log("solution_image 값: " . ($interaction->solution_image ?? 'NULL'));
    debug_log("solution_image 길이: " . (isset($interaction->solution_image) ? strlen($interaction->solution_image) : 0));
    debug_log("========================================");
    
    // audio_url 필드 타입 확인 및 필요시 변경 (VARCHAR(255) -> TEXT)
    try {
        $fieldInfo = $DB->get_record_sql("SHOW COLUMNS FROM {ktm_teaching_interactions} WHERE Field = 'audio_url'");
        if ($fieldInfo && strpos(strtolower($fieldInfo->Type), 'varchar') !== false) {
            // VARCHAR(255)인 경우 TEXT로 변경
            debug_log("audio_url 필드 타입 변경 중: VARCHAR -> TEXT");
            $DB->execute("ALTER TABLE {ktm_teaching_interactions} MODIFY COLUMN audio_url TEXT DEFAULT NULL");
            debug_log("audio_url 필드 타입 변경 완료");
        }
    } catch (Exception $e) {
        // 필드 타입 변경 실패는 치명적이지 않으므로 경고만 기록
        debug_log("audio_url 필드 타입 확인/변경 중 오류 (무시): " . $e->getMessage());
    }

    // solution_text 결정 로직
    // customSolution=true면 무조건 파라미터의 solution 사용 (사용자 입력 풀이)
    if ($customSolution && !empty($solutionText)) {
        $maintext = $solutionText;
        debug_log("customSolution=true, 사용자 입력 풀이 사용 (길이: " . strlen($solutionText) . ")");
    } else if (empty($interaction->solution_text) && !empty($solutionText)) {
        $maintext = $solutionText;
        debug_log("DB에 solution_text가 없어 파라미터 사용");
    } else {
        $maintext = $interaction->solution_text ?? '';
        debug_log("DB의 solution_text 사용");
    }

    // askhint 타입인 경우: contentsid로 문제/해설 정보 가져오기
    $interactionType = isset($interaction->type) ? $interaction->type : '';
    
    // 디버그: interaction 데이터 전체 로깅
    debug_log("=== askhint 디버그 정보 ===");
    debug_log("interaction->type: " . ($interaction->type ?? 'NULL'));
    debug_log("interaction->contentsid: " . ($interaction->contentsid ?? 'NULL'));
    debug_log("interaction->contentstype: " . ($interaction->contentstype ?? 'NULL'));
    debug_log("interaction->problem_image: " . (isset($interaction->problem_image) ? substr($interaction->problem_image, 0, 200) : 'NULL'));
    debug_log("interaction->solution_image: " . (isset($interaction->solution_image) ? substr($interaction->solution_image, 0, 200) : 'NULL'));
    debug_log("=========================");
    
    // askhint 타입 처리
    if ($interactionType === 'askhint') {
        // customSolution=true인 경우: 직접 입력한 힌트 사용 (이미지 기반 처리 건너뛰기)
        if ($customSolution && !empty($solutionText)) {
            debug_log("askhint 타입 + customSolution=true - 직접 입력 힌트 사용 (길이: " . strlen($solutionText) . ")");
            $maintext = $solutionText;
        } else {
            // 일반 askhint: 이미지 기반으로 힌트 생성
            debug_log("askhint 타입 - 이미지 기반 힌트 생성 모드");
            
            // maintext를 이미지 참조용으로 설정
            $maintext = "제공된 문제 이미지와 해설 이미지를 분석하여 힌트를 생성해주세요.";
        }
        
        // contentsid가 있으면 추가 문제 정보 조회 (보조용) - customSolution이 아닌 경우에만
        if (!$customSolution && !empty($interaction->contentsid) && $interaction->contentstype == 2) {
            try {
                $qtext = $DB->get_record_sql("SELECT questiontext, generalfeedback FROM {question} WHERE id=? LIMIT 1", array($interaction->contentsid));
                if ($qtext) {
                    // 문제 텍스트 (참고용)
                    $problemText = strip_tags($qtext->questiontext);
                    $problemText = html_entity_decode($problemText, ENT_QUOTES, 'UTF-8');
                    
                    // 해설 텍스트 (참고용)
                    $solutionTextFromDb = strip_tags($qtext->generalfeedback);
                    $solutionTextFromDb = html_entity_decode($solutionTextFromDb, ENT_QUOTES, 'UTF-8');
                    
                    // maintext에 텍스트 정보도 추가 (이미지가 주, 텍스트는 보조)
                    $maintext = "제공된 문제 이미지와 해설 이미지를 분석하여 힌트를 생성해주세요.\n\n";
                    $maintext .= "[참고 - 문제 텍스트]: " . $problemText;
                    if (!empty($solutionTextFromDb)) {
                        $maintext .= "\n\n[참고 - 해설 텍스트]: " . $solutionTextFromDb;
                    }
                    debug_log("askhint - contentsid로 보조 텍스트 조회 성공");
                }
            } catch (Exception $e) {
                debug_log("askhint - contentsid로 보조 텍스트 조회 실패 (무시): " . $e->getMessage());
            }
        }
        debug_log("askhint 타입: 이미지 기반 maintext 설정 완료");
    } else if (empty($maintext)) {
        // askhint가 아닌 경우 maintext 필수
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 풀이 텍스트가 없습니다.");
    }

    $title = $interaction->problem_type ?? '수학 문제 풀이';
    debug_log("상호작용 로드 완료 - 제목: $title");

    // problem_image 가져오기 (문제 이미지)
    $problemImageBase64 = null;
    if (!empty($interaction->problem_image)) {
        debug_log("problem_image 발견: " . substr($interaction->problem_image, 0, 200));
        $problemImageBase64 = imageUrlToBase64($interaction->problem_image);
        if ($problemImageBase64) {
            debug_log("problem_image를 base64로 변환 성공");
        } else {
            debug_log("problem_image를 base64로 변환 실패");
        }
    } else {
        debug_log("problem_image가 비어있음 (DB에 NULL)");
    }
    
    // solution_image 가져오기 (해설 이미지, customSolution이 false인 경우에만)
    $solutionImageBase64 = null;
    if (!$customSolution && !empty($interaction->solution_image)) {
        debug_log("solution_image 발견: " . substr($interaction->solution_image, 0, 200));
        $solutionImageBase64 = imageUrlToBase64($interaction->solution_image);
        if ($solutionImageBase64) {
            debug_log("solution_image를 base64로 변환 성공");
        } else {
            debug_log("solution_image를 base64로 변환 실패");
        }
    } else {
        if ($customSolution) {
            debug_log("customSolution=true이므로 solution_image를 무시합니다");
        } else {
            debug_log("solution_image가 비어있음 (DB에 NULL)");
        }
    }
    
    // askhint 타입이고 이미지가 없는 경우: contentsid로 이미지 직접 조회
    if ($interactionType === 'askhint' && !empty($interaction->contentsid) && $interaction->contentstype == 2) {
        debug_log("askhint 모드: contentsid로 이미지 조회 시도 (problem_image: " . ($problemImageBase64 ? "있음" : "없음") . ", solution_image: " . ($solutionImageBase64 ? "있음" : "없음") . ")");
        
        try {
            $qtext = $DB->get_record_sql("SELECT questiontext, generalfeedback FROM {question} WHERE id=? LIMIT 1", array($interaction->contentsid));
            if ($qtext) {
                // problem_image가 없는 경우: questiontext에서 문제 이미지 추출
                if (!$problemImageBase64) {
                    debug_log("problem_image가 없어 questiontext에서 문제 이미지 조회 시도");
                    $htmlDom2 = new DOMDocument;
                    @$htmlDom2->loadHTML($qtext->questiontext);
                    $imageTags2 = $htmlDom2->getElementsByTagName('img');
                    
                    foreach ($imageTags2 as $imageTag2) {
                        $imgSrc2 = $imageTag2->getAttribute('src');
                        $imgSrc2 = str_replace(' ', '%20', $imgSrc2);
                        
                        if (!empty($imgSrc2) && strpos($imgSrc2, 'hintimages') === false && (strpos($imgSrc2, '.png') !== false || strpos($imgSrc2, '.jpg') !== false || strpos($imgSrc2, '.jpeg') !== false || strpos($imgSrc2, '.gif') !== false)) {
                            debug_log("contentsid에서 문제 이미지 발견: " . substr($imgSrc2, 0, 100));
                            $problemImageBase64 = imageUrlToBase64($imgSrc2);
                            if ($problemImageBase64) {
                                debug_log("contentsid에서 가져온 문제 이미지를 base64로 변환 성공");
                                break;
                            }
                        }
                    }
                }
                
                // solution_image가 없는 경우: generalfeedback에서 해설 이미지 추출
                if (!$solutionImageBase64) {
                    debug_log("solution_image가 없어 generalfeedback에서 해설 이미지 조회 시도");
                    $htmlDom = new DOMDocument;
                    @$htmlDom->loadHTML($qtext->generalfeedback);
                    $imageTags = $htmlDom->getElementsByTagName('img');
                    
                    foreach ($imageTags as $imageTag) {
                        $imgSrc = $imageTag->getAttribute('src');
                        $imgSrc = str_replace(' ', '%20', $imgSrc);
                        
                        // MATRIX/MATH 경로의 해설 이미지만 선택 (hintimages 제외)
                        if (!empty($imgSrc) && strpos($imgSrc, 'hintimages') === false && (strpos($imgSrc, '.png') !== false || strpos($imgSrc, '.jpg') !== false || strpos($imgSrc, '.jpeg') !== false || strpos($imgSrc, '.gif') !== false)) {
                            debug_log("contentsid에서 해설 이미지 발견: " . substr($imgSrc, 0, 100));
                            $solutionImageBase64 = imageUrlToBase64($imgSrc);
                            if ($solutionImageBase64) {
                                debug_log("contentsid에서 가져온 해설 이미지를 base64로 변환 성공");
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            debug_log("contentsid로 이미지 조회 중 오류: " . $e->getMessage());
        }
    }

    // interaction type 확인 (askhint인 경우 힌트 전용 프롬프트 사용)
    // $interactionType은 위에서 이미 선언됨
    debug_log("상호작용 type: " . ($interactionType ?: 'null'));
    
    // type='askhint'인 경우: 힌트 레벨에 따라 다른 프롬프트 사용
    if ($interactionType === 'askhint') {
        debug_log("askhint 타입 감지 - 힌트 레벨: $hintLevel");
        
        // JSON 프롬프트 파일 로드 시도
        $promptConfig = loadPromptConfig();
        $promptFromJson = getHintPrompt($hintLevel, $promptConfig);
        
        // JSON에서 프롬프트를 성공적으로 로드한 경우
        if ($promptFromJson && !empty($promptFromJson['systemPrompt'])) {
            debug_log("JSON 프롬프트 사용: " . $promptFromJson['name']);
            $systemPrompt = $promptFromJson['systemPrompt'];
            $ttsGuidelines = $promptFromJson['ttsGuidelines'];
            
            // 예시가 있으면 프롬프트에 추가
            if (!empty($promptFromJson['example'])) {
                $systemPrompt .= "\n\n# 출력 예시 (반드시 이 구조를 따라야 함 - @ 기호 2개)\n\n" . $promptFromJson['example'];
            }
            
            // TTS 지침 추가
            if (!empty($ttsGuidelines)) {
                $systemPrompt .= "\n\n" . $ttsGuidelines;
            }
        } else {
            // JSON 로드 실패 시 기본 하드코딩 프롬프트 사용
            debug_log("JSON 프롬프트 로드 실패, 기본 프롬프트 사용");
            
            // 공통 TTS 지침 + 2단계 강제 규칙
            $ttsGuidelines = "
# ⚠️⚠️⚠️ 가장 중요한 규칙: @ 기호는 정확히 2개만! ⚠️⚠️⚠️

- 출력은 반드시 【1단계】와 【2단계】두 부분으로만 나뉘어야 함
- @ 기호는 1단계 끝에 1개, 2단계 끝에 1개 = 총 2개만!
- @ 기호가 2개보다 많으면 실패
- 1단계 전체 내용을 하나의 문단으로 작성 후 @
- 2단계 전체 내용을 하나의 문단으로 작성 후 @

# 중요한 TTS 지침

- TTS로 읽히므로 모든 숫자, 기호, 알파벳은 한글 발음으로 변환
- 예: 1 → 일, x → 엑스, + → 더하기, - → 빼기, × → 곱하기, ÷ → 나누기, = → 은
- 분수: a/b → 비분의 에이
- 거듭제곱: x² → 엑스의 제곱, x³ → 엑스의 세제곱
- 초등~중등 수준 학습자가 이해할 수 있는 쉬운 표현 사용
- 마지막에 격려하는 멘트 추가
- 괄호는 발음하지 않는다
- 숫자나 기호 절대 금지. 모두 한글로 변환";

        // 힌트 레벨별 프롬프트 분기 (⚠️ 모든 힌트는 반드시 2단계로 구성 ⚠️)
        switch ($hintLevel) {
            case 'explain':
                // 📖 문제해설: 1단계(문제해설) + 2단계(핵심포인트)
                $systemPrompt = "# Role:

너는 학생이 문제를 이해할 수 있도록 돕는 수학 튜터야.

# ⚠️⚠️⚠️ 절대 규칙: 반드시 2단계로 구성 ⚠️⚠️⚠️

출력은 반드시 【1단계】와 【2단계】두 부분으로만 나뉘어야 합니다.
- 1단계 끝에 @ 하나로 구분
- 2단계 끝에 @ 하나로 마무리
- 총 @ 기호는 2개만 사용

## 【1단계: 문제 해설】
- 문제를 천천히 읽으면서 무엇을 구하는 문제인지 설명
- 이 문제가 왜 출제되었는지, 어떤 개념과 공식이 필요한지 설명
- 문제에 제시된 조건들을 토대로 어떤 식을 세워야 하는지 구체적으로 안내
- 1단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

## 【2단계: 핵심포인트】
- 이 문제를 풀 때 반드시 기억해야 할 핵심 개념 1~2개
- 실수하기 쉬운 포인트 또는 꼭 확인해야 할 사항
- 학생이 직접 풀어보도록 격려
- 2단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

# 핵심 원칙

⛔ 절대 금지: 정답 알려주기, 실제 계산 보여주기
✅ 필수: @ 기호는 1단계 끝과 2단계 끝에만 총 2개

# 출력 형식 (반드시 이 형식을 따르세요 - @ 기호 2개만!)

[1단계: 문제 해설 전체 내용을 한 문단으로]@
[2단계: 핵심포인트 전체 내용을 한 문단으로]@
$ttsGuidelines

# 출력 예시 (반드시 이 구조를 따라야 함 - @ 기호 2개)

이 문제는 자연수 오를 분수 칠팔분의 일로 나누는 계산이야. 예시에서처럼 자연수가 분수를 나눌 때는 나누는 분수를 거꾸로 뒤집어서 곱하기로 바꾸는 원리를 사용해. 자연수 오는 분모가 일이 되는 오분의 일로 바꿀 수 있고 나누기 칠팔분의 일을 곱하기 팔칠분의 일로 바꿔서 계산하는 흐름을 만들면 돼. 그래서 첫 단계의 목적은 오 나누기 칠팔분의 일을 오분의 일 곱하기 팔칠분의 일이라는 구조로 바꾸는 거야.@
핵심포인트는 자연수도 분수처럼 분모가 일이 되는 꼴로 바꾼다는 점과 분수로 나누기는 그 분수를 뒤집어 곱한다는 규칙을 적용하는 거야.@";
                break;
            
            case 'early':
                // 🔰 초반풀이: 1단계(문제해설+핵심포인트) + 2단계(풀이 초반해설)
                $systemPrompt = "# Role:

너는 학생이 문제를 이해하고 풀이를 시작할 수 있도록 돕는 수학 튜터야.

# ⚠️⚠️⚠️ 절대 규칙: 반드시 2단계로 구성 ⚠️⚠️⚠️

출력은 반드시 【1단계】와 【2단계】두 부분으로만 나뉘어야 합니다.
- 1단계 끝에 @ 하나로 구분
- 2단계 끝에 @ 하나로 마무리
- 총 @ 기호는 2개만 사용

## 【1단계: 문제 해설 + 핵심포인트】
- 문제를 천천히 읽으면서 무엇을 구하는 문제인지 설명
- 이 문제가 왜 출제되었는지, 어떤 개념과 공식이 필요한지 설명
- 문제에 제시된 조건들을 토대로 어떤 식을 세워야 하는지 구체적으로 안내
- 핵심포인트와 주의사항까지 포함
- 1단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

## 【2단계: 풀이 초반 해설】
- 풀이의 첫 1~2단계를 직접 보여줌
- 나머지는 학생이 스스로 해보도록 유도
- 격려의 말
- 2단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

# 핵심 원칙

⛔ 절대 금지: 정답 알려주기, 풀이 중반 이후 보여주기
✅ 필수: @ 기호는 1단계 끝과 2단계 끝에만 총 2개

# 출력 형식 (반드시 이 형식을 따르세요 - @ 기호 2개만!)

[1단계: 문제 해설 + 핵심포인트 전체 내용을 한 문단으로]@
[2단계: 풀이 초반 해설 전체 내용을 한 문단으로]@
$ttsGuidelines

# 출력 예시 (반드시 이 구조를 따라야 함 - @ 기호 2개)

이 문제는 자연수와 분수의 나눗셈을 분수끼리의 곱셈으로 변환하는 기본 훈련 문제야. 나누는 분수 칠팔분의 일을 뒤집어서 팔칠분의 일로 바꾸고 자연수 오는 분모가 일이 되는 오분의 일로 고쳐 적는 것이 출발이야. 그래서 문제를 읽고 나면 오 나누기 칠팔분의 일을 오분의 일 나누기 칠팔분의 일로 보고 그다음을 오분의 일 곱하기 팔칠분의 일로 바꿔서 계산 준비를 하게 돼.@
이제 초반 풀이에서는 오분의 일 곱하기 팔칠분의 일이라는 구조가 만들어졌으니까 분자끼리 곱하고 분모끼리 곱하는 계산을 할 준비가 된 상태야. 이 단계에서는 계산을 하지 않고 곱셈 형태까지 만드는 게 목표야.@";
                break;
            
            case 'middle':
                // 📝 중반풀이: 1단계(문제해설+핵심포인트) + 2단계(풀이 중반해설)
                $systemPrompt = "# Role:

너는 학생이 문제 풀이의 중반까지 따라올 수 있도록 돕는 수학 튜터야.

# ⚠️⚠️⚠️ 절대 규칙: 반드시 2단계로 구성 ⚠️⚠️⚠️

출력은 반드시 【1단계】와 【2단계】두 부분으로만 나뉘어야 합니다.
- 1단계 끝에 @ 하나로 구분
- 2단계 끝에 @ 하나로 마무리
- 총 @ 기호는 2개만 사용

## 【1단계: 문제 해설 + 핵심포인트】
- 문제를 천천히 읽으면서 무엇을 구하는 문제인지 설명
- 이 문제가 왜 출제되었는지, 어떤 개념과 공식이 필요한지 설명
- 문제에 제시된 조건들을 토대로 어떤 식을 세워야 하는지 구체적으로 안내
- 핵심포인트와 주의사항까지 포함
- 1단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

## 【2단계: 풀이 중반 해설】
- 풀이의 초반~중반 단계를 자세히 설명
- 마지막 단계 직전까지 안내
- 마지막은 학생이 완성하도록 유도하며 격려
- 2단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

# 핵심 원칙

⛔ 절대 금지: 정답 알려주기, 마지막 계산 보여주기
✅ 필수: @ 기호는 1단계 끝과 2단계 끝에만 총 2개

# 출력 형식 (반드시 이 형식을 따르세요 - @ 기호 2개만!)

[1단계: 문제 해설 + 핵심포인트 전체 내용을 한 문단으로]@
[2단계: 풀이 중반 해설 전체 내용을 한 문단으로]@
$ttsGuidelines

# 출력 예시 (반드시 이 구조를 따라야 함 - @ 기호 2개)

이 문제는 자연수 나누기 분수를 자연수 곱하기 분수의 역수로 바꾸는 과정을 익히는 것이 목적이야. 자연수 오는 오분의 일로 바꾸고 나누기 칠팔분의 일을 곱하기 팔칠분의 일로 바꾼 뒤 분수끼리 곱하는 규칙을 적용하게 돼. 그래서 자연수와 분수의 관계, 나눗셈을 곱셈으로 바꾸는 이유, 분자분모 곱하는 규칙이 모두 연결되는 문제야.@
중반 풀이에서는 오분의 일 곱하기 팔칠분의 일이 되었으니까 오 곱하기 팔은 분자로 가고 일 곱하기 칠은 분모로 가는 구조가 된다는 사실을 이용해 가분수가 만들어질 것이라는 흐름까지 파악하는 단계야. 이때 아직 값을 계산하지 않고 어떤 형태가 나올지 예상하는 것이 중반부의 핵심이야.@";
                break;
            
            case 'full':
                // 📋 전체해설: 1단계(문제해설+핵심포인트) + 2단계(풀이해설, 계산 제외)
                $systemPrompt = "# Role:

너는 학생에게 문제의 전체 풀이 과정을 해설해주는 수학 튜터야. 구체적인 숫자 계산 결과는 생략하고, 풀이의 논리적 흐름을 설명해.

# ⚠️⚠️⚠️ 절대 규칙: 반드시 2단계로 구성 ⚠️⚠️⚠️

출력은 반드시 【1단계】와 【2단계】두 부분으로만 나뉘어야 합니다.
- 1단계 끝에 @ 하나로 구분
- 2단계 끝에 @ 하나로 마무리
- 총 @ 기호는 2개만 사용

## 【1단계: 문제 해설 + 핵심포인트】
- 문제를 천천히 읽으면서 무엇을 구하는 문제인지 설명
- 이 문제가 왜 출제되었는지, 어떤 개념과 공식이 필요한지 설명
- 문제에 제시된 조건들을 토대로 어떤 식을 세워야 하는지 구체적으로 안내
- 핵심포인트와 주의사항까지 포함
- 1단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

## 【2단계: 풀이 해설 (계산 없이)】
- 풀이의 전체 단계를 논리적으로 설명 (계산 결과는 생략)
- 각 단계에서 무엇을 해야 하는지 안내
- 답의 형태와 검산 방법 안내
- 학생이 직접 계산해서 확인하도록 격려
- 2단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

# 핵심 원칙

⛔ 절대 금지: 정답(숫자) 알려주기, 실제 계산 결과 말하기
✅ 필수: @ 기호는 1단계 끝과 2단계 끝에만 총 2개

# 출력 형식 (반드시 이 형식을 따르세요 - @ 기호 2개만!)

[1단계: 문제 해설 + 핵심포인트 전체 내용을 한 문단으로]@
[2단계: 풀이 해설 전체 내용을 한 문단으로]@
$ttsGuidelines

# 출력 예시 (반드시 이 구조를 따라야 함 - @ 기호 2개)

이 문제는 자연수를 분수로 나누는 기본 원리를 활용하는 문제로 자연수 오는 분모가 일이 되는 오분의 일로 바꿀 수 있고 나누는 분수 칠팔분의 일을 뒤집은 팔칠분의 일로 바꾸어 곱하는 방식으로 해결하게 돼. 따라서 오 나누기 칠팔분의 일은 오분의 일 곱하기 팔칠분의 일로 변환되고 분수 곱셈의 원리에 따라 분자끼리 곱하고 분모끼리 곱하는 계산 과정을 거쳐 최종 값을 얻게 되며 이 모든 과정이 자연수 나눗셈을 분수 곱셈으로 바꾸는 대표적인 풀이 흐름이야.@
전체 해설 단계에서는 이 문제의 흐름이 자연수 변환, 분수 뒤집기, 곱셈 적용, 결과 해석의 네 단계로 연속적으로 이어진다는 점을 이해하는 것이 핵심이야.@";
                break;
            
            case 'custom':
                // ✍️ 직접 입력 힌트: 사용자가 입력한 텍스트를 TTS용으로 정리
                $systemPrompt = "# Role:

너는 사용자가 입력한 힌트 텍스트를 TTS(Text-to-Speech)용으로 정리하는 도우미야.

# 목표

사용자가 직접 작성한 힌트를 자연스럽게 읽히도록 정리해줘.

# 규칙

1. 입력된 텍스트의 내용은 그대로 유지
2. 이미 @ 표시가 있으면 그대로 사용
3. @ 표시가 없으면 적절한 위치에 @ 추가 (문장 끝, 문단 끝)
4. 숫자, 기호, 알파벳은 한글 발음으로 변환
   - 예: 1 → 일, x → 엑스, + → 더하기, = → 은
   - 분수: a/b → 비분의 에이
5. 괄호 안 내용은 자연스럽게 읽히도록 수정
6. TTS가 자연스럽게 읽을 수 있도록 어색한 표현 수정

# 출력 형식

- 입력된 힌트 내용을 TTS용으로 정리한 결과만 출력
- 추가 설명이나 주석 없이 정리된 텍스트만 출력
$ttsGuidelines";
                break;
            
            default:
                // 기본값은 'early'와 동일한 2단계 구조
                $systemPrompt = "# Role:

너는 학생이 문제를 이해하고 풀이를 시작할 수 있도록 돕는 수학 튜터야.

# ⚠️⚠️⚠️ 절대 규칙: 반드시 2단계로 구성 ⚠️⚠️⚠️

출력은 반드시 【1단계】와 【2단계】두 부분으로만 나뉘어야 합니다.
- 1단계 끝에 @ 하나로 구분
- 2단계 끝에 @ 하나로 마무리
- 총 @ 기호는 2개만 사용

## 【1단계: 문제 해설 + 핵심포인트】
- 문제 읽기 및 해설
- 문제의 취지 및 개념/공식 설명
- 식 세우기 안내 + 핵심포인트
- 1단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

## 【2단계: 풀이 초반 해설】
- 풀이 초반부 보여주기
- 나머지는 학생이 직접 풀도록 유도
- 2단계 전체를 하나의 문단으로 작성하고 끝에 @ 붙이기

# 출력 형식 (@ 기호 2개만!)

[1단계 전체 내용]@
[2단계 전체 내용]@
$ttsGuidelines";
                break;
        }
        } // JSON 로드 실패 시 기본 프롬프트 switch문 else 블록 닫기

    } else {
        // 기존 수학 콘텐츠 나레이터 프롬프트 (일반 모드)
        // JSON에서 프롬프트 로드 시도
        $solutionPromptData = getSolutionBasePrompt($promptConfig);

        if ($solutionPromptData && !empty($solutionPromptData['systemPrompt'])) {
            // JSON에서 로드된 프롬프트 사용
            $systemPrompt = $solutionPromptData['systemPrompt'];
            debug_log("일반 모드: JSON에서 해설지 생성 기본 프롬프트 로드 성공");
        } else {
            // JSON 로드 실패 시 기본 하드코딩 프롬프트 사용
            debug_log("일반 모드: JSON 로드 실패, 기본 프롬프트 사용");
            $systemPrompt = "# Role:

act as a mathematics content narrator specialized in converting written math content into engaging, clear, and accurate narration scripts for video content.



# Context:

- The expert needs to convert any mathematical content into a script that sounds natural when spoken in Korean, maintaining the sequence and coherence of the original content.

- The narrator is tasked with making the content understandable and engaging, using explanations, examples, and analogies, especially clarifying any potentially confusing parts.



# Input Values:

- Mathematical text containing numbers, symbols, etc.



# Instructions:

- Convert all numbers into their spoken Korean equivalents (e.g., 1 as 일, 2 as 이, etc.) . 최종 결과물에는 한글만 존재해야하며 다른 기호나 숫자는 존재하지 않아야 합니다.

-  Ensure all symbols, mathematical expressions, and alphabets are converted into their phonetic Korean readings.

- Maintain the logical sequence and coherence of the original mathematical content while transforming it into a narration script.

- Add explanatory notes, examples, or analogies to aid understanding, particularly clarifying any complex or confusing parts.

- Summarize each topic unit clearly, ensuring the script is engaging and understandable for a broad audience.

- Prepare the script for professional voice-over recording, ensuring it is suitable for educational video content.





# 이것은 중요해 ! 

- 어떤 생성결과도 한글만 사용해줘, 특수문자나 숫자, 기호 등은 절대로 사용하지 말아줘

- 해설에 나와있는 내용으로만 진행해줘.

- 결과 생성은 지시형식으로 자연스럽게 이어줘. 절대로 목록화(예시. - 목록1, - 목록2, - 목록3)를 하지마.

- 각각의 단락별로 음성파일이 일시정지가 되게 하려고 함. 이를 위해 마지막 부분에 @를 달아줘

- 입력되는 이미지에 있는 해설을 이용하여 각각의 의미있는 단계를 3~6단계로 나누고 해설을 읽어주는게 아닌 지시를 해야해.

- 중요한건 문제를 풀어주는게 아니고 학생이 스스로 문제를 해결할 수 있게끔 아주 살짝만 돕는거야.

- 학생이 문제의 흐름을 파악할 수 있게 단계별로 지시하고 학습을 돕는 역할만 하는거야. 지시에 대한 답은 절대로 주면 안돼.

- @단위로 멈추는 방식으로 TTS가 작동할 것이므로 반드시 학생에게 도전적이고 의미있는 질문이어야 해. 

- 초등~중등 수준 학습자가 이해할 수 있는 표현 사용. (멱 등 어려운단어 사용금지.)

- 모든 숫자, 기호, 알파벳은 반드시 한글 발음으로 변환. 괄호는 제외. 괄호는 발음하지않는다.



# Guidelines:

- The script should be detailed enough for a professional voice actor to understand and perform without needing additional context.

- The language should be clear, professional, and accessible, suitable for a mathematics educator.

- Where necessary, include cues for intonation or emphasis to guide the voice-over artist.

- 생성결과에 아무리 간단한 경우라고 해도 반드시 숫자, 기호 대신 한글만 사용되어야 해. 

- 마지막에는 학생을 격려하는 멘트를 추가해줘.

Output format:

- Plain text suitable for script reading.



# Output fields:

- Detailed narration script including numbers, explanations, examples, and any additional notes for clarity

Output examples:

오늘은 다항식 일 더하기 엑스 더하기 엑스의 제곱 더하기 엑스의 사제곱 의 삼제곱에 대한 전개식에서 세 가지 진술이 옳은지 판단해 볼 거예요. 첫째 엑스의 십일제곱의 계수는 영인지 둘째 엑스의 육제곱의 계수는 십인지 셋째 상수항을 포함한 모든 항의 계수 합은 육십사인지 확인합니다. 전개는 동일한 괄호가 세 번 곱해지는 형태라서 각 괄호에서 택한 항의 지수들을 더해 목표 지수를 만드는 문제로 바꿀 수 있어요. 문제가 이해되었나요?@



그럼 단계적으로 풀어 보죠. 첫 단계입니다. 각 괄호에서 선택할 수 있는 엑스의 지수는 영과 일과 이와 사 네 가지죠. 전개에서 엑스의 십일제곱을 만들려면 세 괄호에서 고른 지수들의 합이 얼마가 되어야 할까요?@



두번째 단계입니다. 합이 십일이 되려면 각 지수는 영 또는 일 또는 이 또는 사인데 이 네 값으로 세 개를 골라 합을 십일로 만들 수 있을까요? 불가능한지 가능한지 근거를 들어 말해 볼래요?@



셋째 단계 결론을 말해 볼래요. 방금 논리로 엑스의 십일제곱의 계수는 얼마가 되나요?@



좋아요 첫번째 진술을 알아내었어요.



넷째 단계로 엑스의 육제곱의 계수를 구해 봅시다. 세 지수의 합이 육이 되도록 영과 일과 이와 사 중에서 세 개를 고르는 방법의 가짓수를 세면 됩니다. 계산을 쉽게 하려고 경우를 나누어 생각해 보죠. 먼저 사가 한 번 포함되는 경우를 생각해 보세요. 나머지 두 지수의 합은 얼마가 되어야 할까요?@



다섯째 단계 그 둘을 세 괄호에 배치하는 순서를 모두 세어야 계수에 반영됩니다. 사가 한 번인 경우에 대해서 영과 이의 조합과 일이 두 번의 조합 각각 몇 가지 배치가 나오는지 차례대로 말해 보세요.@



여섯째 단계 이번에는 사가 전혀 없는 경우를 보죠. 사용할 수 있는 값은 영과 일과 이뿐이고 합이 육이 되어야 합니다. 가능한 세 지수의 형태를 떠올려 보세요.@



일곱째 단계 마지막으로 사가 두 번 이상인 경우를 검토합시다. 사가 두 번이면 이미 합이 팔이 되어 버려 조건을 넘기죠. 따라서 더 볼 것은 없어요. 이제 지금까지의 경우들을 모두 합쳐 엑스의 육제곱의 계수를 말해 보세요.@



훌륭해요 둘째 진술도 알아내었네요. 



이제 여덟째 단계 전체 계수의 합을 구해 봅시다. 모든 항의 계수 합은 엑스에 일을 대입하면 얻을 수 있어요. 다항식 일 더하기 엑스 더하기 엑스의 제곱 더하기 엑스의 사제곱 에 엑스에 일을 넣으면 값이 얼마가 되죠?@



아홉째 단계 그 값을 세 번 곱하니 결과는 얼마인가요?@



너무 잘했어요. 이렇게 세번째 진술도 알아내었습니다. 이번문제는 지수의 조합을 가지고 세가지 진술이 참인지 거짓인지 알아내는 문제였어요. 계산이 많은 문제인만큼 실수하지않도록 조심하는게 좋겠죠?@



# 이것은 매우 중요해

- 숫자를 표현할 때 반드시 아라비아숫자 읽기 (일, 이, 삼, 사, .... ,이십, 이십일..)를 사용해줘

- 하나, 둘, 셋, 넷, 다섯, 여섯, 일곱, 여덟, 아홉, 열, 열하나 ... 스물 등과 같은 표현은 사용하지말아줘.

- 소숫점을 잘 식별해서 읽어줘 0.35 (영점삼오)

- 소주제나 목차나 목록형으로 생성 금지. 단락 나누지마.

- 예시처럼 단계별로 행동지시를 해야해.

- 말을 너무 많이 하지마. 처음 문제에 대해 어떤 문제인지 소개할 때만 길게말하고 단계별로 지시할 때는 간단하게 한문장으로만 지시해. 덧붙이지말고.

- 숫자나 기호 절대 금지. 

- 괄호는 읽지않는다. 예: (x+y)는 엑스 플러스 와이라고 읽는다.  f(x)는 에프엑스 라고 읽한다.

- 괄호를 발음하지 않는다. 단, 괄호의 내용은 그대로 읽되 '왼쪽 괄호', '오른쪽 괄호' 또는 '괄호 안' 등의 표현은 사용하지 않는다.

- 지수는 항상 '몇제곱'으로 읽으며, '몇승'이라는 표현은 사용하지 않는다. 예를 들어, 엑스의 이승은 엑스의 이제곱으로 읽는다.

- 모든 분수는 계산 의미로 풀지 말고, 그대로 읽는다. 예를 들어 '엑스분의 일'을 '일 나누기 엑스'로 변환하지 않고, **'엑스분의 일'**이라고 그대로 발음한다.

- 대본을 소리내어 읽었을 때 자연스럽게 만들어야해.

- 숫자와 문자가 붙어있다면 붙여읽는다. 예: 2x+6y-7z 는 이엑스 플러스 육와이 마이너스 칠제트 라고읽는다.



#검토및수정

대본이 완성되면 반드시 검토한다. 검토중에 지침에서 벗어나거나 어색한 부분이 있다면 즉시 수정한다. 수정 후 다시 검토를 반복한다.

더이상 수정할 부분이 검토중에 보이지않는다면 결과물을 출력한다.";
        } // JSON 로드 실패 시 기본 프롬프트 else 블록 닫기
    } // else 종료 (일반 모드 프롬프트)

    // 이미지가 있는 경우 프롬프트에 추가
    if ($problemImageBase64 || $solutionImageBase64) {
        if ($interactionType === 'askhint') {
            // askhint 모드: 힌트 레벨에 따른 이미지 활용 지침
            $systemPrompt .= "\n\n# 이미지 활용 지침\n\n";
            
            // 문제 이미지 지침
            if ($problemImageBase64) {
                $systemPrompt .= "## 📷 문제 이미지 (problem_image)\n";
                $systemPrompt .= "- 문제 이미지가 제공되었습니다. 이 이미지에서 문제의 내용과 조건을 파악하세요.\n";
                $systemPrompt .= "- 문제 이미지에 나온 수식, 그림, 도표 등을 분석하여 무엇을 구하는 문제인지 설명하세요.\n\n";
            }
            
            // 해설 이미지 지침
            if ($solutionImageBase64) {
                $systemPrompt .= "## 📷 해설 이미지 (solution_image)\n";
                $systemPrompt .= "- 해설 이미지가 제공되었습니다. 이 이미지를 참고하여 힌트를 생성하세요.\n";
                $systemPrompt .= "- 해설 이미지에서 사용된 개념과 공식을 파악하여 설명하세요.\n";
                $systemPrompt .= "- 해설 이미지의 풀이 방식을 참고하여 어떤 식을 세워야 하는지 구체적으로 안내하세요.\n\n";
            }
            
            // 힌트 레벨별 추가 지침
            $systemPrompt .= "## ⚠️ 힌트 레벨별 제한사항\n";
            switch ($hintLevel) {
                case 'explain':
                    $systemPrompt .= "- 이미지를 참고하되, 실제 풀이 과정은 보여주지 마세요. 식을 세우는 것까지만 안내하세요.\n";
                    break;
                case 'early':
                    $systemPrompt .= "- 풀이의 초반 단계(첫 1~2단계)까지는 보여주되, 나머지는 학생이 직접 해보도록 하세요.\n";
                    break;
                case 'middle':
                    $systemPrompt .= "- 풀이의 중반 단계까지 안내하고, 마지막 계산 단계는 학생이 완성하도록 하세요.\n";
                    break;
                case 'full':
                    $systemPrompt .= "- 전체 풀이 흐름을 설명하되, 실제 계산 결과는 직접 말하지 마세요. 학생이 계산하도록 유도하세요.\n";
                    break;
                case 'custom':
                    $systemPrompt .= "- 직접 입력된 힌트를 TTS용으로 정리하는 모드입니다. 입력 내용을 자연스러운 음성으로 변환해주세요.\n";
                    break;
            }
            $systemPrompt .= "- 최종 정답(숫자)은 직접 알려주지 마세요.\n";
            debug_log("askhint 모드 ($hintLevel): 이미지 활용 지침 추가됨 (problem: " . ($problemImageBase64 ? "있음" : "없음") . ", solution: " . ($solutionImageBase64 ? "있음" : "없음") . ")");
        } else {
            // 일반 모드: 이미지 해설지 우선
            $systemPrompt .= "\n\n# ⚠️ 매우 중요 - 이미지 해설지 우선 규칙\n\n";
            if ($solutionImageBase64) {
                $systemPrompt .= "- 해설 이미지(solution_image)가 제공되었습니다. 이 이미지가 TTS 대본 생성의 최우선 기준입니다.\n";
                $systemPrompt .= "- 해설 이미지에 나온 모든 풀이 단계, 수식, 설명을 정확히 읽고 TTS 대본에 반영하세요.\n";
                $systemPrompt .= "- 해설 이미지의 순서를 그대로 따라 단계별로 지시를 만들어주세요.\n";
                $systemPrompt .= "- 텍스트 해설과 이미지 해설이 다른 경우, 반드시 이미지를 따르세요.\n";
                $systemPrompt .= "- 이미지에 없는 내용을 임의로 추가하지 마세요.\n";
            }
            if ($problemImageBase64) {
                $systemPrompt .= "- 문제 이미지(problem_image)도 제공되었습니다. 문제 파악에 참고하세요.\n";
            }
            debug_log("시스템 프롬프트에 이미지 우선 지침 추가됨");
        }
    }

    // 사용자 프롬프트 (type에 따라 다르게 생성)
    if ($interactionType === 'askhint') {
        // 힌트 레벨별 안내 메시지
        $hintLevelNames = [
            'explain' => '📖 문제해설 힌트 (2단계)',
            'early' => '🔰 초반풀이 힌트 (2단계)',
            'middle' => '📝 중반풀이 힌트 (2단계)',
            'full' => '📋 전체해설 힌트 (2단계)'
        ];
        $hintLevelName = isset($hintLevelNames[$hintLevel]) ? $hintLevelNames[$hintLevel] : '힌트';
        
        // askhint 모드: 2단계 구조의 힌트 사용자 프롬프트
        $userPrompt = "학생이 이 문제에 대해 {$hintLevelName}를 요청했습니다.\n\n";
        $userPrompt .= "제목: $title\n\n";
        $userPrompt .= "문제 내용:\n$maintext\n\n";
        
        // 힌트 레벨별 2단계 안내사항
        switch ($hintLevel) {
            case 'explain':
                $userPrompt .= "📋 반드시 2단계로 나누어 힌트를 제공해주세요:\n\n";
                $userPrompt .= "【1단계: 문제 해설】\n";
                $userPrompt .= "- 문제를 읽으면서 무엇을 구하는지 해설\n";
                $userPrompt .= "- 문제의 취지와 사용될 개념/공식 설명\n";
                $userPrompt .= "- 문제 조건을 토대로 어떤 식을 세울지 구체적으로 안내\n\n";
                $userPrompt .= "【2단계: 핵심포인트】\n";
                $userPrompt .= "- 이 문제의 핵심 개념 1~2개 강조\n";
                $userPrompt .= "- 실수하기 쉬운 포인트 언급\n";
                $userPrompt .= "- 학생이 직접 풀도록 격려\n\n";
                $userPrompt .= "⚠️ 중요: 실제 풀이나 계산은 보여주지 마세요. 단계 시작 시 '일단계', '이단계' 멘트를 넣어주세요.";
                break;
            
            case 'early':
                $userPrompt .= "📋 반드시 2단계로 나누어 힌트를 제공해주세요:\n\n";
                $userPrompt .= "【1단계: 문제 해설 + 핵심포인트】\n";
                $userPrompt .= "- 문제를 읽으면서 무엇을 구하는지 해설\n";
                $userPrompt .= "- 문제의 취지와 사용될 개념/공식 설명\n";
                $userPrompt .= "- 문제 조건을 토대로 어떤 식을 세울지 안내\n";
                $userPrompt .= "- 핵심포인트와 주의사항 언급\n\n";
                $userPrompt .= "【2단계: 풀이 초반 해설】\n";
                $userPrompt .= "- 풀이의 첫 1~2단계를 직접 보여줌\n";
                $userPrompt .= "- 나머지는 학생이 해보도록 유도하며 격려\n\n";
                $userPrompt .= "⚠️ 중요: 최종 정답은 알려주지 마세요. 단계 시작 시 '일단계', '이단계' 멘트를 넣어주세요.";
                break;
            
            case 'middle':
                $userPrompt .= "📋 반드시 2단계로 나누어 힌트를 제공해주세요:\n\n";
                $userPrompt .= "【1단계: 문제 해설 + 핵심포인트】\n";
                $userPrompt .= "- 문제를 읽으면서 무엇을 구하는지 해설\n";
                $userPrompt .= "- 문제의 취지와 사용될 개념/공식 설명\n";
                $userPrompt .= "- 문제 조건을 토대로 어떤 식을 세울지 안내\n";
                $userPrompt .= "- 핵심포인트와 주의사항 언급\n\n";
                $userPrompt .= "【2단계: 풀이 중반 해설】\n";
                $userPrompt .= "- 풀이의 초반~중반 단계를 자세히 설명\n";
                $userPrompt .= "- 마지막 계산 직전까지 안내\n";
                $userPrompt .= "- 마지막은 학생이 완성하도록 유도\n\n";
                $userPrompt .= "⚠️ 중요: 최종 정답은 알려주지 마세요. 단계 시작 시 '일단계', '이단계' 멘트를 넣어주세요.";
                break;
            
            case 'full':
                $userPrompt .= "📋 반드시 2단계로 나누어 힌트를 제공해주세요:\n\n";
                $userPrompt .= "【1단계: 문제 해설 + 핵심포인트】\n";
                $userPrompt .= "- 문제를 읽으면서 무엇을 구하는지 해설\n";
                $userPrompt .= "- 문제의 취지와 사용될 개념/공식 설명\n";
                $userPrompt .= "- 문제 조건을 토대로 어떤 식을 세울지 안내\n";
                $userPrompt .= "- 핵심포인트와 주의사항 언급\n\n";
                $userPrompt .= "【2단계: 풀이 해설 (계산 없이)】\n";
                $userPrompt .= "- 전체 풀이 과정의 논리적 흐름을 설명 (실제 계산 결과는 생략)\n";
                $userPrompt .= "- 답의 형태와 검산 방법 안내\n";
                $userPrompt .= "- 학생이 직접 계산해서 답을 구하도록 유도\n\n";
                $userPrompt .= "⚠️ 중요: 최종 정답(숫자)은 직접 알려주지 마세요. 단계 시작 시 '일단계', '이단계' 멘트를 넣어주세요.";
                break;
            
            case 'custom':
                $userPrompt .= "위 텍스트를 TTS용으로 정리해주세요. 내용은 유지하되, 숫자/기호를 한글로 변환하고 @ 구분점을 적절히 추가해주세요.";
                break;
            
            default:
                $userPrompt .= "📋 반드시 2단계로 나누어 힌트를 제공해주세요.\n\n";
                $userPrompt .= "⚠️ 중요: 최종 정답은 알려주지 마세요.";
                break;
        }
        
        if ($solutionImageBase64) {
            $userPrompt .= "\n\n해설 이미지가 제공되었습니다. 이 이미지를 참고하여 힌트를 생성해주세요.";
        }
        debug_log("askhint 모드: {$hintLevelName} 사용자 프롬프트 생성");
    } else {
        // 일반 모드: 이미지 해설지 우선
        if ($solutionImageBase64) {
            // 해설 이미지가 있는 경우: 이미지를 기준으로 TTS 대본 생성
            $userPrompt = "⚠️ 중요: 제공된 '해설 이미지(solution_image)'를 기준으로 TTS 대본을 생성하세요.\n\n";
            $userPrompt .= "제목: $title\n\n";
            $userPrompt .= "📷 해설 이미지가 제공되었습니다. 이 이미지에 나온 풀이 내용을 정확히 읽고, 이미지의 단계와 순서를 따라 TTS 대본을 생성해주세요.\n\n";
            $userPrompt .= "아래 텍스트 해설은 참고용입니다. 이미지와 텍스트가 다른 경우 반드시 이미지를 우선하세요:\n$maintext";
        } else {
            // 해설 이미지가 없는 경우: 텍스트 기준
            $userPrompt = "다음 수학 문제 풀이를 절차기억 형성 방식으로 설명해주세요:\n\n제목: $title\n\n풀이 내용:\n$maintext";
        }
    }

    debug_log("OpenAI API 호출 준비");

    // API 키 설정 (TTS 생성 함수에서도 사용 가능하도록 전역 변수로 설정)
    $apiKey = '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';
    
    // 환경 변수가 있으면 우선 사용
    $envApiKey = getenv('OPENAI_API_KEY');
    // API 키 형식 검증: sk- 또는 sk-proj-로 시작하고 최소 20자 이상
    if ($envApiKey && preg_match('/^sk(-proj)?-[a-zA-Z0-9_-]{20,}$/', $envApiKey)) {
        $apiKey = $envApiKey;
        debug_log("환경 변수에서 API 키 사용");
    } else {
        debug_log("하드코딩된 API 키 사용");
    }

    // OpenAI GPT API 호출
    $apiUrl = 'https://api.openai.com/v1/chat/completions';
    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ];

    // 메시지 배열 구성 (solution_image가 있으면 vision 형식 사용)
    $messages = [
        ['role' => 'system', 'content' => $systemPrompt]
    ];

    // 이미지가 있는 경우 vision 형식 사용
    $hasImages = ($problemImageBase64 || $solutionImageBase64);
    
    if ($hasImages) {
        // 이미지가 있는 경우: vision 형식으로 메시지 구성
        $imageCount = ($problemImageBase64 ? 1 : 0) + ($solutionImageBase64 ? 1 : 0);
        debug_log("vision 형식으로 메시지 구성 (이미지 {$imageCount}개 포함)");

        $userContent = [];

        // 텍스트 프롬프트 먼저 추가
        $userContent[] = [
            'type' => 'text',
            'text' => $userPrompt
        ];

        // 문제 이미지 추가 (problem_image)
        if ($problemImageBase64) {
            debug_log("problem_image를 API 요청에 추가");
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $problemImageBase64,
                    'detail' => 'high'
                ]
            ];
        }

        // 해설 이미지 추가 (solution_image)
        if ($solutionImageBase64) {
            debug_log("solution_image를 API 요청에 추가");
            $userContent[] = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => $solutionImageBase64,
                    'detail' => 'high'
                ]
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $userContent
        ];
    } else {
        // 이미지가 없는 경우: 기존 형식 사용
        debug_log("기본 형식으로 메시지 구성 (이미지 없음)");
        $messages[] = [
            'role' => 'user',
            'content' => $userPrompt
        ]; 
    }

    $data = [
        'model' => 'gpt-5.1',
        'messages' => $messages,
        'max_completion_tokens' => 4000,
        'temperature' => 0.7
    ];

    debug_log("OpenAI API 호출 시작");

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $gptResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] CURL 오류: " . $curlError);
    }

    debug_log("API 응답 코드: $httpCode");

    if ($httpCode !== 200) {
        $errorData = json_decode($gptResponse, true);
        $errorMessage = isset($errorData['error']['message']) ? $errorData['error']['message'] : $gptResponse;
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] OpenAI API 오류 (HTTP $httpCode): " . $errorMessage);
    }

    $gptData = json_decode($gptResponse, true);
    if (!isset($gptData['choices'][0]['message']['content'])) {
        throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] 나레이션 텍스트 생성 실패: 응답 형식 오류");
    }

    $narrationText = $gptData['choices'][0]['message']['content'];
    debug_log("나레이션 텍스트 생성 완료 (길이: " . mb_strlen($narrationText) . "자)");

    // 나레이션 텍스트를 narration_text 필드에 저장
    try {
        $DB->execute("UPDATE {ktm_teaching_interactions} SET narration_text = ? WHERE id = ?",
            array($narrationText, $interactionId));
        debug_log("나레이션 텍스트 DB 저장 완료");
    } catch (Exception $e) {
        debug_log("나레이션 텍스트 저장 실패 (무시): " . $e->getMessage());
    }

    if (!$generateTTS) {
        // TTS 생성 없이 텍스트만 반환
        $response['success'] = true;
        $response['narrationText'] = $narrationText;
        $response['message'] = '대화형 나레이션 텍스트가 생성되었습니다.';
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ========================================================================
    // TTS 생성 부분: mynote.php의 tts_section_generator.php 방식 그대로 이전
    // ========================================================================

    // @ 기호로 분리된 듣기평가 모드 확인
    $isListeningTest = (strpos($narrationText, '@') !== false);
    
    if ($isListeningTest && $generateTTS) {
        debug_log("듣기평가 모드 감지됨 - mynote.php 방식으로 TTS 생성 시작");
        
        // ====================================================================
        // tts_section_generator.php의 함수들을 직접 복사 (그대로 이전)
        // ====================================================================
        
        /**
         * Split text by @ separator and trim each section
         * (tts_section_generator.php에서 그대로 복사)
         */
        function splitTextBySeparator_local($text) {
            if (!is_string($text)) {
                throw new InvalidArgumentException("[generate_dialog_narration.php:" . __LINE__ . "] Input must be string");
            }
            if (empty($text)) {
                return [];
            }
            $sections = explode('@', $text);
            $sections = array_filter(array_map('trim', $sections));
            return array_values($sections);
        }
        
        /**
         * Validate section count is within acceptable range
         * (tts_section_generator.php에서 그대로 복사)
         */
        function validateSectionCount_local($sections) {
            if (!is_array($sections)) {
                throw new InvalidArgumentException("[generate_dialog_narration.php:" . __LINE__ . "] Input must be array");
            }
            $count = count($sections);
            if ($count < 2) {
                return ['valid' => false, 'error' => "최소 2개 이상의 섹션이 필요합니다. (현재: {$count}개) [generate_dialog_narration.php:" . __LINE__ . "]"];
            }
            if ($count > 20) {
                return ['valid' => false, 'error' => "섹션이 너무 많습니다. 최대 20개까지 가능합니다. (현재: {$count}개) [generate_dialog_narration.php:" . __LINE__ . "]"];
            }
            return ['valid' => true, 'error' => null];
        }
        
        /**
         * Generate filename for TTS audio file
         * (tts_section_generator.php에서 그대로 복사)
         */
        function generateTTSFilename_local($contentsid, $sectionNum) {
            if (!is_int($contentsid) || $contentsid <= 0) {
                throw new InvalidArgumentException("[generate_dialog_narration.php:" . __LINE__ . "] Invalid contentsid");
            }
            if (!is_int($sectionNum) || $sectionNum <= 0) {
                throw new InvalidArgumentException("[generate_dialog_narration.php:" . __LINE__ . "] Invalid sectionNum");
            }
            $timestamp = time();
            return "essay_instruction_{$contentsid}_section_{$sectionNum}_{$timestamp}.mp3";
        }
        
        /**
         * Upload audio file to server storage
         * (tts_section_generator.php에서 그대로 복사)
         */
        function uploadAudioFile_local($audioData, $filename, $contentsid, $sectionNum) {
            try {
                if (empty($audioData)) {
                    throw new Exception("빈 오디오 데이터 [generate_dialog_narration.php:" . __LINE__ . "]");
                }
                
                $safeFilename = basename($filename);
                if ($safeFilename !== $filename) {
                    throw new Exception("보안 위반: 경로 침입 시도 [generate_dialog_narration.php:" . __LINE__ . "]");
                }
                
                if (!preg_match('/^[a-zA-Z0-9_-]+\.mp3$/', $safeFilename)) {
                    throw new Exception("보안 위반: 허용되지 않은 파일명 형식 [generate_dialog_narration.php:" . __LINE__ . "]");
                }
                
                $baseUrl = 'https://mathking.kr/audiofiles/pmemory/sections/';
                $basePath = '/home/moodle/public_html/audiofiles/pmemory/sections/';
                
                if (!is_dir($basePath)) {
                    if (!mkdir($basePath, 0750, true)) {
                        throw new Exception("디렉토리 생성 실패: {$basePath} [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                }
                
                $filePath = $basePath . $safeFilename;
                
                $bytesWritten = file_put_contents($filePath, $audioData);
                if ($bytesWritten === false) {
                    throw new Exception("파일 쓰기 실패: {$filePath} [generate_dialog_narration.php:" . __LINE__ . "]");
                }
                
                @chmod($filePath, 0640);
                
                clearstatcache(true, $filePath);
                if (!file_exists($filePath)) {
                    throw new Exception("파일 생성 실패: 파일이 존재하지 않음 [generate_dialog_narration.php:" . __LINE__ . "]");
                }
                
                $actualSize = filesize($filePath);
                if ($actualSize !== $bytesWritten) {
                    throw new Exception("파일 검증 실패: 예상 {$bytesWritten}바이트, 실제 {$actualSize}바이트 [generate_dialog_narration.php:" . __LINE__ . "]");
                }
                
                $publicUrl = $baseUrl . $safeFilename;
                return ['success' => true, 'url' => $publicUrl, 'error' => null];
                
            } catch (Exception $e) {
                return ['success' => false, 'url' => null, 'error' => $e->getMessage()];
            }
        }
        
        /**
         * Call OpenAI TTS API to generate audio for a section
         * (tts_section_generator.php에서 그대로 복사)
         */
        function generateTTSForSection_local($text, $contentsid, $sectionNum) {
            $maxRetries = 3;
            $retryDelay = 1;
            
            for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
                try {
                    if (!is_string($text) || strlen($text) < 10) {
                        throw new Exception("텍스트가 너무 짧습니다 (최소 10자) [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    if (strlen($text) > 4000) {
                        throw new Exception("텍스트가 너무 깁니다 (최대 4000자) [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    
                    $filename = generateTTSFilename_local($contentsid, $sectionNum);
                    
                    // API 키 가져오기: 상단에서 정의된 $apiKey 사용 (전역 변수)
                    global $apiKey;
                    if (empty($apiKey)) {
                        // 전역 변수가 없으면 환경 변수 확인
                        $apiKey = getenv('OPENAI_API_KEY');
                        if (!$apiKey) {
                            throw new Exception("서버 설정 오류: API 키를 찾을 수 없습니다 [generate_dialog_narration.php:" . __LINE__ . "]");
                        }
                    }
                    // API 키 형식 검증: sk- 또는 sk-proj-로 시작하고 최소 20자 이상 (하이픈, 언더스코어 허용)
                    if (!preg_match('/^sk(-proj)?-[a-zA-Z0-9_-]{20,}$/', $apiKey)) {
                        debug_log("API 키 형식 검증 실패. 키 길이: " . strlen($apiKey) . ", 시작 부분: " . substr($apiKey, 0, 10));
                        throw new Exception("서버 설정 오류: 잘못된 API 키 형식 [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    
                    $postData = json_encode([
                        'model' => 'tts-1',
                        'voice' => 'alloy',
                        'input' => $text,
                        'response_format' => 'mp3',
                        'speed' => 1.0
                    ]);
                    
                    $ch = curl_init('https://api.openai.com/v1/audio/speech');
                    if ($ch === false) {
                        throw new Exception("cURL 초기화 실패 [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST => true,
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer ' . $apiKey,
                            'Content-Type: application/json'
                        ],
                        CURLOPT_POSTFIELDS => $postData,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_CONNECTTIMEOUT => 10
                    ]);
                    
                    $audioData = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);
                    
                    if ($audioData === false) {
                        throw new Exception("네트워크 오류: {$curlError} [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    
                    if ($httpCode !== 200) {
                        $errorResponse = json_decode($audioData, true);
                        $errorMessage = $errorResponse['error']['message'] ?? "Unknown error";
                        if (in_array($httpCode, [429, 500, 502, 503, 504]) && $attempt < $maxRetries) {
                            sleep($retryDelay);
                            $retryDelay *= 2;
                            continue;
                        }
                        throw new Exception("OpenAI API 호출 실패 (HTTP {$httpCode}): {$errorMessage} [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    
                    if (empty($audioData)) {
                        throw new Exception("빈 오디오 데이터 반환됨 [generate_dialog_narration.php:" . __LINE__ . "]");
                    }
                    
                    $uploadResult = uploadAudioFile_local($audioData, $filename, $contentsid, $sectionNum);
                    if (!$uploadResult['success']) {
                        throw new Exception("파일 업로드 실패: " . $uploadResult['error']);
                    }
                    
                    return ['success' => true, 'url' => $uploadResult['url'], 'error' => null];
                    
                } catch (Exception $e) {
                    $errorMsg = $e->getMessage();
                    if ($attempt >= $maxRetries) {
                        return ['success' => false, 'url' => null, 'error' => $errorMsg];
                    }
                    if ($attempt < $maxRetries) {
                        sleep($retryDelay);
                        $retryDelay *= 2;
                    }
                }
            }
            
            return ['success' => false, 'url' => null, 'error' => "최대 재시도 횟수 초과 [generate_dialog_narration.php:" . __LINE__ . "]"];
        }
        
        // ====================================================================
        // TTS 생성 실행 (mynote.php 방식 그대로)
        // ====================================================================
        
        // @ 기호로 구간 분리
        $sections = splitTextBySeparator_local($narrationText);
        $sectionCount = count($sections);
        debug_log("총 {$sectionCount}개 구간으로 분리됨");
        
        // 섹션 개수 검증
        $validation = validateSectionCount_local($sections);
        if (!$validation['valid']) {
            throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] " . $validation['error']);
        }
        
        // 각 구간별로 TTS 생성
        $sectionFiles = [];
        $textSections = [];
        $successCount = 0;
        $failedSections = [];
        $failedDetails = []; // 실패한 섹션의 상세 오류 정보 저장
        
        foreach ($sections as $index => $sectionText) {
            $sectionNum = $index + 1;
            debug_log("구간 {$sectionNum}/{$sectionCount} TTS 생성 중");
            
            try {
                $result = generateTTSForSection_local($sectionText, $interactionId, $sectionNum);
                
                if ($result['success']) {
                    $sectionFiles[] = $result['url'];
                    $textSections[] = $sectionText;
                    $successCount++;
                    debug_log("구간 {$sectionNum} TTS 생성 성공: " . $result['url']);
            } else {
                    $errorMsg = $result['error'] ?? 'Unknown error';
                    debug_log("구간 {$sectionNum} TTS 생성 실패: $errorMsg");
                    $failedSections[] = $sectionNum;
                    $failedDetails[] = "섹션 {$sectionNum}: " . $errorMsg;
                }
            } catch (Exception $e) {
                $errorMsg = $e->getMessage();
                debug_log("구간 {$sectionNum} TTS 생성 예외: $errorMsg");
                $failedSections[] = $sectionNum;
                $failedDetails[] = "섹션 {$sectionNum}: " . $errorMsg;
            }
        }
        
        // 최소 1개 이상 성공해야 함
        if ($successCount === 0) {
            $errorMessage = "[generate_dialog_narration.php:" . __LINE__ . "] 모든 섹션 생성 실패: {$sectionCount}개 섹션 중 성공한 섹션이 없습니다.";
            if (!empty($failedDetails)) {
                $errorMessage .= "\n\n실패 상세 (최대 5개):\n" . implode("\n", array_slice($failedDetails, 0, 5));
                if (count($failedDetails) > 5) {
                    $errorMessage .= "\n... 외 " . (count($failedDetails) - 5) . "개 섹션 실패";
                }
            }
            throw new Exception($errorMessage);
        }
         
        debug_log("TTS 생성 완료: {$successCount}/{$sectionCount} 섹션 성공");
        
        // audio_url 필드에 배열 형식으로 저장 (mynote.php 방식)
        $audioUrlArray = json_encode($sectionFiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // audio_url 필드에 배열 형식으로 저장 (예외 처리 및 저장 확인 추가)
        try {
            $DB->execute("UPDATE {ktm_teaching_interactions} SET audio_url = ? WHERE id = ?",
                array($audioUrlArray, $interactionId));
            
            // 저장 성공 확인
            $updatedInteraction = $DB->get_record('ktm_teaching_interactions', array('id' => $interactionId));
            if ($updatedInteraction && $updatedInteraction->audio_url === $audioUrlArray) {
                debug_log("구간 정보 DB 저장 완료 (audio_url 배열 형식)");
                debug_log("저장된 audio_url 길이: " . strlen($audioUrlArray) . " bytes");
                debug_log("저장된 audio_url (처음 200자): " . substr($audioUrlArray, 0, 200));
            } else {
                $savedLength = $updatedInteraction ? strlen($updatedInteraction->audio_url ?? '') : 0;
                $expectedLength = strlen($audioUrlArray);
                throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] audio_url 저장 확인 실패. 예상 길이: {$expectedLength}, 저장된 길이: {$savedLength}");
            }
        } catch (Exception $e) {
            debug_log("audio_url 저장 실패: " . $e->getMessage());
            throw new Exception("[generate_dialog_narration.php:" . __LINE__ . "] audio_url 저장 중 오류 발생: " . $e->getMessage());
        }
        
        $response['listeningTest'] = true;
        $response['sectionCount'] = $sectionCount;
        $response['sectionFiles'] = $sectionFiles;
        $response['audioUrl'] = $audioUrlArray; // 배열 형식
        $response['successCount'] = $successCount;
        $response['failedSections'] = $failedSections;
        
        if (!empty($failedSections)) {
            $response['warning'] = '일부 섹션 실패: ' . implode(', ', $failedSections);
        }
        
                } else {
        // @ 기호가 없거나 TTS 생성이 비활성화된 경우
        debug_log("@ 기호가 없거나 TTS 생성이 비활성화됨");
        $response['listeningTest'] = false;
        $response['sectionCount'] = 0;
        $response['sectionFiles'] = [];
    }

    // 성공 응답
    $response['success'] = true;
    $response['narrationText'] = $narrationText;
    $response['message'] = '대화형 나레이션과 음성이 성공적으로 생성되었습니다.';
    
    // 일반 모드일 때도 audioUrl 포함
    if (!isset($response['audioUrl']) && isset($audioUrl)) {
        $response['audioUrl'] = $audioUrl;
    }
    
    // 듣기평가 모드가 아닐 때 기본값 설정
    if (!isset($response['listeningTest'])) {
        $response['listeningTest'] = false;
        $response['sectionCount'] = 0;
        $response['sectionFiles'] = [];
    }

} catch (Exception $e) {
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    $errorMessage = $e->getMessage();
    $fullErrorMessage = "[{$errorFile}:{$errorLine}] {$errorMessage}";
    
    debug_log("오류 발생: " . $fullErrorMessage);
    $response['success'] = false;
    $response['message'] = $fullErrorMessage;
    $response['errorDetails'] = [
        'file' => $errorFile,
        'line' => $errorLine,
        'message' => $errorMessage
    ];
}

// 출력 버퍼 정리
ob_clean();

// JSON 응답 출력
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>


