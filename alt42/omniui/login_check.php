<?php
// Login check for exam system
session_start();

// Include configuration
require_once 'config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to authenticate user from mathking
function authenticateUser($username, $password) {
    try {
        $dsn = "mysql:host=" . MATHKING_DB_HOST . ";dbname=" . MATHKING_DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, MATHKING_DB_USER, MATHKING_DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Get user from mathking database
        $stmt = $pdo->prepare("
            SELECT id, username, firstname, lastname, email, password
            FROM " . MATHKING_DB_PREFIX . "user
            WHERE username = :username OR email = :username
            LIMIT 1
        ");
        
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Moodle uses bcrypt for passwords
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['login_time'] = time();
                
                return true;
            }
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Authentication error: " . $e->getMessage());
        return false;
    }
}

// Function to logout
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Check if user is logged in for protected pages
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
    
    // Check session timeout
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > SESSION_TIMEOUT)) {
        logout();
    }
    
    // Update last activity
    $_SESSION['login_time'] = time();
}