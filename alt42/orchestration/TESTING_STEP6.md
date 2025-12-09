# Step 6 Teacher Feedback Panel - Manual Testing Guide

## File Location
- `/alt42/orchestration/agents/agent06_teacher_feedback/ui/teacher_feedback_panel.php`

## Testing URLs

### Main System (Requires Moodle Login)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/index.php?userid=2
```

### Standalone Test Page (No Login Required)
```
https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/test_agent06.html
```

---

## Test Checklist

### ✅ Test 1: Panel Loading in Main System

**Steps:**
1. Log into Moodle at https://mathking.kr
2. Navigate to orchestration system: `/alt42/orchestration/index.php?userid=2`
3. Wait for page to load completely
4. Locate Step 6 in the left sidebar: "👨‍🏫 선생님 피드백"
5. Click on Step 6

**Expected Results:**
- [ ] Right panel clears previous content
- [ ] Agent 06 panel loads in right panel
- [ ] Header "👨‍🏫 선생님 피드백" is visible
- [ ] Description text visible: "학생에 대한 관찰, 개선사항, 칭찬 등을 기록하고 조회합니다."
- [ ] No console errors in browser DevTools

**File Reference:** `index.php:454-456` (Step 6 check), `index.php:605-637` (renderAgent06Panel)

---

### ✅ Test 2: UI Structure Verification

**Steps:**
1. After Step 6 is loaded in right panel
2. Inspect the panel structure

**Expected Elements:**
- [ ] **Toolbar Section** (`.agent06-toolbar`)
  - [ ] "🔍 피드백 불러오기" button
  - [ ] "✍️ 새 피드백 작성" button

- [ ] **Period Selection** (`.agent06-period-section`)
  - [ ] Header: "📅 조회 기간"
  - [ ] 5 period buttons:
    - [ ] "오늘" (active by default)
    - [ ] "1주일"
    - [ ] "2주"
    - [ ] "1개월"
    - [ ] "3개월"

- [ ] **Feedback List** (`.agent06-feedback-list`)
  - [ ] Default empty state: "📝 위 버튼을 클릭하여 피드백을 불러오세요."

- [ ] **New Feedback Section** (`.agent06-new-feedback`)
  - [ ] Header: "✍️ 새 피드백 작성"
  - [ ] Textarea with placeholder text
  - [ ] "💾 피드백 저장" button

**File Reference:** `teacher_feedback_panel.php:241-303`

---

### ✅ Test 3: JavaScript Object Initialization

**Steps:**
1. Open browser DevTools Console (F12)
2. Type: `window.agent06`
3. Press Enter

**Expected Results:**
- [ ] Object exists (not undefined)
- [ ] Check properties:
  ```javascript
  window.agent06.currentPeriod  // Should be 'today'
  window.agent06.userId         // Should be 2 (or current user ID)
  ```
- [ ] Check methods exist:
  ```javascript
  typeof window.agent06.loadFeedback     // Should be 'function'
  typeof window.agent06.saveFeedback     // Should be 'function'
  typeof window.agent06.scrollToNew      // Should be 'function'
  typeof window.agent06.escapeHtml       // Should be 'function'
  ```

**File Reference:** `teacher_feedback_panel.php:306-459`

---

### ✅ Test 4: Period Selection Functionality

**Steps:**
1. Click "1주일" button
2. Open DevTools Console
3. Check: `window.agent06.currentPeriod`

**Expected Results:**
- [ ] "1주일" button gets `active` class (pink background)
- [ ] "오늘" button loses `active` class (white background)
- [ ] Console shows: `currentPeriod` = `'week'`
- [ ] Console log appears: `[Agent06] Period changed: week`

**Test All Periods:**
| Button Text | Expected currentPeriod Value |
|-------------|------------------------------|
| 오늘        | `today`                      |
| 1주일       | `week`                       |
| 2주         | `2weeks`                     |
| 1개월       | `month`                      |
| 3개월       | `3months`                    |

**File Reference:** `teacher_feedback_panel.php:461-471`

---

### ✅ Test 5: Load Feedback Button

**Steps:**
1. Click "🔍 피드백 불러오기" button
2. Watch the panel

**Expected Results:**
- [ ] Loading spinner appears immediately
- [ ] Loading text: "피드백을 불러오는 중..."
- [ ] Spinner disappears after API response
- [ ] **If data exists:**
  - [ ] Summary section appears: "📊 피드백 요약"
  - [ ] Shows count: "오늘 동안 X개의 피드백이 있습니다."
  - [ ] Feedback cards appear in list
  - [ ] Each card shows: teacher name, timestamp, feedback text

- [ ] **If no data:**
  - [ ] Shows: "📭 선택한 기간에 피드백이 없습니다."

- [ ] **If error:**
  - [ ] Shows: "❌피드백 불러오기 실패"
  - [ ] Error message includes file location

**Console Logs to Check:**
```
[Agent06] Loading feedback, period: today, userId: 2
```

**API Endpoint:** `/moodle/local/augmented_teacher/alt42/orchestration_hs2/api/teacher_feedback_api.php?action=get_feedback&user_id=2&period=today`

**File Reference:** `teacher_feedback_panel.php:312-365`

---

### ✅ Test 6: New Feedback Scroll Function

**Steps:**
1. Click "✍️ 새 피드백 작성" button

**Expected Results:**
- [ ] Page smoothly scrolls to new feedback section
- [ ] Textarea becomes focused (cursor appears inside)
- [ ] Textarea is ready for input

**File Reference:** `teacher_feedback_panel.php:443-451`

---

### ✅ Test 7: Feedback Input and Save

**Steps:**
1. Scroll to new feedback section (or click "새 피드백 작성")
2. Type test feedback:
   ```
   테스트 피드백: 학생의 수학 문제 풀이 집중력이 향상되었습니다.
   ```
3. Click "💾 피드백 저장" button

**Expected Results:**
- [ ] If empty: Alert shows "피드백 내용을 입력해주세요."
- [ ] If valid:
  - [ ] API request sent
  - [ ] Success alert: "✅ 피드백이 저장되었습니다."
  - [ ] Textarea clears automatically
  - [ ] Feedback list refreshes (loadFeedback called)

- [ ] If error:
  - [ ] Alert shows: "❌ 피드백 저장 실패: [error message]"
  - [ ] Error includes file location

**API Endpoint:**
```
POST /moodle/local/augmented_teacher/alt42/orchestration_hs2/api/teacher_feedback_api.php
Body: {"action":"save_feedback","user_id":2,"feedback_text":"..."}
```

**File Reference:** `teacher_feedback_panel.php:404-441`

---

### ✅ Test 8: Feedback Display Format

**After loading feedback with data:**

**Expected Format per Card:**
```
┌─────────────────────────────────────┐
│ 👤 [Teacher Name]      [Timestamp]  │
│                                     │
│ [Feedback text with line breaks]    │
└─────────────────────────────────────┘
```

**Verify:**
- [ ] Teacher name is bold and dark gray
- [ ] Timestamp is small and light gray
- [ ] Feedback text supports multi-line (line breaks render as `<br>`)
- [ ] Cards have subtle border and shadow
- [ ] Hover effect: slight elevation and shadow increase

**File Reference:** `teacher_feedback_panel.php:367-386`

---

### ✅ Test 9: Responsive Design

**Steps:**
1. Resize browser window to different widths
2. Test on mobile viewport (DevTools → Toggle Device Toolbar)

**Expected Results:**
- [ ] Panel adapts to container width (100%)
- [ ] Period buttons wrap on narrow screens
- [ ] Toolbar buttons stack on mobile
- [ ] Feedback cards remain readable
- [ ] Textarea expands to full width

**File Reference:** `teacher_feedback_panel.php:23-239` (CSS styles)

---

### ✅ Test 10: Error Handling

**Test Scenarios:**

1. **Network Error (Disconnect internet)**
   - [ ] Shows user-friendly error message
   - [ ] Includes file location for debugging

2. **API Timeout**
   - [ ] Loading indicator eventually hides
   - [ ] Error state shown

3. **Invalid Response**
   - [ ] Gracefully handles malformed JSON
   - [ ] Shows error with context

**File Reference:** `teacher_feedback_panel.php:351-364, 437-440`

---

## Standalone Test (No Login Required)

**URL:** `https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/test_agent06.html`

**Purpose:** Test the panel component in isolation without authentication

**Steps:**
1. Open URL in browser
2. Click "📥 Agent 06 패널 로드" button
3. Watch console log area

**Expected Results:**
- [ ] Panel loads successfully
- [ ] Console shows: "HTML 로드 완료 (X bytes)"
- [ ] Console shows: "패널 렌더링 완료"
- [ ] Console shows: "스크립트 1개 발견"
- [ ] Console shows: "✅ window.agent06 객체 초기화 완료"
- [ ] Console shows: "userId: 2"
- [ ] Console shows: "currentPeriod: today"

**File Reference:** `test_agent06.html:111-172`

---

## Browser Compatibility

Test in multiple browsers:
- [ ] Chrome/Edge (Chromium)
- [ ] Firefox
- [ ] Safari (if available)

---

## Performance Checks

**Using Browser DevTools:**

1. **Network Tab:**
   - [ ] Panel PHP file loads < 1 second
   - [ ] API responses < 2 seconds
   - [ ] No 404 errors

2. **Console Tab:**
   - [ ] No JavaScript errors
   - [ ] Expected log messages appear

3. **Elements Tab:**
   - [ ] Inspect `#agent06-panel` structure
   - [ ] Verify CSS classes applied correctly

