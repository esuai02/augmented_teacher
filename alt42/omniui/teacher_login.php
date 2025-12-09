<?php
// Moodle DB 연결
$CFG = new stdClass();
$CFG->dbhost = '58.180.27.46';
$CFG->dbname = 'mathking';
$CFG->dbuser = 'moodle';
$CFG->dbpass = '@MCtrigd7128';
$CFG->prefix = 'mdl_';

// DB 연결
$DB = new mysqli($CFG->dbhost, $CFG->dbuser, $CFG->dbpass, $CFG->dbname);
if ($DB->connect_error) {
    die("연결 실패: " . $DB->connect_error);
}
$DB->set_charset("utf8mb4");

// 세션 시작
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';

// 로그인 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = '아이디와 비밀번호를 모두 입력해주세요.';
    } else {
        // 사용자 정보 조회
        $sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.password, u.email 
                FROM {$CFG->prefix}user u 
                WHERE u.username = ? AND u.deleted = 0";
        
        $stmt = $DB->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user) {
            // Moodle 비밀번호 검증 (bcrypt)
            if (password_verify($password, $user['password'])) {
                // 교사 권한 확인
                $sql_role = "SELECT data AS role FROM {$CFG->prefix}user_info_data 
                            WHERE userid = ? AND fieldid = 22";
                $stmt_role = $DB->prepare($sql_role);
                $stmt_role->bind_param("i", $user['id']);
                $stmt_role->execute();
                $result_role = $stmt_role->get_result();
                $role_data = $result_role->fetch_assoc();
                
                // 교사 권한이 있는지 확인 (student가 아닌 경우 교사로 간주)
                if ($role_data && $role_data['role'] !== 'student') {
                    // 세션 설정
                    $_SESSION['teacher_id'] = $user['id'];
                    $_SESSION['teacher_username'] = $user['username'];
                    $_SESSION['teacher_name'] = $user['firstname'] . ' ' . $user['lastname'];
                    $_SESSION['teacher_email'] = $user['email'];
                    
                    // 로그인 기록 저장
                    $timecreated = time();
                    $sql_log = "INSERT INTO {$CFG->prefix}abessi_missionlog 
                               (userid, page, timecreated) VALUES (?, 'teacher_login', ?)";
                    $stmt_log = $DB->prepare($sql_log);
                    $stmt_log->bind_param("ii", $user['id'], $timecreated);
                    $stmt_log->execute();
                    
                    // 출결관리 페이지로 리다이렉트
                    header('Location: teacher_attendance_management.php');
                    exit;
                } else {
                    $error_message = '교사 권한이 없습니다. 교사 계정으로 로그인해주세요.';
                }
            } else {
                $error_message = '아이디 또는 비밀번호가 올바르지 않습니다.';
            }
        } else {
            $error_message = '아이디 또는 비밀번호가 올바르지 않습니다.';
        }
    }
}

// 이미 로그인된 경우 리다이렉트
if (isset($_SESSION['teacher_id'])) {
    header('Location: teacher_attendance_management.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MathKing 교사 로그인</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Noto Sans KR', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .login-header p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }
        
        .login-form {
            padding: 40px 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-group input::placeholder {
            color: #a0aec0;
        }
        
        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .error-icon {
            font-size: 20px;
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .login-button:hover::before {
            left: 100%;
        }
        
        .form-footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        
        .form-footer p {
            color: #718096;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .form-footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .form-footer a:hover {
            color: #764ba2;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-right: 8px;
            cursor: pointer;
        }
        
        .remember-me label {
            color: #4a5568;
            font-size: 14px;
            cursor: pointer;
            user-select: none;
        }
        
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .input-icon {
            position: relative;
        }
        
        .input-icon i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">📚</div>
            <h1>MathKing 교사 포털</h1>
            <p>출결관리 시스템 로그인</p>
        </div>
        
        <form class="login-form" method="POST" action="" onsubmit="return handleSubmit(event)">
            <?php if ($error_message): ?>
                <div class="error-message">
                    <span class="error-icon">⚠️</span>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">아이디</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="MathKing 아이디를 입력하세요"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label for="password">비밀번호</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="비밀번호를 입력하세요"
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">로그인 상태 유지</label>
            </div>
            
            <button type="submit" class="login-button" id="loginBtn">
                <span id="btnText">로그인</span>
                <div class="loading" id="loading"></div>
            </button>
            
            <div class="form-footer">
                <p>학생이신가요?</p>
                <a href="https://mathking.kr">MathKing 학생 포털로 이동</a>
            </div>
        </form>
    </div>
    
    <script>
        function handleSubmit(event) {
            const button = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const loading = document.getElementById('loading');
            
            // 로딩 상태 표시
            btnText.style.display = 'none';
            loading.style.display = 'block';
            button.disabled = true;
            
            // 폼은 정상적으로 제출되도록 true 반환
            return true;
        }
        
        // 페이지 로드 시 username 필드에 포커스
        window.onload = function() {
            document.getElementById('username').focus();
        };
        
        // Enter 키로 다음 필드로 이동
        document.getElementById('username').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value) {
                e.preventDefault();
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>
<?php
$DB->close();
?>