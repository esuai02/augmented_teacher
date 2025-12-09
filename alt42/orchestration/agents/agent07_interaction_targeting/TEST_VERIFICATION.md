# Agent 07 Guidance Mode Selection - Test Verification Guide

**Implementation Date**: 2025-01-22
**Version**: 1.0
**Test URL**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/

---

## ✅ Pre-Test Checklist

Before testing, verify all files are deployed:

### Files Created
- [x] `/agents/agent07_interaction_targeting/guidance_modes_data.js`
- [x] `/agents/agent07_interaction_targeting/panel_renderer.js`
- [x] `/agents/agent07_interaction_targeting/modal_popup.js`
- [x] `/agents/agent07_interaction_targeting/styles.css`

### Files Modified
- [x] `/index.php` - Added Agent 07 script includes (lines 363-366)
- [x] `/index.php` - Added Agent 07 CSS link (line 44-45)
- [x] `/index.php` - Modified `renderDetail()` function (lines 529-534)

---

## 🧪 Manual Test Scenarios

### Test 1: Agent 07 Card Click → Right Panel Display

**Steps:**
1. Navigate to orchestration system URL
2. Click on **Agent 07 (상호작용 타게팅)** card in the left sidebar
3. **Expected Result**: Right panel opens with:
   - Agent 07 header (gradient background with icon)
   - "지도 모드 선택" section title
   - 3×3 grid of 9 guidance mode buttons
   - Info box explaining how to use the system

**Success Criteria:**
- [ ] Right panel opens smoothly
- [ ] All 9 mode buttons are visible in 3×3 grid
- [ ] Buttons have icons and Korean labels
- [ ] Info box is present and readable

**Console Log Check:**
```javascript
// Should see:
[panel_renderer.js] ✅ Agent 07 panel renderer loaded successfully
[agent07_guidance_modes_data.js] ✅ Loaded 9 guidance modes with 54 total problems
```

---

### Test 2: Guidance Mode Button Click → Modal Popup

**Steps:**
1. Complete Test 1
2. Click any of the 9 guidance mode buttons (e.g., "📚 커리큘럼")
3. **Expected Result**: Modal popup appears with:
   - Semi-transparent backdrop
   - White modal card (600px max-width)
   - Mode header with icon and name
   - Blue description box with mode tooltip
   - 6 red problem items
   - Close button and X icon
   - Footer info box

**Success Criteria:**
- [ ] Modal appears centered on screen
- [ ] Backdrop is semi-transparent (rgba(0,0,0,0.5))
- [ ] All 6 problem items are visible
- [ ] Problem items have hover effect (color changes)
- [ ] Clicking backdrop closes modal
- [ ] Pressing ESC closes modal
- [ ] Clicking X button closes modal

**Console Log Check:**
```javascript
// Should see:
[modal_popup.js] Showing popup for mode: 커리큘럼 (Index: 0)
```

---

### Test 3: Problem Selection → Analysis Report Generation

**Steps:**
1. Complete Test 2
2. Click any of the 6 problem items in the modal
3. **Expected Result**:
   - Modal closes immediately
   - Right panel shows loading state
   - After 10-20 seconds, AI analysis report appears with:
     - 📋 문제 상황 section
     - 🔍 원인 분석 section
     - 💡 개선 방안 section
     - 📊 예상 효과 section

**Success Criteria:**
- [ ] Modal closes on problem click
- [ ] Loading animation appears in right panel
- [ ] Analysis report successfully generated
- [ ] Report contains all 4 sections
- [ ] Report content is relevant to selected problem
- [ ] Mode context is included in problem text

**Console Log Check:**
```javascript
// Should see:
[modal_popup.js] Problem selected: {...}
[agent_analysis.js] Generating analysis for Agent 07...
```

---

### Test 4: Selection State Persistence

**Steps:**
1. Complete Test 3
2. Click Agent 07 card again to re-open panel
3. **Expected Result**:
   - Previously selected mode button shows:
     - Gradient background (purple)
     - White text
     - Green checkmark badge (✓) in top-right corner
     - Stronger shadow
   - Selection summary appears below grid showing:
     - Number of modes selected
     - List of selected problems with mode icons

**Success Criteria:**
- [ ] Selected button has different visual state
- [ ] Checkmark badge is visible
- [ ] Selection summary displays correct information
- [ ] Multiple selections can be made (test with 2-3 modes)

---

### Test 5: Keyboard Navigation & Accessibility

**Steps:**
1. Complete Test 1
2. Press `Tab` key to navigate through mode buttons
3. Press `Enter` on a focused button
4. In modal, press `Tab` to navigate problem items
5. Press `Escape` to close modal

**Success Criteria:**
- [ ] Tab navigation works correctly
- [ ] Focused elements have visible outline
- [ ] Enter key opens modal
- [ ] Escape key closes modal
- [ ] ARIA labels are present (check with screen reader if available)

