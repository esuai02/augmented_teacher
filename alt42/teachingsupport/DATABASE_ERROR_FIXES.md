# Database Error Fixes for Student Message Inbox System

## Issues Identified and Fixed

### 1. **Table Name Issues in get_student_messages.php**

**Problem**: The code was using inconsistent table names that don't match the actual Moodle database structure.

**Original Code Issues**:
- Used `{messages}` but should be `{message}` 
- Used `{users}` but should be `{user}`
- No fallback for different Moodle versions

**Fixed Code**:
- Added dynamic table detection
- Implemented fallback mechanisms
- Added error handling for missing tables

### 2. **SQL Query Robustness**

**Problem**: Complex JOIN queries were failing when `message_read` table doesn't exist or has different structure.

**Solutions Applied**:
- Added try-catch blocks around SQL queries
- Implemented fallback to simpler queries when complex ones fail
- Added graceful degradation when `message_read` table is missing

### 3. **Error Handling Improvements**

**Problem**: Generic error messages didn't provide enough information for debugging.

**Improvements**:
- Added detailed error messages
- Included debugging information in development mode
- Added graceful fallback when messaging system is not available

### 4. **mark_message_read.php Fixes**

**Problem**: Same table name issues as the main file.

**Fixes**:
- Added table existence checks
- Implemented proper error handling
- Added fallback when `message_read` table doesn't exist

## Files Modified

1. **get_student_messages.php** - Main API file
2. **mark_message_read.php** - Read status update file
3. **debug_database.php** - New debugging tool
4. **test_student_messages.php** - New testing tool

## Key Improvements

### Dynamic Table Detection
```php
// Check what tables actually exist
$tables = $DB->get_tables();
$message_table = 'message';
if (in_array('messages', $tables)) {
    $message_table = 'messages';
} elseif (in_array('message', $tables)) {
    $message_table = 'message';
} else {
    throw new Exception('메시지 테이블을 찾을 수 없습니다.');
}
```

### Fallback Query Strategy
```php
try {
    // Try complex query with JOINs
    $sql = "SELECT m.*, u.firstname, u.lastname, u.email,
                   CASE WHEN mr.id IS NOT NULL THEN 1 ELSE 0 END as is_read
            FROM {" . $message_table . "} m
            JOIN {user} u ON m.useridfrom = u.id
            LEFT JOIN {message_read} mr ON m.id = mr.messageid AND mr.userid = m.useridto
            WHERE m.useridto = ? 
            AND (m.subject LIKE '%문제 해설%' OR m.subject LIKE '%하이튜터링%')
            ORDER BY m.timecreated DESC
            LIMIT ? OFFSET ?";
    
    $messages = $DB->get_records_sql($sql, array($studentid, $perpage, $offset));
} catch (Exception $sql_error) {
    // Fall back to simpler query
    $sql = "SELECT m.*, u.firstname, u.lastname, u.email
            FROM {" . $message_table . "} m
            JOIN {user} u ON m.useridfrom = u.id
            WHERE m.useridto = ? 
            AND (m.subject LIKE '%문제 해설%' OR m.subject LIKE '%하이튜터링%')
            ORDER BY m.timecreated DESC
            LIMIT ? OFFSET ?";
    
    $messages = $DB->get_records_sql($sql, array($studentid, $perpage, $offset));
    
    // Manually set is_read field
    foreach ($messages as $message) {
        $message->is_read = 0;
    }
}
```

### Enhanced Error Response
```php
} catch (Exception $e) {
    $error_details = array(
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => $e->getCode()
    );
    
    // Add debug info in development mode
    if (defined('MOODLE_INTERNAL') && debugging()) {
        $error_details['debug_info'] = array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        );
    }
    
    echo json_encode($error_details);
}
```

## Testing and Debugging

### New Testing Tools Created

1. **debug_database.php** - Comprehensive database structure analysis
2. **test_student_messages.php** - API testing and validation

### How to Test

1. Access `test_student_messages.php` in your browser
2. Check the API response for errors
3. Review database table structure information
4. Use the links to test the actual inbox functionality

## Common Issues and Solutions

### Issue: "데이터베이스 읽기 오류" (Database Read Error)
**Cause**: Table names don't match Moodle installation
**Solution**: Use dynamic table detection code implemented in fixes

### Issue: Empty message list with no errors
**Cause**: No messages match the filter criteria
**Solution**: Check if messages exist with subjects containing "문제 해설" or "하이튜터링"

### Issue: "메시지 테이블을 찾을 수 없습니다"
**Cause**: Moodle messaging system not installed/configured
**Solution**: Enable Moodle messaging or create mock data for testing

## Recommendations for Further Development

1. **Add Configuration File**: Create a config file for table name mappings
2. **Implement Caching**: Cache table structure information
3. **Add Message Creation Tools**: Create tools to generate test messages
4. **Improve User Experience**: Add loading indicators and better error messages
5. **Add Logging**: Implement comprehensive logging for debugging

## Database Schema Expected

The system expects these tables to exist:
- `message` or `messages` - Contains message data
- `user` - User information
- `message_read` - Read status tracking (optional)
- `ktm_teaching_events` - Event logging (optional)

If tables don't exist, the system will now gracefully degrade functionality rather than throwing errors.