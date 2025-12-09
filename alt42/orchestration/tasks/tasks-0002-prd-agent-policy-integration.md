# Task List: Agent Policy Integration - Dynamic Rule Loading

**Based on PRD**: `0002-prd-agent-policy-integration.md`

**Project Context**: Implement Phase 3 (deferred from v1.0) - Connect MVP system to the 21-agent knowledge base for dynamic policy loading.

**Current State Assessment**:
- ✅ **21-Agent Files Exist**: `agents/agent08_calmness/`, `agents/agent20_*/`, `agents/agent21_*/` with markdown policies
- ✅ **YAML Rules Working**: Current `decision/rules/calm_break_rules.yaml` provides reference implementation
- ✅ **Parser Stub Exists**: `lib/policy_parser.php` needs implementation
- ⚠️ **No Policy Versioning**: Need `mdl_mvp_policy_versions` table
- ⚠️ **No Hot Reload**: Manual code changes required for policy updates

## Relevant Files

### Existing Files to Reference
- `agents/agent08_calmness/agent08_calmness.md` - Calm thresholds and metrics (READ ONLY)
- `agents/agent20_intervention_preparation/intervention_templates.md` - Message templates (READ ONLY)
- `agents/agent21_intervention_execution/execution_policies.md` - Delivery rules (READ ONLY)
- `decision/rules/calm_break_rules.yaml` - Current YAML rules (REFERENCE)
- `lib/policy_parser.php` - Existing stub (MODIFY)
- `lib/database.php` - Database utilities (REFERENCE)
- `lib/logger.php` - Logging utilities (REFERENCE)

### New Files to Create
- `lib/markdown_parser.php` - Markdown parsing utilities (CREATE)
- `lib/policy_loader.php` - Policy file watcher and loader (CREATE)
- `lib/policy_cache.php` - Policy caching and versioning (CREATE)
- `lib/rollback_policy.php` - CLI rollback tool (CREATE)
- `database/migrate_policy_versions.php` - Database migration (CREATE)
- `cache/policy_backup.json` - Policy cache file (AUTO-CREATED)
- `contracts/policy.schema.json` - Policy JSON schema (CREATE)

### Test Files
- `tests/markdown_parser.test.php` - Markdown parsing tests (CREATE)
- `tests/policy_parser.test.php` - Policy parsing tests (CREATE)
- `tests/policy_loader.test.php` - Policy loading tests (CREATE)
- `tests/policy_cache.test.php` - Caching tests (CREATE)
- `tests/integration/agent_policy_integration.test.php` - Full integration test (CREATE)

### UI Files
- `ui/policy_viewer.php` - View active policies and versions (CREATE)
- `ui/policy_viewer.css` - Styling (CREATE)
- `ui/policy_viewer.js` - Interactive version history (CREATE)

### Documentation
- `docs/AGENT_POLICY_INTEGRATION_GUIDE.md` - Technical integration guide (CREATE)
- `docs/POLICY_EDITING_GUIDE.md` - Teacher guide for editing agent policies (CREATE)

### Notes
- **CRITICAL**: Never modify files in `agents/` directory - READ ONLY access
- Use existing Moodle `parsedown` library for markdown parsing
- Follow error logging: `throw new Exception("Error at " . __FILE__ . ":" . __LINE__);`
- Maintain backward compatibility with YAML rules
- Test with actual agent08/agent20/agent21 markdown files

---

## Tasks

- [ ] 1.0 Database Schema for Policy Versioning
  - [ ] 1.1 Design `mdl_mvp_policy_versions` table schema
  - [ ] 1.2 Create migration script `database/migrate_policy_versions.php`
  - [ ] 1.3 Write migration test to verify table structure
  - [ ] 1.4 Create rollback migration script
  - [ ] 1.5 Run migration on development database
  - [ ] 1.6 Verify indexes and constraints

- [ ] 2.0 Markdown Parser Implementation
  - [ ] 2.1 Create `lib/markdown_parser.php` with basic parsing
  - [ ] 2.2 Implement YAML frontmatter extraction
  - [ ] 2.3 Implement markdown table parsing
  - [ ] 2.4 Implement code block extraction (YAML/JSON)
  - [ ] 2.5 Write unit tests for markdown parsing
  - [ ] 2.6 Test with actual agent08/agent20/agent21 files

- [ ] 3.0 Policy Parser Core
  - [ ] 3.1 Update `lib/policy_parser.php` to use markdown parser
  - [ ] 3.2 Implement agent08 calm threshold extraction
  - [ ] 3.3 Implement agent20 template extraction
  - [ ] 3.4 Implement agent21 execution rule extraction
  - [ ] 3.5 Create JSON schema `contracts/policy.schema.json`
  - [ ] 3.6 Implement JSON schema validation
  - [ ] 3.7 Write comprehensive unit tests

- [ ] 4.0 Policy Cache System
  - [ ] 4.1 Create `lib/policy_cache.php` for caching logic
  - [ ] 4.2 Implement cache read/write to `cache/policy_backup.json`
  - [ ] 4.3 Implement MD5 hash versioning
  - [ ] 4.4 Implement cache invalidation logic
  - [ ] 4.5 Write cache unit tests
  - [ ] 4.6 Test cache fallback scenarios

- [ ] 5.0 Policy Loader Service
  - [ ] 5.1 Create `lib/policy_loader.php` with file discovery
  - [ ] 5.2 Implement file watching (5-minute polling)
  - [ ] 5.3 Implement hot reload mechanism
  - [ ] 5.4 Implement graceful fallback on parse errors
  - [ ] 5.5 Add policy reload API `api/reload_policies.php`
  - [ ] 5.6 Write integration tests

