# Agent Problem Targeting System - Developer Guide

## 📐 System Architecture

### Overview

The Agent Problem Targeting System is a modular JavaScript/PHP application integrated into the ALT42 orchestration dashboard. It follows a clean separation of concerns pattern with three main layers.

**Version**: 1.0
**Last Updated**: 2025-01-21
**Tech Stack**: PHP 7.1.9, MySQL 5.7, Vanilla JavaScript, Moodle 3.7

---

## 🏗️ Architecture Layers

### 1. Data Layer (`agent_problems.js`)

**Purpose**: Static agent definitions and problem catalog
**Location**: `/assets/js/agent_problems.js`
**Size**: ~8KB, 126 problems across 21 agents

**Structure**:
```javascript
window.agentProblems = [
  {
    id: 'agent_01',          // Unique identifier
    agentNumber: 1,          // 1-21
    name: '실시간 온보딩',      // Korean name
    icon: '🎓',               // Emoji icon
    description: '...',      // Agent purpose
    problems: [              // 5-7 problems
      'Problem text 1...',
      'Problem text 2...',
      // ...
    ]
  },
  // ... 20 more agents
];
```

**Maintenance**:
- Update problem lists by editing the array
- Keep problems concise (1-2 sentences)
- Maintain 5-7 problems per agent
- Sync with `/agents/agent##_*/agent##_*.md` knowledge files

### 2. Presentation Layer (`agent_popup.js`)

**Purpose**: Popup UI rendering and interaction handling
**Location**: `/assets/js/agent_popup.js`
**Size**: ~9KB

**Key Functions**:

#### `showAgentProblemPopup(agentIndex)`
- **Parameters**: `agentIndex` (0-20, maps to agent 1-21)
- **Returns**: void
- **Description**: Renders full-screen modal with problem list
- **Accessibility**: ARIA labels, keyboard navigation, escape handler

**Implementation**:
```javascript
window.showAgentProblemPopup = function(agentIndex) {
  // 1. Validate agent data
  const agent = window.agentProblems[agentIndex];
  if (!agent) { /* error handling */ }

  // 2. Generate problem list HTML (with ARIA attributes)
  const problemsHtml = agent.problems.map((problem, idx) => `
    <div role="button" tabindex="0"
      aria-label="문제 ${idx + 1}: ${problem}"
      onclick="selectAgentProblem(...)"
      onkeydown="if(Enter/Space) selectAgentProblem(...)">
      ${problem}
    </div>
  `).join('');

  // 3. Create modal HTML
  const popupHtml = `
    <div role="dialog" aria-modal="true">
      <!-- Header, problems, footer -->
    </div>
  `;

  // 4. Inject and setup handlers
  document.body.insertAdjacentHTML('beforeend', popupHtml);
  setupEventHandlers(); // backdrop click, escape key
};
```

#### `closeAgentProblemPopup()`
- **Parameters**: none
- **Returns**: void
- **Description**: Closes popup with fade-out animation (200ms)

#### `selectAgentProblem(agentIndex, problemIndex, problemText)`
- **Parameters**:
  - `agentIndex`: 0-20
  - `problemIndex`: 0-based index in problems array
  - `problemText`: Full problem string
- **Returns**: void
- **Description**: Triggers analysis generation and closes popup

### 3. Business Logic Layer (`agent_analysis.js`)

**Purpose**: Analysis report generation and display
**Location**: `/assets/js/agent_analysis.js`
**Size**: ~22KB (includes timeout handling)

**Key Functions**:

#### `generateAnalysisReport(agent, problemText, problemIndex, retryCount)`
- **Parameters**:
  - `agent`: Full agent object
  - `problemText`: Selected problem string
  - `problemIndex`: Problem array index
  - `retryCount`: Retry attempt number (default: 0)
- **Returns**: Promise<void>
- **Description**: Async analysis generation with timeout/retry logic

