<?php
require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// 에러 출력 방지
error_reporting(0);
ini_set('display_errors', 0);

// JSON 응답을 보장하기 위해 헤더 먼저 설정
header('Content-Type: application/json; charset=utf-8');

// 로그인 체크
if (!isloggedin()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$action = $_POST['action'] ?? '';

// 디버깅용: 모든 POST 데이터 확인
error_log("patternbank_ajax.php - All POST data: " . json_encode($_POST));

if ($action === 'save_problem') {
    try {
        // 필수 필드 검증
        if (!isset($_POST['cntid']) || !isset($_POST['cnttype']) || !isset($_POST['question']) || !isset($_POST['solution'])) {
            throw new Exception('Required fields missing');
        }
        
        // 디버깅 정보
        error_log("Save problem - POST data: " . json_encode($_POST));
        error_log("Type field received: " . (isset($_POST['type']) ? $_POST['type'] : 'NOT SET'));
        
        $problem = new stdClass();  
        $problem->authorid = $USER->id;   
        $problem->cntid = $_POST['cntid'];   
        $problem->cnttype = $_POST['cnttype'];     
        $problem->question = $_POST['question']; 
        $problem->solution = $_POST['solution'];
        // choices가 있으면 사용, 없으면 inputanswer 사용
        if (isset($_POST['choices'])) {
            $problem->inputanswer = $_POST['choices'];
        } else {
            $problem->inputanswer = $_POST['inputanswer'] ?? null;
        }
        $problem->type = $_POST['type'] ?? 'similar';  // 기본값은 'similar'
        $problem->timecreated = time(); 
        $problem->timemodified = time();
        
        // NULL 값들 
        $problem->qstnimgurl = null; 
        $problem->solimgurl = null;
        $problem->fullqstnimgurl = null;
        $problem->fullsolimgurl = null;
          

        // 디버깅용 로그 
        error_log("Problem object: " . json_encode($problem)); 
        error_log("Type value: " . $problem->type);
        
        // 테이블 구조 확인
        $columns = $DB->get_columns('abessi_patternbank');
        $has_type_field = false;
        foreach ($columns as $column) {
            if ($column->name === 'type') {
                $has_type_field = true;
                error_log("Type field found in table - Type: " . $column->type . ", Max length: " . $column->max_length);
                break;
            }
        }
        if (!$has_type_field) {
            error_log("WARNING: type field not found in abessi_patternbank table!");
        }
        
        $id = $DB->insert_record('abessi_patternbank', $problem);
        
        error_log("Inserted ID: " . $id);
        
        // 삽입된 데이터 확인
        $inserted = $DB->get_record('abessi_patternbank', ['id' => $id]);
        error_log("Inserted record type: " . (isset($inserted->type) ? $inserted->type : 'NULL'));
        
        echo json_encode(['success' => true, 'id' => $id, 'message' => 'Problem saved successfully', 'type_saved' => $problem->type, 'type_in_db' => isset($inserted->type) ? $inserted->type : 'NULL']);
    } catch (Exception $e) {
        error_log("Save problem error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
    }
    exit; 
}
 
if ($action === 'get_problem') {
    try {
        $id = $_POST['id'];
        $problem = $DB->get_record('abessi_patternbank', ['id' => $id]);
        
        if ($problem) {
            echo json_encode([
                'id' => $problem->id,
                'question' => $problem->question,
                'solution' => $problem->solution,
                'inputanswer' => $problem->inputanswer,
                'qstnimgurl' => $problem->qstnimgurl,
                'solimgurl' => $problem->solimgurl,
                'cntid' => $problem->cntid,
                'cnttype' => $problem->cnttype,
                'type' => $problem->type ?? 'similar'  
                
            ]);
        } else {
            echo json_encode(['error' => 'Problem not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'load_problems') {
    try {
        $cntid = $_POST['cntid'];
        $cnttype = $_POST['cnttype'];
        $problems = $DB->get_records('abessi_patternbank', ['cntid' => $cntid, 'cnttype' => $cnttype]);
        
        $result = [];
        foreach ($problems as $problem) {
            $result[] = [
                'id' => $problem->id,
                'question' => $problem->question,
                'solution' => $problem->solution,
                'inputanswer' => $problem->inputanswer,
                'type' => $problem->type ?? 'similar'
            ];
        }
        
        echo json_encode(['success' => true, 'problems' => $result]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'test') {
    echo json_encode(['success' => true, 'message' => 'Server connection test successful']);
    exit;
}

if ($action === 'update_problem') {
    try {
        if (!isset($_POST['id'])) {
            throw new Exception('Problem ID missing');
        }
        
        $id = $_POST['id'];
        error_log("Update problem - ID: $id");
        
        // 기존 레코드 가져오기
        $problem = $DB->get_record('abessi_patternbank', ['id' => $id]);
        
        if (!$problem) {
            throw new Exception('Problem not found with ID: ' . $id);
        }
        
        // 작성자 권한 확인
        if ($problem->authorid != $USER->id && !is_siteadmin()) {
            throw new Exception('Permission denied: You can only edit your own problems');
        }
        
        // 업데이트할 필드만 설정
        $updateData = new stdClass();
        $updateData->id = $id;
        
        if (isset($_POST['question'])) {
            $updateData->question = $_POST['question'];
        }
        
        if (isset($_POST['solution'])) {
            $updateData->solution = $_POST['solution'];
        }
        
        // choices가 있으면 업데이트
        if (isset($_POST['choices'])) {
            $updateData->inputanswer = $_POST['choices'];
        }
        
        $updateData->timemodified = time();
        
        error_log("Update data: " . json_encode($updateData));
        
        // 데이터베이스 업데이트
        try {
            $success = $DB->update_record('abessi_patternbank', $updateData);
            
            if ($success) {
                error_log("Problem updated successfully");
                echo json_encode(['success' => true, 'message' => 'Problem updated successfully']);
            } else {
                throw new Exception('Database update failed');
            }
        } catch (dml_exception $e) {
            error_log("Database error: " . $e->getMessage());
            throw new Exception('데이터베이스 쓰기 오류: ' . $e->getMessage());
        }
    } catch (Exception $e) {
        error_log("Update problem error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'check_table') {
    try {
        $columns = $DB->get_columns('abessi_patternbank');
        $column_names = [];
        $type_field_info = null;
        
        foreach ($columns as $column) {
            $column_names[] = $column->name;
            if ($column->name === 'type') {
                $type_field_info = [
                    'name' => $column->name,
                    'type' => $column->type,
                    'max_length' => $column->max_length,
                    'not_null' => $column->not_null,
                    'default' => $column->has_default ? $column->default_value : null
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'columns' => $column_names,
            'type_field' => $type_field_info,
            'has_type_field' => !is_null($type_field_info)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'save_analysis') {
    try {
        if (!isset($_POST['cntid']) || !isset($_POST['analysis'])) {
            throw new Exception('Required parameters missing');
        }
        
        $cntid = $_POST['cntid'];
        $analysis = $_POST['analysis'];
        
        error_log("Save analysis - cntid: $cntid, text length: " . strlen($analysis));
        
        // mdl_icontent_pages 테이블 확인
        $page = $DB->get_record('icontent_pages', ['id' => $cntid]);
        
        if (!$page) {
            error_log("Page not found with id: $cntid");
            throw new Exception('Page not found with id: ' . $cntid);
        }
        
        // analysis 필드가 없으면 추가
        $columns = $DB->get_columns('icontent_pages');
        $has_analysis_field = false;
        foreach ($columns as $column) {
            if ($column->name === 'analysis') {
                $has_analysis_field = true;
                break;
            }
        }
        
        if (!$has_analysis_field) {
            error_log("WARNING: analysis field not found in icontent_pages table!");
            // 필드가 없는 경우 reflections0 필드에 저장 (임시)
            $page->reflections0 = $analysis;
        } else {
            $page->analysis = $analysis;
        }
        
        $page->timemodified = time();
        
        $success = $DB->update_record('icontent_pages', $page);
        
        if ($success) {
            error_log("Analysis saved successfully");
            echo json_encode(['success' => true, 'message' => 'Analysis saved successfully']);
        } else {
            throw new Exception('Failed to save analysis');
        }
    } catch (Exception $e) {
        error_log("Save analysis error: " . $e->getMessage());
        echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'generate_similar') {
    // OpenAI 설정 파일 로드 (상대 경로 조정)
    $configPath = __DIR__ . '/../alt42/patternbank/config/openai_config.php';
    if (file_exists($configPath)) {
        require_once($configPath);
    } else {
        // 대체 경로 시도
        $configPath = __DIR__ . '/../alt42k/patternbank/config/openai_config.php';
        if (file_exists($configPath)) {
            require_once($configPath);
        } else {
            error_log("PatternBank: OpenAI config file not found");
            echo json_encode([
                'success' => false,
                'error' => 'OpenAI configuration file not found',
                'message' => 'OpenAI 설정 파일을 찾을 수 없습니다.'
            ]);
            exit;
        }
    }
    
    try {
        // 필수 파라미터 확인
        if (!isset($_POST['cntid']) || !isset($_POST['cnttype'])) {
            throw new Exception('Required parameters missing: cntid and cnttype are required');
        }
        
        $cntid = $_POST['cntid'];
        $cnttype = $_POST['cnttype'];
        $problemType = $_POST['problemType'] ?? 'similar';
        $imageUrl = $_POST['imageUrl'] ?? '';
        $analysisText = $_POST['analysisText'] ?? '';
        
        error_log("PatternBank: Generating similar problems - cntid: $cntid, type: $problemType");
        
        // 유형 분석 텍스트 가져오기 (POST로 전달되지 않았으면 DB에서 조회)
        if (empty($analysisText) && $cnttype == 1) {
            $page = $DB->get_record('icontent_pages', ['id' => $cntid]);
            if ($page) {
                // analysis 필드 확인
                $columns = $DB->get_columns('icontent_pages');
                $has_analysis_field = false;
                foreach ($columns as $column) {
                    if ($column->name === 'analysis') {
                        $has_analysis_field = true;
                        break;
                    }
                }
                if ($has_analysis_field && !empty($page->analysis)) {
                    $analysisText = $page->analysis;
                } elseif (!empty($page->reflections0)) {
                    // analysis 필드가 없으면 reflections0 사용
                    $analysisText = $page->reflections0;
                }
            }
        }
        
        // 원본 문제 정보 조회 (가장 최근 문제를 참고용으로 사용)
        $recentProblem = $DB->get_record_sql(
            "SELECT question, solution, inputanswer 
             FROM {abessi_patternbank} 
             WHERE cntid = ? AND cnttype = ? 
             ORDER BY id DESC 
             LIMIT 1",
            [$cntid, $cnttype]
        );
        
        // 원본 문제 구성
        $originalProblem = [];
        if ($recentProblem) {
            $originalProblem = [
                'question' => $recentProblem->question,
                'solution' => $recentProblem->solution
            ];
            
            if (!empty($recentProblem->inputanswer)) {
                $originalProblem['choices'] = json_decode($recentProblem->inputanswer, true);
            }
        }
        
        // 이미지 URL이 제공된 경우 이미지 기반 생성
        if (!empty($imageUrl)) {
            $originalProblem['imageUrl'] = $imageUrl;
            error_log("PatternBank: Image URL received: " . substr($imageUrl, 0, 200));
        } else {
            error_log("PatternBank: No image URL provided");
        }
        
        // 유형 분석 텍스트 추가
        if (!empty($analysisText)) {
            $originalProblem['analysis'] = strip_tags($analysisText); // HTML 태그 제거
            error_log("PatternBank: Analysis text included: " . substr($analysisText, 0, 100));
        } else {
            error_log("PatternBank: No analysis text provided");
        }
        
        // 원본 문제가 없고 이미지도 없으면 유형 분석만으로 생성
        // 기본 템플릿을 사용하지 않고 유형 분석 정보만으로 생성하도록 함
        if (empty($originalProblem['question']) && empty($imageUrl)) {
            error_log("PatternBank: No original problem or image - will use analysis text only");
            // 원본 문제 필드를 비워두면 프롬프트에서 유형 분석만 사용
        }
        
        // OpenAI API를 통한 유사문제 생성
        error_log('PatternBank: Calling generateSimilarProblems function');
        
        // generateSimilarProblems 함수가 있는지 확인
        if (!function_exists('generateSimilarProblems')) {
            throw new Exception('generateSimilarProblems function not found. Please check OpenAI config file.');
        }
        
        $result = generateSimilarProblems($originalProblem, $problemType);
        
        if (!$result['success']) {
            // 토큰 오류인 경우 상세 정보 포함
            $errorMsg = $result['error'] ?? 'Failed to generate problems';
            if (isset($result['is_token_error']) && $result['is_token_error']) {
                $errorMsg = $result['error'];
                if (isset($result['max_tokens'])) {
                    $errorMsg .= " (현재 Max Tokens: {$result['max_tokens']})";
                }
            }
            throw new Exception($errorMsg);
        }
        
        // 생성된 문제들을 DB에 저장
        $savedProblems = [];
        $errors = [];
        
        foreach ($result['problems'] as $index => $problem) {
            try {
                $problemRecord = new stdClass();
                $problemRecord->authorid = $USER->id;
                $problemRecord->cntid = $cntid;
                $problemRecord->cnttype = $cnttype;
                $problemRecord->question = $problem['question'];
                $problemRecord->solution = $problem['solution'];
                
                // 선택지가 있으면 JSON 문자열로 저장
                if (!empty($problem['choices'])) {
                    $problemRecord->inputanswer = json_encode($problem['choices'], JSON_UNESCAPED_UNICODE);
                } else {
                    $problemRecord->inputanswer = null;
                }
                
                $problemRecord->type = $problemType; // similar or modified
                $problemRecord->timecreated = time();
                $problemRecord->timemodified = time();
                
                // NULL 값들
                $problemRecord->qstnimgurl = null;
                $problemRecord->solimgurl = null;
                $problemRecord->fullqstnimgurl = null;
                $problemRecord->fullsolimgurl = null;
                
                // DB에 저장
                $id = $DB->insert_record('abessi_patternbank', $problemRecord);
                
                if ($id) {
                    $savedProblems[] = [
                        'id' => $id,
                        'number' => $index + 1,
                        'question' => $problem['question'],
                        'solution' => $problem['solution'],
                        'choices' => $problem['choices'] ?? [],
                        'type' => $problemType
                    ];
                    error_log("PatternBank: Problem " . ($index + 1) . " saved with ID: " . $id);
                } else {
                    $errors[] = "문제 " . ($index + 1) . " 저장 실패";
                    error_log("PatternBank: Failed to save problem " . ($index + 1));
                }
                
            } catch (Exception $e) {
                $errors[] = "문제 " . ($index + 1) . " 저장 오류: " . $e->getMessage();
                error_log("PatternBank: Error saving problem " . ($index + 1) . ": " . $e->getMessage());
            }
        }
        
        // 응답 생성
        $response = [
            'success' => count($savedProblems) > 0,
            'problems' => $savedProblems,
            'totalGenerated' => count($result['problems']),
            'totalSaved' => count($savedProblems),
            'usage' => $result['usage'] ?? null
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['error'] = implode(', ', $errors);
            $response['error_type'] = 'database_error';
            $response['error_details'] = [
                'type' => 'database_error',
                'description' => '데이터베이스 저장 중 오류가 발생했습니다.',
                'errors' => $errors
            ];
        }
        
        // 성공 메시지
        if (count($savedProblems) > 0) {
            $response['message'] = count($savedProblems) . "개의 " . 
                ($problemType === 'similar' ? '유사문제' : '변형문제') . 
                "가 성공적으로 생성되었습니다.";
        } else {
            $response['message'] = "문제 생성에 실패했습니다.";
            if (!isset($response['error'])) {
                $response['error'] = "생성된 문제를 데이터베이스에 저장하는 중 오류가 발생했습니다.";
                $response['error_type'] = 'database_error';
            }
        }
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        error_log("PatternBank generate_similar error: " . $e->getMessage());
        error_log("PatternBank generate_similar trace: " . $e->getTraceAsString());
        
        // 오류 메시지에서 오류 타입 추론
        $errorType = 'unknown_error';
        $errorDetails = [
            'type' => 'exception',
            'message' => $e->getMessage(),
            'description' => '예외가 발생했습니다.'
        ];
        
        $errorMsg = $e->getMessage();
        if (stripos($errorMsg, 'token') !== false) {
            $errorType = 'token_error';
            $errorDetails['description'] = '토큰 관련 오류가 발생했습니다.';
        } elseif (stripos($errorMsg, 'network') !== false || stripos($errorMsg, 'CURL') !== false) {
            $errorType = 'network_error';
            $errorDetails['description'] = '네트워크 연결 오류가 발생했습니다.';
        } elseif (stripos($errorMsg, 'database') !== false || stripos($errorMsg, 'DB') !== false) {
            $errorType = 'database_error';
            $errorDetails['description'] = '데이터베이스 오류가 발생했습니다.';
        } elseif (stripos($errorMsg, 'parse') !== false || stripos($errorMsg, 'JSON') !== false) {
            $errorType = 'parsing_error';
            $errorDetails['description'] = '응답 파싱 오류가 발생했습니다.';
        }
        
        echo json_encode([
            'success' => false,
            'error' => $errorMsg,
            'error_type' => $errorType,
            'error_code' => 'EXCEPTION',
            'error_details' => $errorDetails,
            'message' => '유사문제 생성 중 오류가 발생했습니다: ' . $errorMsg
        ]);
    }
    exit;
}

// 잘못된 액션
error_log("Invalid action received: " . $action);
echo json_encode(['success' => false, 'error' => 'Invalid action: ' . $action]);
?>