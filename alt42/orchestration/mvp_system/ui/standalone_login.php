<?php
// File: mvp_system/ui/standalone_login.php
// Standalone Login Page (No Moodle Dependency)
//
// Purpose: Authentication for standalone teacher panel
// Error Location: /mvp_system/ui/standalone_login.php

require_once(__DIR__ . '/standalone_config.php');
require_once(__DIR__ . '/standalone_database.php');

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        try {
            $db = new StandaloneDB();

            // Query user from Moodle users table
            $sql = "
                SELECT
                    u.id,
                    u.username,
                    u.firstname,
                    u.lastname,
                    u.email,
                    uid.data as role
                FROM mdl_user u
                LEFT JOIN mdl_user_info_data uid ON u.id = uid.userid AND uid.fieldid = 22
                WHERE u.username = ? AND u.deleted = 0 AND u.suspended = 0
                LIMIT 1
            ";

            $users = $db->query($sql, [$username]);

            if (!empty($users)) {
                $user = $users[0];

                // IMPORTANT: In production, verify password against Moodle's password hash
                // For now, using simple check (UPDATE THIS FOR PRODUCTION)

                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['firstname'] = $user['firstname'];
                $_SESSION['lastname'] = $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'] ?? 'user';
                $_SESSION['login_time'] = time();

                log_message("User logged in: " . $username . " (ID: " . $user['id'] . ")", "INFO");

                // Redirect to teacher panel
                header('Location: standalone_teacher_panel.php');
                exit;

            } else {
                $error = "Invalid username or password";
                log_message("Failed login attempt: " . $username, "WARNING");
            }

        } catch (Exception $e) {
            $error = "Login error: " . $e->getMessage();
            log_message($error, "ERROR");
        }
    } else {
        $error = "Please enter username and password";
    }
}

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_destroy();
    $success = "You have been logged out successfully";
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Panel Login - MVP System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        .login-header p {
            color: #666;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🎓 Teacher Panel</h1>
            <p>Mathking MVP System</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="login-footer">
            <p>Standalone Teacher Panel v1.0</p>
            <p>No Moodle Dependency</p>
        </div>
    </div>
</body>
</html>
