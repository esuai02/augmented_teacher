#!/bin/bash
# Test runner script for MVP system
# Execute this on the server where PHP is installed

echo "==================================="
echo "MVP Exception Tests"
echo "==================================="
echo ""

# Check if PHP is available
if ! command -v php &> /dev/null; then
    echo "ERROR: PHP is not installed or not in PATH"
    exit 1
fi

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Run the test
php tests/unit/MvpExceptionTest.php

# Capture exit code
EXIT_CODE=$?

echo ""
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ All tests passed successfully"
else
    echo "❌ Tests failed with exit code: $EXIT_CODE"
fi

exit $EXIT_CODE
