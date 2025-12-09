# Component Inventory for students/today.php Migration

## Overview
Complete component mapping of students/today.php (1,450 lines) for systematic migration to today42.php.

## 1. Database Initialization & Core Variables (Lines 1-25)
**Location**: Lines 1-25
**Components**:
- Moodle config inclusion
- Database connection (`$DB`, `$USER`)
- Navbar.php inclusion (EXCLUDE from migration)
- Time variables setup (`$tbegin`, `$maxtime`, `$timecreated`)
- Time calculation variables (`$adayAgo`, `$aweekAgo`, `$timestart3`)

**Dependencies**: 
- Moodle database connection
- Role-based access control (`$role`, `$studentid` from navbar.php)

**Integration Requirements**:
- Must preserve all time calculations
- Require proper error handling for database connections
- Need role-based conditional logic

## 2. Quiz Result Processing & Categorization (Lines 19-157)
**Location**: Lines 19-157
**Components**:
- Main quiz query from `mdl_quiz` and `mdl_quiz_attempts`
- Quiz categorization variables:
  - `$quizlist00` - Preparation activities
  - `$quizlist11`, `$quizlist12` - Today/past internal tests  
  - `$quizlist21`, `$quizlist22` - Today/past standard tests
  - `$quizlist31`, `$quizlist32` - Today/past cognitive enhancement
- Grade calculation and status indicators (green/blue/red dots)
- Role-based quiz modification buttons (`addquiztime`, `deletequiz`)
- Interactive checkboxes with `AddReview()` function calls

**Dependencies**:
- Database queries using `timecreated` field (NOT `date`)
- Role-based access control
- JavaScript functions: `addquiztime()`, `deletequiz()`, `AddReview()`

**Integration Requirements**:
- Preserve complex categorization logic
- Maintain Bootstrap table styling
- Keep role-based conditional rendering
- Ensure proper error handling for quiz queries

## 3. Whiteboard Activity Processing (Lines 168-322)
**Location**: Lines 168-322  
**Components**:
- Whiteboard data query from `mdl_abessi_messages`
- Whiteboard categorization variables:
  - `$wboardlist0` - Today flagged activities
  - `$wboardlist1` - Today regular activities  
  - `$wboardlist2` - Past activities
  - `$reviewwb` - Review activities (visible)
  - `$reviewwb2` - Review activities (hidden, teacher only)
  - `$reviewwb0` - Review reservations
- Interactive elements with `ChangeCheckBox2()` function calls
- Question text extraction and image processing
- Status icons and progress indicators

**Dependencies**:
- Database queries with timestamp comparisons
- Role-based visibility controls
- JavaScript functions: `ChangeCheckBox2()`, `showWboard()`
- External file: `../whiteboard/status_icons.php`

**Integration Requirements**:
- Preserve activity tracking logic
- Maintain role-based conditional rendering
- Keep interactive checkbox functionality
- Ensure proper image handling and tooltip integration

## 4. Analytics Iframe Integration (Lines 568-597)
**Location**: Lines 568-597
**Components**:
- Two-column iframe layout with Bootstrap styling
- Left iframe: `user_analysis.php` (user analytics)
- Right iframe: `timescaffolding_stat.php` (time scaffolding statistics)
- Responsive table layout with proper spacing

**Dependencies**:
- External PHP files for analytics
- Bootstrap CSS classes
- Responsive design patterns

**Integration Requirements**:
- Maintain responsive two-column layout
- Preserve iframe dimensions and styling
- Keep proper table structure and spacing

## 5. Progress Card Section (Lines 600-659)
**Location**: Lines 600-659
**Components**:
- Bootstrap progress cards with color-coded indicators
- Weekly and daily time tracking
- Interactive checkboxes for DMN rest and offline status
- Progress bars with dynamic styling (`bg-success`, `bg-warning`, `bg-danger`)
- Time calculation display with penalty time handling

**Dependencies**:
- Bootstrap progress bar classes
- JavaScript functions: `Resttime()`, `ChangeCheckBox()`
- Time calculation variables from earlier sections

**Integration Requirements**:
- Preserve Bootstrap styling and responsiveness
- Maintain interactive checkbox functionality
- Keep dynamic progress bar color logic
- Ensure proper time calculation display

## 6. Quiz & Whiteboard Display Section (Lines 680-690)
**Location**: Lines 680-690
**Components**:
- Main results table with quiz and whiteboard sections
- Table headers with navigation links (1주일/1개월/3개월)
- Quiz categorization display using variables from section 2
- Whiteboard activity display using variables from section 3
- Teacher-only "오답 클리어" button