---

## Common Issues & Troubleshooting

### Issue: Panel doesn't load
- **Check:** Browser console for errors
- **Check:** Network tab for failed requests
- **Verify:** File path is correct
- **Solution:** Check `index.php:605-637` renderAgent06Panel function

### Issue: window.agent06 is undefined
- **Check:** Script tag executed
- **Check:** No JavaScript syntax errors
- **Solution:** Verify script re-execution in `index.php:618-630`

### Issue: API calls fail
- **Check:** Network tab for endpoint URL
- **Check:** API file exists at orchestration_hs2
- **Verify:** User ID parameter passed correctly
- **Solution:** Check `teacher_feedback_panel.php:325-330, 416-426`

### Issue: Styles not applied
- **Check:** `<style>` tag in panel PHP
- **Check:** No CSS conflicts with parent page
- **Solution:** Verify CSS scoping with `.agent06-*` classes

---

## Completion Criteria

**All tests pass when:**
- ✅ Panel loads in right panel on Step 6 click
- ✅ All UI elements visible and styled correctly
- ✅ Period selection works and updates state
- ✅ Load feedback button triggers API call
- ✅ New feedback scroll and focus works
- ✅ Feedback save creates data (test with dummy data)
- ✅ Feedback display shows properly formatted cards
- ✅ No console errors
- ✅ Responsive on mobile viewports
- ✅ Error handling works gracefully

---

## Test Report Template

```markdown
## Step 6 Testing Report

**Date:** YYYY-MM-DD
**Tester:** [Name]
**Browser:** [Chrome/Firefox/Safari] v[X.X]
**Environment:** Production (mathking.kr)

### Results Summary
- Total Tests: 10
- Passed: X
- Failed: X
- Blocked: X

### Issues Found
1. [Issue description]
   - Severity: High/Medium/Low
   - File: [file:line]
   - Steps to reproduce: ...
   - Expected: ...
   - Actual: ...

### Screenshots
- [Attach relevant screenshots]

### Recommendations
- [Any improvements or fixes needed]
```

---

## File Locations Reference

| File | Purpose | Line References |
|------|---------|-----------------|
| `teacher_feedback_panel.php` | Main panel component | Full file |
| `index.php` | Main orchestration system | 454-456, 605-637, 644 |
| `test_agent06.html` | Standalone test page | 111-172 |
| `teacher_feedback_api.php` | API backend | orchestration_hs2/api/ |

---

**Last Updated:** 2025-10-22
**Version:** 1.0