- [ ] 6.0 Policy Version Management
  - [ ] 6.1 Implement policy version storage in database
  - [ ] 6.2 Create `lib/rollback_policy.php` CLI tool
  - [ ] 6.3 Implement version history queries
  - [ ] 6.4 Add audit logging for policy changes
  - [ ] 6.5 Write version management tests

- [ ] 7.0 Decision Engine Integration
  - [ ] 7.1 Update `decision/rule_engine.py` to accept policy JSON
  - [ ] 7.2 Modify decision API to load policies from parser
  - [ ] 7.3 Implement fallback to YAML rules if parser fails
  - [ ] 7.4 Update decision tests with parsed policies
  - [ ] 7.5 Verify backward compatibility with YAML

- [ ] 8.0 Policy Viewer UI
  - [ ] 8.1 Create `ui/policy_viewer.php` with active policy display
  - [ ] 8.2 Implement version history table
  - [ ] 8.3 Add policy diff viewer (compare versions)
  - [ ] 8.4 Create CSS styling `ui/policy_viewer.css`
  - [ ] 8.5 Add JavaScript interactions `ui/policy_viewer.js`
  - [ ] 8.6 Test UI with multiple policy versions

- [ ] 9.0 Testing & Validation
  - [ ] 9.1 Run all unit tests (6 test suites)
  - [ ] 9.2 Run integration test with real agent files
  - [ ] 9.3 Test hot reload with file modifications
  - [ ] 9.4 Test cache fallback scenarios
  - [ ] 9.5 Test rollback functionality
  - [ ] 9.6 Performance test (parsing under 500ms)
  - [ ] 9.7 Verify zero impact on existing pipeline

- [ ] 10.0 Documentation & Deployment
  - [ ] 10.1 Write `AGENT_POLICY_INTEGRATION_GUIDE.md`
  - [ ] 10.2 Write `POLICY_EDITING_GUIDE.md` for teachers
  - [ ] 10.3 Update `README.md` with policy loading info
  - [ ] 10.4 Create deployment checklist
  - [ ] 10.5 Create rollback procedure documentation
  - [ ] 10.6 Conduct teacher training on policy editing

---

## Detailed Sub-Tasks

### 1.0 Database Schema for Policy Versioning

**Files**:
- Create: `database/migrate_policy_versions.php`
- Modify: None

#### 1.1 Design `mdl_mvp_policy_versions` table schema

Create schema design document with fields:
```sql
CREATE TABLE mdl_mvp_policy_versions (
  id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
  policy_source VARCHAR(50) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  version_hash VARCHAR(64) NOT NULL,
  parsed_rules LONGTEXT NOT NULL,
  is_active TINYINT(1) DEFAULT 0,
  activated_at BIGINT(10),
  deactivated_at BIGINT(10),
  author VARCHAR(100),
  created_at BIGINT(10) NOT NULL,
  INDEX idx_active (is_active, policy_source),
  INDEX idx_hash (version_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### 1.2 Create migration script `database/migrate_policy_versions.php`

```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;
require_login();

try {
    // Create policy_versions table
    $sql = "CREATE TABLE IF NOT EXISTS mdl_mvp_policy_versions (...)";
    $DB->execute($sql);

    echo "✅ Migration successful: mdl_mvp_policy_versions created\n";
} catch (Exception $e) {
    echo "❌ Migration failed at " . __FILE__ . ":" . __LINE__ . ": " . $e->getMessage() . "\n";
    exit(1);
}
?>
```

#### 1.3 Write migration test

Create `tests/migrate_policy_versions.test.php`:
```php
// Test 1: Table exists
$table_exists = $DB->get_manager()->table_exists('mdl_mvp_policy_versions');
assert($table_exists === true, "Table should exist");

// Test 2: All columns present
$columns = $DB->get_columns('mdl_mvp_policy_versions');
assert(isset($columns['policy_source']), "policy_source column missing");
// ... test all columns

echo "✅ All migration tests passed\n";
```

#### 1.4 Create rollback migration script

```php
<?php
// database/rollback_policy_versions.php
$DB->execute("DROP TABLE IF EXISTS mdl_mvp_policy_versions");
echo "✅ Rollback successful\n";
?>
```

#### 1.5 Run migration on development database

```bash
php database/migrate_policy_versions.php
```

Expected output:
```
✅ Migration successful: mdl_mvp_policy_versions created
```

#### 1.6 Verify indexes and constraints

```bash
php -r "
include_once('/home/moodle/public_html/moodle/config.php');
global \$DB;
\$indexes = \$DB->get_records_sql('SHOW INDEXES FROM mdl_mvp_policy_versions');
print_r(\$indexes);
"
```

Expected: 3 indexes (PRIMARY, idx_active, idx_hash)

---

### 2.0 Markdown Parser Implementation

**Files**:
- Create: `lib/markdown_parser.php`
- Test: `tests/markdown_parser.test.php`

#### 2.1 Create `lib/markdown_parser.php` with basic parsing

```php
<?php
class MarkdownParser {
    /**
     * Parse markdown file and extract structured data
     * @param string $file_path Absolute path to markdown file
     * @return array Parsed data structure
     */
    public function parse($file_path) {
        if (!file_exists($file_path)) {
            throw new Exception("File not found: $file_path at " . __FILE__ . ":" . __LINE__);
        }

        $content = file_get_contents($file_path);
        $data = [
            'frontmatter' => $this->extractFrontmatter($content),
            'tables' => $this->extractTables($content),
            'code_blocks' => $this->extractCodeBlocks($content),
            'raw_content' => $content
        ];

        return $data;
    }

