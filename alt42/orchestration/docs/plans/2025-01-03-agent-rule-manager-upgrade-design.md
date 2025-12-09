# Agent Rule Manager Upgrade Design

**Date**: 2025-01-03
**Version**: 1.0
**Status**: Approved
**Author**: Claude Code (based on user requirements)

## Executive Summary

This document defines the complete design for upgrading the MVP Rule Manager to support orchestration of 22 AI agents with individual YAML-based decision rules, conditional execution flow, real-time monitoring, and version control.

## 1. Project Overview

### 1.1 Purpose

Transform the existing `rule_manager.php` (single-scenario YAML editor) into a comprehensive **Agent Orchestration Hub** that:

- Manages 22 individual agent decision rules (YAML-based)
- Orchestrates conditional agent execution based on student context
- Provides real-time monitoring dashboard
- Tracks rule changes with version history
- Offers agent-specific rule editors with CRUD operations

### 1.2 Goals

1. **Rule Management**: Create and manage YAML decision rules for each of 22 agents
2. **Orchestration**: Enable conditional agent execution based on student state and dependencies
3. **Monitoring**: Real-time dashboard showing agent status, execution logs, and statistics
4. **Version Control**: Track all rule changes with user attribution and rollback capability
5. **Security**: Role-based access control (block students/parents, allow teachers/admins)

### 1.3 Scope

**In Scope:**
- 22 agent-specific YAML rule files
- Module-based UI architecture (hub + 22 individual editors + orchestration dashboard)
- Conditional execution engine
- Real-time monitoring and logging
- Database schema for logs, changes, and status
- Error handling with file:line location tracking
- Playwright test automation

**Out of Scope:**
- Agent implementation logic (只管理规则，不实现agent功能)
- Machine learning-based rule generation
- Multi-language UI (Korean only for MVP)
- Mobile responsive design (desktop-first)

---

## 2. Architecture Design

### 2.1 Selected Approach: **Module-Based Architecture**

**Rationale:**
- Simple PHP 7.1 compatibility without complex SPA frameworks
- Clear separation of concerns (each agent = separate page)
- Easy maintenance and debugging
- Minimal JavaScript requirements
- Server-side rendering for better compatibility

**Trade-offs Accepted:**
- Page navigation required (vs single-page app)
- Some code duplication across agent pages (mitigated by shared components)

### 2.2 System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      User Interface Layer                    │
├─────────────────────────────────────────────────────────────┤
│  rule_manager_hub.php          │  orchestrator_dashboard.php │
│  (Main Dashboard)              │  (Real-time Monitoring)     │
├────────────────────────────────┴─────────────────────────────┤
│  agent_rules/agent01_rules.php ... agent22_rules.php        │
│  (22 Individual Agent Rule Editors)                          │
├─────────────────────────────────────────────────────────────┤
│                    Shared Components                         │
│  agent_card.php | rule_form.php | condition_builder.php     │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                   Business Logic Layer                       │
├─────────────────────────────────────────────────────────────┤
│  orchestration/orchestrator.php     (Core orchestration)    │
│  orchestration/agent_executor.php   (Individual execution)  │
│  orchestration/condition_evaluator.php (Condition matching) │
├─────────────────────────────────────────────────────────────┤
│  lib/yaml_parser.php        (Enhanced YAML parsing)         │
│  lib/rule_validator.php     (Rule validation logic)         │
│  lib/agent_monitor.php      (Monitoring & statistics)       │
└─────────────────────────────────────────────────────────────┘
                              ↕
