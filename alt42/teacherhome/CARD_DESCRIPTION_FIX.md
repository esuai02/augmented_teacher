# Card Description Display Fix

## Problem
The user reported that the "신규학생" (new student) card was displaying the wrong description. Instead of showing "신규 학생 상담 및 레벨 테스트", it was showing the generic agent plugin type description "팝업창에서 멀티턴 작업 실행".

## Root Cause
The card display logic was using the plugin type description instead of the card's saved description. While the display logic in `script.js` was correctly prioritizing `card_description`, the field wasn't being properly saved for all cards.

## Solution

### 1. Fixed Display Logic (script.js)
The display logic was already correct, prioritizing fields in this order:
```javascript
const description = config.card_description || 
                   config.description || 
                   cardSetting.description || 
                   (plugin ? plugin.description : '사용자 정의 플러그인');
```

### 2. Fixed Save Logic (script.js)
Updated `autoAddOnboardingCardsToMenu()` to properly save the `card_description` field:
```javascript
const config = {
    plugin_name: item.title,
    card_description: item.description, // 카드의 원본 설명 저장
    // ... other fields
};
```

### 3. Migration Script (fix_card_descriptions.php)
Created a migration script to fix existing cards that don't have the `card_description` field properly set.

### 4. Test Page (test_card_display.html)
Created a test page to verify that cards are displaying the correct descriptions.

## How to Apply the Fix

1. **Run the migration script** to fix existing cards:
   ```bash
   php fix_card_descriptions.php
   ```

2. **Test the fix** by opening the test page:
   ```
   http://yoursite/teacherhome/test_card_display.html
   ```

3. **Verify in the main application** that cards now show correct descriptions.

## Affected Cards
The fix applies to all agent plugin cards, particularly those in the consultation category:
- 신규학생 → "신규 학생 상담 및 레벨 테스트"
- 정기상담 → "정기적인 학습 상담 일정 관리"
- 상황맞춤 → "학생별 맞춤 상담 진행"

## Technical Details
- The `card_description` field is stored in the `plugin_config` JSON column
- The display logic checks multiple fields for backward compatibility
- New cards automatically save with the correct description
- Existing cards need the migration script to be fixed