**Flow**:
```javascript
async function generateAnalysisReport(agent, problemText, problemIndex, retryCount = 0) {
  // 1. Show loading state
  showAnalysisLoading(agent, problemText, retryCount);

  // 2. Prepare API request
  const requestData = {
    agent_id, agent_number, agent_name,
    agent_description, problem_text,
    problem_index, student_id, timestamp
  };

  // 3. Call API with 60-second timeout
  const controller = new AbortController();
  const timeoutId = setTimeout(() => controller.abort(), 60000);

  const response = await fetch('/api/generate_agent_analysis.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(requestData),
    signal: controller.signal
  });

  clearTimeout(timeoutId);

  // 4. Handle response
  if (result.success) {
    displayAnalysisReport(agent, problemText, result.analysis);
  } else {
    throw new Error(result.error);
  }

  // 5. Error handling
  catch (error) {
    if (error.name === 'AbortError' && retryCount < 2) {
      showAnalysisTimeout(agent, problemText, problemIndex, retryCount);
    } else {
      showAnalysisError(agent, problemText, error.message);
    }
  }
}
```

#### `displayAnalysisReport(agent, problemText, analysis)`
- **Parameters**:
  - `agent`: Full agent object
  - `problemText`: Problem string
  - `analysis`: Object with 4 sections
- **Returns**: void
- **Description**: Renders right-side analysis panel (450px width)

**Analysis Structure**:
```javascript
{
  problem_situation: "2-3 sentences...",
  cause_analysis: "3+ causes...",
  improvement_plan: "3+ step plan...",
  expected_outcome: "Quantitative outcomes..."
}
```

#### `showAnalysisTimeout(agent, problemText, problemIndex, retryCount)`
- **Purpose**: Timeout warning with retry button
- **Parameters**: Same as `generateAnalysisReport`
- **Returns**: void
- **Description**: Shows warning panel with retry (up to 3 attempts)

### 4. Backend API Layer (`generate_agent_analysis.php`)

**Purpose**: GPT-4 analysis generation with fallback
**Location**: `/api/generate_agent_analysis.php`
**Size**: ~7KB

**Flow**:
```php
// 1. Moodle initialization
include_once("/home/moodle/public_html/moodle/config.php");
global $DB, $USER;
require_login();

// 2. Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { sendError(...); }
$data = json_decode(file_get_contents('php://input'), true);

// 3. Validate required fields
foreach (['agent_id', 'agent_number', 'agent_name', 'problem_text'] as $field) {
  if (!isset($data[$field])) { sendError("Missing: $field"); }
}

// 4. Get student information
$student = $DB->get_record('user', ['id' => $student_id]);
$student_context = [
  '학년' => getUserField($student_id, 'grade'),
  '학습 성향' => getUserField($student_id, 'learning_style')
];

// 5. GPT-4 analysis (via gpt_helper.php)
$gpt_result = generateGPTAnalysis(
  $agent_number, $agent_name, $agent_description,
  $problem_text, $student_name, $student_context
);

// 6. Fallback to placeholder if GPT fails
if ($gpt_result['success']) {
  $analysis = $gpt_result['analysis'];
} else {
  $analysis = generatePlaceholderAnalysis(...);
}

// 7. Save to database (optional audit trail)
if ($DB->get_manager()->table_exists('alt42_agent_analyses')) {
  $DB->insert_record('alt42_agent_analyses', $record);
}

// 8. Return JSON response
echo json_encode([
  'success' => true,
  'analysis' => $analysis,
  'metadata' => [...]
]);
```

### 5. GPT Integration Layer (`gpt_helper.php`, `gpt_config.php`)

**Purpose**: OpenAI API communication
**Location**: `/api/gpt_helper.php`, `/api/gpt_config.php`

**Configuration (`gpt_config.php`)**:
```php
define('OPENAI_API_KEY', 'sk-YOUR-API-KEY-HERE');
define('OPENAI_API_ENDPOINT', 'https://api.openai.com/v1/chat/completions');
define('OPENAI_MODEL', 'gpt-4');
define('OPENAI_TEMPERATURE', 0.7);
define('OPENAI_MAX_TOKENS', 1500);
define('OPENAI_TIMEOUT', 30);
```

**Key Functions**:

#### `callGPTAPI($prompt, $options = [])`
- **Returns**: `['success' => bool, 'response' => string, 'error' => string]`
- **Description**: cURL wrapper for OpenAI API

#### `generateGPTAnalysis(...)`
- **Parameters**: Agent info, problem, student context
- **Returns**: Structured analysis or error
- **Description**: Builds WXSPERTA-framework prompt, calls API, parses response

