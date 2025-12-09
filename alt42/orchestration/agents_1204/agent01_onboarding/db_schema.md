# Onboarding Report Database Schema

## Table: alt42o_onboarding_reports

This table stores generated onboarding reports combining data from `mdl_alt42_student_profiles` and `alt42o_learning_assessment_results`.

| Field | Type | Description |
|-------|------|-------------|
| id | INT(11) PRIMARY KEY AUTO_INCREMENT | Unique report ID |
| userid | INT(11) NOT NULL | Moodle user ID (foreign key to mdl_user) |
| report_type | VARCHAR(50) | Type: 'initial', 'regenerated' |
| info_data | LONGTEXT | JSON: basic info from student profiles |
| assessment_id | INT(11) | Foreign key to alt42o_learning_assessment_results |
| report_content | LONGTEXT | Generated report HTML/JSON |
| generated_at | INT(11) | Unix timestamp of generation |
| generated_by | VARCHAR(100) | Generator identifier (e.g., 'agent01_onboarding') |
| status | VARCHAR(20) | 'draft', 'published', 'archived' |
| metadata | LONGTEXT | JSON: additional metadata |

**Indexes:**
- idx_userid (userid)
- idx_generated_at (generated_at DESC)
- idx_status (status)

---

## Related Tables

### mdl_alt42_student_profiles

Student profile data collected during onboarding:

| Field | Type | Description |
|-------|------|-------------|
| id | INT(10) PRIMARY KEY AUTO_INCREMENT | Profile ID |
| user_id | INT(10) NOT NULL | User ID (indexed) |
| learning_style | VARCHAR(50) | Learning style preference |
| interests | JSON | Student interests |
| goals | JSON | Learning goals |
| mbti_type | VARCHAR(4) | MBTI personality type |
| preferred_motivator | VARCHAR(50) | Motivation type (default: autonomy) |
| daily_active_time | VARCHAR(20) | Preferred active time |
| streak_days | INT(10) | Consecutive active days (default: 0) |
| total_interactions | INT(10) | Total interactions count (default: 0) |
| last_active | DATE | Last activity date |
| created_at | TIMESTAMP | Creation timestamp (auto) |
| updated_at | TIMESTAMP | Update timestamp (auto on update) |

### alt42o_learning_assessment_results

Learning assessment scores and Q&A data:

| Field | Type | Description |
|-------|------|-------------|
| id | INT(10) PRIMARY KEY | Assessment ID |
| userid | INT(10) | User ID |
| cognitive_score | DECIMAL/FLOAT | Cognitive score (0-5) |
| emotional_score | DECIMAL/FLOAT | Emotional score (0-5) |
| behavioral_score | DECIMAL/FLOAT | Behavioral score (0-5) |
| overall_total | DECIMAL/FLOAT | Overall total score (0-5) |
| qa01-qa16 | TEXT | Question-Answer pairs for 16 questions |
| created_at | TIMESTAMP | Assessment creation time |
| session_id | VARCHAR | Session identifier |

---

## Data Flow

1. **Student completes onboarding_info.php** → Data saved to `mdl_alt42_student_profiles`
2. **Student completes onboarding_learningtype.php** → Scores saved to `alt42o_learning_assessment_results`
3. **Agent card clicked** → Panel checks `alt42o_onboarding_reports` for existing report
4. **Report generation triggered** → Combines data from both tables into HTML report
5. **Report saved** → Stored in `alt42o_onboarding_reports` with metadata

---

## Schema Version

- **Version**: 1.0
- **Created**: 2025-01-21
- **Last Updated**: 2025-01-21
- **Compatible with**: Moodle 3.7, PHP 7.1.9, MySQL 5.7