---

### Test 6: Mobile Responsiveness

**Steps:**
1. Open browser DevTools (F12)
2. Enable mobile device emulation (375px width)
3. Click Agent 07 card
4. Click a mode button
5. Select a problem

**Success Criteria:**
- [ ] Panel adapts to narrow screen
- [ ] Mode grid changes to 2 columns or 1 column
- [ ] Modal is readable on small screen
- [ ] Buttons are touch-friendly (min 44px height)
- [ ] All interactions work with touch

---

### Test 7: Integration with Existing 🎯 Button

**Steps:**
1. Complete Test 1
2. Click the **🎯 문제 타게팅** button on Agent 07 card
3. **Expected Result**: Original popup system opens (NOT guidance mode selection)

**Success Criteria:**
- [ ] 🎯 button still works independently
- [ ] Original agent problem popup appears
- [ ] No interference between two systems

---

### Test 8: Other Agents Not Affected

**Steps:**
1. Click Agent 01, 02, 03, 04, 05, 06, 08, 09... (any agent except 07)
2. **Expected Result**: Each agent shows its own panel (no guidance mode grid)

**Success Criteria:**
- [ ] Agent 01-06 show their original panels
- [ ] Agent 08-21 show their original panels
- [ ] No JavaScript errors in console
- [ ] No visual glitches

---

## 🐛 Common Issues & Troubleshooting

### Issue 1: Scripts Not Loading
**Symptom**: Console shows "ReferenceError: window.agent07GuidanceModes is not defined"

**Solution**:
1. Check file paths in index.php (lines 363-366)
2. Verify files exist at correct locations
3. Clear browser cache (Ctrl+F5)
4. Check PHP error logs for file permission issues

### Issue 2: Modal Not Appearing
**Symptom**: Click mode button but nothing happens

**Solution**:
1. Check console for JavaScript errors
2. Verify `window.showAgent07ModePopup` function exists
3. Check z-index conflicts (modal z-index: 10000)
4. Inspect DOM to see if modal element is created

### Issue 3: Analysis Not Generating
**Symptom**: Problem click closes modal but no analysis appears

**Solution**:
1. Check if `window.generateAnalysisReport` exists
2. Verify `agent_analysis.js` is loaded before Agent 07 scripts
3. Check GPT API configuration and connectivity
4. Review network tab for API request failures

### Issue 4: CSS Not Applied
**Symptom**: Buttons look wrong, no styling

**Solution**:
1. Check CSS file path in index.php (line 44-45)
2. Verify `styles.css` file exists
3. Inspect element to see if styles are loaded
4. Check for CSS syntax errors in browser console

---

## 📊 Test Results Template

**Test Date**: _____________
**Tester**: _____________
**Browser**: _____________
**Screen Size**: _____________

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Card Click → Panel | ⬜ PASS ⬜ FAIL | |
| 2 | Button → Modal | ⬜ PASS ⬜ FAIL | |
| 3 | Problem → Analysis | ⬜ PASS ⬜ FAIL | |
| 4 | State Persistence | ⬜ PASS ⬜ FAIL | |
| 5 | Keyboard Navigation | ⬜ PASS ⬜ FAIL | |
| 6 | Mobile Responsive | ⬜ PASS ⬜ FAIL | |
| 7 | 🎯 Button Integration | ⬜ PASS ⬜ FAIL | |
| 8 | Other Agents | ⬜ PASS ⬜ FAIL | |

**Overall Result**: ⬜ ALL PASS ⬜ PARTIAL ⬜ FAIL

**Critical Issues Found**:
- _____________
- _____________

**Non-Critical Issues**:
- _____________
- _____________

---

## 📝 Test Data

### Test Student ID
- **User ID**: 2 (default test user)
- **URL**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/index.php?userid=2

### Expected Data Loaded
- **9 Guidance Modes**: curriculum, exam, personalized, mission, reflection, selfdirected, apprentice, time, inquiry
- **54 Total Problems**: 6 problems per mode
- **Analysis Integration**: Uses existing GPT-4 API system

---

## ✅ Acceptance Criteria

Implementation is considered **COMPLETE** when:

1. ✅ All 8 test scenarios pass
2. ✅ No JavaScript errors in browser console
3. ✅ No PHP errors in server logs
4. ✅ Mobile responsive design works on 375px screen
5. ✅ Keyboard navigation fully functional
6. ✅ Accessibility features working (ARIA labels, screen reader compatible)
7. ✅ No interference with existing agent system
8. ✅ Selection state persists across panel open/close
9. ✅ Analysis report integrates correctly with guidance mode context
10. ✅ All visual designs match orchestration_hs2 reference pattern

---

**Last Updated**: 2025-01-22