**Dependencies**:
- All quiz and whiteboard variables from previous sections
- Role-based conditional rendering
- JavaScript function: `checkAllBeginCheckboxes()`

**Integration Requirements**:
- Preserve table structure and styling
- Maintain role-based element visibility
- Keep navigation link functionality
- Ensure proper variable integration

## 7. JavaScript Functions (Lines 693-1431)
**Location**: Lines 693-1431
**Components**:

### Core Interactive Functions:
- `checkAllBeginCheckboxes()` - Clear error notes (Lines 694-703)
- `showList(Studentid)` - Show cognitive recent popup (Lines 704-711)
- `showWboard(Wbid)` - Show whiteboard review popup (Lines 712-720)
- `deletequiz(Attemptid)` - Delete quiz attempt with confirmation (Lines 745-779)
- `addquiztime(Attemptid)` - Modify quiz time with multiple options (Lines 780-1013)

### Additional Functions:
- `updatetime()` - Time tracking updates (Lines 1014-1061)
- `ChangeCheckSteps()` - Checkbox state management (Lines 1062-1100)
- `quickReply()`, `quickReply2()` - Quick response functions (Lines 1140-1217)
- `Resttime()` - DMN rest time management with timer modal (Lines 1218-1310)

**Dependencies**:
- SweetAlert library for modals
- jQuery for AJAX calls
- Bootstrap classes for styling
- External file: `check.php` for AJAX processing

**Integration Requirements**:
- Preserve all function definitions
- Maintain SweetAlert modal configurations
- Keep AJAX call structures and error handling
- Ensure proper event handler attachments

## 8. Additional Components
**Location**: Lines 1400-1450
**Components**:
- Select2 plugin initialization
- jQuery UI slider configuration  
- CSS styling for SweetAlert modals
- PostIt system inclusion (`../LLM/postit.php`)

**Dependencies**:
- Select2 library
- jQuery UI library
- Bootstrap theme integration

**Integration Requirements**:
- Preserve library initializations
- Maintain CSS styling
- Keep PostIt system integration

## Database Schema Requirements

### Key Tables:
- `mdl_quiz_attempts` - Quiz data (use `timecreated` not `date`)
- `mdl_abessi_messages` - Whiteboard activities
- `mdl_abessi_today` - Today's activities and goals
- `mdl_abessi_indicators` - Performance indicators

### Critical Fields:
- `timecreated` - Primary timestamp field (NOT `date`)
- `userid`/`studentid` - User identification
- `role` - Access control variable

## Role-Based Access Control Patterns

### Student View:
- Limited quiz modification options
- Restricted whiteboard visibility
- Basic progress tracking

### Teacher View:
- Full quiz management capabilities
- Complete whiteboard access
- Administrative functions
- Additional analytical tools

## Integration Dependencies Map

```
Database Init → Quiz Processing → Whiteboard Processing → Display Sections → JavaScript Functions
     ↓              ↓                    ↓                    ↓              ↓
Role Control → Grade Calculation → Activity Tracking → Table Rendering → Event Handling
```

## Migration Priority Order
1. Database initialization and core variables (Foundation)
2. Role-based access control setup (Security)
3. Quiz result processing and categorization (Core functionality)
4. Whiteboard activity processing (Core functionality)
5. Analytics iframe integration (Analytics)
6. Progress card section (User interface)
7. Display sections and tables (Presentation)
8. JavaScript functions and interactivity (User experience)
9. Additional components and styling (Enhancement)

## Potential Integration Issues
1. **Database Query Compatibility**: Ensure `timecreated` vs `date` field usage
2. **Role Variable Propagation**: Maintain `$role` and `$studentid` throughout
3. **JavaScript Dependencies**: Verify SweetAlert, jQuery, Bootstrap availability
4. **File Path Dependencies**: Update relative paths for included files
5. **CSS Class Compatibility**: Ensure Bootstrap classes are available
6. **AJAX Endpoint Compatibility**: Verify `check.php` functionality

## Testing Checklist
- [ ] Database queries execute without errors
- [ ] Role-based access control functions correctly
- [ ] Quiz categorization displays properly
- [ ] Whiteboard activities load and interact correctly
- [ ] Analytics iframes display content
- [ ] Progress bars show accurate data
- [ ] JavaScript functions respond to user interactions
- [ ] AJAX calls complete successfully
- [ ] Responsive design works on mobile devices
- [ ] Cross-browser compatibility maintained