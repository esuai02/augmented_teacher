#!/bin/bash
# Test runner script for Routine Coach plugin
# Copyright 2024 MathKing

echo "========================================="
echo "Routine Coach Test Suite"
echo "========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
MOODLE_ROOT="/home/moodle/public_html/moodle"
PLUGIN_DIR="$MOODLE_ROOT/local/routinecoach"
TEST_DB="moodle_test"

# Function to run PHPUnit tests
run_phpunit() {
    echo -e "${YELLOW}Running PHPUnit tests...${NC}"
    
    # Initialize test environment
    cd $MOODLE_ROOT
    php admin/tool/phpunit/cli/init.php
    
    # Run tests
    vendor/bin/phpunit \
        --configuration $PLUGIN_DIR/phpunit.xml \
        --testsuite local_routinecoach_testsuite \
        --coverage-html $PLUGIN_DIR/tests/coverage
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ PHPUnit tests passed${NC}"
        return 0
    else
        echo -e "${RED}✗ PHPUnit tests failed${NC}"
        return 1
    fi
}

# Function to run specific test class
run_test_class() {
    local test_class=$1
    echo -e "${YELLOW}Running test class: $test_class${NC}"
    
    cd $MOODLE_ROOT
    vendor/bin/phpunit \
        --configuration $PLUGIN_DIR/phpunit.xml \
        --filter $test_class
}

# Function to run E2E tests
run_e2e() {
    echo -e "${YELLOW}Running E2E tests...${NC}"
    
    cd $PLUGIN_DIR/tests/e2e
    
    # Install dependencies if needed
    if [ ! -d "node_modules" ]; then
        npm install @playwright/test
        npx playwright install
    fi
    
    # Run E2E tests
    npx playwright test widget.e2e.js
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ E2E tests passed${NC}"
        return 0
    else
        echo -e "${RED}✗ E2E tests failed${NC}"
        return 1
    fi
}

# Function to run template snapshot tests
run_snapshot() {
    echo -e "${YELLOW}Running template snapshot tests...${NC}"
    
    cd $MOODLE_ROOT
    vendor/bin/phpunit \
        --configuration $PLUGIN_DIR/phpunit.xml \
        --filter widget_template_test
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Snapshot tests passed${NC}"
        return 0
    else
        echo -e "${RED}✗ Snapshot tests failed${NC}"
        return 1
    fi
}

# Function to validate database schema
validate_db() {
    echo -e "${YELLOW}Validating database schema...${NC}"
    
    cd $MOODLE_ROOT
    php admin/cli/upgrade.php --non-interactive
    
    # Check if tables exist
    mysql -u moodle -p@MCtrigd7128 -D mathking -e "
        SELECT COUNT(*) as table_count FROM information_schema.tables 
        WHERE table_schema = 'mathking' 
        AND table_name IN (
            'mdl_routinecoach_exam',
            'mdl_routinecoach_routine',
            'mdl_routinecoach_task',
            'mdl_routinecoach_log',
            'mdl_routinecoach_pref'
        );
    "
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Database schema valid${NC}"
        return 0
    else
        echo -e "${RED}✗ Database schema invalid${NC}"
        return 1
    fi
}

# Function to run specific test scenarios
run_scenario() {
    local scenario=$1
    
    case $scenario in
        "ratio")
            echo -e "${YELLOW}Testing 7:3 ratio creation...${NC}"
            run_test_class "test_on_exam_saved_creates_routine_with_correct_ratio"
            ;;
        "duplicate")
            echo -e "${YELLOW}Testing duplicate prevention...${NC}"
            run_test_class "test_rebuild_prevents_duplicate_tasks"
            ;;
        "push")
            echo -e "${YELLOW}Testing push notification limits...${NC}"
            run_test_class "test_weekly_max_push_suppresses_notifications"
            ;;
        "widget")
            echo -e "${YELLOW}Testing widget rendering...${NC}"
            run_snapshot
            ;;
        *)
            echo -e "${RED}Unknown scenario: $scenario${NC}"
            return 1
            ;;
    esac
}

# Function to generate coverage report
generate_coverage() {
    echo -e "${YELLOW}Generating coverage report...${NC}"
    
    if [ -d "$PLUGIN_DIR/tests/coverage" ]; then
        echo "Coverage report available at: $PLUGIN_DIR/tests/coverage/index.html"
        
        # Calculate coverage percentage
        coverage=$(grep -oP 'Lines:\s+\K[0-9.]+' $PLUGIN_DIR/tests/coverage.txt 2>/dev/null || echo "0")
        
        if (( $(echo "$coverage > 80" | bc -l) )); then
            echo -e "${GREEN}✓ Code coverage: ${coverage}%${NC}"
        elif (( $(echo "$coverage > 60" | bc -l) )); then
            echo -e "${YELLOW}⚠ Code coverage: ${coverage}%${NC}"
        else
            echo -e "${RED}✗ Code coverage: ${coverage}%${NC}"
        fi
    fi
}

# Main execution
main() {
    local exit_code=0
    
    # Parse arguments
    case ${1:-all} in
        "unit")
            run_phpunit || exit_code=1
            ;;
        "e2e")
            run_e2e || exit_code=1
            ;;
        "snapshot")
            run_snapshot || exit_code=1
            ;;
        "db")
            validate_db || exit_code=1
            ;;
        "scenario")
            run_scenario ${2:-ratio} || exit_code=1
            ;;
        "coverage")
            run_phpunit || exit_code=1
            generate_coverage
            ;;
        "all")
            validate_db || exit_code=1
            run_phpunit || exit_code=1
            run_snapshot || exit_code=1
            run_e2e || exit_code=1
            generate_coverage
            ;;
        *)
            echo "Usage: $0 [unit|e2e|snapshot|db|scenario|coverage|all]"
            echo "       $0 scenario [ratio|duplicate|push|widget]"
            exit 1
            ;;
    esac
    
    echo ""
    echo "========================================="
    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}All tests passed successfully!${NC}"
    else
        echo -e "${RED}Some tests failed. Please review the output above.${NC}"
    fi
    echo "========================================="
    
    exit $exit_code
}

# Run main function
main "$@"