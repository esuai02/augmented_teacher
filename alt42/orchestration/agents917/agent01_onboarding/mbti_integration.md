# MBTI Integration Documentation

**Created**: 2025-01-22
**Agent**: agent01_onboarding
**Purpose**: Integration of MBTI data from mdl_abessi_mbtilog table with onboarding report system

## Overview

This integration adds MBTI personality type management to the onboarding report system, allowing users to:
- View their latest MBTI type in the onboarding report
- Add or update MBTI directly from the onboarding panel
- See MBTI with timestamp information

## Database Integration

### Source Table: mdl_abessi_mbtilog

```sql
CREATE TABLE mdl_abessi_mbtilog (
    id BIGINT(10) PRIMARY KEY AUTO_INCREMENT,
    userid BIGINT(10) NOT NULL,
    type VARCHAR(50),
    mbti VARCHAR(10) NOT NULL,
    timecreated BIGINT(10) NOT NULL
);
```

### Key Features
- **Case-Insensitive Storage**: All MBTI values normalized to uppercase
- **Latest Record Selection**: Uses ORDER BY timecreated DESC LIMIT 1
- **Timestamp Tracking**: Records when MBTI was added

## Implementation Details

### 1. Backend Data Retrieval (report_service.php:52-65)

```php
// Get latest MBTI from mdl_abessi_mbtilog (case-insensitive)
$mbtiLog = $DB->get_record_sql(
    "SELECT * FROM {abessi_mbtilog}
     WHERE userid = ?
     ORDER BY timecreated DESC
     LIMIT 1",
    [$userid]
);

if ($mbtiLog && !empty($mbtiLog->mbti)) {
    // Store MBTI in uppercase for consistency
    $infoData['mbti_type'] = strtoupper($mbtiLog->mbti);
    $infoData['mbti_timecreated'] = $mbtiLog->timecreated;
    $infoData['mbti_log_id'] = $mbtiLog->id;
}
```

**Key Logic**:
- Fetches most recent MBTI record for user
- Converts to uppercase for consistency
- Stores additional metadata (timestamp, log ID)

### 2. MBTI Save Endpoint (report_service.php:184-240)

```php
case 'saveMBTI':
    $mbti = isset($_POST['mbti']) ? trim($_POST['mbti']) : '';

    // Validation
    if (empty($mbti)) {
        echo json_encode(['success' => false, 'error' => 'MBTI value is required']);
        exit;
    }

    if (strlen($mbti) !== 4) {
        echo json_encode(['success' => false, 'error' => 'MBTI must be 4 characters']);
        exit;
    }

    // Convert to uppercase and save
    $mbtiUpper = strtoupper($mbti);

    $record = new stdClass();
    $record->userid = $userid;
    $record->mbti = $mbtiUpper;
    $record->timecreated = time();

    $mbtiId = $DB->insert_record('abessi_mbtilog', $record);

    echo json_encode([
        'success' => true,
        'mbti_id' => $mbtiId,
        'mbti' => $mbtiUpper,
        'timecreated' => $record->timecreated
    ]);
```

**Validation Rules**:
- Required field check
- Exactly 4 characters
- Automatic uppercase conversion
- Error messages include file/line info

### 3. Report Display (report_generator.php:56-62)

```php
if (!empty($info['mbti_type'])) {
    $mbtiDisplay = htmlspecialchars($info['mbti_type']);
    if (!empty($info['mbti_timecreated'])) {
        $mbtiDisplay .= ' <small style="color: #6b7280;">(' .
                        date('Y-m-d H:i', $info['mbti_timecreated']) . ')</small>';
    }
    $html .= '<tr><td><strong>MBTI:</strong></td><td>' . $mbtiDisplay . '</td></tr>';
}
```

**Display Format**: `ENFP (2025-01-22 14:30)`

### 4. Frontend UI (panel.js:94-158)

#### MBTI Input Form
```javascript
displayReport: function(reportHTML) {
    // ... existing report display ...

    actionsDiv.innerHTML = `
        <div class="mbti-section">
            <h4>MBTI 추가/변경</h4>
            <div class="mbti-input-group">
                <input type="text" id="mbtiInput" maxlength="4"
                       placeholder="ENFP, ISTJ 등" class="mbti-input">
                <button class="btn-save-mbti" onclick="OnboardingPanel.saveMBTI()">
                    MBTI 저장
                </button>
            </div>
        </div>
    `;
}
```

