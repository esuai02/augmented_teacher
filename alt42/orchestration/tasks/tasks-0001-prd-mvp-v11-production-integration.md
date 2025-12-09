# Task List: MVP System v1.1 - Production Integration & Policy Loader

**Based on PRD**: `0001-prd-mvp-v11-production-integration.md`

**Project Context**: Transform MVP v1.0 from simulated demonstration to production-integrated system with real student impact.

**Current State Assessment**:
- ✅ **Existing Infrastructure**: 3-layer architecture (Sensing → Decision → Execution), 5 database tables, comprehensive testing framework
- ✅ **Moodle Integration**: Database connection and authentication already working (`config.php` + `require_login()`)
- ✅ **Python Environment**: Python 3.10.12 installed with PyYAML for YAML parsing
- ⚠️ **Policy Parser**: Stub exists at `lib/policy_parser.php` but not implemented
- ⚠️ **Execution Layer**: Currently simulates message sending, needs real Moodle messaging API integration

## Relevant Files

### Core Files to Modify
- `execution/intervention_dispatcher.php` - Add Moodle message_send() integration
- `lib/policy_parser.php` - Implement agents/*.md parser
- `database/migrate_v1_to_v11.php` - Database schema updates (CREATE)
- `config/alerts.config.php` - Alert configuration (CREATE)

### New Files to Create
- `lib/moodle_messenger.php` - Moodle messaging wrapper
- `lib/policy_loader.php` - Policy file watcher and loader
- `lib/alert_dispatcher.php` - Multi-channel alerting
- `decision/rules/ask_teacher_rules.yaml` - Ask Teacher intervention rules
- `decision/rules/review_concept_rules.yaml` - Review Concept intervention rules
- `ui/policy_viewer.php` - Policy version viewer UI
- `ui/message_log.php` - Message delivery log UI
- `ui/alert_config.php` - Alert configuration UI

### Test Files
- `tests/moodle_messenger.test.php` - Messaging integration tests (CREATE)
- `tests/policy_parser.test.php` - Policy parsing tests (CREATE)
- `tests/policy_loader.test.php` - Policy loading tests (CREATE)
- `tests/alert_dispatcher.test.php` - Alerting tests (CREATE)
- `tests/e2e/ask_teacher_scenario.test.php` - E2E test for Ask Teacher (CREATE)
- `tests/e2e/review_concept_scenario.test.php` - E2E test for Review Concept (CREATE)

### Documentation
- `docs/MOODLE_INTEGRATION_GUIDE.md` - Moodle messaging integration docs (CREATE)
- `docs/POLICY_EDITING_GUIDE.md` - Teacher guide for editing policies (CREATE)
- `docs/ALERTING_GUIDE.md` - Alert configuration and troubleshooting (CREATE)

### Notes
- Unit tests should be placed alongside the code files (e.g., `lib/moodle_messenger.php` and `tests/moodle_messenger.test.php`)
- Use `npx jest [path]` to run JavaScript tests or `php [test_file]` for PHP tests
- All database changes require migration scripts and rollback procedures
- Follow existing error logging pattern: `throw new Exception("Message at " . __FILE__ . ":" . __LINE__);`

---

## Tasks

- [ ] 1.0 Database Schema Migration for v1.1
- [ ] 2.0 Moodle LMS Integration
- [ ] 3.0 Dynamic Policy Loader Implementation
- [ ] 4.0 Production Alerting System
- [ ] 5.0 Additional Intervention Types
- [ ] 6.0 Admin UI Components
- [ ] 7.0 Testing & Validation
- [ ] 8.0 Documentation & Deployment

---

I have generated the high-level tasks based on the PRD. **Ready to generate the sub-tasks?** Respond with "**Go**" to proceed.
