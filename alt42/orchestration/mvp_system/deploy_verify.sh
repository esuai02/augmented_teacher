#!/bin/bash
# File: mvp_system/deploy_verify.sh
# Mathking Agentic MVP System - Deployment Verification Script
#
# Purpose: Automated pre-deployment verification on production server
# Usage: bash deploy_verify.sh [quick|full]
# Location: /home/moodle/public_html/moodle/local/augmented_teacher/alt42/orchestration/mvp_system/

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Counters
PASS_COUNT=0
FAIL_COUNT=0
WARN_COUNT=0

# Mode (quick or full)
MODE="${1:-quick}"

echo "========================================================================"
echo "  MATHKING AGENTIC MVP SYSTEM - DEPLOYMENT VERIFICATION"
echo "========================================================================"
echo ""
echo "Mode: $MODE"
echo "Date: $(date)"
echo ""

# Function to print status
print_status() {
    local status=$1
    local message=$2

    if [ "$status" = "PASS" ]; then
        echo -e "${GREEN}✅ PASS${NC} - $message"
        ((PASS_COUNT++))
    elif [ "$status" = "FAIL" ]; then
        echo -e "${RED}❌ FAIL${NC} - $message"
        ((FAIL_COUNT++))
    elif [ "$status" = "WARN" ]; then
        echo -e "${YELLOW}⚠️  WARN${NC} - $message"
        ((WARN_COUNT++))
    else
        echo -e "${BLUE}ℹ️  INFO${NC} - $message"
    fi
}

echo "========================================================================"
echo "PHASE 1: FILE STRUCTURE VERIFICATION"
echo "========================================================================"

# Check critical files
CRITICAL_FILES=(
    "config/app.config.php"
    "lib/database.php"
    "lib/logger.php"
    "sensing/calm_calculator.py"
    "decision/rule_engine.py"
    "execution/intervention_dispatcher.php"
    "orchestrator.php"
    "ui/teacher_panel.php"
    "api/feedback.php"
    "monitoring/sla_monitor.php"
)

for file in "${CRITICAL_FILES[@]}"; do
    if [ -f "$file" ]; then
        print_status "PASS" "File exists: $file"
    else
        print_status "FAIL" "Missing file: $file"
    fi
done

echo ""
echo "========================================================================"
echo "PHASE 2: PYTHON ENVIRONMENT CHECK"
echo "========================================================================"

# Check Python version
if command -v python3 &> /dev/null; then
    PYTHON_VERSION=$(python3 --version 2>&1)
    print_status "PASS" "Python available: $PYTHON_VERSION"

    # Check required Python modules
    PYTHON_MODULES=("yaml" "json" "sys" "datetime")
    for module in "${PYTHON_MODULES[@]}"; do
        if python3 -c "import $module" 2>/dev/null; then
            print_status "PASS" "Python module: $module"
        else
            print_status "FAIL" "Missing Python module: $module"
        fi
    done
else
    print_status "FAIL" "Python3 not found"
fi

echo ""
echo "========================================================================"
echo "PHASE 3: PHP ENVIRONMENT CHECK"
echo "========================================================================"

# Check PHP version
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | head -n 1)
    print_status "PASS" "PHP available: $PHP_VERSION"

    # Check PHP syntax for critical files
    if [ "$MODE" = "full" ]; then
        for file in "${CRITICAL_FILES[@]}"; do
            if [[ "$file" == *.php ]]; then
                if [ -f "$file" ]; then
                    if php -l "$file" > /dev/null 2>&1; then
                        print_status "PASS" "PHP syntax valid: $file"
                    else
                        print_status "FAIL" "PHP syntax error: $file"
                    fi
                fi
            fi
        done
    fi
else
    print_status "FAIL" "PHP not found"
fi

echo ""
echo "========================================================================"
echo "PHASE 4: DIRECTORY PERMISSIONS"
echo "========================================================================"

# Check critical directories
CRITICAL_DIRS=(
    "logs"
    "sensing"
    "decision"
    "execution"
    "ui"
    "api"
    "monitoring"
    "tests"
)

for dir in "${CRITICAL_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        if [ -r "$dir" ]; then
            print_status "PASS" "Directory readable: $dir"
        else
            print_status "WARN" "Directory not readable: $dir"
        fi
    else
        print_status "FAIL" "Missing directory: $dir"
    fi
done

# Check logs directory writability
if [ -d "logs" ]; then
    if [ -w "logs" ]; then
        print_status "PASS" "Logs directory writable"
    else
        print_status "WARN" "Logs directory not writable (will affect logging)"
    fi
