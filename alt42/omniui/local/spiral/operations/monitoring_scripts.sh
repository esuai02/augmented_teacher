#!/bin/bash
# 
# Spiral Scheduler Operational Monitoring Scripts
# 
# @package    local_spiral
# @copyright  2024 MathKing
# @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
#

# Configuration
MOODLE_PATH="/home/moodle/public_html/moodle"
LOG_PATH="/var/log/moodle/spiral"
BASE_URL="https://mathking.kr/moodle"
EMAIL_ADMIN="admin@mathking.kr"

# Create log directory if not exists
mkdir -p "$LOG_PATH"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') - $1" | tee -a "$LOG_PATH/monitoring.log"
}

# Check cron registration
check_cron_registration() {
    log "=== Checking Cron Registration ==="
    
    cd "$MOODLE_PATH"
    
    # List scheduled tasks
    echo -e "${BLUE}Checking spiral tasks registration...${NC}"
    php admin/tool/task/cli/schedule_task.php --list | grep -E "(spiral|recompute)" || {
        echo -e "${RED}❌ Spiral tasks not found in scheduled tasks!${NC}"
        return 1
    }
    
    echo -e "${GREEN}✅ Spiral tasks found in cron${NC}"
    
    # Check specific task
    echo -e "${BLUE}Checking recompute_plans task...${NC}"
    php admin/tool/task/cli/schedule_task.php --list | grep "recompute_plans" && {
        echo -e "${GREEN}✅ recompute_plans task registered${NC}"
    } || {
        echo -e "${RED}❌ recompute_plans task not found!${NC}"
        return 1
    }
    
    log "Cron registration check completed"
}

# Manual task execution
execute_manual_task() {
    log "=== Executing Manual Task ==="
    
    cd "$MOODLE_PATH"
    
    echo -e "${BLUE}Executing recompute_plans task manually...${NC}"
    
    # Execute with timeout
    timeout 300 php admin/tool/task/cli/schedule_task.php \
        --execute=\\local_spiral\\task\\recompute_plans 2>&1 | tee "$LOG_PATH/manual_execution.log"
    
    local exit_code=$?
    
    if [ $exit_code -eq 0 ]; then
        echo -e "${GREEN}✅ Manual task execution completed successfully${NC}"
        log "Manual task execution: SUCCESS"
    elif [ $exit_code -eq 124 ]; then
        echo -e "${YELLOW}⚠️  Task execution timed out (5 minutes)${NC}"
        log "Manual task execution: TIMEOUT"
        return 2
    else
        echo -e "${RED}❌ Task execution failed with exit code $exit_code${NC}"
        log "Manual task execution: FAILED (exit code: $exit_code)"
        return 1
    fi
}

# Check KPI card rendering
check_kpi_rendering() {
    log "=== Checking KPI Card Rendering ==="
    
    echo -e "${BLUE}Testing main page accessibility...${NC}"
    
    # Check HTTP status
    local status_code=$(curl -s -o /dev/null -w "%{http_code}" \
        -H "User-Agent: SpiralMonitor/1.0" \
        --connect-timeout 10 \
        --max-time 30 \
        "$BASE_URL/local/spiral/index.php")
    
    if [ "$status_code" = "200" ]; then
        echo -e "${GREEN}✅ Main page accessible (HTTP $status_code)${NC}"
    elif [ "$status_code" = "302" ] || [ "$status_code" = "301" ]; then
        echo -e "${YELLOW}⚠️  Main page redirected (HTTP $status_code)${NC}"
        log "KPI rendering check: REDIRECT ($status_code)"
    else
        echo -e "${RED}❌ Main page not accessible (HTTP $status_code)${NC}"
        log "KPI rendering check: FAILED (HTTP $status_code)"
        return 1
    fi
    
    # Check for common errors in response
    echo -e "${BLUE}Checking for PHP errors...${NC}"
    local response=$(curl -s --connect-timeout 10 --max-time 30 \
        -H "User-Agent: SpiralMonitor/1.0" \
        "$BASE_URL/local/spiral/index.php" 2>/dev/null)
    
    if echo "$response" | grep -qi "fatal error\|parse error\|warning.*spiral"; then
        echo -e "${RED}❌ PHP errors detected in page response${NC}"
        echo "$response" | grep -i "error\|warning" | head -3
        log "KPI rendering check: PHP_ERRORS"
        return 1
    fi
    
    if echo "$response" | grep -qi "kpi.*dashboard\|kpi.*card"; then
        echo -e "${GREEN}✅ KPI components detected in response${NC}"
        log "KPI rendering check: SUCCESS"
    else
        echo -e "${YELLOW}⚠️  KPI components not clearly detected${NC}"
        log "KPI rendering check: KPI_NOT_FOUND"
    fi
}