**Prompt Structure**:
```
# 학습 문제 분석 요청

## 에이전트 정보
- Agent {number}: {name}
- 담당 영역: {description}

## 학생 정보
- 이름: {student_name}
- 학년: {grade}
- 학습 성향: {learning_style}

## 분석 대상 문제
{problem_text}

## 요청 사항
다음 4가지 섹션으로 분석:
1. [문제 상황]: 2-3문장 기술
2. [원인 분석]: 3가지 이상 (교육학적 근거)
3. [개선 방안]: 단계별 제시 (최소 3단계)
4. [예상 효과]: 구체적 효과 (정량적 지표)

응답 형식:
[문제 상황]
...

[원인 분석]
...

[개선 방안]
...

[예상 효과]
...
```

#### `parseGPTResponse($response)`
- **Parameters**: GPT API response text
- **Returns**: `{problem_situation, cause_analysis, improvement_plan, expected_outcome}`
- **Description**: Regex extraction of 4 sections

**Regex Patterns**:
```php
$patterns = [
  'problem_situation' => '/\[문제\s*상황\]\s*\n(.*?)(?=\n\[|$)/s',
  'cause_analysis' => '/\[원인\s*분석\]\s*\n(.*?)(?=\n\[|$)/s',
  'improvement_plan' => '/\[개선\s*방안\]\s*\n(.*?)(?=\n\[|$)/s',
  'expected_outcome' => '/\[예상\s*효과\]\s*\n(.*?)(?=\n\[|$)/s'
];
```

---

## 🔌 Integration Points

### 1. Main Page Integration (`index.php`)

**CSS Link** (Line 38-39):
```php
<!-- Agent Popup Enhancement Styles -->
<link rel="stylesheet" href="assets/css/agent_popup_enhancements.css?v=<?php echo time(); ?>">
```

**Script Loading** (Lines 288-290):
```php
<!-- Agent Problem Popup System -->
<script src="assets/js/agent_problems.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/agent_popup.js?v=<?php echo time(); ?>"></script>
<script src="assets/js/agent_analysis.js?v=<?php echo time(); ?>"></script>
```

**Card Template Modification** (Lines 365-404):
```javascript
// Restructured card HTML
return `
  <div class="${classes}" style="border-left: 4px solid ${step.color};">
    <!-- Existing card content (clickable area) -->
    <div onclick="handleStepClick(${step.id})">
      ...
    </div>

    <!-- New: Problem targeting button -->
    <button
      onclick="event.stopPropagation(); window.showAgentProblemPopup(${step.id - 1})"
      style="position: absolute; bottom: 8px; right: 8px; ..."
      aria-label="이 에이전트의 문제 타게팅 보기"
      title="이 에이전트의 문제 타게팅 보기">
      🎯 문제 타게팅
    </button>
  </div>
`;
```

### 2. Database Integration (Optional)

**Audit Trail Table** (`alt42_agent_analyses`):
```sql
CREATE TABLE alt42_agent_analyses (
  id BIGINT(10) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  agent_id VARCHAR(50) NOT NULL,
  agent_number INT(11) NOT NULL,
  agent_name VARCHAR(255) NOT NULL,
  problem_text TEXT NOT NULL,
  problem_index INT(11) NOT NULL,
  student_id BIGINT(10) UNSIGNED NOT NULL,
  student_name VARCHAR(255) NOT NULL,
  analysis_json LONGTEXT NOT NULL,
  timecreated BIGINT(10) UNSIGNED NOT NULL,
  timemodified BIGINT(10) UNSIGNED NOT NULL,
  INDEX idx_student (student_id),
  INDEX idx_agent (agent_number),
  INDEX idx_created (timecreated)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Usage Queries**:
```sql
-- Daily analysis count
SELECT DATE(FROM_UNIXTIME(timecreated)) as date,
       COUNT(*) as analysis_count
FROM alt42_agent_analyses
GROUP BY DATE(FROM_UNIXTIME(timecreated))
ORDER BY date DESC;

-- Agent usage frequency
SELECT agent_number, agent_name,
       COUNT(*) as usage_count