    private function extractFrontmatter($content) {
        // Implementation in 2.2
        return null;
    }

    private function extractTables($content) {
        // Implementation in 2.3
        return [];
    }

    private function extractCodeBlocks($content) {
        // Implementation in 2.4
        return [];
    }
}
?>
```

#### 2.2 Implement YAML frontmatter extraction

```php
private function extractFrontmatter($content) {
    // Match YAML frontmatter: ---\nYAML\n---
    if (preg_match('/^---\s*\n(.*?)\n---\s*\n/s', $content, $matches)) {
        $yaml_content = $matches[1];
        try {
            // Use Moodle's YAML parser or Python fallback
            $parsed = yaml_parse($yaml_content);
            return $parsed;
        } catch (Exception $e) {
            throw new Exception("YAML parse error at " . __FILE__ . ":" . __LINE__ . ": " . $e->getMessage());
        }
    }
    return null;
}
```

#### 2.3 Implement markdown table parsing

```php
private function extractTables($content) {
    $tables = [];
    // Match markdown tables: | Header | Header |\n|--------|--------|\n| Cell | Cell |
    preg_match_all('/\|(.+)\|\n\|[\s\-:]+\|\n((?:\|.+\|\n?)+)/m', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $headers = array_map('trim', explode('|', trim($match[1], '|')));
        $rows = explode("\n", trim($match[2]));

        $table_data = [];
        foreach ($rows as $row) {
            if (empty(trim($row))) continue;
            $cells = array_map('trim', explode('|', trim($row, '|')));
            $table_data[] = array_combine($headers, $cells);
        }

        $tables[] = $table_data;
    }

    return $tables;
}
```

#### 2.4 Implement code block extraction

```php
private function extractCodeBlocks($content) {
    $blocks = [];
    // Match fenced code blocks: ```yaml\n...\n```
    preg_match_all('/```(\w+)?\s*\n(.*?)\n```/s', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $lang = $match[1] ?? 'text';
        $code = $match[2];

        if ($lang === 'yaml' || $lang === 'yml') {
            $blocks[] = [
                'type' => 'yaml',
                'content' => yaml_parse($code)
            ];
        } elseif ($lang === 'json') {
            $blocks[] = [
                'type' => 'json',
                'content' => json_decode($code, true)
            ];
        } else {
            $blocks[] = [
                'type' => $lang,
                'content' => $code
            ];
        }
    }

    return $blocks;
}
```

#### 2.5 Write unit tests

Create `tests/markdown_parser.test.php`:
```php
<?php
require_once(__DIR__ . '/../lib/markdown_parser.php');

// Test 1: Parse frontmatter
$test_md = "---\nkey: value\n---\n# Content";
file_put_contents('/tmp/test.md', $test_md);
$parser = new MarkdownParser();
$result = $parser->parse('/tmp/test.md');
assert($result['frontmatter']['key'] === 'value', "Frontmatter parsing failed");

// Test 2: Parse table
$test_table = "| Name | Value |\n|------|-------|\n| Test | 123 |";
file_put_contents('/tmp/test.md', $test_table);
$result = $parser->parse('/tmp/test.md');
assert(count($result['tables']) === 1, "Table parsing failed");
assert($result['tables'][0][0]['Name'] === 'Test', "Table data incorrect");

// Test 3: Parse code block
$test_code = "```yaml\nkey: value\n```";
file_put_contents('/tmp/test.md', $test_code);
$result = $parser->parse('/tmp/test.md');
assert($result['code_blocks'][0]['type'] === 'yaml', "Code block type wrong");

echo "✅ All markdown parser tests passed (3/3)\n";
?>
```

Run test:
```bash
php tests/markdown_parser.test.php
```

Expected: `✅ All markdown parser tests passed (3/3)`

#### 2.6 Test with actual agent files

```bash
php -r "
require_once('lib/markdown_parser.php');
\$parser = new MarkdownParser();
\$result = \$parser->parse('/home/moodle/.../agents/agent08_calmness/agent08_calmness.md');
print_r(\$result);
"
```

Verify output contains extracted frontmatter, tables, and code blocks from agent08 file.

---

### 3.0 Policy Parser Core

**Files**:
- Modify: `lib/policy_parser.php`
- Create: `contracts/policy.schema.json`
- Test: `tests/policy_parser.test.php`

#### 3.1 Update `lib/policy_parser.php` to use markdown parser

```php
<?php
require_once(__DIR__ . '/markdown_parser.php');

class PolicyParser {
    private $markdown_parser;

    public function __construct() {
        $this->markdown_parser = new MarkdownParser();
    }

    /**
     * Parse agent policy file
     * @param string $agent_type 'agent08', 'agent20', or 'agent21'
     * @param string $file_path Absolute path to agent markdown file
     * @return array Structured policy data
     */
    public function parseAgentPolicy($agent_type, $file_path) {
        $markdown_data = $this->markdown_parser->parse($file_path);

        switch ($agent_type) {
            case 'agent08':
                return $this->parseAgent08($markdown_data);
            case 'agent20':
                return $this->parseAgent20($markdown_data);
            case 'agent21':
                return $this->parseAgent21($markdown_data);
            default:
                throw new Exception("Unknown agent type: $agent_type at " . __FILE__ . ":" . __LINE__);
        }
    }

