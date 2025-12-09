# Routine Coach Widget Injection Guide

## Overview
The Routine Coach widget can be injected into any Moodle page to display today's tasks and allow users to track their exam preparation progress.

## Automatic Injection
The widget automatically injects itself on these pages:
- `/augmented_teacher/students/today42.php`
- `/alt42/studenthome/index.php`
- `/local/augmented_teacher/students/today42.php`
- `/moodle/local/augmented_teacher/students/today42.php`

## Manual Injection Methods

### Method 1: AMD Module Initialization
Add this to any Moodle page where you want the widget to appear:

```php
// In your PHP file
$PAGE->requires->js_call_amd('local_routinecoach/routinecoach', 'init', [
    ['userid' => $USER->id]
]);
```

### Method 2: Direct JavaScript Injection
For immediate widget injection via JavaScript:

```javascript
require(['local_routinecoach/routinecoach'], function(RoutineCoach) {
    // Initialize with current user
    RoutineCoach.init({
        userid: M.cfg.userid || <?php echo $USER->id; ?>
    });
    
    // Force widget injection
    RoutineCoach.injectWidget(M.cfg.userid);
});
```

### Method 3: HTML/JavaScript Snippet
Add this snippet to any HTML page:

```html
<!-- Routine Coach Widget Injection -->
<script>
require(['jquery', 'core/ajax'], function($, Ajax) {
    // Check if widget already exists
    if ($('#routinecoach-widget').length > 0) {
        return;
    }
    
    // Create widget container
    var widgetHtml = '<div id="routinecoach-widget-loader" style="' +
        'position: fixed; bottom: 20px; right: 20px; ' +
        'padding: 10px; background: white; border-radius: 8px; ' +
        'box-shadow: 0 2px 8px rgba(0,0,0,0.1); z-index: 1000;">' +
        '<div>Loading Routine Coach...</div>' +
        '</div>';
    
    $('body').append(widgetHtml);
    
    // Load the module
    require(['local_routinecoach/routinecoach'], function(RoutineCoach) {
        $('#routinecoach-widget-loader').remove();
        RoutineCoach.init({
            userid: M.cfg.userid
        });
        RoutineCoach.injectWidget(M.cfg.userid);
    });
});
</script>
```

### Method 4: Custom Page Integration
For custom pages outside of Moodle:

```html
<!DOCTYPE html>
<html>
<head>
    <!-- Include jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <!-- Your page content -->
    
    <!-- Routine Coach Widget -->
    <script>
    $(document).ready(function() {
        var apiBaseUrl = '/local/routinecoach/index.php';
        var userId = <?php echo $USER->id; ?>; // Get from session
        
        // Load widget data
        $.ajax({
            url: apiBaseUrl,
            type: 'GET',
            data: {
                view: 'today',
                userid: userId,
                format: 'json'
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    renderWidget(response.data);
                }
            }
        });
        
        function renderWidget(data) {
            // Widget rendering logic
            var widgetHtml = buildWidgetHtml(data);
            $('body').append(widgetHtml);
            bindWidgetEvents();
        }
    });
    </script>
</body>
</html>
```

## Configuration Options

### Widget Position
Modify the widget position by overriding CSS:

```css
#routinecoach-widget {
    bottom: 50px !important; /* Adjust vertical position */
    right: 50px !important;  /* Adjust horizontal position */
}
```

### Widget Size
Adjust the widget dimensions:

```css
#routinecoach-widget {
    width: 400px !important; /* Wider widget */
    max-height: 500px !important; /* Taller widget */
}
```

### Custom Styling
Apply custom theme colors:

```css
#routinecoach-widget .widget-header {
    background: linear-gradient(135deg, #your-color-1 0%, #your-color-2 100%) !important;
}
```

## API Endpoints

### Get Today's Tasks
```
GET /local/routinecoach/index.php?view=today&userid={userid}&format=json
```

Response:
```json
{
    "success": true,
    "data": {
        "tasks": [...],
        "stats": {
            "exam_label": "3월 중간고사",
            "days_left": 30,
            "ratio": "7:3",
            "completed_count": 2,
            "total_count": 5
        }
    }
}
```

### Complete Task
```
POST /local/routinecoach/index.php
```

Parameters:
- `action`: 'complete'
- `taskid`: Task ID
- `userid`: User ID
- `completed`: 1 (complete) or 0 (uncomplete)
- `sesskey`: Session key

## Troubleshooting

### Widget Not Appearing
1. Check if user is logged in (`require_login()`)
2. Verify AMD module is compiled: `php admin/cli/build_js.php`
3. Check browser console for JavaScript errors
4. Ensure user has `local/routinecoach:view` capability

### Tasks Not Loading
1. Verify database tables are installed
2. Check if user has active exams and routines
3. Verify API endpoint is accessible
4. Check PHP error logs

### Styling Issues
1. Clear Moodle cache: `php admin/cli/purge_caches.php`
2. Check for CSS conflicts with theme
3. Use browser developer tools to inspect styles

## Security Considerations
- Always validate user permissions before injection
- Use session keys for POST requests
- Sanitize all user inputs
- Implement rate limiting for API calls

## Support
For issues or questions, contact the MathKing development team.