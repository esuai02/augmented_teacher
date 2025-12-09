#!/bin/bash
# Pre-deployment validation script
# Validates install.xml and upgrade.php before deployment

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

PLUGIN_DIR="$(dirname "$0")/.."
ERRORS=0
WARNINGS=0

echo "======================================"
echo "Pre-Deployment Validation"
echo "======================================"
echo ""

# Function to check file
check_file() {
    local file=$1
    local description=$2
    
    if [[ -f "$file" ]]; then
        echo -e "${GREEN}✓${NC} $description exists"
        return 0
    else
        echo -e "${RED}✗${NC} $description missing: $file"
        ((ERRORS++))
        return 1
    fi
}

# Function to validate XML
validate_xml() {
    local file=$1
    local description=$2
    
    if xmllint --noout "$file" 2>/dev/null; then
        echo -e "${GREEN}✓${NC} $description XML syntax valid"
        return 0
    else
        echo -e "${RED}✗${NC} $description XML syntax errors:"
        xmllint "$file" 2>&1 | head -5
        ((ERRORS++))
        return 1
    fi
}

# Function to validate PHP syntax
validate_php() {
    local file=$1
    local description=$2
    
    if php -l "$file" > /dev/null 2>&1; then
        echo -e "${GREEN}✓${NC} $description PHP syntax valid"
        return 0
    else
        echo -e "${RED}✗${NC} $description PHP syntax errors:"
        php -l "$file" 2>&1 | head -5
        ((ERRORS++))
        return 1
    fi
}

# 1. Check required files
echo "1. Checking required files..."
echo "------------------------------"

check_file "$PLUGIN_DIR/version.php" "version.php"
check_file "$PLUGIN_DIR/db/install.xml" "db/install.xml"
check_file "$PLUGIN_DIR/db/access.php" "db/access.php"
check_file "$PLUGIN_DIR/lang/en/local_routinecoach.php" "Language file"

echo ""

# 2. Validate version.php
echo "2. Validating version.php..."
echo "------------------------------"

