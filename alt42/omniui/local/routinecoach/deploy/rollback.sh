#!/bin/bash
# Standalone rollback script for Routine Coach plugin
# Can rollback to any previous backup

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Configuration
MOODLE_ROOT="/home/moodle/public_html/moodle"
PLUGIN_DIR="$MOODLE_ROOT/local/routinecoach"
BACKUP_DIR="/home/moodle/backups/routinecoach"
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

echo "======================================"
echo "Routine Coach Rollback Tool"
echo "======================================"
echo ""

# Function to list available backups
list_backups() {
    echo "Available backups:"
    echo "------------------"
    
    local count=0
    
    # List database backups
    echo ""
    echo "Database backups:"
    for backup in $(ls -t "$BACKUP_DIR"/db_backup_*.sql.gz 2>/dev/null | head -10); do
        ((count++))
        local timestamp=$(basename "$backup" | sed 's/db_backup_\(.*\)\.sql\.gz/\1/')
        local size=$(du -h "$backup" | cut -f1)
        local date=$(echo $timestamp | sed 's/\(....\)\(..\)\(..\)_\(..\)\(..\)\(..\)/\1-\2-\3 \4:\5:\6/')
        echo "  $count) $date [$size] - $backup"
    done
    
    if [[ $count -eq 0 ]]; then
        echo "  No database backups found"
    fi
    
    # List file backups
    echo ""
    echo "File backups:"
    count=0
    for backup in $(ls -t "$BACKUP_DIR"/files_backup_*.tar.gz 2>/dev/null | head -10); do
        ((count++))
        local timestamp=$(basename "$backup" | sed 's/files_backup_\(.*\)\.tar\.gz/\1/')
        local size=$(du -h "$backup" | cut -f1)
        local date=$(echo $timestamp | sed 's/\(....\)\(..\)\(..\)_\(..\)\(..\)\(..\)/\1-\2-\3 \4:\5:\6/')
        echo "  $count) $date [$size] - $backup"
    done
    
    if [[ $count -eq 0 ]]; then
        echo "  No file backups found"
    fi
    
    echo ""
}

