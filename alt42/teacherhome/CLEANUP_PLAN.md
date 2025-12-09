# Cleanup Plan: 상담관리 (Consultation Management)

## Overview
Analysis of the consultation management feature to identify and remove dead code while maintaining functionality.

## Dead Code Identified

### 1. Console.log Statements (Debug Code)
Found multiple console.log statements that should be removed in production:

#### Files to Clean:
- `script.js`: 8 instances
- `weekly/weekly.js`: 4 instances  
- `quarterly/quarterly.js`: Multiple instances
- `index.html`: 1 instance
- `test_card_display.html`: Several instances (test file - keep for debugging)

### 2. Unused Functions
- No completely unused consultation-specific functions found
- `getConsultationData()` is actively used in `getMenuStructure()`
- All consultation tabs and items are properly referenced

### 3. Redundant Code Patterns
- Repeated console.log patterns in module methods
- Similar structure across all module files could be further abstracted

### 4. Test/Debug Code
- `fix_card_descriptions.php` - Migration script, can be removed after execution
- `test_card_display.html` - Debug page, keep but exclude from production

### 5. Deprecated Comments
- No TODO/FIXME/DEPRECATED comments found in consultation code

## Safe Cleanup Actions

### Phase 1: Remove Debug Statements
1. Remove console.log statements from production code
2. Keep structural logging if needed for monitoring
3. Replace with proper error handling where appropriate

### Phase 2: Code Organization
1. No dead functions to remove
2. Consultation data structure is clean and well-organized
3. All items are properly used

### Phase 3: File Cleanup
1. Archive migration scripts after execution
2. Move test files to separate test directory

## Risk Assessment
- **Low Risk**: Removing console.log statements
- **No Risk**: No unused functions found
- **No Risk**: Data structure is actively used

## Validation Plan
1. Test consultation menu after cleanup
2. Verify all consultation cards load properly
3. Check plugin functionality remains intact
4. Run automated tests if available

## Files to Modify
1. `script.js` - Remove console.log statements
2. `weekly/weekly.js` - Remove console.log statements
3. `quarterly/quarterly.js` - Remove console.log statements
4. `index.html` - Remove console.log statement

## Files to Keep As-Is
1. `getConsultationData()` function - Actively used
2. Consultation data structure - All items referenced
3. Database operations - No redundant queries found

## Excluded from Cleanup
1. `test_card_display.html` - Test utility
2. `CARD_DESCRIPTION_FIX.md` - Documentation
3. Migration scripts - May be needed for other environments