if validate_php "$PLUGIN_DIR/version.php" "version.php"; then
    # Check version number
    VERSION=$(grep '^\$plugin->version' "$PLUGIN_DIR/version.php" | grep -oP '\d+' || echo "0")
    REQUIRES=$(grep '^\$plugin->requires' "$PLUGIN_DIR/version.php" | grep -oP '\d+' || echo "0")
    COMPONENT=$(grep '^\$plugin->component' "$PLUGIN_DIR/version.php" | grep -oP "'[^']+'" | tr -d "'")
    
    echo "  Version: $VERSION"
    echo "  Requires: Moodle $REQUIRES"
    echo "  Component: $COMPONENT"
    
    # Validate version format (YYYYMMDDXX)
    if [[ ${#VERSION} -eq 10 ]]; then
        echo -e "${GREEN}✓${NC} Version format correct"
    else
        echo -e "${YELLOW}⚠${NC} Version format unusual (expected YYYYMMDDXX)"
        ((WARNINGS++))
    fi
    
    # Validate component name
    if [[ "$COMPONENT" == "local_routinecoach" ]]; then
        echo -e "${GREEN}✓${NC} Component name correct"
    else
        echo -e "${RED}✗${NC} Component name incorrect (expected: local_routinecoach)"
        ((ERRORS++))
    fi
fi

echo ""

# 3. Validate install.xml
echo "3. Validating install.xml..."
echo "------------------------------"

INSTALL_XML="$PLUGIN_DIR/db/install.xml"

if validate_xml "$INSTALL_XML" "install.xml"; then
    # Check table definitions
    TABLES=(
        "routinecoach_exam"
        "routinecoach_routine"
        "routinecoach_task"
        "routinecoach_log"
        "routinecoach_pref"
    )
    
    for table in "${TABLES[@]}"; do
        if xmllint --xpath "//TABLE[@NAME='$table']" "$INSTALL_XML" &>/dev/null; then
            echo -e "${GREEN}✓${NC} Table $table defined"
            
            # Check for required fields
            if xmllint --xpath "//TABLE[@NAME='$table']//FIELD[@NAME='id']" "$INSTALL_XML" &>/dev/null; then
                echo -e "  ${GREEN}✓${NC} Has id field"
            else
                echo -e "  ${RED}✗${NC} Missing id field"
                ((ERRORS++))
            fi
            
            if xmllint --xpath "//TABLE[@NAME='$table']//KEY[@NAME='primary']" "$INSTALL_XML" &>/dev/null; then
                echo -e "  ${GREEN}✓${NC} Has primary key"
            else
                echo -e "  ${YELLOW}⚠${NC} Missing primary key definition"
                ((WARNINGS++))
            fi
        else
            echo -e "${RED}✗${NC} Table $table not defined"
            ((ERRORS++))
        fi
    done
    
    # Check foreign keys
    echo ""
    echo "Checking foreign keys..."
    
    if xmllint --xpath "//TABLE[@NAME='routinecoach_routine']//KEY[@NAME='examid']" "$INSTALL_XML" &>/dev/null; then
        echo -e "${GREEN}✓${NC} routinecoach_routine has examid foreign key"
    else
        echo -e "${YELLOW}⚠${NC} routinecoach_routine missing examid foreign key"
        ((WARNINGS++))
    fi
    
    if xmllint --xpath "//TABLE[@NAME='routinecoach_task']//KEY[@NAME='routineid']" "$INSTALL_XML" &>/dev/null; then
        echo -e "${GREEN}✓${NC} routinecoach_task has routineid foreign key"
    else
        echo -e "${YELLOW}⚠${NC} routinecoach_task missing routineid foreign key"
        ((WARNINGS++))
    fi
fi

echo ""

# 4. Validate upgrade.php (if exists)
echo "4. Validating upgrade.php..."
echo "------------------------------"

UPGRADE_PHP="$PLUGIN_DIR/db/upgrade.php"

if [[ -f "$UPGRADE_PHP" ]]; then
    if validate_php "$UPGRADE_PHP" "upgrade.php"; then
        # Check for upgrade function
        if grep -q "function xmldb_local_routinecoach_upgrade" "$UPGRADE_PHP"; then
            echo -e "${GREEN}✓${NC} Upgrade function found"
            
            # Check for savepoint calls
            SAVEPOINTS=$(grep -c "upgrade_plugin_savepoint" "$UPGRADE_PHP" || echo "0")
            echo "  Savepoints defined: $SAVEPOINTS"
            
            if [[ $SAVEPOINTS -gt 0 ]]; then
                echo -e "${GREEN}✓${NC} Has savepoints"
            else
                echo -e "${YELLOW}⚠${NC} No savepoints found"
                ((WARNINGS++))
            fi
        else
            echo -e "${RED}✗${NC} Upgrade function not found"
            ((ERRORS++))
        fi
    fi
else
    echo -e "${YELLOW}⚠${NC} upgrade.php not found (OK for initial installation)"
    ((WARNINGS++))
fi

echo ""

# 5. Check capabilities
echo "5. Checking capabilities..."
echo "------------------------------"

ACCESS_PHP="$PLUGIN_DIR/db/access.php"

if validate_php "$ACCESS_PHP" "access.php"; then
    # Check for capabilities array
    if grep -q '^\$capabilities' "$ACCESS_PHP"; then
        echo -e "${GREEN}✓${NC} Capabilities array defined"
        
        # Check specific capabilities
        CAPABILITIES=(
            "local/routinecoach:view"
            "local/routinecoach:manage"
            "local/routinecoach:viewall"
        )
        
        for cap in "${CAPABILITIES[@]}"; do
            if grep -q "$cap" "$ACCESS_PHP"; then
                echo -e "  ${GREEN}✓${NC} $cap defined"
            else
                echo -e "  ${RED}✗${NC} $cap not defined"
                ((ERRORS++))
            fi
        done
    else
        echo -e "${RED}✗${NC} Capabilities array not found"
        ((ERRORS++))
    fi
fi

echo ""

# 6. Check language strings
echo "6. Checking language strings..."
echo "------------------------------"

LANG_FILE="$PLUGIN_DIR/lang/en/local_routinecoach.php"

if validate_php "$LANG_FILE" "Language file"; then
    # Check for required strings
    REQUIRED_STRINGS=(
        "pluginname"
        "routinecoach:view"
        "routinecoach:manage"
        "routinecoach:viewall"
    )
    
    for string in "${REQUIRED_STRINGS[@]}"; do
        if grep -q "\$string\['$string'\]" "$LANG_FILE"; then
            echo -e "${GREEN}✓${NC} String '$string' defined"
        else
            echo -e "${RED}✗${NC} String '$string' not defined"
            ((ERRORS++))
        fi
    done
fi

echo ""

# 7. Check service classes
echo "7. Checking service classes..."
echo "------------------------------"

SERVICE_FILE="$PLUGIN_DIR/classes/service/routine_service.php"

if check_file "$SERVICE_FILE" "routine_service.php"; then
    if validate_php "$SERVICE_FILE" "routine_service.php"; then
        # Check namespace
        if grep -q "namespace local_routinecoach\\\\service;" "$SERVICE_FILE"; then
            echo -e "${GREEN}✓${NC} Namespace correct"
        else
            echo -e "${RED}✗${NC} Namespace incorrect or missing"
            ((ERRORS++))
        fi
        
        # Check class definition
        if grep -q "class routine_service" "$SERVICE_FILE"; then
            echo -e "${GREEN}✓${NC} Class defined"
        else
            echo -e "${RED}✗${NC} Class not found"
            ((ERRORS++))
        fi
        
        # Check required methods
        REQUIRED_METHODS=(
            "on_exam_saved"
            "get_today_tasks"
            "complete_task"
        )
        
        for method in "${REQUIRED_METHODS[@]}"; do
            if grep -q "function $method" "$SERVICE_FILE"; then
                echo -e "  ${GREEN}✓${NC} Method $method exists"
            else
                echo -e "  ${RED}✗${NC} Method $method not found"
                ((ERRORS++))
            fi
        done
    fi
fi

echo ""

# 8. Check AMD JavaScript
echo "8. Checking AMD JavaScript..."
echo "------------------------------"

AMD_SRC="$PLUGIN_DIR/amd/src/routinecoach.js"

if check_file "$AMD_SRC" "AMD source file"; then
    # Check for define statement
    if grep -q "^define(" "$AMD_SRC"; then
        echo -e "${GREEN}✓${NC} AMD module defined"
    else
        echo -e "${RED}✗${NC} AMD module definition not found"
        ((ERRORS++))
    fi
    
    # Check if build exists
    AMD_BUILD="$PLUGIN_DIR/amd/build/routinecoach.min.js"
    if [[ -f "$AMD_BUILD" ]]; then
        # Compare timestamps
        if [[ "$AMD_BUILD" -nt "$AMD_SRC" ]]; then
            echo -e "${GREEN}✓${NC} AMD build is up to date"
        else
            echo -e "${YELLOW}⚠${NC} AMD build is outdated, rebuild required"
            echo "  Run: php admin/cli/build_js.php"
            ((WARNINGS++))
        fi
    else
        echo -e "${YELLOW}⚠${NC} AMD build not found, build required"
        echo "  Run: php admin/cli/build_js.php"
        ((WARNINGS++))
    fi
fi

echo ""

# 9. Check templates
echo "9. Checking templates..."
echo "------------------------------"

TEMPLATES=(
    "routinecoach.mustache"
    "widget.mustache"
)

for template in "${TEMPLATES[@]}"; do
    TEMPLATE_FILE="$PLUGIN_DIR/templates/$template"
    if check_file "$TEMPLATE_FILE" "Template $template"; then
        # Basic mustache syntax check
        if grep -q "{{" "$TEMPLATE_FILE"; then
            echo -e "  ${GREEN}✓${NC} Contains mustache syntax"
        else
            echo -e "  ${YELLOW}⚠${NC} No mustache syntax found"
            ((WARNINGS++))
        fi
    fi
done

echo ""

# 10. Check file permissions
echo "10. Checking file permissions..."
echo "------------------------------"

# Check if files are readable
find "$PLUGIN_DIR" -type f ! -readable 2>/dev/null | while read file; do
    echo -e "${YELLOW}⚠${NC} File not readable: $file"
    ((WARNINGS++))
done

# Check if directories are executable
find "$PLUGIN_DIR" -type d ! -executable 2>/dev/null | while read dir; do
    echo -e "${YELLOW}⚠${NC} Directory not accessible: $dir"
    ((WARNINGS++))
done

if [[ $(find "$PLUGIN_DIR" -type f ! -readable 2>/dev/null | wc -l) -eq 0 ]]; then
    echo -e "${GREEN}✓${NC} All files are readable"
fi

if [[ $(find "$PLUGIN_DIR" -type d ! -executable 2>/dev/null | wc -l) -eq 0 ]]; then
    echo -e "${GREEN}✓${NC} All directories are accessible"
fi

echo ""
echo "======================================"
echo "Validation Summary"
echo "======================================"
echo ""

if [[ $ERRORS -eq 0 && $WARNINGS -eq 0 ]]; then
    echo -e "${GREEN}✓ All checks passed!${NC}"
    echo "Plugin is ready for deployment."
    exit 0
elif [[ $ERRORS -eq 0 ]]; then
    echo -e "${YELLOW}⚠ Validation completed with $WARNINGS warnings${NC}"
    echo "Plugin can be deployed but review warnings."
    exit 0
else
    echo -e "${RED}✗ Validation failed with $ERRORS errors and $WARNINGS warnings${NC}"
    echo "Fix errors before deployment."
    exit 1
fi