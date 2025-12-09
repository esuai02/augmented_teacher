<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Health check CLI script for Routine Coach plugin
 *
 * @package    local_routinecoach
 * @copyright  2024 MathKing
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/local/routinecoach/classes/service/routine_service.php');

use local_routinecoach\service\routine_service;

// Get CLI options
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'verbose' => false,
        'json' => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        'j' => 'json'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Routine Coach Health Check

This script performs health checks on the Routine Coach plugin installation.

Options:
  -h, --help       Show this help message
  -v, --verbose    Show detailed output
  -j, --json       Output results in JSON format

Example:
  php health_check.php
  php health_check.php --verbose
  php health_check.php --json

";
    echo $help;
    exit(0);
}

// Health check results
$results = [];
$errors = 0;
$warnings = 0;
$success = 0;

/**
 * Add health check result
 */
function add_result($category, $check, $status, $message = '', $details = null) {
    global $results, $errors, $warnings, $success;
    
    $result = [
        'category' => $category,
        'check' => $check,
        'status' => $status,
        'message' => $message
    ];
    
    if ($details) {
        $result['details'] = $details;
    }
    
    $results[] = $result;
    
    switch ($status) {
        case 'ERROR':
            $errors++;
            break;
        case 'WARNING':
            $warnings++;
            break;
        case 'OK':
            $success++;
            break;
    }
}

/**
 * Output result
 */
function output_result($result, $verbose = false) {
    $status_colors = [
        'OK' => "\033[32m✓\033[0m",      // Green
        'WARNING' => "\033[33m⚠\033[0m",  // Yellow
        'ERROR' => "\033[31m✗\033[0m"     // Red
    ];
    
    $status_symbol = $status_colors[$result['status']] ?? '?';
    
    echo sprintf(
        "%s [%s] %s: %s\n",
        $status_symbol,
        str_pad($result['category'], 15),
        $result['check'],
        $result['message']
    );
    
    if ($verbose && isset($result['details'])) {
        foreach ($result['details'] as $key => $value) {
            echo sprintf("    %s: %s\n", $key, $value);
        }
    }
}

// ============================================================================
// Health Checks
// ============================================================================

cli_heading('Routine Coach Health Check');

// 1. Check plugin installation
try {
    $pluginman = core_plugin_manager::instance();
    $plugininfo = $pluginman->get_plugin_info('local_routinecoach');
    
    if ($plugininfo) {
        add_result('INSTALLATION', 'Plugin installed', 'OK', 
            'Version: ' . $plugininfo->versiondisk);
    } else {
        add_result('INSTALLATION', 'Plugin installed', 'ERROR', 
            'Plugin not found in Moodle');
    }
} catch (Exception $e) {
    add_result('INSTALLATION', 'Plugin installed', 'ERROR', $e->getMessage());
}

// 2. Check database tables
$required_tables = [
    'routinecoach_exam',
    'routinecoach_routine',
    'routinecoach_task',
    'routinecoach_log',
    'routinecoach_pref'
];

$dbman = $DB->get_manager();
foreach ($required_tables as $table) {
    $xmldb_table = new xmldb_table($table);
    if ($dbman->table_exists($xmldb_table)) {
        $count = $DB->count_records($table);
        add_result('DATABASE', "Table: $table", 'OK', 
            "Records: $count");
    } else {
        add_result('DATABASE', "Table: $table", 'ERROR', 
            'Table does not exist');
    }
}

// 3. Check required capabilities
$capabilities = [
    'local/routinecoach:view',
    'local/routinecoach:manage',
    'local/routinecoach:viewall'
];

foreach ($capabilities as $capability) {
    if ($DB->record_exists('capabilities', ['name' => $capability])) {
        add_result('CAPABILITIES', $capability, 'OK', 'Capability exists');
    } else {
        add_result('CAPABILITIES', $capability, 'ERROR', 'Capability not found');
    }
}

// 4. Check service class
try {
    $service = new routine_service();
    add_result('SERVICE', 'Service class', 'OK', 'Service instantiated successfully');
    
    // Test basic service functionality
    $test_userid = 2; // Admin user
    $tasks = $service->get_today_tasks($test_userid);
    add_result('SERVICE', 'get_today_tasks', 'OK', 
        'Method works, returned ' . count($tasks['tasks'] ?? []) . ' tasks');
        
} catch (Exception $e) {
    add_result('SERVICE', 'Service class', 'ERROR', $e->getMessage());
}

// 5. Check scheduled tasks
$tasks = \core\task\manager::get_all_scheduled_tasks();
$found_cron = false;
foreach ($tasks as $task) {
    if ($task->get_component() === 'local_routinecoach') {
        $found_cron = true;
        $lastrun = $task->get_last_run_time();
        $nextrun = $task->get_next_run_time();
        
        add_result('CRON', get_class($task), 'OK', 
            'Next run: ' . userdate($nextrun),
            [
                'last_run' => $lastrun ? userdate($lastrun) : 'Never',
                'next_run' => userdate($nextrun),
                'minute' => $task->get_minute(),
                'hour' => $task->get_hour()
            ]
        );
    }
}

