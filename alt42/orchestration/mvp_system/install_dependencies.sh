#!/bin/bash
# MVP System Dependency Installer
# Installs required Python packages for MVP system
#
# Usage: bash install_dependencies.sh
# Error Location: /mvp_system/install_dependencies.sh

echo "=== MVP System Dependency Installation ==="
echo "Timestamp: $(date '+%Y-%m-%d %H:%i:%S')"
echo ""

# Check Python 3 availability
if ! command -v python3 &> /dev/null; then
    echo "❌ ERROR [install_dependencies.sh:14]: Python 3 not found"
    echo "   Please install Python 3 before running this script"
    exit 1
fi

echo "✅ Python 3 found: $(python3 --version)"

# Check pip3 availability
if ! command -v pip3 &> /dev/null; then
    echo "❌ ERROR [install_dependencies.sh:23]: pip3 not found"
    echo "   Please install pip3: sudo apt-get install python3-pip"
    exit 1
fi

echo "✅ pip3 found: $(pip3 --version)"
echo ""

# Install PyYAML
echo "Installing PyYAML..."
if pip3 install PyYAML --user; then
    echo "✅ PyYAML installed successfully"
else
    echo "❌ ERROR [install_dependencies.sh:36]: Failed to install PyYAML"
    echo "   Try manual installation: pip3 install --user PyYAML"
    exit 1
fi

# Verify installation
echo ""
echo "=== Verifying Installation ==="
if python3 -c "import yaml; print('✅ PyYAML import successful - version:', yaml.__version__)" 2>&1; then
    echo ""
    echo "🎉 All dependencies installed successfully!"
    exit 0
else
    echo "❌ ERROR [install_dependencies.sh:49]: PyYAML import failed after installation"
    exit 1
fi