FROM alt42_agent_analyses
GROUP BY agent_number, agent_name
ORDER BY usage_count DESC;
```

---

## 🎨 UI/UX Design Patterns

### 1. Popup System

**Pattern**: Full-screen overlay modal (Bootstrap-style)
**Accessibility**: WCAG 2.1 AA compliant
- Role: `dialog`, `aria-modal="true"`
- Keyboard: Tab navigation, Enter/Space activation, Escape close
- Focus management: Auto-focus first problem, trap focus within modal

### 2. Right Panel Design

**Pattern**: Fixed-position side panel (inspired by Gmail/Slack)
- **Width**: 450px fixed
- **Position**: `position: fixed; right: 0; top: 0; bottom: 0;`
- **Animation**: slideInRight (0.3s ease)
- **Z-index**: 1000 (below popup overlay: 10000)

### 3. Loading States

**Pattern**: Skeleton screens + progress indicators
- Animated agent icon (spin animation)
- Progress bar (indeterminate)
- Loading text with retry count
- Timeout warning after 60 seconds

### 4. Error Handling

**Pattern**: Inline error messages with retry actions
- Error states: timeout, API failure, validation error
- Color coding: Red (error), Yellow (warning), Blue (info)
- Recovery actions: Retry button, close button, refresh suggestion

---

## 🛠️ Development Guidelines

### Adding New Agents

1. **Update Data Layer** (`agent_problems.js`):
```javascript
{
  id: 'agent_22',
  agentNumber: 22,
  name: '새로운 에이전트',
  icon: '🆕',
  description: '에이전트 설명...',
  problems: [
    '문제 1...',
    '문제 2...',
    // ... 5-7 problems
  ]
}
```

2. **Update Knowledge File**: Create `/agents/agent22_*/agent22_*.md`

3. **Update Main Page**: Add agent card to dashboard

### Modifying Problem Lists

**Location**: `/assets/js/agent_problems.js`
**Process**:
1. Find agent in `window.agentProblems` array
2. Edit `problems` array (5-7 items recommended)
3. Keep problems concise (1-2 sentences)
4. Clear browser cache after update

### Customizing Analysis Structure

**Backend** (`gpt_helper.php`):
```php
function buildAnalysisPrompt(...) {
  // Modify prompt structure
  $prompt .= "## 요청 사항\n";
  $prompt .= "위 문제에 대해 다음 4가지 섹션으로...\n";
  // Add/remove sections
}

function parseGPTResponse($response) {
  // Update regex patterns
  $patterns = [
    'new_section' => '/\[새 섹션\]\s*\n(.*?)(?=\n\[|$)/s',
    // ...
  ];
}
```

**Frontend** (`agent_analysis.js`):
```javascript
function displayAnalysisReport(agent, problemText, analysis) {
  // Update HTML structure
  const sectionHtml = `
    <div class="analysis-section">
      <h4>📌 새 섹션</h4>
      <p>${analysis.new_section}</p>
    </div>
  `;
}
```

### Performance Optimization

**Caching Strategy**:
```php
// Example: Redis cache for 24 hours
$cache_key = "analysis_{$agent_id}_{$problem_index}_{$student_id}";
$cached = $redis->get($cache_key);
if ($cached) {
  return json_decode($cached, true);
}

