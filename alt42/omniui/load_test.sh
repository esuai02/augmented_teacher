#!/bin/bash

# Load Testing Script for attendance_teacher.php
# Tests performance under various load conditions

echo "================================================"
echo "🚀 LOAD TESTING SUITE FOR attendance_teacher.php"
echo "================================================"
echo ""

# Configuration
BASE_URL="https://mathking.kr/moodle/local/augmented_teacher/alt42/omniui/attendance_teacher.php"
TEST_DURATION=60  # seconds
REPORT_FILE="load_test_report.txt"
JSON_REPORT="load_test_results.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if required tools are installed
check_requirements() {
    echo "🔍 Checking requirements..."
    
    # Check for curl
    if ! command -v curl &> /dev/null; then
        echo -e "${RED}❌ curl is not installed${NC}"
        echo "Please install curl: sudo apt-get install curl"
        exit 1
    fi
    
    # Check for jq (for JSON processing)
    if ! command -v jq &> /dev/null; then
        echo -e "${YELLOW}⚠️  jq is not installed (optional for JSON reports)${NC}"
        echo "Install with: sudo apt-get install jq"
    fi
    
    # Check for ab (Apache Bench)
    if ! command -v ab &> /dev/null; then
        echo -e "${YELLOW}⚠️  Apache Bench not installed, using curl only${NC}"
        USE_AB=false
    else
        USE_AB=true
    fi
    
    echo -e "${GREEN}✅ Requirements check completed${NC}"
    echo ""
}

# Function to measure single request performance
measure_single_request() {
    local userid=$1
    local start_time=$(date +%s%N)
    
    response=$(curl -s -o /dev/null -w "%{http_code},%{time_total},%{size_download}" \
        "${BASE_URL}?userid=${userid}")
    
    local end_time=$(date +%s%N)
    local duration=$(( ($end_time - $start_time) / 1000000 )) # Convert to milliseconds
    
    echo "$response,$duration"
}

# Test 1: Single User Performance
test_single_user() {
    echo -e "${BLUE}📊 Test 1: Single User Performance${NC}"
    echo "Testing response time for single user requests..."
    
    local total_time=0
    local min_time=999999
    local max_time=0
    local iterations=10
    
    for i in $(seq 1 $iterations); do
        result=$(measure_single_request 1)
        IFS=',' read -r http_code time_total size_download duration <<< "$result"
        
        time_ms=$(echo "$time_total * 1000" | bc)
        time_int=${time_ms%.*}
        
        total_time=$((total_time + time_int))
        
        if [ $time_int -lt $min_time ]; then
            min_time=$time_int
        fi
        
        if [ $time_int -gt $max_time ]; then
            max_time=$time_int
        fi
        
        echo "  Request $i: ${time_int}ms (HTTP $http_code, ${size_download} bytes)"
    done
    
    avg_time=$((total_time / iterations))
    
    echo ""
    echo "  Results:"
    echo "    • Average: ${avg_time}ms"
    echo "    • Min: ${min_time}ms"
    echo "    • Max: ${max_time}ms"
    
    # Performance grade
    if [ $avg_time -lt 500 ]; then
        echo -e "    • Grade: ${GREEN}EXCELLENT${NC} (<500ms)"
    elif [ $avg_time -lt 1000 ]; then
        echo -e "    • Grade: ${GREEN}GOOD${NC} (<1s)"
    elif [ $avg_time -lt 2000 ]; then
        echo -e "    • Grade: ${YELLOW}ACCEPTABLE${NC} (<2s)"
    else
        echo -e "    • Grade: ${RED}POOR${NC} (>2s)"
    fi
    
    echo ""
}

