<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER, $CFG;
//require_login();

// API 키를 $CFG에서 가져오기
$secret_key = isset($CFG->openai_api_key) ? $CFG->openai_api_key : '';
if (empty($secret_key)) {
    error_log('[capturemathquestion.php] File: ' . basename(__FILE__) . ', Line: ' . __LINE__ . ', Error: API 키가 설정되지 않았습니다.');
}

$userid = $_GET['userid'] ;
$studentid = $userid; // userid를 studentid로 사용
 
$userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); 
$role=$userrole->data;
// 학생 정보 가져오기
$student = $DB->get_record('user', array('id' => $studentid));
if (!$student) {
    print_error('학생 정보를 찾을 수 없습니다.');
}

// role이 student가 아니면 다른 사용자의 정보에도 접근 가능
if ($USER->id != $studentid && $role === 'student') {
    print_error('다른 사용자의 정보에 접근하실 수 없습니다.');
}

$teacher = $DB->get_record_sql("SELECT teacherid FROM mdl_user where id=? ORDER BY id DESC LIMIT 1", array($studentid)); 
$teacherid = $teacher ? $teacher->teacherid : 0;

?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>📤 풀이 요청하기</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../../assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- Fonts and icons -->
    <script src="../../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Open+Sans:300,400,600,700"]},
            custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands"], urls: ['../../assets/css/fonts.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/assets/css/azzara.min.css">
    <link rel="stylesheet" href="/moodle/local/augmented_teacher/assets/css/demo.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Open Sans', sans-serif;
            margin: 0;
            padding: 0;
        }
        
        .content-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 0;
            width: 100%;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 20px 15px;
            text-align: center;
        }

        .header h1 {
            font-size: 22px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
            margin: 0;
        }

        .dashboard {
            padding: 20px 15px;
        }

        /* 풀이 요청 영역 */
        .request-section {
            margin-bottom: 0;
            background: white;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
        }

        .request-section h2 {
            margin-bottom: 20px;
            color: #2d3748;
            font-size: 18px;
            font-weight: bold;
            padding: 0 5px;
        }

        /* 업로드 영역 */
        #uploadArea {
            width: 100%;
            min-height: 200px;
            border: 3px dashed #e2e8f0;
            border-radius: 12px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: #f8fafc;
            transition: all 0.3s;
            position: relative;
            touch-action: manipulation;
        }

        #uploadArea:active {
            background: #edf2f7;
            border-color: #4299e1;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 8px;
        }

        .upload-text {
            color: #718096;
            text-align: center;
            padding: 0 10px;
        }

        .upload-text p {
            font-size: 14px;
            margin-bottom: 4px;
        }

        .upload-text p:last-child {
            font-size: 12px;
        }

        #imagePreview {
            display: none;
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin: 10px;
        }

        /* 폼 요소 스타일 */
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #2d3748;
            font-size: 14px;
        }

        select, input[type="text"] {
            width: 100%;
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            font-size: 16px; /* iOS 줌 방지 */
            -webkit-appearance: none;
            appearance: none;
            box-sizing: border-box;
        }

        select:focus, input[type="text"]:focus {
            outline: none;
            border-color: #4299e1;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (min-width: 768px) {
            .content-container {
                padding: 20px;
            }
            
            .container {
                border-radius: 20px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                margin-top: 20px;
                margin-bottom: 20px;
                min-height: auto;
            }
            
            .header {
                padding: 30px;
                border-radius: 20px 20px 0 0;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .header p {
                font-size: 16px;
            }
            
            .dashboard {
                padding: 30px;
            }
            
            .request-section {
                padding: 30px;
                border-radius: 12px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            }
            
            .form-grid {
                grid-template-columns: 1fr 2fr;
                gap: 20px;
            }
            
            #uploadArea {
                min-height: 250px;
            }
            
            .upload-icon {
                font-size: 60px;
            }
            
            .upload-text p {
                font-size: 16px;
            }
            
            .upload-text p:last-child {
                font-size: 14px;
            }
            
            #imagePreview {
                max-height: 400px;
            }
        }

        /* 버튼 스타일 */
        .btn {
            padding: 14px 24px;
            font-size: 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            min-height: 48px; /* 터치 타겟 최소 크기 */
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:active:not(:disabled) {
            background: #c0392b;
            transform: scale(0.98);
        }

        .btn-primary:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .btn-secondary {
            background: #edf2f7;
            color: #4a5568;
        }

        .btn-secondary:active {
            background: #e2e8f0;
            transform: scale(0.98);
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }
        
        .button-group .btn {
            width: 100%;
        }

        @media (min-width: 768px) {
            .button-group {
                flex-direction: row;
                justify-content: flex-end;
            }
            
            .button-group .btn {
                width: auto;
            }
            
            .btn-primary:hover:not(:disabled) {
                background: #c0392b;
            }
            
            .btn-secondary:hover {
                background: #e2e8f0;
            }
        }

        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
    <div class="content-container">
        <div class="container">
            <div class="header">
                <h1 style="position: relative; display: inline-flex; align-items: center; gap: 10px;">
                    <span>📤</span>
                    풀이 요청하기
                    <button id="copy-url-btn" type="button" style="background: none; border: none; cursor: pointer; padding: 0.2rem 0.5rem; margin-left: 0.5rem; vertical-align: middle; z-index: 10; position: relative;" title="URL 복사">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: rgba(255,255,255,0.9); pointer-events: none;">
                            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                        </svg>
                    </button>
                </h1>
            </div>

            <div class="dashboard">
                <!-- 풀이 요청 영역 -->
                <div id="requestSection" class="request-section">
                    <form id="requestForm" onsubmit="submitRequest(event)">
                        <div style="margin-bottom: 25px;">
                            <input type="file" id="questionImage" accept="image/*" required>
                            <div id="uploadArea" 
                                 onclick="document.getElementById('questionImage').click()" 
                                 ondragover="event.preventDefault(); this.style.backgroundColor='#edf2f7'; this.style.borderColor='#4299e1';" 
                                 ondragleave="this.style.backgroundColor='#f8fafc'; this.style.borderColor='#e2e8f0';"
                                 ondrop="handleDrop(event)">
                                <div class="upload-icon">📷</div>
                                <div class="upload-text">
                                    <p>지원 형식: JPG, PNG, GIF</p>
                                </div>
                                <img id="imagePreview">
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div>
                                <label>
                                    출처
                                </label>
                                <select id="problemType" required>
                                    <option value="exam">내신 기출</option>
                                    <option value="school">학교 프린트</option>
                                    <option value="mathking">MathKing 문제</option>
                                    <option value="textbook" selected>시중교재</option>
                                </select>
                            </div>
                            
                            <div>
                                <label>
                                    메모
                                </label>
                                <input type="text" id="additionalRequest"
                                       placeholder="내용입력">
                            </div>
                        </div>
                        
                        <div class="button-group">
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                🚀 발송하기
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="hideRequestForm()">
                                취소
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const studentId = <?php echo json_encode($studentid); ?>;
        const teacherId = <?php echo json_encode($teacherid); ?>;

        /**
         * 이미지 압축 함수
         * @param {File} file - 압축할 이미지 파일
         * @param {number} maxWidth - 최대 너비 (기본값: 1200px)
         * @param {number} maxHeight - 최대 높이 (기본값: 1200px)
         * @param {number} quality - JPEG 품질 (0-1, 기본값: 0.85)
         * @returns {Promise<string>} - 압축된 이미지의 base64 데이터 URL
         * 파일 위치: capturemathquestion.php:437
         */
        async function compressImage(file, maxWidth = 1200, maxHeight = 1200, quality = 0.85) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const img = new Image();

                    img.onload = function() {
                        // Canvas 생성
                        const canvas = document.createElement('canvas');
                        const ctx = canvas.getContext('2d');

                        // 비율을 유지하면서 크기 조정
                        let width = img.width;
                        let height = img.height;

                        if (width > height) {
                            if (width > maxWidth) {
                                height *= maxWidth / width;
                                width = maxWidth;
                            }
                        } else {
                            if (height > maxHeight) {
                                width *= maxHeight / height;
                                height = maxHeight;
                            }
                        }

                        canvas.width = width;
                        canvas.height = height;

                        // 이미지 그리기
                        ctx.drawImage(img, 0, 0, width, height);

                        // JPEG로 변환 (압축)
                        const compressedDataUrl = canvas.toDataURL('image/jpeg', quality);

                        // 압축 결과 로그
                        const originalSize = (file.size / 1024 / 1024).toFixed(2);
                        const compressedSize = (compressedDataUrl.length * 0.75 / 1024 / 1024).toFixed(2);
                        console.log(`이미지 압축: ${originalSize}MB → ${compressedSize}MB`);

                        resolve(compressedDataUrl);
                    };

                    img.onerror = function() {
                        reject(new Error('이미지 로드 실패 (파일 위치: capturemathquestion.php:478)'));
                    };

                    img.src = e.target.result;
                };

                reader.onerror = function() {
                    reject(new Error('파일 읽기 실패 (파일 위치: capturemathquestion.php:485)'));
                };

                reader.readAsDataURL(file);
            });
        }

        /**
         * 파일 크기 체크 및 압축 여부 결정
         * @param {File} file - 체크할 파일
         * @returns {boolean} - 압축이 필요한지 여부
         * 파일 위치: capturemathquestion.php:494
         */
        function shouldCompressFile(file) {
            const maxSizeInBytes = 15 * 1024 * 1024; // 15MB
            return file.size > maxSizeInBytes;
        }

        // 페이지 로드 시 폼 표시
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded');
            showRequestForm();
            
            // 폼이 제대로 존재하는지 확인
            const form = document.getElementById('requestForm');
            if (form) {
                console.log('Form found:', form);
            } else {
                console.error('Form not found!');
            }
            
            // 단축 URL 생성 및 클립보드 복사 (한 번에 처리)
            var copyBtn = document.getElementById("copy-url-btn");
            console.log("Copy button found:", copyBtn);
            if (copyBtn) {
                copyBtn.addEventListener("click", function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log("Copy button clicked!");
                    var currentUrl = window.location.href;
                    console.log("Current URL:", currentUrl);
                    var btn = this;
                    var originalSvg = btn.innerHTML;
                    
                    // 버튼 비활성화 및 로딩 표시
                    btn.disabled = true;
                    btn.style.opacity = "0.6";
                    btn.style.cursor = "wait";
                    btn.innerHTML = "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"color: rgba(255,255,255,0.9);\"><circle cx=\"12\" cy=\"12\" r=\"10\"></circle><path d=\"M12 6v6l4 2\"></path></svg>";
                    
                    // 단축 URL 생성 요청
                    var formData = new FormData();
                    formData.append("url", currentUrl);
                    
                    // 절대 경로 사용
                    var apiUrl = "/moodle/local/augmented_teacher/students/create_short_url.php";
                    console.log("Fetching URL:", apiUrl);
                    
                    // 타임아웃 Promise 생성 (10초)
                    var timeoutPromise = new Promise(function(resolve, reject) {
                        setTimeout(function() {
                            reject(new Error("요청 시간이 초과되었습니다. (10초)"));
                        }, 10000);
                    });
                    
                    // fetch와 타임아웃 경쟁
                    Promise.race([
                        fetch(apiUrl, {
                            method: "POST",
                            body: formData,
                            credentials: 'same-origin'
                        }),
                        timeoutPromise
                    ])
                    .then(function(response) {
                        console.log("Response status:", response.status);
                        if (!response.ok) {
                            return response.text().then(function(text) {
                                console.error("Error response:", text);
                                throw new Error("HTTP error! status: " + response.status + " - " + text.substring(0, 100));
                            });
                        }
                        return response.json();
                    })
                    .then(function(data) {
                        if (!data) {
                            throw new Error("응답 데이터가 없습니다.");
                        }
                        console.log("Response data:", data);
                        if (data.success && data.short_url) {
                            // 클립보드에 복사
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                return navigator.clipboard.writeText(data.short_url).then(function() {
                                    // 성공 메시지 표시
                                    btn.innerHTML = "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"color: rgba(255,255,255,0.9);\"><path d=\"M20 6L9 17l-5-5\"></path></svg>";
                                    
                                    // 간단한 알림 (선택사항)
                                    var notification = document.createElement("div");
                                    notification.style.cssText = "position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 10px 20px; border-radius: 4px; z-index: 9999; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);";
                                    notification.textContent = "✓ 단축 URL이 클립보드에 복사되었습니다!";
                                    document.body.appendChild(notification);
                                    
                                    setTimeout(function() {
                                        notification.remove();
                                        btn.innerHTML = originalSvg;
                                        btn.disabled = false;
                                        btn.style.opacity = "1";
                                        btn.style.cursor = "pointer";
                                    }, 2000);
                                });
                            } else {
                                // 클립보드 API를 지원하지 않는 경우 (구형 브라우저)
                                var textarea = document.createElement("textarea");
                                textarea.value = data.short_url;
                                textarea.style.position = "fixed";
                                textarea.style.opacity = "0";
                                document.body.appendChild(textarea);
                                textarea.select();
                                document.execCommand("copy");
                                document.body.removeChild(textarea);
                                
                                btn.innerHTML = "<svg width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"color: rgba(255,255,255,0.9);\"><path d=\"M20 6L9 17l-5-5\"></path></svg>";
                                
                                var notification = document.createElement("div");
                                notification.style.cssText = "position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: #4caf50; color: white; padding: 10px 20px; border-radius: 4px; z-index: 9999; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);";
                                notification.textContent = "✓ 단축 URL이 클립보드에 복사되었습니다!";
                                document.body.appendChild(notification);
                                
                                setTimeout(function() {
                                    notification.remove();
                                    btn.innerHTML = originalSvg;
                                    btn.disabled = false;
                                    btn.style.opacity = "1";
                                    btn.style.cursor = "pointer";
                                }, 2000);
                            }
                        } else {
                            throw new Error(data.error || "단축 URL 생성에 실패했습니다.");
                        }
                    })
                    .catch(function(error) {
                        console.error("단축 URL 생성 오류:", error);
                        console.error("Error details:", error.stack);
                        
                        // 버튼 상태 복원
                        btn.innerHTML = originalSvg;
                        btn.disabled = false;
                        btn.style.opacity = "1";
                        btn.style.cursor = "pointer";
                        
                        // 에러 알림
                        var errorNotification = document.createElement("div");
                        errorNotification.style.cssText = "position: fixed; top: 80px; left: 50%; transform: translateX(-50%); background: #f44336; color: white; padding: 10px 20px; border-radius: 4px; z-index: 9999; font-size: 14px; box-shadow: 0 2px 10px rgba(0,0,0,0.2); max-width: 500px; text-align: center;";
                        errorNotification.textContent = "✗ 단축 URL 생성 실패: " + (error.message || "알 수 없는 오류");
                        document.body.appendChild(errorNotification);
                        
                        setTimeout(function() {
                            errorNotification.remove();
                        }, 5000);
                    });
                });
            }
        });
        
        // 풀이 요청 폼 표시
        function showRequestForm() {
            const section = document.getElementById('requestSection');
            section.style.display = 'block';
            section.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        // 풀이 요청 폼 숨기기
        function hideRequestForm() {
            const section = document.getElementById('requestSection');
            section.style.display = 'none';
            clearForm();
        }
        
        // 폼 초기화
        function clearForm() {
            document.getElementById('requestForm').reset();
            const preview = document.getElementById('imagePreview');
            const uploadArea = document.getElementById('uploadArea');
            preview.style.display = 'none';
            preview.src = '';
            uploadArea.querySelector('.upload-icon').style.display = 'block';
            uploadArea.querySelector('.upload-text').style.display = 'block';
        }
        
        // 드래그 앤 드롭 처리
        function handleDrop(event) {
            event.preventDefault();
            const uploadArea = event.currentTarget;
            uploadArea.style.backgroundColor = '#f8fafc';
            uploadArea.style.borderColor = '#e2e8f0';
            
            const files = event.dataTransfer.files;
            if (files.length > 0 && files[0].type.startsWith('image/')) {
                const fileInput = document.getElementById('questionImage');
                fileInput.files = files;
                handleImageSelect(files[0]);
            }
        }
        
        // 이미지 선택 처리
        function handleImageSelect(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('imagePreview');
                const uploadArea = document.getElementById('uploadArea');
                
                preview.src = e.target.result;
                preview.style.display = 'block';
                uploadArea.querySelector('.upload-icon').style.display = 'none';
                uploadArea.querySelector('.upload-text').style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
        
        // 이미지 미리보기
        document.getElementById('questionImage').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                handleImageSelect(file);
            }
        });
        
        // 풀이 요청 제출
        async function submitRequest(event) {
            console.log('submitRequest called - 파일 위치: capturemathquestion.php:724');
            event.preventDefault();

            const fileInput = document.getElementById('questionImage');
            const problemType = document.getElementById('problemType').value;
            const additionalRequest = document.getElementById('additionalRequest').value;
            const submitBtn = document.getElementById('submitBtn');

            console.log('File input:', fileInput);
            console.log('Files:', fileInput.files);

            if (!fileInput.files[0]) {
                alert('문제 이미지를 업로드해주세요.');
                return;
            }

            // 제출 버튼 비활성화
            submitBtn.disabled = true;
            submitBtn.innerHTML = '🔄 전송 중...';

            try {
                const file = fileInput.files[0];
                let imageDataUrl;

                // 파일 크기 체크 및 압축 여부 결정
                const needsCompression = shouldCompressFile(file);
                console.log(`파일 크기: ${(file.size / 1024 / 1024).toFixed(2)}MB, 압축 필요: ${needsCompression}`);

                if (needsCompression) {
                    // 압축 진행 상태 표시
                    submitBtn.innerHTML = '📦 이미지 압축 중...';
                    console.log('이미지 압축 시작... (파일 위치: capturemathquestion.php:751)');

                    try {
                        // 이미지 압축
                        imageDataUrl = await compressImage(file);
                        console.log('이미지 압축 완료 (파일 위치: capturemathquestion.php:756)');

                        // 전송 상태로 변경
                        submitBtn.innerHTML = '🔄 전송 중...';
                    } catch (compressionError) {
                        console.error('압축 실패, 원본 사용:', compressionError);
                        console.error('에러 위치: capturemathquestion.php:762');
                        // 압축 실패 시 원본 사용
                        const reader = new FileReader();
                        imageDataUrl = await new Promise((resolve, reject) => {
                            reader.onload = (e) => resolve(e.target.result);
                            reader.onerror = reject;
                            reader.readAsDataURL(file);
                        });
                    }
                } else {
                    // 압축 불필요, 원본 사용
                    const reader = new FileReader();
                    imageDataUrl = await new Promise((resolve, reject) => {
                        reader.onload = (e) => resolve(e.target.result);
                        reader.onerror = reject;
                        reader.readAsDataURL(file);
                    });
                }

                // 서버로 전송
                try {
                    console.log('Image loaded, sending to server... (파일 위치: capturemathquestion.php:783)');
                    console.log('studentId:', studentId);
                    console.log('teacherId:', teacherId);

                    // 메인 API 호출 (save_interaction.php)
                    const response = await fetch('save_interaction.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            action: 'create_interaction',
                            studentId: studentId,
                            teacherId: teacherId || 0, // 특정 선생님 지정 가능
                            problemType: problemType,
                            problemImage: imageDataUrl,
                            problemText: '',
                            modificationPrompt: additionalRequest
                        })
                    });

                    // 응답을 JSON으로 파싱 시도
                    let data;
                    try {
                        const text = await response.text();
                        console.log('Response received (파일 위치: capturemathquestion.php:814):', text.substring(0, 200));

                        // JSON 파싱 시도
                        data = JSON.parse(text);
                        console.log('JSON parsed successfully (파일 위치: capturemathquestion.php:817):', data);

                    } catch (parseError) {
                        // JSON 파싱 실패 - Content-Type 체크
                        const contentType = response.headers.get('content-type');
                        console.error('JSON parse failed (파일 위치: capturemathquestion.php:822):', {
                            parseError: parseError.message,
                            contentType: contentType,
                            responseText: text ? text.substring(0, 500) : 'empty'
                        });
                        throw new Error('서버가 유효하지 않은 JSON을 반환했습니다. 에러 페이지가 표시되었을 수 있습니다.');
                    }

                    if (data.success) {
                        // 성공 메시지
                        alert('✅ 풀이요청이 전송되었습니다!\n선생님이 확인 후 답변해 드릴 예정입니다.');

                        // student_inbox.php로 리다이렉트
                        window.location.href = `student_inbox.php?studentid=${studentId}`;

                    } else {
                        throw new Error(data.error || '저장 실패');
                    }
                } catch (serverError) {
                    console.error('Error in server request (파일 위치: capturemathquestion.php:831):', serverError);
                    console.error('Error stack:', serverError.stack);
                    alert('요청 처리 중 오류가 발생했습니다: ' + serverError.message + '\n\n파일 크기가 여전히 큰 경우 화질을 더 낮춰보세요.');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '🚀 발송하기';
                }

            } catch (error) {
                console.error('Error in submitRequest (파일 위치: capturemathquestion.php:840):', error);
                console.error('Error stack:', error.stack);
                alert('요청 전송 중 오류가 발생했습니다: ' + error.message);
                // 버튼 상태 복원
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '🚀 발송하기';
                }
            }
        }
    </script>
</body>
</html>