// Generate analysis...
$redis->setex($cache_key, 86400, json_encode($analysis));
```

**Bundle Size Optimization**:
- Minify JavaScript files
- Compress CSS with gzip
- Use CDN for common libraries
- Lazy-load non-critical components

---

## 🧪 Testing

### Manual Testing Checklist

**Popup Functionality**:
- [ ] Click 🎯 button opens popup
- [ ] Popup displays correct agent info
- [ ] Problem list shows 5-7 items
- [ ] Click problem closes popup and triggers analysis
- [ ] Escape key closes popup
- [ ] Click backdrop closes popup
- [ ] Keyboard navigation works (Tab, Enter, Space)

**Analysis Generation**:
- [ ] Loading state appears
- [ ] Analysis completes in <20 seconds (GPT) or <2 seconds (placeholder)
- [ ] Right panel displays with 4 sections
- [ ] Close button works
- [ ] Multiple analyses can be generated sequentially

**Error Handling**:
- [ ] Timeout warning appears after 60 seconds
- [ ] Retry button works (up to 3 attempts)
- [ ] Error messages display correctly
- [ ] Network failure handled gracefully

**Accessibility**:
- [ ] Focus indicators visible
- [ ] Keyboard navigation complete
- [ ] Screen reader announces elements
- [ ] High contrast mode works
- [ ] Reduced motion respected

### Automated Testing (Future)

**Unit Tests** (Recommended: Jest):
```javascript
describe('Agent Popup System', () => {
  test('showAgentProblemPopup creates modal', () => {
    window.showAgentProblemPopup(0);
    const popup = document.getElementById('agent-problem-popup');
    expect(popup).toBeTruthy();
  });

  test('closeAgentProblemPopup removes modal', () => {
    window.showAgentProblemPopup(0);
    window.closeAgentProblemPopup();
    setTimeout(() => {
      const popup = document.getElementById('agent-problem-popup');
      expect(popup).toBeFalsy();
    }, 250);
  });
});
```

**Integration Tests** (Recommended: Playwright):
```javascript
test('Full analysis workflow', async ({ page }) => {
  await page.goto('https://mathking.kr/...');
  await page.click('[data-agent="1"] button');
  await page.click('.agent-problem-item:first-child');
  await page.waitForSelector('#analysis-panel', { timeout: 30000 });
  const analysis = await page.textContent('#analysis-panel');
  expect(analysis).toContain('문제 상황');
});
```

---

## 📊 Monitoring & Logging

### Client-Side Logging

**Console Logs**:
```javascript
console.log('[agent_popup.js] Showing popup for agent:', agent.name);
console.log('[agent_analysis.js] API request:', requestData);
console.log('[agent_analysis.js] API response:', result);
console.error('[agent_analysis.js] Analysis generation error:', error);
```

**Error Tracking** (Recommended: Sentry):
```javascript
if (typeof Sentry !== 'undefined') {
  Sentry.captureException(error, {
    tags: {
      component: 'agent_analysis',
      agent_id: agent.id,
      student_id: studentId
    }
  });
}
```

### Server-Side Logging

**PHP Error Logs** (`/var/log/apache2/error.log`):
```php
error_log("[generate_agent_analysis.php] Request received: " . json_encode($data));
error_log("[gpt_helper.php] Calling GPT API | Model: $model");
error_log("[gpt_helper.php] API call successful | Response length: $length chars");
```

**Monitoring Queries**:
```bash
# Watch error log in real-time
tail -f /var/log/apache2/error.log | grep agent_analysis

# Check GPT API calls
grep "gpt_helper" /var/log/apache2/error.log | tail -20