fi

echo ""
echo "========================================================================"
echo "PHASE 5: CONFIGURATION VALIDATION"
echo "========================================================================"

# Check if config file exists and has required constants
if [ -f "config/app.config.php" ]; then
    if grep -q "define('MVP_VERSION'" config/app.config.php; then
        print_status "PASS" "MVP_VERSION defined in config"
    else
        print_status "WARN" "MVP_VERSION not found in config"
    fi

    if grep -q "define('SLA_TARGET_SECONDS'" config/app.config.php; then
        print_status "PASS" "SLA_TARGET_SECONDS defined in config"
    else
        print_status "WARN" "SLA_TARGET_SECONDS not found in config"
    fi
fi

echo ""
echo "========================================================================"
echo "PHASE 6: YAML RULES VALIDATION"
echo "========================================================================"

# Check YAML rules file
if [ -f "decision/rules/calm_break_rules.yaml" ]; then
    print_status "PASS" "YAML rules file exists"

    # Basic YAML syntax check using Python
    if python3 -c "import yaml; yaml.safe_load(open('decision/rules/calm_break_rules.yaml'))" 2>/dev/null; then
        print_status "PASS" "YAML rules file syntax valid"
    else
        print_status "FAIL" "YAML rules file syntax invalid"
    fi
else
    print_status "FAIL" "Missing YAML rules file"
fi

echo ""

if [ "$MODE" = "full" ]; then
    echo "========================================================================"
    echo "PHASE 7: DATABASE CONNECTION TEST (FULL MODE ONLY)"
    echo "========================================================================"

    # Run PHP database connection test
    if [ -f "tests/verify_mvp.php" ]; then
        print_status "INFO" "Running database verification (this may take a few seconds)..."

        # Run verification script and capture output
        if php tests/verify_mvp.php > /tmp/mvp_verify.log 2>&1; then
            print_status "PASS" "Database verification completed successfully"
            echo ""
            echo "--- Last 10 lines of verification output ---"
            tail -n 10 /tmp/mvp_verify.log
        else
            print_status "FAIL" "Database verification failed"
            echo ""
            echo "--- Error output ---"
            cat /tmp/mvp_verify.log
        fi
    else
        print_status "WARN" "Verification script not found"
    fi

    echo ""
    echo "========================================================================"
    echo "PHASE 8: UNIT TESTS (FULL MODE ONLY)"
    echo "========================================================================"

    # Run Python tests
    if [ -f "sensing/tests/calm_calculator.test.py" ]; then
        if python3 sensing/tests/calm_calculator.test.py > /tmp/calm_test.log 2>&1; then
            print_status "PASS" "Calm calculator tests passed"
        else
            print_status "FAIL" "Calm calculator tests failed"
            tail -n 5 /tmp/calm_test.log
        fi
    fi

    if [ -f "decision/tests/rule_engine.test.py" ]; then
        if python3 decision/tests/rule_engine.test.py > /tmp/rule_test.log 2>&1; then
            print_status "PASS" "Rule engine tests passed"
        else
            print_status "FAIL" "Rule engine tests failed"
            tail -n 5 /tmp/rule_test.log
        fi
    fi
fi

echo ""
echo "========================================================================"
echo "DEPLOYMENT READINESS SUMMARY"
echo "========================================================================"
echo ""
echo -e "${GREEN}✅ Passed:  $PASS_COUNT${NC}"
echo -e "${YELLOW}⚠️  Warnings: $WARN_COUNT${NC}"
echo -e "${RED}❌ Failed:  $FAIL_COUNT${NC}"
echo ""

# Determine overall status
if [ $FAIL_COUNT -eq 0 ] && [ $WARN_COUNT -eq 0 ]; then
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${GREEN}   ✅ SYSTEM READY FOR DEPLOYMENT${NC}"
    echo -e "${GREEN}═══════════════════════════════════════════════════════════════${NC}"
    exit 0
elif [ $FAIL_COUNT -eq 0 ]; then
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}   ⚠️  SYSTEM READY WITH WARNINGS${NC}"
    echo -e "${YELLOW}   Review warnings before deployment${NC}"
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════════${NC}"
    exit 1
else
    echo -e "${RED}═══════════════════════════════════════════════════════════════${NC}"
    echo -e "${RED}   ❌ SYSTEM NOT READY FOR DEPLOYMENT${NC}"
    echo -e "${RED}   Fix failures before proceeding${NC}"
    echo -e "${RED}═══════════════════════════════════════════════════════════════${NC}"
    exit 2
fi
