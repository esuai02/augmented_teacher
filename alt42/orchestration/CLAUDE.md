# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

### Always Use These MCP Tools
- **shrimp MCP**: For task planning, analysis, and complex workflows
- **playwright MCP**: For UI testing and browser automation

Last Updated: 2025-08-31
Version: 2.0

## Environment Configuration

### Server-Based Development (NOT Local)
- **CRITICAL**: This is a **live server environment**, not local development
- Always use: `include_once("/home/moodle/public_html/moodle/config.php");`
- Required globals: `global $DB, $USER;`
- Required authentication: `require_login();`
- User role : `userrole=$DB->get_record_sql("SELECT data FROM mdl_user_info_data where userid='$USER->id' AND fieldid='22'  "); $role=$userrole->data;`
- Never test locally - all changes affect the live server

### System Specifications
- **MySQL**: 5.7
- **PHP**: 7.1.9
- **Moodle**: 3.7
- **Server URL Base**: https://mathking.kr/moodle/local/augmented_teacher/alt42/orchestration/

### UI Development Guidelines
- Use **PHP, JavaScript, CSS, HTML only** - NO React
- Implement minimal functional UI unless explicitly requested
- All error messages must include file path and line number location
