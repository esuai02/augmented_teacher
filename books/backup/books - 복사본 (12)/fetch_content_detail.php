<?php
/**
 * Fetch Content Detail - AJAX Endpoint
 *
 * PURPOSE: Return HTML fragment with content details for display in detail panel
 *
 * CALLED BY: content_inspector.php AJAX request
 *
 * PARAMETERS:
 *   - contentsid: Content ID from mdl_icontent_pages
 *   - studentid: User ID (optional, defaults to current user)
 *
 * RETURNS: HTML fragment with:
 *   - Content thumbnail image (extracted from pageicontent)
 *   - Audio status (audiourl and audiourl2 availability)
 *   - Link to editprompt.php for full narration editing
 *   - HTML5 audio players for playback
 *
 * SECURITY: Called from authenticated parent page, no require_login() needed
 *
 * AUTHOR: Claude Code Assistant
 * DATE: 2025-01-29
 * VERSION: 1.0
 */

// Moodle bootstrap
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;

// No require_login() - called via AJAX from authenticated content_inspector.php

// Get parameters
$contentsid = $_GET['contentsid'] ?? null;
$studentid = $_GET['studentid'] ?? $USER->id;

// Validate required parameter
if(empty($contentsid)) {
    error_log(sprintf('[Fetch Detail Error] File: %s, Line: %d, Error: Missing contentsid parameter',
        basename(__FILE__), __LINE__));
    die('<div style="color:#d32f2f; padding:20px;">Error: Missing content ID</div>');
}

// Fetch content from database
$cnttext = $DB->get_record_sql("SELECT * FROM mdl_icontent_pages WHERE id='$contentsid' LIMIT 1");

