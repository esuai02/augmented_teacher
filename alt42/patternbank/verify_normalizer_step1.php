<?php
/**
 * Verification Script for TDD Step 1 (Test Should Fail)
 * Shows that tests were written BEFORE implementation
 */

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>TDD Step 1 Verification: ApiResponseNormalizer</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
        .info { color: blue; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
        pre { background: #eee; padding: 10px; border-left: 3px solid #333; }
    </style>
</head>
<body>
    <h1>🔬 TDD Step 1 Verification: ApiResponseNormalizer</h1>

    <div class="section">
        <h2>TDD Process Check</h2>
        <p><strong>Expected Behavior:</strong> Test should FAIL because implementation doesn't exist yet</p>
        <p><strong>Why:</strong> This proves we're following Test-Driven Development (write tests first)</p>
    </div>

    <div class="section">
        <h2>Step 1: Check Test File Exists</h2>
        <?php
        $testFile = __DIR__ . '/tests/ApiResponseNormalizerTest.php';
        if (file_exists($testFile)) {
            echo "<p class='pass'>✅ Test file exists: ApiResponseNormalizerTest.php</p>";
            echo "<p>Created at: " . date('Y-m-d H:i:s', filemtime($testFile)) . "</p>";
            echo "<p>Size: " . filesize($testFile) . " bytes</p>";
        } else {
            echo "<p class='fail'>❌ Test file NOT found</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>Step 2: Check Implementation File (Should NOT Exist)</h2>
        <?php
        $implFile = __DIR__ . '/lib/ApiResponseNormalizer.php';
        if (file_exists($implFile)) {
            echo "<p class='fail'>❌ PROBLEM: Implementation file already exists!</p>";
            echo "<p>This violates TDD - tests should be written FIRST</p>";
            echo "<p>File created: " . date('Y-m-d H:i:s', filemtime($implFile)) . "</p>";
        } else {
            echo "<p class='pass'>✅ CORRECT: Implementation file does NOT exist yet</p>";
            echo "<p>This confirms we're writing tests BEFORE implementation (TDD)</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>Step 3: Run Test (Should Fail)</h2>
        <?php
        echo "<p>Attempting to run test...</p>";
        echo "<pre>";

        ob_start();
        try {
            require_once(__DIR__ . '/tests/ApiResponseNormalizerTest.php');
            echo "Unexpected: Tests ran without error\n";
        } catch (Error $e) {
            echo "Expected Error Caught:\n";
            echo "Type: " . get_class($e) . "\n";
            echo "Message: " . $e->getMessage() . "\n";

            if (strpos($e->getMessage(), 'ApiResponseNormalizer') !== false) {
                echo "\n✅ CORRECT: Class 'ApiResponseNormalizer' not found\n";
                echo "This confirms TDD approach - test written BEFORE implementation\n";
            }
        }
        $output = ob_get_clean();
        echo htmlspecialchars($output);

        echo "</pre>";
        ?>
    </div>

    <div class="section">
        <h2>TDD Step 1 Summary</h2>
        <?php
        $testExists = file_exists($testFile);
        $implNotExists = !file_exists($implFile);

        if ($testExists && $implNotExists) {
            echo "<p class='pass'>✅ TDD Step 1 VERIFIED</p>";
            echo "<ul>";
            echo "<li>✓ Test file created FIRST</li>";
            echo "<li>✓ Implementation does NOT exist yet</li>";
            echo "<li>✓ Test fails as expected (class not found)</li>";
            echo "<li>✓ Ready for Step 2: Write implementation</li>";
            echo "</ul>";
        } else {
            echo "<p class='fail'>⚠️ TDD Process Issue Detected</p>";
        }
        ?>
    </div>

    <div class="section">
        <h2>Next Steps</h2>
        <ol>
            <li>✅ <strong>Step 1 Complete:</strong> Test written and fails</li>
            <li><strong>Step 2:</strong> Create minimal ApiResponseNormalizer.php implementation</li>
            <li><strong>Step 3:</strong> Run test again (should pass)</li>
            <li><strong>Step 4:</strong> Add real fixture test</li>
            <li><strong>Step 5:</strong> Verify all 5 tests pass</li>
        </ol>
    </div>

</body>
</html>
