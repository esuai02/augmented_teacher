# Step Player Modal Component

## Overview
Reusable PHP component for step-by-step TTS audio playback interface with circular navigation.

## File Location
`/alt42/teachingsupport/components/step_player_modal.php`

## Features
- ✅ Semantic HTML5 structure (282 lines)
- ✅ Full ARIA accessibility attributes
- ✅ Circular navigation dots (dynamically populated by JS)
- ✅ Audio player with play/pause, progress bar, time display
- ✅ Speed control (0.5x - 2x)
- ✅ Auto-play toggle
- ✅ Previous/Next navigation buttons
- ✅ Keyboard navigation support (Space, Escape, Arrows)
- ✅ Screen reader announcements
- ✅ Moodle security guard
- ✅ Korean language support (UTF-8)
- ✅ BEM CSS naming convention

## Integration
```php
<?php include(__DIR__ . '/alt42/teachingsupport/components/step_player_modal.php'); ?>
```

## Related Files
- **Task 7**: `step_player_modal.css` (styling - to be created)
- **Task 8**: `step_player.js` (behavior - to be created)
- **Backend**: `get_section_data.php` (Task 5 - completed)
- **Backend**: `tts_section_generator.php` (Task 4 - completed)

## JavaScript API (Task 8)
```javascript
StepPlayer.open(messageid);  // Open modal with section data
StepPlayer.close();           // Close modal
```

## Data Flow
1. User clicks trigger → `data-messageid` attribute
2. JS calls `StepPlayer.open(messageid)`
3. AJAX GET `/get_section_data.php?messageid=X`
4. Modal populated with sections
5. Audio playback controlled via `step_player.js`

## Accessibility Features
- `role="dialog"` with `aria-modal="true"`
- `aria-labelledby` and `aria-describedby` for context
- `aria-live="polite"` for step change announcements
- Keyboard navigation: Tab, Space, Enter, Escape, Arrow keys
- Focus trap within modal
- Screen reader helper text container

## CSS Classes (BEM)
- Block: `.step-modal`
- Elements: `.step-modal__header`, `.step-modal__card`, `.step-modal__nav-circles`
- Modifiers: `.step-modal__circle--active`, `.step-modal__nav-btn:disabled`

## Next Steps
- [ ] Task 7: Create CSS styling (`step_player_modal.css`)
- [ ] Task 8: Implement JavaScript behavior (`step_player.js`)
- [ ] Task 9: Integrate into `teachingagent.php`
- [ ] Task 10: Integrate into `student_inbox.php`

## Version
1.0 (2025-11-22)
