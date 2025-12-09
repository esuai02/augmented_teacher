<?php
include_once("/home/moodle/public_html/moodle/config.php");
include_once("/home/moodle/public_html/moodle/configwhiteboard.php");

global $DB;

$wboardid = isset($_GET['wboardid']) ? $_GET['wboardid'] : '';
$reset = isset($_GET['reset']);

if($wboardid !== ''){
    if($reset){
        $DB->execute("UPDATE {abessi_messages} SET status='' WHERE wboardid = ?", array($wboardid));
    }else{
        $DB->execute("UPDATE {abessi_messages} SET status='complete' WHERE wboardid = ?", array($wboardid));
    }
    echo json_encode(['result' => 'ok']);
}else{
    echo json_encode(['result' => 'no_wboardid']);
}
?> 