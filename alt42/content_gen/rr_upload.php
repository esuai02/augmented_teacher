<?php
/**
 * RR Upload Helper - accepts JSON via POST, saves to /tmp/rr{cid}/ and processes
 *
 * Usage:
 *   POST ?action=upload&cid=71&pageid=16632  (body = JSON content)
 *   GET  ?action=mkdir&cid=71                (creates /tmp/rr{cid}/)
 *   GET  ?action=process&cid=71              (processes all JSONs in /tmp/rr{cid}/)
 *   GET  ?action=list&cid=71                 (lists files in /tmp/rr{cid}/)
 */

error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

$action = isset($_GET['action']) ? $_GET['action'] : '';
$cid    = isset($_GET['cid']) ? intval($_GET['cid']) : 0;

if (!$cid || !$action) {
    echo json_encode(['error' => 'Required: action, cid']);
    exit;
}

$dir = '/tmp/rr' . $cid;

switch ($action) {

    case 'mkdir':
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        echo json_encode(['ok' => true, 'dir' => $dir, 'exists' => is_dir($dir)]);
        break;

    case 'upload':
        $pageid = isset($_GET['pageid']) ? intval($_GET['pageid']) : 0;
        if (!$pageid) {
            echo json_encode(['error' => 'pageid required']);
            exit;
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $body = file_get_contents('php://input');
        if (!$body) {
            echo json_encode(['error' => 'empty body']);
            exit;
        }
        // Validate JSON
        $json = json_decode($body, true);
        if (!$json) {
            echo json_encode(['error' => 'invalid JSON']);
            exit;
        }
        $file = $dir . '/' . $pageid . '.json';
        $written = file_put_contents($file, $body);
        echo json_encode(['ok' => true, 'pageid' => $pageid, 'file' => $file, 'bytes' => $written]);
        break;

    case 'list':
        if (!is_dir($dir)) {
            echo json_encode(['error' => 'dir not found', 'dir' => $dir]);
            exit;
        }
        $files = glob($dir . '/*.json');
        $names = array_map('basename', $files);
        echo json_encode(['dir' => $dir, 'count' => count($files), 'files' => $names]);
        break;

    case 'process':
        // Load Moodle
        $cfgpath = dirname(__FILE__) . '/../../../../../../config.php';
        if (!file_exists($cfgpath)) {
            // Try alternative paths
            $cfgpath = '/home/moodle/public_html/moodle/config.php';
        }
        if (!file_exists($cfgpath)) {
            echo json_encode(['error' => 'config.php not found']);
            exit;
        }
        define('NO_MOODLE_COOKIES', true);
        require_once($cfgpath);
        global $DB;

        if (!is_dir($dir)) {
            echo json_encode(['error' => 'dir not found', 'dir' => $dir]);
            exit;
        }

        // Read templates
        $tplDir = dirname(__FILE__) . '/rr_tpl';
        $header = file_get_contents($tplDir . '/header.html');
        $footer = file_get_contents($tplDir . '/footer.html');
        if (!$header || !$footer) {
            echo json_encode(['error' => 'template not found']);
            exit;
        }

        $files = glob($dir . '/*.json');
        $saved = 0;
        $errors = [];

        foreach ($files as $f) {
            $pageid = intval(basename($f, '.json'));
            if (!$pageid) continue;

            $jsonStr = file_get_contents($f);
            $data = json_decode($jsonStr, true);
            if (!$data || !isset($data['badge_text'])) {
                $errors[] = ['pageid' => $pageid, 'error' => 'invalid JSON'];
                continue;
            }

            // Build JS variable block
            $js  = "var BADGE_TEXT = " . json_encode($data['badge_text'], JSON_UNESCAPED_UNICODE) . ";\n";
            $js .= "var ORIGIN_SUMMARY = " . json_encode($data['origin_summary'], JSON_UNESCAPED_UNICODE) . ";\n";
            $js .= "var TYPES = " . json_encode($data['types'], JSON_UNESCAPED_UNICODE) . ";\n";
            $js .= "var QUESTIONS = " . json_encode($data['questions'], JSON_UNESCAPED_UNICODE) . ";\n";

            $html = $header . "\n" . $js . "\n" . $footer;

            // Find the icontent page and save to pagecontents
            // The page structure: page 1 = chapter content, page 2+ = individual fluency
            $page = $DB->get_record('icontent_pages', ['id' => $pageid]);
            if (!$page) {
                $errors[] = ['pageid' => $pageid, 'error' => 'page not found in DB'];
                continue;
            }

            // Check if page 2 exists for this cmid (individual fluency page)
            $cmid = $page->cmid;
            $existingPage2 = $DB->get_record_sql(
                "SELECT * FROM {icontent_pages} WHERE cmid = ? AND pagenum = 2",
                [$cmid]
            );

            if ($existingPage2) {
                // Update existing page 2
                $existingPage2->pagecontents = $html;
                $existingPage2->timemodified = time();
                $DB->update_record('icontent_pages', $existingPage2);
            } else {
                // Create new page 2
                $newPage = new stdClass();
                $newPage->cmid = $cmid;
                $newPage->pagenum = 2;
                $newPage->coverpage = 0;
                $newPage->showtitle = 0;
                $newPage->showborder = 0;
                $newPage->title = $data['badge_text'];
                $newPage->pagecontents = $html;
                $newPage->bgcolor = '';
                $newPage->bordercolor = '';
                $newPage->borderwidth = 0;
                $newPage->maxpages = 0;
                $newPage->timecreated = time();
                $newPage->timemodified = time();
                $DB->insert_record('icontent_pages', $newPage);
            }
            $saved++;
        }

        echo json_encode([
            'ok' => true,
            'dir' => $dir,
            'total' => count($files),
            'saved' => $saved,
            'errors' => $errors
        ]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action. Use: mkdir, upload, list, process']);
}