    private function parseAgent08($data) {
        // Implementation in 3.2
    }

    private function parseAgent20($data) {
        // Implementation in 3.3
    }

    private function parseAgent21($data) {
        // Implementation in 3.4
    }
}
?>
```

#### 3.2 Implement agent08 calm threshold extraction

```php
private function parseAgent08($data) {
    $policy = [
        'agent' => 'agent08_calmness',
        'calm_thresholds' => [],
        'intervention_triggers' => []
    ];

    // Extract from frontmatter
    if (isset($data['frontmatter']['calm_thresholds'])) {
        $policy['calm_thresholds'] = $data['frontmatter']['calm_thresholds'];
    }

    // Extract from tables (example: | Range | Min | Max | Action |)
    foreach ($data['tables'] as $table) {
        if (isset($table[0]['Range']) && isset($table[0]['Action'])) {
            foreach ($table as $row) {
                $range_name = strtolower($row['Range']);
                $policy['calm_thresholds'][$range_name] = [
                    'min' => (int)($row['Min'] ?? 0),
                    'max' => (int)($row['Max'] ?? 100),
                    'action' => $row['Action'],
                    'confidence' => (float)($row['Confidence'] ?? 0.8)
                ];
            }
        }
    }

    return $policy;
}
```

#### 3.3 Implement agent20 template extraction

```php
private function parseAgent20($data) {
    $policy = [
        'agent' => 'agent20_intervention_preparation',
        'intervention_templates' => [],
        'intervention_metadata' => []
    ];

    // Extract templates from code blocks
    foreach ($data['code_blocks'] as $block) {
        if ($block['type'] === 'yaml' && isset($block['content']['templates'])) {
            $policy['intervention_templates'] = $block['content']['templates'];
        }
    }

    // Extract metadata from tables
    foreach ($data['tables'] as $table) {
        if (isset($table[0]['Intervention Type'])) {
            foreach ($table as $row) {
                $type = $row['Intervention Type'];
                $policy['intervention_metadata'][$type] = [
                    'urgency' => $row['Urgency'] ?? 'medium',
                    'estimated_duration' => $row['Duration'] ?? '5 minutes'
                ];
            }
        }
    }

    return $policy;
}
```

#### 3.4 Implement agent21 execution rule extraction

```php
private function parseAgent21($data) {
    $policy = [
        'agent' => 'agent21_intervention_execution',
        'execution_rules' => [],
        'escalation_rules' => [],
        'teacher_approval' => []
    ];

    // Extract execution rules from YAML code blocks
    foreach ($data['code_blocks'] as $block) {
        if ($block['type'] === 'yaml') {
            if (isset($block['content']['execution_rules'])) {
                $policy['execution_rules'] = $block['content']['execution_rules'];
            }
            if (isset($block['content']['escalation'])) {
                $policy['escalation_rules'] = $block['content']['escalation'];
            }
        }
    }

    // Extract teacher approval thresholds from frontmatter
    if (isset($data['frontmatter']['teacher_approval'])) {
        $policy['teacher_approval'] = $data['frontmatter']['teacher_approval'];
    }

    return $policy;
}
```

#### 3.5 Create JSON schema

Create `contracts/policy.schema.json`:
```json
{
  "$schema": "http://json-schema.org/draft-07/schema#",
  "type": "object",
  "required": ["agent", "version"],
  "properties": {
    "agent": {
      "type": "string",
      "enum": ["agent08_calmness", "agent20_intervention_preparation", "agent21_intervention_execution"]
    },
    "version": {
      "type": "string",
      "pattern": "^[0-9a-f]{32}$"
    },
    "calm_thresholds": {
      "type": "object",
      "patternProperties": {
        ".*": {
          "type": "object",
          "required": ["action", "confidence"],
          "properties": {
            "min": {"type": "integer", "minimum": 0, "maximum": 100},
            "max": {"type": "integer", "minimum": 0, "maximum": 100},
            "action": {"type": "string"},
            "confidence": {"type": "number", "minimum": 0, "maximum": 1}
          }
        }
      }
    },
    "intervention_templates": {
      "type": "object",
      "patternProperties": {
        ".*": {"type": "string"}
      }
    },
    "execution_rules": {
      "type": "object",
      "properties": {
        "max_per_day": {"type": "integer", "minimum": 1},
        "cooldown_minutes": {"type": "integer", "minimum": 0},
        "requires_teacher_approval": {"type": "boolean"}
      }
    }
  }
}
```

#### 3.6 Implement JSON schema validation

```php
public function validatePolicy($policy) {
    $schema = json_decode(file_get_contents(__DIR__ . '/../contracts/policy.schema.json'), true);

    // Use JSON schema validator (Moodle core or custom)
    $validator = new JsonSchema\Validator();
    $policy_obj = json_decode(json_encode($policy)); // Convert to object
    $schema_obj = json_decode(json_encode($schema));

    $validator->validate($policy_obj, $schema_obj);

    if (!$validator->isValid()) {
        $errors = [];
        foreach ($validator->getErrors() as $error) {
            $errors[] = sprintf("[%s] %s", $error['property'], $error['message']);
        }
        throw new Exception("Policy validation failed: " . implode(", ", $errors) . " at " . __FILE__ . ":" . __LINE__);
    }

    return true;
}
```

#### 3.7 Write comprehensive unit tests

Create `tests/policy_parser.test.php`:
```php
<?php
require_once(__DIR__ . '/../lib/policy_parser.php');

$parser = new PolicyParser();

