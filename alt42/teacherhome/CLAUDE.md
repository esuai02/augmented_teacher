# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**KTM 코파일럿 (KTM Copilot)** is an educational AI system designed for teachers in the Moodle ecosystem. This is a PHP/JavaScript web application that provides modular teaching assistance through a plugin-based architecture.

### Core Architecture

The system follows a **modular plugin architecture** with 3 main layers:

1. **Frontend Layer**: HTML/CSS/JavaScript with category-based UI
2. **Module System**: 9 educational categories with individual JS modules  
3. **Backend Layer**: PHP APIs with MySQL database integration

### Key Components

- **Main Interface**: `index.php` - Primary Moodle-integrated interface
- **Core Logic**: `script.js` - Central JavaScript controller
- **Module Loader**: `module_loader.js` - Dynamic module data loading
- **Plugin System**: `plugin_settings_client.js` + `plugin_settings_api_real.php`

## Database Architecture

The system uses Moodle's MySQL database with custom tables prefixed `mdl_alt42DB_`:

**Core Tables**:
- `plugin_types` - Plugin type definitions
- `user_plugin_settings` - User-specific configurations  
- `card_plugin_settings` - Card-level plugin settings
- `plugin_settings_history` - Change tracking
- `plugin_usage_stats` - Usage analytics

**Database Setup**:
```bash
# Initialize database tables
php execute_sql_file.php complete_db_schema.sql

# Or via web interface
http://localhost/alt42/teacherhome/execute_sql_file.php
```

## Module System

The application is organized into 9 educational categories:

1. **Quarterly** (`quarterly/`) - 분기활동
2. **Weekly** (`weekly/`) - 주간활동  
3. **Daily** (`daily/`) - 오늘활동
4. **Realtime** (`realtime/`) - 실시간 관리
5. **Interaction** (`interaction/`) - 상호작용 관리
6. **Bias** (`bias/`) - 인지관성 개선 (60 cognitive bias patterns)
7. **Development** (`development/`) - 컨텐츠 및 앱개발
8. **Consultation** (`consultation/`) - 상담관리
9. **Viral Marketing** - 바이럴 마케팅

Each module follows this structure:
```
module_name/
├── module_name.js     # Module logic and data
├── README.md          # Module documentation
└── (additional files)
```

## Plugin System Architecture

**Client-Side**: `KTMPluginSettingsClient` class handles:
- Plugin type management
- User/card setting persistence  
- API communication
- UI generation

**Server-Side**: `PluginSettingsAPINew` class provides:
- RESTful API endpoints
- Database operations
- Transaction management
- Error handling

**Plugin Types**:
- `agent` - Pop-up multi-turn task execution
- `internal_link` - Platform internal navigation
- `external_link` - External site/tool connections  
- `send_message` - Automated user messaging

## Development Commands

**Database Management**:
```bash
# Setup database
php execute_sql_file.php complete_db_schema.sql

# Check table structure
php db_structure_info.php

# Clear plugin data
php clear_plugin_data.php

# Validate migration
php validate_migration.php
```

**Testing**:
```bash
# Test plugin functionality  
open test_plugin_display.php

# Test module integration
open test_module_integration.html

# Test new database structure
open test_new_db_integration.html
```

**Development Server**:
The application requires a Moodle environment with PHP/MySQL. Access via:
```
http://localhost/moodle/local/augmented_teacher/alt42/teacherhome/
```

## Key Integration Points

**Moodle Integration**:
- Requires Moodle session: `require_login()`
- Uses Moodle database: `$DB`, `$USER` globals
- Integrates with user roles via `mdl_user_info_data`

**JavaScript Module Loading**:
- Modules are loaded dynamically via `ModuleLoader` class
- Each category has its own JavaScript module
- Central coordination through `script.js`

**Plugin Settings Persistence**:
- 3-level settings hierarchy: Global → User → Card
- Real-time save/load via AJAX API calls
- Change history tracking for all modifications

## File Organization

**Core Files**:
- `index.php` - Main application entry point
- `script.js` - Central JavaScript controller  
- `styles.css` - Main stylesheet
- `module_loader.js` - Module data loading system

**API Layer**:
- `plugin_settings_api_real.php` - Current production API
- `plugin_db_config.php` - Database configuration
- `module_data_api.php` - Module data API

**Database Schema**:
- `complete_db_schema.sql` - Full database structure
- `create_alt42_plugin_tables.sql` - Plugin system tables
- Various migration scripts for schema updates

**Documentation**:
- `SETUP_GUIDE.md` - Complete setup instructions
- `PLUGIN_API_ARCHITECTURE.md` - API architecture details
- Multiple troubleshooting and migration guides

## Development Notes

**Error Handling**: The system uses comprehensive error logging and user feedback. Check browser console and PHP error logs for debugging.

**Performance**: Module data is cached client-side for 5 minutes. Plugin settings use immediate persistence with optimistic updates.

**Security**: All database operations use prepared statements. User authentication is handled via Moodle's session system.

**Testing**: Multiple HTML test files are available for component testing. Use these to verify functionality after changes.