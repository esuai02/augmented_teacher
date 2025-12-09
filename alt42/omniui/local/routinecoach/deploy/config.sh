#!/bin/bash
# Deployment configuration for Routine Coach plugin
# This file contains all deployment settings and can be customized per environment

# ============================================================================
# Environment Settings
# ============================================================================

# Environment type (development, staging, production)
ENVIRONMENT="${ENVIRONMENT:-development}"

# Moodle paths
MOODLE_ROOT="/home/moodle/public_html/moodle"
MOODLE_DATA="/home/moodle/moodledata"
PLUGIN_NAME="local_routinecoach"
PLUGIN_DIR="$MOODLE_ROOT/local/routinecoach"

# Backup settings
BACKUP_DIR="/home/moodle/backups/routinecoach"
BACKUP_RETENTION_DAYS=30
MAX_BACKUPS=10

# Log settings
LOG_DIR="/var/log/moodle"
DEPLOY_LOG="$LOG_DIR/routinecoach_deploy.log"
ERROR_LOG="$LOG_DIR/routinecoach_error.log"

# ============================================================================
# Database Configuration
# ============================================================================

# Production database
if [[ "$ENVIRONMENT" == "production" ]]; then
    DB_HOST="58.180.27.46"
    DB_NAME="mathking"
    DB_USER="moodle"
    DB_PASS="@MCtrigd7128"
    DB_PREFIX="mdl_"
    
# Staging database
elif [[ "$ENVIRONMENT" == "staging" ]]; then
    DB_HOST="localhost"
    DB_NAME="mathking_staging"
    DB_USER="moodle_staging"
    DB_PASS="staging_password"
    DB_PREFIX="mdls_"
    
# Development database
else
    DB_HOST="localhost"
    DB_NAME="mathking_dev"
    DB_USER="moodle_dev"
    DB_PASS="dev_password"
    DB_PREFIX="mdld_"
fi

# ============================================================================
# Plugin Configuration
# ============================================================================

# Plugin tables (without prefix)
PLUGIN_TABLES=(
    "routinecoach_exam"
    "routinecoach_routine"
    "routinecoach_task"
    "routinecoach_log"
    "routinecoach_pref"
)

# Required capabilities
PLUGIN_CAPABILITIES=(
    "local/routinecoach:view"
    "local/routinecoach:manage"
    "local/routinecoach:viewall"
)

# Required files
REQUIRED_FILES=(
    "version.php"
    "db/install.xml"
    "db/access.php"
    "db/tasks.php"
    "lang/en/local_routinecoach.php"
    "classes/service/routine_service.php"
)

# ============================================================================
# Deployment Options
# ============================================================================

# Safety options
REQUIRE_BACKUP=true
REQUIRE_VALIDATION=true
ALLOW_DOWNGRADE=false
AUTO_ROLLBACK=true

# Performance options
PARALLEL_OPERATIONS=true
CACHE_PURGE=true
OPCACHE_RESET=true

# Notification options
SEND_NOTIFICATIONS=false
NOTIFICATION_EMAIL="admin@mathking.kr"
SLACK_WEBHOOK=""

# ============================================================================
# Health Check Thresholds
# ============================================================================

# Minimum requirements
MIN_PHP_VERSION="7.4.0"
MIN_MOODLE_VERSION="2022041900"  # Moodle 3.9+
MIN_DISK_SPACE_MB=100
MIN_MEMORY_MB=128

# Performance thresholds
MAX_LOAD_AVERAGE=4.0
MAX_DB_CONNECTIONS=50
MAX_EXECUTION_TIME=300

# ============================================================================
# Functions
# ============================================================================

# Load environment-specific overrides
load_environment_config() {
    local env_config="$( dirname "${BASH_SOURCE[0]}" )/config.${ENVIRONMENT}.sh"
    if [[ -f "$env_config" ]]; then
        source "$env_config"
        echo "Loaded environment config: $env_config"
    fi
}

# Validate configuration
validate_config() {
    local errors=0
    
    # Check paths
    if [[ ! -d "$MOODLE_ROOT" ]]; then
        echo "ERROR: MOODLE_ROOT does not exist: $MOODLE_ROOT"
        ((errors++))
    fi
    
    if [[ ! -d "$MOODLE_DATA" ]]; then
        echo "ERROR: MOODLE_DATA does not exist: $MOODLE_DATA"
        ((errors++))
    fi
    
    # Check database connection
    if ! mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
        echo "ERROR: Cannot connect to database"
        ((errors++))
    fi
    
    # Check disk space
    local available_space=$(df -m "$MOODLE_ROOT" | awk 'NR==2 {print $4}')
    if [[ $available_space -lt $MIN_DISK_SPACE_MB ]]; then
        echo "ERROR: Insufficient disk space: ${available_space}MB < ${MIN_DISK_SPACE_MB}MB"
        ((errors++))
    fi
    
    return $errors
}

# Get deployment info
get_deployment_info() {
    echo "Deployment Configuration"
    echo "========================"
    echo "Environment: $ENVIRONMENT"
    echo "Moodle Root: $MOODLE_ROOT"
    echo "Plugin: $PLUGIN_NAME"
    echo "Database: $DB_NAME@$DB_HOST"
    echo "Backup Dir: $BACKUP_DIR"
    echo ""
}

# Send notification
send_notification() {
    local status=$1
    local message=$2
    
    if [[ "$SEND_NOTIFICATIONS" != "true" ]]; then
        return
    fi
    
    # Email notification
    if [[ -n "$NOTIFICATION_EMAIL" ]]; then
        echo "$message" | mail -s "Routine Coach Deployment: $status" "$NOTIFICATION_EMAIL"
    fi
    
    # Slack notification
    if [[ -n "$SLACK_WEBHOOK" ]]; then
        curl -X POST -H 'Content-type: application/json' \
            --data "{\"text\":\"Routine Coach Deployment ($ENVIRONMENT): $status\n$message\"}" \
            "$SLACK_WEBHOOK" 2>/dev/null
    fi
}

# Export configuration
export_config() {
    export ENVIRONMENT
    export MOODLE_ROOT
    export MOODLE_DATA
    export PLUGIN_NAME
    export PLUGIN_DIR
    export BACKUP_DIR
    export DB_HOST
    export DB_NAME
    export DB_USER
    export DB_PASS
    export DB_PREFIX
}

# ============================================================================
# Auto-load environment config
# ============================================================================

load_environment_config
export_config