# Filter errors only
grep "error" /var/log/apache2/error.log | grep agent_
```

---

## 🔐 Security Considerations

### 1. Input Validation

**Frontend**:
```javascript
// Escape user input in HTML
problemText.replace(/'/g, "\\'")
  .replace(/</g, "&lt;")
  .replace(/>/g, "&gt;");
```

**Backend**:
```php
// Validate agent_id format
if (!preg_match('/^agent_\d{2}$/', $data['agent_id'])) {
  sendError('Invalid agent_id format');
}

// Sanitize problem text
$problem_text = htmlspecialchars($data['problem_text'], ENT_QUOTES, 'UTF-8');
```

### 2. API Key Protection

**Never commit keys to Git**:
```bash
# .gitignore
/api/gpt_config.php
```

**Use environment variables** (recommended):
```php
define('OPENAI_API_KEY', getenv('OPENAI_API_KEY'));
```

### 3. SQL Injection Prevention

**Use prepared statements**:
```php
// ✅ SAFE
$student = $DB->get_record('user', ['id' => $student_id]);

// ❌ UNSAFE
$student = $DB->get_record_sql("SELECT * FROM user WHERE id = $student_id");
```

### 4. XSS Prevention

**Escape output**:
```php
echo htmlspecialchars($student_name, ENT_QUOTES, 'UTF-8');
```

**Content Security Policy**:
```php
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline';");
```

---

## 🚀 Deployment

### Pre-Deployment Checklist

- [ ] GPT API key configured (or placeholder mode)
- [ ] Browser cache busting enabled (`?v=<?php echo time(); ?>`)
- [ ] Error logging configured
- [ ] Database table created (if using audit trail)
- [ ] Accessibility tested
- [ ] Mobile responsiveness verified
- [ ] Performance benchmarked (<3s load time)

### Deployment Steps

1. **Backup existing files**:
```bash
cp -r /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration \
      /backup/orchestration_$(date +%Y%m%d_%H%M%S)
```

2. **Upload new files**:
```bash
# Upload via FTP/SFTP
- assets/js/agent_problems.js
- assets/js/agent_popup.js
- assets/js/agent_analysis.js
- assets/css/agent_popup_enhancements.css
- api/generate_agent_analysis.php
- api/gpt_config.php
- api/gpt_helper.php
```

3. **Update index.php**:
- Add CSS link
- Add script tags
- Modify card template

4. **Clear cache**:
```bash
# Moodle cache
php admin/cli/purge_caches.php

# Browser cache (automatic via ?v=timestamp)
```

5. **Test deployment**:
- Load page in browser
- Test popup functionality
- Verify analysis generation
- Check error logs

### Rollback Procedure

```bash
# Restore from backup
rm -rf /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration
cp -r /backup/orchestration_YYYYMMDD_HHMMSS \
      /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration
```

---

## 📚 Code Examples

### Example 1: Adding Custom Analysis Section

**Backend** (`gpt_helper.php`):
```php
function buildAnalysisPrompt(...) {
  // ... existing code ...
  $prompt .= "5. **[실행 일정]**: 개선 방안의 구체적인 실행 일정 (주차별)\n\n";
}

function parseGPTResponse($response) {
  $patterns = [
    // ... existing patterns ...
    'implementation_schedule' => '/\[실행\s*일정\]\s*\n(.*?)(?=\n\[|$)/s'
  ];
  // ... rest of function ...
}
```

**Frontend** (`agent_analysis.js`):
```javascript
function displayAnalysisReport(agent, problemText, analysis) {
  // ... existing sections ...

  sectionsHtml += `
    <div class="analysis-section" style="margin-bottom: 28px;">
      <h4 style="color: #0ea5e9; font-size: 18px; margin-bottom: 14px; display: flex; align-items: center; font-weight: 700;">
        <span style="margin-right: 10px;">📅</span>
        실행 일정
      </h4>
      <div style="color: #495057; font-size: 14px; line-height: 1.8;">
        ${analysis.implementation_schedule || '일정 정보가 없습니다.'}
      </div>
    </div>
  `;
}
```

### Example 2: Analytics Tracking

**Add to `generateAnalysisReport`**:
```javascript
window.generateAnalysisReport = async function(agent, problemText, problemIndex, retryCount = 0) {
  // ... existing code ...

  // Track analytics
  if (typeof gtag !== 'undefined') {
    gtag('event', 'analysis_generated', {
      'event_category': 'Agent Analysis',
      'event_label': agent.name,
      'value': problemIndex
    });
  }

  // ... rest of function ...
};
```

### Example 3: Problem Frequency Tracking

**Client-Side**:
```javascript
// Track problem selection frequency
function trackProblemSelection(agentIndex, problemIndex) {
  const key = `problem_freq_${agentIndex}_${problemIndex}`;
  const current = parseInt(localStorage.getItem(key) || '0');
  localStorage.setItem(key, (current + 1).toString());
}

// Get most selected problems
function getMostSelectedProblems(agentIndex, limit = 3) {
  const agent = window.agentProblems[agentIndex];
  const frequencies = agent.problems.map((problem, idx) => ({
    problem,
    frequency: parseInt(localStorage.getItem(`problem_freq_${agentIndex}_${idx}`) || '0')
  }));

  return frequencies
    .sort((a, b) => b.frequency - a.frequency)
    .slice(0, limit);
}
```

---

## 🔄 Future Enhancements

### Planned Features

1. **Analysis History**:
   - Store past analyses per student
   - Show history in panel
   - Compare changes over time

2. **Multi-Student Comparison**:
   - Compare analyses across students
   - Identify common patterns
   - Class-level insights

3. **Export Functionality**:
   - Export analysis to PDF
   - Email reports to teachers
   - Print-friendly format

4. **Teacher Annotations**:
   - Add notes to analyses
   - Mark implemented improvements
   - Track progress

5. **Enhanced Analytics**:
   - Problem frequency dashboard
   - Agent usage statistics
   - Success rate tracking

---

## 📞 Support & Contribution

### Reporting Bugs

1. Check error logs: `/var/log/apache2/error.log`
2. Reproduce in browser console (F12)
3. Document steps to reproduce
4. Submit to system administrator

### Code Contribution Guidelines

1. Follow existing code style (indentation, naming)
2. Add comments for complex logic
3. Test thoroughly before committing
4. Update documentation
5. Use semantic commit messages

**Example Commit Message**:
```
feat: Add problem frequency tracking to agent popup

- Store selection counts in localStorage
- Display most selected problems badge
- Add analytics tracking for problem selection

Closes #123
```

---

## 📄 License & Credits

**Developer**: ALT42 Development Team
**License**: Proprietary - Moodle ALT42 Project
**Last Updated**: 2025-01-21
**Version**: 1.0

For questions or support, contact the system administrator.