┌─────────────────────────────────────────────────────────────┐
│                      Data Layer                              │
├─────────────────────────────────────────────────────────────┤
│  decision/rules/agent01_rules.yaml ... agent22_rules.yaml  │
│  decision/rules/orchestration_flow.yaml                     │
├─────────────────────────────────────────────────────────────┤
│  Database Tables:                                            │
│  - mvp_agent_logs         (Execution history)               │
│  - mvp_rule_changes       (Version control)                 │
│  - mvp_agent_status       (Current agent states)            │
└─────────────────────────────────────────────────────────────┘
```

### 2.3 Directory Structure

```
mvp_system/
├── ui/
│   ├── rule_manager_hub.php          # Main entry dashboard
│   ├── rule_manager_hub.css
│   ├── rule_manager_hub.js
│   │
│   ├── agent_rules/
│   │   ├── agent01_rules.php          # Individual agent editors
│   │   ├── agent02_rules.php
│   │   ├── ... (agent03-22)
│   │   ├── agent_rules.css            # Shared styles
│   │   └── agent_rules.js             # Shared JavaScript
│   │
│   ├── orchestration/
│   │   ├── orchestrator_dashboard.php # Real-time monitoring
│   │   ├── orchestrator.css
│   │   └── orchestrator.js
│   │
│   └── components/
│       ├── agent_card.php             # Reusable agent card
│       ├── rule_form.php              # Reusable rule form
│       ├── condition_builder.php      # Condition UI builder
│       └── execution_timeline.php     # Execution history UI
│
├── decision/
│   └── rules/
│       ├── agent01_rules.yaml         # Per-agent decision rules
│       ├── agent02_rules.yaml
│       ├── ... (agent03-22)
│       └── orchestration_flow.yaml    # Agent execution flow
│
├── orchestration/
│   ├── orchestrator.php               # Core orchestration engine
│   ├── agent_executor.php             # Individual agent runner
│   └── condition_evaluator.php        # Condition matching logic
│
├── lib/
│   ├── yaml_parser.php                # Enhanced YAML parser
│   ├── rule_validator.php             # Rule validation
│   └── agent_monitor.php              # Monitoring & logging
│
└── scripts/
    ├── migrate_to_new_rule_manager.php
    └── sql/
        ├── create_mvp_agent_logs.sql
        ├── create_mvp_rule_changes.sql
        └── create_mvp_agent_status.sql
