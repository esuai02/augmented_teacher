<?php
/**
 * Test script for Worldview Loader
 */

include_once("worldview_loader.php");

echo "<h2>Worldview Loader Test</h2>";
echo "<pre>";

// Test modes
$test_modes = ['curriculum', 'exam', 'custom', 'mission', 'reflection', 'selfled', 'cognitive', 'timecentered', 'curiositycentered'];

foreach ($test_modes as $mode) {
    echo "\n========================================\n";
    echo "Testing mode: $mode\n";
    echo "========================================\n";
    
    try {
        // Load worldview data
        $worldview = parseWorldviewFromMarkdown($mode);
        
        echo "✅ Successfully loaded worldview for $mode\n";
        echo "   Title: " . $worldview['title'] . "\n";
        echo "   Icon: " . $worldview['icon'] . "\n";
        echo "   Core Belief: " . $worldview['core_belief'] . "\n";
        
        if (!empty($worldview['kpi'])) {
            echo "   KPI Count: " . count($worldview['kpi']) . "\n";
        }
        
        if (!empty($worldview['switching_triggers'])) {
            echo "   Switching Triggers: " . count($worldview['switching_triggers']) . "\n";
        }
        
        echo "\n--- Formatted Prompt Preview ---\n";
        echo formatWorldviewForPrompt($worldview);
        echo "\n";
        
    } catch (Exception $e) {
        echo "❌ Error loading worldview for $mode: " . $e->getMessage() . "\n";
    }
}

// Test caching
echo "\n========================================\n";
echo "Testing Cache Performance\n";
echo "========================================\n";

$start = microtime(true);
$worldview1 = parseWorldviewFromMarkdown('curriculum');
$time1 = microtime(true) - $start;
echo "First load time: " . number_format($time1 * 1000, 2) . " ms\n";

$start = microtime(true);
$worldview2 = parseWorldviewFromMarkdown('curriculum');
$time2 = microtime(true) - $start;
echo "Cached load time: " . number_format($time2 * 1000, 2) . " ms\n";

if ($time2 < $time1) {
    echo "✅ Caching is working (cached load is " . number_format($time1/$time2, 1) . "x faster)\n";
} else {
    echo "⚠️ Caching may not be working optimally\n";
}

echo "</pre>";
?>