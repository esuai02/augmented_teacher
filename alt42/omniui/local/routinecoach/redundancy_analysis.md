# Routine Coach Redundancy Analysis & Injection Alternatives

## 📋 Overview
Analysis of potential conflicts between Routine Coach plugin and existing MathKing system files.

---

## 🔍 File-by-File Conflict Analysis

### 1. `augmented_teacher/students/schedule42.php`

#### Potential Conflicts:
- **Database Access**: Both systems access `mdl_abessi_schedule` table
- **Session Management**: Duplicate session handling with `$USER` object
- **JavaScript Injection**: Multiple widget injections may conflict

#### Redundancy Points:
```php
// Line 12-14 (info_schedule.php reference)
$timecreated=time();
if($role==='student')$DB->execute("INSERT INTO {abessi_missionlog}...");
// CONFLICT: Routine Coach also logs to missionlog at line 294-305 in routine_service.php
```

#### Recommended Alternative Injection:
```javascript
// METHOD 1: Conditional JS Injection (Recommended)
// Add after line 50 in schedule42.php
if (typeof window.routineCoachLoaded === 'undefined') {
    window.routineCoachLoaded = true;
    require(['local_routinecoach/routinecoach'], function(RC) {
        RC.init({userid: <?php echo $USER->id; ?>});
    });
}

// METHOD 2: Iframe Isolation
<iframe id="routinecoach-frame" 
        src="/local/routinecoach/widget.php?userid=<?php echo $USER->id; ?>"
        style="position: fixed; bottom: 20px; right: 20px; 
               width: 320px; height: 400px; border: none;">
</iframe>
```

---

### 2. `alt42/studenthome/index.php`

#### Potential Conflicts:
- **Goal Management**: Overlaps with `mdl_abessi_today` table usage
- **Dashboard Data**: Both systems fetch today's goals
- **AJAX Endpoints**: Multiple endpoints handling similar data

#### Redundancy Points:
```php
// get_dashboard_goals.php lines 42-49
$todayGoal = $DB->get_record_sql(
    "SELECT * FROM {abessi_today} WHERE userid = ? AND type LIKE '오늘목표'..."
);
// CONFLICT: routine_service.php lines 365-384 also queries abessi_today
```

#### Recommended Alternative Injection:
```javascript
// METHOD 1: Event-Based Integration
document.addEventListener('DOMContentLoaded', function() {
    // Check if dashboard goals already loaded
    if (window.dashboardGoalsLoaded) {
        // Integrate with existing data
        window.routineCoachIntegration = {
            mode: 'integrated',
            shareData: true,
            useExistingGoals: true
        };
    }
    
    // Load Routine Coach with integration mode
    require(['local_routinecoach/routinecoach'], function(RC) {
        RC.init({
            userid: <?php echo $USER->id; ?>,
            integration: window.routineCoachIntegration || {}
        });
    });
});

// METHOD 2: Web Component Encapsulation
<routine-coach-widget 
    user-id="<?php echo $USER->id; ?>"
    mode="compact"
    position="bottom-right">
</routine-coach-widget>
```

---

### 3. `students/today42.php`

#### Potential Conflicts:
- **Today's Tasks Display**: Both systems show today's activities
- **Task Completion Tracking**: Duplicate completion mechanisms
- **UI Overlap**: Multiple widgets in same screen area

#### Redundancy Points:
```javascript
// routinecoach.js lines 56-59
return currentPath.includes('/augmented_teacher/students/today42.php') ||
       currentPath.includes('/alt42/studenthome/index.php');
// ISSUE: Auto-injection may conflict with existing UI elements
```

#### Recommended Alternative Injection:
```javascript
// METHOD 1: Smart Position Detection
(function() {
    // Detect existing widgets/elements
    var existingWidgets = document.querySelectorAll('[class*="widget"], [id*="widget"]');
    var position = {bottom: 20, right: 20};
    
    // Adjust position based on existing elements
    existingWidgets.forEach(function(widget) {
        var rect = widget.getBoundingClientRect();
        if (rect.right > window.innerWidth - 400) {
            position.right += 340; // Shift left
        }
    });
    
    // Inject with adjusted position
    var widgetContainer = document.createElement('div');
    widgetContainer.id = 'routinecoach-smart-widget';
    widgetContainer.style.cssText = `
        position: fixed;
        bottom: ${position.bottom}px;
        right: ${position.right}px;
        z-index: 999;
    `;
    document.body.appendChild(widgetContainer);
    
    // Load widget into container
    require(['local_routinecoach/routinecoach'], function(RC) {
        RC.injectInto('#routinecoach-smart-widget', {
            userid: <?php echo $USER->id; ?>
        });
    });
})();

// METHOD 2: Tab/Accordion Integration
<div class="dashboard-tabs">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#today-tasks">오늘 할 일</a></li>
        <li><a href="#routine-coach">루틴 코치</a></li>
    </ul>
    <div class="tab-content">
        <div id="today-tasks" class="tab-pane active">
            <!-- Existing today42.php content -->
        </div>
        <div id="routine-coach" class="tab-pane">
            <div id="routinecoach-embedded"></div>
        </div>
    </div>
</div>
```

---

## 🔧 Global Conflict Resolution Strategies

### 1. Namespace Isolation
```javascript
// Wrap all Routine Coach code in namespace
window.MathKing = window.MathKing || {};
window.MathKing.RoutineCoach = (function() {
    var private = {
        // All plugin code here
    };
    return {
        init: function(config) { /* ... */ },
        inject: function(target) { /* ... */ }
    };
})();
```

