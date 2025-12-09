# Server Testing Instructions

## Task 1: MVP Exception Hierarchy (COMPLETED)

### Quick Start

1. **Upload files to server** (if not synced automatically)

2. **Navigate to directory:**
```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
```

3. **Run tests:**
```bash
php tests/unit/MvpExceptionTest.php
```

4. **Expected output:**
```
=== Running MvpException Tests ===
✓ Base exception message test passed
✓ Connection exception inheritance test passed
✓ Query exception context test passed
✓ Log format test passed
✅ All MvpException tests passed
```

5. **If all tests pass, commit:**
```bash
git add lib/MvpException.php tests/unit/MvpExceptionTest.php
git commit -m "feat: add MVP exception hierarchy with context and logging

- Add MvpException base class with context support
- Add 3-level hierarchy: Connection, Query, Data exceptions
- Add getDetailedMessage() for debugging
- Add toLogFormat() for structured error logging
- Include unit tests for all exception types

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```

### Files Created (Task 1)

1. `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/lib/MvpException.php` (2.3KB)
2. `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/tests/unit/MvpExceptionTest.php` (2.1KB)

---

## Task 2: Configuration Loader (CURRENT)

### Quick Start

1. **Upload files to server** (if not synced automatically)

2. **Navigate to directory:**
```bash
cd /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system
```

3. **Run tests:**
```bash
php tests/unit/MvpConfigTest.php
```

4. **Expected output:**
```
=== Running MvpConfig Tests ===
✓ Moodle config path test passed
  Path: /home/moodle/public_html/moodle/config.php
✓ Database config structure test passed
  Host: localhost
  Database: mathking
  Prefix: mdl_
✅ All MvpConfig tests passed
```

5. **If all tests pass, commit:**
```bash
git add lib/MvpConfig.php tests/unit/MvpConfigTest.php
git commit -m "feat: add MVP configuration loader

- Add MvpConfig class to extract DB config from Moodle
- Add validation for required configuration keys
- Add getMoodleConfigPath() for flexible config location
- Include unit tests for configuration loading
- All error messages include file:line location per project standards

🤖 Generated with [Claude Code](https://claude.com/claude-code)

Co-Authored-By: Claude <noreply@anthropic.com>"
```

### Files Created (Task 2)

1. `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/lib/MvpConfig.php` (2.2KB)
2. `/mnt/c/1 Project/augmented_teacher/alt42/orchestration/mvp_system/tests/unit/MvpConfigTest.php` (1.8KB)

### Alternative Test Method (Task 2 - Web)

Access via browser: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/test_config_web.php`

**Expected output:**
```
=== Testing MvpConfig via Web ===

✓ Test 1: getMoodleConfigPath()
  Path: /home/moodle/public_html/moodle/config.php
  Exists: Yes

✓ Test 2: getDatabaseConfig()
  Host: localhost
  Database: mathking
  User: [actual_user]
  Prefix: mdl_
  Charset: utf8mb4
  Collation: utf8mb4_unicode_ci

✓ Test 3: validateConfig()
  Valid: Yes

✓ Test 4: validateConfig() properly rejects invalid config
  Error: [lib/MvpConfig.php:65] Database configuration missing required key: name

✅ All MvpConfig web tests completed
```

---

## Alternative Test Method (Task 1 - Web)

You can also test via web browser by creating a test endpoint:

```php
<?php
// File: test_exceptions_web.php
header('Content-Type: text/plain; charset=utf-8');

require_once(__DIR__ . '/lib/MvpException.php');

echo "=== Testing MvpException via Web ===\n\n";

// Test 1
try {
    throw new MvpException("Test error message", 500);
} catch (MvpException $e) {
    echo "✓ Test 1: Exception created successfully\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  Code: " . $e->getCode() . "\n\n";
}

// Test 2
$exc = new MvpConnectionException("Connection failed");
echo "✓ Test 2: MvpConnectionException created\n";
echo "  Is MvpException: " . ($exc instanceof MvpException ? "Yes" : "No") . "\n\n";

// Test 3
$exc2 = new MvpQueryException("Query failed", 0, [
    'sql' => 'SELECT * FROM test',
    'params' => ['value1']
]);
$context = $exc2->getContext();
echo "✓ Test 3: Context storage works\n";
echo "  SQL: " . $context['sql'] . "\n";
echo "  Params: " . json_encode($context['params']) . "\n\n";

// Test 4
$exc3 = new MvpDataException("Duplicate key", 0, [
    'key' => 'test_key',
    'value' => 'test_value'
]);
echo "✓ Test 4: Log format\n";
echo $exc3->toLogFormat() . "\n";

echo "\n✅ All manual tests completed\n";
?>
```

Access via: `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/test_exceptions_web.php`

## Troubleshooting

### If PHP is not found:
```bash
which php
# or
/usr/bin/php tests/unit/MvpExceptionTest.php
```

### If file permissions issue:
```bash
chmod +x run_tests.sh
chmod 644 lib/MvpException.php
chmod 644 tests/unit/MvpExceptionTest.php
```

### If path issues:
```bash
# Verify files exist
ls -la lib/MvpException.php
ls -la tests/unit/MvpExceptionTest.php

# Check file contents
head -20 lib/MvpException.php
```