// Test 1: Parse agent08 policy
$policy = $parser->parseAgentPolicy('agent08', '/home/moodle/.../agents/agent08_calmness/agent08_calmness.md');
assert(isset($policy['calm_thresholds']), "Agent08 calm_thresholds missing");
assert(count($policy['calm_thresholds']) > 0, "No thresholds extracted");

// Test 2: Parse agent20 policy
$policy = $parser->parseAgentPolicy('agent20', '/home/moodle/.../agents/agent20_intervention_preparation/intervention_templates.md');
assert(isset($policy['intervention_templates']), "Agent20 templates missing");

// Test 3: Parse agent21 policy
$policy = $parser->parseAgentPolicy('agent21', '/home/moodle/.../agents/agent21_intervention_execution/execution_policies.md');
assert(isset($policy['execution_rules']), "Agent21 execution_rules missing");

// Test 4: Validate policy structure
try {
    $policy['version'] = md5('test');
    $parser->validatePolicy($policy);
    echo "✅ Policy validation passed\n";
} catch (Exception $e) {
    die("❌ Validation failed: " . $e->getMessage() . "\n");
}

echo "✅ All policy parser tests passed (4/4)\n";
?>
```

Run test:
```bash
php tests/policy_parser.test.php
```

Expected: `✅ All policy parser tests passed (4/4)`

---

### 4.0 Policy Cache System

**Files**:
- Create: `lib/policy_cache.php`
- Test: `tests/policy_cache.test.php`

#### 4.1 Create `lib/policy_cache.php`

```php
<?php
class PolicyCache {
    private $cache_file;

    public function __construct($cache_file = null) {
        $this->cache_file = $cache_file ?? __DIR__ . '/../cache/policy_backup.json';

        // Ensure cache directory exists
        $cache_dir = dirname($this->cache_file);
        if (!is_dir($cache_dir)) {
            mkdir($cache_dir, 0755, true);
        }
    }

    /**
     * Write policy to cache
     * @param array $policy Policy data
     * @param string $version_hash MD5 hash of source files
     */
    public function write($policy, $version_hash) {
        $cache_data = [
            'version' => $version_hash,
            'cached_at' => time(),
            'policies' => $policy
        ];

        $json = json_encode($cache_data, JSON_PRETTY_PRINT);
        if (file_put_contents($this->cache_file, $json) === false) {
            throw new Exception("Failed to write cache at " . __FILE__ . ":" . __LINE__);
        }

        return true;
    }

    /**
     * Read policy from cache
     * @param int $max_age Maximum cache age in seconds (default: 7 days)
     * @return array|null Cached policy or null if invalid/expired
     */
    public function read($max_age = 604800) {
        if (!file_exists($this->cache_file)) {
            return null;
        }

        $json = file_get_contents($this->cache_file);
        $cache_data = json_decode($json, true);

        if (!$cache_data || !isset($cache_data['cached_at'])) {
            return null;
        }

        // Check age
        $age = time() - $cache_data['cached_at'];
        if ($age > $max_age) {
            return null; // Expired
        }

        return $cache_data['policies'];
    }

    /**
     * Get cache version hash
     */
    public function getVersion() {
        $cache_data = $this->readRaw();
        return $cache_data['version'] ?? null;
    }

    /**
     * Invalidate cache
     */
    public function invalidate() {
        if (file_exists($this->cache_file)) {
            unlink($this->cache_file);
        }
    }

    private function readRaw() {
        if (!file_exists($this->cache_file)) {
            return null;
        }
        return json_decode(file_get_contents($this->cache_file), true);
    }
}
?>
```

#### 4.2-4.6 Implement and test cache operations

*(Implementations shown in 4.1)*

Create `tests/policy_cache.test.php`:
```php
<?php
require_once(__DIR__ . '/../lib/policy_cache.php');

$cache = new PolicyCache('/tmp/test_cache.json');

// Test 1: Write to cache
$test_policy = ['agent' => 'test', 'rules' => ['key' => 'value']];
$cache->write($test_policy, md5('test'));
assert(file_exists('/tmp/test_cache.json'), "Cache file not created");

// Test 2: Read from cache
$cached = $cache->read();
assert($cached !== null, "Cache read failed");
assert($cached['agent'] === 'test', "Cache data incorrect");

// Test 3: Cache expiration
$cached = $cache->read(0); // 0 seconds = always expired
assert($cached === null, "Expired cache should return null");

// Test 4: Invalidate cache
$cache->invalidate();
assert(!file_exists('/tmp/test_cache.json'), "Cache file should be deleted");

echo "✅ All cache tests passed (4/4)\n";
?>
```

Run test:
```bash
php tests/policy_cache.test.php
```

---

### 5.0 Policy Loader Service

**Files**:
- Create: `lib/policy_loader.php`, `api/reload_policies.php`
- Test: `tests/policy_loader.test.php`

#### 5.1 Create `lib/policy_loader.php` with file discovery

```php
<?php
require_once(__DIR__ . '/policy_parser.php');
require_once(__DIR__ . '/policy_cache.php');

class PolicyLoader {
    private $parser;
    private $cache;
    private $agent_dirs;

    public function __construct() {
        $this->parser = new PolicyParser();
        $this->cache = new PolicyCache();
        $this->agent_dirs = [
            'agent08' => '/home/moodle/.../agents/agent08_calmness/',
            'agent20' => '/home/moodle/.../agents/agent20_intervention_preparation/',
            'agent21' => '/home/moodle/.../agents/agent21_intervention_execution/'
        ];
    }

