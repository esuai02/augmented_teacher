# PRD: MVP System v1.1 - Production Integration & Policy Loader

## Introduction/Overview

The MVP System v1.0 successfully demonstrated a complete AI-driven intervention workflow with exceptional performance (385ms vs 180s SLA target). Version 1.1 transforms the system from a simulated demonstration to a fully production-integrated platform by:

1. **Implementing real Moodle LMS integration** - Actual message delivery to students
2. **Dynamic policy loading from agents/*.md files** - Live rule updates without code changes
3. **Production alerting system** - Email/Slack notifications for SLA violations
4. **Additional intervention types** - Expanding beyond Calm Break

**Problem Solved**: v1.0 successfully validated the architecture but operates in simulation mode. v1.1 makes it production-ready with real student impact.

**Goal**: Transform MVP from demonstration to production-integrated system capable of autonomously managing student interventions with teacher oversight.

## Goals

1. **Real Student Impact**: Interventions delivered through Moodle messaging API to actual students
2. **Dynamic Rule Management**: Teachers can modify intervention rules via agents/*.md files without developer involvement
3. **Operational Excellence**: Automated alerting ensures system health monitoring
4. **Intervention Variety**: Support multiple intervention types beyond Calm Break
5. **Maintain Performance**: Keep SLA compliance ≥90% while adding production integrations

## User Stories

### Real LMS Integration
1. **As a student**, I want to receive intervention messages in my Moodle inbox so that I can act on AI recommendations in real-time.
2. **As a teacher**, I want interventions to appear in Moodle's messaging system so I can track student engagement.
3. **As a system administrator**, I want failed message deliveries to be logged and retried automatically.

### Policy Loader
4. **As a teacher**, I want to update intervention rules by editing agents/*.md files so I don't need developer support.
5. **As a curriculum director**, I want to version control intervention policies so we can track changes over time.
6. **As the AI system**, I want to hot-reload policy changes without restarting services.

### Production Alerting
7. **As a technical lead**, I want to receive Slack alerts when SLA drops below 90% so I can respond before it impacts students.
8. **As an operations team member**, I want email summaries of daily performance so I can identify trends.
9. **As a system administrator**, I want critical errors to trigger PagerDuty alerts for immediate response.

### Additional Interventions
10. **As a teacher**, I want the system to suggest "ask_teacher" interventions when students are confused, not just tired.
11. **As a student**, I want "review_concept" suggestions when I make repeated mistakes on the same topic.

## Functional Requirements

### FR1: Moodle LMS Integration
1.1. The system MUST send intervention messages via Moodle's `message_send()` API
1.2. The system MUST log all message delivery attempts with status (sent/failed/pending)
1.3. The system MUST retry failed deliveries up to 3 times with exponential backoff
1.4. The system MUST support message templates with student-specific variable substitution
1.5. The system MUST validate recipient user IDs exist in Moodle before sending
1.6. The system MUST track message read receipts when available

### FR2: Dynamic Policy Loader
2.1. The system MUST parse agents/*.md files to extract intervention rules
2.2. The system MUST validate parsed rules against JSON schemas before activation
2.3. The system MUST support hot-reloading of policy changes without service restart
2.4. The system MUST maintain backward compatibility with existing YAML rules
2.5. The system MUST log policy version changes with timestamps and author info
2.6. The system MUST fallback to last-known-good policy if parsing fails

### FR3: Production Alerting System
3.1. The system MUST send Slack alerts when SLA compliance drops below 90%
3.2. The system MUST send email digests of daily performance metrics
3.3. The system MUST trigger PagerDuty for critical errors (database down, API failures)
3.4. The system MUST include actionable context in all alerts (error logs, affected students)
3.5. The system MUST rate-limit alerts to prevent notification storms (max 5/hour per channel)
3.6. The system MUST allow alert configuration via environment variables

### FR4: Additional Intervention Types
4.1. The system MUST implement "ask_teacher" intervention for confusion detection
4.2. The system MUST implement "review_concept" intervention for repeated errors
4.3. The system MUST support multiple active intervention types per student
4.4. The system MUST prioritize interventions based on urgency scoring
4.5. The system MUST prevent intervention fatigue (max 3 interventions/day per student)

### FR5: System Administration
5.1. The system MUST provide a CLI tool for testing policy parsing (`php lib/test_policy_parser.php <file>`)
5.2. The system MUST provide a UI for viewing active policy versions
5.3. The system MUST provide a rollback mechanism for policy changes
5.4. The system MUST log all administrative actions for audit trail

## Non-Goals (Out of Scope)

1. **A/B Testing Framework** - Deferred to v1.2
2. **Teacher Analytics Dashboard** - Deferred to v1.2
3. **Student Dashboard** - Deferred to v1.2
4. **Multi-Language Support** - Use English messaging only in v1.1
5. **Machine Learning** - Continue using rule-based decision making
6. **Mobile Apps** - Web interface only
7. **Parent Portal** - Teacher and student access only
8. **Intervention Scheduling** - Real-time interventions only, no scheduling

## Design Considerations

### Moodle API Integration
- Use Moodle's existing `message_send()` function: `https://docs.moodle.org/dev/Messaging_2.0#Sending_a_message`
- Implement in PHP to leverage existing Moodle database connection
- Store Moodle user IDs in `mdl_mvp_intervention_execution` table
- Example template: "Hi {student_name}, you've been working hard for {duration} minutes. Consider taking a 5-minute break to refresh your mind. 🌱"

### Policy Parser Architecture
- Markdown parser: Use `php-markdown` or native `parsedown` library
- Extract YAML frontmatter from agents/*.md files
- Validation: JSON Schema validation against `schemas/policy.schema.json`
- Hot reload: File watcher with inotify or 5-minute cron polling
- Fallback: Cache last-known-good policy in `cache/policy_backup.json`

### Alerting Channels
- **Slack**: Use incoming webhooks (https://api.slack.com/messaging/webhooks)
- **Email**: SMTP via Moodle's email functions
- **PagerDuty**: REST API integration (https://developer.pagerduty.com/api-reference/)
- **Configuration**: Store webhook URLs and API keys in `config/alerts.config.php`

### UI Components
- Policy Viewer: Display current active rules with version history
- Message Log: Filterable table of sent/failed/pending messages
- Alert Configuration: Form for setting thresholds and notification channels

## Technical Considerations

### Dependencies
- **Moodle 3.7+**: For messaging API compatibility
- **PHP parsedown**: Markdown parsing library
- **Python PyYAML**: Already installed for YAML parsing
- **inotify-tools**: For file watching (optional, cron fallback available)

### Database Schema Changes
Add columns to `mdl_mvp_intervention_execution`:
- `moodle_message_id` (INT): Foreign key to mdl_messages
- `moodle_user_id` (INT): Recipient user ID
- `message_content` (TEXT): Rendered message text
- `delivery_status` (ENUM): 'pending', 'sent', 'failed', 'retrying'
- `delivery_attempts` (INT): Retry counter
- `last_attempt_at` (TIMESTAMP): Last delivery attempt time

Add new table `mdl_mvp_policy_versions`:
- `id` (INT): Primary key
- `policy_file` (VARCHAR 255): agents/*.md file path
- `version_hash` (VARCHAR 64): MD5 hash of file content
- `parsed_rules` (JSON): Extracted rules
- `activated_at` (TIMESTAMP): When policy became active
- `deactivated_at` (TIMESTAMP): When policy was replaced
- `author` (VARCHAR 100): Who made the change

### Performance Constraints
- Policy parsing: <100ms per file
- Moodle message delivery: <500ms per message
- Alert dispatch: <2 seconds per alert
- Overall pipeline SLA: Maintain <500ms average (currently 385ms)

### Security
- Validate all user inputs in policy files
- Sanitize message templates to prevent XSS
- Rate limit message sending (max 100/minute)
- Encrypt alert webhook URLs and API keys
- Audit log all policy changes

## Success Metrics

1. **Message Delivery Rate**: ≥95% of interventions successfully delivered to students
2. **Policy Update Speed**: Teachers can update rules and see effects within 5 minutes
3. **Alert Effectiveness**: ≥90% of SLA violations detected and alerted within 2 minutes
4. **System Uptime**: ≥99% uptime for messaging integration
5. **Performance Maintenance**: SLA compliance stays ≥90% after production integration
6. **Teacher Adoption**: ≥3 teachers actively using policy editing within first month

## Open Questions

1. **Message Delivery Timing**: Should interventions be sent immediately or queued for next login? → **Decision**: Immediate send with notification badge.
2. **Policy Conflict Resolution**: What happens if agents/agent08.md and agents/agent20.md define conflicting rules? → **Decision**: Last-modified file wins, log warning.
3. **Alert Fatigue**: How to balance comprehensive alerting with notification overload? → **Decision**: Use configurable thresholds and daily digests.
4. **Retry Strategy**: Should failed messages be retried indefinitely or have a max age? → **Decision**: Max 3 retries, then mark as failed permanently.
5. **Policy Version Migration**: How to handle students mid-intervention when policy changes? → **Decision**: Complete in-flight interventions under old policy, new interventions use new policy.

---

**Document Status**: ✅ Ready for Review
**Version**: 1.0
**Created**: 2025-11-04
**Target Release**: v1.1 (4-6 weeks after v1.0 pilot completion)
**Estimated Effort**: 3-4 weeks development + 1-2 weeks testing
