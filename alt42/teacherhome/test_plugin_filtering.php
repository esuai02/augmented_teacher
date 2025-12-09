<?php
/**
 * Test script to verify plugin filtering by tab
 */

require_once 'plugin_db_config.php';

try {
    $pdo = new PDO($dsn, $user, $password, $options);
    
    // Get all plugins grouped by category and card_title
    $sql = "SELECT category, card_title, COUNT(*) as plugin_count, 
            GROUP_CONCAT(plugin_name SEPARATOR ', ') as plugin_names
            FROM mdl_alt42DB_card_plugin_settings
            WHERE user_id = 2
            GROUP BY category, card_title
            ORDER BY category, card_title";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== Plugin Distribution by Category and Tab ===\n\n";
    
    $currentCategory = '';
    foreach ($results as $row) {
        if ($currentCategory !== $row['category']) {
            $currentCategory = $row['category'];
            echo "\n📁 Category: {$currentCategory}\n";
            echo str_repeat('-', 50) . "\n";
        }
        
        echo "  📑 Tab: '{$row['card_title']}'\n";
        echo "     Plugin Count: {$row['plugin_count']}\n";
        echo "     Plugins: {$row['plugin_names']}\n\n";
    }
    
    // Check for any plugins with empty or null card_title
    $sql2 = "SELECT COUNT(*) as count FROM mdl_alt42DB_card_plugin_settings 
             WHERE user_id = 2 AND (card_title IS NULL OR card_title = '')";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    $emptyCount = $stmt2->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($emptyCount > 0) {
        echo "\n⚠️  WARNING: Found {$emptyCount} plugins with empty card_title!\n";
    }
    
    // List all unique tab titles
    $sql3 = "SELECT DISTINCT card_title FROM mdl_alt42DB_card_plugin_settings 
             WHERE user_id = 2 ORDER BY card_title";
    $stmt3 = $pdo->prepare($sql3);
    $stmt3->execute();
    $tabTitles = $stmt3->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n=== All Unique Tab Titles in Database ===\n";
    foreach ($tabTitles as $title) {
        echo "- '{$title}'\n";
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
}