# Function to select backup
select_backup() {
    local backup_type=$1
    local pattern=""
    
    if [[ "$backup_type" == "db" ]]; then
        pattern="db_backup_*.sql.gz"
    else
        pattern="files_backup_*.tar.gz"
    fi
    
    # Get list of backups
    local backups=($(ls -t "$BACKUP_DIR"/$pattern 2>/dev/null | head -10))
    
    if [[ ${#backups[@]} -eq 0 ]]; then
        echo -e "${RED}No backups found${NC}"
        return 1
    fi
    
    echo "Select $backup_type backup to restore:"
    local i=1
    for backup in "${backups[@]}"; do
        local timestamp=$(basename "$backup" | sed "s/${backup_type}_backup_\(.*\)\..*\.gz/\1/")
        local date=$(echo $timestamp | sed 's/\(....\)\(..\)\(..\)_\(..\)\(..\)\(..\)/\1-\2-\3 \4:\5:\6/')
        echo "  $i) $date - $(basename $backup)"
        ((i++))
    done
    
    read -p "Enter number (1-${#backups[@]}): " selection
    
    if [[ $selection -ge 1 ]] && [[ $selection -le ${#backups[@]} ]]; then
        echo "${backups[$((selection-1))]}"
    else
        echo -e "${RED}Invalid selection${NC}"
        return 1
    fi
}

# Function to restore database
restore_database() {
    local backup_file=$1
    
    if [[ ! -f "$backup_file" ]]; then
        echo -e "${RED}Backup file not found: $backup_file${NC}"
        return 1
    fi
    
    echo -e "${YELLOW}Restoring database from: $(basename $backup_file)${NC}"
    
    # Confirm action
    read -p "This will DROP and RECREATE all plugin tables. Continue? (yes/no): " confirm
    if [[ "$confirm" != "yes" ]]; then
        echo "Rollback cancelled"
        return 1
    fi
    
    # Drop existing tables
    echo "Dropping existing tables..."
    for table in "${PLUGIN_TABLES[@]}"; do
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
            -e "DROP TABLE IF EXISTS ${DB_PREFIX}${table}" 2>/dev/null
        echo -e "  ${GREEN}✓${NC} Dropped ${DB_PREFIX}${table}"
    done
    
    # Restore from backup
    echo "Restoring from backup..."
    if [[ "$backup_file" == *.gz ]]; then
        gunzip -c "$backup_file" | mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME"
    else
        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$backup_file"
    fi
    
    if [[ $? -eq 0 ]]; then
        echo -e "${GREEN}✓ Database restored successfully${NC}"
        
        # Verify restoration
        echo "Verifying restoration..."
        for table in "${PLUGIN_TABLES[@]}"; do
            local count=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
                -e "SELECT COUNT(*) FROM ${DB_PREFIX}${table}" 2>/dev/null | tail -1 || echo "ERROR")
            
            if [[ "$count" != "ERROR" ]]; then
                echo -e "  ${GREEN}✓${NC} ${DB_PREFIX}${table}: $count records"
            else
                echo -e "  ${RED}✗${NC} ${DB_PREFIX}${table}: Failed to verify"
            fi
        done
        
        return 0
    else
        echo -e "${RED}✗ Database restoration failed${NC}"
        return 1
    fi
}

# Function to restore files
restore_files() {
    local backup_file=$1
    
    if [[ ! -f "$backup_file" ]]; then
        echo -e "${RED}Backup file not found: $backup_file${NC}"
        return 1
    fi
    
    echo -e "${YELLOW}Restoring files from: $(basename $backup_file)${NC}"
    
    # Confirm action
    read -p "This will REPLACE all plugin files. Continue? (yes/no): " confirm
    if [[ "$confirm" != "yes" ]]; then
        echo "Rollback cancelled"
        return 1
    fi
    
    # Remove existing plugin directory
    if [[ -d "$PLUGIN_DIR" ]]; then
        echo "Removing existing plugin files..."
        rm -rf "$PLUGIN_DIR"
        echo -e "  ${GREEN}✓${NC} Removed $PLUGIN_DIR"
    fi
    
    # Extract backup
    echo "Extracting backup..."
    tar -xzf "$backup_file" -C "$(dirname "$PLUGIN_DIR")"
    
    if [[ $? -eq 0 ]]; then
        echo -e "${GREEN}✓ Files restored successfully${NC}"
        
        # Set permissions
        echo "Setting permissions..."
        chown -R www-data:www-data "$PLUGIN_DIR"
        find "$PLUGIN_DIR" -type d -exec chmod 755 {} \;
        find "$PLUGIN_DIR" -type f -exec chmod 644 {} \;
        echo -e "  ${GREEN}✓${NC} Permissions set"
        
        return 0
    else
        echo -e "${RED}✗ File restoration failed${NC}"
        return 1
    fi
}

# Function to downgrade version
downgrade_version() {
    local target_version=$1
    
    echo -e "${YELLOW}Downgrading plugin version to: $target_version${NC}"
    
    # Update version in config_plugins table
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "UPDATE ${DB_PREFIX}config_plugins 
            SET value = '$target_version' 
            WHERE plugin = 'local_routinecoach' 
            AND name = 'version'" 2>/dev/null
    
    # Remove upgrade log entries
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "DELETE FROM ${DB_PREFIX}upgrade_log 
            WHERE plugin = 'local_routinecoach' 
            AND version > '$target_version'" 2>/dev/null
    
    echo -e "${GREEN}✓ Version downgraded${NC}"
}

# Function to purge caches
purge_caches() {
    echo -e "${YELLOW}Purging caches...${NC}"
    
    cd "$MOODLE_ROOT"
    php admin/cli/purge_caches.php
    
    # Clear additional caches
    rm -rf "$MOODLE_ROOT/localcache/*" 2>/dev/null
    rm -rf "$MOODLE_ROOT/moodledata/cache/*" 2>/dev/null
    rm -rf "$MOODLE_ROOT/moodledata/localcache/*" 2>/dev/null
    
    echo -e "${GREEN}✓ Caches purged${NC}"
}

# Main menu
main_menu() {
    while true; do
        echo ""
        echo "Select rollback option:"
        echo "----------------------"
        echo "1) Full rollback (database + files)"
        echo "2) Database only"
        echo "3) Files only"
        echo "4) List all backups"
        echo "5) Remove plugin completely"
        echo "6) Exit"
        echo ""
        
        read -p "Enter choice (1-6): " choice
        
        case $choice in
            1)
                echo ""
                echo -e "${BLUE}Full Rollback Selected${NC}"
                echo ""
                
                # Select database backup
                db_backup=$(select_backup "db")
                if [[ $? -ne 0 ]]; then
                    continue
                fi
                
                # Select file backup
                file_backup=$(select_backup "files")
                if [[ $? -ne 0 ]]; then
                    continue
                fi
                
                # Perform rollback
                if restore_database "$db_backup"; then
                    if restore_files "$file_backup"; then
                        
                        # Extract version from backup
                        if [[ -f "$PLUGIN_DIR/version.php" ]]; then
                            version=$(grep '^\$plugin->version' "$PLUGIN_DIR/version.php" | grep -oP '\d+' || echo "0")
                            downgrade_version "$version"
                        fi
                        
                        purge_caches
                        
                        echo ""
                        echo -e "${GREEN}✓ Full rollback completed successfully${NC}"
                    fi
                fi
                ;;
                
            2)
                echo ""
                echo -e "${BLUE}Database Rollback Selected${NC}"
                echo ""
                
                db_backup=$(select_backup "db")
                if [[ $? -eq 0 ]]; then
                    if restore_database "$db_backup"; then
                        purge_caches
                        echo ""
                        echo -e "${GREEN}✓ Database rollback completed${NC}"
                    fi
                fi
                ;;
                
            3)
                echo ""
                echo -e "${BLUE}Files Rollback Selected${NC}"
                echo ""
                
                file_backup=$(select_backup "files")
                if [[ $? -eq 0 ]]; then
                    if restore_files "$file_backup"; then
                        purge_caches
                        echo ""
                        echo -e "${GREEN}✓ Files rollback completed${NC}"
                    fi
                fi
                ;;
                
            4)
                echo ""
                list_backups
                ;;
                
            5)
                echo ""
                echo -e "${RED}Complete Plugin Removal Selected${NC}"
                echo ""
                read -p "This will PERMANENTLY remove the plugin. Continue? (yes/no): " confirm
                
                if [[ "$confirm" == "yes" ]]; then
                    # Drop all tables
                    echo "Dropping all plugin tables..."
                    for table in "${PLUGIN_TABLES[@]}"; do
                        mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
                            -e "DROP TABLE IF EXISTS ${DB_PREFIX}${table}" 2>/dev/null
                        echo -e "  ${GREEN}✓${NC} Dropped ${DB_PREFIX}${table}"
                    done
                    
                    # Remove plugin files
                    if [[ -d "$PLUGIN_DIR" ]]; then
                        echo "Removing plugin files..."
                        rm -rf "$PLUGIN_DIR"
                        echo -e "  ${GREEN}✓${NC} Removed $PLUGIN_DIR"
                    fi
                    
                    # Clean config
                    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" \
                        -e "DELETE FROM ${DB_PREFIX}config_plugins 
                            WHERE plugin = 'local_routinecoach'" 2>/dev/null
                    
                    purge_caches
                    
                    echo ""
                    echo -e "${GREEN}✓ Plugin removed completely${NC}"
                fi
                ;;
                
            6)
                echo "Exiting..."
                exit 0
                ;;
                
            *)
                echo -e "${RED}Invalid choice${NC}"
                ;;
        esac
    done
}

# Check if running with specific backup files as arguments
if [[ $# -eq 2 ]]; then
    # Direct rollback with specified files
    db_backup=$1
    file_backup=$2
    
    if [[ -f "$db_backup" ]] && [[ -f "$file_backup" ]]; then
        echo "Direct rollback mode"
        echo "Database backup: $db_backup"
        echo "File backup: $file_backup"
        echo ""
        
        if restore_database "$db_backup"; then
            if restore_files "$file_backup"; then
                purge_caches
                echo ""
                echo -e "${GREEN}✓ Rollback completed successfully${NC}"
                exit 0
            fi
        fi
        exit 1
    else
        echo -e "${RED}Invalid backup files specified${NC}"
        exit 1
    fi
else
    # Interactive mode
    main_menu
fi