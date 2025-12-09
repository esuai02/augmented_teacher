# PRD: Agent Policy Integration - Dynamic Rule Loading from agents/*.md

## Introduction/Overview

The MVP System v1.0 successfully demonstrated AI-driven intervention with **static YAML rules**. The deferred Phase 3 implements dynamic policy loading from the existing **21-agent system** (`agents/agent*.md` files), enabling the MVP to leverage the comprehensive agent knowledge base without code changes.

**Problem Solved**: Current MVP uses hardcoded YAML rules disconnected from the rich policy definitions in `agents/agent08_calmness/`, `agents/agent20_intervention_preparation/`, and `agents/agent21_intervention_execution/`. Teachers and curriculum designers cannot update intervention logic without developer involvement.

**Goal**: Enable the MVP system to **read, parse, and hot-reload** intervention policies directly from the 21-agent markdown files, creating a living connection between agent knowledge and production interventions.

## Goals

1. **Dynamic Policy Loading**: Parse agents/*.md files to extract intervention rules and thresholds
2. **Zero-Code Updates**: Teachers/curriculum designers can modify intervention logic by editing markdown files
3. **Agent System Integration**: Leverage existing agent08 (Calmness), agent20 (Intervention Prep), agent21 (Intervention Execution) policies
4. **Hot Reload**: Policy changes take effect within 5 minutes without system restart
5. **Backward Compatibility**: Maintain support for existing YAML rules as fallback
6. **Validation & Safety**: Prevent invalid policies from breaking production system

## User Stories

### Policy Editing
1. **As a curriculum director**, I want to adjust calm_score thresholds in `agents/agent08_calmness/agent08_calmness.md` so interventions align with our evolving pedagogy.
2. **As a teacher**, I want to customize intervention messages in `agents/agent20_intervention_preparation/intervention_templates.md` to match my teaching style.
3. **As a system designer**, I want to add new intervention types in agent20 files and see them automatically available in the system.

### Policy Management
4. **As a technical lead**, I want policy parsing errors to fallback to last-known-good rules so students aren't affected by editing mistakes.
5. **As an auditor**, I want to see version history of all policy changes with timestamps and authors.
6. **As a developer**, I want JSON Schema validation to catch invalid policies before activation.

### Agent Integration
7. **As the MVP system**, I want to use agent08's calm definitions as the source of truth for intervention thresholds.
8. **As the intervention dispatcher**, I want to use agent20's message templates for consistent student communication.
9. **As the decision engine**, I want to honor agent21's execution policies for intervention timing and frequency.

## Functional Requirements

### FR1: Markdown Policy Parser
1.1. The system MUST parse markdown files in `agents/agent08_calmness/`, `agents/agent20_*/`, `agents/agent21_*/` directories
1.2. The system MUST extract YAML frontmatter from markdown files
1.3. The system MUST extract structured data from markdown tables, lists, and code blocks
1.4. The system MUST handle multi-file policies (e.g., agent20 has multiple template files)
1.5. The system MUST validate extracted policies against JSON schemas before activation
1.6. The system MUST log parsing errors with file location and line numbers

### FR2: Policy Loader Service
2.1. The system MUST watch agents/*.md files for changes (inotify or 5-minute polling)
2.2. The system MUST hot-reload policies without restarting PHP/Python services
2.3. The system MUST maintain a policy cache in `cache/policy_backup.json` for fast startup
2.4. The system MUST version policies with MD5 hash of file contents
2.5. The system MUST fallback to cached policy if live parsing fails
2.6. The system MUST expose policy reload API endpoint: `POST /api/reload_policies.php`

### FR3: Agent08 Calmness Integration
3.1. The system MUST read calm thresholds from `agents/agent08_calmness/agent08_calmness.md`
3.2. The system MUST extract calm_score ranges: critical (<60), low (60-74), moderate (75-89), high (≥90)
3.3. The system MUST parse intervention trigger conditions from agent08 policy
3.4. The system MUST support agent08's time-based rules (e.g., "after 45 minutes of low calm")
3.5. The system MUST map agent08 terminology to MVP database schema

### FR4: Agent20 Intervention Templates
4.1. The system MUST read intervention templates from `agents/agent20_intervention_preparation/`
4.2. The system MUST extract message templates with variable placeholders: `{student_name}`, `{duration}`, `{calm_score}`
4.3. The system MUST parse intervention metadata: type, urgency, estimated_duration
4.4. The system MUST support template inheritance (base templates + overrides)
4.5. The system MUST validate template variables against available data

### FR5: Agent21 Execution Policies
5.1. The system MUST read execution rules from `agents/agent21_intervention_execution/`
5.2. The system MUST extract delivery constraints: max_per_day, cooldown_minutes, blackout_hours
5.3. The system MUST parse escalation policies for failed interventions
5.4. The system MUST honor agent21's teacher approval thresholds
5.5. The system MUST implement agent21's intervention prioritization logic

### FR6: Policy Version Management
6.1. The system MUST store policy versions in `mdl_mvp_policy_versions` table
6.2. The system MUST record: file_path, version_hash, parsed_rules (JSON), activated_at, author
6.3. The system MUST provide UI for viewing active policy versions
6.4. The system MUST provide CLI rollback: `php lib/rollback_policy.php <version_id>`
6.5. The system MUST audit log all policy changes

## Non-Goals (Out of Scope)

1. **Moodle LMS Integration** - Keep simulated message sending (defer to v1.2)
2. **Real-time Alerting** - Continue using log-based monitoring (defer to v1.2)
3. **A/B Testing** - Single active policy only (defer to v1.2)
4. **Machine Learning** - Rule-based decisions only (defer to future)
5. **Policy Editing UI** - Teachers edit markdown files directly (defer to v1.2)
6. **Multi-Language** - English policies only (defer to v1.2)
7. **Mobile Apps** - Web UI only (defer to future)
8. **Modifying 21-Agent Files** - Read-only access, do not modify agents/ folder structure

## Design Considerations

### Markdown Parser Strategy
- **Library**: Use PHP `parsedown` or `league/commonmark` (already available in Moodle)
- **YAML Frontmatter**: Extract with regex `^---\n(.*?)\n---`
- **Tables**: Parse markdown tables into associative arrays
- **Code Blocks**: Extract YAML/JSON from fenced code blocks
- **Fallback**: If parsing fails, use YAML rules in `decision/rules/`

### File Organization
```
agents/
├── agent08_calmness/
│   ├── agent08_calmness.md          # Main policy (thresholds)
│   └── calm_metrics_definition.md   # Metric calculations
├── agent20_intervention_preparation/
│   ├── intervention_templates.md    # Message templates
│   ├── intervention_types.md        # Type definitions
│   └── intervention_metadata.md     # Urgency, duration
└── agent21_intervention_execution/
    ├── execution_policies.md        # Delivery rules
    ├── escalation_rules.md          # Failure handling
    └── teacher_approval_policy.md   # HITL thresholds
