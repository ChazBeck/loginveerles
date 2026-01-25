#!/bin/bash
#
# Shared Header Installation Script
# This script clones the shared header repository and sets it up
#

echo "🎨 Shared Header Installation"
echo "=============================="
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
echo "Installing to: $SCRIPT_DIR"
echo ""

# Check if sharedheader directory already exists
if [ -d "$SCRIPT_DIR/sharedheader" ]; then
    echo "⚠️  sharedheader directory already exists!"
    read -p "Do you want to remove it and reinstall? (y/N) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Removing old installation..."
        rm -rf "$SCRIPT_DIR/sharedheader"
    else
        echo "Installation cancelled."
        exit 1
    fi
fi

# Clone the repository
echo "📦 Cloning shared header repository..."
git clone https://github.com/ChazBeck/sharedheader.git "$SCRIPT_DIR/sharedheader"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Installation successful!"
    echo ""
    echo "📝 Next steps:"
    echo "1. Test the integration: http://localhost/apps/auth/test_sharedheader.php"
    echo "2. Read the integration guide: SHAREDHEADER_INTEGRATION.md"
    echo "3. Update your applications to use the shared header"
    echo ""
    echo "📂 Files installed to: $SCRIPT_DIR/sharedheader/"
    echo ""
else
    echo ""
    echo "❌ Installation failed!"
    echo ""
    echo "Manual installation:"
    echo "1. Go to: https://github.com/ChazBeck/sharedheader"
    echo "2. Download the repository as ZIP"
    echo "3. Extract to: $SCRIPT_DIR/sharedheader/"
    echo ""
fi