#### Save Logic
```javascript
saveMBTI: function() {
    const mbti = document.getElementById('mbtiInput').value.trim();

    // Validation
    if (!mbti || mbti.length !== 4) {
        alert('MBTI는 4자리여야 합니다 (예: ENFP, ISTJ)');
        return;
    }

    // AJAX save
    fetch('/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/report_service.php', {
        method: 'POST',
        body: `action=saveMBTI&userid=${this.currentUserId}&mbti=${encodeURIComponent(mbti)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.showNotification('MBTI가 저장되었습니다!', 'success');
            this.loadPanelContent(this.currentUserId); // Reload to show new MBTI
        }
    });
}
```

**User Experience**:
- Input auto-converts to uppercase (CSS text-transform)
- Maxlength="4" prevents invalid input
- Success notification with toast
- Automatic panel refresh after save

### 5. Styling (panel.css:208-256)

```css
.mbti-section {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.mbti-input {
    flex: 1;
    padding: 10px 12px;
    border: 2px solid #e5e7eb;
    border-radius: 6px;
    font-size: 16px;
    text-transform: uppercase; /* Auto-uppercase display */
    transition: border-color 0.2s;
}

.btn-save-mbti {
    background: linear-gradient(90deg, #8b5cf6 0%, #6366f1 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
}
```

**Design Features**:
- Light gray background for MBTI section
- Purple gradient button (distinct from regenerate button)
- Uppercase text transform for visual consistency
- Focus state with blue border

## User Workflow

### Viewing MBTI
1. User clicks onboarding agent card
2. Panel slides in from right
3. Report displays with MBTI (if exists) showing type and timestamp
4. Example display: **MBTI**: ENFP (2025-01-22 14:30)

### Adding/Updating MBTI
1. User sees "MBTI 추가/변경" section in report view
2. User enters 4-character MBTI code (e.g., "enfp", "ISTJ")
3. User clicks "MBTI 저장" button
4. System validates (4 characters, non-empty)
5. System converts to uppercase and saves to mdl_abessi_mbtilog
6. Success notification appears
7. Panel automatically reloads showing new MBTI

## API Endpoints

### POST /report_service.php?action=saveMBTI

**Request Parameters**:
- `userid` (int): User ID
- `mbti` (string): 4-character MBTI code (case-insensitive)

**Success Response**:
```json
{
    "success": true,
    "mbti_id": 123,
    "mbti": "ENFP",
    "timecreated": 1705910400,
    "message": "MBTI saved successfully"
}
```

**Error Response**:
```json
{
    "success": false,
    "error": "MBTI must be 4 characters (e.g., ENFP, ISTJ)",
    "file": "/path/to/report_service.php",
    "line": 203
}
```

## Testing Checklist

### Backend Testing
- [ ] MBTI fetches latest record correctly
- [ ] Case-insensitive MBTI handling (enfp → ENFP)
- [ ] Validation rejects empty MBTI
- [ ] Validation rejects non-4-character MBTI
- [ ] Save endpoint creates new record with timestamp
- [ ] Error messages include file/line info

### Frontend Testing
- [ ] MBTI input field appears in report view
- [ ] Placeholder text displays correctly
- [ ] Maxlength limits input to 4 characters
- [ ] Text auto-converts to uppercase visually
- [ ] Save button triggers AJAX request
- [ ] Success notification appears after save
- [ ] Panel reloads automatically showing new MBTI
- [ ] Error messages display for validation failures

### Integration Testing
- [ ] New MBTI appears in report after save
- [ ] Timestamp displays correctly (Y-m-d H:i format)
- [ ] Multiple MBTI entries - only latest shows
- [ ] Report regeneration includes updated MBTI

## Error Handling

### Client-Side Validation
- Empty input: "MBTI를 입력해주세요 (예: ENFP, ISTJ)"
- Invalid length: "MBTI는 4자리여야 합니다 (예: ENFP, ISTJ)"
- Network error: "네트워크 오류: [error message]"

### Server-Side Validation
- Missing userid: "Invalid user ID"
- Empty MBTI: "MBTI value is required"
- Invalid length: "MBTI must be 4 characters (e.g., ENFP, ISTJ)"
- Database error: Exception message with file/line info

## File Changes Summary

### Modified Files
1. **report_service.php** (Lines 52-65, 184-240)
   - Added MBTI fetching from mdl_abessi_mbtilog
   - Added saveMBTI endpoint

2. **report_generator.php** (Lines 56-62)
   - Enhanced MBTI display with timestamp

3. **ui/panel.js** (Lines 94-158)
   - Added MBTI input form to report display
   - Implemented saveMBTI() method

4. **ui/panel.css** (Lines 208-256)
   - Added styling for MBTI section and input

### New Files
- `mbti_integration.md` (this document)

## Future Enhancements

### Potential Improvements
1. **MBTI History View**: Show list of all past MBTI entries
2. **MBTI Validation**: Validate against 16 valid MBTI types
3. **MBTI Analysis**: Display personality insights based on MBTI
4. **MBTI Change Tracking**: Show when MBTI changed and why
5. **Bulk MBTI Import**: Import MBTI data from external sources

### Known Limitations
- No validation against 16 official MBTI types (accepts any 4 characters)
- No MBTI edit functionality (can only add new entries)
- No MBTI history view (only latest is shown)

## Technical Notes

### Case-Insensitive Handling Strategy
- **Input**: Accept any case (enfp, ENFP, EnFp)
- **Storage**: Always uppercase in database
- **Display**: Always uppercase in UI
- **Comparison**: Use strtoupper() for queries

### Timestamp Format
- **Storage**: Unix timestamp (seconds since epoch)
- **Display**: Y-m-d H:i (2025-01-22 14:30)
- **Timezone**: Server timezone (adjust if needed)

### Security Considerations
- Input sanitization with trim()
- SQL injection prevention with Moodle XMLDB API
- XSS prevention with htmlspecialchars()
- Authentication required (require_login())

## Troubleshooting

### MBTI Not Appearing in Report
1. Check if user has MBTI in mdl_abessi_mbtilog
2. Verify query: `SELECT * FROM mdl_abessi_mbtilog WHERE userid = ? ORDER BY timecreated DESC LIMIT 1`
3. Check error logs for SQL errors

### MBTI Save Fails
1. Check browser console for AJAX errors
2. Check network tab for 200 response
3. Verify POST data includes action=saveMBTI, userid, mbti
4. Check server error logs: /var/log/apache2/error.log

### MBTI Timestamp Wrong
1. Verify server timezone setting
2. Check PHP date.timezone configuration
3. Adjust display format if needed

## Related Documentation

- [Integration Guide](integration_guide.md) - Main setup documentation
- [Database Schema](db_schema.md) - Complete database structure
- [Test Integration](test_integration.php) - Standalone test page

## Support

For issues or questions:
1. Check integration_guide.md troubleshooting section
2. Review browser console for JavaScript errors
3. Check server error logs for PHP errors
4. Verify database table exists and has data