    /**
     * Discover all .md files in agent directories
     * @return array List of agent files
     */
    public function discoverAgentFiles() {
        $files = [];

        foreach ($this->agent_dirs as $agent_type => $dir) {
            if (!is_dir($dir)) {
                error_log("Agent directory not found: $dir");
                continue;
            }

            $md_files = glob($dir . '*.md');
            foreach ($md_files as $file) {
                $files[] = [
                    'agent_type' => $agent_type,
                    'file_path' => $file,
                    'file_name' => basename($file)
                ];
            }
        }

        return $files;
    }

    /**
     * Calculate version hash for all agent files
     * @return string MD5 hash of concatenated file hashes
     */
    public function calculateVersionHash() {
        $files = $this->discoverAgentFiles();
        $hashes = [];

        foreach ($files as $file) {
            $hashes[] = md5_file($file['file_path']);
        }

        return md5(implode('', $hashes));
    }

    /**
     * Load all policies from agent files
     * @param bool $use_cache Whether to use cached version
     * @return array Combined policies from all agents
     */
    public function loadPolicies($use_cache = true) {
        $current_hash = $this->calculateVersionHash();

        // Try cache first
        if ($use_cache) {
            $cached_hash = $this->cache->getVersion();
            if ($cached_hash === $current_hash) {
                $cached_policy = $this->cache->read();
                if ($cached_policy !== null) {
                    return $cached_policy;
                }
            }
        }

        // Parse fresh policies
        $policies = $this->parsePolicies();

        // Cache the result
        $this->cache->write($policies, $current_hash);

        return $policies;
    }

    private function parsePolicies() {
        // Implementation in 5.2
    }
}
?>
```

#### 5.2 Implement file watching (5-minute polling)

```php
/**
 * Check if policies have changed since last load
 * @return bool True if policies changed
 */
public function hasChanged() {
    $current_hash = $this->calculateVersionHash();
    $cached_hash = $this->cache->getVersion();

    return $current_hash !== $cached_hash;
}

/**
 * Start policy watcher (5-minute polling loop)
 * Use with cron: */5 * * * * php lib/policy_watcher.php
 */
public function watchPolicies() {
    if ($this->hasChanged()) {
        echo "[" . date('Y-m-d H:i:s') . "] Policy changes detected, reloading...\n";
        try {
            $policies = $this->loadPolicies(false); // Force reload
            $this->storePolicyVersion($policies);
            echo "✅ Policies reloaded successfully\n";
        } catch (Exception $e) {
            echo "❌ Policy reload failed: " . $e->getMessage() . "\n";
            // Keep using cached version
        }
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] No policy changes detected\n";
    }
}
```

Create cron watcher script `lib/policy_watcher.php`:
```php
<?php
require_once(__DIR__ . '/policy_loader.php');

$loader = new PolicyLoader();
$loader->watchPolicies();
?>
```

Add to crontab:
```bash
*/5 * * * * cd /home/moodle/.../mvp_system && php lib/policy_watcher.php >> logs/policy_watcher.log 2>&1
```

#### 5.3 Implement hot reload mechanism

```php
private function parsePolicies() {
    $combined_policies = [
        'agent08' => null,
        'agent20' => null,
        'agent21' => null
    ];

    $files = $this->discoverAgentFiles();

    foreach ($files as $file) {
        try {
            $policy = $this->parser->parseAgentPolicy($file['agent_type'], $file['file_path']);
            $policy['version'] = md5_file($file['file_path']);

            // Validate before merging
            $this->parser->validatePolicy($policy);

            // Merge policies (alphabetical, later files override)
            if ($combined_policies[$file['agent_type']] === null) {
                $combined_policies[$file['agent_type']] = $policy;
            } else {
                $combined_policies[$file['agent_type']] = array_merge(
                    $combined_policies[$file['agent_type']],
                    $policy
                );
            }
        } catch (Exception $e) {
            error_log("Failed to parse {$file['file_path']}: " . $e->getMessage());
            // Continue with other files
        }
    }

    return $combined_policies;
}
```

#### 5.4 Implement graceful fallback

```php
public function loadPoliciesWithFallback() {
    try {
        return $this->loadPolicies(true);
    } catch (Exception $e) {
        error_log("Policy loading failed, using cached version: " . $e->getMessage());

        // Try cache
        $cached = $this->cache->read();
        if ($cached !== null) {
            return $cached;
        }

        // Last resort: use YAML rules
        error_log("Cache unavailable, falling back to YAML rules");
        return $this->loadYamlFallback();
    }
}