if(!$cnttext) {
    error_log(sprintf('[Fetch Detail Error] File: %s, Line: %d, contentsid: %s not found in database',
        basename(__FILE__), __LINE__, $contentsid));
    die('<div style="color:#d32f2f; padding:20px; text-align:center;">
            <h3>⚠️ Content Not Found</h3>
            <p>Content ID: '.htmlspecialchars($contentsid).'</p>
            <p><small>This content may have been deleted or the ID is invalid.</small></p>
         </div>');
}

// Extract image from pageicontent field using DOMDocument
// Pattern copied from editprompt.php:47-60 (proven pattern used in 20+ files)
$imgSrc = null;
if($cnttext->pageicontent) {
    $htmlDom = new DOMDocument;
    @$htmlDom->loadHTML($cnttext->pageicontent);
    $imageTags = $htmlDom->getElementsByTagName('img');

    foreach($imageTags as $imageTag) {
        $imgSrc = $imageTag->getAttribute('src');

        // Filter for actual content images (png/jpg only)
        if(strpos($imgSrc, '.png') !== false || strpos($imgSrc, '.jpg') !== false) {
            break;
        }
    }
}

// Build HTML response with inline styles
?>
<div style="padding:20px; font-family:'Arial','Malgun Gothic',sans-serif;">

    <!-- Content Title -->
    <h3 style="margin:0 0 15px 0; color:#333; font-size:20px; border-bottom:2px solid #4CAF50; padding-bottom:10px;">
        <?php echo htmlspecialchars($cnttext->title); ?>
    </h3>

    <!-- Content ID Badge -->
    <div style="margin-bottom:15px;">
        <span style="display:inline-block; background:#e0e0e0; padding:4px 10px; border-radius:12px; font-size:12px; color:#666;">
            ID: <?php echo htmlspecialchars($contentsid); ?>
        </span>
    </div>

    <!-- Thumbnail Image -->
    <div style="margin-bottom:20px;">
        <strong style="display:block; margin-bottom:8px; color:#555; font-size:14px;">Content Thumbnail:</strong>
        <?php if($imgSrc): ?>
            <img src="<?php echo htmlspecialchars($imgSrc); ?>"
                 alt="Content thumbnail"
                 style="max-width:100%; height:auto; border:2px solid #ddd; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);" />
        <?php else: ?>
            <div style="background:#f5f5f5; padding:40px 20px; text-align:center; color:#999; border:2px dashed #ddd; border-radius:8px;">
                <span style="font-size:48px;">🖼️</span><br/>
                <span style="font-size:14px;">No image available</span>
            </div>
        <?php endif; ?>
    </div>

    <!-- Audio Status Panel -->
    <div style="background:#f9f9f9; padding:15px; border-left:4px solid #4CAF50; margin-bottom:20px; border-radius:4px;">
        <strong style="display:block; margin-bottom:10px; color:#333; font-size:15px;">🎧 Audio Status:</strong>

        <div style="margin-bottom:8px;">
            <span style="font-weight:600; color:#555;">Full Narration (audiourl):</span>
            <?php if($cnttext->audiourl): ?>
                <span style="color:#4CAF50; font-weight:bold;">✅ Available</span>
            <?php else: ?>
                <span style="color:#999;">❌ Not created</span>
            <?php endif; ?>
        </div>

        <div>
            <span style="font-weight:600; color:#555;">Procedural Memory (audiourl2):</span>
            <?php if($cnttext->audiourl2): ?>
                <span style="color:#4CAF50; font-weight:bold;">✅ Available</span>
            <?php else: ?>
                <span style="color:#999;">❌ Not created</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Link to editprompt.php -->
    <?php
    // URL pattern copied from mynote.php:2367
    $editUrl = 'https://mathking.kr/moodle/local/augmented_teacher/LLM/editprompt.php?cntid='.$contentsid.'&cnttype=1&studentid='.$studentid;
    ?>
    <a href="<?php echo $editUrl; ?>"
       target="_blank"
       style="display:inline-block; padding:12px 20px; background:#4CAF50; color:white; text-decoration:none; border-radius:6px; font-weight:600; margin-bottom:20px; box-shadow:0 2px 4px rgba(0,0,0,0.2); transition:background 0.2s;"
       onmouseover="this.style.background='#45a049'"
       onmouseout="this.style.background='#4CAF50'">
        📝 Edit Full Narration
    </a>

    <!-- Audio Players Section -->
    <?php if($cnttext->audiourl || $cnttext->audiourl2): ?>
    <div style="margin-top:20px; padding-top:20px; border-top:2px solid #e0e0e0;">
        <strong style="display:block; margin-bottom:15px; color:#333; font-size:15px;">🔊 Audio Players:</strong>

        <?php if($cnttext->audiourl): ?>
        <div style="margin-bottom:15px; padding:12px; background:#fafafa; border-radius:6px;">
            <label style="font-size:14px; color:#666; font-weight:600; display:block; margin-bottom:8px;">
                Full Narration:
            </label>
            <audio controls style="width:100%; max-width:500px;">
                <source src="<?php echo htmlspecialchars($cnttext->audiourl); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>
        <?php endif; ?>

        <?php if($cnttext->audiourl2): ?>
        <div style="margin-bottom:15px; padding:12px; background:#fafafa; border-radius:6px;">
            <label style="font-size:14px; color:#666; font-weight:600; display:block; margin-bottom:8px;">
                Procedural Memory:
            </label>
            <audio controls style="width:100%; max-width:500px;">
                <source src="<?php echo htmlspecialchars($cnttext->audiourl2); ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="margin-top:20px; padding:15px; background:#fff3cd; border-left:4px solid #ffc107; border-radius:4px;">
        <strong style="color:#856404;">ℹ️ No Audio Available</strong>
        <p style="margin:5px 0 0 0; color:#856404; font-size:14px;">
            Click "Edit Full Narration" to create audio for this content.
        </p>
    </div>
    <?php endif; ?>

</div>

<?php
// Log successful fetch for debugging
error_log(sprintf('[Fetch Detail Success] File: %s, Line: %d, contentsid: %s, title: %s, has_image: %s, has_audiourl: %s, has_audiourl2: %s',
    basename(__FILE__), __LINE__, $contentsid, $cnttext->title,
    $imgSrc ? 'yes' : 'no',
    $cnttext->audiourl ? 'yes' : 'no',
    $cnttext->audiourl2 ? 'yes' : 'no'
));
?>

<!--
DATABASE SCHEMA REFERENCE:
=========================
Table: mdl_icontent_pages
Fields used:
- id (int) - Primary key, content identifier
- title (varchar) - Content title
- pageicontent (longtext) - HTML content with embedded images
- audiourl (varchar) - Full narration audio file URL
- audiourl2 (varchar) - Procedural memory audio file URL
- pagenum (int) - Page ordering number
- cmid (int) - Course module ID (foreign key)
-->
