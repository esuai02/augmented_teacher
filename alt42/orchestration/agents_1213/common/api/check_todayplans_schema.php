<?php
/**
 * Check mdl_abessi_todayplans table schema
 * Verify fback01~fback16 fields exist
 */

include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

header('Content-Type: application/json; charset=utf-8');

try {
    // Get table structure
    $sql = "DESCRIBE {abessi_todayplans}";
    $columns = $DB->get_records_sql($sql);

    // Sample data from recent record
    $sample = $DB->get_record_sql(
        "SELECT * FROM {abessi_todayplans}
         WHERE userid = 2
         ORDER BY timecreated DESC
         LIMIT 1"
    );

    $fbackFields = array();
    if ($sample) {
        for ($i = 1; $i <= 16; $i++) {
            $fieldName = 'fback' . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (property_exists($sample, $fieldName)) {
                $fbackFields[$fieldName] = $sample->$fieldName;
            }
        }
    }

    echo json_encode(array(
        'success' => true,
        'columns' => $columns,
        'sample_record_id' => $sample ? $sample->id : null,
        'fback_fields' => $fbackFields
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE);
}
?>
