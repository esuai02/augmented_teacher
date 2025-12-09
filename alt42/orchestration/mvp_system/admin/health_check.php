<?php
// File: mvp_system/admin/health_check.php
// Health Check Dashboard for MvpDatabase

header('Content-Type: text/html; charset=utf-8');

// Load MvpDatabase
require_once(__DIR__ . '/../lib/MvpDatabase.php');
require_once(__DIR__ . '/../lib/MvpConfig.php');
require_once(__DIR__ . '/../lib/MvpException.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVP System Health Check</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 24px;
        }

        .header .subtitle {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 5px;
        }

        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card h2 {
            margin: 0 0 15px 0;
            font-size: 18px;
            color: #2c3e50;
        }

        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status.ok {
            background: #27ae60;
            color: white;
        }

        .status.error {
            background: #e74c3c;
            color: white;
        }

        .status.warning {
            background: #f39c12;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        table th {
            background: #ecf0f1;
            font-weight: 600;
            font-size: 13px;
        }

        table td {
            font-size: 14px;
        }

        .metric {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }

        .metric:last-child {
            border-bottom: none;
        }

        .metric .label {
            font-weight: 600;
            color: #7f8c8d;
        }

        .metric .value {
            color: #2c3e50;
        }

        .error-message {
            background: #ffebee;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin-top: 15px;
            border-radius: 4px;
        }

        .refresh-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
        }

        .refresh-btn:hover {
            background: #2980b9;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏥 MVP System Health Check</h1>
        <div class="subtitle">Real-time monitoring of MvpDatabase status</div>
    </div>

<?php
$overallStatus = 'ok';
$errors = [];

try {
    // Connection Check
    echo '<div class="card">';
    echo '<h2>🔌 Database Connection</h2>';

    $db = MvpDatabase::getInstance();
    $db->connect();

    echo '<div class="metric">';
    echo '<span class="label">Connection Status</span>';
    echo '<span class="value"><span class="status ok">CONNECTED</span></span>';
    echo '</div>';

    $serverInfo = $db->getServerInfo();
    echo '<div class="metric">';
    echo '<span class="label">MySQL Version</span>';
    echo '<span class="value">' . htmlspecialchars($serverInfo) . '</span>';
    echo '</div>';

    $config = MvpConfig::getDatabaseConfig();
    echo '<div class="metric">';
    echo '<span class="label">Database Host</span>';
    echo '<span class="value">' . htmlspecialchars($config['host']) . '</span>';
    echo '</div>';

    echo '<div class="metric">';
    echo '<span class="label">Database Name</span>';
    echo '<span class="value">' . htmlspecialchars($config['name']) . '</span>';
    echo '</div>';

    echo '<div class="metric">';
    echo '<span class="label">Table Prefix</span>';
    echo '<span class="value">' . htmlspecialchars($db->getTablePrefix()) . '</span>';
    echo '</div>';

    echo '</div>';

} catch (Exception $e) {
    $overallStatus = 'error';
    $errors[] = $e->getMessage();

    echo '<div class="card">';
    echo '<h2>🔌 Database Connection</h2>';
    echo '<div class="metric">';
    echo '<span class="label">Connection Status</span>';
    echo '<span class="value"><span class="status error">FAILED</span></span>';
    echo '</div>';
    echo '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '</div>';
}

// Table Structure Check
if ($overallStatus === 'ok') {
    try {
        echo '<div class="card">';
        echo '<h2>📋 Table Structure</h2>';

        $prefix = $db->getTablePrefix();
        $tableName = $prefix . 'mvp_policy_versions';

        // Check table existence
        // Note: SHOW TABLES LIKE doesn't support prepared statement placeholders
        $escapedTableName = $db->getConnection()->real_escape_string($tableName);
        $sql = "SHOW TABLES LIKE '{$escapedTableName}'";
        $result = $db->fetchOne($sql, []);

        if (!$result) {
            $overallStatus = 'error';
            echo '<div class="metric">';
            echo '<span class="label">Table Status</span>';
            echo '<span class="value"><span class="status error">NOT FOUND</span></span>';
            echo '</div>';
            echo '<div class="error-message">Table ' . htmlspecialchars($tableName) . ' does not exist. Please run migration script.</div>';
        } else {
            echo '<div class="metric">';
            echo '<span class="label">Table Name</span>';
            echo '<span class="value">' . htmlspecialchars($tableName) . ' <span class="status ok">EXISTS</span></span>';
            echo '</div>';

            // Get column count
            $columnsSql = "SHOW COLUMNS FROM {$tableName}";
            $columns = $db->fetchAll($columnsSql);
            $columnCount = count($columns);

            echo '<div class="metric">';
            echo '<span class="label">Columns</span>';
            echo '<span class="value">' . $columnCount . '/10 ';
            echo ($columnCount === 10 ? '<span class="status ok">OK</span>' : '<span class="status warning">MISMATCH</span>');
            echo '</span></div>';

            // Get index count
            $indexesSql = "SHOW INDEXES FROM {$tableName}";
            $indexes = $db->fetchAll($indexesSql);

            $indexNames = [];
            foreach ($indexes as $idx) {
                $indexNames[$idx->Key_name] = true;
            }
            $indexCount = count($indexNames);

            echo '<div class="metric">';
            echo '<span class="label">Indexes</span>';
            echo '<span class="value">' . $indexCount . '/3 ';
            echo ($indexCount === 3 ? '<span class="status ok">OK</span>' : '<span class="status warning">MISMATCH</span>');
            echo '</span></div>';

            // Record count
            $countSql = "SELECT COUNT(*) as count FROM {$tableName}";
            $countResult = $db->fetchOne($countSql);
            $recordCount = $countResult->count;

            echo '<div class="metric">';
            echo '<span class="label">Record Count</span>';
            echo '<span class="value">' . number_format($recordCount) . '</span>';
            echo '</div>';
        }

        echo '</div>';

    } catch (Exception $e) {
        $overallStatus = 'error';
        $errors[] = $e->getMessage();

        echo '<div class="card">';
        echo '<h2>📋 Table Structure</h2>';
        echo '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '</div>';
    }
}

// CRUD Operations Test
if ($overallStatus === 'ok') {
    try {
        echo '<div class="card">';
        echo '<h2>⚡ CRUD Operations</h2>';

        // Test INSERT
        $testData = [
            time(),
            'health_check',
            '/test/health.md',
            md5('health_check_' . time()),
            json_encode(['test' => 'health']),
            0
        ];

        $insertSql = "INSERT INTO {$tableName}
            (created_at, policy_source, file_path, version_hash, parsed_rules, is_active)
            VALUES (?, ?, ?, ?, ?, ?)";

        $db->execute($insertSql, $testData);
        $testId = $db->lastInsertId();

        echo '<div class="metric">';
        echo '<span class="label">INSERT Operation</span>';
        echo '<span class="value"><span class="status ok">PASS</span></span>';
        echo '</div>';

        // Test SELECT
        $selectSql = "SELECT * FROM {$tableName} WHERE id = ?";
        $retrieved = $db->fetchOne($selectSql, [$testId]);

        echo '<div class="metric">';
        echo '<span class="label">SELECT Operation</span>';
        echo '<span class="value"><span class="status ok">PASS</span></span>';
        echo '</div>';

        // Test UPDATE
        $updateSql = "UPDATE {$tableName} SET is_active = ? WHERE id = ?";
        $db->execute($updateSql, [1, $testId]);

        echo '<div class="metric">';
        echo '<span class="label">UPDATE Operation</span>';
        echo '<span class="value"><span class="status ok">PASS</span></span>';
        echo '</div>';

        // Test DELETE
        $deleteSql = "DELETE FROM {$tableName} WHERE id = ?";
        $db->execute($deleteSql, [$testId]);

        echo '<div class="metric">';
        echo '<span class="label">DELETE Operation</span>';
        echo '<span class="value"><span class="status ok">PASS</span></span>';
        echo '</div>';

        echo '</div>';

    } catch (Exception $e) {
        $overallStatus = 'error';
        $errors[] = $e->getMessage();

        echo '<div class="card">';
        echo '<h2>⚡ CRUD Operations</h2>';
        echo '<div class="error-message">' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '</div>';
    }
}

// Overall Status Summary
echo '<div class="card">';
echo '<h2>📊 Overall Status</h2>';

if ($overallStatus === 'ok') {
    echo '<div style="text-align: center; padding: 20px;">';
    echo '<div style="font-size: 48px; margin-bottom: 10px;">✅</div>';
    echo '<div style="font-size: 20px; font-weight: 600; color: #27ae60;">All Systems Operational</div>';
    echo '<div style="margin-top: 10px; color: #7f8c8d;">Last checked: ' . date('Y-m-d H:i:s') . '</div>';
    echo '</div>';
} else {
    echo '<div style="text-align: center; padding: 20px;">';
    echo '<div style="font-size: 48px; margin-bottom: 10px;">❌</div>';
    echo '<div style="font-size: 20px; font-weight: 600; color: #e74c3c;">System Issues Detected</div>';
    echo '<div style="margin-top: 10px; color: #7f8c8d;">Last checked: ' . date('Y-m-d H:i:s') . '</div>';

    if (!empty($errors)) {
        echo '<div style="margin-top: 20px; text-align: left;">';
        echo '<strong>Errors:</strong><ul>';
        foreach ($errors as $error) {
            echo '<li>' . htmlspecialchars($error) . '</li>';
        }
        echo '</ul></div>';
    }
    echo '</div>';
}

echo '<button class="refresh-btn" onclick="location.reload()">🔄 Refresh Status</button>';
echo '</div>';

if (isset($db)) {
    $db->disconnect();
}
?>

</body>
</html>
