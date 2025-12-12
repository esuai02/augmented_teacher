# Integration Guide for Agent01 Onboarding Panel

## Requirements

### HTML Inclusion
Add these to the main orchestration page `<head>`:

```html
<link rel="stylesheet" href="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.css">
<script src="/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/ui/panel.js"></script>
```

### Agent Card Click Handler
Add click event to agent01_onboarding card:

```javascript
// Find the agent01_onboarding card
const agent01Card = document.querySelector('[data-agent-id="agent01_onboarding"]');

if (agent01Card) {
    agent01Card.addEventListener('click', function(e) {
        e.preventDefault();

        // Get current user ID (adjust based on your system)
        const userid = window.currentUserId || <?php echo $USER->id; ?>;

        // Open the panel
        OnboardingPanel.open(userid);
    });
}
```

### Required Data Attribute
Ensure agent card has data attribute:

```html
<div class="agent-card" data-agent-id="agent01_onboarding">
    <!-- Card content -->
</div>
```

## Database Setup

### Step 1: Create Reports Table
Access as admin/teacher:
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/agents/agent01_onboarding/db_setup.php
```

Expected result:
```json
{
    "success": true,
    "message": "Table alt42o_onboarding_reports created successfully"
}
```

### Step 2: Verify Table Creation
Check in phpMyAdmin or MySQL console:
```sql
SHOW TABLES LIKE 'mdl_alt42o_onboarding_reports';
SELECT COUNT(*) FROM mdl_alt42o_onboarding_reports;
```

## Testing Checklist

### Database
- [ ] `alt42o_onboarding_reports` table exists
- [ ] Table has correct structure (10 fields with indexes)
- [ ] Can query table without errors

### Backend Services
- [ ] `report_service.php` accessible
- [ ] `report_generator.php` accessible
- [ ] AJAX endpoints respond with JSON
- [ ] Error messages include file/line info

### Frontend UI
- [ ] Panel CSS loads without conflicts
- [ ] Panel JS initializes without errors
- [ ] Click on agent01 card opens panel
- [ ] Panel slides in from right
- [ ] Close button works
- [ ] Click outside panel closes it
- [ ] Escape key closes panel

### Report Generation
- [ ] "Generate Report" button appears when no report exists
- [ ] Report generation completes successfully
- [ ] Generated report displays correctly
- [ ] Report shows basic info section
- [ ] Report shows student profile section (if data exists)
- [ ] Report shows assessment scores (if data exists)
- [ ] "Regenerate Report" button appears after generation
- [ ] Regeneration works and archives old report

### Error Handling
- [ ] Network errors display properly
- [ ] Server errors show file/line information
- [ ] "Retry" button works after errors
- [ ] Empty data shows friendly messages

### Mobile Responsive
- [ ] Panel full width on mobile (<768px)
- [ ] Touch interactions work
- [ ] Scrolling works smoothly
- [ ] Buttons are touch-friendly

## Troubleshooting

### Panel doesn't open
1. Check browser console for JavaScript errors
2. Verify panel.js is loaded: `console.log(window.OnboardingPanel)`
3. Verify agent card has correct data attribute
4. Check click event handler is attached

### "Generate Report" doesn't work
1. Check network tab for AJAX request
2. Verify userid is being passed correctly
3. Check server error logs for PHP errors
4. Verify database tables exist

### Report data is empty
1. Check if user has data in `mdl_alt42_student_profiles`
2. Check if user has completed `onboarding_learningtype.php`
3. Verify table names match in PHP files
4. Check database field names are correct

### CSS conflicts
1. Namespace all panel styles with `.onboarding-right-panel`
2. Check z-index conflicts (panel uses z-index: 1000)
3. Verify no global styles override panel styles

## API Endpoints

### report_service.php

**POST /report_service.php**

Actions:
- `getOnboardingData` - Get combined user data
  - Params: `userid` (int)
  - Returns: `{success, info, assessment, timestamp}`

- `checkExistingReport` - Check for existing reports
  - Params: `userid` (int)
  - Returns: `{success, exists, report}`

### report_generator.php

**POST /report_generator.php**

Actions:
- `generateReport` - Generate and save new report
  - Params: `userid` (int)
  - Returns: `{success, reportId, reportHTML, reportType, message}`

## File Structure

```
agent01_onboarding/
├── db_schema.md                 # Database documentation
├── db_setup.php                 # Database creation script
├── report_service.php           # Data retrieval service
├── report_generator.php         # Report generation engine
├── integration_guide.md         # This file
├── test_integration.php         # Standalone test page
└── ui/
    ├── panel.css                # Panel styles
    └── panel.js                 # Panel controller
```

## Next Steps

1. **Run database setup** (manual step required)
2. **Add CSS/JS to main orchestration page**
3. **Add click handler to agent01 card**
4. **Test with test_integration.php**
5. **Test in production orchestration page**
6. **Verify report generation with real user data**