if (!$found_cron) {
    add_result('CRON', 'Scheduled tasks', 'WARNING', 'No scheduled tasks found');
}

// 6. Check file permissions
$dirs_to_check = [
    $CFG->dirroot . '/local/routinecoach',
    $CFG->dirroot . '/local/routinecoach/classes',
    $CFG->dirroot . '/local/routinecoach/db',
    $CFG->dirroot . '/local/routinecoach/templates'
];

foreach ($dirs_to_check as $dir) {
    if (is_dir($dir)) {
        if (is_readable($dir)) {
            add_result('PERMISSIONS', basename($dir), 'OK', 'Directory readable');
        } else {
            add_result('PERMISSIONS', basename($dir), 'ERROR', 'Directory not readable');
        }
    } else {
        add_result('PERMISSIONS', basename($dir), 'ERROR', 'Directory not found');
    }
}

// 7. Check AMD modules
$amd_src = $CFG->dirroot . '/local/routinecoach/amd/src/routinecoach.js';
$amd_build = $CFG->dirroot . '/local/routinecoach/amd/build/routinecoach.min.js';

if (file_exists($amd_src)) {
    add_result('AMD', 'Source JS', 'OK', 'routinecoach.js exists');
    
    if (file_exists($amd_build)) {
        $src_time = filemtime($amd_src);
        $build_time = filemtime($amd_build);
        
        if ($build_time >= $src_time) {
            add_result('AMD', 'Built JS', 'OK', 'Build is up to date');
        } else {
            add_result('AMD', 'Built JS', 'WARNING', 
                'Build is outdated, run: php admin/cli/build_js.php');
        }
    } else {
        add_result('AMD', 'Built JS', 'WARNING', 
            'Build not found, run: php admin/cli/build_js.php');
    }
} else {
    add_result('AMD', 'Source JS', 'ERROR', 'routinecoach.js not found');
}

// 8. Check templates
$template_dir = $CFG->dirroot . '/local/routinecoach/templates';
$required_templates = ['routinecoach.mustache', 'widget.mustache'];

foreach ($required_templates as $template) {
    $template_file = $template_dir . '/' . $template;
    if (file_exists($template_file)) {
        add_result('TEMPLATES', $template, 'OK', 'Template exists');
    } else {
        add_result('TEMPLATES', $template, 'ERROR', 'Template not found');
    }
}

// 9. Check integration with existing tables
$integration_tables = ['abessi_schedule', 'abessi_today', 'abessi_missionlog'];
foreach ($integration_tables as $table) {
    $xmldb_table = new xmldb_table($table);
    if ($dbman->table_exists($xmldb_table)) {
        add_result('INTEGRATION', "Table: $table", 'OK', 'Integration table exists');
    } else {
        add_result('INTEGRATION', "Table: $table", 'WARNING', 
            'Integration table not found (may affect some features)');
    }
}

// 10. Check active routines and tasks
try {
    $active_routines = $DB->count_records('routinecoach_routine', ['status' => 'active']);
    $pending_tasks = $DB->count_records('routinecoach_task', ['completed' => 0]);
    $users_with_exams = $DB->count_records_sql(
        "SELECT COUNT(DISTINCT userid) FROM {routinecoach_exam}"
    );
    
    add_result('USAGE', 'Active routines', 'OK', "Count: $active_routines");
    add_result('USAGE', 'Pending tasks', 'OK', "Count: $pending_tasks");
    add_result('USAGE', 'Users with exams', 'OK', "Count: $users_with_exams");
    
} catch (Exception $e) {
    add_result('USAGE', 'Usage statistics', 'WARNING', 'Could not retrieve: ' . $e->getMessage());
}

// ============================================================================
// Output Results
// ============================================================================

if ($options['json']) {
    // JSON output
    $output = [
        'timestamp' => time(),
        'summary' => [
            'total' => count($results),
            'success' => $success,
            'warnings' => $warnings,
            'errors' => $errors
        ],
        'results' => $results
    ];
    
    echo json_encode($output, JSON_PRETTY_PRINT) . "\n";
    
} else {
    // Text output
    echo "\n";
    cli_separator();
    
    foreach ($results as $result) {
        output_result($result, $options['verbose']);
    }
    
    echo "\n";
    cli_separator();
    
    // Summary
    $total = count($results);
    echo "Health Check Summary:\n";
    echo "  Total checks: $total\n";
    echo "  \033[32mSuccess: $success\033[0m\n";
    if ($warnings > 0) {
        echo "  \033[33mWarnings: $warnings\033[0m\n";
    }
    if ($errors > 0) {
        echo "  \033[31mErrors: $errors\033[0m\n";
    }
    
    echo "\n";
    
    // Overall status
    if ($errors > 0) {
        cli_error("Health check FAILED with $errors errors");
    } elseif ($warnings > 0) {
        cli_writeln("Health check completed with $warnings warnings");
        exit(0);
    } else {
        cli_writeln("Health check PASSED - all systems operational");
        exit(0);
    }
}

// Exit with appropriate code
exit($errors > 0 ? 1 : 0);