<?php
// File: mvp_system/tools/convert_legacy_sql.php
// Legacy SQL to Modern Moodle API Converter
// Usage: php convert_legacy_sql.php < legacy_code.php > modern_code.php

/**
 * Convert legacy SQL patterns to modern Moodle API
 */
class LegacySQLConverter {

    /**
     * Convert $_POST variables to clean_param()
     */
    public static function convertPostVariables($code) {
        $patterns = [
            // Integer parameters
            '/\$(\w+)\s*=\s*\$_POST\[\'(eventid|userid|teacherid|attemptid|questionid|checkimsi|trackingid|threadid|course|duration|srcid|itemid|fbid|talkid|wboardid)\'\];/'
                => '$\1 = clean_param($_POST[\'\2\'] ?? 0, PARAM_INT);',

            // Text parameters
            '/\$(\w+)\s*=\s*\$_POST\[\'(inputtext|result|text|comment|feedback)\'\];/'
                => '$\1 = clean_param($_POST[\'\2\'] ?? \'\', PARAM_TEXT);',

            // Alpha parameters
            '/\$(\w+)\s*=\s*\$_POST\[\'(status|type|mode|context)\'\];/'
                => '$\1 = clean_param($_POST[\'\2\'] ?? \'\', PARAM_ALPHA);',

            // Date parameters
            '/\$date\s*=\s*\$_POST\[\'date\'\];/'
                => '$date = clean_param($_POST[\'date\'] ?? \'\', PARAM_TEXT);',
        ];

        foreach ($patterns as $pattern => $replacement) {
            $code = preg_replace($pattern, $replacement, $code);
        }

        return $code;
    }

    /**
     * Convert simple UPDATE statements to prepared statements
     */
    public static function convertSimpleUpdates($code) {
        // Pattern: UPDATE {table} SET field='value' WHERE condition='value'
        $pattern = '/\$DB->execute\("UPDATE \{(\w+)\} SET (\w+)=\'(\$\w+)\' WHERE (\w+)=\'(\$\w+)\'\s*"\);/';

        $callback = function($matches) {
            $table = $matches[1];
            $field = $matches[2];
            $value = $matches[3];
            $whereField = $matches[4];
            $whereValue = $matches[5];

            return sprintf(
                '$DB->execute(' . "\n" .
                '    "UPDATE {%s} SET %s = ?, timemodified = ? WHERE %s = ?",' . "\n" .
                '    [%s, time(), %s]' . "\n" .
                ');',
                $table, $field, $whereField, $value, $whereValue
            );
        };

        return preg_replace_callback($pattern, $callback, $code);
    }

    /**
     * Convert INSERT statements to insert_record()
     */
    public static function convertInserts($code) {
        // This is complex, so we'll provide a template comment instead
        $pattern = '/\$DB->execute\("INSERT INTO \{(\w+)\} \((.*?)\) VALUES\((.*?)\)"\);/s';

        $callback = function($matches) {
            $table = $matches[1];
            $fields = explode(',', $matches[2]);
            $values = explode(',', $matches[3]);

            $conversion = "// TODO: Convert INSERT to insert_record()\n";
            $conversion .= "/*\n";
            $conversion .= '$record = new stdClass();' . "\n";

            foreach ($fields as $i => $field) {
                $field = trim($field);
                $value = isset($values[$i]) ? trim($values[$i], " '\"") : '';
                $conversion .= sprintf('$record->%s = %s;' . "\n", $field, $value);
            }

            $conversion .= '$record->timecreated = time();' . "\n";
            $conversion .= sprintf('$newid = $DB->insert_record(\'%s\', $record);' . "\n", $table);
            $conversion .= "*/\n";

            return $conversion;
        };

        return preg_replace_callback($pattern, $callback, $code);
    }

    /**
     * Add error location info to error messages
     */
    public static function addErrorLocations($code) {
        // Add file and line to die() statements
        $code = preg_replace(
            '/die\("(.*?)"\);/',
            'throw new Exception("$1 at " . __FILE__ . ":" . __LINE__);',
            $code
        );

        return $code;
    }

    /**
     * Convert if-elseif chain to switch statement
     */
    public static function convertToSwitch($code) {
        // This is a manual process, but we can provide structure
        $template = <<<'EOD'
// TODO: Convert to switch statement
/*
switch ($eventid) {
    case 1:
        // Event 1 logic here
        break;

    case 2:
        // Event 2 logic here
        break;

    default:
        throw new Exception("Unknown event ID: $eventid at " . __FILE__ . ":" . __LINE__);
}
*/
EOD;

        // If we detect multiple elseif patterns, add comment
        if (substr_count($code, 'elseif($eventid==') > 5) {
            $code = $template . "\n\n" . $code;
        }

        return $code;
    }

    /**
     * Main conversion method
     */
    public static function convert($code) {
        echo "=== Legacy SQL Converter ===\n";
        echo "Converting code...\n\n";

        $code = self::convertPostVariables($code);
        echo "✓ Converted POST variables to clean_param()\n";

        $code = self::convertSimpleUpdates($code);
        echo "✓ Converted simple UPDATEs to prepared statements\n";

        $code = self::convertInserts($code);
        echo "✓ Added INSERT conversion templates\n";

        $code = self::addErrorLocations($code);
        echo "✓ Added error location tracking\n";

        $code = self::convertToSwitch($code);
        echo "✓ Added switch statement template\n";

        echo "\n=== Conversion Summary ===\n";
        echo "✅ Basic conversions completed\n";
        echo "⚠️  Manual review required for:\n";
        echo "   - Complex UPDATE statements\n";
        echo "   - INSERT statements (templates provided)\n";
        echo "   - if-elseif to switch conversion\n";
        echo "   - Error handling (add try-catch)\n";
        echo "   - CSRF token validation\n\n";

        return $code;
    }
}

// CLI execution
if (php_sapi_name() === 'cli') {
    $input = file_get_contents('php://stdin');

    if (empty($input)) {
        echo "Usage: php convert_legacy_sql.php < input.php > output.php\n";
        echo "Or:    cat input.php | php convert_legacy_sql.php > output.php\n";
        exit(1);
    }

    $output = LegacySQLConverter::convert($input);

    // Write converted code to stdout
    echo "\n=== Converted Code ===\n\n";
    echo $output;
}
?>
