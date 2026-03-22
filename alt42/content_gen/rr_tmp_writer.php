<?php
/**
 * Temporary helper: accepts POST JSON and writes to /tmp for rr_batch_save processing
 * Usage: POST ?dir=rr_cid71_r1&pageid=16665 with JSON body
 */
header('Content-Type: application/json');

$dir = isset($_GET['dir']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['dir']) : '';
$pageid = isset($_GET['pageid']) ? intval($_GET['pageid']) : 0;

if (!$dir || !$pageid) {
    echo json_encode(['error' => 'dir and pageid required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required', 'usage' => 'POST ?dir=NAME&pageid=PID with JSON body']);
    exit;
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);
if (!$data || !isset($data['badge_text']) || !isset($data['questions'])) {
    echo json_encode(['error' => 'invalid JSON: must have badge_text and questions']);
    exit;
}

$tmpDir = '/tmp/' . $dir;
if (!is_dir($tmpDir)) {
    mkdir($tmpDir, 0777, true);
}

$file = $tmpDir . '/' . $pageid . '.json';
$written = file_put_contents($file, $json);

if ($written === false) {
    echo json_encode(['error' => 'write failed', 'file' => $file]);
} else {
    $count = count(glob($tmpDir . '/*.json'));
    echo json_encode([
        'ok' => true, 
        'file' => $file, 
        'bytes' => $written, 
        'total_files' => $count
    ]);
}