# Test 2: Concurrent Users
test_concurrent_users() {
    echo -e "${BLUE}📊 Test 2: Concurrent Users Performance${NC}"
    echo "Testing with multiple concurrent users..."
    
    local concurrent_levels=(5 10 20 50)
    
    for concurrency in "${concurrent_levels[@]}"; do
        echo "  Testing with $concurrency concurrent users..."
        
        if [ "$USE_AB" = true ]; then
            # Use Apache Bench if available
            ab_result=$(ab -n 100 -c $concurrency -g ab_plot_${concurrency}.tsv \
                "${BASE_URL}?userid=1" 2>&1 | grep -E "Requests per second:|Time per request:|Failed requests:")
            echo "    $ab_result"
        else
            # Fallback to parallel curl requests
            start_time=$(date +%s)
            
            for i in $(seq 1 $concurrency); do
                curl -s -o /dev/null "${BASE_URL}?userid=$i" &
            done
            
            wait
            
            end_time=$(date +%s)
            duration=$((end_time - start_time))
            
            echo "    • Completed $concurrency requests in ${duration}s"
            rps=$((concurrency / duration))
            echo "    • Approximate requests/second: $rps"
        fi
    done
    
    echo ""
}

# Test 3: Cache Performance
test_cache_performance() {
    echo -e "${BLUE}📊 Test 3: Cache Performance${NC}"
    echo "Testing cache effectiveness..."
    
    # First request (cold cache)
    echo "  Cold cache test:"
    cold_result=$(measure_single_request 1)
    IFS=',' read -r http_code cold_time size_download duration <<< "$cold_result"
    cold_ms=$(echo "$cold_time * 1000" | bc)
    echo "    • First request: ${cold_ms}ms"
    
    # Immediate second request (warm cache)
    echo "  Warm cache test:"
    warm_result=$(measure_single_request 1)
    IFS=',' read -r http_code warm_time size_download duration <<< "$warm_result"
    warm_ms=$(echo "$warm_time * 1000" | bc)
    echo "    • Cached request: ${warm_ms}ms"
    
    # Calculate improvement
    improvement=$(echo "scale=2; (($cold_ms - $warm_ms) / $cold_ms) * 100" | bc)
    echo "    • Cache improvement: ${improvement}%"
    
    if (( $(echo "$improvement > 50" | bc -l) )); then
        echo -e "    • Cache effectiveness: ${GREEN}EXCELLENT${NC}"
    elif (( $(echo "$improvement > 30" | bc -l) )); then
        echo -e "    • Cache effectiveness: ${GREEN}GOOD${NC}"
    elif (( $(echo "$improvement > 10" | bc -l) )); then
        echo -e "    • Cache effectiveness: ${YELLOW}MODERATE${NC}"
    else
        echo -e "    • Cache effectiveness: ${RED}POOR${NC}"
    fi
    
    echo ""
}

# Test 4: Stress Test
test_stress() {
    echo -e "${BLUE}📊 Test 4: Stress Test${NC}"
    echo "Running stress test for $TEST_DURATION seconds..."
    
    local start_time=$(date +%s)
    local end_time=$((start_time + TEST_DURATION))
    local request_count=0
    local error_count=0
    
    while [ $(date +%s) -lt $end_time ]; do
        # Random user ID between 1 and 100
        userid=$((RANDOM % 100 + 1))
        
        # Make request
        http_code=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}?userid=${userid}")
        
        request_count=$((request_count + 1))
        
        if [ "$http_code" != "200" ]; then
            error_count=$((error_count + 1))
        fi
        
        # Show progress every 10 requests
        if [ $((request_count % 10)) -eq 0 ]; then
            echo -ne "\r  Requests: $request_count | Errors: $error_count"
        fi
    done
    
    echo ""
    echo "  Results:"
    echo "    • Total requests: $request_count"
    echo "    • Failed requests: $error_count"
    echo "    • Success rate: $(echo "scale=2; (($request_count - $error_count) / $request_count) * 100" | bc)%"
    echo "    • Requests/second: $(echo "scale=2; $request_count / $TEST_DURATION" | bc)"
    
    echo ""
}

