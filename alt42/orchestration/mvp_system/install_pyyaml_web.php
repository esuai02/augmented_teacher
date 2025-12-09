<?php
// Environment setup for user-installed Python packages
putenv("PATH=/home/moodle/.local/bin:" . getenv("PATH"));
putenv("PYTHONPATH=/home/moodle/.local/lib/python3.10/site-packages");

/**
 * MVP System PyYAML Installation Web Interface
 *
 * Web interface for installing Python PyYAML dependency
 * Access: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/install_pyyaml_web.php
 *
 * Error Location: /mvp_system/install_pyyaml_web.php
 */

// Include Moodle configuration
require_once('/home/moodle/public_html/moodle/config.php');
global $DB, $USER;
require_login();

// Get user role
$userrole = $DB->get_record_sql("SELECT data FROM mdl_user_info_data WHERE userid='$USER->id' AND fieldid='22'");
$role = $userrole ? $userrole->data : null;

// Security check - allow admin or manager roles
$is_admin = ($role === 'admin' || $role === 'manager');
$action = isset($_GET['action']) ? $_GET['action'] : 'view';

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PyYAML Installation</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 14px; }
        .content { padding: 30px; }
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-warning { background: #fff3cd; border-left: 4px solid #ffc107; color: #856404; }
        .alert-danger { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; }
        .alert-success { background: #d4edda; border-left: 4px solid #28a745; color: #155724; }
        .alert-info { background: #d1ecf1; border-left: 4px solid #17a2b8; color: #0c5460; }
        .section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            font-size: 18px;
            color: #667eea;
            margin-bottom: 15px;
        }
        .code-block {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            margin: 10px 0;
            overflow-x: auto;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover { transform: translateY(-2px); }
        .btn-secondary { background: #6c757d; color: white; }
        .status-check {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: white;
            border-radius: 6px;
        }
        .status-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 16px;
        }
        .status-pass { background: #d4edda; color: #155724; }
        .status-fail { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🐍 PyYAML Installation</h1>
            <p>MVP System Python Dependency Manager</p>
        </div>

        <div class="content">
            <?php if (!$is_admin): ?>
                <div class="alert alert-danger">
                    <strong>⛔ Access Denied</strong><br>
                    Admin privileges required for system installations.<br>
                    Current role: <?php echo htmlspecialchars($role ?? 'none'); ?>
                </div>
            <?php else: ?>

                <div class="section">
                    <h2>Current Status</h2>
                    <?php
                    // Check Python 3
                    $python_check = shell_exec('python3 --version 2>&1');
                    $python_exists = !empty($python_check) && strpos($python_check, 'Python 3') !== false;

                    // Check PyYAML
                    $yaml_check = shell_exec('python3 -c "import yaml; print(yaml.__version__)" 2>&1');
                    $yaml_exists = !empty($yaml_check) && !strpos($yaml_check, 'No module named');
                    ?>

                    <div class="status-check">
                        <div class="status-icon <?php echo $python_exists ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $python_exists ? '✅' : '❌'; ?>
                        </div>
                        <div>
                            <strong>Python 3</strong><br>
                            <span style="font-size: 12px; color: #6c757d;">
                                <?php echo $python_exists ? htmlspecialchars(trim($python_check)) : 'Not found'; ?>
                            </span>
                        </div>
                    </div>

                    <div class="status-check">
                        <div class="status-icon <?php echo $yaml_exists ? 'status-pass' : 'status-fail'; ?>">
                            <?php echo $yaml_exists ? '✅' : '❌'; ?>
                        </div>
                        <div>
                            <strong>PyYAML Module</strong><br>
                            <span style="font-size: 12px; color: #6c757d;">
                                <?php echo $yaml_exists ? 'Version: ' . htmlspecialchars(trim($yaml_check)) : 'Not installed'; ?>
                            </span>
                        </div>
                    </div>
                </div>

                <?php if (!$yaml_exists): ?>
                    <div class="alert alert-warning">
                        <strong>⚠️  PyYAML Not Installed</strong><br>
                        PyYAML is required for YAML rule file validation in the MVP system.
                    </div>

                    <div class="section">
                        <h2>Manual Installation Instructions</h2>
                        <p style="margin-bottom: 15px; color: #6c757d;">
                            Since web-based pip installation may have permission issues, please run this command via SSH:
                        </p>

                        <div class="code-block">pip3 install --user PyYAML</div>

                        <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                            Or use the provided installation script:
                        </p>

                        <div class="code-block">bash /path/to/mvp_system/install_dependencies.sh</div>

                        <p style="margin-top: 15px; color: #6c757d; font-size: 14px;">
                            <strong>Alternative:</strong> If you have root access:
                        </p>

                        <div class="code-block">sudo pip3 install PyYAML</div>
                    </div>

                    <div class="alert alert-info">
                        <strong>ℹ️  Why Manual Installation?</strong><br>
                        Web-based pip installations often fail due to:
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li>PHP process permissions</li>
                            <li>Python virtual environment requirements</li>
                            <li>System-wide vs user-level package conflicts</li>
                        </ul>
                        SSH access provides the most reliable installation method.
                    </div>

                    <a href="install_pyyaml_web.php" class="btn btn-primary" style="margin-top: 10px;">
                        ↻ Refresh Status
                    </a>

                <?php else: ?>
                    <div class="alert alert-success">
                        <strong>✅ PyYAML Installed</strong><br>
                        Version: <?php echo htmlspecialchars(trim($yaml_check)); ?><br>
                        The YAML module is ready for use.
                    </div>

                    <a href="../deploy_verify.php" class="btn btn-primary">
                        → Run Deployment Verification
                    </a>
                <?php endif; ?>

            <?php endif; ?>
        </div>
    </div>
</body>
</html>
