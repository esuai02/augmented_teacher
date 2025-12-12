<?php
/**
 * Student Manual System - DB Migration Script
 * File: alt42/orchestration/agents/studentmanual/db/migration_create_tables.php
 *
 * Purpose: Create database tables for student manual system
 * - mdl_at42_studentmanual_items: 메뉴얼 항목 정보
 * - mdl_at42_studentmanual_contents: 컨텐츠 정보
 * - mdl_at42_stumanual_item_cnts: 메뉴얼 항목과 컨텐츠 연결 테이블 (28자 제한)
 *
 * Usage: Access via browser: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/studentmanual/db/migration_create_tables.php
 */

require_once('/home/moodle/public_html/moodle/config.php');
require_login();

global $DB, $USER;

// Check if user has admin privileges
require_capability('moodle/site:config', context_system::instance());

// Include error handler
require_once(__DIR__ . '/../includes/error_handler.php');

echo "<!DOCTYPE html>\n<html lang=\"ko\">\n<head>\n<title>Student Manual DB Migration</title>\n";
echo "<meta charset=\"UTF-8\">\n";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f5f5f5;padding:10px;border-radius:4px;}</style>\n";
echo "</head>\n<body>\n";
echo "<h1>Student Manual System - DB Migration</h1>\n";

try {
    $dbman = $DB->get_manager();
    $errors = [];
    $success = [];

    // ============================================
    // Table 1: mdl_at42_studentmanual_items
    // ============================================
    $table1Name = 'at42_studentmanual_items';
    $table1 = new xmldb_table($table1Name);

    if ($dbman->table_exists($table1)) {
        echo "<p class='info'>ℹ️ Table '{$table1Name}' already exists. Skipping creation.</p>\n";
    } else {
        // Define table structure
        $table1->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table1->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('description', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table1->add_field('agent_id', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null); // 'agent01', 'agent02', etc.
        $table1->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table1->add_field('updated_at', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table1->add_field('created_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Define primary key
        $table1->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Define indexes
        $table1->add_index('agent_id_idx', XMLDB_INDEX_NOTUNIQUE, ['agent_id']);
        $table1->add_index('created_at_idx', XMLDB_INDEX_NOTUNIQUE, ['created_at']);
        $table1->add_index('created_by_idx', XMLDB_INDEX_NOTUNIQUE, ['created_by']);

        // Create table
        $dbman->create_table($table1);
        $success[] = "Table '{$table1Name}' created successfully!";
        echo "<p class='success'>✅ Table '{$table1Name}' created successfully!</p>\n";
    }

    // ============================================
    // Table 2: mdl_at42_studentmanual_contents
    // ============================================
    $table2Name = 'at42_studentmanual_contents';
    $table2 = new xmldb_table($table2Name);

    if ($dbman->table_exists($table2)) {
        echo "<p class='info'>ℹ️ Table '{$table2Name}' already exists. Skipping creation.</p>\n";
    } else {
        // Define table structure
        $table2->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table2->add_field('content_type', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null); // 'image', 'video', 'audio', 'link'
        $table2->add_field('file_path', XMLDB_TYPE_CHAR, '500', null, null, null, null); // 서버 내부 파일 경로
        $table2->add_field('external_url', XMLDB_TYPE_CHAR, '500', null, null, null, null); // 외부 링크 (YouTube, Vimeo 등)
        $table2->add_field('file_size', XMLDB_TYPE_INTEGER, '10', null, null, null, null); // 파일 크기 (bytes)
        $table2->add_field('mime_type', XMLDB_TYPE_CHAR, '100', null, null, null, null); // MIME 타입
        $table2->add_field('created_at', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table2->add_field('created_by', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);

        // Define primary key
        $table2->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Define indexes
        $table2->add_index('content_type_idx', XMLDB_INDEX_NOTUNIQUE, ['content_type']);
        $table2->add_index('created_at_idx', XMLDB_INDEX_NOTUNIQUE, ['created_at']);

        // Create table
        $dbman->create_table($table2);
        $success[] = "Table '{$table2Name}' created successfully!";
        echo "<p class='success'>✅ Table '{$table2Name}' created successfully!</p>\n";
    }

    // ============================================
    // Table 3: mdl_at42_stumanual_item_cnts (28자 제한으로 축약)
    // ============================================
    $table3Name = 'at42_stumanual_item_cnts';
    $table3 = new xmldb_table($table3Name);

    if ($dbman->table_exists($table3)) {
        echo "<p class='info'>ℹ️ Table '{$table3Name}' already exists. Skipping creation.</p>\n";
    } else {
        // Define table structure
        $table3->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table3->add_field('item_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table3->add_field('content_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table3->add_field('display_order', XMLDB_TYPE_INTEGER, '10', null, null, null, '0'); // 표시 순서

        // Define primary key
        $table3->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);

        // Define foreign keys (if supported)
        // Note: Moodle's xmldb doesn't always support foreign keys, so we'll use indexes
        $table3->add_index('item_id_idx', XMLDB_INDEX_NOTUNIQUE, ['item_id']);
        $table3->add_index('content_id_idx', XMLDB_INDEX_NOTUNIQUE, ['content_id']);
        $table3->add_index('item_content_idx', XMLDB_INDEX_UNIQUE, ['item_id', 'content_id']); // 중복 방지

        // Create table
        $dbman->create_table($table3);
        $success[] = "Table '{$table3Name}' created successfully!";
        echo "<p class='success'>✅ Table '{$table3Name}' created successfully!</p>\n";
    }

    // Display table structures
    echo "<h2>Table Structures</h2>\n";
    
    echo "<h3>1. {$table1Name}</h3>\n";
    echo "<pre>\n";
    echo "Columns:\n";
    echo "  - id (INT, AUTO_INCREMENT, PRIMARY KEY)\n";
    echo "  - title (VARCHAR(255), NOT NULL) - 메뉴얼 항목 제목\n";
    echo "  - description (TEXT) - 메뉴얼 항목 설명\n";
    echo "  - agent_id (VARCHAR(50), NOT NULL) - 연결된 에이전트 ID (agent01, agent02 등)\n";
    echo "  - created_at (INT, NOT NULL) - 생성일시 (Unix timestamp)\n";
    echo "  - updated_at (INT) - 수정일시 (Unix timestamp)\n";
    echo "  - created_by (INT, NOT NULL) - 생성자 사용자 ID\n\n";
    echo "Indexes:\n";
    echo "  - agent_id_idx on (agent_id)\n";
    echo "  - created_at_idx on (created_at)\n";
    echo "  - created_by_idx on (created_by)\n";
    echo "</pre>\n";

    echo "<h3>2. {$table2Name}</h3>\n";
    echo "<pre>\n";
    echo "Columns:\n";
    echo "  - id (INT, AUTO_INCREMENT, PRIMARY KEY)\n";
    echo "  - content_type (VARCHAR(50), NOT NULL) - 컨텐츠 타입 (image/video/audio/link)\n";
    echo "  - file_path (VARCHAR(500)) - 서버 내부 파일 경로\n";
    echo "  - external_url (VARCHAR(500)) - 외부 링크 URL\n";
    echo "  - file_size (INT) - 파일 크기 (bytes)\n";
    echo "  - mime_type (VARCHAR(100)) - MIME 타입\n";
    echo "  - created_at (INT, NOT NULL) - 생성일시 (Unix timestamp)\n";
    echo "  - created_by (INT, NOT NULL) - 생성자 사용자 ID\n\n";
    echo "Indexes:\n";
    echo "  - content_type_idx on (content_type)\n";
    echo "  - created_at_idx on (created_at)\n";
    echo "</pre>\n";

    echo "<h3>3. {$table3Name}</h3>\n";
    echo "<pre>\n";
    echo "Columns:\n";
    echo "  - id (INT, AUTO_INCREMENT, PRIMARY KEY)\n";
    echo "  - item_id (INT, NOT NULL) - 메뉴얼 항목 ID\n";
    echo "  - content_id (INT, NOT NULL) - 컨텐츠 ID\n";
    echo "  - display_order (INT, DEFAULT 0) - 표시 순서\n\n";
    echo "Indexes:\n";
    echo "  - item_id_idx on (item_id)\n";
    echo "  - content_id_idx on (content_id)\n";
    echo "  - item_content_idx (UNIQUE) on (item_id, content_id) - 중복 방지\n";
    echo "</pre>\n";
    echo "<p class='info'>ℹ️ 테이블 이름이 28자 제한으로 인해 'at42_stumanual_item_cnts'로 축약되었습니다.</p>\n";

    // Test database connection
    echo "<h2>Database Connection Test</h2>\n";
    if (StudentManualErrorHandler::tableExists($DB, $table1Name)) {
        echo "<p class='success'>✅ Database connection successful!</p>\n";
        echo "<p>All tables are ready to use.</p>\n";
        
        $count1 = $DB->count_records($table1Name);
        $count2 = $DB->count_records($table2Name);
        $count3 = $DB->count_records($table3Name);
        
        echo "<p>Current row counts:</p>\n";
        echo "<ul>\n";
        echo "<li>{$table1Name}: {$count1} rows</li>\n";
        echo "<li>{$table2Name}: {$count2} rows</li>\n";
        echo "<li>{$table3Name}: {$count3} rows</li>\n";
        echo "</ul>\n";
    } else {
        echo "<p class='error'>❌ Database connection test failed!</p>\n";
    }

    echo "<h2>Next Steps</h2>\n";
    echo "<ul>\n";
    echo "<li>Tables are now ready to store manual items and contents</li>\n";
    echo "<li>You can now start using the student manual system</li>\n";
    echo "<li>Upload directory: /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/agents/studentmanual/uploads/</li>\n";
    echo "</ul>\n";

} catch (Exception $e) {
    $file = __FILE__;
    $line = $e->getLine();
    echo "<p class='error'>❌ Migration failed!</p>\n";
    echo "<p class='error'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>File: " . htmlspecialchars($file) . "</p>\n";
    echo "<p>Line: " . htmlspecialchars($line) . "</p>\n";

    echo "<h3>Stack Trace:</h3>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "</body>\n</html>";

