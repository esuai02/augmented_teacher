<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

// 에러 로깅 활성화
error_reporting(E_ALL);
ini_set('display_errors', 1);

$timecreated=time(); 
require_login(); 

// POST 요청 처리 (JSON 저장 기능)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_json_memo') {
    
    $response = array('success' => false);
    
    try {
        // JSON 데이터 파싱
        $json_data = isset($_POST['json_data']) ? trim($_POST['json_data']) : '';
        
        if (empty($json_data)) {
            throw new Exception('JSON 데이터가 비어있습니다.');
        }
        
        $json_parsed = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('유효하지 않은 JSON 형식입니다: ' . json_last_error_msg());
        }
        
        // JSON에서 필요한 값들 추출
        $useraddcourse = isset($json_parsed['useraddcourse']) ? trim($json_parsed['useraddcourse']) : '';
        $usermathlevel = isset($json_parsed['usermathlevel']) ? trim($json_parsed['usermathlevel']) : '';
        $userprogresstype = isset($json_parsed['userprogresstype']) ? trim($json_parsed['userprogresstype']) : '';
        $memo_content = isset($json_parsed['content']) ? trim($json_parsed['content']) : '';
        $memo_type = isset($json_parsed['type']) ? trim($json_parsed['type']) : 'mystudy';
        
        if (empty($useraddcourse) || empty($usermathlevel) || empty($userprogresstype) || empty($memo_content)) {
            throw new Exception('필수 데이터가 누락되었습니다. (useraddcourse, usermathlevel, userprogresstype, content)');
        }
        
        // 허용된 타입 검증
        $allowed_types = array('timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today');
        if (!in_array($memo_type, $allowed_types)) {
            throw new Exception('유효하지 않은 메모 타입입니다.');
        }
        
        $teacherid = isset($_POST['teacherid']) ? intval($_POST['teacherid']) : 0;
        
        if ($teacherid <= 0) {
            throw new Exception('유효하지 않은 교사 ID입니다.');
        }
        
        // 교사 정보 가져오기
        $collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid'");
        if (!$collegues) {
            throw new Exception('교사 설정 정보를 찾을 수 없습니다.');
        }
        
        $teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79'");
        $tsymbol = $teacher ? $teacher->symbol : '';
        
        $teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79'");
        $tsymbol1 = $teacher1 ? $teacher1->symbol : '';
        
        $teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79'");
        $tsymbol2 = $teacher2 ? $teacher2->symbol : '';
        
        $teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79'");
        $tsymbol3 = $teacher3 ? $teacher3->symbol : '';
        
        // 학생 목록 가져오기 (기본값 설정)
        $academy = $collegues->academy ?? '';
        $amonthago6 = time() - (6 * 30 * 24 * 60 * 60); // 6개월 전
        
        $mystudents = $DB->get_records_sql("SELECT * FROM mdl_user WHERE institution LIKE '%$academy%' AND lastaccess > '$amonthago6' AND (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%' OR firstname LIKE '%$tsymbol2%' OR firstname LIKE '%$tsymbol3%') ORDER BY id DESC");
        
        $processed_students = 0;
        $saved_memos = 0;
        
        // 데이터베이스 테이블 존재 여부 확인
        $table_exists = $DB->get_manager()->table_exists('abessi_stickynotes');
        if (!$table_exists) {
            throw new Exception('데이터베이스 테이블이 존재하지 않습니다.');
        }
        
        foreach ($mystudents as $user) {
            $userid = $user->id;
            $processed_students++;
            
            // 학생 정보 가져오기
            $addcourse = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='83'"); // 기본코스
            $mathlevel = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='114'"); // 학습수준
            $classtype = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='115'"); // 보충과정
            $progresstype = $DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$userid' AND fieldid='116'"); // 진도
            
            // 조건 검사
            $addcourse_data = $addcourse ? $addcourse->data : '';
            $mathlevel_data = $mathlevel ? $mathlevel->data : '';
            $classtype_data = $classtype ? $classtype->data : '';
            $progresstype_data = $progresstype ? $progresstype->data : '';
            
            if (($addcourse_data == $useraddcourse || $classtype_data === $useraddcourse) && 
                ($mathlevel_data == $usermathlevel) && 
                ($progresstype_data == $userprogresstype)) {
                
                // savememo.php 방식으로 메모 저장
                $current_time = time();
                
                // 새로운 메모 생성
                $newmemo = new stdClass();
                $newmemo->userid = $userid;  // NOT NULL 필드
                $newmemo->authorid = $teacherid;   // 교사 ID
                $newmemo->type = $memo_type;
                $newmemo->content = $memo_content;
                $newmemo->created_at = $current_time;
                $newmemo->updated_at = $current_time;
                $newmemo->color = 'yellow'; // 기본 색상
                $newmemo->hide = 0; // 기본값
                
                try {
                    $newid = $DB->insert_record('abessi_stickynotes', $newmemo);
                    if ($newid) {
                        $saved_memos++;
                        error_log("메모 저장 성공 - 학생 ID: $userid, 메모 ID: $newid");
                    }
                } catch (Exception $insert_error) {
                    error_log("메모 저장 실패 - 학생 ID: $userid, 오류: " . $insert_error->getMessage());
                }
            }
        }
        
        $response['success'] = true;
        $response['message'] = "처리 완료: {$processed_students}명의 학생 중 {$saved_memos}개의 메모가 저장되었습니다.";
        $response['processed_students'] = $processed_students;
        $response['saved_memos'] = $saved_memos;
        
    } catch (Exception $e) {
        $response['success'] = false;
        $response['error'] = $e->getMessage();
        error_log("applymeetingresults.php 에러: " . $e->getMessage());
    }
    
    // JSON 응답 전송
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// GET 요청 처리 (기존 로직)
$teacherid = isset($_GET['userid']) ? intval($_GET['userid']) : 0;