```

### Policy Schema Example
```json
{
  "calm_thresholds": {
    "critical": {"max": 59, "action": "immediate_break", "confidence": 0.95},
    "low": {"min": 60, "max": 74, "action": "micro_break", "confidence": 0.85},
    "moderate": {"min": 75, "max": 89, "action": "monitor", "confidence": 0.70},
    "high": {"min": 90, "action": "none", "confidence": 1.0}
  },
  "intervention_templates": {
    "micro_break": "Hi {student_name}, you've been working for {duration}. Take a 5-min break! 🌱",
    "ask_teacher": "Hi {student_name}, stuck on {topic}? Ask your teacher for help! 👨‍🏫"
  },
  "execution_rules": {
    "max_per_day": 3,
    "cooldown_minutes": 60,
    "requires_teacher_approval": true
  }
}
```

### Hot Reload Mechanism
1. **File Watcher**: Use `inotify` (Linux) or 5-minute cron polling
2. **Change Detection**: Compare file MD5 hash with cached version
3. **Atomic Reload**: Parse → Validate → Swap in single transaction
4. **Graceful Fallback**: If validation fails, keep current policy active
5. **Notification**: Log policy reload events with timestamp and changed files

## Technical Considerations

### Dependencies
- **PHP parsedown**: Markdown parsing (available in Moodle core)
- **Python PyYAML**: YAML parsing (already installed)
- **inotify-tools** (optional): File watching
- **JSON Schema**: Policy validation

### Database Schema Changes
New table: `mdl_mvp_policy_versions`
```sql
CREATE TABLE mdl_mvp_policy_versions (
  id BIGINT(10) AUTO_INCREMENT PRIMARY KEY,
  policy_source VARCHAR(50) NOT NULL,  -- 'agent08', 'agent20', 'agent21'
  file_path VARCHAR(255) NOT NULL,
  version_hash VARCHAR(64) NOT NULL,
  parsed_rules LONGTEXT NOT NULL,       -- JSON
  is_active TINYINT(1) DEFAULT 0,
  activated_at BIGINT(10),
  deactivated_at BIGINT(10),
  author VARCHAR(100),
  created_at BIGINT(10) NOT NULL,
  INDEX idx_active (is_active, policy_source),
  INDEX idx_hash (version_hash)
);
```

### Performance Constraints
- **File Parsing**: <200ms per agent file
- **Policy Reload**: <500ms total for all 3 agents
- **Cache Lookup**: <10ms for policy retrieval
- **Overall Impact**: <50ms added latency to pipeline (keep under 500ms total)

### Security & Validation
- **Read-Only Access**: Never modify files in `agents/` directory
- **Path Traversal Prevention**: Validate file paths against whitelist
- **JSON Schema Validation**: Strict schema enforcement before activation
- **Syntax Validation**: YAML parser must handle malformed input gracefully
- **Rollback on Failure**: Auto-rollback if new policy causes system errors

## Success Metrics

1. **Policy Update Speed**: Changes in agents/*.md reflected within 5 minutes
2. **Parse Success Rate**: ≥99% successful parsing of valid markdown policies
3. **Zero Downtime**: Policy reloads without service interruption
4. **Performance Impact**: <50ms added latency to pipeline
5. **Teacher Adoption**: ≥2 teachers successfully edit policies within first month
6. **Fallback Effectiveness**: 100% of parsing failures gracefully handled

## Open Questions

1. **Conflict Resolution**: What if agent08 and agent20 define conflicting thresholds?
   → **Decision**: agent08 is source of truth for thresholds, agent20 for templates

2. **Multi-File Policies**: How to merge policies from multiple markdown files in same agent folder?
   → **Decision**: Alphabetical merge, later files override earlier ones

3. **Partial Parse Failures**: Should system activate partially-parsed policy or reject entirely?
   → **Decision**: Reject entirely, keep previous policy active

4. **Cache Staleness**: How long should cached policies be considered valid?
   → **Decision**: 7 days, force reload if cache older than 7 days

5. **Agent Folder Structure**: Should we enforce specific file naming conventions?
   → **Decision**: No, discover all .md files in agent folders dynamically

---

**Document Status**: ✅ Ready for Review
**Version**: 1.0
**Created**: 2025-11-04
**Target Release**: v1.1 (Phase 3 completion)
**Estimated Effort**: 2-3 weeks development + 1 week testing
**Priority**: HIGH (Deferred from MVP v1.0)
