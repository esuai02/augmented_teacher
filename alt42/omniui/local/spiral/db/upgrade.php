<?php
/**
 * Curriculum Spiral Scheduler Plugin Upgrade
 * 
 * @package    local_spiral
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_spiral_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2024010100) {
        // Define all tables
        $tables = ['spiral_schedules', 'spiral_sessions', 'spiral_conflicts', 'spiral_templates'];
        
        // Set engine and charset for all tables
        foreach ($tables as $table) {
            if ($dbman->table_exists($table)) {
                try {
                    $DB->execute("ALTER TABLE {" . $table . "} 
                                  ENGINE=InnoDB 
                                  DEFAULT CHARSET=utf8mb4 
                                  COLLATE=utf8mb4_unicode_ci");
                } catch (Exception $e) {
                    // Log error but continue
                    error_log("Failed to alter table {$table}: " . $e->getMessage());
                }
            }
        }
        
        // Add CASCADE constraints for foreign keys
        if ($dbman->table_exists('spiral_sessions')) {
            try {
                $DB->execute("ALTER TABLE {spiral_sessions} 
                              DROP FOREIGN KEY IF EXISTS {spiral_sessions_schedule_fk}");
                $DB->execute("ALTER TABLE {spiral_sessions} 
                              ADD CONSTRAINT {spiral_sessions_schedule_fk} 
                              FOREIGN KEY (schedule_id) 
                              REFERENCES {spiral_schedules}(id) 
                              ON DELETE CASCADE");
            } catch (Exception $e) {
                error_log("Failed to add cascade constraint: " . $e->getMessage());
            }
        }
        
        // Upgrade savepoint
        upgrade_plugin_savepoint(true, 2024010100, 'local', 'spiral');
    }
    
    return true;
}