# Test 5: Memory Leak Detection
test_memory_pattern() {
    echo -e "${BLUE}📊 Test 5: Memory Pattern Analysis${NC}"
    echo "Monitoring memory usage pattern..."
    
    echo "  Making 100 sequential requests..."
    
    for i in $(seq 1 100); do
        curl -s -o /dev/null "${BASE_URL}?userid=$((i % 10 + 1))"
        
        if [ $((i % 20)) -eq 0 ]; then
            echo -ne "\r  Progress: $i/100"
        fi
    done
    
    echo ""
    echo "  ✅ Memory pattern test completed"
    echo "  Note: Check server logs for memory usage patterns"
    echo ""
}

# Generate JSON report
generate_json_report() {
    echo "📄 Generating JSON report..."
    
    cat > $JSON_REPORT <<EOF
{
    "timestamp": "$(date -Iseconds)",
    "test_file": "attendance_teacher.php",
    "test_duration": $TEST_DURATION,
    "tests": {
        "single_user": {
            "status": "completed",
            "metrics": {
                "avg_response_time": "${avg_time:-0}ms",
                "min_response_time": "${min_time:-0}ms",
                "max_response_time": "${max_time:-0}ms"
            }
        },
        "concurrent_users": {
            "status": "completed",
            "note": "See detailed output above"
        },
        "cache_performance": {
            "status": "completed",
            "improvement": "${improvement:-0}%"
        },
        "stress_test": {
            "status": "completed",
            "total_requests": ${request_count:-0},
            "error_count": ${error_count:-0},
            "requests_per_second": $(echo "scale=2; ${request_count:-0} / $TEST_DURATION" | bc)
        }
    },
    "overall_grade": "$(calculate_overall_grade)"
}
EOF
    
    echo -e "${GREEN}✅ JSON report saved to $JSON_REPORT${NC}"
}

# Calculate overall grade
calculate_overall_grade() {
    # Simple grade calculation based on avg response time
    if [ ${avg_time:-9999} -lt 500 ]; then
        echo "A"
    elif [ ${avg_time:-9999} -lt 1000 ]; then
        echo "B"
    elif [ ${avg_time:-9999} -lt 2000 ]; then
        echo "C"
    else
        echo "D"
    fi
}

# Generate text report
generate_text_report() {
    {
        echo "================================================"
        echo "LOAD TEST REPORT - $(date)"
        echo "================================================"
        echo ""
        echo "File: attendance_teacher.php"
        echo "URL: $BASE_URL"
        echo "Test Duration: $TEST_DURATION seconds"
        echo ""
        echo "Test Results Summary:"
        echo "--------------------"
        echo "1. Single User Avg Response: ${avg_time:-N/A}ms"
        echo "2. Cache Improvement: ${improvement:-N/A}%"
        echo "3. Stress Test Requests: ${request_count:-N/A}"
        echo "4. Error Rate: ${error_count:-0} errors"
        echo ""
        echo "Overall Grade: $(calculate_overall_grade)"
        echo ""
        echo "Recommendations:"
        echo "---------------"
        
        if [ ${avg_time:-9999} -gt 1000 ]; then
            echo "• Response time is high. Consider implementing additional caching."
        fi
        
        if [ ${error_count:-0} -gt 0 ]; then
            echo "• Errors detected during stress test. Review error logs."
        fi
        
        if (( $(echo "${improvement:-0} < 30" | bc -l) )); then
            echo "• Cache effectiveness is low. Review caching strategy."
        fi
        
        echo ""
        echo "================================================"
    } > $REPORT_FILE
    
    echo -e "${GREEN}✅ Text report saved to $REPORT_FILE${NC}"
}

# Main execution
main() {
    echo "Starting load tests at $(date)"
    echo ""
    
    check_requirements
    
    test_single_user
    test_concurrent_users
    test_cache_performance
    test_stress
    test_memory_pattern
    
    generate_text_report
    generate_json_report
    
    echo ""
    echo "================================================"
    echo -e "${GREEN}✅ ALL LOAD TESTS COMPLETED${NC}"
    echo "================================================"
    echo ""
    echo "Reports generated:"
    echo "  • Text report: $REPORT_FILE"
    echo "  • JSON report: $JSON_REPORT"
    echo ""
    echo "Overall Performance Grade: $(calculate_overall_grade)"
    echo ""
}

# Run main function
main