### 2. Database Query Optimization
```php
// Combine queries to reduce redundancy
class UnifiedDataService {
    public static function getTodayData($userid) {
        // Single query for both systems
        $sql = "SELECT 
                    t1.*, 
                    t2.id as routine_task_id,
                    t2.title as routine_task_title
                FROM {abessi_today} t1
                LEFT JOIN {routinecoach_task} t2 
                    ON t2.userid = t1.userid 
                    AND DATE(FROM_UNIXTIME(t2.duedate)) = CURDATE()
                WHERE t1.userid = ?";
        return $DB->get_records_sql($sql, [$userid]);
    }
}
```

### 3. Event-Driven Architecture
```javascript
// Use custom events for communication
document.addEventListener('mathking:goal-updated', function(e) {
    // Routine Coach responds to goal updates
    MathKing.RoutineCoach.refreshTasks(e.detail);
});

document.addEventListener('routinecoach:task-completed', function(e) {
    // Update main dashboard
    if (window.updateDashboard) {
        window.updateDashboard(e.detail);
    }
});
```

### 4. Progressive Enhancement Pattern
```javascript
// Check capabilities before injection
(function() {
    var features = {
        hasGoals: typeof window.dashboardGoals !== 'undefined',
        hasSchedule: document.querySelector('.schedule-container'),
        hasWidgets: document.querySelectorAll('.widget-container').length > 0
    };
    
    // Adjust injection based on existing features
    if (features.hasGoals && !features.hasWidgets) {
        // Full widget mode
        MathKing.RoutineCoach.init({mode: 'full'});
    } else if (features.hasWidgets) {
        // Compact mode to avoid conflicts
        MathKing.RoutineCoach.init({mode: 'compact'});
    } else {
        // Minimal mode
        MathKing.RoutineCoach.init({mode: 'minimal'});
    }
})();
```

---

## 📊 Redundancy Summary Table

| Component | Existing System | Routine Coach | Conflict Level | Solution |
|-----------|----------------|---------------|----------------|----------|
| Goal Display | `get_dashboard_goals.php` | `get_today_tasks()` | Medium | Unified API |
| Task Completion | `abessi_missionlog` | `complete_task()` | Low | Shared logging |
| Schedule Integration | `info_schedule.php` | `schedule_exam_hook.php` | High | Event system |
| Widget Position | Dashboard widgets | Floating widget | Medium | Smart positioning |
| Session Management | Moodle session | Service session | Low | Use Moodle only |
| Database Access | Direct queries | Service layer | Low | Cache sharing |

---

## 🚀 Recommended Implementation

### Best Practice Injection Method
```php
// In each target file (schedule42.php, today42.php, index.php)
// Add this unified injection code:

<?php
// Routine Coach Safe Injection
if (file_exists($CFG->dirroot . '/local/routinecoach/lib/injector.php')) {
    require_once($CFG->dirroot . '/local/routinecoach/lib/injector.php');
    
    $injector = new \local_routinecoach\injector($USER->id);
    
    // Detect context and inject appropriately
    $context = [
        'page' => basename($_SERVER['SCRIPT_NAME']),
        'existing_widgets' => $PAGE->requires->get_loaded_modules(),
        'mode' => 'auto' // auto, full, compact, minimal, iframe
    ];
    
    echo $injector->getSafeInjectionCode($context);
}
?>
```

### Alternative: Single Entry Point
```javascript
// Create a single loader script
<script src="/local/routinecoach/loader.js" 
        data-userid="<?php echo $USER->id; ?>"
        data-page="<?php echo $PAGE->pagetype; ?>"
        data-mode="auto">
</script>
```

---

## 🔐 Security Considerations

1. **XSS Prevention**: Escape all user data in injected content
2. **CSRF Protection**: Use Moodle's `sesskey` for all AJAX calls
3. **Permission Checks**: Verify `local/routinecoach:view` capability
4. **Data Isolation**: Use separate database prefix for plugin tables
5. **Content Security Policy**: Add CSP headers for injected content

---

## 📝 Conclusion

### Primary Recommendations:
1. **Use conditional JS injection** to avoid duplicate loading
2. **Implement event-driven communication** between systems
3. **Create unified data service** to reduce database queries
4. **Use smart positioning** to avoid UI conflicts
5. **Consider iframe isolation** for complete separation when needed

### Migration Path:
1. Phase 1: Implement conditional injection (no conflicts)
2. Phase 2: Add event system for data sharing
3. Phase 3: Unify database queries where possible
4. Phase 4: Full integration with existing dashboard

---

## 📎 Appendix: Injection Code Templates

### Template 1: Minimal Injection
```javascript
window.addEventListener('load', function() {
    if (!document.getElementById('routinecoach-widget')) {
        var script = document.createElement('script');
        script.src = '/local/routinecoach/widget.min.js';
        script.async = true;
        document.head.appendChild(script);
    }
});
```

### Template 2: Iframe Injection
```html
<div id="routinecoach-container" style="position: relative;">
    <iframe src="/local/routinecoach/widget.php?mode=embedded"
            sandbox="allow-scripts allow-same-origin"
            style="width: 100%; height: 400px; border: none;">
    </iframe>
</div>
```

### Template 3: Web Component
```html
<link rel="import" href="/local/routinecoach/components/widget.html">
<routine-coach-widget userid="<?php echo $USER->id; ?>"></routine-coach-widget>
```