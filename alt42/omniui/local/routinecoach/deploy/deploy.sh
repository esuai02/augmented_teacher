#!/bin/bash
# Deployment script for Routine Coach plugin
# Copyright 2024 MathKing
# 
# Features:
# - Database backup before deployment
# - Install.xml validation
# - Upgrade.php execution
# - Cache purging
# - Rollback on failure
# - Version management

set -e  # Exit on error

# ============================================================================
# Configuration
# ============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Paths
MOODLE_ROOT="/home/moodle/public_html/moodle"
PLUGIN_DIR="$MOODLE_ROOT/local/routinecoach"
BACKUP_DIR="/home/moodle/backups/routinecoach"
DEPLOY_LOG="/var/log/moodle/routinecoach_deploy.log"
TEMP_DIR="/tmp/routinecoach_deploy_$$"

# Database configuration
DB_HOST="58.180.27.46"
DB_NAME="mathking"
DB_USER="moodle"
DB_PASS="@MCtrigd7128"
DB_PREFIX="mdl_"

# Plugin tables
PLUGIN_TABLES=(
    "routinecoach_exam"
    "routinecoach_routine"
    "routinecoach_task"
    "routinecoach_log"
    "routinecoach_pref"
)

# Deployment metadata
DEPLOY_TIMESTAMP=$(date +%Y%m%d_%H%M%S)
CURRENT_VERSION=""
NEW_VERSION=""
ROLLBACK_VERSION=""

# ============================================================================
# Helper Functions
# ============================================================================

# Logging function
log() {
    local level=$1
    shift
    local message="$@"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    
    echo "[$timestamp] [$level] $message" >> "$DEPLOY_LOG"
    
    case $level in
        ERROR)
            echo -e "${RED}[ERROR]${NC} $message" >&2
            ;;
        SUCCESS)
            echo -e "${GREEN}[SUCCESS]${NC} $message"
            ;;
        WARNING)
            echo -e "${YELLOW}[WARNING]${NC} $message"
            ;;
        INFO)
            echo -e "${BLUE}[INFO]${NC} $message"
            ;;
    esac
}

# Check prerequisites
check_prerequisites() {
    log INFO "Checking prerequisites..."
    
    # Check if running as appropriate user
    if [[ $EUID -eq 0 ]]; then
        log ERROR "This script should not be run as root"
        exit 1
    fi
    
    # Check required commands
    local required_commands=("php" "mysql" "mysqldump" "git" "xmllint")
    for cmd in "${required_commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            log ERROR "Required command '$cmd' not found"
            exit 1
        fi
    done
    
    # Check Moodle installation
    if [[ ! -f "$MOODLE_ROOT/config.php" ]]; then
        log ERROR "Moodle installation not found at $MOODLE_ROOT"
        exit 1
    fi
    
    # Create backup directory if not exists
    mkdir -p "$BACKUP_DIR"
    mkdir -p "$(dirname "$DEPLOY_LOG")"
    
    log SUCCESS "Prerequisites check passed"
}

# Get current plugin version
get_current_version() {
    if [[ -f "$PLUGIN_DIR/version.php" ]]; then
        CURRENT_VERSION=$(grep "^\$plugin->version" "$PLUGIN_DIR/version.php" | grep -oP '\d+' || echo "0")
        log INFO "Current version: $CURRENT_VERSION"
    else
        CURRENT_VERSION="0"
        log INFO "No current version found (new installation)"
    fi
}

# ============================================================================
# Validation Functions
# ============================================================================