```

---

## 3. Data Model

### 3.1 Database Schema

#### Table: `mvp_agent_logs`
Stores execution history for all agent runs.

```sql
CREATE TABLE mvp_agent_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(50) NOT NULL,              -- 'agent01', 'agent02', etc.
    student_id INT NOT NULL,
    execution_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    status ENUM('success', 'failed', 'skipped') DEFAULT 'success',
    rules_matched TEXT,                          -- JSON array of matched rule_ids
    execution_result TEXT,                       -- JSON result data
    error_message VARCHAR(500),
    duration_ms INT,
    INDEX idx_agent_student (agent_id, student_id),
    INDEX idx_execution_time (execution_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `mvp_rule_changes`
Version control for rule modifications.

```sql
CREATE TABLE mvp_rule_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,                     -- user_id (teacher/admin)
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    change_type ENUM('create', 'update', 'delete') NOT NULL,
    rule_id VARCHAR(100),
    old_value TEXT,                              -- JSON snapshot before
    new_value TEXT,                              -- JSON snapshot after
    INDEX idx_agent_time (agent_id, changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Table: `mvp_agent_status`
Current status and statistics for each agent.

```sql
CREATE TABLE mvp_agent_status (
    agent_id VARCHAR(50) PRIMARY KEY,
    last_run DATETIME,
    total_executions INT DEFAULT 0,
    success_count INT DEFAULT 0,
    failed_count INT DEFAULT 0,
    avg_duration_ms INT DEFAULT 0,
    current_status ENUM('idle', 'running', 'error') DEFAULT 'idle',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 3.2 YAML Structure

#### Per-Agent Rules (e.g., `agent08_rules.yaml`)

```yaml
version: "1.0"
agent_id: "agent08"
agent_name: "Calmness Monitor"
description: "침착도 기반 학습 적합도 판단 및 개입"
last_updated: "2025-01-03"
updated_by: "teacher_name"

# Orchestration metadata
orchestration:
  priority: 80                    # Execution priority (higher = earlier)
  depends_on: ["agent05"]         # Prerequisite agents
  can_run_parallel: false         # Parallel execution capability

# Decision rules
rules:
  - rule_id: "calm_break_critical"
    priority: 95
    description: "침착도 75 미만 시 긴급 휴식"
    conditions:
      - field: "calmness_score"
        operator: "<"
        value: 75
    action: "micro_break"
    params:
      duration: 300
      message: "짧은 휴식이 필요해요"
    confidence: 0.95
    rationale: "침착도 낮을 시 학습 효율 급감"
```

#### Orchestration Flow (`orchestration_flow.yaml`)

```yaml
version: "1.0"
description: "22개 Agent 실행 흐름 정의"

scenarios:
  - scenario_id: "student_login"
    description: "학생 로그인 시"
    trigger: "login_event"
    agents: ["agent01", "agent14"]
    execution_mode: "sequential"

  - scenario_id: "learning_session_start"
    description: "학습 세션 시작"
    trigger: "session_start"
    agents: ["agent08", "agent05", "agent04"]
    execution_mode: "sequential"

  - scenario_id: "problem_solving"
    description: "문제 풀이 중"
    trigger: "problem_activity"
    conditional_agents:
      - agent_id: "agent08"
        condition: "always"
      - agent_id: "agent12"
        condition: "calmness_score < 80"
      - agent_id: "agent09"
        condition: "consecutive_errors >= 3"
```

---

## 4. Component Design

### 4.1 Main Dashboard (rule_manager_hub.php)

**Purpose**: Central hub displaying all 22 agents with status overview.

**Key Features**:
- Grid layout of 22 agent cards (3 columns)
- Global statistics (total agents, active agents, total executions, success rate)
- Filter/search functionality
- Navigation to individual agent editors and orchestration dashboard

**Data Flow**:
1. Read all 22 agent metadata from `agentXX.md` files
2. Scan `decision/rules/` for YAML file status
3. Query `mvp_agent_status` for real-time statistics
4. Render agent cards using `components/agent_card.php`

### 4.2 Agent Rule Editors (agent_rules/agentXX_rules.php)

**Purpose**: CRUD interface for managing individual agent rules.

**Key Features**:
- Display agent information (name, purpose, dependencies)
- List all rules with priority sorting
- Add/Edit/Delete rule operations
- Rule validation before save
- Change history display (from `mvp_rule_changes`)
- Usage statistics (from `mvp_agent_logs`)

**Data Flow**:
1. Load `decision/rules/agentXX_rules.yaml`
2. Parse YAML into editable structure
3. Display rules in card format
4. On save: Validate → Write YAML → Log change to `mvp_rule_changes`

### 4.3 Orchestration Dashboard (orchestrator_dashboard.php)

**Purpose**: Real-time monitoring of agent execution.

**Key Features**:
- Current execution status (running/queued/completed)
- Agent dependency graph visualization
- Real-time execution log (last 100 entries)
- Performance metrics (last 24 hours)
- Auto-refresh every 5 seconds

**Data Flow**:
1. Query `mvp_agent_status` for current states
2. Query `mvp_agent_logs` for recent executions (last 5 minutes)
3. Render dependency graph from `orchestration_flow.yaml`
4. JavaScript auto-refresh using AJAX

### 4.4 Orchestration Engine (orchestration/orchestrator.php)

**Purpose**: Core logic for conditional agent execution.

**Key Functions**:
- `executeScenario($scenario_id, $student_context)`: Run agents for a scenario
- `evaluateConditions($agent_id, $context)`: Check if agent should run
- `executeDependencyChain($agent_ids)`: Respect agent dependencies
- `logExecution($agent_id, $result)`: Record to database

**Execution Algorithm**:
```
1. Load orchestration_flow.yaml
2. Find matching scenario by trigger
3. For each agent in scenario:
   a. Check dependencies (wait if needed)
   b. Evaluate conditions (skip if not met)
   c. Load agent's YAML rules
   d. Execute agent_executor.php
   e. Log result to mvp_agent_logs
   f. Update mvp_agent_status
4. Return aggregated results
```

---

## 5. Security & Error Handling

### 5.1 Security Measures

**Access Control**:
```php
// Block students and parents
$blocked_roles = ['student', 'parent'];
if (in_array($role, $blocked_roles)) {
    http_response_code(403);
    die("Access Denied | File: " . __FILE__ . ":" . __LINE__);
}
```

**CSRF Protection**:
- Generate token on page load: `$_SESSION['csrf_token']`
- Validate on all form submissions
- Reject mismatched tokens

**Input Validation**:
- Agent ID: Whitelist `agent01` to `agent22` only
- Rule priority: Range check `0-100`
- File path: Prevent path traversal attacks
- YAML content: Validate structure before save

**SQL Injection Prevention**:
- Use parameterized queries exclusively
- No direct string concatenation in SQL

### 5.2 Error Handling

**Standard Error Format**:
```php
throw new RuleManagerException(
    "YAML parsing failed: " . $error_details,
    500,
    __FILE__,
    __LINE__
);

// Output format:
// [Error] YAML parsing failed: Invalid syntax at line 15
// File: /home/moodle/.../agent_rules/agent08_rules.php
// Line: 145
// Timestamp: 2025-01-03 14:30:22
```

**Error Categories**:
1. **YAML Parsing Errors**: Invalid syntax, missing fields
2. **Validation Errors**: Rule data doesn't meet schema
3. **Database Errors**: Connection failure, query errors
4. **File System Errors**: Permission denied, file not found
5. **Execution Errors**: Agent runtime failures

**Recovery Strategies**:
- YAML error → Show edit form with error highlighting
- Validation error → Display validation messages inline
- Database error → Retry with exponential backoff (3 attempts)
- Execution error → Log error, continue with next agent

---

## 6. Performance Optimization

### 6.1 Caching Strategy

**YAML File Caching**:
```php
class YamlCache {
    private static $cache = [];

    public static function get($file_path) {
        $mtime = filemtime($file_path);
        $cache_key = md5($file_path);

        if (isset(self::$cache[$cache_key]) &&
            self::$cache[$cache_key]['mtime'] === $mtime) {
            return self::$cache[$cache_key]['data'];
        }

        $data = parseYamlFile($file_path);
        self::$cache[$cache_key] = ['data' => $data, 'mtime' => $mtime];
        return $data;
    }
}
```

**Dashboard Statistics Caching**:
- Cache duration: 5 minutes
- Invalidate on rule changes
- Reduces database queries by ~90%

### 6.2 Database Indexing

All tables include optimized indexes:
- `mvp_agent_logs`: `(agent_id, student_id)`, `(execution_time)`
- `mvp_rule_changes`: `(agent_id, changed_at)`
- `mvp_agent_status`: Primary key on `agent_id`

### 6.3 Performance Targets

- Dashboard load time: < 2 seconds (all 22 agents)
- Individual agent editor: < 1 second
- Rule save operation: < 500ms
- Orchestration execution: < 100ms per agent
- Real-time dashboard refresh: < 5 seconds

---

## 7. Testing Strategy

### 7.1 Unit Testing

**Components to Test**:
- `lib/yaml_parser.php`: Parse valid/invalid YAML
- `lib/rule_validator.php`: Validate rule schemas
- `orchestration/condition_evaluator.php`: Evaluate conditions
- `lib/agent_monitor.php`: Log execution, calculate stats

### 7.2 Integration Testing

**Test Scenarios**:
1. Create rule → Save to YAML → Reload page → Verify displayed
2. Edit rule → Check version logged to `mvp_rule_changes`
3. Delete rule → Verify removed from YAML
4. Execute scenario → Check agents run in dependency order
5. Agent failure → Verify other agents continue

### 7.3 Playwright Automation

**Test Coverage**:
- Dashboard loads all 22 agents
- Click agent card → Navigate to editor
- Add new rule → Form validation → Save success
- Edit existing rule → Version history updated
- Orchestration dashboard shows real-time logs
- Dependency graph renders correctly

**Sample Test**:
```javascript
test('should create new rule for agent08', async ({ page }) => {
    await page.goto('.../agent_rules/agent08_rules.php');
    await page.click('button:has-text("Add New Rule")');
    await page.fill('#ruleId', 'test_auto_001');
    await page.fill('#priority', '85');
    await page.click('button[type="submit"]');
    await expect(page.locator('.success-message')).toBeVisible();
});
```

### 7.4 Security Testing

**Test Cases**:
- Student login attempt → 403 Forbidden
- CSRF token mismatch → Request rejected
- Invalid agent_id (e.g., `agent99`) → Error displayed
- Path traversal attempt (e.g., `../../config.php`) → Blocked
- SQL injection in rule_id field → Sanitized

---

## 8. Implementation Plan

### Phase 1: Foundation (1-2 days)
- [ ] Create database tables (SQL scripts in `scripts/sql/`)
- [ ] Develop `lib/yaml_parser.php` (enhanced parser with caching)
- [ ] Develop `lib/rule_validator.php` (validation logic)
- [ ] Develop `lib/agent_monitor.php` (logging and statistics)
- [ ] Generate 22 initial YAML files with template structure

### Phase 2: Core UI (2-3 days)
- [ ] Build `rule_manager_hub.php` (main dashboard)
- [ ] Create `components/agent_card.php` (reusable card)
- [ ] Create `components/rule_form.php` (reusable form)
- [ ] Develop shared CSS (`agent_rules.css`)
- [ ] Implement AJAX endpoints for real-time updates

### Phase 3: Agent Rule Editors (2-3 days)
- [ ] Create template for `agentXX_rules.php`
- [ ] Generate 22 individual agent pages
- [ ] Implement rule CRUD operations (Create, Read, Update, Delete)
- [ ] Add version control logging to `mvp_rule_changes`
- [ ] Test rule editing workflow for 3 sample agents

### Phase 4: Orchestration Engine (2-3 days)
- [ ] Develop `orchestration/orchestrator.php` (core engine)
- [ ] Develop `orchestration/agent_executor.php` (individual runner)
- [ ] Develop `orchestration/condition_evaluator.php` (condition logic)
- [ ] Create `orchestration_flow.yaml` with sample scenarios
- [ ] Build `orchestrator_dashboard.php` (monitoring UI)
- [ ] Test conditional execution with mock student data

### Phase 5: Integration & Testing (1-2 days)
- [ ] Integration testing with existing MVP system
- [ ] Performance testing (load all 22 agents)
- [ ] Security testing (access control, CSRF, injection)
- [ ] Playwright automation tests (dashboard, editors, orchestration)
- [ ] User acceptance testing with 2-3 teachers
- [ ] Documentation updates (README, API docs)

### Phase 6: Deployment (1 day)
- [ ] Run migration script on production server
- [ ] Backup existing `rule_manager.php` and YAML files
- [ ] Deploy new code to server
- [ ] Verify all 22 agents load correctly
- [ ] Monitor logs for first 24 hours
- [ ] Collect teacher feedback

**Total Estimated Time**: 8-12 days

---

## 9. Migration Strategy

### 9.1 Data Migration

**Existing Data**:
- Current `calm_break_rules.yaml` → Migrate to `agent08_rules.yaml`
- Preserve existing rule IDs and priorities
- Add new orchestration metadata fields

**Migration Script**:
```bash
php scripts/migrate_to_new_rule_manager.php
```

**Steps**:
1. Create database tables
2. Generate 22 YAML files from agent metadata
3. Migrate existing `calm_break_rules.yaml` to `agent08_rules.yaml`
4. Initialize `mvp_agent_status` with default values
5. Create backup of old files

### 9.2 Rollback Plan

If critical issues occur:
1. Restore backup YAML files
2. Revert to old `rule_manager.php`
3. Drop new database tables (data preserved in backups)
4. Re-deploy after fixes

**Backup Location**: `/mvp_system/backups/2025-01-03/`

---

## 10. Success Criteria

### 10.1 Functional Requirements

- ✅ All 22 agents display on hub dashboard
- ✅ Each agent has individual rule editor
- ✅ Rules can be created, edited, deleted (CRUD)
- ✅ Version control logs all changes with user attribution
- ✅ Orchestration engine executes agents conditionally
- ✅ Dependency resolution works correctly
- ✅ Real-time monitoring dashboard updates every 5 seconds
- ✅ Error messages include file:line location

### 10.2 Performance Requirements

- ✅ Dashboard loads in < 2 seconds
- ✅ Rule save completes in < 500ms
- ✅ Orchestration processes 22 agents in < 3 seconds total
- ✅ Database queries use proper indexes (verified with EXPLAIN)

### 10.3 Security Requirements

- ✅ Students and parents blocked from access
- ✅ CSRF protection on all forms
- ✅ SQL injection prevented via parameterized queries
- ✅ Path traversal attacks blocked
- ✅ All user inputs validated

### 10.4 Testing Requirements

- ✅ 100% of CRUD operations tested
- ✅ 10+ Playwright test cases passing
- ✅ 3+ teacher user acceptance sign-offs
- ✅ Zero critical bugs in production after 7 days

---

## 11. Future Enhancements (Out of Scope for v1.0)

1. **Rule Recommendation Engine**: ML-based suggestions for new rules
2. **A/B Testing Framework**: Test different rule configurations
3. **Mobile Responsive Design**: Tablet and phone support
4. **Multi-language UI**: English translation
5. **Rule Templates Library**: Pre-built rule sets for common scenarios
6. **Advanced Visualizations**: D3.js dependency graphs, execution heatmaps
7. **Webhook Integration**: Trigger external systems on agent execution
8. **Rule Conflict Detection**: Identify contradictory rules across agents

---

## 12. References

### 12.1 Related Documents

- `/alt42/orchestration/.cursor/rules/create-prd.md` - PRD generation template
- `/alt42/orchestration/.cursor/rules/generate-tasks.md` - Task generation template
- `/alt42/orchestration/agents/agent08_calmness/agent08_calmness.md` - Sample agent spec
- `/alt42/orchestration/mvp_system/ui/rule_manager.php` - Existing implementation

### 12.2 Technology Stack

- **Backend**: PHP 7.1.9
- **Database**: MySQL 5.7
- **Frontend**: HTML5, CSS3, Vanilla JavaScript (no frameworks)
- **YAML Parser**: Custom PHP implementation
- **Testing**: Playwright (MCP integration)
- **Server**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/

---

## Appendix A: YAML Schema Reference

### Agent Rule File Schema

```yaml
version: string (required)
agent_id: string (required, pattern: agent[01-22])
agent_name: string (required)
description: string (required)
last_updated: string (required, format: YYYY-MM-DD)
updated_by: string (required)

orchestration:
  priority: integer (required, range: 0-100)
  depends_on: array<string> (optional, default: [])
  can_run_parallel: boolean (optional, default: false)

rules:
  - rule_id: string (required, unique)
    priority: integer (required, range: 0-100)
    description: string (required)
    conditions:
      - field: string (required)
        operator: string (required, enum: <, >, <=, >=, ==, !=)
        value: number|string (required)
    action: string (required)
    params: object (optional)
    confidence: float (required, range: 0.0-1.0)
    rationale: string (required)
```

### Orchestration Flow Schema

```yaml
version: string (required)
description: string (required)

scenarios:
  - scenario_id: string (required, unique)
    description: string (required)
    trigger: string (required)
    agents: array<string> (optional)
    execution_mode: string (optional, enum: sequential|parallel)
    conditional_agents:
      - agent_id: string (required)
        condition: string (required, format: field operator value)
```

---

## Appendix B: API Endpoints (AJAX)

### Dashboard Endpoints

```
GET  /ui/rule_manager_hub.php
     → Returns: HTML page with 22 agent cards

GET  /ui/api/get_agent_stats.php?agent_id=agent08
     → Returns: JSON with agent statistics

POST /ui/api/refresh_dashboard.php
     → Returns: JSON with updated global stats
```

### Rule Management Endpoints

```
GET  /ui/agent_rules/agent08_rules.php
     → Returns: HTML page with agent08 rules

POST /ui/api/save_rule.php
     Body: {agent_id, rule_data, csrf_token}
     → Returns: JSON {success: true, rule_id}

POST /ui/api/delete_rule.php
     Body: {agent_id, rule_id, csrf_token}
     → Returns: JSON {success: true}
```

### Orchestration Endpoints

```
GET  /orchestration/orchestrator_dashboard.php
     → Returns: HTML monitoring dashboard

GET  /orchestration/api/get_execution_logs.php?limit=100
     → Returns: JSON array of recent executions

POST /orchestration/api/execute_scenario.php
     Body: {scenario_id, student_id, context}
     → Returns: JSON {results: [...], total_duration_ms}
```

---

**Document Status**: ✅ Approved for Implementation
**Next Step**: Proceed to Phase 5 (Worktree Setup) and Phase 6 (Task Planning)
