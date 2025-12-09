# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## System Overview

This is an AI-powered persona matching system for educational messaging. It automatically transforms teacher messages to match student learning styles using OpenAI API integration with a Moodle-based platform.
ㅊ
**Core Workflow:**
1. Teacher selects teaching philosophy mode and student learning style 
2. System saves persona pairing to database
3. Real-time chat transforms teacher messages via OpenAI API
4. Students receive messages tailored to their learning preferences

## Architecture

### Database Integration
- **Platform**: Moodle LMS integration via `/home/moodle/public_html/moodle/config.php`
- **Authentication**: Moodle user sessions with role-based access (`mdl_user_info_data` fieldid 22)
- **Custom Tables**: Three main tables for persona modes, message transformations, and chat messages

### Key Components
- **selectmode.php**: Main interface for persona selection, includes OpenAI transformation function
- **chat.php**: Real-time messaging with automatic AI transformation
- **config.php**: Centralized OpenAI API configuration
- **create_persona_modes_table.sql**: Database schema setup

### Message Transformation System
- **API Integration**: Uses OpenAI GPT-4o model with detailed prompts for Korean educational context
- **Fallback Mechanism**: Basic string replacement rules when API fails
- **Persona Modes**: 6 teaching styles × 6 learning styles = 36 possible combinations

## Development Commands

### Database Setup
```bash
# Create required tables
mysql -u username -p database_name < create_persona_modes_table.sql
```

### Configuration
```php
// config.php contains centralized settings
define('OPENAI_API_KEY', 'your-api-key');
define('OPENAI_MODEL', 'gpt-4o');
```

### Access URLs
```
# Main persona selection interface
selectmode.php?userid=[student_id]

# Real-time chat interface  
chat.php?student_id=[student_id]
```

## Critical Implementation Details

### Security Considerations
- OpenAI API keys stored in config.php (should be moved to environment variables in production)
- All database queries use prepared statements via Moodle's `$DB->execute()` and `$DB->get_record_sql()`
- User authentication required via Moodle's `require_login()`

### API Integration Patterns
- **Primary**: OpenAI API calls with detailed Korean educational prompts
- **Secondary**: Fallback to `applyBasicTransformation()` with pattern matching
- **Error Handling**: Logs API failures and gracefully degrades functionality

### Frontend Architecture
- **Dual Interface**: Student view vs Teacher configuration view toggled via JavaScript
- **Real-time Updates**: AJAX-based message sending with immediate UI feedback
- **Message Display**: Paired view showing original teacher message and transformed student message

### Data Flow
1. Persona selection → `mdl_persona_modes` table (teacher_id, student_id, modes)
2. Chat message → OpenAI transformation → dual storage (original + transformed)
3. Message retrieval → chronological display with role indicators

## Multi-System Integration

### Persona Matching System
- **Teaching Philosophies**: curriculum, exam, custom, mission, reflection, selfled
- **Learning Styles**: Same 6 modes mapped to student preferences
- **Transformation Matrix**: 36 possible combinations with unique tone patterns
- **Prompt Engineering**: Korean educational context with cultural sensitivity

### WXsperta Agent Categories
- **Future Design**: Long-term planning and goal-setting agents
- **Execution**: Daily productivity and action-oriented agents  
- **Branding**: Identity development and competitive positioning
- **Knowledge Management**: Information processing and learning optimization

### Development Patterns
- **Role-Based Views**: Student/teacher interfaces with different capabilities
- **Moodle Integration**: Seamless authentication and user data access
- **API-First Design**: RESTful endpoints for all major functionality
- **Graceful Degradation**: Fallback mechanisms for API failures
- **Comprehensive Testing**: Multiple test files for different system components

### Database Schema Patterns
- **Prefixed Tables**: `mdl_` prefix for Moodle integration
- **Timestamp Fields**: Unix timestamps for all temporal data
- **JSON Storage**: Flexible data structures in text fields for older MySQL
- **Indexing Strategy**: Optimized for user-based and time-based queries