<?php
// File: get_audio.php
// Purpose: Get existing audio file for a specific content and student

include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER; 

header('Content-Type: application/json');

try {
    $thispageid = isset($_GET['thispageid']) ? intval($_GET['thispageid']) : 0;
  
    if ($thispageid === 0) {
        throw new Exception('[get_audio.php:' . __LINE__ . '] Missing thispageid parameter');
    }

    // Get musicurl from mdl_icontent_pages
    $sql = "SELECT id, musicurl FROM {icontent_pages} WHERE id = :thispageid LIMIT 1";
    $page = $DB->get_record_sql($sql, ['thispageid' => $thispageid]);

    if ($page && !empty($page->musicurl)) {
        // Extract filename from URL
        $filename = basename($page->musicurl);
        $file_path = '/home/moodle/public_html/Contents/audiofiles/music/' . $filename;

        // Check if file exists
        if (file_exists($file_path)) {
            $filesize = filesize($file_path);
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            echo json_encode([
                'success' => true,
                'message' => 'Audio file found from musicurl',
                'data' => [
                    'id' => $page->id,
                    'filename' => $filename,
                    'url' => $page->musicurl,
                    'size' => $filesize,
                    'type' => $filetype,
                    'source' => 'icontent_pages'
                ]
            ]);
        } else {
            // URL exists but file is missing
            echo json_encode([
                'success' => false,
                'message' => '[get_audio.php:' . __LINE__ . '] File not found: ' . $filename,
                'data' => null
            ]);
        }
    } else {
        // No musicurl found
        echo json_encode([
            'success' => false,
            'message' => 'No musicurl found for this page',
            'data' => null
        ]);
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file' => __FILE__,
        'line' => __LINE__
    ]);
}
?>
