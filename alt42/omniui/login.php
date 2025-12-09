<?php
session_start();

// If already logged in, redirect to exam system
if (isset($_SESSION['user_id'])) {
    header('Location: exam_system.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'login_check.php';
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (authenticateUser($username, $password)) {
        $redirect = $_GET['redirect'] ?? 'exam_system.php';
        header('Location: ' . $redirect);
        exit;
    } else {
        $error = '아이디 또는 비밀번호가 올바르지 않습니다.';
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>수학킹 로그인</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #E6F0FF 0%, #F0E6FF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-container {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            padding: 2rem;
            max-width: 400px;
            width: 100%;
            margin: 1rem;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: bold;
            color: #5B21B6;
            margin-bottom: 0.5rem;
        }

        .subtitle {
            color: #6B7280;
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #D1D5DB;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.15s, box-shadow 0.15s;
        }

        .form-input:focus {
            outline: none;
            border-color: #5B21B6;
            box-shadow: 0 0 0 3px rgba(91, 33, 182, 0.1);
        }

        .error-message {
            background: #FEE2E2;
            color: #DC2626;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .btn-login {
            width: 100%;
            padding: 0.75rem 1.5rem;
            background: #5B21B6;
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-login:hover {
            background: #4C1D95;
        }

        .divider {
            text-align: center;
            margin: 1.5rem 0;
            color: #9CA3AF;
            font-size: 0.875rem;
        }

        .mathking-info {
            background: #F3F4F6;
            border-radius: 0.5rem;
            padding: 1rem;
            font-size: 0.875rem;
            color: #4B5563;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1 class="logo">수학킹</h1>
            <p class="subtitle">시험 대비 시스템</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label class="form-label">아이디 또는 이메일</label>
                <input type="text" name="username" class="form-input" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">비밀번호</label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <button type="submit" class="btn-login">로그인</button>
        </form>

        <div class="divider">또는</div>

        <div class="mathking-info">
            매스킹(MathKing) 계정으로 로그인하세요.<br>
            계정이 없으신가요? 매스킹 사이트에서 가입해주세요.
        </div>
    </div>
</body>
</html>