# Check threshold alerts
check_threshold_alerts() {
    log "=== Checking Threshold Alerts ==="
    
    cd "$MOODLE_PATH"
    
    echo -e "${BLUE}Checking current KPI values...${NC}"
    
    # Get current KPIs via PHP script
    local kpi_output=$(php -r "
    require_once('config.php');
    try {
        \$kpi = \local_spiral\local\kpi_service::get_current_snapshot();
        echo json_encode(\$kpi, JSON_PRETTY_PRINT);
    } catch (Exception \$e) {
        echo 'ERROR: ' . \$e->getMessage();
        exit(1);
    }
    " 2>&1)
    
    local php_exit=$?
    
    if [ $php_exit -ne 0 ]; then
        echo -e "${RED}❌ Failed to retrieve KPI data${NC}"
        echo "$kpi_output"
        log "Threshold check: KPI_RETRIEVAL_FAILED"
        return 1
    fi
    
    echo -e "${GREEN}✅ KPI data retrieved successfully${NC}"
    echo "$kpi_output" | head -10
    
    # Parse and check thresholds
    local conflict_rate=$(echo "$kpi_output" | grep -o '"conflict":[0-9.]*' | cut -d: -f2 | tr -d ' ')
    local ratio_achievement=$(echo "$kpi_output" | grep -o '"ratio":[0-9.]*' | cut -d: -f2 | tr -d ' ')
    local completion_rate=$(echo "$kpi_output" | grep -o '"completion":[0-9.]*' | cut -d: -f2 | tr -d ' ')
    
    echo -e "${BLUE}Threshold Analysis:${NC}"
    
    # Check conflict rate > 5%
    if [ -n "$conflict_rate" ]; then
        if (( $(echo "$conflict_rate > 5" | bc -l) )); then
            echo -e "${RED}⚠️  HIGH CONFLICT RATE: $conflict_rate% (threshold: 5%)${NC}"
            log "ALERT: High conflict rate detected: $conflict_rate%"
        else
            echo -e "${GREEN}✅ Conflict rate OK: $conflict_rate%${NC}"
        fi
    fi
    
    # Check 7:3 ratio deviation
    if [ -n "$ratio_achievement" ]; then
        if (( $(echo "$ratio_achievement < 65 || $ratio_achievement > 75" | bc -l) )); then
            echo -e "${RED}⚠️  RATIO DEVIATION: $ratio_achievement% (target: 65-75%)${NC}"
            log "ALERT: 7:3 ratio deviation detected: $ratio_achievement%"
        else
            echo -e "${GREEN}✅ 7:3 ratio OK: $ratio_achievement%${NC}"
        fi
    fi
    
    # Check completion rate < 80%
    if [ -n "$completion_rate" ]; then
        if (( $(echo "$completion_rate < 80" | bc -l) )); then
            echo -e "${RED}⚠️  LOW COMPLETION RATE: $completion_rate% (threshold: 80%)${NC}"
            log "ALERT: Low completion rate detected: $completion_rate%"
        else
            echo -e "${GREEN}✅ Completion rate OK: $completion_rate%${NC}"
        fi
    fi
    
    log "Threshold check completed"
}

# Database health check
check_database_health() {
    log "=== Checking Database Health ==="
    
    cd "$MOODLE_PATH"
    
    echo -e "${BLUE}Checking spiral tables...${NC}"
    
    # Check table existence and basic stats
    local tables=("spiral_schedules" "spiral_sessions" "spiral_conflicts" "spiral_kpi_history")
    
    for table in "${tables[@]}"; do
        local count=$(php -r "
        require_once('config.php');
        try {
            \$count = \$DB->count_records('$table');
            echo \$count;
        } catch (Exception \$e) {
            echo 'ERROR';
            exit(1);
        }
        " 2>/dev/null)
        
        if [ "$count" = "ERROR" ]; then
            echo -e "${RED}❌ Table $table: ERROR${NC}"
            log "Database check: Table $table error"
        else
            echo -e "${GREEN}✅ Table $table: $count records${NC}"
        fi
    done
    
    # Check for recent activity
    echo -e "${BLUE}Checking recent activity...${NC}"
    local recent_sessions=$(php -r "
    require_once('config.php');
    try {
        \$count = \$DB->count_records_select('spiral_sessions', 'timecreated >= ?', [time() - 86400]);
        echo \$count;
    } catch (Exception \$e) {
        echo 'ERROR';
    }
    " 2>/dev/null)
    
    if [ "$recent_sessions" != "ERROR" ] && [ "$recent_sessions" -gt 0 ]; then
        echo -e "${GREEN}✅ Recent activity: $recent_sessions sessions created in last 24h${NC}"
    else
        echo -e "${YELLOW}⚠️  No recent session activity detected${NC}"
        log "Database check: No recent activity"
    fi
    
    log "Database health check completed"
}

# Performance monitoring
performance_check() {
    log "=== Performance Monitoring ==="
    
    echo -e "${BLUE}Testing page load performance...${NC}"
    
    # Measure response time
    local start_time=$(date +%s.%3N)
    local response=$(curl -s --connect-timeout 15 --max-time 30 \
        -w "%{time_total},%{http_code},%{size_download}" \
        -o /dev/null \
        "$BASE_URL/local/spiral/index.php")
    local end_time=$(date +%s.%3N)
    
    local total_time=$(echo "$response" | cut -d, -f1)
    local http_code=$(echo "$response" | cut -d, -f2)
    local size=$(echo "$response" | cut -d, -f3)
    
    echo "Response time: ${total_time}s"
    echo "HTTP status: $http_code"
    echo "Response size: $size bytes"
    
    # Check performance thresholds
    if (( $(echo "$total_time > 5.0" | bc -l) )); then
        echo -e "${RED}⚠️  SLOW RESPONSE: ${total_time}s (threshold: 5s)${NC}"
        log "PERFORMANCE ALERT: Slow response time: ${total_time}s"
    elif (( $(echo "$total_time > 3.0" | bc -l) )); then
        echo -e "${YELLOW}⚠️  Response time warning: ${total_time}s${NC}"
        log "Performance warning: Response time: ${total_time}s"
    else
        echo -e "${GREEN}✅ Response time OK: ${total_time}s${NC}"
    fi
    
    log "Performance check completed"
}

# Disk space check
disk_space_check() {
    log "=== Checking Disk Space ==="
    
    echo -e "${BLUE}Checking Moodle data directory space...${NC}"
    
    local moodle_data_path="/var/moodledata"
    if [ -d "$moodle_data_path" ]; then
        local usage=$(df -h "$moodle_data_path" | awk 'NR==2 {print $5}' | sed 's/%//')
        echo "Moodle data usage: ${usage}%"
        
        if [ "$usage" -gt 90 ]; then
            echo -e "${RED}⚠️  DISK SPACE CRITICAL: ${usage}%${NC}"
            log "DISK ALERT: Critical disk space: ${usage}%"
        elif [ "$usage" -gt 80 ]; then
            echo -e "${YELLOW}⚠️  Disk space warning: ${usage}%${NC}"
            log "Disk warning: High disk usage: ${usage}%"
        else
            echo -e "${GREEN}✅ Disk space OK: ${usage}%${NC}"
        fi
    fi
    
    # Check log directory space
    local log_usage=$(du -sh "$LOG_PATH" 2>/dev/null | cut -f1)
    echo "Spiral log directory usage: $log_usage"
    
    log "Disk space check completed"
}

# Email alert function
send_alert_email() {
    local subject="$1"
    local message="$2"
    
    if command -v mail >/dev/null 2>&1; then
        echo "$message" | mail -s "$subject" "$EMAIL_ADMIN"
        log "Alert email sent to $EMAIL_ADMIN"
    else
        log "Mail command not available, alert not sent"
    fi
}

# Comprehensive health check
health_check_all() {
    log "========================================="
    log "Starting Spiral Scheduler Health Check"
    log "========================================="
    
    local errors=0
    local warnings=0
    
    # Run all checks
    check_cron_registration || ((errors++))
    echo
    
    check_database_health || ((warnings++))
    echo
    
    check_kpi_rendering || ((errors++))
    echo
    
    check_threshold_alerts || ((warnings++))
    echo
    
    performance_check || ((warnings++))
    echo
    
    disk_space_check || ((warnings++))
    echo
    
    # Summary
    echo -e "${BLUE}=========================================${NC}"
    echo -e "${BLUE}HEALTH CHECK SUMMARY${NC}"
    echo -e "${BLUE}=========================================${NC}"
    
    if [ $errors -eq 0 ] && [ $warnings -eq 0 ]; then
        echo -e "${GREEN}✅ ALL SYSTEMS OPERATIONAL${NC}"
        log "Health check completed: ALL_OK"
    elif [ $errors -eq 0 ]; then
        echo -e "${YELLOW}⚠️  SYSTEM OK WITH WARNINGS ($warnings warnings)${NC}"
        log "Health check completed: WARNINGS ($warnings)"
    else
        echo -e "${RED}❌ SYSTEM ISSUES DETECTED ($errors errors, $warnings warnings)${NC}"
        log "Health check completed: ERRORS ($errors) WARNINGS ($warnings)"
        
        # Send alert email for errors
        send_alert_email "Spiral Scheduler System Issues" \
            "Health check detected $errors critical errors and $warnings warnings. Please check the monitoring logs."
    fi
    
    echo "Detailed logs available at: $LOG_PATH/monitoring.log"
    log "========================================="
}

# Main script
main() {
    case "${1:-help}" in
        "cron")
            check_cron_registration
            ;;
        "execute")
            execute_manual_task
            ;;
        "kpi")
            check_kpi_rendering
            ;;
        "alerts")
            check_threshold_alerts
            ;;
        "database"|"db")
            check_database_health
            ;;
        "performance"|"perf")
            performance_check
            ;;
        "disk")
            disk_space_check
            ;;
        "health"|"all")
            health_check_all
            ;;
        "help"|*)
            echo "Spiral Scheduler Monitoring Scripts"
            echo ""
            echo "Usage: $0 [command]"
            echo ""
            echo "Commands:"
            echo "  cron        - Check cron registration"
            echo "  execute     - Execute manual task"
            echo "  kpi         - Check KPI card rendering"
            echo "  alerts      - Check threshold alerts"
            echo "  database    - Check database health"
            echo "  performance - Check system performance"
            echo "  disk        - Check disk space"
            echo "  health      - Run all health checks"
            echo "  help        - Show this help"
            echo ""
            echo "Examples:"
            echo "  $0 health          # Run complete health check"
            echo "  $0 execute         # Manually execute recompute task"
            echo "  $0 alerts          # Check for threshold violations"
            ;;
    esac
}

# Run main function with all arguments
main "$@"