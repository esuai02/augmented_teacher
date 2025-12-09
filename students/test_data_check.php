<?php
/**
 * Test Data Check - Database Diagnostic Tool
 * Checks if content data exists for contentsreview.php
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

// Same parameters as contentsreview.php
$cntid = 87712;  // From URL: cntid=87712

echo "<h1>Content Review Data Diagnostic</h1>";
echo "<hr>";

// 1. Check if content pages exist
echo "<h2>1. Database Query Test</h2>";
echo "<pre>";
echo "SELECT * FROM mdl_icontent_pages WHERE cmid='$cntid' ORDER BY pagenum ASC\n\n";

$cntpages = $DB->get_records_sql("SELECT * FROM mdl_icontent_pages WHERE cmid='$cntid' ORDER BY pagenum ASC");

echo "Result count: " . count($cntpages) . "\n";
echo "</pre>";

if (count($cntpages) === 0) {
    echo "<p style='color:red; font-weight:bold;'>❌ NO CONTENT FOUND for cmid=$cntid</p>";
    echo "<p>This explains why the content list is empty!</p>";

    // Check if cmid exists at all
    echo "<h3>Checking if ANY content exists...</h3>";
    $allContent = $DB->get_records_sql("SELECT cmid, COUNT(*) as cnt FROM mdl_icontent_pages GROUP BY cmid LIMIT 10");
    echo "<pre>";
    echo "Available cmid values:\n";
    foreach($allContent as $row) {
        echo "cmid={$row->cmid}: {$row->cnt} pages\n";
    }
    echo "</pre>";

} else {
    echo "<p style='color:green; font-weight:bold;'>✅ Found " . count($cntpages) . " content pages</p>";

    // 2. Show sample data
    echo "<h2>2. Content Data Sample</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Page Num</th><th>Has Image?</th><th>audiourl</th><th>audiourl2</th></tr>";

    $count = 0;
    foreach($cntpages as $page) {
        $count++;
        if ($count > 5) break;  // Show first 5 only

        // Check for image
        $hasImage = 'No';
        if($page->pageicontent) {
            $htmlDom = new DOMDocument;
            @$htmlDom->loadHTML($page->pageicontent);
            $imageTags = $htmlDom->getElementsByTagName('img');
            if ($imageTags->length > 0) {
                $hasImage = 'Yes (' . $imageTags->length . ' images)';
            }
        }

        echo "<tr>";
        echo "<td>{$page->id}</td>";
        echo "<td>" . htmlspecialchars($page->title) . "</td>";
        echo "<td>{$page->pagenum}</td>";
        echo "<td>{$hasImage}</td>";
        echo "<td>" . ($page->audiourl ? '✅' : '❌') . "</td>";
        echo "<td>" . ($page->audiourl2 ? '✅' : '❌') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 3. Generated JSON test
    echo "<h2>3. Generated JSON for JavaScript</h2>";

    $contents = array();
    foreach($cntpages as $value) {
        $title = $value->title;
        $npage = $value->pagenum;
        $contentsid = $value->id;

        // Image extraction (same logic as contentsreview.php)
        $ctext = $value->pageicontent;
        $htmlDom = new DOMDocument;
        @$htmlDom->loadHTML($ctext);
        $imageTags = $htmlDom->getElementsByTagName('img');
        $imgSrc = '';

        foreach($imageTags as $imageTag) {
            $imgSrc = $imageTag->getAttribute('src');
            $imgSrc = str_replace(' ', '%20', $imgSrc);
            if(strpos($imgSrc, 'MATRIX') !== false || strpos($imgSrc, 'MATH') !== false || strpos($imgSrc, 'imgur') !== false) {
                break;
            }
        }

        $contents[] = array(
            'id' => 'MC' . str_pad($npage, 3, '0', STR_PAD_LEFT),
            'title' => $title,
            'pagenum' => $npage,
            'contentsid' => $contentsid,
            'imgSrc' => $imgSrc,
            'audiourl' => $value->audiourl ?? '',
            'audiourl2' => $value->audiourl2 ?? ''
        );
    }

    $contentsJson = json_encode($contents, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    echo "<pre style='background:#f5f5f5; padding:10px; max-height:400px; overflow:auto;'>";
    echo htmlspecialchars($contentsJson);
    echo "</pre>";

    echo "<p><strong>Array length:</strong> " . count($contents) . "</p>";
    echo "<p><strong>JSON string length:</strong> " . strlen($contentsJson) . " characters</p>";
}

echo "<hr>";
echo "<p><em>Test completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>
