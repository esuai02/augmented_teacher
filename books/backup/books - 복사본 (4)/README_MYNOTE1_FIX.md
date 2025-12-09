# mynote1.php HTTP ERROR 500 Fix Documentation

## Problem Identified
The HTTP ERROR 500 on mynote1.php was caused by:

1. **Hardcoded Moodle config path** - The file referenced `/home/moodle/public_html/moodle/config.php` which doesn't exist in the current environment
2. **SQL Injection vulnerabilities** - Direct variable insertion in SQL queries without proper escaping
3. **Missing error handling** - No fallback for when Moodle is not installed
4. **Unvalidated GET parameters** - Direct use of $_GET values without validation

## Fixes Applied

### 1. Dynamic Configuration Loading
- Added multiple config path checking
- Implemented fallback to standalone mode when Moodle is not available
- Created MockDB and MockUser classes for standalone operation
- Added external db_config.php for database configuration

### 2. Security Improvements
- **Input Validation**: All GET parameters are now validated and sanitized
- **SQL Injection Prevention**: Converted all SQL queries to use prepared statements with placeholders
- **Error Handling**: Added proper error handling for database operations
- **XSS Prevention**: Used htmlspecialchars() for output of user-provided data

### 3. Database Configuration
Created `db_config.php` to store database credentials:
```php
return [
    'host' => 'localhost',
    'dbname' => 'moodle',
    'username' => 'your_db_user',
    'password' => 'your_db_password',
    'charset' => 'utf8mb4',
    'port' => 3306,
];
```

## Configuration Steps

### For Moodle Environment
1. Ensure Moodle is properly installed
2. The script will automatically detect and use Moodle's config.php

### For Standalone Mode
1. Edit `db_config.php` with your database credentials:
   ```bash
   nano books/db_config.php
   ```

2. Update the database connection details:
   - host: Your MySQL server (usually 'localhost')
   - dbname: Your database name
   - username: Database username
   - password: Database password

3. Ensure your database has the required Moodle tables:
   - mdl_user
   - mdl_user_info_data
   - mdl_icontent_pages
   - mdl_abessi_today
   - mdl_abessi_messages
   - mdl_abessi_questionstamp

## Testing

### 1. Run the test script
Visit: `https://mathking.kr/moodle/local/augmented_teacher/books/test_mynote1.php`

This will check:
- Moodle configuration availability
- Database connectivity
- Required files
- PHP configuration

### 2. Test with sample parameters
- Basic test: `mynote1.php?cid=1&nch=1&cmid=1&dmn=test&page=1&studentid=1`
- Quiz mode: `mynote1.php?cid=1&nch=1&cmid=1&dmn=test&page=1&pgtype=quiz&quizid=1&studentid=1`

## Troubleshooting

### If you still get HTTP ERROR 500:

1. **Check PHP Error Logs**
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/nginx/error.log
   ```

2. **Verify Database Access**
   - Check db_config.php has correct credentials
   - Test database connection with test_mynote1.php
   - Ensure database user has SELECT, INSERT, UPDATE permissions

3. **Check File Permissions**
   ```bash
   chmod 644 books/mynote1.php
   chmod 644 books/db_config.php
   ```

4. **PHP Requirements**
   - PHP 7.2 or higher
   - PDO MySQL extension enabled
   - Memory limit: At least 128MB
   - Max execution time: At least 30 seconds

## Security Recommendations

1. **Move db_config.php outside web root**
   ```php
   $db_config_file = __DIR__ . '/../../config/db_config.php';
   ```

2. **Restrict database user permissions**
   - Grant only necessary permissions (SELECT, INSERT, UPDATE)
   - Avoid using root user

3. **Enable HTTPS**
   - Use SSL/TLS for all connections
   - Update URLs in the code to use https://

4. **Regular Updates**
   - Keep PHP updated
   - Monitor for security vulnerabilities
   - Regular code audits

## Files Modified
- `mynote1.php` - Main file with fixes
- `db_config.php` - Database configuration (created)
- `test_mynote1.php` - Test script (created)

## Next Steps
1. Configure database settings in db_config.php
2. Run test_mynote1.php to verify configuration
3. Test mynote1.php with real parameters
4. Monitor error logs for any remaining issues