<?php
/**
 * Table Schema Inspector
 *
 * Quick diagnostic tool to check mvp_decision_log table structure
 */

require_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Table Schema Inspector</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .schema { background: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border: 1px solid #ddd; }
        th { background: #4CAF50; color: white; }
        .not-null { background: #ffeb3b; }
        .nullable { background: #e3f2fd; }
        h1 { color: #333; }
        .summary { background: #e8f5e9; padding: 15px; border-radius: 5px; margin: 15px 0; }
    </style>
</head>
<body>
    <h1>📋 Table Schema: mdl_mvp_decision_log</h1>

    <?php
    try {
        $columns = $DB->get_columns('mvp_decision_log');

        if (empty($columns)) {
            echo "<div class='error'>❌ Table not found or has no columns</div>";
            exit;
        }

        echo "<div class='summary'>";
        echo "<strong>Total Columns:</strong> " . count($columns) . "<br>";
        echo "<strong>Timestamp:</strong> " . date('Y-m-d H:i:s');
        echo "</div>";

        echo "<div class='schema'>";
        echo "<table>";
        echo "<thead><tr>";
        echo "<th>Column Name</th>";
        echo "<th>Type</th>";
        echo "<th>Max Length</th>";
        echo "<th>NOT NULL</th>";
        echo "<th>Has Default</th>";
        echo "<th>Default Value</th>";
        echo "<th>Auto Increment</th>";
        echo "</tr></thead>";
        echo "<tbody>";

        foreach ($columns as $col) {
            $row_class = $col->not_null ? 'not-null' : 'nullable';
            echo "<tr class='$row_class'>";
            echo "<td><strong>{$col->name}</strong></td>";
            echo "<td>{$col->type}</td>";
            echo "<td>" . ($col->max_length ?? 'N/A') . "</td>";
            echo "<td>" . ($col->not_null ? '✅ YES' : '⬜ NO') . "</td>";
            echo "<td>" . ($col->has_default ? '✅ YES' : '⬜ NO') . "</td>";
            echo "<td>" . ($col->has_default ? htmlspecialchars($col->default_value) : 'NONE') . "</td>";
            echo "<td>" . ($col->auto_increment ?? false ? '✅ YES' : '⬜ NO') . "</td>";
            echo "</tr>";
        }

        echo "</tbody></table>";
        echo "</div>";

        // Show sample insert structure
        echo "<h2>📝 Sample Record Structure</h2>";
        echo "<div class='schema'>";
        echo "<h3>Required Fields (NOT NULL):</h3>";
        echo "<ul>";
        foreach ($columns as $col) {
            if ($col->not_null && !($col->auto_increment ?? false) && !$col->has_default) {
                echo "<li><strong>{$col->name}</strong> ({$col->type})</li>";
            }
        }
        echo "</ul>";
        echo "</div>";

    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "❌ Error: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
    ?>
</body>
</html>
