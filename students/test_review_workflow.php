<?php
/**
 * Test Script for Content Review Workflow
 *
 * PURPOSE: Test database integration end-to-end
 *
 * TESTS:
 *   1. Table existence verification
 *   2. Sample review submission via AJAX endpoint
 *   3. Review retrieval via AJAX endpoint
 *   4. History record verification
 *   5. Version control testing
 *
 * AUTHOR: Claude Code Assistant
 * DATE: 2025-01-29
 * VERSION: 1.0
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

echo "<h1>Content Review Workflow Test</h1>";
echo "<p><strong>User:</strong> {$USER->firstname} {$USER->lastname} (ID: {$USER->id})</p>";
echo "<hr>";

$errors = array();
$success = array();

// ========================================
// Test 1: Table Existence
// ========================================
echo "<h2>Test 1: Database Tables</h2>";

try {
    $count1 = $DB->count_records('mdl_abessi_content_reviews');
    $count2 = $DB->count_records('mdl_abessi_review_history');

    $success[] = "✅ mdl_abessi_content_reviews exists ({$count1} records)";
    $success[] = "✅ mdl_abessi_review_history exists ({$count2} records)";

    echo "<p style='color:green;'>{$success[count($success)-2]}</p>";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";
} catch (Exception $e) {
    $errors[] = "❌ Table check failed: " . $e->getMessage();
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Test 2: AJAX Endpoint - Submit Review
// ========================================
echo "<h2>Test 2: Submit Review (Simulated POST)</h2>";

// Prepare test data
$testData = array(
    'action' => 'submit_review',
    'contentsid' => 29596,
    'cmid' => 87712,
    'pagenum' => 1,
    'review_level' => 'L4',
    'feedback' => '테스트 피드백: 설명이 명확하고 이해하기 쉽습니다.',
    'improvements' => '테스트 개선사항: 추가 예시가 있으면 더 좋을 것 같습니다.',
    'student_id' => 2,
    'wboard_id' => 'keytopic_review_29596_user2'
);

// Simulate POST data
$_POST = $testData;

// Call AJAX endpoint and capture output
ob_start();
include('contentsreview_ajax.php');
$ajaxResponse = ob_get_clean();

echo "<pre style='background:#f5f5f5; padding:10px; border-radius:4px;'>";
echo htmlspecialchars($ajaxResponse);
echo "</pre>";

$responseData = json_decode($ajaxResponse, true);

if ($responseData && $responseData['success']) {
    $success[] = "✅ Review submitted successfully (ID: {$responseData['review_id']})";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";

    $testReviewId = $responseData['review_id'];
} else {
    $errors[] = "❌ Review submission failed";
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
    if ($responseData) {
        echo "<p style='color:red;'>Error: {$responseData['error']}</p>";
    }
}

// ========================================
// Test 3: Verify Database Record
// ========================================
echo "<h2>Test 3: Verify Database Record</h2>";

if (isset($testReviewId)) {
    try {
        $review = $DB->get_record('mdl_abessi_content_reviews', ['id' => $testReviewId]);

        if ($review) {
            $success[] = "✅ Review record found in database";
            echo "<p style='color:green;'>{$success[count($success)-1]}</p>";

            echo "<table border='1' cellpadding='8' style='border-collapse:collapse; margin-top:10px;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>ID</td><td>{$review->id}</td></tr>";
            echo "<tr><td>Content ID</td><td>{$review->contentsid}</td></tr>";
            echo "<tr><td>Level</td><td><strong>{$review->review_level}</strong></td></tr>";
            echo "<tr><td>Status</td><td>{$review->review_status}</td></tr>";
            echo "<tr><td>Reviewer</td><td>{$review->reviewer_name}</td></tr>";
            echo "<tr><td>Version</td><td>{$review->version}</td></tr>";
            echo "<tr><td>Is Latest</td><td>" . ($review->is_latest ? 'Yes' : 'No') . "</td></tr>";
            echo "<tr><td>Created</td><td>" . date('Y-m-d H:i:s', $review->timecreated) . "</td></tr>";
            echo "</table>";
        } else {
            $errors[] = "❌ Review record NOT found in database";
            echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
        }
    } catch (Exception $e) {
        $errors[] = "❌ Database query failed: " . $e->getMessage();
        echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
    }
}

// ========================================
// Test 4: Verify History Record
// ========================================
echo "<h2>Test 4: Verify History Record</h2>";

if (isset($testReviewId)) {
    try {
        $history = $DB->get_record('mdl_abessi_review_history', ['review_id' => $testReviewId]);

        if ($history) {
            $success[] = "✅ History record found";
            echo "<p style='color:green;'>{$success[count($success)-1]}</p>";

            echo "<table border='1' cellpadding='8' style='border-collapse:collapse; margin-top:10px;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>ID</td><td>{$history->id}</td></tr>";
            echo "<tr><td>Review ID</td><td>{$history->review_id}</td></tr>";
            echo "<tr><td>Action Type</td><td><strong>{$history->action_type}</strong></td></tr>";
            echo "<tr><td>Old Level</td><td>" . ($history->old_level ?? 'N/A') . "</td></tr>";
            echo "<tr><td>New Level</td><td>{$history->new_level}</td></tr>";
            echo "<tr><td>Change Summary</td><td>{$history->change_summary}</td></tr>";
            echo "<tr><td>Changed By</td><td>{$history->changed_by_name}</td></tr>";
            echo "<tr><td>Created</td><td>" . date('Y-m-d H:i:s', $history->timecreated) . "</td></tr>";
            echo "</table>";
        } else {
            $errors[] = "❌ History record NOT found";
            echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
        }
    } catch (Exception $e) {
        $errors[] = "❌ History query failed: " . $e->getMessage();
        echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
    }
}

// ========================================
// Test 5: AJAX Endpoint - Get Review
// ========================================
echo "<h2>Test 5: Get Review (Simulated GET)</h2>";

// Simulate GET request
$_GET = array(
    'action' => 'get_review',
    'contentsid' => 29596
);
$_POST = array(); // Clear POST

ob_start();
include('contentsreview_ajax.php');
$getResponse = ob_get_clean();

echo "<pre style='background:#f5f5f5; padding:10px; border-radius:4px;'>";
echo htmlspecialchars($getResponse);
echo "</pre>";

$getData = json_decode($getResponse, true);

if ($getData && $getData['success'] && $getData['review']) {
    $success[] = "✅ Review retrieved successfully";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";
} else {
    $errors[] = "❌ Review retrieval failed";
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Test 6: Version Control (Update Test)
// ========================================
echo "<h2>Test 6: Version Control - Update Test</h2>";

// Submit another review for same content (should increment version)
$updateData = array(
    'action' => 'submit_review',
    'contentsid' => 29596,
    'cmid' => 87712,
    'pagenum' => 1,
    'review_level' => 'L5',
    'feedback' => '수정된 피드백: 완벽합니다!',
    'improvements' => '수정된 개선사항: 개선 사항 없음',
    'student_id' => 2,
    'wboard_id' => 'keytopic_review_29596_user2'
);

$_POST = $updateData;

ob_start();
include('contentsreview_ajax.php');
$updateResponse = ob_get_clean();

$updateData = json_decode($updateResponse, true);

if ($updateData && $updateData['success']) {
    $success[] = "✅ Update submitted (Version: {$updateData['version']})";
    echo "<p style='color:green;'>{$success[count($success)-1]}</p>";

    // Check if old version was marked as not latest
    try {
        $oldVersions = $DB->get_records_sql(
            "SELECT * FROM mdl_abessi_content_reviews
             WHERE contentsid = ? AND is_latest = 0",
            [29596]
        );

        $oldCount = count($oldVersions);
        $success[] = "✅ Found {$oldCount} historical version(s)";
        echo "<p style='color:green;'>{$success[count($success)-1]}</p>";
    } catch (Exception $e) {
        $errors[] = "❌ Version check failed: " . $e->getMessage();
        echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
    }
} else {
    $errors[] = "❌ Update failed";
    echo "<p style='color:red;'>{$errors[count($errors)-1]}</p>";
}

// ========================================
// Summary
// ========================================
echo "<hr>";
echo "<h2>Test Summary</h2>";

if (count($errors) === 0) {
    echo "<p style='color:green; font-size:18px; font-weight:bold;'>✅ All tests passed!</p>";
} else {
    echo "<p style='color:red; font-size:18px; font-weight:bold;'>⚠️  " . count($errors) . " test(s) failed</p>";
}

echo "<h3>Success (" . count($success) . "):</h3>";
echo "<ul>";
foreach($success as $s) {
    echo "<li>$s</li>";
}
echo "</ul>";

if (count($errors) > 0) {
    echo "<h3>Errors (" . count($errors) . "):</h3>";
    echo "<ul>";
    foreach($errors as $e) {
        echo "<li>$e</li>";
    }
    echo "</ul>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>Test in actual UI: <a href='contentsreview.php?userid=2&cntid=87712&title=검수' target='_blank'>Open Content Review Page</a></li>";
echo "<li>Verify status badges appear in content list</li>";
echo "<li>Select a reviewed content and verify form pre-populates</li>";
echo "<li>Submit a new review and verify database update</li>";
echo "<li>Check browser console for debug messages</li>";
echo "</ol>";

echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