if ($teacherid > 0) {
    $collegues = $DB->get_record_sql("SELECT * FROM mdl_abessi_teacher_setting WHERE userid='$teacherid'"); 
    $teacher = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$teacherid' AND fieldid='79'"); 
    $tsymbol = $teacher ? $teacher->symbol : '';
    
    if ($collegues) {
        $teacher1 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr1' AND fieldid='79'"); 
        $tsymbol1 = $teacher1 ? $teacher1->symbol : '';
        $teacher2 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr2' AND fieldid='79'"); 
        $tsymbol2 = $teacher2 ? $teacher2->symbol : '';
        $teacher3 = $DB->get_record_sql("SELECT data AS symbol FROM mdl_user_info_data where userid='$collegues->mntr3' AND fieldid='79'"); 
        $tsymbol3 = $teacher3 ? $teacher3->symbol : '';  
        
        $username = $DB->get_record_sql("SELECT lastname, firstname FROM mdl_user WHERE id='$teacherid'"); 
        
        // 기본값 설정
        $academy = $collegues->academy ?? '';
        $amonthago6 = time() - (6 * 30 * 24 * 60 * 60); // 6개월 전
        
        $mystudents = $DB->get_records_sql("SELECT * FROM mdl_user WHERE institution LIKE '%$academy%' AND lastaccess > '$amonthago6' AND (firstname LIKE '%$tsymbol%' OR firstname LIKE '%$tsymbol1%' OR firstname LIKE '%$tsymbol2%' OR firstname LIKE '%$tsymbol3%') ORDER BY id DESC");
    }
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>회의 결과 적용</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        textarea {
            width: 100%;
            min-height: 200px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-family: monospace;
            font-size: 14px;
            resize: vertical;
        }
        .btn {
            background-color: #007bff;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
        .message {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .json-example {
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 4px;
            padding: 15px;
            margin: 15px 0;
        }
        .json-example pre {
            margin: 0;
            font-family: monospace;
            font-size: 13px;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 20px 0;
        }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: 0 auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>회의 결과 적용 시스템</h1>
        
        <div class="info message" style="display: block;">
            <strong>사용 방법:</strong>
            <p>아래 JSON 형식으로 데이터를 입력하여 조건에 맞는 학생들에게 메모를 저장할 수 있습니다.</p>
        </div>
        
        <div class="json-example">
            <strong>JSON 형식 예제:</strong>
            <pre>{
  "useraddcourse": "수학1",
  "usermathlevel": "중급",
  "userprogresstype": "정규과정",
  "content": "회의 결과에 따른 학습 안내 메시지입니다.",
  "type": "mystudy"
}</pre>
            <small>
                <strong>필수 필드:</strong> useraddcourse, usermathlevel, userprogresstype, content<br>
                <strong>선택 필드:</strong> type (기본값: mystudy)<br>
                <strong>허용 타입:</strong> timescaffolding, chapter, edittoday, mystudy, today
            </small>
        </div>
        
        <form id="jsonForm">
            <div class="form-group">
                <label for="json_data">JSON 데이터 입력:</label>
                <textarea id="json_data" name="json_data" placeholder="위의 예제 형식으로 JSON 데이터를 입력하세요..."></textarea>
            </div>
            
            <input type="hidden" id="teacherid" name="teacherid" value="<?php echo htmlspecialchars($teacherid); ?>">
            <input type="hidden" name="action" value="save_json_memo">
            
            <button type="submit" class="btn" id="saveBtn">저장하기</button>
            <button type="button" class="btn" onclick="validateJson()" style="background-color: #28a745;">JSON 검증</button>
        </form>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>처리 중...</p>
        </div>
        
        <div id="message" class="message"></div>
        
        <?php if (isset($username) && $username): ?>
        <div class="info message" style="display: block; margin-top: 30px;">
            <strong>현재 교사:</strong> <?php echo htmlspecialchars($username->lastname . ' ' . $username->firstname); ?><br>
            <strong>교사 ID:</strong> <?php echo htmlspecialchars($teacherid); ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showMessage(text, type) {
            const messageDiv = document.getElementById('message');
            messageDiv.textContent = text;
            messageDiv.className = 'message ' + type;
            messageDiv.style.display = 'block';
            
            // 5초 후 메시지 숨기기
            setTimeout(() => {
                messageDiv.style.display = 'none';
            }, 5000);
        }
        
        function validateJson() {
            const jsonData = document.getElementById('json_data').value.trim();
            
            if (!jsonData) {
                showMessage('JSON 데이터를 입력해주세요.', 'error');
                return false;
            }
            
            try {
                const parsed = JSON.parse(jsonData);
                
                // 필수 필드 검증
                const requiredFields = ['useraddcourse', 'usermathlevel', 'userprogresstype', 'content'];
                const missingFields = requiredFields.filter(field => !parsed[field] || parsed[field].trim() === '');
                
                if (missingFields.length > 0) {
                    showMessage('누락된 필수 필드: ' + missingFields.join(', '), 'error');
                    return false;
                }
                
                // 타입 검증
                const allowedTypes = ['timescaffolding', 'chapter', 'edittoday', 'mystudy', 'today'];
                if (parsed.type && !allowedTypes.includes(parsed.type)) {
                    showMessage('유효하지 않은 메모 타입입니다. 허용 타입: ' + allowedTypes.join(', '), 'error');
                    return false;
                }
                
                showMessage('JSON 형식이 올바릅니다!', 'success');
                return true;
                
            } catch (e) {
                showMessage('유효하지 않은 JSON 형식입니다: ' + e.message, 'error');
                return false;
            }
        }
        
        document.getElementById('jsonForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!validateJson()) {
                return;
            }
            
            const teacherid = document.getElementById('teacherid').value;
            if (!teacherid || teacherid === '0') {
                showMessage('유효하지 않은 교사 ID입니다. URL에 userid 파라미터를 확인해주세요.', 'error');
                return;
            }
            
            // 로딩 표시
            document.getElementById('loading').style.display = 'block';
            document.getElementById('saveBtn').disabled = true;
            
            // FormData 생성
            const formData = new FormData(this);
            
            // AJAX 요청
            fetch(window.location.pathname + '?userid=' + teacherid, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('saveBtn').disabled = false;
                
                if (data.success) {
                    showMessage(data.message, 'success');
                    // 성공 시 폼 초기화
                    document.getElementById('json_data').value = '';
                } else {
                    showMessage('오류: ' + (data.error || '알 수 없는 오류가 발생했습니다.'), 'error');
                }
            })
            .catch(error => {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('saveBtn').disabled = false;
                showMessage('서버 통신 오류: ' + error.message, 'error');
            });
        });
        
        // 페이지 로드 시 교사 ID 확인
        document.addEventListener('DOMContentLoaded', function() {
            const teacherid = document.getElementById('teacherid').value;
            if (!teacherid || teacherid === '0') {
                showMessage('교사 ID가 설정되지 않았습니다. URL에 ?userid=교사ID 를 추가해주세요.', 'error');
            }
        });
    </script>
</body>
</html> 