# Validate install.xml
validate_install_xml() {
    log INFO "Validating install.xml..."
    
    local install_xml="$PLUGIN_DIR/db/install.xml"
    
    if [[ ! -f "$install_xml" ]]; then
        log ERROR "install.xml not found"
        return 1
    fi
    
    # XML syntax validation
    if ! xmllint --noout "$install_xml" 2>/dev/null; then
        log ERROR "install.xml has XML syntax errors"
        xmllint "$install_xml" 2>&1 | while read line; do
            log ERROR "  $line"
        done
        return 1
    fi
    
    # Validate table definitions
    local table_count=$(xmllint --xpath "count(//TABLE)" "$install_xml" 2>/dev/null)
    if [[ $table_count -ne ${#PLUGIN_TABLES[@]} ]]; then
        log WARNING "Expected ${#PLUGIN_TABLES[@]} tables, found $table_count in install.xml"
    fi
    
    # Check for required fields in each table
    for table in "${PLUGIN_TABLES[@]}"; do
        local table_exists=$(xmllint --xpath "//TABLE[@NAME='$table']" "$install_xml" 2>/dev/null | wc -l)
        if [[ $table_exists -eq 0 ]]; then
            log ERROR "Table $table not found in install.xml"
            return 1
        fi
        
        # Check for id field
        local has_id=$(xmllint --xpath "//TABLE[@NAME='$table']//FIELD[@NAME='id']" "$install_xml" 2>/dev/null | wc -l)
        if [[ $has_id -eq 0 ]]; then
            log ERROR "Table $table missing required 'id' field"
            return 1
        fi
    done
    
    log SUCCESS "install.xml validation passed"
    return 0
}

# Validate upgrade.php
validate_upgrade_php() {
    log INFO "Validating upgrade.php..."
    
    local upgrade_php="$PLUGIN_DIR/db/upgrade.php"
    
    if [[ ! -f "$upgrade_php" ]]; then
        log WARNING "upgrade.php not found (OK for new installation)"
        return 0
    fi
    
    # PHP syntax check
    if ! php -l "$upgrade_php" > /dev/null 2>&1; then
        log ERROR "upgrade.php has PHP syntax errors"
        php -l "$upgrade_php" 2>&1 | while read line; do
            log ERROR "  $line"
        done
        return 1
    fi
    
    # Check for required function
    if ! grep -q "function xmldb_local_routinecoach_upgrade" "$upgrade_php"; then
        log ERROR "Required function xmldb_local_routinecoach_upgrade not found"
        return 1
    fi
    
    log SUCCESS "upgrade.php validation passed"
    return 0
}

# ============================================================================
# Backup Functions
# ============================================================================

# Create full database backup
backup_database() {
    log INFO "Creating database backup..."
    
    local backup_file="$BACKUP_DIR/db_backup_${DEPLOY_TIMESTAMP}.sql"
    
    # Backup only plugin tables
    local tables_to_backup=""
    for table in "${PLUGIN_TABLES[@]}"; do
        tables_to_backup="$tables_to_backup ${DB_PREFIX}${table}"
    done
    
    # Create backup with table structure and data
    mysqldump \
        --host="$DB_HOST" \
        --user="$DB_USER" \
        --password="$DB_PASS" \
        --single-transaction \
        --routines \
        --triggers \
        --add-drop-table \
        --create-options \
        --complete-insert \
        --quote-names \
        "$DB_NAME" \
        $tables_to_backup \
        > "$backup_file" 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        # Compress backup
        gzip "$backup_file"
        backup_file="${backup_file}.gz"
        
        # Verify backup
        if [[ -f "$backup_file" ]] && [[ $(stat -c%s "$backup_file") -gt 0 ]]; then
            log SUCCESS "Database backup created: $backup_file"
            echo "$backup_file" > "$TEMP_DIR/last_backup"
            return 0
        fi
    fi
    
    log ERROR "Database backup failed"
    return 1
}

# Backup plugin files
backup_plugin_files() {
    log INFO "Backing up plugin files..."
    
    local backup_file="$BACKUP_DIR/files_backup_${DEPLOY_TIMESTAMP}.tar.gz"
    
    if [[ -d "$PLUGIN_DIR" ]]; then
        tar -czf "$backup_file" -C "$(dirname "$PLUGIN_DIR")" "$(basename "$PLUGIN_DIR")" 2>/dev/null
        
        if [[ $? -eq 0 ]] && [[ -f "$backup_file" ]]; then
            log SUCCESS "Plugin files backed up: $backup_file"
            echo "$backup_file" > "$TEMP_DIR/last_file_backup"
            return 0
        fi
    else
        log INFO "No existing plugin files to backup"
        return 0
    fi
    
    log ERROR "Plugin files backup failed"
    return 1
}

# Create version snapshot
create_version_snapshot() {
    log INFO "Creating version snapshot..."
    
    local snapshot_file="$BACKUP_DIR/version_snapshot_${DEPLOY_TIMESTAMP}.json"
    
    cat > "$snapshot_file" <<EOF
{
    "timestamp": "$DEPLOY_TIMESTAMP",
    "current_version": "$CURRENT_VERSION",
    "moodle_version": "$(php -r "require '$MOODLE_ROOT/version.php'; echo \$version;")",
    "php_version": "$(php -v | head -1)",
    "tables": [
EOF
    
    # Add table checksums
    local first=true
    for table in "${PLUGIN_TABLES[@]}"; do
        if [[ "$first" != true ]]; then
            echo "," >> "$snapshot_file"
        fi
        first=false
        
        local count=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            -e "SELECT COUNT(*) FROM ${DB_PREFIX}${table}" 2>/dev/null | tail -1 || echo "0")
        
        cat >> "$snapshot_file" <<EOF
        {
            "name": "$table",
            "record_count": $count
        }
EOF
    done
    
    cat >> "$snapshot_file" <<EOF
    ]
}
EOF
    
    log SUCCESS "Version snapshot created: $snapshot_file"
    return 0
}

# ============================================================================
# Deployment Functions
# ============================================================================

# Deploy new version
deploy_plugin() {
    log INFO "Starting plugin deployment..."
    
    # Copy new files (assuming they're staged in a temp location)
    if [[ -d "$TEMP_DIR/new_version" ]]; then
        log INFO "Copying new plugin files..."
        
        # Remove old files (keeping backups)
        if [[ -d "$PLUGIN_DIR" ]]; then
            rm -rf "$PLUGIN_DIR"
        fi
        
        # Copy new files
        cp -r "$TEMP_DIR/new_version" "$PLUGIN_DIR"
        
        # Set permissions
        chown -R www-data:www-data "$PLUGIN_DIR"
        find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;
        find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;
        
        log SUCCESS "Plugin files deployed"
    else
        log INFO "Using existing plugin files"
    fi
    
    return 0
}

# Run database upgrade
run_database_upgrade() {
    log INFO "Running database upgrade..."
    
    cd "$MOODLE_ROOT"
    
    # Run Moodle upgrade script
    php admin/cli/upgrade.php --non-interactive 2>&1 | tee -a "$DEPLOY_LOG"
    
    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        log SUCCESS "Database upgrade completed"
        return 0
    else
        log ERROR "Database upgrade failed"
        return 1
    fi
}

# Purge caches
purge_caches() {
    log INFO "Purging Moodle caches..."
    
    cd "$MOODLE_ROOT"
    
    # Purge all caches
    php admin/cli/purge_caches.php 2>&1 | tee -a "$DEPLOY_LOG"
    
    if [[ ${PIPESTATUS[0]} -eq 0 ]]; then
        log SUCCESS "Caches purged"
        
        # Also clear PHP opcache if available
        if php -m | grep -q "opcache"; then
            php -r "opcache_reset();" 2>/dev/null || true
            log INFO "PHP OpCache cleared"
        fi
        
        # Clear theme caches
        rm -rf "$MOODLE_ROOT/localcache/*" 2>/dev/null || true
        rm -rf "$MOODLE_ROOT/moodledata/cache/*" 2>/dev/null || true
        rm -rf "$MOODLE_ROOT/moodledata/localcache/*" 2>/dev/null || true
        
        return 0
    else
        log ERROR "Cache purge failed"
        return 1
    fi
}

# Verify deployment
verify_deployment() {
    log INFO "Verifying deployment..."
    
    local errors=0
    
    # Check plugin is recognized
    cd "$MOODLE_ROOT"
    local plugin_check=$(php admin/cli/uninstall_plugins.php --show-all 2>/dev/null | grep "local_routinecoach" | wc -l)
    
    if [[ $plugin_check -eq 0 ]]; then
        log ERROR "Plugin not recognized by Moodle"
        ((errors++))
    else
        log SUCCESS "Plugin recognized by Moodle"
    fi
    
    # Check tables exist
    for table in "${PLUGIN_TABLES[@]}"; do
        local table_exists=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            -e "SHOW TABLES LIKE '${DB_PREFIX}${table}'" 2>/dev/null | wc -l)
        
        if [[ $table_exists -eq 0 ]]; then
            log ERROR "Table ${DB_PREFIX}${table} not created"
            ((errors++))
        else
            log SUCCESS "Table ${DB_PREFIX}${table} exists"
        fi
    done
    
    # Check version was updated
    get_current_version
    if [[ "$CURRENT_VERSION" == "$NEW_VERSION" ]] || [[ "$NEW_VERSION" == "" ]]; then
        log WARNING "Version may not have been updated correctly"
    fi
    
    # Run plugin-specific health check
    if [[ -f "$PLUGIN_DIR/cli/health_check.php" ]]; then
        php "$PLUGIN_DIR/cli/health_check.php" 2>&1 | tee -a "$DEPLOY_LOG"
        if [[ ${PIPESTATUS[0]} -ne 0 ]]; then
            log ERROR "Plugin health check failed"
            ((errors++))
        fi
    fi
    
    if [[ $errors -eq 0 ]]; then
        log SUCCESS "Deployment verification passed"
        return 0
    else
        log ERROR "Deployment verification failed with $errors errors"
        return 1
    fi
}

# ============================================================================
# Rollback Functions
# ============================================================================

# Rollback database
rollback_database() {
    log WARNING "Rolling back database..."
    
    local backup_file=$(cat "$TEMP_DIR/last_backup" 2>/dev/null)
    
    if [[ -z "$backup_file" ]] || [[ ! -f "$backup_file" ]]; then
        log ERROR "No backup file found for rollback"
        return 1
    fi
    
    # Drop existing tables
    for table in "${PLUGIN_TABLES[@]}"; do
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            -e "DROP TABLE IF EXISTS ${DB_PREFIX}${table}" 2>/dev/null
        log INFO "Dropped table ${DB_PREFIX}${table}"
    done
    
    # Restore from backup
    log INFO "Restoring from backup: $backup_file"
    
    if [[ "$backup_file" == *.gz ]]; then
        gunzip -c "$backup_file" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$backup_file" 2>/dev/null
    fi
    
    if [[ $? -eq 0 ]]; then
        log SUCCESS "Database rolled back successfully"
        return 0
    else
        log ERROR "Database rollback failed"
        return 1
    fi
}

# Rollback plugin files
rollback_plugin_files() {
    log WARNING "Rolling back plugin files..."
    
    local backup_file=$(cat "$TEMP_DIR/last_file_backup" 2>/dev/null)
    
    if [[ -z "$backup_file" ]] || [[ ! -f "$backup_file" ]]; then
        log WARNING "No file backup found, removing plugin directory"
        rm -rf "$PLUGIN_DIR"
        return 0
    fi
    
    # Remove current files
    rm -rf "$PLUGIN_DIR"
    
    # Restore from backup
    tar -xzf "$backup_file" -C "$(dirname "$PLUGIN_DIR")" 2>/dev/null
    
    if [[ $? -eq 0 ]]; then
        log SUCCESS "Plugin files rolled back successfully"
        return 0
    else
        log ERROR "Plugin files rollback failed"
        return 1
    fi
}

# Downgrade version in database
downgrade_version() {
    log WARNING "Downgrading plugin version in database..."
    
    # Update version in mdl_config_plugins
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "UPDATE ${DB_PREFIX}config_plugins 
            SET value = '$CURRENT_VERSION' 
            WHERE plugin = 'local_routinecoach' 
            AND name = 'version'" 2>/dev/null
    
    # Clear upgrade flags
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "DELETE FROM ${DB_PREFIX}upgrade_log 
            WHERE plugin = 'local_routinecoach' 
            AND version > '$CURRENT_VERSION'" 2>/dev/null
    
    log SUCCESS "Version downgraded to $CURRENT_VERSION"
    return 0
}

# Full rollback procedure
perform_rollback() {
    log ERROR "Deployment failed, initiating rollback..."
    
    local rollback_success=true
    
    # Rollback database
    if ! rollback_database; then
        rollback_success=false
    fi
    
    # Rollback files
    if ! rollback_plugin_files; then
        rollback_success=false
    fi
    
    # Downgrade version
    if ! downgrade_version; then
        rollback_success=false
    fi
    
    # Purge caches after rollback
    purge_caches
    
    if [[ "$rollback_success" == true ]]; then
        log SUCCESS "Rollback completed successfully"
        return 0
    else
        log ERROR "Rollback completed with errors - manual intervention may be required"
        return 1
    fi
}

# ============================================================================
# Main Deployment Flow
# ============================================================================

main() {
    log INFO "========================================="
    log INFO "Routine Coach Deployment Script"
    log INFO "Timestamp: $DEPLOY_TIMESTAMP"
    log INFO "========================================="
    
    # Create temp directory
    mkdir -p "$TEMP_DIR"
    trap "rm -rf $TEMP_DIR" EXIT
    
    # Step 1: Prerequisites
    check_prerequisites
    
    # Step 2: Get current version
    get_current_version
    
    # Step 3: Validate files
    if ! validate_install_xml; then
        log ERROR "Validation failed"
        exit 1
    fi
    
    if ! validate_upgrade_php; then
        log ERROR "Validation failed"
        exit 1
    fi
    
    # Step 4: Create backups
    if ! backup_database; then
        log ERROR "Backup failed"
        exit 1
    fi
    
    if ! backup_plugin_files; then
        log ERROR "Backup failed"
        exit 1
    fi
    
    create_version_snapshot
    
    # Step 5: Deploy
    if ! deploy_plugin; then
        perform_rollback
        exit 1
    fi
    
    # Step 6: Run upgrade
    if ! run_database_upgrade; then
        perform_rollback
        exit 1
    fi
    
    # Step 7: Purge caches
    if ! purge_caches; then
        log WARNING "Cache purge failed, continuing..."
    fi
    
    # Step 8: Verify deployment
    if ! verify_deployment; then
        log ERROR "Verification failed, initiating rollback"
        perform_rollback
        exit 1
    fi
    
    # Step 9: Cleanup old backups (keep last 10)
    log INFO "Cleaning up old backups..."
    ls -t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm
    ls -t "$BACKUP_DIR"/*.tar.gz 2>/dev/null | tail -n +11 | xargs -r rm
    
    log SUCCESS "========================================="
    log SUCCESS "Deployment completed successfully!"
    log SUCCESS "========================================="
    
    # Show summary
    echo ""
    echo "Deployment Summary:"
    echo "-------------------"
    echo "Previous Version: $CURRENT_VERSION"
    echo "Current Version: $(get_current_version && echo $CURRENT_VERSION)"
    echo "Backup Location: $BACKUP_DIR"
    echo "Log File: $DEPLOY_LOG"
    echo ""
    
    exit 0
}

# Handle interrupts
trap 'log ERROR "Deployment interrupted"; perform_rollback; exit 1' INT TERM

# Parse command line arguments
case "${1:-deploy}" in
    deploy)
        main
        ;;
    rollback)
        log INFO "Manual rollback requested"
        perform_rollback
        ;;
    validate)
        check_prerequisites
        validate_install_xml
        validate_upgrade_php
        ;;
    backup)
        check_prerequisites
        get_current_version
        backup_database
        backup_plugin_files
        create_version_snapshot
        ;;
    *)
        echo "Usage: $0 [deploy|rollback|validate|backup]"
        echo ""
        echo "Commands:"
        echo "  deploy   - Full deployment with automatic rollback on failure"
        echo "  rollback - Manual rollback to previous version"
        echo "  validate - Validate configuration files only"
        echo "  backup   - Create backup only"
        exit 1
        ;;
esac