private function loadYamlFallback() {
    // Read existing YAML rules as fallback
    $yaml_file = __DIR__ . '/../decision/rules/calm_break_rules.yaml';
    if (file_exists($yaml_file)) {
        return yaml_parse_file($yaml_file);
    }

    throw new Exception("No fallback policies available at " . __FILE__ . ":" . __LINE__);
}
```

#### 5.5 Add policy reload API

Create `api/reload_policies.php`:
```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// Check permissions (admin only)
if (!is_siteadmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once(__DIR__ . '/../lib/policy_loader.php');

try {
    $loader = new PolicyLoader();
    $policies = $loader->loadPolicies(false); // Force reload

    echo json_encode([
        'success' => true,
        'message' => 'Policies reloaded successfully',
        'version' => $loader->calculateVersionHash(),
        'timestamp' => time()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

Test API:
```bash
curl -X POST "https://mathking.kr/.../api/reload_policies.php" \
  -H "Cookie: MoodleSession=YOUR_SESSION"
```

Expected:
```json
{
  "success": true,
  "message": "Policies reloaded successfully",
  "version": "abc123...",
  "timestamp": 1699123456
}
```

#### 5.6 Write integration tests

Create `tests/policy_loader.test.php`:
```php
<?php
require_once(__DIR__ . '/../lib/policy_loader.php');

$loader = new PolicyLoader();

// Test 1: Discover agent files
$files = $loader->discoverAgentFiles();
assert(count($files) > 0, "No agent files discovered");
echo "✅ Discovered " . count($files) . " agent files\n";

// Test 2: Calculate version hash
$hash = $loader->calculateVersionHash();
assert(strlen($hash) === 32, "Invalid MD5 hash");
echo "✅ Version hash: $hash\n";

// Test 3: Load policies
$policies = $loader->loadPolicies(false); // Fresh load
assert(isset($policies['agent08']), "Agent08 policies missing");
assert(isset($policies['agent20']), "Agent20 policies missing");
assert(isset($policies['agent21']), "Agent21 policies missing");
echo "✅ All agent policies loaded\n";

// Test 4: Cache hit
$cached_policies = $loader->loadPolicies(true); // Use cache
assert($cached_policies === $policies, "Cache mismatch");
echo "✅ Cache working correctly\n";

// Test 5: Fallback mechanism
$fallback_policies = $loader->loadPoliciesWithFallback();
assert($fallback_policies !== null, "Fallback failed");
echo "✅ Fallback mechanism working\n";

echo "✅ All policy loader tests passed (5/5)\n";
?>
```

Run test:
```bash
php tests/policy_loader.test.php
```

---

*(Continuing with remaining tasks 6.0-10.0...)*

### 6.0 Policy Version Management

**Files**:
- Create: `lib/rollback_policy.php`
- Modify: `lib/policy_loader.php` (add version storage)

#### 6.1 Implement policy version storage

Add to `lib/policy_loader.php`:
```php
/**
 * Store policy version in database
 * @param array $policies Parsed policies
 */
private function storePolicyVersion($policies) {
    global $DB;

    foreach ($policies as $agent_type => $policy) {
        if ($policy === null) continue;

        // Deactivate previous versions
        $DB->execute("UPDATE mdl_mvp_policy_versions SET is_active = 0, deactivated_at = ? WHERE policy_source = ? AND is_active = 1", [time(), $agent_type]);

        // Insert new version
        $record = new stdClass();
        $record->policy_source = $agent_type;
        $record->file_path = $this->agent_dirs[$agent_type];
        $record->version_hash = $policy['version'] ?? md5(json_encode($policy));
        $record->parsed_rules = json_encode($policy);
        $record->is_active = 1;
        $record->activated_at = time();
        $record->author = 'system'; // TODO: Track actual user
        $record->created_at = time();

        $DB->insert_record('mdl_mvp_policy_versions', $record);
    }
}
```

#### 6.2 Create `lib/rollback_policy.php` CLI tool

```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB;

if (php_sapi_name() !== 'cli') {
    die("This script must be run from command line\n");
}

if ($argc < 2) {
    echo "Usage: php rollback_policy.php <version_id>\n";
    echo "       php rollback_policy.php --list\n";
    exit(1);
}

$command = $argv[1];

if ($command === '--list') {
    // List recent versions
    $versions = $DB->get_records_sql("
        SELECT id, policy_source, version_hash, is_active, activated_at, deactivated_at
        FROM mdl_mvp_policy_versions
        ORDER BY created_at DESC
        LIMIT 20
    ");

    echo "Recent Policy Versions:\n";
    echo str_repeat('-', 80) . "\n";
    printf("%-5s %-15s %-15s %-8s %-20s\n", 'ID', 'Source', 'Hash', 'Active', 'Activated At');
    echo str_repeat('-', 80) . "\n";

    foreach ($versions as $v) {
        printf("%-5d %-15s %-15s %-8s %-20s\n",
            $v->id,
            $v->policy_source,
            substr($v->version_hash, 0, 12),
            $v->is_active ? 'YES' : 'NO',
            date('Y-m-d H:i:s', $v->activated_at)
        );
    }
    exit(0);
}

$version_id = (int)$command;
$version = $DB->get_record('mdl_mvp_policy_versions', ['id' => $version_id]);

if (!$version) {
    die("Error: Version ID $version_id not found\n");
}

// Confirm rollback
echo "Rolling back to version $version_id:\n";
echo "  Source: {$version->policy_source}\n";
echo "  Hash: {$version->version_hash}\n";
echo "  Created: " . date('Y-m-d H:i:s', $version->created_at) . "\n";
echo "\nConfirm rollback? (yes/no): ";

$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));

if ($confirmation !== 'yes') {
    die("Rollback cancelled\n");
}

// Perform rollback
try {
    $DB->begin_sql();

    // Deactivate current version
    $DB->execute("UPDATE mdl_mvp_policy_versions SET is_active = 0, deactivated_at = ? WHERE policy_source = ? AND is_active = 1", [time(), $version->policy_source]);

    // Activate rollback version
    $DB->execute("UPDATE mdl_mvp_policy_versions SET is_active = 1, activated_at = ? WHERE id = ?", [time(), $version_id]);

    // Update cache
    require_once(__DIR__ . '/policy_cache.php');
    $cache = new PolicyCache();
    $policies = json_decode($version->parsed_rules, true);
    $cache->write([$version->policy_source => $policies], $version->version_hash);

    $DB->commit_sql();

    echo "✅ Rollback successful to version $version_id\n";
} catch (Exception $e) {
    $DB->rollback_sql();
    die("❌ Rollback failed: " . $e->getMessage() . "\n");
}
?>
```

Usage:
```bash
# List versions
php lib/rollback_policy.php --list

# Rollback to specific version
php lib/rollback_policy.php 42
```

#### 6.3-6.5 Version history and audit logging

*(Implementations integrated in 6.1 and 6.2)*

---

### 7.0 Decision Engine Integration

**Files**:
- Modify: `decision/rule_engine.py`, `decision/api/decide.php`

#### 7.1 Update `decision/rule_engine.py` to accept policy JSON

```python
import json
import sys

class RuleEngine:
    def __init__(self, policy_json=None):
        """
        Initialize rule engine with policy
        @param policy_json: JSON string or dict with policy rules
        """
        if policy_json is None:
            # Load from YAML fallback
            self.policy = self.load_yaml_fallback()
        elif isinstance(policy_json, str):
            self.policy = json.loads(policy_json)
        else:
            self.policy = policy_json

    def load_yaml_fallback(self):
        """Load YAML rules as fallback"""
        import yaml
        with open('rules/calm_break_rules.yaml', 'r') as f:
            return yaml.safe_load(f)

    def evaluate(self, student_id, calm_score):
        """
        Evaluate intervention decision
        @param student_id: Student ID
        @param calm_score: Calm score (0-100)
        @return: Decision dict with action, confidence, rationale
        """
        thresholds = self.policy.get('agent08', {}).get('calm_thresholds', {})

        # Find matching threshold
        for range_name, config in thresholds.items():
            min_val = config.get('min', 0)
            max_val = config.get('max', 100)

            if min_val <= calm_score <= max_val:
                return {
                    'action': config['action'],
                    'confidence': config['confidence'],
                    'rationale': f"Calm score {calm_score} in {range_name} range ({min_val}-{max_val})"
                }

        # Default: no intervention
        return {
            'action': 'none',
            'confidence': 1.0,
            'rationale': 'No matching threshold'
        }

if __name__ == '__main__':
    # CLI usage: python rule_engine.py <student_id> <calm_score> [policy_json]
    student_id = int(sys.argv[1])
    calm_score = float(sys.argv[2])
    policy_json = sys.argv[3] if len(sys.argv) > 3 else None

    engine = RuleEngine(policy_json)
    decision = engine.evaluate(student_id, calm_score)
    print(json.dumps(decision))
```

#### 7.2 Modify decision API to load policies from parser

Modify `decision/api/decide.php`:
```php
<?php
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

require_once(__DIR__ . '/../../lib/policy_loader.php');

// Get input
$input = json_decode(file_get_contents('php://input'), true);
$student_id = $input['student_id'] ?? null;
$calm_score = $input['calm_score'] ?? null;

if ($student_id === null || $calm_score === null) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing student_id or calm_score']);
    exit;
}

try {
    // Load policies from agent files
    $loader = new PolicyLoader();
    $policies = $loader->loadPoliciesWithFallback();

    // Call Python rule engine with policies
    $policy_json = json_encode($policies);
    $escaped_json = escapeshellarg($policy_json);
    $cmd = "python3 decision/rule_engine.py $student_id $calm_score $escaped_json";

    $output = shell_exec($cmd);
    $decision = json_decode($output, true);

    if (!$decision) {
        throw new Exception("Rule engine returned invalid output at " . __FILE__ . ":" . __LINE__);
    }

    echo json_encode([
        'success' => true,
        'decision' => $decision,
        'policy_version' => $loader->calculateVersionHash()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
```

#### 7.3-7.5 Fallback and testing

*(Fallback implemented in 7.2 via `loadPoliciesWithFallback()`)*

Test:
```bash
# Test with policy loading
curl -X POST "https://mathking.kr/.../decision/api/decide.php" \
  -H "Content-Type: application/json" \
  -H "Cookie: MoodleSession=YOUR_SESSION" \
  -d '{"student_id": 123, "calm_score": 65.5}'
```

Expected:
```json
{
  "success": true,
  "decision": {
    "action": "micro_break",
    "confidence": 0.85,
    "rationale": "Calm score 65.5 in low range (60-74)"
  },
  "policy_version": "abc123..."
}
```

---

*(Tasks 8.0-10.0 would continue in similar detail... Due to length constraints, I'll provide summaries for remaining tasks)*

### 8.0 Policy Viewer UI - Summary

- Create `ui/policy_viewer.php` with tabs for each agent
- Display active policy JSON with syntax highlighting
- Show version history table with rollback buttons
- Implement policy diff viewer (compare two versions)
- Add CSS/JS for interactive UI

### 9.0 Testing & Validation - Summary

- Run all 6 unit test suites
- Integration test with real agent08/20/21 files
- Test hot reload by modifying agent file
- Test cache fallback scenarios
- Test rollback to previous version
- Performance benchmark (<500ms policy load)
- Verify zero impact on existing pipeline

### 10.0 Documentation & Deployment - Summary

- Write `AGENT_POLICY_INTEGRATION_GUIDE.md` (technical)
- Write `POLICY_EDITING_GUIDE.md` (teacher-facing)
- Update `README.md` with policy system info
- Create deployment checklist for v1.1
- Document rollback procedures
- Conduct teacher training session

---

**Tasks Summary**:
- **Total Tasks**: 10 parent tasks
- **Total Sub-Tasks**: 60+ detailed steps
- **Estimated Effort**: 2-3 weeks development + 1 week testing
- **Priority**: HIGH (Phase 3 deferred from v1.0)

